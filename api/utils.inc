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

?>