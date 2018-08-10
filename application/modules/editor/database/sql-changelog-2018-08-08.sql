
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

INSERT INTO `LEK_change_log` (`dateOfChange`, `jiraNumber`, `type`, `title`, `description`, `userGroup`) VALUES ('2018-08-08', 'TRANSLATE-1352', 'feature', 'Include PM changes in changes-mail and changes.xliff (xliff 2.1)', 'It can be configured if segment changes of PMs should be listed in the changes email and changes xliff file. By default its active.', '14'),
('2018-08-08', 'TRANSLATE-884', 'feature', 'Implement generic match analysis and pre-translation (on the example of OpenTM2)', 'Implement generic match analysis and pre-translation on the example of OpenTM2', '14'),
('2018-08-08', 'TRANSLATE-392', 'feature', 'Systemwide (non-persistent) memory', 'Implemented a memory cache for internal purposes', '8'),
('2018-08-08', 'VISUAL-31', 'change', 'VisualReview: improve segmentation', 'VisualReview: improve segmentation in performance and matchrate', '8'),
('2018-08-08', 'TRANSLATE-1360', 'change', 'Make PM dropdown in task properties searchable', 'The dropdown to change the PM of a task in task properties is now searchable', '12'),
('2018-08-08', 'TRANSLATE-1383', 'bugfix', 'Additional workflow roles associated to a task prohibit a correct workflow switching', 'Some client specific workflows provides additional roles to be associated to a task. This additional roles prohibit a correct workflow step switching.', '12'),
('2018-08-08', 'TRANSLATE-1161', 'bugfix', 'Task locking clean up is only done on listing the task overview', 'Some internal garbage collection was unified: locked tasks, old sessions are cleaned in a general way. On heavy traffic instances this can be changed to cronjob based garbage collection now.', '8'),
('2018-08-08', 'TRANSLATE-1067', 'bugfix', 'API Usage: \'Zend_Exception\' with message \'Indirect modification of overloaded property Zend_View::$rows has no effect', 'On API usage and providing invalid roles on task user association creation this error was triggered.', '8'),
('2018-08-08', 'TRANSLATE-1385', 'bugfix', 'PreFillSession Resource Plugin must be removed', 'PreFillSession Resource Plugin must be removed', '8'),
('2018-08-08', 'TRANSLATE-1340', 'bugfix', 'IP based SessionRestriction is to restrictive for users having multiple IPs', 'Working areas with changing IPs was not possible due the SessionRestriction.', '8'),
('2018-08-08', 'TRANSLATE-1359', 'bugfix', 'PM to task association dropdown in task properties list does not contain all PMs', 'PM to task association dropdown in task properties list does not contain all PMs', '12');