<?php
# $Id: edit_entry.php,v 1.30.2.4 2007/02/13 12:53:24 jberanek Exp $

require_once('grab_globals.inc.php');
include "config.inc.php";
include "functions.inc";
include "$dbsys.inc";
include "mrbs_auth.inc";

global $twentyfourhour_format;

if(!getAuthorised(1))
{
	showAccessDenied($day, $month, $year, $area);
	exit;
}


# This page will add a booking

# We need to know:
#  Name of booker
#  Description of meeting
#  Date (option select box for day, month, year)
#  Time
#  Duration
#  Internal/External


# It is a new booking. The data comes from whichever button the user clicked
	$edit_type   = "series";
	$name        = getUserName();
	$create_by   = getUserName();
	$description = "";
	$start_day   = $day;
	$start_month = $month;
	$start_year  = $year;
    
// Avoid notices for $hour and $minute if periods is enabled

	(isset($hour)) ? $start_hour = $hour : '';
	(isset($minute)) ? $start_min = $minute : '';
	$duration    = ($enable_periods ? 60 : 60 * 60);
	$type        = "I";
	$room_id     = $room;
 
unset($id);

	$rep_id        = 0;
	$rep_type      = 0;
	$rep_end_day   = $day;
	$rep_end_month = $month;
	$rep_end_year  = $year;
	$rep_day       = array(0, 0, 0, 0, 0, 0, 0);


#now that we know all the data to fill the form with we start drawing it

if(!getWritable($create_by, getUserName()))
{
	showAccessDenied($day, $month, $year, $area);
	exit;
}

print_header($day, $month, $year, $area);

?>

<SCRIPT LANGUAGE="JavaScript">
// do a little form verifying
function validate_and_submit ()
{
  // null strings and spaces only strings not allowed
  if(/(^$)|(^\s+$)/.test(document.forms["main"].name.value))
  {
    alert ( "<?php echo get_vocab("you_have_not_entered") . '\n' . get_vocab("brief_description") ?>");
    return false;
  }
  <?php if( ! $enable_periods ) { ?>

  h = parseInt(document.forms["main"].hour.value);
  m = parseInt(document.forms["main"].minute.value);

  if(h > 23 || m > 59)
  {
    alert ("<?php echo get_vocab("you_have_not_entered") . '\n' . get_vocab("valid_time_of_day") ?>");
    return false;
  }
  <?php } ?>

  // check form element exist before trying to access it
  if( document.forms["main"].id )
    i1 = parseInt(document.forms["main"].id.value);
  else
    i1 = 0;

  i2 = parseInt(document.forms["main"].rep_id.value);
  if ( document.forms["main"].rep_num_weeks)
  {
  	n = parseInt(document.forms["main"].rep_num_weeks.value);
  }
  if ((!i1 || (i1 && i2)) && document.forms["main"].rep_type && document.forms["main"].rep_type[6].checked && (!n || n < 2))
  {
    alert("<?php echo get_vocab("you_have_not_entered") . '\n' . get_vocab("useful_n-weekly_value") ?>");
    return false;
  }

   // Form submit can take some times, especially if mails are enabled and
  // there are more than one recipient. To avoid users doing weird things
  // like clicking more than one time on submit button, we hide it as soon
  // it is clicked.
  document.forms["main"].save_button.disabled="true";

  // would be nice to also check date to not allow Feb 31, etc...
  document.forms["main"].submit();

  return true;
}
function OnAllDayClick(allday) // Executed when the user clicks on the all_day checkbox.
{
  form = document.forms["main"];
  if (allday.checked) // If checking the box...
  {
    <?php if( ! $enable_periods ) { ?>
      form.hour.value = "00";
      form.minute.value = "00";
    <?php } ?>
    if (form.dur_units.value!="days") // Don't change it if the user already did.
    {
      form.duration.value = "1";
      form.dur_units.value = "days";
    }
  }
}
</SCRIPT>

<h2><?php echo isset($id) ? ($edit_type == "series" ? get_vocab("editseries") : get_vocab("editentry")) : get_vocab("addentry"); ?></H2>
<h3></h3>

<?php
# Capitalise name of booker
$name = ucwords ($name) ;
?>

<FORM NAME="main" ACTION="edit_entry_handler_fixed.php" METHOD="GET">

<TABLE BORDER=0>

<TR>
<?php
# Only allow Admin or Club to change the name of booker
if (($name == "Admin") OR ($name == "Club"))
{ ?>
<TD CLASS=CR><B><?php echo get_vocab("namebooker")?></B></TD>
  <TD CLASS=CL><INPUT NAME="name" SIZE=40 VALUE="<?php echo htmlspecialchars($name,ENT_NOQUOTES) ?>"></TD></TR>
 <?php 
 } else {  ?>
<TD CLASS=CR><B><?php echo get_vocab("namebooker")?></B></TD>
<TD CLASS=CL> <?php echo $name ?> </TD>
<?php 
}
?>
<TR><TD CLASS=TR><B>Details (optional):</B></TD>
  <TD CLASS=CL><INPUT NAME="description" SIZE=40></TD></TR>

<?php
$timestamp = mktime($start_hour, $start_min, 0, $start_month, $start_day, $start_year);
$day_name = date("l jS F Y", $timestamp);
$time_name = date("H:i", $timestamp);
?>
<TR><TD CLASS=CR><B><?php echo get_vocab("date")?></B></TD>
<TD CLASS=CL> <?php echo $day_name ?> </TD></TR>
 
<TR><TD CLASS=CR><B><?php echo get_vocab("time")?></B></TD>
<TD CLASS=CL> <?php echo $time_name ?> </TD></TR>

<TR><TD CLASS=CR><B><?php echo get_vocab("duration");?></B></TD>
<?php
$duration = 45;
?>
<TD CLASS=CL><?php echo "$duration " ?> minutes</TD></TR>
<?php
$dur_units = minutes;
?> 

<?php
# Determine the court being booked for
	$this_room_name = sql_query1("select room_name from $tbl_room where id=$room");
?>
<TR><TD CLASS=CR><B>Court:</B></TD>
<TD CLASS=CL> <?php echo $this_room_name ?> </TD></TR>


<?php
      # Determine the area id of the room in question first
      $area_id = "1";
?>

     


<?php
 # Fix the Type
$type = "I" ;
?>










<?php if($edit_type == "series") { ?>

<?php
}
else
{
	$key = "rep_type_" . (isset($rep_type) ? $rep_type : "0");

	echo "<tr><td class=\"CR\"><b>".get_vocab("rep_type")."</b></td><td class=\"CL\">".get_vocab($key)."</td></tr>\n";

	if(isset($rep_type) && ($rep_type != 0))
	{
		$opt = "";
		if ($rep_type == 2)
		{
			# Display day names according to language and preferred weekday start.
			for ($i = 0; $i < 7; $i++)
			{
				$wday = ($i + $weekstarts) % 7;
				if ($rep_opt[$wday]) $opt .= day_name($wday) . " ";
			}
		}
		if($opt)
			echo "<tr><td class=\"CR\"><b>".get_vocab("rep_rep_day")."</b></td><td class=\"CL\">$opt</td></tr>\n";

		echo "<tr><td class=\"CR\"><b>".get_vocab("rep_end_date")."</b></td><td class=\"CL\">$rep_end_date</td></tr>\n";
	}
}
/* We display the rep_num_weeks box only if:
   - this is a new entry ($id is not set)
   Xor
   - we are editing an existing repeating entry ($rep_type is set and
     $rep_type != 0 and $edit_type == "series" )
*/
if ( ( !isset( $id ) ) Xor ( isset( $rep_type ) && ( $rep_type != 0 ) && ( "series" == $edit_type ) ) )
{
?>


<?php } ?>

<TR><TD><p></p> </TD></TR>
<TR>
 <TD colspan=2 align=center>
  <SCRIPT LANGUAGE="JavaScript">
   document.writeln ( '<INPUT TYPE="button" NAME="save_button" VALUE="<?php echo get_vocab("save")?>" ONCLICK="validate_and_submit()">' );
  </SCRIPT>
  <NOSCRIPT>
   <INPUT TYPE="submit" VALUE="<?php echo get_vocab("save")?>">
  </NOSCRIPT>
 </TD></TR>
<TR>
 <TD colspan=2 align=center>
    <a href="<?php echo "day.php?year=$year&month=$month&day=$day" ?> "><img src="cancel_button.jpg" alt="cancel" width="62" height="20" border="0" /></a></TD>
</TR>
</TABLE>

<?php if (($name !== "Admin") AND ($name !=="Club")) { ?> 
<INPUT TYPE=HIDDEN NAME="name"   VALUE="<?php echo $name ?>">
<?php } ?> 

<INPUT TYPE=HIDDEN NAME="returl"    VALUE="<?php echo $HTTP_REFERER?>">
<INPUT TYPE=HIDDEN NAME="day"   VALUE="<?php echo $start_day?>">
<INPUT TYPE=HIDDEN NAME="month"   VALUE="<?php echo $start_month?>">
<INPUT TYPE=HIDDEN NAME="hour"   VALUE="<?php echo $start_hour?>">
<INPUT TYPE=HIDDEN NAME="minute"   VALUE="<?php echo $start_min?>">
<INPUT TYPE=HIDDEN NAME="year"   VALUE="<?php echo $start_year?>">
<INPUT TYPE=HIDDEN NAME="duration"   VALUE="<?php echo $duration?>">
<INPUT TYPE=HIDDEN NAME="dur_units"   VALUE="<?php echo $dur_units?>">
<INPUT TYPE=HIDDEN NAME="room_id"   VALUE="<?php echo $room_id?>">
<INPUT TYPE=HIDDEN NAME="type"   VALUE="<?php echo $type?>">
<INPUT TYPE=HIDDEN NAME="create_by" VALUE="<?php echo $create_by?>">
<INPUT TYPE=HIDDEN NAME="rep_id"    VALUE="<?php echo $rep_id?>">
<INPUT TYPE=HIDDEN NAME="edit_type" VALUE="<?php echo $edit_type?>">
<?php if(isset($id)) echo "<INPUT TYPE=HIDDEN NAME=\"id\"        VALUE=\"$id\">\n";
?>

</FORM>

<?php include "trailer.inc" ?>