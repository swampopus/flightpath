<?php
/*
* This include file contains functions pertaining to the
* creation of forms through FlightPath's form API 
*/



/**
 * Render the form array from the callback to the screen, and
 * set the form to save itself in our default submit handler.
 * Valid form_types are:
 * "system_settings" => values automatically saved to variables table.
 * "normal" or BLANK => values are forwarded to $callback_submit() function, if it exists.
*/
function fp_render_form($callback, $form_type = "") {
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
    
  $form_include = "";
  if ($form["form_include"]) {
    $form_include = $form["form_include"];
  }
    
  $extra_form_class = "";
  if ($form_type == "system_settings") {
    $extra_form_class = "fp-system-form";
  }
    
  $rtn .= "<form action='" . base_path() . "/system-handle-form-submit' method='POST' id='sysform' class='$extra_form_class fp-form fp-form-$callback'>";
  
  $rtn .= "<input type='hidden' name='callback' value='$callback'>";
  $rtn .= "<input type='hidden' name='form_type' value='$form_type'>";
  $rtn .= "<input type='hidden' name='form_include' value='$form_include'>";
  $rtn .= "<input type='hidden' name='redirect' value='$path'>";
  $rtn .= "<input type='hidden' name='current_student_id' value='$current_student_id'>";
  
  foreach ($form as $name => $element) {
    if (is_array($element) && isset($element["type"])) {
      $rtn .= fp_render_form_element($name, $element, $callback);
    }    
  }
      

  // If this is a system settings form, go ahead and display the save button.
  if ($form_type == "system_settings") {      
    $rtn .= "<div class='buttons'>";
    $rtn .= fp_render_button("Save settings", "\$(\"#sysform\").submit()");
    $rtn .= "</div>";      
  }
  
  
  $rtn .= "</form>";
      

  // Clear any existing form errors and values
  unset($_SESSION["fp_form_errors"]);
  clear_session_form_values($callback);
      
  return $rtn;
  
}


/**
 * Clear the form submissions variable from the SESSION for this callback.
 */
function clear_session_form_values($callback) {
  unset($_SESSION["fp_form_submissions"][$callback]);  
}



/**
 * This is a very basic valiator for form API submission.
 * All I really care about is making sure required fields have
 * a value in them.  If they do not, we will file a form_error.
 */
function form_basic_validate($form, $form_submitted) {
  
  foreach ($form as $name => $element) {
    if (is_array($element) && $element["required"]) {
      // Okay, this is a required field.  So, check that it has a non-blank value
      // in form_submitted.
      if ($form_submitted["values"][$name] == "") {
        // It's blank!  ERROR!
        $label = $element["label"];
        if ($label == "") $label = $name;
        form_error($name, t("You must enter a value for <b>%element_label</b>", array("%element_label" => $label)));
      }
    }
  }
  
}


/**
 * Register a form_error in the SESSION.
 */
function form_error($element_name, $message) {
  
  $_SESSION["fp_form_errors"][] = array("name" => $element_name, "msg" => $message);
  fp_add_message($message, "error");
  
}


/**
 * Returns TRUE or FALSE if there have been errors for this form submission
 * (We will just look in the SESSION to find out).
 */
function form_has_errors() {
  if (count($_SESSION["fp_form_errors"]) > 0) {
    return TRUE;
  }
  
  return FALSE;
}



/**
 * Returns the HTML to render this form element to the screen.
 * $name is the HTML machine name.  $element is an array containing all we need to render it.
 * If you want default values to be taken from the SESSION (because we had form_errors, say, and we
 * want values to keep what we had between submissions) specify the callback to use in the
 * use_session_submission_values_for_callback variable.
 */
function fp_render_form_element($name, $element, $use_session_submission_values_for_callback = "") {
  $rtn = "";

  $type = $element["type"];
  $value = $element["value"];
  $label = $element["label"];
  $options = $element["options"];
  $description = $element["description"];
  $prefix = $element["prefix"];
  $suffix = $element["suffix"];
  $required = $element["required"];

  if ($use_session_submission_values_for_callback) {
    // Check the SESSION for a previous value which we should use.
    $ignore_types = array("hidden", "markup", "submit");
    if (!in_array($type, $ignore_types)) {
      $value = $_SESSION["fp_form_submissions"][$use_session_submission_values_for_callback]["values"][$name];
    }    
  }
  
  
  $rtn .= "<div id='element-wrapper-$name' class='form-element element-type-$type'>";

  if ($prefix) {
    $rtn .= $prefix;
  }

  $ast = "";
  if ($required) {
    $ast = "<span class='form-required-ast'>*</span>";
  }

  // First of all, what is it's "type"?
  if ($type == "markup") {
    $rtn .= "<div class='markup'>$value</div>";
  }
  else {
    $rtn .= "<label>$ast$label</label>";
  }

  if ($type == "textarea") {
    $rows = ($element["rows"]) ? $element["rows"] : "5";
    $rtn .= "<textarea name='$name' id='element-$name' rows='$rows'>$value</textarea>";
  }

  if ($type == "textfield") {
    $size = ($element["size"]) ? $element["size"] : "60";
    $maxlength = ($element["maxlength"]) ? $element["maxlength"] : "255";
    $value = htmlentities($value, ENT_QUOTES);    
    $rtn .= "<input type='text' name='$name' id='element-$name' size='$size' maxlength='$maxlength' value='$value'>";
  }

  if ($type == "hidden") {
    $value = htmlentities($value, ENT_QUOTES);    
    $rtn .= "<input type='hidden' name='$name' id='element-$name' value='$value'>";
  }

  if ($type == "radios") {
    $rtn .= "<div class='form-radios form-radios-$name'>";
    foreach ($options as $key => $val) {
      $checked = "";
      if ($value == $key) {
        $checked = "checked=checked";
      }
      $rtn .= "<div class='radio-element radio-element-$key'>
                 <label class='label-for-radio'><input type='radio' name='$name' id='element-$name-$key' value='$key' $checked> $val</label>
               </div>";
    }
    $rtn .= "</div>";
  }

  if ($type == "submit") {
    $rtn .= "<input type='submit' value='$value'>";
  }



  if ($description) {
    $rtn .= "<div class='form-element-description'>$description</div>";
  }


  if ($suffix) {
    $rtn .= $suffix;
  }

  $rtn .= "</div>";



  return $rtn;
}