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



// We need to discover what the "levelsDeep" might be, based on the arguments
// used to run cron.php.
// Ex:  If it was run with:
//   php flightpath/cron.php
// then the levelDeep = "flightpath/"

$temp = trim($argv[0]);
$levelsDeep = trim(str_replace("cron.php", "", $temp));

require_once("bootstrap.inc");

if ($GLOBALS["fpSystemSettings"]["cronSecurityToken"] == "") {
  print "\n\n!! A security token has not been set up in the custom/settings.php file.";
  print "\n!! This is required in order to run cron.php";
  print "\n\n";
  die;
}

if ($GLOBALS["fpSystemSettings"]["cronSecurityToken"] != "" 
    && $argv[1] != $GLOBALS["fpSystemSettings"]["cronSecurityToken"]) {
  print "\n\nSecurity token is invalid or not supplied.\n\n";
  print "   Usage: php cron.php security_token_goes_here \n\n";
  die;
}

// If we are here, it means that we have been granted access to continue
// running the cron.  We will set up a GLOBALS variable which will let us
// verify in any other scripts we run that were have been granted access (optional)
$GLOBALS["fpCronGrantedAccess"] = $argv[1];


// Cron is responsible for pruning the log table.  Execute the query now
// to do that.
$db = getGlobalDatabaseHandler();
$olderThanDays = $GLOBALS["fpSystemSettings"]["cronDeleteLogsOlderThanDays"];
if ($olderThanDays != "" && is_numeric($olderThanDays)) {
  if ($olderThanDays < 30) $olderThanDays = 30;
  $db->dbQuery("DELETE FROM log WHERE datetime < (NOW() - INTERVAL $olderThanDays DAY)");
}

// Okay, we will now go through all of our modules and call their hook_cron()
// method, if one exists.
foreach ($GLOBALS["fpSystemSettings"]["modules"] as $module => $value) {
  if (isset($value["disabled"]) && $value["disabled"] == "yes") {
    // Module is not enabled.  Skip it.
    continue;
  }

  if (function_exists($module . "_cron")) {
    call_user_func($module . "_cron");
  }
  
}

?>