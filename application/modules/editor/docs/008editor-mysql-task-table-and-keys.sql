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

CREATE TABLE `LEK_task` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `taskGuid` varchar(38) NOT NULL,
  `taskName` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `taskGuid` (`taskGuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT INTO LEK_task (taskGuid) SELECT distinct taskGuid from LEK_files;

ALTER TABLE `LEK_terms` ADD FOREIGN KEY (`taskGuid`) REFERENCES `LEK_task` (`taskGuid`) ON DELETE CASCADE;
ALTER TABLE `LEK_files` ADD FOREIGN KEY (`taskGuid`) REFERENCES `LEK_task` (`taskGuid`) ON DELETE CASCADE;
ALTER TABLE `LEK_segments` ADD FOREIGN KEY (`taskGuid`) REFERENCES `LEK_task` (`taskGuid`) ON DELETE CASCADE;
ALTER TABLE `LEK_foldertree` ADD FOREIGN KEY (`taskGuid`) REFERENCES `LEK_task` (`taskGuid`) ON DELETE CASCADE;

-- falls referenzen nicht erfüllt sind, kann folgendermaßen aufgeräumt werden (am Beispiel LEK_files): 
-- delete from LEK_files where taskGuid not in (select taskGuid from LEK_task);
