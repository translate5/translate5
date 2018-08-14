
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

INSERT INTO `LEK_change_log` (`dateOfChange`, `jiraNumber`, `type`, `title`, `description`, `userGroup`) VALUES ('2018-08-14', 'TRANSLATE-1376', 'bugfix', 'Segment length calculation does not include length of content outside of mrk tags', 'The segment length calculation contains now also the content outside of MRK tags, mostly whitespace.', '14'),
('2018-08-14', 'TRANSLATE-1399', 'bugfix', 'Using backspace on empty segment increases segment length', 'Using the backspace or delete key on empty segment increases segment length instead doing nothing.', '14'),
('2018-08-14', 'TRANSLATE-1395', 'bugfix', 'Enhance error message on missing relais folder', 'Enhance error message on missing relais folder', '8'),
('2018-08-14', 'TRANSLATE-1379', 'bugfix', 'TrackChanges: disrupt conversion into japanese characters', 'TrackChanges was not working properly with japanase characters', '14'),
('2018-08-14', 'TRANSLATE-1373', 'bugfix', 'TermPortal: TermCollection import stops because of unsaved term', 'TermCollection import stops because of unsaved term', '8'),
('2018-08-14', 'TRANSLATE-1372', 'bugfix', 'TrackChanges: Multiple empty spaces after export', 'TrackChanges: Multiple empty spaces after export', '12');