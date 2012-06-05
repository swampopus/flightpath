<?php


/**
 * This file contains misc functions for FlightPath
 */

 
function filter_markup($str) {
  
  // TODO: get allowed tags from a setting.
  
  $allowed_tags = array('a', 'em', 'strong', 'cite', 'blockquote', 'code', 'ul', 'ol', 'li', 'dl', 'dt', 'dd', 'span', 'div', 'b', 'i', 'u');
  
  return filter_xss($str, $allowed_tags);
  
} 
 
/**
 * This function is taken almost directly from Drupal 7's core code.  It is used to help us filter out
 * dangerous HTML which the user might type.
 * From the D7 documentation:
 * 
 * Filters HTML to prevent cross-site-scripting (XSS) vulnerabilities.
 * Based on kses by Ulf Harnhammar, see http://sourceforge.net/projects/kses. For examples of various XSS attacks, see: http://ha.ckers.org/xss.html.
 * This code does four things:
 *     Removes characters and constructs that can trick browsers.
 *     Makes sure all HTML entities are well-formed.
 *     Makes sure all HTML tags and attributes are well-formed.    
 *     Makes sure no HTML tags contain URLs with a disallowed protocol (e.g. javascript:).
 * 
 */ 
function filter_xss($string, $allowed_tags = array('a', 'em', 'strong', 'cite', 'blockquote', 'code', 'ul', 'ol', 'li', 'dl', 'dt', 'dd', 'span', 'div')) {
  // Only operate on valid UTF-8 strings. This is necessary to prevent cross
  // site scripting issues on Internet Explorer 6.
  if (!fp_validate_utf8($string)) {
    return '';
  }
  // Store the text format.
  filter_xss_split($allowed_tags, TRUE);
  // Remove NULL characters (ignored by some browsers).
  $string = str_replace(chr(0), '', $string);
  // Remove Netscape 4 JS entities.
  $string = preg_replace('%&\s*\{[^}]*(\}\s*;?|$)%', '', $string);

  // Defuse all HTML entities.
  $string = str_replace('&', '&amp;', $string);
  // Change back only well-formed entities in our whitelist:
  // Decimal numeric entities.
  $string = preg_replace('/&amp;#([0-9]+;)/', '&#\1', $string);
  // Hexadecimal numeric entities.
  $string = preg_replace('/&amp;#[Xx]0*((?:[0-9A-Fa-f]{2})+;)/', '&#x\1', $string);
  // Named entities.
  $string = preg_replace('/&amp;([A-Za-z][A-Za-z0-9]*;)/', '&\1', $string);

  return preg_replace_callback('%
    (
    <(?=[^a-zA-Z!/])  # a lone <
    |                 # or
    <!--.*?-->        # a comment
    |                 # or
    <[^>]*(>|$)       # a string that starts with a <, up until the > or the end of the string
    |                 # or
    >                 # just a >
    )%x', 'filter_xss_split', $string);
} 
 
/**
 * Like the filter_xss function, this is taken from D7's
 * _filter_xss_split function
 */
function filter_xss_split($m, $store = FALSE) {
  static $allowed_html;

  if ($store) {
    $allowed_html = array_flip($m);
    return;
  }

  $string = $m[1];

  if (substr($string, 0, 1) != '<') {
    // We matched a lone ">" character.
    return '&gt;';
  }
  elseif (strlen($string) == 1) {
    // We matched a lone "<" character.
    return '&lt;';
  }

  if (!preg_match('%^<\s*(/\s*)?([a-zA-Z0-9]+)([^>]*)>?|(<!--.*?-->)$%', $string, $matches)) {
    // Seriously malformed.
    return '';
  }

  $slash = trim($matches[1]);
  $elem = &$matches[2];
  $attrlist = &$matches[3];
  $comment = &$matches[4];

  if ($comment) {
    $elem = '!--';
  }

  if (!isset($allowed_html[strtolower($elem)])) {
    // Disallowed HTML element.
    return '';
  }

  if ($comment) {
    return $comment;
  }

  if ($slash != '') {
    return "</$elem>";
  }

  // Is there a closing XHTML slash at the end of the attributes?
  $attrlist = preg_replace('%(\s?)/\s*$%', '\1', $attrlist, -1, $count);
  $xhtml_slash = $count ? ' /' : '';

  // Clean up attributes.
  $attr2 = implode(' ', filter_xss_attributes($attrlist));
  $attr2 = preg_replace('/[<>]/', '', $attr2);
  $attr2 = strlen($attr2) ? ' ' . $attr2 : '';

  return "<$elem$attr2$xhtml_slash>";
}

function filter_xss_attributes($attr) {
  $attrarr = array();
  $mode = 0;
  $attrname = '';

  while (strlen($attr) != 0) {
    // Was the last operation successful?
    $working = 0;

    switch ($mode) {
      case 0:
        // Attribute name, href for instance.
        if (preg_match('/^([-a-zA-Z]+)/', $attr, $match)) {
          $attrname = strtolower($match[1]);
          $skip = ($attrname == 'style' || substr($attrname, 0, 2) == 'on');
          $working = $mode = 1;
          $attr = preg_replace('/^[-a-zA-Z]+/', '', $attr);
        }
        break;

      case 1:
        // Equals sign or valueless ("selected").
        if (preg_match('/^\s*=\s*/', $attr)) {
          $working = 1;
          $mode = 2;
          $attr = preg_replace('/^\s*=\s*/', '', $attr);
          break;
        }

        if (preg_match('/^\s+/', $attr)) {
          $working = 1;
          $mode = 0;
          if (!$skip) {
            $attrarr[] = $attrname;
          }
          $attr = preg_replace('/^\s+/', '', $attr);
        }
        break;

      case 2:
        // Attribute value, a URL after href= for instance.
        if (preg_match('/^"([^"]*)"(\s+|$)/', $attr, $match)) {
          $thisval = filter_xss_bad_protocol($match[1]);

          if (!$skip) {
            $attrarr[] = "$attrname=\"$thisval\"";
          }
          $working = 1;
          $mode = 0;
          $attr = preg_replace('/^"[^"]*"(\s+|$)/', '', $attr);
          break;
        }

        if (preg_match("/^'([^']*)'(\s+|$)/", $attr, $match)) {
          $thisval = filter_xss_bad_protocol($match[1]);

          if (!$skip) {
            $attrarr[] = "$attrname='$thisval'";
          }
          $working = 1;
          $mode = 0;
          $attr = preg_replace("/^'[^']*'(\s+|$)/", '', $attr);
          break;
        }

        if (preg_match("%^([^\s\"']+)(\s+|$)%", $attr, $match)) {
          $thisval = filter_xss_bad_protocol($match[1]);

          if (!$skip) {
            $attrarr[] = "$attrname=\"$thisval\"";
          }
          $working = 1;
          $mode = 0;
          $attr = preg_replace("%^[^\s\"']+(\s+|$)%", '', $attr);
        }
        break;
    }

    if ($working == 0) {
      // Not well formed; remove and try again.
      $attr = preg_replace('/
        ^
        (
        "[^"]*("|$)     # - a string that starts with a double quote, up until the next double quote or the end of the string
        |               # or
        \'[^\']*(\'|$)| # - a string that starts with a quote, up until the next quote or the end of the string
        |               # or
        \S              # - a non-whitespace character
        )*              # any number of the above three
        \s*             # any number of whitespaces
        /x', '', $attr);
      $mode = 0;
    }
  }

  // The attribute list ends with a valueless attribute like "selected".
  if ($mode == 1 && !$skip) {
    $attrarr[] = $attrname;
  }
  return $attrarr;
} 

function filter_xss_bad_protocol($string) {
  // Get the plain text representation of the attribute value (i.e. its meaning).
  $string = html_entity_decode($string, ENT_QUOTES, 'UTF-8');
  return htmlspecialchars(fp_strip_dangerous_protocols($string), ENT_QUOTES, 'UTF-8');
}

function fp_strip_dangerous_protocols($uri) {
  static $allowed_protocols;

  if (!isset($allowed_protocols)) {
    $allowed_protocols = array_flip(array('ftp', 'http', 'https', 'irc', 'mailto', 'news', 'nntp', 'rtsp', 'sftp', 'ssh', 'tel', 'telnet', 'webcal'));
  }

  // Iteratively remove any invalid protocol found.
  do {
    $before = $uri;
    $colonpos = strpos($uri, ':');
    if ($colonpos > 0) {
      // We found a colon, possibly a protocol. Verify.
      $protocol = substr($uri, 0, $colonpos);
      // If a colon is preceded by a slash, question mark or hash, it cannot
      // possibly be part of the URL scheme. This must be a relative URL, which
      // inherits the (safe) protocol of the base document.
      if (preg_match('![/?#]!', $protocol)) {
        break;
      }
      // Check if this is a disallowed protocol. Per RFC2616, section 3.2.3
      // (URI Comparison) scheme comparison must be case-insensitive.
      if (!isset($allowed_protocols[strtolower($protocol)])) {
        $uri = substr($uri, $colonpos + 1);
      }
    }
  } while ($before != $uri);

  return $uri;
}

 
function fp_validate_utf8($text) {
  if (strlen($text) == 0) {
    return TRUE;
  }
  // With the PCRE_UTF8 modifier 'u', preg_match() fails silently on strings
  // containing invalid UTF-8 byte sequences. It does not reject character
  // codes above U+10FFFF (represented by 4 or more octets), though.
  return (preg_match('/^./us', $text) == 1);
} 
 
 
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
 * Returns back the site's "token", which is a simply md5 of some randomness.
 * It is used primarily with forms, to ensure against cross-site forgeries.
 */
function fp_token() {
  $site_token = variable_get("site_token", "");
  if ($site_token == "") {
    $site_token = md5("" . time() . rand(1,9999));
    variable_set("site_token", $site_token);
  }
  
  return $site_token;
  
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

  // Close the seesion before we try to redirect.
  session_write_close();
  
  if ($path == "<front>") {
    $path = variable_get("front_page", "main");
  }
  
  
  header('Location: ' . fp_url($path, $query));
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

  // Does the query contain possible replacement patterns? (look for %)
  if (strpos($query, "%") !== 0) {
    // Find modules which implement the hook_menu_handle_replacement_pattern function.
    $modules = modules_implement_hook("menu_handle_replacement_pattern");
    foreach($modules as $module) {
      $query = call_user_func($module . '_menu_handle_replacement_pattern', $query);
    }
  }
  
  
  $rtn .= '<a href="' . fp_url($path, $query) . '" ';
  
  foreach ($attributes as $key => $value) {
    $rtn .= $key . '="' . $value . '" ';
  }
  
  $rtn .= ">$text</a>";



  return $rtn;
}

/**
 * This function will take a path, ex: "admin/config/module"
 * and a query, ex: "nid=5&whatever=yes"
 * And join them together, respecting whether or not clean URL's are enabled.
 */
function fp_url($path, $query, $include_base_path = TRUE) {
  
  // If clean URLs are enabled, we should begin with a ?, if not, use an &
  
  // TODO: make sure that works.
  
  $rtn = "";
  if ($include_base_path) {
    $rtn .= base_path() . "/";
  }  
 
  $rtn .= $path;
  
  $rtn .= "?";
  
  $rtn .= $query;
 
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
  
  if (!user_has_permission("view_fpm_debug")) {
    return;
  }
  
  // Complex variable?  Change it to print_r.
  if (is_array($str) || is_object($str)) {
    $str = "<div class='fp-html-print-r-wrapper'>" . fp_html_print_r($str) . "</div>";
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
 * Similar to print_r, this will return an HTML-friendly
 * click-to-open system similar in design to Krumo.
 */
function fp_html_print_r($var, $name = "") {  
  $rtn = "";
  
  $type = gettype($var);
  $rnd = md5(mt_rand(0, 999999) . microtime() . $type . $name);
  
  if ($type == "boolean") {
    $var = ($var == TRUE) ? "TRUE" : "FALSE";
  }  
  
  $count = "";
  if ($type == "string") {
    $count = " - " . strlen($var) . " " . t("chars");
  }
  
  if ($type == "array" || $type == "object") {
      
    
    if ($type == "array") {
      $count = " - " .  count($var) . " " . t("elements");
    }
    
    if ($type == "object") {
      $count = " - " . get_class($var);
    }
    
    $rtn .= "<div class='fp-html-print-r-multi-row'>
               <div class='fp-html-print-r-selector'
                  onClick='\$(\"#fp-html-print-r-var-value-$rnd\").toggle(\"medium\");'
                  >
                  <span class='fp-html-print-r-var-name'>$name</span>
                  <span class='fp-html-print-r-var-type'>($type$count)</span>
                  </div>
               <div class='fp-html-print-r-var-value' id='fp-html-print-r-var-value-$rnd' style='display: none;'>";
    foreach ($var as $key => $value) {
      $rtn .= fp_html_print_r($value, $key);
    }
    
    $rtn .= "</div>
             </div>";
  }
  else if ($type == "string" && strlen($var) > 50) {
    // If the variable is fairly long, we want to also make it a hide-to-show type field.
    $rtn .= "<div class='fp-html-print-r-multi-row'>
               <div
                  onClick='\$(\"#fp-html-print-r-var-value-$rnd\").toggle(\"medium\");'
                  >
                    <span class='fp-html-print-r-var-name'>$name</span>
                    <span class='fp-html-print-r-var-type'>($type$count)</span>
                    <span class='fp-html-print-r-var-value-abbr'>" . htmlentities(substr($var, 0, 50)) . "...</span>
                  </div>
               <div class='fp-html-print-r-var-value' id='fp-html-print-r-var-value-$rnd' style='display: none;'>
               ";
    $rtn .= htmlentities($var);
    $rtn .= "</div></div>";    
  }
  else { 
    
    $html_val = $var;
    if ($type != "resource") {
      $html_val = htmlentities("" . $var);
    }
    
    $rtn .= "<div class='fp-html-print-r-single-row'>                
                <span class='fp-html-print-r-var-name'>$name</span>
                <span class='fp-html-print-r-var-type'>($type$count)</span>
                <span class='fp-html-print-r-var-value'>$html_val</span>
           </div>";
  }  
  
  return $rtn;
}



/**
 * Will output a debugCT statement only if the user
 * has access to view it. 
 */
function z__admin_debug($str, $var_name = "")
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
 * This will find and include the module in question, calling
 * it's hook_init() function if it has one.  
 *
 * Will return TRUE or FALSE for success or failure to include
 * the module.
 *
 * If the use_module_path is set to some value, we will not attempt to use
 * the setting for this module's path.  Useful if we do not have the module in our
 * modules table yet.
 * 
 * Example use:  include_module("course_search");
 * 
 * @param string $module
 */
function include_module($module, $bool_call_init = TRUE, $use_module_path = "") {

  $system_path = trim($GLOBALS["fp_system_settings"]["file_system_path"]);
  
  $module_path = $GLOBALS["fp_system_settings"]["modules"][$module]["path"];
  if ($use_module_path != "") {
    $module_path = $use_module_path;
  }
  
  if ($module_path != "") {
    $path = $module_path . "/$module.module";
    
        
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
 * Find and include the module's .install file, if it exists.
 * Returns TRUE or FALSE if it was able to find & include the file.
 */
function include_module_install($module, $path) {
  $system_path = trim($GLOBALS["fp_system_settings"]["file_system_path"]);
    
  $install_path = $path . "/$module.install";
  if (file_exists($system_path . "/" . $install_path)) {
    require_once($system_path . "/" . $install_path);
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
function user_has_role($role) {
  global $user;
  
  // Admin always = TRUE
  if ($user->id == 1) return TRUE;
  
  // Check for other users...
  if (in_array($role, $user->roles)) return TRUE;
  
  return FALSE;
  
}


/**
 * Returns TRUE or FALSE if the logged in user has access based on the
 * permission supplied.
 *
 * @param String $permission
 */
function user_has_permission($permission) {
  global $user;  
  
  //fpm("checking permission $permission");
  
  // If the user is admin (id == 1) then they always have access.
  if ($user->id == 1) return TRUE;
  
  
  // Otherwise, simply check their permissions array.
  if (in_array($permission, $user->permissions)) {
    return TRUE;
  }
  
    
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
  return fp_debug_current_time_millis($debug_val, false, $var);
}


function fp_debug_current_time_millis($debug_val = "", $show_current_time = true, $var = "")
{
  // Display the current time in milliseconds, and, if available,
  // show how many milliseconds its been since the last time
  // this function was called.  This helps programmers tell how
  // long a particular function takes to run.  Just place a call
  // to this function before and after the function call.
  
  $rtn = "";
  
  $debug_string = $debug_val;
  if (is_array($debug_val) || is_object($debug_val)) {
    $debug_string = "<pre>" . print_r($debug_val, true) . "</pre>";
  }

  $last_time = $GLOBALS["current_time_millis" . $var] * 1;

  $cur_time = microtime(true) * 1000;

  $debug_string = "<span style='color:red;'>DEBUG:</span>
            <span style='color:green;'>$debug_string</span>";

  $rtn .= "<div style='background-color: white;'>$debug_string";

  if ($last_time > 1)
  {
    $diff = round($cur_time - $last_time,2);
    $rtn .= "<span style='color: blue;'> ($diff" . "ms since last check)</span>";
  } else {
    // Start of clock...
    $rtn .= "<span style='color: blue;'> --- </span>";
  }

  $rtn .= "</div>";
  $GLOBALS["current_time_millis" . $var] = $cur_time;
  $GLOBALS["current_time_millis"] = $cur_time;

  return $rtn;
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

