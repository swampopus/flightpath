<?php


class _DatabaseHandler extends stdClass
{
	//
	public $dbc;   // OLD.  DEPRECATED
	public $pdo;

	function __construct()
	{
	 
	  $db_host = $GLOBALS["fp_system_settings"]["db_host"];
	  $db_user = $GLOBALS["fp_system_settings"]["db_user"];
	  $db_pass = $GLOBALS["fp_system_settings"]["db_pass"];
	  $db_name = $GLOBALS["fp_system_settings"]["db_name"];

    // The port is part of the host.  Separate them out.
    $db_port = "3306";
    $temp = explode(":", $db_host);
    
    $db_host = trim($temp[0]);
    $db_host_ip = trim($temp[0]);  // set as same as db_host for now.
    
    if (isset($temp[1])) $db_port = $temp[1];
    
    
    //$this->dbc = mysql_connect ($db_host, $db_user, $db_pass) or die('Could not connect to database: ' . mysql_error());
		//mysql_select_db ($db_name);

		// Connection by IP address is fastest, so let's always try to do that.
		// It can be time-consuming to convert our hostname to IP address.  Cache it in our SESSION
		if (isset($_SESSION["fp_db_host_ip"])) {
		  $db_host_ip = $_SESSION["fp_db_host_ip"];
    }
    else {
 		  // Convert our db_host into an IP address, then save to simple SESSION cache.
		  $db_host_ip = gethostbyname($db_host);
      $_SESSION["fp_db_host_ip"] = $db_host_ip;
    }



		// Connect using PDO
		$this->pdo = new PDO("mysql:host=$db_host_ip;port=$db_port;dbname=$db_name;charset=utf8", $db_user, $db_pass);
		// Set our error handling...  (using "silent" so I can catch errors in try/catch and display them, email, etc, if wanted.)
		$this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
      
	}


  /**
   * This is a PHP "magic" function.  Called during a serialize command.
   * Basically, we aren't trying to save any local variables.
   * In fact, we will get a fatal exception if we try to serialize our PDO connection.
   */
  function __sleep() {
    return array();  
  }

  /**
   * This function is called when this objectis unserialized.  We want to reconnect to the database, so we'll call our constructor. 
   */
  function __wakeup() {
    $this->__construct();
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
	  
    depricated_message();
    
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
    $now = time();
    $query = "INSERT INTO log (user_id,
						user_name, user_type, action, extra_data, notes,
						 ip, posted, from_url) VALUES (
						'$user_id','$user_name','$user_type','$action','$extra_data',
						'$notes',
						'$ip', '$now' ,'$url') ";
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
			$rtn_array["posted"] = $cur["posted"];
      $rtn_array["required_degree_id"] = $cur["required_degree_id"];
      $rtn_array["db_record"] = $cur;
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
								settings, posted)
								VALUES ('?','?', '?' )", $user_id, serialize($user_settings_array), time());

    watchdog("update_user_settings", "Hide charts set to: @hide", array("@hide" => $user_settings_array["hide_charts"]));

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

	


	function get_developmental_requirements($student_cwid)
	{
		// returns an array which states whether or not the student
		// requires any developmental requirements.

		$rtn_array = array();
		
		$res = $this->db_query("SELECT * FROM student_developmentals
							         WHERE student_id = '?' ", $student_cwid);
		while($cur = $this->db_fetch_array($res)) {
			$rtn_array[] = $cur["requirement"];
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
   * This function is used to perform a database query. It uses PDO execute, which will
   * take automatically replace ? with variables you supply as the arguments to this function,
   * or as an array to this function.  Either will work.
   * Do this by using ?, or naming the variable like :name or :age.
   * 
   * For example:
   * $result = $db->db_query("SELECT * FROM table WHERE name = ? and age = ? ", $name, $temp_age);
   * or
   * $result = $db->db_query("SELECT * FROM table WHERE name = ? AND age = ? ", array($name, $temp_age));
   * or
   * $result = $db->db_query("SELECT * FROM table WHERE name = :name ", array(":name" => $name));
   *
   * @param unknown_type $sql_query
   * @return unknown
   */
  function db_query($sql_query) {
    
    // If there were any arguments to this function, then we must first apply
    // replacement patterns.
    $args = func_get_args();
    array_shift($args);
    if (isset($args[0]) && is_array($args[0])) {
      // If the first argument was an array, it means we passed an array of values instead
      // of passing them directly.  So use them directly as our args.
      $args = $args[0];
    }

    // We need to make sure that arguments are passed without being contained in single quotes ('?').  Should be just ?
    $sql_query = str_replace("'?'", "?", $sql_query);

    // If $c (number of replacements performed) does not match the number of replacements
    // specified, warn the user.
    if (substr_count($sql_query, "?") != count($args)) {
      fpm("<br><b>WARNING:</b> Replacement count does not match what was supplied to query: $sql_query<br><br>");
    }    
    
    //////////////////////////////////////////////
    
    // Run the sqlQuery and return the result set.
    if (!isset($this->pdo) || $this->pdo == NULL) fpm(debug_backtrace());
    
    
    try {
      
      $result = $this->pdo->prepare($sql_query);
      $result->execute($args);      
      return $result;
    } 
    catch (Exception $ex) {
      // Some error happened!
      $this->db_error($ex);
    }
    
    /*
    $result = mysql_query($sql_query, $this->dbc);
    if ($result)
    {
      return $result;
    } else {
      // Meaning, the query failed...
      // Do nothing.  Do not attempt to log anything, as that could cause an infinite loop.     
      
      // Display the error on screen
      $this->db_error();
    }
     **/
    
  } // db_query	
	

  /**
   * Draw out the error onto the screen.
   *
   */
  function db_error(Exception $ex)
  {
        
    $arr = $ex->getTrace();
    
    $when_ts = time();
    $when_english = format_date($when_ts);
    
    $message = $ex->getMessage();
    
    // If we are on production, email someone!
    if (@$GLOBALS["fp_system_settings"]["notify_mysql_error_email_address"] != "")
    {
      $server = $_SERVER["SERVER_NAME"];
      $email_msg = t("A MYSQL error has occured in FlightPath.") . "  
      Server: $server
      
      Timestamp: $when_ts ($when_english)
      
      Error:
      $message
      
      Backtrace:
      " . print_r($arr, true) . "
      ";
      mail($GLOBALS["fp_system_settings"]["notify_mysql_error_email_address"], "FlightPath MYSQL Error Reported on $server", $email_msg);
    }
        
    fpm(t("A MySQL error has occured:") . " $message<br><br>" . t("The backtrace:"));
    fpm($arr);

    if (@$GLOBALS["fp_die_mysql_errors"] == TRUE) {
      print "\n<br>The script has stopped executing because of a MySQL error:
                    $message<br>\n
             Please fix the error and try again.<br>\n";
      print "<br><br>Timestamp: $when_ts ($when_english)
              <br><br>Program backtrace:
              <pre>" . print_r($arr, true) . "</pre>";
      die;
    }
    
    // Also, check to see if the mysql_err is because of a lost connection, as in, the
    // server went down.  In that case, we should also terminate immediately, rather
    // than risk spamming an email recipient with error emails.
    if (stristr($message, "Lost connection to MySQL server")
        || stristr($message, "MySQL server has gone away")) {

      print "<h2 style='font-family: Arial, sans serif;'>Database Connection Error</h2>
              <br>
              <div style='font-size: 1.2em; font-family: Arial, sans serif; padding-left: 30px;
                          padding-right: 30px;'>
              Sorry, but it appears the database is currently unavailable.  This may
              simply be part of scheduled maintenance to the database server.  Please
              try again in a few minutes.  If the problem persists for longer
              than an hour, contact your technical support
              staff.
              
              </div>
              
              ";
      die;          
    }
    
    

  }	// db_error
	
	
	
	
	
  /**
   * This function is used to perform a database query.  It can take simple replacement patterns,
   * by using ?.  If you actually need to have a ? in the query, you can escape it with ??.
   * For example:
   * $result = $db->db_query("SELECT * FROM table WHERE name = '?' and age = ? ", $name, $temp_age);
   *
   * @param unknown_type $sql_query
   * @return unknown
   */
	function z__db_query($sql_query) {
	  
	  // If there were any arguments to this function, then we must first apply
	  // replacement patterns.
	  $args = func_get_args();
	  array_shift($args);
    if (isset($args[0]) && is_array($args[0])) {
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
      fpm("<br><b>WARNING:</b> Replacement count does not match what was supplied to query: $sql_query<br><br>");
    }    
    
	  if (count($args) > 0) {
	    // Replace each occurance of "?" with what's in our array.
	    
	    foreach ($args as $replacement) {
	      // Escape the replacement...
	      // The replacement might ALSO have a question mark in it.  Escape that too.
	      if (strpos($replacement, "?") !== 0) {
	        $replacement = str_replace("?", "~ESCAPED_Q_MARK~", $replacement);
        }
        
	      // Because mysql_real_escape_string will allow \' to pass through, I am going to
        // first use mysql_real_escape_string on all slashes.
        $replacement = str_replace("\\" , mysql_real_escape_string("\\"), $replacement);
        // Okay, perform the replacement
	      $replacement = mysql_real_escape_string($replacement);
	      
	      // If we have a $ followed by a number (like $99), preg_replace will remove it.  So, let's escape the $ if so.
	      /// if so.
	      $replacement = addcslashes($replacement, '$');
	      
	      $sql_query = preg_replace("/\?/", $replacement, $sql_query, 1);	
	         
	    }
	    
	  }
	  	  
	  $sql_query = str_replace("~ESCAPED_Q_MARK~", "?", $sql_query);	    
	  
	  //////////////////////////////////////////////
	  
		// Run the sqlQuery and return the result set.
		if (!is_resource($this->dbc)) fpm(debug_backtrace());
		$result = mysql_query($sql_query, $this->dbc);
		if ($result)
		{
			return $result;
		} else {
			// Meaning, the query failed...
			// Do nothing.  Do not attempt to log anything, as that could cause an infinite loop.			
			
			// Display the error on screen
			$this->db_error();
		}
	}


	/**
	 * Draw out the error onto the screen.
	 *
	 * @param unknown_type $sql
	 */
	function z__db_error($msg = "")
	{
	  
	  $arr = debug_backtrace();
    
	  $when_ts = time();
	  $when_english = format_date($when_ts);
	  
	  $mysql_err = mysql_error();
	  
    // If we are on production, email someone!
    if (@$GLOBALS["fp_system_settings"]["notify_mysql_error_email_address"] != "")
    {
      $server = $_SERVER["SERVER_NAME"];
    	$email_msg = t("A MYSQL error has occured in FlightPath.") . "  
    	Server: $server
    	
    	Timestamp: $when_ts ($when_english)
    	
    	Error:
    	$mysql_err
    	
    	Comments:
    	$msg
    	
    	Backtrace:
    	" . print_r($arr, true) . "
    	";
    	mail($GLOBALS["fp_system_settings"]["notify_mysql_error_email_address"], "FlightPath MYSQL Error Reported on $server", $email_msg);
    }
        
    fpm(t("A MySQL error has occured:") . " $mysql_err<br><br>" . t("The backtrace:"));
    fpm($arr);

    if (@$GLOBALS["fp_die_mysql_errors"] == TRUE) {
      print "\n<br>The script has stopped executing because of a MySQL error:
                    $mysql_err<br>\n
             Please fix the error and try again.<br>\n";
      print "<br><br>Timestamp: $when_ts ($when_english)
              <br><br>Program backtrace:
              <pre>" . print_r($arr, true) . "</pre>";
      die;
    }
    
    // Also, check to see if the mysql_err is because of a lost connection, as in, the
    // server went down.  In that case, we should also terminate immediately, rather
    // than risk spamming an email recipient with error emails.
    if (stristr($mysql_err, "Lost connection to MySQL server")
        || stristr($mysql_err, "MySQL server has gone away")) {

      print "<h2 style='font-family: Arial, sans serif;'>Database Connection Error</h2>
              <br>
              <div style='font-size: 1.2em; font-family: Arial, sans serif; padding-left: 30px;
                          padding-right: 30px;'>
              Sorry, but it appears the database is currently unavailable.  This may
              simply be part of scheduled maintenance to the database server.  Please
              try again in a few minutes.  If the problem persists for longer
              than an hour, contact your technical support
              staff.
              
              </div>
              
              ";
      die;          
    }
    
    

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

		$current_catalog_year = variable_get("current_catalog_year", "2006");
		$catalog_year = $current_catalog_year; // currentCatalogYear.
		if ($course != null)
		{
			$course_id = $course->course_id;
			$catalog_year = $course->catalog_year;
		}

		
		$cache_catalog_year = $catalog_year;

		$cache_catalog_year = 0;

		$array_valid_names = array();
		
		/*  NOTE:  This was never running it seems, so commenting it out.
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
			// load this into the course object, if not null.

			return;
		}
    */

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

				if ($this->db_num_rows($res2) < 1)
				{

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

			$min_hours = $cur["min_hours"];
			$max_hours = $cur["max_hours"];
			$repeat_hours = $cur["repeat_hours"];
			if ($repeat_hours*1 < 1)
			{
				$repeat_hours = $max_hours;
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


		$min_hours = $c->min_hours;
		$max_hours = $c->max_hours;
		
		if ($c->bool_ghost_min_hour) {
		  $min_hours = 0;		  
		}
		
		if ($c->bool_ghost_max_hour) {
		  $max_hours = 0;
		}
		

		$res = $this->db_query("DELETE FROM draft_courses WHERE
							course_id = '?' AND catalog_year = '?' 
								AND subject_id = '?' 
								AND course_num = '?' ", $course_id, $catalog_year, $c->subject_id, $c->course_num);

		$res2 = $this->db_query("INSERT INTO draft_courses(course_id,
								subject_id, course_num, catalog_year,
								title, description, min_hours, max_hours,
								repeat_hours, exclude) values (
								'?','?','?','?','?','?','?','?','?','?') 
								", $course_id, $c->subject_id,$c->course_num,$catalog_year,$c->title,$c->description,$min_hours,$max_hours,$c->repeat_hours,$c->db_exclude);



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



		$query = "select * from advising_sessions
								where
								    `student_id`='$student_id'
								$faculty_line
								and `term_id`='$term_id'
								and `degree_id`='$degree_id'
								and `is_whatif`='$is_what_if'
								$draft_line
								order by `posted` desc limit 1";
		$result = $this->db_query($query) ;
		if ($this->db_num_rows($result) > 0)
		{
			$cur = $this->db_fetch_array($result);
			$advising_session_id = $cur["advising_session_id"];
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
		
		$res = $this->db_query("SELECT * FROM transfer_institutions
								where institution_id = '?' ", $institution_id);
		$cur = $this->db_fetch_array($res);
		return trim($cur['name']);
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
			$temp = explode(":", $course_num);
			$course_num = trim($temp[0]);
		}

		
		// Always override if the global variable is set.
		if (@$GLOBALS["fp_advising"]["bool_use_draft"] == true) {
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


	function get_student_settings($student_cwid)
	{
		// This returns an array (from the xml) of a student's
		// settings in the student_settings table.  It will
		// return FALSE if the student was not in the table.

		$res = $this->db_query("SELECT * FROM student_settings
							WHERE student_id = '?' ", $student_cwid) ;
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


  function get_student_cumulative_hours($student_cwid) {
    
    // Let's perform our queries.
    $res = $this->db_query("SELECT * FROM students 
                      WHERE cwid = '?' ", $student_cwid);

    
    $cur = $this->db_fetch_array($res);
    return $cur["cumulative_hours"];
    
  }


  function get_student_gpa($student_cwid) {
    
    // Let's perform our queries.
    $res = $this->db_query("SELECT * FROM students 
                      WHERE cwid = '?' ", $student_cwid);

    
    $cur = $this->db_fetch_array($res);
    return $cur["gpa"];
    
  }



	function get_student_catalog_year($student_cwid) {
   		
    // Let's perform our queries.
		$res = $this->db_query("SELECT * FROM students 
						          WHERE cwid = '?' ", $student_cwid);

		
		$cur = $this->db_fetch_array($res);
		$catalog = $cur["catalog_year"];
		
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
	function get_student_rank($student_cwid) {
		
		
    // Let's perform our queries.
		$res = $this->db_query("SELECT * FROM students 
						          WHERE cwid = '?' ", $student_cwid);

		
		$cur = $this->db_fetch_array($res);
		$rank = $cur["rank_code"];
				
		return trim($rank);
	}
	
	
  /**
	 * Returns the student's first and last name, put together.
	 * Ex: John Smith or John W Smith.
	 *
	 * @param int $student_id
	 * @return string
	 */
	function get_student_name($cwid) {
		
    // Let's perform our queries.
		$res = $this->db_query("SELECT * FROM users 
						          WHERE cwid = '?'
						          AND is_student = 1 ", $cwid);
		
		$cur = $this->db_fetch_array($res);
    $name = $cur["f_name"] . " " . $cur["l_name"];

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
	function get_faculty_name($cwid) {
    // Let's pull the needed variables out of our settings, so we know what
		// to query, because this is a non-FlightPath table.
		//$tsettings = $GLOBALS["fp_system_settings"]["extra_tables"]["human_resources:faculty_staff"];
		//$tf = (object) $tsettings["fields"];  //Convert to object, makes it easier to work with.  
		//$table_name = $tsettings["table_name"];		
		
		
    // Let's perform our queries.
		$res = $this->db_query("SELECT * FROM users 
						          WHERE cwid = '?'
						          AND is_faculty = '1' ", $cwid);

		
		$cur = $this->db_fetch_array($res);
    $name = $cur["f_name"] . " " . $cur["l_name"];


		// Force into pretty capitalization.
		// turns JOHN SMITH into John Smith	
		$name = ucwords(strtolower($name));
		
		return trim($name);
	}	
	
		
	/**
	 * Looks in our extra tables to find out what major code, if any, has been assigned
	 * to this faculty member.
	 *
	 */
	function get_faculty_major_code($faculty_cwid) {
	  
    // Let's pull the needed variables out of our settings, so we know what
  	// to query, because this is a non-FlightPath table.
  	//$tsettings = $GLOBALS["fp_system_settings"]["extra_tables"]["human_resources:faculty_staff"];
  	//$tf = (object) $tsettings["fields"];  //Convert to object, makes it easier to work with.  
  	//$table_name = $tsettings["table_name"];		  
    
  	$res = $this->db_query("SELECT * FROM faculty WHERE cwid = '?' ", $faculty_cwid);
  	$cur = $this->db_fetch_array($res);
  	
  	return $cur["major_code"];		  
	  
	}
		
	
	function get_student_major_from_db($student_cwid)
	{
	  depricated_message("db->get_student_major_from_db() is deprecated. use get_student_major*s* instead.");
		// Returns the student's major code from the DB.  Does not
		// return the track code.
		
    // Let's perform our queries.
		$res = $this->db_query("SELECT * FROM students 
						          WHERE cwid = '?' ", $student_cwid);
		
		
		$cur = $this->db_fetch_array($res);
		return trim($cur["major_code"]);
	}


  /**
   * Returns an array (or CSV string) of major_codes from the student_degrees table for this student.
   *   
   * 
   */
  function get_student_majors_from_db($student_cwid, $bool_return_as_csv = FALSE) {
    // Looks in the student_degrees table and returns an array of major codes.
    $rtn = array();
    
    /*
    if ($is_whatif == -1) {
      $is_whatif = 0;
      if (@$GLOBALS["fp_advising"]["advising_what_if"] == "yes") {
        $is_whatif = 1;
      }
    }
     * 
     */
    
    
    $res = $this->db_query("SELECT * FROM student_degrees
                            WHERE student_id = '?' 
                            ", $student_cwid);
    while ($cur = $this->db_fetch_array($res)) {
      $rtn[] = $cur["major_code"];
    }
    
    if ($bool_return_as_csv) {
      // Instead return a CSV string of these codes.      
      $rtn = join(",", $rtn);
    }
    
    return $rtn;
    
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

	function get_degrees_in_catalog_year($catalog_year, $bool_include_tracks = false, $bool_use_draft = false, $bool_undergrad_only = TRUE, $only_level_nums = array(1,2))
	{
		// Returns an array of all the degrees from a particular year
		// which are entered into FlightPath.
		
	  $table_name = "degrees";
		if ($bool_use_draft){$table_name = "draft_$table_name";}		
		
		// TODO:  change this to be whatever the graduate code actually is.
		if ($bool_undergrad_only) $undergrad_line = "AND degree_level != 'G' ";
		
		$degree_class_line = "";
    if (count($only_level_nums) > 0) {
      $classes = fp_get_degree_classifications();
      foreach ($only_level_nums as $num) {
        foreach ($classes["levels"][$num] as $machine_name => $val) {
          $degree_class_line .= " degree_class = '" . addslashes($machine_name) . "' OR";
        }
      }
      // Remove training "OR" from degree_class_line
      $degree_class_line = substr($degree_class_line, 0, strlen($degree_class_line) - 2);
    }
    
    if ($degree_class_line != "") {
      $degree_class_line = "AND ($degree_class_line)";
    }
    				
		$rtn_array = array();
		$res = $this->db_query("SELECT * FROM $table_name
								WHERE catalog_year = ? 
								AND exclude = '0'
								$undergrad_line
								$degree_class_line
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
				$temp = explode("_", $major);
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
		  $catalog_year = variable_get("current_catalog_year", "2006");
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
		if (@$GLOBALS["fp_advising"]["bool_use_draft"] == true) {
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




  function db_fetch_array($result) {
    if (!is_object($result)) return FALSE;
    
    return $result->fetch(PDO::FETCH_ASSOC);
  }

  function db_fetch_object($result) {
    if (!is_object($result)) return FALSE;
    
    return $result->fetch(PDO::FETCH_OBJ);
  }

  function db_num_rows($result) {
    if (!is_object($result)) return FALSE;
    
    return $result->rowCount();
  }

  function db_affected_rows($result) {
    
    return db_num_rows($result);
  }

  function db_insert_id() {    
    return $this->pdo->lastInsertId();
  }

  function db_close() {
    return $this->pdo = NULL;  // this is all you need to do to close a PDO connection.
  }


///////////////////////////////////////////////////
///////////////////////////////////////////////////
///////////////////////////////////////////////////

	function z__db_num_rows($result)	{
		return mysql_num_rows($result);
	}

	function z__db_affected_rows() {
	   return mysql_affected_rows();
	}
	
	function z__db_insert_id() {
		return mysql_insert_id();
	}

	function z__db_fetch_array($result) {
		return mysql_fetch_array($result);
	}
  
  
  function z__db_fetch_object($result) {
    return mysql_fetch_object($result);
  }
  

	function z__db_close() {
		return mysql_close($this->dbc);
	}

	
	/////////////////////////////////////////////
	/////////////////////////////////////////////
	/////////////////////////////////////////////
	
	

}
