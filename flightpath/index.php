<?php
//header("Location: main.php");

session_start();
header("Cache-control: private");

require_once("bootstrap.inc");

menu_rebuild_cache();

// FlightPath will now look at the request in the query to decide what page we are going to display.
$page = menu_execute_page_request();

if (!is_int($page)) {
  // Display the page!
  fp_display_page($page);
}

