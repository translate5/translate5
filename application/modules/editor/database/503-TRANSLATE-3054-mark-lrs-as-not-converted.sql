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

UPDATE LEK_languageresources lr
SET lr.specificData = JSON_SET(lr.specificData, '$.protection_hash', '')
WHERE lr.serviceType LIKE '%editor_Services_T5Memory%'
  AND JSON_EXTRACT(lr.specificData, '$.protection_hash') IS NOT NULL
  AND EXISTS (
    SELECT 1
    FROM (
        SELECT 1
        FROM LEK_content_protection_output_mapping om
            JOIN LEK_content_protection_content_recognition ocr
        ON ocr.id = om.outputContentRecognitionId AND ocr.enabled = 1
        JOIN (
            SELECT DISTINCT cr.id
            FROM LEK_content_protection_input_mapping im
                JOIN LEK_content_protection_content_recognition cr
                    ON cr.id = im.contentRecognitionId
            WHERE cr.enabled = 1
        ) icr ON icr.id = om.inputContentRecognitionId
        LIMIT 1
    ) x

    UNION ALL

    SELECT 1
    FROM LEK_content_protection_input_mapping im
        JOIN LEK_content_protection_content_recognition icr
            ON icr.id = im.contentRecognitionId
    WHERE icr.enabled = 1
        AND icr.keepAsIs = 1
    LIMIT 1
);