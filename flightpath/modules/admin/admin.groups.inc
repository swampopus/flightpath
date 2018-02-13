<?php

/* This include file deals with Groups editing for FlightPath */



/**
 * Validate handler for edit group form.
 */
function admin_edit_group_form_validate(&$form, &$form_state) {
  $values = $form_state["values"];
  $group_name = trim($values["group_name"]);
  $group_id = $values["group_id"];
  $de_catalog_year = $values["de_catalog_year"];
  
  
  // We want to make sure the group_name is in a machine-name format,
  // and we need to make sure it is unique for this catalog year!
  $group_name = fp_get_machine_readable(strtolower($group_name));
  
  $form_state['values']['group_name'] = $group_name;
  
    
  // Check that group_name is unique (but exclude the current group_id from the check)
  $mycount = db_query("SELECT count(*) as mycount FROM draft_groups 
                       WHERE group_name = ?
                       AND group_id != ?
                       AND delete_flag = '0'
                       AND catalog_year = ?", $group_name, $group_id, $de_catalog_year)->fetchColumn();
  
  if ($mycount > 0) {    
    form_error("group_name", t("The 'Internal group machine name' %gn is already in use in FlightPath by another group
                                in the catalog year %cy.  
                                Please change to a different name, and submit again. <b>Your work has NOT been saved.</b>
                                ", array("%gn" => $group_name, "%cy" => $de_catalog_year)));
    return;
  }
  
  
}



function admin_edit_group_form_submit(&$form, $form_state) {
  $values = $form_state["values"];
  $de_catalog_year = $values["de_catalog_year"];

  $db = get_global_database_handler();  
  $db2 = new DatabaseHandler();

  $group_id = $values["group_id"];

  // Okay, we are trying to save the details of this group.
  // First thing we need to do is UPDATE the title, group_name,
  // priority, icon and comment.
  $group_name = trim($values["group_name"]);
  $title = trim($values["title"]);
  $priority = trim($values["priority"]);
  $icon_filename = trim($values["icon_filename"]);
  $data_entry_comment = trim($values["data_entry_comment"]);
  $catalog_repeat = intval(trim($values["catalog_repeat"]));

  // Save the entire post to the log.
 
  // Since we are making a change to the draft table(s), let's add a row
  // to draft instructions.
  $db->add_draft_instruction("-");


  // Are we trying to delete this group?
  if ($_POST["perform_action2"] == "delete_group" && user_has_permission("can_delete_data_entry")) {
    $res = db_query("UPDATE draft_groups
                SET delete_flag = '1'
                WHERE group_id = '?' 
                AND catalog_year = '?'
                ", $group_id, $de_catalog_year);
    fp_add_message(t("The group @title (%name) has been deleted successfully for @year", array("@title" => $title, "%name" => $group_name, "@year" => $de_catalog_year)));
    fp_goto("admin/groups", "de_catalog_year=$de_catalog_year");
    return;
  }

  // If the $group_id == new then create a new one.
  if ($group_id == "new") {
    $group_id = $db->request_new_group_id();
    $res = db_query("INSERT INTO draft_groups(group_id, catalog_year)
                values ('?','?') ", $group_id, $de_catalog_year);
    $values["group_id"] = $group_id;
    // Let's reset where we should redirect to after the form submits.
    $form["#redirect"] = array(
      "path" => "admin/groups/edit-group",
      "query" => "group_id=$group_id&de_catalog_year=$de_catalog_year",
    );    
  }

  $res = db_query("UPDATE draft_groups
              SET group_name = '?',
              title = '?',
              priority = '?',
              icon_filename = '?',
              catalog_repeat = '?',
              data_entry_comment = '?'
              WHERE
                group_id = '?' ", 
              $group_name, $title, $priority, $icon_filename, $catalog_repeat, $data_entry_comment, $group_id);

  // We need to delete all the existing course & subgroup requirements from this group.
  // That entails first seeing what subgroups were required and deleting them,
  // then deleting the parent group's requirements.
  // First, find and delete the branches (child groups):
  $res = db_query("SELECT * FROM draft_group_requirements
              WHERE group_id = '?'
              AND child_group_id != '0' ", $group_id);
  while ($cur = db_fetch_array($res)) {
    $cg_id = $cur["child_group_id"];
    $res2 = db_query("DELETE FROM draft_group_requirements
                WHERE group_id = '?' ", $cg_id);
  }
  // Now delete the course requirements...
  $res = db_query("DELETE FROM draft_group_requirements
                WHERE group_id = '?' ", $group_id);

  $courses = trim($values["courses"]);
  // If a definition was set, then we will ignore what is in the POST
  // for the course requrements, and instead use the definition.
  if (trim($values["set_definition"] != "")) {
    $def = urldecode(trim($values["set_definition"]));
    //$cc = trim(get_courses_from_definition($def, $de_catalog_year));
    $temp2 = admin_get_courses_from_definition($def);
    $cc = trim($temp2["text"]);
    if ($cc != "") {
      $courses = $cc;
      // UPDATE this group's definition!
      $res = db_query("UPDATE draft_groups
              SET definition = '?'
              WHERE
                group_id = '?' ", $def, $group_id);

    }
    //print_pre($cc);
  }
  else {
    // In other words, the setDefinition WAS blank.
    // Let's update the table.  This is to fix a bug where they were unable
    // to clear definitions.
      $res = db_query("UPDATE draft_groups
              SET definition = ''
              WHERE
                group_id = '?' ", $group_id);    
  }



  // If catalog_repeat == 1, then we need to perform that operation on the $courses text.
  if ($catalog_repeat == 1) {
    $courses = admin_process_catalog_repeats_for_group_courses_text($courses, $de_catalog_year);
  }

  // Okay, now we look at the actual "courses" box and assemble the group
  // in the database.
  $lines = explode("\n", $courses);
  for ($t = 0; $t < count($lines); $t++) {
    $line = trim($lines[$t]);
    if ($line == "") { continue; }
    // Get rid of extra whitespace.
    $line = str_replace("  ", " ", $line);
    $line = str_replace("  ", " ", $line);
    $line = str_replace("  ", " ", $line);

    // Does this line contain at least one & symbol?  If it does,
    // then this is a subgroup (branch).  If not, then we can insert
    // the course as-is.
    if (strstr($line, "&")) {
      // This line DOES have an ampersand (&), so this is a sub group
      // within this group.
      // First, we need to request a new branchID for this new group.
      if (!$branch_id = $db->request_new_group_id()) {
        die ("Error.  Could not create new group (branch) ID.");
      } 
      else {
        // Add this branch to the list of requirements for this group.
        $query = "INSERT INTO draft_group_requirements
                    (group_id, child_group_id)
                    values ('?','?') ";
        $res = db_query($query, $group_id, $branch_id);

      }
      $c_tokes = explode("&",$line);
      for ($cT = 0; $cT < count($c_tokes); $cT++)
      {
        $tokens = explode(" ", trim($c_tokes[$cT]));
        $subject_id = trim($tokens[0]);
        $course_num = trim($tokens[1]);
        $min_grade = @trim($tokens[2]);
        $course_repeats = @trim($tokens[3]);

        if (strstr($min_grade, "[")) {
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
        if ($course_id = $db->get_course_id($subject_id, $course_num, "", true)) {
                    
          $query = "INSERT INTO draft_group_requirements
                    (group_id, course_id,
                    course_min_grade, course_repeats, data_entry_value)
                    values ('?','?','?','?', ?) ";
          $res = db_query($query, $branch_id, $course_id, $min_grade, $course_repeats, "$subject_id~$course_num");
        } 
        else {
          // The course_id could not be found!
          
          form_error("courses", t("Course Not Found!
              You specified the course
              <b>%course</b> as a requirement, but this course
              could not be found in the catalog.
              It was removed from the list of requirements.
              Are you sure you typed it correctly?  Please check 
              your spelling, and add the course again.", array("%course" => "$subject_id $course_num")));

        }

      }


    } 
    else {
      // Did NOT contain an ampersand (&), so this goes in the
      // regular course requirements.
      $tokens = explode(" ", $line);
      $subject_id = @trim($tokens[0]);
      $course_num = @trim($tokens[1]);
      $min_grade = @trim($tokens[2]);
      $course_repeats = @trim($tokens[3]);

      if (strstr($min_grade, "[")) {
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
      if ($course_id = $db->get_course_id($subject_id, $course_num, "", true)) {
        $query = "INSERT INTO draft_group_requirements
                    (group_id, course_id,
                    course_min_grade, course_repeats, data_entry_value)
                    values (?,?,?,?,?) ";
        $res = db_query($query, $group_id, $course_id, $min_grade, $course_repeats, "$subject_id~$course_num");
        
      } 
      else {
        // The course_id could not be found!
        form_error("courses", t("Course Not Found!
              You specified the course
              <b>%course</b> as a requirement, but this course
              could not be found in the catalog.
              It was removed from the list of requirements.
              Are you sure you typed it correctly?  Please check 
              your spelling, and add the course again.", array("%course" => "$subject_id $course_num")));

      }



    }

  }


  fp_add_message("Group successfully updated.");

  // Clear previous values to make sure the Required Courses box gets updated,
  // even if its set to readonly.
  clear_session_form_values("admin_edit_group_form");
  
}






/**
 * This function will accept the $courses text (textarea) from a group, which spells out
 * all of the courses, and then assign specified repeats based on what is set for that course
 * in the course catalog.
 */
function admin_process_catalog_repeats_for_group_courses_text($courses, $catalog_year) {
  $rtn = "";
  
  // Okay, now we look at the actual "courses" box and assemble the group
  // in the database.
  $lines = explode("\n", $courses);
  for ($t = 0; $t < count($lines); $t++) {
    $line = trim($lines[$t]);
    if ($line == "") { continue; }
    // Get rid of extra whitespace.
    $line = str_replace("  ", " ", $line);
    $line = str_replace("  ", " ", $line);
    $line = str_replace("  ", " ", $line);

    $new_line = "";

    // Does this line contain at least one & symbol?  If it does,
    // then this is a subgroup (branch), and we need to look at all the courses
    // on that branch.
    if (strstr($line, "&")) {
      
      // If this branch contains an ampersand, I don't think I should attempt to process it at all.
      $new_line = $line;
      
      /*
      $c_tokes = explode("&",$line);
      for ($cT = 0; $cT < count($c_tokes); $cT++)
      {
        $tokens = explode(" ", trim($c_tokes[$cT]));
        $subject_id = trim($tokens[0]);
        $course_num = trim($tokens[1]);
        $min_grade = @trim($tokens[2]);
        $course_repeats = @trim($tokens[3]);

        if (strstr($min_grade, "[")) {
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

        // Okay, we now have a subject_id and course_number.  Let's find out how many times
        // it can be repeated for credit.
        $max_cat_repeats = fp_get_max_catalog_repeats_for_course($subject_id, $course_num, $catalog_year, TRUE);

        // Okay, now, let's rebuild this line.        
        $subject_id = str_replace("&", "_A_", $subject_id);
        
        $new_line .= "$subject_id $course_num";
        // Was there a min grade?
        if ($min_grade != "") {
          $new_line .= " ($min_grade)";
        }
        // Specified repeats?
        if ($max_cat_repeats > 0) {
          $new_line .= " [$max_cat_repeats]";
        }
        
        // Put ampersand back at the end?
        if ($cT < count($c_tokes) - 1) {
          $new_line .= " & ";
        }
        else {
          // This is the last part.  No need to add an ampersand, but
          // leave blank, as we will add a \n to the very end later.
        }

        

      } // for $cT

      */
    } 
    else {
      // Did NOT contain an ampersand (&), so this goes in the
      // regular course requirements.
      $tokens = explode(" ", $line);
      $subject_id = @trim($tokens[0]);
      $course_num = @trim($tokens[1]);
      $min_grade = @trim($tokens[2]);
      $course_repeats = @trim($tokens[3]);

      if (strstr($min_grade, "[")) {
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

      // Okay, we now have a subject_id and course_number.  Let's find out how many times
      // it can be repeated for credit.
      $max_cat_repeats = fp_get_max_catalog_repeats_for_course($subject_id, $course_num, $catalog_year, TRUE);

      // Okay, now, let's rebuild this line.        
      $subject_id = str_replace("&", "_A_", $subject_id);
      
      $new_line .= "$subject_id $course_num";
      // Was there a min grade?
      if ($min_grade != "") {
        $new_line .= " ($min_grade)";
      }
      // Specified repeats?
      if ($max_cat_repeats > 0) {
        $new_line .= " [$max_cat_repeats]";
      }

           


    } // else (does not contain &)

    // Add to our $rtn
    $rtn .= $new_line . "\n";
    
  } // for lines...
        
    
    
  return $rtn;
} // admin_process_catalog_repeats_for_group_courses_text






/**
 * This function lets the user edit a group.
 */
function admin_edit_group_form() {
  $form = array();
  $m = 0;
  
  $de_catalog_year = admin_get_de_catalog_year();
  $group_id = $_REQUEST["group_id"];
  
  fp_add_css(fp_get_module_path("admin") . "/css/admin.css");
  fp_add_js(fp_get_module_path("admin") . "/js/admin.js");
    
  
  $form["#redirect"] = array(
    "path" => "admin/groups/edit-group",
    "query" => "group_id=$group_id&de_catalog_year=$de_catalog_year",
  );
  
  
  if (user_has_permission("can_view_advanced")) {
    $form["mark" . $m++] = array(
      "type" => "markup",
      "value" => " <span class='tenpt' style='background-color: yellow; margin-left: 20px;'>
          adv: group_id = $group_id.
          Used by:
          <a href='javascript: adminPopupWindow(\"" . fp_url("admin/groups/popup-show-group-use", "group_id=$group_id")  . "\");'>[degrees]</a>
          </span>",
      "weight" => 0,
    );
  }

  $group = new Group($group_id, null, -1, false, true);
  //print_pre($group->to_string());
  $group->load_descriptive_data();

  fp_set_title(t("Edit Group:") . " $group->title ($de_catalog_year)");

  $form["perform_action2"] = array(
    "type" => "hidden",
    "value" => "",
  );

  $form["set_definition"] = array(
    "type" => "hidden",
    "value" => urlencode($group->definition),
  );

  $form["scroll_top"] = array(
    "type" => "hidden",
    "value" => "",
  );

  $form["group_id"] = array(
    "type" => "hidden",
    "value" => $group_id,
  );

  $form["de_catalog_year"] = array(
    "type" => "hidden",
    "value" => $de_catalog_year,
  );


  // Actually draw the form out.
  $form["title"] = array(
    "type" => "textfield",
    "label" => t("Group title:"),
    "value" => $group->title,
    "maxlength" => 100,
    "required" => TRUE,
    "popup_description" => t("This is what FlightPath uses to refer to this group in screens and popups.
                        Ex: Free Electives, Art Electives, Core Humanities, etc."),
    "weight" => 10,
  );

  $form["group_name"] = array(
    "type" => "textfield",
    "label" => t("Internal group machine name:"),
    "value" => $group->group_name,
    "maxlength" => 100,
    "required" => TRUE,
    "popup_description" => t("The group's \"internal machine name\" is internal to FlightPath, and is never seen by the average user.
                        You may use this to distinguish between groups with the same title. Ex:
                        major_electives_sr and major_electives_jr.  The field must be unique within each catalog year, and
                        consist of only letters, numbers, and underscores.  FlightPath will make
                        sure it is unique when you save, and give you a chance to change it if it
                        is not."),
    "weight" => 20,
  );


  $form["priority"] = array(
    "type" => "textfield",
    "label" => t("Priority:"),
    "value" => $group->priority,
    "maxlength" => 10,
    "size" => 10,
    "popup_description" => t("This should be a number, and it is very important, because it determines the
                        order in which courses are assigned to groups in FlightPath.
                        Higher numbers fill in FIRST!  So a group with a priority of 100 would fill in before
                        a group with a priority of 30."),
    "weight" => 30,
  );


  $form["icon_filename"] = array(
    "type" => "hidden",
    "label" => t("Icon:"),
    "value" => $group->icon_filename,
    "suffix" => "
                <img src='" . fp_theme_location() . "/images/icons/$group->icon_filename' width='19'>
                $group->icon_filename
                &nbsp; &nbsp;
                <a href='javascript: adminPopupWindow(\"" . fp_url("admin/groups/popup-select-icon", "group_id=$group_id") . "\");'>[select another]</a>
                ",
    "weight" => 40,
  );

    
  $form["definition"] = array(
    "type" => "markup",
    "label" => t("Definition:"),
    "value" => "<i>" . nl2br($group->definition) . "</i>",
    "prefix" => "<div style='overflow: auto; max-height: 150px;' class='admin-groups-show-definition'>",
    "suffix" => "</div><a href='javascript: adminPopupWindow(\"" . fp_url("admin/groups/popup-edit-definition", "de_catalog_year=$de_catalog_year&group_id=$group_id") . "\");'
                            >[" . t("edit definition") . "]</a>",
    "weight" => 50,
  );
 

  $form["mark" . $m++] = array(
    "type" => "markup",
    "value" => "<hr>",
    "weight" => 60,
  );

  
  $courses = admin_get_group_courses($group);
  $form["courses"] = array(
    "type" => "textarea",
    "label" => t("Required Courses:"),
    "rows" => 17,
    "cols" => 80,
    "value" => $courses,
    "weight" => 70,
  );
  // if a definition was specified, we need to disable the courses textarea.
  if (trim($group->definition)) {
    $form["courses"]["attributes"] = "readonly=readonly style='background-color: #ccc;' ";
    $form["courses"]["prefix"] = "<div class='admin-groups-courses-disabled'>
                                 " . t("Note: Because a definition was specified, you cannot directly
                                      edit the Required Courses.  Manage these courses using the
                                      Edit Definition popup window.") . "</div>";
                                 
  }
  
  $form["catalog_repeat"] = array(
    "type" => "checkbox",
    "label" => t("Set Catalog Repeats"),
    "value" => $group->db_catalog_repeat,
    "popup_description" => t("If checked, this group will automatically assign specified repeats to courses
                        which are determined to be repeatable for this catalog year.  
                        
                        For example, if you enter
                        ART 101, and in the course catalog that course may be taken two times, then
                        when you save this group the line will be changed to ART 101 [2].
                        
                        This will work with definitions, but will not work in groups with branches.
                        If you are unsure what to select, leave this box unchecked."),
    "weight" => 80,
  );

  $form["data_entry_comment"] = array(
    "type" => "textarea",
    "label" => t("Optional Comment: (only seen by other FlightPath administrators)"),
    "rows" => 3,
    "cols" => 80,
    "value" => $group->data_entry_comment,
    "weight" => 90,
  );
 
  
  $form["submit"] = array(
    "type" => "submit",
    "value" => "Submit",
    "prefix" => "<hr>",
    "weight" => 100,
  );
  
  // Only show delete option based on permission  
  if (user_has_permission("can_delete_data_entry")) {  
    $form["mark" . $m++] = array(
      "type" => "markup",
      "value" => "<div align='right' class='groups-delete-group-wrapper'>
                        " . t("Delete this group?") . " <input type='button' value='X'
                        onClick='adminDeleteGroup(\"$group_id\");'>
                      </div>",
      "weight" => 200,
    );
 
  }
  
  return $form;
}



function admin_display_groups_popup_edit_definition() {
  $rtn = $tdef = "";

  
  fp_add_css(fp_get_module_path("admin") . "/css/admin.css");
  fp_add_js(fp_get_module_path("admin") . "/js/admin.js");

  $group_id = trim($_REQUEST["group_id"]);
  $de_catalog_year = admin_get_de_catalog_year();
  $group = new Group($group_id, null, -1, false, true);
  $group->load_descriptive_data();

  if (isset($_REQUEST["definition"])) {
    $definition = trim($_REQUEST["definition"]);
  }
  else {
    $definition = $group->definition;
  }

  $results = admin_get_courses_from_definition($definition);

  
  // This text will be used to describe how the definition works in a javascript alert box:
  $tdef .= t("Definitions provide a very quick way to add or remove many courses from a group.  For example, to add all CSCI courses, enter:") . "\n";
  $tdef .= "       add CSCI.*\n";
  $tdef .= t("The . is used to seperate the subject from the course number.  The * means \"any value.\"  You may also use it in the subject.  For example, ");
  $tdef .= t("to add all CSCI and any course with a subject that begins with A, enter:") . "\n";
  $tdef .= "       add CSCI.*\n       add A*.*\n";
  $tdef .= t("Removing courses is done the same way.  For example, you can add all courses, then remove any course with a number lower than 400:") . "\n";
  $tdef .= "       add *.*\n       rem *.1*\n       rem *.2*\n       rem *.3*\n";
  $tdef .= "\n\n" . t("It should be stated that add statements will include courses which have been marked as \"exclude.\"  This is normal.  Those courses will not") . " ";
  $tdef .= t("show up in group selection screens, but they will be added to a group if a student has completed that course in the past.");
  
  
  
  $rtn .= "<b>Edit Definition for $group->title ($de_catalog_year)<br><i>$group->group_name</i></b>
    <br><br><form action='" . fp_url("admin/groups/popup-edit-definition", "de_catalog_year=$de_catalog_year&group_id=$group_id") . "' method='POST' id='mainform'>
      <table border='0'>
      <tr>
          <td valign='top' align='right'>
          <div class='tenpt' align='left'>Working Definition:
           " . fp_get_js_alert_link($tdef, "[?]") . "</div>
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
      " . t("If you are satisfied with the results of this definition, 
      click the Save to Group button.  Otherwise, simply close this window.") . "
      
      
      <input type='button' value='Save to Group' onClick='adminPopupSaveDefinition(\"\");'>
  
    ";    
    
    
     
  
  return $rtn;
}




function admin_get_course_array_from_course_id_array($course_idArray) {  
  
  // Convert an array of course_id's into their subject_id ~~ course_num format.
  // Pick non-excluded courses over excluded courses, when you have the option.
  $rtn_array = array();
  $db = new DatabaseHandler();


  // MUST use foreach since we used array_unique earlier.  Can't use
  // count($arr) after you use array_unique!!
  foreach($course_idArray as $t => $value) {
    $new_course = new Course();
    $new_course->bool_use_draft = true;
    $new_course->db = $db;
    $new_course->course_id = $course_idArray[$t];
    $new_course->load_descriptive_data(false);
    array_push($rtn_array, "$new_course->subject_id ~~ $new_course->course_num");
  }
  return $rtn_array;

}




function admin_get_courses_from_definition($definition, $catalog_year = "") {
   
  
  $group_array = array();

  // Okay, first things first, let's trim this sucker and remove extra whitespace.
  $definition = trim($definition);
  $definition = str_replace("   "," ",$definition);
  $definition = str_replace("  "," ",$definition);
  $definition = str_replace("  "," ",$definition);

  // Okay, now let's break this up into lines...
  $d_lines = explode("\n",$definition);
  foreach($d_lines as $line)
  {
    $line = trim($line);

    // Let's get each of the parts... the instruction, and the course data.
    $tokens = explode(" ", $line);
    $instruction = strtolower(trim($tokens[0]));
    @$course_data = trim($tokens[1]);

    // We know that the course data can also be broken up, by the .
    $c_tokens = explode(".", $course_data);
    $subject_data = trim(strtoupper($c_tokens[0]));
    @$course_numData = trim(strtoupper($c_tokens[1]));

    // Okay, so now, for this line, we have an instruction,
    // and some course data (possibly wild cards) to act on.
    //debugCT("$instruction $subject_data $course_numData");

    $t_array = admin_get_course_array_from_definition_data($subject_data, $course_numData, $catalog_year);
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
      $group_array = admin_array_diff($group_array, $t_array);

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
  $group_array2 = admin_get_course_array_from_course_id_array($course_idArray);

  //print_r($group_array);

  // Place in alphabetical order.
  sort($group_array2);

  //var_dump($group_array2);

  $rtn = array();
  $rtn["text"] = "";
  $count = 1;
  // Now that we have the groupArray, we will turn it into a string...
  for ($t = 0; $t < count($group_array2); $t++)
  {
    $line = trim($group_array2[$t]);
    if ($line == "~~" || $line == "") continue;
    $count++;
    $temp = explode(" ~~ ", $line);
    $si = trim($temp[0]);
    $cn = trim($temp[1]);

    @$rtn["text"] .= "$si $cn\n";


  }
  $rtn["text"] = str_replace("&", "_A_", $rtn["text"]);
  //debug_c_t(count($group_array));
  $rtn["count"] = $count;

  return $rtn;
}



function admin_get_course_id_array_from_course_array($course_array) {
  
  $rtn_array = array();
  $db = new DatabaseHandler();

  // MUST use foreach instead of for since we did
  // array_unique!  Can't trust count($arr)!
  foreach($course_array as $t => $value) {
    $line = trim($course_array[$t]);
    if ($line == "~~" || $line == "") continue;
    $count++;
    $temp = explode(" ~~ ", $line);
    $si = trim($temp[0]);
    $cn = trim($temp[1]);

    $course_id = $db->get_course_id($si, $cn, "", true);
    $rtn_array[] = "$course_id";  // force into a string.
  }

  return $rtn_array;
}



function admin_get_course_array_from_definition_data($subject_data, $course_numData, $catalog_year = "") {
  
   // Looks at the subjectData and course_numData fields, and constructs
  // a query to pull our every course which matches it.

  $rtn_array = array();

  $si = str_replace("*","%",$subject_data);
  $cn = str_replace("*","%",$course_numData);

  $catalog_line = "";
  if ($catalog_year != "") {
    $catalog_line = "AND catalog_year = '$catalog_year'";
  }

  
  $query = "SELECT * FROM draft_courses
        WHERE 
          subject_id LIKE '?'
        AND course_num LIKE '?'
        AND course_id > 0
        $catalog_line
        GROUP BY subject_id, course_num
        ";
  $res = db_query($query, $si, $cn) ;
  while ($cur = db_fetch_array($res)) {
    $course_id = $cur["course_id"];
    
    if (in_array($course_id, $rtn_array)) continue;
    $rtn_array[] = $course_id;
    
  }

  return $rtn_array;
}



/**
 * I had to create my own version of array_diff, because the built-in PHP
 * version has a nasty bug where it doesn't work after a certain number of elements.
 */
function admin_array_diff($array1, $array2) {
  
  
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



/**
 * This popup is called from the edit group page.  It lets the user
 * select an icon to assign to a group.
 */
function admin_display_groups_popup_select_icon() {
  $rtn = "";
  
  fp_add_css(fp_get_module_path("admin") . "/css/admin.css");
  fp_add_js(fp_get_module_path("admin") . "/js/admin.js");
    
  $group_id = $_REQUEST["group_id"];
  $group = new Group();
  $group->group_id = $group_id;
  $group->bool_use_draft = true;
  $group->load_descriptive_data();

  $rtn = "<b>Please Select an Icon to use for $group->title (<i>$group->group_name</i>):</b>
      <div class='tenpt'>Current icon: <img src='" . fp_theme_location() . "/images/icons/$group->icon_filename' width='19'>
      $group->icon_filename.
      <br><br>
      Available Icons:
        <blockquote>";
  $handle = opendir(fp_theme_location(FALSE) . "/images/icons/.");
  $files = array();


  $accepted_exts = array("jpg", "gif", "png", "bmp", "JPG", "GIF", "PNG", "BMP", "jpeg");
  while($file = readdir($handle)) {
    if ($file != "." && $file != "..") {
      // Make sure it's extension is an image file.
      // Find out it's ext first.
      $temp = explode(".", $file);
      $ext = $temp[count($temp) - 1];
      if (in_array($ext, $accepted_exts)) {
        $files[] = $file;
      }
    }
  }
  // make sure they are alphabetized.
  sort($files);


  foreach($files as $file) {
    $rtn .= "<div style='padding: 5px;'>
        <input type='button' value='Select -> ' onClick='adminPopupSelectIcon(\"$file\");' >
        &nbsp; &nbsp;
        <img src='" . fp_theme_location() . "/images/icons/$file' width='19'>
        $file</div>";
  }


  $rtn .= "</blockquote></div>";
  return $rtn;
}




/**
 * Return back the courses in a group, suitable for the edit-group form.
 */
function admin_get_group_courses(Group $group) {
  
  
  // Returns a plain text list of the courses in a group's requirements
  // for use in the edit_group_form.
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
    if (!isset($courses[$course_line]))
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




/**
 * This function will display a list of all our groups.
 */
function admin_display_groups() {
  $rtn = "";
  
  $de_catalog_year = admin_get_de_catalog_year();
  
  fp_add_css(fp_get_module_path("admin") . "/css/admin.css");
  fp_add_js(fp_get_module_path("admin") . "/js/admin.js");
  
  $db2 = new DatabaseHandler();

  fp_set_title(t("Edit Groups for") . " $de_catalog_year");

  $rtn .= "
      <h2 class='title'>" . t("Edit Groups for @de_catalog_year", array("@de_catalog_year" => $de_catalog_year)) . "</h2>
        Options:
        <ul style='margin-top: 5px;'>          
          <li>" . l(t("Add a new group to this year"), "admin/groups/edit-group", "de_catalog_year=$de_catalog_year&group_id=new") . "</li>
          <li>" . l(t("Process all group definitions & catalog repeats for this year"), "admin/groups/process-all-definitions", "de_catalog_year=$de_catalog_year") . "</li>
          </li>
        </ul>
          <div align='center'>" . t("Hint: use CTRL-F to search groups quickly") . "</div>
        <table border='0' cellpadding='2' width='100%' class='admin-groups-list'>
        <tr>
          <th>" . t("Title") . "</th>
          <th>" . t("Internal Name") . "</th>
          <th align='center'>Pri</th>
          <th align='center'>i</th>
          <th align='center'>" . t("Used") . "</th>
        </tr>
      ";

  $on_mouse_over = " onmouseover=\"style.backgroundColor='#FFFF99'\"
              onmouseout=\"style.backgroundColor='white'\" ";

  $res = db_query("SELECT * FROM draft_groups
              WHERE catalog_year = '?'
              AND delete_flag = '0'
              ORDER BY title, group_name ", $de_catalog_year);
  while($cur = db_fetch_array($res)) {
    extract($cur, 3, "db");

    $use_count = 0;
    // Find out how many degree plans are using this particular group...

    $res2 = db_query("SELECT count(id) AS count FROM draft_degree_requirements
                WHERE group_id = '?' ", $db_group_id);
    if (db_num_rows($res2) > 0) {
      $cur2 = db_fetch_array($res2);
      $use_count = $cur2["count"];
    }

    $def_flag = "";
    if (trim($db_definition) != "") {
      $def_flag = " (*)";
    }

    if ($db_title == "") {
      $db_title = "[" . t("NO TITLE SPECIFIED") . "]";
    }

    $rtn .= "<tr $on_mouse_over>
          <td valign='top' class='tenpt'><a name='group_$db_group_id'></a>
            " . l($db_title, "admin/groups/edit-group", "group_id=$db_group_id&de_catalog_year=$de_catalog_year") . "
          </td>
          <td valign='top' class='tenpt'>
            <i>$db_group_name</i>$def_flag
          </td>
          <td valign='top' class='tenpt'>
            $db_priority
          </td>
          <td valign='top' class='tenpt'>
            <img src='" . fp_theme_location() . "/images/icons/$db_icon_filename' width='19'>
          </td>
          <td valign='top' class='tenpt'>
            $use_count <a href='javascript: adminPopupWindow(\"" . fp_url("admin/groups/popup-show-group-use", "group_id=$db_group_id") . "\");'><img src='" . fp_theme_location() . "/images/popup.gif' border='0'></a>
          </td>

          
        </tr>"; 
  }

  $rtn .= "</table>";
  
  
  
  
  return $rtn;
}



function admin_process_all_definitions_form() {
  $form = array();
  $de_catalog_year = admin_get_de_catalog_year();

  fp_set_title(t("Process all group definitions and settings for @year", array("@year" => $de_catalog_year)));
  $m = 0;
  
  $form["mark" . $m++] = array(
    "type" => "markup",
    "value" => t("This will cause all of the groups with definitions and other settings (ex: catalog repeats) to be cleared, and their
                  course requirements re-processed.  This function can take several minutes, depending
                  on how many groups with definitions you have."), 
  );  
      

  $form["de_catalog_year"] = array(
    "type" => "hidden",
    "value" => $de_catalog_year,
  );

  $form["submit"] = array(
    "type" => "submit",
    "value" => t("Process groups"),
    "description" => t("Can take several minutes to run. Only click once!"),
  );
     


    
  return $form;
  
}

/**
 * Actually perform the refreshing of definitions.
 */
function admin_process_all_definitions_form_submit($form, $form_submit) {
  
  $values = $form_submit["values"];
  
  $db = get_global_database_handler();
  $de_catalog_year = $values["de_catalog_year"];

  
  // Okay, set up the batch....
  $batch = array(
    "operation" => array("admin_process_all_definitions_perform_batch_operation", array($de_catalog_year)),
    "title" => t("Process all group definitions & settings for %year", array("%year" => $de_catalog_year)),
    "file" => menu_get_module_path("admin") . "/admin.groups.inc",
    "progress_message" => "Processing group @current of @total",
    "display_percent" => TRUE,
  );
 
 
  // Set the batch...
  batch_set($batch);  
  
  
  /*
  
  
  
  
  // This function will go through every group for this year and
  // re-run it's definition, saving the result.

  // First, find every group which has a definition set.
  $res = db_query("SELECT * FROM draft_groups
                       WHERE definition != '' 
                       AND catalog_year = '?' 
                       AND delete_flag = 0 ", $de_catalog_year);
  while($cur = db_fetch_array($res)) {
    $def = $cur["definition"];
    $group_id = $cur["group_id"];
    $group_name = $cur["group_name"];
    $temp = admin_get_courses_from_definition($def);
    $courses = trim($temp["text"]);
    $ccount = 0;

    fp_add_message(t("Working on %name", array("%name" => $group_name)));

    // Remove all the existing group requirements for this group first.
    $res2 = db_query("DELETE FROM draft_group_requirements
                        WHERE group_id = ? ", $group_id);


    $lines = explode("\n", $courses);
    for ($t = 0; $t < count($lines); $t++) {
      $line = trim($lines[$t]);
      if ($line == "") { continue; }
      // Get rid of extra whitespace.
      $line = str_replace("  ", " ", $line);
      $line = str_replace("  ", " ", $line);
      $line = str_replace("  ", " ", $line);

      // Does this line contain at least one & symbol?  If it does,
      // then this is a subgroup (branch).  If not, then we can insert
      // the course as-is.
      if (!strstr($line, "&")) {
        // Did NOT contain an ampersand (&), so this goes in the
        // regular course requirements.
        $tokens = explode(" ", $line);
        $subject_id = trim($tokens[0]);
        $course_num = trim($tokens[1]);
        @$min_grade = trim($tokens[2]);
        @$course_repeats = trim($tokens[3]);

        if (strstr($min_grade, "[")) {
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
        if ($course_id = $db->get_course_id($subject_id, $course_num, "", true)) {
          $query = "INSERT INTO draft_group_requirements
                    (`group_id`,`course_id`,
                    `course_min_grade`,`course_repeats`,`data_entry_value`)
                    values (?, ?, ?, ?, ?) ";
          $res2 = db_query($query, $group_id, $course_id, $min_grade, $course_repeats, "$subject_id~$course_num");
          $ccount++;        
        } 
        else {
          // The course_id could not be found!

          fp_add_message(t("Course not found! You specified %course as a requirement in %gname,
                            but this course could not be found in the catalog.  It was removed from
                            the list of requirements.  Are you sure you typed it correctly? Please
                            check your spelling, and add the course again.", array("%course" => "$subject_id $course_num", "%gname" => $group_name)), "error");

        }
      }
    }

    fp_add_message(t("%name definition processed. %count courses added.", array("%name" => $group_name, "%count" => $ccount)));    

  }
  
    
  fp_add_message(t("Group definitions have been re-run for @year", array("@year" => $de_catalog_year)));
   */ 
}



/**
 * This actually is the batch operation for processing all of our group definitions.
 */
function admin_process_all_definitions_perform_batch_operation(&$batch, $de_catalog_year) {
  
  
  ////////////////////////////  FIRST TIME ///////////////////////////////
  // If this is our first time through, let's init our values.
  if (!isset($batch["results"]["total"])) {
    // Our first time through.  Let's start up.    
    $batch["results"]["current"] = 0;
    $batch["results"]["finished"] = FALSE;    

    // Change to count how many groups with definitions OR "catalog_repeat" set.
    // We need a count of how many groups in this year have definitions to process.
    $res = db_query("SELECT count(*) as `count` FROM draft_groups
                       WHERE (definition != '' OR catalog_repeat = 1) 
                       AND catalog_year = '?' 
                       AND delete_flag = 0 ", $de_catalog_year);
    $cur = db_fetch_array($res);
        
    $batch["results"]["total"] = $cur["count"];
    
  }
  ////////////////////////////////////////////////////////////////////////  
  
  
  // Okay, we can now begin the actual batch process.
  $current = $batch["results"]["current"];
  $total = $batch["results"]["total"];

  $limit = 1;  // how many definitions to process per run of THIS function
  $c = 0;  // count of records.
  
  $db = get_global_database_handler();  
  
  // Change to count how many groups with definitions OR "catalog_repeat" set.
  // First, find every group which has a definition set.
  $res = db_query("SELECT * FROM draft_groups
                       WHERE (definition != '' OR catalog_repeat = 1) 
                       AND catalog_year = '?' 
                       AND delete_flag = 0 
                       limit $current, $limit", $de_catalog_year);
  while($cur = db_fetch_array($res)) {
          
    if ($c >= $limit) {
      break;
    }
      
    
    $def = trim($cur["definition"]);
    $group_id = $cur["group_id"];
    $group_name = $cur["group_name"];
    $catalog_repeat = intval($cur['catalog_repeat']);

    // Only do definitions if there IS a definition.
    if ($def != "") {
      $temp = admin_get_courses_from_definition($def);
      $courses = trim($temp["text"]);
    }
    else {
      // If there are no definitions, assemble a $courses string. admin_get_group_courses(Group $group)
      
      $g = new Group($group_id, null, -1, false, true);  // make sure we are loading from draft.
      $courses = admin_get_group_courses($g);
            
    }

    // If the catalog_repeat flag is set, process that.
    if ($catalog_repeat == 1) {
      $courses = admin_process_catalog_repeats_for_group_courses_text($courses, $de_catalog_year);
    }

    $ccount = 0;

    fp_add_message(t("Working on %name", array("%name" => $group_name)));




    // We need to delete all the existing course & subgroup requirements from this group.
    // That entails first seeing what subgroups were required and deleting them,
    // then deleting the parent group's requirements.
    // First, find and delete the branches (child groups):
    $res2 = db_query("SELECT * FROM draft_group_requirements
                WHERE group_id = ?
                AND child_group_id != '0' ", $group_id);
    while ($cur2 = db_fetch_array($res2)) {
      $cg_id = $cur2["child_group_id"];
      $res22 = db_query("DELETE FROM draft_group_requirements
                  WHERE group_id = ? ", $cg_id);
    }
    // Now delete the course requirements...
    $res2 = db_query("DELETE FROM draft_group_requirements
                  WHERE group_id = ? ", $group_id);



    $lines = explode("\n", $courses);
    for ($t = 0; $t < count($lines); $t++) {
      $line = trim($lines[$t]);
      if ($line == "") { continue; }
      // Get rid of extra whitespace.
      $line = str_replace("  ", " ", $line);
      $line = str_replace("  ", " ", $line);
      $line = str_replace("  ", " ", $line);

      // Does this line contain at least one & symbol?  If it does,
      // then this is a subgroup (branch).  If not, then we can insert
      // the course as-is.
      if (!strstr($line, "&")) {
        // Did NOT contain an ampersand (&), so this goes in the
        // regular course requirements.
        $tokens = explode(" ", $line);
        $subject_id = trim($tokens[0]);
        $course_num = trim($tokens[1]);
        @$min_grade = trim($tokens[2]);
        @$course_repeats = trim($tokens[3]);

        if (strstr($min_grade, "[")) {
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
        if ($course_id = $db->get_course_id($subject_id, $course_num, "", true)) {
          $query = "INSERT INTO draft_group_requirements
                    (`group_id`,`course_id`,
                    `course_min_grade`,`course_repeats`,`data_entry_value`)
                    values (?, ?, ?, ?, ?) ";
          $res2 = db_query($query, $group_id, $course_id, $min_grade, $course_repeats, "$subject_id~$course_num");
          $ccount++;        
        } 
        else {
          // The course_id could not be found!

          fp_add_message(t("Course not found! You specified %course as a requirement in %gname,
                            but this course could not be found in the catalog.  It was removed from
                            the list of requirements.  Are you sure you typed it correctly? Please
                            check your spelling, and add the course again.", array("%course" => "$subject_id $course_num", "%gname" => $group_name)), "error");

        }
      } // if line does not contain &
      else {
        // ELSE, this was a branch!  Insert it as well.

        // This line DOES have an ampersand (&), so this is a sub group
        // within this group.
        // First, we need to request a new branchID for this new group.
        if (!$branch_id = $db->request_new_group_id()) {
          die ("Error.  Could not create new group (branch) ID.");
        } 
        else {
          // Add this branch to the list of requirements for this group.
          $query = "INSERT INTO draft_group_requirements
                      (group_id, child_group_id)
                      values ('?','?') ";
          $res2 = db_query($query, $group_id, $branch_id);
  
        }
        $c_tokes = explode("&",$line);
        for ($cT = 0; $cT < count($c_tokes); $cT++)
        {
          $tokens = explode(" ", trim($c_tokes[$cT]));
          $subject_id = trim($tokens[0]);
          $course_num = trim($tokens[1]);
          $min_grade = @trim($tokens[2]);
          $course_repeats = @trim($tokens[3]);
  
          if (strstr($min_grade, "[")) {
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
          if ($course_id = $db->get_course_id($subject_id, $course_num, "", true)) {
                      
            $query = "INSERT INTO draft_group_requirements
                      (group_id, course_id,
                      course_min_grade, course_repeats, data_entry_value)
                      values ('?','?','?','?', ?) ";
            $res2 = db_query($query, $branch_id, $course_id, $min_grade, $course_repeats, "$subject_id~$course_num");
          } 
          else {
            // The course_id could not be found!
            
            fp_add_message(t("Course not found! You specified %course as a requirement in %gname,
                            but this course could not be found in the catalog.  It was removed from
                            the list of requirements.  Are you sure you typed it correctly? Please
                            check your spelling, and add the course again.", array("%course" => "$subject_id $course_num", "%gname" => $group_name)), "error");
              
          }
  
        } // for $cT
      } // else it does contain &      
      
      
    } // for t < count lines

    fp_add_message(t("%name processed. %count courses added.", array("%name" => $group_name, "%count" => $ccount)) . "<br><br>");    

    

    $c++;
    
  } // while cur = db_fetch_array($res)
  

  // Update our $batch results variables
  $batch["results"]["current"] = $current + $c;
  

  if ($batch["results"]["current"] >= $total) {
    // We are finished!
    $batch["results"]["finished"] = TRUE;
    fp_add_message("<br><br><br>" . t("Group definitions have been processed for %year", array("%year" => $de_catalog_year)));
  }  
    
  
} // admin_process....batch_operation






/**
 * Displays a popup showing where a particular group is being used in FlightPath.
 */
function admin_display_groups_popup_show_group_use() {
  $rtn = "";
  
  $group_id = $_REQUEST["group_id"];
  $group = new Group();
  $group->group_id = $group_id;
  $group->bool_use_draft = true;
  $group->load_descriptive_data();


  $rtn .= "<b>" . t("Degrees using @group_title (%group_name):", array("@group_title"=>$group->title, "%group_name" => $group->group_name)) . "</b>
    <br><br>
    <table border='0' cellspacing='5'>
    <tr>
      <th>" . t("Degree") . "</th>
      <th>" . t("Code") . "</th>
      <th>" . t("Semester") . "</th>
      <th>" . t("Year") . "</th>
    </tr>
    ";

  $res = db_query("SELECT * FROM draft_degrees a,
                    draft_degree_requirements b
                  WHERE a.degree_id = b.degree_id
                  AND b.group_id = '?'
                  ORDER BY a.title, a.major_code, b.semester_num ", $group_id);
  while($cur = db_fetch_array($res))
  {
    extract($cur, 3, "db");
    $rtn .= "<tr>
          <td valign='top' class='tenpt' width='200'>
            $db_title
          </td>
          <td valign='top' class='tenpt' width='100'>
            $db_major_code
          </td>
          <td valign='top' class='tenpt' align='center'>
            " . admin_get_semester_name($db_semester_num) . "
          </td>
          <td valign='top' class='tenpt' width='100'>
            $db_catalog_year
          </td>
          
        </tr>
        ";

  }


  $rtn .= "</table>";  
  
  
  
  return $rtn;
}









