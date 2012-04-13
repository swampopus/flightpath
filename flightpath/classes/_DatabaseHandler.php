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

class _DatabaseHandler
{
	//
	public $dbc;

	function __construct()
	{
	  	  
	  $db_host = $GLOBALS["fp_system_settings"]["db_host"];
	  $db_user = $GLOBALS["fp_system_settings"]["db_user"];
	  $db_pass = $GLOBALS["fp_system_settings"]["db_pass"];
	  $db_name = $GLOBALS["fp_system_settings"]["db_name"];
	  
    $this->dbc = mysql_connect ($db_host, $db_user, $db_pass) or die('Could not connect to database: ' . mysql_error());
		mysql_select_db ($db_name);

	}



	function get_help_page($i)
	{
		$rtn_array = array();


		$res = $this->db_query("SELECT * FROM help WHERE `id`='?' ", $i);
		$cur = $this->db_fetch_array($res);
		$rtn_array["title"] = trim($cur["title"]);
		$rtn_array["body"] = trim($cur["body"]);

		return $rtn_array;

	}

	
	function add_to_log($action, $extra_data = "", $notes = "")
	{
		// Add a row to the log table.
		$ip = $_SERVER["REMOTE_ADDR"];
		$url = mysql_real_escape_string($_SERVER["REQUEST_URI"]);
		$user_id = $_SESSION["fp_user_id"];
		$user_type = $_SESSION["fp_user_type"]; 
		$user_name = mysql_real_escape_string($_SESSION["fp_user_name"]);
    $action = mysql_real_escape_string($action);
    $extra_data = mysql_real_escape_string($extra_data);
    $notes = mysql_real_escape_string($notes);
		
    if ($GLOBALS["fp_page_is_mobile"]) {
      $notes = "M:" . $notes;
    }
    
    // This needs to be mysql_query, instead of "this->db_query", because
    // otherwise it might get into an infinite loop.
    $query = "INSERT INTO log (user_id,
						user_name, user_type, action, extra_data, notes,
						 ip, datetime, from_url) VALUES (
						'$user_id','$user_name','$user_type','$action','$extra_data',
						'$notes',
						'$ip', NOW() ,'$url') ";
		$res = mysql_query($query) or die(mysql_error() . " - " . $query);


		
		

	}

	/**
	 * Sets the maintenance mode.  $val should be either 0 (off) or 1 (on)
	 *
	 * @param integer $val
	 */
	function set_maintenance_mode($val)
	{
    // Convenience function for setting maintenance mode. 0 = off, 1 = on.
    $this->set_settings_variable("maintenance_mode", $val);
	}

	
	/**
	 * This attempts to set a variable in flightpath_settings,
	 * creating it if it does not exist.
	 *
	 * @param string $name
	 * @param string $val
	 */
	function set_settings_variable($name, $val) {
  
    $res = $this->db_query("REPLACE INTO flightpath_settings 
		            (`variable_name`, `value`)
								VALUES ('?', '?') ", $name, $val);		  
	  
	}
	
	
	/**
	 * Returns the value in the database table flightpath_settings
	 * for this variable, if it exists.
	 *
	 * @param string $name
	 */
	function get_settings_variable($name) {
	  
	  $res = $this->db_query("SELECT value FROM flightpath_settings
	                         WHERE variable_name = '?' ", $name);
	  $cur = $this->db_fetch_array($res);
	  
	  return $cur["value"];
	  
	}
	
	
	
	function get_substitution_details($sub_id)
	{
		// Simply returns an associative array containing
		// the details of a substitution.  The subID specified
		// is the actual id of the row of the database in
		// flightpath.student_substitutions.

		$rtn_array = array();

		$res = $this->db_query("SELECT * FROM student_substitutions
								WHERE id = '?' ", $sub_id);
		if ($this->db_num_rows($res) > 0)
		{
			$cur = $this->db_fetch_array($res);
			$rtn_array["faculty_id"] = $cur["faculty_id"];
			$rtn_array["remarks"] = trim($cur["sub_remarks"]);
			$rtn_array["required_course_id"] = $cur["required_course_id"];
			$rtn_array["required_group_id"] = $cur["required_group_id"];
			$rtn_array["datetime"] = $cur["datetime"];
		}

		return $rtn_array;

	}

	function update_user_settings_from_post($user_id)
	{
		// This will retrieve various user settings from the POST
		// and write them to the user_settings table as XML.
		$db = new DatabaseHandler();

		if ($user_id*1 < 1)
		{
			
			return false;
		}

		// First, we need to GET the user's settings array...
		if (!$user_settings_array = $this->get_user_settings($user_id))
		{
			// No existing userSettingsArray, or it's corrupted.
			// Make a new one.
			$user_settings_array = array();
		}

		// Now, update values in the settingsArray, if they are
		// present in the POST.
		if (trim($_POST["hide_charts"]) != "")
		{
			$user_settings_array["hide_charts"] = trim($_POST["hide_charts"]);
		}

		// Now, write it back to the settings table...
		$res = $this->db_query("REPLACE INTO user_settings(user_id,
								settings, `datetime`)
								VALUES ('?','?',NOW() )", $user_id, serialize($user_settings_array));

		$db->add_to_log("update_user_settings", "hide_charts:{$user_settings_array["hide_charts"]}");

		return true;



	}

	function get_user_settings($user_id)
	{
		// return an array of this user's current settings.

		$res = $this->db_query("SELECT * FROM user_settings
									WHERE 
									user_id = '?' ", $user_id);
		$cur = $this->db_fetch_array($res);

    if (!$rtn = unserialize($cur["settings"])) {
      $rtn = array();
    }
    
    return $rtn;
    
	}

	


	function get_developmental_requirements($student_id)
	{
		// returns an array which states whether or not the student
		// requires any developmental requirements.

    // Let's pull the needed variables out of our settings, so we know what
		// to query, because this is a non-FlightPath table.
		$tsettings = $GLOBALS["fp_system_settings"]["extra_tables"]["course_resources:student_developmentals"];
		$tf = (object) $tsettings["fields"];  //Convert to object, makes it easier to work with.  
		$table_name = $tsettings["table_name"];		
		
		
		$rtn_array = array();
		
		$res = $this->db_query("SELECT * FROM $table_name
							         WHERE $tf->student_id = '?' ", $student_id);
		while($cur = $this->db_fetch_array($res)) {
			$rtn_array[] = $cur[$tf->requirement];
		}

		return $rtn_array;

	}



	function get_table_transfer_data_string($table_name, $table_structure, $where_clause = "")
	{
		// This function will return a string of all the data
		// in a particular table, formatted with delimeters.
		// %R~ separates rows, %C~ separates columns.
		// We expect the tableStructure to be a csv of the
		// column names.
		$rtn = "";


		$res = mysql_query("select $table_structure from $table_name $where_clause") or die_and_mail(mysql_error());
		while ($cur = mysql_fetch_row($res))
		{
			$new_row = "";

			foreach($cur as $key => $value)
			{ // put all the values returned together...
				$new_row .= $value . "%C~";
			}
			// Remove last %C%...
			$new_row = substr($new_row, 0, -3);

			// Add it to the rtn...
			$rtn .= $new_row . "%R~";

		}

		// Remove the last %R%...
		$rtn = substr($rtn, 0, -3);

		return $rtn;
	}


	
	/**
	 * This is a simple helper function which "escapes" the question marks (?) in
	 * the string, by changing them to "??".  This makes it suitable for use
	 * within db_query(), but not necessary if used as an argument.  Ex:
	 * db_query("INSERT ... '" . $db->escape_question_marks($xml) . "' ");  is good.
	 * db_query("INSERT ... '?' ", $xml);  is good.   This function not needed.
	 *
	 * @param unknown_type $str
	 */
	function escape_question_marks($str) {
	  $rtn = str_replace("?", "??", $str);
	  return $rtn;
	}
	

  /**
   * This function is used to perform a database query.  It can take simple replacement patterns,
   * by using ?.  If you actually need to have a ? in the query, you can escape it with ??.
   * For example:
   * $result = $db->db_query("SELECT * FROM table WHERE name = '?' and age = ? ", $name, $temp_age);
   *
   * @param unknown_type $sql_query
   * @return unknown
   */
	function db_query($sql_query) {
	  
	  // If there were any arguments to this function, then we must first apply
	  // replacement patterns.
	  $args = func_get_args();
	  array_shift($args);
    if (is_array($args[0])) {
      // If the first argument was an array, it means we passed an array of values instead
      // of passing them directly.  So use them directly as our args.
      $args = $args[0];
    }


    // The query may contain an escaped ?, meaning "??", so I will replace that with something
    // else first, then change it back afterwards.
    $sql_query = str_replace("??", "~ESCAPED_Q_MARK~", $sql_query);
    
    // If $c (number of replacements performed) does not match the number of replacements
    // specified, warn the user.
    if (substr_count($sql_query, "?") != count($args)) {
      // TODO:  Replace this with a call to something
      // like fp_add_message("Blah blah blah", "warning");
      admin_debug("<br><b>WARNING:</b> Replacement count does not match what was supplied to query: $sql_query<br><br>");
    }    
    
	  if (count($args) > 0) {
	    // Replace each occurance of "?" with what's in our array.
	    
	    foreach ($args as $replacement) {
	      // Escape the replacement...
	      // The replacement might ALSO have a question mark in it.  Escape that too.
	      if (strpos($replacement, "?") !== 0) {
	        $replacement = str_replace("?", "~ESCAPED_Q_MARK~", $replacement);
        }
	      $replacement = mysql_real_escape_string($replacement);
	      $sql_query = preg_replace("/\?/", $replacement, $sql_query, 1);	    
	    }
	    
	  }
	  	  
	  $sql_query = str_replace("~ESCAPED_Q_MARK~", "?", $sql_query);	    
	  
	  //////////////////////////////////////////////
	    	  
		// Run the sqlQuery and return the result set.
		$result = mysql_query($sql_query, $this->dbc);
		if ($result)
		{
			return $result;
		} else {
			// Meaning, the query failed...
			$err_screen = $this->db_error($sql_query);
			$this->add_to_log("DB ERROR", mysql_real_escape_string(mysql_error()), mysql_real_escape_string($sql_query));
			//die($err_screen);
		}
	}


	/**
	 * Draw out the error onto the screen.
	 *
	 * @param unknown_type $sql
	 */
	function db_error($msg = "")
	{
    $pC = "<div style='border: 5px solid black; color: black;
    					background-color: beige; font-size: 12pt;
    					padding: 5px; font-family: Arial;'>
    			<div style='font-size: 14pt;'><b>FlightPath Database Error</b></div>
    			We're sorry, but a database error has occured.  The Web Programming
    			support staff have been notified of this error.			
    			<br><br>
    			Please try again
    			in a few minutes.<br><br>
    			";
    
    	// If we are on production, email someone!
    	if ($GLOBALS["fp_system_settings"]["notify_mysql_error_email_address"] != "")
    	{
    	  $server = $_SERVER["SERVER_NAME"];
    		$email_msg = "A MYSQL error has occured in FlightPath.  
    		Server: $server
    		
    		The error:
    		" . mysql_error() . "
    		
    		Comments:
    		$msg
    		";
    		mail($GLOBALS["fp_system_settings"]["notify_mysql_error_email_address"], "FlightPath MYSQL Error Reported on $server", $email_msg);
    	}
    
    	if (isset($GLOBALS["fp_system_settings"]["display_mysql_errors"]) &&  $GLOBALS["fp_system_settings"]["display_mysql_errors"] != FALSE) {
    	  $pC .= "<br><br>_error:<br>" . mysql_error();
    	}
    	
    	$pC .= "</div>";
    	print $pC;	  	  
	  
	}
	
	
	function request_new_group_id()
	{
		// Return a valid new group_id...

		for ($t = 0; $t < 100; $t++)
		{
			$id = mt_rand(1,9999999);
			// Check for collisions...
			$res4 = $this->db_query("SELECT * FROM draft_group_requirements
							WHERE group_id = '$id' LIMIT 1");
			if ($this->db_num_rows($res4) == 0)
			{ // Was not in the table already, so use it!
				return $id;
			}
		}

		return false;

	}



	function request_new_course_id()
	{
		// Return a valid new course_id...

		for ($t = 0; $t < 100; $t++)
		{
			$id = mt_rand(1,9999999);
			// Check for collisions...
			$res4 = $this->db_query("SELECT * FROM draft_courses
							WHERE course_id = '$id' LIMIT 1");
			if ($this->db_num_rows($res4) == 0)
			{ // Was not in the table already, so use it!
				return $id;
			}
		}

		return false;

	}



	function load_course_descriptive_data($course = null, $course_id = 0)
	{

		$current_catalog_year = $GLOBALS["setting_current_catalog_year"]; // currentCatalogYear.
		$catalog_year = $GLOBALS["setting_current_catalog_year"]; // currentCatalogYear.
		if ($course != null)
		{
			$course_id = $course->course_id;
			$catalog_year = $course->catalog_year;
		}

		
		$cache_catalog_year = $catalog_year;

		$cache_catalog_year = 0;

		$array_valid_names = array();
		// First-- is this course in our GLOBALS cache for courses?
		// If it is, then load from that.
		if ($bool_load_from_global_cache == true &&
		$GLOBALS["fp_course_inventory"][$course_id][$cache_catalog_year]["subject_id"] != "")
		{
			$subject_id = $GLOBALS["fp_course_inventory"][$course_id][$cache_catalog_year]["subject_id"];
			$course_num = $GLOBALS["fp_course_inventory"][$course_id][$cache_catalog_year]["course_num"];
			$title = $GLOBALS["fp_course_inventory"][$course_id][$cache_catalog_year]["title"];
			$description = $GLOBALS["fp_course_inventory"][$course_id][$cache_catalog_year]["description"];
			$min_hours = $GLOBALS["fp_course_inventory"][$course_id][$cache_catalog_year]["min_hours"];
			$max_hours = $GLOBALS["fp_course_inventory"][$course_id][$cache_catalog_year]["max_hours"];
			$repeat_hours = $GLOBALS["fp_course_inventory"][$course_id][$cache_catalog_year]["repeat_hours"];
			$db_exclude = $GLOBALS["fp_course_inventory"][$course_id][$cache_catalog_year]["db_exclude"];
			$array_valid_names = $GLOBALS["fp_course_inventory"][$course_id][$cache_catalog_year]["array_valid_names"];
			//admin_debug("loaded from gb cache.");
			// load this into the course object, if not null.

			return;
		}


		if ($course_id != 0)
		{
			$res = $this->db_query("SELECT * FROM courses
							WHERE course_id = '?' 
							AND catalog_year = '?'
							AND catalog_year <= '?' 
							AND delete_flag = '0' 
							AND exclude = '0' ", $course_id, $catalog_year, $current_catalog_year);
			$cur = $this->db_fetch_array($res);

			if ($this->db_num_rows($res) < 1)
			{
			  
				// No results found, so instead pick the most recent
				// catalog year that is not excluded (keeping below the
				// current catalog year from the settings)

				//$this2 = new DatabaseHandler();
				$res2 = $this->db_query("SELECT * FROM courses
							WHERE `course_id`='?' 
							AND `subject_id`!='' 
							AND `delete_flag` = '0' 
							AND `exclude`='0'
							AND `catalog_year` <= '?'
							ORDER BY `catalog_year` DESC LIMIT 1", $course_id, $current_catalog_year);
				$cur = $this->db_fetch_array($res2);
				//admin_debug("courses row: {$cur["id"]}");
				if ($this->db_num_rows($res2) < 1)
				{
					//admin_debug("in here");
					// Meaning, there were no results found that didn't have
					// the exclude flag set.  So, as a last-ditch effort,
					// go ahead and try to retrieve any course, even if it has
					// been excluded. (keeping below the
				  // current catalog year from the settings)
					
					//$this3 = new DatabaseHandler();
					//
					$res3 = $this->db_query("SELECT * FROM courses
							WHERE course_id = '?' 
							AND subject_id != '' 
							AND delete_flag = '0' 
						  AND catalog_year <= '?'	
							ORDER BY catalog_year DESC LIMIT 1", $course_id, $current_catalog_year);
					$cur = $this->db_fetch_array($res3);

				}

			}


			$title = $cur["title"];
			$description = trim($cur["description"]);
			$subject_id = trim(strtoupper($cur["subject_id"]));
			$course_num = trim(strtoupper($cur["course_num"]));

			//admin_debug("  got $subject_id $course_num ");


			if ($min_hours < 1)
			{
				$min_hours = $cur["min_hours"];
				$max_hours = $cur["max_hours"];
				$repeat_hours = $cur["repeat_hours"];
				if ($repeat_hours*1 < 1)
				{
					$repeat_hours = $max_hours;
				}
			}

			$db_exclude = $cur["exclude"];
			$data_entry_comment = $cur["data_entry_comment"];

			// Now, lets get a list of all the valid names for this course.
			// In other words, all the non-excluded names.  For most
			// courses, this will just be one name.  But for cross-listed
			// courses, this will be 2 or more (probably just 2 though).
			// Example: MATH 373 and CSCI 373 are both valid names for that course.
			$res = $this->db_query("SELECT * FROM courses
										WHERE course_id = '?'
										AND exclude = '0' ", $course_id);
			while($cur = $this->db_fetch_array($res))
			{
				$si = $cur["subject_id"];
				$cn = $cur["course_num"];
				if (in_array("$si~$cn", $array_valid_names))
				{
					continue;
				}
				$array_valid_names[] = "$si~$cn";
			}


		}





		if ($description == "")
		{
			//admin_debug("here for $course_id");
			$description = "There is no course description available at this time.";
		}

		if ($title == "")
		{
			$title = "$subject_id $course_num";
		}


		// Now, to reduce the number of database calls in the future, save this
		// to our GLOBALS cache...

		$GLOBALS["fp_course_inventory"][$course_id][$cache_catalog_year]["subject_id"] = $subject_id;
		$GLOBALS["fp_course_inventory"][$course_id][$cache_catalog_year]["course_num"] = $course_num;
		$GLOBALS["fp_course_inventory"][$course_id][$cache_catalog_year]["title"] = $title;
		$GLOBALS["fp_course_inventory"][$course_id][$cache_catalog_year]["description"] = $description;
		$GLOBALS["fp_course_inventory"][$course_id][$cache_catalog_year]["min_hours"] = $min_hours;
		$GLOBALS["fp_course_inventory"][$course_id][$cache_catalog_year]["max_hours"] = $max_hours;
		$GLOBALS["fp_course_inventory"][$course_id][$cache_catalog_year]["repeat_hours"] = $repeat_hours;
		$GLOBALS["fp_course_inventory"][$course_id][$cache_catalog_year]["db_exclude"] = $db_exclude;
		$GLOBALS["fp_course_inventory"][$course_id][$cache_catalog_year]["array_valid_names"] = $array_valid_names;

		$GLOBALS["cache_course_inventory"] = true;  //  rebuild this cache before it closes.


		// Should we put all this into our course object?







	}



	function duplicate_course_for_year($course = null, $catalog_year)
	{
		// Duplicate the course for the given catalog_year.
		// If it already exists for that catalog_year, delete it from the
		// table.
		// In other words, copy all course data from some valid year into this
		// new year.

		$c = $course;
		$course_id = $c->course_id;



		$res = $this->db_query("DELETE FROM draft_courses WHERE
							course_id = '?' AND catalog_year = '?' 
								AND subject_id = '?' 
								AND course_num = '?' ", $course_id, $catalog_year, $c->subject_id, $c->course_num);

		$res2 = $this->db_query("INSERT INTO draft_courses(course_id,
								subject_id, course_num, catalog_year,
								title, description, min_hours, max_hours,
								repeat_hours, exclude) values (
								'?','?','?','?','?','?','?','?','?','?') 
								", $course_id, $c->subject_id,$c->course_num,$catalog_year,$c->title,$c->description,$c->min_hours,$c->max_hours,$c->repeat_hours,$c->db_exclude);



	}

	function update_course_requirement_from_name($subject_id, $course_num, $new_course_id)
	{
		// This will convert all instances of subject_id/course_num
		// to use the newCourseID.  It looks through the requirements tables
		// that may have listed it as a requirement.  We will
		// look specifically at the data_entry_value to do some of them.

		// ************  IMPORTANT ****************
		// This is used only by dataentry.  It is intentionally
		// not doing the draft tables!
		
		$res = $this->db_query("UPDATE degree_requirements
								set `course_id`='?'
								where `data_entry_value`='?~?' ", $new_course_id, $subject_id, $course_num) ;

		$res = $this->db_query("UPDATE group_requirements
								SET `course_id`='?'
								WHERE `data_entry_value`='?~?' ", $new_course_id, $subject_id, $course_num) ;



		// Also update substitutions....
		$res = $this->db_query("UPDATE student_substitutions
								SET `sub_course_id`='?'
								WHERE `sub_entry_value`='?~?' ", $new_course_id, $subject_id, $course_num) ;

		$res = $this->db_query("UPDATE student_substitutions
								SET `required_course_id`='?'
								WHERE `required_entry_value`='?~?' ", $new_course_id, $subject_id, $course_num) ;

		// Also the advising histories....
		$res = $this->db_query("UPDATE advised_courses
								SET `course_id`='?'
								WHERE `entry_value`='?~?' ", $new_course_id, $subject_id, $course_num) ;
		
		
		

	}
	
	function add_draft_instruction($text)
	{
		// Adds a new "instruction" to the draft_instructions table.
		// Simple insert.
		$res = $this->db_query("INSERT INTO draft_instructions
								(instruction) VALUES ('?') ", $text);
	}
	

	function update_course_id($from_course_id, $to_course_id, $bool_draft = false)
	{
		// This will convert *all* instances of "fromCourseID"
		// across every table that it is used, to toCourseID.
		// Use this function when you want to change a course's
		// course_id in the database.

		$table_array = array("advised_courses",
		"courses",
		"degree_requirements",
		"group_requirements",
		"student_unassign_group");

		if ($bool_draft)
		{ // only do the draft tables...
			$table_array = array(
			"draft_courses",
			"draft_degree_requirements",
			"draft_group_requirements",
			);
		}


		// Do the tables where it's named "course_id"...
		foreach($table_array as $table_name)
		{

			$res = $this->db_query("UPDATE $table_name
								SET course_id = '?'
								WHERE course_id = '?' ", $to_course_id, $from_course_id);
		}


		$res = $this->db_query("update student_substitutions
						set `required_course_id`='?'
						where `required_course_id`='?' ", $to_course_id, $from_course_id);

		$res = $this->db_query("update student_substitutions
						set `sub_course_id`='?'
						where `sub_course_id`='?' 
						   and `sub_transfer_flag`='0' ", $to_course_id, $from_course_id);

		$res = $this->db_query("update transfer_eqv_per_student
						set `local_course_id`='?'
						where `local_course_id`='?' ", $to_course_id, $from_course_id);



	}


	function get_advising_session_id($faculty_id = 0, $student_id = "", $term_id = "", $degree_id = "", $bool_what_if = false, $bool_draft = true)
	{
		$is_what_if = "0";
		$is_draft = "0";
		$draft_line = " and `is_draft`='$is_draft' ";
		$faculty_line = " and `faculty_id`='$faculty_id' ";

		if ($faculty_id == 0)
		{ // If no faculty is specified, just get the first one to come up.
			$faculty_line = "";
		}

		if ($bool_what_if == true){$is_what_if = "1";}
		if ($bool_draft == true)
		{
			$is_draft = "1";
			$draft_line = "";
			// If we are told to pull up draft, we can safely
			// assume we just want the most recent save, whether it
			// is saved as a draft or not.
		}



		//admin_debug("$student_id, $faculty_id, $term_id, $degree_id, $is_what_if, $is_draft ");
		$query = "select * from advising_sessions
								where
								    `student_id`='$student_id'
								$faculty_line
								and `term_id`='$term_id'
								and `degree_id`='$degree_id'
								and `is_whatif`='$is_what_if'
								$draft_line
								order by `datetime` desc limit 1";
		$result = $this->db_query($query) ;
		//admin_debug($query);
		if ($this->db_num_rows($result) > 0)
		{
			$cur = $this->db_fetch_array($result);
			$advising_session_id = $cur["advising_session_id"];
			//admin_debug(" - $advising_session_id");
			return $advising_session_id;
		}
		return 0;

	}



	function get_group_id($group_name, $catalog_year)
	{

		if ($catalog_year < $GLOBALS["fp_system_settings"]["earliest_catalog_year"])
		{
			$catalog_year = $GLOBALS["fp_system_settings"]["earliest_catalog_year"];
		}

		$res7 = $this->db_query("SELECT * FROM groups
							WHERE `group_name`='?'
							AND `catalog_year`='?'
							 LIMIT 1 ", $group_name, $catalog_year) ;
		if ($this->db_num_rows($res7) > 0)
		{
			$cur7 = $this->db_fetch_array($res7);
			return $cur7["group_id"];
		}
		return false;
	}



	function request_new_degree_id()
	{
		// Return a valid new id...

		for ($t = 0; $t < 100; $t++)
		{
			$id = mt_rand(1,9999999);
			// Check for collisions...
			$res4 = $this->db_query("SELECT * FROM draft_degrees
							WHERE `degree_id`='$id' limit 1");
			if ($this->db_num_rows($res4) == 0)
			{ // Was not in the table already, so use it!
				return $id;
			}
		}

		return false;

	}


	function get_institution_name($institution_id)
	{
		// Return the name of the institution...
		
    $tsettings = $GLOBALS["fp_system_settings"]["extra_tables"]["course_resources:transfer_institutions"];
  	$tf = (object) $tsettings["fields"];  //Convert to object, makes it easier to work with.  
  	$table_name = $tsettings["table_name"];		
		
		$res = $this->db_query("SELECT * FROM $table_name
								where $tf->institution_id = '?' ", $institution_id);
		$cur = $this->db_fetch_array($res);
		return trim($cur[$tf->name]);
	}



	/**
	 * Retrieve a value from the variables table.
	 *
	 * @param string $name
	 */
	function get_variable($name, $default_value = "") {
	  $res = $this->db_query("SELECT value FROM variables
	                         WHERE name = '?' ", $name);
	  $cur = $this->db_fetch_array($res);
	  
	  $val = $cur["value"];
    if ($val == "") {
      $val = $default_value;
    }
	  
	  return $val;
	}
	
	
	/**
	 * Sets a variable's value in the variables table.
	 *
	 * @param unknown_type $name
	 * @param unknown_type $value
	 */
	function set_variable($name, $value) {	  

    $res2 = $this->db_query("REPLACE INTO variables (name, value)
	                            VALUES ('?', '?') ", $name, $value);
	  
	}
	
	
	function get_course_id($subject_id, $course_num, $catalog_year = "", $bool_use_draft = false)
	{
		// Ignore the colon, if there is one.
		if (strpos($course_num,":"))
		{
			//$course_num = substr($course_num,0,-2);
			$temp = split(":", $course_num);
			$course_num = trim($temp[0]);
		}

		
		// Always override if the global variable is set.
		if ($GLOBALS["bool_use_draft"] == true)
		{
			$bool_use_draft = true;
		}
		
		
		$catalog_line = "";

		if ($catalog_year != "")
		{
			$catalog_line = "and `catalog_year`='$catalog_year' ";
		}

		$table_name = "courses";
		if ($bool_use_draft){$table_name = "draft_$table_name";}
		
		$res7 = $this->db_query("SELECT * FROM $table_name
							WHERE subject_id = '?'
							AND course_num = '?'
							$catalog_line
							 ORDER BY catalog_year DESC LIMIT 1 ", $subject_id, $course_num) ;
		if ($this->db_num_rows($res7) > 0)
		{
			$cur7 = $this->db_fetch_array($res7);
			return $cur7["course_id"];
		}
		return false;
	}


	function get_student_settings($student_id)
	{
		// This returns an array (from the xml) of a student's
		// settings in the student_settings table.  It will
		// return FALSE if the student was not in the table.

		$res = $this->db_query("SELECT * FROM student_settings
							WHERE student_id = '?' ", $student_id) ;
		if ($this->db_num_rows($res) < 1)
		{
			return false;
		}

		$cur = $this->db_fetch_array($res);
    
    if (!$rtn = unserialize($cur["settings"])) {
      $rtn = array();
    }

		return $rtn;

	}

	function get_student_catalog_year($student_id) {
    // Let's pull the needed variables out of our settings, so we know what
		// to query, because this is a non-FlightPath table.
		$tsettings = $GLOBALS["fp_system_settings"]["extra_tables"]["human_resources:students"];
		$tf = (object) $tsettings["fields"];  //Convert to object, makes it easier to work with.  
		$table_name = $tsettings["table_name"];		
		
		
    // Let's perform our queries.
		$res = $this->db_query("SELECT * FROM $table_name 
						          WHERE $tf->student_id = '?' ", $student_id);

		
		$cur = $this->db_fetch_array($res);
		$catalog = $cur[$tf->catalog_year];
		
		$temp = explode("-", $catalog);
		return trim($temp[0]);
	}

	
	/**
	 * Returns whatever is in the Rank field for this student.
	 * Ex: JR, SR, FR, etc.
	 *
	 * @param unknown_type $student_id
	 * @return unknown
	 */
	function get_student_rank($student_id) {
    // Let's pull the needed variables out of our settings, so we know what
		// to query, because this is a non-FlightPath table.
		$tsettings = $GLOBALS["fp_system_settings"]["extra_tables"]["human_resources:students"];
		$tf = (object) $tsettings["fields"];  //Convert to object, makes it easier to work with.  
		$table_name = $tsettings["table_name"];		
		
		
    // Let's perform our queries.
		$res = $this->db_query("SELECT * FROM $table_name 
						          WHERE $tf->student_id = '?' ", $student_id);

		
		$cur = $this->db_fetch_array($res);
		$rank = $cur[$tf->rank_code];
				
		return trim($rank);
	}
	
	
  /**
	 * Returns the student's first and last name, put together.
	 * Ex: John Smith or John W Smith.
	 *
	 * @param int $student_id
	 * @return string
	 */
	function get_student_name($student_id, $bool_include_middle = TRUE) {
    // Let's pull the needed variables out of our settings, so we know what
		// to query, because this is a non-FlightPath table.
		$tsettings = $GLOBALS["fp_system_settings"]["extra_tables"]["human_resources:students"];
		$tf = (object) $tsettings["fields"];  //Convert to object, makes it easier to work with.  
		$table_name = $tsettings["table_name"];		
		
		
    // Let's perform our queries.
		$res = $this->db_query("SELECT * FROM $table_name 
						          WHERE $tf->student_id = '?'", $student_id);

		
		$cur = $this->db_fetch_array($res);
		if ($bool_include_middle) {
		  // with middle name
		  $name = $cur[$tf->f_name] . " " . $cur[$tf->mid_name] . " " . $cur[$tf->l_name];
		}
		else {
		  // No middle name
		  $name = $cur[$tf->f_name] . " " . $cur[$tf->l_name];
		}

		// Force into pretty capitalization.
		// turns JOHN SMITH into John Smith	
		$name = ucwords(strtolower($name));
		
		return trim($name);
	}	
	
	
	
  /**
	 * Returns the faculty's first and last name, put together.
	 * Ex: John Smith or John W Smith.
	 *
	 * @param int $faculty_id
	 * @return string
	 */
	function get_faculty_name($faculty_id, $bool_include_middle = TRUE) {
    // Let's pull the needed variables out of our settings, so we know what
		// to query, because this is a non-FlightPath table.
		$tsettings = $GLOBALS["fp_system_settings"]["extra_tables"]["human_resources:faculty_staff"];
		$tf = (object) $tsettings["fields"];  //Convert to object, makes it easier to work with.  
		$table_name = $tsettings["table_name"];		
		
		
    // Let's perform our queries.
		$res = $this->db_query("SELECT * FROM $table_name 
						          WHERE $tf->faculty_id = '?'", $faculty_id);

		
		$cur = $this->db_fetch_array($res);
		if ($bool_include_middle) {
		  // with middle name
		  $name = $cur[$tf->f_name] . " " . $cur[$tf->mid_name] . " " . $cur[$tf->l_name];
		}
		else {
		  // No middle name
		  $name = $cur[$tf->f_name] . " " . $cur[$tf->l_name];
		}

		// Force into pretty capitalization.
		// turns JOHN SMITH into John Smith	
		$name = ucwords(strtolower($name));
		
		return trim($name);
	}	
	
		
	/**
	 * Looks in our extra tables to find out what major code, if any, has been assigned
	 * to this faculty member.
	 *
	 * @param unknown_type $faculty_id
	 */
	function get_faculty_major_code($user_id) {
	  
    // Let's pull the needed variables out of our settings, so we know what
  	// to query, because this is a non-FlightPath table.
  	$tsettings = $GLOBALS["fp_system_settings"]["extra_tables"]["human_resources:faculty_staff"];
  	$tf = (object) $tsettings["fields"];  //Convert to object, makes it easier to work with.  
  	$table_name = $tsettings["table_name"];		  
    
  	$res = $this->db_query("SELECT * FROM $table_name WHERE $tf->faculty_id = '?' ", $user_id);
  	$cur = $this->db_fetch_array($res);
  	
  	return $cur[$tf->major_code];		  
	  
	}
		
	
	function get_student_major_from_db($student_id)
	{
		// Returns the student's major code from the DB.  Does not
		// return the track code.
		
    // Let's pull the needed variables out of our settings, so we know what
		// to query, because this is a non-FlightPath table.
		$tsettings = $GLOBALS["fp_system_settings"]["extra_tables"]["human_resources:students"];
		$tf = (object) $tsettings["fields"];  //Convert to object, makes it easier to work with.  
		$table_name = $tsettings["table_name"];			
		
    // Let's perform our queries.
		$res = $this->db_query("SELECT * FROM $table_name 
						          WHERE $tf->student_id = '?' ", $student_id);
		
		
		$cur = $this->db_fetch_array($res);
		return trim($cur[$tf->major_code]);
	}


	function get_flightpath_settings()
	{
		// Returns an array of everything in the flightpath_settings table.
		$rtn_array = array();
		$res = $this->db_query("SELECT * FROM flightpath_settings ") ;
		while($cur = $this->db_fetch_array($res))
		{
			$rtn_array[$cur["variable_name"]] = trim($cur["value"]);
		}

		return $rtn_array;

	}

	function get_degrees_in_catalog_year($catalog_year, $bool_include_tracks = false, $bool_use_draft = false, $bool_undergrad_only = TRUE)
	{
		// Returns an array of all the degrees from a particular year
		// which are entered into FlightPath.
		
	  $table_name = "degrees";
		if ($bool_use_draft){$table_name = "draft_$table_name";}		
		
		if ($bool_undergrad_only) $undergrad_line = "AND degree_class != 'G' ";
		
		$rtn_array = array();
		$res = $this->db_query("SELECT * FROM $table_name
								WHERE catalog_year = '?' 
								AND exclude = '0'
								$undergrad_line
								ORDER BY title, major_code ", $catalog_year);
		if ($this->db_num_rows($res) < 1)
		{
			return false;
		}

		while ($cur = $this->db_fetch_array($res))
		{
			$degree_id = $cur["degree_id"];
			$major = trim($cur["major_code"]);
			$title = trim($cur["title"]);
			$track_code = "";
			$major_code = $major;
			// The major may have a track specified.  If so, take out
			// the track and make it seperate.
			if (strstr($major, "_"))
			{
				$temp = split("_", $major);
				$major_code = trim($temp[0]);
				$track_code = trim($temp[1]);
				// The major_code might now have a | at the very end.  If so,
				// get rid of it.
				if (substr($major_code, strlen($major_code)-1, 1) == "|")
				{
					$major_code = str_replace("|","",$major_code);
				}


			}

			// Leave the track in if requested.
			if ($bool_include_tracks == true)
			{
				// Set it back to what we got from the db.
				$major_code = $major;
				$temp_degree = $this->get_degree_plan($major, $catalog_year, true);
				if ($temp_degree->track_code != "")
				{
					$title .= " - " . $temp_degree->track_title;
				}
			}

			$rtn_array[$major_code]["title"] = $title;
			$rtn_array[$major_code]["degree_id"] = $degree_id;
			$rtn_array[$major_code]["degree_class"] = trim(strtoupper($cur["degree_class"]));

		}

		return $rtn_array;

	}

	function get_degree_tracks($major_code, $catalog_year)
	{
		// Will return an array of all the tracks that a particular major
		// has.  Must match the major_code in degree_tracks table.
		// Returns FALSE if there are none.
		$rtn_array = array();
		$res = $this->db_query("SELECT * FROM degree_tracks
								WHERE major_code = '?'
								AND catalog_year = '?' ", $major_code, $catalog_year);
		if ($this->db_num_rows($res) < 1)
		{
			return false;
		}

		while($cur = $this->db_fetch_array($res))
		{
			extract($cur, 3, "db");
			$rtn_array[] = $db_track_code;
		}

		return $rtn_array;

	}

	function get_degree_plan($major_and_track_code, $catalog_year = "", $bool_minimal = false)
	{
		// Returns a degreePlan object from the supplied information.
		
		// If catalog_year is blank, use whatever the current catalog year is, loaded from our settings table.
		if ($catalog_year == "") {
		  $catalog_year = $GLOBALS["setting_current_catalog_year"];
		}
		
		$degree_id = $this->get_degree_id($major_and_track_code, $catalog_year);
		$dp = new DegreePlan($degree_id,null,$bool_minimal);
		if ($dp->major_code == "")
		{
			$dp->major_code = trim($major_and_track_code);
		}
		return $dp;
	}

	function get_degree_id($major_and_track_code, $catalog_year, $bool_use_draft = false)
	{
		// This function expects the major_code and track_code (if it exists)
		// to be joined using |_.  Example:
		// GSBA|_123  or  KIND|EXCP_231.
		// In other words, all in one.

		// Always override if the global variable is set.
		if ($GLOBALS["bool_use_draft"] == true)
		{
			$bool_use_draft = true;
		}
		
		

		if ($catalog_year < $GLOBALS["fp_system_settings"]["earliest_catalog_year"])
		{ // Lowest possible year.
			$catalog_year = $GLOBALS["fp_system_settings"]["earliest_catalog_year"];
		}

		$table_name = "degrees";
		if ($bool_use_draft){$table_name = "draft_$table_name";}
		$res7 = $this->db_query("SELECT * FROM $table_name
							WHERE major_code = '?'
							AND catalog_year = '?'
							 LIMIT 1 ", $major_and_track_code, $catalog_year) ;
		if ($this->db_num_rows($res7) > 0)
		{
			$cur7 = $this->db_fetch_array($res7);
			return $cur7["degree_id"];
		}
		return false;

	}


	function db_num_rows($result)	{
		return mysql_num_rows($result);
	}

	function db_affected_rows() {
	   return mysql_affected_rows();
	}
	
	function db_insert_id() {
		return mysql_insert_id();
	}

	function db_fetch_array($result) {
		return mysql_fetch_array($result);
	}

	function db_close() {
		return mysql_close($this->dbc);
	}


}

?>