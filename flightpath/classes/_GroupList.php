<?php


require_once("ObjList.php");

class _GroupList extends ObjList
{
	/*
	This class inherits mosts of its classes from ObjList
	in a similar fashion as CourseList.
	*/

	
	function reset_list_counters()
	{
		$this->reset_counter();
		// Also, go through all groups in the list and call
		// their "reset_list_counters" method.
		for ($t = 0; $t < $this->count; $t++)
		{
			$group = $this->array_list[$t];
			$group->reset_list_counters();
		}		
	}
	
	
	function contains_group_requirement_id($group_requirement_id)
	{
		// Returns true if any of the lists of courses in these groups
		// contain the group requirement ID.
		for ($t = 0; $t < count($this->array_list); $t++)
		{
			$group = $this->array_list[$t];
			if ($group->list_courses->contains_group_requirement_id($group_requirement_id))
			{
				return true;
			}
		}		
		
		return false;
		
	}
	
   /**
   * Set all the courses and branches in this group to the specified degree_id.
   */
  function set_req_by_degree_id($degree_id = 0) {
      
    // Loop through all the groups in this list...
    $this->reset_counter();
    while ($this->has_more()) {
      $g = $this->get_next();
      // Set req_by_degree_id on this group...
      $g->set_req_by_degree_id($degree_id);
    }  
    
  }
  
  
  

  /**
   * Adds the supplied GroupList to the bottom of $this's list.
   *
   */
  function add_list(GroupList $glist)
  {
    for ($t = 0; $t < count($glist->array_list); $t++)
    {
      $this->add($glist->array_list[$t]);
    }

  }  
  
  /**
   * Return back the matching group object, if it's degree_id matches.  Otherwise, FALSE
   */
  function find_match_with_degree_id($group, $degree_id) {
    for ($t = 0; $t < $this->count; $t++)
    {
      $g = $this->array_list[$t];
      if ($g->group_id == $group->group_id && $g->req_by_degree_id == $degree_id) {
        return $g;
      }
    }
        
    return FALSE;
  }
  
  
  /**
   * Return an array of course_id's for all the groups in this list.
   * will look like:  $rtn[group_id][course_id] = TRUE
   */
  function get_group_course_id_array() {
    $rtn = array();
    $this->reset_counter();
    
    for ($t = 0; $t < $this->count; $t++)
    {
      $g = $this->array_list[$t];
      
      $rtn[$g->group_id] = $g->get_course_id_array();
    }

       
    return $rtn;
      
  }
  
  
  /**
   * Calls the function "reload_missing_courses" on all groups in this list.
   */
  function reload_missing_courses() {
    for ($t = 0; $t < $this->count; $t++)
    {
      $group = $this->array_list[$t];
      $group->reload_missing_courses();
    }    
  }
  
  
  
  /**
   * Return a GroupList which is a clone of this list.
   */
  function get_clone($bool_return_new_groups = FALSE, $bool_load_groups = TRUE, $bool_reload_missing_courses = FALSE)
  {
    $rtn_list = new GroupList();
    
    for ($t = 0; $t < $this->count; $t++)
    {
      $group = $this->array_list[$t];
      
            
      if ($bool_return_new_groups == TRUE)
      {
        $new_group = new Group();
        $new_group->group_id = $group->group_id;
        
        if ($bool_load_groups) {
          $new_group->load_group();  // Make sure the group has all its courses loaded in.
        }
        
        $new_group->set_requirement_type($group->requirement_type);
        $new_group->set_req_by_degree_id($group->req_by_degree_id);                
        
        $rtn_list->add($new_group);
      } 
      else {
        
        if ($bool_load_groups) {
          $group->load_group();  // Make sure the group has all its courses loaded in.
        }
        
        if ($bool_reload_missing_courses) {          
          $group->reload_missing_courses();
        }
                
        
        $group->set_requirement_type($group->requirement_type);
        $group->set_req_by_degree_id($group->req_by_degree_id);
                       
        $rtn_list->add($group);
      } 
      
    } 
    
    return $rtn_list;
      
  }  
  
  
  
	
	function get_advised_courses_list()
	{
		// Return a courseList object of courses in THIS
		// group which have boolAdvisedToTake == true.
		$rtn_list = new CourseList();
		for ($t = 0; $t < count($this->array_list); $t++)
		{
			$group = $this->array_list[$t];
			$rtn_list->add_list($group->list_courses->get_advised_courses_list());
			
			$group->list_groups->reset_counter();
			while($group->list_groups->has_more())
			{
				$gg = $group->list_groups->get_next();
				$rtn_list->add_list($gg->list_courses->get_advised_courses_list());
			}
		}		
		
		$rtn_list->remove_duplicates();
		
		return $rtn_list;
		
	}
	
	
	function assign_min_grade($min_grade)
	{
		// Assign a min grade to every group in this grouplist.
		for ($t = 0; $t < count($this->array_list); $t++)
		{
			$group = $this->array_list[$t];
			$group->assign_min_grade($min_grade);
		}					
		
		
	}
	
  
  
  function sort_degree_advising_weight() {
    
    $tarray = array();
    
    for ($t = 0; $t < count($this->array_list); $t++)
    {
      $g = $this->array_list[$t];
      // Get the degree_id for this group
      $degree_id = $g->req_by_degree_id;
      
      $major_code = fp_get_degree_major_code($degree_id);
      $advising_weight = fp_get_degree_advising_weight($degree_id);
      if (!$advising_weight) $advising_weight = 0;
      
      // Make the number a string, uniformly long    
      $advising_weight = str_pad($advising_weight, 4, "0", STR_PAD_LEFT);
      
      
      //$new_html[$degree_advising_weight . "__" . $degree_title][$req_by_degree_id] = $content;
      $tarray[] = $advising_weight . "___" . $major_code . " ~~ " . $t;
    }
    
    // Now, sort the array to get everything in the correct order.   
     
    sort($tarray);
    
    
    // Now, convert the array back into a list of groups.
    $new_list = new GroupList();
    for($t = 0; $t < count($tarray); $t++)
    {
      $temp = explode(" ~~ ",$tarray[$t]);
      $i = $temp[1];

      $new_list->add($this->array_list[$i]);
    }

    // Okay, now $new_list should contain the correct values.
    // We will transfer over the reference.
    $this->array_list = $new_list->array_list;

    
        
  } // sort_degree_advising_weight
  
  
  
  
	function sort_priority()
	{
		/*
			Sort this list of groups by their priority number.
			Higher priorities should appear at the
			top of the list.
		*/
		$tarray = array();
		// Since I need the indexes, I will have to go through the array
		// myself...
		for ($t = 0; $t < count($this->array_list); $t++)
		{
			$g = $this->array_list[$t];
			$g->load_descriptive_data();
			$pri = "" . ($g->priority*1) . "";
			if (strlen($pri) == 1)
			{
				$pri = "0" . $pri; // padd with 0 on the front.
				// This fixes a sorting problem, because priorities
				// were being evaluated as text, not nums, so "5" seemed
				// larger than "15"  (because it was comparing the 5 to the 1).
			}
			$str = "$pri ~~ $t";

			array_push($tarray,$str);
		}


		rsort($tarray);

		// Now, convert the array back into a list of groups.
		$new_list = new GroupList();
		for($t = 0; $t < count($tarray); $t++)
		{
			$temp = explode(" ~~ ",$tarray[$t]);
			$i = $temp[1];

			$new_list->add($this->array_list[$i]);
		}

		// Okay, now $new_list should contain the correct values.
		// We will transfer over the reference.
		$this->array_list = $new_list->array_list;
		
		
	}
	
	function sort_alphabetical_order($bool_reverse_order = false)
	{

		$tarray = array();
		// Since I need the indexes, I will have to go through the array
		// myself...
		for ($t = 0; $t < count($this->array_list); $t++)
		{
			$g = $this->array_list[$t];
			$g->load_descriptive_data();
			$str = "$g->title ~~ $t";

			array_push($tarray,$str);
		}

		if ($bool_reverse_order == true)
		{
			rsort($tarray);
		} else {
			sort($tarray);
		}

		// Now, convert the array back into a list of groups.
		$new_list = new GroupList();
		for($t = 0; $t < count($tarray); $t++)
		{
			$temp = explode(" ~~ ",$tarray[$t]);
			$i = $temp[1];

			$new_list->add($this->array_list[$i]);
		}

		// Okay, now $new_list should contain the correct values.
		// We will transfer over the reference.
		$this->array_list = $new_list->array_list;
		
				
	}



}

