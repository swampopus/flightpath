<?php

/**
 * These functions deal mostly with theme-related functions.
 * In other words, functions responsible for drawing, rendering, etc, HTML
 * to the screen.
 */


/**
 * Output the contents of the $page variable to the screen.
 */
function fp_display_page($page) {
  
  $screen = new AdvisingScreen("",null,"not_advising");
  $screen->page_title = $page["title"];
  $screen->page_has_search = $page["router_item"]["page_settings"]["page_has_search"];
  $screen->page_banner_is_link = $page["router_item"]["page_settings"]["page_banner_is_link"];
  $screen->page_hide_report_error = $page["router_item"]["page_settings"]["page_hide_report_error"];
  
  // If there are "messages" waiting, then let's add them to the top of content.
  $content_top = "";
  if (count($_SESSION["fp_messages"]) > 0) {
    $content_top .= "<div class='fp-messages'>";
    foreach ($_SESSION["fp_messages"] as $key => $msg) {
      $content_top .= "<div class='fp-message'>$msg</div>";      
    }
    $content_top .= "</div>";    
    unset($_SESSION["fp_messages"]);
  }
  
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


