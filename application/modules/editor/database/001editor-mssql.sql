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

USE [icorrect]

CREATE TABLE LEK_files
(
id int PRIMARY KEY IDENTITY,
taskGuid varchar(38) NOT NULL,
fileName nvarchar(255),
sourceLang nvarchar(50) NOT NULL,
targetLang nvarchar(50) NOT NULL,
fileOrder int NOT NULL
)


CREATE TABLE LEK_segments
(
id int PRIMARY KEY IDENTITY,
fileId int NOT NULL DEFAULT '0',
source ntext NOT NULL,
sourceToSort nvarchar(210) NOT NULL,
target ntext,
targetToSort nvarchar(210),
edited ntext,
editedToSort nvarchar(210),
userGuid varchar(38) NOT NULL,
userName nvarchar(255) NOT NULL DEFAULT '',
taskGuid varchar(38) NOT NULL,
[timestamp] [datetime] NOT NULL DEFAULT (getdate()),
editable bit NOT NULL DEFAULT 'true',
pretrans bit NOT NULL DEFAULT 'false',
matchRate int NOT NULL DEFAULT '0',
qmId varchar(255),
stateId int,
autoStateId INT NOT NULL DEFAULT '0',
fileOrder int NULL
)



CREATE TABLE LEK_segment_history
(
id int PRIMARY KEY IDENTITY,
segmentId int NOT NULL,
edited ntext,
userGuid varchar(38) NOT NULL,
userName nvarchar(255) NOT NULL DEFAULT '',
[timestamp] [datetime] NOT NULL DEFAULT (getdate()),
editable bit NOT NULL,
pretrans bit NOT NULL,
qmId varchar(255),
stateId int,
autoStateId int NOT NULL DEFAULT '0'
)


CREATE TABLE LEK_guistrings
(
id int PRIMARY KEY IDENTITY,
GUID varchar(38) NOT NULL,
term nvarchar(255) NOT NULL,
context varchar(255) NOT NULL,
value ntext NOT NULL,
guiLang int NOT NULL
UNIQUE (GUID),
UNIQUE (term,context,guiLang)
)


CREATE TABLE languages(
id int PRIMARY KEY IDENTITY,
[langName] [varchar](255) NOT NULL,
[unixLocale] [varchar](5) NOT NULL,
[LCID] int NOT NULL,
[ISO639] [varchar](3) NULL,
[Trados] [varchar](5) NULL
UNIQUE (langName),
UNIQUE (unixLocale),
UNIQUE (LCID)
) 

CREATE TABLE LEK_skeletonfiles
(
id int PRIMARY KEY IDENTITY,
fileId int NOT NULL,
fileName nvarchar(255) NOT NULL,
[file] [ntext] NOT NULL
UNIQUE (fileId)
)

CREATE TABLE LEK_internaltags
(
id int PRIMARY KEY IDENTITY,
tagsPerSegmentId int NOT NULL,
tagType int NOT NULL,
segmentId int NOT NULL,
UNIQUE (tagsPerSegmentId, tagType, segmentId)
)

CREATE TABLE LEK_foldertree
(
id int PRIMARY KEY IDENTITY,
tree text NOT NULL,
taskGuid varchar(38) NOT NULL,
UNIQUE (taskGuid)
)

CREATE TABLE LEK_segmentmetadata
(
id int PRIMARY KEY IDENTITY,
segmentId int NOT NULL,
additionalInfo text NOT NULL,
orderNumber text NOT NULL,
taskId int NOT NULL,
vendor text NOT NULL,
UNIQUE (segmentId)
)


CREATE TABLE LEK_segmentterms
(
id int PRIMARY KEY IDENTITY,
segmentId int NOT NULL,
term text NOT NULL,
termType int NOT NULL,
termDescription text NOT NULL
)

CREATE TABLE session (
  id int PRIMARY KEY IDENTITY,
  session_id varchar(38) NOT NULL,
  name varchar(255) NOT NULL DEFAULT '',
  modified int DEFAULT NULL,
  lifetime int DEFAULT NULL,
  session_data text,
  UNIQUE (session_id,name)
)


GO
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
CREATE TRIGGER [dbo].[trg_updateTimestamp2] ON [dbo].[LEK_segment_history]
FOR UPDATE
AS
if not update(timestamp)
UPDATE LEK_segment_history
SET timestamp = GETDATE()
FROM LEK_segment_history
INNER JOIN Inserted
ON LEK_segment_history.id = Inserted.id

GO
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
CREATE TRIGGER [dbo].[trg_updateTimestamp] ON [dbo].[LEK_segments]
FOR UPDATE
AS
if not update(timestamp)
UPDATE LEK_segments
SET timestamp = GETDATE()
FROM LEK_segments
INNER JOIN Inserted
ON LEK_segments.id = Inserted.id
