/* Javascript for the Advise module */

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


/* Toggle an advising checkbox */
function toggleSelection(uniqueID, display_status, warningMsg) {
  // We expect this to be the graphic of the checkbox.
  
  var imgPath = FlightPath.settings.themeLocation + "/images";
  
  var img = $("#cb_" + uniqueID);
  // This is the hidden variable for this course, to
  // determine if it has been selected or not.
  var course = $("#advisecourse_" + uniqueID);

  if (course.val() == "true") {
    // Meaning, this course is currently selected.
    // So, unselect it.
    course.val("");
    img.attr("src", imgPath + "/cb_" + display_status + ".gif");
  } else {
    // Meaning, this is unselected, so lets select it.
    if (warningMsg != "") {
      var x = confirm(warningMsg);
      if (!x) {
        return;
      }
    }

    course.val("true");
    img.attr("src", imgPath + "/cb_" + display_status + "-check.gif");
    
  }
}

function dummyToggleSelection(x,y,x) {
  return;
}


function describeCourse(dataString, blankDegreeID) {
  var url = FlightPath.settings.basePath + "/advise/popup-course-description";
  popupWindowNew(url,"data_string=" + dataString + "&blank_degree_id=" + blankDegreeID);
}


function selectCourseFromGroup(group_id, semester_num, groupHoursRemaining, blankDegreeID) {
  var url = FlightPath.settings.basePath + "/advise/popup-group-select";  
  popupWindowNew(url,"group_id=" + group_id + "&semester_num=" + semester_num + "&group_hours_remaining=" + groupHoursRemaining + "&blank_degree_id=" + blankDegreeID);
}

/**
 * Specifically, this is for redirecting the poup to show a course description 
 * from a group.
 */
function popupDescribeSelected(group_id, semester_num, optionalCourseID, selectedSubject, extraVars)
{
  // This will go through the list of radio buttons
  // on a group select screen (of a popup window),
  // and look for the selected one, and then
  // switch over to a description.  Meant to be
  // called from clicking a tab. (The tabs onClick).

  var course_id = optionalCourseID;
  if (course_id < 1)
  { // CourseID wasnt specified, so try to figure it out...

    var cbs = document.getElementsByName("course");
    for (var t = 0; t < cbs.length; t++)
    {
      var cb = cbs[t];
      if (cb.checked == true)
      {
        // In other words, this course
        // was selected.
        course_id = cb.value;
        // Also attempt to figure out the selectedSubject, if
        // one has not been supplied.
        if (selectedSubject == "")
        {
          selectedSubject = document.getElementById("" + course_id + "_subject").value;
        }
        break;
      }
    }
  }

  window.location = FlightPath.settings.basePath + "/advise/popup-group-select" + "&window_mode=popup&perform_action2=describe_course&course_id=" + course_id + "&group_id=" + group_id + "&semester_num=" + semester_num + "&selected_subject=" + selectedSubject + "&current_student_id=" + FlightPath.settings.currentStudentId + "&" + extraVars;

}


function popupBackToGroupSelect(course_id, group_id, semester_num, extraVars) {
  // This is meant to be called when switching back
  // from a course description tab, while in the
  // group select popup window.  So, this is like I
  // am reading a description of a course, then I click
  // the select tab to go back.  The course_id is the ID
  // of the course whose description I was just reading.
  window.location = FlightPath.settings.basePath + "/advise/popup-group-select" + "&window_mode=popup&course_id=" + course_id + "&group_id=" + group_id + "&semester_num=" + semester_num + "&current_student_id=" + FlightPath.settings.currentStudentId + "&" + extraVars;
}


function popupSubstituteSelected(course_id, group_id, semester_num, extraVars)
{

  if (course_id < 1)
  { // CourseID wasnt specified, so try to figure it out...

    var cbs = document.getElementsByName("course");
    for (var t = 0; t < cbs.length; t++)
    {
      var cb = cbs[t];
      if (cb.checked == true)
      {
        // In other words, this course
        // was selected.
        course_id = cb.value;
        break;
      }
    }
  }

  window.location = FlightPath.settings.basePath + "/advise/popup-substitute-selected" + "&window_mode=popup&course_id=" + course_id + "&group_id=" + group_id + "&semester_num=" + semester_num + "&current_student_id=" + FlightPath.settings.currentStudentId + "&" + extraVars;
}


/**
 * Meant to be called from our group select popup.
 */
function popupAssignSelectedCourseToGroup(semester_num, group_id, advising_term_id, db_group_requirement_id) {

  var varHours = document.getElementById("varHours").value;

  var c = document.getElementsByName("course");
  for (var t = 0; t < c.length; t++)
  {
    if (c[t].checked == true)
    { // Found users selection.
      var course_id = c[t].value;
      
      if (db_group_requirement_id == -1)
      {
        db_group_requirement_id = document.getElementById("" + course_id + "_db_group_requirement_id").value;
      }
      
      opener.assignSelectedCourseToGroup(course_id, semester_num, group_id, varHours, advising_term_id, db_group_requirement_id);

      window.close();
    }
  }

  return false;
}

/**
 * Meant to actually advise a course into a group.
 */
function assignSelectedCourseToGroup(course_id, semester_num, group_id, varHours, advising_term_id, db_group_requirement_id)
{
  //alert("The user selected course " + course_id + " for group " + group_id + " in sem " + semester_num + "for var hours " + varHours + " termid:" + advising_term_id + " grid:" + db_group_requirement_id);
  //return;
  
  varHours = varHours * 1;
  var hiddenElements = document.getElementById("hidden_elements");
  var e = document.createElement("input");
  e.setAttribute("name","advisecourse_" + course_id + "_" + semester_num + "_" + group_id + "_" + varHours + "_random34534534534" + "_" + advising_term_id + "_" + db_group_requirement_id);
  e.setAttribute("type","hidden");
  e.setAttribute("value","true");
  hiddenElements.appendChild(e);      
  
  submitForm(true);
  
}


function submitSaveActive() {
    document.getElementById("perform_action").value = "save_active";
    submitForm();
}

function submitForm(boolShowUpdating) {
  var scrollTop = document.body.scrollTop;
  
  document.getElementById("scroll_top").value = scrollTop;

  // Display an updating message...
  if (boolShowUpdating == true)
  {
    showUpdate(false); // function is in the template itself.       
          
  }
  
  var mainform = document.getElementById("mainform");
  mainform.submit();
}

////////////////////////////////////////

function popupWindowNew(url, extraVars) {
  var my_windowx = window.open(url + "?window_mode=popup&current_student_id=" + FlightPath.settings.currentStudentId + "&" + extraVars,
  "courseinfox" + FlightPath.settings.currentStudentId,"toolbar=no,status=2,scrollbars=yes,resizable=yes,width=460,height=375");

  my_windowx.focus();  // make sure the popup window is on top.
  
}


function popupWindow(action, extraVars) {
  
  var my_windowx = window.open("' . $script_filename . '?windowMode=popup&performAction=" + action + "&current_student_id=" + csid + "&" + extraVars,
  "courseinfox" + csid,"toolbar=no,status=2,scrollbars=yes,resizable=yes,width=460,height=375");

  my_windowx.focus();  // make sure the popup window is on top.

}






