
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

INSERT INTO `LEK_change_log` (`dateOfChange`, `jiraNumber`, `type`, `title`, `description`, `userGroup`) VALUES ('2019-12-18', 'TRANSLATE-1531', 'feature', 'Provide progress data about a task', 'Editors and PMs see the progress of the tasks.', '98'),
('2019-12-18', 'TRANSLATE-1896', 'change', 'Delete MemoQ QA-tags on import of memoq xliff', 'Otherwise the MemoQ file could not be imported', '96'),
('2019-12-18', 'TRANSLATE-1910', 'change', 'When talking to an OpenId server missing ssl certificates can be configured', 'If the SSO server uses a self signed certificate or is not configured properly a missing certificate chain can be configured in the SSO client used by translate5.', '64'),
('2019-12-18', 'TRANSLATE-1824', 'bugfix', 'xlf import does not handle some unicode entities correctly', 'The special characters are masked as tags now.', '96'),
('2019-12-18', 'TRANSLATE-1909', 'bugfix', 'Reset the task tbx hash when assigned termcollection to task is updated', 'For the termtagger a cached TBX is created out of all term-collections assigned to a task. On term-collection update this cached file is updated too.', '96'),
('2019-12-18', 'TRANSLATE-1885', 'bugfix', 'Several BugFixes in the GUI', 'Several BugFixes in the GUI', '64'),
('2019-12-18', 'TRANSLATE-1760', 'bugfix', 'TrackChanges: Bugs with editing content', 'Some errors according to TrackChanges were fixed.', '98'),
('2019-12-18', 'TRANSLATE-1864', 'bugfix', 'Usage of changealike editor may duplicate internal tags', 'This happened only under special circumstances.', '98'),
('2019-12-18', 'TRANSLATE-1804', 'bugfix', 'Segments containing only the number 0 are not imported', 'There were also problems on the export of such segments.', '96'),
('2019-12-18', 'TRANSLATE-1879', 'bugfix', 'Handle removals of corresponding opening and closing tags for tasks with and without trackChanges', 'If a removed tag was part of a tag pair, the second tag is deleted automatically.', '98');