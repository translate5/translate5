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

assuming that no workflow log entry is already in there
insert into LEK_workflow_log (taskGuid, userGuid, stepName, stepNr, created) select taskGuid, pmGuid, 'lectoring', 1, now() from LEK_task;
-- updating all edited segments into the first workflowstep
update LEK_segments set workflowStepNr = 1, workflowStep = 'lectoring' where autoStateId != 0 and autoStateId != 4 and autoStateId != 3 and workflowStepNr = 0 and workflowStep is null;
-- init all old untouched untranslated segments 
update LEK_segments set autoStateId = 4 where edited = '' and source != '' and autoStateId = 0;