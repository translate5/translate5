
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
            
INSERT INTO `LEK_change_log` (`dateOfChange`, `jiraNumber`, `type`, `title`, `description`, `userGroup`) VALUES ('2025-06-20', 'TRANSLATE-3567', 'feature', 'Editor general - Open a task in translate5\'s editor from InstantTranslate', 'Added possibility to open task for editing from instant translate', '15'),
('2025-06-20', 'TRANSLATE-4721', 'change', 'InstantTranslate - Update German UI text in InstantTranslate', 'Use "Hochladen" in "Drag and Drop oder anklicken zum hochladen einer Datei" text', '15'),
('2025-06-20', 'TRANSLATE-4717', 'change', 'Content Protection - Rules for complex integers need some improvements', 'Improvement for complex integer rules', '15'),
('2025-06-20', 'TRANSLATE-4686', 'change', 'TermPortal - Add support for non-base64-encoded params hash', 'Term search params specified after #termportal/search/... can now be given as query string values, i.e. param1=value1&param2=value2 instead of their base64-encoded version', '15'),
('2025-06-20', 'TRANSLATE-4675', 'change', 'LanguageResources - Improve glossary usage', 'Use more wide range of terms as source for pre-translation with variety of language resources ', '15'),
('2025-06-20', 'TRANSLATE-4672', 'change', 'Workflows - reduce print approval job finish dialogue', 'Improve leaving print approval job dialog', '15'),
('2025-06-20', 'TRANSLATE-4653', 'change', 't5memory - Daily logging of t5memory specificData', 'The specificData field for t5memory language resources is logged daily separately for easier backup usage of the TMs.', '15'),
('2025-06-20', 'TRANSLATE-4723', 'bugfix', 'Content Protection, Import/Export - content protection tag short tag numbers are wrong in target for review task', 'Fixed bug which may cause short tag number for content protection tags to be different in target comparing to source for review tasks', '15'),
('2025-06-20', 'TRANSLATE-4722', 'bugfix', 'Editor general - RootCause: Invalid JSON - answer seems not to be from translate5', 'DEBUG: added logging for further investigation once this error pops again', '15'),
('2025-06-20', 'TRANSLATE-4719', 'bugfix', 'Editor general - RootCause: Unresolved Cannot read properties of null (reading \'style\')', 'DEBUG: suppressed ExtJS core javascript error and added logging if this would still lead to any follow-up error, for further investigation', '15'),
('2025-06-20', 'TRANSLATE-4718', 'bugfix', 'Editor general - RootCause: d is undefined', 'added debug code for further investigation if this error pop next time', '15'),
('2025-06-20', 'TRANSLATE-4715', 'bugfix', 'Repetition editor - Fix Message bus segment workflow', 'Fix Message bus segment workflow', '15'),
('2025-06-20', 'TRANSLATE-4702', 'bugfix', 'Task Management - ERROR in core: E0000 - Export folder not found or not write able', 'Sometimes task deletions lead to errors resulting in defect tasks. ', '15'),
('2025-06-20', 'TRANSLATE-4694', 'bugfix', 'Workflows - Rename "Visual approve" workflow', 'Renamed "Visual approve" workflow to "Review and Print approval"', '15'),
('2025-06-20', 'TRANSLATE-4646', 'bugfix', 'Hotfolder Import - "Missing files" Okapi errors in T5 multi-target project', 'Fix file handling in hotfolder processing', '15'),
('2025-06-20', 'TRANSLATE-4560', 'bugfix', 'Auto-QA - Check for normal space at start or end of segment missing in AutoQA', 'Added AutoQA check for a normal space at the start/end of a segment', '15'),
('2025-06-20', 'TRANSLATE-4353', 'bugfix', 'Search & Replace (editor) - Timeout when Searching/Replacing in Segments', 'Make Search and Replace editor feature to work asynchronously to improve user experience', '15');