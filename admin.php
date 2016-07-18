<?php

# $Id: admin.php,v 1.16.2.1 2005/03/29 13:26:15 jberanek Exp $

require_once "grab_globals.inc.php";
include "config.inc.php";
include "functions.inc";
include "$dbsys.inc";
include "mrbs_auth.inc";

#If we dont know the right date then make it up 
if(!isset($day) or !isset($month) or !isset($year))
{
	$day   = date("d");
	$month = date("m");
	$year  = date("Y");
}

if (empty($area))
{
    $area = get_default_area();
}

if(!getAuthorised(2))
{
	showAccessDenied($day, $month, $year, $area);
	exit();
}

print_header($day, $month, $year, isset($area) ? $area : "");

// If area is set but area name is not known, get the name.
if (isset($area))
{
	if (empty($area_name))
	{
		$res = sql_query("select area_name from $tbl_area where id=$area");
    	if (! $res) fatal_error(0, sql_error());
		if (sql_count($res) == 1)
		{
			$row = sql_row($res, 0);
			$area_name = $row[0];
		}
		sql_free($res);
	} else {
		$area_name = unslashes($area_name);
	}
}
?>


<div align="center">
  <div id="container">
    <table width="680" border="0" align="center" cellpadding="2" cellspacing="0">
      <tr>
        <td><font face="Kristen ITC">&nbsp;
          </font>        <h1 class="subheading">Admin page </h1>
		  
		   <h2>Notes for Admin</h2>
      
          <p><strong>Making repeat bookings</strong><br> 
            To make up to <?php echo $max_rep_entrys ?> bookings at once, use the 
            <strong><A href="<?php echo $url_base ?>/edit_entry_admin.php">extended version</A></strong> of the &quot;Add Entry&quot; screen: <br>
          This link should be given to admin users only.</p>
		  
		      <p><strong>Adding members</strong><br> 
            Click &quot;User list&quot; and then &quot;Add a new user&quot;</p>
			
          <p><strong>E-mail confirmations </strong><br>
          When a booking is made, amended or deleted, an e-mail confirmation is automatically<br>
            sent to both the booker and <a href="mailto:<?php echo $mrbs_admin_email ?>"><strong><?php echo $mrbs_admin_email ?></strong></a>.<br>
          If you would like additional adminstrators added to the distribution of these e-mails,<br>
          please contact <a href="mailto:webmaster@etctennis.org"><strong>webmaster@court-booking.co.uk</strong></a>.</p>
		  		  
		        <p><strong>Deletions Report</strong><br> 
              A report of <strong><A href="<?php echo $url_base ?>/report_deletions.php">deleted entries</A></strong> is available.</p>
                      
                        <p><strong>Daylight Saving time changes</strong><br />
Run  <strong><a href="<?php echo $url_base ?>/daylight.php">this adjustment</a></strong> as soon as possible after a daylight saving time change.</p>

		        <p>&nbsp;</p>
          <h2>Instructions for members </h2>
          <p>Using the system should be self explanatory.<br>
          When introducing the system to members we suggest you use a wording along the lines of:</p>
          <div class="instructions"><?php echo $mrbs_name ?> have introduced a new <strong><A href="<?php echo $url_base ?> ">online court booking system</A></strong>.<br>
           You can access the court booking system from the club website.&nbsp;
            <p>To book a court,   simply click on the time slot you require.</p>
            <p>Your login   details are:<br>
         Name - <br>
            Password   - </p>
            <p>To change your password or   e-mail address, click the edit button next to your name on the 
              <strong><A href="<?php echo $url_base ?>/edit_users.php">user list</A></strong>.</p>
            <p>If   you have any questions, please contact <a href="mailto:<?php echo $mrbs_admin_email ?>"><strong><?php echo $mrbs_admin_email ?></strong></a>.</p>
          </div>
          <p>There is a help page on the site with some additional information for members. </p>
          <p>&nbsp;</p>
         </td>
      </tr>
    </table>
  </div>
</div>





<?php include "trailer.inc" ?>
