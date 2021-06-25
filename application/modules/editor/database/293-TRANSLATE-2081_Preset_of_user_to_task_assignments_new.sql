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

CREATE TABLE `LEK_user_assoc_default` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `customerId` int(11) NOT NULL,
  `sourceLang` int(11) DEFAULT NULL,
  `targetLang` int(11) DEFAULT NULL,
  `userGuid` varchar(38) NOT NULL,
  `workflowStepName` varchar(60) NOT NULL DEFAULT 'reviewing',
  `workflow` varchar(60) NOT NULL DEFAULT 'default',
  `deadlineDate` double(19,2) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_default_user_assoc` (`customerId`,`sourceLang`,`targetLang`,`userGuid`,`workflow`,`workflowStepName`),
  KEY `fk_LEK_user_assoc_default_1_idx` (`customerId`),
  KEY `fk_LEK_user_assoc_default_2_idx` (`sourceLang`),
  KEY `fk_LEK_user_assoc_default_3_idx` (`targetLang`),
  KEY `fk_LEK_user_assoc_default_4_idx` (`userGuid`),
  KEY `fk_LEK_user_assoc_default_5_idx` (`workflow`),
  KEY `fk_LEK_user_assoc_default_6_idx` (`workflowStepName`),
  CONSTRAINT `fk_LEK_user_assoc_default_1` FOREIGN KEY (`customerId`) REFERENCES `LEK_customer` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_LEK_user_assoc_default_2` FOREIGN KEY (`sourceLang`) REFERENCES `LEK_languages` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_LEK_user_assoc_default_3` FOREIGN KEY (`targetLang`) REFERENCES `LEK_languages` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_LEK_user_assoc_default_4` FOREIGN KEY (`userGuid`) REFERENCES `Zf_users` (`userGuid`) ON DELETE CASCADE ON UPDATE CASCADE
);

INSERT INTO `Zf_acl_rules` (`module`, `role`, `resource`, `right`) VALUES ('editor', 'pm', 'editor_userassocdefault', 'all');

DELETE FROM `LEK_workflow_action` WHERE `action`='autoAssociateEditorUsers';

ALTER TABLE `Zf_users`
DROP COLUMN `targetLanguage`,
DROP COLUMN `sourceLanguage`;
