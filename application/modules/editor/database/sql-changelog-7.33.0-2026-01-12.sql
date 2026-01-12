
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
            
INSERT INTO `LEK_change_log` (`dateOfChange`, `jiraNumber`, `type`, `title`, `description`, `userGroup`) VALUES ('2026-01-12', 'TRANSLATE-5191', 'change', 't5memory - Rename from OpenTM2 to t5memory in UI', 'Rename OpenTM2 to T5Memory', '15'),
('2026-01-12', 'TRANSLATE-5175', 'change', 'Editor general - Rework internal localization of translate5 texts and add FR and IT', 'Rework of localization: Cleanup of existing localization XLIFFs and introduce IT and FR as new translations of the application.
', '15'),
('2026-01-12', 'TRANSLATE-5160', 'change', 'Okapi integration - Modify default okapi MIF settings', 'Introduced modified default okapi MIF settings as t5 default Adobe FrameMaker MIF documents\' settings', '15'),
('2026-01-12', 'TRANSLATE-5125', 'change', 'Installation & Update, Workflows - Activate KPI statistics feature for on-premise clients', 'Enabled KPI statistics for Levenshtein distance and post-editing time across all installations', '15'),
('2026-01-12', 'TRANSLATE-5115', 'change', 'Test framework - Solve failing test "OkapiExportBconfTest" in local test-runs', 'FIX: Test-suite was not running properly', '15'),
('2026-01-12', 'TRANSLATE-5101', 'change', 'InstantTranslate - InstantTranslate form make use of project description a config', 'Task description field can be configured if visible or not in human revision popup.', '15'),
('2026-01-12', 'TRANSLATE-5089', 'change', 't5memory - Set mock context for segments on update', 'Set mock context for segments on update', '15'),
('2026-01-12', 'TRANSLATE-5077', 'change', 'Editor general - Introduce info icon and tooltip with segment meta data in concordance search analogous to fuzzy panel', 'Concordance search results now display a popup with meta data, just like in the "Matches" tab', '15'),
('2026-01-12', 'TRANSLATE-4950', 'change', 'Editor general - Implement a helper to find XSS vulnerabilities in translate5 and fix found vulnerabilities', '7.33.0: Fix a place in termportal what was not working anymore due the XSS fixes + additional XSS preventions
7.29.2: In order to be save against XSS attacks a helper tool to find them while implementation is introduced, so found vulnerabilities are fixed too.', '15'),
('2026-01-12', 'TRANSLATE-4872', 'change', 'Editor general - Add possibility to hide languages to be used as source or target language', '[🆕 Feature] Added possibility to hide languages via CLI command in the front-end selection fields', '15'),
('2026-01-12', 'TRANSLATE-5068', 'bugfix', 'InstantTranslate - custom field "required" not met for send to human revision pop-up', 'Required flag will be respected in human revision popup for custom fields', '15'),
('2026-01-12', 'TRANSLATE-5058', 'bugfix', 'Editor general - Improve segment content sanitation to prevent XSS attacks (finding  H1.1)', 'Solve an XSS attack vector in segment content.', '15');