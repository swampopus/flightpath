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

die("don't use main.php anymore, please");
/* 
This is the first page users see when they log into the system.
*/
session_start();
header("Cache-control: private");

require_once("bootstrap.inc");


$perform_action = trim(addslashes($_REQUEST["perform_action"]));

/*
If the user is NOT logged in, then show them the login screen.
*/
$msg = "";
if ($perform_action == "clear_cache")
{
  // Wipe out the cache, so joann doesn't have to log out
  // and back in.

  clear_cache(); 
  //$_SESSION["clear_cache"] = "yes";
  $msg = "The cache has been cleared.";
}


// Set/unset draft mode.
if ($perform_action == "draft_mode_yes")
{
  clear_cache();
  $_SESSION["fp_draft_mode"] = "yes";

}
if ($perform_action == "draft_mode_no")
{
  clear_cache();
  $_SESSION["fp_draft_mode"] = "no";
  $msg = "Now viewing in Regular Mode.
			This is what regular users currently see in the system.";
}


if ($perform_action == "perform_logout")
{
  perform_logout();
  die;
}

if ($perform_action == "perform_login")
{
  perform_login();
  die;
} else if ($_SESSION["fp_logged_in"] != true)
{

  display_login();
  die;
}




if ($perform_action == "switch_user" && user_has_permission("de_can_switch_users")) {

  clear_cache();
  //admin_debug("userid: " . $_REQUEST["switch_user_id"]);
  perform_login(true, $_REQUEST["switch_user_id"]);
  //admin_debug("right here");
  die;
} else if ($perform_action == "switch_user" && !user_has_permission("de_can_switch_users"))
{
  die("You do not have access to this function.");
}


/////////////////////////////////////
///  Are we trying to save the draft
///  from a tab change?
/////////////////////////////////////
$fp = new FlightPath();
$fp->process_request_save_draft();

/*if ($_REQUEST["saveDraft"] == "yes")
{
$fp = new FlightPath();
$fp->init(true);
$fp->saveAdvisingSessionFromPost(0,true);
}
*/



$screen = new AdvisingScreen("",null,"not_advising");
$screen->init_advising_variables(true);



// Display the main page...
display_main($msg);
die;

function clear_cache()
{

  $_SESSION["fp_cache_course_inventory"] = "";

  foreach ($_SESSION as $skey=>$val)
  {
    //admin_debug($skey);
    if (strstr($skey, "cache"))
    {
      //admin_debug("wiping $skey");
      $_SESSION[$skey] = "";
    }
  }
}


function perform_logout()
{

  // Check for hooks...
  if (function_exists("main_perform_logout")) {
    return call_user_func("main_perform_logout");
  }

  
  // This will log the user out of the system.
  // log the logout first...
  $db = new DatabaseHandler();
  $db->add_to_log("logout");


  $_SESSION["fp_logged_in"] = false;
  $_SESSION["fp_user_id"] = false;
  $_SESSION["fp_user_type"] = false;
  $_SESSION["fp_can_advise"] = false;
  $_SESSION["fp_can_search"] = false;
  $_SESSION["fp_can_substitute"] = false;
  $_SESSION["fp_cache_course_inventory"] = false;
  $_SESSION["fp_faculty_user_major_code"] = false;

  // Just to make sure we get everything...
  session_destroy();

  display_login($msg);

}



function perform_login($bool_bypass_verification = false, $bypass_user_id = "")
{

  // Check for hooks...
  if (function_exists("main_perform_login")) {
    
    return call_user_func("main_perform_login", $bool_bypass_verification, $bypass_user_id);
  }

  // Are we bypassing logins in the settings, and just giving this user
  // full_admin access?
  if ($GLOBALS["fp_system_settings"]["GRANT_FULL_ACCESS"] == TRUE) {
    $_SESSION["fp_logged_in"] = TRUE;
    $_SESSION["fp_user_id"] = 1;
    $_SESSION["fp_user_type"] = "full_admin";
    $_SESSION["fp_can_advise"] = TRUE;
    $_SESSION["fp_can_search"] = TRUE;
    $_SESSION["fp_can_substitute"] = TRUE;
    $_SESSION["fp_cache_course_inventory"] = false;
    $_SESSION["fp_can_modify_comments"] = TRUE;
    display_main();
    return;
  }
  
  
  // First clear session vars
  $_SESSION["fp_logged_in"] = false;
  $_SESSION["fp_user_id"] = false;
  $_SESSION["fp_user_type"] = false;
  $_SESSION["fp_can_advise"] = false;
  $_SESSION["fp_can_search"] = false;
  $_SESSION["fp_can_substitute"] = false;
  $_SESSION["fp_cache_course_inventory"] = false;
  $_SESSION["fp_faculty_user_major_code"] = false;

  // Attempt to log the user into the system.
  $user_id = trim($_REQUEST["user_id"]);
  $password = trim($_REQUEST["password"]);
  $db = new DatabaseHandler();

  // First thing we need to do -- check to make sure the settings
  // table contains a currentCatalogYear setting, since it's required.
  $settings = $db->get_flightpath_settings();
  if (trim($settings["current_catalog_year"] == ""))
  {
    display_login("<font color='red'>FlightPath is currently undergoing
							routine system maintenance.  Please wait
							a few minutes and try to log in again.</font>");
    die;

  }

  if ($settings["offline_mode"] == "1")
  {  // We are not allowing logins right now.
    display_login();
    die;
  }

  // Are we using the "switch user" feature?
  if ($bool_bypass_verification == true)
  {
    $user_id = $bypass_user_id;
    $_SESSION["fp_switched_user"] = true;
    $db->add_to_log("switch_user","$user_id");
  }

  $is_student = $is_faculty = false;
  $from_username = $user_id;
  
  
  // Attempt to verify the user by the two user types.
  $verify_faculty_login = fp_verify_all_faculty_logins($user_id, $password);
  $verify_student_login = fp_verify_all_student_logins($user_id, $password);
  
  if ($verify_faculty_login) $user_id = $verify_faculty_login;
  if ($verify_student_login) $user_id = $verify_student_login;
  
  // What are this user's possible user types?
  if (trim($db->get_student_name($user_id)) != "")
  {
    $is_student = true;
  }
  if (trim($db->get_faculty_name($user_id)) != "")
  {
    $is_faculty = true;
  }



  $user_type = determine_staff_user_type($user_id);


  $bool_no_login = false;
  if ((($user_type != "limited_faculty_student")
  && ($verify_faculty_login) || ($bool_bypass_verification == true && $is_faculty)))
  {

    // The user is in the faculty/staff database.

    $_SESSION["fp_logged_in"] = true;
    $_SESSION["fp_user_id"] = $user_id;
    $_SESSION["fp_user_name"] = $db->get_faculty_name($user_id, true);
    
    // Figure out their majorCode, if it exists.
    //$_SESSION["fp_faculty_user_major_code"] = determineFacultyUserMajorCode($user_id);
    $_SESSION["fp_faculty_user_major_code"] = $db->get_faculty_major_code($user_id);
    
    $_SESSION["fp_user_type"] = $user_type;

    // Figure out their privileges based on user type...
    if ($user_type == "full_admin" || $user_type == "college_coordinator")
    {
      $_SESSION["fp_can_advise"] = true;
      $_SESSION["fp_can_search"] = true;
      $_SESSION["fp_can_substitute"] = true;
      $_SESSION["fp_can_modify_comments"] = true;
    }
    if ($user_type == "advisor" || $user_type == "adviser")
    {
      $_SESSION["fp_can_advise"] = true;
      $_SESSION["fp_can_search"] = true;
      $_SESSION["fp_can_substitute"] = false;
      $_SESSION["fp_can_modify_comments"] = true;
    }
    if ($user_type == "viewer")
    {
      $_SESSION["fp_can_advise"] = false;
      $_SESSION["fp_can_search"] = true;
      $_SESSION["fp_can_substitute"] = false;
      $_SESSION["fp_can_modify_comments"] = false;

    }
    if ($user_type == "none" && $is_student == false)
    {
      // Users with a type of "none" may go to the Main tab,
      // but that is all!  Once there, they will see no other tabs,
      // and will be given a message telling them they cannot advise
      // in FP.
      // We let them in, so they can still access the Tools of FP.

      $_SESSION["fp_logged_in"] = true;
      $_SESSION["fp_can_advise"] = false;
      $_SESSION["fp_can_search"] = false;
      $_SESSION["fp_can_substitute"] = false;
      $_SESSION["fp_can_modify_comments"] = false;
    } else if ($user_type == "none" && $is_student == true)
    { // is a student/staff member.  Attempt a student login.
      $bool_no_login = true;
    }


    // Get the permissions for this user.
    $res = $db->db_query("select * from flightpath.users
								where `faculty_id`='$user_id' ");
    $cur = $db->db_fetch_array($res);
    $temp = split(",",$cur["permissions"]);
    foreach ($temp as $perm)
    {
      $perm = trim($perm);
      if ($perm != ""){$_SESSION[$perm] = true;}
    }


    // Okay, the user is logged in.  Proceed to the Main tab.
    if (!$bool_no_login)
    {
      $db->add_to_log("login", $from_username);
      display_main();
      die;
    }


  }




  // Is the user a student?
  if ($verify_student_login || $bool_bypass_verification == true)
  {
    // The user is a student.

    $allowed_student_ranks = $GLOBALS["fp_system_settings"]["allowed_student_ranks"];
    
    // Before we let them in, we need to make sure they are undergrad,
    // As FP is only designed
    $rank = $db->get_student_rank($user_id);
    if (!in_array($rank, $allowed_student_ranks))
    { // Student is not an undergread (or, just not allowed in).
      $msg = "<font color='red'>
          {$GLOBALS["fp_system_settings"]["not_allowed_student_message"]}</font>";
      $db->add_to_log("login_fail", "grad student");
      display_login($msg);
      die;
    }

    // The student is an undergrad, so go ahead and log them in.
    $_SESSION["fp_logged_in"] = true;
    $_SESSION["fp_user_id"] = $user_id;
    $_SESSION["fp_user_name"] = $db->get_student_name($user_id, true);
    $_SESSION["fp_user_type"] = "student";
    $_SESSION["fp_can_advise"] = false;
    $_SESSION["fp_can_substitute"] = false;
    $_SESSION["fp_can_modify_comments"] = false;


    // We also need to make it so that the student is "advising" themselves,
    // so that the View shows up correctly.
    $_SESSION["advising_student_id"] = $user_id;
    $GLOBALS["advising_student_id"] = $user_id;

    // Get the student's major as it would have been gotten from
    // the search...
    $major_code = $db->get_student_major_from_d_b($user_id);
    $_SESSION["advising_major_code"] = $major_code;
    $GLOBALS["advising_major_code"] = $major_code;

    $_SESSION["advising_load_active"] = "yes";
    $GLOBALS["advising_load_active"] = "yes";

    $_SESSION["clear_session"] = "yes";
    $GLOBALS["clear_session"] = "yes";

    // Okay, the user is logged in.  Proceed to the Main tab.
    $db->add_to_log("login", $from_username);
    display_main();
    die;

  }


  if ($bool_no_login == true)
  {
    // We were unable to login as either faculty or staff, so kick them
    // out.
    display_login("<font color='red'>We're sorry, but you are not allowed access in FlightPath.
			Please contact your department head for more information.</font>");
    die;
  }


  // If we are here, then we did not log in correctly.
  $msg = "<font color='red'>That username/password combination cannot be verified.
					Please check your spelling and try again.</font>
			";

  $db->add_to_log("login_fail", $from_username);
  display_login($msg);

}


function display_login($msg = "")
{
  // Check for hooks...
  if (function_exists("main_display_login")) {
    return call_user_func("main_display_login", $msg);
  }
  
  
  // This is the login page for FlightPath.
  $screen = new AdvisingScreen();

  $pC .= $screen->get_javascript_code();

  $pC .= "
	
		<div align='center' style='font-size: 14pt;'>
 		Welcome to <b><i><font color='maroon'>FlightPath</font></i></b>, the electronic student
 		advising system!
 		</div><br>";

  if ($screen->settings["urgent_msg"] != "")
  {
    $pC .= "<div class='tenpt hypo' style='margin: 10px; padding: 5px;'>
				<b>Important Notice:</b> " . $screen->convert_b_b_code_to_h_t_m_l($screen->settings["urgent_msg"]) . "
				</div>";
  }

  if ($screen->settings["offline_mode"] == "1") {
    // Logins have been disabled in the settings.  Do not display the login
    // form to the user.
    $pC .= "<div>";
    $msg = trim($screen->convert_b_b_code_to_h_t_m_l($screen->settings["offline_msg"]));
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
  
  $important_notice = "
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
  if ($screen->page_is_mobile) $w1 = "90%";
  
  $login_box = "
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
          <br><span class='tenpt' ><a href='javascript: popupHelpWindow(\"help.php?i={$GLOBALS["fp_system_settings"]["login_help_page_id"]}\");' style='text-decoration:none;'>need help logging-in?</a></span>
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
   		<input type='hidden' name='performAction' value='perform_login'>
   	";
  
  if ($screen->page_is_mobile == true) {
    // the user is viewing this on a mobile device, so make it look
    // a bit nicer.
    $pC .= $screen->draw_c_fieldset($important_notice, "View important notice", true);
    $pC .= $login_box; 
  }
  else {
    // This is NOT mobile, this is a regular desktop browser.
    $pC .= "
     	<table border='0'>
     	<tr>
     	 <td valign='top' width='40%'>
     	  $important_notice
     	 </td>
     	<td valign='middle'>
        $login_box   		
  
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
  /*$page_content = $pC;
  $page_on_load = "document.getElementById(\"cwid_box\").focus();  ";
  $page_hide_report_error = true;
  include("template/fp_template.php");*/

  $screen->page_content = $pC;
  $screen->page_has_search = false;
  $screen->page_on_load = "document.getElementById(\"cwid_box\").focus(); ";
  $page->page_hide_report_error = true;
  // send to the browser
  $screen->output_to_browser();


}


function display_main($msg = "")
{

  // Check for hooks...
  if (function_exists("main_display_main")) {
    return call_user_func("main_display_main", $msg);
  }

  
  $screen = new AdvisingScreen("",null,"not_advising");
  $screen->admin_message = $msg;

  $pC = "";

  $pC .= $screen->display_greeting();
  $pC .= $screen->display_begin_semester_table();

  if ($_SESSION["fp_user_type"] != "none")
  {
    $pC .= $screen->draw_currently_advising_box(true);
  } else {
    // Let the user know they have no privileges in FP.
    $pC .= "<tr>
				<td colspan='5'>
				
				 <div class='hypo tenpt' style='margin: 10px; padding: 10px;'>
				   <div style='float:left; padding-right: 20px; height: 50px;'>
					<img src='$screen->theme_location/images/alert_lg.gif'>
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

  
  $announcements = get_announcements($screen);
  $tools = get_tools($screen);
  $admin_tools = get_admin_tools($screen);
  
  if ($screen->page_is_mobile) {
    $pC .= "<tr><td colspan='2'>$announcements $tools $admin_tools</td></tr>";
  }
  else {
    $pC .= "<tr><td width='50%' valign='top'  style='padding-right: 10px;'>";
    $pC .= $announcements;
    $pC .= "</td><td width='50%' valign='top' style='padding-left: 10px;'>";
    $pC .= $tools;
    $pC .= $admin_tools;
    $pC .= "</td></tr>";
  }
  
  $pC .= $screen->display_end_semester_table();
  $pC .= "<form id='mainform' method='POST'>
			<input type='hidden' id='scrollTop'>
			<input type='hidden' id='performAction' name='performAction'>
			<input type='hidden' id='advisingWhatIf' name='advisingWhatIf'>
			<input type='hidden' id='currentStudentID' name='currentStudentID'>
			</form>";

  $pC .= $screen->get_javascript_code();

  /*	$pageTabs = $screen->drawSystemTabs(0);
  $page_has_search = true;
  $page_content = $pC;
  include("template/fp_template.php");
  */

  $screen->page_content = $pC;
  $screen->page_has_search = true;
  if ($_SESSION["fp_user_type"] == "student" || $_SESSION["fp_can_advise"] == false)
  {
    $screen->page_has_search = false;
  }
  $screen->build_system_tabs(0);

  admin_debug("--");
  //////////////////////////////////////////////////////////
  // To cut down on how long it takes to load huge groups
  // like Free Electives, we will pre-load some of the course inventory here.
  if ($_SESSION["fp_cached_inventory_flag_one"] != true)
  {
    $load_number = $GLOBALS["fp_system_settings"]["load_course_inventory_on_login_number"];
    if ($load_number > 1) {
      $fp = new FlightPath();
      $fp->cache_course_inventory(0,$load_number);
      $_SESSION["fp_cached_inventory_flag_one"] = true;
    }
  }
  admin_debug("--");


  // send to the browser
  $screen->output_to_browser();



}


function get_announcements($screen)
{
  
  // Check for hooks...
  if (function_exists("main_get_announcements")) {
    return call_user_func("main_get_announcements", $screen);
  }
  
  
  $pC = "";
  $pC .= $screen->draw_curved_title("_announcements");

  $db = new DatabaseHandler();
  $settings = $db->get_flightpath_settings();

  $is_empty = true;
  // Pull out just the announcements XML and make it into its own array.
  if ($settings["announcements_xml"] != "")
  {
    if ($xml_array = fp_xml_to_array2($settings["announcements_xml"]))
    {
      // Expected format of the xmlArray:
      //[dt_timecode] = "announcement text."
      // ex: dt_111234432.  Use strtotime to convert.
      // It begins with dt_ because in XML the start of
      // an element must be a letter, not a number.

      krsort($xml_array);  // sort by most recent.

      foreach($xml_array as $datetime => $announcement)
      {
        $dt = str_replace("dt_", "", $datetime);

        $disp_time = date("D, M jS Y  - h:ia", $dt);
        // Re-enable HTML formatting in announcement...
        $temp = split(" ~~ ", $announcement);
        $visible = trim($temp[0]);
        $announcement_text = trim($temp[1]);
        $announcement_text = $screen->convert_b_b_code_to_h_t_m_l($announcement_text);

        if ($visible == "hide")
        {
          // visibility set to "hidden"
          continue;
        }

        if ($visible == "faculty" && $_SESSION["fp_user_type"] == "student")
        { // skip faculty-only comments if we are a student!
          continue;
        }

        $pC .= "<div class='elevenpt' style='margin-top: 20px;'>$announcement_text
							<div align='right' class='tenpt' style='color: gray; padding-right: 10px;'>
							<i>Posted $disp_time</i>
							</div>
						</div>";
      }
    }
  }



  return $pC;
}



function get_tools($screen)
{
  
  // Check for hooks...
  if (function_exists("main_get_tools")) {
    return call_user_func("main_get_tools", $screen);
  }

  
  $pC = "";

  $db = new DatabaseHandler();
  $settings = $db->get_flightpath_settings();
  $current_catalog_year = $settings["current_catalog_year"];

  $pC .= $screen->draw_curved_title("_tools");
  
  // Get all of the menu items which should appear here
  $menus = get_modules_menus();
  
  //var_dump($menus);
  
  $pC .= $screen->draw_menu_items($menus["tools"]);
    
  return $pC;
}


function get_admin_tools($screen)
{
  
  // Check for hooks...
  if (function_exists("main_get_admin_tools")) {
    return call_user_func("main_get_admin_tools", $screen);
  }

  $pC = "";
  $is_empty = TRUE;
  
  $pC .= "<div style='padding-top: 10px;'>&nbsp;</div>";
  $pC .= $screen->draw_curved_title("Special Administrative Tools");


  if (user_has_permission("de_can_access_admin_console")) {
    $pC .= $screen->draw_menu_item("admin.php", "_blank", "<img src='$screen->theme_location/images/toolbox.gif' border='0'>", "FlightPath Admin Console");
    $is_empty = FALSE;
  }
  
  if (user_has_permission("de_can_switch_users")) {

    $pC .= $screen->draw_menu_item("javascript:switch_user();", "", "<img src='$screen->theme_location/images/group.png' border='0'>", "Switch User");
          
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
  
  if (user_has_permission("de_can_administer_data_entry")) {
    
    $pC .= $screen->draw_menu_item("main.php?perform_action=clear_cache&current_student_id=$csid", "", "-", "Clear Cache");
    
    $csid = $GLOBALS["current_student_id"];
    $draft_link = $screen->draw_menu_item("main.php?perform_action=draft_mode_yes&current_student_id=$csid", "", "-", "Switch to Draft Mode");
    if ($GLOBALS["bool_use_draft"] == true)
    {
      $draft_link = $screen->draw_menu_item("main.php?perform_action=draft_mode_no&current_student_id=$csid", "", "-", "Switch to Regular Mode");
    }
    
    $pC .= $draft_link;
    $is_empty = FALSE;
    
  }

  // Get all of the menu items which should appear here
  $menus = get_modules_menus();
  // Now, let's look for menu items with the location "admin_tools"...
  if (is_array($menus["admin_tools"])) {
    
    $admin_tools_menu = $screen->draw_menu_items($menus["admin_tools"]);  
    //var_dump($admin_tools_menu);
    if ($admin_tools_menu) {
      $pC .= $admin_tools_menu;
      $is_empty = FALSE;
    }  
    
      
  }

  
  if ($is_empty) {
    return "";
  }  

  return $pC;
}



?>