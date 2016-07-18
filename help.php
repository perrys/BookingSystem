<?php

# $Id: help.php,v 1.12.2.1 2007/01/24 10:40:16 jberanek Exp $

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

echo "<H3>" . get_vocab("help") . "</H3>\n";
echo get_vocab("please_contact") . '<a href="mailto:' . $mrbs_admin_email
	. '">' . $mrbs_admin
	. "</a> " . get_vocab("for_any_questions") . "\n";

echo "<p><b>How do I login?</b><BR>
Use the individual username and password supplied by the club administrator.<BR>
If these have not yet been received, complete the <a href=\"username.php\">username request form</a></p>";

echo "<p><b>Why can't I delete/alter a booking?</b><BR>
In order to delete or alter a booking, you must be logged in as the same person that made the booking. <BR>Contact <a href=\"mailto:$mrbs_admin_email\">$mrbs_admin</a> or the person who initially made the booking to have it deleted or changed.</p>";

echo "<p><b>What happens if multiple people book the same court at the same time?</b><br>
The short answer is: The first person to click on the <b>Submit</b> button wins.</p>";



echo "<p><b>How does the system work and who wrote it?</b><br>
The <a href=\"http://sourceforge.net/project/?group_id=5113\" target=\"_blank\">Meeting Room Booking System</a>
is open source software that is distributed under the Gnu Public License(GPL). MRBS was written by Daniel Gardner and John Beranek, and has been converted to a court booking system by <a href=\"http://www.jupiterwill.co.uk\" target=\"_blank\">Jupiterwill.</a></p>

<p>The system is written mostly in <a href=\"http://www.php.net\" target=\"_blank\">PHP</a>, 
which is an open source programming language that can be embedded in web 
pages similar in concept to Microsoft active server pages.  PHP is especially 
good at accessing databases.</p>

<p>The database used for the system is either <a href=\"http://www.mysql.com\" target=\"_blank\">MySQL</a>
or <a href=\"http://www.postgresql.org\" target=\"_blank\">PostgreSQL</a>. MySQL is a very fast, multi-threaded, multi-user and robust SQL (Structured Query Language) database server that is also GPL. PostgreSQL is a full-featured multi-user open source Object Relational SQL database server.</p>

<p>The system will run on multiple platforms, including the PC architecture using the <a href=\"http://www.linux.com\" target=\"_blank\">Linux</a> operating system. Linux, is an open source, unix-like operating system.</p>

<p>The web server being used is yet another piece of open source software.  The <a href=\"http://www.apache.org\" target=\"_blank\">Apache</a> web server is the world's most popular web server.</p>";

include "trailer.inc";
?>
