<?php
// ------------------------------------------------------------------
// PHP to read the ARM excel sheet and dump into team list
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
// Extract team list
// ------------------------------------------------------------------

// Remove additional ARM names
for ($i=0;$i<$match_num_lines;$i++) {
  if(preg_match('/ARM /',$home_team[$i])) {
    $home_team[$i] = "ARM";
  }
  if(preg_match('/ARM /',$away_team[$i])) {
    $away_team[$i] = "ARM";
  }
}

for ($i=0;$i<$match_num_lines;$i++) {
  for ($j=0;$j<2;$j++) {
    if ($j==0) {
      $team_list[$i*2+$j] = $home_team[$i];
    } else {
      $team_list[$i*2+$j] = $away_team[$i];
    }
  }
}

// Remove duplicate entries from the array
$unique_team_list = array_unique($team_list);

if ($debug_msg!=0) {
  foreach($unique_team_list as $key => $val) {
    echo "$key => $val <br />";
  }
}
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
// Empty the table first
// ------------------------------------------------------------------

$SQL="TRUNCATE team_list";
$result_sql=mysql_query($SQL);

// ------------------------------------------------------------------
// Insert the sorted array into the team list
// ------------------------------------------------------------------

$i=0;
foreach ($unique_team_list as $key => $val) {
  $i=$i+1;
  $SQL="INSERT INTO `$database`.`team_list` (`id`, `name`) VALUES ($i, '$val')";
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