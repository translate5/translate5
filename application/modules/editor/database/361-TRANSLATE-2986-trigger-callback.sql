-- /*
-- START LICENSE AND COPYRIGHT
--
--  This file is part of translate5
--
--  Copyright (c) 2013 - '.(date('Y')).' Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.
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


INSERT INTO `LEK_workflow_action` (`workflow`, `trigger`, `actionClass`, `action`, `parameters`,`description`) 
VALUES ('default', 'handleAllFinishOfARole', 'editor_Workflow_Actions', 'triggerCallbackAction', '', 'Send a request to the configured url parametar with the task and task user assoc data. If params field is provided in the parametars field, this will be applied to in the request json.');


INSERT INTO `LEK_workflow_action` (`workflow`, `trigger`, `actionClass`, `action`, `parameters`,`description`) 
VALUES ('complex', 'handleAllFinishOfARole', 'editor_Workflow_Actions', 'triggerCallbackAction', '', 'Send a request to the configured url parametar with the task and task user assoc data. If params field is provided in the parametars field, this will be applied to in the request json.');
