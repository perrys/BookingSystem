<?php

if (! ($_SERVER['REQUEST_METHOD'] == 'GET' || $_SERVER['REQUEST_METHOD'] == 'POST' || $_SERVER['REQUEST_METHOD'] == 'DELETE') )
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
include "utils.inc";

$timezone = new DateTimeZone('Europe/London');

function &get_noshow_table($start_dt, $end_dt)
{
    global $timezone;
    $area = get_default_area();

    $result = array();

    $sql = "SELECT TE.id, TE.name, TE.type, TE.description, TE.start_time, TE.end_time, TE.room_id, TE.create_by, 
                   NS.update_userid, TU.name, NS.update_gui, NS.timestamp, TE.created_ts 
            FROM mrbs_noshow NS
            INNER JOIN mrbs_entry TE ON TE.id = NS.entry_id
            LEFT JOIN mrbs_users TU ON TU.id = NS.update_userid
            WHERE  NS.timestamp <= '" . $end_dt->format("Y-m-d") . "'
            AND    NS.timestamp > '"  . $start_dt->format("Y-m-d") . "'";
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
        $duration   = $row[5] - $start;
        $owner      = $row[7];
        $owner_id   = intval(sql_query1("SELECT id FROM mrbs_users WHERE name='" . $owner . "'"));
        $owner_id   = $owner_id < 0 ? null : $owner_id;
        
        $slot = array("entry_id" => intval($row[0]),
                      "name" => $row[1],
                      "type" => $row[2],
                      "description" => $row[3],
                      "date" => $start_date, 
                      "time" => $start_time, 
                      "duration_mins" => $duration / 60, 
                      "court" => intval($row[6]),
                      "created_ts" => $row[12],
                      "owner" => $owner,
                      "owner_userid" => $owner_id,
                      "reporter_userid" => intval($row[8]),
                      "reporter_name" => $row[9],
                      "update_gui" => $row[10],
                      "timestamp" => $row[11]
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
    adjust_dt($end_dt, $ndays);

    $result = &get_noshow_table($start_dt, $end_dt);
    output_table($result, "noshow_" . $start_dt->format("Y-m-d"));
}
else if ($_SERVER['REQUEST_METHOD'] == 'POST' || $_SERVER['REQUEST_METHOD'] == 'DELETE')
{
    include "../mrbs_sql.inc";

    global $timezone, $tbl_entry;
    $data = json_decode(file_get_contents('php://input'), true);
    trigger_error($data);

    $user_id = $data["user_id"];
    $expected_token = createTokenRaw(sprintf("id:%d", $user_id));
    $request_method = $_SERVER['REQUEST_METHOD'];
    if (isset($method)) 
    {
        $request_method = $method;
    }

    if (strcasecmp($expected_token, $data["user_token"]) != 0) {
        return_error(401, "could not authenticate user ID");
    }

    if (isset($id)) {
        $id = intval($id);
        if ($request_method == "DELETE") {
            $sql = "SELECT update_userid FROM mrbs_noshow WHERE entry_id=$id";
            $reporter_id = sql_query1($sql);
            if ($reporter_id <= 0) {
                return_error(404, "noshow entry not found");
            }
            if ($reporter_id != $user_id) {
                return_error(403, "not authorized to change this entry");
            }
            $sql = "DELETE FROM mrbs_noshow WHERE entry_id=$id";    
            $nrows = sql_command($sql);
            if ($nrows > 0)
                return_error(202, "removed");
            else {
                trigger_error($sql);
                trigger_error(sql_error());
                return_error(500, "delete possibly not completed");
            }
        } else if ($request_method == "POST") {
            if (! mrbs_create_noshow_entry($id, $user_id)) {
                return_error(500, "noshow not created for supplied id");
            }
            return_error(201, "created");
        }
    }
}
