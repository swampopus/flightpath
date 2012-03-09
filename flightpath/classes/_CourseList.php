<?php
/*
FlightPath was originally designed and programmed by the 
University of Louisiana at Monroe. The original source is 
copyright (C) 2011-present by the University of Louisiana at Monroe.

FlightPath is considered "open source" under the 
GNU General Public License, version 3 or any later version. 
This covers any related files and documentation packaged with 
FlightPath. 

The license is defined in full here: http://www.gnu.org/licenses/gpl.html,
and reproduced in the LICENSE.txt file.

You may modify FlightPath's source code, but this copyright and license
notice must not be modified, and must be included with the source code.
------------------------------
*/

require_once("_obj_list.php");

class __course_list extends ObjList
{
	// This inherits most of its classes from ObjList,
	// but, it has to be able to do special functions
	// specific to Courses.  Use parent:: to access
	// a parent function within ObjList.
	// Example: parent::indexOf();
	//public $arrayCourseIDList = array();

	/**
	 * Used to cast a regular ObjList object into a CourseList.
	 *
	 * @param ObjList $obj
	 * 
	 * @return CourseList
	 */
	static public function cast(ObjList $obj)
	{ // This can be used to cast a regular ObjList
		// into a CourseList object.
		// Use the syntax:  CourseList::cast($x);
		$new_c_l = new CourseList();
		$new_c_l->array_list = $obj->array_list;
		$new_c_l->is_empty = $obj->is_empty;
		$new_c_l->reset_counter();

		return $new_c_l;

	}


	/**
	 * Give every course in the list a minimum grade.
	 *
	 * @param string $min_grade
	 */
	function assign_min_grade($min_grade)
	{
		// Go through the list and give every course the specified
		// min grade.

		for ($t = 0; $t < $this->count; $t++)
		{
			$course = $this->array_list[$t];
			$course->min_grade = $min_grade;
		}


	}


	/**
	 * Go through the list and find any course whose hours are greater
	 * than $hours.  Make that course "unselectable."  Used in the groups.
	 * 
	 * For example, if a student may only select 3 hours from a group, we don't
	 * want to give them the option of selecting a 5 hour course.  But we also
	 * don't want to remove that course either.  We want to display it so they
	 * know it was an option (and possibly need to substitute or move things
	 * around if they need it).
	 *
	 * Returns TRUE if anything got assigned, FALSE if nothing got assigned.
	 * 
	 * @param int $hours
	 * 
	 * @return bool
	 */
	function assign_unselectable_courses_with_hours_greater_than($hours)
	{
		// Go through the list and assign bool_unselectable courses whose minHour
		// is greater than $hours.
		// Returns TRUE if it did assign something,
		// false if it didn't.

		$bool_assigned = false;

		for ($t = 0; $t < $this->count; $t++)
		{
			$course = $this->array_list[$t];
			if ($course->subject_id == "")
			{
				$course->load_descriptive_data();
			}
			
			if ($course->min_hours > $hours)
			{
				$course->bool_unselectable = true;
				$bool_assigned = true;
			}
		}

		return $bool_assigned;

	}

	/**
	 * Find and return a specific course from the list.
	 *
	 * @param int $course_id
	 *       - The course_id to look for.  Do not set if using
	 *         $use_course.
	 * 
	 * @param int $term_id
	 *       - The term_id for the course to look for.  Do not set if using
	 *         $use_course.
	 * 
	 * @param bool $bool_transfer
	 *       - Is the course we are looking for a transfer course?  Do not
	 *         use if using $use_course.
	 * 
	 * @param bool $bool_exclude_substitutions
	 *       - If TRUE, we will not consider courses which have been used
	 *         in a substitution.
	 * 
	 * @param Course $use_course
	 *       - Optional.  If you already have a course object which can be used
	 *         as a template to search for, specify it here.  Otherwise, set to
	 *         NULL.  If using this, then $course_id, $term_id, and $bool_transfer
	 *         will be ignored.
	 * 
	 * 
	 * @return Course
	 */
	function find_specific_course($course_id = 0, $term_id = 0, $bool_transfer = false, $bool_exclude_substitutions = true, Course $use_course = null)
	{
		if ($use_course != null && is_object($use_course))
		{
			$course_id = $use_course->course_id;
			$term_id = $use_course->term_id;
			$bool_transfer = $use_course->bool_transfer;
		}
		// Look through the array for a course with this id, termId, and
		// transfer credit status.
		//admin_debug("Looking for $course_id $term_id $bool_transfer ");
		for ($t = 0; $t < $this->count; $t++)
		{
			$course = $this->array_list[$t];

			$check_course_id = $course->course_id;
			//admin_debug("..... looking at $check_course_id");
			if ($bool_transfer == true && is_object($course->course_transfer))
			{
				$check_course_id = $course->course_transfer->course_id;
				//admin_debug("..... ..... using transfer $check_course_id");
			}

			if ($check_course_id == $course_id && $course->term_id == $term_id && $course->bool_transfer == $bool_transfer)
			{

				if ($bool_exclude_substitutions == true)
				{
					if ($course->bool_substitution == true)
					{
						continue;
					}

				}

				return $course;
			}
		}

		return false;
	}


	/**
	 * Call the $course->load_course_descriptive_data() on 
	 * every course in the list.
	 *
	 */
	function load_course_descriptive_data()
	{
		// Call the load_descriptive_data() method
		// for every course in the list.

		for ($t = 0; $t < $this->count; $t++)
		{
			$course = $this->array_list[$t];
			$course->load_descriptive_data();
		}
	}


  /**
   * Using the parent's function of find_all_matches, this
   * will return a CourseList of all courses which match
   * the Course object.
   *
   * @param Course $course_c
   * @return CourseList
   */
	function find_all_matches(Course $course_c)
	{
		if (!$list_matches =  parent::find_all_matches($course_c))
		{
			return false;
		}


		$list_matches = CourseList::cast($list_matches);
		return $list_matches;
	}


	/**
	 * Returns a match to the Course courseC which does
	 * not have any courses fulfilling it.  Usefull for finding
	 * course requirement matches in a list which have not
	 * yet been assigned.
	 *
	 * @param Course $course_c
	 * @return Course
	 */
	function find_first_unfulfilled_match(Course $course_c)
	{
		// Returns match to courseC which does not have
		// any courses fulfilling it.  Useful for finding
		// course requirement matches in a list which have not
		// yet been assigned.

		for ($t = 0; $t < $this->count; $t++)
		{
			if ($this->array_list[$t]->equals($course_c) && $this->array_list[$t]->course_list_fulfilled_by->is_empty == true)
			{
				return $this->array_list[$t];
			}
		}

		return false;
	}

	/**
	 * Go through the list and set the $bool_exclude_repeat flag to TRUE
	 * for all matches of $course in this list.
	 *
	 * Returns FALSE if no matches could be found.
	 * 
	 * @param Course $course
	 * @return bool
	 */
	function mark_repeats_exclude(Course $course)
	{
		// Set the bool_exclude_repeat flag to TRUE for all
		// occurances of $course in THIS list.

		if (!$list_matches =  parent::find_all_matches($course))
		{
			return false;
		}

		$list_matches = CourseList::cast($list_matches);
		$list_matches->reset_counter();
		while($list_matches->has_more())
		{
			$c = $list_matches->get_next();
			$c->bool_exclude_repeat = true;
		}

		return true;

	}

	/**
	 * Find a list of matches to Course courseC, which fulfill 
	 * the min_grade requirement, ordered by most recently taken.
	 *
	 * Returns FALSE if no matches were found, else it will 
	 * return the matched Course object.
	 * 
	 * @param Course $course_c
	 * @param string $min_grade
	 * @param bool $bool_mark_repeats_exclude
	 * 
	 * @return Course
	 */
	function find_most_recent_match(Course $course_c, $min_grade = "D", $bool_mark_repeats_exclude = false)
	{
		// Get a list of all matches to courseC, and
		// then order them by the most recently taken course
		// first.
		// We should, too, check for minimum grades here
		// as well.


		if (!$list_matches =  parent::find_all_matches($course_c))
		{
			return false;
		}


		$list_matches = CourseList::cast($list_matches);


		// Don't just return if it's only got a size of 1,
		// so that it forces it to do the min grade checking.
		/*		if ($list_matches->getSize() == 1)
		{
		return $list_matches->get_next();
		}
		*/
		if ($list_matches->is_empty)
		{
			return false;
		}

		// If we are here, then we have more than one match.
		// Meaning, we have more than one class which might fit
		// into this course requirement.

		// Sort the courses into most recently taken first.
		//admin_debug("-------------------");
		//print_pre($list_matches->to_string());
		$list_matches->sort_most_recent_first();

		//admin_debug("-------------------");
		//print_pre($list_matches->to_string());
		//admin_debug("-------------------");

		// So, now that it's sorted, we should look through the list,
		// checking the min grade requirements (if any).  When we find
		// a good one, we will select it.

		$list_matches->reset_counter();
		while($list_matches->has_more())
		{
			$c = $list_matches->get_next();
			
			if ($c->bool_exclude_repeat == true)
			{
				continue;
			}
			//////////////////////////////////////////
			///  Check for min grade, etc, here.
			//admin_debug("checking min grade ($min_grade) for " . $c->to_string());
			if (!$c->meets_min_grade_requirement_of(null, $min_grade))
			{
				//admin_debug("skippin");
				if ($bool_mark_repeats_exclude == true)
				{
					// Since this course does not meet the min_grade,
					// check to see if it may be repeated.  If it can't,
					// then we must mark ALL previous attempts at this
					// course as being excluded from further consideration.
					// (ULM policy on repeats).
					// We don't do this consideration if they simply
					// withdrew from a course...
					if ($c->grade == "W") { continue; }

          if ($c->min_hours < 1 || $c->min_hours == "") {
					  $c->load_descriptive_data();  // make sure we get hour data for this course.
					}					
					
					if ($c->repeat_hours <= $c->min_hours)
					{
						// No repeats.
						//admin_debug("no repeats allowed. rep hours:" . $c->repeat_hours . " - min_hours:" . $c->min_hours);
						$this->mark_repeats_exclude($c);
						return false;

					} else {
						// Repeats allowed, so just continue.
						continue;
					}

				} else {
					continue;
				}
			}

			// Has the course already been assigned?
			if ($c->bool_has_been_assigned)
			{ // Skip over it.  Now, this is an important part here, because actually, we should
				// only skip it (and look at the next one) if this course is allowed to be
				// repeated.  If it cannot be repeated, or if the student has taken the
				// maximum allowed hours, then we should return false right here.
				continue;
			}

			return $c;
		}

		return false;

	}

	/**
	 * @todo implement this function.
	 *
	 */
	function sort_best_grade_first()
	{
		// This will look very similar to sort_most_recent_first
		// when I get a chance to fool with it.
	}

	
	/**
	 * Remove courses from THIS list which appear in listCourses under
	 * these conditions:
	 *   - the listCourses->"assigned_to_group_id" != $group_id
	 * This function is being used primarily with $list_courses being the
	 * list of courses that students have taken.
	 * Also checking substitutions for courses substituted into groups.

	 * @param CourseList $list_courses
	 * @param int $group_id
	 * @param bool $bool_keep_repeatable_courses
	 * @param SubstitutionList $list_substitutions
	 */
	function remove_previously_fulfilled(CourseList $list_courses, $group_id, $bool_keep_repeatable_courses = true, $list_substitutions)
	{

		$rtn_list = new CourseList();

		for ($t = 0; $t < $this->count; $t++)
		{
			$course = $this->array_list[$t];

			if ($bool_keep_repeatable_courses == true)
			{ // We can always keep repeatable courses in the list.
				if ($course->repeat_hours > $course->min_hours)
				{
					$rtn_list->add($course);
					continue;
				}
			}

			//admin_debug($course->to_string());
			
			// Has the course been substituted?
			if ($test_sub = $list_substitutions->find_requirement($course,false, -1))
			{
				//admin_debug("found " . $test_sub->to_string());
				// it WAS substituted, so we should NOT add it to our
				// rtnList.
				continue;
			}
			
			
			// Okay, now check if $course is anywhere in $list_courses
			if ($test_course = $list_courses->find_match($course))
			{
				// Yes, it found a match.
				//admin_debug("Here!");
				// I am taking out this part where I say if it is in
				// this group then we can keep it.  I think that shouldn't
				// be in.
				// This course is in another group, so do nothing
				// and skip it.
				//admin_debug("found elsewhere in group: $test_course->grade");
				
				// perhaps the course is on the degreePlan in excess with a W
				// or F?
				if (!$test_course->meets_min_grade_requirement_of(null, "D"))
				{
					// Meaning, this was a failed attempt, so we can add
					// our original course back in.
					$rtn_list->add($course);
					continue;
				}
				
				// perhaps this course was purposefully excluded from
				// this list because it did not meet the min grade
				// requirements?  If this is the case, $course should
				// still appear in THIS list.
				if (!$test_course->meets_min_grade_requirement_of($course))
				{
					// Meaning, this was attempt did not meet the
					// min grade of the original requirement, so we can add
					// our original requirement back in.
					$rtn_list->add($course);
					continue;
				}
															
			} else {
				// The course was NOT found in the courseList,
				// so its safe to add it back in.
				$rtn_list->add($course);
			}

		}


		$this->array_list = $rtn_list->array_list;
		$this->reset_counter();

	}


	/**
	 * Returns an array containing the unique subject_id's of
	 * the courses in this list.  Its assumed to be ordered
	 * already!
	 *
	 * @param bool $bool_ignore_excluded
	 * @return array
	 */
	function get_course_subjects($bool_ignore_excluded = true)
	{
		// returns an array containing the unique subject_id's
		// of the courses in this list.

		// IMPORTANT:  The list is assumed to be ordered already!  Either
		// alphabetically or reverse alphabetically.
		$old_subject_id = "";
		$rtn_array = array();

		for ($t = 0; $t < $this->count; $t++)
		{
			$course = $this->array_list[$t];
			if ($course->subject_id == "")
			{
				$course->load_descriptive_data();
			}

			
			// Go through all valid names for this course.
			for ($x = 0; $x < count($course->array_valid_names); $x++)
			{
				$temp = split("~",$course->array_valid_names[$x]);
				$subj = strtoupper($temp[0]);

				if (in_array($subj, $rtn_array))
				{ // skip ones with subjects we have already looked at.
					continue;
				}

				if ($course->db_exclude == 1)
				{
					//admin_debug("skipping " . $course->to_string());
					continue;
				}


				// We have a new subject.  Add it to the array.
				$rtn_array[] = $subj;
			}

		}

		return $rtn_array;
	}

	/**
	 * Go through the courseList and take out any course
	 * which does not have the $subject as its subject_id.
	 *
	 * @param string $subject
	 * @param bool $bool_reassign_valid_name
	 *     - If set to TRUE, we will look at other possible valid names
	 *       for this course.  If we find one, we will reassign the course's
	 *       subject_id and course_num to the new valid name.
	 * 
	 */
	function exclude_all_subjects_except($subject, $bool_reassign_valid_name = true)
	{

		$new_course_list = new CourseList();

		for ($t = 0; $t < $this->count; $t++)
		{
			$course = $this->array_list[$t];
			if ($course->subject_id == $subject)
			{
				$new_course_list->add($course);
				continue;
			}
			
			// Not the right subject-- but perhaps the course has another
			// valid name with this subject?  Ex: CSCI 373 and MATH 373.
			
			if ($bool_reassign_valid_name == true && count($course->array_valid_names) > 1)
			{
				for ($x = 0; $x < count($course->array_valid_names); $x++)
				{
					if (strstr($course->array_valid_names[$x], $subject))
					{
						$temp = split("~",$course->array_valid_names[$x]);
						$course->subject_id = $temp[0];
						$course->course_num = $temp[1];
						//admin_debug("use $course->subject_id $course->course_num");
						$new_course_list->add($course);
						continue;
					}
				}
				
			}
			
			
		}

		// Now, transfer ownership of the arraylist.
		$this->array_list = $new_course_list->array_list;

	}


	/**
	 * This re-sorts the CourseList so that fulfilled courses
	 * are first, in alphabetical order, followed by
	 * unfulfilled courses, in alphabetical order.
	 * This is most useful for making the groups
	 * show up correctly.
	 * 
	 */
	function sort_fulfilled_first_alphabetical()
	{

		$tarray = array();
		for ($t = 0; $t < $this->count; $t++)
		{
			//if (!is_object($this->array_list[$t]->courseFulfilledBy))
			if ($this->array_list[$t]->course_list_fulfilled_by->is_empty == true)
			{ // Skip if not fulfilled.
				continue;
			}

			$c = $this->array_list[$t];
			$str = "$c->subject_id ~~ $c->course_num ~~ $t";
			array_push($tarray,$str);
		}

		sort($tarray);

		$new_list = new CourseList();
		for($t = 0; $t < count($tarray); $t++)
		{
			$temp = split(" ~~ ",$tarray[$t]);
			$i = $temp[2];

			$new_list->add($this->array_list[$i]);
		}


		// Alright, now we do it again, but with unfulfilled courses.
		$tarray = array();
		for ($t = 0; $t < $this->count; $t++)
		{
			//if (is_object($this->array_list[$t]->courseFulfilledBy))
			if ($this->array_list[$t]->course_list_fulfilled_by->is_empty != true)
			{ // Skip if fulfilled.
				continue;
			}

			$c = $this->array_list[$t];
			$str = "$c->subject_id ~~ $c->course_num ~~ $t";
			array_push($tarray,$str);
		}

		sort($tarray);

		$new_list2 = new CourseList();
		for($t = 0; $t < count($tarray); $t++)
		{
			$temp = split(" ~~ ",$tarray[$t]);
			$i = $temp[2];

			$new_list2->add($this->array_list[$i]);
		}

		// Now, combine the two lists.
		$new_list->add_list($new_list2);

		// And, transfer the newList into this list.
		$this->array_list = $new_list->array_list;


	}



  /**
   * This re-sorts the CourseList so that advised courses
   * are last, in alphabetical order, preceeded by
   * unfulfilled courses, in alphabetical order.
   * 
   *
   */
	function sort_advised_last_alphabetical()
	{

		$tarray = array();
		for ($t = 0; $t < $this->count; $t++)
		{
			if ($this->array_list[$t]->bool_advised_to_take == true)
			{ // Skip if not fulfilled.
				continue;
			}

			$c = $this->array_list[$t];
			$str = "$c->subject_id ~~ $c->course_num ~~ $t";
			array_push($tarray,$str);
		}

		sort($tarray);


		// Alright, now we do it again, but with advised courses.
		$t2array = array();
		for ($t = 0; $t < $this->count; $t++)
		{
			if ($this->array_list[$t]->bool_advised_to_take == false)
			{ // Skip if not advised
				continue;
			}

			$c = $this->array_list[$t];
			$str = "$c->subject_id ~~ $c->course_num ~~ $t";
			array_push($t2array,$str);
		}

		sort($t2array);

		$t3array = array_merge($tarray, $t2array);

		$new_list = new CourseList();
		for($t = 0; $t < count($t3array); $t++)
		{
			$temp = split(" ~~ ",$t3array[$t]);
			$i = $temp[2];

			$new_list->add($this->array_list[$i]);
		}

		// And, transfer the newList into this list.
		$this->array_list = $new_list->array_list;


	}


	/**
	 * This function will resort this courselist for which a substitution
	 * has been made in listSubstitutions.
	 *
	 * @param SubstitutionList $list_substitutions
	 * @param int $group_id
	 */
	function sort_substitutions_first($list_substitutions, $group_id = 0)
	{
		// This will sort courses in a list for which
		// a substitution has been made in listSubstitutions.
		// It will place those courses at the top of the list.

		$top_array = array();

		// Since I need the indexes, I will have to go through the array
		// myself...
		for ($t = 0; $t < $this->count; $t++)
		{
			$c = $this->array_list[$t];
			// So-- does this course have a substitution somewhere in
			// the list (for the supplied groupID) ?
			if ($substitution = $list_substitutions->find_requirement($c, true, $group_id))
			{
				// yes, there is a sub for this group (or bare degree plan)
				$top_array[] = $t;
			}

		}

		// Okay, we now have, in the topArray, a list of indexes which should
		// appear at the top.
		$new_list = new CourseList();
		for ($j = 0; $j < count($top_array); $j++)
		{
			$new_list->add($this->array_list[$top_array[$j]]);
		}

		// Now, add everything else in the array (except indecies
		// appearing in topArray)

		for ($t = 0; $t < $this->count; $t++)
		{
			if (in_array($t, $top_array))
			{
				continue;
			}
			$new_list->add($this->array_list[$t]);
		}

		$this->array_list = $new_list->array_list;
		$new_list->reset_counter();

	}


	/**
	 * This will sort so that courses with the smallest hours
	 * (first trying hours_awarded, then min_hours)
	 * are at the top of the list.  If the list contains more
	 * than one course with a set of hours (like there are 30   
	 * courses all worth 3 hours) then it orders those as
	 * most-recently-taken first. 
	 *
	 */
	function sort_smallest_hours_first()
	{
		
		$tarray = array();

		// Since I need the indexes, I will have to go through the array
		// myself...
		for ($t = 0; $t < $this->count; $t++)
		{
			$c = $this->array_list[$t];
			$hours = $c->hours_awarded*1;
			if ($hours < 1)
			{
				$hours = $c->min_hours*1;
			}
			$str = "$hours ~~ $t";
			array_push($tarray,$str);
		}

		// Now, sort the array...
		//print_pre(print_r($tarray));
		sort($tarray);
		//print_pre(print_r($tarray));

		// Now, convert the array back into a list of courses.
		$new_list = new CourseList();
		for($t = 0; $t < count($tarray); $t++)
		{
			$temp = split(" ~~ ",$tarray[$t]);
			$i = $temp[1];

			$new_list->add($this->array_list[$i]);
		}

		// Okay, now $new_list should contain the correct values.
		// We will transfer over the reference.
		$this->array_list = $new_list->array_list;


	}


	/**
	 * This method will sort by the most recently taken
	 * courses (determined by the term_id).
	 * The easiest way I can think to do this is to temporarily
	 * put their term_id's and index#'s into an array, and then
	 * have PHP sort the array itself.  PHP's sorting algorithm
	 * is faster than anything I can program right now, anyway.
	 *
	 * @param bool $bool_maintain_alpha_order
	 */
	function sort_most_recent_first($bool_maintain_alpha_order = true)
	{
		$tarray = array();

		
		// Since I need the indexes, I will have to go through the array
		// myself...
		for ($t = 0; $t < $this->count; $t++)
		{
			$c = $this->array_list[$t];
			$cn = "";
			if ($bool_maintain_alpha_order == true)
			{
				// We say 1000- the course number in order to give
				// us the complement of the number.  That is so it will
				// reverse-sort in the correct order.  Strange, but it fixes
				// a small display issue where PHYS 207 and PHYS 209, taken at
				// the same time, causes PHYS 209 to be displayed first.
				// We also reverse the subject_id, again, so that
				// MATH will be sorted above ZOOL, when taken at the same time.
				// This might not work at all, though...
				
				$cn = strrev($c->subject_id) . "," . (1000 - $c->course_num);
				//admin_debug($cn);
			}
			$str = "$c->term_id ~~ $cn ~~ $t";
			//admin_debug($str);
			array_push($tarray,$str);
		}

		// Now, sort the array...
		//print_pre(print_r($tarray));
		rsort($tarray);
		//print_pre(print_r($tarray));

		// Now, convert the array back into a list of courses.
		$new_list = new CourseList();
		for($t = 0; $t < count($tarray); $t++)
		{
			$temp = split(" ~~ ",$tarray[$t]);
			$i = $temp[2];

			$new_list->add($this->array_list[$i]);
		}

		// Okay, now $new_list should contain the correct values.
		// We will transfer over the reference.
		$this->array_list = $new_list->array_list;

	}

	/**
	 * Convienence function.  It simply calls sort_alphabetical_order(), but
	 * passes the boolean value to make it be reversed.
	 *
	 */
	function sort_reverse_alphabetical_order()
	{
		$this->sort_alphabetical_order(true);
	}


	/**
	 * Sorts the course list into alphabetical order.  If load_descriptive_data()
	 * has not already been called for each course, it will call it.
	 *
	 * @param bool $bool_reverse_order
	 *         - If set to TRUE, the list will be in reverse order.
	 * 
	 * @param unknown_type $bool_only_transfers
	 *         - Only sort the transfer courses.
	 * 
	 * @param unknown_type $bool_set_array_index
	 *         - If set to true, it will set the $course->array_index value
	 *           to the index value in $this's array_list array.
	 * 
	 */
	function sort_alphabetical_order($bool_reverse_order = false, $bool_only_transfers = false, $bool_set_array_index = false)
	{
		// Sort the list into alphabetical order, based
		// on the subject_id and course_num.
		$tarray = array();
		// Since I need the indexes, I will have to go through the array
		// myself...
		for ($t = 0; $t < $this->count; $t++)
		{
			$c = $this->array_list[$t];
			if ($c->subject_id == "")
			{
				$c->load_descriptive_data();
			}


			if ($bool_only_transfers == true)
			{
				// Rarer.  We only want to sort the transfer credits.  If the course doesn not
				// have transfers, don't skip, just put in the original.  Otherwise, we will be using
				// the transfer credit's SI and CN.
				if (is_object($c->course_transfer))
				{
					$str = $c->course_transfer->subject_id . " ~~ " . $c->course_transfer->course_num ." ~~ $t";
				} else {
					// There was no transfer!
					$str = "$c->subject_id ~~ $c->course_num ~~ $t";
				}
			} else {

				// This is the one which will be run most often.  Just sort the list
				// in alphabetical order.

				$str = "$c->subject_id ~~ $c->course_num ~~ $t";
			}
			array_push($tarray,$str);
		}

		// Now, sort the array...
		//print_pre(print_r($tarray));

		if ($bool_reverse_order == true)
		{
			rsort($tarray);
		} else {
			sort($tarray);
		}
		//print_pre(print_r($tarray));

		// Now, convert the array back into a list of courses.
		$new_list = new CourseList();
		for($t = 0; $t < count($tarray); $t++)
		{
			$temp = split(" ~~ ",$tarray[$t]);
			$i = $temp[2];
			if ($bool_set_array_index == true)
			{
				$this->array_list[$i]->array_index = $i;
			}
			$new_list->add($this->array_list[$i]);
		}

		// Okay, now $new_list should contain the correct values.
		// We will transfer over the reference.
		$this->array_list = $new_list->array_list;

	}


	/**
	 * Returns an array of db_group_requirement_id's from the courses
	 * in this list.
	 *
	 * @return array
	 */
	function get_group_requirement_id_array()
	{
		// Return an array of db_group_requirement_id's
		// from the courses in this list, indexed by the
		// id's.

		$rtn_array = array();
		for ($t = 0; $t < $this->count; $t++)
		{
			$course = $this->array_list[$t];
			$rtn_array[$course->db_group_requirement_id] = true;
		}

		return $rtn_array;
	}

	
	
	/**
	 * Returns TRUE if this list has a course which contains
	 * $id for it's db_group_requirement_id property.
	 *
	 * @param int $id
	 *         - This is the id to test for.
	 * 
	 * @return bool
	 */
	function contains_group_requirement_id($id)
	{
		// Returns true if the list has a course
		// which contains $id for it's db_group_requirement_id.
		// False if it cannot be found.
		for ($t = 0; $t < $this->count; $t++)
		{
			$course = $this->array_list[$t];
			if ($course->db_group_requirement_id == $id)
			{
				return true;
			}
		}

		return false;

	}

	
  /**
	 * Returns the first course in the list
	 * which the user may select for advising.  This
	 * method is used by the popup window to determine
	 * what exactly is the first element of the course list.
	 * (so it can make that radio button checked).
	 * 
	 * Returns FALSE if it cannot find a selectable course.
   *
   * @return Course
   */
	function find_first_selectable()
	{
		/*

		*/
		$temp_i = $this->i;  // preserve the counter.
		$this->reset_counter();

		while($this->has_more())
		{
			$c = $this->get_next();
			if ($c->bool_advised_to_take == true)
			{
				continue;
			}
			if (!$c->course_list_fulfilled_by->is_empty)
			{
				continue;
			}
			if ($c->bool_unselectable == true)
			{
				continue;
			}

			// $c is our valid course...
			$this->i = $temp_i;
			//print_pre($c->to_string());
			return $c;


		}

		$this->i = $temp_i;
		return false;

	}


	/**
	 * Returns TRUE if there is at least one course in this list which
	 * is selected (for advising).
	 *
	 * @return bool
	 */
	function has_any_course_selected()
	{
		/*
		Returns TRUE if there is at least one course
		in this list which is "selected."  FALSE, otherwise.
		*/
		$temp_i = $this->i;  // preserve the counter.
		$this->reset_counter();
		$rtn = false;
		while($this->has_more())
		{
			$c = $this->get_next();
			if ($c->bool_selected == true)
			{
				$rtn = true;
				break;
			}
		}

		$this->i = $temp_i;
		return $rtn;
	}


	/**
	 * Mark every course in this list as bool_has_been_displayed = true.
	 * Used for making sure we don't display the same course twice on
	 * screen.
	 * 
	 * Returns FALSE if we did not mark any courses.
	 *
	 * @param int $semester_num
	 *         - If > -1, we will first make sure the course
	 *           falls into this semesterNum.  This way we can only
	 *           perform this operation on a particular semester.
	 * 
	 * @return bool
	 */
	function mark_as_displayed($semester_num = -1)
	{

	  $temp_i = $this->i;  // preserve the counter.
		$this->reset_counter();
		$rtn = false;
		while($this->has_more())
		{
			$c = $this->get_next();
			if ($semester_num != -1)
			{ // A semesterNum was specified.
				// Make sure the course is in the correct semester.
				if ($c->assigned_to_semester_num != $semester_num)
				{
					continue;
				}
			}

			$c->bool_has_been_displayed = true;
			$rtn = true;

		}

		$this->i = $temp_i;
		return $rtn;
	}


	/**
	 * Returns a CourseList of all the courses matching course_id
	 * that has bool_has_been_assigned == TRUE.
	 *
	 * @param int $course_id
	 * @return CourseList
	 */
	function get_previous_assignments($course_id)
	{
		// Return a courseList of all the times a course matching
		// course_id has the bool_has_been_assigned set to TRUE.

		$rtn_list = new CourseList();

		for ($t = 0; $t < $this->count; $t++)
		{
			$course = $this->array_list[$t];
			if ($course->course_id == $course_id && $course->bool_has_been_assigned == true)
			{
				$rtn_list->add($course);
			}
		}

		return $rtn_list;

	}

	/**
	 * Find the "best" match for this course, based on what
	 * the university considers a best match.
	 * This largely has to do with repeats.
	 * If the student has more than one credit, what is
	 * the "best" match?
	 * 
	 * For example, at ULM we consider the best match to be the
	 * most recent that meets the minimum requirements.
	 * Other schools might simply take the best grade.
	 *
	 * @param Course $course_c
	 * @param string $min_grade
	 * @param bool $bool_mark_repeats_exclude
	 *
	 * @return Course
	 */
	function find_best_match(Course $course_c, $min_grade = "D", $bool_mark_repeats_exclude = false)
	{

		return $this->find_most_recent_match($course_c, $min_grade, $bool_mark_repeats_exclude);

	}



	/**
	 * Adds the supplied CourseList to the bottom of $this's list.
	 *
	 * @param CourseList $course_l
	 */
	function add_list(CourseList $course_l)
	{
		for ($t = 0; $t < count($course_l->array_list); $t++)
		{
			//admin_debug($course_l->array_list[$t]->assigned_to_semester_num);
			//admin_debug("adding " . $course_l->array_list[$t]->to_string());
			$this->add($course_l->array_list[$t]);
		}

	}


	/**
	 * Returns hour many hours are in $this CourseList.
	 *
	 * @todo The ignore list should be database-based.  Should just get it
	 *       from the settings.
	 * 
	 * @param string $requirement_type
	 *         - If specified, we will only count courses which match this
	 *           requirement_type.
	 * 
	 * @param bool $bool_use_ignore_list
	 * @return int
	 */
	function count_hours($requirement_type = "", $bool_use_ignore_list = false, $bool_correct_ghost_hour = true, $bool_force_zero_hours_to_one_hour = false)
	{
		// Returns how many hours are being represented in this courseList.
		// A requirement type of "uc" is the same as "c"
		// (university capstone is a core requirement)


		$count = 0;
		for ($t = 0; $t < $this->count; $t++)
		{
			$course = $this->array_list[$t];

			if ($bool_use_ignore_list == true)
			{
				// Do ignore some courses...
				$temp_course_name = $course->subject_id . " " . $course->course_num;
				// Check in our settings to see if we should ignore this course
				// (configured in /custom/settings.php)
				if (in_array($temp_course_name, $GLOBALS["fp_system_settings"]["ignore_courses_from_hour_counts"])) {
					continue;
				}
				
			}
			
			if ($course->bool_substitution_new_from_split == true)
			{
				// Do not count the possible fragments that are created
				// from a new substitution split.  This is causing problems
				// in getting accurate numbers on the pie charts.
				
				// BUT-- only skip if this new fragment isn't also being
				// substituted somewhere else!
				if ($course->bool_substitution == false)
				{ // not being used in another sub, so skip it.
					//admin_debug("skipping " . $course->to_string());
					continue;
				}
			}

			$h_get_hours = $course->get_hours();
			if ($bool_correct_ghost_hour) {
  			// If this course has a ghosthour, then use the
  			// hours_awarded (probably 1).  However, if it was substituted,
  			// then we actually want the 0 hour.  Confusing, isn't it?
  			if ($course->bool_ghost_hour) {
  			  $h_get_hours = $course->hours_awarded;
  			}
			}
			
			if ($bool_force_zero_hours_to_one_hour) {			  
			  // We want to force anything with a 0 hour to be 1 hour.
			  // Helps when selecting 0 hour courses from groups.
			  if ($h_get_hours == 0) {			    
			    $h_get_hours = 1;
			  }
			}
			
			
			if ($requirement_type == "")
			{
				$count = $count + $h_get_hours;
			} else {
				// Requirement Type not blank, so only count these hours
				// if it has the set requirement type.
				if ($course->requirement_type == $requirement_type)
				{
					$count = $count + $h_get_hours;
					//admin_debug($course->to_string());
					continue;
				}

				// For specifically "university capstone" courses...
				if ($course->requirement_type == "uc" && $requirement_type == "c")
				{
					$count = $count + $h_get_hours;
				}

				if ($course->requirement_type == "um" && $requirement_type == "m")
				{
					$count = $count + $h_get_hours;
				}


			}
		}

		return $count;
	}

	
	/**
	 * Removes courses which have neither been fulfilled or advised.
	 *
	 */
	function remove_unfulfilled_and_unadvised_courses()
	{
		// remove courses from THIS list
		// which have not been fulfilled AND
		// are not currently advised.
		$rtn_list = new CourseList();
		for ($t = 0; $t < $this->count; $t++)
		{
			$course = $this->array_list[$t];
			if ($course->course_list_fulfilled_by->is_empty == false)
			{
				// something IS fulfilling it!
				$rtn_list->add($course);

			} else if ($course->bool_advised_to_take == true)
			{
				// Was not being fulfilled, but, it was advised
				// to take.
				$rtn_list->add($course);
			}



		}

		$this->array_list = $rtn_list->array_list;
		$this->reset_counter();
	}


	/**
	 * Removes courses from this list which have not been fulfilled
	 * (ther course_list_fulfilled_by is empty).
	 *
	 */
	function remove_unfulfilled_courses()
	{
		// remove courses in THIS list
		// which have nothing in their course_list_fulfilled_by
		// object.
		$rtn_list = new CourseList();
		for ($t = 0; $t < $this->count; $t++)
		{
			$course = $this->array_list[$t];
			if ($course->course_list_fulfilled_by->is_empty == false)
			{
				$rtn_list->add($course);
			}
		}

		$this->array_list = $rtn_list->array_list;
		$this->reset_counter();

	}

	
	/**
	 * Returns a clone CourseList of $this.
	 *
	 * @param bool $bool_return_new_courses
	 *         - If set to TRUE, it will create new Course objects
	 *           based on the course_id's of the ones in $this's list.
	 *           If set to FALSE, this will add the exact same Course
	 *           objects by reference to the new list.
	 * 
	 * @return CourseList
	 */
	function get_clone($bool_return_new_courses = false)
	{
		// This will return a clone of this list.
		// If boolReturnNewCourses is true, then it will
		// return a new list of new instances of courses
		// from this list.
		$rtn_list = new CourseList();
		
		for ($t = 0; $t < $this->count; $t++)
		{
			$course = $this->array_list[$t];
			
			if ($bool_return_new_courses == true)
			{
				$new_course = new Course();
				$new_course->course_id = $course->course_id;
				$rtn_list->add($new_course);
			} else {
				$rtn_list->add($course);
			}	
			
		}	
		
		return $rtn_list;
			
	}
	

	/**
	 * Returns a CourseList of all the fulfilled or advised courses
	 * in $this's list.
	 *
	 * @param bool $bool_return_new_courses
	 *         - Works the same as get_clone()'s boolReturnNewCourses
	 *           variable.
	 * 
	 * @return Course
	 */
	function get_fulfilled_or_advised($bool_return_new_courses = false)
	{
		
		$rtn_list = new CourseList();
		
		for ($t = 0; $t < $this->count; $t++)
		{
			$course = $this->array_list[$t];
			$add_course = $course;
			
			if ($bool_return_new_courses == true)
			{
				$add_course = new Course();
				$add_course->course_id = $course->course_id;
			}
			
			if ($course->bool_advised_to_take == true)
			{
				$rtn_list->add($add_course);
			}
			
			// Several ways to tell if a course is here by credit...
			if (!$course->course_list_fulfilled_by->is_empty)
			{
				$rtn_list->add($add_course);
			} else if ($course->grade != "") {
				$rtn_list->add($add_course);
			} else if ($course->bool_substitution == true)
			{
				$rtn_list->add($add_course);
			}			
		}
		
		return $rtn_list;
		
	}
	
	/**
	 * Returns the number of courses in this list which have either
	 * been fulfilled or advised to take.  It does not count hours,
	 * just the courses themselves.
	 *
	 * @return int
	 */
	function count_fulfilled_or_advised()
	{
		// This function returns the number of courses in this
		// courseList which is either fulfilled or has been advised
		// to take.  It does care about hours, just the number of
		// courses themselves.
		$count = 0;
		for ($t = 0; $t < $this->count; $t++)
		{
			$course = $this->array_list[$t];
			if ($course->bool_advised_to_take == true)
			{
				$count++;
			}
			
			// Several ways to tell if a course is here by credit...
			if (!$course->course_list_fulfilled_by->is_empty)
			{
				$count++;
			} else if ($course->grade != "") {
				$count++;
			} else if ($course->bool_substitution == true)
			{
				$count++;
			}			
		}
		
		return $count;
		
	}
	
	
	/**
	 * Returns a CourseList of courses which have bool_advised_to_take == true.
	 *
	 * @return CourseList
	 */
	function get_advised_courses_list()
	{
		// Return a courseList object of courses in THIS
		// list which have bool_advised_to_take == true.
		$rtn_list = new CourseList();
		for ($t = 0; $t < $this->count; $t++)
		{
			$course = $this->array_list[$t];
			if ($course->bool_advised_to_take == true)
			{
				$rtn_list->add($course);
			}
		}

		return $rtn_list;

	}


	/**
	 * Similar to count_hours, but this will only count courses
	 * which have been taken and have a grade.
	 * 
	 * @todo ignore list should be db-based, in the settings.
	 *
	 * @param string $requirement_type
	 *         - If set, we will only look for courses matching this requirement_type.
	 * 
	 * @param bool $bool_use_ignore_list
	 * @param bool $bool_ignore_enrolled
	 * @return CourseList
	 */
	function count_credit_hours($requirement_type = "", $bool_use_ignore_list = false, $bool_ignore_enrolled = false)
	{
		// Similar to count_hours, but this will only
		// count courses which have been taken (have a grade).


		$count = 0;
		//admin_debug($requirement_type);
		for ($t = 0; $t < $this->count; $t++)
		{
			$course = $this->array_list[$t];

			if ($bool_use_ignore_list == true)
			{
				// Do ignore some courses...
        $temp_course_name = $course->subject_id . " " . $course->course_num;
				// Check in our settings to see if we should ignore this course
				// (configured in /custom/settings.php)
				if (in_array($temp_course_name, $GLOBALS["fp_system_settings"]["ignore_courses_from_hour_counts"])) {
					continue;
				}				

			}


			if ($bool_ignore_enrolled == true)
			{
				if ($course->is_completed() == false)
				{
					//admin_debug("skip" . $course->to_string());
					if ($course->course_list_fulfilled_by->is_empty)
					{

						continue;
					} else {
						if ($course->course_list_fulfilled_by->get_first()->is_completed() == false)
						{
							continue;
						}
					}
				}
			}

			if ($course->grade != "")// || !($course->course_list_fulfilled_by->is_empty))
			{
				if ($requirement_type == "")
				{
				  $h = $course->get_hours();
					$count = $count + $h;
					//admin_debug($course->to_string());
					//admin_debug($h);
				} else {
					if ($course->requirement_type == $requirement_type)
					{
						$count = $count + $course->get_hours();
						//admin_debug($course->to_string());
						continue;
					}

					// For specifically "university capstone" courses...
					if ($course->requirement_type == "uc" && $requirement_type == "c")
					{
						$count = $count + $course->get_hours();
					}

					if ($course->requirement_type == "um" && $requirement_type == "m")
					{
						$count = $count + $course->get_hours();
					}


				}
			} else {

				// maybe it's a substitution?
				if ($requirement_type == "")
				{
					if ($course->course_list_fulfilled_by->is_empty == false)
					{
						$cc = $course->course_list_fulfilled_by->get_first();
						if ($cc->bool_substitution)
						{
						  
							$h = $cc->substitution_hours;
							
							if ($cc->bool_ghost_hour) {
							  $h = 0;
							}
							
							$count = $count + $h;
							admin_debug($cc->to_string());
						}
					}
				} else {
					if ($requirement_type == $course->requirement_type)
					{
						if ($course->course_list_fulfilled_by->is_empty == false)
						{
							$cc = $course->course_list_fulfilled_by->get_first();
							if ($cc->bool_substitution)
							{
								$h = $cc->substitution_hours;
								
                if ($cc->bool_ghost_hour) {
  							  $h = 0;
  							}								
								
								$count = $count + $h;
								//admin_debug($cc->to_string());
							}
						}

					}


				}
			}
		}

		return $count;

	}


	/**
	 * Assign a groupID to every course in the list.
	 *
	 * @param int $group_id
	 */
	function assign_group_id($group_id)
	{
		for ($t = 0; $t < $this->count; $t++)
		{
			$course = $this->array_list[$t];
			$course->assigned_to_group_id = $group_id;
		}
	}


	/**
	 * Assign a semesterNum to every course in the list.
	 *
	 * @param int $semester_num
	 */
	function assign_semester_num($semester_num)
	{
		for ($t = 0; $t < $this->count; $t++)
		{
			$course = $this->array_list[$t];
			$course->assigned_to_semester_num = $semester_num;
		}
	}

	/**
	 * Sets the bool_has_been_assigned property of every course in
	 * the list.
	 *
	 * @param bool $bool_has_been_assigned
	 *         - What to set each course's->boolhasBeenAssigned property
	 *           to.
	 * 
	 */
	function set_has_been_assigned($bool_has_been_assigned = true)
	{
		// Set the bool_has_been_assigned for all items
		// in this list.
		for ($t = 0; $t < $this->count; $t++)
		{
			$course = $this->array_list[$t];
			$course->bool_has_been_assigned = $bool_has_been_assigned;
		}

	}


	/**
	 * Set's each course's bool_substitution value.
	 *
	 * @param bool $bool_s
	 *         - What to set each course's bool_substitution value to.
	 */
	function set_bool_substitution($bool_s = true)
	{
		// Set the bool_substitution for all items
		// in this list.
		for ($t = 0; $t < $this->count; $t++)
		{
			$course = $this->array_list[$t];
			$course->bool_substitution = $bool_s;
		}

	}

	/**
	 * Sets each course's $course_substitution value to the supplied
	 * Course object.
	 *
	 * @param Course $course_s
	 * @param string $sub_remarks
	 */
	function set_course_substitution(Course $course_s, $sub_remarks = "")
	{
		for ($t = 0; $t < $this->count; $t++)
		{
			$course = $this->array_list[$t];
			$course->course_substitution = $course_s;
			$course->sub_remarks = $sub_remarks;
		}

	}



	/**
	 * Go through the list and decrement the specified_repeats
	 * value for all instances of Course $course.
	 *
	 * @param Course $course
	 */
	function dec_specified_repeats(Course $course)
	{
		// Go through the list, and decrement the specified_repeats
		// value for all instances of $course.
		for ($t = 0; $t < $this->count; $t++)
		{
			$course2 = $this->array_list[$t];
			if ($course2->course_id == $course->course_id)
			{
				$course2->specified_repeats--;
			}
		}

	}


	/**
	 * Go through the list and set the specified_repeats value to $num
	 * for all instances of $course.
	 *
	 * @param Course $course
	 * @param int $num
	 */
	function set_specified_repeats(Course $course, $num)
	{
		for ($t = 0; $t < $this->count; $t++)
		{
			$course2 = $this->array_list[$t];
			if ($course2->course_id == $course->course_id)
			{
				$course2->specified_repeats = $num;
				$course2->bool_specified_repeats = true;
			}

		}

	}


	/**
	 * Removes excluded courses from the list (courses that
	 * have db_exclude == 1)
	 *
	 */
	function remove_excluded()
	{
		// Removes courses from the list that have a db_exclude == 1.
		$new_list = new CourseList();
		// Do this by adding elements to an array.
		// course_id => index in list.
		for ($t = 0; $t < $this->count; $t++)
		{
			$course = $this->array_list[$t];
			if ($course->subject_id == "")
			{ // load descriptive data (include exclude info)
				$course->load_descriptive_data();
			}
			
			if ($course->db_exclude == 1)
			{
				continue;
			}

			$new_list->add($course);

		}

		$this->array_list = $new_list->array_list;
		$this->reset_counter();

	}


	/**
	 * Removes null's and duplicate courses from the list.
	 *
	 */
	function remove_duplicates()
	{
		// Go through and remove duplicates from the list.
		// Also remove null's

		$tarray = array();
		$new_list = new CourseList();
		// Do this by adding elements to an array.
		// course_id => index in list.
		for ($t = 0; $t < $this->count; $t++)
		{
			$course = $this->array_list[$t];
			if ($course == null)
			{
				continue;
			}

			$tarray[$course->course_id] = -1;
		}

		for ($t = 0; $t < $this->count; $t++)
		{
			$course = $this->array_list[$t];
			if ($course == null)
			{
				continue;
			}

			//if (is_object($course->courseFulfilledBy))
			if (!($course->course_list_fulfilled_by->is_empty))
			{
				$tarray[$course->course_id] = $t;
				continue;
			}

			if ($tarray[$course->course_id]*1 < 0)
			{
				//admin_debug("empty $course->subject_id $course->course_num ");
				$tarray[$course->course_id] = $t;
			}

		}
		//print_pre(print_r($tarray));

		// Now, go through tarray and rebuild the newList.
		foreach($tarray as $course_id => $i)
		{
			$new_list->add($this->array_list[$i]);
		}

		// Switch over the reference.
		$this->array_list = $new_list->array_list;
		$this->reset_counter();

	}

} // end class CourseList









?>