<?php

function return_error($status_code, $msg) 
{
    header("Content-Type: text/plain");
    switch ($status_code) {
      case 100: $text = 'Continue'; break;
      case 101: $text = 'Switching Protocols'; break;
      case 200: $text = 'OK'; break;
      case 201: $text = 'Created'; break;
      case 202: $text = 'Accepted'; break;
      case 203: $text = 'Non-Authoritative Information'; break;
      case 204: $text = 'No Content'; break;
      case 205: $text = 'Reset Content'; break;
      case 206: $text = 'Partial Content'; break;
      case 300: $text = 'Multiple Choices'; break;
      case 301: $text = 'Moved Permanently'; break;
      case 302: $text = 'Moved Temporarily'; break;
      case 303: $text = 'See Other'; break;
      case 304: $text = 'Not Modified'; break;
      case 305: $text = 'Use Proxy'; break;
      case 400: $text = 'Bad Request'; break;
      case 401: $text = 'Unauthorized'; break;
      case 402: $text = 'Payment Required'; break;
      case 403: $text = 'Forbidden'; break;
      case 404: $text = 'Not Found'; break;
      case 405: $text = 'Method Not Allowed'; break;
      case 406: $text = 'Not Acceptable'; break;
      case 407: $text = 'Proxy Authentication Required'; break;
      case 408: $text = 'Request Time-out'; break;
      case 409: $text = 'Conflict'; break;
      case 410: $text = 'Gone'; break;
      case 411: $text = 'Length Required'; break;
      case 412: $text = 'Precondition Failed'; break;
      case 413: $text = 'Request Entity Too Large'; break;
      case 414: $text = 'Request-URI Too Large'; break;
      case 415: $text = 'Unsupported Media Type'; break;
      case 500: $text = 'Internal Server Error'; break;
      case 501: $text = 'Not Implemented'; break;
      case 502: $text = 'Bad Gateway'; break;
      case 503: $text = 'Service Unavailable'; break;
      case 504: $text = 'Gateway Time-out'; break;
      case 505: $text = 'HTTP Version not supported'; break;
      default:
        exit('Unknown http status code "' . htmlentities($status_code) . '"');
        break;
    }

    $protocol = (isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0');

    header($protocol . ' ' . $status_code . ' ' . $text);
    header('Content-Type: text/plain');

    echo $status_code . " [" . $text . "]: " . $msg;
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
