<?php
# $Id: day.php,v 1.38.2.3 2007/02/13 12:53:24 jberanek Exp $

require_once "grab_globals.inc.php";
include "config.inc.php";
include "functions.inc";
include "$dbsys.inc";
include "mrbs_auth.inc";
include "mincals.inc";

if (empty($debug_flag)) $debug_flag = 0;

#If we dont know the right date then make it up 
if (!isset($day) or !isset($month) or !isset($year))
{
	$day   = date("d");
	$month = date("m");
	$year  = date("Y");
} else {
# Make the date valid if day is more then number of days in month
	while (!checkdate(intval($month), intval($day), intval($year)))
		$day--;
}
if (empty($area))
	$area = get_default_area();


$spec = get_start_end($year, $month, $day);
$morningstarts         = $spec[0] ;
$morningstarts_minutes = $spec[1] ;
$eveningends           = $spec[2] ;
$eveningends_minutes   = $spec[3] ;


# print the page header
print_header($day, $month, $year, $area);

$format = "Gi";
if( $enable_periods ) {
	$format = "i";
	$resolution = 60;
	$morningstarts = 12;
	$morningstarts_minutes = 0;
	$eveningends = 12;
	$eveningends_minutes = count($periods)-1;

}

# ensure that $morningstarts_minutes defaults to zero if not set
if( empty( $morningstarts_minutes ) )
	$morningstarts_minutes=0;

# Define the start and end of each day in a way which is not affected by
# daylight saving...
# dst_change:
# -1 => no change
#  0 => entering DST
#  1 => leaving DST
$dst_change = is_dst($month,$day,$year);
$am7=mktime($morningstarts,$morningstarts_minutes,0,$month,$day,$year,is_dst($month,$day,$year,$morningstarts));
$pm7=mktime($eveningends,$eveningends_minutes,0,$month,$day,$year,is_dst($month,$day,$year,$eveningends));

if ( $pview != 1 ) {
   echo "<table width=\"100%\"><tr><td width=\"30%\">";

     echo "</td>\n";


   #Draw the three month calendars
   minicals($year, $month, $day, $area, '', 'day');
   echo "</tr></table>";
}

#y? are year, month and day of yesterday
#t? are year, month and day of tomorrow

$i= mktime(12,0,0,$month,$day-1,$year);
$yy = date("Y",$i);
$ym = date("m",$i);
$yd = date("d",$i);

$i= mktime(12,0,0,$month,$day+1,$year);
$ty = date("Y",$i);
$tm = date("m",$i);
$td = date("d",$i);

#We want to build an array containing all the data we want to show
#and then spit it out. 

#Get all appointments for today in the area that we care about
#Note: The predicate clause 'start_time <= ...' is an equivalent but simpler
#form of the original which had 3 BETWEEN parts. It selects all entries which
#occur on or cross the current day.
$sql = "SELECT TR.id, start_time, end_time, name, TE.id, type,
        TE.description, NS.entry_id as no_show
   FROM $tbl_entry TE
   INNER JOIN $tbl_room TR ON TE.room_id = TR.id 
   LEFT JOIN mrbs_noshow NS ON NS.entry_id = TE.id 
   WHERE area_id = $area
   AND start_time <= $pm7 AND end_time > $am7";

$res = sql_query($sql);
if (! $res) fatal_error(0, sql_error());
for ($i = 0; ($row = sql_row($res, $i)); $i++) {
	# Each row weve got here is an appointment.
	#Row[0] = Room ID
	#row[1] = start time
	#row[2] = end time
	#row[3] = short description
	#row[4] = id of this booking
	#row[5] = type (internal/external)
	#row[6] = description

	# $today is a map of the screen that will be displayed
	# It looks like:
	#     $today[Room ID][Time][id]
	#                          [color]
	#                          [data]
	#                          [long_descr]

	# Fill in the map for this meeting. Start at the meeting start time,
	# or the day start time, whichever is later. End one slot before the
	# meeting end time (since the next slot is for meetings which start then),
	# or at the last slot in the day, whichever is earlier.
	# Time is of the format HHMM without leading zeros.
	#
	# Note: int casts on database rows for max may be needed for PHP3.
	# Adjust the starting and ending times so that bookings which don't
	# start or end at a recognized time still appear.
	$start_t = max(round_t_down($row[1], $resolution, $am7), $am7);
	$end_t = min(round_t_up($row[2], $resolution, $am7) - $resolution, $pm7);
	for ($t = $start_t; $t <= $end_t; $t += $resolution)
	{
		$today[$row[0]][date($format,$t)]["id"]    = $row[4];
		$today[$row[0]][date($format,$t)]["color"] = $row[5];
		$today[$row[0]][date($format,$t)]["data"]  = "";
		$today[$row[0]][date($format,$t)]["long_descr"]  = "";
		$today[$row[0]][date($format,$t)]["no_show"]  = $row[7];
	}

	# Show the name of the booker in the first segment that the booking
	# happens in, or at the start of the day if it started before today.
	if ($row[1] < $am7)
	{
		$today[$row[0]][date($format,$am7)]["data"] = $row[3];
		$today[$row[0]][date($format,$am7)]["long_descr"] = $row[6];
	}
	else
	{
		$today[$row[0]][date($format,$start_t)]["data"] = $row[3];
		$today[$row[0]][date($format,$start_t)]["long_descr"] = $row[6];
	}
}

if ($debug_flag) 
{
	echo "<p>DEBUG:<pre>\n";
	echo "\$dst_change = $dst_change\n";
	echo "\$am7 = $am7 or " . date($format,$am7) . "\n";
	echo "\$pm7 = $pm7 or " . date($format,$pm7) . "\n";
	if (gettype($today) == "array")
	while (list($w_k, $w_v) = each($today))
		while (list($t_k, $t_v) = each($w_v))
			while (list($k_k, $k_v) = each($t_v))
				echo "d[$w_k][$t_k][$k_k] = '$k_v'\n";
	else echo "today is not an array!\n";
	echo "</pre><p>\n";
}

# We need to know what all the rooms area called, so we can show them all
# pull the data from the db and store it. Convienently we can print the room
# headings and capacities at the same time

$sql = "select room_name, capacity, id, description from $tbl_room where area_id=$area order by 1";

$res = sql_query($sql);

# It might be that there are no rooms defined for this area.
# If there are none then show an error and dont bother doing anything
# else
if (! $res) fatal_error(0, sql_error());
if (sql_count($res) == 0)
{
	echo "<h1>".get_vocab("no_rooms_for_area")."</h1>";
	sql_free($res);
}
else
{
	#Show current date
	$day_name = date("l jS F Y", $am7);
	echo "<h2 align=center>$day_name</h2>\n";

	if ( $pview != 1 ) {
		#Show Go to day before and after links
        $output = "<table width=\"100%\"><tr><td><a href=\"day.php?year=$yy&month=$ym&day=$yd&area=$area\">&lt;&lt;".get_vocab("daybefore")."</a></td>
        <td align=center><a href=\"day.php?area=$area\">".get_vocab("gototoday")."</a></td>
        <td align=right><a href=\"day.php?year=$ty&month=$tm&day=$td&area=$area\">".get_vocab("dayafter")."&gt;&gt;</a></td></tr></table>\n";
        print $output;
	}

	// Include the active cell content management routines.
	// Must be included before the beginnning of the main table.
	if ($javascript_cursor) // If authorized in config.inc.php, include the javascript cursor management.
            {
	    echo "<SCRIPT language=\"JavaScript\" type=\"text/javascript\" src=\"xbLib.js\"></SCRIPT>\n";
            echo "<SCRIPT language=\"JavaScript\">InitActiveCell("
               . ($show_plus_link ? "true" : "false") . ", "
               . "true, "
               . ((FALSE != $times_right_side) ? "true" : "false") . ", "
               . "\"$highlight_method\", "
               . "\"" . get_vocab("click_to_reserve") . "\""
               . ");</SCRIPT>\n";
            }

	#This is where we start displaying stuff

#Set stagger count starting point
$stagger = 1;


	echo "<table cellspacing=0 border=1 width=\"100%\">";
	echo "<tr><th width=\"1%\">".($enable_periods ? get_vocab("period") : get_vocab("time"))."</th>";

	$room_column_width = (int)(95 / sql_count($res));
	for ($i = 0; ($row = sql_row($res, $i)); $i++)
	{
        echo "<th width=\"$room_column_width%\">
            <a href=\"week.php?year=$year&month=$month&day=$day&area=$area&room=$row[2]\"
            title=\"" . get_vocab("viewweek") . " &#10;&#10;$row[3]\">"
            . htmlspecialchars($row[0]) . ($row[1] > 0 ? "($row[1])" : "") . "</a></th>";
		$rooms[] = $row[2];
	}

    # next line to display times on right side
    if ( FALSE != $times_right_side )
    {
        echo "<th width=\"1%\">". ( $enable_periods  ? get_vocab("period") : get_vocab("time") )
        ."</th>";
    }
    echo "</tr>\n";
  
	# URL for highlighting a time. Don't use REQUEST_URI or you will get
	# the timetohighlight parameter duplicated each time you click.
	$hilite_url="day.php?year=$year&month=$month&day=$day&area=$area&timetohighlight";

	# This is the main bit of the display
	# We loop through time and then the rooms we just got

	# if the today is a day which includes a DST change then use
	# the day after to generate timesteps through the day as this
	# will ensure a constant time step
	( $dst_change != -1 ) ? $j = 1 : $j = 0;
	
	$row_class = "even_row";
        $end_second = $eveningends * 3600 + $eveningends_minutes * 60;
	for (
		$t = mktime($morningstarts, $morningstarts_minutes, 0, $month, $day+$j, $year);
		$t <= mktime($eveningends, $eveningends_minutes, 0, $month, $day+$j, $year);
		$t += $resolution, $row_class = ($row_class == "even_row")?"odd_row":"even_row"
	)
	{
		# convert timestamps to HHMM format without leading zeros
		$time_t = date($format, $t);

		# Show the time linked to the URL for highlighting that time
		echo "<tr>";
		tdcell("red");
		if( $enable_periods ){
			$time_t_stripped = preg_replace( "/^0/", "", $time_t );
			echo "<a href=\"$hilite_url=$time_t\"  title=\""
            . get_vocab("highlight_line") . "\">"
            . $periods[$time_t_stripped] . "</a></td>\n";
		} else {
			echo "<a href=\"$hilite_url=$time_t\" title=\""
            . get_vocab("highlight_line") . "\">"
            . utf8_strftime(hour_min_format(),$t) . "</a></td>\n";
		}

		# Loop through the list of rooms we have for this area
		while (list($key, $room) = each($rooms))
		{
			if(isset($today[$room][$time_t]["id"]))
			{
				$id    = $today[$room][$time_t]["id"];
				$color = $today[$room][$time_t]["color"];
				$descr = htmlspecialchars($today[$room][$time_t]["data"]);
				$long_descr = htmlspecialchars($today[$room][$time_t]["long_descr"]);
				$no_show = $today[$room][$time_t]["no_show"];
			}
			else
			{
                                unset($no_show);
				unset($id);
			}

			# $c is the colour of the cell that the browser sees. White normally,
			# red if were hightlighting that line and a nice attractive green if the room is booked.
	
# We tell if its booked by $id having something in it
			if (isset($id)) 
				$c = isset($no_show) ? "noshow" : $color;
			elseif (isset($timetohighlight) && ($time_t == $timetohighlight))
				$c = "red";
			else
				$c = $row_class; # Use the default color class for the row.

			tdcell($c);

# If the room isnt booked then allow it to be booked
if ($stagger_set > 1) {
  $book = ($stagger - $room) %3;
  $day_seconds = $t % (24 * 3600);
  if ($day_seconds > ($end_second - (($stagger_set-1) * $resolution))) {
    $book = 1;
  }
} else {
  $book = 0;
}

			if(!isset($id))
			{
				$hour = date("H",$t);
				$minute  = date("i",$t);
				
#How far in advance is this slot
			$advance = mktime($hour, $minute, 0, $month, $day, $year) - mktime();

				if ( $pview != 1 ) {
					if ($javascript_cursor)
					{
						echo "<SCRIPT language=\"JavaScript\">\n<!--\n";
						echo "BeginActiveCell();\n";
						echo "// -->\n</SCRIPT>";
					}
					echo "<center>";

					if ( $book == 0) {
					
						if (($advance + (45 * 60) > 0) AND ($advance < $advance_limit * 24 * 60 * 60 ) )	
						{
                                                    $token = createTokenYMD($year, $month, $day, $hour, $minute, $room);
                                                    echo "<a href=\"edit_entry$suffix.php?area=$area&room=$room&hour=$hour&minute=$minute&year=$year&month=$month&day=$day&token=$token\"><img src=new.gif width=10 height=10 border=0></a>";
                                                }
					 else {echo " - " ;}
					
					
					} else {echo "&nbsp;";}



					echo "</center>";
					if ($javascript_cursor)
					{
						echo "<SCRIPT language=\"JavaScript\">\n<!--\n";
						echo "EndActiveCell();\n";
						echo "// -->\n</SCRIPT>";
					}
				} else echo '&nbsp;';
			}
			elseif ($descr != "")
			{
				



#if it is booked then show
				if (isset($no_show))
					echo "<span class='noshow'>NO SHOW</span>";
				echo " <a href=\"view_entry$suffix.php?id=$id&area=$area&day=$day&month=$month&year=$year\" title=\"$long_descr\">$descr</a>";
                                
			}
			else
				echo "&nbsp;\"&nbsp;";

			echo "</td>\n";
		}
        # next lines to display times on right side
        if ( FALSE != $times_right_side )
        {
            if( $enable_periods )
            {
                tdcell("red");
                $time_t_stripped = preg_replace( "/^0/", "", $time_t );
                echo "<a href=\"$hilite_url=$time_t\"  title=\""
                . get_vocab("highlight_line") . "\">"
                . $periods[$time_t_stripped] . "</a></td>\n";
            }
            else
            {
                tdcell("red");
		        echo "<a href=\"$hilite_url=$time_t\" title=\""
                . get_vocab("highlight_line") . "\">"
                . utf8_strftime(hour_min_format(),$t) . "</a></td>\n";
            }
        }

		echo "</tr>\n";


		reset($rooms);
		$stagger++;
	}

	echo "</table>\n";
    (isset($output)) ? print $output : '';
	show_colour_key();
}

include "trailer.inc";
?>
