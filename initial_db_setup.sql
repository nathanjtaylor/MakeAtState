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
