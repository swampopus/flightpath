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
 
This is a very important script.  This will manage all of the data entry
and settings of FlightPath.  To be used only by administrators.
*/

session_start();
header("Cache-control: private");

$_SESSION["deAdvancedMode"] = true;

require_once("bootstrap.inc");

 

$screen = new AdvisingScreen();
$screen->initAdvisingVariables();
// We need to do this so that our course's loadDescriptiveData function
// will load the most recent course names.

$db = new DatabaseHandler();

initHiddenVariables();

$performAction = trim($_REQUEST["performAction"]);


/*if ($_SESSION["fpDataEntryLoggedIn"] != true && $performAction != "login")
{
  displayLogin();
  die;
}

if ($performAction == "login")
{
  performLogin();
}

if ($performAction == "logout")
{
  performLogout();
  die;
}
*/

if (!userHasPermission("deCanAccessAdminConsole")) {
  displayAccessDenied("Admin Console");
}




if ($performAction == "" || $performAction == "menu")
{
  displayMainMenu();
}

if ($performAction == "editAnnouncements")
{
  displayEditAnnouncements();
}

if ($performAction == "previewAnnouncement")
{
  displayPreviewAnnouncement();
}


if ($performAction == "editUrgentMsg")
{
  displayEditUrgentMsg();
}

if ($performAction == "editOfflineMode")
{
  displayEditOfflineMode();
}


if ($performAction == "editHelp")
{
  displayEditHelp();
}

if ($performAction == "editUsers")
{
  displayEditUsers();
}


if ($performAction == "transferData")
{
  displayTransferData();
}

if ($performAction == "performTransferData")
{
  performTransferData();
}


if ($performAction == "applyDraftChanges")
{
  displayApplyDraftChanges();
}


if ($performAction == "performClearJohnDoe")
{
  performClearJohnDoe();
}


if ($performAction == "performApplyDraftChanges")
{
  performApplyDraftChanges();
}

if ($performAction == "performProcessGroupDefinitions")
{
  performProcessGroupDefinitions();
}

if ($performAction == "copyDegree")
{
  displayCopyDegree();
}

if ($performAction == "performCopyDegree")
{
  performCopyDegree();
}


if ($performAction == "addNewDegree")
{
  displayAddNewDegree();
}

if ($performAction == "performAddNewDegree")
{
  performAddNewDegree();
}


if ($performAction == "performEditAnnouncements")
{
  performEditAnnouncements();
}

if ($performAction == "performEditFlightPathSettings")
{
  performEditFlightPathSettings();
}

if ($performAction == "performEditHelp")
{
  performEditHelp();
}


if ($performAction == "performEditUrgentMsg")
{
  performEditUrgentMsg();
}

if ($performAction == "performEditOfflineMode")
{
  performEditOfflineMode();
}


if ($performAction == "editFlightPathSettings")
{
  displayEditFlightPathSettings();
}


if ($performAction == "requestTransfer")
{
  displayRequestDataTransfer();
}
if ($performAction == "performRequestTransfer")
{
  performRequestDataTransfer();
}


if ($performAction == "editDegrees")
{
  displayEditDegrees();
}

if ($performAction == "editGroups")
{
  displayEditGroups();
}

if ($performAction == "editCourses")
{
  displayEditCourses();
}


if ($performAction == "popupAddGroup")
{
  popupAddGroup();
}

if ($performAction == "popupShowGroupUse")
{
  popupShowGroupUse();
}

if ($performAction == "popupDegreesUsingCourse")
{
  popupDegreesUsingCourse();
}

if ($performAction == "popupGroupsUsingCourse")
{
  popupGroupsUsingCourse();
}

if ($performAction == "popupStudentsUsingCourse")
{
  popupStudentsUsingCourse();
}


if ($performAction == "popupEditDefinition")
{
  popupEditDefinition();
}

if ($performAction == "popupSelectIcon")
{
  popupSelectIcon();
}


if ($performAction == "editSpecificGroup")
{
  displayEditSpecificGroup();
}

if ($performAction == "editSpecificCourse")
{
  displayEditSpecificCourse();
}

if ($performAction == "editSpecificUser")
{
  displayEditSpecificUser();
}


if ($performAction == "editSpecificDegree")
{
  displayEditSpecificDegree();
}

if ($performAction == "performEditSpecificDegree")
{
  performEditSpecificDegree();
}

if ($performAction == "performEditSpecificGroup")
{
  performEditSpecificGroup();
}

if ($performAction == "performEditSpecificCourse")
{
  performEditSpecificCourse();
}

if ($performAction == "performEditSpecificUser")
{
  performEditSpecificUser();
}



if ($performAction == "performSetCatalogYear")
{
  $catalogYear = trim($_POST["catalogYear"]);
  $GLOBALS["deCatalogYear"] = $catalogYear;
  displayMainMenu("<font color='green'>Catalog Year editing set to $catalogYear.</font><br>");
}



die;


function performClearJohnDoe() {
  
  //////////////////////////////////
  // Check for hooks...
  $function = "admin_performClearJohnDoe";
  if (function_exists($function)) {
    return call_user_func($function);
  }
  //////////////////////////////////
  if (!userHasPermission("deCanAdministerDataEntry")) {
    displayAccessDenied();
  }
  
  
  
  // This function will clear the advising history for John Doe (99999999).
  // Clears advising comments, too.
  $cwid = "99999999";
  $db = new DatabaseHandler();
  $res = $db->dbQuery("SELECT * FROM advising_sessions
                       WHERE student_id = '?' ", $cwid);
  while ($cur = $db->dbFetchArray($res)) {
    $aid = $cur["advising_session_id"];
    $db->dbQuery("DELETE FROM advised_courses WHERE advising_session_id = '?' ", $aid);
  }
  
  $db->dbQuery("DELETE FROM advising_sessions WHERE student_id = '?' ", $cwid);
  $db->dbQuery("DELETE FROM advising_comments WHERE student_id = '?' ", $cwid);
  
  displayMainMenu(getSuccessMsg("Advising history and comments for John Doe successfully cleared."));
  
}



function displayPreviewAnnouncement()
{

  ///////////////////////////////////
  // Check for hooks...
  $function = "admin_displayPreviewAnnouncement";
  if (function_exists($function)) {
    return call_user_func($function);
  }   
  ///////////////////////////////////
  
  
  // This function is intended to display within a popup.  It will
  // display an announcement exactly as it will appear in FP.
  $ann = $_REQUEST["announcement"];
  $ann = urldecode($ann);

  $screen = new AdvisingScreen();
  $screen->pageIsPopup = true;

  $ann = $screen->convertBBCodeToHTML($ann);

  $pC .= "<table cellpadding='0' cellspacing='0' width='315'>
			<tr><td valign='top'>";

  // Display announcements here...
  $pC .= $screen->drawCurvedTitle("Preview Announcement...");
  $pC .= "<div class='elevenpt' style='margin-top: 20px;'>$ann
							<div align='right' class='tenpt' style='color: gray; padding-right: 10px;'>
							<i>Posted xxxx-xxxx-xxx</i>
							</div>
						</div>";

  $pC .= "</td></tr>
			</table>";



  $screen->pageContent = $pC;
  $screen->outputToBrowser();


}


function displayEditUsers($msg = "")
{
  
  //////////////////////////////////
  // Check for hooks...
  $function = "admin_displayEditUsers";
  if (function_exists($function)) {
    return call_user_func($function, $msg);
  }
  //////////////////////////////////
  if (!userHasPermission("deCanAdministerUsers")) {
    displayAccessDenied();
  }
  
  
  global $db, $screen;
  $deCatalogYear = $GLOBALS["deCatalogYear"];

  $cc = 1;

  $pC = "";


  // First, let's get our list of departments...
  $deptArray = array();
  $d = 0;
  
  // Let's pull the needed variables out of our settings, so we know what
	// to query, because this is a non-FlightPath table.
	$tsettings = $GLOBALS["fpSystemSettings"]["extraTables"]["human_resources:faculty_staff"];
	$tf = (object) $tsettings["fields"];  //Convert to object, makes it easier to work with.  
	$tableName = $tsettings["tableName"];    
  
  $res = $db->dbQuery("select distinct `$tf->deptName` from $tableName ORDER BY `$tf->deptName` ");
  if ($res)
  {
    while ($cur = $db->dbFetchArray($res))
    {
      if (trim($cur["$tf->deptName"]) == "")
      {// skip if blank
        continue;
      }
      $deptArray[$d] = trim(ucwords(strtolower($cur["$tf->deptName"])));
      $d++;
    }
  }

  $pC .= "<a class='tenpt' href='admin.php'>Back to main menu.</a><br>
			<h2>Edit Users</h2>$msg";

  $pC .= "<div class='tenpt'>
			Range: <a href='admin.php?performAction=editUsers&ur=A&lr=AZZZ'>A</a> &nbsp;
						<a href='admin.php?performAction=editUsers&ur=B&lr=BZZZ'>B</a> &nbsp; 
                       <a href='admin.php?performAction=editUsers&ur=C&lr=CZZ'>C</a> &nbsp; 
                       <a href='admin.php?performAction=editUsers&ur=D&lr=DZZ'>D</a> &nbsp; 
                       <a href='admin.php?performAction=editUsers&ur=E&lr=EZZ'>E</a> &nbsp; 
                       <a href='admin.php?performAction=editUsers&ur=F&lr=FZZ'>F</a> &nbsp; 
                       <a href='admin.php?performAction=editUsers&ur=G&lr=GZZZ'>G</a> &nbsp; 
                       <a href='admin.php?performAction=editUsers&ur=H&lr=HZZZ'>H</a> &nbsp; 
                       <a href='admin.php?performAction=editUsers&ur=I&lr=IZZZ'>&nbsp;I </a> &nbsp; 
                       <a href='admin.php?performAction=editUsers&ur=J&lr=JZZZ'>J</a> &nbsp; 
                       <a href='admin.php?performAction=editUsers&ur=K&lr=KZZZ'>K</a> &nbsp; 
                       <a href='admin.php?performAction=editUsers&ur=L&lr=LZZZ'>L</a> &nbsp; 
                       <a href='admin.php?performAction=editUsers&ur=M&lr=MZZZ'>M</a> &nbsp; 
                       <a href='admin.php?performAction=editUsers&ur=N&lr=NZZZ'>N</a> &nbsp; 
                       <a href='admin.php?performAction=editUsers&ur=O&lr=OZZZ'>O</a> &nbsp; 
                       <a href='admin.php?performAction=editUsers&ur=P&lr=PZZZ'>P</a> &nbsp; 
                       <a href='admin.php?performAction=editUsers&ur=Q&lr=RZZZ'>Q-R</a> &nbsp; 
                       <a href='admin.php?performAction=editUsers&ur=S&lr=SZZZ'>S</a> &nbsp; 
                       <a href='admin.php?performAction=editUsers&ur=T&lr=TZZZ'>T</a> &nbsp; 
                       <a href='admin.php?performAction=editUsers&ur=U&lr=VZZZ'>U-V</a> &nbsp; 
                       
                       <a href='admin.php?performAction=editUsers&ur=W&lr=WZZZ'>W</a> &nbsp; 
                       <a href='admin.php?performAction=editUsers&ur=X&lr=ZZZZZ'>X-Z</a> 
                       
                       <br><br>
                       
                       
                                        
                        <form action='admin.php' method='GET' style='margin:0px; padding:0px;'>
                        <input type='hidden' name='performAction' value='editUsers'>
                         Search: <input type='text' class='smallinput' name='search' value='$search' length='20'>
                        <input type='submit' name='searchsubmit' class='smallinput' value=' -> '>
                       
                        &nbsp; &nbsp; or &nbsp; &nbsp;
                       <select name='department' class='smallinput'>
                       	<option value=''>Select a department...</option>
                       	<option value=''>--------------------------</option>
              			<!--DEPTSEARCH-->
                       </select>
                       <input type='submit' name='deptsubmit' class='smallinput' value=' -> '>
                       
                       </form>
                       </div>
                       <br>
              				";



  $displaying = "";



  $ur = trim($_GET["ur"]);
  $lr = trim($_GET["lr"]);

  if ($ur != "" || $lr != "")
  {
    $_SESSION["prev_user_search"] = "";
  }


  if ($ur == "")
  { // meaning, no range was set.  Use A - C
    $ur = $_SESSION["ur"];
    $lr = $_SESSION["lr"];
    if ($ur == "")
    { // if still blank, assign it..
      $ur = "A";
      $lr = "AZZZZ";
    }

  }
  $_SESSION["ur"] = $ur;
  $_SESSION["lr"] = $lr;

  $search = trim($_GET["search"]);
  $dept = trim($_GET["department"]);

  if ($search != "" || $dept != "")
  {
    $_SESSION["prev_user_search"] = "";
  }


  if ($_SESSION["prev_user_search"] != "")
  {
    $temp = split("%%",$_SESSION["prev_user_search"]);
    if ($temp[0] == "search")
    {
      $search = $temp[1];
    }
    if ($temp[0] == "dept")
    {
      $_GET["deptsubmit"] = "1";
      $dept = $temp[1];
    }
  }


  $_SESSION["prev_user_search"] = "";

  
  // Let's pull the needed variables out of our settings, so we know what
	// to query, because this is a non-FlightPath table.
	$tsettings = $GLOBALS["fpSystemSettings"]["extraTables"]["human_resources:faculty_staff"];
	$tf = (object) $tsettings["fields"];  //Convert to object, makes it easier to work with.  
	$tableName = $tsettings["tableName"];  
  
  
  if ($search != "" && !($_GET["deptsubmit"]))
  {
    // Something was searched for, and the dept submit button was not pushed.
    $dept = "";
    $temp = split(" ",$search);
    $search1 = $temp[0];
    $search2 = trim($temp[1]);

    $_SESSION["prev_user_search"] = "search%%$search";
    $displaying = $search;
    $secondPart = "";
    if ($search2 != "")
    {
      // Two search terms, probably a name...
      $result = $db->dbQuery("SELECT * FROM $tableName
					WHERE  
					($tf->lName LIKE '%?%'
					AND $tf->fName LIKE '%?%')
					ORDER BY $tf->lName, $tf->fName ", $search2, $search1);

    }else {

      // One search term....
      $result = $db->dbQuery("SELECT * FROM $tableName
					WHERE $tf->facultyID LIKE '%?%' 
					OR $tf->lName LIKE '%?%'
					OR $tf->fName LIKE '%?%'  
					ORDER BY $tf->lName, $tf->fName ", $search1, $search1, $search1);
    }

  }
  else if ($dept != "" && $_GET["deptsubmit"])
  {
    // User select a department.  Look for it...
    $search = "";
    $_SESSION["prev_user_search"] = "dept%%$dept";
    $result = $db->dbQuery("SELECT * FROM $tableName
					WHERE $tf->deptName = '?' 
					ORDER BY $tf->lName, $tf->fName ", $dept);
    $displaying = $dept;
  }
  else
  { // No search, so look for the range...
    $result = $db->dbQuery("SELECT * FROM $tableName
                        WHERE 
                        $tf->lName BETWEEN '?' AND '?'
                        ORDER BY $tf->lName, $tf->fName ", $ur, $lr);
    $displaying = $ur;
  }
  $pC .= "<div class='tenpt' style='padding-bottom: 5px;'><b>Displaying:</b> $displaying</div>
			<table border='0' width='100%' cellpadding='3' cellspacing='0'>
		
				<tr class='tenpt'>
					<td><b>CWID</b></td>
					<td><b>Name</b></td>
					<td></td>
					<td><b>Department</b></td>
					<td><b>User Type</b></td>
				</tr>
		
		";
  while ($cur = $db->dbFetchArray($result))
  {

    $l_name = trim(ucwords(strtolower($cur[$tf->lName])));
    $f_name = trim(ucwords(strtolower($cur[$tf->fName])));
    $mid_name = trim(ucwords(strtolower($cur[$tf->midName])));
    $faculty_id = trim($cur[$tf->facultyID]);
    $dept_name = trim(ucwords(strtolower($cur[$tf->deptName])));
        
    // Now, we find out this person's user type...
    $user_type = determineStaffUserType($faculty_id);


    $ast = "";
    $reason = "";


    $fgcol = "black";

    if ($user_type == "")
    { // user is nothing.
      $fgcol = "gray";
    }
    if ($user_type == "college_coordinator")
    { // user is a substitutor
      $fgcol = "red";
    }



    //			$pC .= "<a href='edit_users.php?action=load&course_id=$course_id'> user: $f_name $mid_name $l_name </a><br>";

    $pC .= "<tr
					onmouseover=\"style.backgroundColor='#FFFF99'\"
      				onmouseout=\"style.backgroundColor='$bgcol'\"
					class='hand tenpt'
					style='color: $fgcol'
					onClick='window.location=\"admin.php?performAction=editSpecificUser&facultyID=$faculty_id&userType=$user_type\";'
      			>
      				
      				<td valign='top' width='15%'>$faculty_id</td>
					<td valign='top' width='15%'>$f_name</td>
					<td valign='top' width='15%'>$l_name</td>
					<td valign='top'>$dept_name</td>
					<td valign='top'>$user_type</td>
					
					
					
				</tr>";

  } // while
  $pC .= "</table>";



  // Put in the dept pulldown....
  $bC = "";
  for ($t = 0; $t<count($deptArray); $t++)
  {
    $dd = $deptArray[$t];

    $sel = "";
    if ($dd == $dept)
    {
      $sel = "selected";
    }
    $bC .= "<option value='$dd' $sel>{$deptArray[$t]}</option> \n";
  }
  $pC = str_replace("<!--DEPTSEARCH-->",$bC,$pC);


  $screen->pageTitle = "FlightPath Admin - Users";
  $screen->pageHideReportError = true;
  $screen->pageContent = $pC;
  // send to the browser
  $screen->outputToBrowser();



}


function performApplyDraftChanges()
{
  
  //////////////////////////////////
  // Check for hooks...
  $function = "admin_performApplyDraftChanges";
  if (function_exists($function)) {
    return call_user_func($function);
  }
  //////////////////////////////////
  if (!userHasPermission("deCanAdministerDataEntry")) {
    displayAccessDenied();
  }
  
  
  // This function will "apply" the changes in the draft tables
  // to the production tables.
  global $db;
  // Check to make sure they entered the transfer passcode correctly.
  if ($_POST["passcode"] != $GLOBALS["fpSystemSettings"]["adminTransferPasscode"])
  {
    displayApplyDraftChanges("<font color='red'>ERROR.  Transfer passcode incorrect.  Check with the FlightPath administrator
								to learn the passcode.</font>");
    die;

  }

  // Save the entire post to the log.
  $postXML = fp_arrayToXml("post",$_POST, true);
  $db->addToLog("admin_perform_apply_draft_changes","begin",$postXML);


  // First, set maintenance mode...
  $db->setMaintenanceMode("1");

  // Okay, so what we gotta do is truncate the production tables,
  // then copy the draft tables in.
  $tableArray = array(
  "courses",
  "degree_requirements",
  "degree_tracks",
  "degrees",
  "group_requirements",
  "groups",
  );

  foreach($tableArray as $tableName)
  {
    $draftTableName = "draft_$tableName";
    $db->addToLog("admin_perform_apply_draft_changes","$tableName,$draftTableName");
    // First, truncate existing...
    $query = "truncate table $tableName";
    $res = $db->dbQuery($query);
    // Now, copy in draft changes...
    $query = "INSERT INTO $tableName
						SELECT * FROM $draftTableName ";
    $res = $db->dbQuery($query);
  }

  $db2 = new DatabaseHandler();
  // Now, we need to go through the draft_instructions table,
  // and perform each instruction one at a time.
  $res = $db->dbQuery("SELECT * FROM draft_instructions
						ORDER BY `id` ");
  while($cur = $db->dbFetchArray($res))
  {
    $instruction = trim($cur["instruction"]);
    $db2->addToLog("admin_perform_apply_draft_changes",$instruction);

    $temp = explode(",",$instruction);

    if (trim($temp[0]) == "updateCourseID")
    {
      $db2->updateCourseID(trim($temp[1]), trim($temp[2]));
    }

    if (trim($temp[0]) == "updateCourseRequirementFromName")
    {
      $db2->updateCourseRequirementFromName(trim($temp[1]), trim($temp[2]), trim($temp[3]));
    }
  }

  // Once this is done, truncate the draft_instructions table.
  $res = $db->dbQuery("TRUNCATE TABLE draft_instructions");


  // And we are done!  Set maintenance mode back to 0.
  $db->setMaintenanceMode("0");
  $db->addToLog("admin_perform_apply_draft_changes","finished");

  // Send emails to notify programmers...
  $notify = $GLOBALS["fpSystemSettings"]["notifyApplyDraftChangesEmailAddress"];
  if ($notify)
  {
    mail($notify, "FlightPath Apply Draft Changes", "Someone has applied draft changes to FlightPath, which updated degree plans, groups, and courses.");
  }
  // Send us back to the ApplyDraftChanges screen...
  displayApplyDraftChanges(getSuccessMsg("Successfully updated the production database with draft changes at " . getCurrentTime() . ". Your changes are now live and visible on production."));

}



function performEditHelp()
{
  
  //////////////////////////////////
  // Check for hooks...
  $function = "admin_performEditHelp";
  if (function_exists($function)) {
    return call_user_func($function);
  }
  //////////////////////////////////
  if (!userHasPermission("deCanAdministerDataEntry")) {
    displayAccessDenied();
  }
  
  
  global $db;
  $pageID = trim($_POST["pageID"]);
  if ($pageID == "new")
  {
    // Add a new page to the help system...
    $res = $db->dbQuery("INSERT INTO help(`title`)
								values ('') ");
    $pageID = $db->dbInsertID();
    $_POST["pageID"] = $pageID;
  }

  // Save the entire post to the log.
  $postXML = fp_arrayToXml("post",$_POST, true);
  $db->addToLog("admin_edit_help","$pageID",$postXML);


  $title = trim($_POST["title"]);
  $body = trim($_POST["body"]);

  $res = $db->dbQuery("UPDATE help
							SET `title`='?',
								`body`='?'
							WHERE `id`='?' ", $title, $body, $pageID);
  displayEditHelp(getSuccessMsg("Successfully updated Help page at " . getCurrentTime()));

}



/*
function performLogin()
{
  
  //////////////////////////////////
  // Check for hooks...
  $function = "admin_performLogin";
  if (function_exists($function)) {
    return call_user_func($function);
  }
  //////////////////////////////////
  
  
  // Is the user logging in a valid faculty member, and in the administrator's table in FP?
  $userID = trim($_POST["userID"]);
  $password = trim($_POST["password"]);

  if (!($userID = fp_verifyAllFacultyLogins($userID, $password)))
  {
    $msg = "<div style='color:red'>Your username/password combination is not valid.  Check your spelling
						and try again.</div>";
    displayLogin($msg);
    die;
  }

  // They made it through, but are they in the administrator's table?
  $db = new DatabaseHandler();
  $res = $db->dbQuery("SELECT * FROM administrators WHERE `faculty_id`='$userID' ");
  if ($db->dbNumRows($res) == 0)
  {
    $msg = "<div style='color:red'>You do not have access to Data Entry.  Only System Administrators
							may log in.</div>";
    displayLogin($msg);
    die;
  }

  // If we are here, then we have access.  Log us on in.
  $_SESSION["fpDataEntryLoggedIn"] = true;
  $db->addToLog("admin_login");
  displayMainMenu();
  die;

}

function performLogout()
{
  
  //////////////////////////////////
  // Check for hooks...
  $function = "admin_performLogout";
  if (function_exists($function)) {
    return call_user_func($function);
  }
  //////////////////////////////////
  
  
  $db = new DatabaseHandler();
  $_SESSION["fpDataEntryLoggedIn"] = false;
  $msg = "<div style='color:green'>
				You have been logged out of Data Entry, but not FlightPath.  If you are on a computer which
				is not your own, 
				you should log out of FlightPath as well and close the web browser for added security.
							</div>"; 
  displayLogin($msg);
  $db->addToLog("admin_logout");
  die;

}


function displayLogin($msg = "")
{
  
  //////////////////////////////////
  // Check for hooks...
  $function = "admin_displayLogin";
  if (function_exists($function)) {
    return call_user_func($function, $msg);
  }
  //////////////////////////////////
  
  
  // This is the login page for Data Entry
  $screen = new AdvisingScreen();

  //$pC .= $screen->getJavascriptCode();

  $pC .= "
 		<form action='admin.php' method='POST'>
 		<input type='hidden' name='performAction' value='login'>
 		$msg
 		Please login to the FlightPath Admin Console using your Administrator's username and password.
          <table>
            <tr><td><b>USER:</b></td><td><input type=text name='userID' id='cwid_box'></td></tr>
            <tr><td><b>PASS:</b></td><td><input type='password' name='password'></td></tr>
          </table>
          <input type='submit' value='Login -->'>
          
          
</form>

  		"; 

  
  $screen->pageContent = $pC;
  $screen->pageHasSearch = false;
  $screen->pageOnLoad = "document.getElementById(\"cwid_box\").focus(); ";
  // send to the browser
  $screen->outputToBrowser();


}
*/


function displayEditSpecificUser($msg = "")
{
  
  //////////////////////////////////
  // Check for hooks...
  $function = "admin_displayEditSpecificUser";
  if (function_exists($function)) {
    return call_user_func($function, $msg);
  }
  //////////////////////////////////
  if (!userHasPermission("deCanAdministerUsers")) {
    displayAccessDenied();
  }
  
  global $screen, $db;
  $pC = "";

  $pC .= "<a class='tenpt' href='admin.php?performAction=editUsers'>Back to Users list</a>
			&nbsp; &nbsp; - &nbsp; &nbsp;
		<a class='tenpt' href='admin.php'>Back to main menu</a>
			$msg";


  $facultyID = trim($_REQUEST["facultyID"]);
  $userType = trim($_REQUEST["userType"]);
  //$myurl = trim($_GET["myurl"]);

  // Get faculty member details...
  
  // Let's pull the needed variables out of our settings, so we know what
	// to query, because this is a non-FlightPath table.
	$tsettings = $GLOBALS["fpSystemSettings"]["extraTables"]["human_resources:faculty_staff"];
	$tf = (object) $tsettings["fields"];  //Convert to object, makes it easier to work with.  
	$tableName = $tsettings["tableName"];     
  
  $result = $db->dbQuery("SELECT * FROM $tableName
                        WHERE 
                        $tf->facultyID = '?' ", $facultyID) ;
  $cur = $db->dbFetchArray($result);

  $l_name = trim(ucwords(strtolower($cur[$tf->lName])));
  $f_name = trim(ucwords(strtolower($cur[$tf->fName])));
  $mid_name = trim(ucwords(strtolower($cur[$tf->midName])));
  $dept_name = trim(ucwords(strtolower($cur[$tf->deptName])));


  $advisees = "";


  // Get the list of advisees.
  
  // Let's pull the needed variables out of our settings, so we know what
	// to query, because this is a non-FlightPath table.
	$tsettings = $GLOBALS["fpSystemSettings"]["extraTables"]["human_resources:advisor_student"];
	$tfa = (object) $tsettings["fields"];  //Convert to object, makes it easier to work with.  
	$tableName_a = $tsettings["tableName"];     
  
	$tsettings = $GLOBALS["fpSystemSettings"]["extraTables"]["human_resources:students"];
	$tfb = (object) $tsettings["fields"];  //Convert to object, makes it easier to work with.  
	$tableName_b = $tsettings["tableName"];     

	$res = $db->dbQuery("SELECT * FROM $tableName_a a, $tableName_b b
						WHERE a.$tfa->facultyID = '?' 
						AND a.$tfa->studentID = b.$tfb->studentID
							ORDER BY $tfb->majorCode, $tfb->lName, $tfb->fName
						", $facultyID);
  while ($cur2 = $db->dbFetchArray($res))
  {
    $name = ucwords(strtolower($cur2[$tfb->fName] . " " . $cur2[$tfb->lName]));
    $advisees .= trim($cur2[$tfb->studentID]) . " {$cur2[$tfb->majorCode]}   $name \n";
  }




  //$pC .= draw_curved_title("Edit User");

  //$catname = getCatalogName();

  $sel[$userType] = "selected";
  //{$sel["none"]}
  $pC .= "
         
     <form action='admin.php' method='POST' style='margin-top: 5px;'>
     <input type='hidden' name='performAction' value='performEditSpecificUser'>
     

	User: &nbsp; <b>$f_name $mid_name $l_name ($facultyID)</b>
   &nbsp; &nbsp; &nbsp; &nbsp; Department: &nbsp; <b>$dept_name</b>
   <br>
   Current user type: <b>$userType</b>
    
   
   <input type='hidden' name='facultyID' value='$facultyID'>
    <br>
    
    

    
    <hr noshade width='95%' color='gray'><br>

<div class='tenpt'>    
   User Type: 
   <select id='select_user_type' name='userType'>
   	<option id='none' value='none' {$sel["none"]}>none</option>
   	<option id='limited_faculty_student' value='limited_faculty_student' {$sel["limited_faculty_student"]}>limited faculty-student</option>
   	<option id='viewer' value='viewer' {$sel["viewer"]}>viewer</option>
   	<option id='advisor' value='advisor' {$sel["advisor"]}>advisor</option>
   	<option id='college_coordinator' value='college_coordinator' {$sel["college_coordinator"]}>college_coordinator</option>
   </select>

     
    &nbsp; &nbsp;
    <a href='javascript: popupAlertHelp(\"user_types\");'>(Help - User Types)</a>
    
    
   <br><br>
    
   Advisees:
     <br>
   <textarea id='textarea_description' name='description' readonly=readonly style='background-color: lightgrey;' cols='50' rows='4' >$advisees</textarea>
	<br>
   Extra Permissions:
   <br>";
  
  
  // Lets get an array of all available permissions from our various
  // modules.
  $allPerms = getModulesPermissions();
    
  foreach ($allPerms as $module => $val) {    
    $moduleName = $GLOBALS["fpSystemSettings"]["modules"][$module]["name"];
    if (!$moduleName) $moduleName = $module;
    
    $pC .= "<div class='fp-user-management-permission-module-name'>$moduleName</div>";
    
    foreach ($allPerms[$module] as $permData) {
    
      foreach ($permData as $permName => $permValues) {
        $checked = $cval = " ";
        
        if (userHasPermission($permName, $facultyID)) {
          $checked = "checked";
          $cval = "X";
        }
          
        // Does the user editing this user have this permission?  If not,
        // they may not grant it for others!
        if (userHasPermission($permName)) {
          $pC .= "<div class='fp-user-management-permission'>
                  <label><input type='checkbox' name='perm~~_~~$permName' value='yes' $checked>{$permValues["title"]}</label>
                  <div class='fp-user-management-permission-line'>{$permValues["description"]}</div>
                  </div>";       
        }
        else {
          
          $pC .= "<div class='fp-user-management-permission fp-user-management-permission-disabled'>
                  <div class='fp-user-management-permission-disabled-msg'>
                    You may not edit this permission for this user, as you do
                    not have this permission yourself.
                  </div>
                  <label>[$cval] {$permValues["title"]}</label>
                  <div class='fp-user-management-permission-line'>{$permValues["description"]}</div>
                  </div>";       
          
        }
        
      }
      
    }    
  }
  
  /*
  $pciChecked = "";
  $res = $db->dbQuery("SELECT * FROM users WHERE
						`faculty_id`='$facultyID' ");
  $cur = $db->dbFetchArray($res);
  if (stristr($cur["permissions"], "deCanUpdateCourseInfo"))
  {
    $pciChecked = "checked";
  }
  
  $pC .= "
   &nbsp; &nbsp; <input type='checkbox' name='perm_course_info' value='yes' $pciChecked> Can access Course Info System (to edit syllabi/schedules)
   
   </div>
   ";
  */
  
  $pC .= "
   <br>
   <input type='submit' value='Save ->'>
   </form>
   
   ";

  $pC .= getJS();


  $screen->pageTitle = "FlightPath Admin - Edit User";
  $screen->pageHideReportError = true;
  $screen->pageContent = $pC;
  // send to the browser
  $screen->outputToBrowser();


}



function displayEditSpecificCourse($msg = "", $boolScroll = true)
{
  
  //////////////////////////////////
  // Check for hooks...
  $function = "admin_displayEditSpecificCourse";
  if (function_exists($function)) {
    return call_user_func($function, $msg, $boolScroll);
  }
  //////////////////////////////////
  if (!userHasPermission("deCanAdministerDataEntry")) {
    displayAccessDenied();
  }
  
  
  global $screen, $db;
  $deCatalogYear = $GLOBALS["deCatalogYear"];
  $courseID = $_REQUEST["courseID"];
  $subjectID = $_REQUEST["subjectID"];
  $courseNum = $_REQUEST["courseNum"];

  $subjectID = str_replace("_A_","&",$subjectID);

  $pC .= "<a class='tenpt' href='admin.php?performAction=editCourses&deCatalogYear=$deCatalogYear#course_$courseID'>Back to Course List</a>  &nbsp; - &nbsp;
			<a class='tenpt' href='admin.php?deCatalogYear=$deCatalogYear'>Back to main menu.</a>
			";
  if ($_SESSION["deAdvancedMode"] == true)
  {
    $pC .= " <span class='tenpt' style='background-color: yellow; margin-left: 20px;'>
					adv: courseID = $courseID. Used by:
					<a href='javascript: popupWindow(\"admin.php?performAction=popupDegreesUsingCourse&courseID=$courseID\")'>[degrees]</a>
					<a href='javascript: popupWindow(\"admin.php?performAction=popupGroupsUsingCourse&courseID=$courseID\")'>[groups]</a>
					<a href='javascript: popupWindow(\"admin.php?performAction=popupStudentsUsingCourse&courseID=$courseID\")'>[students]</a>
				  </span>";
  }

  $course = new Course($courseID,false,null,false,$deCatalogYear, true);
  //adminDebug($course->description);
  $course->catalogYear = $deCatalogYear;  // Since it may be 1900, force it!
  $course->loadDescriptiveData(false, true, false, true, true);



  $pC .= "<h2>Edit Course $subjectID $courseNum ($deCatalogYear)</h2>$msg";

  $pC .= "<form id='mainform' action='admin.php' method='POST'>
			<input type='hidden' name='performAction' value='performEditSpecificCourse'>
			<input type='hidden' name='courseID' value='$courseID'>
			
			<input type='hidden' name='subjectID' value='$subjectID'>
			<input type='hidden' name='courseNum' value='$courseNum'>
			
			";
  $course->catalogYear = $deCatalogYear;  // Since it may be 1900, force it!
  $pC .= getHiddenVariables();
  $allNames = $course->getAllNames(true);
  $warnEqv = "";
  if (strstr($allNames, ","))
  {
    $warnEqv = "yes";
  }
  
  // Correct ghosthours, if they exist.
  if ($course->boolGhostHour) {
    $course->maxHours = 0;
  }
  if ($course->boolGhostMinHour) {
    $course->minHours = 0;
  }
  
  
  
  $pC .= "<table border='0' cellspacing='5'>
			<tr>
				<td valign='top' class='tenpt'>
					Course name(s):
				</td>
				<td valign='top' class='tenpt'>
					<input type='text' name='courseNames' value='$allNames' size='60'>
					<a href='javascript: popupAlertHelp(\"course_names\");'>?</a> 	
				</td>
			</tr>
			<tr>
				<td valign='top' class='tenpt'>
					Title:
				</td>
				<td valign='top' class='tenpt'>
					<input type='text' name='title' value='$course->title' size='60'>
					<a href='javascript: popupAlertHelp(\"course_title\");'>?</a> 	
				</td>
			</tr>
			<tr>
				<td valign='top' class='tenpt'>
					Min hours:
				</td>
				<td valign='top' class='tenpt'>
					<input type='text' name='minHours' value='$course->minHours' size='5'>
					<a href='javascript: popupAlertHelp(\"course_min_hours\");'>?</a> 	
				</td>
			</tr>
			<tr>
				<td valign='top' class='tenpt'>
					Max hours:
				</td>
				<td valign='top' class='tenpt'>
					<input type='text' name='maxHours' value='$course->maxHours' size='5'>
					<a href='javascript: popupAlertHelp(\"course_max_hours\");'>?</a> 	
				</td>
			</tr>
			<tr>
				<td valign='top' class='tenpt'>
					Repeat hours:
				</td>
				<td valign='top' class='tenpt'>
					<input type='text' name='repeatHours' value='$course->repeatHours' size='5'>
					<a href='javascript: popupAlertHelp(\"course_repeat_hours\");'>?</a> 	
				</td>
			</tr>
			<!--
			<tr>
				<td valign='top' class='tenpt'>
					Exclude:
				</td>
				<td valign='top' class='tenpt'>
					<input type='text' name='exclude' value='$course->dbExclude' size='2'>
					<a href='javascript: popupAlertHelp(\"course_exclude\");'>?</a> [Default = 0]
				</td>
			</tr>
			-->
			</table>
			<div class='tenpt'>Description:
			<textarea name='description' rows='4' cols='80'>$course->description</textarea>			
			<br>
			<input type='button' value='Save for $deCatalogYear' onClick='submitForm();'>
			   <input type='checkbox' name='allYears' value='yes'> Update all years for this course. 
			       <a href='javascript: popupAlertHelp(\"all_years\");'>?</a>
			<br><br>
			<b>[Optional]</b> Comment: (only seen by data entry administrators)<br>
			<textarea name='dataEntryComment' rows='3' cols='80'>$course->dataEntryComment</textarea>
			<br>
				<div align='right'>
					Delete this course for $deCatalogYear? <input type='button' value='X'
									onClick='deleteCourse(\"$courseID\",\"$deCatalogYear\",\"$warnEqv\");'>
				</div>			
			</div>
			
			
			";

  $pC .= "
			</form>";

  $pC .= getJS();
  $screen->pageTitle = "FlightPath Admin - Edit Course";

  if ($boolScroll)
  {
    $screen->pageScrollTop = trim($_POST["scrollTop"]);
  }
  $screen->pageHideReportError = true;
  //include("template/fp_template.php");

  $screen->pageContent = $pC;
  // send to the browser
  $screen->outputToBrowser();


  if ($_REQUEST["serialize"] != "")
  {
    print "<br><textarea rows=20 cols=80>" . serialize($course) . "</textarea>";
  }


}


function displayEditCourses($msg = "")
{
  //////////////////////////////////
  // Check for hooks...
  $function = "admin_displayEditCourses";
  if (function_exists($function)) {
    return call_user_func($function, $msg);
  }
  //////////////////////////////////
  if (!userHasPermission("deCanAdministerDataEntry")) {
    displayAccessDenied();
  }
  
  
  global $db, $screen;
  $deCatalogYear = $GLOBALS["deCatalogYear"];

  $pC = "";



  $cc = 1;

  $show_hidden = trim($_GET["show_hidden"]);
  if ($show_hidden != "")
  {
    $_SESSION["dehidden"] = $show_hidden;
  } else {
    $show_hidden = $_SESSION["dehidden"];
  }

  // Get the "upper range" (UR) and
  // lower range (LR) of the courses in question...
  $ur = trim($_GET["ur"]);
  $lr = trim($_GET["lr"]);
  if ($ur == "")
  { // meaning, no range was set.  Use A - AZZZZZ, so, all of the A's.
    $ur = $_SESSION["dec_ur"];
    $lr = $_SESSION["dec_lr"];
    if ($ur == "")
    { // if still blank, assign it..
      $ur = "A";
      $lr = "AZZZZ";
    }

  }
  $_SESSION["dec_ur"] = $ur;
  $_SESSION["dec_lr"] = $lr;


  $pC .= "<a class='tenpt' href='admin.php?deCatalogYear=$deCatalogYear'>Back to main menu.</a><br>
			<h2>Edit Courses for $deCatalogYear</h2>$msg
			";

  $pC .= "<div style='background-color: beige; margin-bottom:10px; text-align: center; padding: 3px;'>
			<a href='admin.php?deCatalogYear=$deCatalogYear&performAction=editCourses&ur=A&lr=AZZZ'>A</a> &nbsp;
						<a href='admin.php?deCatalogYear=$deCatalogYear&performAction=editCourses&ur=B&lr=BZZZ'>B</a> &nbsp; 
                       <a href='admin.php?deCatalogYear=$deCatalogYear&performAction=editCourses&ur=C&lr=CNZZ'>C-CN</a> &nbsp; 
                       <a href='admin.php?deCatalogYear=$deCatalogYear&performAction=editCourses&ur=CO&lr=CZZZ'>CO-CZ</a> &nbsp; 
                       <a href='admin.php?deCatalogYear=$deCatalogYear&performAction=editCourses&ur=D&lr=DZZZ'>D</a> &nbsp; 
                       <a href='admin.php?deCatalogYear=$deCatalogYear&performAction=editCourses&ur=E&lr=EZZZ'>E</a> &nbsp; 
                       <a href='admin.php?deCatalogYear=$deCatalogYear&performAction=editCourses&ur=F&lr=FZZZ'>F</a> &nbsp; 
                       <a href='admin.php?deCatalogYear=$deCatalogYear&performAction=editCourses&ur=G&lr=GZZZ'>G</a> &nbsp; 
                       <a href='admin.php?deCatalogYear=$deCatalogYear&performAction=editCourses&ur=H&lr=HZZZ'>H</a> &nbsp; 
                       <a href='admin.php?deCatalogYear=$deCatalogYear&performAction=editCourses&ur=I&lr=LZZZ'>I-L</a> &nbsp; 
                       <a href='admin.php?deCatalogYear=$deCatalogYear&performAction=editCourses&ur=M&lr=MRZZZ'>M-MR</a> &nbsp; 
                       <a href='admin.php?deCatalogYear=$deCatalogYear&performAction=editCourses&ur=MS&lr=MZZZ'>MS-MZ</a> &nbsp; 
                       <a href='admin.php?deCatalogYear=$deCatalogYear&performAction=editCourses&ur=N&lr=OZZZ'>N-O</a> &nbsp; 
                       <a href='admin.php?deCatalogYear=$deCatalogYear&performAction=editCourses&ur=P&lr=PZZZ'>P</a> &nbsp; 
                       <a href='admin.php?deCatalogYear=$deCatalogYear&performAction=editCourses&ur=Q&lr=RZZZ'>Q-R</a> &nbsp; 
                       <a href='admin.php?deCatalogYear=$deCatalogYear&performAction=editCourses&ur=S&lr=SOZZZ'>S-SO</a> &nbsp;
                       <a href='admin.php?deCatalogYear=$deCatalogYear&performAction=editCourses&ur=SP&lr=SPZZZ'>SP-SZ</a> &nbsp;
                       <a href='admin.php?deCatalogYear=$deCatalogYear&performAction=editCourses&ur=T&lr=ZZZZZ'>T-Z</a>
				</div>                       
        <div class='tenpt'>			
                       Missing a course?  <a href='admin.php?deCatalogYear=$deCatalogYear&performAction=editSpecificCourse&courseID=new'>Click Here to Add a Course</a><br>
                       
                       Legend:  <br>&nbsp; &nbsp;[e] = Course has at least one add'l excluded name.  
                       			&nbsp; &nbsp;[v] = Course has at least one add'l valid name.
                       			
         </div>

         ";

  $excludeLine = "and exclude != 1";
  if ($show_hidden == "yes")
  {
    $pC .= "<div class='tenpt'><b>Showing excluded courses.
                  <a href='admin.php?deCatalogYear=$deCatalogYear&performAction=editCourses&show_hidden=no'>Hide?</a>
        </b></div>";
    $excludeLine = "";

  }  else {
    $pC .= "<div class='tenpt hypo'><b>Hiding excluded courses.
              <a href='admin.php?deCatalogYear=$deCatalogYear&performAction=editCourses&show_hidden=yes'>Show?</a>
              </b></div>";
  }

  $pC .= "<hr><br>
          <table border='0' cellpadding='3' cellspacing='0'>";



  $q = "SELECT * FROM draft_courses
                        WHERE 
                        `catalog_year`='?' and
                        `subject_id` BETWEEN '?' AND '?'
                        AND `delete_flag`='0'
                        $excludeLine
                        ORDER BY `subject_id`, `course_num`";
  $result = $db->dbQuery($q, $deCatalogYear, $ur, $lr);
  while ($cur = $db->dbFetchArray($result))
  {
    extract($cur, 3, "db");

    $exNames = "";
    $valNames = "";
    // Check to see if this course has more than one name...
    // removed AND `catalog_year`='$deCatalogYear' from query,
    // because we don't care what other cat year it came from.
    $res2 = $db->dbQuery("SELECT * FROM draft_courses
									WHERE `course_id`='?'
										", $db_course_id);
    while ($cur2 = $db->dbFetchArray($res2))
    {
      if ($cur2["subject_id"] == $db_subject_id && $cur2["course_num"] == $db_course_num)
      {
        continue;
      }

      /*				if ($db_subject_id == "CMST")
      {
      adminDebug("$db_subject_id $db_course_num : " . $cur2["subject_id"] . " " . $cur2["course_num"] . " ex:" . $cur2["exclude"] . " cy: " . $cur2["catalog_year"]);
      }
      */

      if ($cur2["exclude"] == "1")
      {
        $exNames = "[e]";
      } else {
        $valNames = "[v]";
      }

    }


    $spanstyle = "";

    if ($db_exclude == "1")
    {
      $spanstyle = "background-color: lightgrey;";
    }

    $tempCourse = new Course();
    $db_title = $tempCourse->fixTitle($db_title);

    $hrs = $db_min_hours;
    if (trim($db_min_hours) != trim($db_max_hours))
    {
      $hrs .= " - $db_max_hours";
    }

    $hrs .= " hrs.";

    $repHours = "";
    if ($db_repeat_hours > $db_min_hours)
    {
      $repHours = " rep to $db_repeat_hours hrs.";
    }

    // remove special chars from subject_id...
    $displaySubjectID = $db_subject_id;
    $db_subject_id = str_replace("&","_A_",$db_subject_id);


    $pC .= "<tr style='$spanstyle'>
					<td valign='top' width='90%'><a name='course_$db_course_id'></a>";
    $pC .= "<div style='$spanstyle padding:3px;'><a href='admin.php?performAction=editSpecificCourse&courseID=$db_course_id&subjectID=$db_subject_id&courseNum=$db_course_num&deCatalogYear=$deCatalogYear'>$displaySubjectID $db_course_num - $db_title</a> - $hrs$repHours</div>";

    $pC .= "</td>
					<td valign='top' width='5%'>
					$exNames
					</td>
					
					<td valign='top' width='5%'>
					$valNames
					</td>
				</tr>";

  } // while

  $pC .= "</table>";


  $screen->pageTitle = "FlightPath Admin - Courses";
  $screen->pageHideReportError = true;
  //include("template/fp_template.php");

  $screen->pageContent = $pC;
  // send to the browser
  $screen->outputToBrowser();


}


function performEditSpecificCourse()
{
  //////////////////////////////////
  // Check for hooks...
  $function = "admin_performEditSpecificCourse";
  if (function_exists($function)) {
    return call_user_func($function);
  }
  //////////////////////////////////
  if (!userHasPermission("deCanAdministerDataEntry")) {
    displayAccessDenied();
  }
  
  
  global $db;
  $deCatalogYear = $GLOBALS["deCatalogYear"];
  $courseID = trim($_REQUEST["courseID"]);
  $courseNames = trim($_POST["courseNames"]);

  if ($courseNames == "")
  {
    $courseNames = $_POST["subjectID"] . " " . $_POST["courseNum"];
  }

  $title = trim($_POST["title"]);
  $minHours = trim($_POST["minHours"]);
  $maxHours = trim($_POST["maxHours"]);
  $repeatHours = trim($_POST["repeatHours"]);
  //$exclude = trim($_POST["exclude"]);
  $description = trim($_POST["description"]);
  $dataEntryComment = trim($_POST["dataEntryComment"]);

  // Save the entire post to the log.
  $postXML = fp_arrayToXml("post",$_POST, true);
  $db->addToLog("admin_edit_course","$courseID,$courseNames",$postXML);

  // Since we are making a change to the draft table(s), let's add a row
  // to draft instructions.
  $db->addDraftInstruction("-");



  // Unlike the degrees and the groups, courseIDs are not
  // unique.  Only a courseID + catalogYear pair are unique.  This
  // is so we can handle equivalent courses more gracefully.

  // So, the first thing we need to do is delete *all* courses with the
  // courseID and catalogYear listed above.  For most courses, this will
  // only be one row.  For eqv courses, this will delete more than one row.
  if ($courseID != "new")
  {
    // Don't delete!  Temporarily transfer to a temporary courseID.
    // Will possibly delete later.

    
    
    $res = $db->dbQuery("UPDATE draft_courses
				  SET `course_id`='-12345'
				  WHERE `course_id`='?'
				AND `catalog_year`='?' ", $courseID, $deCatalogYear);
  }


  if ($_POST["performAction2"] == "delete_course")
  {
    // That's it.  All we wanted to do was delete the course.
    $query = "DELETE FROM draft_courses
				  WHERE `course_id`='-12345'
					";
    //debugCT($query);
    $res = $db->dbQuery($query);
    displayEditCourses("<div><font color='green' size='4'>Course <i>$courseNames</i> successfully deleted for $deCatalogYear.</font></div>");
    die;
  }

  // If the $courseID == new then create a new one.
  if ($courseID == "new")
  {
    $courseID = $db->requestNewCourseID();
    $_POST["courseID"] = $courseID;
    $_GET["courseID"] = $courseID;
    $_REQUEST["courseID"] = $courseID;
    //debugCT("new course ID is $courseID. courseNames: [$courseNames" . "]");
  }




  // Now, we will split the courseNames on commas, and for each
  // token, we will insert a row into the database.
  $courses = split(",", $courseNames);
  foreach($courses as $course)
  {
    $course = str_replace("  ", " ", $course);
    $course = str_replace("  ", " ", $course);
    $course = str_replace("  ", " ", $course);
    $course = trim($course);
    if ($course == "") { continue; }

    $temp = split(" ", $course);
    $subjectID = trim($temp[0]);
    $courseNum = trim($temp[1]);

    ////////////
    ///  Error conditions...
    if (strtolower($courseNum) == "exclude")
    {
      $errors .= "<div style='color:red;'>
						It appears you specified an excluded course
						without a course number.  You entered <b>$subjectID $courseNum</b>.
						Notice there is no course number. Please re-enter.
						</div>";
      continue;
    }

    if ($courseNum == "")
    {
      $errors .= "<div style='color:red;'>
						It appears you specified a course
						without a course number.  You entered <b>$subjectID $courseNum</b>.
						Notice there is no course number. Please re-enter.
						</div>";
      continue;
    }
    ////////////////

    $exclude = 0;
    // Do we exclude?
    if (strtolower(trim($temp[2])) == "exclude")
    {
      $exclude = 1;
      // Set ALL courses with this subjectID and courseNum to exclude!
      $res = $db->dbQuery("UPDATE draft_courses
								SET `exclude`='1'
								WHERE `subject_id`='?'
								AND `course_num`='?' 
								", $subjectID, $courseNum);


    } else {
      // Aet all courses with this subjectID and courseNum to NOT exclude!
      $res = $db->dbQuery("UPDATE draft_courses
								SET `exclude`='0'
								WHERE `subject_id`='?'
								AND `course_num`='?' 
								", $subjectID, $courseNum);

    }

    // Did the user specify a course which already exists?  If so,
    // mark that course's ID as -12345...
    $res = $db->dbQuery("UPDATE draft_courses
								SET `course_id`='-12345'
								WHERE `subject_id`='?'
								AND `course_num`='?' 
								AND `catalog_year`='?' ", $subjectID, $courseNum, $deCatalogYear);



    // We now have enough information to make an insertion into
    // the table.
    $query = "INSERT INTO draft_courses
					(`course_id`,`subject_id`,`course_num`,`catalog_year`,
						`title`,`description`,`min_hours`,`max_hours`,`repeat_hours`,
						`exclude`,`data_entry_comment`)
						values ('?','?','?','?','?','?','?','?','?','?','?') ";
    //debugCT($query);
    $res = $db->dbQuery($query, $courseID,$subjectID,$courseNum,$deCatalogYear,
						$title,$description,$minHours,$maxHours,$repeatHours,
						$exclude,$dataEntryComment);

    // Now, this part is tricky.  Are there any courses which already
    // existed with this subjectID and courseNum, but not this courseID?
    // This would happen if we add an eqv for a course that already existed
    // elsewhere.  We want to change that existing course's ID to match the
    // new one, but we also need to update EVERY table that used the old
    // courseID with the new courseID, including degree plans, groups,
    // substitutions, etc.

    // query for subjectID and courseNum but != courseID.
    // get oldCourseID.
    // call function updateCourseID(oldCourseID, newCourseID)
    $res2 = $db->dbQuery("SELECT * FROM draft_courses WHERE
								`subject_id`='?'
								AND `course_num`='?'
								AND `course_id` != '?' 
								AND `course_id` != '-12345' ", $subjectID, $courseNum, $courseID);
    while ($cur2 = $db->dbFetchArray($res2))
    {
      $oldCourseID = $cur2["course_id"];
      // Now, update all the existing references to $oldCourseID
      // with the new courseID.
      $db2 = new DatabaseHandler();
      $db2->updateCourseID($oldCourseID, $courseID, true);
      // Now, add it to our list of things to update when we apply
      // the draft changes...
      $db2->addDraftInstruction("updateCourseID,$oldCourseID,$courseID");
    }





  }

  // We have to accomodate the situation that there used to be an
  // eqv set up (multiple course names were set) but now there is not.
  // In other words, someone wanted to undo an eqv.
  // We used to have:  ACCT 101, MATH 101
  // But they took out the comma.  So, only ACCT 101 just got written
  // to the database, while MATH 101 has been marked as -12345 and is
  // destined to be deleted.
  // -- we need to give MATH 101 a new courseID and update that courseID
  // for all years.
  // Then, we need to go through all our tables and update where it was
  // actually spelled out that "MATH 101" be used with the new courseID.
  // -- This process will ensure that no previous existing courses
  // will get deleted.  That they will remain as their own unique
  // courses.

  // First thing's first.  Go through all the courses with the courseID
  // of -12345.  If we find one that does not have the same subjectID
  // and courseNum with the new ID, then this is a removed eqv, and
  // that is our cue that it should be it's own course.
  $res = $db->dbQuery("SELECT * FROM draft_courses
							WHERE `course_id`='-12345' ");
  while ($cur = $db->dbFetchArray($res))
  {
    $foundSI = $cur["subject_id"];
    $foundCN = $cur["course_num"];
    $db2 = new DatabaseHandler();
    $res2 = $db2->dbQuery("SELECT * FROM draft_courses
							WHERE `course_id`='?'
							AND `subject_id`='?'
							AND `course_num`='?' 
							AND `catalog_year`='?' ", $courseID, $foundSI, $foundCN, $deCatalogYear);
    if ($db2->dbNumRows($res2) == 0)
    {
      // Meaning, this course name is not listed with the courseID,
      // so this is a broken eqv.
      // We should make a new courseID and all that for this course,
      // for all available years.
      //debugCT("removed eqv: $foundSI $foundCN");
      $newCourseID = $db2->requestNewCourseID();
      $db3 = new DatabaseHandler();
      $res3 = $db3->dbQuery("UPDATE draft_courses
									SET `course_id`='?'
									WHERE `subject_id`='?'
									AND `course_num`='?' ", $newCourseID, $foundSI, $foundCN);
      // removed WHERE `course_id`='-12345' from query.  We want to UPDATE
      // this across all years for this course.
      // And also UPDATE every other table that specified foundSI &CN
      // as a requirement.
      $db3->updateCourseRequirementFromName($foundSI, $foundCN, $newCourseID);
      $db3->addDraftInstruction("updateCourseRequirementFromName,$foundSI,$foundCN,$newCourseID");
    }
  }





  // Was the "allYears" box checked?  If it was, then update all instances
  // of this course, across all available catalog years.
  if ($_POST["allYears"] == "yes")
  {
    $res = $db->dbQuery("UPDATE draft_courses
									SET `title`='?',
									`description`='?',
									`min_hours`='?',
									`max_hours`='?',
									`repeat_hours`='?'
									WHERE `course_id`='?' ", $title, $description, $minHours, $maxHours, $repeatHours, $courseID);
  }



  // Clean up.  Delete the temporary courseID...

  $query = "DELETE FROM draft_courses
				  WHERE `course_id`='-12345'
					";
  //debugCT($query);
  $res = $db->dbQuery($query);




  $msg = "<font color='green' size='4'>Course updated successfully at " . getCurrentTime() . ".</font>";

  if ($errors != "")
  {
    $msg = $errors;
  }


  displayEditSpecificCourse($msg);
}


function performEditSpecificUser()
{
  
  //////////////////////////////////
  // Check for hooks...
  $function = "admin_performEditSpecificUser";
  if (function_exists($function)) {
    return call_user_func($function);
  }
  //////////////////////////////////
  if (!userHasPermission("deCanAdministerUsers")) {
    displayAccessDenied();
  }
  
  
  global $db;

  $facultyID = $_REQUEST["facultyID"];
  $userType = $_REQUEST["userType"];
  $permissions = "";

  
  // Build the permissions string we need to add to the db.
  foreach ($_REQUEST as $key => $value) {
    if (strstr($key, "perm~~_~~") && $value == "yes") {
      $perm = trim(str_replace("perm~~_~~", "", $key));
      
      if (userHasPermission($perm)) {
        $permissions .= $perm . ",";
      }
      
    }
  }
  
/*  if ($_REQUEST["perm_course_info"] == "yes")
  {
    $permissions .= "deCanUpdateCourseInfo,";
  }
*/

  // First, delete the existing entry for this user
  // from the table.
  $res = $db->dbQuery("DELETE FROM users WHERE `faculty_id`='?' ", $facultyID) ;

  // Now, insert.
  $res = $db->dbQuery("INSERT INTO users
						(`faculty_id`,`user_type`,`permissions`)
						values ('?','?','?') ", $facultyID, $userType, $permissions) ;


  displayEditSpecificUser(getSuccessMsg("Successfully updated user " . getCurrentTime()));


}


function performProcessGroupDefinitions()
{
  //////////////////////////////////
  // Check for hooks...
  $function = "admin_performProcessGroupDefinitions";
  if (function_exists($function)) {
    return call_user_func($function);
  }
  //////////////////////////////////
  if (!userHasPermission("deCanAdministerDataEntry")) {
    displayAccessDenied();
  }
  
  
  global $db;
  $db2 = new DatabaseHandler();
  $deCatalogYear = $GLOBALS["deCatalogYear"];
  $msg = "";
  $msg .= "<ul>";
  // This function will go through every group for this year and
  // re-run it's definition, saving the result.

  // First, find every group which has a definition set.
  $res = $db->dbQuery("SELECT * FROM draft_groups
                       WHERE definition != '' 
                       AND catalog_year = '?' 
                       AND delete_flag = 0 ", $deCatalogYear);
  while($cur = $db->dbFetchArray($res))
  {
    $def = $cur["definition"];
    $groupID = $cur["group_id"];
    $groupName = $cur["group_name"];
    $temp = getCoursesFromDefinition($def);
    $courses = trim($temp["text"]);
    $ccount = 0;

    $msg .= "<li>Working on $groupName...</li>";

    // Remove all the existing group requirements for this group first.
    $res2 = $db->dbQuery("DELETE FROM draft_group_requirements
                        WHERE group_id = ? ", $groupID);


    $lines = split("\n", $courses);
    for ($t = 0; $t < count($lines); $t++)
    {
      $line = trim($lines[$t]);
      if ($line == "") { continue; }
      // Get rid of extra whitespace.
      $line = str_replace("  ", " ", $line);
      $line = str_replace("  ", " ", $line);
      $line = str_replace("  ", " ", $line);

      // Does this line contain at least one & symbol?  If it does,
      // then this is a subgroup (branch).  If not, then we can insert
      // the course as-is.
      if (!strstr($line, "&"))
      {
        // Did NOT contain an ampersand (&), so this goes in the
        // regular course requirements.
        $tokens = split(" ", $line);
        $subjectID = trim($tokens[0]);
        $courseNum = trim($tokens[1]);
        $minGrade = trim($tokens[2]);
        $courseRepeats = trim($tokens[3]);

        if (strstr($minGrade, "["))
        {
          // This is actually a specified repeat, not a min grade.
          $courseRepeats = $minGrade;
          $minGrade = "";
        }

        $minGrade = str_replace("(","",$minGrade);
        $minGrade = strtoupper(str_replace(")","",$minGrade));

        $courseRepeats = str_replace("[","",$courseRepeats);
        $courseRepeats = str_replace("]","",$courseRepeats);
        $courseRepeats--;
        if ($courseRepeats < 0) { $courseRepeats = 0; }

        // If the subject_id had a _A_ in it, convert this back
        // to an ampersand.
        $subjectID = str_replace("_A_", "&", $subjectID);

        // We don't care about catalog year anymore...
        if ($courseID = $db->getCourseID($subjectID, $courseNum, "", true))
        {
          $query = "INSERT INTO draft_group_requirements
										(`group_id`,`course_id`,
										`course_min_grade`,`course_repeats`,`data_entry_value`)
										values ('?','?',
										'?','?','?~?') ";
          $res2 = $db->dbQuery($query, $groupID, $courseID, $minGrade, $courseRepeats, $subjectID, $courseNum);
          $ccount++;
          //debugCT($query);
        } else {
          // The courseID could not be found!
          $msg .= "<li><font color='red'><b>Course Not Found!</b>
							You specified the course
							<b>$subjectID $courseNum</b> as a requirement in $groupName, but this course
							could not be found in the catalog.
							It was removed from the list of requirements.
							Are you sure you typed it correctly?  Please check 
							your spelling, and add the course again.</font></li>";

        }
      }
    }

    $msg .= "<li>$groupName defintion processed.  $ccount courses added.</li>";


  }
  $msg .= "</ul>";

  $msg = "<div class='hypo'><b>Processed group definitions.</b>  Log:<br>$msg</div>";

  $db->addToLog("admin_proc_group_defs","$deCatalogYear");
  
  displayEditGroups($msg);


}



function performEditSpecificGroup()
{
  //////////////////////////////////
  // Check for hooks...
  $function = "admin_performEditSpecificGroup";
  if (function_exists($function)) {
    return call_user_func($function);
  }
  //////////////////////////////////
  if (!userHasPermission("deCanAdministerDataEntry")) {
    displayAccessDenied();
  }
  
  
  global $db;
  $db2 = new DatabaseHandler();
  $deCatalogYear = $GLOBALS["deCatalogYear"];
  $groupID = $_REQUEST["groupID"];

  // Okay, we are trying to save the details of this group.
  // First thing we need to do is UPDATE the title, groupName,
  // priority, icon and comment.
  $groupName = trim($_POST["groupName"]);
  $title = trim($_POST["title"]);
  $priority = trim($_POST["priority"]);
  $iconFilename = trim($_POST["iconFilename"]);
  $dataEntryComment = trim($_POST["dataEntryComment"]);

  // Save the entire post to the log.
  $postXML = fp_arrayToXml("post",$_POST, true);
  $db->addToLog("admin_edit_group","$groupID,$groupName",$postXML);

  // Since we are making a change to the draft table(s), let's add a row
  // to draft instructions.
  $db->addDraftInstruction("-");


  // Are we trying to delete this group?
  if ($_POST["performAction2"] == "delete_group")
  {
    $res = $db->dbQuery("UPDATE draft_groups
								SET `delete_flag`='1'
								WHERE `group_id`='?' 
								AND `catalog_year`='?'
								", $groupID, $deCatalogYear);
    displayEditGroups("<font size='4' color='green'>The group $title ($groupName) has been deleted successfully for this year.</font>");
    die;
  }

  // If the $groupID == new then create a new one.
  if ($groupID == "new")
  {
    $groupID = $db->requestNewGroupID();
    $res = $db->dbQuery("INSERT INTO draft_groups(`group_id`,`catalog_year`)
								values ('?','?') ", $groupID, $deCatalogYear);
    $_POST["groupID"] = $groupID;
    $_GET["groupID"] = $groupID;
    $_REQUEST["groupID"] = $groupID;
  }


  $res = $db->dbQuery("UPDATE draft_groups
							SET `group_name`='?',
							`title`='?',
							`priority`='?',
							`icon_filename`='?',
							`data_entry_comment`='?'
							WHERE
								`group_id`='?' ", 
              $groupName, $title, $priority, $iconFilename, $dataEntryComment, $groupID);

  // We need to delete all the existing course & subgroup requirements from this group.
  // That entails first seeing what subgroups were required and deleting them,
  // then deleting the parent group's requirements.
  // First, find and delete the branches (child groups):
  $res = $db->dbQuery("SELECT * FROM draft_group_requirements
							WHERE `group_id`='?'
							AND `child_group_id` != '0' ", $groupID);
  while ($cur = $db->dbFetchArray($res))
  {
    $cgID = $cur["child_group_id"];
    $res2 = $db2->dbQuery("DELETE FROM draft_group_requirements
								WHERE `group_id`='?' ", $cgID);
  }
  // Now delete the course requirements...
  $res = $db->dbQuery("DELETE FROM draft_group_requirements
								WHERE `group_id`='?' ", $groupID);

  $courses = trim($_POST["courses"]);
  // If a definition was set, then we will ignore what is in the POST
  // for the course requrements, and instead use the definition.
  if (trim($_POST["setDefinition"] != ""))
  {
    $def = urldecode(trim($_POST["setDefinition"]));
    //$cc = trim(getCoursesFromDefinition($def, $deCatalogYear));
    $temp2 = getCoursesFromDefinition($def);
    $cc = trim($temp2["text"]);
    if ($cc != "")
    {
      $courses = $cc;
      // UPDATE this group's definition!
      $res = $db->dbQuery("UPDATE draft_groups
							SET `definition`='?'
							WHERE
								`group_id`='?' ", $def, $groupID);

    }
    //print_pre($cc);
  }
  else {
    // In other words, the setDefinition WAS blank.
    // Let's update the table.  This is to fix a bug where they were unable
    // to clear definitions.
      $res = $db->dbQuery("UPDATE draft_groups
							SET `definition`=''
							WHERE
								`group_id`='?' ", $groupID);    
  }

  // Okay, now we look at the actual "courses" box and assemble the group
  // in the database.
  $lines = split("\n", $courses);
  for ($t = 0; $t < count($lines); $t++)
  {
    $line = trim($lines[$t]);
    if ($line == "") { continue; }
    // Get rid of extra whitespace.
    $line = str_replace("  ", " ", $line);
    $line = str_replace("  ", " ", $line);
    $line = str_replace("  ", " ", $line);

    // Does this line contain at least one & symbol?  If it does,
    // then this is a subgroup (branch).  If not, then we can insert
    // the course as-is.
    if (strstr($line, "&"))
    {
      // This line DOES have an ampersand (&), so this is a sub group
      // within this group.
      // First, we need to request a new branchID for this new group.
      if (!$branchID = $db->requestNewGroupID())
      {
        die ("Error.  Could not create new group (branch) ID.");
      } else {
        // Add this branch to the list of requirements for this group.
        $query = "INSERT INTO draft_group_requirements
										(`group_id`,`child_group_id`)
										values ('?','?') ";
        $res = $db->dbQuery($query, $groupID, $branchID);
        //debugCT($query);

      }
      $cTokes = split("&",$line);
      for ($cT = 0; $cT < count($cTokes); $cT++)
      {
        $tokens = split(" ", trim($cTokes[$cT]));
        $subjectID = trim($tokens[0]);
        $courseNum = trim($tokens[1]);
        $minGrade = trim($tokens[2]);
        $courseRepeats = trim($tokens[3]);

        if (strstr($minGrade, "["))
        {
          // This is actually a specified repeat, not a min grade.
          $courseRepeats = $minGrade;
          $minGrade = "";
        }

        $minGrade = str_replace("(","",$minGrade);
        $minGrade = str_replace(")","",$minGrade);

        $courseRepeats = str_replace("[","",$courseRepeats);
        $courseRepeats = str_replace("]","",$courseRepeats);
        $courseRepeats--;
        if ($courseRepeats < 0) { $courseRepeats = 0; }

        // If the subject_id had a _A_ in it, convert this back
        // to an ampersand.
        $subjectID = str_replace("_A_", "&", $subjectID);

        // Commenting out, because we do not care about catalogYear
        // when specifying courses...
        //if ($courseID = $db->getCourseID($subjectID, $courseNum, $deCatalogYear))
        if ($courseID = $db->getCourseID($subjectID, $courseNum, "", true))
        {
          $query = "INSERT INTO draft_group_requirements
										(`group_id`,`course_id`,
										`course_min_grade`,`course_repeats`,`data_entry_value`)
										values ('?','?',
										'?','?','?~?') ";
          $res = $db->dbQuery($query, $branchID, $courseID, $minGrade, $courseRepeats, $subjectID, $courseNum);
          //debugCT($query);
        } else {
          // The courseID could not be found!
          $errors .= "<br><font color='red'><b>Course Not Found!</b>
							You specified the course
							<b>$subjectID $courseNum</b> as a requirement, but this course
							could not be found in the catalog.
							It was removed from the list of requirements.
							Are you sure you typed it correctly?  Please check 
							your spelling, and add the course again.</font>";

        }

      }


    } else {
      // Did NOT contain an ampersand (&), so this goes in the
      // regular course requirements.
      $tokens = split(" ", $line);
      $subjectID = trim($tokens[0]);
      $courseNum = trim($tokens[1]);
      $minGrade = trim($tokens[2]);
      $courseRepeats = trim($tokens[3]);

      if (strstr($minGrade, "["))
      {
        // This is actually a specified repeat, not a min grade.
        $courseRepeats = $minGrade;
        $minGrade = "";
      }

      $minGrade = str_replace("(","",$minGrade);
      $minGrade = strtoupper(str_replace(")","",$minGrade));

      $courseRepeats = str_replace("[","",$courseRepeats);
      $courseRepeats = str_replace("]","",$courseRepeats);
      $courseRepeats--;
      if ($courseRepeats < 0) { $courseRepeats = 0; }

      // If the subject_id had a _A_ in it, convert this back
      // to an ampersand.
      $subjectID = str_replace("_A_", "&", $subjectID);

      // We don't care about catalog year anymore...
      if ($courseID = $db->getCourseID($subjectID, $courseNum, "", true))
      {
        $query = "INSERT INTO draft_group_requirements
										(`group_id`,`course_id`,
										`course_min_grade`,`course_repeats`,`data_entry_value`)
										values ('?','?',
										'?','?','?~?') ";
        $res = $db->dbQuery($query, $groupID, $courseID, $minGrade, $courseRepeats, $subjectID, $courseNum);
        //debugCT($query);
      } else {
        // The courseID could not be found!
        $errors .= "<br><font color='red'><b>Course Not Found!</b>
							You specified the course
							<b>$subjectID $courseNum</b> as a requirement, but this course
							could not be found in the catalog.
							It was removed from the list of requirements.
							Are you sure you typed it correctly?  Please check 
							your spelling, and add the course again.</font>";

      }



    }

  }


  $msg = "<font color='green' size='4'>Group updated successfully at " . getCurrentTime() . ".</font>";
  $boolScroll = true;
  if ($errors != "")
  {
    $msg = $errors;
    $boolScroll = false;
  }


  displayEditSpecificGroup($msg, $boolScroll);


}


function performCopyDegree()
{
  //////////////////////////////////
  // Check for hooks...
  $function = "admin_performCopyDegree";
  if (function_exists($function)) {
    return call_user_func($function);
  }
  //////////////////////////////////
  if (!userHasPermission("deCanAdministerDataEntry")) {
    displayAccessDenied();
  }
  
  
  global $db;
  $deCatalogYear = $GLOBALS["deCatalogYear"];

  $sourceMajorCode = trim(strtoupper($_POST["sourceMajorCode"]));
  $destinationMajorCode = trim(strtoupper($_POST["destinationMajorCode"]));
  $includeTracks = $_POST["includeTracks"];
  
  // First thing's first.  Make sure the sourceMajorCode exists.
  $res = $db->dbQuery("SELECT * FROM draft_degrees 
                    WHERE (major_code = '?'
                    OR major_code LIKE '?|%')
                    AND catalog_year='?' ", $sourceMajorCode, $sourceMajorCode, $deCatalogYear) ;
  if ($db->dbNumRows($res) == 0) {
    // Meaning, it could not be found.
    displayCopyDegree("<font color='red'>The source major, $sourceMajorCode, could
                        not be found for $deCatalogYear.</font>");
    return;
  }

  // Alright, if we got to here, we can proceed.  We need to 
  // delete everything involving the destination major.
  // First, get the degree_id's in a select...
  $res = $db->dbQuery("SELECT * FROM draft_degrees 
                    WHERE (major_code = '?'
                    OR major_code LIKE '?|%')
                    AND catalog_year='?' ", $destinationMajorCode, $destinationMajorCode, $deCatalogYear) ;
  if ($db->dbNumRows($res) > 0) {
    while ($cur = $db->dbFetchArray($res)) {
      $degree_id = $cur["degree_id"];
      $res2 = $db->dbQuery("DELETE FROM draft_degree_requirements
                           WHERE degree_id='?' ", $degree_id) ;
      
      $res2 = $db->dbQuery("DELETE FROM draft_degrees
                           WHERE degree_id = '?' ", $degree_id) ;      
    }
    // Now, delete the tracks.
    $res2 = $db->dbQuery("DELETE FROM draft_degree_tracks
                          WHERE major_code = '?' 
                          AND catalog_year='?' ", $destinationMajorCode, $deCatalogYear) ;
  }

  // Okay, with the destination major good and deleted, we can proceed with
  // the copy.
  
  // Let's build up an array of all the degrees we will be copying.
  $sourceArray = array();
  // First, the base degree...
  $res = $db->dbQuery("SELECT * FROM draft_degrees 
                    WHERE major_code = '?'
                    AND catalog_year='?' ", $sourceMajorCode, $deCatalogYear) ;
  $cur = $db->dbFetchArray($res);
  $sourceArray[] = $cur;
  
  // Now, any tracks or concentrations?
  if ($includeTracks == "yes") {
    $res = $db->dbQuery("SELECT * FROM draft_degrees 
                      WHERE major_code LIKE '?|%'
                      AND catalog_year='?' ", $sourceMajorCode, $deCatalogYear) ;
    while ($cur = $db->dbFetchArray($res)) {
      $sourceArray[] = $cur;
    }

    // While we're here, let's go ahead and make a copy of the tracks.
    $res = $db->dbQuery("SELECT * FROM draft_degree_tracks
                        WHERE (major_code = '?'
                        OR major_code LIKE '?|%' )
                        AND catalog_year='?' ", $sourceMajorCode, $sourceMajorCode, $deCatalogYear) ;
    while($cur = $db->dbFetchArray($res)) {
      extract($cur, 3, "db");
      $destCode = $destinationMajorCode;
      if (strstr($db_major_code, "|")) {
        // We need to adjust the destCode to match
        //the source.
        $destCode = str_replace("$sourceMajorCode|", "$destinationMajorCode|", $db_major_code);
      }
      
      $res2 = $db->dbQuery("INSERT INTO draft_degree_tracks
                          (catalog_year, major_code, track_code, 
                          track_title, track_short_title, track_description)
                          VALUES
                          ('?', '?', '?', '?', '?', '?') ",
                          $deCatalogYear, $destCode, $db_track_code, 
                          $db_track_title, $db_track_short_title, 
                          $db_track_description) ;
                          
    }       
  }
  
  //var_dump($sourceArray);
  // Okay, now it's time to go through the sourceArray
  // and duplicate them.
  foreach ($sourceArray as $src) {
    extract($src, 3, "src");
    
    $destCode = $destinationMajorCode;
    if (strstr($src_major_code, "|")) {
      // We need to adjust the destCode to match
      //the source.
      $destCode = str_replace("$sourceMajorCode|", "$destinationMajorCode|", $src_major_code);
    }
    
    //var_dump($destCode);
    $destDegreeID = $db->requestNewDegreeID();

    // Create the entry in the degrees table.
    $res = $db->dbQuery("INSERT INTO draft_degrees
                        (degree_id, major_code, degree_type, degree_class, title,
                         public_note, semester_titles_csv,
                         catalog_year, exclude)
                         VALUES   
                        ('?', '?', '?', '?', '?', '?', '?', '?', '?') ",
                         $destDegreeID, $destCode, $src_degree_type, $src_degree_class, $src_title,
                         $src_public_note, $src_semester_titles_csv,
                         $deCatalogYear, $src_exclude);
    
    // Now, go through the source's degree requirements and copy those over.
    $res = $db->dbQuery("SELECT * FROM draft_degree_requirements
                         WHERE degree_id = '$src_degree_id' ");
    while ($cur = $db->dbFetchArray($res)) {
      extract($cur, 3, "db");
      
      $res2 = $db->dbQuery("INSERT INTO draft_degree_requirements
                          (degree_id, semester_num, group_id,
                           group_requirement_type,
                           group_hours_required,
                           group_min_grade, course_id,
                           course_min_grade,
                           course_requirement_type,
                           data_entry_value)
                           VALUES
                          ('?', '?', '?', '?', '?',
                           '?', '?',
                           '?',
                           '?',
                           '?') ",
                            $destDegreeID, $db_semester_num, $db_group_id,
                           $db_group_requirement_type,
                           $db_group_hours_required,
                           $db_group_min_grade, $db_course_id,
                           $db_course_min_grade,
                           $db_course_requirement_type,
                           $db_data_entry_value);
                           
    }  
    
  }
  
  // Make a - entry into the draft_instruction table so it will
  // remind the administrator to apply draft changes.
  $res = $db->dbQuery("INSERT INTO draft_instructions
                        (instruction) VALUES ('-') ");
  
  
  
  displayCopyDegree(getSuccessMsg("Degree $sourceMajorCode has been copied to $destinationMajorCode for $deCatalogYear."));
}


function performAddNewDegree()
{
  
  //////////////////////////////////
  // Check for hooks...
  $function = "admin_performAddNewDegree";
  if (function_exists($function)) {
    return call_user_func($function);
  }
  //////////////////////////////////
  if (!userHasPermission("deCanAdministerDataEntry")) {
    displayAccessDenied();
  }
  
  
  global $db;
  $deCatalogYear = $GLOBALS["deCatalogYear"];
  // This will be used to add a new degree (and possibly track)
  // to the database.
  $majorCode = trim(strtoupper(($_POST["majorCode"])));
  $trackCode = trim(strtoupper($_POST["trackCode"]));

  $newMajor = $_POST["newMajor"];
  $newTrack = $_POST["newTrack"];

  //////////////////////////////////////////////

  if ($majorCode == "")
  {
    $msg = "<div><font color='red' size='4'>You must enter
					a major or major|concentration code.</font></div>";
    displayAddNewDegree($msg);
    die;
  }

  if ($newTrack == "new" && $trackCode == "")
  {
    $msg = "<div><font color='red' size='4'>You selected to add
				a track, but did not specify a track code.</font></div>";
    displayAddNewDegree($msg);
    die;
  }

  // Make sure user did not enter an underscore (_) in either
  // the track or major code!
  if (strstr($trackCode, "_") || strstr($majorCode, "_"))
  {
    $msg = "<div><font color='red' size='4'>You are not allowed to enter
              underscores (_) in either the track code or major code.
              FlightPath will add that for you. Please re-enter your
              new degree w/o using an underscore.</font></div>";
    displayAddNewDegree($msg);
    die;

  }


  ////////////////////////////////////////////////////




  // First, deal with the major/concentration.
  // Firstly, check to see if it already exists...
  $res = $db->dbQuery("SELECT * FROM draft_degrees
					WHERE `catalog_year`='?'
					AND `major_code`='?' ", $deCatalogYear, $majorCode);
  if ($db->dbNumRows($res) > 0 && $newMajor == "new")
  { // Meaning, it already exists, yet we are trying to add it as a new
    // major.  This is an error!
    $msg = "<div><font color='red' size='4'>The major $majorCode already exists for $deCatalogYear.
					You cannot add it as new.</font></div>";
    displayAddNewDegree($msg);
    die;
  }
  if ($db->dbNumRows($res) == 0 && $newMajor == "existing")
  { // This is another error.  We are trying to add a track to an existing
    // major code, but none was found.
    $msg = "<div><font color='red' size='4'>The major $majorCode could not be found
					in the system for $deCatalogYear. Perhaps you need to add it first?</font></div>";
    displayAddNewDegree($msg);
    die;
  }
  if ($db->dbNumRows($res) == 0 && $newMajor == "new")
  {
    // This means we are trying to add a new major to the degrees table.
    // We may proceed with this.
    $db2 = new DatabaseHandler();
    $degree_id = $db2->requestNewDegreeID();
    $db2->dbQuery("INSERT INTO draft_degrees
						(`degree_id`,`major_code`,`catalog_year`)
						values ('?', '?', '?') ", $degree_id, $majorCode, $deCatalogYear);
  }


  if ($newTrack == "new")
  {
    //////////////////////////////////////////////////
    // Now, let's see about adding ourself a track...
    // First, check to see if it exists...
    $res = $db->dbQuery("SELECT * FROM draft_degree_tracks
					WHERE `catalog_year`='?'
					AND `major_code`='?' 
					AND `track_code`='?' ", $deCatalogYear, $majorCode, $trackCode);	

    if ($db->dbNumRows($res) > 0)
    {
      // Meaning, it already existed, so we can't create it.
      $msg = "<div><font color='red' size='4'>The major and track $majorCode $trackCode already exists for $deCatalogYear.
					You cannot add it as new.</font></div>";
      displayAddNewDegree($msg);
      die;
    } else {
      // We can add it to the tracks table...
      $db2 = new DatabaseHandler();
      $db2->dbQuery("INSERT INTO draft_degree_tracks
							(`catalog_year`,`major_code`,`track_code`)
							values ('?', '?', '?') ", $deCatalogYear, $majorCode, $trackCode);

      // Now, we also need to add this major & track code to the degrees table.
      $newMajorCode = $majorCode;
      if (strstr($majorCode, "|"))
      {
        // Already has a pipe, so it has a concentration.
        $newMajorCode .= "_$trackCode";
      } else {
        // No concentration...
        $newMajorCode .= "|_$trackCode";
      }

      $degree_id = $db2->requestNewDegreeID();
      $db2->dbQuery("INSERT INTO draft_degrees
						(`degree_id`,`major_code`,`catalog_year`)
						values ('?', '?', '?') ", $degree_id, $newMajorCode, $deCatalogYear);

    }





  }


  displayAddNewDegree(getSuccessMsg("New degree $majorCode $trackCode added successfully to $deCatalogYear.
						You may add another degree, or use the menu at the top of the page to edit this new degree."));


}






function performEditSpecificDegree()
{
  
  //////////////////////////////////
  // Check for hooks...
  $function = "admin_performEditSpecificDegree";
  if (function_exists($function)) {
    return call_user_func($function);
  }
  //////////////////////////////////
  if (!userHasPermission("deCanAdministerDataEntry")) {
    displayAccessDenied();
  }
  
  
  global $db;
  $deCatalogYear = $GLOBALS["deCatalogYear"];
  // This will UPDATE a degree in the system with the courses
  // and groups that the user selected.
  $performAction2 = trim($_POST["performAction2"]);

  if (strstr($performAction2, "delGroup"))
  {
    $temp = split("_",$performAction2);
    $delGroup = new Group();
    $delGroup->boolUseDraft = true;
    $delGroup->groupID = $temp[1];
    $delGroup->assignedToSemesterNum = $temp[2];

  }
  //debugCT($performAction2);

  $majorCode = trim($_POST["majorCode"]);
  if ($majorCode == ""){	die("Fatal error:  majorCode not found.");}

  // Since we are making a change to the draft table(s), let's add a row
  // to draft instructions.
  $db->addDraftInstruction("-");


  $degreeID = "";
  // First things first, if this degree already exists in this
  // catalog year, then we need to delete it first.
  if ($degreeID = $db->getDegreeID($majorCode, $deCatalogYear, true))
  {
    $degree = new DegreePlan($degreeID, null, false, false, true);
    $degree->loadDescriptiveData();

    // Delete from degree_requirements WHERE this degree_id exists.
    $res = $db->dbQuery("DELETE FROM draft_degree_requirements
									WHERE `degree_id`='?' ", $degreeID);

    // Are we trying to DELETE this degree?  If so, keep deleting!
    if ($performAction2 == "delete_degree")
    {
      $res = $db->dbQuery("DELETE FROM draft_degrees
									WHERE `degree_id`='?' ", $degreeID);

      // Also need to get rid of the track, if there is one for this
      // degree.
      $res = $db->dbQuery("DELETE FROM draft_degree_tracks
									WHERE `major_code`='$degree->majorCode' 
									AND `track_code`='$degree->trackCode'
									AND `catalog_year` = '?' LIMIT 1", $deCatalogYear);

      // Okay, we have deleted everything.  We need to go back to
      // just the list of degrees.
      displayEditDegrees("<font color='green'><b>The degree $majorCode ($deCatalogYear) has been deleted.</b></font>");
      die;
    }


  } else {
    // We need to generate a new degreeID for this majorCode and catalogYear,
    // because one does not already exist!
    if (!$degreeID = $db->requestNewDegreeID())
    {
      die ("Error.  Could not create new degreeID.");
    }
  }

  // Save the entire post to the log.
  $postXML = fp_arrayToXml("post",$_POST, true);
  $db->addToLog("admin_edit_degree","$degreeID,$majorCode",$postXML);


  $errors = "";
  $semesterTitlesCSV = "";
  $highestSemesterNum = 0;   // What is the largest semesterNum in the system?
  // Okay, now get the various courses...
  for ($semesterNum = 0; $semesterNum < 50; $semesterNum++)
  {
    // Assuming no more than 50 semesters.
    $courses = trim($_POST["courses_$semesterNum"]);
    if ($courses == "")
    {
      continue;
    }

    if ($semesterNum > $highestSemesterNum)
    {
      $highestSemesterNum = $semesterNum;
    }

    $courseRows = split("\n",$courses);
    for ($t = 0; $t < count($courseRows); $t++)
    {
      $line = trim($courseRows[$t]);
      if ($line == "")
      {
        continue;
      }
      // Take out extra whitespace between tokens.
      $line = str_replace("   ", " ", $line);
      $line = str_replace("  ", " ", $line);
      $line = str_replace("  ", " ", $line);
      $line = str_replace("  ", " ", $line);

      $tokens = split(" ", $line);
      $subjectID = $tokens[0];
      $courseNum = $tokens[1];
      $requirementType = strtolower($tokens[2]);

      if ($requirementType == "")
      { // major type by default.
        $requirementType = "m";
      }

      $minGrade = strtoupper($tokens[3]);

      if (strstr($requirementType, "("))
      {
        // This means there was no requirementType specified, so it's "m",
        // and a minGrade was found in its place.
        $minGrade = strtoupper($requirementType);
        $requirementType = "m";
      }

      $minGrade = str_replace("(","",$minGrade);
      $minGrade = str_replace(")","",$minGrade);

      /////////////////////////////////////////////
      // Okay, we now have enough information to insert the course.
      // Find out what the courseID is.
      if ($courseID = $db->getCourseID($subjectID, $courseNum, "", true))  // don't care about catalog year.
      {
        $query = "INSERT INTO draft_degree_requirements
										(`degree_id`,`semester_num`,`course_id`,
										`course_min_grade`,`course_requirement_type`,
										 `data_entry_value`)
										values ('?','?','?',
										'?','?','?~?') ";
        $res = $db->dbQuery($query, $degreeID, $semesterNum, $courseID, $minGrade, $requirementType, $subjectID, $courseNum);
        //debugCT($query);
      } else {
        // The courseID could not be found!
        $errors .= "<br><font color='red'><b>Course Not Found!</b>
							In Block " . ($semesterNum+1) . ", you specified the course
							<b>$subjectID $courseNum</b> as a requirement, but this course
							could not be found.
							It was removed from that block.
							Are you sure you typed it correctly?  Please go to this
							semester, check your spelling, and add the course again.</font>";

      }

    }


  }

  // Get the groups....
  foreach($_POST as $key => $value)
  {
    if (!strstr($key, "group_"))
    {
      continue;
    }
    // Only look at the groups...
    $temp = split("_", $value);
    $groupID = $temp[0];
    $semesterNum = $temp[1];
    $hours = $temp[2];
    $type = $temp[3];
    $minGrade = trim($temp[4]);

    if ($semesterNum > $highestSemesterNum)
    {
      $highestSemesterNum = $semesterNum;
    }


    // Do not add if we are supposed to be deleting this group!
    if (is_object($delGroup))
    {
      if ($delGroup->groupID == $groupID && $delGroup->assignedToSemesterNum == $semesterNum)
      {
        continue;
      }
    }

    // We now have enough information to insert this group.
    //debugCT("group: $groupID $semesterNum $hours $type");
    $query = "INSERT INTO draft_degree_requirements
										(`degree_id`,`semester_num`,`group_id`,
										`group_requirement_type`,`group_hours_required`,`group_min_grade`)
										values ('?','?','?',
										'?','?','?') ";
    $res = $db->dbQuery($query, $degreeID, $semesterNum, $groupID, $type, $hours, $minGrade);
    //debugCT($query);


  }


  // Was there a group added or deleted?
  if (strstr($performAction2,"addGroup"))
  {
    $temp = split("_",$performAction2);
    $groupID = $temp[1];
    $semesterNum = $temp[2];
    $hours = trim($temp[3]);
    $type = $temp[4];
    $minGrade = trim($temp[5]);
    $query = "INSERT INTO draft_degree_requirements
										(`degree_id`,`semester_num`,`group_id`,
										`group_requirement_type`,`group_hours_required`,`group_min_grade`)
										VALUES ('?','?','?','?','?','?') ";
    $res = $db->dbQuery($query, $degreeID, $semesterNum, $groupID, $type, $hours, $minGrade);

  }


  // Make the semesterTitlesCSV...
  for ($semesterNum = 0; $semesterNum <= $highestSemesterNum; $semesterNum++)
  {
    $semesterTitlesCSV .= trim($_POST["semester_title_$semesterNum"]) . ",";
  }

  // Before we UPDATE, also grab the degree title, degree_type,
  // and exclude value, etc....
  $degreeTitle = trim($_POST["title"]);
  $degreeType = trim($_POST["degree_type"]);
  $degreeClass = strtoupper(trim($_POST["degree_class"]));
  $exclude = trim($_POST["exclude"]);
  $publicNote = trim($_POST["public_note"]);
  $res = $db->dbQuery("UPDATE draft_degrees
							SET `semester_titles_csv`='?',
							`title`='?',
							`degree_type`='?',
							`degree_class`='?',
							`exclude`='?',
							`public_note`='?'
							WHERE `degree_id`='?' ",
              $semesterTitlesCSV, $degreeTitle, $degreeType, $degreeClass, $exclude, $publicNote, $degreeID);

  ////  Was there a track title/description?  If so, UPDATE that in the tracks
  // table...
  if (strstr($majorCode, "_"))
  {
    // There was a track. Update track description.
    $temp = split("_",$majorCode);
    $major = trim($temp[0]);
    // major might now have a | at the end.  If so, take it out.
    if (substr($major, strlen($major)-1, 1) == "|")
    {
      $major = str_replace("|","",$major);
    }


    $track = trim($temp[1]);
    $trackDescription = trim($_POST["track_description"]);
    $trackTitle = trim($_POST["track_title"]);
    //debugCT($trackDescription);
    $res = $db->dbQuery("UPDATE draft_degree_tracks
								SET `track_description`='?',
								`track_title`='?'
								WHERE `track_code`='?'
								AND `major_code`='?' 
								AND `catalog_year`='?' ", $trackDescription, $trackTitle, $track, $major, $deCatalogYear);
  }




  $msg = "<font color='green' size='4'>Degree updated successfully at " . getCurrentTime() . ".</font>";
  $boolScroll = $boolButtonMsg = true;
  if ($errors != "")
  {
    $msg = $errors;
    $boolScroll = $boolButtonMsg = false;
  }

  displayEditSpecificDegree($msg, $boolScroll, $boolButtonMsg);
}

function getCurrentTime()
{  
  //////////////////////////////////
  // Check for hooks...
  $function = "admin_getCurrentTime";
  if (function_exists($function)) {
    return call_user_func($function);
  }
  //////////////////////////////////
    
  $datetime              = date ("g:i:sa");
  return $datetime;
}




function popupStudentsUsingCourse()
{
  //////////////////////////////////
  // Check for hooks...
  $function = "admin_popupStudentsUsingCourse";
  if (function_exists($function)) {
    return call_user_func($function);
  }
  //////////////////////////////////
  
  
  global $db, $screen;
  $courseID = $_REQUEST["courseID"];
  $course = new course($courseID);
  $course->boolUseDraft = true;
  $course->loadDescriptiveData();

  $pC = "";
  $pC .= "<b>Top 150 Students who have taken $course->subjectID $course->courseNum ($courseID):</b>
			<br><br>
	
			<table border='1'>
		";

  
  // Let's pull the needed variables out of our settings, so we know what
	// to query, because this involves non-FlightPath tables.
	$tsettings = $GLOBALS["fpSystemSettings"]["extraTables"]["course_resources:student_courses"];
	$tf = (object) $tsettings["fields"];  //Convert to object, makes it easier to work with.  
	$tableName = $tsettings["tableName"];  
  
  $res = $db->dbQuery("
						SELECT * FROM $tableName							
							WHERE 
								$tf->subjectID = '$course->subjectID'
								AND $tf->courseNum = '$course->courseNum'
								LIMIT 150	") ;

  while($cur = $db->dbFetchArray($res)) {
    $studentID = $cur[$tf->studentID];
    $hours = $cur[$tf->hoursAwarded];
    $grade = $cur[$tf->grade];
    $termID = $cur[$tf->termID];
    
    $pC .= "<tr>
					<td valign='top' class='tenpt'>$studentID</td>
					<td valign='top' class='tenpt'>$hours</td>
					<td valign='top' class='tenpt'>$grade</td>
					<td valign='top' class='tenpt'>$termID</td>
				
					
				</tr>
				";
  }

  $pC .= "</table>";

  $screen->pageTitle = "FlightPath Admin - Students Taken Course";
  $pC .= getJS();

  $screen->pageIsPopup = true;
  $screen->pageHideReportError = true;
  //include("template/fp_template.php");

  $screen->pageContent = $pC;
  // send to the browser
  $screen->outputToBrowser();


}



function popupGroupsUsingCourse()
{
  //////////////////////////////////
  // Check for hooks...
  $function = "admin_popupGroupsUsingCourse";
  if (function_exists($function)) {
    return call_user_func($function);
  }
  //////////////////////////////////
  
  
  global $db, $screen;
  $courseID = $_REQUEST["courseID"];
  $course = new course($courseID, false, null, false, "", true);
  $course->loadDescriptiveData();

  $pC = "";
  $pC .= "<b>Groups using $course->subjectID $course->courseNum ($courseID),
				Only showing top level groups (will not display if course
				only appears in branches):</b>
			<br><br>
	
			<table border='1'>
		";

  $res = $db->dbQuery("SELECT * FROM draft_groups a,
							draft_group_requirements b
							WHERE course_id = '?'
							and a.group_id = b.group_id ", $courseID) ;
  $c = 0;
  while($cur = $db->dbFetchArray($res))
  {
    extract ($cur, 3, "db");
    $pC .= "<tr>
					<td valign='top' class='tenpt'>$db_title</td>
					<td valign='top' class='tenpt'>$db_group_name</td>
					<td valign='top' class='tenpt'>$db_catalog_year</td>
					<td valign='top' class='tenpt'><a href='admin.php?performAction=popupShowGroupUse&groupID=$db_group_id'>degree use</a></td>
					
				</tr>
				";
    $c++;
  }

  $pC .= "</table>Count: $c";

  $screen->pageTitle = "FlightPath Admin - Groups Using Course";
  $pC .= getJS();

  $screen->pageIsPopup = true;
  $screen->pageHideReportError = true;
  //include("template/fp_template.php");

  $screen->pageContent = $pC;
  // send to the browser
  $screen->outputToBrowser();


}


function popupDegreesUsingCourse()
{
  
  //////////////////////////////////
  // Check for hooks...
  $function = "admin_popupDegreesUsingCourse";
  if (function_exists($function)) {
    return call_user_func($function);
  }
  //////////////////////////////////
  
  
  global $db, $screen;
  $courseID = $_REQUEST["courseID"];
  $course = new course($courseID);
  $course->boolUseDraft = true;
  $course->loadDescriptiveData();

  $pC = "";
  $pC .= "<b>Degrees using $course->subjectID $course->courseNum ($courseID)
				in the bare degree plan (not in groups):</b>
			<br><br>
	
			<table border='1'>
		";

  $res = $db->dbQuery("SELECT * FROM draft_degrees a,
							draft_degree_requirements b
							WHERE `course_id`='?'
							and a.degree_id = b.degree_id ", $courseID) ;
  $c = 0;
  while($cur = $db->dbFetchArray($res))
  {
    extract ($cur, 3, "db");
    $pC .= "<tr>
					<td valign='top' class='tenpt'>$db_title</td>
					<td valign='top' class='tenpt'>$db_major_code</td>
					<td valign='top' class='tenpt'>$db_catalog_year</td>
					<td valign='top' class='tenpt'>" . getSemesterName($db_semester_num) . "</td>
				</tr>
				";
    $c++;
  }

  $pC .= "</table>Count: $c";

  $screen->pageTitle = "FlightPath Admin - Degrees Using Course";
  $pC .= getJS();

  $screen->pageIsPopup = true;
  $screen->pageHideReportError = true;
  //include("template/fp_template.php");

  $screen->pageContent = $pC;
  // send to the browser
  $screen->outputToBrowser();


}

function popupShowGroupUse()
{
  
  //////////////////////////////////
  // Check for hooks...
  $function = "admin_popupShowGroupUse";
  if (function_exists($function)) {
    return call_user_func($function);
  }
  //////////////////////////////////
  
  
  global $db, $screen;

  $groupID = $_REQUEST["groupID"];
  $group = new Group();
  $group->groupID = $groupID;
  $group->boolUseDraft = true;
  $group->loadDescriptiveData();


  $pC = "";
  $pC .= "<b>Degrees using $group->title (<i>$group->groupName</i>):</b>
		<br><br>
		<table border='0' cellspacing='5'>
		<tr>
			<td><u>Degree</u></td>
			<td><u>Code</u></td>
			<td><u>Semester</u></td>
			<td><u>Year</u></td>
		</tr>
		";

  $res = $db->dbQuery("SELECT * FROM draft_degrees a,
    								draft_degree_requirements b
    							WHERE a.degree_id = b.degree_id
    							AND b.group_id = '?'
    							ORDER BY a.title, a.major_code, b.semester_num ", $groupID);
  while($cur = $db->dbFetchArray($res))
  {
    extract($cur, 3, "db");
    $pC .= "<tr>
					<td valign='top' class='tenpt' width='200'>
						$db_title
					</td>
					<td valign='top' class='tenpt' width='100'>
						$db_major_code
					</td>
					<td valign='top' class='tenpt' align='center'>
						" . getSemesterName($db_semester_num) . "
					</td>
					<td valign='top' class='tenpt' width='100'>
						$db_catalog_year
					</td>
					
				</tr>
				";

  }


  $pC .= "</table>";

  $screen->pageTitle = "FlightPath Admin - Group Use";
  $pC .= getJS();

  $screen->pageIsPopup = true;
  $screen->pageHideReportError = true;
  //include("template/fp_template.php");

  $screen->pageContent = $pC;
  // send to the browser
  $screen->outputToBrowser();



}

function getSemesterName($semesterNum)
{
  
  //////////////////////////////////
  // Check for hooks...
  $function = "admin_getSemesterName";
  if (function_exists($function)) {
    return call_user_func($function, $semesterNum);
  }
  //////////////////////////////////
  
  
  $ar = array("Freshman Year", "Sophomore Year", "Junior Year", "Senior Year");
  $s = $ar[$semesterNum];
  if ($s == "")
  {
    $s = "Year " . ($semesterNum + 1);
  }
  return $s;
}



function popupSelectIcon()
{
  //////////////////////////////////
  // Check for hooks...
  $function = "admin_popupSelectIcon";
  if (function_exists($function)) {
    return call_user_func($function);
  }
  //////////////////////////////////
  
  
  global $db, $screen;

  $groupID = $_REQUEST["groupID"];
  $group = new Group();
  $group->groupID = $groupID;
  $group->boolUseDraft = true;
  $group->loadDescriptiveData();

  $pC = "<b>Please Select an Icon to use for $group->title (<i>$group->groupName</i>):</b>
			<div class='tenpt'>Current icon: <img src='$screen->themeLocation/images/icons/$group->iconFilename' width='19'>
			$group->iconFilename.
			<br><br>
			Available Icons:
				<blockquote>";
  $handle = opendir("$screen->themeLocation/images/icons/.");
  $files = array();


  while($file = readdir($handle))
  {
    if ($file != "." && $file != ".." && $file != ".svn")
    {
      $files[] = $file;
    }
  }
  // make sure they are alphabetized.
  sort($files);


  foreach($files as $file)
  {
    $pC .= "<div style='padding: 5px;'>
				<input type='button' value='Select -> ' onClick='popupSelectIcon(\"$file\");' >
				&nbsp; &nbsp;
				<img src='$screen->themeLocation/images/icons/$file' width='19'>
				$file</div>";
  }


  $pC .= "</blockquote></div>";
  $screen->pageTitle = "FlightPath Admin - Select Icon";
  $pC .= getJS();

  $screen->pageIsPopup = true;
  $screen->pageHideReportError = true;

  $screen->pageContent = $pC;
  // send to the browser
  $screen->outputToBrowser();


}

function popupAddGroup()
{
  //////////////////////////////////
  // Check for hooks...
  $function = "admin_popupAddGroup";
  if (function_exists($function)) {
    return call_user_func($function);
  }
  //////////////////////////////////
  if (!userHasPermission("deCanAdministerDataEntry")) {
    displayAccessDenied();
  }
  
  
  global $db,$screen;
  $semesterNum = trim($_GET["semesterNum"]);
  $deCatalogYear = $GLOBALS["deCatalogYear"];
  $pC = "";

  $pC .= "<b>Add an elective group to semester: $semesterNum in $deCatalogYear</b><br>
				<span class='tenpt'>Use keyboard shortcut CTRL-F to find groups quickly.</span>
				<br><br>
				First, select a group (from $deCatalogYear):
				<div class='tenpt' 
					style='height:200px; overflow-y: scroll; border: 1px solid black;
					margin:5px;'>
				<table border='0' cellspacing='5'>";

  $res = $db->dbQuery("SELECT * FROM draft_groups
							WHERE `catalog_year`='?'
							AND `delete_flag`='0'
							ORDER BY `title` ", $deCatalogYear);
  while($cur = $db->dbFetchArray($res))
  {
    extract($cur, 3, "db");
    $pC .= "<tr><td valign='middle'>
		            <input type='radio' name='rgroups' value='$db_group_id'></td>
					<td valign='top' class='tenpt'>
					$db_title<br><i>$db_group_name</i>
					</td>
				</tr>
					
				";
  }


  $pC .= "</table></div>
			Next, select properties for this group:
			<a href='javascript: popupAlertHelp(\"group_properties\");'>?</a>
			<div class='tenpt' style='padding-top: 5px;'>
			Hrs: <input type='text' name='hours' id='hours' size='2'>
			Min Grade: <select name='minGrade' id='minGrade'>
							<option value=''>--</option>
							<option value='C'>C</option>
							<option value='B'>B</option>
							<option value='A'>A</option>
							
						</select>
			Type: <select name='type' id='type'>
					<option value='m'>m - Major</option>
					<option value='c'>c - Core</option>
					<option value='s'>s - Supporting</option>
					<option value='e'>e - Elective</option>
					</select>
				&nbsp; &nbsp;
			<input type='button' value='Add Group ->'
				onClick='popupAddGroup(\"$semesterNum\");'>
			</div>";


  $screen->pageTitle = "FlightPath Admin - Add Group";
  $pC .= getJS();

  $screen->pageIsPopup = true;
  $screen->pageHideReportError = true;
  //include("template/fp_template.php");
  $screen->pageContent = $pC;
  // send to the browser
  $screen->outputToBrowser();


}


function popupEditDefinition()
{
  //////////////////////////////////
  // Check for hooks...
  $function = "admin_popupEditDefinition";
  if (function_exists($function)) {
    return call_user_func($function);
  }
  //////////////////////////////////
  if (!userHasPermission("deCanAdministerDataEntry")) {
    displayAccessDenied();
  }
  
  
  global $db, $screen;
  $groupID = trim($_REQUEST["groupID"]);
  $deCatalogYear = $GLOBALS["deCatalogYear"];
  $group = new Group($groupID, null, -1, false, true);
  $group->loadDescriptiveData();

  $definition = trim($_REQUEST["definition"]);
  if ($definition == "")
  {
    $definition = $group->definition;
  }

  $results = getCoursesFromDefinition($definition);
  //$results = getCoursesFromDefinition($definition, $deCatalogYear);

  $pC = "";

  $pC .= "<b>Edit Definition for $group->title ($deCatalogYear)<br><i>$group->groupName</i></b>
<br><br><form action='admin.php?performAction=popupEditDefinition&deCatalogYear=$deCatalogYear&groupID=$groupID' method='POST' id='mainform'>
	<table border='0'>
	<tr>
			<td valign='top' align='right'>
			<div class='tenpt' align='left'>Working Definition:
			 <a href='javascript:popupAlertHelp(\"definition\");'>?</a></div>
			<textarea name='definition' id='definition' rows='10' cols='15' >$definition</textarea>
			<br>
			<input type='button' value='Generate ->'
				onClick='showUpdate(); document.getElementById(\"mainform\").submit();'>
		</td>
		<td valign='top' align='right' class='tenpt'>
			<div class='tenpt' align='left'>Results:" . " (" . $results["count"] . ")</div>
			<textarea rows='10' cols='35' readonly=readonly
				style='background-color: lightgray;'>{$results["text"]}</textarea>
				<br>
				(loads courses from all catalog years)
		</td>
	</tr>
	</table>
	</form>
	If you are satisfied with the results of this definition, 
	click the Save to Group button.  Otherwise, simply close this window.
	
	
	<input type='button' value='Save to Group' onClick='popupSaveDefinition(\"\");'>
	
	";



  $screen->pageTitle = "FlightPath Admin - Edit Definition";
  $pC .= getJS();

  $screen->pageIsPopup = true;
  $screen->pageHideReportError = true;

  $screen->pageContent = $pC;
  // send to the browser
  $screen->outputToBrowser();


}


function getCoursesFromDefinition($definition, $catalogYear = "")
{
  //////////////////////////////////
  // Check for hooks...
  $function = "admin_getCoursesFromDefinition";
  if (function_exists($function)) {
    return call_user_func($function, $definition, $catalogYear);
  }
  //////////////////////////////////
  
  
  
  $groupArray = array();

  // Okay, first things first, let's trim this sucker and remove extra whitespace.
  $definition = trim($definition);
  $definition = str_replace("   "," ",$definition);
  $definition = str_replace("  "," ",$definition);
  $definition = str_replace("  "," ",$definition);

  // Okay, now let's break this up into lines...
  $dLines = split("\n",$definition);
  foreach($dLines as $line)
  {
    $line = trim($line);

    // Let's get each of the parts... the instruction, and the course data.
    $tokens = split(" ", $line);
    $instruction = strtolower(trim($tokens[0]));
    $courseData = trim($tokens[1]);

    // We know that the course data can also be broken up, by the .
    $cTokens = split("\.", $courseData);
    $subjectData = trim(strtoupper($cTokens[0]));
    $courseNumData = trim(strtoupper($cTokens[1]));

    // Okay, so now, for this line, we have an instruction,
    // and some course data (possibly wild cards) to act on.
    //debugCT("$instruction $subjectData $courseNumData");

    $tArray = getCourseArrayFromDefinitionData($subjectData, $courseNumData, $catalogYear);
    // Okay, we got our list.  Now what do we do with them?
    if ($instruction == "add" || $instruction == "+")
    {
      $groupArray = array_merge($groupArray, $tArray);
      $groupArray = array_unique($groupArray);
    }

    if ($instruction == "remove" || $instruction == "rem" || $instruction == "-" || $instruction == "del")
    {
      //print "<pre>" . print_r($tArray) . "</pre>";
      //debugCT(count($groupArray));
      //$groupArray = array_diff($groupArray, $tArray);
      $groupArray = rp_array_diff($groupArray, $tArray);

      $groupArray = array_unique($groupArray);
      //debugCT(count($groupArray));
    }



  }

  // Here's what we need to do:
  // In groupArray, we have the subject_id and course_num of every course in this definition.
  // We need to convert them to courseID's from the table,
  // and make sure we do not have duplicates.
  // First, get an array of courseID from the groupArray...
  $courseIDArray = $groupArray;
  // Take out duplicate entries (caused by eqv courses)...

  $courseIDArray = array_unique($courseIDArray);
  //print_r($courseIDArray);
  //debugCT(sizeof($courseIDArray));
  // Now, convert BACK into the "groupArray" structure (subject_id and course_num)...
  $groupArray2 = getCourseArrayFromCourseIDArray($courseIDArray);

  //print_r($groupArray);

  // Place in alphabetical order.
  sort($groupArray2);

  //var_dump($groupArray2);

  $rtn = array();
  $count = 1;
  // Now that we have the groupArray, we will turn it into a string...
  for ($t = 0; $t < count($groupArray2); $t++)
  {
    $line = trim($groupArray2[$t]);
    if ($line == "~~" || $line == "") continue;
    $count++;
    $temp = split(" ~~ ", $line);
    $si = trim($temp[0]);
    $cn = trim($temp[1]);

    $rtn["text"] .= "$si $cn\n";


  }
  $rtn["text"] = str_replace("&", "_A_", $rtn["text"]);
  //debugCT(count($groupArray));
  $rtn["count"] = $count;

  return $rtn;
}

function getCourseArrayFromCourseIDArray($courseIDArray)
{
  //////////////////////////////////
  // Check for hooks...
  $function = "admin_getCourseArrayFromCourseIDArray";
  if (function_exists($function)) {
    return call_user_func($function, $courseIDArray);
  }
  //////////////////////////////////
  
  
  
  // Convert an array of courseID's into their subjectID ~~ courseNum format.
  // Pick non-excluded courses over excluded courses, when you have the option.
  $rtnArray = array();
  $db = new DatabaseHandler();


  // MUST use foreach since we used array_unique earlier.  Can't use
  // count($arr) after you use array_unique!!
  foreach($courseIDArray as $t => $value)
  {
    $newCourse = new Course();
    $newCourse->boolUseDraft = true;
    $newCourse->db = $db;
    $newCourse->courseID = $courseIDArray[$t];
    $newCourse->loadDescriptiveData(false);
    array_push($rtnArray, "$newCourse->subjectID ~~ $newCourse->courseNum");
  }
  return $rtnArray;

}


function getCourseIDArrayFromCourseArray($courseArray)
{
  //////////////////////////////////
  // Check for hooks...
  $function = "admin_getCourseIDArrayFromCourseArray";
  if (function_exists($function)) {
    return call_user_func($function, $courseArray);
  }
  //////////////////////////////////
  
  
  $rtnArray = array();
  $db = new DatabaseHandler();

  // MUST use foreach instead of for since we did
  // array_unique!  Can't trust count($arr)!
  foreach($courseArray as $t => $value)
  {
    $line = trim($courseArray[$t]);
    if ($line == "~~" || $line == "") continue;
    $count++;
    $temp = split(" ~~ ", $line);
    $si = trim($temp[0]);
    $cn = trim($temp[1]);

    $courseID = $db->getCourseID($si, $cn, "", true);
    $rtnArray[] = "$courseID";  // force into a string.
  }

  return $rtnArray;
}


function rp_array_diff($array1, $array2)
{
  //////////////////////////////////
  // Check for hooks...
  $function = "admin_rp_array_diff";
  if (function_exists($function)) {
    return call_user_func($function, $array1, $array2);
  }
  //////////////////////////////////
  
  
  // Return an array of values from array1 that
  // are NOT in array2.
  // This is my (Richard Peacock) own implementation of array_diff,
  // because something is broken with it, so I am programming
  // my own.
  $rtn = array();
  //for ($t = 0; $t < count($array1); $t++)
  // MUST use foreach instead of for(count($arr))
  // because I did array_unique on it!
  foreach($array1 as $t => $value)
  {
    if (in_array($array1[$t], $array2))
    {
      continue;
    } else {
      $rtn[] = $array1[$t];
    }
  }
  return $rtn;

}


function getCourseArrayFromDefinitionData($subjectData, $courseNumData, $catalogYear = "")
{
  
  //////////////////////////////////
  // Check for hooks...
  $function = "admin_getCourseArrayFromDefinitionData";
  if (function_exists($function)) {
    return call_user_func($function, $subjectData, $courseNumData, $catalogYear);
  }
  //////////////////////////////////
  
  
  global $db;
  // Looks at the subjectData and courseNumData fields, and constructs
  // a query to pull our every course which matches it.

  $rtnArray = array();

  $si = str_replace("*","%",$subjectData);
  $cn = str_replace("*","%",$courseNumData);

  $catalogLine = "";
  if ($catalogYear != "")
  {
    $catalogLine = "AND `catalog_year`='$catalogYear'";
  }

  
  $query = "SELECT * FROM draft_courses
				WHERE 
					`subject_id` LIKE '?'
				AND `course_num` LIKE '?'
				AND `course_id` > 0
				$catalogLine
				GROUP BY subject_id, course_num
				";
  $res = $db->dbQuery($query, $si, $cn) ;
  while ($cur = $db->dbFetchArray($res))
  {
    $courseID = $cur["course_id"];
    
    if (in_array($courseID, $rtnArray)) continue;
    $rtnArray[] = $courseID;
    
  }

  return $rtnArray;
}



function initHiddenVariables()
{
  
  //////////////////////////////////
  // Check for hooks...
  $function = "admin_initHiddenVariables";
  if (function_exists($function)) {
    return call_user_func($function);
  }
  //////////////////////////////////
  
  
  global $db;
  $settings = $db->getFlightPathSettings();


  $GLOBALS["deCatalogYear"] = trim($_REQUEST["deCatalogYear"]);

  if ($GLOBALS["deCatalogYear"] == "")
  {
    // default value.
    $GLOBALS["deCatalogYear"] = $settings["currentCatalogYear"];
  }
}


function getHiddenVariables()
{
  // Supply the HTML for hidden variables needed by the data entry system.
  $rtn = "";

  $rtn .= "
				<input type='hidden' name='deCatalogYear' value='{$GLOBALS["deCatalogYear"]}' id='deCatalogYear'>
				
				<input type='hidden' name='performAction2' value='' id='performAction2'>
				<input type='hidden' name='scrollTop' value='' id='scrollTop'>
			";

  return $rtn;
}



function displayEditSpecificGroup($msg = "", $boolScroll = true)
{
  //////////////////////////////////
  // Check for hooks...
  $function = "admin_displayEditSpecificGroup";
  if (function_exists($function)) {
    return call_user_func($function, $msg, $boolScroll);
  }
  //////////////////////////////////
  if (!userHasPermission("deCanAdministerDataEntry")) {
    displayAccessDenied();
  }
  
  
  global $screen, $db;
  $deCatalogYear = $GLOBALS["deCatalogYear"];
  $groupID = $_REQUEST["groupID"];
  $pC = "";

  $pC .= "<a class='tenpt' href='admin.php?performAction=editGroups&deCatalogYear=$deCatalogYear#group_$groupID'>Back to Group List</a>  &nbsp; - &nbsp;
			<a class='tenpt' href='admin.php?deCatalogYear=$deCatalogYear'>Back to main menu.</a>
			";
  if ($_SESSION["deAdvancedMode"] == true)
  {
    $pC .= " <span class='tenpt' style='background-color: yellow; margin-left: 20px;'>
					adv: groupID = $groupID.
					Used by:
					<a href='javascript: popupWindow(\"admin.php?performAction=popupShowGroupUse&groupID=$groupID\");'>[degrees]</a>
				  </span>";
  }

  $group = new Group($groupID, null, -1, false, true);
  //print_pre($group->toString());
  $group->loadDescriptiveData();


  $pC .= "<h2>Edit Group: $group->title ($deCatalogYear)</h2>$msg";
  $pC .= "<form action='admin.php' method='POST' id='mainform'>
			<input type='hidden' name='deCatalogYear' value='$deCatalogYear'>
			<input type='hidden' name='performAction' value='performEditSpecificGroup'>
			<input type='hidden' name='performAction2' value='' id='performAction2'>
			<input type='hidden' name='setDefinition' id='setDefinition' value=''>
			<input type='hidden' name='scrollTop' id='scrollTop' value=''>
			<input type='hidden' name='groupID' value='$groupID'>
			
			<div class='tenpt'>
			
			<table border='0'>
			<tr>
				<td valign='top' class='tenpt'>
					FlightPath Group Title:
				</td>
				<td valign='top' class='tenpt'>
					<input type='text' name='title' value='$group->title' maxlength='100' size='50'> 
					<a href='javascript: popupAlertHelp(\"group_title\");'>?</a> 	
			</td>
			</tr>
			<tr>
				<td valign='top' class='tenpt'>
					Internal Group Name: 
				</td>
				<td valign='top' class='tenpt'>
					<input type='text' name='groupName' value='$group->groupName' maxlength='100' size='50'> 
					<a href='javascript: popupAlertHelp(\"group_name\");'>?</a>
				</td>
			</tr>
			<tr>
				<td valign='top' class='tenpt'>
					Priority: 
				</td>
				<td valign='top' class='tenpt'>
					<input type='text' name='priority' value='$group->priority' maxlength='5' size='5'> 
					<a href='javascript: popupAlertHelp(\"group_priority\");'>?</a>
				</td>
			</tr>
			<tr>
				<td valign='top' class='tenpt'>
					Icon: 
				</td>
				<td valign='top' class='tenpt'>
					<input type='hidden' name='iconFilename' id='iconFilename' value='$group->iconFilename'> 
					<img src='$screen->themeLocation/images/icons/$group->iconFilename' width='19' id='iconSrc'> <span id='iconFn'>$group->iconFilename</span>
						 - <a href='javascript: popupWindow(\"admin.php?performAction=popupSelectIcon&groupID=$groupID\");'>[select another]</a>
				</td>
			</tr>
			<tr>
				<td valign='top' class='tenpt'>
					Definition: 
				</td>
				";
  $dheight = "";
  if (strlen($group->definition) > 100)
  {
    $dheight = "height: 150px;";
  }
  $pC .= "
				<td valign='top' class='tenpt'>
					<a href='javascript: popupWindow(\"admin.php?performAction=popupEditDefinition&deCatalogYear=$deCatalogYear&groupID=$groupID\");'>[edit definition]</a>
					<div class='tenpt' style='overflow: auto; $dheight'>
					<i>" . nl2br($group->definition) . "</i>
					</div>
				</td>
			</tr>
			
			</table>
			<hr>
			";

  $reqBoxExtra = $reqBoxStyle = $reqBoxExplain = "";
  if (trim($group->definition) != "")
  {
    // Meaning, we have a definition specified, so disable the
    // required courses box!
    $reqBoxExtra = "readonly=readonly";
    $reqBoxStyle = "background-color: lightgray;";
    $reqBoxExplain = "<div class='tenpt' style='padding-top: 10px;'>
							<b>Note:</b> Because a definition was specified,
							you cannot directly edit the Required Courses
							box.  Please manage specific courses using the
							Edit Definition window.
							</div>";
  }

  $courses = "";
  // Create the courses variable from all the requirements in this group.
  $courses = getGroupCourses($group);
  $pC .= "Required Courses: (<a href='javascript: popupAlertHelp(\"group_entry\");'>Help - entering min grades and/or repeats</a>)$reqBoxExplain<br>
			<textarea name='courses' style='line-height: 1.5em;$reqBoxStyle' wrap='OFF' rows='17' cols='80' $reqBoxExtra>$courses</textarea>
			<br>
			<input type='button' value='Save for $deCatalogYear' onClick='submitForm();'><br><br>
			<b>[Optional]</b> Comment: (only seen by data entry administrators)<br>
			<textarea name='dataEntryComment' rows='3' cols='80'>$group->dataEntryComment</textarea>
			<br>
				<div align='right'>
					Delete this group? <input type='button' value='X'
									onClick='deleteGroup(\"$groupID\");'>
				</div>			
			</div>
			";




  $pC .= "</form>";

  $pC .= getJS();
  $screen->pageTitle = "FlightPath Admin - Edit Group";

  if ($boolScroll)
  {
    $screen->pageScrollTop = trim($_POST["scrollTop"]);
  }
  $screen->pageHideReportError = true;
  //include("template/fp_template.php");

  $screen->pageContent = $pC;
  // send to the browser
  $screen->outputToBrowser();


  if ($_REQUEST["serialize"] != "")
  {
    print "<br><textarea rows=20 cols=80>" . serialize($group) . "</textarea>";
  }
}


function getGroupCourses(Group $group)
{
  //////////////////////////////////
  // Check for hooks...
  $function = "admin_getGroupCourses";
  if (function_exists($function)) {
    return call_user_func($function, $group);
  }
  //////////////////////////////////
  
  
  // Returns a plain text list of the courses in a group's requirements
  // for use in the editSpecificGroup page.
  $rtn = "";

  // courses not in branches...
  $courses = array();
  $cCount = 0;
  $group->listCourses->loadCourseDescriptiveData();
  $group->listCourses->sortAlphabeticalOrder();
  $group->listCourses->resetCounter();
  while($group->listCourses->hasMore())
  {
    $c = $group->listCourses->getNext();
    if (strstr($c->subjectID , "&"))
    {
      $c->subjectID = str_replace("&", "_A_", $c->subjectID);

    }
    $courseLine = "$c->subjectID $c->courseNum";
    //$rtn .= "$c->subjectID $c->courseNum";

    if ($c->minGrade != "" && $c->minGrade != "D")
    {
      //$rtn .= " ($c->minGrade)";
      $courseLine .= " ($c->minGrade)";
    }

    //$rtn .= "\n";
    if ($courses[$courseLine] == "")
    {
      $courses[$courseLine] = 0;
    }
    // This is to check for specified repeats.
    $courses[$courseLine]++;

  }

  // Go through the $courses array to check for specified repeats.
  foreach($courses as $course => $repCount)
  {
    $repLine = " [$repCount]";
    if ($repCount == 1)
    {
      $repLine = "";
    }
    $rtn .= "$course$repLine\n";
  }



  // Now, get them branches!
  if (!$group->listGroups->isEmpty)
  {
    $group->listGroups->resetCounter();
    while ($group->listGroups->hasMore())
    {
      $g = $group->listGroups->getNext();

      $g->listCourses->loadCourseDescriptiveData();
      $g->listCourses->sortAlphabeticalOrder();
      $g->listCourses->resetCounter();
      while($g->listCourses->hasMore())
      {
        $c = $g->listCourses->getNext();
        if (strstr($c->subjectID , "&"))
        {
          $c->subjectID = str_replace("&", "_A_", $c->subjectID);
        }

        $rtn .= "$c->subjectID $c->courseNum";

        if ($c->minGrade != "" && $c->minGrade != "D")
        {
          $rtn .= " ($c->minGrade)";
        }

        $rtn .= "  &  ";

      }

      // Take off the last &.
      $rtn = trim($rtn);
      $rtn = substr($rtn,0,-1);
      $rtn = trim($rtn);

      $rtn .= "\n";

    }
  }


  return $rtn;
}

function displayEditSpecificDegree($msg = "", $boolScrollPage = false, $boolButtonMsg = true)
{
  //////////////////////////////////
  // Check for hooks...
  $function = "admin_displayEditSpecificDegree";
  if (function_exists($function)) {
    return call_user_func($function, $msg, $boolScrollPage, $boolButtonMsg);
  }
  //////////////////////////////////
  if (!userHasPermission("deCanAdministerDataEntry")) {
    displayAccessDenied();
  }
  
  
  global $screen, $db;
  $deCatalogYear = $GLOBALS["deCatalogYear"];
  $majorCode = $_REQUEST["majorCode"];

  if ($msg == "")
  {
    $msg = "<font size='4'>&nbsp; &nbsp;</font>";
  }

  $buttonMsg = $msg;
  if ($boolButtonMsg == false) {$buttonMsg = "";}

  $degreeID = intval($db->getDegreeID($majorCode, $deCatalogYear, true));
  // The intval says, if it's false, make it = 0.  otherwise keep the number
  // that is returned.
  $degree = new DegreePlan($degreeID, null, false, false, true);
  $degree->loadDescriptiveData();
  //var_dump($degree);


  $pC = "";

  $pC .= "<a class='tenpt' href='admin.php?performAction=editDegrees&deCatalogYear=$deCatalogYear#degree_$degreeID'>Back to Degree List</a>  &nbsp; - &nbsp;
			<a class='tenpt' href='admin.php?deCatalogYear=$deCatalogYear'>Back to main menu.</a>
			
			";
  if ($_SESSION["deAdvancedMode"] == true)
  {
    $pC .= " <span class='tenpt' style='background-color: yellow; margin-left: 20px;'>
					advanced: degreeID = $degreeID.
				  </span>";
  }

  $pC .= "<form id='mainform' action='admin.php' method='POST'>";

  $pC .= "<div style='font-size: 16pt; font-weight:bold; padding-top: 20px;'>$degree->degreeType $degree->title<br>$majorCode ($deCatalogYear)</div>";
  $pC .= "
			<table>
			 <tr>
				<td valign='top' class='tenpt' width='15%'>Degree Type:</td>
				<td valign='top' class='tenpt' width='15%'><input type='text' name='degree_type' value='$degree->degreeType' size='5' maxlength='20'></td>

				<td valign='top' class='tenpt' width='15%'>Degree Class:</td>
				<td valign='top' class='tenpt'><input type='text' name='degree_class' value='$degree->degreeClass' size='2' maxlength='1'>
				<a href='javascript: popupAlertHelp(\"degree_class\");'>?</a></td>
			 </tr>
				
			 </tr>
			 <tr>
				<td valign='top' class='tenpt'>Degree Title:</td>
				<td valign='top' class='tenpt' colspan='3'><input type='text' name='title' value='$degree->title' size='80' maxlength='100'></td>
			 </tr>
			 <tr>
				<td valign='top' class='tenpt'>Exclude:</td>
				<td valign='top' class='tenpt' colspan='3'><input type='text' name='exclude' value='$degree->dbExclude' size='2' maxlength='1'>
				<a href='javascript: popupAlertHelp(\"degree_exclude\");'>?</a></td>
			 </tr>
			 
			 
			 
			</table> ";

  if (strstr($majorCode, "_"))
  {
    $pC .= "<b>Edit track information:</b>
			<blockquote style='margin-top: 0px; margin-bottom: 0px;'>
		<font size='2'>Track title: <input type='text' name='track_title' value='$degree->trackTitle' size='60' maxlength='100'></font><br>
		<font size='2'>Track description: <a href='javascript: popupAlertHelp(\"edit_formatting\");'>(Help - Formatting)</a>
						<a href='javascript: popupAlertHelp(\"track_description\");'>(Help - Track Descriptions)</a>
		</font><br>
		<textarea name='track_description' cols='60' rows='3'>" . convertHTMLToBBCode($degree->trackDescription) . "</textarea>
		</blockquote>
		";
  }
  $pC .= "<div class='tenpt' align='center'>(Scroll to the bottom of the page for more options)</div>
	
			$msg";

  $pC .= "
			<input type='hidden' name='performAction' value='performEditSpecificDegree'>
			<input type='hidden' name='majorCode' value='$majorCode'> ";

  $pC .= "
				
			
			";
  $pC .= getHiddenVariables();



  $degree->listSemesters->resetCounter();
  while ($degree->listSemesters->hasMore())
  {
    $semester = $degree->listSemesters->getNext();
    if ($semester->semesterNum < 0)
    {
      continue;
    }
    $semDefaultTitle = getSemesterName($semester->semesterNum);
    if ($semester->title == $semDefaultTitle)
    {
      $semester->title = "";
    }

    $pC .= "<div class='elevenpt' style='padding-bottom: 30px;'>
					<b>Block number: " . ($semester->semesterNum +1) . "</b>
					&nbsp; &nbsp; &nbsp; &nbsp;
					Default title: $semDefaultTitle
					&nbsp; &nbsp;
					Override: <input type='text' name='semester_title_$semester->semesterNum' value='$semester->title' size='20'>
					<a href='javascript: popupAlertHelp(\"semester_title\");'>?</a>
					<table border='1' width='100%'>
					";
    // Get the courses.
    $pC .= "<tr><td valign='top'>
					<textarea name='courses_$semester->semesterNum' rows='10' cols='20'>";
    $semester->listCourses->sortAlphabeticalOrder();
    $semester->listCourses->resetCounter();
    while($semester->listCourses->hasMore())
    {
      $course = $semester->listCourses->getNext();
      $course->loadDescriptiveData();
      $pC .= "$course->subjectID $course->courseNum $course->requirementType";
      if ($course->minGrade != "D" && $course->minGrade != "")
      {
        $pC .= " ($course->minGrade)";
      }
      $pC .= "\n";
    }
    $pC .= "</textarea>
		<div class='tenpt'>(<a href='javascript: popupAlertHelp(\"degree_entry\");'>Help - entering requirements, min grades, and repeats</a>)</div>
		</td>";

    // Get the groups...
    $pC .= "<td valign='top' class='tenpt' width='100%'>
					<table width='100%' border='0' cellspacing='5'>
					<tr>
						<td valign='top' class='tenpt' width='1'>&nbsp;</td>
						<td valign='top' class='tenpt'>Group</td>
						<td valign='top' class='tenpt' width='5'>hrs</td>
						<td valign='top' class='tenpt' width='5'>grd</td>
						<td valign='top' class='tenpt' width='5'>type</td>
						</tr>";
    $semester->listGroups->sortAlphabeticalOrder();
    $semester->listGroups->resetCounter();
    while($semester->listGroups->hasMore())
    {
      $group = $semester->listGroups->getNext();
      $group->loadDescriptiveData();
      $pC .= "<tr><td valign='middle'><input type='button' value='x' style='width:15px; height:20px;' onClick='delGroup(\"$group->groupID\",\"$semester->semesterNum\");'></td>
						<td valign='top' class='tenpt'>
						$group->title<br><i>$group->groupName</i></td>
						<td valign='top' class='tenpt'>$group->hoursRequired</td>
						<td valign='top' class='tenpt'>$group->minGrade</td>
						<td valign='top' class='tenpt'>$group->requirementType
						<input type='hidden' name='group_" . $group->groupID . "_" . rand(1,999999) . "' value='$group->groupID" . "_$semester->semesterNum" . "_$group->hoursRequired" . "_$group->requirementType" . "_$group->minGrade'>
						</td>";	
    }
    $pC .= "</table>
				<div style='margin-top: 10px; margin-left: 20px;'>
					<a href='javascript:popupWindow(\"admin.php?performAction=popupAddGroup&semesterNum=$semester->semesterNum&deCatalogYear=$deCatalogYear\");'>Add an elective group</a></div>
		</td>";


    $pC .= "</table><br><input type='button' onClick='submitForm();' value='Save for $deCatalogYear'> $buttonMsg</div>";
    $sCount = $semester->semesterNum+1;
  }

  // Always add an additional 4 semesters to the bottom.
  for ($t = 0; $t < 4; $t++)
  {
    $sem = $t + $sCount;
    if ($sem > 49)
    {
      // Max number of semesters.  More or less arbitrarily set number.
      $pC .= "<br>Maximum number of semesters created.<br>";
      break;
    }

    $pC .= "<div class='elevenpt' style='padding-bottom: 30px;'>
					<b>Block number: " . ($sem+1) . "</b>
					&nbsp; &nbsp; &nbsp; &nbsp;
					Default title: " . getSemesterName($sem) . "
					&nbsp; &nbsp;
					Override: <input type='text' name='semester_title_$sem' value='' size='20'>
					<a href='javascript: popupAlertHelp(\"semester_title\");'>?</a>
					
					<table border='1' width='100%'>
					";

    $pC .= "<tr><td valign='top'>
					<textarea name='courses_$sem' rows='10' cols='20'>";
    $pC .= "</textarea></td>";

    // the groups...
    $pC .= "<td valign='top' class='tenpt' width='100%'>
					<table width='100%' border='0' cellspacing='5'>
					<tr>
						<td valign='top' class='tenpt' width='1'>&nbsp;</td>
						<td valign='top' class='tenpt'>Group</td>
						<td valign='top' class='tenpt'>hrs</td>
						<td valign='top' class='tenpt'>grd</td>
						<td valign='top' class='tenpt'>type</td>
						</tr>";
    $pC .= "</table>
				<div style='margin-top: 10px; margin-left: 20px;'>
					<a href='javascript:popupWindow(\"admin.php?performAction=popupAddGroup&semesterNum=$sem&deCatalogYear=$deCatalogYear\");'>Add an elective group</a></div>
		</td>";


    $pC .= "</table><br><input type='button' onClick='submitForm();' value='Save for $deCatalogYear'> $buttonMsg</div>";

  }
  $pC .= "<div class='elevenpt'>If you need more semester boxes, simply save this page, and additional blank
			boxes will appear below.</div>
			
			<br><br>
			<div class='elevenpt'><b>More Options:</b><br>
			Enter a public note for this degree: 
			 <a href='javascript: popupAlertHelp(\"public_note\");'>(Help - Public Note)</a>
			 <a href='javascript: popupAlertHelp(\"edit_formatting\");'>(Help - Formatting)</a>
			<br>
			<textarea name='public_note' rows='4' cols='80'>$degree->publicNote</textarea>
			
			
			</div>
			
			
			<input type='button' onClick='submitForm();' value='Save for $deCatalogYear'> $buttonMsg</div>
			
			"; 




  $pC .= "</form>";

  $pC .= "				<div align='right'>
					Delete this degree? <input type='button' value='X'
									onClick='deleteDegree(\"$degreeID\");'>
				</div>			
        ";


  $pC .= getJS();
  $screen->pageTitle = "FlightPath Admin - Edit Degree";

  if ($boolScrollPage == true)
  {
    $screen->pageScrollTop = trim($_POST["scrollTop"]);
  }
  $screen->pageHideReportError = true;
  //include("template/fp_template.php");
  $screen->pageContent = $pC;
  // send to the browser
  $screen->outputToBrowser();


  if ($_REQUEST["serialize"] != "")
  {
    print "<br><textarea rows=20 cols=80>" . serialize($degree) . "</textarea>";
  }

}

function convertHTMLToBBCode($str)
{
  //////////////////////////////////
  // Check for hooks...
  $function = "admin_convertHTMLToBBCode";
  if (function_exists($function)) {
    return call_user_func($function, $str);
  }
  //////////////////////////////////
  
  
  $str = str_replace("<b>","[b]",$str);
  $str = str_replace("</b>","[/b]",$str);
  $str = str_replace("<i>","[i]",$str);
  $str = str_replace("</i>","[/i]",$str);
  $str = str_replace("<u>","[u]",$str);
  $str = str_replace("</u>","[/u]",$str);

  return $str;
}


function displayEditDegrees($msg = "")
{
  //////////////////////////////////
  // Check for hooks...
  $function = "admin_displayEditDegrees";
  if (function_exists($function)) {
    return call_user_func($function, $msg);
  }
  //////////////////////////////////
  if (!userHasPermission("deCanAdministerDataEntry")) {
    displayAccessDenied();
  }
  
  
  global $screen, $db;
  $db2 = new DatabaseHandler();
  $deCatalogYear = $GLOBALS["deCatalogYear"];
  $pC = "";

  $pC .= "<a class='tenpt' href='admin.php?deCatalogYear=$deCatalogYear'>Back to main menu.</a><br>
			<h2 style='margin-bottom: 5px;'>Edit Degrees for $deCatalogYear</h2>$msg
			<div class='tenpt'>
				<a href='admin.php?performAction=addNewDegree&deCatalogYear=$deCatalogYear'>Add new degree plan (major, concentration, or track)</a>.
				&nbsp; &nbsp; | &nbsp; &nbsp;
				<a href='admin.php?performAction=copyDegree&deCatalogYear=$deCatalogYear'>Copy a degree plan</a>.
				<br><br>
			</div>
			<div class='tenpt' style='background-color: lightgray;'>
				Majors or Tracks marked as \"exclude\" are in gray.  You may pull up students with these
				majors in FlightPath, but they will not be options in the What If mode.
			</div>
			<br>
			<div class='tenpt' align='center'>
				Use <b>CTRL-F</b> to find degrees more quickly.
			</div>
			";

  $res = $db->dbQuery("SELECT * FROM draft_degrees
							WHERE `catalog_year`='?'
						 ORDER BY degree_type, major_code, title ", $deCatalogYear);

  while($cur = $db->dbFetchArray($res))
  {
    $db_exclude = 0;
    extract($cur, 3, "db");

    if ($db_degree_type == "NA" && strstr($db_major, "|"))
    {
      $db_degree_type = " -- ";
    }

    $bgc = "white";
    if ($db_exclude == "1")
    {
      $bgc = "lightgray";
    }

    /*
    $onMouseOver = " onmouseover=\"style.backgroundColor='#FFFF99'\"
    onmouseout=\"style.backgroundColor='$bgc'\" ";
    */

    $img = "spacer.gif";

    // get JUST the major code...
    $temp = split("\|", $db_major_code);
    $justMajor = trim($temp[0]);
    $justConc = trim($temp[1]);
    $outside = "";

    //if ($justConc != "" && strstr($justConc, "_"))
    if (strstr($justConc, "_"))
    {
      // If the concentration has an underscore, it's actually
      // a track.  Let's get the track title...
      $temp2 = split("_",$justConc);
      $justTrack = trim($temp2[1]);
      // Might need to add the first part BACK onto the major...
      if (trim($temp2[0]) != "")
      {
        $justMajor .= "|" . trim($temp2[0]);
      }


      $res2 = $db2->dbQuery("SELECT * FROM draft_degree_tracks
								WHERE `catalog_year`='?'
								AND `major_code`='?'
								AND `track_code`='?' ", $deCatalogYear, $justMajor, $justTrack);
      if ($db2->dbNumRows($res2) > 0)
      {
        $cur2 = $db2->dbFetchArray($res2);

        $db_title = trim($cur2["track_title"]);
        $outside = "----&gt;";
        if (strstr($justMajor, "|"))
        { // both a conc AND a track. Denote it special.
          $outside = ">>" . $outside;
        }
        $db_degree_type = "";

      }
    } else if($justConc != "")
    {
      // Meaning, this is a concentration, NOT a track.
      $db_degree_type = "";
      $outside = "&gt;&gt;";
    }



    $pC .= "<a name='degree_$db_degree_id'></a>";
    $pC .= "<div class='elevenpt' style='padding-bottom: 3px; padding-top: 3px; background-color: $bgc'
						$onMouseOver>
					<img src='$screen->themeLocation/images/$img' width='16'> $outside
						<a href='admin.php?performAction=editSpecificDegree&deCatalogYear=$deCatalogYear&majorCode=$db_major_code' class='degree-$db_degree_class'>
							$db_degree_type $db_title $db_major_code</a>
				</div>";



  }

  $pC .= "<style>
            a.degree-G {
              color: green;
            }
            
            a.degree-G:visited {
              color: DarkOliveGreen;
            }
            

          </style>";
  
  $screen->pageTitle = "FlightPath Admin - Degrees";

  $screen->pageHideReportError = true;
  //include("template/fp_template.php");
  $screen->pageContent = $pC;
  // send to the browser
  $screen->outputToBrowser();


}



function displayEditGroups($msg = "")
{
  //////////////////////////////////
  // Check for hooks...
  $function = "admin_displayEditGroups";
  if (function_exists($function)) {
    return call_user_func($function, $msg);
  }
  //////////////////////////////////
  if (!userHasPermission("deCanAdministerDataEntry")) {
    displayAccessDenied();
  }
  
  
  global $screen, $db;

  $db2 = new DatabaseHandler();
  $deCatalogYear = $GLOBALS["deCatalogYear"];
  $pC = "";

  $pC .= "<a class='tenpt' href='admin.php?deCatalogYear=$deCatalogYear'>Back to main menu.</a><br>
			<h2 style='margin-bottom:2px;'>Edit Groups for $deCatalogYear</h2>$msg
        Options:
				<ul style='margin-top: 5px;'>
				  <li><a href='admin.php?performAction=editSpecificGroup&groupID=new&deCatalogYear=$deCatalogYear'>Add a new group to this year</a><br>
					</li>
					<li><a href='javascript: processDefinitions($deCatalogYear);'>Process all group definitions for this year</a>
					</li>
				</ul>
					<div align='center'>(Hint: use CTRL-F to search groups)</div>
				<table border='0' cellpadding='2'>
				<tr>
					<td><b>Title</b></td>
					<td><b>Internal Name</b></td>
					<td align='center'><b>Pri</b></td>
					<td align='center'><b>i</b></td>
					<td align='center'><b>Used</b></td>
				</tr>
			";

  $onMouseOver = " onmouseover=\"style.backgroundColor='#FFFF99'\"
      				onmouseout=\"style.backgroundColor='white'\" ";

  $res = $db->dbQuery("SELECT * FROM draft_groups
							WHERE `catalog_year`='?'
							AND `delete_flag`='0'
							ORDER BY `title`, `group_name` ", $deCatalogYear);
  while($cur = $db->dbFetchArray($res))
  {
    extract($cur, 3, "db");

    $useCount = 0;
    // Find out how many degree plans are using this particular group...

    $res2 = $db->dbQuery("SELECT count(id) AS count FROM draft_degree_requirements
								WHERE `group_id`='$db_group_id' ");
    if ($db->dbNumRows($res2) > 0)
    {
      $cur2 = $db->dbFetchArray($res2);
      $useCount = $cur2["count"];
    }

    $defFlag = "";
    if (trim($db_definition) != "")
    {
      $defFlag = " (*)";
    }

    if ($db_title == "")
    {
      $db_title = "[NO TITLE SPECIFIED]";
    }

    $pC .= "<tr $onMouseOver>
					<td valign='top' class='tenpt'><a name='group_$db_group_id'></a>
						<a href='admin.php?performAction=editSpecificGroup&groupID=$db_group_id&deCatalogYear=$deCatalogYear'>
							$db_title</a>
					</td>
					<td valign='top' class='tenpt'>
						<i>$db_group_name</i>$defFlag
					</td>
					<td valign='top' class='tenpt'>
						$db_priority
					</td>
					<td valign='top' class='tenpt'>
						<img src='$screen->themeLocation/images/icons/$db_icon_filename' width='19'>
					</td>
					<td valign='top' class='tenpt'>
						$useCount <a href='javascript: popupWindow(\"admin.php?performAction=popupShowGroupUse&groupID=$db_group_id\");'><img src='$screen->themeLocation/images/popup.gif' border='0'></a>
					</td>

					
				</tr>"; 
  }

  $pC .= "</table>";

  $pC .= getJS();
  $screen->pageTitle = "FlightPath Admin - Groups";

  $screen->pageContent = $pC;
  // send to the browser
  $screen->outputToBrowser();


}


function performEditUrgentMsg()
{
  
  //////////////////////////////////
  // Check for hooks...
  $function = "admin_performEditUrgentMsg";
  if (function_exists($function)) {
    return call_user_func($function);
  }
  //////////////////////////////////
  if (!userHasPermission("deCanAdministerDataEntry")) {
    displayAccessDenied();
  }
  
  
  
  global $db;

  $urgentMsg = trim(strip_tags($_POST["urgentMsg"]));
  $db->setSettingsVariable("urgentMsg", $urgentMsg);
  
  displayEditUrgentMsg(getSuccessMsg("Successfully updated urgent message at " . getCurrentTime()));
}



function performEditOfflineMode()
{
  //////////////////////////////////
  // Check for hooks...
  $function = "admin_performEditOfflineMode";
  if (function_exists($function)) {
    return call_user_func($function);
  }
  //////////////////////////////////
  if (!userHasPermission("deCanAdministerDataEntry")) {
    displayAccessDenied();
  }
  
  
  global $db;

  $offlineMsg = trim(strip_tags($_POST["offlineMsg"]));
  $db->setSettingsVariable("offlineMsg", $offlineMsg);
  
  $offlineMode = trim(strip_tags($_POST["offlineMode"]));
  $db->setSettingsVariable("offlineMode", $offlineMode);
  

  displayEditOfflineMode(getSuccessMsg("Successfully updated offline mode settings at " . getCurrentTime()));
}


function performEditFlightPathSettings()
{
  //////////////////////////////////
  // Check for hooks...
  $function = "admin_performEditFlightPathSettings";
  if (function_exists($function)) {
    return call_user_func($function);
  }
  //////////////////////////////////
  if (!userHasPermission("deCanAdministerDataEntry")) {
    displayAccessDenied();
  }
  
  
  global $db;

  $availableAdvisingTermIDs = trim(strip_tags($_POST["availableAdvisingTermIDs"]));
  $db->setSettingsVariable("availableAdvisingTermIDs", $availableAdvisingTermIDs);
  
  $advisingTermID = trim(strip_tags($_POST["advisingTermID"]));
  $db->setSettingsVariable("advisingTermID", $advisingTermID);

  
  $currentCatalogYear = trim(strip_tags($_POST["currentCatalogYear"]));
  $db->setSettingsVariable("currentCatalogYear", $currentCatalogYear);  

  $currentDraftCatalogYear = trim(strip_tags($_POST["currentDraftCatalogYear"]));
  $db->setSettingsVariable("currentDraftCatalogYear", $currentDraftCatalogYear);

  // Save the entire post to the log.
  $postXML = fp_arrayToXml("post",$_POST, true);
  $db->addToLog("admin_edit_settings","",$postXML);


  displayEditFlightPathSettings(getSuccessMsg("Successfully updated FlightPath advising settings at " . getCurrentTime()));
}


function performEditAnnouncements()
{
  //////////////////////////////////
  // Check for hooks...
  $function = "admin_performEditAnnouncements";
  if (function_exists($function)) {
    return call_user_func($function);
  }
  //////////////////////////////////
  if (!userHasPermission("deCanAdministerDataEntry")) {
    displayAccessDenied();
  }
  
  
  global $db;
  $xmlArray = array();
  // Saves the announcements in the POST.
  foreach($_POST as $key => $value)
  {
    if (strstr($key, "datetime"))
    {
      // Get the count #.
      $temp = split("_",$key);
      $count = trim($temp[1]);

      $dt = strtotime($_POST["datetime_$count"]);
      if ($dt == 0)
      {
        $dt = rand(1,99999);
      }
      $announcementText = trim(strip_tags($_POST["announcement_$count"]));
      if ($announcementText == "")
      {
        continue;
      }

      $visible = trim($_POST["visible_$count"]);

      // UPDATE the xmlArray...
      $xmlArray["dt_$dt"] = "$visible ~~ $announcementText";

    }
  }
  // Now, convert to XML and UPDATE the table.
  $xml = fp_arrayToXml("announcements",$xmlArray);
  $db->setSettingsVariable("announcements_xml", $xml);
  
  
  // Save the entire post to the log.
  $postXML = fp_arrayToXml("post",$_POST, true);
  $db->addToLog("admin_edit_announcements","",$postXML);


  displayEditAnnouncements(getSuccessMsg("Successfully updated announcements at " . getCurrentTime()));
}

function getSuccessMsg($msg)
{
  //////////////////////////////////
  // Check for hooks...
  $function = "admin_getSuccessMsg";
  if (function_exists($function)) {
    return call_user_func($function, $msg);
  }
  //////////////////////////////////
  
  
  return "<div style='color: green; font-size: 16pt; padding-bottom:20px;'>$msg
			<div class='tenpt' style='color: black;'>You may continue editing, or return to the main menu
				by following the link above.</div>
			</div>
			";
}


function displayEditFlightPathSettings($msg = "")
{
  //////////////////////////////////
  // Check for hooks...
  $function = "admin_displayEditFlightPathSettings";
  if (function_exists($function)) {
    return call_user_func($function, $msg);
  }
  //////////////////////////////////
  if (!userHasPermission("deCanAdministerDataEntry")) {
    displayAccessDenied();
  }
  
  
  global $screen, $db;
  $deCatalogYear = $GLOBALS["deCatalogYear"];
  $pC = "";
  $pC .= "<a class='tenpt' href='admin.php?deCatalogYear=$deCatalogYear'>Back to main menu.</a><br>
			<h2>Edit FlightPath Advising Settings</h2>$msg
			<div class='elevenpt'>
			<form action='admin.php' method='post'>
			<input type='hidden' name='performAction' value='performEditFlightPathSettings'>";

  $settings = $db->getFlightPathSettings();

  $pC .= "FP Term Quick Reference:";
  $sems = array(40,41, 60,81,82);
  for($t = $settings["currentCatalogYear"]; $t <= $settings["currentCatalogYear"] + 2; $t++) {
    $pC .= "<div style='padding-left: 15px;'>";
    foreach($sems as $sm) {
      $tryYear = $t . $sm;
      $course = new Course();
      $course->termID = $tryYear;
      $pC .= "" . $course->getTermDescription(true) . ": <b>$course->termID</b>, &nbsp; &nbsp; &nbsp;";
    }
    $pC .= "</div>";
  }  
  
  $pC .= "<br>
      Available Advising Terms: (seperate by commas. Ex: 200940,200941,200960)<br>
			<input type='text' name='availableAdvisingTermIDs' value='{$settings["availableAdvisingTermIDs"]}' maxlength='100' size='40'>
			<div style='font-size:8pt;'>* Make sure to list these in order, so they will appear in order in FP.</div>
			<br>
			Default advising term:<br>
			<input type='text' name='advisingTermID' value='{$settings["advisingTermID"]}' maxlength='100' size='40'>
			<div style='font-size:8pt;'>* Of the Available Advising Terms, this should be the default that FlightPath is set to
					when an advisor logs in. Ex: 200940.</div>
			
			<br>
			Current catalog year:<br>
			<input type='text' name='currentCatalogYear' value='{$settings["currentCatalogYear"]}' maxlength='100' size='40'>
			<div style='font-size:8pt;'>* This is the year that What If loads degrees from, as well as several other important functions.  
				Only change this once you have
						fully loaded a new catalog year.</div>

			<br>
			Current <b>DRAFT</b> catalog year:<br>
			<input type='text' name='currentDraftCatalogYear' value='{$settings["currentDraftCatalogYear"]}' maxlength='100' size='40'>
			<div style='font-size:8pt;'>* <b>While in Draft mode</b>, this is the year that What If loads degrees from, as well as several other important functions.  
				You may change this while working on a new catalog.  It will not
				affect any other users of the system.  While not working on a new catalog,
				set this to the same as the Current catalog year.</div>
						
			";


  $pC .= "<br><br><input type='submit' value='Save'>
			</form></div>";


  $pC .= getJS();

  $screen->pageTitle = "FlightPath Admin - FlightPath Settings";
  $screen->pageHideReportError = true;
  //include("template/fp_template.php");

  $screen->pageContent = $pC;
  // send to the browser
  $screen->outputToBrowser();


}

function displayCopyDegree($msg = "")
{
  //////////////////////////////////
  // Check for hooks...
  $function = "admin_displayCopyDegree";
  if (function_exists($function)) {
    return call_user_func($function, $msg);
  }
  //////////////////////////////////
  if (!userHasPermission("deCanAdministerDataEntry")) {
    displayAccessDenied();
  }
  
  
  
  global $screen, $db;
  $deCatalogYear = $GLOBALS["deCatalogYear"];
  $pC = "";
  $pC .= "<a class='tenpt' href='admin.php?performAction=editDegrees&deCatalogYear=$deCatalogYear'>Back to Degrees List</a>
			&nbsp; - &nbsp;
			<a class='tenpt' href='admin.php?deCatalogYear=$deCatalogYear'>Back to main menu</a>
			
			<br>
			<h2>Copy Degree for $deCatalogYear</h2>$msg
      
      <div>Use this form to copy (duplicate) a degree plan <b>in this
      catalog year</b>.</div>
					<br><br>
			<form action='admin.php' method='post'>
			 <input type='hidden' name='deCatalogYear' value='$deCatalogYear'>
			 <input type='hidden' name='performAction' value='performCopyDegree'>
			 
			 Enter the SOURCE major code you wish to copy:<br>
			 Source: <input type='text' name='sourceMajorCode' size='5'>
			 <div style='font-size: 8pt;'><b>Ex: ART or GBUS</b>. Do not enter any track or concentration codes here.</div>
			 <br>
			 <input type='checkbox' name='includeTracks' value='yes'>Include
			   tracks and concentrations?
			 <div style='font-size: 8pt;'>Check this box if you wish to also copy any
			   tracks and concentrations this major code may have associated with it.
			   If you do not check this box, only the base degree will be copied.  If
			   the major does not have tracks and concentrations, leave this unchecked.
			   </div>
			 
			 <br><br>
			 Enter the DESTINATION major code here:
			 <br>Destination: <input type='text' name='destinationMajorCode' size='5'>
			 <div style='font-size: 8pt'><b>Ex: CHEM or XYZ</b>. Do not enter any track or concentration codes here.
			  <br>
			  <b>Note:</b> if the destination major already exists, it, and <b>all its tracks and concentrations</b>
			         will be deleted for <b>$deCatalogYear</b>!</div>
			   
			 
			 <br><br>
			 <input type='submit' value='Submit' onClick='return confirmDegreeCopy();'>
			</form>

			<script type='text/javascript'>
			
			function confirmDegreeCopy() {
			 var srcMajor = document.getElementsByName('sourceMajorCode')[0].value;
			 var destMajor = document.getElementsByName('destinationMajorCode')[0].value;
			 
			 var x = confirm('Are you sure you wish to copy  ' + srcMajor + '  to  ' + destMajor + ' ? If  ' + destMajor + '  already exists, it will be completely deleted, including all tracks and concentrations!');
			 return x;
			}
			
			</script>
			
  ";  
  
  $screen->pageTitle = "FlightPath Admin - Copy Degree";

  //include("template/fp_template.php");
  $screen->pageContent = $pC;
  // send to the browser
  $screen->outputToBrowser();
  
}



function displayAddNewDegree($msg = "")
{
  //////////////////////////////////
  // Check for hooks...
  $function = "admin_displayAddNewDegree";
  if (function_exists($function)) {
    return call_user_func($function, $msg);
  }
  //////////////////////////////////
    
  if (!userHasPermission("deCanAdministerDataEntry")) {
    displayAccessDenied();
  }
  
  global $screen, $db;
  $deCatalogYear = $GLOBALS["deCatalogYear"];
  $pC = "";
  $pC .= "<a class='tenpt' href='admin.php?performAction=editDegrees&deCatalogYear=$deCatalogYear'>Back to Degrees List</a>
			&nbsp; - &nbsp;
			<a class='tenpt' href='admin.php?deCatalogYear=$deCatalogYear'>Back to main menu</a>
			
			<br>
			<h2>Add New Degree for $deCatalogYear</h2>$msg
	
			You may use this screen to add a new degree, by entering a new
			major, concentration, or track (degree option).
			
			<form action='admin.php' method='post'>
			<input type='hidden' name='deCatalogYear' value='$deCatalogYear'>
			<input type='hidden' name='performAction' value='performAddNewDegree'>
			Please select an option:<br>
				<blockquote>
				<input type='radio' name='newMajor' value='new' checked>Enter a <b>new</b> major code [and concentration]<br>
				<input type='radio' name='newMajor' value='existing'>Enter an <b>existing</b> major code [and concentration] (only adding a new track)<br>
				&nbsp; &nbsp; Major|Conc code: <input type='text' name='majorCode' value='' size='8'>
					<div style='font-size: 9pt; padding-left: 20px;'>
					To enter a concentration code, use MAJOR|CONC.  The | character is
					call the pipe, and it should under the backspace key.
					If adding a new concentration to an existing major,
					you still put this in as a NEW major code.  Do not have
					any spaces in this box.  The concentration code is optional.  If the
					major does not have a concentration, then simply enter the major code
					by itself.
					</div>
				
				<br>

				<input type='radio' name='newTrack' value='new'>Enter a <b>new</b> track (degree option) code<br>
				<input type='radio' name='newTrack' value='none' checked>N/A  (not adding a track. Leave blank)<br>
				&nbsp; &nbsp; Track code: <input type='text' name='trackCode' value='' size='4'>
				
				
				</blockquote>
				<input type='submit' value='Submit'>
			</form>
		";








  $screen->pageTitle = "FlightPath Admin - Add New Degree";

  $screen->pageContent = $pC;
  // send to the browser
  $screen->outputToBrowser();


}


function displayApplyDraftChanges($msg = "")
{
  //////////////////////////////////
  // Check for hooks...
  $function = "admin_displayApplyDraftChanges";
  if (function_exists($function)) {
    return call_user_func($function, $msg);
  }
  //////////////////////////////////
  if (!userHasPermission("deCanAdministerDataEntry")) {
    displayAccessDenied();
  }
  
  
  
  global $screen, $db;
  $deCatalogYear = $GLOBALS["deCatalogYear"];
  $curCat = $screen->settings["currentCatalogYear"];
  $pC = "";
  $pC .= "<a class='tenpt' href='admin.php?deCatalogYear=$deCatalogYear'>Back to main menu.</a><br>
			<h2>Apply Draft Changes</h2>$msg
			<div class='elevenpt'>
			<form action='admin.php?deCatalogYear=$deCatalogYear' method='post'>
			<input type='hidden' name='performAction' value='performApplyDraftChanges'>";


  $pC .= "	You can use this form to apply your draft changes to the production database,
				making changes to degrees, courses, and groups visible
				to all users of the system.
				<br><br>
				
				<b>For added security</b> you must enter the transfer passcode:
				<input type='password' name='passcode' size='10'><br><br>
	
			<input type='submit' value='Submit'> (May take several seconds or minutes. Please click only ONCE).
			
			</form></div>";


  $pC .= getJS();

  $screen->pageTitle = "FlightPath Admin - Apply Draft Changes";

  $screen->pageContent = $pC;
  // send to the browser
  $screen->outputToBrowser();

}



/**
 * I don't believe this function is being used any longer
 * in FlightPath, but I am going to leave it in as it may be
 * useful again one day.
 *
 * @param unknown_type $msg
 * @return unknown
 */
function displayTransferData($msg = "")
{
  //////////////////////////////////
  // Check for hooks...
  $function = "admin_displayTransferData";
  if (function_exists($function)) {
    return call_user_func($function, $msg);
  }
  //////////////////////////////////
  
  
  global $screen, $db;
  $deCatalogYear = $GLOBALS["deCatalogYear"];
  $curCat = $screen->settings["currentCatalogYear"];
  $pC = "";
  $pC .= "<a class='tenpt' href='admin.php?deCatalogYear=$deCatalogYear'>Back to main menu.</a><br>
			<h2>Transfer Data to Production Server</h2>$msg
			<div class='elevenpt'>
			<form action='admin.php?deCatalogYear=$deCatalogYear' method='post'>
			<input type='hidden' name='performAction' value='performTransferData'>";


  $pC .= "For some small amounts of data, you can automatically transfer
				data to the production server using this screen.  For
				Degrees, Courses, and Groups, you must still request a data
				transfer, as that amount of data is too large for this process.
					
				<br><br>
				Check what data you would like to transfer to production:
				<blockquote>
				<input type='radio' name='transfer' value='settings' checked=checked> Announcements, Urgent Msg, and FlightPath settings.<br>
				&nbsp; &nbsp; &nbsp; &nbsp; (current catalog year is <b>$curCat</b>)<br>
				<input type='radio' name='transfer' value='users'> User Privileges.<br>
				<input type='radio' name='transfer' value='help'> Help pages.<br>
				
				
							</blockquote>	
			";


  $pC .= "
			<b>For added security</b> you must enter the transfer passcode:
				<input type='password' name='passcode' size='10'><br><br>
	
			<input type='submit' value='Submit'> (May take several moments. Please click only ONCE).
			
			</form></div>";


  $pC .= getJS();

  $screen->pageTitle = "FlightPath Admin - Transfer Data";

  $screen->pageContent = $pC;
  // send to the browser
  $screen->outputToBrowser();

}



function displayEditHelp($msg = "")
{
  //////////////////////////////////
  // Check for hooks...
  $function = "admin_displayEditHelp";
  if (function_exists($function)) {
    return call_user_func($function, $msg);
  }
  //////////////////////////////////
  if (!userHasPermission("deCanAdministerDataEntry")) {
    displayAccessDenied();
  }
  
  
  global $screen, $db;
  $deCatalogYear = $GLOBALS["deCatalogYear"];
  $pC = "";
  $pC .= "<a class='tenpt' href='admin.php?deCatalogYear=$deCatalogYear'>Back to main menu.</a><br>
			<h2>Edit Help</h2>$msg
			<div class='elevenpt'>
			<form action='admin.php' method='post' name='form1' id='form1'>
			<input type='hidden' name='performAction' value='editHelp'>";

  $pC .= "Select a page to edit:
					<select name='pageID'>
					<option value='0'>-- Please select --</option>
					";

  $res = $db->dbQuery("SELECT * FROM help ORDER BY `id` ");
  while ($cur = $db->dbFetchArray($res))
  {
    $pC .= "<option value='{$cur["id"]}'>{$cur["id"]} : {$cur["title"]}</option> \n";
  }
  $pC .= "<option value='new'> - Create a NEW page - </option>
				</select>
		
		<input type='submit' value='Load page ->'>
		</form>";
  $pageID = trim($_POST["pageID"]);

  if ($pageID < 1 && $pageID != "new") {$pageID = 1;} // default to main page.

  $helpPage = $db->getHelpPage($pageID);

  $pageURL = "";

  $pC .= "
		<hr>
		<b>Editing page:</b> ";
  if ($pageID != "new")
  {

    $pC .= "(<a href='help.php?i=$pageID' target='_blank'>click to load page in new window</a>)";
    $pageURL = "<br>Page URL: <tt style='background-color: beige;'>help.php?i=$pageID</tt>
						 <br>&nbsp; &nbsp; &nbsp; BBCode popup link (loads in a popup window): <span style='background-color: beige;'>[popup=help.php?i=$pageID]Click here![/popup]</span>
						 <!-- <br>&nbsp; &nbsp; &nbsp; BBCode internal link (loads in same window): <span style='background-color: beige;'>[url2=help.php?i=$pageID]Click here![/url2]</span> -->
						 ";
    $db->addToLog("admin_edit_help","$pageID");

  }
  $pC .= "
		<br>
		<form method='post' action='admin.php' name='mainform' id='mainform'>
		<input type='hidden' name='performAction' value='performEditHelp'>
		<input type='hidden' name='scrollTop' id='scrollTop' value=''>
		<input type='hidden' name='pageID' value='$pageID'>
		";		


  $pC .= "Page: $pageID $pageURL<br>Title: <input type='text' name='title' value='{$helpPage["title"]}' maxlength='100' size='60'>
					<br>Body: <font size='1'><b>Trouble with Copy/Paste? Use keyboard shortcuts CTRL-C and CTRL-V.</b></font><br>
					<textarea name='body' rows='20' cols='80'>{$helpPage["body"]}</textarea>
					<br><br>
						";


  $pC .= "<input type='button' onClick='submitForm();' value='Save'>
			</form></div>";


  $pC .= getJS();
  $pC .= getJSTinyMCE();

  $screen->pageTitle = "FlightPath Admin - Help";

  $screen->pageHideReportError = true;
  $screen->pageContent = $pC;
  $screen->pageScrollTop = $_POST["scrollTop"];
  // send to the browser
  $screen->outputToBrowser();


}



function displayEditUrgentMsg($msg = "")
{
  //////////////////////////////////
  // Check for hooks...
  $function = "admin_displayEditUrgentMsg";
  if (function_exists($function)) {
    return call_user_func($function, $msg);
  }
  //////////////////////////////////
  if (!userHasPermission("deCanAdministerDataEntry")) {
    displayAccessDenied();
  }
  
  
  global $screen, $db;
  $deCatalogYear = $GLOBALS["deCatalogYear"];
  $pC = "";
  $pC .= "<a class='tenpt' href='admin.php?deCatalogYear=$deCatalogYear'>Back to main menu.</a><br>
			<h2>Edit Urgent Message</h2>$msg
			<div class='elevenpt'>
			Any message you enter here will be displayed at the top of every page
			in the system.  This is used to alert users that the system is about
			to be taken offline, or any other urgently-needed information.<br><br>
			To delete this message, simple delete all the text in the box and save.
			<br><br>
			<a href='javascript: popupAlertHelp(\"edit_formatting\");'>Click to view <b>formatting</b> help.</a>
			</div>
			<hr><div class='elevenpt'><form action='admin.php' method='post'>
			<input type='hidden' name='performAction' value='performEditUrgentMsg'>";

  $settings = $db->getFlightPathSettings();

  $pC .= "Urgent Message:
					<br>
					<textarea name='urgentMsg' rows='3' cols='60'>{$settings["urgentMsg"]}</textarea>
					<br><br>
						";


  $pC .= "<input type='submit' value='Save'>
			</form></div>";


  $pC .= getJS();

  $screen->pageTitle = "FlightPath Admin - Urgent Message";

  //include("template/fp_template.php");
  $screen->pageHideReportError = true;
  $screen->pageContent = $pC;
  // send to the browser
  $screen->outputToBrowser();


}



function displayEditOfflineMode($msg = "")
{
  //////////////////////////////////
  // Check for hooks...
  $function = "admin_displayEditOfflineMode";
  if (function_exists($function)) {
    return call_user_func($function, $msg);
  }
  //////////////////////////////////
  if (!userHasPermission("deCanAdministerDataEntry")) {
    displayAccessDenied();
  }
  
  
  
  global $screen, $db;
  $deCatalogYear = $GLOBALS["deCatalogYear"];
  $pC = "";
  $pC .= "<a class='tenpt' href='admin.php?deCatalogYear=$deCatalogYear'>Back to main menu.</a><br>
			<h2>Set/Unset Offline Mode</h2>$msg
			<div class='elevenpt'>
			If Offline Mode is enabled, logins will be disabled into FlightPath (though
			you will still be able to login to this Administrative Console by going
			directly to flightpath/admin.php).
			</div>
			<hr><div class='elevenpt'><form action='admin.php' method='post'>
			<input type='hidden' name='performAction' value='performEditOfflineMode'>";

  $settings = $db->getFlightPathSettings();

  $pC .= "
          Offline Mode Setting:
          <input type='text' name='offlineMode' size='2' value='{$settings["offlineMode"]}'>
          <div style='font-size: 9pt; padding-left: 10px;'>Enter <b>1</b> (one) to enable, <b>0</b> (zero) to disable</div>
          <br>
          Offline Message:
					<br>
					<textarea name='offlineMsg' rows='3' cols='60'>{$settings["offlineMsg"]}</textarea>
          <div style='font-size: 9pt; padding-left: 10px;'>
            This message will be displayed instead of the login page when OfflineMode is enabled.
            Leave blank for a default message.  BBCode syntax is allowed for 
            extra formatting. <a href='javascript: popupAlertHelp(\"edit_formatting\");'>Click to view <b>formatting</b> help</a>.
            
          </div>					
					<br><br>
						";


  $pC .= "<input type='submit' value='Save'>
			</form></div>";


  $pC .= getJS();

  $screen->pageTitle = "FlightPath Admin - Offline Mode";

  //include("template/fp_template.php");
  $screen->pageHideReportError = true;
  $screen->pageContent = $pC;
  // send to the browser
  $screen->outputToBrowser();


}



function displayEditAnnouncements($msg = "")
{
  //////////////////////////////////
  // Check for hooks...
  $function = "admin_displayEditAnnouncements";
  if (function_exists($function)) {
    return call_user_func($function, $msg);
  }
  //////////////////////////////////
  if (!userHasPermission("deCanAdministerDataEntry")) {
    displayAccessDenied();
  }
  
  
  global $screen, $db;
  $deCatalogYear = $GLOBALS["deCatalogYear"];
  $pC = "";
  $pC .= "<a class='tenpt' href='admin.php?deCatalogYear=$deCatalogYear'>Back to main menu.</a><br>
			<h2>Edit Announcements</h2>$msg
			<div class='elevenpt'>
			These are the current announcements in the system.  To delete an
			announcement, delete the text.  If you need more
			blank boxes, fill these out, then save and come back.
			<br><br>
			<b>For the date/time, type the word <u>NOW</u> for today's date and time.</b><br>
			Enter date/times in this format: <b>YYYY-MM-DD HH:MM:SS</b> (time portion is optional)<br>
			Examples: 2008-12-13 &nbsp; &nbsp; or &nbsp; &nbsp; 2008-10-12 01:10pm  &nbsp; &nbsp; or &nbsp; &nbsp; 2008-11-15 13:14:06
			<br><br>
			<b>Announcements will display on page in order of most recent to oldest.</b><br>
			<a href='javascript: popupAlertHelp(\"edit_formatting\");'>Click to view <b>formatting</b> help.</a>
			</div>
			<hr><div class='elevenpt'><form action='admin.php' method='post'>
			<input type='hidden' name='performAction' value='performEditAnnouncements'>";

  $settings = $db->getFlightPathSettings();

  $count = 0;
  // Pull out just the announcements XML and make it into its own array.
  if ($settings["announcements_xml"] != "")
  {
    if ($xmlArray = fp_xmlToArray2($settings["announcements_xml"]))
    {
      krsort($xmlArray);
      // Expected format of the xmlArray:
      //[dt_timecode] = "announcement text."
      // ex: dt_111234432.  Use strtotime to convert.
      // It begins with dt_ because in XML the start of
      // an element must be a letter, not a number.
      foreach($xmlArray as $datetime => $announcement)
      {
        $dt = str_replace("dt_", "", $datetime);
        $dispTime = date("Y-m-d H:i:s", $dt);
        $pcheck = $fcheck = $hcheck = "";
        $pcheck = "checked=checked";

        // The announcement is split by a " ~~ " between the visibility
        // and the announcement itself.
        $temp = split(" ~~ ", $announcement);
        $vis = trim($temp[0]);
        $announcementText = trim($temp[1]);

        if ($vis == "faculty")
        {
          $pcheck = "";
          $fcheck = "checked=checked";
        }
        if ($vis == "hide")
        {
          $pcheck = "";
          $hcheck = "checked=checked";
        }

        $pC .= "Date/time: <input type='text' name='datetime_$count' value='$dispTime'>
				         <a href='javascript: popupAlertHelp(\"datetime\");'>?</a>
						<br>Visibility:
							<input type='radio' name='visible_$count' value='public' $pcheck>Anyone (incl. students)
							&nbsp; &nbsp; &nbsp;
							<input type='radio' name='visible_$count' value='faculty' $fcheck>Faculty/Staff
							&nbsp; &nbsp; &nbsp;
							<input type='radio' name='visible_$count' value='hide' $hcheck><span style='background-color: beige;'>Hidden</span>
							
				        <br>Announcement:
						<br>
						<textarea name='announcement_$count' id='announcement_$count' rows='4' cols='70'>$announcementText</textarea>
						<div align='left'>
						<a href='javascript: viewAnnouncementPreview(\"$count\");'>View Preview (in popup)</a>
						</div>
						<br><br>
						";

        $count++;
      }
    }
  }

  // add blank rows...
  for ($c = 0; $c < 1; $c++)
  {
    $pC .= "Date/time: <input type='text' name='datetime_$count' value=''>
				         (ex: 2008-01-15 14:02:00)
						<br>Visibility:
						<input type='radio' name='visible_$count' value='public' checked=checked>Anyone (incl. students)
						&nbsp; &nbsp; &nbsp;
						<input type='radio' name='visible_$count' value='faculty'>Faculty/Staff
							&nbsp; &nbsp; &nbsp;
							<input type='radio' name='visible_$count' value='hide'><span style='background-color: beige;'>Hidden</span>
						
				     	<br>Announcement:
						<br>
						<textarea name='announcement_$count' rows='4' cols='70'></textarea>
						<br><br><br>
						";

    $count++;

  }

  $pC .= "<input type='submit' value='Save'>
			</form></div>";


  $pC .= getJS();

  $screen->pageTitle = "FlightPath Admin - Announcements";

  //include("template/fp_template.php");
  $screen->pageHideReportError = true;
  $screen->pageContent = $pC;
  // send to the browser
  $screen->outputToBrowser();


}


function getJSTinyMCE()
{
  //////////////////////////////////
  // Check for hooks...
  $function = "admin_getJSTinyMCE";
  if (function_exists($function)) {
    return call_user_func($function);
  }
  //////////////////////////////////
  
  
  $rtn = '
   <script type    ="text/javascript"
           src     ="./inc/tinymce/jscripts/tiny_mce/tiny_mce.js">
   </script>

   <script type="text/javascript">
   /***************************************************************************
    *  Using Javascript invoke the "tinyMCE" text editor for all textareas.   *
    *  This editable textarea includes a toolbar with such editing functions  *
    *  as font size, bold, italic, underline, fore and background colors,     *
    *  justify left and center, cut, copy, paste, bulleted and numbered       *
    *  lists, indent and outdent, date, time, and print.                      *
    ***************************************************************************/
      tinyMCE.init(
      {
         mode                            : "textareas",
         handle_event_callback           : "myHandleEvent",
         theme_advanced_buttons1         : "fontsizeselect,bold,italic,underline," +
                                           "strikethrough,forecolor,backcolor," +
                                           "separator,undo,redo,separator," +
                                           "justifyleft,justifycenter,justifyright,separator",
         theme_advanced_buttons1_add     : "cut,copy,paste,separator,bullist,numlist,separator,image,link,unlink,table,cell_props",
         theme_advanced_buttons2	     : "",
         theme_advanced_buttons3         : "",
         theme_advanced_toolbar_location : "top",
         theme_advanced_toolbar_align    : "left",
         plugins						 : "table",
         extended_valid_elements         : "hr[class|width|size|noshade]," +
                                           "font[face|size|color|style]," +
                                           "span[class|align|style]"
      });
	
   
   function myHandleEvent(e)
   {
       return true; // Continue handling
   }
      	</script>
	';

  return $rtn;
}


function displayMainMenu($msg = "")
{
  //////////////////////////////////
  // Check for hooks...
  $function = "admin_displayMainMenu";
  if (function_exists($function)) {
    return call_user_func($function, $msg);
  }
  //////////////////////////////////
  
  
  // Main menu for the entry system.

  global $screen, $db;

  $settings = $db->getFlightPathSettings();

  $deCatalogYear = $GLOBALS["deCatalogYear"];
  $pC = "";

  $sp = "&nbsp; &nbsp; &nbsp;";

  $pC .= "<h2>FlightPath Admin Console - Main Menu</h2>
			
			Use the following options to edit the settings and data in FlightPath. <!--<a href='admin.php?performAction=logout'><b>Logout?</b> --></a>
			";
  if (userHasPermission("deCanAdministerUsers")) {
    $pC .= $screen->drawMenuItem("admin.php?performAction=editUsers", "", "<img src='$screen->themeLocation/images/group.png' border='0'>",  "User Management");    
  }
  
  if (userHasPermission("deCanAdministerDataEntry")) {
        
    $pC .= $screen->drawMenuItem("admin.php?performAction=editAnnouncements", "", "<img src='$screen->themeLocation/images/calendar_edit.png' border='0'>",  "Edit Announcements");
    
    $pC .= $screen->drawMenuItem("admin.php?performAction=editUrgentMsg", "", "<img src='$screen->themeLocation/images/error.png' border='0'>",  "Edit Urgent Message");
    
    $pC .= $screen->drawMenuItem("admin.php?performAction=editOfflineMode", "", "<img src='$screen->themeLocation/images/delete.png' border='0'>",  "Set/Unset Offline Mode");
    
    $pC .= $screen->drawMenuItem("admin.php?performAction=editHelp", "", "<img src='$screen->themeLocation/images/page_edit.png' border='0'>",  "Edit Help Pages");
  }


  // Add module's menus....
  $menus = getModulesMenus();  
  if (isset($menus["admin_console"])) {
    $pC .= $screen->drawMenuItems($menus["admin_console"]);
  }

    
  
  
  if (userHasPermission("deCanAdministerDataEntry")) {
    
    $pC .= "	
    <br><br>
  			<div style='border: 1px solid black; padding:5px;'>
  			<a name='demenu'>
  			Data Entry<br><br> 
  			
  			$msg
  			<form action='admin.php#demenu' method='post'> " . getHiddenVariables() . "
  			<input type='hidden' name='performAction' value='performSetCatalogYear'>
  			Editing Catalog Year: <select name='catalogYear'>
  									" . getCatalogYearOptions($deCatalogYear, $settings["currentCatalogYear"]) . "
  									</select>
  									<input type='submit' value='-&gt;'>
  			</form>
  			<ul>
  				<li><a href='admin.php?performAction=editDegrees&deCatalogYear=$deCatalogYear' class='nounderline'>Edit Degree Plans
  							(for $deCatalogYear)</a></li>
  				<li><a href='admin.php?performAction=editGroups&deCatalogYear=$deCatalogYear' class='nounderline'>Edit Groups
  							(for $deCatalogYear)</a></li>
  				<li><a href='admin.php?performAction=editCourses&deCatalogYear=$deCatalogYear' class='nounderline'>Edit Courses
  							(for $deCatalogYear)</a></li>
  			</ul>
  					
  
  			";
    // Do we need to UPDATE any draft changes?
    $res = $db->dbQuery("SELECT * FROM draft_instructions
  	                   ");
    if ($db->dbNumRows($res) > 0)
    {
      $needToApply = "<div class='hypo' style='font-size: 10pt;
                                  padding: 5px;'>
                      <b>Note:</b> Draft changes have been made which have yet to be applied.
                      When you are ready for your draft changes to appear in
                      production, click the link below.
                      </div>";
    }
    $pC .= "
  
  			$needToApply
  			<ul>
  				<!--
  				<li><a href='admin.php?performAction=transferData&deCatalogYear=$deCatalogYear' class='nounderline'>Transfer data to production server</a></li>
  				<li><a href='admin.php?performAction=requestTransfer&deCatalogYear=$deCatalogYear' class='nounderline'>Request large data transfer to production server</a></li>
  				-->
  				<li><a href='admin.php?performAction=applyDraftChanges&deCatalogYear=$deCatalogYear' class='nounderline'>Apply Draft Changes</a></li>
  			</ul>
  			</div> 
  			";
  
  
    $pC .= "
  			<div style='border: 1px solid black; padding:5px;'>
  			 FlightPath Advising Settings<br>
  			
  			 <ul>
  				<li>Available terms for advising: <b>{$settings["availableAdvisingTermIDs"]}</b> </li>
  				<li>Default advising term: <b>{$settings["advisingTermID"]}</b> </li>
  				<li>Current Catalog Year: <b>{$settings["currentCatalogYear"]}</b> </li>
  				<li>Current Draft Catalog Year: <b>{$settings["currentDraftCatalogYear"]}</b> </li>
  				
  			</ul>
  			<a href='admin.php?performAction=editFlightPathSettings'>Edit Settings</a>
  			
  			<br><br>
  			<a href='javascript: confirmClearJDHistory();'>Clear 'John Doe' History</a>
  			</div>
  			</div>";
  }
  else {
    $pC .= "<b>You are seeing a limited view of the Admin Console, because
              you do not have permission to access Data Entry for FlightPath.</b>";
  }
			
		
  $pC .= "	
			<div style='font-size: 8pt; margin-top: 20px;'>Some system icons provided by the Silk icon package, obtained
						from www.famfamfam.com/lab/icons/silk/</div>			
			";


  $pC .= getJS();

  $screen->pageTitle = "FlightPath Admin - Main Menu";

  //include("template/fp_template.php");

  $screen->pageContent = $pC;
  // send to the browser
  $screen->outputToBrowser();


}

function getCatalogYearOptions($selectedYear, $currentCatalogYear)
{
  //////////////////////////////////
  // Check for hooks...
  $function = "admin_getCatalogYearOptions";
  if (function_exists($function)) {
    return call_user_func($function, $selectedYear, $currentCatalogYear);
  }
  //////////////////////////////////
  
  
  
  $rtn = "";

  // Show <options> for the available years.
  $years = array();

  // Check to make sure this has been configured!
  $earliest = $GLOBALS["fpSystemSettings"]["earliestCatalogYear"];
  if ($earliest == "") {
    return "<option value=''>DATA NOT AVAIL. CHECK SYSTEM SETTINGS</option>";
  }
  
  for ($t = $currentCatalogYear + 1; $t >= $GLOBALS["fpSystemSettings"]["earliestCatalogYear"]; $t--)
  {
    $years[] = $t;
  }

  $years[] = 1900;

  //$years = array(1900, 2006,2007,2008,2009);
  foreach ($years as $year)
  {
    $sel = "";
    if ($year == $selectedYear)
    {
      $sel = "SELECTED";
    }
    $rtn .= "<option value='$year' $sel>$year</option>";
  }

  return $rtn;
}

function getJS()
{
  //////////////////////////////////
  // Check for hooks...
  $function = "admin_getJS";
  if (function_exists($function)) {
    return call_user_func($function);
  }
  //////////////////////////////////
  
  
  $rtn = '
	<script type="text/javascript">
	
	function popupAlertHelp(action)
	{
		var t = "Instant Help:\n------------------\n\n";

		if (action == "edit_announcements")
		{
			t = t + "Use this to edit the announcements found on the Main tab in FlightPath.";
			
		}
		if (action == "edit_urgent")
		{
			t = t + "An Urgent Message is one which is displayed at the top of every page in FlightPath, for every user.  Good examples are to warn that the system is about to be taken down.";
		}
		if (action == "user_types")
		{
			t = t + "Available faculty/staff user types in FlightPath:\n\n";
			t = t + "  none - The user may not log into FlightPath.\n";
			t = t + "  limited faculty student - The user is redefined as a student upon login, so that they may only view their own degree plan.\n";
			t = t + "  viewer - The user may search for any student in the system and load their degree plan, but they may not advise them.\n";
			t = t + "  advisor - The user may search for any student in the system and load their degree plan, and they can advise the student.  They cannot perform substitutions.\n";
			t = t + "  college_coordinator - The highest level user in the system (next to administrators). They may advise any student as well as perform substitutions.\n";
			
		}

		
		if (action == "public_note")
		{
			t = t + "A public note will appear at the top of a degree plan when pulled up in FlightPath.  Use this ";
			t = t + "to pass messages to all students and advisors who pull up this degree plan. \n\n";
			t = t + "It will begin with the text \"Important Message:\" automatically.";
		}
		
		
		if (action == "degree_exclude")
		{
			t = t + "If the Exclude value is set to 1 (the number one), then this degree will show up in gray on the list of degrees.  It will also not be selectable in What If mode in FlightPath. ";
			t = t + "If you are not sure what to enter, either leave it blank or enter a 0 (zero).";
		}
		
		if (action == "degree_class")
		{
			t = t + "Enter the degree classification code in this box.  If left blank, it is assumed to be an \"Undergraduate\" degree.\n";
			t = t + "Enter \"G\" for a degree which should only be accessible to Graduate students in What If mode.\n\n";
			t = t + "NOTE: NOT CURRENTLY SUPPORTED IN THIS VERSION OF FLIGHTPATH";
		}
		
		
		if (action == "track_description")
		{
			t = t + "This is where you can enter a short description of this track (also called a Degree Option) which will display for the user in a pop-up when they select to change degree options. ";
			t = t + "\n\nTo enter a default message, which will display at the top of the selection window, begin the description with:\n      DEFAULT: \nIt must be in all caps, and you must have the colon (:) after it. ";
			t = t + "By doing this in ANY of the track descriptions for a major, FP will ignore all other track descriptions and ONLY display the default. ";
			t = t + "\n\nExample of usage:  DEFAULT: You may select any of these degree options.";
		}		
		
		if (action == "degree_entry")
		{
			t = t + "Enter course requirements in this format: \n   SUBJECT COURSENUM type (MINGRADE)\n\n";
			t = t + "  type - lowercase character denoting the requirement type of the course.  Make sure you have a space between it and the course number.  If no type is specified, it is understood to be a major requirement.\n";
			t = t + "  min grade - Place the min grade (if there is one) in parenthesis after the type.  Make sure there is a space between the min grade and the type (or course number, if there is no type specified).\n   Example:  ACCT 110 s (C)\n\n";
			t = t + "Repeats require no special characters or symbols.  Simply enter the course again.";
		}

		if (action == "group_entry")
		{
			t = t + "Enter information about a course in this format:\n   SUBJECT COURSENUM (mingrade) [repeats]\n\n";
			t = t + "Entering minimum grades works the same as it does in Degree entry.  Simply specify the min grade in parenthesis () after the course number.\n   Ex: ";
			t = t + " ACCT 110 (C) \n";
			t = t + "It is very important to have a space between the course number and the min grade.\nSpecifying repeats works the same way, but uses brackets. ";
			t = t + "For example, to specify that a student may take a course 4 times in a group, enter this:\n     ACCT 110 (C) [4]\nThis will designate that the course may be taken a total of 4 times for this group,";
			t = t + "and FlightPath will display a message telling the user such.  You do not need to specify a min grade in order to specify repeats.  For example, ACCT 110 [4].\n\n**VERY IMPORTANT**: You may only specify repeats in groups that DO NOT have branches!";
		}
		
		
		if (action == "edit_formatting")
		{
			t = t + "You may add BOLD, ITALICS, and UNDERLINES to your text by adding simple BBCode tags.\n\n";
			t = t + "To make text appear BOLD, use [b] and [/b] tags.  For example:\n    This is [b]bold[/b] text.\n\n";
			t = t + "Italics and underlines works similarly.\n  Ex:  This is [i]italics[/i] text.  This is [u]underline[/u] text.\n\n";
			t = t + "Other allowed tags:\n\nColors: [color=green]text[/color]  (most common colors allowed)\n";
			t = t + "Line-Break:  [br]  (forces a line break) \n";
			t = t + "Links: [url=http://www.google.com]Click here for Google![/url]\n   -- Links will always load in a new window.\n";
			t = t + "Popups:  [popup=help.php?i=2]Click here for a Help popup[/popup]\n  -- The [popup] tag (works great with Help pages) is ";
			t = t + "just like the [url] tag, except it will load the page in a medium-sized popup window. ";
			

		}

		if (action == "all_years")
		{
			t = t + "Since courses can exist in multiple years (ex: 2006, 2007, and 2008), checking this ";
			t = t + "box gives you the option of updating title, description, and hour information for all instances ";
			t = t + "of this course, in all available catalog years.\n\nOptional comments are NOT updated across years.\nCourse names and eqvs (and excludes) are automatically updated across all years.\n\n";
			t = t + "If unsure what to do, leave this box unchecked. ";

		}
		
		if (action == "semester_title")
		{
			t = t + "You may override the default title for a block.  For example, if instead of Freshman Year you want it to read Pre-Pharmacy Year 1 in FlightPath, ";
			t = t + "then you would enter that in this box and hit save.  To change a title back to the default, just leave it blank.";
		}

		
		if (action == "datetime")
		{
			t = t + "Date/time stamps should be entered in Year-major order: YYYY-MM-DD. Ex:\n";
			t = t + "   2008-01-12 \n";
			t = t + "Entering a time is OPTIONAL.  If you would like to enter a time, please enter it after the date, in this format: \n";
			t = t + "HH:MM:SS  For example:\n  2008-01-12 13:15:00   or even   2008-01-12 01:15pm \n";
			t = t + "Seconds are not required to be entered. \n";
			t = t + "\n   You may type simply   NOW   in the box to make it todays date and time!";
		}

		if (action == "group_title")
		{
			t = t + "The group title is what FlightPath will use to refer to this group in all on-screen ";
			t = t + "text like footnotes and windows. Ex: Free Electives, Upper-Level Electives, Core Humanities, etc.";
		}
		
		if (action == "definition")
		{
			t = t + "Definitions provide a very quick way to add or remove many courses from a group.  For example, to add all CSCI courses, enter:\n";
			t = t + "       add CSCI.*\n";
			t = t + "The . is used to seperate the subject from the course number.  The * means \"any value.\"  You may also use it in the subject.  For example, ";
			t = t + "to add all CSCI and any course with a subject that begins with A, enter:\n";
			t = t + "       add CSCI.*\n       add A*.*\n";
			t = t + "Removing courses is done the same way.  For example, you can add all courses, then remove any course with a number lower than 400:\n";
			t = t + "       add *.*\n       rem *.1*\n       rem *.2*\n       rem *.3*\n";
			t = t + "\n\nIt should be stated that add statements will include courses which have been marked as \"exclude.\"  This is normal.  Those courses will not ";
			t = t + "show up in group selection screens, but they will be added to a group if a student has completed that course in the past.";
		}
		
		if (action == "group_name")
		{
			t = t + "The group name is internal to the data entry system, and is never seen by the user. ";
			t = t + "You may use this to distinguish between two groups which may have the same title. ";
			t = t + "For example, the group major_electives_1 may be different from major_electives_2, but ";
			t = t + "both may have the title of simply Major Electives.\n\n";
			t = t + "This field may be considered optional, but is highly recommended you enter something here ";
			t = t + "for your own clarity later on.\n\n";
			t = t + "It is okay to have the same Group Title and Group Name.";
		}

		if (action == "group_priority")
		{
			t = t + "This number is very important, because it determines the order in which ";
			t = t + "courses are assigned to groups in FlightPath.\n\nHigher-priority groups fill in FIRST.\n\n";
			t = t + "For example, lets say group_1 has a priority of 10 and group_2 has a priority of 50.  If both ";
			t = t + "group_1 and group_2 can accept the course ENGL 101, it will be assigned to group_2, because ";
			t = t + "group_2 has the higher priority.\n\n";
		}

		if (action == "course_names")
		{
			t = t + "These are the possible display names for this course. Typically, there will be only one display name.  For example, ACCT 110. ";
			t = t + "Notice there is a space between the subject ID (ACCT) and the course number (110).  This is very important.\n\n";
			t = t + "If this course is known by another name (ie, it has an equivalent course) you may specify that course\'s name as well using a comma. ";
			t = t + "You may chose to exclude a course name (from course selection screens in FlightPath) by simply adding the word exclude after its name. ";
			t = t + "Just make sure to seperate it with a space from the course number.\n\n";
			t = t + "For example: MATH 373, CSCI 373, MATH 373A exclude, MATH 370 exclude \n";
						
			t = t + "\nIMPORTANT: Course names (including eqvs and exclusions) are instantly updated for ALL YEARS of a course.  So, if you exclude ";
			t = t + "a course in 2008, that same course will be marked as exclude for 2006, 2007, and every other year that it exists.  The same is true ";
			t = t + "for when you enter an eqv (by using a comma) to show that a course has more than one name.";
		}
		
		if (action == "course_title")
		{
			t = t + "This is the title of the course, as seen in popup windows on FlightPath.  For example, Biology II Lab.";
		}

		if (action == "course_min_hours" || action == "course_max_hours")
		{
			t = t + "The minimum hours and maximum hours for a course will usually be the same number, for example: 3.  The numbers ";
			t = t + "differ if the course has a variable numbers of hours, say 1-6 hours.  In this example, you would enter 1 as the min hours, ";
			t = t + "and 6 as the max hours.";
		}

		if (action == "course_repeat_hours")
		{
			t = t + "This is for how many hours a course may be repeated for credit.  For example, if a course description reads that ";
			t = t + "a course is worth 3 hours, and may be repeated for up to 9 hours of credit, then you would enter a 9 in this box.\n\n";
			t = t + "Most courses cannot be repeated for credit.  If a course CANNOT be repeated for credit, this number will be the same ";
			t = t + "as the min hours, or simply blank.  If you are unsure what to enter, either leave it blank or enter a zero.";
		}
		
		if (action == "course_exclude")
		{
			t = t + "This is NOT the same as deleting a course!  Excluding a course means it will be removed from selections in ";
			t = t + "groups for the student, but it will remain part of the system, so that if a student has already taken the course, ";
			t = t + "it will at least appear in their excess credits.\n\n";
			t = t + "Set it to one (1) to exclude, or zero (0) to leave the course as active.  By default, courses are not excluded, and are set to zero (0).";
			
		}

		if (action == "group_properties")
		{
			t = t + "The Hrs means how many hours are required to fulfill this group in this semester or year?  For example, 6.  Must contain a whole number larger than 0.\n\n";
			t = t + "The Min Grade is the default minimum grade any course taken from this group must have in order to fulfill the group. ";
			t = t + "This is different from the minimum grade set per-course within the group entry screen.  This minimum grade value will always override ";
			t = t + "any other minimum grade setting within the group.  Leave blank for no min grade (meaning that any passing grade is acceptable.)\n\n";
			t = t + "The Type setting helps FlightPath classify and attribute hours to one of several categories.  If unsure what to put here, use Elective.";
			
		}
		
		var x = alert(t);
	}
	
	
	function popupWindow(url)
	{
		var my_windowxvvv = window.open(url,
        "courseinfoxvvv","toolbar=no,status=2,scrollbars=yes,resizable=yes,width=600,height=400");

		my_windowxvvv.focus();  // make sure the popup window is on top.
				
	}
	
	function popupWindow2(url)
	{
		my_windowx2vvv = window.open(url,
        "courseinfox2vvv","toolbar=no,status=2,scrollbars=yes,resizable=yes,width=500,height=300");

		my_windowx2vvv.focus();  // make sure the popup window is on top.
				
	}

	function viewAnnouncementPreview(count)
	{
		// Display the announcement in question in a popup window so
		// the admin user can see a preview of what it looks like
		// before saving.
		
		var value = document.getElementById("announcement_" + count).value;
		value = escape(value);
		popupWindow2("admin.php?performAction=previewAnnouncement&announcement=" + value);
		
	}
	
	function popupSelectIcon(file)
	{
		opener.document.getElementById("iconFilename").value = file;
		opener.submitForm();
		window.close();
	}
	
	function deleteGroup(groupID)
	{
		var x = confirm("Are you sure you wish to delete this group? Any degrees which point to it will need to be manually edited and re-saved remove this group requirement.\n\nClick OK to proceed and delete this group.");
		if (!x)
		{
			return;
		}
		
		document.getElementById("performAction2").value="delete_group";
		submitForm();
		
	}
	
	
	function deleteDegree(degreeID)
	{
		var x = confirm("Are you sure you wish to delete this degree? This action cannot be undone.");
		if (!x)
		{
			return;
		}
		
		document.getElementById("performAction2").value="delete_degree";
		submitForm();
		
	}
	
	
	function deleteCourse(courseID, catalogYear, warnEqv)
	{
		var x = confirm("Are you sure you wish to delete this course for the catalog year " + catalogYear + "?  Any degrees or groups which use this course will have to be manually edited and re-saved to remove this course requirement.\n\nClick OK to proceed and delete this course.");
		//alert("Feature not available yet.");
		if (!x)
		{
			return;
		}
		
		if (warnEqv == "yes")
		{
			var x = confirm("It appears this course has equivalencies in place.  If you delete now, it will delete all of the equivalent courses too.  You should remove the eqvs first.  Do you still want to proceed?");
			if (!x)
			{
				return;
			}
		}
		
		document.getElementById("performAction2").value="delete_course";
		submitForm();
		
		
	}
	
	function processDefinitions(catalogYear)
	{
	 var x = confirm("Are you sure you wish to process all group definitions for the year " + catalogYear + "?\n\nAll groups with definitions will be cleared, and their definitions re-run.\n\nNOTICE: This may take more than a minute to complete.\n\nClick OK to proceed.");
	 if (x)
	 {
	   window.location = "admin.php?performAction=performProcessGroupDefinitions&deCatalogYear=" + catalogYear;
	 }
	}
	
	function popupAddGroup(semesterNum)
	{

		var groupID = 0;
		
		var cbs = document.getElementsByName("rgroups");
		for (var t = 0; t < cbs.length; t++)
		{
			var cb = cbs[t];
			if (cb.checked == true)
			{
				// In other words, this group
				// was selected.
				groupID = cb.value;
			}
		}
		
		var hours = document.getElementById("hours").value;
		var type = document.getElementById("type").value;
		var minGrade = document.getElementById("minGrade").value;
		
		if (hours < 1 || groupID < 1)
		{
			alert("Please select a group and number of hours!");
			return;
		}
		
		//alert(groupID + " " + hours + " " + type + " " + minGrade);
		opener.document.getElementById("performAction2").value="addGroup_" + groupID + "_" + semesterNum + "_" + hours + "_" + type + "_" + minGrade;
		opener.submitForm();
		window.close();
				
	}
	
	
	function popupSaveDefinition()
	{
		var x = confirm("Are you sure you wish to save this definition?  Doing this will overwrite whatever may already be in the Required Courses box.\n\nClick OK to proceed.");
		if (!x)
		{
			return;
		}
		
		var def = encodeURI(document.getElementById("definition").value);
		opener.document.getElementById("setDefinition").value = def;
		opener.showUpdate();
		opener.submitForm();
		window.close();
		
	}
	
	function submitForm()
	{
		document.getElementById("scrollTop").value = document.body.scrollTop;
		document.getElementById("mainform").submit();
	}	
	
	
	function delGroup(groupID, semesterNum)
	{
		var dsn = Number(semesterNum) + 1;
		var x = confirm("Are you sure you want to delete this group from block " + dsn + "?");
		if (!x)
		{
			return;
		}
		
		document.getElementById("performAction2").value="delGroup_" + groupID + "_" + semesterNum;
		submitForm();
		
		
	}
	
	function confirmClearJDHistory()
	{
	 var x = confirm("Are you sure you wish to clear the advising and comment history for John Doe (student 99999999)?");
	 if (x) 
	 {
	   window.location = "admin.php?performAction=performClearJohnDoe";
	 }
	}
	
	</script>
			';
  return $rtn;
}


?>