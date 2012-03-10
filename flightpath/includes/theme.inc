<?php

/**
 * These functions deal mostly with theme-related functions.
 * In other words, functions responsible for drawing, rendering, etc, HTML
 * to the screen.
 */


/**
 * Output the contents of the $page variable to the screen.
 * $page is an array containing details about the page, as well as
 * its original menu item (router_item) definition.
 */
function fp_display_page($page) {
  
  $screen = new AdvisingScreen("",null,"not_advising");
  $screen->page_title = $page["title"];
  $screen->page_has_search = $page["router_item"]["page_settings"]["page_has_search"];
  $screen->page_banner_is_link = $page["router_item"]["page_settings"]["page_banner_is_link"];
  $screen->page_hide_report_error = $page["router_item"]["page_settings"]["page_hide_report_error"];
  
  // Was this page a tab?  And if so, are there any other tabs we need to display? //
  if ($page["router_item"]["type"] == MENU_TYPE_TAB || $page["router_item"]["tab_parent"] != "") {
    // We know we should have at least 1 tab for this page.
    $tab_array = fp_build_tab_array($page);        
    $screen->page_tabs = fp_render_tab_array($tab_array);    
  }
  
  
      
  // Build up the content //
  
  $content_top = "";  
  $content_top .= fp_render_greeting();
  
  // Any sub-tabs we need to render?
  if ($page["router_item"]["type"] == MENU_TYPE_SUB_TAB) {
    $sub_tab_array = fp_build_sub_tab_array($page);
    $content_top .= fp_render_sub_tab_array($sub_tab_array);
  }
  
  
  
  
  // If there are "messages" waiting, then let's add them to the top of content.
  if (count($_SESSION["fp_messages"]) > 0) {
    $content_top .= "<div class='fp-messages'>";
    foreach ($_SESSION["fp_messages"] as $key => $msg) {
      $content_top .= "<div class='fp-message'>$msg</div>";      
    }
    $content_top .= "</div>";    
    unset($_SESSION["fp_messages"]);
  }

  // TODO:  content_top gets the Currently Advising box.    
  
  $screen->page_content = $content_top .= $page["content"];
  // If there are CSS files to add, add those.
  if (is_array($GLOBALS["fp_extra_css"])) {
    foreach ($GLOBALS["fp_extra_css"] as $filename) {
      //pretty_print ("adding $filename");
      $screen->add_css($filename);
    }
  }
  
  
  $screen->output_to_browser();  
}



/**
 * Create a "sub-tab" array, which looks like a standard tab_array,
 * but it contains only this page's sub-tab siblings.
 */
function fp_build_sub_tab_array($page) {
  $tab_array = array();

  $tab_family = $page["router_item"]["tab_family"];
  $active_tab_path = $page["path"];

  $items = menu_get_items_in_tab_family($tab_family);
  
  foreach ($items as $item) {
    $title = $item["title"];
    $path = $item["path"];
    $active = FALSE;
    $on_click = $page["page_settings"]["tab_on_click"];
    if ($on_click == "") {
      // Just a simple link to the path
      $on_click = "window.location=\"" . base_path() . "/" . $path . "\"";
    }
          
    if ($path == $active_tab_path) {
      // This is the current page we are on.
      $active = TRUE;
      $title = $page["title"];
      $on_click = "";
    }            

    $tab_array[] = array(
      "title" => $item["title"],
      "active" => $active,
      "on_click" => $on_click,
    );
    
  }
  
  return $tab_array;
}


/**
 * Looks at the current page array and returns a valid $tab_array
 * Meant for the top of the screen.
 */
function fp_build_tab_array($page) {
    $tab_array = array();
    
    $tab_family = $page["router_item"]["tab_family"];
    $active_tab_path = $page["path"];
    
    if ($page["router_item"]["type"] == MENU_TYPE_SUB_TAB) {
      // We want to know the PARENT's tab_family, so we can display the correct tabs at the top of the page.
      $tab_parent = $page["router_item"]["tab_parent"];
      
      $item = menu_get_item($tab_parent);      
      $tab_family = $item["tab_family"];
      $active_tab_path = $item["path"];
      
    }
        
    
    // look for other possible tabs, based on the "tab_family" value 
    // TODO:  also check on access_callback, permissions, etc.   
    $items = menu_get_items_in_tab_family($tab_family);
    foreach ($items as $item) {
      $title = $item["title"];
      $path = $item["path"];
      $active = FALSE;
      $on_click = $page["page_settings"]["tab_on_click"];
      if ($on_click == "") {
        // Just a simple link to the path
        $on_click = "window.location=\"" . base_path() . "/" . $path . "\"";
      }
            
      if ($path == $active_tab_path) {
        // This is the current page we are on.
        $active = TRUE;
        $title = $page["title"];
        $on_click = "";
      }            

      $tab_array[] = array(
        "title" => $item["title"],
        "active" => $active,
        "on_click" => $on_click,
      );
      
    }
    
    return $tab_array;
}



/**
 * This displays a friendly message to the user, and provides
 * a logout link at the top.
 */
function fp_render_greeting() {
  global $user;
  if ($user->id < 1) {
    // No one is logged in!  Don't display.
    return "";
  }
  
  $rtn = "";
  
  $today = date("D, F jS, Y",time("today"));
  
  $rtn .= "<div class='flightpath-greeting-message'>
              Welcome $user->name! Today is $today. " . l("Log out?", "logout") . "
          </div>";
  
  
  return $rtn;  
  
}



/**
 * Similar to render_tab_array.
 */
function fp_render_sub_tab_array($tab_array) {
  $rtn = "";
  
  $rtn .= "<div class='sub-tabs'>";

  foreach ($tab_array as $tab) {
    $title = $tab["title"];
    $on_click = $tab["on_click"];
    $active = $tab["active"];
    
    $extra_class = "";
    if ($active) $extra_class = "gradbutton-active";
    
    $rtn .= fp_render_button($title, $on_click, true, "", $extra_class);
    
  }
  
  
  $rtn .= "</div>";
      
  return $rtn;
}



/**
 * Returns the HTML to draw a pretty button.
 *
 * @param string $title
 * @param string $on_click
 * @param bool $bool_padd
 * @param string $style
 * @return string
 */
function fp_render_button($title, $on_click, $bool_padd = true, $style = "", $extra_class = "")
{
  // Style is expected to look like:
  // style='some:thing;'
  // with SINGLE apostrophes!  not quotes.

  $on_mouse = "onmouseover='this.className=\"gradbutton gradbutton_hover hand $extra_class\";'
        onmouseout='this.className=\"gradbutton hand $extra_class\";'
        onmousedown='this.className=\"gradbutton gradbutton_down hand $extra_class\";'
        onmouseup='this.className=\"gradbutton gradbutton_hover hand $extra_class\";'
        ";

  if (fp_screen_is_mobile()) $on_mouse = "";  // Causes problems for some mobile devices.
  
  if ($bool_padd)
  {
    $padd = "&nbsp; &nbsp;";
  }


  $rtn = "<span class='gradbutton hand $extra_class' onClick='$on_click' $on_mouse $style >
      $padd $title $padd
      </span>

    ";
  return $rtn;
}

/**
 * Given a propperly formatted tab_array, this will return the HTML
 * to draw it on a page.
 *
 * @param array $tab_array
 *       - Array should have this structure:
 *         - $tab_array[i]["title"] = The title or caption of the tab. "main", or "Edit", etc.
 *         - $tab_array[i]["active"] = boolean.  True if this is the tab we are currently looking at.
 *         - $tab_array[i]["on_click"] = This is a valid onClick='blah blah' that is the result of clicking the tab.
 * 
 * @return string
 */
function fp_render_tab_array($tab_array)
{

  // TODO:  fix mobile tabs
  //if ($this->page_is_mobile) {
  //  return $this->draw_mobile_tabs($tab_array);
  //}
  
  $rtn = "";

  $rtn .= "<table border='0' width='100%' cellpadding='0' cellspacing='0'>
    <tr>
    ";

  $img_path = fp_theme_location() . "/images";


  for ($t = 0; $t < count($tab_array); $t++)
  {
    $title = $tab_array[$t]["title"];
    $active = $tab_array[$t]["active"];
    $on_click = $tab_array[$t]["on_click"];

    if ($title == "")
    {
      continue;
    }

    $padd = "30px;";
    if ($t > 0)
    {
      $padd = "5px;";
    }

    $rtn .= "<td style='padding-right: $padd'></td>
        <td>";

    if ($active != TRUE)
    { //innactive tabs...

      $theclass = "inactive_tab hand";
      $overclass = "inactive_tab_over";
      if ($on_click == "")
      {
        $theclass = "inactive_tab";
        $overclass = "inactive_tab_over_no_link";
      }

      $rtn .= "
    
    <table align='left' cellpadding='0' onClick='$on_click' cellspacing='0' class='$theclass' onmouseover='this.className=\"$overclass\";' onmouseout='this.className=\"inactive_tab\";'>
    <tr>
      <td class='tab_tl_i' align='left' valign='baseline'  width='12'></td>
      <td class='tab_top_i' valign='baseline' ></td>
      <td class='tab_tr_i' valign='baseline' align='right' width='12'></td>
    </tr>
    <tr>
     <td class='tab_left_i' align='left' valign='baseline' width='12'>
      <img src='$img_path/tab_left_i.gif' width='12' height='18'>
    </td>
    <td align='left' nowrap class='tab_center_i'>
     <div style='padding-bottom: 3px; margin-top: -2px;' class='tab_text'>$title</div>
    </td>
    <td class='tab_right_i' align='right' valign='baseline'>
     <img src='$img_path/tab_right_i.gif' width='12' height='18'>
    </td>
    </tr>
    </table>

    ";

    } else {
      // active tab...
      $rtn .= "
    <table align='left' cellpadding='0' cellspacing='0' class='active_tab'>
      <tr>
        <td class='tab_tl' align='left' valign='baseline' width='12'></td>
        <td class='tab_top' valign='baseline'></td>
        <td class='tab_tr' valign='baseline' align='right' width='12'></td>
      </tr>
      <tr>
        <td class='tab_left' align='left' valign='baseline' width='12'>
        <img src='$img_path/tab_left.gif' width='12' height='18'>
        </td>
        <td align='left' nowrap class='tab_center'>
          <div style='padding-bottom: 3px; margin-top: -2px;' class='tab_text'>$title</div>
        </td>
        <td class='tab_right' align='right' valign='baseline'>
          <img src='$img_path/tab_right.gif' width='12' height='18'>
        </td>
      </tr>
    </table>      
    ";

    }

    $rtn .= "</td>";

  }


  $rtn .= "
     </tr>
     </table>";

  return $rtn;

}




function display_not_found($msg = "") {
  
  $page = array(
    "title" => "Not Found",
    "content" => "<h2>Page not found</h2>Sorry, the requested page could not be found!",
  );
  
  fp_display_page($page);
}


function display_access_denied($system_name = "", $bool_die_at_end = TRUE) {
  
  // Check for hooks...
  if (function_exists("functions_display_access_denied")) {
    return call_user_func("functions_display_access_denied", $system_name, $bool_die_at_end);
  }  

  $pC = "";
  
  $pC .= "<h2>Access Denied";
  if ($system_name) {
    $pC .= " for $system_name";
  }
  $pC .= "</h2>";
  
  $pC .= "Sorry, but you do not have sufficent permissions in FlightPath
          to access this page.";
  
  
  $screen = new AdvisingScreen("", null, "not_advising");
  $screen->page_content = $pC;
  $screen->output_to_browser();
  
  if ($bool_die_at_end) {
    die;
  }
}





/**
 * Return the theme location
 */
function fp_theme_location() {
  return $GLOBALS["fp_system_settings"]["base_path"] . "/" . $GLOBALS["fp_system_settings"]["theme"];
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


function fp_render_menu_item($item) {
  $rtn = "";
  
  $description = $item["description"];
  $title = $item["title"];
  $url = $GLOBALS["fp_system_settings"]["base_path"] . "/" . $item["path"];
  $target = $item["page_settings"]["target"];
  $menu_icon = $item["page_settings"]["menu_icon"];
  if ($menu_icon != "") {
    $icon_img = "<img src='$menu_icon' border='0'>";    
  } 
  else { 
    $icon_img = "<span class='fp-menu-item-no-icon'></span>";
  } 
  
  if (!$description) $extra_class = "fp-menu-item-tight";
  
  $rtn .= "<div class='fp-menu-item $extra_class'>
            <div class='fp-menu-item-link-line'>
              <a href='$url' target='$target'>$icon_img $title</a>
            </div>
            ";
  if ($description) {
    $rtn .= " <div class='fp-menu-item-description'>$description</div>";
  }
  $rtn .= "</div>";  
  
  return $rtn;
}


function fp_render_currently_advising_box() {
  
}



function pretty_print($var) {
  print "<pre>" . print_r($var, true) . "</pre>";
}


