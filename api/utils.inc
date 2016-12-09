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

# move DT forward by DAYS, HOURS and MINUTES
function adjust_dt($dt, $days, $hours, $minutes)
{
    $hastime = false;
    $spec = "P";
    if ($days > 0) {
        $spec .= $days . "D";
    }           
    if (isset($hours) and $hours > 0) {
        $spec .= "T" . $hours . "H";
        $hastime = true;
    }
    if (isset($minutes) and $minutes > 0) {
        if (! $hastime) {
            $spec .= "T";
            $hastime = true;
        }
        $spec .= $minutes . "M";
    }
    $interval = new DateInterval($spec);
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
