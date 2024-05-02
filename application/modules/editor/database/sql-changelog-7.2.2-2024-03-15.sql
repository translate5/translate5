
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
            
INSERT INTO `LEK_change_log` (`dateOfChange`, `jiraNumber`, `type`, `title`, `description`, `userGroup`) VALUES ('2024-03-15', 'TRANSLATE-3794', 'feature', 't5memory - Improve reimport tasks mechanism', 'New command is added for reimport task segments to TM
Added new button to the language resources UI for reimporting only updated segments', '15'),
('2024-03-15', 'TRANSLATE-3748', 'feature', 'LanguageResources - TMX zip-import', 'Added support for zip uploads in t5memory resources.', '15'),
('2024-03-15', 'TRANSLATE-3812', 'change', 'Import/Export - Make runtimeOptions.frontend.importTask.edit100PercentMatch affect the server side', 'The config runtimeOptions.frontend.importTask.edit100PercentMatch is renamed to runtimeOptions.import.edit100PercentMatch and affects now API imports too. Previously this was always false for API imports.', '15'),
('2024-03-15', 'TRANSLATE-3810', 'bugfix', 'Editor general - Reorganize tm can save status of internal fuzzy memory', 'FIx for a problem with the memory name for t5memory language resources.', '15'),
('2024-03-15', 'TRANSLATE-3805', 'bugfix', 'Editor general - RootCause error: Cannot read properties of null (reading \'NEXTeditable\')', 'Fix for UI error when saving segment and there are no available next segments in the workflow.', '15'),
('2024-03-15', 'TRANSLATE-3804', 'bugfix', 'Editor general - RootCause: Cannot read properties of null (reading \'items\')', 'DEBUG: more info about the problem will be captured for further investigation once it happen next time', '15'),
('2024-03-15', 'TRANSLATE-3799', 'bugfix', 't5memory - Segment check after update in t5memory doesn\'t work properly with escaped symbols', 'Fixed check if segment was updated properly in t5memory', '15'),
('2024-03-15', 'TRANSLATE-3798', 'bugfix', 'Editor general - RootCause: Failed to execute \'setAttribute\' on \'Element\': \'vorlage,\' is not a valid attribute name.', 'Added more detailed logging of such cases for further investigation', '15'),
('2024-03-15', 'TRANSLATE-3797', 'bugfix', 'Editor general - Do not run CLI cron jobs with active maintenance', 'Scheduled cron jobs via CLI may not run when maintenance is enabled.', '15'),
('2024-03-15', 'TRANSLATE-3796', 'bugfix', 't5memory - Fix t5memory migration command', 'Fix cleaning config value in t5memory:migrate command', '15'),
('2024-03-15', 'TRANSLATE-3793', 'bugfix', 'LanguageResources - change date format in file name of resource usage export', 'Fix date-format in excel-export zip-file-names to become the standard "Y-m-d"', '15'),
('2024-03-15', 'TRANSLATE-3789', 'bugfix', 'VisualReview / VisualTranslation - Remove "Max number of layout errors" from visual, just "warn" from a certain thresh on, that errors happened', 'Visual: Tasks are imported, even if the thresh of layout-errors is exceeded; Only a warning will be added in such cases', '15'),
('2024-03-15', 'TRANSLATE-3782', 'bugfix', 'Repetition editor - repetions editor target text not shown', 'FIXED: target tests were not visible in repetitions editor', '15'),
('2024-03-15', 'TRANSLATE-3758', 'bugfix', 'Configuration - move config edit100PercentMatch to client level', 'Attention: See als TRANSLATE-3812! The config runtimeOptions.frontend.importTask.edit100PercentMatch is renamed to runtimeOptions.import.edit100PercentMatch and affects now API imports too, and can be set on client level.', '15'),
('2024-03-15', 'TRANSLATE-3755', 'bugfix', 'Main back-end mechanisms (Worker, Logging, etc.) - PHP E_ERROR: Uncaught TypeError: gzdeflate(): Argument #1 ($data) must be of type string, bool given', 'FIXED: if error happens on json-encoding events to be logged - is now POSTed to logger instead', '15'),
('2024-03-15', 'TRANSLATE-3741', 'bugfix', 'Import/Export - Pricing scheme selected on client level is not respected for projects coming over the hotfolder', 'Fix Task creation process. Provide pricing preset from Client config', '15'),
('2024-03-15', 'TRANSLATE-3670', 'bugfix', 'Editor general - Task custom fields label should be required', 'Label field is not required when creating new custom field.', '15');