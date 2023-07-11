-- --------------------------------------------------------
-- DB inserts for MariaDB
-- Project name: 3dPrime
-- --------------------------------------------------------

-- Adding a new cancellation reason to the cancellation_reasons table. 
-- This was requested my makerspace as they sometimes make test jobs to test printers or laser cutting machines.
INSERT INTO 3dprime.cancellation_reasons values(0, 1, "Test Job", 0)
