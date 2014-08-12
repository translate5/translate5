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
ALTER TABLE `LEK_task` ADD COLUMN `entityVersion` int(11) NOT NULL DEFAULT 0 AFTER `id`;

  DELIMITER |
  CREATE TRIGGER LEK_task_versioning BEFORE UPDATE ON LEK_task
      FOR EACH ROW 
        IF OLD.entityVersion = NEW.entityVersion THEN 
          SET NEW.entityVersion = OLD.entityVersion + 1;
        ELSE 
          CALL raise_version_conflict; 
        END IF|

  CREATE TRIGGER LEK_taskUserAssoc_versioning_up BEFORE UPDATE ON LEK_taskUserAssoc
      FOR EACH ROW 
        IF not @`entityVersion` is null THEN
          UPDATE LEK_task SET entityVersion = @`entityVersion` WHERE taskGuid = NEW.taskGuid;
          SET @`entityVersion` := null;
        END IF|

  CREATE TRIGGER LEK_taskUserAssoc_versioning_ins BEFORE INSERT ON LEK_taskUserAssoc
      FOR EACH ROW 
        IF not @`entityVersion` is null THEN
          UPDATE LEK_task SET entityVersion = @`entityVersion` WHERE taskGuid = NEW.taskGuid;
          SET @`entityVersion` := null;
        END IF|

  CREATE TRIGGER LEK_taskUserAssoc_versioning_del BEFORE DELETE ON LEK_taskUserAssoc
      FOR EACH ROW 
        IF not @`entityVersion` is null THEN
          UPDATE LEK_task SET entityVersion = @`entityVersion` WHERE taskGuid = OLD.taskGuid;
          SET @`entityVersion` := null;
        END IF|

  CREATE TRIGGER LEK_workflow_userpref_versioning_up BEFORE UPDATE ON LEK_workflow_userpref
      FOR EACH ROW 
        IF not @`entityVersion` is null THEN
          UPDATE LEK_task SET entityVersion = @`entityVersion` WHERE taskGuid = NEW.taskGuid;
          SET @`entityVersion` := null;
        END IF|

  CREATE TRIGGER LEK_workflow_userpref_versioning_ins BEFORE INSERT ON LEK_workflow_userpref
      FOR EACH ROW 
        IF not @`entityVersion` is null THEN
          UPDATE LEK_task SET entityVersion = @`entityVersion` WHERE taskGuid = NEW.taskGuid;
          SET @`entityVersion` := null;
        END IF|

  CREATE TRIGGER LEK_workflow_userpref_versioning_del BEFORE DELETE ON LEK_workflow_userpref
      FOR EACH ROW 
        IF not @`entityVersion` is null THEN
          UPDATE LEK_task SET entityVersion = @`entityVersion` WHERE taskGuid = OLD.taskGuid;
          SET @`entityVersion` := null;
        END IF|
  DELIMITER ;
