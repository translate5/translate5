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

UPDATE Zf_configuration SET
                `default` = "[\"mittagqi:K62yMbCgYMT9n4x4\"]",
                `defaults` = "",
                `guiName` = "NEC-TM: API credentials",
                `guiGroup` = "System setup: Language resources",
                `level` = "2",
                `description`  = "Username and password for connecting to NEC-TM.",
                `comment` = ""
                WHERE `name` = "runtimeOptions.plugins.NecTm.credentials";
UPDATE Zf_configuration SET
                `default` = "[\"http://pangeanic-online.com:47979\"]",
                `defaults` = "",
                `guiName` = "NEC-TM: API URL",
                `guiGroup` = "System setup: Language resources",
                `level` = "2",
                `description`  = "NEC-TM Api Server URL; format: [\"SCHEME://HOST:PORT\"]",
                `comment` = ""
                WHERE `name` = "runtimeOptions.plugins.NecTm.server";
UPDATE Zf_configuration SET
                `default` = "[\"tag391\",\"tag840\"]",
                `defaults` = "",
                `guiName` = "NEC-TM: Top-level categories",
                `guiGroup` = "System setup: Language resources",
                `level` = "2",
                `description`  = "Only TM data below the top-level categories (in NEC-TMs wording these are called „Tags“) can be accessed (plus all public categories). Enter the NEC-TM\'s tag-ids here, not their tag-names!",
                `comment` = ""
                WHERE `name` = "runtimeOptions.plugins.NecTm.topLevelCategoriesIds";
