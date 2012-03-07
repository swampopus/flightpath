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

class ObjList
{

	public $arrayList, $i, $isEmpty, $count;

	function __construct()
	{
		$this->arrayList = array();
		$this->i = 0;
		$this->count = 0;
		$this->isEmpty = true;
	}


	function add($c, $boolAddToTop = false)
	{
		// Adds courses to the list.  Remember to perform
		// resetCounter before using this list, or the count
		// variable will be off!
		if ($boolAddToTop == false)
		{
			$this->arrayList[] = $c;
			//adminDebug(".....adding course");
		} else {
			// We are going to add this to the top of the array, pushing
			// everything else down.
			$tempArray = array();
			$tempArray[0] = $c;
			
			$newArray = array_merge($tempArray, $this->arrayList);
			$this->arrayList = $newArray;
			// adminDebug("adding to top...");
		}
		
			$this->isEmpty = false;
			$this->count = count($this->arrayList);
		
		
	}

	function indexOf($objC)
	{
		// Find in the array an object.equals(objC), and return the
		// index.

		for ($t = 0; $t < count($this->arrayList); $t++)
		{
			if ($this->arrayList[$t]->equals($objC))
			{
				return $t;
			}
		}

		return -1;

	}

	function checkIsEmpty()
	{
		if (count($this->arrayList) > 0)
		{
			$this->isEmpty = false;
		}
	}

	function objectIndexOf($objC)
	{
		// This will return the array index of the exact object being requested.
		// Not the ->equals(), but rather an == of the object (the reference is the same)

		for ($t = 0; $t < count($this->arrayList); $t++)
		{
			if ($this->arrayList[$t] == $objC)
			{
				return $t;
			}
		}

		return -1;


	}


	function resetCounter()
	{
		$this->i = 0;
		$this->count = count($this->arrayList);
	}

	function getFirst()
	{
		if ($this->getSize() > 0)
		{
			return $this->getElement(0);
		} else {
			return false;
		}
	}


	function getElement($c)
	{

		return $this->arrayList[$c];
	}



	function findMatch($objC)
	{ // This actually returns an object if it can find
		// it using indexOf.

		$c = $this->indexOf($objC);
		if ($c > -1)
		{
			return $this->getElement($c);
		} else {
			return false;
		}
	}


	function insertAfterIndex($newI, $objC)
	{
		$rtn = new ObjList();
		for ($t = 0; $t < $newI; $t++)
		{
			$rtn->add($this->arrayList[$t]);
		}

		$rtn->add($objC);

		for ($t = $newI; $t < count($this->arrayList); $t++)
		{
			$rtn->add($this->arrayList[$t]);
		}

		$this->arrayList = $rtn->arrayList;
		$this->count = count($this->arrayList);

	}


	function findAllMatches($objC)
	{
		// This will find all the matches of objC in the
		// array, and return an ObjList of matches.
		$rtn = new ObjList();
		$boolNoMatches = true;
		for ($t = 0; $t < $this->count; $t++)
		{
			if ($this->arrayList[$t]->equals($objC))
			{
				$rtn->add($this->arrayList[$t]);
				$boolNoMatches = false;
			}
		}

		if ($boolNoMatches == false)
		{
			return $rtn;
		} else {
			return false;
		}

	}


	function getSize()
	{
		return sizeof($this->arrayList);
	}



	function toString()
	{
		// Return a string of every obj in this list.
		$rtn = "";

		for ($t = 0; $t < $this->count; $t++)
		{
			$rtn .= $this->arrayList[$t]->toString();
		}

		return $rtn;
	}

	function hasMore()
	{
		//adminDebug("here " . count($this->arrayList));
		if ($this->i < $this->count)
		{
			return true;
		} else {
			return false;
		}
	}

	function getNext()
	{
		$s = $this->arrayList[$this->i];
		$this->i++;
		return $s;
	}


}

?>