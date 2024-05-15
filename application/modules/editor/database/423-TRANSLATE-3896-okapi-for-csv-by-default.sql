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

-- CSV internal parser active
INSERT INTO `Zf_configuration` (`name`, `confirmed`, `module`, `category`, `value`, `default`, `defaults`, `type`, `description`, `level`, `guiName`, `guiGroup`)
VALUES ('runtimeOptions.import.fileparser.csv.active', '1', 'editor', 'editor', '0', '0', '', 'boolean', 'When activated, CSV files will be processed with the internal CSV parser (instead of e.g. OKAPI)', 8, 'CSV: use internal fileparser', 'File format: CSV');

-- CSV format options
UPDATE `Zf_configuration` SET `guiGroup` = 'File format: CSV', `guiName` = 'CSV import: Regular expression used BEFORE tag-protection (only with internal CSV fileparser)' WHERE `name` = 'runtimeOptions.import.fileparser.csv.options.regexes.beforeTagParsing.regex';
UPDATE `Zf_configuration` SET `guiGroup` = 'File format: CSV', `guiName` = 'CSV import: Regular expression used AFTER tag protection (only with internal CSV fileparser)' WHERE `name` = 'runtimeOptions.import.fileparser.csv.options.regexes.afterTagParsing.regex';
UPDATE `Zf_configuration` SET `guiGroup` = 'File format: CSV', `guiName` = 'CSV import: Name of source text column (only with internal CSV fileparser)' WHERE `name` = 'runtimeOptions.import.csv.fields.source';
UPDATE `Zf_configuration` SET `guiGroup` = 'File format: CSV', `guiName` = 'CSV import: Name of ID column (only with internal CSV fileparser)' WHERE `name` = 'runtimeOptions.import.csv.fields.mid';
UPDATE `Zf_configuration` SET `guiGroup` = 'File format: CSV', `guiName` = 'CSV import: ecnclosure (only with internal CSV fileparser)' WHERE `name` = 'runtimeOptions.import.csv.enclosure';
UPDATE `Zf_configuration` SET `guiGroup` = 'File format: CSV', `guiName` = 'CSV import: delimiter (only with internal CSV fileparser)' WHERE `name` = 'runtimeOptions.import.csv.delimiter';

-- File formats: Ex File parser options
UPDATE `Zf_configuration` SET `guiGroup` = 'File formats', `guiName` = 'Import (all formats): Protect tags' WHERE `name` = 'runtimeOptions.import.fileparser.options.protectTags';
UPDATE `Zf_configuration` SET `guiGroup` = 'File formats', `guiName` = 'XLF Import: Sub element length is included in overall transunit-length' WHERE `name` = 'runtimeOptions.import.xlf.includedSubElementInLengthCalculation';
UPDATE `Zf_configuration` SET `guiGroup` = 'File formats', `guiName` = 'XLIFF import: Placeables detection XPathes' WHERE `name` = 'runtimeOptions.import.xlf.placeablesXpathes';

-- File formats: others
UPDATE `Zf_configuration` SET `guiGroup` = 'File formats', `guiName` = 'Export (all formats): Export comments to xliff' WHERE `name` = 'runtimeOptions.editor.export.exportComments';
UPDATE `Zf_configuration` SET `guiGroup` = 'File formats', `guiName` = 'Export (all formats): Export zip guid folder' WHERE `name` = 'runtimeOptions.editor.export.taskguiddirectory';

-- OKAPI
UPDATE `Zf_configuration` SET `guiGroup` = 'File formats', `guiName` = 'OKAPI import: Attach original files' WHERE `name` = 'runtimeOptions.plugins.Okapi.import.fileconverters.attachOriginalFileAsReference';

-- Transit format
UPDATE `Zf_configuration` SET `guiGroup` = 'File format: Transit' WHERE `name` = 'runtimeOptions.plugins.transit.exportOnlyEditable';
UPDATE `Zf_configuration` SET `guiGroup` = 'File format: Transit' WHERE `name` = 'runtimeOptions.plugins.transit.writeInfoField.enabled';
UPDATE `Zf_configuration` SET `guiGroup` = 'File format: Transit' WHERE `name` = 'runtimeOptions.plugins.transit.writeInfoField.exportDate';
UPDATE `Zf_configuration` SET `guiGroup` = 'File format: Transit' WHERE `name` = 'runtimeOptions.plugins.transit.writeInfoField.exportDateValue';
UPDATE `Zf_configuration` SET `guiGroup` = 'File format: Transit' WHERE `name` = 'runtimeOptions.plugins.transit.writeInfoField.manualStatus';
UPDATE `Zf_configuration` SET `guiGroup` = 'File format: Transit' WHERE `name` = 'runtimeOptions.plugins.transit.writeInfoField.termsWithoutTranslation';
