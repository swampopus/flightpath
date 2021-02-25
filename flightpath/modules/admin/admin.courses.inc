<?php

/* Functions related to working with Course data in FP */


/**
 * This function displays all of our courses for us to edit.
 */
function admin_display_courses() {
  $rtn = "";


  // Do this using $render array, so it can be altered
  // by hook_content_alter
  $render = array();
  $render['#id'] = 'admin_display_courses';
  



  $de_catalog_year = admin_get_de_catalog_year();

  fp_add_css(fp_get_module_path("admin") . "/css/admin.css");
  
  $cc = 1;

  $show_hidden = trim(@$_GET["show_hidden"]);
  if ($show_hidden != "") {
    $_SESSION["dehidden"] = $show_hidden;
  } 
  else {
    $show_hidden = @$_SESSION["dehidden"];
  }
 
  // Get the "upper range" (UR) and
  // lower range (LR) of the courses in question...
  $ur = trim(@$_GET["ur"]);
  $lr = trim(@$_GET["lr"]);
  if ($ur == "") { 
    // meaning, no range was set.  Use A - AZZZZZ, so, all of the A's.
    $ur = @$_SESSION["dec_ur"];
    $lr = @$_SESSION["dec_lr"];
    if ($ur == "") { 
      // if still blank, assign it..
      $ur = "A";
      $lr = "AZZZZ";
    }

  }
  $_SESSION["dec_ur"] = $ur;
  $_SESSION["dec_lr"] = $lr;


  fp_set_title(t("Courses for @year", array("@year" => $de_catalog_year)));  


  $html = "";

  $letter_ranges = array(
    "A" => array("A", "AZZZZ"),
    "B" => array("B", "BZZZZ"),
    "C-CN" => array("C", "CNZZZ"),
    "CO-CZ" => array("CO", "CZZZZ"),
    "D" => array("D", "DZZZZ"),
    "E" => array("E", "EZZZZ"),
    "F" => array("F", "FZZZZ"),
    "G" => array("G", "GZZZZ"),
    "H" => array("H", "HZZZZ"),
    "I-L" => array("I", "LZZZZ"),
    "M-MR" => array("M", "MRZZZ"),
    "MS-MZ" => array("MS", "MZZZZ"),
    "N-O" => array("N", "OZZZZ"),
    "P" => array("P", "PZZZZ"),
    "Q-R" => array("Q", "RZZZZ"),
    "S-SO" => array("S", "SOZZZ"),
    "SP-SZ" => array("SP", "SZZZZ"),
    "T-Z" => array("T", "ZZZZZ"),
  );


  $html .= "<div class='admin-courses-select-letter-bar'>
          ";
  foreach($letter_ranges as $disp => $vals) {
    $html .= l($disp, "admin/courses", "de_catalog_year=$de_catalog_year&ur=" . $vals[0] . "&lr=" . $vals[1], array("class" => "admin-courses-letter-link")) . " &nbsp; ";
  }

  $html .= "</div>                       
        <div class='tenpt'>             
        <div style='padding: 5px;'>          
        " . l(t("Add a course for @year", array("@year" => $de_catalog_year)), "admin/courses/edit-course", "course_id=new&de_catalog_year=$de_catalog_year") . "
        </div>          
                       Legend:  <br>&nbsp; &nbsp;[e] = Course has at least one add'l excluded name.  
                            &nbsp; &nbsp;[v] = Course has at least one add'l valid name.
                            
         </div>

         ";

  $exclude_line = "and exclude != 1";
  if ($show_hidden == "yes") {
    $html .= "<div class='tenpt'><b>" . t("Showing excluded courses.") . " ";
    $html .= l(t("Hide?"), "admin/courses", "de_catalog_year=$de_catalog_year&show_hidden=no");
    $html .= "</b></div>";
    $exclude_line = "";

  }  
  else {
    $html .= "<div class='tenpt'><b>" . t("Hiding excluded courses.") . " ";
    $html .= l(t("Show?"), "admin/courses", "de_catalog_year=$de_catalog_year&show_hidden=yes");
    $html .= "</b></div>";   
  }

  $html .= "<hr><br>";
  
  

  $render['upper_links'] = array(
    'value' => $html,
  );
  
    
  
  $render['courses_table_top'] = array(
    'value' => "<table border='0' cellpadding='3' cellspacing='0' class='disp-courses-table'>",
  );  
  

  $q = "SELECT * FROM draft_courses
                        WHERE 
                          catalog_year = '?'
                          AND subject_id BETWEEN '?' AND '?'
                          AND delete_flag = '0'
                          $exclude_line
                        ORDER BY subject_id, course_num";
  $result = db_query($q, $de_catalog_year, $ur, $lr);
  while ($cur = db_fetch_array($result)) {
    extract($cur, 3, "db");

    $ex_names = "";
    $val_names = "";
    // Check to see if this course has more than one name...
    // removed AND `catalog_year`='$de_catalog_year' from query,
    // because we don't care what other cat year it came from.
    $res2 = db_query("SELECT * FROM draft_courses
                  WHERE course_id = '?'
                    ", $db_course_id);
    while ($cur2 = db_fetch_array($res2)) {
      if ($cur2["subject_id"] == $db_subject_id && $cur2["course_num"] == $db_course_num) {
        continue;
      }

      if ($cur2["exclude"] == "1") {
        $ex_names = "[e]";
      } 
      else {
        $val_names = "[v]";
      }

    }


    $spanstyle = "";

    if ($db_exclude == "1") {
      $spanstyle = "background-color: #ddd;";
    }

    $temp_course = new Course();
    $db_title = $temp_course->fix_title($db_title);

    $db_min_hours = $db_min_hours*1;
    $db_max_hours = $db_max_hours*1;
    $db_repeat_hours = $db_repeat_hours*1;
    
    $hrs = $db_min_hours;
    if (trim($db_min_hours) != trim($db_max_hours)) {
      $hrs .= " - $db_max_hours";
    }

    $hrs .= " hrs.";

    $rep_hours = "";
    if ($db_repeat_hours > $db_min_hours) {
      $rep_hours = " rep to $db_repeat_hours hrs.";
    }

    // remove special chars from subject_id...
    $display_subject_id = $db_subject_id;
    $db_subject_id = str_replace("&","_A_",$db_subject_id);

    $render['course_row_' . $db_course_id] = array(
      'value' => "
                    <tr style='$spanstyle'>
                      <td valign='top' width='90%'>
                          <a name='course_$db_course_id'></a>
                          <div style='$spanstyle padding:3px;'>
    
           " . l("$display_subject_id $db_course_num - $db_title", "admin/courses/edit-course", "course_id=$db_course_id&subject_id=$db_subject_id&course_num=$db_course_num&de_catalog_year=$de_catalog_year") . " - $hrs$rep_hours</div>

                  </td>
                  <td valign='top' width='5%'>
                      $ex_names
                  </td>
                  
                  <td valign='top' width='5%'>
                        $val_names
                  </td>
                </tr>",     

           'data' => array(
                    'course_id' => $db_course_id,
                    'catalog_year' => $de_catalog_year,
                    'db_row' => $cur,
                    'subject_id' => $db_subject_id,
                    'course_num' => $db_course_num,
                  ),                
                
    );
  




  } // while

  
  $render['courses_table_bottom'] = array(
    'value' => "</table>",
  );
  
  
  
  
  $rtn .= fp_render_content($render);

  
  return $rtn;
  
}


/**
 * This form lets the user edit details about a course.
 */
function admin_edit_course_form() {
  $form = array();
  
  $de_catalog_year = admin_get_de_catalog_year();
  $course_id = @$_REQUEST["course_id"];
  $subject_id = @$_REQUEST["subject_id"];
  $subject_id = str_replace("_A_","&",$subject_id);  
  $course_num = @$_REQUEST["course_num"];
  
  $m = 0;
  
  fp_set_title(t("Edit Course @course", array("@course" => "$subject_id $course_num ($de_catalog_year)")));
  fp_add_css(fp_get_module_path("admin") . "/css/admin.css");
  fp_add_js(fp_get_module_path("admin") . "/js/admin.js");
       
  $form["de_catalog_year"] = array(
    "type" => "hidden",
    "value" => $de_catalog_year,
  );
  
  $form["course_id"] = array(
    "type" => "hidden",
    "value" => $course_id,
  );
  
  $form["subject_id"] = array(
    "type" => "hidden",
    "value" => $subject_id,
  );
        
  $form["course_num"] = array(
    "type" => "hidden",
    "value" => $course_num,
  );
                
  $form["perform_action2"] = array(
    "type" => "hidden",
    "value" => "",
  );
  
  // Begin the form...

  // TODO:  implement this!
  if (@$_SESSION["de_advanced_mode"] == true)
  {
    $pC .= " <span class='tenpt' style='background-color: yellow; margin-left: 20px;'>
          adv: course_id = $course_id. Used by:
          <a href='javascript: popupWindow(\"admin.php?performAction=popup_degrees_using_course&course_id=$course_id\")'>[degrees]</a>
          <a href='javascript: popupWindow(\"admin.php?performAction=popup_groups_using_course&course_id=$course_id\")'>[groups]</a>
          <a href='javascript: popupWindow(\"admin.php?performAction=popup_students_using_course&course_id=$course_id\")'>[students]</a>
          </span>";
  }

  $course = new Course($course_id,false,null,false,$de_catalog_year, true);
  $course->catalog_year = $de_catalog_year;  // Since it may be 1900, force it!
  $course->load_descriptive_data(false, true, false, true, true);

  $course->catalog_year = $de_catalog_year;  // Since it may be 1900, force it!
  $all_names = $course->get_all_names(true);
  $warn_eqv = "";
  if (strstr($all_names, ",")) {
    $warn_eqv = "yes";
  }
  
  // Correct ghosthours, if they exist.
  if ($course->bool_ghost_hour) {
    $course->max_hours = 0;
  }
  if ($course->bool_ghost_min_hour) {
    $course->min_hours = 0;
  }
  
  $form["course_names"] = array(
    "type" => "textfield",
    "label" => t("Course names(s):"),
    "required" => TRUE,
    "value" => $all_names,
    "popup_description" => t("These are the possible display names for this course. Typically,
                              there will be only one name. Ex: ACCT 110. You must place a space
                              between the subject ID and course number.
                              
                              If this course is known by another name (has an eqv course)
                              then enter it as well using a comma.
                              Ex: ACCT 110, MATH 110A
                              
                              Add the word 'exclude' to prevent it from showing in course
                              selection windows in FlightPath.
                              Ex: ACCT 110, MATH 110A exclude
                              
                              IMPORTANT: Course names are updated for ALL YEARS of a course."),
    "weight" => 10,
  );  
  
  $form["title"] = array(
    "type" => "textfield",
    "label" => t("Title:"), 
    "value" => $course->title,
    "popup_description" => t("The title of the course displayed in FlightPath."),
    "weight" => 20,
  );  
  
  $form["min_hours"] = array(
    "type" => "textfield",
    "label" => t("Min hours:"),
    "size" => 10,
    "value" => $course->min_hours,
    "popup_description" => t("The min and max hours for a course will usually be the same
                              number. Ex: 3.  If they differ, it means the course has
                              variable hours.  
                              
                              For example, if you have a course
                              worth 1-6 hours, enter 1 for min, and 6 for max."),
    "weight" => 30,
  );  
   

  $form["max_hours"] = array(
    "type" => "textfield",
    "label" => t("Max hours:"),
    "size" => 10,
    "value" => $course->max_hours,
    "popup_description" => t("The min and max hours for a course will usually be the same
                              number. Ex: 3.  If they differ, it means the course has
                              variable hours.  
                              
                              For example, if you have a course
                              worth 1-6 hours, enter 1 for min, and 6 for max."),
    "weight" => 40,
  );  

  $form["repeat_hours"] = array(
    "type" => "textfield",
    "label" => t("Repeat hours:"),
    "size" => 10,
    "value" => $course->repeat_hours,
    "popup_description" => t("This is the number of hours a course may be repeated for credit.
                              For example, if a course is worth 3 hours, but may be repeated
                              for up to 9 hours of credit, enter a 9 in this box.
                              
                              If a course CANNOT be repeated for credit, this would
                              be the same number as the min and max, or simply blank.
                              
                              If you are unsure what to enter, leave it blank."),
    "weight" => 50,
  );  
   
    
  $form["description"] = array(
    "type" => "textarea",
    "label" => t("Description"),
    "value" => $course->description,
    "rows" => 4,
    "cols" => 80,
    "weight" => 60,    
  );  
     
  $form["all_years"] = array(
    "type" => "checkboxes",
    "label" => t("Update for all years?"),
    "options" => array("yes" => t("Update title, description, and hour info for all years of this course.")),    
    "popup_description" => t("Check this box and press Submit to update title, description, and hour information
                              for all available years of this course.  Note that course name is automatically
                              updated for all years, regardless if you check this box or not."),
    "weight" => 70,
  );   
     
  
  $form["data_entry_comment"] = array(
    "type" => "textarea",
    "label" => t("Optional Comment: (only seen by other FlightPath administrators)"),
    "rows" => 3,
    "cols" => 80,
    "value" => $course->data_entry_comment,
    "weight" => 80,
  );
   
     
  
  $form["submit"] = array(
    "type" => "submit",
    "value" => t("Save for @year", array("@year" => $de_catalog_year)),
    "prefix" => "<hr>",
    "weight" => 90,
  );
  
  
  // Only show delete option based on permission  
  if (user_has_permission("can_delete_data_entry")) {
  
    $form["mark" . $m++] = array(
      "type" => "markup",
      "value" => "<div align='right' class='courses-delete-course-wrapper'>
                        " . t("Delete this course?") . " <input type='button' value='X'
                        onClick='adminDeleteCourse(\"$course_id\",\"$de_catalog_year\",\"$warn_eqv\");'>
                      </div>",
      "weight" => 100, 
    );
    
  }
  
  
  
  return $form;
}



function admin_edit_course_form_submit(&$form, &$form_submit) {
  $values = $form_submit["values"];
  

  $de_catalog_year = $values["de_catalog_year"];
  $course_id = trim($values["course_id"]);
  $course_names = trim($values["course_names"]);

  $db = get_global_database_handler();
  
  if ($course_names == "") {
    $course_names = $values["subject_id"] . " " . $values["course_num"];
  }

  $title = trim($values["title"]);
  $min_hours = floatval(trim($values["min_hours"]));
  $max_hours = floatval(trim($values["max_hours"]));
  $repeat_hours = floatval(trim($values["repeat_hours"]));
  //$exclude = trim($_POST["exclude"]);
  $description = trim($values["description"]);
  $data_entry_comment = trim($values["data_entry_comment"]);

  // Since we are making a change to the draft table(s), let's add a row
  // to draft instructions.
  $db->add_draft_instruction("-");



  // Unlike the degrees and the groups, course_ids are not
  // unique.  Only a course_id + catalog_year pair are unique.  This
  // is so we can handle equivalent courses more gracefully.

  // So, the first thing we need to do is delete *all* courses with the
  // course_id and catalog_year listed above.  For most courses, this will
  // only be one row.  For eqv courses, this will delete more than one row.
  if ($course_id != "new") {
    // Don't delete!  Temporarily transfer to a temporary course_id.
    // Will possibly delete later.
    
    $res = db_query("UPDATE draft_courses
          SET course_id = '-12345'
          WHERE course_id = '?'
        AND catalog_year = '?' ", $course_id, $de_catalog_year);
  }


  if ($values["perform_action2"] == "delete_course" && user_has_permission("can_delete_data_entry")) {
    // That's it.  All we wanted to do was delete the course.
    $query = "DELETE FROM draft_courses
          WHERE course_id = '-12345'
          ";
    $res = db_query($query);
    
    fp_add_message(t("Course %course successfully deleted for %year", array("%course" => $course_names, "%year" => $de_catalog_year)));
    // Redirect us to the list of courses.
    $form["#redirect"] = array(
      "path" => "admin/courses",
      "query" => "de_catalog_year=$de_catalog_year",
    );
    return;
    
  }

  // If the $course_id == new then create a new one.
  if ($course_id == "new") {
    $course_id = $db->request_new_course_id();

    $values["course_id"] = $course_id;
    // change the $form redirect so we get sent to the new course on save. 
    $form["#redirect"] = array(
      "path" => "admin/courses/edit-course",
      "query" => "de_catalog_year=$de_catalog_year&course_id=$course_id",
    );
    
    $form_submit['values']['course_id'] = $course_id;  // set the new course id back into the array, for other modules to use.
    
  }




  // Now, we will split the courseNames on commas, and for each
  // token, we will insert a row into the database.
  $courses = explode(",", $course_names);
  foreach($courses as $course) {
    $course = str_replace("  ", " ", $course);
    $course = str_replace("  ", " ", $course);
    $course = str_replace("  ", " ", $course);
    $course = trim($course);
    if ($course == "") { continue; }

    $temp = explode(" ", $course);
    $subject_id = trim($temp[0]);
    $course_num = trim($temp[1]);

    ////////////
    ///  Error conditions...
    if (strtolower($course_num) == "exclude" || $course_num == "") {
      form_error("course_names", t("It appears you specified an excluded course
            without a course number.  You entered %course.
            Notice there is no course number. Please re-enter.", array("%course" => "$subject_id $course_num")));            
      continue;
    }

    ////////////////

    $exclude = 0;
    // Do we exclude?
    if (strtolower(trim($temp[2])) == "exclude") {
      $exclude = 1;
      // Set ALL courses with this subject_id and course_num to exclude!
      $res = db_query("UPDATE draft_courses
                SET exclude = '1'
                WHERE subject_id = '?'
                AND course_num = '?' 
                ", $subject_id, $course_num);


    } 
    else {
      // Aet all courses with this subject_id and course_num to NOT exclude!
      $res = db_query("UPDATE draft_courses
                SET exclude = '0'
                WHERE subject_id = '?'
                AND course_num = '?' 
                ", $subject_id, $course_num);

    }

    // Did the user specify a course which already exists?  If so,
    // mark that course's ID as -12345...
    $res = db_query("UPDATE draft_courses
                SET course_id = '-12345'
                WHERE subject_id = '?'
                AND course_num = '?' 
                AND catalog_year = '?' ", $subject_id, $course_num, $de_catalog_year);



    // We now have enough information to make an insertion into
    // the table.
    $query = "INSERT INTO draft_courses
          (`course_id`,`subject_id`,`course_num`,`catalog_year`,
            `title`,`description`,`min_hours`,`max_hours`,`repeat_hours`,
            `exclude`,`data_entry_comment`)
            values ('?','?','?','?','?','?','?','?','?','?','?') ";
    //debug_c_t($query);
    $res = db_query($query, $course_id,$subject_id,$course_num,$de_catalog_year,
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
    $res2 = db_query("SELECT * FROM draft_courses WHERE
                subject_id = '?'
                AND course_num = '?'
                AND course_id != '?' 
                AND course_id != '-12345' ", $subject_id, $course_num, $course_id);
    while ($cur2 = db_fetch_array($res2)) {
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
  $res = db_query("SELECT * FROM draft_courses
              WHERE course_id = '-12345' ");
  while ($cur = db_fetch_array($res)) {
    $found_s_i = $cur["subject_id"];
    $found_c_n = $cur["course_num"];
    $db2 = new DatabaseHandler();
    $res2 = $db2->db_query("SELECT * FROM draft_courses
              WHERE course_id = '?'
              AND subject_id = '?'
              AND course_num = '?' 
              AND catalog_year = '?' ", $course_id, $found_s_i, $found_c_n, $de_catalog_year);
    if ($db2->db_num_rows($res2) == 0) {
      // Meaning, this course name is not listed with the course_id,
      // so this is a broken eqv.
      // We should make a new course_id and all that for this course,
      // for all available years.

      $new_course_id = $db2->request_new_course_id();
      $db3 = new DatabaseHandler();
      $res3 = $db3->db_query("UPDATE draft_courses
                  SET course_id = '?'
                  WHERE subject_id = '?'
                  AND course_num = '?' ", $new_course_id, $found_s_i, $found_c_n);
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
  if (is_array($values["all_years"]) && $values["all_years"]["yes"] == "yes") {
    $res = db_query("UPDATE draft_courses
                  SET title = '?',
                    description = '?',
                    min_hours = '?',
                    max_hours = '?',
                    repeat_hours = '?'
                  WHERE course_id = '?' ", $title, $description, $min_hours, $max_hours, $repeat_hours, $course_id);
                  
  }



  // Clean up.  Delete the temporary course_id...
  $res = db_query("DELETE FROM draft_courses WHERE course_id = '-12345'  ");

  fp_add_message("Course updated successfully.");  
  
  
}




