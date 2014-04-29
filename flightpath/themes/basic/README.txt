READEME for Basic Starter theme
===============================

Unlike the "classic" theme, this theme is meant to be very plain and basic,
lacking many of the images which comprise the classic theme's layout.

The idea is for you to use this theme as a starting point for designing other themes.

================
HOW TO CUSTOMIZE
================

1. Begin by copying the "basic" directory to /custom/themes/your_theme_name

2. Rename basic.info to your_theme_name.info.

3. Edit your_theme_name.info and change the values to reflect your new theme name.

4. Visit your admin console and go to System settings.  Change the theme location to:
        custom/themes/your_theme_name

You will customize the theme by manipulating the CSS as much as possible.  You
can edit style.css if you like, but it is advised that all of your edits go in
the custom.css file, to make it easier for you to find them later.

If you are not familiar working with CSS, it is advised that you read up on it.

Also, using the Firebug add-on to Firefox, or the Developer tools in Chrome can greatly
assist in tracking down CSS class names so you know what to edit.


===================
CACHING INFORMATION
===================

Browsers will naturally cache CSS and image files, to make loading pages
within a site faster.  To ensure that the browser loads the most recent changes to your
theme, you should clear your site's cache (http://example.com/flightpath/admin-tools/clear-cache).
- This is also linked on the Main tab when you log in as admin.