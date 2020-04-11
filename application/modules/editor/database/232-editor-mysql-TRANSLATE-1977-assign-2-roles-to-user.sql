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

ALTER TABLE `LEK_workflow_userpref` 
DROP FOREIGN KEY `LEK_workflow_userpref_ibfk_2`,
DROP FOREIGN KEY `LEK_workflow_userpref_ibfk_1`;
ALTER TABLE `LEK_workflow_userpref` 
DROP INDEX `taskGuid` ;

ALTER TABLE `LEK_taskUserAssoc` 
DROP FOREIGN KEY `LEK_taskUserAssoc_ibfk_3`,
DROP FOREIGN KEY `LEK_taskUserAssoc_ibfk_2`;
ALTER TABLE `LEK_taskUserAssoc` 
DROP INDEX `userGuid` ,
DROP INDEX `taskGuid` ;

ALTER TABLE `LEK_taskUserAssoc` 
ADD UNIQUE INDEX `taskGuid` (`taskGuid` ASC, `userGuid` ASC, `role` ASC),
ADD INDEX `userGuid` (`userGuid` ASC);
ALTER TABLE `LEK_taskUserAssoc` 
ADD CONSTRAINT `fk_LEK_taskUserAssoc_1`
  FOREIGN KEY (`userGuid`)
  REFERENCES `Zf_users` (`userGuid`)
  ON DELETE CASCADE
  ON UPDATE RESTRICT,
ADD CONSTRAINT `fk_LEK_taskUserAssoc_2`
  FOREIGN KEY (`taskGuid`)
  REFERENCES `LEK_task` (`taskGuid`)
  ON DELETE CASCADE
  ON UPDATE RESTRICT;
  
  ALTER TABLE `LEK_workflow_userpref` 
ADD COLUMN `taskUserAssocId` INT(11) NULL AFTER `notEditContent`,
ADD INDEX `fk_LEK_workflow_userpref_1_idx` (`taskGuid` ASC),
ADD INDEX `fk_LEK_workflow_userpref_2_idx` (`taskUserAssocId` ASC);
ALTER TABLE `LEK_workflow_userpref` 
ADD CONSTRAINT `fk_LEK_workflow_userpref_1`
  FOREIGN KEY (`taskGuid`)
  REFERENCES `LEK_task` (`taskGuid`)
  ON DELETE CASCADE
  ON UPDATE RESTRICT,
ADD CONSTRAINT `fk_LEK_workflow_userpref_2`
  FOREIGN KEY (`taskUserAssocId`)
  REFERENCES `LEK_taskUserAssoc` (`id`)
  ON DELETE CASCADE
  ON UPDATE RESTRICT;

UPDATE `LEK_workflow_userpref` `dest`,
(
   SELECT `id`,`userGuid`,`taskGuid` FROM `LEK_taskUserAssoc` 
) `src` 
SET
  `dest`.`taskUserAssocId` = `src`.`id`
WHERE `dest`.`taskGuid` = `src`.`taskGuid`
AND `dest`.`userGuid` = `src`.`userGuid`;
  
  