
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
            
INSERT INTO `LEK_change_log` (`dateOfChange`, `jiraNumber`, `type`, `title`, `description`, `userGroup`) VALUES ('2025-09-30', 'TRANSLATE-4006', 'change', 'Translate5 CLI - CLI service:autodiscovery should work also for disabled plugins in cloud installer', 'CLI: Service Autodiscovery gets a new option to run also on disabled plugins.', '15'),
('2025-09-30', 'TRANSLATE-5022', 'bugfix', 'LanguageResources - Performance issue with languageresources log counting query', 'Improved performance of log statistics queries by optimizing database indexing and query execution for faster results when counting language resource logs - executed on each language resource overview.', '15'),
('2025-09-30', 'TRANSLATE-5015', 'bugfix', 'VisualReview / VisualTranslation - Visual: Disable auto-conversion of Spreadsheets (XLSX, ...) on Import', 'Visual: Disable automatic conversion of Excel/spreadsheets on import when automatic conversion of office files is active', '15'),
('2025-09-30', 'TRANSLATE-5009', 'bugfix', 'TermPortal - Optimize term image handling for huge term sets', 'The query that removes unused image records has been optimized. It now uses better indexing and avoids unnecessary full table scans, resulting in a faster cleanup of old data.', '15'),
('2025-09-30', 'TRANSLATE-5006', 'bugfix', 'Import/Export - Multiple problems with segment materialised view', 'The materialized views of per task segment data are created as innodb tables and cleaned up daily only now', '15'),
('2025-09-30', 'TRANSLATE-5002', 'bugfix', 'Search & Replace (editor) - RootCause: Cannot read properties of null (reading \'editor\')', 'Fixed JS error by doing search retry on richeditor instantiation if it was not successful within the previous attempt', '15'),
('2025-09-30', 'TRANSLATE-5001', 'bugfix', 'Editor general - RootCause: can\'t access property "setAttribute", me.inputCmp.el.down(...).dom is undefined', 'Fixed JS error by adding check for opened segment editor prior it\'s height sync', '15'),
('2025-09-30', 'TRANSLATE-4991', 'bugfix', 'Editor general - comments and search fields: copy+paste not possible any more', 'Copy/paste is not prevented anymore when segment is not opened', '15'),
('2025-09-30', 'TRANSLATE-4990', 'bugfix', 'TermTagger integration - Wrong Term - Segment connection / assignment', 'Source terms\' languages are now aligned to match task source language (unless there\'s a sublanguage mismatch) to make those terms recognizable by TermTagger', '15'),
('2025-09-30', 'TRANSLATE-4989', 'bugfix', 'Client management - prevent deletion of defaultcustomer', 'Deletion of defaultcustomer is no longer possible as deleted defaultcustomer leads to a non working installation.', '15'),
('2025-09-30', 'TRANSLATE-4988', 'bugfix', 'Export - LF (Line feeds) inserted in im XLIFF 2.1 Export', 'Removed extra line feeds inserted in XLIFF 2.1 export', '15'),
('2025-09-30', 'TRANSLATE-4970', 'bugfix', 'Editor general - Strange request to GET /Editor.model.admin.TaskUserAssoc leading to RootCause error. 3rd attempt', 'Fixed reason of a strange GET request', '15'),
('2025-09-30', 'TRANSLATE-4959', 'bugfix', 'Auto-QA - QA logic problem for added/removed whitespaces', 'Removed whitespace tags\' indices from the QA detection logic', '15'),
('2025-09-30', 'TRANSLATE-4954', 'bugfix', 'Task Management - task property tabs not acessible for ended tasks', 'Inner components (fields, cell-editors, buttons) within \'Users\', \'Language resources\' and \'Properties\' task-tabs are now disabled instead of those tabs themselves', '15'),
('2025-09-30', 'TRANSLATE-4270', 'bugfix', 'Installation & Update - Service Autodiscovery does not work with custom ports', 'Service autodiscovery is not able to deal with custom ports.', '15');