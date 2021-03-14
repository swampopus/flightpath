<?php
/**
 * This file contains our actual calculation test functions.
 * 
 * See student_priority's hook_define_calculation_tests().
*/




/**
 * Does the student work on campus?
 * 
 */
function student_priority__does_student_work_on_campus($student) {
  
  $rtn = array();
  
  // For this test, we will simply examine the student's works_on_campus field, and return
  // the appropriate score.
  
  /*
  if (@$student_node->field_works_on_campus['und'][0]['value'] == 'yes') {
    $rtn['result'] = 'Y';    
  }
  else {
    $rtn['result'] = 'N';
  }
  */
  
  // TODO:  dev:  hard-code a response.
  $rtn['result'] = 'N';
  
  
  return $rtn;
  
} // student_priority__does_student_work_on_campus



function student_priority__is_student_gpa_above_250($student) {

  $rtn = array();
  
  // TODO: are we supposed to calculate the student GPA on the fly, or go by whats in the database?
  
  // For this test, we will simply examine the student's cumulative GPA
  if (floatval($student->gpa) > 2.5) {
    $rtn['result'] = 'Y';    
  }
  else {
    $rtn['result'] = 'N';
  }
  
  $rtn['extra'] = 'Current cumulative GPA is ' . floatval($student->gpa);
  
   
  
  return $rtn;

  
} // student_priority__is_student_gpa_above_250




/** 
 * Does the student have more than 2 D's or F's?
 */
function student_priority__does_student_have_more_than_2_d_or_f($student) {
  $rtn = array();  
        
  $courses = $student->list_courses_taken->array_list;
  
  
  $rtn['result'] = 'N';
  $res_count = 0;
  $extra = "";
  foreach ($courses as $course) {
    if ($course->grade == 'F' || $course->grade == 'D') {
      $res_count++;
      $extra .= "<div class='db-course-record'>$course->subject_id $course->course_num $course->grade ($course->term_id)</div>";
    }
  }
  
  if ($res_count > 2) {
    $rtn['result'] = 'Y';
    
  }
  
  
  $rtn['extra'] = $extra;
  
  return $rtn;
  
} // student_priority__does_student_have_more_than_2_d_or_f



/** 
 * Does the student have more than 2 W's?
 */
function student_priority__does_student_have_more_than_2_w($student) {
  $rtn = array();  
  
  
  $courses = $student->list_courses_taken->array_list;
  
  $rtn['result'] = 'N';
  $res_count = 0;
  $extra = "";
  foreach ($courses as $course) {
    if ($course->grade == 'W') {
      $res_count++;
      $extra .= "<div class='db-course-record'>$course->subject_id $course->course_num $course->grade ($course->term_id)</div>";
    }
  }
  
  if ($res_count > 2) {
    $rtn['result'] = 'Y';
    
  }
  
  
  $rtn['extra'] = $extra;
  
  return $rtn;
  
} // student_priority__does_student_have_more_than_2_w





/**
 * Did the student score below a B in ENGL 1001?  Returns 'N/A' if not applicable.
 */
function student_priority__did_student_score_below_b_on_engl_1001($student) {
  
  $rtn = array();
  
  // First, did the student even take ENGL 101?
  $courses = $student->list_courses_taken->array_list;
    
  $rtn['result'] = 'N/A';
  $rtn['extra'] = 'The student has not yet completed ENGL 101.';
  
  foreach ($courses as $course) {
    if ($course->subject_id == 'ENGL' && ($course->course_num == '101' || $course->course_num == '1001')) {
      if ($course->grade != 'A' && $course->grade != 'B') {
        $rtn['result'] = 'Y';        
      }
      else {
        $rtn['result'] = 'N';
      }
      
      $rtn['extra'] = "The student earned '$course->grade' in ENGL 1001 in $course->term_id.";
      
    }
  } // foreach
  
  return $rtn;
  
} // student_priority__did_student_score_below_b_on_engl_1001




/**
 * Returns 0, 1, or 2+
 */
function student_priority__how_many_times_has_student_changed_majors($student) {
  $rtn = array();
  
  
  
  //$num = intval($student_node->field_number_of_major_changes['und'][0]['value']);
  
  // TODO: dev: hard code for now.
  $num = 1;
  
  if ($num == 0) $rtn['result'] = '0';
  if ($num == 1) $rtn['result'] = '1';
  if ($num >= 2) $rtn['result'] = '2+';
  
  $rtn['extra'] = "The student has changed majors $num times.";
  
  return $rtn;
}





/**
 * If the student attended 'BadHighSchool', then we return 'Y'
 */
function student_priority__did_the_student_attend_underperforming_high_school($student) {
  $rtn = array();
  $rtn['result'] = 'N';
  
  //$school = $student_node->field_high_school['und'][0]['value'];
  
  // TODO: dev: hard-code for now
  $school = 'badhighschool';
  
  $rtn['extra'] = "The student attended $school high school.";
  
  if (strtolower(trim($school)) == 'badhighschool') {
    $rtn['result'] = 'Y';
  }
  
  return $rtn;
  
} // student_priority__did_the_student_attend_underperforming_high_school




function student_priority__does_student_have_federal_financial_aid($student) {
  $rtn = array();
  
  // TODO: dev: hard-code for now.
  /*
  if (@$student_node->field_federal_financial_aid['und'][0]['value'] == 'yes') {
    $rtn['result'] = 'Y';    
  }
  else {
    $rtn['result'] = 'N';
  }
  */
  $rtn['result'] = 'Y';
  
  return $rtn; 
  
} // student_priority__does_student_have_federal_financial_aid













