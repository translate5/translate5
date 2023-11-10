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

RENAME TABLE LEK_number_protection_number_recognition TO LEK_content_protection_content_recognition;

CREATE TABLE `LEK_content_protection_input_mapping` (
    `id` int (11) NOT NULL AUTO_INCREMENT,
    `languageId` int (11) NOT NULL COMMENT 'Foreign Key to LEK_languages',
    `contentRecognitionId` int (11) NOT NULL COMMENT 'Foreign Key to LEK_content_protection_number_recognition',
    PRIMARY KEY (`id`),
    CONSTRAINT FOREIGN KEY (`languageId`) REFERENCES `LEK_languages` (`id`) ON DELETE CASCADE,
    CONSTRAINT FOREIGN KEY (`contentRecognitionId`) REFERENCES `LEK_content_protection_number_recognition` (`id`) ON DELETE CASCADE,
    UNIQUE (`languageId`, `contentRecognitionId`)
);

INSERT INTO LEK_content_protection_input_mapping (id, languageId, contentRecognitionId)
SELECT id, languageId, numberRecognitionId
FROM LEK_number_protection_input_mapping;

CREATE TABLE `LEK_content_protection_output_mapping` (
    `id` int (11) NOT NULL AUTO_INCREMENT,
    `languageId` int (11) DEFAULT NULL COMMENT 'Foreign Key to LEK_languages',
    `contentRecognitionId` int (11) NOT NULL COMMENT 'Foreign Key to LEK_content_protection_number_recognition',
    `format` varchar(124) DEFAULT NULL,
    PRIMARY KEY (`id`),
    CONSTRAINT FOREIGN KEY (`languageId`) REFERENCES `LEK_languages` (`id`) ON DELETE CASCADE,
    CONSTRAINT FOREIGN KEY (`contentRecognitionId`) REFERENCES `LEK_content_protection_number_recognition` (`id`) ON DELETE CASCADE,
    UNIQUE (`languageId`, `contentRecognitionId`)
);

INSERT INTO LEK_content_protection_output_mapping (id, languageId, contentRecognitionId)
SELECT id, languageId, numberRecognitionId
FROM LEK_number_protection_output_mapping;
