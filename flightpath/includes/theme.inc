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
function format_date($timestamp = 0, $format = "standard", $custom = "") {
  
  if (!is_numeric($timestamp)) {
    $timestamp = 0;  // make sure it's numeric.
  }
  
  if ($timestamp == 0) {
    $timestamp = strtotime("now");
  }
  
  
  $f = "";  
  if ($format == "standard") {
    $f = "n/d/Y h:i:sa";
  }
  
  if ($format == "short") {
    $f = "n/d/Y - g:ia";
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
    $rtn .= fp_render_section_title($title);
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
  if (@$GLOBALS["fp_set_title"] != "") {
    $page_title = $GLOBALS["fp_set_title"];
  }

  if (!is_object($screen)) {
    $screen = new AdvisingScreen("",null,"not_advising");
  }
  $screen->page_title = $page_title;
  $screen->page_has_search = @$page["router_item"]["page_settings"]["page_has_search"];

  // Add some body classes to the page for this student.
  $student = $screen->student;
  if (!is_object($student)) {
    $student = new Student(); 
    $student->student_id = $current_student_id;
    $student->load_student_data();
  }
  if (is_object($student)) {
    fp_add_body_class("student-rank-$student->db_rank  student-catalog-year-$student->catalog_year");
  }  
 
  
  $screen->page_banner_is_link = @$page["router_item"]["page_settings"]["page_banner_is_link"];
  $screen->page_hide_report_error = @$page["router_item"]["page_settings"]["page_hide_report_error"];
  $screen->page_is_popup = @$page["router_item"]["page_settings"]["page_is_popup"];
  if (isset($page["router_item"]["page_settings"]["screen_mode"])) {    
    $screen->screen_mode = $page["router_item"]["page_settings"]["screen_mode"];
  }
  if (isset($page["router_item"]["page_settings"]["bool_print"])) {
    $screen->bool_print = $page["router_item"]["page_settings"]["bool_print"];
  }

  if (@$_REQUEST["scroll_top"] != "") {
    $screen->page_scroll_top = $_REQUEST["scroll_top"];    
  }
  
  // If there is a SESSION var for scroll_top, use that instead, then wipe it.
  if (@$_SESSION['scroll_top'] != "") {
    $screen->page_scroll_top = $_SESSION["scroll_top"];
    $_SESSION['scroll_top'] = "";
    unset($_SESSION['scroll_top']);
  }
  
  // Was this page a tab?  And if so, are there any other tabs we need to display? //
  if (@$page["router_item"]["type"] == MENU_TYPE_TAB || @$page["router_item"]["tab_parent"] != "") {
    // We know we should have at least 1 tab for this page.
    $tab_array = fp_build_tab_array($page);        
    $screen->page_tabs = fp_render_tab_array($tab_array);    
  }
  
  // Should we override the page_tabs with what is in "fp_set_page_tabs" ?
  if (isset($GLOBALS["fp_set_page_tabs"]) && is_array($GLOBALS["fp_set_page_tabs"])) {
    //fpm($GLOBALS["fp_set_page_tabs"]);
    $screen->page_tabs = fp_render_tab_array($GLOBALS["fp_set_page_tabs"]);    
  }
  
  
  // Build up the content //
  
  $content_top = "";  
  $page_show_title = @$page["router_item"]["page_settings"]["page_show_title"];
  if (isset($GLOBALS["fp_set_show_title"])) {
    $page_show_title = $GLOBALS["fp_set_show_title"];
  }
  
  if ($page_show_title == FALSE) {
    //$content_top .= "<h2 class='title'>" . $page_title . "</h2>";
    $page_title = "";
  } 
    
  
  $c = 0;
  
  // Are there any "menu_link"s for this?  In order words, little links at the top of the page,
  // where breadcrumbs might appear.
  if (!$screen->bool_print && @is_array($page["router_item"]["page_settings"]["menu_links"])) {
    $content_top .= "<ul class='top-menu-links'>";
    foreach ($page["router_item"]["page_settings"]["menu_links"] as $item) {
      $lclass = "";
      if ($c == 0) $lclass='first';
      
      $p = menu_convert_replacement_pattern(@$item["path"]);
      $q = menu_convert_replacement_pattern(@$item["query"]);
      $t = @$item["text"];
      $a = @$item["attributes"];
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
    if (@$page["router_item"]["type"] == MENU_TYPE_SUB_TAB) {
      $sub_tab_array = fp_build_sub_tab_array($page);
      $content_top .= fp_render_sub_tab_array($sub_tab_array);
    }
    
    // Should we override the page sub-tabs with what is in "fp_set_page_sub_tabs" ?
    if (isset($GLOBALS["fp_set_page_sub_tabs"]) && is_array($GLOBALS["fp_set_page_sub_tabs"])) {
      $content_top .= fp_render_sub_tab_array($GLOBALS["fp_set_page_sub_tabs"]);    
    }
  }

      
  // If there are "messages" waiting, then let's add them to the top of content.
  if (@count($_SESSION["fp_messages"]) > 0) {
    $content_top .= "<div class='fp-messages'>";
    foreach ($_SESSION["fp_messages"] as $key => $tmsg) {
      $type = $tmsg["type"];
      $message = $tmsg["msg"];
      $content_top .= "<div class='fp-message fp-message-$type'>$message</div>";
    }
    $content_top .= "</div>";    
    unset($_SESSION["fp_messages"]);
  }

  $content_above_tabs = "";

  // content_top gets the Currently Advising box.
  if (@$page["router_item"]["page_settings"]["display_currently_advising"] == TRUE) {
    // TODO:  this needs better logic for the new system.    
    $content_top .= fp_render_currently_advising_box(false, $screen->bool_blank, $screen->bool_print, NULL, $screen->screen_mode);
  }    
  
  $screen->page_content = $content_top .= $page["content"];
  // If there are CSS files to add, add those.
    
  
  // Add in the body classes
  $screen->page_body_classes .= " " . @$GLOBALS["fp_add_body_classes"];  
  
  // Add in our URL  (after we sanitize it appropriately)
  $class = @$_REQUEST['q'];
  $class = str_replace("'", '', $class);
  $class = str_replace('"', '', $class);
  $class = str_replace('(', '', $class);
  $class = str_replace(')', '', $class);
  $class = str_replace(';', '', $class);
  $class = str_replace('.', '', $class);
  $class = str_replace('<', '', $class);
  $class = str_replace('>', '', $class);
  $class = str_replace('/', '-', $class);
  $class = str_replace('\\', '', $class);
  $class = str_replace('#', '', $class);
  $class = str_replace('&', '', $class);  
  
  $screen->page_body_classes .= " page--" . str_replace("/", "-", $class);
  
  
  $screen->output_to_browser();  
} // fp_display_page





/**
 * Return the HTML for breadcrumbs for the current page we are on.
 */
function fp_render_breadcrumbs() {
  $rtn = "";
  
  $z_index = 20;
  
  $rtn .= "<div id='breadcrumb-inner-wrapper'>
             <ul class='breadcrumbs'>
              <li class='crumbs first' >
                <a href='" . fp_url("student-search/search") . "' style='z-index: 20;'>Students</a>
              </li>
              <li class='crumbs'>
                <a href='l' style='z-index: 19;'>Test Student (9999)</a>
              </li>
                            
            </ul>
          </div>";
  
  
  $rtn .= "<div class='clear'></div>";
  
  return $rtn;
} // fp_render_breadcrumbs



/**
 * Returns the HTML for the left sidebar content.
 */
function fp_render_sidebar_left_content() {  
  $html = "";
  
  $html .= "<ul class='sidebar-left-nav'>
              <a href='" . fp_url("<front>") . "'>
                <li class='home first'>                
                  <i class='fa fa-home'></i>
                  <div class='desc'>Dashboard</div>
                
                </li>
              </a>
              <a href='" . fp_url("student-search") . "'>
                <li class='students'>
                    <i class='fa fa-users'></i>
                    <div class='desc'>Students</div>
                </li>
              </a>
              <a href='" . fp_url("tools/course-search") . "'>
                <li class='courses'>
                  <i class='fa fa-book'></i>
                  <div class='desc'>Courses</div>                
                </li>
              </a>
              <li class='analytics'>
                <i class='fa fa-bar-chart'></i>
                <div class='desc'>Analytics</div>                
              </li>
              <a href='" . fp_url("admin-tools") . "'>
                <li class='admin-tools'>
                  <i class='fa fa-bolt'></i>
                  <div class='desc'>Admin Tools</div>
                </li>
              </a>
              
            </ul>";
  
  
  
  return $html;    
  
} // fp_render_sidebar_left_content



/**
 * Returns the HTML for the top navigation content of the screen itself.
 */
function fp_render_top_nav_content() {
  $html = "";
  
  $html .= "
    <ul class='top-nav-ul'>
      <li class='greeting first'>" . fp_render_greeting() . "</li>
      <li class='student-search'>" . student_search_render_small_search() . "</li>
      <li class='user-options last'>
        <i class='fa fa-user'></i>
        <i class='fa fa-caret-down'></i>
        
      </li>
    </ul>
  ";
  
  
  return $html;
} // fp_render_top_nav_content









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
      fs.className = 'c-fieldset-closed-$id c-fieldset-closed c-fieldset';
    }
    else {
      // Was closed.  let's open it.
      fieldset_state_$id = 1;
      
      \$(content).slideDown('medium');
      fs.className = 'c-fieldset-open-$id c-fieldset-open c-fieldset';      
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
    $on_click = @$page["page_settings"]["tab_on_click"];
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
      $on_click = @$page["page_settings"]["tab_on_click"];
      $href = "";
      if ($on_click == "") {
        // Just a simple link to the path
        // Include the current_student_id!
        $query = "current_student_id=$current_student_id";
        
        $turl = fp_url($path, $query, TRUE);
        
        //$on_click = "window.location=\"" . $turl . "\"";
        $href = $turl;
      }
            
      if ($path == $active_tab_path) {
        // This is the current page we are on.
        $active = TRUE;
        $title = $page["title"];
        $on_click = "";
        $href = "";
      }            

      $tab_array[] = array(
        "title" => $item["title"],
        "active" => $active,
        "on_click" => $on_click,
        "href" => $href,
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
    $bool_catalog_warning = FALSE;
    $bool_future_catalog_warning = FALSE;    
        
    $csid = $current_student_id;
    
    if (is_object($screen) && is_object($screen->degree_plan)) {
      $degree_plan = $screen->degree_plan;      
    }
    
    if (!isset($student) || $student == null || !is_object($student)) {
      $student = new Student($csid);
    }

    if ($degree_plan == null) {
      // Degree plan is still null.  This probably means we are NOT on an advising screen, so, let's
      // use a FlightPath object to init our degree plan, which might actually be a combination of degrees.
      
                  
      // First, we can cheat by seeing if we have anything cached for this student the last time their degree plan
      // was loaded.  Using this,
      // its much faster & memory-friendly than trying to re-init a whole new FlightPath object and DegreePlan object(s).
      $bool_got_from_simple_cache = FALSE;
      // TODO:  Check for _what_if if we are in what if mode?
      
      if (isset($_SESSION["fp_simple_degree_plan_cache_for_student"])) {
        if ($_SESSION["fp_simple_degree_plan_cache_for_student"]["cwid"] == $csid) {
          // Yes, it's for this student.  Load her up.
          $degree_plan = new DegreePlan();
          $degree_plan->degree_id = $_SESSION["fp_simple_degree_plan_cache_for_student"]["degree_id"];
          $degree_plan->combined_degree_ids_array = $_SESSION["fp_simple_degree_plan_cache_for_student"]["combined_degree_ids_array"];
          $degree_plan->is_combined_dynamic_degree_plan = $_SESSION["fp_simple_degree_plan_cache_for_student"]["is_combined_dynamic_degree_plan"];
                   
                              
          $bool_got_from_simple_cache = TRUE;
        }
      }
      
      if (!$bool_got_from_simple_cache) {
        // didn't get it from the simple cache, so load it all fresh.
        $fp = new FlightPath($student);
        $fp->init(TRUE);
        $degree_plan = $fp->degree_plan;
      }
    }

    
    
    $settings = fp_get_system_settings();
          
    
    $rtn .= "<table class='fp-currently-advising'>";
    
    $for_term = $whatif = $what_if_select = $hypoclass = "";
    
    $advising_term_id = @$GLOBALS["fp_advising"]["advising_term_id"];
    
    if ($advising_term_id != "" && ($screen_mode != "not_advising" && $screen_mode != "detailed")
    && user_has_permission("can_advise_students"))
    {
      // If this is someone who is allowed to change terms. (advisor or above)
            
      $t_term_id = $advising_term_id;
      $for_term = " " . t("for") . " ";
      $termdesc = get_term_description($t_term_id);

      
      $furl = fp_url("advise/popup-change-term");
      
      // Let's make the change-term link a little more intuitive.
      $for_term .= "<span class='currently-advising-box-change-term'><a href='javascript: popupWindow2(\"" . $furl . "\",\"advising_term_id=$t_term_id\");'>$termdesc</a></span>";
      
      
      
      /*
      $for_term .= "<span style='font-size: 8pt; font-weight:normal;'>
             - <a href='javascript: popupWindow2(\"" . $furl . "\",\"advising_term_id=$t_term_id\");' style='color:blue; background-color: white; border: 1px solid black; padding-left: 3px; padding-right: 3px;'>" . t("change") . "<img src='" . fp_theme_location() . "/images/calendar1.jpg' height='13' border='0' style='vertical-align: bottom;'></a>
            </span>";
      */

    }

    // TODO:  bool_blank? bool_print?
    if (@$GLOBALS["fp_advising"]["advising_what_if"] == "yes" && !$bool_blank)
    {
      $whatif = " (" . t("in \"What If\" mode") . ") ";
      $hypoclass = "hypo";
      
      // Set the cat year to whatever our What If cat year was, current if we can't find it.
      $what_if_catalog_year = @$GLOBALS["fp_advising"]["what_if_catalog_year"];
      if ($what_if_catalog_year != 0 && $what_if_catalog_year != "") {
        $student->catalog_year = $what_if_catalog_year;
      }
      else {
        
        // Only change to current if that is how our settings are set...
        if (variable_get("what_if_catalog_year", "current") == "current") {
          $student->catalog_year = $settings["current_catalog_year"];
        }
      }
      
      
      if ($bool_print != true) {
        $what_if_select = "<div class='tenpt'><b>
          " . l(t("Change What If Settings"), "what-if", "advising_what_if=yes&what_if_major_code=none&what_if_track_code=none&what_if_track_degree_ids=none&current_student_id=$current_student_id") . "
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
      <table border='0' width='100%'  class='' cellpadding='0' cellspacing='0' >
      <tr>
          <td colspan='4' class='' align='center' height='20'>
          " . fp_render_section_title("$ca$whatif$for_term") . "
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


    if (!$bool_blank) {
      array_push($display_array, t("Name:") . " ~~ " . $student->name);
      array_push($display_array, t("CWID:") . " ~~ " . $student->student_id);
    }


    $db = get_global_database_handler();


    ///////////////////////////
    $degree_title_div = "";
    $s = "";  // plural degrees?
    $bool_show_track_selection_link = FALSE;
    // We are trying to figure out what to display for the Degree field.  If there is only ONE degree plan
    // to worry about, then we will display it.
    if ($degree_plan == null || $degree_plan->is_combined_dynamic_degree_plan != TRUE) {
      // This is an ordinary degree plan.
      
      $use_degree_plan = $degree_plan;
      
      $degree_title = "";
      if ($degree_plan != NULL) {
        $degree_title = $degree_plan->get_title2(TRUE);
      }

      /**
       * This degree is in fact a track.
       */
      if (strstr($degree_plan->major_code, "_")) {
        $degree_title = $degree_plan->get_title2(TRUE, TRUE);
        
        if ($degree_plan->db_allow_dynamic == 0) {
          // This degree is NON-dynamic.  Meaning, if we change tracks, we should do it from the perspective
          // of the original major code, and how FP 4.x did things.
          $temp = explode("_", $degree_plan->major_code);
          $m = trim($temp[0]);
          // Remove trailing | if its there.
          $m = rtrim($m, "|");
          
          $use_degree_plan = $db->get_degree_plan($m, $degree_plan->catalog_year, TRUE);  // the original degree plan!          
        }
      }
      
      
      if ($screen_mode == "detailed") {
        $degree_title .= " ($degree_plan->major_code)";
      }

      if ($use_degree_plan != null && $use_degree_plan->get_available_tracks()) {        
        $bool_show_track_selection_link = TRUE;
        
      }
      
      $degree_title_div = "<div class='degree-title'>$degree_title</div>";
      
      //array_push($display_array, t("Degree:") . " ~~ " . $degree_title);
    
    }
    else {
      // This degree plan is made from combining several other degrees.  let's trot them out and display them as well.
      $t_degree_plan_titles = "";
      foreach ($degree_plan->combined_degree_ids_array as $t_degree_id) {
        $t_degree_plan = new DegreePlan();
        $t_degree_plan->degree_id = $t_degree_id;
        
        $t_degree_plan->load_descriptive_data();

        if ($t_degree_plan->get_available_tracks()) {
          $bool_show_track_selection_link = TRUE;
        }

        
        // Add it to our box...
        // If more than one major/minor, etc, combine them into one div
        $t_title = $t_degree_plan->get_title2(TRUE);
        $t_class = $t_degree_plan->degree_class;
        $t_class_details = fp_get_degree_classification_details($t_class);
        // If this is actually a track, let's get the track's title instead.
        if ($x = $t_degree_plan->get_track_title(TRUE)) {
          $t_title = $x;
        }
        
        if ($t_class_details["level_num"] == 3) {
          $t_title = "<span class='level-3-raquo'>&raquo;</span>" . $t_title;
        }
        
        $machine_major_code = fp_get_machine_readable($t_degree_plan->major_code);
        if ($t_degree_plan->track_code != "") {
          $machine_major_code .=  "-" . fp_get_machine_readable($t_degree_plan->track_code);
        }
        
        
        $t_degree_plan_titles .= "<div class='multi-degree-title multi-degree-class-" . $t_class . "
                                      multi-degree-class-level-" . $t_class_details["level_num"] . "
                                      multi-degree-code-$machine_major_code '> 
                                      " . $t_title . "</div>";
        $s = "s";
      }
      $degree_title_div = $t_degree_plan_titles;
      
    }

    // Now, do we want to add an option to select a track to the degree_title_div?
    if ($bool_show_track_selection_link) {
      // Are we in what_if mode?
      $advising_what_if = @$GLOBALS["fp_advising"]["advising_what_if"];
      // We want to add a link to the popup to let the user select other degree tracks.
      $op_link = "<a class='degree-op-link-change-degree-options' href='javascript: popupWindow2(\"" . fp_url("advise/popup-change-track", "advising_what_if=$advising_what_if") . "\",\"\");'
                    style='background: url(" . fp_theme_location() . "/images/popup.gif) no-repeat top left;
                            padding-left: 20px; text-decoration: none; font-size: 0.8em;'
                    >Change degree options</a>";
      
      if ($screen_mode == "not_advising" || $screen_mode == "detailed") {
        $op_link = "";
      }

      if (!user_has_permission("can_advise_students")) {
        
        if (@$GLOBALS["fp_advising"]["advising_what_if"] != "yes")
        {
          // In other words, we do not have permission to advise,
          // and we are not in whatIf, so take out the link.
          $op_link = "";
        }
      }
     
      if ($op_link) {
        $degree_title_div .= "<div class='degree-op-link' style='text-align: right;'>$op_link</div>";
      }            
    }          

    // Add our "degrees" section....
    array_push($display_array, t("Degree$s:") . " ~~ " . $degree_title_div);     
        
    
    
    
     
     
    if (!$bool_blank) {
      array_push($display_array, t("Rank:") . " ~~ " . $student->rank);
    }
    
    array_push($display_array, t("Catalog Year:") . " ~~ " . $cat_year);
    
    if (!$bool_blank) {
      array_push($display_array, t("Cumulative:") . " ~~ " . $student->cumulative_hours . " " . t("hrs") . ". &nbsp;" . fp_truncate_decimals($student->gpa, 3) . " " . t("GPA"));
    }


    // Invoke a hook, letting other modules act on the "display array".
    invoke_hook('alter_currently_advising_box', array(&$display_array));




    if ($student->student_id != "" || $bool_blank == true)
    { // Make sure we have selected a student! (or are viewing a blank plan)
      // Now, go through the array and display it.
      for ($t = 0; $t < count($display_array); $t = $t + 2)
      {
        $temp = explode(" ~~ ",$display_array[$t]);
        $name1 = trim($temp[0]);
        $value1 = trim($temp[1]);


        $temp = @explode(" ~~ ",$display_array[$t+1]);
        $name2 = @trim($temp[0]);
        $value2 = @trim($temp[1]);


        
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
    } else {
      // No student has been selected yet!
      $rtn .= "<tr height='60'>
        <td align='center' class='no-advisee-selected'> " . t("No advisee selected.") . " </td>
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
      
      
      $msg = t("This student's catalog year is @cat_year, 
        and specific curriculum requirements are not yet 
        available for this year.  
        To advise this student according to @new_cat
        requirements, select the student's major using What If.", array("@cat_year" => $cat_year, "@new_cat" => "{$settings["current_catalog_year"]}-" . ($settings["current_catalog_year"] + 1)));
      
      // If the what_if catalog year is in "student" mode, we can't tell them to use What If, cause it won't work.
      if (variable_get("what_if_catalog_year", "current") == "student") {
        $msg = t("This student's catalog year is @cat_year, 
          and specific curriculum requirements are not yet 
          available for this year.", array("@cat_year" => $cat_year));
        
      }
      
      
      
      $rtn .= "
      
      <div class='tenpt hypo' style='margin-top: 4px; padding: 2px;'>
      <table border='0' cellspacing='0' cellpadding='0'>
      <td valign='top'>
        <img src='" . fp_theme_location() . "/images/alert_lg.gif' >  
      </td>
      <td valign='middle' class='tenpt' style='padding-left: 8px;'>
        <b>" . t("Important Notice:") . " </b>
        $msg
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
  
  $today = date("D, F jS, Y", strtotime("today"));
  
  
  $dname = $user->name;
  if ($user->f_name != "" || $user->l_name != "") {
    $dname = trim($user->f_name . " " . $user->l_name) . " ($user->name)";
  }
  
  $rtn .= "<div class='flightpath-greeting-message'>
              " . t("Welcome @dname! Today is @today", array("@dname" => $dname, "@today" => $today)) . "
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
    $title = @$tab["title"];
    $on_click = @$tab["on_click"];
    $active = @$tab["active"];
    $type = @$tab["type"];
    
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
 */
function fp_render_button($title, $on_click, $extra_class = "") {
    
  // we want to add on a variable-name-friendly version of the title to extra_class.
  $extra_class .= " fp-render-button-" . fp_get_machine_readable(strtolower($title));

  $rtn = "<button class='fp-render-button $extra_class' onClick='$on_click'>$title</button>";
  
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
 *         - $tab_array[i]["href"] = This is a complete href for the link we will create.
 *         - $tab_array[i]["on_click"] = This is an onClick command for javascript.  If this is set, href will be ignored.
 * 
 * @return string
 */
function fp_render_tab_array($tab_array) {

  $rtn = "";

  if (count($tab_array) == 0) return "";

  $img_path = fp_theme_location() . "/images";

  $rtn .= "<ul class='tabs'>";

  for ($t = 0; $t < count($tab_array); $t++)
  {
    $title = @$tab_array[$t]["title"];
    $active = @$tab_array[$t]["active"];
    $on_click = @$tab_array[$t]["on_click"];
    $href = @$tab_array[$t]["href"];

    $extra_a = "";
    if ($on_click != "") {
      $href = "javascript:void(0);"; 
      $extra_a = "onclick='$on_click'";
    }
 
    if ($title == "")
    {
      continue;
    }

    $extra_class = "inactive";
    if ($active) $extra_class = "active";

    if ($t == 0) $extra_class .= " first";
    if ($t == count($tab_array) - 1) $extra_class .= " last";

    $rtn .= "<li class='tab $extra_class'><a href='$href' $extra_a>$title</a></li>";
    
  }


  $rtn .= "</ul>";
     

  return $rtn;

}




function display_not_found() {

  header("HTTP/1.0 404 Not Found", TRUE, 404);
  
  $page = array(
    "title" => t("Not Found"),
    "content" => "<h2>" . t("Page not found") . "</h2>" . t("Sorry, the requested page could not be found."),
  );
  
  fp_display_page($page);
  die;
}


function display_access_denied() {
      
  header('HTTP/1.1 403 Forbidden', TRUE, 403);  
  
  $page = array(
    "title" => t("Access Denied"),
    "content" => "<h2>" . t("Access Denied") . "</h2>" . t("Sorry, but you do not have access to the requested page or function."),
  );  
    
  fp_display_page($page);
  die;  
}



/**
 * Return the theme location
 */
function fp_theme_location($bool_include_base_path = TRUE) {
  
  $p = @$GLOBALS["fp_system_settings"]["theme"];
  
  // The theme hasn't been set for some reason
  if ($p == "") {
    $p = "themes/fp6_clean";
  }
  
  if ($bool_include_base_path) {
    $bp = base_path();
    
    // The following code is to fix a bug where we might end up with // as our location, if our base_path is simply "/", meaning,
    // the site is located on a bare domain.
    if ($bp != "/") {
      $p = base_path() . "/" . $p;
    }
    else {
      $p = "/" . $p;
    }    
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
  depricated_message();
  // Will simply draw a curved title bar containing the $title
  // as the text.

  $rtn = "
    <div class='blueTitle' style='text-align: center; border-radius: 5px; padding: 1px;'>
      <span class='tenpt'><b>$text</b></span>
    </div>
  ";


  return $rtn;

}


function fp_render_section_title($text, $extra_class = '') {
  
  $rtn = "<div class='section-box-title section-box-title-$extra_class'>$text</div>";


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
  depricated_message("render_square_line");
  $rtn = "";
  
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
  $target = @$item["page_settings"]["target"];
  $menu_icon = @$item["page_settings"]["menu_icon"];
  $extra_class = "";
  if ($menu_icon != "") {

    if (!strstr($menu_icon, 'http')) {
      $base_url = $GLOBALS['fp_system_settings']['base_url'];  
      if ($base_url == "/") $base_url = "";  // so that we don't have // as a path, because of the next line.
      
      if (substr($menu_icon, 0, 1) == '/') {
        // Already starts with a slash, so probably should just use as is.
        // take no action.
      }
      else {       
        $menu_icon = $base_url . "/" . $menu_icon;
      }          
    }    
    $icon_img = "<img src='$menu_icon' border='0' class='fpmn-icon'>";    
  } 
  else { 
    $icon_img = "<span class='fp-menu-item-no-icon'></span>";
  } 
  
  if (!$description) $extra_class = "fp-menu-item-tight";
   
  $rtn .= "<div class='fp-menu-item $extra_class'>
            <div class='fp-menu-item-link-line'>
              <a href='$url' target='$target'>$icon_img$title</a>
            </div>
            ";
  if ($description) {
    $rtn .= " <div class='fp-menu-item-description'>$description</div>";
  }
  $rtn .= "</div>";  
  
  return $rtn;
}


/**
 * Alias of pretty_print($var)
 */
function ppm($var) {
  pretty_print($var);
}

function pretty_print($var) {
  print "<pre>" . print_r($var, true) . "</pre>";
}


