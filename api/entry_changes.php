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

function &get_audit_table($start_dt, $end_dt)
{
    global $timezone;
    $area = get_default_area();

    $result = array();

    $sql = "SELECT entry_id, MC.name, type, description, start_time, end_time, room_id, owner, update_type, MU.name, update_userid, update_gui, timestamp 
            FROM mrbs_entry_changes MC, mrbs_users MU
            WHERE MC.update_userid = MU.id 
            AND timestamp <= '" . $end_dt->format("Y-m-d") . "'
            AND timestamp > '"  . $start_dt->format("Y-m-d") . "'";
    $res = sql_query($sql);
    if (! $res) 
    {
        trigger_error(sql_error());
        return_error(500, "internal server error");
    }

    $result = array();
    for ($i = 0; ($row = sql_row($res, $i)); $i++) 
    {
        $start      = $row[4];
        $slot_dt    = new DateTime(null, $timezone);
        $slot_dt->setTimestamp($start);
        $start_date = $slot_dt->format("Y-m-d");
        $start_time = $slot_dt->format("H:i");
        $start_mins = to_minutes($slot_dt);
        $duration   = $row[5] - $start;
        
        $slot = array("entry_id" => $row[0],
                      "name" => $row[1],
                      "type" => $row[2],
                      "description" => $row[3],
                      "date" => $start_date, 
                      "time" => $start_time, 
                      "duration_mins" => $duration / 60, 
                      "court" => $row[6],
                      "owner" => $row[7],
                      "update_type" => $row[8],
                      "update_username" => $row[9],
                      "update_userid" => intval($row[10]),
                      "update_gui" => $row[11],
                      "update_timestamp" => $row[12],
        );
       
        array_push($result, $slot);
    }
    return $result;
}

if ($_SERVER['REQUEST_METHOD'] == 'GET')
{        
    if (! isset($date)) {
        return_error(400, "date must be supplied");
    }
    if (! isset($ndays)) {
        $ndays = 1;
    }
    
    $start_dt = dt_from_iso_str($date, $timezone);
    $end_dt = clone $start_dt;
    $end_dt->add(new DateInterval("P" . $ndays . "D"));

    $result = &get_audit_table($start_dt, $end_dt);
    if (isset($format) and $format == "csv") {        
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=data.csv');
        $output = fopen('php://output', 'w');
        $cols = null;
        foreach ($result as $row) {
            if (! $cols) {
                $cols = array_keys($row);
                fputcsv($output, $cols);
            }
            fputcsv($output, $row);
        }
    } else {        
        header("Content-Type: application/json");
        echo json_encode($result, true);
    }
}
