




function contentDeleteFile(fieldname, fid) {
  var x = confirm("Are you sure you wish to remove this file?");
  if (!x) return false;
  
  // get rid of the fid from "previous upload" value and make the file field visible again  
  var v = $("#element-previous_upload_" + fieldname).val();
  var newV = "";
  var temp = v.split(',');
  for (var t = 0; t < temp.length; t++) {
    if (temp[t] == fid) continue;  // this is the fid we want to remove.    
    newV += temp[t] + ","; // add everything else.
  }
  
  $("#element-previous_upload_" + fieldname).val(newV);
  
  $("#element-wrapper-" + fieldname).removeClass("element-wrapper--hidden");  
  
  // Also hide the markup where we say what the previously uploaded file was.
  $(".markup-element-markup_" + fieldname + " .file-field-line-" + fid).hide();
  
  
}




/**
 * Add another File input, under this one. 
 * @param {Object} fieldname
 */
function contentAddMoreFile(fieldname) {
  
  var fe = "<input type='file' name='" + fieldname + "[]' >";
  
  $('#element-inner-wrapper-attachment').append(fe);
  
  
}