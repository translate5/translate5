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

INSERT INTO `LEK_change_log` (`dateOfChange`, `jiraNumber`, `type`, `title`, `description`, `userGroup`) VALUES ('2017-04-24', 'TRANSLATE-871', 'feature', 'New Tooltip should show segment meta data over segmentNrInTask column', 'A new tooltip over the segments segmentNrInTask column should show all segment meta data expect the data which is already shown (autostates, matchrate and locked (by css))', '14'),
('2017-04-24', 'TRANSLATE-823', 'change', 'Internal tags are ignored for relais import segment comparison ', 'When relais data is imported, the source columns of relais and normal data are compared to ensure that the alignment is correct. In this comparison internal tags are ignored now completely. Also HTML entities are getting normalized on both sides of the comparison.', '12'),
('2017-04-24', 'TRANSLATE-870', 'change', 'Enable MatchRate and Relais column per default in ergonomic mode', 'Enable MatchRate and Relais column per default in ergonomic mode', '14'),
('2017-04-24', 'TRANSLATE-875', 'bugfix', 'The width of the relais column was calculated wrong', 'Since using ergonomic mode as default mode, the width of the relais column was calculated too small', '14');