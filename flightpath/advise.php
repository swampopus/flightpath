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



ini_set("session.bug_compat_warn", false);  // prevent a particular irrelevant warning from showing.
/*
This is a "basic advising" screen for FlightPath.
*/
session_start();
header("Cache-control: private");

require_once("bootstrap.inc");

if ($_SESSION["fpLoggedIn"] != true && $_REQUEST["blankDegreeID"] == "")
{ // If not logged in, show the login screen.
  header("Location: main.php");
  die;
}

$boolLoogedIn = true;
// We do this, because we might be allowed in if we are only
// looking at blank degree plans.
if ($_SESSION["fpLoggedIn"] != true)
{$boolLoggedIn = false;}


// Not essential.  This will help me keep track of
// details later, possibly....
$GLOBALS["adminNotesArray"] = array();

$windowMode = trim(addslashes($_REQUEST["windowMode"]));


/////////////////////////////////////
///  Are we trying to save the draft
///  from a tab change?
/////////////////////////////////////
$fp = new FlightPath();
$fp->processRequestSaveDraft();




if ($_REQUEST["clearSession"] == "yes")
{
  $tempScreen = new AdvisingScreen();
  $tempScreen->clearVariables();
}

$degreePlan = $student = $fp = "";

if ($windowMode == "history" || $_REQUEST["performAction"] == "history")
{
  displayAdviseeHistory();
}

if ($windowMode == "popup")
{
  displayPopup();
}

if ($windowMode == "" || $windowMode == "screen")
{
  displayScreen();
}

if ($windowMode == "summary")
{
  displayPopupAdvisingSummary();
}


die;



function displayPopupAdvisingSummary()
{
  // Check for hooks...
  if (function_exists("advise_displayPopupAdvisingSummary")) {
    return call_user_func("advise_displayPopupAdvisingSummary");
  }
  
  
  $advisingSessionID = trim(addslashes($_GET["advisingSessionID"]));

  $screen = new AdvisingScreen("",null,"notAdvising");
  $screen->boolPrint = true;
  $db = new DatabaseHandler();

  $res = $db->dbQuery("SELECT * FROM advising_sessions
							       WHERE `advising_session_id`='$advisingSessionID' ");
  if ($db->dbNumRows($res) > 0)
  {
    $cur = $db->dbFetchArray($res);
    extract($cur, 3, "db");
  }
  $dt = date("F jS, Y, g:ia",strtotime($db_datetime));


  $db = new DatabaseHandler();
  $tempCourse = new Course();
  $tempCourse->termID = $db_term_id;
  $term = $tempCourse->getTermDescription();
  $degreePlan = new DegreePlan();
  $degreePlan->degreeID = $db_degree_id;
  $degreePlan->loadDescriptiveData();
  $degreeTitle = $degreePlan->getTitle(true);

  $student = new Student($db_student_id, $db);

  $whatIfMessage = "";
  if ($db_is_whatif == "1")
  {
    $officialDegreePlan = $student->getDegreePlan(false, true);
    $officialDegreePlan->loadDescriptiveData();
    $officialDegreeTitle = $officialDegreePlan->getTitle(true);

    $whatIfMessage = "<b>Note:</b>
							This advising was saved using <b>What If</b> mode
							for the $degreeTitle major.  According to {$GLOBALS["fpSystemSettings"]["schoolInitials"]} records,
							the student's official major is <u>$officialDegreeTitle</u>.
							";
  }


  $w = ($screen->pageIsMobile) ? "100%" : "500";
  
  $pC = "";
  $pC .= "<table width='$w'><td valign='top'>";
  
  if ($screen->pageIsMobile) {

  }
  else {
  
    $pC .= "
			<table class='tenpt' border='0' width='100%' cellpadding='3' cellspacing='0' style='border-width: 2px; border-style: solid; border-color: black;'>
			 <tr height='7'>
			 	<td> </td>
			 </tr>
			 <tr>
			  <td valign='top' width='15%'>Student:</td>
			  <td valign='top' width='40%'>" . $db->getStudentName($db_student_id, false) . " ($db_student_id)</td>
			  <td valign='top' rowspan='3'>
			  	<table width='100%' cellpadding='0' cellspacing='0'>
				<tr height='20'>
				 <td width='100%' valign='bottom'><hr noshade size='1' width='100%' color='black' style='margin: 0px;'></td>
				</tr>
				<tr height='20'>
				 <td width='100%' valign='top' align='right'><span style='font-size: 8pt;'>Student signature</span></td>
				</tr>

				<tr height='20'>
				 <td width='100%' valign='bottom'><hr noshade size='1' width='100%' color='black' style='margin: 0px;'></td>
				</tr>
				<tr>
				 <td width='100%' valign='bottom' align='right'><span style='font-size: 8pt;'>Advisor signature</span></td>
				</tr>
			 <tr height='7'>
			 	<td> </td>
			 </tr>
	
				
				</table> ";
  }
  
  $pC .= "
			  </td>
			 </tr>
			 <tr>
			  <td valign='top' width='10%'>Advisor:</td>
			  <td valign='top'>" . $db->getFacultyName($db_faculty_id, false) . "</td>

			 </tr>
			 <tr>
			  <td valign='top'>Term:</td>
			  <td valign='top'>$term</td>

			 </tr>
			 ";
  if (!$screen->pageIsMobile) {
    $pC .= "
			 <tr>
			  <td valign='top' colspan='2'>
			  Alternate Term: _____________________
			  </td>
			 </tr>";
  }
			 
	$pC .= "	 
			 <tr>
			 	<td valign='top' colspan='4'>
			 	";

  if ($degreeTitle != "")
  {
    $pC .= "Major: $degreeTitle";
  }

  $pC .= "
			 </tr>
			 </table>
			 <div class='tenpt'><i>Submitted on $dt.</i></div>
			 <div class='tenpt'>$whatIfMessage</div>
			 <br>
		";

  $pC .= $screen->drawCurvedTitle("Advised Courses");

  $fp = new FlightPath($student,$degreePlan, $db);

  $fp->loadAdvisingSessionFromDatabase("","",false,false,$advisingSessionID);

  $pC .= "<table border='0' cellpadding='3'>
			<tr>
				<td class='tenpt' valign='top' width='25%'>
					<b>Course</b>
				</td>
				<td class='tenpt' valign='top' width='70%'>
					<b>Title</b>
				</td>
				<td class='tenpt' valign='top'>
					<b>Hours</b>
				</td>
			</tr>";



  // Get a courseList of all the courses which are set as advised to take.
  $advisedCoursesList = $fp->courseListAdvisedCourses;
  $advisedCoursesList->loadCourseDescriptiveData();
  $advisedCoursesList->sortAlphabeticalOrder();
  $advisedCoursesList->resetCounter();
  while ($advisedCoursesList->hasMore())
  {
    $course = $advisedCoursesList->getNext();
    // set the catalogYear from the termID.
    $course->termID = $db_term_id;
    $course->setCatalogYearFromTermID();
    $course->loadDescriptiveData(false);

    $pC .= "<tr>
					<td class='tenpt' valign='top'>
					$course->subjectID $course->courseNum
					</td>
					<td class='tenpt' valign='top'>
					$course->title
					</td>
					<td class='tenpt' valign='top' align='center'>
					" . $course->getHours() . " 
					</td>
					
				</tr>
			";
  }

  $pC .= "</table>
			<div align='right' class='tenpt' style='padding-top: 10px; padding-right: 15px;'>
			  <b>Total advised hours: &nbsp; " . $advisedCoursesList->countHours() . "</b>
			</div>
			";


  if (!$screen->pageIsMobile) {
    $pC .= "<br>";
    $pC .= $screen->drawCurvedTitle("Alternate Courses");
    $pC .= "<div class='tenpt'>
  			You may use this space to write in alternate courses 
  			the student should enroll in, in the event that an 
  			advised course is full or unavailable.
  			<br><br>
  			___________________________________________________________________ <br><br>
  			___________________________________________________________________
  			</div>	";
  }

  $pC .= "</table>";


  $screen->pageTitle = $db->getStudentName($db_student_id, false) . " ($db_student_id) $term Advising Summary";
  $screen->pageContent = $pC;
  $screen->outputToBrowser();



}


function displayPopupToolbox($screen, $performAction2)
{
  
  // Check for hooks...
  if (function_exists("advise_displayPopupToolbox")) {
    return call_user_func("advise_displayPopupToolbox", $screen, $performAction2);
  }
  
  
  global $degreePlan, $student, $fp;

  $pageContent = "";

  if ($_SESSION["fpCanSubstitute"] != true)
  {
    die("Your user type is not allowed to access this function.");
  }

  $pageContent .= $screen->getJavascriptCode();
  $csid = $GLOBALS["currentStudentID"];

  if ($performAction2 == "substitutions")
  {
    // Display the substitution management screen.
    $pageContent .= $screen->displayToolboxSubstitutions();


    // Create the tabs for the page...
    $tabArray = array();
    $tabArray[0]["title"] = "Transfers";
    $tabArray[0]["onClick"] = 'window.location="advise.php?windowMode=popup&performAction=toolbox&performAction2=transfers&currentStudentID=' . $csid . '";';

    $tabArray[1]["title"] = "Substitutions";
    $tabArray[1]["onClick"] = 'window.location="advise.php?windowMode=popup&performAction=toolbox&performAction2=substitutions&currentStudentID=' . $csid . '";';
    $tabArray[1]["active"] = true;

    $tabArray[2]["title"] = "Moved";
    $tabArray[2]["onClick"] = 'window.location="advise.php?windowMode=popup&performAction=toolbox&performAction2=moved&currentStudentID=' . $csid . '";';

    $tabArray[3]["title"] = "Courses";
    $tabArray[3]["onClick"] = 'window.location="advise.php?windowMode=popup&performAction=toolbox&performAction2=courses&order=name&currentStudentID=' . $csid . '";';


    $screen->pageTabs = $screen->drawTabs($tabArray);
  }

  if ($performAction2 == "courses")
  {
    $pageContent .= $screen->displayToolboxCourses();

    // Create the tabs for the page...
    $tabArray = array();
    $tabArray[0]["title"] = "Transfers";
    $tabArray[0]["onClick"] = 'window.location="advise.php?windowMode=popup&performAction=toolbox&performAction2=transfers&currentStudentID=' . $csid . '";';

    $tabArray[1]["title"] = "Substitutions";
    $tabArray[1]["onClick"] = 'window.location="advise.php?windowMode=popup&performAction=toolbox&performAction2=substitutions&currentStudentID=' . $csid . '";';

    $tabArray[2]["title"] = "Moved";
    $tabArray[2]["onClick"] = 'window.location="advise.php?windowMode=popup&performAction=toolbox&performAction2=moved&currentStudentID=' . $csid . '";';

    $tabArray[3]["title"] = "Courses";
    $tabArray[3]["onClick"] = 'window.location="advise.php?windowMode=popup&performAction=toolbox&performAction2=courses&order=name&currentStudentID=' . $csid . '";';
    $tabArray[3]["active"] = true;

    $screen->pageTabs = $screen->drawTabs($tabArray);

  }



  if ($performAction2 == "" || $performAction2 == "transfers")
  {
    // Display the transfer eqv management system.
    $pageContent .= $screen->displayToolboxTransfers();

    // Create the tabs for the page...
    $tabArray = array();
    $tabArray[0]["title"] = "Transfers";
    $tabArray[0]["onClick"] = 'window.location="advise.php?windowMode=popup&performAction=toolbox&performAction2=transfers&currentStudentID=' . $csid . '";';
    $tabArray[0]["active"] = true;

    $tabArray[1]["title"] = "Substitutions";
    $tabArray[1]["onClick"] = 'window.location="advise.php?windowMode=popup&performAction=toolbox&performAction2=substitutions&currentStudentID=' . $csid . '";';

    $tabArray[2]["title"] = "Moved";
    $tabArray[2]["onClick"] = 'window.location="advise.php?windowMode=popup&performAction=toolbox&performAction2=moved&currentStudentID=' . $csid . '";';

    $tabArray[3]["title"] = "Courses";
    $tabArray[3]["onClick"] = 'window.location="advise.php?windowMode=popup&performAction=toolbox&performAction2=courses&order=name&currentStudentID=' . $csid . '";';


    $screen->pageTabs = $screen->drawTabs($tabArray);

  }

  if ($performAction2 == "moved")
  {
    // Display the moved courses management system.
    $pageContent .= $screen->displayToolboxMoved();

    // Create the tabs for the page...
    $tabArray = array();
    $tabArray[0]["title"] = "Transfers";
    $tabArray[0]["onClick"] = 'window.location="advise.php?windowMode=popup&performAction=toolbox&performAction2=transfers&currentStudentID=' . $csid . '";';

    $tabArray[1]["title"] = "Substitutions";
    $tabArray[1]["onClick"] = 'window.location="advise.php?windowMode=popup&performAction=toolbox&performAction2=substitutions&currentStudentID=' . $csid . '";';

    $tabArray[2]["title"] = "Moved";
    $tabArray[2]["onClick"] = 'window.location="advise.php?windowMode=popup&performAction=toolbox&performAction2=moved&currentStudentID=' . $csid . '";';
    $tabArray[2]["active"] = true;

    $tabArray[3]["title"] = "Courses";
    $tabArray[3]["onClick"] = 'window.location="advise.php?windowMode=popup&performAction=toolbox&performAction2=courses&order=name&currentStudentID=' . $csid . '";';

    $screen->pageTabs = $screen->drawTabs($tabArray);

  }



  $screen->pageIsPopup = true;
  $screen->pageContent = $pageContent;
  $screen->outputToBrowser();

}


function displayPopupChangeTerm($screen, $performAction2)
{
  // Check for hooks...
  if (function_exists("advise_displayPopupChangeTerm")) {
    return call_user_func("advise_displayPopupChangeTerm", $screen, $performAction2);
  }
  
  
  global $degreePlan, $student, $fp;

  $pageContent = "";
  $pageContent .= $screen->getJavascriptCode();


  $pageContent .= $screen->displayChangeTerm();


  // Create the tabs for the page...
  $tabArray = array();
  $tabArray[0]["title"] = "Select";
  $tabArray[0]["active"] = true;

  $screen->pageTabs = $screen->drawTabs($tabArray);



  $screen->pageIsPopup = true;
  $screen->pageContent = $pageContent;
  $screen->outputToBrowser();


}


function displayPopupChangeTrack($screen, $performAction2)
{
  // Check for hooks...
  if (function_exists("advise_displayPopupChangeTrack")) {
    return call_user_func("advise_displayPopupChangeTrack", $screen, $performAction2);
  }

  global $degreePlan, $student, $fp;

  $pageContent = "";
  $pageContent .= $screen->getJavascriptCode();


  $pageContent .= $screen->displayChangeTrack();


  // Create the tabs for the page...
  $tabArray = array();
  $tabArray[0]["title"] = "Select";
  $tabArray[0]["active"] = true;

  $screen->pageTabs = $screen->drawTabs($tabArray);


  $screen->pageIsPopup = true;
  $screen->pageContent = $pageContent;
  $screen->outputToBrowser();


}



function displayPopup()
{
  // Check for hooks...
  if (function_exists("advise_displayPopup")) {
    return call_user_func("advise_displayPopup");
  }
  
  
  global $degreePlan, $student, $fp;

  // This is a popup window, so, we need to figure out what
  // the user is trying to do.
  $performAction = trim(addslashes($_GET["performAction"]));
  $performAction2 = trim(addslashes($_GET["performAction2"]));

  // Since this is a popup, I know we can load from the cache.
  $_REQUEST["loadFromCache"] = "yes";

  initScreen();


  $db = new DatabaseHandler();
  $settings = $db->getFlightPathSettings();


  $pageContent = "";
  $screen = new AdvisingScreen("advise.php", $fp, "popup");

  $blankDegreeID = "";
  if ($_REQUEST["blankDegreeID"] != "")
  { // Should contain the ID of the blank degree plan.
    $screen->boolBlank = true;
    $degreePlan = new DegreePlan($_REQUEST["blankDegreeID"]);
    $screen->degreePlan = $degreePlan;
    $blankDegreeID = $_REQUEST["blankDegreeID"];

  }


  if ($performAction == "toolbox")
  {
    displayPopupToolbox($screen, $performAction2);
    return;
  }

  if ($performAction == "changeTerm")
  {
    displayPopupChangeTerm($screen, $performAction2);
    return;
  }

  if ($performAction == "changeTrack")
  {
    displayPopupChangeTrack($screen, $performAction2);
    return;
  }


  if ($performAction == "displayDescription")
  {
    $dataString = trim($_GET["dataString"]);
    $course = new Course();
    if ($dataString != "")
    {
      
      $course->loadCourseFromDataString($dataString);
     
    }


    $pageContent .= $screen->getJavascriptCode();
    $pageContent .= $screen->displayPopupCourseDescription("", $course);
    // Create the tabs for the page...
    $tabArray = array();
    $tabArray[0]["title"] = "Description";
    $tabArray[0]["active"] = true;

    if ($_SESSION["fpCanSubstitute"] == true && !$screen->boolBlank)
    {
      if ($course->boolSubstitution != true && $course->grade == "")
      { // By checking grade, we are making sure this course has NOT already
        // been taken by the student.  In other words, verify that this course
        // is an unfulfilled requirement on the degree plan ONLY.
        $extraVars = "hoursAvail=$course->maxHours";
        $tabArray[1]["title"] = "Substitute";
        $tabArray[1]["onClick"] = "popupSubstituteSelected(\"$course->courseID\",\"$course->assignedToGroupID\",\"$course->assignedToSemesterNum\",\"$extraVars\");";
      }
    }

    $screen->pageTabs = $screen->drawTabs($tabArray);
  }


  if ($_SESSION["fpCanSubstitute"] == true)
  {
    if ($performAction == "substituteSelected")
    {
      $courseID = trim($_GET["courseID"]);
      $groupID = trim(addslashes($_GET["groupID"]));
      $semesterNum = trim(addslashes($_GET["semesterNum"]));
      $groupHoursRemaining = trim(addslashes($_GET["groupHoursRemaining"]));

      $pageContent .= $screen->getJavascriptCode();
      $pageContent .= $screen->displayPopupSubstitute($courseID, $groupID, $semesterNum, $groupHoursRemaining);

    }
  }


  if ($performAction == "displayGroupSelect")
  {
    $courseID = trim($_GET["courseID"]);
    $groupID = trim(addslashes($_GET["groupID"]));
    $groupHoursRemaining = trim(addslashes($_GET["groupHoursRemaining"]));
    $semesterNum = trim(addslashes($_GET["semesterNum"]));

    if (!$group = $degreePlan->findPlaceholderGroup($groupID, $semesterNum))
    {
      adminDebug("Could not find group $groupID in semester $semesterNum.");
    }

    if ($groupID == -88)
    { // This is the Add a Course group.  We must initialize it, as it
      // does not exist yet.
      // We need to populate this group now.
      $group->listCourses = $fp->getAllCoursesInCatalogYear($settings["currentCatalogYear"]);
      $group->title = "Add an Additional Course";
      $group->listCourses->assignGroupID($groupID);
      $group->listCourses->loadCourseDescriptiveData();
    }


    if ($courseID != "")
    {
      // Meaning, a courseID was specified, so make sure
      // it is "selected" inside the group and branches.

      $course = new Course();
      $course->courseID = $courseID;

      $tempCourseList = $group->findCourses($course);
      if (!$tempCourseList)
      {
        $tempCourseList = $degreePlan->findCourses($courseID, $groupID, $semesterNum);
      }

      if ($tempCourseList)
      {
        $tempCourseList->resetCounter();
        while($tempCourseList->hasMore())
        {
          $tempCourse = $tempCourseList->getNext();
          $tempCourse->boolSelected = true;
          //$tempCourse->assignedToSemesterNum = $semesterNum;
        }
      }

      
    }

    if ($performAction2 == "" || $performAction2 == "select")
    {
      $pageContent .= $screen->getJavascriptCode();
      if ($group)
      {
        $pageContent .= $screen->displayPopupGroupSelect($group, $groupHoursRemaining);
      }
      // Create the tabs for the page...
      $tabArray = array();
      $tabArray[0]["title"] = "Description";
      $tabArray[0]["onClick"] = "popupDescribeSelected(\"$groupID\",\"$semesterNum\",\"0\",\"\",\"groupHoursRemaining=$groupHoursRemaining&blankDegreeID=$blankDegreeID\");";
      $tabArray[1]["title"] = "Select";
      $tabArray[1]["active"] = true;

      // If we are allowed to substitute....
      if ($_SESSION["fpCanSubstitute"] == true && $groupID != -88 && !$screen->boolBlank)
      {
        $tabArray[2]["title"] = "Substitute";
        $tabArray[2]["onClick"] = "popupSubstituteSelected(\"0\",\"$groupID\",\"$semesterNum\",\"groupHoursRemaining=$groupHoursRemaining\");";
      }

      $screen->pageTabs = $screen->drawTabs($tabArray);
    }

    if ($performAction2 == "describeCourse")
    {
      $pageContent .= $screen->getJavascriptCode();
      $pageContent .= $screen->displayPopupCourseDescription($courseID,null,$group, true);
      // Create the tabs for the page...
      $tabArray = array();
      $tabArray[0]["title"] = "Description";
      $tabArray[0]["active"] = true;
      $tabArray[1]["title"] = "Select";
      $subject = trim($_GET["selectedSubject"]);

      $tabArray[1]["onClick"] = "popupBackToGroupSelect(\"$courseID\",\"$groupID\",\"$semesterNum\",\"selectedSubject=$subject&groupHoursRemaining=$groupHoursRemaining&blankDegreeID=$blankDegreeID\");";

      // If we are allowed to substitute....
      if ($_SESSION["fpCanSubstitute"] == true && $groupID != -88 && !$screen->boolBlank)
      {
        $tabArray[2]["title"] = "Substitute";
        $tabArray[2]["onClick"] = "popupSubstituteSelected(\"$courseID\",\"$groupID\",\"$semesterNum\",\"groupHoursRemaining=$groupHoursRemaining\");";
      }

      $screen->pageTabs = $screen->drawTabs($tabArray);

    }



  }


  $screen->pageIsPopup = true;
  $screen->pageHideReportError = true;
  $screen->pageContent = $pageContent;
  $screen->outputToBrowser();


  // Should we re-cache the course inventory?  If there have been any changes
  // to it, then we will see that in a GLOBALS variable...
  if ($GLOBALS["cacheCourseInventory"] == true)
  {
    $_SESSION["fpCacheCourseInventory"] = serialize($GLOBALS["fpCourseInventory"]);
  }


  //adminDebug("finsihed");

}


function displayScreen()
{
  // Check for hooks...
  if (function_exists("advise_displayScreen")) {
    return call_user_func("advise_displayScreen");
  }
  
  global $degreePlan, $student, $fp;
  $db = new DatabaseHandler();
  //adminDebug("Starting script...", "main");
  initScreen();

  $pageContent = "";
  $logAction = "view_by_year";
  $logExtra = $student->studentID;

  if ($GLOBALS["advisingView"] == "type")
  {
    $screen = new AdvisingScreenTypeView("advise.php", $fp);
    $screen->view = "type";
    $logAction = "view_by_type";

  } else {

    // Default advising view.  "View by Year"
    $screen = new AdvisingScreen("advise.php", $fp);
  }

  if ($GLOBALS["printView"] == "yes")
  {
    $screen->boolPrint = true;
    $screen->screenMode = "notAdvising";
    $logExtra .= ",print_view";
  }


  $pageContent .= $screen->displayGreeting();

  if ($GLOBALS["advisingWhatIf"] == "yes" && $GLOBALS["whatIfMajorCode"] == "")
  {
    // We are in WhatIf, but we have not selected a major, so give
    // the user a selection screen.
    $screen->screenMode = "notAdvising";
    $pageContent .= $screen->displayWhatIfSelection();
  } else {
    // This is a normal advising screen.  Either View or WhatIf.

    $pageContent .= $screen->displayViewOptions();

    $screen->buildScreenElements();

    $pageContent .= $screen->displayScreen();

  }


  // If we are in WhatIf mode, let's write something special to
  // the log.
  if ($GLOBALS["advisingWhatIf"] == "yes" && $GLOBALS["whatIfMajorCode"] != "")
  {
    $logAction .= "_whatif";
    $logExtra = $GLOBALS["whatIfMajorCode"] . " " . $GLOBALS["whatIfTrackCode"];
  }

  $db->addToLog($logAction, $logExtra);


  $screen->buildSystemTabs(2, true);
  if ($GLOBALS["advisingWhatIf"] == "yes")
  {
    $screen->buildSystemTabs(5, true, true);
  }

  $screen->pageScrollTop = trim($_POST["scrollTop"]);
  //adminDebug("Finished", "main");
  //adminDebug(strlen($pageContent));
  $screen->pageHasSearch = true;
  if ($_SESSION["fpUserType"] == "student")
  {
    $screen->pageHasSearch = false;
  }


  $screen->pageContent = $pageContent;
  // send to the browser
  $screen->outputToBrowser();


  //	print_pre($student->listCoursesTaken->toString());
  // Should we re-cache the course inventory?  If there have been any changes
  // to it, then we will see that in a GLOBALS variable...
  if ($GLOBALS["cacheCourseInventory"] == true)
  {
    $_SESSION["fpCacheCourseInventory"] = serialize($GLOBALS["fpCourseInventory"]);
  }


  //adminDebug("Finished", "main");
}





function initScreen()
{
  
  // Check for hooks...
  if (function_exists("advise_initScreen")) {
    return call_user_func("advise_initScreen");
  }
    
  global $degreePlan, $student, $fp, $windowMode;

  $performAction = trim(addslashes($_POST["performAction"]));
  $tempScreen = new AdvisingScreen();
  $tempScreen->initAdvisingVariables();
  $boolWhatIf = false;
  
  $csid = $GLOBALS["currentStudentID"];


  $db = new DatabaseHandler();
  $cache = $_SESSION["cacheFP$csid"];

  if ($GLOBALS["advisingWhatIf"] == "yes")
  {
    $majorCode = $GLOBALS["whatIfMajorCode"];
    $trackCode = $GLOBALS["whatIfTrackCode"];
    //$majorCode = "ART";
    $boolWhatIf = true;
    //$GLOBALS["loadFromCache"] = "no";
    $cache = $_SESSION["cacheWhatIf$csid"];
    //adminDebug("here");
  }

  $boolDraft = true;
  if ($GLOBALS["advisingLoadActive"] == "yes")
  { // If we are loading from Active, then rebuild the cache as well.
    $boolDraft = false;
    $GLOBALS["loadFromCache"] = "no";
  }

  if ($_SESSION["fpUserType"] == "student")
  {
    $boolDraft = false;
    // never load a draft advising session if a student
    // is logged in!
  }


  ///////////////////////
  ///  Disable student data Caching....
  //$GLOBALS["loadFromCache"] = "no";


  // Attempt to load the course inventory cache...
  if ($courseInventory = unserialize($_SESSION["fpCacheCourseInventory"]))
  {
    $GLOBALS["fpCourseInventory"] = $courseInventory;
  }


  if ($GLOBALS["loadFromCache"] == "yes" && $cache != "" && $fp = unserialize($cache))
  {
    //adminDebug("Unserializing...");
    $fp->db = new DatabaseHandler();
    $student = $fp->student;
    $degreePlan = $fp->degreePlan;
    $student->db = new DatabaseHandler();
    $degreePlan->db = new DatabaseHandler();
    //adminDebug("Done Unserializing... $boolWhatIf");

  } else {
    $fp = new FlightPath();
    //adminDebug("xx");
    $fp->init();
    //adminDebug("xx");
    $student = $fp->student;
    $degreePlan = $fp->degreePlan;
    $GLOBALS["loadFromCache"] = "no";
    //adminDebug($GLOBALS["advisingMajorCode"]);
  }


  // Should we update the USER settings for anything?
  if ($GLOBALS["fpUpdateUserSettingsFlag"] != "")
  {
    //$GLOBALS["userSettings_hideCharts"] = $_REQUEST["hideCharts"];
    if (!$db->updateUserSettingsFromPost($_SESSION["fpUserID"]))
    {
      adminDebug("could not write user settings.");
    }
  }


  if ($performAction == "saveDraft")
  {
    // Save, then reload the student.

    $fp->saveAdvisingSessionFromPost(0,true);

  }

  if ($performAction == "saveActive")
  {
    // Save, then go to the history screen.
    $advIDArray = $fp->saveAdvisingSessionFromPost(0,false);
    displayAdviseeHistory(true, $advIDArray);
    die;
  }


  
  if ($boolWhatIf == true && $GLOBALS["whatIfMajorCode"] == "")
  {
    // In other words, we are on the WhatIf tab, but we have not
    // selected a major.  So, just exit out.  We will give the user
    // a displayScreen later.
    return;
  }





  
  if ($GLOBALS["loadFromCache"] != "yes")
  { // do not load from cache....

    $student->loadStudent();

    $student->loadStudentSubstitutions();

    $student->loadUnassignments();

    $student->listCoursesTaken->sortAlphabeticalOrder();
    $student->listCoursesTaken->sortMostRecentFirst();
    //	print_pre($student->listCoursesTaken->toString());


    $fp->flagOutdatedSubstitutions();
    $fp->assignCoursesToSemesters(); // bare degree plan. not groups.
    $fp->assignCoursesToGroups();
    
  }

  //adminDebug("Serializing...");
  if ($GLOBALS["saveToCache"] != "no" && $windowMode != "popup")
  {
    if ($boolWhatIf == false)
    { // NOT in whatIf mode.  Normal.
      //adminDebug("start serialize");

      $_SESSION["cacheFP$csid"] = serialize($fp);
      
      //adminDebug(strlen($_SESSION["cacheFP$csid"]));
      //adminDebug("Done Serializing...normal");
      
    } else {
      // We are in WhatIf mode.
      $_SESSION["cacheWhatIf$csid"] = serialize($fp);      
      //adminDebug("Done Serializing...what if");


    }
  }





  $fp->loadAdvisingSessionFromDatabase(0,$advisingTermID,$boolWhatIf,$boolDraft,0);

  //adminDebug("load advising session");

  // Once we have loaded the advising session, we should always try to load
  // from draft from then on out.
  $GLOBALS["advisingLoadActive"] = "";

  //adminDebug("mem:" .  round(memory_get_usage(true)/1024/1024,1) . "mb");

  //print_pre($student->listCoursesTaken->toString());
    
  
}




function displayAdviseeHistory($boolFromSave = false, $advisingSessionIDArray = "")
{

  // Check for hooks...
  if (function_exists("advise_displayAdviseeHistory")) {
    return call_user_func("advise_displayAdviseeHistory", $boolFromSave, $advisingSessionIDArray);
  }

  
  $screen = new AdvisingScreen("",null,"notAdvising");
  $db = new DatabaseHandler();


  //if (!$boolFromSave)
  //{
  $screen->initAdvisingVariables(true);
  //}


  $studentID = $GLOBALS["advisingStudentID"];


  $pC = "";

  $pC .= $screen->getJavascriptCode();
  $pC .= $screen->displayGreeting();
  $pC .= $screen->displayBeginSemesterTable();
  $pC .= $screen->drawCurrentlyAdvisingBox(true);

  //-------------------------------------------------------------
  //----   If we are coming back from a perm save, then we should display
  //----   a message and a convienent link to allow the advisor to get the
  //----   advising summary.
  //-------------------------------------------------------------
  if ($boolFromSave == true)
  {

    // We should have the advising session ID's in the
    // advisingSessionIDArray array.
    $clickLinks = "";

    foreach($advisingSessionIDArray as $termID=>$value)
    {
      $aid = $advisingSessionIDArray[$termID];
      if ($aid != "")
      {
        $newCourse = new Course();
        $newCourse->termID = $termID;
        $termName = $newCourse->getTermDescription();
        $clickLinks .= "<li>
								<a href='javascript: popupPrintWindow(\"advise.php?windowMode=summary&advisingSessionID=$aid\");'>
								<img src='$screen->themeLocation/images/popup.gif' border='0'>
								$termName</a>";


      }
    }


    // onClick='popupPrintWindow(\"advise.php?windowMode=summary&advisingSessionID=ADVID\");'
    $pC .= "
				<tr>
					<td colspan='2' width='100%'>
					
				<div class='hypo' 
				align='left' style='border: 1px solid black;
							margin: 10px 0px 10px 0px; padding: 10px; 
							font-size: 12pt; font-weight: bold;'>
				You have successfully advised " . $db->getStudentName($studentID) . " ($studentID).
				<br><span style='color: blue;'>Click 
				 to view a pop-up printable summary for: 
				 <ul style='margin-top: 5px; margin-bottom: 5px;'>
				$clickLinks
				</ul></span></div>
				
				</td>
				</tr>
		";

  }

  $pC .= "<tr><td width='50%' valign='top'  style='padding-right: 10px;'>";

  ///////////////////////////////////////////////////
  //////////////////////////////////////////////////
  /////////  Advising History
  ///////////////////////////////////////////////////
  $pC .= $screen->drawCurvedTitle("Advising History");
  $pC .= "<table border='0' cellspacing='0'>";
  $oldSessionDT = 0;
  $aCount = 0;
  $isEmpty = true;
  $firstStyle = "color: maroon; font-weight:bold;";
  $onMouseOver = "onmouseover=\"style.backgroundColor='#FFFF99'\"
           onmouseout=\"style.backgroundColor='white'\" ";

  $res = $db->dbQuery("SELECT * FROM advising_sessions
							WHERE `student_id`='$studentID'
							AND `is_draft`='0'
							AND `is_empty`='0'
							ORDER BY `datetime` DESC, `term_id` DESC ");
  while($cur = $db->dbFetchArray($res))
  {
    extract($cur, 3, "db");

    $dt = date("n/j/y g:ia",strtotime($db_datetime));

    // Is this datetime within 5 seconds of the previous datetime?
    // If so, they were likely saved together, and are part
    // of the same advising session.  Otherwise, this is a NEW
    // advising session.  The if statement below is testing is this
    // a new advising session.
    $testDT = strtotime($db_datetime);
    //adminDebug($oldSessionDT . " - " . $testDT);
    if ($oldSessionDT < ($testDT - 5) || $oldSessionDT > ($testDT + 5))
    {
      $p = "20px;";
      if ($aCount == 0)
      {
        $p = "10px;";
      }

      $oldSessionDT = $testDT;
      $advisedBy = "<div style='padding-top: $p'>
							<b>Advised by " . $db->getFacultyName($db_faculty_id, false) . "</b>
						</div>";

      $pC .= "<tr><td colspan='2' class='tenpt'>
							$advisedBy
						</td>
					</tr>";
      $aCount++;

    }
    $isEmpty = false;

    if ($aCount > 1)
    {
      $firstStyle = "";
    }


    $onClick = "popupPrintWindow(\"advise.php?windowMode=summary&advisingSessionID=$db_advising_session_id\");";

    $newCourse = new Course();
    $newCourse->termID = $db_term_id;
    $term = $newCourse->getTermDescription();

    $pC .= "<tr $onMouseOver style='cursor: pointer; $firstStyle'
					onClick='$onClick'>
					<td valign='top' class='tenpt'
						style='padding-left:20px;'
						width='165'>
						$term
					</td>
					<td valign='top' class='tenpt'>
						$dt
					</td>
				</tr>";


  }
  $pC .= "</table>";
  $pC .= "<form id='mainform' method='POST'>
			<input type='hidden' id='scrollTop'>
			<input type='hidden' id='performAction' name='performAction'>
			<input type='hidden' id='advisingWhatIf' name='advisingWhatIf'>
			<input type='hidden' id='currentStudentID' name='currentStudentID'>
			</form>";


  if ($isEmpty == true) {
    $pC .= "<div class='tenpt'>No advising history available.</div>";
  }


  //----------------------------------------------------------------------------------------
  //------------------------------ COMMENT HISTORY -----------------------------------------
  //----------------------------------------------------------------------------------------
  $pC .= "</td><td width='50%' valign='top'>";
  $pC .= $screen->drawCurvedTitle("Comment History");
  $pC .= "<table border='0' cellspacing='0'>";

  $oldTermID = "";
  $firstStyle = "first";
  $isEmpty = true;
  $hasAdminCategory = false;
  $accessLine = "";
  if ($_SESSION["fpUserType"] == "student")
  { // May not be necessary, since students don't see this tab anyway...
    $accessLine = "and `access_type`='public' ";
  }
  
  $pC .= "<tr><td colspan='3' class='tenpt'>
				<!--STARTCOM$catType--><div style='padding-top: 10px;'>
					<b>Advising Comments</b>
					&nbsp; 
				<a href='javascript: popupPrintWindow(\"comments.php?performAction=displayComment&category=all\");' 
					class='nounderline'><img src='$screen->themeLocation/images/popup.gif' border='0'>view/print all</a>
				</div><!--ENDCOM$catType-->
				</td></tr>";

  $res = $db->dbQuery("select * from advising_comments
						where `student_id`='$studentID' 
						and `delete_flag`='0'
						$accessLine
						$catLine
						order by `datetime` desc ");
  while ($cur = $db->dbFetchArray($res))
  {
    extract($cur, 3, "db");
    $dt = date("n/j/y g:ia",strtotime($db_datetime));


    if ($firstStyle == "first")
    {
      $firstStyle = "color: maroon; font-weight:bold;
					";
    }




    $onClick = "popupPrintWindow(\"comments.php?performAction=displayComment&id=$db_id\");";
    $pC .= "<tr $onMouseOver style='cursor:pointer; $firstStyle $extraStyle'
					onClick='$onClick'>
					<td valign='top' width='165' class='tenpt'
						style='padding-left: 20px;'>
						" . $db->getFacultyName($db_faculty_id, false) . "
					</td>
					<td valign='top' class='tenpt'>
					$dt$ast
					</td>
				</tr>";

    $isEmpty = false;
    $firstStyle = "";
  }

  if ($isEmpty == true) {
    
    $pC .= "<tr><td colspan='4' class='tenpt'>
						<div style='padding-left: 20px;'>
							No $catType comment history available.</div></td></tr>";
  }

  $pC .= "</table>";


  $pC .= "</td></tr>";




  $pC .= $screen->displayEndSemesterTable();

  $pC .= "<script type='text/javascript'>" . $screen->getJS_popupPrintWindow() . "</script>";

  $screen->pageContent = $pC;
  $screen->pageHasSearch = true;
  if ($_SESSION["fpUserType"] == "student")
  {
    $screen->pageHasSearch = false;
  }
  $screen->buildSystemTabs(4);
  $screen->pageTitle = "FlightPath - History";
  // send to the browser
  $screen->outputToBrowser();

  die;

}

?>
