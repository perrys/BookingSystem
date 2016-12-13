<?php
# $Id: trailer.inc,v 1.10.2.1 2006/10/09 15:54:00 jberanek Exp $

if ( $pview != 1 ) {

echo "<P><HR><B>".get_vocab("viewday").":</B>\n";

if(!isset($year))
	$year = strftime("%Y");

if(!isset($month))
	$month = strftime("%m");

if(!isset($day))
	$day = strftime("%d");

if (empty($area))
	$params = "";
else
	$params = "&area=$area";

for($i = -6; $i <= 7; $i++)
{
	$ctime = mktime(0, 0, 0, $month, $day + $i, $year);

	$str = utf8_strftime(empty($dateformat)? "%b %d" : "%d %b", $ctime);

	$cyear  = date("Y", $ctime);
	$cmonth = date("m", $ctime);
	$cday   = date("d", $ctime);
	if ($i != -6) echo " | ";
	if ($i == 0) echo '<b>[ ';
	echo "<a href=\"day.php?year=$cyear&month=$cmonth&day=$cday$params\">$str</a>\n";
	if ($i == 0) echo ']</b> ';
}

echo "<BR><B>".get_vocab("viewweek").":</B>\n";

if (!empty($room)) $params .= "&room=$room";

$ctime = mktime(0, 0, 0, $month, $day, $year);
# How many days to skip back to first day of week:
$skipback = (date("w", $ctime) - $weekstarts + 7) % 7;
	
for ($i = -4; $i <= 4; $i++)
{
	$ctime = mktime(0, 0, 0, $month, $day + 7 * $i - $skipback, $year);

	$cweek  = date("W", $ctime);
	$cday   = date("d", $ctime);
	$cmonth = date("m", $ctime);
	$cyear  = date("Y", $ctime);
	if ($i != -4) echo " | ";

	if ($view_week_number)
	{
		$str = $cweek;
	}
	else
	{
		$str = utf8_strftime(empty($dateformat)? "%b %d" : "%d %b", $ctime);
	}
	if ($i == 0) echo '<b>[ ';
	echo "<a href=\"week.php?year=$cyear&month=$cmonth&day=$cday$params\">$str</a>\n";
	if ($i == 0) echo ']</b> ';
}

echo "<HR>";
echo '<p><center><a href="' . basename($PHP_SELF) . '?' . $QUERY_STRING . '&pview=1">' . get_vocab("ppreview") . '</a></center><p>';

}
?>

<p><center><a href="http://www.court-booking.co.uk" target="_blank">Court Booking System by Jupiterwill</a></center></p>

<p><center>Version 2016.12.13-1</center></p>

<HR>
</BODY>
</HTML>