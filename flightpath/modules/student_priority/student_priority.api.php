<?php

/**
 * This file defines the hooks we recognize from this module.
*/



/**
 * Return back an array of the calculation tests we offer.
 */
function hook_define_calculation_tests() {
  
  $rtn = array();
  
  $rtn['my_module__something_test'] = array(   // the key is also the callback function to call!  By convention, use module name, 2 underscores, then test name.
  
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
  
  
  return $rtn;
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




