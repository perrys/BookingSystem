<?php
// $Id: edit_entry_handler.php,v 1.22.2.5 2007/02/13 12:53:27 jberanek Exp $

require_once "grab_globals.inc.php";
include "config.inc.php";
include "functions.inc";
include "$dbsys.inc";
include "mrbs_auth.inc";
include "mrbs_sql.inc";

#If we dont know the right date then make it up
if(!isset($day) or !isset($month) or !isset($year))
{
    $day   = date("d");
    $month = date("m");
    $year  = date("Y");
}

if(empty($area))
    $area = get_default_area();

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

if ($name == '')
{
     print_header($day, $month, $year, $area);
     ?>
       <H1><?php echo get_vocab('invalid_booking'); ?></H1>
       <?php echo get_vocab('must_set_description'); ?>
   </BODY>
</HTML>
<?php
     exit;
}       

# Support locales where ',' is used as the decimal point
$duration = preg_replace('/,/', '.', $duration);

if( $enable_periods ) {
	$resolution = 60;
	$hour = 12;
	$minute = $period;
        $max_periods = count($periods);
        if( $dur_units == "periods" && ($minute + $duration) > $max_periods )
        {
            $duration = (24*60*floor($duration/$max_periods)) + ($duration%$max_periods);
        }
        if( $dur_units == "days" && $minute == 0 )
        {
		$dur_units = "periods";
                $duration = $max_periods + ($duration-1)*60*24;
        }
    }

// Units start in seconds
$units = 1.0;

switch($dur_units)
{
    case "years":
        $units *= 52;
    case "weeks":
        $units *= 7;
    case "days":
        $units *= 24;
    case "hours":
        $units *= 60;
    case "periods":
    case "minutes":
        $units *= 60;
    case "seconds":
        break;
}

// Units are now in "$dur_units" numbers of seconds


if(isset($all_day) && ($all_day == "yes"))
{
    if( $enable_periods )
    {
        $starttime = mktime(12, 0, 0, $month, $day, $year);
        $endtime   = mktime(12, $max_periods, 0, $month, $day, $year);
    }
    else
    {
        $starttime = mktime(9, 0, 0, $month, $day  , $year, is_dst($month, $day  , $year));
        $end_minutes = $eveningends_minutes + $morningstarts_minutes;
        ($eveningends_minutes > 59) ? $end_minutes += 60 : '';
        $endtime   = mktime($eveningends, $end_minutes, 0, $month, $day, $year, is_dst($month, $day, $year));
    }
}
else
{
    if (!$twentyfourhour_format)
    {
      if (isset($ampm) && ($ampm == "pm") && ($hour<12))
      {
        $hour += 12;
      }
      if (isset($ampm) && ($ampm == "am") && ($hour>11))
      {
        $hour -= 12;
      }
    }

    $starttime = mktime($hour, $minute, 0, $month, $day, $year, is_dst($month, $day, $year, $hour));
    $endtime   = mktime($hour, $minute, 0, $month, $day, $year, is_dst($month, $day, $year, $hour)) + ($units * $duration);

    # Round up the duration to the next whole resolution unit.
    # If they asked for 0 minutes, push that up to 1 resolution unit.
    $diff = $endtime - $starttime;
    if (($tmp = $diff % $resolution) != 0 || $diff == 0)
        $endtime += $resolution - $tmp;

    $endtime += cross_dst( $starttime, $endtime );
}

if(isset($rep_type) && isset($rep_end_month) && isset($rep_end_day) && isset($rep_end_year))
{
    // Get the repeat entry settings
    $rep_enddate = mktime($hour, $minute, 0, $rep_end_month, $rep_end_day, $rep_end_year);
}
else
    $rep_type = 0;

if(!isset($rep_day))
    $rep_day = array();

# For weekly repeat(2), build string of weekdays to repeat on:
$rep_opt = "";
if (($rep_type == 2) || ($rep_type == 6))
    for ($i = 0; $i < 7; $i++) $rep_opt .= empty($rep_day[$i]) ? "0" : "1";


# Expand a series into a list of start times:
if ($rep_type != 0)
    $reps = mrbsGetRepeatEntryList($starttime, isset($rep_enddate) ? $rep_enddate : 0,
        $rep_type, $rep_opt, $max_rep_entrys, $rep_num_weeks);

# When checking for overlaps, for Edit (not New), ignore this entry and series:
$repeat_id = 0;
if (isset($id))
{
    $ignore_id = $id;
    $repeat_id = sql_query1("SELECT repeat_id FROM $tbl_entry WHERE id=$id");
    if ($repeat_id < 0)
        $repeat_id = 0;
}
else
    $ignore_id = 0;

# Acquire mutex to lock out others trying to book the same slot(s).
if (!sql_mutex_lock("$tbl_entry"))
    fatal_error(1, get_vocab("failed_to_acquire"));

$delete_original = true;

# Check for any schedule conflicts in each room we're going to try and
# book in





        if($edit_type == "series")
        {
            $new_id = mrbsCreateRepeatingEntrys($starttime, $endtime,   $rep_type, $rep_enddate, $rep_opt,
                                      $room_id,   $create_by, $name,     $type,        $description,
                                      isset($rep_num_weeks) ? $rep_num_weeks : 0);
            // Send a mail to the Administrator
            if (MAIL_ADMIN_ON_BOOKINGS or MAIL_AREA_ADMIN_ON_BOOKINGS or
                MAIL_ROOM_ADMIN_ON_BOOKINGS or MAIL_BOOKER)
            {
                include_once "functions_mail.inc";
                // Send a mail only if this a new entry, or if this is an
                // edited entry but we have to send mail on every change,
                // and if mrbsCreateRepeatingEntrys is successful
                if ( ( (isset($id) && MAIL_ADMIN_ALL) or !isset($id) ) && (0 != $new_id) )
                {
                    // Get room name and area name. Would be better to avoid
                    // a database access just for that. Ran only if we need
                    // details
                    if (MAIL_DETAILS)
                    {
                        $sql = "SELECT r.id, r.room_name, r.area_id, a.area_name ";
                        $sql .= "FROM $tbl_room r, $tbl_area a ";
                        $sql .= "WHERE r.id=$room_id AND r.area_id = a.id";
                        $res = sql_query($sql);
                        $row = sql_row($res, 0);
                        $room_name = $row[1];
                        $area_name = $row[3];
                    }
                    // If this is a modified entry then call
                    // getPreviousEntryData to prepare entry comparison.
                    if ( isset($id) )
                    {
                        $mail_previous = getPreviousEntryData($id, 1);
                    }
                    $result = notifyAdminOnBooking(!isset($id), $new_id);
                }
            }
        }
        else
        {
            # Mark changed entry in a series with entry_type 2:
            if ($repeat_id > 0)
                $entry_type = 2;
            else
                $entry_type = 0;

            $mail_previous = null;
            include_once "functions_mail.inc";
            if ( isset($id) )
            {
                $mail_previous = getPreviousEntryData($id, 0);
            }
            
            if ($mail_previous != null and
                $starttime  == $mail_previous["start_time"] and
                $endtime    == $mail_previous["end_time"] and
                $entry_type == $mail_previous["entry_type"] and
                $repeat_id  == $mail_previous["repeat_id"] and
                $room_id    == $mail_previous["room_id"])
            {
                # Just an update to metadata, so we can update the entry in place:
                mrbsUpdateEntry($id, $create_by, $name, $type, $description);
                $new_id = $id;
                $delete_original = false;
            } else {            
                # Create the entry:
                $new_id = mrbsCreateSingleEntry($starttime, $endtime, $entry_type, $repeat_id, $room_id,
                                                $create_by, $name, $type, $description);
            }
            // Send a mail to the Administrator
            if (MAIL_ADMIN_ON_BOOKINGS or MAIL_AREA_ADMIN_ON_BOOKINGS or
                MAIL_ROOM_ADMIN_ON_BOOKINGS or MAIL_BOOKER)
            {
                // Send a mail only if this a new entry, or if this is an
                // edited entry but we have to send mail on every change,
                // and if mrbsCreateRepeatingEntrys is successful
                if ( ( (isset($id) && MAIL_ADMIN_ALL) or !isset($id) ) && (0 != $new_id) )
                {
                    // Get room name and are name. Would be better to avoid
                    // a database access just for that. Ran only if we need
                    // details.
                    if (MAIL_DETAILS)
                    {
                        $sql = "SELECT r.id, r.room_name, r.area_id, a.area_name ";
                        $sql .= "FROM $tbl_room r, $tbl_area a ";
                        $sql .= "WHERE r.id=$room_id AND r.area_id = a.id";
                        $res = sql_query($sql);
                        $row = sql_row($res, 0);
                        $room_name = $row[1];
                        $area_name = $row[3];
                    }
                    // If this is a modified entry then call
                    // getPreviousEntryData to prepare entry comparison.
                    $result = notifyAdminOnBooking(!isset($id), $new_id);
                }
            }
        }


    # Delete the original entry
    if(isset($id) and $delete_original)
        mrbsDelEntry(getUserName(), $id, ($edit_type == "series"), 1);

    sql_mutex_unlock("$tbl_entry");
    
    $area = mrbsGetRoomArea($room_id);
    
    # Now its all done go back to the day view
    Header("Location: day.php?year=$year&month=$month&day=$day&area=$area");
    exit;


# The room was not free.
sql_mutex_unlock("$tbl_entry");

if(strlen($err))
{
    print_header($day, $month, $year, $area);
    
    echo "<H2>" . get_vocab("sched_conflict") . "</H2>";
    if(!isset($hide_title))
    {
        echo get_vocab("conflict");
        echo "<UL>";
    }
    
    echo $err;
    
    if(!isset($hide_title))
        echo "</UL>";
}

echo "<a href=\"day.php\">".get_vocab("returncal")."</a><p>";

include "trailer.inc"; ?>
