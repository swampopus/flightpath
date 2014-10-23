<?php

/**
 * These functions deal mostly with theme-related functions.
 * In other words, functions responsible for drawing, rendering, etc, HTML
 * to the screen.
 */


// TODO:  Implement this functioN!
function convert_html_to_bbcode($str) {
  depricated_message();
  return $str;
} 

// TODO: implement this!
function convert_bbcode_to_html($str) {
  depricated_message();
  return $str;
}


/**
 * Format a timestamp using the date command.
 * TODO: Make the formats something which can be controlled through the settings.
 * 
 * Available formats:
 *  - standard
 *  - short
 *  - pretty
 *  - just_date
 *
 */
function format_date($timestamp, $format = "standard", $custom = "") {
  
  if (!is_numeric($timestamp)) {
    $timestamp = 0;  // make sure it's numeric.
  }
  
  $f = "";  
  if ($format == "standard") {
    $f = "n/d/Y h:i:sa";
  }
  
  if ($format == "short") {
    $f = "n/d/Y - g:i";
  }
  
  if ($format == "pretty") {
    $f = "F jS, Y, g:ia";
  }
  
  if ($format == "just_date") {
    $f = "F jS, Y";
  }
  
  if ($custom != "") $f = $custom;
  
  return date($f, $timestamp);
  
  
}





/**
 * Render a "menu" block of menu items which are all rooted at the menu_root.
 * So if the menu root is tools, it might return items whose paths look like:
 *   tools/fun
 *   tools/here/there
 * So long as the menu type is "MENU_TYPE_NORMAL_ITEM".  Other types will be ignored.
 * 
 * 
 * We want to 
 */
function fp_render_menu_block($title = "Tools", $menu_root = "tools") {
  $rtn = "";
  
  $is_empty = true;
  if ($title != "") {
    $rtn .= fp_render_curved_line($title);
  }
  
  $menu_items = menu_get_items_beginning_with($menu_root);
  foreach ($menu_items as $item) {
    if ($item["type"] != MENU_TYPE_CALLBACK) {
      $rtn .= fp_render_menu_item($item);
      $is_empty = false;
    }
  }
  
  if ($is_empty) {
    return "";
  }
  //pretty_print($menu_items);
  
  return $rtn;   
  
}

/**
 * Output the contents of the $page variable to the screen.
 * $page is an array containing details about the page, as well as
 * its original menu item (router_item) definition.
 */
function fp_display_page($page) {
  global $current_student_id, $screen;
  $page_title = $page["title"];
  if ($GLOBALS["fp_set_title"] != "") {
    $page_title = $GLOBALS["fp_set_title"];
  }

  if (!is_object($screen)) {
    $screen = new AdvisingScreen("",null,"not_advising");
  }
  $screen->page_title = $page_title;
  $screen->page_has_search = $page["router_item"]["page_settings"]["page_has_search"];
  
  // If we don't have permission to search, then hide the little search.
  if (!user_has_permission("view_any_advising_session")) {
    $screen->page_has_search = FALSE;
  }
  
  // mobile screens never have the search box
  if (fp_screen_is_mobile()) {
    $screen->page_has_search = FALSE;
  }
  
  $screen->page_banner_is_link = $page["router_item"]["page_settings"]["page_banner_is_link"];
  $screen->page_hide_report_error = $page["router_item"]["page_settings"]["page_hide_report_error"];
  $screen->page_is_popup = $page["router_item"]["page_settings"]["page_is_popup"];
  if (isset($page["router_item"]["page_settings"]["screen_mode"])) {    
    $screen->screen_mode = $page["router_item"]["page_settings"]["screen_mode"];
  }
  if (isset($page["router_item"]["page_settings"]["bool_print"])) {
    $screen->bool_print = $page["router_item"]["page_settings"]["bool_print"];
  }

  if ($_REQUEST["scroll_top"] != "") {
    $screen->page_scroll_top = $_REQUEST["scroll_top"];    
  }
  
  // Was this page a tab?  And if so, are there any other tabs we need to display? //
  if ($page["router_item"]["type"] == MENU_TYPE_TAB || $page["router_item"]["tab_parent"] != "") {
    // We know we should have at least 1 tab for this page.
    $tab_array = fp_build_tab_array($page);        
    $screen->page_tabs = fp_render_tab_array($tab_array);    
  }
  
  // Should we override the page_tabs with what is in "fp_set_page_tabs" ?
  if (is_array($GLOBALS["fp_set_page_tabs"])) {
    //fpm($GLOBALS["fp_set_page_tabs"]);
    $screen->page_tabs = fp_render_tab_array($GLOBALS["fp_set_page_tabs"]);    
  }
  
      
  // Build up the content //
  
  $content_top = "";  
  $page_show_title = $page["router_item"]["page_settings"]["page_show_title"];
  if (isset($GLOBALS["fp_set_show_title"])) {
    $page_show_title = $GLOBALS["fp_set_show_title"];
  }
  
  if ($page_show_title == TRUE) {
    $content_top .= "<h2 class='title'>" . $page_title . "</h2>";
  } 
  
  if ($page["router_item"]["page_settings"]["display_greeting"] == TRUE) {  
    $content_top .= fp_render_greeting();
  }
  
  // Are there any "menu_link"s for this?  In order words, little links at the top of the page,
  // where breadcrumbs might appear.
  if (!$screen->bool_print && is_array($page["router_item"]["page_settings"]["menu_links"])) {
    $content_top .= "<ul class='top-menu-links'>";
    foreach ($page["router_item"]["page_settings"]["menu_links"] as $item) {
      $lclass = "";
      if ($c == 0) $lclass='first';
      
      $p = menu_convert_replacement_pattern($item["path"]);
      $q = menu_convert_replacement_pattern($item["query"]);
      $t = $item["text"];
      $a = $item["attributes"];
      if (!is_array($a)) $a = array();
      
      // Make sure the current user has access to view this link.  Otherwise, do not even
      // bother showing it.
      $test_item = menu_get_item($p) ;      
      if (!menu_check_user_access($test_item)) {
        continue;
      }
      
      
      $content_top .= "<li class='$lclass'>" . l($t, $p, $q, $a) . "</li>";
      $c++;
    }
    $content_top .= "</ul>";
  }
  
  
  if (!$screen->bool_print) {
    // Any sub-tabs we need to render?
    if ($page["router_item"]["type"] == MENU_TYPE_SUB_TAB) {
      $sub_tab_array = fp_build_sub_tab_array($page);
      $content_top .= fp_render_sub_tab_array($sub_tab_array);
    }
    
    // Should we override the page sub-tabs with what is in "fp_set_page_sub_tabs" ?
    if (is_array($GLOBALS["fp_set_page_sub_tabs"])) {
      $content_top .= fp_render_sub_tab_array($GLOBALS["fp_set_page_sub_tabs"]);    
    }
  }

  
  
  
  // If there are "messages" waiting, then let's add them to the top of content.
  if (count($_SESSION["fp_messages"]) > 0) {
    $content_top .= "<div class='fp-messages'>";
    foreach ($_SESSION["fp_messages"] as $key => $tmsg) {
      $type = $tmsg["type"];
      $message = $tmsg["msg"];
      $content_top .= "<div class='fp-message fp-message-$type'>$message</div>";
    }
    $content_top .= "</div>";    
    unset($_SESSION["fp_messages"]);
  }

  // content_top gets the Currently Advising box.
  if ($page["router_item"]["page_settings"]["display_currently_advising"] == TRUE) {
    $content_top .= fp_render_currently_advising_box(false, $screen->bool_blank, $screen->bool_print, NULL, $screen->screen_mode);
  }    
  
  $screen->page_content = $content_top .= $page["content"];
  // If there are CSS files to add, add those.
    
  
  // Add in the body classes
  $screen->page_body_classes .= " " . $GLOBALS["fp_add_body_classes"];  
  
  // Add in our URL
  $screen->page_body_classes .= " page-" . str_replace("/", "-", $_REQUEST["q"]);
  
  
  $screen->output_to_browser();  
}


/**
 * Sets whether the title should be shown on the page or not.
 */
function fp_show_title($bool_show = TRUE) {
  $GLOBALS["fp_set_show_title"] = $bool_show;
}


/**
 * This function will return the HTML to contruct a collapsible fieldset,
 * complete with javascript and style tags.
 *
 * @param String $content
 * @param String $legend
 * @param bool $bool_start_closed
 * @return String
 */
function fp_render_c_fieldset($content, $legend = "Click to expand/collapse", $bool_start_closed = false)
{
  
  // Create a random ID for this fieldset, js, and styles.
  $id = md5(rand(9,99999) . time());
  
  $start_js_val = 1;
  $fsstate = "open";
  $content_style = "";
  
  if ($bool_start_closed) {
    $start_js_val = 0;
    $fsstate = "closed";
    $content_style = "display: none;";
  }
  
  $js = "<script type='text/javascript'>
  
  var fieldset_state_$id = $start_js_val;
  
  function toggle_fieldset_$id() {
    
    var content = document.getElementById('content_$id');
    var fs = document.getElementById('fs_$id');
      
    if (fieldset_state_$id == 1) {
      // Already open.  Let's close it.
      fieldset_state_$id = 0;      
      \$(content).slideUp('medium');
      fs.className = 'c-fieldset-closed-$id';
    }
    else {
      // Was closed.  let's open it.
      fieldset_state_$id = 1;
      
      \$(content).slideDown('medium');
      fs.className = 'c-fieldset-open-$id';      
    }  
  }  
  </script>";
  
  $rtn = "  
    <fieldset class='c-fieldset-$fsstate-$id c-fieldset-$fsstate c-fieldset' id='fs_$id'>
      <legend><a href='javascript: toggle_fieldset_$id();' class='nounderline'>$legend</a></legend>
      <div id='content_$id' class='c-fieldset-content' style='$content_style'>
        $content
      </div>
    </fieldset>
    $js  
    
  <style>
  fieldset.c-fieldset-open-$id {
    border: 1px solid;
  }

  fieldset.c-fieldset-closed-$id {
    border: 1px solid;
    border-bottom-width: 0;
    border-left-width: 0;
    border-right-width: 0;    
  }  

  legend a {
    text-decoration: none;
  }
  
  </style>
    
  ";
  
  
  return $rtn;
}



/**
 * Create a "sub-tab" array, which looks like a standard tab_array,
 * but it contains only this page's sub-tab siblings.
 */
function fp_build_sub_tab_array($page) {
  global $current_student_id;
  
  $tab_array = array();

  $tab_family = $page["router_item"]["tab_family"];
  $active_tab_path = $page["path"];

  $items = menu_get_items_in_tab_family($tab_family);
  
  foreach ($items as $item) {
        
    // Does the user have access to this subtab?
    if (!menu_check_user_access($item)) {
      continue;
    }  
    
    $title = $item["title"];
    $path = $item["path"];
    $active = FALSE;
    $on_click = $page["page_settings"]["tab_on_click"];
    if ($on_click == "") {
      // Just a simple link to the path
      // Add the current_student_id to the query!
      $query = "current_student_id=$current_student_id";
      
      $turl = fp_url($path, $query, TRUE);
      
      $on_click = "window.location=\"" . $turl . "\"";
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
    global $current_student_id;
    $tab_array = array();
    
    $tab_family = $page["router_item"]["tab_family"];
    $active_tab_path = $page["path"];
    
    if ($page["router_item"]["tab_parent"] != "") {
      // We want to know the PARENT's tab_family, so we can display the correct tabs at the top of the page.
      $tab_parent = $page["router_item"]["tab_parent"];
      
      $item = menu_get_item($tab_parent);      
      $tab_family = $item["tab_family"];
      $active_tab_path = $item["path"];
      
    }
        
    
    // look for other possible tabs, based on the "tab_family" value 
    // Also check to make sure the user has access to the tab.   
    $items = menu_get_items_in_tab_family($tab_family);
    foreach ($items as $item) {
      
      // Does the user have access to this tab?
      if (!menu_check_user_access($item)) {
        continue;
      }
      
      $title = $item["title"];
      $path = $item["path"];
      $active = FALSE;
      $on_click = $page["page_settings"]["tab_on_click"];
      if ($on_click == "") {
        // Just a simple link to the path
        // Include the current_student_id!
        $query = "current_student_id=$current_student_id";
        
        $turl = fp_url($path, $query, TRUE);
        
        $on_click = "window.location=\"" . $turl . "\"";
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
   * Draws the CurrentlyAdvisingBox which appears at the top of the screen,
   * containing the student's information like name, major, etc.
   *
   * @param bool $bool_hide_catalog_warning
   *       - If set to TRUE, FP will not display a warning which tells
   *         the user that they are working under an outdated catalog year.
   * 
   * @return string
   */
  function fp_render_currently_advising_box($bool_hide_catalog_warning = false, $bool_blank = FALSE, $bool_print = FALSE, $degree_plan = NULL, $screen_mode = "") {
    global $current_student_id, $screen, $user, $student;
    
    $rtn  = "";
    
    $csid = $current_student_id;
    
    if (is_object($screen) && is_object($screen->degree_plan)) {
      $degree_plan = $screen->degree_plan;      
    }
    
    if (!isset($student) || $student == null || !is_object($student)) {
      $student = new Student($csid);
    }
    
    $settings = fp_get_system_settings();
          
    
    $rtn .= "<table class='fp-currently-advising'>";
    
    $for_term = $whatif = $what_if_select = $hypoclass = "";
    
    if ($GLOBALS["fp_advising"]["advising_term_id"] != "" && ($screen_mode != "not_advising" && $screen_mode != "detailed")
    && user_has_permission("can_advise_students"))
    {
      $t_term_id = $GLOBALS["fp_advising"]["advising_term_id"];
      $for_term = " " . t("for") . " " . get_term_description($t_term_id);

      // If this is an advisor or above
      $furl = fp_url("advise/popup-change-term");
      $for_term .= "<span style='font-size: 8pt; font-weight:normal;'>
             - <a href='javascript: popupWindow2(\"" . $furl . "\",\"advising_term_id=$t_term_id\");' style='color:blue; background-color: white; border: 1px solid black; padding-left: 3px; padding-right: 3px;'>" . t("change") . "<img src='" . fp_theme_location() . "/images/calendar1.jpg' height='13' border='0' style='vertical-align: bottom;'></a>
            </span>";


    }

    // TODO:  bool_blank? bool_print?
    if ($GLOBALS["fp_advising"]["advising_what_if"] == "yes" && !$bool_blank)
    {
      $whatif = " (" . t("in \"What If\" mode") . ") ";
      $hypoclass = "hypo";
      // latest cat year because its what if.
      $student->catalog_year = $settings["current_catalog_year"];
      if ($bool_print != true) {
        $what_if_select = "<div class='tenpt'><b>
          " . l(t("Change What If Settings"), "what-if", "advising_what_if=yes&what_if_major_code=none&what_if_track_code=none&current_student_id=$current_student_id") . "
          </b></div>";
      }
    }

    $ca = t("Currently Advising");
    
    if (!user_has_permission("can_advise_students") || $screen_mode == "detailed") {
      $ca = t("Student Details");
    }

    if ($bool_blank == true) {
      $ca = t("Viewing Blank Degree Plan");
    }

    
    $rtn .= "<tr><td colspan='2' style='padding-bottom: 10px;'>
        $what_if_select
      <table border='0' width='100%'  class='elevenpt blueBorder' cellpadding='0' cellspacing='0' >
      <tr>
          <td colspan='4' class='blueTitle' align='center' height='20'>
          " . fp_render_square_line("$ca$whatif$for_term") . "
          </td>                    
      </tr>
      ";

    // Okay, let's build up the display array.
    $display_array = array();

    // How to display the catalog_year...
    $cat_year = $student->catalog_year . "-" . ($student->catalog_year + 1);

    // Should we display a catalog year warning?  This is
    // something that can be part of a settings table.
    if ($student->catalog_year < $settings["earliest_catalog_year"]) {
      $cat_year = "<b>$cat_year</b>";
      $bool_catalog_warning = true;
    }

    if ($settings["current_catalog_year"] > $settings["earliest_catalog_year"]) {
      // Is the student's catalog set beyond the range that
      // FP has data for?  If so, show a warning.
      if ($student->catalog_year > $settings["current_catalog_year"])
      {
        $cat_year = "<b>$cat_year</b>";
        $bool_future_catalog_warning = true;

      }
    }

    $db = get_global_database_handler();

    if ($degree_plan != NULL) {
      $degree_title = $degree_plan->get_title2();
    }
    if ($degree_title == "") {
      // Attempt to load the degree from the student's information.     
      $degree_plan = $db->get_degree_plan($student->major_code, $student->catalog_year, true);
      $degree_title = $degree_plan->get_title2();
    }

    if (!$bool_blank) {
      array_push($display_array, t("Name:") . " ~~ " . $student->name);
      array_push($display_array, t("CWID:") . " ~~ " . $student->student_id);
    }
    
    if ($screen_mode == "detailed") {
      $degree_title .= " ($degree_plan->major_code)";
    }
    
    array_push($display_array, t("Major:") . " ~~ " . $degree_title);
    // If this degree has tracks, we must display something about it here.

    if ($degree_plan->bool_has_tracks)
    {
      $extra_vars = "";

      /*if ($GLOBALS["advising_what_if"] == "yes")
      {
      $extra_vars .= "what_if_major_code={$GLOBALS["what_if_major_code"]}";
      $extra_vars .= "&what_if_track_code={$GLOBALS["what_if_track_code"]}";
      $extra_vars .= "&advising_what_if=yes";
      }*/
      
      $op_link = "<a href='javascript: popupWindow2(\"" . fp_url("advise/popup-change-track") . "\",\"$extra_vars\");'><img
              src='" . fp_theme_location() . "/images/popup.gif' border='0' 
              title='" . t("Click to change degree options.") . "'></a>";
      $op_text = t("Click to select:") . " $op_link";

      if ($screen_mode == "not_advising" || $screen_mode == "detailed")
      {
        $op_text = t("None selected");
        $op_link = "";
      }

      if (!user_has_permission("can_advise_students")) {
        
        if ($GLOBALS["fp_advising"]["advising_what_if"] != "yes")
        {
          // In other words, we do not have permission to advise,
          // and we are not in whatIf, so take out the link.
          $op_link = "";
          $op_text = t("None selected");

        }
      }


      // Did has the student already selected an option?
      if ($degree_plan->track_code != "")
      {
        // Display the track code in detailed mode.
        $tc = ($screen_mode == "detailed") ? "($degree_plan->track_code)" : "";
        
        $op_text = $degree_plan->track_title . " $tc $op_link";
      }


      array_push($display_array, t("Option:") . " ~~ " . $op_text);
    }
    if (!$bool_blank) {
      array_push($display_array, t("Rank:") . " ~~ " . $student->rank);
    }
    array_push($display_array, t("Catalog Year:") . " ~~ " . $cat_year);
    if (!$bool_blank) {
      array_push($display_array, t("Cumulative:") . " ~~ " . $student->cumulative_hours . " " . t("hrs") . ". &nbsp;" . fp_truncate_decimals($student->gpa, 3) . " " . t("GPA"));
    }

    if ($student->student_id > 1 || $bool_blank == true)
    { // Make sure we have selected a student! (or are viewing a blank plan)
      // Now, go through the array and display it.
      for ($t = 0; $t < count($display_array); $t = $t + 2)
      {
        $temp = explode(" ~~ ",$display_array[$t]);
        $name1 = trim($temp[0]);
        $value1 = trim($temp[1]);


        $temp = explode(" ~~ ",$display_array[$t+1]);
        $name2 = trim($temp[0]);
        $value2 = trim($temp[1]);


        if (fp_screen_is_mobile()) {
          // Mobile screen.  Needs to be more condensed.
          $rtn .= "<tr class='$hypoclass'>
                  <td valign='top'>$value1</td>
                  <td valign='top'>$value2</td>
                  </tr>";
        }
        else {
         // Regular desktop screen. 
        
          $rtn .= "
        
            <tr class='$hypoclass' >
              <td valign='top' width='20%' class='side_padding' style='padding-top: 5px;'>
                 $name1
              </td>
              <td width='30%' valign='top' class='side_padding elevenpt' style='padding-top: 5px;'>
                $value1
              </td>
              <td valign='top' align='left' width='20%' class='side_padding elevenpt' style='padding-top: 5px;'>
                   $name2
              </td>
               <td align='right' width='30%' valign='top' class='side_padding elevenpt' style='padding-top: 5px;'>
                   $value2
                </td>
            </tr>  ";
      
        }
        
      }
    } else {
      // No student has been selected yet!
      $rtn .= "<tr height='60'>
        <td align='center'> No advisee selected. </td>
      </tr>";
      $bool_hide_catalog_warning = true;
    }

    
    $rtn .= "</table>";

    if ($bool_catalog_warning == true && !$bool_hide_catalog_warning)
    {
      $rtn .= "
      
      <div class='tenpt hypo' style='margin-top: 4px; padding: 2px;'>
      <table border='0' cellspacing='0' cellpadding='0'>
      <td valign='top'>
        <img src='" . fp_theme_location() . "/images/alert_lg.gif' >  
      </td>
      <td valign='middle' class='tenpt' style='padding-left: 8px;'>
        <b>" . t("Important Notice:") . " </b>
        " . t("FlightPath cannot display degree plans from 
        catalogs earlier than @earliest.
        The above student's catalog year is @current, which means 
        that the degree plan below may not accurately 
        display this student's degree requirements.", array("@earliest" => $settings["earliest_catalog_year"] . "-" . ($settings["earliest_catalog_year"] + 1), "@current" => $cat_year)) . "
      </td>
      </table>
      </div>
      
    "; 
    }

    if ($bool_future_catalog_warning == true && !$bool_hide_catalog_warning)
    {
      $rtn .= "
      
      <div class='tenpt hypo' style='margin-top: 4px; padding: 2px;'>
      <table border='0' cellspacing='0' cellpadding='0'>
      <td valign='top'>
        <img src='" . fp_theme_location() . "/images/alert_lg.gif' >  
      </td>
      <td valign='middle' class='tenpt' style='padding-left: 8px;'>
        <b>Important Notice: </b>
        This student's catalog year is $cat_year, 
        and specific curriculum requirements are not yet 
        available for this year.  
        To advise this student according to {$settings["current_catalog_year"]}-" . ($settings["current_catalog_year"] + 1) . "
        requirements, select the student's major using What If.
      </td>
      </table>
      </div>
      
    "; 
    }




    $rtn .= "</td></tr>";

    
    $rtn .= "</table>";
    
    return $rtn;
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
  
  $dname = $user->name;
  if ($user->f_name != "" || $user->l_name != "") {
    $dname = trim($user->f_name . " " . $user->l_name) . " ($user->name)";
  }
  
  $rtn .= "<div class='flightpath-greeting-message'>
              Welcome $dname! Today is $today. " . l("Log out?", "logout") . "
          </div>";
  
  return $rtn;  
  
}



/**
 * Similar to render_tab_array.
 */
function fp_render_sub_tab_array($tab_array) {
  global $current_student_id;
  
  $rtn = "";
  
  $rtn .= "<div class='sub-tabs'>";

  foreach ($tab_array as $tab) {
    $title = $tab["title"];
    $on_click = $tab["on_click"];
    $active = $tab["active"];
    $type = $tab["type"];
    
    $extra_class = "";
    if ($active) $extra_class = "gradbutton-active";
    
    if ($type == "link" ) {
      // Render as a standard link
      $rtn .= "<a href='javascript: $on_click' class='fp-sub-tab-link $extra_class'>$title</a>";
    }
    else {
      // Render as a button
      $rtn .= fp_render_button($title, $on_click, true, "", $extra_class);
    }
    
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
  
  if ($bool_padd) {
    $padd = "&nbsp;&nbsp;";
  }


  $rtn = "<span class='gradbutton hand $extra_class' onClick='$on_click' $on_mouse $style >
      $padd $title $padd
      </span>

    ";
  return $rtn;
}


function fp_render_mobile_tab_array($tab_array) {
    
    $rtn = "";
    
    $js_vars = "var mobileTabSelections = new Array(); ";

    if (count($tab_array) <= 1) return "";
    
    
    $rtn .= "<table border='0' width='200' cellpadding='0' cellspacing='0' class='fp-mobile-tabs'>
             <td>
             <b>" . t("Display") . ": </b>";
    
    
    $rtn .= "<select onChange='executeSelection()' id='mobileTabsSelect'>";
    
    for ($t = 0; $t < count($tab_array); $t++)
    {
      $title = $tab_array[$t]["title"];
      $active = $tab_array[$t]["active"];
      $on_click = $tab_array[$t]["on_click"];

      if ($title == "")
      {
        continue;
      }
      $sel = ($active == true) ? $sel = "selected":"";
      
      $rtn .= "<option $sel value='$t'>$title</option>";
            
      $js_vars .= "mobileTabSelections[$t] = '$on_click'; \n";
      
    }   
    
    $rtn .= "</select>
              </td></table>";
            
    
    $rtn .= '
      <script type="text/javascript">
      ' . $js_vars . '      
      
      function executeSelection() {
        var sel = document.getElementById("mobileTabsSelect").value;
        
        var statement = mobileTabSelections[sel];
        // Lets execute the statement...
        eval(statement);
        
      }
      
      
      </script>
    ';
    
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

  // fix mobile tabs
  if (fp_screen_is_mobile()) {
    return fp_render_mobile_tab_array($tab_array);         
  }
    
  
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




function display_not_found() {
  
  $page = array(
    "title" => t("Not Found"),
    "content" => "<h2>" . t("Page not found") . "</h2>" . t("Sorry, the requested page could not be found."),
  );
  
  fp_display_page($page);
}


function display_access_denied() {
  $page = array(
    "title" => t("Access Denied"),
    "content" => "<h2>" . t("Access Denied") . "</h2>" . t("Sorry, but you do not have access to the requested page or function."),
  );
  
  fp_display_page($page);  
}



/**
 * Return the theme location
 */
function fp_theme_location($bool_include_base_path = TRUE) {
  
  $p = $GLOBALS["fp_system_settings"]["theme"];
  
  // The theme hasn't been set for some reason
  if ($p == "") {
    $p = "themes/classic";
  }
  
  if ($bool_include_base_path) {
    $p = base_path() . "/" . $p;    
  }
  
  return $p;
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



/**
 * Will draw a string in a pretty square box.  Used for displaying semester
 * titles.
 *
 * @param string $title
 * @return string
 */
function fp_render_square_line($text) {
  
  $rtn .= "
        <table border='0' width='100%' cellpadding='0' cellspacing='0'>
       <tr>
        <td width='10%' align='left' valign='top'></td>
        <td width='80%' align='center' rowspan='2'>
         <span class='tenpt' style='color: white' ><b>$text</b></span>
        </td>
        <td width='10%' align='right' valign='top'></td>
       </tr>
       <tr>
        <td align='left' valign='bottom'></td>
        <td align='right' valign='bottom'></td>
       </tr> 
      </table>     
    
      ";

  return $rtn;

}


function fp_render_menu_item($item, $bool_check_user_access = TRUE) {
  $rtn = "";
  
  if ($bool_check_user_access) {
    if (!menu_check_user_access($item)) {
      return "";
    }
  }
  
  $description = $item["description"];
  $title = $item["title"];
  //$url = $GLOBALS["fp_system_settings"]["base_path"] . "/" . $item["path"];
  $url = fp_url($item["path"], "", TRUE);
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




function pretty_print($var) {
  print "<pre>" . print_r($var, true) . "</pre>";
}


