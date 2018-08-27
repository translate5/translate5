
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

INSERT INTO `LEK_change_log` (`dateOfChange`, `jiraNumber`, `type`, `title`, `description`, `userGroup`) VALUES ('2018-08-27', 'VISUAL-50', 'feature', 'VisualReview: Improve initial loading performance by accessing images directly and not via PHP proxy', 'For security reasons all VisualReview content is piped through a PHP proxy for authentication. For huge VisualReview data this can be slow. By setting runtimeOptions.plugins.VisualReview.directPublicAccess = 1 in the config an alternative way by using symlinks is enabled.', '12'),
('2018-08-27', 'VISUAL-49', 'change', 'VisualReview: Extend default editor mode to support visualReview', 'The initial view mode in VisualReview can now be configured, this is either simple or default. Config: runtimeOptions.plugins.VisualReview.startViewMode.', '8'),
('2018-08-27', 'TRANSLATE-1415', 'change', 'Rename startViewMode values in config', 'Rename startViewMode values in config', '8'),
('2018-08-27', 'TRANSLATE-1416', 'bugfix', 'exception \'PDOException\' with message \'SQLSTATE[42S01]: Base table or view already exists: 1050 Table \'siblings\' already exists\'', 'The exception can happen on cron triggered workflow actions.', '8'),
('2018-08-27', 'VISUAL-48', 'bugfix', 'VisualReview: Improve visualReview scroll performance on very large VisualReview Projects', 'VisualReview: Improve visualReview scroll performance on very large VisualReview Projects', '14'),
('2018-08-27', 'TRANSLATE-1413', 'bugfix', 'TermPortal: Import deletes all old Terms, regardless of the originating TermCollection', 'TermPortal: Import deletes all old Terms, regardless of the originating TermCollection', '12'),
('2018-08-27', 'TRANSLATE-1392', 'bugfix', 'Unlock task on logout ', 'This change is needed, since garbage collector is triggered only periodically instead on each task overview request.', '12');