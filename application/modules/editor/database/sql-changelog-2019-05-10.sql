
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

INSERT INTO `LEK_change_log` (`dateOfChange`, `jiraNumber`, `type`, `title`, `description`, `userGroup`) VALUES ('2019-05-10', 'TRANSLATE-1403', 'feature', 'Anonymize users in the workflow', 'If configured via task template in the task, the users associated to a task are shown anonymized. ', '12'),
('2019-05-10', 'TRANSLATE-1648', 'change', 'Disable the drop down menu in the column head of the task grid via ACL', 'By default each role is allowed to use the drop down menu.', '8'),
('2019-05-10', 'TRANSLATE-1636', 'change', 'OpenID Connect: Automatically remove protocol from translate5 domain', 'The protocol (scheme) is determined automatically.', '8'),
('2019-05-10', 'VISUAL-64', 'change', 'VisualReview: Improve texts on leaving visualReview task', 'Just some small wording changes.', '12'),
('2019-05-10', 'TRANSLATE-1646', 'bugfix', 'The frontend inserts invisible BOM (EFBBBF) characters into the saved segment', 'This invisible character is removed right now on saving a segment. If there are such characters in the imported data, they are masked as a tag.', '12'),
('2019-05-10', 'TRANSLATE-1642', 'bugfix', 'Saving client with duplicate "translate5 domain" shows wrong error message', 'The error message was corrected.', '8'),
('2019-05-10', 'T5DEV-267', 'bugfix', 'GroupShare Integration: pre-translation and analysis does not work', 'This is fixed now, also the other GroupShare related issue: T5DEV-268: continue not inside a loop or switch', '12'),
('2019-05-10', 'TRANSLATE-1635', 'bugfix', 'OpenID Connect: Logout URL of TermPortal leads to error, when directly login again with OpenID via MS ActiveDirectory', 'The user is redirected now to the main login page.', '8'),
('2019-05-10', 'TRANSLATE-1633', 'bugfix', 'Across XLF comment import does provide wrong comment date', 'This is fixed now.', '12'),
('2019-05-10', 'TRANSLATE-1641', 'bugfix', 'Adjust the translate5 help window width and height', 'The window size was adjusted to more appropriate values.', '8'),
('2019-05-10', 'TRANSLATE-1640', 'bugfix', 'OpenID Connect: Customer domain is mandatory for OpenId group', 'This is not mandatory anymore.', '8'),
('2019-05-10', 'TRANSLATE-1632', 'bugfix', 'JS: Cannot read property \'length\' of undefined', 'This is fixed now.', '14'),
('2019-05-10', 'TRANSLATE-1631', 'bugfix', 'JS: me.store.reload is not a function', 'This is fixed now.', '14'),
('2019-05-10', 'TRANSLATE-337', 'bugfix', 'uniqid should not be used for security relevant issues', 'The usage of uniqid and the GUID generation is basing now on random_bytes.', '8'),
('2019-05-10', 'TRANSLATE-1639', 'bugfix', 'OpenID Connect: OpenId authorization redirect after wrong translate5 password', 'This is fixed now', '8'),
('2019-05-10', 'TRANSLATE-1638', 'bugfix', 'OpenID Connect: OpenId created user is not editable', 'The users are editable now.', '12');