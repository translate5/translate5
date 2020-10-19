
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
            
INSERT INTO `LEK_change_log` (`dateOfChange`, `jiraNumber`, `type`, `title`, `description`, `userGroup`) VALUES ('2020-10-14', 'TRANSLATE-2246', 'change', 'Move the Ip based exception and the extended user model into the same named Plugin', 'Some code refactoring.', '8'),
('2020-10-14', 'TRANSLATE-2259', 'bugfix', 'Inconsistent workflow may lead in TaskUserAssoc Entity Not Found error when saving a segment.', 'The PM is allowed to set the Job associations as they want it. This may lead to an inconsistent workflow. One error when editing segments in an inconsistent workflow is fixed now.', '15'),
('2020-10-14', 'TRANSLATE-2258', 'bugfix', 'Fix error E1161 "The job can not be modified due editing by a user" so that it is not triggered by viewing only users.', 'The above mentioned error is now only triggered if the user has opened the task for editing, before also a readonly opened task was triggering that error.', '12'),
('2020-10-14', 'TRANSLATE-2247', 'bugfix', 'New installations save wrong mysql executable path (for installer and updater)', 'Fix a bug preventing new installations to be usable.', '8'),
('2020-10-14', 'TRANSLATE-2045', 'bugfix', 'Use utf8mb4 charset for DB', 'Change all utf8 fields to the mysql datatype utf8mb4. ', '8');
