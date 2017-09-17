<?php

include "utils.inc";

# Return booking entries in JSON format

if (! ($_SERVER['REQUEST_METHOD'] == 'GET' || $_SERVER['REQUEST_METHOD'] == 'OPTIONS' || $_SERVER['REQUEST_METHOD'] == 'POST' || $_SERVER['REQUEST_METHOD'] == 'PATCH' || $_SERVER['REQUEST_METHOD'] == 'DELETE') )
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

# some useful dates:

$timezone = new DateTimeZone('Europe/London');
$now_dt = new DateTime(null, $timezone);

$cutoff_dt = clone $now_dt;
adjust_dt($cutoff_dt, $advance_limit, 0, 0);

$today_dt =  clone $now_dt;
$today_dt->setTime(0, 0, 0); # midnight

if (isset($start_date))
{
    $start_dt = dt_from_iso_str($start_date, $timezone);    
} else {
    $start_dt = $today_dt;
}

if (isset($end_date))
{
    $end_dt = dt_from_iso_str($end_date, $timezone);
} else {
    $end_dt = clone $start_dt;    
    adjust_dt($end_dt, $advance_limit+1, 0, 0);
}
    

function &get_entries($start_date, $end_date, $id)
{
    global $timezone, $tbl_entry, $tbl_room, $start_dt, $end_dt, $now_dt, $with_server_time;
    $area = get_default_area();

    $result = array();

    if (isset($with_server_time))
    {
        $result['server_time'] = $now_dt->format(DateTime::ISO8601);
    }
    
    $sql = "SELECT TR.id, start_time, end_time, TE.name, TE.id, type,
            TE.description, TE.create_by, TE.timestamp,
            NS.entry_id, TE.created_ts, TU.id
       FROM $tbl_entry TE
       INNER JOIN $tbl_room TR on TR.id = TE.room_id
       LEFT JOIN mrbs_noshow NS on TE.id = NS.entry_id
       LEFT JOIN mrbs_users TU ON TU.name = TE.create_by
       WHERE area_id = $area ";
    if (isset($id))
    {
        $sql .= " AND TE.id = " . $id;
    } else {
        $sql .= "
       AND start_time <= " . $end_dt->getTimeStamp() . "
       AND start_time > "  . $start_dt->getTimeStamp() . "
       ORDER BY start_time";
    }    
    $res = sql_query($sql);
    if (! $res) 
    {
        trigger_error($sql);
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
                      "created_ts" => $row[10],
                      "created_by" => $row[7],
                      "created_by_id" => isset($row[11]) ? intval($row[11]) : null,
                      "timestamp" => $row[8],
                      "no_show" => $row[9] != null
        );
    
        $room_slots[$start_time] = $slot;
    }

    return $result;
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
    $slot_dt = clone $date;
    $start_date = $slot_dt->format("Y-m-d");

    $iter_mins = 7 * 60; # sometimes courts are reserved by admin earlier than they can be booked.
    while ($iter_mins < $end_mins) 
    {
        $slot_dt->setTime($iter_mins/60, $iter_mins%60, 0);
        $start_time = $slot_dt->format("H:i");
        if (isset($room_slots[$start_time]))
        {
            $last_slot = $room_slots[$start_time];
            $iter_mins += $last_slot["duration_mins"];
            continue;
        }
        if ($iter_mins < $start_mins) # look out for extra-early admin bookings
        {
            $iter_mins += $resolution_mins;
            continue;
        }
        $next_start = $end_mins;
        while ($idx < $nbookings)
        {
            $next_slot = current($room_slots);
            if ($next_slot["start_mins"] > $iter_mins)
            {
                $next_start = $next_slot["start_mins"];
                break;
            }
            next($room_slots);
            ++$idx;
        }
        $duration_mins = $next_start - $iter_mins;
        if ($duration_mins > $default_duration) 
        {
            # try to align with the normal slots for this court
            $remainder = ($iter_mins - $start_mins) % $default_duration;
            $duration_mins = $default_duration - $remainder;
        }

        $gap = array("start_mins" => $iter_mins,
                     "duration_mins" => $duration_mins,
                     "start_time" => $start_time);
        if ($slot_dt > $now_dt  && $slot_dt < $cutoff_dt)
            $gap["token"] = createToken(sprintf("%sT%s", $start_date, $start_time), $room);
        $room_slots[$start_time] = $gap;
        $iter_mins += $duration_mins;
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'GET')
{        
    $result = &get_entries($start_date, $end_date, $id);
    if (!isset($id) && isset($with_tokens))
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
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' || $_SERVER['REQUEST_METHOD'] == 'DELETE' || $_SERVER['REQUEST_METHOD'] == 'PATCH')
{
    include "../mrbs_sql.inc";

    global $timezone, $tbl_entry;
    $data = json_decode(file_get_contents('php://input'), true);

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

    $user_name = getUserNameForID($user_id);
    if (! $user_name)
        return_error(401, "unrecognized user ID");

    $return_object = NULL;
    if (isset($id)) { # we are altering an existing entry
        $id = intval($id);
        $existing_entry = mrbsGetEntryInfo($id);
        if (! $existing_entry) {
            return_error(404, "booking not found");
        }
        if (! getWritable($existing_entry["create_by"], $user_name)) {
            return_error(403, "permission dennied for entry $id");
        }
        $auth_level = authGetUserLevel($user_name, $auth["admin"]);

        if ($request_method == 'DELETE') {
            # handle straight deletion:
            $rightnow = time();
            $delta_t = $rightnow - $existing_entry["start_time"];
            if ($delta_t > 0 && $auth_level < 2) { 
                return_error(403, "cannot delete and entry after its start time " . $delta_t);
            }
            
            $result = mrbsDelEntry($user_name, $id, false, false, $user_id);
            if ($result > 0) {
                $timestamp_old = strtotime($existing_entry['timestamp'] . "Z"); # assume timestamp is UTC
                $timestamp_new = time();
                $sql2 = "INSERT INTO mrbs_entry_deleted 
                         (id, name, create_by, type, description, start_time, end_time, 
                          repeat_id, room_id, timestamp_old, timestamp_new, delete_by) 
                         VALUES 
                         ($id, '{$existing_entry['name']}', '{$existing_entry['create_by']}', '{$existing_entry['type']}',
                          '{$existing_entry['description']}', {$existing_entry['start_time']}, {$existing_entry['end_time']},
                          {$existing_entry['repeat_id']}, {$existing_entry['room_id']},
                          $timestamp_old, $timestamp_new, '$user_name') ";
                $num_rows = sql_command($sql2);    
                if ($num_rows < 0) {
                    trigger_error($sql2);
                    trigger_error(sql_error());
                    return_error(500, "entry deleted but unable to update deleted entries table - please contact webmaster");
                }
                $return_object = $existing_entry;
            } else {
                return_error(500, "unable to delete entry $id");
            }
        } else if ($request_method == 'PATCH') {
            # update an existing
            try {
              $nupdated = mrbsUpdateEntry($id, $user_name, $data['name'], $data['type'], $data['description'], $user_id);
              if ($nupdated == 0) {
                return_error(304, "Entry $id not updated");
              }
            } catch (Exception $e) {
                # assume it is an issue with the input data
                return_error(400, $e->getMessage());
            }
            $return_object = mrbsGetEntryInfo($id);
        } else {
            return_error(405); # method not allowed
        }

    } else {

        $start_time = dt_from_iso_str($data["date"], $timezone);
        adjust_dt($start_time, 0, 0, $data["start_mins"]);
        $end_time = clone $start_time;
        adjust_dt($end_time, 0, 0, $data["duration_mins"]);
        
        $entry_type = 0;
        $repeat_id  = 0;
        $room_id    = $data["court"];
        $start_chk  = $start_time->getTimeStamp();
        $end_chk    = $end_time->getTimeStamp()-1;
        

        # Acquire mutex to lock out others trying to book the same slot(s).
        if (!sql_mutex_lock("$tbl_entry"))
            return_error(503, "couldn't get database table lock");

        # Select any slots which overlap ($starttime,$endtime) for this room:
        $sql = "SELECT id, name, start_time FROM $tbl_entry WHERE
                start_time < $end_chk AND end_time > $start_chk
                AND room_id = $room_id";
        $res = sql_query($sql);
        if (! $res) 
        {
            trigger_error(sql_error());
            return_error(500, "internal server error");
        }
        if (sql_count($res) > 0)
        {
            $err = "ERROR - booking would conflict with the following:\n\n";
            for ($i = 0; ($row = sql_row($res, $i)); $i++)
            {
                if ($i > 0)
                    $err .= ", ";
                $slot_dt = new DateTime(null, $timezone);
                $slot_dt->setTimestamp($row[2]);                
                $err .= $slot_dt->format("H:i") . " " . $row[1];
            }
            return_error(409, $err);
        }
        
        $new_id = mrbsCreateSingleEntry($start_time->getTimeStamp(), 
                                        $end_time->getTimeStamp(), 
                                        $entry_type, $repeat_id,
                                        $room_id,
                                        $user_name, 
                                        $data["name"], 
                                        $data["type"], 
                                        $data["description"],
                                        $user_id
        );

        if ($new_id == 0) {
            return_error(500, "ERROR - failed to create new booking ");        
        }

        sql_mutex_unlock("$tbl_entry");

        $return_object = array("id"          => $new_id,
                               "start_time"  => $start_time->getTimeStamp(),
                               "end_time"    => $end_time->getTimeStamp(),
                               "name"        => $data["name"],
                               "description" => $data["description"],
                               "room_id"     => $data["court"]);
    }
    
    header("Content-Type: application/json");
    
    echo json_encode($return_object);
}


?>
