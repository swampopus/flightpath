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
 * The name "Semester" might be a little misleading, as it usually refers
 * to years and the like.  But, it might also refer to Summer semesters.
 * Basically, its a collection of courses and groups that are required of
 * a student.  For example, the "Freshman" semester will contain courses
 * and groups to be taken Freshman year.
 *
 */
class _Semester
{
	public $title, $semesterNum, $notice;
	public $listCourses, $listGroups;
	/*
	* $title		Freshman, Sophomore, Summer II, etc.
	* $rankNum		Numeric "rank" or order of the semester object. 1,2,3, etc.
	*
	* *** MIGHT SHOULD BE A GROUP INSTEAD? A group can be a list
	*				of courses, and a list of groups.  That sounds like a semester
	*				to me.  But, if not...
	* $listCourses	This is a list of courses which are required
	* $listGroups	This is a list of the groups which are required.
	*/
	
	function __construct($semesterNum = "")
	{
		$this->semesterNum = $semesterNum;
		
		//$this->listCourses = new ObjList();
		$this->listCourses = new CourseList();
		$this->listGroups = new GroupList();
		
		$this->assignTitle();	
	}
	
	function equals(Semester $semester)
	{
		if ($this->semesterNum == $semester->semesterNum)
		{
			return true;
		}
		
		return false;			
	}
	
	function assignTitle()
	{
		if ($this->semesterNum == 0)
		{$this->title = "Freshman Year";}
		if ($this->semesterNum == 1)
		{$this->title = "Sophomore Year";}
		if ($this->semesterNum == 2)
		{$this->title = "Junior Year";}
		if ($this->semesterNum == 3)
		{$this->title = "Senior Year";}
		if ($this->semesterNum == 4)
		{$this->title = "Year 5";}
		
	}
	
	
	function toString()
	{
		$rtn = "";
		
		$rtn .= " Semester: $this->semesterNum \n";
		if (!$this->listCourses->isEmpty)
		{
			$rtn .= $this->listCourses->toString();
		}
		if (!$this->listGroups->isEmpty)
		{
			$rtn .= $this->listGroups->toString();
		}
		
		return $rtn;
	}
	
	function resetListCounters()
	{
		// Goes through all lists in the semester and
		// calls function "resetCounter" on them.
		// Important to do before we start trying to use and
		// work with the semesters.
		$this->listCourses->resetCounter();
		$this->listGroups->resetListCounters();
	}
	
} // end class Semester
?>