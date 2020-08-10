-- /*
-- START LICENSE AND COPYRIGHT
-- 
--  This file is part of translate5
--  
--  Copyright (c) 2013 - 2017 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.
-- 
--  Contact:  http://www.MittagQI.com/  /  service (ATT) MittagQI.com
-- 
--  This file may be used under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE version 3
--  as published by the Free Software Foundation and appearing in the file agpl3-license.txt 
--  included in the packaging of this file.  Please review the following information 
--  to ensure the GNU AFFERO GENERAL PUBLIC LICENSE version 3 requirements will be met:
--  http://www.gnu.org/licenses/agpl.html
--   
--  There is a plugin exception available for use with this release of translate5 for
--  translate5: Please see http://www.translate5.net/plugin-exception.txt or 
--  plugin-exception.txt in the root folder of translate5.
--   
--  @copyright  Marc Mittag, MittagQI - Quality Informatics
--  @author     MittagQI - Quality Informatics
--  @license    GNU AFFERO GENERAL PUBLIC LICENSE version 3 with plugin-execption
-- 			 http://www.gnu.org/licenses/agpl.html http://www.translate5.net/plugin-exception.txt
-- 
-- END LICENSE AND COPYRIGHT
-- */

USE [icorrect]

CREATE TABLE LEK_task
(
id int PRIMARY KEY IDENTITY,
taskGuid varchar(38) NOT NULL,
taskName nvarchar(255) NOT NULL
)

ALTER TABLE LEK_terms
ADD CONSTRAINT LEK_terms_taskGuid_FK FOREIGN KEY (taskGuid) REFERENCES LEK_task (taskGuid) ON DELETE CASCADE

ALTER TABLE LEK_files
ADD CONSTRAINT LEK_files_taskGuid_FK FOREIGN KEY (taskGuid) REFERENCES LEK_task (taskGuid) ON DELETE CASCADE

ALTER TABLE LEK_segments
ADD CONSTRAINT LEK_segments_taskGuid_FK FOREIGN KEY (taskGuid) REFERENCES LEK_task (taskGuid) ON DELETE CASCADE

ALTER TABLE LEK_foldertree
ADD CONSTRAINT LEK_foldertree_taskGuid_FK FOREIGN KEY (taskGuid) REFERENCES LEK_task (taskGuid) ON DELETE CASCADE