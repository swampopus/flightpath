<?php
/**
 * @file
 * This file is responsible for outputting our content to the web browser.
 * 
 * Available variables (supplied by AdvisingScreen::output_to_browser() method.
 * 
 * $page_content    The primary content of the page.    
 * $page_on_load    If the page performs any javascript onLoad, it goes here.
 * $page_tabs     Contains the HTML to draw the correct tabs at the top of the page.
 * $page_is_popup   Set to either TRUE or FALSE.  If TRUE, do not display the header,
 *          and possibly have different layout parameters.  These are booleans
 *          and not strings.
 * $page_title      The HTML title of the browser window.
 * $page_has_search   Either TRUE or FALSE.  Is a boolean, not a string.  If set to
 *          TRUE, the page will have a search bar at the top.
 * $page_scroll_top   If set, the page will automatically scroll to this position (and
 *          the one below it) on load.
 * $page_hide_report_error     if set to TRUE, the page will not display the link to report
 *              an error.
 * $page_banner_is_link   true or false.  If true, the banner at the top will load FP in a
 *            new window.
 * $page_extra_js_settings    These are variable definitions set by other modules, using the fp_add_js() command.
 * $page_extra_css_files      These are extra CSS files which other modules wish to include, using fp_add_css().
 * $page_extra_js_files       Similar to extra_css files, but for extra javascript files.
 * $page_body_classes         A string containing the CSS classes (space-separated) which should go on the body element.
 * 
 * $page_header   This is the "header content" for the page.  Basically, the logo?  TODO:  do I need this to be a variable?
 * $page_sidebar_left_content      This is the HTML contents that should appear in the left sidebar, if any.
 * 
 * 
 * 
*/
?>
  
<script>
  $(document).ready(function() {
    
    loginPageResizeColumns();    
    $(window).resize(function() {
      loginPageResizeColumns();
    });
  
  });

  function loginPageResizeColumns() {
    var h = window.innerHeight || document.documentElement.clientHeight || document.body.clientHeight;       
    $("#page-login-user-form").css('height', h + 'px');
    $("#page-login-page__wallpaper").css('height', h + 'px');
  }

</script>
    
  
<body class='<?php print $page_body_classes; ?>'>


  <div class='page-login-wrapper'>
    
    <div id='page-login-user-form'>
      <div class='top-banner'>
        <a href='<?php print fp_url("login"); ?>'>
          <img src='<?php print "$theme_location/images/"; ?>fp_banner_default.png' border='0'>
        </a>
      </div>
      
      <h2 class='login-welcome'>Welcome to FlightPath</h2>
      <?php print $page_content; ?>
         
      
    </div>
    
    <div id='page-login-page__wallpaper'>
      &nbsp;
         <!-- <div id='school-logo'><img src='https://www.ulm.edu/_resources/images/ulm-academic-logo-circle.png'></div> -->
    </div>
    
  </div>  
    
 
  
</body>