<?php

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


// Load the includes files, for common functions.
require_once("$levels_deep" . "includes/theme.inc");
require_once("$levels_deep" . "includes/misc.inc");
require_once("$levels_deep" . "includes/forms.inc");
require_once("$levels_deep" . "includes/menu.inc");
require_once("$levels_deep" . "includes/db.inc");

// Should we load the settings file?  This variable should be
// set prior to including this bootstrap file.
if (!isset($skip_flightpath_settings) || $skip_flightpath_settings != TRUE) {
  // Load the global system settings file.
  require_once("$levels_deep" . "custom/settings.php");
}


// Load all of the classes, as well as the custom classes.
require_once("$levels_deep" . "classes/all_classes.php");


// Should we load the settings file?  This variable should be
// set prior to including this bootstrap file.
if (!isset($skip_flightpath_modules) || $skip_flightpath_modules != TRUE) {
  // Include all of the enabled modules
  foreach ($GLOBALS["fp_system_settings"]["modules"] as $module => $value) {
    if (isset($value["enabled"]) && $value["enabled"] != "1") {
      // Module is not enabled.  Skip it.
      continue;
    }
  
    include_module($module, TRUE);
  }
}




?>