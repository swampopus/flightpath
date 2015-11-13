<?php

class _SubstitutionList extends ObjList
{

	function find_requirement($course_requirement, $bool_exclude_has_been_applied = false, $group_id = 0)
	{
		// Look through the array for a substitution which has this
		// course requirement.
		// If group_id == -1, no particular group is required.
		for ($t = 0; $t < count($this->array_list); $t++)
		{
			$substitution = $this->array_list[$t];

			if ($bool_exclude_has_been_applied == true && $substitution->bool_has_been_applied == true)
			{
				// Skip substitutions which we have already applied.
				continue;
			}

			$cr = $substitution->course_requirement;
			//adminDebug($cr->course_id . " " . $course_requirement->course_id);
			if ($group_id == -1)
			{ // No particular group_id is required...
				if ($cr->course_id == $course_requirement->course_id)
				{
					return $substitution;
				}
			} else {
				// ONLY check if it's in the supplied group_id...
				if ($cr->course_id == $course_requirement->course_id && $cr->get_bool_assigned_to_group_id($group_id))
				{
					return $substitution;
				}

			}
		}

		return false;
	}

	function find_group_additions(Group $group)
	{
		$group_id = $group->group_id;
		$rtn_list = new CourseList();
		// Find additions for this group_id and return them.
		for ($t = 0; $t < count($this->array_list); $t++)
		{
			$substitution = $this->array_list[$t];
			if ($substitution->bool_group_addition == true)
			{
				$c = $substitution->course_requirement;
				if ($c->get_bool_assigned_to_group_id($group_id))
				{
					$cc = $substitution->course_list_substitutions->get_first();
					//adminDebug("~~ $cc->course_id");
					$rtn_list->add($cc);
				}
			}

		}

		if (!$rtn_list->is_empty)
		{
			return $rtn_list;
		} else {
			return false;
		}

	}


} // class SubstitutionList
