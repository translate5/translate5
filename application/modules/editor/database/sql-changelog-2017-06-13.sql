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

INSERT INTO `LEK_change_log` (`dateOfChange`, `jiraNumber`, `type`, `title`, `description`, `userGroup`) VALUES ('2017-06-13', 'TRANSLATE-885', 'feature', 'On translation tasks the original target content and its hash for the repetition editor is also filled', 'On translation tasks (all targets are initially empty) the original target column and its hash for the repetition editor is also filled with the translated content of the editable target column.', '12'),
('2017-06-13', 'TRANSLATE-894', 'feature', 'The source content (incl. Tags) can be copied to the target column with CTRL-INS', 'The source content (incl. Tags) can be copied to the target column with CTRL-INS', '14'),
('2017-06-13', 'TRANSLATE-895', 'feature', 'Individual tags can be copied from source to target by pressing CTRL + , followed by the tagnumber', 'Individual tags can be copied from source to target by pressing CTRL + , (comma) followed by the tagnumber.', '14'),
('2017-06-13', 'TRANSLATE-901', 'feature', 'A flexible extendable task creation wizard was introduced', 'A flexible extendable task creation wizard was introduced', '14'),
('2017-06-13', 'TRANSLATE-902', 'feature', 'With the Globalese Plug-In pretranslation with Globalese Machine Translation is possible', 'With the Globalese Plug-In pretranslation with Globalese Machine Translation is possible', '14'),
('2017-06-13', 'TRANSLATE-296', 'change', 'Internal code refactoring to unify handling with special characters on the import', 'Some internal code refactoring was done, to unify the escaping of special characters and whitespace characters for all available import formats.', '12'),
('2017-06-13', 'TRANSLATE-896', 'change', 'The button layout on the segment grid toolbar was optimized', 'The button layout on the segment grid toolbar was optimized.', '14');