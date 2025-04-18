
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
            
INSERT INTO `LEK_change_log` (`dateOfChange`, `jiraNumber`, `type`, `title`, `description`, `userGroup`) VALUES ('2025-04-17', 'TRANSLATE-4587', 'change', 'Main back-end mechanisms (Worker, Logging, etc.) - Define customer specific templates', 'Enable customer based templates(requires creation of separate template per client).', '15'),
('2025-04-17', 'TRANSLATE-4452', 'change', 't5memory, TM Maintenance - Improve user experience in TM Maintenance', 'Rework error messages in TM Maintenance to be more clear for the end user.', '15'),
('2025-04-17', 'TRANSLATE-4613', 'bugfix', 'Editor general - Deprecation error', 'FIXED: deprecation error flooding the php log', '15'),
('2025-04-17', 'TRANSLATE-4610', 'bugfix', 'LanguageResources - Results for match table are not rendered', 'Fix match table rendering', '15'),
('2025-04-17', 'TRANSLATE-4609', 'bugfix', 'TM Maintenance - Uncaught Error: Top-most item should be ...', 'FIXED: problems with distinction between automatic and user scrolling', '15'),
('2025-04-17', 'TRANSLATE-4608', 'bugfix', 't5memory, TM Maintenance - Amount of segments calculated and in the grid are different', 'Fixed bug which might prevent all segments to load into the list in TMMaintenance panel after search', '15'),
('2025-04-17', 'TRANSLATE-4606', 'bugfix', 'Content Protection - Content protection: Not same tag count in source and target after conversion', 'Fix tag filtering in TM conversion process', '15'),
('2025-04-17', 'TRANSLATE-4596', 'bugfix', 'Import/Export - Save resname comments', 'Fix resname comments saving', '15'),
('2025-04-17', 'TRANSLATE-4595', 'bugfix', 'Content Protection - Content protection: Apply current rules for updated segments in TM Maintenance', 'Apply current rules for updated segments in TM Maintenance', '15'),
('2025-04-17', 'TRANSLATE-4540', 'bugfix', 'Content Protection - Content Protection: Change logic of LR availability on TM Conversion', 'Content Protection: Language resource will be available for fuzzy searches when it is only scheduled for conversion and completely locked when conversion in progress', '15');