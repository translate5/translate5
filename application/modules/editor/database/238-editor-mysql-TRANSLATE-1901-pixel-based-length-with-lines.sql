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


-- (1) new: max. number of lines for segments

-- additions for segment's meta
ALTER TABLE `LEK_segments_meta`
ADD `maxNumberOfLines` INT(3) DEFAULT NULL COMMENT 'max. number of lines in pixel-based length check' AFTER `maxWidth`;

-- default value for number of lines
INSERT INTO `Zf_configuration` (`name`, `confirmed`, `module`, `category`, `value`, `default`, `defaults`, `type`, `description`) VALUES 
('runtimeOptions.lengthRestriction.maxNumberOfLines', '1', 'editor', 'system', '1', '1', '', 'integer', 'Default max. number of  lines in pixel-based length check.');

-- Migration-script
UPDATE `LEK_segments_meta` SET `maxNumberOfLines` = 1 WHERE `sizeUnit` = 'pixel';

-- (2) restructure config according to structure of task-templates

UPDATE `Zf_configuration` SET `name` = 'runtimeOptions.lengthRestriction.pixelMapping' WHERE `name` = 'runtimeOptions.pixelMapping.pixelWidths';
