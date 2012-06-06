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

if ($_SESSION["fp_logged_in"] != true)
{ // If not logged in, show the login screen.
	header("Location: main.php");
	die;
}

/////////////////////////////////////
///  Are we trying to save the draft
///  from a tab change?
/////////////////////////////////////
$fp = new FlightPath();
$fp->process_request_save_draft();



$screen = new AdvisingScreen();
$screen->init_advising_variables(true);
$screen->screen_mode = "not_advising";

$db = new DatabaseHandler();
$perform_action = trim($_REQUEST["perform_action"]);

if ($perform_action == "" || $perform_action == "new_comment")
{
	display_main_page($perform_action);
}

if ($perform_action == "save_comment")
{
	perform_save_comment();
}

if ($perform_action == "delete_comment")
{
	perform_delete_comment();
}

if ($perform_action == "display_comment")
{
	display_comment();
}

die;

function display_comment()
{
  
  // Check for hooks...
  if (function_exists("comments_display_comment")) {
    return call_user_func("comments_display_comment");
  }
  
	global $screen, $db;
	// This method will display a particular comment (or range
	// of comments), presumably in a popup window for the user
	// to print.

	// We can either view an entire semester or one comment at
	// a time.
	$pC = "";
	$student_id = $GLOBALS["advising_student_id"];
	$id = addslashes($_REQUEST["id"]);
	$category = addslashes($_REQUEST["category"]);

	$pC .= "<div style='font-size: 14pt; font-weight: bold;'>
			<!--category--> Comment <!--c_history--> for " . $db->get_student_name($student_id) . " ($student_id) ";
	if ($category != "")
	{
		//$pC = str_replace("<!--c_history-->"," History ", $pC);
		//$pC = str_replace("<!--category-->",ucwords($category), $pC);
	}
	$pC .= "</div>";

	$access_line = "";
	
	if ($_SESSION["fp_user_type"] == "student")
	{
	  $access_line = "and `access_type` = 'public' ";
	}
	
	$query = "";
	if ($id != "")
	{
		$query = "SELECT * FROM advising_comments
						WHERE `id`='$id' 
						AND `student_id`='$student_id'						
						AND `delete_flag`='0'
						$access_line
						ORDER BY `term_id` DESC, `datetime` DESC";
	} else {
		// Print all comments.
		$query = "SELECT * FROM advising_comments
						WHERE `student_id`='$student_id'
						AND `delete_flag`='0'
						$access_line
						ORDER BY `datetime` DESC";
	}

	$res = $db->db_query($query);
	while ($cur = $db->db_fetch_array($res))
	{
		extract($cur, 3, "db");
		$pC .= show_comment("",$db_access_type, $db_faculty_id, $db_datetime, $db_comment, $db_category);
	}



	$screen->bool_print = true;
	$screen->page_content = $pC;
	// send to the browser
	$screen->output_to_browser();


}


function perform_delete_comment()
{
  // Check for hooks...
  if (function_exists("comments_perform_delete_comment")) {
    return call_user_func("comments_perform_delete_comment");
  }
  
	global $db;

	if ($_SESSION["fp_can_modify_comments"] != true)	{
		die("Your user type does not have the ability to perform this function.");
	}
	
	$id = addslashes($_REQUEST["id"]);
	// Check to make sure that this particular comment may be deleted.	
	if (!in_array($id, $_SESSION["fp_comments_may_delete"])) {
	  die("Sorry, that comment cannot be deleted at this time.");
	}

	
	
	$res = $db->db_query("UPDATE advising_comments
							SET `delete_flag`='1'
							WHERE `id`='$id' ");

	$db->add_to_log("delete_comment", "comment_id:$id");

	display_main_page("","<div class='tenpt' style='color: green;'>Comment deleted successfully.</div>");

}


function perform_save_comment()
{
  
  // Check for hooks...
  if (function_exists("comments_perform_save_comment")) {
    return call_user_func("comments_perform_save_comment");
  }
  
	global $db;

	if ($_SESSION["fp_can_modify_comments"] != true)
	{
		die("Your user type does not have the ability to perform this function.");
	}


	$student_id = $GLOBALS["advising_student_id"];
	//$term_id = $GLOBALS["advising_term_id"];
	$faculty_id = $_SESSION["fp_user_id"];
	$term_id = trim($_POST["term_id"]);

	// Simply save the new comment which the user has entered.
	
	$access_type = $_POST["type"];
	$comment = trim(mysql_real_escape_string($_POST["comment"]));
	if ($comment == "")
	{
		display_main_page();
		die;
	}

	$res = $db->db_query("INSERT INTO advising_comments
							(`student_id`,`faculty_id`,`term_id`,
								`comment`,`datetime`,`access_type`)
								VALUES
							('$student_id','$faculty_id','$term_id',
							'$comment',NOW(),'$access_type') ");

	$db->add_to_log("save_comment", "$student_id");

	display_main_page();

}



function draw_view_selector($selected_view, $bool_warn_change = false)
{
	$rtn = "";

	$rtn .= "<div class='tenpt'>Show comments: &nbsp; &nbsp;";

	$js_bool = "false";
	if ($bool_warn_change)
	{
		$js_bool = "true";
	}
	
	$l_array = array("all"=>"_all~comments.php?view=all",
	"advising"=>"_advising~comments.php?view=advising",
	"administration"=>"_administration~comments.php?view=administration");
	foreach($l_array as $key => $value)
	{
		$temp = split("~",$value);
		$title = trim($temp[0]);
		if ($key == $selected_view)
		{
			$title = "<b>$title</b>";
		}
		$action = trim($temp[1]);
		$rtn .= "<a href='javascript: changeCommentView(\"$action\",$js_bool);' class='nounderline'>$title</a> &nbsp; &nbsp; &nbsp;";
	}
	$rtn .= "</div>";

	return $rtn;
}




function display_main_page($perform_action = "", $msg = "")
{
  
  // Check for hooks...
  if (function_exists("comments_display_main_page")) {
    return call_user_func("comments_display_main_page", $perform_action, $msg);
  }
  
  
	global $screen, $db;
	$student_id = $GLOBALS["advising_student_id"];
	$term_id = $GLOBALS["advising_term_id"];
	$available_terms = $GLOBALS["setting_available_advising_term_ids"];
	$csid = $GLOBALS["current_student_id"];
	$selected_view = $_REQUEST["view"];
	
	if ($selected_view == "")
	{
		$selected_view = "all";
	}
	
	$pC = "";

	$pC .= get_j_s_functions();
	$pC .= "<form id='mainform' method='POST' style='margin: 0px; padding:0px;'>
			<input type='hidden' id='scrollTop'>
			<input type='hidden' id='performAction' name='performAction'>
			<input type='hidden' id='advisingWhatIf' name='advisingWhatIf'>
			<input type='hidden' id='currentStudentID' name='currentStudentID' value='$csid'>
			</form>";
	//$pC .= $screen->getJavascriptCode();

	$pC .= $screen->display_greeting();
	$pC .= "<!--VIEWSELECT-->"; 
	$pC .= $screen->display_begin_semester_table();
	$pC .= $screen->draw_currently_advising_box(true);

	$bool_warn_change_tab = false;
	$pC .= "<tr><td colspan='2' width='100%' valign='top'  style='padding-right: 10px;'>";

	if ($_SESSION["fp_can_modify_comments"] == true)
	{
		if ($perform_action != "new_comment")
		{
			$pC .= "<a href='comments.php?performAction=newComment&currentStudentID=$csid&view=$selected_view'>Enter a new comment</a>";
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
			 	" . $screen->draw_button("_cancel","window.location=\"comments.php?current_student_id=$csid\";") . " &nbsp; &nbsp; &nbsp; &nbsp;
			 	" . $screen->draw_button("_save","document.get_element_by_id(\"cform\").submit();'") . "
			 	<!--
			 	<input type='button' value='Cancel' onClick='window.location=\"comments.php\";'> &nbsp; &nbsp; &nbsp; &nbsp;
			 	<input type='submit' value='Save'>
			 	-->
			 	<input type='hidden' name='performAction' value='saveComment'>
			 	<input type='hidden' name='currentStudentID' id='currentStudentID' value='$csid'>
			 </div>
			</div>
				</form>";
			$bool_warn_change_tab = true;  // warn the user if they try to change
			// the tab from here.
		}
	}

	
	
	// Show existing/old comments:
	$old_term_id = "";

	$access_line = $cat_line = "";
	if ($_SESSION["fp_user_type"] == "student")
	{ // May not be necessary, since students don't see this tab anyway...
		$access_line = "and `access_type`='public' ";
	}
	if ($selected_view != "all")
	{
		$cat_line = " and `category`='$selected_view' ";
	}

	$res = $db->db_query("SELECT * FROM advising_comments
						WHERE `student_id`='$student_id' 
						AND `delete_flag`='0'
						$access_line
						$cat_line
						ORDER BY `datetime` DESC ");
	while ($cur = $db->db_fetch_array($res))
	{
		extract($cur, 3, "db");

		$delete_link = "";
		if ($db_faculty_id == $_SESSION["fp_user_id"]
		&& $_SESSION["fp_can_modify_comments"])
		{ // You may delete your own comments...
			// Only show the delete link if the comment was made within the past
			// 3 months.
			$nowdt = date("_y-m-d", strtotime("now"));
			$now = strtotime("now");
			$del_range = strtotime("$nowdt -3 month");

			$then = strtotime($db_datetime);

			if ($then > $del_range)
			{
				$delete_link = "<a href='javascript: deleteComment(\"$db_id\");'>delete?</a>";
        // Let's keep track of the fact that we can delete this one.  We will
        // check that later if the user tries to delete a comment.
				if (!is_array($_SESSION["fp_comments_may_delete"])) {
				  $_SESSION["fp_comments_may_delete"] = array();
				}
				$_SESSION["fp_comments_may_delete"][] = $db_id;
			}
		}
		$pC .= show_comment($delete_link, $db_access_type, $db_faculty_id, $db_datetime, $db_comment, $db_category);

	}

	$pC .= "</td></tr>";

	$pC .= $screen->display_end_semester_table();
	


	/*	$pageTabs = $screen->drawSystemTabs(3);
	$page_has_search = true;
	$page_content = $pC;
	include("template/fp_template.php");
	*/
	$screen->page_content = $pC;
	$screen->page_has_search = true;
	$screen->build_system_tabs(3, false, false,$bool_warn_change_tab);
	// send to the browser
	$screen->output_to_browser();



}


function show_comment($delete_link, $access_type, $faculty_id, $datetime, $comment, $category = "")
{

  // Check for hooks...
  if (function_exists("comments_show_comment")) {
    return call_user_func("comments_show_comment", $delete_link, $access_type, $faculty_id, $datetime, $comment, $category);
  }

  global $db;
  
	$comment = one_wordwrap($comment, 100);
	$access_type = ucwords($access_type);
	if ($access_type == "_public")
	{
		$access_type = "<span class='hypo'>$access_type</span>";
	}
	
	
	$pC = "<div style='margin: 10px; width='600px;'>
					<div class='tenpt' style='float:left;'>
					$access_type 
						comment by " . $db->get_faculty_name($faculty_id, false) . ":
					</div>
					<div class='tenpt' style='text-align: right; float: clear;'>
						$datetime $delete_link
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


function get_j_s_functions()
{
    // Check for hooks...
  if (function_exists("comments_get_j_s_functions")) {
    return call_user_func("comments_get_j_s_functions");
  }
  
	$csid = $GLOBALS["current_student_id"];
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

	$temp_screen = new AdvisingScreen();
	$rtn .= $temp_screen->get_j_s_submit_form();
	$rtn .= $temp_screen->get_j_s_change_tab();

	$rtn .= '
      </script>
			';

	return $rtn;
}

?>