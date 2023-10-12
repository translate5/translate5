
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
            
INSERT INTO `LEK_change_log` (`dateOfChange`, `jiraNumber`, `type`, `title`, `description`, `userGroup`) VALUES ('2023-09-29', 'TRANSLATE-3403', 'feature', 'TermPortal - Show history of term or attribute in TermPortal', 'Added ability to show editing history for terms and attributes', '15'),
('2023-09-29', 'TRANSLATE-1436', 'feature', 'TermPortal - Add or propose terminology directly from translate5 task', 'Added ability to propose terminology right from the opened segment in the editor', '15'),
('2023-09-29', 'TRANSLATE-3500', 'change', 'ConnectWorldserver - Plugin ConnectWorldserver: finished task will (sometimes) not be transfered back to Worldserver', 'If connection to Worldserver does not exist, transfer back to Worldserver does not happen, but the task in Translate5 was finished nevertheless.
Now there is a check so its not possible to finish the task any more and a "connection-error" is shown to the user.', '15'),
('2023-09-29', 'TRANSLATE-3408', 'change', 'InstantTranslate - Implement proper segmentation for InstantTranslate', 'Improved segmentation for InstantTranslate to work like the  target segmentation of Okapi', '15'),
('2023-09-29', 'TRANSLATE-3503', 'bugfix', 'VisualReview / VisualTranslation - If pdf file contains brackets import fails', 'Fixed bug which caused task containing PDF files with square brackets in name fail to import', '15'),
('2023-09-29', 'TRANSLATE-3477', 'bugfix', 'User Management - Add missing ACL right and role documentation', 'Added ACL rights and role documentation.', '15');