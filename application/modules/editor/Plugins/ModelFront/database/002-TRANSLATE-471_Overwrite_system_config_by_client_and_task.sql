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
                `guiName` = "ModelFront API token",
                `guiGroup` = "System setup: Language resources",
                `level` = "2",
                `description`  = "ModelFront api token. The token is used for api authentication. More info can be found in https://modelfront.com/",
                `comment` = ""
                WHERE `name` = "runtimeOptions.plugins.ModelFront.apiToken";
UPDATE Zf_configuration SET
                `default` = "https://api.modelfront.com/v1/",
                `defaults` = "",
                `guiName` = "ModelFront API URL",
                `guiGroup` = "System setup: Language resources",
                `level` = "2",
                `description`  = "ModelFront api url. In the current config also the api version should be included.",
                `comment` = ""
                WHERE `name` = "runtimeOptions.plugins.ModelFront.apiUrl";
