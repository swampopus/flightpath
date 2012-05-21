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
  $default_path = $path;
  // Figure out the "default_query" from $_GET
  $new_query = array();
  foreach ($_GET as $key => $val) {
    if ($key != "q") {
      $new_query[] = "$key=$val"; 
    }
  }
  if (count($new_query)) {
    $default_query = join("&", $new_query);
  }
  
  $page_title = $GLOBALS["fp_current_menu_router_item"]["title"];
  if (isset($GLOBALS["fp_set_title"])) {
     $page_title = $GLOBALS["fp_set_title"];
  }
  
  if ($page_title != "") {
    $rtn .= "<h2 class='title'>" . $page_title . "</h2>";
  }
    
/*  if (is_array($form["redirect"])) {
    $redirect_path = $form["redirect"]["path"];
    $redirect_query = $form["redirect"]["query"];
  }
 */
    
  //fpm($GLOBALS["fp_current_menu_router_item"]);
  $form_path = $GLOBALS["fp_current_menu_router_item"]["path"];
    
  // Are there any files required to get to the submit handler for this form?
  $form_include = "";  
  // Set the form_include to the current page's "file" requirement, if any.
  if (is_array($GLOBALS["fp_current_menu_router_item"])) {
    if (isset($GLOBALS["fp_current_menu_router_item"]["file"])) {
      $form_include = $GLOBALS["fp_current_menu_router_item"]["file"];
    }
  }  
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
  $rtn .= "<input type='hidden' name='form_path' value='$form_path'>";
  $rtn .= "<input type='hidden' name='form_include' value='$form_include'>";
  $rtn .= "<input type='hidden' name='default_redirect_path' value='$default_path'>";
  $rtn .= "<input type='hidden' name='default_redirect_query' value='$default_query'>";
  //$rtn .= "<input type='hidden' name='redirect_query' value='$redirect_query'>";
  $rtn .= "<input type='hidden' name='current_student_id' value='$current_student_id'>";
  
  $use_callback = "";
  if (form_has_errors()) {
    // We will only pull previous POST's values if there are errors on the form.
    $use_callback = $callback;
  }
  
  foreach ($form as $name => $element) {
    if (is_array($element) && (isset($element["type"]) || isset($element["value"]))) {
      $rtn .= fp_render_form_element($name, $element, $use_callback);
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
  if ($type == "") $type = "markup";
  
  $value = $element["value"];
  $label = $element["label"];
  $options = $element["options"];
  $description = $element["description"];
  $popup_description = $element["popup_description"];
  $prefix = $element["prefix"];
  $suffix = $element["suffix"];
  $required = $element["required"];
  $no_please_select = $element["no_please_select"];
  
  $attributes = $element["attributes"];


  if (is_array($attributes)) {
    // Convert the attributes array into a string.
    $new_attr = "";
    foreach ($attributes as $key => $val) {
      $new_attr .= " $key='$val' ";
    }
    $attributes = $new_attr;
  }

  $popup_help_link = "";
  if ($popup_description) {
    // Cheap hack to get rid of whitespace    
    for ($t = 0; $t < 5; $t++) {
      $popup_description = str_replace("  ", " ", $popup_description);
    }
    
    $popup_description = str_replace("\n", "[NL]", $popup_description);
    $popup_description = htmlentities($popup_description, ENT_QUOTES);
    $popup_description = str_replace("&quot;", "\&quot;", $popup_description);
    $popup_description = str_replace("[NL]", "\\n", $popup_description);
    
    
    
    $popup_help_link = " <a href='javascript: alert(\"" . $popup_description . "\");' class='form-popup-description'>[?]</a>";
  }

  $element_extra_css = "";
  if (is_array($_SESSION["fp_form_errors"])) {
    foreach ($_SESSION["fp_form_errors"] as $err) {
      if ($err["name"] == $name) {
        // There is an error on this element!  Add an extra CSS element.
        $element_extra_css .= "form-element-error";
      }
    }
  }

  if ($use_session_submission_values_for_callback && is_array($_SESSION["fp_form_submissions"][$use_session_submission_values_for_callback]["values"])) {
    // Check the SESSION for a previous value which we should use.
    $ignore_types = array("hidden", "markup", "submit");
    if (!in_array($type, $ignore_types)) {
      $value = $_SESSION["fp_form_submissions"][$use_session_submission_values_for_callback]["values"][$name];
    }    
  }
  
  
  $rtn .= "<div id='element-wrapper-$name' class='form-element element-type-$type $element_extra_css'>";

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
  else if ($type != "hidden") {
    $rtn .= "<label>$ast$label $popup_help_link</label>";
  }

  if ($type == "textarea") {
    $rows = ($element["rows"]) ? $element["rows"] : "5";
    $rtn .= "<textarea name='$name' id='element-$name' rows='$rows' $attributes>$value</textarea>";
  }

  if ($type == "textfield" || $type == "password") {
    if ($type == "textfield") $type = "text";
    $size = ($element["size"]) ? $element["size"] : "60";
    $maxlength = ($element["maxlength"]) ? $element["maxlength"] : "255";
    $value = htmlentities($value, ENT_QUOTES);    
    $rtn .= "<input type='$type' name='$name' id='element-$name' size='$size' maxlength='$maxlength' value='$value' $attributes>";
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
                 <label class='label-for-radio'><input type='radio' name='$name' id='element-$name-$key' value='$key' $checked $attributes> $val</label>
               </div>";
    }
    $rtn .= "</div>";
  }

  if ($type == "select") {
    $rtn .= "<select name='$name' id='element-$name' $attributes>";
    if ($no_please_select != TRUE) {
      $rtn .= "<option value=''>- Please select -</option>";
    }
    
    foreach ($options as $key => $val) {
      $selected = "";
      if ($value == $key) {
        $selected = "selected";
      }
      $rtn .= "<option value='$key' $selected>$val</option>";
    }
    $rtn .= "</select>";
  }



  if ($type == "checkboxes") {
    $rtn .= "<div class='form-checkboxes form-checkboxes-$name'>";
    foreach ($options as $key => $val) {
      $checked = "";
      if (is_array($value) && $value[$key] == $key) {
        
        $checked = "checked=checked";
      }
      $rtn .= "<div class='checkbox-element checkbox-element-$key'>
                 <label class='label-for-checkbox'><input type='checkbox' name='$name" . "[$key]' id='element-$name-$key' value='$key' $checked $attributes> $val</label>
               </div>";
    }
    $rtn .= "</div>";
  }



  if ($type == "submit") {
    $rtn .= "<input type='submit' value='$value' $attributes>";
  }
  
  if ($type == "button") {
    $rtn .= "<input type='button' value='$value' $attributes>";    
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