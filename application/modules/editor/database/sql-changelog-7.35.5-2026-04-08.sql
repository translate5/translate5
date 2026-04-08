
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
--              http://www.gnu.org/licenses/agpl.html http://www.translate5.net/plugin-exception.txt
--
-- END LICENSE AND COPYRIGHT
-- */

-- userGroup calculation: basic: 1; editor: 2; pm: 4; admin: 8
            
INSERT INTO `LEK_change_log` (`dateOfChange`, `jiraNumber`, `type`, `title`, `description`, `userGroup`) VALUES ('2026-04-08', 'TRANSLATE-5378', 'change', 't5memory - New installations set t5memory skipAuthor to true by default', 'For new installations t5memory skipAuthor is enabled by default. For existing instances the configuration is not automatically changed.', '15'),
('2026-04-08', 'TRANSLATE-5268', 'change', 'InstantTranslate - Instant-translate: Move plugin specific user defaults to separate entity', 'Reorganise internal storage for InstantTranslate specific features. ', '15'),
('2026-04-08', 'TRANSLATE-4176', 'change', 'Auto-QA - highlighted segment should stay after unfiltering of QA', 'FIXED: segments grid is now scrolled to the last selected segment if quality filters were unset', '15'),
('2026-04-08', 'TRANSLATE-5392', 'bugfix', 't5memory - Type error in reorganise', 'Prevent type error before increasing reorganise attempts', '15'),
('2026-04-08', 'TRANSLATE-5391', 'bugfix', 't5memory - Incorrect handling for replace rules in case if entity protected only on TM side', 'Provide output format for rule to render TM only entity', '15'),
('2026-04-08', 'TRANSLATE-5390', 'bugfix', 't5connect - Concordance triggers reorganise for first search', 'Prevents probable problems with concordance search and reorganisation of TMs.', '15'),
('2026-04-08', 'TRANSLATE-5382', 'bugfix', 'translate5 AI - Terms are ignored on translation re-try for LLM resources', 'Solves problem where terminology is ignored when failed segment translation is re-send.', '15'),
('2026-04-08', 'TRANSLATE-5375', 'bugfix', 'Task Management, Workflows - no deadline reminder for tasks in state unconfirmed', 'Send deadline reminder e-mail for tasks in state unconfirmed', '15'),
('2026-04-08', 'TRANSLATE-5369', 'bugfix', 'Import/Export - Excel Re-Import: Import Tag-Parsing seems to produce broken markup', 'FIX: The Excel-Reimport parser did produce broken markup in rare situations', '15'),
('2026-04-08', 'TRANSLATE-5367', 'bugfix', 't5memory - Tags remapping depends on the order of matches for t5memoryxliff tag handler', 'Fixed bug which may cause tags to be improperly remapped in segment target.', '15'),
('2026-04-08', 'TRANSLATE-5364', 'bugfix', 'InstantTranslate - send to human revision not respecting client selection', 'Fix problem where customer was changed for human revision tasks in case the user has more than one customer assigned.', '15'),
('2026-04-08', 'TRANSLATE-5361', 'bugfix', 't5memory - t5memory produces invalid xml and can\'t process html entities in tu tag attributes', 'Fix TU nodes on TMX export from t5memory
Cleanup TU attributes on import to t5memory', '15'),
('2026-04-08', 'TRANSLATE-5359', 'bugfix', 'Task Management - User association window save button now always visible', 'User Association: When adding or editing a user, the Save/Cancel buttons are now always visible; scrolling only applies to the form content.', '15'),
('2026-04-08', 'TRANSLATE-5356', 'bugfix', 'Hotfolder Import - COTI: Check for archives in standard correct folder structure', 'Check for archives in standard correct folder structure', '15'),
('2026-04-08', 'TRANSLATE-5349', 'bugfix', 'MatchAnalysis & Pretranslation - Term collection repetitions not counted in the analysis', 'Fix for a problem where term collection repetitions where skipped in the analysis.', '15'),
('2026-04-08', 'TRANSLATE-5346', 'bugfix', 'translate5 AI - Improve TQE for empty target segments', 'Improve TQE empty target reasoning message.', '15'),
('2026-04-08', 'TRANSLATE-5337', 'bugfix', 'Content Protection, Editor general - edit source field in editor displays target format of content protection rule', 'Fix displaying content protected tag while editing segment source.', '15'),
('2026-04-08', 'TRANSLATE-5319', 'bugfix', 'Editor general - PHP Error [E9999 core] Zend_Db_Statement_Exception: SQLSTATE[21000]: Cardinality violation', 'PHP error fixed', '15'),
('2026-04-08', 'TRANSLATE-5318', 'bugfix', 'job coordinator, User Management - job coordinator cannot notify users: forbidden', 'Allow Job Coordinator to send job notifications', '15'),
('2026-04-08', 'TRANSLATE-5317', 'bugfix', 'Editor general, localization - UI language switch in grid broken', 'FIX: The GUI-locale switch in the segment-grid did not show all available locales and was not working properly', '15'),
('2026-04-08', 'TRANSLATE-5304', 'bugfix', 'InstantTranslate - InstantTranslate fails to load on specific customer / file filter combinations', 'Fixed that InstantTranslate did not load in some situations.', '15'),
('2026-04-08', 'TRANSLATE-5244', 'bugfix', 'Package Ex and Re-Import - Reimport of Translator Package leads to invalid Markup', 'FIX: translator package reimport may lead to errors due to tag-errors in diffing to evaluate track-changes', '15'),
('2026-04-08', 'TRANSLATE-5205', 'bugfix', 'InstantTranslate - Backend for missing default PM error not shown on human revision usage', 'Do not clear and remove important error messages.', '15'),
('2026-04-08', 'TRANSLATE-5194', 'bugfix', 'Repetition editor, Search & Replace (editor) - search and replace dialogue and repetitions', 'FIXED: Search & Replace window is now prevent from overlapping Repetitions window', '15'),
('2026-04-08', 'TRANSLATE-5151', 'bugfix', 'InstantTranslate - pre-selected languages in InstantTranslate not remembered', 'Fix the problem where default instant translate pre-selected languages config was not evaluated correctly.', '15');