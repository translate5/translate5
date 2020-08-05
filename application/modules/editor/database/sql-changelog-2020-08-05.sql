
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

INSERT INTO `LEK_change_log` (`dateOfChange`, `jiraNumber`, `type`, `title`, `description`, `userGroup`) VALUES ('2020-08-05', 'TRANSLATE-2069', 'feature', 'Show task-id and segment-id in URL and enable to access a task via URL (sponsored by Supertext)', 'A user is now able to send an URL that points to a certain segment of an opened task to another user and he will be able to automatically open the segment and scroll to the task alone via entering the URL (provided he has access rights to the task). This works also, if the user still has to log in and also if login works via OpenID Connect.', '14'),
('2020-08-05', 'TRANSLATE-2150', 'change', 'Disable default enabled workflow action finishOverduedTaskUserAssoc', 'Disable default enabled workflow action finishOverduedTaskUserAssoc', '8'),
('2020-08-05', 'TRANSLATE-2159', 'change', 'Update Third-Party-Library Horde Text Diff', 'Include the up2date version of the used diff library', '8'),
('2020-08-05', 'TRANSLATE-2141', 'bugfix', 'Again further major improvments of the layout for the „What you see is what you get“ feature compared to version 5.0.6', 'Again further major improvments of the layout for the „What you see is what you get“ feature compared to version 5.0.6', '14'),
('2020-08-05', 'TRANSLATE-2148', 'bugfix', 'Load module plugins only', 'A fix in the architecture of translate5', '8'),
('2020-08-05', 'TRANSLATE-2153', 'bugfix', 'In some cases translate5 deletes spaces between segments', 'This refers to the visual layout representation of segments (not the actual translation)', '14'),
('2020-08-05', 'TRANSLATE-2155', 'bugfix', 'Visual HTML fails on import for multi-target-lang project', 'Creating a mulit-lang project failed, when fetching the layout via URL', '12'),
('2020-08-05', 'TRANSLATE-2158', 'bugfix', 'Reflect special whitespace characters in the layout', 'Entering linebreak, non-breaking-space and tabs in the segment effects now „What you see is what you get“ the layout', '14');