<?php

/**
* These functions deal with user & permission management in the admin module.
*/



/**
 * This is the permissions form, where users can set which roles have which permissions.
 */
function admin_permissions_form() {
  $form = array();

  fp_add_css(fp_get_module_path("admin") . "/css/admin.css");
  
  
  // Let's get all of our permissions from the permissions table.
  $permissions = array();
  $res = db_query("SELECT * FROM role_permissions");
  while ($cur = db_fetch_array($res)) {
    $permissions[$cur["rid"]][] = $cur["perm"];
  }
    
  $cb = $m = 1; 
  
  $form["mark" . $m++] = array(
    
    "value" => t("Use this form to assign permissions to roles in the system.
                  These permissions are defined by module."),
  );

  $roles = array();
  $res = db_query("SELECT * FROM roles ORDER BY rid ");
  while ($cur = db_fetch_array($res)) {
    $roles[$cur["rid"]] = $cur["name"];
  }



  $col_count = count($roles) + 1;
  
  $form["mark" . $m++] = array(
    "value" => "<table class='admin-perm-table' width='100%' cellpadding='0' cellspacing='0'>
                <tr><th style='min-width: 400px;'>Permission</th>",
  );   
  
  foreach ($roles as $key => $role) {
    $form["mark" . $m++] = array(
      "value" => "<th style='min-width: 100px;'>" . $role . "</th>",
    );   
    
  }  

  $form["mark" . $m++] = array(
    "value" => "</tr>",
  );   

    
  // Go through all of the modules, then call hook_perm for them, to get their permissions.
  $modules = modules_implement_hook("perm");
  //fpm($modules);
  foreach ($modules as $module) {
    $form["mark" . $m++] = array(
      "value" => "<tr><td colspan='$col_count' class='perm-module-name'>" . ucwords($module) . " " . t("module") . "</td></tr>",
    );   
    
    $zebra = "even";
    
    // Let's get all the perms.
    $perms = call_user_func($module . "_perm");    
    foreach ($perms as $perm_name => $perm_details) {
      $title = $perm_details["title"];
      $desc = $perm_details["description"];

      $zebra = ($zebra == "even") ? "odd" : "even";

      $form["mark" . $m++] = array(
        "value" => "<tr class='perm-cb-row perm-cb-row-$zebra'><td class='perm-details' valign='top'>
                      <div class='perm-title' title='$perm_name'>$title</div>                      
                      <div class='perm-desc' title='$perm_name'>$desc</div>
                   </td>",
      );   

      foreach ($roles as $key => $role) {
        
        // Should this be checked by default (cause it was all ready in our table?)
        $default_value = array();
        if (is_array($permissions[$key]) && in_array($perm_name, $permissions[$key])) {
          // Yes, it was in there!  Set up the default_value as an array that
          // looks like array(key => key).  That is how we set a checkbox to be checked
          // by default.
          $default_value = array($key . "___$perm_name" => $key . "___$perm_name");          
        }
        
        $form["perm_cb_" . $cb++] = array(
          "type" => "checkboxes",
          "options" => array("$key" . "___$perm_name" => ""),
          "value" => $default_value,
          "prefix" => "<td class='perm-cb'>",
          "suffix" => "</td>",          
        );   
        
      }
     
      
      
      $form["mark" . $m++] = array(
        "value" => "</tr>",
      );   
            
           
      
    }
    
  }
  
    
  
  $form["mark" . $m++] = array(
    "value" => "</table>",
  );   
    
    
  $form["submit"] = array(
    "type" => "submit",
    "value" => t("Save permissions"),
    "prefix" => "<hr>",
  );
    
  return $form;
}
 
 
 
/**
 * Submit handler for the permissions form.
 */
function admin_permissions_form_submit($form, $form_submit) {
  $values = $form_submit["values"];
  
  // We should begin by truncating our permissions table, then re-inserting
  // everything we get from this submission.
  db_query("TRUNCATE TABLE role_permissions");
  
  // Find all the perm checkboxes.  
  foreach($values as $key => $val) {
    if (strstr($key, "perm_cb_")) {
      $cb = current($val);
      if (strstr($cb, "___")) {
        $temp = explode("___", $cb);
        $rid = $temp[0];
        $perm = $temp[1];
        
        // Okay, save this to our table.
        db_query("INSERT INTO role_permissions (rid, perm)
                  VALUES ('?', '?') ", $rid, $perm);      
      }
    }
  }
  
  fp_add_message(t("Permissions saved successfully."));
  
} 
 


/**
 * This form allows the user to manage the roles in the system.
 */
function admin_user_roles_form() {
  $form = array();
  
  fp_add_css(fp_get_module_path("admin") . "/css/admin.css");
  fp_add_js(fp_get_module_path("admin") . "/js/admin.js");
  
  
  
  $form["mark" . $m++] = array(
    "type" => "markup",
    "value" => t("Roles are able to be assigned permissions in FlightPath, and then users are assigned
                those roles.  You may not remove the two roles, 'anonymous user' and 'authenticated user'.
                Those are assigned automatically when the user logs in or out.
                However, you may add as many more roles as you wish.")
                . "<br><br><b>" . t("roles:") . "</b>
                <table style='padding-left: 20px;'>",
  );

  $res = db_query("SELECT * FROM roles ORDER BY rid");
  while ($cur = db_fetch_array($res)) {
    
    $key = $cur["rid"];
    $value = $cur["name"];
    
    $prompt_link = fp_get_js_prompt_link("Enter a new name to change this role to:", $value, "document.getElementById(\"element-perform_action2\").value=\"edit~_~$key~_~\" + response; document.getElementById(\"sysform\").submit(); ", t("edit")) . "  |  ";
    $confirm_link = fp_get_js_confirm_link(t("Are you sure you wish to delete this role?\\nIt will be removed from users' accounts.  This action cannot be undone.\\n\\nProceed?"), "document.getElementById(\"element-perform_action2\").value=\"del~_~$key\"; document.getElementById(\"sysform\").submit(); ", t("delete"));
    
    if ($key == 1 || $key == 2) {
      $prompt_link = $confirm_link = "";
    }
     
    $form["mark" . $m++] = array(
      "type" => "markup",
      "value" => "<tr><td>$value</td><td>" . $prompt_link . " 
                  " . $confirm_link . "</td></tr>", 
    );    
  }
  
  $form["mark" . $m++] = array(
    "type" => "markup",
    "value" => "</table>",
  );
  
  $form["perform_action2"] = array(
    "type" => "hidden",
  );
  
  $form["new_role"] = array(
    "type" => "textfield",
    "label" => t("Add a new role:"),    
  );
  
  $form["submit"] = array(
    "type" => "submit",
    "value" => t("Add new"),
  );  
  
  return $form;
}


function admin_user_roles_form_submit($form, $form_state) {
  $values = $form_state["values"];

  if (trim($values["new_role"]) != "") {
    $new_role = strtolower(trim($values["new_role"]));
    
    // Clean up any trouble chars
    $new_role = preg_replace("/[^a-zA-Z0-9_]/", " ", $new_role);

    // Okay, add to the roles table.
    db_query("INSERT INTO roles (name) VALUES ('?') ", $new_role);
        
    fp_add_message("The new role has been added successfully.");       
  }
    
  
  if (strstr($values["perform_action2"], "del~_~")) {
    $temp = explode("~_~", $values["perform_action2"]);
    $i = trim($temp[1]);
  
    // Remove this rid from the table.
    db_query("DELETE FROM roles WHERE rid = '?' ", $i);
      
    fp_add_message("The role has been deleted successfully.");    
            
  }
  
  
  if (strstr($values["perform_action2"], "edit~_~")) {
    $temp = explode("~_~", $values["perform_action2"]);
    $i = trim($temp[1]);
    $new_name = strtolower(trim($temp[2]));
    $new_name = preg_replace("/[^a-zA-Z0-9_]/", " ", $new_name);    
    if (trim($new_name) != "") {  
      
      // Let's update the table.
      db_query("UPDATE roles SET name = '?' WHERE rid = '?' ", $new_name, $i);
  
      fp_add_message("The role has been edited successfully.");    
    }
    
  }  
  
}


/**
 * Let the user edit a user's roles and other information.
 */
function admin_edit_user_form() {

  $form = array();
  $faculty_id = $_REQUEST["user_id"];
  $name = fp_get_faculty_name($faculty_id);
  
  fp_set_title(t("Edit User @name (@id)", array("@name" => $name, "@id" => $faculty_id)));

  $user_roles = system_get_roles_for_user($faculty_id);
  //fpm($user_roles);
  $default_values = array();
  foreach ($user_roles as $rid => $val) {
    $default_values[$rid] = $rid;
  }

  $form["user_id"] = array(
    "type" => "hidden",
    "value" => $faculty_id,
  );
  
  // Show a list of roles in the system which we may select from, and check the ones
  // all ready assigned to this user.
  
  $options = array();
      
  $res = db_query("SELECT * FROM roles ORDER BY rid");
  while ($cur = db_fetch_array($res)) {
    
    $key = $cur["rid"];
    $value = $cur["name"];

    if ($key > 2) {
      $options[$key] = $value;
    } 
      
  }  
//fpm($default_values);
  $form["roles"] = array(
    "label" => t("Check which roles this user should have."),
    "type" => "checkboxes",
    "options" => $options,
    "value" => $default_values,
  );
      

      
  
  $form["submit"] = array(
    "type" => "submit",  
    "value" => "Submit",    
    "prefix" => "<hr>",
  );
  
  
  return $form;    
  
}


function admin_edit_user_form_submit($form, $form_state) {
  
  $values = $form_state["values"];
  //fpm($values);
  $user_id = $values["user_id"];
  // Save the roles into the database for this user.
  // Begin by deleting what's there all ready.
  db_query("DELETE FROM user_roles WHERE user_id = '?' ", $user_id);

  foreach ($values["roles"] as $rid) {
    //fpm("inserting $rid");
    db_query("INSERT INTO user_roles (user_id, rid)
              VALUES ('?', '?') ", $user_id, $rid);
  }

  fp_add_message(t("User updated successfully."));
  
  
}





/**
 * Display our list of users in the system.
*/
function admin_display_users() {
  global $db, $screen;
  $de_catalog_year = $GLOBALS["de_catalog_year"];

  $cc = 1;

  $rtn = "";

  fp_add_css(fp_get_module_path("admin") . "/css/admin.css");

  // First, let's get our list of departments...
  $dept_array = array();
  $d = 0;
  
  // Let's pull the needed variables out of our settings, so we know what
  // to query, because this is a non-FlightPath table.
  //$tsettings = $GLOBALS["fp_system_settings"]["extra_tables"]["human_resources:faculty_staff"];
  //$tf = (object) $tsettings["fields"];  //Convert to object, makes it easier to work with.  
  //$table_name = $tsettings["table_name"];    
  
  $res = db_query("SELECT DISTINCT department FROM faculty ORDER BY department ");
  while ($cur = db_fetch_array($res)) {
    if (trim($cur["department"]) == "")
    {// skip if blank
      continue;
    }
    
    $dept_array[$d] = trim(ucwords(strtolower($cur["department"])));
    $d++;
  }


  $rtn .= "<h2 class='title'>" . t("Edit Users") . "</h2>";


  $letter_ranges = array(
    "A" => array("A", "AZZZZ"),
    "B" => array("B", "BZZZZ"),
    "C" => array("C", "CZZZ"),
    "D" => array("D", "DZZZZ"),
    "E" => array("E", "EZZZZ"),
    "F" => array("F", "FZZZZ"),
    "G" => array("G", "GZZZZ"),
    "H" => array("H", "HZZZZ"),
    "I" => array("I", "IZZZ"),
    "J" => array("J", "JZZZ"),
    "K" => array("K", "KZZZ"),
    "L" => array("L", "LZZZ"),    
    "M" => array("M", "MZZZ"),
    "N" => array("N", "NZZZ"),
    "O" => array("O", "OZZZ"),    
    "P" => array("P", "PZZZ"),
    "Q-R" => array("Q", "RZZZZ"),
    "S" => array("S", "SZZZ"),
    "T" => array("T", "TZZZZ"),
    "U" => array("U", "UZZZ"),
    "V-Z" => array("V", "ZZZZ"),        
  );


  $rtn .= "<div class='admin-courses-select-letter-bar'>
          ";
  foreach($letter_ranges as $disp => $vals) {
    $rtn .= l($disp, "admin/config/users", "de_catalog_year=$de_catalog_year&ur=" . $vals[0] . "&lr=" . $vals[1], array("class" => "admin-courses-letter-link")) . " &nbsp; ";
  }


  $rtn .= "</div>";
  $rtn .= "                             
                        <form action='" . base_path() . "/admin/config/users' method='GET' style='margin:0px; padding:0px;'>
                        <input type='hidden' name='performAction' value='editUsers'>
                         " . t("Search:") . " <input type='text' class='smallinput' name='search' value='$search' length='20'>
                        <input type='submit' name='searchsubmit' class='smallinput' value=' -> '>
                       
                        &nbsp; &nbsp; " . t("or") . " &nbsp; &nbsp;
                       <select name='department' class='smallinput'>
                        <option value=''>" . t("Select a department...") . "</option>
                        <option value=''>--------------------------</option>
                    <!--DEPTSEARCH-->
                       </select>
                       <input type='submit' name='deptsubmit' class='smallinput' value=' -> '>
                       
                       </form>
                       </div>
                       <br>
                      ";



  $displaying = "";



  $ur = trim($_GET["ur"]);
  $lr = trim($_GET["lr"]);

  if ($ur != "" || $lr != "")
  {
    $_SESSION["prev_user_search"] = "";
  }


  if ($ur == "")
  { // meaning, no range was set.  Use A - C
    $ur = $_SESSION["ur"];
    $lr = $_SESSION["lr"];
    if ($ur == "")
    { // if still blank, assign it..
      $ur = "A";
      $lr = "AZZZZ";
    }

  }
  $_SESSION["ur"] = $ur;
  $_SESSION["lr"] = $lr;

  $search = trim($_GET["search"]);
  $dept = trim($_GET["department"]);

  if ($search != "" || $dept != "")
  {
    $_SESSION["prev_user_search"] = "";
  }


  if ($_SESSION["prev_user_search"] != "")
  {
    $temp = explode("%%",$_SESSION["prev_user_search"]);
    if ($temp[0] == "search")
    {
      $search = $temp[1];
    }
    if ($temp[0] == "dept")
    {
      $_GET["deptsubmit"] = "1";
      $dept = $temp[1];
    }
  }


  $_SESSION["prev_user_search"] = "";

  
  // Let's pull the needed variables out of our settings, so we know what
  // to query, because this is a non-FlightPath table.
  //$tsettings = $GLOBALS["fp_system_settings"]["extra_tables"]["human_resources:faculty_staff"];
  //$tf = (object) $tsettings["fields"];  //Convert to object, makes it easier to work with.  
  //$table_name = $tsettings["table_name"];  
  
  
  if ($search != "" && !($_GET["deptsubmit"]))
  {
    // Something was searched for, and the dept submit button was not pushed.
    $dept = "";
    $temp = explode(" ",$search);
    $search1 = $temp[0];
    $search2 = trim($temp[1]);

    $_SESSION["prev_user_search"] = "search%%$search";
    $displaying = $search;
    $second_part = "";
    if ($search2 != "")
    {
      // Two search terms, probably a name...
      $result = db_query("SELECT * FROM users u, faculty f
          WHERE  
          u.user_id = f.user_id AND
          (l_name LIKE '%?%'
          AND f_name LIKE '%?%')
          ORDER BY l_name, f_name ", $search2, $search1);

    }else {

      // One search term....
      $result = db_query("SELECT * FROM users u, faculty f
          WHERE 
          u.user_id = f.user_id AND
          u.user_id LIKE '%?%' 
          OR l_name LIKE '%?%'
          OR f_name LIKE '%?%'  
          ORDER BY l_name, f_name ", $search1, $search1, $search1);
    }

  }
  else if ($dept != "" && $_GET["deptsubmit"]) {
    // User select a department.  Look for it...
    $search = "";
    $_SESSION["prev_user_search"] = "dept%%$dept";
    $result = db_query("SELECT * FROM users a, faculty b
          WHERE a.user_id = b.user_id
          AND a.is_faculty = 1
          AND department = '?' 
          ORDER BY l_name, f_name ", $dept);
    $displaying = $dept;
  }
  else
  { // No search, so look for the range...
    $result = db_query("SELECT * FROM users u, faculty f
                        WHERE
                        u.user_id = f.user_id AND 
                        l_name BETWEEN '?' AND '?'
                        ORDER BY l_name, f_name ", $ur, $lr);
    $displaying = $ur;
  }
  $rtn .= "<div class='tenpt' style='padding-bottom: 5px;'><b>" . t("Displaying:") . "</b> $displaying</div>
      <table border='0' width='100%' cellpadding='3' cellspacing='0' class='user-list'>
    
        <tr>
          <th>CWID</th>
          <th>Name</th>
          <th></th>
          <th>Department</th>
          <th>Roles</th>
          <th>&nbsp;</th>
        </tr>
    
    ";
  while ($cur = db_fetch_array($result)) {

    $l_name = trim(ucwords(strtolower($cur["l_name"])));
    $f_name = trim(ucwords(strtolower($cur["f_name"])));    
    $faculty_id = trim($cur["user_id"]);
    $dept_name = trim(ucwords(strtolower($cur["department"])));

    
    $ast = "";
    $reason = "";


    $fgcol = "black";

    $roles = "";
    $temp = system_get_roles_for_user($faculty_id);
    foreach ($temp as $t) {
      $roles .= "<div class='list-role'>$t</div>";
    }



    //      $pC .= "<a href='edit_users.php?action=load&course_id=$course_id'> user: $f_name $mid_name $l_name </a><br>";

    $rtn .= "<tr>
              
              <td valign='top' width='15%'>$faculty_id</td>
          <td valign='top' width='15%'>$f_name</td>
          <td valign='top' width='15%'>$l_name</td>
          <td valign='top'>$dept_name</td>
          <td valign='top'>$roles</td>
          <td valign='top'>" . l(t("edit"), "admin/users/edit-user", "user_id=$faculty_id&de_catalog_year=$de_catalog_year") . "</td>
          
          
        </tr>";

  } // while
  $rtn .= "</table>";



  // Put in the dept pulldown....
  $bC = "";
  for ($t = 0; $t<count($dept_array); $t++)
  {
    $dd = $dept_array[$t];

    $sel = "";
    if ($dd == $dept)
    {
      $sel = "selected";
    }
    $bC .= "<option value='$dd' $sel>{$dept_array[$t]}</option> \n";
  }
  $rtn = str_replace("<!--DEPTSEARCH-->",$bC,$rtn);

  
  
  return $rtn;
  
}




