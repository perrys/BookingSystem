<?php

require_once "grab_globals.inc.php";
include "config.inc.php";
include "$dbsys.inc";
include "functions.inc";
include "version.inc";

print_header($day, $month, $year, $area);

# Delete all bookings from the deleted bookings table
$query = mysql_query("DELETE FROM mrbs_entry_deleted");
if ($query) {
  echo '<p>Deletion successful</p>';
} else {
  echo '<p>Error deleting entry from database!<br />'.
      'Error: ' . mysql_error() . '</p>';
}

?>
<p><a href="report_deletions.php">Return to deletions report</a></p>



<?php include "trailer.inc"; ?>