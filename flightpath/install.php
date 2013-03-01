<?php
/**
 * @file
 * This is the initial installation file for FlightPath.
 * 
 * This script will handle the initial installation of FlightPath, which
 * entails creating its database tables and settings.php file.
 */

// Set the PHP error reporting level for FlightPath.  In this case,
// only show us errors and warnings. (Hide "notice" and "strict" messages)
error_reporting(E_ERROR | E_WARNING);
 
session_start();

header("Cache-control: private");

// Load our bootstrap (skipping over loads we don't need)
$skip_flightpath_settings = TRUE;
$skip_flightpath_modules = TRUE;
require("bootstrap.inc");

// Load needed modules 
require_once("modules/system/system.module"); 


// Check here to see if FlightPath has already been installed.
// We will do this by simply looking for the settings.php file.
if (file_exists("custom/settings.php")) {
  die("FlightPath has already been installed.  If you wish to re-install FlightPath,
        DELETE the custom/settings.php file, and drop all of the tables in FlightPath's
        database.");
}

/*
 * To begin setting up FlightPath, the user must have completed
 * two other steps-- select language, and pass the requirements
 * check.
 */

$lang = $_REQUEST["lang"];

if ($lang == "") {
  install_display_lang_selection();
  die;
}

// If we made it here, the language must have been set.  So,
// now check the requirements, and display the results if there
// are any.
if ($req_array = install_check_requirements()) {
  install_display_requirements($req_array);
  die;
}

if ($_REQUEST["perform_action"] != "install") {
  // If we made it this far, it means we have no unfulfilled requirements.
  // Let's go ahead and ask the user for their database information.
  install_display_db_form();
  die;
}
else {
  // We ARE trying to install.  Let's give it a go!
  install_perform_install();
  die;
}



die;


/**
 * Actually performs the installation of FlightPath
 */
function install_perform_install() {
  global $user;
  $user->id = 1;  // set to admin during install
  
  $db_name = trim($_POST["db_name"]);
  $db_host = trim($_POST["db_host"]);
  $db_port = trim($_POST["db_port"]);
  $db_user = trim($_POST["db_user"]);
  $db_pass = trim($_POST["db_pass"]);
  
  $admin_pass = trim($_POST["admin_pass"]);
  $admin_pass2 = trim($_POST["admin_pass2"]);
  $admin_name = trim($_POST["admin_name"]);

  if (strlen($admin_name) < 3) {
    return install_display_db_form("<font color='red'>" . st("Please select another
                                                            username for Admin (ex: admin)
                                                            which is at least 3 characters long.") . "</font>");    
  }  

  if (strlen($admin_pass) < 5) {
    return install_display_db_form("<font color='red'>" . st("Admin password must be at least 5 characters long.") . "</font>");    
  }  
  
  if ($admin_pass != $admin_pass2) {
    return install_display_db_form("<font color='red'>" . st("You must enter the same Admin password for both the
                                                                'Admin Password' field and the 'Re-enter Password'
                                                                field.") . "</font>");    
  }  
  

  // Place into settings so our installation procedures will work.
  $GLOBALS["fp_system_settings"]["db_host"] = $db_host . ':' . $db_port;
  $GLOBALS["fp_system_settings"]["db_user"] = $db_user;
  $GLOBALS["fp_system_settings"]["db_pass"] = $db_pass;
  $GLOBALS["fp_system_settings"]["db_name"] = $db_name;
  

  // Make sure admin information is OK.
  



  // We will attempt to connect to this database.  If we have any problems, we will go back to
  // the form and inform the user.
  if (!@mysql_connect ($db_host . ':' . $db_port, $db_user, $db_pass)) {
    return install_display_db_form("<font color='red'>" . st("Could not connect.  Please check that you have
                                    created the database already, and given the user all of the permissions
                                    (except Grant).  Then, make sure you typed the username and
                                    password correctly.") . "</font>");
  }
  if (!@mysql_select_db ($db_name)) {
    return install_display_db_form("<font color='red'>" . st("Could not connect to the database name you specified.  
                                    Please check that you have
                                    created the database already, and given the user all of the permissions
                                    (except Grant).  Possibly check that the database name is correct.") . "</font>");    
  }
  
  
  ///////////////////////////////
  // If we have made it here, then we have been provided valid database credentials.  
  // Let's write out our settings.php file.
  $settings_template = trim(install_get_settings_file_template());
  // Add in our replacements
  $settings_template = str_replace("%DB_HOST%", $db_host, $settings_template);
  $settings_template = str_replace("%DB_PORT%", $db_port, $settings_template);
  $settings_template = str_replace("%DB_NAME%", $db_name, $settings_template);
  $settings_template = str_replace("%DB_USER%", $db_user, $settings_template);
  $settings_template = str_replace("%DB_PASS%", $db_pass, $settings_template);
  $settings_template = str_replace("%CRON_SECURITY_TOKEN%", md5(time()), $settings_template);
  
  // Attempt to figure out the file_system_path based on __FILE__
  $file_system_path = str_replace("install.php", "", __FILE__);
  // Convert \ to / in the file system path.
  $file_system_path = str_replace("\\", "/", $file_system_path);
  // Get rid of the last character, which should be a / at this point.
  $file_system_path = substr($file_system_path, 0, -1);
  
  $settings_template = str_replace("%FILE_SYSTEM_PATH%", $file_system_path, $settings_template);
  
  // Attempt to figure out the base URL.
  $protocol = strpos(strtolower($_SERVER['SERVER_PROTOCOL']),'https') === FALSE ? 'http' : 'https';
  $host = $_SERVER['HTTP_HOST'];
  $script = $_SERVER['SCRIPT_NAME'];
  $base_url = $protocol . "://" . $host . $script;
  $base_url = str_replace("/install.php", "", $base_url);
    
  $settings_template = str_replace("%BASE_URL%", $base_url, $settings_template);    
    
  // Figure out the base_path and add it in.
  $base_path = str_replace($protocol . "://" . $host, "", $base_url);
  
  $settings_template = str_replace("%BASE_PATH%", $base_path, $settings_template);
    
  // Okay, we have completed all the changes to the settings template string, we can
  // write it out to a file now.
  if (!file_put_contents("custom/settings.php", $settings_template)) {
    die("There was an error trying to write out the /custom/settings.php file.  Please
         make sure the /custom directory is writable to the webserver.");
  }
    
  ///////////////////////////////////
  // Okay, we have just written out our settings.php file.
  // We now need to install our database.  We will do this by
  // running the system module's hook_install, as it contains all
  // of our various tables needed to run FlightPath.
  include_once("modules/system/system.install");
  
  $GLOBALS["fp_die_mysql_errors"] = TRUE;
  // call system_install() to perform our numerous DB table creations.
  system_install();
     
  // With db tables created, let's include our settings file so we can get some
  // important GLOBAL variables set up.
  include("custom/settings.php");
  // Re-establish DatabaseHandler object connection since we just re-loaded the settings file.
  $temp_db = new DatabaseHandler();
    
  // Add the admin user to the newly-created users table and the "faculty" table.
  db_query("INSERT INTO users (user_id, user_name, cwid, password, is_faculty, f_name, l_name)
            VALUES ('1', '?', '1', '?', '1', 'Admin', 'User') ", $admin_name, md5($admin_pass));

  db_query("INSERT INTO faculty (cwid) VALUES ('1') ");
            
  // Having made it here, we now need to call system_enable,
  // which will in turn enable all of the other modules which
  // we will need to have, as well as other database changes.
  system_enable();
  
  // Now that we have enabled all of the modules (and made other database changes)
  // let's re-include the bootstrap file, which will re-init our GLOBAL settings,
  // as well as load all of our new modules.
  $skip_flightpath_settings = FALSE;
  $skip_flightpath_modules = FALSE;
  include("bootstrap.inc");
  // Re-establish DatabaseHandler object connection since we just re-loaded the settings file.
  $temp_db = new DatabaseHandler();
  
  /////////////////////////  

  // Now, we need to clear our caches and re-build the menu router.
  fp_clear_cache();
  
  // wipe out the SESSION to remove any extraneous messages.
  session_destroy();
  
  // Okay, now we are done!
  // let's re-direct to a new page.
  fp_goto("install-finished");
}


/**
 * Returns a template for a new settings file.
 * 
 * The only role of this function is to provide a settings
 * template, with replacement patterns which we will use to create
 * a new settings.php file.
 */
function install_get_settings_file_template() {
  return '

<?php
/**
 * @file
 * The settings file for FlightPath, containing database and other settings.
 *
 * Once you have made changes to this file, it would be best to change
 * the permissions to "read-only" to prevent unauthorized users
 * from altering it.
 */
 
// Set the PHP error reporting level for FlightPath.  In this case,
// only show us errors and warnings. (Hide "notice" and "strict" messages)
error_reporting(E_ERROR | E_WARNING);
 
// Set the PHP max time limit which any one page is allowed to take up while
// running.  The default is 30 seconds.  Change this value (or remove it)
// as needed.
set_time_limit(300);  // 300 seconds = 5 minutes.

 
/**
 * All system settings will be placed (at the end of this script)
 * into a $GLOBALS variable, but for now will be placed into an
 * array.
 */
 
$system_settings = array();

////////////////////////////////////
// !!!  *** IMPORTANT !!!  ***    //
////////////////////////////////////
// If this variable is set to TRUE, then anyone who attempts to log in
// will have full, admin access.
// Only set this to TRUE when you are first installing FlightPath.
// Otherwise, leave it set to FALSE!
$system_settings["GRANT_FULL_ACCESS"] = FALSE;
////////////////////////////////////

// This should be the actual filesystem path to the directory
// where FlightPath is installed.  Do NOT include a trailing slash!
// Ex: /var/www/public_html/flightpath  or, for Windows: C:/htdocs/flightpath
// ** Depending on your webserver, you may be required to use forward-slashes! **
// use the following line to help you figure out the fileSystemPath, by seeing
// what the path is to this file:
// print "<br>System path to settings.php: " . __FILE__ . "<br><br>";
$system_settings["file_system_path"] = "%FILE_SYSTEM_PATH%";

// The base URL is the actual URL a user would type to visit your site.
// Do NOT enter a trailing slash!
// Ex:  http://localhost/flightpath
$system_settings["base_url"] = "%BASE_URL%";

// The basePath is related to the baseURL.  It is the part of the URL which comes after
// your domain name.
// It MUST begin with a preceeding slash.
// Ex: If your site is example.com/dev/flightpath, then you should
// enter  "/dev/flightpath" 
$system_settings["base_path"] = "%BASE_PATH%";


////////////////////////////////////
// *** Database-related settings ***
////////////////////////////////////
$system_settings["db_host"] = "%DB_HOST%:%DB_PORT%"; // domain/ip address of the mysql host. ex: localhost or mysite.com:32145
$system_settings["db_user"] = "%DB_USER%"; 
$system_settings["db_pass"] = "%DB_PASS%"; 
$system_settings["db_name"] = "%DB_NAME%"; // Name of the actual database where
                                              // flightpath\'s tables are located.
                                              // Usually just "flightpath"




/////////////////////////////////////
// *** Defaults ***                //
/////////////////////////////////////
// These default settings are for installation reasons only. 
// They will be overwritten in memory
// once the flightpath_system_settings table is read in at the end of the file.
// Do not alter them.
$system_settings["display_mysql_errors"] = TRUE;
$system_settings["theme"] = "themes/classic";


////////////////////////////////////
// *** Misc Settings ***
////////////////////////////////////
// To cut down on load times when the user loads a large elective group
// containing many courses, FlightPath can load some of the course inventory
// upon login.  Set the number of courses to load here.
$system_settings["load_course_inventory_on_login_number"] = 2000;


////////////////////////////////////
// *** Cron-related ***
////////////////////////////////////
// If you wish to use cron.php (which will call every module\'s
// hook_cron() function), you may set up a cron job like this:
//      php cron.php security_token_string
 
// SecurityToken:  This is something which
// must be the first argument passed to cron.php.  It can be any continuous
// string of *alpha-numeric* characters.
// This is a security measure to prevent unauthorized users (or web-users) from
// running cron.php, and is REQUIRED!
// For example, if the token is "qwss34frwquu" then to run the script you would need
// to use:   http://url/cron.php?t=CRON_TOKEN  (use wget to access from a system cron job.)
$system_settings["cron_security_token"] = "%CRON_SECURITY_TOKEN%";


/////////////////////////////////////
/////////////////////////////////////
//
// The end of the settings file!

/////////////////////////////////////
/////////////////////////////////////
/////////////////////////////////////
// Do not alter or remove!!
// This will load the contents of the flightpath_system_settings
// table into the $system_settings variable.  These are extra settings
// which were set via the web using the system module.
$db_host = $system_settings["db_host"];
$db_user = $system_settings["db_user"];
$db_pass = $system_settings["db_pass"];
$db_name = $system_settings["db_name"];
$dbc = mysql_connect ($db_host, $db_user, $db_pass) or die("Could not connect to database: " . mysql_error());
mysql_select_db ($db_name);

$res = mysql_query("SELECT * FROM variables");
while ($cur = mysql_fetch_array($res)) {
  if ($val = unserialize($cur["value"])) {
    $system_settings[$cur["name"]] = $val;
  }
}

$res = mysql_query("SELECT * FROM modules WHERE enabled = 1
                    ORDER BY weight");
while ($cur = mysql_fetch_array($res)) {
  $system_settings["modules"][$cur["name"]] = $cur;
}

mysql_close($dbc);

// We want to make sure the "system" module is enabled, so we will hard-code
// its values.
if ($system_settings["modules"]["system"]["enabled"] != 1) {
  $system_settings["modules"]["system"]["path"] = "modules/system";
  $system_settings["modules"]["system"]["enabled"] = 1;
}

////////////////////////////////////////////
////////////////////////////////////////////
// This must appear at the VERY end!  Nothing should come after it....
//
// Assign our systemSettings to the GLOBALS array so we can access it anywhere.
$GLOBALS["fp_system_settings"] = $system_settings;

//////////////////////////////////////////////
//////////////////////////////////////////////
// PUT NOTHING BELOW THIS LINE!!!!    
    
  
';

}




/**
 * Displays the form to let a user set up a new database
 */
function install_display_db_form($msg = "") {
  global $lang;
  
  $db_name = $_POST["db_name"];
  $db_host = $_POST["db_host"];
  $db_port = $_POST["db_port"];
  $db_user = $_POST["db_user"];
  $db_pass = $_POST["db_pass"];

  $admin_pass = $_POST["admin_pass"];
  $admin_pass2 = $_POST["admin_pass2"];
  $admin_name = $_POST["admin_name"];
  
  
  
  $pC = "";

  $pC .= "<h2 class='title'>" . st("Setup Database and Admin") . "</h2>$msg
          <p>" . st("You should have already set up a database and database user
                      (with all privileges except Grant) for FlightPath.  Please
                      enter that information below.") . "</p>
                      
          <hr>
          <form action='install.php?lang=$lang' method='POST'>
          <input type='hidden' name='perform_action' value='install'>
          <table border='0' cellpadding='3' style='margin-left: 20px;'>

            <tr>
              <td colspan='2'><b>" . st("FlightPath administrator information") . "</b></td>
            </tr>
          
          
            <tr>
              <td valign='top'>" . st("Admin Username:") . "</td>
              <td valign='top'><input type='text' name='admin_name' value='$admin_name' size='15' maxlength='50'> Ex: admin</td>
            </tr>
            
            <tr>
              <td valign='top'>" . st("Admin Password:") . "</td>
              <td valign='top'><input type='password' name='admin_pass' value='$admin_pass' size='20'></td>
            </tr>
            
            <tr>
              <td valign='top'>" . st("Re-enter Password:") . "</td>
              <td valign='top'><input type='password' name='admin_pass2' value='$admin_pass2' size='20'></td>
            </tr>
            
                        
            <tr>
              <td colspan='2'><hr>
                <b>" . st("Database information") . "</b></td>
            </tr>
            
            <tr>
              <td valign='top'>" . st("Database Name:") . "</td>
              <td valign='top'><input type='text' name='db_name' value='$db_name' size='50'></td>
            </tr>
            
            <tr>
              <td valign='top'>" . st("Database Host/IP:") . "</td>
              <td valign='top'><input type='text' name='db_host' value='$db_host' size='50'></td>
            </tr>

            <tr>
              <td valign='top'>" . st("Database Port:") . "</td>
              <td valign='top'><input type='text' name='db_port' value='$db_port' size='10'> Ex: 3306</td>
            </tr>

            <tr>
              <td valign='top'>" . st("Database Username:") . "</td>
              <td valign='top'><input type='text' name='db_user' value='$db_user' size='50'></td>
            </tr>
            
            <tr>
              <td valign='top'>" . st("Database Password:") . "</td>
              <td valign='top'><input type='password' name='db_pass' value='$db_pass' size='50'></td>
            </tr>
            
          </table>
          <br><br>
          <input type='submit' value='" . st("Install") . "'>
          <br>
          <b>" . st("Please click only once.  May take several seconds to install.") . "
          </form>";
    
  

  
  // Display the screen
  $page_content = $pC;  
  $page_title = "Install FlightPath";
  $page_hide_report_error = TRUE;
  
  include("themes/classic/fp_template.php");  

  
}




/**
 * Check for missing requirements of the system.
 * 
 * Returns an array of missing requirements which the user must fix before
 * installation can continue.
 * 
 */
function install_check_requirements() {
  $rtn = array();
  
  // Is the /custom directory writable?
  if (!is_writable("custom")) {
    $rtn[] = st("Please make sure the <em>/custom</em> directory is writable to the web server.
               <br>Ex: chmod 777 custom");    
  }
  
  if (count($rtn) == 0) return FALSE;
  return $rtn;
}


/**
 * Displays the requirements on screen for the user. 
 */
function install_display_requirements($req_array) {
  global $lang;
  
  $pC = "";
  
  $pC .= "<h2 class='title'>" . st("Check Requirements") . "</h2>
          <p>" . st("The following requirements must be fixed before installation of FlightPath
                  can continue.") . "</p>";
    
  foreach ($req_array as $req) {
    $pC .= "<div style='padding: 5px; margin: 10px; border: 1px solid red; 
                    font-family: Courier New, serif; font-size:0.9em'>$req</div>";    
  }

  $pC .= "<p>" . st("Please fix the problems listed, then reload to try again:") . "
          <br><a href='install.php?lang=$lang'>" . st("Click here to try again") . "</a>";
  
  // Display the screen
  $page_content = $pC;  
  $page_title = "Install FlightPath";
  $page_hide_report_error = TRUE;
  
  include("themes/classic/fp_template.php");  
}





function install_display_lang_selection() {
  $pC = "";
  
  $pC .= "<h2 class='title'>Install FlightPath</h2>
          Please follow the instructions on the following pages to complete
          your installation of FlightPath.
          
            <h3 class='title'>Select language</h3>
          Please begin by selecting an installation language.
          <ul>
            <li><a href='install.php?lang=en'>English</li>
          </ul>";
    
  // Display the screen
  $page_content = $pC;  
  $page_title = "Install FlightPath";
  $page_hide_report_error = TRUE;
  
  include("themes/classic/fp_template.php");
}