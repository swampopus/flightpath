<?php

/**
 * @file
 * Keep track of functions dealing specifically with student user management
*/


/**
 * This is the form which we will use to manage a student's courses they've taken.
 */
function user_student_edit_student_courses_form() {
    
  fp_add_js(fp_get_module_path("user") . "/js/user.js");

  $form = array();
  $student_cwid = $_REQUEST["student_cwid"];  // now getting it from argument
  $user_id = db_get_user_id_from_cwid($student_cwid, "student");



  // Figure out what the page's sub-tabs should be, and set them.
  $tab_array = array();
  $tab_array[0]["title"] = t("Edit Student");
  $tab_array[0]["active"] = FALSE;
  $tab_array[0]["on_click"] = "window.location=\"" . fp_url("admin/users/edit-student-user", "student_cwid=$student_cwid&de_catalog_year=$de_catalog_year") . "\"";
      
  $tab_array[1]["title"] = t("Edit Student Courses");
  $tab_array[1]["active"] = TRUE;
  $tab_array[1]["on_click"] = "window.location=\"" . fp_url("admin/users/edit-student-user/courses", "student_cwid=$student_cwid&de_catalog_year=$de_catalog_year") . "\"";
      
  fp_set_page_sub_tabs($tab_array);




  
  if ($student_cwid != "new") {  
    $name = fp_get_student_name($student_cwid);  
    fp_set_title(t("Edit Student Courses @name (@id)", array("@name" => $name, "@id" => $student_cwid)));
  }
  else {
    // A new student!  We can't create a new student until a CWID is assigned.
    fp_set_title(t("Create New Student User"));
    $form["mark_sorry"] = array(
      "type" => "markup",
      "value" => "<p>" . t("Sorry, but you cannot add student courses until the student
                      has been fully created (and given a CWID).  Use the Edit Student button above
                      to return to that screen.") . "</p>",
    );
    return $form;
  }

  $form["user_id"] = array(
    "type" => "hidden",
    "value" => $user_id,
  );

  $form["perform_action2"] = array(
    "type" => "hidden",
    "value" => "",
  );

  $form["student_cwid"] = array(
    "type" => "hidden",
    "value" => $student_cwid,
  );
  
  
  
  // Okay, let's create a table in which we will place the student's courses.
  $form["mark" . $m++] = array(
    "value" => "<p>
                  " . ("Enter the student's local (non-transfer) courses they have enrolled in at your institution.") . "
                  <ul>
                    <li>" . t("Course:  Enter the full course subject ID and num, separated by a space.  Ex:  ACCT 101") . "</li>
                    <li>" . t("Term:  Enter the term code that this student enrolled in this course. Ex:  201540") . "</li>
                    <li>" . t("Grade: Enter the grade they earned. Ex: B") . "</li>
                    <li>" . t("Hrs: Enter the hours awarded.  Ex: 3") . "</li>
                    <li>" . t("Lvl: (optional), enter the level code for this course. Ex: UG") . "</li>
                  </ul>
                  " . t("<b>Note:</b> If you have any routines which might overwrite this data (in the student_courses table), then this
                               data may be ignored or deleted!") . "
                </p>",
  );
  
  $form["warn_me"] = array(
    "type" => "checkbox",
    "label" => t("Warn me if I enter a course which doesn't exist in draft_courses (good for catching typos)"),    
    "value" => "yes",
  );
  
  
  $form["mark" . $m++] = array(
    "value" => "
                <table border='0' width='100%' cellpadding='3' cellspacing='0'>
                  <tr>
                    <th>" . t("Course") . "</th>
                    <th>" . t("Term") . "</th>
                    <th>" . t("Grade") . "</th>
                    <th>" . t("Hrs") . "</th>
                    <th>" . t("Lvl") . "</th>
                  </tr>",
  );
  
  
  // Let's get an array of what courses the student has already taken (if any)
  $courses = array();
  $res = db_query("SELECT * FROM student_courses 
                   WHERE student_id = '?'
                   ORDER BY subject_id, course_num", $student_cwid);
  while ($cur = db_fetch_array($res)) {
    $courses[] = array(
      "course" => $cur["subject_id"] . " " . $cur["course_num"],
      "term" => $cur["term_id"],
      "grade" => $cur["grade"],
      "hrs" => $cur["hours_awarded"] * 1, // will trim excess zeroes if there
      "lvl" => $cur["level_code"],
    );
  }                   
              
  // Let's add 10 additional (blank) lines to the courses array.
  for ($t = 0; $t < 10; $t++) {
    $courses[] = array(
      "course" => "",
      "term" => "",
      "grade" => "",
      "hrs" => "",
      "lvl" => "",
    );    
  }
  
  
  // Okay, now let's go through the courses array and display in the table...
  
  $stripe = "";  
  foreach ($courses as $t => $val) {
        
    if ($stripe == "") {
      $stripe = "style='background-color: beige;'";
    }
    else {
      $stripe = "";
    }  
        
    $form["course_$t"] = array(
      "prefix" => "<tr $stripe >
                      <td valign='top'>",
      "type" => "textfield",
      "size" => 12,      
      "suffix" => "</td>",
      "value" => $val["course"],
    );    

    $form["term_$t"] = array(
      "prefix" => "<td valign='top'>",
      "type" => "textfield",
      "size" => 8,      
      "suffix" => "</td>",
      "value" => $val["term"],
    );    

    $form["grade_$t"] = array(
      "prefix" => "<td valign='top'>",
      "type" => "textfield",
      "size" => 4,      
      "suffix" => "</td>",
      "value" => $val["grade"],
    );    
    
    $form["hrs_$t"] = array(
      "prefix" => "<td valign='top'>",
      "type" => "textfield",
      "size" => 4,      
      "suffix" => "</td>",
      "value" => $val["hrs"],
    );    
                
    $form["lvl_$t"] = array(
      "prefix" => "<td valign='top'>",
      "type" => "textfield",
      "size" => 4,      
      "suffix" => "</td>
                   </tr>",
      "value" => $val["lvl"],                   
    );    
                    
    
  } // for loop
   
  
  $form["number_of_courses"] = array(
    "type" => "hidden",
    "value" => count($courses),
  );
  
  
  
  // close table.
  $form["mark" . $m++] = array(
    "value" => "</table>
                <p>" . t("Rows without Course entered will be skipped.
                    <br><br><b>Note:</b> To add more blank rows, save your work.  When the page reloads, there will be additional
                   blank rows to add new courses.") . "</p>",
  );
  

  $form["submit_btn"] = array(
    "type" => "submit",
    "value" => "Submit",
  );  
  
  
  return $form;
} // user_student_edit_student_courses_form


function user_student_edit_student_courses_form_validate($form, $form_state) {
  // If the user requested it, check to see if the course exists (in the draft_courses table)
   
  if ($form_state["values"]["warn_me"] === TRUE) {
    // Yes, the user wants to be warned if they entered a course which doesn't exist.
    $number_of_courses = intval($form_state["values"]["number_of_courses"]);
    for ($t = 0; $t < $number_of_courses; $t++) {
      $course = trim($form_state["values"]["course_$t"]);
      if ($course == "") continue;
      
      $temp = explode(" ", $course);
      $subject_id = trim($temp[0]);
      $course_num = trim($temp[1]);
      
      // Check to see that this course exists.
      $res = db_query("SELECT subject_id FROM draft_courses
                       WHERE subject_id = '?'
                       AND course_num = '?' ", $subject_id, $course_num);
      $cur = db_fetch_array($res);
      if ($cur["subject_id"] != $subject_id) {
        form_error("course_$t", t("The course %subject_id %course_num could not be found in the draft_courses table.  A typo?  Your data has NOT been saved.", 
                                                  array("%subject_id" => $subject_id, "%course_num" => $course_num)));
      }                       
                
    }
  }
  
  
  
}





function user_student_edit_student_courses_form_submit($form, $form_state) {
    
  $student_id = $form_state["values"]["student_cwid"];
  
  // Erase what's there already:
  db_query("DELETE FROM student_courses WHERE student_id = '?' ", $student_id);  
  
  $number_of_courses = intval($form_state["values"]["number_of_courses"]);
  for ($t = 0; $t < $number_of_courses; $t++) {
    $course = trim($form_state["values"]["course_$t"]);
    if ($course == "") continue;
    
    $temp = explode(" ", $course);
    $subject_id = trim($temp[0]);
    $course_num = trim($temp[1]);      
    
    $term_id = trim($form_state["values"]["term_$t"]);
    $grade = trim($form_state["values"]["grade_$t"]);
    $hours_awarded = trim($form_state["values"]["hrs_$t"]);
    $level_code = trim($form_state["values"]["lvl_$t"]);

    // Add to table
    db_query("INSERT INTO student_courses (student_id, subject_id, course_num, hours_awarded, grade, term_id, level_code)
              VALUES ('?', '?', '?', '?', '?', '?', '?')", $student_id, $subject_id, $course_num, $hours_awarded, $grade, $term_id, $level_code);
   
  }  

  fp_add_message(t("Student courses updated."));
  
    
} // student_courses_form_submit






/**
 * Let the user edit a studentuser's information.
 */
function user_edit_student_user_form() {

  fp_add_js(fp_get_module_path("user") . "/js/user.js");

  $form = array();
  $m = 0;
  $student_cwid = @$_REQUEST["student_cwid"];  // now getting it from argument
  $de_catalog_year = @$_REQUEST["de_catalog_year"];
  $user_id = db_get_user_id_from_cwid($student_cwid, "student");

  $db = get_global_database_handler();

  // Figure out what the page's sub-tabs should be, and set them.
  $tab_array = array();
  $tab_array[0]["title"] = t("Edit Student");
  $tab_array[0]["active"] = TRUE;
  $tab_array[0]["on_click"] = "window.location=\"" . fp_url("admin/users/edit-student-user", "student_cwid=$student_cwid&de_catalog_year=$de_catalog_year") . "\"";
      
  $tab_array[1]["title"] = t("Edit Student Courses");
  $tab_array[1]["active"] = FALSE;
  $tab_array[1]["on_click"] = "window.location=\"" . fp_url("admin/users/edit-student-user/courses", "student_cwid=$student_cwid&de_catalog_year=$de_catalog_year") . "\"";
      
  fp_set_page_sub_tabs($tab_array);





  
  if ($student_cwid != "new") {  
    $name = fp_get_student_name($student_cwid);  
    fp_set_title(t("Edit Student User @name (@id)", array("@name" => $name, "@id" => $student_cwid)));
  }
  else {
    // A new student!
    fp_set_title(t("Create New Student User"));
  }

  $form["user_id"] = array(
    "type" => "hidden",
    "value" => $user_id,
  );

  $form["perform_action2"] = array(
    "type" => "hidden",
    "value" => "",
  );

  $form["student_cwid"] = array(
    "type" => "hidden",
    "value" => $student_cwid,
  );
    
  
  // TODO: At the moment, only faculty can be assigned roles in FP.  However, this could change
  // one day, so I am going to leave this code in place for students, but commented out.
  /*
  $user_roles = system_get_roles_for_user($user_id);
  //fpm($user_roles);
  $default_values = array();
  foreach ($user_roles as $rid => $val) {
    $default_values[$rid] = $rid;
  }

  // Show a list of roles in the system which we may select from, and check the ones
  // all ready assigned to this user.
  
  $options = array();
      
  $res = db_query("SELECT * FROM roles ORDER BY rid");
  while ($cur = db_fetch_array($res)) {
    
    $key = $cur["rid"];
    $value = $cur["name"];

    if ($key > 2) {
      $options[$key] = $value;
    } 
      
  }  
//fpm($default_values);
  $form["roles"] = array(
    "label" => t("Check which roles this user should have."),
    "type" => "checkboxes",
    "options" => $options,
    "value" => $default_values,
  );
  */  


  // Let's present the form elements to allow some basic editing of this user.
  
  // Only if we are making a new student...
  if ($student_cwid == "new") {
    $form["new_student_cwid"] = array(
      "label" => t("Enter a new CWID, unique to students:"),
      "type" => "textfield",
      "size" => 20,
      "required" => TRUE,     
      "description" => t("Enter an ID for this student.  It may be the same
                          as a faculty member, but may not be the same as any existing
                          student.  You will not be able to edit this value, once saved."),
    );
    
    $form["new_user_name"] = array(
      "label" => t("Enter a new username, unique to all users:"),
      "type" => "textfield",
      "size" => 20,     
      "required" => TRUE,
      "description" => t("Enter a username for this user.  This is what the user will
                          use to log in.  It must be unique to all users (cannot have both
                          a faculty and a student with the same username).  You will not
                          be able to edit this value, once saved."),
    );
        
    
    $cur = array();  
  }
  else {
    // NOT a new student.  Load their information normally.
    
    $res = db_query("SELECT * FROM users u, students s
                     WHERE u.cwid = '?'
                     AND u.is_student = '1'
                     AND u.cwid = s.cwid", $student_cwid);
    $cur = db_fetch_array($res);
  }

  $user_name = $cur["user_name"];
  if ($user_name != "") {
    $form["mark" . $m++] = array(
      "value" => "<p><b>Username:</b> $user_name</p>",
    );
  }
  
  $form["new_password"] = array(
    "label" => t("Enter a new password for this user:"),
    "type" => "textfield",
    "size" => 20,
    "required" => ($student_cwid == "new") ? TRUE : FALSE,
    "description" => t("If you enter any value here, it will change the
                        user's password in FlightPath.  If you are using the LDAP module,
                        the LDAP password will be unaffected."),    
  );

  $form["email"] = array(
    "label" => t("Email:"),
    "type" => "textfield",
    "value" => $cur["email"],
  );  

  $form["f_name"] = array(
    "label" => t("First name:"),
    "type" => "textfield",
    "value" => $cur["f_name"],
  );  

  $form["l_name"] = array(
    "label" => t("Last name:"),
    "type" => "textfield",
    "value" => $cur["l_name"],
  );  

  $form["is_disabled"] = array(
    "label" => t("Is disabled:"),
    "type" => "textfield",
    "value" => $cur["is_disabled"],
    "size" => 5,
    "description" => t("Enter only 1 or 0 (number one for 'yes', or number zero for 'no').  This setting means the user will
                        be ignored by FlightPath, and they will not be able to log in or be searched for.
                        It is safer to disable a user, than delete them."),
  );  
  
  
  // Unique to students...
  $form["cumulative_hours"] = array(
    "label" => t("Cumulative hours:"),
    "type" => "textfield",
    "value" => $cur["cumulative_hours"],
    "size" => 5,
    "description" => t("How many hours has the student earned?  NOTE: If FlightPath is set to
                        calculate this, rather than read from the database, whatever you enter here
                        will be ignored, and instead this value will be calculated on the fly when
                        the student is loaded."),
  );  
    
  $form["gpa"] = array(
    "label" => t("GPA:"),
    "type" => "textfield",
    "value" => $cur["gpa"],
    "size" => 5,
    "description" => t("What is the student's GPA?  NOTE: If FlightPath is set to
                        calculate this, rather than read from the database, whatever you enter here
                        will be ignored, and instead this value will be calculated on the fly when
                        the student is loaded."),    
  );  
    
  $form["rank_code"] = array(
    "label" => t("Rank or Classification:"),
    "type" => "textfield",
    "value" => $cur["rank_code"],
    "size" => 5,
    "description" => t("For example, FR, SO, JR, SR, GR for Freshman, Sophomore, Junior, Senior, and Graduate.") . "
                      <br><b>" . t("Important:") . "</b> " . t("You must enter a code from the Allowed Student Ranks field
                      on the System Settings form.  For example, FR.  If you do not do this, you will not be able to search
                      for this student in FlightPath.") . "<br>&nbsp; &nbsp; &nbsp;" . t("Current value for Allowed Student Ranks:") . " 
                      <i>" . variable_get("allowed_student_ranks", "") . "</i>",
  );  

  
  $major_code_array = $db->get_student_majors_from_db($student_cwid, FALSE, TRUE);
  
  $temp = "";
  foreach ($major_code_array as $details) {
    if ((string)$details["is_editable"] == "1") {
      $temp .= "* ";
    }
    
    $temp .= $details["major_code"] . "\n";
  }      
  
  $form["major_codes"] = array(
    "label" => t("Major code(s):"),
    "type" => "textarea",
    "value" => $temp,
    "cols" => 40,
    "rows" => 5,
    "description" => t("Enter the student's major codes, one per line.
                          <br>Ex:
                          <br>&nbsp; &nbsp; ACCT
                          <br>&nbsp; &nbsp; MATH
                        <br>Advanced: If any of the level 3 (add-on) major codes can be 'editable' by the advisor or another
                        privileged user during normal advising, add an asterisk
                        at the beginning.  For example: <b>*</b> ACCT|_COSC.  If you are unsure what this means, do not enter an asterisk."),
  );  
    
    
  $form["catalog_year"] = array(
    "label" => t("Catalog year:"),
    "type" => "textfield",
    "value" => $cur["catalog_year"],
    "size" => 10,
    "description" => t("Only the leading year is used.  For example, for the
                        catalog year 2008-2009, you would just enter 2008."),
  );  

  $form["is_active"] = array(
    "label" => t("Is active:"),
    "type" => "textfield",
    "value" => $cur["is_active"],
    "size" => 5,
    "description" => t("Enter only 1 or 0 (number one for 'yes', or number zero for 'no').  This setting means the student
                      will not appear in searches automatically, unless the advisor specifies to search
                      for inactive students."),
  );  
        
      
      
  
  $form["submit"] = array(
    "type" => "submit",  
    "value" => "Submit",    
    "prefix" => "<hr>",
  );
  
  
  if ($student_cwid != "new" && user_has_permission("delete_users")) {
    
    $form["mark" . $m++] = array(
      "type" => "markup",
      "value" => "<div align='right'>
                        " . t("Delete this student?") . " <input type='button' value='X'
                        onClick='userDeleteStudent();'>
                      </div>", 
    );  
  }
  
  
  
  
  
  return $form;    
  
}


/**
 * Validate handler for editing student users.
 */
function user_edit_student_user_form_validate($form, $form_state) {
  
  $values = $form_state["values"];
  
  // If a password was given, make sure it is appropriate.
  if (trim($values["new_password"]) != "") {
    if (strlen(trim($values["new_password"])) < 5) {
      form_error("new_password", t("Please enter a password that is at least 5 characters long."));
      return;
    }
  }
  
  // If creating a new user, make sure new_student_cwid and new_user_name are not
  // already in use.
  if ($values["student_cwid"] == "new") {
    $new_cwid = trim($values["new_student_cwid"]);
    $new_user_name = trim($values["new_user_name"]);

    /*  // CWIDS are no longer required to be numeric.
    // Check that cwid is numeric.
    if (!is_numeric($new_cwid)) {
      form_error("new_student_cwid", t("The cwid you entered is not numeric.  CWIDs must contain only numbers.  
                                    Please select a different cwid."));
      return;
    }
    */

    // Check that username is at least 4 characters
    if (strlen($new_user_name) < 4) {
      form_error("new_user_name", t("The username you entered is too short.  It must be at least 4 characters.
                                      Please select a different username."));
      return;
      
    }

    
    // Check cwid isn't already in use.
    $test = db_result(db_query("SELECT cwid FROM users WHERE cwid = '?' AND is_student = '1'", $new_cwid));    
    if ($test == $new_cwid) {
      form_error("new_student_cwid", t("The cwid you entered is already in use.  Please select a different cwid."));
      return;
    }
   
    // Check user_name isn't already in use.
    $test = db_result(db_query("SELECT user_name FROM users WHERE user_name = '?' ", $new_user_name));    
    if ($test == $new_user_name) {
      form_error("new_user_name", t("The username you entered is already in use.  Please select a different username."));
      return;
    }
      
   
    
  }
    
  
}


/**
 * Submit handler for editing student users.
 */
function user_edit_student_user_form_submit($form, $form_state) {
  
  $values = $form_state["values"];
  foreach ($values as $key => $val) {
    if (!is_array($val)) {
      $values[$key] = trim($val);
    }
  }
  //fpm($values);
  $user_id = $values["user_id"];
  $student_cwid = $values["student_cwid"];
  
  // Are we supposed to DELETE a student?
  if ($values["perform_action2"] == "delete_student" && user_has_permission("delete_users")) {

    
    db_query("DELETE FROM students WHERE cwid = '?' ", $student_cwid);
    db_query("DELETE FROM users WHERE cwid = '?' AND is_student = '1' ", $student_cwid);      
    
    fp_add_message(t("User has been deleted."));
    fp_goto("admin/users/students");
    return;
  }
  
  $bool_is_new = FALSE;
  
  if ($student_cwid != "new") {
    // NOT a new student!  Insert values normally.
    // First-- was there a password given?  If so, insert that separate.
    if (trim($values["new_password"]) != "") {
      $new_pass = md5(trim($values["new_password"]));
      db_query("UPDATE users 
                SET password = '?' 
                WHERE cwid = '?' 
                AND is_student = '1' ", $new_pass, $student_cwid);
    }  
    // Okay, now we can just update everything else.
    // Update users table first...
    db_query("UPDATE users
              SET email = '?',                  
                  f_name = '?',
                  l_name = '?',
                  is_disabled = '?'
               WHERE cwid = '?'
               AND is_student = '1' ", $values["email"], $values["f_name"], 
                                       $values["l_name"], $values["is_disabled"],
                                       $student_cwid);
   
   // Now, update the students table entry.
   db_query("UPDATE students
             SET cumulative_hours = '?',
                 gpa = '?',
                 rank_code = '?',                 
                 catalog_year = '?',
                 is_active = '?'                 
             WHERE cwid = '?'", $values["cumulative_hours"], $values["gpa"], $values["rank_code"],
                                $values["catalog_year"], 
                                $values["is_active"], $student_cwid);             

    fp_add_message(t("User updated successfully."));

  }
  else {
    // This is a NEW user!  We need to perform inserts.  Thanks to our validate handler,
    // we know all of the values we have are valid.
    
    $bool_is_new = TRUE;
    
    db_query("INSERT INTO users (user_name, password, is_student, email, cwid, f_name, l_name, is_disabled)
              VALUES ('?', '?', '1', '?', '?', '?', '?', '?')
              ", $values["new_user_name"], md5($values["new_password"]), $values["email"], $values["new_student_cwid"],
                 $values["f_name"], $values["l_name"], $values["is_disabled"]);
    db_query("INSERT INTO students (cwid, cumulative_hours, gpa, rank_code, catalog_year, is_active)
              VALUES ('?', '?', '?', '?', '?', '?')
             ", $values["new_student_cwid"], $values["cumulative_hours"], $values["gpa"], $values["rank_code"],
                $values["catalog_year"], $values["is_active"]);
                                  
    fp_add_message(t("User created successfully."));
    
    $student_cwid = $values["new_student_cwid"];
        
  }
  
  
  // Now, regardless if this was a new student or not, we need to update the student_degrees table.
  // First, delete what's there for this CWID.
  db_query("DELETE FROM student_degrees WHERE student_id = ?", $student_cwid);
  // Now, insert.
  $temp = explode("\n", $values["major_codes"]);
  foreach ($temp as $mc) {
    $mc = trim($mc);
    if ($mc == "") continue;
    
    $is_editable = "0";
    if (strstr($mc, "*")) {
      $is_editable = "1";
      $mc = trim(str_replace("*", "", $mc));  // remove the asterisk.
    }
    
    // Now, insert into the table.
    db_query("INSERT INTO student_degrees (student_id, major_code, is_editable)
              VALUES (?, ?, ?) ", array($student_cwid, $mc, $is_editable));    
    
  }
  
  
  
  if ($bool_is_new) {
    // If new, we need to go to the regular edit form for this newly created student.
    fp_goto("admin/users/edit-student-user", "student_cwid=" . $values["new_student_cwid"]);
  }
  
  // If not new, it just reloads the form normally.
  
}

