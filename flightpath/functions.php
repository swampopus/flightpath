<?php
/*
FlightPath was originally designed and programmed by the 
University of Louisiana at Monroe. The original source is 
copyright (C) 2011-present by the University of Louisiana at Monroe.

FlightPath is considered "open source" under the 
GNU General Public License, version 3 or any later version. 
This covers any related files and documentation packaged with 
FlightPath. 

The license is defined in full here: http://www.gnu.org/licenses/gpl.html,
and reproduced in the LICENSE.txt file.

You may modify FlightPath's source code, but this copyright and license
notice must not be modified, and must be included with the source code.
------------------------------
*/



// This file contains common functions used in FlightPath.


/**
 * Go through all installed modules and rebuild the menu_router table,
 * based on each module's hook_menu function.
 */
function menu_rebuild_cache() {
  
  foreach ($GLOBALS["fpSystemSettings"]["modules"] as $module => $value) {    
    if (isset($value["disabled"]) && $value["disabled"] == "yes") {
      // Module is not enabled.  Skip it.
      continue;
    }    
    if (function_exists($module . "_menu")) {
      $items = call_user_func($module . "_menu");
      
      // Okay, now go through the $items array, and write the needed information
      // to the menu_router array.
      foreach ($items as $path => $item) {
        if (is_numeric($path)) continue; // problem, so skip.

        // Update our menu_router table.
        // Begin by deleting the existing path, if there is one.
        db_query("DELETE FROM menu_router WHERE path = '?' ", $path);
        // Now, insert the new one.
        db_query("INSERT INTO menu_router
                    (path, access_callback, access_arguments, page_callback, page_arguments, title, description, type, weight, icon, page_settings)
                    VALUES
                    ('?', '?', '?', '?', '?', '?', '?', '?', '?', '?', '?')
                    ", $path, $item["access_callback"], serialize($item["access_arguments"]), $item["page_callback"],
                       serialize($item['page_arguments']), $item['title'], $item['description'], $item['type'], $item['weight'], $item['icon'],
                       serialize($item['page_settings']));
      }              
    }
  }
  
  fp_add_message(t("The menu router has been rebuilt"));
    
}



function menu_execute_page_request() {
  $path = $_GET["q"];
  //If the path is blank, figure out what the "font page" is, and use that path.
  if ($path == "") {
    $path = variable_get("front_page", "main");
  }
  
  if ($router_item = menu_get_item($path)) {
   
    // Let's figure out if the user has access to this menu item or not.
    $access = FALSE;
    if ($router_item["access_callback"] == 1) {
      $access = TRUE;
    }
     
    if ($access) {
      if ($router_item['file']) {
        require_once($router_item['file']);
      }
      $page = array();
      $page["content"] = call_user_func_array($router_item['page_callback'], $router_item['page_arguments']);      
      $page["path"] = $path;
      // TODO:  Check to see if the user has overridden the title using the fp_set_title() command.
      $page["title"] = $router_item["title"];
      $page["router_item"] = $router_item;

      return $page;    
           
    }
    else {
      return MENU_ACCESS_DENIED;
    }
  }
  return MENU_NOT_FOUND;  
}


/**
 * Return array from menu_router for this item.
 */
function menu_get_item($path) {
  $res = db_query("SELECT * FROM menu_router WHERE path = '?' ", $path);
  $cur = db_fetch_array($res);
  
  // Unserialize the things which are supposed to be unserialized.
  $cur["page_arguments"] = unserialize($cur["page_arguments"]);
  if (!is_array($cur["page_arguments"])) {
    $cur["page_arguments"] = array();
  }

  $cur["access_arguments"] = unserialize($cur["access_arguments"]);
  if (!is_array($cur["access_arguments"])) {
    $cur["access_arguments"] = array();
  }

  $cur["page_settings"] = unserialize($cur["page_settings"]);
  if (!is_array($cur["page_settings"])) {
    $cur["page_settings"] = array();
  }


  return $cur;    
    
}



/////////////////////////////////////////////////////////////////////


function fp_add_message($str) {
  $_SESSION["fpMessages"][] = $str;
}

function fp_add_css($path_to_css) {
  $GLOBALS["fpExtraCss"][] = $path_to_css;
}

function fp_get_module_path($module, $bool_include_file_system_path = FALSE, $bool_include_base_path = TRUE) {
  
  $p = getModulePath($module, $bool_include_file_system_path);
    
  if ($bool_include_base_path) {
    $p = $GLOBALS["fpSystemSettings"]["basePath"] . "/" . $p;
  }
    
  return $p;
}

/**
 * Eventually, this function will be used to translate strings.  For now, just pass through.
 */
function t($str) {
  return $str;
}

/**
 * This works like Drupal's l() function for creating links.
 * Ex:  l("Click here for course search!", "tools/course-search", "abc=xyz&hello=goodbye", array("class" => "my-class"));
 * Do not include preceeding or trailing slashes.
 */
function l($text, $path, $query = "", $attributes = array()) {
  $rtn = "";

  if ($query != "") {
    // Should begin with a ?
    // TOOD:
    // But NOT if we don't have clean URLs enabled.  If we don't, it will break, and it should
    // begin with an &.
    $query = "?" . $query;   
  }  
  
  $rtn .= '<a href="' . $GLOBALS["fpSystemSettings"]["baseURL"] . '/' . $path . $query . '" ';
  
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
  
  if (isset($GLOBALS["fpPageIsMobile"])) {
    return $GLOBALS["fpPageIsMobile"];
  }
  
  $userAgent = $_SERVER['HTTP_USER_AGENT']; 

  $lookFor = array(
    "ipod", 
    "iphone", 
    "android", 
    "opera mini", 
    "blackberry",
    "(pre\/|palm os|palm|hiptop|avantgo|plucker|xiino|blazer|elaine)",
    "(iris|3g_t|windows ce|opera mobi|windows ce; smartphone;|windows ce; iemobile)",
    "(smartphone|iemobile)",
    );
  
  foreach ($lookFor as $testAgent) {   
    if (preg_match('/' . $testAgent . '/i',$userAgent)) {
       $is_mobile = TRUE;
      break;
    }
  }  
  
  
  $GLOBALS["fpPageIsMobile"] = $is_mobile;
  return $is_mobile;
  
} // ends function mobile_device_detect



/**
 * Output the contents of the $page variable to the screen.
 */
function fp_display_page($page) {
  
  $screen = new AdvisingScreen("",null,"notAdvising");
  $screen->pageTitle = $page["title"];
  $screen->pageHasSearch = $page["router_item"]["page_settings"]["page_has_search"];
  $screen->pageBannerIsLink = $page["router_item"]["page_settings"]["page_banner_is_link"];
  $screen->pageHideReportError = $page["router_item"]["page_settings"]["page_hide_report_error"];
  
  // If there are "messages" waiting, then let's add them to the top of content.
  $content_top = "";
  if (count($_SESSION["fpMessages"]) > 0) {
    $content_top .= "<div class='fp-messages'>";
    foreach ($_SESSION["fpMessages"] as $key => $msg) {
      $content_top .= "<div class='fp-message'>$msg</div>";      
    }
    $content_top .= "</div>";    
    unset($_SESSION["fpMessages"]);
  }
  
  $screen->pageContent = $content_top .= $page["content"];
  // If there are CSS files to add, add those.
  foreach ($GLOBALS["fpExtraCss"] as $filename) {
    //pretty_print ("adding $filename");
    $screen->addCss($filename);
  }
  
  
  $screen->outputToBrowser();  
}


/**
 * Return the theme location
 */
function fp_theme_location() {
  return $GLOBALS["fpSystemSettings"]["basePath"] . "/" . $GLOBALS["fpSystemSettings"]["theme"];
}


/**
 * Will draw a string in a pretty curved box.  Used for displaying semester
 * titles.
 *
 * @param string $title
 * @return string
 */
function fp_render_curved_line($text) {
  // Will simply draw a curved title bar containing the $title
  // as the text.
  $img_path = fp_theme_location();
  

  $rtn = "
   <table border='0' class='blueTitle' width='100%' cellpadding='0' cellspacing='0'>
     <tr>
      <td width='10%' align='left' valign='top'><img src='$img_path/images/corner_tl.gif'></td>
      <td width='80%' align='center' rowspan='2'>
       <span class='tenpt'><b>$text</b></span>
      </td>
      <td width='10%' align='right' valign='top'><img src='$img_path/images/corner_tr.gif'></td>
     </tr>
     <tr>
      <td align='left' valign='bottom'><img src='$img_path/images/corner_bl.gif'></td>
      <td align='right' valign='bottom'><img src='$img_path/images/corner_br.gif'></td>
     </tr> 
    </table>
";

  return $rtn;

}



////////////////////////////////////////////////////////////////////

function db_query($query) {
  // Capture arguments to this function, to pass along to our $db object.
  $args = func_get_args();
  array_shift($args);  
  
  $db = getGlobalDatabaseHandler();  
  $res = $db->dbQuery($query, $args);

  return $res;    
}

function db_fetch_array($result_handler) {
  $db = getGlobalDatabaseHandler();
  return $db->dbFetchArray($result_handler);
}

function variable_get($name, $default_value = "") {
  $db = getGlobalDatabaseHandler();
  return $db->getVariable($name, $default_value);
}

function variable_set($name, $value) {
  $db = getGlobalDatabaseHandler();
  $db->setVariable($name, $value);  
}

function fp_get_system_settings() {
  $db = getGlobalDatabaseHandler();
  return $db->getFlightPathSettings();  
}


function pretty_print($var) {
  print "<pre>" . print_r($var, true) . "</pre>";
}


/////////////////////////////////////////////////////////////////////////////////////////////
/////////////////////////////////////////////////////////////////////////////////////////////
/////////////////////////////////////////////////////////////////////////////////////////////



/**
 * This method will return a globally-set DatabaseHandler object,
 * creating it if it does not already exist.  This is for efficiency
 * reasons, so every module or method does not need to keep creating
 * databasehandler objects (and re-connecting to the database).
 *
 */
function getGlobalDatabaseHandler() {
  
  if (!isset($GLOBALS["fpGlobalDatabaseHandler"]) || !is_object($GLOBALS["fpGlobalDatabaseHandler"])) {
    $GLOBALS["fpGlobalDatabaseHandler"] = new DatabaseHandler();
  }
  
  return $GLOBALS["fpGlobalDatabaseHandler"];  
  
}


/**
 * Will output a debugCT statement only if the user
 * is a full_admin.  $_SESSION["fpUserType"] must == full_admin.
 *
 * 
 */
function adminDebug($str, $varName = "")
{
	if ($GLOBALS["fpSystemSettings"]["disableAdminDebug"] == true)
	{
		return;
	}
	// Will output a debugCT only if the user is a full_admin.
	$temp = $GLOBALS["fpSystemSettings"]["disableDebugCT"];  // save this....
	$GLOBALS["fpSystemSettings"]["disableDebugCT"] = false;
	
	if ($_SESSION["fpUserType"] == "full_admin")
	{
		fp_debugCT($str, $varName);
	}
	
	$GLOBALS["fpSystemSettings"]["disableDebugCT"] = $temp;  // restore its original state.
	
}


function displayAccessDenied($systemName = "", $boolDieAtEnd = TRUE) {
  
  // Check for hooks...
  if (function_exists("functions_displayAccessDenied")) {
    return call_user_func("functions_displayAccessDenied", $systemName, $boolDieAtEnd);
  }  

  $pC = "";
  
  $pC .= "<h2>Access Denied";
  if ($systemName) {
    $pC .= " for $systemName";
  }
  $pC .= "</h2>";
  
  $pC .= "Sorry, but you do not have sufficent permissions in FlightPath
          to access this page.";
  
  
  $screen = new AdvisingScreen("", null, "notAdvising");
  $screen->pageContent = $pC;
  $screen->outputToBrowser();
  
  if ($boolDieAtEnd) {
    die;
  }
}


/**
 * This function determines the user type of a logged-in
 * NON student user.  As in, are they admin, advisors, viewers, etc.
 * 
 *
 * @param unknown_type $userID
 * @return unknown
 */
function determineStaffUserType($userID)
{
  
  // Check for hooks...
  if (function_exists("functions_determineStaffUserType")) {
    return call_user_func("functions_determineStaffUserType", $userID);
  }
  
 
  // If GRANT_FULL_ACCESS is turned on, then this person
  // is full_admin.
  if ($GLOBALS["fpSystemSettings"]["GRANT_FULL_ACCESS"] == TRUE) {
    return "full_admin";
  }
  
  
  // Attempt to figure out the user's type.
  $db = new DatabaseHandler();

  // Is the user a full admin?  Meaning they basically
  // have the same privileges as a college_coordinator,
  // but can also get into Data Entry.
  $res = $db->dbQuery("SELECT * FROM administrators
								       WHERE faculty_id = '?' ", $userID);
  if ($db->dbNumRows($res) == 1)
  {
    return "full_admin";
  }

  //////////////////////////////////////////////////////////////////
  // Does the user have a type specified in the User management system?
  $res = $db->dbQuery("SELECT * FROM users
								WHERE faculty_id = '?' ", $userID);
  $cur = $db->dbFetchArray($res);
  $userType = trim($cur["user_type"]);

  if ($userType != "")
  {
    return $userType;
  }

  /////////////////////////////////////////////////////////////////////
  // The user was not specifically found in the users table,
  // so let's try to determine what their user type might be...
  // If your school uses the optional "employeeType" field of the
  // human_resources:faculty_staff table to determine user type, then you
  // must override this function in a hook and change this section.
  
  // Let's pull the needed variables out of our settings, so we know what
	// to query, because this is a non-FlightPath table.
	$tsettings = $GLOBALS["fpSystemSettings"]["extraTables"]["human_resources:faculty_staff"];
	$tf = (object) $tsettings["fields"];  //Convert to object, makes it easier to work with.  
	$tableName = $tsettings["tableName"];    
  
  $res = $db->dbQuery("SELECT * FROM $tableName
								WHERE $tf->facultyID = '?' ", $userID);
  $cur = $db->dbFetchArray($res);
  $empType = $cur[$tf->employeeType];
  
  if($empType == 10 ||
  $empType == 20 ||
  $empType == 30 ||
  $empType == 40 ||
  $empType == 50 ||
  $empType == 99)
  {
    $userType = "advisor";
  }
  else {
    $userType = "none";
  }

  return $userType;


}



/**
 * This is used usually when being viewed by a mobile device.
 * It will shorten a catalog year range of 2008-2009 to just
 *  "08-09" or "2008-09"  or even "09-2009".
 *
 * @param unknown_type $catRange
 */
function getShorterCatalogYearRange($catRange, $abbrFirst = true, $abbrSecond = true) {
  
  $temp = explode("-", $catRange);
  
  $first = $temp[0];
  $second = $temp[1];
  
  if ($abbrFirst) {
    $first = substr($first, 2, 2);
  }
  if ($abbrSecond) {
    $second = substr($second, 2, 2);
  }
  
  return "$first-$second";
}




/**
 * This function returns the path to a particular module, if it
 * exists (returns FALSE otherwise).
 * Example:
 * $x = getModulePath("course_search");
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
function getModulePath($module = "", $boolIncludeFileSystemPath = FALSE) {
  
  $systemPath = "";
  
  if ($boolIncludeFileSystemPath) {
    $systemPath = trim($GLOBALS["fpSystemSettings"]["fileSystemPath"]) . "/";
  }
    
  
  if (isset($GLOBALS["fpSystemSettings"]["modules"][$module]["path"])) {
    return $systemPath . $GLOBALS["fpSystemSettings"]["modules"][$module]["path"];
  }
  else {
    return FALSE;
  }
}

/**
 * This will find and include the module in question, calling
 * it's hook_init() function if it has one.  
 *
 * Will return TRUE or FALSE for success or failure to include
 * the module.
 *
 * Example use:  includeModule("course_search");
 * 
 * @param string $module
 */
function includeModule($module, $bool_call_init = TRUE) {

  $systemPath = trim($GLOBALS["fpSystemSettings"]["fileSystemPath"]);
  
  if (isset($GLOBALS["fpSystemSettings"]["modules"][$module]["path"])) {
    $path = $GLOBALS["fpSystemSettings"]["modules"][$module]["path"] . "/$module.module";
    if (file_exists($systemPath . "/" . $path)) {
      require_once($systemPath . "/" . $path);
    }
    else {
      print "<br><b>Could not find module '$module' at '$systemPath/$path'</b><br>";
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
 * <form action="' . getModuleActionURL("course_search") . '" method="POST">
 * 
 * To create a link to itself, it would look like:
 * 
 * <a href="' . getModuleActionURL("course_search") . "&year=1992&name=peacock">
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
function getModuleActionURL($module = "") {
  
  if (isset($GLOBALS["fpSystemSettings"]["modules"][$module])) {
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
function fp_xmlToArray2($xml_data)
{
	$xml_data = trim(utf8_encode($xml_data));
	if ($xml_data == ""){return false;}

	$na = array();
	
	try{
    @$xmlObject = new SimpleXmlElement($xml_data);
  	foreach($xmlObject->children() as $element => $value)
  	{
  		$val = (String) $value;
  		$na["$element"] =  $val;
  	}
	
	} catch(Exception $exception) {
	   // Do nothing if this fails.  Just let us return an empty array. 
	   // TODO:  a call to fp_add_message or some such would be good here!
	   adminDebug("<b>WARNING:</b> Unable to parse XML: $xml_data");
	}

	
	return $na;

}


/**
 * Returns TRUE or FALSE if the logged in user has access based on the
 * permission supplied.
 *
 * @param String $permission
 */
function userHasPermission($permission, $facultyID = "") {
  
  // We will check the database table each time, so that the user doesn't
  // have to log out and back in before their permissions change.
  
  $db = getGlobalDatabaseHandler();
  
  if ($facultyID == "") {
    $facultyID = $_SESSION["fpUserID"];
  }

  // If the user is admin, always return TRUE.  Check the user type
  $userType = determineStaffUserType($facultyID);
  if ($userType == "full_admin") {
    return TRUE;
  }  
    
  // Otherwise, check their permissions from the table.
  
  $res = $db->dbQuery("SELECT * FROM users WHERE faculty_id = '?' ", $facultyID);
  $cur = $db->dbFetchArray($res);
  
  $pArray = array();
  
  $temp = explode("," , $cur["permissions"]);
  foreach ($temp as $t) {
    $pArray[] = trim($t);
  }
  
  if (in_array($permission, $pArray)) {
    return TRUE;
  }
  
  return FALSE;
  
}


/**
 * This looks at the global termIDStructure setting and returns back
 * an array of only term suffixes (like 40, 60, mm, etc).
 *
 */
function getTermIDSuffixes() {
  
  $rtn = array();  
  
  $temp = $GLOBALS["fpSystemSettings"]["termIDStructure"];
  $structures = explode("\n", $temp);
    
  foreach ($structures as $structure) {      
    $tokens = explode(",", $structure);
    $termDef = trim($tokens[0]);
    
    // Get rid of the replacement pattern.
    // Looks like:  [Y4]40.  We want the 40.
    // Simply explode on "]"
    $temp = explode("]", $termDef);
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
function getModulesPermissions() {
  $rtn = array();
  
  
  foreach ($GLOBALS["fpSystemSettings"]["modules"] as $module => $value) {
    
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

/**
 * This will look through the modules array (which we assume
 * is from $GLOBALS[fpSystemSettings][modules]
 * and reorder based on weight.
 * 
 * It takes the $modules array by reference, so nothing
 * is returned.
 * 
 * Use:  reorderModulesByWeight($GLOBALS["fpSystemSettings"]["modules"]);
 *
 * @param unknown_type $modules
 */
function reorderModulesByWeight(&$modules) {
  
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
    
  $newArray = array();
  foreach ($temp as $t) {
    $vals = explode("~~", $t);
    $module = $vals[1];
    $newArray[$module] = $modules[$module];
  }
  
  // Reassign the $modules array and we're done!
  $modules = $newArray;
  
}


/**
 * Similar to getModulesPermissions, this will scan through all installed
 * modules' hook_menu() functions, and assemble an array which is sorted
 * by "location" and then by "weight".
 *
 */
function getModulesMenus() {
  
  $menus = array();
  foreach ($GLOBALS["fpSystemSettings"]["modules"] as $module => $value) {    
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
    foreach ($menus[$c] as $d => $menuData) {
      $w = $menuData["weight"];
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
  $newArray = array();
  
  foreach ($temp as $t) {
    $vals = explode("~~", $t);
    $c = $vals[1];
    $d = $vals[2];
    
    // Place them into subarrays indexed by location
    $newArray[$menus[$c][$d]["location"]][] = $menus[$c][$d];    
  }
  
  return $newArray;
  
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
 * $htmlSafe is a boolean.  If set to true, it will convert ' and "
 * characters into their HTML equivalent.  I recommend always setting this
 * to true.
 *
 * @param String $root
 * @param Array $xml_array
 * @param boolean $htmlSafe
 * @return String
 */
function fp_arrayToXml ($root, $xml_array, $htmlSafe = false)
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
		$text = stripNonUTF8("$text", $htmlSafe);  // strip out non-utf8 chars.
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



/**
 * This function will strip a string ($str) of any non-utf8
 * characters.  This is necessary for the XML functions
 * also present in this file.
 *  
 * if $htmlSafe is set to TRUE, then it will replace " and ' with
 * their HTML codes (&quot; and &#39;), ensuring that they can
 * pass through a mysql query or be set inside a value='' field
 * without causing problems.
 *
 * @param String $str
 * @param boolean $htmlSafe
 * @return String
 */
function stripNonUTF8($str, $htmlSafe = false){
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

	if ($htmlSafe == true)
	{
		$newstr = str_replace("'","&#39;",$newstr);
		$newstr = str_replace('"','&quot;',$newstr);
		$newstr = str_replace("<","&lt;",$newstr);

	}

	return $newstr;
}




function fp_debugCT($debugString = "", $var = "")
{ // Shortcut to the other function.
	if ($GLOBALS["fpSystemSettings"]["disableDebugCT"] == true)
	{
		return;
	}

	fp_debugCurrentTimeMillis($debugString, false, $var);
}


function fp_print_pre($str)
{
	print "<pre>" . $str . "</pre>";
}


function fp_debugCurrentTimeMillis($debugString = "", $showCurrentTime = true, $var = "")
{
	// Display the current time in milliseconds, and, if available,
	// show how many milliseconds its been since the last time
	// this function was called.  This helps programmers tell how
	// long a particular function takes to run.  Just place a call
	// to this function before and after the function call.

	$lastTime = $GLOBALS["currentTimeMillis" . $var] * 1;

	$curTime = microtime(true) * 1000;

	//if ($debugString != "")
	//{
	$debugString = "<span style='color:red;'>DEBUG:</span>
						<span style='color:green;'>$debugString</span>";
	//}

	print "<div style='background-color: white;'>$debugString
			";
	if ($showCurrentTime == true)
	{
		print "<span style='color: red;'>TIME:</span>
				<span style='color: green;'>$curTime" . "ms</span>";
	}

	if ($lastTime > 1)
	{
		$diff = round($curTime - $lastTime,2);
		print "<span style='color: blue;'> ($diff" . "ms since last check)</span>";
	} else {
		// Start of clock...
		print "<span style='color: blue;'> --- </span>";
	}

	print "</div>";
	$GLOBALS["currentTimeMillis" . $var] = $curTime;
	$GLOBALS["currentTimeMillis"] = $curTime;

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
function fp_verifyAllFacultyLogins($username, $password) {
  
  // Check for hooks...
  if (function_exists("functions_fp_verifyAllFacultyLogins")) {
    return call_user_func("functions_fp_verifyAllFacultyLogins", $username, $password);
  }  
    
  // Authenticate by the user_auth table by default.
  $db = new DatabaseHandler();
  $res = $db->dbQuery("SELECT * FROM user_auth
                        WHERE user_name = '?'
                        AND password = '?' 
                        AND is_faculty = '1' ", $username, md5($password));
  $cur = $db->dbFetchArray($res);
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
function fp_verifyAllStudentLogins($username, $password) {
  
  // Check for hooks...
  if (function_exists("functions_fp_verifyAllStudentLogins")) {
    return call_user_func("functions_fp_verifyAllStudentLogins", $username, $password);
  }  
 
  
  // Authenticate by the user_auth table by default.
  $db = new DatabaseHandler();
  $res = $db->dbQuery("SELECT * FROM user_auth
                        WHERE user_name = '?'
                        AND password = '?' 
                        AND is_student = '1' ", $username, md5($password));
  $cur = $db->dbFetchArray($res);
  if ($cur["user_name"] == $username) {
    return $cur["user_id"];
  }
  
  // By default, return FALSE;
  return FALSE;
    
}


?>