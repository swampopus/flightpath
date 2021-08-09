<?php
/**
 * This file is meant to be used entirely via the command-line interface.  It will not function for web browsers.
 * 
 * Example use:  php cli.php clear-cache   (clears all cache)
 * 
*/


if (!flightpath_is_cli()) {
  print "\n\n==========================\n\n";
  print "Sorry, this script is only available as a command-line tool.";
  print "\n\n==========================\n\n";
  die;
}


print "\n\n----------------------\n";
print "FlightPath Command-Line Interface Script\n";
print "----------------------\n\n";


// Keep the script from timing out prematurely...
set_time_limit(300);


// Include the FlightPath bootstrap file, which will load the minimum files and modules
// to run FlightPath from the command line.
require_once("bootstrap.inc");
$GLOBALS["fp_die_mysql_errors"] = TRUE;


// Get the command being issued to the script.  Ex: php cli.php COMMAND
$command_one = trim($argv[1]);



// Based on command, perform action.

if ($command_one == 'clear-cache') {
  print "\n - Clearing cache...";  
  fp_clear_cache();
  print "\n --> Cache has been cleared.";
}











print "\n\n Finished execution.";
print "\n\n----------------------\n";

die;  // Finished with the script.  Functions go below to make it more tidy.
///////////////////////////////////////////////////
///////////////////////////////////////////////////
///////////////////////////////////////////////////
/**
 *  Returns TRUE or FALSE if we are in CLI mode.  Borrowed code from Drupal 7: https://api.drupal.org/api/drupal/includes!bootstrap.inc/function/drupal_is_cli/7.x
 */
function flightpath_is_cli() {
  return !isset($_SERVER['SERVER_SOFTWARE']) && (php_sapi_name() == 'cli' || is_numeric($_SERVER['argc']) && $_SERVER['argc'] > 0);
}














