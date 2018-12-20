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

/* remove the invalid taskGuid and language resoruces form the table */
DELETE FROM LEK_term_collection_taskassoc WHERE taskGuid NOT IN (select taskGuid from LEK_task);
DELETE FROM LEK_term_collection_taskassoc WHERE collectionId NOT IN (select id from LEK_languageresources);

/* migrate from LEK_term_collection_taskassoc to LEK_languageresources_taskassoc */
INSERT INTO `LEK_languageresources_taskassoc` (`languageResourceId`,`taskGuid`,`segmentsUpdateable`)
SELECT  `tcta`.`collectionId`,`tcta`.`taskGuid`,0
FROM `LEK_term_collection_taskassoc` `tcta`
LEFT JOIN `LEK_languageresources_taskassoc` `rs` on `rs`.`taskGuid`=`tcta`.`taskGuid` AND `rs`.`languageResourceId`=`tcta`.`collectionId`
WHERE `rs`.`taskGuid` IS NULL;

DROP TABLE `LEK_term_collection_taskassoc`;
