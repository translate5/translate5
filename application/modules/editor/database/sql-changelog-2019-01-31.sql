
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

INSERT INTO `LEK_change_log` (`dateOfChange`, `jiraNumber`, `type`, `title`, `description`, `userGroup`) VALUES ('2019-01-31', 'TRANSLATE-1555', 'change', 'Okapi Import: Add SRX segmentation rules for most common languages', 'Add SRX segmentation rules for most common languages (bg, br, cs, da, de, el, en, es, et, fi, fr, hr, hu, it, ja, ko, lt, lv, nl, pl, pt, ro, ru, sk, sl, sr, tw, zh)', '12'),
('2019-01-31', 'TRANSLATE-1557', 'bugfix', 'Implement the missing workflow step workflowEnded', 'For some workflow configurations a defined end of the workflow is needed. Therefore the step workflowEnded was created.', '12'),
('2019-01-31', 'TRANSLATE-1299', 'bugfix', 'metaCache generation is cut off by mysql setting', 'In the frontend some strange JSON errors can appear if there were to much MRK tags in one segment.', '12'),
('2019-01-31', 'TRANSLATE-1554', 'bugfix', 'List only terms in task languages combination in editor terminology list', 'When editing a task with multilingual TBX imported, all languages were shown in the term window of a segment.', '14'),
('2019-01-31', 'TRANSLATE-1550', 'bugfix', 'unnecessary okapiarchive.zip wastes harddisk space', 'On each import an additional okapiarchive.zip was created, although there was no Okapi import.', '8');