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

class _Substitution
{
	public $courseRequirement; // The original degree requirement.
	public $courseListSubstitutions; // The course(s) which are
	//filling in for that requirement.
	public $boolHasBeenApplied;
	public $boolGroupAddition;
	public $facultyID;  // The faculty member that made the substitution.

	public $boolOutdated;  // set to true if this is an outdated sub (for an old major or the like)
	public $remarks; // like a comment for the substitution.

	public $outdatedNote; // will contain information about WHY this was outdated.
	
	function __construct()
	{

		$this->courseRequirement = new Course();
		$this->courseListSubstitutions = new CourseList();
		$this->boolGroupAddition = false;
		$this->boolOutdated = false;
	}

	function toString()
	{
		$rtn = "";
		if ($this->boolGroupAddition)
		{
			$ga = "group addition ";
		}
		$rtn .= "Substitution: $ga " . $this->courseRequirement->toString() . " fulfilled by ";
		$rtn .= $this->courseListSubstitutions->toString() . "\n";
		$tcrgroup = new Group($this->courseRequirement->assignedToGroupID);
		$rtn .= "CR group: " . $tcrgroup->title . " CR semester num: " . $this->courseRequirement->assignedToSemesterNum . "\n";
		$tsubgroup = new Group($this->courseListSubstitutions->getFirst()->assignedToGroupID);
		$rtn .= "Sub group: " . $tsubgroup->title . " Sub semester num: " . $this->courseListSubstitutions->getFirst()->assignedToSemesterNum . "\n";


		return $rtn;
	}
} // end class Substitution




?>