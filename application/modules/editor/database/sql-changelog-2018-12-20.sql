
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

INSERT INTO `LEK_change_log` (`dateOfChange`, `jiraNumber`, `type`, `title`, `description`, `userGroup`) VALUES ('2018-12-20', 'TRANSLATE-1490', 'feature', 'Highlight fuzzy range in source of match in translate5 editor', 'Highlight fuzzy range in source of match in translate5 editor for all TM resources.', '14'),
('2018-12-20', 'TRANSLATE-1430', 'feature', 'Enable copy and paste of internal tags from source to target', 'Enable copy and paste of internal tags from source to target', '6'),
('2018-12-20', 'TRANSLATE-1397', 'feature', 'Multitenancy phase 1', 'First steps for multi-tenancy, by setting grid filters automatically.', '12'),
('2018-12-20', 'TRANSLATE-1206', 'feature', 'Add Whitespace chars to segment', 'Add additional whitespace, tab characters and non breaking space can be added to the segment - protected as internal tag.', '6'),
('2018-12-20', 'TRANSLATE-1483', 'change', 'PHP Backend: Implement an easy way to join tables for filtering via API', 'PHP Backend: Implement an easy way to join tables for filtering via API', '8'),
('2018-12-20', 'TRANSLATE-1460', 'change', 'Deactivate export menu in taskoverview for editor users', 'The export menu in the taskoverview was deactivated for editor users and is now only visible for PM and admin users.', '12'),
('2018-12-20', 'TRANSLATE-1500', 'bugfix', 'PM dropdown field in task properties shows max 25 users', 'The dropdown field to change the PM in the task properties shows max 25 PMs', '12'),
('2018-12-20', 'TRANSLATE-1497', 'bugfix', 'Convert JSON.parse calls to Ext.JSON.decode calls for better debugging', 'Due the wrong JSON decode call we got less error information on receiving invalid JSON.', '8'),
('2018-12-20', 'TRANSLATE-1491', 'bugfix', 'Combine multiple OpenTM2 100% matches to one match', 'Group multiple identical OpenTM2 100% matches with different >100% match rates to one match with the best match rate.', '14'),
('2018-12-20', 'TRANSLATE-1488', 'bugfix', 'JS Error "Cannot read property \'row\' of null" on using bookmark functionality', 'The error in the frontend is solved.', '14'),
('2018-12-20', 'TRANSLATE-1487', 'bugfix', 'User can not change his own password', 'Fixed that user can not change his own password in the GUI.', '14'),
('2018-12-20', 'TRANSLATE-1477', 'bugfix', 'Error on removing a user from a task which finished then the task', 'In some circumstances an PHP error happened on removing a user from a task.', '12'),
('2018-12-20', 'TRANSLATE-1476', 'bugfix', 'TrackChanges: JS Error when replacing a character in certain cases', 'TrackChanges: JS Error when replacing a character in certain cases', '6'),
('2018-12-20', 'TRANSLATE-1475', 'bugfix', 'Merging of term tagger result and track changes content leads to several errors', 'Merging of term tagger result and track changes content leads to several errors in the content. Missing or invalid content for example.', '14'),
('2018-12-20', 'TRANSLATE-1474', 'bugfix', 'Clicking in Treepanel while segments are loading is creating an error', 'Clicking in Treepanel while segments are loading is creating an error', '6'),
('2018-12-20', 'TRANSLATE-1472', 'bugfix', 'Task delete throws DB foreign key constraint error', 'Task delete throws DB foreign key constraint error', '12'),
('2018-12-20', 'TRANSLATE-1470', 'bugfix', 'Do not automatically add anymore missing tags on overtaking results from language resources', 'Do not automatically add anymore missing tags on overtaking results from language resources', '6'),
('2018-12-20', 'TRANSLATE-146', 'bugfix', 'Internal translation mechanism creates corrupt XLIFF', 'The internal translation mechanism of the application was creating corrupt XLIFF which then blocked application loading.', '8'),
('2018-12-20', 'TRANSLATE-1465', 'bugfix', 'InstantTranslate: increased input-field must not be covered by other elements', 'InstantTranslate: increased input-field must not be covered by other elements', '6'),
('2018-12-20', 'TRANSLATE-1463', 'bugfix', 'Trigger workflow action not in all remove user cases', 'Trigger workflow action not in all remove user cases', '6'),
('2018-12-20', 'TRANSLATE-1449', 'bugfix', 'Spellcheck needs to handle whitespace tags as space / word boundary', 'Spellcheck needs to handle whitespace tags as space / word boundary', '12'),
('2018-12-20', 'TRANSLATE-1440', 'bugfix', 'Short tag view does not accurately reflect tag order and relationship between tags', 'Short tag view does not accurately reflect tag order and relationship between tags', '14'),
('2018-12-20', 'TRANSLATE-1505', 'bugfix', 'Several smaller issues', 'Fix defaultuser usage in session api test / Fix minor issue in TBX parsing / Fix tag number calculation / Fix typo in translation / Fix wrong file name for TM download / Fixing DB alter SQL issues with MatchAnalysis Plugin / Show logo on bootstrap JS load / detect and select compound languages from uploaded filename / fix misleading variable name / fix typo in code comment / fixing task delete on test / integrate OpenTM2 check in build and deploy / make PM user available in mail templates', '12'),
('2018-12-20', 'TRANSLATE-1429', 'bugfix', 'TrackChanges: Unable to get property \'className\' of undefined or null reference', 'TrackChanges: Fixed Js error: Unable to get property \'className\' of undefined or null reference', '6'),
('2018-12-20', 'TRANSLATE-1398', 'bugfix', 'TrackChanges: Backspace and DEL are removing whole content instead only single characters', 'TrackChanges: Backspace and DEL are removing whole content instead only single characters', '6'),
('2018-12-20', 'TRANSLATE-1333', 'bugfix', 'Search and Replace: JS Error: Die Eigenschaft "getActiveTab" eines undefinierten oder Nullverweises kann nicht abgerufen werden', 'Search and Replace: JS Error: Die Eigenschaft "getActiveTab" eines undefinierten oder Nullverweises kann nicht abgerufen werden', '6'),
('2018-12-20', 'TRANSLATE-1332', 'bugfix', 'Search and Replace - JS error: record is undefined', 'Search and Replace - JS error: record is undefined', '6'),
('2018-12-20', 'TRANSLATE-1300', 'bugfix', 'TrackChanges: Position of the caret after deleting from CTRL+A', 'TrackChanges: Position of the caret after deleting from CTRL+A', '6'),
('2018-12-20', 'TRANSLATE-1020', 'bugfix', 'Tasknames with HTML entities are producing errors in segmentstatistics plugin', 'Tasknames with HTML entities are producing errors in segmentstatistics plugin', '12'),
('2018-12-20', 'T5DEV-251', 'bugfix', 'Several issues in InstantTranslate', 'Several issues in InstantTranslate', '14'),
('2018-12-20', 'T5DEV-253', 'bugfix', 'Several issues in match analysis and pre-translation', 'Several issues in match analysis and pre-translation', '12'),
('2018-12-20', 'TRANSLATE-1499', 'bugfix', 'Task Name filtering does not work anymore after leaving a task', 'Task Name filtering does not work anymore after opening and then leaving a task', '12');