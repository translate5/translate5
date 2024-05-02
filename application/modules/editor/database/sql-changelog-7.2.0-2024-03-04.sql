
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
            
INSERT INTO `LEK_change_log` (`dateOfChange`, `jiraNumber`, `type`, `title`, `description`, `userGroup`) VALUES ('2024-03-04', 'TRANSLATE-3764', 'change', 'InstantTranslate - make runtimeOptions.InstantTranslate.user.defaultLanguages possible in UI', 'Default selected languages for instant translate are configurable.', '15'),
('2024-03-04', 'TRANSLATE-3759', 'change', 'ConnectWorldserver - Failing Test MittagQI\Translate5\Plugins\ConnectWorldserver\tests\ExternalOnlineReviewTest::testCreateTaskFromWorldserverTestdata', 'Bugfix failing test', '15'),
('2024-03-04', 'TRANSLATE-3757', 'change', 'Editor general - New documentation links', 'Add new documentation links.', '15'),
('2024-03-04', 'TRANSLATE-3554', 'change', 'VisualReview / VisualTranslation - Enhancements for visual as ordered by translate5 Consortium', 'Visual: Improved the Text-Reflow of the WYSIWYG Visual (right frame) to:
* detect sequences of text as justified, right/left aligned and centered paragraphs
* avoid lost segments due to changed text-order
* improve detection & rendering of lists
* avoid overlapping elements in the frontend
* improve handling of superflous whitespace from the segments', '15'),
('2024-03-04', 'TRANSLATE-3780', 'bugfix', 'Trados integration - Change default of runtimeOptions.editor.frontend.reviewTask.useSourceForReference back to "Disabled"', 'Revoke the default value for the "runtimeOptions.editor.frontend.reviewTask.useSourceForReference" config back to disabled.', '15'),
('2024-03-04', 'TRANSLATE-3773', 'bugfix', 'Editor general - Task action menu error', 'Fix for UI error where the task action menu was not up to date with the task.', '15'),
('2024-03-04', 'TRANSLATE-3767', 'bugfix', 'Editor general - UI error when Tag-Checking', 'Fix improper use of ExtJS-API', '15'),
('2024-03-04', 'TRANSLATE-3762', 'bugfix', 'Task Management - RootCause: Cannot read properties of null (reading \'items\')', 'FIXED: error popping on frequent subsequent clicks on task menu icon', '15'),
('2024-03-04', 'TRANSLATE-3755', 'bugfix', 'Main back-end mechanisms (Worker, Logging, etc.) - PHP E_ERROR: Uncaught TypeError: gzdeflate(): Argument #1 ($data) must be of type string, bool given', 'FIXED: if error happens on json-encoding events to be logged - is now POSTed to logger instead', '15'),
('2024-03-04', 'TRANSLATE-3754', 'bugfix', 'LanguageResources - Fix tag handling in taking over matches from matchresource panel', 'When taking over matches from the matchpanel tag order of the source is applied to the target.', '15'),
('2024-03-04', 'TRANSLATE-3751', 'bugfix', 'Editor general - Reduce log level for not found errors', 'Reduce log level of multiple errors.', '15'),
('2024-03-04', 'TRANSLATE-3745', 'bugfix', 't5memory - Querying segments with flipped tags between source and target does not work', 'When dealing with segments where the tag order has changed between source and target, the order of tags was saved wrong and restored wrong from t5memory when re-using such a segment. ', '15'),
('2024-03-04', 'TRANSLATE-3735', 'bugfix', 'Editor general - Manual QA complete segment not editable, if segment is opened for editing', 'FIXED: Manual QA was disabled when segment opened', '15'),
('2024-03-04', 'TRANSLATE-3697', 'bugfix', 'InstantTranslate - Missing whitespaces in InstantTranslate', 'Fix wrong newline conversion', '15');