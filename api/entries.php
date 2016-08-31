<?php

include "utils.inc";

# Return booking entries in JSON format

if ($_SERVER['REQUEST_METHOD'] != 'GET' && $_SERVER['REQUEST_METHOD'] != 'OPTIONS') 
{
    return_error(405, "method not allowed");
}

$allowed_origins = array("http://www.wokingsquashclub.org", "http://localhost:8000");

if (isset($_SERVER['HTTP_ORIGIN'])) 
{
    $origin = $_SERVER['HTTP_ORIGIN'];
    if (in_array($origin, $allowed_origins, true)) 
    {
        header('Access-Control-Allow-Origin: '. $origin);
        header('Access-Control-Allow-Methods: GET'); 
    } else {
        return_error(403, "forbidden");
    }
}

require_once "../grab_globals.inc.php";
include "../config.inc.php";
include "../functions.inc";
include "../$dbsys.inc";
include "../mrbs_auth.inc";

$timezone = new DateTimeZone('Europe/London');

function to_midnight($adate)
{
    $dt = clone $adate;
    $dt->setTime(0, 0, 0);
    return $dt;
}

function dt_from_iso_str($datestr)
{
    global $timezone;
    $dt = DateTime::createFromFormat("Y-m-d", $datestr, $timezone);
    $dt->setTime(0, 0, 0);
    return $dt;
}

function to_minutes($date)
{
    $hours = intval($date->format("H"));
    $mins  = intval($date->format("i"));
    return 60 * $hours + $mins;
}

function adjust_dt($dt, $days)
{
    $interval = new DateInterval("P" . $days . "D");
    $dt->add($interval);
}

$now_dt = new DateTime(null, $timezone);
$today_dt = to_midnight($now_dt);
$area = get_default_area();
$cutoff_dt = clone $now_dt;
adjust_dt($cutoff_dt, $advance_limit);

if (isset($start_date))
{
    $start_dt = dt_from_iso_str($start_date);    
} else {
    $start_dt = $today_dt;
}

if (isset($end_date))
{
    $end_dt = dt_from_iso_str($end_date);
} else {
    $end_dt = clone $start_dt;    
    adjust_dt($end_dt, $advance_limit+1);
}


$result = array();

$sql = "SELECT $tbl_room.id, start_time, end_time, name, $tbl_entry.id, type,
        $tbl_entry.description, $tbl_entry.create_by, $tbl_entry.timestamp
   FROM $tbl_entry, $tbl_room
   WHERE $tbl_entry.room_id = $tbl_room.id
   AND area_id = $area
   AND start_time <= " . $end_dt->getTimeStamp() . "
   AND start_time > "  . $start_dt->getTimeStamp() . "
   ORDER BY start_time";

$res = sql_query($sql);
if (! $res) 
{
    trigger_error(sql_error());
    return_error(500, "internal server error");
}

for ($i = 0; ($row = sql_row($res, $i)); $i++) 
{
    $room_id    = $row[0];
    $start      = $row[1];
    $slot_dt    = new DateTime(null, $timezone);
    $slot_dt->setTimestamp($start);
    $start_date = $slot_dt->format("Y-m-d");
    $start_time = $slot_dt->format("H:i");
    $start_mins = to_minutes($slot_dt);
    $duration   = $row[2] - $start;
    $date_list  = &retrieve_or_create($result, $start_date);
    $room_slots = &retrieve_or_create($date_list, $room_id);

    $slot = array("start_time" => $start_time, 
                  "start_mins" => $start_mins,
                  "duration_mins" => $duration / 60, 
                  "name" => $row[3],
                  "id" => intval($row[4]),
                  "type" => $row[5],
                  "description" => $row[6],
                  "created_by" => $row[7],
                  "timestamp" => $row[8]);

    $room_slots[$start_time] = $slot;
}

function add_free_slots($date, &$day_bookings, $room)
{
    global $morningstarts, $morningstarts_minutes, $eveningends, $eveningends_minutes, $resolution, $stagger_set, $now_dt, $cutoff_dt;
    $resolution_mins  = $resolution / 60;
    $room_offset_mins = ($room-1) * $resolution_mins;
    $start_mins       = $morningstarts * 60 + $morningstarts_minutes + $room_offset_mins; 
    $end_mins         = $eveningends   * 60 + $eveningends_minutes   + $room_offset_mins - $resolution_mins;
    $default_duration = $resolution_mins * $stagger_set;

    $room_slots = &retrieve_or_create($day_bookings, $room);
    reset($room_slots);
    $nbookings  = count($room_slots);
    $idx = 0;
    $start = $start_mins; 
    $slot_dt = clone $date;
    $start_date = $slot_dt->format("Y-m-d");

    while ($start < $end_mins) 
    {
        $slot_dt->setTime($start/60, $start%60, 0);
        $start_time = $slot_dt->format("H:i");
        if (isset($room_slots[$start_time]))
        {
            $last_slot = $room_slots[$start_time];
            $start += $last_slot["duration_mins"];
            continue;
        }
        $next_start = $end_mins;
        while ($idx < $nbookings)
        {
            $next_slot = current($room_slots);
            if ($next_slot["start_mins"] > $start)
            {
                $next_start = $next_slot["start_mins"];
                break;
            }
            next($room_slots);
            ++$idx;
        }
        $duration_mins = $next_start - $start;
        if ($duration_mins > $default_duration) 
        {
            # try to align with the normal slots for this court
            $remainder = ($start - $start_mins) % $default_duration;
            $duration_mins = $default_duration - $remainder;
        }

        $gap = array("start_mins" => $start,
                     "duration_mins" => $duration_mins,
                     "start_time" => $start_time);
        if ($slot_dt > $now_dt  && $slot_dt < $cutoff_dt)
            $gap["token"] = createToken(sprintf("%sT%s", $start_date, $start_time), $room);
        $room_slots[$start_time] = $gap;
        $start += $duration_mins;
    }
}
        

if (isset($with_tokens))
{
    $iter_dt = clone $start_dt;
    $one_day = new DateInterval("P1D");
    while ($iter_dt < $end_dt)
    {
        $date = $iter_dt->format("Y-m-d");
        $date_list  = &retrieve_or_create($result, $date);
        for ($room = 1; $room <= 3; ++$room) 
        {
            add_free_slots($iter_dt, $date_list, $room); 
        }
        $iter_dt->add($one_day);
    }
}

header("Content-Type: application/json");
echo json_encode($result);

?>
