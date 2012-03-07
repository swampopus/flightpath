/* Table structure for table `administrators` */
CREATE TABLE `administrators` (
  `faculty_id` int(11) NOT NULL,
  `comment` text NOT NULL,
  PRIMARY KEY  (`faculty_id`)
);

/* Table structure for table `advised_courses` */
CREATE TABLE `advised_courses` (
  `id` int(11) NOT NULL auto_increment,
  `advising_session_id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `entry_value` varchar(20) NOT NULL,
  `semester_num` int(11) NOT NULL,
  `group_id` int(11) NOT NULL,
  `var_hours` int(11) NOT NULL default '0',
  `term_id` int(11) NOT NULL,
  PRIMARY KEY  (`id`),
  KEY `advid` (`advising_session_id`),
  KEY `course_id` (`course_id`),
  KEY `ev` (`entry_value`)
);

/* Table structure for table `advising_comments` */
CREATE TABLE `advising_comments` (
  `id` int(11) NOT NULL auto_increment,
  `student_id` int(11) NOT NULL,
  `faculty_id` int(11) NOT NULL,
  `term_id` int(11) NOT NULL,
  `comment` text NOT NULL,
  `datetime` datetime NOT NULL,
  `access_type` varchar(20) NOT NULL,
  `delete_flag` tinyint(4) NOT NULL,
  PRIMARY KEY  (`id`),
  KEY `student_id` (`student_id`), 
  KEY `delete_flag` (`delete_flag`)
);

/* Table structure for table `advising_sessions` */
CREATE TABLE `advising_sessions` (
  `advising_session_id` int(11) NOT NULL auto_increment,
  `student_id` int(11) NOT NULL,
  `faculty_id` int(11) NOT NULL,
  `term_id` int(11) NOT NULL,
  `degree_id` int(11) NOT NULL,
  `major_code` varchar(20) NOT NULL,
  `track_code` varchar(20) NOT NULL,
  `catalog_year` int(11) NOT NULL,
  `datetime` datetime NOT NULL,
  `is_whatif` tinyint(4) NOT NULL default '0',
  `is_draft` tinyint(4) NOT NULL default '0',
  `is_empty` tinyint(4) NOT NULL,
  PRIMARY KEY  (`advising_session_id`),
  KEY `sid` (`student_id`),
  KEY `termid` (`term_id`)
);

/* Table structure for table `course_rotation_schedule` */
CREATE TABLE `course_rotation_schedule` (
  `id` int(11) NOT NULL auto_increment,
  `faculty_id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `term_id` varchar(20) NOT NULL,
  `entry_value` varchar(20) NOT NULL,
  `datetime` datetime NOT NULL,
  PRIMARY KEY  (`id`)
);

/* Table structure for table `course_syllabi` */
CREATE TABLE `course_syllabi` (
  `id` int(11) NOT NULL auto_increment,
  `course_id` int(11) NOT NULL,
  `course_perm_id` varchar(20) NOT NULL,
  `filename` varchar(255) NOT NULL,
  `datetime` datetime NOT NULL,
  PRIMARY KEY  (`id`)
);

/* Table structure for table `courses` */
CREATE TABLE `courses` (
  `id` int(11) NOT NULL auto_increment,
  `course_id` int(11) NOT NULL,
  `subject_id` varchar(10) NOT NULL,
  `course_num` varchar(10) NOT NULL,
  `catalog_year` int(11) NOT NULL default '2006',
  `title` text NOT NULL,
  `description` text NOT NULL,
  `min_hours` int(11) NOT NULL,
  `max_hours` int(11) NOT NULL,
  `repeat_hours` int(11) NOT NULL,
  `exclude` tinyint(4) NOT NULL default '0',
  `data_entry_comment` text NOT NULL,
  `delete_flag` tinyint(4) NOT NULL default '0',
  PRIMARY KEY  (`id`),
  KEY `course_id` (`course_id`),
  KEY `subject_id` (`subject_id`),
  KEY `course_num` (`course_num`),
  KEY `catalog_year` (`catalog_year`)
);

/* Table structure for table `degree_requirements` */
CREATE TABLE `degree_requirements` (
  `id` int(11) NOT NULL auto_increment,
  `degree_id` int(11) NOT NULL,
  `semester_num` int(11) NOT NULL,
  `group_id` int(11) NOT NULL,
  `group_requirement_type` varchar(10) NOT NULL,
  `group_hours_required` int(11) NOT NULL,
  `group_min_grade` varchar(10) NOT NULL,
  `course_id` int(11) NOT NULL,
  `course_min_grade` varchar(10) NOT NULL,
  `course_requirement_type` varchar(10) NOT NULL,
  `data_entry_value` varchar(50) NOT NULL,
  PRIMARY KEY  (`id`),
  KEY `degree_id` (`degree_id`),
  KEY `group_id` (`group_id`),
  KEY `dev` (`data_entry_value`)
);

/* Table structure for table `degree_tracks` */
CREATE TABLE `degree_tracks` (
  `track_id` int(11) NOT NULL auto_increment,
  `catalog_year` int(11) NOT NULL default '2006',
  `major_code` varchar(10) NOT NULL,
  `track_code` varchar(10) NOT NULL,
  `track_title` varchar(100) NOT NULL,
  `track_short_title` varchar(50) NOT NULL,
  `track_description` text NOT NULL,
  PRIMARY KEY  (`track_id`)
);

/* Table structure for table `degrees` */
CREATE TABLE `degrees` (
  `id` int(11) NOT NULL auto_increment,
  `degree_id` int(11) NOT NULL,
  `major_code` varchar(20) NOT NULL,
  `degree_type` varchar(20) NOT NULL,
  `degree_class` varchar(5) NOT NULL,
  `title` varchar(200) NOT NULL,
  `public_note` text NOT NULL,
  `semester_titles_csv` text NOT NULL,
  `catalog_year` int(11) NOT NULL default '2006',
  `exclude` int(11) NOT NULL default '0',
  PRIMARY KEY  (`id`),
  KEY `degree_id` (`degree_id`)
);

/* Table structure for table `draft_courses` */
CREATE TABLE `draft_courses` (
  `id` int(11) NOT NULL auto_increment,
  `course_id` int(11) NOT NULL,
  `subject_id` varchar(10) NOT NULL,
  `course_num` varchar(10) NOT NULL,
  `catalog_year` int(11) NOT NULL default '2006',
  `title` text NOT NULL,
  `description` text NOT NULL,
  `min_hours` int(11) NOT NULL,
  `max_hours` int(11) NOT NULL,
  `repeat_hours` int(11) NOT NULL,
  `exclude` tinyint(4) NOT NULL default '0',
  `data_entry_comment` text NOT NULL,
  `delete_flag` tinyint(4) NOT NULL default '0',
  PRIMARY KEY  (`id`),
  KEY `course_id` (`course_id`),
  KEY `subject_id` (`subject_id`),
  KEY `course_num` (`course_num`),
  KEY `catalog_year` (`catalog_year`)
);

/* Table structure for table `draft_degree_requirements` */
CREATE TABLE `draft_degree_requirements` (
  `id` int(11) NOT NULL auto_increment,
  `degree_id` int(11) NOT NULL,
  `semester_num` int(11) NOT NULL,
  `group_id` int(11) NOT NULL,
  `group_requirement_type` varchar(10) NOT NULL,
  `group_hours_required` int(11) NOT NULL,
  `group_min_grade` varchar(10) NOT NULL,
  `course_id` int(11) NOT NULL,
  `course_min_grade` varchar(10) NOT NULL,
  `course_requirement_type` varchar(10) NOT NULL,
  `data_entry_value` varchar(50) NOT NULL,
  PRIMARY KEY  (`id`),
  KEY `degree_id` (`degree_id`),
  KEY `group_id` (`group_id`),
  KEY `dev` (`data_entry_value`)
);

/* Table structure for table `draft_degree_tracks` */
CREATE TABLE `draft_degree_tracks` (
  `track_id` int(11) NOT NULL auto_increment,
  `catalog_year` int(11) NOT NULL default '2006',
  `major_code` varchar(10) NOT NULL,
  `track_code` varchar(10) NOT NULL,
  `track_title` varchar(100) NOT NULL,
  `track_short_title` varchar(50) NOT NULL,
  `track_description` text NOT NULL,
  PRIMARY KEY  (`track_id`)
);

/* Table structure for table `draft_degrees` */
CREATE TABLE `draft_degrees` (
  `id` int(11) NOT NULL auto_increment,
  `degree_id` int(11) NOT NULL,
  `major_code` varchar(20) NOT NULL,
  `degree_type` varchar(20) NOT NULL,
  `degree_class` varchar(5) NOT NULL,
  `title` varchar(200) NOT NULL,
  `public_note` text NOT NULL,
  `semester_titles_csv` text NOT NULL,
  `catalog_year` int(11) NOT NULL default '2006',
  `exclude` int(11) NOT NULL default '0',
  PRIMARY KEY  (`id`),
  KEY `degree_id` (`degree_id`)
);

/* Table structure for table `draft_group_requirements` */
CREATE TABLE `draft_group_requirements` (
  `id` int(11) NOT NULL auto_increment,
  `group_id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `course_min_grade` varchar(10) NOT NULL,
  `course_repeats` int(11) NOT NULL default '0',
  `child_group_id` int(11) NOT NULL,
  `data_entry_value` varchar(50) NOT NULL,
  PRIMARY KEY  (`id`),
  KEY `group_id` (`group_id`),
  KEY `dev` (`data_entry_value`)
);

/* Table structure for table `draft_groups` */
CREATE TABLE `draft_groups` (
  `id` int(11) NOT NULL auto_increment,
  `group_id` int(11) NOT NULL,
  `group_name` varchar(200) NOT NULL,
  `title` varchar(255) NOT NULL,
  `definition` text NOT NULL,
  `icon_filename` text NOT NULL,
  `catalog_year` int(11) NOT NULL,
  `priority` int(11) NOT NULL default '50',
  `delete_flag` tinyint(4) NOT NULL default '0',
  `data_entry_comment` text NOT NULL,
  PRIMARY KEY  (`id`),
  KEY `group_id` (`group_id`),
  KEY `group_name` (`group_name`),
  KEY `catalog_year` (`catalog_year`),
  KEY `title` (`title`)
);

/* Table structure for table `draft_instructions` */
CREATE TABLE `draft_instructions` (
  `id` int(11) NOT NULL auto_increment,
  `instruction` text NOT NULL,
  PRIMARY KEY  (`id`)
);

/* Table structure for table `flightpath_settings` */
CREATE TABLE `flightpath_settings` (
  `variable_name` varchar(100) NOT NULL,
  `value` text NOT NULL,
  `comment` text NOT NULL,
  PRIMARY KEY  (`variable_name`)
);

/* Table structure for table `flightpath_system_settings` */
CREATE TABLE `flightpath_system_settings` (
  `name` varchar(255) NOT NULL,
  `value` text NOT NULL,
  PRIMARY KEY  (`name`)
);

/* Table structure for table `group_requirements` */
CREATE TABLE `group_requirements` (
  `id` int(11) NOT NULL auto_increment,
  `group_id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `course_min_grade` varchar(10) NOT NULL,
  `course_repeats` int(11) NOT NULL default '0',
  `child_group_id` int(11) NOT NULL,
  `data_entry_value` varchar(50) NOT NULL,
  PRIMARY KEY  (`id`),
  KEY `group_id` (`group_id`),
  KEY `dev` (`data_entry_value`)
);

/* Table structure for table `groups` */
CREATE TABLE `groups` (
  `id` int(11) NOT NULL auto_increment,
  `group_id` int(11) NOT NULL,
  `group_name` varchar(200) NOT NULL,
  `title` varchar(255) NOT NULL,
  `definition` text NOT NULL,
  `icon_filename` text NOT NULL,
  `catalog_year` int(11) NOT NULL,
  `priority` int(11) NOT NULL default '50',
  `delete_flag` tinyint(4) NOT NULL default '0',
  `data_entry_comment` text NOT NULL,
  PRIMARY KEY  (`id`),
  KEY `group_id` (`group_id`),
  KEY `group_name` (`group_name`),
  KEY `catalog_year` (`catalog_year`),
  KEY `title` (`title`)
);

/* Table structure for table `help` */
CREATE TABLE `help` (
  `id` int(11) NOT NULL auto_increment,
  `title` varchar(200) NOT NULL,
  `body` text NOT NULL,
  PRIMARY KEY  (`id`)
);

/* Table structure for table `log` */
CREATE TABLE `log` (
  `id` int(11) NOT NULL auto_increment,
  `user_id` int(11) NOT NULL,
  `user_name` varchar(30) NOT NULL,
  `user_type` varchar(30) NOT NULL,
  `action` varchar(50) NOT NULL,
  `extra_data` varchar(100) NOT NULL,
  `notes` text NOT NULL,
  `ip` varchar(20) NOT NULL,
  `datetime` datetime NOT NULL,
  `from_url` varchar(50) NOT NULL,
  PRIMARY KEY  (`id`),
  KEY `user_type` (`user_id`),
  KEY `action` (`action`),
  KEY `dt` (`datetime`),
  KEY `url` (`from_url`),
  KEY `extra data` (`extra_data`),
  KEY `usertype` (`user_type`)
);

/* Table structure for table `student_settings` */
CREATE TABLE `student_settings` (
  `student_id` int(11) NOT NULL,
  `settings_xml` text NOT NULL,
  `datetime` datetime NOT NULL,
  PRIMARY KEY  (`student_id`)
);

/* Table structure for table `student_substitutions` */
CREATE TABLE `student_substitutions` (
  `id` int(11) NOT NULL auto_increment,
  `student_id` int(11) NOT NULL,
  `faculty_id` int(11) NOT NULL,
  `required_course_id` int(11) NOT NULL,
  `required_entry_value` varchar(20) NOT NULL,
  `required_group_id` int(11) NOT NULL,
  `required_semester_num` int(11) NOT NULL,
  `sub_course_id` int(11) NOT NULL,
  `sub_entry_value` varchar(20) NOT NULL,
  `sub_term_id` int(11) NOT NULL,
  `sub_transfer_flag` tinyint(4) NOT NULL,
  `sub_hours` tinyint(4) NOT NULL,
  `sub_remarks` text NOT NULL,
  `datetime` datetime NOT NULL,
  `delete_flag` tinyint(4) NOT NULL,
  PRIMARY KEY  (`id`),
  KEY `student_id` (`student_id`),
  KEY `rev` (`required_entry_value`),
  KEY `sev` (`sub_entry_value`)
);

/* Table structure for table `student_unassign_group` */
CREATE TABLE `student_unassign_group` (
  `id` int(11) NOT NULL auto_increment,
  `student_id` int(11) NOT NULL,
  `faculty_id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `term_id` int(11) NOT NULL,
  `transfer_flag` tinyint(4) NOT NULL default '0',
  `group_id` int(11) NOT NULL,
  `delete_flag` tinyint(4) NOT NULL default '0',
  `datetime` datetime NOT NULL,
  PRIMARY KEY  (`id`)
);

/* Table structure for table `student_unassign_transfer_eqv` */
CREATE TABLE `student_unassign_transfer_eqv` (
  `id` int(11) NOT NULL auto_increment,
  `student_id` int(11) NOT NULL,
  `faculty_id` int(11) NOT NULL,
  `transfer_course_id` int(11) NOT NULL,
  `delete_flag` tinyint(4) NOT NULL default '0',
  `datetime` datetime NOT NULL,
  PRIMARY KEY  (`id`)
);

/* Table structure for table `user_settings` */
CREATE TABLE `user_settings` (
  `user_id` int(11) NOT NULL,
  `settings_xml` text NOT NULL,
  `datetime` datetime NOT NULL,
  PRIMARY KEY  (`user_id`)
);

/* Table structure for table `users` */
CREATE TABLE `users` (
  `faculty_id` int(11) NOT NULL,
  `user_type` varchar(50) NOT NULL default 'none',
  `permissions` text NOT NULL,
  PRIMARY KEY  (`faculty_id`)
);

/* Table structure for table `variables` */
CREATE TABLE `variables` (
  `name` varchar(255) NOT NULL,
  `value` text NOT NULL,
  PRIMARY KEY  (`name`)
);