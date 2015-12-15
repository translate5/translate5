-- /*
-- START LICENSE AND COPYRIGHT
-- 
--  This file is part of translate5
--  
--  Copyright (c) 2013 - 2015 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.
-- 
--  Contact:  http://www.MittagQI.com/  /  service (ATT) MittagQI.com
-- 
--  This file may be used under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE version 3
--  as published by the Free Software Foundation and appearing in the file agpl3-license.txt 
--  included in the packaging of this file.  Please review the following information 
--  to ensure the GNU AFFERO GENERAL PUBLIC LICENSE version 3.0 requirements will be met:
--  http://www.gnu.org/licenses/agpl.html
-- 
--  There is a plugin exception available for use with this release of translate5 for
--  open source applications that are distributed under a license other than AGPL:
--  Please see Open Source License Exception for Development of Plugins for translate5
--  http://www.translate5.net/plugin-exception.txt or as plugin-exception.txt in the root
--  folder of translate5.
--   
--  @copyright  Marc Mittag, MittagQI - Quality Informatics
--  @author     MittagQI - Quality Informatics
--  @license    GNU AFFERO GENERAL PUBLIC LICENSE version 3 with plugin-execptions
-- 			 http://www.gnu.org/licenses/agpl.html http://www.translate5.net/plugin-exception.txt
-- 
-- END LICENSE AND COPYRIGHT
-- */

CREATE TABLE `LEK_segment_user_assoc` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `segmentId` int(11) NOT NULL,
  `userGuid` varchar(38) NOT NULL,
  `taskGuid` varchar(38) NOT NULL,
  `isWatched` int(1) NOT NULL DEFAULT '1',
  `created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `modified` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`),
  UNIQUE KEY `segment_user` (`segmentId`,`userGuid`),
  KEY `userGuid` (`userGuid`),
  KEY `segmentId` (`segmentId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

ALTER TABLE `LEK_segment_user_assoc`
  ADD CONSTRAINT `Zf_users_FK` FOREIGN KEY (`userGuid`) REFERENCES `Zf_users` (`userGuid`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `LEK_segments_FK` FOREIGN KEY (`segmentId`) REFERENCES `LEK_segments` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

INSERT INTO `Zf_acl_rules` VALUES(NULL, 'editor', 'editor', 'editor_segmentuserassoc', 'all');