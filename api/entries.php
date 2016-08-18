<?php

include "utils.inc";

# Return booking entries in JSON format

if ($_SERVER['REQUEST_METHOD'] != 'GET') 
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

$SECONDS_PER_DAY = 24 * 60 * 60;

$now_timestamp = time();
$today_timestamp = $SECONDS_PER_DAY * ($now_timestamp / $SECONDS_PER_DAY);
$area = get_default_area();

if (isset($start_date))
{
    $start_timestamp = DateTime::createFromFormat("Y-m-d", $start_date)->getTimestamp();
} else {
    $start_timestamp = $today_timestamp;
}

if (isset($end_date))
{
    $end_timestamp = DateTime::createFromFormat("Y-m-d", $end_date)->getTimestamp();
} else {
    $end_timestamp = $start_timestamp + ($advance_limit + 1) * $SECONDS_PER_DAY;
}

$result = array();

$sql = "SELECT $tbl_room.id, start_time, end_time, name, $tbl_entry.id, type,
        $tbl_entry.description, $tbl_entry.create_by
   FROM $tbl_entry, $tbl_room
   WHERE $tbl_entry.room_id = $tbl_room.id
   AND area_id = $area
   AND start_time <= $end_timestamp AND start_time > $start_timestamp
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
    $start_date = date("Y-m-d", $start);
    $start_time = date("H:i",   $start);
    $duration   = $row[2] - $start;
    $date_list  = &retrieve_or_create($result, $start_date);
    $room_slots = &retrieve_or_create($date_list, $room_id);

    $slot = array("start_time" => $start_time, 
                  "start_mins" => ($start % $SECONDS_PER_DAY) / 60,
                  "duration_mins" => $duration / 60, 
                  "name" => $row[3],
                  "id" => intval($row[4]),
                  "type" => $row[5],
                  "description" => $row[6],
                  "created_by" => $row[7]);

    $room_slots[$start_time] = $slot;
}

function add_free_slots($date, &$day_bookings, $room)
{
    global $morningstarts, $morningstarts_minutes, $eveningends, $eveningends_minutes, $resolution, $stagger_set;
    $resolution_mins  = $resolution / 60;
    $room_offset_mins = ($room-1) * $resolution_mins;
    $start_mins       = $morningstarts * 60 + $morningstarts_minutes + $room_offset_mins; 
    $end_mins         = $eveningends   * 60 + $eveningends_minutes   + $resolution_mins;
    $default_duration = $resolution_mins * $stagger_set;

    $room_slots = &retrieve_or_create($day_bookings, $room);
    reset($room_slots);
    $nbookings  = count($room_slots);
    $idx = 0;

    for ($start = $start_mins; $start < $end_mins; $start += $default_duration) 
    {
        $start_time = sprintf("%02d:%02d", $start/60, $start%60);
        if (isset($room_slots[$start_time]))
            continue;
        $next_start = $end_mins;
        while ($idx < $nbookings)
        {
            $next_slot = current($room_slots);
            next($room_slots);
            ++$idx;
            if ($next_slot["start_mins"] > $start)
            {
                $next_start = $next_slot["start_mins"];
                break;
            }
        }
        $duration = min($default_duration, $next_start - $start);

        $gap = array("start_mins" => $start,
                     "duration_mins" => $duration,
                     "start_time" => $start_time,
                     "token" => createToken(sprintf("%sT%s", $date, $start_time), $room));
        $room_slots[$start_time] = $gap;
    }
}
        

if (isset($with_tokens))
{
    for ($date_ts = $start_timestamp; $date_ts < $end_timestamp; $date_ts += $SECONDS_PER_DAY)
    {
        $date = date("Y-m-d", $date_ts);
        $date_list  = &retrieve_or_create($result, $date);
        for ($room = 1; $room <= 3; ++$room) 
        {
            add_free_slots($start_date, $date_list, $room); 
        }
    }
}

header("Content-Type: application/json");
echo json_encode($result);

?>
