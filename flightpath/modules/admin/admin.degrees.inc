<?php



function admin_display_degrees_popup_add_group() {
  $semester_num = trim($_GET["semester_num"]);
  $de_catalog_year = admin_get_de_catalog_year();
  
   fp_add_js(fp_get_module_path("admin") . "/js/admin.js");
  
  $rtn = "";

  $rtn .= "<b>" . t("Add an elective group to semester: @semester_num in @de_catalog_year", array("@semester_num" => $semester_num, "@de_catalog_year" => $de_catalog_year)) . "</b><br>
        <span class='tenpt'>" . t("Use keyboard shortcut CTRL-F to find groups quickly.") . "</span>
        <br><br>
        First, select a group (from $de_catalog_year):
        <div class='tenpt' 
          style='height:200px; overflow-y: scroll; border: 1px solid black;
          margin:5px;'>
        <table border='0' cellspacing='5'>";

  $res = db_query("SELECT * FROM draft_groups
              WHERE catalog_year = '?'
              AND delete_flag = '0'
              ORDER BY title ", $de_catalog_year);
  while($cur = db_fetch_array($res)) {
    extract($cur, 3, "db");
    $rtn .= "<tr><td valign='middle'>
                <input type='radio' name='rgroups' value='$db_group_id'></td>
          <td valign='top' class='tenpt'>
          $db_title<br><i>$db_group_name</i>
          </td>
        </tr>
          
        "; 
  }


  $rtn .= "</table></div>
      " . t("Next, select properties for this group:") . "
      <a href='javascript: popupAlertHelp(\"group_properties\");'>?</a>
      <div class='tenpt' style='padding-top: 5px;'>
      Hrs: <input type='text' name='hours' id='hours' size='2'>
      Min Grade: <select name='min_grade' id='min_grade'>
              <option value=''>--</option>
              <option value='C'>C</option>
              <option value='B'>B</option>
              <option value='A'>A</option>
              
            </select>
      Type: <select name='type' id='type'>";
  // Get our list of requirement types from our settings.
  $types = fp_get_requirement_types();
  foreach ($types as $code => $desc) {
    $rtn .= "<option value='$code'>$code - $desc</option>";
  }
  $rtn .= "
          </select>
        &nbsp; &nbsp;
        " . fp_render_button("Add group &raquo;", "adminPopupAddGroup(\"$semester_num\");") . "
      </div>";


  return $rtn;
}


/**
 * This form lets the user copy a degree and all of it's tracks & concentrations.
 */
function admin_copy_degree_form() {
  $de_catalog_year = admin_get_de_catalog_year();
  
  fp_set_title(t("Copy Degree for") . " $de_catalog_year");
  
  $form = array();

  $form["mark" . $m++] = array(
    "type" => "markup",
    "value" => t("Use this form to duplicate a degree plan in this catalog year.
                  <b>IMPORTANT:</b> If you specify a destination major code which
                  already exists, <b>all of its tracks and concentrations</b> will be
                  deleted for @year!", array("@year" => $de_catalog_year)),
  );


  $form["de_catalog_year"] = array(
    "type" => "hidden",
    "value" => $de_catalog_year,
  );  


  $form["source_major_code"] = array(
    "type" => "textfield",
    "size" => 15,
    "label" => t("Enter the SOURCE major code you wish to copy:"),
    "required" => TRUE,
    "description" => t("Ex: ART or GBUS. Do not enter any track or concentration codes here."),
  );

  $form["include_tracks"] = array(
    "type" => "checkboxes",
    "label" => t("Include tracks and concentrations?"),
    "options" => array("yes" => "Include tracks and concentrations."),
    "description" => t("Check this box if you wish to also copy any tracks and concentrations 
                        this major code may have associated with it. If you do not check this box, 
                        only the base degree will be copied. If the major does not have 
                        tracks and concentrations, leave this unchecked."),
  );

  $form["destination_major_code"] = array(
    "type" => "textfield",
    "size" => 15,    
    "label" => t("Enter the DESTINATION major code you wish to create/overwrite:"),
    "required" => TRUE,
    "description" => t("Ex: CHEM or XYZ. Do not enter any track or concentration codes here.  If the destination
                        major already exists, it and <b>all of its tracks and concentrations</b> will be
                        deleted for this catalog year!"),
  );
  
  
  
  // Our submit button.
  $form["submit"] = array(
    "type" => "submit",
    "value" => "Submit",
    "prefix" => "<hr>",
  );
    
  
  
  return $form;
}



function admin_copy_degree_form_submit($form, $form_submit) {
  //fpm($form_submit);
  
  global $db;
  
  $values = $form_submit["values"];

  $de_catalog_year = $values["de_catalog_year"];  
  
    
  $source_major_code = trim(strtoupper($values["source_major_code"]));
  $destination_major_code = trim(strtoupper($values["destination_major_code"]));
  $include_tracks = $values["include_tracks"]["yes"];
  
  // First thing's first.  Make sure the sourceMajorCode exists.
  $res = db_query("SELECT * FROM draft_degrees 
                    WHERE (major_code = '?'
                    OR major_code LIKE '?|%')
                    AND catalog_year='?' ", $source_major_code, $source_major_code, $de_catalog_year) ;
  if (db_num_rows($res) == 0) {
    // Meaning, it could not be found.
    
    form_error("source_major_code", t("The source major, %source, could not be found for %year", array("%source" => $source_major_code, "%year" => $de_catalog_year)));
    return;
  }

  // Alright, if we got to here, we can proceed.  We need to 
  // delete everything involving the destination major.
  // First, get the degree_id's in a select...
  $res = db_query("SELECT * FROM draft_degrees 
                    WHERE (major_code = '?'
                    OR major_code LIKE '?|%')
                    AND catalog_year='?' ", $destination_major_code, $destination_major_code, $de_catalog_year) ;
  if (db_num_rows($res) > 0) {
    while ($cur = db_fetch_array($res)) {
      $degree_id = $cur["degree_id"];
      $res2 = db_query("DELETE FROM draft_degree_requirements
                           WHERE degree_id='?' ", $degree_id) ;
      
      $res2 = db_query("DELETE FROM draft_degrees
                           WHERE degree_id = '?' ", $degree_id) ;      
    }
    // Now, delete the tracks.
    $res2 = db_query("DELETE FROM draft_degree_tracks
                          WHERE major_code = '?' 
                          AND catalog_year='?' ", $destination_major_code, $de_catalog_year) ;
  }

  // Okay, with the destination major good and deleted, we can proceed with
  // the copy.
  
  // Let's build up an array of all the degrees we will be copying.
  $source_array = array();
  // First, the base degree...
  $res = db_query("SELECT * FROM draft_degrees 
                    WHERE major_code = '?'
                    AND catalog_year='?' ", $source_major_code, $de_catalog_year) ;
  $cur = db_fetch_array($res);
  $source_array[] = $cur;
  
  // Now, any tracks or concentrations?
  if ($include_tracks == "yes") {
    $res = db_query("SELECT * FROM draft_degrees 
                      WHERE major_code LIKE '?|%'
                      AND catalog_year='?' ", $source_major_code, $de_catalog_year) ;
    while ($cur = db_fetch_array($res)) {
      $source_array[] = $cur;
    }

    // While we're here, let's go ahead and make a copy of the tracks.
    $res = db_query("SELECT * FROM draft_degree_tracks
                        WHERE (major_code = '?'
                        OR major_code LIKE '?|%' )
                        AND catalog_year='?' ", $source_major_code, $source_major_code, $de_catalog_year) ;
    while($cur = db_fetch_array($res)) {
      extract($cur, 3, "db");
      $dest_code = $destination_major_code;
      if (strstr($db_major_code, "|")) {
        // We need to adjust the destCode to match
        //the source.
        $dest_code = str_replace("$source_major_code|", "$destination_major_code|", $db_major_code);
      }
      
      $res2 = db_query("INSERT INTO draft_degree_tracks
                          (catalog_year, major_code, track_code, 
                          track_title, track_short_title, track_description)
                          VALUES
                          ('?', '?', '?', '?', '?', '?') ",
                          $de_catalog_year, $dest_code, $db_track_code, 
                          $db_track_title, $db_track_short_title, 
                          $db_track_description) ;
                          
    }       
  }
  
  $db = get_global_database_handler();
  
  //var_dump($source_array);
  // Okay, now it's time to go through the sourceArray
  // and duplicate them.
  foreach ($source_array as $src) {
    extract($src, 3, "src");
    
    $dest_code = $destination_major_code;
    if (strstr($src_major_code, "|")) {
      // We need to adjust the destCode to match
      //the source.
      $dest_code = str_replace("$source_major_code|", "$destination_major_code|", $src_major_code);
    }
    
    //var_dump($dest_code);
    $dest_degree_id = $db->request_new_degree_id();

    // Create the entry in the degrees table.
    $res = db_query("INSERT INTO draft_degrees
                        (degree_id, major_code, degree_type, degree_class, title,
                         public_note, semester_titles_csv,
                         catalog_year, exclude)
                         VALUES   
                        ('?', '?', '?', '?', '?', '?', '?', '?', '?') ",
                         $dest_degree_id, $dest_code, $src_degree_type, $src_degree_class, $src_title,
                         $src_public_note, $src_semester_titles_csv,
                         $de_catalog_year, $src_exclude);
    
    // Now, go through the source's degree requirements and copy those over.
    $res = db_query("SELECT * FROM draft_degree_requirements
                         WHERE degree_id = '$src_degree_id' ");
    while ($cur = db_fetch_array($res)) {
      extract($cur, 3, "db");
      
      $res2 = $db->db_query("INSERT INTO draft_degree_requirements
                          (degree_id, semester_num, group_id,
                           group_requirement_type,
                           group_hours_required,
                           group_min_grade, course_id,
                           course_min_grade,
                           course_requirement_type,
                           data_entry_value)
                           VALUES
                          ('?', '?', '?', '?', '?',
                           '?', '?',
                           '?',
                           '?',
                           '?') ",
                            $dest_degree_id, $db_semester_num, $db_group_id,
                           $db_group_requirement_type,
                           $db_group_hours_required,
                           $db_group_min_grade, $db_course_id,
                           $db_course_min_grade,
                           $db_course_requirement_type,
                           $db_data_entry_value);
                           
    }  
    
  }
  
  // Make a - entry into the draft_instruction table so it will
  // remind the administrator to apply draft changes.
  $res = db_query("INSERT INTO draft_instructions
                        (instruction) VALUES ('-') ");
  
  fp_add_message(t("Degree %source has been copied to %dest for %year",
                  array("%source" => $source_major_code, "%dest" => $destination_major_code, "%year" => $de_catalog_year)));
                   
                    
}



/**
 * This form lets the user add a degree to the database. 
 */
function admin_add_degree_form() {  
  $de_catalog_year = admin_get_de_catalog_year();
  
  fp_set_title(t("Add Degree for") . " " . $de_catalog_year);
  
  $form = array();
  
  $form["markup" . $m++] = array(
    "type" => "markup",
    "value" => t("You may use this screen to add a new degree,
                  by entering a new major, concentration, or track (degree option)"),
  );
  

  $form["de_catalog_year"] = array(
    "type" => "hidden",
    "value" => $de_catalog_year,
  );  

  $form["new_major"] = array(
    "type" => "radios",
    "label" => t("Please select: are you entering a new major code [and concentration] 
                  or an existing major code [and concentration] (so you can add a new track)?"),
    "options" => array(
      "new" => t("Entering a <b>new</b> major code [and concentration]"),
      "existing" => t("Entering an <b>existing</b> major code [and concentration] (only adding a new track)"),
    ),
    "required" => TRUE,
  );

  
  $form["major_code"] = array(
    "type" => "textfield",
    "size" => 15,
    "label" => t("Major|Conc code:"),
    "description" => t("To enter a concentration code, use MAJOR|CONC. 
                      The | character is call the pipe, and it should under the backspace key. 
                      If adding a new concentration to an existing major, you still put this in as a 
                      NEW major code. Do not have any spaces in this box. The concentration code 
                      is optional. If the major does not have a concentration, 
                      then simply enter the major code by itself."),
    "required" => TRUE,
  );


  $form["new_track"] = array(
    "type" => "radios",
    "label" => t("Please select: are you entering a new track code?"),
    "options" => array(
      "new" => t("Entering a <b>new</b> track code"),
      "none" => t("None - Not adding a track.  Leave blank"),
    ),
    "required" => TRUE,
  );


  $form["track_code"] = array(
    "type" => "textfield",
    "size" => 15,
    "label" => t("Track code:"),
    "description" => t("Leave blank if you selected None above."),
  );



  // Our submit button.
  $form["submit"] = array(
    "type" => "submit",
    "value" => "Submit",
    "prefix" => "<hr>",
  );
  
  
  return $form;
   
}


/**
 * Submit handler for the add_degree_form.
 */
function admin_add_degree_form_submit($form, $form_submit) {
  $values = $form_submit["values"];

  $de_catalog_year = $values["de_catalog_year"];
  // This will be used to add a new degree (and possibly track)
  // to the database.
  $major_code = trim(strtoupper($values["major_code"]));
  $track_code = trim(strtoupper($values["track_code"]));

  $new_major = $values["new_major"];
  $new_track = $values["new_track"];

  //////////////////////////////////////////////

  if ($new_track == "new" && $track_code == "") {   
    form_error("track_code", t("You selected to add a track, but did not specify a track code.")); 
  }

  // Make sure user did not enter an underscore (_) in either
  // the track or major code!
  if (strstr($track_code, "_") || strstr($major_code, "_")) {
        
    form_error("major_code", t("You are not allowed to enter underscores (_) in either the track code
                    or major code.  FlightPath will add that for you.  Please re-enter
                    your new degree without using an underscore.")); 

  }

  // Return since we have errors all ready.
  if (form_has_errors()) {
    return;
  }


  ////////////////////////////////////////////////////




  // First, deal with the major/concentration.
  // Firstly, check to see if it already exists...
  $res = db_query("SELECT * FROM draft_degrees
          WHERE catalog_year = '?'
          AND major_code = '?' ", $de_catalog_year, $major_code);
  if (db_num_rows($res) > 0 && $new_major == "new") { 
    // Meaning, it already exists, yet we are trying to add it as a new
    // major.  This is an error!
    fp_add_message(t("The major code %major_code already exists for %year.  You cannot add it as a new major.", array("%major_code" => $major_code, "%year" => $de_catalog_year)), "error");
    return;
    
  }
  if (db_num_rows($res) == 0 && $new_major == "existing") { 
    // This is another error.  We are trying to add a track to an existing
    // major code, but none was found.
    
    fp_add_message(t("The major code %major_code could not be found in the system for %year.  Perhaps you need to add it first?", array("%major_code" => $major_code, "%year" => $de_catalog_year)), "error");    
    return;
  }
  if (db_num_rows($res) == 0 && $new_major == "new") {
    // This means we are trying to add a new major to the degrees table.
    // We may proceed with this.
    $db2 = new DatabaseHandler();
    $degree_id = $db2->request_new_degree_id();
    $db2->db_query("INSERT INTO draft_degrees
            (degree_id, major_code, catalog_year)
            values ('?', '?', '?') ", $degree_id, $major_code, $de_catalog_year);
  }


  if ($new_track == "new") {
    //////////////////////////////////////////////////
    // Now, let's see about adding ourself a track...
    // First, check to see if it exists...
    $res = db_query("SELECT * FROM draft_degree_tracks
          WHERE catalog_year = '?'
          AND major_code = '?' 
          AND track_code = '?' ", $de_catalog_year, $major_code, $track_code);  

    if (db_num_rows($res) > 0) {
      // Meaning, it already existed, so we can't create it.      
      fp_add_message(t("The major and track code %major_code already exists for %year.  You cannot add it as a new major/track code.", array("%major_code" => "$major_code $track_code", "%year" => $de_catalog_year)), "error");
      return;      
      
    } 
    else {
      // We can add it to the tracks table...
      $db2 = new DatabaseHandler();
      $db2->db_query("INSERT INTO draft_degree_tracks
              (catalog_year, major_code, track_code)
              values ('?', '?', '?') ", $de_catalog_year, $major_code, $track_code);

      // Now, we also need to add this major & track code to the degrees table.
      $new_major_code = $major_code;
      if (strstr($major_code, "|")) {
        // Already has a pipe, so it has a concentration.
        $new_major_code .= "_$track_code";
      } 
      else {
        // No concentration...
        $new_major_code .= "|_$track_code";
      }

      $degree_id = $db2->request_new_degree_id();
      $db2->db_query("INSERT INTO draft_degrees
            (degree_id, major_code, catalog_year)
            values ('?', '?', '?') ", $degree_id, $new_major_code, $de_catalog_year);

    }

  }


  // Success!  We are done.
  fp_add_message(t("The new degree %major_code was added successfully for %year.  
                      You may add another degree, or use the menu at the top of the page to return to your list
                      of degrees, so you may begin editing the degree.", array("%major_code" => "$major_code $track_code", "%year" => $de_catalog_year)));
  
  clear_session_form_values("admin_add_degree_form");
  
}


/*
 * This function lists all of our degrees on the screen, for a given year
 */
function admin_display_degrees() {
  $de_catalog_year = admin_get_de_catalog_year();
  $rtn = "";    
    
  fp_add_css(fp_get_module_path("admin") . "/css/admin.css");

  $rtn .= "<h2 class='title'>" . t("Degrees for @year", array("@year" => $de_catalog_year)) . "</h2>";  

  $rtn .= "<div class='admin-degrees-upper-links'>";  
  $rtn .= l("Add new degree plan (major, degree plan, or track)", "admin/degrees/add-degree", "de_catalog_year=$de_catalog_year");
  $rtn .= " &nbsp; &nbsp; | &nbsp; &nbsp; ";
  $rtn .= l("Copy a degree plan", "admin/degrees/copy-degree", "de_catalog_year=$de_catalog_year");
  $rtn .= "</div>";
  
  
  $rtn .= "<div class='list-degree-row-excluded'>
        " . t("Majors or Tracks marked as \"exclude\" are shaded.  You may pull up students with these
        majors in FlightPath, but they will not be options in the What If mode.") . "
      </div>
      <br>
      <div class='tenpt' align='center'>
        " . t("Use CTRL-F to find degrees more quickly.") . "
      </div>";
  

  $res = db_query("SELECT * FROM draft_degrees
              WHERE catalog_year = '?'
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
    $temp = explode("|", $db_major_code);
    $just_major = trim($temp[0]);
    $just_conc = trim($temp[1]);
    $outside = "";

    //if ($just_conc != "" && strstr($just_conc, "_"))
    if (strstr($just_conc, "_")) {      
      // If the concentration has an underscore, it's actually
      // a track.  Let's get the track title...
      $temp2 = explode("_",$just_conc);
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


function admin_handle_edit_degree_submit() {

  $de_catalog_year = $_REQUEST["de_catalog_year"];
  $db = get_global_database_handler();

  // What courses should have their type overwritten as "x"?
  $ignore_courses = csv_to_array(variable_get("ignore_courses_from_hour_counts", ""));

  
  // This will UPDATE a degree in the system with the courses
  // and groups that the user selected.
  $perform_action2 = trim($_POST["perform_action2"]);

  if (strstr($perform_action2, "delGroup")) {
    $temp = explode("_",$perform_action2);
    $del_group = new Group();
    $del_group->bool_use_draft = true;
    $del_group->group_id = $temp[1];
    $del_group->assigned_to_semester_num = $temp[2];

  }

  $major_code = trim($_POST["major_code"]);
  if ($major_code == ""){ die("Fatal error:  major_code not found.");}

  // Since we are making a change to the draft table(s), let's add a row
  // to draft instructions.
  $db->add_draft_instruction("-");


  $degree_id = "";
  // First things first, if this degree already exists in this
  // catalog year, then we need to delete it first.
  if ($degree_id = $db->get_degree_id($major_code, $de_catalog_year, true)) {
    $degree = new DegreePlan($degree_id, null, false, false, true);
    $degree->load_descriptive_data();

    // Delete from degree_requirements WHERE this degree_id exists.
    $res = db_query("DELETE FROM draft_degree_requirements
                  WHERE degree_id = '?' ", $degree_id);

    // Are we trying to DELETE this degree?  If so, keep deleting!
    if ($perform_action2 == "delete_degree") {
      $res = db_query("DELETE FROM draft_degrees
                  WHERE  degree_id = '?' ", $degree_id);

      // Also need to get rid of the track, if there is one for this
      // degree.
      $res = db_query("DELETE FROM draft_degree_tracks
                  WHERE major_code = '$degree->major_code' 
                  AND track_code = '$degree->track_code'
                  AND catalog_year = '?' LIMIT 1", $de_catalog_year);

      // Okay, we have deleted everything.  We need to go back to
      // just the list of degrees.
      //display_edit_degrees("<font color='green'><b>The degree $major_code ($de_catalog_year) has been deleted.</b></font>");
      //die;
      
      fp_add_message("The degree $major_code ($de_catalog_year) has been deleted.");
      fp_goto("admin/degrees", "de_catalog_year=$de_catalog_year");
      return; 
      
    }


  } 
  else {
    // We need to generate a new degreeID for this major_code and catalog_year,
    // because one does not already exist!
    if (!$degree_id = $db->request_new_degree_id()) {
      die ("Error.  Could not create new degreeID.");
    }
  }

  // Save the entire post to the log.

  $errors = "";
  $semester_titles_c_s_v = "";
  $highest_semester_num = 0;   // What is the largest semester_num in the system?
  // Okay, now get the various courses...
  for ($semester_num = 0; $semester_num < 50; $semester_num++)
  {
    // Assuming no more than 50 semesters.
    $courses = trim($_POST["courses_$semester_num"]);
    if ($courses == "") {
      continue;
    }

    if ($semester_num > $highest_semester_num) {
      $highest_semester_num = $semester_num;
    }

    $course_rows = explode("\n",$courses);
    for ($t = 0; $t < count($course_rows); $t++) {
      $line = trim($course_rows[$t]);
      if ($line == "") {
        continue;
      }
      // Take out extra whitespace between tokens.
      $line = str_replace("   ", " ", $line);
      $line = str_replace("  ", " ", $line);
      $line = str_replace("  ", " ", $line);
      $line = str_replace("  ", " ", $line);

      $tokens = explode(" ", $line);
      $subject_id = $tokens[0];
      $course_num = $tokens[1];
      $requirement_type = strtolower($tokens[2]);

      if ($requirement_type == "") { 
        // major type by default.
        $requirement_type = "m";
      }

      // If this course is in our ignore list, override it's type
      // to be 'x'
      if (in_array("$subject_id $course_num", $ignore_courses)) {
        $requirement_type = "x";
      }
      
      
      $min_grade = strtoupper($tokens[3]);

      if (strstr($requirement_type, "(")) {
        // This means there was no requirement_type specified, so it's "m",
        // and a min_grade was found in its place.
        $min_grade = strtoupper($requirement_type);
        $requirement_type = "m";
      }

      $min_grade = str_replace("(","",$min_grade);
      $min_grade = str_replace(")","",$min_grade);

      /////////////////////////////////////////////
      // Okay, we now have enough information to insert the course.
      // Find out what the course_id is.
      if ($course_id = $db->get_course_id($subject_id, $course_num, "", true))  // don't care about catalog year.
      {
        $query = "INSERT INTO draft_degree_requirements
                    (degree_id, semester_num, course_id, course_min_grade, course_requirement_type, data_entry_value)
                    values ('?','?','?','?','?','?~?') ";
        $res = db_query($query, $degree_id, $semester_num, $course_id, $min_grade, $requirement_type, $subject_id, $course_num);
        //debug_c_t($query);
      } else {
        // The course_id could not be found!
        $errors .= "<br><font color='red'><b>Course Not Found!</b>
              In Block " . ($semester_num+1) . ", you specified the course
              <b>$subject_id $course_num</b> as a requirement, but this course
              could not be found.
              It was removed from that block.
              Are you sure you typed it correctly?  Please go to this
              semester, check your spelling, and add the course again.</font>";

      }

    }


  }

  // Get the groups....
  foreach($_POST as $key => $value) {
    if (!strstr($key, "group_")) {
      continue;
    }
    // Only look at the groups...
    $temp = explode("_", $value);
    $group_id = $temp[0];
    $semester_num = $temp[1];
    $hours = $temp[2];
    $type = $temp[3];
    $min_grade = trim($temp[4]);

    if ($semester_num > $highest_semester_num) {
      $highest_semester_num = $semester_num;
    }


    // Do not add if we are supposed to be deleting this group!
    if (is_object($del_group)) {
      if ($del_group->group_id == $group_id && $del_group->assigned_to_semester_num == $semester_num) {
        continue;
      }
    }

    // We now have enough information to insert this group.
    //debugCT("group: $group_id $semester_num $hours $type");
    $query = "INSERT INTO draft_degree_requirements
                    (`degree_id`,`semester_num`,`group_id`,
                    `group_requirement_type`,`group_hours_required`,`group_min_grade`)
                    values ('?','?','?',
                    '?','?','?') ";
    $res = db_query($query, $degree_id, $semester_num, $group_id, $type, $hours, $min_grade);


  }


  // Was there a group added or deleted?
  if (strstr($perform_action2,"addGroup")) {
    $temp = explode("_",$perform_action2);
    $group_id = $temp[1];
    $semester_num = $temp[2];
    $hours = trim($temp[3]);
    $type = $temp[4];
    $min_grade = trim($temp[5]);
    $query = "INSERT INTO draft_degree_requirements
                    (`degree_id`,`semester_num`,`group_id`,
                    `group_requirement_type`,`group_hours_required`,`group_min_grade`)
                    VALUES ('?','?','?','?','?','?') ";
    $res = db_query($query, $degree_id, $semester_num, $group_id, $type, $hours, $min_grade);

  }


  // Make the semesterTitlesCSV...
  for ($semester_num = 0; $semester_num <= $highest_semester_num; $semester_num++) {
    $semester_titles_csv .= trim($_POST["semester_title_$semester_num"]) . ",";
  }

  // Before we UPDATE, also grab the degree title, degree_type,
  // and exclude value, etc....
  $degree_title = trim($_POST["title"]);
  $degree_type = trim($_POST["degree_type"]);
  $degree_class = strtoupper(trim($_POST["degree_class"]));
  $exclude = trim($_POST["exclude"]);
  $public_note = trim($_POST["public_note"]);
  $res = db_query("UPDATE draft_degrees
              SET `semester_titles_csv`='?',
              `title`='?',
              `degree_type`='?',
              `degree_class`='?',
              `exclude`='?',
              `public_note`='?'
              WHERE `degree_id`='?' ",
              $semester_titles_csv, $degree_title, $degree_type, $degree_class, $exclude, $public_note, $degree_id);

  ////  Was there a track title/description?  If so, UPDATE that in the tracks
  // table...
  if (strstr($major_code, "_"))  {
    // There was a track. Update track description.
    $temp = explode("_",$major_code);
    $major = trim($temp[0]);
    // major might now have a | at the end.  If so, take it out.
    if (substr($major, strlen($major)-1, 1) == "|") {
      $major = str_replace("|","",$major);
    }


    $track = trim($temp[1]);
    $track_description = trim($_POST["track_description"]);
    $track_title = trim($_POST["track_title"]);
    //debugCT($track_description);
    $res = db_query("UPDATE draft_degree_tracks
                SET `track_description`='?',
                `track_title`='?'
                WHERE `track_code`='?'
                AND `major_code`='?' 
                AND `catalog_year`='?' ", $track_description, $track_title, $track, $major, $de_catalog_year);
  }




  //$msg = "<font color='green' size='4'>Degree updated successfully at " . get_current_time() . ".</font>";
  fp_add_message("Degree updated succesfully.");
  $bool_scroll = $bool_button_msg = true;
  if ($errors != "")
  {
    fp_add_message("ERROR(s): $errors");
    $bool_scroll = $bool_button_msg = false;
  }


  fp_goto("admin/degrees/edit-degree", "de_catalog_year=$de_catalog_year&major_code=$major_code&scroll_top=" . $_REQUEST["scroll_top"] . "&button_msg=" . urlencode("Degree updated successfully at " . date("H:i:s")));
  return;    
    
}


/**
 * This screen displays the form which allows the user to actually
 * edit a degree.
 */
function admin_display_edit_degree() {
  $de_catalog_year = admin_get_de_catalog_year();
  $rtn = "";
  
  $major_code = $_REQUEST["major_code"];

  // Add in our CSS and JS
  fp_add_js(fp_get_module_path("admin") . "/js/admin.js");
  fp_add_css(fp_get_module_path("admin") . "/css/admin.css");

  $button_msg = trim(addslashes($_REQUEST["button_msg"]));  
  
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

  $rtn .= "<form id='mainform' action='" . base_path() . "/admin/degrees/handle-edit-degree-submit' method='POST'>
            <input type='hidden' name='perform_action2' id='perform_action2'>
            <input type='hidden' name='de_catalog_year' value='$de_catalog_year'>
            <input type='hidden' name='scroll_top' id='scroll_top' value=''>
            <input type='hidden' name='major_code' value='$major_code'>                        
            ";

  $rtn .= "<div style='font-size: 16pt; font-weight:bold; padding-top: 20px;'>$degree->degree_type $degree->title<br>$major_code ($de_catalog_year)</div>";
  $rtn .= "
      <table>
       <tr>
        <td valign='top' class='tenpt' width='15%'>" . t("Degree Type:") . "</td>
        <td valign='top' class='tenpt' width='15%'><input type='text' name='degree_type' value='$degree->degree_type' size='5' maxlength='20'></td>

        <td valign='top' class='tenpt' width='15%'>" . t("Degree Class:") . "</td>
        <td valign='top' class='tenpt'><input type='text' name='degree_class' value='$degree->degree_class' size='2' maxlength='1'>
        <a href='javascript: adminPopupAlertHelp(\"degree_class\");'>?</a>
        
        </td>
       </tr>
        
       </tr>
       <tr>
        <td valign='top' class='tenpt'>" . t("Degree Title:") . "</td>
        <td valign='top' class='tenpt' colspan='3'><input type='text' name='title' value='$degree->title' size='80' maxlength='100'></td>
       </tr>
       <tr>
        <td valign='top' class='tenpt'>" . t("Exclude:") . "</td>
        <td valign='top' class='tenpt' colspan='3'><input type='text' name='exclude' value='$degree->db_exclude' size='2' maxlength='1'>
        <a href='javascript: adminPopupAlertHelp(\"degree_exclude\");'>?</a></td>
       </tr>
       
       
        
      </table> ";

  if (strstr($major_code, "_"))
  {
    $rtn .= "<b>Edit track information:</b>
      <blockquote style='margin-top: 0px; margin-bottom: 0px;'>
    <font size='2'>Track title: <input type='text' name='track_title' value='$degree->track_title' size='60' maxlength='100'></font><br>
    <font size='2'>Track description: " . fp_get_js_alert_link(t("You may enter simple HTML in this field, like <b>bold</b> or <i>italics</i>."), t("(Help - Formatting)")) . "
            <a href='javascript: adminPopupAlertHelp(\"track_description\");'>(Help - Track Descriptions)</a>
    </font><br>
    <textarea name='track_description' cols='60' rows='3'>" . filter_markup($degree->track_description) . "</textarea>
    </blockquote>
    ";
  }
  $rtn .= "<div class='tenpt' align='center'>(Scroll to the bottom of the page for more options)</div>";

  $degree->list_semesters->reset_counter();
  while ($degree->list_semesters->has_more()) {
    $semester = $degree->list_semesters->get_next();
    if ($semester->semester_num < 0) {
      continue;
    }
    $sem_default_title = admin_get_semester_name($semester->semester_num);
    if ($semester->title == $sem_default_title) {
      $semester->title = "";
    }

    $rtn .= "<div class='elevenpt' style='padding-bottom: 30px;'>
          <b>Block number: " . ($semester->semester_num +1) . "</b>
          &nbsp; &nbsp; &nbsp; &nbsp;
          Default title: $sem_default_title
          &nbsp; &nbsp;
          Override: <input type='text' name='semester_title_$semester->semester_num' value='$semester->title' size='20'>
          <a href='javascript: adminPopupAlertHelp(\"semester_title\");'>?</a>
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
    <div class='tenpt'>(<a href='javascript: adminPopupAlertHelp(\"degree_entry\");'>Help - entering requirements, min grades, and repeats</a>)</div>
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
      
            <a href='javascript: adminDelGroup(\"$group->group_id\",\"$semester->semester_num\");'><img src='" . fp_theme_location() . "/images/delete.png' border='0'></a>
                  
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
          <a href='javascript: adminPopupWindow(\"" . base_path() . "/admin/degrees/popup-add-group&semester_num=$semester->semester_num&de_catalog_year=$de_catalog_year\");'>Add an elective group</a>
        </div>
    </td>";


    $rtn .= "</table><br>
        
    " . fp_render_button(t("Save for @year", array("@year" => $de_catalog_year)), "adminSubmitForm();") . "
      <span class='admin-button-msg'>$button_msg</span>
    
    </div>
    ";
    $s_count = $semester->semester_num+1;
  }

  // Always add an additional 4 semesters to the bottom.
  for ($t = 0; $t < 4; $t++) {
    $sem = $t + $s_count;
    if ($sem > 99) {
      // Max number of semesters.  More or less arbitrarily set number.
      $rtn .= "<br>" . t("Maximum number of semesters created.") . "<br>";
      break;
    }

    $rtn .= "<div class='elevenpt' style='padding-bottom: 30px;'>
          <b>Block number: " . ($sem+1) . "</b>
          &nbsp; &nbsp; &nbsp; &nbsp;
          Default title: " . admin_get_semester_name($sem) . "
          &nbsp; &nbsp;
          Override: <input type='text' name='semester_title_$sem' value='' size='20'>
          <a href='javascript: adminPopupAlertHelp(\"semester_title\");'>?</a>
          
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
          <a href='javascript: adminPopupWindow(\"" . base_path() . "/admin/degrees/popup-add-group&semester_num=$sem&de_catalog_year=$de_catalog_year\");'>Add an elective group</a>
    </td>";


    $rtn .= "</table><br>
    
    " . fp_render_button(t("Save for @year", array("@year" => $de_catalog_year)), "adminSubmitForm();") . "
        <span class='admin-button-msg'>$button_msg</span>
    
    </div>";

  }
  $rtn .= "<div class='elevenpt'>If you need more semester boxes, simply save this page, and additional blank
      boxes will appear below.</div>
      
      <br><br>
      <div class='elevenpt'><b>More Options:</b><br>
      Enter a public note for this degree: 
       <a href='javascript: adminPopupAlertHelp(\"public_note\");'>(Help - Public Note)</a>
       " . fp_get_js_alert_link(t("You may enter simple HTML in this field, like <b>bold</b> or <i>italics</i>."), t("(Help - Formatting)")) . "
      <br>
      <textarea name='public_note' rows='4' cols='80'>$degree->public_note</textarea>
      
      
      </div>
      
      
      
      "; 




  $rtn .= "</form>";

  $rtn .= "        <div align='right'>
          Delete this degree? <input type='button' value='X'
                  onClick='adminDeleteDegree(\"$degree_id\");'>
        </div>      
        ";


  //$pC .= get_j_s();


  if ($_REQUEST["serialize"] != "")
  {
    print "<br><textarea rows=20 cols=80>" . serialize($degree) . "</textarea>";
  }  
  
  
  return $rtn;
}

