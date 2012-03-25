<?php

/* This include file deals with Groups editing for FlightPath */




function admin_edit_group_form_submit($form, $form_submit) {
  $values = $form_submit["values"];
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

  // Save the entire post to the log.
  //$post_x_m_l = fp_array_to_xml("post",$_POST, true);
  //$db->add_to_log("admin_edit_group","$group_id,$group_name",$post_x_m_l);

  // Since we are making a change to the draft table(s), let's add a row
  // to draft instructions.
  $db->add_draft_instruction("-");


  // Are we trying to delete this group?
  if ($_POST["perform_action2"] == "delete_group") {
    $res = db_query("UPDATE draft_groups
                SET delete_flag = '1'
                WHERE group_id = '?' 
                AND catalog_year = '?'
                ", $group_id, $de_catalog_year);
    fp_add_message(t("The group %title (%name) has been deleted successfully for %year", array("%title" => $title, "%name" => $group_name, "%year" => $de_catalog_year)));
    fp_goto("admin/groups", "de_catalog_year=$de_catalog_year");
    return;
  }

  // If the $group_id == new then create a new one.
  if ($group_id == "new") {
    $group_id = $db->request_new_group_id();
    $res = db_query("INSERT INTO draft_groups(group_id, catalog_year)
                values ('?','?') ", $group_id, $de_catalog_year);
    $values["group_id"] = $group_id;
  }

  $res = db_query("UPDATE draft_groups
              SET group_name = '?',
              title = '?',
              priority = '?',
              icon_filename = '?',
              data_entry_comment = '?'
              WHERE
                group_id = '?' ", 
              $group_name, $title, $priority, $icon_filename, $data_entry_comment, $group_id);

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
      $res = $db->db_query("UPDATE draft_groups
              SET definition = ''
              WHERE
                group_id = '?' ", $group_id);    
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
        $min_grade = trim($tokens[2]);
        $course_repeats = trim($tokens[3]);

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
                    values ('?','?','?','?','?~?') ";
          $res = db_query($query, $branch_id, $course_id, $min_grade, $course_repeats, $subject_id, $course_num);
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
      $subject_id = trim($tokens[0]);
      $course_num = trim($tokens[1]);
      $min_grade = trim($tokens[2]);
      $course_repeats = trim($tokens[3]);

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
                    values ('?','?','?','?','?~?') ";
        $res = db_query($query, $group_id, $course_id, $min_grade, $course_repeats, $subject_id, $course_num);
        
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
  

}



/**
 * This function lets the user edit a group.
 */
function admin_edit_group_form() {
  $form = array();
  
  $de_catalog_year = admin_get_de_catalog_year();
  $group_id = $_REQUEST["group_id"];
  
  
  // Make sure this file is reachable for the submit handler.
  $form["form_include"] = menu_get_module_path("admin") . "/admin.groups.inc";  
  $form["redirect"] = array(
    "path" => "admin/groups/edit-group",
    "query" => "group_id=$group_id&de_catalog_year=$de_catalog_year",
  );
  
  
  if (user_has_permission("can_view_advanced")) {
    $form["mark" . $m++] = array(
      "type" => "markup",
      "value" => " <span class='tenpt' style='background-color: yellow; margin-left: 20px;'>
          adv: group_id = $group_id.
          Used by:
          <a href='javascript: popupWindow(\"admin.php?performAction=popup_show_group_use&group_id=$group_id\");'>[degrees]</a>
          </span>",
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
    "value" => "",
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
    "popup_description" => t("This is what FlightPath uses to refer to this group in screens and popups.
                        Ex: Free Electives, Art Electives, Core Humanities, etc."),
  );

  $form["group_name"] = array(
    "type" => "textfield",
    "label" => t("Internal group name:"),
    "value" => $group->group_name,
    "maxlength" => 100,
    "popup_description" => t("The \"group's name\" is internal to FlightPath, and is never seen by the average user.
                        You may use this to distinguish between groups with the same title. Ex:
                        major_electives_sr and major_electives_jr.  The field may be considered
                        optional, but it is highly recommended you enter something here for your
                        own clarity later on.  It is OK to have the same group title and name."),
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
  );


  // TODO:  Make this a popup or pull down or something.
  $form["icon_filename"] = array(
    "type" => "textfield",
    "label" => t("Icon:"),
    "value" => $group->icon_filename,
    "description" => t("This should be a popup situation here"),
  );

    
  $form["definition"] = array(
    "type" => "markup",
    "label" => t("Definition:"),
    "value" => "<label>" . t("Definition:") . "</label><i>" . nl2br($group->definition) . "</i>",
    "prefix" => "<div style='overflow: auto; max-height: 150px;' class='admin-groups-show-definition'>",
    "suffix" => "</div><a href='l'>[" . t("edit definition") . "]</a>",
  );
 

  $form["mark" . $m++] = array(
    "type" => "markup",
    "value" => "<hr>",
  );

  
  $courses = admin_get_group_courses($group);
  $form["courses"] = array(
    "type" => "textarea",
    "label" => t("Required Courses:"),
    "rows" => 17,
    "cols" => 80,
    "value" => $courses,
  );
  // if a definition was specified, we need to disable the courses textarea.
  if (trim($group->definition)) {
    $form["courses"]["attributes"] = "readonly=readonly style='background-color: #ccc;' ";
    $form["courses"]["prefix"] = "<div class='admin-groups-courses-disabled'>
                                 " . t("Note: Because a definition was specified, you cannot directly
                                      edit the Required COurses.  Manage these courses using the
                                      Edit Definition popup window.") . "</div>";
                                 
  }
  


  $form["data_entry_comment"] = array(
    "type" => "textarea",
    "label" => t("Optional Comment: (only seen by other FlightPath administrators)"),
    "rows" => 3,
    "cols" => 80,
    "value" => $group->data_entry_comment,
  );
 
  
  $form["submit"] = array(
    "type" => "submit",
    "value" => "Submit",
    "prefix" => "<hr>",
  );
  
  $form["mark" . $m++] = array(
    "type" => "markup",
    "value" => "<div align='right'>
                      Delete this group? <input type='button' value='X'
                      onClick='deleteGroup(\"$group_id\");'>
                    </div>", 
  );
 
 
  
  return $form;
}



/**
 * Return back the courses in a group, suitable for the edit-group form.
 */
function admin_get_group_courses(Group $group) {
  
  
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




/**
 * This function will display a list of all our groups.
 */
function admin_display_groups() {
  $rtn = "";
  
  $de_catalog_year = admin_get_de_catalog_year();
  
  fp_add_css(fp_get_module_path("admin") . "/css/admin.css");
  
  $db2 = new DatabaseHandler();

  fp_set_title(t("Edit Groups for") . " $de_catalog_year");

  $rtn .= "
      <h2 class='title'>" . t("Edit Groups for %de_catalog_year", array("%de_catalog_year" => $de_catalog_year)) . "</h2>
        Options:
        <ul style='margin-top: 5px;'>
          <li><a href='admin.php?performAction=editSpecificGroup&group_id=new&de_catalog_year=$de_catalog_year'>Add a new group to this year</a><br>
          </li>
          <li><a href='javascript: processDefinitions($de_catalog_year);'>Process all group definitions for this year</a>
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
            <img src='$screen->theme_location/images/icons/$db_icon_filename' width='19'>
          </td>
          <td valign='top' class='tenpt'>
            $use_count <a href='javascript: popupWindow(\"admin.php?performAction=popup_show_group_use&group_id=$db_group_id\");'><img src='$screen->theme_location/images/popup.gif' border='0'></a>
          </td>

          
        </tr>"; 
  }

  $rtn .= "</table>";
  
  
  
  
  return $rtn;
}