<?php
/*****************************************************************************\
*									      *
*   File name       auth_db.inc						      *
*									      *
*   Description	    Authenticate users from a table in the MRBS database.     *
*									      *
*   Notes	    To use this authentication scheme, set in config.inc.php: *
*			$auth["type"]  = "db";				      *
*									      *
*   History								      *
*    2004/01/11 JFL Created this file					      *
*									      *
\*****************************************************************************/

// $Id: auth_db.inc,v 1.6.2.3 2005/08/20 09:46:44 jberanek Exp $

/* session_php.inc and session_cookie.inc will add a link to the user list
    in the logon box, if the value $user_list_link is set. */
$user_list_link = "edit_users.php";

/* authValidateUser($user, $pass)
 * 
 * Checks if the specified username/password pair are valid
 * 
 * $user  - The user name
 * $pass  - The password
 * 
 * Returns:
 *   0        - The pair are invalid or do not exist
 *   non-zero - The pair are valid
 */

function authValidateUser($user, $pass)
{
   global $tbl_users;

   $user=slashes($user);
   $user=strtolower($user);
   $pass = md5($pass);
   $id = sql_query1("select id from $tbl_users where name='$user' and password='$pass';");
   if ($id <= 0)
      return false;
   return $id;
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
