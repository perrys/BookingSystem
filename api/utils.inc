<?php

function return_error($status_code, $msg) 
{
    header("Content-Type: text/plain");
    http_response_code($status_code);
    echo $status_code . ": " . $msg;
    exit;
}

# get the value for KEY from the associative array ARR; if not present
# then insert a new arrry under KEY and return that.
function &retrieve_or_create(&$arr, $key) 
{
    $target = null;
    if (! isset($arr[$key])) {
        $target = array();
        $arr[$key] =& $target;
    } else {
        $target =& $arr[$key];
    }
    return $target;
}

# move DT forward by DAYS
function adjust_dt($dt, $days)
{
    $interval = new DateInterval("P" . $days . "D");
    $dt->add($interval);
}

# return a date from a string in ISO8859 YYYY-MM-DD format
function dt_from_iso_str($datestr, $timezone)
{
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



?>