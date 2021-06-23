<?php
 
/**
 * @file
 * The cron.php file for FlightPath, which should be run periodically.
 * 
 * This file will invoke hook_cron on all available modules.  It should be
 * accessed in a similar method as 
 * wget http://url/cron.php?t=CRON_TOKEN
 * 
 * You can find your site's cron token (and change it if you wish)
 * in your /custom/settings.php file.
 */
 
require_once("bootstrap.inc");

//$GLOBALS["fp_die_mysql_errors"] = TRUE;
//menu_rebuild_cache();

$token = @$_REQUEST["t"];
if ($token != @$GLOBALS["fp_system_settings"]["cron_security_token"]) {
  die("Sorry, cron security token does not match. View this file's
      source code for instructions on setting up your site's cron.");
}

watchdog("cron", "Cron run started", array(), WATCHDOG_DEBUG);
invoke_hook("cron");
watchdog("cron", "Cron run completed", array(), WATCHDOG_DEBUG);
variable_set("cron_last_run", time());