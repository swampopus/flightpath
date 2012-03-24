/* JS for the admin module */

function adminPopupAlertHelp(action)
{
  var t = "Instant Help:\n------------------\n\n";

  if (action == "edit_announcements")
  {
    t = t + "Use this to edit the announcements found on the Main tab in FlightPath.";
    
  }
  if (action == "edit_urgent")
  {
    t = t + "An Urgent Message is one which is displayed at the top of every page in FlightPath, for every user.  Good examples are to warn that the system is about to be taken down.";
  }
  if (action == "user_types")
  {
    t = t + "Available faculty/staff user types in FlightPath:\n\n";
    t = t + "  none - The user may not log into FlightPath.\n";
    t = t + "  limited faculty student - The user is redefined as a student upon login, so that they may only view their own degree plan.\n";
    t = t + "  viewer - The user may search for any student in the system and load their degree plan, but they may not advise them.\n";
    t = t + "  advisor - The user may search for any student in the system and load their degree plan, and they can advise the student.  They cannot perform substitutions.\n";
    t = t + "  college_coordinator - The highest level user in the system (next to administrators). They may advise any student as well as perform substitutions.\n";
    
  }

  
  if (action == "public_note")
  {
    t = t + "A public note will appear at the top of a degree plan when pulled up in FlightPath.  Use this ";
    t = t + "to pass messages to all students and advisors who pull up this degree plan. \n\n";
    t = t + "It will begin with the text \"Important Message:\" automatically.";
  }
  
  
  if (action == "degree_exclude")
  {
    t = t + "If the Exclude value is set to 1 (the number one), then this degree will show up in gray on the list of degrees.  It will also not be selectable in What If mode in FlightPath. ";
    t = t + "If you are not sure what to enter, either leave it blank or enter a 0 (zero).";
  }
  
  if (action == "degree_class")
  {
    t = t + "Enter the degree classification code in this box.  If left blank, it is assumed to be an \"Undergraduate\" degree.\n";
    t = t + "Enter \"G\" for a degree which should only be accessible to Graduate students in What If mode.\n\n";
    t = t + "NOTE: NOT CURRENTLY SUPPORTED IN THIS VERSION OF FLIGHTPATH";
  }
  
  
  if (action == "track_description")
  {
    t = t + "This is where you can enter a short description of this track (also called a Degree Option) which will display for the user in a pop-up when they select to change degree options. ";
    t = t + "\n\nTo enter a default message, which will display at the top of the selection window, begin the description with:\n      DEFAULT: \nIt must be in all caps, and you must have the colon (:) after it. ";
    t = t + "By doing this in ANY of the track descriptions for a major, FP will ignore all other track descriptions and ONLY display the default. ";
    t = t + "\n\nExample of usage:  DEFAULT: You may select any of these degree options.";
  }   
  
  if (action == "degree_entry")
  {
    t = t + "Enter course requirements in this format: \n   SUBJECT COURSENUM type (MINGRADE)\n\n";
    t = t + "  type - lowercase character denoting the requirement type of the course.  Make sure you have a space between it and the course number.  If no type is specified, it is understood to be a major requirement.\n";
    t = t + "  min grade - Place the min grade (if there is one) in parenthesis after the type.  Make sure there is a space between the min grade and the type (or course number, if there is no type specified).\n   Example:  ACCT 110 s (C)\n\n";
    t = t + "Repeats require no special characters or symbols.  Simply enter the course again.";
  }

  if (action == "group_entry")
  {
    t = t + "Enter information about a course in this format:\n   SUBJECT COURSENUM (mingrade) [repeats]\n\n";
    t = t + "Entering minimum grades works the same as it does in Degree entry.  Simply specify the min grade in parenthesis () after the course number.\n   Ex: ";
    t = t + " ACCT 110 (C) \n";
    t = t + "It is very important to have a space between the course number and the min grade.\nSpecifying repeats works the same way, but uses brackets. ";
    t = t + "For example, to specify that a student may take a course 4 times in a group, enter this:\n     ACCT 110 (C) [4]\nThis will designate that the course may be taken a total of 4 times for this group,";
    t = t + "and FlightPath will display a message telling the user such.  You do not need to specify a min grade in order to specify repeats.  For example, ACCT 110 [4].\n\n**VERY IMPORTANT**: You may only specify repeats in groups that DO NOT have branches!";
  }
  
  
  if (action == "edit_formatting")
  {
    t = t + "You may add BOLD, ITALICS, and UNDERLINES to your text by adding simple BBCode tags.\n\n";
    t = t + "To make text appear BOLD, use [b] and [/b] tags.  For example:\n    This is [b]bold[/b] text.\n\n";
    t = t + "Italics and underlines works similarly.\n  Ex:  This is [i]italics[/i] text.  This is [u]underline[/u] text.\n\n";
    t = t + "Other allowed tags:\n\nColors: [color=green]text[/color]  (most common colors allowed)\n";
    t = t + "Line-Break:  [br]  (forces a line break) \n";
    t = t + "Links: [url=http://www.google.com]Click here for Google![/url]\n   -- Links will always load in a new window.\n";
    t = t + "Popups:  [popup=help.php?i=2]Click here for a Help popup[/popup]\n  -- The [popup] tag (works great with Help pages) is ";
    t = t + "just like the [url] tag, except it will load the page in a medium-sized popup window. ";
    

  }

  if (action == "all_years")
  {
    t = t + "Since courses can exist in multiple years (ex: 2006, 2007, and 2008), checking this ";
    t = t + "box gives you the option of updating title, description, and hour information for all instances ";
    t = t + "of this course, in all available catalog years.\n\nOptional comments are NOT updated across years.\nCourse names and eqvs (and excludes) are automatically updated across all years.\n\n";
    t = t + "If unsure what to do, leave this box unchecked. ";

  }
  
  if (action == "semester_title")
  {
    t = t + "You may override the default title for a block.  For example, if instead of Freshman Year you want it to read Pre-Pharmacy Year 1 in FlightPath, ";
    t = t + "then you would enter that in this box and hit save.  To change a title back to the default, just leave it blank.";
  }

  
  if (action == "datetime")
  {
    t = t + "Date/time stamps should be entered in Year-major order: YYYY-MM-DD. Ex:\n";
    t = t + "   2008-01-12 \n";
    t = t + "Entering a time is OPTIONAL.  If you would like to enter a time, please enter it after the date, in this format: \n";
    t = t + "HH:MM:SS  For example:\n  2008-01-12 13:15:00   or even   2008-01-12 01:15pm \n";
    t = t + "Seconds are not required to be entered. \n";
    t = t + "\n   You may type simply   NOW   in the box to make it todays date and time!";
  }

  if (action == "group_title")
  {
    t = t + "The group title is what FlightPath will use to refer to this group in all on-screen ";
    t = t + "text like footnotes and windows. Ex: Free Electives, Upper-Level Electives, Core Humanities, etc.";
  }
  
  if (action == "definition")
  {
    t = t + "Definitions provide a very quick way to add or remove many courses from a group.  For example, to add all CSCI courses, enter:\n";
    t = t + "       add CSCI.*\n";
    t = t + "The . is used to seperate the subject from the course number.  The * means \"any value.\"  You may also use it in the subject.  For example, ";
    t = t + "to add all CSCI and any course with a subject that begins with A, enter:\n";
    t = t + "       add CSCI.*\n       add A*.*\n";
    t = t + "Removing courses is done the same way.  For example, you can add all courses, then remove any course with a number lower than 400:\n";
    t = t + "       add *.*\n       rem *.1*\n       rem *.2*\n       rem *.3*\n";
    t = t + "\n\nIt should be stated that add statements will include courses which have been marked as \"exclude.\"  This is normal.  Those courses will not ";
    t = t + "show up in group selection screens, but they will be added to a group if a student has completed that course in the past.";
  }
  
  if (action == "group_name")
  {
    t = t + "The group name is internal to the data entry system, and is never seen by the user. ";
    t = t + "You may use this to distinguish between two groups which may have the same title. ";
    t = t + "For example, the group major_electives_1 may be different from major_electives_2, but ";
    t = t + "both may have the title of simply Major Electives.\n\n";
    t = t + "This field may be considered optional, but is highly recommended you enter something here ";
    t = t + "for your own clarity later on.\n\n";
    t = t + "It is okay to have the same Group Title and Group Name.";
  }

  if (action == "group_priority")
  {
    t = t + "This number is very important, because it determines the order in which ";
    t = t + "courses are assigned to groups in FlightPath.\n\nHigher-priority groups fill in FIRST.\n\n";
    t = t + "For example, lets say group_1 has a priority of 10 and group_2 has a priority of 50.  If both ";
    t = t + "group_1 and group_2 can accept the course ENGL 101, it will be assigned to group_2, because ";
    t = t + "group_2 has the higher priority.\n\n";
  }

  if (action == "course_names")
  {
    t = t + "These are the possible display names for this course. Typically, there will be only one display name.  For example, ACCT 110. ";
    t = t + "Notice there is a space between the subject ID (ACCT) and the course number (110).  This is very important.\n\n";
    t = t + "If this course is known by another name (ie, it has an equivalent course) you may specify that course\'s name as well using a comma. ";
    t = t + "You may chose to exclude a course name (from course selection screens in FlightPath) by simply adding the word exclude after its name. ";
    t = t + "Just make sure to seperate it with a space from the course number.\n\n";
    t = t + "For example: MATH 373, CSCI 373, MATH 373A exclude, MATH 370 exclude \n";
          
    t = t + "\nIMPORTANT: Course names (including eqvs and exclusions) are instantly updated for ALL YEARS of a course.  So, if you exclude ";
    t = t + "a course in 2008, that same course will be marked as exclude for 2006, 2007, and every other year that it exists.  The same is true ";
    t = t + "for when you enter an eqv (by using a comma) to show that a course has more than one name.";
  }
  
  if (action == "course_title")
  {
    t = t + "This is the title of the course, as seen in popup windows on FlightPath.  For example, Biology II Lab.";
  }

  if (action == "course_min_hours" || action == "course_max_hours")
  {
    t = t + "The minimum hours and maximum hours for a course will usually be the same number, for example: 3.  The numbers ";
    t = t + "differ if the course has a variable numbers of hours, say 1-6 hours.  In this example, you would enter 1 as the min hours, ";
    t = t + "and 6 as the max hours.";
  }

  if (action == "course_repeat_hours")
  {
    t = t + "This is for how many hours a course may be repeated for credit.  For example, if a course description reads that ";
    t = t + "a course is worth 3 hours, and may be repeated for up to 9 hours of credit, then you would enter a 9 in this box.\n\n";
    t = t + "Most courses cannot be repeated for credit.  If a course CANNOT be repeated for credit, this number will be the same ";
    t = t + "as the min hours, or simply blank.  If you are unsure what to enter, either leave it blank or enter a zero.";
  }
  
  if (action == "course_exclude")
  {
    t = t + "This is NOT the same as deleting a course!  Excluding a course means it will be removed from selections in ";
    t = t + "groups for the student, but it will remain part of the system, so that if a student has already taken the course, ";
    t = t + "it will at least appear in their excess credits.\n\n";
    t = t + "Set it to one (1) to exclude, or zero (0) to leave the course as active.  By default, courses are not excluded, and are set to zero (0).";
    
  }

  if (action == "group_properties")
  {
    t = t + "The Hrs means how many hours are required to fulfill this group in this semester or year?  For example, 6.  Must contain a whole number larger than 0.\n\n";
    t = t + "The Min Grade is the default minimum grade any course taken from this group must have in order to fulfill the group. ";
    t = t + "This is different from the minimum grade set per-course within the group entry screen.  This minimum grade value will always override ";
    t = t + "any other minimum grade setting within the group.  Leave blank for no min grade (meaning that any passing grade is acceptable.)\n\n";
    t = t + "The Type setting helps FlightPath classify and attribute hours to one of several categories.  If unsure what to put here, use Elective.";
    
  }
  
  var x = alert(t);
}


function adminPopupWindow(url)
{
  var my_windowxvvv = window.open(url,
      "courseinfoxvvv","toolbar=no,status=2,scrollbars=yes,resizable=yes,width=600,height=400");

  my_windowxvvv.focus();  // make sure the popup window is on top.
      
}

function adminPopupWindow2(url)
{
  my_windowx2vvv = window.open(url,
      "courseinfox2vvv","toolbar=no,status=2,scrollbars=yes,resizable=yes,width=500,height=300");

  my_windowx2vvv.focus();  // make sure the popup window is on top.
      
}

function adminViewAnnouncementPreview(count) {
  // Display the announcement in question in a popup window so
  // the admin user can see a preview of what it looks like
  // before saving.
  
  var value = document.getElementById("announcement_" + count).value;
  value = escape(value);
  popupWindow2("admin.php?performAction=previewAnnouncement&announcement=" + value);
  
}

function adminPopupSelectIcon(file) {
  opener.document.getElementById("icon_filename").value = file;
  opener.adminadminSubmitForm();
  window.close();
}

function adminDeleteGroup(group_id) {
  var x = confirm("Are you sure you wish to delete this group? Any degrees which point to it will need to be manually edited and re-saved remove this group requirement.\n\nClick OK to proceed and delete this group.");
  if (!x)
  {
    return;
  }
  
  document.getElementById("perform_action2").value="delete_group";
  adminSubmitForm();
  
}


function adminDeleteDegree(degreeID) {
  var x = confirm("Are you sure you wish to delete this degree? This action cannot be undone.");
  if (!x) {
    return;
  }
  
  document.getElementById("perform_action2").value="delete_degree";
  adminSubmitForm();
  
}


function adminDeleteCourse(course_id, catalog_year, warnEqv) {
  var x = confirm("Are you sure you wish to delete this course for the catalog year " + catalog_year + "?  Any degrees or groups which use this course will have to be manually edited and re-saved to remove this course requirement.\n\nClick OK to proceed and delete this course.");
  //alert("Feature not available yet.");
  if (!x) {
    return;
  }
  
  if (warnEqv == "yes") {
    var x = confirm("It appears this course has equivalencies in place.  If you delete now, it will delete all of the equivalent courses too.  You should remove the eqvs first.  Do you still want to proceed?");
    if (!x) {
      return;
    }
  }
  
  document.getElementById("perform_action2").value="delete_course";
  adminSubmitForm();
  
  
}

function adminProcessDefinitions(catalog_year) {
 var x = confirm("Are you sure you wish to process all group definitions for the year " + catalog_year + "?\n\nAll groups with definitions will be cleared, and their definitions re-run.\n\nNOTICE: This may take more than a minute to complete.\n\nClick OK to proceed.");
 if (x) {
   window.location = "admin.php?performAction=perform_process_group_definitions&de_catalog_year=" + catalog_year;
 }
}

function adminPopupAddGroup(semester_num) {

  var group_id = 0;
  
  var cbs = document.getElementsByName("rgroups");
  for (var t = 0; t < cbs.length; t++)
  {
    var cb = cbs[t];
    if (cb.checked == true) {
      // In other words, this group
      // was selected.
      group_id = cb.value;
    }
  }
  
  var hours = document.getElementById("hours").value;
  var type = document.getElementById("type").value;
  var min_grade = document.getElementById("min_grade").value;
  
  if (hours < 1 || group_id < 1) {
    alert("Please select a group and number of hours!");
    return;
  }
  
  //alert(group_id + " " + hours + " " + type + " " + min_grade);
  opener.document.getElementById("perform_action2").value="addGroup_" + group_id + "_" + semester_num + "_" + hours + "_" + type + "_" + min_grade;
  opener.adminSubmitForm();
  window.close();
      
}


function adminPopupSaveDefinition() {
  var x = confirm("Are you sure you wish to save this definition?  Doing this will overwrite whatever may already be in the Required Courses box.\n\nClick OK to proceed.");
  if (!x) {
    return;
  }
  
  var def = encodeURI(document.getElementById("definition").value);
  opener.document.getElementById("set_definition").value = def;
  opener.showUpdate();
  opener.adminSubmitForm();
  window.close();
  
}

function adminSubmitForm() {
  document.getElementById("scroll_top").value = document.body.scrollTop;
  document.getElementById("mainform").submit();
} 


function adminDelGroup(group_id, semester_num) {
  var dsn = Number(semester_num) + 1;
  var x = confirm("Are you sure you want to delete this group from block " + dsn + "?");
  if (!x) {
    return;
  }
  
  document.getElementById("perform_action2").value="delGroup_" + group_id + "_" + semester_num;
  adminSubmitForm();
  
  
}

function adminConfirmClearJDHistory() {
 var x = confirm("Are you sure you wish to clear the advising and comment history for John Doe (student 99999999)?");
 if (x) {
   window.location = "admin.php?performAction=perform_clear_john_doe";
 }
}