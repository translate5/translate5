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

-- fix the defaults for some values changed by us and where default was forgotten:
UPDATE Zf_configuration SET `default` = 'https://github.com/translate5/translate5/blob/develop/docs/ERRORCODES.md#{0}' WHERE name = 'runtimeOptions.errorCodesUrl';
UPDATE Zf_configuration SET `default` = '1' WHERE name = 'runtimeOptions.customers.anonymizeUsers';
UPDATE Zf_configuration SET `default` = '["surName","firstName","email","role","state","deadlineDate"]' WHERE name = 'runtimeOptions.editor.notification.userListColumns';
UPDATE Zf_configuration SET `default` = '{}' WHERE name = 'runtimeOptions.frontend.defaultState.editor.segmentsGrid';
UPDATE Zf_configuration SET `default` = '' WHERE name = 'runtimeOptions.frontend.defaultState.helpWindow.useroverview';
UPDATE Zf_configuration SET `default` = '/help/{0}' WHERE name = 'runtimeOptions.frontend.helpWindow.customeroverview.loaderUrl';
UPDATE Zf_configuration SET `default` = '/help/{0}' WHERE name = 'runtimeOptions.frontend.helpWindow.editor.loaderUrl';
UPDATE Zf_configuration SET `default` = '/help/{0}' WHERE name = 'runtimeOptions.frontend.helpWindow.languageresource.loaderUrl';
UPDATE Zf_configuration SET `default` = '/help/{0}' WHERE name = 'runtimeOptions.frontend.helpWindow.taskoverview.loaderUrl';
UPDATE Zf_configuration SET `default` = '1' WHERE name = 'runtimeOptions.customers.anonymizeUsers';
UPDATE Zf_configuration SET `default` = '0' WHERE name = 'runtimeOptions.frontend.tasklist.pmMailTo';
UPDATE Zf_configuration SET `default` = '70' WHERE name = 'runtimeOptions.InstantTranslate.minMatchRateBorder';
UPDATE Zf_configuration SET `default` = '{}' WHERE name = 'runtimeOptions.InstantTranslate.user.defaultLanguages';
UPDATE Zf_configuration SET `default` = '[]' WHERE name = 'runtimeOptions.LanguageResources.lucylt.credentials';
UPDATE Zf_configuration SET `default` = '[]' WHERE name = 'runtimeOptions.LanguageResources.lucylt.server';
UPDATE Zf_configuration SET `default` = '' WHERE name = 'runtimeOptions.LanguageResources.microsoft.apiUrl';
UPDATE Zf_configuration SET `default` = '["https://lc-api.sdl.com/"]' WHERE name = 'runtimeOptions.LanguageResources.sdllanguagecloud.server';
UPDATE Zf_configuration SET `default` = '{"8":"7", "9":"8", "10":"9", "11":"10", "12":"11", "13":"12", "14":"13", "15":"14", "16":"15", "17":"16", "18":"25", "19":"18", "20":"26", "24":"31", "54":"48", "70": "42", "96": "56"}' WHERE name = 'runtimeOptions.lengthRestriction.pixelMapping';
UPDATE Zf_configuration SET `default` = '2' WHERE name = 'runtimeOptions.plugins.AcrossHotfolder.defaultPM';
UPDATE Zf_configuration SET `default` = '' WHERE name = 'runtimeOptions.plugins.ModelFront.apiUrl';
UPDATE Zf_configuration SET `default` = '[]' WHERE name = 'runtimeOptions.plugins.NecTm.credentials';
UPDATE Zf_configuration SET `default` = '[]' WHERE name = 'runtimeOptions.plugins.NecTm.server';
UPDATE Zf_configuration SET `default` = '[]' WHERE name = 'runtimeOptions.plugins.NecTm.topLevelCategoriesIds';
UPDATE Zf_configuration SET `default` = '0' WHERE name = 'runtimeOptions.plugins.Okapi.import.fileconverters.attachOriginalFileAsReference';
UPDATE Zf_configuration SET `default` = '' WHERE name = 'runtimeOptions.plugins.PangeaMt.apikey';
UPDATE Zf_configuration SET `default` = '["http://prod.pangeamt.com:8080"]' WHERE name = 'runtimeOptions.plugins.PangeaMt.server';
UPDATE Zf_configuration SET `default` = 'admittedTerm-admn-sts' WHERE name = 'runtimeOptions.tbx.defaultAdministrativeStatus';
UPDATE Zf_configuration SET `default` = '20' WHERE name = 'runtimeOptions.worker.maxParallelWorkers';
UPDATE Zf_configuration SET `default` = '3' WHERE name = 'runtimeOptions.worker.MittagQI\\Translate5\\Plugins\\SpellCheck\\Worker.maxParallelWorkers';
UPDATE Zf_configuration SET value = 1, `default` = '1' WHERE name = 'runtimeOptions.plugins.SpellCheck.liveCheckOnEditing';


