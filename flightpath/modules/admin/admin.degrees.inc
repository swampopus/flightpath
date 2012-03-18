<?php

/*
* These are functions related the data entry specifically for degree plans.
*/

/*
 * This function lists all of our degrees on the screen, for a given year
 */
function admin_display_degrees() {
  $de_catalog_year = admin_get_de_catalog_year();
  $rtn = "";    
    
  fp_add_css(fp_get_module_path("admin") . "/css/admin.css");

  $rtn .= "<h2 class='title'>" . t("Degrees for @year", array("@year" => $de_catalog_year)) . "</h2>";  
  
  $rtn .= "<div class='list-degree-row-excluded'>
        " . t("Majors or Tracks marked as \"exclude\" are shaded.  You may pull up students with these
        majors in FlightPath, but they will not be options in the What If mode.") . "
      </div>
      <br>
      <div class='tenpt' align='center'>
        " . t("Use CTRL-F to find degrees more quickly.") . "
      </div>";
  

  $res = db_query("SELECT * FROM draft_degrees
              WHERE `catalog_year`='?'
             ORDER BY degree_type, major_code, title ", $de_catalog_year);

  while($cur = db_fetch_array($res)) {
    $db_exclude = 0;
    extract($cur, 3, "db");

    if ($db_degree_type == "NA" && strstr($db_major, "|")) {
      $db_degree_type = " -- ";
    }

    $extra_class = "";
    if ($db_exclude == "1") {
      $extra_class = "list-degree-row-excluded";
    }

    // get JUST the major code...
    $temp = split("\|", $db_major_code);
    $just_major = trim($temp[0]);
    $just_conc = trim($temp[1]);
    $outside = "";

    //if ($just_conc != "" && strstr($just_conc, "_"))
    if (strstr($just_conc, "_")) {
      // If the concentration has an underscore, it's actually
      // a track.  Let's get the track title...
      $temp2 = split("_",$just_conc);
      $just_track = trim($temp2[1]);
      // Might need to add the first part BACK onto the major...
      if (trim($temp2[0]) != "")
      {
        $just_major .= "|" . trim($temp2[0]);
      }


      $res2 = db_query("SELECT * FROM draft_degree_tracks
                WHERE catalog_year = '?'
                AND major_code = '?'
                AND track_code = '?' ", $de_catalog_year, $just_major, $just_track);
      if (db_num_rows($res2) > 0) {
        $cur2 = db_fetch_array($res2);

        $db_title = trim($cur2["track_title"]);
        $outside = "----&gt;";
        if (strstr($just_major, "|")) { 
          // both a conc AND a track. Denote it special.
          $outside = ">>" . $outside;
        }
        $db_degree_type = "";
      }
    } 
    else if($just_conc != "") {
      // Meaning, this is a concentration, NOT a track.
      $db_degree_type = "";
      $outside = "&gt;&gt;";
    }



    $rtn .= "<div class='admin-degrees-list-degree'>
             <a name='degree_$db_degree_id'></a>";
    $rtn .= "<div class='list-degree-row $extra_class'>
            $outside
            " . l("$db_degree_type $db_title $db_major_code", "admin/degrees/edit-degree", "de_catalog_year=$de_catalog_year&major_code=$db_major_code", array("class" => "degree-$db_degree_class")) . "
            </div>
            </div>";



  }    
  
  return $rtn;
    
}


/**
 * This screen displays the form which allows the user to actually
 * edit a degree.
 */
function admin_display_edit_degree() {
  $de_catalog_year = admin_get_de_catalog_year();
  $rtn = "";
  
  $major_code = $_REQUEST["major_code"];

  $button_msg = $msg;
  if ($bool_button_msg == false) {$button_msg = "";}
  
  $db = get_global_database_handler();

  $degree_id = intval($db->get_degree_id($major_code, $de_catalog_year, true));
  // The intval says, if it's false, make it = 0.  otherwise keep the number
  // that is returned.
  $degree = new DegreePlan($degree_id, null, false, false, true);
  $degree->load_descriptive_data();


  if (user_has_permission("can_view_advanced")) {
    $rtn .= " <span class='tenpt' style='background-color: yellow; margin-left: 20px;'>
          advanced: degreeID = $degree_id.
          </span>";
  }

  $rtn .= "<form id='mainform' action='admin.php' method='POST'>";

  $rtn .= "<div style='font-size: 16pt; font-weight:bold; padding-top: 20px;'>$degree->degree_type $degree->title<br>$major_code ($de_catalog_year)</div>";
  $rtn .= "
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
    $rtn .= "<b>Edit track information:</b>
      <blockquote style='margin-top: 0px; margin-bottom: 0px;'>
    <font size='2'>Track title: <input type='text' name='track_title' value='$degree->track_title' size='60' maxlength='100'></font><br>
    <font size='2'>Track description: <a href='javascript: popupAlertHelp(\"edit_formatting\");'>(Help - Formatting)</a>
            <a href='javascript: popupAlertHelp(\"track_description\");'>(Help - Track Descriptions)</a>
    </font><br>
    <textarea name='track_description' cols='60' rows='3'>" . convert_html_to_bbcode($degree->track_description) . "</textarea>
    </blockquote>
    ";
  }
  $rtn .= "<div class='tenpt' align='center'>(Scroll to the bottom of the page for more options)</div>";

  $rtn .= "
      <input type='hidden' name='performAction' value='perform_edit_specific_degree'>
      <input type='hidden' name='major_code' value='$major_code'> ";

  $rtn .= "
  
      <input type='hidden' name='de_catalog_year' value='$de_catalog_year'>      
      
      ";
  



  $degree->list_semesters->reset_counter();
  while ($degree->list_semesters->has_more()) {
    $semester = $degree->list_semesters->get_next();
    if ($semester->semester_num < 0) {
      continue;
    }
    $sem_default_title = admin_degrees_get_semester_name($semester->semester_num);
    if ($semester->title == $sem_default_title) {
      $semester->title = "";
    }

    $rtn .= "<div class='elevenpt' style='padding-bottom: 30px;'>
          <b>Block number: " . ($semester->semester_num +1) . "</b>
          &nbsp; &nbsp; &nbsp; &nbsp;
          Default title: $sem_default_title
          &nbsp; &nbsp;
          Override: <input type='text' name='semester_title_$semester->semester_num' value='$semester->title' size='20'>
          <a href='javascript: popupAlertHelp(\"semester_title\");'>?</a>
          <table border='1' width='100%'>
          ";
    // Get the courses.
    $rtn .= "<tr><td valign='top'>
          <textarea name='courses_$semester->semester_num' rows='10' cols='20'>";
    $semester->list_courses->sort_alphabetical_order();
    $semester->list_courses->reset_counter();
    while($semester->list_courses->has_more()) {
      $course = $semester->list_courses->get_next();
      $course->load_descriptive_data();
      $rtn .= "$course->subject_id $course->course_num $course->requirement_type";
      if ($course->min_grade != "D" && $course->min_grade != "") {
        $rtn .= " ($course->min_grade)";
      }
      $rtn .= "\n";
    }
    $rtn .= "</textarea>
    <div class='tenpt'>(<a href='javascript: popupAlertHelp(\"degree_entry\");'>Help - entering requirements, min grades, and repeats</a>)</div>
    </td>";

    // Get the groups...
    $rtn .= "<td valign='top' class='tenpt' width='100%'>
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
    while($semester->list_groups->has_more()) {
      $group = $semester->list_groups->get_next();
      $group->load_descriptive_data();
      $rtn .= "<tr><td valign='middle'>
      
            <a href='javascript:delGroup(\"$group->group_id\",\"$semester->semester_num\");'><img src='" . fp_theme_location() . "/images/delete.png' border='0'></a>
                  
            <td valign='top' class='tenpt'>
            $group->title<br><i>$group->group_name</i></td>
            <td valign='top' class='tenpt'>$group->hours_required</td>
            <td valign='top' class='tenpt'>$group->min_grade</td>
            <td valign='top' class='tenpt'>$group->requirement_type
            <input type='hidden' name='group_" . $group->group_id . "_" . rand(1,999999) . "' value='$group->group_id" . "_$semester->semester_num" . "_$group->hours_required" . "_$group->requirement_type" . "_$group->min_grade'>
            </td>"; 
    }
    $rtn .= "</table>
        <div style='margin-top: 10px; margin-left: 20px;'>
          <a href='javascript:popupWindow(\"admin.php?performAction=popup_add_group&semester_num=$semester->semester_num&de_catalog_year=$de_catalog_year\");'>Add an elective group</a></div>
    </td>";


    $rtn .= "</table><br>
        
    " . fp_render_button(t("Save for @year", array("@year" => $de_catalog_year)), "submitForm();") . "
    
    </div>
    ";
    $s_count = $semester->semester_num+1;
  }

  // Always add an additional 4 semesters to the bottom.
  for ($t = 0; $t < 4; $t++) {
    $sem = $t + $s_count;
    if ($sem > 99) {
      // Max number of semesters.  More or less arbitrarily set number.
      $rtn .= "<br>Maximum number of semesters created.<br>";
      break;
    }

    $rtn .= "<div class='elevenpt' style='padding-bottom: 30px;'>
          <b>Block number: " . ($sem+1) . "</b>
          &nbsp; &nbsp; &nbsp; &nbsp;
          Default title: " . admin_degrees_get_semester_name($sem) . "
          &nbsp; &nbsp;
          Override: <input type='text' name='semester_title_$sem' value='' size='20'>
          <a href='javascript: popupAlertHelp(\"semester_title\");'>?</a>
          
          <table border='1' width='100%'>
          ";

    $rtn .= "<tr><td valign='top'>
          <textarea name='courses_$sem' rows='10' cols='20'>";
    $rtn .= "</textarea></td>";

    // the groups...
    $rtn .= "<td valign='top' class='tenpt' width='100%'>
          <table width='100%' border='0' cellspacing='5'>
          <tr>
            <td valign='top' class='tenpt' width='1'>&nbsp;</td>
            <td valign='top' class='tenpt'>Group</td>
            <td valign='top' class='tenpt'>hrs</td>
            <td valign='top' class='tenpt'>grd</td>
            <td valign='top' class='tenpt'>type</td>
            </tr>";
    $rtn .= "</table>
        <div style='margin-top: 10px; margin-left: 20px;'>
          <a href='javascript:popupWindow(\"admin.php?performAction=popup_add_group&semester_num=$sem&de_catalog_year=$de_catalog_year\");'>Add an elective group</a></div>
    </td>";


    $rtn .= "</table><br>
    
    " . fp_render_button(t("Save for @year", array("@year" => $de_catalog_year)), "submitForm();") . "
    
    </div>";

  }
  $rtn .= "<div class='elevenpt'>If you need more semester boxes, simply save this page, and additional blank
      boxes will appear below.</div>
      
      <br><br>
      <div class='elevenpt'><b>More Options:</b><br>
      Enter a public note for this degree: 
       <a href='javascript: popupAlertHelp(\"public_note\");'>(Help - Public Note)</a>
       <a href='javascript: popupAlertHelp(\"edit_formatting\");'>(Help - Formatting)</a>
      <br>
      <textarea name='public_note' rows='4' cols='80'>$degree->public_note</textarea>
      
      
      </div>
      
      
      
      "; 




  $rtn .= "</form>";

  $rtn .= "        <div align='right'>
          Delete this degree? <input type='button' value='X'
                  onClick='deleteDegree(\"$degree_id\");'>
        </div>      
        ";


  //$pC .= get_j_s();


  if ($_REQUEST["serialize"] != "")
  {
    print "<br><textarea rows=20 cols=80>" . serialize($degree) . "</textarea>";
  }  
  
  
  return $rtn;
}


/**
 * Convienence function to get semester's default regular name.
 */
function admin_degrees_get_semester_name($semester_num) {    
  
  $ar = array(t("Freshman Year"), t("Sophomore Year"), t("Junior Year"), t("Senior Year"));
  $s = $ar[$semester_num];
  if ($s == "")
  {
    $s = t("Year") . " " . ($semester_num + 1);
  }
  return $s;
}

