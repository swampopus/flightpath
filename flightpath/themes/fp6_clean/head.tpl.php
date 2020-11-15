<!DOCTYPE HTML>
<?php
/**
 * @file
 * This file is meant to contain all of the common items which should appear in
 * the <head></head> section of the page.
 * 
 * The same variables are available as those found in the page.tpl.php file.
 * 
*/

//print $page_on_load;
if ($page_is_popup) {
  $page_on_load .= '
    //parent.fpNudgeDialog();
  ';
}

?>
<head>  
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />  
  <meta name="viewport" content="width=device-width" />

   
  <?php // Bring in jQuery and jQuery UI, as well as jQuery UI's css. ?>
  
  <script src="<?php print base_path() ?>/inc/jquery-3.5.1.min.js" type="text/javascript"></script>
  <script src="<?php print base_path() ?>/inc/jquery-ui-1.12.1.custom/jquery-ui.min.js" type="text/javascript"></script>  
  <script src="<?php print base_path() ?>/inc/modal-alert/js/daypilot-modal-2.9.js" type="text/javascript"></script>
  
  <script type='text/javascript'>
  <?php print $page_extra_js_settings; ?>     
    // perform any requested actions on page load...
    $(document).ready(function() { <?php print $page_on_load; ?>; });  
  </script>
  
  <link rel='stylesheet' type='text/css' href='<?php print base_path() ?>/inc/jquery-ui-1.12.1.custom/jquery-ui.min.css' />
  
  
  <?php
   // Add extra JS files.     
   print $page_extra_js_files;
  
   // Load this theme's CSS file(s)
   print "<link rel='stylesheet' type='text/css' href='$theme_location/style.css?$page_css_js_query_string' /> \n";
   print "<link rel='stylesheet' type='text/css' href='$theme_location/media.css?$page_css_js_query_string' /> \n";
  
   // Load any extra CSS files which addon modules might have added.
   print $page_extra_css_files;
    
   // Load the custom.css file for this theme...
   print "<link rel='stylesheet' type='text/css' href='$theme_location/custom.css?$page_css_js_query_string' /> \n";
   
  ?>
  
  <link rel="stylesheet" href="<?php print $theme_location ?>/font-awesome-4.7.0/css/font-awesome.min.css">
    
  <title><?php print $page_title; ?> | <?php print $system_name; ?></title>
</head>
  