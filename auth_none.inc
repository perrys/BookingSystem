<?php

/* $Id: auth_none.inc,v 1.1.4.2 2005/03/29 13:26:15 jberanek Exp $
 *
 * Dummy authentication scheme, that accepts any user.
 * Useful for using mrbs without authentication,
 * or in combination with a session scheme that already enforces
 * its own authentication. For example that in IIS.
 *
 * To use this authentication scheme set the following
 * things in config.inc.php:
 *
 *      $auth["type"]    = "none";
 *      $auth["session"] = your choice
 *
 * Then, you may configure admin users:
 *
 * $auth["admin"][] = "nt_username1";
 * $auth["admin"][] = "nt_username2";
 *
 * See AUTHENTICATION  for more informations.
 */

/** 
 * Checks if the specified username/password pair are valid.
 *
 * For this authentication scheme always validates.
 * 
 * @param   string  $user   The user name
 * @param   string  $pass   The password
 * @return  int             non-zero - Always valid
 */
function authValidateUser($user, $pass)
{
    return 1;
}

/* authGetUserLevel($user)
 * 
 * Determines the users access level
 * 
 * $user - The user name
 *
 * Returns:
 *   The users access level
 */
function authGetUserLevel($user, $lev1_admin)
{
    // User not logged in, user level '0'
    if(!isset($user))
	return 0;
	
    // Check if the user is can modify
    for($i = 0; isset($lev1_admin[$i]); $i++)
    {
	if(strcasecmp($user, $lev1_admin[$i]) == 0)
	    return 2;
    }
	
    // Everybody else is access level '1'
    return 1;
}

?>
