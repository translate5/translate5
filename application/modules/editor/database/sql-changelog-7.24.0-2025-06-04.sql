
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
            
INSERT INTO `LEK_change_log` (`dateOfChange`, `jiraNumber`, `type`, `title`, `description`, `userGroup`) VALUES ('2025-06-04', 'TRANSLATE-4212', 'feature', 'Hotfolder Import, Import/Export - COTI-Level 2 Support for translate5', 'COTI-Level 2 Support for translate5', '15'),
('2025-06-04', 'TRANSLATE-4681', 'change', 'LanguageResources - ClientPM user gets misleading popup on attempt to change client list in language resource', 'Fix change of client list in language resource by Client PM', '15'),
('2025-06-04', 'TRANSLATE-4679', 'change', 'openai - Make capacity be configurable for Azure integration', 'Added new configuration value capacity for Azure cloud integration', '15'),
('2025-06-04', 'TRANSLATE-4654', 'change', 't5memory, TM Maintenance - Change batch deletion behavior in TM Maintenance', '7.24.0: Changes needed for feature release
7.23.3: Improved batch deletion in TM Maintenace for small batches', '15'),
('2025-06-04', 'TRANSLATE-4644', 'change', 'VisualReview / VisualTranslation - Improve Visual: Better Segmentation, better handling of linebreaks in the WYSIWYG', 'Visual improvement: Improve segmentation by finding repetitions immediately after a segment, improve the WYSIWYG by removing all linebreaks but headline-seperatory when exchanging texts
FIX cloning of tasks: Task-Meta was not cloned
REDUCE level of termtagger-warnings about tagging-errors to info', '15'),
('2025-06-04', 'TRANSLATE-4639', 'change', 'InstantTranslate - InstantTranslate changeFuzzy-Matches color scheme', 'Fuzzy matches are now styled with orange instead of red color to stop misleading the users', '15'),
('2025-06-04', 'TRANSLATE-4635', 'change', 't5memory - Add triggering worker in case there are reimport failed segments', 'In case there are segments that are failed to reimport due to t5memory overload - new worker is scheduled for another try. The worker is started after 15 minutes delay.', '15'),
('2025-06-04', 'TRANSLATE-4634', 'change', 'Import/Export - Improve error-handling / message for XLIFF2 import', 'Improved error message when importing a xliff2-file without okapi', '15'),
('2025-06-04', 'TRANSLATE-4619', 'change', 'openai - add link to LLM fine-tuning', 'GPT model props window: added info-icons with links to docs page', '15'),
('2025-06-04', 'TRANSLATE-4358', 'change', 'Hotfolder Import - Add functional test for AcrossHotfolder', 'New tests for Hotfolder plugin', '15'),
('2025-06-04', 'TRANSLATE-4085', 'change', 'Auto-QA - tooltip to ignore QA error too small, error count and quality loading', 'Solved Firefox-specific problem with appearance of qualities in the right panel and in toolip shown on right-click', '15'),
('2025-06-04', 'TRANSLATE-4076', 'change', 'usability language resources, usability task overview - Improve filtering', 'Improved toolbar items order and added active filters toolbar in all major tabs', '15'),
('2025-06-04', 'TRANSLATE-4061', 'change', 'Auto-QA - AutoQA: no content for spelling error', 'FIXED: appearance problem for qualities grid in the right side panel', '15'),
('2025-06-04', 'TRANSLATE-4042', 'change', 'Auto-QA - QA filter for solved segments can not be removed.', 'FIXED: An auto-QA filter can not be removed if all problems were solved.', '15'),
('2025-06-04', 'TRANSLATE-3712', 'change', 'usability editor - Enhance usability of consistency check', 'added sorting and background highlighting for inconsistent segments when one of corresponding AutoQA filters is in use', '15'),
('2025-06-04', 'TRANSLATE-4695', 'bugfix', 'WebSocket Server - Error: Class "editor_Models_task" does not exist', 'Fixed typo in class name leading to fatal error', '15'),
('2025-06-04', 'TRANSLATE-4692', 'bugfix', 'Workflows - Make KPI calculation and advanced filtering as fast as possible', '- Reduced task view refresh delay on certain filters change (match rate range, language resource type, language resource)
- Increased KPI window load timeout to 60s
- Optimized MariaDb/SQLite bulk write queries
- Added optional usage of DuckDb CLI  to directly query KPI-related data from a SQLite database file', '15'),
('2025-06-04', 'TRANSLATE-4688', 'bugfix', 'TBX-Import - Across TBX import problems with SSL URLs', 'Fixed the access to across instances with self signed certificates.', '15'),
('2025-06-04', 'TRANSLATE-4687', 'bugfix', 'User Management - Allow Client PM to create clients', 'Allow Client PM to create clients', '15'),
('2025-06-04', 'TRANSLATE-4682', 'bugfix', 'API - Fix percentage filter', 'Fix general DB error when using percentage filters (like finished column) as client PM.', '15'),
('2025-06-04', 'TRANSLATE-4678', 'bugfix', 'Configuration - Correct several config default values', 'Corrected the default value of several configuration values, which is used to determine if a value is considered as changed or not.', '15'),
('2025-06-04', 'TRANSLATE-4670', 'bugfix', 'Workflows - Optimize excessive data load in kpiAction', '7.24.0: Additional fixes regarding statistics performance
7.23.3: Reduced KPI window loading time - further improvements will follow', '15'),
('2025-06-04', 'TRANSLATE-4661', 'bugfix', 'I10N - Improve internal translation toolset', 'Improve the toolset to handle internal translations of the application', '15'),
('2025-06-04', 'TRANSLATE-4657', 'bugfix', 'VisualReview / VisualTranslation - Visual: SVG backround for empty PDFs optionally', 'Visual Ennhancement: Optionally detect empty imported or exchanged PDFs and render them as vector-grapghics', '15'),
('2025-06-04', 'TRANSLATE-4652', 'bugfix', 'Main back-end mechanisms (Worker, Logging, etc.) - Add missing languages', 'Adds additional language: sr-Latn-SP', '15'),
('2025-06-04', 'TRANSLATE-4651', 'bugfix', 'VisualReview / VisualTranslation - Fix hint for font-imports', 'Visual: Fix hint in import wizard about font-upload', '15'),
('2025-06-04', 'TRANSLATE-4598', 'bugfix', 't5memory - t5memory reboot not handled in import', 'Handle t5memory reboot in process of import', '15'),
('2025-06-04', 'TRANSLATE-4524', 'bugfix', 'Editor general - CTRL + INS/. should always use source for reference', 'CTRL + INS (CTRL + .) shortcut now inserts source field content instead of reference field content.', '15'),
('2025-06-04', 'TRANSLATE-4432', 'bugfix', 'Auto-QA - QA filter "Empty Segments" not persistent', 'FIXED: Empty Segments QA filter unintended unchecking problem', '15'),
('2025-06-04', 'TRANSLATE-4389', 'bugfix', 'Search & Replace (editor) - Timeouts and delays for user when use repetition editor', 'Repetition editor: process repetitions asynchronously if Frontend Message Bus plugin is active', '15');