-- --------------------------------------------------------
-- Table Definitions for MariaDB
-- Project name: 3dPrime
-- --------------------------------------------------------



CREATE TABLE IF NOT EXISTS permissions (
    permission_id INTEGER UNSIGNED NOT NULL AUTO_INCREMENT,
    group_name VARCHAR(80),                                                 -- template name for assigning pre-determined permissions to a user
    internal_name VARCHAR(80),                                              -- a non public name used for distingusing purpose of the group eg:- default 
    file_max_size INTEGER UNSIGNED NOT NULL DEFAULT 0,                      -- max size allowed for a single file for this user in MB
    files_expire_after INTEGER UNSIGNED NOT NULL DEFAULT 7,                 -- # of days before inactive files are deleted (inactive files are not part of an open job)
    files_total_count INTEGER UNSIGNED NOT NULL DEFAULT 0,                  -- total number of file allowed to be uploaded (regardless of size) for this user
    total_allocated_size INTEGER UNSIGNED NOT NULL DEFAULT 0,            -- total storage space alloted for the user
    manage_level TINYINT(3) UNSIGNED NOT NULL DEFAULT 0,                    -- what level of workflow_steps the user is allowed to manage
      
      INDEX idx_internal_name (internal_name),
    PRIMARY KEY (permission_id)
    
)
CHARACTER SET utf8mb4
ENGINE = InnoDB;

CREATE TABLE IF NOT EXISTS users (
    user_id INTEGER UNSIGNED NOT NULL AUTO_INCREMENT,
    email varchar(255) NOT NULL,
    permission_id INTEGER UNSIGNED NOT NULL,
    override_perm_id INTEGER UNSIGNED,
    fullname varchar(255) NOT NULL,
    lastname varchar(255) DEFAULT NULL, 
    pw_hash varchar(128) NOT NULL DEFAULT '!',
    created DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    verified DATETIME,                                                      -- when the current email address was verified; if null, then the email has not be verified
    onetime_token VARCHAR(32),                                              -- one time token for reset password
    onetime_token_expires DATETIME,                                         -- one time token expiration date
    onetime_repeat TINYINT(1) UNSIGNED NOT NULL DEFAULT 0,                  -- number of times a onetime token has been requested before it expires (to catch someone sending too many requests)
    removed DATETIME,                                                       -- when this user account was marked removed; if null, then user is current
    blocked DATETIME,                                                       -- when user was blocked; if null, then user is not blocked
    blocked_user_id INTEGER UNSIGNED,                                       -- who blocked this user
    blocked_notes TEXT,                                                     -- notes about why a persons was blocked/unblocked
    phone_num VARCHAR(16),
    hint VARCHAR(255),
    affiliation int(10) unsigned NOT NULL DEFAULT 1,
    department VARCHAR(255) DEFAULT NULL,
    okta_token VARCHAR(255) DEFAULT NULL,
    PRIMARY KEY (user_id),
    INDEX idx_user_fullname (fullname),
    INDEX idx_user_email (email),
    INDEX idx_user_created (created),
    INDEX idx_user_removed (removed),
    INDEX idx_user_blocked (blocked),
    CONSTRAINT fk_user_permission_id  FOREIGN KEY (permission_id) REFERENCES permissions (permission_id),
    CONSTRAINT fk_override_perm_id FOREIGN KEY (override_perm_id) REFERENCES permissions (permission_id)


)
CHARACTER SET utf8mb4
ENGINE = InnoDB
ROW_FORMAT=DYNAMIC;


-- files: tracking of successfully uploaded files (even after file has been deleted)
CREATE TABLE IF NOT EXISTS files (
    file_id INTEGER UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id INTEGER UNSIGNED NOT NULL,                              -- who uploaded this file
    file_name varchar(255) NOT NULL,                                -- the name of the file as it was uploaded
    sys_file_path varchar(255) NOT NULL,                            -- the file path of the saved file relative to the upload file directory
    created DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,            -- when the file record was created
    deleted DATETIME,                                               -- when the file was deleted
    file_size INTEGER UNSIGNED,                                     -- in bytes

    INDEX idx_file_created (created),
    INDEX idx_file_deleted (deleted),
    PRIMARY KEY (file_id),
    CONSTRAINT fk_files_user_id FOREIGN KEY (user_id) REFERENCES users (user_id)
    
)
CHARACTER SET utf8mb4
ENGINE = InnoDB;

--projects
CREATE TABLE IF NOT EXISTS projects (
  project_id int(10) unsigned NOT NULL AUTO_INCREMENT,
  project_name VARCHAR(255) DEFAULT NULL,
  created datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  removed datetime DEFAULT NULL,
  user_id int(10) unsigned DEFAULT NULL,
  submitted datetime DEFAULT NULL,

  PRIMARY KEY (project_id),
  INDEX idx_project_created (created)
)
CHARACTER SET utf8mb4
ENGINE = InnoDB
ROW_FORMAT=DYNAMIC;

-- cart
CREATE TABLE IF NOT EXISTS cart(
    cart_id INTEGER UNSIGNED NOT NULL AUTO_INCREMENT,
    file_id INTEGER UNSIGNED NOT NULL,
    user_id INTEGER UNSIGNED NOT NULL,
    project_id INTEGER UNSIGNED NOT NULL,
    created DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    submitted DATETIME ,
    removed DATETIME ,
    cart_data varchar(1024),
    
    PRIMARY KEY (cart_id),
    INDEX idx_cart_created (created),
    CONSTRAINT fk_file_id FOREIGN KEY (file_id) REFERENCES files(file_id),
    CONSTRAINT fk_project_id FOREIGN KEY (project_id) REFERENCES projects(project_id)
)
CHARACTER SET utf8mb4
ENGINE = InnoDB
ROW_FORMAT=DYNAMIC;

-- groups
CREATE TABLE IF NOT EXISTS `groups` (
  `group_id` INTEGER UNSIGNED NOT NULL AUTO_INCREMENT,
  `group_name` varchar(255) not null,
  `group_tag` VARCHAR(64) NOT NULL,
  `admin_email` VARCHAR(255) NOT NULL,
  `removed` datetime,
  PRIMARY KEY (group_id),
  INDEX idx_ugroup_tag(group_tag)
)
CHARACTER SET utf8mb4
ENGINE = InnoDB;

-- workflows
CREATE TABLE IF NOT EXISTS workflows (
    workflow_id INTEGER UNSIGNED NOT NULL AUTO_INCREMENT,
    name VARCHAR(64) NOT NULL, 
    data TEXT,                                               -- workflow name: e.g. 3D Print; Plotter Print; T-Shirt Print, etc
    allowed_ext_data VARCHAR(4000),                                    -- serialized arry that contatins the allowed file exts for the workflow
    workflow_removed datetime DEFAULT NULL,
    workflow_tag VARCHAR(64) DEFAULT NULL,
    group_id INTEGER UNSIGNED,
    disabled INTEGER UNSIGNED DEFAULT 0,

   PRIMARY KEY (workflow_id),
   CONSTRAINT fk_workflows_group_id FOREIGN KEY (group_id) REFERENCES groups(group_id)
)
CHARACTER SET utf8mb4
ENGINE = InnoDB;

-- Necessary workflow steps
CREATE TABLE IF NOT EXISTS workflow_step_type(
    workflow_step_type_id INTEGER UNSIGNED NOT NULL AUTO_INCREMENT,
    workflow_step_type_name varchar(64) NOT NULL,
    PRIMARY KEY(workflow_step_type_id)
)
CHARACTER SET utf8mb4
ENGINE = InnoDB;

CREATE TABLE IF NOT EXISTS workflow_steps (
    work_step_id INTEGER UNSIGNED NOT NULL AUTO_INCREMENT,
    name VARCHAR(64) NOT NULL,                                              -- identifier as to the step (that is, which class to call) to process this step. e.g. contact_info, select_files, approve_files, set_print_prices, print_tracking, hold_for_pickup, ready_to_ship, item_shipped, item_picked_up, item_abandoned                  -- descriptive name of workflow step: e.g. Submit, Pending approval, Cancelled, Ready for Printing, Printed, Ready for pickup, Delayed, Job Completed
    ordering INTEGER NOT NULL,                                              -- in what order the workflow progresses
    manage_level TINYINT(3) UNSIGNED DEFAULT 0 ,                             -- level of permissons for the required workflow step
    email_confirmation TINYINT(3) UNSIGNED DEFAULT 0 ,                             -- level of permissons for the required workflow step
    admin_status varchar(255) NOT NULL,
    user_status varchar(255) NOT NULL,
    step_type_id int(10) unsigned NOT NULL, 
    allow_cancellation int(10) unsigned NOT NULL DEFAULT 0,                   -- flag to determine if user can cancel on this step
    step_removed datetime DEFAULT NULL,
    INDEX idx_step_ordering (ordering),
    INDEX idx_step_name (name),
    
    PRIMARY KEY (work_step_id),
    CONSTRAINT fk_step_type_id FOREIGN KEY (step_type_id) REFERENCES workflow_step_type (workflow_step_type_id)
    
)
CHARACTER SET utf8mb4
ENGINE = InnoDB;

CREATE TABLE IF NOT EXISTS jobs (
    job_id INTEGER UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id INTEGER UNSIGNED NOT NULL,                                                   -- who created this job
    created DATETIME DEFAULT CURRENT_TIMESTAMP,                                 -- when the job was first created
    closed DATETIME DEFAULT NULL,
    project_id int(10) unsigned NOT NULL,
    curr_work_step_id int(10) unsigned NOT NULL,
    job_updated datetime DEFAULT NULL,
    INDEX idx_jobs_created (created),
    INDEX idx_jobs_closed (closed),
                                                   -- when the job was closed (completed, canceled, abandoned, etc)
    PRIMARY KEY(job_id),
    CONSTRAINT fk_jobs_user_id FOREIGN KEY (user_id) REFERENCES users (user_id),
    CONSTRAINT `fk_jobs_project_id` FOREIGN KEY (`project_id`) REFERENCES `projects` (`project_id`)
    
    
)
CHARACTER SET utf8mb4
ENGINE = InnoDB;

CREATE TABLE IF NOT EXISTS job_files (
    job_file_id INTEGER UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id INTEGER UNSIGNED NOT NULL,
    created DATETIME DEFAULT CURRENT_TIMESTAMP,
    removed DATETIME DEFAULT NULL,
    job_id int(10) unsigned NOT NULL,
    file_id int(10) unsigned NOT NULL,
    workflow_id int(10) unsigned NOT NULL,
    data VARCHAR(16000) DEFAULT NULL,                                                                  -- serialized information specific to this job in this step
    file_name varchar(255) NOT NULL,                                -- the name of the file as it was uploaded
    INDEX idx_jobs_created (created),
    INDEX idx_jobs_removed (removed),
    PRIMARY KEY(job_file_id),
    CONSTRAINT fk_job_files_job_id FOREIGN KEY (job_id) REFERENCES jobs (job_id),
    CONSTRAINT fk_job_files_file_id FOREIGN KEY (file_id) REFERENCES files (file_id),
    CONSTRAINT `fk_job_files_workflow_id` FOREIGN KEY (`workflow_id`) REFERENCES `workflows` (`workflow_id`)

)
CHARACTER SET utf8mb4
ENGINE = InnoDB;



-- job_steps: 
CREATE TABLE IF NOT EXISTS job_steps (
    job_step_id INTEGER UNSIGNED NOT NULL AUTO_INCREMENT,
    job_id INTEGER UNSIGNED NOT NULL,                                           -- the associated job
    work_step_id INTEGER UNSIGNED NOT NULL,                                     -- which workflow_step this matches
    data VARCHAR(16000) DEFAULT NULL,                                                                  -- serialized information specific to this job in this step
    completed DATETIME,                                                         -- when this step was marked as completed
    completed_user_id INTEGER UNSIGNED NOT NULL,                                -- who completed this step
    reset datetime DEFAULT NULL,                                                -- step has been resetted 


    INDEX idx_job_step_completed (completed),

    PRIMARY KEY(job_step_id),
    CONSTRAINT fk_jobstep_job_id FOREIGN KEY (job_id) REFERENCES jobs(job_id),
    CONSTRAINT fk_jobsteps_work_step_id FOREIGN KEY (work_step_id) REFERENCES workflow_steps (work_step_id)
    
)
CHARACTER SET utf8mb4
ENGINE = InnoDB;

-- job_prints: track the prints for a file for a specific job
CREATE TABLE IF NOT EXISTS job_prints (
    print_id INTEGER UNSIGNED NOT NULL AUTO_INCREMENT,
    job_id INTEGER UNSIGNED NOT NULL,
    file_id INTEGER UNSIGNED NOT NULL,
    created DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    success TINYINT(1) UNSIGNED NOT NULL,
    quantity TINYINT(2) UNSIGNED NOT NULL,                                      -- number of prints succeeded (or number of failed prints)
    result VARCHAR(128), 

    INDEX idx_jobs_prints_created (created),                                    -- allowed to enter notes about the print, typically if the print failed or was canceled mid-print
    
    PRIMARY KEY(print_id),
    CONSTRAINT fk_jobprint_job_id FOREIGN KEY (job_id) REFERENCES jobs(job_id),
    CONSTRAINT fk_jobprint_file_id FOREIGN KEY (file_id) REFERENCES files(file_id)
)
CHARACTER SET utf8mb4
ENGINE = InnoDB;

-- emails: a record of all email sent or that was attempted to be sent
CREATE TABLE IF NOT EXISTS emails (
    email_id INTEGER UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id INTEGER UNSIGNED NOT NULL,
    sent_user_id INTEGER UNSIGNED  NOT NULL DEFAULT 0, 
    sent_by_admin INTEGER UNSIGNED NOT NULL DEFAULT 0, 
    headers VARCHAR(255),                                                       -- any header for the email
    recipients VARCHAR(255),                                                    -- who the email was sent to
    subject VARCHAR(128),                                                       -- the subject of the email
    message TEXT,                                                               -- the content of the email
    created DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,                        -- when the email record was created
    sent DATETIME,                                                              -- null if the mail has not been sent, or the timestamp of when the email was successfully sent
    send_attempts TINYINT(1) NOT NULL DEFAULT 0,                                -- number of attempts to send the email (mail script should trigger failure after 3rd failed attempt)
    message_read datetime DEFAULT NULL,
    failure VARCHAR(64),                                                        -- if mail could not be sent, describe what went wrong (mail error message)

    INDEX idx_emails_created (created),
    PRIMARY KEY (email_id),
    CONSTRAINT fk_emails_email_id FOREIGN KEY (user_id) REFERENCES users(user_id)
)
CHARACTER SET utf8mb4
ENGINE = InnoDB;

--  attachments: keeps track of all the attchments associated with an email
CREATE TABLE IF NOT EXISTS attachments (
    attachment_id INTEGER UNSIGNED NOT NULL AUTO_INCREMENT,
    email_id INTEGER UNSIGNED NOT NULL,
    file_id INTEGER UNSIGNED NOT NULL,

    PRIMARY KEY(attachment_id),
    CONSTRAINT fk_attachments_email_id FOREIGN KEY (email_id) REFERENCES emails(email_id),
    CONSTRAINT fk_attachments_file_id FOREIGN KEY (file_id) REFERENCES files(file_id)
)
CHARACTER SET utf8mb4
ENGINE = InnoDB;

-- job_updates: track notes about job, both internally and for customer
CREATE TABLE IF NOT EXISTS job_updates (
    update_id INTEGER UNSIGNED NOT NULL AUTO_INCREMENT,
    job_id INTEGER UNSIGNED NOT NULL,                                           -- which job this update is for
    job_step_id INTEGER UNSIGNED NOT NULL,                                               -- if this update was associated with a specific job_step
    created DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,                        -- when this update was created
    removed DATETIME,                                                           -- if this update should be hidden from view (basically deleted from normal view)
    public_view TINYINT(1) NOT NULL DEFAULT 0,                                  -- whether this update can be viewed by the customer
    note TEXT,                                                                  -- the actual note/update
    email_id INTEGER UNSIGNED NOT NULL,                                                  -- if not null, then the email entry this update tried to send
    
    INDEX idx_jobs_updates_created (created),
    INDEX idx_jobs_updates_removed (removed), 

    PRIMARY KEY (update_id),
    CONSTRAINT fk_jobpupdate_job_id FOREIGN KEY (job_id) REFERENCES jobs(job_id), 
    CONSTRAINT fk_jobpupdate_job_step_id FOREIGN KEY (job_step_id) REFERENCES job_steps(job_step_id), 
    CONSTRAINT fk_jobpupdate_email_id FOREIGN KEY (email_id) REFERENCES emails(email_id)  
)
CHARACTER SET utf8mb4
ENGINE = InnoDB;

-- Notes : notes on any given job 
CREATE TABLE IF NOT exists notes(
    note_id INTEGER UNSIGNED NOT NULL AUTO_INCREMENT,
    job_id INTEGER UNSIGNED NOT NULL,
    added_user_id INTEGER UNSIGNED NOT NULL,
    note_text TEXT,
    note_created DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_note_created (note_created),
    PRIMARY KEY(note_id),
    CONSTRAINT fk_notes_job_id  FOREIGN KEY (job_id) REFERENCES jobs (job_id),
    CONSTRAINT fk_notes_added_user_id FOREIGN KEY (added_user_id) REFERENCES users(user_id)

)
CHARACTER SET utf8mb4
ENGINE = InnoDB;

-- TODO Sessions 
CREATE TABLE IF NOT EXISTS sessions (
    session_id INTEGER UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id INTEGER UNSIGNED NOT NULL,
    session_value varchar(255) NOT NULL,
    session_start DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    session_lastactive DATETIME,
    PRIMARY KEY (session_id),
    CONSTRAINT fk_sessions_user_id FOREIGN KEY (user_id) REFERENCES users (user_id)
)
CHARACTER SET utf8mb4
ENGINE = InnoDB;

-- TODO logs 
CREATE TABLE IF NOT EXISTS logs (
    log_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id INTEGER UNSIGNED,                                                   -- under which user this log entry was created
    log_stamp DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    log_message TEXT,
    PRIMARY KEY(log_id)
)
CHARACTER SET utf8mb4
ENGINE = InnoDB;

-- job holds table
CREATE TABLE IF NOT EXISTS job_holds (
    hold_id INTEGER UNSIGNED NOT NULL AUTO_INCREMENT,
    job_id INTEGER UNSIGNED NOT NULL,
    on_hold_step_id INTEGER UNSIGNED NOT NULL,                                  -- the step which job has been put on hold
    hold_placed DATETIME DEFAULT NULL,
    hold_released DATETIME DEFAULT NULL,
    completed_user_id int(10) unsigned NOT NULL,
    INDEX idx_job_holds_hold_placed (hold_placed),
    INDEX idx_job_holds_hold_released (hold_released),
    PRIMARY KEY(hold_id),
    CONSTRAINT fk_job_holds_completed_user_id FOREIGN KEY (completed_user_id) REFERENCES users (user_id),
    CONSTRAINT fk_job_holds_job_id FOREIGN KEY (job_id) REFERENCES jobs(job_id),
    CONSTRAINT fk_job_holds_on_hold_step_id FOREIGN KEY (on_hold_step_id) REFERENCES job_steps(job_step_id)

) 
CHARACTER SET utf8mb4
ENGINE=InnoDB;


CREATE TABLE IF NOT EXISTS `cancellation_reasons` (
  `cancellation_reason_id` int(11) NOT NULL AUTO_INCREMENT,
  `for_staff` tinyint(1) DEFAULT NULL,
  `reason` tinytext NOT NULL,
  `more_information` tinyint(1) DEFAULT NULL,
  PRIMARY KEY (`cancellation_reason_id`)
) 
CHARACTER SET utf8mb4
ENGINE = InnoDB;

CREATE TABLE IF NOT EXISTS `cancellations` (
  `cancellation_id` int(11) NOT NULL AUTO_INCREMENT,
  `job_id` int(11) NOT NULL,
  `reason_id` int(11) DEFAULT NULL,
  `more_reason` tinytext DEFAULT NULL,
  PRIMARY KEY (`cancellation_id`),
  KEY `fk_reason_id` (`reason_id`),
  CONSTRAINT `fk_reason_id` FOREIGN KEY (`reason_id`) REFERENCES `cancellation_reasons` (`cancellation_reason_id`)
) 
CHARACTER SET utf8mb4
ENGINE=InnoDB;

-- Assessment Questions tables

CREATE TABLE IF NOT EXISTS assessment_q_types (
    qtype_id INTEGER UNSIGNED NOT NULL AUTO_INCREMENT,
    question_type VARCHAR(80) NOT NULL,
    has_choices BOOLEAN DEFAULT FALSE,
    PRIMARY KEY (qtype_id)
)
CHARACTER SET utf8mb4
ENGINE = InnoDB;

-- todo: add ordering?
CREATE TABLE IF NOT EXISTS assessment_questions (
    question_id INTEGER UNSIGNED NOT NULL AUTO_INCREMENT,
    qtype_id INTEGER UNSIGNED NOT NULL,
    question_text VARCHAR(255) NOT NULL,
    question_removed datetime DEFAULT NULL,
    PRIMARY KEY (question_id),
    CONSTRAINT fk_qtype_id FOREIGN KEY (qtype_id) REFERENCES assessment_q_types (qtype_id)
)
CHARACTER SET utf8mb4
ENGINE = InnoDB;

CREATE TABLE IF NOT EXISTS assessment_answers (
    answer_id INTEGER UNSIGNED NOT NULL AUTO_INCREMENT,
    question_id INTEGER UNSIGNED NOT NULL,
    answer_text VARCHAR(255) NOT NULL,
    PRIMARY KEY (answer_id),
    CONSTRAINT fk_question_id FOREIGN KEY (question_id) REFERENCES assessment_questions (question_id)
)
CHARACTER SET utf8mb4
ENGINE = InnoDB;

-- Multiple choice / pick one answer choices
CREATE TABLE IF NOT EXISTS assessment_q_mc_choices (
    option_id INTEGER UNSIGNED NOT NULL AUTO_INCREMENT,
    question_id INTEGER UNSIGNED NOT NULL,
    option_text VARCHAR(255) NOT NULL,
    PRIMARY KEY (option_id),
    CONSTRAINT fk_mcq_question_id FOREIGN KEY (question_id) REFERENCES assessment_questions (question_id)
)
CHARACTER SET utf8mb4
ENGINE = InnoDB;