<?php
/*
FlightPath was originally designed and programmed by the 
University of Louisiana at Monroe. The original source is 
copyright (C) 2011-present by the University of Louisiana at Monroe.

FlightPath is considered "open source" under the 
GNU General Public License, version 3 or any later version. 
This covers any related files and documentation packaged with 
FlightPath. 

The license is defined in full here: http://www.gnu.org/licenses/gpl.html,
and reproduced in the LICENSE.txt file.

You may modify FlightPath's source code, but this copyright and license
notice must not be modified, and must be included with the source code.
------------------------------
*/


/*
This is the Help system for FlightPath.
I'm kind of iffy as to whether or not this is even really
needed.
*/

session_start();
header("Cache-control: private");

require_once("bootstrap.inc");


$db = new DatabaseHandler();
$screen = new AdvisingScreen("", null, "not_advising");

// What page is the user trying to see?
$i = trim(addslashes($_GET["i"])) * 1;

if ($i < 1)
{
	$i = 1;  // by default, the main page.
}


// Okay, get the page from the database...
$help_page = $db->get_help_page($i);

$pC = "";

$pC .= "<div style='font-size: 16pt; font-weight: bold;'>FlightPath Help - " . $help_page["title"] . "</div>";
$body = trim($help_page["body"]);
//$body = $screen->convertBBCodeToHTML($body);

$pC .= "<div align='center'><div style='padding-top: 20px; width:90%; text-align: left;'>$body</div></div>";


$screen->page_content = $pC;
$screen->page_has_search = false;
$screen->page_is_popup = true;
$screen->page_title = "FlightPath Help - " . $help_page["title"];
// send to the browser
$screen->output_to_browser();

$db->add_to_log("help","$i,{$help_page["title"]}");



die;



?>