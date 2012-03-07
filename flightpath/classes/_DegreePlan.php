<?php
/*
FlightPath was originally designed and programmed by the 
University of Louisiana at Monroe. The original source is 
copyright (C) 2011-present by the University of Louisiana at Monroe.

FlightPath is considered "open source" under the 
GNU General Public License, version 3 or any later version. 
This covers any related files and documentation packaged with 
FlightPath. 

The license is defined in full here: http://www.gnu.org/licenses/gpl.html,
and reproduced in the LICENSE.txt file.

You may modify FlightPath's source code, but this copyright and license
notice must not be modified, and must be included with the source code.
------------------------------
*/

class _DegreePlan
{
  public $majorCode, $title, $degreeType, $degreeClass, $shortDescription, $longDescription;
  public $listSemesters, $listDegreePlans, $listGroups, $db, $degreeID, $catalogYear;
  public $trackCode, $trackTitle, $trackDescription, $studentArraySignificantCourses;
  public $boolHasTracks, $arraySemesterTitles, $dbExclude;
  public $publicNote;

  public $totalMajorHours, $totalCoreHours, $totalDegreeHours;
  public $fulfilledMajorHours, $fulfilledCoreHours, $fulfilledDegreeHours;

  public $boolUseDraft;

  /**
	* $majorCode		ACCT, CSCI, etc.
	* $title			Accounting, Computer Science, etc.
	* $degreeType		BBA, BS, AS, etc.
	* $shortDescription	These are a text description of this degree plan.  Useful
	*					for descriptions of "Tracks" or "Options." The short
	* $longDescription	one appears in a pull down, the long one is a more
	*					complete text description.  Will probably be unused
	*					by most degrees.
	* $listSemesters	A list of semesters that this DegreePlan requires.
	* $listDegreePlans	If this degree plan has multiple tracks or options, then
	*					they would be spelled out as other degree plans, and listed
	*					here.  For example, Biology has multiple "tracks" which,
	*					internally, should be treated as different degree plans.
	**/


  function __construct($degreeID = "", DatabaseHandler $db = NULL, $boolLoadMinimal = false, $arraySignificantCourses = false, $boolUseDraft = false)
  {
    $this->listSemesters = new ObjList();
    $this->listGroups = new GroupList();
    $this->boolUseDraft = $boolUseDraft;
    // Always override if the global variable is set.
    if ($GLOBALS["boolUseDraft"] == true)
    {
      $this->boolUseDraft = true;
    }


    $this->db = $db;
    if ($db == NULL)
    {
      $this->db = getGlobalDatabaseHandler();
    }

    $this->studentArraySignificantCourses = $arraySignificantCourses;

    if ($degreeID != "")
    {

      $this->degreeID = $degreeID;
      $this->loadDescriptiveData();
      if (!$boolLoadMinimal)
      {
        $this->loadDegreePlan();
      }
      // Add the "Add a Course" semester to the semester list.
      $this->addSemesterCoursesAdded();

    }



  }


  function calculateProgressHours()
  {
    $this->totalMajorHours = $this->getProgressHours("m");
    $this->totalCoreHours = $this->getProgressHours("c");
    $this->totalDegreeHours = $this->getProgressHours("");
    //adminDebug("fulfilled major: ");
    $this->fulfilledMajorHours = $this->getProgressHours("m", false);
    //adminDebug("fulfilled core:  ");
    $this->fulfilledCoreHours = $this->getProgressHours("c", false);
    //adminDebug("fulfilled -degree:  ");
    $this->fulfilledDegreeHours = $this->getProgressHours("", false);
  }


  function getProgressHours($requirementType = "", $boolRequiredHoursOnly = true)
  {
    // Returns the number of hours required (or fulfilled) in a degree plan
    // for courses & groups with the specified requirementType.
    // ex:  "m", "s", etc.  leave blank for ALL required hours.
    // if boolRequiredHours is FALSE, then we will only look for the courses
    // which got fulfilled.

    $hours = 0;

    $this->listSemesters->resetCounter();
    while ($this->listSemesters->hasMore())
    {
      $sem = $this->listSemesters->getNext();
      //adminDebug("taken courses re: $requirementType in " . $sem->semesterNum . ":: $temp");
      if ($boolRequiredHoursOnly == true)
      {
        //adminDebug("regular count hours");
        $hours += $sem->listCourses->countHours($requirementType, true, false);
      } else {
        $temp = $sem->listCourses->countCreditHours($requirementType, true, true);
        $hours += $temp;
      }
    }

    // Also, add in groups matching this requirement type.
    $this->listGroups->resetCounter();
    while ($this->listGroups->hasMore())
    {
      $g = $this->listGroups->getNext();
      if ($g->groupID < 0)
      { // Skip Add a course group.
        continue;
      }

      //print_pre($g->toString());

      $gHours = $g->hoursRequired;
      if ($boolRequiredHoursOnly == false)
      { // only count the fulfilled hours, then.
        $gHours = $g->getFulfilledHours(true, false, true, -1, true);
        //adminDebug("taken group re: $requirementType in " . $g->title . ":: $gHours");
      }

      if ($requirementType == "")
      {
        $hours += $gHours;
        //adminDebug("here $group->title : $gHours rt: $requirementType");
      } else {
        // A requirement is specified, so make sure
        // the group is of this requirement.

        if ($boolRequiredHoursOnly == true)
        {  // make sure it's of the right type.
          $gHours = $g->hoursRequiredByType[$requirementType]*1;
          $hours += $gHours;
          continue;
        }
        
        if ($g->requirementType == $requirementType)
        {
          //if ($requirementType == "m")
          //{
           // adminDebug("Hours required for " . $g->title . ": " . $g->hoursRequiredByType["m"]);
          //}

          $hours += $gHours;
        }
        
        
        
      }
    }


    return $hours;

  }

  function loadDegreePlan()
  {
    // Load this degree plan from the database and fully
    // assemble it.
    $degreeID = $this->degreeID;

    $oldSemester = "";
    $tableName1 = "degrees";
    $tableName2 = "degree_requirements";
    if ($this->boolUseDraft) {
      $tableName1 = "draft_$tableName1";
      $tableName2 = "draft_$tableName2";
    }

    $res = $this->db->dbQuery("SELECT * FROM $tableName1 a, $tableName2 b
            							WHERE a.degree_id = '?'
            							AND a.degree_id = b.degree_id 
            							ORDER BY semester_num ", $this->degreeID);
    while ($cur = $this->db->dbFetchArray($res))
    {
      $this->title = $cur["title"];
      $this->majorCode = $cur["major_code"];
      $this->degreeClass = strtoupper(trim($cur["degree_class"]));

      $semesterNum = $cur["semester_num"];
      if ($semesterNum != $oldSemester)
      {
        // This is a new semester object we are dealing with.
        $oldSemester = $semesterNum;
        $objSemester = new Semester($semesterNum);
        $objSemester->title = trim($this->arraySemesterTitles[$semesterNum]);
        if ($objSemester->title == "") { $objSemester->assignTitle(); }
        $this->listSemesters->add($objSemester);
      }

      if ($cur["course_id"]*1 > 0)
      {
        // A course is the next degree requirement.
        $catYear = "";
        if ($this->boolUseDraft) $catYear = $this->catalogYear;        
        
        $courseC = new Course($cur["course_id"], false, $this->db, false, $catYear, $this->boolUseDraft);
        $courseC->assignedToSemesterNum = $semesterNum;
        $courseC->minGrade = trim(strtoupper($cur["course_min_grade"]));
        if ($courseC->minGrade == "")
        { // By default, all courses have a
          // min grade requirement of D.
          $courseC->minGrade = "D";
        }
        $courseC->requirementType = trim($cur["course_requirement_type"]);

        //adminDebug($courseC->toString() . $courseC->getCatalogHours());
        
        $objSemester->listCourses->add($courseC);

      }

      if ($cur["group_id"]*1 > 0)
      {
        // A group is the next degree requirement.
        //$groupG = new Group($cur["group_id"], $this->db, $semesterNum);

        $title = "";
        $iconFilename = "";
        // Add the real Group (with all the courses, branches, etc)
        // to the DegreePlan's group list!
        // First, see if this group alread exists.  If it does,
        // simply add the number of hours required to it.  If not,
        // create it fresh.
        if ($newGroup = $this->findGroup($cur["group_id"]))
        {
          // Was already there (probably in another semester),
          // so, just increment the required hours.
          $newGroup->hoursRequired = $newGroup->hoursRequired + $cur["group_hours_required"];
          $newGroup->hoursRequiredByType[$cur["group_requirement_type"]] += $cur["group_hours_required"];
          $title = $newGroup->title;
          $iconFilename = $newGroup->iconFilename;
        } else {
          // Was not already there; insert it.
          $groupN = new Group($cur["group_id"], $this->db, $semesterNum, $this->studentArraySignificantCourses, $this->boolUseDraft);
          $groupN->hoursRequired = $cur["group_hours_required"];
          $groupN->hoursRequiredByType[$cur["group_requirement_type"]] += $groupN->hoursRequired;
          if (trim($cur["group_min_grade"]) != "")
          {
            $groupN->assignMinGrade(trim(strtoupper($cur["group_min_grade"])));
          }
          $groupN->requirementType = $cur["group_requirement_type"];
          $title = $groupN->title;
          $iconFilename = $groupN->iconFilename;
          $this->listGroups->add($groupN);
        }


        // Add a placeholder to the Semester....
        $groupG = new Group();
        $groupG->boolUseDraft = $this->boolUseDraft;
        $groupG->groupID = $cur["group_id"];
        $groupG->loadDescriptiveData();
        $groupG->requirementType = $cur["group_requirement_type"];
        if (trim($cur["group_min_grade"]) != "")
        {
          $groupG->assignMinGrade(trim(strtoupper($cur["group_min_grade"])));
        }
        $groupG->assignedToSemesterNum = $semesterNum;
        $groupG->title = "$title";
        $groupG->iconFilename = $iconFilename;
        $groupG->hoursRequired = $cur["group_hours_required"];
        $groupG->boolPlaceholder = true;
        $objSemester->listGroups->add($groupG);


      }




    }



    $this->listGroups->sortPriority();

  }


  function getTitle($boolIncludeTrack = false)
  {
    // This will return the title of this degree, possibly
    // including the track's title as well.

    $rtn = $this->title;
    if ($boolIncludeTrack == true)
    {
      if ($this->trackTitle != "")
      {
        $rtn .= " with " . $this->trackTitle . "";
      }
    }

    return $rtn;

  }


  function getTitle2()
  {
    // This will simply return the degree's title.  If it does not
    // exist, it will try to find another degree with the same majorCode.
    // This is to fix the problem with students with catalog years outside
    // of FlightPath's database, but with major codes that have titles.

    $this->loadDescriptiveData();

    if ($this->title != "")
    {
      return $this->title;
    }


    // Still no title?  Try to load ANY degree title with this degree's
    // majorCode.
    $tableName = "degrees";
    if ($this->boolUseDraft) {$tableName = "draft_$tableName";}

    $res = $this->db->dbQuery("SELECT * FROM $tableName
            								WHERE major_code = '?' 
            								ORDER BY catalog_year DESC LIMIT 1", $this->majorCode);
    $cur = $this->db->dbFetchArray($res);
    $this->title = $cur["title"];

    return $this->title;

  }


  function loadDescriptiveData()
  {
    $tableName = "degrees";
    if ($this->boolUseDraft) {$tableName = "draft_$tableName";}

    $res = $this->db->dbQuery("SELECT * FROM $tableName
								               WHERE degree_id = '?' ", $this->degreeID);

    if ($this->db->dbNumRows($res) > 0)
    {
      $cur = $this->db->dbFetchArray($res);
      $this->majorCode = $cur["major_code"];
      $this->title = $cur["title"];
      $this->publicNote = $cur["public_note"];
      $this->catalogYear = $cur["catalog_year"];
      $this->degreeType = trim($cur["degree_type"]);
      $this->dbExclude = trim($cur["exclude"]);

      // Get the semester titles.
      $temp = trim($cur["semester_titles_csv"]);
      $this->arraySemesterTitles = split(",",$temp);

      if (strstr($this->majorCode, "_"))
      {
        // This means that there is a track.  Get all the information
        // you can about it.
        $temp = split("_", $this->majorCode);
        $this->trackCode = trim($temp[1]);
        $this->majorCode = trim($temp[0]);

        // The majorCode might now have a | at the very end.  If so,
        // get rid of it.
        if (substr($this->majorCode, strlen($this->majorCode)-1, 1) == "|")
        {
          $this->majorCode = str_replace("|","",$this->majorCode);
        }
        // Now, look up information on the track.
        $tableName = "degree_tracks";
        if ($this->boolUseDraft) {$tableName = "draft_$tableName";}

        $res = $this->db->dbQuery("SELECT * FROM $tableName
                								WHERE major_code = '?'
                								AND track_code = '?'
                								AND catalog_year = '?' ", $this->majorCode, $this->trackCode, $this->catalogYear);
        $cur = $this->db->dbFetchArray($res);

        $this->trackTitle = $cur["track_title"];
        $this->trackDescription = $cur["track_description"];

      }

      // Does this major have any tracks at all?  If so, set a bool.
      if ($this->db->getDegreeTracks($this->majorCode, $this->catalogYear))
      {
        $this->boolHasTracks = true;
      }

    }

  }


  function getAdvisedCoursesList()
  {
    // Return a courseList object containing every course
    // in this degreePlan which is marked as boolAdvisedToTake=true.
    $rtnList = new CourseList();

    $this->listSemesters->resetCounter();
    while ($this->listSemesters->hasMore())
    {
      $semester = $this->listSemesters->getNext();
      $rtnList->addList($semester->listCourses->getAdvisedCoursesList());
    }
    $rtnList->addList($this->listGroups->getAdvisedCoursesList());

    return $rtnList;
  }


  /**
   * Returns a simple array with values seperated by " ~~ "
   * in this order: trackCode ~~ trackTitle ~~ trackDesc
   *
   * @return array
   */
  function getAvailableTracks()
  {
    $rtnArray = array();

    $rtnArray[] = "  ~~ None ~~ Select this option to display
						the base degree plan (may not be available for all majors).";
    $tableName = "degree_tracks";
    if ($this->boolUseDraft) {$tableName = "draft_$tableName";}

    $res = $this->db->dbQuery("SELECT * FROM $tableName
              								WHERE major_code = '?'
              								AND catalog_year = '?' 
              								ORDER BY track_title ", $this->majorCode, $this->catalogYear);
    while($cur = $this->db->dbFetchArray($res))
    {

      $trackCode = $cur["track_code"];
      $trackTitle = $cur["track_title"];
      $trackDescription = $cur["track_description"];
      //adminDebug($trackCode);
      $rtnArray[] = "$trackCode ~~ $trackTitle ~~ $trackDescription";
    }

    if (count($rtnArray))
    {
      return $rtnArray;
    } else {
      return false;
    }


  }


  function addSemesterDevelopmental($studentID)
  {
    // This will add the developmental courses in as
    // a semester.  Will check the studentID to see if any
    // developmentals are required.
    // -55 is the developmental semester.
    $sem = new Semester(-55);
    $sem->title = "Developmental Requirements";
    $isEmpty = true;

    $tempArray = $this->db->getDevelopmentalRequirements($studentID);
    // We expect this to give us back an array like:
    // 0 => ART~101
    // 1 => MATH~090
    foreach($tempArray as $tempCourseName) {
      $temp = explode("~", $tempCourseName);
      $c = new Course($this->db->getCourseID($temp[0], $temp[1]));
      $c->minGrade = "C";
      $c->requirementType = "dev";
      $sem->listCourses->add($c);
      
      $isEmpty = false;      
    }
    
    $sem->notice = "According to our records, you are required to
		complete the course(s) listed above. 
		For some transfer students, your record may 
		not be complete. If you have any questions, 
		please ask your advisor. ";

    if (!$isEmpty)
    {
      $this->listSemesters->add($sem);
    }

  }

  function addSemesterCoursesAdded()
  {
    // The "Add a Course" box on screen is really just a
    // semester, with the number -88, with a single group,
    // also numbered -88.
    $semesterCoursesAdded = new Semester(-88);
    $semesterCoursesAdded->title = "Courses Added by Advisor";

    // Now, we want to add the Add a Course group...
    $g = new Group();
    $g->groupID = -88;
    // Since it would take a long time during page load, we will
    // leave this empty of courses for now.  It doesn't matter anyway,
    // as we will not be checking this group for course membership
    // anyway.  We only need to load it in the popup.
    $g->hoursRequired = 99999;  // Nearly infinite selections may be made.
    $g->assignedToSemesterNum = -88;

    $semesterCoursesAdded->listGroups->add($g);

    $this->listSemesters->add($semesterCoursesAdded);

    // Also, add it to the list of groups OUTSIDE of semesters.
    $this->listGroups->add($g);

  }


  

  function findGroup($groupID)
  {
    // Locate the group with groupID in the
    // list of groups, and return it.
    $this->listGroups->resetCounter();
    while($this->listGroups->hasMore())
    {
      $group = $this->listGroups->getNext();
      if ($group->groupID == $groupID)
      {
        return $group;
      }

      if (!$group->listGroups->isEmpty)
      {
        $group->listGroups->resetCounter();
        while($group->listGroups->hasMore())
        {
          $branch = $group->listGroups->getNext();
          if ($branch->groupID == $groupID)
          {
            return $branch;
          }
        }
      }

    }

    return false;
  }



  function findPlaceholderGroup($groupID, $semesterNum)
  {
    // Locate the group within the semesters that matches
    // this groupID and semesterNum.  The assumption here
    // is that no one semester will list the same
    // group twice.  In other words, Core Fine Arts
    // can only have 1 entry for Freshman Year.

    // Create a dummy semester with the correct semesterNum...
    $newSemester = new Semester($semesterNum);
    // Create dummy group as well... don't use the constructor, just
    // set the groupID manually to same time. (no DB calls)
    $newGroup = new Group();
    $newGroup->groupID = $groupID;

    //print_pre($this->listSemesters->toString());
    // Find the semester in the list of semesters with this same semesterNum...
    if (!$semester = $this->listSemesters->findMatch($newSemester))
    {
      // The semester wasn't found!
      return false;
    }


    // Okay, now go through $semester and find the groupID...
    if (!$group = $semester->listGroups->findMatch($newGroup))
    {
      // It wasn't found in the top-level groups.  Look one deeper...
      if (!$semester->listGroups->isEmpty)
      {
        $semester->listGroups->resetCounter();
        while($semester->listGroups->hasMore())
        {
          $group = $semester->listGroups->getNext();
          if ($g = $group->listGroups->findMatch($newGroup))
          {
            //$g->assignToSemester($semesterNum);
            return $g;
          }
        }
      }
    } else {
      // Meaning, we found it!
      //$group->assignToSemester($semesterNum);
      return $group;
    }

    return false;

  }



  function findCourses($courseID, $groupID = 0, $semesterNum)
  {
    // This will locate a course within the degree plan, and return
    // back either that course object, or FALSE.
    $newCourse = new Course($courseID);
    $newSemester = new Semester($semesterNum);
    $rtnCourseList = new CourseList();
    // Okay, if the course is within a group, then
    // we can first use the findGroup method.
    if ($groupID != 0)
    {
      if ($group = $this->findGroup($groupID))
      {

        //adminDebug("Right here, hon.");
        if (!($group->listCourses->isEmpty))
        {
          if ($cL = $group->findCourses($newCourse))
          {
            $rtnCourseList->addList($cL);
            //adminDebug(count($rtnCourseList->arrayList));
          }
        }
        if (!($group->listGroups->isEmpty))
        {
          // Look within each sub group for the course...
          $group->listGroups->resetCounter();
          while($group->listGroups->hasMore())
          {
            $branch = $group->listGroups->getNext();
            if (!$branch->listCourses->isEmpty)
            {
              if ($cL = $branch->findCourses($newCourse))
              {
                $rtnCourseList->addList($cL);
              }
            }
            // Here we can look for groups within groups...

          }
        }
      }

      return $rtnCourseList;

    } else if ($semesterNum != -1) {
      // No group specified.  This course is on the
      // bare degree plan.  We were given a specific semester,
      // so try to find it there...
      if ($semester = $this->listSemesters->findMatch($newSemester))
      {

        if ($cL = $semester->listCourses->findAllMatches($newCourse))
        {
          $rtnCourseList->addList($cL);
          return $rtnCourseList;
        }
      }

    } else if ($semesterNum == -1)
    {
      // Meaning, we do not know which semester it goes in, so
      // attempt all semesters, and return with the first instance.
      $this->listSemesters->resetCounter();
      while($this->listSemesters->hasMore())
      {
        $sem = $this->listSemesters->getNext();
        if ($cL = $sem->listCourses->findAllMatches($newCourse))
        {
          $rtnCourseList->addList($cL);
          return $rtnCourseList;
        }

      }
    }

    return false;


  }


  function toString()
  {
    // Output this degree plan object in a helpful manner.
    $rtn = "";

    $rtn .= "Degree Plan: $this->title ($this->majorCode) \n";
    $rtn .= $this->listSemesters->toString();
    $rtn .= "----------------------------------------- \n";
    $rtn .= "--  ALL GROUPS   \n";
    $rtn .= $this->listGroups->toString();



    return $rtn;
  }

} // end class DegreePlan
?>