
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
            
INSERT INTO `LEK_change_log` (`dateOfChange`, `jiraNumber`, `type`, `title`, `description`, `userGroup`) VALUES ('2023-10-04', 'TRANSLATE-3504', 'feature', 'VisualReview / VisualTranslation - Improve t5memory cli management', 'Added a new command for deleting t5memory language resource', '15'),
('2023-10-04', 'TRANSLATE-1436', 'feature', 'TermPortal - Add or propose terminology directly from translate5 task', 'translate5 - 6.6.0: Added ability to propose terminology right from the opened segment in the editor
translate5 - 6.6.1: Additional UI improvements', '15'),
('2023-10-04', 'TRANSLATE-3510', 'bugfix', 'Main back-end mechanisms (Worker, Logging, etc.) - Remove error log summary mail', 'The error log summary e-mail is removed in favour of the error log available in the UI.', '15'),
('2023-10-04', 'TRANSLATE-3497', 'bugfix', 'Editor general, Search & Replace (editor) - "replace all" disabled', '\'Replace all\' button is now disabled if task is really opened by more than one user', '15'),
('2023-10-04', 'TRANSLATE-3487', 'bugfix', 'Editor general - Taking over fuzzy matches in the UI may lead to corrupted internal tags', '6.5.4: FIX: Segments with more then 9 tags were producing errors in the UI
6.5.3: Taking over fuzzy matches in the UI was producing corrupted internal tags. In the Editor the tags were looking correctly, but on database level they did contain the wrong content. 
6.5.5: Fix problem with locked segment tag
6.6.1: Fix problem in old safari browsers', '15');