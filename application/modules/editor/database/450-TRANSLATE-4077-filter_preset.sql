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

CREATE TABLE `LEK_user_filter_preset` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `userId` int(11) DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `panel` enum('projectGrid','adminTaskGrid','tmOverviewPanel') DEFAULT 'projectGrid',
  `state` text DEFAULT '{}',
  PRIMARY KEY (`id`),
  UNIQUE KEY `userId,title,panel` (`userId`,`title`,`panel`),
  CONSTRAINT `ibfk_filter_preset_userId` FOREIGN KEY (`userId`) REFERENCES `Zf_users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO `Zf_acl_rules` (`module`, `role`, `resource`, `right`) VALUES
('editor','admin','editor_userfilterpreset','all'),
('editor','editor','editor_userfilterpreset','all'),
('editor','pm','editor_userfilterpreset','all'),
('editor','pmlight','editor_userfilterpreset','all'),
('editor','clientpm','editor_userfilterpreset','all');