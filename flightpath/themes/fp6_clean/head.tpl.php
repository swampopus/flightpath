<?php
/**
 * @file
 * This file is meant to contain all of the common items which should appear in
 * the <head></head> section of the page.
 * 
 * The same variables are available as those found in the page.tpl.php file.
 * 
*/
?>
<head>  
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
  <meta name="viewport" content="width=device-width" />

  <?php // Bring in jQuery and jQuery UI, as well as jQuery UI's css. ?>
  <script src="<?php print base_path() ?>/inc/jquery-1.8.3.min.js" type="text/javascript"></script>
  <script src="<?php print base_path() ?>/inc/jquery-ui-1.9.2.custom.min.js" type="text/javascript"></script>
  <link rel='stylesheet' type='text/css' href='<?php print base_path() ?>/inc/jqueryui-css/ui-lightness/jquery-ui-1.9.2.custom.min.css' />

  <script type='text/javascript'>
  <?php print $page_extra_js_settings; ?>     
  // perform any requested actions on page load...
  $(document).ready(function() { <?php print $page_on_load; ?> });
  </script>
  <?php
   // Add extra JS files.    
   print $page_extra_js_files;
  
   // Load this theme's CSS file(s)
   print "<link rel='stylesheet' type='text/css' href='$theme_location/style.css?$page_css_js_query_string' /> \n";
       
   // Load any extra CSS files which addon modules might have added.
   print $page_extra_css_files;
   
   // Load the custom.css file for this theme...
   print "<link rel='stylesheet' type='text/css' href='$theme_location/custom.css?$page_css_js_query_string' /> \n";
   
  ?>
  <link rel="shortcut icon" href="favicon.ico" type="image/x-icon" />
  
  <title><?php print $page_title; ?></title>
</head>
  