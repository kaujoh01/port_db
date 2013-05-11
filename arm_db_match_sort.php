<?php
// ------------------------------------------------------------------
// PHP to read the ARM excel sheet and create match list
// ------------------------------------------------------------------
// ------------------------------------------------------------------
// Debug
// ------------------------------------------------------------------

// Debug enable/disable
$debug_msg = 1;

$split = array();
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
// Functions
// ------------------------------------------------------------------
include 'arm_db_functions.php';
// ------------------------------------------------------------------
// ------------------------------------------------------------------
// Read ground list
// ------------------------------------------------------------------
$SQL = "SELECT * FROM ground_list";
$sql_result = mysql_query($SQL);
while($db_field = mysql_fetch_array($sql_result)) {
  $ground_list_name[$db_field["id"]] = $db_field["name"];
}
// ------------------------------------------------------------------
// ------------------------------------------------------------------
// Read team list
// ------------------------------------------------------------------
$SQL = "SELECT * FROM team_list";
$sql_result = mysql_query($SQL);
while($db_field = mysql_fetch_array($sql_result)) {
  $team_list_name[$db_field["id"]] = $db_field["name"];
}
// ------------------------------------------------------------------
// ------------------------------------------------------------------
// Read match list excel sheet
// ------------------------------------------------------------------
$file = @fopen('arm_crick_match_list.csv', "r") or exit("Unable to open file!");
$i=0;
while(!feof($file))
{
  $curr_line = fgets($file);
  $split = preg_split('%[,]+%', $curr_line);
  foreach ($split as $key => $val) {
    switch ($key)
      {
      case 0: $date[$i]          = format_date($val); break;
      case 1: $home_team_name    = $val; break;
      case 2: $away_team_name    = $val; break;
      case 3: $ground_id[$i]     = get_ground_id($val); break;
      case 4: $match_type_id[$i] = get_match_type_id($val); break;
      case 5: $bat_first         = $val; break;
      case 6: $home_team_score   = $val; break;
      case 7: $away_team_score   = $val; break;
      case 8: $result_name       = $val; break;
      }
  }
  if ($bat_first==0) {
    $inn1_team_name[$i]  = $home_team_name;
    $inn2_team_name[$i]  = $away_team_name;
    $inn1_team_score[$i] = $home_team_score;
    $inn2_team_score[$i] = $away_team_score;
  } else {
    $inn1_team_name[$i]  = $away_team_name;
    $inn2_team_name[$i]  = $home_team_name;
    $inn1_team_score[$i] = $away_team_score;
    $inn2_team_score[$i] = $home_team_score;
  }
  $result_id[$i] = get_result_id($bat_first, $result_name);
  $i=$i+1;
}
$match_num_lines = $i;

if ($debug_msg!=0) {
  for ($i=0;$i<$match_num_lines;$i++) {
    $echo_ground_name = $ground_list_name[$ground_id[$i]];
    echo "$i=$date[$i]::
          $inn1_team_name[$i]::
          $inn2_team_name[$i]::
          $echo_ground_name::
          $match_type_id[$i]::
          $inn1_team_score[$i]::
          $inn2_team_score[$i]::
          $result_id[$i]<br />";
  }
}
fclose($file);
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