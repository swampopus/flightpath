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

class _Group
{
	public $title, $iconFilename, $groupID, $requirementType, $minGrade, $groupName;
	public $hoursRequired, $hoursRemaining, $hoursFulfilled, $hoursFulfilledForCredit;
	public $hoursRequiredByType;
	public $assignedToSemesterNum, $boolPlaceholder, $dataEntryComment;
	public $listCourses, $listGroups, $db, $countOfMatches, $boolWinningBranch;
	public $catalogYear;
	public $priority;
	//////////////////
	///  From the database...
	public $dbUnassignGroupID, $dbDeleteFlag;

	///////////////
	///  Used with in-system logic....
	public $hoursAssigned;
	public $boolUseDraft;


	/**
	* $title			"Free Electives","Core Fine Arts", etc.
	* $iconFilename		monalisa.gif, tower1.gif, etc.
	* $groupID		ID of the group in the db table.
	*
	* $type			Major, Supporting, Core, etc.
	* $minGrade		This is if the group itself has a min grade requirement.
	* 				Ex: B,C etc.
	* $listCourses	This is a CourseList of courses.  These are
	* 				the courses which are actually required by the group.
	* 				If individual courses have their own min grade requirements,
	* 				or what have you, that only refer to this group, then they
	* 				would be put in here.
	* $listGroups	This is a list of groups that belong within this group.
	* 				Used when you have branching.  Potentially can be quite
	* 				complicated, since each group in the list can also have
	*				subgroups.
	**/


	function __construct($groupID = "", DatabaseHandler $db = NULL, $semesterNum = -1, $arraySignificantCourses = false, $boolUseDraft = false)
	{
		$this->groupID = $groupID;
		$this->assignedToSemesterNum = $semesterNum;
		$this->countOfMatches = 0;
		$this->hoursAssigned = 0;
		$this->listCourses = new CourseList();
		$this->listGroups = new GroupList();
		$this->boolUseDraft = $boolUseDraft;
		$this->hoursRequiredByType = array();
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


		if ($groupID != "")
		{
			$this->boolPlaceholder = false;
			$this->loadGroup(true, $arraySignificantCourses);
		}

	}



	function assignToSemester($semesterNum)
	{
		$this->assignedToSemesterNum = $semesterNum;
		$tempI = $this->listCourses->i;
		$this->listCourses->resetCounter();
		while($this->listCourses->hasMore())
		{
			$c = $this->listCourses->getNext();
			$c->assignedToSemesterNum = $semesterNum;
		}
		$this->listCourses->i = $tempI;
	}


	function resetListCounters()
	{
		// Resets the counters on all groups and course lists
		// in this group.
		$this->listCourses->resetCounter();
		$this->listGroups->resetCounter();
	}

	function assignMinGrade($minGrade)
	{
		// Assign every course in the group to have this particular min grade.
		$this->minGrade = $minGrade;

		$this->listCourses->assignMinGrade($minGrade);
		$this->listGroups->assignMinGrade($minGrade);


	}

	function getHoursRemaining($semesterNum = -1)
	{
		// returns hor many hours are left for this group.

		return ($this->hoursRequired - $this->getFulfilledHours(true, true, false, $semesterNum));
	}

	function loadGroup($boolLoadSignificantOnly = true, $arraySignificantCourses = false, $boolReloadMissingOnly = false)
	{
		$groupID = $this->groupID;
		$this->loadDescriptiveData();
		if ($this->dbDeleteFlag == 1)
		{
			return;
		}

		//var_dump($arraySignificantCourses);
		//adminDebug(is_array($arraySignificantCourses));

		$boolSignificantCoursesEmpty = true;
		if (is_array($arraySignificantCourses))
		{
			$boolSignificantCoursesEmpty = false;
		}

		if ($boolReloadMissingOnly == true)
		{
			// We are only going to load the *missing* courses from
			// this group.  So, begin by getting an array of what is
			// not missing.

			$arrayGroupRequirementIDs = $this->listCourses->getGroupRequirementIDArray();

			//var_dump($arrayGroupRequirementIDs);

		}

		//adminDebug("ii");
		$tableName = "group_requirements";
		if ($this->boolUseDraft) {$tableName = "draft_$tableName";}
		
		$res = $this->db->dbQuery("SELECT * FROM $tableName
							WHERE group_id = '?'	", $this->groupID);
		while ($cur = $this->db->dbFetchArray($res))
		{

			$id = $cur["id"];
			$courseID = $cur["course_id"]*1;


			if ($cur["course_id"]*1 > 0)
			{
				//adminDebug($boolSignificantCoursesEmtpy);
				if ($boolLoadSignificantOnly == true && $boolSignificantCoursesEmpty == false)
				{
					// If this course_id is NOT in the array of significant courses
					// (that the student took or has transfer credit or subs for)
					// then skip it.  Never add it to the group.
					//adminDebug($arraySignificantCourses[$cur["course_id"]]);
					if ($arraySignificantCourses[$cur["course_id"]] != true)
					{// course was not in there, so skip!
						//adminDebug("skip {$cur["course_id"]} ");
						continue;
					}


				}


				// A course is the next requirement.
				for ($t = 0; $t <= $cur["course_repeats"]; $t++)
				{ // Add in the specified repeats for this group...
					// This will usually only go through the loop once.

					//adminDebug("looking at " . $cur["course_id"]);
					//$courseC = new Course($cur["course_id"], false, $this->db);
					$useID = $id . "_rep_$t";

					if ($boolReloadMissingOnly == true)
					{
						// Only load this course if it is missing from the group.
						// Read the reloadMissingCourses() method for an explanation
						// of why we should want to do this.
						// Basically, check all the courses in the current
						// listCourses object for a dbGroupRequirementID of $id.
						// Only proceed if $id was NOT found.

						if ($arrayGroupRequirementIDs[$useID] == true)
						{
							//adminDebug("skipping $useID - $courseID");
							continue;
						}
					}

					//adminDebug("x1");
					$courseC = new Course();
					$courseC->boolUseDraft = $this->boolUseDraft;
					$courseC->courseID = $cur["course_id"];
					$courseC->dbGroupRequirementID = $useID;
					$courseC->db = $this->db;
					$courseC->catalogYear = $this->catalogYear;
					//$courseC->loadDescriptiveData();
					//adminDebug($courseC->toString());
					//adminDebug("x2");
					//adminDebug($courseC->toString());
					$courseC->assignedToGroupID = $groupID;
					$courseC->assignedToSemesterNum = $this->assignedToSemesterNum;

					$courseC->specifiedRepeats = $cur["course_repeats"];
					if ($cur["course_repeats"] > 0)
					{
						$courseC->boolSpecifiedRepeat = true;
					}

					$courseC->minGrade = trim(strtoupper($cur["course_min_grade"]));
					if ($courseC->minGrade == "")
					{ // By default, all courses have a
						// min grade requirement of D.
						$courseC->minGrade = "D";
					}


					$this->listCourses->add($courseC);
					//$this->listCourses->arrayList[] = $courseC;
					//adminDebug("adding " . $courseC->toString());
					//$this->listCourses->arrayList[] = $courseC;
				}


			}
			//$this->listCourses->resetCounter();
			//adminDebug("ii");
			if ($cur["child_group_id"]*1 > 0)
			{
				// Another group is the next requirement (its a branch)
				if ($boolReloadMissingOnly == true)
				{ // Since we are reloading courses, this subgroup is already
					// part of this group, so do not re-create it, just find it
					// and reload it's missing courses.
					$tempG = new Group();
					$tempG->boolUseDraft = $this->boolUseDraft;
					$tempG->groupID = $cur["child_group_id"];
					if ($groupG = $this->listGroups->findMatch($tempG))
					{
						$groupG->reloadMissingCourses();
					} else {adminDebug("could not find sub group to reload!");}
				} else {
					// This is a brand-new sub group, so create it
					// and add it to this group.
					$groupG = new Group($cur["child_group_id"],null,$this->assignedToSemesterNum, $arraySignificantCourses, $this->boolUseDraft);
					$this->listGroups->add($groupG);
				}
			}
		}

	}


	function reloadMissingCourses()
	{
		// This function will go through the group and reload
		// any courses which are missing from the group object,
		// but are spelled out in the database table.
		// This is used after we have loaded a group from
		// cache (because the cached group only contains
		// courses which the student has taken).

		$this->loadGroup(false, "", true);

	}

	function replaceMissingCourse($courseID, $dbGroupRequirementID="")
	{
		// replace courseID in this group, if it is missing.

		$this->db = new DatabaseHandler();


		$tableName = "group_requirements";
		if ($this->boolUseDraft) {$tableName = "draft_$tableName";}

		// Look for all instances of this course in the group's base list...
		$res = $this->db->dbQuery("SELECT * FROM $tableName
									WHERE `group_id`='?'
									AND `course_id`='?' ", $this->groupID, $courseID);
		while ($cur = $this->db->dbFetchArray($res))
		{
			$id = $cur["id"];

			for ($t = 0; $t <= $cur["course_repeats"]; $t++)
			{
				$course = new Course($courseID,false,$db, false, "", $this->boolUseDraft);
				$useID = $id . "_rep_$t";
				// Make sure the group does not already have this requirementID...
				if ($this->listCourses->containsGroupRequirementID($useID))
				{
					continue;
				}

				$course->assignedToGroupID = $this->groupID;
				$course->dbGroupRequirementID = $useID;
				$course->specifiedRepeats = $cur["course_repeats"];
				if ($cur["course_repeats"] > 0)
				{
					$course->boolSpecifiedRepeat = true;
				}

				//adminDebug(" -- found it. replacei $this->groupID ");
				$this->listCourses->add($course);
			}
		}

		// Now, go through all of the group's branches and
		// do the same thing.
		$this->listGroups->resetCounter();
		while($this->listGroups->hasMore())
		{
			$g = $this->listGroups->getNext();
			$g->replaceMissingCourse($courseID);
		}




	}


	function loadDescriptiveData()
	{

		if ($db == NULL)
		{
			$this->db = getGlobalDatabaseHandler();
		}

		$tableName = "groups";
		if ($this->boolUseDraft) {$tableName = "draft_$tableName";}
		// Load information about the group's title, icon, etc.
		$res = $this->db->dbQuery("SELECT * FROM $tableName
							WHERE group_id = '?' ", $this->groupID);
		$cur = $this->db->dbFetchArray($res);
		$this->title = trim($cur["title"]);
		$this->iconFilename = trim($cur["icon_filename"]);
		$this->groupName = trim($cur["group_name"]);
		$this->dataEntryComment = trim($cur["data_entry_comment"]);
		$this->priority = trim($cur["priority"]);
		$this->definition = trim($cur["definition"]);
		$this->dbDeleteFlag = trim($cur["delete_flag"]);
		$this->catalogYear = trim($cur["catalog_year"]);


		if ($this->groupID == -88)
		{
			$this->title = "Add an Additional Course";
		}


	}


	function getFulfilledHours($boolCheckSubgroups = true, $boolCountAdvised = true, $boolRequireHasBeenDisplayed = false, $onlyCountSemesterNum = -1, $boolIgnoreEnrolled = false)
	{
		// Returns how many hours have been used by the
		// course fulfillments for this group...
		$count = 0;
		// if onlyCountSemesterNum != -1, then we will only count courses
		// who have their "assignedToSemesterNum" = $onlyCountSemesterNum.

		//print_pre($this->toString());
		$this->listCourses->resetCounter();
		while($this->listCourses->hasMore())
		{
			$c = $this->listCourses->getNext();
			//adminDebug("checking " . $c->toString() . " sn:" . $c->assignedToSemesterNum );
			if ($onlyCountSemesterNum != -1 && $c->assignedToSemesterNum != $onlyCountSemesterNum)
			{
				// Only accept courses assigned to a particular semester.
				//adminDebug("wrong sem. skipping " . $c->toString());
				continue;
			}

			
			if (is_object($c->courseListFulfilledBy) && !($c->courseListFulfilledBy->isEmpty))
			{
				if ($boolIgnoreEnrolled == true)
				{
					// Only allow it if it has been completed.
					if ($c->courseListFulfilledBy->getFirst()->isCompleted() == false)
					{
						continue;
					}
				}



				if (!$boolRequireHasBeenDisplayed)
				{ // The course does not have to have been displayed on the page yet.
					$count = $count + $c->courseListFulfilledBy->countHours("", false, false);
					//adminDebug('here');
				} else {
					if ($c->courseListFulfilledBy->getFirst()->boolHasBeenDisplayed == true)
					{
						//adminDebug("here");
						$count = $count + $c->courseListFulfilledBy->countHours("", false, false);
				
						//adminDebug($c->toString());
						//adminDebug($count);

					}
				}
			} else if ($c->boolAdvisedToTake && $boolCountAdvised == true)
			{
        $h = $c->getHours(); 
				$count = $count + $h;
			}

		}

		if ($boolCheckSubgroups == true)
		{
			// If there are any subgroups for this group, then run
			// this function for each group as well.
			$this->listGroups->resetCounter();
			while($this->listGroups->hasMore())
			{

				$g = $this->listGroups->getNext();
				$gc = $g->getFulfilledHours(true, $boolCountAdvised, $boolRequireHasBeenDisplayed, $onlyCountSemesterNum, $boolIgnoreEnrolled);
				//print_pre($g->toString());
				//adminDebug($gc . " in sem $onlyCountSemesterNum");
				$count = $count + $gc;
			}
		}

		return $count;

	}


	function equals(Group $group)
	{
		if ($this->groupID == $group->groupID)
		{
			return true;
		}

		return false;

	}



	function toString()
	{
		$rtn = "";

		$rtn .= "    Group: $this->groupID | $this->title $this->catalogYear ($this->hoursRequired hrs req.)\n    {\n";
		if (!$this->listCourses->isEmpty)
		{
			$rtn .= $this->listCourses->toString();
		}

		if (!$this->listGroups->isEmpty)
		{
			$rtn .= $this->listGroups->toString();
		}

		$rtn .= "    } \n";

		return $rtn;
	}


	function findCourses(Course $course)
	{
		// Return a CourseList of all the Course objects
		// which are in this group that match
		$rtnCourseList = new CourseList();

		if ($objList = $this->listCourses->findAllMatches($course))
		{
			$objList->resetCounter();
			while($objList->hasMore())
			{
				$c = $objList->getNext();
				$c->requiredOnBranchID = $this->groupID;
			}
			$rtnCourseList->addList($objList);
			return $rtnCourseList;
		}

		return false;

	}



} // end class Group

?>