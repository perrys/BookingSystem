<?php
// $Id: edit_entry_handler.php,v 1.22.2.5 2007/02/13 12:53:27 jberanek Exp $

require_once "grab_globals.inc.php";
include "config.inc.php";
include "functions.inc";
include "$dbsys.inc";
include "mrbs_auth.inc";
include "mrbs_sql.inc";

$day   = date("d");
$month = date("m");
$year  = date("Y");

if(!isset($id))
{
    showAccessDenied($day, $month, $year, $area);
    exit;
}



# Acquire mutex to lock out others trying to book the same slot(s).
if (!sql_mutex_lock("mrbs_noshow"))
    fatal_error(1, get_vocab("failed_to_acquire"));

if ($action == "report_no_show") {

    if (! getAuthorised(1))
    {
        showAccessDenied($day, $month, $year, $area);
        exit;
    }
    mrbs_create_noshow_entry($id);
    
} else if ($action == "remove_no_show") {
    
    $sql = "SELECT update_userid FROM mrbs_noshow WHERE entry_id=$id";
    $reporter_id = sql_query1($sql);
    if ($reporter_id <= 0) {
        showAccessDenied($day, $month, $year, $area);
        exit;
    }
    if (! ($reporter_id == getUserID() || getAuthorised(2))) {
        showAccessDenied($day, $month, $year, $area);
        exit;
    }
    $sql = "DELETE FROM mrbs_noshow WHERE entry_id=$id";    
    sql_command($sql);
}
        
    

# Now its all done go back to the day view
Header("Location: day.php");

print_header($day, $month, $year, $area);
echo "<p><a href=\"day.php\">".get_vocab("returncal")."</a><p>";
include "trailer.inc"; ?>
