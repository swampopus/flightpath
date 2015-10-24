<?php


class _DegreePlan
{
  public $major_code, $title, $degree_type, $degree_level, $degree_class, $short_description, $long_description;
  public $list_semesters, $list_degree_plans, $list_groups, $db, $degree_id, $catalog_year;
  public $track_code, $track_title, $track_description, $student_array_significant_courses;
  public $bool_has_tracks, $array_semester_titles, $db_exclude, $db_allow_dynamic;
  public $public_note;

  public $total_major_hours, $total_core_hours, $total_degree_hours;
  public $fulfilled_major_hours, $fulfilled_core_hours, $fulfilled_degree_hours;
  public $major_qpts_hours, $core_qpts_hours, $degree_qpts_hours;
  public $major_qpts, $degree_qpts, $core_qpts;

  public $gpa_calculations;
  
  
  public $bool_use_draft;

  /**
	* $major_code		ACCT, CSCI, etc.
	* $title			Accounting, Computer Science, etc.
	* $degree_type		BBA, BS, AS, etc.
	* $short_description	These are a text description of this degree plan.  Useful
	*					for descriptions of "Tracks" or "Options." The short
	* $long_description	one appears in a pull down, the long one is a more
	*					complete text description.  Will probably be unused
	*					by most degrees.
	* $list_semesters	A list of semesters that this DegreePlan requires.
	* $list_degree_plans	If this degree plan has multiple tracks or options, then
	*					they would be spelled out as other degree plans, and listed
	*					here.  For example, Biology has multiple "tracks" which,
	*					internally, should be treated as different degree plans.
	**/


  function __construct($degree_id = "", DatabaseHandler $db = NULL, $bool_load_minimal = false, $array_significant_courses = false, $bool_use_draft = false)
  {
    $this->list_semesters = new ObjList();
    $this->list_groups = new GroupList();
    $this->bool_use_draft = $bool_use_draft;
    // Always override if the global variable is set.
    if ($GLOBALS["fp_advising"]["bool_use_draft"] == true) {
      $this->bool_use_draft = true;
    }


    $this->db = $db;
    if ($db == NULL)
    {
      $this->db = get_global_database_handler();
    }

    $this->student_array_significant_courses = $array_significant_courses;

    if ($degree_id != "")
    {

      $this->degree_id = $degree_id;
      $this->load_descriptive_data();
      if (!$bool_load_minimal)
      {
        $this->load_degree_plan();
      }
      // Add the "Add a Course" semester to the semester list.
      $this->add_semester_courses_added();

    }



  }


  function calculate_progress_hours()
  {
    
    // Let's go through our requirement types by code, and collect calcuations on them
    // in the gpa_calculations array.
    $types = fp_get_requirement_types();
    // Add a pseudo-code in for "degree", which the functions will convert into a blank.
    $types["degree"] = "Degree (total)";
    foreach ($types as $code => $desc) {
      // Make sure to skip appropriate codes we don't care about.
      if ($code == 'x') continue;      
      
      
      $this->gpa_calculations[$code]["total_hours"] = $this->get_progress_hours($code);
      $this->gpa_calculations[$code]["fulfilled_hours"] = $this->get_progress_hours($code, FALSE);
      $this->gpa_calculations[$code]["qpts_hours"] = $this->get_progress_hours($code, FALSE, TRUE);      
      
      
      // Get ONLY local hours, too....
      $this->gpa_calculations[$code . "_local"]["total_hours"] = $this->get_progress_hours($code, TRUE, FALSE, TRUE);
      $this->gpa_calculations[$code . "_local"]["fulfilled_hours"] = $this->get_progress_hours($code, FALSE, FALSE, TRUE);
      $this->gpa_calculations[$code . "_local"]["qpts_hours"] = $this->get_progress_hours($code, FALSE, TRUE, TRUE);     
      
      
    }
    
    
    
     
  }

  /**
   * Calculate the quality points of our completed courses, so we can use
   * that to figure out GPA.
   *
   */
  function calculate_progress_quality_points() {      
    
    // Let's go through our requirement types by code, and collect calcuations on them
    // in the gpa_calculations array.
    $types = fp_get_requirement_types();
    // Add a pseudo-code in for "degree", which the functions will convert into a blank.
    $types["degree"] = "Degree (total)";
    foreach ($types as $code => $desc) {
      // Make sure to skip appropriate codes we don't care about.
      if ($code == 'x') continue;
      
      $this->gpa_calculations[$code]["qpts"] = $this->get_progress_quality_points($code);
      
      // Get only local courses, too...
      $this->gpa_calculations[$code . "_local"]["qpts"] = $this->get_progress_quality_points($code, TRUE);
    }
        
    
    /*
    $this->major_qpts = $this->get_progress_quality_points("m");
    $this->core_qpts = $this->get_progress_quality_points("c");
    $this->degree_qpts = $this->get_progress_quality_points("");
    */  
    
  }
  

  function get_progress_hours($requirement_type = "", $bool_required_hours_only = TRUE, $bool_qpts_grades_only = FALSE, $bool_exclude_all_transfer_credits = FALSE)
  {
    // Returns the number of hours required (or fulfilled) in a degree plan
    // for courses & groups with the specified requirement_type.
    // ex:  "m", "s", etc.  leave blank for ALL required hours.
    // if boolRequiredHours is FALSE, then we will only look for the courses
    // which got fulfilled.
    
   if ($requirement_type == "degree") $requirement_type = "";

    
    
    $hours = 0;
    
    $this->list_semesters->reset_counter();
    while ($this->list_semesters->has_more())
    {
      $sem = $this->list_semesters->get_next();

      if ($bool_required_hours_only == TRUE)
      {
        $hours += $sem->list_courses->count_hours($requirement_type, true, false, FALSE);  // do not exclude transfer credits, since this is for required hours only.
      } else {
        $temp = $sem->list_courses->count_credit_hours($requirement_type, true, true, $bool_qpts_grades_only, $bool_exclude_all_transfer_credits);
                
        $hours += $temp;
      }
    }                       

    
 
    
    // Also, add in groups matching this requirement type.
    $this->list_groups->reset_counter();
    while ($this->list_groups->has_more())
    {
      $g = $this->list_groups->get_next();
      if ($g->group_id < 0)                           
      { // Skip Add a course group.
        continue;
      }

      // Make sure the group doesn't have a type of 'x' assigned to it, which means we should
      // skip it.
      if ($g->requirement_type == 'x') continue;
      

      $g_hours = $g->hours_required;
      if ($bool_required_hours_only == false)
      { // only count the fulfilled hours, then.        
        $g_hours = $g->get_fulfilled_hours(true, false, true, -1, true, $bool_qpts_grades_only, $requirement_type, $bool_exclude_all_transfer_credits);        
            
      }

      if ($requirement_type == "")
      {
        $hours += $g_hours;
      } else {
        // A requirement is specified, so make sure
        // the group is of this requirement.

        if ($bool_required_hours_only == true)
        {  // make sure it's of the right type.
          $g_hours = $g->hours_required_by_type[$requirement_type]*1;
          $hours += $g_hours;
          continue;
        }
        
        if ($g->requirement_type == $requirement_type)
        {            
          $hours += $g_hours;
        }
        
      
        
        
      }
    }
    
    return $hours;

  }
  
  
  /**
   * Similar to get_progress_hours, this will return back the quality points a student has earned
   * towards this degree.  It can then be used to calculate GPA.
   *
   * @param unknown_type $requirement_type
   * @param unknown_type $bool_required_hours_only
   * @return unknown
   */
  function get_progress_quality_points($requirement_type = "", $bool_exclude_all_transfer_credits = FALSE) {
    // Returns the number of hours required (or fulfilled) in a degree plan
    // for courses & groups with the specified requirement_type.
    // ex:  "m", "s", etc.  leave blank for ALL required hours.
    // if boolRequiredHours is FALSE, then we will only look for the courses
    // which got fulfilled.

    if ($requirement_type == "degree") $requirement_type = "";
    
    $points = 0;

    $this->list_semesters->reset_counter();
    while ($this->list_semesters->has_more())
    {
      $sem = $this->list_semesters->get_next();

      $p = $sem->list_courses->count_credit_quality_points($requirement_type, true, true, $bool_exclude_all_transfer_credits);
      $points = $points + $p;
      
    }

    
    // Also, add in groups matching this requirement type.
    $this->list_groups->reset_counter();
    while ($this->list_groups->has_more())
    {
      $g = $this->list_groups->get_next();
      if ($g->group_id < 0)
      { // Skip Add a course group.
        continue;
      }
      
      // Make sure the group doesn't have a type of 'x' assigned to it, which means we should
      // skip it.
      if ($g->requirement_type == 'x') continue;
      
      $g_points = $g->get_fulfilled_quality_points(TRUE, -1, TRUE, TRUE, $requirement_type, $bool_exclude_all_transfer_credits);
      $points = $points + $g_points;       

      
    }
    

    
    return $points;

  }  
  
  
  
  
  

  function load_degree_plan()
  {
    // Load this degree plan from the database and fully
    // assemble it.
    $degree_id = $this->degree_id;

    $old_semester = "";
    $table_name1 = "degrees";
    $table_name2 = "degree_requirements";
    if ($this->bool_use_draft) {
      $table_name1 = "draft_$table_name1";
      $table_name2 = "draft_$table_name2";
    }

    $res = $this->db->db_query("SELECT * FROM $table_name1 a, $table_name2 b
            							WHERE a.degree_id = '?'
            							AND a.degree_id = b.degree_id 
            							ORDER BY semester_num ", $this->degree_id);
    while ($cur = $this->db->db_fetch_array($res))
    {
      $this->title = $cur["title"];
      $this->major_code = $cur["major_code"];
      $this->degree_level = strtoupper(trim($cur["degree_level"]));
      $this->degree_class = $cur["degree_class"];

      $semester_num = $cur["semester_num"];
      if ($semester_num != $old_semester)
      {
        // This is a new semester object we are dealing with.
        $old_semester = $semester_num;
        $obj_semester = new Semester($semester_num);
        $obj_semester->title = trim($this->array_semester_titles[$semester_num]);
        if ($obj_semester->title == "") { $obj_semester->assign_title(); }
        $this->list_semesters->add($obj_semester);
      }

      if ($cur["course_id"]*1 > 0)
      {
        // A course is the next degree requirement.
        
        //if ($this->bool_use_draft) $cat_year = $this->catalog_year;        
        $cat_year = $this->catalog_year;        
        
        $course_c = new Course($cur["course_id"], false, $this->db, false, $cat_year, $this->bool_use_draft);
        $course_c->assigned_to_semester_num = $semester_num;
        $course_c->min_grade = trim(strtoupper($cur["course_min_grade"]));
        if ($course_c->min_grade == "")
        { // By default, all courses have a
          // min grade requirement of D.
          $course_c->min_grade = "D";
        }
        $course_c->requirement_type = trim($cur["course_requirement_type"]);

        //adminDebug($course_c->to_string() . $course_c->getCatalogHours());
        
        $obj_semester->list_courses->add($course_c);

      }

      if ($cur["group_id"]*1 > 0)
      {
        // A group is the next degree requirement.
        //$group_g = new Group($cur["group_id"], $this->db, $semester_num);

        $title = "";
        $icon_filename = "";
        // Add the real Group (with all the courses, branches, etc)
        // to the DegreePlan's group list!
        // First, see if this group alread exists.  If it does,
        // simply add the number of hours required to it.  If not,
        // create it fresh.
        if ($new_group = $this->find_group($cur["group_id"]))
        {
          // Was already there (probably in another semester),
          // so, just increment the required hours.
          $new_group->hours_required = $new_group->hours_required + ($cur["group_hours_required"] * 1);
          $new_group->hours_required_by_type[$cur["group_requirement_type"]] += ($cur["group_hours_required"] * 1);
          $title = $new_group->title;
          $icon_filename = $new_group->icon_filename;
        } else {
          // Was not already there; insert it.
          $group_n = new Group($cur["group_id"], $this->db, $semester_num, $this->student_array_significant_courses, $this->bool_use_draft, $cur["group_requirement_type"]);
          $group_n->hours_required = $cur["group_hours_required"] * 1;
          $group_n->hours_required_by_type[$cur["group_requirement_type"]] += $group_n->hours_required;
          if (trim($cur["group_min_grade"]) != "")
          {
            $group_n->assign_min_grade(trim(strtoupper($cur["group_min_grade"])));
          }
          $title = $group_n->title;
          $icon_filename = $group_n->icon_filename;
          $this->list_groups->add($group_n);
        }


        // Add a placeholder to the Semester....
        $group_g = new Group();
        $group_g->bool_use_draft = $this->bool_use_draft;
        $group_g->group_id = $cur["group_id"];
        $group_g->load_descriptive_data();
        $group_g->requirement_type = $cur["group_requirement_type"];
        if (trim($cur["group_min_grade"]) != "")
        {
          $group_g->assign_min_grade(trim(strtoupper($cur["group_min_grade"])));
        }
        $group_g->assigned_to_semester_num = $semester_num;
        $group_g->title = "$title";
        $group_g->icon_filename = $icon_filename;
        $group_g->hours_required = $cur["group_hours_required"] * 1;
        $group_g->bool_placeholder = true;
        $obj_semester->list_groups->add($group_g);


      }




    }



    $this->list_groups->sort_priority();

  }


  function get_title($bool_include_track = false)
  {
    // This will return the title of this degree, possibly
    // including the track's title as well.

    $rtn = $this->title;
    if ($bool_include_track == true)
    {
      if ($this->track_title != "")
      {
        $rtn .= " with " . $this->track_title . "";
      }
    }

    return $rtn;

  }


  function get_title2()
  {
    // This will simply return the degree's title.  If it does not
    // exist, it will try to find another degree with the same major_code.
    // This is to fix the problem with students with catalog years outside
    // of FlightPath's database, but with major codes that have titles.

    $this->load_descriptive_data();

    if ($this->title != "")
    {
      return $this->title;
    }


    // Still no title?  Try to load ANY degree title with this degree's
    // major_code.
    $table_name = "degrees";
    if ($this->bool_use_draft) {$table_name = "draft_$table_name";}

    $res = $this->db->db_query("SELECT * FROM $table_name
            								WHERE major_code = '?' 
            								ORDER BY catalog_year DESC LIMIT 1", $this->major_code);
    $cur = $this->db->db_fetch_array($res);
    $this->title = $cur["title"];

    return $this->title;

  }


  function load_descriptive_data()
  {
    $table_name = "degrees";
    if ($this->bool_use_draft) {$table_name = "draft_$table_name";}

    $res = $this->db->db_query("SELECT * FROM $table_name
								               WHERE degree_id = '?' ", $this->degree_id);

    if ($this->db->db_num_rows($res) > 0)
    {
      $cur = $this->db->db_fetch_array($res);
      $this->major_code = $cur["major_code"];
      $this->title = $cur["title"];
      $this->public_note = $cur["public_note"];
      $this->catalog_year = $cur["catalog_year"];
      $this->degree_type = trim($cur["degree_type"]);
      $this->db_exclude = trim($cur["exclude"]);
      $this->db_allow_dynamic = trim($cur["allow_dynamic"]);

      // Get the semester titles.
      $temp = trim($cur["semester_titles_csv"]);
      $this->array_semester_titles = explode(",",$temp);

      if (strstr($this->major_code, "_"))
      {
        // This means that there is a track.  Get all the information
        // you can about it.
        $temp = explode("_", $this->major_code);
        $this->track_code = trim($temp[1]);
        $this->major_code = trim($temp[0]);

        // The major_code might now have a | at the very end.  If so,
        // get rid of it.
        if (substr($this->major_code, strlen($this->major_code)-1, 1) == "|")
        {
          $this->major_code = str_replace("|","",$this->major_code);
        }
        // Now, look up information on the track.
        $table_name = "degree_tracks";
        if ($this->bool_use_draft) {$table_name = "draft_$table_name";}

        $res = $this->db->db_query("SELECT * FROM $table_name
                								WHERE major_code = '?'
                								AND track_code = '?'
                								AND catalog_year = '?' ", $this->major_code, $this->track_code, $this->catalog_year);
        $cur = $this->db->db_fetch_array($res);

        $this->track_title = $cur["track_title"];
        $this->track_description = $cur["track_description"];

      }

      // Does this major have any tracks at all?  If so, set a bool.
      if ($this->db->get_degree_tracks($this->major_code, $this->catalog_year))
      {
        $this->bool_has_tracks = true;
      }

    }

  }


  function get_advised_courses_list()
  {
    // Return a courseList object containing every course
    // in this degreePlan which is marked as boolAdvisedToTake=true.
    $rtn_list = new CourseList();

    $this->list_semesters->reset_counter();
    while ($this->list_semesters->has_more())
    {
      $semester = $this->list_semesters->get_next();
      $rtn_list->add_list($semester->list_courses->get_advised_courses_list());
    }
    $rtn_list->add_list($this->list_groups->get_advised_courses_list());

    return $rtn_list;
  }


  /**
   * Returns a simple array with values seperated by " ~~ "
   * in this order: track_code ~~ track_title ~~ trackDesc
   *
   * @return array
   */
  function get_available_tracks()
  {
    $rtn_array = array();

    $rtn_array[] = "  ~~ None ~~ Select this option to display
						the base degree plan (may not be available for all majors).";
    $table_name = "degree_tracks";
    if ($this->bool_use_draft) {$table_name = "draft_$table_name";}

    $res = $this->db->db_query("SELECT * FROM $table_name
              								WHERE major_code = '?'
              								AND catalog_year = '?' 
              								ORDER BY track_title ", $this->major_code, $this->catalog_year);
    while($cur = $this->db->db_fetch_array($res))
    {

      $track_code = $cur["track_code"];
      $track_title = $cur["track_title"];
      $track_description = $cur["track_description"];
      //adminDebug($track_code);
      $rtn_array[] = "$track_code ~~ $track_title ~~ $track_description";
    }

    if (count($rtn_array))
    {
      return $rtn_array;
    } else {
      return false;
    }


  }


  function add_semester_developmental($student_id)
  {
    // This will add the developmental courses in as
    // a semester.  Will check the studentID to see if any
    // developmentals are required.
    // -55 is the developmental semester.
    $sem = new Semester(-55);
    $sem->title = variable_get("developmentals_title", t("Developmental Requirements"));
    $is_empty = true;

    $temp_array = $this->db->get_developmental_requirements($student_id);
    // We expect this to give us back an array like:
    // 0 => ART~101
    // 1 => MATH~090
    foreach($temp_array as $temp_course_name) {
      $temp = explode("~", $temp_course_name);
      $c = new Course($this->db->get_course_id($temp[0], $temp[1]));
      $c->min_grade = "C";
      $c->requirement_type = "dev";
      $sem->list_courses->add($c);
      
      $is_empty = false;      
    }
    
    $sem->notice = variable_get("developmentals_notice", t("According to our records, you are required to complete the course(s) listed above. For some transfer students, your record may not be complete. If you have any questions, please ask your advisor."));

    if (!$is_empty)
    {
      $this->list_semesters->add($sem);
    }

  }

  function add_semester_courses_added()
  {
    // The "Add a Course" box on screen is really just a
    // semester, with the number -88, with a single group,
    // also numbered -88.
    $semester_courses_added = new Semester(-88);
    $semester_courses_added->title = t("Courses Added by Advisor");

    // Now, we want to add the Add a Course group...
    $g = new Group();
    $g->group_id = -88;
    // Since it would take a long time during page load, we will
    // leave this empty of courses for now.  It doesn't matter anyway,
    // as we will not be checking this group for course membership
    // anyway.  We only need to load it in the popup.
    $g->hours_required = 99999;  // Nearly infinite selections may be made.
    $g->assigned_to_semester_num = -88;

    $semester_courses_added->list_groups->add($g);

    $this->list_semesters->add($semester_courses_added);

    // Also, add it to the list of groups OUTSIDE of semesters.
    $this->list_groups->add($g);

  }


  

  function find_group($group_id)
  {
    // Locate the group with group_id in the
    // list of groups, and return it.
    $this->list_groups->reset_counter();
    while($this->list_groups->has_more())
    {
      $group = $this->list_groups->get_next();
      if ($group->group_id == $group_id)
      {
        return $group;
      }

      if (!$group->list_groups->is_empty)
      {
        $group->list_groups->reset_counter();
        while($group->list_groups->has_more())
        {
          $branch = $group->list_groups->get_next();
          if ($branch->group_id == $group_id)
          {
            return $branch;
          }
        }
      }

    }

    return false;
  }



  function find_placeholder_group($group_id, $semester_num)
  {
    // Locate the group within the semesters that matches
    // this group_id and semesterNum.  The assumption here
    // is that no one semester will list the same
    // group twice.  In other words, Core Fine Arts
    // can only have 1 entry for Freshman Year.

    // Create a dummy semester with the correct semesterNum...
    $new_semester = new Semester($semester_num);
    // Create dummy group as well... don't use the constructor, just
    // set the group_id manually to same time. (no DB calls)
    $new_group = new Group();
    $new_group->group_id = $group_id;

    //print_pre($this->list_semesters->to_string());
    // Find the semester in the list of semesters with this same semesterNum...
    if (!$semester = $this->list_semesters->find_match($new_semester))
    {
      // The semester wasn't found!
      return false;
    }


    // Okay, now go through $semester and find the group_id...
    if (!$group = $semester->list_groups->find_match($new_group))
    {
      // It wasn't found in the top-level groups.  Look one deeper...
      if (!$semester->list_groups->is_empty)
      {
        $semester->list_groups->reset_counter();
        while($semester->list_groups->has_more())
        {
          $group = $semester->list_groups->get_next();
          if ($g = $group->list_groups->find_match($new_group))
          {
            //$g->assign_to_semester($semester_num);
            return $g;
          }
        }
      }
    } else {
      // Meaning, we found it!
      //$group->assign_to_semester($semester_num);
      return $group;
    }

    return false;

  }



  function find_courses($course_id, $group_id = 0, $semester_num)
  {
    // This will locate a course within the degree plan, and return
    // back either that course object, or FALSE.
    $new_course = new Course($course_id);
    $new_semester = new Semester($semester_num);
    $rtn_course_list = new CourseList();
    // Okay, if the course is within a group, then
    // we can first use the find_group method.
    if ($group_id != 0)
    {
      if ($group = $this->find_group($group_id))
      {

        if (!($group->list_courses->is_empty))
        {
          if ($cL = $group->find_courses($new_course))
          {

            $rtn_course_list->add_list($cL);

          }
        }
        if (!($group->list_groups->is_empty))
        {
          // Look within each sub group for the course...
          $group->list_groups->reset_counter();
          while($group->list_groups->has_more())
          {
            $branch = $group->list_groups->get_next();
            if (!$branch->list_courses->is_empty)
            {
              if ($cL = $branch->find_courses($new_course))
              {
                $rtn_course_list->add_list($cL);
              }
            }
            // Here we can look for groups within groups...

          }
        }
      }

      return $rtn_course_list;

    } else if ($semester_num != -1) {
      // No group specified.  This course is on the
      // bare degree plan.  We were given a specific semester,
      // so try to find it there...
      if ($semester = $this->list_semesters->find_match($new_semester))
      {

        if ($cL = $semester->list_courses->find_all_matches($new_course))
        {
          $rtn_course_list->add_list($cL);
          return $rtn_course_list;
        }
      }

    } else if ($semester_num == -1)
    {
      // Meaning, we do not know which semester it goes in, so
      // attempt all semesters, and return with the first instance.
      $this->list_semesters->reset_counter();
      while($this->list_semesters->has_more())
      {
        $sem = $this->list_semesters->get_next();
        if ($cL = $sem->list_courses->find_all_matches($new_course))
        {
          $rtn_course_list->add_list($cL);
          return $rtn_course_list;
        }

      }
    }

    return false;


  }


  function to_string()
  {
    // Output this degree plan object in a helpful manner.
    $rtn = "";

    $rtn .= "Degree Plan: $this->title ($this->major_code) \n";
    $rtn .= $this->list_semesters->to_string();
    $rtn .= "----------------------------------------- \n";
    $rtn .= "--  ALL GROUPS   \n";
    $rtn .= $this->list_groups->to_string();



    return $rtn;
  }

} // end class DegreePlan