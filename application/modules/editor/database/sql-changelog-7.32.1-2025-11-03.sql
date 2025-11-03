
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
            
INSERT INTO `LEK_change_log` (`dateOfChange`, `jiraNumber`, `type`, `title`, `description`, `userGroup`) VALUES ('2025-11-03', 'TRANSLATE-5042', 'change', 't5memory - Redo TMX cut off functionality', '7.31.2 & 7.32.1: Fix in file naming
7.31.0: Improve RAM usage on TMX import process', '15'),
('2025-11-03', 'TRANSLATE-5099', 'bugfix', 'Editor general - F3 shortcut problem when source editing is enabled', 'FIXED: problem with F3 shortcut when source editing is enabled', '15'),
('2025-11-03', 'TRANSLATE-5091', 'bugfix', 'TermTagger integration - Sort terms in TermPortlet not working in certain scenarios', 'FIXED: fixed terms sorting logic for TermPortlet', '15');