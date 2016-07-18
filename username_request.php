<?php

# $Id: username.php,v 1 2007/12/17 Judi Hopcroft jupiterwill.co.uk

require_once "grab_globals.inc.php";
include "config.inc.php";
include "$dbsys.inc";
include "functions.inc";
include "version.inc";

print_header($day, $month, $year, $area);

?>

<div align="center">
  <table width="680" border="2" align="center" cellpadding="2" cellspacing="0" bordercolor="#2a9044" bgcolor="#FFFffF">
    <tr>
      <td>
         <p align="center">


<?php 
echo      "<p><b><font face=\"Arial,Helvetica\" size=4><center>Username Request</font></center></b></p>";
  
$to = $mrbs_admin_email;
$subject = "Court Booking System - $mrbs_name - username request";
$body = 
"A username request had been received\n".
"Name:    $name\n".
"E-mail address:  $email\n".
"Other - details:    $other_details\n";

mail($to,$subject,$body,$headers);    
     
echo "<p><font face=\"Arial,Helvetica\" b><center>Thank you for your Court Booking System username request.<br>
We shall contact you shortly by e-mail.  </center></p>";

echo "<p><font face=\"Arial,Helvetica\"><b></font>";
?>

</p>
      </font>       
        </td>
    </tr>
  </table>
</div>

<?php
include "trailer.inc";
?>