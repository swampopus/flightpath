
$(document).ready(function() {
  
  // On startup, let's init tinymce.
  tinyMCE.init(
      {
         mode                            : "textareas",         
         theme_advanced_buttons1         : "bold,italic,underline," +
                                           "strikethrough,forecolor,backcolor," +
                                           "separator,undo,redo,separator," +
                                           "justifyleft,justifycenter,separator",
         theme_advanced_buttons1_add     : "cut,copy,paste,separator,bullist,numlist,outdent,indent,separator,code,link,unlink",
         theme_advanced_buttons2         : "",
         theme_advanced_buttons3         : "",
         theme_advanced_toolbar_location : "top",
         width                           : "97%",
         theme_advanced_statusbar_location : "",
         content_css                     : FlightPath.settings.tinymceModulePath + "/css/tinymce.css",
         theme_advanced_toolbar_align    : "left",
         extended_valid_elements         : "hr[class|width|size|noshade]," +
                                           "font[face|size|color|style]," +
                                           "span[class|align|style]"
      });
  
  
  
  
});