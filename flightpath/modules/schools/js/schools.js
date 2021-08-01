
$(document).ready(function() {
  
  schoolsDisableEnableFieldsOnSchoolSettingsForm();
  
  $("#element-school_use_default_school_values").change(function() {
    var ischecked = $(this).is(':checked');
    
    $("#use-default-remember").show();
    
    if (ischecked == true) {
      fp_alert("Note: By setting to use Default school values, all existing values on this page will be ignored and replaced with those from the Default school.<br><br>If you are sure you wish to do this, scroll to the bottom and click <em>Save settings</em>.  Otherwise, uncheck this box.");
    }
    
    schoolsDisableEnableFieldsOnSchoolSettingsForm();
    
  });
});




function schoolsDisableEnableFieldsOnSchoolSettingsForm() {
  var ischecked = $("#element-school_use_default_school_values").is(':checked');
  
  if (ischecked == true) {
    
    // disable fields.
    $("#fp-form-system_school_data_form input").attr('readonly','readonly');
    $("#fp-form-system_school_data_form input").addClass('disable-element');
    
    $("#fp-form-system_school_data_form textarea").attr('readonly','readonly');
    $("#fp-form-system_school_data_form textarea").addClass('disable-element');
    
    $("#fp-form-system_school_data_form select").attr('readonly','readonly');
    $("#fp-form-system_school_data_form select").addClass('disable-element');

    $("#fp-form-system_school_data_form .element-type-checkbox .form-element").attr('readonly','readonly');
    $("#fp-form-system_school_data_form .element-type-checkbox .form-element").addClass('disable-element');

    // Re-enable our "use default values" checkbox and submit button
    $("#fp-form-system_school_data_form #element-wrapper-school_use_default_school_values .form-element").removeAttr('readonly');
    $("#fp-form-system_school_data_form #element-wrapper-school_use_default_school_values .form-element").removeClass('disable-element');
    
    $("#fp-form-system_school_data_form .element-type-submit input").removeAttr('readonly');
    $("#fp-form-system_school_data_form .element-type-submit input").removeClass('disable-element');

    
  } // ischecked = true  
  else {
    // ischecked = false
    // Enable all the fields.
    
    $("#fp-form-system_school_data_form input").removeAttr('readonly');
    $("#fp-form-system_school_data_form input").removeClass('disable-element');
    
    $("#fp-form-system_school_data_form textarea").removeAttr('readonly');
    $("#fp-form-system_school_data_form textarea").removeClass('disable-element');
    
    $("#fp-form-system_school_data_form select").removeAttr('readonly');
    $("#fp-form-system_school_data_form select").removeClass('disable-element');
    
    
  } // ischecked = false
} // schoolsDisableEnableFieldsOnSchoolSettingsForm





function schoolsConfirmChangeSchool(old_school_id) {
  var x = confirm("Are you sure you wish to change the school?  This might cause problems with users, courses, groups, or degrees that reference the previously selected school value.  Only change if you are sure of what you are doing.  If unsure, click Cancel/No.");
  
  if (!x) {  
    // change back to whatever the orginal school_id was.
    $("#element-schools_school").val(old_school_id);   
  }
  
  
}
