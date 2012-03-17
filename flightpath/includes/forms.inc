<?php
/*
* This include file contains functions pertaining to the
* creation of forms through FlightPath's form API 
*/


/**
 * Render the form array from the callback to the screen, and
 * set the form to save itself in our default submit handler.
 */
function fp_system_settings_form($callback) {
  global $current_student_id, $user;
  $rtn = "";
  
  $form = call_user_func($callback);
  
  // Figure out the current page's title and display it.
  $path = $_GET["q"];
  $menu_item = menu_get_item($path);
  $rtn .= "<h2 class='title'>" . $menu_item["title"] . "</h2>";
    
  if ($form["redirect"]) {
    $path = $form["redirect"];
  }
    
  $rtn .= "<form action='" . base_path() . "/system-handle-form-submit' method='POST' id='sysform' class='fp-system-form fp-form fp-form-$callback'>";
  
  $rtn .= "<input type='hidden' name='callback' value='$callback'>";
  $rtn .= "<input type='hidden' name='redirect' value='$path'>";
  $rtn .= "<input type='hidden' name='current_student_id' value='$current_student_id'>";
  
  foreach ($form as $name => $element) {
    if (isset($element["type"])) {
      $rtn .= fp_render_form_element($name, $element);
    }    
  }
      
      
  $rtn .= "<div class='buttons'>";
  $rtn .= fp_render_button("Save settings", "\$(\"#sysform\").submit()");
  $rtn .= "</div>";
      
  $rtn .= "</form>";
      
      
      
  return $rtn;
  
}


/**
 * Returns the HTML to render this form element to the screen.
 * $name is the HTML machine name.  $element is an array containing all we need to render it.
 */
function fp_render_form_element($name, $element) {
  $rtn = "";

  $type = $element["type"];
  $value = $element["value"];
  $label = $element["label"];
  
  $rtn .= "<div id='element-wrapper-$name' class='element-type-$type'>";

  // First of all, what is it's "type"?
  if ($type == "markup") {
    $rtn .= "<div class='markup'>$value</div>";
  }
  else {
    $rtn .= "<label>$label</label>";
  }

  if ($type == "textarea") {
    $rows = ($element["rows"]) ? $element["rows"] : "5";
    $rtn .= "<textarea name='$name' id='element-$name' rows='$rows'>$value</textarea>";
  }


  $rtn .= "</div>";



  return $rtn;
}