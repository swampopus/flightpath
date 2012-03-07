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

class _FlightPath
{
	public $student, $degreePlan, $db, $boolWhatIf;
	public $courseListAdvisedCourses;


	function __construct($student = "", $degreePlan = "", DatabaseHandler $db = null, $boolPerformFullInit = false)
	{
		if ($student != "")
		{
			$this->student = $student;
		}
		if ($degreePlan != "")
		{
			$this->degreePlan = $degreePlan;
		}

		if ($db != null)
		{
			$this->db = $db;
		} else {
			$this->db = getGlobalDatabaseHandler();
		}

		if ($boolPerformFullInit == true)
		{
			$this->init(true);
		}

		$this->courseListAdvisedCourses = new CourseList();

	}


	function init($boolInitAdvisingVariables = false, $boolIgnoreWhatIfAdvisingVariables = false, $boolLoadFull = true)
	{
		// This will initialize this flightPath object
		// based on what is available in the global variables.
		// Takes the place of what was going on at the beginning
		// of advise.php.

		if ($boolInitAdvisingVariables == true)
		{
			$tempScreen = new AdvisingScreen();
			$tempScreen->initAdvisingVariables($boolIgnoreWhatIfAdvisingVariables);
		}

		$majorCode = $GLOBALS["advisingMajorCode"];
		$trackCode = $GLOBALS["advisingTrackCode"];
		$studentID = $GLOBALS["advisingStudentID"];
		$advisingTermID = $GLOBALS["advisingTermID"];
		$availableTerms = $GLOBALS["settingAvailableAdvisingTermIDs"];



		$this->boolWhatIf = false;

		// Are we in WhatIf mode?
		if ($GLOBALS["advisingWhatIf"] == "yes")
		{
			$majorCode = $GLOBALS["whatIfMajorCode"];
			$trackCode = $GLOBALS["whatIfTrackCode"];
			//adminDebug("trackCode: $trackCode");
			//$majorCode = "ART";
			$this->boolWhatIf = true;
			//adminDebug("here");

		}

		if ($boolLoadFull == false)
		{	// not trying to load anything, so return.
			return;
		}

		//adminDebug("going through");
		//adminDebug($majorCode);
		$db = $this->db;


		if ($boolLoadFull == true)
		{
			$student = new Student($studentID);
		} else {
			$student = new Student();
			$student->studentID = $studentID;

		}



		$settings = $db->getFlightPathSettings();

		$catalogYear = $student->catalogYear;
		if ($this->boolWhatIf)
		{
			$catalogYear = $settings["currentCatalogYear"];
		}

		// make sure their catalog year is not past the system's current
		// year setting.
		if ($catalogYear > $settings["currentCatalogYear"]
		&& $settings["currentCatalogYear"] > $GLOBALS["fpSystemSettings"]["earliestCatalogYear"])
		{ // Make sure degree plan is blank if it is!
			$catalogYear = 99999;
		}

		if ($GLOBALS["advisingUpdateStudentSettingsFlag"] != "")
		{
			$student->arraySettings["trackCode"] = $trackCode;
			$student->arraySettings["majorCode"] = $majorCode;
		}

		$tMajorCode = $majorCode;

		if ($trackCode != "")
		{
			// Does the majorCode already have a | in it?
			if (!strstr($tMajorCode, "|"))
			{
				$tMajorCode .= "|_" . $trackCode;
			} else {
				// it DOES have a | in it already, so just add the
				// trackCode using _.  This is most likely because
				// we are dealing with a track AND a concentration.
				$tMajorCode .= "_" . $trackCode;
			}
		}


		$degreeID = $db->getDegreeID($tMajorCode, $catalogYear);
    //adminDebug($degreeID);
		
		//adminDebug($tMajorCode);

		if ($student->arraySettings["trackCode"] != "" && $this->boolWhatIf == false
		&& $student->arraySettings["majorCode"] == $majorCode)
		{
			// The student has a selected track in their settings,
			// so use that (but only if it is for their current major-- settings
			// could be old!)

			$tMajorCode = $student->getMajorAndTrackCode();
			$tempDegreeID = $db->getDegreeID($tMajorCode, $student->catalogYear);
			if ($tempDegreeID) {
			  $degreeID = $tempDegreeID;
			}
		}

		//adminDebug($degreeID);


		if ($boolLoadFull == true)
		{
			$this->student = $student;
			//adminDebug(" .. yy");
			$degreePlan = new DegreePlan($degreeID, $db, false, $student->arraySignificantCourses);
			//adminDebug(" .. yy");
			$degreePlan->addSemesterDevelopmental($student->studentID);
			$this->degreePlan = $degreePlan;
		}



	}


	/**
	 * 		This function will check to see if we are trying to save
	 * the draft from a tab change.  It should be near the top
	 *	of all of FP's "tab" pages, like Main, Comments, etc.
	 *
	 */
	function processRequestSaveDraft()
	{

		/////////////////////////////////////
		///  Are we trying to save the draft
		///  from a tab change?
		/////////////////////////////////////
		if ($_REQUEST["saveDraft"] == "yes")
		{
			$this->init(true, false, false);
			// If we are coming from the WhatIf tab, we need to save
			// as WhatIf.  Else, save as normal.
			if ($_REQUEST["fromWI"] == "yes")
			{
				// Yes, we are coming from WhatIf mode, so
				// save under WhatIf.
				//adminDebug("Coming from What If");
				$GLOBALS["advisingWhatIf"] = "yes";
				$this->init(false);
				//$GLOBALS["advisingWhatIf"] = "no";
			} else {
				// NOT coming from WhatIf mode.  Save as a normal draft.
				$GLOBALS["advisingWhatIf"] = "no";
				$this->init(true, true);
				//$GLOBALS["advisingWhatIf"] = "yes";
			}
			$this->saveAdvisingSessionFromPost(0,true);
		}

	}


	function assignCoursesToGroups()
	{
		// This method will look at the student's courses
		// and decide which groups they should be fit into.

		// We will be going through the degree plan's master list
		// of groups to decide this.
		$student = $this->student;
		$this->degreePlan->listGroups->sortPriority();
		$this->degreePlan->listGroups->resetCounter();
		while($this->degreePlan->listGroups->hasMore())
		{
			$g = $this->degreePlan->listGroups->getNext();

			if ($g->groupID == -88)
			{
				// Add a course group.  Skip.
				continue;
			}

			// Does the student have any group additions for this
			// group?  Technically it is a substitution.
			// We will add them in now, because we do not take additions
			// into consideration when figuring out branches.
			//adminDebug("_____Working on group $g->title");
			//print_pre($g->toString());
			if ($courseListAdditions = $student->listSubstitutions->findGroupAdditions($g))
			{
				$courseListAdditions->resetCounter();
				while($courseListAdditions->hasMore())
				{
					$cA = $courseListAdditions->getNext();
					$newCourse = new Course();
					$newCourse->courseID = $cA->courseID;

					if ($cA->boolTransfer == true)
					{
						if ($cA->courseID == 0 && is_object($cA->courseTransfer))
						{ // This is a transfer course which has been added.
							$newCourse->courseID = $cA->courseTransfer->courseID;
						}
						$newCourse->boolTransfer = true;
					}

					$newCourse->assignedToSemesterNum = $g->assignedToSemesterNum;
					$newCourse->requirementType = $g->requirementType;
					// Add this course as a requirement.
					//$newCourse->loadDescriptiveData();
					//adminDebug("Found group addition for " . $g->title . ". It is " . $newCourse->toString());
					$g->listCourses->add($newCourse, true);
					// Later on, when we do assignCoursesToList, it
					// will automatically find this course and apply the
					// substitution.
				}
			}
			//print_pre($g->toString());


			// First we see if there are any bare courses at this level.  If there
			// are, then this group has NO branches!  Otherwise, the courses must
			// always be contained in a branch!
			if (!$g->listCourses->isEmpty)
			{
				// Yes, there are courses here.  So, assign them at this level.
				//$this->assignCoursesToList($g->listCourses, $this->student, true, $g);
				$this->assignCoursesToList($g->listCourses, $this->student, true, $g, true);
				// Okay, if we have fulfilled our courses at this level.

				// then we can continue on to the next "top level" group.
				//continue;
			}


			if (!$g->listGroups->isEmpty)
			{
				/*
				Now we've got some trouble.  This is our first level of groups.
				If this object exists, then it means that this group branches off
				at its first level.  So, instead of actually assigning courses to
				groups, we need to find out which group has the most matches, and THEN
				we will assign them.
				*/
				//adminDebug("She's got branches!");
				$g->reloadMissingCourses();
				//print_pre($g->toString());
				$highCount = -1;
				$bestBranch = -1;
				$g->listGroups->resetCounter();
				while($g->listGroups->hasMore())
				{
					$branchOne = $g->listGroups->getNext();
					if (!$branchOne->listCourses->isEmpty)
					{
						// This does not actually assign.  Just counts.
						//adminDebug("..just get count..");
						//$count = $this->assignCoursesToList($branchOne->listCourses, $this->student, false, $g, true);
						$count = $this->getCountOfMatches($branchOne, $this->student, $g);
						$branchOne->countOfMatches = $count;


						if ($count > $highCount)
						{
							$highCount = $count;
							//adminDebug("$branchOne->groupID $highCount");
							$bestBranch = $g->listGroups->objectIndexOf($branchOne);
							//$bestBranch = $branchOne;
						}
					}

				}
				// Okay, coming out of that, we should know which branch has the best count (number
				// of matches).  So, let's assign courses to that branch.
				if ($bestBranch != -1)
				{
					$winningBranch = $g->listGroups->getElement($bestBranch);
					$winningBranch->boolWinningBranch = true;
					//adminDebug($highCount . " brid:" . $winningBranch->groupID);
					//adminDebug(".. actually assign..");
					$this->assignCoursesToList($winningBranch->listCourses, $this->student, true, $g, true);
					//print_pre($winningBranch->toString());
				}

			}


		}

	}


	function getCountOfMatches($branch, $student, $group)
	{
		return $this->assignCoursesToList($branch->listCourses, $student, false, $group, true);
	}

	function flagOutdatedSubstitutions()
	{
		// Go through the student's substitutions and flag ones that
		// do not apply to this degree plan.  Also, unset any boolSubstitution
		// variables which were set.

		$this->student->listSubstitutions->resetCounter();
		while ($this->student->listSubstitutions->hasMore())
		{
			$substitution = $this->student->listSubstitutions->getNext();

			$requiredGroupID = $substitution->courseRequirement->assignedToGroupID;
			//adminDebug("found sub for group $requiredGroupID ");
			// First check-- does this degree even have this group ID?
			$outdatedNote = "";
			if ($requiredGroupID == 0)
			{
				// bare degree plan.
				// Does the bare degree plan list the courseRequirement
				// anywhere?
				$boolSubValid = false;
				$this->degreePlan->listSemesters->resetCounter();
				while($this->degreePlan->listSemesters->hasMore() && $boolSubValid == false)
				{
					$sem = $this->degreePlan->listSemesters->getNext();
					if ($sem->listCourses->findMatch($substitution->courseRequirement))
					{
						$boolSubValid = true;
					} else {
						// Could not find the course requirement in question.
						$boolSubValid = false;
						$scr = $substitution->courseRequirement;
						$scr->loadDescriptiveData();
						$outdatedNote = "This substitution is for the course $scr->subjectID
											$scr->courseNum (id: $scr->courseID) on the 
											bare degree plan, but the student's current degree does
											not specify this course.";
					}
				}


			} else {
				// requiredGroupID != 0.  So, does this
				// degree plan have a group with this id?
				$boolSubValid = false;
				if ($g = $this->degreePlan->findGroup($requiredGroupID))
				{
					$boolSubValid = true;
				} else {
					// Could not find the group in question.  Add an "outdatedNote"
					// to the sub...
					$boolSubValid = false;
					$newGroup = new Group();
					$newGroup->groupID = $requiredGroupID;
					$newGroup->loadDescriptiveData();
					$groupName = "";
					if ($_SESSION["fpUserType"] == "full_admin")
					{ // only show if we are full admin.
						$groupName = "<i>$newGroup->groupName,</i>";
					}
					$outdatedNote = "This substitution is for the group $newGroup->title
									(id: $newGroup->groupID, $groupName $newGroup->catalogYear),
									but the student's current degree does not call for this 
									specific group.";
				}
			}


			if ($boolSubValid == false)
			{
				//adminDebug("Could not find degree sub for " . $substitution->courseRequirement->toString());
				// Couldn't find a match, so remove this sub!
				$substitution->boolOutdated = true;
				$substitution->outdatedNote = $outdatedNote;
				$substitution->courseListSubstitutions->getFirst()->boolOutdatedSub = true;
				$substitution->courseListSubstitutions->getFirst()->boolSubstitution = false;
				if ($substitution->courseListSubstitutions->getFirst()->tempOldCourseID > 0)
				{ // Restore the courseID *if* it was set to 0 on purpose. (happens
					// when there is a sub of a transfer to kill the transfer eqv.  This will
					// restore it).
					$substitution->courseListSubstitutions->getFirst()->courseID = $substitution->courseListSubstitutions->getFirst()->tempOldCourseID;
				}
			}



		}



	}


	function assignCoursesToList(ObjList $listRequirements, Student $student, $boolPerformAssignment = true, Group $group = null, $boolCheckSignificantCourses = false)
	{
		$count = 0;

		if ($group == null)
		{
			$group = new Group();
			$group->groupID = 0;
		}

		$groupID = $group->groupID;
		// If the groupID == 0, we may be talking about the bare degree plan.

		$hoursRequired = $group->hoursRequired*1;
		$hoursAssigned = $group->hoursAssigned;
		//adminDebug("---------- hoursRequired: $hoursRequired, $groupID, $group->title");
		if ($hoursRequired*1 < 1 || $hoursRequired == "")
		{
			$hoursRequired = 999999;
		}

		//print_pre($listRequirements->toString());
		$listRequirements->sortSmallestHoursFirst();
		$listRequirements->sortSubstitutionsFirst($student->listSubstitutions, $groupID);
		$listRequirements->resetCounter();
		while($listRequirements->hasMore())
		{
			$courseRequirement = $listRequirements->getNext();

			if ($boolCheckSignificantCourses == true)
			{
				// Only look for the courseRequirement if it is in the student's
				// arraySignificantCourses array.
				if ($student->arraySignificantCourses[$courseRequirement->courseID] != true)
				{// course was not in there, so skip!
					continue;
				}
			}



			//adminDebug(".looking at CR $courseRequirement->courseID $courseRequirement->subjectID $courseRequirement->courseNum. lc: $listRequirements->count");
			if ($courseRequirement->boolSpecifiedRepeat == true)
			{
				// Since this requirement has specified repeats, we want
				// to make all of the student's taken courses (for this course)
				// also have specified repeats.
				$student->listCoursesTaken->setSpecifiedRepeats($courseRequirement, $courseRequirement->specifiedRepeats);
			}

			//print_pre($student->listSubstitutions->toString());

			//adminDebug("looking in group ID: $groupID");
			// Does the student have any substitutions for this requirement?
			if ($substitution = $student->listSubstitutions->findRequirement($courseRequirement, true, $groupID))
			{
				//adminDebug($substitution->toString());
				// Since the substitution was made, I don't really care about
				// min grades or the like.  Let's just put it in.

				// Make sure this isn't a group addition and we are *currently*
				// NOT looking at the group it is being added to.  This is to
				// correct a bug.
				if ($substitution->boolGroupAddition == true)
				{
					//adminDebug("Group addition for req:" . $courseRequirement->toString() . " curgid: $groupID");
					if ($substitution->courseRequirement->assignedToGroupID != $groupID)
					{
						//adminDebug("skipping $courseRequirement->subjectID $courseRequirement->courseNum");

						continue;
					}

				}


				//adminDebug($courseRequirement->toString() . " " . $courseRequirement->getHours());

				if ($boolPerformAssignment == true)
				{
					// If the courseRequirement's minHours are greater than
					// the substitution's hours, then we have to split the
					// coureRequirement into 2 pieces, and add the second piece just
					// after this one in the list.
					$courseSub = $substitution->courseListSubstitutions->getFirst();
					if ($courseRequirement->minHours*1 > $courseSub->hoursAwarded*1)
					{
						$remainingHours = $courseRequirement->minHours - $courseSub->hoursAwarded;
						//adminDebug(" original: " . $courseRequirement->toString() . " hrs: $courseRequirement->minHours, remaining hours: $remainingHours. sub awarded: $courseSub->hoursAwarded");

						$newCourseString = $courseRequirement->toDataString();
						$newCourse = new Course();
						$newCourse->loadCourseFromDataString($newCourseString);
						$newCourse->minHours = $newCourse->maxHours = $remainingHours;
						$newCourse->boolSubstitutionSplit = true;
						$newCourse->boolSubstitutionNewFromSplit = true;
						$newCourse->requirementType = $courseRequirement->requirementType;

						$courseRequirement->boolSubstitutionSplit = true;
						$courseRequirement->boolSubstitutionNewFromSplit = false;

						// Now, add this into the list, right after the courseRequirement.
						$currentI = $listRequirements->i;
						$listRequirements->insertAfterIndex($currentI, $newCourse);

					}

					$courseRequirement->courseListFulfilledBy = $substitution->courseListSubstitutions;

					$substitution->courseListSubstitutions->assignGroupID($groupID);
					$substitution->courseListSubstitutions->setHasBeenAssigned(true);
					$substitution->courseListSubstitutions->setBoolSubstitution(true);
					$substitution->courseListSubstitutions->setCourseSubstitution($courseRequirement, $substitution->remarks);
					$substitution->boolHasBeenApplied = true;


				}
				$count++;
				continue;
			}


			// Has the student taken this course requirement?
			if ($c = $student->listCoursesTaken->findBestMatch($courseRequirement, $courseRequirement->minGrade, true))
			{

        $hGetHours = $c->getHours();
				if ($c->boolGhostHour) {
				  // If this is a ghost hour, then $hGetHours would == 0 right now,
				  // instead, use the the adjusted value (probably 1).
				  $hGetHours = $c->hoursAwarded;
				  //adminDebug("hGetHours == " . $hGetHours);
				}			  
			  
				// Can we assign any more hours to this group?  Are we
				// out of hours, and should stop?
				if ($hoursAssigned >= $hoursRequired)
				{
					//adminDebug("out of hours, continuing. assigned: $hoursAssigned. req: $hoursrequired. ");
					continue;
				}

				// Will the hours of this course put us over the hoursRequired limit?
				if ($hoursAssigned + $c->hoursAwarded > $hoursRequired)
				{
					//adminDebug("right here $c->subjectID $c->courseNum.  Skipping $group->title");
					continue;
				}

				// Do not apply substitutionSplit courses to anything automatically.
				// They must be applied by substitutions.
				if ($c->boolSubstitutionNewFromSplit == true)
				{
					//adminDebug("skipping " . $c->toString());
					continue;
				}


				// Make sure the course meets min grade requirements.
				if (!$c->meetsMinGradeRequirementOf($courseRequirement))
				{
					//adminDebug("Bad min grade " . $c->toString());

					//adminDebug($courseRequirement->minGrade);


					continue;
				}

				if ($c->groupListUnassigned->isEmpty == false)
				{
					//adminDebug("~" . $c->toString() . " unassigned " . $c->groupListUnassigned->toString() . " cur group: $groupID");
				}

				// Has the course been unassigned from this group?
				if ($c->groupListUnassigned->findMatch($group))
				{
					//adminDebug("unassigned! " . $c->toString() . "");
					continue;
				}

				// Prereq checking would also go here.
				//	adminDebug("Examining: " . $c->toString());

				// Make sure $c is not being used in a substitution.
				if ($c->boolSubstitution == true)
				{
					//adminDebug("- - - don't use!  A sub!");
					continue;
				}

				if ($c->boolHasBeenAssigned != true)
				{//Don't count courses which have already been placed in other groups.
					//adminDebug("A match for $c->subjectID $c->courseNum ");

					// Has another version of this course already been
					// assigned?  And if so, are repeats allowed for this
					// course?  And if so, then how many hours of the
					// repeatHours have I used up?  If I cannot do any more
					// repeats, then quit.  Otherwise, let it continue...
					//adminDebug("rep hours: $courseRequirement->subjectID $courseRequirement->courseNum rep hours: $courseRequirement->repeatHours ");

					$courseListRepeats = $student->listCoursesTaken->getPreviousAssignments($c->courseID);


					if ($courseListRepeats->getSize() > 0)
					{
						//adminDebug("loading inside group: $groupID");
						// So, a copy of this course has been assigned more than once...
						// Get the total number of hours taken up by this course.
						$cc = $courseListRepeats->countHours();
						//adminDebug($cc);
						// have we exceeded the number of available repeatHours
						// for this course?
						if ($courseRequirement->repeatHours < 1)
						{
							$courseRequirement->loadDescriptiveData();
						}

						//if ($cc + $c->getHours() > $courseRequirement->repeatHours*1)
						if ($cc + $hGetHours > $courseRequirement->repeatHours*1)
						{
							// Do not allow the repeat.
							//adminDebug("kicking out. " . $courseRequirement->toString() . " $courseRequirement->repeatHours , cc: $cc from group $group->title");
							continue;
						}



					}

					// Basically--- if the most recent attempt fails
					// a min grade check, then tag all attempts as "unuseable"
					// so that they can't be used in other groups.  --
					// unless they are able to be repeated.  BARF!

					// Inc hoursAssigned, even if we aren't actually
					// performing an assignment.  This helps us accurately
					// calculate the count.
					
					$hoursAssigned = $hoursAssigned + $hGetHours;

					if ($boolPerformAssignment == true)
					{
						//$courseRequirement->courseFulfilledBy = $c;
						$courseRequirement->courseListFulfilledBy->add($c);
						$courseRequirement->grade = $c->grade;
						$courseRequirement->hoursAwarded = $c->hoursAwarded;
						$courseRequirement->boolGhostHour = $c->boolGhostHour;
						
						$c->boolHasBeenAssigned = true;
						//adminDebug(" -- -- assigning ... $courseRequirement->subjectID $courseRequirement->courseNum with " . $c->toString() );
						$c->requirementType = $courseRequirement->requirementType;
						$c->assignedToGroupID = $groupID;
						$group->hoursAssigned = $hoursAssigned;
						// Should check for:
						// Can it be assigned, based on the number of allowed course repeats?
						if ($courseRequirement->boolSpecifiedRepeat == true)
						{
							// $c is what they actually took.
							$c->boolSpecifiedRepeat = true;
							$c->specifiedRepeats = $courseRequirement->specifiedRepeats;
							//adminDebug($c->toString());
							$listRequirements->decSpecifiedRepeats($c);
						}
					}


					$count++;
				} else {
					//adminDebug(" .. .. .. skipping " . $c->toString());
				}
			}

		}


		//adminDebug($count);
		return $count;
	}



	function assignCoursesToSemesters()
	{
		// This method will look at the student's courses
		// and decide if they should be assigned to degree requirements
		// which have been spelled out in each semester.  This
		// is not where it looks into groups.
		//adminDebug("- - - - - - - doing semesters - - - - -- ");
		$this->degreePlan->listSemesters->resetCounter();
		while($this->degreePlan->listSemesters->hasMore())
		{
			$semester = $this->degreePlan->listSemesters->getNext();

			// Okay, let's look at the courses (not groups) in this
			// semester...
			//print "looking in semester $semester->semesterNum <br>";
			$this->assignCoursesToList($semester->listCourses, $this->student);

		}

		//adminDebug("- - - - - - - done w/ semesters - - - - -- ");

	}


	function getSubjectTitle($subjectID)
	{
		// From the subjectID, get the title.
		// Example: COSC = Computer Science.

		// Let's pull the needed variables out of our settings, so we know what
		// to query, because this is a non-FlightPath table.
		$tsettings = $GLOBALS["fpSystemSettings"]["extraTables"]["course_resources:subjects"];
		$tf = (object) $tsettings["fields"];  //Convert to object, makes it easier to work with.  
		$tableName = $tsettings["tableName"];
		
		$res = $this->db->dbQuery("SELECT * FROM $tableName
							WHERE $tf->subjectID = '?' LIMIT 1 ", $subjectID);
		$cur = $this->db->dbFetchArray($res);
		return trim($cur[$tf->title]);

	}



	function getAllCoursesInCatalogYear($catalogYear = "2006", $boolLoadDescriptiveData = false, $limitStart = 0, $limitSize = 0)
	{
		// Returns a CourseList object of all the
		// undergraduate courses in the
		// supplied catalogYear.

		$limLine = "";
		if ($limitSize > 0)
		{
			$limLine = " limit $limitStart, $limitSize ";
		}
		$rtnList = new CourseList();
		$cArray = array();
		$result = $this->db->dbQuery("SELECT * FROM courses
							WHERE 
								catalog_year = '?'
								AND course_num < '{$GLOBALS["fpSystemSettings"]["graduateLevelCourseNum"]}'
							ORDER BY subject_id, course_num
							$limLine
							", $catalogYear);

		while($cur = $this->db->dbFetchArray($result))
		{ 


			$course = new Course();
			$course->courseID = $cur["course_id"];
			$course->subjectID = $cur["subject_id"];
			$course->courseNum = $cur["course_num"];
			$course->minHours = $cur["min_hours"];
			$course->maxHours = $cur["max_hours"];

			if ($boolLoadDescriptiveData == true)
			{
				$course->loadDescriptiveData();
			}

			$rtnList->add($course);
		}

		return $rtnList;

	}

	function cacheCourseInventory($limitStart = 0, $limitSize = 4000)
	{
		// Load courses from the inventory into the inventory cache...
		// Attempt to load the course inventory cache...
		if ($courseInventory = unserialize($_SESSION["fpCacheCourseInventory"]))
		{
			$GLOBALS["fpCourseInventory"] = $courseInventory;
		}

		$result = $this->db->dbQuery("SELECT DISTINCT course_id FROM courses
							WHERE 
								course_num < '{$GLOBALS["fpSystemSettings"]["graduateLevelCourseNum"]}'
								LIMIT $limitStart, $limitSize
							");

		while($cur = $this->db->dbFetchArray($result))
		{
			$courseID = $cur["course_id"];

			$this->db->loadCourseDescriptiveData(null, $courseID);

		}

		// Should we re-cache the course inventory?  If there have been any changes
		// to it, then we will see that in a GLOBALS variable...
		if ($GLOBALS["cacheCourseInventory"] == true)
		{
			$_SESSION["fpCacheCourseInventory"] = serialize($GLOBALS["fpCourseInventory"]);
		}


	}


	function replaceMissingCourseInGroup($courseID, $groupID)
	{
		// Given a group in the degree plan, this will
		// make sure that course is actually in the group.  If it
		// is not, then it will add it in where it should be.
		// This is necessary because we have previously removed
		// courses which the student hadn't taken.  Well, if the
		// student was advised for a particular course in a group,
		// then that course probably was originally removed
		// from the group.  So, put it back in.

		//adminDebug("replace $courseID in group $groupID ...");

		// First, find the group.
		if (!$group = $this->degreePlan->findGroup($groupID))
		{
			adminDebug(" ~~ could not find group $groupID for replacemMissingCourseInGroup");
			return;
		}

		// Okay, now tell the group to replace the instance of this course
		// in the group.  This is made easy, because we have
		// the dbGroupRequirementID, which is the actual id from the
		// row in group_requirements that this course was advised from.
		$group->replaceMissingCourse($courseID);



	}


	function saveAdvisingSessionFromPost($facultyID = 0, $boolDraft = true)
	{
	  //var_dump($_POST);
		// This method will, only by looking at variables in the
		// POST, save an advising session into the database.
		$db = new DatabaseHandler();
		if ($facultyID == 0)
		{ // if none supplied, use the one from the session of
			// whomever is currently logged in.
			$facultyID = $_SESSION["fpUserID"];
		}

		$boolFoundUpdateMatch = false;
		$studentID = $this->student->studentID;
		$degreeID = $this->degreePlan->degreeID;
		$majorCode = $this->degreePlan->majorCode;
		$availableTerms = $GLOBALS["settingAvailableAdvisingTermIDs"];

		// Do we need to update the student's settings?
		if (trim($_POST["advisingUpdateStudentSettingsFlag"]) != "")
		{
			// We are to assume that the student's arraySettings
			// have already been updated by this point, so we will
			// simply convert them to XML and store in the database.
			$xml = fp_arrayToXml("settings", $this->student->arraySettings);
			$result = $db->dbQuery("REPLACE INTO student_settings
									(`student_id`,`settings_xml`,`datetime`)
									VALUES ('?','?', NOW() )	", $studentID, $xml);
			$db->addToLog("update_student_settings", "$studentID");

		}


		// Is there anything in "logAddition" which we should write to the log?
		if ($_POST["logAddition"] != "")
		{
			//adminDebug("add" . $_POST["logAddition"]);
			$temp = explode("_",$_POST["logAddition"]);
			if ($temp[0] == "changeTerm")
			{
				$db->addToLog("change_term","$studentID," . $temp[1]);
			}

			if ($temp[0] == "changeTrack")
			{

				$db->addToLog("change_track","$studentID," . $temp[1]);
			}


		}


		// If this user cannot advise, then just return right now.
		if ($_SESSION["fpCanAdvise"] != true)
		{
			return;
		}


		// First, create a new entry in the advising_sessions table,
		// so we can get the advisingSessionID.

		// But before we can do that, we look for an existing entry
		// which matches this.  If we find it, we delete it so the
		// new one will display instead.
		// Only delete if its a draft copy!
		$isDraft = intval($boolDraft);
		$isWhatIf = intval($this->boolWhatIf);

		// Since we only want one draft copy per term/per student,
		// let's delete
		// any draft copies already in existence, if we are saving a draft.
		$result = $db->dbQuery("DELETE FROM advising_sessions
									WHERE `student_id`='?'
									AND `is_draft`='1'
									AND `degree_id`='?'
									AND `is_whatif`='?' ", $studentID, $degreeID, $isWhatIf);


		// The first thing we need to do is go through the availableTerms,
		// create new entries for them in the table, and store what their
		// session ID's are in an array.
		$advisingSessionIDArray = array();
		$advisingSessionIDArrayCount = array();

		$temp = explode(",",$availableTerms);
		foreach ($temp as $termID)
		{
			$termID = trim($termID);

			if ($termID == "") { continue; }

			// Okay, now create a new entry in the system for that term.
			// We create entries for all available terms, whether we
			// are going to use them later or not.
			$result = $db->dbQuery("INSERT INTO advising_sessions
								(`student_id`,`faculty_id`,`term_id`,`degree_id`,
								`major_code`,
								`catalog_year`,`datetime`,`is_whatif`,`is_draft`)
								VALUES
								('?', '?','?','?','?','?',NOW(),'?','?') 
								", $studentID, $facultyID,$termID,$degreeID, $majorCode, $catalogYear, $isWhatIf, $isDraft);
			$advisingSessionID = mysql_insert_id();
			$advisingSessionIDArray[$termID] = $advisingSessionID;
			$advisingSessionIDArrayCount[$termID] = 0;
		}
		//adminDebug($advisingSessionID);


		$wi = "";
		if ($isWhatIf == "1"){$wi = "_whatif";}

		if ($boolDraft)
		{
			$db->addToLog("save_adv_draft$wi", "$studentID,majorCode:$majorCode");
		} else {
			$db->addToLog("save_adv_active$wi", "$studentID,majorCode:$majorCode");
		}


		// Go through the POST, looking for the
		// phrase "advisecourse_" in the name of the variables.
		// There should be one of these for every course that was
		// on the page.  It looks like this:
		// advisecourse_courseID_semesterNum_groupID_varHours_randomID
		foreach($_POST as $key => $value)
		{
			if (!strstr($key,"advisecourse_"))
			{ // Skip vars which don't have this as part of the name.
				//adminDebug("skipping $key");
				continue;
			}
			if ($value != "true")
			{ // This means the course was *not* advised to be taken,
				// so, skip it.
				continue;
			}

			//adminDebug($key);
			$temp = explode("_",$key);
			$courseID = trim($temp[1]);
			$semesterNum = trim($temp[2]);
			$groupID = trim($temp[3]);
			$varHours = trim($temp[4]);
			$randomID = trim($temp[5]);
			$advisedTermID = trim($temp[6]);
			$dbGroupRequirementID = trim($temp[7]);

			$advisingSessionID = $advisingSessionIDArray[$advisedTermID];

			$newCourse = new Course($courseID);
			$newCourse->loadDescriptiveData();
			$entryValue = "$newCourse->subjectID~$newCourse->courseNum";


			//adminDebug("$advisedTermID $entryValue");

			// Some particular course should be updated.  Possibly this one.
			// Updates happen because of a student changing the
			// variable hours, for example.
			if (trim($_POST["updatecourse"]) != "")
			{
				$temp2 = explode("_",trim($_POST["updatecourse"]));

				$tcourseID = $temp2[0];
				$tgroupID = $temp2[1] * 1;
				$tsemesterNum = $temp2[2] * 1;
				$tvarHours = $temp2[3];
				$trandomID = $temp2[4];
				$tadvisedTermID = $temp2[5];

				// Do we have a match?
				if ($courseID == $tcourseID && $randomID == $trandomID)
				{
					// We have a match, so update with the new information.
					$varHours = $tvarHours;
					$boolFoundUpdateMatch = true;
				}


			}


			if ($groupID != 0)
			{
				$this->replaceMissingCourseInGroup($courseID, $groupID);
			}


			// Okay, write it to the table...
			$result = $db->dbQuery("INSERT INTO advised_courses
									(`advising_session_id`,`course_id`,
									`entry_value`,`semester_num`,
										`group_id`,`var_hours`,`term_id`)
									VALUES
									('?','?','?','?','?','?','?')
									", $advisingSessionID,$courseID,$entryValue,$semesterNum,$groupID,$varHours,$advisedTermID);

			$advisingSessionIDArrayCount[$advisedTermID]++;

		}

		// Did we have to perform an update-- but no course was found?
		if (trim($_POST["updatecourse"]) != "" && $boolFoundUpdateMatch == false)
		{
			// This means that the course was probably on the bare
			// degree program, and not already checked for advising.  So,
			// let's add it to the advised_courses table, so it DOES
			// get checked for advising.
			$temp2 = explode("_",trim($_POST["updatecourse"]));
			$courseID = $temp2[0];
			$groupID = $temp2[1] * 1;
			$semesterNum = $temp2[2] * 1;
			$varHours = $temp2[3];
			$advisedTermID = $temp2[5];

			$advisingSessionID = $advisingSessionIDArray[$advisedTermID];

			$result = $db->dbQuery("INSERT INTO advised_courses
									(`advising_session_id`,`course_id`,`semester_num`,
										`group_id`,`var_hours`,`term_id`)
									VALUES
									('?','?','?','?','?','?')
									", $advisingSessionID,$courseID,$semesterNum,$groupID,$varHours,$advisedTermID);

			$advisingSessionIDArrayCount[$advisedTermID]++;

			if ($groupID != 0)
			{
				$this->replaceMissingCourseInGroup($courseID, $groupID);
			}


		}


		//------------------------------------------------------
		//
		//             Substitutions...
		//
		//-------------------------------------------------------
		if (trim($_POST["savesubstitution"]) != "")
		{
			$temp = explode("_",trim($_POST["savesubstitution"]));
			$courseID = $temp[0];  // required course
			$groupID = $temp[1] * 1;
			$semesterNum = $temp[2] * 1;

			$subCourseID = $temp[3];
			$subTermID = $temp[4];
			$subTransferFlag = $temp[5];
			$subHours = $temp[6];
			$subAddition = $temp[7];
			$subRemarks = urldecode($temp[8]);

			if ($subAddition == "true")
			{
				$courseID = 0;
			}

			//adminDebug($subCourseID);
			// Figure out the entry values for the required course & sub course...
			$requiredEntryValue = $subEntryValue = "";
			if ($courseID > 0)
			{
				$newCourse = new Course($courseID);
				$newCourse->loadDescriptiveData();
				$requiredEntryValue = "$newCourse->subjectID~$newCourse->courseNum";
			}

			if ($subTransferFlag != 1)
			{
				$newCourse = new Course($subCourseID);
				$newCourse->loadDescriptiveData();
				$subEntryValue = "$newCourse->subjectID~$newCourse->courseNum";

			}

			if ($groupID != 0 && $courseID != 0)
			{
				$this->replaceMissingCourseInGroup($courseID, $groupID);
			}


			$result = $db->dbQuery("INSERT INTO student_substitutions
									(`student_id`,`faculty_id`,`required_course_id`,`required_entry_value`,
									`required_group_id`,`required_semester_num`,`sub_course_id`,`sub_entry_value`,
									`sub_term_id`,`sub_transfer_flag`,`sub_hours`,`sub_remarks`,`datetime`)
									VALUES
									('?','?','?','?','?','?','?','?','?','?','?','?',NOW())
									", $studentID,$facultyID,$courseID,$requiredEntryValue,$groupID,$semesterNum,$subCourseID,$subEntryValue,$subTermID,$subTransferFlag,$subHours,$subRemarks);

			$db->addToLog("save_substitution", "$studentID,groupID:$groupID,insertID:" . mysql_insert_id());

		}


		if (trim($_POST["removesubstitution"]) != "")
		{
			$temp = explode("_",trim($_POST["removesubstitution"]));
			$subID = trim($temp[0]) * 1;

			$result = $db->dbQuery("UPDATE student_substitutions
									SET `delete_flag`='1'
									WHERE `id`='?'	", $subID);

			$db->addToLog("remove_substitution", "$studentID,subID:$subID");

		}



		//------------------------------------------------------
		//
		//             Group Unassignments
		//
		//-------------------------------------------------------
		if (trim($_POST["unassign_group"]) != "")
		{
			$temp = explode("_",trim($_POST["unassign_group"]));
			$courseID = $temp[0];
			$termID = $temp[1];
			$transferFlag = $temp[2];
			$groupID = $temp[3];

			$result = $db->dbQuery("INSERT INTO student_unassign_group
									(`student_id`,`faculty_id`,`course_id`,
									`term_id`,`transfer_flag`,`group_id`,
									`datetime`)
									VALUES
									('?','?','?','?','?','?',NOW())
									", $studentID,$facultyID,$courseID,$termID,$transferFlag,$groupID);

			$db->addToLog("save_unassign_group", "$studentID,groupID:$groupID");

		}

		if (trim($_POST["restore_unassign_group"]) != "")
		{
			$temp = explode("_",trim($_POST["restore_unassign_group"]));
			$unassignID = trim($temp[0]) * 1;

			//adminDebug($unassignID);
			//die;

			$result = $db->dbQuery("UPDATE student_unassign_group
									SET `delete_flag`='1'
									WHERE `id`='?' ", $unassignID);

			$db->addToLog("restore_unassign_group", "$studentID,unassignID:$unassignID");

		}


		//------------------------------------------------------
		//
		//             Transfer EQV Unassignments
		//
		//-------------------------------------------------------
		if (trim($_POST["unassign_transfer_eqv"]) != "")
		{
			$temp = explode("_",trim($_POST["unassign_transfer_eqv"]));
			$courseID = $temp[0];

			$result = $db->dbQuery("INSERT INTO student_unassign_transfer_eqv
									(`student_id`,`faculty_id`,`transfer_course_id`,
									`datetime`)
									VALUES
									('?','?','?',NOW())
									", $studentID, $facultyID, $courseID);

			$db->addToLog("save_unassign_transfer", "$studentID,courseID:$courseID");

		}

		if (trim($_POST["restore_transfer_eqv"]) != "")
		{
			$temp = explode("_",trim($_POST["restore_transfer_eqv"]));
			$unassignID = trim($temp[0]) * 1;

			$result = $db->dbQuery("UPDATE student_unassign_transfer_eqv
									SET `delete_flag`='1'
									WHERE `id`='?' ", $unassignID);

			$db->addToLog("restore_unassign_transfer", "$studentID,unassignID:$unassignID");

		}



		////////////////////////////////////////////////////
		///////  Cleanup !////////////////////////////////
		////////////////////////////////////////////////////
		// If any of the advisingSessions we created earlier
		// are blank, we should FLAG them, so they will not
		// show up under the student's history.
		// Only flag non-draft empty ones.  If they are draft,
		// let them be.
		// We just look at $advisingSessionIDArrayCount[] to see
		// if any of the counts are still 0.  If they are, delete
		// that advisingSessionID from the table.
		if ($isDraft == 0)
		{
			foreach ($advisingSessionIDArray as $termID => $advisingSessionID)
			{
				if ($advisingSessionIDArrayCount[$termID] == 0)
				{

					// This one is blank!  Delete it!
					$res = $db->dbQuery("UPDATE advising_sessions
								SET `is_empty`='1'	
								WHERE `advising_session_id`='?' ", $advisingSessionID);
					$advisingSessionIDArray[$termID] = "";
				}
			}
		}

		return $advisingSessionIDArray;


	}


	function loadAdvisingSessionfromDatabase($facultyID = 0, $termID = "", $boolWhatIf = false, $boolDraft = true, $advisingSessionID = 0)
	{
		// This method will load an advising session for a particular
		// student, and modify the degree plan object to reflect
		// the advisings.
    $db = new DatabaseHandler();
		$isWhatIf = "0";
		$isDraft = "0";
		if ($boolWhatIf == true){$isWhatIf = "1";}
		if ($boolDraft == true){$isDraft = "1";}

		$degreeID = $this->degreePlan->degreeID;
		$studentID = $this->student->studentID;
		$availableTerms = $GLOBALS["settingAvailableAdvisingTermIDs"];



		$advisingSessionLine = " `advising_session_id`='$advisingSessionID' ";
		// First, find the advising session id...
		if ($advisingSessionID < 1 && $availableTerms == "")
		{
			$advisingSessionID = $this->db->getAdvisingSessionID($facultyID,$studentID,$termID,$degreeID,$boolWhatIf,$boolDraft);
			$advisingSessionLine = " `advising_session_id`='$advisingSessionID' ";


		} else if ($advisingSessionID < 1 && $availableTerms != "")
		{
			// Meaning, we are looking for more than one term.
			$advisingSessionLine = "(";
			$temp = explode(",",$availableTerms);
			for ($t = 0; $t < count($temp); $t++)
			{
				$tID = trim($temp[$t]);

				$asid = $this->db->getAdvisingSessionID($facultyID,$studentID,$tID,$degreeID,$boolWhatIf,$boolDraft);
				if ($asid != 0)
				{
					$advisingSessionLine .= " advising_session_id='$asid' || ";
				}
			}
			// Take off the last 3 chars...
			$advisingSessionLine = substr($advisingSessionLine, 0, -3);
			$advisingSessionLine .= ")";
			if ($advisingSessionLine == ")")
			{  // Found NO previously advised semesters, so just
				// use a dummy value which guarantees it pulls up nothing.
				$advisingSessionLine = " advising_session_id='-99999'";
			}

		}

		// Now, look up the courses they were advised to take.
		$query = "SELECT * FROM advised_courses
								WHERE 
								 $advisingSessionLine
								ORDER BY `id` ";
		//adminDebug($query);
		$result = $db->dbQuery($query);
		while($cur = $db->dbFetchArray($result))
		{
			$courseID = trim($cur["course_id"]);
			$semesterNum = trim($cur["semester_num"]);
			$groupID = trim($cur["group_id"]);
			$varHours = trim($cur["var_hours"]);
			$advisedTermID = trim($cur["term_id"]);
			$id = trim($cur["id"]);
			//adminDebug("course $courseID sem:$semesterNum group:$groupID $varHours");

			// Add this course to the generic list of advised courses.  Useful
			// if we are using this to pull up an advising summary.
			$tempCourse = new Course($courseID);
			$tempCourse->advisedHours = $varHours;
			$this->courseListAdvisedCourses->add($tempCourse);

			if ($semesterNum == -88)
			{
				// This was a courses added by the advisor.
				$this->assignCourseToCoursesAddedList($courseID, $varHours, $id, $advisedTermID);
				continue;
			}

			// Now, we need to modify the degreePlan object to
			// show these advisings.
			if ($courseList = $this->degreePlan->findCourses($courseID, $groupID, $semesterNum))
			{
				//adminDebug("in here");
				//adminDebug(count($courseList->arrayList));

				// This course may exist in several different branches of a group, so we need
				// to mark all the branches as having been advised to take.  Usually, this CourseList
				// will probably only have 1 course object in it.  But, better safe than sorry.
				$courseList->resetCounter();
				if ($course = $courseList->getNext())
				{
					//adminDebug($course->toString());
					// make sure the hour count has been loaded correctly.
					if ($course->getCatalogHours() < 1)
					{
						$course->loadDescriptiveData();
					}

					// Let's start by looking at the first course.  Is it
					// supposed to be repeated?
					if ($course->boolSpecifiedRepeat==true
					&& $course->specifiedRepeats >= 0 )
					{
						// This is a course which is supposed to be repeated.
						// We need to cycle through and find an instance
						// of this course which has not been advised yet.
						//adminDebug("Specified to be repeated. $course->specifiedRepeats.");


						$courseList->resetCounter();
						while($courseList->hasMore())
						{
							$course = $courseList->getNext();

							// make sure the hour count has been loaded correctly.
							if ($course->getCatalogHours() < 1)
							{
								$course->loadDescriptiveData();
							}

							//if ($course->boolAdvisedToTake != true && !is_object($course->courseFulfilledBy))
							if ($course->boolAdvisedToTake != true && $course->courseListFulfilledBy->isEmpty == true)
							{
								//adminDebug("here for " . $course->toString());
								// Okay, this course is supposed to be taken/advised
								// more than once.  So, I will mark this one as
								// advised, and then break out of the loop, since
								// I don't want to mark all occurances as advised.
								$course->boolAdvisedToTake = true;
								$course->assignedToSemesterNum = $semesterNum;
								//adminDebug("Putting it in $semesterNum." . $course->toString(" ",true));
								$course->assignedToGroupID = $groupID;
								$course->advisedHours = $varHours;
								$course->advisedTermID = $advisedTermID;
								$course->dbAdvisedCoursesID = $id;
								$courseList->decSpecifiedRepeats($course);
								break;
							}
						}
						continue;  // Go to the next advised course.
					}
				}

				//////////////////////////////
				// We're here, because it was not a repeatable course.
				// ** We should only go through THIS loop once!  So,
				// we will break after we make our assignment.
				$courseList->resetCounter();
				while($courseList->hasMore())
				{
					$course = $courseList->getNext();
					//adminDebug($course->toString());
					// make sure the hour count has been loaded correctly.
					if ($course->getCatalogHours() < 1)
					{
						$course->loadDescriptiveData();
					}

					// make sure it has not already been advised to take.
					// Would occur if the same course is specified more
					// than once in a semester.
					if ($course->boolAdvisedToTake == true)
					{
						continue;
					}

					// Has this course already been fulfilled by something?
					// If so, we cannot attempt to say it's been advised!
					if (!$course->courseListFulfilledBy->isEmpty)
					{
						// meaning, this course HAS been fulfilled.
						// So, let's move this advising to the "added by advisor"
						// spot.
						$this->assignCourseToCoursesAddedList($courseID, $varHours, $id, $advisedTermID);
						break;
					}
					
					
					$course->boolAdvisedToTake = true;
					$course->assignedToSemesterNum = $semesterNum;
					//adminDebug("NOW Putting it in $semesterNum." . $course->toString(" ",true));
					$course->assignedToGroupID = $groupID;
					$course->advisedHours = $varHours;
					$course->advisedTermID = $advisedTermID;
					$course->dbAdvisedCoursesID = $id;
					if ($course->requiredOnBranchID > 0)
					{
						// In other words, this course was found on a branch, so we need
						// to increment that branch's countOfMatches.
						if ($branch = $this->degreePlan->findGroup($course->requiredOnBranchID))
						{
							//adminDebug($branch->toString());
							$branch->countOfMatches++;
						} else {
							adminDebug("Error: Could not find branch.");
						}

					}

					// We should only be in this loop once, so let's
					// break after we make our assignment.
					break;

					//print_pre($course->toString());
				}
				//adminDebug("out of loop");

			}

		}

		// Now, what we need to do is tell the DegreePlan to re-sort its
		// group's course lists, so that the advised courses are lower
		// than the fulfilled courses.

		//$this->degreePlan->sortGroupsFulfilledFirst();
		//print_pre($this->degreePlan->listGroups->toString());

	} // function loadAdvisingSessionFromDatabase


	function splitRequirementsBySubstitutions()
	{
		// Go through all the required courses on the degree plan,
		// and if there is a partial substitution specified in the student's
		// list of substitutions, then split that requirement into 2 courses,
		// one with enough hours to satisfy the sub, and the remaining hours.
		$degreePlan = $this->degreePlan;
		$student = $this->student;

		$student->listSubstitutions->resetCounter();
		while($student->listSubstitutions->hasMore())
		{
			$substitution = $student->listSubstitutions->getNext();

			$courseRequirement = $substitution->courseRequirement;
			$courseSub = $substitution->courseListSubstitutions->getFirst();

			// Check to see if the courseSub's hoursAwarded are less than the
			// courseRequirement's min hours...
			if ($courseRequirement->minHours > $courseSub->hoursAwarded)
			{
				// Meaning the original course requirement is not being
				// fully satisfied by this substitution!  The original
				// course requirement has hours left over which must be
				// fulfilled somehow.
				$remainingHours = $courseRequirement->minHours - $courseSub->hoursAwarded;
				//adminDebug($courseRequirement->toString() . " remaining hours: $remainingHours");
				// This means that the course requirement needs to be split.
				// So, find this course in the degree plan.
				$requiredCourseID = $courseRequirement->courseID;
				$requiredGroupID = $courseRequirement->assignedToGroupID;
				$requiredSemesterNum = $courseRequirement->assignedToSemesterNum;

				if ($foundCourses = $degreePlan->findCourses($requiredCourseID, $requiredGroupID, $requiredSemesterNum))
				{
					//print_pre($foundCourses->toString());

				}

			}



		}


	}



	function assignCourseToCoursesAddedList($courseID, $varHours = 0, $dbAdvisedCoursesID = 0, $advisedTermID = 0)
	{
		// Set the supplied course as "advised to take" in the degree plan's
		// special added courses group, which is number -88.

		$course = new Course($courseID, false, $this->db);
		$course->boolAdvisedToTake = true;
		$course->assignedToSemesterNum = -88;
		$course->assignedToGroupID = -88;
		$course->advisedHours = $varHours;
		$course->dbAdvisedCoursesID = $dbAdvisedCoursesID;
		$course->advisedTermID = $advisedTermID;

		if ($group = $this->degreePlan->findGroup(-88))
		{
			$group->listCourses->add($course);
		}

		// Done!
	}





}

?>