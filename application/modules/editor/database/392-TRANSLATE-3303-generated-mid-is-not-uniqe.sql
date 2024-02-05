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

ALTER TABLE `LEK_files` 
ADD COLUMN `isReimportable` TINYINT(1) NOT NULL DEFAULT '0' AFTER `encoding`;

ALTER TABLE `LEK_segments_meta`
    ADD COLUMN `mrkMid` VARCHAR(255) NULL COMMENT 'Represent the parsed mrk id or internal counter in case the mrk does not have id attribute.',
    ADD COLUMN `sourceFileId` VARCHAR(1000) NULL COMMENT 'Parsed file identifier. For xlf this is the original attribute from the file tag',
    ADD COLUMN `transunitHash` VARCHAR(255) NULL COMMENT 'Unique hash generated from: \n- LEK_files->fileId\n- the original attribute from the xlif file tag \n- the id tag value from the trans-unit';

# Migrate the transunitId to new transunitHash value
UPDATE LEK_segments_meta m
    INNER JOIN LEK_segments s ON m.segmentId = s.id
SET m.transunitHash = md5(CONCAT(m.transunitHash,'_',s.fileId));

# Set the reimportable flag for each file where the matching the file parsers below
UPDATE LEK_files SET isReimportable = 1
WHERE fileParser IN(
                        'editor_Models_Import_FileParser_Xlf',
                        'FixXlfTranslate2082',
                        'editor_Models_Import_FileParser_XlfZend',
                        'editor_Models_Import_FileParser_Xml'
                       );


INSERT INTO `Zf_configuration` (`name`, `confirmed`, `module`, `category`, `value`, `default`, `defaults`, `type`, `description`, `level`, `guiName`, `guiGroup`, `comment`)
VALUES
    ('runtimeOptions.import.xlf.includedSubElementInLengthCalculation', '1', 'editor', 'import', '0', '0', '', 'boolean', 'If enabled, the length of the content of sub element is added to the overall transunit-length - if disabled the length is ignored.', 8, 'Import: sub element length is included in overall transunit-length', 'Fileparser: xlf', '');
