
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
            
INSERT INTO `LEK_change_log` (`dateOfChange`, `jiraNumber`, `type`, `title`, `description`, `userGroup`) VALUES ('2025-08-26', 'TRANSLATE-4910', 'bugfix', 'Editor general - Approve job popup is not shown', 'Fix problem where approve job popup was not displayed on task open', '15'),
('2025-08-26', 'TRANSLATE-4909', 'bugfix', 'Editor general - Prompt for leaving the task when requests are pending', 'Prompt will be shown when task leave button is clicked while there are pending requests.', '15'),
('2025-08-26', 'TRANSLATE-4908', 'bugfix', 'Editor general - RootCause: arrLength is not defined', 'FIXED: incorrect visiblity for couple of variables', '15'),
('2025-08-26', 'TRANSLATE-4905', 'bugfix', 'Editor general - Strange request to GET /Editor.model.admin.TaskUserAssoc leading to RootCause error. 2nd attempt', 'DEBUG: added more debug code, plus added a fix to see if it help', '15'),
('2025-08-26', 'TRANSLATE-4904', 'bugfix', 'Editor general - RootCause undefined is not an object (evaluating \'string.replace\')', 'Fixed bug which prevented some special characters to be inserted in visual review', '15'),
('2025-08-26', 'TRANSLATE-4901', 'bugfix', 'Editor general - RootCause: E1381 error on the backend (caused by VisualReview)', 'FIXED: segment refresh for 1st user when changed by 2nd user - is now prevented if 1st user left the task or it\'s another task', '15');