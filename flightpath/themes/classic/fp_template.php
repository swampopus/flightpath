<?php
/**
 * @file
 * This is the primary page template for FlightPath.
 * 
 * Certain variables are set by the AdvisingScreen class, in its output_to_browser() method, which
 * are available here:
 * 
 *
 * $page_content		The actual content of the page which appears in the
 *					center.
 * $page_on_load			If the page performs any javascript onLoad, it goes here.
 * $page_tabs			Contains the HTML to draw the correct tabs at the top of the page.
 * $page_is_popup		Set to either TRUE or FALSE.  If TRUE, do not display the header,
 *					and possibly have different layout parameters.  These are booleans
 *					and not strings.
 * $page_title			The HTML title of the browser window.
 * $page_has_search		Either TRUE or FALSE.  Is a boolean, not a string.  If set to
 *					TRUE, the page will have a search bar at the top.
 * $page_scroll_top		If set, the page will automatically scroll to this position (and
 *					the one below it) on load.
 * $page_hide_report_error     if set to TRUE, the page will not display the link to report
 * 							an error.
 * $page_banner_is_link   true or false.  If true, the banner at the top will load FP in a
 *						new window.
 * $page_extra_js_settings    These are variable definitions set by other modules, using the fp_add_js() command.
 * $page_extra_css_files      These are extra CSS files which other modules wish to include, using fp_add_css().
 * $page_extra_js_files       Similar to extra_css files, but for extra javascript files.
 * $page_body_classes         A string containing the CSS classes (space-separated) which should go on the body element.
 * 
 **/
$theme_location = fp_theme_location();

// If this page is a popup, let's add that information to the body_classes.
if ($page_is_popup) {
  $page_body_classes .= " page-is-popup ";
}

?>
<html>
	<head>	
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
	
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
		 print "<link rel='stylesheet' type='text/css' href='$theme_location/layout.css?$page_css_js_query_string' /> \n";
		 
		 // Load any extra CSS files which addon modules might have added.
		 print $page_extra_css_files;
		 
     // Load the custom.css file for this theme...
     print "<link rel='stylesheet' type='text/css' href='$theme_location/custom.css?$page_css_js_query_string' /> \n";
     
		?>
		<link rel="shortcut icon" href="favicon.ico" type="image/x-icon" />
		
		<title><?php print $page_title; ?></title>
	</head>
	
	
	<body class='<?php print $page_body_classes; ?>'>
	
	
		<table class='top-banner fp-layout-table'>
			<tr>			
				<td class='corner-top-left'></td>
	      <td class='layout-table-top'></td>
				<td class='corner-top-right'></td>
			</tr>
			<tr>			  
			  <td class='layout-table-left'></td>			  
			  <td class='layout-table-content'>
			    <?php
			    
            
            // ***************** Header Content   *****************
            // *****************      *****************
            if ($page_banner_is_link == TRUE) {
              print "<a href='{$GLOBALS["fp_system_settings"]["self_url"]}' target='_blank'>";
            }
            ?><img src='<?php print "$theme_location/images/"; ?>fp_banner_default.png' border='0'><?php
            if ($page_banner_is_link == TRUE) {
              print "</a>";
            }    
            
            
			  ?>
			  </td>			  
			  <td class='layout-table-right'></td>
			</tr>
			<tr>
				<td class='corner-bottom-left'>					
				  <div class='layout-table-spacer_33_36'></div>
				</td>
				<td class='layout-table-bottom'></td>
				<td class='corner-bottom-right'>					
					<div class='layout-table-spacer_33_36'></div>
				</td>
			</tr>			
		</table>	
	
			
    <table class='main-page-content fp-layout-table'>
			
			<tr>
			
			
			<td class='tabs-search-td' colspan="3">
					<table class='tabs-search-table'>
						<tr>
						
						
							<!-- ********** The tabs td ************-->
							<td class='tabs-td'>
								<table class='tabs-table-tabs'>
									<tr>
										<td align="left">

										<?php print $page_tabs; ?>
										
										</td>
									</tr>
								</table>
							</td>										
			
			<!-- The possible search bar td -->
							<td class='search-td'>
							<?php
							// Insert a search bar if there is one.

							if ($page_has_search == TRUE && function_exists("student_search_menu")) {
							  
                print student_search_render_small_search();                                

							}

							?>
              </td>              
						</tr>
					</table>
				</td>
			</tr>
			<tr>
			
			
				<td class='corner-top-left'></td>
				<td class='layout-table-top'></td>
				<td class='corner-top-right'></td>
			</tr>
			<tr>
				<td class='layout-table-left'></td>
				<td class='layout-table-content'>

          <div class='page-content'>
            <?php
                // ***************** Page specific content will be in here *****************    
                print $page_content;
                // ***************** Page specific content was in here     *****************
            ?>
        </div>
            
            				  
				</td>
				
				
				
				<td class='layout-table-right'></td>
			</tr>
			<tr>
				<td class='corner-bottom-left'>
					<div class='layout-table-spacer_33_36'></div>
				</td>
				<td class='layout-table-bottom'></td>
				<td class='corner-bottom-right'>
					<div class='layout-table-spacer_33_36'></div>
				</td>		
			
			
			</tr>			
		</table>	
		
		<?php
		//------------------------------------------------
		// ------- MSG AT BOTTOM -------------------------
		//------------------------------------------------
		?>
		
		
		<div class='fp-bottom-message'>
    <?php
      if ($page_hide_report_error != TRUE) {
        print "<a class='nounderline' href='javascript: popupreportcontact()'>" . t("Contact the FlightPath production team") . "</a>";
      }
    ?>
    <!-- Optional copyright message could go here.
      	<span>&copy; Date, Institution, etc.</span> -->	  
		</div>
		
		

		
	</body>
</html>