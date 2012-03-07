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
$screen = new AdvisingScreen("", null, "notAdvising");

// What page is the user trying to see?
$i = trim(addslashes($_GET["i"])) * 1;

if ($i < 1)
{
	$i = 1;  // by default, the main page.
}


// Okay, get the page from the database...
$helpPage = $db->getHelpPage($i);

$pC = "";

$pC .= "<div style='font-size: 16pt; font-weight: bold;'>FlightPath Help - " . $helpPage["title"] . "</div>";
$body = trim($helpPage["body"]);
//$body = $screen->convertBBCodeToHTML($body);

$pC .= "<div align='center'><div style='padding-top: 20px; width:90%; text-align: left;'>$body</div></div>";


$screen->pageContent = $pC;
$screen->pageHasSearch = false;
$screen->pageIsPopup = true;
$screen->pageTitle = "FlightPath Help - " . $helpPage["title"];
// send to the browser
$screen->outputToBrowser();

$db->addToLog("help","$i,{$helpPage["title"]}");



die;



?>