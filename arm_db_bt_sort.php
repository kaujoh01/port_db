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
$total_list_inn1_num_runs = array();
$total_list_inn2_num_runs = array();
$opp_bt_match_id = array();
$opp_bt_fl_id = array();
$opp_bt_out_id = array();
$opp_bt_inn_type = array();
$opp_bt_order = array();
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
  $match_list_inn1_id[$match_id] = $db_field["inn1_team_id"];
  $match_list_inn2_id[$match_id] = $db_field["inn2_team_id"];
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
    $bt_bat_order[$i] = 0;
  } else if ( ($match_list_inn1_id[$bt_match_id[$i]]==1) &&
	      ($match_list_inn2_id[$bt_match_id[$i]]==1)) {
    // This is an ARM vs ARM game
    if ($bt_order[$i]>11) {
      $bt_inn_type[$i] = 2;
      $bt_bat_order[$i] = $bt_order[$i] - 11;
    } else {
      $bt_inn_type[$i] = 1;
      $bt_bat_order[$i] = $bt_order[$i];
    }
  } else {
    $bt_bat_order[$i] = $bt_order[$i];
    // This is a normal game so find out if ARM was innings1
    // or innings2
    if ($match_list_inn1_id[$bt_match_id[$i]]==1) {
      $bt_inn_type[$i] = 1;
    } else if ($match_list_inn2_id[$bt_match_id[$i]]==1) {
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
  //
  // Update fielding statistics by plugging into opposition
  //
  if ($bt_match_id[$i]!=0) {
    // Update catches
    if ($bt_catches[$i]!=0) {
      for ($j=0; $j<$bt_catches[$i]; $j++) {
	array_push($opp_bt_match_id, $bt_match_id[$i]);
	array_push($opp_bt_fl_id, $bt_id[$i]);
	array_push($opp_bt_out_id, 3);
	if ($bt_inn_type[$i]==1) {
	  array_push($opp_bt_inn_type, 2);
	} else {
	  array_push($opp_bt_inn_type, 1);
	}
      }
    }
    // Update stumpings
    if ($bt_stumpings[$i]!=0) {
      for ($j=0; $j<$bt_stumpings[$i]; $j++) {
	array_push($opp_bt_match_id, $bt_match_id[$i]);
	array_push($opp_bt_fl_id, $bt_id[$i]);
	array_push($opp_bt_out_id, 8);
	if ($bt_inn_type[$i]==1) {
	  array_push($opp_bt_inn_type, 2);
	} else {
	  array_push($opp_bt_inn_type, 1);
	}
      }
    }
    // Update run outs
    if ($bt_run_outs[$i]!=0) {
      for ($j=0; $j<$bt_run_outs[$i]; $j++) {
	array_push($opp_bt_match_id, $bt_match_id[$i]);
	array_push($opp_bt_fl_id, $bt_id[$i]);
	array_push($opp_bt_out_id, 6);
	if ($bt_inn_type[$i]==1) {
	  array_push($opp_bt_inn_type, 2);
	} else {
	  array_push($opp_bt_inn_type, 1);
	}
      }
    }
  } else {
    echo "ERROR: Match ID 0 for fielding stat use <br />";
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
$SQL="TRUNCATE inn1_bt_list";
//$result_sql=mysql_query($SQL);
$SQL="TRUNCATE inn2_bt_list";
//$result_sql=mysql_query($SQL);

for ($i=0;$i<$player_num_lines;$i++) {
  $put_match_id = $bt_match_id[$i];
  $put_order = $bt_bat_order[$i];
  $put_bt_id = $bt_id[$i];
  $put_num_runs = $bt_runs_scored[$i];
  $put_num_balls = 0;
  $put_num_4s = 0;
  $put_num_6s = 0;
  $put_out_id = $bt_how_out_id[$i];
  $put_bl_id = 0;
  $put_fl_id = 0;
  $bt_list_field="`match_id`, `order`, `bt_id`, `num_runs`, `num_balls`, `num_4s`, `num_6s`, `out_id`, `bl_id`, `fl_id`";
  $bt_list_value="$put_match_id, $put_order, $put_bt_id, $put_num_runs, $put_num_balls, $put_num_4s, $put_num_6s, $put_out_id, $put_bl_id, $put_fl_id";

  // SANITY CHECK
  if ($put_order>11) {
    echo "WARNING: Batting order greater than 11 for match_id==$put_match_id <br />";
  }
  //
  // Update the DB
  //
  if ($bt_inn_type[$i]==1) {
    $SQL="INSERT INTO `$database`.`inn1_bt_list` ($bt_list_field) VALUES ($bt_list_value)";
    //$result_sql=mysql_query($SQL);
    //echo "$SQL<br />";
  } else if ($bt_inn_type[$i]==2) {
    $SQL="INSERT INTO `$database`.`inn2_bt_list` ($bt_list_field) VALUES ($bt_list_value)";
    //$result_sql=mysql_query($SQL);
    //echo "$SQL<br />";
  } else {
    echo "ERROR: Couldn't enter data into batting list <br />";
  }
  //
  // Update total list
  //
  if ($bt_inn_type[$i]==1) {
    if ($put_order==1) {
      $total_list_inn1_num_runs[$put_match_id] = $put_num_runs;
      $total_list_inn2_num_runs[$put_match_id] = 0;
    } else {
      $total_list_inn1_num_runs[$put_match_id] = $total_list_inn1_num_runs[$put_match_id] + $put_num_runs;
      $total_list_inn2_num_runs[$put_match_id] = 0;
    }
  } else if ($bt_inn_type[$i]==2) {
    if ($put_order==1) {
      $total_list_inn2_num_runs[$put_match_id] = $put_num_runs;
      $total_list_inn1_num_runs[$put_match_id] = 0;
    } else {
      $total_list_inn2_num_runs[$put_match_id] = $total_list_inn2_num_runs[$put_match_id] + $put_num_runs;
      $total_list_inn1_num_runs[$put_match_id] = 0;
    }
  } else {
    echo "ERROR: Couldn't calculate num_runs <br />";
  }
}
// ------------------------------------------------------------------
// ------------------------------------------------------------------
// Update innings 1 or innings 2 bowling
// ------------------------------------------------------------------
$SQL="TRUNCATE inn1_bl_list";
$result_sql=mysql_query($SQL);
$SQL="TRUNCATE inn2_bl_list";
$result_sql=mysql_query($SQL);

$bl_order = 0;
for ($i=0;$i<$player_num_lines;$i++) {
  $put_match_id = $bt_match_id[$i];
  $put_bl_id = $bt_id[$i];
  $put_num_overs = $bt_overs[$i];
  $put_num_maidens = $bt_maidens[$i];
  $put_num_runs = $bt_runs_concede[$i];
  $put_num_wickets = $bt_wickets[$i];
  $put_num_nb = 0;
  $put_num_wd = 0;

  // Derive the order
  if ($i==0) {
    $bl_order = 0;
  } else if ( ($bt_date[$i]!=$bt_date[$i-1]) ||
              ($bt_inn_type[$i]!=$bt_inn_type[$i-1])) {
    $bl_order = 0;
  }
  if ($put_num_overs!=0) {
    $bl_order = $bl_order + 1;
  }
  $put_order = $bl_order;

  // SANITY CHECK
  if ($put_order>11) {
    echo "WARNING: Bowling order greater than 11 for match_id==$put_match_id <br />";
  }

  $bl_list_field="`match_id`, `bl_id`, `order`, `num_overs`, `num_maidens`, `num_runs`, `num_wickets`, `num_nb`, `num_wd`";
  $bl_list_value="$put_match_id, $put_bl_id, $put_order, $put_num_overs, $put_num_maidens, $put_num_runs, $put_num_wickets, $put_num_nb, $put_num_wd";
  //
  // Update the DB
  //
  if ($put_num_overs!=0) {
    if ($bt_inn_type[$i]==1) {
      $SQL="INSERT INTO `$database`.`inn2_bl_list` ($bl_list_field) VALUES ($bl_list_value)";
      $result_sql=mysql_query($SQL);
      //echo "$SQL<br />";
    } else if ($bt_inn_type[$i]==2) {
      $SQL="INSERT INTO `$database`.`inn1_bl_list` ($bl_list_field) VALUES ($bl_list_value)";
      $result_sql=mysql_query($SQL);
      //echo "$SQL<br />";
    } else {
      echo "ERROR: Couldn't enter data into bowling list <br />";
    }
  }
}
// ------------------------------------------------------------------
// ------------------------------------------------------------------
// Update total list
// ------------------------------------------------------------------
// Read table
$SQL = "SELECT * FROM total_list" ;
$db_result = mysql_query($SQL);
$i=0;
while($db_field = mysql_fetch_array($db_result))
{
  $match_id = $db_field["id"];
  $total_list_inn1_score[$match_id] = $db_field["inn1_score"];
  $total_list_inn2_score[$match_id] = $db_field["inn2_score"];
}

// Truncate table
$SQL="TRUNCATE total_list";
//$result_sql=mysql_query($SQL);

// Write to table
foreach ($total_list_inn1_score as $match_id => $val) {
  // if the match id doesn't exist then skip
  if (array_key_exists($match_id, $total_list_inn1_num_runs)) {
    $put_inn1_score    = $total_list_inn1_score[$match_id];
    $put_inn2_score    = $total_list_inn2_score[$match_id];
    $put_inn1_num_runs = $total_list_inn1_num_runs[$match_id];
    $put_inn2_num_runs = $total_list_inn2_num_runs[$match_id];
    $total_list_field="`id`, `inn1_score`, `inn2_score`, `inn1_num_runs`, `inn2_num_runs`";
    $total_list_value="$match_id, $put_inn1_score, $put_inn2_score, $put_inn1_num_runs, $put_inn2_num_runs";
    $SQL="INSERT INTO `$database`.`total_list` ($total_list_field) VALUES ($total_list_value)";
    //$result_sql=mysql_query($SQL);
    //echo "$SQL<br />";
  }
}
// ------------------------------------------------------------------
// ------------------------------------------------------------------
// Update fielding list
// ------------------------------------------------------------------
// update order
$fl_order = 1;
for ($i=0; $i<sizeof($opp_bt_match_id); $i++) {
  if ($i==0) {
    $fl_order = 1;
  } else if ($opp_bt_match_id[$i]!=$opp_bt_match_id[$i-1]) {
    $fl_order = 1;
  } else {
    $fl_order = $fl_order + 1;
  }
  $opp_bt_order[$i] = $fl_order;
}
foreach ($opp_bt_match_id as $key => $val) {
  $bt_list_field="`match_id`, `order`, `bt_id`, `num_runs`, `num_balls`, `num_4s`, `num_6s`, `out_id`, `bl_id`, `fl_id`";
  $bt_list_value="$val, $opp_bt_order[$key], 0, 0, 0, 0, 0, $opp_bt_out_id[$key], 0, $opp_bt_fl_id[$key]";
  if ($opp_bt_inn_type[$key]==1) {
    $SQL="INSERT INTO `$database`.`inn1_bt_list` ($bt_list_field) VALUES ($bt_list_value)";
  } else {
    $SQL="INSERT INTO `$database`.`inn2_bt_list` ($bt_list_field) VALUES ($bt_list_value)";
  }
  //$result_sql=mysql_query($SQL);
  //echo "$SQL<br />";

  if ($debug_msg!=0) {
    echo "match_id==$val,
          fl_id==$opp_bt_fl_id[$key],
          out_id==$opp_bt_out_id[$key],
          inn_type==$opp_bt_inn_type[$key]<br />";
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