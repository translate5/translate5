
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

-- userGroup calculation: basic: 1; editor: 2; pm: 4; admin: 8
            
INSERT INTO `LEK_change_log` (`dateOfChange`, `jiraNumber`, `type`, `title`, `description`, `userGroup`) VALUES ('2025-03-06', 'TRANSLATE-4525', 'change', 'ModelFront - Disable default usage of ModelFront plug-in and mark as deprecated', 'Modelfront API did change, so therefore the plugin is not working anymore and is disabled now.', '15'),
('2025-03-06', 'TRANSLATE-4502', 'change', 'Import/Export - Xliff Import: Use resname from group tag as segment description', 'Xliff Import: Use resname grom group tag as sement descriptot', '15'),
('2025-03-06', 'TRANSLATE-4501', 'change', 'Hotfolder Import - Hotfolder: Add import folder info to error mail', 'Hotfolder: Add import folder info to error mail', '15'),
('2025-03-06', 'TRANSLATE-4462', 'change', 'LanguageResources, t5memory - Add writing taskGuid to UpdateLanguageResourcesWorker', 'Added task GUID to UpdateLanguageResourcesWorker scheduling, so now if task is deleted dependent UpdateLanguageResourcesWorker are also deleted', '15'),
('2025-03-06', 'TRANSLATE-4526', 'bugfix', 'LanguageResources - Auto language detection in resource creation', 'Fix a problem when uploading tm/tmx file containing language shortcuts.', '15'),
('2025-03-06', 'TRANSLATE-4517', 'bugfix', 'TM Maintenance - TM Maintenance TU count incorrect + error message', 'FIXED: wrong total count was shown in various states', '15'),
('2025-03-06', 'TRANSLATE-4507', 'bugfix', 'TM Maintenance - Typo and column menu unneeded items', 'typo fixed and made sorting and grouping unused column menu items to be hidden', '15'),
('2025-03-06', 'TRANSLATE-4505', 'bugfix', 'API - Job controller events are not hydrated correctly', 'Fix: hydrate user job controller events with entity', '15'),
('2025-03-06', 'TRANSLATE-4504', 'bugfix', 'Import/Export - Task defaults are evaluated at the wrong place in import process', 'FIX: Event-Handling on import may leads to import-options not being processed or being processed to late', '15'),
('2025-03-06', 'TRANSLATE-4503', 'bugfix', 'Workflows - Workflow: Next job in workflow has invalid status', 'Workflow: Add logging of meta info for edge case of workflow set next job', '15'),
('2025-03-06', 'TRANSLATE-4500', 'bugfix', 'Editor general - Repetition editor: res-name is not used to look for repetitions', 'Repetition editor: Use res-name to look for repetitions', '15'),
('2025-03-06', 'TRANSLATE-4498', 'bugfix', 'Content Protection - Conversion of TMs produces corrupt xml where html entities are present in segments', 'Content protection: Fix html entity handling', '15'),
('2025-03-06', 'TRANSLATE-4480', 'bugfix', 'Import/Export - Match analysis worker not queued', ' translate5 - 7.20.4: Fix: Queue Match analysis worker on import
 translate5 - 7.20.5: Changed another place with the same problem, and fixes needed in Terminologie handling due a follow up error.', '15');