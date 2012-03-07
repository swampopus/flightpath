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

class _Student
{
	public $studentID, $name, $majorCode, $gpa, $cumulativeHours, $catalogYear;
	public $listCoursesTaken, $listCoursesAdvised, $listCoursesAdded, $db, $rank;
	public $listStandardizedTests, $listSubstitutions;
	public $listTransferEqvsUnassigned;
	public $arraySettings, $arraySignificantCourses, $arrayHideGradesTerms;
	
	/*
	* $studentID		The student's (database-generated) Campus Wide ID.
	* $name				The student's name by which they'll be referred to on screen.
	* $majorCode		ACCT, CSCI, etc.
	* $gpa				Grade point average. Ex: 3.12, 2.97, etc.
	* $cumulativeHours	How many hours the student has earned to date.
	* $catalogYear		What catalog are the listed as? Ex: 2007, 2008, etc.
	* $listCoursesTaken This is a list of all the courses the student has taken.
	It is made up of Course objects.
	*/


	function __construct($studentID = "", DatabaseHandler $db = NULL)
	{
		$this->studentID = $studentID;
		$this->arrayHideGradesTerms = array();
		$this->arraySignificantCourses = array();  // array of courseIDs
		// the student has taken, or has subs for (or transfer eqv's).
		// used later to help speed up assignCoursesToList in FlightPath.

		$this->db = $db;
		if ($db == NULL)
		{
			$this->db = getGlobalDatabaseHandler();
		}
		// If a cwid was specified, then go ahead and load and assemble
		// all information in the database on this student.
		if ($studentID != "")
		{
		  $this->determineTermsToHideGrades();
			$this->loadStudent();
		}

	}

	
	/**
	 * This is a stub function.  If you are planning on hiding course grades
	 * for a term at a time, you should override this method in /custom/classes
	 * and place that logic here.
	 * 
	 * For example,
	 * at ULM, students cannot see their final grades for a term until they
	 * have completed their course evaluations for every course they took that
	 * term, OR, until 2 weeks have passed.  
	 * 
	 * 
	 *
	 */
  function determineTermsToHideGrades()
	{
	  return;
	}		
	
	
	function loadStudent()
	{

		$this->listTransferEqvsUnassigned = new CourseList();
		$this->listCoursesTaken = new CourseList();
		$this->listCoursesAdded = new CourseList();

		$this->listSubstitutions = new SubstitutionList();

		$this->listStandardizedTests = new ObjList();
		$this->arraySettings = array();

		if ($this->studentID != "")
		{
			$this->loadTransferEqvsUnassigned();
			$this->loadCoursesTaken();
			$this->loadStudentData();
			$this->loadTestScores();
			$this->loadSettings();
			$this->loadSignificantCourses();
			//$this->loadUnassignments();
			//$this->loadStudentSubstitutions();
		}
	}

	function loadSignificantCourses()
	{
		// This will attempt to add as much to the arraySignificantCourses
		// as we can, which was not previously determined.
		// For example: courses we were advised to take and/or
		// substitutions.

		// Now, when this gets called, it's actually *before* we
		// write any subs or advisings to the database, so what we
		// actually need to do is go through the POST
		// and add any courseID's we find.
		// In the future, perhaps it would be more efficient
		// to have just one POST variable to look at, perhaps
		// comma-seperated.
		

		// Look in the database of advised courses for ANY course advised in
		// the range of advisingTermIDs.
		$advisingTermIDs = $GLOBALS["settingAvailableAdvisingTermIDs"];
		$temp = split(",",$advisingTermIDs);
		foreach ($temp as $termID)
		{
			$termID = trim($termID);
			//adminDebug($termID);
			$res = $this->db->dbQuery("SELECT * FROM advising_sessions a,
							advised_courses b
							WHERE a.student_id='?'
							AND a.advising_session_id = b.advising_session_id
							AND a.term_id = '?' 
							AND a.is_draft = '0' ", $this->studentID, $termID);
			while ($cur = $this->db->dbFetchArray($res))
			{
				$this->arraySignificantCourses[$cur["course_id"]] = true;
			}
		}


		// Now, look for any course which a substitution might have been
		// performed for...
		$res = $this->db->dbQuery("SELECT * FROM student_substitutions
										WHERE student_id='?' ", $this->studentID);
		while ($cur = $this->db->dbFetchArray($res))
		{
			$this->arraySignificantCourses[$cur["required_course_id"]] = true;
		}


	}

	function loadSignificantCoursesFromListCoursesTaken()
	{
		// Build the arraySignificantCourses
		// entriely from listCoursesTaken.
		$this->listCoursesTaken->resetCounter();
		while($this->listCoursesTaken->hasMore())
		{
			$c = $this->listCoursesTaken->getNext();
			$this->arraySignificantCourses[$c->courseID] = true;
		}
	}
	
	
	function loadSettings()
	{
		// This will load & set up the arraySettings variable for this
		// student.
		$res = $this->db->dbQuery("SELECT * FROM student_settings
									WHERE 
									student_id='?' ", $this->studentID);
		$cur = $this->db->dbFetchArray($res);

		$xml = $cur["settings_xml"];
		if ($arr = fp_xmlToArray2($xml))
		{
			$this->arraySettings = $arr;
		}

	}

	function loadTransferEqvsUnassigned()
	{
		$res = $this->db->dbQuery("SELECT * FROM student_unassign_transfer_eqv
									WHERE
									student_id='?' 
									AND delete_flag='0'
									ORDER BY id ", $this->studentID);
		while($cur = $this->db->dbFetchArray($res))
		{
			extract ($cur, 3, "db");
			$newCourse = new Course();
			$newCourse->boolTransfer = true;
			$newCourse->courseID = $db_transfer_course_id;
			$newCourse->dbUnassignTransferID = $db_id;
			//adminDebug($newCourse->courseID);
			$this->listTransferEqvsUnassigned->add($newCourse);

		}
	}


	function initSemesterCoursesAdded()
	{
		// The "Add a Course" box on screen is really just a
		// semester, with the number -88, with a single group,
		// also numbered -88.
		$this->semesterCoursesAdded = new Semester(-88);
		$this->semesterCoursesAdded->title = "Courses Added by Advisor";

		// Now, we want to add the Add a Course group...
		$g = new Group();
		$g->groupID = -88;
		// Since it would take a long time during page load, we will
		// leave this empty of courses for now.  It doesn't matter anyway,
		// as we will not be checking this group for course membership
		// anyway.  We only need to load it in the popup.
		$g->hoursRequired = 99999;  // Nearly infinite selections may be made.
		$g->assignedToSemesterNum = -88;
		$g->title = "Add an Additional Course";

		$this->semesterCoursesAdded->listGroups->add($g);

	}


	function loadUnassignments()
	{
		// Load courses which have been unassigned from groups
		// or the bare degree plan.
		$res = $this->db->dbQuery("SELECT * FROM student_unassign_group
							WHERE 
								student_id='?' 
								AND delete_flag='0' ", $this->studentID);
		while($cur = $this->db->dbFetchArray($res))
		{
			extract ($cur, 3, "db");

			if ($takenCourse = $this->listCoursesTaken->findSpecificCourse($db_course_id, $db_term_id, (bool) $db_transfer_flag, true))
			{
				// Add the groupID to this courses' list of unassigned groups.
				$newGroup = new Group();
				$newGroup->groupID = $db_group_id;
				$newGroup->dbUnassignGroupID = $db_id;


				$takenCourse->groupListUnassigned->add($newGroup);
			}

		}



	}



	function loadTestScores()
	{
		// If the student has any scores (from standardized tests)
		// then load them here.

		$st = null;
		
		
    // Let's pull the needed variables out of our settings, so we know what
		// to query, because this is a non-FlightPath table.
		$tsettings = $GLOBALS["fpSystemSettings"]["extraTables"]["flightpath_resources:student_tests"];
		$tfa = (object) $tsettings["fields"];  //Convert to object, makes it easier to work with.  
		$tableName_a = $tsettings["tableName"];
				
		$tsettings = $GLOBALS["fpSystemSettings"]["extraTables"]["flightpath_resources:tests"];
		$tfb = (object) $tsettings["fields"];  //Convert to object, makes it easier to work with.  
		$tableName_b = $tsettings["tableName"];
		
		$res = $this->db->dbQuery("		          
		          SELECT * FROM $tableName_a a,$tableName_b b 
							WHERE 
								$tfa->studentID = '?' 
								AND a.$tfa->testID = b.$tfb->testID
								AND a.$tfa->categoryID = b.$tfb->categoryID
							ORDER BY $tfa->dateTaken DESC, $tfb->position ", $this->studentID);		
		while($cur = $this->db->dbFetchArray($res))
		{
			
		  $c++;
		  
		  $db_position = $cur[$tfb->position];
		  $db_datetime = $cur[$tfa->dateTaken];		  
		  $db_test_id = $cur[$tfb->testID];
		  $db_test_description = $cur[$tfb->testDescription];
		  $db_category_description = $cur[$tfb->categoryDescription];
		  $db_category_id = $cur[$tfb->categoryID];
		  $db_score = $cur[$tfa->score];
		  
		  
			if (!(($db_datetime . $db_test_id) == $oldRow))
			{
				// We are at a new test.  Add the old test to our list.
				if ($st != null)
				{
					//adminDebug("adding " . $st->toString());
					$this->listStandardizedTests->add($st);

				}

				$st = new StandardizedTest();
				$st->testID = $db_test_id;
				$st->dateTaken = $db_datetime;
				$st->description = $db_test_description;
				$oldRow = $db_datetime . $db_test_id;

			}

			$st->categories[$db_position . $c]["description"] = $db_category_description;
			$st->categories[$db_position . $c]["category_id"] = $db_category_id;
			$st->categories[$db_position . $c]["score"] = $db_score;

			//adminDebug(count($st->categories));

		}

		// Add the last one created.
		if ($st != null)
		{
			$this->listStandardizedTests->add($st);
		}

		//print_pre($this->listStandardizedTests->toString());

	}


	function loadStudentSubstitutions()
	{
		// Load the substitutions which have been made for
		// this student.
		
		// Meant to be called AFTER loadCoursesTaken.
		$this->listSubstitutions = new SubstitutionList();
		
		$res = $this->db->dbQuery("SELECT * FROM
						student_substitutions
						WHERE student_id='?'
						AND delete_flag='0' ", $this->studentID);
		while($cur = $this->db->dbFetchArray($res))
		{

			$subID = $cur["id"];
			$subCourseID = $cur["sub_course_id"];
			$subTermID = $cur["sub_term_id"];
			$subBoolTransfer = (bool) $cur["sub_transfer_flag"];
			$subHours = $cur["sub_hours"];
			$subRemarks = trim($cur["sub_remarks"]);
			$facultyID = $cur["faculty_id"];

			if (strstr($subTermID, "9999"))
			{
				// was an unknown semester.  Let's set it lower so
				// it doesn't screw up my sorting.
				$subTermID = 11111;
			}


			// Okay, look to see if we can find the course specified by this
			// courseSubstitution within the list of courses which the student
			// has taken.  If the subHours is less than the hoursAwarded for the
			// particular course, it means the course has been split up!

			if($takenCourse = $this->listCoursesTaken->findSpecificCourse($subCourseID, $subTermID, $subBoolTransfer, true))
			{
				
								
				// If this takenCourse is a transfer credit, then we want to remove
				// any automatic eqv it may have set.
				// We can do this easily by setting its courseID to 0.
				if ($subBoolTransfer == true)
				{
					$takenCourse->tempOldCourseID = $takenCourse->courseID;
					$takenCourse->courseID = 0;
				}

				if ($subHours == 0)
				{ // If none specified, assume its the full amount.
					$subHours = $takenCourse->hoursAwarded;
				}


				if (($takenCourse->hoursAwarded > $subHours))
				{
					// Okay, now this means that the course which we are
					// using in the substitution-- the course which the student
					// has actually taken-- is being split up in the substitution.
					// We are only using a portion of its hours.
					$remainingHours = $takenCourse->hoursAwarded - $subHours;
					
					// Create a clone of the course with the leftover hours, and add
					// it back into the listCoursesTaken.
					$newCourseString = $takenCourse->toDataString();
					$newCourse = new Course();
					$newCourse->loadCourseFromDataString($newCourseString);

					$newCourse->boolSubstitutionSplit = true;
					$newCourse->boolSubstitutionNewFromSplit = true;

          $newCourse->subjectID = $takenCourse->subjectID;
          $newCourse->courseNum = $takenCourse->courseNum;
					
					$newCourse->hoursAwarded = $remainingHours;
					if (is_object($newCourse->courseTransfer))
					{
						$newCourse->courseTransfer->hoursAwarded = $remainingHours;
					}

					$takenCourse->boolSubstitutionSplit = true;
					$takenCourse->hoursAwarded = $subHours;
					if (is_object($takenCourse->courseTransfer))
					{
						$takenCourse->courseTransfer->hoursAwarded = $subHours;
					}


					
					// Add the newCourse back into the student's listCoursesTaken.
					$this->listCoursesTaken->add($newCourse);

				}


				$takenCourse->substitutionHours = $subHours;
				$takenCourse->boolSubstitution = true;
				$takenCourse->displayStatus = "completed";
				$takenCourse->dbSubstitutionID = $subID;


				$substitution = new Substitution();

				if ($cur["required_course_id"] > 0)
				{
					$courseRequirement = new Course($cur["required_course_id"]);
					
					$this->arraySignificantCourses[$courseRequirement->courseID] = true;

				} else {
					// This is a group addition!
					$courseRequirement = new Course($subCourseID, $subBoolTransfer);
					$this->arraySignificantCourses[$subCourseID] = true;					
					$substitution->boolGroupAddition = true;
				}

				$courseRequirement->assignedToGroupID = $cur["required_group_id"];
				$courseRequirement->assignedToSemesterNum = $cur["required_semester_num"];
				$takenCourse->assignedToGroupID = $cur["required_group_id"];
				$takenCourse->assignedToSemesterNum = $cur["required_semester_num"];

				$substitution->courseRequirement = $courseRequirement;

				
				$substitution->courseListSubstitutions->add($takenCourse);
				

				$substitution->remarks = $subRemarks;
				$substitution->facultyID = $facultyID;
				$this->listSubstitutions->add($substitution);



			} else {
				//adminDebug("Taken course not found. $subCourseID $subTermID transfer: $subBoolTransfer");
			}

		}

		//print_pre($this->listCoursesTaken->toString());
		//print_pre($this->listSubstitutions->toString());


	}


	/**
	 * This loads a student's personal data, like name and so forth.
	 *
	 */
	function loadStudentData()
	{

	  // Let's pull the needed variables out of our settings, so we know what
		// to query, because this is a non-FlightPath table.
		$tsettings = $GLOBALS["fpSystemSettings"]["extraTables"]["human_resources:students"];
		$tf = (object) $tsettings["fields"];  //Convert to object, makes it easier to work with.  
		$tableName = $tsettings["tableName"];

		
	  // Let's perform our queries.
		$res = $this->db->dbQuery("SELECT * FROM $tableName 
						          WHERE $tf->studentID = '?' ", $this->studentID);
		$cur = $this->db->dbFetchArray($res);

		

		$this->cumulativeHours = $cur[$tf->cumulativeHours];
		$this->gpa = $cur[$tf->gpa];
		$this->rank = $this->getRankDescription($cur[$tf->rankCode]);
		$this->majorCode = $cur[$tf->majorCode];

		$this->name = ucwords(strtolower($cur[$tf->fName] . " " . $cur[$tf->lName]));

		$catalog = $cur[$tf->catalogYear];
		
		// If this is written in the format 2006-2007, then we just want
		// the first part.  Luckily, this will still work even if there WASN'T
		// a - in there ;)
	  $temp = explode("-", $catalog);
	  $this->catalogYear = $temp[0];

	}

	
	/**
	 * Given a rankCode like FR, SO, etc., get the english
	 * description. For example: Freshman, Sophomore, etc.
	 *	 
	 */
	function getRankDescription($rankCode = "") {
    $rankArray = array(
      "FR"=>"Freshman", 
      "SO"=>"Sophomore",
      "JR"=>"Junior", 
      "SR"=>"Senior", 
      "PR"=>"Professional"
    );	  
    
    return $rankArray[$rankCode];
        
	}
	
	

	/**
	 * Returns a student's degree plan object.
	 *
	 */
	function getDegreePlan($boolLoadFull = true, $boolIgnoreSettings = false)
	{
		
	  $tMajorCode = $this->getMajorAndTrackCode($boolIgnoreSettings);
		//adminDebug($tMajorCode);
		$degreeID = $this->db->getDegreeID($tMajorCode, $this->catalogYear);
		if ($boolLoadFull)
		{
			$degreePlan = new DegreePlan($degreeID, $this->db);
		} else {
			$degreePlan = new DegreePlan();
			$degreePlan->degreeID = $degreeID;
			$degreePlan->loadDescriptiveData();
		}

		return $degreePlan;
	}


	/**
	 * Enter description here...
	 * Returns the major code and trackCode, if it exists in this form:
	 *  MAJOR|CONC_TRACK
	 *  Though usually it will be:
	 * MAJR|_TRCK
	 * Asumes you have already called "loadSettings()";
	 */
	function getMajorAndTrackCode($boolIgnoreSettings = false)
	{

		$rtn = "";
		$majorCode = "";

		if ($this->arraySettings["majorCode"] != "")
		{ // If they have settings saved, use those...
			if ($this->arraySettings["trackCode"] != "")
			{
				// if it does NOT have a | in it already....
				if (!strstr($this->arraySettings["majorCode"], "|"))
				{
					$rtn = $this->arraySettings["majorCode"] . "|_" . $this->arraySettings["trackCode"];
				} else {
					// it DOES have a | already, so we join with just a _.  This would
					// be the case if we have a track AND an concentration.
					$rtn = $this->arraySettings["majorCode"] . "_" . $this->arraySettings["trackCode"];
				}
			} else {
				$rtn = $this->arraySettings["majorCode"];
			}
			$majorCode = $this->arraySettings["majorCode"];
		} else {
			$rtn = $this->majorCode;
		}

		//adminDebug($this->arraySettings["majorCode"]);
		
		if ($boolIgnoreSettings == true)
		{
			$rtn = $this->majorCode;
		}


		return $rtn;

	}

	
	
	function loadCoursesTaken($boolLoadTransferCredits = true)
	{

	  $retakeGrades = $GLOBALS["fpSystemSettings"]["retakeGrades"];
	  // Let's pull the needed variables out of our settings, so we know what
		// to query, because this involves non-FlightPath tables.
		$tsettings = $GLOBALS["fpSystemSettings"]["extraTables"]["course_resources:student_courses"];
		$tf = (object) $tsettings["fields"];  //Convert to object, makes it easier to work with.  
		$tableName = $tsettings["tableName"];

		// This will create and load the listCoursesTaken list.
		// contains SQL queries to fully create the listCoursesTaken.
		$res = $this->db->dbQuery("SELECT *	FROM $tableName									
                							 WHERE 
                								$tf->studentID = '?' ", $this->studentID);
	
		while($cur = $this->db->dbFetchArray($res))
		{

			// Create a course object for this course...
			$isTransfer = false;
			$courseID = $this->db->getCourseID($cur[$tf->subjectID], $cur[$tf->courseNum]);

			if (!$courseID)
			{
				adminDebug("Course not found while trying to load student data: {$cur[$tf->subjectID]} {$cur[$tf->courseNum]}");
				continue;
			}

			$newCourse = new Course();
			$newCourse->courseID = $courseID;

			// Load descriptive data for this course from the catalog (so we can get min, max, and repeat hours)
			$newCourse->loadDescriptiveData();

			// Now, over-write whatever we got from the descriptive data with what the course was called
			// when the student took it.
			$newCourse->subjectID = $cur[$tf->subjectID];
			$newCourse->courseNum = $cur[$tf->courseNum];
			$newCourse->grade = $cur[$tf->grade];
			$newCourse->termID = $cur[$tf->termID];
			
			// Is this grade supposed to be hidden from students?
			if (in_array($newCourse->termID, $this->arrayHideGradesTerms)
			  && $_SESSION["fpUserType"] == "student")
			{
			  $newCourse->boolHideGrade = true;
			}			
			
			$newCourse->hoursAwarded = trim($cur[$tf->hoursAwarded]);
			$newCourse->displayStatus = "completed";
			$newCourse->boolTaken = true;
			
			// Was this course worth 0 hours but they didn't fail it?
			// If so, we need to set it to actually be 1 hour, and
			// indicate this is a "ghost hour."
			if (!in_array($newCourse->grade, $retakeGrades) 
			     && $newCourse->hoursAwarded == 0) 			
			{
			  $newCourse->hoursAwarded = 1;
			  $newCourse->boolGhostHour = TRUE;
			}			
			
			// Now, add the course to the list_courses_taken...
			$this->listCoursesTaken->add($newCourse);
			$this->arraySignificantCourses[$courseID] = true;
			
		}


		
		if ($boolLoadTransferCredits == false) {
			return;
		}
		
		
		// Tranfer credits?  Get those too...
	  // Let's pull the needed variables out of our settings, so we know what
		// to query, because this involves non-FlightPath tables.
		$tsettings = $GLOBALS["fpSystemSettings"]["extraTables"]["course_resources:student_transfer_courses"];
		$tfa = (object) $tsettings["fields"];  //Convert to object, makes it easier to work with.  
		$tableName_a = $tsettings["tableName"];
			
		$tsettings = $GLOBALS["fpSystemSettings"]["extraTables"]["course_resources:transfer_courses"];
		$tfb = (object) $tsettings["fields"];  //Convert to object, makes it easier to work with.  
		$tableName_b = $tsettings["tableName"];
		
		
		$res = $this->db->dbQuery("
                  			SELECT *
                  			FROM $tableName_a a, 
                  			     $tableName_b b 
                  			WHERE a.$tfa->transferCourseID = b.$tfb->transferCourseID
                  			AND a.$tfa->studentID = '?' ", $this->studentID);

		while($cur = $this->db->dbFetchArray($res))
		{
			$transferCourseID = $cur[$tfa->transferCourseID];
			$institutionID = $cur[$tfb->institutionID];

			$newCourse = new Course();

			// Find out if this course has an eqv.
			if ($courseID = $this->getTransferCourseEqv($transferCourseID, false, $cur[$tfa->termID]))
			{
				$newCourse = new Course($courseID);
				$this->arraySignificantCourses[$courseID] = true;
			}



			$tCourse = new Course();
			$tCourse->subjectID = $cur[$tfb->subjectID];
			$tCourse->courseNum = $cur[$tfb->courseNum];
			$tCourse->courseID = $transferCourseID;
			$tCourse->boolTransfer = true;
			$tCourse->institutionID = $institutionID;

			$newCourse->boolTransfer = true;

			$newCourse->courseTransfer = $tCourse;
			$newCourse->grade = $cur[$tfb->grade];
			$tCourse->grade = $cur[$tfb->grade];

			$newCourse->hoursAwarded = $cur[$tfb->hoursAwarded];
			$tCourse->hoursAwarded = $cur[$tfb->hoursAwarded];
			
			
		  // Was this course worth 0 hours but they didn't fail it?
			// If so, we need to set it to actually be 1 hour, and
			// indicate this is a "ghost hour."
			if (!in_array($newCourse->grade, $retakeGrades) 
			     && $newCourse->hoursAwarded == 0) 			
			{
			  $newCourse->hoursAwarded = 1;
			  $newCourse->boolGhostHour = TRUE;
			  $tCourse->hoursAwarded = 1;
			  $tCourse->boolGhostHour = TRUE;
			}						
			
			$newCourse->boolTaken = true;
			$tCourse->boolTaken = true;
			

			$newCourse->termID = $cur[$tfb->termID];
			if (strstr($newCourse->termID, "9999"))
			{
				// was an unknown semester.  Let's set it lower so
				// it doesn't screw up my sorting.
				$newCourse->termID = 11111;
			}
      $tCourse->termID = $newCourse->termID;
			$newCourse->displayStatus = "completed";

			$this->listCoursesTaken->add($newCourse);
		}
		//		print_pre($this->listCoursesTaken->toString());

	}



	
	/**
	 * Find a transfer eqv for this student, for this course in question.
	 *
	 */
	function getTransferCourseEqv($transferCourseID, $boolIgnoreUnassigned = false, $requireValidTermID = "")
	{
		
	  // First, make sure that this transfer course hasn't
		// been unassigned.  Do this by checking through
		// the student's courseListUnassignedTransferEQVs.
		$tempCourse = new Course();
		$tempCourse->courseID = $transferCourseID;
		if ($boolIgnoreUnassigned == false && $this->listTransferEqvsUnassigned->findMatch($tempCourse)) {
			// The transfer course in question has had its eqv removed,
			// so skip it!
			return false;
		}

    
	  // Let's pull the needed variables out of our settings, so we know what
		// to query, because this involves non-FlightPath tables.
		$tsettings = $GLOBALS["fpSystemSettings"]["extraTables"]["course_resources:transfer_eqv_per_student"];
		$tf = (object) $tsettings["fields"];  //Convert to object, makes it easier to work with.  
		$tableName = $tsettings["tableName"];

		
    $validTermLine = "";
    if ($requireValidTermID != "") {
      // We are requesting eqv's only from a particular valid term, so, amend
      // the query.
      $validTermLine = "AND $tf->validTermID = $requireValidTermID ";
    }
		
        
		// Does the supplied transfer course ID have an eqv?
		$res = $this->db->dbQuery("
			SELECT * FROM $tableName
			WHERE $tf->transferCourseID = '?'
			AND $tf->studentID = '?'
			AND $tf->brokenID = '0'
			$validTermLine 	", $transferCourseID, $this->studentID);

		if ($cur = $this->db->dbFetchArray($res)) {
			return $cur[$tf->localCourseID];
		}
 
		return false;

	}
	
	
	function toString()	{
		$rtn = "Student Information:\n";
		$rtn .= " Courses Taken:\n";
		$rtn .= $this->listCoursesTaken->toString();
		return $rtn;
	}

} // end class Student

?>