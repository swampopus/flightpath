<?php
/**
*This script expects several variables to be set before it is included.
*
* $page_content		The actual content of the page which appears in the
*					center.
* $page_on_load			If the page performs any javascript onLoad, it goes here.
*					Should include the onLoad command.  Ex:
*					$page_on_load = "setVars()";
* $page_on_unload		If the page is supposed to perform something when the user
*					closes it, set it here.
* $page_tabs			Contains the HTML to draw the correct tabs at the top of the page.
* $page_is_popup		Set to either TRUE or FALSE.  If TRUE, do not display the header,
*					and possibly have different layout parameters.  These are booleans
*					and not strings.
* $page_title			The HTML title of the browser window.
* $page_has_search		Either TRUE or FALSE.  Is a boolean, not a string.  If set to
*					TRUE, the page will have a search bar at the top.
* $page_scroll_top		If set, the page will automatically scroll to this position (and
*					the one below it) on load.
* $page_scroll_left		The page will scroll to this position on load.  pageScrollTop
*					must also be set for this to happen.
* $page_scroll_to		If set, the page will automatically scroll to the named anchor
* 					specified in this variable.  For example, if it is set to "bob", then
* 					on load, the page will scroll to where that anchor is on the page.
* $pageHelpTopic	If the page has a help entry associated with it, enter the topic
* 					in this variable.  It will cause the page to present a help icon.
* $page_hide_report_error     if set to TRUE, the page will not display the link to report
* 							an error.
* $page_banner_is_link   true or false.  If true, the banner at the top will load FP in a
*						new window.
* 
**/
$theme_location = fp_theme_location();
?>  

<html>
	<head>	
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
	
		<script src="<?php print base_path() ?>/inc/jquery-1.8.3.min.js" type="text/javascript"></script>
		<script src="<?php print base_path() ?>/inc/jquery-ui-1.9.2.custom.min.js" type="text/javascript"></script>
	  <link rel='stylesheet' type='text/css' href='<?php print base_path() ?>/inc/jqueryui-css/ui-lightness/jquery-ui-1.9.2.custom.min.css'>
		
		<script type='text/javascript'>
    <?php print $page_extra_js_settings; ?>     
    // perform any requested actions on page load...
		$(document).ready(function() { <?php print $page_on_load; ?> });
		</script>
		
		
		
		<?php
		 // Add extra JS files.    
     print $page_extra_js_files;
		
		 // Load this theme's CSS file(s)
		 print "<link rel='stylesheet' type='text/css' href='$theme_location/style.css'>";
		 
		 // Load any extra CSS files which addon modules might have added.
		 print $page_extra_css_files
		 
		?>
		<link rel="shortcut icon" href="favicon.ico" type="image/x-icon" />
		
		<title><?php print $page_title; ?></title>
	</head>
	
	
	<body class='<?php print $page_body_classes; ?>'>
	
	
	<?php


	// If the page is a popup, do not display header..
	if ($page_is_popup != TRUE)
	{

		print "<table width='800' cellpadding='0' cellspacing='0' bgcolor='white' align='center'>";

	?>
	
	
			
			<tr>
				
				
							
			
				<td align="left" width="28" valign="baseline" bgcolor="#ECECDB" style="background: url('<?php print "$theme_location/images/"; ?>tl.gif') #ECECDB top left no-repeat;" height="17">
					<!--<img src="tl.gif" width="28" height="17" style="margin: 0px; padding: 0px;">-->
				</td>
	<td valign="baseline" style="background: url('<?php print "$theme_location/images/"; ?>top.gif') #ECECDB repeat-x; width: 800px;"></td>
				<td align="right" width="33" valign="baseline" bgcolor="#ECECDB" style="background: url('<?php print "$theme_location/images/"; ?>tr.gif') #ECECDB top right no-repeat;">
					<!--<img src="tr.gif" width="33" height="17" style="margin: 0px; padding: 0px;">-->
				</td>
			</tr>
			<tr>
				<td align="center" colspan="3">
					<div style="background: url('<?php print "$theme_location/images/"; ?>left.gif') repeat-y top left;">
					<div style="background: url('<?php print "$theme_location/images/"; ?>right.gif') repeat-y top right;">
						<table width="90%" cellpadding="0" cellpadding="0" >
							<tr>
								<td align="left">
                                	<?php
                                	// ***************** Header Content   *****************
                                	// *****************      *****************
                                	if ($page_banner_is_link == true)
                                	{
                                		print "<a href='{$GLOBALS["fp_system_settings"]["self_url"]}' target='_blank'>";
                                	}
		                            ?>
		                            <img src='<?php print "$theme_location/images/"; ?>fp_banner_default.png' border='0'>
	                            <?php
	                            if ($page_banner_is_link == true)
	                            {
	                            	print "</a>";
	                            }

	                            ?>
								</td>
							</tr>
						</table>
					</div>
					</div>
				</td>
			</tr>
			<tr>
				<td align="left" valign="bottom" style="background: url('<?php print "$theme_location/images/"; ?>bl.gif') #ECECDB no-repeat;">
					<img src="<?php print "$theme_location/images/"; ?>spacer.gif" width="33" height="36">
				</td>
				<td valign="bottom" style="background: url('<?php print "$theme_location/images/"; ?>bottom.gif') #ECECDB repeat-x;"></td>
				<td align="right" valign="bottom" style="background: url('<?php print "$theme_location/images/"; ?>br.gif') #ECECDB no-repeat;">
					<img src="<?php print "$theme_location/images/"; ?>spacer.gif" width="33" height="36">
				</td>
			</tr>			
		</table>	
		<br>

	<?php

	} // close if statement (if isPopup)

	?>
	
	
	
	
	
			
	<?php

	// Set the page width based on whether or not
	// we're in a popup window.
	if ($page_is_popup == TRUE)
	{
		print "<table width='100%' cellpadding='0' cellspacing='0' bgcolor='White' align='center'>";
	} else {
		print "<table width='800' cellpadding='0' cellspacing='0' bgcolor='White' align='center'>";
	}

			?>
			<tr>
			
			
			<td colspan="3">
					<table width="100%" cellpadding="0" cellspacing="0" align="left" bgcolor="#ECECDB">
						<tr>
						
						
							<!-- ********** The tabs td ************-->
							<td>
								<table width="100%" cellpadding="0" cellspacing="0" align="left"">
									<tr>
										<td align="left">

										<?php print $page_tabs; ?>
										
										</td>
									</tr>
								</table>
							</td>										
			
			<!-- The possible search bar td -->
							<td align="right" width="100%" valign='bottom'>
							<?php
							// Insert a search bar if there is one.

							if ($page_has_search == TRUE && function_exists("student_search_menu")) {
							  
                print student_search_render_small_search();                                

							}

							?>
              </td>
              <td align='right' style='padding-left: 30px'>							
							
							</td>
						</tr>
					</table>
				</td>
			</tr>
			<tr>
			
			
				<td align="left" valign="baseline" bgcolor="#ECECDB" style="background: url('<?php print "$theme_location/images/"; ?>tl.gif') #ECECDB top left no-repeat;" height="17">
					<!--<img src="tl.gif" width="28" height="17" style="margin: 0px; padding: 0px;">-->
				</td>
				<td valign="baseline" style="background: url('<?php print "$theme_location/images/"; ?>top.gif') #ECECDB repeat-x; width: 800px;"></td>
				<td align="right" valign="baseline" bgcolor="#ECECDB" style="background: url('<?php print "$theme_location/images/"; ?>tr.gif') #ECECDB top right no-repeat;">
					<!--<img src="tr.gif" width="33" height="17" style="margin: 0px; padding: 0px;">-->
				</td>
			</tr>
			<tr>
				<td align="center" colspan="3">
					<div style="background: url('<?php print "$theme_location/images/"; ?>left.gif') repeat-y top left;">
					<div style="background: url('<?php print "$theme_location/images/"; ?>right.gif') repeat-y top right;">
						<table width="90%" 
							<?php
							//------------------------------------------------------
							// Force height if page is popup...
							//------------------------------------------------------
							if ($page_is_popup)
							{
								print " height='250' ";
							}

							?>
							   cellpadding="0" cellpadding="0">
							<tr>
								<td align="left" valign='top'>								
								<div class='page-content'>
								<?php
                                	// ***************** Page specific content will be in here *****************

                                	print $page_content;


                                	// ***************** Page specific content was in here     *****************
		                            ?>
		          </div>
								</td>
								<td valign='top' align='right'>
								</td>
							</tr>
						</table>
					</div>
					</div>
				</td>
			</tr>
			<tr>
				<td align="left" valign="bottom" style="background: url('<?php print "$theme_location/images/"; ?>bl.gif') #ECECDB no-repeat;">
					<img src="<?php print "$theme_location/images/"; ?>spacer.gif" width="33" height="36">
				</td>
				<td valign="bottom" style="background: url('<?php print "$theme_location/images/"; ?>bottom.gif') #ECECDB repeat-x;" width="100%"></td>
				<td align="right" valign="bottom" style="background: url('<?php print "$theme_location/images/"; ?>br.gif') #ECECDB no-repeat;">
					<img src="<?php print "$theme_location/images/"; ?>spacer.gif" width="33" height="36">
				</td>		
			
			
			</tr>			
		</table>	
		
		<?php
		//------------------------------------------------
		// ------- MSG AT BOTTOM -------------------------
		//------------------------------------------------
		?>
		
		<table width="<?php

		// Set the page width based on whether or not
		// we're in a popup window.
		if ($page_is_popup == TRUE)
		{
			print "100%";
		} else {
			print "800";
		}

			?>" cellpadding="0" cellspacing="0" align="center">		
		<td width='15'>&nbsp; </td>
		<td style='font-size: 8pt;'>
		<?php
		if ($page_hide_report_error != TRUE) {
			print "<a class='nounderline' href='javascript: popupreportcontact()'>" . t("Contact the FlightPath production team") . "</a>";
		}
		?>
		</td>
		</table>
		
		<?php
		if ($page_is_popup != true)
		{
		  // TODO:  Allow for some kind of copyright notice here?  Probably in a setting
			//print "<div align='center' style='font-size: 8pt;'>&copy; <a href='http://www.ulm.edu'>University of Louisiana at Monroe</a>, all rights reserved</div>";
		}
		?>
		
	</body>
</html>