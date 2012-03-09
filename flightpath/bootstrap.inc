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
 * This file should be included by every public-facing page of FlightPath.
 * It will include all the classes, as well as settings and function files.
 * 
 * In the future, it will be used to pre-load modules. 
 */


// Note: $levelsDeep will have been set (or not) by another script.  This
// is so that you can send "../../" or whatever if you need to.  Currently
// used in routines/routines.php.
if (!isset($levelsDeep)) $levelsDeep = "";


// Should we load the functions file?  This variable should be
// set prior to including this bootstrap file.
if (!isset($skipFlightPathFunctions) || $skipFlightPathFunctions != TRUE) {
  // Load the common "functions.php" file.
  require_once("$levelsDeep" . "functions.php");
}

// Load the global system settings file.
require_once("$levelsDeep" . "custom/settings.php");


// Load all of the classes, as well as the custom classes.
require_once("$levelsDeep" . "classes/allClasses.php");



// Define useful constants
define("MENU_TYPE_NORMAL_ITEM", 1);
define("MENU_TYPE_CALLBACK", 2);
define("MENU_TYPE_TAB", 3);
define("MENU_ACCESS_DENIED", 4);
define("MENU_NOT_FOUND", 5);


// Include all of the enabled modules
foreach ($GLOBALS["fpSystemSettings"]["modules"] as $module => $value) {
  if (isset($value["disabled"]) && $value["disabled"] == "yes") {
    // Module is not enabled.  Skip it.
    continue;
  }
  
  includeModule($module, TRUE);
}




?>