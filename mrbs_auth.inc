<?php
// $Id: mrbs_auth.inc,v 1.8.2.1 2005/03/29 13:26:27 jberanek Exp $

// include the authentification wrappers
include "auth_$auth[type].inc";
if (isset($auth['session'])) include "session_$auth[session].inc";

/* getAuthorised($user, $pass, $level)
 * 
 * Check to see if the user name/password is valid
 * 
 * $user  - The user name
 * $pass  - The users password
 * $level - The access level required
 * 
 * Returns:
 *   0        - The user does not have the required access
 *   non-zero - The user has the required access
 */
function getAuthorised($level)
{
    global $auth;

    $user = getUserName();
    if(isset($user) == FALSE) {
        authGet();
        return 0;
    }

    return authGetUserLevel($user, $auth["admin"]) >= $level;
}

/* 
 * Returns the name corresponding to USER_ID, or false if not found
 * 
 */
function getUserNameForID($user_id)
{
   global $tbl_users;

   $res = sql_query("select name from $tbl_users where id = $user_id");
   switch(sql_count($res)) 
   {
       case 0:
         sql_free($res);
         return false;
       case 1:
         $name = sql_row($res, 0)[0];
         sql_free($res);
         return $name;
       default:
         sql_free($res);
         throw new Exception("more than one matching user entry for id $user_id");
   }    
   return false;
}


/* getWritable($creator, $user)
 * 
 * Determines if a user is able to modify an entry
 *
 * $creator - The creator of the entry
 * $user    - Who wants to modify it
 *
 * Returns:
 *   0        - The user does not have the required access
 *   non-zero - The user has the required access
 */
function getWritable($creator, $user)
{
    global $auth;

    // Always allowed to modify your own stuff
    if(strcasecmp($creator, $user) == 0)
        return 1;

    if(authGetUserLevel($user, $auth["admin"]) >= 2)
        return 1;

    // Unathorised access
    return 0;
}

/* showAccessDenied()
 * 
 * Displays an appropate message when access has been denied
 * 
 * Retusns: Nothing
 */
function showAccessDenied($day, $month, $year, $area)
{
    global $HTTP_REFERER;

    print_header($day, $month, $year, $area);
?>
  <H1><?php echo get_vocab("accessdenied")?></H1>
  <P>
   <?php echo get_vocab("norights")?>
  </P>
  <P>
   <A HREF="<?php echo $HTTP_REFERER; ?>"><?php echo get_vocab("returnprev"); ?></A>
  </P>
 </BODY>
</HTML>
<?php
}

function createTokenRaw($message) 
{
    global $csrf_token_salt;
    return hash_hmac("md5", $message, $csrf_token_salt);
}

function createToken($datetime, $room) 
{
    if (! is_string($datetime))
    {
        assert("get_class($datetime) == \"DateTime\"");
        $datetime = $datetime->format("Y-m-dTH:i");
    }
    $message = sprintf("%s/%d", $datetime, intval($room));
    return createTokenRaw($message);
}

function createTokenYMD($year, $month, $day, $hour, $minute, $room) 
{
    $datetime = sprintf("%04d-%02d-%02dT%02d:%02d", intval($year), intval($month), intval($day), intval($hour), intval($minute));
    return createToken($datetime, $room);
}

function verifyToken($start_year, $start_month, $start_day, $start_hour, $start_min, $room_id, $token)
{
    return $token == createTokenYMD($start_year, $start_month, $start_day, $start_hour, $start_min, $room_id);
}

/* showInvalidRequest()
 * 
 * Displays an appropate message when an invalid request is detected,
 * which usually means that the requester is up to something...
 * 
 * Returns: Nothing
 */
function showInvalidRequest($day, $month, $year, $area)
{
    global $HTTP_REFERER;

    print_header($day, $month, $year, $area);
?>
  <H1>Invalid Request</H1>
  <P>
   Attempting to book a court without clicking through the calendar page is not allowed. 
  </P>
  <P>
   This incident has been reported. Please do not do this again.
  </P>
 </BODY>
</HTML>
<?php

    global $mrbs_admin_email;
    include_once "functions_mail_fixed.inc";
    $user = getUserName();
    if (! isset($user))
        $user = "<unknown>";
    $subject = "Invalid Court Booking Request";
    $body = $user . " sent an invalid request:\n\n";
    ob_start();
    var_dump($_SERVER);
    $body = $body . ob_get_clean();
    sendMail($mrbs_admin_email, $subject, $body);

}


?>
