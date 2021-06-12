
function schoolsConfirmChangeSchool(old_school_id) {
  var x = confirm("Are you sure you wish to change the school?  This might cause problems with users, courses, groups, or degrees that reference the previously selected school value.  Only change if you are sure of what you are doing.  If unsure, click Cancel/No.");
  
  if (!x) {  
    // change back to whatever the orginal school_id was.
    $("#element-schools_school").val(old_school_id);   
  }
  
  
}
