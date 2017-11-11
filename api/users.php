<?php

include "utils.inc";

if (! ($_SERVER['REQUEST_METHOD'] == 'GET' ||
       $_SERVER['REQUEST_METHOD'] == 'DELETE' ||
       $_SERVER['REQUEST_METHOD'] == 'POST' ||
       $_SERVER['REQUEST_METHOD'] == 'OPTIONS' ||
       $_SERVER['REQUEST_METHOD'] == 'PATCH' ))
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
        return_error(403, "Forbidden");
    }
}

require_once "../grab_globals.inc.php";
include "../config.inc.php";
include "../functions.inc";
include "../$dbsys.inc";
include "../mrbs_auth.inc";

$user = getUserName();
if (isset($user) == FALSE)
{
    return_error(401, "Please log in first");
}
$auth_level = authGetUserLevel($user, $auth["admin"]);

function &get_users($id)
{
    global $tbl_users;
    $result = array();
    $sql = "SELECT id, name, email FROM $tbl_users";
    if (isset($id))
    {
        $sql .= " where id = " . intval($id);
    }
    $res = sql_query($sql);
    if (! $res) 
    {
        trigger_error($sql);
        trigger_error(sql_error());
        return_error(500, "Unknown error");
    }
    for ($i = 0; ($row = sql_row($res, $i)); $i++) 
    {
        $result[] = array("id" => intval($row[0]), "name" => $row[1], "email" => $row[2]);
    }
    return $result;
}

$request_method = $_SERVER['REQUEST_METHOD'];
if (isset($method)) 
{
    $request_method = $method;
}

if ($request_method == 'GET')
{        
    $result = &get_users($id);
    header("Content-Type: application/json");
    echo json_encode($result);
}
else if ($request_method == 'DELETE')
{
    if ($auth_level < 2)
        return_error(401, "Admin rights required");
    
    if (! isset($id))
        return_error(400, "ID not supplied");
    
    $id = intval($id);
    $sql = "DELETE FROM $tbl_users WHERE id=$id";
    $nrows = sql_command($sql);
    if ($num_rows < 0)
    {
        trigger_error($sql);
        trigger_error(sql_error());
        return_error(500, "ERROR - user not delted");
    }
    else if ($nrows == 0)
    {
        return_error(404, "id not found");
    }
}   
else if ($request_method == 'POST')
{
    if ($auth_level < 2)
        return_error(401, "Admin rights required");

    if (! (isset($name) && isset($password) && isset($email)))
        return_error(400, "Details not supplied");

    $name = slashes($name);
    $email = slashes($email);
    $password = md5($password);
    sql_mutex_lock($tbl_users);
    $id = sql_query1("select max(id) from $tbl_users");
    $id += 1;
    $sql = "INSERT INTO $tbl_users (id, name, password, email) VALUES ($id, '$name', '$password', '$email')";
    $num_rows = sql_command($sql);
    sql_mutex_unlock($tbl_users);
    if ($num_rows < 0) {
        trigger_error($sql);
        trigger_error(sql_error());
        return_error(500, "user not created");
    }
    $result = array("id" => $id);
    header("Content-Type: application/json");
    echo json_encode($result);
}    
else if ($request_method == 'PATCH')
{
    if ($auth_level < 2)
        return_error(401, "Admin rights required");

    if (! isset($id))
        return_error(400, "id not supplied");

    $id = intval($id);
    $sql = "UPDATE $tbl_users SET";
    $updated = false;
    if (isset($name))
    {
        $name = slashes($name);
        $sql .= " name='$name'";
        $updated = true;
    }
    if (isset($email))
    {
        if ($updated)
            $sql .= ", ";
        $email = slashes($email);
        $sql .= " email='$email'";
        $updated = true;
    }
    $sql .= " WHERE id=$id";
    trigger_error($sql);
    if (! $updated) {
        return_error(400, "no updatable fields supplied");
    }
    
    $num_rows = sql_command($sql);
    if ($num_rows < 0) {
        trigger_error($sql);
        trigger_error(sql_error());
        return_error(500, "user not created");
    }
}    
    
?>
