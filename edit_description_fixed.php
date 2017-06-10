<?php
# $Id: edit_entry.php,v 1.30.2.4 2007/02/13 12:53:24 jberanek Exp $

require_once('grab_globals.inc.php');
include "config.inc.php";
include "functions.inc";
include "$dbsys.inc";
include "mrbs_auth.inc";

global $twentyfourhour_format;

#If we dont know the right date then make it up
if(!isset($day) or !isset($month) or !isset($year))
{
	$day   = date("d");
	$month = date("m");
	$year  = date("Y");
}
if(empty($area))
	$area = get_default_area();
if(!isset($edit_type))
	$edit_type = "";

if(!getAuthorised(1))
{
	showAccessDenied($day, $month, $year, $area);
	exit;
}

# This page will either add or modify a booking

# We need to know:
#  Name of booker
#  Description of meeting
#  Date (option select box for day, month, year)
#  Time
#  Duration
#  Internal/External

# Firstly we need to know if this is a new booking or modifying an old one
# and if it's a modification we need to get all the old data from the db.
# If we had $id passed in then it's a modification.
if (isset($id))
{
	$sql = "select name, create_by, description, start_time, end_time,
	        type, room_id, entry_type, repeat_id from $tbl_entry where id=$id";
	
	$res = sql_query($sql);
	if (! $res) fatal_error(1, sql_error());
	if (sql_count($res) != 1) fatal_error(1, get_vocab("entryid") . $id . get_vocab("not_found"));
	
	$row = sql_row($res, 0);
	sql_free($res);
# Note: Removed stripslashes() calls from name and description. Previous
# versions of MRBS mistakenly had the backslash-escapes in the actual database
# records because of an extra addslashes going on. Fix your database and
# leave this code alone, please.
	$name        = $row[0];
	$create_by   = $row[1];
	$description = $row[2];
	$start_day   = strftime('%d', $row[3]);
	$start_month = strftime('%m', $row[3]);
	$start_year  = strftime('%Y', $row[3]);
	$start_hour  = strftime('%H', $row[3]);
	$start_min   = strftime('%M', $row[3]);
	$duration    = $row[4] - $row[3] - cross_dst($row[3], $row[4]);
	$type        = $row[5];
	$room_id     = $row[6];
	$entry_type  = $row[7];
	$rep_id      = $row[8];
	
	if($entry_type >= 1)
	{
		$sql = "SELECT rep_type, start_time, end_date, rep_opt, rep_num_weeks
		        FROM $tbl_repeat WHERE id=$rep_id";
		
		$res = sql_query($sql);
		if (! $res) fatal_error(1, sql_error());
		if (sql_count($res) != 1) fatal_error(1, get_vocab("repeat_id") . $rep_id . get_vocab("not_found"));
		
		$row = sql_row($res, 0);
		sql_free($res);
		
		$rep_type = $row[0];

		if($edit_type == "series")
		{
			$start_day   = (int)strftime('%d', $row[1]);
			$start_month = (int)strftime('%m', $row[1]);
			$start_year  = (int)strftime('%Y', $row[1]);
			
			$rep_end_day   = (int)strftime('%d', $row[2]);
			$rep_end_month = (int)strftime('%m', $row[2]);
			$rep_end_year  = (int)strftime('%Y', $row[2]);
			
			switch($rep_type)
			{
				case 2:
				case 6:
					$rep_day[0] = $row[3][0] != "0";
					$rep_day[1] = $row[3][1] != "0";
					$rep_day[2] = $row[3][2] != "0";
					$rep_day[3] = $row[3][3] != "0";
					$rep_day[4] = $row[3][4] != "0";
					$rep_day[5] = $row[3][5] != "0";
					$rep_day[6] = $row[3][6] != "0";

					if ($rep_type == 6)
					{
						$rep_num_weeks = $row[4];
					}
					
					break;
				
				default:
					$rep_day = array(0, 0, 0, 0, 0, 0, 0);
			}
		}
		else
		{
			$rep_type     = $row[0];
			$rep_end_date = utf8_strftime('%A %d %B %Y',$row[2]);
			$rep_opt      = $row[3];
		}
	}
}
else
{
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
}

# These next 4 if statements handle the situation where
# this page has been accessed directly and no arguments have
# been passed to it.
# If we have not been provided with a room_id
if( empty( $room_id ) )
{
	$sql = "select id from $tbl_room limit 1";
	$res = sql_query($sql);
	$row = sql_row($res, 0);
	$room_id = $row[0];

}

# If we have not been provided with starting time
if( empty( $start_hour ) && $morningstarts < 10 )
	$start_hour = "0$morningstarts";

if( empty( $start_hour ) )
	$start_hour = "$morningstarts";

if( empty( $start_min ) )
	$start_min = "00";

// Remove "Undefined variable" notice
if (!isset($rep_num_weeks))
{
    $rep_num_weeks = "";
}

$enable_periods ? toPeriodString($start_min, $duration, $dur_units) : toTimeString($duration, $dur_units);

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
    alert ( "<?php echo get_vocab("you_have_not_entered") .  get_vocab("brief_description") ?>");
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

  // check that a room(s) has been selected
  // this is needed as edit_entry_handler does not check that a room(s)
  // has been chosen
  if( document.forms["main"].elements['rooms[]'].selectedIndex == -1 )
  {
    alert("<?php echo get_vocab("you_have_not_selected") . '\n' . get_vocab("valid_room") ?>");
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
<h3><?php if ($max_booking > 0) 
{echo "Courts may be booked for a maximum of $max_booking minutes."; }
else {echo "";} ?> </h3>

<?php 
# Capitalise name of booker
$name = ucwords ($name) ;
 ?>

<FORM NAME="main" ACTION="edit_description_handler_fixed.php" METHOD="GET">

<TABLE BORDER=0>

<TR>
<TD CLASS=CR><B><?php echo get_vocab("namebooker")?></B></TD>
<TD CLASS=CL>
<?php
if ((authGetUserLevel(getUserName(), $auth["admin"]) >= 2) || (authGetUserLevel(getUserName(), $auth["coach"]) >= 2) || ($name == "Club")) {
  echo "<INPUT NAME='name'   VALUE='$name'>";
} else {
  echo "<INPUT TYPE='HIDDEN' NAME='name'   VALUE='$name'>";
  echo $name;
}
?> </TD>
</TR>
<TR><TD CLASS=TR><B>Description:</B></TD>
  <TD
 CLASS=TL><TEXTAREA 
NAME="description" 
ROWS=1 COLS=40 WRAP="virtual"><?php echo
htmlspecialchars ( $description ); ?></TEXTAREA>
</TD>
</TR>

<?php
$timestamp = mktime($start_hour, $start_min, 0, $start_month, $start_day, $start_year);
$day_name = date("l jS F Y", $timestamp);
$time_name = date("H:i", $timestamp);
?>
<TR><TD CLASS=CR><B><?php echo get_vocab("date")?></B></TD>
<TD CLASS=CL>
 <?php echo $day_name ?> 
</TD>
</TR>

<TR><TD CLASS=CR><B><?php echo get_vocab("time")?></B></TD>
<TD CLASS=CL>
<?php
echo $time_name ?>
<?php
?>
</TD></TR>

<TR><TD CLASS=CR><B><?php echo get_vocab("duration");?></B></TD>
<TD CLASS=CL>  <?php echo "$duration $dur_units" ; ?> </TD>
</TR>

<TR><TD CLASS=CR><B>Location:</B></TD>
<TD CLASS=CL>  Court <?php echo "$room_id" ; ?> </TD>
</TR>

<?php
      # Determine the area id of the room in question first
      $sql = "select area_id from $tbl_room where id=$room_id";
      $res = sql_query($sql);
      $row = sql_row($res, 0);
      $area_id = $row[0];
      # determine if there is more than one area
      $sql = "select id from $tbl_area";
      $res = sql_query($sql);
      $num_areas = sql_count($res);
      # if there is more than one area then give the option
      # to choose areas.
      if( $num_areas > 1 ) {

?>
<script language="JavaScript">
<!--
function changeRooms( formObj )
{
    areasObj = eval( "formObj.areas" );

    area = areasObj[areasObj.selectedIndex].value
    roomsObj = eval( "formObj.elements['rooms[]']" )

    // remove all entries
    for (i=0; i < (roomsObj.length); i++)
    {
      roomsObj.options[i] = null
    }
    // add entries based on area selected
    switch (area){
<?php
        # get the area id for case statement
	$sql = "select id, area_name from $tbl_area order by area_name";
        $res = sql_query($sql);
	if ($res) for ($i = 0; ($row = sql_row($res, $i)); $i++)
	{

                print "      case \"".$row[0]."\":\n";
        	# get rooms for this area
		$sql2 = "select id, room_name from $tbl_room where area_id='".$row[0]."' order by room_name";
        	$res2 = sql_query($sql2);
		if ($res2) for ($j = 0; ($row2 = sql_row($res2, $j)); $j++)
		{
                	print "        roomsObj.options[$j] = new Option(\"".str_replace('"','\\"',$row2[1])."\",".$row2[0] .")\n";
                }
		# select the first entry by default to ensure
		# that one room is selected to begin with
		print "        roomsObj.options[0].selected = true\n";
		print "        break\n";
	}
?>
    } //switch
}

// create area selector if javascript is enabled as this is required
// if the room selector is to be updated.
this.document.writeln("<tr><td class=CR><b><?php echo get_vocab("areas") ?>:</b></td><td class=CL valign=top>");
this.document.writeln("          <select name=\"areas\" onChange=\"changeRooms(this.form)\">");
<?php
# get list of areas
$sql = "select id, area_name from $tbl_area order by area_name";
$res = sql_query($sql);
if ($res) for ($i = 0; ($row = sql_row($res, $i)); $i++)
{
	$selected = "";
	if ($row[0] == $area_id) {
		$selected = "SELECTED";
	}
	print "this.document.writeln(\"            <option $selected value=\\\"".$row[0]."\\\">".$row[1]."\")\n";
}
?>
this.document.writeln("          </select>");
this.document.writeln("</td></tr>");
// -->
</script>
<?php
} # if $num_areas
?>
<tr>
  <td colspan="2" align="center">
      <input type="submit" value="<?php echo get_vocab("save")?>" />
     </td>
</tr>
<TR>
 <TD colspan=2 align=center>
    <a href="<?php echo "day.php?year=$year&month=$month&day=$day" ?> "><img src="cancel_button.jpg" alt="cancel" width="62" height="20" border="0" /></a></TD>
</TR>
</TABLE>

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