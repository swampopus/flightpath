<?php

/**
 * This file contains mostly menu-related all-purpose functions
 * for FlightPath
 */

 
 
/**
 * Go through all installed modules and rebuild the menu_router table,
 * based on each module's hook_menu function.
 */
function menu_rebuild_cache($bool_display_message = TRUE) {


  // Begin by wiping out the menu_router table
  db_query("TRUNCATE TABLE menu_router ");
  
  $modules = modules_implement_hook("menu");
  
  foreach ($modules as $module) {
    $items = call_user_func($module . "_menu");
    
    // Okay, now go through the $items array, and write the needed information
    // to the menu_router array.
    foreach ($items as $path => $item) {
      if (is_numeric($path)) continue; // problem, so skip.

      // Update our menu_router table.
      // Now, insert the new one.
      db_query("INSERT INTO menu_router
                  (path, access_callback, access_arguments, page_callback, page_arguments, title, description, type, weight, icon, page_settings, tab_family, tab_parent, file)
                  VALUES
                  ('?', '?', '?', '?', '?', '?', '?', '?', '?', '?', '?', '?', '?', '?')
                  ", $path, $item["access_callback"], serialize($item["access_arguments"]), $item["page_callback"],
                     serialize($item['page_arguments']), $item['title'], $item['description'], $item['type'], $item['weight'], $item['icon'],
                     serialize($item['page_settings']), $item['tab_family'], $item['tab_parent'], $item['file']);
    }
  }  
  
  if ($bool_display_message) {
    fp_add_message(t("The menu router has been rebuilt"));
  }
    
}


function menu_get_module_path($module) {

  

  if (isset($GLOBALS["fp_system_settings"]["modules"][$module]["path"])) {
    return $system_path . $GLOBALS["fp_system_settings"]["modules"][$module]["path"];
  }
  else {
    return FALSE;
  }
    
}



/**
 * Looks at the router item's details (from menu_get_item)
 * and returns TRUE or FALSE if the user can access this item. 
 */
function menu_check_user_access($router_item) {
  $access = FALSE;
  if ($router_item["access_callback"] == 1) {
    $access = TRUE;
  }
  if (is_array($router_item["access_arguments"])) {
    if ($router_item["access_callback"] == "") {
      // If we specified access arguments, but not a callback, assume it
      // is the function user_has_permission().
      $router_item["access_callback"] = "user_has_permission";
    }
  }
  
  if (!$access && $router_item["access_callback"] != "") {
    // We need to see if the user has the right permissions to use this item.  We will do this
    // by calling the access_callback, passing it the access_arguments
    $access = call_user_func_array($router_item["access_callback"], $router_item["access_arguments"]);
  }    
    
    
  return $access;
}


function menu_execute_page_request() {
  $path = $_GET["q"];

  //If the path is blank, figure out what the "font page" is, and use that path.
  if ($path == "") {
    $path = variable_get("front_page", "main");
  }
  
  if ($router_item = menu_get_item($path)) {
    
    // Let's save the router item in the GLOBALS array, so we can use information from it
    // throughout FlightPath, if we need to.
    $GLOBALS["fp_current_menu_router_item"] = $router_item;
    
    // If the menu item contains a "redirect", then we should perform
    // an fp_goto to that immediately.
    if (isset($router_item["page_settings"]["redirect"])) {
      $p = $router_item["page_settings"]["redirect"]["path"];
      $q = $router_item["page_settings"]["redirect"]["query"];
      fp_goto($p, $q);
      return;
    }
       
    // Let's figure out if the user has access to this menu item or not.  
    if (menu_check_user_access($router_item)) {
      if ($router_item['file'] != "") {      
        require_once($router_item['file']);
      }
      $page = array();
      $page["content"] = call_user_func_array($router_item['page_callback'], $router_item['page_arguments']);      
      $page["path"] = $path;
      $page["title"] = $router_item["title"];
      $page["router_item"] = $router_item;

      return $page;    
           
    }
    else {
      return MENU_ACCESS_DENIED;
    }
  }
  return MENU_NOT_FOUND;  
}


/**
 * Return menu_items whose path begins with the menu_root.
 * Ex:   "tools"  would return tools/fun and tools/here/there
 */
function menu_get_items_beginning_with($menu_root) {
  $rtn = array();
  
  $res = db_query("SELECT path FROM menu_router WHERE path LIKE '?%' 
                    ORDER BY weight, title", $menu_root);
  while ($cur = db_fetch_array($res)) {
    $path = $cur["path"];
    $item = menu_get_item($path);
    if ($item) {
      $rtn[] = $item;
    }
  }
  
  
  return $rtn;
}


function menu_get_items_in_tab_family($tab_family) {
  $rtn = array();
  
  $res = db_query("SELECT path FROM menu_router WHERE tab_family = '?'
                   ORDER BY weight, title", $tab_family);
  while ($cur = db_fetch_array($res)) {
    $path = $cur["path"];
    $item = menu_get_item($path);
    if ($item) {
      $rtn[] = $item;
    }    
  }
    
  return $rtn;
}


/**
 * Return array from menu_router for this item. * 
 */
function menu_get_item($path) {
  $res = db_query("SELECT * FROM menu_router WHERE path = '?' ", $path);
  $cur = db_fetch_array($res);

  if ($cur["path"] != $path) {
    // It was not found!
    return NULL;
  }  
  
  // Unserialize the things which are supposed to be unserialized.
  $cur["page_arguments"] = unserialize($cur["page_arguments"]);
  if (!is_array($cur["page_arguments"])) {
    $cur["page_arguments"] = array();
  }

  $cur["access_arguments"] = unserialize($cur["access_arguments"]);
  if (!is_array($cur["access_arguments"])) {
    $cur["access_arguments"] = array();
  }

  $cur["page_settings"] = unserialize($cur["page_settings"]);
  if (!is_array($cur["page_settings"])) {
    $cur["page_settings"] = array();
  }
  
  return $cur;    
      
}
 
