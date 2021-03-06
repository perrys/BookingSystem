<?php 
/********************************************************************************************\
*                                                                                            *
*   File name       session_remote_user.inc                                                  *
*                                                                                            *
*   Description     Get user identity/password using the REMOTE_USER environment variable.   *
*                   Both identity and password equal the value of REMOTE_USER.               *
*                                                                                            *
*   Notes           To use this session scheme, set in config.inc.php:                       *
*                     $auth['session']  = 'remote_user';                                     *
*                     $auth['type'] = 'none';
*                                                                                            *
*                   If you want to display a logout link, set in config.inc.php:             *
*                     $auth['remote_user']['logout_link'] = '/logout/link.html';             *
*                                                                                            *
*   History                                                                                  *
*    2006-01-04 Bjorn.Wiberg@its.uu.se  Created based on session_http.inc                    *
*    2006-02-16 Bjorn.Wiberg@its.uu.se  Made logout link a configuration item                *
*                                                                                            *
\*********************************************************************************************/

// $Id: session_remote_user.inc,v 1.1.2.1 2006/04/26 10:01:43 jberanek Exp $

/* authGet()
 * 
 * Request the user name/password
 * 
 * Returns: Nothing
 */
function authGet()
{
  // User is expected to already be authenticated by the web server, so do nothing
}

function getUserName()
{
  if ((!isset($_SERVER['REMOTE_USER'])) || (!is_string($_SERVER['REMOTE_USER'])) || (empty($_SERVER['REMOTE_USER']))) {
    return NULL;
  } else {
    return $_SERVER['REMOTE_USER'];
  }
}

// Print the logon entry on the top banner.
function PrintLogonBox()
{
	global $user_list_link;
        global $auth;
  
	$user = getUserName();

	if (isset($user))
	{
	// words 'you are xxxx' becomes a link to the
        // report page with only entries created by xxx. Past entries are not
        // displayed but this can be changed
       	$search_string = "report_user.php?From_day=$day&From_month=$month&".
          "From_year=$year&To_day=1&To_month=12&To_year=2030&areamatch=&".
          "roommatch=&namematch=&descrmatch=&summarize=1&sortby=r&display=d&".
          "sumby=d&creatormatch=$user"; ?>

    <TD CLASS="banner" BGCOLOR="#C0E0FF" ALIGN=CENTER>
      <A name="logonBox" href="<?php echo "$search_string\" title=\""
         . get_vocab('show_my_entries') . "\">" . get_vocab('you_are')." "
         .$user ?></A><br>
<?php if (isset($user_list_link)) print "	  <br>\n	  " .
	    "<A href='$user_list_link'>" . get_vocab('user_list') . "</A><br>\n" ;
?>

<?php
// Retrieve logout link from configuration, if specified
if (isset($auth['remote_user']['logout_link']) && is_string($auth['remote_user']['logout_link']) && (!empty($auth['remote_user']['logout_link']))) {
  print '<A HREF="' . $auth['remote_user']['logout_link'] .'">' . get_vocab('logoff') . "</A><BR>\n";
}
?>

    </TD>
<?php
    }
    else
    {
?>
    </TABLE>
    <H1>Error, REMOTE_USER was not set when it should have been</H1>
<?php
    exit;
    }
}

?>
