
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
            
INSERT INTO `LEK_change_log` (`dateOfChange`, `jiraNumber`, `type`, `title`, `description`, `userGroup`) VALUES ('2024-04-09', 'TRANSLATE-3860', 'change', 'VisualReview / VisualTranslation - FIX: Bugs of Visual Enhancements Milestone I', 'FIX Visual: Segments may be duplicated in the reflow / WYSIWYG and appear multiple times overlapping other segments', '15'),
('2024-04-09', 'TRANSLATE-3865', 'bugfix', 'LanguageResources, User Management - Error on language resources for admins with no assigned users', 'Fix for a problem in language resources overview for users with 0 assigned customers.', '15'),
('2024-04-09', 'TRANSLATE-3864', 'bugfix', 'Import/Export - Problem with sdlxliff export and track changes', 'Fix for sdlxliff export and track changes.', '15');