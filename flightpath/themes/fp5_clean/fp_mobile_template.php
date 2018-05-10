<?php
/*
	This is very similar to the fp_template, except this is formatted
	to be used with content which is supposed to get printed out.
*/

$theme_location = fp_theme_location();

print "
<meta name='viewport' id='view' content='width=350;'/>
<meta name='format-detection' content='telephone=no'>                
<link rel='stylesheet' type='text/css' href='$theme_location/style.css?$page_css_js_query_string' />
<link rel='stylesheet' type='text/css' href='$theme_location/layout.css?$page_css_js_query_string' />
<link rel='stylesheet' type='text/css' href='$theme_location/mobile.css?$page_css_js_query_string' />";

// Load any extra CSS files which addon modules might have added.
print $page_extra_css_files;

// Load our custom.css last, so we can override whatever needs to be overwritten
print "<link rel='stylesheet' type='text/css' href='$theme_location/custom.css?$page_css_js_query_string' />";

print "<title>$page_title</title>";

?>

		<script src="<?php print base_path() ?>/inc/jquery-1.8.3.min.js" type="text/javascript"></script>
		<script src="<?php print base_path() ?>/inc/jquery-ui-1.9.2.custom.min.js" type="text/javascript"></script>
	  <link rel='stylesheet' type='text/css' href='<?php print base_path() ?>/inc/jqueryui-css/ui-lightness/jquery-ui-1.9.2.custom.min.css'>


<script type='text/javascript'>
    <?php print $page_extra_js_settings; ?>     
    // perform any requested actions on page load...
		$(document).ready(function() { <?php print $page_on_load; ?> });
</script>

<?php print $page_extra_js_files; ?>

<body class='fp-mobile-theme <?php print $page_body_classes; ?>'>
<!-- LOGO -->
<img src='<?php print $theme_location; ?>/images/fp_banner_default_226px.png' alt='FlightPath' title='FlightPath'>

<table border='0' width='100%' cellspacing='0' cellpadding='0'>

<?php
if ($page_tabs) {
  print "
  <tr>
   <td>
			<table width='100%' cellpadding='0' cellspacing='0' align='left'>
				<tr>
					<td align='left'>
					$page_tabs										
					</td>
				</tr>
			</table>
  </td>
  </tr>
  ";
  
} 
?>
<tr>
<td valign='top'>
<!-- PAGE CONTENT -->
<?php print $page_content; ?>
</td> 
</tr>
</table>

<!--
<div class='fpcopy-notice'>
&copy; <a href='http://www.ulm.edu' class='nounderline'>University of Louisiana at Monroe</a>
</div>
-->

</body>