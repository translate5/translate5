-- /*
-- START LICENSE AND COPYRIGHT
--
--  This file is part of translate5
--
--  Copyright (c) 2013 - '.(date('Y')).' Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.
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
SET @@session.group_concat_max_len = 4294967295;


-- SELECT @ids := (SELECT GROUP_CONCAT(`id`) FROM `terms_attributes_datatype`);
-- the above query produces:
--   Warning Code : 1287
--   Setting user variables within expressions is deprecated and will be removed in a future release.
--   Consider alternatives: 'SET variable=expression, ...', or 'SELECT expression(s) INTO variables(s)'.
-- SELECT @ids = (SELECT GROUP_CONCAT(`id`) FROM `terms_attributes_datatype`);
-- the above (1st alternative advised) gives empty result
-- so replaced as advised in 2nd alternative
SELECT (SELECT GROUP_CONCAT(`id`) FROM `terms_attributes_datatype`) INTO @ids;

SELECT (CONCAT('UPDATE `terms_attributes` SET `dataTypeId` = NULL WHERE `dataTypeId` NOT IN (', @ids, ')')) INTO @query;
PREPARE stmt FROM @query;
EXECUTE stmt;

-- add foreign key
ALTER TABLE `terms_attributes`
    ADD CONSTRAINT `tad_id` FOREIGN KEY (`dataTypeId`) REFERENCES `terms_attributes_datatype` (`id`) ON UPDATE CASCADE ON DELETE SET NULL;
