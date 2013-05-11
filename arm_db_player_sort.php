<?php
// ------------------------------------------------------------------
// PHP to read the ARM player excel sheet and update player list
// ------------------------------------------------------------------
// ------------------------------------------------------------------
// Debug
// ------------------------------------------------------------------

// Debug enable/disable
$debug_msg = 1;
$split = array();
// ------------------------------------------------------------------
// ------------------------------------------------------------------
// Functions
// ------------------------------------------------------------------
include 'arm_db_functions.php';
// ------------------------------------------------------------------
// ------------------------------------------------------------------
//
//  DATABASE CONNECTION
//
// ------------------------------------------------------------------
// ------------------------------------------------------------------
// Initialize DB variables
// ------------------------------------------------------------------
$user_name = "root";
$password = "";
$database = "armcricket";
$server = "localhost";

//$user_name = "sql27119";
//$password = "pA2%jK8!";
//$database = "sql27119";
//$server = "sql2.freemysqlhosting.net";
$db_handle = mysql_connect($server, $user_name, $password);
$db_found = mysql_select_db($database, $db_handle);
// ------------------------------------------------------------------
// ------------------------------------------------------------------
// Read player list
// ------------------------------------------------------------------

$file = @fopen('arm_crick_player_list.csv', "r") or exit("Unable to open file!");
$i=0;
$split = array();
while(!feof($file))
{
  $curr_line = fgets($file);
  $split = preg_split('%[,]+%', $curr_line);
  foreach ($split as $key => $val) {
    switch ($key)
      {
      case 0:  $player_date[$i]  = format_date($val); break;
      case 1:  $player_name[$i]  = $val; break;
      case 2:  $runs_scored[$i]  = $val; break;
      case 3:  $how_out[$i]      = $val; break;
      case 4:  $overs[$i]        = $val; break;
      case 5:  $maidens[$i]      = $val; break;
      case 6:  $runs_concede[$i] = $val; break;
      case 7:  $wickets[$i]      = $val; break;
      case 8:  $catches[$i]      = $val; break;
      case 9:  $stumpings[$i]    = $val; break;
      case 10: $run_outs[$i]     = $val; break;
      case 11: $player_home_team[$i]  = $val; break;
      case 12: $player_match_type[$i] = $val; break;
      }
  }
  $i=$i+1;
}
$player_num_lines = $i;

fclose($file);
// ------------------------------------------------------------------
// ------------------------------------------------------------------
// Extract player list
// ------------------------------------------------------------------
// Remove duplicate entries from the player list array
$unique_player_list = array_unique($player_name);
// ------------------------------------------------------------------
// ------------------------------------------------------------------
// Empty the table first for player list
// ------------------------------------------------------------------
$SQL="TRUNCATE player_list";
$result_sql=mysql_query($SQL);
// ------------------------------------------------------------------
// ------------------------------------------------------------------
// Insert the sorted array into the player list
// ------------------------------------------------------------------

$i=0;
$split = array();
foreach ($unique_player_list as $key => $val) {
  $i=$i+1;
  // extract firt name and last name
  $split = preg_split('%[\s]+%', $val);
  foreach ($split as $new_key => $new_val) {
    switch ($new_key)
      {
      case 0: $first_name = $new_val; break;
      case 1: $last_name = $new_val; break;
      default: echo "ERROR: Player name construction failed -- key==$new_key, val==$val<br />";
      }
  }
  $SQL="INSERT INTO `$database`.`player_list` (`id`, `team_id`, `first_name`, `last_name`) VALUES ($i, 1, '$first_name', '$last_name')";
  $result_sql=mysql_query($SQL);
  if ($debug_msg!=0) {
    echo "id==$i,
          team_id==1,
          first_name==$first_name,
          last_name==$last_name<br />";
  }
}
// ------------------------------------------------------------------
// ------------------------------------------------------------------
// Close session
// ------------------------------------------------------------------
//
mysql_close($db_handle);
//
// ------------------------------------------------------------------

// --CODE ENDS HERE--
?>