<?php

/**
 * This include fine mostly involves DB command shortcuts.
*/

define ('WATCHDOG_NOTICE', 101);
define ('WATCHDOG_ALERT', 102);
define ('WATCHDOG_ERROR', 103);
define ('WATCHDOG_DEBUG', 103);
/**
 * Call other modules which implement the hook_watchdog function.
 * Adapted from Drupal 6's watchdog function.
 */
function watchdog($type, $message, $variables = array(), $severity = WATCHDOG_NOTICE, $extra_data = "") {
  // TODO:  implement me.  have the system_module implement a hook_watchdog.
}


/**
 * Returns the faculty member's name based on the ID provided.
 */
function fp_get_faculty_name($faculty_id) {
  $db = get_global_database_handler();
  $name = $db->get_faculty_name($faculty_id);
  if (!$name) $name = "Unknown Advisor";
  return $name;
}




function db_query($query) {
  // Capture arguments to this function, to pass along to our $db object.
  $args = func_get_args();
  array_shift($args);  
  
  $db = get_global_database_handler();  
  $res = $db->db_query($query, $args);

  return $res;    
}

function db_fetch_array($result_handler) {
  $db = get_global_database_handler();
  return $db->db_fetch_array($result_handler);
}

function db_num_rows($result_handler) {
  $db = get_global_database_handler();
  return $db->db_num_rows($result_handler);
}



function variable_get($name, $default_value = "") {
  $res = db_query("SELECT value FROM variables
                         WHERE name = '?' ", $name);
  $cur = db_fetch_array($res);
  
  $val = unserialize($cur["value"]);
  if (!$val) {
    $val = $default_value;
  }
  
  if ($val == "BOOLEAN_FALSE_PLACEHOLDER") {
    $val = FALSE;
  }
  
  return $val;
}


function variable_set($name, $value) {
  
  // Boolean FALSE presents unusual problems when we try to tell if it got unserialized correctly.
  // We will convert it to a placeholder so we can positively store it.   
  if ($value === FALSE) {
    $value = "BOOLEAN_FALSE_PLACEHOLDER";
  }
    
  db_query("REPLACE INTO variables (name, value)
              VALUES ('?', '?') ", $name, serialize($value));
  
}

function fp_get_system_settings() {
  //$db = get_global_database_handler();
  //$settings = $db->get_flightpath_settings();
  
  if (isset($GLOBALS["fp_system_variables"])) {
    return $GLOBALS["fp_system_variables"];
  }
  
  // Get all of our settings from the variables table.
  $res = db_query("SELECT * FROM variables");
  while ($cur = db_fetch_array($res)) {
    $name = $cur["name"];
    $val = unserialize($cur["value"]);
    
    if ($val == "BOOLEAN_FALSE_PLACEHOLDER") {
      $val = FALSE;
    }
    
    $settings[$name] = $val;
    
  }
  
  
  // Make sure some important settings have _something_ set, or else it could cause
  // problems for some modules.
  if ($settings["current_catalog_year"] == "") {
    $settings["current_catalog_year"] = 2006;
  }  
  if ($settings["earliest_catalog_year"] == "") {
    $settings["earliest_catalog_year"] = 2006;
  }  
  
  $GLOBALS["fp_system_variables"] = $settings;
  
  return $settings;
    
}
