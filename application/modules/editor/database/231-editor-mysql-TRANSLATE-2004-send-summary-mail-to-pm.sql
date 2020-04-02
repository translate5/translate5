/*
START LICENSE AND COPYRIGHT

 This file is part of translate5
 
 Copyright (c) 2013 - 2017 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

 Contact:  http://www.MittagQI.com/  /  service (ATT) MittagQI.com

 This file may be used under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE version 3
 as published by the Free Software Foundation and appearing in the file agpl3-license.txt 
 included in the packaging of this file.  Please review the following information 
 to ensure the GNU AFFERO GENERAL PUBLIC LICENSE version 3 requirements will be met:
 http://www.gnu.org/licenses/agpl.html
  
 There is a plugin exception available for use with this release of translate5 for
 translate5 plug-ins that are distributed under GNU AFFERO GENERAL PUBLIC LICENSE version 3:
 Please see http://www.translate5.net/plugin-exception.txt or plugin-exception.txt in the root
 folder of translate5.
  
 @copyright  Marc Mittag, MittagQI - Quality Informatics
 @author     MittagQI - Quality Informatics
 @license    GNU AFFERO GENERAL PUBLIC LICENSE version 3 with plugin-execption
			 http://www.gnu.org/licenses/agpl.html http://www.translate5.net/plugin-exception.txt

END LICENSE AND COPYRIGHT
*/


INSERT INTO `LEK_workflow_action` (`workflow`, `trigger`, `actionClass`, `action`, `parameters`,`description`) 
VALUES ('default', 'handleImportCompleted', 'editor_Workflow_Notification', 'notifyImportErrorSummary', '{"always":false}', 'Sends a summary of import errors and warnings to the PM. By default the PM gets the mail only if he did not start the import, so the import was started by API. Can be overriden by setting always to true.');

INSERT INTO `Zf_worker_dependencies` (`worker`, `dependency`)
VALUES ('editor_Models_Import_Worker_FinalStep','editor_Models_Import_Worker_SetTaskToOpen'),
('editor_Models_Import_Worker_FinalStep','editor_Models_Import_Worker');

INSERT INTO `Zf_configuration` (`name`, `confirmed`, `module`, `category`, `value`, `default`, `defaults`, `type`, `description`) 
VALUES
('runtimeOptions.worker.editor_Models_Import_Worker_FinalStep.maxParallelWorkers', 1, 'editor', 'worker', 1, 1, '', 'integer', 'Max parallel running workers of the Final Import Worker worker');
