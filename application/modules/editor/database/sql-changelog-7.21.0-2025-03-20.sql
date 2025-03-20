
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
            
INSERT INTO `LEK_change_log` (`dateOfChange`, `jiraNumber`, `type`, `title`, `description`, `userGroup`) VALUES ('2025-03-20', 'TRANSLATE-4369', 'feature', 'openai - OpenAI GPT Training Improvements', 'OpenAI Plugin: Improve the UI & introduce prompt management', '15'),
('2025-03-20', 'TRANSLATE-4361', 'feature', 'Import/Export - Add filters to html-report', 'Task HTML Export: JS UI filters implemented', '15'),
('2025-03-20', 'TRANSLATE-3647', 'feature', 'Okapi integration - Okapi integration: Enable down- and upload of fprm and pln files and pre-parsing of xliff by Okapi', 'Added down- and upload of any FPRM and Pipeline files. Added optional pre-parsing of xliff with OKAPI. The pre-parsing will be only possible with file-format-settings created from the date of the release on. With this release, the translate5-default settings do not have XLIFF-extensions mapped anymore and if such extension-mapping is added to a file-format, the pre-parsing of XLIFF will be active.', '15'),
('2025-03-20', 'TRANSLATE-3535', 'feature', 'Task Management - Evaluate postediting time and levenshtein distance', 'Added segments editing history data aggregation to calculate and display KPIs related to levenshtein distances and post-editing time', '15'),
('2025-03-20', 'TRANSLATE-2971', 'feature', 'Task Management, Workflows - Set job deadlines for multiple projects or tasks at the same time', '7.21.0: Fix: no task selected alert if tasks are selected
7.15.0: Added ability to set job deadlines for multiple projects or tasks at the same time', '15'),
('2025-03-20', 'TRANSLATE-4492', 'change', 'InstantTranslate - Speed test for synchronous file translation end-point', 'Optimized synchronous file translation end-point response time and added speed test for it', '15'),
('2025-03-20', 'TRANSLATE-4459', 'change', 'Client management - Improve okapi commands (and others) for single instances and for the cloud', '7.21.0: CLI t5 okapi:purge command got option --server-name
Improve Client management Commands for Okapi and cloud instances', '15'),
('2025-03-20', 'TRANSLATE-4010', 'change', 'Okapi integration - BCONF: Add XSLT step to pipeline', 'Make Okapi integration more resilient regarding the usage of not supported features in the bconf', '15'),
('2025-03-20', 'TRANSLATE-4545', 'bugfix', 'TM Maintenance - Scrolling problem', 'FIXED: new segments weren\'t shown into grid despite loaded', '15'),
('2025-03-20', 'TRANSLATE-4544', 'bugfix', 'InstantTranslate - Tooltip over source and target language remains hanging after language change', 'When changing the source or target language the tooltip did remain over the select and the textfields below till the user clicks somewhere else.', '15'),
('2025-03-20', 'TRANSLATE-4516', 'bugfix', 'Import/Export - Okapi CLI repair tool', 'Added "t5 patch:okapi146whitespace" CLI command to detect and add back missing whitespace characters caused by Okapi 1.46 segmenting issue', '15'),
('2025-03-20', 'TRANSLATE-4488', 'bugfix', 'Workflows - Batch set properties - date is applied also to unselected workflow steps', 'Batch set properties - fix incorrect behavior when filtering by customer is applied', '15'),
('2025-03-20', 'TRANSLATE-4286', 'bugfix', 'Task Management - Average processing time KPIs - add calculation for workflow steps', 'Average processing time KPIs: corrected UI texts and added calculation for workflow steps taking into account filters selected', '15'),
('2025-03-20', 'TRANSLATE-4230', 'bugfix', 'Export - tag-check broken for excel re-import', 'Excel Re-Import:
- escaping fixed for unwanted < and > brackets', '15');