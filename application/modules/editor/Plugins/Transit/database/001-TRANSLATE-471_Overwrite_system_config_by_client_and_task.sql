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
                `defaults` = "NULL",
                `guiName` = "Transit files: Add export date to infofield ",
                `guiGroup` = "File formats",
                `level` = "16",
                `description`  = "If the writing of information to the target-infofield is activated (this is determined by another configuraiton parameter), the export date of the current file from translate5 to the file system is added to the target infofield on export",
                `comment` = ""
                WHERE `name` = "runtimeOptions.plugins.transit.writeInfoField.exportDate";
UPDATE Zf_configuration SET
                `default` = "0",
                `defaults` = "NULL",
                `guiName` = "Transit files: Add info to infofield ",
                `guiGroup` = "File formats",
                `level` = "16",
                `description`  = "If checked, informations are added to the target-infofield of a segment- further configuration values decide, which information.",
                `comment` = ""
                WHERE `name` = "runtimeOptions.plugins.transit.writeInfoField.enabled";
UPDATE Zf_configuration SET
                `default` = "",
                `defaults` = "",
                `guiName` = "Transit files: Export date to write",
                `guiGroup` = "File formats",
                `level` = "16",
                `description`  = "If the writing of information to the target-infofield is activated (this is determined by another configuraiton parameter), and if the export date is added to the target-infofield (also determined by another parameter) this text field becomes relevant. If it is empty the current date is used as export date. If it contains a valid date in the form YYYY-MM-DD this date is used.",
                `comment` = ""
                WHERE `name` = "runtimeOptions.plugins.transit.writeInfoField.exportDateValue";
UPDATE Zf_configuration SET
                `default` = "1",
                `defaults` = "NULL",
                `guiName` = "Transit files: Write back only editable",
                `guiGroup` = "File formats",
                `level` = "16",
                `description`  = "If checked, only the content of editable segments is written back to transit file om export. This does not influence the Info Field!",
                `comment` = ""
                WHERE `name` = "runtimeOptions.plugins.transit.exportOnlyEditable";
UPDATE Zf_configuration SET
                `default` = "0",
                `defaults` = "NULL",
                `guiName` = "Transit files: Write manual status to infofield ",
                `guiGroup` = "File formats",
                `level` = "16",
                `description`  = "If the writing of information to the target-infofield is activated (this is determined by another configuraiton parameter), the manual status is added to the target infofield on export",
                `comment` = ""
                WHERE `name` = "runtimeOptions.plugins.transit.writeInfoField.manualStatus";
UPDATE Zf_configuration SET
                `default` = "0",
                `defaults` = "NULL",
                `guiName` = "Transit files: Write source terms without translation to infofield",
                `guiGroup` = "File formats",
                `level` = "16",
                `description`  = "If the writing of information to the target-infofield is activated (this is determined by another configuraiton parameter), and if this checkbox is checcked, terms in the source text without any translation in the target text are written to infofield.",
                `comment` = ""
                WHERE `name` = "runtimeOptions.plugins.transit.writeInfoField.termsWithoutTranslation";
