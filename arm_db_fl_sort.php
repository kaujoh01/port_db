<?php
// ------------------------------------------------------------------
// PHP to read the ARM excel sheet and dump innings 1
// ------------------------------------------------------------------
// ------------------------------------------------------------------
// Debug
// ------------------------------------------------------------------

// Debug enable/disable
$debug_msg = 0;

$inn1_max_order = array();
$inn2_max_order = array();

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

$db_handle = mysql_connect($server, $user_name, $password);
$db_found = mysql_select_db($database, $db_handle);
// ------------------------------------------------------------------
// Objectives here is that for each match I need to know the
// maximum order for innings 1 and innings 2
// ------------------------------------------------------------------
// Initialize the max order arrays based on match id
// ------------------------------------------------------------------
$SQL = "SELECT * FROM match_list" ;
$db_result = mysql_query($SQL);
while($db_field = mysql_fetch_array($db_result))
{
  $match_id = $db_field["id"];
  $inn1_max_order[$match_id] = 0;
  $inn2_max_order[$match_id] = 0;
}
// ------------------------------------------------------------------
// ------------------------------------------------------------------
// Max order for innings 1
// ------------------------------------------------------------------
$SQL = "SELECT * FROM inn1_bt_list" ;
$db_result = mysql_query($SQL);
$i=0;
while($db_field = mysql_fetch_array($db_result))
{
  $match_id = $db_field["match_id"];
  $order    = $db_field["order"];
  $inn1_bt_list_match_id[$i] = $match_id;
  $inn1_bt_list_order[$i]    = $order;
  $i = $i + 1;
  if ($order > $inn1_max_order[$match_id]) {
    $inn1_max_order[$match_id] = $order;
  }
}
$num_inn1_bt_list = $i;
// ------------------------------------------------------------------
// ------------------------------------------------------------------
// Max order for innings 2
// ------------------------------------------------------------------
$SQL = "SELECT * FROM inn2_bt_list" ;
$db_result = mysql_query($SQL);
$i=0;
while($db_field = mysql_fetch_array($db_result))
{
  $match_id = $db_field["match_id"];
  $order    = $db_field["order"];
  $inn2_bt_list_match_id[$i] = $match_id;
  $inn2_bt_list_order[$i]    = $order;
  $i = $i + 1;

  if ($order > $inn2_max_order[$match_id]) {
    $inn2_max_order[$match_id] = $order;
  }
}
$num_inn2_bt_list = $i;
// ------------------------------------------------------------------
// ------------------------------------------------------------------
// Display
// ------------------------------------------------------------------
foreach ($inn1_max_order as $key => $val) {
  if (0) {
    echo "match_id==$key,
          inn1_order==$val,
          inn2_order==$inn2_max_order[$key] <br />";
  }
}
// ------------------------------------------------------------------
// ------------------------------------------------------------------
// Read the fl list
// ------------------------------------------------------------------
$SQL = "SELECT * FROM fl_list" ;
$db_result = mysql_query($SQL);
$i=0;
while($db_field = mysql_fetch_array($db_result))
{
  $fl_list_match_id[$i] = $db_field["match_id"];
  $fl_list_inn_type[$i] = $db_field["inn_type"];
  $fl_list_out_id[$i]   = $db_field["out_id"];
  $fl_list_fl_id[$i]    = $db_field["fl_id"];
  $i = $i + 1;
}
$num_lines = $i;
// ------------------------------------------------------------------
// ------------------------------------------------------------------
// Write fl
// ------------------------------------------------------------------
for ($i=0; $i < $num_lines; $i++) {
  // match id
  $match_id = $fl_list_match_id[$i];
  // Check if this is a new order
  $is_new_order = 0;
  if ($i==0) {
    $is_new_order = 1;
  } else if ($fl_list_match_id[$i]!=$fl_list_match_id[$i-1]) {
    $is_new_order = 1;
  } else if ($fl_list_inn_type[$i]!=$fl_list_inn_type[$i-1]) {
    $is_new_order = 1;
  }
  // If this is a new order then reset $order
  if ($is_new_order==1) {
    $order = 1;
  } else {
    $order = $order + 1;
  }
  if ($fl_list_inn_type[$i]==1) {
    // inn 1
    $put_order = $inn1_max_order[$match_id] + $order;
  } else if ($fl_list_inn_type[$i]==2) {
    // inn 1
    $put_order = $inn2_max_order[$match_id] + $order;
  } else {
    echo "ERROR::: <br />";
  }
  $fl_list_order[$i] = $put_order;
  if (0) {
    echo "i==$i,
          match_id==$match_id,
          inn_type==$fl_list_inn_type[$i],
          out_id==$fl_list_out_id[$i],
          fl_id==$fl_list_fl_id[$i],
          order==$fl_list_order[$i] <br />";
  }
}
// ------------------------------------------------------------------
// ------------------------------------------------------------------
// Error check
// ------------------------------------------------------------------
for ($i=0; $i < $num_lines; $i++) {
  $test_match_id = $fl_list_match_id[$i];
  $test_order    = $fl_list_order[$i];

  if ($fl_list_inn_type[$i]==1) {
    for ($j=0; $j < $num_inn1_bt_list; $j++) {
      if ($test_match_id==$inn1_bt_list_match_id[$j]) {
	if ($test_order==$inn1_bt_list_order[$j]) {
	  echo "FATAL ERROR INN 1 <br />";
	}
      }
    }
  } else {
    for ($j=0; $j < $num_inn2_bt_list; $j++) {
      if ($test_match_id==$inn2_bt_list_match_id[$j]) {
	if ($test_order==$inn2_bt_list_order[$j]) {
	  echo "FATAL ERROR INN 2 <br />";
	}
      }
    }
  }
}
// ------------------------------------------------------------------
// ------------------------------------------------------------------
// Final FL write
// ------------------------------------------------------------------
for ($i=0; $i < $num_lines; $i++) {
  $put_match_id = $fl_list_match_id[$i];
  $put_order = $fl_list_order[$i];
  $put_out_id = $fl_list_out_id[$i];
  $put_fl_id = $fl_list_fl_id[$i];

  $bt_list_field="`match_id`, `order`, `bt_id`, `num_runs`, `num_balls`, `num_4s`, `num_6s`, `out_id`, `bl_id`, `fl_id`";
  $bt_list_value="$put_match_id, $put_order, 0, 0, 0, 0, 0, $put_out_id, 0, $put_fl_id";

  if ($fl_list_inn_type[$i]==1) {
    $SQL="INSERT INTO `$database`.`inn1_bt_list` ($bt_list_field) VALUES ($bt_list_value)";
    $result_sql=mysql_query($SQL);
    echo "$SQL<br />";
  } else if ($fl_list_inn_type[$i]==2) {
    $SQL="INSERT INTO `$database`.`inn2_bt_list` ($bt_list_field) VALUES ($bt_list_value)";
    $result_sql=mysql_query($SQL);
    echo "$SQL<br />";
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