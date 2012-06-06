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

if ($_SESSION["fp_logged_in"] != true && $_REQUEST["blank_degree_id"] == "")
{ // If not logged in, show the login screen.
  header("Location: main.php");
  die;
}

$bool_looged_in = true;
// We do this, because we might be allowed in if we are only
// looking at blank degree plans.
if ($_SESSION["fp_logged_in"] != true)
{$bool_logged_in = false;}


// Not essential.  This will help me keep track of
// details later, possibly....
$GLOBALS["admin_notes_array"] = array();

$window_mode = trim(addslashes($_REQUEST["window_mode"]));


/////////////////////////////////////
///  Are we trying to save the draft
///  from a tab change?
/////////////////////////////////////
$fp = new FlightPath();
$fp->process_request_save_draft();




if ($_REQUEST["clear_session"] == "yes")
{
  $temp_screen = new AdvisingScreen();
  $temp_screen->clear_variables();
}

$degree_plan = $student = $fp = "";

if ($window_mode == "history" || $_REQUEST["perform_action"] == "history")
{
  display_advisee_history();
}

if ($window_mode == "popup")
{
  display_popup();
}

if ($window_mode == "" || $window_mode == "screen")
{
  display_screen();
}

if ($window_mode == "summary")
{
  display_popup_advising_summary();
}


die;



function display_popup_advising_summary()
{
  // Check for hooks...
  if (function_exists("advise_display_popup_advising_summary")) {
    return call_user_func("advise_display_popup_advising_summary");
  }
  
  
  $advising_session_id = trim(addslashes($_GET["advising_session_id"]));

  $screen = new AdvisingScreen("",null,"not_advising");
  $screen->bool_print = true;
  $db = new DatabaseHandler();

  $res = $db->db_query("SELECT * FROM advising_sessions
							       WHERE `advising_session_id`='$advising_session_id' ");
  if ($db->db_num_rows($res) > 0)
  {
    $cur = $db->db_fetch_array($res);
    extract($cur, 3, "db");
  }
  $dt = date("F jS, Y, g:ia",strtotime($db_datetime));


  $db = new DatabaseHandler();
  $temp_course = new Course();
  $temp_course->term_id = $db_term_id;
  $term = $temp_course->get_term_description();
  $degree_plan = new DegreePlan();
  $degree_plan->degree_id = $db_degree_id;
  $degree_plan->load_descriptive_data();
  $degree_title = $degree_plan->get_title(true);

  $student = new Student($db_student_id, $db);

  $what_if_message = "";
  if ($db_is_whatif == "1")
  {
    $official_degree_plan = $student->get_degree_plan(false, true);
    $official_degree_plan->load_descriptive_data();
    $official_degree_title = $official_degree_plan->get_title(true);

    $what_if_message = "<b>Note:</b>
							This advising was saved using <b>What If</b> mode
							for the $degree_title major.  According to {$GLOBALS["fp_system_settings"]["school_initials"]} records,
							the student's official major is <u>$official_degree_title</u>.
							";
  }


  $w = ($screen->page_is_mobile) ? "100%" : "500";
  
  $pC = "";
  $pC .= "<table width='$w'><td valign='top'>";
  
  if ($screen->page_is_mobile) {

  }
  else {
  
    $pC .= "
			<table class='tenpt' border='0' width='100%' cellpadding='3' cellspacing='0' style='border-width: 2px; border-style: solid; border-color: black;'>
			 <tr height='7'>
			 	<td> </td>
			 </tr>
			 <tr>
			  <td valign='top' width='15%'>Student:</td>
			  <td valign='top' width='40%'>" . $db->get_student_name($db_student_id, false) . " ($db_student_id)</td>
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
			  <td valign='top'>" . $db->get_faculty_name($db_faculty_id, false) . "</td>

			 </tr>
			 <tr>
			  <td valign='top'>Term:</td>
			  <td valign='top'>$term</td>

			 </tr>
			 ";
  if (!$screen->page_is_mobile) {
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

  if ($degree_title != "")
  {
    $pC .= "Major: $degree_title";
  }

  $pC .= "
			 </tr>
			 </table>
			 <div class='tenpt'><i>Submitted on $dt.</i></div>
			 <div class='tenpt'>$what_if_message</div>
			 <br>
		";

  $pC .= $screen->draw_curved_title("Advised Courses");

  $fp = new FlightPath($student,$degree_plan, $db);

  $fp->load_advising_session_from_database("","",false,false,$advising_session_id);

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
  $advised_courses_list = $fp->course_list_advised_courses;
  $advised_courses_list->load_course_descriptive_data();
  $advised_courses_list->sort_alphabetical_order();
  $advised_courses_list->reset_counter();
  while ($advised_courses_list->has_more())
  {
    $course = $advised_courses_list->get_next();
    // set the catalogYear from the term_id.
    $course->term_id = $db_term_id;
    $course->set_catalog_year_from_term_id();
    $course->load_descriptive_data(false);

    $pC .= "<tr>
					<td class='tenpt' valign='top'>
					$course->subject_id $course->course_num
					</td>
					<td class='tenpt' valign='top'>
					$course->title
					</td>
					<td class='tenpt' valign='top' align='center'>
					" . $course->get_hours() . " 
					</td>
					
				</tr>
			";
  }

  $pC .= "</table>
			<div align='right' class='tenpt' style='padding-top: 10px; padding-right: 15px;'>
			  <b>Total advised hours: &nbsp; " . $advised_courses_list->count_hours() . "</b>
			</div>
			";


  if (!$screen->page_is_mobile) {
    $pC .= "<br>";
    $pC .= $screen->draw_curved_title("Alternate Courses");
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


  $screen->page_title = $db->get_student_name($db_student_id, false) . " ($db_student_id) $term Advising Summary";
  $screen->page_content = $pC;
  $screen->output_to_browser();



}


function display_popup_toolbox($screen, $perform_action2)
{
  
  // Check for hooks...
  if (function_exists("advise_display_popup_toolbox")) {
    return call_user_func("advise_display_popup_toolbox", $screen, $perform_action2);
  }
  
  
  global $degree_plan, $student, $fp;

  $page_content = "";

  if ($_SESSION["fp_can_substitute"] != true)
  {
    die("Your user type is not allowed to access this function.");
  }

  $page_content .= $screen->get_javascript_code();
  $csid = $GLOBALS["current_student_id"];

  if ($perform_action2 == "substitutions")
  {
    // Display the substitution management screen.
    $page_content .= $screen->display_toolbox_substitutions();


    // Create the tabs for the page...
    $tab_array = array();
    $tab_array[0]["title"] = "_transfers";
    $tab_array[0]["on_click"] = 'window.location="advise.php?window_mode=popup&perform_action=toolbox&perform_action2=transfers&current_student_id=' . $csid . '";';

    $tab_array[1]["title"] = "_substitutions";
    $tab_array[1]["on_click"] = 'window.location="advise.php?window_mode=popup&perform_action=toolbox&perform_action2=substitutions&current_student_id=' . $csid . '";';
    $tab_array[1]["active"] = true;

    $tab_array[2]["title"] = "_moved";
    $tab_array[2]["on_click"] = 'window.location="advise.php?window_mode=popup&perform_action=toolbox&perform_action2=moved&current_student_id=' . $csid . '";';

    $tab_array[3]["title"] = "_courses";
    $tab_array[3]["on_click"] = 'window.location="advise.php?window_mode=popup&perform_action=toolbox&perform_action2=courses&order=name&current_student_id=' . $csid . '";';


    $screen->page_tabs = $screen->draw_tabs($tab_array);
  }

  if ($perform_action2 == "courses")
  {
    $page_content .= $screen->display_toolbox_courses();

    // Create the tabs for the page...
    $tab_array = array();
    $tab_array[0]["title"] = "_transfers";
    $tab_array[0]["on_click"] = 'window.location="advise.php?window_mode=popup&perform_action=toolbox&perform_action2=transfers&current_student_id=' . $csid . '";';

    $tab_array[1]["title"] = "_substitutions";
    $tab_array[1]["on_click"] = 'window.location="advise.php?window_mode=popup&perform_action=toolbox&perform_action2=substitutions&current_student_id=' . $csid . '";';

    $tab_array[2]["title"] = "_moved";
    $tab_array[2]["on_click"] = 'window.location="advise.php?window_mode=popup&perform_action=toolbox&perform_action2=moved&current_student_id=' . $csid . '";';

    $tab_array[3]["title"] = "_courses";
    $tab_array[3]["on_click"] = 'window.location="advise.php?window_mode=popup&perform_action=toolbox&perform_action2=courses&order=name&current_student_id=' . $csid . '";';
    $tab_array[3]["active"] = true;

    $screen->page_tabs = $screen->draw_tabs($tab_array);

  }



  if ($perform_action2 == "" || $perform_action2 == "transfers")
  {
    // Display the transfer eqv management system.
    $page_content .= $screen->display_toolbox_transfers();

    // Create the tabs for the page...
    $tab_array = array();
    $tab_array[0]["title"] = "_transfers";
    $tab_array[0]["on_click"] = 'window.location="advise.php?window_mode=popup&perform_action=toolbox&perform_action2=transfers&current_student_id=' . $csid . '";';
    $tab_array[0]["active"] = true;

    $tab_array[1]["title"] = "_substitutions";
    $tab_array[1]["on_click"] = 'window.location="advise.php?window_mode=popup&perform_action=toolbox&perform_action2=substitutions&current_student_id=' . $csid . '";';

    $tab_array[2]["title"] = "_moved";
    $tab_array[2]["on_click"] = 'window.location="advise.php?window_mode=popup&perform_action=toolbox&perform_action2=moved&current_student_id=' . $csid . '";';

    $tab_array[3]["title"] = "_courses";
    $tab_array[3]["on_click"] = 'window.location="advise.php?window_mode=popup&perform_action=toolbox&perform_action2=courses&order=name&current_student_id=' . $csid . '";';


    $screen->page_tabs = $screen->draw_tabs($tab_array);

  }

  if ($perform_action2 == "moved")
  {
    // Display the moved courses management system.
    $page_content .= $screen->display_toolbox_moved();

    // Create the tabs for the page...
    $tab_array = array();
    $tab_array[0]["title"] = "_transfers";
    $tab_array[0]["on_click"] = 'window.location="advise.php?window_mode=popup&perform_action=toolbox&perform_action2=transfers&current_student_id=' . $csid . '";';

    $tab_array[1]["title"] = "_substitutions";
    $tab_array[1]["on_click"] = 'window.location="advise.php?window_mode=popup&perform_action=toolbox&perform_action2=substitutions&current_student_id=' . $csid . '";';

    $tab_array[2]["title"] = "_moved";
    $tab_array[2]["on_click"] = 'window.location="advise.php?window_mode=popup&perform_action=toolbox&perform_action2=moved&current_student_id=' . $csid . '";';
    $tab_array[2]["active"] = true;

    $tab_array[3]["title"] = "_courses";
    $tab_array[3]["on_click"] = 'window.location="advise.php?window_mode=popup&perform_action=toolbox&perform_action2=courses&order=name&current_student_id=' . $csid . '";';

    $screen->page_tabs = $screen->draw_tabs($tab_array);

  }



  $screen->page_is_popup = true;
  $screen->page_content = $page_content;
  $screen->output_to_browser();

}


function display_popup_change_term($screen, $perform_action2)
{
  // Check for hooks...
  if (function_exists("advise_display_popup_change_term")) {
    return call_user_func("advise_display_popup_change_term", $screen, $perform_action2);
  }
  
  
  global $degree_plan, $student, $fp;

  $page_content = "";
  $page_content .= $screen->get_javascript_code();


  $page_content .= $screen->display_change_term();


  // Create the tabs for the page...
  $tab_array = array();
  $tab_array[0]["title"] = "_select";
  $tab_array[0]["active"] = true;

  $screen->page_tabs = $screen->draw_tabs($tab_array);



  $screen->page_is_popup = true;
  $screen->page_content = $page_content;
  $screen->output_to_browser();


}


function display_popup_change_track($screen, $perform_action2)
{
  // Check for hooks...
  if (function_exists("advise_display_popup_change_track")) {
    return call_user_func("advise_display_popup_change_track", $screen, $perform_action2);
  }

  global $degree_plan, $student, $fp;

  $page_content = "";
  $page_content .= $screen->get_javascript_code();


  $page_content .= $screen->display_change_track();


  // Create the tabs for the page...
  $tab_array = array();
  $tab_array[0]["title"] = "_select";
  $tab_array[0]["active"] = true;

  $screen->page_tabs = $screen->draw_tabs($tab_array);


  $screen->page_is_popup = true;
  $screen->page_content = $page_content;
  $screen->output_to_browser();


}



function display_popup()
{
  // Check for hooks...
  if (function_exists("advise_display_popup")) {
    return call_user_func("advise_display_popup");
  }
  
  
  global $degree_plan, $student, $fp;

  // This is a popup window, so, we need to figure out what
  // the user is trying to do.
  $perform_action = trim(addslashes($_GET["perform_action"]));
  $perform_action2 = trim(addslashes($_GET["perform_action2"]));

  // Since this is a popup, I know we can load from the cache.
  $_REQUEST["load_from_cache"] = "yes";

  init_screen();


  $db = new DatabaseHandler();
  $settings = $db->get_flightpath_settings();


  $page_content = "";
  $screen = new AdvisingScreen("advise.php", $fp, "popup");

  $blank_degree_id = "";
  if ($_REQUEST["blank_degree_id"] != "")
  { // Should contain the ID of the blank degree plan.
    $screen->bool_blank = true;
    $degree_plan = new DegreePlan($_REQUEST["blank_degree_id"]);
    $screen->degree_plan = $degree_plan;
    $blank_degree_id = $_REQUEST["blank_degree_id"];

  }


  if ($perform_action == "toolbox")
  {
    display_popup_toolbox($screen, $perform_action2);
    return;
  }

  if ($perform_action == "change_term")
  {
    display_popup_change_term($screen, $perform_action2);
    return;
  }

  if ($perform_action == "change_track")
  {
    display_popup_change_track($screen, $perform_action2);
    return;
  }


  if ($perform_action == "display_description")
  {
    $data_string = trim($_GET["data_string"]);
    $course = new Course();
    if ($data_string != "")
    {
      
      $course->load_course_from_data_string($data_string);
     
    }


    $page_content .= $screen->get_javascript_code();
    $page_content .= $screen->display_popup_course_description("", $course);
    // Create the tabs for the page...
    $tab_array = array();
    $tab_array[0]["title"] = "_description";
    $tab_array[0]["active"] = true;

    if ($_SESSION["fp_can_substitute"] == true && !$screen->bool_blank)
    {
      if ($course->bool_substitution != true && $course->grade == "")
      { // By checking grade, we are making sure this course has NOT already
        // been taken by the student.  In other words, verify that this course
        // is an unfulfilled requirement on the degree plan ONLY.
        $extra_vars = "hours_avail=$course->max_hours";
        $tab_array[1]["title"] = "_substitute";
        $tab_array[1]["on_click"] = "popup_substitute_selected(\"$course->course_id\",\"$course->assigned_to_group_id\",\"$course->assigned_to_semester_num\",\"$extra_vars\");";
      }
    }

    $screen->page_tabs = $screen->draw_tabs($tab_array);
  }


  if ($_SESSION["fp_can_substitute"] == true)
  {
    if ($perform_action == "substitute_selected")
    {
      $course_id = trim($_GET["course_id"]);
      $group_id = trim(addslashes($_GET["group_id"]));
      $semester_num = trim(addslashes($_GET["semester_num"]));
      $group_hours_remaining = trim(addslashes($_GET["group_hours_remaining"]));

      $page_content .= $screen->get_javascript_code();
      $page_content .= $screen->display_popup_substitute($course_id, $group_id, $semester_num, $group_hours_remaining);

    }
  }


  if ($perform_action == "display_group_select")
  {
    $course_id = trim($_GET["course_id"]);
    $group_id = trim(addslashes($_GET["group_id"]));
    $group_hours_remaining = trim(addslashes($_GET["group_hours_remaining"]));
    $semester_num = trim(addslashes($_GET["semester_num"]));

    if (!$group = $degree_plan->find_placeholder_group($group_id, $semester_num))
    {
      admin_debug("Could not find group $group_id in semester $semester_num.");
    }

    if ($group_id == -88)
    { // This is the Add a Course group.  We must initialize it, as it
      // does not exist yet.
      // We need to populate this group now.
      $group->list_courses = $fp->get_all_courses_in_catalog_year($settings["current_catalog_year"]);
      $group->title = "Add an Additional Course";
      $group->list_courses->assign_group_id($group_id);
      $group->list_courses->load_course_descriptive_data();
    }


    if ($course_id != "")
    {
      // Meaning, a course_id was specified, so make sure
      // it is "selected" inside the group and branches.

      $course = new Course();
      $course->course_id = $course_id;

      $temp_course_list = $group->find_courses($course);
      if (!$temp_course_list)
      {
        $temp_course_list = $degree_plan->find_courses($course_id, $group_id, $semester_num);
      }

      if ($temp_course_list)
      {
        $temp_course_list->reset_counter();
        while($temp_course_list->has_more())
        {
          $temp_course = $temp_course_list->get_next();
          $temp_course->bool_selected = true;
          //$temp_course->assigned_to_semester_num = $semester_num;
        }
      }

      
    }

    if ($perform_action2 == "" || $perform_action2 == "select")
    {
      $page_content .= $screen->get_javascript_code();
      if ($group)
      {
        $page_content .= $screen->display_popup_group_select($group, $group_hours_remaining);
      }
      // Create the tabs for the page...
      $tab_array = array();
      $tab_array[0]["title"] = "_description";
      $tab_array[0]["on_click"] = "popup_describe_selected(\"$group_id\",\"$semester_num\",\"0\",\"\",\"group_hours_remaining=$group_hours_remaining&blank_degree_id=$blank_degree_id\");";
      $tab_array[1]["title"] = "_select";
      $tab_array[1]["active"] = true;

      // If we are allowed to substitute....
      if ($_SESSION["fp_can_substitute"] == true && $group_id != -88 && !$screen->bool_blank)
      {
        $tab_array[2]["title"] = "_substitute";
        $tab_array[2]["on_click"] = "popup_substitute_selected(\"0\",\"$group_id\",\"$semester_num\",\"group_hours_remaining=$group_hours_remaining\");";
      }

      $screen->page_tabs = $screen->draw_tabs($tab_array);
    }

    if ($perform_action2 == "describe_course")
    {
      $page_content .= $screen->get_javascript_code();
      $page_content .= $screen->display_popup_course_description($course_id,null,$group, true);
      // Create the tabs for the page...
      $tab_array = array();
      $tab_array[0]["title"] = "_description";
      $tab_array[0]["active"] = true;
      $tab_array[1]["title"] = "_select";
      $subject = trim($_GET["selected_subject"]);

      $tab_array[1]["on_click"] = "popup_back_to_group_select(\"$course_id\",\"$group_id\",\"$semester_num\",\"selected_subject=$subject&group_hours_remaining=$group_hours_remaining&blank_degree_id=$blank_degree_id\");";

      // If we are allowed to substitute....
      if ($_SESSION["fp_can_substitute"] == true && $group_id != -88 && !$screen->bool_blank)
      {
        $tab_array[2]["title"] = "_substitute";
        $tab_array[2]["on_click"] = "popup_substitute_selected(\"$course_id\",\"$group_id\",\"$semester_num\",\"group_hours_remaining=$group_hours_remaining\");";
      }

      $screen->page_tabs = $screen->draw_tabs($tab_array);

    }



  }


  $screen->page_is_popup = true;
  $screen->page_hide_report_error = true;
  $screen->page_content = $page_content;
  $screen->output_to_browser();


  // Should we re-cache the course inventory?  If there have been any changes
  // to it, then we will see that in a GLOBALS variable...
  if ($GLOBALS["cache_course_inventory"] == true)
  {
    $_SESSION["fp_cache_course_inventory"] = serialize($GLOBALS["fp_course_inventory"]);
  }


  //admin_debug("finsihed");

}


function display_screen()
{
  // Check for hooks...
  if (function_exists("advise_display_screen")) {
    return call_user_func("advise_display_screen");
  }
  
  global $degree_plan, $student, $fp;
  $db = new DatabaseHandler();
  //admin_debug("Starting script...", "main");
  init_screen();

  $page_content = "";
  $log_action = "view_by_year";
  $log_extra = $student->student_id;

  if ($GLOBALS["advising_view"] == "type")
  {
    $screen = new AdvisingScreenTypeView("advise.php", $fp);
    $screen->view = "type";
    $log_action = "view_by_type";

  } else {

    // Default advising view.  "View by Year"
    $screen = new AdvisingScreen("advise.php", $fp);
  }

  if ($GLOBALS["print_view"] == "yes")
  {
    $screen->bool_print = true;
    $screen->screen_mode = "not_advising";
    $log_extra .= ",print_view";
  }


  $page_content .= $screen->display_greeting();

  if ($GLOBALS["advising_what_if"] == "yes" && $GLOBALS["what_if_major_code"] == "")
  {
    // We are in WhatIf, but we have not selected a major, so give
    // the user a selection screen.
    $screen->screen_mode = "not_advising";
    $page_content .= $screen->display_what_if_selection();
  } else {
    // This is a normal advising screen.  Either View or WhatIf.

    $page_content .= $screen->display_view_options();

    $screen->build_screen_elements();

    $page_content .= $screen->display_screen();

  }


  // If we are in WhatIf mode, let's write something special to
  // the log.
  if ($GLOBALS["advising_what_if"] == "yes" && $GLOBALS["what_if_major_code"] != "")
  {
    $log_action .= "_whatif";
    $log_extra = $GLOBALS["what_if_major_code"] . " " . $GLOBALS["what_if_track_code"];
  }

  $db->add_to_log($log_action, $log_extra);


  $screen->build_system_tabs(2, true);
  if ($GLOBALS["advising_what_if"] == "yes")
  {
    $screen->build_system_tabs(5, true, true);
  }

  $screen->page_scroll_top = trim($_POST["scroll_top"]);
  //admin_debug("Finished", "main");
  //admin_debug(strlen($page_content));
  $screen->page_has_search = true;
  if ($_SESSION["fp_user_type"] == "student")
  {
    $screen->page_has_search = false;
  }


  $screen->page_content = $page_content;
  // send to the browser
  $screen->output_to_browser();


  //	print_pre($student->list_courses_taken->toString());
  // Should we re-cache the course inventory?  If there have been any changes
  // to it, then we will see that in a GLOBALS variable...
  if ($GLOBALS["cache_course_inventory"] == true)
  {
    $_SESSION["fp_cache_course_inventory"] = serialize($GLOBALS["fp_course_inventory"]);
  }


  //admin_debug("Finished", "main");
}





function init_screen()
{
  
  // Check for hooks...
  if (function_exists("advise_init_screen")) {
    return call_user_func("advise_init_screen");
  }
    
  global $degree_plan, $student, $fp, $window_mode;

  $perform_action = trim(addslashes($_POST["perform_action"]));
  $temp_screen = new AdvisingScreen();
  $temp_screen->init_advising_variables();
  $bool_what_if = false;
  
  $csid = $GLOBALS["current_student_id"];


  $db = new DatabaseHandler();
  $cache = $_SESSION["cache_f_p$csid"];

  if ($GLOBALS["advising_what_if"] == "yes")
  {
    $major_code = $GLOBALS["what_if_major_code"];
    $track_code = $GLOBALS["what_if_track_code"];
    //$major_code = "ART";
    $bool_what_if = true;
    //$GLOBALS["load_from_cache"] = "no";
    $cache = $_SESSION["cache_what_if$csid"];
    //admin_debug("here");
  }

  $bool_draft = true;
  if ($GLOBALS["advising_load_active"] == "yes")
  { // If we are loading from Active, then rebuild the cache as well.
    $bool_draft = false;
    $GLOBALS["load_from_cache"] = "no";
  }

  if ($_SESSION["fp_user_type"] == "student")
  {
    $bool_draft = false;
    // never load a draft advising session if a student
    // is logged in!
  }


  ///////////////////////
  ///  Disable student data Caching....
  //$GLOBALS["load_from_cache"] = "no";


  // Attempt to load the course inventory cache...
  if ($course_inventory = unserialize($_SESSION["fp_cache_course_inventory"]))
  {
    $GLOBALS["fp_course_inventory"] = $course_inventory;
  }


  if ($GLOBALS["load_from_cache"] == "yes" && $cache != "" && $fp = unserialize($cache))
  {
    //admin_debug("Unserializing...");
    $fp->db = new DatabaseHandler();
    $student = $fp->student;
    $degree_plan = $fp->degree_plan;
    $student->db = new DatabaseHandler();
    $degree_plan->db = new DatabaseHandler();
    //admin_debug("Done Unserializing... $bool_what_if");

  } else {
    $fp = new FlightPath();
    //admin_debug("xx");
    $fp->init();
    //admin_debug("xx");
    $student = $fp->student;
    $degree_plan = $fp->degree_plan;
    $GLOBALS["load_from_cache"] = "no";
    //admin_debug($GLOBALS["advisingMajorCode"]);
  }


  // Should we update the USER settings for anything?
  if ($GLOBALS["fp_update_user_settings_flag"] != "")
  {
    //$GLOBALS["userSettings_hideCharts"] = $_REQUEST["hideCharts"];
    if (!$db->update_user_settings_from_post($_SESSION["fp_user_id"]))
    {
      admin_debug("could not write user settings.");
    }
  }


  if ($perform_action == "save_draft")
  {
    // Save, then reload the student.

    $fp->save_advising_session_from_post(0,true);

  }

  if ($perform_action == "save_active")
  {
    // Save, then go to the history screen.
    $adv_id_array = $fp->save_advising_session_from_post(0,false);
    display_advisee_history(true, $adv_id_array);
    die;
  }


  
  if ($bool_what_if == true && $GLOBALS["what_if_major_code"] == "")
  {
    // In other words, we are on the WhatIf tab, but we have not
    // selected a major.  So, just exit out.  We will give the user
    // a display_screen later.
    return;
  }





  
  if ($GLOBALS["load_from_cache"] != "yes")
  { // do not load from cache....

    $student->load_student();

    $student->load_student_substitutions();

    $student->load_unassignments();

    $student->list_courses_taken->sort_alphabetical_order();
    $student->list_courses_taken->sort_most_recent_first();
    //	print_pre($student->list_courses_taken->toString());


    $fp->flag_outdated_substitutions();
    $fp->assign_courses_to_semesters(); // bare degree plan. not groups.
    $fp->assign_courses_to_groups();
    
  }

  //admin_debug("Serializing...");
  if ($GLOBALS["save_to_cache"] != "no" && $window_mode != "popup")
  {
    if ($bool_what_if == false)
    { // NOT in whatIf mode.  Normal.
      //admin_debug("start serialize");

      $_SESSION["cache_f_p$csid"] = serialize($fp);
      
      //admin_debug(strlen($_SESSION["cache_f_p$csid"]));
      //admin_debug("Done Serializing...normal");
      
    } else {
      // We are in WhatIf mode.
      $_SESSION["cache_what_if$csid"] = serialize($fp);      
      //admin_debug("Done Serializing...what if");


    }
  }





  $fp->load_advising_session_from_database(0,$advising_term_id,$bool_what_if,$bool_draft,0);

  //admin_debug("load advising session");

  // Once we have loaded the advising session, we should always try to load
  // from draft from then on out.
  $GLOBALS["advising_load_active"] = "";

  //admin_debug("mem:" .  round(memory_get_usage(true)/1024/1024,1) . "mb");

  //print_pre($student->list_courses_taken->toString());
    
  
}




function display_advisee_history($bool_from_save = false, $advising_session_id_array = "")
{

  // Check for hooks...
  if (function_exists("advise_display_advisee_history")) {
    return call_user_func("advise_display_advisee_history", $bool_from_save, $advising_session_id_array);
  }

  
  $screen = new AdvisingScreen("",null,"not_advising");
  $db = new DatabaseHandler();


  //if (!$bool_from_save)
  //{
  $screen->init_advising_variables(true);
  //}


  $student_id = $GLOBALS["advising_student_id"];


  $pC = "";

  $pC .= $screen->get_javascript_code();
  $pC .= $screen->display_greeting();
  $pC .= $screen->display_begin_semester_table();
  $pC .= $screen->draw_currently_advising_box(true);

  //-------------------------------------------------------------
  //----   If we are coming back from a perm save, then we should display
  //----   a message and a convienent link to allow the advisor to get the
  //----   advising summary.
  //-------------------------------------------------------------
  if ($bool_from_save == true)
  {

    // We should have the advising session ID's in the
    // advisingSessionIDArray array.
    $click_links = "";

    foreach($advising_session_id_array as $term_id=>$value)
    {
      $aid = $advising_session_id_array[$term_id];
      if ($aid != "")
      {
        $new_course = new Course();
        $new_course->term_id = $term_id;
        $term_name = $new_course->get_term_description();
        $click_links .= "<li>
								<a href='javascript: popup_print_window(\"advise.php?window_mode=summary&advising_session_id=$aid\");'>
								<img src='$screen->theme_location/images/popup.gif' border='0'>
								$term_name</a>";


      }
    }


    // onClick='popup_print_window(\"advise.php?window_mode=summary&advising_session_id=ADVID\");'
    $pC .= "
				<tr>
					<td colspan='2' width='100%'>
					
				<div class='hypo' 
				align='left' style='border: 1px solid black;
							margin: 10px 0px 10px 0px; padding: 10px; 
							font-size: 12pt; font-weight: bold;'>
				You have successfully advised " . $db->get_student_name($student_id) . " ($student_id).
				<br><span style='color: blue;'>Click 
				 to view a pop-up printable summary for: 
				 <ul style='margin-top: 5px; margin-bottom: 5px;'>
				$click_links
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
  $pC .= $screen->draw_curved_title("Advising History");
  $pC .= "<table border='0' cellspacing='0'>";
  $old_session_d_t = 0;
  $a_count = 0;
  $is_empty = true;
  $first_style = "color: maroon; font-weight:bold;";
  $on_mouse_over = "onmouseover=\"style.backgroundColor='#FFFF99'\"
           onmouseout=\"style.backgroundColor='white'\" ";

  $res = $db->db_query("SELECT * FROM advising_sessions
							WHERE `student_id`='$student_id'
							AND `is_draft`='0'
							AND `is_empty`='0'
							ORDER BY `datetime` DESC, `term_id` DESC ");
  while($cur = $db->db_fetch_array($res))
  {
    extract($cur, 3, "db");

    $dt = date("n/j/y g:ia",strtotime($db_datetime));

    // Is this datetime within 5 seconds of the previous datetime?
    // If so, they were likely saved together, and are part
    // of the same advising session.  Otherwise, this is a NEW
    // advising session.  The if statement below is testing is this
    // a new advising session.
    $test_d_t = strtotime($db_datetime);
    //admin_debug($old_session_d_t . " - " . $test_d_t);
    if ($old_session_d_t < ($test_d_t - 5) || $old_session_d_t > ($test_d_t + 5))
    {
      $p = "20px;";
      if ($a_count == 0)
      {
        $p = "10px;";
      }

      $old_session_d_t = $test_d_t;
      $advised_by = "<div style='padding-top: $p'>
							<b>Advised by " . $db->get_faculty_name($db_faculty_id, false) . "</b>
						</div>";

      $pC .= "<tr><td colspan='2' class='tenpt'>
							$advised_by
						</td>
					</tr>";
      $a_count++;

    }
    $is_empty = false;

    if ($a_count > 1)
    {
      $first_style = "";
    }


    $on_click = "popup_print_window(\"advise.php?window_mode=summary&advising_session_id=$db_advising_session_id\");";

    $new_course = new Course();
    $new_course->term_id = $db_term_id;
    $term = $new_course->get_term_description();

    $pC .= "<tr $on_mouse_over style='cursor: pointer; $first_style'
					on_click='$on_click'>
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


  if ($is_empty == true) {
    $pC .= "<div class='tenpt'>No advising history available.</div>";
  }


  //----------------------------------------------------------------------------------------
  //------------------------------ COMMENT HISTORY -----------------------------------------
  //----------------------------------------------------------------------------------------
  $pC .= "</td><td width='50%' valign='top'>";
  $pC .= $screen->draw_curved_title("Comment History");
  $pC .= "<table border='0' cellspacing='0'>";

  $old_term_id = "";
  $first_style = "first";
  $is_empty = true;
  $has_admin_category = false;
  $access_line = "";
  if ($_SESSION["fp_user_type"] == "student")
  { // May not be necessary, since students don't see this tab anyway...
    $access_line = "and `access_type`='public' ";
  }
  
  $pC .= "<tr><td colspan='3' class='tenpt'>
				<!--STARTCOM$cat_type--><div style='padding-top: 10px;'>
					<b>Advising Comments</b>
					&nbsp; 
				<a href='javascript: popupPrintWindow(\"comments.php?performAction=displayComment&category=all\");' 
					class='nounderline'><img src='$screen->theme_location/images/popup.gif' border='0'>view/print all</a>
				</div><!--ENDCOM$cat_type-->
				</td></tr>";

  $res = $db->db_query("select * from advising_comments
						where `student_id`='$student_id' 
						and `delete_flag`='0'
						$access_line
						$cat_line
						order by `datetime` desc ");
  while ($cur = $db->db_fetch_array($res))
  {
    extract($cur, 3, "db");
    $dt = date("n/j/y g:ia",strtotime($db_datetime));


    if ($first_style == "first")
    {
      $first_style = "color: maroon; font-weight:bold;
					";
    }




    $on_click = "popup_print_window(\"comments.php?perform_action=display_comment&id=$db_id\");";
    $pC .= "<tr $on_mouse_over style='cursor:pointer; $first_style $extra_style'
					on_click='$on_click'>
					<td valign='top' width='165' class='tenpt'
						style='padding-left: 20px;'>
						" . $db->get_faculty_name($db_faculty_id, false) . "
					</td>
					<td valign='top' class='tenpt'>
					$dt$ast
					</td>
				</tr>";

    $is_empty = false;
    $first_style = "";
  }

  if ($is_empty == true) {
    
    $pC .= "<tr><td colspan='4' class='tenpt'>
						<div style='padding-left: 20px;'>
							No $cat_type comment history available.</div></td></tr>";
  }

  $pC .= "</table>";


  $pC .= "</td></tr>";




  $pC .= $screen->display_end_semester_table();

  $pC .= "<script type='text/javascript'>" . $screen->get_j_s_popup_print_window() . "</script>";

  $screen->page_content = $pC;
  $screen->page_has_search = true;
  if ($_SESSION["fp_user_type"] == "student")
  {
    $screen->page_has_search = false;
  }
  $screen->build_system_tabs(4);
  $screen->page_title = "FlightPath - History";
  // send to the browser
  $screen->output_to_browser();

  die;

}

?>
