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

class _SubstitutionList extends ObjList
{

	function findRequirement($courseRequirement, $boolExcludeHasBeenApplied = false, $groupID = 0)
	{
		// Look through the array for a substitution which has this
		// course requirement.
		// If groupID == -1, no particular group is required.
		for ($t = 0; $t < count($this->arrayList); $t++)
		{
			$substitution = $this->arrayList[$t];

			if ($boolExcludeHasBeenApplied == true && $substitution->boolHasBeenApplied == true)
			{
				// Skip substitutions which we have already applied.
				continue;
			}

			$cr = $substitution->courseRequirement;
			//adminDebug($cr->courseID . " " . $courseRequirement->courseID);
			if ($groupID == -1)
			{ // No particular groupID is required...
				if ($cr->courseID == $courseRequirement->courseID)
				{
					return $substitution;
				}
			} else {
				// ONLY check if it's in the supplied groupID...
				if ($cr->courseID == $courseRequirement->courseID && $cr->assignedToGroupID == $groupID)
				{
					return $substitution;
				}

			}
		}

		return false;
	}

	function findGroupAdditions(Group $group)
	{
		$groupID = $group->groupID;
		$rtnList = new CourseList();
		// Find additions for this groupID and return them.
		for ($t = 0; $t < count($this->arrayList); $t++)
		{
			$substitution = $this->arrayList[$t];
			if ($substitution->boolGroupAddition == true)
			{
				$c = $substitution->courseRequirement;
				if ($c->assignedToGroupID == $groupID)
				{
					$cc = $substitution->courseListSubstitutions->getFirst();
					//adminDebug("~~ $cc->courseID");
					$rtnList->add($cc);
				}
			}

		}

		if (!$rtnList->isEmpty)
		{
			return $rtnList;
		} else {
			return false;
		}

	}


} // class SubstitutionList

?>