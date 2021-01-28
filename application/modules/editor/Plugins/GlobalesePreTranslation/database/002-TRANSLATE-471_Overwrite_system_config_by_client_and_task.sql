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
                `default` = "",
                `defaults` = "",
                `guiName` = "Globalese: API key",
                `guiGroup` = "System setup: Language resources",
                `level` = "2",
                `description`  = "Api key for Globalese authentication",
                `comment` = ""
                WHERE `name` = "runtimeOptions.plugins.GlobalesePreTranslation.api.apiKey";
UPDATE Zf_configuration SET
                `default` = "https://translate5.globalese-mt.com/api/v2/",
                `defaults` = "",
                `guiName` = "Globalese: API URL",
                `guiGroup` = "System setup: Language resources",
                `level` = "2",
                `description`  = "Url used for Globalese api",
                `comment` = ""
                WHERE `name` = "runtimeOptions.plugins.GlobalesePreTranslation.api.url";
UPDATE Zf_configuration SET
                `default` = "",
                `defaults` = "",
                `guiName` = "Globalese: API username",
                `guiGroup` = "System setup: Language resources",
                `level` = "2",
                `description`  = "Username for Globalese API authentication",
                `comment` = ""
                WHERE `name` = "runtimeOptions.plugins.GlobalesePreTranslation.api.username";
