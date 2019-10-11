
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

INSERT INTO `LEK_change_log` (`dateOfChange`, `jiraNumber`, `type`, `title`, `description`, `userGroup`) VALUES ('2019-10-07', 'TRANSLATE-1671', 'feature', '(Un)lock 100%-Matches in task properties', '100%-Matches can now be locked and unlocked in the task properties by a PM at any time in the workflow', '12'),
('2019-10-07', 'TRANSLATE-1803', 'feature', 'New options for automatic term proposal deletion on TBX import', 'Via GUI and URL-triggered import term proposals can now be deleted completely independent of term deletion (meaning deletion of terms or proposals, that existed before the import in translate5)', '12'),
('2019-10-07', 'TRANSLATE-1816', 'feature', 'Create a search & replace button', 'A new button for search&replace has been introduced to make it more easy for users to find the feature', '14'),
('2019-10-07', 'TRANSLATE-1817', 'feature', 'Get rid of head panel in editor', 'The head panel of translate5 editor has been removed to give more space for the actual work by default', '14'),
('2019-10-07', 'TRANSLATE-1551', 'bugfix', 'Readonly task is editable when using VisualReview', '', '14'),
('2019-10-07', 'TRANSLATE-1761', 'bugfix', 'Clean up „tbx-for-filesystem-import“ directory', 'Old non-needed TBX files left from old imports are deleted', '8'),
('2019-10-07', 'TRANSLATE-1790', 'bugfix', 'In the general mail template the portal link points to wrong url', 'This has been the case, if translate5 is configured to run on a certain sub-domain for a certain customer', '12');