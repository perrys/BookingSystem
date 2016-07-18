<?php

# $Id: username.php,v 1 2007/12/17 Judi Hopcroft jupiterwill.co.uk

require_once "grab_globals.inc.php";
include "config.inc.php";
include "$dbsys.inc";
include "functions.inc";
include "version.inc";

#If we dont know the right date then make it up
if(!isset($day) or !isset($month) or !isset($year))
{
	$day   = date("d");
	$month = date("m");
	$year  = date("Y");
}
if(empty($area))
	$area = get_default_area();

print_header($day, $month, $year, $area);

echo "<h1>Court Booking System - username request</h1>\n";

echo " <p>If you are a member of $mrbs_name and have not yet been allocated a Court Booking System username, please complete the form below. <br />
A username and password will be e-mailed to you.</p>"; ?>

<form method="post" action="username_request.php" name="Username Request">
            <table border="0">
      <tr>
        <td width="170">*Name:</td>
        <td width="375"><input name="name" type="text" id="name" size="50" /></td>
            </tr>
      
      <tr>
        <td>*E-mail address: </td>
            <td class="style3"><input name="email" type="text" id="email" size="50" /></td>
            </tr>
      
      <tr>
        <td>* required fields </td>
		     <td>&nbsp;</td>
      </tr>
      <tr>      </tr>
      
      <tr>
        <td>&nbsp;</td>
        <td>&nbsp;</td>
      </tr>
      <tr>
        <td>Other comments:<br>
          (optional)</td>
              <td><textarea name="other_details" cols="45" rows="5" id="other_details"></textarea></td>
            </tr>
      </table>
                        <p align="center">
              <input type="submit" name="Submit" value="Submit">
              <input type="reset" name="Reset" value="Reset">
              <br>
            </p>
          </form>
<?php
include "trailer.inc";
?>
