<?php

require_once "grab_globals.inc.php";
include "config.inc.php";
include "functions.inc";
include "$dbsys.inc";
include "mrbs_auth.inc";
include "mrbs_sql.inc";

print_header($day, $month, $year, $area);

$change_date = mktime(1,0,0,$month,$day,$year);

	$sql = "
	SELECT id,
		   " . sql_syntax_timestamp_to_unix("timestamp") . ",		   	       
	       start_time,
		   end_time
	FROM  mrbs_entry
	WHERE start_time > $change_date
	ORDER BY start_time
	";

$result = mysql_query($sql);

while($row = mysql_fetch_array($result))
  {
  $id = $row['id'];
  $start_time = $row['start_time'];
  $end_time = $row['end_time'];  
  $timestamp = $row[1]; 

  if ($dur_units == "Forward") {$corrected_start_time = $start_time-3600*$duration;
  								 $corrected_end_time = $end_time-3600*$duration ;}
  if ($dur_units == "Back")	   {$corrected_start_time = $start_time+3600*$duration;    
  								 $corrected_end_time = $end_time+3600*$duration ;}	
	if ($timestamp < $change_date)
	{
 	  $sql_2 = "UPDATE mrbs_entry SET
       		   start_time='$corrected_start_time',
        	   end_time='$corrected_end_time'
      		   WHERE id='$id'";

	  $result_2 = mysql_query($sql_2);


	}
  } //end of while loop

echo "<h1>Corrections Made</h1>";

include "trailer.inc"; ?>