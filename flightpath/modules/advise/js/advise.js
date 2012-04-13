/* Javascript for the Advise module */



function changeTerm(term_id) {
  document.getElementById("advising_term_id").value = term_id;
  document.getElementById("log_addition").value = "change_term~" + term_id;
  
  // rebuild the cache.
  document.getElementById("load_from_cache").value="no";
  
  
  submitForm(true);
}

function changeView(view) {
  document.getElementById("advising_view").value = view;
  
  submitForm(true);
}

function popupChangeTrack(track_code) {
  var x = confirm("Are you sure you wish to change degree options?");
  if (x) {
    opener.changeTrack(track_code);
    window.close();
  }
}

function popupChangeWhatIfTrack(track_code) {
  var x = confirm("Are you sure you wish to change degree options?");
  if (x) {
    opener.document.getElementById("what_if_track_code").value = track_code;
    opener.document.getElementById("load_from_cache").value = "no";
    opener.document.getElementById("log_addition").value = "change_track~" + track_code;
    
    opener.submitForm(true);
    window.close();
  }
}

function changeTrack(track_code) {
  document.getElementById("advising_track_code").value = track_code;
  document.getElementById("advising_update_student_settings_flag").value = "true";
  document.getElementById("log_addition").value = "change_track~" + track_code;
  //alert(document.getElementById("logAddition").value);
  // rebuild the cache.
  document.getElementById("load_from_cache").value="no";

  submitForm(true);
}

function hideShowCharts(status) {
  document.getElementById("hide_charts").value = status;    
  document.getElementById("fp_update_user_settings_flag").value = "true";

  submitForm(true);
}


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



function popupUnassignTransferEqv(course_id) {
  var x = confirm("Are you sure you wish to remove this transfer course equivalency?\n\nThis action will only affect the current student.  It will not impact any other student\'s records.");
  if (x) {
    opener.unassignTransferEqv(course_id);
    window.close();
  }
}


function unassignTransferEqv(course_id) {

  var hiddenElements = document.getElementById("hidden_elements");
  var e = document.createElement("input");
  e.setAttribute("name","unassign_transfer_eqv");
  e.setAttribute("type","hidden");
  e.setAttribute("value","" + course_id + "~" + "");
  hiddenElements.appendChild(e);      
  
  // rebuild the cache.
  document.getElementById("load_from_cache").value="no";
  
  submitForm(true);
  
}


function popupUnassignFromGroup(course_id, term_id, transferFlag, group_id) {
  var x = confirm("Are you sure you wish to remove this course?");
  if (x)
  {
    opener.unassignFromGroup(course_id, term_id, transferFlag, group_id);
    window.close();
  }
}

 

function unassignFromGroup(course_id, term_id, transferFlag, group_id) {

  var hiddenElements = document.getElementById("hidden_elements");
  var e = document.createElement("input");
  e.setAttribute("name","unassign_group");
  e.setAttribute("type","hidden");
  e.setAttribute("value","" + course_id + "~" + term_id + "~" + transferFlag + "~" + group_id + "");
  hiddenElements.appendChild(e);      
  
  // rebuild the cache.
  document.getElementById("load_from_cache").value="no";
  
  
  submitForm(true);
  
}





function toggleDisabledCompleted(x,y,type) {
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

function dummyToggleSelection(x,y,z) {
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

  var var_hours = document.getElementById("varHours").value;

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
      
      opener.assignSelectedCourseToGroup(course_id, semester_num, group_id, var_hours, advising_term_id, db_group_requirement_id);

      window.close();
    }
  }

  return false;
}



function popupUpdateSubData(max_hours, term_id, transferFlag, groupHoursAvail, subCourseHours) {
  document.getElementById("subTermID").value = term_id;
  document.getElementById("subTransferFlag").value = transferFlag;
  // if the addition checkbox is checked, use the groupHoursAvail as
  // the max.
  if (document.getElementById("cbAddition").checked == true)
  {
    max_hours = groupHoursAvail;
    if (max_hours > subCourseHours)
    {
      max_hours = subCourseHours;
    }
  }
  
  //alert(max_hours);
  
  var sel = document.getElementById("subHours");
  
  // Replace this pulldowns elements with a range of values from
  // max_hours to 1.
  
  // First, remove all existing options.
  sel.options.length = 0;

  sel.options[0] = new Option(" Max (default) ", max_hours, true, true);
  var c = 1;
  // Now, add in the others.
  for (var t = max_hours; t > 0; t--)
  {
    sel.options[c] = new Option(" " + t + " ", t, false, false);
    c++;
  }
  
  
}


/* Saves a substitution from the popup */
function saveSubstitution(course_id, group_id, semester_num, subCourseID, subTermID, subTransferFlag, subHours, subAddition, subRemarks) {
  //alert("The user to sub course " + course_id + " for group " + group_id + " in sem " + semester_num + "for course " + subCourseID + " hours: " + subHours + " addition: " + subAddition + "remarks: " + subRemarks);
    
  var hiddenElements = document.getElementById("hidden_elements");
  var e = document.createElement("input");
  e.setAttribute("name","savesubstitution");
  e.setAttribute("type","hidden");
  e.setAttribute("value","" + course_id + "~" + group_id + "~" + semester_num + "~" + subCourseID + "~" + subTermID + "~" + subTransferFlag + "~" + subHours + "~" + subAddition + "~" + subRemarks + "");
  hiddenElements.appendChild(e);      
  
  // rebuild the cache.
  document.getElementById("load_from_cache").value="no";
  
  submitForm(true);
  
}



/**
 * Meant to actually advise a course into a group.
 */
function assignSelectedCourseToGroup(course_id, semester_num, group_id, var_hours, advising_term_id, db_group_requirement_id)
{
  //alert("The user selected course " + course_id + " for group " + group_id + " in sem " + semester_num + "for var hours " + varHours + " termid:" + advising_term_id + " grid:" + db_group_requirement_id);
  //return;
  
  var_hours = var_hours * 1;
  var hiddenElements = document.getElementById("hidden_elements");
  var e = document.createElement("input");
  e.setAttribute("name","advisecourse_" + course_id + "_" + semester_num + "_" + group_id + "_" + var_hours + "_random34534534534" + "_" + advising_term_id + "_" + db_group_requirement_id);
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
  if (boolShowUpdating == true) {
    showUpdate(false); // function is in the template itself.          
  }
  
  var mainform = document.getElementById("mainform");
  mainform.submit();
}




/* For unselecting a course from a group.  We want to remove the selection, then submit the form. */
function toggleSelectionAndSave(uniqueID, display_status, warningMsg) {
  toggleSelection(uniqueID, display_status, warningMsg);
  submitForm(true);
}



function popupSaveSubstitution(course_id, group_id, semester_num) {
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
  
  //alert(course_id);
        
  var subTermID = document.getElementById("subTermID").value;   
  var subTransferFlag = document.getElementById("subTransferFlag").value;   
  var subRemarks = document.getElementById("subRemarks").value;   

  // make sure the remarks do not have a ~ in them.
  subRemarks = str_replace("~", "_", subRemarks);

  //alert(subRemarks)

  if (subHours < 1 || subCourseID == 0)
  {
    alert("Please select a course to substitute.");
    return;
  }
  
  opener.saveSubstitution(course_id, group_id, semester_num, subCourseID, subTermID, subTransferFlag, subHours, subAddition, subRemarks);
  window.close();
}


//+ Jonas Raoni Soares Silva  
//@ http://jsfromhell.com
// Found this function on the Internet.  It acts like php str_replace function:
function str_replace(f, r, s) {
    var ra = r instanceof Array, sa = s instanceof Array, l = (f = [].concat(f)).length, r = [].concat(r), i = (s = [].concat(s)).length;
    while(j = 0, i--)
      while(s[i] = s[i].split(f[j]).join(ra ? r[j] || "" : r[0]), ++j < l);
  return sa ? s : s[0];
}


function saveSubstitution(course_id, group_id, semester_num, subCourseID, subTermID, subTransferFlag, subHours, subAddition, subRemarks) {
  //alert("The user to sub course " + course_id + " for group " + group_id + " in sem " + semester_num + "for course " + subCourseID + " hours: " + subHours + " addition: " + subAddition + "remarks: " + subRemarks);
    
  var hiddenElements = document.getElementById("hidden_elements");
  var e = document.createElement("input");
  e.setAttribute("name","savesubstitution");
  e.setAttribute("type","hidden");
  e.setAttribute("value","" + course_id + "~" + group_id + "~" + semester_num + "~" + subCourseID + "~" + subTermID + "~" + subTransferFlag + "~" + subHours + "~" + subAddition + "~" + subRemarks + "");
  hiddenElements.appendChild(e);      
  
  // rebuild the cache.
  document.getElementById("load_from_cache").value="no";
  
  submitForm(true);
  
}

function updateSelectedCourse(course_id, group_id, semester_num, varHours, random_id, advising_term_id) {
  //alert("The user selected course " + course_id + " for group " + group_id + " in sem " + semester_num + "for var hours " + varHours + "id: " + random_id + " term:" + advising_term_id);
      
  var hiddenElements = document.getElementById("hidden_elements");
  var e = document.createElement("input");
  e.setAttribute("name","updatecourse");
  e.setAttribute("type","hidden");
  e.setAttribute("value","" + course_id + "~" + group_id + "~" + semester_num + "~" + varHours + "~" + random_id + "~" + advising_term_id);
  hiddenElements.appendChild(e);      
  
  submitForm(true);
  
}


function removeSubstitution(subID)
{
  var hiddenElements = document.getElementById("hidden_elements");
  var e = document.createElement("input");
  e.setAttribute("name","removesubstitution");
  e.setAttribute("type","hidden");
  e.setAttribute("value","" + subID + "~" + "");
  hiddenElements.appendChild(e);      
  
  // rebuild the cache.
  document.getElementById("load_from_cache").value="no";
  
  
  submitForm(true);
  
}


function restoreTransferEqv(db_unassign_transfer_id) {
  var hiddenElements = document.getElementById("hidden_elements");
  var e = document.createElement("input");
  e.setAttribute("name","restore_transfer_eqv");
  e.setAttribute("type","hidden");
  e.setAttribute("value","" + db_unassign_transfer_id + "~" + "");
  hiddenElements.appendChild(e);      
  
  // rebuild the cache.
  document.getElementById("load_from_cache").value="no";
  
  
  submitForm(true);
  
}

function restoreUnassignFromGroup(db_unassign_group_id)
{

  var hiddenElements = document.getElementById("hidden_elements");
  var e = document.createElement("input");
  e.setAttribute("name","restore_unassign_group");
  e.setAttribute("type","hidden");
  e.setAttribute("value","" + db_unassign_group_id + "~" + "");
  hiddenElements.appendChild(e);      
  
  // rebuild the cache.
  document.getElementById("load_from_cache").value="no";
  
  
  submitForm(true);
  
}


function popupRemoveSubstitution(subID) {

  var x = confirm("Are you sure you wish to remove this substitution?");
  if (x)
  {
    opener.removeSubstitution(subID);
    window.close();
  } 
}

function popupRestoreTransferEqv(db_unassign_transfer_id) {

  opener.restoreTransferEqv(db_unassign_transfer_id);
  window.close();
}

function popupRestoreUnassignFromGroup(db_unassign_group_id) {
  opener.restoreUnassignFromGroup(db_unassign_group_id);
  window.close();

}

function popupSetVarHours() {
  var hid = document.getElementById("varHours");  
  var sel = document.getElementById("selHours");  
  hid.value = sel.value;
}


function popupUpdateSelectedCourse(course_id, group_id, semester_num, random_id, advising_term_id) {
  var varHours = document.getElementById("varHours").value;
  opener.updateSelectedCourse(course_id, group_id, semester_num, varHours, random_id, advising_term_id);
  window.close();

}

function toggleDisabledChangeTerm(x,y,termDescription) {
  var t = "";
  t = t + "This course was advised for the " + termDescription + ". ";
  t = t + "It cannot be unselected from here.  Please first change the Currently Advising term to \"" + termDescription + "\"";
  t = t + " by clicking the [change] link near the top of the page. ";

  alert(t);
}

function toggleDisabledCompleted(x,y,type) {
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

function setVar(id, newValue) {
  document.getElementById(id).value = newValue
}


////////////////////////////////////////

function popupWindowNew(url, extraVars) {
  var my_windowx = window.open(url + "&window_mode=popup&current_student_id=" + FlightPath.settings.currentStudentId + "&" + extraVars,
  "courseinfox" + FlightPath.settings.currentStudentId,"toolbar=no,status=2,scrollbars=yes,resizable=yes,width=460,height=375");

  my_windowx.focus();  // make sure the popup window is on top.
  
}

function popupPrintWindow(url) {
  var my_windowx2p = window.open(url + "&window_mode=popup&current_student_id=" + FlightPath.settings.currentStudentId,
  "courseinfoxprint" + FlightPath.settings.currentStudentId,"toolbar=no,status=2,scrollbars=yes,resizable=yes,width=700,height=500");

  my_windowx2p.focus();  // make sure the popup window is on top.

}


function popupHelpWindow(url) {
  var my_windowxhelp2p = window.open(url + "&current_student_id=" + FlightPath.settings.currentStudentId,
  "courseinfoxhelp" + FlightPath.settings.currentStudentId,"toolbar=no,status=2,scrollbars=yes,resizable=yes,width=700,height=500");

  my_windowxhelp2p.focus();  // make sure the popup window is on top.

}



// function popupWindow(action, extraVars) {
//   
  // var my_windowx = window.open("' . $script_filename . '?windowMode=popup&performAction=" + action + "&current_student_id=" + csid + "&" + extraVars,
  // "courseinfox" + csid,"toolbar=no,status=2,scrollbars=yes,resizable=yes,width=460,height=375");
// 
  // my_windowx.focus();  // make sure the popup window is on top.
// 
// }

function popupWindow2(url, extraVars) {
  var my_windowx2 = window.open(url + "&window_mode=popup&current_student_id=" + FlightPath.settings.currentStudentId + "&" + extraVars,
  "courseinfox2" + FlightPath.settings.currentStudentId,"toolbar=no,status=2,scrollbars=yes,resizable=yes,width=460,height=375");

  my_windowx2.focus();  // make sure the popup window is on top.

}




