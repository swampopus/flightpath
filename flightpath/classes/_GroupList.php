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

require_once("_obj_list.php");

class __group_list extends ObjList
{
	/*
	This class inherits mosts of its classes from ObjList
	in a similar fashion as CourseList.
	*/

	
	function reset_list_counters()
	{
		$this->reset_counter();
		// Also, go through all groups in the list and call
		// their "reset_list_counters" method.
		for ($t = 0; $t < $this->count; $t++)
		{
			$group = $this->array_list[$t];
			$group->reset_list_counters();
		}		
	}
	
	
	function contains_group_requirement_id($group_requirement_id)
	{
		// Returns true if any of the lists of courses in these groups
		// contain the group requirement ID.
		for ($t = 0; $t < count($this->array_list); $t++)
		{
			$group = $this->array_list[$t];
			if ($group->list_courses->contains_group_requirement_id($group_requirement_id))
			{
				return true;
			}
		}		
		
		return false;
		
	}
	
	
	function get_advised_courses_list()
	{
		// Return a courseList object of courses in THIS
		// group which have boolAdvisedToTake == true.
		$rtn_list = new CourseList();
		for ($t = 0; $t < count($this->array_list); $t++)
		{
			$group = $this->array_list[$t];
			$rtn_list->add_list($group->list_courses->get_advised_courses_list());
			
			$group->list_groups->reset_counter();
			while($group->list_groups->has_more())
			{
				$gg = $group->list_groups->get_next();
				$rtn_list->add_list($gg->list_courses->get_advised_courses_list());
			}
		}		
		
		$rtn_list->remove_duplicates();
		
		return $rtn_list;
		
	}
	
	
	function assign_min_grade($min_grade)
	{
		// Assign a min grade to every group in this grouplist.
		for ($t = 0; $t < count($this->array_list); $t++)
		{
			$group = $this->array_list[$t];
			$group->assign_min_grade($min_grade);
		}					
		
		
	}
	
	function sort_priority()
	{
		/*
			Sort this list of groups by their priority number.
			Higher priorities should appear at the
			top of the list.
		*/
		$tarray = array();
		// Since I need the indexes, I will have to go through the array
		// myself...
		for ($t = 0; $t < count($this->array_list); $t++)
		{
			$g = $this->array_list[$t];
			$g->load_descriptive_data();
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
		$new_list = new GroupList();
		for($t = 0; $t < count($tarray); $t++)
		{
			$temp = split(" ~~ ",$tarray[$t]);
			$i = $temp[1];

			$new_list->add($this->array_list[$i]);
		}

		// Okay, now $new_list should contain the correct values.
		// We will transfer over the reference.
		$this->array_list = $new_list->array_list;
		
		
	}
	
	function sort_alphabetical_order($bool_reverse_order = false)
	{

		$tarray = array();
		// Since I need the indexes, I will have to go through the array
		// myself...
		for ($t = 0; $t < count($this->array_list); $t++)
		{
			$g = $this->array_list[$t];
			$g->load_descriptive_data();
			$str = "$g->title ~~ $t";

			array_push($tarray,$str);
		}

		if ($bool_reverse_order == true)
		{
			rsort($tarray);
		} else {
			sort($tarray);
		}

		// Now, convert the array back into a list of groups.
		$new_list = new GroupList();
		for($t = 0; $t < count($tarray); $t++)
		{
			$temp = split(" ~~ ",$tarray[$t]);
			$i = $temp[1];

			$new_list->add($this->array_list[$i]);
		}

		// Okay, now $new_list should contain the correct values.
		// We will transfer over the reference.
		$this->array_list = $new_list->array_list;
		
				
	}



}




?>