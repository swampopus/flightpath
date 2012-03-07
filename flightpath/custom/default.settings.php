<?php
/**
 * This is the settings file for your installation of FlightPath.
 * You should copy the default.settings.php file to a new file,
 * named settings.php.  All your changes should happen there.
 * 
 * Once you have made changes to it, it might be best to change
 * the permissions to "read-only" to prevent unauthorized users
 * from altering it.
 */

/**
 * All system settings will be placed (at the end of this script)
 * into a $GLOBALS variable, but for now will be placed into an
 * array.
 */

$systemSettings = array();

////////////////////////////////////
// !!!  *** IMPORTANT !!!  ***    //
////////////////////////////////////
// If this variable is set to TRUE, then anyone who attempts to log in
// will have full, admin access.
// Only set this to TRUE when you are first installing FlightPath.
// Otherwise, leave it set to FALSE!
$systemSettings["GRANT_FULL_ACCESS"] = FALSE;
////////////////////////////////////

// This should be the actual filesystem path to the directory
// where FlightPath is installed.  Do NOT include a trailing slash!
// Ex: /var/www/public_html/flightpath
$systemSettings["fileSystemPath"] = "/www/webservices/flightpath";

////////////////////////////////////
// *** Database-related settings ***
////////////////////////////////////
$systemSettings["dbHost"] = "localhost"; // domain/ip address of the mysql host
$systemSettings["dbUser"] = "fpuser"; 
$systemSettings["dbPass"] = "fpuserpass"; 
$systemSettings["dbName"] = "flightpath"; // Name of the actual database where
                                              // flightpath's tables are located.
                                              // Usually just "flightpath"




/////////////////////////////////////
// *** Defaults ***                //
/////////////////////////////////////
// These default settings are for installation reasons only. 
// They will be overwritten in memory
// once the flightpath_system_settings table is read in at the end of the file.
// Do not alter them.
$systemSettings["displayMySQLErrors"] = TRUE;
$systemSettings["theme"] = "themes/classic";



////////////////////////////////////
// *** Cron-related ***
////////////////////////////////////
// If you wish to use cron.php (which will call every module's
// hook_cron() function), you may set up a cron job like this:
//      php cron.php security_token_string
/* 
 SecurityToken:  This is something which
 must be the first argument passed to cron.php.  It can be any continuous
 string of *alpha-numeric* characters.  If it is blank, then no securityToken 
 will be required.
 This is a security measure to prevent unauthorized users (or web-users) from
 running cron.php, and is REQUIRED!
 For example, if the token is "qwss34frwquu" then to run the script you would need
 to use:   php cron.php qwss34frwquu
*/
$systemSettings["cronSecurityToken"] = "abc123";

// The cron will automatically delete log entries (in the log table)
// older than this many days when it runs.  Logs can be very useful (and are
// required by the stats module) but can also take up a lot of space.
// It is recommended that you do not set this lower than 365 days, unless
// you know for sure that you will not need logs older than that.
// For safety, you cannot set this lower than 30 days.
// Comment out to disable.
$systemSettings["cronDeleteLogsOlderThanDays"] = 400;

////////////////////////////////////
// *** External Tables/Fields***
////////////////////////////////////
/* 
 This section deals with tables and even specific fields 
 which may or may not fall outside
 of the FlightPath database.  For example, tables relating to student
 and faculty data.

 Important, if the table is outside of the flightpath database specified
 above, then you must enter the table_name as database.tablename.
 For example:  "human_resources.faculty_staff"
 And make sure the flightpath user, specified above, has select privileges
 to those tables.
 
 These are defined in a multi-dimension array structure.
 
*/
//human_resources:students
$systemSettings["extraTables"]["human_resources:students"] = array(
  "tableName" => "flightpath_extra_data.students",
  "fields" => array(
    "studentID" => "student_id",
    "fName" => "f_name",
    "lName" => "l_name",
    "midName" => "mid_name",
    "cumulativeHours" => "cumulative_hours",
    "gpa" => "GPA",
    "rankCode" => "rank_code",
    "majorCode" => "major_code",
    "catalogYear" => "catalog_year",
  ),
);


// human_resources:faculty_staff
$systemSettings["extraTables"]["human_resources:faculty_staff"] = array(
  "tableName" => "flightpath_extra_data.faculty_staff",
  "fields" => array(
    "facultyID" => "faculty_id",
    "fName" => "f_name",
    "lName" => "l_name",
    "midName" => "mid_name",
    "majorCode" => "major_code",
    "deptName" => "dept_name",
    "collegeName" => "college_name",
    "employeeType" => "employee_type",
  ),
);


// human_resources:advisor_student
$systemSettings["extraTables"]["human_resources:advisor_student"] = array(
  "tableName" => "flightpath_extra_data.advisor_student",
  "fields" => array(
    "facultyID" => "faculty_id",
    "studentID" => "student_id",    
  ),
);




//course_resources:student_courses
$systemSettings["extraTables"]["course_resources:student_courses"] = array(
  "tableName" => "flightpath_extra_data.student_courses",
  "fields" => array(
    "studentID" => "student_id",
    "subjectID" => "subject_id",
    "courseNum" => "course_num",
    "hoursAwarded" => "hours_awarded",
    "grade" => "grade",
    "termID" => "term_id",
  ),
);


// course_resources:student_transfer_courses
$systemSettings["extraTables"]["course_resources:student_transfer_courses"] = array (
  "tableName" => "flightpath_extra_data.student_transfer_courses",
  "fields" => array(
    "studentID" => "student_id",
    "transferCourseID" => "transfer_course_id",
    "studentSpecificCourseTitle" => "student_specific_course_title",
    "termID" => "term_id",
    "grade" => "grade",
    "hoursAwarded" => "hours_awarded",
  ),
);


// course_resources:transfer_courses
$systemSettings["extraTables"]["course_resources:transfer_courses"] = array (
  "tableName" => "flightpath_extra_data.transfer_courses",
  "fields" => array (
    "transferCourseID" => "transfer_course_id",
    "institutionID" => "institution_id",
    "subjectID" => "subject_id",
    "courseNum" => "course_num",
    "title" => "title",
    "description" => "description",
    "minHours" => "min_hours",
    "maxHours" => "max_hours",
  ),
);


// course_resources:transfer_institutions
$systemSettings["extraTables"]["course_resources:transfer_institutions"] = array (
  "tableName" => "flightpath_extra_data.transfer_institutions",
  "fields" => array (
    "institutionID" => "institution_id",
    "name" => "name",
    "state" => "state",
  ),
);



// course_resources:transfer_eqv_per_student
$systemSettings["extraTables"]["course_resources:transfer_eqv_per_student"] = array (
  "tableName" => "flightpath_extra_data.transfer_eqv_per_student",
  "fields" => array (
    "studentID" => "student_id",
    "transferCourseID" => "transfer_course_id",
    "localCourseID" => "local_course_id",
    "validTermID" => "valid_term_id",
    "brokenID" => "broken_id",
  ),
);



// course_resources:subjects
$systemSettings["extraTables"]["course_resources:subjects"] = array (
  "tableName" => "flightpath_extra_data.subjects",
  "fields" => array (
    "subjectID" => "subject_id",
    "title" => "title",
  ),
);


// course_resources:student_developmentals
$systemSettings["extraTables"]["course_resources:student_developmentals"] = array (
  "tableName" => "flightpath_extra_data.student_developmentals",
  "fields" => array (
    "studentID" => "student_id",
    "requirement" => "requirement",
  ),
);


// flightpath_resources:student_tests
$systemSettings["extraTables"]["flightpath_resources:student_tests"] = array (
  "tableName" => "flightpath_extra_data.student_tests",
  "fields" => array (
    "studentID" => "student_id",
    "testID" => "test_id",
    "categoryID" => "category_id",
    "score" => "score",
    "dateTaken" => "datetime",
  ),
);


// flightpath_resources:tests
$systemSettings["extraTables"]["flightpath_resources:tests"] = array (
  "tableName" => "flightpath_extra_data.tests",
  "fields" => array (
    "testID" => "test_id",
    "categoryID" => "category_id",
    "position" => "position",
    "testDescription" => "test_description",
    "categoryDescription" => "category_description",
  ),
);



///////////////////////////////////////////////////////////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////////////////////////////////////////////////////////


/////////////////////////////////////
/////////////////////////////////////
//
// The end of the settings file!

/////////////////////////////////////
/////////////////////////////////////
/////////////////////////////////////
// Do not alter or remove!!
// This will load the contents of the flightpath_system_settings
// table into the $systemSettings variable.  These are extra settings
// which were set via the web using the system module.
$dbHost = $systemSettings["dbHost"];
$dbUser = $systemSettings["dbUser"];
$dbPass = $systemSettings["dbPass"];
$dbName = $systemSettings["dbName"];
$dbc = mysql_connect ($dbHost, $dbUser, $dbPass) or die('Could not connect to database: ' . mysql_error());
mysql_select_db ($dbName);

$res = mysql_query("SELECT * FROM flightpath_system_settings");
while ($cur = mysql_fetch_array($res)) {
  if ($val = unserialize($cur["value"])) {
    $systemSettings[$cur["name"]] = $val;
  }
}
mysql_close($dbc);


// We want to make sure the "system" module is enabled, so we will hard-code
// its values.
$systemSettings["modules"]["system"]["path"] = "modules/system";
$systemSettings["modules"]["system"]["disabled"] = "no";

// Reorder the modules by weight.
reorderModulesByWeight($systemSettings["modules"]);


////////////////////////////////////////////
////////////////////////////////////////////
// This must appear at the VERY end!  Nothing should come after it....
//
// Assign our systemSettings to the GLOBALS array so we can access it anywhere.
$GLOBALS["fpSystemSettings"] = $systemSettings;
?>