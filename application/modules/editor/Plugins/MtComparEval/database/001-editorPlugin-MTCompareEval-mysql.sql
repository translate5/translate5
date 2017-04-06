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
--  translate5 plug-ins that are distributed under GNU AFFERO GENERAL PUBLIC LICENSE version 3:
--  Please see http://www.translate5.net/plugin-exception.txt or plugin-exception.txt in the root
--  folder of translate5.
--   
--  @copyright  Marc Mittag, MittagQI - Quality Informatics
--  @author     MittagQI - Quality Informatics
--  @license    GNU AFFERO GENERAL PUBLIC LICENSE version 3 with plugin-execption
-- 			 http://www.gnu.org/licenses/agpl.html http://www.translate5.net/plugin-exception.txt
-- 
-- END LICENSE AND COPYRIGHT
-- */

INSERT INTO Zf_configuration (`name`, `confirmed`, `module`, `category`, `value`, `default`, `defaults`, `type`, `description`) VALUES
('runtimeOptions.plugins.MtComparEval.url', 1, 'editor', 'plugins', 'http://localhost:8080', 'http://localhost:8080', '', 'string', 'Base URL to the MT-ComparEval Tool, for format see default value');


ALTER TABLE `LEK_task_meta` ADD COLUMN mtCompareEvalState enum('notsent','importing','imported') NOT NULL DEFAULT 'notsent' COMMENT 'Contains the current state of MT-ComparEval experiment to this task';
ALTER TABLE `LEK_task_meta` ADD COLUMN mtCompareEvalId varchar(256) NULL DEFAULT NULL COMMENT 'Contains the MT-ComparEval experiment ID';
ALTER TABLE `LEK_task_meta` ADD COLUMN mtCompareEvalStart datetime NULL DEFAULT NULL COMMENT 'Contains the datetime when export to MT-ComparEval was started';
