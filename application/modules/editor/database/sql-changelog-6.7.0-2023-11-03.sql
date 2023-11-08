
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
            
INSERT INTO `LEK_change_log` (`dateOfChange`, `jiraNumber`, `type`, `title`, `description`, `userGroup`) VALUES ('2023-11-03', 'TRANSLATE-3549', 'feature', 'User Management - Delete users from list after set time', 'New feature that allows to automatically delete SSO users that have not logged in for a set period of time', '15'),
('2023-11-03', 'TRANSLATE-3544', 'feature', 'InstantTranslate - add keyboard shortcut for "translate" in instant translate', 'If auto-translate is disabled in instant translate, users now can run translations with a keyboard shortcut (alt + enter).', '15'),
('2023-11-03', 'TRANSLATE-3407', 'feature', 'Editor general, Repetition editor - Filter only repetitions except first', 'It\'s now possible to show repetitions excluding first occurrences of each repetition group', '15'),
('2023-11-03', 'TRANSLATE-3557', 'change', 't5memory - Improve fuzzy analysis speed', 'Internal fuzzy speeded up by omitting flushing memory on each segment update in t5memory', '15'),
('2023-11-03', 'TRANSLATE-3555', 'change', 'Task Management - Flexibilize task deletion and archiving trigger', 'Add flexibility to task auto-deletion. Now user can provide workflow statuses at which task will be archived and deleted from task list ', '15'),
('2023-11-03', 'TRANSLATE-3531', 'change', 't5memory - Improve memory:migrate CLI command', 'For not exportable memories a create-empty option is added to the memory:migrate CLI command', '15'),
('2023-11-03', 'TRANSLATE-3530', 'change', 'ConnectWorldserver - Plugin ConnectWorldserver: add DueDate', 'Added DueDate for tasks created by plugin ConnectWorldserver', '15'),
('2023-11-03', 'TRANSLATE-3520', 'change', 'Translate5 CLI - Improve internal translation package creation', 'Implemented a CLI command to import the internal translations as translate5 task.', '15'),
('2023-11-03', 'TRANSLATE-3418', 'change', 'Main back-end mechanisms (Worker, Logging, etc.) - Make toast messages closeable, when clicking somewhere', 'Toast messages are now closeable', '15'),
('2023-11-03', 'TRANSLATE-3364', 'change', 'Editor general, SpellCheck (LanguageTool integration) - Show and take over correction proposals of spellcheck also by keyboard', 'Added CTRL+R shortcut for showing replacement suggestions when the cursor is inside an spellcheck-highlighted word in an open segment editor', '15'),
('2023-11-03', 'TRANSLATE-3363', 'change', 'Auto-QA, Editor general, SpellCheck (LanguageTool integration) - Keyboard short-cuts to set false positives', 'Added ability to use CTRL + ALT + DIGIT keyboard shortcut to toggle false positive flag on selected segment\'s qualities', '15'),
('2023-11-03', 'TRANSLATE-3315', 'change', 'Auto-QA - Enhance false positive pop-up, that appears on right-click on error', 'Enhanced the way of how false-positives  flag can be spreaded across similar AutoQA errors', '15'),
('2023-11-03', 'TRANSLATE-3314', 'change', 'Auto-QA - "Only errors" in AutoQA in the editor should be default setting', 'Default option in AutoQA dropdown at the top left is changed from \'Show all\' to \'Only errors\'', '15'),
('2023-11-03', 'TRANSLATE-3556', 'bugfix', 'MatchAnalysis & Pretranslation - Timeout on segment view creation on large imports', 'Fixing a problem with the initializing of matchanalysis workers producing strange materialized view timeouts on larger imports.', '15'),
('2023-11-03', 'TRANSLATE-3540', 'bugfix', 'Export - problems with excel ex and re import', 'Fixed that the task menu is updated directly after exporting a task as excel so that re-import button is shown without reloading the task overview.', '15'),
('2023-11-03', 'TRANSLATE-3529', 'bugfix', 'InstantTranslate - writetm - Call to a member function getId() on null', 'Made the /instanttranslateapi/writetm endpoint more robust against missing or wrong data in request.', '15'),
('2023-11-03', 'TRANSLATE-3527', 'bugfix', 'TermPortal - TermPortal batch edit "select all" leads to error: param "termid" is not given', 'Fixed batch-editing problem popping when all-terms-except-certain selection have no except-certain terms', '15'),
('2023-11-03', 'TRANSLATE-3526', 'bugfix', 'Import/Export, VisualReview / VisualTranslation - Export not possible after server movement', 'Some specific file formats did store an absolute file path which prevents server movements.', '15'),
('2023-11-03', 'TRANSLATE-3525', 'bugfix', 'VisualReview / VisualTranslation - Video-Pathes in visual not ready for use in docker cloud', 'FIX: Pathes of linked videos in Visual contained absolute paths on server, making problems on changing server location', '15'),
('2023-11-03', 'TRANSLATE-3523', 'bugfix', 'Auto-QA - Allow tag error if error already exists in reference', 'Change the way how duplicated tags are parsed during import if they are already duplicated in the imported content.', '15'),
('2023-11-03', 'TRANSLATE-3522', 'bugfix', 'Editor general - Project focus route in URL does not work', 'Fix problem where project or task was not being focused when clicking on a link containing a direct task route.', '15'),
('2023-11-03', 'TRANSLATE-3521', 'bugfix', 'Configuration, Translate5 CLI - Config CLI command was resetting the config level to 1', 'Due a bug on the CLI config command the config level of changed values was reset to system level, so the config was not changeable in the UI anymore.', '15'),
('2023-11-03', 'TRANSLATE-3514', 'bugfix', 'Editor general - Fix several invalid class references', 'Several invalid class references and other coding problems are fixed.', '15'),
('2023-11-03', 'TRANSLATE-3513', 'bugfix', 'Comments - Blocked segments can be commented and then are unblocked', 'Blocked and locked segments no longer can be commented in translate5 if not explicitly allowed by ACL.', '15'),
('2023-11-03', 'TRANSLATE-3512', 'bugfix', 'Auto-QA - Missing tags cannot be inserted in the editor', 'New config useSourceForReference whose purpose is to choose the reference field for review tasks.
Fixed some error message to make it more obvious which field tags are compared to
Shortcuts like ctrl+insert, ctrl+comma+digit are now working based on reference field tags, but not source', '15'),
('2023-11-03', 'TRANSLATE-3509', 'bugfix', 'Task Management - project ID filter closes after inserting first character', 'Fixed filter for ID-column in projects view where the filter dialogue was closing to fast.', '15'),
('2023-11-03', 'TRANSLATE-3508', 'bugfix', 'Editor general - changed error message when long segments cannot be saved to TM', 'Improved error message on saving large segments to t5memory TM.', '15'),
('2023-11-03', 'TRANSLATE-3496', 'bugfix', 'Main back-end mechanisms (Worker, Logging, etc.) - session code cleanup and performance improvement', 'Loading performance of session data improved. (Step 1)', '15'),
('2023-11-03', 'TRANSLATE-3485', 'bugfix', 'LanguageResources, OpenTM2 integration - T5memory: add Export-call to clone & reorganize, log invalid segments', 'FIXES t5memory: 
* add import-call before clone & reorganize calls to fix updated segments missing
* add logging on reorganize for invalid segments when cloning', '15'),
('2023-11-03', 'TRANSLATE-3463', 'bugfix', 'Auto-QA - Deactivation AutoQA for Import queues the AutoQA-Workers nevertheless', 'FIX: AutoQA-Workers have been queued even when the AutoQA was completely disabled for the Import', '15'),
('2023-11-03', 'TRANSLATE-3456', 'bugfix', 't5memory - Error-Message that a segment could not be saved back to TM not shown', 'FIX: Error-Message that a segment could not be saved back to TM was not shown in the frontend', '15'),
('2023-11-03', 'TRANSLATE-3427', 'bugfix', 'Import/Export - Reference files from import zip package are not imported', 'Fix problem where reference files where not imported from zip packages.', '15'),
('2023-11-03', 'TRANSLATE-3419', 'bugfix', 'Task Management - Click on PM name in project overview opens mail with undefined address - and logs out user in certain cases', 'FIXED: \'mailto:undefined\' links in PM names in Project overview', '15'),
('2023-11-03', 'TRANSLATE-3390', 'bugfix', 'Auto-QA - Filter is reset, if autoqa error is marked as false positive', 'AutoQA filter does now keep selection on False Positive change for qualities', '15'),
('2023-11-03', 'TRANSLATE-3316', 'bugfix', 'Editor general, Search & Replace (editor) - Mysql wildcards not escaped when using search and replace and grid filters', 'Mysql wildcards (% and _  ) are now escaped when searching with search and replace and with the grid filters.', '15'),
('2023-11-03', 'TRANSLATE-3300', 'bugfix', 'TermTagger integration - Terms that contain xml special chars are not tagged', 'Replaced non-breaking spaces with ordinary spaces before feeding tbx-data to TermTagger', '15'),
('2023-11-03', 'TRANSLATE-3291', 'bugfix', 'Editor general - Sort terms in TermPortlet of the editor according to their order in the segment', 'Terms in the right-side Terminology-panel are now sorted in the order they appear in segment source', '15'),
('2023-11-03', 'TRANSLATE-1068', 'bugfix', 'API - Improve REST API on wrong usage', 'API requests (expect file uploading requests) can now understand JSON in raw body, additionally to the encapsulated JSON in a data form field. Also a proper HTTP error code is sent when providing invalid JSON.', '15');