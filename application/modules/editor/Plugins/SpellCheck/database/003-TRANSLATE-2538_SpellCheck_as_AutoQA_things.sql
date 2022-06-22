-- /*
-- START LICENSE AND COPYRIGHT
--
--  This file is part of translate5
--
--  Copyright (c) 2013 - 2020 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.
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

UPDATE `Zf_configuration` 
SET 
  `name` = "runtimeOptions.plugins.SpellCheck.languagetool.url.gui",
  `guiName` = CONCAT(`guiName`, " for GUI")
WHERE `name` = "runtimeOptions.plugins.SpellCheck.languagetool.api.baseurl";

INSERT INTO `Zf_configuration` (`name`, `confirmed`, `module`, `category`, `value`, `default`, `defaults`, `type`, `typeClass`, `description`, `level`, `guiName`, `guiGroup`, `comment`) values
('runtimeOptions.plugins.SpellCheck.languagetool.url.import' ,'1','editor','plugins','[\"http://localhost:8081/v2\", \"http://localhost:8082/v2\"]','[\"http://localhost:8081/v2\"]','','list',NULL,'Refers to import processes. List one or multiple URLs, where LanguageTool-instances can be reached for segment target text spell checking. Translate5 does a load balancing, if more than one is configured.','2','Spell-, grammar and style check service URLs for import','Editor: QA',''),
('runtimeOptions.plugins.SpellCheck.languagetool.url.default','1','editor','plugins','[\"http://localhost:8081/v2\"]','[\"http://localhost:8081/v2\"]','','list',NULL,'List of available LanguageTool-URLs. At least one available URL must be defined. Example: [\"http://localhost:8081/v2\"]','1','Spell-, grammar and style check service default URL','Editor: QA','deprecated');

INSERT INTO `Zf_configuration` (`name`, `confirmed`, `module`, `category`, `value`, `default`, `defaults`, `type`, `typeClass`, `description`, `level`, `guiName`, `guiGroup`, `comment`) values
('runtimeOptions.worker.editor_Plugins_SpellCheck_Worker_Import.maxParallelWorkers','1','editor','worker','2','1','','integer',NULL,'Max parallel running workers of the spellCheck import worker','1',NULL,NULL,NULL);

INSERT INTO `Zf_configuration` (`name`, `confirmed`, `module`, `category`, `value`, `default`, `defaults`, `type`, `typeClass`, `description`, `level`, `guiName`, `guiGroup`, `comment`) values
('runtimeOptions.autoQA.enableSegmentSpellCheck','1','editor','system','1','1','','boolean',NULL,'If activated (default), AutoQA covers checking the segments against spell-, grammar- and style-checks, provided by LanguageTool','8','Enables segment spell checks','Editor: QA','');

INSERT INTO `Zf_worker_dependencies` (`worker`, `dependency`) values('editor_Plugins_SpellCheck_Worker_Import'        ,'editor_Segment_Quality_ImportWorker');
INSERT INTO `Zf_worker_dependencies` (`worker`, `dependency`) values('editor_Plugins_SpellCheck_Worker_Import'        ,'editor_Segment_Quality_OperationWorker');
INSERT INTO `Zf_worker_dependencies` (`worker`, `dependency`) values('editor_Segment_Quality_OperationFinishingWorker','editor_Plugins_SpellCheck_Worker_Import');
INSERT INTO `Zf_worker_dependencies` (`worker`, `dependency`) values('editor_Segment_Quality_ImportFinishingWorker'   ,'editor_Plugins_SpellCheck_Worker_Import');

INSERT INTO `Zf_configuration` (`name`, `confirmed`, `module`, `category`, `value`, `default`, `defaults`, `type`, `typeClass`, `description`, `level`, `guiName`, `guiGroup`, `comment`) values
('runtimeOptions.plugins.SpellCheck.languagetool.maxSegmentCharacterCount' ,'1','editor','plugins','20000','20000','','integer',NULL,'Maximum number of characters per request to LanguageTool server','1','','','');
