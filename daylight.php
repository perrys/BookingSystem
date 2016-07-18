<?php

require_once('grab_globals.inc.php');
include "config.inc.php";
include "functions.inc";
include "$dbsys.inc";
include "mrbs_auth.inc";

global $twentyfourhour_format;

	$day   = date("d");
	$month = date("m");
	$year  = date("Y");

if(!getAuthorised(1))
{
	showAccessDenied($day, $month, $year, $area);
	exit;
}

if(!getWritable($create_by, getUserName()))
{
	showAccessDenied($day, $month, $year, $area);
	exit;
}

print_header($day, $month, $year, $area);

?>

<h2>Daylight Saving Time Change Adjustment</h2>
<p>This court booking sytem was written so that it was unaffected by daylight saving time changes. However, due to a change in the functions supported by PHP, this is no longer the case.
Bookings that straddle the change will shift by an hour when the clocks change. </p>
<p>Run the adjustment below as soon as possible after the time change.</p>
<p>1. Check any new bookings that have been made since the time change. If they are in the gaps that the old bookings will move into, there will be a clash.</p>
<p>2. Enter the details of the time change. The date will normally be a Sunday:</p>
<FORM NAME="main" ACTION="daylight_handler.php" METHOD="GET">

<TABLE BORDER=0>

<TR><TD CLASS=CR><B>Date:</B></TD>
 <TD CLASS=CL>  <?php genDateSelector("", $day, $month, $year) ?> </TD></TR>
 
<TR><TD CLASS=CR><B>Time Change:</B></TD>
  <TD CLASS=CL><INPUT NAME="duration" SIZE=4 VALUE="1"> Hour
  
<SELECT NAME="dur_units">
<option value="Forward">Forward</option>
<option value="Back">Back</option>
</SELECT>

Select forward in Spring and back in Autumn</TD></TR>

<TR> </TR>

<TR> <TD>
3. Press submit 

 </TD>
 <TD>
 
   <INPUT TYPE="submit" VALUE="SUBMIT">
 </TD></TR>

</TABLE>


</FORM>

<?php include "trailer.inc" ?>