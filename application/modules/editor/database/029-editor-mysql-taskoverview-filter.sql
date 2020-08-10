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

alter table `LEK_task` add column `pmName` varchar (512) not null after `pmGuid`;
update `LEK_task` t, `Zf_users` u set t.pmName = concat(u.surName,', ', u.firstName,' (',u.login, ')') where t.pmGuid = u.userGuid;

alter table `LEK_task` add column `userCount` integer(11) not null default 0 after `wordCount`;
update `LEK_task` t, (select count(*) cnt, taskGuid from `LEK_taskUserAssoc` group by taskGuid) a set t.userCount = a.cnt where t.taskGuid = a.taskGuid;

alter table `LEK_task` change `ordered` `orderdate` datetime DEFAULT NULL;
update LEK_task set targetDeliveryDate = date(targetDeliveryDate), realDeliveryDate = date(realDeliveryDate), orderdate = date(orderdate);