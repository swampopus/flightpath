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
    