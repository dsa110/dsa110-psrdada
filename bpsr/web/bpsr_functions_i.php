<?PHP

function getResultsInfo($utc_start, $results_dir) {

  $obs_dir = $results_dir."/".$utc_start;

  $data = array();

  $freq_channels = getSubDirs($obs_dir);

  $data["nbeams"] = count($freq_channels);

  $cmd = "find ".$obs_dir." -name \"obs.start\" | tail -n 1";
  $an_obs_start = exec($cmd);
  $data["obs_start"] = $an_obs_start;

  if (file_exists($obs_dir."/obs.txt")) {
    $data["annotation"] = exec("cat ".$obs_dir."/obs.txt");
  } else {
    $data["annotation"] = "";
  }

  for ($i=0; $i<count($freq_channels); $i++) {

    # For each channel, check for the existence of images
    $dir = $obs_dir."/".$freq_channels[$i];

    $data[$i]["dir"] = "/results/".$utc_start."/".$freq_channels[$i];

    /* If no archives have been produced */
    if (count($freq_channels) == 0) {
      $all_results[$observations[$i]]["obs_start"] = "unset";
    }
  } 

  return $data;
}


/*
 * Gets the most recent image/results files from a BPSR observation
 */

function getBPSRResults($results_dir, $utc_start="latest", $type="all", $size="all") {

  $utc_starts = array();
  $types = array();
  $sizes = array();
  $results = array();

  if (is_array($utc_start)) {
    $utc_starts = $utc_start;
  } else if ($utc_start == "all") {
    $cmd = "ls -1 ".$results_dir;
    $str = exec($cmd);
    $utc_starts = split("\n", rtrim($str));
  } else if ($utc_start == "latest") {
    $cmd = "ls -1 ".$results_dir." | tail -n 1";
    $utc_starts = array(exec($cmd));
  } else {
    $utc_starts = array($utc_start);
  }

  if (is_array($type)) {
    $types = $type;
  } else if ($type == "all") {
    $types = array("bp", "ts", "fft", "dts");
  } else {
    $types = array($type);
  }
                                                                                                             
  if (is_array($size)) {
    $sizes = $size;
  }else if ($size == "all") {
    $sizes = array("1024x768", "400x300", "112x84");
  } else {
    $sizes = array($size);
  }

  foreach ($utc_starts as $u) {

    $dir = $results_dir."/".$u;

    /* now find the 13 files requested */
    if ($handle = opendir($dir)) {
      while (false !== ($file = readdir($handle))) {

        if ($file != "." && $file != "..") {
                                                                                                             
          /* If this is a beam?? subdirectory */
          if ( (is_dir($dir."/".$file)) && (ereg("^([0-9][0-9])$", $file)) ) {
                                                                                                             
            /* Get into a relative dir... */
            chdir($dir);
            $beamid = (int) $file;

            # Foreach image type
            foreach ($types as $t) {

              foreach ($sizes as $s) {
    
                $img = "/images/blankimage.gif";
                /* Find the hi res images */
                $cmd = "find ".$file." -name \"*.".$t."_".$s.".png\" | sort -n";
                $find_result = exec($cmd, $array, $return_val);
                if (($return_val == 0) && (strlen($find_result) > 1)) {
                  $img = "/bpsr/results/".$u."/".$find_result;
                }
                $results[$u][($beamid-1)][$t."_".$s] = $img;
              }
            }
          }
        }                                                                                                   
      }
      closedir($handle);
    } else {
      echo "Could not open plot directory: ".$dir."<BR>\n";
    }
  }

  return $results;
}



function getServerLogInformation() {

  $arr = array();
  $arr["bpsr_tcs_interface"] =   array("logfile" => "bpsr_tcs_interface.log", "name" => "TCS Interface", "tag" => "server");
  $arr["bpsr_results_manager"] = array("logfile" => "bpsr_results_manager.log", "name" => "Results Mngr", "tag" => "server");
  $arr["dada_pwc_command"] =     array("logfile" => "dada_pwc_command.log", "name" => "dada_pwc_command", "tag" => "server");
  $arr["ibob_simulator"] =       array("logfile" => "ibob_simualtor.log", "name" => "IBOB Sim", "tag" => "server");
  $arr["bpsr_pwc_monitor"] =     array("logfile" => "nexus.pwc.log", "name" => "PWC", "tag" => "pwc");
  $arr["bpsr_sys_monitor"] =     array("logfile" => "nexus.sys.log", "name" => "SYS", "tag" => "sys");
  $arr["bpsr_src_monitor"] =     array("logfile" => "nexus.src.log", "name" => "SRC", "tag" => "src");
  return $arr;

}


function getClientLogInformation() {

  $arr = array();
  #$arr["bpsr_master_control"] = array("logfile" => "bpsr_master_control.log", "name" => "master_control";
  $arr["bpsr_observation_manager"] = array("logfile" => "nexus.sys.log", "name" => "Obs Mngr", "tag" => "obs mngr");
  $arr["bpsr_results_monitor"]     = array("logfile" => "nexus.sys.log", "name" => "Results Mon", "tag" => "results mon");
  $arr["processor"]                = array("logfile" => "nexus.src.log", "name" => "Processor", "tag" => "proc");
  #$arr["bpsr_pwc_monitor"]         = array("logfile" => "nexus.pwc.log", "name" => "PWC", "tag" => "pwc");
  #$arr["bpsr_sys_monitor"]         = array("logfile" => "nexus.sys.log", "name" => "SYS", "tag" => "sys");
  #$arr["bpsr_src_monitor"]         = array("logfile" => "nexus.src.log", "name" => "SRC", "tag" => "src");

  return $arr;

}

function getServerDaemonNames() {
                                                                                                                                                                              
  $arr = array();
  $arr["bpsr_tcs_interface"] = "TCS Interface";
  $arr["bpsr_pwc_monitor"] = "PWC Mon";
  $arr["bpsr_sys_monitor"] = "SYS Mon";
  $arr["bpsr_src_monitor"] = "SRC Mon";
  $arr["bpsr_results_manager"] = "Results Mngr";
  $arr["ibob_simulator"] = "IBOB Sim.";
  $arr["ibob_connection_manager"] = "IBOB conn.";
  $arr["dada_pwc_command"] = "PWCC";
  return $arr;
}
                                                                                                                                                                              
function getClientDaemonNames() {

  $arr = array();

  $arr["bpsr_master_control"] = "Master Ctrl";
  $arr["bpsr_observation_manager"] = "Obs. Mngr";
  $arr["bpsr_results_monitor"] = "Results Monitor";
  $arr["processor"] = "Processor";

  return $arr;
}
                                                                                                                                                                              
function getClientDaemonTypes() {
  $arr = array();
                                                                                                                                                                              
  $arr["bpsr_master_control"] = "none";
  $arr["bpsr_observation_manager"] = "sys";
  $arr["bpsr_results_monitor"] = "sys";
  $arr["processor"] = "src";
  return $arr;
}

function getClientDaemonTags() {

  $arr = array();

  $arr["bpsr_master_control"] = "none";
  $arr["bpsr_observation_manager"] = "obs mngr";
  $arr["bpsr_results_monitor"] = "results mon";
  $arr["processor"] = "proc";
  return $arr;
}
                                                                                                                                                                              
function getServerDaemonTypes() {
                                                                                                                                                                              
  $arr = array();
  $arr["bpsr_tcs_interface"] = "bpsr_tcs_interface";
  $arr["pwc_monitor"] = "nexus.pwc";
  $arr["sys_monitor"] = "nexus.sys";
  $arr["src_monitor"] = "nexus.src";
  $arr["bpsr_results_manager"] = "bpsr_results_manager";
  $arr["dada_pwc_command"] = "dada_pwc_command";
  $arr["ibob_simulator"] = "ibob_simulator";
  $arr["ibob_connection_manager"] = "ibob_connection_manager";
  return $arr;
}




?>
