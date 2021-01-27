-- /*
-- START LICENSE AND COPYRIGHT
--
--  This file is part of translate5
--
--  Copyright (c) 2013 - 2020 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.
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

CREATE TABLE `LEK_languageresources_mt_usage_log_sum` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `langageResourceId` int(11) NOT NULL,
  `langageResourceName` varchar(1024) COLLATE utf8mb4_unicode_ci NOT NULL,
  `langageResourceType` varchar(45) COLLATE utf8mb4_unicode_ci NOT NULL,
  `sourceLang` int(11) NOT NULL,
  `targetLang` int(11) NOT NULL,
  `customerId` int(11) NOT NULL,
  `yearAndMonth` varchar(45) COLLATE utf8mb4_unicode_ci NOT NULL,
  `totalCharacters` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `monthlySummaryKey` (`langageResourceId`,`sourceLang`,`targetLang`,`customerId`,`yearAndMonth`),
  KEY `fk_LEK_languageresources_mt_usage_log_sum_1_idx` (`customerId`),
  CONSTRAINT `fk_LEK_languageresources_mt_usage_log_sum_customer` FOREIGN KEY (`customerId`) REFERENCES `LEK_customer` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

ALTER TABLE `LEK_languageresources_mt_usage_log` 
ADD INDEX `fk_LEK_languageresources_mt_usage_log_1_idx` (`languageResourceId` ASC);
ALTER TABLE `LEK_languageresources_mt_usage_log` 
ADD CONSTRAINT `fk_LEK_languageresources_mt_usage_log_1`
  FOREIGN KEY (`languageResourceId`)
  REFERENCES `LEK_languageresources` (`id`)
  ON DELETE CASCADE
  ON UPDATE CASCADE;
  
  
CREATE TABLE `LEK_documents_usage_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `taskType` varchar(45) COLLATE utf8mb4_unicode_ci NOT NULL,
  `sourceLang` int(11) NOT NULL,
  `targetLang` int(11) NOT NULL,
  `customerId` int(11) NOT NULL,
  `yearAndMonth` varchar(45) COLLATE utf8mb4_unicode_ci NOT NULL,
  `taskCount` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `document_type_count` (`taskType`,`customerId`,`yearAndMonth`),
  KEY `fk_customer_idx` (`customerId`),
  CONSTRAINT `fk_customer` FOREIGN KEY (`customerId`) REFERENCES `LEK_customer` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;