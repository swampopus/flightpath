<?php
/**
*This script expects several variables to be set before it is included.
*
* $pageContent		The actual content of the page which appears in the
*					center.
* $pageOnLoad			If the page performs any javascript onLoad, it goes here.
*					Should include the onLoad command.  Ex:
*					$pageOnLoad = "setVars()";
* $pageOnUnload		If the page is supposed to perform something when the user
*					closes it, set it here.
* $pageTabs			Contains the HTML to draw the correct tabs at the top of the page.
* $pageIsPopup		Set to either TRUE or FALSE.  If TRUE, do not display the header,
*					and possibly have different layout parameters.  These are booleans
*					and not strings.
* $pageTitle			The HTML title of the browser window.
* $pageHasSearch		Either TRUE or FALSE.  Is a boolean, not a string.  If set to
*					TRUE, the page will have a search bar at the top.
* $pageScrollTop		If set, the page will automatically scroll to this position (and
*					the one below it) on load.
* $pageScrollLeft		The page will scroll to this position on load.  pageScrollTop
*					must also be set for this to happen.
* $pageScrollTo		If set, the page will automatically scroll to the named anchor
* 					specified in this variable.  For example, if it is set to "bob", then
* 					on load, the page will scroll to where that anchor is on the page.
* $pageHelpTopic	If the page has a help entry associated with it, enter the topic
* 					in this variable.  It will cause the page to present a help icon.
* $pageHideReportError     if set to TRUE, the page will not display the link to report
* 							an error.
* $pageBannerIsLink   true or false.  If true, the banner at the top will load FP in a
*						new window.
* 
**/
$themeLocation = $GLOBALS["fpSystemSettings"]["baseURL"] . "/" . $GLOBALS["fpSystemSettings"]["theme"];
?> 
<html>
	<head>
	
	
		<script src="<?php print $GLOBALS["fpSystemSettings"]["basePath"]; ?>/inc/jquery-1.3.2.min.js" type="text/javascript"></script>
		
		<script type='text/javascript'>

		function defaultOnLoad()
		{
			<?php

			print $pageOnLoad;
			// If the page had a scrollTo set, we should also
			// perform that here...
			if ($pageScrollTo != "")
			{
				print "location.href = \"#$pageScrollTo\"; \n";
			}

			?>

		}


		function popuphelp(topic)
		{ // open a popup help window...
			help_window = window.open("./help.php?topic=" + topic + "",
			"helpwindow","toolbar=no,status=2,scrollbars=yes,resizable=yes,width=650,height=500");

			help_window.focus();  // make sure the popup window is on top.


		}

		function popupreportcontact()
		{
			err_window = window.open("./popup_report_contact.php",
			"errwindow","toolbar=no,status=2,scrollbars=yes,resizable=yes,width=500,height=400");

			err_window.focus();  // make sure the popup window is on top.

		}


		function doSearch()
		{
			//document.getElementById("da_search_for").value = document.getElementById("search_bar_value").value;
			//document.getElementById("da_advisee_action").value = "search";
			//document.getElementById("da_from_search_button").value = "true";

			//document.mainform.action = "studentsearch.php";
			//document.mainform.submit();

			window.location="studentsearch.php?didSearch=true&searchFor=" + document.getElementById("search_bar_value").value;

			return true;
		}

		function searchKeyPress(e)
		{
			// If they typed an ENTER key, then call doSearch...
			if(e){
				e = e
			} else {
				e = window.event
			}

			if(e.which){
				var keycode = e.which
			} else {
				var keycode = e.keyCode
			}

			if(keycode == 13) {
				doSearch();
			}
		}

		function showUpdate(boolShowLoad)
		{
			var scrollTop = document.body.scrollTop;
			var updateMsg = document.getElementById("updateMsg");
			if (boolShowLoad == true)
			{
				updateMsg = document.getElementById("loadMsg");
			}
			var w = document.body.clientWidth;
			//var h = document.body.clientHeight;
			//var t = scrollTop + (h/2);
			var t = scrollTop;
			updateMsg.style.left = "" + ((w/2) - 120) + "px";
			updateMsg.style.top = "" + t + "px";

			updateMsg.style.position = "absolute";  // must use absolute for ie.
			updateMsg.style.display = "";
		}


		</script>
		
		
		
		<?php
		 // Load this theme's CSS file(s)
		 print "<link rel='stylesheet' type='text/css' href='$themeLocation/style.css'>";
		 
		 // Load any extra CSS files which addon modules might have added.
		 if (is_array($pageExtraCssFiles) && count($pageExtraCssFiles) > 0) {
		   foreach ($pageExtraCssFiles as $cssFileName) {
		     print "<link rel='stylesheet' type='text/css' href='$cssFileName'>";
		   }
		 }
		 
		?>
		<link rel="shortcut icon" href="favicon.ico" type="image/x-icon" />
		
		<title><?php 
		if ($pageTitle == "")
		{ // By default, page title is this...
			$pageTitle = $GLOBALS["fpSystemSettings"]["schoolInitials"] . " FlightPath";
		}
		print $pageTitle;

		?></title>
	</head>
	
	<?php
	$scroll = "";
	if (trim($pageScrollTop != ""))
	{
		$pageScrollLeft = 0;
		$scroll = " scrollTo($pageScrollLeft, $pageScrollTop);";
	}
	$onclose = "";
	if (trim($pageOnUnload != ""))
	{
		$onclose = "onUnload='$pageOnUnload'";
	}


	print "<body onLoad='defaultOnLoad(); $scroll' $onclose>";

	?>
	
	
	
	<?php


	// If the page is a popup, do not display header..
	if ($pageIsPopup != TRUE)
	{

		print "<table width='800' cellpadding='0' cellspacing='0' bgcolor='white' align='center'>";

	?>
	
	
			
			<tr>
				
				
							
			
				<td align="left" width="28" valign="baseline" bgcolor="#ECECDB" style="background: url('<?php print "$themeLocation/images/"; ?>tl.gif') #ECECDB top left no-repeat;" height="17">
					<!--<img src="tl.gif" width="28" height="17" style="margin: 0px; padding: 0px;">-->
				</td>
	<td valign="baseline" style="background: url('<?php print "$themeLocation/images/"; ?>top.gif') #ECECDB repeat-x; width: 800px;"></td>
				<td align="right" width="33" valign="baseline" bgcolor="#ECECDB" style="background: url('<?php print "$themeLocation/images/"; ?>tr.gif') #ECECDB top right no-repeat;">
					<!--<img src="tr.gif" width="33" height="17" style="margin: 0px; padding: 0px;">-->
				</td>
			</tr>
			<tr>
				<td align="center" colspan="3">
					<div style="background: url('<?php print "$themeLocation/images/"; ?>left.gif') repeat-y top left;">
					<div style="background: url('<?php print "$themeLocation/images/"; ?>right.gif') repeat-y top right;">
						<table width="90%" cellpadding="0" cellpadding="0" >
							<tr>
								<td align="left">
                                	<?php
                                	// ***************** Header Content   *****************
                                	// *****************      *****************
                                	if ($pageBannerIsLink == true)
                                	{
                                		print "<a href='{$GLOBALS["fpSystemSettings"]["selfURL"]}' target='_blank'>";
                                	}
		                            ?>
		                            <img src='<?php print "$themeLocation/images/"; ?>fp_banner_default.png' border='0'>
	                            <?php
	                            if ($pageBannerIsLink == true)
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
				<td align="left" valign="bottom" style="background: url('<?php print "$themeLocation/images/"; ?>bl.gif') #ECECDB no-repeat;">
					<img src="<?php print "$themeLocation/images/"; ?>spacer.gif" width="33" height="36">
				</td>
				<td valign="bottom" style="background: url('<?php print "$themeLocation/images/"; ?>bottom.gif') #ECECDB repeat-x;"></td>
				<td align="right" valign="bottom" style="background: url('<?php print "$themeLocation/images/"; ?>br.gif') #ECECDB no-repeat;">
					<img src="<?php print "$themeLocation/images/"; ?>spacer.gif" width="33" height="36">
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
	if ($pageIsPopup == TRUE)
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

										<?php print $pageTabs; ?>
										
										</td>
									</tr>
								</table>
							</td>										
			
			<!-- The possible search bar td -->
							<td align="right" width="100%" valign='bottom'>
							<?php
							// Insert a search bar if there is one.

							if ($pageHasSearch == TRUE )
							{
								print "<div style='padding-bottom: 2px;'>
										 <input type='text' class='smallinput' size='30' name='search' id='search_bar_value' 
										 	value='Search by name or CWID.' 
										 	onFocus='document.getElementById(\"search_bar_value\").focus(); document.getElementById(\"search_bar_value\").select();' onKeyPress ='searchKeyPress(event)'>
										 <input type='button' name='submit' value='=>' class='smallinput' onClick='doSearch()'>
										</div>
									</td>
									<td align='right' style='padding-left: 30px'>
									
								";

							}

							if ($pageBannerIsLink == true)
							{
								print "<table cellpadding='0' cellspacing='0'
										style='padding: 3px;
										 
										border: 1px solid black;
										background-color: white;' 
										class='tenpt'><td>
										 <a href='{$GLOBALS["fpSystemSettings"]["selfURL"]}' target='_blank' class='nounderline'>
										 <img src='$themeLocation/images/popup.gif' border='0'>
										 Click here to launch FlightPath!</a>
										</td></table>
									</td>
									<td align='right' style='padding-left: 30px'>
									
								";								
							}


							?>
							</td>
						</tr>
					</table>
				</td>
			</tr>
			<tr>
			
			
				<td align="left" valign="baseline" bgcolor="#ECECDB" style="background: url('<?php print "$themeLocation/images/"; ?>tl.gif') #ECECDB top left no-repeat;" height="17">
					<!--<img src="tl.gif" width="28" height="17" style="margin: 0px; padding: 0px;">-->
				</td>
				<td valign="baseline" style="background: url('<?php print "$themeLocation/images/"; ?>top.gif') #ECECDB repeat-x; width: 800px;"></td>
				<td align="right" valign="baseline" bgcolor="#ECECDB" style="background: url('<?php print "$themeLocation/images/"; ?>tr.gif') #ECECDB top right no-repeat;">
					<!--<img src="tr.gif" width="33" height="17" style="margin: 0px; padding: 0px;">-->
				</td>
			</tr>
			<tr>
				<td align="center" colspan="3">
					<div style="background: url('<?php print "$themeLocation/images/"; ?>left.gif') repeat-y top left;">
					<div style="background: url('<?php print "$themeLocation/images/"; ?>right.gif') repeat-y top right;">
						<table width="90%" 
							<?php
							//------------------------------------------------------
							// Force height if page is popup...
							//------------------------------------------------------
							if ($pageIsPopup)
							{
								print " height='250' ";
							}

							?>
							   cellpadding="0" cellpadding="0">
							<tr>
								<td align="left" valign='top'>
								<div id='updateMsg' class='updateMsg' style='display: none;'>Updating...</div>
								<div id='loadMsg' class='updateMsg' style='display: none;'>Loading...</div>
								<div class='page-content'>
								<?php
                                	// ***************** Page specific content will be in here *****************

                                	print($pageContent);


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
				<td align="left" valign="bottom" style="background: url('<?php print "$themeLocation/images/"; ?>bl.gif') #ECECDB no-repeat;">
					<img src="<?php print "$themeLocation/images/"; ?>spacer.gif" width="33" height="36">
				</td>
				<td valign="bottom" style="background: url('<?php print "$themeLocation/images/"; ?>bottom.gif') #ECECDB repeat-x;" width="100%"></td>
				<td align="right" valign="bottom" style="background: url('<?php print "$themeLocation/images/"; ?>br.gif') #ECECDB no-repeat;">
					<img src="<?php print "$themeLocation/images/"; ?>spacer.gif" width="33" height="36">
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
		if ($pageIsPopup == TRUE)
		{
			print "100%";
		} else {
			print "800";
		}

			?>" cellpadding="0" cellspacing="0" align="center">		
		<td width='15'>&nbsp; </td>
		<td style='font-size: 8pt;'>
		<?php
		if ($pageHideReportError != TRUE)
		{
			print "<a class='nounderline' href='javascript: popupreportcontact()'>Contact the FlightPath production team</a>";
		}
		?>
		</td>
		</table>
		
		<?php
		if ($pageIsPopup != true)
		{
			print "<div align='center' style='font-size: 8pt;'>&copy; <a href='http://www.ulm.edu'>University of Louisiana at Monroe</a>, all rights reserved</div>";
		}
		?>
		
	</body>
</html>