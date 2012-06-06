
SET FOREIGN_KEY_CHECKS=0;
-- ----------------------------
-- Table structure for advisor_student
-- ----------------------------
DROP TABLE IF EXISTS `advisor_student`;
CREATE TABLE `advisor_student` (
  `faculty_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  PRIMARY KEY  (`faculty_id`,`student_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- ----------------------------
-- Records of advisor_student
-- ----------------------------
 
-- ----------------------------
-- Table structure for faculty_staff
-- ----------------------------
DROP TABLE IF EXISTS `faculty_staff`;
CREATE TABLE `faculty_staff` (
  `faculty_id` int(11) NOT NULL,
  `f_name` varchar(20) NOT NULL,
  `l_name` varchar(20) NOT NULL,
  `mid_name` varchar(20) NOT NULL,
  `major_code` varchar(20) NOT NULL,
  `dept_name` varchar(100) NOT NULL,
  `college_name` varchar(100) NOT NULL,
  `employee_type` varchar(10) NOT NULL,
  PRIMARY KEY  (`faculty_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- ----------------------------
-- Records of faculty_staff
-- ----------------------------
INSERT INTO `faculty_staff` VALUES ('200', 'Bob', 'Teacherman', 'Q', 'ENGL', 'English', 'AS', '10');

-- ----------------------------
-- Table structure for student_courses
-- ----------------------------
DROP TABLE IF EXISTS `student_courses`;
CREATE TABLE `student_courses` (
  `id` int(11) NOT NULL auto_increment,
  `student_id` int(11) NOT NULL,
  `subject_id` varchar(10) NOT NULL,
  `course_num` varchar(10) NOT NULL,
  `hours_awarded` int(11) NOT NULL,
  `grade` varchar(5) NOT NULL,
  `term_id` varchar(10) NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=9 DEFAULT CHARSET=latin1;

-- ----------------------------
-- Records of student_courses
-- ----------------------------
INSERT INTO `student_courses` VALUES ('1', '500', 'MATH', '111', '3', 'A', '200460');
INSERT INTO `student_courses` VALUES ('2', '500', 'ENGL', '101', '3', 'C', '200540');
INSERT INTO `student_courses` VALUES ('3', '500', 'CSCI', '340', '3', 'BMID', '200540');
INSERT INTO `student_courses` VALUES ('4', '501', 'CSCI', '200', '3', 'A', '200540');
INSERT INTO `student_courses` VALUES ('5', '501', 'CSCI', '203', '3', 'B', '200540');
INSERT INTO `student_courses` VALUES ('6', '501', 'HIST', '111', '3', 'C', '200540');
INSERT INTO `student_courses` VALUES ('7', '501', 'HIST', '112', '3', 'F', '200440');
INSERT INTO `student_courses` VALUES ('8', '501', 'MATH', '240', '3', 'B', '200540');

-- ----------------------------
-- Table structure for student_developmentals
-- ----------------------------
DROP TABLE IF EXISTS `student_developmentals`;
CREATE TABLE `student_developmentals` (
  `student_id` int(11) NOT NULL,
  `requirement` varchar(15) NOT NULL,
  PRIMARY KEY  (`student_id`,`requirement`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- ----------------------------
-- Records of student_developmentals
-- ----------------------------

-- ----------------------------
-- Table structure for student_tests
-- ----------------------------
DROP TABLE IF EXISTS `student_tests`;
CREATE TABLE `student_tests` (
  `id` int(11) NOT NULL auto_increment,
  `student_id` int(11) NOT NULL,
  `test_id` varchar(20) NOT NULL,
  `category_id` varchar(20) NOT NULL,
  `score` varchar(10) NOT NULL,
  `datetime` datetime NOT NULL,
  PRIMARY KEY  (`id`),
  KEY `student_id` (`student_id`)
) ENGINE=MyISAM AUTO_INCREMENT=853575 DEFAULT CHARSET=latin1;

-- ----------------------------
-- Records of student_tests
-- ----------------------------

-- ----------------------------
-- Table structure for student_transfer_courses
-- ----------------------------
DROP TABLE IF EXISTS `student_transfer_courses`;
CREATE TABLE `student_transfer_courses` (
  `student_id` int(11) NOT NULL,
  `transfer_course_id` int(11) NOT NULL,
  `student_specific_course_title` varchar(255) NOT NULL,
  `term_id` varchar(10) NOT NULL,
  `grade` varchar(5) NOT NULL,
  `hours_awarded` int(11) NOT NULL,
  PRIMARY KEY  (`student_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- ----------------------------
-- Records of student_transfer_courses
-- ----------------------------

-- ----------------------------
-- Table structure for students
-- ----------------------------
DROP TABLE IF EXISTS `students`;
CREATE TABLE `students` (
  `student_id` int(11) NOT NULL,
  `f_name` varchar(20) NOT NULL,
  `l_name` varchar(20) NOT NULL,
  `mid_name` varchar(20) NOT NULL,
  `cumulative_hours` varchar(5) NOT NULL,
  `gpa` varchar(5) NOT NULL,
  `rank_code` varchar(5) NOT NULL,
  `major_code` varchar(20) NOT NULL,
  `catalog_year` int(11) NOT NULL,
  PRIMARY KEY  (`student_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- ----------------------------
-- Records of students
-- ----------------------------
INSERT INTO `students` VALUES ('500', 'John', 'Smith', '', '10', '3.0', 'FR', 'COSC', '2006');
INSERT INTO `students` VALUES ('501', 'Jane', 'Smith', '', '15', '2.6', 'JR', 'ENGL', '206');

-- ----------------------------
-- Table structure for subjects
-- ----------------------------
DROP TABLE IF EXISTS `subjects`;
CREATE TABLE `subjects` (
  `subject_id` varchar(10) NOT NULL,
  `title` varchar(255) NOT NULL,
  PRIMARY KEY  (`subject_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- ----------------------------
-- Records of subjects
-- ----------------------------

-- ----------------------------
-- Table structure for tests
-- ----------------------------
DROP TABLE IF EXISTS `tests`;
CREATE TABLE `tests` (
  `id` int(11) NOT NULL auto_increment,
  `test_id` varchar(20) NOT NULL,
  `category_id` varchar(20) NOT NULL,
  `position` int(11) NOT NULL,
  `test_description` varchar(200) NOT NULL,
  `category_description` varchar(200) NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=380 DEFAULT CHARSET=latin1;

-- ----------------------------
-- Records of tests
-- ----------------------------

-- ----------------------------
-- Table structure for transfer_courses
-- ----------------------------
DROP TABLE IF EXISTS `transfer_courses`;
CREATE TABLE `transfer_courses` (
  `transfer_course_id` int(11) NOT NULL auto_increment,
  `institution_id` varchar(10) NOT NULL,
  `subject_id` varchar(10) NOT NULL,
  `course_num` varchar(10) NOT NULL,
  `title` varchar(100) NOT NULL,
  `description` text NOT NULL,
  `min_hours` int(11) NOT NULL,
  `max_hours` int(11) NOT NULL,
  PRIMARY KEY  (`transfer_course_id`),
  KEY `ic` (`institution_id`),
  KEY `si` (`subject_id`),
  KEY `cn` (`course_num`)
) ENGINE=MyISAM AUTO_INCREMENT=104206 DEFAULT CHARSET=latin1;

-- ----------------------------
-- Records of transfer_courses
-- ----------------------------

-- ----------------------------
-- Table structure for transfer_eqv_per_student
-- ----------------------------
DROP TABLE IF EXISTS `transfer_eqv_per_student`;
CREATE TABLE `transfer_eqv_per_student` (
  `id` int(11) NOT NULL auto_increment,
  `student_id` int(11) NOT NULL,
  `transfer_course_id` int(11) NOT NULL,
  `local_course_id` int(11) NOT NULL,
  `valid_term_id` int(11) NOT NULL,
  `broken_id` int(11) NOT NULL,
  PRIMARY KEY  (`id`),
  KEY `student_id` (`student_id`)
) ENGINE=MyISAM AUTO_INCREMENT=128904 DEFAULT CHARSET=latin1;

-- ----------------------------
-- Records of transfer_eqv_per_student
-- ----------------------------

-- ----------------------------
-- Table structure for transfer_institutions
-- ----------------------------
DROP TABLE IF EXISTS `transfer_institutions`;
CREATE TABLE `transfer_institutions` (
  `institution_id` varchar(10) NOT NULL,
  `name` varchar(200) NOT NULL,
  `state` varchar(10) NOT NULL,
  PRIMARY KEY  (`institution_id`),
  KEY `state` (`state`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- ----------------------------
-- Records of transfer_institutions
-- ----------------------------
