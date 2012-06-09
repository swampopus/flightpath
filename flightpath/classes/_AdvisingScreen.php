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

class _AdvisingScreen
{
	public $width_array, $popup_width_array, $script_filename, $is_on_left, $box_array;
	public $degree_plan, $student, $bool_popup, $footnote_array, $flightpath;
	public $screen_mode, $db, $bool_print, $view, $settings, $user_settings;
	public $bool_blank, $bool_hiding_grades;
	public $admin_message, $earliest_catalog_year;

	// Variables for the template/theme output...
	public $theme_location, $page_content, $page_has_search, $page_tabs, $page_on_load;
	public $page_hide_report_error, $page_scroll_top, $page_is_popup, $page_is_mobile;
	public $page_title, $page_extra_css_files;
  


	/**
	 * This is the constructor.  Must be named this for inheritence to work
	 * correctly.
	 *
	 * @param string $script_filename
	 *   - This is the script which forms with POST to.  Ex: "advise.php"
	 * 
	 * @param FlightPath $flightpath   
	 *   - FlightPath object.
	 *
	 * @param string $screen_mode
	 *   - A string describing what "mode" we are in.  
	 *     - If left blank, we assume it is full-screen and normal.
	 *     - If set to "popup" then we are in a popup window, and we will
	 *       not draw certain elements.
	 *  
	 */
	function __construct($script_filename = "", FlightPath $flightpath = null, $screen_mode = "")
	{
		$this->width_array = Array("10%", "8%","8%", "17%", "26%", "10%", "10%", "9%");
		$this->popup_width_array = Array("17%", "1%", "1%", "15%", "26%", "15%", "15%", "10%");
		
		$this->script_filename = $script_filename;
		$this->is_on_left = true;
		$this->box_array = array();
		$this->footnote_array = array();
		
		$this->page_extra_css_files = array();

		$this->flightpath = $flightpath;
		$this->degree_plan = $flightpath->degree_plan;
		$this->student = $flightpath->student;

		$this->db = get_global_database_handler();

		if ($screen_mode == "popup")
		{
			$this->bool_popup = true;
		}

		$this->bool_blank = false;

		$this->screen_mode = $screen_mode;

		//$this->settings = $this->db->get_flightpath_settings();
	
		$this->earliest_catalog_year = $GLOBALS["fp_system_settings"]["earliest_catalog_year"];
		
		$this->determine_mobile_device();
				
	}

	

	/**
	 * This function will attempt to determine automatically
	 * if we are on a mobile device.  If so, it will set
	 * $this->page_is_mobile = TRUE
	 *
	 */
function determine_mobile_device(){
  $user_agent = $_SERVER['HTTP_USER_AGENT']; 

  $look_for = array(
    "ipod", 
    "iphone", 
    "android", 
    "opera mini", 
    "blackberry",
    "(pre\/|palm os|palm|hiptop|avantgo|plucker|xiino|blazer|elaine)",
    "(iris|3g_t|windows ce|opera mobi|windows ce; smartphone;|windows ce; iemobile)",
    "(smartphone|iemobile)",
    );
  
  foreach ($look_for as $test_agent) {   
    if (preg_match('/' . $test_agent . '/i',$user_agent)) {
       $this->page_is_mobile = true;
       break;
    }
  }  
  
  
  $GLOBALS["fp_page_is_mobile"] = $this->page_is_mobile;
  
} // ends function mobile_device_detect
	


/**
 * This function will return the HTML to contruct a collapsible fieldset,
 * complete with javascript and style tags.
 *
 * @param String $content
 * @param String $legend
 * @param bool $bool_start_closed
 * @return String
 */
function draw_c_fieldset($content, $legend = "Click to expand/collapse", $bool_start_closed = false)
{
  
  // Create a random ID for this fieldset, js, and styles.
  $id = md5(rand(9,99999) . time());
  
  $start_js_val = 1;
  $fsstate = "open";
  $content_style = "";
  
  if ($bool_start_closed) {
    $start_js_val = 0;
    $fsstate = "closed";
    $content_style = "display: none;";
  }
  
  $js = "<script type='text/javascript'>
  
  var fieldset_state_$id = $start_js_val;
  
  function toggle_fieldset_$id() {
    
    var content = document.getElementById('content_$id');
    var fs = document.getElementById('fs_$id');
      
    if (fieldset_state_$id == 1) {
      // Already open.  Let's close it.
      fieldset_state_$id = 0;
      content.style.display = 'none';
      fs.className = 'c-fieldset-closed-$id';
    }
    else {
      // Was closed.  let's open it.
      fieldset_state_$id = 1;
      content.style.display = '';
      fs.className = 'c-fieldset-open-$id';      
    }  
  }  
  </script>";
  
  $rtn = "  
    <fieldset class='c-fieldset-$fsstate-$id' id='fs_$id'>
      <legend><a href='javascript: toggle_fieldset_$id();' class='nounderline'>$legend</a></legend>
      <div id='content_$id' style='$content_style'>
        $content
      </div>
    </fieldset>
    $js  
    
  <style>
  fieldset.c-fieldset-open-$id {
    border: 1px solid;
  }

  fieldset.c-fieldset-closed-$id {
    border: 1px solid;
    border-bottom-width: 0;
    border-left-width: 0;
    border-right-width: 0;    
  }  

  legend a {
    text-decoration: none;
  }
  
  </style>
    
  ";
  
  
  return $rtn;
}




/**
 * Simply builds a single menu item.
 *
 * @return string
 */
function draw_menu_item($url, $target, $icon_img, $title, $description = "") {
  
  $rtn = "";
  
  if (!$description) $extra_class = "fp-menu-item-tight";
  
  $rtn .= "<div class='fp-menu-item $extra_class'>
            <div class='fp-menu-item-link-line'>
              <a href='$url' target='$target'>$icon_img $title</a>
            </div>
            ";
  if ($description) {
    $rtn .= " <div class='fp-menu-item-description'>$description</div>";
  }
  $rtn .= "</div>";  
  
  return $rtn;
}


/**
 * Uses the draw_menu_item method to draw the HTML for
 * all the supplied menu items, assuming the user has
 * permission to view them.
 * 
 * Returns the HTML or "" if no menus could be drawn.
 *
 * @param unknown_type $menu_array
 */
function draw_menu_items($menu_array) {

  $rtn = "";
  
  if (count($menu_array) == 0) return "";
  
  
  foreach($menu_array as $item) {
    $url = $item["url"];
    $target = $item["target"];
    $icon = $item["icon"];
    if ($icon) {
      $icon_img = "<img src='$icon' border='0'>";
    }
    else {
      $icon_img = "<span class='fp-menu-item-no-icon'></span>";
    }
    
    $title = $item["title"];
    $description = $item["description"];
    
    // Make sure they have permission!
    if ($item["permission"] != "") {
      if (!user_has_permission($item["permission"])) {
        // User did NOT have permission to view this link.
        continue;
      }
    }    
    
    $rtn .= $this->draw_menu_item($url, $target, $icon_img, $title, $description);
    
  }      
  
  return $rtn;
  
}

	
	/**
	 * This method outputs the screen to the browser by performing
	 * an include(path-to-theme-file.php).  All necessary information
	 * must be placed into certain variables before the include happens.
	 * 
	 */
	function output_to_browser()
	{
		// This method will output the screen to the browser.
		// outputs the $page_content variable.
				
		$page_content = $this->page_content;
		$page_tabs = $this->page_tabs;
		$page_has_search = $this->page_has_search;
		$page_on_load = $this->page_on_load;
		$page_scroll_top = $this->page_scroll_top;
		$page_is_popup = $this->page_is_popup;
		$page_title = $this->page_title;
		$page_hide_report_error = $this->page_hide_report_error;
    $page_extra_css_files = $GLOBALS["fp_extra_css"];		
		$page_extra_js_files = $GLOBALS["fp_extra_js"];
		$print_option = "";
		if ($this->bool_print == true)
		{
			$print_option = "print_";
		}

		if ($this->page_is_mobile == true)
		{
		  $print_option = "mobile_";
		}
					
		include($GLOBALS["fp_system_settings"]["theme"] . "/fp_" . $print_option . "template.php");
	}

	
	
	/**
	 * This function simply adds a reference for additional CSS to be
	 * link'd in to the theme.  It is used by add-on modules.
	 * 
	 * The filename needs to be from the reference of the base
	 * FlightPath install.
	 * 
	 * Ex:  $screen->add_css("modules/course_search/css/style.css");
	 *
	 * @param String $filename
	 */
	function add_css($filename) {

	  $this->page_extra_css_files[] = $filename;
	  
	}
	
	
	
	/**
	 * Converts a string containing BBCode to the equivalent HTML.
	 *
	 * @param string $str
	 * @return string
	 */
	function z__convert_bbcode_to_html($str)
	{
		// This will accept a string with BBcode tags in it,
		// and convert them to HTML tags.
		$str = str_replace("[b]","<b>",$str);
		$str = str_replace("[/b]","</b>",$str);

		$str = str_replace("[i]","<i>",$str);
		$str = str_replace("[/i]","</i>",$str);

		$str = str_replace("[u]","<u>",$str);
		$str = str_replace("[/u]","</u>",$str);

		$str = str_replace("[center]","<center>",$str);
		$str = str_replace("[/center]","</center>",$str);

		$str = str_replace("[ul]","<ul>",$str);
		$str = str_replace("[/ul]","</ul>",$str);

		$str = str_replace("[li]","<li>",$str);
		$str = str_replace("[/li]","</li>",$str);


		$str = str_replace("[br]","<br>",$str);

		// convert more than 1 space into 2 hard spaces...
		$str = str_replace("  ","&nbsp;&nbsp;",$str);


		// Check for colored text
		$str = preg_replace("(\[color=(.+?)\](.+?)\[\/color\])is","<span style='color:$1;'>$2</span>",$str);

		// valid URL characters...
		$url_search_string = " a-zA-Z0-9\:\/\-\?\&\.\=\_\~\#\'";
		// Check for a link...
		$str = preg_replace("(\[url\=([$url_search_string]*)\](.+?)\[/url\])", "<a href='$1' target='_blank' class='nounderline'>$2</a>", $str);
		// check for a link that does NOT load in a new window (URL2)
		$str = preg_replace("(\[url2\=([$url_search_string]*)\](.+?)\[/url2\])", "<a href='$1'>$2</a>", $str);
		// check for a link to a popup....
		$str = preg_replace("(\[popup\=([$url_search_string]*)\](.+?)\[/popup\])", "<a href='javascript: popupHelpWindow(\"$1\");' class='nounderline'>$2</a>", $str);
		// Images...  (looks like: [img]http://www.image.jpg[/img]
		//$str = preg_replace("(\[img\]([$url_search_string]*)\](.+?)\[/img\])", "<img src='$1' border='0'>", $str);

		// Images
		// [img]pathtoimage[/img]
		$str = preg_replace("/\[img\](.+?)\[\/img\]/", "<img src='$1' border='0'>", $str);

		// [img=widthxheight]image source[/img]
		$str = preg_replace("/\[img\=([0-9]*)x([0-9]*)\](.+?)\[\/img\]/", "<img src='$3' width='$1' height='$2' border='0'>", $str);



		return $str;
	}


/**
 * Clear the session varibles.
 *
 */
	function clear_variables()
	{
		// Clear the session variables.
		$csid = $_REQUEST["current_student_id"];

		$_SESSION["advising_student_id$csid"] = "";
		$_SESSION["advising_student_id"] = "";
		$_SESSION["advising_major_code$csid"] = "";
		$_SESSION["advising_track_code$csid"] = "";
		$_SESSION["advising_term_id$csid"] = "";
		$_SESSION["advising_what_if$csid"] = "";
		$_SESSION["what_if_major_code$csid"] = "";

		$_SESSION["cache_f_p$csid"] = "";
		$_SESSION["cache_what_if$csid"] = "";

	}


	/**
	 * Constructs the HTML which will be used to display
	 * the student's transfer credits
	 *
	 */
	function build_transfer_credit()
	{
		$pC = "";
		$is_empty = true;
		$pC .= $this->draw_semester_box_top("Transfer Credit", true);
		// Basically, go through all the courses the student has taken,
		// And only show the transfers.  This is similar to Excess credit.



		$this->student->list_courses_taken->sort_alphabetical_order(false, true);
		$this->student->list_courses_taken->reset_counter();
		while($this->student->list_courses_taken->has_more())
		{
			$course = $this->student->list_courses_taken->get_next();

			// Skip non transfer credits.
			if ($course->bool_transfer != true)
			{
				continue;
			}

			$bool_add_footnote = false;
			if ($course->bool_has_been_displayed == true)
			{ // Show the footnote if this has already been displayed
				// elsewhere on the page.
				$bool_add_footnote = true;
			}

			$pC .= $this->draw_course_row($course,"","",false,false,$bool_add_footnote,true);
			$is_empty = false;

		}



		if ($GLOBALS["advising_course_has_asterisk"] == true)
		{
			$pC .= "<tr>
				<td colspan='10'>
				<div class='tenpt' style='margin-top: 10px; padding: 3px;'>
				<b>*</b> Courses marked with an asterisk (*) have
					equivalencies at {$GLOBALS["fp_system_settings"]["school_initials"]}.  
					Click on the course for more
					details.			
				</div>		
				</td>
				</tr>
				";
		}

		$pC .= $this->draw_semester_box_bottom();

		if (!$is_empty)
		{
			$this->add_to_screen($pC);
		}

	}




	/**
	 * Constructs the HTML to show which courses have been added
	 * by an advisor.
	 *
	 */
	function build_added_courses()
	{

		$pC = "";


		$semester = new Semester(-88);
		if ($new_semester = $this->degree_plan->list_semesters->find_match($semester))
		{
			$this->add_to_screen($this->display_semester($new_semester));
		}
	}



  /**
   * Constructs the HTML to show the Excess Credits list.
   *
   */
	function build_excess_credit()
	{

		$pC = "";
		$pC .= $this->draw_semester_box_top(t("Excess Credits"));
		$is_empty = true;

		// Basically, go through all the courses the student has taken,
		// selecting out the ones that are not fulfilling any
		// requirements.
		$this->student->list_courses_taken->sort_alphabetical_order();
		$this->student->list_courses_taken->reset_counter();
		while($this->student->list_courses_taken->has_more())
		{
			$course = $this->student->list_courses_taken->get_next();

			if ($course->bool_has_been_displayed == true)
			{ // Skip ones which have been assigned to groups or semesters.
				continue;
			}

			// Skip transfer credits.
			if ($course->bool_transfer == true)
			{
				continue;
			}

			// Skip substitutions
			if ($course->bool_substitution == true)
			{
				continue;
			}
      
			$pC .= $this->draw_course_row($course,"","",false,false);
			$is_empty = false;
		}


		$pC .= $this->draw_semester_box_bottom();

		if (!$is_empty)
		{
			$this->add_to_screen($pC);
		}
	}


	/**
	 * Constructs the HTML which will show footnotes for substitutions
	 * and transfer credits.
	 *
	 */
	function build_footnotes()
	{
		// Display the footnotes & messages.

		$pC = "";
		$is_empty = true;
		$pC .= $this->draw_semester_box_top(t("Footnotes & Messages"), true);

		$pC .= "<tr><td colspan='8' class='tenpt'>
					";
		$fn_type_array = array("substitution","transfer");
		$fn_char = array("substitution" => "S", "transfer"=>"T");
		$fn_name = array("substitution" => t("Substitutions"), 
		                "transfer" => t("Transfer Equivalency Footnotes"));
		$fn_between = array("substitution" => t("for"),
		                   "transfer" => t("for") . " {$GLOBALS["fp_system_settings"]["school_initials"]}'s");
		for ($xx = 0; $xx <= 1; $xx++)
		{
			$fn_type = $fn_type_array[$xx];
			if (count($this->footnote_array[$fn_type]) < 1)
			{
				continue;
			}

			$pC .= "<div style='padding-bottom: 10px;'>
						<b>{$fn_name[$fn_type]}</b>";
			$is_empty = false;
			for ($t = 1; $t <= count($this->footnote_array[$fn_type]); $t++)
			{
				$line = $this->footnote_array[$fn_type][$t];

				if ($line == "")
				{
					continue;
				}

				$extra = ".";

				$temp = split(" ~~ ", $line);
				$o_course = trim($temp[0]);
				$new_course = trim($temp[1]);
				$using_hours = trim($temp[2]);
				if ($using_hours != "")
				{
					$using_hours = "($using_hours hrs)";
				}
				$in_group = trim($temp[3]);

				$fbetween = $fn_between[$fn_type];

				if ($in_group > 0 && $fn_type=="substitution")
				{
					$new_group = new Group();
					$new_group->group_id = $in_group;
					$new_group->load_descriptive_data();
					$extra = "<div style='padding-left:45px;'><i>" . t("in") . " $new_group->title.</i></div>";
					if ($new_course == $o_course || $o_course == "")
					{
						$o_course = t("was added");
						$fbetween = "";
						$extra = str_replace("<i>" . t("in"), "<i>" . t("to"), $extra);
					}
				}



				$pC .= "<div class='tenpt'>&nbsp; &nbsp;
					<sup>{$fn_char[$fn_type]}$t</sup>
					$new_course $using_hours $fbetween $o_course$extra</div>";

			}
			$pC .= "</div>";
		}


		////////////////////////////////////
		////  Moved Courses...
		$m_is_empty = true;
		$pC .= "<!--MOVEDCOURSES-->";
		$this->student->list_courses_taken->sort_alphabetical_order();
		$this->student->list_courses_taken->reset_counter();
		while($this->student->list_courses_taken->has_more())
		{
			$c = $this->student->list_courses_taken->get_next();
			// Skip courses which haven't had anything moved.
			if ($c->group_list_unassigned->is_empty == true)
			{	continue;	}

			if ($c->course_id > 0)
			{	$c->load_descriptive_data();	}

			$l_s_i = $c->subject_id;
			$l_c_n = $c->course_num;
			$l_term = $c->get_term_description(true);

			$pC .= "<div class='tenpt' style='padding-left: 10px; padding-bottom: 5px;'>
							$l_s_i $l_c_n ($c->hours_awarded " . t("hrs") . ") - $c->grade - $l_term
								";
			
			$c->group_list_unassigned->reset_counter();
			while($c->group_list_unassigned->has_more())
			{
				$group = $c->group_list_unassigned->get_next();
				$group->load_descriptive_data();
				$group_title = "";
				if ($group->group_id > 0)
				{
					$group_title = "<i>$group->title</i>";
				} else {
					$group_title = t("the degree plan");
				}
				$pC .= t("was removed from") . " $group_title.";
			}



			$pC .= "</div>";

			$m_is_empty = false;
			$is_empty = false;
		}

		if ($m_is_empty == false)
		{
			$mtitle = "<div style='padding-bottom: 10px;'>
						<div style='padding-bottom: 5px;'>
						<b>" . t("Moved Courses") . "</b><br>
				" . t("Some courses have been moved out of their 
				original positions on your degree plan.") . "</div>";
			$pC = str_replace("<!--MOVEDCOURSES-->",$mtitle,$pC);
			$pC .= "</div>";
		}



		// For admins only....
		if (user_has_permission("can_substitute")) {
			if ($this->bool_print != true)
			{// Don't display in print view.
				$pC .= "<div style='tenpt'>				
					<a href='javascript: popupWindow2(\"" . base_path() . "/advise/popup-toolbox/transfers\",\"\");'><img src='" . fp_theme_location() . "/images/toolbox.gif' border='0'>" . t("Administrator's Toolbox") . "</a>
				</div>";
				$is_empty = false;
			}
		}


  	$pC .= "</td></tr>";

		$pC .= $this->draw_semester_box_bottom();

		if (!$is_empty)
		{
			$this->add_to_screen($pC);
		}
	}


  /**
   * Used in the Toolbox popup, this will display content of the tab which
   * shows a student's substututions
   *
   * @return string
   */
	function display_toolbox_substitutions()
	{
		$pC = "";
		// This will display the substitution management screen.

		$pC .= fp_render_curved_line(t("Manage Substitutions"));

		$pC .= "<div class='tenpt'>
				" . t("The following substitutions have been made for this student:") . "
				<br><br>
				";
		$is_empty = true;


		$this->student->list_substitutions->reset_counter();
		while ($this->student->list_substitutions->has_more())
		{
			$substitution = $this->student->list_substitutions->get_next();

			$course_requirement = $substitution->course_requirement;
			$subbed_course = $substitution->course_list_substitutions->get_first();

			$sub_s_i = $subbed_course->subject_id;
			$sub_c_n = $subbed_course->course_num;

			$cr_s_i = $course_requirement->subject_id;
			$cr_c_n = $course_requirement->course_num;
			$cr_hrs = $course_requirement->get_hours();

			$in_group = ".";
			if ($subbed_course->assigned_to_group_id > 0)
			{
				$new_group = new Group();
				$new_group->group_id = $subbed_course->assigned_to_group_id;
				$new_group->load_descriptive_data();

				$in_group = " in $new_group->title.";
			}

			$sub_action = t("was substituted for");
			$sub_trans_notice = "";
			if ($substitution->bool_group_addition == true)
			{
				$sub_action = t("was added to");
				$cr_s_i = $cr_c_n = "";
				$in_group = str_replace("in","",$in_group);
			}

			if ($subbed_course->bool_transfer == true && is_object($subbed_course->course_transfer))
			{
				$sub_s_i = $subbed_course->course_transfer->subject_id;
				$sub_c_n = $subbed_course->course_transfer->course_num;
				$sub_trans_notice = "[" . t("transfer") . "]";
			}

			$by = $remarks = "";
			$temp = $this->db->get_substitution_details($subbed_course->db_substitution_id);
			$by = $this->db->get_faculty_name($temp["faculty_id"], false);
			$remarks = $temp["remarks"];
			$ondate = format_date($temp["posted"]);
			
			if ($by != "")
			{
				$by = " <br>&nbsp; &nbsp; " . t("Substitutor:") . " $by. 
						<br>&nbsp; &nbsp; <i>$ondate.</i>";
			}

			if ($remarks != "")
			{
				$remarks = " <br>&nbsp; &nbsp; " . t("Remarks:") . " <i>$remarks</i>.";
			}


			$extra = "";
			if ($substitution->bool_outdated)
			{
				$extra = " <span style='color:red'>[OUTDATED: ";
				$extra .= $substitution->outdated_note;
				$extra .= "]</span>";
			}

			$pC .= "<div class='tenpt' style='margin-bottom: 20px;'>
						$sub_s_i $sub_c_n $sub_trans_notice ($subbed_course->substitution_hours hrs) $sub_action
						$cr_s_i $cr_c_n$in_group $by$remarks $extra
						<br>
							<a href='javascript: popupRemoveSubstitution(\"$subbed_course->db_substitution_id\");'>" . t("Remove substitution?") . "</a>
					</div>";

			$is_empty = false;
		}

		if ($is_empty == true)
		{
			$pC .= "<div align='center'>" . t("No substitutions have been made for this student.") . "</div>";
		}

		$pC .= "</div>";

		$this->db->add_to_log("toolbox", "substitutions");

		return $pC;
	}

  /**
   * Used in the Toolbox popup, this will display content of the tab which
   * shows a student's transfers
   *
   * @return string
   */
	function display_toolbox_transfers()
	{
		$pC = "";
		// This will display the substitution management screen.

		$pC .= fp_render_curved_line(t("Manage Transfer Equivalencies"));

		$pC .= "<div class='tenpt'>
				" . t("This student has the following transfer credits and equivalencies.") . "
				<br><br>
				";
		$is_empty = true;

		$this->student->list_courses_taken->sort_alphabetical_order(false, true);
		$this->student->list_courses_taken->reset_counter();
		while($this->student->list_courses_taken->has_more())
		{
			$c = $this->student->list_courses_taken->get_next();

			// Skip non transfer credits.
			if ($c->bool_transfer != true)
			{
				continue;
			}

			if ($c->course_id > 0)
			{
				$c->load_descriptive_data();
			}
			$course = $c->course_transfer;

			$course->load_descriptive_transfer_data();

			$l_s_i = $c->subject_id;
			$l_c_n = $c->course_num;
			$l_title = $this->fix_course_title($c->title);

			$t_s_i = $course->subject_id;
			$t_c_n = $course->course_num;
			$t_term = $c->get_term_description(true);
			$grade = $c->grade;
			if ($grade == "W" || $grade == "F" || $grade == "NC" || $grade == "I")
			{
				$grade = "<span style='color: red;'>$grade</span>";
			}

			$t_inst = $this->fix_institution_name($course->institution_name);

			$pC .= "<div class='tenpt' style='padding-bottom: 15px;'>
							<b>$t_s_i $t_c_n</b> ($c->hours_awarded hrs) - $grade - $t_term - $t_inst
								";
			if ($c->bool_substitution_split == true)
			{
				$pC .= "<div class='tenpt'><b> +/- </b> This course's hours were split in a substitution.</div>";
			}
			$initials = $GLOBALS["fp_system_settings"]["school_initials"];
			// Does this course NOT have an equivalency?
			if ($c->course_id == 0)
			{
				// But, has the eqv been removed?  If so, display a link to restore it,
				// if not, show a link to remove it!
				if ($rC = $this->student->list_transfer_eqvs_unassigned->find_match($course))
				{
					// Yes, the eqv WAS removed (or unassigned)
					$pC .= "<div class='tenpt'>" . t("This course's @initials equivalency was removed for this student.", array("@initials" => $initials)) . "<br>
							<a href='javascript: popupRestoreTransferEqv(\"$rC->db_unassign_transfer_id\")'>" . t("Restore?") . "</a></div>";
				} else {
					$pC .= "<div class='tenpt'>" . t("@initials equivalency not yet entered (or is not applicable).", array("@initials" => $initials)) . "</div>";
				}
			} else {
				// This course *DOES* have an equivalency.
				$pC .= "<div class='tenpt'>$initials eqv: $l_s_i $l_c_n - $l_title</div>";

				$pC .= "<div class='tenpt' align='right'>
							<a href='javascript: popupUnassignTransferEqv(\"" . $course->course_id . "\");'>" . t("Remove this equivalency?") . "</a>
							</div>";

			}

			$pC .= "</div>";

			$is_empty = false;
		}

		if ($is_empty == true)
		{
			$pC .= "<div align='center'>" . t("There are no transfer equivalencies for this student.") . "</div>";
		}

		$pC .= "</div>";

		$this->db->add_to_log("toolbox", "transfers");

		return $pC;
	}


  /**
   * Used in the Toolbox popup, this will display content of the tab which
   * shows a student's courses which they have taken.
   *
   * @return string
   */
	function display_toolbox_courses()
	{
		$pC = "";

		$pC .= fp_render_curved_line(t("All Student Courses"));

		$csid = $_REQUEST["current_student_id"];
		$order = $_REQUEST["order"];
		if ($order == "name")
		{
			$ns = "font-weight: bold; color: black; text-decoration: none;";
		} else {
			$os = "font-weight: bold; color: black; text-decoration: none;";
		}

		$pC .= "<div class='tenpt'>
				" . t("This window displays all of the student's courses
				which FlightPath is able to load.") . "					
				<br><br>
				" . t("Order by:") . " &nbsp; &nbsp;";
    $pC .= l(t("Name"), "advise/popup-toolbox/courses", "order=name", array("style" => $ns)) . "&nbsp; &nbsp;";
    $pC .= l(t("Date Taken"), "advise/popup-toolbox/courses", "order=date", array("style" => $os));

		$pC .= "<hr>
				<table border='0' cellpadding='2'>
					";
		$is_empty = true;
		if ($order == "name")
		{
			$this->student->list_courses_taken->sort_alphabetical_order();
		} else {
			$this->student->list_courses_taken->sort_most_recent_first();
		}
		$this->student->list_courses_taken->reset_counter();
		while($this->student->list_courses_taken->has_more())
		{
			$c = $this->student->list_courses_taken->get_next();

			if ($c->course_id > 0)
			{
				$c->load_descriptive_data();
			}

			$l_s_i = $c->subject_id;
			$l_c_n = $c->course_num;
			$eqv_line = "";

			if ($c->course_transfer->course_id > 0)
			{
				if ($c->course_id > 0)
				{
					$eqv_line = "<tr>
							<td colspan='8' class='tenpt'
								style='padding-left: 20px;'>
								<i>*eqv to {$GLOBALS["fp_system_settings"]["school_initials"]} $l_s_i $l_c_n</i></td>
							</tr>";
				}
				$l_s_i = $c->course_transfer->subject_id;
				$l_c_n = $c->course_transfer->course_num;

			}


			$l_title = $this->fix_course_title($c->title);
			$l_term = $c->get_term_description(true);

			$h = $c->hours_awarded;
			if ($c->bool_ghost_hour) {
			  $h .= "(" . t("ghost") . "<a href='javascript:alertSubGhost()'>?</a>)";
			}
			
			$pC .= "<tr>
						<td valign='top' class='tenpt'>$l_s_i</td>
						<td valign='top' class='tenpt'>$l_c_n</td>
						<td valign='top' class='tenpt'>$h</td>
						<td valign='top' class='tenpt'>$c->grade</td>
						<td valign='top' class='tenpt'>$c->term_id</td>
						";
			$pC .= "<td valign='top' class='tenpt'>";

			if ($c->bool_transfer) {$pC .= "T ";}


			if ($c->bool_substitution) {$pC .= "S ";}



			if ($c->bool_has_been_assigned)
			{
				$pC .= "A:";
				if ($c->assigned_to_group_id == 0)
				{
					$pC .= "degree plan";
				} else {
					$temp_group = new Group();
					$temp_group->group_id = $c->assigned_to_group_id;
					$temp_group->load_descriptive_data();
					$pC .= $temp_group->title;
				}


			}
			$pC .= "</td>";



			$pC .= "</tr>$eqv_line";

			$is_empty = false;
		}

		if ($is_empty == true)
		{
			$pC .= "<div align='center'>" . t("No courses have been moved for this student.") . "</div>";
		}

		$pC .= "</table>";

		$pC .= "</div>";

		$this->db->add_to_log("toolbox", "courses,$order");

		return $pC;
	}


  /**
   * Used in the Toolbox popup, this will display content of the tab which
   * shows a student's moved courses. That is, courses which have had
   * their group memberships changed.
   *
   * @return string
   */
	function display_toolbox_moved()
	{
		$pC = "";


		$pC .= fp_render_curved_line(t("Manage Moved Courses"));

		$pC .= "<div class='tenpt'>
				" . t("This student has the following course movements.") . "
				<br><br>
				";
		$is_empty = true;

		$this->student->list_courses_taken->sort_alphabetical_order();
		$this->student->list_courses_taken->reset_counter();
		while($this->student->list_courses_taken->has_more())
		{
			$c = $this->student->list_courses_taken->get_next();

			// Skip courses which haven't had anything moved.
			if ($c->group_list_unassigned->is_empty == true)
			{
				continue;
			}


			if ($c->course_id > 0)
			{
				$c->load_descriptive_data();
			}

			$l_s_i = $c->subject_id;
			$l_c_n = $c->course_num;
			$l_title = $this->fix_course_title($c->title);
			$l_term = $c->get_term_description(true);

			$h = $c->hours_awarded;
			if ($c->bool_ghost_hour) {
			  $h .= " [" . t("ghost") . "<a href='javascript:alertSubGhost();'>?</a>] ";
			}
			
			$pC .= "<div class='tenpt' style='padding-bottom: 15px;'>
							<b>$l_s_i $l_c_n</b> ($h " . t("hrs") . ") - $c->grade - $l_term
								";

			$c->group_list_unassigned->reset_counter();
			while($c->group_list_unassigned->has_more())
			{
				$group = $c->group_list_unassigned->get_next();
				$group->load_descriptive_data();
				$group_title = "";
				if ($group->group_id > 0)
				{
					$group_title = "<i>$group->title</i>";
				} else {
					$group_title = t("the degree plan");
				}
				$pC .= "<div class='tenpt'>" . t("This course was removed from") . " $group_title.<br>
							<a href='javascript: popupRestoreUnassignFromGroup(\"$group->db_unassign_group_id\")'>" . t("Restore?") . "</a>
							</div>
							";
			}



			$pC .= "</div>";

			$is_empty = false;
		}

		if ($is_empty == true)
		{
			$pC .= "<div align='center'>" . t("No courses have been moved for this student.") . "</div>";
		}

		$pC .= "</div>";

		$this->db->add_to_log("toolbox", "moved");

		return $pC;
	}


/**
 * Constructs the HTML to show the student's test scores.
 *
 */
	function build_test_scores()
	{
		// This function will build our Test Scores box.
		// Only do this if the student actually has any test scores.

		if ($this->student->list_standardized_tests->is_empty)
		{
			return;
		}

		$top_scores = array();

		$pC = "";
		$pC .= $this->draw_semester_box_top(t("Test Scores"), TRUE);

		$pC .= "<tr><td colspan='8' class='tenpt'>
					";
    
    $fsC = "";
    
		// Go through and find all the test scores for the student...
		$this->student->list_standardized_tests->reset_counter();
		while($this->student->list_standardized_tests->has_more()) {
			$st = $this->student->list_standardized_tests->get_next();
      
			$dt = strtotime($st->date_taken);
			$ddate = format_date($dt, "just_date");

			$fsC .= "<div>
						<b>$st->description</b> - $ddate
						<ul>";
			foreach($st->categories as $position => $cat_array)
			{
				$fsC .= "<li>{$cat_array["description"]} - {$cat_array["score"]}</li>";

			}
			$fsC .= "</ul>
					</div>";

		}

    $pC .= fp_render_c_fieldset($fsC, t("Click to view/hide standardized test scores"), TRUE);

		$pC .= "</td></tr>";


		$pC .= $this->draw_semester_box_bottom();

		$this->add_to_screen($pC);
	}



/**
 * This function is used by the "build" functions most often.  It very
 * simply adds a block of HTML to an array called box_array.
 *
 * @param string $content_box
 */
	function add_to_screen($content_box)
	{
		$this->box_array[] = $content_box;
	}


	/**
	 * This function calls the other "build" functions to assemble
	 * the View or What If tabs in FlightPath.
	 *
	 */
	function build_screen_elements()
	{
		// This function will build & assemble all of the onscreen
		// elements for the advising screen.  It should be
		// called before display_screen();

		$this->build_semester_list();

		$this->build_excess_credit();
		$this->build_test_scores();

		$this->build_transfer_credit();

		if (!$this->bool_blank)
		{ // Don't show if this is a blank degree plan.
			$this->build_footnotes();
			$this->build_added_courses();
		}

	}




	/**
	 * This function is used to draw an individual pie chart box.
	 * It accepts values of top/bottom in order to come up
	 * with a percentage.
	 *
	 * @param string $title
	 * 
	 * @param float $top_value
	 *         - The top part of a ratio.  Ex: for 1/2, $top_value = 1.
	 *
	 * @param float $bottom_value
	 *         - The bottom part of a ratio.  For 1/2, $bottom_value = 2.
	 *         - Do not let this equal zero.  If it does, the calculation
	 *           for the pie chart will never be evaluated.
	 * @param string $pal
	 *         - Which palette to use for the pie chart.
	 *         - Acceptable values:
	 *           - core
	 *           - major
	 *           - cumulative
	 *           - student
	 * 
	 * @return string
	 */
	function draw_pie_chart_box($title, $top_value, $bottom_value, $pal)
	{
		$pC = "";

				
		if ($bottom_value > 0)
		{
			$val = round(($top_value / $bottom_value)*100);
		}
		if ($val > 100) { $val = 99; }
    
		$leftval = 100 - $val;
		
		$back_col = "660000";
		$fore_col = "FFCC33";
		
    if ($pal == "major")
    {
    	$fore_col = "93D18B";
    }
    
    if ($pal == "cumulative")
    {
    	$fore_col = "5B63A5";
    }
    
    $vval = $val;
    if ($vval < 1) $vval = 1;
    
		// Create a graph using google's chart API		
		$google_chart_url = "https://chart.googleapis.com/chart?cht=p&chd=t:$vval,$leftval&chs=75x75&chco=$fore_col|$back_col&chp=91.1";
		
		$pC .= "<table border='0' width='100%'  height='100' class='elevenpt blueBorder' cellpadding='0' cellspacing='0' >
 						<tr>
  							<td class='blueTitle' align='center' height='20'>
    				" . fp_render_square_line($title) . "
  							</td>
 						</tr>
 						<tr>
 							<td>
 								<table border='0'>
 								<td>
 									<!-- <img src='jgraph/display_graph.php?pal=$pal&value=$val'> -->
 									<img src='$google_chart_url'>
 								</td>
 								<td class='elevenpt'>
 								    <span style='color: blue;'>$val% " . t("Complete") . "</span><br>
 								    ( <span style='color: blue;'>$top_value</span>
 									 / <span style='color: gray;'>$bottom_value " . t("hours") . "</span> )
								</td>
								</table>
 							</td>
 						</tr>
 					</table>
				";

		return $pC;
	}


	/**
	 * This function calls drawPieChart to construct the student's 3
	 * progress pie charts.
	 *
	 * @return string
	 */
	function draw_progress_boxes()
	{
	  global $user;
		// Draw the boxes for student progress (where
		// the pie charts go!)
		$pC = "";


		if ($this->degree_plan->total_degree_hours < 1)
		{
			$this->degree_plan->calculate_progress_hours();
		}

		$total_major_hours = $this->degree_plan->total_major_hours;
		$total_core_hours = $this->degree_plan->total_core_hours;
		$total_degree_hours = $this->degree_plan->total_degree_hours;
		$fulfilled_major_hours = $this->degree_plan->fulfilled_major_hours;
		$fulfilled_core_hours = $this->degree_plan->fulfilled_core_hours;
		$fulfilled_degree_hours = $this->degree_plan->fulfilled_degree_hours;


		$pC .= "<tr><td colspan='2'>
				";

    $user->settings = $this->db->get_user_settings($user->id);
				
		if ($user->settings["hide_charts"] != "hide" && $this->bool_print == false && $this->bool_blank == false && $this->page_is_mobile == false)
		{ // Display the pie charts unless the student's settings say to hide them.

		
			$pC .= "
				<div style='margin-bottom: 10px;'>
				<table width='100%' cellspacing='0' cellpadding='0' border='0'>
				<td width='33%' style='padding-right:5px;'>
					" . $this->draw_pie_chart_box(t("Progress - Core Courses"),$fulfilled_core_hours, $total_core_hours, "core") . "
				</td>
				
				<td width='33%' style='padding-right: 5px;'>
					" . $this->draw_pie_chart_box(t("Progress - Major Courses"),$fulfilled_major_hours, $total_major_hours, "major") . "
				</td>
				
				<td width='33%'>
					" . $this->draw_pie_chart_box(t("Progress - Degree"),$fulfilled_degree_hours, $total_degree_hours, "cumulative") . "
				</td>
				

				
				</table>
				";

			$pC .= "
				
				<div style='font-size: 8pt; text-align:right;'>
					<a href='javascript:hideShowCharts(\"hide\");'>" . t("hide charts") . "</a>
				</div>";

			$pC .= "
				</div>";
		} else {
			// Hide the charts!  Show a "show" link....
			$pC .= "
 			<table border='0' width='100%'  class='elevenpt blueBorder' cellpadding='0' cellspacing='0' >
 			<tr>
  				<td colspan='4' class='blueTitle' align='center' height='20'>
    			" . fp_render_square_line(t("Progress")) . "
  				</td>
 			</tr>
 			<tr>
 				<td class='tenpt' width='33%' align='center'>
 					" . t("Core:") . " $fulfilled_core_hours / $total_core_hours
 				</td>
 				<td class='tenpt' width='33%' align='center'>
 					" . t("Major:") . " $fulfilled_major_hours / $total_major_hours
 				</td>
 				<td class='tenpt' width='33%' align='center'>
 					" . t("Degree:") . " $fulfilled_degree_hours / $total_degree_hours
 				</td>
 				
 			</tr>

			</table>
			";

			if ($this->bool_print != true && $this->bool_blank != true && $this->page_is_mobile != true)
			{

				$pC .= "<div style='font-size: 8pt; text-align:right;'>
					<a href='javascript:hideShowCharts(\"show\");'>" . t("show charts") . "</a>
				</div>
					";
			} else {
				$pC .= "<div> &nbsp; </div>";
			}
		}
		$pC .= "
				</td></tr>";



		return $pC;
	}




  /**
   * Will display the "public note" at the top of a degree.  This
   * was entred in Data Entry.
   *
   * @return string
   */
	function draw_public_note()
	{
		// This will display a "public note" to the user about
		// this degree.  The public note was entered in Data Entry.

		if ($this->degree_plan->public_note == "")
		{
			return "";
		}

		$public_note = filter_markup($this->degree_plan->public_note);

		$pC = "";

		$pC .= "<tr><td colspan='8'>
					<div class='tenpt' 
						style='border: 5px double #C1A599;
								padding: 5px;
								margin: 10px;'>
					<b>" . t("Important Message:") . "</b> $public_note
					</div>
					</td></tr>";


		return $pC;


	}
	
	
	
	/**
	 * This function generates the HTML to display the screen.  Should
	 * be used in conjunction with output_to_browser()
	 *
	 * @return string
	 */	
	function display_screen()
	{
		// This will generate the html to display the screen.
		$pC = "";



		if ($this->bool_hiding_grades && !$this->bool_print && $GLOBALS["fp_system_settings"]["hiding_grades_message"] != "")
		{
		  // Display the message about us hiding grades.
		  $pC .= "
          <tr><td colspan='2'>
          			<div class='tenpt hypo' style='margin-top: 4px; margin-bottom: 4px; 
          			 padding: 2px; border: 1px solid maroon;'>
          			<table border='0' cellspacing='0' cellpadding='0'>
          			<td valign='top'>
          				<img src='fp_theme_location()/images/alert_lg.gif' >	
          			</td>
          			<td valign='middle' class='tenpt' style='padding-left: 8px;'>
          			{$GLOBALS["fp_system_settings"]["hiding_grades_message"]}
          			</td>
          			</table>
          			</div>
          </td></tr>		  
		  ";
		}
		
		
		//$pC .= $this->draw_currently_advising_box();
		$pC .= $this->draw_progress_boxes();
		
    
		
		$pC .= $this->draw_public_note();

		for ($t = 0; $t < count($this->box_array); $t++)
		{

			$align = "right";
			if ($this->is_on_left)
			{
				$pC .= "<tr>";
				$align= "left";
			}
			
			$pC .= "<td valign='top' align='$align' class='fp-boxes'>";
			$pC .= $this->box_array[$t];
			$pC .= "</td>";
			
			if (fp_screen_is_mobile()) {
			 // If we are on a mobile device, force it to use
			 // only one column. 
			 $this->is_on_left = false;
			}
			
			if (!$this->is_on_left) // on right of page
			{
				$pC .= "</tr>";
			}
			$this->is_on_left = !$this->is_on_left;
		}

		if (!$this->is_on_left) // on right of the page.
		{ // close up any loose ends.
			$pC .= "</tr>";
		}


		if (user_has_permission("can_advise_students"))
		{
			if (!$this->bool_print && !$this->bool_blank)
			{
      
			  $pC .= "<tr>";
			  
        if (!fp_screen_is_mobile()) {
          $pC .= "<td>&nbsp;</td>";
        }
			  
				$pC .= "<td align='center'>
						<div class='tenpt' style='margin-top:35px; margin-bottom:10px; padding: 10px; width: 200px;'>
						" . fp_render_button(t("Submit"),"submitSaveActive();") . "					
						</div>
						</td></tr>
						";		


				//$this->add_to_screen("<input type='button' value='Submit' onClick='submitSaveActive();'>");
			}
		}
    
		return $pC;

	}

	/**
	 * Returns the HTML to draw a pretty button.
	 *
	 * @param string $title
	 * @param string $on_click
	 * @param bool $bool_padd
	 * @param string $style
	 * @return string
	 */
	function draw_button($title, $on_click, $bool_padd = true, $style = "")
	{
		// Style is expected to look like:
		// style='some:thing;'
		// with SINGLE apostrophes!  not quotes.

		$on_mouse = "onmouseover='this.className=\"gradbutton gradbutton_hover hand\";'
					onmouseout='this.className=\"gradbutton hand\";'
					onmousedown='this.className=\"gradbutton gradbutton_down hand\";'
					onmouseup='this.className=\"gradbutton gradbutton_hover hand\";'
					";

		if ($this->page_is_mobile) $on_mouse = "";  // Causes problems for some mobile devices.
		
		if ($bool_padd)
		{
			$padd = "&nbsp; &nbsp;";
		}


		$rtn = "<span class='gradbutton hand' onClick='$on_click' $on_mouse $style >
				$padd $title $padd
				</span>

			";
		return $rtn;
	}



	/**
	 * Constructs the HTML to display the list of semesters for the student.
	 *
	 */
	function build_semester_list() {
	  
		$list_semesters = $this->degree_plan->list_semesters;
		// Go through each semester and add it to the screen...
		$list_semesters->reset_counter();

		while($list_semesters->has_more())
		{
			$semester = $list_semesters->get_next();
			$semester->reset_list_counters();
			if ($semester->semester_num == -88)
			{ // These are the "added by advisor" courses.  Skip them.
				continue;
			}


			$this->add_to_screen($this->display_semester($semester, true));

		}

	}


	
	/**
	 * This function is called when we know we are on a mobile
	 * browser.  We have to handle tab rendering differently
	 * in order to make them all fit. 
	 *
	 * @param unknown_type $tab_array
	 */
	function draw_mobile_tabs($tab_array) {
	  
	  $rtn = "";
	  
	  $js_vars = "var mobileTabSelections = new Array(); ";

	  if (count($tab_array) <= 1) return "";
	  
	  
	  $rtn .= "<table border='0' width='200' cellpadding='0' cellspacing='0' class='fp-mobile-tabs'>
	           <td>
	           <b>Display: </b>";
	  
	  
/*	  if (count($tab_array) == 1) {
	    // Just one element, no need to render the select list.
	    $rtn .= $tab_array[0]["title"];
	    $rtn .= "</td></table>";
	    return $rtn;
	  }
*/
	  
	  $rtn .= "<select onChange='executeSelection()' id='mobileTabsSelect'>";
	  
	  for ($t = 0; $t < count($tab_array); $t++)
		{
			$title = $tab_array[$t]["title"];
			$active = $tab_array[$t]["active"];
			$on_click = $tab_array[$t]["on_click"];

			if ($title == "")
			{
				continue;
			}
			$sel = ($active == true) ? $sel = "selected":"";
			
			$rtn .= "<option $sel value='$t'>$title</option>";
						
			$js_vars .= "mobile_tab_selections[$t] = '$on_click'; \n";
			
		}	  
	  
		$rtn .= "</select>
		          </td></table>";
	  				
		
		$rtn .= '
		  <script type="text/javascript">
		  ' . $js_vars . '		  
		  
		  function executeSelection() {
		    var sel = document.getElementById("mobileTabsSelect").value;
		    
		    var statement = mobile_tab_selections[sel];
		    // Lets execute the statement...
		    eval(statement);
		    
		  }
		  
		  
		  </script>
		';
		
	  return $rtn;
	  
	}
	
	
	

  /**
   * Displays the contents of the Descripton tab for the course popup.
   *
   * @param int $course_id
   *        - The course_id of the course to show.  Leave blank if supplying
   *          the object instead.
   * 
   * @param Course $course
   *        - The course object to display.  Leave as NULL if supplying
   *          the course_id instead.
   * 
   * @param Group $group
   *        - The Group object that this course has been placed into.
   * 
   * @param bool $show_advising_buttons
   *        - Should we show the advising buttons in this popup?  Would be
   *          set to false for student view, or for anyone who is not
   *          allowed to advise this course into a group for the student.
   * 
   * @return string
   */
	function display_popup_course_description($course_id = "", Course $course = null, $group = null, $show_advising_buttons = false)
	{
		$pC = "";

		if ($course_id != "" && $course_id != 0) {
		  
			$course = new Course($course_id);
		}


		
		$db_group_requirement_id = $_REQUEST["db_group_requirement_id"];
		
  

		if ($course == null)
		{
			// No course available!
			$pC .= fp_render_curved_line(t("Description"));
			$pC .= "<div class='tenpt'>" . t("No course was selected.  Please
					click the Select tab at the top of the screen.") . "
					</div>";
			return $pC;
		}


		$advising_term_id = $GLOBALS["fp_advising"]["advising_term_id"];

    
		$course->load_descriptive_data();

		$course_hours = $course->get_hours();

		if ($course->bool_transfer)
		{


		}

		// Does this course have more than one valid (non-excluded) name?
		$other_valid_names = "";
		if (count($course->array_valid_names) > 1)
		{
			for ($t = 0; $t < count($course->array_valid_names); $t++)
			{
				$name = $course->array_valid_names[$t];
				if ($name == "$course->subject_id~$course->course_num")
				{
					continue;
				}
				$other_valid_names .= ", " . str_replace("~"," ",$name);
			}
		}

		$course->fix_title();

    $initials = $GLOBALS["fp_system_settings"]["school_initials"];
		
		$pC .= fp_render_curved_line("$course->subject_id $course->course_num$other_valid_names <!--EQV1-->");
		$bool_transferEqv = true;
		if ($course->bool_transfer)
		{
			// This is a transfer course.  Begin by displaying the transfer credit's
			// information.
			
			$course->course_transfer->load_descriptive_transfer_data($this->student->student_id);
			$hrs = $course->course_transfer->get_hours()*1;
			if ($hrs == 0)
			{
				$hrs = $course->get_hours();
			}
						
			// make transfer course titles all caps.
			$course->course_transfer->title = strtoupper($course->course_transfer->title);

			$pC .= "<div style='margin-top: 13px;' class='tenpt'>
				<b>" . t("Transfer Credit Information:") . "</b><br>
				<div style='margin-left: 20px;' class='tenpt'>
					" . t("Course:") . " " . $course->course_transfer->subject_id . " " . $course->course_transfer->course_num . " 
					- " . $course->course_transfer->title . " ($hrs hrs)<br>
					" . t("Institution:") . " " . $this->fix_institution_name($course->course_transfer->institution_name) . "<br>
					" . t("Term:") . " " . $course->get_term_description() . "<br>
					<!-- Grade: " . $course->grade . "<br> -->
					";

			$transfer_eqv_text = $course->course_transfer->transfer_eqv_text;
			if ($transfer_eqv_text == "") {
				$transfer_eqv_text = t("Not entered or not applicable.");
				$bool_transferEqv = false;
			}

			$pC .= "$initials Eqv: $transfer_eqv_text<br>
				</div>
					</div>";

		}


		$pC .= "
		   	<div style='margin-top: 13px;'>
				<div class='tenpt'>";
		if ($course->course_id != 0)
		{
		  $use_hours = $course_hours;
			if ($course->bool_transfer)
			{
				$pC .= "<b>$initials " . t("Equivalent Course Information:") . "</b><br>
						<b>$course->subject_id $course->course_num</b> - ";
				$new_course = new Course();
				$new_course->course_id = $course->course_id;
				$new_course->load_descriptive_data();
				$use_hours = $new_course->get_catalog_hours();
			}
			$pC .= "
					<b>$course->title ($use_hours " . t("hrs") . ")</b>";
		}
		if ($course->bool_substitution_new_from_split || $course->bool_substitution_split)
		{
			$pC .= "<div class='tenpt' style='margin-bottom:5px;'>
						<i>" . t("This course's hours were split in a substitution.") . "</i> 
						<a href='javascript: alertSplitSub();'>?</a>
					</div>";
		}

		$pC .= "</div>";

		if ($course->course_id != 0)
		{
			$pC .= "
			<div class='tenpt'>
					$course->description
				</div>
			</div>
				"; 
		}

		if ($course->bool_transfer == true && $course->course_id < 1 && $course->bool_substitution == false)
		{ // No local eqv!

			$pC .= "<div class='tenpt' style='margin-top: 10px;'><b>Note:</b> ";
			/*
			$pC .= "
			<b>Note:</b> This course is a transfer credit which
			the student completed at <i>";

			$pC .= $this->fix_institution_name($course->course_transfer->institution_name) . "</i>.";
			*/
			$pC = str_replace("<!--EQV1-->"," (" . t("Transfer Credit") . ")",$pC);
			if (!$bool_transferEqv)
			{
				$t_msg = t("This course does not have an assigned @initials equivalency, or the equivalency
							has been removed for this student.
						Ask your advisor if this course will count towards your degree.", array("@initials" => $initials)) . "
					</div>"; 
			} else {
				$t_msg = t("FlightPath cannot assign this course to a @initials equivalency on
							the student's degree plan, 
							or the equivalency
							has been removed for this student.
						Ask your advisor if this course will count towards your degree.", array("@initials" => $initials)) . "
					</div>"; 				
			}

			$pC .= $t_msg;

		} elseif ($course->bool_transfer == true && $course->course_id > 0 && $course->bool_substitution == false)
		{ // Has a local eqv!

			$t_s_i = $course->course_transfer->subject_id;
			$t_c_n = $course->course_transfer->course_num;
			/*			$pC .= "<div class='tenpt' style='margin-top: 10px;'>
			<b>Note:</b> The course listed above is equivalent
			to <b>$t_s_i $t_c_n</b>,
			which the student completed at <i>";

			// Replace the temporary comment <!--EQV1--> in the header with
			// the new eqv information.
			*/
			$pC = str_replace("<!--EQV1-->"," (" . t("Transfer Credit") . " $t_s_i $t_c_n)",$pC);
			/*			$pC .= $this->fix_institution_name($course->course_transfer->institution_name);
			$pC .= "</i>.";
			*/
			// Admin function only.
			if (user_has_permission("can_substitute"))
			{
				$pC .= "<div align='left' class='tenpt'>
					<b>" . t("Special administrative function:") . "</b>
						<a href='javascript: popupUnassignTransferEqv(\"" . $course->course_transfer->course_id . "\");'>" . t("Remove this equivalency?") . "</a></div>";
				$pC .= "</div>";
			}


			$pC .= "</div>";
		}


		if ($course->term_id != "" && $course->term_id != "11111" && $course->display_status != "eligible" && $course->display_status != "disabled")
		{
			$pC .= "<div class='tenpt' style='margin-top: 10px;'>
						" . t("The student enrolled in this course in") . " " . $course->get_term_description() . ".
					</div>";
		} else if ($course->term_id == "11111")
		{
			$pC .= "<div class='tenpt' style='margin-top: 10px;'>
						" . t("The exact date that the student enrolled in this course
						cannot be retrieved at this time.  Please check the
						student's official transcript for more details.") . "
					</div>";

		}

		if ($course->assigned_to_group_id*1 > 0 && $course->grade != "" && $course->bool_transfer != true && $course->bool_substitution != true)
		{
			//$g = new Group($course->assigned_to_group_id);
			$g = new Group();
			$g->group_id = $course->assigned_to_group_id;
			$g->load_descriptive_data();

			$pC .= "<div class='tenpt' style='margin-top: 10px;'>
						<img src='fp_theme_location()/images/icons/$g->icon_filename' width='19' height='19'>
						&nbsp;
						" . t("This course is a member of") . " $g->title.
					";
			// If user is an admin...
			if (user_has_permission("can_substitute")) {
				$tflag = intval($course->bool_transfer);
				$pC .= "<div align='left' class='tenpt'>
					<b>" . t("Special administrative function:") . "</b>
						<a href='javascript: popupUnassignFromGroup(\"$course->course_id\",\"$course->term_id\",\"$tflag\",\"$g->group_id\");'>" . t("Remove from this group?") . "</a></div>";
				$pC .= "</div>";
			}

		} else if ($course->grade != "" && $course->bool_transfer != true && $course->bool_substitution != true && $course->bool_has_been_assigned == true) {
			// Course is not assigned to a group; it's on the bare degree plan.  group_id = 0.
			// If user is an admin...
			if (user_has_permission("can_substitute"))
			{
				$tflag = intval($course->bool_transfer);
				$pC .= "<div align='left' class='tenpt'>
					<b>" . t("Special administrative function:") . "</b>
						<a href='javascript: popupUnassignFromGroup(\"$course->course_id\",\"$course->term_id\",\"$tflag\",\"0\");'>" . t("Remove from the degree plan?") . "</a></div>";
				$pC .= "</div>";
			}

		}


		// Substitutors get extra information:
		if (user_has_permission("can_substitute") && $course->assigned_to_group_id > 0) {
			
			
			$pC .= "<div class='tenpt' style='margin-top: 20px;'>
					<b>" . t("Special administrative information:") . "</b>
					
				<span id='viewinfolink'
				onClick='document.getElementById(\"admin_info\").style.display=\"\"; this.style.display=\"none\"; '
				class='hand' style='color: blue;'
				> - " . t("Click to show") . " -</span>					
					
					<div style='padding-left: 20px; display:none;' id='admin_info'>
					";

			// Course is assigned to a group.
			if ($course->assigned_to_group_id > 0) {
  			$group = new Group();
  			$group->group_id = $course->assigned_to_group_id;
  			$group->load_descriptive_data();
  			
  			$pC .= "
  					" . t("Course is assigned to group:") . "<br>
  					&nbsp; " . t("Group ID:") . " $group->group_id<br>
  					&nbsp; " . t("Title:") . " $group->title<br>";
				$pC .= "&nbsp; <i>" . t("Internal name:") . " $group->group_name</i><br>";
  			
  			$pC .= "&nbsp; " . t("Catalog year:") . " $group->catalog_year
  			";
			}
			$pC .= "
					</div>
					
					</div>";								
		}


		if ($course->bool_substitution == true)
		{
			// Find out who did it and if they left any remarks.
			$db = $this->db;
			$temp = $db->get_substitution_details($course->db_substitution_id);
			$by = $db->get_faculty_name($temp["faculty_id"], false);
			$remarks = $temp["remarks"];
			$ondate = format_date($temp["posted"], "", "n/d/_y");
			
			
			if ($by != "")
			{
				$by = " by $by, on $ondate.";
			}

			if ($remarks != "")
			{
				$remarks = " " . t("Substitution remarks:") . " <i>$remarks</i>.";
			}

			$forthecourse = t("for the original course
						requirement of") . " <b>" . $course->course_substitution->subject_id . " 
						" . $course->course_substitution->course_num . "</b>";
			if ($temp["required_course_id"]*1 == 0)
			{
				$forthecourse = "";
			}

			$pC .= "<div class='tenpt' style='margin-top: 10px;'>
						<b>" . t("Note:") . "</b> " . t("This course was substituted into the 
						degree plan") . " $forthecourse
						$by$remarks";

			
			if (user_has_permission("can_substitute")) {
				$pC .= "<div align='left' class='tenpt' style='padding-left: 10px;'>
					<b>" . t("Special administrative function:") . "</b>
					<a href='javascript: popupRemoveSubstitution(\"$course->db_substitution_id\");'>" . t("Remove substitution?") . "</a>
					</div>";
			}

		}

		// Only show if the course has not been taken...
		if ($course->has_variable_hours() && $course->grade == "")
		{
			$pC .= "<div class='tenpt' style='margin-top: 10px;'>
					" . t("This course has variable hours. Please select 
					how many hours this course will be worth:") . "<br>
					<center>
					<select name='selHours' id='selHours' onChange='popupSetVarHours();'>
					";
			
			// Correct for ghost hours, if they are there.
			$min_h = $course->min_hours;
			$max_h = $course->max_hours;
			if ($course->bool_ghost_min_hour) $min_h = 0;
			if ($course->bool_ghost_hour) $max_h = 0;
			
			for($t = $min_h; $t <= $max_h; $t++)
			{
				$sel = "";
				if ($t == $course->advised_hours){ $sel = "SELECTED"; }
				$pC .= "<option value='$t' $sel>$t</option>";
			}
			$pC .= "</select> " . t("hours.") . "<br>
					
					</center>
					</div>";

			if ($course->advised_hours > -1)
			{
				$var_hours_default = $course->advised_hours;
			} else {
				$var_hours_default = $min_h;
			}

		}

		if ($show_advising_buttons == true && !$this->bool_blank) {

			// Insert a hidden radio button so the javascript works okay...
			$pC .= "<input type='radio' name='course' value='$course->course_id' checked='checked'
					style='display: none;'>
					<input type='hidden' name='varHours' id='varHours' value='$var_hours_default'>";

			if (user_has_permission("can_advise_students"))
			{
				$pC .= "<div style='margin-top: 20px;'>
				" . fp_render_button(t("Select Course"), "popupAssignSelectedCourseToGroup(\"$group->assigned_to_semester_num\", \"$group->group_id\",\"$advising_term_id\",\"$db_group_requirement_id\");", true, "style='font-size: 10pt;'") . "
				</div>
				
				";
			}
		} 
		else if ($show_advising_buttons == false && $course->has_variable_hours() == true && $course->grade == "" && user_has_permission("can_advise_students") && !$this->bool_blank) {
			// Show an "update" button, and use the course's assigned_to_group_id and
			// assigned_to_semester_num.
			$pC .= "
					<input type='hidden' name='varHours' id='varHours' value='$var_hours_default'>";


      $pC .= fp_render_button(t("Update"), "popupUpdateSelectedCourse(\"$course->course_id\",\"$course->assigned_to_group_id\",\"$course->assigned_to_semester_num\",\"$course->random_id\",\"$advising_term_id\");");

		}


		return $pC;
	}




	/**
	 * Simple function to make an institution name look more pretty, because
	 * all institution names pass through ucwords(), sometimes the capitalization
	 * gets messed up.  This function tries to correct it.
	 * 
	 * Feel free to override it and add to it, if needed.
	 *
	 * @param string $str
	 * @return string
	 */
	function fix_institution_name($str)
	{
		$str = str_replace("-", " - ", $str);
		$str = ucwords(strtolower($str));
		$str = str_replace(" Of ", " of ", $str);
		$str = str_replace("clep", "CLEP", $str);
		$str = str_replace("_clep", "CLEP", $str);
		$str = str_replace("_act", "ACT", $str);
		$str = str_replace("_sat", "SAT", $str);
		$str = str_replace("Ap ", "AP ", $str);
		$str = str_replace("_dsst", "DSST", $str);
		
		// Fix school initials.
		// Turns "Ulm" into "ULM"
	  $school_initials = $GLOBALS["fp_system_settings"]["school_initials"];
		$str = str_replace(ucwords(strtolower($school_initials)), $school_initials, $str);		
		

		if ($str == "")
		{
			$str = "<i>unknown institution</i>";
		}



		return $str;
	}

	/**
	 * Left in for legacy reasons, this function uses a new Course object's
	 * method of $course->fix_title to make a course's title more readable.
	 *
	 * @param string $str
	 * @return stromg
	 */
	function fix_course_title($str)
	{
		/*		$str = str_replace("&", " & ", $str);

		$str = ucwords(strtolower(trim($str)));
		$str = str_replace("Ii","II",$str);
		$str = str_replace("IIi","III",$str);
		$str = str_replace("Iv","IV",$str);
		$str = str_replace("Vi","VI",$str);


		if ($str == "")
		{
		$str = "Title not available";
		}
		*/


		$new_course = new Course();
		$str = $new_course->fix_title($str);


		return $str;
	}


	/**
	 * Given a Semester object, this will generate the HTML to draw it out
	 * to the screen.
	 *
	 * @param Semester $semester
	 * @param bool $bool_display_hour_count
	 *       - If set to TRUE, it will display a small "hour count" message
	 *         at the bottom of each semester, showing how many hours are in
	 *         the semester.  Good for debugging purposes.
	 * 
	 * @return string
	 */
	function display_semester(Semester $semester, $bool_display_hour_count = false)
	{
		// Display the contents of a semester object
		// on the screen (in HTML)
		$pC = "";
		$pC .= $this->draw_semester_box_top($semester->title);

		$count_hoursCompleted = 0;

		// First, display the list of bare courses.

		$semester->list_courses->sort_alphabetical_order();
		$semester->list_courses->reset_counter();
		//print_pre($semester->list_courses->toString());
		while($semester->list_courses->has_more())
		{
			$course = $semester->list_courses->get_next();
			//$pC .= "<tr><td colspan='8'>";
			// Is this course being fulfilled by anything?

			//if (is_object($course->courseFulfilledBy))
			if (!($course->course_list_fulfilled_by->is_empty))
			{ // this requirement is being fulfilled by something the student took...

				//$pC .= $this->draw_course_row($course->courseFulfilledBy);
				$pC .= $this->draw_course_row($course->course_list_fulfilled_by->get_first());
				$course->course_list_fulfilled_by->get_first()->bool_has_been_displayed = true;

				//$count_hoursCompleted += $course->courseFulfilledBy->hours_awarded;
				if ($course->course_list_fulfilled_by->get_first()->display_status == "completed")
				{ // We only want to count completed hours, no midterm or enrolled courses.
					$h = $course->course_list_fulfilled_by->get_first()->hours_awarded;
					if ($course->course_list_fulfilled_by->get_first()->bool_ghost_hour == TRUE) {
					  $h = 0;
					}
					$count_hoursCompleted += $h;
				}

			} else {
				// This requirement is not being fulfilled...
				$pC .= $this->draw_course_row($course);

			}

			//$pC .= "</td></tr>";

		}


		// Now, draw all the groups.
		$semester->list_groups->sort_alphabetical_order();
		$semester->list_groups->reset_counter();
		while($semester->list_groups->has_more())
		{

			$group = $semester->list_groups->get_next();
			$pC .= "<tr><td colspan='8'>";
			$pC .= $this->display_group($group);
			$count_hoursCompleted += $group->hours_fulfilled_for_credit;
			$pC .= "</td></tr>";
		}

		// Add hour count to the bottom...
		if ($bool_display_hour_count == true && $count_hoursCompleted > 0)
		{
			$pC .= "<tr><td colspan='8'>
				<div class='tenpt' style='text-align:right; margin-top: 10px;'>
				Completed hours: $count_hoursCompleted
				</div>
				";
			$pC .= "</td></tr>";
		}


		// Does the semester have a notice?
		if ($semester->notice != "")
		{
			$pC .= "<tr><td colspan='8'>
					<div class='hypo tenpt' style='margin-top: 15px; padding: 5px;'>
						<b>Important Notice:</b> $semester->notice
					</div>
					</td></tr>";
		}

		$pC .= $this->draw_semester_box_bottom();

		return $pC;
	}


	/**
	 * This function displays a Group object on the degree plan.  This is not
	 * the selection popup display.  It will either show the group as multi
	 * rows, filled in with courses, or as a "blank row" for the user to click
	 * on.
	 *
	 * @param Group $place_group
	 * @return string
	 */
	function display_group(Group $place_group)
	{
		// Display a group, either filled in with courses,
		// and/or with a "blank row" for the user to
		// click on.
		$pC = "";

		// Now, if you will recall, all of the groups and their courses, etc,
		// are in the degree_plan's list_groups.  The $place_group object here
		// is just a placeholder.  So, get the real group...

		if (!$group = $this->degree_plan->find_group($place_group->group_id))
		{
			fpm("Group not found.");
			return;
		}

		


		$title = $group->title;

		$display_course_list = new CourseList();

		// Okay, first look for courses in the first level
		// of the group.
		//$group->list_courses->sort_alphabetical_order();

		$display_semesterNum = $place_group->assigned_to_semester_num;
		

		$group->list_courses->remove_unfulfilled_and_unadvised_courses();
		$group->list_courses->reset_counter();
		while($group->list_courses->has_more())
		{
			$course = $group->list_courses->get_next();

			// Do we have enough hours to keep going?
			$fulfilled_hours = $display_course_list->count_hours();
			$remaining = $place_group->hours_required - $fulfilled_hours;


			// If the course in question is part of a substitution that is not
			// for this group, then we should skip it.
			if (!($course->course_list_fulfilled_by->is_empty))
			{
				$try_c = $course->course_list_fulfilled_by->get_first();
				if ($try_c->bool_substitution == true && $try_c->assigned_to_group_id != $group->group_id)
				{

					continue;
				}
			}


			if (!($course->course_list_fulfilled_by->is_empty) && $course->course_list_fulfilled_by->get_first()->bool_has_been_displayed != true && $course->bool_has_been_displayed != true)
			{
				$c = $course->course_list_fulfilled_by->get_first();
				if ($remaining < $c->get_hours())
				{
					continue;
				}


				$c->temp_flag = false;
				$c->icon_filename = $group->icon_filename;
				$c->title_text = "This course is a member of $group->title.";
				$display_course_list->add($c);


			}

			if ($course->bool_advised_to_take && $course->bool_has_been_displayed != true && $course->assigned_to_semester_num == $display_semesterNum)
			{
				$c = $course;
				if ($remaining < $c->get_hours())
				{
					continue;
				}

				$c->temp_flag = true;
				$c->icon_filename = $group->icon_filename;
				$c->title_text = "The student has been advised to take this course to fulfill a $group->title requirement.";
				$display_course_list->add($c);

			}
		}


		$group->list_groups->reset_counter();
		while($group->list_groups->has_more())
		{
			$branch = $group->list_groups->get_next();
			// look for courses at this level...
			if (!$branch->list_courses->is_empty)
			{

				$branch->list_courses->sort_alphabetical_order();
				$branch->list_courses->reset_counter();
				while($branch->list_courses->has_more())
				{
					$course = $branch->list_courses->get_next();

					// Do we have enough hours to keep going?
					$fulfilled_hours = $display_course_list->count_hours();
					$remaining = $place_group->hours_required - $fulfilled_hours;

					if (!($course->course_list_fulfilled_by->is_empty) && $course->course_list_fulfilled_by->get_first()->bool_has_been_displayed != true && $course->bool_has_been_displayed != true)
					{
						$c = $course->course_list_fulfilled_by->get_first();
						if ($remaining < $c->get_hours() || $remaining < 1)
						{
							continue;
						}

						$c->temp_flag = false;
						$c->icon_filename = $group->icon_filename;
						$c->title_text = "This course is a member of $group->title.";

						if (!$display_course_list->find_match($c))
						{ // Make sure it isn't already in the display list.

							$display_course_list->add($c);
						} else if (is_object($c->course_transfer))
						{
							if (!$display_course_list->find_match($c->course_transfer))
							{ // Make sure it isn't already in the display list.
								$display_course_list->add($c);
							}
						}


					}

					if ($course->bool_advised_to_take && $course->bool_has_been_displayed != true && $course->assigned_to_semester_num == $display_semesterNum)
					{

						$c = $course;
						if ($remaining < $c->get_hours() || $remaining < 1)
						{

							continue;
						}

						$c->temp_flag = true;
						$c->icon_filename = $group->icon_filename;
						$c->title_text = "The student has been advised to take this course to fulfill a $group->title requirement.";
						if (!$display_course_list->find_match($c))
						{
							$display_course_list->add($c);
						}

					}


				}

			}
		}







		$display_course_list->sort_advised_last_alphabetical();


		$pC .= $this->display_group_course_list($display_course_list, $group, $display_semesterNum);

		$fulfilled_hours = $display_course_list->count_hours("", false, false, true);
		$fulfilled_credit_hours = $display_course_list->count_credit_hours("",false,true);
		

		$test_hours = $fulfilled_hours;
		// if the fulfilledCreditHours is > than the fulfilledHours,
		// then assign the fulfilledCreditHours to the testHours.
		if ($fulfilled_credit_hours > $fulfilled_hours)
		{ // done to fix a bug involving splitting hours in a substitution.		  
			$test_hours = $fulfilled_credit_hours;
		} 
		// If there are any remaining hours in this group,
		// draw a "blank" selection row.
		$remaining = $place_group->hours_required - $test_hours;
		$place_group->hours_remaining = $remaining;
		$place_group->hours_fulfilled = $fulfilled_hours;
		$place_group->hours_fulfilled_for_credit = $fulfilled_credit_hours;
		if ($remaining > 0)
		{
			$pC .= "<tr><td colspan='8' class='tenpt'>";
			$pC .= $this->draw_group_select_row($place_group, $remaining);
			$pC .= "</td></tr>";
		}


		return $pC;
	}


	/**
	 * Find all instaces of a Course in a Group and mark as displayed.
	 *
	 * @param Group $group
	 * @param Course $course
	 */
	function mark_course_as_displayed(Group $group, Course $course)
	{
		// Find all instances of $course in $group,
		// and mark as displayed.

		if ($obj_list = $group->list_courses->find_all_matches($course))
		{
			$course_list = CourseList::cast($obj_list);
			$course_list->mark_as_displayed();
		}
		// Now, go through all the course lists within each branch...
		$group->list_groups->reset_counter();
		while($group->list_groups->has_more())
		{
			$g = $group->list_groups->get_next();
			if ($obj_list = $g->list_courses->find_all_matches($course))
			{

				$course_list = CourseList::cast($obj_list);
				$course_list->mark_as_displayed($semester_num);
			}
		}


	}



  /**
   * Displays all the courses in a CourseList object, using 
   * the draw_course_row function.
   * 
   * It looks like the group and semester_num are not being used
   * anymore.
   * 
   * @todo Check on unused variables.
   *
   * @param CourseList $course_list
   * @param unknown_type $group
   * @param unknown_type $semester_num
   * @return unknown
   */
	function display_group_course_list($course_list, $group, $semester_num)
	{
		$course_list->reset_counter();
		while($course_list->has_more())
		{
			$course = $course_list->get_next();



			$pC .= $this->draw_course_row($course, $course->icon_filename, $course->title_text, $course->temp_flag);

			// Doesn't matter if its a specified repeat or not.  Just
			// mark it as having been displayed.
			$course->bool_has_been_displayed = true;
			
		}
		return $pC;

	}


	/**
	 * This draws the "blank row" for a group on the degree plan, which instructs
	 * the user to click on it to select a course from the popup.
	 *
	 * @param Group $group
	 * @param int $remaining_hours
	 * @return string
	 */
	function draw_group_select_row(Group $group, $remaining_hours)
	{
		$pC = "";
		$img_path = fp_theme_location() . "/images";
		$on_mouse_over = " onmouseover=\"style.backgroundColor='#FFFF99'\"
      				onmouseout=\"style.backgroundColor='white'\" ";

		if ($this->page_is_mobile) $on_mouse_over = "";  // Causes problems for some mobile devices.
		
		$w1_1 = $this->width_array[0];
		$w1_2 = $this->width_array[1];
		$w1_3 = $this->width_array[2];
		$w2 = $this->width_array[3];
		$w3 = $this->width_array[4];
		$w4 = $this->width_array[5];
		$w5 = $this->width_array[6];
		$w6 = $this->width_array[7];

		$s = "s";
		if ($remaining_hours < 2)
		{
			$s = "";
		}
		$select_icon = "<img src='$img_path/select.gif' border='0'>";
		$icon_link = "<img src='$img_path/icons/$group->icon_filename' width='19' height='19' border='0' alt='$title_text' title='$title_text'>";

		$blank_degree_id = "";
		if ($this->bool_blank)
		{
			$blank_degree_id = $this->degree_plan->degree_id;
		}

		$js_code = "selectCourseFromGroup(\"$group->group_id\", \"$group->assigned_to_semester_num\", \"$remaining_hours\", \"$blank_degree_id\");";

		$row_msg = "<i>Click <font color='red'>&gt;&gt;</font> to select $remaining_hours hour$s.</i>";
		$hand_class = "hand";

		if ($this->bool_print)
		{
			// In print view, disable all popups and mouseovers.
			$on_mouse_over = "";
			$js_code = "";
			$hand_class = "";
			$row_msg = "<i>Select $remaining_hours hour$s from $group->title.</i>";
		}


		if ($group->group_id == -88)
		{ // This is the Add a Course group.
			$row_msg = "<i>Click to add an additional course.</i>";
			$select_icon = "<span style='font-size: 16pt; color:blue;'>+</span>";
			$icon_link = "";
		}


		$pC .= "
   		<table border='0' cellpadding='0' width='100%' cellspacing='0' align='left'>
     	<tr height='20' class='$hand_class'
      		$on_mouse_over title='$group->title'>
      		<td width='$w1_1' align='left'>&nbsp;</td>
      		<td width='$w1_2' align='left' onClick='$js_code'>$icon_link</td>
      		<td width='$w1_3' align='left' onClick='$js_code'>$select_icon</td>
      		<td align='left' colspan='5' class='tenpt underline' onClick='$js_code'>
      		$row_msg
       				
     	</tr>
     	</table>";		





		return $pC;
	}

	/**
	 * Uses the draw_box_top function, specifically for semesters.
	 *
	 * @param string $title
	 * @param bool $hideheaders
	 * @return string
	 */
	function draw_semester_box_top($title, $hideheaders = false)
	{

	  $w = 340;
	  if ($this->page_is_mobile) $w = "100%";
		return $this->draw_box_top($title, $hideheaders, $w);
	}

	/**
	 * Uses the draw_box_bottom function, specifically for semesters.
	 * Actually, this function is a straight alias for $this->draw_box_bottom().
	 *
	 * @return string
	 */
	function draw_semester_box_bottom()
	{
		return $this->draw_box_bottom();
	}

	/**
	 * Very, very simple.  Just returns "</table>";
	 *
	 * @return string
	 */
	function draw_box_bottom()
	{
		return "</table>";
	}

	/**
	 * Used to draw the beginning of semester boxes and other boxes, for example
	 * the footnotes.
	 *
	 * @param string $title
	 * @param bool $hideheaders
	 *       - If TRUE, then the course/hrs/grd headers will not be displayed.
	 * 
	 * @param int $table_width
	 *       - The HTML table width, in pixels.  If not set, it will default
	 *         to 300 pixels wide.
	 * 
	 * @return string
	 */
	function draw_box_top($title, $hideheaders=false, $table_width = 300){ 
	  // returns the beginnings of the year tables...

		// Get width values from width_array (supplied by calling function,
		// for example, draw_year_box_top
		$w1_1 = $this->width_array[0];
		$w1_2 = $this->width_array[1];
		$w1_3 = $this->width_array[2];
		$w2 = $this->width_array[3];
		$w3 = $this->width_array[4];
		$w4 = $this->width_array[5];
		$w5 = $this->width_array[6];
		$w6 = $this->width_array[7];

		if ($this->bool_popup == true)
		{
			$w1_1 = $this->popup_width_array[0];
			$w1_2 = $this->popup_width_array[1];
			$w1_3 = $this->popup_width_array[2];
			$w2 = $this->popup_width_array[3];
			$w3 = $this->popup_width_array[4];
			$w4 = $this->popup_width_array[5];
			$w5 = $this->popup_width_array[6];
			$w6 = $this->popup_width_array[7];
		}


		$headers = array();
		if ($hideheaders != true)
		{
			$headers[0] = t("Course");
			$headers[1] = t("Hrs");
			$headers[2] = t("Grd");
			$headers[3] = t("Pts");
		}


		$rtn = "
		   <table border='0' width='$table_width' cellpadding='0' cellspacing='0' class='fp-box-top'>
   			<tr>
    		<td colspan='8' class='blueTitle' align='center' valign='top'>
    				";
		$rtn .= fp_render_curved_line($title);

		$rtn .= "
    		</td>
   			</tr>
   					";
		if (!$hide_headers)
		{
			$rtn .= "
   			<tr height='20'>

    			<td width='$w1_1' align='left'>
     			&nbsp;
    			</td>

    			<td width='$w1_2' align='left'>
     			&nbsp;
    			</td>

    			<td width='$w1_3' align='left'>
     			&nbsp;
    			</td>
    
        		<td align='left' width='$w2'>
     				<font size='2'><b>$headers[0]</b></font>
	    		</td>

    			<td width='$w3' align='left'>&nbsp;</td>
    			<td width='$w4'>
     				<font size='2'><b>$headers[1]</b></font>
    			</td>
    			<td width='$w5'>
     				<font size='2'><b>$headers[2]</b></font>
    			</td>
    			<td width='$w6'>
     				<font size='2'><b>$headers[3]</b></font>
    			</td>
   			</tr>
   				";
		}
		return $rtn;

	} // draw_year_box_top


	/**
	 * This is used by lots of other functions to display a course on the screen.
	 * It will show the course, the hours, the grade, and quality points, as well
	 * as any necessary icons next to it.
	 *
	 * @param Course $course
	 * @param string $icon_filename
	 * @param string $title_text
	 * @param bool $js_toggle_and_save
	 *         - If set to TRUE, when the checkbox next to this course is clicked,
	 *           the page will be submitted and a draft will be saved.
	 * 
	 * @param bool $bool_display_check
	 *         - If set to FALSE, no checkbox will be displayed for this course row.
	 * 
	 * @param bool $bool_add_footnote
	 * @param bool $bool_add_asterisk_to_transfers
	 *
	 * @return string
	 */
	function draw_course_row(Course $course, $icon_filename = "", $title_text = "", $js_toggle_and_save = false, $bool_display_check = true, $bool_add_footnote = true, $bool_add_asterisk_to_transfers = false)
	{ 
		// Display a course itself...
		$pC = "";
		$w1_1 = $this->width_array[0];
		$w1_2 = $this->width_array[1];
		$w1_3 = $this->width_array[2];
		$w2 = $this->width_array[3];
		$w3 = $this->width_array[4];
		$w4 = $this->width_array[5];
		$w5 = $this->width_array[6];
		$w6 = $this->width_array[7];

		$img_path = fp_theme_location() . "/images";
		
		// The current term we are advising for.
		$advising_term_id = $GLOBALS["fp_advising"]["advising_term_id"];
    if (!$advising_term_id) {
      $advising_term_id = 0;
    }

		$course->assign_display_status();
		// If the course has already been advised in a different semester,
		// we should set the advising_term_id to that and disable unchecking.
		if ($course->advised_term_id*1 > 0 && $course->bool_advised_to_take == true && $course->advised_term_id != $advising_term_id)
		{
			$course->display_status = "disabled";
			$advising_term_id = $course->advised_term_id;
		}


		if ($course->subject_id == "")
		{
			$course->load_descriptive_data();
		}


		$subject_id = $course->subject_id;
		$course_num = $course->course_num;


		$o_subject_id = $subject_id;
		$o_course_num = $course_num;

		$footnote = "";
		$ast = "";
		// Is this actually a transfer course?  If so, display its
		// original subject_id and course_num.
		if ($course->bool_transfer == true)
		{
			$subject_id = $course->course_transfer->subject_id;
			$course_num = $course->course_transfer->course_num;
			$institution_name = $course->course_transfer->institution_name;

			if ($bool_add_asterisk_to_transfers == true)
			{
				$course->course_transfer->load_descriptive_transfer_data($this->student->student_id);
				if ($course->course_transfer->transfer_eqv_text != "")
				{
					$ast = "*";
					$GLOBALS["advising_course_has_asterisk"] = true;
				}
			}

			// Apply a footnote if it has a local eqv.
			if ($bool_add_footnote == true && $course->course_id > 0)
			{
				$footnote = "";

				$footnote .= "<span class='superscript'>T";
				$fcount = count($this->footnote_array["transfer"]) + 1;
				if ($course->bool_has_been_displayed == true)
				{ // If we've already displayed this course once, and are
					// now showing it again (like in the Transfer Credit list)
					// we do not want to increment the footnote counter.
					$fcount = $course->transfer_footnote;
				}
				$course->transfer_footnote = $fcount;
				$footnote .= "$fcount</span>";
				$this->footnote_array["transfer"][$fcount] = "$o_subject_id $o_course_num ~~ $subject_id $course_num ~~  ~~ $institution_name";
			}
		}


		if ($course->bool_substitution == true )
		{

			if ($course->course_substitution->subject_id == "")
			{ // Reload subject_id, course_num, etc, for the substitution course,
				// which is actually the original requirement.
				if (is_object($course->course_substitution))
				{
					$course->course_substitution->load_descriptive_data();
				} 
				
			}

			$o_subject_id = $course->course_substitution->subject_id;
			$o_course_num = $course->course_substitution->course_num;

			if ($bool_add_footnote == true)
			{
				$footnote = "";
				$footnote .= "<span class='superscript'>S";
				$fcount = count($this->footnote_array["substitution"]) + 1;
				if ($course->bool_has_been_displayed == true)
				{ // If we've already displayed this course once, and are
					// now showing it again (like in the Transfer Credit list)
					// we do not want to increment the footnote counter.
					$fcount = $course->substitution_footnote;
				}
				$course->substitution_footnote = $fcount;
				$footnote .= "$fcount</span>";
				$this->footnote_array["substitution"][$fcount] = "$o_subject_id $o_course_num ~~ $subject_id $course_num ~~ $course->substitution_hours ~~ $course->assigned_to_group_id";
				
			}
		}

		$hours = $course->hours_awarded;

		if ($hours*1 < 1)
		{
			$hours = $course->get_catalog_hours();
		}

		$hours = $hours * 1;

		$var_hour_icon = "&nbsp;";
		
		
		if ($course->has_variable_hours() == true && !$course->bool_taken)
		{
		  // The bool_taken part of this IF statement is because if the course
		  // has been completed, we should only use the hours_awarded.
		  
			$var_hour_icon = "<img src='" . fp_theme_location() . "/images/var_hour.gif'
								title='" . t("This course has variable hours.") . "'
								alt='" . t("This course has variable hours.") . "'>";
			$hours = $course->get_advised_hours();

		}

		if ($course->bool_ghost_hour == TRUE) {
		  // This course was given a "ghost hour", meaning it is actually
		  // worth 0 hours, not 1, even though it's hours_awarded is currently
		  // set to 1.  So, let's just make the display be 0.
		  $hours = "0";
		}
		
		$grade = $course->grade;

		$dispgrade = $grade;
		// If there is a MID, then this is a midterm grade.
		$dispgrade = str_replace("MID","<span class='superscript'>" . t("mid") . "</span>",$dispgrade);

		if (strtoupper($grade) == "E")
		{ // Currently enrolled.  Show no grade.
			$dispgrade = "";
		}

		if ($course->bool_hide_grade)
		{
		  $dispgrade = "--";
		  $this->bool_hiding_grades = true;
		}
		
		$display_status =  $course->display_status;

		if ($display_status == "completed")
		{
			$pts = $this->get_quality_points($grade, $hours);
		}

		$course_id = $course->course_id;
		$semester_num = $course->assigned_to_semester_num;
		$group_id = $course->assigned_to_group_id;
		$random_id = $course->random_id;
		$advised_hours = $course->advised_hours;

		$unique_id = $course_id . "_" . $semester_num . "_" . rand(1,9999);
		$hid_name = "advisecourse_$course_id" . "_$semester_num" . "_$group_id" . "_$advised_hours" . "_$random_id" . "_$advising_term_id" . "_random" . rand(1,9999);
		$hid_value = "";
		$opchecked = "";
		if ($course->bool_advised_to_take == true)
		{
			$hid_value = "true";
			$opchecked = "-check";
		}

		$op_on_click_function = "toggleSelection";
		if ($js_toggle_and_save == true)
		{
			$op_on_click_function = "toggleSelectionAndSave";
		}

		$extra_js_vars = "";
		if ($course->display_status == "disabled")
		{ // Checkbox needs to be disabled because this was advised in another
			// term.
			$op_on_click_function = "toggleDisabledChangeTerm";
			$course->term_id = $course->advised_term_id;
			$extra_js_vars = $course->get_term_description();

		}

		if ($course->display_status == "completed" || $course->display_status == "enrolled")
		{
			$op_on_click_function = "toggleDisabledCompleted";
			$opchecked = "";
			$extra_js_vars = $course->display_status;
		}

		if ($course->display_status == "retake")
		{
			// this course was probably subbed in while the student
			// was still enrolled, and they have since made an F or W.
			// So, disable it.
			$op_on_click_function = "dummyToggleSelection";
			$opchecked = "";
		}


		if ($this->bool_print || $this->bool_blank)
		{
			// If this is print view, disable clicking.
			$op_on_click_function = "dummyToggleSelection";
		}

		if (!user_has_permission("can_advise_students"))
		{
			// This user does not have the abilty to advise,
			// so take away the ability to toggle anything (like
			// we are in print view).
			$op_on_click_function = "dummyToggleSelection";
		}

		$op = "<img src='$img_path/cb_" . $display_status . "$opchecked.gif'
					border='0'
					id='cb_$unique_id'
					onclick='{$op_on_click_function}(\"$unique_id\",\"$display_status\",\"$extra_js_vars\");'
					>";
		$hid = "<input type='hidden' name='$hid_name'
						id='advisecourse_$unique_id' value='$hid_value'>";

		// Okay, we can't actually serialize a course, as it takes too much space.
		// It was slowing down the page load significantly!  So, I am going
		// to use a function I wrote called to_data_string().

		$data_string = $course->to_data_string();
		$blank_degree_id = "";
		if ($this->bool_blank == true)
		{
			$blank_degree_id = $this->degree_plan->degree_id;
		}

		$js_code = "describeCourse(\"$data_string\",\"$blank_degree_id\");";

		$icon_link = "";

		if ($course->requirement_type == "um" || $course->requirement_type == "uc")
		{
			$icon_filename = "ucap.gif";
			$title_text = t("This course is a University Capstone.");
		}

		if ($icon_filename != "") {
			$icon_link = "<img src='" . fp_theme_location() . "/images/icons/$icon_filename' width='19' height='19' border='0' alt='$title_text' title='$title_text'>";
		}

		$on_mouse_over = " onmouseover=\"style.backgroundColor='#FFFF99'\"
      				onmouseout=\"style.backgroundColor='white'\" ";

		if (fp_screen_is_mobile()) $on_mouse_over = "";  // Causes problems for some mobile devices.
		
		$hand_class = "hand";

		if ($bool_display_check == false) {
			$op = $hid = "";
		}


		if ($this->bool_print) {
			// In print view, disable all popups and mouseovers.
			$on_mouse_over = "";
			$js_code = "";
			$hand_class = "";
		}


		$pC .= "<tr><td colspan='8'>";


		if ($course->bool_substitution_new_from_split != true || ($course->bool_substitution_new_from_split == true && $course->display_status != "eligible")){

			if ($course_num == ""){
				$course_num = "&nbsp;";
			}



			$pC .= "
   		<table border='0' cellpadding='0' width='100%' cellspacing='0' align='left'>
     	<tr height='20' class='$hand_class $display_status'
      		$on_mouse_over title='$title_text'>
      		<td width='$w1_1' align='left'>$op$hid</td>
      		<td width='$w1_2' align='left' onClick='$js_code'>$icon_link</td>
      		<td width='$w1_3' align='left' onClick='$js_code'>&nbsp;$ast</td>
      		<td align='left' width='$w2' class='tenpt underline' onClick='$js_code'>
       				$subject_id</td>
       		<td class='tenpt underline' width='$w3' align='left' 
       			onClick='$js_code'>
        			$course_num$footnote</td>
	       <td class='tenpt underline' width='$w4' onClick='$js_code'>$hours$var_hour_icon</td>
       	   <td class='tenpt underline' width='$w5' onClick='$js_code'>$dispgrade&nbsp;</td>
       	   <td class='tenpt underline' width='$w6' onClick='$js_code'>$pts&nbsp;</td>
     	</tr>
     	</table>";

		} else {
			// These are the leftover hours from a partial substitution.

			$pC .= "
   		<table border='0' cellpadding='0' width='100%' cellspacing='0' align='left'>
     	<tr height='20' class='hand $display_status'
      		$on_mouse_over title='$title_text'>
      		<td width='$w1_1' align='left'>$op$hid</td>
      		<td width='$w1_2' align='left' onClick='$js_code'>$icon_link</td>
      		<td width='$w1_3' align='left' onClick='$js_code'>&nbsp;</td>
      		<td align='left' class='tenpt underline' onClick='$js_code'
      			colspan='4'>
       				&nbsp; &nbsp; $subject_id &nbsp;
        			$course_num$footnote
	       			&nbsp; ($hours " . t("hrs left") . ")
       	   	</td>
     	</tr>
     	</table>";		

		}

		$pC .= "</td></tr>";


		return $pC;
	}


	/**
	 * Calculate the quality points for a grade and hours.
	 *
	 * @param string $grade
	 * @param int $hours
	 * @return int
	 */
	function get_quality_points($grade, $hours){

		switch ($grade) {
			case 'A':
				$pts = 4 * $hours;
				break;
			case 'B':
				$pts = 3 * $hours;
				break;
			case 'C':
				$pts = 2 * $hours;
				break;
			case 'D':
				$pts = 1 * $hours;
				break;
		}
		return $pts;

	}


  /**
   * Used in the group selection popup, this will display a course with 
   * a radio button next to it, so the user can select it.
   *
   * @param Course $course
   * @param int $group_hours_remaining
   * 
   * @return string
   */
	function draw_popup_group_select_course_row(Course $course, $group_hours_remaining = 0)
	{
		// Display a course itself...
		$pC = "";
		$w1_1 = $this->popup_width_array[0];
		$w1_2 = $this->popup_width_array[1];
		$w1_3 = $this->popup_width_array[2];
		$w2 = $this->popup_width_array[3];
		$w3 = $this->popup_width_array[4];
		$w4 = $this->popup_width_array[5];
		$w5 = $this->popup_width_array[6];
		$w6 = $this->popup_width_array[7];

		if ($course->subject_id == "")
		{
			// Lacking course's display data, so reload it from the DB.
			$course->load_course($course->course_id);
		}


		$subject_id = $course->subject_id;
		$course_num = $course->course_num;
		$hours = $course->get_catalog_hours();
		$display_status = $course->display_status;
		$db_group_requirement_id = $course->db_group_requirement_id;
		$grade = $course->grade;
		$repeats = $course->specified_repeats;
		if ($repeats > 0)
		{
			$w3 = "15%";
		}

		$course_id = $course->course_id;
		$group_id = $course->assigned_to_group_id;
		$semester_num = $course->assigned_to_semester_num;

		$var_hour_icon = "&nbsp;";
		if ($course->has_variable_hours() == true)
		{
			$var_hour_icon = "<img src='" . fp_theme_location() . "/images/var_hour.gif'
								title='" . t("This course has variable hours.") . "'
								alt='" . t("This course has variable hours.") . "'>";
		}


		$checked = "";
		if ($course->bool_selected == true)
		{
			$checked = " checked='checked' ";
		}
		$op = "<input type='radio' name='course' value='$course_id' $checked>";
		$hid = "<input type='hidden' name='$course_id" . "_subject'
						id='$course_id" . "_subject' value='$subject_id'>
					<input type='hidden' name='$course_id" . "_db_group_requirement_id'
						id='$course_id" . "_db_group_requirement_id' value='$db_group_requirement_id'>";

		$blank_degree_id = "";
		if ($this->bool_blank)
		{
			$blank_degree_id = $this->degree_plan->degree_id;
		}

		//$serializedCourse = urlencode(serialize($course));
		$js_code = "popupDescribeSelected(\"$group_id\",\"$semester_num\",\"$course_id\",\"$subject_id\",\"group_hours_remaining=$group_hours_remaining&db_group_requirement_id=$db_group_requirement_id&blank_degree_id=$blank_degree_id\");";

		$on_mouse_over = " onmouseover=\"style.backgroundColor='#FFFF99'\"
      				onmouseout=\"style.backgroundColor='white'\" ";
		
		if ($this->page_is_mobile) $on_mouse_over = "";  // Causes problems for some mobile devices.
		
		$hand_class = "hand";
		$extra_style = "";

		if ($course->bool_unselectable == true)
		{
			// Cannot be selected, so remove that ability!
			$hand_class = "";
			$on_mouse_over = "";
			$js_code = "";
			$op = "";
			$extra_style = "style='font-style: italic; color:gray;'";
		}


		$pC .= "
   		<table border='0' cellpadding='0' width='100%' cellspacing='0' align='left'>
     	<tr height='20' class='$hand_class $display_status'
      		$on_mouse_over title='$title_text'>
      		<td width='$w1_1' align='left'>$op$hid</td>
      		<td width='$w1_2' align='left' onClick='$js_code'>$icon_link</td>
      		<td width='$w1_3' align='left' onClick='$js_code'>&nbsp;</td>
      		<td align='left' width='$w2' class='tenpt underline' 
      				onClick='$js_code' $extra_style>
       				$subject_id</td>
       		<td class='tenpt underline' $extra_style width='$w3' align='left' 
       			onClick='$js_code'>
        			$course_num</td>
        	";
		if ($repeats > 0)
		{
			$pC .= "
				<td class='tenpt underline' style='color: gray;' 
					onClick='$js_code' colspan='3'>
				<i>" . t("May take up to") . " <span style='color: blue;'>" . ($repeats + 1) . "</span> " . t("times.") . "</i>
				</td>
			";
		} else {

			$pC .= "
	       <td class='tenpt underline' width='$w4' onClick='$js_code' $extra_style>$hours&nbsp;$var_hour_icon</td>
       	   <td class='tenpt underline' width='$w5' onClick='$js_code'>$grade&nbsp;</td>
       	   <td class='tenpt underline' width='$w6' onClick='$js_code'>$pts&nbsp;</td>
       	   ";
		}

		$pC .= "
     	</tr>
     	</table>";		


		return $pC;
	}



	/**
	 * This is used to display the substitution popup to a user, to let them
	 * actually make a substitution.
	 *
	 * @param int $course_id
	 * @param int $group_id
	 * @param int $semester_num
	 * @param int $hours_avail
	 * 
	 * @return string
	 */
	function display_popup_substitute($course_id = 0, $group_id, $semester_num, $hours_avail = "")
	{
		// This lets the user make a substitution for a course.
		$pC = "";

		$course = new Course($course_id);
		$bool_sub_add = false;

		$c_title = t("Substitute for") . " $course->subject_id $course->course_num";
		if ($course_id == 0)
		{
			$c_title = t("Substitute an additional course");
			$bool_sub_add = true;
		}
		$pC .= fp_render_curved_line($c_title);

		$extra = ".<input type='checkbox' id='cbAddition' value='true' style='display:none;'>";
		if ($group_id > 0)
		{
			$new_group = new Group($group_id);
			$checked = "";
			if ($bool_sub_add == true){$checked = "checked disabled";}
			$extra = " " . t("in the group %newg.", array("%newg" => $new_group->title)) . "
			" . t("Addition only:") . " <input type='checkbox' id='cbAddition' value='true' $checked> 
			   <a href='javascript: alertSubAddition();'>?</a>";
		}

		$c_hours = $course->max_hours*1;
		$c_ghost_hour = "";
		if ($course->bool_ghost_hour == TRUE) {
		  $c_ghost_hour = t("ghost") . "<a href='javascript: alertSubGhost();'>?</a>";
		}

		if (($hours_avail*1 > 0 && $hours_avail < $c_hours) || ($c_hours < 1))
		{

			// Use the remaining hours if we have fewer hours left in
			// the group than the course we are subbing for.
			$c_hours = $hours_avail;
		}

		if ($hours_avail == "" || $hours_avail*1 < 1)
		{
			$hours_avail = $c_hours;
		}

		$pC .= "<div class='tenpt'>
					" . t("Please select a course to substitute
				for %course", array("%course" => "$course->subject_id $course->course_num ($c_hours $c_ghost_hour " . t("hrs") . ")")) . "$extra
				</div>
				
				<div class='tenpt' 
					style='height: 175px; overflow: auto; border:1px inset black; padding: 5px;'>
					<table border='0' cellpadding='0' cellspacing='0' width='100%'>
					
					";
    
		$this->student->list_courses_taken->sort_alphabetical_order(false, true);
    
		for ($t = 0; $t <= 1; $t++)
		{
			if ($t == 0) {$the_title = "{$GLOBALS["fp_system_settings"]["school_initials"]} " . t("Credits"); $bool_transferTest = true;}
			if ($t == 1) {$the_title = t("Transfer Credits"); $bool_transferTest = false;}

			$pC .= "<tr><td colspan='3' valign='top' class='tenpt' style='padding-bottom: 10px;'>
				$the_title
				</td>
				<td class='tenpt' valign='top' >" . t("Hrs") . "</td>
				<td class='tenpt' valign='top' >" . t("Grd") . "</td>
				<td class='tenpt' valign='top' >" . t("Term") . "</td>
				</tr>";
			
			$is_empty = true;
			$this->student->list_courses_taken->reset_counter();
			while($this->student->list_courses_taken->has_more())
			{
				$c = $this->student->list_courses_taken->get_next();
				
				if ($c->bool_transfer == $bool_transferTest)
				{
					continue;
				}

				
				if (!$c->meets_min_grade_requirement_of(null, "D"))
				{// Make sure the grade is OK.
					continue;
				}

				$t_flag = 0;
				if ($c->bool_transfer == true)
				{
					$t_flag = 1;
				}
				$is_empty = false;

				$subject_id = $c->subject_id;
				$course_num = $c->course_num;
				$tcourse_id = $c->course_id;

				if ($bool_transferTest == false)
				{
					// Meaning, we are looking at transfers now.
					// Does the transfer course have an eqv set up?  If so,
					// we want *that* course to appear.
					if (is_object($c->course_transfer))
					{
						$subject_id = $c->course_transfer->subject_id;
						$course_num = $c->course_transfer->course_num;
						$tcourse_id = $c->course_transfer->course_id;
						$t_flag = 1;
					}
				}

				$m_hours = $c->hours_awarded*1;

				if ($c->max_hours*1 < $m_hours)
				{
					$m_hours = $c->max_hours*1;

				}

				if (($hours_avail*1 > 0 && $hours_avail < $m_hours) || ($m_hours < 1))
				{
					$m_hours = $hours_avail;
				}

				// is max_hours more than the original course's hours?
				if ($m_hours > $c_hours)
				{
					$m_hours = $c_hours;
				}

				if ($m_hours > $c->hours_awarded)
				{
					$m_hours = $c->hours_awarded;
				}

				if ($c->bool_substitution != true && $c->bool_outdated_sub != true)
				{
				  $h = $c->hours_awarded;
				  if ($c->bool_ghost_hour == TRUE) {
				    $h .= "(ghost<a href='javascript: alertSubGhost();'>?</a>)";
				  }

					$pC .= "<tr>
						<td valign='top' class='tenpt' width='15%'>
							<input type='radio' name='subCourse' id='subCourse' value='$tcourse_id'
							 onClick='popupUpdateSubData(\"$m_hours\",\"$c->term_id\",\"$t_flag\",\"$hours_avail\",\"$c->hours_awarded\");'>
						</td>
						<td valign='top' class='tenpt underline' width='13%'>
							$subject_id
						</td>
						<td valign='top' class='tenpt underline' width='15%'>
							$course_num
						</td>
						

						<td valign='top' class='tenpt underline' width='10%'>
							$h
						</td>
						<td valign='top' class='tenpt underline' width='10%'>
							$c->grade
						</td>
						<td valign='top' class='tenpt underline'>
							" . $c->get_term_description(true) . "
						</td>

						
					</tr>
					";
				} else {



					if (is_object($c->course_substitution) && $c->course_substitution->subject_id == "")
					{ // Load subject_id and course_num of the original
						// requirement.
						$c->course_substitution->load_descriptive_data();
					}

					$extra = "";
					if ($c->assigned_to_group_id > 0)
					{
						$new_group = new Group($c->assigned_to_group_id);
						$extra = " in $new_group->title";
					}
					if ($c->bool_outdated_sub == true)
					{
						$help_link = "<a href='javascript: popupHelpWindow(\"help.php?i=9\");' class='nounderline'>(?)</a>";
						$extra .= " <span style='color:red;'>[" . t("Outdated") . "$help_link]</span>";
					}

					// It has already been substituted!
					$pC .= "<tr style='background-color: beige;'>
						<td valign='top' class='tenpt' width='15%'>
						 " . t("Sub:") . "
						</td>
						<td valign='top' class='tenpt' colspan='5'>
							$subject_id 
						
							$course_num ($c->substitution_hours)
							 -> " . $c->course_substitution->subject_id . "
							 " . $c->course_substitution->course_num . "$extra
						</td>

						
					</tr>
					";

				}

			}

			if ($is_empty == true)
			{
				// Meaning, there were no credits (may be the case with
				// transfer credits)
				$pC .= "<tr><td colspan='8' class='tenpt'>
							- " . t("No substitutable credits available.") . "
						</td></tr>";
			}

			$pC .= "<tr><td colspan='4'>&nbsp;</td></tr>";
		}


		$pC .= "</table></div>
		<div class='tenpt' style='margin-top: 5px;'>
			" . t("Select number of hours to use:") . "
			<select name='subHours' id='subHours'>
				<option value=''>" . t("None Selected") . "</option>
			</select>
			
		</div>
		<input type='hidden' name='subTransferFlag' id='subTransferFlag' value=''>
		<input type='hidden' name='subTermID' id='subTermID' value=''>
		<input type='button' value='Save Substitution' onClick='popupSaveSubstitution(\"$course_id\",\"$group_id\",\"$semester_num\");'>
		
		<div class='tenpt' style='padding-top: 5px;'><b>" . t("Optional") . "</b> - " . t("Enter remarks:") . " 
		<input type='text' name='subRemarks' id='subRemarks' value='' size='30' maxlength='254'>
		
		</div>
		";


		return $pC;
	}



	/**
	 * This function displays the popup which lets a user select a course to be
	 * advised into a group.
	 *
	 * @param Group $place_group
	 * @param int $group_hours_remaining
	 * @return string
	 */
	function display_popup_group_select(Group $place_group, $group_hours_remaining = 0)
	{
		$pC = "";

		$advising_term_id = $GLOBALS["fp_advising"]["advising_term_id"];

		if ($place_group->group_id != -88)
		{
			// This is NOT the Add a Course group.

			if (!$group = $this->degree_plan->find_group($place_group->group_id))
			{
				fpm("Group not found.");
				return;
			}
		} else {
			// This is the Add a Course group.
			$group = $place_group;
		}

		$group_id = $group->group_id;

		// So now we have a group object, $group, which is most likely
		// missing courses.  This is because when we loaded & cached it
		// earlier, we did not load any course which wasn't a "significant course,"
		// meaning, the student didn't have credit for it or the like.
		// So what we need to do now is reload the group, being careful
		// to preserve the existing courses / sub groups in the group.
		$group->reload_missing_courses();

		if ($group_hours_remaining == 0)
		{
			// Attempt to figure out the remaining hours (NOT WORKING IN ALL CASES!)
			// This specifically messes up when trying to get fulfilled hours in groups
			// with branches.
			$group_fulfilled_hours = $group->get_fulfilled_hours(true, true, false, $place_group->assigned_to_semester_num);
			$group_hours_remaining = $place_group->hours_required - $group_fulfilled_hours;

		}




		$display_semesterNum = $place_group->assigned_to_semester_num;
		$pC .= "<!--MSG--><!--MSG2--><!--BOXTOP-->";

		$bool_display_submit = true;
		$bool_display_back_to_subject_select = false;
		$bool_subject_select = false;
		$bool_unselectableCourses = false;
		$final_course_list = new CourseList();

		$group->list_courses->reset_counter();
		if (!($group->list_courses->is_empty))
		{

			$group->list_courses->assign_semester_num($display_semesterNum);

			$new_course_list = $group->list_courses;
			// Is this list so long that we first need to ask the user to
			// select a subject?
			if ($new_course_list->get_size() > 30)
			{

				// First, we are only going to do this if there are more
				// than 30 courses, AND more than 2 subjects in the list.
				$new_course_list->sort_alphabetical_order();
				$subject_array = $new_course_list->get_course_subjects();
				//print_pre($new_course_list->to_string());
				//var_dump($subject_array);
				if (count($subject_array) > 2)
				{
					// First, check to see if the user has already
					// selected a subject.
					$selected_subject = trim(addslashes($_GET["selected_subject"]));
					if ($selected_subject == "")
					{
						// Prompt them to select a subject first.
						$pC .= $this->draw_popup_group_subject_select($subject_array, $group->group_id, $display_semesterNum, $group_hours_remaining);
						$new_course_list = new CourseList(); // empty it
						$bool_display_submit = false;
						$bool_subject_select = true;
					} else {
						// Reduce the newCourseList to only contain the
						// subjects selected.
						$new_course_list->exclude_all_subjects_except($selected_subject);
						$bool_display_back_to_subject_select = true;
					}
				}
			}

			$new_course_list->reset_counter();			
			$new_course_list->sort_alphabetical_order();

			

			$final_course_list->add_list($new_course_list);
		}

		if (!($group->list_groups->is_empty))
		{
			// Basically, this means that this group
			// has multiple subgroups.  We need to find out
			// which branches the student may select from
			// (based on what they have already taken, or been
			// advised to take), and display it (excluding duplicates).
			//print_pre($group->to_string());
			// The first thing we need to do, is find the subgroup
			// or subgroups with the most # of matches.
			$new_course_list = new CourseList();
			$all_zero= true;

			// Okay, this is a little squirely.  What I need to do
			// first is get a course list of all the courses which
			// are currently either fulfilling or advised for all branches
			// of this group.
			$fa_course_list = new CourseList();
			$group->list_groups->reset_counter();
			while($group->list_groups->has_more())
			{
				$branch = $group->list_groups->get_next();
				$fa_course_list->add_list($branch->list_courses->get_fulfilled_or_advised(true));
			}
			$fa_course_list->remove_duplicates();
			//print_pre($fa_course_list->to_string());
			// Alright, now we create a fake student and set their
			// list_courses_taken, so that we can use this student
			// to recalculate the count_of_matches in just a moment.
			$new_student = new Student();
			$new_student->load_student();
			$new_student->list_courses_taken = $fa_course_list;
			$new_student->load_significant_courses_from_list_courses_taken();

			// Okay, now we need to go through and re-calculate our
			// count_of_matches for each branch.  This is because we
			// have cached this value, and after some advisings, it may
			// not be true any longer.

			$highest_match_count = 0;
			$group->list_groups->reset_counter();
			while($group->list_groups->has_more())
			{
				$branch = $group->list_groups->get_next();
				// recalculate count_of_matches here.
				$clone_branch = new Group();
				$clone_branch->list_courses = $branch->list_courses->get_clone(true);
				$matches_count = $this->flightpath->get_count_of_matches($clone_branch, $new_student, null);
				$branch->count_of_matches = $matches_count;
				if ($matches_count >= $highest_match_count)
				{ // Has more than one match on this branch.

					$highest_match_count = $matches_count;
				}
			}

			// If highestMatchCount > 0, then get all the branches
			// which have that same match count.
			if ($highest_match_count > 0)
			{
				$group->list_groups->reset_counter();
				while($group->list_groups->has_more())
				{
					$branch = $group->list_groups->get_next();

					if ($branch->count_of_matches == $highest_match_count)
					{ // This branch has the right number of matches.  Add it.

						$new_course_list->add_list($branch->list_courses);
						$all_zero = false;
					}

				}

			}
			
			if ($all_zero == true)
			{
				// Meaning, all of the branches had 0 matches,
				// so we should add all the branches to the
				// newCourseList.

				$group->list_groups->reset_counter();
				while($group->list_groups->has_more())
				{
					$branch = $group->list_groups->get_next();
					$new_course_list->add_list($branch->list_courses);
				}
			} else {
				// Meaning that at at least one branch is favored.
				// This also means that a user's course
				// selections have been restricted as a result.
				// Replace the MSG at the top saying so.
				$msg = "<div class='tenpt'>" . t("Your selection of courses has been
							restricted based on previous course selections.") . "</div>";
				$pC = str_replace("<!--MSG-->", $msg, $pC);
			}

			// Okay, in the newCourseList object, we should
			// now have a list of all the courses the student is
			// allowed to take, but there are probably duplicates.
			//print_pre($new_course_list->to_string());


			$new_course_list->remove_duplicates();

			$new_course_list->assign_group_id($group->group_id);
			$new_course_list->assign_semester_num($display_semesterNum);

			$final_course_list->add_list($new_course_list);
			
		}


		//print_pre($final_course_list->to_string());
		// Remove courses which have been marked as "exclude" in the database.
		$final_course_list->remove_excluded();

		//print_pre($final_course_list->to_string());

		// Here's a fun one:  We need to remove courses for which the student
		// already has credit that *don't* have repeating hours.
		// For example, if a student took MATH 113, and it fills in to
		// Core Math, then we should not see it as a choice for advising
		// in Free Electives (or any other group except Add a Course).
		// We also should not see it in other instances of Core Math.
		if ($group->group_id != -88 && $this->bool_blank != TRUE)
		{
			// Only do this if NOT in Add a Course group...
			// also, don't do it if we're looking at a "blank" degree.
			$final_course_list->remove_previously_fulfilled($this->student->list_courses_taken, $group->group_id, true, $this->student->list_substitutions);

		}

		$final_course_list->sort_alphabetical_order();
		if (!$final_course_list->has_any_course_selected()) {
			if ($c = $final_course_list->find_first_selectable()) {
				$c->bool_selected = true;
			}
		}

		// flag any courses with more hours than are available for this group.
		if ($final_course_list->assign_unselectable_courses_with_hours_greater_than($group_hours_remaining))
		{

			$bool_unselectableCourses = true;
		}


		$pC .= $this->display_popup_group_select_course_list($final_course_list, $group_hours_remaining);

		// If there were no courses in the finalCourseList, display a message.
		if (count($final_course_list->array_list) < 1 && !$bool_subject_select)
		{
			$pC .= "<tr>
					<td colspan='8'>
						<div class='tenpt'>
						<b>Please Note:</b> 
						" . t("FlightPath could not find any eligible
						courses to display for this list.  Ask your advisor
						if you have completed courses, or may enroll in
						courses, which can be
						displayed here.");

			if (user_has_permission("can_advise_students")){
			  // This is an advisor, so put in a little more
				// information.
				$pC .= "
									<div class='tenpt' style='padding-top: 5px;'><b>" . t("Special note to advisors:") . "</b> " . t("You may still
											advise a student to take a course, even if it is unselectable
											in this list.  Use the \"add an additional course\" link at
											the bottom of the page.") . "</div>";
			}
			$pC .= "						</div>
					</td>
					</tr>";
			$bool_no_courses = true;
		}

		$pC .= $this->draw_semester_box_bottom();

		$s = "s";
		//print_pre($place_group->to_string());

		if ($group_hours_remaining == 1){$s = "";}
		if ($bool_unselectableCourses == true) {
			$unselectable_notice = " <div class='tenpt'><i>(" . t("Courses worth more than %hrs hour$s
								may not be selected.", array("%hrs" => $group_hours_remaining)) . ")</i></div>";
			if (user_has_permission("can_advise_students")) {
				// This is an advisor, so put in a little more
				// information.
				$unselectable_notice .= "
									<div class='tenpt' style='padding-top: 5px;'><b>" . t("Special note to advisors:") . "</b> " . t("You may still
											advise a student to take a course, even if it is unselectable
											in this list.  Use the \"add an additional course\" link at
											the bottom of the page.") . "</div>";
			}
		}

		if ($group_hours_remaining < 100 && $bool_no_courses != true)	{ 
		  // Don't show for huge groups (like add-a-course)
			$pC .= "<div class='elevenpt' style='margin-top:5px;'>
					" . t("You may select <b>@hrs</b>
						hour$s from this list.", array("@hrs" => $group_hours_remaining)) . "$unselectable_notice</div>";
		}

		if ($bool_display_submit == true && !$this->bool_blank && $bool_no_courses != true)
		{
			if (user_has_permission("can_advise_students")) {
				$pC .= "<input type='hidden' name='varHours' id='varHours' value=''>
					<div style='margin-top: 20px;'>
					
					
				" . fp_render_button(t("Select Course"), "popupAssignSelectedCourseToGroup(\"$place_group->assigned_to_semester_num\", \"$group->group_id\",\"$advising_term_id\",\"-1\");", true, "style='font-size: 10pt;'") . "
					</div>
				";
			}

		}

		// Substitutors get extra information:
		if (user_has_permission("can_substitute") && $group->group_id != -88)
		{
			$pC .= "<div class='tenpt' style='margin-top: 20px;'>
					<b>" . t("Special administrative information:") . "</b>
					
				<span id='viewinfolink'
				onClick='document.getElementById(\"admin_info\").style.display=\"\"; this.style.display=\"none\"; '
				class='hand' style='color: blue;'
				> - " . t("Click to show") . " -</span>					
					
					<div style='padding-left: 20px; display:none;' id='admin_info'>
					" . t("Information about this group:") . "<br>
					&nbsp; " . t("Group ID:") . " $group->group_id<br>
					&nbsp; " . t("Title:") . " $group->title<br>";
  		$pC .= "&nbsp; <i>" . t("Internal name:") . " $group->group_name</i><br>";

			$pC .= "&nbsp; " . t("Catalog year:") . " $group->catalog_year
					</div>
					
					</div>";						
		}


		if ($bool_display_back_to_subject_select == true) {
			$csid = $GLOBALS["current_student_id"];
			$blank_degree_id = "";
			if ($this->bool_blank)
			{
				$blank_degree_id = $this->degree_plan->degree_id;
			}
			$back_link = "<span class='tenpt'>
						<a href='" . base_path() . "/advise/popup-group-select&window_mode=popup&group_id=$group->group_id&semester_num=$display_semesterNum&group_hours_remaining=$group_hours_remaining&current_student_id=$csid&blank_degree_id=$blank_degree_id' 
						class='nounderline'>" . t("Click here to return to subject selection.") . "</a></span>";
			$pC = str_replace("<!--MSG2-->",$back_link,$pC);
		}

		$box_top = $this->draw_semester_box_top("$group->title", !$bool_display_submit);
		$pC = str_replace("<!--BOXTOP-->",$box_top,$pC);

		return $pC;
	}


	/**
	 * When the groupSelect has too many courses, they are broken down into
	 * subjects, and the user first selects a subject.  This function will
	 * draw out that select list.
	 *
	 * @param array $subject_array
	 * @param int $group_id
	 * @param int $semester_num
	 * @param int $group_hours_remaining
	 * @return string
	 */
	function draw_popup_group_subject_select($subject_array, $group_id, $semester_num, $group_hours_remaining = 0)
	{
		$csid = $GLOBALS["current_student_id"];
		$blank_degree_id = "";
		if ($this->bool_blank)
		{
			$blank_degree_id = $this->degree_plan->degree_id;
		}
		$pC .= "<tr><td colspan='8' class='tenpt'>";
		$pC .= "<form action='" . base_path() . "/advise/popup-group-select' method='GET' style='margin:0px; padding:0px;' id='theform'>
					<input type='hidden' name='window_mode' value='popup'>
					<input type='hidden' name='group_id' value='$group_id'>
					<input type='hidden' name='semester_num' value='$semester_num'>
					<input type='hidden' name='group_hours_remaining' value='$group_hours_remaining'>
					<input type='hidden' name='current_student_id' value='$csid'>
					<input type='hidden' name='blank_degree_id' value='$blank_degree_id'>
		
					" . t("Please begin by selecting a subject from the list below.") . "
					<br><br>
					<select name='selected_subject'>
					<option value=''>" . t("Please select a subject...") . "</option>
					<option value=''>----------------------------------------</option>
					";
		$new_array = array();
		foreach($subject_array as $key => $subject_id)
		{

			if ($title = $this->flightpath->get_subject_title($subject_id)) {
				$new_array[] = "$title ~~ $subject_id";
			} else {
			  $new_array[] = "$subject_id ~~ $subject_id";
			}
			
		}

		sort($new_array);

		foreach ($new_array as $key => $value)
		{
			$temp = split(" ~~ ",$value);
			$title = trim($temp[0]);
			$subject_id = trim($temp[1]);
			$pC .= "<option value='$subject_id'>$title</option>";
		}

		$pC .= "</select>
				<div style='margin: 20px;' align='left'>
				" . fp_render_button(t("Next") . " ->","document.getElementById(\"theform\").submit();") . "
				</div>
					<!-- <input type='submit' value='submit'> -->
					
			  			</form>
			  ";
		$pC .= "</td></tr>";

		return $pC;
	}


	/**
	 * Accepts a CourseList object and draws it out to the screen. Meant to 
	 * be called by display_popup_group_select();
	 *
	 * @param CourseList $course_list
	 * @param int $group_hours_remaining
	 * @return string
	 */
	function display_popup_group_select_course_list(CourseList $course_list = null, $group_hours_remaining = 0)
	{
		// Accepts a CourseList object and draws it out to the screen.  Meant to
		// be called by display_popup_group_select().
		$pC = "";

		if ($course_list == null)
		{

			return;
		}

		$old_course = null;

		$course_list->reset_counter();
		while($course_list->has_more())
		{
			$course = $course_list->get_next();
			if ($course->equals($old_course))
			{ // don't display the same course twice in a row.
				continue;
			}


			$pC .= "<tr><td colspan='8'>";

			if ($course->course_list_fulfilled_by->is_empty && !$course->bool_advised_to_take)
			{ // So, only display if it has not been fulfilled by anything.
				$pC .= $this->draw_popup_group_select_course_row($course, $group_hours_remaining);
				$old_course = $course;
			} 
			$pC .= "</td></tr>";
		}


		return $pC;
	}




	/**
	 * Returns a list of "hidden" HTML input tags which are used to keep
	 * track of advising variables between page loads.
	 *
	 * @param string $perform_action
	 *       - Used for when we submit the form, so that FlightPath will
	 *         know what action we are trying to take.
	 * 
	 * @return string
	 */
	function get_hidden_advising_variables($perform_action = "")
	{
		$rtn = "";

		$rtn .= "<span id='hidden_elements'>
		
			<input type='hidden' name='perform_action' id='perform_action' value='$perform_action'>
			<input type='hidden' name='perform_action2' id='perform_action2' value=''>
			<input type='hidden' name='scroll_top' id='scroll_top' value=''>
			<input type='hidden' name='load_from_cache' id='load_from_cache' value='yes'>
			<input type='hidden' name='print_view' id='print_view' value='{$GLOBALS["print_view"]}'>			
			<input type='hidden' name='hide_charts' id='hide_charts' value=''>
			
			<input type='hidden' name='advising_load_active' id='advising_load_active' value='{$GLOBALS["fp_advising"]["advising_load_active"]}'>
			<input type='hidden' name='advising_student_id' id='advising_student_id' value='{$GLOBALS["fp_advising"]["advising_student_id"]}'>
			<input type='hidden' name='advising_term_id' id='advising_term_id' value='{$GLOBALS["fp_advising"]["advising_term_id"]}'>
			<input type='hidden' name='advising_major_code' id='advising_major_code' value='{$GLOBALS["fp_advising"]["advising_major_code"]}'>
			<input type='hidden' name='advising_track_code' id='advising_track_code' value='{$GLOBALS["fp_advising"]["advising_track_code"]}'>
			<input type='hidden' name='advising_update_student_settings_flag' id='advising_update_student_settings_flag' value=''>
			<input type='hidden' name='advising_what_if' id='advising_what_if' value='{$GLOBALS["fp_advising"]["advising_what_if"]}'>
			<input type='hidden' name='what_if_major_code' id='what_if_major_code' value='{$GLOBALS["fp_advising"]["what_if_major_code"]}'>
			<input type='hidden' name='what_if_track_code' id='what_if_track_code' value='{$GLOBALS["fp_advising"]["what_if_track_code"]}'>
			<input type='hidden' name='advising_view' id='advising_view' value='{$GLOBALS["fp_advising"]["advising_view"]}'>

			<input type='hidden' name='current_student_id' id='current_student_id' value='{$GLOBALS["fp_advising"]["current_student_id"]}'>
			<input type='hidden' name='log_addition' id='log_addition' value=''>
			
			<input type='hidden' name='fp_update_user_settings_flag' id='fp_update_user_settings_flag' value=''>
			
			</span>
			";

		return $rtn;
	}


}
?>