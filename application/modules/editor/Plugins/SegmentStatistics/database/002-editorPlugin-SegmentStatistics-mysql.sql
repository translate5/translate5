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

ALTER TABLE `LEK_plugin_segmentstatistics` 
ADD COLUMN `wordCount` int(11) NOT NULL COMMENT 'number of words in the segment' 
AFTER `charCount`;

UPDATE `LEK_plugin_segmentstatistics` SET wordCount = -1 WHERE `type` = 'import';

CREATE TABLE `LEK_plugin_segmentstatistic_terms` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `taskGuid` varchar(38) NOT NULL COMMENT 'Foreign Key to LEK_task',
  `mid` varchar(60) NOT NULL COMMENT 'Foreign Key to LEK_terms',
  `segmentId` int(11) NOT NULL COMMENT 'Segment ID, no FK needed',
  `fieldName` varchar(120) NOT NULL COMMENT 'name of the segment field',
  `fieldType` varchar(120) NOT NULL COMMENT 'type of the segment field',  
  `term` varchar(19000) NOT NULL COMMENT 'Term Content',
  `notFoundCount` int(11) NOT NULL DEFAULT 0 COMMENT 'count of this term not found',
  `foundCount` int(11) NOT NULL DEFAULT 0 COMMENT 'count of this term found',
  `type` enum('import','export') DEFAULT 'import',
  PRIMARY KEY (`id`),
  UNIQUE KEY `termPerTask` (`mid`, `segmentId`, `fieldName`, `type`),
  CONSTRAINT FOREIGN KEY (`taskGuid`) REFERENCES `LEK_task` (`taskGuid`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT INTO Zf_configuration (`name`, `confirmed`, `module`, `category`, `value`, `default`, `defaults`, `type`, `description`) VALUES 
('runtimeOptions.plugins.SegmentStatistics.xlsTemplateExport', 1, 'editor', 'plugins', 'modules/editor/Plugins/SegmentStatistics/templates/export-template.xlsx', 'modules/editor/Plugins/SegmentStatistics/templates/export-template.xlsx', null, 'absolutepath', 'Path to the XLSX export template. Path can be absolute or relative to application directory.'),
('runtimeOptions.plugins.SegmentStatistics.xlsTemplateImport', 1, 'editor', 'plugins', 'modules/editor/Plugins/SegmentStatistics/templates/import-template.xlsx', 'modules/editor/Plugins/SegmentStatistics/templates/import-template.xlsx', null, 'absolutepath', 'Path to the XLSX import template. Path can be absolute or relative to application directory.');

INSERT INTO Zf_configuration (`name`, `confirmed`, `module`, `category`, `value`, `default`, `defaults`, `type`, `description`) VALUES 
('runtimeOptions.plugins.SegmentStatistics.metaToIgnore.transitLockedForRefMat', 1, 'editor', 'plugins', '0', '0', null, 'boolean', 'decides, if segments with metadata "transitLockedForRefMat" will be ignored by this plugin.')
ON DUPLICATE KEY UPDATE `name`=`name`;


