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


/**
 * This class is the View by Type view for FlightPath.  As such, it
 * inherits most of it's classes from _AdvisingScreen.
 *
 *	The biggest difference with View by Type from the default
 *	View by Year is that we don't care about the original semesters
 *	that were spelled out in the degree plan.  We need to re-organize them
 *	into new semesters for Major, Core, Supporting, and Electives.  So,
 *	most of the methods here will be about doing that.
 *
 */
class _AdvisingScreenTypeView extends _AdvisingScreen
{


  /**
   * In _AdvisingScreen, this method simply displays the degree plan's
   * semesters to the screen.  But here, we need to go through the 4
   * type categories: Core, Major, Supporting, and Electives,
   * and only display courses and groups from each semester fitting
   * that type.
   *
   */
	function buildSemesterList()
	{

		$listSemesters = $this->degreePlan->listSemesters;
		// Go through each semester and add it to the screen...
		$listSemesters->resetCounter();

		$this->addToScreen($this->displaySemesterList($listSemesters, "c", "Core Requirements", true));
		$this->addToScreen($this->displaySemesterList($listSemesters, "m", "Major Requirements", true));
		$this->addToScreen($this->displaySemesterList($listSemesters, "s", "Supporting Requirements", true));
		$this->addToScreen($this->displaySemesterList($listSemesters, "e", "Electives", true));

		
		$tempDS = new Semester(-55); // developmental requirements.
		if ($devSem = $listSemesters->findMatch($tempDS))
		{
			$this->addToScreen($this->displaySemester($devSem));
		}
						
		
	}


	/**
	 * Does the testType match the reqType?  This function is used
	 * to make sure that courses or groups with a certain requirementType
	 * are placed in the correct semester blocks on screen.
	 *
	 * @param string $testType
	 * @param string $reqType
	 * @return bool
	 */
	function matchRequirementType($testType, $reqType)
	{
		// Does the testType match the reqType?
		
		if ($testType == $reqType)
		{
			return true;
		}
		
		if ($testType == "uc" && $reqType == "c")
		{  // university captone core.
			return true;
		}

		if ($testType == "um" && $reqType == "m")
		{  // university captone major
			return true;
		}
		
		
		if ($reqType == "e")
		{
			// type "elective."  test must not be c, s, or m.
			if ($testType != "c" && $testType != "s" && $testType != "m"
				&& $testType != "uc" && $testType != "um" && $testType != "dev")
			{
				return true;
			}
		}
		
		return false;
		
	}
	
	/**
	 * Display contents of a semester list as a single semester,
	 * only displaying courses matching the requirementType.
	 * If the requirementType is "e", then we will look for anything
	 * not containing an m, s, uc, um, or c as a requirementType.
	 *
	 * @param SemesterList $listSemesters
	 * @param string $requirementType
	 * @param string $title
	 * @param bool $boolDisplayHourCount
	 * @return string
	 */
	function displaySemesterList($listSemesters, $requirementType, $title, $boolDisplayHourCount = false)
	{

		// Display the contents of a semester object
		// on the screen (in HTML)
		$pC = "";
		$pC .= $this->drawSemesterBoxTop($title);

		$countHoursCompleted = 0;
		$listSemesters->resetCounter();
		while($listSemesters->hasMore())
		{
			$semester = $listSemesters->getNext();
			if ($semester->semesterNum == -88)
			{ // These are the "added by advisor" courses.  Skip them.
				continue;
			}
			
			// First, display the list of bare courses.
			$semester->listCourses->sortAlphabeticalOrder();
			$semester->listCourses->resetCounter();
			$semIsEmpty = true;
			$semRnd = rand(0,9999);
			$pC .= "<tr><td colspan='4' class='tenpt'>
					<b><!--SEMTITLE$semRnd--></b></td></tr>";
			while($semester->listCourses->hasMore())
			{
				$course = $semester->listCourses->getNext();
				// Make sure the requirement type matches!
				if (!$this->matchRequirementType($course->requirementType, $requirementType))
				{
					continue;
				}
		
				// Is this course being fulfilled by anything?
				//if (is_object($course->courseFulfilledBy))
				if (!($course->courseListFulfilledBy->isEmpty))
				{ // this requirement is being fulfilled by something the student took...
					//$pC .= $this->drawCourseRow($course->courseFulfilledBy);
					$pC .= $this->drawCourseRow($course->courseListFulfilledBy->getFirst());
					//$countHoursCompleted += $course->courseFulfilledBy->hoursAwarded;
					$course->courseListFulfilledBy->getFirst()->boolHasBeenDisplayed = true;
					
					if ($course->courseListFulfilledBy->getFirst()->displayStatus == "completed")
					{ // We only want to count completed hours, no midterm or enrolled courses.
						//$countHoursCompleted += $course->courseListFulfilledBy->getFirst()->hoursAwarded;
            $h = $course->courseListFulfilledBy->getFirst()->hoursAwarded;
					  if ($course->courseListFulfilledBy->getFirst()->boolGhostHour == TRUE) {
					   $h = 0;
					  }
					  $countHoursCompleted += $h;						
					}
				} else {
					// This requirement is not being fulfilled...
					$pC .= $this->drawCourseRow($course);
				}
				//$pC .= "</td></tr>";
				$semIsEmpty = false;
			}

			// Now, draw all the groups.
			$semester->listGroups->sortAlphabeticalOrder();
			$semester->listGroups->resetCounter();
			while($semester->listGroups->hasMore())
			{
				//debugCT("dddd");
				$group = $semester->listGroups->getNext();
				if (!$this->matchRequirementType($group->requirementType, $requirementType))
				{
					continue;
				}

				$pC .= "<tr><td colspan='8'>";
				$pC .= $this->displayGroup($group);
				$countHoursCompleted += $group->hoursFulfilledForCredit;
				$pC .= "</td></tr>";
				$semIsEmpty = false;
			}

			if ($semIsEmpty == false)
			{
				// There WAS something in this semester, put in the title.
				
				//debugCT("replacing $semRnd with $semester->title");
				$pC = str_replace("<!--SEMTITLE$semRnd-->",$semester->title,$pC);
			}
			
		}
		
		
		// Add hour count to the bottom...
		if ($boolDisplayHourCount == true && $countHoursCompleted > 0)
		{
			$pC .= "<tr><td colspan='8'>
				<div class='tenpt' style='text-align:right; margin-top: 10px;'>
				Completed hours: $countHoursCompleted
				</div>
				";
			$pC .= "</td></tr>";
		}

		$pC .= $this->drawSemesterBoxBottom();

		return $pC;

	}

}
?>