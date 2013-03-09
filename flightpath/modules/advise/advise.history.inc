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
  
  $rtn = "";
  
  fp_add_css(fp_get_module_path("advise") . "/css/advise.css");
  fp_add_js(fp_get_module_path("advise") . "/js/advise.js");
  
  
  $rtn .= "<table class='fp-semester-table'>";

 
  // If coming here from a save, display info here.
  if ($_REQUEST["adv_id_array_serialized"] != "") {
    $click_links = "";
    $advising_session_id_array = unserialize($_REQUEST["adv_id_array_serialized"]);
 
    foreach($advising_session_id_array as $term_id=>$value) {
      $term_id = (string) $term_id;  // Make sure term_id is a standard string
      $aid = $advising_session_id_array[$term_id];
      if ($aid != "")
      {        
        $term_name = get_term_description($term_id);
        
        $click_links .= "<li>
                <a href='javascript: popupPrintWindow(\"" . base_path() . "/advise/popup-display-summary&advising_session_id=$aid\");'>
                <img src='" . fp_theme_location() . "/images/popup.gif' border='0'>
                $term_name</a>";

      }
    }


    // onClick='popup_print_window(\"advise.php?window_mode=summary&advising_session_id=ADVID\");'
    $rtn .= "
        <tr>
          <td colspan='2' width='100%'>
          
        <div class='hypo' 
        align='left' style='border: 1px solid black;
              margin: 10px 0px 10px 0px; padding: 10px; 
              font-size: 12pt; font-weight: bold;'>
        " . t("You have successfully advised") . " " . $db->get_student_name($student_id) . " ($student_id).
        <br><span style='color: blue;'>" . t("Click 
         to view a pop-up printable summary for:") . " 
         <ul style='margin-top: 5px; margin-bottom: 5px;'>
        $click_links
        </ul></span></div>
        
        </td>
        </tr>
    ";

  }
    

   



  $rtn .= "<tr><td width='50%' valign='top'  style='padding-right: 10px;'>";

  ///////////////////////////////////////////////////
  //////////////////////////////////////////////////
  /////////  Advising History
  ///////////////////////////////////////////////////
  $rtn .= fp_render_curved_line(t("Advising History"));
  $rtn .= "<table border='0' cellspacing='0'>";
  $old_session_d_t = 0;
  $a_count = 0;
  $is_empty = true;
  $first_style = "color: maroon; font-weight:bold;";
  $on_mouse_over = "onmouseover=\"style.backgroundColor='#FFFF99'\"
           onmouseout=\"style.backgroundColor='white'\" ";

  $res = db_query("SELECT * FROM advising_sessions
              WHERE student_id = '?'
              AND is_draft = '0'
              AND is_empty = '0'
              ORDER BY `posted` DESC, `term_id` DESC ", $student_id);
  while($cur = db_fetch_array($res)) {
    extract($cur, 3, "db");

    $dt = date("n/j/y g:ia",$db_posted);

    // Is this datetime within 5 seconds of the previous datetime?
    // If so, they were likely saved together, and are part
    // of the same advising session.  Otherwise, this is a NEW
    // advising session.  The if statement below is testing is this
    // a new advising session.
    $test_d_t = $db_posted;
    if ($old_session_d_t < ($test_d_t - 5) || $old_session_d_t > ($test_d_t + 5))
    {
      $p = "20px;";
      if ($a_count == 0)
      {
        $p = "10px;";
      }

      $old_session_d_t = $test_d_t;
      $advised_by = "<div style='padding-top: $p'>
              <b>" . t("Advised by") . " " . fp_get_faculty_name($db_faculty_id, false) . "</b>
            </div>";

      $rtn .= "<tr><td colspan='2' class='tenpt'>
              $advised_by
            </td>
          </tr>";
      $a_count++;

    }
    $is_empty = false;

    if ($a_count > 1) {
      $first_style = "";
    }


    $on_click = "popupPrintWindow(\"" . base_path() . "/advise/popup-display-summary&advising_session_id=$db_advising_session_id\");";

    $term = get_term_description($db_term_id);

    $rtn .= "<tr $on_mouse_over style='cursor: pointer; $first_style'
          onClick='$on_click'>
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
  $rtn .= "</table>";

  if ($is_empty == true) {
    $rtn .= "<div class='tenpt'>No advising history available.</div>";
  }


  //----------------------------------------------------------------------------------------
  //------------------------------ COMMENT HISTORY -----------------------------------------
  //----------------------------------------------------------------------------------------
  $rtn .= "</td><td width='50%' valign='top'>";
  $rtn .= fp_render_curved_line(t("Comment History"));
  $rtn .= "<table border='0' cellspacing='0'>";

  $old_term_id = "";
  $first_style = "first";
  $is_empty = true;
  $has_admin_category = false;
  $access_line = "";
  
  if (!user_has_permission("view_faculty_comments")) { 
    $access_line = "and `access_type`='public' ";    
  }
  
  $rtn .= "<tr><td colspan='3' class='tenpt'>
        <!--STARTCOM$cat_type--><div style='padding-top: 10px;'>
          <b>" . t("Advising Comments") . "</b>
          &nbsp; 
        <a href='javascript: popupPrintWindow(\"" . base_path() . "/comments/popup-display-all-comments\");'  
          class='nounderline'><img src='" . fp_theme_location() . "/images/popup.gif' border='0'>view/print all</a>
        </div><!--ENDCOM$cat_type-->
        </td></tr>";


  $res = db_query("SELECT * FROM advising_comments
            WHERE student_id = '?' 
            AND delete_flag = '0'
            $access_line
            $cat_line
            order by `posted` desc ", $student_id);
  while ($cur = db_fetch_array($res))
  {
    extract($cur, 3, "db");
    $dt = format_date($db_posted);

    if ($first_style == "first")
    {
      $first_style = "color: maroon; font-weight:bold;
          ";
    }




    $on_click = "popupPrintWindow(\"" . base_path() . "/comments/popup-display-comment&id=$db_id\");";
    $rtn .= "<tr $on_mouse_over style='cursor:pointer; $first_style $extra_style'
          onClick='$on_click'>
          <td valign='top' width='165' class='tenpt'
            style='padding-left: 20px;'>
            " . fp_get_faculty_name($db_faculty_id, false) . "
          </td>
          <td valign='top' class='tenpt'>
          $dt$ast
          </td>
        </tr>";

    $is_empty = false;
    $first_style = "";
  }

  if ($is_empty == true) {
    
    $rtn .= "<tr><td colspan='4' class='tenpt'>
            <div style='padding-left: 20px;'>
              " . t("No @cat_type comment history available.", array("@cat_type" => $cat_type)) . "</div></td></tr>";
  }

  $rtn .= "</table>";


  $rtn .= "</td></tr>";

  
  
  
  $rtn .= "</table>";
  
  return $rtn;
}


/**
 * Displays the printable advising summary.
 */
function advise_popup_display_summary() {
  $rtn = "";

  $db = get_global_database_handler();
  
  $advising_session_id = trim($_REQUEST["advising_session_id"]);


  $res = db_query("SELECT * FROM advising_sessions
                     WHERE advising_session_id = '?' ", $advising_session_id);
  if (db_num_rows($res) > 0) {
    $cur = $db->db_fetch_array($res);
    extract($cur, 3, "db");
  }
  $dt = format_date($db_posted, "pretty");

  $term = get_term_description($db_term_id);
  $degree_plan = new DegreePlan();
  $degree_plan->degree_id = $db_degree_id;
  $degree_plan->load_descriptive_data();
  $degree_title = $degree_plan->get_title(true);

  $student = new Student($db_student_id, $db);

  $what_if_message = "";
  if ($db_is_whatif == "1") {
    $official_degree_plan = $student->get_degree_plan(false, true);
    $official_degree_plan->load_descriptive_data();
    $official_degree_title = $official_degree_plan->get_title(true);

    $what_if_message = "<b>" . t("Note:") . "</b>
              " . t("This advising was saved using <b>What If</b> mode
              for the %degree_title major.  According to @initials records,
              the student's official major is %official.", array("%degree_title" => $degree_title, "@initials" => $GLOBALS["fp_system_settings"]["school_initials"], "%official" => $official_degree_title));
  }

  $page_is_mobile = fp_screen_is_mobile();

  $w = ($page_is_mobile) ? "100%" : "500";
  
  $rtn .= "<table width='$w'><td valign='top'>";
  
  if ($page_is_mobile) {

  }
  else {
  
    $rtn .= "
      <table class='tenpt' border='0' width='100%' cellpadding='3' cellspacing='0' style='border-width: 2px; border-style: solid; border-color: black;'>
       <tr height='7'>
        <td> </td>
       </tr>
       <tr>
        <td valign='top' width='15%'>" . t("Student:") . "</td>
        <td valign='top' width='40%'>" . $db->get_student_name($db_student_id, false) . " ($db_student_id)</td>
        <td valign='top' rowspan='3'>
          <table width='100%' cellpadding='0' cellspacing='0'>
        <tr height='20'>
         <td width='100%' valign='bottom'><hr noshade size='1' width='100%' color='black' style='margin: 0px;'></td>
        </tr>
        <tr height='20'>
         <td width='100%' valign='top' align='right'><span style='font-size: 8pt;'>" . t("Student signature") . "</span></td>
        </tr>

        <tr height='20'>
         <td width='100%' valign='bottom'><hr noshade size='1' width='100%' color='black' style='margin: 0px;'></td>
        </tr>
        <tr>
         <td width='100%' valign='bottom' align='right'><span style='font-size: 8pt;'>" . t("Advisor signature") . "</span></td>
        </tr>
       <tr height='7'>
        <td> </td>
       </tr>
  
        
        </table> ";
  }
  
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
  if (!$page_is_mobile) {
    $rtn .= "
       <tr>
        <td valign='top' colspan='2'>
        " . t("Alternate Term:") . " _____________________
        </td>
       </tr>";
  }
       
  $rtn .= "   
       <tr>
        <td valign='top' colspan='4'>
        ";

  if ($degree_title != "")
  {
    $rtn .= t("Major:") . " $degree_title";
  }

  $rtn .= "
       </tr>
       </table>
       <div class='tenpt'><i>" . t("Submitted on") . " $dt.</i></div>
       <div class='tenpt'>$what_if_message</div>
       <br>
    ";

  $rtn .= fp_render_curved_line(t("Advised Courses"));

  $fp = new FlightPath($student,$degree_plan, $db);

  $fp->load_advising_session_from_database("","",false,false,$advising_session_id);

  $rtn .= "<table border='0' cellpadding='3'>
      <tr>
        <td class='tenpt' valign='top' width='25%'>
          <b>" . t("Course") . "</b>
        </td>
        <td class='tenpt' valign='top' width='70%'>
          <b>" . t("Title") . "</b>
        </td>
        <td class='tenpt' valign='top'>
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

  $rtn .= "</table>
      <div align='right' class='tenpt' style='padding-top: 10px; padding-right: 15px;'>
        <b>" . t("Total advised hours:") . " &nbsp; " . $advised_courses_list->count_hours() . "</b>
      </div>
      ";


  if (!$page_is_mobile) {
    $rtn .= "<br>";
    $rtn .= fp_render_curved_line(t("Alternate Courses"));
    $rtn .= "<div class='tenpt'>
        " . t("You may use this space to write in alternate courses 
        the student should enroll in, in the event that an 
        advised course is full or unavailable.") . "
        <br><br>
        ___________________________________________________________________ <br><br>
        ___________________________________________________________________
        </div>  ";
  }

  $rtn .= "</table>";

  fp_set_title($db->get_student_name($db_student_id, FALSE) . " ($db_student_id) $term " . t("Advising Summary"));

  
  return $rtn;
}









