<?php
# $Id: del_entry.php,v 1.5.2.1 2005/03/29 13:26:16 jberanek Exp $

require_once "grab_globals.inc.php";
include "config.inc.php";
include "functions.inc";
include "$dbsys.inc";
include "mrbs_auth.inc";
include "mrbs_sql.inc";

$username1        = getUserName();
$username1 = ucwords ($username1) ;

if( (getAuthorised(1)) OR ($username1 == "Club") )
{
if ($info = mrbsGetEntryInfo($id))
{
	if (time() > $info["start_time"] && (! getAuthorised(2))) {
	    showTooLateDenied($day, $month, $year, $area);
	    return;
	}
	$day   = strftime("%d", $info["start_time"]);
	$month = strftime("%m", $info["start_time"]);
	$year  = strftime("%Y", $info["start_time"]);
	$area  = mrbsGetRoomArea($info["room_id"]);
	
# Transfer data to deleted entries table
$timestamp_new = time();
$delete_by = $username1;
$sql2 = "INSERT INTO mrbs_entry_deleted( id, name, create_by, type, description, start_time, end_time, repeat_id, room_id, timestamp_old, timestamp_new, delete_by ) VALUES ( $id, '$name', '$create_by', '$type', '$description', $start_time_1, $end_time_1, $repeat_id, $room_id, $timestamp_1, $timestamp_new, '$delete_by' ) ";
if ($username1 !== "Webmaster")
{$res2 = mysql_query($sql2);}

# E-mail confirmation of deletion
if (MAIL_ADMIN_ON_DELETE)
    {
        include_once "functions_mail.inc";
        // Gather all fields values for use in emails.
        $mail_previous = getPreviousEntryData($id, $series);
    }
    sql_begin();
	$result = mrbsDelEntry(getUserName(), $id, $series, 1);
	sql_commit();
	if ($result)
	{
        // Send a mail to the Administrator
        (MAIL_ADMIN_ON_DELETE) ? $result = notifyAdminOnDelete($mail_previous) : '';
        Header("Location: day.php?day=$day&month=$month&year=$year&area=$area");
		exit();
	}
	}
}

// If you got this far then we got an access denied.
showAccessDenied($day, $month, $year, $area);
?>
