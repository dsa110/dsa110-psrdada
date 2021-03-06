<?PHP

include_once("functions_i.php");

define("INSTRUMENT", "mopsr");
define("CFG_FILE", "/home/dada/linux_64/share/mopsr.cfg");
define("AQ_FILE",  "/home/dada/linux_64/share/mopsr_aq.cfg");
define("BF_FILE",  "/home/dada/linux_64/share/mopsr_bf.cfg");
define("BP_FILE",  "/home/dada/linux_64/share/mopsr_bp.cfg");
define("BS_FILE",  "/home/dada/linux_64/share/mopsr_bs.cfg");
define("CNR_FILE", "/home/dada/linux_64/share/mopsr_cornerturn.cfg");
define("BP_CNR_FILE", "/home/dada/linux_64/share/mopsr_bp_cornerturn.cfg");
define("SP_FILE",  "/home/dada/linux_64/share/mopsr_signal_paths.txt");
define("CSS_FILE", "/mopsr/mopsr.css");

/* MySQL config file location */
define(MYSQL_DB_CONFIG_FILE, "/home/dada/linux_64/share/mysql-config.php");

include_once("site_definitions_i.php");
include_once("instrument.lib.php");
date_default_timezone_set('Australia/Sydney');

class mopsr extends instrument
{
  function mopsr()
  {
    instrument::instrument(INSTRUMENT, CFG_FILE, URL_FULL);

    $this->css_path = CSS_FILE;
    $this->banner_image = "/mopsr/images/mopsr_logo_480x60.png";
    $this->banner_image_repeat = "/mopsr/images/mopsr_logo_1x60.png";
    $this->fav_icon = "/mopsr/images/mopsr_favicon.ico";

    $cornerturn_cfg  = $this->configFileToHash(CNR_FILE);
    $this->config = array_merge($this->config, $cornerturn_cfg);

    $aq_cfg  = $this->configFileToHash(AQ_FILE);
    $this->config = array_merge($this->config, $aq_cfg);
  }

  function serverLogInfo()
  {
    $arr = array();
    $arr["mopsr_tmc_interface"]          = array("logfile" => "mopsr_tmc_interface.log", "name" => "TMC Interface", "tag" => "server", "shortname" => "TMC");
    $arr["mopsr_results_manager"]        = array("logfile" => "mopsr_results_manager.log", "name" => "Results Mngr", "tag" => "server", "shortname" => "Results");
    $arr["mopsr_results_manager_tb"]     = array("logfile" => "mopsr_results_manager_tb.log", "name" => "Results Mngr TB", "tag" => "server", "shortname" => "Results");
    $arr["mopsr_results_mgr"]            = array("logfile" => "mopsr_results_mgr.log", "name" => "Results Mgr", "tag" => "server", "shortname" => "Results");
    $arr["mopsr_web_monitor"]            = array("logfile" => "mopsr_web_monitor.log", "name" => "Web Monitor", "tag" => "server", "shortname" => "Monitor");
    $arr["mopsr_event_monitor"]          = array("logfile" => "mopsr_event_monitor.log", "name" => "Event Monitor", "tag" => "server", "shortname" => "EMon");
    $arr["mopsr_swin_transferrer"]       = array("logfile" => "mopsr_swin_transferrer.log", "name" => "Swin Transfer", "tag" => "server", "shortname" => "SwinXfer");
    $arr["mopsr_frb_manager"]            = array("logfile" => "mopsr_frb_manager.log", "name" => "FRB Manager", "tag" => "server", "shortname" => "FRBMngr");
    $arr["mopsr_frb_detector"]           = array("logfile" => "mopsr_frb_detector.log", "name" => "FRB Detector", "tag" => "server", "shortname" => "FRBDetector");

    $arr["mopsr_pwc_monitor"]            = array("logfile" => "nexus.pwc.log", "name" => "PWC", "tag" => "pwc", "shortname" => "PWC");
    $arr["mopsr_sys_monitor"]            = array("logfile" => "nexus.sys.log", "name" => "SYS", "tag" => "sys", "shortname" => "SYS");
    $arr["mopsr_src_monitor"]            = array("logfile" => "nexus.src.log", "name" => "SRC", "tag" => "src", "shortname" => "SRC");
    $arr["mopsr_bf_sys_monitor"]         = array("logfile" => "bfs.sys.log", "name" => "BF SYS", "tag" => "sys", "shortname" => "BF_SYS");
    $arr["mopsr_bf_src_monitor"]         = array("logfile" => "bfs.src.log", "name" => "BF SRC", "tag" => "src", "shortname" => "BF_SRC");
    $arr["mopsr_bp_sys_monitor"]         = array("logfile" => "bps.sys.log", "name" => "BP SYS", "tag" => "sys", "shortname" => "BP_SYS");
    $arr["mopsr_bp_src_monitor"]         = array("logfile" => "bps.src.log", "name" => "BP SRC", "tag" => "src", "shortname" => "BP_SRC");
    $arr["mopsr_bs_sys_monitor"]         = array("logfile" => "bss.sys.log", "name" => "BS SYS", "tag" => "sys", "shortname" => "BS_SYS");
    $arr["mopsr_bs_src_monitor"]         = array("logfile" => "bss.src.log", "name" => "BS SRC", "tag" => "src", "shortname" => "BS_SRC");

    $arr["mopsr_ib_receiver"]            = array("logfile" => "mopsr_ib_receiver.log", "name" => "IB Rcv", "tag" => "ib_rcv", "shortname" => "IB_Rcv");
    $arr["mopsr_rx_monitor"]             = array("logfile" => "mopsr_ib_receiver.log", "name" => "RX Mon", "tag" => "rx_mon", "shortname" => "RX_Mon");
    return $arr;
  }

  function clientLogInfo() {

    $arr = array();
    $arr["mopsr_observation_manager"] = array("logfile" => "nexus.sys.log", "name" => "Obs Mngr", "tag" => "obs_mngr");
    $arr["mopsr_results_monitor"]     = array("logfile" => "nexus.sys.log", "name" => "Results Mon", "tag" => "results_mon");
    $arr["mopsr_archive_manager"]     = array("logfile" => "nexus.sys.log", "name" => "Archive Mngr", "tag" => "archive_mngr");
    $arr["mopsr_pwc"]                 = array("logfile" => "nexus.pwc.log", "name" => "PWC", "tag" => "pwc");
    $arr["mopsr_mux_send"]            = array("logfile" => "nexus.sys.log", "name" => "Mux Send", "tag" => "muxsend");
    $arr["mopsr_aqdsp"]               = array("logfile" => "nexus.sys.log", "name" => "AQDSP", "tag" => "aqdsp");
    $arr["mopsr_aq_diskdb"]           = array("logfile" => "nexus.sys.log", "name" => "DiskDB", "tag" => "aqdisk");
    $arr["mopsr_aq_cleaner"]          = array("logfile" => "nexus.sys.log", "name" => "Cleaner", "tag" => "aqcleaner");
    $arr["mopsr_aq_mask_manager"]     = array("logfile" => "nexus.sys.log", "name" => "Mask Manager", "tag" => "aqmask");
    $arr["mopsr_aq_dump_manager"]     = array("logfile" => "nexus.sys.log", "name" => "Dump Manager", "tag" => "dump_xfer");
    $arr["mopsr_aq_dbevent"]          = array("logfile" => "nexus.sys.log", "name" => "Event", "tag" => "event");
    $arr["mopsr_aq_dumper"]           = array("logfile" => "nexus.sys.log", "name" => "Dumper", "tag" => "aqdumper");
    $arr["mopsr_superb"]              = array("logfile" => "nexus.sys.log", "name" => "Superb Mon", "tag" => "superb");
    $arr["mopsr_dbsplitdb"]           = array("logfile" => "nexus.sys.log", "name" => "split", "tag" => "split");
    $arr["mopsr_dbantsdb"]            = array("logfile" => "nexus.sys.log", "name" => "Select Ants", "tag" => "ants");
    $arr["mopsr_dspsr"]               = array("logfile" => "nexus.sys.log", "name" => "DSPSR", "tag" => "proc");
    $arr["mopsr_proc"]                = array("logfile" => "nexus.sys.log", "name" => "Generic Proc", "tag" => "proc");
    $arr["mopsr_dumper"]              = array("logfile" => "nexus.sys.log", "name" => "Dumper", "tag" => "dump");

    $arr["mopsr_mux_recv"]            = array("logfile" => "bfs.sys.log", "name" => "Mux Recv", "tag" => "mux recv");
    $arr["mopsr_bf_transpose"]        = array("logfile" => "bfs.sys.log", "name" => "Transpose", "tag" => "bf xpose");
    $arr["mopsr_bf_cleaner"]          = array("logfile" => "bfs.sys.log", "name" => "Cleaner", "tag" => "bfcleaner");
    $arr["mopsr_bf_rescale"]          = array("logfile" => "bfs.sys.log", "name" => "Rescale", "tag" => "bf scale");
    $arr["mopsr_bf_process"]          = array("logfile" => "bfs.sys.log", "name" => "Proc", "tag" => "bf proc");
    $arr["mopsr_bf_archive_manager"]  = array("logfile" => "bfs.sys.log", "name" => "Archive Mngr", "tag" => "archive mngr");
    $arr["mopsr_bfdsp"]               = array("logfile" => "bfs.sys.log", "name" => "DSP", "tag" => "bfdsp");
    $arr["mopsr_bf_results_mon"]      = array("logfile" => "bfs.src.log", "name" => "Results Mon", "tag" => "results mon");
    $arr["mopsr_bf_corr"]             = array("logfile" => "bfs.sys.log", "name" => "Corr", "tag" => "corr");
    $arr["mopsr_bf_corr_results"]     = array("logfile" => "bfs.sys.log", "name" => "Corr Results", "tag" => "corr_results");
    $arr["mopsr_bf_tbs"]              = array("logfile" => "bfs.sys.log", "name" => "TBs", "tag" => "tbs");
    $arr["mopsr_bf_tb0"]              = array("logfile" => "bfs.sys.log", "name" => "TB0", "tag" => "tb0");
    $arr["mopsr_bf_tb1"]              = array("logfile" => "bfs.sys.log", "name" => "TB1", "tag" => "tb1");
    $arr["mopsr_bf_tb2"]              = array("logfile" => "bfs.sys.log", "name" => "TB2", "tag" => "tb2");
    $arr["mopsr_bf_tb3"]              = array("logfile" => "bfs.sys.log", "name" => "TB3", "tag" => "tb3");
    $arr["mopsr_bf_tbs_results"]      = array("logfile" => "bfs.sys.log", "name" => "TBs Results", "tag" => "tbs_results");
    $arr["mopsr_bf_dbevent"]          = array("logfile" => "bfs.sys.log", "name" => "Event", "tag" => "event");
    $arr["mopsr_bf_dumper"]           = array("logfile" => "bfs.sys.log", "name" => "Dumper", "tag" => "dumper");

    # Beam Processor Scripts
    $arr["mopsr_bp_send"]             = array("logfile" => "bps.sys.log", "name" => "Send", "tag" => "bp_send");
    $arr["mopsr_bp_recv"]             = array("logfile" => "bps.sys.log", "name" => "Recv", "tag" => "bp_recv");
    $arr["mopsr_bp_split"]            = array("logfile" => "bps.sys.log", "name" => "Split", "tag" => "bp_split");
    $arr["mopsr_bp_sigproc"]          = array("logfile" => "bps.sys.log", "name" => "Sigproc", "tag" => "bp_sigproc");
    $arr["mopsr_bp_injector"]          = array("logfile" => "bps.sys.log", "name" => "Injector", "tag" => "bpinjector");
    $arr["mopsr_bp_digifil"]          = array("logfile" => "bps.sys.log", "name" => "Digifil", "tag" => "bp_digifil");
    $arr["mopsr_bp_fb_manager"]       = array("logfile" => "bps.sys.log", "name" => "FB Manager", "tag" => "bp_fb_mngr");
    $arr["mopsr_bp_fb_decimate"]      = array("logfile" => "bps.sys.log", "name" => "FB Decimator", "tag" => "bp_fb_deci");
    $arr["mopsr_bp_integrate"]        = array("logfile" => "bps.sys.log", "name" => "Int", "tag" => "bp_int");
    $arr["mopsr_bp_process"]          = array("logfile" => "bps.sys.log", "name" => "Proc", "tag" => "bp_proc");
    $arr["mopsr_bp_reblock"]          = array("logfile" => "bps.sys.log", "name" => "Reblock", "tag" => "bp_reblock");
    $arr["mopsr_bp_heimdall"]         = array("logfile" => "bps.sys.log", "name" => "Heimdall", "tag" => "bp_heimdall");
    $arr["mopsr_bp_cands_mon"]        = array("logfile" => "bps.sys.log", "name" => "Cands Mon", "tag" => "bp_cands_mon");
    $arr["mopsr_bp_cleaner"]          = array("logfile" => "bps.sys.log", "name" => "Cleaner", "tag" => "bpclenaer");
    $arr["mopsr_bp_frb_detector"]     = array("logfile" => "bps.sys.log", "name" => "FRB Detector", "tag" => "frb_detector");

    # Beam Smirfer Scripts
    $arr["mopsr_bs_nepenthes"]        = array("logfile" => "bss.sys.log", "name" => "Nepenthes", "tag" => "bs_nepenthes");
    $arr["mopsr_bs_stitcher"]         = array("logfile" => "bss.sys.log", "name" => "Sticther", "tag" => "bs_stitcher");
    $arr["mopsr_bs_dspsr"]            = array("logfile" => "bss.sys.log", "name" => "Dspsr", "tag" => "bs_dspsr");
    $arr["mopsr_bs_results_mgr"]      = array("logfile" => "bss.sys.log", "name" => "Results Mgr", "tag" => "bs_results");
    $arr["mopsr_bs_distrib_beams"]    = array("logfile" => "bss.sys.log", "name" => "Beam Distrib", "tag" => "bs_distrib");
    $arr["mopsr_bs_transfer"]         = array("logfile" => "bss.sys.log", "name" => "Cand Transfer", "tag" => "bs_transfer");
    $arr["mopsr_bs_cleaner"]          = array("logfile" => "bss.sys.log", "name" => "Cleaner", "tag" => "bs_cleaner");

    return $arr;

  }

  function getClientStatusMessages($config)
  {
    $status = instrument::getPWCStatusMessages($config);

    $message_types = array("pwc", "src", "sys");
    $message_classes = array("warn", "error");
    $message_class_values = array("warn" => STATUS_WARN, "error" => STATUS_ERROR);
    $status_dir = $config["STATUS_DIR"];

    for ($i=0; $i<$config["NUM_DISTRIB"]; $i++) {
      
      $host = $config["DISTRIB_".$i];

      for ($j=0; $j<count($message_types); $j++) {

        $message_type = $message_types[$j];

        for ($k=0; $k<count($message_classes); $k++) {

          $message_class = $messsage_classes[$k];

          $fname = $status_dir."/".$host.".".$message_type.".".$message_class;
          if (file_exists($fname)) {
            $status[$host."_".$message_type."_STATUS"]  = $message_class_values[$message_class];
            $status[$host."_".$message_type."_MESSAGE"] = instrument::getSingleStatusMessage($fname);
          } else {
            $status[$host."_".$message_type."_STATUS"] = STATUS_OK;
             $status[$host."_".$message_type."_MESSAGE"] = "";
          }
        }
      }
    }

    return $status;
  }

  #
  # Over-ride baseclass method
  #
  function getObsImages($dir, $sources = array())
  {
    # determine how many antenna / beam 
    $rval = 0;
    if (count($sources) == 0)
    {
      $cmd = "find ".$dir." -mindepth 1 -maxdepth 1 -type d -printf '%f\n' | sort -n";
      $line = exec($cmd, $sources, $rval);
    }

    $results = array();
    foreach ($sources as $source)
    {
      $images = array();
      $cmd = "find ".$dir." -mindepth 1 -maxdepth 1 -type f -name '????-??-??-??:??:??.".$source.".??.*x*.png' -printf '%f\n' | sort -n";
      $line = exec($cmd, $images, $rval);
        
      if ($line != "")
      {
        if ( $source == "TB" ) {
          $cmd = "ls ".$dir."/TB/J*_f.tot | awk -F/ '{print \$NF}' | awk -F_ '{print $1}'";
          $line = exec($cmd, $source_from_tb, $rval);
          $source = $source_from_tb[0];
        }
        $results[$source] = array();
        foreach ($images as $image)
        {
          list ($utc, $source2, $type, $res, $ext) = explode (".", $image);
          $results[$source][$type."_".$res] = $image;
        }
      }
    }
    
    return $results;
  }

  function getObsSources($dir) 
  {
    # determine how many pulsars are present
    $rval = 0;
    $ants = array();
    $cmd = "find ".$dir." -mindepth 1 -maxdepth 1 -type d -printf '%f\n'";
    $line = exec($cmd, $ants, $rval);

    $results = array();
    foreach ($ants as $ant)
    {
      $tots = array();
      $cmd = "find ".$dir."/".$ant." -mindepth 1 -maxdepth 1 -type f -name '*.tot' -printf '%f\n'";
      $line = exec($cmd, $tots, $rval);

      $results[$ant] = array();
      foreach ($tots as $tot)
      {
        $arr = split("_", $tot, 3);
        if (count($arr) == 3)
          $s = $arr[0]."_".$arr[1];
        else
          $s = $arr[0];

        if (!array_key_exists($s, $results[$ant]))
          $results[$ant][$s] = array();

        if (strpos($tot, "_t") !== FALSE) 
        {
          $results[$ant][$s]["int"]     = $this->getIntergrationLength($dir."/".$ant."/".$tot);
          $results[$ant][$s]["src"]     = $this->getArchiveName($dir."/".$ant."/".$tot);
          $results[$ant][$s]["dm"]      = instrument::getSourceDM($results[$ant][$s]["src"]);
          $results[$ant][$s]["p0"]      = instrument::getSourcePeriodMS($results[$ant][$s]["src"]);
          $results[$ant][$s]["nsubint"] = $this->getNumSubints($dir."/".$ant."/".$tot);
        }

        if (strpos($tot, "_f") !== FALSE) 
        {
          $results[$ant][$s]["snr"]     = instrument::getSNR($dir."/".$ant."/".$tot);
        }
      }
    }

    return $results;
  }

  function addToRA($ra, $min)
  {
    $parts = split(":", $ra);
    $hh = $mm = $ss = 0;
    if (count($parts) >= 1)
      $hh = $parts[0];
    if (count($parts) >= 2)
      $mm = $parts[1];
    if (count($parts) == 3)
      $ss = $parts[2];

    $hours = $hh + ($mm/60) + ($ss / 3600);

    $hours += ($min / 60);

    $hh = floor($hours);

    $minutes = 60 * ($hours - $hh);
    $mm = floor($minutes);

    $seconds = 60 * ($minutes - $mm);
    $ss = floor($seconds);

    $ss_remainder = $seconds - $ss;

    $new_ra = sprintf("%02d", $hh).":".sprintf("%02d", $mm).":".sprintf("%02d", $ss).".".substr(sprintf("%0.1f", $ss_remainder),2);

    return ($new_ra);
  }

  function addToDEC ($dec, $min)
  {
    $parts = split(":", $dec);
    $dd = $mm = $ss = 0;
    if (count($parts) >= 1)
      $dd = $parts[0];
    if (count($parts) >= 2)
      $mm = $parts[1];
    if (count($parts) == 3)
      $ss = $parts[2];

    $degrees = $dd + ($mm / 60) + ($ss / 3600);

    $degrees += ($min / 60);

    $dd = floor($degrees);

    $minutes = 60 * ($degrees - $dd);
    $mm = floor($minutes);

    $seconds = 60 * ($minutes - $mm);
    $ss = floor($seconds);

    $ss_remainder = $seconds - $ss;

    $new_dec = sprintf("%02d", $dd).":".sprintf("%02d", $mm).":".sprintf("%02d", $ss).".".substr(sprintf("%0.1f", $ss_remainder),2);

    return ($new_dec);
  }

  function readSignalPaths()
  {
    $hash = $this->configFileToHash(SP_FILE);

    $sps = array();
    foreach ($hash as $key => $val)
    {
      $pfb = str_replace(" ", "_", $val);
      $sps[$pfb] = $key;
    }
    return $sps;
  }

} // END OF CLASS DEFINITION
