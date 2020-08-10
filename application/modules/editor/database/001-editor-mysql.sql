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

CREATE TABLE IF NOT EXISTS `LEK_files`
(
id int(11) NOT NULL AUTO_INCREMENT,
taskGuid varchar(38) NOT NULL,
fileName varchar(255),
sourceLang varchar(11) NOT NULL,
targetLang varchar(11) NOT NULL,
fileOrder int NOT NULL,
PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


CREATE TABLE IF NOT EXISTS `LEK_segments`
(
id int(11) NOT NULL AUTO_INCREMENT,
fileId int NOT NULL,
source longtext NOT NULL,
sourceToSort varchar(210) NOT NULL,
target longtext,
targetToSort varchar(210),
edited longtext,
editedToSort varchar(210),
userGuid varchar(38) NOT NULL,
userName varchar(255) NOT NULL DEFAULT '',
taskGuid varchar(38) NOT NULL,
`timestamp` timestamp  NOT NULL,
editable boolean NOT NULL DEFAULT '1',
pretrans boolean NOT NULL DEFAULT '0',
matchRate INT NOT NULL DEFAULT '0',
qmId varchar(255),
stateId int,
autoStateId INT NOT NULL DEFAULT '0',
fileOrder int(11) NULL,
PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `LEK_segment_history`
(
id int(11) NOT NULL AUTO_INCREMENT,
segmentId int(11) NOT NULL,
`edited` longtext,
userGuid varchar(38) NOT NULL,
userName varchar(255) NOT NULL DEFAULT '',
`timestamp` timestamp  NOT NULL,
editable boolean NOT NULL,
pretrans boolean NOT NULL,
qmId varchar(255),
stateId int,
autoStateId INT NOT NULL DEFAULT '0',
autoStatusId int,
PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `LEK_skeletonfiles`
(
id int(11) NOT NULL AUTO_INCREMENT,
fileId int NOT NULL,
fileName varchar(255) NOT NULL,
file longtext NOT NULL,
PRIMARY KEY (`id`),
UNIQUE KEY `fileId` (`fileId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `LEK_internaltags`
(
id int(11) NOT NULL AUTO_INCREMENT,
tagsPerSegmentId int NOT NULL,
tagType int NOT NULL,
segmentId int NOT NULL,
PRIMARY KEY (`id`),
UNIQUE KEY (`tagsPerSegmentId`, `tagType`, `segmentId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `LEK_foldertree`
(
id int(11) NOT NULL AUTO_INCREMENT,
tree text NOT NULL,
taskGuid varchar(38) NOT NULL,
PRIMARY KEY (`id`),
UNIQUE KEY `taskGuid` (`taskGuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `LEK_segmentmetadata`
(
id int(11) NOT NULL AUTO_INCREMENT,
segmentId int NOT NULL,
additionalInfo text NOT NULL,
orderNumber text NOT NULL,
taskId int NOT NULL,
vendor text NOT NULL,
PRIMARY KEY (`id`),
UNIQUE KEY `segmentId` (`segmentId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


CREATE TABLE IF NOT EXISTS `LEK_segmentterms`
(
id int(11) NOT NULL AUTO_INCREMENT,
segmentId int NOT NULL,
term text NOT NULL,
termType int NOT NULL,
termDescription text NOT NULL,
PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

