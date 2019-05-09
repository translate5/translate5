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

INSERT INTO `Zf_acl_rules` (`module`, `role`, `resource`, `right`) VALUES
('editor', 'pm', 'editor_term', 'all');

CREATE TABLE `LEK_term_proposal` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `term` varchar(19000) NOT NULL DEFAULT '' COMMENT 'the proposed term',
  `collectionId` int(11) NOT NULL COMMENT 'links to the collection',
  `termId` int(11) DEFAULT NULL COMMENT 'links to the term',
  `created` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  CONSTRAINT FOREIGN KEY (`termId`) REFERENCES `LEK_terms` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT FOREIGN KEY (`collectionId`) REFERENCES `LEK_languageresources` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
);

ALTER TABLE `LEK_terms` 
ADD COLUMN `processStatus` VARCHAR(128) DEFAULT 'finalized' AFTER status;
