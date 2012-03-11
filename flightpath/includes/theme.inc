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
  $screen->page_is_popup = $page["router_item"]["page_settings"]["page_is_popup"];
  
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
  
  if ($page["router_item"]["page_settings"]["display_greeting"] == TRUE) {  
    $content_top .= fp_render_greeting();
  }
  
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

  // content_top gets the Currently Advising box.
  if ($page["router_item"]["page_settings"]["display_currently_advising"] == TRUE) {
    $content_top .= fp_render_currently_advising_box();
  }    
  
  $screen->page_content = $content_top .= $page["content"];
  // If there are CSS files to add, add those.
    
  
  $screen->output_to_browser();  
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
    $title = $item["title"];
    $path = $item["path"];
    $active = FALSE;
    $on_click = $page["page_settings"]["tab_on_click"];
    if ($on_click == "") {
      // Just a simple link to the path
      // Add the current_student_id to the query!
      $query = "&current_student_id=$current_student_id";
      $on_click = "window.location=\"" . base_path() . "/" . $path . $query . "\"";
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
        // Include the current_student_id!
        $query = "&current_student_id=$current_student_id";
        
        $on_click = "window.location=\"" . base_path() . "/" . $path . $query . "\"";
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
    global $current_student_id;
    
    $rtn  = "";
    
    $csid = $current_student_id;
    
    $student = new Student();
    $student->student_id = $csid;
    $student->load_student_data();
          
    $settings = fp_get_system_settings();
          
    
    $rtn .= "<table class='fp-currently-advising'>";
  
    
    $for_term = $whatif = $what_if_select = $hypoclass = "";
    if ($GLOBALS["fp_advising"]["advising_term_id"] != "" && $screen_mode != "not_advising"
    && user_has_permission("can_advise_students"))
    {
      $temp_course = new Course();
      $temp_course->term_id = $GLOBALS["fp_advising"]["advising_term_id"];
      $t_term_id = $GLOBALS["fp_advising"]["advising_term_id"];
      $for_term = " for " . $temp_course->get_term_description();

      // If this is an advisor or above
      $for_term .= "<span style='font-size: 8pt; font-weight:normal;'>
             - <a href='javascript: popupWindow(\"changeTerm\",\"advising_term_id=$t_term_id\");' style='color:blue; background-color: white; border: 1px solid black; padding-left: 3px; padding-right: 3px;'>change<img src='fp_theme_location()/images/calendar1.jpg' height='13' border='0' style='vertical-align: bottom;'></a>
            </span>";


    }

    // TODO:  bool_blank? bool_print?
    if ($GLOBALS["fp_advising"]["advising_what_if"] == "yes" && !$bool_blank)
    {
      $whatif = " (in \"What If\" mode) ";
      $hypoclass = "hypo";
      // latest cat year because its what if.
      $student->catalog_year = $settings["current_catalog_year"];
      if ($bool_print != true) {
        $what_if_select = "<div class='tenpt'><b>
          <a href='advise.php?advising_what_if=yes&what_if_major_code=none&what_if_track_code=none&current_student_id=$csid'>Change What If Settings</a></b></div>";
      }
    }

    $ca = "Currently Advising";
    // TODO:  should be based on roles instead of type
    if ($_SESSION["fp_user_type"] == "student" || $_SESSION["fp_user_type"] == "viewer")
    {
      $ca = "Student Details";
    }

    if ($bool_blank == true) {
      $ca = "Viewing Blank Degree Plan";
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
      array_push($display_array, "Name: ~~ " . $student->name);
      array_push($display_array, "CWID: ~~ " . $student->student_id);
    }
    array_push($display_array, "Major: ~~ " . $degree_title);
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
      
      $op_link = "<a href='javascript: popupWindow(\"changeTrack\",\"$extra_vars\");'><img
              src='fp_theme_location()/images/popup.gif' border='0' 
              title='Click to change degree options.'></a>";
      $op_text = "Click to select: $op_link";

      if ($screen_mode == "not_advising")
      {
        $op_text = "None selected";
        $op_link = "";
      }

      if (!user_has_permission("can_advise_students")) {
        
        if ($GLOBALS["fp_advising"]["advising_what_if"] != "yes")
        {
          // In other words, we do not have permission to advise,
          // and we are not in whatIf, so take out the link.
          $op_link = "";
          $op_text = "None selected";

        }
      }


      // Did has the student already selected an option?
      if ($degree_plan->track_code != "")
      {
        $op_text = $degree_plan->track_title . " $op_link";
      }


      array_push($display_array, "Option: ~~ " . $op_text);
    }
    if (!$bool_blank) {
      array_push($display_array, "Rank: ~~ " . $student->rank);
    }
    array_push($display_array, "Catalog Year: ~~ " . $cat_year);
    if (!$bool_blank) {
      array_push($display_array, "Cumulative: ~~ " . $student->cumulative_hours . " hrs. &nbsp;" . $student->gpa . " GPA");
    }

    if ($student->student_id > 1 || $bool_blank == true)
    { // Make sure we have selected a student! (or are viewing a blank plan)
      // Now, go through the array and display it.
      for ($t = 0; $t < count($display_array); $t = $t + 2)
      {
        $temp = split(" ~~ ",$display_array[$t]);
        $name1 = trim($temp[0]);
        $value1 = trim($temp[1]);


        $temp = split(" ~~ ",$display_array[$t+1]);
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
        <img src='fp_theme_location()/images/alert_lg.gif' >  
      </td>
      <td valign='middle' class='tenpt' style='padding-left: 8px;'>
        <b>Important Notice: </b>
        FlightPath cannot display degree plans from 
        catalogs earlier than " . $settings["earliest_catalog_year"] . "-" . ($settings["earliest_catalog_year"] + 1) . ".  
        The above student's catalog year is $cat_year, which means 
        that the degree plan below may not accurately 
        display this student's degree requirements.
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
        <img src='fp_theme_location()/images/alert_lg.gif' >  
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
  
  $rtn .= "<div class='flightpath-greeting-message'>
              Welcome $user->name! Today is $today. " . l("Log out?", "logout") . "
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



/**
 * Will draw a string in a pretty square box.  Used for displaying semester
 * titles.
 *
 * @param string $title
 * @return string
 */
function fp_render_square_line($text) {
  
  $pC .= "
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




function pretty_print($var) {
  print "<pre>" . print_r($var, true) . "</pre>";
}


