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
	  	  
	  $dbHost = $GLOBALS["fpSystemSettings"]["dbHost"];
	  $dbUser = $GLOBALS["fpSystemSettings"]["dbUser"];
	  $dbPass = $GLOBALS["fpSystemSettings"]["dbPass"];
	  $dbName = $GLOBALS["fpSystemSettings"]["dbName"];
	  
    $this->dbc = mysql_connect ($dbHost, $dbUser, $dbPass) or die('Could not connect to database: ' . mysql_error());
		mysql_select_db ($dbName);

	}



	function getHelpPage($i)
	{
		$rtnArray = array();


		$res = $this->dbQuery("SELECT * FROM help WHERE `id`='?' ", $i);
		$cur = $this->dbFetchArray($res);
		$rtnArray["title"] = trim($cur["title"]);
		$rtnArray["body"] = trim($cur["body"]);

		return $rtnArray;

	}

	
	function addToLog($action, $extraData = "", $notes = "")
	{
		// Add a row to the log table.
		$ip = $_SERVER["REMOTE_ADDR"];
		$url = mysql_real_escape_string($_SERVER["REQUEST_URI"]);
		$userID = $_SESSION["fpUserID"];
		$userType = $_SESSION["fpUserType"]; 
		$userName = mysql_real_escape_string($_SESSION["fpUserName"]);
    $action = mysql_real_escape_string($action);
    $extraData = mysql_real_escape_string($extraData);
    $notes = mysql_real_escape_string($notes);
		
    if ($GLOBALS["fp_page_is_mobile"]) {
      $notes = "M:" . $notes;
    }
    
    // This needs to be mysql_query, instead of "this->dbQuery", because
    // otherwise it might get into an infinite loop.
    $query = "INSERT INTO log (user_id,
						user_name, user_type, action, extra_data, notes,
						 ip, datetime, from_url) VALUES (
						'$userID','$userName','$userType','$action','$extraData',
						'$notes',
						'$ip', NOW() ,'$url') ";
		$res = mysql_query($query) or die(mysql_error() . " - " . $query);


		
		

	}

	/**
	 * Sets the maintenance mode.  $val should be either 0 (off) or 1 (on)
	 *
	 * @param integer $val
	 */
	function setMaintenanceMode($val)
	{
    // Convenience function for setting maintenance mode. 0 = off, 1 = on.
    $this->setSettingsVariable("maintenanceMode", $val);
	}

	
	/**
	 * This attempts to set a variable in flightpath_settings,
	 * creating it if it does not exist.
	 *
	 * @param string $name
	 * @param string $val
	 */
	function setSettingsVariable($name, $val) {
  
    $res = $this->dbQuery("REPLACE INTO flightpath_settings 
		            (`variable_name`, `value`)
								VALUES ('?', '?') ", $name, $val);		  
	  
	}
	
	
	/**
	 * Returns the value in the database table flightpath_settings
	 * for this variable, if it exists.
	 *
	 * @param string $name
	 */
	function getSettingsVariable($name) {
	  
	  $res = $this->dbQuery("SELECT value FROM flightpath_settings
	                         WHERE variable_name = '?' ", $name);
	  $cur = $this->dbFetchArray($res);
	  
	  return $cur["value"];
	  
	}
	
	
	
	function getSubstitutionDetails($subID)
	{
		// Simply returns an associative array containing
		// the details of a substitution.  The subID specified
		// is the actual id of the row of the database in
		// flightpath.student_substitutions.

		$rtnArray = array();

		$res = $this->dbQuery("SELECT * FROM student_substitutions
								WHERE id = '?' ", $subID);
		if ($this->dbNumRows($res) > 0)
		{
			$cur = $this->dbFetchArray($res);
			$rtnArray["facultyID"] = $cur["faculty_id"];
			$rtnArray["remarks"] = trim($cur["sub_remarks"]);
			$rtnArray["requiredCourseID"] = $cur["required_course_id"];
			$rtnArray["requiredGroupID"] = $cur["required_group_id"];
			$rtnArray["datetime"] = $cur["datetime"];
		}

		return $rtnArray;

	}

	function updateUserSettingsFromPost($userID)
	{
		// This will retrieve various user settings from the POST
		// and write them to the user_settings table as XML.
		$db = new DatabaseHandler();

		if ($userID*1 < 1)
		{
			//adminDebug("no userID specified.");
			return false;
		}

		// First, we need to GET the user's settings array...
		if (!$userSettingsArray = $this->getUserSettings($userID))
		{
			// No existing userSettingsArray, or it's corrupted.
			// Make a new one.
			$userSettingsArray = array();
		}

		// Now, update values in the settingsArray, if they are
		// present in the POST.
		if (trim($_POST["hideCharts"]) != "")
		{
			$userSettingsArray["hideCharts"] = trim($_POST["hideCharts"]);
		}

		$xml = fp_arrayToXml("settings", $userSettingsArray);

		// Now, write it back to the settings table...
		$res = $this->dbQuery("REPLACE INTO user_settings(user_id,
								settings_xml, `datetime`)
								VALUES ('?','?',NOW() )", $userID, $xml);

		$db->addToLog("update_user_settings", "hideCharts:{$userSettingsArray["hideCharts"]}");

		return true;



	}

	function getUserSettings($userID)
	{
		// return an array of this user's current settings.

		$res = $this->dbQuery("SELECT * FROM user_settings
									WHERE 
									user_id = '?' ", $userID);
		$cur = $this->dbFetchArray($res);

		$xml = $cur["settings_xml"];
		if ($arr = fp_xmlToArray2($xml))
		{
			return $arr;
		} else {
			return false;
		}


	}

	


	function getDevelopmentalRequirements($studentID)
	{
		// returns an array which states whether or not the student
		// requires any developmental requirements.

    // Let's pull the needed variables out of our settings, so we know what
		// to query, because this is a non-FlightPath table.
		$tsettings = $GLOBALS["fpSystemSettings"]["extraTables"]["course_resources:student_developmentals"];
		$tf = (object) $tsettings["fields"];  //Convert to object, makes it easier to work with.  
		$tableName = $tsettings["tableName"];		
		
		
		$rtnArray = array();
		
		$res = $this->dbQuery("SELECT * FROM $tableName
							         WHERE $tf->studentID = '?' ", $studentID);
		while($cur = $this->dbFetchArray($res)) {
			$rtnArray[] = $cur[$tf->requirement];
		}

		return $rtnArray;

	}



	function getTableTransferDataString($tableName, $tableStructure, $whereClause = "")
	{
		// This function will return a string of all the data
		// in a particular table, formatted with delimeters.
		// %R~ separates rows, %C~ separates columns.
		// We expect the tableStructure to be a csv of the
		// column names.
		$rtn = "";


		$res = mysql_query("select $tableStructure from $tableName $whereClause") or dieAndMail(mysql_error());
		while ($cur = mysql_fetch_row($res))
		{
			$newRow = "";

			foreach($cur as $key => $value)
			{ // put all the values returned together...
				$newRow .= $value . "%C~";
			}
			// Remove last %C%...
			$newRow = substr($newRow, 0, -3);

			// Add it to the rtn...
			$rtn .= $newRow . "%R~";

		}

		// Remove the last %R%...
		$rtn = substr($rtn, 0, -3);

		return $rtn;
	}


	
	/**
	 * This is a simple helper function which "escapes" the question marks (?) in
	 * the string, by changing them to "??".  This makes it suitable for use
	 * within dbQuery(), but not necessary if used as an argument.  Ex:
	 * dbQuery("INSERT ... '" . $db->escapeQuestionMarks($xml) . "' ");  is good.
	 * dbQuery("INSERT ... '?' ", $xml);  is good.   This function not needed.
	 *
	 * @param unknown_type $str
	 */
	function escapeQuestionMarks($str) {
	  $rtn = str_replace("?", "??", $str);
	  return $rtn;
	}
	

  /**
   * This function is used to perform a database query.  It can take simple replacement patterns,
   * by using ?.  If you actually need to have a ? in the query, you can escape it with ??.
   * For example:
   * $result = $db->dbQuery("SELECT * FROM table WHERE name = '?' and age = ? ", $name, $temp_age);
   *
   * @param unknown_type $sqlQuery
   * @return unknown
   */
	function dbQuery($sqlQuery) {
	  
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
    $sqlQuery = str_replace("??", "~ESCAPED_Q_MARK~", $sqlQuery);
    
    // If $c (number of replacements performed) does not match the number of replacements
    // specified, warn the user.
    if (substr_count($sqlQuery, "?") != count($args)) {
      // TODO:  Replace this with a call to something
      // like fp_add_message("Blah blah blah", "warning");
      adminDebug("<br><b>WARNING:</b> Replacement count does not match what was supplied to query: $sqlQuery<br><br>");
    }    
    
	  if (count($args) > 0) {
	    // Replace each occurance of "?" with what's in our array.
	    
	    foreach ($args as $replacement) {
	      // Escape the replacement...
	      $replacement = mysql_real_escape_string($replacement);
	      $sqlQuery = preg_replace("/\?/", $replacement, $sqlQuery, 1);	    
	    }
	    
	  }
	  	  
	  $sqlQuery = str_replace("~ESCAPED_Q_MARK~", "?", $sqlQuery);	    
	  
	  //////////////////////////////////////////////
	  
		// Run the sqlQuery and return the result set.
		$result = mysql_query($sqlQuery, $this->dbc);
		if ($result)
		{
			return $result;
		} else {
			// Meaning, the query failed...
			$errScreen = $this->dbError($sqlQuery);
			$this->addToLog("DB ERROR", mysql_real_escape_string(mysql_error()), mysql_real_escape_string($sqlQuery));
			die($errScreen);
		}
	}


	/**
	 * Draw out the error onto the screen.
	 *
	 * @param unknown_type $sql
	 */
	function dbError($msg = "")
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
    	if ($GLOBALS["fpSystemSettings"]["notifyMySQLErrorEmailAddress"] != "")
    	{
    	  $server = $_SERVER["SERVER_NAME"];
    		$emailMsg = "A MYSQL error has occured in FlightPath.  
    		Server: $server
    		
    		The error:
    		" . mysql_error() . "
    		
    		Comments:
    		$msg
    		";
    		mail($GLOBALS["fpSystemSettings"]["notifyMySQLErrorEmailAddress"], "FlightPath MYSQL Error Reported on $server", $emailMsg);
    	}
    
    	if ($GLOBALS["fpSystemSettings"]["displayMySQLErrors"] == TRUE) {
    	  $pC .= "<br><br>Error:<br>" . mysql_error();
    	}
    	
    	$pC .= "</div>";
    	print $pC;	  	  
	  
	}
	
	
	function requestNewGroupID()
	{
		// Return a valid new group_id...

		for ($t = 0; $t < 100; $t++)
		{
			$id = mt_rand(1,9999999);
			// Check for collisions...
			$res4 = $this->dbQuery("SELECT * FROM draft_group_requirements
							WHERE group_id = '$id' LIMIT 1");
			if ($this->dbNumRows($res4) == 0)
			{ // Was not in the table already, so use it!
				return $id;
			}
		}

		return false;

	}



	function requestNewCourseID()
	{
		// Return a valid new course_id...

		for ($t = 0; $t < 100; $t++)
		{
			$id = mt_rand(1,9999999);
			// Check for collisions...
			$res4 = $this->dbQuery("SELECT * FROM draft_courses
							WHERE course_id = '$id' LIMIT 1");
			if ($this->dbNumRows($res4) == 0)
			{ // Was not in the table already, so use it!
				return $id;
			}
		}

		return false;

	}



	function loadCourseDescriptiveData($course = null, $courseID = 0)
	{

		$currentCatalogYear = $GLOBALS["settingCurrentCatalogYear"]; // currentCatalogYear.
		$catalogYear = $GLOBALS["settingCurrentCatalogYear"]; // currentCatalogYear.
		if ($course != null)
		{
			$courseID = $course->courseID;
			$catalogYear = $course->catalogYear;
		}

		
		$cacheCatalogYear = $catalogYear;

		$cacheCatalogYear = 0;

		$arrayValidNames = array();
		// First-- is this course in our GLOBALS cache for courses?
		// If it is, then load from that.
		if ($boolLoadFromGlobalCache == true &&
		$GLOBALS["fpCourseInventory"][$courseID][$cacheCatalogYear]["subjectID"] != "")
		{
			$subjectID = $GLOBALS["fpCourseInventory"][$courseID][$cacheCatalogYear]["subjectID"];
			$courseNum = $GLOBALS["fpCourseInventory"][$courseID][$cacheCatalogYear]["courseNum"];
			$title = $GLOBALS["fpCourseInventory"][$courseID][$cacheCatalogYear]["title"];
			$description = $GLOBALS["fpCourseInventory"][$courseID][$cacheCatalogYear]["description"];
			$minHours = $GLOBALS["fpCourseInventory"][$courseID][$cacheCatalogYear]["minHours"];
			$maxHours = $GLOBALS["fpCourseInventory"][$courseID][$cacheCatalogYear]["maxHours"];
			$repeatHours = $GLOBALS["fpCourseInventory"][$courseID][$cacheCatalogYear]["repeatHours"];
			$dbExclude = $GLOBALS["fpCourseInventory"][$courseID][$cacheCatalogYear]["dbExclude"];
			$arrayValidNames = $GLOBALS["fpCourseInventory"][$courseID][$cacheCatalogYear]["arrayValidNames"];
			//adminDebug("loaded from gb cache.");
			// load this into the course object, if not null.

			return;
		}


		if ($courseID != 0)
		{
			$res = $this->dbQuery("SELECT * FROM courses
							WHERE course_id = '?' 
							AND catalog_year = '?'
							AND catalog_year <= '?' 
							AND delete_flag = '0' 
							AND exclude = '0' ", $courseID, $catalogYear, $currentCatalogYear);
			$cur = $this->dbFetchArray($res);

			if ($this->dbNumRows($res) < 1)
			{
			  
				// No results found, so instead pick the most recent
				// catalog year that is not excluded (keeping below the
				// current catalog year from the settings)

				//$this2 = new DatabaseHandler();
				$res2 = $this->dbQuery("SELECT * FROM courses
							WHERE `course_id`='?' 
							AND `subject_id`!='' 
							AND `delete_flag` = '0' 
							AND `exclude`='0'
							AND `catalog_year` <= '?'
							ORDER BY `catalog_year` DESC LIMIT 1", $courseID, $currentCatalogYear);
				$cur = $this->dbFetchArray($res2);
				//adminDebug("courses row: {$cur["id"]}");
				if ($this->dbNumRows($res2) < 1)
				{
					//adminDebug("in here");
					// Meaning, there were no results found that didn't have
					// the exclude flag set.  So, as a last-ditch effort,
					// go ahead and try to retrieve any course, even if it has
					// been excluded. (keeping below the
				  // current catalog year from the settings)
					
					//$this3 = new DatabaseHandler();
					//
					$res3 = $this->dbQuery("SELECT * FROM courses
							WHERE course_id = '?' 
							AND subject_id != '' 
							AND delete_flag = '0' 
						  AND catalog_year <= '?'	
							ORDER BY catalog_year DESC LIMIT 1", $courseID, $currentCatalogYear);
					$cur = $this->dbFetchArray($res3);

				}

			}


			$title = $cur["title"];
			$description = trim($cur["description"]);
			$subjectID = trim(strtoupper($cur["subject_id"]));
			$courseNum = trim(strtoupper($cur["course_num"]));

			//adminDebug("  got $subjectID $courseNum ");


			if ($minHours < 1)
			{
				$minHours = $cur["min_hours"];
				$maxHours = $cur["max_hours"];
				$repeatHours = $cur["repeat_hours"];
				if ($repeatHours*1 < 1)
				{
					$repeatHours = $maxHours;
				}
			}

			$dbExclude = $cur["exclude"];
			$dataEntryComment = $cur["data_entry_comment"];

			// Now, lets get a list of all the valid names for this course.
			// In other words, all the non-excluded names.  For most
			// courses, this will just be one name.  But for cross-listed
			// courses, this will be 2 or more (probably just 2 though).
			// Example: MATH 373 and CSCI 373 are both valid names for that course.
			$res = $this->dbQuery("SELECT * FROM courses
										WHERE course_id = '?'
										AND exclude = '0' ", $courseID);
			while($cur = $this->dbFetchArray($res))
			{
				$si = $cur["subject_id"];
				$cn = $cur["course_num"];
				if (in_array("$si~$cn", $arrayValidNames))
				{
					continue;
				}
				$arrayValidNames[] = "$si~$cn";
			}


		}





		if ($description == "")
		{
			//adminDebug("here for $courseID");
			$description = "There is no course description available at this time.";
		}

		if ($title == "")
		{
			$title = "$subjectID $courseNum";
		}


		// Now, to reduce the number of database calls in the future, save this
		// to our GLOBALS cache...

		$GLOBALS["fpCourseInventory"][$courseID][$cacheCatalogYear]["subjectID"] = $subjectID;
		$GLOBALS["fpCourseInventory"][$courseID][$cacheCatalogYear]["courseNum"] = $courseNum;
		$GLOBALS["fpCourseInventory"][$courseID][$cacheCatalogYear]["title"] = $title;
		$GLOBALS["fpCourseInventory"][$courseID][$cacheCatalogYear]["description"] = $description;
		$GLOBALS["fpCourseInventory"][$courseID][$cacheCatalogYear]["minHours"] = $minHours;
		$GLOBALS["fpCourseInventory"][$courseID][$cacheCatalogYear]["maxHours"] = $maxHours;
		$GLOBALS["fpCourseInventory"][$courseID][$cacheCatalogYear]["repeatHours"] = $repeatHours;
		$GLOBALS["fpCourseInventory"][$courseID][$cacheCatalogYear]["dbExclude"] = $dbExclude;
		$GLOBALS["fpCourseInventory"][$courseID][$cacheCatalogYear]["arrayValidNames"] = $arrayValidNames;

		$GLOBALS["cacheCourseInventory"] = true;  //  rebuild this cache before it closes.


		// Should we put all this into our course object?







	}



	function duplicateCourseForYear($course = null, $catalogYear)
	{
		// Duplicate the course for the given catalogYear.
		// If it already exists for that catalogYear, delete it from the
		// table.
		// In other words, copy all course data from some valid year into this
		// new year.

		$c = $course;
		$courseID = $c->courseID;



		$res = $this->dbQuery("DELETE FROM draft_courses WHERE
							course_id = '?' AND catalog_year = '?' 
								AND subject_id = '?' 
								AND course_num = '?' ", $courseID, $catalogYear, $c->subjectID, $c->courseNum);

		$res2 = $this->dbQuery("INSERT INTO draft_courses(course_id,
								subject_id, course_num, catalog_year,
								title, description, min_hours, max_hours,
								repeat_hours, exclude) values (
								'?','?','?','?','?','?','?','?','?','?') 
								", $courseID, $c->subjectID,$c->courseNum,$catalogYear,$c->title,$c->description,$c->minHours,$c->maxHours,$c->repeatHours,$c->dbExclude);



	}

	function updateCourseRequirementFromName($subjectID, $courseNum, $newCourseID)
	{
		// This will convert all instances of subjectID/courseNum
		// to use the newCourseID.  It looks through the requirements tables
		// that may have listed it as a requirement.  We will
		// look specifically at the data_entry_value to do some of them.

		// ************  IMPORTANT ****************
		// This is used only by dataentry.  It is intentionally
		// not doing the draft tables!
		
		$res = $this->dbQuery("UPDATE degree_requirements
								set `course_id`='?'
								where `data_entry_value`='?~?' ", $newCourseID, $subjectID, $courseNum) ;

		$res = $this->dbQuery("UPDATE group_requirements
								SET `course_id`='?'
								WHERE `data_entry_value`='?~?' ", $newCourseID, $subjectID, $courseNum) ;



		// Also update substitutions....
		$res = $this->dbQuery("UPDATE student_substitutions
								SET `sub_course_id`='?'
								WHERE `sub_entry_value`='?~?' ", $newCourseID, $subjectID, $courseNum) ;

		$res = $this->dbQuery("UPDATE student_substitutions
								SET `required_course_id`='?'
								WHERE `required_entry_value`='?~?' ", $newCourseID, $subjectID, $courseNum) ;

		// Also the advising histories....
		$res = $this->dbQuery("UPDATE advised_courses
								SET `course_id`='?'
								WHERE `entry_value`='?~?' ", $newCourseID, $subjectID, $courseNum) ;
		
		
		

	}
	
	function addDraftInstruction($text)
	{
		// Adds a new "instruction" to the draft_instructions table.
		// Simple insert.
		$res = $this->dbQuery("INSERT INTO draft_instructions
								(instruction) VALUES ('?') ", $text);
	}
	

	function updateCourseID($fromCourseID, $toCourseID, $boolDraft = false)
	{
		// This will convert *all* instances of "fromCourseID"
		// across every table that it is used, to toCourseID.
		// Use this function when you want to change a course's
		// courseID in the database.

		$tableArray = array("advised_courses",
		"courses",
		"degree_requirements",
		"group_requirements",
		"student_unassign_group");

		if ($boolDraft)
		{ // only do the draft tables...
			$tableArray = array(
			"draft_courses",
			"draft_degree_requirements",
			"draft_group_requirements",
			);
		}


		// Do the tables where it's named "course_id"...
		foreach($tableArray as $tableName)
		{

			$res = $this->dbQuery("UPDATE $tableName
								SET course_id = '?'
								WHERE course_id = '?' ", $toCourseID, $fromCourseID);
		}


		$res = $this->dbQuery("update student_substitutions
						set `required_course_id`='?'
						where `required_course_id`='?' ", $toCourseID, $fromCourseID);

		$res = $this->dbQuery("update student_substitutions
						set `sub_course_id`='?'
						where `sub_course_id`='?' 
						   and `sub_transfer_flag`='0' ", $toCourseID, $fromCourseID);

		$res = $this->dbQuery("update transfer_eqv_per_student
						set `local_course_id`='?'
						where `local_course_id`='?' ", $toCourseID, $fromCourseID);



	}


	function getAdvisingSessionID($facultyID = 0, $studentID = "", $termID = "", $degreeID = "", $boolWhatIf = false, $boolDraft = true)
	{
		$isWhatIf = "0";
		$isDraft = "0";
		$draftLine = " and `is_draft`='$isDraft' ";
		$facultyLine = " and `faculty_id`='$facultyID' ";

		if ($facultyID == 0)
		{ // If no faculty is specified, just get the first one to come up.
			$facultyLine = "";
		}

		if ($boolWhatIf == true){$isWhatIf = "1";}
		if ($boolDraft == true)
		{
			$isDraft = "1";
			$draftLine = "";
			// If we are told to pull up draft, we can safely
			// assume we just want the most recent save, whether it
			// is saved as a draft or not.
		}



		//adminDebug("$studentID, $facultyID, $termID, $degreeID, $isWhatIf, $isDraft ");
		$query = "select * from advising_sessions
								where
								    `student_id`='$studentID'
								$facultyLine
								and `term_id`='$termID'
								and `degree_id`='$degreeID'
								and `is_whatif`='$isWhatIf'
								$draftLine
								order by `datetime` desc limit 1";
		$result = $this->dbQuery($query) ;
		//adminDebug($query);
		if ($this->dbNumRows($result) > 0)
		{
			$cur = $this->dbFetchArray($result);
			$advisingSessionID = $cur["advising_session_id"];
			//adminDebug(" - $advisingSessionID");
			return $advisingSessionID;
		}
		return 0;

	}



	function getGroupID($groupName, $catalogYear)
	{

		if ($catalogYear < $GLOBALS["fpSystemSettings"]["earliestCatalogYear"])
		{
			$catalogYear = $GLOBALS["fpSystemSettings"]["earliestCatalogYear"];
		}

		$res7 = $this->dbQuery("SELECT * FROM groups
							WHERE `group_name`='?'
							AND `catalog_year`='?'
							 LIMIT 1 ", $groupName, $catalogYear) ;
		if ($this->dbNumRows($res7) > 0)
		{
			$cur7 = $this->dbFetchArray($res7);
			return $cur7["group_id"];
		}
		return false;
	}



	function requestNewDegreeID()
	{
		// Return a valid new id...

		for ($t = 0; $t < 100; $t++)
		{
			$id = mt_rand(1,9999999);
			// Check for collisions...
			$res4 = $this->dbQuery("SELECT * FROM draft_degrees
							WHERE `degree_id`='$id' limit 1");
			if ($this->dbNumRows($res4) == 0)
			{ // Was not in the table already, so use it!
				return $id;
			}
		}

		return false;

	}


	function getInstitutionName($institutionID)
	{
		// Return the name of the institution...
		
    $tsettings = $GLOBALS["fpSystemSettings"]["extraTables"]["course_resources:transfer_institutions"];
  	$tf = (object) $tsettings["fields"];  //Convert to object, makes it easier to work with.  
  	$tableName = $tsettings["tableName"];		
		
		$res = $this->dbQuery("SELECT * FROM $tableName
								where $tf->institutionID = '?' ", $institutionID);
		$cur = $this->dbFetchArray($res);
		return trim($cur[$tf->name]);
	}



	/**
	 * Retrieve a value from the variables table.
	 *
	 * @param string $name
	 */
	function getVariable($name, $defaultValue = "") {
	  $res = $this->dbQuery("SELECT value FROM variables
	                         WHERE name = '?' ", $name);
	  $cur = $this->dbFetchArray($res);
	  
	  $val = $cur["value"];
    if ($val == "") {
      $val = $defaultValue;
    }
	  
	  return $val;
	}
	
	
	/**
	 * Sets a variable's value in the variables table.
	 *
	 * @param unknown_type $name
	 * @param unknown_type $value
	 */
	function setVariable($name, $value) {	  

	  $name = mysql_real_escape_string($name);
	  $value = mysql_real_escape_string($value);
	  
    $res2 = $this->dbQuery("REPLACE INTO variables (name, value)
	                            VALUES ('?', '?') ", $name, $value);
	  
	}
	
	
	function getCourseID($subjectID, $courseNum, $catalogYear = "", $boolUseDraft = false)
	{
		// Ignore the colon, if there is one.
		if (strpos($courseNum,":"))
		{
			//$courseNum = substr($courseNum,0,-2);
			$temp = split(":", $courseNum);
			$courseNum = trim($temp[0]);
		}

		
		// Always override if the global variable is set.
		if ($GLOBALS["boolUseDraft"] == true)
		{
			$boolUseDraft = true;
		}
		
		
		$catalogLine = "";

		if ($catalogYear != "")
		{
			$catalogLine = "and `catalog_year`='$catalogYear' ";
		}

		$tableName = "courses";
		if ($boolUseDraft){$tableName = "draft_$tableName";}
		
		$res7 = $this->dbQuery("SELECT * FROM $tableName
							WHERE subject_id = '?'
							AND course_num = '?'
							$catalogLine
							 ORDER BY catalog_year DESC LIMIT 1 ", $subjectID, $courseNum) ;
		if ($this->dbNumRows($res7) > 0)
		{
			$cur7 = $this->dbFetchArray($res7);
			return $cur7["course_id"];
		}
		return false;
	}


	function getStudentSettings($studentID)
	{
		// This returns an array (from the xml) of a student's
		// settings in the student_settings table.  It will
		// return FALSE if the student was not in the table.

		$res = $this->dbQuery("SELECT * FROM student_settings
							WHERE student_id = '?' ", $studentID) ;
		if ($this->dbNumRows($res) < 1)
		{
			return false;
		}

		$cur = $this->dbFetchArray($res);
		$xml = $cur["settings_xml"];
		if ($xml == "")
		{
			return false;
		}

		if (!$xmlArray = fp_xmlToArray2($xml))
		{
			return false;
		}

		return $xmlArray;

	}

	function getStudentCatalogYear($studentID) {
    // Let's pull the needed variables out of our settings, so we know what
		// to query, because this is a non-FlightPath table.
		$tsettings = $GLOBALS["fpSystemSettings"]["extraTables"]["human_resources:students"];
		$tf = (object) $tsettings["fields"];  //Convert to object, makes it easier to work with.  
		$tableName = $tsettings["tableName"];		
		
		
    // Let's perform our queries.
		$res = $this->dbQuery("SELECT * FROM $tableName 
						          WHERE $tf->studentID = '?' ", $studentID);

		
		$cur = $this->dbFetchArray($res);
		$catalog = $cur[$tf->catalogYear];
		
		$temp = explode("-", $catalog);
		return trim($temp[0]);
	}

	
	/**
	 * Returns whatever is in the Rank field for this student.
	 * Ex: JR, SR, FR, etc.
	 *
	 * @param unknown_type $studentID
	 * @return unknown
	 */
	function getStudentRank($studentID) {
    // Let's pull the needed variables out of our settings, so we know what
		// to query, because this is a non-FlightPath table.
		$tsettings = $GLOBALS["fpSystemSettings"]["extraTables"]["human_resources:students"];
		$tf = (object) $tsettings["fields"];  //Convert to object, makes it easier to work with.  
		$tableName = $tsettings["tableName"];		
		
		
    // Let's perform our queries.
		$res = $this->dbQuery("SELECT * FROM $tableName 
						          WHERE $tf->studentID = '?' ", $studentID);

		
		$cur = $this->dbFetchArray($res);
		$rank = $cur[$tf->rankCode];
				
		return trim($rank);
	}
	
	
  /**
	 * Returns the student's first and last name, put together.
	 * Ex: John Smith or John W Smith.
	 *
	 * @param int $studentID
	 * @return string
	 */
	function getStudentName($studentID, $boolIncludeMiddle = TRUE) {
    // Let's pull the needed variables out of our settings, so we know what
		// to query, because this is a non-FlightPath table.
		$tsettings = $GLOBALS["fpSystemSettings"]["extraTables"]["human_resources:students"];
		$tf = (object) $tsettings["fields"];  //Convert to object, makes it easier to work with.  
		$tableName = $tsettings["tableName"];		
		
		
    // Let's perform our queries.
		$res = $this->dbQuery("SELECT * FROM $tableName 
						          WHERE $tf->studentID = '?'", $studentID);

		
		$cur = $this->dbFetchArray($res);
		if ($boolIncludeMiddle) {
		  // with middle name
		  $name = $cur[$tf->fName] . " " . $cur[$tf->midName] . " " . $cur[$tf->lName];
		}
		else {
		  // No middle name
		  $name = $cur[$tf->fName] . " " . $cur[$tf->lName];
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
	 * @param int $facultyID
	 * @return string
	 */
	function getFacultyName($facultyID, $boolIncludeMiddle = TRUE) {
    // Let's pull the needed variables out of our settings, so we know what
		// to query, because this is a non-FlightPath table.
		$tsettings = $GLOBALS["fpSystemSettings"]["extraTables"]["human_resources:faculty_staff"];
		$tf = (object) $tsettings["fields"];  //Convert to object, makes it easier to work with.  
		$tableName = $tsettings["tableName"];		
		
		
    // Let's perform our queries.
		$res = $this->dbQuery("SELECT * FROM $tableName 
						          WHERE $tf->facultyID = '?'", $facultyID);

		
		$cur = $this->dbFetchArray($res);
		if ($boolIncludeMiddle) {
		  // with middle name
		  $name = $cur[$tf->fName] . " " . $cur[$tf->midName] . " " . $cur[$tf->lName];
		}
		else {
		  // No middle name
		  $name = $cur[$tf->fName] . " " . $cur[$tf->lName];
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
	 * @param unknown_type $facultyID
	 */
	function getFacultyMajorCode($userID) {
	  
    // Let's pull the needed variables out of our settings, so we know what
  	// to query, because this is a non-FlightPath table.
  	$tsettings = $GLOBALS["fpSystemSettings"]["extraTables"]["human_resources:faculty_staff"];
  	$tf = (object) $tsettings["fields"];  //Convert to object, makes it easier to work with.  
  	$tableName = $tsettings["tableName"];		  
    
  	$res = $this->dbQuery("SELECT * FROM $tableName WHERE $tf->facultyID = '?' ", $userID);
  	$cur = $this->dbFetchArray($res);
  	
  	return $cur[$tf->majorCode];		  
	  
	}
		
	
	function getStudentMajorFromDB($studentID)
	{
		// Returns the student's major code from the DB.  Does not
		// return the track code.
		
    // Let's pull the needed variables out of our settings, so we know what
		// to query, because this is a non-FlightPath table.
		$tsettings = $GLOBALS["fpSystemSettings"]["extraTables"]["human_resources:students"];
		$tf = (object) $tsettings["fields"];  //Convert to object, makes it easier to work with.  
		$tableName = $tsettings["tableName"];			
		
    // Let's perform our queries.
		$res = $this->dbQuery("SELECT * FROM $tableName 
						          WHERE $tf->studentID = '?' ", $studentID);
		
		
		$cur = $this->dbFetchArray($res);
		return trim($cur[$tf->majorCode]);
	}


	function getFlightPathSettings()
	{
		// Returns an array of everything in the flightpath_settings table.
		$rtnArray = array();
		$res = $this->dbQuery("SELECT * FROM flightpath_settings ") ;
		while($cur = $this->dbFetchArray($res))
		{
			$rtnArray[$cur["variable_name"]] = trim($cur["value"]);
		}

		return $rtnArray;

	}

	function getDegreesInCatalogYear($catalogYear, $boolIncludeTracks = false, $boolUseDraft = false, $boolUndergradOnly = TRUE)
	{
		// Returns an array of all the degrees from a particular year
		// which are entered into FlightPath.
		
	  $tableName = "degrees";
		if ($boolUseDraft){$tableName = "draft_$tableName";}		
		
		if ($boolUndergradOnly) $undergradLine = "AND degree_class != 'G' ";
		
		$rtnArray = array();
		$res = $this->dbQuery("SELECT * FROM $tableName
								WHERE catalog_year = '?' 
								AND exclude = '0'
								$undergradLine
								ORDER BY title, major_code ", $catalogYear);
		if ($this->dbNumRows($res) < 1)
		{
			return false;
		}

		while ($cur = $this->dbFetchArray($res))
		{
			$degreeID = $cur["degree_id"];
			$major = trim($cur["major_code"]);
			$title = trim($cur["title"]);
			$trackCode = "";
			$majorCode = $major;
			// The major may have a track specified.  If so, take out
			// the track and make it seperate.
			if (strstr($major, "_"))
			{
				$temp = split("_", $major);
				$majorCode = trim($temp[0]);
				$trackCode = trim($temp[1]);
				// The majorCode might now have a | at the very end.  If so,
				// get rid of it.
				if (substr($majorCode, strlen($majorCode)-1, 1) == "|")
				{
					$majorCode = str_replace("|","",$majorCode);
				}


			}

			// Leave the track in if requested.
			if ($boolIncludeTracks == true)
			{
				// Set it back to what we got from the db.
				$majorCode = $major;
				$tempDegree = $this->getDegreePlan($major, $catalogYear, true);
				if ($tempDegree->trackCode != "")
				{
					$title .= " - " . $tempDegree->trackTitle;
				}
			}

			$rtnArray[$majorCode]["title"] = $title;
			$rtnArray[$majorCode]["degreeID"] = $degreeID;
			$rtnArray[$majorCode]["degreeClass"] = trim(strtoupper($cur["degree_class"]));

		}

		return $rtnArray;

	}

	function getDegreeTracks($majorCode, $catalogYear)
	{
		// Will return an array of all the tracks that a particular major
		// has.  Must match the major_code in degree_tracks table.
		// Returns FALSE if there are none.
		$rtnArray = array();
		$res = $this->dbQuery("SELECT * FROM degree_tracks
								WHERE major_code = '?'
								AND catalog_year = '?' ", $majorCode, $catalogYear);
		if ($this->dbNumRows($res) < 1)
		{
			return false;
		}

		while($cur = $this->dbFetchArray($res))
		{
			extract($cur, 3, "db");
			$rtnArray[] = $db_track_code;
		}

		return $rtnArray;

	}

	function getDegreePlan($majorAndTrackCode, $catalogYear = "", $boolMinimal = false)
	{
		// Returns a degreePlan object from the supplied information.
		
		// If catalogYear is blank, use whatever the current catalog year is, loaded from our settings table.
		if ($catalogYear == "") {
		  $catalogYear = $GLOBALS["settingCurrentCatalogYear"];
		}
		
		$degreeID = $this->getDegreeID($majorAndTrackCode, $catalogYear);
		$dp = new DegreePlan($degreeID,null,$boolMinimal);
		if ($dp->majorCode == "")
		{
			$dp->majorCode = trim($majorAndTrackCode);
		}
		return $dp;
	}

	function getDegreeID($majorAndTrackCode, $catalogYear, $boolUseDraft = false)
	{
		// This function expects the majorCode and trackCode (if it exists)
		// to be joined using |_.  Example:
		// GSBA|_123  or  KIND|EXCP_231.
		// In other words, all in one.

		// Always override if the global variable is set.
		if ($GLOBALS["boolUseDraft"] == true)
		{
			$boolUseDraft = true;
		}
		
		

		if ($catalogYear < $GLOBALS["fpSystemSettings"]["earliestCatalogYear"])
		{ // Lowest possible year.
			$catalogYear = $GLOBALS["fpSystemSettings"]["earliestCatalogYear"];
		}

		$tableName = "degrees";
		if ($boolUseDraft){$tableName = "draft_$tableName";}
		$res7 = $this->dbQuery("SELECT * FROM $tableName
							WHERE major_code = '?'
							AND catalog_year = '?'
							 LIMIT 1 ", $majorAndTrackCode, $catalogYear) ;
		if ($this->dbNumRows($res7) > 0)
		{
			$cur7 = $this->dbFetchArray($res7);
			return $cur7["degree_id"];
		}
		return false;

	}


	function dbNumRows($result)	{
		return mysql_num_rows($result);
	}

	function dbAffectedRows() {
	   return mysql_affected_rows();
	}
	
	function dbInsertID() {
		return mysql_insert_id();
	}

	function dbFetchArray($result) {
		return mysql_fetch_array($result);
	}

	function dbClose() {
		return mysql_close($this->dbc);
	}


}

?>