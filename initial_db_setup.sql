-- Insert values into permissions table
INSERT INTO `permissions` VALUES
(1,NULL,NULL,256,30,10,0,0),
(2,'Library patron','users',256,30,10,268435456,0),
(3,'Student staff','student_staff',256,30,10,268435456,1),
(4,'Staff','staff',512,180,20,268435456,2),
(5,'Site admin','admin',1024,180,20,268435456,3);

-- Insert workflow step types
INSERT INTO `workflow_step_type` VALUES
(1,'General step'),
(2,'Price step'),
(3,'Completed step'),
(4,'Cancelled step'),
(5,'Cancelled by user step'),
(6,'Delivery date step');

-- Insert cancellation reasons
INSERT INTO cancellation_reasons (`for_staff`, `reason`, `more_information`) VALUES
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
INSERT INTO assessment_q_types VALUES
(0, 'Text'),
(1, 'YesNo'),
(2, 'MultipleChoice'),
(3, 'SelectOne');

-- Default assessment questions
INSERT INTO assessment_questions (question_id, qtype_id, question_text) VALUES 
(0, 1, "Can MSU Libraries post a picture of your work on its Social Media accounts?"),
(1, 0, "List Instagram accounts you want to be tagged with:"),
(2, 1, "Is this project part of an MSU class?"),
(3, 0, "What course/project is this project associated with?"),
(4, 2, "Is this project you are submitting associated with any of these items?"),

-- Multiple choice / pick one answer choices
INSERT INTO assessment_q_mc_choices (question_id, option_text) VALUES
(4, "This is a gift, for fun, or personal project"),
(4, "This is a homework assignment"),
(4, "Part of a graduate thesis or dissertation"),
(4, "Research-related"),
(4, "A work-related job or task (e.g. exhibition, promotions, or giveaways)"),
(4, "Prototyping for business or entrepreneurship"),
(4, "I prefer not to say");