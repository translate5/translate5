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
--              http://www.gnu.org/licenses/agpl.html http://www.translate5.net/plugin-exception.txt
--
-- END LICENSE AND COPYRIGHT
-- */

CREATE TABLE `task_custom_fields` (
  `id` INT (11) NOT NULL AUTO_INCREMENT,
  # `customerId` INT DEFAULT NULL,
  `label` VARCHAR (255),
  `tooltip` VARCHAR (255),
  `type` ENUM('textfield', 'textarea', 'checkbox', 'combobox') NOT NULL DEFAULT 'textfield',
  `comboboxData` TEXT,
  `regex` VARCHAR (255),
  `mode` ENUM ('optional', 'required', 'readonly') NOT NULL DEFAULT 'optional',
  `placesToShow` SET ('projectWizard', 'projectGrid', 'taskGrid'),
  `position` INT (11) NOT NULL DEFAULT 0,
  # CONSTRAINT FOREIGN KEY (`customerId`) REFERENCES `LEK_customer` (`id`) ON DELETE CASCADE,
  PRIMARY KEY (`id`)
) ENGINE = INNODB CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;


-- Add acl-records
INSERT IGNORE INTO `Zf_acl_rules` (`module`, `role`, `resource`, `right`)
VALUES ('editor', 'pm', 'frontend', 'taskCustomField');

INSERT IGNORE INTO `Zf_acl_rules` (`module`, `role`, `resource`, `right`)
VALUES ('editor', 'pm', 'editor_taskcustomfield', 'all');