<?php

/**
 * @file
 * This file contains mostly db shortcuts.
 */


/**
 * Add a log entry to the watchdog (log) table.
 * 
 * This is adapted from Drupal 6's watchdog system.
 *
 * @param string $type
 *    Generally, this is the name of the module, or some other short text
 *    which can be used to categorize this log entry. Ex:  "system" or "routines".
 * @param string $message
 *    This is the actual log message itself.  It can be any length, and contain replacement
 *    patterns (very similar to the t() function.) Ex: "The user @user has logged in."
 * @param array $variables
 *    If replacement patterns exist in the $message, this is where they are defined, similar
 *    to the t() function. Ex:  array("@user" => $user->name) *    
 * @param int $severity
 *    One of several constant values, denoting the severity level.
 *    Available values:
 *      - WATCHDOG_DEBUG    (for development)
 *      - WATCHDOG_NOTICE   (default)
 *      - WATCHDOG_ALERT    (a step above "notice")
 *      - WATCHDOG_ERROR    (highest level of severity)
 * @param string $extra_data
 *    Any extra bit of text you want to add on. Must be 255 characters or less.  Good for adding
 *    extra codes and such which can be queried for later more easily.
 */
function watchdog($type, $message, $variables = array(), $severity = WATCHDOG_NOTICE, $extra_data = "") {
  global $user;
  
  // TODO:  Have a setting where, we do not actually log certain severity levels, like notice or debug
  // (to save space)
  
  $user_id = 0;
  $cwid = 0;
  $user_name = "";
  $is_student = 0;
  $is_faculty = 0;
      
  if (is_object($user)) {
    $user_id = @$user->id;
    $cwid = @$user->cwid;
    $user_name = @$user->name;
    $is_student = (int) @$user->is_student;
    $is_faculty = (int) @$user->is_faculty;
  }
  
  $is_mobile = (int) fp_screen_is_mobile();
  
  $ip = $_SERVER["REMOTE_ADDR"];
  $location = $_SERVER["REQUEST_URI"];
  $referer = $_SERVER['HTTP_REFERER'];
  
  $ser_variables = "";
  if (count($variables) > 0) {
    $ser_variables = serialize($variables);
  }
  
  db_query("INSERT INTO watchdog 
            (user_id, user_name, cwid, type, message, variables, severity, extra_data, location, referer, ip, is_mobile, is_student, is_faculty, timestamp)
            VALUES
            ('?', '?', '?', '?', '?', '?', '?', '?', '?', '?', '?', '?', '?', '?', '?')
            ", $user_id, $user_name, $cwid, $type, $message, $ser_variables, $severity, $extra_data, $location, $referer, $ip, $is_mobile, $is_student, $is_faculty, time());
  
  
}


/**
 * Returns the faculty member's name based on the ID provided.
 */
function fp_get_faculty_name($cwid) {
  $db = get_global_database_handler();
  $name = $db->get_faculty_name($cwid);
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
  $rtn->cwid = $cur->cwid;
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





/**
 * Look up the user_id based on the the user_name.  Returns FALSE if it cannot find it.
 *
 * @param unknown_type $user_name
 */
function db_get_user_id($user_name) {
  $user_id = db_result(db_query("SELECT user_id FROM users WHERE user_name = '?' ", $user_name));
  
  if ($user_id) {
    return $user_id;
  }
  
  return FALSE;
  
}


function db_get_user_id_from_cwid($cwid, $type = "faculty") {
  
  $type_line = " is_faculty='1' ";
  if ($type == "student") {
    $type_line = " is_student='1' ";
  }
    
  $user_id = db_result(db_query("SELECT user_id FROM users WHERE cwid = '?' AND $type_line ", $cwid));
  
  if ($user_id) {
    return $user_id;
  }
  
  return FALSE;
  
}


function fp_get_student_name($cwid) {
  if ($cwid == 0) {
    return t("Anonymous");
  }
  $db = get_global_database_handler();
  $name = $db->get_student_name($cwid);
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
function db_result($res) {

  if (is_object($res)) {
    return $res->fetchColumn();  // fetches the first value returned.
  }  
  
  return NULL;
}


function db_insert_id() {
  $db = get_global_database_handler();  
  return $db->db_insert_id();
}


/**
 * Return the array from the user_settings table.
 *
 * @param unknown_type $user_id
 */
function db_get_user_settings($user_id) {
  $db = get_global_database_handler();
  
  return $db->get_user_settings($user_id);
}

/**
 * Return a specific setting's value, based on the var_name given.
 *
 * @param unknown_type $user_id
 * @param unknown_type $var_name
 */
function db_get_user_setting($user_id, $var_name = "", $default_value = "") {
  $settings = db_get_user_settings($user_id);  
  $val = @$settings[$var_name];
  if (!$val) {
    $val = $default_value;
  }
  
  return $val;
}


function db_set_user_setting($user_id, $var_name, $value) {
  $settings = db_get_user_settings($user_id);
  $settings[$var_name] = $value;
  
  $ser = serialize($settings);
  
  db_query("DELETE FROM user_settings WHERE user_id = '?' ", $user_id);
  db_query("INSERT INTO user_settings (user_id, settings, posted)
            VALUES ('?', '?', '?') ", $user_id, $ser, time());
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

function db_affected_rows() {
  $db = get_global_database_handler();
  return $db->db_affected_rows();  
}


/**
 * Returns TRUE if the table specified exists or not.
 */
function db_table_exists($table_name) {
  $res = db_query("SHOW TABLES LIKE ? ", $table_name);
  $cur = db_fetch_array($res);
  if ($cur[0] == $table_name) {
    return TRUE;
  }
  
  return FALSE;
  
}


/**
 * Get a variable from the database.  We will first look in our GLOBALS array,
 * to see that it hasn't already been retrieved this page load.
 *
 * @param unknown_type $name
 * @param unknown_type $default_value
 * @return unknown
 */
function variable_get($name, $default_value = "") {
  
  $val = null;
  
  // First, check in our GLOBALS array, like a cache...
  if (isset($GLOBALS["fp_system_settings"][$name])) {
    $val = $GLOBALS["fp_system_settings"][$name];
  }
  else {    
    // Not found-- look in the database for it.
    $res = db_query("SELECT value FROM variables
                           WHERE name = '?' ", $name);
    $cur = db_fetch_array($res);    
    $val = unserialize($cur["value"]);
    
    if ($val === "BOOLEAN_FALSE_PLACEHOLDER") {
      $val = FALSE;
    }
    
    // Save back to our cache...
    $GLOBALS["fp_system_settings"][$name] = $val;
  }

  
  if (!$val) {
    $val = $default_value;
  }

  // We must have this down here again, just in case what got stored in the GLOBALS
  // array was this placeholder.  This can happen, because the settings file doesn't do
  // this check when assembling this variable on page load.  It's something that needs
  // to be fixed.
  if ($val === "BOOLEAN_FALSE_PLACEHOLDER") {
    $val = FALSE;
  }
  
  return $val;
}


/**
 * Set a variable value, so we can retrieve it later on.
 * 
 * This will write to our variables database table, as well as store in a cache
 * array for quick look-up later.
 *
 * @param unknown_type $name
 * @param unknown_type $value
 */
function variable_set($name, $value) {

  // Save back to our "cache" GLOBALS array:
  $GLOBALS["fp_system_settings"][$name] = $value;
  
  
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
  
  if ($force_rebuild == FALSE && isset($GLOBALS["fp_system_settings"])) {
    return $GLOBALS["fp_system_settings"];
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
    $GLOBALS["fp_system_settings"][$name] = $val;
    
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
