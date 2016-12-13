<?php

if (! ($_SERVER['REQUEST_METHOD'] == 'GET') )
{
    return_error(405, "method not allowed");
}


require_once "../grab_globals.inc.php";
include "../config.inc.php";
include "../functions.inc";
include "../$dbsys.inc";
include "../mrbs_auth.inc";
include "utils.inc";

# some useful dates:

$timezone = new DateTimeZone('Europe/London');

if (! (isset($start_date) && isset($end_date))) {
    return_error(400, "start and end dates must be supplied");
}

$start_dt = dt_from_iso_str($start_date, $timezone);    
$end_dt = dt_from_iso_str($end_date, $timezone);

function &get_deleted_entries($start_date, $end_date)
{
    global $timezone, $tbl_entry, $tbl_room, $start_dt, $end_dt;
    $area = get_default_area();

    $result = array();

    $sql = "SELECT room_id, start_time, end_time, name, id, type, description, create_by, timestamp_old, delete_by, timestamp 
            FROM mrbs_entry_deleted
            WHERE start_time <= " . $end_dt->getTimeStamp() . "
            AND start_time > "  . $start_dt->getTimeStamp() ;
    
    $res = sql_query($sql);
    if (! $res) 
    {
        trigger_error(sql_error());
        return_error(500, "internal server error");
    }

    $result = array();
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
        $orig_ts    = new DateTime(null, $timezone);
        $orig_ts->setTimestamp($row[8]);
    
        $slot = array("start_date" => $start_date,
                      "start_time" => $start_time, 
                      "start_mins" => $start_mins,
                      "court" => $room_id,
                      "duration_mins" => $duration / 60, 
                      "name" => $row[3],
                      "id" => intval($row[4]),
                      "type" => $row[5],
                      "description" => $row[6],
                      "updated_by" => $row[7],
                      "updated_timestamp" => $orig_ts->format("Y-m-d H:i:s"),
                      "deleted_by" => $row[9],
                      "deleted_timestamp" => $row[10]
            );
    
        array_push($result, $slot);
    }
    return $result;
}

if ($_SERVER['REQUEST_METHOD'] == 'GET')
{        
    $result = &get_deleted_entries($start_date, $end_date);
    header("Content-Type: application/json");
    echo json_encode($result, true);
}
