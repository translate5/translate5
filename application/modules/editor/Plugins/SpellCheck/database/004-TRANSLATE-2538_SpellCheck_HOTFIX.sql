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

SELECT (SELECT `value` FROM `Zf_configuration` WHERE `name` = "runtimeOptions.plugins.SpellCheck.languagetool.url.gui")
INTO @languagetool;

UPDATE `Zf_configuration`
SET `value` = IF (LENGTH(@languagetool) > 0, CONCAT('[\"', @languagetool, '\"]'), '[]')
WHERE `name` = "runtimeOptions.plugins.SpellCheck.languagetool.url.default" AND `value` = '["http://localhost:8081/v2"]';

UPDATE `Zf_configuration`
SET `value` = IF (LENGTH(@languagetool) > 0, CONCAT('[\"', @languagetool, '\"]'), '[]')
WHERE `name` = "runtimeOptions.plugins.SpellCheck.languagetool.url.import" AND `value` LIKE '["http://localhost:8081/v2",%"http://localhost:8082/v2"]';

SET @db = DATABASE();
SET @tbl = "LEK_segments_meta";
SET @col = "spellcheckState";
SET @sql = (SELECT IF(
       (
           SELECT COUNT(*) FROM `INFORMATION_SCHEMA`.`COLUMNS`
           WHERE `table_name`   = @tbl
             AND `table_schema` = @db
             AND `column_name`  = @col
       ) > 0,
       "SELECT 1",
       CONCAT("ALTER TABLE ", @tbl, " ADD ", @col, " VARCHAR(36) DEFAULT 'unchecked' COMMENT 'Contains the SpellCheck-state for this segment while importing';")
));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;