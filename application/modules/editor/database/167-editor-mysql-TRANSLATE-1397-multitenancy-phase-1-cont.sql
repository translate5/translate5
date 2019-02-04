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
  
-- new insight for multitnancy-concept: customers CAN be empty for users!
-- (eg managers should be able to see everything without being assigned to every customer)

-- I. change customer-column for users to NOT NULL
ALTER TABLE `Zf_users`
  MODIFY COLUMN `customers` VARCHAR(255) NOT NULL;

-- II. don't set defaultcustomer for users on migration (= revert from 166-editor-mysql-TRANSLATE-1397-multitenancy-phase-1-cont.sql)
SELECT @cust_id := id
  FROM LEK_customer
  WHERE name = 'defaultcustomer';
UPDATE `Zf_users`
  SET customers = ''
  WHERE customers = CONCAT(',', @cust_id, ',');