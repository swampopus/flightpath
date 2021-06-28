<?php

/**
 * This file defines the hooks we recognize from this module.
*/



/**
 * Lets us modify an array of calculation tests, either adding to it, or altering the existing ones.
 * Notice that $arr is passed by reference.
 */
function hook_define_calculation_tests(&$arr) {
   
  
  $arr['my_module__something_test'] = array(   // the key is also the callback function to call!  By convention, use module name, 2 underscores, then test name.
  
    'title' => 'This is the title of this test.',
    'description' => 'This is a description of this test.',
    
    'result_scores' => array(  // Based on the returned 'result' value, what "score" should we use?  The default can be overridden in the UI.
      'Y' => 0,
      'N' => 0.5
    ),
    
    
    'file' => array('my_module', 'my_modules.calculations.inc'),  // module name first, the file next (assuming it is within a module folder.        
    'group' => 'Custom Group A',  // groups multiple tests together this way    
    'weight' => 1010,   // lighter weights float to the top (get evaluated first).  By convention, Jetstream's core test weights are at 0 - 999, and other tests should start at 1000.
    
  );
  
  
  // No need to return anything, since $arr is passed by reference.
  
}




/**
 * 
 *  This is an example of what a test should look like. Notice it always accepts the $student_node as an argument. 
 * 
 * 
 */
function example_mymodule__does_student_work_on_campus($student_node) {
  
  $rtn = array();
  
  // For this test, we will simply examine the student's works_on_campus field, and return
  // the appropriate result.  
  
  if ($student_node->field_works_on_campus['und'][0]['value'] == 'yes') {
    $rtn['result'] = 'Y';    
  }
  else {
    $rtn['result'] = 'N';
  }
  
  $rtn['extra'] = 'Extra content goes here.';
  
  
  
  return $rtn;
  
} 




