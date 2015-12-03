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


CREATE TABLE `LEK_browser_log` (
  `id` integer (11) DEFAULT NULL AUTO_INCREMENT,
  `datetime` datetime NOT NULL DEFAULT NOW(),
  `login` varchar(255) DEFAULT NULL COMMENT 'user login',
  `userGuid` varchar(38) DEFAULT NULL COMMENT 'userguid',
  `appVersion` varchar(255) DEFAULT NULL COMMENT 'used browser version (navigator.appVersion)',
  `userAgent` varchar(255) DEFAULT NULL COMMENT 'used userAgent (navigator.userAgent)',
  `browserName` varchar(255) DEFAULT NULL COMMENT 'used browser (navigator.browserName)',
  `maxWidth` integer (11) DEFAULT NULL COMMENT 'screen width',
  `maxHeight` integer (11) DEFAULT NULL COMMENT 'screen height',
  `usedWidth` integer (11) DEFAULT NULL COMMENT 'used window width',
  `usedHeight` integer (11) DEFAULT NULL COMMENT 'used window height',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;