<?php
# $Id: view_entry.php,v 1.16 2004/06/23 21:06:52 gwalker Exp $

require_once "grab_globals.inc.php";
include "config.inc.php";
include "functions.inc";
include "$dbsys.inc";
include "mrbs_auth.inc";

#If we dont know the right date then make it up
if(!isset($day) or !isset($month) or !isset($year))
{
	$day   = date("d");
	$month = date("m");
	$year  = date("Y");
}
if(empty($area))
	$area = get_default_area();

print_header($day, $month, $year, $area);

if( empty($series) ) {
	$series = 0;
}
else {
	$series = 1;
}

if( $series ){
	$sql = "
	SELECT $tbl_repeat.name,
	       $tbl_repeat.description,
	       $tbl_repeat.create_by,
	       $tbl_room.room_name,
	       $tbl_area.area_name,
	       $tbl_repeat.type,
	       $tbl_repeat.room_id,
	       " . sql_syntax_timestamp_to_unix("$tbl_repeat.timestamp") . ",
	       ($tbl_repeat.end_time - $tbl_repeat.start_time),
	       $tbl_repeat.start_time,
	       $tbl_repeat.end_time,
	       $tbl_repeat.rep_type,
	       $tbl_repeat.end_date,
	       $tbl_repeat.rep_opt,
	       $tbl_repeat.rep_num_weeks

	FROM  $tbl_repeat, $tbl_room, $tbl_area
	WHERE $tbl_repeat.room_id = $tbl_room.id
		AND $tbl_room.area_id = $tbl_area.id
		AND $tbl_repeat.id=$id
	";
}
else {
	$sql = "
	SELECT TE.name,
	       TE.description,
	       TE.create_by,
	       TR.room_name,
	       TA.area_name,
	       TE.type,
	       TE.room_id,
	       " . sql_syntax_timestamp_to_unix("TE.timestamp") . ",
	       (TE.end_time - TE.start_time),
	       TE.start_time,
	       TE.end_time,
	       TE.repeat_id,
	       NS.timestamp as noshow_ts,
	       NS.update_userid as noshow_reporter
	FROM  $tbl_entry TE
	INNER JOIN $tbl_room TR ON  TE.room_id = TR.id
	INNER JOIN $tbl_area TA ON  TR.area_id = TA.id
        LEFT JOIN mrbs_noshow NS ON NS.entry_id = TE.id
	WHERE TE.id=$id
	";
}

$res = sql_query($sql);
if (! $res) fatal_error(0, sql_error());

if(sql_count($res) < 1) {
	fatal_error(
		0,
		($series ? get_vocab("invalid_series_id") : get_vocab("invalid_entry_id"))
	);
}

$row = sql_row($res, 0);
sql_free($res);

# Note: Removed stripslashes() calls from name and description. Previous
# versions of MRBS mistakenly had the backslash-escapes in the actual database
# records because of an extra addslashes going on. Fix your database and
# leave this code alone, please.
$name         = htmlspecialchars($row[0]);
$description  = htmlspecialchars($row[1]);
$create_by    = htmlspecialchars($row[2]);
$room_name    = htmlspecialchars($row[3]);
$area_name    = htmlspecialchars($row[4]);
$type         = $row[5];
$room_id      = $row[6];
$timestamp_1  = $row[7];
$updated      = time_date_string($row[7]);
$start_time_1 = $row[9];
$end_time_1   = $row[10];
$repeat_id    = $row[11];
$noshow_ts    = $row[12];
$noshow_rep   = $row[13];

# need to make DST correct in opposite direction to entry creation
# so that user see what he expects to see
$duration     = $row[8] - cross_dst($row[9], $row[10]);

$start_date = time_date_string($row[9]);

if( $enable_periods )
	list( , $end_date) =  period_date_string($row[10], -1);
else
        $end_date = time_date_string($row[10]);


$rep_type = 0;

if( $series == 1 ){

	$rep_type     = $row[11];
	$rep_end_date = utf8_strftime('%A %d %B %Y',$row[12]);
	$rep_opt      = $row[13];
	$rep_num_weeks = $row[14];
	# I also need to set $id to the value of a single entry as it is a
	# single entry from a series that is used by del_entry.php and
	# edit_entry.php
	# So I will look for the first entry in the series where the entry is
	# as per the original series settings
	$sql = "SELECT id
	        FROM $tbl_entry
		WHERE repeat_id=\"$id\" AND entry_type=\"1\"
		ORDER BY start_time
		LIMIT 1";
	$res = sql_query($sql);
	if (! $res) fatal_error(0, sql_error());
	if(sql_count($res) < 1) {
		# if all entries in series have been modified then
		# as a fallback position just select the first entry
		# in the series
		# hopefully this code will never be reached as
		# this page will display the start time of the series
		# but edit_entry.php will display the start time of the entry
		sql_free($res);
		$sql = "SELECT id
			FROM $tbl_entry
			WHERE repeat_id=\"$id\"
			ORDER BY start_time
			LIMIT 1";
		$res = sql_query($sql);
		if (! $res) fatal_error(0, sql_error());
	}
	$row = sql_row($res, 0);
	$id = $row[0];
	sql_free($res);
}
else {

	$repeat_id = $row[11];

	if($repeat_id != 0)
	{
		$res = sql_query("SELECT rep_type, end_date, rep_opt, rep_num_weeks
				FROM $tbl_repeat WHERE id=$repeat_id");
		if (! $res) fatal_error(0, sql_error());

		if (sql_count($res) == 1)
		{
			$row = sql_row($res, 0);

			$rep_type     = $row[0];
			$rep_end_date = utf8_strftime('%A %d %B %Y',$row[1]);
			$rep_opt      = $row[2];
			$rep_num_weeks = $row[3];
		}
		sql_free($res);
	}
}


$enable_periods ? toPeriodString($start_period, $duration, $dur_units) : toTimeString($duration, $dur_units);

$repeat_key = "rep_type_" . $rep_type;

# Next check if the user is an admin

    $user = getUserName();
    $level = authGetUserLevel($user, $auth["admin"]);

# Now that we know all the data we start drawing it

?>

<H3><?php echo $name ?></H3>
 <table border=0>
   <tr>
    <td><b><?php echo get_vocab("description") ?></b></td>
    <td><?php    echo nl2br($description) ?></td>
   </tr>
   <tr>
    <td><b><?php echo get_vocab("room").":" ?></b></td>
    <td><?php    echo  nl2br($room_name) ?></td>
   </tr>
   <tr>
    <td><b><?php echo get_vocab("start_date") ?></b></td>
    <td><?php    echo $start_date ?></td>
   </tr>
   <tr>
    <td><b><?php echo get_vocab("duration") ?></b></td>
    <td><?php    echo $duration . " " . $dur_units ?></td>
   </tr>
   <tr>
    <td><b><?php echo get_vocab("end_date") ?></b></td>
    <td><?php    echo $end_date ?></td>
   </tr>
   <tr>
    <td><b><?php echo get_vocab("type") ?></b></td>
    <td><?php    echo empty($typel[$type]) ? "?$type?" : $typel[$type] ?></td>
   </tr>
   <tr>
    <td><b><?php echo get_vocab("createdby") ?></b></td>
    <td><?php    echo $create_by ?></td>
   </tr>
   <tr>
    <td><b><?php echo get_vocab("lastupdate") ?></b></td>
    <td><?php    echo $updated ?></td>
   </tr>
</table>
<br>
<p>
<?php

$timezone = new DateTimeZone('Europe/London');
$slot_dt  = new DateTime(null, $timezone);
$slot_dt->setTimestamp($start_time_1);
$now_dt = new DateTime(null, $timezone);
$diff = $slot_dt->diff($now_dt);
$user = getUserName();
$diff_minutes = (60*$diff->h + $diff->i);
if ($diff->invert)
   $diff_minutes *= -1;
$past_start_time = $diff_minutes >= 15;

if (isset($user) && $diff->days == 0 && $past_start_time) {
   if ($noshow_ts != null) {
      echo "<p>Reported as a NO SHOW on $noshow_ts. ";
      if ($level == 2 || $noshow_rep == getUserID()) {
?>
    <form method="POST" action="report_noshow_handler.php" class="noshow">
        <input type="hidden" name="id" value="<?php echo "$id"; ?>" />
        <input type="hidden" name="action" value="remove_no_show" />        
        <button type="submit">Remove No Show</button>
    </form>
<?php
      }
   } else {
?>
    <form method="POST" action="report_noshow_handler.php" class="noshow">
        <input type="hidden" name="id" value="<?php echo "$id"; ?>" />
        <input type="hidden" name="action" value="report_no_show" />        
        <button type="submit">Report a NO SHOW</button>
    </form>
<?php
   }    
}


# Only an admin can edit a fixed entry
if ($level == 2) {
	
if( ! $series )
	echo "<a href=\"edit_entry.php?id=$id\">". get_vocab("editentry") ."</a>";

if($repeat_id)
	echo " - ";

if($repeat_id || $series )
	echo "<a href=\"edit_entry.php?id=$id&edit_type=series&day=$day&month=$month&year=$year\">".get_vocab("editseries")."</a>";
        echo "<BR>";
}

if ($diff->days * ($diff->invert ? -1 : 1) < 0 || ($diff->days == 0 && !$past_start_time) || $level == 2 ) {

    if( ! $series )
	echo "<A HREF=\"del_entry.php?id=$id&series=0&name=$name&create_by=$create_by&type=$type&description=$description&start_time_1=$start_time_1&end_time_1=$end_time_1&repeat_id=$repeat_id&room_id=$room_id&timestamp_1=$timestamp_1\" onClick=\"return confirm('".get_vocab("confirmdel")."');\">".get_vocab("deleteentry")."</A>";

    if($repeat_id)
	echo " - ";

    if($repeat_id || $series )
	echo "<A HREF=\"del_entry.php?id=$id&series=1&day=$day&month=$month&year=$year\" onClick=\"return confirm('".get_vocab("confirmdel")."');\">".get_vocab("deleteseries")."</A>";	

    echo "<BR>";

    echo "<A HREF=\"edit_description_fixed.php?id=$id&series=0&name=$name&create_by=$create_by&type=$type&description=$description&start_time_1=$start_time_1&end_time_1=$end_time_1&repeat_id=$repeat_id&room_id=$room_id&timestamp_1=$timestamp_1&duration=$duration&dur_units=$dur_units\">Edit Description</A>";

}

?>
<BR>
<?php if (isset($HTTP_REFERER)) //remove the link if displayed from an email
{ ?>
<a href="<?php echo $HTTP_REFERER ?>"><?php echo get_vocab("returnprev") ?></a>
<?php
}
include "trailer.inc"; ?>
