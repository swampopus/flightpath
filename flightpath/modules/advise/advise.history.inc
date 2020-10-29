<?php

/*
 * This file is responsible for the contents / functionality of the History tab. 
*/


/**
 * Displays the history tab on screen.
 */
function advise_display_history() {
  global $current_student_id;
  
  $student_id = $current_student_id;
  $db = get_global_database_handler();
  
  fp_set_title('');
  
  $extra_style = $ast = "";
  $rtn = "";
  
  fp_add_css(fp_get_module_path("advise") . "/css/advise.css");  
  fp_add_js(fp_get_module_path("advise") . "/js/advise.js");
  
    
  $rtn .= "<table class='fp-semester-table'>";

 
  // If coming here from a save, display info here.
  if (@$_REQUEST["adv_id_array_serialized"] != "") {
    $click_links = "";
    $advising_session_id_array = unserialize($_REQUEST["adv_id_array_serialized"]);
 
    foreach($advising_session_id_array as $term_id=>$value) {
      $term_id = (string) $term_id;  // Make sure term_id is a standard string
      $aid = $advising_session_id_array[$term_id];
      if ($aid != "")
      {        
        $term_name = get_term_description($term_id);
        
        $turl = fp_url("advise/popup-display-summary", "advising_session_id=$aid");
        
        /*
        $click_links .= "<li>
                <a href='javascript: popupPrintWindow(\"" . $turl . "\");'>
                <img src='" . fp_theme_location() . "/images/popup.gif' border='0'>
                $term_name</a>";
        */
        $click_links .= "<li class='just-saved-summary'>
                            <a href='javascript: popupLargeIframeDialog(\"" . $turl . "\",\"" . t("Advising Session @term", array("@term" => $term_name)) . "\",\"\");'>
                                <i class='fa fa-window-restore'></i> $term_name
                            </a>
                         </li>";        
                
                

      }
    }


    $rtn .= "
        <tr>
          <td colspan='2' width='100%'>
          
        <div class='hypo' 
        align='left' style='border: 1px solid black;
              margin: 10px 0px 10px 0px; padding: 10px; 
              font-size: 12pt; font-weight: bold;'>
        " . t("You have successfully advised") . " " . $db->get_student_name($student_id) . " ($student_id).
        <br>" . t("Click to view a pop-up printable summary for:") . " 
         <ul style='margin-top: 5px; margin-bottom: 5px;'>
        $click_links
        </ul></div>
        
        </td>
        </tr>
    ";

  }
    

   



  $rtn .= "<tr><td width='50%' valign='top'  style='padding-right: 10px;'>
          ";

  ///////////////////////////////////////////////////
  //////////////////////////////////////////////////
  /////////  Advising History
  ///////////////////////////////////////////////////
  $rtn .= fp_render_section_title(t("Advising History"));
  $render_left = array();  // creating a new render array.
  $render_left["#id"] = "advise_history_left_column";
  $render_left["#student_id"] = $student_id;
  
  //$rtn .= "<table border='0' cellspacing='0'>";
  $old_session_d_t = 0;
  $a_count = 0;
  $c = 0;
  $is_empty = true;
  $first_style = "color: maroon; font-weight:bold;";
  $advising_session_id_array = array();
  
  $on_mouse_over = "
            onmouseover='$(this).addClass(\"selection_highlight\");'
            onmouseout='$(this).removeClass(\"selection_highlight\");'
    ";      
  
  $html = "";
  

  $res = db_query("SELECT * FROM advising_sessions
              WHERE student_id = '?'
              AND is_draft = '0'
              AND is_empty = '0'
              ORDER BY `posted` DESC, `term_id` DESC ", $student_id);
  while($cur = db_fetch_array($res)) {
    extract($cur, 3, "db");

    $dt = date("n/j/y g:ia",$db_posted);
    $html = "";

    // Is this datetime within 5 seconds of the previous datetime?
    // If so, they were likely saved together, and are part
    // of the same advising session.  Otherwise, this is a NEW
    // advising session.  The if statement below is testing is this
    // a new advising session.
    $test_d_t = $db_posted;
    if ($old_session_d_t < ($test_d_t - 5) || $old_session_d_t > ($test_d_t + 5))
    {

      $old_session_d_t = $test_d_t;
      $advised_by = "<div class='history-advised-row history-advised-row-$a_count'>
              <strong>" . t("Advised by") . " " . fp_get_faculty_name($db_faculty_id, false) . "</strong>
            </div>";
      
      
      $html .= "<div class=' '>$advised_by</div>";      
      
      
            
          
      $a_count++;

    }
    $is_empty = false;

    if ($a_count > 1) {
      $first_style = "";
    }

    $turl = fp_url("advise/popup-display-summary", "advising_session_id=$db_advising_session_id");

    $advising_session_id_array[] = $db_advising_session_id;
    $term = get_term_description($db_term_id);

    $on_click = "popupLargeIframeDialog(\"" . $turl . "\",\"" . t("Advising Session @term - @date", array("@term" => $term, "@date" => $dt)) . "\",\"\");";  
              
    
    
    $html .= "<div $on_mouse_over style='cursor: pointer; $first_style' onClick='$on_click'>
                <div class=' ' style='display: inline-block; padding-left:20px; width:165px;'>$term</div>
                <div class=' ' style='display: inline-block;'>$dt</div>
              </div>";

    // If this is more than X number, let's hide the rest in a collapsible fieldset.
    if ($c < 5) {
      $render_left["advising_history_$c"] = array(
        "value" => $html,
        "weight" => $c,
      );
    }
    else {
      // Let's place in a collapsible fieldset!
      if (!isset($render_left["advising_history_fs"])) {
        $render_left["advising_history_fs"] = array(
          "type" => "cfieldset",
          "label" => t("Show complete advising history"),
          "start_closed" => TRUE,
          "elements" => array(),
          "weight" => $c,          
        );
      }
      $render_left["advising_history_fs"]["elements"][] = array("advising_history_$c" => array(
        "value" => $html,
      ));
        
      
    }
        
        
    $c++;
  }
  //$rtn .= "</table>";

  if ($is_empty == true) {
    //$rtn .= "<div class=' '>No advising history available.</div>";
    
    $render_left["markup_no_adv_history"] = array(
      "value" => t("No advising history available."),
    );
  }
  else {
    
    $print_all_url = fp_url("advise/popup-display-summary", "advising_session_id=" . join(",", $advising_session_id_array));
    
    $render_left["markup_print_all"] = array(
      "value" => "<a href='javascript: popupLargeIframeDialog(\"" . $print_all_url . "\",\"" . t("Complete Advising History") . "\",\"\");' class='view-print-all'>
                      <i class='fa fa-window-restore'></i> " . t("View Complete History") . "
                  </a>
          ",
      "weight" => -1,
    );
  }

  // Okay, we can render the left-hand side now.
  $rtn .= fp_render_content($render_left);




  //----------------------------------------------------------------------------------------
  //------------------------------ COMMENT HISTORY -----------------------------------------
  //----------------------------------------------------------------------------------------
  $rtn .= "</td><td width='50%' valign='top'>";
  $rtn .= fp_render_section_title(t("Comment History"));
  //$rtn .= "<table border='0' cellspacing='0'>";
  
  // Prepare a render array for the right-hand side.
  $render_right = array();
  $render_right["#id"] = "advise_history_right_column";
  $render_right["#student_id"] = $student_id;
  
  $html = "";
  
  $old_term_id = "";
  $first_style = "first";
  $is_empty = true;
  $has_admin_category = false;
  $access_line = "";
  
  //if (!user_has_permission("view_faculty_comments")) { 
  //  $access_line = "and `access_type`='public' ";    
  //}
  
  $access_types = (user_has_permission("view_faculty_comments")) ? array("faculty", "public") : array("public");

  
  $turl = fp_url("comments/popup-display-all-comments");
    
   
  $html .= "<div class=' '>
        <!--STARTCOM--><div style='padding-top: 10px;'>
          <strong>" . t("Advising Comments") . "</strong>
          &nbsp; 
          <a href='javascript: popupLargeIframeDialog(\"" . $turl . "\",\"" . t("All Comments for Student") . "\",\"\");' class='view-print-all'>
            <i class='fa fa-window-restore'></i> " . t("View All Comments") . "
          </a>
        </div><!--ENDCOM-->
        </div>";


// Use the function comments_get_comments instead of a direct query.

  $res = comments_get_comments($student_id, FALSE, $access_types);
  foreach ($res as $cur)
  {
    extract($cur, 3, "db");
    $dt = format_date($db_posted);

    if ($first_style == "first")
    {
      $first_style = "color: maroon; font-weight:bold;
          ";
    }


    $turl = fp_url("comments/popup-display-comment", "id=$db_id");
    
    $on_click = "popupLargeIframeDialog(\"" . $turl . "\",\"" . t("Comment @date", array("@date" => $dt)) . "\",\"\");";    
    
    
    $html .=  "<div $on_mouse_over class='history-comment-row' style='cursor:pointer; $first_style $extra_style'
          onClick='$on_click'>
          <div class=' ' style='padding-left: 20px; width:165px; display:inline-block;'>
            " . fp_get_faculty_name($db_faculty_id, false) . "
          </div>
          <div class=' ' style='display: inline-block;'>
              $dt$ast
          </div>
        </div>";
      
    $is_empty = false;
    $first_style = "";
  }

  if ($is_empty == true) {
    /*
    $rtn .= "<tr><td colspan='4' class=' '>
            <div style='padding-left: 20px;'>
              " . t("No comment history available.") . "</div></td></tr>";
    */
    
    $render_right["markup_no_comment_history"] = array(
      "value" => t("No comment history available."),
    );                  
              
  }

  $render_right["comment_history"] = array(
    "value" => $html,
    "weight" => 0,
  );
  
  //$rtn .= "</table>";

  // Render our render array now...
  $rtn .= fp_render_content($render_right);
  
  
  

  $rtn .= "</td></tr>";

  
  
  
  $rtn .= "</table>";
  
  
  // Let's set our breadcrumbs
  $db = get_global_database_handler();
  $crumbs = array();
  $crumbs[] = array(
    'text' => 'Students',
    'path' => 'student-search',
  );
  $crumbs[] = array(
    'text' => $db->get_student_name($current_student_id) . " ({$current_student_id})",
    'path' => 'student-profile',
    'query' => "current_student_id={$current_student_id}",
  );  
  fp_set_breadcrumbs($crumbs);    
  
  
  
  return $rtn;
}


/**
 * Displays the printable advising summary.
 * 
 * If the advising_session_id is null, it gets that value from the REQUEST.  Otherwise, we expect it to be a csv of the
 * sessions we are interested in.
 * 
 * If bool_html is set to FALSE, then we will return results in plain text, suitable for email.
 * 
 */
function advise_popup_display_summary($advising_session_id = NULL, $bool_html = TRUE) {
  $rtn = "";
  $txt = "";
  
  $db = get_global_database_handler();
  
  if ($advising_session_id == NULL) {
    $advising_session_id = rtrim(trim($_REQUEST["advising_session_id"]), ",");
  }
  
  $bool_multiple = FALSE;
  
  // If we have commas in our advising_session_id, it means we had more than one that were passed to us!  We want to show
  // them all. 
  
  if (!strstr($advising_session_id, ",")) {
    $res = db_query("SELECT * FROM advising_sessions
                     WHERE advising_session_id = ? ", $advising_session_id);
  }
  else {
    
    $bool_multiple = TRUE;
    $session_array = csv_to_array($advising_session_id);
        
    $where = "";
    foreach ($session_array as $sid) {
      $where .= " advising_session_id = ? OR";
    }
    $where = rtrim($where, "OR");
    $res = db_query("SELECT * FROM advising_sessions
                     WHERE $where 
                     ORDER BY `posted` DESC ", $session_array);
        
  }
  while ($cur = db_fetch_array($res)) {
    extract($cur, 3, "db");
  
    $dt = format_date($db_posted, "pretty");
  
    $term = get_term_description($db_term_id);
    
    $degree_title_line = "";
    $ds = "";
    $degree_plan = new DegreePlan();
    if ($db_degree_id != DegreePlan::DEGREE_ID_FOR_COMBINED_DEGREE) {
      $degree_plan->degree_id = $db_degree_id;
      $degree_plan->load_descriptive_data();
      $degree_title_line = $degree_plan->get_title(true);    
    }
    else {
      // This is a set of combined degrees.  We need to load all of the
      // degrees in the major_code_csv to get their titles.
      $temp = explode(",", $db_major_code_csv);
      $ds = "s";
      foreach ($temp as $major_code) {
        if ($major_code == "") continue;      
        $dp = $db->get_degree_plan($major_code, $db_catalog_year);
        if ($dp != null) {
          $degree_title_line .= $dp->get_title2(TRUE, TRUE) . ", ";
        }      
      }
      $degree_title_line = rtrim($degree_title_line, ", ");
    }
  
    $student = new Student($db_student_id, $db);
  
    $what_if_message = "";
    if ($db_is_whatif == 1) {
        
      $official_degree_title = "";
      $odsverb = " is";
      $c = 0;
      $majors = $db->get_student_majors_from_db($student->student_id);
      foreach ($majors as $major_code) {      
        if ($major_code == "") continue;
              
        $dp = $db->get_degree_plan($major_code, $db_catalog_year);
        if ($dp != null) {
          $c++;
          $official_degree_title .= $dp->get_title2(TRUE, TRUE) . ", ";
        }      
      }
      if ($c > 1) $odsverb = "s are";
      
      $official_degree_title = rtrim($official_degree_title, ", ");    
        
      $what_if_message = "<b>" . t("Note:") . "</b>
                " . t("This advising was saved using <b>What If</b> mode
                for the degree$ds %degree_title.  According to @initials records,
                the student's official degree$odsverb %official.", array("%degree_title" => $degree_title_line, 
                                                              "@initials" => $GLOBALS["fp_system_settings"]["school_initials"], 
                                                              "%official" => $official_degree_title));
    }
  
    
    $rtn .= "<table class='adv-history-table' width='100%'><td valign='top'>";
    
    if ($page_is_mobile) {
  
    }
    else {
    
      $rtn .= "
        <table class='  summary-transaction-details' border='0' width='100%' cellpadding='3' cellspacing='0' style='border-width: 2px; border-style: solid; border-color: black;'>
         <tr height='7'>
          <td> </td>
         </tr>
         <tr>
          <td valign='top' width='15%'>" . t("Student:") . "</td>
          <td valign='top' width='40%'>" . $db->get_student_name($db_student_id, false) . " ($db_student_id)</td>
          <td valign='top' rowspan='3'>
            <table width='100%' cellpadding='0' cellspacing='0'>
          <tr height='20'>
           <td width='100%' id='student-signature-line' valign='bottom'><hr noshade size='1' width='100%' color='black' style='margin: 0px;'></td>
          </tr>
          <tr height='20'>
           <td width='100%' id='student-signature-caption' valign='top' align='right'><span style='font-size: 8pt;'>" . t("Student signature") . "</span></td>
          </tr>
  
          <tr height='20'>
           <td width='100%'  id='advisor-signature-line' valign='bottom'><hr noshade size='1' width='100%' color='black' style='margin: 0px;'></td>
          </tr>
          <tr>
           <td width='100%'  id='advisor-signature-caption'valign='bottom' align='right'><span style='font-size: 8pt;'>" . t("Advisor signature") . "</span></td>
          </tr>
         <tr height='7'>
          <td> </td>
         </tr>
    
          
          </table> ";
      
      $txt .= "\n-----  Advising Summary for $term -----\n";    
      $txt .= "Student: " . $db->get_student_name($db_student_id, false) . " ($db_student_id) \n";    
          
    } // else 
    
    $rtn .= "
          </td>
         </tr>
         <tr>
          <td valign='top' width='10%'>" . t("Advisor:") . "</td>
          <td valign='top'>" . fp_get_faculty_name($db_faculty_id, false) . "</td>
  
         </tr>
         <tr>
          <td valign='top'>" . t("Term:") . "</td>
          <td valign='top'>$term</td>
  
         </tr>
         ";
    
    $txt .= "Advisor: " . fp_get_faculty_name($db_faculty_id, false) . "\n";
    $txt .= "Term: " . $term . "\n";
    
    if (!$page_is_mobile) {
      $rtn .= "
         <tr>
          <td valign='top' colspan='2'  id='summary-alternate-term'>
          " . t("Alternate Term:") . " _____________________
          </td>
         </tr>";
    }
         
    $rtn .= "   
         <tr>
          <td valign='top' colspan='4'>
          ";
  
    if ($degree_title_line != "")
    {
      $rtn .= t("Degree$ds:") . " $degree_title_line";
      
      $txt .= "Degree$ds: " . $degree_title_line . "\n";
      
    }
  
    $rtn .= "
         </tr>
         </table>
         <div class=' '><i>" . t("Submitted on") . " $dt.</i></div>
         <div class=' '>$what_if_message</div>
         <br>
      ";
  
    $txt .= "Submitted on $dt \n";
    if ($what_if_message) {
      $txt .= "  " . fp_reduce_whitespace(strip_tags($what_if_message)) . "\n";
    }
    
    $rtn .= fp_render_section_title(t("Advised Courses"));
  
    $txt .= "\nAdvised Courses:\n";
  
    $fp = new FlightPath($student,$degree_plan, $db);
      
    $fp->load_advising_session_from_database("","",false,false,$db_advising_session_id);
  
    $rtn .= "<table border='0' cellpadding='3' class='summary-advised-courses'>
        <tr>
          <td class='  course-si-cn' valign='top' width='25%'>
            <b>" . t("Course") . "</b>
          </td>
          <td class='  course-title' valign='top' width='70%'>
            <b>" . t("Title") . "</b>
          </td>
          <td class='  course-hours' valign='top'>
            <b>" . t("Hours") . "</b>
          </td>
        </tr>";
  
  
  
    // Get a courseList of all the courses which are set as advised to take.
    $advised_courses_list = $fp->course_list_advised_courses;
    $advised_courses_list->load_course_descriptive_data();
    $advised_courses_list->sort_alphabetical_order();
    $advised_courses_list->reset_counter();
    while ($advised_courses_list->has_more()) {
      $course = $advised_courses_list->get_next();
      // set the catalogYear from the term_id.
      $course->term_id = $db_term_id;
      $course->set_catalog_year_from_term_id();
      $course->load_descriptive_data(false);
  
      $rtn .= "<tr>
            <td class='  course-si-cn' valign='top'>
            $course->subject_id $course->course_num
            </td>
            <td class='  course-title' valign='top'>
            $course->title
            </td>
            <td class='  course-hours' valign='top' align='center'>
            " . $course->get_hours() . " 
            </td>
            
          </tr>
        ";
        
        $txt .= "  $course->subject_id $course->course_num - $course->title - " . $course->get_hours() . "hrs \n"; 
    }
  
    $rtn .= "</table>
        <div align='right' class='  total-advised-hours' style='padding-top: 10px; padding-right: 15px;'>
          <b>" . t("Total advised hours:") . " &nbsp; " . $advised_courses_list->count_hours() . "</b>
        </div>
        ";
  
    $txt .= "Total advised hours: " . $advised_courses_list->count_hours() . "\n";
    $txt .= "\n\n";
  
    if (!$page_is_mobile) {
      $rtn .= "<br>";
      $rtn .= "<div class='summary-alternate-courses'>";
      $rtn .= fp_render_section_title(t("Alternate Courses"));
      $rtn .= "<div class='  alt-courses-desc-and-lines'>
          " . t("You may use this space to write in alternate courses 
          the student should enroll in, in the event that an 
          advised course is full or unavailable.") . "
          <br><br>
          ___________________________________________________________________ <br><br>
          ___________________________________________________________________
          </div>  ";
      $rtn .= "</div>";
    }
  
    $rtn .= "</table>";
    
  } // while, for the query itself!    

  // Are we NOT returning html?
  if (!$bool_html) {
    return $txt;
  }
  
  fp_set_title($db->get_student_name($db_student_id, FALSE) . " ($db_student_id) $term " . t("Advising Summary"));
  return $rtn;
}









