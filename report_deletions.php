<?php

require_once "grab_globals.inc.php";
include "config.inc.php";
include "$dbsys.inc";
include "functions.inc";
include "version.inc";

print_header($day, $month, $year, $area);

// The basic SELECT statement
$select = 'SELECT * ';
$from   = ' FROM mrbs_entry_deleted';
$where  = ' WHERE 1=1';
$order  = ' ORDER BY timestamp_new DESC';

?>
<h1>Deleted Bookings  </h1>
<p>Delete all: <a href="delete_all_deletions.php"><img src="delete_button.jpg" alt="delete" width="50" height="22" border="0" /></a></p>
<p>* The delete buttons PERMANENTLY remove bookings from the list of deleted bookings</p>
<table border="1"><tr>
  <td><strong>Name</strong></td>
    <td><strong>Created By</strong></td>
<td><div align="center"><strong>Date</strong></div></td>
<td><strong>Start Time</strong></td>
<td><strong>End Time</strong></td>
<td><strong>Court</strong></td>
<td><strong>Date & Time Booked</strong></td>
<td><strong>Date & Time Cancelled</strong></td>
<td><strong>Deleted By</strong></td>
<td><strong>Hours before slot</strong></td>
<td><strong>Delete*</strong></td></tr>
    
    <?php
$result = @mysql_query($select . $from . $where . $order);
if (!$result) {
  echo '</table>';
  exit('<p>Error retrieving data from database!<br />'.
      'Error: ' . mysql_error() . '</p>');
}

while ($row = mysql_fetch_array($result)) {

  echo "<tr valign='top'>\n";
  $id = $row['id'];
  $date = date ('d M Y', $row['start_time']);
  $start_time = date ('H:i', $row['start_time']);
  $end_time = date ('H:i', $row['end_time']);  
  $room_id = $row['room_id'];  
  $timestamp_old = date ('d M Y H:i', $row['timestamp_old']);  
  $timestamp_new = date ('d M Y H:i', $row['timestamp_new']);  
  $name = htmlspecialchars($row['name']);
  $create_by = htmlspecialchars($row['create_by']);  
    $delete_by = htmlspecialchars($row['delete_by']);  
  $notice = round(($row['start_time'] - $row['timestamp_new'])/3600);

 echo "<td>$name</td>\n";
 echo "<td>$create_by</td>\n"; 
 echo "<td>$date</td>\n";
 echo "<td>$start_time</td>\n";
 echo "<td>$end_time</td>\n";
 echo "<td>$room_id</td>\n";
 echo "<td>$timestamp_old</td>\n";
 echo "<td>$timestamp_new</td>\n";
  echo "<td>$delete_by</td>\n"; 
if ($notice < 0)
{ echo "<td bgcolor=\"#00FFFF\"><strong>$notice</strong></td>\n";}
elseif ($notice < 48)
{ echo "<td bgcolor=\"#FF0000\"><strong>$notice</strong></td>\n";}
else
{ echo "<td>$notice</td>\n";}
?>
 <TD><a href="<?php echo "delete_deletions.php?id=$id" ?> "><img src="delete_button.jpg" alt="delete" width="50" height="22" border="0" /></a></TD>

</tr>
<?php } ?>
</table>

</div>

<?php include "trailer.inc"; ?>