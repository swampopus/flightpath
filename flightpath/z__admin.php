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

$_SESSION["de_advanced_mode"] = true;

require_once("bootstrap.inc");

 

$screen = new AdvisingScreen();
$screen->init_advising_variables();
// We need to do this so that our course's load_descriptive_data function
// will load the most recent course names.

$db = new DatabaseHandler();

init_hidden_variables();

$perform_action = trim($_REQUEST["perform_action"]);


/*if ($_SESSION["fpDataEntryLoggedIn"] != true && $perform_action != "login")
{
  displayLogin();
  die;
}

if ($perform_action == "login")
{
  performLogin();
}

if ($perform_action == "logout")
{
  performLogout();
  die;
}
*/

if (!user_has_permission("de_can_access_admin_console")) {
  display_access_denied("Admin Console");
}




if ($perform_action == "" || $perform_action == "menu")
{
  display_main_menu();
}

if ($perform_action == "edit_announcements")
{
  display_edit_announcements();
}

if ($perform_action == "preview_announcement")
{
  display_preview_announcement();
}


if ($perform_action == "edit_urgent_msg")
{
  display_edit_urgent_msg();
}

if ($perform_action == "edit_offline_mode")
{
  display_edit_offline_mode();
}


if ($perform_action == "edit_help")
{
  display_edit_help();
}

if ($perform_action == "edit_users")
{
  display_edit_users();
}


if ($perform_action == "transfer_data")
{
  display_transfer_data();
}

if ($perform_action == "perform_transfer_data")
{
  perform_transfer_data();
}


if ($perform_action == "apply_draft_changes")
{
  display_apply_draft_changes();
}


if ($perform_action == "perform_clear_john_doe")
{
  perform_clear_john_doe();
}


if ($perform_action == "perform_apply_draft_changes")
{
  perform_apply_draft_changes();
}

if ($perform_action == "perform_process_group_definitions")
{
  perform_process_group_definitions();
}

if ($perform_action == "copy_degree")
{
  display_copy_degree();
}

if ($perform_action == "perform_copy_degree")
{
  perform_copy_degree();
}


if ($perform_action == "add_new_degree")
{
  display_add_new_degree();
}

if ($perform_action == "perform_add_new_degree")
{
  perform_add_new_degree();
}


if ($perform_action == "perform_edit_announcements")
{
  perform_edit_announcements();
}

if ($perform_action == "perform_edit_flightpath_settings")
{
  perform_edit_flightpath_settings();
}

if ($perform_action == "perform_edit_help")
{
  perform_edit_help();
}


if ($perform_action == "perform_edit_urgent_msg")
{
  perform_edit_urgent_msg();
}

if ($perform_action == "perform_edit_offline_mode")
{
  perform_edit_offline_mode();
}


if ($perform_action == "edit_flightpath_settings")
{
  display_edit_flightpath_settings();
}


if ($perform_action == "request_transfer")
{
  display_request_data_transfer();
}
if ($perform_action == "perform_request_transfer")
{
  perform_request_data_transfer();
}


if ($perform_action == "edit_degrees")
{
  display_edit_degrees();
}

if ($perform_action == "edit_groups")
{
  display_edit_groups();
}

if ($perform_action == "edit_courses")
{
  display_edit_courses();
}


if ($perform_action == "popup_add_group")
{
  popup_add_group();
}

if ($perform_action == "popup_show_group_use")
{
  popup_show_group_use();
}

if ($perform_action == "popup_degrees_using_course")
{
  popup_degrees_using_course();
}

if ($perform_action == "popup_groups_using_course")
{
  popup_groups_using_course();
}

if ($perform_action == "popup_students_using_course")
{
  popup_students_using_course();
}


if ($perform_action == "popup_edit_definition")
{
  popup_edit_definition();
}

if ($perform_action == "popup_select_icon")
{
  popup_select_icon();
}


if ($perform_action == "edit_specific_group")
{
  display_edit_specific_group();
}

if ($perform_action == "edit_specific_course")
{
  display_edit_specific_course();
}

if ($perform_action == "edit_specific_user")
{
  display_edit_specific_user();
}


if ($perform_action == "edit_specific_degree")
{
  display_edit_specific_degree();
}

if ($perform_action == "perform_edit_specific_degree")
{
  perform_edit_specific_degree();
}

if ($perform_action == "perform_edit_specific_group")
{
  perform_edit_specific_group();
}

if ($perform_action == "perform_edit_specific_course")
{
  perform_edit_specific_course();
}

if ($perform_action == "perform_edit_specific_user")
{
  perform_edit_specific_user();
}



if ($perform_action == "perform_set_catalog_year")
{
  $catalog_year = trim($_POST["catalog_year"]);
  $GLOBALS["de_catalog_year"] = $catalog_year;
  display_main_menu("<font color='green'>Catalog Year editing set to $catalog_year.</font><br>");
}



die;


function perform_clear_john_doe() {
  
  //////////////////////////////////
  // Check for hooks...
  $function = "admin_perform_clear_john_doe";
  if (function_exists($function)) {
    return call_user_func($function);
  }
  //////////////////////////////////
  if (!user_has_permission("de_can_administer_data_entry")) {
    display_access_denied();
  }
  
  
  
  // This function will clear the advising history for John Doe (99999999).
  // Clears advising comments, too.
  $cwid = "99999999";
  $db = new DatabaseHandler();
  $res = $db->db_query("SELECT * FROM advising_sessions
                       WHERE student_id = '?' ", $cwid);
  while ($cur = $db->db_fetch_array($res)) {
    $aid = $cur["advising_session_id"];
    $db->db_query("DELETE FROM advised_courses WHERE advising_session_id = '?' ", $aid);
  }
  
  $db->db_query("DELETE FROM advising_sessions WHERE student_id = '?' ", $cwid);
  $db->db_query("DELETE FROM advising_comments WHERE student_id = '?' ", $cwid);
  
  display_main_menu(get_success_msg("Advising history and comments for John Doe successfully cleared."));
  
}



function display_preview_announcement()
{

  ///////////////////////////////////
  // Check for hooks...
  $function = "admin_display_preview_announcement";
  if (function_exists($function)) {
    return call_user_func($function);
  }   
  ///////////////////////////////////
  
  
  // This function is intended to display within a popup.  It will
  // display an announcement exactly as it will appear in FP.
  $ann = $_REQUEST["announcement"];
  $ann = urldecode($ann);

  $screen = new AdvisingScreen();
  $screen->page_is_popup = true;

  $ann = $screen->convert_b_b_code_to_h_t_m_l($ann);

  $pC .= "<table cellpadding='0' cellspacing='0' width='315'>
			<tr><td valign='top'>";

  // Display announcements here...
  $pC .= $screen->draw_curved_title("Preview Announcement...");
  $pC .= "<div class='elevenpt' style='margin-top: 20px;'>$ann
							<div align='right' class='tenpt' style='color: gray; padding-right: 10px;'>
							<i>Posted xxxx-xxxx-xxx</i>
							</div>
						</div>";

  $pC .= "</td></tr>
			</table>";



  $screen->page_content = $pC;
  $screen->output_to_browser();


}


function display_edit_users($msg = "")
{
  
  //////////////////////////////////
  // Check for hooks...
  $function = "admin_display_edit_users";
  if (function_exists($function)) {
    return call_user_func($function, $msg);
  }
  //////////////////////////////////
  if (!user_has_permission("de_can_administer_users")) {
    display_access_denied();
  }
  
  
  global $db, $screen;
  $de_catalog_year = $GLOBALS["de_catalog_year"];

  $cc = 1;

  $pC = "";


  // First, let's get our list of departments...
  $dept_array = array();
  $d = 0;
  
  // Let's pull the needed variables out of our settings, so we know what
	// to query, because this is a non-FlightPath table.
	$tsettings = $GLOBALS["fp_system_settings"]["extra_tables"]["human_resources:faculty_staff"];
	$tf = (object) $tsettings["fields"];  //Convert to object, makes it easier to work with.  
	$table_name = $tsettings["table_name"];    
  
  $res = $db->db_query("select distinct `$tf->dept_name` from $table_name ORDER BY `$tf->dept_name` ");
  if ($res)
  {
    while ($cur = $db->db_fetch_array($res))
    {
      if (trim($cur["$tf->dept_name"]) == "")
      {// skip if blank
        continue;
      }
      $dept_array[$d] = trim(ucwords(strtolower($cur["$tf->dept_name"])));
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
	$tsettings = $GLOBALS["fp_system_settings"]["extra_tables"]["human_resources:faculty_staff"];
	$tf = (object) $tsettings["fields"];  //Convert to object, makes it easier to work with.  
	$table_name = $tsettings["table_name"];  
  
  
  if ($search != "" && !($_GET["deptsubmit"]))
  {
    // Something was searched for, and the dept submit button was not pushed.
    $dept = "";
    $temp = split(" ",$search);
    $search1 = $temp[0];
    $search2 = trim($temp[1]);

    $_SESSION["prev_user_search"] = "search%%$search";
    $displaying = $search;
    $second_part = "";
    if ($search2 != "")
    {
      // Two search terms, probably a name...
      $result = $db->db_query("SELECT * FROM $table_name
					WHERE  
					($tf->l_name LIKE '%?%'
					AND $tf->f_name LIKE '%?%')
					ORDER BY $tf->l_name, $tf->f_name ", $search2, $search1);

    }else {

      // One search term....
      $result = $db->db_query("SELECT * FROM $table_name
					WHERE $tf->faculty_id LIKE '%?%' 
					OR $tf->l_name LIKE '%?%'
					OR $tf->f_name LIKE '%?%'  
					ORDER BY $tf->l_name, $tf->f_name ", $search1, $search1, $search1);
    }

  }
  else if ($dept != "" && $_GET["deptsubmit"])
  {
    // User select a department.  Look for it...
    $search = "";
    $_SESSION["prev_user_search"] = "dept%%$dept";
    $result = $db->db_query("SELECT * FROM $table_name
					WHERE $tf->dept_name = '?' 
					ORDER BY $tf->l_name, $tf->f_name ", $dept);
    $displaying = $dept;
  }
  else
  { // No search, so look for the range...
    $result = $db->db_query("SELECT * FROM $table_name
                        WHERE 
                        $tf->l_name BETWEEN '?' AND '?'
                        ORDER BY $tf->l_name, $tf->f_name ", $ur, $lr);
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
  while ($cur = $db->db_fetch_array($result))
  {

    $l_name = trim(ucwords(strtolower($cur[$tf->l_name])));
    $f_name = trim(ucwords(strtolower($cur[$tf->f_name])));
    $mid_name = trim(ucwords(strtolower($cur[$tf->mid_name])));
    $faculty_id = trim($cur[$tf->faculty_id]);
    $dept_name = trim(ucwords(strtolower($cur[$tf->dept_name])));
        
    // Now, we find out this person's user type...
    $user_type = determine_staff_user_type($faculty_id);


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
					onClick='window.location=\"admin.php?performAction=editSpecificUser&faculty_id=$faculty_id&user_type=$user_type\";'
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
  for ($t = 0; $t<count($dept_array); $t++)
  {
    $dd = $dept_array[$t];

    $sel = "";
    if ($dd == $dept)
    {
      $sel = "selected";
    }
    $bC .= "<option value='$dd' $sel>{$dept_array[$t]}</option> \n";
  }
  $pC = str_replace("<!--DEPTSEARCH-->",$bC,$pC);


  $screen->page_title = "FlightPath Admin - Users";
  $screen->page_hide_report_error = true;
  $screen->page_content = $pC;
  // send to the browser
  $screen->output_to_browser();



}


function perform_apply_draft_changes()
{
  
  //////////////////////////////////
  // Check for hooks...
  $function = "admin_perform_apply_draft_changes";
  if (function_exists($function)) {
    return call_user_func($function);
  }
  //////////////////////////////////
  if (!user_has_permission("de_can_administer_data_entry")) {
    display_access_denied();
  }
  
  
  // This function will "apply" the changes in the draft tables
  // to the production tables.
  global $db;
  // Check to make sure they entered the transfer passcode correctly.
  if ($_POST["passcode"] != $GLOBALS["fp_system_settings"]["admin_transfer_passcode"])
  {
    display_apply_draft_changes("<font color='red'>ERROR.  Transfer passcode incorrect.  Check with the FlightPath administrator
								to learn the passcode.</font>");
    die;

  }

  // Save the entire post to the log.
  $post_x_m_l = fp_array_to_xml("post",$_POST, true);
  $db->add_to_log("admin_perform_apply_draft_changes","begin",$post_x_m_l);


  // First, set maintenance mode...
  $db->set_maintenance_mode("1");

  // Okay, so what we gotta do is truncate the production tables,
  // then copy the draft tables in.
  $table_array = array(
  "courses",
  "degree_requirements",
  "degree_tracks",
  "degrees",
  "group_requirements",
  "groups",
  );

  foreach($table_array as $table_name)
  {
    $draft_table_name = "draft_$table_name";
    $db->add_to_log("admin_perform_apply_draft_changes","$table_name,$draft_table_name");
    // First, truncate existing...
    $query = "truncate table $table_name";
    $res = $db->db_query($query);
    // Now, copy in draft changes...
    $query = "INSERT INTO $table_name
						SELECT * FROM $draft_table_name ";
    $res = $db->db_query($query);
  }

  $db2 = new DatabaseHandler();
  // Now, we need to go through the draft_instructions table,
  // and perform each instruction one at a time.
  $res = $db->db_query("SELECT * FROM draft_instructions
						ORDER BY `id` ");
  while($cur = $db->db_fetch_array($res))
  {
    $instruction = trim($cur["instruction"]);
    $db2->add_to_log("admin_perform_apply_draft_changes",$instruction);

    $temp = explode(",",$instruction);

    if (trim($temp[0]) == "update_course_id")
    {
      $db2->update_course_id(trim($temp[1]), trim($temp[2]));
    }

    if (trim($temp[0]) == "update_course_requirement_from_name")
    {
      $db2->update_course_requirement_from_name(trim($temp[1]), trim($temp[2]), trim($temp[3]));
    }
  }

  // Once this is done, truncate the draft_instructions table.
  $res = $db->db_query("TRUNCATE TABLE draft_instructions");


  // And we are done!  Set maintenance mode back to 0.
  $db->set_maintenance_mode("0");
  $db->add_to_log("admin_perform_apply_draft_changes","finished");

  // Send emails to notify programmers...
  $notify = $GLOBALS["fp_system_settings"]["notify_apply_draft_changes_email_address"];
  if ($notify)
  {
    mail($notify, "FlightPath Apply Draft Changes", "Someone has applied draft changes to FlightPath, which updated degree plans, groups, and courses.");
  }
  // Send us back to the ApplyDraftChanges screen...
  display_apply_draft_changes(get_success_msg("Successfully updated the production database with draft changes at " . get_current_time() . ". Your changes are now live and visible on production."));

}



function perform_edit_help()
{
  
  //////////////////////////////////
  // Check for hooks...
  $function = "admin_perform_edit_help";
  if (function_exists($function)) {
    return call_user_func($function);
  }
  //////////////////////////////////
  if (!user_has_permission("de_can_administer_data_entry")) {
    display_access_denied();
  }
  
  
  global $db;
  $page_id = trim($_POST["page_id"]);
  if ($page_id == "new")
  {
    // Add a new page to the help system...
    $res = $db->db_query("INSERT INTO help(`title`)
								values ('') ");
    $page_id = $db->db_insert_id();
    $_POST["page_id"] = $page_id;
  }

  // Save the entire post to the log.
  $post_x_m_l = fp_array_to_xml("post",$_POST, true);
  $db->add_to_log("admin_edit_help","$page_id",$post_x_m_l);


  $title = trim($_POST["title"]);
  $body = trim($_POST["body"]);

  $res = $db->db_query("UPDATE help
							SET `title`='?',
								`body`='?'
							WHERE `id`='?' ", $title, $body, $page_id);
  display_edit_help(get_success_msg("Successfully updated Help page at " . get_current_time()));

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
  $res = $db->db_query("SELECT * FROM administrators WHERE `faculty_id`='$userID' ");
  if ($db->db_num_rows($res) == 0)
  {
    $msg = "<div style='color:red'>You do not have access to Data Entry.  Only System Administrators
							may log in.</div>";
    displayLogin($msg);
    die;
  }

  // If we are here, then we have access.  Log us on in.
  $_SESSION["fpDataEntryLoggedIn"] = true;
  $db->add_to_log("admin_login");
  display_main_menu();
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
  $db->add_to_log("admin_logout");
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

  
  $screen->page_content = $pC;
  $screen->pageHasSearch = false;
  $screen->pageOnLoad = "document.getElementById(\"cwid_box\").focus(); ";
  // send to the browser
  $screen->output_to_browser();


}
*/


function display_edit_specific_user($msg = "")
{
  
  //////////////////////////////////
  // Check for hooks...
  $function = "admin_display_edit_specific_user";
  if (function_exists($function)) {
    return call_user_func($function, $msg);
  }
  //////////////////////////////////
  if (!user_has_permission("de_can_administer_users")) {
    display_access_denied();
  }
  
  global $screen, $db;
  $pC = "";

  $pC .= "<a class='tenpt' href='admin.php?performAction=editUsers'>Back to Users list</a>
			&nbsp; &nbsp; - &nbsp; &nbsp;
		<a class='tenpt' href='admin.php'>Back to main menu</a>
			$msg";


  $faculty_id = trim($_REQUEST["faculty_id"]);
  $user_type = trim($_REQUEST["user_type"]);
  //$myurl = trim($_GET["myurl"]);

  // Get faculty member details...
  
  // Let's pull the needed variables out of our settings, so we know what
	// to query, because this is a non-FlightPath table.
	$tsettings = $GLOBALS["fp_system_settings"]["extra_tables"]["human_resources:faculty_staff"];
	$tf = (object) $tsettings["fields"];  //Convert to object, makes it easier to work with.  
	$table_name = $tsettings["table_name"];     
  
  $result = $db->db_query("SELECT * FROM $table_name
                        WHERE 
                        $tf->faculty_id = '?' ", $faculty_id) ;
  $cur = $db->db_fetch_array($result);

  $l_name = trim(ucwords(strtolower($cur[$tf->l_name])));
  $f_name = trim(ucwords(strtolower($cur[$tf->f_name])));
  $mid_name = trim(ucwords(strtolower($cur[$tf->mid_name])));
  $dept_name = trim(ucwords(strtolower($cur[$tf->dept_name])));


  $advisees = "";


  // Get the list of advisees.
  
  // Let's pull the needed variables out of our settings, so we know what
	// to query, because this is a non-FlightPath table.
	$tsettings = $GLOBALS["fp_system_settings"]["extra_tables"]["human_resources:advisor_student"];
	$tfa = (object) $tsettings["fields"];  //Convert to object, makes it easier to work with.  
	$table_name_a = $tsettings["table_name"];     
  
	$tsettings = $GLOBALS["fp_system_settings"]["extra_tables"]["human_resources:students"];
	$tfb = (object) $tsettings["fields"];  //Convert to object, makes it easier to work with.  
	$table_name_b = $tsettings["table_name"];     

	$res = $db->db_query("SELECT * FROM $table_name_a a, $table_name_b b
						WHERE a.$tfa->faculty_id = '?' 
						AND a.$tfa->student_id = b.$tfb->student_id
							ORDER BY $tfb->major_code, $tfb->l_name, $tfb->f_name
						", $faculty_id);
  while ($cur2 = $db->db_fetch_array($res))
  {
    $name = ucwords(strtolower($cur2[$tfb->f_name] . " " . $cur2[$tfb->l_name]));
    $advisees .= trim($cur2[$tfb->student_id]) . " {$cur2[$tfb->major_code]}   $name \n";
  }




  //$pC .= draw_curved_title("Edit User");

  //$catname = getCatalogName();

  $sel[$user_type] = "selected";
  //{$sel["none"]}
  $pC .= "
         
     <form action='admin.php' method='POST' style='margin-top: 5px;'>
     <input type='hidden' name='performAction' value='perform_edit_specific_user'>
     

	User: &nbsp; <b>$f_name $mid_name $l_name ($faculty_id)</b>
   &nbsp; &nbsp; &nbsp; &nbsp; Department: &nbsp; <b>$dept_name</b>
   <br>
   Current user type: <b>$user_type</b>
    
   
   <input type='hidden' name='faculty_id' value='$faculty_id'>
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
  $all_perms = get_modules_permissions();
    
  foreach ($all_perms as $module => $val) {    
    $module_name = $GLOBALS["fp_system_settings"]["modules"][$module]["name"];
    if (!$module_name) $module_name = $module;
    
    $pC .= "<div class='fp-user-management-permission-module-name'>$module_name</div>";
    
    foreach ($all_perms[$module] as $perm_data) {
    
      foreach ($perm_data as $perm_name => $perm_values) {
        $checked = $cval = " ";
        
        if (user_has_permission($perm_name, $faculty_id)) {
          $checked = "checked";
          $cval = "X";
        }
          
        // Does the user editing this user have this permission?  If not,
        // they may not grant it for others!
        if (user_has_permission($perm_name)) {
          $pC .= "<div class='fp-user-management-permission'>
                  <label><input type='checkbox' name='perm~~_~~$perm_name' value='yes' $checked>{$perm_values["title"]}</label>
                  <div class='fp-user-management-permission-line'>{$perm_values["description"]}</div>
                  </div>";       
        }
        else {
          
          $pC .= "<div class='fp-user-management-permission fp-user-management-permission-disabled'>
                  <div class='fp-user-management-permission-disabled-msg'>
                    You may not edit this permission for this user, as you do
                    not have this permission yourself.
                  </div>
                  <label>[$cval] {$perm_values["title"]}</label>
                  <div class='fp-user-management-permission-line'>{$perm_values["description"]}</div>
                  </div>";       
          
        }
        
      }
      
    }    
  }
  
  /*
  $pciChecked = "";
  $res = $db->db_query("SELECT * FROM users WHERE
						`faculty_id`='$faculty_id' ");
  $cur = $db->db_fetch_array($res);
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

  $pC .= get_j_s();


  $screen->page_title = "FlightPath Admin - Edit User";
  $screen->page_hide_report_error = true;
  $screen->page_content = $pC;
  // send to the browser
  $screen->output_to_browser();


}



function display_edit_specific_course($msg = "", $bool_scroll = true)
{
  
  //////////////////////////////////
  // Check for hooks...
  $function = "admin_display_edit_specific_course";
  if (function_exists($function)) {
    return call_user_func($function, $msg, $bool_scroll);
  }
  //////////////////////////////////
  if (!user_has_permission("de_can_administer_data_entry")) {
    display_access_denied();
  }
  
  
  global $screen, $db;
  $de_catalog_year = $GLOBALS["de_catalog_year"];
  $course_id = $_REQUEST["course_id"];
  $subject_id = $_REQUEST["subject_id"];
  $course_num = $_REQUEST["course_num"];

  $subject_id = str_replace("_A_","&",$subject_id);

  $pC .= "<a class='tenpt' href='admin.php?performAction=editCourses&de_catalog_year=$de_catalog_year#course_$course_id'>Back to Course List</a>  &nbsp; - &nbsp;
			<a class='tenpt' href='admin.php?deCatalogYear=$de_catalog_year'>Back to main menu.</a>
			";
  if ($_SESSION["de_advanced_mode"] == true)
  {
    $pC .= " <span class='tenpt' style='background-color: yellow; margin-left: 20px;'>
					adv: course_id = $course_id. Used by:
					<a href='javascript: popupWindow(\"admin.php?performAction=popup_degrees_using_course&course_id=$course_id\")'>[degrees]</a>
					<a href='javascript: popupWindow(\"admin.php?performAction=popup_groups_using_course&course_id=$course_id\")'>[groups]</a>
					<a href='javascript: popupWindow(\"admin.php?performAction=popup_students_using_course&course_id=$course_id\")'>[students]</a>
				  </span>";
  }

  $course = new Course($course_id,false,null,false,$de_catalog_year, true);
  //admin_debug($course->description);
  $course->catalog_year = $de_catalog_year;  // Since it may be 1900, force it!
  $course->load_descriptive_data(false, true, false, true, true);



  $pC .= "<h2>Edit Course $subject_id $course_num ($de_catalog_year)</h2>$msg";

  $pC .= "<form id='mainform' action='admin.php' method='POST'>
			<input type='hidden' name='performAction' value='perform_edit_specific_course'>
			<input type='hidden' name='course_id' value='$course_id'>
			
			<input type='hidden' name='subject_id' value='$subject_id'>
			<input type='hidden' name='course_num' value='$course_num'>
			
			";
  $course->catalog_year = $de_catalog_year;  // Since it may be 1900, force it!
  $pC .= get_hidden_variables();
  $all_names = $course->getAll_names(true);
  $warn_eqv = "";
  if (strstr($all_names, ","))
  {
    $warn_eqv = "yes";
  }
  
  // Correct ghosthours, if they exist.
  if ($course->bool_ghost_hour) {
    $course->max_hours = 0;
  }
  if ($course->bool_ghost_min_hour) {
    $course->min_hours = 0;
  }
  
  
  
  $pC .= "<table border='0' cellspacing='5'>
			<tr>
				<td valign='top' class='tenpt'>
					Course name(s):
				</td>
				<td valign='top' class='tenpt'>
					<input type='text' name='courseNames' value='$all_names' size='60'>
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
					<input type='text' name='min_hours' value='$course->min_hours' size='5'>
					<a href='javascript: popupAlertHelp(\"course_min_hours\");'>?</a> 	
				</td>
			</tr>
			<tr>
				<td valign='top' class='tenpt'>
					Max hours:
				</td>
				<td valign='top' class='tenpt'>
					<input type='text' name='max_hours' value='$course->max_hours' size='5'>
					<a href='javascript: popupAlertHelp(\"course_max_hours\");'>?</a> 	
				</td>
			</tr>
			<tr>
				<td valign='top' class='tenpt'>
					Repeat hours:
				</td>
				<td valign='top' class='tenpt'>
					<input type='text' name='repeat_hours' value='$course->repeat_hours' size='5'>
					<a href='javascript: popupAlertHelp(\"course_repeat_hours\");'>?</a> 	
				</td>
			</tr>
			<!--
			<tr>
				<td valign='top' class='tenpt'>
					Exclude:
				</td>
				<td valign='top' class='tenpt'>
					<input type='text' name='exclude' value='$course->db_exclude' size='2'>
					<a href='javascript: popupAlertHelp(\"course_exclude\");'>?</a> [Default = 0]
				</td>
			</tr>
			-->
			</table>
			<div class='tenpt'>Description:
			<textarea name='description' rows='4' cols='80'>$course->description</textarea>			
			<br>
			<input type='button' value='Save for $de_catalog_year' onClick='submitForm();'>
			   <input type='checkbox' name='allYears' value='yes'> Update all years for this course. 
			       <a href='javascript: popupAlertHelp(\"all_years\");'>?</a>
			<br><br>
			<b>[Optional]</b> Comment: (only seen by data entry administrators)<br>
			<textarea name='data_entry_comment' rows='3' cols='80'>$course->data_entry_comment</textarea>
			<br>
				<div align='right'>
					Delete this course for $de_catalog_year? <input type='button' value='X'
									onClick='deleteCourse(\"$course_id\",\"$de_catalog_year\",\"$warn_eqv\");'>
				</div>			
			</div>
			
			
			";

  $pC .= "
			</form>";

  $pC .= get_j_s();
  $screen->page_title = "FlightPath Admin - Edit Course";

  if ($bool_scroll)
  {
    $screen->page_scroll_top = trim($_POST["scroll_top"]);
  }
  $screen->page_hide_report_error = true;
  //include("template/fp_template.php");

  $screen->page_content = $pC;
  // send to the browser
  $screen->output_to_browser();


  if ($_REQUEST["serialize"] != "")
  {
    print "<br><textarea rows=20 cols=80>" . serialize($course) . "</textarea>";
  }


}


function display_edit_courses($msg = "")
{
  //////////////////////////////////
  // Check for hooks...
  $function = "admin_display_edit_courses";
  if (function_exists($function)) {
    return call_user_func($function, $msg);
  }
  //////////////////////////////////
  if (!user_has_permission("de_can_administer_data_entry")) {
    display_access_denied();
  }
  
  
  global $db, $screen;
  $de_catalog_year = $GLOBALS["de_catalog_year"];

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


  $pC .= "<a class='tenpt' href='admin.php?deCatalogYear=$de_catalog_year'>Back to main menu.</a><br>
			<h2>Edit Courses for $de_catalog_year</h2>$msg
			";

  $pC .= "<div style='background-color: beige; margin-bottom:10px; text-align: center; padding: 3px;'>
			<a href='admin.php?deCatalogYear=$de_catalog_year&performAction=editCourses&ur=A&lr=AZZZ'>A</a> &nbsp;
						<a href='admin.php?deCatalogYear=$de_catalog_year&performAction=editCourses&ur=B&lr=BZZZ'>B</a> &nbsp; 
                       <a href='admin.php?deCatalogYear=$de_catalog_year&performAction=editCourses&ur=C&lr=CNZZ'>C-CN</a> &nbsp; 
                       <a href='admin.php?deCatalogYear=$de_catalog_year&performAction=editCourses&ur=CO&lr=CZZZ'>CO-CZ</a> &nbsp; 
                       <a href='admin.php?deCatalogYear=$de_catalog_year&performAction=editCourses&ur=D&lr=DZZZ'>D</a> &nbsp; 
                       <a href='admin.php?deCatalogYear=$de_catalog_year&performAction=editCourses&ur=E&lr=EZZZ'>E</a> &nbsp; 
                       <a href='admin.php?deCatalogYear=$de_catalog_year&performAction=editCourses&ur=F&lr=FZZZ'>F</a> &nbsp; 
                       <a href='admin.php?deCatalogYear=$de_catalog_year&performAction=editCourses&ur=G&lr=GZZZ'>G</a> &nbsp; 
                       <a href='admin.php?deCatalogYear=$de_catalog_year&performAction=editCourses&ur=H&lr=HZZZ'>H</a> &nbsp; 
                       <a href='admin.php?deCatalogYear=$de_catalog_year&performAction=editCourses&ur=I&lr=LZZZ'>I-L</a> &nbsp; 
                       <a href='admin.php?deCatalogYear=$de_catalog_year&performAction=editCourses&ur=M&lr=MRZZZ'>M-MR</a> &nbsp; 
                       <a href='admin.php?deCatalogYear=$de_catalog_year&performAction=editCourses&ur=MS&lr=MZZZ'>MS-MZ</a> &nbsp; 
                       <a href='admin.php?deCatalogYear=$de_catalog_year&performAction=editCourses&ur=N&lr=OZZZ'>N-O</a> &nbsp; 
                       <a href='admin.php?deCatalogYear=$de_catalog_year&performAction=editCourses&ur=P&lr=PZZZ'>P</a> &nbsp; 
                       <a href='admin.php?deCatalogYear=$de_catalog_year&performAction=editCourses&ur=Q&lr=RZZZ'>Q-R</a> &nbsp; 
                       <a href='admin.php?deCatalogYear=$de_catalog_year&performAction=editCourses&ur=S&lr=SOZZZ'>S-SO</a> &nbsp;
                       <a href='admin.php?deCatalogYear=$de_catalog_year&performAction=editCourses&ur=SP&lr=SPZZZ'>SP-SZ</a> &nbsp;
                       <a href='admin.php?deCatalogYear=$de_catalog_year&performAction=editCourses&ur=T&lr=ZZZZZ'>T-Z</a>
				</div>                       
        <div class='tenpt'>			
                       Missing a course?  <a href='admin.php?deCatalogYear=$de_catalog_year&performAction=editSpecificCourse&course_id=new'>Click Here to Add a Course</a><br>
                       
                       Legend:  <br>&nbsp; &nbsp;[e] = Course has at least one add'l excluded name.  
                       			&nbsp; &nbsp;[v] = Course has at least one add'l valid name.
                       			
         </div>

         ";

  $exclude_line = "and exclude != 1";
  if ($show_hidden == "yes")
  {
    $pC .= "<div class='tenpt'><b>Showing excluded courses.
                  <a href='admin.php?deCatalogYear=$de_catalog_year&performAction=editCourses&show_hidden=no'>Hide?</a>
        </b></div>";
    $exclude_line = "";

  }  else {
    $pC .= "<div class='tenpt hypo'><b>Hiding excluded courses.
              <a href='admin.php?deCatalogYear=$de_catalog_year&performAction=editCourses&show_hidden=yes'>Show?</a>
              </b></div>";
  }

  $pC .= "<hr><br>
          <table border='0' cellpadding='3' cellspacing='0'>";



  $q = "SELECT * FROM draft_courses
                        WHERE 
                        `catalog_year`='?' and
                        `subject_id` BETWEEN '?' AND '?'
                        AND `delete_flag`='0'
                        $exclude_line
                        ORDER BY `subject_id`, `course_num`";
  $result = $db->db_query($q, $de_catalog_year, $ur, $lr);
  while ($cur = $db->db_fetch_array($result))
  {
    extract($cur, 3, "db");

    $ex_names = "";
    $val_names = "";
    // Check to see if this course has more than one name...
    // removed AND `catalog_year`='$de_catalog_year' from query,
    // because we don't care what other cat year it came from.
    $res2 = $db->db_query("SELECT * FROM draft_courses
									WHERE `course_id`='?'
										", $db_course_id);
    while ($cur2 = $db->db_fetch_array($res2))
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
        $ex_names = "[e]";
      } else {
        $val_names = "[v]";
      }

    }


    $spanstyle = "";

    if ($db_exclude == "1")
    {
      $spanstyle = "background-color: lightgrey;";
    }

    $temp_course = new Course();
    $db_title = $temp_course->fix_title($db_title);

    $hrs = $db_min_hours;
    if (trim($db_min_hours) != trim($db_max_hours))
    {
      $hrs .= " - $db_max_hours";
    }

    $hrs .= " hrs.";

    $rep_hours = "";
    if ($db_repeat_hours > $db_min_hours)
    {
      $rep_hours = " rep to $db_repeat_hours hrs.";
    }

    // remove special chars from subject_id...
    $display_subject_id = $db_subject_id;
    $db_subject_id = str_replace("&","_A_",$db_subject_id);


    $pC .= "<tr style='$spanstyle'>
					<td valign='top' width='90%'><a name='course_$db_course_id'></a>";
    $pC .= "<div style='$spanstyle padding:3px;'><a href='admin.php?performAction=editSpecificCourse&course_id=$db_course_id&subject_id=$db_subject_id&course_num=$db_course_num&de_catalog_year=$de_catalog_year'>$display_subject_id $db_course_num - $db_title</a> - $hrs$rep_hours</div>";

    $pC .= "</td>
					<td valign='top' width='5%'>
					$ex_names
					</td>
					
					<td valign='top' width='5%'>
					$val_names
					</td>
				</tr>";

  } // while

  $pC .= "</table>";


  $screen->page_title = "FlightPath Admin - Courses";
  $screen->page_hide_report_error = true;
  //include("template/fp_template.php");

  $screen->page_content = $pC;
  // send to the browser
  $screen->output_to_browser();


}


function perform_edit_specific_course()
{
  //////////////////////////////////
  // Check for hooks...
  $function = "admin_perform_edit_specific_course";
  if (function_exists($function)) {
    return call_user_func($function);
  }
  //////////////////////////////////
  if (!user_has_permission("de_can_administer_data_entry")) {
    display_access_denied();
  }
  
  
  global $db;
  $de_catalog_year = $GLOBALS["de_catalog_year"];
  $course_id = trim($_REQUEST["course_id"]);
  $course_names = trim($_POST["course_names"]);

  if ($course_names == "")
  {
    $course_names = $_POST["subject_id"] . " " . $_POST["course_num"];
  }

  $title = trim($_POST["title"]);
  $min_hours = trim($_POST["min_hours"]);
  $max_hours = trim($_POST["max_hours"]);
  $repeat_hours = trim($_POST["repeat_hours"]);
  //$exclude = trim($_POST["exclude"]);
  $description = trim($_POST["description"]);
  $data_entry_comment = trim($_POST["data_entry_comment"]);

  // Save the entire post to the log.
  $post_x_m_l = fp_array_to_xml("post",$_POST, true);
  $db->add_to_log("admin_edit_course","$course_id,$course_names",$post_x_m_l);

  // Since we are making a change to the draft table(s), let's add a row
  // to draft instructions.
  $db->add_draft_instruction("-");



  // Unlike the degrees and the groups, course_ids are not
  // unique.  Only a course_id + catalog_year pair are unique.  This
  // is so we can handle equivalent courses more gracefully.

  // So, the first thing we need to do is delete *all* courses with the
  // course_id and catalog_year listed above.  For most courses, this will
  // only be one row.  For eqv courses, this will delete more than one row.
  if ($course_id != "new")
  {
    // Don't delete!  Temporarily transfer to a temporary course_id.
    // Will possibly delete later.

    
    
    $res = $db->db_query("UPDATE draft_courses
				  SET `course_id`='-12345'
				  WHERE `course_id`='?'
				AND `catalog_year`='?' ", $course_id, $de_catalog_year);
  }


  if ($_POST["perform_action2"] == "delete_course")
  {
    // That's it.  All we wanted to do was delete the course.
    $query = "DELETE FROM draft_courses
				  WHERE `course_id`='-12345'
					";
    //debug_c_t($query);
    $res = $db->db_query($query);
    display_edit_courses("<div><font color='green' size='4'>Course <i>$course_names</i> successfully deleted for $de_catalog_year.</font></div>");
    die;
  }

  // If the $course_id == new then create a new one.
  if ($course_id == "new")
  {
    $course_id = $db->request_new_course_id();
    $_POST["course_id"] = $course_id;
    $_GET["course_id"] = $course_id;
    $_REQUEST["course_id"] = $course_id;
    //debugCT("new course ID is $course_id. courseNames: [$course_names" . "]");
  }




  // Now, we will split the courseNames on commas, and for each
  // token, we will insert a row into the database.
  $courses = split(",", $course_names);
  foreach($courses as $course)
  {
    $course = str_replace("  ", " ", $course);
    $course = str_replace("  ", " ", $course);
    $course = str_replace("  ", " ", $course);
    $course = trim($course);
    if ($course == "") { continue; }

    $temp = split(" ", $course);
    $subject_id = trim($temp[0]);
    $course_num = trim($temp[1]);

    ////////////
    ///  Error conditions...
    if (strtolower($course_num) == "exclude")
    {
      $errors .= "<div style='color:red;'>
						It appears you specified an excluded course
						without a course number.  You entered <b>$subject_id $course_num</b>.
						Notice there is no course number. Please re-enter.
						</div>";
      continue;
    }

    if ($course_num == "")
    {
      $errors .= "<div style='color:red;'>
						It appears you specified a course
						without a course number.  You entered <b>$subject_id $course_num</b>.
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
      // Set ALL courses with this subject_id and course_num to exclude!
      $res = $db->db_query("UPDATE draft_courses
								SET `exclude`='1'
								WHERE `subject_id`='?'
								AND `course_num`='?' 
								", $subject_id, $course_num);


    } else {
      // Aet all courses with this subject_id and course_num to NOT exclude!
      $res = $db->db_query("UPDATE draft_courses
								SET `exclude`='0'
								WHERE `subject_id`='?'
								AND `course_num`='?' 
								", $subject_id, $course_num);

    }

    // Did the user specify a course which already exists?  If so,
    // mark that course's ID as -12345...
    $res = $db->db_query("UPDATE draft_courses
								SET `course_id`='-12345'
								WHERE `subject_id`='?'
								AND `course_num`='?' 
								AND `catalog_year`='?' ", $subject_id, $course_num, $de_catalog_year);



    // We now have enough information to make an insertion into
    // the table.
    $query = "INSERT INTO draft_courses
					(`course_id`,`subject_id`,`course_num`,`catalog_year`,
						`title`,`description`,`min_hours`,`max_hours`,`repeat_hours`,
						`exclude`,`data_entry_comment`)
						values ('?','?','?','?','?','?','?','?','?','?','?') ";
    //debug_c_t($query);
    $res = $db->db_query($query, $course_id,$subject_id,$course_num,$de_catalog_year,
						$title,$description,$min_hours,$max_hours,$repeat_hours,
						$exclude,$data_entry_comment);

    // Now, this part is tricky.  Are there any courses which already
    // existed with this subject_id and course_num, but not this course_id?
    // This would happen if we add an eqv for a course that already existed
    // elsewhere.  We want to change that existing course's ID to match the
    // new one, but we also need to update EVERY table that used the old
    // course_id with the new course_id, including degree plans, groups,
    // substitutions, etc.

    // query for subject_id and course_num but != course_id.
    // get oldCourseID.
    // call function update_course_id(oldCourseID, newCourseID)
    $res2 = $db->db_query("SELECT * FROM draft_courses WHERE
								`subject_id`='?'
								AND `course_num`='?'
								AND `course_id` != '?' 
								AND `course_id` != '-12345' ", $subject_id, $course_num, $course_id);
    while ($cur2 = $db->db_fetch_array($res2))
    {
      $old_course_id = $cur2["course_id"];
      // Now, update all the existing references to $old_course_id
      // with the new course_id.
      $db2 = new DatabaseHandler();
      $db2->update_course_id($old_course_id, $course_id, true);
      // Now, add it to our list of things to update when we apply
      // the draft changes...
      $db2->add_draft_instruction("update_course_id,$old_course_id,$course_id");
    }





  }

  // We have to accomodate the situation that there used to be an
  // eqv set up (multiple course names were set) but now there is not.
  // In other words, someone wanted to undo an eqv.
  // We used to have:  ACCT 101, MATH 101
  // But they took out the comma.  So, only ACCT 101 just got written
  // to the database, while MATH 101 has been marked as -12345 and is
  // destined to be deleted.
  // -- we need to give MATH 101 a new course_id and update that course_id
  // for all years.
  // Then, we need to go through all our tables and update where it was
  // actually spelled out that "MATH 101" be used with the new course_id.
  // -- This process will ensure that no previous existing courses
  // will get deleted.  That they will remain as their own unique
  // courses.

  // First thing's first.  Go through all the courses with the course_id
  // of -12345.  If we find one that does not have the same subject_id
  // and course_num with the new ID, then this is a removed eqv, and
  // that is our cue that it should be it's own course.
  $res = $db->db_query("SELECT * FROM draft_courses
							WHERE `course_id`='-12345' ");
  while ($cur = $db->db_fetch_array($res))
  {
    $found_s_i = $cur["subject_id"];
    $found_c_n = $cur["course_num"];
    $db2 = new DatabaseHandler();
    $res2 = $db2->db_query("SELECT * FROM draft_courses
							WHERE `course_id`='?'
							AND `subject_id`='?'
							AND `course_num`='?' 
							AND `catalog_year`='?' ", $course_id, $found_s_i, $found_c_n, $de_catalog_year);
    if ($db2->db_num_rows($res2) == 0)
    {
      // Meaning, this course name is not listed with the course_id,
      // so this is a broken eqv.
      // We should make a new course_id and all that for this course,
      // for all available years.
      //debugCT("removed eqv: $found_s_i $found_c_n");
      $new_course_id = $db2->request_new_course_id();
      $db3 = new DatabaseHandler();
      $res3 = $db3->db_query("UPDATE draft_courses
									SET `course_id`='?'
									WHERE `subject_id`='?'
									AND `course_num`='?' ", $new_course_id, $found_s_i, $found_c_n);
      // removed WHERE `course_id`='-12345' from query.  We want to UPDATE
      // this across all years for this course.
      // And also UPDATE every other table that specified foundSI &CN
      // as a requirement.
      $db3->update_course_requirement_from_name($found_s_i, $found_c_n, $new_course_id);
      $db3->add_draft_instruction("update_course_requirement_from_name,$found_s_i,$found_c_n,$new_course_id");
    }
  }





  // Was the "all_years" box checked?  If it was, then update all instances
  // of this course, across all available catalog years.
  if ($_POST["all_years"] == "yes")
  {
    $res = $db->db_query("UPDATE draft_courses
									SET `title`='?',
									`description`='?',
									`min_hours`='?',
									`max_hours`='?',
									`repeat_hours`='?'
									WHERE `course_id`='?' ", $title, $description, $min_hours, $max_hours, $repeat_hours, $course_id);
  }



  // Clean up.  Delete the temporary course_id...

  $query = "DELETE FROM draft_courses
				  WHERE `course_id`='-12345'
					";
  //debug_c_t($query);
  $res = $db->db_query($query);




  $msg = "<font color='green' size='4'>Course updated successfully at " . get_current_time() . ".</font>";

  if ($errors != "")
  {
    $msg = $errors;
  }


  display_edit_specific_course($msg);
}


function perform_edit_specific_user()
{
  
  //////////////////////////////////
  // Check for hooks...
  $function = "admin_perform_edit_specific_user";
  if (function_exists($function)) {
    return call_user_func($function);
  }
  //////////////////////////////////
  if (!user_has_permission("de_can_administer_users")) {
    display_access_denied();
  }
  
  
  global $db;

  $faculty_id = $_REQUEST["faculty_id"];
  $user_type = $_REQUEST["user_type"];
  $permissions = "";

  
  // Build the permissions string we need to add to the db.
  foreach ($_REQUEST as $key => $value) {
    if (strstr($key, "perm~~_~~") && $value == "yes") {
      $perm = trim(str_replace("perm~~_~~", "", $key));
      
      if (user_has_permission($perm)) {
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
  $res = $db->db_query("DELETE FROM users WHERE `faculty_id`='?' ", $faculty_id) ;

  // Now, insert.
  $res = $db->db_query("INSERT INTO users
						(`faculty_id`,`user_type`,`permissions`)
						values ('?','?','?') ", $faculty_id, $user_type, $permissions) ;


  display_edit_specific_user(get_success_msg("Successfully updated user " . get_current_time()));


}


function perform_process_group_definitions()
{
  //////////////////////////////////
  // Check for hooks...
  $function = "admin_perform_process_group_definitions";
  if (function_exists($function)) {
    return call_user_func($function);
  }
  //////////////////////////////////
  if (!user_has_permission("de_can_administer_data_entry")) {
    display_access_denied();
  }
  
  
  global $db;
  $db2 = new DatabaseHandler();
  $de_catalog_year = $GLOBALS["de_catalog_year"];
  $msg = "";
  $msg .= "<ul>";
  // This function will go through every group for this year and
  // re-run it's definition, saving the result.

  // First, find every group which has a definition set.
  $res = $db->db_query("SELECT * FROM draft_groups
                       WHERE definition != '' 
                       AND catalog_year = '?' 
                       AND delete_flag = 0 ", $de_catalog_year);
  while($cur = $db->db_fetch_array($res))
  {
    $def = $cur["definition"];
    $group_id = $cur["group_id"];
    $group_name = $cur["group_name"];
    $temp = get_courses_from_definition($def);
    $courses = trim($temp["text"]);
    $ccount = 0;

    $msg .= "<li>Working on $group_name...</li>";

    // Remove all the existing group requirements for this group first.
    $res2 = $db->db_query("DELETE FROM draft_group_requirements
                        WHERE group_id = ? ", $group_id);


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
        $subject_id = trim($tokens[0]);
        $course_num = trim($tokens[1]);
        $min_grade = trim($tokens[2]);
        $course_repeats = trim($tokens[3]);

        if (strstr($min_grade, "["))
        {
          // This is actually a specified repeat, not a min grade.
          $course_repeats = $min_grade;
          $min_grade = "";
        }

        $min_grade = str_replace("(","",$min_grade);
        $min_grade = strtoupper(str_replace(")","",$min_grade));

        $course_repeats = str_replace("[","",$course_repeats);
        $course_repeats = str_replace("]","",$course_repeats);
        $course_repeats--;
        if ($course_repeats < 0) { $course_repeats = 0; }

        // If the subject_id had a _A_ in it, convert this back
        // to an ampersand.
        $subject_id = str_replace("_A_", "&", $subject_id);

        // We don't care about catalog year anymore...
        if ($course_id = $db->get_course_id($subject_id, $course_num, "", true))
        {
          $query = "INSERT INTO draft_group_requirements
										(`group_id`,`course_id`,
										`course_min_grade`,`course_repeats`,`data_entry_value`)
										values ('?','?',
										'?','?','?~?') ";
          $res2 = $db->db_query($query, $group_id, $course_id, $min_grade, $course_repeats, $subject_id, $course_num);
          $ccount++;
          //debug_c_t($query);
        } else {
          // The course_id could not be found!
          $msg .= "<li><font color='red'><b>Course Not Found!</b>
							You specified the course
							<b>$subject_id $course_num</b> as a requirement in $group_name, but this course
							could not be found in the catalog.
							It was removed from the list of requirements.
							Are you sure you typed it correctly?  Please check 
							your spelling, and add the course again.</font></li>";

        }
      }
    }

    $msg .= "<li>$group_name defintion processed.  $ccount courses added.</li>";


  }
  $msg .= "</ul>";

  $msg = "<div class='hypo'><b>Processed group definitions.</b>  Log:<br>$msg</div>";

  $db->add_to_log("admin_proc_group_defs","$de_catalog_year");
  
  display_edit_groups($msg);


}



function perform_edit_specific_group()
{
  //////////////////////////////////
  // Check for hooks...
  $function = "admin_perform_edit_specific_group";
  if (function_exists($function)) {
    return call_user_func($function);
  }
  //////////////////////////////////
  if (!user_has_permission("de_can_administer_data_entry")) {
    display_access_denied();
  }
  
  
  global $db;
  $db2 = new DatabaseHandler();
  $de_catalog_year = $GLOBALS["de_catalog_year"];
  $group_id = $_REQUEST["group_id"];

  // Okay, we are trying to save the details of this group.
  // First thing we need to do is UPDATE the title, group_name,
  // priority, icon and comment.
  $group_name = trim($_POST["group_name"]);
  $title = trim($_POST["title"]);
  $priority = trim($_POST["priority"]);
  $icon_filename = trim($_POST["icon_filename"]);
  $data_entry_comment = trim($_POST["data_entry_comment"]);

  // Save the entire post to the log.
  $post_x_m_l = fp_array_to_xml("post",$_POST, true);
  $db->add_to_log("admin_edit_group","$group_id,$group_name",$post_x_m_l);

  // Since we are making a change to the draft table(s), let's add a row
  // to draft instructions.
  $db->add_draft_instruction("-");


  // Are we trying to delete this group?
  if ($_POST["perform_action2"] == "delete_group")
  {
    $res = $db->db_query("UPDATE draft_groups
								SET `delete_flag`='1'
								WHERE `group_id`='?' 
								AND `catalog_year`='?'
								", $group_id, $de_catalog_year);
    display_edit_groups("<font size='4' color='green'>The group $title ($group_name) has been deleted successfully for this year.</font>");
    die;
  }

  // If the $group_id == new then create a new one.
  if ($group_id == "new")
  {
    $group_id = $db->request_new_group_id();
    $res = $db->db_query("INSERT INTO draft_groups(`group_id`,`catalog_year`)
								values ('?','?') ", $group_id, $de_catalog_year);
    $_POST["group_id"] = $group_id;
    $_GET["group_id"] = $group_id;
    $_REQUEST["group_id"] = $group_id;
  }


  $res = $db->db_query("UPDATE draft_groups
							SET `group_name`='?',
							`title`='?',
							`priority`='?',
							`icon_filename`='?',
							`data_entry_comment`='?'
							WHERE
								`group_id`='?' ", 
              $group_name, $title, $priority, $icon_filename, $data_entry_comment, $group_id);

  // We need to delete all the existing course & subgroup requirements from this group.
  // That entails first seeing what subgroups were required and deleting them,
  // then deleting the parent group's requirements.
  // First, find and delete the branches (child groups):
  $res = $db->db_query("SELECT * FROM draft_group_requirements
							WHERE `group_id`='?'
							AND `child_group_id` != '0' ", $group_id);
  while ($cur = $db->db_fetch_array($res))
  {
    $cg_id = $cur["child_group_id"];
    $res2 = $db2->db_query("DELETE FROM draft_group_requirements
								WHERE `group_id`='?' ", $cg_id);
  }
  // Now delete the course requirements...
  $res = $db->db_query("DELETE FROM draft_group_requirements
								WHERE `group_id`='?' ", $group_id);

  $courses = trim($_POST["courses"]);
  // If a definition was set, then we will ignore what is in the POST
  // for the course requrements, and instead use the definition.
  if (trim($_POST["set_definition"] != ""))
  {
    $def = urldecode(trim($_POST["set_definition"]));
    //$cc = trim(get_courses_from_definition($def, $de_catalog_year));
    $temp2 = get_courses_from_definition($def);
    $cc = trim($temp2["text"]);
    if ($cc != "")
    {
      $courses = $cc;
      // UPDATE this group's definition!
      $res = $db->db_query("UPDATE draft_groups
							SET `definition`='?'
							WHERE
								`group_id`='?' ", $def, $group_id);

    }
    //print_pre($cc);
  }
  else {
    // In other words, the setDefinition WAS blank.
    // Let's update the table.  This is to fix a bug where they were unable
    // to clear definitions.
      $res = $db->db_query("UPDATE draft_groups
							SET `definition`=''
							WHERE
								`group_id`='?' ", $group_id);    
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
      if (!$branch_id = $db->request_new_group_id())
      {
        die ("Error.  Could not create new group (branch) ID.");
      } else {
        // Add this branch to the list of requirements for this group.
        $query = "INSERT INTO draft_group_requirements
										(`group_id`,`child_group_id`)
										values ('?','?') ";
        $res = $db->db_query($query, $group_id, $branch_id);
        //debug_c_t($query);

      }
      $c_tokes = split("&",$line);
      for ($cT = 0; $cT < count($c_tokes); $cT++)
      {
        $tokens = split(" ", trim($c_tokes[$cT]));
        $subject_id = trim($tokens[0]);
        $course_num = trim($tokens[1]);
        $min_grade = trim($tokens[2]);
        $course_repeats = trim($tokens[3]);

        if (strstr($min_grade, "["))
        {
          // This is actually a specified repeat, not a min grade.
          $course_repeats = $min_grade;
          $min_grade = "";
        }

        $min_grade = str_replace("(","",$min_grade);
        $min_grade = str_replace(")","",$min_grade);

        $course_repeats = str_replace("[","",$course_repeats);
        $course_repeats = str_replace("]","",$course_repeats);
        $course_repeats--;
        if ($course_repeats < 0) { $course_repeats = 0; }

        // If the subject_id had a _A_ in it, convert this back
        // to an ampersand.
        $subject_id = str_replace("_A_", "&", $subject_id);

        // Commenting out, because we do not care about catalog_year
        // when specifying courses...
        //if ($course_id = $db->get_course_id($subject_id, $course_num, $de_catalog_year))
        if ($course_id = $db->get_course_id($subject_id, $course_num, "", true))
        {
          $query = "INSERT INTO draft_group_requirements
										(`group_id`,`course_id`,
										`course_min_grade`,`course_repeats`,`data_entry_value`)
										values ('?','?',
										'?','?','?~?') ";
          $res = $db->db_query($query, $branch_id, $course_id, $min_grade, $course_repeats, $subject_id, $course_num);
          //debug_c_t($query);
        } else {
          // The course_id could not be found!
          $errors .= "<br><font color='red'><b>Course Not Found!</b>
							You specified the course
							<b>$subject_id $course_num</b> as a requirement, but this course
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
      $subject_id = trim($tokens[0]);
      $course_num = trim($tokens[1]);
      $min_grade = trim($tokens[2]);
      $course_repeats = trim($tokens[3]);

      if (strstr($min_grade, "["))
      {
        // This is actually a specified repeat, not a min grade.
        $course_repeats = $min_grade;
        $min_grade = "";
      }

      $min_grade = str_replace("(","",$min_grade);
      $min_grade = strtoupper(str_replace(")","",$min_grade));

      $course_repeats = str_replace("[","",$course_repeats);
      $course_repeats = str_replace("]","",$course_repeats);
      $course_repeats--;
      if ($course_repeats < 0) { $course_repeats = 0; }

      // If the subject_id had a _A_ in it, convert this back
      // to an ampersand.
      $subject_id = str_replace("_A_", "&", $subject_id);

      // We don't care about catalog year anymore...
      if ($course_id = $db->get_course_id($subject_id, $course_num, "", true))
      {
        $query = "INSERT INTO draft_group_requirements
										(`group_id`,`course_id`,
										`course_min_grade`,`course_repeats`,`data_entry_value`)
										values ('?','?',
										'?','?','?~?') ";
        $res = $db->db_query($query, $group_id, $course_id, $min_grade, $course_repeats, $subject_id, $course_num);
        //debug_c_t($query);
      } else {
        // The course_id could not be found!
        $errors .= "<br><font color='red'><b>Course Not Found!</b>
							You specified the course
							<b>$subject_id $course_num</b> as a requirement, but this course
							could not be found in the catalog.
							It was removed from the list of requirements.
							Are you sure you typed it correctly?  Please check 
							your spelling, and add the course again.</font>";

      }



    }

  }


  $msg = "<font color='green' size='4'>Group updated successfully at " . get_current_time() . ".</font>";
  $bool_scroll = true;
  if ($errors != "")
  {
    $msg = $errors;
    $bool_scroll = false;
  }


  display_edit_specific_group($msg, $bool_scroll);


}


function perform_copy_degree()
{
  //////////////////////////////////
  // Check for hooks...
  $function = "admin_perform_copy_degree";
  if (function_exists($function)) {
    return call_user_func($function);
  }
  //////////////////////////////////
  if (!user_has_permission("de_can_administer_data_entry")) {
    display_access_denied();
  }
  
  
  global $db;
  $de_catalog_year = $GLOBALS["de_catalog_year"];

  $source_major_code = trim(strtoupper($_POST["source_major_code"]));
  $destination_major_code = trim(strtoupper($_POST["destination_major_code"]));
  $include_tracks = $_POST["include_tracks"];
  
  // First thing's first.  Make sure the sourceMajorCode exists.
  $res = $db->db_query("SELECT * FROM draft_degrees 
                    WHERE (major_code = '?'
                    OR major_code LIKE '?|%')
                    AND catalog_year='?' ", $source_major_code, $source_major_code, $de_catalog_year) ;
  if ($db->db_num_rows($res) == 0) {
    // Meaning, it could not be found.
    display_copy_degree("<font color='red'>The source major, $source_major_code, could
                        not be found for $de_catalog_year.</font>");
    return;
  }

  // Alright, if we got to here, we can proceed.  We need to 
  // delete everything involving the destination major.
  // First, get the degree_id's in a select...
  $res = $db->db_query("SELECT * FROM draft_degrees 
                    WHERE (major_code = '?'
                    OR major_code LIKE '?|%')
                    AND catalog_year='?' ", $destination_major_code, $destination_major_code, $de_catalog_year) ;
  if ($db->db_num_rows($res) > 0) {
    while ($cur = $db->db_fetch_array($res)) {
      $degree_id = $cur["degree_id"];
      $res2 = $db->db_query("DELETE FROM draft_degree_requirements
                           WHERE degree_id='?' ", $degree_id) ;
      
      $res2 = $db->db_query("DELETE FROM draft_degrees
                           WHERE degree_id = '?' ", $degree_id) ;      
    }
    // Now, delete the tracks.
    $res2 = $db->db_query("DELETE FROM draft_degree_tracks
                          WHERE major_code = '?' 
                          AND catalog_year='?' ", $destination_major_code, $de_catalog_year) ;
  }

  // Okay, with the destination major good and deleted, we can proceed with
  // the copy.
  
  // Let's build up an array of all the degrees we will be copying.
  $source_array = array();
  // First, the base degree...
  $res = $db->db_query("SELECT * FROM draft_degrees 
                    WHERE major_code = '?'
                    AND catalog_year='?' ", $source_major_code, $de_catalog_year) ;
  $cur = $db->db_fetch_array($res);
  $source_array[] = $cur;
  
  // Now, any tracks or concentrations?
  if ($include_tracks == "yes") {
    $res = $db->db_query("SELECT * FROM draft_degrees 
                      WHERE major_code LIKE '?|%'
                      AND catalog_year='?' ", $source_major_code, $de_catalog_year) ;
    while ($cur = $db->db_fetch_array($res)) {
      $source_array[] = $cur;
    }

    // While we're here, let's go ahead and make a copy of the tracks.
    $res = $db->db_query("SELECT * FROM draft_degree_tracks
                        WHERE (major_code = '?'
                        OR major_code LIKE '?|%' )
                        AND catalog_year='?' ", $source_major_code, $source_major_code, $de_catalog_year) ;
    while($cur = $db->db_fetch_array($res)) {
      extract($cur, 3, "db");
      $dest_code = $destination_major_code;
      if (strstr($db_major_code, "|")) {
        // We need to adjust the destCode to match
        //the source.
        $dest_code = str_replace("$source_major_code|", "$destination_major_code|", $db_major_code);
      }
      
      $res2 = $db->db_query("INSERT INTO draft_degree_tracks
                          (catalog_year, major_code, track_code, 
                          track_title, track_short_title, track_description)
                          VALUES
                          ('?', '?', '?', '?', '?', '?') ",
                          $de_catalog_year, $dest_code, $db_track_code, 
                          $db_track_title, $db_track_short_title, 
                          $db_track_description) ;
                          
    }       
  }
  
  //var_dump($source_array);
  // Okay, now it's time to go through the sourceArray
  // and duplicate them.
  foreach ($source_array as $src) {
    extract($src, 3, "src");
    
    $dest_code = $destination_major_code;
    if (strstr($src_major_code, "|")) {
      // We need to adjust the destCode to match
      //the source.
      $dest_code = str_replace("$source_major_code|", "$destination_major_code|", $src_major_code);
    }
    
    //var_dump($dest_code);
    $dest_degree_id = $db->request_new_degree_id();

    // Create the entry in the degrees table.
    $res = $db->db_query("INSERT INTO draft_degrees
                        (degree_id, major_code, degree_type, degree_class, title,
                         public_note, semester_titles_csv,
                         catalog_year, exclude)
                         VALUES   
                        ('?', '?', '?', '?', '?', '?', '?', '?', '?') ",
                         $dest_degree_id, $dest_code, $src_degree_type, $src_degree_class, $src_title,
                         $src_public_note, $src_semester_titles_csv,
                         $de_catalog_year, $src_exclude);
    
    // Now, go through the source's degree requirements and copy those over.
    $res = $db->db_query("SELECT * FROM draft_degree_requirements
                         WHERE degree_id = '$src_degree_id' ");
    while ($cur = $db->db_fetch_array($res)) {
      extract($cur, 3, "db");
      
      $res2 = $db->db_query("INSERT INTO draft_degree_requirements
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
                            $dest_degree_id, $db_semester_num, $db_group_id,
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
  $res = $db->db_query("INSERT INTO draft_instructions
                        (instruction) VALUES ('-') ");
  
  
  
  display_copy_degree(get_success_msg("Degree $source_major_code has been copied to $destination_major_code for $de_catalog_year."));
}


function perform_add_new_degree()
{
  
  //////////////////////////////////
  // Check for hooks...
  $function = "admin_perform_add_new_degree";
  if (function_exists($function)) {
    return call_user_func($function);
  }
  //////////////////////////////////
  if (!user_has_permission("de_can_administer_data_entry")) {
    display_access_denied();
  }
  
  
  global $db;
  $de_catalog_year = $GLOBALS["de_catalog_year"];
  // This will be used to add a new degree (and possibly track)
  // to the database.
  $major_code = trim(strtoupper(($_POST["major_code"])));
  $track_code = trim(strtoupper($_POST["track_code"]));

  $new_major = $_POST["new_major"];
  $new_track = $_POST["new_track"];

  //////////////////////////////////////////////

  if ($major_code == "")
  {
    $msg = "<div><font color='red' size='4'>You must enter
					a major or major|concentration code.</font></div>";
    display_add_new_degree($msg);
    die;
  }

  if ($new_track == "new" && $track_code == "")
  {
    $msg = "<div><font color='red' size='4'>You selected to add
				a track, but did not specify a track code.</font></div>";
    display_add_new_degree($msg);
    die;
  }

  // Make sure user did not enter an underscore (_) in either
  // the track or major code!
  if (strstr($track_code, "_") || strstr($major_code, "_"))
  {
    $msg = "<div><font color='red' size='4'>You are not allowed to enter
              underscores (_) in either the track code or major code.
              FlightPath will add that for you. Please re-enter your
              new degree w/o using an underscore.</font></div>";
    display_add_new_degree($msg);
    die;

  }


  ////////////////////////////////////////////////////




  // First, deal with the major/concentration.
  // Firstly, check to see if it already exists...
  $res = $db->db_query("SELECT * FROM draft_degrees
					WHERE `catalog_year`='?'
					AND `major_code`='?' ", $de_catalog_year, $major_code);
  if ($db->db_num_rows($res) > 0 && $new_major == "new")
  { // Meaning, it already exists, yet we are trying to add it as a new
    // major.  This is an error!
    $msg = "<div><font color='red' size='4'>The major $major_code already exists for $de_catalog_year.
					You cannot add it as new.</font></div>";
    display_add_new_degree($msg);
    die;
  }
  if ($db->db_num_rows($res) == 0 && $new_major == "existing")
  { // This is another error.  We are trying to add a track to an existing
    // major code, but none was found.
    $msg = "<div><font color='red' size='4'>The major $major_code could not be found
					in the system for $de_catalog_year. Perhaps you need to add it first?</font></div>";
    display_add_new_degree($msg);
    die;
  }
  if ($db->db_num_rows($res) == 0 && $new_major == "new")
  {
    // This means we are trying to add a new major to the degrees table.
    // We may proceed with this.
    $db2 = new DatabaseHandler();
    $degree_id = $db2->request_new_degree_id();
    $db2->db_query("INSERT INTO draft_degrees
						(`degree_id`,`major_code`,`catalog_year`)
						values ('?', '?', '?') ", $degree_id, $major_code, $de_catalog_year);
  }


  if ($new_track == "new")
  {
    //////////////////////////////////////////////////
    // Now, let's see about adding ourself a track...
    // First, check to see if it exists...
    $res = $db->db_query("SELECT * FROM draft_degree_tracks
					WHERE `catalog_year`='?'
					AND `major_code`='?' 
					AND `track_code`='?' ", $de_catalog_year, $major_code, $track_code);	

    if ($db->db_num_rows($res) > 0)
    {
      // Meaning, it already existed, so we can't create it.
      $msg = "<div><font color='red' size='4'>The major and track $major_code $track_code already exists for $de_catalog_year.
					You cannot add it as new.</font></div>";
      display_add_new_degree($msg);
      die;
    } else {
      // We can add it to the tracks table...
      $db2 = new DatabaseHandler();
      $db2->db_query("INSERT INTO draft_degree_tracks
							(`catalog_year`,`major_code`,`track_code`)
							values ('?', '?', '?') ", $de_catalog_year, $major_code, $track_code);

      // Now, we also need to add this major & track code to the degrees table.
      $new_major_code = $major_code;
      if (strstr($major_code, "|"))
      {
        // Already has a pipe, so it has a concentration.
        $new_major_code .= "_$track_code";
      } else {
        // No concentration...
        $new_major_code .= "|_$track_code";
      }

      $degree_id = $db2->request_new_degree_id();
      $db2->db_query("INSERT INTO draft_degrees
						(`degree_id`,`major_code`,`catalog_year`)
						values ('?', '?', '?') ", $degree_id, $new_major_code, $de_catalog_year);

    }





  }


  display_add_new_degree(get_success_msg("New degree $major_code $track_code added successfully to $de_catalog_year.
						You may add another degree, or use the menu at the top of the page to edit this new degree."));


}






function perform_edit_specific_degree()
{
  
  //////////////////////////////////
  // Check for hooks...
  $function = "admin_perform_edit_specific_degree";
  if (function_exists($function)) {
    return call_user_func($function);
  }
  //////////////////////////////////
  if (!user_has_permission("de_can_administer_data_entry")) {
    display_access_denied();
  }
  
  
  global $db;
  $de_catalog_year = $GLOBALS["de_catalog_year"];
  // This will UPDATE a degree in the system with the courses
  // and groups that the user selected.
  $perform_action2 = trim($_POST["perform_action2"]);

  if (strstr($perform_action2, "del_group"))
  {
    $temp = split("_",$perform_action2);
    $del_group = new Group();
    $del_group->bool_use_draft = true;
    $del_group->group_id = $temp[1];
    $del_group->assigned_to_semester_num = $temp[2];

  }
  //debug_c_t($perform_action2);

  $major_code = trim($_POST["major_code"]);
  if ($major_code == ""){	die("Fatal error:  major_code not found.");}

  // Since we are making a change to the draft table(s), let's add a row
  // to draft instructions.
  $db->add_draft_instruction("-");


  $degree_id = "";
  // First things first, if this degree already exists in this
  // catalog year, then we need to delete it first.
  if ($degree_id = $db->get_degree_id($major_code, $de_catalog_year, true))
  {
    $degree = new DegreePlan($degree_id, null, false, false, true);
    $degree->load_descriptive_data();

    // Delete from degree_requirements WHERE this degree_id exists.
    $res = $db->db_query("DELETE FROM draft_degree_requirements
									WHERE `degree_id`='?' ", $degree_id);

    // Are we trying to DELETE this degree?  If so, keep deleting!
    if ($perform_action2 == "delete_degree")
    {
      $res = $db->db_query("DELETE FROM draft_degrees
									WHERE `degree_id`='?' ", $degree_id);

      // Also need to get rid of the track, if there is one for this
      // degree.
      $res = $db->db_query("DELETE FROM draft_degree_tracks
									WHERE `major_code`='$degree->major_code' 
									AND `track_code`='$degree->track_code'
									AND `catalog_year` = '?' LIMIT 1", $de_catalog_year);

      // Okay, we have deleted everything.  We need to go back to
      // just the list of degrees.
      display_edit_degrees("<font color='green'><b>The degree $major_code ($de_catalog_year) has been deleted.</b></font>");
      die;
    }


  } else {
    // We need to generate a new degreeID for this major_code and catalog_year,
    // because one does not already exist!
    if (!$degree_id = $db->request_new_degree_id())
    {
      die ("Error.  Could not create new degreeID.");
    }
  }

  // Save the entire post to the log.
  $post_x_m_l = fp_array_to_xml("post",$_POST, true);
  $db->add_to_log("admin_edit_degree","$degree_id,$major_code",$post_x_m_l);


  $errors = "";
  $semester_titles_c_s_v = "";
  $highest_semester_num = 0;   // What is the largest semester_num in the system?
  // Okay, now get the various courses...
  for ($semester_num = 0; $semester_num < 50; $semester_num++)
  {
    // Assuming no more than 50 semesters.
    $courses = trim($_POST["courses_$semester_num"]);
    if ($courses == "")
    {
      continue;
    }

    if ($semester_num > $highest_semester_num)
    {
      $highest_semester_num = $semester_num;
    }

    $course_rows = split("\n",$courses);
    for ($t = 0; $t < count($course_rows); $t++)
    {
      $line = trim($course_rows[$t]);
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
      $subject_id = $tokens[0];
      $course_num = $tokens[1];
      $requirement_type = strtolower($tokens[2]);

      if ($requirement_type == "")
      { // major type by default.
        $requirement_type = "m";
      }

      $min_grade = strtoupper($tokens[3]);

      if (strstr($requirement_type, "("))
      {
        // This means there was no requirement_type specified, so it's "m",
        // and a min_grade was found in its place.
        $min_grade = strtoupper($requirement_type);
        $requirement_type = "m";
      }

      $min_grade = str_replace("(","",$min_grade);
      $min_grade = str_replace(")","",$min_grade);

      /////////////////////////////////////////////
      // Okay, we now have enough information to insert the course.
      // Find out what the course_id is.
      if ($course_id = $db->get_course_id($subject_id, $course_num, "", true))  // don't care about catalog year.
      {
        $query = "INSERT INTO draft_degree_requirements
										(`degree_id`,`semester_num`,`course_id`,
										`course_min_grade`,`course_requirement_type`,
										 `data_entry_value`)
										values ('?','?','?',
										'?','?','?~?') ";
        $res = $db->db_query($query, $degree_id, $semester_num, $course_id, $min_grade, $requirement_type, $subject_id, $course_num);
        //debug_c_t($query);
      } else {
        // The course_id could not be found!
        $errors .= "<br><font color='red'><b>Course Not Found!</b>
							In Block " . ($semester_num+1) . ", you specified the course
							<b>$subject_id $course_num</b> as a requirement, but this course
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
    $group_id = $temp[0];
    $semester_num = $temp[1];
    $hours = $temp[2];
    $type = $temp[3];
    $min_grade = trim($temp[4]);

    if ($semester_num > $highest_semester_num)
    {
      $highest_semester_num = $semester_num;
    }


    // Do not add if we are supposed to be deleting this group!
    if (is_object($del_group))
    {
      if ($del_group->group_id == $group_id && $del_group->assigned_to_semester_num == $semester_num)
      {
        continue;
      }
    }

    // We now have enough information to insert this group.
    //debugCT("group: $group_id $semester_num $hours $type");
    $query = "INSERT INTO draft_degree_requirements
										(`degree_id`,`semester_num`,`group_id`,
										`group_requirement_type`,`group_hours_required`,`group_min_grade`)
										values ('?','?','?',
										'?','?','?') ";
    $res = $db->db_query($query, $degree_id, $semester_num, $group_id, $type, $hours, $min_grade);
    //debug_c_t($query);


  }


  // Was there a group added or deleted?
  if (strstr($perform_action2,"add_group"))
  {
    $temp = split("_",$perform_action2);
    $group_id = $temp[1];
    $semester_num = $temp[2];
    $hours = trim($temp[3]);
    $type = $temp[4];
    $min_grade = trim($temp[5]);
    $query = "INSERT INTO draft_degree_requirements
										(`degree_id`,`semester_num`,`group_id`,
										`group_requirement_type`,`group_hours_required`,`group_min_grade`)
										VALUES ('?','?','?','?','?','?') ";
    $res = $db->db_query($query, $degree_id, $semester_num, $group_id, $type, $hours, $min_grade);

  }


  // Make the semesterTitlesCSV...
  for ($semester_num = 0; $semester_num <= $highest_semester_num; $semester_num++)
  {
    $semester_titles_c_s_v .= trim($_POST["semester_title_$semester_num"]) . ",";
  }

  // Before we UPDATE, also grab the degree title, degree_type,
  // and exclude value, etc....
  $degree_title = trim($_POST["title"]);
  $degree_type = trim($_POST["degree_type"]);
  $degree_class = strtoupper(trim($_POST["degree_class"]));
  $exclude = trim($_POST["exclude"]);
  $public_note = trim($_POST["public_note"]);
  $res = $db->db_query("UPDATE draft_degrees
							SET `semester_titles_csv`='?',
							`title`='?',
							`degree_type`='?',
							`degree_class`='?',
							`exclude`='?',
							`public_note`='?'
							WHERE `degree_id`='?' ",
              $semester_titles_c_s_v, $degree_title, $degree_type, $degree_class, $exclude, $public_note, $degree_id);

  ////  Was there a track title/description?  If so, UPDATE that in the tracks
  // table...
  if (strstr($major_code, "_"))
  {
    // There was a track. Update track description.
    $temp = split("_",$major_code);
    $major = trim($temp[0]);
    // major might now have a | at the end.  If so, take it out.
    if (substr($major, strlen($major)-1, 1) == "|")
    {
      $major = str_replace("|","",$major);
    }


    $track = trim($temp[1]);
    $track_description = trim($_POST["track_description"]);
    $track_title = trim($_POST["track_title"]);
    //debugCT($track_description);
    $res = $db->db_query("UPDATE draft_degree_tracks
								SET `track_description`='?',
								`track_title`='?'
								WHERE `track_code`='?'
								AND `major_code`='?' 
								AND `catalog_year`='?' ", $track_description, $track_title, $track, $major, $de_catalog_year);
  }




  $msg = "<font color='green' size='4'>Degree updated successfully at " . get_current_time() . ".</font>";
  $bool_scroll = $bool_button_msg = true;
  if ($errors != "")
  {
    $msg = $errors;
    $bool_scroll = $bool_button_msg = false;
  }

  display_edit_specific_degree($msg, $bool_scroll, $bool_button_msg);
}

function get_current_time()
{  
  //////////////////////////////////
  // Check for hooks...
  $function = "admin_get_current_time";
  if (function_exists($function)) {
    return call_user_func($function);
  }
  //////////////////////////////////
    
  $datetime              = date ("g:i:sa");
  return $datetime;
}




function popup_students_using_course()
{
  //////////////////////////////////
  // Check for hooks...
  $function = "admin_popup_students_using_course";
  if (function_exists($function)) {
    return call_user_func($function);
  }
  //////////////////////////////////
  
  
  global $db, $screen;
  $course_id = $_REQUEST["course_id"];
  $course = new course($course_id);
  $course->bool_use_draft = true;
  $course->load_descriptive_data();

  $pC = "";
  $pC .= "<b>Top 150 Students who have taken $course->subject_id $course->course_num ($course_id):</b>
			<br><br>
	
			<table border='1'>
		";

  
  // Let's pull the needed variables out of our settings, so we know what
	// to query, because this involves non-FlightPath tables.
	$tsettings = $GLOBALS["fp_system_settings"]["extra_tables"]["course_resources:student_courses"];
	$tf = (object) $tsettings["fields"];  //Convert to object, makes it easier to work with.  
	$table_name = $tsettings["table_name"];  
  
  $res = $db->db_query("
						SELECT * FROM $table_name							
							WHERE 
								$tf->subject_id = '$course->subject_id'
								AND $tf->course_num = '$course->course_num'
								LIMIT 150	") ;

  while($cur = $db->db_fetch_array($res)) {
    $student_id = $cur[$tf->student_id];
    $hours = $cur[$tf->hours_awarded];
    $grade = $cur[$tf->grade];
    $term_id = $cur[$tf->term_id];
    
    $pC .= "<tr>
					<td valign='top' class='tenpt'>$student_id</td>
					<td valign='top' class='tenpt'>$hours</td>
					<td valign='top' class='tenpt'>$grade</td>
					<td valign='top' class='tenpt'>$term_id</td>
				
					
				</tr>
				";
  }

  $pC .= "</table>";

  $screen->page_title = "FlightPath Admin - Students Taken Course";
  $pC .= get_j_s();

  $screen->page_is_popup = true;
  $screen->page_hide_report_error = true;
  //include("template/fp_template.php");

  $screen->page_content = $pC;
  // send to the browser
  $screen->output_to_browser();


}



function popup_groups_using_course()
{
  //////////////////////////////////
  // Check for hooks...
  $function = "admin_popup_groups_using_course";
  if (function_exists($function)) {
    return call_user_func($function);
  }
  //////////////////////////////////
  
  
  global $db, $screen;
  $course_id = $_REQUEST["course_id"];
  $course = new course($course_id, false, null, false, "", true);
  $course->load_descriptive_data();

  $pC = "";
  $pC .= "<b>Groups using $course->subject_id $course->course_num ($course_id),
				Only showing top level groups (will not display if course
				only appears in branches):</b>
			<br><br>
	
			<table border='1'>
		";

  $res = $db->db_query("SELECT * FROM draft_groups a,
							draft_group_requirements b
							WHERE course_id = '?'
							and a.group_id = b.group_id ", $course_id) ;
  $c = 0;
  while($cur = $db->db_fetch_array($res))
  {
    extract ($cur, 3, "db");
    $pC .= "<tr>
					<td valign='top' class='tenpt'>$db_title</td>
					<td valign='top' class='tenpt'>$db_group_name</td>
					<td valign='top' class='tenpt'>$db_catalog_year</td>
					<td valign='top' class='tenpt'><a href='admin.php?performAction=popup_show_group_use&group_id=$db_group_id'>degree use</a></td>
					
				</tr>
				";
    $c++;
  }

  $pC .= "</table>Count: $c";

  $screen->page_title = "FlightPath Admin - Groups Using Course";
  $pC .= get_j_s();

  $screen->page_is_popup = true;
  $screen->page_hide_report_error = true;
  //include("template/fp_template.php");

  $screen->page_content = $pC;
  // send to the browser
  $screen->output_to_browser();


}


function popup_degrees_using_course()
{
  
  //////////////////////////////////
  // Check for hooks...
  $function = "admin_popup_degrees_using_course";
  if (function_exists($function)) {
    return call_user_func($function);
  }
  //////////////////////////////////
  
  
  global $db, $screen;
  $course_id = $_REQUEST["course_id"];
  $course = new course($course_id);
  $course->bool_use_draft = true;
  $course->load_descriptive_data();

  $pC = "";
  $pC .= "<b>Degrees using $course->subject_id $course->course_num ($course_id)
				in the bare degree plan (not in groups):</b>
			<br><br>
	
			<table border='1'>
		";

  $res = $db->db_query("SELECT * FROM draft_degrees a,
							draft_degree_requirements b
							WHERE `course_id`='?'
							and a.degree_id = b.degree_id ", $course_id) ;
  $c = 0;
  while($cur = $db->db_fetch_array($res))
  {
    extract ($cur, 3, "db");
    $pC .= "<tr>
					<td valign='top' class='tenpt'>$db_title</td>
					<td valign='top' class='tenpt'>$db_major_code</td>
					<td valign='top' class='tenpt'>$db_catalog_year</td>
					<td valign='top' class='tenpt'>" . get_semester_name($db_semester_num) . "</td>
				</tr>
				";
    $c++;
  }

  $pC .= "</table>Count: $c";

  $screen->page_title = "FlightPath Admin - Degrees Using Course";
  $pC .= get_j_s();

  $screen->page_is_popup = true;
  $screen->page_hide_report_error = true;
  //include("template/fp_template.php");

  $screen->page_content = $pC;
  // send to the browser
  $screen->output_to_browser();


}

function popup_show_group_use()
{
  
  //////////////////////////////////
  // Check for hooks...
  $function = "admin_popup_show_group_use";
  if (function_exists($function)) {
    return call_user_func($function);
  }
  //////////////////////////////////
  
  
  global $db, $screen;

  $group_id = $_REQUEST["group_id"];
  $group = new Group();
  $group->group_id = $group_id;
  $group->bool_use_draft = true;
  $group->load_descriptive_data();


  $pC = "";
  $pC .= "<b>Degrees using $group->title (<i>$group->group_name</i>):</b>
		<br><br>
		<table border='0' cellspacing='5'>
		<tr>
			<td><u>Degree</u></td>
			<td><u>Code</u></td>
			<td><u>Semester</u></td>
			<td><u>Year</u></td>
		</tr>
		";

  $res = $db->db_query("SELECT * FROM draft_degrees a,
    								draft_degree_requirements b
    							WHERE a.degree_id = b.degree_id
    							AND b.group_id = '?'
    							ORDER BY a.title, a.major_code, b.semester_num ", $group_id);
  while($cur = $db->db_fetch_array($res))
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
						" . get_semester_name($db_semester_num) . "
					</td>
					<td valign='top' class='tenpt' width='100'>
						$db_catalog_year
					</td>
					
				</tr>
				";

  }


  $pC .= "</table>";

  $screen->page_title = "FlightPath Admin - Group Use";
  $pC .= get_j_s();

  $screen->page_is_popup = true;
  $screen->page_hide_report_error = true;
  //include("template/fp_template.php");

  $screen->page_content = $pC;
  // send to the browser
  $screen->output_to_browser();



}

function get_semester_name($semester_num)
{
  
  //////////////////////////////////
  // Check for hooks...
  $function = "admin_get_semester_name";
  if (function_exists($function)) {
    return call_user_func($function, $semester_num);
  }
  //////////////////////////////////
  
  
  $ar = array("Freshman Year", "Sophomore Year", "Junior Year", "Senior Year");
  $s = $ar[$semester_num];
  if ($s == "")
  {
    $s = "Year " . ($semester_num + 1);
  }
  return $s;
}



function popup_select_icon()
{
  //////////////////////////////////
  // Check for hooks...
  $function = "admin_popup_select_icon";
  if (function_exists($function)) {
    return call_user_func($function);
  }
  //////////////////////////////////
  
  
  global $db, $screen;

  $group_id = $_REQUEST["group_id"];
  $group = new Group();
  $group->group_id = $group_id;
  $group->bool_use_draft = true;
  $group->load_descriptive_data();

  $pC = "<b>Please Select an Icon to use for $group->title (<i>$group->group_name</i>):</b>
			<div class='tenpt'>Current icon: <img src='$screen->theme_location/images/icons/$group->icon_filename' width='19'>
			$group->icon_filename.
			<br><br>
			Available Icons:
				<blockquote>";
  $handle = opendir("$screen->theme_location/images/icons/.");
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
				<input type='button' value='Select -> ' onClick='popup_select_icon(\"$file\");' >
				&nbsp; &nbsp;
				<img src='$screen->theme_location/images/icons/$file' width='19'>
				$file</div>";
  }


  $pC .= "</blockquote></div>";
  $screen->page_title = "FlightPath Admin - Select Icon";
  $pC .= get_j_s();

  $screen->page_is_popup = true;
  $screen->page_hide_report_error = true;

  $screen->page_content = $pC;
  // send to the browser
  $screen->output_to_browser();


}

function popup_add_group()
{
  //////////////////////////////////
  // Check for hooks...
  $function = "admin_popup_add_group";
  if (function_exists($function)) {
    return call_user_func($function);
  }
  //////////////////////////////////
  if (!user_has_permission("de_can_administer_data_entry")) {
    display_access_denied();
  }
  
  
  global $db,$screen;
  $semester_num = trim($_GET["semester_num"]);
  $de_catalog_year = $GLOBALS["de_catalog_year"];
  $pC = "";

  $pC .= "<b>Add an elective group to semester: $semester_num in $de_catalog_year</b><br>
				<span class='tenpt'>Use keyboard shortcut CTRL-F to find groups quickly.</span>
				<br><br>
				First, select a group (from $de_catalog_year):
				<div class='tenpt' 
					style='height:200px; overflow-y: scroll; border: 1px solid black;
					margin:5px;'>
				<table border='0' cellspacing='5'>";

  $res = $db->db_query("SELECT * FROM draft_groups
							WHERE `catalog_year`='?'
							AND `delete_flag`='0'
							ORDER BY `title` ", $de_catalog_year);
  while($cur = $db->db_fetch_array($res))
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
			Min Grade: <select name='min_grade' id='min_grade'>
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
				onClick='popup_add_group(\"$semester_num\");'>
			</div>";


  $screen->page_title = "FlightPath Admin - Add Group";
  $pC .= get_j_s();

  $screen->page_is_popup = true;
  $screen->page_hide_report_error = true;
  //include("template/fp_template.php");
  $screen->page_content = $pC;
  // send to the browser
  $screen->output_to_browser();


}


function popup_edit_definition()
{
  //////////////////////////////////
  // Check for hooks...
  $function = "admin_popup_edit_definition";
  if (function_exists($function)) {
    return call_user_func($function);
  }
  //////////////////////////////////
  if (!user_has_permission("de_can_administer_data_entry")) {
    display_access_denied();
  }
  
  
  global $db, $screen;
  $group_id = trim($_REQUEST["group_id"]);
  $de_catalog_year = $GLOBALS["de_catalog_year"];
  $group = new Group($group_id, null, -1, false, true);
  $group->load_descriptive_data();

  $definition = trim($_REQUEST["definition"]);
  if ($definition == "")
  {
    $definition = $group->definition;
  }

  $results = get_courses_from_definition($definition);
  //$results = get_courses_from_definition($definition, $de_catalog_year);

  $pC = "";

  $pC .= "<b>Edit Definition for $group->title ($de_catalog_year)<br><i>$group->group_name</i></b>
<br><br><form action='admin.php?performAction=popup_edit_definition&de_catalog_year=$de_catalog_year&group_id=$group_id' method='POST' id='mainform'>
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



  $screen->page_title = "FlightPath Admin - Edit Definition";
  $pC .= get_j_s();

  $screen->page_is_popup = true;
  $screen->page_hide_report_error = true;

  $screen->page_content = $pC;
  // send to the browser
  $screen->output_to_browser();


}


function get_courses_from_definition($definition, $catalog_year = "")
{
  //////////////////////////////////
  // Check for hooks...
  $function = "admin_get_courses_from_definition";
  if (function_exists($function)) {
    return call_user_func($function, $definition, $catalog_year);
  }
  //////////////////////////////////
  
  
  
  $group_array = array();

  // Okay, first things first, let's trim this sucker and remove extra whitespace.
  $definition = trim($definition);
  $definition = str_replace("   "," ",$definition);
  $definition = str_replace("  "," ",$definition);
  $definition = str_replace("  "," ",$definition);

  // Okay, now let's break this up into lines...
  $d_lines = split("\n",$definition);
  foreach($d_lines as $line)
  {
    $line = trim($line);

    // Let's get each of the parts... the instruction, and the course data.
    $tokens = split(" ", $line);
    $instruction = strtolower(trim($tokens[0]));
    $course_data = trim($tokens[1]);

    // We know that the course data can also be broken up, by the .
    $c_tokens = split("\.", $course_data);
    $subject_data = trim(strtoupper($c_tokens[0]));
    $course_numData = trim(strtoupper($c_tokens[1]));

    // Okay, so now, for this line, we have an instruction,
    // and some course data (possibly wild cards) to act on.
    //debugCT("$instruction $subject_data $course_numData");

    $t_array = get_course_array_from_definition_data($subject_data, $course_numData, $catalog_year);
    // Okay, we got our list.  Now what do we do with them?
    if ($instruction == "add" || $instruction == "+")
    {
      $group_array = array_merge($group_array, $t_array);
      $group_array = array_unique($group_array);
    }

    if ($instruction == "remove" || $instruction == "rem" || $instruction == "-" || $instruction == "del")
    {
      //print "<pre>" . print_r($t_array) . "</pre>";
      //debug_c_t(count($group_array));
      //$group_array = array_diff($group_array, $t_array);
      $group_array = rp_array_diff($group_array, $t_array);

      $group_array = array_unique($group_array);
      //debug_c_t(count($group_array));
    }



  }

  // Here's what we need to do:
  // In groupArray, we have the subject_id and course_num of every course in this definition.
  // We need to convert them to course_id's from the table,
  // and make sure we do not have duplicates.
  // First, get an array of course_id from the groupArray...
  $course_idArray = $group_array;
  // Take out duplicate entries (caused by eqv courses)...

  $course_idArray = array_unique($course_idArray);
  //print_r($course_idArray);
  //debugCT(sizeof($course_idArray));
  // Now, convert BACK into the "groupArray" structure (subject_id and course_num)...
  $group_array2 = get_course_array_from_course_id_array($course_idArray);

  //print_r($group_array);

  // Place in alphabetical order.
  sort($group_array2);

  //var_dump($group_array2);

  $rtn = array();
  $count = 1;
  // Now that we have the groupArray, we will turn it into a string...
  for ($t = 0; $t < count($group_array2); $t++)
  {
    $line = trim($group_array2[$t]);
    if ($line == "~~" || $line == "") continue;
    $count++;
    $temp = split(" ~~ ", $line);
    $si = trim($temp[0]);
    $cn = trim($temp[1]);

    $rtn["text"] .= "$si $cn\n";


  }
  $rtn["text"] = str_replace("&", "_A_", $rtn["text"]);
  //debug_c_t(count($group_array));
  $rtn["count"] = $count;

  return $rtn;
}

function get_course_array_from_course_id_array($course_idArray)
{
  //////////////////////////////////
  // Check for hooks...
  $function = "admin_get_course_array_from_course_id_array";
  if (function_exists($function)) {
    return call_user_func($function, $course_idArray);
  }
  //////////////////////////////////
  
  
  
  // Convert an array of course_id's into their subject_id ~~ course_num format.
  // Pick non-excluded courses over excluded courses, when you have the option.
  $rtn_array = array();
  $db = new DatabaseHandler();


  // MUST use foreach since we used array_unique earlier.  Can't use
  // count($arr) after you use array_unique!!
  foreach($course_idArray as $t => $value)
  {
    $new_course = new Course();
    $new_course->bool_use_draft = true;
    $new_course->db = $db;
    $new_course->course_id = $course_idArray[$t];
    $new_course->load_descriptive_data(false);
    array_push($rtn_array, "$new_course->subject_id ~~ $new_course->course_num");
  }
  return $rtn_array;

}


function get_course_id_array_from_course_array($course_array)
{
  //////////////////////////////////
  // Check for hooks...
  $function = "admin_get_course_id_array_from_course_array";
  if (function_exists($function)) {
    return call_user_func($function, $course_array);
  }
  //////////////////////////////////
  
  
  $rtn_array = array();
  $db = new DatabaseHandler();

  // MUST use foreach instead of for since we did
  // array_unique!  Can't trust count($arr)!
  foreach($course_array as $t => $value)
  {
    $line = trim($course_array[$t]);
    if ($line == "~~" || $line == "") continue;
    $count++;
    $temp = split(" ~~ ", $line);
    $si = trim($temp[0]);
    $cn = trim($temp[1]);

    $course_id = $db->get_course_id($si, $cn, "", true);
    $rtn_array[] = "$course_id";  // force into a string.
  }

  return $rtn_array;
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


function get_course_array_from_definition_data($subject_data, $course_numData, $catalog_year = "")
{
  
  //////////////////////////////////
  // Check for hooks...
  $function = "admin_get_course_array_from_definition_data";
  if (function_exists($function)) {
    return call_user_func($function, $subject_data, $course_numData, $catalog_year);
  }
  //////////////////////////////////
  
  
  global $db;
  // Looks at the subjectData and course_numData fields, and constructs
  // a query to pull our every course which matches it.

  $rtn_array = array();

  $si = str_replace("*","%",$subject_data);
  $cn = str_replace("*","%",$course_numData);

  $catalog_line = "";
  if ($catalog_year != "")
  {
    $catalog_line = "AND `catalog_year`='$catalog_year'";
  }

  
  $query = "SELECT * FROM draft_courses
				WHERE 
					`subject_id` LIKE '?'
				AND `course_num` LIKE '?'
				AND `course_id` > 0
				$catalog_line
				GROUP BY subject_id, course_num
				";
  $res = $db->db_query($query, $si, $cn) ;
  while ($cur = $db->db_fetch_array($res))
  {
    $course_id = $cur["course_id"];
    
    if (in_array($course_id, $rtn_array)) continue;
    $rtn_array[] = $course_id;
    
  }

  return $rtn_array;
}



function init_hidden_variables()
{
  
  //////////////////////////////////
  // Check for hooks...
  $function = "admin_init_hidden_variables";
  if (function_exists($function)) {
    return call_user_func($function);
  }
  //////////////////////////////////
  
  
  global $db;
  $settings = $db->get_flightpath_settings();


  $GLOBALS["de_catalog_year"] = trim($_REQUEST["de_catalog_year"]);

  if ($GLOBALS["de_catalog_year"] == "")
  {
    // default value.
    $GLOBALS["de_catalog_year"] = $settings["current_catalog_year"];
  }
}


function get_hidden_variables()
{
  // Supply the HTML for hidden variables needed by the data entry system.
  $rtn = "";

  $rtn .= "
				<input type='hidden' name='deCatalogYear' value='{$GLOBALS["de_catalog_year"]}' id='deCatalogYear'>
				
				<input type='hidden' name='performAction2' value='' id='performAction2'>
				<input type='hidden' name='scrollTop' value='' id='scrollTop'>
			";

  return $rtn;
}



function display_edit_specific_group($msg = "", $bool_scroll = true)
{
  //////////////////////////////////
  // Check for hooks...
  $function = "admin_display_edit_specific_group";
  if (function_exists($function)) {
    return call_user_func($function, $msg, $bool_scroll);
  }
  //////////////////////////////////
  if (!user_has_permission("de_can_administer_data_entry")) {
    display_access_denied();
  }
  
  
  global $screen, $db;
  $de_catalog_year = $GLOBALS["de_catalog_year"];
  $group_id = $_REQUEST["group_id"];
  $pC = "";

  $pC .= "<a class='tenpt' href='admin.php?performAction=editGroups&de_catalog_year=$de_catalog_year#group_$group_id'>Back to Group List</a>  &nbsp; - &nbsp;
			<a class='tenpt' href='admin.php?deCatalogYear=$de_catalog_year'>Back to main menu.</a>
			";
  if ($_SESSION["de_advanced_mode"] == true)
  {
    $pC .= " <span class='tenpt' style='background-color: yellow; margin-left: 20px;'>
					adv: group_id = $group_id.
					Used by:
					<a href='javascript: popupWindow(\"admin.php?performAction=popup_show_group_use&group_id=$group_id\");'>[degrees]</a>
				  </span>";
  }

  $group = new Group($group_id, null, -1, false, true);
  //print_pre($group->to_string());
  $group->load_descriptive_data();


  $pC .= "<h2>Edit Group: $group->title ($de_catalog_year)</h2>$msg";
  $pC .= "<form action='admin.php' method='POST' id='mainform'>
			<input type='hidden' name='deCatalogYear' value='$de_catalog_year'>
			<input type='hidden' name='performAction' value='perform_edit_specific_group'>
			<input type='hidden' name='performAction2' value='' id='performAction2'>
			<input type='hidden' name='setDefinition' id='setDefinition' value=''>
			<input type='hidden' name='scrollTop' id='scrollTop' value=''>
			<input type='hidden' name='group_id' value='$group_id'>
			
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
					<input type='text' name='group_name' value='$group->group_name' maxlength='100' size='50'> 
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
					<input type='hidden' name='icon_filename' id='icon_filename' value='$group->icon_filename'> 
					<img src='$screen->theme_location/images/icons/$group->icon_filename' width='19' id='iconSrc'> <span id='iconFn'>$group->icon_filename</span>
						 - <a href='javascript: popupWindow(\"admin.php?performAction=popup_select_icon&group_id=$group_id\");'>[select another]</a>
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
					<a href='javascript: popupWindow(\"admin.php?performAction=popup_edit_definition&de_catalog_year=$de_catalog_year&group_id=$group_id\");'>[edit definition]</a>
					<div class='tenpt' style='overflow: auto; $dheight'>
					<i>" . nl2br($group->definition) . "</i>
					</div>
				</td>
			</tr>
			
			</table>
			<hr>
			";

  $req_box_extra = $req_box_style = $req_box_explain = "";
  if (trim($group->definition) != "")
  {
    // Meaning, we have a definition specified, so disable the
    // required courses box!
    $req_box_extra = "readonly=readonly";
    $req_box_style = "background-color: lightgray;";
    $req_box_explain = "<div class='tenpt' style='padding-top: 10px;'>
							<b>Note:</b> Because a definition was specified,
							you cannot directly edit the Required Courses
							box.  Please manage specific courses using the
							Edit Definition window.
							</div>";
  }

  $courses = "";
  // Create the courses variable from all the requirements in this group.
  $courses = get_group_courses($group);
  $pC .= "Required Courses: (<a href='javascript: popupAlertHelp(\"group_entry\");'>Help - entering min grades and/or repeats</a>)$req_box_explain<br>
			<textarea name='courses' style='line-height: 1.5em;$req_box_style' wrap='OFF' rows='17' cols='80' $req_box_extra>$courses</textarea>
			<br>
			<input type='button' value='Save for $de_catalog_year' onClick='submitForm();'><br><br>
			<b>[Optional]</b> Comment: (only seen by data entry administrators)<br>
			<textarea name='data_entry_comment' rows='3' cols='80'>$group->data_entry_comment</textarea>
			<br>
				<div align='right'>
					Delete this group? <input type='button' value='X'
									onClick='deleteGroup(\"$group_id\");'>
				</div>			
			</div>
			";




  $pC .= "</form>";

  $pC .= get_j_s();
  $screen->page_title = "FlightPath Admin - Edit Group";

  if ($bool_scroll)
  {
    $screen->page_scroll_top = trim($_POST["scroll_top"]);
  }
  $screen->page_hide_report_error = true;
  //include("template/fp_template.php");

  $screen->page_content = $pC;
  // send to the browser
  $screen->output_to_browser();


  if ($_REQUEST["serialize"] != "")
  {
    print "<br><textarea rows=20 cols=80>" . serialize($group) . "</textarea>";
  }
}


function get_group_courses(Group $group)
{
  //////////////////////////////////
  // Check for hooks...
  $function = "admin_get_group_courses";
  if (function_exists($function)) {
    return call_user_func($function, $group);
  }
  //////////////////////////////////
  
  
  // Returns a plain text list of the courses in a group's requirements
  // for use in the editSpecificGroup page.
  $rtn = "";

  // courses not in branches...
  $courses = array();
  $c_count = 0;
  $group->list_courses->load_course_descriptive_data();
  $group->list_courses->sort_alphabetical_order();
  $group->list_courses->reset_counter();
  while($group->list_courses->has_more())
  {
    $c = $group->list_courses->get_next();
    if (strstr($c->subject_id , "&"))
    {
      $c->subject_id = str_replace("&", "_A_", $c->subject_id);

    }
    $course_line = "$c->subject_id $c->course_num";
    //$rtn .= "$c->subject_id $c->course_num";

    if ($c->min_grade != "" && $c->min_grade != "D")
    {
      //$rtn .= " ($c->min_grade)";
      $course_line .= " ($c->min_grade)";
    }

    //$rtn .= "\n";
    if ($courses[$course_line] == "")
    {
      $courses[$course_line] = 0;
    }
    // This is to check for specified repeats.
    $courses[$course_line]++;

  }

  // Go through the $courses array to check for specified repeats.
  foreach($courses as $course => $rep_count)
  {
    $rep_line = " [$rep_count]";
    if ($rep_count == 1)
    {
      $rep_line = "";
    }
    $rtn .= "$course$rep_line\n";
  }



  // Now, get them branches!
  if (!$group->list_groups->is_empty)
  {
    $group->list_groups->reset_counter();
    while ($group->list_groups->has_more())
    {
      $g = $group->list_groups->get_next();

      $g->list_courses->load_course_descriptive_data();
      $g->list_courses->sort_alphabetical_order();
      $g->list_courses->reset_counter();
      while($g->list_courses->has_more())
      {
        $c = $g->list_courses->get_next();
        if (strstr($c->subject_id , "&"))
        {
          $c->subject_id = str_replace("&", "_A_", $c->subject_id);
        }

        $rtn .= "$c->subject_id $c->course_num";

        if ($c->min_grade != "" && $c->min_grade != "D")
        {
          $rtn .= " ($c->min_grade)";
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

function display_edit_specific_degree($msg = "", $bool_scroll_page = false, $bool_button_msg = true)
{
  //////////////////////////////////
  // Check for hooks...
  $function = "admin_display_edit_specific_degree";
  if (function_exists($function)) {
    return call_user_func($function, $msg, $bool_scroll_page, $bool_button_msg);
  }
  //////////////////////////////////
  if (!user_has_permission("de_can_administer_data_entry")) {
    display_access_denied();
  }
  
  
  global $screen, $db;
  $de_catalog_year = $GLOBALS["de_catalog_year"];
  $major_code = $_REQUEST["major_code"];

  if ($msg == "")
  {
    $msg = "<font size='4'>&nbsp; &nbsp;</font>";
  }

  $button_msg = $msg;
  if ($bool_button_msg == false) {$button_msg = "";}

  $degree_id = intval($db->get_degree_id($major_code, $de_catalog_year, true));
  // The intval says, if it's false, make it = 0.  otherwise keep the number
  // that is returned.
  $degree = new DegreePlan($degree_id, null, false, false, true);
  $degree->load_descriptive_data();
  //var_dump($degree);


  $pC = "";

  $pC .= "<a class='tenpt' href='admin.php?performAction=editDegrees&de_catalog_year=$de_catalog_year#degree_$degree_id'>Back to Degree List</a>  &nbsp; - &nbsp;
			<a class='tenpt' href='admin.php?deCatalogYear=$de_catalog_year'>Back to main menu.</a>
			
			";
  if ($_SESSION["de_advanced_mode"] == true)
  {
    $pC .= " <span class='tenpt' style='background-color: yellow; margin-left: 20px;'>
					advanced: degreeID = $degree_id.
				  </span>";
  }

  $pC .= "<form id='mainform' action='admin.php' method='POST'>";

  $pC .= "<div style='font-size: 16pt; font-weight:bold; padding-top: 20px;'>$degree->degree_type $degree->title<br>$major_code ($de_catalog_year)</div>";
  $pC .= "
			<table>
			 <tr>
				<td valign='top' class='tenpt' width='15%'>Degree Type:</td>
				<td valign='top' class='tenpt' width='15%'><input type='text' name='degree_type' value='$degree->degree_type' size='5' maxlength='20'></td>

				<td valign='top' class='tenpt' width='15%'>Degree Class:</td>
				<td valign='top' class='tenpt'><input type='text' name='degree_class' value='$degree->degree_class' size='2' maxlength='1'>
				<a href='javascript: popupAlertHelp(\"degree_class\");'>?</a></td>
			 </tr>
				
			 </tr>
			 <tr>
				<td valign='top' class='tenpt'>Degree Title:</td>
				<td valign='top' class='tenpt' colspan='3'><input type='text' name='title' value='$degree->title' size='80' maxlength='100'></td>
			 </tr>
			 <tr>
				<td valign='top' class='tenpt'>Exclude:</td>
				<td valign='top' class='tenpt' colspan='3'><input type='text' name='exclude' value='$degree->db_exclude' size='2' maxlength='1'>
				<a href='javascript: popupAlertHelp(\"degree_exclude\");'>?</a></td>
			 </tr>
			 
			 
			 
			</table> ";

  if (strstr($major_code, "_"))
  {
    $pC .= "<b>Edit track information:</b>
			<blockquote style='margin-top: 0px; margin-bottom: 0px;'>
		<font size='2'>Track title: <input type='text' name='track_title' value='$degree->track_title' size='60' maxlength='100'></font><br>
		<font size='2'>Track description: <a href='javascript: popupAlertHelp(\"edit_formatting\");'>(Help - Formatting)</a>
						<a href='javascript: popupAlertHelp(\"track_description\");'>(Help - Track Descriptions)</a>
		</font><br>
		<textarea name='track_description' cols='60' rows='3'>" . convert_h_t_m_l_to_b_b_code($degree->track_description) . "</textarea>
		</blockquote>
		";
  }
  $pC .= "<div class='tenpt' align='center'>(Scroll to the bottom of the page for more options)</div>
	
			$msg";

  $pC .= "
			<input type='hidden' name='performAction' value='perform_edit_specific_degree'>
			<input type='hidden' name='major_code' value='$major_code'> ";

  $pC .= "
				
			
			";
  $pC .= get_hidden_variables();



  $degree->list_semesters->reset_counter();
  while ($degree->list_semesters->has_more())
  {
    $semester = $degree->list_semesters->get_next();
    if ($semester->semester_num < 0)
    {
      continue;
    }
    $sem_default_title = get_semester_name($semester->semester_num);
    if ($semester->title == $sem_default_title)
    {
      $semester->title = "";
    }

    $pC .= "<div class='elevenpt' style='padding-bottom: 30px;'>
					<b>Block number: " . ($semester->semester_num +1) . "</b>
					&nbsp; &nbsp; &nbsp; &nbsp;
					Default title: $sem_default_title
					&nbsp; &nbsp;
					Override: <input type='text' name='semester_title_$semester->semester_num' value='$semester->title' size='20'>
					<a href='javascript: popupAlertHelp(\"semester_title\");'>?</a>
					<table border='1' width='100%'>
					";
    // Get the courses.
    $pC .= "<tr><td valign='top'>
					<textarea name='courses_$semester->semester_num' rows='10' cols='20'>";
    $semester->list_courses->sort_alphabetical_order();
    $semester->list_courses->reset_counter();
    while($semester->list_courses->has_more())
    {
      $course = $semester->list_courses->get_next();
      $course->load_descriptive_data();
      $pC .= "$course->subject_id $course->course_num $course->requirement_type";
      if ($course->min_grade != "D" && $course->min_grade != "")
      {
        $pC .= " ($course->min_grade)";
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
    $semester->list_groups->sort_alphabetical_order();
    $semester->list_groups->reset_counter();
    while($semester->list_groups->has_more())
    {
      $group = $semester->list_groups->get_next();
      $group->load_descriptive_data();
      $pC .= "<tr><td valign='middle'><input type='button' value='x' style='width:15px; height:20px;' onClick='delGroup(\"$group->group_id\",\"$semester->semester_num\");'></td>
						<td valign='top' class='tenpt'>
						$group->title<br><i>$group->group_name</i></td>
						<td valign='top' class='tenpt'>$group->hours_required</td>
						<td valign='top' class='tenpt'>$group->min_grade</td>
						<td valign='top' class='tenpt'>$group->requirement_type
						<input type='hidden' name='group_" . $group->group_id . "_" . rand(1,999999) . "' value='$group->group_id" . "_$semester->semester_num" . "_$group->hours_required" . "_$group->requirement_type" . "_$group->min_grade'>
						</td>";	
    }
    $pC .= "</table>
				<div style='margin-top: 10px; margin-left: 20px;'>
					<a href='javascript:popupWindow(\"admin.php?performAction=popup_add_group&semester_num=$semester->semester_num&de_catalog_year=$de_catalog_year\");'>Add an elective group</a></div>
		</td>";


    $pC .= "</table><br><input type='button' onClick='submitForm();' value='Save for $de_catalog_year'> $button_msg</div>";
    $s_count = $semester->semester_num+1;
  }

  // Always add an additional 4 semesters to the bottom.
  for ($t = 0; $t < 4; $t++)
  {
    $sem = $t + $s_count;
    if ($sem > 49)
    {
      // Max number of semesters.  More or less arbitrarily set number.
      $pC .= "<br>Maximum number of semesters created.<br>";
      break;
    }

    $pC .= "<div class='elevenpt' style='padding-bottom: 30px;'>
					<b>Block number: " . ($sem+1) . "</b>
					&nbsp; &nbsp; &nbsp; &nbsp;
					Default title: " . get_semester_name($sem) . "
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
					<a href='javascript:popupWindow(\"admin.php?performAction=popup_add_group&semester_num=$sem&de_catalog_year=$de_catalog_year\");'>Add an elective group</a></div>
		</td>";


    $pC .= "</table><br><input type='button' onClick='submitForm();' value='Save for $de_catalog_year'> $button_msg</div>";

  }
  $pC .= "<div class='elevenpt'>If you need more semester boxes, simply save this page, and additional blank
			boxes will appear below.</div>
			
			<br><br>
			<div class='elevenpt'><b>More Options:</b><br>
			Enter a public note for this degree: 
			 <a href='javascript: popupAlertHelp(\"public_note\");'>(Help - Public Note)</a>
			 <a href='javascript: popupAlertHelp(\"edit_formatting\");'>(Help - Formatting)</a>
			<br>
			<textarea name='public_note' rows='4' cols='80'>$degree->public_note</textarea>
			
			
			</div>
			
			
			<input type='button' onClick='submitForm();' value='Save for $de_catalog_year'> $button_msg</div>
			
			"; 




  $pC .= "</form>";

  $pC .= "				<div align='right'>
					Delete this degree? <input type='button' value='X'
									onClick='deleteDegree(\"$degree_id\");'>
				</div>			
        ";


  $pC .= get_j_s();
  $screen->page_title = "FlightPath Admin - Edit Degree";

  if ($bool_scroll_page == true)
  {
    $screen->page_scroll_top = trim($_POST["scroll_top"]);
  }
  $screen->page_hide_report_error = true;
  //include("template/fp_template.php");
  $screen->page_content = $pC;
  // send to the browser
  $screen->output_to_browser();


  if ($_REQUEST["serialize"] != "")
  {
    print "<br><textarea rows=20 cols=80>" . serialize($degree) . "</textarea>";
  }

}

function convert_h_t_m_l_to_b_b_code($str)
{
  //////////////////////////////////
  // Check for hooks...
  $function = "admin_convert_h_t_m_l_to_b_b_code";
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


function display_edit_degrees($msg = "")
{
  //////////////////////////////////
  // Check for hooks...
  $function = "admin_display_edit_degrees";
  if (function_exists($function)) {
    return call_user_func($function, $msg);
  }
  //////////////////////////////////
  if (!user_has_permission("de_can_administer_data_entry")) {
    display_access_denied();
  }
  
  
  global $screen, $db;
  $db2 = new DatabaseHandler();
  $de_catalog_year = $GLOBALS["de_catalog_year"];
  $pC = "";

  $pC .= "<a class='tenpt' href='admin.php?deCatalogYear=$de_catalog_year'>Back to main menu.</a><br>
			<h2 style='margin-bottom: 5px;'>Edit Degrees for $de_catalog_year</h2>$msg
			<div class='tenpt'>
				<a href='admin.php?performAction=addNewDegree&de_catalog_year=$de_catalog_year'>Add new degree plan (major, concentration, or track)</a>.
				&nbsp; &nbsp; | &nbsp; &nbsp;
				<a href='admin.php?performAction=copyDegree&de_catalog_year=$de_catalog_year'>Copy a degree plan</a>.
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

  $res = $db->db_query("SELECT * FROM draft_degrees
							WHERE `catalog_year`='?'
						 ORDER BY degree_type, major_code, title ", $de_catalog_year);

  while($cur = $db->db_fetch_array($res))
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
    $on_mouse_over = " onmouseover=\"style.backgroundColor='#FFFF99'\"
    onmouseout=\"style.backgroundColor='$bgc'\" ";
    */

    $img = "spacer.gif";

    // get JUST the major code...
    $temp = split("\|", $db_major_code);
    $just_major = trim($temp[0]);
    $just_conc = trim($temp[1]);
    $outside = "";

    //if ($just_conc != "" && strstr($just_conc, "_"))
    if (strstr($just_conc, "_"))
    {
      // If the concentration has an underscore, it's actually
      // a track.  Let's get the track title...
      $temp2 = split("_",$just_conc);
      $just_track = trim($temp2[1]);
      // Might need to add the first part BACK onto the major...
      if (trim($temp2[0]) != "")
      {
        $just_major .= "|" . trim($temp2[0]);
      }


      $res2 = $db2->db_query("SELECT * FROM draft_degree_tracks
								WHERE `catalog_year`='?'
								AND `major_code`='?'
								AND `track_code`='?' ", $de_catalog_year, $just_major, $just_track);
      if ($db2->db_num_rows($res2) > 0)
      {
        $cur2 = $db2->db_fetch_array($res2);

        $db_title = trim($cur2["track_title"]);
        $outside = "----&gt;";
        if (strstr($just_major, "|"))
        { // both a conc AND a track. Denote it special.
          $outside = ">>" . $outside;
        }
        $db_degree_type = "";

      }
    } else if($just_conc != "")
    {
      // Meaning, this is a concentration, NOT a track.
      $db_degree_type = "";
      $outside = "&gt;&gt;";
    }



    $pC .= "<a name='degree_$db_degree_id'></a>";
    $pC .= "<div class='elevenpt' style='padding-bottom: 3px; padding-top: 3px; background-color: $bgc'
						$on_mouse_over>
					<img src='$screen->theme_location/images/$img' width='16'> $outside
						<a href='admin.php?performAction=editSpecificDegree&de_catalog_year=$de_catalog_year&major_code=$db_major_code' class='degree-$db_degree_class'>
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
  
  $screen->page_title = "FlightPath Admin - Degrees";

  $screen->page_hide_report_error = true;
  //include("template/fp_template.php");
  $screen->page_content = $pC;
  // send to the browser
  $screen->output_to_browser();


}



function display_edit_groups($msg = "")
{
  //////////////////////////////////
  // Check for hooks...
  $function = "admin_display_edit_groups";
  if (function_exists($function)) {
    return call_user_func($function, $msg);
  }
  //////////////////////////////////
  if (!user_has_permission("de_can_administer_data_entry")) {
    display_access_denied();
  }
  
  
  global $screen, $db;

  $db2 = new DatabaseHandler();
  $de_catalog_year = $GLOBALS["de_catalog_year"];
  $pC = "";

  $pC .= "<a class='tenpt' href='admin.php?deCatalogYear=$de_catalog_year'>Back to main menu.</a><br>
			<h2 style='margin-bottom:2px;'>Edit Groups for $de_catalog_year</h2>$msg
        Options:
				<ul style='margin-top: 5px;'>
				  <li><a href='admin.php?performAction=editSpecificGroup&group_id=new&de_catalog_year=$de_catalog_year'>Add a new group to this year</a><br>
					</li>
					<li><a href='javascript: processDefinitions($de_catalog_year);'>Process all group definitions for this year</a>
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

  $on_mouse_over = " onmouseover=\"style.backgroundColor='#FFFF99'\"
      				onmouseout=\"style.backgroundColor='white'\" ";

  $res = $db->db_query("SELECT * FROM draft_groups
							WHERE `catalog_year`='?'
							AND `delete_flag`='0'
							ORDER BY `title`, `group_name` ", $de_catalog_year);
  while($cur = $db->db_fetch_array($res))
  {
    extract($cur, 3, "db");

    $use_count = 0;
    // Find out how many degree plans are using this particular group...

    $res2 = $db->db_query("SELECT count(id) AS count FROM draft_degree_requirements
								WHERE `group_id`='$db_group_id' ");
    if ($db->db_num_rows($res2) > 0)
    {
      $cur2 = $db->db_fetch_array($res2);
      $use_count = $cur2["count"];
    }

    $def_flag = "";
    if (trim($db_definition) != "")
    {
      $def_flag = " (*)";
    }

    if ($db_title == "")
    {
      $db_title = "[NO TITLE SPECIFIED]";
    }

    $pC .= "<tr $on_mouse_over>
					<td valign='top' class='tenpt'><a name='group_$db_group_id'></a>
						<a href='admin.php?performAction=editSpecificGroup&group_id=$db_group_id&de_catalog_year=$de_catalog_year'>
							$db_title</a>
					</td>
					<td valign='top' class='tenpt'>
						<i>$db_group_name</i>$def_flag
					</td>
					<td valign='top' class='tenpt'>
						$db_priority
					</td>
					<td valign='top' class='tenpt'>
						<img src='$screen->theme_location/images/icons/$db_icon_filename' width='19'>
					</td>
					<td valign='top' class='tenpt'>
						$use_count <a href='javascript: popupWindow(\"admin.php?performAction=popup_show_group_use&group_id=$db_group_id\");'><img src='$screen->theme_location/images/popup.gif' border='0'></a>
					</td>

					
				</tr>"; 
  }

  $pC .= "</table>";

  $pC .= get_j_s();
  $screen->page_title = "FlightPath Admin - Groups";

  $screen->page_content = $pC;
  // send to the browser
  $screen->output_to_browser();


}


function perform_edit_urgent_msg()
{
  
  //////////////////////////////////
  // Check for hooks...
  $function = "admin_perform_edit_urgent_msg";
  if (function_exists($function)) {
    return call_user_func($function);
  }
  //////////////////////////////////
  if (!user_has_permission("de_can_administer_data_entry")) {
    display_access_denied();
  }
  
  
  
  global $db;

  $urgent_msg = trim(strip_tags($_POST["urgent_msg"]));
  $db->set_settings_variable("urgent_msg", $urgent_msg);
  
  display_edit_urgent_msg(get_success_msg("Successfully updated urgent message at " . get_current_time()));
}



function perform_edit_offline_mode()
{
  //////////////////////////////////
  // Check for hooks...
  $function = "admin_perform_edit_offline_mode";
  if (function_exists($function)) {
    return call_user_func($function);
  }
  //////////////////////////////////
  if (!user_has_permission("de_can_administer_data_entry")) {
    display_access_denied();
  }
  
  
  global $db;

  $offline_msg = trim(strip_tags($_POST["offline_msg"]));
  $db->set_settings_variable("offline_msg", $offline_msg);
  
  $offline_mode = trim(strip_tags($_POST["offline_mode"]));
  $db->set_settings_variable("offline_mode", $offline_mode);
  

  display_edit_offline_mode(get_success_msg("Successfully updated offline mode settings at " . get_current_time()));
}


function perform_edit_flightpath_settings()
{
  //////////////////////////////////
  // Check for hooks...
  $function = "admin_perform_edit_flightpath_settings";
  if (function_exists($function)) {
    return call_user_func($function);
  }
  //////////////////////////////////
  if (!user_has_permission("de_can_administer_data_entry")) {
    display_access_denied();
  }
  
  
  global $db;

  $available_advising_term_ids = trim(strip_tags($_POST["available_advising_term_ids"]));
  $db->set_settings_variable("available_advising_term_ids", $available_advising_term_ids);
  
  $advising_term_id = trim(strip_tags($_POST["advising_term_id"]));
  $db->set_settings_variable("advising_term_id", $advising_term_id);

  
  $current_catalog_year = trim(strip_tags($_POST["current_catalog_year"]));
  $db->set_settings_variable("current_catalog_year", $current_catalog_year);  

  $current_draft_catalog_year = trim(strip_tags($_POST["current_draft_catalog_year"]));
  $db->set_settings_variable("current_draft_catalog_year", $current_draft_catalog_year);

  // Save the entire post to the log.
  $post_x_m_l = fp_array_to_xml("post",$_POST, true);
  $db->add_to_log("admin_edit_settings","",$post_x_m_l);


  display_edit_flightpath_settings(get_success_msg("Successfully updated FlightPath advising settings at " . get_current_time()));
}


function perform_edit_announcements()
{
  //////////////////////////////////
  // Check for hooks...
  $function = "admin_perform_edit_announcements";
  if (function_exists($function)) {
    return call_user_func($function);
  }
  //////////////////////////////////
  if (!user_has_permission("de_can_administer_data_entry")) {
    display_access_denied();
  }
  
  
  global $db;
  $xml_array = array();
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
      $announcement_text = trim(strip_tags($_POST["announcement_$count"]));
      if ($announcement_text == "")
      {
        continue;
      }

      $visible = trim($_POST["visible_$count"]);

      // UPDATE the xmlArray...
      $xml_array["dt_$dt"] = "$visible ~~ $announcement_text";

    }
  }
  // Now, convert to XML and UPDATE the table.
  $xml = fp_array_to_xml("announcements",$xml_array);
  $db->set_settings_variable("announcements_xml", $xml);
  
  
  // Save the entire post to the log.
  $post_x_m_l = fp_array_to_xml("post",$_POST, true);
  $db->add_to_log("admin_edit_announcements","",$post_x_m_l);


  display_edit_announcements(get_success_msg("Successfully updated announcements at " . get_current_time()));
}

function get_success_msg($msg)
{
  //////////////////////////////////
  // Check for hooks...
  $function = "admin_get_success_msg";
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


function display_edit_flightpath_settings($msg = "")
{
  //////////////////////////////////
  // Check for hooks...
  $function = "admin_display_edit_flightpath_settings";
  if (function_exists($function)) {
    return call_user_func($function, $msg);
  }
  //////////////////////////////////
  if (!user_has_permission("de_can_administer_data_entry")) {
    display_access_denied();
  }
  
  
  global $screen, $db;
  $de_catalog_year = $GLOBALS["de_catalog_year"];
  $pC = "";
  $pC .= "<a class='tenpt' href='admin.php?deCatalogYear=$de_catalog_year'>Back to main menu.</a><br>
			<h2>Edit FlightPath Advising Settings</h2>$msg
			<div class='elevenpt'>
			<form action='admin.php' method='post'>
			<input type='hidden' name='performAction' value='perform_edit_flightpath_settings'>";

  $settings = $db->get_flightpath_settings();

  $pC .= "FP Term Quick Reference:";
  $sems = array(40,41, 60,81,82);
  for($t = $settings["current_catalog_year"]; $t <= $settings["current_catalog_year"] + 2; $t++) {
    $pC .= "<div style='padding-left: 15px;'>";
    foreach($sems as $sm) {
      $try_year = $t . $sm;
      $course = new Course();
      $course->term_id = $try_year;
      $pC .= "" . $course->get_term_description(true) . ": <b>$course->term_id</b>, &nbsp; &nbsp; &nbsp;";
    }
    $pC .= "</div>";
  }  
  
  $pC .= "<br>
      Available Advising Terms: (seperate by commas. Ex: 200940,200941,200960)<br>
			<input type='text' name='availableAdvisingTermIDs' value='{$settings["available_advising_term_ids"]}' maxlength='100' size='40'>
			<div style='font-size:8pt;'>* Make sure to list these in order, so they will appear in order in FP.</div>
			<br>
			Default advising term:<br>
			<input type='text' name='advisingTermID' value='{$settings["advising_term_id"]}' maxlength='100' size='40'>
			<div style='font-size:8pt;'>* Of the Available Advising Terms, this should be the default that FlightPath is set to
					when an advisor logs in. Ex: 200940.</div>
			
			<br>
			Current catalog year:<br>
			<input type='text' name='currentCatalogYear' value='{$settings["current_catalog_year"]}' maxlength='100' size='40'>
			<div style='font-size:8pt;'>* This is the year that What If loads degrees from, as well as several other important functions.  
				Only change this once you have
						fully loaded a new catalog year.</div>

			<br>
			Current <b>DRAFT</b> catalog year:<br>
			<input type='text' name='currentDraftCatalogYear' value='{$settings["current_draft_catalog_year"]}' maxlength='100' size='40'>
			<div style='font-size:8pt;'>* <b>While in Draft mode</b>, this is the year that What If loads degrees from, as well as several other important functions.  
				You may change this while working on a new catalog.  It will not
				affect any other users of the system.  While not working on a new catalog,
				set this to the same as the Current catalog year.</div>
						
			";


  $pC .= "<br><br><input type='submit' value='Save'>
			</form></div>";


  $pC .= get_j_s();

  $screen->page_title = "FlightPath Admin - FlightPath Settings";
  $screen->page_hide_report_error = true;
  //include("template/fp_template.php");

  $screen->page_content = $pC;
  // send to the browser
  $screen->output_to_browser();


}

function display_copy_degree($msg = "")
{
  //////////////////////////////////
  // Check for hooks...
  $function = "admin_display_copy_degree";
  if (function_exists($function)) {
    return call_user_func($function, $msg);
  }
  //////////////////////////////////
  if (!user_has_permission("de_can_administer_data_entry")) {
    display_access_denied();
  }
  
  
  
  global $screen, $db;
  $de_catalog_year = $GLOBALS["de_catalog_year"];
  $pC = "";
  $pC .= "<a class='tenpt' href='admin.php?performAction=editDegrees&de_catalog_year=$de_catalog_year'>Back to Degrees List</a>
			&nbsp; - &nbsp;
			<a class='tenpt' href='admin.php?deCatalogYear=$de_catalog_year'>Back to main menu</a>
			
			<br>
			<h2>Copy Degree for $de_catalog_year</h2>$msg
      
      <div>Use this form to copy (duplicate) a degree plan <b>in this
      catalog year</b>.</div>
					<br><br>
			<form action='admin.php' method='post'>
			 <input type='hidden' name='deCatalogYear' value='$de_catalog_year'>
			 <input type='hidden' name='performAction' value='perform_copy_degree'>
			 
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
			         will be deleted for <b>$de_catalog_year</b>!</div>
			   
			 
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
  
  $screen->page_title = "FlightPath Admin - Copy Degree";

  //include("template/fp_template.php");
  $screen->page_content = $pC;
  // send to the browser
  $screen->output_to_browser();
  
}



function display_add_new_degree($msg = "")
{
  //////////////////////////////////
  // Check for hooks...
  $function = "admin_display_add_new_degree";
  if (function_exists($function)) {
    return call_user_func($function, $msg);
  }
  //////////////////////////////////
    
  if (!user_has_permission("de_can_administer_data_entry")) {
    display_access_denied();
  }
  
  global $screen, $db;
  $de_catalog_year = $GLOBALS["de_catalog_year"];
  $pC = "";
  $pC .= "<a class='tenpt' href='admin.php?performAction=editDegrees&de_catalog_year=$de_catalog_year'>Back to Degrees List</a>
			&nbsp; - &nbsp;
			<a class='tenpt' href='admin.php?deCatalogYear=$de_catalog_year'>Back to main menu</a>
			
			<br>
			<h2>Add New Degree for $de_catalog_year</h2>$msg
	
			You may use this screen to add a new degree, by entering a new
			major, concentration, or track (degree option).
			
			<form action='admin.php' method='post'>
			<input type='hidden' name='deCatalogYear' value='$de_catalog_year'>
			<input type='hidden' name='performAction' value='perform_add_new_degree'>
			Please select an option:<br>
				<blockquote>
				<input type='radio' name='newMajor' value='new' checked>Enter a <b>new</b> major code [and concentration]<br>
				<input type='radio' name='newMajor' value='existing'>Enter an <b>existing</b> major code [and concentration] (only adding a new track)<br>
				&nbsp; &nbsp; Major|Conc code: <input type='text' name='major_code' value='' size='8'>
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
				&nbsp; &nbsp; Track code: <input type='text' name='track_code' value='' size='4'>
				
				
				</blockquote>
				<input type='submit' value='Submit'>
			</form>
		";








  $screen->page_title = "FlightPath Admin - Add New Degree";

  $screen->page_content = $pC;
  // send to the browser
  $screen->output_to_browser();


}


function display_apply_draft_changes($msg = "")
{
  //////////////////////////////////
  // Check for hooks...
  $function = "admin_display_apply_draft_changes";
  if (function_exists($function)) {
    return call_user_func($function, $msg);
  }
  //////////////////////////////////
  if (!user_has_permission("de_can_administer_data_entry")) {
    display_access_denied();
  }
  
  
  
  global $screen, $db;
  $de_catalog_year = $GLOBALS["de_catalog_year"];
  $cur_cat = $screen->settings["current_catalog_year"];
  $pC = "";
  $pC .= "<a class='tenpt' href='admin.php?deCatalogYear=$de_catalog_year'>Back to main menu.</a><br>
			<h2>Apply Draft Changes</h2>$msg
			<div class='elevenpt'>
			<form action='admin.php?deCatalogYear=$de_catalog_year' method='post'>
			<input type='hidden' name='performAction' value='perform_apply_draft_changes'>";


  $pC .= "	You can use this form to apply your draft changes to the production database,
				making changes to degrees, courses, and groups visible
				to all users of the system.
				<br><br>
				
				<b>For added security</b> you must enter the transfer passcode:
				<input type='password' name='passcode' size='10'><br><br>
	
			<input type='submit' value='Submit'> (May take several seconds or minutes. Please click only ONCE).
			
			</form></div>";


  $pC .= get_j_s();

  $screen->page_title = "FlightPath Admin - Apply Draft Changes";

  $screen->page_content = $pC;
  // send to the browser
  $screen->output_to_browser();

}



/**
 * I don't believe this function is being used any longer
 * in FlightPath, but I am going to leave it in as it may be
 * useful again one day.
 *
 * @param unknown_type $msg
 * @return unknown
 */
function display_transfer_data($msg = "")
{
  //////////////////////////////////
  // Check for hooks...
  $function = "admin_display_transfer_data";
  if (function_exists($function)) {
    return call_user_func($function, $msg);
  }
  //////////////////////////////////
  
  
  global $screen, $db;
  $de_catalog_year = $GLOBALS["de_catalog_year"];
  $cur_cat = $screen->settings["current_catalog_year"];
  $pC = "";
  $pC .= "<a class='tenpt' href='admin.php?deCatalogYear=$de_catalog_year'>Back to main menu.</a><br>
			<h2>Transfer Data to Production Server</h2>$msg
			<div class='elevenpt'>
			<form action='admin.php?deCatalogYear=$de_catalog_year' method='post'>
			<input type='hidden' name='performAction' value='perform_transfer_data'>";


  $pC .= "For some small amounts of data, you can automatically transfer
				data to the production server using this screen.  For
				Degrees, Courses, and Groups, you must still request a data
				transfer, as that amount of data is too large for this process.
					
				<br><br>
				Check what data you would like to transfer to production:
				<blockquote>
				<input type='radio' name='transfer' value='settings' checked=checked> Announcements, Urgent Msg, and FlightPath settings.<br>
				&nbsp; &nbsp; &nbsp; &nbsp; (current catalog year is <b>$cur_cat</b>)<br>
				<input type='radio' name='transfer' value='users'> User Privileges.<br>
				<input type='radio' name='transfer' value='help'> Help pages.<br>
				
				
							</blockquote>	
			";


  $pC .= "
			<b>For added security</b> you must enter the transfer passcode:
				<input type='password' name='passcode' size='10'><br><br>
	
			<input type='submit' value='Submit'> (May take several moments. Please click only ONCE).
			
			</form></div>";


  $pC .= get_j_s();

  $screen->page_title = "FlightPath Admin - Transfer Data";

  $screen->page_content = $pC;
  // send to the browser
  $screen->output_to_browser();

}



function display_edit_help($msg = "")
{
  //////////////////////////////////
  // Check for hooks...
  $function = "admin_display_edit_help";
  if (function_exists($function)) {
    return call_user_func($function, $msg);
  }
  //////////////////////////////////
  if (!user_has_permission("de_can_administer_data_entry")) {
    display_access_denied();
  }
  
  
  global $screen, $db;
  $de_catalog_year = $GLOBALS["de_catalog_year"];
  $pC = "";
  $pC .= "<a class='tenpt' href='admin.php?deCatalogYear=$de_catalog_year'>Back to main menu.</a><br>
			<h2>Edit Help</h2>$msg
			<div class='elevenpt'>
			<form action='admin.php' method='post' name='form1' id='form1'>
			<input type='hidden' name='performAction' value='editHelp'>";

  $pC .= "Select a page to edit:
					<select name='pageID'>
					<option value='0'>-- Please select --</option>
					";

  $res = $db->db_query("SELECT * FROM help ORDER BY `id` ");
  while ($cur = $db->db_fetch_array($res))
  {
    $pC .= "<option value='{$cur["id"]}'>{$cur["id"]} : {$cur["title"]}</option> \n";
  }
  $pC .= "<option value='new'> - Create a NEW page - </option>
				</select>
		
		<input type='submit' value='Load page ->'>
		</form>";
  $page_id = trim($_POST["page_id"]);

  if ($page_id < 1 && $page_id != "new") {$page_id = 1;} // default to main page.

  $help_page = $db->get_help_page($page_id);

  $page_u_r_l = "";

  $pC .= "
		<hr>
		<b>Editing page:</b> ";
  if ($page_id != "new")
  {

    $pC .= "(<a href='help.php?i=$page_id' target='_blank'>click to load page in new window</a>)";
    $page_u_r_l = "<br>Page URL: <tt style='background-color: beige;'>help.php?i=$page_id</tt>
						 <br>&nbsp; &nbsp; &nbsp; BBCode popup link (loads in a popup window): <span style='background-color: beige;'>[popup=help.php?i=$page_id]Click here![/popup]</span>
						 <!-- <br>&nbsp; &nbsp; &nbsp; BBCode internal link (loads in same window): <span style='background-color: beige;'>[url2=help.php?i=$page_id]Click here![/url2]</span> -->
						 ";
    $db->add_to_log("admin_edit_help","$page_id");

  }
  $pC .= "
		<br>
		<form method='post' action='admin.php' name='mainform' id='mainform'>
		<input type='hidden' name='performAction' value='perform_edit_help'>
		<input type='hidden' name='scrollTop' id='scrollTop' value=''>
		<input type='hidden' name='pageID' value='$page_id'>
		";		


  $pC .= "Page: $page_id $page_u_r_l<br>Title: <input type='text' name='title' value='{$help_page["title"]}' maxlength='100' size='60'>
					<br>Body: <font size='1'><b>Trouble with Copy/Paste? Use keyboard shortcuts CTRL-C and CTRL-V.</b></font><br>
					<textarea name='body' rows='20' cols='80'>{$help_page["body"]}</textarea>
					<br><br>
						";


  $pC .= "<input type='button' onClick='submitForm();' value='Save'>
			</form></div>";


  $pC .= get_j_s();
  $pC .= get_j_s_tiny_m_c_e();

  $screen->page_title = "FlightPath Admin - Help";

  $screen->page_hide_report_error = true;
  $screen->page_content = $pC;
  $screen->page_scroll_top = $_POST["scroll_top"];
  // send to the browser
  $screen->output_to_browser();


}



function display_edit_urgent_msg($msg = "")
{
  //////////////////////////////////
  // Check for hooks...
  $function = "admin_display_edit_urgent_msg";
  if (function_exists($function)) {
    return call_user_func($function, $msg);
  }
  //////////////////////////////////
  if (!user_has_permission("de_can_administer_data_entry")) {
    display_access_denied();
  }
  
  
  global $screen, $db;
  $de_catalog_year = $GLOBALS["de_catalog_year"];
  $pC = "";
  $pC .= "<a class='tenpt' href='admin.php?deCatalogYear=$de_catalog_year'>Back to main menu.</a><br>
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
			<input type='hidden' name='performAction' value='perform_edit_urgent_msg'>";

  $settings = $db->get_flightpath_settings();

  $pC .= "Urgent Message:
					<br>
					<textarea name='urgentMsg' rows='3' cols='60'>{$settings["urgent_msg"]}</textarea>
					<br><br>
						";


  $pC .= "<input type='submit' value='Save'>
			</form></div>";


  $pC .= get_j_s();

  $screen->page_title = "FlightPath Admin - Urgent Message";

  //include("template/fp_template.php");
  $screen->page_hide_report_error = true;
  $screen->page_content = $pC;
  // send to the browser
  $screen->output_to_browser();


}



function display_edit_offline_mode($msg = "")
{
  //////////////////////////////////
  // Check for hooks...
  $function = "admin_display_edit_offline_mode";
  if (function_exists($function)) {
    return call_user_func($function, $msg);
  }
  //////////////////////////////////
  if (!user_has_permission("de_can_administer_data_entry")) {
    display_access_denied();
  }
  
  
  
  global $screen, $db;
  $de_catalog_year = $GLOBALS["de_catalog_year"];
  $pC = "";
  $pC .= "<a class='tenpt' href='admin.php?deCatalogYear=$de_catalog_year'>Back to main menu.</a><br>
			<h2>Set/Unset Offline Mode</h2>$msg
			<div class='elevenpt'>
			If Offline Mode is enabled, logins will be disabled into FlightPath (though
			you will still be able to login to this Administrative Console by going
			directly to flightpath/admin.php).
			</div>
			<hr><div class='elevenpt'><form action='admin.php' method='post'>
			<input type='hidden' name='performAction' value='perform_edit_offline_mode'>";

  $settings = $db->get_flightpath_settings();

  $pC .= "
          Offline Mode Setting:
          <input type='text' name='offlineMode' size='2' value='{$settings["offline_mode"]}'>
          <div style='font-size: 9pt; padding-left: 10px;'>Enter <b>1</b> (one) to enable, <b>0</b> (zero) to disable</div>
          <br>
          Offline Message:
					<br>
					<textarea name='offlineMsg' rows='3' cols='60'>{$settings["offline_msg"]}</textarea>
          <div style='font-size: 9pt; padding-left: 10px;'>
            This message will be displayed instead of the login page when OfflineMode is enabled.
            Leave blank for a default message.  BBCode syntax is allowed for 
            extra formatting. <a href='javascript: popupAlertHelp(\"edit_formatting\");'>Click to view <b>formatting</b> help</a>.
            
          </div>					
					<br><br>
						";


  $pC .= "<input type='submit' value='Save'>
			</form></div>";


  $pC .= get_j_s();

  $screen->page_title = "FlightPath Admin - Offline Mode";

  //include("template/fp_template.php");
  $screen->page_hide_report_error = true;
  $screen->page_content = $pC;
  // send to the browser
  $screen->output_to_browser();


}



function display_edit_announcements($msg = "")
{
  //////////////////////////////////
  // Check for hooks...
  $function = "admin_display_edit_announcements";
  if (function_exists($function)) {
    return call_user_func($function, $msg);
  }
  //////////////////////////////////
  if (!user_has_permission("de_can_administer_data_entry")) {
    display_access_denied();
  }
  
  
  global $screen, $db;
  $de_catalog_year = $GLOBALS["de_catalog_year"];
  $pC = "";
  $pC .= "<a class='tenpt' href='admin.php?deCatalogYear=$de_catalog_year'>Back to main menu.</a><br>
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
			<input type='hidden' name='performAction' value='perform_edit_announcements'>";

  $settings = $db->get_flightpath_settings();

  $count = 0;
  // Pull out just the announcements XML and make it into its own array.
  if ($settings["announcements_xml"] != "")
  {
    if ($xml_array = fp_xml_to_array2($settings["announcements_xml"]))
    {
      krsort($xml_array);
      // Expected format of the xmlArray:
      //[dt_timecode] = "announcement text."
      // ex: dt_111234432.  Use strtotime to convert.
      // It begins with dt_ because in XML the start of
      // an element must be a letter, not a number.
      foreach($xml_array as $datetime => $announcement)
      {
        $dt = str_replace("dt_", "", $datetime);
        $disp_time = date("Y-m-d H:i:s", $dt);
        $pcheck = $fcheck = $hcheck = "";
        $pcheck = "checked=checked";

        // The announcement is split by a " ~~ " between the visibility
        // and the announcement itself.
        $temp = split(" ~~ ", $announcement);
        $vis = trim($temp[0]);
        $announcement_text = trim($temp[1]);

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

        $pC .= "Date/time: <input type='text' name='datetime_$count' value='$disp_time'>
				         <a href='javascript: popupAlertHelp(\"datetime\");'>?</a>
						<br>Visibility:
							<input type='radio' name='visible_$count' value='public' $pcheck>Anyone (incl. students)
							&nbsp; &nbsp; &nbsp;
							<input type='radio' name='visible_$count' value='faculty' $fcheck>Faculty/Staff
							&nbsp; &nbsp; &nbsp;
							<input type='radio' name='visible_$count' value='hide' $hcheck><span style='background-color: beige;'>Hidden</span>
							
				        <br>Announcement:
						<br>
						<textarea name='announcement_$count' id='announcement_$count' rows='4' cols='70'>$announcement_text</textarea>
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


  $pC .= get_j_s();

  $screen->page_title = "FlightPath Admin - Announcements";

  //include("template/fp_template.php");
  $screen->page_hide_report_error = true;
  $screen->page_content = $pC;
  // send to the browser
  $screen->output_to_browser();


}


function get_j_s_tiny_m_c_e()
{
  //////////////////////////////////
  // Check for hooks...
  $function = "admin_get_j_s_tiny_m_c_e";
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


function display_main_menu($msg = "")
{
  //////////////////////////////////
  // Check for hooks...
  $function = "admin_display_main_menu";
  if (function_exists($function)) {
    return call_user_func($function, $msg);
  }
  //////////////////////////////////
  
  
  // Main menu for the entry system.

  global $screen, $db;

  $settings = $db->get_flightpath_settings();

  $de_catalog_year = $GLOBALS["de_catalog_year"];
  $pC = "";

  $sp = "&nbsp; &nbsp; &nbsp;";

  $pC .= "<h2>FlightPath Admin Console - Main Menu</h2>
			
			Use the following options to edit the settings and data in FlightPath. <!--<a href='admin.php?performAction=logout'><b>Logout?</b> --></a>
			";
  if (user_has_permission("de_can_administer_users")) {
    $pC .= $screen->draw_menu_item("admin.php?perform_action=edit_users", "", "<img src='$screen->theme_location/images/group.png' border='0'>",  "User Management");    
  }
  
  if (user_has_permission("de_can_administer_data_entry")) {
        
    $pC .= $screen->draw_menu_item("admin.php?perform_action=edit_announcements", "", "<img src='$screen->theme_location/images/calendar_edit.png' border='0'>",  "Edit Announcements");
    
    $pC .= $screen->draw_menu_item("admin.php?perform_action=edit_urgent_msg", "", "<img src='$screen->theme_location/images/error.png' border='0'>",  "Edit Urgent Message");
    
    $pC .= $screen->draw_menu_item("admin.php?perform_action=edit_offline_mode", "", "<img src='$screen->theme_location/images/delete.png' border='0'>",  "Set/Unset Offline Mode");
    
    $pC .= $screen->draw_menu_item("admin.php?perform_action=edit_help", "", "<img src='$screen->theme_location/images/page_edit.png' border='0'>",  "Edit Help Pages");
  }


  // Add module's menus....
  $menus = get_modules_menus();  
  if (isset($menus["admin_console"])) {
    $pC .= $screen->draw_menu_items($menus["admin_console"]);
  }

    
  
  
  if (user_has_permission("de_can_administer_data_entry")) {
    
    $pC .= "	
    <br><br>
  			<div style='border: 1px solid black; padding:5px;'>
  			<a name='demenu'>
  			Data Entry<br><br> 
  			
  			$msg
  			<form action='admin.php#demenu' method='post'> " . get_hidden_variables() . "
  			<input type='hidden' name='performAction' value='performSetCatalogYear'>
  			Editing Catalog Year: <select name='catalog_year'>
  									" . get_catalog_year_options($de_catalog_year, $settings["current_catalog_year"]) . "
  									</select>
  									<input type='submit' value='-&gt;'>
  			</form>
  			<ul>
  				<li><a href='admin.php?performAction=editDegrees&de_catalog_year=$de_catalog_year' class='nounderline'>Edit Degree Plans
  							(for $de_catalog_year)</a></li>
  				<li><a href='admin.php?performAction=editGroups&de_catalog_year=$de_catalog_year' class='nounderline'>Edit Groups
  							(for $de_catalog_year)</a></li>
  				<li><a href='admin.php?performAction=editCourses&de_catalog_year=$de_catalog_year' class='nounderline'>Edit Courses
  							(for $de_catalog_year)</a></li>
  			</ul>
  					
  
  			";
    // Do we need to UPDATE any draft changes?
    $res = $db->db_query("SELECT * FROM draft_instructions
  	                   ");
    if ($db->db_num_rows($res) > 0)
    {
      $need_to_apply = "<div class='hypo' style='font-size: 10pt;
                                  padding: 5px;'>
                      <b>Note:</b> Draft changes have been made which have yet to be applied.
                      When you are ready for your draft changes to appear in
                      production, click the link below.
                      </div>";
    }
    $pC .= "
  
  			$need_to_apply
  			<ul>
  				<!--
  				<li><a href='admin.php?performAction=transferData&de_catalog_year=$de_catalog_year' class='nounderline'>Transfer data to production server</a></li>
  				<li><a href='admin.php?performAction=requestTransfer&de_catalog_year=$de_catalog_year' class='nounderline'>Request large data transfer to production server</a></li>
  				-->
  				<li><a href='admin.php?performAction=applyDraftChanges&de_catalog_year=$de_catalog_year' class='nounderline'>Apply Draft Changes</a></li>
  			</ul>
  			</div> 
  			";
  
  
    $pC .= "
  			<div style='border: 1px solid black; padding:5px;'>
  			 FlightPath Advising Settings<br>
  			
  			 <ul>
  				<li>Available terms for advising: <b>{$settings["available_advising_term_ids"]}</b> </li>
  				<li>Default advising term: <b>{$settings["advising_term_id"]}</b> </li>
  				<li>Current Catalog Year: <b>{$settings["current_catalog_year"]}</b> </li>
  				<li>Current Draft Catalog Year: <b>{$settings["current_draft_catalog_year"]}</b> </li>
  				
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


  $pC .= get_j_s();

  $screen->page_title = "FlightPath Admin - Main Menu";

  //include("template/fp_template.php");

  $screen->page_content = $pC;
  // send to the browser
  $screen->output_to_browser();


}

function get_catalog_year_options($selected_year, $current_catalog_year)
{
  //////////////////////////////////
  // Check for hooks...
  $function = "admin_get_catalog_year_options";
  if (function_exists($function)) {
    return call_user_func($function, $selected_year, $current_catalog_year);
  }
  //////////////////////////////////
  
  
  
  $rtn = "";

  // Show <options> for the available years.
  $years = array();

  // Check to make sure this has been configured!
  $earliest = $GLOBALS["fp_system_settings"]["earliest_catalog_year"];
  if ($earliest == "") {
    return "<option value=''>DATA NOT AVAIL. CHECK SYSTEM SETTINGS</option>";
  }
  
  for ($t = $current_catalog_year + 1; $t >= $GLOBALS["fp_system_settings"]["earliest_catalog_year"]; $t--)
  {
    $years[] = $t;
  }

  $years[] = 1900;

  //$years = array(1900, 2006,2007,2008,2009);
  foreach ($years as $year)
  {
    $sel = "";
    if ($year == $selected_year)
    {
      $sel = "SELECTED";
    }
    $rtn .= "<option value='$year' $sel>$year</option>";
  }

  return $rtn;
}

function get_j_s()
{
  //////////////////////////////////
  // Check for hooks...
  $function = "admin_get_j_s";
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
	
	function popup_select_icon(file)
	{
		opener.document.getElementById("icon_filename").value = file;
		opener.submitForm();
		window.close();
	}
	
	function deleteGroup(group_id)
	{
		var x = confirm("Are you sure you wish to delete this group? Any degrees which point to it will need to be manually edited and re-saved remove this group requirement.\n\nClick OK to proceed and delete this group.");
		if (!x)
		{
			return;
		}
		
		document.getElementById("perform_action2").value="delete_group";
		submitForm();
		
	}
	
	
	function deleteDegree(degreeID)
	{
		var x = confirm("Are you sure you wish to delete this degree? This action cannot be undone.");
		if (!x)
		{
			return;
		}
		
		document.getElementById("perform_action2").value="delete_degree";
		submitForm();
		
	}
	
	
	function deleteCourse(course_id, catalog_year, warnEqv)
	{
		var x = confirm("Are you sure you wish to delete this course for the catalog year " + catalog_year + "?  Any degrees or groups which use this course will have to be manually edited and re-saved to remove this course requirement.\n\nClick OK to proceed and delete this course.");
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
		
		document.getElementById("perform_action2").value="delete_course";
		submitForm();
		
		
	}
	
	function processDefinitions(catalog_year)
	{
	 var x = confirm("Are you sure you wish to process all group definitions for the year " + catalog_year + "?\n\nAll groups with definitions will be cleared, and their definitions re-run.\n\nNOTICE: This may take more than a minute to complete.\n\nClick OK to proceed.");
	 if (x)
	 {
	   window.location = "admin.php?performAction=perform_process_group_definitions&de_catalog_year=" + catalog_year;
	 }
	}
	
	function popup_add_group(semester_num)
	{

		var group_id = 0;
		
		var cbs = document.getElementsByName("rgroups");
		for (var t = 0; t < cbs.length; t++)
		{
			var cb = cbs[t];
			if (cb.checked == true)
			{
				// In other words, this group
				// was selected.
				group_id = cb.value;
			}
		}
		
		var hours = document.getElementById("hours").value;
		var type = document.getElementById("type").value;
		var min_grade = document.getElementById("min_grade").value;
		
		if (hours < 1 || group_id < 1)
		{
			alert("Please select a group and number of hours!");
			return;
		}
		
		//alert(group_id + " " + hours + " " + type + " " + min_grade);
		opener.document.getElementById("perform_action2").value="addGroup_" + group_id + "_" + semester_num + "_" + hours + "_" + type + "_" + min_grade;
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
		opener.document.getElementById("set_definition").value = def;
		opener.showUpdate();
		opener.submitForm();
		window.close();
		
	}
	
	function submitForm()
	{
		document.getElementById("scroll_top").value = document.body.scrollTop;
		document.getElementById("mainform").submit();
	}	
	
	
	function delGroup(group_id, semester_num)
	{
		var dsn = Number(semester_num) + 1;
		var x = confirm("Are you sure you want to delete this group from block " + dsn + "?");
		if (!x)
		{
			return;
		}
		
		document.getElementById("perform_action2").value="delGroup_" + group_id + "_" + semester_num;
		submitForm();
		
		
	}
	
	function confirmClearJDHistory()
	{
	 var x = confirm("Are you sure you wish to clear the advising and comment history for John Doe (student 99999999)?");
	 if (x) 
	 {
	   window.location = "admin.php?performAction=perform_clear_john_doe";
	 }
	}
	
	</script>
			';
  return $rtn;
}


?>