
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
<title>Court Booking System - Woking Squash Club - username request</title>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
</head>

<body bgcolor="#ffffff">
<div align="center">
  <table width="680" border="2" align="center" cellpadding="2" cellspacing="0" bordercolor="#2a9044" bgcolor="#FFFfff">
    <tr>
      <td>
        <p align="center"><img src="banner.jpg" width="600" height="183"></p>
        <p align="center"><font color="#2a9044" face="Arial, Helvetica, sans-serif"><b><strong>COURT BOOKING SYSTEM</strong></b></font></p>
        <p align="center">
          <? 
if (!$HTTP_POST_VARS["name"] || !$HTTP_POST_VARS["email"] ) {
  $ErrStatus=1;
} else {
  $ErrStatus=0;
}
$name=$HTTP_POST_VARS["name"];
$email=$HTTP_POST_VARS["email"];
$other_details=$HTTP_POST_VARS["other_details"];
?>
          <!DOCTYPE HTML PUBLIC>
          <? echo $ErrStatus ? "<p><b><font face=\"Arial,Helvetica\" size=4><center>Request Submission Error</font></center></b></p>" :
                                                  "<p><b><font face=\"Arial,Helvetica\" size=4><center>Username Request</font></center></b></p>";
  ?>
          <? 
if (!$ErrStatus) {
  
$to = "wokingsquashclub@court-booking.co.uk";
$subject = "Court Booking System - Woking Squash Club - username request";
$body = 
"A username request had been received\n".
"Name:    $name\n".
"E-mail address:  $email\n".
"Other - details:    $other_details\n";

mail($to,$subject,$body,$headers);    
     
  echo "<p><font face=\"Arial,Helvetica\" b><center>Thank you for your Court Booking System username request.<br>
We shall contact you shortly by e-mail.  </center></p>
   ";

echo "<p><font face=\"Arial,Helvetica\"><b></font>";

    

} else {
  echo "<p><font face=\"Arial,Helvetica\"><center>There was a problem with the submitted information.<br>
  Please use your browser's  &quot;Back&quot; button and make sure that<br>
  the boxes for name and e-mail address have been filled in.<center></font></p>"; 
}
?>
</p>
        </font>      1
        <p align="center"><font size="2" face="Arial, Helvetica, sans-serif"><b><a href=http://www.court-booking.co.uk/WokingSquashClub>Return
      to Court Booking System</a></b></font></p>      </td>
    </tr>
  </table>
</div>
</body>
</html>