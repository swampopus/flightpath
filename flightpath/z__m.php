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
  This file is the "module handler" for FlightPath.
  Modules are really just files which get included by this file,
  and then their _init() function gets called. It is up to the module
  to decide how to proceed from there, based on POST or GET variables, 
  permissions, etc.
  
  To add modules to FlightPath, you should add them to the 
  custom/modules directory, and then edit the settings.php file
  to explain where the module is located.
*/

// Allow modules to request that the session not be set.
if ($_REQUEST["nosession"] != "true") {
  session_start();
}

// Load all of our classes and settings.
require_once("bootstrap.inc");


// Now, try to figure out what the user is trying to do.

$module = trim($_REQUEST["n"]);  // name of module being requested.

if (function_exists($module . "_page_switchboard")) {
  call_user_func($module . "_page_switchboard");
}
else {
  print "<b>Sorry, the $module module is either not installed, or 
        does not have its $module" . "_page_switchboard() function
        defined, and therefore cannot be loaded.</b>";
}
  

?>