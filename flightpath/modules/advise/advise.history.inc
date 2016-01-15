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
        
        $click_links .= "<li>
                <a href='javascript: popupPrintWindow(\"" . $turl . "\");'>
                <img src='" . fp_theme_location() . "/images/popup.gif' border='0'>
                $term_name</a>";

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
  $render_left = array();  // creating a new render array.
  $render_left["#id"] = "advise_history_left_column";
  
  //$rtn .= "<table border='0' cellspacing='0'>";
  $old_session_d_t = 0;
  $a_count = 0;
  $c = 0;
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
    $html = "";

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
      /*
      $rtn .= "<tr><td colspan='2' class='tenpt'>
              $advised_by
            </td>
          </tr>";
      */
      
      $html .= "<div class='tenpt'>$advised_by</div>";      
      
      
            
          
      $a_count++;

    }
    $is_empty = false;

    if ($a_count > 1) {
      $first_style = "";
    }

    $turl = fp_url("advise/popup-display-summary", "advising_session_id=$db_advising_session_id");

    $on_click = "popupPrintWindow(\"" . $turl . "\");";

    $term = get_term_description($db_term_id);

    /*
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
    */
    
    
    $html .= "<div $on_mouse_over style='cursor: pointer; $first_style' onClick='$on_click'>
                <div class='tenpt' style='display: inline-block; padding-left:20px; width:165px;'>$term</div>
                <div class='tenpt' style='display: inline-block;'>$dt</div>
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
          "label" => t("View complete advising history"),
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
    //$rtn .= "<div class='tenpt'>No advising history available.</div>";
    
    $render_left["markup_no_adv_history"] = array(
      "value" => t("No advising history available."),
    );
  }

  // Okay, we can render the left-hand side now.
  $rtn .= fp_render_content($render_left);




  //----------------------------------------------------------------------------------------
  //------------------------------ COMMENT HISTORY -----------------------------------------
  //----------------------------------------------------------------------------------------
  $rtn .= "</td><td width='50%' valign='top'>";
  $rtn .= fp_render_curved_line(t("Comment History"));
  //$rtn .= "<table border='0' cellspacing='0'>";
  
  // Prepare a render array for the right-hand side.
  $render_right = array();
  $render_right["#id"] = "advise_history_right_column";
  
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
  
  /*
  $rtn .= "<tr><td colspan='3' class='tenpt'>
        <!--STARTCOM--><div style='padding-top: 10px;'>
          <b>" . t("Advising Comments") . "</b>
          &nbsp; 
        <a href='javascript: popupPrintWindow(\"" . $turl . "\");'  
          class='nounderline'><img src='" . fp_theme_location() . "/images/popup.gif' border='0'>view/print all</a>
        </div><!--ENDCOM-->
        </td></tr>";

   */
   
  $html .= "<div colspan='3' class='tenpt'>
        <!--STARTCOM--><div style='padding-top: 10px;'>
          <b>" . t("Advising Comments") . "</b>
          &nbsp; 
        <a href='javascript: popupPrintWindow(\"" . $turl . "\");'  
          class='nounderline'><img src='" . fp_theme_location() . "/images/popup.gif' border='0'>view/print all</a>
        </div><!--ENDCOM-->
        </div>";


// Use the function comments_get_comments instead of a direct query.

  $res = comments_get_comments($student_id, FALSE, $access_types);

/*  
  
  $res = db_query("SELECT * FROM advising_comments
            WHERE student_id = '?' 
            AND delete_flag = '0'
            $access_line
            $cat_line
            order by `posted` desc ", $student_id);
            */
  //while ($cur = db_fetch_array($res))
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

    $on_click = "popupPrintWindow(\"" . $turl . "\");";
    /*
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

     * 
     */
    $html .=  "<div $on_mouse_over style='cursor:pointer; $first_style $extra_style'
          onClick='$on_click'>
          <div class='tenpt' style='padding-left: 20px; width:165px; display:inline-block;'>
            " . fp_get_faculty_name($db_faculty_id, false) . "
          </div>
          <div class='tenpt' style='display: inline-block;'>
              $dt$ast
          </div>
        </div>";
      
    $is_empty = false;
    $first_style = "";
  }

  if ($is_empty == true) {
    /*
    $rtn .= "<tr><td colspan='4' class='tenpt'>
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
    $cur = db_fetch_array($res);
    extract($cur, 3, "db");
  }
  $dt = format_date($db_posted, "pretty");

  $term = get_term_description($db_term_id);
  
  $degree_title_line = "";
  $ds = "";
  $degree_plan = new DegreePlan();
  if ($db_degree_id != _DegreePlan::DEGREE_ID_FOR_COMBINED_DEGREE) {
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

  if ($degree_title_line != "")
  {
    $rtn .= t("Degree$ds:") . " $degree_title_line";
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









