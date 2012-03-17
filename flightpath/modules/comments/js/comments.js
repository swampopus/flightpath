/*
 * Javascript file for comments module
 */


function deleteComment(id) {
  var x = confirm("Are you sure you wish to delete this comment?\nThis action cannot be undone.");
  if (x) {
    window.location=FlightPath.settings.basePath + "/comments/delete-comment&current_student_id=" + FlightPath.settings.currentStudentId + "&comment_id=" + id;
  }
}