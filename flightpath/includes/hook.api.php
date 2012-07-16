 <?php
 
 /**
  * @file
  * Lists all available hooks within FlightPath's core code.
  * 
  * This script is not meant to be included and used in FlightPath,
  * it is simply for documentation of how the various hooks work.
  * 
  * In each example, you may use a hook by replacing the word "hook"
  * with the name of your custom module.
  * 
  * For example, hook_menu() might be my_module_menu().  FlightPath
  * will automatically begin using the hooks in your module, once the
  * module is enabled.
 */
 
 
 

function hook_form_alter(&$form, $form_id) {
  if ($form_id == "example_form_callback") {
    $form["new_element"] = array(
      // ...
    );
  }
}
 