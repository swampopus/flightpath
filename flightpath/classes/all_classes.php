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

/*
	This file simply includes all the class files.  It makes it easier
	for scripts to include just this one file (allClasses.php) rather
	than the individual class files.
*/


// Note: $levels_deep will have been set (or not) by another script.  This
// is so that you can send "../../" or whatever if you need to.  Currently
// used in routines/routines.php.
if (!isset($levels_deep)) $levels_deep = "";

// These are the various class files we need to load.  These 
// are all overridden by the files in custom/classes, even if nothing
// is changed.  This is how FlightPath lets you override core functionality
// without actually editing core files.
//
// If you need to make changes, make them in custom/classes.
require_once("_AdvisingScreen.php");
require_once("_AdvisingScreenTypeView.php");
require_once("_DatabaseHandler.php");
require_once("_Course.php");

require_once("_StandardizedTest.php");
require_once("objList.php");
require_once("_CourseList.php");
require_once("_Group.php");
require_once("_GroupList.php");
require_once("_Semester.php");

require_once("_DegreePlan.php");
require_once("_Substitution.php");
require_once("_SubstitutionList.php");
require_once("_Student.php");
require_once("_FlightPath.php");


// Now, once these are loaded, require the user-created ones...
require_once("$levels_deep" . "custom/classes/all_custom_classes.php");


?>