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

CREATE TABLE `LEK_task_config` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `taskGuid` varchar(38) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `value` mediumtext COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`id`),
  UNIQUE KEY `taskGuidConfigName` (`taskGuid`,`name`),
  KEY `taskGuidIdx` (`taskGuid`),
  KEY `configNameIdx` (`name`),
  CONSTRAINT `LEK_task_fk` FOREIGN KEY (`taskGuid`) REFERENCES `LEK_task` (`taskGuid`) ON DELETE CASCADE,
  CONSTRAINT `Zf_configuration_fk` FOREIGN KEY (`name`) REFERENCES `Zf_configuration` (`name`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `LEK_customer_config` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `customerId` int(11) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `value` mediumtext COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`id`),
  UNIQUE KEY `customerIdConfigName` (`customerId`,`name`),
  KEY `customerIdIdx` (`customerId`),
  KEY `configNameIdx` (`name`),
  CONSTRAINT `LEK_customer-LEK_customer_config-fk` FOREIGN KEY (`customerId`) REFERENCES `LEK_customer` (`id`) ON DELETE CASCADE,
  CONSTRAINT `Zf_configuration-LEK_customer_config-fk` FOREIGN KEY (`name`) REFERENCES `Zf_configuration` (`name`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `Zf_acl_rules` (`module`, `role`, `resource`, `right`) 
 VALUES ('editor', 'pm', 'applicationconfigLevel', 'customer');
 
INSERT INTO `Zf_acl_rules` (`module`, `role`, `resource`, `right`) 
 VALUES ('editor', 'pm', 'applicationconfigLevel', 'task');
 
 INSERT INTO `Zf_acl_rules` (`module`, `role`, `resource`, `right`) 
 VALUES ('editor', 'pm', 'applicationconfigLevel', 'taskImport');
 
 INSERT INTO `Zf_acl_rules` (`module`, `role`, `resource`, `right`) 
 VALUES ('editor', 'admin', 'applicationconfigLevel', 'customer');
 
INSERT INTO `Zf_acl_rules` (`module`, `role`, `resource`, `right`) 
 VALUES ('editor', 'admin', 'applicationconfigLevel', 'task');
 
 INSERT INTO `Zf_acl_rules` (`module`, `role`, `resource`, `right`) 
 VALUES ('editor', 'admin', 'applicationconfigLevel', 'taskImport');
 
INSERT INTO `Zf_acl_rules` (`module`, `role`, `resource`, `right`) 
VALUES ('editor', 'pm', 'frontend', 'configOverwriteGrid');

INSERT INTO `Zf_acl_rules` (`module`, `role`, `resource`, `right`) 
VALUES ('editor', 'admin', 'frontend', 'configOverwriteGrid');
 
 
 -- remove the system right level acl. The system config should not be edited via frontend/api
 DELETE FROM `Zf_acl_rules` WHERE `resource`='applicationconfigLevel' AND `right`='system';
 
 -- change the user level to 32, the task level is now 16, and task import level 8
 UPDATE Zf_configuration SET level = 32 
WHERE level = 16;

-- Update the config values 
UPDATE Zf_configuration SET
                 `default` = "individual",
                 `defaults` = "never,always,individual",
                 `guiName` = "Autopropagate / Repetition editor default behaviour",
                 `guiGroup` = "Editor: Miscellaneous options",
                 `level` = "32",
                 `description`  = "Default behaviour, for the repetition editor (auto-propgate). Possible values: \'never\', \'always\', \'individual\' – they refer to when automatic replacements are made. Individual means, for every segment with repetitions a window will pop-up and asked the user what to do.",
                 `comment` = ""
                 WHERE `name` = "runtimeOptions.alike.defaultBehaviour";
UPDATE Zf_configuration SET
                 `default` = "{\"1\": \"Manueller Demo Status 1\", \"2\": \"Muss erneut überprüft werden\"}",
                 `defaults` = "",
                 `guiName` = "Manual status options",
                 `guiGroup` = "Editor: Miscellaneous options",
                 `level` = "8",
                 `description`  = "Available options for the status, that can be set manually on the right side of the editor",
                 `comment` = ""
                 WHERE `name` = "runtimeOptions.segments.stateFlags";
UPDATE Zf_configuration SET
                 `default` = "1",
                 `defaults` = "",
                 `guiName` = "On leaving a task: Show second confirmation window",
                 `guiGroup` = "Editor: Miscellaneous options",
                 `level` = "4",
                 `description`  = "When an assigned user leaves a task, he is asked, if he wants to finish or just leave the task. If this checkbox here is checked, and the user that leaves the task clicks „finish task“, he will be asked a second time, if he really wants to finish.",
                 `comment` = ""
                 WHERE `name` = "runtimeOptions.editor.showConfirmFinishTaskPopup";
UPDATE Zf_configuration SET
                 `default` = "1",
                 `defaults` = "",
                 `guiName` = "Show reference file pop-up",
                 `guiGroup` = "Editor: Miscellaneous options",
                 `level` = "16",
                 `description`  = "If checked, a pop-up is shown after a task is opened in the translate5 editor, if reference files are attached to the task.",
                 `comment` = ""
                 WHERE `name` = "runtimeOptions.editor.showReferenceFilesPopup";
UPDATE Zf_configuration SET
                 `default` = "0",
                 `defaults` = "",
                 `guiName` = "Source editing possible",
                 `guiGroup` = "Editor: Miscellaneous options",
                 `level` = "8",
                 `description`  = "Enable the editing of the source text in the editor (export of the changed text is only possible for CSV so far).",
                 `comment` = ""
                 WHERE `name` = "runtimeOptions.import.enableSourceEditing";
UPDATE Zf_configuration SET
                 `default` = "1",
                 `defaults` = "",
                 `guiName` = "Is the status panel on the right side of the editor visible",
                 `guiGroup` = "Editor: Miscellaneous options",
                 `level` = "16",
                 `description`  = "If checked, the status panel on the right side of the editor is visible",
                 `comment` = ""
                 WHERE `name` = "runtimeOptions.segments.showStatus";
UPDATE Zf_configuration SET
                 `default` = "",
                 `defaults` = "",
                 `guiName` = "100% matches: Edit them",
                 `guiGroup` = "Editor: QA",
                 `level` = "16",
                 `description`  = "If checked, 100% matches can be edited in the task.",
                 `comment` = ""
                 WHERE `name` = "runtimeOptions.frontend.importTask.fieldsDefaultValue";
UPDATE Zf_configuration SET
                 `default` = "1",
                 `defaults` = "",
                 `guiName` = "100% Matches: Warn, if edited",
                 `guiGroup` = "Editor: QA",
                 `level` = "16",
                 `description`  = "If checked, a warning will be shown, if the user edits a 100% match",
                 `comment` = ""
                 WHERE `name` = "runtimeOptions.editor.enable100pEditWarning";
UPDATE Zf_configuration SET
                 `default` = "1",
                 `defaults` = "",
                 `guiName` = "Allow adding new whitespace",
                 `guiGroup` = "Editor: QA",
                 `level` = "16",
                 `description`  = "If enabled, the user can insert new whitespace while editing. Requires „AutoQA: Allow whitespace tag errors“ to be enabled, too.",
                 `comment` = ""
                 WHERE `name` = "runtimeOptions.segments.userCanInsertWhitespaceTags";
UPDATE Zf_configuration SET
                 `default` = "0",
                 `defaults` = "",
                 `guiName` = "AutoQA: Allow tag errors",
                 `guiGroup` = "Editor: QA",
                 `level` = "16",
                 `description`  = "If enabled the user can ignore tag validation errors. If disabled the user must correct the errors before saving the segment. Whitespace tags are configured with another config option.",
                 `comment` = ""
                 WHERE `name` = "runtimeOptions.segments.userCanIgnoreTagValidation";
UPDATE Zf_configuration SET
                 `default` = "1",
                 `defaults` = "",
                 `guiName` = "AutoQA: Allow whitespace tag errors",
                 `guiGroup` = "Editor: QA",
                 `level` = "16",
                 `description`  = "If enabled deleted / added whitespace tags are ignored in the tag validation. If disabled the user must have the same whitespace tags ins source and target.",
                 `comment` = ""
                 WHERE `name` = "runtimeOptions.segments.userCanModifyWhitespaceTags";
UPDATE Zf_configuration SET
                 `default` = "1",
                 `defaults` = "",
                 `guiName` = "Import: Ignore framing tag pairs",
                 `guiGroup` = "Editor: QA",
                 `level` = "8",
                 `description`  = "If checked, framing tags (tag pairs that surround the complete segment) are ignored on import. Does work for native file formats and standard xliff. Does not work for sdlxliff. See http://confluence.translate5.net/display/TFD/Xliff.",
                 `comment` = ""
                 WHERE `name` = "runtimeOptions.import.xlf.ignoreFramingTags";
UPDATE Zf_configuration SET
                 `default` = "1",
                 `defaults` = "",
                 `guiName` = "MQM panel active",
                 `guiGroup` = "Editor: QA",
                 `level` = "16",
                 `description`  = "If checked, the MQM quality assurance panel on the right side of the editor is visible",
                 `comment` = ""
                 WHERE `name` = "runtimeOptions.editor.enableQmSubSegments";
UPDATE Zf_configuration SET
                 `default` = "{\"critical\": \"Critical\",\"major\": \"Major\",\"minor\": \"Minor\"}",
                 `defaults` = "",
                 `guiName` = "MQM severity levels",
                 `guiGroup` = "Editor: QA",
                 `level` = "8",
                 `description`  = "Severity levels for the MQM quality assurance. The MQM issue types can be overwritten in the import zip file (please see https://confluence.translate5.net/display/BUS/ZIP+import+package+format ). Please contact translate5s developers, if this should be available as a GUI configuration option.",
                 `comment` = ""
                 WHERE `name` = "runtimeOptions.editor.qmSeverity";
UPDATE Zf_configuration SET
                 `default` = "1",
                 `defaults` = "",
                 `guiName` = "Only TermCollections assigned: Hide fuzzy match panel",
                 `guiGroup` = "Editor: QA",
                 `level` = "16",
                 `description`  = "If this is checked and only a TermCollection and no MT or TM language resource is assigned to the task, the fuzzy match panel will not be shown in translate5s editor.",
                 `comment` = ""
                 WHERE `name` = "runtimeOptions.editor.LanguageResources.disableIfOnlyTermCollection";
UPDATE Zf_configuration SET
                 `default` = "{\"8\":\"7\", \"9\":\"8\", \"10\":\"9\", \"11\":\"10\", \"12\":\"11\", \"13\":\"12\", \"14\":\"13\", \"15\":\"14\", \"16\":\"15\", \"17\":\"16\", \"18\":\"17\", \"19\":\"18\", \"20\":\"19\"}",
                 `defaults` = "",
                 `guiName` = "Pixel length restriction: Default mappings",
                 `guiGroup` = "Editor: QA",
                 `level` = "16",
                 `description`  = "Define the default pixel-widths for font-sizes, independent from the used font or character. Key is the font size and value the pixel width assumed in the GUI check.",
                 `comment` = ""
                 WHERE `name` = "runtimeOptions.lengthRestriction.pixelMapping";
UPDATE Zf_configuration SET
                 `default` = "{\"1\": \"Demo-QM-Fehler 1\", \"2\": \"Falsche Übersetzung\", \"3\": \"Terminologieproblem\", \"4\": \"Fließendes Problem\", \"5\": \"Inkonsistenz\"}",
                 `defaults` = "",
                 `guiName` = "Quality assurance options",
                 `guiGroup` = "Editor: QA",
                 `level` = "8",
                 `description`  = "Available options for the quality assurcance panel on the right side of the editor",
                 `comment` = ""
                 WHERE `name` = "runtimeOptions.segments.qualityFlags";
UPDATE Zf_configuration SET
                 `default` = "1",
                 `defaults` = "",
                 `guiName` = "Quality assurance panel active",
                 `guiGroup` = "Editor: QA",
                 `level` = "16",
                 `description`  = "If checked, the quality assurance panel on the right side of the editor is visible",
                 `comment` = ""
                 WHERE `name` = "runtimeOptions.segments.showQM";
UPDATE Zf_configuration SET
                 `default` = "[\"zh\", \"ja\", \"ko\", \"ko-KR\",\"zh-CN\",\"zh-HK\",\"zh-MO\",\"zh-SG\",\"zh-TW\",\"ja-JP\",\"th\",\"th-TH\"]",
                 `defaults` = "",
                 `guiName` = "Terminology check: Disable stemming for languages",
                 `guiGroup` = "Editor: QA",
                 `level` = "4",
                 `description`  = "For certain languages (East Asian ones) it makes no sense to use the stemmer for terminology checking. List here the rfc5646 codes of those languages, where the stemmer should not be used (use the language codes, that the languages have in translate5)",
                 `comment` = ""
                 WHERE `name` = "runtimeOptions.termTagger.targetStringMatch";
UPDATE Zf_configuration SET
                 `default` = "0",
                 `defaults` = "",
                 `guiName` = "TermTagger: Check read-only segments",
                 `guiGroup` = "Editor: QA",
                 `level` = "8",
                 `description`  = "If checked, the termTagger checks also read-only segments. Should not be activated to safe performance, if possible.",
                 `comment` = ""
                 WHERE `name` = "runtimeOptions.termTagger.tagReadonlySegments";
UPDATE Zf_configuration SET
                 `default` = "",
                 `defaults` = "",
                 `guiName` = "Custom HTML in editor (left accordion)",
                 `guiGroup` = "Editor: UI layout & more",
                 `level` = "4",
                 `description`  = "If set, another Accordion tab is included in the left part of the editor and filled with the contents of the set URL. For more info see the branding paragraph in confluence link: https://confluence.translate5.net/display/TAD/Implement+a+custom+translate5+skin",
                 `comment` = ""
                 WHERE `name` = "runtimeOptions.editor.customPanel.url";
UPDATE Zf_configuration SET
                 `default` = "",
                 `defaults` = "",
                 `guiName` = "Custom HTML in editor (upper right)",
                 `guiGroup` = "Editor: UI layout & more",
                 `level` = "4",
                 `description`  = "If set, this content is loaded in the upper right part of the editor. For moreinfo see the branding paragraph in confluence link: https://confluence.translate5.net/display/TAD/Implement+a+custom+translate5+skin",
                 `comment` = ""
                 WHERE `name` = "runtimeOptions.editor.customHtmlContainer";
UPDATE Zf_configuration SET
                 `default` = "normal",
                 `defaults` = "normal,details",
                 `guiName` = "Default editor mode",
                 `guiGroup` = "Editor: UI layout & more",
                 `level` = "16",
                 `description`  = "View mode which should be used on editor start up (if visual mode is used for the task, the default editor mode for visual is applied. It is defined in the section „Editor: Visual“).",
                 `comment` = ""
                 WHERE `name` = "runtimeOptions.editor.startViewMode";
UPDATE Zf_configuration SET
                 `default` = "{}",
                 `defaults` = "",
                 `guiName` = "Editor bottom panel default configuration",
                 `guiGroup` = "Editor: UI layout & more",
                 `level` = "4",
                 `description`  = "Default state configuration for the editor fuzzy match and concordoance search panel. If this field value is empty ({} is not an empty value!), no state will be applied/saved for this component.",
                 `comment` = ""
                 WHERE `name` = "runtimeOptions.frontend.defaultState.editor.languageResourceEditorPanel";
UPDATE Zf_configuration SET
                 `default` = "{}",
                 `defaults` = "",
                 `guiName` = "Editor left panel default configuration",
                 `guiGroup` = "Editor: UI layout & more",
                 `level` = "4",
                 `description`  = "Default state configuration for the editor west panel. If this field value is empty ({} is not an empty value!), no state will be applied/saved for this component.",
                 `comment` = ""
                 WHERE `name` = "runtimeOptions.frontend.defaultState.editor.westPanel";
UPDATE Zf_configuration SET
                 `default` = "{}",
                 `defaults` = "",
                 `guiName` = "Editor left panel file tree default configuration",
                 `guiGroup` = "Editor: UI layout & more",
                 `level` = "4",
                 `description`  = "Default state configuration for the editor west panel file tree. If this field value is empty ({} is not an empty value!), no state will be applied/saved for this component.",
                 `comment` = ""
                 WHERE `name` = "runtimeOptions.frontend.defaultState.editor.westPanelFileorderTree";
UPDATE Zf_configuration SET
                 `default` = "{}",
                 `defaults` = "",
                 `guiName` = "Editor left panel review file tree default configuration",
                 `guiGroup` = "Editor: UI layout & more",
                 `level` = "4",
                 `description`  = "Default state configuration for the editor west panel reference files tree. If this field value is empty ({} is not an empty value!), no state will be applied/saved for this component.",
                 `comment` = ""
                 WHERE `name` = "runtimeOptions.frontend.defaultState.editor.westPanelReferenceFileTree";
UPDATE Zf_configuration SET
                 `default` = "{}",
                 `defaults` = "",
                 `guiName` = "Editor right panel default configuration",
                 `guiGroup` = "Editor: UI layout & more",
                 `level` = "4",
                 `description`  = "Default state configuration for the editor east panel. If this field value is empty ({} is not an empty value!), no state will be applied/saved for this component.",
                 `comment` = ""
                 WHERE `name` = "runtimeOptions.frontend.defaultState.editor.eastPanel";
UPDATE Zf_configuration SET
                 `default` = "{}",
                 `defaults` = "",
                 `guiName` = "Editor right panel review comments area default configuration",
                 `guiGroup` = "Editor: UI layout & more",
                 `level` = "4",
                 `description`  = "Default state configuration for the editor east panel comments. If this field value is empty ({} is not an empty value!), no state will be applied/saved for this component.",
                 `comment` = ""
                 WHERE `name` = "runtimeOptions.frontend.defaultState.editor.eastPanelCommentPanel";
UPDATE Zf_configuration SET
                 `default` = "{}",
                 `defaults` = "",
                 `guiName` = "Editor right panel review segment meta data default configuration",
                 `guiGroup` = "Editor: UI layout & more",
                 `level` = "4",
                 `description`  = "Default state configuration for the editor east panel segments meta. If this field value is empty ({} is not an empty value!), no state will be applied/saved for this component.",
                 `comment` = ""
                 WHERE `name` = "runtimeOptions.frontend.defaultState.editor.eastPanelSegmentsMetapanel";
UPDATE Zf_configuration SET
                 `default` = "",
                 `defaults` = "",
                 `guiName` = "Editor segment table default configuration",
                 `guiGroup` = "Editor: UI layout & more",
                 `level` = "4",
                 `description`  = "Segment table default state configuration. When this config is empty, the task grid state will not be saved or applied. For how to config this value please visit this page: ",
                 `comment` = ""
                 WHERE `name` = "runtimeOptions.frontend.defaultState.editor.segmentsGrid";
UPDATE Zf_configuration SET
                 `default` = "",
                 `defaults` = "",
                 `guiName` = "Title for custom HTML in editor (left accordion)",
                 `guiGroup` = "Editor: UI layout & more",
                 `level` = "4",
                 `description`  = "Optional title for the additional custom panel in the left. This text is used for all GUI languages. If it should be translated, overwrite it in a XLF file in client-specific/locales",
                 `comment` = ""
                 WHERE `name` = "runtimeOptions.editor.customPanel.title";
UPDATE Zf_configuration SET
                 `default` = ",",
                 `defaults` = "",
                 `guiName` = "CSV import: delimiter",
                 `guiGroup` = "File formats",
                 `level` = "8",
                 `description`  = "The delimiter translate5 will expect to parse CSV files. If this is not present in the CSV, a CSV import will fail (Okapi bconf is not used for CSV iimport).",
                 `comment` = ""
                 WHERE `name` = "runtimeOptions.import.csv.delimiter";
UPDATE Zf_configuration SET
                 `default` = "\"",
                 `defaults` = "",
                 `guiName` = "CSV import: ecnclosure",
                 `guiGroup` = "File formats",
                 `level` = "8",
                 `description`  = "The enclosure translate5 will expect to parse CSV files. If this is not present in the CSV, a CSV import will fail (Okapi bconf is not used for CSV iimport).",
                 `comment` = ""
                 WHERE `name` = "runtimeOptions.import.csv.enclosure";
-- 
UPDATE Zf_configuration SET
                 `value` = "id",
                 `default` = "id",
                 `defaults` = "",
                 `guiName` = "CSV import: Name of ID column",
                 `guiGroup` = "File formats",
                 `level` = "8",
                 `description`  = "The name of the ID column for a CSV import. If this does not exist in the CSV, the CSV import will fail. All columns with column header different than the source text column and the ID column will be treated as target text columns (potentially many).",
                 `comment` = ""
                 WHERE `name` = "runtimeOptions.import.csv.fields.mid";
UPDATE Zf_configuration SET
                 `value` = "source",
                 `default` = "source",
                 `defaults` = "",
                 `guiName` = "CSV import: Name of source text column",
                 `guiGroup` = "File formats",
                 `level` = "8",
                 `description`  = "The name of the source text column for a CSV import. If this does not exist in the CSV, the CSV import will fail. All columns with column header different than the source text column and the ID column will be treated as target text columns (potentially many).",
                 `comment` = ""
                 WHERE `name` = "runtimeOptions.import.csv.fields.source";
UPDATE Zf_configuration SET
                 `default` = "1",
                 `defaults` = "",
                 `guiName` = "Export comments to xliff",
                 `guiGroup` = "File formats",
                 `level` = "16",
                 `description`  = "if checked, the segment comments will be exported into the exported bilingual file (if this is supported by the implementation for that file type).",
                 `comment` = ""
                 WHERE `name` = "runtimeOptions.editor.export.exportComments";
UPDATE Zf_configuration SET
                 `default` = "{\"okapi\": true}",
                 `defaults` = "",
                 `guiName` = "Original files: Attach them",
                 `guiGroup` = "File formats",
                 `level` = "8",
                 `description`  = "Attach original files as reference files for all files, that are converted by Okapi (all except bilingual file formatts and CSV)",
                 `comment` = ""
                 WHERE `name` = "runtimeOptions.import.fileconverters.attachOriginalFileAsReference";
UPDATE Zf_configuration SET
                 `default` = "0",
                 `defaults` = "",
                 `guiName` = "SDLXLIFF comments: Import them",
                 `guiGroup` = "File formats",
                 `level` = "8",
                 `description`  = "Defines if SDLXLIFF comments should be imported or they should produce an error on import. See https://confluence.translate5.net/display/TFD/SDLXLIFF.",
                 `comment` = ""
                 WHERE `name` = "runtimeOptions.import.sdlxliff.importComments";
UPDATE Zf_configuration SET
                 `default` = "1",
                 `defaults` = "",
                 `guiName` = "SDLXLIFF track changes: Apply on import",
                 `guiGroup` = "File formats",
                 `level` = "8",
                 `description`  = "Defines if SDLXLIFF change marks should be applied to and removed from the content, or if they should produce an error on import. See http://confluence.translate5.net/display/TFD/SDLXLIFF.",
                 `comment` = ""
                 WHERE `name` = "runtimeOptions.import.sdlxliff.applyChangeMarks";
UPDATE Zf_configuration SET
                 `default` = "1",
                 `defaults` = "",
                 `guiName` = "XLIFF (and others): Preserve whitespace",
                 `guiGroup` = "File formats",
                 `level` = "8",
                 `description`  = "Defines how to import whitespace in XLF files and all native file formats (since they are converted to XLIFF by Okapi). If checcked, whitespace is preserved, if not whitespace is collapsed. See http://confluence.translate5.net/display/TFD/Xliff.",
                 `comment` = ""
                 WHERE `name` = "runtimeOptions.import.xlf.preserveWhitespace";
UPDATE Zf_configuration SET
                 `default` = "70",
                 `defaults` = "",
                 `guiName` = "Google default match rate",
                 `guiGroup` = "Language resources",
                 `level` = "2",
                 `description`  = "Default fuzzy match value for translations done by Google. Used in the analysis and in the fuzzy match panel, if ModelFront is not used for risk prediction of MT.",
                 `comment` = ""
                 WHERE `name` = "runtimeOptions.LanguageResources.google.matchrate";
UPDATE Zf_configuration SET
                 `default` = "70",
                 `defaults` = "",
                 `guiName` = "MS Translator default match rate",
                 `guiGroup` = "Language resources",
                 `level` = "2",
                 `description`  = "Default fuzzy match value for translations done by MicroSoft translator. Used in the analysis and in the fuzzy match panel, if ModelFront is not used for risk prediction of MT.",
                 `comment` = ""
                 WHERE `name` = "runtimeOptions.LanguageResources.microsoft.matchrate";
UPDATE Zf_configuration SET
                 `default` = "0",
                 `defaults` = "",
                 `guiName` = "OpenTM2: Show all 100% matches",
                 `guiGroup` = "Language resources",
                 `level` = "2",
                 `description`  = "If this is not checked, for 100%-Matches that differ in the target, the target of the match with the highest match rate is shown. If the match rate is the same, the match with the newest change date is shown.If this is checked, all 100%-Matches that differ in the target are shown.",
                 `comment` = ""
                 WHERE `name` = "runtimeOptions.LanguageResources.opentm2.showMultiple100PercentMatches";
UPDATE Zf_configuration SET
                 `default` = "70",
                 `defaults` = "",
                 `guiName` = "SDL language cloud default match rate",
                 `guiGroup` = "Language resources",
                 `level` = "2",
                 `description`  = "Default fuzzy match value for translations done by SDL languagecloud. Used in the analysis and in the fuzzy match panel, if ModelFront is not used for risk prediction of MT.",
                 `comment` = ""
                 WHERE `name` = "runtimeOptions.LanguageResources.sdllanguagecloud.matchrate";
UPDATE Zf_configuration SET
                 `default` = "supersededTerm",
                 `defaults` = "",
                 `guiName` = "Term import: Map non-standard term status",
                 `guiGroup` = "Language resources",
                 `level` = "1",
                 `description`  = "Maps a term status, that is not TBX standard and comes from the import to a standard term status. Current standard term status in translate5 are: preferredTerm (GUI value „preferred“), admittedTerm (GUI value „permitted“), legalTerm (GUI value „permitted“), regulatedTerm (GUI value „permitted“), standardizedTerm (GUI value „permitted“), deprecatedTerm (GUI value „forbidden“), supersededTerm (GUI value „forbidden“). ",
                 `comment` = ""
                 WHERE `name` = "runtimeOptions.tbx.termImportMap.across_ISO_picklist_Usage.do not use";
UPDATE Zf_configuration SET
                 `default` = "supersededTerm",
                 `defaults` = "",
                 `guiName` = "Term import: Map non-standard term status",
                 `guiGroup` = "Language resources",
                 `level` = "1",
                 `description`  = "Maps a term status, that is not TBX standard and comes from the import to a standard term status. Current standard term status in translate5 are: preferredTerm (GUI value „preferred“), admittedTerm (GUI value „permitted“), legalTerm (GUI value „permitted“), regulatedTerm (GUI value „permitted“), standardizedTerm (GUI value „permitted“), deprecatedTerm (GUI value „forbidden“), supersededTerm (GUI value „forbidden“). ",
                 `comment` = ""
                 WHERE `name` = "runtimeOptions.tbx.termImportMap.across_ISO_picklist_Verwendung.Unwort";
UPDATE Zf_configuration SET
                 `default` = "supersededTerm",
                 `defaults` = "",
                 `guiName` = "Term import: Map non-standard term status",
                 `guiGroup` = "Language resources",
                 `level` = "1",
                 `description`  = "Maps a term status, that is not TBX standard and comes from the import to a standard term status. Current standard term status in translate5 are: preferredTerm (GUI value „preferred“), admittedTerm (GUI value „permitted“), legalTerm (GUI value „permitted“), regulatedTerm (GUI value „permitted“), standardizedTerm (GUI value „permitted“), deprecatedTerm (GUI value „forbidden“), supersededTerm (GUI value „forbidden“). ",
                 `comment` = ""
                 WHERE `name` = "runtimeOptions.tbx.termImportMap.across_userdef_picklist_Verwendung.Unwort";
UPDATE Zf_configuration SET
                 `default` = "finalized",
                 `defaults` = "finalized,unprocessed",
                 `guiName` = "Terminology import: Default term attributes process status",
                 `guiGroup` = "Language resources",
                 `level` = "2",
                 `description`  = "Default term and term entry attribute status for newly imported term attributes.",
                 `comment` = ""
                 WHERE `name` = "runtimeOptions.tbx.defaultTermAttributeStatus";
UPDATE Zf_configuration SET
                 `default` = "1",
                 `defaults` = "",
                 `guiName` = "PM mail address in task overview",
                 `guiGroup` = "Project and task overview",
                 `level` = "2",
                 `description`  = "If this is active, the PM name in the task overview PM column will be linked with the mail address of the PM.",
                 `comment` = ""
                 WHERE `name` = "runtimeOptions.frontend.tasklist.pmMailTo";
UPDATE Zf_configuration SET
                 `default` = "",
                 `defaults` = "",
                 `guiName` = "Task overview default configuration",
                 `guiGroup` = "Project and task overview",
                 `level` = "2",
                 `description`  = "Defines, what columns in the task overview are shown in  what order and if they are hidden or visible. For more information please see
https://confluence.translate5.net/display/CON/Configure+tabular+views+default+layout",
                 `comment` = ""
                 WHERE `name` = "runtimeOptions.frontend.defaultState.adminTaskGrid";
UPDATE Zf_configuration SET
                 `default` = "1",
                 `defaults` = "",
                 `guiName` = "OpenID Connect: Use user-info endpoint",
                 `guiGroup` = "System setup: Authentication",
                 `level` = "2",
                 `description`  = "Request the authentication provider for additional user information via user info endpoint",
                 `comment` = ""
                 WHERE `name` = "runtimeOptions.openid.requestUserInfo";
UPDATE Zf_configuration SET
                 `default` = "0",
                 `defaults` = "",
                 `guiName` = "Show OpenID configuration for default customer",
                 `guiGroup` = "System setup: Authentication",
                 `level` = "2",
                 `description`  = "If this is checked, the OpenID Connect configuration data is also shown for the default customer.",
                 `comment` = ""
                 WHERE `name` = "runtimeOptions.customers.openid.showOpenIdDefaultCustomerData";
UPDATE Zf_configuration SET
                 `default` = "en",
                 `defaults` = "",
                 `guiName` = "Application GUI fallback locale",
                 `guiGroup` = "System setup: General",
                 `level` = "2",
                 `description`  = "This is the fallback locale used for users in the application GUI. First is checked if the user has configured a locale, if not applicationLocale is checked. If that is empty the prefered browser languages are evaluated. If there is also no usable language this last fallbackLocale is used.",
                 `comment` = ""
                 WHERE `name` = "runtimeOptions.translation.fallbackLocale";
UPDATE Zf_configuration SET
                 `default` = "",
                 `defaults` = "de,en",
                 `guiName` = "Application GUI locale",
                 `guiGroup` = "System setup: General",
                 `level` = "2",
                 `description`  = "Set here a default locale for the application GUI. If empty the default locale is derived from the users browser (which is the default).",
                 `comment` = ""
                 WHERE `name` = "runtimeOptions.translation.applicationLocale";
UPDATE Zf_configuration SET
                 `default` = "100",
                 `defaults` = "",
                 `guiName` = "Auto-Delete tasks older than",
                 `guiGroup` = "System setup: General",
                 `level` = "2",
                 `description`  = "Attention: This is by default NOT active. To activate it, a workflow action needs to be configured. This is currently only possible on DB-Level. 
If the task is older than defined days, it will be automatically deleted. Older means, that it is not touched in the system for a longer time than this. Touching means at least opening the task or changing any kind of task assignments (users, language resources, etc.)",
                 `comment` = ""
                 WHERE `name` = "runtimeOptions.taskLifetimeDays";
UPDATE Zf_configuration SET
                 `default` = "[]",
                 `defaults` = "",
                 `guiName` = "BCC addresses for ALL mails",
                 `guiGroup` = "System setup: General",
                 `level` = "2",
                 `description`  = "List of e-mail addresses, that will be set to BCC for ALL e-mails translate5 sends.",
                 `comment` = ""
                 WHERE `name` = "runtimeOptions.mail.generalBcc";
UPDATE Zf_configuration SET
                 `default` = "MittagQI - Quality Informatics",
                 `defaults` = "",
                 `guiName` = "Company name",
                 `guiGroup` = "System setup: General",
                 `level` = "2",
                 `description`  = "Name of the company, that uses translate5. Is shown in E-Mails and other places",
                 `comment` = ""
                 WHERE `name` = "runtimeOptions.companyName";
UPDATE Zf_configuration SET
                 `default` = "1",
                 `defaults` = "",
                 `guiName` = "Error-logging in the browser",
                 `guiGroup` = "System setup: General",
                 `level` = "2",
                 `description`  = "If checked, error-logging in the graphical user interface is activated. Errors will be send to translate5s developers via theRootCause.io. Users can decide on every single occurence of an error, if they want to report it.",
                 `comment` = ""
                 WHERE `name` = "runtimeOptions.debug.enableJsLogger";
UPDATE Zf_configuration SET
                 `default` = "0",
                 `defaults` = "",
                 `guiName` = "Error-logging in the browser – activate video",
                 `guiGroup` = "System setup: General",
                 `level` = "2",
                 `description`  = "If checked, the error-logging in the GUI (see previous option) is extended by video recording. Videos are only kept in case of an error, that is send by the user to theRootCause.io. The user still has the option to decide, if he only wants to submit the error or if he also wants to submit the video. If a video is provided, it will be deleted, when translate5s developers did look after the error.",
                 `comment` = ""
                 WHERE `name` = "runtimeOptions.debug.enableJsLoggerVideo";
UPDATE Zf_configuration SET
                 `default` = "3",
                 `defaults` = "",
                 `guiName` = "Export: Xliff2: Max. parallel processes",
                 `guiGroup` = "System setup: General",
                 `level` = "2",
                 `description`  = "Max parallel running processes of the xliff2 export worker are allowed. Please consult translate5s team, if you change this.",
                 `comment` = ""
                 WHERE `name` = "runtimeOptions.worker.editor_Models_Export_Xliff2Worker.maxParallelWorkers";
UPDATE Zf_configuration SET
                 `default` = "127.0.0.1",
                 `defaults` = "",
                 `guiName` = "IP address allowed for cron calls",
                 `guiGroup` = "System setup: General",
                 `level` = "2",
                 `description`  = "It is recommended to call translate5s cron job mechanism every 15 min. These calls are only allowed to originate from the IP address configured here.",
                 `comment` = ""
                 WHERE `name` = "runtimeOptions.cronIP";
UPDATE Zf_configuration SET
                 `default` = "07473 / 220202",
                 `defaults` = "",
                 `guiName` = "Phone to call",
                 `guiGroup` = "System setup: General",
                 `level` = "2",
                 `description`  = "Telephone number, where you can contact the department responsible for translate5.",
                 `comment` = ""
                 WHERE `name` = "runtimeOptions.contactData.emergencyTelephoneNumber";
UPDATE Zf_configuration SET
                 `default` = "IT-Abteilung",
                 `defaults` = "",
                 `guiName` = "Responsible department",
                 `guiGroup` = "System setup: General",
                 `level` = "2",
                 `description`  = "Department that is responsible for translate5 in the company, that uses translate5.",
                 `comment` = ""
                 WHERE `name` = "runtimeOptions.contactData.emergencyContactDepartment";
UPDATE Zf_configuration SET
                 `default` = "www.translate5.net",
                 `defaults` = "",
                 `guiName` = "Server name",
                 `guiGroup` = "System setup: General",
                 `level` = "2",
                 `description`  = "Domainname under which de application is running",
                 `comment` = ""
                 WHERE `name` = "runtimeOptions.server.name";
UPDATE Zf_configuration SET
                 `default` = "http://",
                 `defaults` = "http://,https://",
                 `guiName` = "Server protocol",
                 `guiGroup` = "System setup: General",
                 `level` = "2",
                 `description`  = "Protocol of the application (http:// or https://",
                 `comment` = ""
                 WHERE `name` = "runtimeOptions.server.protocol";
UPDATE Zf_configuration SET
                 `default` = "{\"doNotShowAgain\":false,\"loaderUrl\":\"/help/{0}\"}",
                 `defaults` = "",
                 `guiName` = "Help window client overview: No auto-show",
                 `guiGroup` = "System setup: Help",
                 `level` = "2",
                 `description`  = "Help window default state configuration for the client overview panel. When this is not checked, the window will appear automatically in the user overview. A user can then mark the checkbox „Do not show again“ himself in the help window, which will be remembered for this user.",
                 `comment` = ""
                 WHERE `name` = "runtimeOptions.frontend.defaultState.helpWindow.customeroverview";
UPDATE Zf_configuration SET
                 `default` = "{\"doNotShowAgain\":false,\"loaderUrl\":\"/help/{0}\"}",
                 `defaults` = "",
                 `guiName` = "Help window editor: No auto-show",
                 `guiGroup` = "System setup: Help",
                 `level` = "2",
                 `description`  = "Help window default state configuration for the editor overview panel. When this is not checked, the window will appear automatically in the user overview. A user can then mark the checkbox „Do not show again“ himself in the help window, which will be remembered for this user.",
                 `comment` = ""
                 WHERE `name` = "runtimeOptions.frontend.defaultState.helpWindow.editor";
UPDATE Zf_configuration SET
                 `default` = "{\"doNotShowAgain\":false,\"loaderUrl\":\"/help/{0}\"}",
                 `defaults` = "",
                 `guiName` = "Help window language resources: No auto-show",
                 `guiGroup` = "System setup: Help",
                 `level` = "2",
                 `description`  = "Help window default state configuration for the language resource overview panel. When this is not checked, the window will appear automatically in the user overview. A user can then mark the checkbox „Do not show again“ himself in the help window, which will be remembered for this user.",
                 `comment` = ""
                 WHERE `name` = "runtimeOptions.frontend.defaultState.helpWindow.languageresource";
UPDATE Zf_configuration SET
                 `default` = "{\"doNotShowAgain\":false}",
                 `defaults` = "",
                 `guiName` = "Help window preferences: No auto-show",
                 `guiGroup` = "System setup: Help",
                 `level` = "2",
                 `description`  = "Help window default state configuration for the preferences section. When this is not checked, the window will appear automatically in the user overview. A user can then mark the checkbox „do not show again“ himself in the help window, which will be remembered for this user.",
                 `comment` = ""
                 WHERE `name` = "runtimeOptions.frontend.defaultState.helpWindow.preferences";
UPDATE Zf_configuration SET
                 `default` = "{\"doNotShowAgain\":false}",
                 `defaults` = "",
                 `guiName` = "Help window project overview: No auto-show",
                 `guiGroup` = "System setup: Help",
                 `level` = "2",
                 `description`  = "Help window default state configuration for the project overview panel. When this is not checked, the window will appear automatically in the user overview. A user can then mark the checkbox „do not show again“ himself in the help window, which will be remembered for this user.",
                 `comment` = ""
                 WHERE `name` = "runtimeOptions.frontend.defaultState.helpWindow.project";
UPDATE Zf_configuration SET
                 `default` = "{\"doNotShowAgain\":false,\"loaderUrl\":\"/help/{0}\"}",
                 `defaults` = "",
                 `guiName` = "Help window task overview: No auto-show",
                 `guiGroup` = "System setup: Help",
                 `level` = "2",
                 `description`  = "Help window default state configuration for the task overview panel. When this is not checked, the window will appear automatically in the user overview. A user can then mark the checkbox „Do not show again“ himself in the help window, which will be remembered for this user.",
                 `comment` = ""
                 WHERE `name` = "runtimeOptions.frontend.defaultState.helpWindow.taskoverview";
UPDATE Zf_configuration SET
                 `default` = "",
                 `defaults` = "",
                 `guiName` = "Help window URL: client overview",
                 `guiGroup` = "System setup: Help",
                 `level` = "2",
                 `description`  = "The content from the defined url will be loaded in this help page section. If emtpy, nothing is loaded and the help button will not be available.",
                 `comment` = ""
                 WHERE `name` = "runtimeOptions.frontend.helpWindow.customeroverview.loaderUrl";
UPDATE Zf_configuration SET
                 `default` = "",
                 `defaults` = "",
                 `guiName` = "Help window URL: language resource overview",
                 `guiGroup` = "System setup: Help",
                 `level` = "2",
                 `description`  = "The content from the defined url will be loaded in this help page section. If emtpy, nothing is loaded and the help button will not be available.",
                 `comment` = ""
                 WHERE `name` = "runtimeOptions.frontend.helpWindow.languageresource.loaderUrl";
UPDATE Zf_configuration SET
                 `default` = "/help/{0}",
                 `defaults` = "",
                 `guiName` = "Help window URL: preferences",
                 `guiGroup` = "System setup: Help",
                 `level` = "2",
                 `description`  = "The content from the defined url will be loaded in this help page section. If emtpy, nothing is loaded and the help button will not be available.",
                 `comment` = ""
                 WHERE `name` = "runtimeOptions.frontend.helpWindow.preferences.loaderUrl";
UPDATE Zf_configuration SET
                 `default` = "/help/{0}",
                 `defaults` = "",
                 `guiName` = "Help window URL: project overview",
                 `guiGroup` = "System setup: Help",
                 `level` = "2",
                 `description`  = "The content from the defined url will be loaded in this help page section. If emtpy, nothing is loaded and the help button will not be available.",
                 `comment` = ""
                 WHERE `name` = "runtimeOptions.frontend.helpWindow.project.loaderUrl";
UPDATE Zf_configuration SET
                 `default` = "",
                 `defaults` = "",
                 `guiName` = "Help window URL: task overview",
                 `guiGroup` = "System setup: Help",
                 `level` = "2",
                 `description`  = "The content from the defined url will be loaded in this help page section. If emtpy, nothing is loaded and the help button will not be available.",
                 `comment` = ""
                 WHERE `name` = "runtimeOptions.frontend.helpWindow.taskoverview.loaderUrl";
UPDATE Zf_configuration SET
                 `default` = "",
                 `defaults` = "",
                 `guiName` = "Help window URL: task overview",
                 `guiGroup` = "System setup: Help",
                 `level` = "2",
                 `description`  = "The content from the defined url will be loaded in this help page section. If emtpy, nothing is loaded and the help button will not be available.",
                 `comment` = ""
                 WHERE `name` = "runtimeOptions.frontend.helpWindow.editor.loaderUrl";
UPDATE Zf_configuration SET
                 `default` = "",
                 `defaults` = "",
                 `guiName` = "Help window URL: user overview",
                 `guiGroup` = "System setup: Help",
                 `level` = "2",
                 `description`  = "The content from the defined url will be loaded in this help page section. If emtpy, nothing is loaded and the help button will not be available.",
                 `comment` = ""
                 WHERE `name` = "runtimeOptions.frontend.helpWindow.useroverview.loaderUrl";
UPDATE Zf_configuration SET
                 `default` = "{\"doNotShowAgain\":false,\"loaderUrl\":\"/help/{0}\"}",
                 `defaults` = "",
                 `guiName` = "Help window user overview: No auto-show",
                 `guiGroup` = "System setup: Help",
                 `level` = "2",
                 `description`  = "Help window default state configuration for the user overview panel. When this is not checked, the window will appear automatically in the user overview. A user can then mark the checkbox „Do not show again“ himself in the help window, which will be remembered for this user.",
                 `comment` = ""
                 WHERE `name` = "runtimeOptions.frontend.defaultState.helpWindow.useroverview";
UPDATE Zf_configuration SET
                 `default` = "",
                 `defaults` = "",
                 `guiName` = "Google: API key",
                 `guiGroup` = "System setup: Language resources",
                 `level` = "2",
                 `description`  = "Api key to authenticate with google cloud translate api.",
                 `comment` = ""
                 WHERE `name` = "runtimeOptions.LanguageResources.google.apiKey";
UPDATE Zf_configuration SET
                 `default` = "",
                 `defaults` = "",
                 `guiName` = "Google: project id",
                 `guiGroup` = "System setup: Language resources",
                 `level` = "2",
                 `description`  = "Project id used by the google translate api.",
                 `comment` = ""
                 WHERE `name` = "runtimeOptions.LanguageResources.google.projectId";
UPDATE Zf_configuration SET
                 `default` = "3",
                 `defaults` = "",
                 `guiName` = "Language resource import: Max. parallel processes",
                 `guiGroup` = "System setup: Language resources",
                 `level` = "2",
                 `description`  = "Max parallel running processes of the import of language resource data (TMX or TBX, etc) are allowed. Please consult translate5s team, if you change this.",
                 `comment` = ""
                 WHERE `name` = "runtimeOptions.worker.editor_Services_ImportWorker.maxParallelWorkers";
UPDATE Zf_configuration SET
                 `default` = "[\"translate5:DyJvc57=F2\"]",
                 `defaults` = "",
                 `guiName` = "Lucy LT credentials",
                 `guiGroup` = "System setup: Language resources",
                 `level` = "2",
                 `description`  = "List of Lucy LT credentials to the Lucy LT Servers. Each server entry must have one credential entry. One credential entry looks like: \"username:password\"",
                 `comment` = ""
                 WHERE `name` = "runtimeOptions.LanguageResources.lucylt.credentials";
UPDATE Zf_configuration SET
                 `default` = "80",
                 `defaults` = "",
                 `guiName` = "Lucy LT default match rate",
                 `guiGroup` = "System setup: Language resources",
                 `level` = "2",
                 `description`  = "Default fuzzy match value for translations done by Lucy LT. Used in the analysis and in the fuzzy match panel, if ModelFront is not used for risk prediction of MT.",
                 `comment` = ""
                 WHERE `name` = "runtimeOptions.LanguageResources.lucylt.matchrate";
UPDATE Zf_configuration SET
                 `default` = "[\"https://ltxpress.lucysoftware.com/AutoTranslateRS/V1.3\"]",
                 `defaults` = "",
                 `guiName` = "Lucy LT server URL(s)",
                 `guiGroup` = "System setup: Language resources",
                 `level` = "2",
                 `description`  = "List of available Lucy LT servers",
                 `comment` = ""
                 WHERE `name` = "runtimeOptions.LanguageResources.lucylt.server";
UPDATE Zf_configuration SET
                 `default` = "",
                 `defaults` = "",
                 `guiName` = "Microsoft translator API key",
                 `guiGroup` = "System setup: Language resources",
                 `level` = "2",
                 `description`  = "Microsoft translator language resource api key. After compliting the account registration and resource configuration, get the api key from the azzure portal.",
                 `comment` = ""
                 WHERE `name` = "runtimeOptions.LanguageResources.microsoft.apiKey";
UPDATE Zf_configuration SET
                 `default` = "https://api.cognitive.microsofttranslator.com",
                 `defaults` = "",
                 `guiName` = "Microsoft translator API URL",
                 `guiGroup` = "System setup: Language resources",
                 `level` = "2",
                 `description`  = "Microsoft translator language resource api url. To be able to use microsoft translator, you should create an microsoft azure account. Create and setup and microsoft azureaccount in the following link: https://azure.microsoft.com/en-us/services/cognitive-services/translator-text-api/",
                 `comment` = ""
                 WHERE `name` = "runtimeOptions.LanguageResources.microsoft.apiUrl";
UPDATE Zf_configuration SET
                 `default` = "70",
                 `defaults` = "",
                 `guiName` = "Moses default match rate",
                 `guiGroup` = "System setup: Language resources",
                 `level` = "2",
                 `description`  = "Default fuzzy match value for translations done by Moses. Used in the analysis and in the fuzzy match panel, if ModelFront is not used for risk prediction of MT.",
                 `comment` = ""
                 WHERE `name` = "runtimeOptions.LanguageResources.moses.matchrate";
UPDATE Zf_configuration SET
                 `default` = "[]",
                 `defaults` = "",
                 `guiName` = "Moses server URL(s)",
                 `guiGroup` = "System setup: Language resources",
                 `level` = "2",
                 `description`  = "Zero, one or more URLs, where a Moses server is accessable as a language resource. Example: http://www.translate5.net:8124/RPC2",
                 `comment` = ""
                 WHERE `name` = "runtimeOptions.LanguageResources.moses.server";
UPDATE Zf_configuration SET
                 `default` = "",
                 `defaults` = "",
                 `guiName` = "OpenTM2 instance pre-fix",
                 `guiGroup` = "System setup: Language resources",
                 `level` = "2",
                 `description`  = "When using one OpenTM2 instance for multiple translate5 instances, a unique prefix for each translate5 instance must be configured to avoid filename collisions of the Memories on the OpenTM2 server.",
                 `comment` = ""
                 WHERE `name` = "runtimeOptions.LanguageResources.opentm2.tmprefix";
UPDATE Zf_configuration SET
                 `default` = "[]",
                 `defaults` = "",
                 `guiName` = "OpenTM2 server URL(s)",
                 `guiGroup` = "System setup: Language resources",
                 `level` = "2",
                 `description`  = "Zero, one or more URLs, where an OpenTM2 server is accessable as a language resource. Example: http://opentm2.translate5.net:1984/otmmemoryservice/",
                 `comment` = ""
                 WHERE `name` = "runtimeOptions.LanguageResources.opentm2.server";
UPDATE Zf_configuration SET
                 `default` = "1",
                 `defaults` = "",
                 `guiName` = "Preload fuzzy matches in advance",
                 `guiGroup` = "System setup: Language resources",
                 `level` = "2",
                 `description`  = "For how many segments starting from the current one in advance fuzzy matches are pre-loaded, so that they are immidiately available for the translator?",
                 `comment` = ""
                 WHERE `name` = "runtimeOptions.LanguageResources.preloadedTranslationSegments";
UPDATE Zf_configuration SET
                 `default` = "",
                 `defaults` = "",
                 `guiName` = "SDL language cloud API key",
                 `guiGroup` = "System setup: Language resources",
                 `level` = "2",
                 `description`  = "Api key used for authentication to the SDL language cloud api",
                 `comment` = ""
                 WHERE `name` = "runtimeOptions.LanguageResources.sdllanguagecloud.apiKey";
UPDATE Zf_configuration SET
                 `default` = "",
                 `defaults` = "",
                 `guiName` = "SDL language cloud server URL(s)",
                 `guiGroup` = "System setup: Language resources",
                 `level` = "2",
                 `description`  = "List of available SdlLanguageCloud servers",
                 `comment` = ""
                 WHERE `name` = "runtimeOptions.LanguageResources.sdllanguagecloud.server";
UPDATE Zf_configuration SET
                 `default` = "[\"http://localhost:9003\"]",
                 `defaults` = "",
                 `guiName` = "TermTagger for GUI",
                 `guiGroup` = "System setup: Language resources",
                 `level` = "2",
                 `description`  = "Refers to segments saved in the GUI. List one or multiple URLs, where termtagger-instances can be reached for checking and marked in the segments (to check, if the correct terminology is used). Translate5 does a load balancing, if more than one is configured.",
                 `comment` = ""
                 WHERE `name` = "runtimeOptions.termTagger.url.gui";
UPDATE Zf_configuration SET
                 `default` = "[\"http://localhost:9001\",\"http://localhost:9002\"]",
                 `defaults` = "",
                 `guiName` = "TermTagger for imports",
                 `guiGroup` = "System setup: Language resources",
                 `level` = "2",
                 `description`  = "Refers to import processes. List one or multiple URLs, where termtagger-instances can be reached for checking and marked in the segments (to check, if the correct terminology is used). Translate5 does a load balancing, if more than one is configured.",
                 `comment` = ""
                 WHERE `name` = "runtimeOptions.termTagger.url.import";
UPDATE Zf_configuration SET
                 `default` = "3",
                 `defaults` = "",
                 `guiName` = "Export: Max. parallel import processes",
                 `guiGroup` = "System setup: Load balancing",
                 `level` = "2",
                 `description`  = "How many parallel processes are allowed for the export. This value depends on what your hardware can serve. Please consult translate5s team, if you change this.",
                 `comment` = ""
                 WHERE `name` = "runtimeOptions.worker.editor_Models_Export_Worker.maxParallelWorkers";
UPDATE Zf_configuration SET
                 `default` = "1",
                 `defaults` = "",
                 `guiName` = "Export/Import: Excel: Max. parallel processes",
                 `guiGroup` = "System setup: Load balancing",
                 `level` = "2",
                 `description`  = "Max parallel running processes of the Excel task export and reimport are allowed. Please consult translate5s team, if you change this.",
                 `comment` = ""
                 WHERE `name` = "runtimeOptions.worker.editor_Models_Excel_Worker.maxParallelWorkers";
UPDATE Zf_configuration SET
                 `default` = "1",
                 `defaults` = "",
                 `guiName` = "Globalese: Max. parallel Globalese pre-translation processes",
                 `guiGroup` = "System setup: Load balancing",
                 `level` = "2",
                 `description`  = "This refers to the xliff file based pre-translation with Globalese – not the language resource-based one. How many parallel processes are allowed depends on Globalese capabilities.",
                 `comment` = ""
                 WHERE `name` = "runtimeOptions.worker.editor_Plugins_GlobalesePreTranslation_Worker.maxParallelWorkers";
UPDATE Zf_configuration SET
                 `default` = "1",
                 `defaults` = "",
                 `guiName` = "Import: Analysis: Max. parallel processes",
                 `guiGroup` = "System setup: Load balancing",
                 `level` = "2",
                 `description`  = "Max parallel running processes of the match analysis worker are allowed. Please consult translate5s team, if you change this.",
                 `comment` = ""
                 WHERE `name` = "runtimeOptions.worker.editor_Plugins_MatchAnalysis_Worker.maxParallelWorkers";
UPDATE Zf_configuration SET
                 `default` = "3",
                 `defaults` = "",
                 `guiName` = "Import: Analysis: Modelfront: Max. parallel workers",
                 `guiGroup` = "System setup: Load balancing",
                 `level` = "2",
                 `description`  = "Max parallel running workers of the ModelFront worker",
                 `comment` = ""
                 WHERE `name` = "runtimeOptions.worker.editor_Plugins_ModelFront_Worker.maxParallelWorkers";
UPDATE Zf_configuration SET
                 `default` = "3",
                 `defaults` = "",
                 `guiName` = "Import: File filters: Max. parallel processes",
                 `guiGroup` = "System setup: Load balancing",
                 `level` = "2",
                 `description`  = "How many parallel processes are allowed for okapi file conversion within the translate5 instance. Please consult translate5s team, if you change this.",
                 `comment` = ""
                 WHERE `name` = "runtimeOptions.worker.editor_Plugins_Okapi_Worker.maxParallelWorkers";
UPDATE Zf_configuration SET
                 `default` = "3",
                 `defaults` = "",
                 `guiName` = "Import: Max. parallel import processes",
                 `guiGroup` = "System setup: Load balancing",
                 `level` = "2",
                 `description`  = "How many parallel processes are allowed for file and segment parsing in the import. This value depends on what your hardware can serve. Please consult translate5s team, if you change this.",
                 `comment` = ""
                 WHERE `name` = "runtimeOptions.worker.editor_Models_Import_Worker.maxParallelWorkers";
UPDATE Zf_configuration SET
                 `default` = "admittedTerm",
                 `defaults` = "preferredTerm,deprecatedTerm,standardizedTerm,legalTerm,supersededTerm,admittedTerm",
                 `guiName` = "Default term status (for import)",
                 `guiGroup` = "TermPortal",
                 `level` = "2",
                 `description`  = "Default value for the term status, if in the imported file no term status is defined for a term.",
                 `comment` = ""
                 WHERE `name` = "runtimeOptions.tbx.defaultTermStatus";
UPDATE Zf_configuration SET
                 `default` = "1",
                 `defaults` = "",
                 `guiName` = "Sub-languages in search drop-down",
                 `guiGroup` = "TermPortal",
                 `level` = "2",
                 `description`  = "Are sub-languages shown and usable as selectable options in the drop-down to select the language, in which is searched?",
                 `comment` = ""
                 WHERE `name` = "runtimeOptions.TermPortal.showSubLanguages";
UPDATE Zf_configuration SET
                 `default` = "0",
                 `defaults` = "",
                 `guiName` = "Term creation: Comment mandatory",
                 `guiGroup` = "TermPortal",
                 `level` = "2",
                 `description`  = "Is a comment mandatory, when a new term is created or proposed?",
                 `comment` = ""
                 WHERE `name` = "runtimeOptions.termportal.commentAttributeMandatory";
UPDATE Zf_configuration SET
                 `default` = "[\"de-de\", \"en-gb\"]",
                 `defaults` = "",
                 `guiName` = "",
                 `guiGroup` = "TermPortal",
                 `level` = "2",
                 `description`  = "Default languages in the termportal term search",
                 `comment` = ""
                 WHERE `name` = "runtimeOptions.termportal.defaultlanguages";
UPDATE Zf_configuration SET
                 `default` = "",
                 `defaults` = "",
                 `guiName` = "User overview default configuration",
                 `guiGroup` = "User management",
                 `level` = "2",
                 `description`  = "Defines, what columns in the user overview are shown in  what order and if they are hidden or visible. For more information please see
https://confluence.translate5.net/display/CON/Configure+tabular+views+default+layout",
                 `comment` = ""
                 WHERE `name` = "runtimeOptions.frontend.defaultState.adminUserGrid";
UPDATE Zf_configuration SET
                 `default` = "0",
                 `defaults` = "",
                 `guiName` = "Anonymize users",
                 `guiGroup` = "Workflow",
                 `level` = "8",
                 `description`  = "Are user names anonymized in the workflow (for other users of the workflow)?",
                 `comment` = ""
                 WHERE `name` = "runtimeOptions.customers.anonymizeUsers";
UPDATE Zf_configuration SET
                 `default` = "open",
                 `defaults` = "open,unconfirmed",
                 `guiName` = "Initial task state",
                 `guiGroup` = "Workflow",
                 `level` = "8",
                 `description`  = "Defines the state a task should get directly after import. Possible states are: open, unconfirmed",
                 `comment` = ""
                 WHERE `name` = "runtimeOptions.import.initialTaskState";
UPDATE Zf_configuration SET
                 `default` = "cooperative",
                 `defaults` = "competitive,cooperative,simultaneous",
                 `guiName` = "Multi user task editing mode",
                 `guiGroup` = "Workflow",
                 `level` = "8",
                 `description`  = "Initial mode how the task should be used by different users. See also https://confluence.translate5.net/display/TAD/Task",
                 `comment` = ""
                 WHERE `name` = "runtimeOptions.import.initialTaskUsageMode";
UPDATE Zf_configuration SET
                 `default` = "0",
                 `defaults` = "",
                 `guiName` = "Workflow notifications: Attach XLIFF with changes",
                 `guiGroup` = "Workflow",
                 `level` = "4",
                 `description`  = "If enabled, notification e-mails with segment data get also added the changed segments as XLIFF-attachment.",
                 `comment` = ""
                 WHERE `name` = "runtimeOptions.editor.notification.enableSegmentXlfAttachment";
UPDATE Zf_configuration SET
                 `default` = "sameStepIncluded",
                 `defaults` = "allIncluded,sameStepIncluded,notIncluded",
                 `guiName` = "Workflow notifications: Include PM changes",
                 `guiGroup` = "Workflow",
                 `level` = "16",
                 `description`  = "Defines how changes of PMs should be included into the notification mails: You can choose to include all PM changes, only the PM changes that happened in the workflow step, that just had been finished or if they should not be included at all.",
                 `comment` = ""
                 WHERE `name` = "runtimeOptions.editor.notification.pmChanges";
UPDATE Zf_configuration SET
                 `default` = "[\"surName\",\"firstName\",\"login\",\"email\",\"role\",\"state\"]",
                 `defaults` = "surName,firstName,login,email,role,state",
                 `guiName` = "Workflow notifications: User listing columns",
                 `guiGroup` = "Workflow",
                 `level` = "16",
                 `description`  = "Some workflow mail notifications contain a user listing. The available columns can be configured here.",
                 `comment` = ""
                 WHERE `name` = "runtimeOptions.editor.notification.userListColumns";
UPDATE Zf_configuration SET
                 `default` = "0",
                 `defaults` = "",
                 `guiName` = "Workflow notifications: XLIFF version",
                 `guiGroup` = "Workflow",
                 `level` = "4",
                 `description`  = "If checked, if the generated XLIFF will be in XLIFF 2 format. Else XLIFF 1.2",
                 `comment` = ""
                 WHERE `name` = "runtimeOptions.editor.notification.xliff2Active";
UPDATE Zf_configuration SET
                 `default` = "0",
                 `defaults` = "",
                 `guiName` = "Only one login per user",
                 `guiGroup` = "",
                 `level` = "1",
                 `description`  = "If checked, a user can only login with one browser at the same time. Else he could login with the same username in 2 different browsers at the same time.",
                 `comment` = ""
                 WHERE `name` = "runtimeOptions.singleUserRestriction";
UPDATE Zf_configuration SET
                 `default` = "14",
                 `defaults` = "",
                 `guiName` = "",
                 `guiGroup` = "",
                 `level` = "1",
                 `description`  = "define the default lifetime in days after which unused materialized views are deleted",
                 `comment` = ""
                 WHERE `name` = "resources.db.matViewLifetime";
UPDATE Zf_configuration SET
                 `default` = "\'[^.A-Za-z0-9_!@#$%^&()+={}\\[\\]\\\',~`-]\'",
                 `defaults` = "",
                 `guiName` = "",
                 `guiGroup` = "",
                 `level` = "1",
                 `description`  = "Regulärer Ausdruck, der innerhalb einer pcre-Zeichenklasse gültig sein muss -  bei Dateiuploads werden alle anderen Zeichen aus dem Dateinamen rausgeworfen",
                 `comment` = ""
                 WHERE `name` = "runtimeOptions.defines.ALLOWED_FILENAME_CHARS";
UPDATE Zf_configuration SET
                 `default` = "\"^\\d\\d\\d\\d-[01]\\d-[0-3]\\d [0-2]\\d:[0-6]\\d:[0-6]\\d$\"",
                 `defaults` = "",
                 `guiName` = "",
                 `guiGroup` = "",
                 `level` = "1",
                 `description`  = "",
                 `comment` = ""
                 WHERE `name` = "runtimeOptions.defines.DATE_REGEX";
UPDATE Zf_configuration SET
                 `default` = "\"^[A-Za-z0-9._%+-]+@(?:[A-Za-z0-9-]+\\.)+[A-Za-z]{2,19}$\"",
                 `defaults` = "",
                 `guiName` = "",
                 `guiGroup` = "",
                 `level` = "1",
                 `description`  = "",
                 `comment` = ""
                 WHERE `name` = "runtimeOptions.defines.EMAIL_REGEX";
UPDATE Zf_configuration SET
                 `default` = "\"^(\\{){0,1}[0-9a-fA-F]{8}\\-[0-9a-fA-F]{4}\\-[0-9a-fA-F]{4}\\-[0-9a-fA-F]{4}\\-[0-9a-fA-F]{12}(\\}){0,1}$\"",
                 `defaults` = "",
                 `guiName` = "",
                 `guiGroup` = "",
                 `level` = "1",
                 `description`  = "",
                 `comment` = ""
                 WHERE `name` = "runtimeOptions.defines.GUID_REGEX";
UPDATE Zf_configuration SET
                 `default` = "\"^_[0-9a-fA-F]{8}\\-[0-9a-fA-F]{4}\\-[0-9a-fA-F]{4}\\-[0-9a-fA-F]{4}\\-[0-9a-fA-F]{12}$\"",
                 `defaults` = "",
                 `guiName` = "",
                 `guiGroup` = "",
                 `level` = "1",
                 `description`  = "",
                 `comment` = ""
                 WHERE `name` = "runtimeOptions.defines.GUID_START_UNDERSCORE_REGEX";
UPDATE Zf_configuration SET
                 `default` = "\"^([A-Za-z-]{2,3})|([A-Za-z]{2,3}-[A-Za-z]{2})$\"",
                 `defaults` = "",
                 `guiName` = "",
                 `guiGroup` = "",
                 `level` = "1",
                 `description`  = "",
                 `comment` = ""
                 WHERE `name` = "runtimeOptions.defines.ISO639_1_REGEX";
UPDATE Zf_configuration SET
                 `default` = "../data/locales",
                 `defaults` = "",
                 `guiName` = "",
                 `guiGroup` = "",
                 `level` = "1",
                 `description`  = "",
                 `comment` = ""
                 WHERE `name` = "runtimeOptions.dir.locales";
UPDATE Zf_configuration SET
                 `default` = "../data/cache",
                 `defaults` = "",
                 `guiName` = "",
                 `guiGroup` = "",
                 `level` = "1",
                 `description`  = "",
                 `comment` = ""
                 WHERE `name` = "runtimeOptions.dir.logs";
UPDATE Zf_configuration SET
                 `default` = "modules/editor/images/imageTags",
                 `defaults` = "",
                 `guiName` = "",
                 `guiGroup` = "",
                 `level` = "1",
                 `description`  = "Image Tags und Image Tags JSON Verzeichnisse: die Pfadangabe ist vom public-Verzeichnis aus zu sehen ohne beginnenden Slash (http-Pfad). Trennzeichen ist immer \'/\' (Slash).",
                 `comment` = ""
                 WHERE `name` = "runtimeOptions.dir.tagImagesBasePath";
UPDATE Zf_configuration SET
                 `default` = "../data/editorImportedTasks",
                 `defaults` = "",
                 `guiName` = "",
                 `guiGroup` = "",
                 `level` = "1",
                 `description`  = "Pfad zu einem vom WebServer beschreibbaren, über htdocs nicht erreichbaren Verzeichnis, in diesem werden die kompletten persistenten (und temporären) Daten zu einem Task gespeichert",
                 `comment` = ""
                 WHERE `name` = "runtimeOptions.dir.taskData";
UPDATE Zf_configuration SET
                 `default` = "../data/tmp",
                 `defaults` = "",
                 `guiName` = "",
                 `guiGroup` = "",
                 `level` = "1",
                 `description`  = "",
                 `comment` = ""
                 WHERE `name` = "runtimeOptions.dir.tmp";
UPDATE Zf_configuration SET
                 `default` = "",
                 `defaults` = "",
                 `guiName` = "",
                 `guiGroup` = "",
                 `level` = "1",
                 `description`  = "see editor skinning documentation",
                 `comment` = ""
                 WHERE `name` = "runtimeOptions.editor.branding";
UPDATE Zf_configuration SET
                 `default` = "Editor.view.ViewPortEditor",
                 `defaults` = "Editor.view.ViewPortEditor,Editor.view.ViewPortSingle",
                 `guiName` = "",
                 `guiGroup` = "",
                 `level` = "1",
                 `description`  = "the editor viewport is changeable, default is Editor.view.ViewPort, also available: Editor.view.ViewPortSingle",
                 `comment` = ""
                 WHERE `name` = "runtimeOptions.editor.editorViewPort";
UPDATE Zf_configuration SET
                 `default` = "\"([^\\w-])\"u",
                 `defaults` = "",
                 `guiName` = "",
                 `guiGroup` = "",
                 `level` = "1",
                 `description`  = "regex which defines non-word-characters; must include brackets () for the return of the delimiters of preg_split by PREG_SPLIT_DELIM_CAPTURE; define including delimiters and modificators",
                 `comment` = ""
                 WHERE `name` = "runtimeOptions.editor.export.wordBreakUpRegex";
UPDATE Zf_configuration SET
                 `default` = "1",
                 `defaults` = "",
                 `guiName` = "",
                 `guiGroup` = "",
                 `level` = "1",
                 `description`  = "defines if the generated xml should be additionaly stored in the task directory",
                 `comment` = ""
                 WHERE `name` = "runtimeOptions.editor.notification.saveXmlToFile";
UPDATE Zf_configuration SET
                 `default` = "modules/editor",
                 `defaults` = "",
                 `guiName` = "",
                 `guiGroup` = "",
                 `level` = "1",
                 `description`  = "path beneath APPLICATION_RUNDIR to the directory inside which the standard qmFlagXmlFile resides (must be relative from APPLICATION_RUNDIR without trailing slash)",
                 `comment` = ""
                 WHERE `name` = "runtimeOptions.editor.qmFlagXmlFileDir";
UPDATE Zf_configuration SET
                 `default` = "QM_Subsegment_Issues.xml",
                 `defaults` = "",
                 `guiName` = "",
                 `guiGroup` = "",
                 `level` = "1",
                 `description`  = "path to the XML Definition of QM Issues. Used on import.",
                 `comment` = ""
                 WHERE `name` = "runtimeOptions.editor.qmFlagXmlFileName";
UPDATE Zf_configuration SET
                 `default` = "/build/classic/theme-classic/resources/theme-classic-all.css",
                 `defaults` = "",
                 `guiName` = "",
                 `guiGroup` = "",
                 `level` = "1",
                 `description`  = "Ext JS CSS File, wird automatisch um den extJsBasepath ergänzt; alternativ: ext-all.css durch ext-all-gray.css ersetzen",
                 `comment` = ""
                 WHERE `name` = "runtimeOptions.extJs.cssFile";
UPDATE Zf_configuration SET
                 `default` = "UTF-8",
                 `defaults` = "",
                 `guiName` = "",
                 `guiGroup` = "",
                 `level` = "1",
                 `description`  = "encoding von Datei- und Verzeichnisnamen im Filesystem (muss von iconv unterstützt werden)",
                 `comment` = ""
                 WHERE `name` = "runtimeOptions.fileSystemEncoding";
UPDATE Zf_configuration SET
                 `default` = "C1D11C25-45D2-11D0-B0E2-444553540000",
                 `defaults` = "",
                 `guiName` = "",
                 `guiGroup` = "",
                 `level` = "1",
                 `description`  = "ID die einem Fork übergeben wird und verhindert, dass der Fork Zend_Session::regenerateId aufruft. Falls dieser Quellcode öffentlich wird: Diesen String bei jeder Installation individuell definieren, um Hacking vorzubeugen (beliebiger String gemäß [A-Za-z0-9])",
                 `comment` = ""
                 WHERE `name` = "runtimeOptions.forkNoRegenerateId";
UPDATE Zf_configuration SET
                 `default` = "0",
                 `defaults` = "",
                 `guiName` = "",
                 `guiGroup` = "",
                 `level` = "1",
                 `description`  = "Nur mit ViewPortSingle: Definiert die Headerhöhe in Pixeln.",
                 `comment` = ""
                 WHERE `name` = "runtimeOptions.headerOptions.height";
UPDATE Zf_configuration SET
                 `default` = "",
                 `defaults` = "",
                 `guiName` = "",
                 `guiGroup` = "",
                 `level` = "1",
                 `description`  = "Nur mit ViewPortSingle: Diese Datei wird als Header eingebunden. Die Pfadangabe ist relativ zum globalen Public Verzeichnis.",
                 `comment` = ""
                 WHERE `name` = "runtimeOptions.headerOptions.pathToHeaderFile";
UPDATE Zf_configuration SET
                 `default` = "163",
                 `defaults` = "",
                 `guiName` = "",
                 `guiGroup` = "",
                 `level` = "1",
                 `description`  = "Blau-Wert der Hintergrundfarbe",
                 `comment` = ""
                 WHERE `name` = "runtimeOptions.imageTag.backColor.B";
UPDATE Zf_configuration SET
                 `default` = "255",
                 `defaults` = "",
                 `guiName` = "",
                 `guiGroup` = "",
                 `level` = "1",
                 `description`  = "Grün-Wert der Hintergrundfarbe",
                 `comment` = ""
                 WHERE `name` = "runtimeOptions.imageTag.backColor.G";
UPDATE Zf_configuration SET
                 `default` = "57",
                 `defaults` = "",
                 `guiName` = "",
                 `guiGroup` = "",
                 `level` = "1",
                 `description`  = "Rot-Wert der Hintergrundfarbe",
                 `comment` = ""
                 WHERE `name` = "runtimeOptions.imageTag.backColor.R";
UPDATE Zf_configuration SET
                 `default` = "0",
                 `defaults` = "",
                 `guiName` = "",
                 `guiGroup` = "",
                 `level` = "1",
                 `description`  = "Blau-Wert der Schriftfarbe",
                 `comment` = ""
                 WHERE `name` = "runtimeOptions.imageTag.fontColor.B";
UPDATE Zf_configuration SET
                 `default` = "0",
                 `defaults` = "",
                 `guiName` = "",
                 `guiGroup` = "",
                 `level` = "1",
                 `description`  = "Grün-Wert der Schriftfarbe",
                 `comment` = ""
                 WHERE `name` = "runtimeOptions.imageTag.fontColor.G";
UPDATE Zf_configuration SET
                 `default` = "0",
                 `defaults` = "",
                 `guiName` = "",
                 `guiGroup` = "",
                 `level` = "1",
                 `description`  = "Rot-Wert der Schriftfarbe",
                 `comment` = ""
                 WHERE `name` = "runtimeOptions.imageTag.fontColor.R";
UPDATE Zf_configuration SET
                 `default` = "modules/editor/ThirdParty/Open_Sans/OpenSans-Regular.ttf",
                 `defaults` = "",
                 `guiName` = "",
                 `guiGroup` = "",
                 `level` = "1",
                 `description`  = "must be true type font - relative path to application folder",
                 `comment` = ""
                 WHERE `name` = "runtimeOptions.imageTag.fontFilePath";
UPDATE Zf_configuration SET
                 `default` = "9",
                 `defaults` = "",
                 `guiName` = "",
                 `guiGroup` = "",
                 `level` = "1",
                 `description`  = "",
                 `comment` = ""
                 WHERE `name` = "runtimeOptions.imageTag.fontSize";
UPDATE Zf_configuration SET
                 `default` = "14",
                 `defaults` = "",
                 `guiName` = "",
                 `guiGroup` = "",
                 `level` = "1",
                 `description`  = "",
                 `comment` = ""
                 WHERE `name` = "runtimeOptions.imageTag.height";
UPDATE Zf_configuration SET
                 `default` = "0",
                 `defaults` = "",
                 `guiName` = "",
                 `guiGroup` = "",
                 `level` = "1",
                 `description`  = "horizontalrer Startpunkt der Schrift von der linken unteren Ecke aus",
                 `comment` = ""
                 WHERE `name` = "runtimeOptions.imageTag.horizStart";
UPDATE Zf_configuration SET
                 `default` = "1",
                 `defaults` = "",
                 `guiName` = "",
                 `guiGroup` = "",
                 `level` = "1",
                 `description`  = "",
                 `comment` = ""
                 WHERE `name` = "runtimeOptions.imageTag.paddingRight";
UPDATE Zf_configuration SET
                 `default` = "11",
                 `defaults` = "",
                 `guiName` = "",
                 `guiGroup` = "",
                 `level` = "1",
                 `description`  = "vertikaler Startpunkt der Schrift von der linken unteren Ecke aus",
                 `comment` = ""
                 WHERE `name` = "runtimeOptions.imageTag.vertStart";
UPDATE Zf_configuration SET
                 `default` = "21",
                 `defaults` = "",
                 `guiName` = "",
                 `guiGroup` = "",
                 `level` = "1",
                 `description`  = "Blau-Wert der Hintergrundfarbe",
                 `comment` = ""
                 WHERE `name` = "runtimeOptions.imageTags.qmSubSegment.backColor.B";
UPDATE Zf_configuration SET
                 `default` = "130",
                 `defaults` = "",
                 `guiName` = "",
                 `guiGroup` = "",
                 `level` = "1",
                 `description`  = "Grün-Wert der Hintergrundfarbe",
                 `comment` = ""
                 WHERE `name` = "runtimeOptions.imageTags.qmSubSegment.backColor.G";
UPDATE Zf_configuration SET
                 `default` = "255",
                 `defaults` = "",
                 `guiName` = "",
                 `guiGroup` = "",
                 `level` = "1",
                 `description`  = "Rot-Wert der Hintergrundfarbe",
                 `comment` = ""
                 WHERE `name` = "runtimeOptions.imageTags.qmSubSegment.backColor.R";
UPDATE Zf_configuration SET
                 `default` = "2",
                 `defaults` = "",
                 `guiName` = "",
                 `guiGroup` = "",
                 `level` = "1",
                 `description`  = "horizontalrer Startpunkt der Schrift von der linken unteren Ecke aus",
                 `comment` = ""
                 WHERE `name` = "runtimeOptions.imageTags.qmSubSegment.horizStart";
UPDATE Zf_configuration SET
                 `default` = "3",
                 `defaults` = "",
                 `guiName` = "",
                 `guiGroup` = "",
                 `level` = "1",
                 `description`  = "",
                 `comment` = ""
                 WHERE `name` = "runtimeOptions.imageTags.qmSubSegment.paddingRight";
UPDATE Zf_configuration SET
                 `default` = "0",
                 `defaults` = "",
                 `guiName` = "",
                 `guiGroup` = "",
                 `level` = "1",
                 `description`  = "keep also the task files after an exception while importing, if false the files will be deleted",
                 `comment` = ""
                 WHERE `name` = "runtimeOptions.import.keepFilesOnError";
UPDATE Zf_configuration SET
                 `default` = "rfc5646",
                 `defaults` = "rfc5646,unix,lcid",
                 `guiName` = "",
                 `guiGroup` = "",
                 `level` = "1",
                 `description`  = "Beim Import können die zu importierenden Sprachen in verschiedenen Formaten mitgeteilt werden",
                 `comment` = ""
                 WHERE `name` = "runtimeOptions.import.languageType";
UPDATE Zf_configuration SET
                 `default` = "proofRead",
                 `defaults` = "",
                 `guiName` = "",
                 `guiGroup` = "",
                 `level` = "1",
                 `description`  = "",
                 `comment` = ""
                 WHERE `name` = "runtimeOptions.import.proofReadDirectory";
UPDATE Zf_configuration SET
                 `default` = "referenceFiles",
                 `defaults` = "",
                 `guiName` = "",
                 `guiGroup` = "",
                 `level` = "1",
                 `description`  = "Verzeichnisnamen unter welchem innerhalb des Import Ordners die Referenz Dateien gesucht werden soll",
                 `comment` = ""
                 WHERE `name` = "runtimeOptions.import.referenceDirectory";
UPDATE Zf_configuration SET
                 `default` = "relais",
                 `defaults` = "",
                 `guiName` = "",
                 `guiGroup` = "",
                 `level` = "1",
                 `description`  = "Relaissprachen Steuerung: Befinden sich im ImportRoot zwei Verzeichnisse mit den folgenden Namen, so wird zu dem Projekt eine Relaissprache aus den Daten im relaisDirectory importiert. Die Inhalte in relais und proofRead müssen strukturell identisch sein",
                 `comment` = ""
                 WHERE `name` = "runtimeOptions.import.relaisDirectory";
UPDATE Zf_configuration SET
                 `default` = "1",
                 `defaults` = "",
                 `guiName` = "",
                 `guiGroup` = "",
                 `level` = "1",
                 `description`  = "gibt an, ob bei fehlenden Relaisinformationen eine Fehlermeldung ins Log geschrieben werden soll",
                 `comment` = ""
                 WHERE `name` = "runtimeOptions.import.reportOnNoRelaisFile";
UPDATE Zf_configuration SET
                 `default` = "editor_Workflow_Default",
                 `defaults` = "",
                 `guiName` = "",
                 `guiGroup` = "",
                 `level` = "1",
                 `description`  = "",
                 `comment` = ""
                 WHERE `name` = "runtimeOptions.import.taskWorkflow";
UPDATE Zf_configuration SET
                 `default` = "/login/logout",
                 `defaults` = "",
                 `guiName` = "",
                 `guiGroup` = "",
                 `level` = "1",
                 `description`  = "http-orientierte URL auf die umgelenkt wird, wenn REST ein 401 Unauthorized wirft",
                 `comment` = ""
                 WHERE `name` = "runtimeOptions.loginUrl";
UPDATE Zf_configuration SET
                 `default` = "1.0",
                 `defaults` = "",
                 `guiName` = "",
                 `guiGroup` = "",
                 `level` = "1",
                 `description`  = "Faktor um die Dauer der eingeblendeten Nachrichten zu beeinflussen (Dezimalzeichen = Punkt!)",
                 `comment` = ""
                 WHERE `name` = "runtimeOptions.messageBox.delayFactor";
UPDATE Zf_configuration SET
                 `default` = "[\"css/editorAdditions.css?v=1\"]",
                 `defaults` = "",
                 `guiName` = "",
                 `guiGroup` = "",
                 `level` = "1",
                 `description`  = "CSS Dateien welche zusätzlich eingebunden werden sollen. Pfad relativ zum Web-Root der Anwendung. Per Default wird das CSS zur Anzeige des Translate5 Logos eingebunden.",
                 `comment` = ""
                 WHERE `name` = "runtimeOptions.publicAdditions.css";
UPDATE Zf_configuration SET
                 `default` = "/images",
                 `defaults` = "",
                 `guiName` = "",
                 `guiGroup` = "",
                 `level` = "1",
                 `description`  = "http-orientierter Pfad zum image-Verzeichnis",
                 `comment` = ""
                 WHERE `name` = "runtimeOptions.server.pathToIMAGES";
UPDATE Zf_configuration SET
                 `default` = "/js",
                 `defaults` = "",
                 `guiName` = "",
                 `guiGroup` = "",
                 `level` = "1",
                 `description`  = "http-orientierter Pfad zum js-Verzeichnis",
                 `comment` = ""
                 WHERE `name` = "runtimeOptions.server.pathToJsDir";
UPDATE Zf_configuration SET
                 `default` = "0",
                 `defaults` = "",
                 `guiName` = "",
                 `guiGroup` = "",
                 `level` = "1",
                 `description`  = "Enables the TermTagger to be verbose",
                 `comment` = ""
                 WHERE `name` = "runtimeOptions.termTagger.debug";
UPDATE Zf_configuration SET
                 `default` = "0",
                 `defaults` = "",
                 `guiName` = "",
                 `guiGroup` = "",
                 `level` = "1",
                 `description`  = "Enables the fuzzy mode",
                 `comment` = ""
                 WHERE `name` = "runtimeOptions.termTagger.fuzzy";
UPDATE Zf_configuration SET
                 `default` = "70",
                 `defaults` = "",
                 `guiName` = "",
                 `guiGroup` = "",
                 `level` = "1",
                 `description`  = "The fuzzy percentage as integer, from 0 to 100",
                 `comment` = ""
                 WHERE `name` = "runtimeOptions.termTagger.fuzzyPercent";
UPDATE Zf_configuration SET
                 `default` = "2",
                 `defaults` = "",
                 `guiName` = "",
                 `guiGroup` = "",
                 `level` = "1",
                 `description`  = "max. word count for fuzzy search",
                 `comment` = ""
                 WHERE `name` = "runtimeOptions.termTagger.maxWordLengthSearch";
UPDATE Zf_configuration SET
                 `default` = "2",
                 `defaults` = "",
                 `guiName` = "",
                 `guiGroup` = "",
                 `level` = "1",
                 `description`  = "min. number of chars at the beginning of a compared word in the text, which have to be identical to be matched in a fuzzy search",
                 `comment` = ""
                 WHERE `name` = "runtimeOptions.termTagger.minFuzzyStartLength";
UPDATE Zf_configuration SET
                 `default` = "5",
                 `defaults` = "",
                 `guiName` = "",
                 `guiGroup` = "",
                 `level` = "1",
                 `description`  = "min. char count for words in the text compared in fuzzy search",
                 `comment` = ""
                 WHERE `name` = "runtimeOptions.termTagger.minFuzzyStringLength";
UPDATE Zf_configuration SET
                 `default` = "1",
                 `defaults` = "",
                 `guiName` = "",
                 `guiGroup` = "",
                 `level` = "1",
                 `description`  = "Enables the stemmer",
                 `comment` = ""
                 WHERE `name` = "runtimeOptions.termTagger.stemmed";
UPDATE Zf_configuration SET
                 `default` = "de",
                 `defaults` = "",
                 `guiName` = "",
                 `guiGroup` = "",
                 `level` = "1",
                 `description`  = "should be the default-locale in translation-setup, if no target locale is set",
                 `comment` = ""
                 WHERE `name` = "runtimeOptions.translation.sourceCodeLocale";
UPDATE Zf_configuration SET
                 `default` = "ha",
                 `defaults` = "",
                 `guiName` = "",
                 `guiGroup` = "",
                 `level` = "1",
                 `description`  = "setze auf Hausa als eine Sprache, die wohl nicht als Oberflächensprache vorkommen wird. So kann auch das deutsche mittels xliff-Datei überschrieben werden und die in die Quelldateien einprogrammierten Werte müssen nicht geändert werden",
                 `comment` = ""
                 WHERE `name` = "runtimeOptions.translation.sourceLocale";
UPDATE Zf_configuration SET
                 `default` = "0",
                 `defaults` = "",
                 `guiName` = "",
                 `guiGroup` = "",
                 `level` = "1",
                 `description`  = "If true the column labels are getting an anonymous column name.",
                 `comment` = ""
                 WHERE `name` = "runtimeOptions.workflow.default.anonymousColumns";
UPDATE Zf_configuration SET
                 `default` = "show",
                 `defaults` = "show,hide,disable",
                 `guiName` = "",
                 `guiGroup` = "",
                 `level` = "1",
                 `description`  = "visiblity of non-editable targetcolumn(s): For \"show\" or \"hide\" the user can change the visibility of the columns in the usual way in the editor. If \"disable\" is selected, the user has no access at all to the non-editable columns.",
                 `comment` = ""
                 WHERE `name` = "runtimeOptions.workflow.default.visibility";
UPDATE Zf_configuration SET
                 `default` = "1",
                 `defaults` = "",
                 `guiName` = "",
                 `guiGroup` = "",
                 `level` = "1",
                 `description`  = "If true the column labels are getting an anonymous column name.",
                 `comment` = ""
                 WHERE `name` = "runtimeOptions.workflow.dummy.anonymousColumns";
UPDATE Zf_configuration SET
                 `default` = "show",
                 `defaults` = "show,hide,disable",
                 `guiName` = "",
                 `guiGroup` = "",
                 `level` = "1",
                 `description`  = "visiblity of non-editable targetcolumn(s): For \"show\" or \"hide\" the user can change the visibility of the columns in the usual way in the editor. If \"disable\" is selected, the user has no access at all to the non-editable columns.",
                 `comment` = ""
                 WHERE `name` = "runtimeOptions.workflow.dummy.visibility";
UPDATE Zf_configuration SET
                 `default` = "1",
                 `defaults` = "",
                 `guiName` = "",
                 `guiGroup` = "",
                 `level` = "1",
                 `description`  = "If true the column labels are getting an anonymous column name.",
                 `comment` = ""
                 WHERE `name` = "runtimeOptions.workflow.ranking.anonymousColumns";
UPDATE Zf_configuration SET
                 `default` = "disable",
                 `defaults` = "show,hide,disable",
                 `guiName` = "",
                 `guiGroup` = "",
                 `level` = "1",
                 `description`  = "visiblity of non-editable targetcolumn(s): For \"show\" or \"hide\" the user can change the visibility of the columns in the usual way in the editor. If \"disable\" is selected, the user has no access at all to the non-editable columns.",
                 `comment` = ""
                 WHERE `name` = "runtimeOptions.workflow.ranking.visibility";
UPDATE Zf_configuration SET
                 `default` = "editor_Workflow_Default",
                 `defaults` = "",
                 `guiName` = "",
                 `guiGroup` = "",
                 `level` = "1",
                 `description`  = "array with all available workflow classes for this installations",
                 `comment` = ""
                 WHERE `name` = "runtimeOptions.workflows.0";
UPDATE Zf_configuration SET
                 `default` = "/css/translate5.css?v=2",
                 `defaults` = "",
                 `guiName` = "",
                 `guiGroup` = "",
                 `level` = "1",
                 `description`  = "Pfad zu einzelner Datei unterhalb des public-Verzeichnisses mit beginnendem Slash",
                 `comment` = ""
                 WHERE `name` = "runtimeOptions.server.pathToCSS";
UPDATE Zf_configuration SET
                 `default` = "[]",
                 `defaults` = "",
                 `guiName` = "",
                 `guiGroup` = "",
                 `level` = "1",
                 `description`  = "list of menu-entries for the logged out status of translate5. Other view scripts will lead to 404 in logged out status, even if they exist",
                 `comment` = ""
                 WHERE `name` = "runtimeOptions.content.mainMenu";
UPDATE Zf_configuration SET
                 `default` = "",
                 `defaults` = "",
                 `guiName` = "",
                 `guiGroup` = "",
                 `level` = "1",
                 `description`  = "If the here defined string is found in the column name, the column is to be considered as a meta column. Also the string will be removed in frontend!",
                 `comment` = ""
                 WHERE `name` = "runtimeOptions.segments.fieldMetaIdentifier";
UPDATE Zf_configuration SET
                 `default` = "8.6",
                 `defaults` = "NULL",
                 `guiName` = "",
                 `guiGroup` = "",
                 `level` = "1",
                 `description`  = "factor which is used to calculate the column width from the max chars of a column, if it can be smaller than maxWidth",
                 `comment` = ""
                 WHERE `name` = "runtimeOptions.editor.columns.widthFactor";
UPDATE Zf_configuration SET
                 `default` = "9",
                 `defaults` = "NULL",
                 `guiName` = "",
                 `guiGroup` = "",
                 `level` = "1",
                 `description`  = "factor which is used to calculate the column width from the chars of a column-header, if the otherwise calculated width would be to small for the header",
                 `comment` = ""
                 WHERE `name` = "runtimeOptions.editor.columns.widthFactorHeader";
UPDATE Zf_configuration SET
                 `default` = "1.9",
                 `defaults` = "NULL",
                 `guiName` = "",
                 `guiGroup` = "",
                 `level` = "1",
                 `description`  = "factor which is used to calculate the column width for the ergonomic mode from the width which is set for the editing mode, if it is smaller than the maxWidth ",
                 `comment` = ""
                 WHERE `name` = "runtimeOptions.editor.columns.widthFactorErgonomic";
UPDATE Zf_configuration SET
                 `default` = "250",
                 `defaults` = "NULL",
                 `guiName` = "",
                 `guiGroup` = "",
                 `level` = "1",
                 `description`  = "default width for text contents columns in the editor in pixel. If column needs less space, this is adjusted automatically",
                 `comment` = ""
                 WHERE `name` = "runtimeOptions.editor.columns.maxWidth";
UPDATE Zf_configuration SET
                 `default` = "20",
                 `defaults` = "",
                 `guiName` = "",
                 `guiGroup` = "",
                 `level` = "1",
                 `description`  = "The here configured value is used as padding in pixels to add to a column width, if the column is editable. It depends on the editable icon width.",
                 `comment` = ""
                 WHERE `name` = "runtimeOptions.editor.columns.widthOffsetEditable";
UPDATE Zf_configuration SET
                 `default` = "0",
                 `defaults` = "",
                 `guiName` = "",
                 `guiGroup` = "",
                 `level` = "1",
                 `description`  = "This flag disables the application to send E-Mails.",
                 `comment` = ""
                 WHERE `name` = "runtimeOptions.sendMailDisabled";
UPDATE Zf_configuration SET
                 `default` = "de",
                 `defaults` = "",
                 `guiName` = "",
                 `guiGroup` = "",
                 `level` = "1",
                 `description`  = "The default locale to be used when using users with invalid stored locale",
                 `comment` = ""
                 WHERE `name` = "runtimeOptions.defaultLanguage";
UPDATE Zf_configuration SET
                 `default` = "",
                 `defaults` = "",
                 `guiName` = "",
                 `guiGroup` = "",
                 `level` = "1",
                 `description`  = "If empty defaults to \"runtimeOptions.server.protocol\" and \"runtimeOptions.server.name\". This config allows to access the local worker API through a different URL as the public one. Format of this configuration value: SCHEME://HOST:PORT",
                 `comment` = ""
                 WHERE `name` = "runtimeOptions.worker.server";
UPDATE Zf_configuration SET
                 `default` = "1",
                 `defaults` = "NULL",
                 `guiName` = "",
                 `guiGroup` = "",
                 `level` = "1",
                 `description`  = "Setting this to 0 switches off the termTagger for the GUI.",
                 `comment` = ""
                 WHERE `name` = "runtimeOptions.termTagger.switchOn.GUI";
UPDATE Zf_configuration SET
                 `default` = "1",
                 `defaults` = "NULL",
                 `guiName` = "",
                 `guiGroup` = "",
                 `level` = "1",
                 `description`  = "Setting this to 0 switches off the termTagger for the import.",
                 `comment` = ""
                 WHERE `name` = "runtimeOptions.termTagger.switchOn.import";
UPDATE Zf_configuration SET
                 `default` = "1",
                 `defaults` = "",
                 `guiName` = "",
                 `guiGroup` = "",
                 `level` = "1",
                 `description`  = "defines if the generated xml should also contain an alt trans field with a diff like content of the segment.",
                 `comment` = ""
                 WHERE `name` = "runtimeOptions.editor.notification.includeDiff";
UPDATE Zf_configuration SET
                 `default` = "1",
                 `defaults` = "",
                 `guiName` = "",
                 `guiGroup` = "",
                 `level` = "1",
                 `description`  = "Max parallel running workers of the export completed notification worker.",
                 `comment` = ""
                 WHERE `name` = "runtimeOptions.worker.editor_Models_Export_ExportedWorker.maxParallelWorkers";
UPDATE Zf_configuration SET
                 `default` = "1",
                 `defaults` = "",
                 `guiName` = "",
                 `guiGroup` = "",
                 `level` = "1",
                 `description`  = "Max parallel running workers of the Import completed notification worker",
                 `comment` = ""
                 WHERE `name` = "runtimeOptions.worker.editor_Models_Import_Worker_SetTaskToOpen.maxParallelWorkers";
UPDATE Zf_configuration SET
                 `default` = "1",
                 `defaults` = "",
                 `guiName` = "",
                 `guiGroup` = "",
                 `level` = "1",
                 `description`  = "Max parallel running workers of the MtComparEval communication worker",
                 `comment` = ""
                 WHERE `name` = "runtimeOptions.worker.editor_Plugins_MtComparEval_Worker.maxParallelWorkers";
UPDATE Zf_configuration SET
                 `default` = "1",
                 `defaults` = "",
                 `guiName` = "",
                 `guiGroup` = "",
                 `level` = "1",
                 `description`  = "Max parallel running workers of MtComparEval check state worker",
                 `comment` = ""
                 WHERE `name` = "runtimeOptions.worker.editor_Plugins_MtComparEval_CheckStateWorker.maxParallelWorkers";
UPDATE Zf_configuration SET
                 `default` = "1",
                 `defaults` = "",
                 `guiName` = "",
                 `guiGroup` = "",
                 `level` = "1",
                 `description`  = "Max parallel running workers of the LockSegmentsBasedOnConfig plugin worker",
                 `comment` = ""
                 WHERE `name` = "runtimeOptions.worker.editor_Plugins_LockSegmentsBasedOnConfig_Worker.maxParallelWorkers";
UPDATE Zf_configuration SET
                 `default` = "3",
                 `defaults` = "",
                 `guiName` = "",
                 `guiGroup` = "",
                 `level` = "1",
                 `description`  = "Max parallel running workers of the SegmentStatistics creation worker",
                 `comment` = ""
                 WHERE `name` = "runtimeOptions.worker.editor_Plugins_SegmentStatistics_Worker.maxParallelWorkers";
UPDATE Zf_configuration SET
                 `default` = "3",
                 `defaults` = "",
                 `guiName` = "",
                 `guiGroup` = "",
                 `level` = "1",
                 `description`  = "Max parallel running workers of the SegmentStatistics writer worker",
                 `comment` = ""
                 WHERE `name` = "runtimeOptions.worker.editor_Plugins_SegmentStatistics_WriteStatisticsWorker.maxParallelWorkers";
UPDATE Zf_configuration SET
                 `default` = "1",
                 `defaults` = "",
                 `guiName` = "",
                 `guiGroup` = "",
                 `level` = "1",
                 `description`  = "Max parallel running workers of the NoMissingTargetTerminology plugin worker",
                 `comment` = ""
                 WHERE `name` = "runtimeOptions.worker.editor_Plugins_NoMissingTargetTerminology_Worker.maxParallelWorkers";
UPDATE Zf_configuration SET
                 `default` = "1",
                 `defaults` = "",
                 `guiName` = "",
                 `guiGroup` = "",
                 `level` = "1",
                 `description`  = "How many parallel processes ",
                 `comment` = ""
                 WHERE `name` = "runtimeOptions.worker.editor_Plugins_TermTagger_Worker_TermTagger.maxParallelWorkers";
UPDATE Zf_configuration SET
                 `default` = "1",
                 `defaults` = "",
                 `guiName` = "",
                 `guiGroup` = "",
                 `level` = "1",
                 `description`  = "Max parallel running workers of the termTagger import worker",
                 `comment` = ""
                 WHERE `name` = "runtimeOptions.worker.editor_Plugins_TermTagger_Worker_TermTaggerImport.maxParallelWorkers";
UPDATE Zf_configuration SET
                 `default` = "1",
                 `defaults` = "",
                 `guiName` = "",
                 `guiGroup` = "",
                 `level` = "1",
                 `description`  = "Max parallel running workers of the generic callback worker",
                 `comment` = ""
                 WHERE `name` = "runtimeOptions.worker.ZfExtended_Worker_Callback.maxParallelWorkers";
UPDATE Zf_configuration SET
                 `default` = "/ext-6.0.0",
                 `defaults` = "",
                 `guiName` = "",
                 `guiGroup` = "",
                 `level` = "1",
                 `description`  = "Ext JS Base Verzeichnis",
                 `comment` = ""
                 WHERE `name` = "runtimeOptions.extJs.basepath.600";
UPDATE Zf_configuration SET
                 `default` = "1",
                 `defaults` = "",
                 `guiName` = "",
                 `guiGroup` = "",
                 `level` = "1",
                 `description`  = "If checked, translate5 shows a message if the used browser is not supported.",
                 `comment` = ""
                 WHERE `name` = "runtimeOptions.showSupportedBrowsersMsg";
UPDATE Zf_configuration SET
                 `default` = "1",
                 `defaults` = "",
                 `guiName` = "",
                 `guiGroup` = "",
                 `level` = "1",
                 `description`  = "If enabled, shows IE users an advice to use a more performant browser.",
                 `comment` = ""
                 WHERE `name` = "runtimeOptions.browserAdvice";
UPDATE Zf_configuration SET
                 `default` = "[\"IGNORE_TAGS\",\"NORMALIZE_ENTITIES\"]",
                 `defaults` = "IGNORE_TAGS,NORMALIZE_ENTITIES",
                 `guiName` = "",
                 `guiGroup` = "",
                 `level` = "1",
                 `description`  = "Flag list how import source and relais source should be compared on relais import. IGNORE_TAGS: if given ignore all tags; NORMALIZE_ENTITIES: try to convert back all HTML entities into applicable characters for comparison.",
                 `comment` = ""
                 WHERE `name` = "runtimeOptions.import.relaisCompareMode";
UPDATE Zf_configuration SET
                 `default` = "/ext-6.2.0",
                 `defaults` = "",
                 `guiName` = "",
                 `guiGroup` = "",
                 `level` = "1",
                 `description`  = "Ext JS Base Verzeichnis",
                 `comment` = ""
                 WHERE `name` = "runtimeOptions.extJs.basepath.620";
UPDATE Zf_configuration SET
                 `default` = "disabled",
                 `defaults` = "disabled,dynamic,static",
                 `guiName` = "",
                 `guiGroup` = "",
                 `level` = "1",
                 `description`  = "Enables and configures the ability to login via a hash value. In dynamic mode the hash changes after each usage, in static mode the hash remains the same (insecure!).",
                 `comment` = ""
                 WHERE `name` = "runtimeOptions.hashAuthentication";
UPDATE Zf_configuration SET
                 `default` = "1",
                 `defaults` = "",
                 `guiName` = "",
                 `guiGroup` = "",
                 `level` = "1",
                 `description`  = "Max parallel running workers of the MatchResource ReImport worker",
                 `comment` = ""
                 WHERE `name` = "runtimeOptions.worker.editor_Models_LanguageResources_Worker.maxParallelWorkers";
UPDATE Zf_configuration SET
                 `default` = "3",
                 `defaults` = "",
                 `guiName` = "",
                 `guiGroup` = "",
                 `level` = "1",
                 `description`  = "Max parallel running import filetree workers",
                 `comment` = ""
                 WHERE `name` = "runtimeOptions.worker.editor_Models_Import_Worker_FileTree.maxParallelWorkers";
UPDATE Zf_configuration SET
                 `default` = "3",
                 `defaults` = "",
                 `guiName` = "",
                 `guiGroup` = "",
                 `level` = "1",
                 `description`  = "Max parallel running import reference filetree workers",
                 `comment` = ""
                 WHERE `name` = "runtimeOptions.worker.editor_Models_Import_Worker_ReferenceFileTree.maxParallelWorkers";
UPDATE Zf_configuration SET
                 `default` = "10",
                 `defaults` = "",
                 `guiName` = "",
                 `guiGroup` = "",
                 `level` = "1",
                 `description`  = "The maximum count of the search results in the autocomplete",
                 `comment` = ""
                 WHERE `name` = "runtimeOptions.termportal.searchTermsCount";
UPDATE Zf_configuration SET
                 `default` = "1",
                 `defaults` = "",
                 `guiName` = "",
                 `guiGroup` = "",
                 `level` = "1",
                 `description`  = "Max parallel running workers of the VisualReview-PdfToHtmlWorker worker",
                 `comment` = ""
                 WHERE `name` = "runtimeOptions.worker.editor_Plugins_VisualReview_PdfToHtmlWorker.maxParallelWorkers";
UPDATE Zf_configuration SET
                 `default` = "1",
                 `defaults` = "",
                 `guiName` = "",
                 `guiGroup` = "",
                 `level` = "1",
                 `description`  = "Max parallel running workers of the VisualReview-SegmentationWorker worker",
                 `comment` = ""
                 WHERE `name` = "runtimeOptions.worker.editor_Plugins_VisualReview_SegmentationWorker.maxParallelWorkers";
UPDATE Zf_configuration SET
                 `default` = "request",
                 `defaults` = "request,cron",
                 `guiName` = "",
                 `guiGroup` = "",
                 `level` = "1",
                 `description`  = "Defines how garbage collection should be triggerd: on each request in a specific time frame, cron via cronjob URL /editor/cron/periodical. Calling the cron URL once reconfigures the application to use cron based garbage collection.",
                 `comment` = ""
                 WHERE `name` = "runtimeOptions.garbageCollector.invocation";
UPDATE Zf_configuration SET
                 `default` = "preferred",
                 `defaults` = "preferred,permitted,forbidden",
                 `guiName` = "",
                 `guiGroup` = "",
                 `level` = "1",
                 `description`  = "Defines how the Term Status should be visualized in the Frontend",
                 `comment` = ""
                 WHERE `name` = "runtimeOptions.tbx.termLabelMap.preferredTerm";
UPDATE Zf_configuration SET
                 `default` = "permitted",
                 `defaults` = "preferred,permitted,forbidden",
                 `guiName` = "",
                 `guiGroup` = "",
                 `level` = "1",
                 `description`  = "Defines how the Term Status should be visualized in the Frontend",
                 `comment` = ""
                 WHERE `name` = "runtimeOptions.tbx.termLabelMap.admittedTerm";
UPDATE Zf_configuration SET
                 `default` = "permitted",
                 `defaults` = "preferred,permitted,forbidden",
                 `guiName` = "",
                 `guiGroup` = "",
                 `level` = "1",
                 `description`  = "Defines how the Term Status should be visualized in the Frontend",
                 `comment` = ""
                 WHERE `name` = "runtimeOptions.tbx.termLabelMap.legalTerm";
UPDATE Zf_configuration SET
                 `default` = "permitted",
                 `defaults` = "preferred,permitted,forbidden",
                 `guiName` = "",
                 `guiGroup` = "",
                 `level` = "1",
                 `description`  = "Defines how the Term Status should be visualized in the Frontend",
                 `comment` = ""
                 WHERE `name` = "runtimeOptions.tbx.termLabelMap.regulatedTerm";
UPDATE Zf_configuration SET
                 `default` = "permitted",
                 `defaults` = "preferred,permitted,forbidden",
                 `guiName` = "",
                 `guiGroup` = "",
                 `level` = "1",
                 `description`  = "Defines how the Term Status should be visualized in the Frontend",
                 `comment` = ""
                 WHERE `name` = "runtimeOptions.tbx.termLabelMap.standardizedTerm";
UPDATE Zf_configuration SET
                 `default` = "forbidden",
                 `defaults` = "preferred,permitted,forbidden",
                 `guiName` = "",
                 `guiGroup` = "",
                 `level` = "1",
                 `description`  = "Defines how the Term Status should be visualized in the Frontend",
                 `comment` = ""
                 WHERE `name` = "runtimeOptions.tbx.termLabelMap.deprecatedTerm";
UPDATE Zf_configuration SET
                 `default` = "forbidden",
                 `defaults` = "preferred,permitted,forbidden",
                 `guiName` = "",
                 `guiGroup` = "",
                 `level` = "1",
                 `description`  = "Defines how the Term Status should be visualized in the Frontend",
                 `comment` = ""
                 WHERE `name` = "runtimeOptions.tbx.termLabelMap.supersededTerm";
UPDATE Zf_configuration SET
                 `default` = "",
                 `defaults` = "",
                 `guiName` = "",
                 `guiGroup` = "",
                 `level` = "1",
                 `description`  = "Available file types by extension per engine type. The engine type is defined by source rcf5646,target rcf5646. ex: \"en-ge,en-us\"",
                 `comment` = ""
                 WHERE `name` = "runtimeOptions.LanguageResources.fileExtension";
UPDATE Zf_configuration SET
                 `default` = "[]",
                 `defaults` = "",
                 `guiName` = "",
                 `guiGroup` = "",
                 `level` = "1",
                 `description`  = "Maximum character per language resource allowed for search. The configuration key is the language resource id, and the value is the character limit. Ex: {{\"1\": 100},{\"2\": 300}}",
                 `comment` = ""
                 WHERE `name` = "runtimeOptions.LanguageResources.searchCharacterLimit";
UPDATE Zf_configuration SET
                 `default` = "1",
                 `defaults` = "",
                 `guiName` = "",
                 `guiGroup` = "",
                 `level` = "1",
                 `description`  = "If enabled the session of the user is tried to be destroyed when the application window is closed.",
                 `comment` = ""
                 WHERE `name` = "runtimeOptions.logoutOnWindowClose";
UPDATE Zf_configuration SET
                 `default` = "https://confluence.translate5.net/display/TAD/EventCodes#EventCodes-{0}",
                 `defaults` = "",
                 `guiName` = "",
                 `guiGroup` = "",
                 `level` = "1",
                 `description`  = "Url for information to the error codes. The placeholder \"{0}\" will be replaced by the error code.",
                 `comment` = ""
                 WHERE `name` = "runtimeOptions.errorCodesUrl";
UPDATE Zf_configuration SET
                 `default` = "3",
                 `defaults` = "",
                 `guiName` = "",
                 `guiGroup` = "",
                 `level` = "1",
                 `description`  = "Max parallel running processes of the are NEC-TM catagories (aka tags) sync are allowed. Please consult translate5s team, if you change this.",
                 `comment` = ""
                 WHERE `name` = "runtimeOptions.worker.editor_Plugins_NecTm_Worker.maxParallelWorkers";
UPDATE Zf_configuration SET
                 `default` = "1",
                 `defaults` = "",
                 `guiName` = "",
                 `guiGroup` = "",
                 `level` = "1",
                 `description`  = "0 if the close button in the segments grid header should be shown (only senseful in editor only usage).",
                 `comment` = ""
                 WHERE `name` = "runtimeOptions.editor.toolbar.hideCloseButton";
UPDATE Zf_configuration SET
                 `default` = "0",
                 `defaults` = "",
                 `guiName` = "",
                 `guiGroup` = "",
                 `level` = "1",
                 `description`  = "1 if the leave task button should be hidden in the segments grid header.",
                 `comment` = ""
                 WHERE `name` = "runtimeOptions.editor.toolbar.hideLeaveTaskButton";
UPDATE Zf_configuration SET
                 `default` = "",
                 `defaults` = "",
                 `guiName` = "",
                 `guiGroup` = "",
                 `level` = "1",
                 `description`  = "The name of a file(full system path) holding one or more certificates to verify the peer with.",
                 `comment` = ""
                 WHERE `name` = "runtimeOptions.openid.sslCertificatePath";
UPDATE Zf_configuration SET
                 `default` = "Translate5",
                 `defaults` = "",
                 `guiName` = "",
                 `guiGroup` = "",
                 `level` = "1",
                 `description`  = "Name of the application shown in the application itself.",
                 `comment` = ""
                 WHERE `name` = "runtimeOptions.appName";
UPDATE Zf_configuration SET
                 `default` = "",
                 `defaults` = "",
                 `guiName` = "",
                 `guiGroup` = "",
                 `level` = "1",
                 `description`  = "Url for the brending source in the editor branding area. When the config is configured with this value : /client-specific/branding.phtml , then the branding.phtml file will be loaded from the client-specific/public direcotry .",
                 `comment` = ""
                 WHERE `name` = "runtimeOptions.editor.editorBrandingSource";
UPDATE Zf_configuration SET
                 `default` = "1",
                 `defaults` = "",
                 `guiName` = "",
                 `guiGroup` = "",
                 `level` = "1",
                 `description`  = "Max parallel running workers of the Final Import Worker worker",
                 `comment` = ""
                 WHERE `name` = "runtimeOptions.worker.editor_Models_Import_Worker_FinalStep.maxParallelWorkers";
UPDATE Zf_configuration SET
                 `default` = "150",
                 `defaults` = "",
                 `guiName` = "",
                 `guiGroup` = "",
                 `level` = "1",
                 `description`  = "Only segments with a lesser word count are sent to the termTagger",
                 `comment` = ""
                 WHERE `name` = "runtimeOptions.termTagger.maxSegmentWordCount";
UPDATE Zf_configuration SET
                 `default` = "1",
                 `defaults` = "",
                 `guiName` = "",
                 `guiGroup` = "",
                 `level` = "1",
                 `description`  = "Max parallel running workers of the WGET worker",
                 `comment` = ""
                 WHERE `name` = "runtimeOptions.worker.editor_Plugins_VisualReview_WgetHtmlWorker.maxParallelWorkers";
UPDATE Zf_configuration SET
                 `default` = "1",
                 `defaults` = "",
                 `guiName` = "",
                 `guiGroup` = "",
                 `level` = "1",
                 `description`  = "Max parallel running workers of the HTML Import worker",
                 `comment` = ""
                 WHERE `name` = "runtimeOptions.worker.editor_Plugins_VisualReview_HtmlImportWorker.maxParallelWorkers";
UPDATE Zf_configuration SET
                 `default` = "1",
                 `defaults` = "",
                 `guiName` = "",
                 `guiGroup` = "",
                 `level` = "1",
                 `description`  = "Max parallel running workers of the XmlXslt worker",
                 `comment` = ""
                 WHERE `name` = "runtimeOptions.worker.editor_Plugins_VisualReview_XmlXsltToHtmlWorker.maxParallelWorkers";
UPDATE Zf_configuration SET
                 `default` = "{}",
                 `defaults` = "",
                 `guiName` = "",
                 `guiGroup` = "",
                 `level` = "1",
                 `description`  = "Default state configuration for the editor search window. If this field value is empty ({} is not an empty value!), no state will be applied/saved for this component.",
                 `comment` = ""
                 WHERE `name` = "runtimeOptions.frontend.defaultState.editor.searchreplacewindow";
UPDATE Zf_configuration SET
                 `default` = "0",
                 `defaults` = "",
                 `guiName` = "",
                 `guiGroup` = "",
                 `level` = "32",
                 `description`  = "Only replace repetition automatically / propose replacement of repetition, if target is empty. This is the default value, can be changed by the user.",
                 `comment` = ""
                 WHERE `name` = "runtimeOptions.alike.showOnEmptyTarget";
UPDATE Zf_configuration SET
                 `default` = "https://jira.translate5.net/browse/{0}",
                 `defaults` = "",
                 `guiName` = "",
                 `guiGroup` = "",
                 `level` = "1",
                 `description`  = "Url for information to the error codes. The placeholder \"{0}\" will be replaced by the error code.",
                 `comment` = ""
                 WHERE `name` = "runtimeOptions.jiraIssuesUrl";
UPDATE Zf_configuration SET
                 `default` = "0",
                 `defaults` = "",
                 `guiName` = "",
                 `guiGroup` = "",
                 `level` = "",
                 `description`  = "",
                 `comment` = "deprecated"
                 WHERE `name` = "runtimeOptions.disableErrorMails.all";
UPDATE Zf_configuration SET
                 `default` = "0",
                 `defaults` = "",
                 `guiName` = "",
                 `guiGroup` = "",
                 `level` = "1",
                 `description`  = "deaktiviert ausschließlich den Versand der Error-Mails ohne dump",
                 `comment` = "deprecated"
                 WHERE `name` = "runtimeOptions.disableErrorMails.default";
UPDATE Zf_configuration SET
                 `default` = "0",
                 `defaults` = "",
                 `guiName` = "",
                 `guiGroup` = "",
                 `level` = "1",
                 `description`  = "",
                 `comment` = "deprecated"
                 WHERE `name` = "runtimeOptions.disableErrorMails.fulldump";
UPDATE Zf_configuration SET
                 `default` = "0",
                 `defaults` = "",
                 `guiName` = "",
                 `guiGroup` = "",
                 `level` = "1",
                 `description`  = "deaktiviert ausschließlich den Versand der Error-Mails mit minidump",
                 `comment` = "deprecated"
                 WHERE `name` = "runtimeOptions.disableErrorMails.minidump";
UPDATE Zf_configuration SET
                 `default` = "1",
                 `defaults` = "",
                 `guiName` = "",
                 `guiGroup` = "",
                 `level` = "1",
                 `description`  = "deaktiviert ausschließlich den Versand der Error-Mails ohne dump",
                 `comment` = "deprecated"
                 WHERE `name` = "runtimeOptions.disableErrorMails.notFound";
UPDATE Zf_configuration SET
                 `default` = "0",
                 `defaults` = "",
                 `guiName` = "",
                 `guiGroup` = "",
                 `level` = "1",
                 `description`  = "Wert mit 1 aktiviert grundsätzlich das errorCollecting im Errorhandler. D. h. Fehler werden nicht mehr vom ErrorController, sondern vom ErrorcollectController behandelt und im Fehlerfall wird nicht sofort eine Exception geworfen, sondern die Fehlerausgabe erfolgt erst für alle Fehler gesammelt am Ende jedes Controller-Dispatches. Fehlermails und Logging analog zum normalen ErrorController. Wert 0 ist die empfohlene Standardeinstellung, da bei sauberer Programmierung schon ein fehlender Array-Index (also ein php-notice) zu unerwarteten Folgeerscheinungen führt und daher nicht kalkulierbare Nachwirkungen auf Benutzer und Datenbank hat. Wert kann über die Zend_Registry an beliebiger Stelle im Prozess per Zend_Registry aktiviert werden. Damit diese Einstellung greifen kann, muss das Resource-Plugin ZfExtended_Controllers_Plugins_ErrorCollect in der application.ini aktiviert sein",
                 `comment` = "deprecated"
                 WHERE `name` = "runtimeOptions.errorCollect";
UPDATE Zf_configuration SET
                 `default` = "/ext-4.0.7",
                 `defaults` = "",
                 `guiName` = "",
                 `guiGroup` = "",
                 `level` = "1",
                 `description`  = "Ext JS Base Verzeichnis",
                 `comment` = "deprecated"
                 WHERE `name` = "runtimeOptions.extJs.basepath.407";
UPDATE Zf_configuration SET
                 `default` = "[\"editableColumn\"]",
                 `defaults` = "",
                 `guiName` = "",
                 `guiGroup` = "",
                 `level` = "1",
                 `description`  = "Column itemIds der Spalten die per Default ausgeblendet sein sollen. Die itemIds werden in der ui/segments/grid.js definiert, in der Regel Spaltenname + \'Column\'",
                 `comment` = "deprecated"
                 WHERE `name` = "runtimeOptions.segments.disabledFields";
UPDATE Zf_configuration SET
                 `default` = "0",
                 `defaults` = "",
                 `guiName` = "",
                 `guiGroup` = "",
                 `level` = "1",
                 `description`  = "Legt fest, ob alle E-Mails lokal verschickt werden sollen, dann wird bei allen E-Mails alles ab dem @ bis zum Ende der Adresse beim, Versenden der Mail als Empfängeradresse weggelassen. Aus new@marcmittag.de wird also new",
                 `comment` = "deprecated"
                 WHERE `name` = "runtimeOptions.sendMailLocally";
UPDATE Zf_configuration SET
                 `default` = "0",
                 `defaults` = "",
                 `guiName` = "",
                 `guiGroup` = "",
                 `level` = "1",
                 `description`  = "Bei Wert 0 zeigt er für den Produktivbetrieb dem Anwender im Browser nur eine allgemeine Fehlermeldung und keinen Trace",
                 `comment` = "deprecated"
                 WHERE `name` = "runtimeOptions.showErrorsInBrowser";
UPDATE Zf_configuration SET
                 `default` = "[\"http://localhost:9001\",\"http://localhost:9002\"]",
                 `defaults` = "",
                 `guiName` = "",
                 `guiGroup` = "",
                 `level` = "1",
                 `description`  = "Comma separated list of available TermTagger-URLs. At least one available URL must be defined. Example: [\"http://localhost:9000\"]",
                 `comment` = "deprecated"
                 WHERE `name` = "runtimeOptions.termTagger.url.default";
UPDATE Zf_configuration SET
                 `default` = "",
                 `defaults` = "",
                 `guiName` = "",
                 `guiGroup` = "",
                 `level` = "1",
                 `description`  = "The server maintenance start date and time in the format 2016-09-21 09:21",
                 `comment` = "deprecated"
                 WHERE `name` = "runtimeOptions.maintenance.startDate";
UPDATE Zf_configuration SET
                 `default` = "30",
                 `defaults` = "",
                 `guiName` = "",
                 `guiGroup` = "",
                 `level` = "1",
                 `description`  = "This is set to a number of minutes. This defines, how many minutes before the runtimeOptions.maintenance.startDate the users who are currently logged in are notified",
                 `comment` = "deprecated"
                 WHERE `name` = "runtimeOptions.maintenance.timeToNotify";
UPDATE Zf_configuration SET
                 `default` = "5",
                 `defaults` = "",
                 `guiName` = "",
                 `guiGroup` = "",
                 `level` = "1",
                 `description`  = "This is set to a number of minutes. This defines, how many minutes before the runtimeOptions.maintenance.startDate the no new users are log in anymore.",
                 `comment` = "deprecated"
                 WHERE `name` = "runtimeOptions.maintenance.timeToLoginLock";
UPDATE Zf_configuration SET
                 `default` = "",
                 `defaults` = "",
                 `guiName` = "",
                 `guiGroup` = "",
                 `level` = "1",
                 `description`  = "An additional text message about the maintenance, shown in the GUI and in the maintenance announcement e-mail.",
                 `comment` = "deprecated"
                 WHERE `name` = "runtimeOptions.maintenance.message";
UPDATE Zf_configuration SET
                 `default` = "admin",
                 `defaults` = "",
                 `guiName` = "",
                 `guiGroup` = "",
                 `level` = "1",
                 `description`  = "A comma separated list of system roles, which should receive the maintenance announcement e-mail. Single users can be added by adding user:LOGINNAME instead a group.",
                 `comment` = "deprecated"
                 WHERE `name` = "runtimeOptions.maintenance.announcementMail";
UPDATE Zf_configuration SET
                 `default` = "1",
                 `defaults` = "",
                 `guiName` = "All translate5 languages available for creating term?",
                 `guiGroup` = "TermPortal",
                 `level` = "2",
                 `description`  = "If activated, when the user creates a new term in the TermPortal, he is able to select the language of the term from all languages available in translate5. If deactivated, he can only choose from those languages, that exist in the language resources that are available for him at the moment.",
                 `comment` = ""
                 WHERE `name` = "runtimeOptions.termportal.newTermAllLanguagesAvailable";
UPDATE Zf_configuration SET
                `default` = "please configure me",
                `defaults` = "",
                `guiName` = "",
                `guiGroup` = "",
                `level` = "1",
                `description`  = "If no PM can be assigned automatically due non matching languages, the user defined here with his login is used instead.",
                `comment` = "deprecated"
                WHERE `name` = "runtimeOptions.plugins.Miele.autoAssignPmFallback.login";
UPDATE Zf_configuration SET
                `default` = "[\"editor_Plugins_ChangeLog_Init\",\"editor_Plugins_MatchAnalysis_Init\",\"editor_Plugins_TermTagger_Bootstrap\",\"editor_Plugins_Transit_Bootstrap\",\"editor_Plugins_SpellCheck_Init\"]",
                `defaults` = "",
                `guiName` = "Active plug-ins",
                `guiGroup` = "System setup: General",
                `level` = "2",
                `description`  = "This list contains the plugins which should be loaded for the application! Please see https://confluence.translate5.net/display/CON/Plug-in+overview for more information. If you activate a plug-in, every user should log out and log in again. Also some plug-ins like TrackChanges should not be deactivated, once they had been used.",
                `comment` = ""
                WHERE `name` = "runtimeOptions.plugins.active";
