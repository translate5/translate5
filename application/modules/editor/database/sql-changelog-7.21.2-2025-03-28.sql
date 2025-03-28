
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
            
INSERT INTO `LEK_change_log` (`dateOfChange`, `jiraNumber`, `type`, `title`, `description`, `userGroup`) VALUES ('2025-03-28', 'TRANSLATE-3535', 'feature', 'Task Management - Evaluate postediting time and levenshtein distance', 'translate5 - 7.21.0: Added segments editing history data aggregation to calculate and display KPIs related to levenshtein distances and post-editing time
translate5 - 7.21.2: Automated test fixes', '15'),
('2025-03-28', 'TRANSLATE-4575', 'change', 'openai - Training window broken in certain cases', 'FIXED: training window UI broken if at least one unsuccesful training present
', '15'),
('2025-03-28', 'TRANSLATE-4309', 'change', 'file format settings - Add proper Pipeline management & Validation', 'Pipeline validation & management by steps and step-properties', '15');