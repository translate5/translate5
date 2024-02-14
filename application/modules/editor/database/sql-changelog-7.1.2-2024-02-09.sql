
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
            
INSERT INTO `LEK_change_log` (`dateOfChange`, `jiraNumber`, `type`, `title`, `description`, `userGroup`) VALUES ('2024-02-09', 'TRANSLATE-3692', 'change', 'TermPortal - Log deleted terms', 'Implement logging when term is deleted from a term collection.', '15'),
('2024-02-09', 'TRANSLATE-3404', 'change', 't5memory - Change t5memory reorganize call to async', 'Added support of t5memory v0.5.x ', '15'),
('2024-02-09', 'TRANSLATE-3332', 'change', 'SpellCheck (LanguageTool integration) - New error type for languageTool', '\'Numbers\'-category errors detected by SpellCheck\'s LanguageTool are now counted and shown in the left AutoQA panel', '15'),
('2024-02-09', 'TRANSLATE-3706', 'bugfix', 'MatchAnalysis & Pretranslation - Pretranslation choses match with same matchrate independent of age', 'Use the newer TM match-rate in case there are more than 100% or greater match-rates.', '15'),
('2024-02-09', 'TRANSLATE-3703', 'bugfix', 'file format settings - custom file extension for file filter not recognized and no UI error message', 'FIXED: Backend rejected file-type although matching file-format was set in the frontend', '15'),
('2024-02-09', 'TRANSLATE-3698', 'bugfix', 'Editor general - User info command line tool error', 'Fix for user info command line tool.', '15'),
('2024-02-09', 'TRANSLATE-3696', 'bugfix', 'Task Management - RootCause: Cannot read properties of null (reading \'get\')', 'FIXED: task user special properties were not addable', '15'),
('2024-02-09', 'TRANSLATE-3693', 'bugfix', 'Configuration - Custom field with regex UI validation stops project creation wizard', 'Fix for a custom field validations in project creation wizard', '15'),
('2024-02-09', 'TRANSLATE-3689', 'bugfix', 'Editor general - No record in task action menu', 'Improve the task detection when action menu is created.', '15'),
('2024-02-09', 'TRANSLATE-3688', 'bugfix', 'Client management - Advanced filters in task overview are not saved', 'FIXED: advanced filters are now saved as well', '15'),
('2024-02-09', 'TRANSLATE-3687', 'bugfix', 'Editor general - RootCause error: record is undefined', 'Fix for UI error when trying to update edited segments in visual review layout.', '15'),
('2024-02-09', 'TRANSLATE-3656', 'bugfix', 'Import/Export - Buttons do not work in project wizard, if moved to "burger" menu', 'FIXED: overflow-menu buttons not working in the \'User assignment defaults\' step of project wizard', '15'),
('2024-02-09', 'TRANSLATE-3613', 'bugfix', 'Editor general - Message on "no more segments in workflow" misleading', 'FIXED: misleading messages when editing inside filtered segments grid is reached top or bottom', '15'),
('2024-02-09', 'TRANSLATE-3604', 'bugfix', 'Auto-QA - Consistency quality', 'FIXED: wrong translation for \'Inconsistent target\' AutoQA label', '15'),
('2024-02-09', 'TRANSLATE-3587', 'bugfix', 'Import/Export - navigation through fields in task creation wizard', 'FIXED: tabbable fields problem while mask is shown in project wizard', '15'),
('2024-02-09', 'TRANSLATE-3568', 'bugfix', 'InstantTranslate - DeepL swallos full stop between sentences', 'Text was re-segmented if source language had to be auto-detected', '15'),
('2024-02-09', 'TRANSLATE-3466', 'bugfix', 'Import/Export - TBX-import: reduce log data during import', 'Reduced logs for E1472 and E1446 so that total quantity of occurrences happened during import is logged once per event type, instead of logging each occurrence individually', '15');