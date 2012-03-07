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

class _AdvisingScreen
{
	public $widthArray, $popupWidthArray, $scriptFilename, $isOnLeft, $boxArray;
	public $degreePlan, $student, $boolPopup, $footnoteArray, $flightPath;
	public $screenMode, $db, $boolPrint, $view, $settings, $userSettings;
	public $boolBlank, $boolHidingGrades;
	public $adminMessage, $earliestCatalogYear;

	// Variables for the template/theme output...
	public $themeLocation, $pageContent, $pageHasSearch, $pageTabs, $pageOnLoad;
	public $pageHideReportError, $pageScrollTop, $pageIsPopup, $pageIsMobile;
	public $pageTitle, $pageExtraCssFiles;
  


	/**
	 * This is the constructor.  Must be named this for inheritence to work
	 * correctly.
	 *
	 * @param string $scriptFilename
	 *   - This is the script which forms with POST to.  Ex: "advise.php"
	 * 
	 * @param FlightPath $flightPath   
	 *   - FlightPath object.
	 *
	 * @param string $screenMode
	 *   - A string describing what "mode" we are in.  
	 *     - If left blank, we assume it is full-screen and normal.
	 *     - If set to "popup" then we are in a popup window, and we will
	 *       not draw certain elements.
	 *  
	 */
	function __construct($scriptFilename = "", FlightPath $flightPath = null, $screenMode = "")
	{
		$this->widthArray = Array("10%", "10%","10%", "15%", "26%", "10%", "10%", "9%");
		$this->popupWidthArray = Array("17%", "1%", "1%", "15%", "26%", "15%", "15%", "10%");
		
		$this->scriptFilename = $scriptFilename;
		$this->isOnLeft = true;
		$this->boxArray = array();
		$this->footnoteArray = array();
		
		$this->pageExtraCssFiles = array();

		$this->flightPath = $flightPath;
		$this->degreePlan = $flightPath->degreePlan;
		$this->student = $flightPath->student;

		$this->db = getGlobalDatabaseHandler();

		if ($screenMode == "popup")
		{
			$this->boolPopup = true;
		}

		$this->boolBlank = false;

		$this->screenMode = $screenMode;

		$this->settings = $this->db->getFlightPathSettings();
		$this->userSettings = $this->db->getUserSettings($_SESSION["fpUserID"]);

		$this->themeLocation = $GLOBALS["fpSystemSettings"]["theme"];
		if ($this->themeLocation == "") {
		  // Force a default!
		  $this->themeLocation = "themes/classic";
		}
		
		
		$this->earliestCatalogYear = $GLOBALS["fpSystemSettings"]["earliestCatalogYear"];
		
		$this->determineMobileDevice();
				
	}

	

	/**
	 * This function will attempt to determine automatically
	 * if we are on a mobile device.  If so, it will set
	 * $this->pageIsMobile = TRUE
	 *
	 */
function determineMobileDevice(){
  $userAgent = $_SERVER['HTTP_USER_AGENT']; 

  $lookFor = array(
    "ipod", 
    "iphone", 
    "android", 
    "opera mini", 
    "blackberry",
    "(pre\/|palm os|palm|hiptop|avantgo|plucker|xiino|blazer|elaine)",
    "(iris|3g_t|windows ce|opera mobi|windows ce; smartphone;|windows ce; iemobile)",
    "(smartphone|iemobile)",
    );
  
  foreach ($lookFor as $testAgent) {   
    if (preg_match('/' . $testAgent . '/i',$userAgent)) {
       $this->pageIsMobile = true;
       break;
    }
  }  
  
  
  $GLOBALS["fp_page_is_mobile"] = $this->pageIsMobile;
  
} // ends function mobile_device_detect
	


/**
 * This function will return the HTML to contruct a collapsible fieldset,
 * complete with javascript and style tags.
 *
 * @param String $content
 * @param String $legend
 * @param bool $boolStartClosed
 * @return String
 */
function drawCFieldset($content, $legend = "Click to expand/collapse", $boolStartClosed = false)
{
  
  // Create a random ID for this fieldset, js, and styles.
  $id = md5(rand(9,99999) . time());
  
  $start_js_val = 1;
  $fsstate = "open";
  $content_style = "";
  
  if ($boolStartClosed) {
    $start_js_val = 0;
    $fsstate = "closed";
    $content_style = "display: none;";
  }
  
  $js = "<script type='text/javascript'>
  
  var fieldset_state_$id = $start_js_val;
  
  function toggle_fieldset_$id() {
    
    var content = document.getElementById('content_$id');
    var fs = document.getElementById('fs_$id');
      
    if (fieldset_state_$id == 1) {
      // Already open.  Let's close it.
      fieldset_state_$id = 0;
      content.style.display = 'none';
      fs.className = 'c-fieldset-closed-$id';
    }
    else {
      // Was closed.  let's open it.
      fieldset_state_$id = 1;
      content.style.display = '';
      fs.className = 'c-fieldset-open-$id';      
    }  
  }  
  </script>";
  
  $rtn = "  
    <fieldset class='c-fieldset-$fsstate-$id' id='fs_$id'>
      <legend><a href='javascript: toggle_fieldset_$id();' class='nounderline'>$legend</a></legend>
      <div id='content_$id' style='$content_style'>
        $content
      </div>
    </fieldset>
    $js  
    
  <style>
  fieldset.c-fieldset-open-$id {
    border: 1px solid;
  }

  fieldset.c-fieldset-closed-$id {
    border: 1px solid;
    border-bottom-width: 0;
    border-left-width: 0;
    border-right-width: 0;    
  }  

  legend a {
    text-decoration: none;
  }
  
  </style>
    
  ";
  
  
  return $rtn;
}




/**
 * Simply builds a single menu item.
 *
 * @return string
 */
function drawMenuItem($url, $target, $icon_img, $title, $description = "") {
  
  $rtn = "";
  
  if (!$description) $extraClass = "fp-menu-item-tight";
  
  $rtn .= "<div class='fp-menu-item $extraClass'>
            <div class='fp-menu-item-link-line'>
              <a href='$url' target='$target'>$icon_img $title</a>
            </div>
            ";
  if ($description) {
    $rtn .= " <div class='fp-menu-item-description'>$description</div>";
  }
  $rtn .= "</div>";  
  
  return $rtn;
}


/**
 * Uses the drawMenuItem method to draw the HTML for
 * all the supplied menu items, assuming the user has
 * permission to view them.
 * 
 * Returns the HTML or "" if no menus could be drawn.
 *
 * @param unknown_type $menuArray
 */
function drawMenuItems($menuArray) {

  $rtn = "";
  
  if (count($menuArray) == 0) return "";
  
  
  foreach($menuArray as $item) {
    $url = $item["url"];
    $target = $item["target"];
    $icon = $item["icon"];
    if ($icon) {
      $iconImg = "<img src='$icon' border='0'>";
    }
    else {
      $iconImg = "<span class='fp-menu-item-no-icon'></span>";
    }
    
    $title = $item["title"];
    $description = $item["description"];
    
    // Make sure they have permission!
    if ($item["permission"] != "") {
      if (!userHasPermission($item["permission"])) {
        // User did NOT have permission to view this link.
        continue;
      }
    }    
    
    $rtn .= $this->drawMenuItem($url, $target, $iconImg, $title, $description);
    
  }      
  
  return $rtn;
  
}

	
	/**
	 * This method outputs the screen to the browser by performing
	 * an include(path-to-theme-file.php).  All necessary information
	 * must be placed into certain variables before the include happens.
	 * 
	 */
	function outputToBrowser()
	{
		// This method will output the screen to the browser.
		// outputs the $pageContent variable.
				
		$pageContent = $this->pageContent;
		$pageTabs = $this->pageTabs;
		$pageHasSearch = $this->pageHasSearch;
		$pageOnLoad = $this->pageOnLoad;
		$pageScrollTop = $this->pageScrollTop;
		$pageIsPopup = $this->pageIsPopup;
		$pageTitle = $this->pageTitle;
		$pageHideReportError = $this->pageHideReportError;
    $pageExtraCssFiles = $this->pageExtraCssFiles;		
		
		$printOption = "";
		if ($this->boolPrint == true)
		{
			$printOption = "print_";
		}

		if ($this->pageIsMobile == true)
		{
		  $printOption = "mobile_";
		}
					
		include("$this->themeLocation/fp_" . $printOption . "template.php");
	}

	
	
	/**
	 * This function simply adds a reference for additional CSS to be
	 * link'd in to the theme.  It is used by add-on modules.
	 * 
	 * The filename needs to be from the reference of the base
	 * FlightPath install.
	 * 
	 * Ex:  $screen->addCss("modules/course_search/css/style.css");
	 *
	 * @param String $filename
	 */
	function addCss($filename) {

	  $this->pageExtraCssFiles[] = $filename;
	  
	}
	
	
	
	/**
	 * Converts a string containing BBCode to the equivalent HTML.
	 *
	 * @param string $str
	 * @return string
	 */
	function convertBBCodeToHTML($str)
	{
		// This will accept a string with BBcode tags in it,
		// and convert them to HTML tags.
		$str = str_replace("[b]","<b>",$str);
		$str = str_replace("[/b]","</b>",$str);

		$str = str_replace("[i]","<i>",$str);
		$str = str_replace("[/i]","</i>",$str);

		$str = str_replace("[u]","<u>",$str);
		$str = str_replace("[/u]","</u>",$str);

		$str = str_replace("[center]","<center>",$str);
		$str = str_replace("[/center]","</center>",$str);

		$str = str_replace("[ul]","<ul>",$str);
		$str = str_replace("[/ul]","</ul>",$str);

		$str = str_replace("[li]","<li>",$str);
		$str = str_replace("[/li]","</li>",$str);


		$str = str_replace("[br]","<br>",$str);

		// convert more than 1 space into 2 hard spaces...
		$str = str_replace("  ","&nbsp;&nbsp;",$str);


		// Check for colored text
		$str = preg_replace("(\[color=(.+?)\](.+?)\[\/color\])is","<span style='color:$1;'>$2</span>",$str);

		// valid URL characters...
		$urlSearchString = " a-zA-Z0-9\:\/\-\?\&\.\=\_\~\#\'";
		// Check for a link...
		$str = preg_replace("(\[url\=([$urlSearchString]*)\](.+?)\[/url\])", "<a href='$1' target='_blank' class='nounderline'>$2</a>", $str);
		// check for a link that does NOT load in a new window (URL2)
		$str = preg_replace("(\[url2\=([$urlSearchString]*)\](.+?)\[/url2\])", "<a href='$1'>$2</a>", $str);
		// check for a link to a popup....
		$str = preg_replace("(\[popup\=([$urlSearchString]*)\](.+?)\[/popup\])", "<a href='javascript: popupHelpWindow(\"$1\");' class='nounderline'>$2</a>", $str);
		// Images...  (looks like: [img]http://www.image.jpg[/img]
		//$str = preg_replace("(\[img\]([$urlSearchString]*)\](.+?)\[/img\])", "<img src='$1' border='0'>", $str);

		// Images
		// [img]pathtoimage[/img]
		$str = preg_replace("/\[img\](.+?)\[\/img\]/", "<img src='$1' border='0'>", $str);

		// [img=widthxheight]image source[/img]
		$str = preg_replace("/\[img\=([0-9]*)x([0-9]*)\](.+?)\[\/img\]/", "<img src='$3' width='$1' height='$2' border='0'>", $str);



		return $str;
	}


/**
 * Clear the session varibles.
 *
 */
	function clearVariables()
	{
		// Clear the session variables.
		$csid = $_REQUEST["currentStudentID"];

		$_SESSION["advisingStudentID$csid"] = "";
		$_SESSION["advisingStudentID"] = "";
		$_SESSION["advisingMajorCode$csid"] = "";
		$_SESSION["advisingTrackCode$csid"] = "";
		$_SESSION["advisingTermID$csid"] = "";
		$_SESSION["advisingWhatIf$csid"] = "";
		$_SESSION["whatIfMajorCode$csid"] = "";

		$_SESSION["cacheFP$csid"] = "";
		$_SESSION["cacheWhatIf$csid"] = "";

	}

	/**
	 * This method will display the greeting and logoff link which
	 * appears at the top of the page.  If will also display
	 * the urgentMsg, if one exists.  It returns back HTML.
	 *
	 * @return string
	 */
	function displayGreeting()
	{
		// Displays the greeting message & log off link at the top of the page.
		// Also displays the urgentMsg, if it exists.
		$pC = "";

		// Check to see if the GRANT_FULL_ACCESS flag is set.  If so, it
		// represents a major security risk, and the user should be informed.
		if ($GLOBALS["fpSystemSettings"]["GRANT_FULL_ACCESS"] == TRUE) {
		  $pC .= "<div class='fp-warn-grant-full'>
		            <b>Warning:</b> The 'GRANT_FULL_ACCESS' flag has been
		            set in the settings.php file.  This means that any user
		            attempting to log in will be granted full_admin access!
		            Only keep this set during the initial setup of FlightPath
		            or during extreme emergencies.
		          </div>";
		}

		
		if ($this->boolPrint)
		{ // Don't display in Print View.
			return "";
		}

		$name = "";
		$dt = date("D, F jS, Y",time("today"));

		if ($_SESSION["fpUserType"] == "student")
		{
			$name = $this->db->getStudentName($_SESSION["fpUserID"], false);
		} else {
			$name = $this->db->getFacultyName($_SESSION["fpUserID"], false);
		}

		$pC .= "<div class='tenpt'>
					Welcome $name.  ";
		if (!$this->pageIsMobile) {
		  $pC .= "Today is $dt. &nbsp;";
		}
		$pC .= "<a href='main.php?performAction=performLogout'>Logout?</a>
				</div>
					";

		if ($GLOBALS["boolUseDraft"] == true)
		{
			$this->adminMessage .= "<div style='text-align: center;'>Now viewing in <b>Draft</b> Mode.
				<br>Substitutions and advisings will still be saved normally.		
				</div>";
		}

		if ($_SESSION["fpSwitchedUser"] == true)
		{
			$this->adminMessage .= "<div>
										As an admin user, you have switched profiles
										and are now seeing FlightPath as though you were
										<b>{$_SESSION["fpUserName"]}</b>.  To return to your own account, log out,
										then back in.
									</div>";
		}


		if ($this->adminMessage != "")
		{
			$pC .= "<div class='admin-message'>
						<b>Admin Message:</b> $this->adminMessage						
					</div>";
		}



		$uM = "";
		if ($this->settings["urgentMsg"] != "")
		{

			$uM = "<div class='tenpt hypo' style='margin: 10px; padding: 5px;'>
					<b>Important Notice:</b> 
					" . $this->convertBBCodeToHTML($this->settings["urgentMsg"]) . "
					</div>";
		}

		if ($this->settings["maintenanceMode"]*1 > 0)
		{
			// We are in maintenance mode.  Display an appropriate
			// message as an urgentMsg.
			$uM = "<div class='tenpt hypo' style='margin: 10px; padding: 5px;'>
					<b>Important Notice:</b> 
					FlightPath is currently undergoing routine 
					system maintenance, which should last for the next 5-10
					minutes.  During this time you may notice slow load 
					times or inaccurate data.  We apologize for the 
					inconvenience, and thank you for your patience.					
					</div>";
		}


		if ($uM != "")
		{
			$pC .= $uM;
		}


		return $pC;
	}


	/**
	 * Constructs the HTML which will be used to display
	 * the student's transfer credits
	 *
	 */
	function buildTransferCredit()
	{
		$pC = "";
		$isEmpty = true;
		$pC .= $this->drawSemesterBoxTop("Transfer Credit", true);
		// Basically, go through all the courses the student has taken,
		// And only show the transfers.  This is similar to Excess credit.



		$this->student->listCoursesTaken->sortAlphabeticalOrder(false, true);
		$this->student->listCoursesTaken->resetCounter();
		while($this->student->listCoursesTaken->hasMore())
		{
			$course = $this->student->listCoursesTaken->getNext();

			// Skip non transfer credits.
			if ($course->boolTransfer != true)
			{
				continue;
			}

			$boolAddFootnote = false;
			if ($course->boolHasBeenDisplayed == true)
			{ // Show the footnote if this has already been displayed
				// elsewhere on the page.
				$boolAddFootnote = true;
			}

			$pC .= $this->drawCourseRow($course,"","",false,false,$boolAddFootnote,true);
			$isEmpty = false;

		}



		if ($GLOBALS["advisingCourseHasAsterisk"] == true)
		{
			$pC .= "<tr>
				<td colspan='10'>
				<div class='tenpt' style='margin-top: 10px; padding: 3px;'>
				<b>*</b> Courses marked with an asterisk (*) have
					equivalencies at {$GLOBALS["fpSystemSettings"]["schoolInitials"]}.  
					Click on the course for more
					details.			
				</div>		
				</td>
				</tr>
				";
		}

		$pC .= $this->drawSemesterBoxBottom();

		if (!$isEmpty)
		{
			$this->addToScreen($pC);
		}

	}




	/**
	 * Constructs the HTML to show which courses have been added
	 * by an advisor.
	 *
	 */
	function buildAddedCourses()
	{

		$pC = "";


		$semester = new Semester(-88);
		if ($newSemester = $this->degreePlan->listSemesters->findMatch($semester))
		{
			$this->addToScreen($this->displaySemester($newSemester));
		}
	}



  /**
   * Constructs the HTML to show the Excess Credits list.
   *
   */
	function buildExcessCredit()
	{

		$pC = "";
		$pC .= $this->drawSemesterBoxTop("Excess Credits");
		$isEmpty = true;

		// Basically, go through all the courses the student has taken,
		// selecting out the ones that are not fulfilling any
		// requirements.
		$this->student->listCoursesTaken->sortAlphabeticalOrder();
		$this->student->listCoursesTaken->resetCounter();
		while($this->student->listCoursesTaken->hasMore())
		{
			$course = $this->student->listCoursesTaken->getNext();

			if ($course->boolHasBeenDisplayed == true)
			{ // Skip ones which have been assigned to groups or semesters.
				continue;
			}

			// Skip transfer credits.
			if ($course->boolTransfer == true)
			{
				continue;
			}

			// Skip substitutions
			if ($course->boolSubstitution == true)
			{
				continue;
			}
      
			$pC .= $this->drawCourseRow($course,"","",false,false);
			$isEmpty = false;
		}


		$pC .= $this->drawSemesterBoxBottom();

		if (!$isEmpty)
		{
			$this->addToScreen($pC);
		}
	}


	/**
	 * Constructs the HTML which will show footnotes for substitutions
	 * and transfer credits.
	 *
	 */
	function buildFootnotes()
	{
		// Display the footnotes & messages.

		$pC = "";
		$isEmpty = true;
		$pC .= $this->drawSemesterBoxTop("Footnotes & Messages", true);

		$pC .= "<tr><td colspan='8' class='tenpt'>
					";
		$fnTypeArray = array("substitution","transfer");
		$fnChar = array("substitution"=>"S", "transfer"=>"T");
		$fnName = array("substitution"=>"Substitutions", 
		                "transfer"=>"Transfer Equivalency Footnotes");
		$fnBetween = array("substitution"=>"for",
		                   "transfer"=>"for {$GLOBALS["fpSystemSettings"]["schoolInitials"]}'s");
		for ($xx = 0; $xx <= 1; $xx++)
		{
			$fnType = $fnTypeArray[$xx];
			if (count($this->footnoteArray[$fnType]) < 1)
			{
				continue;
			}

			$pC .= "<div style='padding-bottom: 10px;'>
						<b>{$fnName[$fnType]}</b>";
			$isEmpty = false;
			for ($t = 1; $t <= count($this->footnoteArray[$fnType]); $t++)
			{
				$line = $this->footnoteArray[$fnType][$t];

				if ($line == "")
				{
					continue;
				}

				$extra = ".";

				$temp = split(" ~~ ", $line);
				$oCourse = trim($temp[0]);
				$newCourse = trim($temp[1]);
				$usingHours = trim($temp[2]);
				if ($usingHours != "")
				{
					$usingHours = "($usingHours hrs)";
				}
				$inGroup = trim($temp[3]);

				$fbetween = $fnBetween[$fnType];

				if ($inGroup > 0 && $fnType=="substitution")
				{
					$newGroup = new Group();
					$newGroup->groupID = $inGroup;
					$newGroup->loadDescriptiveData();
					$extra = "<div style='padding-left:45px;'><i>in $newGroup->title.</i></div>";
					if ($newCourse == $oCourse || $oCourse == "")
					{
						$oCourse = "was added";
						$fbetween = "";
						$extra = str_replace("<i>in", "<i>to", $extra);
					}
				}



				$pC .= "<div class='tenpt'>&nbsp; &nbsp;
					<sup>{$fnChar[$fnType]}$t</sup>
					$newCourse $usingHours $fbetween $oCourse$extra</div>";

			}
			$pC .= "</div>";
		}


		////////////////////////////////////
		////  Moved Courses...
		$mIsEmpty = true;
		$pC .= "<!--MOVEDCOURSES-->";
		$this->student->listCoursesTaken->sortAlphabeticalOrder();
		$this->student->listCoursesTaken->resetCounter();
		while($this->student->listCoursesTaken->hasMore())
		{
			$c = $this->student->listCoursesTaken->getNext();
			// Skip courses which haven't had anything moved.
			if ($c->groupListUnassigned->isEmpty == true)
			{	continue;	}

			if ($c->courseID > 0)
			{	$c->loadDescriptiveData();	}

			$lSI = $c->subjectID;
			$lCN = $c->courseNum;
			$lTerm = $c->getTermDescription(true);

			$pC .= "<div class='tenpt' style='padding-left: 10px; padding-bottom: 5px;'>
							$lSI $lCN ($c->hoursAwarded hrs) - $c->grade - $lTerm
								";
			
			$c->groupListUnassigned->resetCounter();
			while($c->groupListUnassigned->hasMore())
			{
				$group = $c->groupListUnassigned->getNext();
				$group->loadDescriptiveData();
				$groupTitle = "";
				if ($group->groupID > 0)
				{
					$groupTitle = "<i>$group->title</i>";
				} else {
					$groupTitle = "the degree plan";
				}
				$pC .= "was removed from $groupTitle.
							";
			}



			$pC .= "</div>";

			$mIsEmpty = false;
			$isEmpty = false;
		}

		if ($mIsEmpty == false)
		{
			$mtitle = "<div style='padding-bottom: 10px;'>
						<div style='padding-bottom: 5px;'>
						<b>Moved Courses</b><br>
				Some courses have been moved out of their 
				original positions on your degree plan.</div>";
			$pC = str_replace("<!--MOVEDCOURSES-->",$mtitle,$pC);
			$pC .= "</div>";
		}



		// For admins only....
		if ($_SESSION["fpCanSubstitute"] == true)
		{
			if ($this->boolPrint != true)
			{// Don't display in print view.
				$pC .= "<div style='tenpt'>
					<a href='javascript: popupWindow2(\"toolbox\",\"\");'><img src='$this->themeLocation/images/toolbox.gif' border='0'>Administrator's Toolkit</a>
				</div>";
				$isEmpty = false;
			}
		}


		$pC .= "</td></tr>";

		$pC .= $this->drawSemesterBoxBottom();

		if (!$isEmpty)
		{
			$this->addToScreen($pC);
		}
	}


  /**
   * Used in the Toolbox popup, this will display content of the tab which
   * shows a student's substututions
   *
   * @return string
   */
	function displayToolboxSubstitutions()
	{
		$pC = "";
		// This will display the substitution management screen.

		$pC .= $this->drawCurvedTitle("Manage Substitutions");

		$pC .= "<div class='tenpt'>
				The following substitutions have been made for this student:
				<br><br>
				";
		$isEmpty = true;

		//adminDebug($this->student->listSubstitutions->getSize());

		$this->student->listSubstitutions->resetCounter();
		while ($this->student->listSubstitutions->hasMore())
		{
			$substitution = $this->student->listSubstitutions->getNext();

			$courseRequirement = $substitution->courseRequirement;
			$subbedCourse = $substitution->courseListSubstitutions->getFirst();

			$subSI = $subbedCourse->subjectID;
			$subCN = $subbedCourse->courseNum;

			$crSI = $courseRequirement->subjectID;
			$crCN = $courseRequirement->courseNum;
			$crHrs = $courseRequirement->getHours();

			$inGroup = ".";
			if ($subbedCourse->assignedToGroupID > 0)
			{
				$newGroup = new Group();
				$newGroup->groupID = $subbedCourse->assignedToGroupID;
				$newGroup->loadDescriptiveData();

				$inGroup = " in $newGroup->title.";
			}

			$subAction = "was substituted for";
			$subTransNotice = "";
			if ($substitution->boolGroupAddition == true)
			{
				$subAction = "was added to";
				$crSI = $crCN = "";
				$inGroup = str_replace("in","",$inGroup);
			}

			if ($subbedCourse->boolTransfer == true && is_object($subbedCourse->courseTransfer))
			{
				$subSI = $subbedCourse->courseTransfer->subjectID;
				$subCN = $subbedCourse->courseTransfer->courseNum;
				$subTransNotice = "[transfer]";
			}

			$by = $remarks = "";
			$temp = $this->db->getSubstitutionDetails($subbedCourse->dbSubstitutionID);
			$by = $this->db->getFacultyName($temp["facultyID"], false);
			$remarks = $temp["remarks"];
			$ondate = date("n/d/Y h:i:sa", strtotime($temp["datetime"]));
			
			if ($by != "")
			{
				$by = " <br>&nbsp; &nbsp; Substitutor: $by. 
						<br>&nbsp; &nbsp; <i>$ondate.</i>";
			}

			if ($remarks != "")
			{
				$remarks = " <br>&nbsp; &nbsp; Remarks: <i>$remarks</i>.";
			}


			$extra = "";
			if ($substitution->boolOutdated)
			{
				$extra = " <span style='color:red'>[OUTDATED: ";
				$extra .= $substitution->outdatedNote;
				$extra .= "]</span>";
			}

			$pC .= "<div class='tenpt' style='margin-bottom: 20px;'>
						$subSI $subCN $subTransNotice ($subbedCourse->substitutionHours hrs) $subAction
						$crSI $crCN$inGroup $by$remarks $extra
						<br>
							<a href='javascript: popupRemoveSubstitution(\"$subbedCourse->dbSubstitutionID\");'>Remove substitution?</a>
					</div>";

			$isEmpty = false;
		}

		if ($isEmpty == true)
		{
			$pC .= "<div align='center'>No substitutions have been made for this student.</div>";
		}

		$pC .= "</div>";

		$this->db->addToLog("toolkit", "substitutions");

		return $pC;
	}

  /**
   * Used in the Toolbox popup, this will display content of the tab which
   * shows a student's transfers
   *
   * @return string
   */
	function displayToolboxTransfers()
	{
		$pC = "";
		// This will display the substitution management screen.

		$pC .= $this->drawCurvedTitle("Manage Transfer Equivalencies");

		$pC .= "<div class='tenpt'>
				This student has the following transfer credits and equivalencies.
				<br><br>
				";
		$isEmpty = true;

		$this->student->listCoursesTaken->sortAlphabeticalOrder(false, true);
		$this->student->listCoursesTaken->resetCounter();
		while($this->student->listCoursesTaken->hasMore())
		{
			$c = $this->student->listCoursesTaken->getNext();

			// Skip non transfer credits.
			if ($c->boolTransfer != true)
			{
				continue;
			}

			if ($c->courseID > 0)
			{
				$c->loadDescriptiveData();
			}
			$course = $c->courseTransfer;
			//adminDebug($course->courseID);
			$course->loadDescriptiveTransferData();
			//$course->loadCourse($course->courseID, true);  // reload data for this course.

			$lSI = $c->subjectID;
			$lCN = $c->courseNum;
			$lTitle = $this->fixCourseTitle($c->title);

			$tSI = $course->subjectID;
			$tCN = $course->courseNum;
			$tTerm = $c->getTermDescription(true);
			$grade = $c->grade;
			if ($grade == "W" || $grade == "F" || $grade == "NC" || $grade == "I")
			{
				$grade = "<span style='color: red;'>$grade</span>";
			}
			//$tCourseTitle = $this->fixCourseTitle($course->title);
			$tInst = $this->fixInstitutionName($course->institutionName);

			$pC .= "<div class='tenpt' style='padding-bottom: 15px;'>
							<b>$tSI $tCN</b> ($c->hoursAwarded hrs) - $grade - $tTerm - $tInst
								";
			if ($c->boolSubstitutionSplit == true)
			{
				$pC .= "<div class='tenpt'><b> +/- </b> This course's hours were split in a substitution.</div>";
			}
			$initials = $GLOBALS["fpSystemSettings"]["schoolInitials"];
			// Does this course NOT have an equivalency?
			if ($c->courseID == 0)
			{
				// But, has the eqv been removed?  If so, display a link to restore it,
				// if not, show a link to remove it!
				if ($rC = $this->student->listTransferEqvsUnassigned->findMatch($course))
				{
					// Yes, the eqv WAS removed (or unassigned)
					$pC .= "<div class='tenpt'>This course's $initials equivalency was removed for this student.<br>
							<a href='javascript: popupRestoreTransferEqv(\"$rC->dbUnassignTransferID\")'>Restore?</a></div>";
				} else {
					$pC .= "<div class='tenpt'>$initials equivalency not yet entered (or is not applicable).</div>";
				}
			} else {
				// This course *DOES* have an equivalency.
				$pC .= "<div class='tenpt'>$initials eqv: $lSI $lCN - $lTitle</div>";

				$pC .= "<div class='tenpt' align='right'>
							<a href='javascript: popupUnassignTransferEqv(\"" . $course->courseID . "\");'>Remove this equivalency?</a>
							</div>";

			}

			$pC .= "</div>";

			$isEmpty = false;
		}

		if ($isEmpty == true)
		{
			$pC .= "<div align='center'>There are no transfer equivalencies for this student.</div>";
		}

		$pC .= "</div>";

		$this->db->addToLog("toolkit", "transfers");

		return $pC;
	}

	/**
	 * Displays the pulldown select list for picking a new What If degree.
	 * Returns HTML.
	 *
	 * @return string
	 */
	function displayWhatIfSelection($boolUndergradOnly = TRUE)
	{
		$pC = "";

		if ($this->boolPrint)
		{
			return "";
		}

		$db = new DatabaseHandler();

		$pC .= "<form action='advise.php' id='mainform' method='POST'>";
		$pC .= $this->getJavascriptCode();
		$pC .= $this->displayBeginSemesterTable();

		$pC .= $this->drawCurrentlyAdvisingBox();
		$pC .= "<tr><td colspan='2'>";

		$pC .= $this->drawCurvedTitle("What if I change my major to...");

		$pC .= "<br>
				Major: <select name='whatIfMajorCode' class='what-if-selector'>
					<option value=''>Please select a major</option>
					<option value=''>------------------------------</option>\n
					";
		$currentCatalogYear = $this->settings["currentCatalogYear"];
		//$boolUseDraft = $GLOBALS["boolUseDraft"];
		$boolUseDraft = FALSE;  // leave as false for now.  Because you can't select
		                      // degree options, and if you click submit it really does
		                      // save it.  Better to just use blank degrees.
		if ($degreeArray = $db->getDegreesInCatalogYear($currentCatalogYear, false, $boolUseDraft, $boolUndergradOnly))
		{
			foreach($degreeArray as $majorCode => $value)
			{
				if (trim($value["title"]) == ""){continue;}
				$pC .= "<option value='$majorCode'>{$value["title"]}</option> \n";
			}
		}

		$csid = $GLOBALS["currentStudentID"];
		$pC .= "</select>
				<br><br>";

		$pC .= "
				<div align='right'>
				" . $this->drawButton("Try It Out!", "showUpdate(true);submitForm();") . "				
				<!--	<input type='button' value='Try It Out!' onClick='showUpdate(true);submitForm();'>   -->
					
					<input type='hidden' name='loadFromCache' value='no'>
					<input type='hidden' name='windowMode' value='screen'>
					<input type='hidden' id='scrollTop'>
					<input type='hidden' id='performAction' name='performAction'>
					<input type='hidden' id='advisingWhatIf' name='advisingWhatIf' value='yes'>
					<input type='hidden' id='currentStudentID' name='currentStudentID' value='$csid'>
					
				</div>
				<br><br>
				<div class='hypo tenpt'>
				 <b>Important Notice:</b> What If displays degree plans
				 from the most recent catalog year ($currentCatalogYear-" . ($currentCatalogYear + 1) . "), 
				 as any major change would place the student into the 
				 most recent catalog. 
				</div>";



		$pC .= "</td></tr>";
		$pC .= $this->displayEndSemesterTable();
		//$pC .= $this->getHiddenAdvisingVariables("");

		$pC .= "</form>
				";


		return $pC;
	}


  /**
   * Used in the Toolbox popup, this will display content of the tab which
   * shows a student's courses which they have taken.
   *
   * @return string
   */
	function displayToolboxCourses()
	{
		$pC = "";

		$pC .= $this->drawCurvedTitle("All Student Courses");

		$csid = $_REQUEST["currentStudentID"];
		$order = $_REQUEST["order"];
		if ($order == "name")
		{
			$ns = " style='font-weight: bold; color: black; text-decoration: none;' ";
		} else {
			$os = " style='font-weight: bold; color: black; text-decoration: none;' ";
		}

		$pC .= "<div class='tenpt'>
				This window displays all of the student's courses
				which FlightPath is able to load.  
					<a href='javascript: popupHelpWindow(\"help.php?i=7\");'>Confused? Click here.</a>
				<br><br>
				Order by: &nbsp; &nbsp;";
		$pC .= "<a $ns href='advise.php?windowMode=popup&performAction=toolbox&performAction2=courses&order=name&currentStudentID=$csid'>Name</a>
				&nbsp; &nbsp;";
		$pC .= "<a $os href='advise.php?windowMode=popup&performAction=toolbox&performAction2=courses&order=date&currentStudentID=$csid'>Date Taken</a>";

		$pC .= "<hr>
				<table border='0' cellpadding='2'>
					";
		$isEmpty = true;
		if ($order == "name")
		{
			$this->student->listCoursesTaken->sortAlphabeticalOrder();
		} else {
			$this->student->listCoursesTaken->sortMostRecentFirst();
		}
		$this->student->listCoursesTaken->resetCounter();
		while($this->student->listCoursesTaken->hasMore())
		{
			$c = $this->student->listCoursesTaken->getNext();

			if ($c->courseID > 0)
			{
				$c->loadDescriptiveData();
			}

			$lSI = $c->subjectID;
			$lCN = $c->courseNum;
			$eqvLine = "";

			if ($c->courseTransfer->courseID > 0)
			{
				if ($c->courseID > 0)
				{
					$eqvLine = "<tr>
							<td colspan='8' class='tenpt'
								style='padding-left: 20px;'>
								<i>*eqv to {$GLOBALS["fpSystemSettings"]["schoolInitials"]} $lSI $lCN</i></td>
							</tr>";
				}
				$lSI = $c->courseTransfer->subjectID;
				$lCN = $c->courseTransfer->courseNum;

			}


			$lTitle = $this->fixCourseTitle($c->title);
			$lTerm = $c->getTermDescription(true);

			//$pC .= "<div class='tenpt' style='padding-bottom: 15px;'>
			//					<b>$lSI $lCN</b> ($c->hoursAwarded hrs) - $c->grade - $lTerm
			//						";
			//$pC .= "</div>";

			$h = $c->hoursAwarded;
			if ($c->boolGhostHour) {
			  $h .= "(ghost<a href='javascript:alertSubGhost()'>?</a>)";
			}
			
			$pC .= "<tr>
						<td valign='top' class='tenpt'>$lSI</td>
						<td valign='top' class='tenpt'>$lCN</td>
						<td valign='top' class='tenpt'>$h</td>
						<td valign='top' class='tenpt'>$c->grade</td>
						<td valign='top' class='tenpt'>$c->termID</td>
						";
			$pC .= "<td valign='top' class='tenpt'>";

			if ($c->boolTransfer) {$pC .= "T ";}
			//$pC .= "</td>";

			//$pC .= "<td valign='top' class='tenpt'>";
			if ($c->boolSubstitution) {$pC .= "S ";}
			//$pC .= "</td>";

			//$pC .= "<td valign='top' class='tenpt'>";
			if ($c->boolHasBeenAssigned)
			{
				$pC .= "A:";
				if ($c->assignedToGroupID == 0)
				{
					$pC .= "degree plan";
				} else {
					$tempGroup = new Group();
					$tempGroup->groupID = $c->assignedToGroupID;
					$tempGroup->loadDescriptiveData();
					$pC .= $tempGroup->title;
				}


			}
			$pC .= "</td>";



			$pC .= "</tr>$eqvLine";

			$isEmpty = false;
		}

		if ($isEmpty == true)
		{
			$pC .= "<div align='center'>No courses have been moved for this student.</div>";
		}

		$pC .= "</table>";

		$pC .= "</div>";

		$this->db->addToLog("toolkit", "courses,$order");

		return $pC;
	}


  /**
   * Used in the Toolbox popup, this will display content of the tab which
   * shows a student's moved courses. That is, courses which have had
   * their group memberships changed.
   *
   * @return string
   */
	function displayToolboxMoved()
	{
		$pC = "";


		$pC .= $this->drawCurvedTitle("Manage Moved Courses");

		$pC .= "<div class='tenpt'>
				This student has the following course movements.
				<br><br>
				";
		$isEmpty = true;

		$this->student->listCoursesTaken->sortAlphabeticalOrder();
		$this->student->listCoursesTaken->resetCounter();
		while($this->student->listCoursesTaken->hasMore())
		{
			$c = $this->student->listCoursesTaken->getNext();

			// Skip courses which haven't had anything moved.
			if ($c->groupListUnassigned->isEmpty == true)
			{
				continue;
			}


			if ($c->courseID > 0)
			{
				$c->loadDescriptiveData();
			}

			$lSI = $c->subjectID;
			$lCN = $c->courseNum;
			$lTitle = $this->fixCourseTitle($c->title);
			$lTerm = $c->getTermDescription(true);

			$h = $c->hoursAwarded;
			if ($c->boolGhostHour) {
			  $h .= " [ghost<a href='javascript:alertSubGhost();'>?</a>] ";
			}
			
			$pC .= "<div class='tenpt' style='padding-bottom: 15px;'>
							<b>$lSI $lCN</b> ($h hrs) - $c->grade - $lTerm
								";
			//adminDebug($c->groupListUnassigned->getSize());
			$c->groupListUnassigned->resetCounter();
			while($c->groupListUnassigned->hasMore())
			{
				$group = $c->groupListUnassigned->getNext();
				$group->loadDescriptiveData();
				$groupTitle = "";
				if ($group->groupID > 0)
				{
					$groupTitle = "<i>$group->title</i>";
				} else {
					$groupTitle = "the degree plan";
				}
				$pC .= "<div class='tenpt'>This course was removed from $groupTitle.<br>
							<a href='javascript: popupRestoreUnassignFromGroup(\"$group->dbUnassignGroupID\")'>Restore?</a>
							</div>
							";
			}



			$pC .= "</div>";

			$isEmpty = false;
		}

		if ($isEmpty == true)
		{
			$pC .= "<div align='center'>No courses have been moved for this student.</div>";
		}

		$pC .= "</div>";

		$this->db->addToLog("toolkit", "moved");

		return $pC;
	}


/**
 * Constructs the HTML to show the student's test scores.
 *
 */
	function buildTestScores()
	{
		// This function will build our Test Scores box.
		// Only do this if the student actually has any test scores.

		if ($this->student->listStandardizedTests->isEmpty)
		{
			return;
		}

		$topScores = array();

		$pC = "";
		$pC .= $this->drawSemesterBoxTop("Test Scores", true);

		$pC .= "<tr><td colspan='8' class='tenpt'>
					<!--TOP_ACT_SCORES-->
					
			<span id='viewscorelink'
				onClick='document.getElementById(\"testscores\").style.display=\"\"; this.style.display=\"none\"; '
				class='hand' style='color: blue;'
				>
						Click to view all available scores.
						<br><br>
						</span>
				
			<span id='testscores' style='display:none;' >					
					";

		// Go through and find all the test scores for the student...
		$this->student->listStandardizedTests->resetCounter();
		while($this->student->listStandardizedTests->hasMore())
		{
			$st = $this->student->listStandardizedTests->getNext();
			$dt = strtotime($st->dateTaken);
			$ddate = date("M jS, Y", $dt);

			// TODO:  I am not sure if this is used anymore... the pre-2003 thing.
			if ($st->testID == "ACT") // old one
			{ $st->description = "ACT - Pre-2003 Version";	}

			$pC .= "<div>
						<b>$st->description</b> - $ddate
						<ul>";
			foreach($st->categories as $position => $catArray)
			{
				$pC .= "<li>{$catArray["description"]} - {$catArray["score"]}</li>";


				if (strstr($st->testID,"A05"))
				{
					// If we are dealing with an ACT score,
					// get the top scores in each category.
					if ($catArray["score"] > $topScores[$catArray["category_id"]] * 1)
					{
						//$topScores[$catArray["description"]] = $catArray["score"];
						$topScores[$catArray["category_id"]] = $catArray["score"];
					}
				}


			}
			$pC .= "</ul>
					</div>";

		}

		$pC .= "
		<span
				onClick='document.getElementById(\"viewscorelink\").style.display=\"\"; document.getElementById(\"testscores\").style.display=\"none\"; '
				class='hand' style='color: blue;'>
						Click to hide scores.
						
						</span>
				</span></td></tr>";

		// Add in the top ACT scores...
		if (count($topScores))
		{
			$topLine = "<b>Highest ACT scores from all tests:</b>
				<ul>
					<li> English - {$topScores["A01"]} </li>
					<li> Mathematics - {$topScores["A02"]} </li>
					<li> Composite - {$topScores["A05"]} </li>					
				</ul>
				";
			$pC = str_replace("<!--TOP_ACT_SCORES-->",$topLine, $pC);

		}


		$pC .= $this->drawSemesterBoxBottom();

		$this->addToScreen($pC);
	}



/**
 * This function is used by the "build" functions most often.  It very
 * simply adds a block of HTML to an array called boxArray.
 *
 * @param string $contentBox
 */
	function addToScreen($contentBox)
	{
		$this->boxArray[] = $contentBox;
	}


	/**
	 * This function calls the other "build" functions to assemble
	 * the View or What If tabs in FlightPath.
	 *
	 */
	function buildScreenElements()
	{
		// This function will build & assemble all of the onscreen
		// elements for the advising screen.  It should be
		// called before displayScreen();

		$this->buildSemesterList();
		//$this->buildDevelopmentalRequirements();
		$this->buildExcessCredit();
		$this->buildTestScores();

		$this->buildTransferCredit();

		if (!$this->boolBlank)
		{ // Don't show if this is a blank degree plan.
			$this->buildFootnotes();
			$this->buildAddedCourses();
		}

	}


	/**
	 * Displays the popup window which lets the user select a new
	 * advising term.
	 *
	 * @return string
	 */
	function displayChangeTerm()
	{
		$pC = "";

		$tempCourse = new Course();
		$tempCourse->termID = $GLOBALS["advisingTermID"];
		$currentTerm = $tempCourse->getTermDescription();
		$currentTermID = $tempCourse->termID;


		$pC .= $this->drawCurvedTitle("Select an Advising Term");
		$pC .= "<div class='tenpt'>
				You may advise this student for future semesters.  Please select which
				advising term you would like to advise for from the list below.  If you
				are unsure, simply close this window and continue to advise for the current
				term of <b>$currentTerm</b>.
				</div>";

		$pC .= "<ul>";

		$temp = split(",",$GLOBALS["settingAvailableAdvisingTermIDs"]);
		for ($t = 0; $t < count($temp); $t++)
		{
			$termID = trim($temp[$t]);

			$tempCourse = new Course();
			$tempCourse->termID = $termID;

			$termDesc = $tempCourse->getTermDescription();
			if ($termID == $currentTermID)
			{
				$termDesc = "<b>$termDesc</b> - current";
			}

			$pC .= "<li class='tenpt' style='padding:3px;'><a href='javascript: popupChangeTerm(\"$termID\");'>$termDesc</a></li>";

		}

		$pC .= "</ul>";

		return $pC;
	}


	/**
	 * Displays the popup window which lets the user select a different track
	 * for their major.  On screen, Tracks are referred to as "degree options."
	 *
	 * @return string
	 */
	function displayChangeTrack()
	{
		// This displays the popup window which lets the user select a different
		// track for their major.

		$pC = "";

		$this->degreePlan->loadDescriptiveData();
		$pC .= $this->drawCurvedTitle("Select a Degree Option");
		$pC .= "<div class='tenpt'>
				This major has one or more degree options, which affects which courses are required. 
				Please select a degree option (or track) from the list below.
				<br><br>
				If you are unsure of what to do, simply close this window.
				";

		$pC .= "<br><br><b>" . $this->degreePlan->title . "</b> degree options:</div><!--DEFAULT-->
				<ul>";

		// Get the list of available tracks for this student.
		if (!$tracks = $this->degreePlan->getAvailableTracks())
		{
			$pC .= "<li>This major has no degree options.</li>";
		}

		// Is there a "default" message for all tracks, which will override
		// any other track descriptions?
		// We need to look through all the tracks for the
		// characters:  "DEFAULT:"
		// If we find this, then this is the default description
		// which applies to all the tracks, and it should be displayed
		// at the top.
		$boolDefaultDescription = false;
		for ($t = 0; $t < count($tracks); $t++)
		{
			$temp = split(" ~~ ", $tracks[$t]);
			$trackCode = trim($temp[0]);
			$trackTitle = trim($temp[1]);
			$trackDescription = trim($temp[2]);

			if (strstr($trackDescription, "DEFAULT:"))
			{
				// Yes!  We found a default message.
				$boolDefaultDescription = true;
				$trackDescription = $this->convertBBCodeToHTML(trim(str_replace("DEFAULT:", "", $trackDescription)));
				$trackDescription = "<div style='padding-top: 10px;' class='tenpt'>$trackDescription</div>";
				$pC = str_replace("<!--DEFAULT-->",$trackDescription, $pC);
				break;
			}

		}


		for ($t = 0; $t < count($tracks); $t++)
		{
			$temp = split(" ~~ ", $tracks[$t]);
			$trackCode = trim($temp[0]);
			$trackTitle = trim($temp[1]);
			$trackDescription = "";

			// If this is the current trackCode, mark it as such.
			if ($this->student->arraySettings["trackCode"] == $trackCode
			&& $this->student->arraySettings["majorCode"] == $this->degreePlan->majorCode)
			{
				$trackTitle .= " <b>(current)</b>";
			}

			if ($boolDefaultDescription == false)
			{
				$trackDescription = $this->convertBBCodeToHTML(trim($temp[2]));
				if ($trackDescription != "")
				{
					$trackDescription = " - $trackDescription";
				}
			}

			$tempTC = $trackCode;
			if ($tempTC == "")
			{
				$tempTC = "none";
			}


			$onClick = "popupChangeTrack(\"$tempTC\");";
			if ($GLOBALS["advisingWhatIf"] == "yes")
			{
				$onClick = "popupChangeWhatIfTrack(\"$tempTC\");";

			}

			$pC .= "<li class='tenpt' style='padding:3px;'>
					<a href='javascript: $onClick'>$trackTitle</a> $trackDescription</li>";

		}

		$pC .= "</ul>";

		return $pC;
	}

	/**
	 * This function is used to draw an individual pie chart box.
	 * It accepts values of top/bottom in order to come up
	 * with a percentage.
	 *
	 * @param string $title
	 * 
	 * @param float $topValue
	 *         - The top part of a ratio.  Ex: for 1/2, $topValue = 1.
	 *
	 * @param float $bottomValue
	 *         - The bottom part of a ratio.  For 1/2, $bottomValue = 2.
	 *         - Do not let this equal zero.  If it does, the calculation
	 *           for the pie chart will never be evaluated.
	 * @param string $pal
	 *         - Which palette to use for the pie chart.
	 *         - Acceptable values:
	 *           - core
	 *           - major
	 *           - cumulative
	 *           - student
	 * 
	 * @return string
	 */
	function drawPieChartBox($title, $topValue, $bottomValue, $pal)
	{
		$pC = "";

				
		if ($bottomValue > 0)
		{
			$val = round(($topValue / $bottomValue)*100);
		}
		if ($val > 100) { $val = 99; }
    
		$leftval = 100 - $val;
		
		$backCol = "660000";
		$foreCol = "FFCC33";
		
    if ($pal == "major")
    {
    	$foreCol = "93D18B";
    }
    
    if ($pal == "cumulative")
    {
    	$foreCol = "5B63A5";
    }
    
    $vval = $val;
    if ($vval < 1) $vval = 1;
    
		// Create a graph using google's chart API		
		$google_chart_url = "https://chart.googleapis.com/chart?cht=p&chd=t:$vval,$leftval&chs=75x75&chco=$foreCol|$backCol&chp=91.1";
		
		$pC .= "<table border='0' width='100%'  height='100' class='elevenpt blueBorder' cellpadding='0' cellspacing='0' >
 						<tr>
  							<td class='blueTitle' align='center' height='20'>
    				" . $this->drawSquareTitle($title) . "
  							</td>
 						</tr>
 						<tr>
 							<td>
 								<table border='0'>
 								<td>
 									<!-- <img src='jgraph/display_graph.php?pal=$pal&value=$val'> -->
 									<img src='$google_chart_url'>
 								</td>
 								<td class='elevenpt'>
 								    <span style='color: blue;'>$val% Complete</span><br>
 								    ( <span style='color: blue;'>$topValue</span>
 									 / <span style='color: gray;'>$bottomValue hours</span> )
								</td>
								</table>
 							</td>
 						</tr>
 					</table>
				";

		return $pC;
	}


	/**
	 * This function calls drawPieChart to construct the student's 3
	 * progress pie charts.
	 *
	 * @return string
	 */
	function drawProgressBoxes()
	{
		// Draw the boxes for student progress (where
		// the pie charts go!)
		$pC = "";


		//adminDebug("getting stuff --------  ");

		if ($this->degreePlan->totalDegreeHours < 1)
		{
			$this->degreePlan->calculateProgressHours();
		}

		$totalMajorHours = $this->degreePlan->totalMajorHours;
		$totalCoreHours = $this->degreePlan->totalCoreHours;
		$totalDegreeHours = $this->degreePlan->totalDegreeHours;
		$fulfilledMajorHours = $this->degreePlan->fulfilledMajorHours;
		$fulfilledCoreHours = $this->degreePlan->fulfilledCoreHours;
		$fulfilledDegreeHours = $this->degreePlan->fulfilledDegreeHours;


		$pC .= "<tr><td colspan='2'>
				";

		if ($this->userSettings["hideCharts"] != "hide" && $this->boolPrint == false && $this->boolBlank == false && $this->pageIsMobile == false)
		{ // Display the pie charts unless the student's settings say to hide them.


			/*			$totalMajorHours = $this->degreePlan->getProgressHours("m");
			$totalCoreHours = $this->degreePlan->getProgressHours("c");
			$totalDegreeHours = $this->degreePlan->getProgressHours("");
			//adminDebug("fulfilled major: ");
			$fulfilledMajorHours = $this->degreePlan->getProgressHours("m", false);
			//adminDebug("fulfilled core:  ");
			$fulfilledCoreHours = $this->degreePlan->getProgressHours("c", false);
			//adminDebug("fulfilled -degree:  ");
			$fulfilledDegreeHours = $this->degreePlan->getProgressHours("", false);
			*/
			$pC .= "
				<div style='margin-bottom: 10px;'>
				<table width='100%' cellspacing='0' cellpadding='0' border='0'>
				<td width='33%' style='padding-right:5px;'>
					" . $this->drawPieChartBox("Progress - Core Courses",$fulfilledCoreHours, $totalCoreHours, "core") . "
				</td>
				
				<td width='33%' style='padding-right: 5px;'>
					" . $this->drawPieChartBox("Progress - Major Courses",$fulfilledMajorHours, $totalMajorHours, "major") . "
				</td>
				
				<td width='33%'>
					" . $this->drawPieChartBox("Progress - Degree",$fulfilledDegreeHours, $totalDegreeHours, "cumulative") . "
				</td>
				

				
				</table>
				";

			$pC .= "
				
				<div style='font-size: 8pt; text-align:right;'>
					<a href='javascript:hideShowCharts(\"hide\");'>hide charts</a>
				</div>";

			$pC .= "
				</div>";
		} else {
			// Hide the charts!  Show a "show" link....
			$pC .= "
 			<table border='0' width='100%'  class='elevenpt blueBorder' cellpadding='0' cellspacing='0' >
 			<tr>
  				<td colspan='4' class='blueTitle' align='center' height='20'>
    			" . $this->drawSquareTitle("Progress") . "
  				</td>
 			</tr>
 			<tr>
 				<td class='tenpt' width='33%' align='center'>
 					Core: $fulfilledCoreHours / $totalCoreHours
 				</td>
 				<td class='tenpt' width='33%' align='center'>
 					Major: $fulfilledMajorHours / $totalMajorHours
 				</td>
 				<td class='tenpt' width='33%' align='center'>
 					Degree: $fulfilledDegreeHours / $totalDegreeHours
 				</td>
 				
 			</tr>

			</table>
			";

			if ($this->boolPrint != true && $this->boolBlank != true && $this->pageIsMobile != true)
			{

				$pC .= "<div style='font-size: 8pt; text-align:right;'>
					<a href='javascript:hideShowCharts(\"show\");'>show charts</a>
				</div>
					";
			} else {
				$pC .= "<div> &nbsp; </div>";
			}
		}
		$pC .= "
				</td></tr>";



		return $pC;
	}



	/**
	 * Draws the CurrentlyAdvisingBox which appears at the top of the screen,
	 * containing the student's information like name, major, etc.
	 *
	 * @param bool $boolHideCatalogWarning
	 *       - If set to TRUE, FP will not display a warning which tells
	 *         the user that they are working under an outdated catalog year.
	 * 
	 * @return string
	 */
	function drawCurrentlyAdvisingBox($boolHideCatalogWarning = false)
	{
		// This will draw the box which contains student information,
		// like Name, Major, etc.
		$pC = "";
		
		$csid = $GLOBALS["currentStudentID"];
		if ($this->student == null)
		{
			$this->student = new Student();
			$this->student->studentID = $GLOBALS["advisingStudentID"];
			$this->student->loadStudentData();
		}

		$forTerm = $whatif = $whatIfSelect = $hypoclass = "";
		if ($GLOBALS["advisingTermID"] != "" && $this->screenMode != "notAdvising"
		&& $_SESSION["fpUserType"] != "student")
		{
			$tempCourse = new Course();
			$tempCourse->termID = $GLOBALS["advisingTermID"];
			$tTermID = $GLOBALS["advisingTermID"];
			$forTerm = " for " . $tempCourse->getTermDescription();

			// If this is an advisor or above
			$forTerm .= "<span style='font-size: 8pt; font-weight:normal;'>
						 - <a href='javascript: popupWindow(\"changeTerm\",\"advisingTermID=$tTermID\");' style='color:blue; background-color: white; border: 1px solid black; padding-left: 3px; padding-right: 3px;'>change<img src='$this->themeLocation/images/calendar1.jpg' height='13' border='0' style='vertical-align: bottom;'></a>
						</span>";


		}

		if ($GLOBALS["advisingWhatIf"] == "yes" && !$this->boolBlank)
		{
			$whatif = " (in \"What If\" mode) ";
			$hypoclass = "hypo";
			// latest cat year because its what if.
			$this->student->catalogYear = $this->settings["currentCatalogYear"];
			if ($this->boolPrint != true)
			{
				$whatIfSelect = "<div class='tenpt'><b>
					<a href='advise.php?advisingWhatIf=yes&whatIfMajorCode=none&whatIfTrackCode=none&currentStudentID=$csid'>Change What If Settings</a></b></div>";
			}
		}

		$ca = "Currently Advising";
		if ($_SESSION["fpUserType"] == "student" || $_SESSION["fpUserType"] == "viewer")
		{
			$ca = "Student Details";
		}

		if ($this->boolBlank == true)
		{
			$ca = "Viewing Blank Degree Plan";
		}

		$pC .= "<tr><td colspan='2' style='padding-bottom: 10px;'>
				$whatIfSelect
 			<table border='0' width='100%'  class='elevenpt blueBorder' cellpadding='0' cellspacing='0' >
 			<tr>
  				<td colspan='4' class='blueTitle' align='center' height='20'>
    			" . $this->drawSquareTitle("$ca$whatif$forTerm") . "
  				</td>
 			</tr>
 			";

		// Okay, let's build up the display array.
		$displayArray = array();

		// How to display the catalogYear...
		$catYear = $this->student->catalogYear . "-" . ($this->student->catalogYear + 1);

		// Should we display a catalog year warning?  This is
		// something that can be part of a settings table.
		if ($this->student->catalogYear < $this->earliestCatalogYear)
		{
			$catYear = "<b>$catYear</b>";
			$boolCatalogWarning = true;
		}

		if ($this->settings["currentCatalogYear"] > $this->earliestCatalogYear)
		{
			// Is the student's catalog set beyond the range that
			// FP has data for?  If so, show a warning.
			if ($this->student->catalogYear > $this->settings["currentCatalogYear"])
			{
				$catYear = "<b>$catYear</b>";
				$boolFutureCatalogWarning = true;

			}
		}


		if ($this->degreePlan != null)
		{
			$degreeTitle = $this->degreePlan->getTitle2();
		}
		if ($degreeTitle == "")
		{
			// Attempt to load the degree from the student's information.			
			$degreePlan = $this->db->getDegreePlan($this->student->majorCode, $this->student->catalogYear, true);
			$degreeTitle = $degreePlan->getTitle2();
		}

		if (!$this->boolBlank)
		{
			array_push($displayArray, "Name: ~~ " . $this->student->name);
			array_push($displayArray, "CWID: ~~ " . $this->student->studentID);
		}
		array_push($displayArray, "Major: ~~ " . $degreeTitle);
		// If this degree has tracks, we must display something about it here.

		if ($this->degreePlan->boolHasTracks)
		{
			$extraVars = "";

			/*if ($GLOBALS["advisingWhatIf"] == "yes")
			{
			$extraVars .= "whatIfMajorCode={$GLOBALS["whatIfMajorCode"]}";
			$extraVars .= "&whatIfTrackCode={$GLOBALS["whatIfTrackCode"]}";
			$extraVars .= "&advisingWhatIf=yes";
			}*/
			
			$opLink = "<a href='javascript: popupWindow(\"changeTrack\",\"$extraVars\");'><img
							src='$this->themeLocation/images/popup.gif' border='0' 
							title='Click to change degree options.'></a>";
			$opText = "Click to select: $opLink";

			if ($this->screenMode == "notAdvising")
			{
				$opText = "None selected";
				$opLink = "";
			}

			if ($_SESSION["fpCanAdvise"] != true)
			{
			  
				if ($GLOBALS["advisingWhatIf"] != "yes")
				{
					// In other words, we do not have permission to advise,
					// and we are not in whatIf, so take out the link.
					$opLink = "";
					$opText = "None selected";

				}
			}


			// Did has the student already selected an option?
			if ($this->degreePlan->trackCode != "")
			{
				$opText = $this->degreePlan->trackTitle . " $opLink";
			}


			array_push($displayArray, "Option: ~~ " . $opText);
		}
		if (!$this->boolBlank)
		{
			array_push($displayArray, "Rank: ~~ " . $this->student->rank);
		}
		array_push($displayArray, "Catalog Year: ~~ " . $catYear);
		if (!$this->boolBlank)
		{
			array_push($displayArray, "Cumulative: ~~ " . $this->student->cumulativeHours . " hrs. &nbsp;" . $this->student->gpa . " GPA");
		}

		if ($this->student->studentID > 1 || $this->boolBlank == true)
		{ // Make sure we have selected a student! (or are viewing a blank plan)
			// Now, go through the array and display it.
			for ($t = 0; $t < count($displayArray); $t = $t + 2)
			{
				$temp = split(" ~~ ",$displayArray[$t]);
				$name1 = trim($temp[0]);
				$value1 = trim($temp[1]);


				$temp = split(" ~~ ",$displayArray[$t+1]);
				$name2 = trim($temp[0]);
				$value2 = trim($temp[1]);


				if ($this->pageIsMobile) {
				  // Mobile screen.  Needs to be more condensed.
				  $pC .= "<tr class='$hypoclass'>
				          <td valign='top'>$value1</td>
				          <td valign='top'>$value2</td>
				          </tr>";
				}
				else {
			   // Regular desktop screen.	
				
  				$pC .= "
  			
      		 	<tr class='$hypoclass' >
      		 		<td valign='top' width='20%' class='side_padding' style='padding-top: 5px;'>
      		 		   $name1
      				</td>
      				<td width='30%' valign='top' class='side_padding elevenpt' style='padding-top: 5px;'>
      					$value1
      				</td>
      				<td valign='top' align='left' width='20%' class='side_padding elevenpt' style='padding-top: 5px;'>
      				     $name2
      				</td>
      				 <td align='right' width='30%' valign='top' class='side_padding elevenpt' style='padding-top: 5px;'>
      				     $value2
      		   		</td>
      		 	</tr>  ";
      
				}
				
			}
		} else {
			// No student has been selected yet!
			$pC .= "<tr height='60'>
				<td align='center'> No advisee selected. </td>
			</tr>";
			$boolHideCatalogWarning = true;
		}

		$pC .= "</table>";

		if ($boolCatalogWarning == true && !$boolHideCatalogWarning)
		{
			$pC .= "
			
			<div class='tenpt hypo' style='margin-top: 4px; padding: 2px;'>
			<table border='0' cellspacing='0' cellpadding='0'>
			<td valign='top'>
				<img src='$this->themeLocation/images/alert_lg.gif' >	
			</td>
			<td valign='middle' class='tenpt' style='padding-left: 8px;'>
				<b>Important Notice: </b>
				FlightPath cannot display degree plans from 
				catalogs earlier than $this->earliestCatalogYear-" . ($this->earliestCatalogYear + 1) . ".  
				The above student's catalog year is $catYear, which means 
				that the degree plan below may not accurately 
				display this student's degree requirements.
			</td>
			</table>
			</div>
			
		"; 
		}

		if ($boolFutureCatalogWarning == true && !$boolHideCatalogWarning)
		{
			$pC .= "
			
			<div class='tenpt hypo' style='margin-top: 4px; padding: 2px;'>
			<table border='0' cellspacing='0' cellpadding='0'>
			<td valign='top'>
				<img src='$this->themeLocation/images/alert_lg.gif' >	
			</td>
			<td valign='middle' class='tenpt' style='padding-left: 8px;'>
				<b>Important Notice: </b>
				This student's catalog year is $catYear, 
				and specific curriculum requirements are not yet 
				available for this year.  
				To advise this student according to {$this->settings["currentCatalogYear"]}-" . ($this->settings["currentCatalogYear"] + 1) . "
				requirements, select the student's major using What If.
			</td>
			</table>
			</div>
			
		"; 
		}




		$pC .= "</td></tr>";

		return $pC;
	}

	/**
	 * Simple function will will return the HTML to draw a bit of text,
	 * used most often to show the title above a block of content.  This
	 * will have square corners.
	 *
	 * @param string $title
	 * @return string
	 */
	function drawSquareTitle($title)
	{
		$pC = "";

		$pC .= "
        <table border='0' width='100%' cellpadding='0' cellspacing='0'>
       <tr>
        <td width='10%' align='left' valign='top'></td>
        <td width='80%' align='center' rowspan='2'>
         <span class='tenpt' style='color: white' ><b>$title</b></span>
        </td>
        <td width='10%' align='right' valign='top'></td>
       </tr>
       <tr>
        <td align='left' valign='bottom'></td>
        <td align='right' valign='bottom'></td>
       </tr> 
      </table>     
		
			";


		return $pC;
	}



	/**
	 * Initializes important advising variables from the REQUEST
	 * or SESSION, accordingly.
	 *
	 * @param bool $boolIgnoreWhatIfVariables
	 *         - If set to TRUE, variables involving What If mode
	 *           will be ignored.
	 * 
	 */
	function initAdvisingVariables($boolIgnoreWhatIfVariables = false)
	{
		// This function loads the various "advising variables"
		// into the $GLOBALS array.


		// The current student ID is what we append to all session variables
		// dealing with the current student.  We do this so that we will
		// keep session variables unique, so that we can have more than one
		// window open at a time, with multiple students.
		// Therefor, this should never go into the session.
		// Annoyingly, we must pass carry this around on each page in the system.
		$GLOBALS["currentStudentID"] = $_REQUEST["currentStudentID"];
		if ($GLOBALS["currentStudentID"] == "")
		{
			$GLOBALS["currentStudentID"] = $_REQUEST["advisingStudentID"];
		}

		$csid = $GLOBALS["currentStudentID"];

		//adminDebug("csid: $csid");
		// Get the student ID.
		$GLOBALS["advisingStudentID"] = $_REQUEST["advisingStudentID"];
		if ($GLOBALS["advisingStudentID"] == "")
		{
			$GLOBALS["advisingStudentID"] = $_SESSION["advisingStudentID$csid"];
			if ($GLOBALS["advisingStudentID"] == "")
			{ // Default value...
				//$GLOBALS["advisingStudentID"] = "10035744";
			}
		}


		// Should we load from the Draft advising session?  or the active?
		$GLOBALS["advisingLoadActive"] = $_REQUEST["advisingLoadActive"];
		if ($GLOBALS["advisingLoadActive"] == "")
		{ // values will either be "yes" or "" (any other value than "yes" is
			// considered to be negative.
			// Default value...
			$GLOBALS["advisingLoadActive"] = "";

		}



		/*
		// Get the degree ID.
		$GLOBALS["advisingDegreeID"] = $_REQUEST["advisingDegreeID"];
		if ($GLOBALS["advisingDegreeID"] == "")
		{
		$GLOBALS["advisingDegreeID"] = $_SESSION["advisingDegreeID"];
		if ($GLOBALS["advisingDegreeID"] == "")
		{ // Default value...
		$GLOBALS["advisingDegreeID"] = "586227"; // cosc
		}
		}
		*/

		// Get the majorCode.
		$GLOBALS["advisingMajorCode"] = $_REQUEST["advisingMajorCode"];
		//adminDebug($GLOBALS["advisingMajorCode"]);
		if ($GLOBALS["advisingMajorCode"] == "")
		{
			$GLOBALS["advisingMajorCode"] = $_SESSION["advisingMajorCode$csid"];
			if ($GLOBALS["advisingMajorCode"] == "")
			{ // Default value...
				//$GLOBALS["advisingMajorCode"] = "COSC";
			}
		}




		// Get the trackCode.
		$GLOBALS["advisingTrackCode"] = $_REQUEST["advisingTrackCode"];
		if ($GLOBALS["advisingTrackCode"] == "")
		{
			$GLOBALS["advisingTrackCode"] = $_SESSION["advisingTrackCode$csid"];
			if ($GLOBALS["advisingTrackCode"] == "")
			{ // Default value...

			}
		}

		// Update the student's settings?
		$GLOBALS["advisingUpdateStudentSettingsFlag"] = $_POST["advisingUpdateStudentSettingsFlag"];
		// Make it only come from the POST, for safety.
		if ($GLOBALS["advisingUpdateStudentSettingsFlag"] == "")
		{
			$GLOBALS["advisingUpdateStudentSettingsFlag"] = $_SESSION["advisingUpdateStudentSettingsFlag$csid"];
			if ($GLOBALS["advisingUpdateStudentSettingsFlag"] == "")
			{ // Default value...

			}
		}


		// Update the logged-in user's settings?
		$GLOBALS["fpUpdateUserSettingsFlag"] = $_POST["fpUpdateUserSettingsFlag"];
		// Make it only come from the POST, for safety.
		if ($GLOBALS["fpUpdateUserSettingsFlag"] == "")
		{
			$GLOBALS["fpUpdateUserSettingsFlag"] = $_SESSION["fpUpdateUserSettingsFlag$csid"];
			if ($GLOBALS["fpUpdateUserSettingsFlag"] == "")
			{ // Default value...

			}
		}



		$settings = $this->settings;


		$GLOBALS["advisingTermID"] = $_REQUEST["advisingTermID"];  // Get it from the GET or POST.
		if ($GLOBALS["advisingTermID"] == "")
		{
			// Set to the default advising term.
			$GLOBALS["advisingTermID"] = $_SESSION["advisingTermID$csid"];
			if ($GLOBALS["advisingTermID"] == "")
			{
				// default value:
				$GLOBALS["advisingTermID"] = $settings["advisingTermID"];
			}
		}

		// Are we currently in WhatIf mode?
		$GLOBALS["advisingWhatIf"] = $_REQUEST["advisingWhatIf"];  // Get it from the GET or POST.
		if ($GLOBALS["advisingWhatIf"] == "")
		{
			// Will equal "yes" if we ARE in whatIf mode.
			$GLOBALS["advisingWhatIf"] = $_SESSION["advisingWhatIf$csid"];
			if ($GLOBALS["advisingWhatIf"] == "")
			{
				// Default value:
				$GLOBALS["advisingWhatIf"] = "no";
			}
		}

		$GLOBALS["whatIfMajorCode"] = $_REQUEST["whatIfMajorCode"];  // Get it from the GET or POST.
		if ($GLOBALS["whatIfMajorCode"] == "")
		{
			// Will equal "yes" if we ARE in whatIf mode.
			$GLOBALS["whatIfMajorCode"] = $_SESSION["whatIfMajorCode$csid"];
			if ($GLOBALS["whatIfMajorCode"] == "")
			{
				// Default value:
				$GLOBALS["whatIfMajorCode"] = "";
			}
		}

		$GLOBALS["whatIfTrackCode"] = $_REQUEST["whatIfTrackCode"];  // Get it from the GET or POST.
		if ($GLOBALS["whatIfTrackCode"] == "")
		{
			// Will equal "yes" if we ARE in whatIf mode.
			$GLOBALS["whatIfTrackCode"] = $_SESSION["whatIfTrackCode$csid"];
			if ($GLOBALS["whatIfTrackCode"] == "")
			{
				// Default value:
				$GLOBALS["whatIfTrackCode"] = "";
			}
		}


		if ($GLOBALS["whatIfMajorCode"] == "none")
		{
			$GLOBALS["whatIfMajorCode"] = "";
		}
		if ($GLOBALS["whatIfTrackCode"] == "none")
		{
			$GLOBALS["whatIfTrackCode"] = "";
		}
		if ($GLOBALS["advisingTrackCode"] == "none")
		{
			$GLOBALS["advisingTrackCode"] = "";
		}


		// Settings... (from the database)
		$GLOBALS["settingAvailableAdvisingTermIDs"] = $settings["availableAdvisingTermIDs"];
		$GLOBALS["settingAdvisingTermID"] = $settings["advisingTermID"];
		$GLOBALS["settingCurrentCatalogYear"] = $settings["currentCatalogYear"];
		$GLOBALS["settingCurrentDraftCatalogYear"] = $settings["currentDraftCatalogYear"];

		// Are we in Print View?
		$GLOBALS["printView"] = $_REQUEST["printView"];

		// Should we try to load from the cache?
		$GLOBALS["loadFromCache"] = $_REQUEST["loadFromCache"];
		if ($GLOBALS["loadFromCache"] == "")
		{
			// By default, attempt to load from cache.
			$GLOBALS["loadFromCache"] = "yes";
		}

		// What "view" are we in?  View by Year or by Type?
		// Not the same as printView.  printView should work regardless
		// of our advisingView.
		$GLOBALS["advisingView"] = $_REQUEST["advisingView"];
		if ($GLOBALS["advisingView"] == "")
		{
			$GLOBALS["advisingView"] = $_SESSION["advisingView$csid"];
		}


		// Place values into session.
		$_SESSION["advisingStudentID$csid"] = $GLOBALS["advisingStudentID"];
		$_SESSION["advisingStudentID"] = $GLOBALS["advisingStudentID"]; // used ONLY in the error report popup!
		$_SESSION["advisingMajorCode$csid"] = $GLOBALS["advisingMajorCode"];
		$_SESSION["advisingTrackCode$csid"] = $GLOBALS["advisingTrackCode"];
		$_SESSION["advisingTermID$csid"] = $GLOBALS["advisingTermID"];
		$_SESSION["advisingWhatIf$csid"] = $GLOBALS["advisingWhatIf"];
		$_SESSION["whatIfMajorCode$csid"] = $GLOBALS["whatIfMajorCode"];
		$_SESSION["whatIfTrackCode$csid"] = $GLOBALS["whatIfTrackCode"];
		$_SESSION["advisingView$csid"] = $GLOBALS["advisingView"];


		if ($boolIgnoreWhatIfVariables == true)
		{
			$GLOBALS["advisingWhatIf"] = "";
			$GLOBALS["whatIfMajorCode"] = "";
			$GLOBALS["whatIfTrackCode"] = "";
		}



		if ($_SESSION["fpUserType"] == "student")
		{
			// The student can only pull up their own information.  This
			// check is to try and prevent a hacker student from trying
			// to pull up someone else's information.
			if ($_SESSION["advisingStudentID"] != $_SESSION["fpUserID"])
			{
				session_destroy();
				die("You do not have access to that function.  Please log back in: <a href='main.php'>Log into FlightPath.</a>");
			}
		}

		// Are we in draft mode?
		if ($_SESSION["fpDraftMode"] == "yes")
		{
			$GLOBALS["boolUseDraft"] = true;
		} else {
			$GLOBALS["boolUseDraft"] = false;
		}




	}


  /**
   * Will display the "public note" at the top of a degree.  This
   * was entred in Data Entry.
   *
   * @return string
   */
	function drawPublicNote()
	{
		// This will display a "public note" to the user about
		// this degree.  The public note was entered in Data Entry.

		if ($this->degreePlan->publicNote == "")
		{
			return "";
		}

		$publicNote = $this->convertBBCodeToHTML($this->degreePlan->publicNote);

		$pC = "";

		$pC .= "<tr><td colspan='8'>
					<div class='tenpt' 
						style='border: 5px double #C1A599;
								padding: 5px;
								margin: 10px;'>
					<b>Important Message:</b> $publicNote
					</div>
					</td></tr>";


		return $pC;


	}
	
	
	
	/**
	 * This function generates the HTML to display the screen.  Should
	 * be used in conjunction with outputToBrowser()
	 *
	 * @return string
	 */	
	function displayScreen()
	{
		// This will generate the html to display the screen.
		$pC = "";


		$pC .= "<form action='$this->scriptFilename' id='mainform' method='POST'>";

		$pC .= $this->getJavascriptCode();
		$pC .= $this->displayBeginSemesterTable();

		if ($this->boolHidingGrades && !$this->boolPrint && $GLOBALS["fpSystemSettings"]["hidingGradesMessage"] != "")
		{
		  // Display the message about us hiding grades.
		  $pC .= "
          <tr><td colspan='2'>
          			<div class='tenpt hypo' style='margin-top: 4px; margin-bottom: 4px; 
          			 padding: 2px; border: 1px solid maroon;'>
          			<table border='0' cellspacing='0' cellpadding='0'>
          			<td valign='top'>
          				<img src='$this->themeLocation/images/alert_lg.gif' >	
          			</td>
          			<td valign='middle' class='tenpt' style='padding-left: 8px;'>
          			{$GLOBALS["fpSystemSettings"]["hidingGradesMessage"]}
          			</td>
          			</table>
          			</div>
          </td></tr>		  
		  ";
		}
		
		
		$pC .= $this->drawCurrentlyAdvisingBox();
		$pC .= $this->drawProgressBoxes();
		
    
		
		$pC .= $this->drawPublicNote();

		for ($t = 0; $t < count($this->boxArray); $t++)
		{

			$align = "right";
			if ($this->isOnLeft)
			{
				$pC .= "<tr>";
				$align= "left";
			}
			
			$pC .= "<td valign='top' align='$align' class='fp-boxes'>";
			$pC .= $this->boxArray[$t];
			$pC .= "</td>";
			
			if ($this->pageIsMobile) {
			 // If we are on a mobile device, force it to use
			 // only one column. 
			 $this->isOnLeft = false;
			}
			
			if (!$this->isOnLeft) // on right of page
			{
				$pC .= "</tr>";
			}
			$this->isOnLeft = !$this->isOnLeft;
		}

		if (!$this->isOnLeft) // on right of the page.
		{ // close up any loose ends.
			$pC .= "</tr>";
		}


		if ($_SESSION["fpCanAdvise"] == true)
		{
			if (!$this->boolPrint && !$this->boolBlank)
			{
      
			  $pC .= "<tr>";
			  
        if (!$this->pageIsMobile) {
          $pC .= "<td>&nbsp;</td>";
        }
			  
				$pC .= "<td align='center'>
						<div class='tenpt' style='margin-top:35px; margin-bottom:10px; padding: 10px; width: 200px;'>
						" . $this->drawButton("Submit","submitSaveActive();") . "					
						</div>
						</td></tr>
						";		


				//$this->addToScreen("<input type='button' value='Submit' onClick='submitSaveActive();'>");
			}
		}

		$pC .= $this->displayEndSemesterTable();
		$pC .= $this->getHiddenAdvisingVariables("saveDraft");

		$pC .= "</form>
				";

		return $pC;

	}

	/**
	 * Returns the HTML to draw a pretty button.
	 *
	 * @param string $title
	 * @param string $onClick
	 * @param bool $boolPadd
	 * @param string $style
	 * @return string
	 */
	function drawButton($title, $onClick, $boolPadd = true, $style = "")
	{
		// Style is expected to look like:
		// style='some:thing;'
		// with SINGLE apostrophes!  not quotes.

		$onMouse = "onmouseover='this.className=\"gradbutton gradbutton_hover hand\";'
					onmouseout='this.className=\"gradbutton hand\";'
					onmousedown='this.className=\"gradbutton gradbutton_down hand\";'
					onmouseup='this.className=\"gradbutton gradbutton_hover hand\";'
					";

		if ($this->pageIsMobile) $onMouse = "";  // Causes problems for some mobile devices.
		
		if ($boolPadd)
		{
			$padd = "&nbsp; &nbsp;";
		}


		$rtn = "<span class='gradbutton hand' onClick='$onClick' $onMouse $style >
				$padd $title $padd
				</span>

			";
		return $rtn;
	}

	/**
	 * Returns the HTML for starting off the top of a "semester table".
	 * Which is, the Freshman, Sophomore, etc, blocks on the View and What If
	 * tabs.
	 *
	 * @return string
	 */
	function displayBeginSemesterTable()
	{
		// Return the HTML that starts off the "semester table"
		// in the middle of the page.  Ie, it should look like
		// <table border='0' width='etc, etc'>
		$pC = "";

		$pC .= "<table border='0' width='95%' cellspacing='0' align='left' cellpadding='0' class='fp-semester-table'>";

		return $pC;
	}

	/**
	 * Constructs the HTML to display the list of semesters for the student.
	 *
	 */
	function buildSemesterList()
	{

		$listSemesters = $this->degreePlan->listSemesters;
		// Go through each semester and add it to the screen...
		$listSemesters->resetCounter();

		while($listSemesters->hasMore())
		{
			$semester = $listSemesters->getNext();
			$semester->resetListCounters();
			if ($semester->semesterNum == -88)
			{ // These are the "added by advisor" courses.  Skip them.
				continue;
			}


			$this->addToScreen($this->displaySemester($semester, true));

		}

	}


	/**
	 * Constructs the tabs (using drawSystemTabs) that are used on the
	 * main FlightPath pages.  For descriptions of each parameter, see
	 * drawSystemTabs().
	 *
	 * @param int $activeTabNumber
	 * @param bool $boolSaveDraft
	 * @param bool $boolFromWhatIf
	 * @param bool $boolWarnChange
	 */
	function buildSystemTabs($activeTabNumber = 0, $boolSaveDraft = false, $boolFromWhatIf = false, $boolWarnChange = false)
	{
		// assemble the pageTabs...
		$this->pageTabs = $this->drawSystemTabs($activeTabNumber, $boolSaveDraft, $boolFromWhatIf, $boolWarnChange);

	}

	/**
	 * Returns the HTML to draw out the main FlightPath tabs.
	 *
	 * @param int $activeTabNumber
	 *       - The number of the current tab
	 * 
	 * @param bool $boolSaveDraft
	 *       - Should the tab attempt to save a draft when the user switches?
	 *       - Should be set to TRUE on View and What If.
	 * 
	 * @param bool $boolFromWhatIf
	 *       - Are we comming from WhatIf mode?
	 * 
	 * @param bool $boolWarnChange
	 *       - Should we warn the user with a javascript confirm box
	 *         that they have unsaved changes on the page?
	 * 
	 * @return string
	 */
	function drawSystemTabs($activeTabNumber = 0, $boolSaveDraft = false, $boolFromWhatIf = false, $boolWarnChange = false)
	{
		// Returns the HTML to draw out the primary system tabs,
		// for example, Main, View, Comments, etc.
		// If SaveDraft == true, it means that we should save a draft copy
		// of the advising session when we change tabs.  Should be set to true
		// if we are on the View or What If tabs.

		$sd = "";
		if ($boolSaveDraft == true)
		{
			$sd = "&saveDraft=yes";
		}

		$fwi = "";
		if ($boolFromWhatIf == true)
		{
			$fwi="&fromWI=yes";
		}

		$warn = "";
		if ($boolWarnChange == true)
		{
			// Warn the user that they are about to change tabs, and
			// do not change tabs if they click "cancel."
			$warn = "warn";
		}


		$tabArray = array();

		$tabArray[0]["title"] = "Main";
		$tabArray[0]["onClick"] = "changeTab(\"main.php?a=vt$sd$fwi\",\"\", \"no\",\"$warn\");";

		if ($_SESSION["fpUserType"] != "none")
		{

			if ($_SESSION["fpUserType"] != "student")
			{ // not for students.
				$tabArray[1]["title"] = "Advisees";
				$tabArray[1]["onClick"] = "changeTab(\"studentsearch.php?a=vt$sd$fwi\",\"\", \"no\",\"$warn\");";
			} else {
				$tabArray[1]["title"] = "";
			}

			if ($GLOBALS["advisingStudentID"] != "")
			{
				$tabArray[2]["title"] = "View";
				$tabArray[2]["onClick"] = "changeTab(\"advise.php?a=vt$sd$fwi\",\"\",\"no\",\"$warn\");";

				if ($_SESSION["fpUserType"] != "student")
				{ // not for students.
					$tabArray[3]["title"] = "Comments";
					$tabArray[3]["onClick"] = "changeTab(\"comments.php?a=vt$sd$fwi\",\"\",\"no\",\"$warn\");";
				} else {
					$tabArray[3]["title"] = "";
				}

				$tabArray[4]["title"] = "History";
				$tabArray[4]["onClick"] = "changeTab(\"advise.php?windowMode=history$sd$fwi\",\"history\", \"no\",\"$warn\");";

				$tabArray[5]["title"] = "What If";
				$tabArray[5]["onClick"] = "changeTab(\"advise.php?a=vt$sd$fwi\",\"\", \"yes\",\"$warn\");";
			} else {
				// No student selected yet!
				$onClick = "alert(\"Please first select a student from the \\nAdvisees tab at the top of the page.\");";
				$tabArray[2]["title"] = "View";
				$tabArray[2]["onClick"] = $onClick;
				if ($_SESSION["fpUserType"] != "student")
				{ // not for students.
					$tabArray[3]["title"] = "Comments";
					$tabArray[3]["onClick"] = $onClick;
				} else {
					$tabArray[3]["title"] = "";
				}


				$tabArray[4]["title"] = "History";
				$tabArray[4]["onClick"] = $onClick;

				$tabArray[5]["title"] = "What If";
				$tabArray[5]["onClick"] = $onClick;

			}
		}

		$tabArray[$activeTabNumber]["active"] = true;



		return $this->drawTabs($tabArray);

	}

	/**
	 * Displays the views the user may select for the View and What If tabs.
	 * For example, Type view and Year view.
	 *
	 * @return string
	 */	
	function displayViewOptions()
	{
		// Draw the available views the user may select from.
		$pC = "";

		if ($this->boolPrint)
		{ // Don't display in Print View.
			return "";
		}

		$csid = $GLOBALS["currentStudentID"];

		$ystyle = "style='text-decoration: none; color: black; font-weight: bold;'";
		$tstyle = "style='color: blue;'";
		if ($this->view == "type")
		{
			$tstyle = $ystyle;
			$ystyle = "style='color: blue;'";
		}

		$onMouse = "onmouseover='this.className=\"gradbutton_hover hand\";'
					onmouseout='this.className=\"gradbutton hand\";'
					onmousedown='this.className=\"gradbutton_down hand\";'
		";

		if ($this->pageIsMobile) $onMouse = "";  // Causes problems for some mobile devices.
		


		$pC .= "<div class='tenpt' style='margin-top:5px; margin-bottom:10px;'>";
		if ($this->boolBlank == false)
		{
			$pC .= "" . $this->drawButton("Display by Year", "changeView(\"year\");", false, $ystyle) . "
					" . $this->drawButton("Display by Type", "changeView(\"type\");", false, $tstyle) . "
					";
			if (!$this->pageIsMobile) {
  			$pC .= "&nbsp;&nbsp;
  					<a href='javascript:launchPrintView();' class='nounderline'>Print View <img src='$this->themeLocation/images/popup.gif' border='0'></a>
  					";
  
  			// If this is a full_admin, show them special options...
  			if ($_SESSION["fpUserType"] == "full_admin")
  			{
  				$draftLink = "<a href='main.php?performAction=draftModeYes&currentStudentID=$csid' class='nounderline'>Switch to Draft Mode</a>";
  				if ($GLOBALS["boolUseDraft"] == true)
  				{
  					$draftLink = "<a href='main.php?performAction=draftModeNo&currentStudentID=$csid' class='nounderline'>Switch to Regular Mode</a>";
  				}
  				$pC .= "<span class='full_admin_top_options'>
  						<b>Admin Options:</b>
  						<a href='main.php?performAction=clearCache&currentStudentID=$csid' class='nounderline'>Clear cache</a> 
  						- 
  						$draftLink
  						
  					</span>";
  			}
			}


		}else{
			// We are viewing a blank degree plan
			$blankDegreeID = $this->degreePlan->degreeID;

			$moduleActionURL = getModuleActionURL("blank_degrees");
			
			$pC .= "" . $this->drawButton("Display by Year", "window.location=\"$moduleActionURL&blankDegreeID=$blankDegreeID&blankView=year\";", false, $ystyle) . "
					" . $this->drawButton("Display by Type", "window.location=\"$moduleActionURL&blankDegreeID=$blankDegreeID&blankView=type\";", false, $tstyle) . "

					";
			if (!$this->pageIsMobile) {
  			$pC .= "&nbsp;&nbsp;
  					<a href='$moduleActionURL&blankDegreeID=$blankDegreeID&blankView=$this->view&blankPrint=yes' class='nounderline' target='_blank'>Print View <img src='$this->themeLocation/images/popup.gif' border='0'></a>
  					";
			}


				if ($GLOBALS["boolUseDraft"] == true)
				{
					$pC .= "&nbsp; &nbsp; &nbsp; Viewing in <b>DRAFT</b> mode.";
				}
			

			//$pC = str_replace("changeView(\"year\"), "changeBlankView(", $pC);
			//$pC = str_replace("launchPrintView()", "launchBlankPrintView()", $pC);
		}






		$pC.= "</div>
				";		

		return $pC;
	}


	
	/**
	 * This function is called when we know we are on a mobile
	 * browser.  We have to handle tab rendering differently
	 * in order to make them all fit. 
	 *
	 * @param unknown_type $tabArray
	 */
	function drawMobileTabs($tabArray) {
	  
	  $rtn = "";
	  
	  $jsVars = "var mobileTabSelections = new Array(); ";

	  if (count($tabArray) <= 1) return "";
	  
	  
	  $rtn .= "<table border='0' width='200' cellpadding='0' cellspacing='0' class='fp-mobile-tabs'>
	           <td>
	           <b>Display: </b>";
	  
	  
/*	  if (count($tabArray) == 1) {
	    // Just one element, no need to render the select list.
	    $rtn .= $tabArray[0]["title"];
	    $rtn .= "</td></table>";
	    return $rtn;
	  }
*/
	  
	  $rtn .= "<select onChange='executeSelection()' id='mobileTabsSelect'>";
	  
	  for ($t = 0; $t < count($tabArray); $t++)
		{
			$title = $tabArray[$t]["title"];
			$active = $tabArray[$t]["active"];
			$onClick = $tabArray[$t]["onClick"];

			if ($title == "")
			{
				continue;
			}
			$sel = ($active == true) ? $sel = "selected":"";
			
			$rtn .= "<option $sel value='$t'>$title</option>";
						
			$jsVars .= "mobileTabSelections[$t] = '$onClick'; \n";
			
		}	  
	  
		$rtn .= "</select>
		          </td></table>";
	  				
		
		$rtn .= '
		  <script type="text/javascript">
		  ' . $jsVars . '		  
		  
		  function executeSelection() {
		    var sel = document.getElementById("mobileTabsSelect").value;
		    
		    var statement = mobileTabSelections[sel];
		    // Lets execute the statement...
		    eval(statement);
		    
		  }
		  
		  
		  </script>
		';
		
	  return $rtn;
	  
	}
	
	/**
	 * Given a propperly formatted tabArray, this will return the HTML
	 * to draw it on a page.
	 *
	 * @param array $tabArray
	 *       - Array should have this structure:
	 *         - $tabArray[i]["title"] = The title or caption of the tab. "Main", or "Edit", etc.
	 *         - $tabArray[i]["active"] = boolean.  True if this is the tab we are currently looking at.
	 *         - $tabArray[i]["onClick"] = This is a valid onClick='blah blah' that is the result of clicking the tab.
	 * 
	 * @return string
	 */
	function drawTabs($tabArray)
	{
		// This function will return the HTML to draw out
		// page tabs.  It assumes the tabArray is setup thusly:
		// $tabArray[i]["title"] = The title or caption of the tab. "Main", or "Edit", etc.
		// $tabArray[i]["active"] = boolean.  True if this is the tab we are currently looking at.
		// $tabArray[i]["onClick"] = This is a valid onClick='blah blah' that is the result of clicking the tab.

		if ($this->pageIsMobile) {
		  return $this->drawMobileTabs($tabArray);
		}
		
		$rtn = "";

		$rtn .= "<table border='0' width='100%' cellpadding='0' cellspacing='0'>
			<tr>
			";

		$imgPath = $this->themeLocation . "/images";


		for ($t = 0; $t < count($tabArray); $t++)
		{
			$title = $tabArray[$t]["title"];
			$active = $tabArray[$t]["active"];
			$onClick = $tabArray[$t]["onClick"];

			if ($title == "")
			{
				continue;
			}

			$padd = "30px;";
			if ($t > 0)
			{
				$padd = "5px;";
			}

			$rtn .= "<td style='padding-right: $padd'></td>
					<td>";

			if ($active != TRUE)
			{ //innactive tabs...

				$theclass = "inactive_tab hand";
				$overclass = "inactive_tab_over";
				if ($onClick == "")
				{
					$theclass = "inactive_tab";
					$overclass = "inactive_tab_over_no_link";
				}

				$rtn .= "
			
			<table align='left' cellpadding='0' onClick='$onClick' cellspacing='0' class='$theclass' onmouseover='this.className=\"$overclass\";' onmouseout='this.className=\"inactive_tab\";'>
			<tr>
				<td class='tab_tl_i' align='left' valign='baseline'  width='12'></td>
				<td class='tab_top_i' valign='baseline' ></td>
				<td class='tab_tr_i' valign='baseline' align='right' width='12'></td>
			</tr>
			<tr>
			 <td class='tab_left_i' align='left' valign='baseline' width='12'>
				<img src='$imgPath/tab_left_i.gif' width='12' height='18'>
			</td>
			<td align='left' nowrap class='tab_center_i'>
			 <div style='padding-bottom: 3px; margin-top: -2px;' class='tab_text'>$title</div>
			</td>
			<td class='tab_right_i' align='right' valign='baseline'>
			 <img src='$imgPath/tab_right_i.gif' width='12' height='18'>
			</td>
			</tr>
			</table>

			";

			} else {
				// active tab...
				$rtn .= "
			<table align='left' cellpadding='0' cellspacing='0' class='active_tab'>
				<tr>
					<td class='tab_tl' align='left' valign='baseline' width='12'></td>
					<td class='tab_top' valign='baseline'></td>
					<td class='tab_tr' valign='baseline' align='right' width='12'></td>
				</tr>
				<tr>
					<td class='tab_left' align='left' valign='baseline' width='12'>
					<img src='$imgPath/tab_left.gif' width='12' height='18'>
					</td>
					<td align='left' nowrap class='tab_center'>
						<div style='padding-bottom: 3px; margin-top: -2px;' class='tab_text'>$title</div>
					</td>
					<td class='tab_right' align='right' valign='baseline'>
						<img src='$imgPath/tab_right.gif' width='12' height='18'>
					</td>
				</tr>
			</table>			
			";

			}

			$rtn .= "</td>";

		}


		$rtn .= "
	 		 </tr>
	 		 </table>";

		return $rtn;

	}



  /**
   * Displays the contents of the Descripton tab for the course popup.
   *
   * @param int $courseID
   *        - The courseID of the course to show.  Leave blank if supplying
   *          the object instead.
   * 
   * @param Course $course
   *        - The course object to display.  Leave as NULL if supplying
   *          the courseID instead.
   * 
   * @param Group $group
   *        - The Group object that this course has been placed into.
   * 
   * @param bool $showAdvisingButtons
   *        - Should we show the advising buttons in this popup?  Would be
   *          set to false for student view, or for anyone who is not
   *          allowed to advise this course into a group for the student.
   * 
   * @return string
   */
	function displayPopupCourseDescription($courseID = "", Course $course = null, $group = null, $showAdvisingButtons = false)
	{
		$pC = "";

		//$course = new Course();
		
		if ($courseID != "" && $courseID != 0) {
		  
			$course = new Course($courseID);
		}

		//adminDebug($course->toString());
		$dbGroupRequirementID = $_REQUEST["dbGroupRequirementID"];
		//adminDebug("RequirmentID: $dbGroupRequirementID");
  

		if ($course == null)
		{
			// No course available!
			$pC .= $this->drawCurvedTitle("Description");
			$pC .= "<div class='tenpt'>No course was selected.  Please
					click the Select tab at the top of the screen.
					</div>";
			return $pC;
		}


		$advisingTermID = $GLOBALS["advisingTermID"];
		//adminDebug($advisingTermID);
    
		$course->loadDescriptiveData();
		//adminDebug($course->title);
		$courseHours = $course->getHours();
		//adminDebug("Information for courseID: $course->courseID");
		if ($course->boolTransfer)
		{
			//adminDebug(" -- transfer courseid: " . $course->courseTransfer->courseID );

		}
		//$courseHours = $course->hoursAwarded;

		//var_dump($course->arrayValidNames);

		// Does this course have more than one valid (non-excluded) name?
		$otherValidNames = "";
		if (count($course->arrayValidNames) > 1)
		{
			for ($t = 0; $t < count($course->arrayValidNames); $t++)
			{
				$name = $course->arrayValidNames[$t];
				if ($name == "$course->subjectID~$course->courseNum")
				{
					continue;
				}
				$otherValidNames .= ", " . str_replace("~"," ",$name);
			}
		}

		//adminDebug($course->toString());
		//print_pre($this->student->listCoursesTaken->toString());
		$course->fixTitle();

    $initials = $GLOBALS["fpSystemSettings"]["schoolInitials"];
		
		$pC .= $this->drawCurvedTitle("$course->subjectID $course->courseNum$otherValidNames <!--EQV1-->");
		$boolTransferEqv = true;
		if ($course->boolTransfer)
		{
			// This is a transfer course.  Begin by displaying the transfer credit's
			// information.
			
			$course->courseTransfer->loadDescriptiveTransferData($this->student->studentID);
			$hrs = $course->courseTransfer->getHours()*1;
			if ($hrs == 0)
			{
				$hrs = $course->getHours();
			}
						
			// make transfer course titles all caps.
			$course->courseTransfer->title = strtoupper($course->courseTransfer->title);

			$pC .= "<div style='margin-top: 13px;' class='tenpt'>
				<b>Transfer Credit Information:</b><br>
				<div style='margin-left: 20px;' class='tenpt'>
					Course: " . $course->courseTransfer->subjectID . " " . $course->courseTransfer->courseNum . " 
					- " . $course->courseTransfer->title . " ($hrs hrs)<br>
					Institution: " . $this->fixInstitutionName($course->courseTransfer->institutionName) . "<br>
					Term: " . $course->getTermDescription() . "<br>
					<!-- Grade: " . $course->grade . "<br> -->
					";

			$transferEqvText = $course->courseTransfer->transferEqvText;
			if ($transferEqvText == "")
			{
				$transferEqvText = "Not entered or not applicable.";
				$boolTransferEqv = false;
			}

			$pC .= "$initials Eqv: $transferEqvText<br>
				</div>
					</div>";

		}


		$pC .= "
		   	<div style='margin-top: 13px;'>
				<div class='tenpt'>";
		if ($course->courseID != 0)
		{
		  $useHours = $courseHours;
			if ($course->boolTransfer)
			{
				$pC .= "<b>$initials Equivalent Course Information:</b><br>
						<b>$course->subjectID $course->courseNum</b> - ";
				$newCourse = new Course();
				$newCourse->courseID = $course->courseID;
				$newCourse->loadDescriptiveData();
				$useHours = $newCourse->getCatalogHours();
			}
			$pC .= "
					<b>$course->title ($useHours hrs)</b>";
		}
		if ($course->boolSubstitutionNewFromSplit || $course->boolSubstitutionSplit)
		{
			$pC .= "<div class='tenpt' style='margin-bottom:5px;'>
						<i>This course's hours were split in a substitution.</i> 
						<a href='javascript: alertSplitSub();'>?</a>
					</div>";
		}

		$pC .= "</div>";

		if ($course->courseID != 0)
		{
			$pC .= "
			<div class='tenpt'>
					$course->description
				</div>
			</div>
				"; 
		}

		if ($course->boolTransfer == true && $course->courseID < 1 && $course->boolSubstitution == false)
		{ // No local eqv!

			$pC .= "<div class='tenpt' style='margin-top: 10px;'><b>Note:</b> ";
			/*
			$pC .= "
			<b>Note:</b> This course is a transfer credit which
			the student completed at <i>";

			$pC .= $this->fixInstitutionName($course->courseTransfer->institutionName) . "</i>.";
			*/
			$pC = str_replace("<!--EQV1-->"," (Transfer Credit)",$pC);
			if (!$boolTransferEqv)
			{
				$tMsg = "This course does not have an assigned $initials equivalency, or the equivalency
							has been removed for this student.
						Ask your advisor if this course will count towards your degree.
					</div>"; 
			} else {
				$tMsg = "FlightPath cannot assign this course to a $initials equivalency on
							the student's degree plan, 
							or the equivalency
							has been removed for this student.
						Ask your advisor if this course will count towards your degree.
					</div>"; 				
			}

			$pC .= $tMsg;

		} elseif ($course->boolTransfer == true && $course->courseID > 0 && $course->boolSubstitution == false)
		{ // Has a local eqv!

			$tSI = $course->courseTransfer->subjectID;
			$tCN = $course->courseTransfer->courseNum;
			/*			$pC .= "<div class='tenpt' style='margin-top: 10px;'>
			<b>Note:</b> The course listed above is equivalent
			to <b>$tSI $tCN</b>,
			which the student completed at <i>";

			// Replace the temporary comment <!--EQV1--> in the header with
			// the new eqv information.
			*/
			$pC = str_replace("<!--EQV1-->"," (Transfer Credit $tSI $tCN)",$pC);
			/*			$pC .= $this->fixInstitutionName($course->courseTransfer->institutionName);
			$pC .= "</i>.";
			*/
			// Admin function only.
			if ($_SESSION["fpCanSubstitute"] == true)
			{
				$pC .= "<div align='left' class='tenpt'>
					<b>Special administrative function:</b>
						<a href='javascript: popupUnassignTransferEqv(\"" . $course->courseTransfer->courseID . "\");'>Remove this equivalency?</a></div>";
				$pC .= "</div>";
			}


			$pC .= "</div>";
		}


		if ($course->termID != "" && $course->termID != "11111" && $course->displayStatus != "eligible" && $course->displayStatus != "disabled")
		{
			$pC .= "<div class='tenpt' style='margin-top: 10px;'>
						The student enrolled in this course in " . $course->getTermDescription() . ".
					</div>";
		} else if ($course->termID == "11111")
		{
			$pC .= "<div class='tenpt' style='margin-top: 10px;'>
						The exact date that the student enrolled in this course
						cannot be retrieved at this time.  Please check the
						student's official transcript for more details.
					</div>";

		}

		if ($course->assignedToGroupID*1 > 0 && $course->grade != "" && $course->boolTransfer != true && $course->boolSubstitution != true)
		{
			//$g = new Group($course->assignedToGroupID);
			$g = new Group();
			$g->groupID = $course->assignedToGroupID;
			$g->loadDescriptiveData();

			$pC .= "<div class='tenpt' style='margin-top: 10px;'>
						<img src='$this->themeLocation/images/icons/$g->iconFilename' width='19' height='19'>
						&nbsp;
						This course is a member of $g->title.
					";
			// If user is an admin...
			if ($_SESSION["fpCanSubstitute"] == true)
			{
				$tflag = intval($course->boolTransfer);
				$pC .= "<div align='left' class='tenpt'>
					<b>Special administrative function:</b>
						<a href='javascript: popupUnassignFromGroup(\"$course->courseID\",\"$course->termID\",\"$tflag\",\"$g->groupID\");'>Remove from this group?</a></div>";
				$pC .= "</div>";
			}

		} else if ($course->grade != "" && $course->boolTransfer != true && $course->boolSubstitution != true && $course->boolHasBeenAssigned == true) {
			// Course is not assigned to a group; it's on the bare degree plan.  groupID = 0.
			// If user is an admin...
			if ($_SESSION["fpCanSubstitute"] == true)
			{
				$tflag = intval($course->boolTransfer);
				$pC .= "<div align='left' class='tenpt'>
					<b>Special administrative function:</b>
						<a href='javascript: popupUnassignFromGroup(\"$course->courseID\",\"$course->termID\",\"$tflag\",\"0\");'>Remove from the degree plan?</a></div>";
				$pC .= "</div>";
			}

		}


		// Substitutors get extra information:
		if ($_SESSION["fpCanSubstitute"] == true && $course->assignedToGroupID > 0)
		{
			
			
			$pC .= "<div class='tenpt' style='margin-top: 20px;'>
					<b>Special administrative information:</b>
					
				<span id='viewinfolink'
				onClick='document.getElementById(\"admin_info\").style.display=\"\"; this.style.display=\"none\"; '
				class='hand' style='color: blue;'
				> - Click to show -</span>					
					
					<div style='padding-left: 20px; display:none;' id='admin_info'>
					";

			// Course is assigned to a group.
			if ($course->assignedToGroupID > 0) {
  			$group = new Group();
  			$group->groupID = $course->assignedToGroupID;
  			$group->loadDescriptiveData();
  			
  			$pC .= "
  					Course is assigned to group:<br>
  					&nbsp; Group ID: $group->groupID<br>
  					&nbsp; Title: $group->title<br>";
  			if ($_SESSION["fpUserType"] == "full_admin")
  			{ // only show if we are full admin.
  				$pC .= "&nbsp; <i>Internal name: $group->groupName</i><br>";
  			}
  			
  			$pC .= "&nbsp; Catalog year: $group->catalogYear
  			";
			}
			$pC .= "
					</div>
					
					</div>";								
		}


		if ($course->boolSubstitution == true)
		{
			// Find out who did it and if they left any remarks.
			$db = $this->db;
			$temp = $db->getSubstitutionDetails($course->dbSubstitutionID);
			$by = $db->getFacultyName($temp["facultyID"], false);
			$remarks = $temp["remarks"];
			$ondate = date("n/d/Y", strtotime($temp["datetime"]));
			
			
			if ($by != "")
			{
				$by = " by $by, on $ondate.";
			}

			if ($remarks != "")
			{
				$remarks = " Substitution remarks: <i>$remarks</i>.";
			}

			$forthecourse = "for the original course
						requirement of <b>" . $course->courseSubstitution->subjectID . " 
						" . $course->courseSubstitution->courseNum . "</b>";
			if ($temp["requiredCourseID"]*1 == 0)
			{
				$forthecourse = "";
			}

			$pC .= "<div class='tenpt' style='margin-top: 10px;'>
						<b>Note:</b> This course was substituted into the 
						degree plan $forthecourse
						$by$remarks";

			
			if ($_SESSION["fpCanSubstitute"] == true)
			{
				$pC .= "<div align='left' class='tenpt' style='padding-left: 10px;'>
					<b>Special administrative function:</b>
					<a href='javascript: popupRemoveSubstitution(\"$course->dbSubstitutionID\");'>Remove substitution?</a>
					</div>";
			}

		}

		// Only show if the course has not been taken...
		if ($course->hasVariableHours() && $course->grade == "")
		{
			$pC .= "<div class='tenpt' style='margin-top: 10px;'>
					This course has variable hours.<br>Please select 
					how many hours this course will be worth:<br>
					<center>
					<select name='selHours' id='selHours' onChange='popupSetVarHours();'>
					";
			
			// Correct for ghost hours, if they are there.
			$minH = $course->minHours;
			$maxH = $course->maxHours;
			if ($course->boolGhostMinHour) $minH = 0;
			if ($course->boolGhostHour) $maxH = 0;
			
			for($t = $minH; $t <= $maxH; $t++)
			{
				$sel = "";
				if ($t == $course->advisedHours){ $sel = "SELECTED"; }
				$pC .= "<option value='$t' $sel>$t</option>";
			}
			$pC .= "</select> hours.<br>
					
					</center>
					</div>";

			if ($course->advisedHours > -1)
			{
				$varHoursDefault = $course->advisedHours;
			} else {
				$varHoursDefault = $minH;
			}

		}



		if ($showAdvisingButtons == true && !$this->boolBlank)
		{

			// Insert a hidden radio button so the javascript works okay...
			$pC .= "<input type='radio' name='course' value='$course->courseID' checked='checked'
					style='display: none;'>
					<input type='hidden' name='varHours' id='varHours' value='$varHoursDefault'>";

			if ($_SESSION["fpCanAdvise"] == true)
			{
				$pC .= "<div style='margin-top: 20px;'>
				" . $this->drawButton("Select Course", "popupAssignSelectedCourseToGroup(\"$group->assignedToSemesterNum\", \"$group->groupID\",\"$advisingTermID\",\"$dbGroupRequirementID\");", true, "style='font-size: 10pt;'") . "
				</div>
				
				";
			}
		} elseif ($showAdvisingButtons == false && $course->hasVariableHours() == true && $course->grade == "")
		{
			// Show an "update" button, and use the course's assignedToGroupID and
			// assignedToSemesterNum.
			$pC .= "
					<input type='hidden' name='varHours' id='varHours' value='$varHoursDefault'>";


			$pC .= "<input type='button' value='Update'
				onClick='popupUpdateSelectedCourse(\"$course->courseID\",\"$course->assignedToGroupID\",\"$course->assignedToSemesterNum\",\"$course->randomID\",\"$advisingTermID\");'>";

		}


		return $pC;
	}




	/**
	 * Simple function to make an institution name look more pretty, because
	 * all institution names pass through ucwords(), sometimes the capitalization
	 * gets messed up.  This function tries to correct it.
	 * 
	 * Feel free to override it and add to it, if needed.
	 *
	 * @param string $str
	 * @return string
	 */
	function fixInstitutionName($str)
	{
		$str = str_replace("-", " - ", $str);
		$str = ucwords(strtolower($str));
		$str = str_replace(" Of ", " of ", $str);
		$str = str_replace("clep", "CLEP", $str);
		$str = str_replace("Clep", "CLEP", $str);
		$str = str_replace("Act", "ACT", $str);
		$str = str_replace("Sat", "SAT", $str);
		$str = str_replace("Ap ", "AP ", $str);
		$str = str_replace("Dsst", "DSST", $str);
		
		// Fix school initials.
		// Turns "Ulm" into "ULM"
	  $schoolInitials = $GLOBALS["fpSystemSettings"]["schoolInitials"];
		$str = str_replace(ucwords(strtolower($schoolInitials)), $schoolInitials, $str);		
		

		if ($str == "")
		{
			$str = "<i>unknown institution</i>";
		}



		return $str;
	}

	/**
	 * Left in for legacy reasons, this function uses a new Course object's
	 * method of $course->fixTitle to make a course's title more readable.
	 *
	 * @param string $str
	 * @return stromg
	 */
	function fixCourseTitle($str)
	{
		/*		$str = str_replace("&", " & ", $str);

		$str = ucwords(strtolower(trim($str)));
		$str = str_replace("Ii","II",$str);
		$str = str_replace("IIi","III",$str);
		$str = str_replace("Iv","IV",$str);
		$str = str_replace("Vi","VI",$str);


		if ($str == "")
		{
		$str = "Title not available";
		}
		*/


		$newCourse = new Course();
		$str = $newCourse->fixTitle($str);


		return $str;
	}


	/**
	 * Given a Semester object, this will generate the HTML to draw it out
	 * to the screen.
	 *
	 * @param Semester $semester
	 * @param bool $boolDisplayHourCount
	 *       - If set to TRUE, it will display a small "hour count" message
	 *         at the bottom of each semester, showing how many hours are in
	 *         the semester.  Good for debugging purposes.
	 * 
	 * @return string
	 */
	function displaySemester(Semester $semester, $boolDisplayHourCount = false)
	{
		// Display the contents of a semester object
		// on the screen (in HTML)
		$pC = "";
		$pC .= $this->drawSemesterBoxTop($semester->title);

		$countHoursCompleted = 0;

		// First, display the list of bare courses.

		$semester->listCourses->sortAlphabeticalOrder();
		$semester->listCourses->resetCounter();
		//print_pre($semester->listCourses->toString());
		while($semester->listCourses->hasMore())
		{
			$course = $semester->listCourses->getNext();
			//$pC .= "<tr><td colspan='8'>";
			// Is this course being fulfilled by anything?

			//if (is_object($course->courseFulfilledBy))
			if (!($course->courseListFulfilledBy->isEmpty))
			{ // this requirement is being fulfilled by something the student took...

				//$pC .= $this->drawCourseRow($course->courseFulfilledBy);
				$pC .= $this->drawCourseRow($course->courseListFulfilledBy->getFirst());
				$course->courseListFulfilledBy->getFirst()->boolHasBeenDisplayed = true;

				//$countHoursCompleted += $course->courseFulfilledBy->hoursAwarded;
				if ($course->courseListFulfilledBy->getFirst()->displayStatus == "completed")
				{ // We only want to count completed hours, no midterm or enrolled courses.
					$h = $course->courseListFulfilledBy->getFirst()->hoursAwarded;
					if ($course->courseListFulfilledBy->getFirst()->boolGhostHour == TRUE) {
					  $h = 0;
					}
					$countHoursCompleted += $h;
				}

			} else {
				// This requirement is not being fulfilled...
				$pC .= $this->drawCourseRow($course);

			}

			//$pC .= "</td></tr>";

		}


		// Now, draw all the groups.
		$semester->listGroups->sortAlphabeticalOrder();
		$semester->listGroups->resetCounter();
		while($semester->listGroups->hasMore())
		{
			//adminDebug("dddd");
			$group = $semester->listGroups->getNext();
			$pC .= "<tr><td colspan='8'>";
			$pC .= $this->displayGroup($group);
			$countHoursCompleted += $group->hoursFulfilledForCredit;
			$pC .= "</td></tr>";
		}

		// Add hour count to the bottom...
		if ($boolDisplayHourCount == true && $countHoursCompleted > 0)
		{
			$pC .= "<tr><td colspan='8'>
				<div class='tenpt' style='text-align:right; margin-top: 10px;'>
				Completed hours: $countHoursCompleted
				</div>
				";
			$pC .= "</td></tr>";
		}


		// Does the semester have a notice?
		if ($semester->notice != "")
		{
			$pC .= "<tr><td colspan='8'>
					<div class='hypo tenpt' style='margin-top: 15px; padding: 5px;'>
						<b>Important Notice:</b> $semester->notice
					</div>
					</td></tr>";
		}

		$pC .= $this->drawSemesterBoxBottom();

		return $pC;
	}


	/**
	 * This function displays a Group object on the degree plan.  This is not
	 * the selection popup display.  It will either show the group as multi
	 * rows, filled in with courses, or as a "blank row" for the user to click
	 * on.
	 *
	 * @param Group $placeGroup
	 * @return string
	 */
	function displayGroup(Group $placeGroup)
	{
		// Display a group, either filled in with courses,
		// and/or with a "blank row" for the user to
		// click on.
		$pC = "";

		// Now, if you will recall, all of the groups and their courses, etc,
		// are in the degreePlan's listGroups.  The $placeGroup object here
		// is just a placeholder.  So, get the real group...

		if (!$group = $this->degreePlan->findGroup($placeGroup->groupID))
		{
			adminDebug("Group not found.");
			return;
		}

		if ($group->title == "Core Fine Arts" )
		{
			//print_pre($group->toString());
			//adminDebug($group->getFulfilledHours());
		}



		$title = $group->title;

		$displayCourseList = new CourseList();

		/*
		// Display the title of the Group...
		$pC .= "<tr><td colspan='8' class='tenpt'>
		&nbsp;<b>$title</b>
		</td></tr>";
		*/
		// Okay, first look for courses in the first level
		// of the group.
		//$group->listCourses->sortAlphabeticalOrder();

		$displaySemesterNum = $placeGroup->assignedToSemesterNum;
		//adminDebug("$group->title $displaySemesterNum");

		$group->listCourses->removeUnfulfilledAndUnadvisedCourses();
		/*		if ($group->groupID == 660701)
		{
		//print_pre($group->toString());
		}
		*/		$group->listCourses->resetCounter();
		while($group->listCourses->hasMore())
		{
			$course = $group->listCourses->getNext();

			// Do we have enough hours to keep going?
			$fulfilledHours = $displayCourseList->countHours();
			$remaining = $placeGroup->hoursRequired - $fulfilledHours;
			//adminDebug("$group->title ff: $fulfilledHours rem: $remaining c:" . $course->toString());

			// If the course in question is part of a substitution that is not
			// for this group, then we should skip it.
			if (!($course->courseListFulfilledBy->isEmpty))
			{
				$tryC = $course->courseListFulfilledBy->getFirst();
				if ($tryC->boolSubstitution == true && $tryC->assignedToGroupID != $group->groupID)
				{
					//adminDebug($tryC->toString() . " subbed not in group $group->title.");
					continue;
				}
			}


			//adminDebug($course->toString());
			//if (is_object($course->courseFulfilledBy) && $course->courseFulfilledBy->boolHasBeenDisplayed != true && $course->boolHasBeenDisplayed != true)
			if (!($course->courseListFulfilledBy->isEmpty) && $course->courseListFulfilledBy->getFirst()->boolHasBeenDisplayed != true && $course->boolHasBeenDisplayed != true)
			{
				//$pC .= "<tr><td colspan='8'>";
				//$titleText = "This course is a member of $group->title.";
				//$pC .= $this->drawCourseRow($course->courseFulfilledBy, $group->iconFilename, $titleText);
				//$pC .= "</td></tr>";
				//$c = $course->courseFulfilledBy;
				$c = $course->courseListFulfilledBy->getFirst();
				if ($remaining < $c->getHours())
				{
					//adminDebug("here for " . $c->toString());
					continue;
				}

				//adminDebug("here $c->courseID $group->title " . $c->toString());
				$c->tempFlag = false;
				$c->iconFilename = $group->iconFilename;
				$c->titleText = "This course is a member of $group->title.";
				//if (!$displayCourseList->findMatch($c))
				//{ // Make sure it isn't already in the display list.
				$displayCourseList->add($c);
				//}

				//adminDebug("display " . $c->toString());


			}

			if ($course->boolAdvisedToTake && $course->boolHasBeenDisplayed != true && $course->assignedToSemesterNum == $displaySemesterNum)
			{
				//$pC .= "<tr><td colspan='8'>";
				//$titleText = "The student has been advised to take this course to fulfill a $group->title requirement.";
				//$pC .= $this->drawCourseRow($course, $group->iconFilename, $titleText, true);
				//$pC .= "</td></tr>";
				$c = $course;
				if ($remaining < $c->getHours())
				{
					continue;
				}

				$c->tempFlag = true;
				$c->iconFilename = $group->iconFilename;
				$c->titleText = "The student has been advised to take this course to fulfill a $group->title requirement.";
				//if (!$displayCourseList->findMatch($c))
				//{ // Make sure it isn't already in the display list.
				$displayCourseList->add($c);
				//}
				//adminDebug($c->toString());

				// Take off remaining hours!

			}
		}


		// Alright, now we will see if this group has any branches
		// (groups within groups).
		//adminDebug("-_-_-_-_ Looking in Group $group->title -_-");
		//print_pre($group->toString());

		$group->listGroups->resetCounter();
		while($group->listGroups->hasMore())
		{
			$branch = $group->listGroups->getNext();
			// look for courses at this level...
			if (!$branch->listCourses->isEmpty)
			{

				$branch->listCourses->sortAlphabeticalOrder();
				$branch->listCourses->resetCounter();
				while($branch->listCourses->hasMore())
				{
					$course = $branch->listCourses->getNext();
					//adminDebug("Examining course " . $course->toString());
					// Do we have enough hours to keep going?
					$fulfilledHours = $displayCourseList->countHours();
					$remaining = $placeGroup->hoursRequired - $fulfilledHours;
					//adminDebug($course->boolHasBeenDisplayed);
					//if (is_object($course->courseFulfilledBy) && $course->courseFulfilledBy->boolHasBeenDisplayed != true && $course->boolHasBeenDisplayed != true)
					if (!($course->courseListFulfilledBy->isEmpty) && $course->courseListFulfilledBy->getFirst()->boolHasBeenDisplayed != true && $course->boolHasBeenDisplayed != true)
					{
						//adminDebug("got in here");
						//$pC .= "<tr><td colspan='8'>";
						//$titleText = "This course is a member of $group->title.";
						//$pC .= $this->drawCourseRow($course->courseFulfilledBy, $group->iconFilename, $titleText);
						//$pC .= "</td></tr>";

						//$c = $course->courseFulfilledBy;
						$c = $course->courseListFulfilledBy->getFirst();
						if ($remaining < $c->getHours() || $remaining < 1)
						{
							//adminDebug(" - Skip course because remaining hours are gone.");
							continue;
						}

						$c->tempFlag = false;
						$c->iconFilename = $group->iconFilename;
						$c->titleText = "This course is a member of $group->title.";
						//						adminDebug("-- Add fulfulled course to display list.");
						if (!$displayCourseList->findMatch($c))
						{ // Make sure it isn't already in the display list.
							//adminDebug("adding course!");
							$displayCourseList->add($c);
						} else if (is_object($c->courseTransfer))
						{
							if (!$displayCourseList->findMatch($c->courseTransfer))
							{ // Make sure it isn't already in the display list.
								//adminDebug("adding transfer course!");
								$displayCourseList->add($c);
							}
						}


					}

					if ($course->boolAdvisedToTake && $course->boolHasBeenDisplayed != true && $course->assignedToSemesterNum == $displaySemesterNum)
					{
						//adminDebug($course->toString());
						//$pC .= "<tr><td colspan='8'>";
						//$titleText = "The student has been advised to take this course to fulfill a $group->title requirement.";
						//$pC .= $this->drawCourseRow($course, $group->iconFilename, $titleText, true);
						//$pC .= "</td></tr>";
						//adminDebug($course->toString() . " - " . $course->boolHasBeenDisplayed);

						$c = $course;
						if ($remaining < $c->getHours() || $remaining < 1)
						{
							//adminDebug(" - Skip course because remaining hours are gone.");
							continue;
						}

						$c->tempFlag = true;
						$c->iconFilename = $group->iconFilename;
						$c->titleText = "The student has been advised to take this course to fulfill a $group->title requirement.";
						if (!$displayCourseList->findMatch($c))
						{
							$displayCourseList->add($c);
						}

					}


				}

			}
		}






		//$displayCourseList->removeDuplicates();
		//$displayCourseList->sortAlphabeticalOrder();

		$displayCourseList->sortAdvisedLastAlphabetical();


		$pC .= $this->displayGroupCourseList($displayCourseList, $group, $displaySemesterNum);
		//if ($group->title == "Major Ensemble"){print_pre($group->toString());	}

		$fulfilledHours = $displayCourseList->countHours("", false, false, true);
		$fulfilledCreditHours = $displayCourseList->countCreditHours("",false,true);
		

		$testHours = $fulfilledHours;
		// if the fulfilledCreditHours is > than the fulfilledHours,
		// then assign the fulfilledCreditHours to the testHours.
		if ($fulfilledCreditHours > $fulfilledHours)
		{ // done to fix a bug involving splitting hours in a substitution.		  
			$testHours = $fulfilledCreditHours;
		} 
		// If there are any remaining hours in this group,
		// draw a "blank" selection row.
		$remaining = $placeGroup->hoursRequired - $testHours;
		//adminDebug("$placeGroup->title $placeGroup->hoursRequired $testHours");
		$placeGroup->hoursRemaining = $remaining;
		$placeGroup->hoursFulfilled = $fulfilledHours;
		$placeGroup->hoursFulfilledForCredit = $fulfilledCreditHours;
		if ($remaining > 0)
		{
			$pC .= "<tr><td colspan='8' class='tenpt'>";
			$pC .= $this->drawGroupSelectRow($placeGroup, $remaining);
			$pC .= "</td></tr>";
		}


		return $pC;
	}


	/**
	 * Find all instaces of a Course in a Group and mark as displayed.
	 *
	 * @param Group $group
	 * @param Course $course
	 */
	function markCourseAsDisplayed(Group $group, Course $course)
	{
		// Find all instances of $course in $group,
		// and mark as displayed.

		if ($objList = $group->listCourses->findAllMatches($course))
		{
			$courseList = CourseList::cast($objList);
			//adminDebug("marking all displayed: " . $course->toString());
			$courseList->markAsDisplayed();
		}
		// Now, go through all the course lists within each branch...
		$group->listGroups->resetCounter();
		while($group->listGroups->hasMore())
		{
			$g = $group->listGroups->getNext();
			if ($objList = $g->listCourses->findAllMatches($course))
			{

				$courseList = CourseList::cast($objList);
				$courseList->markAsDisplayed($semesterNum);
			}
		}


	}



  /**
   * Displays all the courses in a CourseList object, using 
   * the drawCourseRow function.
   * 
   * It looks like the group and semesterNum are not being used
   * anymore.
   * 
   * @todo Check on unused variables.
   *
   * @param CourseList $courseList
   * @param unknown_type $group
   * @param unknown_type $semesterNum
   * @return unknown
   */
	function displayGroupCourseList($courseList, $group, $semesterNum)
	{
		$courseList->resetCounter();
		while($courseList->hasMore())
		{
			$course = $courseList->getNext();

			//adminDebug("disp: $group->title : " . $course->toString());
			if ($course->boolTransfer == true)
			{
				if (is_object($course->courseTransfer))
				{
					//	debugCT (" - - trns: " . $course->courseTransfer->toString());
				} else {
					//debugCT (" - - trns_noeqv: " . $course->toString());
				}
			}


			//$pC .= "<tr><td colspan='8'>";
			$pC .= $this->drawCourseRow($course, $course->iconFilename, $course->titleText, $course->tempFlag);

			// Doesn't matter if its a specified repeat or not.  Just
			// mark it as having been displayed.
			$course->boolHasBeenDisplayed = true;
			/*
			if ($course->boolSpecifiedRepeat != true)
			{
			// Regular, non-repeat courses.  Mark all of them
			// as displayed, in all branches.
			//$this->markCourseAsDisplayed($group, $course);

			$course->boolHasBeenDisplayed = true;
			adminDebug("Marking displayed: " . $course->toString());
			} else {
			// This is a course which is supposed to be repeated.
			// So, only mark this one instance as displayed.

			$course->boolHasBeenDisplayed = true;

			}
			*/
			//$pC .= "</td></tr>";
		}
		return $pC;

	}


	/**
	 * This draws the "blank row" for a group on the degree plan, which instructs
	 * the user to click on it to select a course from the popup.
	 *
	 * @param Group $group
	 * @param int $remainingHours
	 * @return string
	 */
	function drawGroupSelectRow(Group $group, $remainingHours)
	{
		$pC = "";
		$imgPath = $this->themeLocation . "/images";
		$onMouseOver = " onmouseover=\"style.backgroundColor='#FFFF99'\"
      				onmouseout=\"style.backgroundColor='white'\" ";

		if ($this->pageIsMobile) $onMouseOver = "";  // Causes problems for some mobile devices.
		
		$w1_1 = $this->widthArray[0];
		$w1_2 = $this->widthArray[1];
		$w1_3 = $this->widthArray[2];
		$w2 = $this->widthArray[3];
		$w3 = $this->widthArray[4];
		$w4 = $this->widthArray[5];
		$w5 = $this->widthArray[6];
		$w6 = $this->widthArray[7];

		$s = "s";
		if ($remainingHours < 2)
		{
			$s = "";
		}
		$selectIcon = "<img src='$imgPath/select.gif' border='0'>";
		$iconLink = "<img src='$imgPath/icons/$group->iconFilename' width='19' height='19' border='0' alt='$titleText' title='$titleText'>";

		$blankDegreeID = "";
		if ($this->boolBlank)
		{
			$blankDegreeID = $this->degreePlan->degreeID;
		}

		$jsCode = "selectCourseFromGroup(\"$group->groupID\", \"$group->assignedToSemesterNum\", \"$remainingHours\", \"$blankDegreeID\");";

		$rowMsg = "<i>Click <font color='red'>&gt;&gt;</font> to select $remainingHours hour$s.</i>";
		$handClass = "hand";

		if ($this->boolPrint)
		{
			// In print view, disable all popups and mouseovers.
			$onMouseOver = "";
			$jsCode = "";
			$handClass = "";
			$rowMsg = "<i>Select $remainingHours hour$s from $group->title.</i>";
		}


		if ($group->groupID == -88)
		{ // This is the Add a Course group.
			$rowMsg = "<i>Click to add an additional course.</i>";
			$selectIcon = "<span style='font-size: 16pt; color:blue;'>+</span>";
			$iconLink = "";
		}


		$pC .= "
   		<table border='0' cellpadding='0' width='100%' cellspacing='0' align='left'>
     	<tr height='20' class='$handClass'
      		$onMouseOver title='$group->title'>
      		<td width='$w1_1' align='left'>&nbsp;</td>
      		<td width='$w1_2' align='left' onClick='$jsCode'>$iconLink</td>
      		<td width='$w1_3' align='left' onClick='$jsCode'>$selectIcon</td>
      		<td align='left' colspan='5' class='tenpt underline' onClick='$jsCode'>
      		$rowMsg
       				
     	</tr>
     	</table>";		





		return $pC;
	}

	/**
	 * Uses the drawBoxTop function, specifically for semesters.
	 *
	 * @param string $title
	 * @param bool $hideheaders
	 * @return string
	 */
	function drawSemesterBoxTop($title, $hideheaders = false)
	{

	  $w = 300;
	  if ($this->pageIsMobile) $w = "100%";
		return $this->drawBoxTop($title, $hideheaders, $w);
	}

	/**
	 * Uses the drawBoxBottom function, specifically for semesters.
	 * Actually, this function is a straight alias for $this->drawBoxBottom().
	 *
	 * @return string
	 */
	function drawSemesterBoxBottom()
	{
		return $this->drawBoxBottom();
	}

	/**
	 * Very, very simple.  Just returns "</table>";
	 *
	 * @return string
	 */
	function drawBoxBottom()
	{
		return "</table>";
	}

	/**
	 * Used to draw the beginning of semester boxes and other boxes, for example
	 * the footnotes.
	 *
	 * @param string $title
	 * @param bool $hideheaders
	 *       - If TRUE, then the course/hrs/grd headers will not be displayed.
	 * 
	 * @param int $tableWidth
	 *       - The HTML table width, in pixels.  If not set, it will default
	 *         to 300 pixels wide.
	 * 
	 * @return string
	 */
	function drawBoxTop($title, $hideheaders=false, $tableWidth = 300)
	{ // returns the beginnings of the year tables...

		// Get width values from widthArray (supplied by calling function,
		// for example, draw_year_box_top
		$w1_1 = $this->widthArray[0];
		$w1_2 = $this->widthArray[1];
		$w1_3 = $this->widthArray[2];
		$w2 = $this->widthArray[3];
		$w3 = $this->widthArray[4];
		$w4 = $this->widthArray[5];
		$w5 = $this->widthArray[6];
		$w6 = $this->widthArray[7];

		if ($this->boolPopup == true)
		{
			$w1_1 = $this->popupWidthArray[0];
			$w1_2 = $this->popupWidthArray[1];
			$w1_3 = $this->popupWidthArray[2];
			$w2 = $this->popupWidthArray[3];
			$w3 = $this->popupWidthArray[4];
			$w4 = $this->popupWidthArray[5];
			$w5 = $this->popupWidthArray[6];
			$w6 = $this->popupWidthArray[7];
		}


		$headers = array();
		if ($hideheaders != true)
		{
			$headers[0] = "Course";
			$headers[1] = "Hrs";
			$headers[2] = "Grd";
			$headers[3] = "Pts";
		}


		$rtn = "
		   <table border='0' width='$tableWidth' cellpadding='0' cellspacing='0' class='fp-box-top'>
   			<tr>
    		<td colspan='8' class='blueTitle' align='center' valign='top'>
    				";
		$rtn .= $this->drawCurvedTitle($title);

		$rtn .= "
    		</td>
   			</tr>
   					";
		if (!$hideHeaders)
		{
			$rtn .= "
   			<tr height='20'>

    			<td width='$w1_1' align='left'>
     			&nbsp;
    			</td>

    			<td width='$w1_2' align='left'>
     			&nbsp;
    			</td>

    			<td width='$w1_3' align='left'>
     			&nbsp;
    			</td>
    
        		<td align='left' width='$w2'>
     				<font size='2'><b>$headers[0]</b></font>
	    		</td>

    			<td width='$w3' align='left'>&nbsp;</td>
    			<td width='$w4'>
     				<font size='2'><b>$headers[1]</b></font>
    			</td>
    			<td width='$w5'>
     				<font size='2'><b>$headers[2]</b></font>
    			</td>
    			<td width='$w6'>
     				<font size='2'><b>$headers[3]</b></font>
    			</td>
   			</tr>
   				";
		}
		return $rtn;

	} // draw_year_box_top



	/**
	 * Will draw a string in a pretty curved box.  Used for displaying semester
	 * titles.
	 *
	 * @param string $title
	 * @return string
	 */
	function drawCurvedTitle($title)
	{
		// Will simply draw a curved title bar containing the $title
		// as the text.
		$imgPath = $this->themeLocation . "/images";

		$rtn = "
     <table border='0' class='blueTitle' width='100%' cellpadding='0' cellspacing='0'>
       <tr>
        <td width='10%' align='left' valign='top'><img src='$imgPath/corner_tl.gif'></td>
        <td width='80%' align='center' rowspan='2'>
         <span class='tenpt'><b>$title</b></span>
        </td>
        <td width='10%' align='right' valign='top'><img src='$imgPath/corner_tr.gif'></td>
       </tr>
       <tr>
        <td align='left' valign='bottom'><img src='$imgPath/corner_bl.gif'></td>
        <td align='right' valign='bottom'><img src='$imgPath/corner_br.gif'></td>
       </tr> 
      </table>
	";

		return $rtn;

	} // draw_curved_title



	/**
	 * This is used by lots of other functions to display a course on the screen.
	 * It will show the course, the hours, the grade, and quality points, as well
	 * as any necessary icons next to it.
	 *
	 * @param Course $course
	 * @param string $iconFilename
	 * @param string $titleText
	 * @param bool $jsToggleAndSave
	 *         - If set to TRUE, when the checkbox next to this course is clicked,
	 *           the page will be submitted and a draft will be saved.
	 * 
	 * @param bool $boolDisplayCheck
	 *         - If set to FALSE, no checkbox will be displayed for this course row.
	 * 
	 * @param bool $boolAddFootnote
	 * @param bool $boolAddAsteriskToTransfers
	 *
	 * @return string
	 */
	function drawCourseRow(Course $course, $iconFilename = "", $titleText = "", $jsToggleAndSave = false, $boolDisplayCheck = true, $boolAddFootnote = true, $boolAddAsteriskToTransfers = false)
	{
		// Display a course itself...
		$pC = "";
		$w1_1 = $this->widthArray[0];
		$w1_2 = $this->widthArray[1];
		$w1_3 = $this->widthArray[2];
		$w2 = $this->widthArray[3];
		$w3 = $this->widthArray[4];
		$w4 = $this->widthArray[5];
		$w5 = $this->widthArray[6];
		$w6 = $this->widthArray[7];

		$imgPath = $this->themeLocation . "/images";
		
		// The current term we are advising for.
		$advisingTermID = $GLOBALS["advisingTermID"];

		$course->assignDisplayStatus();
		// If the course has already been advised in a different semester,
		// we should set the advisingTermID to that and disable unchecking.
		if ($course->advisedTermID*1 > 0 && $course->boolAdvisedToTake == true && $course->advisedTermID != $advisingTermID)
		{
			$course->displayStatus = "disabled";
			$advisingTermID = $course->advisedTermID;
		}


		if ($course->subjectID == "")
		{
			$course->loadDescriptiveData();
		}


		$subjectID = $course->subjectID;
		$courseNum = $course->courseNum;


		$oSubjectID = $subjectID;
		$oCourseNum = $courseNum;

		$footnote = "";
		$ast = "";
		// Is this actually a transfer course?  If so, display its
		// original subjectID and courseNum.
		if ($course->boolTransfer == true)
		{
			$subjectID = $course->courseTransfer->subjectID;
			$courseNum = $course->courseTransfer->courseNum;
			$institutionName = $course->courseTransfer->institutionName;

			if ($boolAddAsteriskToTransfers == true)
			{
				$course->courseTransfer->loadDescriptiveTransferData($this->student->studentID);
				if ($course->courseTransfer->transferEqvText != "")
				{
					$ast = "*";
					$GLOBALS["advisingCourseHasAsterisk"] = true;
				}
			}

			// Apply a footnote if it has a local eqv.
			if ($boolAddFootnote == true && $course->courseID > 0)
			{
				$footnote = "";

				$footnote .= "<span class='superscript'>T";
				$fcount = count($this->footnoteArray["transfer"]) + 1;
				if ($course->boolHasBeenDisplayed == true)
				{ // If we've already displayed this course once, and are
					// now showing it again (like in the Transfer Credit list)
					// we do not want to increment the footnote counter.
					$fcount = $course->transferFootnote;
				}
				$course->transferFootnote = $fcount;
				$footnote .= "$fcount</span>";
				$this->footnoteArray["transfer"][$fcount] = "$oSubjectID $oCourseNum ~~ $subjectID $courseNum ~~  ~~ $institutionName";
			}
		}


		if ($course->boolSubstitution == true )
		{

			if ($course->courseSubstitution->subjectID == "")
			{ // Reload subjectID, courseNum, etc, for the substitution course,
				// which is actually the original requirement.
				if (is_object($course->courseSubstitution))
				{
					$course->courseSubstitution->loadDescriptiveData();
				} 
				
			}

			$oSubjectID = $course->courseSubstitution->subjectID;
			$oCourseNum = $course->courseSubstitution->courseNum;

			if ($boolAddFootnote == true)
			{
				$footnote = "";
				$footnote .= "<span class='superscript'>S";
				$fcount = count($this->footnoteArray["substitution"]) + 1;
				if ($course->boolHasBeenDisplayed == true)
				{ // If we've already displayed this course once, and are
					// now showing it again (like in the Transfer Credit list)
					// we do not want to increment the footnote counter.
					$fcount = $course->substitutionFootnote;
				}
				$course->substitutionFootnote = $fcount;
				$footnote .= "$fcount</span>";
				$this->footnoteArray["substitution"][$fcount] = "$oSubjectID $oCourseNum ~~ $subjectID $courseNum ~~ $course->substitutionHours ~~ $course->assignedToGroupID";
				
			}
		}

		$hours = $course->hoursAwarded;

		if ($hours*1 < 1)
		{
			$hours = $course->getCatalogHours();
		}

		$hours = $hours * 1;

		$varHourIcon = "&nbsp;";
		
		
		if ($course->hasVariableHours() == true && !$course->boolTaken)
		{
		  // The boolTaken part of this IF statement is because if the course
		  // has been completed, we should only use the hoursAwarded.
		  
			$varHourIcon = "<img src='$this->themeLocation/images/var_hour.gif'
								title='This course has variable hours.'
								alt='This course has variable hours.'>";
			$hours = $course->getAdvisedHours();

		}

		if ($course->boolGhostHour == TRUE) {
		  // This course was given a "ghost hour", meaning it is actually
		  // worth 0 hours, not 1, even though it's hoursAwarded is currently
		  // set to 1.  So, let's just make the display be 0.
		  $hours = "0";
		}
		
		$grade = $course->grade;

		$dispgrade = $grade;
		// If there is a MID, then this is a midterm grade.
		$dispgrade = str_replace("MID","<span class='superscript'>mid</span>",$dispgrade);

		if (strtoupper($grade) == "E")
		{ // Currently enrolled.  Show no grade.
			$dispgrade = "";
		}

		if ($course->boolHideGrade)
		{
		  $dispgrade = "--";
		  $this->boolHidingGrades = true;
		}
		
		$displayStatus =  $course->displayStatus;

		if ($displayStatus == "completed")
		{
			$pts = $this->getQualityPoints($grade, $hours);
		}

		$courseID = $course->courseID;
		$semesterNum = $course->assignedToSemesterNum;
		$groupID = $course->assignedToGroupID;
		$randomID = $course->randomID;
		$advisedHours = $course->advisedHours;

		$uniqueID = $courseID . "_" . $semesterNum . "_" . rand(1,9999);
		$hidName = "advisecourse_$courseID" . "_$semesterNum" . "_$groupID" . "_$advisedHours" . "_$randomID" . "_$advisingTermID" . "_random" . rand(1,9999);
		$hidValue = "";
		$opchecked = "";
		if ($course->boolAdvisedToTake == true)
		{
			$hidValue = "true";
			$opchecked = "-check";
		}

		$opOnClickFunction = "toggleSelection";
		if ($jsToggleAndSave == true)
		{
			$opOnClickFunction = "toggleSelectionAndSave";
		}

		$extraJSVars = "";
		if ($course->displayStatus == "disabled")
		{ // Checkbox needs to be disabled because this was advised in another
			// term.
			$opOnClickFunction = "toggleDisabledChangeTerm";
			$course->termID = $course->advisedTermID;
			$extraJSVars = $course->getTermDescription();

		}

		if ($course->displayStatus == "completed" || $course->displayStatus == "enrolled")
		{
			$opOnClickFunction = "toggleDisabledCompleted";
			$opchecked = "";
			$extraJSVars = $course->displayStatus;
		}

		if ($course->displayStatus == "retake")
		{
			// this course was probably subbed in while the student
			// was still enrolled, and they have since made an F or W.
			// So, disable it.
			$opOnClickFunction = "dummyToggleSelection";
			$opchecked = "";
		}


		if ($this->boolPrint || $this->boolBlank)
		{
			// If this is print view, disable clicking.
			$opOnClickFunction = "dummyToggleSelection";
		}

		if ($_SESSION["fpCanAdvise"] != true)
		{
			// This user does not have the abilty to advise,
			// so take away the ability to toggle anything (like
			// we are in print view).
			$opOnClickFunction = "dummyToggleSelection";
		}

		$op = "<img src='$imgPath/cb_" . $displayStatus . "$opchecked.gif'
					border='0'
					id='cb_$uniqueID'
					onClick='$opOnClickFunction(\"$uniqueID\",\"$displayStatus\",\"$extraJSVars\");'
					>";
		$hid = "<input type='hidden' name='$hidName'
						id='advisecourse_$uniqueID' value='$hidValue'>";

		// Okay, we can't actually serialize a course, as it takes too much space.
		// It was slowing down the page load significantly!  So, I am going
		// to use a function I wrote called toDataString().

		$dataString = $course->toDataString();
		$blankDegreeID = "";
		if ($this->boolBlank == true)
		{
			$blankDegreeID = $this->degreePlan->degreeID;
		}

		$jsCode = "describeCourse(\"$dataString\",\"$blankDegreeID\");";

		$iconLink = "";
		//adminDebug($course->toString() . " RT: " . $course->requirementType);
		if ($course->requirementType == "um" || $course->requirementType == "uc")
		{
			$iconFilename = "ucap.gif";
			$titleText = "This course is a University Capstone.";
		}

		if ($iconFilename != "")
		{
			$iconLink = "<img src='$this->themeLocation/images/icons/$iconFilename' width='19' height='19' border='0' alt='$titleText' title='$titleText'>";
		}

		$onMouseOver = " onmouseover=\"style.backgroundColor='#FFFF99'\"
      				onmouseout=\"style.backgroundColor='white'\" ";

		if ($this->pageIsMobile) $onMouseOver = "";  // Causes problems for some mobile devices.
		
		$handClass = "hand";

		if ($boolDisplayCheck == false)
		{
			$op = $hid = "";
		}


		if ($this->boolPrint)
		{
			// In print view, disable all popups and mouseovers.
			$onMouseOver = "";
			$jsCode = "";
			$handClass = "";
		}


		$pC .= "<tr><td colspan='8'>";


		if ($course->boolSubstitutionNewFromSplit != true || ($course->boolSubstitutionNewFromSplit == true && $course->displayStatus != "eligible"))
		{

			if ($course->boolSubstitution == true)
			{
				//adminDebug($subjectID . $courseNum . " $footnote $hours");

			}

			if ($courseNum == "")
			{
				$courseNum = "&nbsp;";
			}



			$pC .= "
   		<table border='0' cellpadding='0' width='100%' cellspacing='0' align='left'>
     	<tr height='20' class='$handClass $displayStatus'
      		$onMouseOver title='$titleText'>
      		<td width='$w1_1' align='left'>$op$hid</td>
      		<td width='$w1_2' align='left' onClick='$jsCode'>$iconLink</td>
      		<td width='$w1_3' align='left' onClick='$jsCode'>&nbsp;$ast</td>
      		<td align='left' width='$w2' class='tenpt underline' onClick='$jsCode'>
       				$subjectID</td>
       		<td class='tenpt underline' width='$w3' align='left' 
       			onClick='$jsCode'>
        			$courseNum$footnote</td>
	       <td class='tenpt underline' width='$w4' onClick='$jsCode'>$hours$varHourIcon</td>
       	   <td class='tenpt underline' width='$w5' onClick='$jsCode'>$dispgrade&nbsp;</td>
       	   <td class='tenpt underline' width='$w6' onClick='$jsCode'>$pts&nbsp;</td>
     	</tr>
     	</table>";

		} else {
			// These are the leftover hours from a partial substitution.
			//adminDebug("here");
			$pC .= "
   		<table border='0' cellpadding='0' width='100%' cellspacing='0' align='left'>
     	<tr height='20' class='hand $displayStatus'
      		$onMouseOver title='$titleText'>
      		<td width='$w1_1' align='left'>$op$hid</td>
      		<td width='$w1_2' align='left' onClick='$jsCode'>$iconLink</td>
      		<td width='$w1_3' align='left' onClick='$jsCode'>&nbsp;</td>
      		<td align='left' class='tenpt underline' onClick='$jsCode'
      			colspan='4'>
       				&nbsp; &nbsp; $subjectID &nbsp;
        			$courseNum$footnote
	       			&nbsp; ($hours hrs left)
       	   	</td>
     	</tr>
     	</table>";		

		}

		$pC .= "</td></tr>";


		return $pC;
	}


	/**
	 * Calculate the quality points for a grade and hours.
	 *
	 * @param string $grade
	 * @param int $hours
	 * @return int
	 */
	function getQualityPoints($grade, $hours){

		switch ($grade) {
			case 'A':
				$pts = 4 * $hours;
				break;
			case 'B':
				$pts = 3 * $hours;
				break;
			case 'C':
				$pts = 2 * $hours;
				break;
			case 'D':
				$pts = 1 * $hours;
				break;
		}
		return $pts;

	}


  /**
   * Used in the group selection popup, this will display a course with 
   * a radio button next to it, so the user can select it.
   *
   * @param Course $course
   * @param int $groupHoursRemaining
   * 
   * @return string
   */
	function drawPopupGroupSelectCourseRow(Course $course, $groupHoursRemaining = 0)
	{
		// Display a course itself...
		$pC = "";
		$w1_1 = $this->popupWidthArray[0];
		$w1_2 = $this->popupWidthArray[1];
		$w1_3 = $this->popupWidthArray[2];
		$w2 = $this->popupWidthArray[3];
		$w3 = $this->popupWidthArray[4];
		$w4 = $this->popupWidthArray[5];
		$w5 = $this->popupWidthArray[6];
		$w6 = $this->popupWidthArray[7];

		if ($course->subjectID == "")
		{
			// Lacking course's display data, so reload it from the DB.
			$course->loadCourse($course->courseID);
		}


		$subjectID = $course->subjectID;
		$courseNum = $course->courseNum;
		$hours = $course->getCatalogHours();
		$displayStatus = $course->displayStatus;
		$dbGroupRequirementID = $course->dbGroupRequirementID;
		$grade = $course->grade;
		$repeats = $course->specifiedRepeats;
		if ($repeats > 0)
		{
			$w3 = "15%";
		}

		$courseID = $course->courseID;
		$groupID = $course->assignedToGroupID;
		$semesterNum = $course->assignedToSemesterNum;

		$varHourIcon = "&nbsp;";
		if ($course->hasVariableHours() == true)
		{
			$varHourIcon = "<img src='$this->themeLocation/images/var_hour.gif'
								title='This course has variable hours.'
								alt='This course has variable hours.'>";
		}


		$checked = "";
		if ($course->boolSelected == true)
		{
			$checked = " checked='checked' ";
		}
		$op = "<input type='radio' name='course' value='$courseID' $checked>";
		$hid = "<input type='hidden' name='$courseID" . "_subject'
						id='$courseID" . "_subject' value='$subjectID'>
					<input type='hidden' name='$courseID" . "_dbGroupRequirementID'
						id='$courseID" . "_dbGroupRequirementID' value='$dbGroupRequirementID'>";

		$blankDegreeID = "";
		if ($this->boolBlank)
		{
			$blankDegreeID = $this->degreePlan->degreeID;
		}

		//$serializedCourse = urlencode(serialize($course));
		$jsCode = "popupDescribeSelected(\"$groupID\",\"$semesterNum\",\"$courseID\",\"$subjectID\",\"groupHoursRemaining=$groupHoursRemaining&dbGroupRequirementID=$dbGroupRequirementID&blankDegreeID=$blankDegreeID\");";

		$onMouseOver = " onmouseover=\"style.backgroundColor='#FFFF99'\"
      				onmouseout=\"style.backgroundColor='white'\" ";
		
		if ($this->pageIsMobile) $onMouseOver = "";  // Causes problems for some mobile devices.
		
		$handClass = "hand";
		$extraStyle = "";

		if ($course->boolUnselectable == true)
		{
			// Cannot be selected, so remove that ability!
			$handClass = "";
			$onMouseOver = "";
			$jsCode = "";
			$op = "";
			$extraStyle = "style='font-style: italic; color:gray;'";
		}


		$pC .= "
   		<table border='0' cellpadding='0' width='100%' cellspacing='0' align='left'>
     	<tr height='20' class='$handClass $displayStatus'
      		$onMouseOver title='$titleText'>
      		<td width='$w1_1' align='left'>$op$hid</td>
      		<td width='$w1_2' align='left' onClick='$jsCode'>$iconLink</td>
      		<td width='$w1_3' align='left' onClick='$jsCode'>&nbsp;</td>
      		<td align='left' width='$w2' class='tenpt underline' 
      				onClick='$jsCode' $extraStyle>
       				$subjectID</td>
       		<td class='tenpt underline' $extraStyle width='$w3' align='left' 
       			onClick='$jsCode'>
        			$courseNum</td>
        	";
		if ($repeats > 0)
		{
			$pC .= "
				<td class='tenpt underline' style='color: gray;' 
					onClick='$jsCode' colspan='3'>
				<i>May take up to <span style='color: blue;'>" . ($repeats + 1) . "</span> times.</i>
				</td>
			";
		} else {

			$pC .= "
	       <td class='tenpt underline' width='$w4' onClick='$jsCode' $extraStyle>$hours&nbsp;$varHourIcon</td>
       	   <td class='tenpt underline' width='$w5' onClick='$jsCode'>$grade&nbsp;</td>
       	   <td class='tenpt underline' width='$w6' onClick='$jsCode'>$pts&nbsp;</td>
       	   ";
		}

		$pC .= "
     	</tr>
     	</table>";		


		return $pC;
	}



	/**
	 * This is used to display the substitution popup to a user, to let them
	 * actually make a substitution.
	 *
	 * @param int $courseID
	 * @param int $groupID
	 * @param int $semesterNum
	 * @param int $hoursAvail
	 * 
	 * @return string
	 */
	function displayPopupSubstitute($courseID = 0, $groupID, $semesterNum, $hoursAvail = "")
	{
		// This lets the user make a substitution for a course.
		$pC = "";

		$course = new Course($courseID);
		$boolSubAdd = false;

		$cTitle = "Substitute for $course->subjectID $course->courseNum";
		if ($courseID == 0)
		{
			$cTitle = "Substitute an additional course";
			$boolSubAdd = true;
		}
		$pC .= $this->drawCurvedTitle($cTitle);

		$extra = ".<input type='checkbox' id='cbAddition' value='true' style='display:none;'>";
		if ($groupID > 0)
		{
			$newGroup = new Group($groupID);
			$checked = "";
			if ($boolSubAdd == true){$checked = "checked disabled";}
			$extra = " in the group <i>$newGroup->title</i>.
			Addition only: <input type='checkbox' id='cbAddition' value='true' $checked> 
			   <a href='javascript: alertSubAddition();'>?</a>";
		}

		$cHours = $course->maxHours*1;
		$cGhostHour = "";
		if ($course->boolGhostHour == TRUE) {
		  $cGhostHour = "ghost<a href='javascript: alertSubGhost();'>?</a>";
		}

		if (($hoursAvail*1 > 0 && $hoursAvail < $cHours) || ($cHours < 1))
		{
			//adminDebug($hoursAvail);
			// Use the remaining hours if we have fewer hours left in
			// the group than the course we are subbing for.
			$cHours = $hoursAvail;
		}

		if ($hoursAvail == "" || $hoursAvail*1 < 1)
		{
			$hoursAvail = $cHours;
		}

		//adminDebug("c hours $cHours, hoursAvail $hoursAvail");

		$pC .= "<div class='tenpt'>
					Please select a course to substitute
				for <b>$course->subjectID $course->courseNum ($cHours $cGhostHour hrs)</b>$extra
				</div>
				
				<div class='tenpt' 
					style='height: 175px; overflow: auto; border:1px inset black; padding: 5px;'>
					<table border='0' cellpadding='0' cellspacing='0' width='100%'>
					
					";
    
		$this->student->listCoursesTaken->sortAlphabeticalOrder(false, true);
    
		for ($t = 0; $t <= 1; $t++)
		{
			if ($t == 0) {$theTitle = "{$GLOBALS["fpSystemSettings"]["schoolInitials"]} Credits"; $boolTransferTest = true;}
			if ($t == 1) {$theTitle = "Transfer Credits"; $boolTransferTest = false;}

			$pC .= "<tr><td colspan='3' valign='top' class='tenpt' style='padding-bottom: 10px;'>
				$theTitle
				</td>
				<td class='tenpt' valign='top' >Hrs</td>
				<td class='tenpt' valign='top' >Grd</td>
				<td class='tenpt' valign='top' >Term</td>
				</tr>";
			
			$isEmpty = true;
			$this->student->listCoursesTaken->resetCounter();
			while($this->student->listCoursesTaken->hasMore())
			{
				$c = $this->student->listCoursesTaken->getNext();
				
				if ($c->boolTransfer == $boolTransferTest)
				{
					continue;
				}

				
				if (!$c->meetsMinGradeRequirementOf(null, "D"))
				{// Make sure the grade is OK.
					continue;
				}

				$tFlag = 0;
				if ($c->boolTransfer == true)
				{
					$tFlag = 1;
				}
				$isEmpty = false;

				$subjectID = $c->subjectID;
				$courseNum = $c->courseNum;
				$tcourseID = $c->courseID;

				if ($boolTransferTest == false)
				{
					// Meaning, we are looking at transfers now.
					// Does the transfer course have an eqv set up?  If so,
					// we want *that* course to appear.
					if (is_object($c->courseTransfer))
					{
						$subjectID = $c->courseTransfer->subjectID;
						$courseNum = $c->courseTransfer->courseNum;
						$tcourseID = $c->courseTransfer->courseID;
						$tFlag = 1;
					}
				}
				//adminDebug($courseID);
				$mHours = $c->hoursAwarded*1;

				if ($c->maxHours*1 < $mHours)
				{
					$mHours = $c->maxHours*1;

				}

				if (($hoursAvail*1 > 0 && $hoursAvail < $mHours) || ($mHours < 1))
				{
					$mHours = $hoursAvail;
				}

				// is maxHours more than the original course's hours?
				if ($mHours > $cHours)
				{
					$mHours = $cHours;
				}

				if ($mHours > $c->hoursAwarded)
				{
					$mHours = $c->hoursAwarded;
				}

				//adminDebug("$mHours , $hoursAvail");
				//adminDebug("looking at " . $c->toString());
				if ($c->boolSubstitution != true && $c->boolOutdatedSub != true)
				{
				  $h = $c->hoursAwarded;
				  if ($c->boolGhostHour == TRUE) {
				    $h .= "(ghost<a href='javascript: alertSubGhost();'>?</a>)";
				  }
					//adminDebug("here");
					$pC .= "<tr>
						<td valign='top' class='tenpt' width='15%'>
							<input type='radio' name='subCourse' id='subCourse' value='$tcourseID'
							 onClick='popupUpdateSubData(\"$mHours\",\"$c->termID\",\"$tFlag\",\"$hoursAvail\",\"$c->hoursAwarded\");'>
						</td>
						<td valign='top' class='tenpt underline' width='13%'>
							$subjectID
						</td>
						<td valign='top' class='tenpt underline' width='15%'>
							$courseNum
						</td>
						

						<td valign='top' class='tenpt underline' width='10%'>
							$h
						</td>
						<td valign='top' class='tenpt underline' width='10%'>
							$c->grade
						</td>
						<td valign='top' class='tenpt underline'>
							" . $c->getTermDescription(true) . "
						</td>

						
					</tr>
					";
				} else {



					if (is_object($c->courseSubstitution) && $c->courseSubstitution->subjectID == "")
					{ // Load subjectID and courseNum of the original
						// requirement.
						$c->courseSubstitution->loadDescriptiveData();
					}

					$extra = "";
					if ($c->assignedToGroupID > 0)
					{
						$newGroup = new Group($c->assignedToGroupID);
						$extra = " in $newGroup->title";
					}
					if ($c->boolOutdatedSub == true)
					{
						$helpLink = "<a href='javascript: popupHelpWindow(\"help.php?i=9\");' class='nounderline'>(?)</a>";
						$extra .= " <span style='color:red;'>[Outdated$helpLink]</span>";
					}

					// It has already been substituted!
					$pC .= "<tr style='background-color: beige;'>
						<td valign='top' class='tenpt' width='15%'>
						 Sub:
						</td>
						<td valign='top' class='tenpt' colspan='5'>
							$subjectID 
						
							$courseNum ($c->substitutionHours)
							 -> " . $c->courseSubstitution->subjectID . "
							 " . $c->courseSubstitution->courseNum . "$extra
						</td>

						
					</tr>
					";

				}

			}

			if ($isEmpty == true)
			{
				// Meaning, there were no credits (may be the case with
				// transfer credits)
				$pC .= "<tr><td colspan='8' class='tenpt'>
							- No substitutable credits available.
						</td></tr>";
			}

			$pC .= "<tr><td colspan='4'>&nbsp;</td></tr>";
		}


		$pC .= "</table></div>
		<div class='tenpt' style='margin-top: 5px;'>
			Select number of hours to use:
			<select name='subHours' id='subHours'>
				<option value=''>None Selected</option>
			</select>
			
		</div>
		<input type='hidden' name='subTransferFlag' id='subTransferFlag' value=''>
		<input type='hidden' name='subTermID' id='subTermID' value=''>
		<input type='button' value='Save Substitution' onClick='popupSaveSubstitution(\"$courseID\",\"$groupID\",\"$semesterNum\");'>
		
		<div class='tenpt' style='padding-top: 5px;'><b>Optional</b> - Enter remarks: 
		<input type='text' name='subRemarks' id='subRemarks' value='' size='30' maxlength='254'>
		
		</div>
		";


		return $pC;
	}



	/**
	 * This function displays the popup which lets a user select a course to be
	 * advised into a group.
	 *
	 * @param Group $placeGroup
	 * @param int $groupHoursRemaining
	 * @return string
	 */
	function displayPopupGroupSelect(Group $placeGroup, $groupHoursRemaining = 0)
	{
		$pC = "";

		$advisingTermID = $GLOBALS["advisingTermID"];

		if ($placeGroup->groupID != -88)
		{
			// This is NOT the Add a Course group.

			if (!$group = $this->degreePlan->findGroup($placeGroup->groupID))
			{
				adminDebug("Group not found.");
				return;
			}
		} else {
			// This is the Add a Course group.
			$group = $placeGroup;
		}

		$groupID = $group->groupID;

		// So now we have a group object, $group, which is most likely
		// missing courses.  This is because when we loaded & cached it
		// earlier, we did not load any course which wasn't a "significant course,"
		// meaning, the student didn't have credit for it or the like.
		// So what we need to do now is reload the group, being careful
		// to preserve the existing courses / sub groups in the group.
		//print_pre($group->toString());
		$group->reloadMissingCourses();
		//print_pre($group->toString());

		if ($groupHoursRemaining == 0)
		{
			// Attempt to figure out the remaining hours (NOT WORKING IN ALL CASES!)
			// This specifically messes up when trying to get fulfilled hours in groups
			// with branches.
			$groupFulfilledHours = $group->getFulfilledHours(true, true, false, $placeGroup->assignedToSemesterNum);
			$groupHoursRemaining = $placeGroup->hoursRequired - $groupFulfilledHours;
			//adminDebug("count hours for semester $placeGroup->assignedToSemesterNum");

			//adminDebug("req:$placeGroup->hoursRequired fulfilled:$groupFulfilledHours");

		}



		//adminDebug("placegroup hrs rem: " . $placeGroup->getHoursRemaining());
		$displaySemesterNum = $placeGroup->assignedToSemesterNum;
		$pC .= "<!--MSG--><!--MSG2--><!--BOXTOP-->";

		$boolDisplaySubmit = true;
		$boolDisplayBackToSubjectSelect = false;
		$boolSubjectSelect = false;
		$boolUnselectableCourses = false;
		$finalCourseList = new CourseList();
		//adminDebug("here");
		//print_pre($group->toString());

		$group->listCourses->resetCounter();
		if (!($group->listCourses->isEmpty))
		{

			$group->listCourses->assignSemesterNum($displaySemesterNum);

			$newCourseList = $group->listCourses;
			// Is this list so long that we first need to ask the user to
			// select a subject?
			if ($newCourseList->getSize() > 30)
			{

				// First, we are only going to do this if there are more
				// than 30 courses, AND more than 2 subjects in the list.
				$newCourseList->sortAlphabeticalOrder();
				$subjectArray = $newCourseList->getCourseSubjects();
				//print_pre($newCourseList->toString());
				//var_dump($subjectArray);
				if (count($subjectArray) > 2)
				{
					// First, check to see if the user has already
					// selected a subject.
					$selectedSubject = trim(addslashes($_GET["selectedSubject"]));
					if ($selectedSubject == "")
					{
						// Prompt them to select a subject first.
						$pC .= $this->drawPopupGroupSubjectSelect($subjectArray, $group->groupID, $displaySemesterNum, $groupHoursRemaining);
						$newCourseList = new CourseList(); // empty it
						$boolDisplaySubmit = false;
						$boolSubjectSelect = true;
					} else {
						// Reduce the newCourseList to only contain the
						// subjects selected.
						$newCourseList->excludeAllSubjectsExcept($selectedSubject);
						$boolDisplayBackToSubjectSelect = true;
					}
				}
			}

			$newCourseList->resetCounter();			
			$newCourseList->sortAlphabeticalOrder();

			

			$finalCourseList->addList($newCourseList);
		}

		if (!($group->listGroups->isEmpty))
		{
			// Basically, this means that this group
			// has multiple subgroups.  We need to find out
			// which branches the student may select from
			// (based on what they have already taken, or been
			// advised to take), and display it (excluding duplicates).
			//print_pre($group->toString());
			// The first thing we need to do, is find the subgroup
			// or subgroups with the most # of matches.
			$newCourseList = new CourseList();
			$allZero= true;

			// Okay, this is a little squirely.  What I need to do
			// first is get a course list of all the courses which
			// are currently either fulfilling or advised for all branches
			// of this group.
			$faCourseList = new CourseList();
			$group->listGroups->resetCounter();
			while($group->listGroups->hasMore())
			{
				$branch = $group->listGroups->getNext();
				$faCourseList->addList($branch->listCourses->getFulfilledOrAdvised(true));
			}
			$faCourseList->removeDuplicates();
			//print_pre($faCourseList->toString());
			// Alright, now we create a fake student and set their
			// listCoursesTaken, so that we can use this student
			// to recalculate the countOfMatches in just a moment.
			$newStudent = new Student();
			$newStudent->loadStudent();
			$newStudent->listCoursesTaken = $faCourseList;
			$newStudent->loadSignificantCoursesFromListCoursesTaken();

			// Okay, now we need to go through and re-calculate our
			// countOfMatches for each branch.  This is because we
			// have cached this value, and after some advisings, it may
			// not be true any longer.

			$highestMatchCount = 0;
			$group->listGroups->resetCounter();
			while($group->listGroups->hasMore())
			{
				$branch = $group->listGroups->getNext();
				// recalculate countOfMatches here.
				$cloneBranch = new Group();
				$cloneBranch->listCourses = $branch->listCourses->getClone(true);
				$matchesCount = $this->flightPath->getCountOfMatches($cloneBranch, $newStudent, null);
				//print_pre($branch->toString());
				$branch->countOfMatches = $matchesCount;
				//adminDebug($matchesCount);
				if ($matchesCount >= $highestMatchCount)
				{ // Has more than one match on this branch.
					//			adminDebug($branch->groupID . " " . $branch->countOfMatches);
					$highestMatchCount = $matchesCount;
				}
			}
			//adminDebug("going with $highestMatchCount");
			// If highestMatchCount > 0, then get all the branches
			// which have that same match count.
			if ($highestMatchCount > 0)
			{
				$group->listGroups->resetCounter();
				while($group->listGroups->hasMore())
				{
					$branch = $group->listGroups->getNext();
					//print_pre($branch->toString());
					if ($branch->countOfMatches == $highestMatchCount)
					{ // This branch has the right number of matches.  Add it.
						//adminDebug($branch->groupID . " " . $branch->countOfMatches);

						$newCourseList->addList($branch->listCourses);
						$allZero = false;
					}

				}

			}
			
			if ($allZero == true)
			{
				// Meaning, all of the branches had 0 matches,
				// so we should add all the branches to the
				// newCourseList.

				$group->listGroups->resetCounter();
				while($group->listGroups->hasMore())
				{
					$branch = $group->listGroups->getNext();
					$newCourseList->addList($branch->listCourses);
				}
			} else {
				// Meaning that at at least one branch is favored.
				// This also means that a user's course
				// selections have been restricted as a result.
				// Replace the MSG at the top saying so.
				$msg = "<div class='tenpt'>Your selection of courses has been
							restricted based on previous course selections.</div>";
				$pC = str_replace("<!--MSG-->", $msg, $pC);
			}

			// Okay, in the newCourseList object, we should
			// now have a list of all the courses the student is
			// allowed to take, but there are probably duplicates.
			//print_pre($newCourseList->toString());


			$newCourseList->removeDuplicates();

			$newCourseList->assignGroupID($group->groupID);
			$newCourseList->assignSemesterNum($displaySemesterNum);

			$finalCourseList->addList($newCourseList);
			
		}


		//print_pre($finalCourseList->toString());
		// Remove courses which have been marked as "exclude" in the database.
		$finalCourseList->removeExcluded();

		//print_pre($finalCourseList->toString());

		// Here's a fun one:  We need to remove courses for which the student
		// already has credit that *don't* have repeating hours.
		// For example, if a student took MATH 113, and it fills in to
		// Core Math, then we should not see it as a choice for advising
		// in Free Electives (or any other group except Add a Course).
		// We also should not see it in other instances of Core Math.
		if ($group->groupID != -88 && $this->boolBlank != TRUE)
		{
			// Only do this if NOT in Add a Course group...
			// also, don't do it if we're looking at a "blank" degree.
			$finalCourseList->removePreviouslyFulfilled($this->student->listCoursesTaken, $group->groupID, true, $this->student->listSubstitutions);
			//print_pre($finalCourseList->toString());
		}
		//print_pre($this->student->listSubstitutions->toString());
		//print_pre($finalCourseList->toString());

		$finalCourseList->sortAlphabeticalOrder();
		if (!$finalCourseList->hasAnyCourseSelected())
		{
			//adminDebug("in here");
			if ($c = $finalCourseList->findFirstSelectable())
			{
				$c->boolSelected = true;
				//adminDebug($c->toString());
			}
		}

		// flag any courses with more hours than are available for this group.
		if ($finalCourseList->assignUnselectableCoursesWithHoursGreaterThan($groupHoursRemaining))
		{

			$boolUnselectableCourses = true;
		}


		$pC .= $this->displayPopupGroupSelectCourseList($finalCourseList, $groupHoursRemaining);

		// If there were no courses in the finalCourseList, display a message.
		if (count($finalCourseList->arrayList) < 1 && !$boolSubjectSelect)
		{
			$pC .= "<tr>
					<td colspan='8'>
						<div class='tenpt'>
						<b>Please Note:</b> 
						FlightPath could not find any eligible
						courses to display for this list.  Ask your advisor
						if you have completed courses, or may enroll in
						courses, which can be
						displayed here.";

			if ($_SESSION["fpCanAdvise"] == true)
			{
				// This is an advisor, so put in a little more
				// information.
				$pC .= "
									<div class='tenpt' style='padding-top: 5px;'><b>Special note to advisors:</b> You may still
											advise a student to take a course, even if it is unselectable
											in this list.  Use the \"add an additional course\" link at
											the bottom of the page.</div>
										";
			}
			$pC .= "						</div>
					</td>
					</tr>";
			$boolNoCourses = true;
		}

		$pC .= $this->drawSemesterBoxBottom();

		$s = "s";
		//print_pre($placeGroup->toString());

		if ($groupHoursRemaining == 1){$s = "";}
		if ($boolUnselectableCourses == true)
		{
			$unselectableNotice = " <div class='tenpt'><i>(Courses worth more than $groupHoursRemaining hour$s
								may not be selected.)</i></div>";
			if ($_SESSION["fpCanAdvise"] == true)
			{
				// This is an advisor, so put in a little more
				// information.
				$unselectableNotice .= "
									<div class='tenpt' style='padding-top: 5px;'><b>Special note to advisors:</b> You may still
											advise a student to take a course, even if it is unselectable
											in this list.  Use the \"add an additional course\" link at
											the bottom of the page.</div>
										";
			}
		}

		if ($groupHoursRemaining < 100 && $boolNoCourses != true)
		{ // Don't show for huge groups (like add-a-course)
			$pC .= "<div class='elevenpt' style='margin-top:5px;'>
					You may select <b>$groupHoursRemaining</b>
						hour$s from this list.$unselectableNotice</div>";
		}
		//adminDebug($placeGroup->assignedToSemesterNum);
		if ($boolDisplaySubmit == true && !$this->boolBlank && $boolNoCourses != true)
		{
			if ($_SESSION["fpCanAdvise"] == true)
			{
				$pC .= "<input type='hidden' name='varHours' id='varHours' value=''>
					<div style='margin-top: 20px;'>
					
					
				" . $this->drawButton("Select Course", "popupAssignSelectedCourseToGroup(\"$placeGroup->assignedToSemesterNum\", \"$group->groupID\",\"$advisingTermID\",\"-1\");", true, "style='font-size: 10pt;'") . "
				<!--
					<input type='button' value='Select Course'
				onClick='popupAssignSelectedCourseToGroup(\"$placeGroup->assignedToSemesterNum\", \"$group->groupID\",\"$advisingTermID\");'>
				-->
					</div>
				";
			}

		}

		// Substitutors get extra information:
		if ($_SESSION["fpCanSubstitute"] == true && $group->groupID != -88)
		{
			$pC .= "<div class='tenpt' style='margin-top: 20px;'>
					<b>Special administrative information:</b>
					
				<span id='viewinfolink'
				onClick='document.getElementById(\"admin_info\").style.display=\"\"; this.style.display=\"none\"; '
				class='hand' style='color: blue;'
				> - Click to show -</span>					
					
					<div style='padding-left: 20px; display:none;' id='admin_info'>
					Information about this group:<br>
					&nbsp; Group ID: $group->groupID<br>
					&nbsp; Title: $group->title<br>";
			if ($_SESSION["fpUserType"] == "full_admin")
			{ // only show if we are full admin.
				$pC .= "&nbsp; <i>Internal name: $group->groupName</i><br>";
			}
			$pC .= "&nbsp; Catalog year: $group->catalogYear
					</div>
					
					</div>";						
		}


		if ($boolDisplayBackToSubjectSelect == true)
		{
			$csid = $GLOBALS["currentStudentID"];
			$blankDegreeID = "";
			if ($this->boolBlank)
			{
				$blankDegreeID = $this->degreePlan->degreeID;
			}
			$backLink = "<span class='tenpt'>
						<a href='$this->scriptFilename?windowMode=popup&performAction=displayGroupSelect&groupID=$group->groupID&semesterNum=$displaySemesterNum&groupHoursRemaining=$groupHoursRemaining&currentStudentID=$csid&blankDegreeID=$blankDegreeID' 
						class='nounderline'>Click here to return to subject selection.</a></span>";
			$pC = str_replace("<!--MSG2-->",$backLink,$pC);
		}

		$boxTop = $this->drawSemesterBoxTop("$group->title", !$boolDisplaySubmit);
		$pC = str_replace("<!--BOXTOP-->",$boxTop,$pC);

		return $pC;
	}


	/**
	 * When the groupSelect has too many courses, they are broken down into
	 * subjects, and the user first selects a subject.  This function will
	 * draw out that select list.
	 *
	 * @param array $subjectArray
	 * @param int $groupID
	 * @param int $semesterNum
	 * @param int $groupHoursRemaining
	 * @return string
	 */
	function drawPopupGroupSubjectSelect($subjectArray, $groupID, $semesterNum, $groupHoursRemaining = 0)
	{
		$csid = $GLOBALS["currentStudentID"];
		$blankDegreeID = "";
		if ($this->boolBlank)
		{
			$blankDegreeID = $this->degreePlan->degreeID;
		}
		$pC .= "<tr><td colspan='8' class='tenpt'>";
		$pC .= "<form action='$this->scriptFilename' method='GET' style='margin:0px; padding:0px;' id='theform'>
					<input type='hidden' name='windowMode' value='popup'>
					<input type='hidden' name='performAction' value='displayGroupSelect'>
					<input type='hidden' name='groupID' value='$groupID'>
					<input type='hidden' name='semesterNum' value='$semesterNum'>
					<input type='hidden' name='groupHoursRemaining' value='$groupHoursRemaining'>
					<input type='hidden' name='currentStudentID' value='$csid'>
					<input type='hidden' name='blankDegreeID' value='$blankDegreeID'>
		
					Please begin by selecting a subject from the list below.
					<br><br>
					<select name='selectedSubject'>
					<option value=''>Please select a subject...</option>
					<option value=''>----------------------------------------</option>
					";
		$newArray = array();
		foreach($subjectArray as $key => $subjectID)
		{
			//adminDebug($subjectID);
			if ($title = $this->flightPath->getSubjectTitle($subjectID)) {
				$newArray[] = "$title ~~ $subjectID";
			} else {
			  $newArray[] = "$subjectID ~~ $subjectID";
			}
			
		}

		sort($newArray);

		foreach ($newArray as $key => $value)
		{
			$temp = split(" ~~ ",$value);
			$title = trim($temp[0]);
			$subjectID = trim($temp[1]);
			$pC .= "<option value='$subjectID'>$title</option>";
		}

		$pC .= "</select>
				<div style='margin: 20px;' align='left'>
				" . $this->drawButton("Next ->","document.getElementById(\"theform\").submit();") . "
				</div>
					<!-- <input type='submit' value='submit'> -->
					
			  			</form>
			  ";
		$pC .= "</td></tr>";

		return $pC;
	}


	/**
	 * Accepts a CourseList object and draws it out to the screen. Meant to 
	 * be called by displayPopupGroupSelect();
	 *
	 * @param CourseList $courseList
	 * @param int $groupHoursRemaining
	 * @return string
	 */
	function displayPopupGroupSelectCourseList(CourseList $courseList = null, $groupHoursRemaining = 0)
	{
		// Accepts a CourseList object and draws it out to the screen.  Meant to
		// be called by displayPopupGroupSelect().
		$pC = "";

		if ($courseList == null)
		{
			//adminDebug("here");
			return;
		}

		$oldCourse = null;

		$courseList->resetCounter();
		while($courseList->hasMore())
		{
			$course = $courseList->getNext();
			if ($course->equals($oldCourse))
			{ // don't display the same course twice in a row.
				continue;
			}

			//adminDebug($course->boolSelected);
			$pC .= "<tr><td colspan='8'>";

			//if (!is_object($course->courseFulfilledBy) && !$course->boolAdvisedToTake)
			if ($course->courseListFulfilledBy->isEmpty && !$course->boolAdvisedToTake)
			{ // So, only display if it has not been fulfilled by anything.
				$pC .= $this->drawPopupGroupSelectCourseRow($course, $groupHoursRemaining);
				$oldCourse = $course;
			} else {
				// Do not display courses which the student has fulfilled,
				// or courses for which the student has already been advised.
				//$pC .= $this->drawPopupGroupSelectCourseRow($course->courseFulfilledBy);
			}
			$pC .= "</td></tr>";
		}


		return $pC;
	}





  /**
   * Depricated.  Same as drawBoxBottom()
   * 
   * @todo  Should this be removed?
   *
   * @return unknown
   */
	function displayEndSemesterTable()
	{
		// Return the HTML that ends & closes up the semester
		// table.
		$pC = "";
		$pC .= "</table>";
		return $pC;
	}


	/**
	 * This function gets all of the various javascript functions and
	 * places them all into a string.
	 *
	 * @return string
	 */
	function getJavascriptCode()
	{
		$rtn = "";

		$rtn .= "<script type=\"text/javascript\">
		var csid = \"{$GLOBALS["currentStudentID"]}\";
		
		";

		$rtn .= $this->getJS_changeTab();

		$rtn .= $this->getJS_launchPrintView();
		$rtn .= $this->getJS_hideShowCharts();
		$rtn .= $this->getJS_changeView();



		$rtn .= $this->getJS_submitSaveActive();
		$rtn .= $this->getJS_popupWindow();
		$rtn .= $this->getJS_popupWindow2();
		$rtn .= $this->getJS_popupPrintWindow();
		$rtn .= $this->getJS_popupHelpWindow();
		$rtn .= $this->getJS_popupChangeTerm();
		$rtn .= $this->getJS_changeTerm();

		//$rtn .= $this->getJS_popupSubstitutionRemarks();

		$rtn .= $this->getJS_popupChangeTrack();
		$rtn .= $this->getJS_popupChangeWhatIfTrack();
		$rtn .= $this->getJS_changeTrack();

		$rtn .= $this->getJS_alertHelps();
		$rtn .= $this->getJS_popupAssignSelectedCourseToGroup();
		$rtn .= $this->getJS_assignSelectedCourseToGroup();
		$rtn .= $this->getJS_updateSelectedCourse();
		$rtn .= $this->getJS_toggleSelection();
		$rtn .= $this->getJS_toggleSelectionAndSave();
		$rtn .= $this->getJS_toggleDisabledChangeTerm();
		$rtn .= $this->getJS_toggleDisabledCompleted();
		$rtn .= $this->getJS_describeCourse();
		$rtn .= $this->getJS_submitForm();
		$rtn .= $this->getJS_selectCourseFromGroup();
		$rtn .= $this->getJS_popupSetVarHours();
		$rtn .= $this->getJS_popupUpdateSelectedCourse();
		$rtn .= $this->getJS_popupSubstituteSelected();
		$rtn .= $this->getJS_popupBackToGroupSelect();
		$rtn .= $this->getJS_popupRemoveSubstitution();
		$rtn .= $this->getJS_popupUpdateSubData();
		$rtn .= $this->getJS_popupSaveSubstitution();
		$rtn .= $this->getJS_removeSubstitution();
		$rtn .= $this->getJS_setVar();
		$rtn .= $this->getJS_saveSubstitution();
		$rtn .= $this->getJS_popupDescribeSelected();
		$rtn .= $this->getJS_popupUnassignFromGroup();
		$rtn .= $this->getJS_unassignFromGroup();
		$rtn .= $this->getJS_popupUnassignTransferEqv();
		$rtn .= $this->getJS_unassignTransferEqv();
		$rtn .= $this->getJS_restoreTransferEqv();
		$rtn .= $this->getJS_popupRestoreTransferEqv();

		$rtn .= $this->getJS_restoreUnassignFromGroup();
		$rtn .= $this->getJS_popupRestoreUnassignFromGroup();



		$rtn .= "</script>";

		return $rtn;
	}

	function getJS_changeTab()
	{
		$rtn = '
	
	function changeTab(formAction, performAction, advisingWhatIf, warnChange)
	{
	
		if (warnChange == "warn")
		{
			var x = confirm("Are you sure you wish to change tabs?  Any unsaved work will be lost.\n\nClick OK to change tabs, click Cancel to stay on this page.");
			if (!x)
			{
				return;
			}
		}
	
		document.getElementById("performAction").value = performAction;
		if (advisingWhatIf != "")
		{
			document.getElementById("advisingWhatIf").value = advisingWhatIf;
		}
		
		document.getElementById("currentStudentID").value = csid;
		
		document.getElementById("mainform").action = formAction;
		submitForm(false);
	}
	
		';

		return $rtn;
	}


	function getJS_launchPrintView()
	{
		$rtn = '
	
	function launchPrintView()
	{
		var mf = document.getElementById("mainform");	
		mf.target = "_blank";
		document.getElementById("printView").value = "yes";
		
		submitForm(false);
		
		// Reset to default...
		document.getElementById("printView").value = "";
		mf.target = "";
	}
	
		';

		return $rtn;
	}


	function getJS_submitSaveActive()
	{
		$rtn = '
		
		function submitSaveActive()
		{
			document.getElementById("performAction").value = "saveActive";
			submitForm();
		}
		
			';
		return $rtn;
	}

	function getJS_popupChangeTerm()
	{
		$rtn = '
		
		function popupChangeTerm(termID)
		{
			var x = confirm("Are you sure you wish to change advising terms?");
			if (x)
			{
				opener.changeTerm(termID);
				window.close();
			}
		}
				';
		return $rtn;
	}

	function getJS_changeView()
	{
		$rtn = '
		
		function changeView(view)
		{
			document.getElementById("advisingView").value = view;
			
			// rebuild the cache.
			//document.getElementById("loadFromCache").value="no";
						
			
			submitForm(true);
		}
		
		';
		return $rtn;

	}


	function getJS_changeTerm()
	{
		$rtn = '
		
		function changeTerm(termID)
		{
			document.getElementById("advisingTermID").value = termID;
			document.getElementById("logAddition").value = "changeTerm_" + termID;
			
			// rebuild the cache.
			document.getElementById("loadFromCache").value="no";
			
			
			submitForm(true);
		}
				';
		return $rtn;
	}

	function getJS_popupChangeTrack()
	{
		$rtn = '
		
		function popupChangeTrack(trackCode)
		{
			var x = confirm("Are you sure you wish to change degree options?");
			if (x)
			{
				opener.changeTrack(trackCode);
				window.close();
			}
		}
				';
		return $rtn;
	}


	function getJS_popupChangeWhatIfTrack()
	{
		$rtn = '
		
		function popupChangeWhatIfTrack(trackCode)
		{
			var x = confirm("Are you sure you wish to change degree options?");
			if (x)
			{
				opener.document.getElementById("whatIfTrackCode").value = trackCode;
				opener.document.getElementById("loadFromCache").value = "no";
				opener.document.getElementById("logAddition").value = "changeTrack_" + trackCode;
				
				opener.submitForm(true);
				window.close();
			}
		}
				';
		return $rtn;
	}


	function getJS_changeTrack()
	{
		$rtn = '
		
		function changeTrack(trackCode)
		{
			document.getElementById("advisingTrackCode").value = trackCode;
			document.getElementById("advisingUpdateStudentSettingsFlag").value = "true";
			document.getElementById("logAddition").value = "changeTrack_" + trackCode;
//alert(document.getElementById("logAddition").value);
			// rebuild the cache.
			document.getElementById("loadFromCache").value="no";

			submitForm(true);
		}
				';
		return $rtn;
	}


	function getJS_hideShowCharts()
	{
		$rtn = '
		
		function hideShowCharts(status)
		{
			document.getElementById("hideCharts").value = status;		
			document.getElementById("fpUpdateUserSettingsFlag").value = "true";


			submitForm(true);
		}
				';
		return $rtn;
	}



	function getJS_alertHelps()
	{
		// Simple Javascript alerts meant for quick help or tips.
		$rtn = '
		
		function alertSplitSub()
		{
			var x = "";
			
			x = x + "You college advisor has chosen to split this course into ";
			x = x + "several pieces so that it can be subsituted more easily. ";
			x = x + "Your original course, as it appears on your transcript, has not ";
			x = x + "been altered.\n\n";
			x = x + "If you have any questions about why this course was split, ";
			x = x + "please ask your advisor.";
			
			alert(x);
		}

		function alertSubAddition()
		{
			var x = "";
			x = x + "By checking the \"Addition only\" box, you are ";
			x = x + "indicating you wish to simply add a course ";
			x = x + "to the elective group, and NOT perform a course-for-course substitution. ";
			x = x + "\n\nIf you are unsure which kind of substitution to make, ";
			x = x + "then check this box.";
					
			alert(x);
		}		
		
		function alertSubGhost() {
			var x = "";
			x = x + "This course has a \"ghost hour\" associated with it. ";
			x = x + "This means that the student actually earned zero hours for this course, or that it is actually worth 0 hours, ";
			x = x + "but in order for FlightPath to use the course, it must internally be recorded ";
			x = x + "as being worth 1 hour.  This is a limitation of FlightPath, and should not ";
			x = x + "affect the student\'s GPA or other hour calculations.";
					
			alert(x);
		
		}
		
		
		';

		return $rtn;
	}

	function getJS_popupUpdateSubData()
	{
		$rtn = '
		
		function popupUpdateSubData(maxHours, termID, transferFlag, groupHoursAvail, subCourseHours)
		{
			document.getElementById("subTermID").value = termID;
			document.getElementById("subTransferFlag").value = transferFlag;
			// if the addition checkbox is checked, use the groupHoursAvail as
			// the max.
			if (document.getElementById("cbAddition").checked == true)
			{
				maxHours = groupHoursAvail;
				if (maxHours > subCourseHours)
				{
					maxHours = subCourseHours;
				}
			}
			
			//alert(maxHours);
			
			var sel = document.getElementById("subHours");
			
			// Replace this pulldowns elements with a range of values from
			// maxHours to 1.
			
			// First, remove all existing options.
			sel.options.length = 0;

			sel.options[0] = new Option(" Max (default) ", maxHours, true, true);
			var c = 1;
			// Now, add in the others.
			for (var t = maxHours; t > 0; t--)
			{
				sel.options[c] = new Option(" " + t + " ", t, false, false);
				c++;
			}
			
			
		}
		
			';
		return $rtn;
	}


	function getJS_popupUnassignTransferEqv()
	{
		$rtn = '
		
		function popupUnassignTransferEqv(courseID)
		{
			var x = confirm("Are you sure you wish to remove this transfer course equivalency?\n\nThis action will only affect the current student.  It will not impact any other student\'s records.");
			if (x)
			{
				opener.unassignTransferEqv(courseID);
				window.close();
			}
		}
		
			';
		return $rtn;
	}


	function getJS_unassignTransferEqv()
	{
		$rtn = '
		
		function unassignTransferEqv(courseID)
		{

			var hiddenElements = document.getElementById("hiddenElements");
			var e = document.createElement("input");
			e.setAttribute("name","unassign_transfer_eqv");
			e.setAttribute("type","hidden");
			e.setAttribute("value","" + courseID + "_" + "");
			hiddenElements.appendChild(e);			
			
			// rebuild the cache.
			document.getElementById("loadFromCache").value="no";
			
			submitForm(true);
			
		}
		
			';
		return $rtn;
	}




	function getJS_popupUnassignFromGroup()
	{
		$rtn = '
		
		function popupUnassignFromGroup(courseID, termID, transferFlag, groupID)
		{
			var x = confirm("Are you sure you wish to remove this course?");
			if (x)
			{
				opener.unassignFromGroup(courseID, termID, transferFlag, groupID);
				window.close();
			}
		}
		
			';
		return $rtn;
	}


	function getJS_unassignFromGroup()
	{
		$rtn = '
		
		function unassignFromGroup(courseID, termID, transferFlag, groupID)
		{

			var hiddenElements = document.getElementById("hiddenElements");
			var e = document.createElement("input");
			e.setAttribute("name","unassign_group");
			e.setAttribute("type","hidden");
			e.setAttribute("value","" + courseID + "_" + termID + "_" + transferFlag + "_" + groupID + "");
			hiddenElements.appendChild(e);			
			
			// rebuild the cache.
			document.getElementById("loadFromCache").value="no";
			
			
			submitForm(true);
			
		}
		
			';
		return $rtn;
	}

	/*
	function getJS_popupSubstitutionRemarks()
	{
	$rtn = '

	function popupSubstitutionRemarks()
	{
	var input = prompt("[Optional] Please enter a remark\nor comment for this substitution:","");
	if (input == null || input == "")
	{
	return;
	}

	document.getElementById("subRemarks").value = escape(input);
	document.getElementById("subRemarksDiv").innerHTML = "<i>Remark: " + input + "</i>";
	}

	';

	return $rtn;
	}

	*/
	function getJS_popupSaveSubstitution()
	{
		$rtn = '
		
		function popupSaveSubstitution(courseID, groupID, semesterNum)
		{
			var subHours = document.getElementById("subHours").value;
			var subCourseID = 0;
			var subAddition = "";
			if (document.getElementById("cbAddition").checked == true)
			{
				subAddition = "true";
			}
			
			var cbs = document.getElementsByName("subCourse");
			for (var t = 0; t < cbs.length; t++)
			{
				var cb = cbs[t];
				if (cb.checked == true)
				{
					// In other words, this course
					// was selected.
					subCourseID = cb.value;
				}
			}
			
			//alert(courseID);
						
			var subTermID = document.getElementById("subTermID").value;		
			var subTransferFlag = document.getElementById("subTransferFlag").value;		
			var subRemarks = document.getElementById("subRemarks").value;		

			// make sure the remarks do not have a _ in them.
			subRemarks = str_replace("_", "-", subRemarks);

			//alert(subRemarks)

			if (subHours < 1 || subCourseID == 0)
			{
				alert("Please select a course to substitute.");
				return;
			}
			
			opener.saveSubstitution(courseID, groupID, semesterNum, subCourseID, subTermID, subTransferFlag, subHours, subAddition, subRemarks);
			window.close();
		}
		

	//+ Jonas Raoni Soares Silva	
	//@ http://jsfromhell.com
	// Found this function on the Internet.  It acts like php str_replace function:
	function str_replace(f, r, s)
	{
     	var ra = r instanceof Array, sa = s instanceof Array, l = (f = [].concat(f)).length, r = [].concat(r), i = (s = [].concat(s)).length;
     	while(j = 0, i--)
     		while(s[i] = s[i].split(f[j]).join(ra ? r[j] || "" : r[0]), ++j < l);
     	return sa ? s : s[0];
    }
		
		
			';
		return $rtn;
	}


	function getJS_saveSubstitution()
	{
		$rtn = '
		function saveSubstitution(courseID, groupID, semesterNum, subCourseID, subTermID, subTransferFlag, subHours, subAddition, subRemarks)
		{
			//alert("The user to sub course " + courseID + " for group " + groupID + " in sem " + semesterNum + "for course " + subCourseID + " hours: " + subHours + " addition: " + subAddition + "remarks: " + subRemarks);
				
			var hiddenElements = document.getElementById("hiddenElements");
			var e = document.createElement("input");
			e.setAttribute("name","savesubstitution");
			e.setAttribute("type","hidden");
			e.setAttribute("value","" + courseID + "_" + groupID + "_" + semesterNum + "_" + subCourseID + "_" + subTermID + "_" + subTransferFlag + "_" + subHours + "_" + subAddition + "_" + subRemarks + "");
			hiddenElements.appendChild(e);			
			
			// rebuild the cache.
			document.getElementById("loadFromCache").value="no";
			
			submitForm(true);
			
		}
				';
		return $rtn;
	}




	function getJS_updateSelectedCourse()
	{
		$rtn = '
		function updateSelectedCourse(courseID, groupID, semesterNum, varHours, randomID, advisingTermID)
		{
			//alert("The user selected course " + courseID + " for group " + groupID + " in sem " + semesterNum + "for var hours " + varHours + "id: " + randomID + " term:" + advisingTermID);
					
			var hiddenElements = document.getElementById("hiddenElements");
			var e = document.createElement("input");
			e.setAttribute("name","updatecourse");
			e.setAttribute("type","hidden");
			e.setAttribute("value","" + courseID + "_" + groupID + "_" + semesterNum + "_" + varHours + "_" + randomID + "_" + advisingTermID);
			hiddenElements.appendChild(e);			
			
			submitForm(true);
			
		}
				';
		return $rtn;
	}


	function getJS_assignSelectedCourseToGroup()
	{
		// Meant to handle when a user selects a course for
		// advising from the popup window.

		$rtn = '
		function assignSelectedCourseToGroup(courseID, semesterNum, groupID, varHours, advisingTermID, dbGroupRequirementID)
		{
			//alert("The user selected course " + courseID + " for group " + groupID + " in sem " + semesterNum + "for var hours " + varHours + " termid:" + advisingTermID + " grid:" + dbGroupRequirementID);
			
			varHours = varHours * 1;
			var hiddenElements = document.getElementById("hiddenElements");
			var e = document.createElement("input");
			e.setAttribute("name","advisecourse_" + courseID + "_" + semesterNum + "_" + groupID + "_" + varHours + "_random34534534534" + "_" + advisingTermID + "_" + dbGroupRequirementID);
			e.setAttribute("type","hidden");
			e.setAttribute("value","true");
			hiddenElements.appendChild(e);			
			
			submitForm(true);
			
		}
				';
		return $rtn;
	}

	function getJS_removeSubstitution()
	{

		$rtn = '
		function removeSubstitution(subID)
		{
			var hiddenElements = document.getElementById("hiddenElements");
			var e = document.createElement("input");
			e.setAttribute("name","removesubstitution");
			e.setAttribute("type","hidden");
			e.setAttribute("value","" + subID + "_" + "");
			hiddenElements.appendChild(e);			
			
			// rebuild the cache.
			document.getElementById("loadFromCache").value="no";
			
			
			submitForm(true);
			
		}
				';
		return $rtn;
	}


	function getJS_restoreTransferEqv()
	{

		$rtn = '
		function restoreTransferEqv(dbUnassignTransferID)
		{
			var hiddenElements = document.getElementById("hiddenElements");
			var e = document.createElement("input");
			e.setAttribute("name","restore_transfer_eqv");
			e.setAttribute("type","hidden");
			e.setAttribute("value","" + dbUnassignTransferID + "_" + "");
			hiddenElements.appendChild(e);			
			
			// rebuild the cache.
			document.getElementById("loadFromCache").value="no";
			
			
			submitForm(true);
			
		}
				';
		return $rtn;
	}


	function getJS_restoreUnassignFromGroup()
	{

		$rtn = '
		function restoreUnassignFromGroup(dbUnassignGroupID)
		{
		
			var hiddenElements = document.getElementById("hiddenElements");
			var e = document.createElement("input");
			e.setAttribute("name","restore_unassign_group");
			e.setAttribute("type","hidden");
			e.setAttribute("value","" + dbUnassignGroupID + "_" + "");
			hiddenElements.appendChild(e);			
			
			// rebuild the cache.
			document.getElementById("loadFromCache").value="no";
			
			
			submitForm(true);
			
		}
				';
		return $rtn;
	}



	function getJS_submitForm()
	{

		$rtn = '
		function submitForm(boolShowUpdating)
		{
			var scrollTop = document.body.scrollTop;
			
			document.getElementById("scrollTop").value = scrollTop;
		
			// Display an updating message...
			if (boolShowUpdating == true)
			{
				showUpdate(false); // function is in the template itself.				
							
			}
			
			var mainform = document.getElementById("mainform");
			mainform.submit();
		}
				';
		return $rtn;
	}

	function getJS_popupRemoveSubstitution()
	{

		$rtn = '
		function popupRemoveSubstitution(subID)
		{
		
			var x = confirm("Are you sure you wish to remove this substitution?");
			if (x)
			{
				opener.removeSubstitution(subID);
				window.close();
			}	
		
		}
				';
		return $rtn;
	}


	function getJS_popupRestoreTransferEqv()
	{

		$rtn = '
		function popupRestoreTransferEqv(dbUnassignTransferID)
		{
		
			opener.restoreTransferEqv(dbUnassignTransferID);
			window.close();
			//window.location=window.location;  // refresh popup window
		
		}
				';
		return $rtn;
	}


	function getJS_popupRestoreUnassignFromGroup()
	{

		$rtn = '
		function popupRestoreUnassignFromGroup(dbUnassignGroupID)
		{
		
			opener.restoreUnassignFromGroup(dbUnassignGroupID);
			window.close();
			//window.location=window.location;  // refresh popup window
		
		}
				';
		return $rtn;
	}



	function getJS_popupSetVarHours()
	{
		// Meant to handle when a user selects a course for
		// advising from the popup window.

		$rtn = '
		function popupSetVarHours()
		{
			var hid = document.getElementById("varHours");
			
			var sel = document.getElementById("selHours");
			
			hid.value = sel.value;
	
		}
				';
		return $rtn;
	}



	function getJS_popupUpdateSelectedCourse()
	{

		$rtn = '
		function popupUpdateSelectedCourse(courseID, groupID, semesterNum, randomID, advisingTermID)
		{

			var varHours = document.getElementById("varHours").value;

			opener.updateSelectedCourse(courseID, groupID, semesterNum, varHours, randomID, advisingTermID);
			window.close();

		}
		';
		return $rtn;
	}


	function getJS_popupAssignSelectedCourseToGroup()
	{
		// Meant to handle when a user selects a course for
		// advising from the popup window.

		$rtn = '
		function popupAssignSelectedCourseToGroup(semesterNum, groupID, advisingTermID, dbGroupRequirementID)
		{

			var varHours = document.getElementById("varHours").value;

			var c = document.getElementsByName("course");
			for (var t = 0; t < c.length; t++)
			{
				if (c[t].checked == true)
				{ // Found users selection.
					var courseID = c[t].value;
					
					if (dbGroupRequirementID == -1)
					{
						dbGroupRequirementID = document.getElementById("" + courseID + "_dbGroupRequirementID").value;
					}
					
					opener.assignSelectedCourseToGroup(courseID, semesterNum, groupID, varHours, advisingTermID, dbGroupRequirementID);

					window.close();
				}
			}


			return false;
		}
		';
		return $rtn;
	}


	function getJS_selectCourseFromGroup()
	{
		$rtn = '
		function selectCourseFromGroup(groupID, semesterNum, groupHoursRemaining, blankDegreeID)
		{
			popupWindow("displayGroupSelect","groupID=" + groupID + "&semesterNum=" + semesterNum + "&groupHoursRemaining=" + groupHoursRemaining + "&blankDegreeID=" + blankDegreeID);
		}
		';


		return $rtn;
	}

	function getJS_describeCourse()
	{
		$scriptFilename = $this->scriptFilename;
		$rtn = '
		function describeCourse(dataString, blankDegreeID)
		{
			popupWindow("displayDescription","dataString=" + dataString + "&blankDegreeID=" + blankDegreeID);
		}
		';
		return $rtn;
	}


	function getJS_popupDescribeSelected()
	{
		$scriptFilename = $this->scriptFilename;
		$rtn = '
		function popupDescribeSelected(groupID, semesterNum, optionalCourseID, selectedSubject, extraVars)
		{
			// This will go through the list of radio buttons
			// on a group select screen (of a popup window),
			// and look for the selected one, and then
			// switch over to a description.  Meant to be
			// called from clicking a tab. (The tabs onClick).

			var courseID = optionalCourseID;
			if (courseID < 1)
			{ // CourseID wasnt specified, so try to figure it out...

				var cbs = document.getElementsByName("course");
				for (var t = 0; t < cbs.length; t++)
				{
					var cb = cbs[t];
					if (cb.checked == true)
					{
						// In other words, this course
						// was selected.
						courseID = cb.value;
						// Also attempt to figure out the selectedSubject, if
						// one has not been supplied.
						if (selectedSubject == "")
						{
							selectedSubject = document.getElementById("" + courseID + "_subject").value;
						}
						break;
					}
				}
			}

			window.location = "' . $scriptFilename . '?windowMode=popup&performAction=displayGroupSelect&performAction2=describeCourse&courseID=" + courseID + "&groupID=" + groupID + "&semesterNum=" + semesterNum + "&selectedSubject=" + selectedSubject + "&currentStudentID=" + csid + "&" + extraVars;

		}
		';
		return $rtn;

	}


	function getJS_popupSubstituteSelected()
	{
		$scriptFilename = $this->scriptFilename;
		$rtn = '
		function popupSubstituteSelected(courseID, groupID, semesterNum, extraVars)
		{

			if (courseID < 1)
			{ // CourseID wasnt specified, so try to figure it out...

				var cbs = document.getElementsByName("course");
				for (var t = 0; t < cbs.length; t++)
				{
					var cb = cbs[t];
					if (cb.checked == true)
					{
						// In other words, this course
						// was selected.
						courseID = cb.value;
						break;
					}
				}
			}


			window.location = "' . $scriptFilename . '?windowMode=popup&performAction=substituteSelected&courseID=" + courseID + "&groupID=" + groupID + "&semesterNum=" + semesterNum + "&currentStudentID=" + csid + "&" + extraVars;
		}
		';
		return $rtn;

	}



	function getJS_popupBackToGroupSelect()
	{
		$scriptFilename = $this->scriptFilename;
		$rtn = '
		function popupBackToGroupSelect(courseID, groupID, semesterNum, extraVars)
		{
			// This is meant to be called when switching back
			// from a course description tab, while in the
			// group select popup window.  So, this is like I
			// am reading a description of a course, then I click
			// the select tab to go back.  The courseID is the ID
			// of the course whose description I was just reading.

			window.location = "' . $scriptFilename . '?windowMode=popup&performAction=displayGroupSelect&courseID=" + courseID + "&groupID=" + groupID + "&semesterNum=" + semesterNum + "&currentStudentID=" + csid + "&" + extraVars;

		}
		';
		return $rtn;

	}


	function getJS_popupWindow()
	{
		$scriptFilename = $this->scriptFilename;
		$rtn = '
		function popupWindow(action, extraVars)
		{
			var my_windowx = window.open("' . $scriptFilename . '?windowMode=popup&performAction=" + action + "&currentStudentID=" + csid + "&" + extraVars,
			"courseinfox" + csid,"toolbar=no,status=2,scrollbars=yes,resizable=yes,width=460,height=375");

			my_windowx.focus();  // make sure the popup window is on top.

		}
		';
		return $rtn;

	}

	function getJS_popupWindow2()
	{
		$scriptFilename = $this->scriptFilename;
		$rtn = '
		function popupWindow2(action, extraVars)
		{
			var my_windowx2 = window.open("' . $scriptFilename . '?windowMode=popup&performAction=" + action + "&currentStudentID=" + csid + "&" + extraVars,
			"courseinfox2" + csid,"toolbar=no,status=2,scrollbars=yes,resizable=yes,width=460,height=375");

			my_windowx2.focus();  // make sure the popup window is on top.

		}


		';
		return $rtn;

	}

	function getJS_popupPrintWindow()
	{

		$rtn = '
		function popupPrintWindow(url)
		{
			var my_windowx2p = window.open(url + "&currentStudentID=" + csid,
			"courseinfoxprint" + csid,"toolbar=no,status=2,scrollbars=yes,resizable=yes,width=700,height=500");

			my_windowx2p.focus();  // make sure the popup window is on top.

		}
		';

		return $rtn;
	}

	function getJS_popupHelpWindow()
	{
		$rtn = '
		function popupHelpWindow(url)
		{
			var my_windowxhelp2p = window.open(url + "&currentStudentID=" + csid,
			"courseinfoxhelp" + csid,"toolbar=no,status=2,scrollbars=yes,resizable=yes,width=700,height=500");

			my_windowxhelp2p.focus();  // make sure the popup window is on top.

		}
				
		';
		return $rtn;

	}


	function getJS_toggleDisabledChangeTerm()
	{
		$rtn = '

		function toggleDisabledChangeTerm(x,y,termDescription)
		{
			var t = "";
			t = t + "This course was advised for the " + termDescription + ". ";
			t = t + "It cannot be unselected from here.  Please first change the Currently Advising term to \"" + termDescription + "\"";
			t = t + " by clicking the [change] link near the top of the page. ";

			alert(t);
		}

		';
		return $rtn;
	}

	function getJS_toggleDisabledCompleted()
	{
		$rtn = '

		function toggleDisabledCompleted(x,y,type)
		{
			var t = "";
			if (type == "completed")
			{
				t = t + "The student has successfully completed this course.";
				t = t + "To advise the student to retake this course, please select it from the \"Courses Added by Advisor\" box at the bottom of the screen.";
			} else if (type == "enrolled")
			{
				t = t + "The student is currently enrolled in this course. ";
				t = t + "To advise the student to retake this course, please select it from the \"Courses Added by Advisor\" box at the bottom of the screen.";
			}
			alert(t);
		}

		';
		return $rtn;
	}


	function getJS_toggleSelection()
	{
		$imgPath = $this->themeLocation . "/images";
		$rtn = '
		function toggleSelection(uniqueID, displayStatus, warningMsg)
		{
			// We expect this to be the graphic of the checkbox.
			var img = document.getElementById("cb_" + uniqueID);
			// This is the hidden variable for this course, to
			// determine if it has been selected or not.
			var course = document.getElementById("advisecourse_" + uniqueID);

			if (course.value == "true")
			{
				// Meaning, this course is currently selected.
				// So, unselect it.
				course.value = "";
				img.src = "' . $imgPath . '/cb_" + displayStatus + ".gif";
			} else {
				// Meaning, this is unselected, so lets select it.
				if (warningMsg != "")
				{
					var x = confirm(warningMsg);
					if (!x)
					{
						return;
					}
				}

				course.value = "true";
				img.src = "' . $imgPath . '/cb_" + displayStatus + "-check.gif";

			}
		}

		function dummyToggleSelection(x,y,x)
		{
			return;
		}

		';

		return $rtn;
	}

	function getJS_toggleSelectionAndSave()
	{
		$rtn = '
		function toggleSelectionAndSave(uniqueID, displayStatus, warningMsg)
		{
			toggleSelection(uniqueID, displayStatus, warningMsg);
			submitForm(true);
		}
		';
		return $rtn;
	}

	function getJS_setVar()
	{
		$rtn = '
		function setVar(id, newValue)
		{
			document.getElementById(id).value = newValue
		}
		';
		return $rtn;
	}


	/**
	 * Returns a list of "hidden" HTML input tags which are used to keep
	 * track of advising variables between page loads.
	 *
	 * @param string $performAction
	 *       - Used for when we submit the form, so that FlightPath will
	 *         know what action we are trying to take.
	 * 
	 * @return string
	 */
	function getHiddenAdvisingVariables($performAction = "")
	{
		$rtn = "";

		$rtn .= "<span id='hiddenElements'>
		
			<input type='hidden' name='performAction' id='performAction' value='$performAction'>
			<input type='hidden' name='performAction2' id='performAction2' value=''>
			<input type='hidden' name='scrollTop' id='scrollTop' value=''>
			<input type='hidden' name='loadFromCache' id='loadFromCache' value='yes'>
			<input type='hidden' name='printView' id='printView' value='{$GLOBALS["printView"]}'>			
			<input type='hidden' name='hideCharts' id='hideCharts' value=''>
			
			<input type='hidden' name='advisingLoadActive' id='advisingLoadActive' value='{$GLOBALS["advisingLoadActive"]}'>
			<input type='hidden' name='advisingStudentID' id='advisingStudentID' value='{$GLOBALS["advisingStudentID"]}'>
			<input type='hidden' name='advisingTermID' id='advisingTermID' value='{$GLOBALS["advisingTermID"]}'>
			<input type='hidden' name='advisingMajorCode' id='advisingMajorCode' value='{$GLOBALS["advisingMajorCode"]}'>
			<input type='hidden' name='advisingTrackCode' id='advisingTrackCode' value='{$GLOBALS["advisingTrackCode"]}'>
			<input type='hidden' name='advisingUpdateStudentSettingsFlag' id='advisingUpdateStudentSettingsFlag' value=''>
			<input type='hidden' name='advisingWhatIf' id='advisingWhatIf' value='{$GLOBALS["advisingWhatIf"]}'>
			<input type='hidden' name='whatIfMajorCode' id='whatIfMajorCode' value='{$GLOBALS["whatIfMajorCode"]}'>
			<input type='hidden' name='whatIfTrackCode' id='whatIfTrackCode' value='{$GLOBALS["whatIfTrackCode"]}'>
			<input type='hidden' name='advisingView' id='advisingView' value='{$GLOBALS["advisingView"]}'>

			<input type='hidden' name='currentStudentID' id='currentStudentID' value='{$GLOBALS["currentStudentID"]}'>
			<input type='hidden' name='logAddition' id='logAddition' value=''>
			
			<input type='hidden' name='fpUpdateUserSettingsFlag' id='fpUpdateUserSettingsFlag' value=''>
			
			</span>
			";

		return $rtn;
	}


}
?>