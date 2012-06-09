<?php
/**
 * This is the settings file for your installation of FlightPath.
 * You should copy the default.settings.php file to a new file,
 * named settings.php.  All your changes should happen there.
 * 
 * Once you have made changes to it, it might be best to change
 * the permissions to "read-only" to prevent unauthorized users
 * from altering it.
 */
 
// Set the PHP error reporting level for FlightPath.  In this case,
// only show us errors and warnings. (Hide "notice" and "strict" messages)
error_reporting(E_ERROR | E_WARNING);
 
 
/**
 * All system settings will be placed (at the end of this script)
 * into a $GLOBALS variable, but for now will be placed into an
 * array.
 */
 
$system_settings = array();

////////////////////////////////////
// !!!  *** IMPORTANT !!!  ***    //
////////////////////////////////////
// If this variable is set to TRUE, then anyone who attempts to log in
// will have full, admin access.
// Only set this to TRUE when you are first installing FlightPath.
// Otherwise, leave it set to FALSE!
$system_settings["GRANT_FULL_ACCESS"] = FALSE;
////////////////////////////////////

// This should be the actual filesystem path to the directory
// where FlightPath is installed.  Do NOT include a trailing slash!
// Ex: /var/www/public_html/flightpath  or, for Windows: C:/htdocs/flightpath
// ** Depending on your webserver, you may be required to use forward-slashes! **
// use the following line to help you figure out the fileSystemPath, by seeing
// what the path is to this file:
// print "<br>System path to settings.php: " . __FILE__ . "<br><br>";
$system_settings["file_system_path"] = "/www/webservices/flightpath";

// The baseURL is the actual URL a user would type to visit your site.
// Do NOT enter a trailing slash!
// Ex:  http://localhost/flightpath
$system_settings["base_url"] = "http://localhost/flightpath-advising/flightpath";

// The basePath is related to the baseURL.  It is the parth of the URL which comes after
// your domain name.
// It MUST begin with a preceeding slash.
// Ex: If your site is example.com/dev/flightpath, then you should
// enter  "/dev/flightpath" 
$system_settings["base_path"] = "/flightpath-advising/flightpath";


////////////////////////////////////
// *** Database-related settings ***
////////////////////////////////////
$system_settings["db_host"] = "localhost"; // domain/ip address of the mysql host. ex: localhost or mysite.com:32145
$system_settings["db_user"] = "fpuser"; 
$system_settings["db_pass"] = "fpuserpass"; 
$system_settings["db_name"] = "flightpath"; // Name of the actual database where
                                              // flightpath's tables are located.
                                              // Usually just "flightpath"




/////////////////////////////////////
// *** Defaults ***                //
/////////////////////////////////////
// These default settings are for installation reasons only. 
// They will be overwritten in memory
// once the flightpath_system_settings table is read in at the end of the file.
// Do not alter them.
$system_settings["display_mysql_errors"] = TRUE;
$system_settings["theme"] = "themes/classic";


////////////////////////////////////
// *** Misc Settings ***
////////////////////////////////////
// To cut down on load times when the user loads a large elective group
// containing many courses, FlightPath can load some of the course inventory
// upon login.  Set the number of courses to load here.
$system_settings["load_course_inventory_on_login_number"] = 2000;


////////////////////////////////////
// *** Cron-related ***
////////////////////////////////////
// If you wish to use cron.php (which will call every module's
// hook_cron() function), you may set up a cron job like this:
//      php cron.php security_token_string
/* 
 SecurityToken:  This is something which
 must be the first argument passed to cron.php.  It can be any continuous
 string of *alpha-numeric* characters.  If it is blank, then no securityToken 
 will be required.
 This is a security measure to prevent unauthorized users (or web-users) from
 running cron.php, and is REQUIRED!
 For example, if the token is "qwss34frwquu" then to run the script you would need
 to use:   php cron.php qwss34frwquu
*/
$system_settings["cron_security_token"] = "abc123";

// The cron will automatically delete log entries (in the log table)
// older than this many days when it runs.  Logs can be very useful (and are
// required by the stats module) but can also take up a lot of space.
// It is recommended that you do not set this lower than 365 days, unless
// you know for sure that you will not need logs older than that.
// For safety, you cannot set this lower than 30 days.
// Comment out to disable.
$system_settings["cron_delete_logs_older_than_days"] = 400;



/////////////////////////////////////
/////////////////////////////////////
//
// The end of the settings file!

/////////////////////////////////////
/////////////////////////////////////
/////////////////////////////////////
// Do not alter or remove!!
// This will load the contents of the flightpath_system_settings
// table into the $system_settings variable.  These are extra settings
// which were set via the web using the system module.
$db_host = $system_settings["db_host"];
$db_user = $system_settings["db_user"];
$db_pass = $system_settings["db_pass"];
$db_name = $system_settings["db_name"];
$dbc = mysql_connect ($db_host, $db_user, $db_pass) or die('Could not connect to database: ' . mysql_error());
mysql_select_db ($db_name);

$res = mysql_query("SELECT * FROM flightpath_system_settings");
while ($cur = mysql_fetch_array($res)) {
  if ($val = unserialize($cur["value"])) {
    $system_settings[$cur["name"]] = $val;
  }
}

$res = mysql_query("SELECT * FROM modules WHERE enabled = 1
                    ORDER BY weight");
while ($cur = mysql_fetch_array($res)) {
  $system_settings["modules"][$cur["name"]] = $cur;
}

mysql_close($dbc);

// We want to make sure the "system" module is enabled, so we will hard-code
// its values.
if ($system_settings["modules"]["system"]["enabled"] != 1) {
  $system_settings["modules"]["system"]["path"] = "modules/system";
  $system_settings["modules"]["system"]["enabled"] = 1;
}

////////////////////////////////////////////
////////////////////////////////////////////
// This must appear at the VERY end!  Nothing should come after it....
//
// Assign our systemSettings to the GLOBALS array so we can access it anywhere.
$GLOBALS["fp_system_settings"] = $system_settings;

//////////////////////////////////////////////
//////////////////////////////////////////////
// PUT NOTHING BELOW THIS LINE!!!!


