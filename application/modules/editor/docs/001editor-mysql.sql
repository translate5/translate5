--  /*
--  START LICENSE AND COPYRIGHT
--  
--  This file is part of Translate5 Editor PHP Serverside and build on Zend Framework
--  
--  Copyright (c) 2013 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.
-- 
--  Contact:  http://www.MittagQI.com/  /  service (ÄTT) MittagQI.com
-- 
--  This file may be used under the terms of the GNU General Public License version 3.0
--  as published by the Free Software Foundation and appearing in the file gpl3-license.txt 
--  included in the packaging of this file.  Please review the following information 
--  to ensure the GNU General Public License version 3.0 requirements will be met:
--  http://www.gnu.org/copyleft/gpl.html.
-- 
--  For this file you are allowed to make use of the same FLOSS exceptions to the GNU 
--  General Public License version 3.0 as specified by Sencha for Ext Js. 
--  Please be aware, that Marc Mittag / MittagQI take no warranty  for any legal issue, 
--  that may arise, if you use these FLOSS exceptions and recommend  to stick to GPL 3. 
--  For further information regarding this topic please see the attached license.txt
--  of this software package.
--  
--  MittagQI would be open to release translate5 under EPL or LGPL also, if this could be
--  brought in accordance with the ExtJs license scheme. You are welcome to support us
--  with legal support, if you are interested in this.
--  
--  
--  @copyright  Marc Mittag, MittagQI - Quality Informatics
--  @author     MittagQI - Quality Informatics
--  @license    GNU General Public License version 3.0 http://www.gnu.org/copyleft/gpl.html
--              with FLOSS exceptions (see floss-exception.txt and ux-exception.txt at the root level)
--  
--  END LICENSE AND COPYRIGHT 
--  */
-- 

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

