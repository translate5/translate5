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

-- create default customer if none exists
INSERT INTO `LEK_customer` (`name`,`number`,`searchCharacterLimit`)
SELECT * FROM (SELECT 'defaultcustomer','default for legacy data',100000) AS tmp
WHERE NOT EXISTS (
    SELECT * FROM `LEK_customer` WHERE `LEK_customer`.`name` = 'defaultcustomer'
) LIMIT 1;

-- add column for task-customer-assoc (each task can be assigned to one customer only => INT(11) as for customer-id)
ALTER TABLE `LEK_task`
ADD COLUMN `customerId` INT(11) NULL COMMENT 'Client (= id from table LEK_customer)';

-- set defaultcustomer as default customer for every task (this is the initial step, so we can set it for all)
UPDATE `LEK_task` AS task, `LEK_customer` AS customer 
SET task.customerId = customer.id
WHERE customer.name = 'defaultcustomer';

-- set foreign key for task-customer-assoc
ALTER TABLE `LEK_task`
ADD INDEX `fk_LEK_task_1_idx` (`customerId` ASC),
ADD CONSTRAINT `fk_LEK_task_1`
  FOREIGN KEY (`customerId`)
  REFERENCES `LEK_customer` (`id`)
  ON DELETE RESTRICT;
  
-- assign all language resources which are not belonging to any customer to the default customer
-- step 1: get customerId
SELECT @cust_id := id
FROM LEK_customer
WHERE name = 'defaultcustomer';
-- step 2: insert language resources that are not assigned so far
INSERT INTO LEK_languageresources_customerassoc (languageResourceId, customerId, useAsDefault)
SELECT res.id, @cust_id, 0
FROM LEK_languageresources res
LEFT JOIN LEK_languageresources_customerassoc assoc ON res.id = assoc.languageResourceId
WHERE assoc.id IS NULL;

