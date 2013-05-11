<?php
// ------------------------------------------------------------------
// PHP to write to extras list
// ------------------------------------------------------------------
// ------------------------------------------------------------------
// Debug
// ------------------------------------------------------------------

// Debug enable/disable
$debug_msg = 0;
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
// total list
// ------------------------------------------------------------------
$SQL = "SELECT * FROM total_list" ;
$db_result = mysql_query($SQL);
$i=0;
while($db_field = mysql_fetch_array($db_result))
{
  $match_id = $db_field["id"];
  $total_list_inn1_score[$match_id]    = $db_field["inn1_score"];
  $total_list_inn2_score[$match_id]    = $db_field["inn2_score"];
  $total_list_inn1_num_runs[$match_id] = $db_field["inn1_num_runs"];
  $total_list_inn2_num_runs[$match_id] = $db_field["inn2_num_runs"];
  $i=$i+1;
}
$num_matches = $i;
// ------------------------------------------------------------------
// ------------------------------------------------------------------
// Update extras list
// ------------------------------------------------------------------
$SQL="TRUNCATE opp_a_xt_list";
$result_sql=mysql_query($SQL);
$SQL="TRUNCATE opp_b_xt_list";
$result_sql=mysql_query($SQL);

foreach ($total_list_inn1_score as $match_id => $val) {
  //
  // Update innings 1
  //
  if ($total_list_inn1_score[$match_id]>=$total_list_inn1_num_runs[$match_id]) {
    $diff = $total_list_inn1_score[$match_id] - $total_list_inn1_num_runs[$match_id];
    $SQL="INSERT INTO `$database`.`opp_a_xt_list` (`match_id`, `num_lb`, `num_b`) VALUES ($match_id, 0, $diff)";
    $result_sql=mysql_query($SQL);
    //echo "$SQL<br />";
  } else {
    echo "ERROR: Total score less than num score for innings 1 in match_id==$match_id";
  }
  //
  // Update innings 2
  //
  if ($total_list_inn2_score[$match_id]>=$total_list_inn2_num_runs[$match_id]) {
    $diff = $total_list_inn2_score[$match_id] - $total_list_inn2_num_runs[$match_id];
    $SQL="INSERT INTO `$database`.`opp_b_xt_list` (`match_id`, `num_lb`, `num_b`) VALUES ($match_id, 0, $diff)";
    $result_sql=mysql_query($SQL);
    //echo "$SQL<br />";
  } else {
    echo "ERROR: Total score less than num score for innings 2 in match_id==$match_id";
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