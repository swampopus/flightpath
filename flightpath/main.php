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
This is the first page users see when they log into the system.
*/
session_start();
header("Cache-control: private");

require_once("bootstrap.inc");


$performAction = trim(addslashes($_REQUEST["performAction"]));

/*
If the user is NOT logged in, then show them the login screen.
*/
$msg = "";
if ($performAction == "clearCache")
{
  // Wipe out the cache, so joann doesn't have to log out
  // and back in.

  clearCache(); 
  //$_SESSION["clearCache"] = "yes";
  $msg = "The cache has been cleared.";
}


// Set/unset draft mode.
if ($performAction == "draftModeYes")
{
  clearCache();
  $_SESSION["fpDraftMode"] = "yes";

}
if ($performAction == "draftModeNo")
{
  clearCache();
  $_SESSION["fpDraftMode"] = "no";
  $msg = "Now viewing in Regular Mode.
			This is what regular users currently see in the system.";
}


if ($performAction == "performLogout")
{
  performLogout();
  die;
}

if ($performAction == "performLogin")
{
  performLogin();
  die;
} else if ($_SESSION["fpLoggedIn"] != true)
{

  displayLogin();
  die;
}




if ($performAction == "switchUser" && userHasPermission("deCanSwitchUsers")) {

  clearCache();
  //adminDebug("userid: " . $_REQUEST["switchUserID"]);
  performLogin(true, $_REQUEST["switchUserID"]);
  //adminDebug("right here");
  die;
} else if ($performAction == "switchUser" && !userHasPermission("deCanSwitchUsers"))
{
  die("You do not have access to this function.");
}


/////////////////////////////////////
///  Are we trying to save the draft
///  from a tab change?
/////////////////////////////////////
$fp = new FlightPath();
$fp->processRequestSaveDraft();

/*if ($_REQUEST["saveDraft"] == "yes")
{
$fp = new FlightPath();
$fp->init(true);
$fp->saveAdvisingSessionFromPost(0,true);
}
*/



$screen = new AdvisingScreen("",null,"notAdvising");
$screen->initAdvisingVariables(true);



// Display the main page...
displayMain($msg);
die;

function clearCache()
{

  $_SESSION["fpCacheCourseInventory"] = "";

  foreach ($_SESSION as $skey=>$val)
  {
    //adminDebug($skey);
    if (strstr($skey, "cache"))
    {
      //adminDebug("wiping $skey");
      $_SESSION[$skey] = "";
    }
  }
}


function performLogout()
{

  // Check for hooks...
  if (function_exists("main_performLogout")) {
    return call_user_func("main_performLogout");
  }

  
  // This will log the user out of the system.
  // log the logout first...
  $db = new DatabaseHandler();
  $db->addToLog("logout");


  $_SESSION["fpLoggedIn"] = false;
  $_SESSION["fpUserID"] = false;
  $_SESSION["fpUserType"] = false;
  $_SESSION["fpCanAdvise"] = false;
  $_SESSION["fpCanSearch"] = false;
  $_SESSION["fpCanSubstitute"] = false;
  $_SESSION["fpCacheCourseInventory"] = false;
  $_SESSION["fpFacultyUserMajorCode"] = false;

  // Just to make sure we get everything...
  session_destroy();

  displayLogin($msg);

}



function performLogin($boolBypassVerification = false, $bypassUserID = "")
{

  // Check for hooks...
  if (function_exists("main_performLogin")) {
    
    return call_user_func("main_performLogin", $boolBypassVerification, $bypassUserID);
  }

  // Are we bypassing logins in the settings, and just giving this user
  // full_admin access?
  if ($GLOBALS["fpSystemSettings"]["GRANT_FULL_ACCESS"] == TRUE) {
    $_SESSION["fpLoggedIn"] = TRUE;
    $_SESSION["fpUserID"] = 1;
    $_SESSION["fpUserType"] = "full_admin";
    $_SESSION["fpCanAdvise"] = TRUE;
    $_SESSION["fpCanSearch"] = TRUE;
    $_SESSION["fpCanSubstitute"] = TRUE;
    $_SESSION["fpCacheCourseInventory"] = false;
    $_SESSION["fpCanModifyComments"] = TRUE;
    displayMain();
    return;
  }
  
  
  // First clear session vars
  $_SESSION["fpLoggedIn"] = false;
  $_SESSION["fpUserID"] = false;
  $_SESSION["fpUserType"] = false;
  $_SESSION["fpCanAdvise"] = false;
  $_SESSION["fpCanSearch"] = false;
  $_SESSION["fpCanSubstitute"] = false;
  $_SESSION["fpCacheCourseInventory"] = false;
  $_SESSION["fpFacultyUserMajorCode"] = false;

  // Attempt to log the user into the system.
  $userID = trim($_REQUEST["userID"]);
  $password = trim($_REQUEST["password"]);
  $db = new DatabaseHandler();

  // First thing we need to do -- check to make sure the settings
  // table contains a currentCatalogYear setting, since it's required.
  $settings = $db->getFlightPathSettings();
  if (trim($settings["currentCatalogYear"] == ""))
  {
    displayLogin("<font color='red'>FlightPath is currently undergoing
							routine system maintenance.  Please wait
							a few minutes and try to log in again.</font>");
    die;

  }

  if ($settings["offlineMode"] == "1")
  {  // We are not allowing logins right now.
    displayLogin();
    die;
  }

  // Are we using the "switch user" feature?
  if ($boolBypassVerification == true)
  {
    $userID = $bypassUserID;
    $_SESSION["fpSwitchedUser"] = true;
    $db->addToLog("switch_user","$userID");
  }

  $isStudent = $isFaculty = false;
  $fromUsername = $userID;
  
  
  // Attempt to verify the user by the two user types.
  $verifyFacultyLogin = fp_verifyAllFacultyLogins($userID, $password);
  $verifyStudentLogin = fp_verifyAllStudentLogins($userID, $password);
  
  if ($verifyFacultyLogin) $userID = $verifyFacultyLogin;
  if ($verifyStudentLogin) $userID = $verifyStudentLogin;
  
  // What are this user's possible user types?
  if (trim($db->getStudentName($userID)) != "")
  {
    $isStudent = true;
  }
  if (trim($db->getFacultyName($userID)) != "")
  {
    $isFaculty = true;
  }



  $userType = determineStaffUserType($userID);


  $boolNoLogin = false;
  if ((($userType != "limited_faculty_student")
  && ($verifyFacultyLogin) || ($boolBypassVerification == true && $isFaculty)))
  {

    // The user is in the faculty/staff database.

    $_SESSION["fpLoggedIn"] = true;
    $_SESSION["fpUserID"] = $userID;
    $_SESSION["fpUserName"] = $db->getFacultyName($userID, true);
    
    // Figure out their majorCode, if it exists.
    //$_SESSION["fpFacultyUserMajorCode"] = determineFacultyUserMajorCode($userID);
    $_SESSION["fpFacultyUserMajorCode"] = $db->getFacultyMajorCode($userID);
    
    $_SESSION["fpUserType"] = $userType;

    // Figure out their privileges based on user type...
    if ($userType == "full_admin" || $userType == "college_coordinator")
    {
      $_SESSION["fpCanAdvise"] = true;
      $_SESSION["fpCanSearch"] = true;
      $_SESSION["fpCanSubstitute"] = true;
      $_SESSION["fpCanModifyComments"] = true;
    }
    if ($userType == "advisor" || $userType == "adviser")
    {
      $_SESSION["fpCanAdvise"] = true;
      $_SESSION["fpCanSearch"] = true;
      $_SESSION["fpCanSubstitute"] = false;
      $_SESSION["fpCanModifyComments"] = true;
    }
    if ($userType == "viewer")
    {
      $_SESSION["fpCanAdvise"] = false;
      $_SESSION["fpCanSearch"] = true;
      $_SESSION["fpCanSubstitute"] = false;
      $_SESSION["fpCanModifyComments"] = false;

    }
    if ($userType == "none" && $isStudent == false)
    {
      // Users with a type of "none" may go to the Main tab,
      // but that is all!  Once there, they will see no other tabs,
      // and will be given a message telling them they cannot advise
      // in FP.
      // We let them in, so they can still access the Tools of FP.

      $_SESSION["fpLoggedIn"] = true;
      $_SESSION["fpCanAdvise"] = false;
      $_SESSION["fpCanSearch"] = false;
      $_SESSION["fpCanSubstitute"] = false;
      $_SESSION["fpCanModifyComments"] = false;
    } else if ($userType == "none" && $isStudent == true)
    { // is a student/staff member.  Attempt a student login.
      $boolNoLogin = true;
    }


    // Get the permissions for this user.
    $res = $db->dbQuery("select * from flightpath.users
								where `faculty_id`='$userID' ");
    $cur = $db->dbFetchArray($res);
    $temp = split(",",$cur["permissions"]);
    foreach ($temp as $perm)
    {
      $perm = trim($perm);
      if ($perm != ""){$_SESSION[$perm] = true;}
    }


    // Okay, the user is logged in.  Proceed to the Main tab.
    if (!$boolNoLogin)
    {
      $db->addToLog("login", $fromUsername);
      displayMain();
      die;
    }


  }




  // Is the user a student?
  if ($verifyStudentLogin || $boolBypassVerification == true)
  {
    // The user is a student.

    $allowedStudentRanks = $GLOBALS["fpSystemSettings"]["allowedStudentRanks"];
    
    // Before we let them in, we need to make sure they are undergrad,
    // As FP is only designed
    $rank = $db->getStudentRank($userID);
    if (!in_array($rank, $allowedStudentRanks))
    { // Student is not an undergread (or, just not allowed in).
      $msg = "<font color='red'>
          {$GLOBALS["fpSystemSettings"]["notAllowedStudentMessage"]}</font>";
      $db->addToLog("login_fail", "grad student");
      displayLogin($msg);
      die;
    }

    // The student is an undergrad, so go ahead and log them in.
    $_SESSION["fpLoggedIn"] = true;
    $_SESSION["fpUserID"] = $userID;
    $_SESSION["fpUserName"] = $db->getStudentName($userID, true);
    $_SESSION["fpUserType"] = "student";
    $_SESSION["fpCanAdvise"] = false;
    $_SESSION["fpCanSubstitute"] = false;
    $_SESSION["fpCanModifyComments"] = false;


    // We also need to make it so that the student is "advising" themselves,
    // so that the View shows up correctly.
    $_SESSION["advisingStudentID"] = $userID;
    $GLOBALS["advisingStudentID"] = $userID;

    // Get the student's major as it would have been gotten from
    // the search...
    $majorCode = $db->getStudentMajorFromDB($userID);
    $_SESSION["advisingMajorCode"] = $majorCode;
    $GLOBALS["advisingMajorCode"] = $majorCode;

    $_SESSION["advisingLoadActive"] = "yes";
    $GLOBALS["advisingLoadActive"] = "yes";

    $_SESSION["clearSession"] = "yes";
    $GLOBALS["clearSession"] = "yes";

    // Okay, the user is logged in.  Proceed to the Main tab.
    $db->addToLog("login", $fromUsername);
    displayMain();
    die;

  }


  if ($boolNoLogin == true)
  {
    // We were unable to login as either faculty or staff, so kick them
    // out.
    displayLogin("<font color='red'>We're sorry, but you are not allowed access in FlightPath.
			Please contact your department head for more information.</font>");
    die;
  }


  // If we are here, then we did not log in correctly.
  $msg = "<font color='red'>That username/password combination cannot be verified.
					Please check your spelling and try again.</font>
			";

  $db->addToLog("login_fail", $fromUsername);
  displayLogin($msg);

}


function displayLogin($msg = "")
{
  // Check for hooks...
  if (function_exists("main_displayLogin")) {
    return call_user_func("main_displayLogin", $msg);
  }
  
  
  // This is the login page for FlightPath.
  $screen = new AdvisingScreen();

  $pC .= $screen->getJavascriptCode();

  $pC .= "
	
		<div align='center' style='font-size: 14pt;'>
 		Welcome to <b><i><font color='maroon'>FlightPath</font></i></b>, the electronic student
 		advising system!
 		</div><br>";

  if ($screen->settings["urgentMsg"] != "")
  {
    $pC .= "<div class='tenpt hypo' style='margin: 10px; padding: 5px;'>
				<b>Important Notice:</b> " . $screen->convertBBCodeToHTML($screen->settings["urgentMsg"]) . "
				</div>";
  }

  if ($screen->settings["offlineMode"] == "1") {
    // Logins have been disabled in the settings.  Do not display the login
    // form to the user.
    $pC .= "<div>";
    $msg = trim($screen->convertBBCodeToHTML($screen->settings["offlineMsg"]));
    if ($msg != "") {
      $pC .= $msg;
    }
    else {
      $pC .= "FlightPath is currently offline at this time for maintenance and updates.
	           We are sorry for the inconvenience.  Please try again later.";
    }
    
    $pC .= "</div>";
  }
  else {
  // NOT in offlineMode!  So, display the login form normally.  

  $pC .= "<noscript>
            <div style='padding: 5px; background-color: red; color: white; font-size: 12pt; font-weight: bold;'>
            FlightPath requires JavaScript to be enabled in order to
            function correctly.  Please enable JavaScript on your browser
            before continuing.
          </noscript>";
  
  $importantNotice = "
   	 	<div class='hypo tenpt' style='padding:5px; text-align: justify;'>
   	 	<b>Important Notice:</b> This degree audit system is intended to assist you in determining 
   		your progress toward a degree, but is not an official transcript. 
   		Although efforts are made to ensure the accuracy of this system, you 
   		should carefully review it and report any discrepancies to your advisor. 
   		It is your responsibility to confirm the status of major requirements by 
   		consulting a departmental advisor. It is also your responsibility to seek 
   		information on all college and major requirements in the undergraduate 
   		catalog to which you are assigned. Final confirmation of degree requirements is subject to approval 
   		by the major department and Dean.
   		</div>
  ";
  
  $w1 = 300;
  if ($screen->pageIsMobile) $w1 = "90%";
  
  $loginBox = "
   		<table border='0' width='$w1' align='center' class='blueBorder' cellpadding='0' cellspacing='0'>
   		<tr>
   		 <td class='blueTitle' align='center' height='20' colspan='2'>
   		 <span class='tenpt' style='color: white' ><b>Please login below...</b></span>
   		 </td>
   		</tr>
   		<tr>
   		<td colspan=2> &nbsp;
   		</td>
   		</tr>
   		<tr>
          <td align=center valign=top width='35%'>
            
            <span style='color: #660000; '><b>USER:</b></span></td><td valign=top><input type=text name='userID' id='cwid_box'></td>
          </td>
        </tr>
        <tr>
          <td align=center valign=top>
            <span style='color: #660000; '><b>PASS:</b></span></td>
          <td valign=top><input type='password' name='password'>
          <br><span class='tenpt' ><a href='javascript: popupHelpWindow(\"help.php?i={$GLOBALS["fpSystemSettings"]["loginHelpPageID"]}\");' style='text-decoration:none;'>need help logging-in?</a></span>
            </td>
        </tr>
        <tr>
          <td align=center>
          </td>
          <td align=left>
  			<br>     
  			<input type='hidden' name='scrollTop' id='scrollTop'>   
            	<input style='font-size: 12pt; color: #660000;' NAME='submit_fun' type='submit' value='Login' onClick='showUpdate(true);submitForm(false);'>
            
            
            <BR><br>
            
  
          </td>
        </tr>
      </table>
   ";
  
  
  $pC .= "
   		<form action='main.php' method='POST' id='mainform' onSubmit='showUpdate(true);'>
   		<input type='hidden' name='performAction' value='performLogin'>
   	";
  
  if ($screen->pageIsMobile == true) {
    // the user is viewing this on a mobile device, so make it look
    // a bit nicer.
    $pC .= $screen->drawCFieldset($importantNotice, "View important notice", true);
    $pC .= $loginBox; 
  }
  else {
    // This is NOT mobile, this is a regular desktop browser.
    $pC .= "
     	<table border='0'>
     	<tr>
     	 <td valign='top' width='40%'>
     	  $importantNotice
     	 </td>
     	<td valign='middle'>
        $loginBox   		
  
       </td>
      </tr>
     </table>
     ";
  }
  
  $pC .= "
  </form>
  <br>$msg
  ";
  
/*  $pC .= "
   		<div class='tenpt' style='padding:5px; color: maroon;' align='center'>
  		FlightPath works best with <b>Internet Explorer</b> or <b>Firefox</b>, and requires JavaScript to be enabled.
  		<br>	</div>";
*/  
  
  }
  /*$pageContent = $pC;
  $pageOnLoad = "document.getElementById(\"cwid_box\").focus();  ";
  $pageHideReportError = true;
  include("template/fp_template.php");*/

  $screen->pageContent = $pC;
  $screen->pageHasSearch = false;
  $screen->pageOnLoad = "document.getElementById(\"cwid_box\").focus(); ";
  $page->pageHideReportError = true;
  // send to the browser
  $screen->outputToBrowser();


}


function displayMain($msg = "")
{

  // Check for hooks...
  if (function_exists("main_displayMain")) {
    return call_user_func("main_displayMain", $msg);
  }

  
  $screen = new AdvisingScreen("",null,"notAdvising");
  $screen->adminMessage = $msg;

  $pC = "";

  $pC .= $screen->displayGreeting();
  $pC .= $screen->displayBeginSemesterTable();

  if ($_SESSION["fpUserType"] != "none")
  {
    $pC .= $screen->drawCurrentlyAdvisingBox(true);
  } else {
    // Let the user know they have no privileges in FP.
    $pC .= "<tr>
				<td colspan='5'>
				
				 <div class='hypo tenpt' style='margin: 10px; padding: 10px;'>
				   <div style='float:left; padding-right: 20px; height: 50px;'>
					<img src='$screen->themeLocation/images/alert_lg.gif'>
				   </div>
				  <b>Please Note:</b>
					At this time, you do not have access to student records in FlightPath.
					However, you may still access certain Tools, listed below.  If you feel
					that you have reached this message in error, and you do require
					access to student records, please speak with your department head or call
					342-5554.
				  </div>
				</td>
				</tr>					
				";
  }

  
  $announcements = getAnnouncements($screen);
  $tools = getTools($screen);
  $adminTools = getAdminTools($screen);
  
  if ($screen->pageIsMobile) {
    $pC .= "<tr><td colspan='2'>$announcements $tools $adminTools</td></tr>";
  }
  else {
    $pC .= "<tr><td width='50%' valign='top'  style='padding-right: 10px;'>";
    $pC .= $announcements;
    $pC .= "</td><td width='50%' valign='top' style='padding-left: 10px;'>";
    $pC .= $tools;
    $pC .= $adminTools;
    $pC .= "</td></tr>";
  }
  
  $pC .= $screen->displayEndSemesterTable();
  $pC .= "<form id='mainform' method='POST'>
			<input type='hidden' id='scrollTop'>
			<input type='hidden' id='performAction' name='performAction'>
			<input type='hidden' id='advisingWhatIf' name='advisingWhatIf'>
			<input type='hidden' id='currentStudentID' name='currentStudentID'>
			</form>";

  $pC .= $screen->getJavascriptCode();

  /*	$pageTabs = $screen->drawSystemTabs(0);
  $pageHasSearch = true;
  $pageContent = $pC;
  include("template/fp_template.php");
  */

  $screen->pageContent = $pC;
  $screen->pageHasSearch = true;
  if ($_SESSION["fpUserType"] == "student" || $_SESSION["fpCanAdvise"] == false)
  {
    $screen->pageHasSearch = false;
  }
  $screen->buildSystemTabs(0);

  adminDebug("--");
  //////////////////////////////////////////////////////////
  // To cut down on how long it takes to load huge groups
  // like Free Electives, we will pre-load some of the course inventory here.
  if ($_SESSION["fpCachedInventoryFlagOne"] != true)
  {
    $loadNumber = $GLOBALS["fpSystemSettings"]["loadCourseInventoryOnLoginNumber"];
    if ($loadNumber > 1) {
      $fp = new FlightPath();
      $fp->cacheCourseInventory(0,$loadNumber);
      $_SESSION["fpCachedInventoryFlagOne"] = true;
    }
  }
  adminDebug("--");


  // send to the browser
  $screen->outputToBrowser();



}


function getAnnouncements($screen)
{
  
  // Check for hooks...
  if (function_exists("main_getAnnouncements")) {
    return call_user_func("main_getAnnouncements", $screen);
  }
  
  
  $pC = "";
  $pC .= $screen->drawCurvedTitle("Announcements");

  $db = new DatabaseHandler();
  $settings = $db->getFlightPathSettings();

  $isEmpty = true;
  // Pull out just the announcements XML and make it into its own array.
  if ($settings["announcements_xml"] != "")
  {
    if ($xmlArray = fp_xmlToArray2($settings["announcements_xml"]))
    {
      // Expected format of the xmlArray:
      //[dt_timecode] = "announcement text."
      // ex: dt_111234432.  Use strtotime to convert.
      // It begins with dt_ because in XML the start of
      // an element must be a letter, not a number.

      krsort($xmlArray);  // sort by most recent.

      foreach($xmlArray as $datetime => $announcement)
      {
        $dt = str_replace("dt_", "", $datetime);

        $dispTime = date("D, M jS Y  - h:ia", $dt);
        // Re-enable HTML formatting in announcement...
        $temp = split(" ~~ ", $announcement);
        $visible = trim($temp[0]);
        $announcementText = trim($temp[1]);
        $announcementText = $screen->convertBBCodeToHTML($announcementText);

        if ($visible == "hide")
        {
          // visibility set to "hidden"
          continue;
        }

        if ($visible == "faculty" && $_SESSION["fpUserType"] == "student")
        { // skip faculty-only comments if we are a student!
          continue;
        }

        $pC .= "<div class='elevenpt' style='margin-top: 20px;'>$announcementText
							<div align='right' class='tenpt' style='color: gray; padding-right: 10px;'>
							<i>Posted $dispTime</i>
							</div>
						</div>";
      }
    }
  }



  return $pC;
}



function getTools($screen)
{
  
  // Check for hooks...
  if (function_exists("main_getTools")) {
    return call_user_func("main_getTools", $screen);
  }

  
  $pC = "";

  $db = new DatabaseHandler();
  $settings = $db->getFlightPathSettings();
  $currentCatalogYear = $settings["currentCatalogYear"];

  $pC .= $screen->drawCurvedTitle("Tools");
  
  // Get all of the menu items which should appear here
  $menus = getModulesMenus();
  
  //var_dump($menus);
  
  $pC .= $screen->drawMenuItems($menus["tools"]);
    
  return $pC;
}


function getAdminTools($screen)
{
  
  // Check for hooks...
  if (function_exists("main_getAdminTools")) {
    return call_user_func("main_getAdminTools", $screen);
  }

  $pC = "";
  $isEmpty = TRUE;
  
  $pC .= "<div style='padding-top: 10px;'>&nbsp;</div>";
  $pC .= $screen->drawCurvedTitle("Special Administrative Tools");


  if (userHasPermission("deCanAccessAdminConsole")) {
    $pC .= $screen->drawMenuItem("admin.php", "_blank", "<img src='$screen->themeLocation/images/toolbox.gif' border='0'>", "FlightPath Admin Console");
    $isEmpty = FALSE;
  }
  
  if (userHasPermission("deCanSwitchUsers")) {

    $pC .= $screen->drawMenuItem("javascript:switchUser();", "", "<img src='$screen->themeLocation/images/group.png' border='0'>", "Switch User");
          
    $pC .= '
			<script type="text/javascript">
				function switchUser()
				{
					var x = prompt("As an admin, you may switch to another user.  Enter their CWID below.");
					if (x)
					{
						window.location = "main.php?performAction=switchUser&switchUserID=" + x;
					}
				}
			</script>
			';
  }
  
  if (userHasPermission("deCanAdministerDataEntry")) {
    
    $pC .= $screen->drawMenuItem("main.php?performAction=clearCache&currentStudentID=$csid", "", "-", "Clear Cache");
    
    $csid = $GLOBALS["currentStudentID"];
    $draftLink = $screen->drawMenuItem("main.php?performAction=draftModeYes&currentStudentID=$csid", "", "-", "Switch to Draft Mode");
    if ($GLOBALS["boolUseDraft"] == true)
    {
      $draftLink = $screen->drawMenuItem("main.php?performAction=draftModeNo&currentStudentID=$csid", "", "-", "Switch to Regular Mode");
    }
    
    $pC .= $draftLink;
    $isEmpty = FALSE;
    
  }

  // Get all of the menu items which should appear here
  $menus = getModulesMenus();
  // Now, let's look for menu items with the location "admin_tools"...
  if (is_array($menus["admin_tools"])) {
    
    $adminToolsMenu = $screen->drawMenuItems($menus["admin_tools"]);  
    //var_dump($adminToolsMenu);
    if ($adminToolsMenu) {
      $pC .= $adminToolsMenu;
      $isEmpty = FALSE;
    }  
    
      
  }

  
  if ($isEmpty) {
    return "";
  }  

  return $pC;
}



?>