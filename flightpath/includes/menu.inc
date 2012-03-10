<?php

/**
 * This file contains mostly menu-related all-purpose functions
 * for FlightPath
 */

 ////////////////////////////////////////////
 

// Define useful constants
define("MENU_TYPE_NORMAL_ITEM", 101);
define("MENU_TYPE_CALLBACK", 102);
define("MENU_TYPE_TAB", 103);
define("MENU_TYPE_SUB_TAB", 104);
/////
define("MENU_ACCESS_DENIED", 201);
define("MENU_NOT_FOUND", 202);
    
 
/**
 * Go through all installed modules and rebuild the menu_router table,
 * based on each module's hook_menu function.
 */
function menu_rebuild_cache() {
  
  foreach ($GLOBALS["fp_system_settings"]["modules"] as $module => $value) {    
    if (isset($value["disabled"]) && $value["disabled"] == "yes") {
      // Module is not enabled.  Skip it.
      continue;
    }    
    if (function_exists($module . "_menu")) {
      $items = call_user_func($module . "_menu");
      
      // Okay, now go through the $items array, and write the needed information
      // to the menu_router array.
      foreach ($items as $path => $item) {
        if (is_numeric($path)) continue; // problem, so skip.

        // Update our menu_router table.
        // Begin by deleting the existing path, if there is one.
        db_query("DELETE FROM menu_router WHERE path = '?' ", $path);
        // Now, insert the new one.
        db_query("INSERT INTO menu_router
                    (path, access_callback, access_arguments, page_callback, page_arguments, title, description, type, weight, icon, page_settings, tab_family, tab_parent)
                    VALUES
                    ('?', '?', '?', '?', '?', '?', '?', '?', '?', '?', '?', '?', '?')
                    ", $path, $item["access_callback"], serialize($item["access_arguments"]), $item["page_callback"],
                       serialize($item['page_arguments']), $item['title'], $item['description'], $item['type'], $item['weight'], $item['icon'],
                       serialize($item['page_settings']), $item['tab_family'], $item['tab_parent']);
      }
    }
  }
  
  fp_add_message(t("The menu router has been rebuilt"));
    
}



function menu_execute_page_request() {
  $path = $_GET["q"];

  //If the path is blank, figure out what the "font page" is, and use that path.
  if ($path == "") {
    $path = variable_get("front_page", "main");
  }
  if ($router_item = menu_get_item($path)) {
   
    // Let's figure out if the user has access to this menu item or not.
    $access = FALSE;
    if ($router_item["access_callback"] == 1) {
      $access = TRUE;
    }
     
    if ($access) {
      if ($router_item['file']) {
        require_once($router_item['file']);
      }
      $page = array();
      $page["content"] = call_user_func_array($router_item['page_callback'], $router_item['page_arguments']);      
      $page["path"] = $path;
      // TODO:  Check to see if the user has overridden the title using the fp_set_title() command.
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
 * Return array from menu_router for this item.
 */
function menu_get_item($path) {
  $res = db_query("SELECT * FROM menu_router WHERE path = '?' ", $path);
  $cur = db_fetch_array($res);
  
  if ($cur["path"] != $path) {
    // It was not found!
    return NULL;
  }  
  
  // Make sure the page_callback is a reachable function.  If it is not, 
  // it means the module which defined it might be disabled.
  if (!function_exists($cur["page_callback"])) {
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
 
