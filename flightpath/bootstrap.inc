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
// TEST

/**
 * This file should be included by every public-facing page of FlightPath.
 * It will include all the classes, as well as settings and function files.
 * 
 * In the future, it will be used to pre-load modules. 
 */


// Note: $levels_deep will have been set (or not) by another script.  This
// is so that you can send "../../" or whatever if you need to.  Currently
// used in routines/routines.php.
if (!isset($levels_deep)) $levels_deep = "";


// Should we load the functions file?  This variable should be
// set prior to including this bootstrap file.
if (!isset($skip_flightpath_functions) || $skip_flightpath_functions != TRUE) {
  // Load the includes files, for common functions.
  require_once("$levels_deep" . "includes/theme.inc");
  require_once("$levels_deep" . "includes/misc.inc");
  require_once("$levels_deep" . "includes/forms.inc");
  require_once("$levels_deep" . "includes/menu.inc");
  require_once("$levels_deep" . "includes/db.inc");
}

// Load the global system settings file.
require_once("$levels_deep" . "custom/settings.php");


// Load all of the classes, as well as the custom classes.
require_once("$levels_deep" . "classes/all_classes.php");


// Include all of the enabled modules
foreach ($GLOBALS["fp_system_settings"]["modules"] as $module => $value) {
  if (isset($value["enabled"]) && $value["enabled"] != "1") {
    // Module is not enabled.  Skip it.
    continue;
  }
  
  include_module($module, TRUE);
}




?>