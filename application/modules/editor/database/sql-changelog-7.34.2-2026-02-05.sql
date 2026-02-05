
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
            
INSERT INTO `LEK_change_log` (`dateOfChange`, `jiraNumber`, `type`, `title`, `description`, `userGroup`) VALUES ('2026-02-05', 'TRANSLATE-5197', 'feature', 'Editor general - Add icon for inserting complete source segment and for revert/redo last change', '[🆕 Feature] Added new buttons to segment toolbar', '15'),
('2026-02-05', 'TRANSLATE-4834', 'feature', 'translate5 AI - Translation Quality Estimation (TQE): base implementation', '7.34.2: DB migration file checks to prevent previously fixed DB problems in future
7.34.1: Fix for installations without AI plug-in
7.34.0: Translation Quality Estimation (TQE) is now possible with translate5 AI.', '15'),
('2026-02-05', 'TRANSLATE-5258', 'change', 'InstantTranslate - Instant-translate file translation: selected MT engine not used', 'Fixed problem where the selected MT resource was not used in instant-translate file translation.', '15'),
('2026-02-05', 'TRANSLATE-5253', 'change', 'Import/Export - Check for string tag id in defs of sdlxliff', 'Correct check for string tag ids in tag definition section of sdlxliff file', '15'),
('2026-02-05', 'TRANSLATE-5207', 'change', 'Task Management - Make creating materialized view atomic to prevent race conditions', '7.34.2 - [:bug:  Bugfix] when using config matViewEngineInnoDB segment views could not be created anymore
7.34.0 - [:gear:  Improvement] Improved task materialized view creation to prevent possible race condition', '15'),
('2026-02-05', 'TRANSLATE-5054', 'change', 'Editor general - Editor: tags change position after saving', 'FIX: rarely, a sequence of TrackChanges tags lead to invalidly merging some of them', '15'),
('2026-02-05', 'TRANSLATE-5257', 'bugfix', 'translate5 AI - translate5 AI: maxToken to low for reasoning models', 'The default value for calculating max tokens for the LLM\'s is increased so reasoning models dont run into limits.', '15'),
('2026-02-05', 'TRANSLATE-5254', 'bugfix', 'translate5 AI - translate5 AI: error when sending segments as xliff to LLMs', 'Fix for a problem where error was triggered when sending segments as xliff to the LLMs.', '15'),
('2026-02-05', 'TRANSLATE-5249', 'bugfix', 'Editor general - API endpoint /editor/languages is not available for normal users', 'The available languages were available only for API users via the API. 
Now also ordinary users can fetch them via API.', '15'),
('2026-02-05', 'TRANSLATE-5247', 'bugfix', 'Task Management - In some cases change of deadline date in project wizard may throw an error', 'Fix change of deadline date in project wizard', '15'),
('2026-02-05', 'TRANSLATE-5243', 'bugfix', 'Editor general - Error occurrs in editor', '[🐞 Fix] Improved stability of the editor', '15'),
('2026-02-05', 'TRANSLATE-5237', 'bugfix', 'Editor general - RootCause: Cannot read properties of undefined (reading \'isSynchronized\')', 'DEBUG: added logging for further insvestigation of a rare problem related to messagebox shown based on WebSocket event', '15'),
('2026-02-05', 'TRANSLATE-5235', 'bugfix', 'Editor general - RootCause: Cannot read properties of undefined (reading \'coordinatorGroup\')', 'FIXED: problem with tooltip for \'Coordinator Groups\'-tab ', '15'),
('2026-02-05', 'TRANSLATE-5231', 'bugfix', 'Editor general - RootCause: can\'t access property "get", this.task is undefined', 'FIXED: project task management is now temporarily disabled while project tasks grid is loading', '15'),
('2026-02-05', 'TRANSLATE-5229', 'bugfix', 'Editor general - RootCause: Cannot read properties of undefined (reading \'cantSaveEmptySegment\')', 'FIXED: error popping on attempt to save empty segment', '15'),
('2026-02-05', 'TRANSLATE-5223', 'bugfix', 'TermPortal - RootCause: Cannot read properties of undefined (reading \'getKey\')', 'FIXED: problem popping while typing keyword for terms search', '15'),
('2026-02-05', 'TRANSLATE-5221', 'bugfix', 'Editor general - Use a UI lib for HTML sanitizing instead just htmlEncode', 'Use a more sophisticated UI lib for HTML sanitising instead just encoding it.', '15'),
('2026-02-05', 'TRANSLATE-5220', 'bugfix', 'Task Management - InstantTranslate task multilingual visible for simple admins and PMs', 'Acl will be applied for multi instant-translate tasks.', '15'),
('2026-02-05', 'TRANSLATE-5216', 'bugfix', 'translate5 AI - Check why gpt 5.1 and 5.2 work only very inconsistently in MS Azure AI foundry and not at all at openai', '[🐞 Fix]  Added support of GPT-5 models', '15'),
('2026-02-05', 'TRANSLATE-5209', 'bugfix', 'Editor general - Error when opening a segment', '[🐞 Fix] Fix opening segment in case it has wrong internal tags markup', '15'),
('2026-02-05', 'TRANSLATE-5202', 'bugfix', 'Editor general - RootCause: can\'t access property "down", me.getTaskManagement() is undefined', 'FIXED: problem with too quick start of task editing after language resource associations were changed', '15'),
('2026-02-05', 'TRANSLATE-5200', 'bugfix', 'Editor general - Error occurs on attempt to save an empty segment', '[🐞 Fix] Fixed error being occurred on attempt to save an empty segment', '15'),
('2026-02-05', 'TRANSLATE-5199', 'bugfix', 'Editor general - Hardly (or not at all) visible spell check mark-up in some cases', '[🐞 Fix] Make spellcheck visible when min-max length is enabled', '15'),
('2026-02-05', 'TRANSLATE-5190', 'bugfix', 'Editor general - JS error in front end on random operations', 'Fix front end popping error', '15'),
('2026-02-05', 'TRANSLATE-5172', 'bugfix', 'LanguageResources - Syncronize customer-LR assoc data between LR-tab and Clients-tab\'s LR-subtab', 'FIXED: added missing syncronization between \'Language resources\'-tab and \'Clients\'-tab\'s \'Language resources\'-subtab', '15'),
('2026-02-05', 'TRANSLATE-5058', 'bugfix', 'Editor general - Improve segment content sanitation to prevent XSS attacks (finding  H1.1)', '7.34.2: Improve segment content checks
7.33.0: Solve an XSS attack vector in segment content.', '15'),
('2026-02-05', 'TRANSLATE-4920', 'bugfix', 'Editor general, TrackChanges - Accept-reject trackchanges works wrong', 'Fixed accept/reject trackchanges', '15'),
('2026-02-05', 'TRANSLATE-4902', 'bugfix', 'Editor general - RootCause: E1381 error on the backend (on segment save)', 'FIXED: task ID detection on segment saving', '15');