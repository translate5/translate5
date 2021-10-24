
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
            
INSERT INTO `LEK_change_log` (`dateOfChange`, `jiraNumber`, `type`, `title`, `description`, `userGroup`) VALUES ('2021-10-11', 'TRANSLATE-2637', 'change', 'Warn regarding merging terms', 'Warning message will be shown when using merge terms functionality in term collection import/re-import', '15'),
('2021-10-11', 'TRANSLATE-2630', 'change', 'Add language resource name to language resource pop-up - same for projects', 'Improves info messages and windows titles in language resources, project and task overview.', '15'),
('2021-10-11', 'TRANSLATE-2597', 'bugfix', 'Set resource usage log lifetime by default to 30 days', 'This will set the default lifetime days for resources usage log configuration to 30 days when there is no value set.', '15'),
('2021-10-11', 'TRANSLATE-2528', 'bugfix', 'Instant-translate and Term-portal route after login', 'Fixed problems accessing TermPortal / InstantTranslate with external URLs.', '15');