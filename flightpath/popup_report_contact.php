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


session_start();
header("Cache-control: private"); //This is important for IE6 (according to a site I went to)
/*
This script will allow the user to report an error or bug to the
flightpath team.
*/

require_once("bootstrap.inc");

$screen = new AdvisingScreen("",null,"notAdvising");
$db = new DatabaseHandler();

if ($_SESSION["fpLoggedIn"] != true)
{
  displayPleaseLogin();
  die;
}



$action = trim($_POST["performAction"]);
if ($action == "")
{
	displayErrorForm();
}

if ($action == "send")
{
	performSend();
}

die;

function displayPleaseLogin() {
  
  // Check for hooks...
  if (function_exists("popup_report_contact_displayPleaseLogin")) {
    return call_user_func("popup_report_contact_displayPleaseLogin");
  }  
  
  $screen = new AdvisingScreen();
  $screen->pageIsPopup = TRUE;

	$tabArray[0]["title"] = "Contact";
	$tabArray[0]["active"] = TRUE;
	$screen->pageTabs = $screen->drawTabs($tabArray);
	
	$pageContent .= $screen->drawCurvedTitle("Please login first...");
	$pageContent .= "For security reasons, you must be signed in to FlightPath to use
			this contact system.  <br><br>If you are having trouble
			signing on FlightPath, 
			visit <a href='javascript: popupHelpWindow(\"help.php?i=5\");'>this help page</a>,
			or call the Computing Center help desk
			at 342-3333.  Thank you.";
	$screen->pageHideReportError = TRUE;
	$pageContent .= "<script type='text/javascript'>
						var csid=0;";
	$pageContent .= $screen->getJS_popupHelpWindow();
	$pageContent .= "</script>";

	$screen->pageContent = $pageContent;
	$screen->outputToBrowser();
	
}

function performSend()
{
  global $db;
  
  // Check for hooks...
  if (function_exists("popup_report_contact_performSend")) {
    return call_user_func("popup_report_contact_performSend");
  }  
  

	$screen = new AdvisingScreen();
	
	// This function will email out the error reporting thing.
	$onscreen = addslashes(trim($_POST["onscreen"]));
	$cwid = $_SESSION["fpUserID"];
	$usertype = $_SESSION["fpUserType"];
	if ($usertype == "student")
	{
		$username = $db->getStudentName($cwid, false);
	} else {
		$username = $db->getFacultyName($cwid, false);
	}
	
	$da_possible_student = $_SESSION["advisingStudentID"];
	$problem = trim($_POST["problem"]);

	if ($problem == "")
	{
		displayErrorForm();
		die;
	}
	
	// Create datetime of when this entry was inserted (central time zone)
	$datetime= date("Y-m-d H:i:s", strtotime("now"));

	$headers = "From: FlightPath-noreply@ulm.edu\n";
	$subject = "FLIGHTPATH BUG REPORT - $onscreen ";
	$msg = "";
	$msg .= "You have received a new bug report on $datetime.\n";
	$msg .= "Name: $username  CWID: $cwid  Usertype: $usertype \n\n";
	$msg .= "On screen: $onscreen  \n";
	$msg .= "Possible Student: $da_possible_student  \n";
	$msg .= "Problem: \n $problem \n\n";
	$msg .= "------------------------------------------------ \n";

	$themd5 = md5($username . $cwid . $problem . $usertype . $onscreen);

	if ($_SESSION["da_error_report_md5"] != $themd5)
	{  // Helps stop people from resubmitting over and over again
		// (by hitting refresh, or by malicious intent)..

		$msg = addslashes($msg);
		
		mail($GLOBALS["fpSystemSettings"]["contactEmailAddress"],$subject,$msg,$headers);

	} 

	$_SESSION["da_error_report_md5"] = $themd5;

	$screen->pageIsPopup = TRUE;
	$tabArray[0]["title"] = "Contact";
	$tabArray[0]["active"] = TRUE;
	$screen->pageHideReportError = TRUE;
	$screen->pageTabs = $screen->drawTabs($tabArray);
	$pC = "";

	$pC .= $screen->drawCurvedTitle("Contact the FlightPath Production Team");
	$pC .= "Thank you very much for your submission!  Your message
			has been forwarded to the FlightPath production team.
			<br><br>
			You may now close this window.";


	//$pageContent = $pC;
	$screen->pageContent = $pC;
	
	// send to the browser
	$screen->outputToBrowser();
	

} // doPerformSend



function displayErrorForm()
{

  // Check for hooks...
  if (function_exists("popup_report_contact_displayErrorForm")) {
    return call_user_func("popup_report_contact_displayErrorForm");
  }  
  
  
	$screen = new AdvisingScreen();
	
	$screen->pageIsPopup = TRUE;
	$tabArray[0]["title"] = "Contact";
	$tabArray[0]["active"] = TRUE;
	$screen->pageHideReportError = TRUE;
	$screen->pageTabs = $screen->drawTabs($tabArray);

	$pC = "";
	
	$pC .= $screen->drawCurvedTitle("Contact the FlightPath Production Team");
	$pC .= "<div style='margin-top: 5px;' class='elevenpt'>
			If you've noticed an error, have a suggestion, or just wish to 
			contact the developers of FlightPath,
			please fill out the form below.  
			
			
			Thank you!
			</div>
			<form name='mainform' action='popup_report_contact.php' method='POST'>
			<input type='hidden' name='performAction' value='send'>
			
			
			
			<div style='border: 1px solid lightgray; padding: 10px; margin: 20px;'>
			
			<select name='onscreen'>
					<option>- Please select a category -</option>
					<option value=''>-----------------------</option>
					<option>Sign On</option>
					<option>Main Tab</option>
					<option>Advisees</option>
					<option>Advisee Search</option>
					<option>Advising History</option>
					<option>View Tab</option>
					<option>Comments</option>
					<option>What If? Tab</option>
					<option>Help window</option>
					<option>Course description window</option>
					<option>Course selection window</option>
					<option>Substitution window</option>
					<option>Other / Not Applicable</option>
			</select>
			<br><br>
			<div class='tenpt'>
			Please enter your message below...
			</div>
			
			 <textarea name='problem' cols='40' rows='5'></textarea>
			 <br><br>
			 <input type='submit' value='Submit'>
			 
			 </div>
			 
			 
			 
			 </form>
			 ";

	$screen->pageContent = $pC;
	// send to the browser
	$screen->pageHideReportError = TRUE;
	$screen->outputToBrowser();
	
}


?>