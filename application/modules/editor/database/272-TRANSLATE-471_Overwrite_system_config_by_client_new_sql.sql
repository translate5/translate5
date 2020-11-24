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

UPDATE `Zf_configuration` 
SET `guiGroup`='TermPortal', 
`guiName`='All translate5 languages available for creating term?', 
`description`='If activated, when the user creates a new term in the TermPortal, he is able to select the language of the term from all languages available in translate5. If deactivated, he can only choose from those languages, that exist in the language resources that are available for him at the moment.',
`level`='2' 
WHERE `name`='runtimeOptions.termportal.newTermAllLanguagesAvailable';

UPDATE Zf_configuration SET
 `guiName` = "Autopropagate / Repetition editor default behaviour for empty target",
 `guiGroup` = "Editor: Miscellaneous options",
 `description`  = "Default behaviour, for „empty target“ checkbox in the repetition editor (auto-propgate): Only replace repetition automatically / propose replacement of repetition, if target is empty. This is the default behaviour, that can be changed by the user."
 WHERE `name` = "runtimeOptions.alike.showOnEmptyTarget";
 
 UPDATE Zf_configuration SET
 `guiName` = "Status panel: Show"
 WHERE `name` = "runtimeOptions.segments.showStatus";

-- for now we set this level 2 (when the refactoring is done this should be level 16) 
  UPDATE Zf_configuration SET
 `level` = 2
 WHERE `name` = "runtimeOptions.frontend.importTask.edit100PercentMatch";
 
 UPDATE Zf_configuration SET
 `guiGroup` = "Language resources"
WHERE `name` = "runtimeOptions.editor.LanguageResources.disableIfOnlyTermCollection";

 UPDATE Zf_configuration SET
 `guiName` = "Help window URL: Editor"
WHERE `name` = "runtimeOptions.frontend.helpWindow.editor.loaderUrl";

-- we set for not this on level 1, when it is refactored it should be set to level 2
 UPDATE Zf_configuration SET
 `level` = 1
WHERE `name` = "runtimeOptions.LanguageResources.opentm2.showMultiple100PercentMatches";

 UPDATE Zf_configuration SET
 `guiGroup` = "Language resources" 
WHERE `guiGroup` = "System setup: Language resources";

 UPDATE Zf_configuration SET
 `guiGroup` = "System setup: Load balancing" 
WHERE `name` = "runtimeOptions.worker.editor_Models_Export_Xliff2Worker.maxParallelWorkers";
