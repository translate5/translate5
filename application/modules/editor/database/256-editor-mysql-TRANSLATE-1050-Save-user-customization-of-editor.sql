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

/* remove duplicate data and leave the lates record */
DELETE FROM LEK_user_config WHERE id NOT IN (SELECT * FROM (SELECT MAX(id) FROM LEK_user_config GROUP BY userGuid,name) as tbl);

/* apply unique index zfconfig name and userGuid */
ALTER TABLE `LEK_user_config` ADD UNIQUE `userGuidConfigNameIndex`(`name`, `userGuid`);

INSERT INTO `Zf_configuration` (`name`, `confirmed`, `module`, `category`, `value`, `default`, `defaults`, `type`, `description`, `level`) 
VALUES ('runtimeOptions.frontend.defaultState.editor.westPanel', '1', 'editor', 'system', '{}', '{}', '', 'map', 'Default state configuration for the editor west panel. If this field value is empty ({} is not an empty value!), no state will be applied/saved for this component.',16);

INSERT INTO `Zf_configuration` (`name`, `confirmed`, `module`, `category`, `value`, `default`, `defaults`, `type`, `description`, `level`) 
VALUES ('runtimeOptions.frontend.defaultState.editor.eastPanel', '1', 'editor', 'system', '{}', '{}', '', 'map', 'Default state configuration for the editor east panel. If this field value is empty ({} is not an empty value!), no state will be applied/saved for this component.',16);

INSERT INTO `Zf_configuration` (`name`, `confirmed`, `module`, `category`, `value`, `default`, `defaults`, `type`, `description`, `level`) 
VALUES ('runtimeOptions.frontend.defaultState.editor.westPanelFileorderTree', '1', 'editor', 'system', '{}', '{}', '', 'map', 'Default state configuration for the editor west panel file order tree. If this field value is empty ({} is not an empty value!), no state will be applied/saved for this component.',16);

INSERT INTO `Zf_configuration` (`name`, `confirmed`, `module`, `category`, `value`, `default`, `defaults`, `type`, `description`, `level`) 
VALUES ('runtimeOptions.frontend.defaultState.editor.westPanelReferenceFileTree', '1', 'editor', 'system', '{}', '{}', '', 'map', 'Default state configuration for the editor west panel reference files tree. If this field value is empty ({} is not an empty value!), no state will be applied/saved for this component.',16);

INSERT INTO `Zf_configuration` (`name`, `confirmed`, `module`, `category`, `value`, `default`, `defaults`, `type`, `description`, `level`) 
VALUES ('runtimeOptions.frontend.defaultState.editor.eastPanelSegmentsMetapanel', '1', 'editor', 'system', '{}', '{}', '', 'map', 'Default state configuration for the editor east panel segments meta. If this field value is empty ({} is not an empty value!), no state will be applied/saved for this component.',16);

INSERT INTO `Zf_configuration` (`name`, `confirmed`, `module`, `category`, `value`, `default`, `defaults`, `type`, `description`, `level`) 
VALUES ('runtimeOptions.frontend.defaultState.editor.eastPanelCommentPanel', '1', 'editor', 'system', '{}', '{}', '', 'map', 'Default state configuration for the editor east panel comments. If this field value is empty ({} is not an empty value!), no state will be applied/saved for this component.',16);

INSERT INTO `Zf_configuration` (`name`, `confirmed`, `module`, `category`, `value`, `default`, `defaults`, `type`, `description`, `level`) 
VALUES ('runtimeOptions.frontend.defaultState.editor.languageResourceEditorPanel', '1', 'editor', 'system', '{}', '{}', '', 'map', 'Default state configuration for the editor language resources editor panel. If this field value is empty ({} is not an empty value!), no state will be applied/saved for this component.',16);

UPDATE `Zf_configuration` SET 
`name`='runtimeOptions.frontend.defaultState.editor.segmentsGrid' WHERE `name`='runtimeOptions.frontend.defaultState.segmentsGrid';
