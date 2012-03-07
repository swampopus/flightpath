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
header("Cache-control: private");

require_once("bootstrap.inc");

if ($_SESSION["fpLoggedIn"] != true)
{ // If not logged in, show the login screen.
	header("Location: main.php");
	die;
}

/////////////////////////////////////
///  Are we trying to save the draft
///  from a tab change?
/////////////////////////////////////
$fp = new FlightPath();
$fp->processRequestSaveDraft();



$screen = new AdvisingScreen();
$screen->initAdvisingVariables(true);
$screen->screenMode = "notAdvising";

$db = new DatabaseHandler();
$performAction = trim($_REQUEST["performAction"]);

if ($performAction == "" || $performAction == "newComment")
{
	displayMainPage($performAction);
}

if ($performAction == "saveComment")
{
	performSaveComment();
}

if ($performAction == "deleteComment")
{
	performDeleteComment();
}

if ($performAction == "displayComment")
{
	displayComment();
}

die;

function displayComment()
{
  
  // Check for hooks...
  if (function_exists("comments_displayComment")) {
    return call_user_func("comments_displayComment");
  }
  
	global $screen, $db;
	// This method will display a particular comment (or range
	// of comments), presumably in a popup window for the user
	// to print.

	// We can either view an entire semester or one comment at
	// a time.
	$pC = "";
	$studentID = $GLOBALS["advisingStudentID"];
	$id = addslashes($_REQUEST["id"]);
	$category = addslashes($_REQUEST["category"]);

	$pC .= "<div style='font-size: 14pt; font-weight: bold;'>
			<!--category--> Comment <!--c_history--> for " . $db->getStudentName($studentID) . " ($studentID) ";
	if ($category != "")
	{
		//$pC = str_replace("<!--c_history-->"," History ", $pC);
		//$pC = str_replace("<!--category-->",ucwords($category), $pC);
	}
	$pC .= "</div>";

	$accessLine = "";
	
	if ($_SESSION["fpUserType"] == "student")
	{
	  $accessLine = "and `access_type` = 'public' ";
	}
	
	$query = "";
	if ($id != "")
	{
		$query = "SELECT * FROM advising_comments
						WHERE `id`='$id' 
						AND `student_id`='$studentID'						
						AND `delete_flag`='0'
						$accessLine
						ORDER BY `term_id` DESC, `datetime` DESC";
	} else {
		// Print all comments.
		$query = "SELECT * FROM advising_comments
						WHERE `student_id`='$studentID'
						AND `delete_flag`='0'
						$accessLine
						ORDER BY `datetime` DESC";
	}

	$res = $db->dbQuery($query);
	while ($cur = $db->dbFetchArray($res))
	{
		extract($cur, 3, "db");
		$pC .= showComment("",$db_access_type, $db_faculty_id, $db_datetime, $db_comment, $db_category);
	}



	$screen->boolPrint = true;
	$screen->pageContent = $pC;
	// send to the browser
	$screen->outputToBrowser();


}


function performDeleteComment()
{
  // Check for hooks...
  if (function_exists("comments_performDeleteComment")) {
    return call_user_func("comments_performDeleteComment");
  }
  
	global $db;

	if ($_SESSION["fpCanModifyComments"] != true)	{
		die("Your user type does not have the ability to perform this function.");
	}
	
	$id = addslashes($_REQUEST["id"]);
	// Check to make sure that this particular comment may be deleted.	
	if (!in_array($id, $_SESSION["fpCommentsMayDelete"])) {
	  die("Sorry, that comment cannot be deleted at this time.");
	}

	
	
	$res = $db->dbQuery("UPDATE advising_comments
							SET `delete_flag`='1'
							WHERE `id`='$id' ");

	$db->addToLog("delete_comment", "commentID:$id");

	displayMainPage("","<div class='tenpt' style='color: green;'>Comment deleted successfully.</div>");

}


function performSaveComment()
{
  
  // Check for hooks...
  if (function_exists("comments_performSaveComment")) {
    return call_user_func("comments_performSaveComment");
  }
  
	global $db;

	if ($_SESSION["fpCanModifyComments"] != true)
	{
		die("Your user type does not have the ability to perform this function.");
	}


	$studentID = $GLOBALS["advisingStudentID"];
	//$termID = $GLOBALS["advisingTermID"];
	$facultyID = $_SESSION["fpUserID"];
	$termID = trim($_POST["termID"]);

	// Simply save the new comment which the user has entered.
	
	$accessType = $_POST["type"];
	$comment = trim(mysql_real_escape_string($_POST["comment"]));
	if ($comment == "")
	{
		displayMainPage();
		die;
	}

	$res = $db->dbQuery("INSERT INTO advising_comments
							(`student_id`,`faculty_id`,`term_id`,
								`comment`,`datetime`,`access_type`)
								VALUES
							('$studentID','$facultyID','$termID',
							'$comment',NOW(),'$accessType') ");

	$db->addToLog("save_comment", "$studentID");

	displayMainPage();

}



function drawViewSelector($selectedView, $boolWarnChange = false)
{
	$rtn = "";

	$rtn .= "<div class='tenpt'>Show comments: &nbsp; &nbsp;";

	$jsBool = "false";
	if ($boolWarnChange)
	{
		$jsBool = "true";
	}
	
	$lArray = array("all"=>"All~comments.php?view=all",
	"advising"=>"Advising~comments.php?view=advising",
	"administration"=>"Administration~comments.php?view=administration");
	foreach($lArray as $key => $value)
	{
		$temp = split("~",$value);
		$title = trim($temp[0]);
		if ($key == $selectedView)
		{
			$title = "<b>$title</b>";
		}
		$action = trim($temp[1]);
		$rtn .= "<a href='javascript: changeCommentView(\"$action\",$jsBool);' class='nounderline'>$title</a> &nbsp; &nbsp; &nbsp;";
	}
	$rtn .= "</div>";

	return $rtn;
}




function displayMainPage($performAction = "", $msg = "")
{
  
  // Check for hooks...
  if (function_exists("comments_displayMainPage")) {
    return call_user_func("comments_displayMainPage", $performAction, $msg);
  }
  
  
	global $screen, $db;
	$studentID = $GLOBALS["advisingStudentID"];
	$termID = $GLOBALS["advisingTermID"];
	$availableTerms = $GLOBALS["settingAvailableAdvisingTermIDs"];
	$csid = $GLOBALS["currentStudentID"];
	$selectedView = $_REQUEST["view"];
	
	if ($selectedView == "")
	{
		$selectedView = "all";
	}
	
	$pC = "";

	$pC .= getJS_functions();
	$pC .= "<form id='mainform' method='POST' style='margin: 0px; padding:0px;'>
			<input type='hidden' id='scrollTop'>
			<input type='hidden' id='performAction' name='performAction'>
			<input type='hidden' id='advisingWhatIf' name='advisingWhatIf'>
			<input type='hidden' id='currentStudentID' name='currentStudentID' value='$csid'>
			</form>";
	//$pC .= $screen->getJavascriptCode();

	$pC .= $screen->displayGreeting();
	$pC .= "<!--VIEWSELECT-->"; 
	$pC .= $screen->displayBeginSemesterTable();
	$pC .= $screen->drawCurrentlyAdvisingBox(true);

	$boolWarnChangeTab = false;
	$pC .= "<tr><td colspan='2' width='100%' valign='top'  style='padding-right: 10px;'>";

	if ($_SESSION["fpCanModifyComments"] == true)
	{
		if ($performAction != "newComment")
		{
			$pC .= "<a href='comments.php?performAction=newComment&currentStudentID=$csid&view=$selectedView'>Enter a new comment</a>";
			$pC .= "$msg";
		} else {

			
			
			$pC .= "<form action='comments.php' method='POST' id='cform'>
				<div class='tenpt'>
			<b>Enter your comment below:</b><br>
			
			<div class='tenpt' >
			Visible to:
			<input type='radio' name='type' value='public'>Anyone (incl. students) &nbsp; &nbsp; &nbsp;
			<input type='radio' name='type' value='faculty' checked>Faculty/Staff only
			
						
			</div>
			
			";


			
			$pC .= "
		<div style='float: clear;'>&nbsp;</div>
			 <textarea rows='7' cols='75' name='comment'></textarea>
			 <div style='font-size: 8pt;'><b>Trouble with Copy/Paste?</b> Use keyboard shortcuts CTRL-C and CTRL-V.</div>
			 <div style='text-align:right; padding-right: 20px;'>
			 	" . $screen->drawButton("Cancel","window.location=\"comments.php?currentStudentID=$csid\";") . " &nbsp; &nbsp; &nbsp; &nbsp;
			 	" . $screen->drawButton("Save","document.getElementById(\"cform\").submit();'") . "
			 	<!--
			 	<input type='button' value='Cancel' onClick='window.location=\"comments.php\";'> &nbsp; &nbsp; &nbsp; &nbsp;
			 	<input type='submit' value='Save'>
			 	-->
			 	<input type='hidden' name='performAction' value='saveComment'>
			 	<input type='hidden' name='currentStudentID' id='currentStudentID' value='$csid'>
			 </div>
			</div>
				</form>";
			$boolWarnChangeTab = true;  // warn the user if they try to change
			// the tab from here.
		}
	}

	
	
	// Show existing/old comments:
	$oldTermID = "";

	$accessLine = $catLine = "";
	if ($_SESSION["fpUserType"] == "student")
	{ // May not be necessary, since students don't see this tab anyway...
		$accessLine = "and `access_type`='public' ";
	}
	if ($selectedView != "all")
	{
		$catLine = " and `category`='$selectedView' ";
	}

	$res = $db->dbQuery("SELECT * FROM advising_comments
						WHERE `student_id`='$studentID' 
						AND `delete_flag`='0'
						$accessLine
						$catLine
						ORDER BY `datetime` DESC ");
	while ($cur = $db->dbFetchArray($res))
	{
		extract($cur, 3, "db");

		$deleteLink = "";
		if ($db_faculty_id == $_SESSION["fpUserID"]
		&& $_SESSION["fpCanModifyComments"])
		{ // You may delete your own comments...
			// Only show the delete link if the comment was made within the past
			// 3 months.
			$nowdt = date("Y-m-d", strtotime("now"));
			$now = strtotime("now");
			$delRange = strtotime("$nowdt -3 month");

			$then = strtotime($db_datetime);

			if ($then > $delRange)
			{
				$deleteLink = "<a href='javascript: deleteComment(\"$db_id\");'>delete?</a>";
        // Let's keep track of the fact that we can delete this one.  We will
        // check that later if the user tries to delete a comment.
				if (!is_array($_SESSION["fpCommentsMayDelete"])) {
				  $_SESSION["fpCommentsMayDelete"] = array();
				}
				$_SESSION["fpCommentsMayDelete"][] = $db_id;
			}
		}
		$pC .= showComment($deleteLink, $db_access_type, $db_faculty_id, $db_datetime, $db_comment, $db_category);

	}

	$pC .= "</td></tr>";

	$pC .= $screen->displayEndSemesterTable();
	


	/*	$pageTabs = $screen->drawSystemTabs(3);
	$pageHasSearch = true;
	$pageContent = $pC;
	include("template/fp_template.php");
	*/
	$screen->pageContent = $pC;
	$screen->pageHasSearch = true;
	$screen->buildSystemTabs(3, false, false,$boolWarnChangeTab);
	// send to the browser
	$screen->outputToBrowser();



}


function showComment($deleteLink, $accessType, $facultyID, $datetime, $comment, $category = "")
{

  // Check for hooks...
  if (function_exists("comments_showComment")) {
    return call_user_func("comments_showComment", $deleteLink, $accessType, $facultyID, $datetime, $comment, $category);
  }

  global $db;
  
	$comment = one_wordwrap($comment, 100);
	$accessType = ucwords($accessType);
	if ($accessType == "Public")
	{
		$accessType = "<span class='hypo'>$accessType</span>";
	}
	
	
	$pC = "<div style='margin: 10px; width='600px;'>
					<div class='tenpt' style='float:left;'>
					$accessType 
						comment by " . $db->getFacultyName($facultyID, false) . ":
					</div>
					<div class='tenpt' style='text-align: right; float: clear;'>
						$datetime $deleteLink
					</div>
					<div style='border: 1px solid black; padding: 10px;
								background-color: #F9F9F9; 
								margin-bottom: 20px;'>
					$comment
					</div>
				</div>
				";
	return $pC;
}


/*
 This function will break up
 one long word.  Like if someone writes "................................."
 for a long time.  This will keep it from stretching out the page.
*/
function one_wordwrap($string,$width)
{
	$s = explode(" ", $string);
	foreach ($s as $k => $v)
	{
		$cnt = strlen($v);
		if ($cnt > $width) 
		{
			$v = wordwrap($v, $width, "\n", true);
		}
		$new_string .= "$v ";
	}
	return $new_string;
}


function getJS_functions()
{
    // Check for hooks...
  if (function_exists("comments_getJS_functions")) {
    return call_user_func("comments_getJS_functions");
  }
  
	$csid = $GLOBALS["currentStudentID"];
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
                                           "justifyleft,justifycenter,separator",
         theme_advanced_buttons1_add     : "cut,copy,paste,separator,bullist,numlist",
         theme_advanced_buttons2         : "",
         theme_advanced_buttons3         : "",
         theme_advanced_toolbar_location : "top",
         theme_advanced_toolbar_align    : "left",
         extended_valid_elements         : "hr[class|width|size|noshade]," +
                                           "font[face|size|color|style]," +
                                           "span[class|align|style]"
      });
	
    var csid = "' . $csid . '"; 
   function myHandleEvent(e)
   {
       return true; // Continue handling
   }
      

   function deleteComment(id)
   {
   		var x = confirm("Are you sure you wish to delete this comment?");
   		if (x)
   		{
   			window.location="comments.php?performAction=deleteComment&id=" + id + "&currentStudentID=" + csid;
   		}
   }
   
   function changeCommentView(url, warnTab)
   {
   	if (warnTab)
   	{
   		var x = confirm("Are you sure you wish to change your view of the comments tab? Any unsaved work will be lost.\n\nClick OK to change views, click Cancel to stay on this page.");
   		if (!x)
   		{
   			return;
   		}
   	}
   	
   	window.location = "" + url;
   }
   
		function popupHelpWindow(url)
		{
			var my_windowxhelp2p = window.open(url + "&currentStudentID=" + csid,
			"courseinfoxhelp" + csid,"toolbar=no,status=2,scrollbars=yes,resizable=yes,width=700,height=500");

			my_windowxhelp2p.focus();  // make sure the popup window is on top.

		}   
   
   
   ';

	$tempScreen = new AdvisingScreen();
	$rtn .= $tempScreen->getJS_submitForm();
	$rtn .= $tempScreen->getJS_changeTab();

	$rtn .= '
      </script>
			';

	return $rtn;
}

?>