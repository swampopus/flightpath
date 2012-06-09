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
  
  // TODO:  Have a setting where, we do not actually log certain severity levels, like notice or debug
  // (to save space)
  
  
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
  if (!$name) $name = t("Unknown Advisor");
  return $name;
}


/**
 * Returns back a user object for this user_id.
 * If the user is not found in the users table, it will return NULL.
 * If the user_id requested is 0, the anonymous user object is returned.
 */
function fp_load_user($user_id) {
  
  $rtn = new stdClass();
  
  if ($user_id == 0) {
    // Return the anonymous user.
    $rtn->id = 0;
    $rtn->name = t("Anonymous");
    $rtn->roles = array(1 => "anonymous user");
    $rtn->permissions = fp_get_permissions_for_role(1);
    return $rtn;        
  }
  
  $res = db_query("SELECT * FROM users WHERE user_id = '?' ", $user_id);
  if (db_num_rows($res) == 0) return NULL;
  $cur = db_fetch_object($res);
  
  $rtn->id = $cur->user_id;
  $rtn->name = $cur->user_name;
  $rtn->f_name = $cur->f_name;
  $rtn->l_name = $cur->l_name;
  $rtn->email = $cur->email;
  $rtn->is_student = (bool) $cur->is_student;
  $rtn->is_faculty = (bool) $cur->is_faculty;
  $rtn->roles = array();
  $rtn->permissions = array();
    
  // Load the user's roles and    
  $res = db_query("SELECT * FROM user_roles a,                                
                                roles c
                  WHERE a.user_id = '?'
                  AND a.rid = c.rid", $user_id);
  while($cur = db_fetch_array($res)) {
    $rtn->roles[$cur["rid"]] = $cur["name"];
  }
      
  // Let's make sure we get the authenticated user role as well, #2.
  $rtn->roles[2] = "authenticated user";
        
  // Go through each role and add in the permissions for each role.
  foreach ($rtn->roles as $rid => $val) {
    $perms = fp_get_permissions_for_role($rid);
    // Merge the arrays while KEEPING the original's key.  So don't
    // use array_merge, use the + operator.
    $rtn->permissions = $rtn->permissions + $perms;
  } 
  
  
  return $rtn;  
}






function fp_get_student_name($student_id) {
  if ($student_id == 0) {
    return t("Anonymous");
  }
  $db = get_global_database_handler();
  $name = $db->get_student_name($student_id);
  if (!$name) $name = t("Unknown Student");
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
