
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
            
INSERT INTO `LEK_change_log` (`dateOfChange`, `jiraNumber`, `type`, `title`, `description`, `userGroup`) VALUES ('2023-08-04', 'TRANSLATE-3442', 'bugfix', 'Client management - Role Client-PM must have Customer-management enabled to have customers available in other views', 'FIX: Role "PM selected Clients" must have Customer management enabled to have accessible Customers in other management-views', '15'),
('2023-08-04', 'TRANSLATE-3441', 'bugfix', 'Editor general - Translate5 UI errors', 'Multiple fixes for UI errors.', '15'),
('2023-08-04', 'TRANSLATE-3433', 'bugfix', 'VisualReview / VisualTranslation - Segment selection/scrolling may leads to wrong "segment not found" toasts', 'translate5 - 6.5.0: BUG: segment selection/scrolling may leads to wrong "segment not found" toasts
translate5 - 6.5.1: Additional improvement', '15'),
('2023-08-04', 'TRANSLATE-3422', 'bugfix', 'TBX-Import - Language mapping does not work correctly for TBX, that are imported in a zip', 'Language matching is improved when importing TBX file in import zip package.', '15');