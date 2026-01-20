
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
            
INSERT INTO `LEK_change_log` (`dateOfChange`, `jiraNumber`, `type`, `title`, `description`, `userGroup`) VALUES ('2026-01-20', 'TRANSLATE-5214', 'change', 'ConnectWorldserver - Plugin Connect Worldserver: Integrity Constraint Violation on deleting Pretranslation task', 'Plugin Connect Worldserver:
- improved auto-deleting of pretranslation tasks', '15'),
('2026-01-20', 'TRANSLATE-5211', 'change', 'Comments - Excel Export gets now also comments in translate5.', 'Task excel export contains now also the existing comments in translate5.', '15'),
('2026-01-20', 'TRANSLATE-5175', 'change', 'Editor general - Rework internal localization of translate5 texts and add FR and IT', '7.33.3: Proper encoding of special characters in different languages
7.33.1: Fix so that client specific texts are working again
7.33.0: Rework of localization: Cleanup of existing localization XLIFFs and introduce IT and FR as new translations of the application.
', '15'),
('2026-01-20', 'TRANSLATE-5168', 'change', 'InstantTranslate - imrpovement form field "send to human revision"', 'Improve locales in human revision prompt window.', '15'),
('2026-01-20', 'TRANSLATE-5169', 'bugfix', 'InstantTranslate - custom name for InstantTranslate not shown correctly for InstantTranslate only users', 'Standalone instant-translate tab will share same title as when combined with term-portal.', '15');