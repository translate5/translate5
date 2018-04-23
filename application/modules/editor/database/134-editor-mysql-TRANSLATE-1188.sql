/*
START LICENSE AND COPYRIGHT

 This file is part of translate5
 
 Copyright (c) 2013 - 2017 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

 Contact:  http://www.MittagQI.com/  /  service (ATT) MittagQI.com

 This file may be used under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE version 3
 as published by the Free Software Foundation and appearing in the file agpl3-license.txt 
 included in the packaging of this file.  Please review the following information 
 to ensure the GNU AFFERO GENERAL PUBLIC LICENSE version 3 requirements will be met:
 http://www.gnu.org/licenses/agpl.html
  
 There is a plugin exception available for use with this release of translate5 for
 translate5 plug-ins that are distributed under GNU AFFERO GENERAL PUBLIC LICENSE version 3:
 Please see http://www.translate5.net/plugin-exception.txt or plugin-exception.txt in the root
 folder of translate5.
  
 @copyright  Marc Mittag, MittagQI - Quality Informatics
 @author     MittagQI - Quality Informatics
 @license    GNU AFFERO GENERAL PUBLIC LICENSE version 3 with plugin-execption
			 http://www.gnu.org/licenses/agpl.html http://www.translate5.net/plugin-exception.txt

END LICENSE AND COPYRIGHT
*/


/**
  README:
  	1. drop the taskGuid foreign key in lek_terms
    2. create tearm collection for each task in lek_terms table
    3. insert task to term collection association
    4. update the term collection id in lek_terms after the term collection for this task is created
    3. remove the taskguid column from lek_terms table
    4. remove the tigId column from lek_terms table
 */

ALTER TABLE `LEK_terms` 
DROP FOREIGN KEY `LEK_terms_ibfk_1`;

ALTER TABLE `LEK_terms` 
DROP INDEX `taskGuid`;

ALTER TABLE `LEK_terms` 
DROP INDEX `taskGuid_2`;

ALTER TABLE `LEK_term_collection` 
ADD COLUMN `taskGuid` VARCHAR(38) NULL;

INSERT INTO LEK_term_collection (`name`,`taskGuid`)
SELECT concat("Term Collection for Task:",`task`.`taskName` ,";Task Number:",`task`.`taskNr`,";Task Guid:",`task`.`taskGuid`) as `taskName`,`task`.`taskGuid` FROM `LEK_terms` `term`
INNER JOIN `LEK_task` `task` ON `task`.`taskGuid`=`term`.`taskGuid`
WHERE  `task`.`terminologie` = 1
GROUP BY `term`.`taskGuid`;

INSERT INTO `LEK_term_collection_taskassoc` (`collectionId`,`taskGuid`)
SELECT `id`,`taskGuid` FROM `LEK_term_collection`;

UPDATE `LEK_terms` `t`, `LEK_term_collection` `c` set `t`.`collectionId` = `c`.`id` where `t`.`taskGuid` = `c`.`taskGuid`;

ALTER TABLE `LEK_term_collection` DROP COLUMN `taskGuid`;

ALTER TABLE `LEK_terms` DROP COLUMN `taskGuid`;
ALTER TABLE `LEK_terms` DROP COLUMN `tigId`, DROP INDEX `tigId` ;


