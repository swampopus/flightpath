<?php

/**
 * @file
 * The primary entry point for FlightPath.
 * 
 * This script will determine which page the user is trying to view, 
 * and display it for them.
 */


session_start();

header("Cache-control: private");


// If the settings.php file doesn't exist, then FlightPath must not be installed,
// and we should redirect to install.php.
if (!file_exists("custom/settings.php")) {
  header ("Location: install.php");
  die;
}


require_once("bootstrap.inc");

// For development reasons only:
// To rebuild the cache on every page load, uncomment the following line
// menu_rebuild_cache();

// FlightPath will now look at the request in the query to decide what page we are going to display.
$page = menu_execute_page_request();

if (!is_int($page)) {
  // Display the page!  
  fp_display_page($page);
}
else {  
  if ($page == MENU_NOT_FOUND) {
    display_not_found();
  }
  else if ($page == MENU_ACCESS_DENIED) {
    display_access_denied();
  }
}

// Call hook_exit as we leave the page.
invoke_hook("exit"); 
 
 