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

class _StandardizedTest
{
	public $categories, $description, $testID, $dateTaken;



	function __construct()
	{
		$categories = Array();
	}


	function toString()
	{
		$rtn = "";

		$rtn .= "test: $this->dateTaken - $this->testID - $this->description : \n";
		if (count($this->categories))
		{
			foreach($this->categories as $position => $value)
			{
				$rtn .= "  $position - {$value["category_id"]} - {$value["description"]} - {$value["score"]} \n";
			}
		}

		return $rtn;
	}


}




?>