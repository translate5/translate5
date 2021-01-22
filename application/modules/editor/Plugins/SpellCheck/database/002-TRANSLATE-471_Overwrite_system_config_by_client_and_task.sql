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
                `default` = "0",
                `defaults` = "",
                `guiName` = "Spell-, grammar and style check active",
                `guiGroup` = "Editor: QA",
                `level` = "8",
                `description`  = "If checked, spell- grammar and style check is active (based on languagetool)",
                `comment` = ""
                WHERE `name` = "runtimeOptions.plugins.SpellCheck.active";
UPDATE Zf_configuration SET
                `default` = "http://yourlanguagetooldomain/api/v2",
                `defaults` = "",
                `guiName` = "Spell-, grammar and style check service URL",
                `guiGroup` = "System setup: General",
                `level` = "2",
                `description`  = "Base-URL used for LanguagaTool - use the API-URL of your installed languageTool (without trailing slash!) - for example http://yourlanguagetooldomain/api/v2",
                `comment` = ""
                WHERE `name` = "runtimeOptions.plugins.SpellCheck.languagetool.api.baseurl";
