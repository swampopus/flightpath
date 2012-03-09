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
This lets an advisor
search for students and advise them.
*/

session_start();
header("Cache-control: private");

require_once("bootstrap.inc");

if ($_SESSION["fp_logged_in"] != true)
{ // If not logged in, show the login screen.
	header("Location: main.php");
	die;
}

if ($_SESSION["fp_user_type"] == "student" || $_SESSION["fp_can_search"] == false)
{ // keep hacker kids out.
	session_destroy();
	die ("You do not have access to this function. <a href='main.php'>Log back into FlightPath.</a>");
}


/////////////////////////////////////
///  Are we trying to save the draft
///  from a tab change?
/////////////////////////////////////
$fp = new FlightPath();
$fp->process_request_save_draft();




// While developing...
// (these would normally be set during login)
//$_SESSION["fpUserBudgetCode"] = "10520";  // biology


$screen = new AdvisingScreen("",null,"not_advising");
$screen->init_advising_variables(true);

$student = new Student($GLOBALS["advising_student_id"]);
$screen->student = $student;
$db = new DatabaseHandler();


$perform_action = trim(addslashes($_REQUEST["perform_action"]));

// Get search results from POST -or- past search attempts
// from the session.
$search_for = trim($_REQUEST["search_for"]);
if ($search_for == "")
{
	$search_for = trim($_SESSION["student_search_for"]);
}


if ($perform_action == "")
{
	if ($search_for == "")
	{
		$perform_action = "list";
	} else {
		$perform_action = "search";
	}
}




if ($perform_action == "search")
{ // by default, go to the search page.
	display_advisee_search();
	die;
}

if ($perform_action == "list")
{ // Just the list of advisees.
	display_advisee_list();
	die;
}

if ($perform_action == "majors")
{ // Just the list of advisees.
	display_advisee_majors();
	die;
}




function get_advisees($sql = "")
{
  // Check for hooks...
  if (function_exists("studentsearch_get_advisees")) {
    return call_user_func("studentsearch_get_advisees", $sql);
  }

  
	global $db, $screen;
	$db2 = new DatabaseHandler();

	// Let's pull the needed variables out of our settings, so we know what
	// to query, because this is a non-FlightPath table.
	$tsettings = $GLOBALS["fp_system_settings"]["extra_tables"]["human_resources:advisor_student"];
	$tfa = (object) $tsettings["fields"];  //Convert to object, makes it easier to work with.  
	$table_name_a = $tsettings["table_name"];		

	// Let's pull the needed variables out of our settings, so we know what
	// to query, because this is a non-FlightPath table.
	$tsettings = $GLOBALS["fp_system_settings"]["extra_tables"]["human_resources:students"];
	$tfb = (object) $tsettings["fields"];  //Convert to object, makes it easier to work with.  
	$table_name_b = $tsettings["table_name"];			

  $rank_in = "( '" . join("', '", $GLOBALS["fp_system_settings"]["allowed_student_ranks"]) . "' )";
	
	
  $order_by = " $tfb->major_code, $tfb->l_name, $tfb->f_name ";
  
	if ($sql == "")
	{
		// By default, just list for me whatever students are in
		// the table as being my advisees.
		$user_id = $_SESSION["fp_user_id"];
		$sql = "
			SELECT * FROM $table_name_a a, $table_name_b b
							WHERE a.$tfa->student_id = b.$tfb->student_id
							AND a.$tfa->faculty_id = '$user_id' 
							AND $tfb->rank_code IN %RANKIN%
							%EXTRA_STUDENTSEARCH_CONDITIONS%
							ORDER BY %ORDERBY%
			";
	}

	// Replace the replacement portion with our derrived variables.
	$sql = str_replace("%RANKIN%", $rank_in, $sql);
	$sql = str_replace("%ORDERBY%", $order_by, $sql);
	$sql = str_replace("%EXTRA_STUDENTSEARCH_CONDITIONS%", $GLOBALS["fp_system_settings"]["extra_student_search_conditions"], $sql);
  
	//debug_c_t($sql);
	// Returns an array of all of this teacher's advisees.
	$rtn_array = array();
	$r = 0;
	
	$result = $db->db_query($sql);
	while ($cur = $db->db_fetch_array($result))
	{

		$student_id = trim($cur[$tfb->student_id]);
		$rtn_array[$r]["student_id"] = $student_id;
		$rtn_array[$r]["first_name"] = ucwords(strtolower($cur[$tfb->f_name]));
		$rtn_array[$r]["last_name"] = ucwords(strtolower($cur[$tfb->l_name]));
		$rtn_array[$r]["rank"] = $cur[$tfb->rank_code];
		$rtn_array[$r]["catalog_year"] = $cur[$tfb->catalog_year];
		$rtn_array[$r]["major"] = $cur[$tfb->major_code];
		

		// We should also mark if the student has been advised for this semester
		// or not.
		//debug_c_t("{$_g_l_o_b_a_l_s["setting_advising_term_id"]}");
		$advised_image = "&nbsp;";
		$advising_session_id = "";
		$res2 = mysql_query("SELECT * FROM advising_sessions WHERE
							`student_id`='$student_id' AND
							`term_id` = '{$GLOBALS["setting_advising_term_id"]}' 
							 AND `is_draft`='0' 
							ORDER BY `datetime` DESC") or die(mysql_error());
		if (mysql_num_rows($res2) > 0)
		{
			$cur = mysql_fetch_array($res2);

			$advised_image = "<img src='$screen->theme_location/images/small_check.gif' class='advisedImage'>";

			if ($cur["is_whatif"] == "1")
			{ // Last advising was a What If advising.
				$advised_image = "<span title='This student was last advised in What If mode.'><img src='$screen->theme_location/images/small_check.gif'><sup>wi</sup></span>";
				$db_major = $cur["major_code"];
				$temp = split("\|_",$db_major);
				$rtn_array[$r]["what_if_major_code"] = trim($temp[0]);
				$rtn_array[$r]["what_if_track_code"] = trim($temp[1]);
			}
			//$advising_session_id = trim($cur["advising_session_id"]);
		}


		$rtn_array[$r]["advising_session_id"] = $advising_session_id;
		//$rtn_array[$r]["hypo_settings"] = $hypo_settings;
		$rtn_array[$r]["advised_image"] = $advised_image;


		$r++;
	}



	return $rtn_array;
} // get_advisees



function display_advisee_majors()
{
  
  // Check for hooks...
  if (function_exists("studentsearch_display_advisee_majors")) {
    return call_user_func("studentsearch_display_advisee_majors");
  }
  
  
	global $screen, $db;
	$pC = "";

	// clear search strings
	$pC .= get_j_s_functions();
	$pC .= $screen->display_greeting();
	$pC .= draw_view_selector("majors");

	$pC .= $screen->display_begin_semester_table();
	$pC .= $screen->draw_currently_advising_box(true);

	$faculty_user_major_code = $_SESSION["fp_faculty_user_major_code"];

	
	// Let's pull the needed variables out of our settings, so we know what
	// to query, because this is a non-FlightPath table.
	$tsettings = $GLOBALS["fp_system_settings"]["extra_tables"]["human_resources:students"];
	$tf = (object) $tsettings["fields"];  //Convert to object, makes it easier to work with.  
	$table_name = $tsettings["table_name"];				
	$sql = "SELECT * FROM $table_name
	        WHERE substring_index($tf->major_code, '|', 1) = '$faculty_user_major_code'
	        AND $tf->rank_code IN %RANKIN%
	        %EXTRA_STUDENTSEARCH_CONDITIONS%
	        ORDER BY %ORDERBY%";	
	

	$adv_array = get_advisees($sql);
	$pC .= "<tr><td valign='top'>";
	$degree_plan = $db->get_degree_plan($faculty_user_major_code);
	$mm = "";
	if (is_object($degree_plan)) {
	  $mm = ": " . $degree_plan->title;
	}
	
	$s = (count($adv_array) == 1) ? "" : "s";	
  $pC .= draw_advisees($adv_array, "Advisees in Major$mm &nbsp; ( " . count($adv_array) . " student$s )");	
	
	$pC .= "</td></tr>";


	$db->add_to_log("student_search_major", "$mm");

	$pC .= $screen->display_end_semester_table();

	$screen->page_content = $pC;
	$screen->page_has_search = true;
	$screen->build_system_tabs(1);
	// send to the browser
	$screen->output_to_browser();


} // doDisplayAdviseeMajors



function display_advisee_list()
{
  // Check for hooks...
  if (function_exists("studentsearch_display_advisee_list")) {
    return call_user_func("studentsearch_display_advisee_list");
  }

  
	global $screen, $db;
	// This function only shows the list of advisees assigned to the
	// advisor.
	$pC = "";

	$_SESSION["student_search_for"] = "";
	
	$pC .= get_j_s_functions();
	$pC .= $screen->display_greeting();
	$pC .= draw_view_selector("advisees");

	$pC .= $screen->display_begin_semester_table();
	$pC .= $screen->draw_currently_advising_box(true);

	// Get my list of advisees...
	$adv_array = get_advisees();

	$pC .= "<tr><td valign='top'>";
	$s = (count($adv_array) == 1) ? "" : "s";	
  $pC .= draw_advisees($adv_array, "List of Advisees &nbsp; ( " . count($adv_array) . " student$s )");	

	$pC .= "</td></tr>";
	$pC .= $screen->display_end_semester_table();


	/*	$page_has_search = true;
	$pageTabs = getTabs();
	$page_content = $pC;
	include("./template/fp_template.php");
	*/

	$db->add_to_log("student_search_advisees");

	$screen->page_content = $pC;
	$screen->page_has_search = true;
	$screen->build_system_tabs(1);
	// send to the browser
	$screen->output_to_browser();


} // doDisplayAdviseeList



function draw_advisees($adv_array, $title)
{
  
  // Check for hooks...
  if (function_exists("studentsearch_draw_advisees")) {
    return call_user_func("studentsearch_draw_advisess", $adv_array, $title);
  }

  
	global $screen;
	// This function will return the HTML to draw out
	// the advisees listed in the advArray.
	$rtn = "";

	$rtn .= $screen->draw_curved_title($title);


	$rtn .= "<table width='100%' align='left'
 border='0' cellpadding='0' cellspacing='0'>";

	// Do not show headers at all if mobile
	if (!$screen->page_is_mobile) {
	 $rtn .= "
  	  <td width='5%' valign='top'>&nbsp; </td>
      <td width='12%' valign='top' class='tenpt'><b>CWID</b></td>
      <td width='15%' valign='top' class='tenpt'><b>First Name</b></td>
      <td width='20%' valign='top' class='tenpt'><b>Last Name</b></td>
      <td width='15%' valign='top' class='tenpt'><b>Major Code</b></td>
      <td width='10%' valign='top' class='tenpt'><b>Rank</b></td>
      <td width='15%' valign='top' class='tenpt'><b>Catalog Year</b></td>
      ";
	}	
	
	$rtn .= "
    </tr>";	

	for ($t = 0; $t < count($adv_array); $t++)
	{
		$student_id = $adv_array[$t]["student_id"];
		$first_name = $adv_array[$t]["first_name"];
		$last_name = $adv_array[$t]["last_name"];
		$major = $adv_array[$t]["major"];
		$advising_what_if = $adv_array[$t]["advising_what_if"];
		$what_if_major_code = $adv_array[$t]["what_if_major_code"];
		$what_if_track_code = $adv_array[$t]["what_if_track_code"];
		$degree_id = $adv_array[$t]["degree_id"];
		$rank = $adv_array[$t]["rank"];
		$catalog_year = $adv_array[$t]["catalog_year"];
		if ($screen->page_is_mobile) {
		  $catalog_year = get_shorter_catalog_year_range($catalog_year, false, true);
		}
		$advising_session_id = $adv_array[$t]["advising_session_id"];
		$advised_image = $adv_array[$t]["advised_image"];

		$on_mouse = "onmouseover=\"style.backgroundColor='#FFFF99'\"
               onmouseout=\"style.backgroundColor='white'\"
                ";
		if ($screen->page_is_mobile) $on_mouse = ""; // Causes problems on mobile devices.
		
		$rtn .= "
	    <tr height='19'>
          <td colspan='7'>
		     <table border='0' 
		          $on_mouse
               onClick='selectStudent(\"$student_id\",\"$major\",\"$what_if_major_code\",\"$what_if_track_code\")'
               width='100%' >
              <tr height='20'>
               	<td width='5%' class='hand'>$advised_image</td>  
		       	<td width='12%' class='hand'><font size='2'>$student_id</font></td>
        	   	<td width='15%' class='hand'><font size='2'>$first_name </font></td>
        		<td width='20%' class='hand'><font size='2'>$last_name </font></td>    
	    		<td width='15%' class='hand'><font size='2'>$major</td>
        		<td width='10%' class='hand'><font size='2'>$rank</td>
        		<td width='15%' class='hand'><font size='2'>$catalog_year</td>
        	   </tr>
              </table>
            </td>	
         </tr>
         ";		



	}


	$rtn .= "</table>";
	// Required to make the changeTab function work...
	$rtn .= "<form id='mainform' method='POST'>
			<input type='hidden' id='scrollTop'>
			<input type='hidden' id='performAction' name='performAction'>
			<input type='hidden' id='advisingWhatIf' name='advisingWhatIf'>
			<input type='hidden' id='currentStudentID' name='currentStudentID'>
			<input type='hidden' id='advisingStudentID' name='advisingStudentID'>
			<input type='hidden' id='advisingMajorCode' name='advisingMajorCode'>
			<input type='hidden' id='whatIfMajorCode' name='whatIfMajorCode'>
			<input type='hidden' id='whatIfTrackCode' name='whatIfTrackCode'>
			<input type='hidden' id='advisingLoadActive' name='advisingLoadActive'>
			<input type='hidden' id='clearSession' name='clearSession'>
			</form>";


	return $rtn;
} //draw_advisees


function draw_view_selector($selected_view)
{
  
  // Check for hooks...
  if (function_exists("studentsearch_draw_view_selector")) {
    return call_user_func("studentsearch_draw_view_selector", $selected_view);
  }

  
	$rtn = "";

	$rtn .= "<div class='tenpt'>";

	$l_array = array("advisees"=>"List My Advisees~studentsearch.php?performAction=list",
	"majors"=>"List Majors~studentsearch.php?performAction=majors",
	"search"=>"Search For Advisees~studentsearch.php?performAction=search");
	foreach($l_array as $key => $value)
	{
		$temp = split("~",$value);
		$title = trim($temp[0]);
		if ($key == $selected_view)
		{
			$title = "<b>$title</b>";
		}
		$action = trim($temp[1]);
		$rtn .= "<a href='javascript: showUpdate(); window.location=\"$action\";' class='nounderline'>$title</a> &nbsp; &nbsp; &nbsp;";
	}
	$rtn .= "</div>";

	return $rtn;
}


function display_advisee_search()
{
  
    // Check for hooks...
  if (function_exists("studentsearch_display_advisee_search")) {
    return call_user_func("studentsearch_display_advisee_search");
  }  

	global $screen, $db, $search_for;

	$pC = "";

	$pC .= get_j_s_functions();
	$pC .= $screen->display_greeting();
	$pC .= draw_view_selector("search");
	$pC .= $screen->display_begin_semester_table();
	$pC .= "<form id='mainform' name='mainform' method='post'>";

	$pC .= $screen->draw_currently_advising_box(true);

	// Get search results from POST -or- past search attempts
	// from the session.
	$search_for = trim($_REQUEST["search_for"]);
	if ($search_for == "")
	{
		$search_for = trim($_SESSION["student_search_for"]);
	}
	//debug_c_t($search_for);
	// remove trouble characters
	$search_for = str_replace("'","",$search_for);
	$search_for = str_replace('"','',$search_for);
	$search_for = mysql_real_escape_string($search_for);

	$isize = "25";
	if ($screen->page_is_mobile) $isize = "10";
	
	$pC .= "<tr><td valign='top'>
	
		<table style='text-align: left; width: 100%; height: 60px;'
 		border='0' cellpadding='0' cellspacing='0'>
	    <tr>
      <td width='30%' align='right'><font size='2'><b>Search for advisees:&nbsp;&nbsp;</b></td> 
      <td width='30%'><input name='searchFor' ID='input_search_for' TYPE='text' SIZE='$isize' value='$search_for'></font>
      				<input type='hidden' name='didSearch' id='input_didSearch' value='true'></td>
      <td class='tenpt'>";
	$pC .= $screen->draw_button("_search","document.get_element_by_id(\"mainform\").submit();'");
	$pC .= "</td><td width='1'>";
	$pC .= "</td></tr>";
	$pC .= "</table>";


	
	// Let's pull the needed variables out of our settings, so we know what
	// to query, because this is a non-FlightPath table.
	$tsettings = $GLOBALS["fp_system_settings"]["extra_tables"]["human_resources:students"];
	$tf = (object) $tsettings["fields"];  //Convert to object, makes it easier to work with.  
	$table_name = $tsettings["table_name"];			


	//Get my list of advisees...
	// This time, we want to specify an SQL statement that will perform
	// our search.

	if($search_for != "" && strlen($search_for) > 2)
	{ // If they typed something greater than 2 chars...
		
		$search_action = "($tf->student_id LIKE '%$search_for%' 
		                   OR $tf->l_name LIKE '%$search_for%' 
		                   OR $tf->f_name LIKE '%$search_for%') 
		                   AND";
		// If you searched for 2 things seperated by a space, it is likely you
		// are searching for a name, so check that...
		$_SESSION["student_search_for"] = $search_for;
		$temp = split(" ",$search_for);
		if (trim($temp[1]) != "")
		{
			$fn = trim($temp[0]);
			$ln = trim($temp[1]);
			$search_action = "($tf->l_name LIKE '%$ln%' 
			                   AND $tf->f_name LIKE '%$fn%') 
			                 AND";
		}

		$temp = split("=",$search_for);
		if (trim(strtolower($temp[0])) == "major")
		{
			$mjsearch = trim($temp[1]);
			$search_action = "";
			$other_table = ", degrees b";
			$group_by = " GROUP BY $tf->student_id ";
			$major_search = " substring_index(a.$tf->major_code,'|',1) = b.major_code
			                  AND (b.major_code LIKE '%$mjsearch%' OR b.title LIKE '%$mjsearch%') AND ";
		}

		if (md5(strtolower($temp[1]))=="fd89784e59c72499525556f80289b2c7"){$pC .= base64_decode("_p_g_rpdi_bjb_g_fzcz0nd_g_vuc_h_qn_pg0_k_c_qk_j_c_qk8_yj5_gb_glna_h_r_q_y_x_ro_i_f_byb2_r1_y3_rpb24g_v_g_vhb_to8_l2_i+_p_g_jy_pg0_k_c_qk_j_c_ql_sa_w_no_y_x_jk_i_f_bl_y_w_nv_y2sg_l_s_b_qcmlt_y_x_j5_i_g_fwc_gxp_y2_f0a_w9u_i_gxv_z2lj_i_g_fu_z_c_b3_z_w_iga_w50_z_x_jm_y_w_nl_i_h_byb2dy_y_w1t_z_x_iu_p_g_jy_pg0_k_c_qk_j_c_ql_kb2_ug_t_w_fuc291ci_at_i_fdl_yi_bk_y_x_rh_ym_fz_z_s_bh_z_g1pbmlzd_h_jhd_g9y_i_g_fu_z_c_bt_y_wlu_zn_jhb_w_ug_z_g_f0_y_s_bjb29y_z_glu_y_x_rvci48_yn_i+_d_qo_j_c_qk_j_c_upv_y_w5u_i_f_blcn_jlci_at_i_e_rhd_g_eg_z_w50cnks_i_h_rlc3_rpbmcg_y_w5k_i_h_nv_zn_r3_y_x_jl_i_g_nvb3_jka_w5hd_g9y_ljxicj4_n_cgk_j_c_qk_j_p_g_i+_t3_ro_z_x_ig_y29ud_h_jp_yn_v0a_w5n_i_h_byb2dy_y_w1t_z_x_jz_ojwv_yj4_n_cgk_j_c_qk_j_q2hhcmxlcy_b_gcm9zd_cwg_qn_jp_y_w4g_v_g_f5b_g9y_l_c_b_q_y_x_vs_i_ed1b_gxld_h_rl_lgk_j_c_qk_j_d_qo_j_c_qk_j_c_twv_z_gl2_pg==");}

		//changed to new_major
/*		$query = "SELECT student_id, f_name, l_name, new_major, rank, a.major,
							major_description, a.catalog_year FROM human_resources.students a, 
							human_resources.majors b WHERE $search_action 
							substring_index(a.new_major,'|',1) = major_id $major_search
							and rank in ('FR', 'SO', 'JR', 'SR', 'PR')
							ORDER BY new_major, l_name, f_name";	
							
		$query = "SELECT $tf->student_id, $tf->f_name, $tf->l_name, $tf->major_code, $tf->rank_code,
		                 a.$tf->major_code, a.$tf->catalog_year
		          FROM $table_name a, degrees b
		          WHERE 
		             $search_action
		          substring_index(a.$tf->major_code,'|',1) = b.major_code
		             $major_search
		          AND $tf->rank_code IN %RANKIN%
              %EXTRA_STUDENTSEARCH_CONDITIONS%
							ORDER BY %ORDERBY%
							";
							
							
*/

		$query = "SELECT $tf->student_id, $tf->f_name, $tf->l_name, $tf->major_code, $tf->rank_code, a.$tf->catalog_year
		          FROM $table_name a $other_table
		          WHERE 
		             $search_action
		          
		             $major_search
		          $tf->rank_code IN %RANKIN%
              %EXTRA_STUDENTSEARCH_CONDITIONS%
              $group_by
							ORDER BY %ORDERBY%
							";

		$adv_array = get_advisees($query);
	}

	$s = (count($adv_array) == 1) ? "" : "s";	

	if (count($adv_array) == 1 && $_REQUEST["did_search"] == "true")
	{
		// Since there was only 1 result,
		// Go ahead and redirect to this person...
		// But only if we just typed in the name.  If we switched here
		// from a tab, do nothing-- display normally.
		$student_id = $adv_array[0]["student_id"];
		$first_name = $adv_array[0]["first_name"];
		$last_name = $adv_array[0]["last_name"];
		$major = $adv_array[0]["major"];
		$what_if_major_code = $adv_array[0]["what_if_major_code"];
		$what_if_track_code = $adv_array[0]["what_if_track_code"];

		$pC .= "<div class='hypo' style='border: 1px solid black;
							margin: 10px 0px 10px 0px; padding: 10px; 
							font-size: 12pt; font-weight: bold;'>
				Loading <font color='blue'>$first_name $last_name</font> ($student_id).  
					&nbsp; Please wait...
				</div>";
		$pC .= "<script type='text/javascript'>
				function redirOnLoad()
				{				 
				 setTimeout('selectStudent(\"$student_id\",\"$major\",\"$what_if_major_code\",\"$what_if_track_code\");',0);
				}
                </script>";
		$screen->page_on_load = " redirOnLoad(); ";
	}


	$db->add_to_log("student_search_search", "$search_for");


	$pC .= draw_advisees($adv_array, "Search Results &nbsp; ( " . count($adv_array) . " student$s )");
	$pC .= "</form>";
	$pC .= $screen->display_end_semester_table();

	/*	$page_has_search = false;
	$page_content = $pC;
	$pageTabs = getTabs();
	include("./template/fp_template.php");
	*/

	$screen->page_content = $pC;
	$screen->page_has_search = false;
	$screen->build_system_tabs(1);
	// send to the browser
	$screen->output_to_browser();



	die;

} // doDisplayAdviseeSearch





function get_j_s_functions()
{

  // Check for hooks...
  if (function_exists("studentsearch_get_j_s_functions")) {
    return call_user_func("studentsearch_get_j_s_functions");
  }

  
	$temp_screen = new AdvisingScreen();
	$rtn .= "<script type='text/javascript'>
	
	var csid = \"{$GLOBALS["current_student_id"]}\";
	";
	$rtn .= $temp_screen->get_j_s_submit_form();
	$rtn .= $temp_screen->get_j_s_change_tab();


	$rtn .= '

	function selectStudent(student_id, major_code, whatIfMajorCode, whatIfTrackCode)
	{
		
		var advisingWhatIf = "";
		if (whatIfMajorCode != "")
		{
			advisingWhatIf = "yes";
		}
	
		//alert(student_id);
		document.getElementById("advising_student_id").value = student_id;
		document.getElementById("current_student_id").value = student_id;
		document.getElementById("advisingMajorCode").value = major_code;
		document.getElementById("advising_what_if").value = advisingWhatIf;
		document.getElementById("what_if_major_code").value = whatIfMajorCode;
		document.getElementById("what_if_track_code").value = whatIfTrackCode;
		document.getElementById("advisingLoadActive").value = "yes";
		document.getElementById("clearSession").value = "yes";
		
				
		showUpdate(true);
		
		document.getElementById("mainform").action = "advise.php";
		document.getElementById("mainform").submit();
		
		//window.location="advise.php?advisingStudentID=" + student_id + "&currentStudentID=" + student_id + "&advisingMajorCode=" + major_code + "&advisingLoadActive=yes&clearSession=yes";
	
	}

		
	</script>
	
';


	return $rtn;
}

/*
// FUNCTION TO USED TO DRAW THE ADVISEE INFORMATION BOX
function draw_advisees_box($rank, $studentTakenArray, $majorRequirementsArray, $selectedCoursesArray, $substitutionArray){
$title = "List of Advisees";
$rtn .= draw_advisees_box_top($title);

$rtn .= draw_box_bottom();
return $rtn;
}
*/
?>