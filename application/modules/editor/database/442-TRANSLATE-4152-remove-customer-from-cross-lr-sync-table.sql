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

ALTER TABLE `LEK_cross_language_resource_synchronization_connection`
    DROP CONSTRAINT LEK_cross_language_resource_synchronization_connection_ibfk_1;

ALTER TABLE `LEK_cross_language_resource_synchronization_connection`
    DROP CONSTRAINT LEK_cross_language_resource_synchronization_connection_ibfk_2;

ALTER TABLE `LEK_cross_language_resource_synchronization_connection`
    DROP CONSTRAINT LEK_cross_language_resource_synchronization_connection_ibfk_3;

ALTER TABLE LEK_cross_language_resource_synchronization_connection
    DROP INDEX languageResourcePair;

ALTER TABLE `LEK_cross_language_resource_synchronization_connection` DROP COLUMN `customerId`;

ALTER TABLE `LEK_cross_language_resource_synchronization_connection`
    ADD CONSTRAINT LEK_cross_language_resource_synchronization_connection_ibfk_1
        FOREIGN KEY (sourceLanguageResourceId) REFERENCES LEK_languageresources (id)
            ON DELETE RESTRICT
            ON UPDATE CASCADE;

ALTER TABLE `LEK_cross_language_resource_synchronization_connection`
    ADD CONSTRAINT LEK_cross_language_resource_synchronization_connection_ibfk_2
        FOREIGN KEY (targetLanguageResourceId) REFERENCES LEK_languageresources (id)
            ON DELETE RESTRICT
            ON UPDATE CASCADE;

ALTER TABLE LEK_cross_language_resource_synchronization_connection
    ADD UNIQUE KEY languageResourcePair (sourceLanguageResourceId, targetLanguageResourceId);


