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


/*
Class definition for the Course object.
*/

class _Course
{
  // Some public variables and what they are used for.

  // Database & misc related:
  public $randomID, $dbAdvisedCoursesID;
  public $boolPlaceholder, $db, $dbSubstitutionID, $dbUnassignTransferID;
  public $dbExclude, $dataEntryComment, $arrayIndex, $dataEntryValue;
  public $dbGroupRequirementID;  // the id from the group_requirements table where this was specified.

  // Course catalog data related:
  public $subjectID, $courseNum, $courseID, $requirementType, $catalogYear;
  public $minHours, $maxHours, $listPrereqs, $repeatHours;
  public $arrayValidNames;

  // Student record related:
  public $boolTaken, $termID, $sectionNumber, $grade, $hoursAwarded, $qualityPoints;
  public $boolTransfer, $institutionID, $institutionName, $courseTransfer;
  public $transferEqvText, $transferFootnote, $boolOutdatedSub;
  public $boolSubstitution, $courseSubstitution, $substitutionHours, $subRemarks, $subFacultyID;
  public $boolSubstitutionSplit, $substitutionFootnote, $boolSubstitutionNewFromSplit;

  // Major or Group Requirement related:
  public $minGrade, $specifiedRepeats, $boolSpecifiedRepeat, $requiredOnBranchID;
  public $assignedToGroupID, $assignedToSemesterNum, $boolExcludeRepeat;

  // advising & in-system logic related:
  public $advisedHours, $boolSelected, $boolAdvisedToTake;
  public $courseListFulfilledBy; //$courseFulfilledBy,
  public $boolHasBeenAssigned, $boolAddedCourse, $groupListUnassigned;
  public $advisedTermID, $tempOldCourseID;
  public $boolUseDraft;
  //public $boolHasBeenAssignedToBareDegreePlan;

  // Display related:
  public $displayStatus, $iconFilename, $description, $title;
  public $titleText, $tempFlag, $boolHasBeenDisplayed;
  public $boolUnselectable;
  public $boolHideGrade, $boolGhostHour, $boolGhostMinHour;

  


/**
 * The constructor for a Course object.
 *
 * @param int $courseID
 *        - Numeric courseID of the course to try to load.  Leave blank
 *          if you simply wish to instantiate a course object.
 * 
 * @param bool $isTransfer
 *        - Is this course a transfer course?  Meaning, from another
 *          school.
 * 
 * @param DatabaseHandler $db
 * @param bool $isBlank
 * @param int $catalogYear
 *        - What catalogYear does this Course belong to?  This is
 *          used later when we call loadDescriptiveData() to get its 
 *          description, hour count, etc.
 * 
 * @param bool $boolUseDraft
 */
  function __construct($courseID = "", $isTransfer = false, DatabaseHandler $db = NULL, $isBlank = false, $catalogYear = "", $boolUseDraft = false)
  {
    
    $this->advisedHours = -1;
        
    if ($isBlank == true)
    { // Do nothing if this is a "blank" course.
      return;
    }

    $arrayValidNames = array();  // will hold all "valid" names for this course (non excluded names).
    $this->courseID = $courseID*1;  // Force it to be numeric.
    $this->tempOldCourseID = 0;  // Used in case we delete the courseID, we can get it back (good with substitutions of transfers that are outdated).
    $this->catalogYear = $catalogYear;
    $this->assignedToSemesterNum = -1;
    $this->assignedToGroupID = 0;
    $this->boolAdvisedToTake = false;
    $this->boolAddedCourse = false;
    $this->specifiedRepeats = 0;
    $this->boolExcludeRepeat = false;
    $this->boolSpecifiedRepeat = false;
    $this->randomID = rand(1,9999);
    $this->displayStatus = "eligible";
    $this->courseListFulfilledBy = new CourseList();
    $this->groupListUnassigned = new ObjList();
    $this->boolUseDraft = $boolUseDraft;

    // Always override if the global variable is set.
    if ($GLOBALS["boolUseDraft"] == true)
    {
      $this->boolUseDraft = true;
    }

    $this->db = $db;
    if ($db == NULL)
    {
      $this->db = getGlobalDatabaseHandler();;
      if (!is_object($this->db))
      {
        $this->db = new DatabaseHandler();
      }
    }

    if ($courseID != "")
    {
      $this->loadCourse($courseID, $isTransfer);
    }
  }


  /**
   * This function will create a "data string" of the course.
   * Think of it as a poor man's serialize.  I can't actually use
   * serialize, as I have to call this for every course on the screen,
   * and the page load time was too long when using serialize, probably
   * because of all the extra fields which I did not need.
   * 
   * The string returned will be used to send information about this
   * course to a popup window.
   * 
   * Important details about the course are put into a particular order,
   * separated by commas.  Booleans are converted to either 1 or 0.
   * 
   * This function is the mirror of loadCourseFromDataString().
   *
   * @return string
   */
  function toDataString()
  {
    $rtn = "";

    $rtn .= $this->courseID . ",";
    $rtn .= $this->assignedToSemesterNum . ",";
    $rtn .= $this->assignedToGroupID . ",";
    $rtn .= intval($this->boolAdvisedToTake) . ",";
    $rtn .= $this->specifiedRepeats . ",";
    $rtn .= intval($this->boolSpecifiedRepeat) . ",";
    $rtn .= $this->grade . ",";
    $rtn .= $this->hoursAwarded . ",";
    $rtn .= $this->termID . ",";
    $rtn .= $this->advisedHours . ",";

    $rtn .= intval($this->boolTransfer) . ",";

    // If this is a transfer, then we will put in various information
    // about the original transfer course...
    if ($this->boolTransfer == true)
    {
      $rtn .= $this->courseTransfer->courseID . ",";
    } else {
      // Just enter blank.
      $rtn .= ",";
    }

    $rtn .= intval($this->boolAddedCourse) . ",";
    $rtn .= $this->dbAdvisedCoursesID . ",";
    $rtn .= $this->randomID . ",";

    $rtn .= intval($this->boolSubstitution) . ",";
    // If this is a substitution, what is the original requirement?
    if ($this->boolSubstitution == true)
    {
      $rtn .= $this->courseSubstitution->courseID . ",";
    } else {
      // Just enter blank.
      $rtn .= ",";
    }

    $rtn .= $this->dbSubstitutionID . ",";
    $rtn .= $this->minHours . ",";
    $rtn .= $this->maxHours . ",";

    $rtn .= intval($this->boolSubstitutionNewFromSplit) . ",";
    $rtn .= intval($this->boolSubstitutionSplit) . ",";
    $rtn .= intval($this->boolHasBeenAssigned) . ",";

    $rtn .= $this->displayStatus . ",";
    
    $rtn .= intval($this->boolGhostHour) . ",";




    return $rtn;
  }

  /**
   * This will take a data string, as created by
   * the function toDataString(), and make $this object
   * match the original object.  It is a poor man's
   * unserialize.  See toDataString()'s description for a fuller
   * picture of what is going on.
   * 
   * To use:  
   *  - $newCourse = new Course();
   *  - $newCourse->loadCourseFromDataString($data);
   *          
   *
   * @param string $str
   */
  function loadCourseFromDataString($str)
  {
    
    $temp = split(",",$str);

    $this->courseID = 				$temp[0];

    $this->loadCourse($this->courseID);

    $this->assignedToSemesterNum = 	$temp[1];
    $this->assignedToGroupID 	= 	$temp[2];
    $this->boolAdvisedToTake 	= 		(bool) $temp[3];
    $this->specifiedRepeats   	= 	$temp[4];
    $this->boolSpecifiedRepeat 	= 	(bool) $temp[5];
    $this->grade   				= 	$temp[6];
    $this->hoursAwarded			= 	$temp[7];
    $this->termID				= 	$temp[8];
    $this->advisedHours			=	$temp[9];

    $this->boolTransfer 		= 	(bool) $temp[10];

    // Was this a transfer course?
    if ($this->boolTransfer == true)
    {
      $tCourse = new Course($temp[11], true);
      $tCourse->termID = $this->termID;
      $this->courseTransfer = $tCourse;
    }

    $this->boolAddedCourse 		= 	(bool) $temp[12];
    $this->dbAdvisedCoursesID	= 	$temp[13];
    $this->randomID				= 	$temp[14];

    $this->boolSubstitution		= 	(bool) $temp[15];

    // Was this a substitution course?
    if ($this->boolSubstitution == true)
    {
      $tCourse = new Course($temp[16]); // original course requirement.
      $this->courseSubstitution = $tCourse;
    }

    $this->dbSubstitutionID		= 	$temp[17];
    $this->minHours				= 	$temp[18];
    $this->maxHours				= 	$temp[19];

    $this->boolSubstitutionNewFromSplit	= 	(bool) $temp[20];
    $this->boolSubstitutionSplit	= 	(bool) $temp[21];
    $this->boolHasBeenAssigned	= 	(bool) $temp[22];

    $this->displayStatus	= 	$temp[23];

    $this->boolGhostHour	= 	(bool) $temp[24];
    


  }


  /**
   * This function will return a CSV string of all the possible
   * names for this course, in alphabetical order.
   * 
   * This function is used by DataEntry primarily.
   *
   * @param bool $boolAddWhiteSpace
   * @param bool $boolAddExclude
   * @return string
   */
  function getAllNames($boolAddWhiteSpace = false, $boolAddExclude = true)
  {
    $rtn = "";

    $usedArray = array();

    $tableName = "courses";
    if ($this->boolUseDraft) {$tableName = "draft_$tableName";}
    // took out: and `catalog_year`='$this->catalogYear'
    // because we don't care what catalog year it comes from...
    $res = $this->db->dbQuery("SELECT * FROM $tableName
						WHERE course_id = '?'
						AND delete_flag = '0' 
						ORDER BY subject_id, course_num ", $this->courseID);
    while($cur = $this->db->dbFetchArray($res))
    {

      if (in_array($cur["subject_id"] . "~" . $cur["course_num"], $usedArray))
      { // skip ones we have already seen.
        continue;
      }

      $usedArray[] = $cur["subject_id"] . "~" . $cur["course_num"];

      $rtn .= $cur["subject_id"] . " " . $cur["course_num"];

      if ($cur["exclude"] != '0' && $boolAddExclude == true)
      {
        $rtn .= " exclude";
      }

      $rtn .= ",";
      if ($boolAddWhiteSpace == true)
      {
        $rtn .= " ";
      }
    }

    $rtn = trim($rtn);
    // remove last comma.
    $rtn = substr($rtn,0,-1);

    return $rtn;
  }


  /**
   * The function returns either an integer of the the number of
   * hours the course is worth, or, a range in the form of 
   * min-max (if the course has variable hours)
   * 
   * Examples: 3 or 1-6
   *
   * @return string
   */
  function getCatalogHours()
  {
    
    if (!$this->hasVariableHours())
    {
      return $this->minHours;
    } else {
      // Meaning this does course have variable hours.

      $minH = $this->minHours;
      $maxH = $this->maxHours;
      
      
      // Convert back from ghosthours.
      if ($this->boolGhostMinHour) {
        $minH = 0;
      }
      
      if ($this->boolGhostHour) {
        $maxH = 0;
      }    
        
      
      return "$minH-$maxH";
    }
  }




  /**
   * Returns how many hours this course has been advised for.
   * This is used with courses which have variable hours.  If
   * the course has not been advised for any particular number
   * of hours, then it's minHours are returned.
   *
   * @return unknown
   */
  function getAdvisedHours()
  {
    if ($this->advisedHours > -1)
    {
      return $this->advisedHours;
    } else {
      // No, the user has not selected any hours yet.  So,
      // just display the minHours.
      
      // Correct for ghost hours, if any.
      $minH = $this->minHours;
      if ($this->boolGhostMinHour) {
        $minH = 0;
      }
      
      return $minH;
    }

  }

  /**
   * This will assign the $this->displayStatus string
   * based on the grade the student has made on the course.
   * The displayStatus is used by other display functions to decide
   * what color the course should show up as.
   *
   */
  function assignDisplayStatus()
  {
    // Assigns the display status, based on grade.
    $grade = $this->grade;
    // Get these grade definitions from our system settings
    // Configure them in custom/settings.php
    $retakeGrades = $GLOBALS["fpSystemSettings"]["retakeGrades"];
    $enrolledGrades = $GLOBALS["fpSystemSettings"]["enrolledGrades"];


    if (in_array($grade, $retakeGrades))
    {
      $this->displayStatus = "retake";
    }

    if (in_array($grade, $enrolledGrades))
    {
      $this->displayStatus = "enrolled";
    }
  }

  
  /**
   * Returns TRUE if the student has completed the course 
   * (and did not make a failing grade on it).
   *
   * @return bool
   */  
  function isCompleted()
  {
    // returns true if the course has been completed.
    $grade = $this->grade;

    // Get these grade definitions from our system settings
    // Configure them in custom/settings.php
    $retakeGrades = $GLOBALS["fpSystemSettings"]["retakeGrades"];
    $enrolledGrades = $GLOBALS["fpSystemSettings"]["enrolledGrades"];

    if ($grade == "") {
      return false;
    }

    if (in_array($grade, $enrolledGrades)) {
      return false;
    }

    if (in_array($grade, $retakeGrades)) {
      return false;
    }

    return true;

  }


  /**
   * Does $this meed the minimum grade requirement of the
   * supplied course requirement?  You may specify either
   * a Course object, or just enter the minGrade in the mGrade
   * variable.
   *
   * @param Course $courseReq
   *      - The Course object who has the min grade requirement.
   *        Set to NULL if using $mGrade.
   * 
   * @param string $mGrade
   *      - The min grade which $this must meet.  Do not use if using
   *        $courseReq.
   * 
   * @return bool
   */
  function meetsMinGradeRequirementOf(Course $courseReq = NULL, $mGrade = "")
  {
    // Does $this course meet the min grade requirement
    // of the supplied course requirement?

    // Get these grade definitions from our system settings
    // Configure them in custom/settings.php
    $bOrBetter = $GLOBALS["fpSystemSettings"]["bOrBetter"];
    $cOrBetter = $GLOBALS["fpSystemSettings"]["cOrBetter"];
    $dOrBetter = $GLOBALS["fpSystemSettings"]["dOrBetter"];
    $enrolledGrades = $GLOBALS["fpSystemSettings"]["enrolledGrades"];

    if ($courseReq != null) {
      $minGrade = $courseReq->minGrade;
      //adminDebug($minGrade);
    } else {
      $minGrade = $mGrade;
    }



    if ($minGrade == "")
    { // There is no min grade requirement for this course.
      return true;
    }

    // If the student is currently enrolled, return true.
    if (in_array($this->grade, $enrolledGrades))
    {
      return true;
    }


    if ($minGrade == "A" && $this->grade == "A")
    {
      return true;
    }

    if ($minGrade == "B" && in_array($this->grade, $bOrBetter))
    {
      return true;
    }

    if ($minGrade == "C" && in_array($this->grade, $cOrBetter))
    {
      return true;
    }

    if ($minGrade == "D" && in_array($this->grade, $dOrBetter))
    {
      return true;
    }



    return false;
  }


  /**
   * Simply returns TRUE if $this has variable hours.
   *
   * @return bool
   */
  function hasVariableHours()
  {
    
    $minH = $this->minHours;
    $maxH = $this->maxHours;
    
    
    // Convert back from ghosthours, for the comparison.
    if ($this->boolGhostMinHour) {
      $minH = 0;
    }
    
    if ($this->boolGhostHour) {
      $maxH = 0;
    }
    
    
    if ($minH == $maxH)
    {
      return false;
    } else {
      return true;
    }
  }


  /**
   * Figure out the number of hours this particular
   * instance of the course is worth.  In the case
   * of variable hours, it will return the number
   * of hours selected.  If that does not exist,
   * it will return the MIN HOURS.
   *
   * @return int
   */
  function getHours()
  {

    
       
    // Do they have any hoursAwarded? (because they completed
    // the course)
    if ($this->hoursAwarded > 0)
    {
      $h = $this->hoursAwarded;
      return $h;
    }

    
    if ($this->hasVariableHours() && $this->advisedHours > -1) {
      return $this->advisedHours;
    }
    
    
    // This course might be set to 1 hour, but be a "ghost hour",
    // meaning the student actually earned 0 hours, but we recorded 1
    // to make FP's math work out.  So, let's return back 0 hours.
    if ($this->boolGhostHour)
    {
      adminDebug("here");
      //$h = $this->hoursAwarded;
      //if ($this->boolGhostHour) {
        $h = 0;
      //}
      
      return $h;
    }



    // No selected hours, but it's a variable hour course.
    // So, return the minHours for this course.
    return $this->minHours;

  }


  /**
   * This function is used for comparing a course name to the subjectID
   * and courseNum of $this.  
   * We expect a space between the subjectID and CourseNum in $str.
   * 
   * For example: MATH 1010
   * 
   * You may also ONLY specify a subject, ex: BIOL.  If you do that,
   * then only the subject will be compared.
   * 
   * Example of use:  if ($c->nameEquals("ART 101")) then do this etc.
   *
   * @param string $str
   * @return bool
   */
  function nameEquals($str)
  {
    // We expect the str to be given to us
    // with a space b/t the subjectID and courseNum.
    // ex:  MATH 111
    // may also ONLY specify the subject. ex:  BIOL

    $temp = split(" ",$str);
    if ($this->subjectID == $temp[0] && ($this->courseNum == $temp[1] || trim($temp[1]) == ""))
    {
      return true;
    }

    return false;

  }

  
  /**
   * Convienience function.  Simply compare the courseID of
   * another course to $this to see if they are equal.
   * 
   * This is also used by CourseList and ObjList to determine
   * matches.
   * 
   * Usage:  if ($newCourse.equals($otherCourse)) { ... }
   *
   * @param Course $courseC
   * @return bool
   */
  function equals(Course $courseC = null)
  {
    if ($this->courseID == $courseC->courseID)
    {
      return true;
    }

    return false;
  }

  
  
  /**
   * Load $this as a new course based on the subjectID and courseNum,
   * instead of the courseID.  This is a useful function for when you
   * know a subjectID and courseNum, but not courseID (for example, if
   * it comes from human input).
   *
   * @param string $subjectID
   * @param string $courseNum
   */
  function loadCourseFromName($subjectID, $courseNum)
  {
    // Load a course based on its name.  In otherwords,
    // find the CourseID this way first.
    $courseID = $this->db->getCourseID($subjectID, $courseNum);
    $this->loadCourse($courseID);
  }


  /**
   * Loads $this as a new course, based on courseID.
   *
   * @param int $courseID
   * @param bool $isTransfer
   */
  function loadCourse($courseID, $isTransfer = false)
  {

    if ($this->db == NULL)
    {
      $this->db = getGlobalDatabaseHandler();
    }


    $catalogLine = "";
    if ($this->catalogYear != "") {
      $catalogLine = " AND catalog_year = '$this->catalogYear' ";      
    }

    if ($isTransfer == false) {      
      $this->loadDescriptiveData();
    } else {
      // This is a transfer course.  Find out its eqv, if any...
      
      // Let's pull the needed variables out of our settings, so we know what
  		// to query, because this involves non-FlightPath tables.
  		$tsettings = $GLOBALS["fpSystemSettings"]["extraTables"]["course_resources:transfer_courses"];
  		$tfa = (object) $tsettings["fields"];  //Convert to object, makes it easier to work with.  
  		$tableName_a = $tsettings["tableName"];
      
  		$tsettings = $GLOBALS["fpSystemSettings"]["extraTables"]["course_resources:transfer_institutions"];
  		$tfb = (object) $tsettings["fields"];  //Convert to object, makes it easier to work with.  
  		$tableName_b = $tsettings["tableName"];

  		
      
      $res = $this->db->dbQuery("SELECT * FROM
										$tableName_a a,
										$tableName_b b
										WHERE 
									   a.$tfa->transferCourseID = '?' 
									   AND a.$tfa->institutionID = b.$tfb->institutionID ", $courseID);
      $cur = $this->db->dbFetchArray($res);
      $this->subjectID = $cur[$tfa->subjectID];
      $this->courseNum = $cur[$tfa->courseNum];      
      $this->courseID = $courseID;
      $this->boolTransfer = true;
      $this->institutionID = $cur[$tfa->institutionID];
      $this->institutionName = $cur[$tfb->name];
      
    }

    $this->assignDisplayStatus();
  }


  /**
   * This function will correct capitalization problems in course titles.
   * 
   * @param string $str
   * 
   * @return string
   * 
   */
  function fixTitle($str = "")
  {

    if ($str == "")
    {
      $str = $this->title;
    }

        
    $str = str_replace("/", " / ", $str);
    $str = str_replace("/", " / ", $str);
    $str = str_replace("-", " - ", $str);
    $str = str_replace(":", ": ", $str);
    $str = str_replace("(", "( ", $str);

    // Only pad an ampersand if we are not talking about
    // an HTML character.
    if (!strstr($str,"&#"))
    {
      $str = str_replace("&", " & ", $str);
    }

    // Let's also get rid of extra spaces.
    $str = str_replace("   ", " ", $str);
    $str = str_replace("  ", " ", $str);

    // convert to ucwords and fix some problems introduced by that.
    $str = trim(ucwords(strtolower($str)));
    
    $str = str_replace("Iii", "III", $str);
    $str = str_replace("Ii", "II", $str);
    $str = str_replace(" Iv"," IV",$str);
    $str = str_replace(" Vi"," VI",$str);
    $str = str_replace(" Of "," of ",$str);
    $str = str_replace(" The "," the ",$str);
    $str = str_replace(" In "," in ",$str);
    $str = str_replace(" And "," and ",$str);
    $str = str_replace(" An "," an ",$str);
    $str = str_replace(" A "," a ",$str);
    $str = str_replace(" To "," to ",$str);
    $str = str_replace(" For "," for ",$str);

    // Strange words and abreviations which should be changed.
    $str = str_replace("Afrotc","AFROTC",$str);
    $str = str_replace("Gis","GIS",$str);
    $str = str_replace("Dna","DNA",$str);
    $str = str_replace("Cpr","CPR",$str);
    $str = str_replace(" Rn"," RN",$str);
    $str = str_replace(" Micu"," MICU",$str);
    $str = str_replace(" Sicu"," SICU",$str);
    $str = str_replace(" Picu"," PICU",$str);
    $str = str_replace(" Nicu"," NICU",$str);


    // Cleanup
    $str = str_replace("( ", "(", $str);
    $str = str_replace(" - ", "-", $str);


    // Is this just a course name by itself?  If so, it should
    // all be capitalized.
    $temp = split(" ", $str);

    if (count($temp) == 2
    && strlen($temp[0]) <= 4
    && strlen($temp[1]) <= 4)
    {// We could also test to see if there are numbers starting the
      // second token.
      $str = strtoupper($str);
    }

    // If this contains the word "formerly" then we need to pull out what's
    // there and make it all uppercase, except for the word Formerly.
    if (strstr(strtolower($str), strtolower("formerly "))) 
    {
      $formline = preg_replace("/.*\((formerly .*)\).*/i", "$1", $str);
      $str = str_replace($formline, strtoupper($formline), $str);
      $str = str_replace("FORMERLY ", "Formerly ", $str);
    }
    

    $this->title = $str;

    return $str;
  }

  
  /**
   * This function will load $this will all sorts of descriptive data
   * from the database.  For example, hours, title, description, etc.
   * 
   * It must be called before any attempts at sorting (by alphabetical order)
   * are made on lists of courses.
   * 
   * It will by default try to load this information from cache.  If it cannot
   * find it in the cache, it will query the database, and then add what it finds
   * to the cache.
   * 
   *
   * @param bool $boolLoadFromGlobalCache
   *        - If set to TRUE, this will attempt to load the course data
   *          from the "global cache", that is, the cache which is held in the
   *          GLOBALS array.  This should usually be set to TRUE, since this is
   *          much faster than querying the database.
   * 
   * @param bool $boolIgnoreCatalogYearInCache
   *        - If set to TRUE, we will grab whatever is in the cache for this
   *          course's courseID, regardless of if the catalog years match.
   *          If set to FALSE, we will try to match the course's catalog year
   *          in the cache as well.
   * 
   * @param bool $boolLimitCurrentCatalogYear
   *        - If set to TRUE, then we will only *query* for the course's
   *          catalogYear in the db, and those before it (if we do not find
   *          the exact catalogYear).  We will not look for any catalog years
   *          after it.  If set to FALSE, we will look through any 
   *          valid catalog year.
   * 
   * @param bool $boolForceCatalogYear
   *        - If set to TRUE, we will only look for the course's catalog
   *          year in the database.
   * 
   * @param bool $boolIgnoreExclude
   *        - If set to TRUE, we will ignore courses marked as "exclude" in the
   *          database.
   * 
   */
  function loadDescriptiveData($boolLoadFromGlobalCache = true, $boolIgnoreCatalogYearInCache = true, $boolLimitCurrentCatalogYear = true, $boolForceCatalogYear = false, $boolIgnoreExclude = false)
  {

    if ($this->db == null)
    {
      $this->db = getGlobalDatabaseHandler();
    }

    $db = $this->db;


    if ($this->catalogYear == "")
    {
      $this->catalogYear = $GLOBALS["settingCurrentCatalogYear"];  // current catalogYear.
    }

    $settingCurrentCatalogYear = $GLOBALS["settingCurrentCatalogYear"]*1;
    if ($this->boolUseDraft) {
      $settingCurrentCatalogYear = $GLOBALS["settingCurrentDraftCatalogYear"]*1;
    }
    
    $earliestCatalogYear = $GLOBALS["fpSystemSettings"]["earliestCatalogYear"];
    
    
    if ($settingCurrentCatalogYear < $earliestCatalogYear)
    { // If it has not been set, assume the default.
      $settingCurrentCatalogYear = $earliestCatalogYear;
    }

    if ($boolLimitCurrentCatalogYear == true && $settingCurrentCatalogYear > $earliestCatalogYear)
    {
      if ($this->catalogYear*1 > $settingCurrentCatalogYear)
      {

        $this->catalogYear = $settingCurrentCatalogYear;  // current catalogYear.
      }
    }

    if ($this->catalogYear < $earliestCatalogYear && $this->catalogYear != 1900)
    {
      // Out of range, so set to default
      $this->catalogYear = $earliestCatalogYear;
    }

    $catLine = "";
    if ($boolForceCatalogYear == true)
    {
      $catLine = " AND catalog_year = '$this->catalogYear' ";
    }


    $cacheCatalogYear = $this->catalogYear;
    if ($boolIgnoreCatalogYearInCache == true)
    {
      $cacheCatalogYear = 0;
    }

    if (!isset($this->arrayValidNames))
    {
      $this->arrayValidNames = array();
    }


    // First-- is this course in our GLOBALS cache for courses?
    // If it is, then load from that.
    if ($boolLoadFromGlobalCache == true && $this->courseID != 0 &&
    $GLOBALS["fpCourseInventory"][$this->courseID][$cacheCatalogYear]["subjectID"] != "")
    {
      $this->subjectID = $GLOBALS["fpCourseInventory"][$this->courseID][$cacheCatalogYear]["subjectID"];
      $this->courseNum = $GLOBALS["fpCourseInventory"][$this->courseID][$cacheCatalogYear]["courseNum"];
      $this->title = $GLOBALS["fpCourseInventory"][$this->courseID][$cacheCatalogYear]["title"];
      $this->description = $GLOBALS["fpCourseInventory"][$this->courseID][$cacheCatalogYear]["description"];
      $this->minHours = $GLOBALS["fpCourseInventory"][$this->courseID][$cacheCatalogYear]["minHours"];
      
      // Reset the ghosthours to default.
      $this->boolGhostHour = $this->boolGhostMinHour = FALSE;

      if ($this->minHours < 1) {
        $this->minHours = 1;        
        $this->boolGhostMinHour = TRUE;
      }
      
      $this->maxHours = $GLOBALS["fpCourseInventory"][$this->courseID][$cacheCatalogYear]["maxHours"];
      
      if ($this->maxHours < 1) {
        $this->maxHours = 1;
        $this->boolGhostHour = TRUE;
      }
      
      
      $this->repeatHours = $GLOBALS["fpCourseInventory"][$this->courseID][$cacheCatalogYear]["repeatHours"];
      $this->dbExclude = $GLOBALS["fpCourseInventory"][$this->courseID][$cacheCatalogYear]["dbExclude"];
      $this->arrayValidNames = $GLOBALS["fpCourseInventory"][$this->courseID][$cacheCatalogYear]["arrayValidNames"];
      //adminDebug("loaded from gb cache.");
      return;
    }


    if ($this->courseID != 0)
    {
      
      $excludeLine = " AND exclude = '0' ";
      if ($boolIgnoreExclude) {
        $excludeLine = "";
      }
      
      $tableName = "courses";
      if ($this->boolUseDraft) {$tableName = "draft_$tableName";}
      $res = $this->db->dbQuery("SELECT * FROM $tableName
      							WHERE course_id = '?' 
      							AND catalog_year = '?' 
      							AND delete_flag = '0' 
      							$excludeLine ", $this->courseID, $this->catalogYear);
      $cur = $this->db->dbFetchArray($res);

      //adminDebug("here i am" . $this->courseID);
      //var_dump($cur);

      if ($this->db->dbNumRows($res) < 1)
      {
        // No results found, so instead pick the most recent
        // entry.

        $tableName = "courses";
        if ($this->boolUseDraft) {$tableName = "draft_$tableName";}
        $res2 = $db->dbQuery("SELECT * FROM $tableName
							WHERE course_id = '?' 
							AND subject_id != '' 
							AND delete_flag = '0' 
							$excludeLine
							AND catalog_year <= '$settingCurrentCatalogYear'
							$catLine
							ORDER BY `catalog_year` DESC LIMIT 1", $this->courseID);
        $cur = $db->dbFetchArray($res2);

        if ($db->dbNumRows($res2) < 1)
        {
          // Meaning, there were no results found that didn't have
          // the exclude flag set.
          // So, try to retrieve any course, even if it has
          // been excluded (but within our catalog year range)
          //$db3 = new DatabaseHandler();
          $tableName = "courses";
          if ($this->boolUseDraft) {$tableName = "draft_$tableName";}
          $res3 = $db->dbQuery("SELECT * FROM $tableName
							WHERE course_id = '?' 
							AND subject_id != '' 
							AND delete_flag = '0'
							AND catalog_year <= '$settingCurrentCatalogYear'
							$catLine
							ORDER BY `catalog_year` DESC LIMIT 1", $this->courseID);
          $cur = $db->dbFetchArray($res3);

        }

      }


      $this->title = $this->fixTitle($cur["title"]);
      $this->description = trim($cur["description"]);
      $this->subjectID = trim(strtoupper($cur["subject_id"]));
      $this->courseNum = trim(strtoupper($cur["course_num"]));


      $this->minHours = $cur["min_hours"];
      $this->maxHours = $cur["max_hours"];

      // Reset the ghosthours to default.
      $this->boolGhostHour = $this->boolGhostMinHour = FALSE;
      
      if ($this->minHours < 1) {
        $this->minHours = 1;
        $this->boolGhostMinHour = TRUE;
      }
      if ($this->maxHours < 1) {
        $this->maxHours = 1;
        $this->boolGhostHour = TRUE;
      }
      
      
      $this->repeatHours = $cur["repeat_hours"];
      if ($this->repeatHours*1 < 1)
      {
        $this->repeatHours = $this->maxHours;
      }

      $this->dbExclude = $cur["exclude"];
      $this->dataEntryComment = $cur["data_entry_comment"];

      // Now, lets get a list of all the valid names for this course.
      // In other words, all the non-excluded names.  For most
      // courses, this will just be one name.  But for cross-listed
      // courses, this will be 2 or more (probably just 2 though).
      // Example: MATH 373 and CSCI 373 are both valid names for that course.
      $tableName = "courses";
      if ($this->boolUseDraft) {$tableName = "draft_$tableName";}

      $res = $this->db->dbQuery("SELECT * FROM $tableName
										WHERE course_id = '?'
										AND exclude = '0' ", $this->courseID);
      while($cur = $this->db->dbFetchArray($res))
      {
        $si = $cur["subject_id"];
        $cn = $cur["course_num"];
        if (in_array("$si~$cn", $this->arrayValidNames))
        {
          continue;
        }
        $this->arrayValidNames[] = "$si~$cn";
      }


    } else if ($this->boolTransfer == true)
    {
      // This is a transfer credit which did not have a local
      // course eqv.  At the moment, the subjectID and
      // courseNum are empty.  So, let's fill them in with the
      // transfer credit's information.
      if ($this->courseTransfer != null)
      {

        $this->subjectID = $this->courseTransfer->subjectID;
        $this->courseNum = $this->courseTransfer->courseNum;
        if ($this->courseTransfer->hoursAwarded > 0)
        {
          $this->hoursAwarded = $this->courseTransfer->hoursAwarded;
        }
      }


    }


    if ($this->description == "")
    {      
      $this->description = "There is no course description available at this time.";
    }

    if ($this->title == "")
    {
      $this->title = "$this->subjectID $this->courseNum";
    }


    // Now, to reduce the number of database calls in the future, save this
    // to our GLOBALS cache...

    // We do need to go back and correct the ghost hours, setting them
    // back to 0 hrs, or else this will be a problem.
    $minHours = $this->minHours;
    $maxHours = $this->maxHours;
    if ($this->boolGhostMinHour) $minHours = 0;
    if ($this->boolGhostHour) $maxHours = 0;
    
    $GLOBALS["fpCourseInventory"][$this->courseID][$cacheCatalogYear]["subjectID"] = $this->subjectID;
    $GLOBALS["fpCourseInventory"][$this->courseID][$cacheCatalogYear]["courseNum"] = $this->courseNum;
    $GLOBALS["fpCourseInventory"][$this->courseID][$cacheCatalogYear]["title"] = $this->title;
    $GLOBALS["fpCourseInventory"][$this->courseID][$cacheCatalogYear]["description"] = $this->description;
    $GLOBALS["fpCourseInventory"][$this->courseID][$cacheCatalogYear]["minHours"] = $minHours;
    $GLOBALS["fpCourseInventory"][$this->courseID][$cacheCatalogYear]["maxHours"] = $maxHours;
    $GLOBALS["fpCourseInventory"][$this->courseID][$cacheCatalogYear]["repeatHours"] = $this->repeatHours;
    $GLOBALS["fpCourseInventory"][$this->courseID][$cacheCatalogYear]["dbExclude"] = $this->dbExclude;
    $GLOBALS["fpCourseInventory"][$this->courseID][$cacheCatalogYear]["arrayValidNames"] = $this->arrayValidNames;

    $GLOBALS["cacheCourseInventory"] = true;  //  rebuild this cache before it closes.


  }


  /**
   * Similar to loadDescriptiveData(), this will load whatever we have
   * for $this transfer course.
   *
   * @param int $studentID
   *        - If > 0, we will look for the course data which has been
   *          assigned for this particular student.  If it == 0, we will
   *          just use the first bit of data we find.
   * 
   */
  function loadDescriptiveTransferData($studentID = 0)
  {
    // This method should be called to load transfer course data
    // into THIS object.  It assumes that $this->courseID is a transfer
    // course's ID, which can be looked up in flightpath.transfer_courses.

    // If a studentID is specified, it will load eqv information.
    if ($this->db == null)
    {
      $this->db = getGlobalDatabaseHandler();
    }

    
    // Let's pull the needed variables out of our settings, so we know what
		// to query, because this involves non-FlightPath tables.
		$tsettings = $GLOBALS["fpSystemSettings"]["extraTables"]["course_resources:transfer_courses"];
		$tf = (object) $tsettings["fields"];  //Convert to object, makes it easier to work with.  
		$tableName = $tsettings["tableName"];    
    
    
    $res = $this->db->dbQuery("SELECT * FROM $tableName
									     WHERE $tf->transferCourseID = '?' ", $this->courseID);
    $cur = $this->db->dbFetchArray($res);

    $this->subjectID = $cur[$tf->subjectID];
    $this->courseNum = $cur[$tf->courseNum];
    $this->title = $this->fixTitle($cur[$tf->title]);
    $this->minHours = $cur[$tf->minHours];
    $this->maxHours = $cur[$tf->maxHours];
    $this->institutionID = $cur[$tf->institutionID];
    // Try to figure out the institution name for this course...
    $this->institutionName = $this->db->getInstitutionName($this->institutionID);

    if ($studentID > 0)
    {
      // Because transfer credit titles may differ from student
      // to student, let's look up the title in the sisdata table...
      
      // Let's pull the needed variables out of our settings, so we know what
  		// to query, because this involves non-FlightPath tables.
  		$tsettings = $GLOBALS["fpSystemSettings"]["extraTables"]["course_resources:student_transfer_courses"];
  		$tf = (object) $tsettings["fields"];  //Convert to object, makes it easier to work with.  
  		$tableName = $tsettings["tableName"];    
      
      
      $termLine = "";
      if ($this->termID > 1) {
        $termLine = "AND $tf->termID = '$this->termID' ";
      }
      
      $res = $this->db->dbQuery("SELECT * FROM $tableName
									WHERE $tf->studentID = '?'
									AND $tf->transferCourseID = '?' 
									$termLine ", $studentID, $this->courseID);
      $cur = $this->db->dbFetchArray($res);
      if (trim($cur[$tf->studentSpecificCourseTitle]) != "") {
        $this->title = trim($cur[$tf->studentSpecificCourseTitle]);
      }
      // Also assign hoursAwarded while we are here.
      $this->hoursAwarded = $cur[$tf->hoursAwarded];


      // Get EQV information....
      // Let's pull the needed variables out of our settings, so we know what
  		// to query, because this involves non-FlightPath tables.
  		$tsettings = $GLOBALS["fpSystemSettings"]["extraTables"]["course_resources:transfer_eqv_per_student"];
  		$tf = (object) $tsettings["fields"];  //Convert to object, makes it easier to work with.  
  		$tableName = $tsettings["tableName"];    
      
      
      $res2 = $this->db->dbQuery("SELECT * FROM $tableName
            					WHERE $tf->studentID = '?'	
            					AND $tf->transferCourseID = '?' 
            					AND $tf->validTermID = '?' ", $studentID, $this->courseID, $this->termID);
      while($cur2 = $this->db->dbFetchArray($res2))
      {        
        $c = new Course($cur2[$tf->localCourseID]);
        $this->transferEqvText .= "$c->subjectID $c->courseNum
							(" . $c->getCatalogHours() . " hrs) ";
      }

    }


  }

  
  /**
   * Based on $this->termID, set what catalog year should go with
   * the course.
   *
   */
  function setCatalogYearFromTermID()
  {

    if ($this->db == null)
    {
      $this->db = new DatabaseHandler();
    }

    if (strstr($this->termID, "1111"))
    {
      $this->catalogYear = $GLOBALS["fpSystemSettings"]["earliestCatalogYear"];
    }

    $this->catalogYear = trim(substr($this->termID,0,4));

    // If the catalog year is greater than the currentCatalogYear
    // setting, then set it to that.

    $settings = $this->db->getFlightPathSettings();
    if ($this->catalogYear > $settings["currentCatalogYear"])
    {
      $this->catalogYear = $settings["currentCatalogYear"];
    }



  }

  /**
   * Based on $this->termID, returns a plain english description
   * of the term.  For example, 20061 would return "Spring of 2006".
   *
   * @param bool $boolAbbreviate
   *        - If set to TRUE, abbreviations will be used.  For example,
   *          Spring will be "Spr" and 2006 will be '06.
   * 
   * 
   * @return unknown
   */
  function getTermDescription($boolAbbreviate = false)
  {
    // Describe the term in plain english, for displays.
    // Ex:  "Fall of 2002."
    $rtn = "";

    if (strstr($this->termID, "1111"))
    {
      return "(data unavailable at this time)";
    }

    $year4 = trim(substr($this->termID, 0, 4));
    $year2 = trim(substr($this->termID, 2, 2));
    $ss = trim(substr($this->termID, 4, strlen($this->termID) - 4));
    
    $year4p1 = $year4 + 1;
    $year4m1 = $year4 - 1;
    
    // left-pad these with 0's if needed.
    $year2p1 = fp_number_pad($year2 + 1, 2);
    $year2m1 = fp_number_pad($year2 - 1, 2);
        
    // Let's look at the termIDStructure setting and attempt to match
    // what we have been supplied.
    // We expect this structure to look something like:
    // [Y4]60, Spring, Spring of [Y4], Spr '[Y2]
    // [Y4]40, Fall, Fall of [Y4-1], Fall '[Y2-1]
    
    $temp = $GLOBALS["fpSystemSettings"]["termIDStructure"];
    $structures = explode("\n", $temp);
    
    foreach ($structures as $structure) {      
      // Perform the necessary replacement patterns on the structure.
      $structure = str_replace("[Y4]", $year4, $structure);
      $structure = str_replace("[Y2]", $year2, $structure);
      $structure = str_replace("[Y4-1]", $year4m1, $structure);
      $structure = str_replace("[Y2-1]", $year2m1, $structure);
      $structure = str_replace("[Y4+1]", $year4p1, $structure);
      $structure = str_replace("[Y2+1]", $year2p1, $structure);
      
      // Now, break up the structure to make it easier to work with.
      $tokens = explode(",", $structure);
      $termDef = trim($tokens[0]);
      $fullDescription = trim($tokens[2]);
      $abbrDescription = trim($tokens[3]);
      
      // Does our termID match the termDef?
      if ($termDef == $this->termID) {
        if ($boolAbbreviate) {
          return $abbrDescription;
        }
        else {
          return $fullDescription;
        }
      }
      
    }

    return $rtn;
  }

  /**
   * Basically, this is a comparator function that will return true
   * if $this equals many of the attributes of $courseC.  Useful for
   * seeing if $this is an "instance of" a particular course, but not
   * necessairily the course that the student took.  Example: if you want
   * to test if MATH 101 is part of a group.  You wouldn't use ==, since
   * all the attributes might not be the same.
   * 
   * @param Course $courseC
   * 
   * @return bool
   */
  function equalsPlaceholder(Course $courseC)
  {

    // First, see if the courses are identical.
   
    if ($this->equals($courseC)) 
    {
      return true;
    }
    
    // Okay, now we go through and test for particular attributes
    // to be equal.
    if ($this->subjectID == $courseC->subjectID
    && $this->courseNum == $courseC->courseNum
    && $this->institution == $courseC->institution)
    {
      return true;
    }


    return false;
  }

  
  /**
   * This is the toString method for Course.  Because we want to pass it
   * values, we are not using the magic method of "__toString".  So, to use,
   * invoke this method directly.  Ex:
   * 
   * $x = $newCourse->toString("", true);
   *
   * @param string $pad
   *        - How much padding to use.  Specified in the form of a string
   *          of spaces.  Ex:  "   "
   * 
   * @param bool $boolShowRandom
   *        - Display the randomly assigned number which goes with
   *          this course.
   * 
   * @return string
   */
  function toString($pad = "      ", $boolShowRandom = false)
  {
    $rtn = "";

    if ($this->subjectID == "") {
      $this->loadDescriptiveData();
    }

    if ($boolShowRandom) {$x = "rnd:$this->randomID -";}

    $rtn = $pad . "$this->courseID $x- $this->subjectID $this->courseNum ($this->hoursAwarded) $this->grade $this->termID";

    if ($this->courseListFulfilledBy->isEmpty != true) {
      // In other words, if this is a requirement, and it is
      // being fulfilled by one of the student's courses,
      // then let's see it.
      $rtn .= " ->fulfilled by " . $this->courseListFulfilledBy->getFirst()->toString("");
    }

    if ($this->boolTransfer == true && is_object($this->courseTransfer))
    {
      $rtn .= " - XFER eqv to " . $this->courseTransfer->toString("");
    } else if ($this->boolTransfer == true){
      $rtn .= " - XFER no eqv ";
    }


    if ($this->boolAdvisedToTake) {
      $rtn .= " - adv in sem " . $this->assignedToSemesterNum . ".";
    }

    if ($this->boolSubstitution) {
      $rtn .= " - substitution.";
    }

    if ($this->boolExcludeRepeat) {
      $rtn .= " - excluded repeat.";
    }

    if ($this->dbExclude > 0) {
      $rtn .= " - dbExclude = $this->dbExclude";
    }

    if ($this->specifiedRepeats > 0) {
      $rtn .= " reps: $this->specifiedRepeats";
    }


    $rtn .= "\n";
    return $rtn;
  }



  /**
   * This is the magic method __sleep().  PHP will call this method any time
   * this object is being serialized.  It is supposed to return an array of
   * all the variables which need to be serialized.
   * 
   * What we are doing in it is skipping
   * any variables which we are not using or which do not need to be
   * serialized.  This will greatly reduce the size of the final serialized
   * string.
   * 
   * It may not seem worth it at first, but consider that we may be serializing
   * an entire degree plan, with a dozen groups, each with every course in the
   * catalog.  That could easily be 10,000+ courses which get serialized!
   *
   * @return array
   */
  function __sleep()
  {
    // This is supposed to return an array with the names
    // of the variables which are supposed to be serialized.

    $arr = array(
    "dbAdvisedCoursesID",
    "dbSubstitutionID", "dbUnassignTransferID",
    "dbExclude", "arrayIndex", "dbGroupRequirementID", "arrayValidNames",
    "dataEntryValue",

    "subjectID", "courseNum", "courseID", "requirementType", "catalogYear",
    "minHours", "maxHours", "repeatHours", "boolOutdatedSub",

    "boolTaken", "termID", "sectionNumber", "grade", "hoursAwarded", "qualityPoints",
    "boolTransfer", "institutionID", "institutionName", "courseTransfer", "transferFootnote",
    "boolSubstitution", "courseSubstitution", "substitutionHours",
    "boolSubstitutionSplit", "substitutionFootnote", "boolSubstitutionNewFromSplit",

    "minGrade", "specifiedRepeats", "boolSpecifiedRepeat", "requiredOnBranchID",
    "assignedToGroupID", "assignedToSemesterNum",

    "advisedHours", "boolSelected", "boolAdvisedToTake", "boolUseDraft",
    "courseFulfilledBy", "courseListFulfilledBy",
    "boolHasBeenAssigned", "boolAddedCourse", "groupListUnassigned",

    "displayStatus", "boolHasBeenDisplayed", "boolHideGrade", "boolGhostHour",
    "boolGhostMinHour",
    );

    // Okay, remove any variables we are not using
    // from the array.
    $rtn = array();
    foreach($arr as $var)
    {
      if (isset($this->$var))  // This checks to see if we are using
      {						// the variable or not.
        $rtn[] = $var;
      }
    }

    return $rtn;
  }




} // end of Course class.

?>