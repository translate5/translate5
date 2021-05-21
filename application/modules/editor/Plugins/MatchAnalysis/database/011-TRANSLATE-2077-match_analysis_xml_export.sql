-- /*
-- START LICENSE AND COPYRIGHT
-- 
--  This file is part of ZfExtended library
--  
--  Copyright (c) 2013 - 2017 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.
-- 
--  Contact:  http://www.MittagQI.com/  /  service (ATT) MittagQI.com
-- 
--  This file may be used under the terms of the GNU LESSER GENERAL PUBLIC LICENSE version 3
--  as published by the Free Software Foundation and appearing in the file lgpl3-license.txt 
--  included in the packaging of this file.  Please review the following information 
--  to ensure the GNU LESSER GENERAL PUBLIC LICENSE version 3.0 requirements will be met:
-- https://www.gnu.org/licenses/lgpl-3.0.txt
-- 
--  @copyright  Marc Mittag, MittagQI - Quality Informatics
--  @author     MittagQI - Quality Informatics
--  @license    GNU LESSER GENERAL PUBLIC LICENSE version 3
-- 			 https://www.gnu.org/licenses/lgpl-3.0.txt
-- 
-- END LICENSE AND COPYRIGHT
-- */

-- to prevent duplicate column errors, since this alter file was moved and is now recognized as new again
DELIMITER ;;
CREATE PROCEDURE ALTER_MATCH_ANALYSIS()
BEGIN
    DECLARE CONTINUE HANDLER FOR 1060 BEGIN END;
    ALTER TABLE `LEK_match_analysis` ADD COLUMN `type` varchar (64);
    ALTER TABLE `LEK_match_analysis_taskassoc` ADD COLUMN `uuid` varchar (64);
    ALTER TABLE `LEK_match_analysis_taskassoc` ADD `finishedAt` DATETIME DEFAULT CURRENT_TIMESTAMP NULL;
END;;
DELIMITER ;
CALL ALTER_MATCH_ANALYSIS();
DROP PROCEDURE ALTER_MATCH_ANALYSIS;

-- first fix all finishedAt values for legacy data
UPDATE `LEK_match_analysis_taskassoc`
SET finishedAt = created; 

-- now disallow null values
ALTER TABLE `LEK_match_analysis_taskassoc` MODIFY COLUMN `finishedAt` DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL;
