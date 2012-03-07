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

require_once("ObjList.php");

class _CourseList extends ObjList
{
	// This inherits most of its classes from ObjList,
	// but, it has to be able to do special functions
	// specific to Courses.  Use parent:: to access
	// a parent function within ObjList.
	// Example: parent::indexOf();
	//public $arrayCourseIDList = array();

	/**
	 * Used to cast a regular ObjList object into a CourseList.
	 *
	 * @param ObjList $obj
	 * 
	 * @return CourseList
	 */
	static public function cast(ObjList $obj)
	{ // This can be used to cast a regular ObjList
		// into a CourseList object.
		// Use the syntax:  CourseList::cast($x);
		$newCL = new CourseList();
		$newCL->arrayList = $obj->arrayList;
		$newCL->isEmpty = $obj->isEmpty;
		$newCL->resetCounter();

		return $newCL;

	}


	/**
	 * Give every course in the list a minimum grade.
	 *
	 * @param string $minGrade
	 */
	function assignMinGrade($minGrade)
	{
		// Go through the list and give every course the specified
		// min grade.

		for ($t = 0; $t < $this->count; $t++)
		{
			$course = $this->arrayList[$t];
			$course->minGrade = $minGrade;
		}


	}


	/**
	 * Go through the list and find any course whose hours are greater
	 * than $hours.  Make that course "unselectable."  Used in the groups.
	 * 
	 * For example, if a student may only select 3 hours from a group, we don't
	 * want to give them the option of selecting a 5 hour course.  But we also
	 * don't want to remove that course either.  We want to display it so they
	 * know it was an option (and possibly need to substitute or move things
	 * around if they need it).
	 *
	 * Returns TRUE if anything got assigned, FALSE if nothing got assigned.
	 * 
	 * @param int $hours
	 * 
	 * @return bool
	 */
	function assignUnselectableCoursesWithHoursGreaterThan($hours)
	{
		// Go through the list and assign boolUnselectable courses whose minHour
		// is greater than $hours.
		// Returns TRUE if it did assign something,
		// false if it didn't.

		$boolAssigned = false;

		for ($t = 0; $t < $this->count; $t++)
		{
			$course = $this->arrayList[$t];
			if ($course->subjectID == "")
			{
				$course->loadDescriptiveData();
			}
			
			if ($course->minHours > $hours)
			{
				$course->boolUnselectable = true;
				$boolAssigned = true;
			}
		}

		return $boolAssigned;

	}

	/**
	 * Find and return a specific course from the list.
	 *
	 * @param int $courseID
	 *       - The courseID to look for.  Do not set if using
	 *         $useCourse.
	 * 
	 * @param int $termID
	 *       - The termID for the course to look for.  Do not set if using
	 *         $useCourse.
	 * 
	 * @param bool $boolTransfer
	 *       - Is the course we are looking for a transfer course?  Do not
	 *         use if using $useCourse.
	 * 
	 * @param bool $boolExcludeSubstitutions
	 *       - If TRUE, we will not consider courses which have been used
	 *         in a substitution.
	 * 
	 * @param Course $useCourse
	 *       - Optional.  If you already have a course object which can be used
	 *         as a template to search for, specify it here.  Otherwise, set to
	 *         NULL.  If using this, then $courseID, $termID, and $boolTransfer
	 *         will be ignored.
	 * 
	 * 
	 * @return Course
	 */
	function findSpecificCourse($courseID = 0, $termID = 0, $boolTransfer = false, $boolExcludeSubstitutions = true, Course $useCourse = null)
	{
		if ($useCourse != null && is_object($useCourse))
		{
			$courseID = $useCourse->courseID;
			$termID = $useCourse->termID;
			$boolTransfer = $useCourse->boolTransfer;
		}
		// Look through the array for a course with this id, termId, and
		// transfer credit status.
		//adminDebug("Looking for $courseID $termID $boolTransfer ");
		for ($t = 0; $t < $this->count; $t++)
		{
			$course = $this->arrayList[$t];

			$checkCourseID = $course->courseID;
			//adminDebug("..... looking at $checkCourseID");
			if ($boolTransfer == true && is_object($course->courseTransfer))
			{
				$checkCourseID = $course->courseTransfer->courseID;
				//adminDebug("..... ..... using transfer $checkCourseID");
			}

			if ($checkCourseID == $courseID && $course->termID == $termID && $course->boolTransfer == $boolTransfer)
			{

				if ($boolExcludeSubstitutions == true)
				{
					if ($course->boolSubstitution == true)
					{
						continue;
					}

				}

				return $course;
			}
		}

		return false;
	}


	/**
	 * Call the $course->loadCourseDescriptiveData() on 
	 * every course in the list.
	 *
	 */
	function loadCourseDescriptiveData()
	{
		// Call the loadDescriptiveData() method
		// for every course in the list.

		for ($t = 0; $t < $this->count; $t++)
		{
			$course = $this->arrayList[$t];
			$course->loadDescriptiveData();
		}
	}


  /**
   * Using the parent's function of findAllMatches, this
   * will return a CourseList of all courses which match
   * the Course object.
   *
   * @param Course $courseC
   * @return CourseList
   */
	function findAllMatches(Course $courseC)
	{
		if (!$listMatches =  parent::findAllMatches($courseC))
		{
			return false;
		}


		$listMatches = CourseList::cast($listMatches);
		return $listMatches;
	}


	/**
	 * Returns a match to the Course courseC which does
	 * not have any courses fulfilling it.  Usefull for finding
	 * course requirement matches in a list which have not
	 * yet been assigned.
	 *
	 * @param Course $courseC
	 * @return Course
	 */
	function findFirstUnfulfilledMatch(Course $courseC)
	{
		// Returns match to courseC which does not have
		// any courses fulfilling it.  Useful for finding
		// course requirement matches in a list which have not
		// yet been assigned.

		for ($t = 0; $t < $this->count; $t++)
		{
			if ($this->arrayList[$t]->equals($courseC) && $this->arrayList[$t]->courseListFulfilledBy->isEmpty == true)
			{
				return $this->arrayList[$t];
			}
		}

		return false;
	}

	/**
	 * Go through the list and set the $boolExcludeRepeat flag to TRUE
	 * for all matches of $course in this list.
	 *
	 * Returns FALSE if no matches could be found.
	 * 
	 * @param Course $course
	 * @return bool
	 */
	function markRepeatsExclude(Course $course)
	{
		// Set the boolExcludeRepeat flag to TRUE for all
		// occurances of $course in THIS list.

		if (!$listMatches =  parent::findAllMatches($course))
		{
			return false;
		}

		$listMatches = CourseList::cast($listMatches);
		$listMatches->resetCounter();
		while($listMatches->hasMore())
		{
			$c = $listMatches->getNext();
			$c->boolExcludeRepeat = true;
		}

		return true;

	}

	/**
	 * Find a list of matches to Course courseC, which fulfill 
	 * the minGrade requirement, ordered by most recently taken.
	 *
	 * Returns FALSE if no matches were found, else it will 
	 * return the matched Course object.
	 * 
	 * @param Course $courseC
	 * @param string $minGrade
	 * @param bool $boolMarkRepeatsExclude
	 * 
	 * @return Course
	 */
	function findMostRecentMatch(Course $courseC, $minGrade = "D", $boolMarkRepeatsExclude = false)
	{
		// Get a list of all matches to courseC, and
		// then order them by the most recently taken course
		// first.
		// We should, too, check for minimum grades here
		// as well.


		if (!$listMatches =  parent::findAllMatches($courseC))
		{
			return false;
		}


		$listMatches = CourseList::cast($listMatches);


		// Don't just return if it's only got a size of 1,
		// so that it forces it to do the min grade checking.
		/*		if ($listMatches->getSize() == 1)
		{
		return $listMatches->getNext();
		}
		*/
		if ($listMatches->isEmpty)
		{
			return false;
		}

		// If we are here, then we have more than one match.
		// Meaning, we have more than one class which might fit
		// into this course requirement.

		// Sort the courses into most recently taken first.
		//adminDebug("-------------------");
		//print_pre($listMatches->toString());
		$listMatches->sortMostRecentFirst();

		//adminDebug("-------------------");
		//print_pre($listMatches->toString());
		//adminDebug("-------------------");

		// So, now that it's sorted, we should look through the list,
		// checking the min grade requirements (if any).  When we find
		// a good one, we will select it.

		$listMatches->resetCounter();
		while($listMatches->hasMore())
		{
			$c = $listMatches->getNext();
			
			if ($c->boolExcludeRepeat == true)
			{
				continue;
			}
			//////////////////////////////////////////
			///  Check for min grade, etc, here.
			//adminDebug("checking min grade ($minGrade) for " . $c->toString());
			if (!$c->meetsMinGradeRequirementOf(null, $minGrade))
			{
				//adminDebug("skippin");
				if ($boolMarkRepeatsExclude == true)
				{
					// Since this course does not meet the minGrade,
					// check to see if it may be repeated.  If it can't,
					// then we must mark ALL previous attempts at this
					// course as being excluded from further consideration.
					// (ULM policy on repeats).
					// We don't do this consideration if they simply
					// withdrew from a course...
					if ($c->grade == "W") { continue; }

          if ($c->minHours < 1 || $c->minHours == "") {
					  $c->loadDescriptiveData();  // make sure we get hour data for this course.
					}					
					
					if ($c->repeatHours <= $c->minHours)
					{
						// No repeats.
						//adminDebug("no repeats allowed. rep hours:" . $c->repeatHours . " - minHours:" . $c->minHours);
						$this->markRepeatsExclude($c);
						return false;

					} else {
						// Repeats allowed, so just continue.
						continue;
					}

				} else {
					continue;
				}
			}

			// Has the course already been assigned?
			if ($c->boolHasBeenAssigned)
			{ // Skip over it.  Now, this is an important part here, because actually, we should
				// only skip it (and look at the next one) if this course is allowed to be
				// repeated.  If it cannot be repeated, or if the student has taken the
				// maximum allowed hours, then we should return false right here.
				continue;
			}

			return $c;
		}

		return false;

	}

	/**
	 * @todo implement this function.
	 *
	 */
	function sortBestGradeFirst()
	{
		// This will look very similar to sortMostRecentFirst
		// when I get a chance to fool with it.
	}

	
	/**
	 * Remove courses from THIS list which appear in listCourses under
	 * these conditions:
	 *   - the listCourses->"assignedToGroupID" != $groupID
	 * This function is being used primarily with $listCourses being the
	 * list of courses that students have taken.
	 * Also checking substitutions for courses substituted into groups.

	 * @param CourseList $listCourses
	 * @param int $groupID
	 * @param bool $boolKeepRepeatableCourses
	 * @param SubstitutionList $listSubstitutions
	 */
	function removePreviouslyFulfilled(CourseList $listCourses, $groupID, $boolKeepRepeatableCourses = true, $listSubstitutions)
	{

		$rtnList = new CourseList();

		for ($t = 0; $t < $this->count; $t++)
		{
			$course = $this->arrayList[$t];

			if ($boolKeepRepeatableCourses == true)
			{ // We can always keep repeatable courses in the list.
				if ($course->repeatHours > $course->minHours)
				{
					$rtnList->add($course);
					continue;
				}
			}

			//adminDebug($course->toString());
			
			// Has the course been substituted?
			if ($testSub = $listSubstitutions->findRequirement($course,false, -1))
			{
				//adminDebug("found " . $testSub->toString());
				// it WAS substituted, so we should NOT add it to our
				// rtnList.
				continue;
			}
			
			
			// Okay, now check if $course is anywhere in $listCourses
			if ($testCourse = $listCourses->findMatch($course))
			{
				// Yes, it found a match.
				//adminDebug("Here!");
				// I am taking out this part where I say if it is in
				// this group then we can keep it.  I think that shouldn't
				// be in.
				// This course is in another group, so do nothing
				// and skip it.
				//adminDebug("found elsewhere in group: $testCourse->grade");
				
				// perhaps the course is on the degreePlan in excess with a W
				// or F?
				if (!$testCourse->meetsMinGradeRequirementOf(null, "D"))
				{
					// Meaning, this was a failed attempt, so we can add
					// our original course back in.
					$rtnList->add($course);
					continue;
				}
				
				// perhaps this course was purposefully excluded from
				// this list because it did not meet the min grade
				// requirements?  If this is the case, $course should
				// still appear in THIS list.
				if (!$testCourse->meetsMinGradeRequirementOf($course))
				{
					// Meaning, this was attempt did not meet the
					// min grade of the original requirement, so we can add
					// our original requirement back in.
					$rtnList->add($course);
					continue;
				}
															
			} else {
				// The course was NOT found in the courseList,
				// so its safe to add it back in.
				$rtnList->add($course);
			}

		}


		$this->arrayList = $rtnList->arrayList;
		$this->resetCounter();

	}


	/**
	 * Returns an array containing the unique subjectID's of
	 * the courses in this list.  Its assumed to be ordered
	 * already!
	 *
	 * @param bool $boolIgnoreExcluded
	 * @return array
	 */
	function getCourseSubjects($boolIgnoreExcluded = true)
	{
		// returns an array containing the unique subjectID's
		// of the courses in this list.

		// IMPORTANT:  The list is assumed to be ordered already!  Either
		// alphabetically or reverse alphabetically.
		$oldSubjectID = "";
		$rtnArray = array();

		for ($t = 0; $t < $this->count; $t++)
		{
			$course = $this->arrayList[$t];
			if ($course->subjectID == "")
			{
				$course->loadDescriptiveData();
			}

			
			// Go through all valid names for this course.
			for ($x = 0; $x < count($course->arrayValidNames); $x++)
			{
				$temp = split("~",$course->arrayValidNames[$x]);
				$subj = strtoupper($temp[0]);

				if (in_array($subj, $rtnArray))
				{ // skip ones with subjects we have already looked at.
					continue;
				}

				if ($course->dbExclude == 1)
				{
					//adminDebug("skipping " . $course->toString());
					continue;
				}


				// We have a new subject.  Add it to the array.
				$rtnArray[] = $subj;
			}

		}

		return $rtnArray;
	}

	/**
	 * Go through the courseList and take out any course
	 * which does not have the $subject as its subjectID.
	 *
	 * @param string $subject
	 * @param bool $boolReassignValidName
	 *     - If set to TRUE, we will look at other possible valid names
	 *       for this course.  If we find one, we will reassign the course's
	 *       subjectID and courseNum to the new valid name.
	 * 
	 */
	function excludeAllSubjectsExcept($subject, $boolReassignValidName = true)
	{

		$newCourseList = new CourseList();

		for ($t = 0; $t < $this->count; $t++)
		{
			$course = $this->arrayList[$t];
			if ($course->subjectID == $subject)
			{
				$newCourseList->add($course);
				continue;
			}
			
			// Not the right subject-- but perhaps the course has another
			// valid name with this subject?  Ex: CSCI 373 and MATH 373.
			
			if ($boolReassignValidName == true && count($course->arrayValidNames) > 1)
			{
				for ($x = 0; $x < count($course->arrayValidNames); $x++)
				{
					if (strstr($course->arrayValidNames[$x], $subject))
					{
						$temp = split("~",$course->arrayValidNames[$x]);
						$course->subjectID = $temp[0];
						$course->courseNum = $temp[1];
						//adminDebug("use $course->subjectID $course->courseNum");
						$newCourseList->add($course);
						continue;
					}
				}
				
			}
			
			
		}

		// Now, transfer ownership of the arraylist.
		$this->arrayList = $newCourseList->arrayList;

	}


	/**
	 * This re-sorts the CourseList so that fulfilled courses
	 * are first, in alphabetical order, followed by
	 * unfulfilled courses, in alphabetical order.
	 * This is most useful for making the groups
	 * show up correctly.
	 * 
	 */
	function sortFulfilledFirstAlphabetical()
	{

		$tarray = array();
		for ($t = 0; $t < $this->count; $t++)
		{
			//if (!is_object($this->arrayList[$t]->courseFulfilledBy))
			if ($this->arrayList[$t]->courseListFulfilledBy->isEmpty == true)
			{ // Skip if not fulfilled.
				continue;
			}

			$c = $this->arrayList[$t];
			$str = "$c->subjectID ~~ $c->courseNum ~~ $t";
			array_push($tarray,$str);
		}

		sort($tarray);

		$newList = new CourseList();
		for($t = 0; $t < count($tarray); $t++)
		{
			$temp = split(" ~~ ",$tarray[$t]);
			$i = $temp[2];

			$newList->add($this->arrayList[$i]);
		}


		// Alright, now we do it again, but with unfulfilled courses.
		$tarray = array();
		for ($t = 0; $t < $this->count; $t++)
		{
			//if (is_object($this->arrayList[$t]->courseFulfilledBy))
			if ($this->arrayList[$t]->courseListFulfilledBy->isEmpty != true)
			{ // Skip if fulfilled.
				continue;
			}

			$c = $this->arrayList[$t];
			$str = "$c->subjectID ~~ $c->courseNum ~~ $t";
			array_push($tarray,$str);
		}

		sort($tarray);

		$newList2 = new CourseList();
		for($t = 0; $t < count($tarray); $t++)
		{
			$temp = split(" ~~ ",$tarray[$t]);
			$i = $temp[2];

			$newList2->add($this->arrayList[$i]);
		}

		// Now, combine the two lists.
		$newList->addList($newList2);

		// And, transfer the newList into this list.
		$this->arrayList = $newList->arrayList;


	}



  /**
   * This re-sorts the CourseList so that advised courses
   * are last, in alphabetical order, preceeded by
   * unfulfilled courses, in alphabetical order.
   * 
   *
   */
	function sortAdvisedLastAlphabetical()
	{

		$tarray = array();
		for ($t = 0; $t < $this->count; $t++)
		{
			if ($this->arrayList[$t]->boolAdvisedToTake == true)
			{ // Skip if not fulfilled.
				continue;
			}

			$c = $this->arrayList[$t];
			$str = "$c->subjectID ~~ $c->courseNum ~~ $t";
			array_push($tarray,$str);
		}

		sort($tarray);


		// Alright, now we do it again, but with advised courses.
		$t2array = array();
		for ($t = 0; $t < $this->count; $t++)
		{
			if ($this->arrayList[$t]->boolAdvisedToTake == false)
			{ // Skip if not advised
				continue;
			}

			$c = $this->arrayList[$t];
			$str = "$c->subjectID ~~ $c->courseNum ~~ $t";
			array_push($t2array,$str);
		}

		sort($t2array);

		$t3array = array_merge($tarray, $t2array);

		$newList = new CourseList();
		for($t = 0; $t < count($t3array); $t++)
		{
			$temp = split(" ~~ ",$t3array[$t]);
			$i = $temp[2];

			$newList->add($this->arrayList[$i]);
		}

		// And, transfer the newList into this list.
		$this->arrayList = $newList->arrayList;


	}


	/**
	 * This function will resort this courselist for which a substitution
	 * has been made in listSubstitutions.
	 *
	 * @param SubstitutionList $listSubstitutions
	 * @param int $groupID
	 */
	function sortSubstitutionsFirst($listSubstitutions, $groupID = 0)
	{
		// This will sort courses in a list for which
		// a substitution has been made in listSubstitutions.
		// It will place those courses at the top of the list.

		$topArray = array();

		// Since I need the indexes, I will have to go through the array
		// myself...
		for ($t = 0; $t < $this->count; $t++)
		{
			$c = $this->arrayList[$t];
			// So-- does this course have a substitution somewhere in
			// the list (for the supplied groupID) ?
			if ($substitution = $listSubstitutions->findRequirement($c, true, $groupID))
			{
				// yes, there is a sub for this group (or bare degree plan)
				$topArray[] = $t;
			}

		}

		// Okay, we now have, in the topArray, a list of indexes which should
		// appear at the top.
		$newList = new CourseList();
		for ($j = 0; $j < count($topArray); $j++)
		{
			$newList->add($this->arrayList[$topArray[$j]]);
		}

		// Now, add everything else in the array (except indecies
		// appearing in topArray)

		for ($t = 0; $t < $this->count; $t++)
		{
			if (in_array($t, $topArray))
			{
				continue;
			}
			$newList->add($this->arrayList[$t]);
		}

		$this->arrayList = $newList->arrayList;
		$newList->resetCounter();

	}


	/**
	 * This will sort so that courses with the smallest hours
	 * (first trying hoursAwarded, then minHours)
	 * are at the top of the list.  If the list contains more
	 * than one course with a set of hours (like there are 30   
	 * courses all worth 3 hours) then it orders those as
	 * most-recently-taken first. 
	 *
	 */
	function sortSmallestHoursFirst()
	{
		
		$tarray = array();

		// Since I need the indexes, I will have to go through the array
		// myself...
		for ($t = 0; $t < $this->count; $t++)
		{
			$c = $this->arrayList[$t];
			$hours = $c->hoursAwarded*1;
			if ($hours < 1)
			{
				$hours = $c->minHours*1;
			}
			$str = "$hours ~~ $t";
			array_push($tarray,$str);
		}

		// Now, sort the array...
		//print_pre(print_r($tarray));
		sort($tarray);
		//print_pre(print_r($tarray));

		// Now, convert the array back into a list of courses.
		$newList = new CourseList();
		for($t = 0; $t < count($tarray); $t++)
		{
			$temp = split(" ~~ ",$tarray[$t]);
			$i = $temp[1];

			$newList->add($this->arrayList[$i]);
		}

		// Okay, now $newList should contain the correct values.
		// We will transfer over the reference.
		$this->arrayList = $newList->arrayList;


	}


	/**
	 * This method will sort by the most recently taken
	 * courses (determined by the termID).
	 * The easiest way I can think to do this is to temporarily
	 * put their termID's and index#'s into an array, and then
	 * have PHP sort the array itself.  PHP's sorting algorithm
	 * is faster than anything I can program right now, anyway.
	 *
	 * @param bool $boolMaintainAlphaOrder
	 */
	function sortMostRecentFirst($boolMaintainAlphaOrder = true)
	{
		$tarray = array();

		
		// Since I need the indexes, I will have to go through the array
		// myself...
		for ($t = 0; $t < $this->count; $t++)
		{
			$c = $this->arrayList[$t];
			$cn = "";
			if ($boolMaintainAlphaOrder == true)
			{
				// We say 1000- the course number in order to give
				// us the complement of the number.  That is so it will
				// reverse-sort in the correct order.  Strange, but it fixes
				// a small display issue where PHYS 207 and PHYS 209, taken at
				// the same time, causes PHYS 209 to be displayed first.
				// We also reverse the subjectID, again, so that
				// MATH will be sorted above ZOOL, when taken at the same time.
				// This might not work at all, though...
				
				$cn = strrev($c->subjectID) . "," . (1000 - $c->courseNum);
				//adminDebug($cn);
			}
			$str = "$c->termID ~~ $cn ~~ $t";
			//adminDebug($str);
			array_push($tarray,$str);
		}

		// Now, sort the array...
		//print_pre(print_r($tarray));
		rsort($tarray);
		//print_pre(print_r($tarray));

		// Now, convert the array back into a list of courses.
		$newList = new CourseList();
		for($t = 0; $t < count($tarray); $t++)
		{
			$temp = split(" ~~ ",$tarray[$t]);
			$i = $temp[2];

			$newList->add($this->arrayList[$i]);
		}

		// Okay, now $newList should contain the correct values.
		// We will transfer over the reference.
		$this->arrayList = $newList->arrayList;

	}

	/**
	 * Convienence function.  It simply calls sortAlphabeticalOrder(), but
	 * passes the boolean value to make it be reversed.
	 *
	 */
	function sortReverseAlphabeticalOrder()
	{
		$this->sortAlphabeticalOrder(true);
	}


	/**
	 * Sorts the course list into alphabetical order.  If loadDescriptiveData()
	 * has not already been called for each course, it will call it.
	 *
	 * @param bool $boolReverseOrder
	 *         - If set to TRUE, the list will be in reverse order.
	 * 
	 * @param unknown_type $boolOnlyTransfers
	 *         - Only sort the transfer courses.
	 * 
	 * @param unknown_type $boolSetArrayIndex
	 *         - If set to true, it will set the $course->arrayIndex value
	 *           to the index value in $this's arrayList array.
	 * 
	 */
	function sortAlphabeticalOrder($boolReverseOrder = false, $boolOnlyTransfers = false, $boolSetArrayIndex = false)
	{
		// Sort the list into alphabetical order, based
		// on the subjectID and courseNum.
		$tarray = array();
		// Since I need the indexes, I will have to go through the array
		// myself...
		for ($t = 0; $t < $this->count; $t++)
		{
			$c = $this->arrayList[$t];
			if ($c->subjectID == "")
			{
				$c->loadDescriptiveData();
			}


			if ($boolOnlyTransfers == true)
			{
				// Rarer.  We only want to sort the transfer credits.  If the course doesn not
				// have transfers, don't skip, just put in the original.  Otherwise, we will be using
				// the transfer credit's SI and CN.
				if (is_object($c->courseTransfer))
				{
					$str = $c->courseTransfer->subjectID . " ~~ " . $c->courseTransfer->courseNum ." ~~ $t";
				} else {
					// There was no transfer!
					$str = "$c->subjectID ~~ $c->courseNum ~~ $t";
				}
			} else {

				// This is the one which will be run most often.  Just sort the list
				// in alphabetical order.

				$str = "$c->subjectID ~~ $c->courseNum ~~ $t";
			}
			array_push($tarray,$str);
		}

		// Now, sort the array...
		//print_pre(print_r($tarray));

		if ($boolReverseOrder == true)
		{
			rsort($tarray);
		} else {
			sort($tarray);
		}
		//print_pre(print_r($tarray));

		// Now, convert the array back into a list of courses.
		$newList = new CourseList();
		for($t = 0; $t < count($tarray); $t++)
		{
			$temp = split(" ~~ ",$tarray[$t]);
			$i = $temp[2];
			if ($boolSetArrayIndex == true)
			{
				$this->arrayList[$i]->arrayIndex = $i;
			}
			$newList->add($this->arrayList[$i]);
		}

		// Okay, now $newList should contain the correct values.
		// We will transfer over the reference.
		$this->arrayList = $newList->arrayList;

	}


	/**
	 * Returns an array of dbGroupRequirementID's from the courses
	 * in this list.
	 *
	 * @return array
	 */
	function getGroupRequirementIDArray()
	{
		// Return an array of dbGroupRequirementID's
		// from the courses in this list, indexed by the
		// id's.

		$rtnArray = array();
		for ($t = 0; $t < $this->count; $t++)
		{
			$course = $this->arrayList[$t];
			$rtnArray[$course->dbGroupRequirementID] = true;
		}

		return $rtnArray;
	}

	
	
	/**
	 * Returns TRUE if this list has a course which contains
	 * $id for it's dbGroupRequirementID property.
	 *
	 * @param int $id
	 *         - This is the id to test for.
	 * 
	 * @return bool
	 */
	function containsGroupRequirementID($id)
	{
		// Returns true if the list has a course
		// which contains $id for it's dbGroupRequirementID.
		// False if it cannot be found.
		for ($t = 0; $t < $this->count; $t++)
		{
			$course = $this->arrayList[$t];
			if ($course->dbGroupRequirementID == $id)
			{
				return true;
			}
		}

		return false;

	}

	
  /**
	 * Returns the first course in the list
	 * which the user may select for advising.  This
	 * method is used by the popup window to determine
	 * what exactly is the first element of the course list.
	 * (so it can make that radio button checked).
	 * 
	 * Returns FALSE if it cannot find a selectable course.
   *
   * @return Course
   */
	function findFirstSelectable()
	{
		/*

		*/
		$tempI = $this->i;  // preserve the counter.
		$this->resetCounter();

		while($this->hasMore())
		{
			$c = $this->getNext();
			if ($c->boolAdvisedToTake == true)
			{
				continue;
			}
			if (!$c->courseListFulfilledBy->isEmpty)
			{
				continue;
			}
			if ($c->boolUnselectable == true)
			{
				continue;
			}

			// $c is our valid course...
			$this->i = $tempI;
			//print_pre($c->toString());
			return $c;


		}

		$this->i = $tempI;
		return false;

	}


	/**
	 * Returns TRUE if there is at least one course in this list which
	 * is selected (for advising).
	 *
	 * @return bool
	 */
	function hasAnyCourseSelected()
	{
		/*
		Returns TRUE if there is at least one course
		in this list which is "selected."  FALSE, otherwise.
		*/
		$tempI = $this->i;  // preserve the counter.
		$this->resetCounter();
		$rtn = false;
		while($this->hasMore())
		{
			$c = $this->getNext();
			if ($c->boolSelected == true)
			{
				$rtn = true;
				break;
			}
		}

		$this->i = $tempI;
		return $rtn;
	}


	/**
	 * Mark every course in this list as boolHasBeenDisplayed = true.
	 * Used for making sure we don't display the same course twice on
	 * screen.
	 * 
	 * Returns FALSE if we did not mark any courses.
	 *
	 * @param int $semesterNum
	 *         - If > -1, we will first make sure the course
	 *           falls into this semesterNum.  This way we can only
	 *           perform this operation on a particular semester.
	 * 
	 * @return bool
	 */
	function markAsDisplayed($semesterNum = -1)
	{

	  $tempI = $this->i;  // preserve the counter.
		$this->resetCounter();
		$rtn = false;
		while($this->hasMore())
		{
			$c = $this->getNext();
			if ($semesterNum != -1)
			{ // A semesterNum was specified.
				// Make sure the course is in the correct semester.
				if ($c->assignedToSemesterNum != $semesterNum)
				{
					continue;
				}
			}

			$c->boolHasBeenDisplayed = true;
			$rtn = true;

		}

		$this->i = $tempI;
		return $rtn;
	}


	/**
	 * Returns a CourseList of all the courses matching courseID
	 * that has boolHasBeenAssigned == TRUE.
	 *
	 * @param int $courseID
	 * @return CourseList
	 */
	function getPreviousAssignments($courseID)
	{
		// Return a courseList of all the times a course matching
		// courseID has the boolHasBeenAssigned set to TRUE.

		$rtnList = new CourseList();

		for ($t = 0; $t < $this->count; $t++)
		{
			$course = $this->arrayList[$t];
			if ($course->courseID == $courseID && $course->boolHasBeenAssigned == true)
			{
				$rtnList->add($course);
			}
		}

		return $rtnList;

	}

	/**
	 * Find the "best" match for this course, based on what
	 * the university considers a best match.
	 * This largely has to do with repeats.
	 * If the student has more than one credit, what is
	 * the "best" match?
	 * 
	 * For example, at ULM we consider the best match to be the
	 * most recent that meets the minimum requirements.
	 * Other schools might simply take the best grade.
	 *
	 * @param Course $courseC
	 * @param string $minGrade
	 * @param bool $boolMarkRepeatsExclude
	 *
	 * @return Course
	 */
	function findBestMatch(Course $courseC, $minGrade = "D", $boolMarkRepeatsExclude = false)
	{

		return $this->findMostRecentMatch($courseC, $minGrade, $boolMarkRepeatsExclude);

	}



	/**
	 * Adds the supplied CourseList to the bottom of $this's list.
	 *
	 * @param CourseList $courseL
	 */
	function addList(CourseList $courseL)
	{
		for ($t = 0; $t < count($courseL->arrayList); $t++)
		{
			//adminDebug($courseL->arrayList[$t]->assignedToSemesterNum);
			//adminDebug("adding " . $courseL->arrayList[$t]->toString());
			$this->add($courseL->arrayList[$t]);
		}

	}


	/**
	 * Returns hour many hours are in $this CourseList.
	 *
	 * @todo The ignore list should be database-based.  Should just get it
	 *       from the settings.
	 * 
	 * @param string $requirementType
	 *         - If specified, we will only count courses which match this
	 *           requirementType.
	 * 
	 * @param bool $boolUseIgnoreList
	 * @return int
	 */
	function countHours($requirementType = "", $boolUseIgnoreList = false, $boolCorrectGhostHour = true, $boolForceZeroHoursToOneHour = false)
	{
		// Returns how many hours are being represented in this courseList.
		// A requirement type of "uc" is the same as "c"
		// (university capstone is a core requirement)


		$count = 0;
		for ($t = 0; $t < $this->count; $t++)
		{
			$course = $this->arrayList[$t];

			if ($boolUseIgnoreList == true)
			{
				// Do ignore some courses...
				$tempCourseName = $course->subjectID . " " . $course->courseNum;
				// Check in our settings to see if we should ignore this course
				// (configured in /custom/settings.php)
				if (in_array($tempCourseName, $GLOBALS["fpSystemSettings"]["ignoreCoursesFromHourCounts"])) {
					continue;
				}
				
			}
			
			if ($course->boolSubstitutionNewFromSplit == true)
			{
				// Do not count the possible fragments that are created
				// from a new substitution split.  This is causing problems
				// in getting accurate numbers on the pie charts.
				
				// BUT-- only skip if this new fragment isn't also being
				// substituted somewhere else!
				if ($course->boolSubstitution == false)
				{ // not being used in another sub, so skip it.
					//adminDebug("skipping " . $course->toString());
					continue;
				}
			}

			$hGetHours = $course->getHours();
			if ($boolCorrectGhostHour) {
  			// If this course has a ghosthour, then use the
  			// hoursAwarded (probably 1).  However, if it was substituted,
  			// then we actually want the 0 hour.  Confusing, isn't it?
  			if ($course->boolGhostHour) {
  			  $hGetHours = $course->hoursAwarded;
  			}
			}
			
			if ($boolForceZeroHoursToOneHour) {			  
			  // We want to force anything with a 0 hour to be 1 hour.
			  // Helps when selecting 0 hour courses from groups.
			  if ($hGetHours == 0) {			    
			    $hGetHours = 1;
			  }
			}
			
			
			if ($requirementType == "")
			{
				$count = $count + $hGetHours;
			} else {
				// Requirement Type not blank, so only count these hours
				// if it has the set requirement type.
				if ($course->requirementType == $requirementType)
				{
					$count = $count + $hGetHours;
					//adminDebug($course->toString());
					continue;
				}

				// For specifically "university capstone" courses...
				if ($course->requirementType == "uc" && $requirementType == "c")
				{
					$count = $count + $hGetHours;
				}

				if ($course->requirementType == "um" && $requirementType == "m")
				{
					$count = $count + $hGetHours;
				}


			}
		}

		return $count;
	}

	
	/**
	 * Removes courses which have neither been fulfilled or advised.
	 *
	 */
	function removeUnfulfilledAndUnadvisedCourses()
	{
		// remove courses from THIS list
		// which have not been fulfilled AND
		// are not currently advised.
		$rtnList = new CourseList();
		for ($t = 0; $t < $this->count; $t++)
		{
			$course = $this->arrayList[$t];
			if ($course->courseListFulfilledBy->isEmpty == false)
			{
				// something IS fulfilling it!
				$rtnList->add($course);

			} else if ($course->boolAdvisedToTake == true)
			{
				// Was not being fulfilled, but, it was advised
				// to take.
				$rtnList->add($course);
			}



		}

		$this->arrayList = $rtnList->arrayList;
		$this->resetCounter();
	}


	/**
	 * Removes courses from this list which have not been fulfilled
	 * (ther courseListFulfilledBy is empty).
	 *
	 */
	function removeUnfulfilledCourses()
	{
		// remove courses in THIS list
		// which have nothing in their courseListFulfilledBy
		// object.
		$rtnList = new CourseList();
		for ($t = 0; $t < $this->count; $t++)
		{
			$course = $this->arrayList[$t];
			if ($course->courseListFulfilledBy->isEmpty == false)
			{
				$rtnList->add($course);
			}
		}

		$this->arrayList = $rtnList->arrayList;
		$this->resetCounter();

	}

	
	/**
	 * Returns a clone CourseList of $this.
	 *
	 * @param bool $boolReturnNewCourses
	 *         - If set to TRUE, it will create new Course objects
	 *           based on the courseID's of the ones in $this's list.
	 *           If set to FALSE, this will add the exact same Course
	 *           objects by reference to the new list.
	 * 
	 * @return CourseList
	 */
	function getClone($boolReturnNewCourses = false)
	{
		// This will return a clone of this list.
		// If boolReturnNewCourses is true, then it will
		// return a new list of new instances of courses
		// from this list.
		$rtnList = new CourseList();
		
		for ($t = 0; $t < $this->count; $t++)
		{
			$course = $this->arrayList[$t];
			
			if ($boolReturnNewCourses == true)
			{
				$newCourse = new Course();
				$newCourse->courseID = $course->courseID;
				$rtnList->add($newCourse);
			} else {
				$rtnList->add($course);
			}	
			
		}	
		
		return $rtnList;
			
	}
	

	/**
	 * Returns a CourseList of all the fulfilled or advised courses
	 * in $this's list.
	 *
	 * @param bool $boolReturnNewCourses
	 *         - Works the same as getClone()'s boolReturnNewCourses
	 *           variable.
	 * 
	 * @return Course
	 */
	function getFulfilledOrAdvised($boolReturnNewCourses = false)
	{
		
		$rtnList = new CourseList();
		
		for ($t = 0; $t < $this->count; $t++)
		{
			$course = $this->arrayList[$t];
			$addCourse = $course;
			
			if ($boolReturnNewCourses == true)
			{
				$addCourse = new Course();
				$addCourse->courseID = $course->courseID;
			}
			
			if ($course->boolAdvisedToTake == true)
			{
				$rtnList->add($addCourse);
			}
			
			// Several ways to tell if a course is here by credit...
			if (!$course->courseListFulfilledBy->isEmpty)
			{
				$rtnList->add($addCourse);
			} else if ($course->grade != "") {
				$rtnList->add($addCourse);
			} else if ($course->boolSubstitution == true)
			{
				$rtnList->add($addCourse);
			}			
		}
		
		return $rtnList;
		
	}
	
	/**
	 * Returns the number of courses in this list which have either
	 * been fulfilled or advised to take.  It does not count hours,
	 * just the courses themselves.
	 *
	 * @return int
	 */
	function countFulfilledOrAdvised()
	{
		// This function returns the number of courses in this
		// courseList which is either fulfilled or has been advised
		// to take.  It does care about hours, just the number of
		// courses themselves.
		$count = 0;
		for ($t = 0; $t < $this->count; $t++)
		{
			$course = $this->arrayList[$t];
			if ($course->boolAdvisedToTake == true)
			{
				$count++;
			}
			
			// Several ways to tell if a course is here by credit...
			if (!$course->courseListFulfilledBy->isEmpty)
			{
				$count++;
			} else if ($course->grade != "") {
				$count++;
			} else if ($course->boolSubstitution == true)
			{
				$count++;
			}			
		}
		
		return $count;
		
	}
	
	
	/**
	 * Returns a CourseList of courses which have boolAdvisedToTake == true.
	 *
	 * @return CourseList
	 */
	function getAdvisedCoursesList()
	{
		// Return a courseList object of courses in THIS
		// list which have boolAdvisedToTake == true.
		$rtnList = new CourseList();
		for ($t = 0; $t < $this->count; $t++)
		{
			$course = $this->arrayList[$t];
			if ($course->boolAdvisedToTake == true)
			{
				$rtnList->add($course);
			}
		}

		return $rtnList;

	}


	/**
	 * Similar to countHours, but this will only count courses
	 * which have been taken and have a grade.
	 * 
	 * @todo ignore list should be db-based, in the settings.
	 *
	 * @param string $requirementType
	 *         - If set, we will only look for courses matching this requirementType.
	 * 
	 * @param bool $boolUseIgnoreList
	 * @param bool $boolIgnoreEnrolled
	 * @return CourseList
	 */
	function countCreditHours($requirementType = "", $boolUseIgnoreList = false, $boolIgnoreEnrolled = false)
	{
		// Similar to countHours, but this will only
		// count courses which have been taken (have a grade).


		$count = 0;
		//adminDebug($requirementType);
		for ($t = 0; $t < $this->count; $t++)
		{
			$course = $this->arrayList[$t];

			if ($boolUseIgnoreList == true)
			{
				// Do ignore some courses...
        $tempCourseName = $course->subjectID . " " . $course->courseNum;
				// Check in our settings to see if we should ignore this course
				// (configured in /custom/settings.php)
				if (in_array($tempCourseName, $GLOBALS["fpSystemSettings"]["ignoreCoursesFromHourCounts"])) {
					continue;
				}				

			}


			if ($boolIgnoreEnrolled == true)
			{
				if ($course->isCompleted() == false)
				{
					//adminDebug("skip" . $course->toString());
					if ($course->courseListFulfilledBy->isEmpty)
					{

						continue;
					} else {
						if ($course->courseListFulfilledBy->getFirst()->isCompleted() == false)
						{
							continue;
						}
					}
				}
			}

			if ($course->grade != "")// || !($course->courseListFulfilledBy->isEmpty))
			{
				if ($requirementType == "")
				{
				  $h = $course->getHours();
					$count = $count + $h;
					//adminDebug($course->toString());
					//adminDebug($h);
				} else {
					if ($course->requirementType == $requirementType)
					{
						$count = $count + $course->getHours();
						//adminDebug($course->toString());
						continue;
					}

					// For specifically "university capstone" courses...
					if ($course->requirementType == "uc" && $requirementType == "c")
					{
						$count = $count + $course->getHours();
					}

					if ($course->requirementType == "um" && $requirementType == "m")
					{
						$count = $count + $course->getHours();
					}


				}
			} else {

				// maybe it's a substitution?
				if ($requirementType == "")
				{
					if ($course->courseListFulfilledBy->isEmpty == false)
					{
						$cc = $course->courseListFulfilledBy->getFirst();
						if ($cc->boolSubstitution)
						{
						  
							$h = $cc->substitutionHours;
							
							if ($cc->boolGhostHour) {
							  $h = 0;
							}
							
							$count = $count + $h;
							adminDebug($cc->toString());
						}
					}
				} else {
					if ($requirementType == $course->requirementType)
					{
						if ($course->courseListFulfilledBy->isEmpty == false)
						{
							$cc = $course->courseListFulfilledBy->getFirst();
							if ($cc->boolSubstitution)
							{
								$h = $cc->substitutionHours;
								
                if ($cc->boolGhostHour) {
  							  $h = 0;
  							}								
								
								$count = $count + $h;
								//adminDebug($cc->toString());
							}
						}

					}


				}
			}
		}

		return $count;

	}


	/**
	 * Assign a groupID to every course in the list.
	 *
	 * @param int $groupID
	 */
	function assignGroupID($groupID)
	{
		for ($t = 0; $t < $this->count; $t++)
		{
			$course = $this->arrayList[$t];
			$course->assignedToGroupID = $groupID;
		}
	}


	/**
	 * Assign a semesterNum to every course in the list.
	 *
	 * @param int $semesterNum
	 */
	function assignSemesterNum($semesterNum)
	{
		for ($t = 0; $t < $this->count; $t++)
		{
			$course = $this->arrayList[$t];
			$course->assignedToSemesterNum = $semesterNum;
		}
	}

	/**
	 * Sets the boolHasBeenAssigned property of every course in
	 * the list.
	 *
	 * @param bool $boolHasBeenAssigned
	 *         - What to set each course's->boolhasBeenAssigned property
	 *           to.
	 * 
	 */
	function setHasBeenAssigned($boolHasBeenAssigned = true)
	{
		// Set the boolHasBeenAssigned for all items
		// in this list.
		for ($t = 0; $t < $this->count; $t++)
		{
			$course = $this->arrayList[$t];
			$course->boolHasBeenAssigned = $boolHasBeenAssigned;
		}

	}


	/**
	 * Set's each course's boolSubstitution value.
	 *
	 * @param bool $boolS
	 *         - What to set each course's boolSubstitution value to.
	 */
	function setBoolSubstitution($boolS = true)
	{
		// Set the boolSubstitution for all items
		// in this list.
		for ($t = 0; $t < $this->count; $t++)
		{
			$course = $this->arrayList[$t];
			$course->boolSubstitution = $boolS;
		}

	}

	/**
	 * Sets each course's $courseSubstitution value to the supplied
	 * Course object.
	 *
	 * @param Course $courseS
	 * @param string $subRemarks
	 */
	function setCourseSubstitution(Course $courseS, $subRemarks = "")
	{
		for ($t = 0; $t < $this->count; $t++)
		{
			$course = $this->arrayList[$t];
			$course->courseSubstitution = $courseS;
			$course->subRemarks = $subRemarks;
		}

	}



	/**
	 * Go through the list and decrement the specifiedRepeats
	 * value for all instances of Course $course.
	 *
	 * @param Course $course
	 */
	function decSpecifiedRepeats(Course $course)
	{
		// Go through the list, and decrement the specifiedRepeats
		// value for all instances of $course.
		for ($t = 0; $t < $this->count; $t++)
		{
			$course2 = $this->arrayList[$t];
			if ($course2->courseID == $course->courseID)
			{
				$course2->specifiedRepeats--;
			}
		}

	}


	/**
	 * Go through the list and set the specifiedRepeats value to $num
	 * for all instances of $course.
	 *
	 * @param Course $course
	 * @param int $num
	 */
	function setSpecifiedRepeats(Course $course, $num)
	{
		for ($t = 0; $t < $this->count; $t++)
		{
			$course2 = $this->arrayList[$t];
			if ($course2->courseID == $course->courseID)
			{
				$course2->specifiedRepeats = $num;
				$course2->boolSpecifiedRepeats = true;
			}

		}

	}


	/**
	 * Removes excluded courses from the list (courses that
	 * have dbExclude == 1)
	 *
	 */
	function removeExcluded()
	{
		// Removes courses from the list that have a dbExclude == 1.
		$newList = new CourseList();
		// Do this by adding elements to an array.
		// courseID => index in list.
		for ($t = 0; $t < $this->count; $t++)
		{
			$course = $this->arrayList[$t];
			if ($course->subjectID == "")
			{ // load descriptive data (include exclude info)
				$course->loadDescriptiveData();
			}
			
			if ($course->dbExclude == 1)
			{
				continue;
			}

			$newList->add($course);

		}

		$this->arrayList = $newList->arrayList;
		$this->resetCounter();

	}


	/**
	 * Removes null's and duplicate courses from the list.
	 *
	 */
	function removeDuplicates()
	{
		// Go through and remove duplicates from the list.
		// Also remove null's

		$tarray = array();
		$newList = new CourseList();
		// Do this by adding elements to an array.
		// courseID => index in list.
		for ($t = 0; $t < $this->count; $t++)
		{
			$course = $this->arrayList[$t];
			if ($course == null)
			{
				continue;
			}

			$tarray[$course->courseID] = -1;
		}

		for ($t = 0; $t < $this->count; $t++)
		{
			$course = $this->arrayList[$t];
			if ($course == null)
			{
				continue;
			}

			//if (is_object($course->courseFulfilledBy))
			if (!($course->courseListFulfilledBy->isEmpty))
			{
				$tarray[$course->courseID] = $t;
				continue;
			}

			if ($tarray[$course->courseID]*1 < 0)
			{
				//adminDebug("empty $course->subjectID $course->courseNum ");
				$tarray[$course->courseID] = $t;
			}

		}
		//print_pre(print_r($tarray));

		// Now, go through tarray and rebuild the newList.
		foreach($tarray as $courseID => $i)
		{
			$newList->add($this->arrayList[$i]);
		}

		// Switch over the reference.
		$this->arrayList = $newList->arrayList;
		$this->resetCounter();

	}

} // end class CourseList









?>