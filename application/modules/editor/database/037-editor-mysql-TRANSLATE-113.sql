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
