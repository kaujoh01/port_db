<?php
// ------------------------------------------------------------------
// PHP to read the ARM excel sheet and create match list
// ------------------------------------------------------------------
// ------------------------------------------------------------------
// Debug
// ------------------------------------------------------------------

// Debug enable/disable
$debug_msg = 0;

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
  // result type
  $result_id[$i] = get_result_id($bat_first, $result_name);
  // inn1 team id
  $pass_inn1_team_name = $inn1_team_name[$i];
  $inn1_team_id[$i] = get_team_id($pass_inn1_team_name);
  // inn2 team id
  $pass_inn2_team_name = $inn2_team_name[$i];
  $inn2_team_id[$i] = get_team_id($pass_inn2_team_name);
  //
  // VALIDATION
  //
  // check match type id
  if ($match_type_id[$i]==3) {
    echo "ERROR: Could not find match type ID i==$i<br />";
  }
  // check result id
  if ($result_id[$i]==4) {
    echo "ERROR: Could not find Result ID i==$i<br />";
  }
  // Check team ID
  if ($inn1_team_id[$i]==0) {
    echo "ERROR: Could not find Innings1 team ID for name $pass_inn1_team_name and i==$i<br />";
  }
  if ($inn2_team_id[$i]==0) {
    echo "ERROR: Could not find Innings2 team ID for name $pass_inn2_team_name and i==$i<br />";
  }
  // update i to next
  $i=$i+1;
}
$match_num_lines = $i;

if ($debug_msg!=0) {
  for ($i=0;$i<$match_num_lines;$i++) {
    $echo_ground_name = $ground_list_name[$ground_id[$i]];
    echo "id==$i,
          date==$date[$i],
          inn1_name==$inn1_team_name[$i],
          inn2_name==$inn2_team_name[$i],
          gnd_name==$echo_ground_name,
          match_type==$match_type_id[$i],
          inn1_score==$inn1_team_score[$i],
          inn2_score==$inn2_team_score[$i],
          result_id==$result_id[$i]<br />";
  }
}
fclose($file);
// ------------------------------------------------------------------
// ------------------------------------------------------------------
// Update match statistics
// ------------------------------------------------------------------
// Empty the table first for master match list
$SQL="TRUNCATE match_list";
$result_sql=mysql_query($SQL);

for ($i=0;$i<$match_num_lines;$i++) {
  // -- DATE --
  $put_date = $date[$i];
  // -- TYPE ID --
  $put_type_id = $match_type_id[$i];
  // -- GROUND ID --
  $put_ground_id = $ground_id[$i];
  // -- OPP A ID --> INN1 ID --
  $put_opp_a_id = $inn1_team_id[$i];
  // -- OPP B ID --> INN2 ID --
  $put_opp_b_id = $inn2_team_id[$i];
  // -- WHO WON --> RESULTS ID --
  $put_who_won = $result_id[$i];
  // -- HOME OR AWAY --
  $put_home_away = 0;
  // -- NUM OVERS --
  $put_num_overs = 20;
  // -- OVERS TYPE --
  $put_overs_type = 6;
  // -- DESCRIPTION --
  $put_description = "Legacy";
  // -- IS LEGACY--
  $put_is_legacy = 1;

  //
  // DISPLAY RESULT
  //
  if ($debug_msg!=0) {
    echo "<br />
          id==$i,
          date==$put_date,
          type_id==$put_type_id,
          ground_id==$put_ground_id,
          opp_a_id==$put_opp_a_id,
          opp_b_id==$put_opp_b_id,
          who_won==$put_who_won,
          home_away==$put_home_away,
          num_overs==$put_num_overs,
          overs_type==$put_overs_type,
          description==$put_description,
          is_legacy==$put_is_legacy";
  }

  // Insert the master match list
  $match_id=$i+1;
  $match_list_field="`id`, `date`, `type_id`, `ground_id`, `opp_a_id`, `opp_b_id`, `who_won`, `home_away`, `num_overs`, `overs_type`, `description`, `is_legacy`";
  $match_list_value="$match_id, '$put_date', $put_type_id, $put_ground_id, $put_opp_a_id, $put_opp_b_id, $put_who_won, $put_home_away, $put_num_overs, $put_overs_type, '$put_description', $put_is_legacy";
  $SQL="INSERT INTO `$database`.`match_list` ($match_list_field) VALUES ($match_list_value)";
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