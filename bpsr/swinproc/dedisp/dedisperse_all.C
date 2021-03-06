/*
  dedisperse  - dedisperses raw filterbank data or folded pulse profiles
*/
#define LONG64BIT unsigned long long
#include <stdlib.h>
#include "string.h"
#include <libgen.h>
#include "gtools.h"
#ifdef HAVE_CONFIG_H
#include <config.h>
#endif
extern "C" {
#include "dedisperse_all.h"
};
#include "getDMtable.h"
//#include "gtools.h"

void inline_dedisperse_all_help(){
  fprintf(stderr,"dedisperse_all help\n");
  fprintf(stderr,"Usage: dedisperse_all filename [options]\n");
  fprintf(stderr,"-v                 verbose mode (prints status every DM trial)\n");
  fprintf(stderr,"-k killfilename    kill all channels in killfilename\n");
  fprintf(stderr,"-d st_DM end_DM    dedisperse from st_DM to end_DM\n");
  fprintf(stderr,"-i [40] psr width  intrinsic pulse width in us\n");
  fprintf(stderr,"-tol [1.25]        smear tolerance, e.g. 25%% = 1.25\n");
  fprintf(stderr,"-g gulpsize        number of samples to dedisp at once\n");
  fprintf(stderr,"-n Nsamptotal      Only do Nsamptotal samples\n");
  fprintf(stderr,"-s Nsamps          Skip Nsamp samples before starting\n");
  fprintf(stderr,"-m Nsub            Create files with Nsub subbands\n");
  fprintf(stderr,"-l                 Create logfile of exact DMs used\n");
  fprintf(stderr,"-G                 Do giant burst search.\n");
  fprintf(stderr,"Gburst suboptions:\n");
  fprintf(stderr,"    -wid N [d: 30] allow N bin tolerance between discrete bursts\n");
  fprintf(stderr,"    -sig N [d: 6] search N-sigma threshold\n");
  fprintf(stderr,"    -dec N [d: 256] search up to a N=2^? bin matched filter\n");
  fprintf(stderr,"    -cut N [d: 3] RFI filter: DMs below which to disregard\n");
  fprintf(stderr,"                  low-DM-peaking pulse candidates\n");
  fprintf(stderr,"    -file NAME [d: ./GResults.txt] file to write Gsearch results to\n");
  fprintf(stderr," \n");
  fprintf(stderr,"dedisperse_all uses OpenMP and 16 bit words to\n");
  fprintf(stderr,"create many dedispersed files at once in a highly\n");
  fprintf(stderr,"parallel manner. It is for use on 64 bit machines\n");
  fprintf(stderr,"but still works on 32 bit machines.\n");
  fprintf(stderr,"Currently tested on 96x1bit files, 512x1bit files, 1024x2bit files.\n");
#ifdef HAVE_OPENMP
  fprintf(stderr,"Compiled with OpenMP: Multi-threading enabled\n");
#else
  fprintf(stderr,"**SINGLE THREADED MODE**\nLink against OpenMP (-fopenmp with GNU on gcc > 4.2 for multi-threaded)\n");
#endif
}

FILE *input, *output, *outfileptr, *dmlogfileptr;
char  inpfile[180], outfile[180], ignfile[180], dmlogfilename[180];
char outfile_root[180];

/* global variables describing the operating mode */
int ascii, asciipol, stream, swapout, headerless, nbands, userbins, usrdm, baseline, clipping, sumifs, profnum1, profnum2, nobits, wapp_inv, wapp_off;
double refrf,userdm,fcorrect;
float clipvalue,jyf1,jyf2;
int fftshift;
#include "wapp_header.h"
#include "key.h"
struct WAPP_HEADER *wapp;
struct WAPP_HEADER head;

/*
  tsamp in seconds, f0, df in MHz
  returns the DM for a given delay between the top and
  bottom frequency channel in samples.
*/

int load_killdata(int * killdata,int nchans,char * killfile){
  FILE * kptr;
  char line[100];
  kptr = fopen(killfile,"r");
    if (kptr==NULL){
      fprintf(stderr,"Error opening file %s\n",killfile);
      exit(-2);
    }
    for (int i=0; i<nchans;i++) {
      if (fgets(line,20,kptr)!=NULL){  // Read in whole line
	int nscanned = sscanf(line,"%d",&killdata[i]);
	if (nscanned==0) {
	  fprintf(stderr,"Could not scan %s as 1 or 0\n",line);
	  exit(-1);
	}
      } else{
	fprintf(stderr,"Error reading %dth value from %s\n",i,killfile);
	exit(-1);
      }
    }
  fclose(kptr);
  return(0);
}

float get_DM(int nsamples, int nchan, double tsamp, double f0, double df){

  float DM;
  float nu1,nu2,nu1_2,nu2_2;

  //printf("5vals %d %d %lf %lf %lf\n",nsamples,nchan,tsamp,f0,df);

  if (nsamples==0) return(0.0);

  nu1 = f0;
  nu2 = f0 + (nchan-1)*df;
  nu1_2 = 1.0e6/(nu1*nu1);
  nu2_2 = 1.0e6/(nu2*nu2);

  DM = (float) nsamples * tsamp/4.15e-3 * 1.0/(nu2_2-nu1_2);
  return(DM);
}

/*
  Returns the shift in samples for a given DM. Units as above.
*/

int DM_shift(float DM, int nchan, double tsamp, double f0, double df){

  float shift;
  float nu1,nu2,nu1_2,nu2_2;

  if (nchan==0) return(0);

  nu1 = f0;
  nu2 = f0 + (nchan)*df;
  nu1_2 = 1.0e6/(nu1*nu1);
  nu2_2 = 1.0e6/(nu2*nu2);

  //printf("nu1 %f nu2 %f DM %f\n",nu1,nu2,DM);

  shift = 4.15e-3 * DM * (nu2_2-nu1_2);
  //printf("shift is %f (s)\n",shift);
  //printf("shift in samples is %f\n",shift/tsamp);
  return ((int) (shift/tsamp+0.5));
}


// does the dedispersion for one gulp, one DM trial.                                               
void do_dedispersion(unsigned short int ** storage, unsigned short int * unpackeddata, int nbands, int ntodedisp, int ntoload, float DMtrial,int * killdata){
    int idelay,start_chan,end_chan;
    LONG64BIT * casted_times;
    int chans_per_band = (int)(nchans/nbands);
    
    for (int iband=0;iband<nbands;iband++){
	casted_times = (LONG64BIT*) storage[iband];
	start_chan = iband*chans_per_band;
	end_chan = (iband+1)*chans_per_band;
	double fch1_subband = fch1 + foff*start_chan; // offset to the start of the subband              
	for (int j=0;j<(ntodedisp)/4;j++)casted_times[j]=0;
	for (int k=start_chan;k<end_chan;k++){
	  if (killdata[k]==1){
	    idelay = DM_shift(DMtrial,k-start_chan,tsamp,fch1_subband,foff);
	    int stride = k*ntoload+idelay;
#if HAVE_OPENMP
//#pragma omp parallel for private(j)
#endif
	    for (int j=0;j<ntodedisp/4;j++){
		casted_times[j]+=*((LONG64BIT*) (unpackeddata+(j*4+stride)));
	    }
	  } // killdata
	} // channel #
    }
}


int main (int argc, char *argv[])
{
  /* local variables */
  char string[180];
  int i,useroutput=0,nfiles=0,fileidx,sigproc,scan_number,subscan=0;
  int numsamps=0;
  unsigned char * rawdata;
  unsigned short int * unpacked; //, * times;
  int nbytesraw;
  int ibyte,j,k;
  unsigned char abyte;
  unsigned short int ** times;
  int nread;
  float DM_trial;
  int ndm=0;
  double ti = 40.0;
  double tol = 1.25;
  float total_MBytes = 0;
  float start_DM=0.0, end_DM;
  int counts;
  int dmlogfile=0, verbose=0;
  int readsamp = 0;
  int nreadsamp = 0;
  int skip = 0;
  int nskip = 0;
  int ntoload;
  int ntodedisp;
  int maxdelay = 0;
  int appendable = 0;
  int ngulp; //max number of samples at a time
  int gulping = 0;
  int ngulpsize;
  int nsampleft; 
  char * killfile;
  int killing=0;
  bool doGsearch = 0;
  int Gwidtol = 30;
  float Gthresh = 6;
  int Gscrnch = 256;
  float Girrel = 3;
  char * Gfilename = "GResults.txt";
  float flo,fhi;

  if(sizeof(LONG64BIT) != 8 ){
	  fprintf(stderr,"ERROR: sofware has been compiled with LONG64BIT as a datatype of %d bytes, needs to be 8\n",sizeof(LONG64BIT));
  }

  /* check number of command-line arguments and print help if necessary */
  if (argc<2) {
    inline_dedisperse_all_help();
    exit(0);
  }
  
  /* print help if necessary */
  if (strcmp(argv[1],"-h")==0) {
    inline_dedisperse_all_help();
    exit(0);
  }
  
  /* set up default globals */
  userbins=usrdm=asciipol=stream=clipping=swapout=headerless=0;
  sumifs=wapp_inv=wapp_off=barycentric=0;
  nobits=32;
  ascii=1;
  fftshift=1;
  profnum1 = 0;
  profnum2 = 1000;
  nbands=baseline=1;
  clipvalue=refrf=userdm=fcorrect=0.0;
  refdm=-1.0;
  output=NULL;
  strcpy(ignfile,"");

  // **************************************
  //          PARSE COMMAND LINE
  // **************************************
  i = 1;
  while (i<argc) {
    if (fopen(argv[i],"rb")!=NULL){
      strcpy(inpfile,argv[i]);
      input=fopen(inpfile,"rb");
      if (input==NULL){
        fprintf(stderr,"Error opening file %s\n",inpfile);
        exit(-1);
      }
      nfiles++;
    }
    else if (!strcmp(argv[i],"-d")) {
      /* get DM from the user */
      start_DM=atof(argv[++i]);
      end_DM=atof(argv[++i]);
      usrdm=1;
    }
    else if (!strcmp(argv[i],"-l")) {
      /* Create a log file of the DMs */
      dmlogfile=1;
    }
    else if (!strcmp(argv[i],"-i")) {
      /* set intrinsic width */
      ti=atof(argv[++i]);
    }
    else if (!strcmp(argv[i],"-tol")) {
      /* set tolerance level (e.g. 25% = 1.25)*/
      tol=atof(argv[++i]);
    }
    else if (!strcmp(argv[i],"-v")) {
      verbose=1;
    }
    else if (!strcmp(argv[i],"-n")) {
      /* read only X samples */
      ntodedisp=atoi(argv[++i]);
      readsamp=1;
    }
    else if (!strcmp(argv[i],"-s")) {
      /* skip first X samples */
      nskip=atoi(argv[++i]);
      skip=1;
    }
    else if (!strcmp(argv[i],"-m")) {
      /* sub-band */
      nbands=atoi(argv[++i]);
    }
    else if (!strcmp(argv[i],"-g")) {
      ngulpsize=atoi(argv[++i]);
      gulping=1;
    }
    else if (!strcmp(argv[i],"-a")) {
      appendable=1;
    }
    else if (!strcmp(argv[i],"-k")) {
      killing = 1;
      killfile = (char *) malloc(strlen(argv[++i])+1);
      strcpy(killfile,argv[i]);
    }
    else if (!strcmp(argv[i],"-G")) {
      doGsearch = 1;
    }
    else if (!strcmp(argv[i],"-wid")) {
      Gwidtol = atoi(argv[++i]);
    }
    else if (!strcmp(argv[i],"-sig")) {
      Gthresh = atof(argv[++i]);
    }
    else if (!strcmp(argv[i],"-dec")) {
      Gscrnch = atoi(argv[++i]);
    }
    else if (!strcmp(argv[i],"-cut")){
      Girrel = atof(argv[++i]);
    }
    else if (!strcmp(argv[i],"-file")){
      Gfilename = (argv[++i]);
    }
    else {
      /* unknown argument passed down - stop! */
      inline_dedisperse_all_help();
      fprintf(stderr,"unknown argument (%s) passed to %s\n\n",argv[i],argv[0]);
      exit(1);
    }
    i++;
  }


  // **************************************
  //            ERROR TESTING
  // **************************************
  if (doGsearch && nbands>1){
    fprintf(stderr,"Can't do gsearch while running in subband mode!\n");
    exit(-1);
  }

  if (!useroutput) {
    /* no output file selected, use standard output */
    output=stdout;
    strcpy(outfile,"stdout");
  }

  if (!nfiles) {
    fprintf(stderr,"File not supplied on command line; please supply filename here: ");
    strcpy(inpfile,"stdin");
    input=fopen(inpfile,"rb");
    if (input==NULL){
      fprintf(stderr,"Error opening file %s\n",inpfile);
      exit(-1);
    }
    nfiles=1;
  }
  fileidx=1;
  if (nfiles>1) {
    fprintf(stderr,"Multi file mode not supported yet!\n");
    exit(-1);
  }
  char tmp[180];
  char *tmp2;
  strcpy(tmp,inpfile); // These few lines strip the input file's
  tmp2 = basename(tmp);// name from it's directory tag.
  strcpy(outfile_root,tmp2);


  // **************************************
  //         VALUE PRECALCULATION
  // **************************************  
  /* read in the header to establish what the input data are... */
  sigproc=read_header(input);
  if (!sigproc) {
    fprintf(stderr,"Not sigproc data\n");
    exit(-1);
  }
  if (foff > 0.0) {
    fprintf(stderr,"dedisperse can't handle low->high frequency ordering!");
    exit(1);
  }

  // But in case low->high frequency ordering ever gets implemented...
  if (foff < 0){
    flo = (float)fch1 + (nchans*(float)foff);
    fhi = (float)fch1;
  } else {
    flo = (float)fch1;
    fhi = (float)fch1 + (nchans*(float)foff);
  }

  if (fileidx == 1) {
    /* this is filterbank data */
    if (output!=stdout) output=fopen(outfile,"wb");
    if (output==NULL){
      fprintf(stderr,"Error opening file %s\n",output);
      exit(-1);
    }
  }
  
  numsamps = nsamples(inpfile,sigproc,nbits,nifs,nchans);	/* get numsamps */
  if (usrdm) maxdelay = DM_shift(end_DM,nchans,tsamp,fch1,foff);
  cout << "maxdelay = " << maxdelay << endl;
  
  if (readsamp) numsamps=ntodedisp+maxdelay;
  
  if (gulping) {
    if (ngulpsize>numsamps) ngulpsize = numsamps-maxdelay;
    ntodedisp=ngulpsize;
  } else {
    ntodedisp=numsamps-maxdelay;
    ngulpsize=ntodedisp;
  }

  ntoload = ntodedisp + maxdelay; 
  
  nbytesraw = nchans * ntoload * nbits/8;
  if ((rawdata = (unsigned char *) malloc(nbytesraw))==NULL){
      fprintf(stderr,"Error allocating %d bytes of RAM for raw data\n",nbytesraw);
      exit(-1);
  }
  // skip either 0 or nskip samples
  fseek(input, nskip*nchans*nbits/8, SEEK_CUR);
  nsampleft-=nskip;
  // some values used for unpacking
  int sampperbyte = (int)(8/nbits);
  int andvalue = (int)pow(2,nbits)-1;


  // **************************************
  //      SET UP DMTABLE AND SUBBANDS
  // **************************************  
  float * DMtable;
  // a hack for less files when subbanding... M.Keith
  if (nbands==1)
    getDMtable(start_DM,end_DM, tsamp*1e6, ti, foff, (fch1+(nchans/2-0.5)*foff)/1000,nchans/nbands, tol, &ndm, DMtable);
  else
    getDMtable(0.0,end_DM, tsamp*1e6, ti, foff, (fch1+(nchans/2-0.5)*foff)/1000,nchans/nbands, tol, &ndm, DMtable);
  
  fprintf(stderr,"%d subbands from %d chans\n",nbands,nchans);

  if(nchans % nbands){
	fprintf(stderr,"invalid number of subbands selected!\n");
	exit(1);
  }

  int nwholegulps = (numsamps - maxdelay)/ngulpsize;
  int nleft = numsamps - ngulpsize * nwholegulps;
  int ngulps = nwholegulps + 1;


  //  if (!debird){
    unpacked = (unsigned short int *) malloc(nchans*ntoload*
					     sizeof(unsigned short int));
    if (unpacked==NULL) {
      fprintf(stderr,"Failed to allocate space for unpacked %d bytes\n",
	      (int)(nchans*ntoload*sizeof(unsigned short int)));
      exit(-2);
    }
    times = (unsigned short int **) 
      malloc(sizeof(unsigned short int*)*nbands); 
    for(int band=0; band < nbands; band++){
	times[band] = (unsigned short int *)
		      malloc(sizeof(unsigned short int)*ntodedisp);
    }
    if (times==NULL){
      fprintf(stderr,"Error allocating times array\n");
      exit(-1);
    }
    //  } // (!debird)

  int * killdata = new int[nchans];
  if (killing) int loaded = load_killdata(killdata,nchans,killfile);
  else
    {
      killing=1;
      for (int i=0;i<nchans;i++) killdata[i]=1; // ie don't kill anything.
    }

  int rotate = 0;
  while(nchans*(pow(2,nbits)-1)/(float)nbands/(float)(pow(2,rotate)) > 255)
    rotate++;
  
  printf("Dividing output by %d to scale to 1 byte per sample per subband\n",(int)(pow(2,rotate)));

  // Set up gpulse control variables
  GPulseState Gholder(ndm); // Giant pulse state to hold trans-DM detections
  int Gndet;                // Integer number of detections in this beam
  int *Gresults;            // Integer array of results: contained as cand1.start.bin cand1.end.bin cand2.start.bin cand2.end.bin...... etc)

  // Start of main loop
  for (int igulp=0; igulp<ngulps;igulp++){

//rewind a few samples if not at first gulp
    if (igulp!=0){
	    fprintf(stderr,"Skipping back %d bytes\n",maxdelay*nbits*nchans/8);
	    int ret = fseek(input,-1*maxdelay*nbits*nchans/8,SEEK_CUR);
	    if(ret){
		    fprintf(stderr,"Could not skip backwards!\n");
		    exit(1);
	    }
    }
    if (igulp==nwholegulps) {
      ntoload = nleft;
      nbytesraw = ntoload*nbits*nchans/8;
      ntodedisp = nleft - maxdelay;
    }

//read gulp from file
    fprintf(stderr,"Gulp %d Loading %d samples, i. e. %d bytes of Raw Data\n",igulp, ntoload, nbytesraw);
    nread = fread(rawdata,nbytesraw,1,input);
    
    if (nread != 1){
      fprintf(stderr, "Failed to read %d nread = %d \n", nbytesraw, nread);
      exit(-1);
    }
  
    /* Unpack it if dedispersing */
    
    if (1){
      if (verbose) fprintf(stderr,"Reordering data\n");
      // all time samples for a given freq channel in order in RAM

      for (ibyte=0;ibyte<nchans/sampperbyte;ibyte++){
#if HAVE_OPENMP
#pragma omp parallel for private (abyte,k,j)
#endif
        for (j=0;j<ntoload;j++){
          abyte = rawdata[ibyte+j*nchans/sampperbyte];
	  for (k=0;k<8;k+=nbits)
            unpacked[j+((ibyte*8+k)/(int)nbits)*ntoload]=(unsigned short int)((abyte>>k)&andvalue);
	  }
      }
      
      if (1==0){ // Old way.
      if (killing){
	for (int i=0;i<nchans;i++){
	  if (!killdata[i]){
	    cout << i << " " ;
#if HAVE_OPENMP
#pragma omp parallel for private(j)	  
#endif
	    for (int j=0;j<ntoload;j++)
	      unpacked[j+i*ntoload]=0;
	  }
	}
      }
      }
      
      cout << endl; // flush the buffer
      
      /* for each DM dedisperse it */
      /* Start with zero DM */
      if (!usrdm) end_DM=1e3;
      if (dmlogfile){
	sprintf(dmlogfilename,"%s.dmlog",outfile_root);
	dmlogfileptr=fopen(dmlogfilename,"w");
	if (dmlogfileptr==NULL) {
	  fprintf(stderr,"Error opening file %s\n",dmlogfilename);
	  exit(-3);
	}
      }
      if (igulp==0) appendable=0; else appendable=1;
      for (int idm=0;idm<ndm;idm++)
	{
	  //DM_trial = get_DM(idm,nchans,tsamp,fch1,foff);
	  DM_trial = DMtable[idm];
	  if (DM_trial>=start_DM && DM_trial<=end_DM){
	    if (verbose) fprintf(stderr,"DM trial #%d at  DM=%f ",idm,DM_trial);
	    if (dmlogfile) fprintf(dmlogfileptr,"%f %07.2f\n",DM_trial,
				   DM_trial);
	    // Name output file, dmlog file
	    if(nbands==1)sprintf(outfile,"%s.%07.2f.tim",outfile_root,DM_trial);
	    else sprintf(outfile,"%s.%07.2f.sub",outfile_root,DM_trial);
	    // open it
	    if (appendable) outfileptr=fopen(outfile,"a");
	    if (!appendable){
	      outfileptr=fopen(outfile,"w");
	      // create a log of the 'sub' dms to dedisperse at
	      // These DMs are chosen fairly arbitraraly, rather than
	      // based on any ridgid mathematics.
	      if(nbands > 1){
		float* DMtable_sub;
		int ndm_sub;
		float sub_start_DM=0;
		float sub_end_DM=end_DM;
		if (idm > 0){
		  sub_start_DM =  (DMtable[idm]+ DMtable[idm-1])/2.0;
		}
		if (idm < ndm-1){
		  sub_end_DM =  (DMtable[idm]+ DMtable[idm+1])/2.0;
		}
		// make the dm table for the sub-banded data...
		getDMtable(sub_start_DM,sub_end_DM, tsamp*1e6, ti, foff, (fch1+(nchans/2-0.5)*foff)/1000,
			   nchans, tol, &ndm_sub, DMtable_sub);
		char subdm_filename[180];
		sprintf(subdm_filename,"%s.valid_dms",outfile);
		FILE* subdm_fileptr = fopen(subdm_filename,"w");
		if(subdm_fileptr == NULL){
		  printf("Error: could not open file %s for writing\n",subdm_filename);
		  exit(2);
		}
		for(int ix = 0; ix < ndm_sub; ix++){
		  double sub_dm_trial = DMtable_sub[ix];
		  fprintf(subdm_fileptr,"%f\n",sub_dm_trial);
		}
		delete[] DMtable_sub;
		fclose(subdm_fileptr);
	      }
	    }
	    output=outfileptr;
	    // write header variables into globals
	    refdm = DM_trial;
	    nobits = 8;
	    // write header
	    if (!appendable) dedisperse_header();
	    // do the dedispersion
	    do_dedispersion(times, unpacked, nbands, ntodedisp, ntoload, DM_trial, killdata);
	    
	    // Do the Gsearch for this DM trial
	    if (doGsearch){
	      Gholder.searchforgiants(idm,ntodedisp,times[0],Gthresh,Gwidtol,Gscrnch,DM_trial,1);
	    }
	    
	    // write data
	    if (1==1){
	      unsigned char lotsofbytes[ntodedisp];
	      for (int d=0;d<ntodedisp;d++){
		for(int iband=0; iband<nbands; iband++){
		unsigned short int twobytes = times[iband][d]>>rotate;
		//		unsigned char onebyte = twobytes;
		lotsofbytes[d]=(twobytes-128);
		}
	      }
	    fwrite(lotsofbytes,ntodedisp,1,outfileptr);
	    }
	    // close file
	    fclose(outfileptr);
	    if (verbose) fprintf(stderr,"%d bytes to file %s\n", ntodedisp*nbands, outfile);
	    total_MBytes+= (ntodedisp*nbands)/1.0e6;
	  } // if DM is in range
	} // DM for loop
      if (verbose) fprintf(stderr,"Wrote a total of %6.1f MB to disk\n",total_MBytes);
      /* close log files if on last input file */
      if (dmlogfile) fclose(dmlogfileptr);
    } //if (!debird)
    
    // After gulp's done, pump out the Gsearch results for that gulp
    if (doGsearch){
      Gresults = Gholder.givetimes(&Gndet,tsamp,flo,fhi,Girrel,-1,Gfilename);
      for (i=0;i<ndm;i++) Gholder.DMtrials[i].erase(Gholder.DMtrials[i].begin(),Gholder.DMtrials[i].end());
//      fprintf(stderr,"GRESULTS:\n");
//      for (int i=0;i<Gndet;i+=2){
//	  fprintf (stderr,"Detection %d: %d\t%d\n",i,Gresults[i],Gresults[i+1]);
//      }
    }

  }
  return(0); // exit normally
} // main
