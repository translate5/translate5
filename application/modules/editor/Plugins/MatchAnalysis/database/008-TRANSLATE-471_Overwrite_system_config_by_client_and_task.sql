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
                `default` = "1",
                `defaults` = "",
                `guiName` = "Language resource batch query: Enable",
                `guiGroup` = "System setup: Language resources",
                `level` = "2",
                `description`  = "Enables batch query requests for match analysis and pretranslations only for the associated language resource that support batch query. Batch query is much faster for many language resources for imports and InstantTranslate",
                `comment` = ""
                WHERE `name` = "runtimeOptions.plugins.MatchAnalysis.enableBatchQuery";

UPDATE Zf_configuration SET
                 `default` = "3",
                 `defaults` = "",
                 `guiName` = "Import: Analysis: Max. parallel processes",
                 `guiGroup` = "System setup: Load balancing",
                 `level` = "2",
                 `description`  = "Max parallel running workers of the MatchAnalysis BatchWorker",
                 `comment` = ""
                 WHERE `name` = "runtimeOptions.worker.editor_Plugins_MatchAnalysis_BatchWorker.maxParallelWorkers";