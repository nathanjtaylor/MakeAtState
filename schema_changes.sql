CREATE TABLE `cancellation_reasons` (
  `cancellation_reason_id` int(11) NOT NULL AUTO_INCREMENT,
  `for_staff` tinyint(1) DEFAULT NULL,
  `reason` tinytext NOT NULL,
  `more_information` tinyint(1) DEFAULT NULL,
  PRIMARY KEY (`cancellation_reason_id`)
) ENGINE=InnoDB AUTO_INCREMENT=24 DEFAULT CHARSET=utf8mb4

INSERT INTO cancellation_reasons (`for_staff`, `reason`, `more_information`) VALUES(0, "Printed somewhere else at MSU", 0), (0, "Estimated completion date was too long", 0), (0, "Needed a different material", 0), (0, "No longer wanted", 0), (0, "Too expensive", 0),(1, "Patron did not respond", 0),(1, "Patron cancelled in person, or via phone, email, etc.", 0), (1, "Issue with design that could not be solved", 0),(1, "Other", 1), (0, "Other", 1) ;

CREATE TABLE `cancellations` (
  `cancellation_id` int(11) NOT NULL AUTO_INCREMENT,
  `job_id` int(11) NOT NULL,
  `reason_id` int(11) DEFAULT NULL,
  `more_reason` tinytext DEFAULT NULL,
  PRIMARY KEY (`cancellation_id`),
  KEY `fk_reason_id` (`reason_id`),
  CONSTRAINT `fk_reason_id` FOREIGN KEY (`reason_id`) REFERENCES `cancellation_reasons` (`cancellation_reason_id`)
) ENGINE=InnoDB AUTO_INCREMENT=84 DEFAULT CHARSET=utf8mb4


CREATE TABLE `job_holds` (
  `hold_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `job_id` int(10) unsigned NOT NULL,
  `on_hold_step_id` int(10) unsigned NOT NULL,
  `hold_placed` datetime DEFAULT NULL,
  `hold_released` datetime DEFAULT NULL,
  `completed_user_id` int(10) unsigned NOT NULL DEFAULT 188,
  PRIMARY KEY (`hold_id`),
  KEY `idx_job_holds_hold_placed` (`hold_placed`),
  KEY `idx_job_holds_hold_released` (`hold_released`),
  KEY `fk_job_holds_job_id` (`job_id`),
  KEY `fk_job_holds_on_hold_step_id` (`on_hold_step_id`),
  KEY `fk_job_holds_completed_user_id` (`completed_user_id`),
  CONSTRAINT `fk_job_holds_completed_user_id` FOREIGN KEY (`completed_user_id`) REFERENCES `users` (`user_id`),
  CONSTRAINT `fk_job_holds_job_id` FOREIGN KEY (`job_id`) REFERENCES `jobs` (`job_id`),
  CONSTRAINT `fk_job_holds_on_hold_step_id` FOREIGN KEY (`on_hold_step_id`) REFERENCES `job_steps` (`job_step_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2522 DEFAULT CHARSET=utf8mb4

/* Changes for make central expansion - Nov 5, 2020*/
CREATE TABLE IF NOT EXISTS `groups` (
    `group_id` INTEGER UNSIGNED NOT NULL AUTO_INCREMENT,
    `group_tag` VARCHAR(64) NOT NULL,
    `admin_email` VARCHAR(255) NOT NULL,
    PRIMARY KEY (group_id),
    INDEX idx_ugroup_tag(group_tag)
)
CHARACTER SET utf8mb4
ENGINE = InnoDB
ROW_FORMAT=DYNAMIC;

ALTER TABLE 3dprime.groups
ADD group_name varchar(255) not null;

ALTER TABLE 3dprime.groups
ADD removed datetime;

ALTER TABLE workflows ADD group_id INTEGER UNSIGNED ;
ALTER TABLE workflows  ADD CONSTRAINT `fk_workflows_group_id` FOREIGN KEY (`group_id`) REFERENCES `groups` (`group_id`);

/* Changes for make central expansion - END*/

/* Changes to add disabled flag to the workflows table */

ALTER TABLE workflows ADD disabled INTEGER UNSIGNED DEFAULT NULL;

 /* Changes to add disabled flag to the workflows table - END */

/* Change the data from varchar to text in the workflows table as these are getting bigger */
ALTER TABLE workflows MODIFY COLUMN data TEXT
/* Change the data from varchar to text in the workflows table as these are getting bigger - END */

/* Change the data from varchar to text in the cart table to allow for long notes */
ALTER TABLE cart MODIFY COLUMN cart_data TEXT
/* Change the data from varchar to text in the cart table to allow for long notes - END */


