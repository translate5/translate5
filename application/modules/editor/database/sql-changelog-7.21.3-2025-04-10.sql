
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
            
INSERT INTO `LEK_change_log` (`dateOfChange`, `jiraNumber`, `type`, `title`, `description`, `userGroup`) VALUES ('2025-04-10', 'TRANSLATE-3647', 'feature', 'Okapi integration - Okapi integration: Enable down- and upload of fprm and pln files and pre-parsing of xliff by Okapi', 'translate5 - 7.21.0: Added down- and upload of any FPRM and Pipeline files. Added optional pre-parsing of xliff with OKAPI. The pre-parsing will be only possible with file-format-settings created from the date of the release on. With this release, the translate5-default settings do not have XLIFF-extensions mapped anymore and if such extension-mapping is added to a file-format, the pre-parsing of XLIFF will be active.
translate5 - 7.21.3: Add versioning to pipeline files', '15'),
('2025-04-10', 'TRANSLATE-2971', 'feature', 'Task Management, Workflows - Set job deadlines for multiple projects or tasks at the same time', '7.21.3: Fix: not working when all tasks selected manually
7.21.0: Fix: no task selected alert if tasks are selected
7.15.0: Added ability to set job deadlines for multiple projects or tasks at the same time', '15'),
('2025-04-10', 'TRANSLATE-4582', 'change', 'Workflows - Auto-close jobs with task deadline date as reference', 'New option added: detect potential auto-close jobs from task deadline date as reference. Can be configured only as workflow action parameter.', '15'),
('2025-04-10', 'TRANSLATE-4561', 'change', 'Content Protection - Content Protection: improve "default simple" regex', 'Improve "default simple" regex.
This update will also set language resources that use "default simple" integer rule for protection as un-converted and one need to manually start conversion of such language resources', '15'),
('2025-04-10', 'TRANSLATE-4558', 'change', 'Workflows - Tests for batch set properties', 'Covered batch set properties with tests', '15'),
('2025-04-10', 'TRANSLATE-4553', 'change', 't5memory - Add prefix to segmentNr when request t5memory to distinguish segmentNr and numeral resname', 't5memory: Add prefix to segmentNr when request t5memory to distinguish segmentNr and numeral resname', '15'),
('2025-04-10', 'TRANSLATE-4521', 'change', 'Task Management - Introduce config for time of daily auto-close check', 'Enables time config at which time of the day the auto-close jobs will be checked and closed.', '15'),
('2025-04-10', 'TRANSLATE-4501', 'change', 'Hotfolder Import - Hotfolder: Add import folder info to error mail', '7.21.3: Fix: better formatting of errors in e-mail
7.20.5: Hotfolder: Add import folder info to error mail', '15'),
('2025-04-10', 'TRANSLATE-4426', 'change', 'Task Management - change sorting of user tab grid', 'Jobs are now sorted by workflow position in the task user association panel.', '15'),
('2025-04-10', 'TRANSLATE-3202', 'change', 'file format settings - Adopt File Format Settings for OpenXML to Okapi version 1.4.7', 'translate5 - 7.19.0: Adopted changes in FPRM structure for Okapi version 1.4.7
translate5 - 7.21.3: Fix okapi version fetching
', '15'),
('2025-04-10', 'TRANSLATE-4603', 'bugfix', 'Configuration - PMs are set as possible values for random config values if filter applied', 'Fix possible values for configs', '15'),
('2025-04-10', 'TRANSLATE-4599', 'bugfix', 'Okapi integration - Disallow okapi pipeline step "External command"', 'Disallow okapi pipeline step "External command" as part of uploaded bconfs/pipelines', '15'),
('2025-04-10', 'TRANSLATE-4597', 'bugfix', 'Okapi integration - Test latest Okapi 1.48-snapshot build and update translate5 with next release', 'Added latest Okapi 1.48-snapshot support', '15'),
('2025-04-10', 'TRANSLATE-4593', 'bugfix', 't5memory - t5memory: Check Language resource status based on last writable TM', 'Check Language resource status based on last writable TM', '15'),
('2025-04-10', 'TRANSLATE-4588', 'bugfix', 'Import/Export - HTML number entities are not properly imported', 'Whitespace encoded as XML numbered entities is not properly decoded and recognized as whitespace', '15'),
('2025-04-10', 'TRANSLATE-4585', 'bugfix', 'LanguageResources - Reimport task doesn\'t work for properly with special characters', 'Fixed bug which may prevent task reimport to TM if task contains segments with \ (back slash) symbol at the end of the segment.', '15'),
('2025-04-10', 'TRANSLATE-4584', 'bugfix', 'file format settings - make usage of resname selectable in file format settings', 'Make usage of resname selectable in file format settings', '15'),
('2025-04-10', 'TRANSLATE-4583', 'bugfix', 'Repetition editor - repetitions editor not working', 'Fix fetch of alike segment. Fix resname comment saving (activate xliff comment import)', '15'),
('2025-04-10', 'TRANSLATE-4580', 'bugfix', 'Content Protection - Bug in format applying on number protection', 'Fix symbol duplication on number format applying', '15'),
('2025-04-10', 'TRANSLATE-4579', 'bugfix', 'Main back-end mechanisms (Worker, Logging, etc.) - Worker queue is called frequently and chained', 'Performance improvement for the workers, prevention of system overload when scheduling thousands of workers.', '15'),
('2025-04-10', 'TRANSLATE-4578', 'bugfix', 'Editor general, LanguageResources, Task Management - Missing mayor language leads to errors on resources-task association detection', 'Fix for error when detecting language alias for task-resources associations', '15'),
('2025-04-10', 'TRANSLATE-4568', 'bugfix', 'Content Protection - apply current content protection rules on saving task to Master TM', 'On saving task into master TM re-apply current protection rules to segments of task', '15'),
('2025-04-10', 'TRANSLATE-4547', 'bugfix', 't5memory - Make processes with t5memory resistant for t5memory restart', 'Make processes with t5memory resistant for t5memory restart', '15'),
('2025-04-10', 'TRANSLATE-4539', 'bugfix', 'Content Protection - Content Protection: Protection look alike rules and t5memory response formatter is faulty', 'Fix converted tags ids, fix representation of matches in UI, don\'t process language resources with empty memories
', '15'),
('2025-04-10', 'TRANSLATE-4522', 'bugfix', 'Configuration - description of runtimeOptions.import.projectDeadline.jobDeadlineFraction misleading', 'Update config description', '15'),
('2025-04-10', 'TRANSLATE-4494', 'bugfix', 'Client management, Export, LanguageResources - Resource log export leads to out of memory', 'Resource log export re-implemented to be memory-efficient', '15');