
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
            
INSERT IGNORE INTO `LEK_change_log` (`dateOfChange`, `jiraNumber`, `type`, `title`, `description`, `userGroup`) VALUES ('2025-09-11', 'TRANSLATE-3569', 'feature', 'VisualReview / VisualTranslation - provide Visual for MS Office files via LibreOffice CMD', '7.29.1: Added service to autodiscovery
7.28.7: Added service to autodiscovery
7.28.0: Added ability to have a visual from MS Office of LibreOffice-Files without having to upload the PDF version', '15'),
('2025-09-11', 'TRANSLATE-4939', 'change', 'Installation & Update - Update tfpdf library', 'The tfpdf library is updated to 1.33 v', '15'),
('2025-09-11', 'TRANSLATE-4964', 'bugfix', 'Main back-end mechanisms (Worker, Logging, etc.) - User customer column need more characters', '[🐞 Fix] The size of the column for user customers is increased.', '15'),
('2025-09-11', 'TRANSLATE-4963', 'bugfix', 'Editor general - RootCause: Cannot read properties of null (reading \'getStore\')', '[🐞 Fix] Concordance search request is now aborted on task leave', '15'),
('2025-09-11', 'TRANSLATE-4962', 'bugfix', 'Editor general - Cannot read properties of undefined (reading \'reverseTransform\')', '[🐞 Fix] Prevent saving segment when editor is not yet properly instantiated', '15'),
('2025-09-11', 'TRANSLATE-4961', 'bugfix', 'VisualReview / VisualTranslation - Visual Exchange at times runs into an worker-exception and is stuck', '[🐞 Fix] Some exceptions in visual workers need special handling when occurring within a visual exchange', '15'),
('2025-09-11', 'TRANSLATE-4956', 'bugfix', 'Editor general - RootCause: The segment could not be opened for editing, since the previously opened segment was not correctly saved yet.', '[🐞 Fix] Added a fix for the possible wrong check implementation to see if this help', '15'),
('2025-09-11', 'TRANSLATE-4955', 'bugfix', 'User Management - Show all login log entries to a user and unlock user via CLI', '[🐞 Fix] Show all login log entries to a user (failed were missing) and unlock user via CLI instead of direct DB.', '15'),
('2025-09-11', 'TRANSLATE-4951', 'bugfix', 'VisualReview / VisualTranslation - Better titles for visual configs pointing at InstantTranslate\'s file-translation', '[🐞 Fix] Improve titles/descriptions for Visual configs relevant for InstantTranslate', '15'),
('2025-09-11', 'TRANSLATE-4949', 'bugfix', 'Main back-end mechanisms (Worker, Logging, etc.) - Maintenance Mode check blocks application', '[🐞 Fix] A check in the UI if the maintenance mode is still active was implemented in a way what could led to totally blocked server.', '15'),
('2025-09-11', 'TRANSLATE-4948', 'bugfix', 'Export - Internal Tags get lost when "surrounded" by trackchanges del-tags', '[🐞 Fix] Tags may get lost when surrounded by trackchanges deletions without other content inbetween', '15'),
('2025-09-11', 'TRANSLATE-4945', 'bugfix', 'Editor general - RootCause: Cannot read properties of undefined (reading \'editingPlugin\')', '[🐞 Fix] Editor-specific event handlers are now cleaned up on task exit', '15'),
('2025-09-11', 'TRANSLATE-4944', 'bugfix', 'MatchAnalysis & Pretranslation, t5memory - Long segment causes error in fuzzy', '[🐞 Fix]  Fixed error preventing pretranslation to run in case task contains to long segments', '15'),
('2025-09-11', 'TRANSLATE-4943', 'bugfix', 'Editor general - deletion not treated as deletion (length restriction)', '[🐞 Fix] Fixed issue when deleted new line tag was not treated as deleted and was taking into account in length restriction calculation', '15'),
('2025-09-11', 'TRANSLATE-4942', 'bugfix', 'Workflows - Problem with TrackChanges permission on job creation', '[🐞 Fix] Fix permission checkboxes on user job creation', '15'),
('2025-09-11', 'TRANSLATE-4941', 'bugfix', 'Editor general - Whitespace in the end of written text is removed', '[🐞 Fix] Fixed removing whitespace at the end of the written text after applying spellcheck markup', '15'),
('2025-09-11', 'TRANSLATE-4931', 'bugfix', 'Content Protection - Content Protection: plus/minus sign not in output', '[🐞 Fix] Handling of symbol prefix in rules with transforming feature', '15'),
('2025-09-11', 'TRANSLATE-4911', 'bugfix', 'Editor general - Ctrl + V and Ctrl + 1 not working reliably', '[🐞 Fix] problems with Ctrl+V and Ctrl+1 keyboard shortcuts when used outside of htmleditor', '15');
