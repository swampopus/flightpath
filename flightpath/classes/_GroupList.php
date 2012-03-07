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

class _GroupList extends ObjList
{
	/*
	This class inherits mosts of its classes from ObjList
	in a similar fashion as CourseList.
	*/

	
	function resetListCounters()
	{
		$this->resetCounter();
		// Also, go through all groups in the list and call
		// their "resetListCounters" method.
		for ($t = 0; $t < $this->count; $t++)
		{
			$group = $this->arrayList[$t];
			$group->resetListCounters();
		}		
	}
	
	
	function containsGroupRequirementID($groupRequirementID)
	{
		// Returns true if any of the lists of courses in these groups
		// contain the group requirement ID.
		for ($t = 0; $t < count($this->arrayList); $t++)
		{
			$group = $this->arrayList[$t];
			if ($group->listCourses->containsGroupRequirementID($groupRequirementID))
			{
				return true;
			}
		}		
		
		return false;
		
	}
	
	
	function getAdvisedCoursesList()
	{
		// Return a courseList object of courses in THIS
		// group which have boolAdvisedToTake == true.
		$rtnList = new CourseList();
		for ($t = 0; $t < count($this->arrayList); $t++)
		{
			$group = $this->arrayList[$t];
			$rtnList->addList($group->listCourses->getAdvisedCoursesList());
			
			$group->listGroups->resetCounter();
			while($group->listGroups->hasMore())
			{
				$gg = $group->listGroups->getNext();
				$rtnList->addList($gg->listCourses->getAdvisedCoursesList());
			}
		}		
		
		$rtnList->removeDuplicates();
		
		return $rtnList;
		
	}
	
	
	function assignMinGrade($minGrade)
	{
		// Assign a min grade to every group in this grouplist.
		for ($t = 0; $t < count($this->arrayList); $t++)
		{
			$group = $this->arrayList[$t];
			$group->assignMinGrade($minGrade);
		}					
		
		
	}
	
	function sortPriority()
	{
		/*
			Sort this list of groups by their priority number.
			Higher priorities should appear at the
			top of the list.
		*/
		$tarray = array();
		// Since I need the indexes, I will have to go through the array
		// myself...
		for ($t = 0; $t < count($this->arrayList); $t++)
		{
			$g = $this->arrayList[$t];
			$g->loadDescriptiveData();
			$pri = "" . ($g->priority*1) . "";
			if (strlen($pri) == 1)
			{
				$pri = "0" . $pri; // padd with 0 on the front.
				// This fixes a sorting problem, because priorities
				// were being evaluated as text, not nums, so "5" seemed
				// larger than "15"  (because it was comparing the 5 to the 1).
			}
			$str = "$pri ~~ $t";

			array_push($tarray,$str);
		}


		rsort($tarray);

		// Now, convert the array back into a list of groups.
		$newList = new GroupList();
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
	
	function sortAlphabeticalOrder($boolReverseOrder = false)
	{

		$tarray = array();
		// Since I need the indexes, I will have to go through the array
		// myself...
		for ($t = 0; $t < count($this->arrayList); $t++)
		{
			$g = $this->arrayList[$t];
			$g->loadDescriptiveData();
			$str = "$g->title ~~ $t";

			array_push($tarray,$str);
		}

		if ($boolReverseOrder == true)
		{
			rsort($tarray);
		} else {
			sort($tarray);
		}

		// Now, convert the array back into a list of groups.
		$newList = new GroupList();
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



}




?>