
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

INSERT INTO `LEK_change_log` (`dateOfChange`, `jiraNumber`, `type`, `title`, `description`, `userGroup`) VALUES ('2019-09-12', 'TRANSLATE-1736', 'feature', 'Config switch to disable sub-languages for TermPortal search field', 'Config switch to disable sub-languages for TermPortal search field', '8'),
('2019-09-12', 'TRANSLATE-1741', 'feature', 'Usage of user crowds in translate5', 'Multiple users assigned to a task can be used as user crowd. The first user who confirms the task will be responsible then, and unassign all other users.', '12'),
('2019-09-12', 'TRANSLATE-1734', 'feature', 'InstantTranslate: Preset of languages used for translation', 'If a user logs in the first time, the languages are now also preset to a sense-full value.', '8'),
('2019-09-12', 'TRANSLATE-1735', 'feature', 'Optionally make note field in TermPortal mandatory', 'Optionally make note field in TermPortal mandatory', '8'),
('2019-09-12', 'TRANSLATE-1733', 'feature', 'System config in TermPortal: All languages available for adding a new term?', 'System config in TermPortal: All languages available for adding a new term?', '8'),
('2019-09-12', 'TRANSLATE-1792', 'change', 'Make columns in user table of workflow e-mails configurable', 'Some workflow e-mails are containing user lists. The columns of that lists are now configurable.', '8'),
('2019-09-12', 'TRANSLATE-1791', 'change', 'Enable neutral salutation', 'Providing gender information for users is not mandatory anymore, salutation will be neutral in emails if value is omitted.', '12'),
('2019-09-12', 'TRANSLATE-1742', 'bugfix', 'Not configured mail server may crash application', 'Now the errors in connection to the mail server do not stop the request anymore, they are just logged.', '8'),
('2019-09-12', 'TRANSLATE-1771', 'bugfix', '"InstantTranslate Into" available in to many languages', '"InstantTranslate Into" available in to many languages', '8'),
('2019-09-12', 'TRANSLATE-1788', 'bugfix', 'Javascript error getEditorBody.textContent() is undefined', 'The error is fixed.', '8'),
('2019-09-12', 'TRANSLATE-1782', 'bugfix', 'Minor TermPortal bugs fixed', 'Minor TermPortal bugs fixed.', '8');