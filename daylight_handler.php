<?php

require_once "grab_globals.inc.php";
include "config.inc.php";
include "functions.inc";
include "$dbsys.inc";
include "mrbs_auth.inc";
include "mrbs_sql.inc";

print_header($day, $month, $year, $area);

$change_date = mktime(1,0,0,$month,$day,$year);
$change_date_returned = date("d M Y", $change_date);

echo "<h1>Bookings to be adjusted:</h1>";
echo "Date of time change:   $change_date_returned <a href='daylight_handler_action.php?day=$day&month=$month&year=$year&duration=$duration&dur_units=$dur_units'><button>Submit the Correction</button></a> ";  

	$sql = "
	SELECT id,
		   name,
		   " . sql_syntax_timestamp_to_unix("timestamp") . ",		   	       
		   room_id,
	       start_time
	FROM  mrbs_entry
	WHERE start_time > $change_date
	ORDER BY start_time
	";

$result = mysql_query($sql);
?> 
<table>
    <tr>
    <td width="100"><h3>Date</h3></td>
    <td width="100"><h3>Time</h3></td>
    <td width="100"><h3>Corrected Time</h3></td>    
    <td width="100"><h3>Court</h3></td>
    <td width="200"><h3>Name</h3></td>
    <td width="200"><h3>Date Booked</h3></td>    
    </tr>
    
<?php

while($row = mysql_fetch_array($result))
  {
  $id = $row['id'];
  $start_time = $row['start_time'];
  $start_date = date("d M Y", $start_time); 
  $timestamp = $row[2]; 
  $booked = date("d M Y", $timestamp); 
  if ($dur_units == "Forward") $corrected_start_time = $start_time-3600*$duration;
  if ($dur_units == "Back") $corrected_start_time = $start_time+3600*$duration;    
  $room_id = $row['room_id'];
  $name = $row['name']; 
 	$start_hour  = strftime('%H', $start_time);
	$start_min   = strftime('%M', $start_time);
 	$corrected_start_hour  = strftime('%H', $corrected_start_time);
	$corrected_start_min   = strftime('%M', $corrected_start_time);	
	
	if ($timestamp < $change_date)
	{
	echo "<td>$start_date</td>\n";	
	echo "<td>$start_hour:$start_min</td>\n";		
	echo "<td>$corrected_start_hour:$corrected_start_min</td>\n";		
	echo "<td>$room_id</td>\n";	
	echo "<td>$name</td>\n";
	echo "<td>$booked</td></tr>\n";	
	}
  } //end of while loop
  	
echo "</table>";

include "trailer.inc"; ?>