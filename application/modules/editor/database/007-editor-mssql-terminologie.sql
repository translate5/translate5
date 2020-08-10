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

USE [icorrect]

CREATE TABLE LEK_terms
(
id int PRIMARY KEY IDENTITY,
taskGuid varchar(38) NOT NULL,
term nvarchar (255) NOT NULL,
termId varchar (255) NOT NULL,
status varchar (255) NOT NULL,
definition varchar (255) NOT NULL,
groupId varchar (255) NOT NULL,
language varchar (32) NOT NULL,
INDEX (groupId),
INDEX (taskGuid)
)

CREATE TABLE LEK_segments2terms
(
id int PRIMARY KEY IDENTITY,
segmentId int NOT NULL,
lang varchar (6) NOT NULL,
used bit NOT NULL default 'false',
termId int NOT NULL,
transFound bit NOT NULL default 'false',
FOREIGN KEY (termId) REFERENCES LEK_terms (id) ON DELETE CASCADE,
FOREIGN KEY (segmentId) REFERENCES LEK_segments (id) ON DELETE CASCADE
)

CREATE TABLE `LEK_terminstances` (
id int PRIMARY KEY IDENTITY,
segmentId int NOT NULL,
term nvarchar (255) NOT NULL,
projectTerminstanceId int  NOT NULL,
termId int NOT NULL,
FOREIGN KEY (termId) REFERENCES LEK_terms (id) ON DELETE CASCADE,
FOREIGN KEY (segmentId) REFERENCES LEK_segments (id) ON DELETE CASCADE
)

DROP TABLE `LEK_segmentterms`;