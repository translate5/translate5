-- /*
-- START LICENSE AND COPYRIGHT
--
--  This file is part of translate5
--
--  Copyright (c) 2013 - '.(date('Y')).' Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.
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

DROP TABLE `terms_resp_person`;

CREATE TABLE `terms_ref_object` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `collectionId` int(11) NOT NULL,
  `listType` varchar(64) NOT NULL,
  `key` varchar(64) NOT NULL,
  `data` varchar(1024) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY (`collectionId`, `listType`, `key`),
  CONSTRAINT FOREIGN KEY (`collectionId`) REFERENCES `LEK_languageresources` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


ALTER TABLE `terms_attributes` 
ADD COLUMN `isDescripGrp` TINYINT(1) NULL DEFAULT 0 AFTER `attrLang`;

ALTER TABLE `terms_transacgrp` 
CHANGE `ifDescripgrp` `isDescripGrp` TINYINT(1) DEFAULT 0 NULL;
