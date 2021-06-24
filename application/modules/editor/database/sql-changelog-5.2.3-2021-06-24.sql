
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
            
INSERT INTO `LEK_change_log` (`dateOfChange`, `jiraNumber`, `type`, `title`, `description`, `userGroup`) VALUES ('2021-06-24', 'TRANSLATE-2556', 'bugfix', 'PHP Error Specified column previousOrigin is not in the row', 'This error was triggered in certain circumstances by the import of SDLXLIFF files containing empty origin information.', '15'),
('2021-06-24', 'TRANSLATE-2555', 'bugfix', 'XML errors in uploaded TMX files are not shown properly in the TM event log', 'The XML error was logged in the system log, but was not added to the specific log of the TM. This is changed now so that the PM can see what is wrong.', '15'),
('2021-06-24', 'TRANSLATE-2554', 'bugfix', 'BUG TermTagger Worker: Workers are scheduled exponentially', 'FIXED: Bug in TermTagger Worker leads to scheduling workers exponentially what causes database deadlocks', '15'),
('2021-06-24', 'TRANSLATE-2552', 'bugfix', 'Typos in translate5', 'Fixes couple of typos in translate5 locales', '15');