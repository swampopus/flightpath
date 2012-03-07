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


/*
This lets an advisor
search for students and advise them.
*/

session_start();
header("Cache-control: private");

require_once("bootstrap.inc");

if ($_SESSION["fpLoggedIn"] != true)
{ // If not logged in, show the login screen.
	header("Location: main.php");
	die;
}

if ($_SESSION["fpUserType"] == "student" || $_SESSION["fpCanSearch"] == false)
{ // keep hacker kids out.
	session_destroy();
	die ("You do not have access to this function. <a href='main.php'>Log back into FlightPath.</a>");
}


/////////////////////////////////////
///  Are we trying to save the draft
///  from a tab change?
/////////////////////////////////////
$fp = new FlightPath();
$fp->processRequestSaveDraft();




// While developing...
// (these would normally be set during login)
//$_SESSION["fpUserBudgetCode"] = "10520";  // biology


$screen = new AdvisingScreen("",null,"notAdvising");
$screen->initAdvisingVariables(true);

$student = new Student($GLOBALS["advisingStudentID"]);
$screen->student = $student;
$db = new DatabaseHandler();


$performAction = trim(addslashes($_REQUEST["performAction"]));

// Get search results from POST -or- past search attempts
// from the session.
$searchFor = trim($_REQUEST["searchFor"]);
if ($searchFor == "")
{
	$searchFor = trim($_SESSION["studentSearchFor"]);
}


if ($performAction == "")
{
	if ($searchFor == "")
	{
		$performAction = "list";
	} else {
		$performAction = "search";
	}
}




if ($performAction == "search")
{ // by default, go to the search page.
	displayAdviseeSearch();
	die;
}

if ($performAction == "list")
{ // Just the list of advisees.
	displayAdviseeList();
	die;
}

if ($performAction == "majors")
{ // Just the list of advisees.
	displayAdviseeMajors();
	die;
}




function getAdvisees($sql = "")
{
  // Check for hooks...
  if (function_exists("studentsearch_getAdvisees")) {
    return call_user_func("studentsearch_getAdvisees", $sql);
  }

  
	global $db, $screen;
	$db2 = new DatabaseHandler();

	// Let's pull the needed variables out of our settings, so we know what
	// to query, because this is a non-FlightPath table.
	$tsettings = $GLOBALS["fpSystemSettings"]["extraTables"]["human_resources:advisor_student"];
	$tfa = (object) $tsettings["fields"];  //Convert to object, makes it easier to work with.  
	$tableName_a = $tsettings["tableName"];		

	// Let's pull the needed variables out of our settings, so we know what
	// to query, because this is a non-FlightPath table.
	$tsettings = $GLOBALS["fpSystemSettings"]["extraTables"]["human_resources:students"];
	$tfb = (object) $tsettings["fields"];  //Convert to object, makes it easier to work with.  
	$tableName_b = $tsettings["tableName"];			

  $rankIn = "( '" . join("', '", $GLOBALS["fpSystemSettings"]["allowedStudentRanks"]) . "' )";
	
	
  $orderBy = " $tfb->majorCode, $tfb->lName, $tfb->fName ";
  
	if ($sql == "")
	{
		// By default, just list for me whatever students are in
		// the table as being my advisees.
		$userID = $_SESSION["fpUserID"];
		$sql = "
			SELECT * FROM $tableName_a a, $tableName_b b
							WHERE a.$tfa->studentID = b.$tfb->studentID
							AND a.$tfa->facultyID = '$userID' 
							AND $tfb->rankCode IN %RANKIN%
							%EXTRA_STUDENTSEARCH_CONDITIONS%
							ORDER BY %ORDERBY%
			";
	}

	// Replace the replacement portion with our derrived variables.
	$sql = str_replace("%RANKIN%", $rankIn, $sql);
	$sql = str_replace("%ORDERBY%", $orderBy, $sql);
	$sql = str_replace("%EXTRA_STUDENTSEARCH_CONDITIONS%", $GLOBALS["fpSystemSettings"]["extraStudentSearchConditions"], $sql);
  
	//debugCT($sql);
	// Returns an array of all of this teacher's advisees.
	$rtnArray = array();
	$r = 0;
	
	$result = $db->dbQuery($sql);
	while ($cur = $db->dbFetchArray($result))
	{

		$studentID = trim($cur[$tfb->studentID]);
		$rtnArray[$r]["studentID"] = $studentID;
		$rtnArray[$r]["firstName"] = ucwords(strtolower($cur[$tfb->fName]));
		$rtnArray[$r]["lastName"] = ucwords(strtolower($cur[$tfb->lName]));
		$rtnArray[$r]["rank"] = $cur[$tfb->rankCode];
		$rtnArray[$r]["catalogYear"] = $cur[$tfb->catalogYear];
		$rtnArray[$r]["major"] = $cur[$tfb->majorCode];
		

		// We should also mark if the student has been advised for this semester
		// or not.
		//debugCT("{$GLOBALS["settingAdvisingTermID"]}");
		$advisedImage = "&nbsp;";
		$advisingSessionID = "";
		$res2 = mysql_query("SELECT * FROM advising_sessions WHERE
							`student_id`='$studentID' AND
							`term_id` = '{$GLOBALS["settingAdvisingTermID"]}' 
							 AND `is_draft`='0' 
							ORDER BY `datetime` DESC") or die(mysql_error());
		if (mysql_num_rows($res2) > 0)
		{
			$cur = mysql_fetch_array($res2);

			$advisedImage = "<img src='$screen->themeLocation/images/small_check.gif' class='advisedImage'>";

			if ($cur["is_whatif"] == "1")
			{ // Last advising was a What If advising.
				$advisedImage = "<span title='This student was last advised in What If mode.'><img src='$screen->themeLocation/images/small_check.gif'><sup>wi</sup></span>";
				$dbMajor = $cur["major_code"];
				$temp = split("\|_",$dbMajor);
				$rtnArray[$r]["whatIfMajorCode"] = trim($temp[0]);
				$rtnArray[$r]["whatIfTrackCode"] = trim($temp[1]);
			}
			//$advisingSessionID = trim($cur["advising_session_id"]);
		}


		$rtnArray[$r]["advisingSessionID"] = $advising_session_id;
		//$rtnArray[$r]["hypo_settings"] = $hypo_settings;
		$rtnArray[$r]["advisedImage"] = $advisedImage;


		$r++;
	}



	return $rtnArray;
} // get_advisees



function displayAdviseeMajors()
{
  
  // Check for hooks...
  if (function_exists("studentsearch_displayAdviseeMajors")) {
    return call_user_func("studentsearch_displayAdviseeMajors");
  }
  
  
	global $screen, $db;
	$pC = "";

	// clear search strings
	$pC .= getJS_functions();
	$pC .= $screen->displayGreeting();
	$pC .= drawViewSelector("majors");

	$pC .= $screen->displayBeginSemesterTable();
	$pC .= $screen->drawCurrentlyAdvisingBox(true);

	$facultyUserMajorCode = $_SESSION["fpFacultyUserMajorCode"];

	
	// Let's pull the needed variables out of our settings, so we know what
	// to query, because this is a non-FlightPath table.
	$tsettings = $GLOBALS["fpSystemSettings"]["extraTables"]["human_resources:students"];
	$tf = (object) $tsettings["fields"];  //Convert to object, makes it easier to work with.  
	$tableName = $tsettings["tableName"];				
	$sql = "SELECT * FROM $tableName
	        WHERE substring_index($tf->majorCode, '|', 1) = '$facultyUserMajorCode'
	        AND $tf->rankCode IN %RANKIN%
	        %EXTRA_STUDENTSEARCH_CONDITIONS%
	        ORDER BY %ORDERBY%";	
	

	$advArray = getAdvisees($sql);
	$pC .= "<tr><td valign='top'>";
	$degreePlan = $db->getDegreePlan($facultyUserMajorCode);
	$mm = "";
	if (is_object($degreePlan)) {
	  $mm = ": " . $degreePlan->title;
	}
	
	$s = (count($advArray) == 1) ? "" : "s";	
  $pC .= drawAdvisees($advArray, "Advisees in Major$mm &nbsp; ( " . count($advArray) . " student$s )");	
	
	$pC .= "</td></tr>";


	$db->addToLog("student_search_major", "$mm");

	$pC .= $screen->displayEndSemesterTable();

	$screen->pageContent = $pC;
	$screen->pageHasSearch = true;
	$screen->buildSystemTabs(1);
	// send to the browser
	$screen->outputToBrowser();


} // doDisplayAdviseeMajors



function displayAdviseeList()
{
  // Check for hooks...
  if (function_exists("studentsearch_displayAdviseeList")) {
    return call_user_func("studentsearch_displayAdviseeList");
  }

  
	global $screen, $db;
	// This function only shows the list of advisees assigned to the
	// advisor.
	$pC = "";

	$_SESSION["studentSearchFor"] = "";
	
	$pC .= getJS_functions();
	$pC .= $screen->displayGreeting();
	$pC .= drawViewSelector("advisees");

	$pC .= $screen->displayBeginSemesterTable();
	$pC .= $screen->drawCurrentlyAdvisingBox(true);

	// Get my list of advisees...
	$advArray = getAdvisees();

	$pC .= "<tr><td valign='top'>";
	$s = (count($advArray) == 1) ? "" : "s";	
  $pC .= drawAdvisees($advArray, "List of Advisees &nbsp; ( " . count($advArray) . " student$s )");	

	$pC .= "</td></tr>";
	$pC .= $screen->displayEndSemesterTable();


	/*	$pageHasSearch = true;
	$pageTabs = getTabs();
	$pageContent = $pC;
	include("./template/fp_template.php");
	*/

	$db->addToLog("student_search_advisees");

	$screen->pageContent = $pC;
	$screen->pageHasSearch = true;
	$screen->buildSystemTabs(1);
	// send to the browser
	$screen->outputToBrowser();


} // doDisplayAdviseeList



function drawAdvisees($advArray, $title)
{
  
  // Check for hooks...
  if (function_exists("studentsearch_drawAdvisees")) {
    return call_user_func("studentsearch_drawAdvisess", $advArray, $title);
  }

  
	global $screen;
	// This function will return the HTML to draw out
	// the advisees listed in the advArray.
	$rtn = "";

	$rtn .= $screen->drawCurvedTitle($title);


	$rtn .= "<table width='100%' align='left'
 border='0' cellpadding='0' cellspacing='0'>";

	// Do not show headers at all if mobile
	if (!$screen->pageIsMobile) {
	 $rtn .= "
  	  <td width='5%' valign='top'>&nbsp; </td>
      <td width='12%' valign='top' class='tenpt'><b>CWID</b></td>
      <td width='15%' valign='top' class='tenpt'><b>First Name</b></td>
      <td width='20%' valign='top' class='tenpt'><b>Last Name</b></td>
      <td width='15%' valign='top' class='tenpt'><b>Major Code</b></td>
      <td width='10%' valign='top' class='tenpt'><b>Rank</b></td>
      <td width='15%' valign='top' class='tenpt'><b>Catalog Year</b></td>
      ";
	}	
	
	$rtn .= "
    </tr>";	

	for ($t = 0; $t < count($advArray); $t++)
	{
		$studentID = $advArray[$t]["studentID"];
		$firstName = $advArray[$t]["firstName"];
		$lastName = $advArray[$t]["lastName"];
		$major = $advArray[$t]["major"];
		$advisingWhatIf = $advArray[$t]["advisingWhatIf"];
		$whatIfMajorCode = $advArray[$t]["whatIfMajorCode"];
		$whatIfTrackCode = $advArray[$t]["whatIfTrackCode"];
		$degreeID = $advArray[$t]["degreeID"];
		$rank = $advArray[$t]["rank"];
		$catalogYear = $advArray[$t]["catalogYear"];
		if ($screen->pageIsMobile) {
		  $catalogYear = getShorterCatalogYearRange($catalogYear, false, true);
		}
		$advisingSessionID = $advArray[$t]["advisingSessionID"];
		$advisedImage = $advArray[$t]["advisedImage"];

		$onMouse = "onmouseover=\"style.backgroundColor='#FFFF99'\"
               onmouseout=\"style.backgroundColor='white'\"
                ";
		if ($screen->pageIsMobile) $onMouse = ""; // Causes problems on mobile devices.
		
		$rtn .= "
	    <tr height='19'>
          <td colspan='7'>
		     <table border='0' 
		          $onMouse
               onClick='selectStudent(\"$studentID\",\"$major\",\"$whatIfMajorCode\",\"$whatIfTrackCode\")'
               width='100%' >
              <tr height='20'>
               	<td width='5%' class='hand'>$advisedImage</td>  
		       	<td width='12%' class='hand'><font size='2'>$studentID</font></td>
        	   	<td width='15%' class='hand'><font size='2'>$firstName </font></td>
        		<td width='20%' class='hand'><font size='2'>$lastName </font></td>    
	    		<td width='15%' class='hand'><font size='2'>$major</td>
        		<td width='10%' class='hand'><font size='2'>$rank</td>
        		<td width='15%' class='hand'><font size='2'>$catalogYear</td>
        	   </tr>
              </table>
            </td>	
         </tr>
         ";		



	}


	$rtn .= "</table>";
	// Required to make the changeTab function work...
	$rtn .= "<form id='mainform' method='POST'>
			<input type='hidden' id='scrollTop'>
			<input type='hidden' id='performAction' name='performAction'>
			<input type='hidden' id='advisingWhatIf' name='advisingWhatIf'>
			<input type='hidden' id='currentStudentID' name='currentStudentID'>
			<input type='hidden' id='advisingStudentID' name='advisingStudentID'>
			<input type='hidden' id='advisingMajorCode' name='advisingMajorCode'>
			<input type='hidden' id='whatIfMajorCode' name='whatIfMajorCode'>
			<input type='hidden' id='whatIfTrackCode' name='whatIfTrackCode'>
			<input type='hidden' id='advisingLoadActive' name='advisingLoadActive'>
			<input type='hidden' id='clearSession' name='clearSession'>
			</form>";


	return $rtn;
} //draw_advisees


function drawViewSelector($selectedView)
{
  
  // Check for hooks...
  if (function_exists("studentsearch_drawViewSelector")) {
    return call_user_func("studentsearch_drawViewSelector", $selectedView);
  }

  
	$rtn = "";

	$rtn .= "<div class='tenpt'>";

	$lArray = array("advisees"=>"List My Advisees~studentsearch.php?performAction=list",
	"majors"=>"List Majors~studentsearch.php?performAction=majors",
	"search"=>"Search For Advisees~studentsearch.php?performAction=search");
	foreach($lArray as $key => $value)
	{
		$temp = split("~",$value);
		$title = trim($temp[0]);
		if ($key == $selectedView)
		{
			$title = "<b>$title</b>";
		}
		$action = trim($temp[1]);
		$rtn .= "<a href='javascript: showUpdate(); window.location=\"$action\";' class='nounderline'>$title</a> &nbsp; &nbsp; &nbsp;";
	}
	$rtn .= "</div>";

	return $rtn;
}


function displayAdviseeSearch()
{
  
    // Check for hooks...
  if (function_exists("studentsearch_displayAdviseeSearch")) {
    return call_user_func("studentsearch_displayAdviseeSearch");
  }  

	global $screen, $db, $searchFor;

	$pC = "";

	$pC .= getJS_functions();
	$pC .= $screen->displayGreeting();
	$pC .= drawViewSelector("search");
	$pC .= $screen->displayBeginSemesterTable();
	$pC .= "<form id='mainform' name='mainform' method='post'>";

	$pC .= $screen->drawCurrentlyAdvisingBox(true);

	// Get search results from POST -or- past search attempts
	// from the session.
	$searchFor = trim($_REQUEST["searchFor"]);
	if ($searchFor == "")
	{
		$searchFor = trim($_SESSION["studentSearchFor"]);
	}
	//debugCT($searchFor);
	// remove trouble characters
	$searchFor = str_replace("'","",$searchFor);
	$searchFor = str_replace('"','',$searchFor);
	$searchFor = mysql_real_escape_string($searchFor);

	$isize = "25";
	if ($screen->pageIsMobile) $isize = "10";
	
	$pC .= "<tr><td valign='top'>
	
		<table style='text-align: left; width: 100%; height: 60px;'
 		border='0' cellpadding='0' cellspacing='0'>
	    <tr>
      <td width='30%' align='right'><font size='2'><b>Search for advisees:&nbsp;&nbsp;</b></td> 
      <td width='30%'><input name='searchFor' ID='input_search_for' TYPE='text' SIZE='$isize' value='$searchFor'></font>
      				<input type='hidden' name='didSearch' id='input_didSearch' value='true'></td>
      <td class='tenpt'>";
	$pC .= $screen->drawButton("Search","document.getElementById(\"mainform\").submit();'");
	$pC .= "</td><td width='1'>";
	$pC .= "</td></tr>";
	$pC .= "</table>";


	
	// Let's pull the needed variables out of our settings, so we know what
	// to query, because this is a non-FlightPath table.
	$tsettings = $GLOBALS["fpSystemSettings"]["extraTables"]["human_resources:students"];
	$tf = (object) $tsettings["fields"];  //Convert to object, makes it easier to work with.  
	$tableName = $tsettings["tableName"];			


	//Get my list of advisees...
	// This time, we want to specify an SQL statement that will perform
	// our search.

	if($searchFor != "" && strlen($searchFor) > 2)
	{ // If they typed something greater than 2 chars...
		
		$search_action = "($tf->studentID LIKE '%$searchFor%' 
		                   OR $tf->lName LIKE '%$searchFor%' 
		                   OR $tf->fName LIKE '%$searchFor%') 
		                   AND";
		// If you searched for 2 things seperated by a space, it is likely you
		// are searching for a name, so check that...
		$_SESSION["studentSearchFor"] = $searchFor;
		$temp = split(" ",$searchFor);
		if (trim($temp[1]) != "")
		{
			$fn = trim($temp[0]);
			$ln = trim($temp[1]);
			$search_action = "($tf->lName LIKE '%$ln%' 
			                   AND $tf->fName LIKE '%$fn%') 
			                 AND";
		}

		$temp = split("=",$searchFor);
		if (trim(strtolower($temp[0])) == "major")
		{
			$mjsearch = trim($temp[1]);
			$search_action = "";
			$otherTable = ", degrees b";
			$groupBy = " GROUP BY $tf->studentID ";
			$major_search = " substring_index(a.$tf->majorCode,'|',1) = b.major_code
			                  AND (b.major_code LIKE '%$mjsearch%' OR b.title LIKE '%$mjsearch%') AND ";
		}

		if (md5(strtolower($temp[1]))=="fd89784e59c72499525556f80289b2c7"){$pC .= base64_decode("PGRpdiBjbGFzcz0ndGVucHQnPg0KCQkJCQk8Yj5GbGlnaHRQYXRoIFByb2R1Y3Rpb24gVGVhbTo8L2I+PGJyPg0KCQkJCQlSaWNoYXJkIFBlYWNvY2sgLSBQcmltYXJ5IGFwcGxpY2F0aW9uIGxvZ2ljIGFuZCB3ZWIgaW50ZXJmYWNlIHByb2dyYW1tZXIuPGJyPg0KCQkJCQlKb2UgTWFuc291ciAtIFdlYiBkYXRhYmFzZSBhZG1pbmlzdHJhdG9yIGFuZCBtYWluZnJhbWUgZGF0YSBjb29yZGluYXRvci48YnI+DQoJCQkJCUpvYW5uIFBlcnJlciAtIERhdGEgZW50cnksIHRlc3RpbmcgYW5kIHNvZnR3YXJlIGNvb3JkaW5hdG9yLjxicj4NCgkJCQkJPGI+T3RoZXIgY29udHJpYnV0aW5nIHByb2dyYW1tZXJzOjwvYj4NCgkJCQkJQ2hhcmxlcyBGcm9zdCwgQnJpYW4gVGF5bG9yLCBQYXVsIEd1bGxldHRlLgkJCQkJDQoJCQkJCTwvZGl2Pg==");}

		//changed to new_major
/*		$query = "SELECT student_id, f_name, l_name, new_major, rank, a.major,
							major_description, a.catalog_year FROM human_resources.students a, 
							human_resources.majors b WHERE $search_action 
							substring_index(a.new_major,'|',1) = major_id $major_search
							and rank in ('FR', 'SO', 'JR', 'SR', 'PR')
							ORDER BY new_major, l_name, f_name";	
							
		$query = "SELECT $tf->studentID, $tf->fName, $tf->lName, $tf->majorCode, $tf->rankCode,
		                 a.$tf->majorCode, a.$tf->catalogYear
		          FROM $tableName a, degrees b
		          WHERE 
		             $search_action
		          substring_index(a.$tf->majorCode,'|',1) = b.major_code
		             $major_search
		          AND $tf->rankCode IN %RANKIN%
              %EXTRA_STUDENTSEARCH_CONDITIONS%
							ORDER BY %ORDERBY%
							";
							
							
*/

		$query = "SELECT $tf->studentID, $tf->fName, $tf->lName, $tf->majorCode, $tf->rankCode, a.$tf->catalogYear
		          FROM $tableName a $otherTable
		          WHERE 
		             $search_action
		          
		             $major_search
		          $tf->rankCode IN %RANKIN%
              %EXTRA_STUDENTSEARCH_CONDITIONS%
              $groupBy
							ORDER BY %ORDERBY%
							";

		$advArray = getAdvisees($query);
	}

	$s = (count($advArray) == 1) ? "" : "s";	

	if (count($advArray) == 1 && $_REQUEST["didSearch"] == "true")
	{
		// Since there was only 1 result,
		// Go ahead and redirect to this person...
		// But only if we just typed in the name.  If we switched here
		// from a tab, do nothing-- display normally.
		$studentID = $advArray[0]["studentID"];
		$firstName = $advArray[0]["firstName"];
		$lastName = $advArray[0]["lastName"];
		$major = $advArray[0]["major"];
		$whatIfMajorCode = $advArray[0]["whatIfMajorCode"];
		$whatIfTrackCode = $advArray[0]["whatIfTrackCode"];

		$pC .= "<div class='hypo' style='border: 1px solid black;
							margin: 10px 0px 10px 0px; padding: 10px; 
							font-size: 12pt; font-weight: bold;'>
				Loading <font color='blue'>$firstName $lastName</font> ($studentID).  
					&nbsp; Please wait...
				</div>";
		$pC .= "<script type='text/javascript'>
				function redirOnLoad()
				{				 
				 setTimeout('selectStudent(\"$studentID\",\"$major\",\"$whatIfMajorCode\",\"$whatIfTrackCode\");',0);
				}
                </script>";
		$screen->pageOnLoad = " redirOnLoad(); ";
	}


	$db->addToLog("student_search_search", "$searchFor");


	$pC .= drawAdvisees($advArray, "Search Results &nbsp; ( " . count($advArray) . " student$s )");
	$pC .= "</form>";
	$pC .= $screen->displayEndSemesterTable();

	/*	$pageHasSearch = false;
	$pageContent = $pC;
	$pageTabs = getTabs();
	include("./template/fp_template.php");
	*/

	$screen->pageContent = $pC;
	$screen->pageHasSearch = false;
	$screen->buildSystemTabs(1);
	// send to the browser
	$screen->outputToBrowser();



	die;

} // doDisplayAdviseeSearch





function getJS_functions()
{

  // Check for hooks...
  if (function_exists("studentsearch_getJS_functions")) {
    return call_user_func("studentsearch_getJS_functions");
  }

  
	$tempScreen = new AdvisingScreen();
	$rtn .= "<script type='text/javascript'>
	
	var csid = \"{$GLOBALS["currentStudentID"]}\";
	";
	$rtn .= $tempScreen->getJS_submitForm();
	$rtn .= $tempScreen->getJS_changeTab();


	$rtn .= '

	function selectStudent(studentID, majorCode, whatIfMajorCode, whatIfTrackCode)
	{
		
		var advisingWhatIf = "";
		if (whatIfMajorCode != "")
		{
			advisingWhatIf = "yes";
		}
	
		//alert(studentID);
		document.getElementById("advisingStudentID").value = studentID;
		document.getElementById("currentStudentID").value = studentID;
		document.getElementById("advisingMajorCode").value = majorCode;
		document.getElementById("advisingWhatIf").value = advisingWhatIf;
		document.getElementById("whatIfMajorCode").value = whatIfMajorCode;
		document.getElementById("whatIfTrackCode").value = whatIfTrackCode;
		document.getElementById("advisingLoadActive").value = "yes";
		document.getElementById("clearSession").value = "yes";
		
				
		showUpdate(true);
		
		document.getElementById("mainform").action = "advise.php";
		document.getElementById("mainform").submit();
		
		//window.location="advise.php?advisingStudentID=" + studentID + "&currentStudentID=" + studentID + "&advisingMajorCode=" + majorCode + "&advisingLoadActive=yes&clearSession=yes";
	
	}

		
	</script>
	
';


	return $rtn;
}

/*
// FUNCTION TO USED TO DRAW THE ADVISEE INFORMATION BOX
function draw_advisees_box($rank, $studentTakenArray, $majorRequirementsArray, $selectedCoursesArray, $substitutionArray){
$title = "List of Advisees";
$rtn .= draw_advisees_box_top($title);

$rtn .= draw_box_bottom();
return $rtn;
}
*/
?>