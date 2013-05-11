<?php
// ------------------------------------------------------------------
// PHP to read the ARM excel sheet and dump into a MySQL database
// ------------------------------------------------------------------
// STEPS:-
// -------
// 1. Read the match list sheet line by line into an array
// 2. Apply regexp to extract multiple information into another array

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
// match list
// ------------------------------------------------------------------
$SQL = "SELECT * FROM match_list" ;
$db_result = mysql_query($SQL);
$i=0;
while($db_field = mysql_fetch_array($db_result))
{
  $match_id = $db_field["id"];
  $match_list_date[$match_id] = $db_field["date"];
  $match_list_opp_a_id[$match_id] = $db_field["opp_a_id"];
  $match_list_opp_b_id[$match_id] = $db_field["opp_b_id"];
  $i=$i+1;
}
$match_num_lines = $i;
// ------------------------------------------------------------------
// ------------------------------------------------------------------
// total list
// ------------------------------------------------------------------
$SQL = "SELECT * FROM total_list" ;
$db_result = mysql_query($SQL);
while($db_field = mysql_fetch_array($db_result))
{
  $match_id = $db_field["id"];
  $total_list_opp_a_total[$match_id] = $db_field["opp_a_total"];
  $total_list_opp_b_total[$match_id] = $db_field["opp_b_total"];
}
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

if ($debug_msg!=0) {
  for ($i=0;$i<$player_num_lines;$i++) {
    echo "<br />
          id==$i,
          date==$player_date[$i],
          name==$player_name[$i],
          runs==$runs_scored[$i],
          how_out==$how_out[$i],
          overs==$overs[$i],
          maidens==$maidens[$i],
          runs_concede==$runs_concede[$i],
          wickets==$wickets[$i],
          catches==$catches[$i],
          stumpings==$stumpings[$i],
          run_outs==$run_outs[$i],
          player_home_team==$player_home_team[$i],
          player_match_type==$player_match_type[$i]";
  }
}
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
      default: echo "ERROR-->key==$new_key, val==$new_val<br />";
      }
  }
  $SQL="INSERT INTO `$database`.`player_list` (`id`, `team_id`, `first_name`, `last_name`) VALUES ($i, 1, '$first_name', '$last_name')";
  $result_sql=mysql_query($SQL);
  // keep a player name list
  $player_name_list[$i] = "$first_name $last_name";
}
// ------------------------------------------------------------------
// ------------------------------------------------------------------
// Add player ID to the orignial player list
// ------------------------------------------------------------------
for ($i=0;$i<$player_num_lines;$i++) {
  $player_name_found = 0;
  foreach ($player_name_list as $arr_player_id => $arr_player_name) {
    if ($arr_player_name==$player_name[$i]) {
      $player_id[$i] = $arr_player_id;
      $player_name_found = 1;
    }
  }
  if ($player_name_found==0) {
    echo "<br />ERROR: Player name not found--> $player_name[$i]";
  }
}
// ------------------------------------------------------------------
// ------------------------------------------------------------------
// Update innings 1 or innings 2 batting list
// ------------------------------------------------------------------
$opp_a_total_score = array();
$opp_b_total_score = array();
$bat_debug = 0;
//$SQL="TRUNCATE opp_a_bt_list";
//$result_sql=mysql_query($SQL);
//$SQL="TRUNCATE opp_b_bt_list";
//$result_sql=mysql_query($SQL);

foreach ($match_list_date as $match_id => $match_date) {
  // initialize
  $order = 0;
  $opp_a_total_score[$match_id] = 0;
  $opp_b_total_score[$match_id] = 0;

  for ($i=0;$i<$player_num_lines;$i++) {
    // match date first
    if ($match_date==$player_date[$i]) {
      // if this match is ARM vs ARM then handle it slightly differently
      if ( ($match_list_opp_a_id[$match_id]==1) &&
	   ($match_list_opp_b_id[$match_id]==1)) {
	$order = $order + 1;
	if ($order < 12) {
	  $put_match_id = $match_id;
	  $put_order = $order;
	  $put_bt_id = $player_id[$i];
	  $put_num_runs = $runs_scored[$i];
	  $put_out_id = get_out_id($how_out[$i]);
	  $SQL_opp_a_field = "`match_id`, `order`, `bt_id`, `num_runs`, `num_balls`, `num_4s`, `num_6s`, `out_id`, `bl_id`, `c_ro_id`";
	  $SQL_opp_a_value = "$put_match_id, $put_order, $put_bt_id, $put_num_runs, 0, 0, 0, $put_out_id, 0, 0";
	  $SQL="INSERT INTO `$database`.`opp_a_bt_list` ($SQL_opp_a_field) VALUES ($SQL_opp_a_value)";
	  if ($bat_debug!=0) {echo "$SQL<br />";}
	  //$result_sql=mysql_query($SQL);
	  $opp_a_total_score[$match_id] = $opp_a_total_score[$match_id] + $put_num_runs;
	} else {
	  $put_match_id = $match_id;
	  $put_order = $order-11;
	  $put_bt_id = $player_id[$i];
	  $put_num_runs = $runs_scored[$i];
	  $put_out_id = get_out_id($how_out[$i]);
	  $SQL_opp_b_field = "`match_id`, `order`, `bt_id`, `num_runs`, `num_balls`, `num_4s`, `num_6s`, `out_id`, `bl_id`, `c_ro_id`";
	  $SQL_opp_b_value = "$put_match_id, $put_order, $put_bt_id, $put_num_runs, 0, 0, 0, $put_out_id, 0, 0";
	  $SQL="INSERT INTO `$database`.`opp_b_bt_list` ($SQL_opp_b_field) VALUES ($SQL_opp_b_value)";
	  if ($bat_debug!=0) {echo "$SQL<br />";}
	  //$result_sql=mysql_query($SQL);
	  $opp_b_total_score[$match_id] = $opp_b_total_score[$match_id] + $put_num_runs;
	}
      } else if ($match_list_opp_a_id[$match_id]==1) {
	$order = $order + 1;
	$put_match_id = $match_id;
	$put_order = $order;
	$put_bt_id = $player_id[$i];
	$put_num_runs = $runs_scored[$i];
	$put_out_id = get_out_id($how_out[$i]);
	$SQL_opp_a_field = "`match_id`, `order`, `bt_id`, `num_runs`, `num_balls`, `num_4s`, `num_6s`, `out_id`, `bl_id`, `c_ro_id`";
	$SQL_opp_a_value = "$put_match_id, $put_order, $put_bt_id, $put_num_runs, 0, 0, 0, $put_out_id, 0, 0";
	$SQL="INSERT INTO `$database`.`opp_a_bt_list` ($SQL_opp_a_field) VALUES ($SQL_opp_a_value)";
	if ($bat_debug!=0) {echo "$SQL<br />";}
	//$result_sql=mysql_query($SQL);
	$opp_a_total_score[$match_id] = $opp_a_total_score[$match_id] + $put_num_runs;
      } else if ($match_list_opp_b_id[$match_id]==1) {
	$order = $order + 1;
	$put_match_id = $match_id;
	$put_order = $order;
	$put_bt_id = $player_id[$i];
	$put_num_runs = $runs_scored[$i];
	$put_out_id = get_out_id($how_out[$i]);
	$SQL_opp_b_field = "`match_id`, `order`, `bt_id`, `num_runs`, `num_balls`, `num_4s`, `num_6s`, `out_id`, `bl_id`, `c_ro_id`";
	$SQL_opp_b_value = "$put_match_id, $put_order, $put_bt_id, $put_num_runs, 0, 0, 0, $put_out_id, 0, 0";
	$SQL="INSERT INTO `$database`.`opp_b_bt_list` ($SQL_opp_b_field) VALUES ($SQL_opp_b_value)";
	if ($bat_debug!=0) {echo "$SQL<br />";}
	//$result_sql=mysql_query($SQL);
	$opp_b_total_score[$match_id] = $opp_b_total_score[$match_id] + $put_num_runs;
      } else {
	echo "ERROR: No match found";
      }
    }
  }
}
// ------------------------------------------------------------------
// ------------------------------------------------------------------
// Update innings 1 and 2 extras (to match up the total)
// ------------------------------------------------------------------
$SQL="TRUNCATE opp_a_xt_list";
$result_sql=mysql_query($SQL);
$SQL="TRUNCATE opp_b_xt_list";
$result_sql=mysql_query($SQL);

foreach ($match_list_date as $match_id => $match_date) {
  // If innings 1 is ARM then check for total
  if ($match_list_opp_a_id[$match_id]==1) {
    if ($total_list_opp_a_total[$match_id] >= $opp_a_total_score[$match_id]) {
      $inn1_extras = $total_list_opp_a_total[$match_id] - $opp_a_total_score[$match_id];
      $SQL="INSERT INTO `$database`.`opp_a_xt_list` (`match_id`, `num_lb`, `num_b`) VALUES ($match_id, $inn1_extras, 0)";
      //echo "$SQL<br />";
      $result_sql=mysql_query($SQL);
    } else {
      //echo "FAIL: opp_a --> match_id==$match_id($match_date), total==$total_list_opp_a_total[$match_id], bat_total==$opp_a_total_score[$match_id]<br />";
      $inn1_extras = 0;
      $SQL="INSERT INTO `$database`.`opp_a_xt_list` (`match_id`, `num_lb`, `num_b`) VALUES ($match_id, $inn1_extras, 0)";
      //echo "$SQL<br />";
      $result_sql=mysql_query($SQL);
    }
  }
  // If innings 2 is ARM then check for total
  if ($match_list_opp_b_id[$match_id]==1) {
    if ($total_list_opp_b_total[$match_id] >= $opp_b_total_score[$match_id]) {
      $inn2_extras = $total_list_opp_b_total[$match_id] - $opp_b_total_score[$match_id];
      $SQL="INSERT INTO `$database`.`opp_b_xt_list` (`match_id`, `num_lb`, `num_b`) VALUES ($match_id, $inn2_extras, 0)";
      //echo "$SQL<br />";
      $result_sql=mysql_query($SQL);
    } else {
      //echo "FAIL: opp_b --> match_id==$match_id($match_date), total==$total_list_opp_b_total[$match_id], bat_total==$opp_b_total_score[$match_id]<br />";
      $inn2_extras = 0;
      $SQL="INSERT INTO `$database`.`opp_b_xt_list` (`match_id`, `num_lb`, `num_b`) VALUES ($match_id, $inn2_extras, 0)";
      //echo "$SQL<br />";
      $result_sql=mysql_query($SQL);
    }
  }
}
// ------------------------------------------------------------------
// ------------------------------------------------------------------
// Update innings 1 or innings2 bowling list
// ------------------------------------------------------------------
//$SQL="TRUNCATE opp_a_bl_list";
//$result_sql=mysql_query($SQL);
//$SQL="TRUNCATE opp_b_bl_list";
//$result_sql=mysql_query($SQL);

foreach ($match_list_date as $match_id => $match_date) {
  // initialize
  $order = 0;
  $opp_a_total_score[$match_id] = 0;
  $opp_b_total_score[$match_id] = 0;

  for ($i=0;$i<$player_num_lines;$i++) {
    // match date first
    if ($match_date==$player_date[$i]) {
      // if this match is ARM vs ARM then handle it slightly differently
      if ( ($match_list_opp_a_id[$match_id]==1) &&
	   ($match_list_opp_b_id[$match_id]==1)) {
	$order = $order + 1;
	if ($order < 12) {
	  $put_match_id = $match_id;
	  $put_order = $order;
	  $put_bt_id = $player_id[$i];
	  $put_num_runs = $runs_scored[$i];
	  $put_out_id = get_out_id($how_out[$i]);
	  $SQL_opp_a_field = "`match_id`, `order`, `bt_id`, `num_runs`, `num_balls`, `num_4s`, `num_6s`, `out_id`, `bl_id`, `c_ro_id`";
	  $SQL_opp_a_value = "$put_match_id, $put_order, $put_bt_id, $put_num_runs, 0, 0, 0, $put_out_id, 0, 0";
	  $SQL="INSERT INTO `$database`.`opp_a_bt_list` ($SQL_opp_a_field) VALUES ($SQL_opp_a_value)";
	  if ($bat_debug!=0) {echo "$SQL<br />";}
	  //$result_sql=mysql_query($SQL);
	  $opp_a_total_score[$match_id] = $opp_a_total_score[$match_id] + $put_num_runs;
	} else {
	  $put_match_id = $match_id;
	  $put_order = $order-11;
	  $put_bt_id = $player_id[$i];
	  $put_num_runs = $runs_scored[$i];
	  $put_out_id = get_out_id($how_out[$i]);
	  $SQL_opp_b_field = "`match_id`, `order`, `bt_id`, `num_runs`, `num_balls`, `num_4s`, `num_6s`, `out_id`, `bl_id`, `c_ro_id`";
	  $SQL_opp_b_value = "$put_match_id, $put_order, $put_bt_id, $put_num_runs, 0, 0, 0, $put_out_id, 0, 0";
	  $SQL="INSERT INTO `$database`.`opp_b_bt_list` ($SQL_opp_b_field) VALUES ($SQL_opp_b_value)";
	  if ($bat_debug!=0) {echo "$SQL<br />";}
	  //$result_sql=mysql_query($SQL);
	  $opp_b_total_score[$match_id] = $opp_b_total_score[$match_id] + $put_num_runs;
	}
      } else if ($match_list_opp_a_id[$match_id]==1) {
	$order = $order + 1;
	$put_match_id = $match_id;
	$put_order = $order;
	$put_bt_id = $player_id[$i];
	$put_num_runs = $runs_scored[$i];
	$put_out_id = get_out_id($how_out[$i]);
	$SQL_opp_a_field = "`match_id`, `order`, `bt_id`, `num_runs`, `num_balls`, `num_4s`, `num_6s`, `out_id`, `bl_id`, `c_ro_id`";
	$SQL_opp_a_value = "$put_match_id, $put_order, $put_bt_id, $put_num_runs, 0, 0, 0, $put_out_id, 0, 0";
	$SQL="INSERT INTO `$database`.`opp_a_bt_list` ($SQL_opp_a_field) VALUES ($SQL_opp_a_value)";
	if ($bat_debug!=0) {echo "$SQL<br />";}
	//$result_sql=mysql_query($SQL);
	$opp_a_total_score[$match_id] = $opp_a_total_score[$match_id] + $put_num_runs;
      } else if ($match_list_opp_b_id[$match_id]==1) {
	$order = $order + 1;
	$put_match_id = $match_id;
	$put_order = $order;
	$put_bt_id = $player_id[$i];
	$put_num_runs = $runs_scored[$i];
	$put_out_id = get_out_id($how_out[$i]);
	$SQL_opp_b_field = "`match_id`, `order`, `bt_id`, `num_runs`, `num_balls`, `num_4s`, `num_6s`, `out_id`, `bl_id`, `c_ro_id`";
	$SQL_opp_b_value = "$put_match_id, $put_order, $put_bt_id, $put_num_runs, 0, 0, 0, $put_out_id, 0, 0";
	$SQL="INSERT INTO `$database`.`opp_b_bt_list` ($SQL_opp_b_field) VALUES ($SQL_opp_b_value)";
	if ($bat_debug!=0) {echo "$SQL<br />";}
	//$result_sql=mysql_query($SQL);
	$opp_b_total_score[$match_id] = $opp_b_total_score[$match_id] + $put_num_runs;
      } else {
	echo "ERROR: No match found";
      }
    }
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