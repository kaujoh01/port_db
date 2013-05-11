<?php
// ------------------------------------------------------------------
// PHP to read the ARM excel sheet and dump the ground list
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
$file = @fopen('arm_crick_match_list.csv', "r") or exit("Unable to open file!");
$i=0;
while(!feof($file))
{
  $curr_line = fgets($file);
  $split = preg_split('%[,]+%', $curr_line);
  foreach ($split as $key => $val) {
    switch ($key)
      {
      case 0: $date[$i]        = $val; break;
      case 1: $home_team[$i]   = $val; break;
      case 2: $away_team[$i]   = $val; break;
      case 3: $ground_name[$i] = $val; break;
      case 4: $match_type[$i]  = $val; break;
      case 5: $bat_first[$i]   = $val; break;
      case 6: $team_score[$i]  = $val; break;
      case 7: $opp_score[$i]   = $val; break;
      case 8: $result[$i]      = $val; break;
      }
  }
  $i=$i+1;
}
$match_num_lines = $i;

fclose($file);
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
// Extract ground list
// ------------------------------------------------------------------

// Remove duplicate entries from the ground list array
$unique_ground_list = array_unique($ground_name);

if ($debug_msg!=0) {
  foreach($unique_ground_list as $key => $val) {
    echo "$key => $val <br />";
  }
}
// ------------------------------------------------------------------
// ------------------------------------------------------------------
// Empty the table first
// ------------------------------------------------------------------

$SQL="TRUNCATE ground_list";
$result_sql=mysql_query($SQL);

// ------------------------------------------------------------------
// Insert the sorted array into the ground list
// ------------------------------------------------------------------

// Update ground list array too
$i=0;
foreach ($unique_ground_list as $key => $val) {
  $i=$i+1;
  $SQL="INSERT INTO `$database`.`ground_list` (`id`, `name`) VALUES ($i, '$val')";
  $result_sql=mysql_query($SQL);
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