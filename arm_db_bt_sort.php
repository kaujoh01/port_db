<?php
// ------------------------------------------------------------------
// PHP to read the ARM excel sheet and dump innings 1
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
// how out list
// ------------------------------------------------------------------
$SQL = "SELECT * FROM how_out_list" ;
$db_result = mysql_query($SQL);
while($db_field = mysql_fetch_array($db_result))
{
  $how_out_list_name[$db_field["id"]] = $db_field["name"];
}
// ------------------------------------------------------------------
// ------------------------------------------------------------------
// total list
// ------------------------------------------------------------------
$SQL = "SELECT * FROM total_list" ;
$db_result = mysql_query($SQL);
while($db_field = mysql_fetch_array($db_result))
{
  $match_id = $db_field["id"];
  $total_list_inn1_score[$match_id] = $db_field["inn1_score"];
  $total_list_inn2_score[$match_id] = $db_field["inn2_score"];
}
// ------------------------------------------------------------------
// ------------------------------------------------------------------
// player list
// ------------------------------------------------------------------
$SQL = "SELECT * FROM player_list" ;
$db_result = mysql_query($SQL);
while($db_field = mysql_fetch_array($db_result))
{
  $first_name = $db_field["first_name"];
  $last_name  = $db_field["last_name"];
  $player_list_name[$db_field["id"]] = "$first_name $last_name";
}
// ------------------------------------------------------------------
// ------------------------------------------------------------------
// Read player list
// ------------------------------------------------------------------
$file = @fopen('arm_crick_player_list.csv', "r") or exit("Unable to open file!");
$i=0;
$split = array();
$order = 0;
while(!feof($file))
{
  $curr_line = fgets($file);
  $split = preg_split('%[,]+%', $curr_line);
  // Extract info for the line
  foreach ($split as $key => $val) {
    switch ($key)
      {
      case 0:  $bt_date[$i]         = format_date($val); break;
      case 1:  $bt_name             = $val; break;
      case 2:  $bt_runs_scored[$i]  = $val; break;
      case 3:  $bt_how_out_id[$i]   = get_out_id($val); break;
      case 4:  $bt_overs[$i]        = $val; break;
      case 5:  $bt_maidens[$i]      = $val; break;
      case 6:  $bt_runs_concede[$i] = $val; break;
      case 7:  $bt_wickets[$i]      = $val; break;
      case 8:  $bt_catches[$i]      = $val; break;
      case 9:  $bt_stumpings[$i]    = $val; break;
      case 10: $bt_run_outs[$i]     = $val; break;
      case 11: $bt_home_team[$i]    = $val; break;
      case 12: $bt_match_type[$i]   = $val; break;
      }
  }
  // Derive the order
  if ($i==0) {
    $order = 1;
  } else if ($bt_date[$i]!=$bt_date[$i-1]) {
    $order = 1;
  } else {
    $order = $order +1;
  }
  $bt_order[$i] = $order;
  // Get batsman ID
  $bt_id[$i] = get_player_id($bt_name);
  // Get the match ID
  $found_date = 0;
  foreach ($match_list_date as $match_id => $match_date) {
    if ($match_date==$bt_date[$i]) {
      $bt_match_id[$i] = $match_id;
      $found_date = 1;
    }
  }
  // check date found
  if ($found_date==0) {
    echo "ERROR: Match ID could not be deduced for date $bt_date[$i]<br />";
    // temporarily assign a 0 match id
    $bt_match_id[$i] = 0;
  }
  // Generate if the current player is innings 1 or innings 2
  if ($bt_match_id[$i]==0) {
    $bt_inn_type[$i] = 3;
  } else if ( ($match_list_opp_a_id[$bt_match_id[$i]]==1) &&
	      ($match_list_opp_b_id[$bt_match_id[$i]]==1)) {
    // This is an ARM vs ARM game
    if ($bt_order[$i]>11) {
      $bt_inn_type[$i] = 2;
    } else {
      $bt_inn_type[$i] = 1;
    }
  } else {
    // This is a normal game so find out if ARM was innings1
    // or innings2
    if ($match_list_opp_a_id[$bt_match_id[$i]]==1) {
      $bt_inn_type[$i] = 1;
    } else if ($match_list_opp_b_id[$bt_match_id[$i]]==1) {
      $bt_inn_type[$i] = 2;
    } else {
      $bt_inn_type[$i] = 4;
    }
  }
  // Error checking for bt inn type
  if ($bt_inn_type[$i]==3) {
    echo "ERROR: Innings (code - 3) could not be derived for date $bt_date[$i]<br/>";
  }
  if ($bt_inn_type[$i]==4) {
    echo "ERROR: Innings (code - 4) could not be derived for date $bt_date[$i]<br/>";
  }
  // Update i to next
  $i=$i+1;
}
$player_num_lines = $i;

if ($debug_msg!=0) {
  for ($i=0;$i<$player_num_lines;$i++) {
    $bt_name = $player_list_name[$bt_id[$i]];
    $how_out_name = $how_out_list_name[$bt_how_out_id[$i]];
    $order = $bt_order[$i];
    echo "id==$i,
          order==$order,
          match_id==$bt_match_id[$i],
          innings==$bt_inn_type[$i],
          date==$bt_date[$i],
          name==$bt_name,
          runs==$bt_runs_scored[$i],
          how_out==$how_out_name,
          overs==$bt_overs[$i],
          maidens==$bt_maidens[$i],
          runs_concede==$bt_runs_concede[$i],
          wickets==$bt_wickets[$i],
          catches==$bt_catches[$i],
          stumpings==$bt_stumpings[$i],
          run_outs==$bt_run_outs[$i]<br />";
  }
}
fclose($file);
// ------------------------------------------------------------------
// ------------------------------------------------------------------
// Update innings 1 or innings 2 batting list
// ------------------------------------------------------------------
$SQL="TRUNCATE opp_a_bt_list";
$result_sql=mysql_query($SQL);
$SQL="TRUNCATE opp_b_bt_list";
$result_sql=mysql_query($SQL);

for ($i=0;$i<$player_num_lines;$i++) {
  $put_match_id = $bt_match_id[$i];
  $put_order = $bt_order[$i];
  $put_bt_id = $bt_id[$i];
  $put_num_runs = $bt_runs_scored[$i];
  $put_num_balls = 0;
  $put_num_4s = 0;
  $put_num_6s = 0;
  $put_out_id = $bt_how_out_id[$i];
  $put_bl_id = 0;
  $put_c_ro_id = 0;
  $bt_list_field="`match_id`, `order`, `bt_id`, `num_runs`, `num_balls`, `num_4s`, `num_6s`, `out_id`, `bl_id`, `c_ro_id`";
  $bt_list_value="$put_match_id, $put_order, $put_bt_id, $put_num_runs, $put_num_balls, $put_num_4s, $put_num_6s, $put_out_id, $put_bl_id, $put_c_ro_id";
  //
  // Update the DB
  //
  if ($bt_inn_type[$i]==1) {
    $SQL="INSERT INTO `$database`.`opp_a_bt_list` ($bt_list_field) VALUES ($bt_list_value)";
    $result_sql=mysql_query($SQL);
    //echo "$SQL<br />";
  } else if ($bt_inn_type[$i]==2) {
    $SQL="INSERT INTO `$database`.`opp_b_bt_list` ($bt_list_field) VALUES ($bt_list_value)";
    $result_sql=mysql_query($SQL);
    //echo "$SQL<br />";
  } else {
    echo "ERROR: Couldn't enter data into batting list <br />";
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