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
                `default` = "modules/editor/Plugins/SegmentStatistics/templates/export-template.xlsx",
                `defaults` = "NULL",
                `guiName` = "",
                `guiGroup` = "",
                `level` = "1",
                `description`  = "Path to the XLSX export template. Path can be absolute or relative to application directory.",
                `comment` = ""
                WHERE `name` = "runtimeOptions.plugins.SegmentStatistics.xlsTemplateExport";
UPDATE Zf_configuration SET
                `default` = "modules/editor/Plugins/SegmentStatistics/templates/import-template.xlsx",
                `defaults` = "NULL",
                `guiName` = "",
                `guiGroup` = "",
                `level` = "1",
                `description`  = "Path to the XLSX import template. Path can be absolute or relative to application directory.",
                `comment` = ""
                WHERE `name` = "runtimeOptions.plugins.SegmentStatistics.xlsTemplateImport";
UPDATE Zf_configuration SET
                `default` = "0",
                `defaults` = "NULL",
                `guiName` = "",
                `guiGroup` = "",
                `level` = "1",
                `description`  = "decides, if segments with metadata \"transitLockedForRefMat\" will be ignored by this plugin.",
                `comment` = ""
                WHERE `name` = "runtimeOptions.plugins.SegmentStatistics.metaToIgnore.transitLockedForRefMat";
UPDATE Zf_configuration SET
                `default` = "15",
                `defaults` = "",
                `guiName` = "",
                `guiGroup` = "",
                `level` = "1",
                `description`  = "If there are more files in the task as configured here, the worksheets per file are disabled, only the summary worksheet is shown",
                `comment` = ""
                WHERE `name` = "runtimeOptions.plugins.SegmentStatistics.disableFileWorksheetCount";
UPDATE Zf_configuration SET
                `default` = "0",
                `defaults` = "",
                `guiName` = "",
                `guiGroup` = "",
                `level` = "1",
                `description`  = "If enabled only the filtered file set of statistics are created. If disabled all are generated.",
                `comment` = ""
                WHERE `name` = "runtimeOptions.plugins.SegmentStatistics.createFilteredOnly";
