<?php

/**
 * This include fine mostly involves DB command shortcuts.
*/ 

define ('WATCHDOG_NOTICE', 5);
define ('WATCHDOG_ALERT', 1);
define ('WATCHDOG_ERROR', 3);
define ('WATCHDOG_DEBUG', 7);
/**
 * Call other modules which implement the hook_watchdog function.
 * Adapted from Drupal 6's watchdog function.
 */
function watchdog($type, $message, $variables = array(), $severity = WATCHDOG_NOTICE, $extra_data = "") {
  global $user;
  
  $user_id = $user->id;
  $user_name = $user->name;
  $is_student = (int) $user->is_student;
  $is_faculty = (int) $user->is_faculty;
  
  $is_mobile = (int) fp_screen_is_mobile();
  
  $ip = $_SERVER["REMOTE_ADDR"];
  $location = $_SERVER["REQUEST_URI"];
  $referer = $_SERVER['HTTP_REFERER'];
  
  $ser_variables = "";
  if (count($variables) > 0) {
    $ser_variables = serialize($variables);
  }
  
  db_query("INSERT INTO watchdog 
            (user_id, user_name, type, message, variables, severity, extra_data, location, referer, ip, is_mobile, is_student, is_faculty, timestamp)
            VALUES
            ('?', '?', '?', '?', '?', '?', '?', '?', '?', '?', '?', '?', '?', '?')
            ", $user_id, $user_name, $type, $message, $ser_variables, $severity, $extra_data, $location, $referer, $ip, $is_mobule, $is_student, $is_faculty, time());
  
  
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

function fp_get_permissions_for_role($rid) {
  $rtn = array();
  $res = db_query("SELECT * FROM role_permissions
                   WHERE rid = '?' ", $rid);
  while($cur = db_fetch_array($res)) {
    $rtn[$cur["pid"]] = $cur["perm"];
  }
  return $rtn;
}


/**
 * Returns back the first result from a resource_handler.
 */
function db_result($resource_handler) {
  $cur = db_fetch_array($resource_handler);
  return $cur[0];
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

function db_fetch_object($result_handler) {
  $db = get_global_database_handler();
  return $db->db_fetch_object($result_handler);  
}

function db_num_rows($result_handler) {
  $db = get_global_database_handler();
  return $db->db_num_rows($result_handler);
}


/**
 * Returns TRUE if the table specified exists or not.
 */
function db_table_exists($table_name) {
  $res = db_query("SHOW TABLES LIKE '?' ", $table_name);
  $cur = db_fetch_array($res);
  if ($cur[0] == $table_name) {
    return TRUE;
  }
  
  return FALSE;
  
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


/**
 * Re-query the modules table and re-add to our global array.
 */
function fp_rebuild_modules_list($reinclude = TRUE) {
  unset($GLOBALS["fp_system_settings"]["modules"]);
  
  $res = db_query("SELECT * FROM modules WHERE enabled = 1
                      ORDER BY weight");
  while ($cur = db_fetch_array($res)) {
         
    $GLOBALS["fp_system_settings"]["modules"][$cur["name"]] = $cur;

    if ($reinclude) {
      include_module($cur["name"], FALSE);
    }
    
  }
  
  
}



function fp_get_system_settings($force_rebuild = FALSE) {
  //$db = get_global_database_handler();
  //$settings = $db->get_flightpath_settings();
  
  if ($force_rebuild == FALSE && isset($GLOBALS["fp_system_variables"])) {
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
