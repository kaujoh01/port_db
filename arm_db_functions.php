<?php
// ------------------------------------------------------------------
// PHP script to format date
// ------------------------------------------------------------------
// ------------------------------------------------------------------
// Functions
// ------------------------------------------------------------------
// 1. Format date from 01-jun-12 to 2012-06-01
function format_date($old_date_format) {
  $split_date = array();
  $split_date = preg_split('%[-]+%', $old_date_format);
  foreach ($split_date as $date_key => $date_val) {
    if ($date_key==0) {
      // this is the day
      $new_date_format = "$date_val";
    } else if ($date_key==1) {
      // this is month
      if (preg_match("/jan/i", $date_val)) {
	$new_date_format = "01-$new_date_format";
      } else if (preg_match("/feb/i", $date_val)) {
	$new_date_format = "02-$new_date_format";
      } else if (preg_match("/mar/i", $date_val)) {
	$new_date_format = "03-$new_date_format";
      } else if (preg_match("/apr/i", $date_val)) {
	$new_date_format = "04-$new_date_format";
      } else if (preg_match("/may/i", $date_val)) {
	$new_date_format = "05-$new_date_format";
      } else if (preg_match("/jun/i", $date_val)) {
	$new_date_format = "06-$new_date_format";
      } else if (preg_match("/jul/i", $date_val)) {
	$new_date_format = "07-$new_date_format";
      } else if (preg_match("/aug/i", $date_val)) {
	$new_date_format = "08-$new_date_format";
      } else if (preg_match("/sep/i", $date_val)) {
	$new_date_format = "09-$new_date_format";
      } else if (preg_match("/oct/i", $date_val)) {
	$new_date_format = "10-$new_date_format";
      } else if (preg_match("/nov/i", $date_val)) {
	$new_date_format = "11-$new_date_format";
      } else if (preg_match("/dec/i", $date_val)) {
	$new_date_format = "12-$new_date_format";
      } else {
	echo "ERROR: No date known for $date_val";
      }
    } else if ($date_key==2) {
      // this is year
      if (preg_match("/^9/i", $date_val)) {
	$new_date_format = "19$date_val-$new_date_format";
      } else {
	$new_date_format = "20$date_val-$new_date_format";
      }
    } else {
      echo "ERROR: No array";
    }
  }
  return $new_date_format;
}
// 2. Get out id
function get_out_id($how_out_text) {
  // 1 not out
  // 2 bowled
  // 3 caught
  // 4 lbw
  // 5 dnb
  // 6 run out
  // 7 retired
  // 8 stumped
  // 9 c&b
  // 10 hit wicket
  if(preg_match('/Not Out/',$how_out_text)) {
    return 1;
  } else if(preg_match('/Bowled/',$how_out_text)) {
    return 2;
  } else if(preg_match('/Ct/',$how_out_text)) {
    return 3;
  } else if(preg_match('/LBW/',$how_out_text)) {
    return 4;
  } else if(preg_match('/DNB/',$how_out_text)) {
    return 5;
  } else if(preg_match('/Run Out/',$how_out_text)) {
    return 6;
  } else if(preg_match('/Retd/',$how_out_text)) {
    return 7;
  } else if(preg_match('/Stumped/',$how_out_text)) {
    return 8;
  } else if(preg_match('/C&B/',$how_out_text)) {
    return 9;
  } else if(preg_match('/Hit Wicket/',$how_out_text)) {
    return 10;
  } else {
    return 0;
    echo "FAIL: Could not find how out ID";
  }
  return 0;
}
// 3. Get ground id
function get_ground_id($use_ground_name) {
  $SQL = "SELECT * FROM ground_list";
  $sql_result = mysql_query($SQL);
  while($db_field = mysql_fetch_array($sql_result)) {
    $local_ground_list_name[$db_field["id"]] = $db_field["name"];
  }
  foreach ($local_ground_list_name as $local_id => $local_ground_name) {
    if(preg_match("/$use_ground_name/i",$local_ground_name)) {
      return $local_id;
      echo "ERROR: Why has the code reached here?";
    }
  }
  return 0;
}
// 4. Get match type id
function get_match_type_id($use_match_type_name) {
  if(preg_match('/fr/i',$use_match_type_name)) {
    return 0;
  } else if (preg_match('/l-bz1/i',$use_match_type_name)) {
    return 1;
  } else if (preg_match('/l-bz2/i',$use_match_type_name)) {
    return 2;
  } else {
    return 3;
  }
  return 3;
}
// 5. Get result id
function get_result_id($use_bat_first, $use_result_name) {
  if(preg_match('/won/i',$use_result_name)) {
    if ($use_bat_first==0) {
      return 0;
    } else {
      return 1;
    }
  } else if (preg_match('/lost/i',$use_result_name)) {
    if ($use_bat_first==0) {
      return 1;
    } else {
      return 0;
    }
  } else if (preg_match('/abandoned/i',$use_result_name)) {
    return 2;
  } else if (preg_match('/tie/i',$use_result_name)) {
    return 3;
  } else {
    return 4;
  }
  return 4;
}
// 6. Get team id
function get_team_id($use_team_name) {
  $SQL = "SELECT * FROM team_list";
  $sql_result = mysql_query($SQL);
  while($db_field = mysql_fetch_array($sql_result)) {
    $local_team_list_name[$db_field["id"]] = $db_field["name"];
  }
  // first match ARM name
  if(preg_match('/ARM /',$use_team_name)) {
    return 1;
  } else {
    foreach ($local_team_list_name as $local_id => $local_team_name) {
      if(preg_match("/$use_team_name/",$local_team_name)) {
	return $local_id;
	echo "ERROR: Why has the code reached here?";
      }
    }
  }
  return 0;
}
// ------------------------------------------------------------------
// ------------------------------------------------------------------
// --CODE ENDS HERE--
?>