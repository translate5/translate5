
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

INSERT INTO `LEK_change_log` (`dateOfChange`, `jiraNumber`, `type`, `title`, `description`, `userGroup`) VALUES ('2017-12-06', 'TRANSLATE-1074', 'feature', 'On using the auth hash to open a task, finished tasks should be openable readonly.', 'Clicking on a link with a authhash the user does not know it the task is already finished. If the task is in status finished or waiting it should be opened readonly.', '12'),
('2017-12-06', 'TRANSLATE-1055', 'change', 'Disable the rootcause feedback button.', 'Disable the rootcause feedback button.', '12'),
('2017-12-06', 'TRANSLATE-1073', 'change', 'Update configured languages.', 'All major languages are added and in some cases also corrected according rfc5646 standards. Where available MS LCIDs are added. Users who use language shortcuts that are not the same as rfc5646 should be careful with importing the corresponding changes sql to their database.', '12'),
('2017-12-06', 'TRANSLATE-1072', 'change', 'Set default GUI language for users to EN', 'Set default GUI language for users to EN', '12'),
('2017-12-06', 'visualReview', 'bugfix', 'visualReview: fixes for translate5 embedded editor usage and RTL fixes', 'Some fixes were needed in order  to use VisualReview in embedded translate5.', '12');
