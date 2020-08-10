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

INSERT INTO `LEK_change_log` (`dateOfChange`, `jiraNumber`, `type`, `title`, `description`, `userGroup`) VALUES ('2017-07-11', 'TRANSLATE-628', 'change', 'Log changed terminology in changes xliff', 'In the changes.xlf generated on workflow notifications, the terminology of the changed segments is added as valid mrk tags.', '14'),
('2017-07-11', 'TRANSLATE-921', 'bugfix', 'Saving ChangeAlikes reaches PHP max_input_vars limit with a very high repetition count', 'Saving ChangeAlikes reaches PHP max_input_vars limit with a very high repetition count', '8'),
('2017-07-11', 'TRANSLATE-922', 'bugfix', 'Segment timestamp updates only on the first save of a segment', 'Segment timestamp updates only on the first save of a segment', '14');