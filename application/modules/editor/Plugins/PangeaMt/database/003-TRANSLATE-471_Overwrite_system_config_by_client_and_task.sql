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
                `default` = "(put your api key here)",
                `defaults` = "",
                `guiName` = "PangeaMT API key",
                `guiGroup` = "System setup: Language resources",
                `level` = "2",
                `description`  = "The apikey as given from PangeaMT",
                `comment` = ""
                WHERE `name` = "runtimeOptions.plugins.PangeaMt.apikey";
UPDATE Zf_configuration SET
                `default` = "[]",
                `defaults` = "",
                `guiName` = "PangeaMT API URL",
                `guiGroup` = "System setup: Language resources",
                `level` = "2",
                `description`  = "PangeaMT Api Server; format: [\"SCHEME://HOST:PORT\"]",
                `comment` = ""
                WHERE `name` = "runtimeOptions.plugins.PangeaMt.server";
UPDATE Zf_configuration SET
                `default` = "70",
                `defaults` = "",
                `guiName` = "PangeaMT default match rate",
                `guiGroup` = "System setup: Language resources",
                `level` = "2",
                `description`  = "Default fuzzy match value for translations done by PangeaMT. Used in the analysis and in the fuzzy match panel, if ModelFront is not used for risk prediction of MT.",
                `comment` = ""
                WHERE `name` = "runtimeOptions.plugins.PangeaMt.matchrate";
UPDATE Zf_configuration SET
                `default` = "50",
                `defaults` = "",
                `guiName` = "PangeaMT timeout",
                `guiGroup` = "System setup: Language resources",
                `level` = "2",
                `description`  = "Request timeout in seconds for PangeaMT.",
                `comment` = ""
                WHERE `name` = "runtimeOptions.plugins.PangeaMt.requestTimeout";
