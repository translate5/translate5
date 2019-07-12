
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

INSERT INTO `LEK_change_log` (`dateOfChange`, `jiraNumber`, `type`, `title`, `description`, `userGroup`) VALUES ('2019-03-21', 'TRANSLATE-1600', 'feature', 'TrackChanges: Make tracked change marks hideable via a button and keyboard short-cut', 'The tracked change marks are hideable via a the view mode menu and keyboard short-cut CTRL-SHIFT-E', '14'),
('2019-03-21', 'TRANSLATE-1390', 'feature', 'Microsoft translator can be used as language resource', 'The Microsoft translator can be used as language resource right now, it must be configured in the configuration before usage.', '12'),
('2019-03-21', 'TRANSLATE-1613', 'bugfix', 'The segment timestamp is not set properly with MySQL 8', 'The segment timestamp is not set properly with MySQL 8', '8'),
('2019-03-21', 'TRANSLATE-1612', 'bugfix', 'Task clone does not clone language resources', 'Now the language resources are also cloned for a task', '12'),
('2019-03-21', 'TRANSLATE-1604', 'bugfix', 'Jobs may not be created with status finished', 'Assigning a user in the status "finished" to a task makes no sense, so this is prohibited right now.', '12'),
('2019-03-21', 'TRANSLATE-1609', 'bugfix', 'API Usage: On task creation no PM can be explicitly defined', 'Now the PM can be set directly on task creation if the API user has the right to do so.', '8'),
('2019-03-21', 'TRANSLATE-1603', 'bugfix', 'Show the link to TermPortal in InstantTranslate only, if user has TermPortal access rights', 'Show the link to TermPortal in InstantTranslate only, if user has TermPortal access rights', '8'),
('2019-03-21', 'TRANSLATE-1595', 'bugfix', 'Match analysis export button is disabled erroneously', 'Match analysis export button is disabled erroneously, this is fixed right now.', '12'),
('2019-03-21', 'TRANSLATE-1597', 'bugfix', 'Concordance search uses only the source language', 'The concordance search was only searching in the source language, even if entered a search term in the target search field.', '14'),
('2019-03-21', 'TRANSLATE-1607', 'bugfix', 'Feature logout on page change disables language switch', 'The new feature to logout on each page change (browser close) disabled the language switch, this is fixed right now.', '14'),
('2019-03-21', 'TRANSLATE-1599', 'bugfix', 'Error in Search and Replace repaired', 'The error "Cannot read property \'segmentNrInTask\' of undefined" was fixed in search and replace', '14'),
('2019-03-21', 'T5DEV-266', 'bugfix', 'Sessions can be hijacked', 'A serious bug in the session handling was fixed.', '8');