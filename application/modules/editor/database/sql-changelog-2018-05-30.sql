
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

INSERT INTO `LEK_change_log` (`dateOfChange`, `jiraNumber`, `type`, `title`, `description`, `userGroup`) VALUES ('2018-05-30', 'TRANSLATE-1269', 'change', 'Enable deletion of older terms in termportal', 'Enable deletion of older terms in termportal', '8'),
('2018-05-30', 'TINTERNAL-28', 'change', 'Change TBX Collection directory naming scheme', 'The TBX Collection directory naming scheme was changed', '8'),
('2018-05-30', 'TRANSLATE-1268', 'change', 'Pre-select language of term search with GUI-language', 'Pre-select language of term search with GUI-language', '12'),
('2018-05-30', 'TRANSLATE-1266', 'change', 'Show "-" as value instead of provisionallyProcessed', 'Show "-" as value instead of provisionallyProcessed', '12'),
('2018-05-30', 'TRANSLATE-1231', 'bugfix', 'xliff 1.2 import can not handle different number of mrk-tags in source and target', 'In Across xliff it can happen that mrk tags in source and target have a different structure. Such tasks can now imported into translate5.', '12'),
('2018-05-30', 'TRANSLATE-1265', 'bugfix', 'Deletion of task does not delete dependent termCollection', 'Deletion of task does not delete dependent termCollection', '8'),
('2018-05-30', 'TRANSLATE-1283', 'bugfix', 'TermPortal: Add GUI translations for Term collection attributes', 'Add GUI translations for Term collection attributes', '8'),
('2018-05-30', 'TRANSLATE-1284', 'bugfix', 'TermPortal: term searches are not restricted to a specific term collection', 'TermPortal: term searches are not restricted to a specific term collection', '8');