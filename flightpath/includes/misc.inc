<?php
/**
 * @file
 * This file contains misc functions for FlightPath
 */

 
 
 
/**
 * Send a request through the Internet and return the result as an object.
 * 
 * This is a modified copy of the Drupal 6 function drupal_http_request(),
 * taken from here: http://api.drupal.org/api/drupal/includes!common.inc/function/drupal_http_request/6
 */
function fp_http_request($url, $headers = array(), $method = 'GET', $data = NULL, $retry = 3, $timeout = 30.0) {
  global $db_prefix;

  $result = new stdClass();

  // Parse the URL and make sure we can handle the schema.
  $uri = parse_url($url);

  if ($uri == FALSE) {
    $result->error = 'unable to parse URL';
    $result->code = -1001;
    return $result;
  }

  if (!isset($uri['scheme'])) {
    $result->error = 'missing schema';
    $result->code = -1002;
    return $result;
  }

  timer_start(__FUNCTION__);

  switch ($uri['scheme']) {
    case 'http':
    case 'feed':
      $port = isset($uri['port']) ? $uri['port'] : 80;
      $host = $uri['host'] . ($port != 80 ? ':' . $port : '');
      $fp = @fsockopen($uri['host'], $port, $errno, $errstr, $timeout);
      break;
    case 'https':
      // Note: Only works for PHP 4.3 compiled with OpenSSL.
      $port = isset($uri['port']) ? $uri['port'] : 443;
      $host = $uri['host'] . ($port != 443 ? ':' . $port : '');
      $fp = @fsockopen('ssl://' . $uri['host'], $port, $errno, $errstr, $timeout);
      break;
    default:
      $result->error = 'invalid schema ' . $uri['scheme'];
      $result->code = -1003;
      return $result;
  }

  // Make sure the socket opened properly.
  if (!$fp) {
    // When a network error occurs, we use a negative number so it does not
    // clash with the HTTP status codes.
    $result->code = -$errno;
    $result->error = trim($errstr);

    // Log that this failed.
    watchdog("http_request", "fp_http_request failed! Perhaps the server cannot make requests?", array(), WATCHDOG_ERROR);

    return $result;
  }

  // Construct the path to act on.
  $path = isset($uri['path']) ? $uri['path'] : '/';
  if (isset($uri['query'])) {
    $path .= '?' . $uri['query'];
  }

  // Create HTTP request.
  $defaults = array(
    // RFC 2616: "non-standard ports MUST, default ports MAY be included".
    // We don't add the port to prevent from breaking rewrite rules checking the
    // host that do not take into account the port number.
    'Host' => "Host: $host", 
    'User-Agent' => 'User-Agent: FlightPath (+http://getflightpath.com/)',
  );

  // Only add Content-Length if we actually have any content or if it is a POST
  // or PUT request. Some non-standard servers get confused by Content-Length in
  // at least HEAD/GET requests, and Squid always requires Content-Length in
  // POST/PUT requests.
  $content_length = strlen($data);
  if ($content_length > 0 || $method == 'POST' || $method == 'PUT') {
    $defaults['Content-Length'] = 'Content-Length: ' . $content_length;
  }

  // If the server url has a user then attempt to use basic authentication
  if (isset($uri['user'])) {
    $defaults['Authorization'] = 'Authorization: Basic ' . base64_encode($uri['user'] . (!empty($uri['pass']) ? ":" . $uri['pass'] : ''));
  }

  foreach ($headers as $header => $value) {
    $defaults[$header] = $header . ': ' . $value;
  }

  $request = $method . ' ' . $path . " HTTP/1.0\r\n";
  $request .= implode("\r\n", $defaults);
  $request .= "\r\n\r\n";
  $request .= $data;

  $result->request = $request;

  // Calculate how much time is left of the original timeout value.
  $time_left = $timeout - timer_read(__FUNCTION__) / 1000;
  if ($time_left > 0) {
    stream_set_timeout($fp, floor($time_left), floor(1000000 * fmod($time_left, 1)));
    fwrite($fp, $request);
  }

  // Fetch response.
  $response = '';
  while (!feof($fp)) {
    // Calculate how much time is left of the original timeout value.
    $time_left = $timeout - timer_read(__FUNCTION__) / 1000;
    if ($time_left <= 0) {
      $result->code = HTTP_REQUEST_TIMEOUT;
      $result->error = 'request timed out';
      return $result;
    }
    stream_set_timeout($fp, floor($time_left), floor(1000000 * fmod($time_left, 1)));
    $chunk = fread($fp, 1024);
    $response .= $chunk;
  }
  fclose($fp);

  // Parse response headers from the response body.
  // Be tolerant of malformed HTTP responses that separate header and body with
  // \n\n or \r\r instead of \r\n\r\n.  See http://drupal.org/node/183435
  list($split, $result->data) = preg_split("/\r\n\r\n|\n\n|\r\r/", $response, 2);
  $split = preg_split("/\r\n|\n|\r/", $split);

  list($protocol, $code, $status_message) = explode(' ', trim(array_shift($split)), 3);
  $result->protocol = $protocol;
  $result->status_message = $status_message;

  $result->headers = array();

  // Parse headers.
  while ($line = trim(array_shift($split))) {
    list($header, $value) = explode(':', $line, 2);
    if (isset($result->headers[$header]) && $header == 'Set-Cookie') {
      // RFC 2109: the Set-Cookie response header comprises the token Set-
      // Cookie:, followed by a comma-separated list of one or more cookies.
      $result->headers[$header] .= ',' . trim($value);
    }
    else {
      $result->headers[$header] = trim($value);
    }
  }

  $responses = array(
    100 => 'Continue', 
    101 => 'Switching Protocols', 
    200 => 'OK', 
    201 => 'Created', 
    202 => 'Accepted', 
    203 => 'Non-Authoritative Information', 
    204 => 'No Content', 
    205 => 'Reset Content', 
    206 => 'Partial Content', 
    300 => 'Multiple Choices', 
    301 => 'Moved Permanently', 
    302 => 'Found', 
    303 => 'See Other', 
    304 => 'Not Modified', 
    305 => 'Use Proxy', 
    307 => 'Temporary Redirect', 
    400 => 'Bad Request', 
    401 => 'Unauthorized', 
    402 => 'Payment Required', 
    403 => 'Forbidden', 
    404 => 'Not Found', 
    405 => 'Method Not Allowed', 
    406 => 'Not Acceptable', 
    407 => 'Proxy Authentication Required', 
    408 => 'Request Time-out', 
    409 => 'Conflict', 
    410 => 'Gone', 
    411 => 'Length Required', 
    412 => 'Precondition Failed', 
    413 => 'Request Entity Too Large', 
    414 => 'Request-URI Too Large', 
    415 => 'Unsupported Media Type', 
    416 => 'Requested range not satisfiable', 
    417 => 'Expectation Failed', 
    500 => 'Internal Server Error', 
    501 => 'Not Implemented', 
    502 => 'Bad Gateway', 
    503 => 'Service Unavailable', 
    504 => 'Gateway Time-out', 
    505 => 'HTTP Version not supported',
  );
  // RFC 2616 states that all unknown HTTP codes must be treated the same as the
  // base code in their class.
  if (!isset($responses[$code])) {
    $code = floor($code / 100) * 100;
  }

  switch ($code) {
    case 200: // OK
    case 304: // Not modified
      break;
    case 301: // Moved permanently
    case 302: // Moved temporarily
    case 307: // Moved temporarily
      $location = $result->headers['Location'];
      $timeout -= timer_read(__FUNCTION__) / 1000;
      if ($timeout <= 0) {
        $result->code = HTTP_REQUEST_TIMEOUT;
        $result->error = 'request timed out';
      }
      elseif ($retry) {
        $result = fp_http_request($result->headers['Location'], $headers, $method, $data, --$retry, $timeout);
        $result->redirect_code = $result->code;
      }
      $result->redirect_url = $location;

      break;
    default:
      $result->error = $status_message;
  }

  $result->code = $code;
  return $result;
}
 

/**
 * Begin a microtime timer for later use.
 */
function timer_start($name) {
  global $timers;

  list($usec, $sec) = explode(' ', microtime());
  $timers[$name]['start'] = (float) $usec + (float) $sec;
  $timers[$name]['count'] = isset($timers[$name]['count']) ? ++$timers[$name]['count'] : 1;
}

/**
 * Works with the timer_start() function to return how long
 * it has been since the start.
 */
function timer_read($name) {
  global $timers;

  if (isset($timers[$name]['start'])) {
    list($usec, $sec) = explode(' ', microtime());
    $stop = (float) $usec + (float) $sec;
    $diff = round(($stop - $timers[$name]['start']) * 1000, 2);

    if (isset($timers[$name]['time'])) {
      $diff += $timers[$name]['time'];
    }
    return $diff;
  }
}


/**
 * Returns a random string of length len.
 */
function fp_get_random_string($len = 7, $alpha = TRUE, $numeric = TRUE, $symbols = FALSE) {
  
  $base = "";
  if ($alpha) {
    $base .= "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ";
  }
  if ($numeric) {
    $base .= "12345678901234567890";
  }
  if ($symbols) {
    $base .= "!@#$%^&*()_+!@#$%^&*()-=";
  }
  
  $str = "";
  for ($t = 0; $t < $len; $t++) {
    $base = str_shuffle($base);
    $str .= $base[0];
  }
  
  return $str;
  
}


 
/**
 * Call all modules which implement hook_clear_cache
 */
function fp_clear_cache() {
  // Find modules which implement hook_clear_cache
  $modules = modules_implement_hook("clear_cache");
  foreach ($modules as $module) {
    call_user_func($module . '_clear_cache');
  }  
} 
 
 
 
/**
 * Filter HTML, allowing only certain tags, and removing dangerous attributes.
 * 
 * $type can be:
 *    - "basic" - Only certain tags allowed, no attributes. Safest. New lines = <br>
 *    - "full" - All HTML is allowed through.
 * 
 */
function filter_markup($str, $type = "basic") {
  
  if ($type == "basic") {
    $str = nl2br($str);    
    $allowed_tags = array('a', 'em', 'strong', 'cite', 
                          'blockquote', 'code', 'ul', 'ol', 'li', 
                          'dl', 'dt', 'dd', 'span', 'div', 
                          'b', 'i', 'u', 'br', 'p', 'table', 'tr', 
                          'td', 'th', 'tbody', );
    
    $str = filter_xss($str, $allowed_tags);
  }
  
  
  return $str;
  
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
 
/**
 * Simple function to convert a string into a machine-readable string.
 * 
 * Useful for making possibly unsafe text work as an array index, a CSS class, etc.  Replaces
 * "bad" characters, or characters which might not be allowed for variables, for example,
 * into underscores (_).
 *
 * @param unknown_type $str
 * @return unknown
 */
function fp_get_machine_readable($str) {
 return preg_replace('@[^a-zA-Z0-9_]+@','_',$str);  
}

 
/////////////////////////////////////////////////////////////////////

/**
 * Return back an assoc array of our set degree classifications, separated by "level"
 */
function fp_get_degree_classifications() {
  $rtn = array();
  
  // Level 1
  $temp = explode("\n", variable_get("degree_classifications_level_1", "MAJOR ~ Major"));
  foreach ($temp as $line) {
    $temp2 = explode("~", $line);
    $machine_name = trim($temp2[0]);
    $title = trim($temp2[1]);
    if ($machine_name != "") {
      $rtn["levels"][1][$machine_name] = $title;
      $rtn["machine_names"][$machine_name] = $title;
    }
  }
  
  // Level 2
  $temp = explode("\n", variable_get("degree_classifications_level_2", "MINOR ~ Minor"));
  foreach ($temp as $line) {
    $temp2 = explode("~", $line);
    $machine_name = trim($temp2[0]);
    $title = trim($temp2[1]);
    if ($machine_name != "") {
      $rtn["levels"][2][$machine_name] = $title;
      $rtn["machine_names"][$machine_name] = $title;
    }
  }
  
  
  // Level 3
  $temp = explode("\n", variable_get("degree_classifications_level_3", "CONC ~ Concentration"));
  foreach ($temp as $line) {
    $temp2 = explode("~", $line);
    $machine_name = trim($temp2[0]);
    $title = trim($temp2[1]);
    if ($machine_name != "") {
      $rtn["levels"][3][$machine_name] = $title;
      $rtn["machine_names"][$machine_name] = $title;
    }
  }
  
  
  
  return $rtn;
}


/**
 * Returns back an assoc array for the supplied code.  Looks like:
 * $arr["level_num"] = number
 * $arr["title"] = the title
 * 
 * 
 */
function fp_get_degree_classification_details($degree_class = "MAJOR", $bool_return_class_code_as_title_if_not_found = TRUE) {
  $rtn = array();
  
  if ($bool_return_class_code_as_title_if_not_found) {
    // Use the degree_class as title for default, if we can't find it otherwise.
    $rtn["level_num"] = 0;
    $rtn["title"] = $degree_class;
    $rtn["degree_class"] = $degree_class;
  }
  
  $degree_classifications = fp_get_degree_classifications();
  foreach ($degree_classifications["levels"] as $num => $details) {
    if (isset($details[$degree_class])) {
      $rtn["level_num"] = $num;
      $rtn["title"] = $details[$degree_class];
      $rtn["degree_class"] = $degree_class;   
      break;   
    }
  }
  
  
  return $rtn;
}



/**
 * Return an array version of the term_id_structure field from the admin settings
 *
 */
function get_term_structures() {
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
    $rtn[trim($temp[1])] = array(
      "term_def" => $term_def,
      "short" => trim(@$tokens[1]),
      "abbr" => trim(@$tokens[2]),
      "full" => trim(@$tokens[3]),
      "disp_adjust" => trim(@$tokens[4]),
    );
  
  }
  
  
  return $rtn;
}


/**
 * Returns back an array of all the available requirement types (by code) that
 * have been defined.
 *
 */
function fp_get_requirement_types() {
  $rtn = array();
  
  $temp = explode("\n", variable_get("requirement_types", "g ~ General Requirements\nc ~ Core Requirements\ne ~ Electives\nm ~ Major Requirements\ns ~ Supporting Requirements\nx ~ Additional Requirements"));
  foreach ($temp as $line) {
    $line = trim($line);
    if ($line == "") continue;
    
    $temp = explode("~", $line);
    $code = trim(strtolower($temp[0]));
    $desc = trim($temp[1]);

    $rtn[$code] = $desc;   
    
  }
  
  // Make sure that code 'x' is set.
  if (!isset($rtn["x"])) {
    $rtn["x"] = t("Additional Requirements");
  }
  
  // Make sure code 'e' for Electives is set.
  if (!isset($rtn["e"])) {
    $rtn["e"] = t("Electives");
  }
  
  // Make sure code 'm' is set, for Major Requirements, our default type.
  if (!isset($rtn["m"])) {
    $rtn["m"] = t("Major Requirements");
  }
  
  
  
  return $rtn;
  
}


/**
 * If this function is called, it will override any other page tabs
 * which might be getting constructed.  This lets the programmer,
 * at run-time, completely control what tabs are at the top of the page.
 */
function fp_set_page_tabs($tab_array) {
  $GLOBALS["fp_set_page_tabs"] = $tab_array;
}

/**
 * Allows the programmer to define subtabs at the top of the page.
 *
 * @param unknown_type $tab_array
 */
function fp_set_page_sub_tabs($tab_array) {
  $GLOBALS["fp_set_page_sub_tabs"] = $tab_array;
}

/**
 * Allows the programmer to set the title of the page, overwriting any default title.
 *
 * @param unknown_type $title
 */
function fp_set_title($title) {  
  $GLOBALS["fp_set_title"] = $title;
  if ($title == "") {
    fp_show_title(FALSE);  // No title to show!
  }
  else {
    fp_show_title(TRUE);  // If we are calling this function, we clearly want to display the title.
  }
}

/**
 * Add a CSS class to the body tag of the page.  Useful for themeing later on.
 *
 * @param unknown_type $class
 */
function fp_add_body_class($class) {
  $GLOBALS["fp_add_body_classes"] .= " " . $class;
}


/**
 * Returns back the site's "token", which is a simply md5 of some randomness.
 * It is used primarily with forms, to ensure against cross-site forgeries.
 * The site's token gets saved to the variables table, for later use.  The idea
 * is that every installation of FlightPath has a semi-unique token.
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
 * Simple function to split a basic CSV string, trim all elements, then return
 * the resulting array.
 */
function csv_to_array($csv_string) {
  $temp = explode(",", $csv_string);
  $temp = array_map("trim", $temp);
  return $temp;
}


/**
 * Add a "message" to the top of the screen.  Useful for short messages like "You have been logged out"
 * or "Form submitted successfully."
 *
 * @param String $msg
 *          This is the string message itself.
 * @param String $type
 *          The "type" of message.  This string is added as a CSS class to the message, for theming later.
 * @param boolean $bool_no_repeat
 *          Boolean.  Should the message show more than once per page view?  Set to TRUE if it should NOT.  
 */
function fp_add_message($msg, $type = "status", $bool_no_repeat = FALSE) {

  $md5 = md5($type . $msg);  

  if ($bool_no_repeat && is_array($_SESSION["fp_messages"])) {
    // Make sure this message isn't already in the session.
    foreach($_SESSION["fp_messages"] as $s) {
      if ($s["md5"] == $md5) return;
    }
  }

    
  $_SESSION["fp_messages"][] = array("type" => $type, "msg" => $msg, "md5" => $md5);
}


/**
 * Add an extra CSS file to the page with this function.
 * Ex:    fp_add_css(fp_get_module_path("admin") . '/css/admin.css');
 *
 * @param String $path_to_css
 */
function fp_add_css($path_to_css) {
  $GLOBALS["fp_extra_css"][] = $path_to_css;
}



/**
 * Add extra javascript to the page.
 * 
 * - type = file... $js is expected to be the path to a javascript file.
 * - type = setting... $js is expected to be an associative array of settings.
 *            For example:  array("my_path" => "blah", "my_color" => "red").
 *            They will be available in javascript in the object FlightPath like so:
 *              FlightPath.settings.my_color;
 * 
 * Ex: fp_add_js(fp_get_module_path("admin") . '/js/admin.js');
 * 
 * @see fp_add_css()
 * 
 */
function fp_add_js($js, $type = "file") {
  if ($type == "file") {  
    $GLOBALS["fp_extra_js"][] = $js;
  }
  
  if ($type == "setting") {
    if (!isset($GLOBALS["fp_extra_js_settings"])) $GLOBALS["fp_extra_js_settings"] = array();
    
    $GLOBALS["fp_extra_js_settings"] = array_merge($GLOBALS["fp_extra_js_settings"], $js);
  }
  
}


/**
 * This function will create a string from a 1 dimensional assoc array.
 * Ex:   arr = array("pet" => "dog", "name" => "Rex")
 *      will return:    pet-dog,name-Rex    under the default settings.
 * 
 * Use the fp_explode_assoc function to piece it back together.
 * @see fp_explode_assoc
 */
function fp_join_assoc($arr, $glue = ",", $assign_sep = "-") {
  $rtn = "";
  
  foreach ($arr as $key => $val) {
    $rtn .= $key . $assign_sep . $val . $glue;
  }
  
  // Should be an extra glue character at the end we need to trim off.
  $rtn = rtrim($rtn, $glue);
  
  return $rtn;
}

/**
 * Takes a string (created by fp_join_assoc()) and re-creates the 1 dimensional assoc array.
 *
 * @see fp_join_assoc()
 */
function fp_explode_assoc($string, $delim = ",", $assign_sep = "-") {
  $rtn = array();
  
  $temp = explode($delim, $string);
  foreach($temp as $line) {
    $line = trim($line);
    if ($line == "") continue;
    
    $temp2 = explode($assign_sep, $line);
    if (is_numeric($temp2[1])) {
      $temp2[1] = $temp2[1] * 1;  // if its numeric anyway, make it have a numeric type.      
    }
    
    $rtn[$temp2[0]] = $temp2[1];
    
  }
  
  return $rtn;
}



/**
 * Return the filepath to the module
 *
 * @param unknown_type $module
 * @param unknown_type $bool_include_file_system_path
 * @param unknown_type $bool_include_base_path
 * @return unknown
 */
function fp_get_module_path($module, $bool_include_file_system_path = FALSE, $bool_include_base_path = TRUE) {
  
  $p = menu_get_module_path($module, $bool_include_file_system_path);
    
  if ($bool_include_base_path) {
    $p = $GLOBALS["fp_system_settings"]["base_path"] . "/" . $p;
  }
    
  return $p;
}



/**
 * Convenience function to return the /files system path.  Does NOT end with a trailing slash.
 *
 */
function fp_get_files_path() {
  return $GLOBALS["fp_system_settings"]["file_system_path"] . "/custom/files";
}



/**
 * Simply returns the module's row from the modules table, if it exists.
 *
 * @param unknown_type $module
 */
function fp_get_module_details($module) {
  
  // Special case if we are looking up flightpath itself
  if ($module == "flightpath") {
    $rtn = array(
      "info" => array("name" => t("FlightPath (Core)")),
      "version" => FLIGHTPATH_VERSION,
    );
    return $rtn;
  }
  
  $res = db_query("SELECT * FROM modules WHERE name = '?' ", $module);
  $cur = db_fetch_array($res);
  if ($test = unserialize($cur["info"])) {
    $cur["info"] = $test;
  }
  
  return $cur;
}



/**
 * Eventually, this function will be used to translate strings.  For now, just pass through.
 * Allows variable replacements.  Use like this:
 * t("@name's blob", array("@name" => "Richard"));
 * or simply
 * t("My blob");  if you don't need replacements.
 * 
 * Not implimented yet.
 */
function t($str, $vars = array()) {
    
  if (is_array($vars) && count($vars) > 0) {
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
 * Provides translation functionality when database is not available.
 * 
 * Not implemented yet
 */
function st($str, $vars = array()) {
  // Not implemented yet.  For now, just replicate t().
  if (is_array($vars) && count($vars) > 0) {
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
  $p = $GLOBALS["fp_system_settings"]["base_path"];
  
  // the base_path setting isn't set, so just use '.', meaning, start
  // at the currect directory by default.
  if ($p == "") {
    $p = ".";
  }
  
  return $p;
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
    
    $temp = @$GLOBALS["fp_system_settings"]["term_id_structure"];
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
      $term_def = @trim($tokens[0]);
      $full_description = @trim($tokens[2]);
      $abbr_description = @trim($tokens[3]);
      
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

    // No descr could be found, so just display the term_id itself.
    if (trim($rtn) == "") {
      $rtn = $term_id;
    }

    return $rtn;
  }





/**
 * Redirect the user's browser to the specified internal path + query.
 * 
 * We will automatically add the current_student_id variable, if it is not present
 * in the query.
 * 
 * Example uses:
 *  - fp_goto("admin");
 *  - fp_goto("test/1234");
 *  - fp_goto("test/123", "selection=yes&fruit=apple");
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

  
  // Does the path contain possible replacement patterns? (look for %)
  if (strpos($path, "%") !== 0) {
    $path = menu_convert_replacement_pattern($path);    
  }
  
  // Does the query contain possible replacement patterns? (look for %)
  if (strpos($query, "%") !== 0) {
    $query = menu_convert_replacement_pattern($query);    
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
function fp_url($path, $query = "", $include_base_path = TRUE) {
  
  // If clean URLs are enabled, we should begin with a ?, if not, use an &
  
  $rtn = "";
  if ($include_base_path) {
    $rtn .= base_path() . "/";
  }  
 
 
  $bool_clean_urls = variable_get("clean_urls", FALSE);
  if (!$bool_clean_urls) {
    // Clean URLs are NOT enabled!  Let's make sure the URL contains "index.php?q="
    $rtn .= "index.php?q=";    
  }
 
 
  $rtn .= $path;
  
  if ($query != "") {
    // Is there a ? already in the $rtn?  If not, add a ?.  If so, use a &.
    if (!strstr($rtn, "?")) {  
      $rtn .= "?";  
    }
    else {
      $rtn .= "&";
    }
    
    $rtn .= $query;
  }
 
  return $rtn;
          
}




  /**
   * This function will attempt to determine automatically
   * if we are on a mobile device, and should therefor use the mobile
   * theme and layout settings.
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
  
  $is_mobile = FALSE;
  
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
 * Invoke all module hooks for the supplied hook.
 */
function invoke_hook($hook = "example_hook_here", $params = array()) {
  $rtn = array();
  $modules = modules_implement_hook($hook);
  foreach($modules as $module) {
    $rtn[$module] = call_user_func_array($module . "_" . $hook, $params);
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
  
  $db = @$GLOBALS["fp_global_database_handler"];
  
  if (!is_object($db) || !is_object($db->pdo)) {
    // Something isn't right, or it wasn't set correctly.  Create a new connection.
    $GLOBALS["fp_global_database_handler"] = new DatabaseHandler();
  }
  
  return $GLOBALS["fp_global_database_handler"];  
  
}


/**
 * Uses fp_add_message, but in this case, it also adds in the filename and line number
 * which the message came from!
 * 
 * Most useful for developers, tracking down issues.  It's also only visible to administrators
 * with the "view_fpm_debug" permission. So if you need to display a message only to admins,
 * you can use fpm() as a shortcut.
 * 
 * Note: If you attempt to fpm() an array
 * or object with too many levels of nesting, it may run out of memory and your script will die.
 */
function fpm($var) {
  
  if (!user_has_permission("view_fpm_debug")) {
    return;
  }
  
  // Complex variable?  Change it to print_r.
  $str = $var;
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
 * Displays a depricated message on screen.  Useful for tracking down 
 * when depricated functions are being used.
 */
function depricated_message($str = "A depricated function has been called.") {
  fpm($str);
  fpm(debug_backtrace());
}



/**
 * Similar to print_r, this will return an HTML-friendly
 * click-to-open system similar in design to Krumo.
 */
function fp_html_print_r($var, $name = "", $cnt = 0) {  
  $rtn = "";

  
  if ($cnt > 20) {
    // Max levels deep.  Deeper, and PHP might run
    // out of memory or complain.
    $rtn .= "<div class='fp-html-print-r-too-deep'>
              " . t("Depth too great. To view deeper,
                     rephrase your fpm() call, starting at this depth.") . "
             </div>";
    return $rtn;
  }
  
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
      $rtn .= fp_html_print_r($value, $key, ($cnt + 1));
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
 * Creates a javascript "confirm" link, so when clicked it asks the user a question, then proceeds
 * if they select OK.  The main reason I want to do this is so I can pass the $question through
 * my t() function. (do it when you call this function)
 */
function fp_get_js_confirm_link($question, $action_if_yes, $link_text) {
  
  $rtn = "";  

  $question = fp_reduce_whitespace($question);
  
  $question = htmlentities($question, ENT_QUOTES);
  $question = str_replace("\n", "\\n", $question);
  
  $rtn .= "<a href='javascript: if(confirm(\"$question\")) { $action_if_yes;  }'>$link_text</a>";

      
  return $rtn;
}

/**
 * Creates a javascript "prompt" link, which will ask the user a question.
 * 
 * Similar to the fp_get_js_confirm_link function, but this is a prompt box
 * which lets the user type in a response.
 * 
 * @see fp_get_js_confirm_link
 */
function fp_get_js_prompt_link($question, $default, $action_if_yes, $link_text) {
  
  $rtn = "";  

  $question = fp_reduce_whitespace($question);
  
  $question = htmlentities($question, ENT_QUOTES);
  $question = str_replace("\n", "\\n", $question);
  
  $rtn .= "<a href='javascript: var response = prompt(\"$question\", \"$default\");
                                if (response != null) 
                                {
                                   $action_if_yes ;  
                                }
                                '>$link_text</a>";

      
  return $rtn;
}


/**
 * Creates a javascript "alert" link, which tells the user some message with javascript alert().
 * 
 * Similar to the fp_get_js_confirm_link function, but this is a simple alert message,
 * with no user input.
 * 
 * @see fp_get_js_confirm_link
 */
function fp_get_js_alert_link($message, $link_text = "", $extra_css_class = "") {
  
  $rtn = "";  

  if ($link_text == "") {
    $link_text = "[?]";
  }
  
  $message = fp_reduce_whitespace($message);
  
  $message = str_replace("\n", "[NL]", $message);
  $message = htmlentities($message, ENT_QUOTES);
  $message = str_replace("&quot;", "\&quot;", $message);
  $message = str_replace("[NL]", "\\n", $message);
      
  $rtn .= "<a href='javascript: alert(\"" . $message . "\");' class='fp-alert-link $extra_css_class'>$link_text</a>";

      
  return $rtn;  
  
}


/**
 * Simple helper function to reduce whitespace (like double-spaces) 
 *
 * @param unknown_type $str
 */
function fp_reduce_whitespace($str) {
  // Cheap hack to get rid of whitespace    
  for ($t = 0; $t < 5; $t++) {
    $str = str_replace("  ", " ", $str);
  }  

  return $str;  
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
 * This simple function will take a number and truncate the number of decimals
 * to the requested places.  This can be used in place of number_format(), which *rounds*
 * numbers.
 * 
 * For example, number_format(1.99999, 2) gives you 2.00.
 * But THIS function gives:
 *   fp_truncate_decimals(1.999999, 2) = 1.99
 * 
 *
 * @param unknown_type $places
 */
function fp_truncate_decimals($num, $places = 2) {
    
  // does $num contain a .?  If not, add it on.
  if (!strstr("" . $num, ".")) {
    $num .= ".0";
  }
  
  // Break it by .
  $temp = explode (".", "" . $num);
  
  // Get just the decimals and trim 'em
  $decimals = trim(substr($temp[1], 0, $places));
  if (strlen($decimals) < $places) {
    // Padd with zeros on the right!
    $decimals = str_pad($decimals, $places, "0", STR_PAD_RIGHT);
  }
  
  $new_num = $temp[0] . "." . $decimals; 
  
  return $new_num;
}



/**
 * Shortcut to fp_debug_current_time_millis()
 * 
 * @see fp_debug_current_time_millis()
 *
 * @param unknown_type $debug_val
 * @param unknown_type $var
 * @return unknown
 */
function fp_debug_ct($debug_val = "", $var = "")
{ // Shortcut to the other function.
  return fp_debug_current_time_millis($debug_val, false, $var);
}



/**
 * When called repeatedly, this function will display a message along with a milisecond count
 * out to the side.  Very useful for developers to time function calls or queries, to see how long they
 * are taking.
 * 
 * For example:
 * fp_debug_ct("starting query");
 * db_query(".........") // whatever
 * fp_debug_ct("finished query");
 * 
 * On screen, that would display our messages, with time values, so we can see how many milliseconds
 * it took to execute between calls of fp_debug_ct().
 *
 * @param String $debug_val
 *          The message to display on screen.
 * @param boolean $show_current_time
 *          Should we display the current time as well?
 * @param String $var
 *          Optional.  Include a variable name so you can have more than one timer running
 *          at the same time.
 * @return unknown
 */
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

  $last_time = @($GLOBALS["current_time_millis" . $var]) * 1;  //*1 forces numeric

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

