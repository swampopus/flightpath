<?php


/**
 * This file contains misc functions for FlightPath
 */

/////////////////////////////////////////////////////////////////////


/**
 * If this program is called, it will override any other page tabs
 * which might be getting constructed.  This lets the programmer,
 * at run-time, completely control what tabs are at the top of the page.
 */
function fp_set_page_tabs($tab_array) {
  $GLOBALS["fp_set_page_tabs"] = $tab_array;
}
function fp_set_page_sub_tabs($tab_array) {
  $GLOBALS["fp_set_page_sub_tabs"] = $tab_array;
}

function fp_set_title($title) {
  $GLOBALS["fp_set_title"] = $title;
}

/**
 * Simple function to split a basiv CSV string, trim all elements, then return
 * the resulting array.
 */
function csv_to_array($csv_string) {
  $temp = explode(",", $csv_string);
  $temp = array_map("trim", $temp);
  return $temp;
}


function fp_add_message($msg, $type = "status") {
  $_SESSION["fp_messages"][] = array("type" => $type, "msg" => $msg);
}

function fp_add_css($path_to_css) {
  $GLOBALS["fp_extra_css"][] = $path_to_css;
}

function fp_add_js($path_to_js) {
  $GLOBALS["fp_extra_js"][] = $path_to_js;
}


function fp_get_module_path($module, $bool_include_file_system_path = FALSE, $bool_include_base_path = TRUE) {
  
  $p = menu_get_module_path($module, $bool_include_file_system_path);
    
  if ($bool_include_base_path) {
    $p = $GLOBALS["fp_system_settings"]["base_path"] . "/" . $p;
  }
    
  return $p;
}

/**
 * Eventually, this function will be used to translate strings.  For now, just pass through.
 * Allows variable replacements.  Use like this:
 * t("@name's blob", array("@name" => "Richard"));
 * or simply
 * t("My blob");  if you don't need replacements.
 */
function t($str, $vars = array()) {
    
  if (count($vars) > 0) {
    foreach ($vars as $var => $val) {
      
      // If var begins with %, it means we want to italicize the val.
      if (strstr($var, "%")) {
        $val = "<em>$val</em>";
      }
      
      $str = str_replace($var, $val, $str);
    }
  }
    
  return $str;
}

/**
 * Shortcut for getting the base_path variable from the global system settings.
 */
function base_path() {
  return $GLOBALS["fp_system_settings"]["base_path"];
}


/**
 * Convert a term ID into a description.  Ex: 20095 = Spring of 2009.
 */
function get_term_description($term_id, $bool_abbreviate = false) {
    // Describe the term in plain english, for displays.
    // Ex:  "Fall of 2002."
    $rtn = "";

    if (strstr($term_id, "1111"))
    {
      return "(data unavailable at this time)";
    }

    $year4 = trim(substr($term_id, 0, 4));
    $year2 = trim(substr($term_id, 2, 2));
    $ss = trim(substr($term_id, 4, strlen($term_id) - 4));
    
    $year4p1 = $year4 + 1;
    $year4m1 = $year4 - 1;
    
    // left-pad these with 0's if needed.
    $year2p1 = fp_number_pad($year2 + 1, 2);
    $year2m1 = fp_number_pad($year2 - 1, 2);
        
    // Let's look at the term_idStructure setting and attempt to match
    // what we have been supplied.
    // We expect this structure to look something like:
    // [Y4]60, Spring, Spring of [Y4], Spr '[Y2]
    // [Y4]40, Fall, Fall of [Y4-1], Fall '[Y2-1]
    
    $temp = $GLOBALS["fp_system_settings"]["term_id_structure"];
    $structures = explode("\n", $temp);
    
    foreach ($structures as $structure) {      
      // Perform the necessary replacement patterns on the structure.
      $structure = str_replace("[Y4]", $year4, $structure);
      $structure = str_replace("[Y2]", $year2, $structure);
      $structure = str_replace("[Y4-1]", $year4m1, $structure);
      $structure = str_replace("[Y2-1]", $year2m1, $structure);
      $structure = str_replace("[Y4+1]", $year4p1, $structure);
      $structure = str_replace("[Y2+1]", $year2p1, $structure);
      
      // Now, break up the structure to make it easier to work with.
      $tokens = explode(",", $structure);
      $term_def = trim($tokens[0]);
      $full_description = trim($tokens[2]);
      $abbr_description = trim($tokens[3]);
      
      // Does our term_id match the termDef?
      if ($term_def == $term_id) {
        if ($bool_abbreviate) {
          return $abbr_description;
        }
        else {
          return $full_description;
        }
      }
      
    }

    if (trim($rtn) == "") {
      $rtn = t("Unknown term");
    }

    return $rtn;
  }





/**
 * Redirect the user's browser to the specified internal path + query.
 */
function fp_goto($path, $query = "") {
  global $current_student_id;
    
  if ($current_student_id != "" && !strstr($query, "current_student_id=")) {
    // If the query doesn't contain the current_student_id, then add it in.
    $query .= "&current_student_id=$current_student_id";
  }

  if ($query != "") {
    // Should begin with a ?
    // TODO:
    // But NOT if we don't have clean URLs enabled.  If we don't, it will break, and it should
    // begin with an &.
    $query = "?" . $query;   
  }  
  
  // Close the seesion before we try to redirect.
  session_write_close();
  
  if ($path == "<front>") {
    $path = variable_get("front_page", "main");
  }
  
  
  header('Location: ' . base_path() . '/' . $path . $query);
  exit();
  
}

/**
 * This works like Drupal's l() function for creating links.
 * Ex:  l("Click here for course search!", "tools/course-search", "abc=xyz&hello=goodbye", array("class" => "my-class"));
 * Do not include preceeding or trailing slashes.
 */
function l($text, $path, $query = "", $attributes = array()) {
  $rtn = "";

  if ($path == "<front>") {
    $path = variable_get("front_page", "main");
  }

  if ($query != "") {
    // Should begin with a ?
    // TODO:
    // But NOT if we don't have clean URLs enabled.  If we don't, it will break, and it should
    // begin with an &.
    $query = "?" . $query;   
  } 
  
  
  // Does the query contain possible replacement patterns? (look for %)
  if (strpos($query, "%") !== 0) {
    // Find modules which implement the hook_menu_handle_replacement_pattern function.
    $modules = modules_implement_hook("menu_handle_replacement_pattern");
    foreach($modules as $module) {
      $query = call_user_func($module . '_menu_handle_replacement_pattern', $query);
    }
  }
  
  
  $rtn .= '<a href="' . base_path() . '/' . $path . $query . '" ';
  
  foreach ($attributes as $key => $value) {
    $rtn .= $key . '="' . $value . '" ';
  }
  
  $rtn .= ">$text</a>";



  return $rtn;
}


  /**
   * This function will attempt to determine automatically
   * if we are on a mobile device.  If so, it will set
   * $this->pageIsMobile = TRUE
   *
   */
function fp_screen_is_mobile(){
  
  if (isset($GLOBALS["fp_page_is_mobile"])) {
    return $GLOBALS["fp_page_is_mobile"];
  }
  
  $user_agent = $_SERVER['HTTP_USER_AGENT']; 

  $look_for = array(
    "ipod", 
    "iphone", 
    "android", 
    "opera mini", 
    "blackberry",
    "(pre\/|palm os|palm|hiptop|avantgo|plucker|xiino|blazer|elaine)",
    "(iris|3g_t|windows ce|opera mobi|windows ce; smartphone;|windows ce; iemobile)",
    "(smartphone|iemobile)",
    );
  
  foreach ($look_for as $test_agent) {   
    if (preg_match('/' . $test_agent . '/i',$user_agent)) {
       $is_mobile = TRUE;
      break;
    }
  }  
  
  
  $GLOBALS["fp_page_is_mobile"] = $is_mobile;
  return $is_mobile;
  
} // ends function mobile_device_detect




////////////////////////////////////////////////////////////////////

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

/////////////////////////////////////////////////////////////////////////////////////////////
/////////////////////////////////////////////////////////////////////////////////////////////
/////////////////////////////////////////////////////////////////////////////////////////////



 
/**
 * Return an array of enabled modules which implement the provided hook.
 * Do not include the preceeding "_" on the hook name!
 */
function modules_implement_hook($hook = "example_hook_here") {
  $rtn = array();
  
  foreach ($GLOBALS["fp_system_settings"]["modules"] as $module => $value) {    
    if (isset($value["enabled"]) && $value["enabled"] != "1") {
      // Module is not enabled.  Skip it.
      continue;
    }
    if (function_exists($module . '_' . $hook)) {
      $rtn[] = $module;
    }
  }
  
  return $rtn;  
}



/**
 * This method will return a globally-set DatabaseHandler object,
 * creating it if it does not already exist.  This is for efficiency
 * reasons, so every module or method does not need to keep creating
 * databasehandler objects (and re-connecting to the database).
 *
 */
function get_global_database_handler() {
  
  if (!isset($GLOBALS["fp_global_database_handler"]) || !is_object($GLOBALS["fp_global_database_handler"])) {
    $GLOBALS["fp_global_database_handler"] = new DatabaseHandler();
  }
  
  return $GLOBALS["fp_global_database_handler"];  
  
}


/**
 * Uses fp_add_message, but in this case, it also adds in the filename and line number
 * which the message came from!
 */
function fpm($str) {
  
  // Complex variable?  Change it to print_r.
  if (is_array($str) || is_object($str)) {
    $str = "<pre>" . print_r($str, TRUE) . "</pre>";
  }    

  $arr = debug_backtrace();
  //pretty_print($arr);
  $file = $arr[0]["file"];
  if (strlen($file) > 70) {
    $file = "..." . substr($file, strlen($file) - 70);
  }
  $str .= "<div class='fp-message-backtrace'>line {$arr[0]["line"]}: $file</div>";
    
  fp_add_message("&bull; " . $str);

    
}

/**
 * Will output a debugCT statement only if the user
 * has access to view it. 
 */
function admin_debug($str, $var_name = "")
{
  if ($GLOBALS["fp_system_settings"]["disable_admin_debug"] == true)
  {
    return;
  }
  // Will output a debugCT only if the user is a full_admin.
  $temp = $GLOBALS["fp_system_settings"]["disable_debug_ct"];  // save this....
  $GLOBALS["fp_system_settings"]["disable_debug_ct"] = false;
  
  if (user_has_permission("access_admin_debug"))
  {
    fp_debug_ct($str, $var_name);
  }
  
  $GLOBALS["fp_system_settings"]["disable_debug_ct"] = $temp;  // restore its original state.
  
}


/**
 * This function determines the user type of a logged-in
 * NON student user.  As in, are they admin, advisors, viewers, etc.
 * 
 *
 * @param unknown_type $user_id
 * @return unknown
 */
function determine_staff_user_type($user_id)
{
  
  // Check for hooks...
  if (function_exists("functions_determine_staff_user_type")) {
    return call_user_func("functions_determine_staff_user_type", $user_id);
  }
  
 
  // If GRANT_FULL_ACCESS is turned on, then this person
  // is full_admin.
  if ($GLOBALS["fp_system_settings"]["GRANT_FULL_ACCESS"] == TRUE) {
    return "full_admin";
  }
  
  
  // Attempt to figure out the user's type.
  $db = new DatabaseHandler();

  // Is the user a full admin?  Meaning they basically
  // have the same privileges as a college_coordinator,
  // but can also get into Data Entry.
  $res = $db->db_query("SELECT * FROM administrators
                       WHERE faculty_id = '?' ", $user_id);
  if ($db->db_num_rows($res) == 1)
  {
    return "full_admin";
  }

  //////////////////////////////////////////////////////////////////
  // Does the user have a type specified in the User management system?
  $res = $db->db_query("SELECT * FROM users
                WHERE faculty_id = '?' ", $user_id);
  $cur = $db->db_fetch_array($res);
  $user_type = trim($cur["user_type"]);

  if ($user_type != "")
  {
    return $user_type;
  }

  /////////////////////////////////////////////////////////////////////
  // The user was not specifically found in the users table,
  // so let's try to determine what their user type might be...
  // If your school uses the optional "employee_type" field of the
  // human_resources:faculty_staff table to determine user type, then you
  // must override this function in a hook and change this section.
  
  // Let's pull the needed variables out of our settings, so we know what
  // to query, because this is a non-FlightPath table.
  $tsettings = $GLOBALS["fp_system_settings"]["extra_tables"]["human_resources:faculty_staff"];
  $tf = (object) $tsettings["fields"];  //Convert to object, makes it easier to work with.  
  $table_name = $tsettings["table_name"];    
  
  $res = $db->db_query("SELECT * FROM $table_name
                WHERE $tf->faculty_id = '?' ", $user_id);
  $cur = $db->db_fetch_array($res);
  $emp_type = $cur[$tf->employee_type];
  
  if($emp_type == 10 ||
  $emp_type == 20 ||
  $emp_type == 30 ||
  $emp_type == 40 ||
  $emp_type == 50 ||
  $emp_type == 99)
  {
    $user_type = "advisor";
  }
  else {
    $user_type = "none";
  }

  return $user_type;


}



/**
 * This is used usually when being viewed by a mobile device.
 * It will shorten a catalog year range of 2008-2009 to just
 *  "08-09" or "2008-09"  or even "09-2009".
 *
 * @param unknown_type $cat_range
 */
function get_shorter_catalog_year_range($cat_range, $abbr_first = true, $abbr_second = true) {
  
  $temp = explode("-", $cat_range);
  
  $first = $temp[0];
  $second = $temp[1];
  
  if ($abbr_first) {
    $first = substr($first, 2, 2);
  }
  if ($abbr_second) {
    $second = substr($second, 2, 2);
  }
  
  return "$first-$second";
}




/**
 * This function returns the path to a particular module, if it
 * exists (returns FALSE otherwise).
 * Example:
 * $x = get_module_path("course_search");
 * will return:
 *   custom/modules/course_search
 * 
 * Notice it does NOT have leading or trailing slashes!
 *
 * If you set boolIncludeFileSystemPath = TRUE,
 * you will get back the FILE path of the module. Ex:
 *   /var/www/public_html/flightpath/custom/modules/course_search
 * 
 * 
 * @param unknown_type $module
 */
/*
function get_module_path($module = "", $bool_include_file_system_path = FALSE) {
  
  $system_path = "";
  
  if ($bool_include_file_system_path) {
    $system_path = trim($GLOBALS["fp_system_settings"]["file_system_path"]) . "/";
  }
    
  
  if (isset($GLOBALS["fp_system_settings"]["modules"][$module]["path"])) {
    return $system_path . $GLOBALS["fp_system_settings"]["modules"][$module]["path"];
  }
  else {
    return FALSE;
  }
}
*/

/**
 * This will find and include the module in question, calling
 * it's hook_init() function if it has one.  
 *
 * Will return TRUE or FALSE for success or failure to include
 * the module.
 *
 * Example use:  include_module("course_search");
 * 
 * @param string $module
 */
function include_module($module, $bool_call_init = TRUE) {

  $system_path = trim($GLOBALS["fp_system_settings"]["file_system_path"]);
  
  if (isset($GLOBALS["fp_system_settings"]["modules"][$module]["path"])) {
    $path = $GLOBALS["fp_system_settings"]["modules"][$module]["path"] . "/$module.module";
    if (file_exists($system_path . "/" . $path)) {
      require_once($system_path . "/" . $path);
    }
    else {
      print "<br><b>Could not find module '$module' at '$system_path/$path'</b><br>";
    }
    // Now that we have included it, call the module's hook_init() method.
    if ($bool_call_init) {      
      if (function_exists($module . "_init")) {
        call_user_func($module . "_init");
      }
    }    
    return TRUE;
  }
  
  return FALSE;  
}



/**
 * Will return a path which an HTML form may submit to in order to return
 * back to the module which is calling it.
 * 
 * For example, if the course_search module wants a form to submit something
 * to itself, it looks like:
 * <form action="' . get_module_action_u_r_l("course_search") . '" method="POST">
 * 
 * To create a link to itself, it would look like:
 * 
 * <a href="' . get_module_action_u_r_l("course_search") . "&year=1992&name=peacock">
 *
 * The returned URL will already have a ? starting the query string, so you may
 * begin any additional query with &.
 * 
 * This works fine with forms whose methods are POST, but not GET. You will
 * need to add a hidden variable to those forms like so:
 *    <input type='hidden' name='n' value='course_search'>
 * 
 * @param String $module
 */
function get_module_action_u_r_l($module = "") {
  
  if (isset($GLOBALS["fp_system_settings"]["modules"][$module])) {
    return "m.php?n=$module";
  }
  else {
    return FALSE;
  }  
  
}


/**
 * Conveiencence function to convert a simple XML string
 * into an associative array.
 *
 * @param unknown_type $xml_data
 * @return unknown
 */
/*
function fp_xml_to_array2($xml_data)
{
  $xml_data = trim(utf8_encode($xml_data));
  if ($xml_data == ""){return false;}

  $na = array();
  
  try{
    @$xml_object = new SimpleXmlElement($xml_data);
    foreach($xml_object->children() as $element => $value)
    {
      $val = (string) $value;
      $na["$element"] =  $val;
    }
  
  } catch(Exception $exception) {
     // Do nothing if this fails.  Just let us return an empty array. 
     // TODO:  a call to fp_add_message or some such would be good here!
     admin_debug("<b>WARNING:</b> Unable to parse XML: $xml_data");
  }

  
  return $na;

}
*/



/**
 * Creates a javascript "confirm" link, so when clicked it asks the user a question, then proceeds
 * if they select OK.  The main reason I want to do this is so I can pass the $question through
 * my t() function. (do it when you call this function)
 */
function fp_get_js_confirm_link($question, $action_if_yes, $link_text) {
  
  $rtn = "";  

  $question = htmlentities($question, ENT_QUOTES);

  $rtn .= "<a href='javascript: if(confirm(\"$question\")) { $action_if_yes;  }'>$link_text</a>";

      
  return $rtn;
}

/**
 * Creates a javascript "confirm" link, so when clicked it asks the user a question, then proceeds
 * if they select OK.  The main reason I want to do this is so I can pass the $question through
 * my t() function. (do it when you call this function)
 */
function fp_get_js_prompt_link($question, $default, $action_if_yes, $link_text) {
  
  $rtn = "";  

  $question = htmlentities($question, ENT_QUOTES);

  $rtn .= "<a href='javascript: var response = prompt(\"$question\", \"$default\");
                                if (response != null) 
                                {
                                   $action_if_yes ;  
                                }
                                '>$link_text</a>";

      
  return $rtn;
}



/**
 * Does the user have the specified role?
 */
function user_has_role($roll, $faculty_if = "") {
  global $user;
  
  // Admin always = TRUE
  if (in_array("administrator", $user->rolls)) return TRUE;
  
  // Check for other users...
  if (in_array($roll, $user->rolls)) return TRUE;
  
  return FALSE;
  
}


/**
 * Returns TRUE or FALSE if the logged in user has access based on the
 * permission supplied.
 *
 * @param String $permission
 */
function user_has_permission($permission, $faculty_id = "") {
  global $user;  
  // If the user has the role "administrator", then they always have
  // permission.
  if (in_array("administrator", $user->rolls)) return TRUE;
  
  /*
  
  // We will check the database table each time, so that the user doesn't
  // have to log out and back in before their permissions change.
  
  $db = get_global_database_handler();
  
  if ($faculty_id == "") {
    $faculty_id = $_SESSION["fp_user_id"];
  }

  // If the user is admin, always return TRUE.  Check the user type
  $user_type = determine_staff_user_type($faculty_id);
  if ($user_type == "full_admin") {
    return TRUE;
  }  
    
  // Otherwise, check their permissions from the table.
  
  $res = $db->db_query("SELECT * FROM users WHERE faculty_id = '?' ", $faculty_id);
  $cur = $db->db_fetch_array($res);
  
  $p_array = array();
  
  $temp = explode("," , $cur["permissions"]);
  foreach ($temp as $t) {
    $p_array[] = trim($t);
  }
  
  if (in_array($permission, $p_array)) {
    return TRUE;
  }
  */
  return FALSE;
  
}


/**
 * This looks at the global termIDStructure setting and returns back
 * an array of only term suffixes (like 40, 60, mm, etc).
 *
 */
function get_term_id_suffixes() {
  
  $rtn = array();  
  
  $temp = $GLOBALS["fp_system_settings"]["term_id_structure"];
  $structures = explode("\n", $temp);
    
  foreach ($structures as $structure) {      
    $tokens = explode(",", $structure);
    $term_def = trim($tokens[0]);
    
    // Get rid of the replacement pattern.
    // Looks like:  [Y4]40.  We want the 40.
    // Simply explode on "]"
    $temp = explode("]", $term_def);
    $rtn[] = trim($temp[1]);    
  
  }

  return $rtn;
  
}


/**
 * This function will read through all the modules' permissions and
 * return back an array.  Specifically, it retrieves arrays from each
 * modules' hook_perm() function.
 *
 */
function get_modules_permissions() {
  $rtn = array();
  
  
  foreach ($GLOBALS["fp_system_settings"]["modules"] as $module => $value) {
    
    if (isset($value["disabled"]) && $value["disabled"] == "yes") {
      // Module is not enabled.  Skip it.
      continue;
    }
    
    
    if (function_exists($module . "_perm")) {
      $rtn[$module][] = call_user_func($module . "_perm");
    }
  }
    
  return $rtn;
}



// Commenting-out since we can now use MYSQL to order by weight.
/**
 * This will look through the modules array (which we assume
 * is from $GLOBALS[fpSystemSettings][modules]
 * and reorder based on weight.
 * 
 * It takes the $modules array by reference, so nothing
 * is returned.
 * 
 * Use:  reorder_modules_by_weight($GLOBALS["fp_system_settings"]["modules"]);
 *
 * @param unknown_type $modules
 */
/*
function reorder_modules_by_weight(&$modules) {
  
  $temp = array();
  foreach ($modules as $module => $value) {
    $w = $value["weight"];
    if ($w == "") $w = "0";

    // We need to front-pad $w with zeros, so it is the same length
    // for every entry.  Otherwise it will not sort correctly.
    $w = fp_number_pad($w, 10);      
    
    $temp[] = "$w~~$module";       
       
  }
      
  // Now, sort $temp...
  sort($temp);  
    
  $new_array = array();
  foreach ($temp as $t) {
    $vals = explode("~~", $t);
    $module = $vals[1];
    $new_array[$module] = $modules[$module];
  }
  
  // Reassign the $modules array and we're done!
  $modules = $new_array;
  
}
*/


/**
 * Similar to get_modules_permissions, this will scan through all installed
 * modules' hook_menu() functions, and assemble an array which is sorted
 * by "location" and then by "weight".
 *
 */
function get_modules_menus() {
  
  $menus = array();
  foreach ($GLOBALS["fp_system_settings"]["modules"] as $module => $value) {    
    if (isset($value["disabled"]) && $value["disabled"] == "yes") {
      // Module is not enabled.  Skip it.
      continue;
    }    
    if (function_exists($module . "_menu")) {
      $menus[] = call_user_func($module . "_menu");      
    }
  }
  
  // Let's re-order based on weight...
  // Convert to a single dimensional array for easier sorting.
  $temp = array();
  foreach ($menus as $c => $value) {
    foreach ($menus[$c] as $d => $menu_data) {
      $w = $menu_data["weight"];
      if ($w == "") $w = "0";
    
      // We need to front-pad $w with zeros, so it is the same length
      // for every entry.  Otherwise it will not sort correctly.
      $w = fp_number_pad($w, 10);      
      
      $temp[] = "$w~~$c~~$d";    
    }
  }
  
  //var_dump($temp);
  // Now, sort $temp...
  sort($temp);
  //var_dump($temp);
  // Now, go back through $temp and get our new array...
  $new_array = array();
  
  foreach ($temp as $t) {
    $vals = explode("~~", $t);
    $c = $vals[1];
    $d = $vals[2];
    
    // Place them into subarrays indexed by location
    $new_array[$menus[$c][$d]["location"]][] = $menus[$c][$d];    
  }
  
  return $new_array;
  
}


/**
 * Simple function to left padd numbers with 0's.
 * 1 becomes 001
 * 20 becomes 020 
 * and so on.
 *
 * @param int $number
 * @param int $n
 * @return String
 */
function fp_number_pad($number, $len) {
  return str_pad((int) $number, $len, "0", STR_PAD_LEFT);
}

/**
 * arrayToXml will convert a single-deminsion associative array
 * into an XML document.  For example, if you send it the $_POST
 * array (after a submission) you will get back an XML document
 * which you can then easily insert into a database table.
 *
 * $root is the root element of the XML document.  You can make this
 * up yourself.  It won't affect anything, as far as I can tell, but
 * it is required.  If you don't know what to put, make it "xml_doc" or
 * something similar.
 * $xml_array is the array you want converted into XML.
 * $html_safe is a boolean.  If set to true, it will convert ' and "
 * characters into their HTML equivalent.  I recommend always setting this
 * to true.
 *
 * @param String $root
 * @param Array $xml_array
 * @param boolean $html_safe
 * @return String
 */
/*
function fp_array_to_xml ($root, $xml_array, $html_safe = false)
{
  $memory = xmlwriter_open_memory ();       // Allocate memory for XML writer

  // if $makeHTMLSafe is true, then " ' and < will be converted to their
  // HTML characters.  Recommended for web uses.

  //xmlwriter_set_indent      ($memory, true);// Indent the XML document

  // Start the XML document and create the DTD tag.
  xmlwriter_start_document  ($memory, '1.0', 'UTF-8');
  // xmlwriter_start_dtd       ($memory, 'html',
  //    '-//WAPFORUM//DTD XHTML Mobile 1.0//EN',
  //    'http://www.wapforum.org/DTD/xhtml-mobile10.dtd');
  // xmlwriter_end_dtd         ($memory);

  // Start the XML document root tag.
  xmlwriter_start_element   ($memory, $root);

  // Define attributes for the document namespace and language.
  // xmlwriter_write_attribute ($memory, 'xmlns',
  //    'http://www.wapforum.org/DTD/xhtml-mobile10.dtd');
  // xmlwriter_write_attribute ($memory, 'xm:lang', 'en');

  // Write each array element as the next XML tag.
  foreach ($xml_array as $tag => $text)
  {
    $text = strip_non_u_t_f8("$text", $html_safe);  // strip out non-utf8 chars.
    xmlwriter_write_element ($memory, $tag, $text);

  }

  // Generate the ending tag for the document root.
  xmlwriter_end_element      ($memory);

  // End the DTD for this XML document.
  // xmlwriter_end_dtd          ($memory);

  // Output the data in "$memory" to a String variable "$xml".
  $xml = xmlwriter_output_memory ($memory, true);

  return $xml;
} // End function arrayToXml()
*/


/**
 * This function will strip a string ($str) of any non-utf8
 * characters.  This is necessary for the XML functions
 * also present in this file.
 *  
 * if $html_safe is set to TRUE, then it will replace " and ' with
 * their HTML codes (&quot; and &#39;), ensuring that they can
 * pass through a mysql query or be set inside a value='' field
 * without causing problems.
 *
 * @param String $str
 * @param boolean $html_safe
 * @return String
 */
function strip_non_u_t_f8($str, $html_safe = false){
  $good[] = 9;  #tab
  $good[] = 10; #nl
  $good[] = 13; #cr
  for($a=32;$a<127;$a++){
    $good[] = $a;
  }
  $len = strlen($str);
  for($b=0;$b < $len+1; $b++){
    if(in_array(ord($str[$b]), $good)){
      $newstr .= $str[$b];
    }//fi
  }//rof

  if ($html_safe == true)
  {
    $newstr = str_replace("'","&#39;",$newstr);
    $newstr = str_replace('"','&quot;',$newstr);
    $newstr = str_replace("<","&lt;",$newstr);

  }

  return $newstr;
}




function fp_debug_ct($debug_val = "", $var = "")
{ // Shortcut to the other function.
  if ($GLOBALS["fp_system_settings"]["disable_debug_ct"] == true)
  {
    return;
  }

  fp_debug_current_time_millis($debug_val, false, $var);
}


function fp_print_pre($str)
{
  print "<pre>" . $str . "</pre>";
}


function fp_debug_current_time_millis($debug_val = "", $show_current_time = true, $var = "")
{
  // Display the current time in milliseconds, and, if available,
  // show how many milliseconds its been since the last time
  // this function was called.  This helps programmers tell how
  // long a particular function takes to run.  Just place a call
  // to this function before and after the function call.
  
  $debug_string = $debug_val;
  if (is_array($debug_val) || is_object($debug_val)) {
    $debug_string = "<pre>" . print_r($debug_val, true) . "</pre>";
  }

  $last_time = $GLOBALS["current_time_millis" . $var] * 1;

  $cur_time = microtime(true) * 1000;

  //if ($debug_string != "")
  //{
  $debug_string = "<span style='color:red;'>DEBUG:</span>
            <span style='color:green;'>$debug_string</span>";
  //}

  print "<div style='background-color: white;'>$debug_string
      ";
  if ($show_current_time == true)
  {
    print "<span style='color: red;'>TIME:</span>
        <span style='color: green;'>$cur_time" . "ms</span>";
  }

  if ($last_time > 1)
  {
    $diff = round($cur_time - $last_time,2);
    print "<span style='color: blue;'> ($diff" . "ms since last check)</span>";
  } else {
    // Start of clock...
    print "<span style='color: blue;'> --- </span>";
  }

  print "</div>";
  $GLOBALS["current_time_millis" . $var] = $cur_time;
  $GLOBALS["current_time_millis"] = $cur_time;

}






/**
 * This function is intended for you to overwrite using the hooks system.
 * That's because every school might handle logins differently-- they might
 * use a MySQL table of SHA1'd values, they might use LDAP, etc.
 * 
 * !!! IMPORTANT !!!
 * This function should return boolean FALSE if login failed, and the user's numeric userID
 * if they succeeded!  Ex: "10035744" or FALSE
 * 
 * This function is passed exactly what the user typed into the login boxes, so be
 * sure to sanitize the input before use.
 *
 * @param string $username
 * @param string $password
 * @return mixed
 */
function fp_verify_all_faculty_logins($username, $password) {
  
  // Check for hooks...
  if (function_exists("functions_fp_verify_all_faculty_logins")) {
    return call_user_func("functions_fp_verify_all_faculty_logins", $username, $password);
  }  
    
  // Authenticate by the user_auth table by default.
  $db = new DatabaseHandler();
  $res = $db->db_query("SELECT * FROM user_auth
                        WHERE user_name = '?'
                        AND password = '?' 
                        AND is_faculty = '1' ", $username, md5($password));
  $cur = $db->db_fetch_array($res);
  if ($cur["user_name"] == $username) {
    return $cur["user_id"];
  }
  
  // By default, return FALSE;
  return FALSE;
  
}


/**
 * This function is intended for you to overwrite using the hooks system.
 * That's because every school might handle logins differently-- they might
 * use a MySQL table of SHA1'd values, they might use LDAP, etc.
 * 
 * !!! IMPORTANT !!!
 * This function should return boolean FALSE if login failed, and the user's numeric ID
 * if they succeeded!  Ex: "10035744" or FALSE
 * 
 * This function is passed exactly what the user typed into the login boxes, so be
 * sure to sanitize the input before use.
 *
 * @param string $username
 * @param string $password
 * @return mixed
 */
function fp_verify_all_student_logins($username, $password) {
  
  // Check for hooks...
  if (function_exists("functions_fp_verify_all_student_logins")) {
    return call_user_func("functions_fp_verify_all_student_logins", $username, $password);
  }  
 
  
  // Authenticate by the user_auth table by default.
  $db = new DatabaseHandler();
  $res = $db->db_query("SELECT * FROM user_auth
                        WHERE user_name = '?'
                        AND password = '?' 
                        AND is_student = '1' ", $username, md5($password));
  $cur = $db->db_fetch_array($res);
  if ($cur["user_name"] == $username) {
    return $cur["user_id"];
  }
  
  // By default, return FALSE;
  return FALSE;
    
}

