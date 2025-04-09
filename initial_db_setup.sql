-- Insert values into permissions table
INSERT IGNORE INTO `permissions` VALUES
(1,NULL,NULL,256,30,10,0,0),
(2,'Library patron','users',256,30,10,268435456,0),
(3,'Student staff','student_staff',256,30,10,268435456,1),
(4,'Staff','staff',512,180,20,268435456,2),
(5,'Site admin','admin',1024,180,20,268435456,3);

-- Insert workflow step types
INSERT IGNORE INTO `workflow_step_type` VALUES
(1,'General step'),
(2,'Price step'),
(3,'Completed step'),
(4,'Cancelled step'),
(5,'Cancelled by user step'),
(6,'Delivery date step');

-- Insert cancellation reasons
INSERT IGNORE INTO cancellation_reasons (`for_staff`, `reason`, `more_information`) VALUES
(0, "Printed somewhere else at MSU", 0),
(0, "Estimated completion date was too long", 0),
(0, "Needed a different material", 0),
(0, "No longer wanted", 0),
(0, "Too expensive", 0),
(0, "Will resubmit file", 0),
(1, "Patron did not respond", 0),
(1, "Patron cancelled in person, or via phone, email, etc.", 0),
(1, "Issue with design that could not be solved", 0),
(1, "Test or training", 0),
(1, "Patron resubmitted file", 0),
(1, "Other", 1),
(0, "Other", 1);

-- Assessment question types
INSERT IGNORE INTO assessment_q_types (qtype_id, question_type, has_choices) VALUES
(1, 'Text', FALSE),
(2, 'YesNo', FALSE),
(3, 'MultipleChoice', TRUE),
(4, 'SelectOne', TRUE);

-- Default assessment questions
INSERT IGNORE INTO assessment_questions (question_id, qtype_id, question_text, ordering) VALUES 
(1, 2, "Can MSU Libraries post a picture of your work on its Social Media accounts?", 1),
(2, 1, "List Instagram accounts you want to be tagged with:", 2),
(3, 2, "Is this project part of an MSU class?", 3),
(4, 1, "What course/project is this project associated with?", 4),
(5, 3, "Is this project you are submitting associated with any of these items?", 5);

-- Multiple choice / pick one answer choices
INSERT IGNORE INTO assessment_q_mc_choices (option_id, question_id, option_text) VALUES
(1, 5, "This is a gift, for fun, or personal project"),
(2, 5, "This is a homework assignment"),
(3, 5, "Part of a graduate thesis or dissertation"),
(4, 5, "Research-related"),
(5, 5, "A work-related job or task (e.g. exhibition, promotions, or giveaways)"),
(6, 5, "Prototyping for business or entrepreneurship"),
(7, 5, "I prefer not to say");