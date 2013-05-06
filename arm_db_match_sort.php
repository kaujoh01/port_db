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
$file = @fopen('arm_crick_match_list.csv', "r") or exit("Unable to open file!");
$i=0;
while(!feof($file))
{
  //echo fgets($file). "<br>";
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

if ($debug_msg!=0) {
  for ($i=0;$i<$match_num_lines;$i++) {
    echo "$i=$date[$i]::$home_team[$i]::$away_team[$i]::$ground_name[$i]::$match_type[$i]::$bat_first[$i]::$team_score[$i]::$opp_score[$i]::$result[$i]";
    echo "<br />";
  }
}
fclose($file);

// ------------------------------------------------------------------
// Format Data
// ------------------------------------------------------------------

// match_list:-
// -----------
// id --> generated using $i
// date --> $ already available
// type_id --> already available (reverse the string into type)
// ground_id --> ground names available, generate ground list first
// opp_a_id --> opp a name available, generate team list first
// opp_b_id --> opp b name available, generate team list first
// who_won --> already available
// home_away --> available
// num_overs --> to be assumed to be 20?
// overs_type --> to be assumed to be 6?
// description --> leave empty
// is_legacy --> 1
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

//foreach($team_list as $key => $val) {
//  echo "$key => $val <br />";
//}

// Remove duplicate entries from the array
$unique_team_list = array_unique($team_list);

//foreach($unique_team_list as $key => $val) {
//  echo "$key => $val <br />";
//}
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
  $team_name_list[$i] = "$val";
  $result_sql=mysql_query($SQL);
}
// ------------------------------------------------------------------
// ------------------------------------------------------------------
// Extract ground list
// ------------------------------------------------------------------

// Remove duplicate entries from the ground list array
$unique_ground_list = array_unique($ground_name);

foreach($unique_ground_list as $key => $val) {
//  echo "$key => $val <br />";
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
$ground_name_list = array();
foreach ($unique_ground_list as $key => $val) {
  $i=$i+1;
  $SQL="INSERT INTO `$database`.`ground_list` (`id`, `name`) VALUES ($i, '$val')";
  $result_sql=mysql_query($SQL);
  $ground_name_list[$i] = $val;
}
// ------------------------------------------------------------------
// ------------------------------------------------------------------
// Update match statistics
// ------------------------------------------------------------------

// Empty the table first for master match list
$SQL="TRUNCATE match_list";
$result_sql=mysql_query($SQL);

// match_list:-
// -----------
// id --> generated using $i
// date --> $ already available
// type_id --> already available (reverse the string into type)
// ground_id --> ground names available, generate ground list first
// opp_a_id --> opp a name available, generate team list first
// opp_b_id --> opp b name available, generate team list first
// who_won --> already available
// home_away --> available
// num_overs --> to be assumed to be 20?
// overs_type --> to be assumed to be 6?
// description --> leave empty
// is_legacy --> 1
for ($i=0;$i<$match_num_lines;$i++) {
  // -- DATE --
  $put_date = format_date($date[$i]);

  // -- INNINGS1 and INNINGS2 ID --
  if ($bat_first[$i]==0) {
    $innings1_team_name = $home_team[$i];
    $innings2_team_name = $away_team[$i];
  } else {
    $innings1_team_name = $away_team[$i];
    $innings2_team_name = $home_team[$i];
  }

  $inn1_found = 0;
  $inn2_found = 0;
  foreach ($team_name_list as $key => $val) {
    if ($innings1_team_name==$val) {
      $put_innings1_id = $key;
      $inn1_found = 1;
    }
    if ($innings2_team_name==$val) {
      $put_innings2_id = $key;
      $inn2_found = 1;
    }
  }
  if ($inn1_found==0) {
    echo " <br /> ERROR: INN1";
  }
  if ($inn2_found==0) {
    echo " <br /> ERROR: INN2";
  }

  // -- TYPE ID --
  if (preg_match("/fr/i", $match_type[$i])) {
    $put_type_id = 0;
  } else if (preg_match("/l-bz1/i", $match_type[$i])) {
    $put_type_id = 1;
  } else if (preg_match("/l-bz2/i", $match_type[$i])) {
    $put_type_id = 2;
  } else {
    echo "<br /> ERROR: $match_type[$i]::end::";
  }
  // -- GROUND ID --
  foreach ($ground_name_list as $key => $val) {
    if ($val==$ground_name[$i]) {
      $put_ground_id = $key;
      $ground_found = 1;
    }
  }
  if ($ground_found==0) {
    echo " <br /> ERROR: No ground found";
  }

  // -- WHO WON --
  if (preg_match("/won/i", $result[$i])) {
    $put_who_won = ($bat_first[$i]==0) ? 0 : 1;
  } else if (preg_match("/lost/i", $result[$i])) {
    $put_who_won = ($bat_first[$i]==0) ? 1 : 0;
  } else if (preg_match("/tie/i", $result[$i])) {
    $put_who_won = 2;
  } else if (preg_match("/abandoned/i", $result[$i])) {
    $put_who_won = 3;
  } else {
    echo "<br /> ERROR: ::start::$temp_string::end::";
  }

  // -- NUM OVERS --
  $put_num_overs = 20;
  // -- OVERS TYPE --
  $put_overs_type = 6;
  // -- TEXT --
  $put_text = "Legacy";
  // -- IS LEGACY --
  $put_is_legacy = 1;

  //
  // DISPLAY RESULT
  //
  $new_debug = 0;
  if ($new_debug!=0) {
    echo "<br />
          id==$i,
          date==$put_date,
          type_id==$put_type_id,
          ground_id==$put_ground_id,
          opp_a_id==$put_innings1_id,
          opp_b_id==$put_innings2_id,
          who_won==$put_who_won,
          home_away==0,
          num_overs==$put_num_overs,
          overs_type==$put_overs_type,
          description==$put_text,
          is_legacy==$put_is_legacy";
  }

  // Insert the master match list
  $match_id=$i+1;
  $match_list_field="`id`, `date`, `type_id`, `ground_id`, `opp_a_id`, `opp_b_id`, `who_won`, `home_away`, `num_overs`, `overs_type`, `description`, `is_legacy`";
  $match_list_value="$match_id, '$put_date', $put_type_id, $put_ground_id, $put_innings1_id, $put_innings2_id, $put_who_won, 0, 20, 6, '$put_text', 1";
  $SQL="INSERT INTO `$database`.`match_list` ($match_list_field) VALUES ($match_list_value)";
  //echo "$SQL <br />";
  $result_sql=mysql_query($SQL);

  // Update the total scores array
  if ($bat_first[$i]==0) {
    $total_list_opp_a_total[$match_id] = $team_score[$i];
    $total_list_opp_b_total[$match_id] = $opp_score[$i];
  } else {
    $total_list_opp_a_total[$match_id] = $opp_score[$i];
    $total_list_opp_b_total[$match_id] = $team_score[$i];
  }
}
// ------------------------------------------------------------------
// ------------------------------------------------------------------
// Update total list DB
// ------------------------------------------------------------------
foreach ($total_list_opp_a_total as $tot_key => $tot_val) {
  $put_opp_a_total = $tot_val;
  $put_opp_b_total = $total_list_opp_b_total[$tot_key];
  $SQL="INSERT INTO `$database`.`total_list` (`id`, `opp_a_total`, `opp_b_total`) VALUES ($tot_key, $put_opp_a_total, $put_opp_b_total)";
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