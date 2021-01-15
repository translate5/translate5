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

INSERT INTO `Zf_configuration` (`name`, `confirmed`, `module`, `category`, `value`, `default`, `defaults`, `type`, `description`, `level`, `guiName`, `guiGroup`, `comment`) 
VALUES ('runtimeOptions.lengthRestriction.size-unit', '1', 'editor', 'system', 'char', 'char', 'char,pixel', 'string', 'Defines how the unit of measurement size used for length calculation.', '8', 'Segment length restriction: Unit of measurement', 'Editor: QA', '');

INSERT INTO `Zf_configuration` (`name`, `confirmed`, `module`, `category`, `value`, `default`, `defaults`, `type`, `description`, `level`, `guiName`, `guiGroup`, `comment`) 
VALUES ('runtimeOptions.lengthRestriction.maxWidth', '1', 'editor', 'system', '', '', '', 'integer', 'The count is based on the unit of measurement. If maxNumberOfLines is set, maxWidth refers to the length of each line, otherwise maxWidth refers to the trans-unit in the underlying xliff (which might span multiple segments)', '8', 'Segment length restriction: Maximal allowed width', 'Editor: QA', '');

INSERT INTO `Zf_configuration` (`name`, `confirmed`, `module`, `category`, `value`, `default`, `defaults`, `type`, `description`, `level`, `guiName`, `guiGroup`, `comment`) 
VALUES ('runtimeOptions.lengthRestriction.minWidth', '1', 'editor', 'system', '', '', '', 'integer', 'The count is based on the unit of measurement. If maxNumberOfLines is set, minWidth refers to the length of each line, otherwise minWidth refers to the trans-unit in the underlying xliff (which might span multiple segments)', '8', 'Segment length restriction: Minimal allowed width', 'Editor: QA', '');

INSERT INTO `Zf_configuration` (`name`, `confirmed`, `module`, `category`, `value`, `default`, `defaults`, `type`, `description`, `level`, `guiName`, `guiGroup`, `comment`) 
VALUES ('runtimeOptions.lengthRestriction.maxNumberOfLines', '1', 'editor', 'system', '', '', '', 'integer', 'How many lines the text in the segment is maximal allowed to have (can be overwritten in xliff\'s trans-unit)', '8', 'Segment length restriction: Allowed number of lines in segment', 'Editor: QA', '');

INSERT INTO `Zf_configuration` (`name`, `confirmed`, `module`, `category`, `value`, `default`, `defaults`, `type`, `description`, `level`, `guiName`, `guiGroup`, `comment`) 
VALUES ('runtimeOptions.lengthRestriction.pixelmapping.font', '1', 'editor', 'system', '', '', '', 'string', 'Contains the name of a font-family, e.g. "Arial" or "Times New Roman", that refers to the pixel-mapping.xlsx file (see documentation in translate5s confluence)', '8', 'Segment length restriction (pixel-based): Font name', 'Editor: QA', '');

INSERT INTO `Zf_configuration` (`name`, `confirmed`, `module`, `category`, `value`, `default`, `defaults`, `type`, `description`, `level`, `guiName`, `guiGroup`, `comment`) 
VALUES ('runtimeOptions.lengthRestriction.pixelmapping.fontSize', '1', 'editor', 'system', '', '', '', 'integer', 'Contains a font-size, e.g. "12", that refers to the pixel-mapping.xlsx file (see documentation in translate5s confluence)', '8', 'Segment length restriction (pixel-based): Font size', 'Editor: QA', '');

INSERT INTO `Zf_configuration` (`name`, `confirmed`, `module`, `category`, `value`, `default`, `defaults`, `type`, `description`, `level`, `guiName`, `guiGroup`, `comment`)
VALUES ('runtimeOptions.import.fileparser.csv.options.protectTags', '1', 'editor', 'system', '1', '1', '', 'boolean', 'If set to active, tags inside the CSV cells are protected as tags in translate5 segments. This is done for all HTML5 tags and in addition for all tags that look like a valid XML snippet.', '8', 'CSV import: Protect tags', 'File formats', '');

INSERT INTO `Zf_configuration` (`name`, `confirmed`, `module`, `category`, `value`, `default`, `defaults`, `type`, `description`, `level`, `guiName`, `guiGroup`, `comment`)
VALUES ('runtimeOptions.import.fileparser.csv.options.regexes.beforeTagParsing.regex', '1', 'editor', 'system', '[]', '[]', '', 'list', 'Must contain a valid php-pcre REGEX. Patterns that match the REGEX will be protected as internal tags inside the segments during the translation process. If the regex is not valid, the import will throw an error and continue without using the regex. If "protect tags" is active, the REGEX will be applied to the segment BEFORE translate5 tries to protect tags. If "protect tags" is not active, the REGEX will still be applied.', '8', 'CSV import: Regular expression (run BEFORE tag protection)', 'File formats', '');

INSERT INTO `Zf_configuration` (`name`, `confirmed`, `module`, `category`, `value`, `default`, `defaults`, `type`, `description`, `level`, `guiName`, `guiGroup`, `comment`)
VALUES ('runtimeOptions.import.fileparser.csv.options.regexes.afterTagParsing.regex', '1', 'editor', 'system', '[]', '[]', '', 'list', 'Must contain a valid php-pcre REGEX. Patterns that match the REGEX will be protected as internal tags inside the segments during the translation process. If the regex is not valid, the import will throw an error and continue without using the regex. If "protect tags" is active, the REGEX will be applied to the segment AFTER translate5 tries to protect tags. If "protect tags" is not active, the REGEX will still be applied.', '8', 'CSV import: Regular expression (run AFTER tag protection)', 'File-parser: after tag protection regex', '');

UPDATE `Zf_configuration` SET `description`='If set to active, a warning will be shown, if the user edits a 100% match' WHERE `name`='runtimeOptions.editor.enable100pEditWarning';
UPDATE `Zf_configuration` SET `description`='If set to active, the MQM quality assurance panel on the right side of the editor is visible' WHERE `name`='runtimeOptions.editor.enableQmSubSegments';
UPDATE `Zf_configuration` SET `description`='If set to active, the status panel on the right side of the editor is visible' WHERE `name`='runtimeOptions.segments.showStatus';
UPDATE `Zf_configuration` SET `description`='If set to active, a user can only login with one browser at the same time. Else he could login with the same username in 2 different browsers at the same time.' WHERE `name`='runtimeOptions.singleUserRestriction';
UPDATE `Zf_configuration` SET `description`='If set to active, informations are added to the target-infofield of a segment- further configuration values decide, which information.' WHERE `name`='runtimeOptions.plugins.transit.writeInfoField.enabled';
UPDATE `Zf_configuration` SET `description`='If the writing of information to the target-infofield is activated (this is determined by another configuraiton parameter), and If set to active, terms in the source text without any translation in the target text are written to infofield.' WHERE `name`='runtimeOptions.plugins.transit.writeInfoField.termsWithoutTranslation';
UPDATE `Zf_configuration` SET `description`='If set to active, the quality assurance panel on the right side of the editor is visible' WHERE `name`='runtimeOptions.segments.showQM';
UPDATE `Zf_configuration` SET `description`='If set to active, translate5 shows a message if the used browser is not supported.' WHERE `name`='runtimeOptions.showSupportedBrowsersMsg';
UPDATE `Zf_configuration` SET `description`='If set to active, the segment comments will be exported into the exported bilingual file (if this is supported by the implementation for that file type).' WHERE `name`='runtimeOptions.editor.export.exportComments';
UPDATE `Zf_configuration` SET `description`='If set to active, error-logging in the graphical user interface is activated. Errors will be send to translate5s developers via theRootCause.io. Users can decide on every single occurence of an error, if they want to report it.' WHERE `name`='runtimeOptions.debug.enableJsLogger';
UPDATE `Zf_configuration` SET `description`='If set to active, if the generated XLIFF will be in XLIFF 2 format. Else XLIFF 1.2' WHERE `name`='runtimeOptions.editor.notification.xliff2Active';
UPDATE `Zf_configuration` SET `description`='If set to active, the import option that decides, if 100% matches can be edited in the task is activated by default. Else it is disabled by default (but can be enabled in the import settings).' WHERE `name`='runtimeOptions.frontend.importTask.edit100PercentMatch';
UPDATE `Zf_configuration` SET `description`='If set to active, spell- grammar and style check is active (based on languagetool)' WHERE `name`='runtimeOptions.plugins.SpellCheck.active';
UPDATE `Zf_configuration` SET `description`='If this is set to disabled, for 100%-Matches that differ in the target, the target of the match with the highest match rate is shown. If the match rate is the same, the match with the newest change date is shown.If set to active, all 100%-Matches that differ in the target are shown.' WHERE `name`='runtimeOptions.LanguageResources.opentm2.showMultiple100PercentMatches';
UPDATE `Zf_configuration` SET `description`='If set to active and only a TermCollection and no MT or TM language resource is assigned to the task, the fuzzy match panel will not be shown in translate5s editor.' WHERE `name`='runtimeOptions.editor.LanguageResources.disableIfOnlyTermCollection';
UPDATE `Zf_configuration` SET `description`='If set to active, the OpenID Connect configuration data is also shown for the default customer.' WHERE `name`='runtimeOptions.customers.openid.showOpenIdDefaultCustomerData';
UPDATE `Zf_configuration` SET `description`='If set to active, framing tags (tag pairs that surround the complete segment) are ignored on import. Does work for native file formats and standard xliff. Does not work for sdlxliff. See http://confluence.translate5.net/display/TFD/Xliff.' WHERE `name`='runtimeOptions.import.xlf.ignoreFramingTags';
UPDATE `Zf_configuration` SET `description`='If set to active, the error-logging in the GUI (see previous option) is extended by video recording. Videos are only kept in case of an error, that is send by the user to theRootCause.io. The user still has the option to decide, if he only wants to submit the error or if he also wants to submit the video. If a video is provided, it will be deleted, when translate5s developers did look after the error.' WHERE `name`='runtimeOptions.debug.enableJsLoggerVideo';
UPDATE `Zf_configuration` SET `description`='Help window default state configuration for the client overview panel. When this is set to disabled, the window will appear automatically in the user overview. A user can then mark the checkbox „Do not show again“ himself in the help window, which will be remembered for this user.' WHERE `name`='runtimeOptions.frontend.defaultState.helpWindow.customeroverview';
UPDATE `Zf_configuration` SET `description`='Help window default state configuration for the task overview panel. When this is set to disabled, the window will appear automatically in the user overview. A user can then mark the checkbox „Do not show again“ himself in the help window, which will be remembered for this user.' WHERE `name`='runtimeOptions.frontend.defaultState.helpWindow.taskoverview';
UPDATE `Zf_configuration` SET `description`='Help window default state configuration for the user overview panel. When this is set to disabled, the window will appear automatically in the user overview. A user can then mark the checkbox „Do not show again“ himself in the help window, which will be remembered for this user.' WHERE `name`='runtimeOptions.frontend.defaultState.helpWindow.useroverview';
UPDATE `Zf_configuration` SET `description`='Help window default state configuration for the editor overview panel. When this is set to disabled, the window will appear automatically in the user overview. A user can then mark the checkbox „Do not show again“ himself in the help window, which will be remembered for this user.' WHERE `name`='runtimeOptions.frontend.defaultState.helpWindow.editor';
UPDATE `Zf_configuration` SET `description`='Help window default state configuration for the language resource overview panel. When this is set to disabled, the window will appear automatically in the user overview. A user can then mark the checkbox „Do not show again“ himself in the help window, which will be remembered for this user.' WHERE `name`='runtimeOptions.frontend.defaultState.helpWindow.languageresource';
UPDATE `Zf_configuration` SET `description`='If set to active, the termTagger checks also read-only segments. Should not be activated to safe performance, if possible.' WHERE `name`='runtimeOptions.termTagger.tagReadonlySegments';
UPDATE `Zf_configuration` SET `description`='If set to active, disables the „What you see is what you get feature“ and simply shows the perfect layout without changing anything while typing and without pre-translating the layout with the target text.' WHERE `name`='runtimeOptions.plugins.VisualReview.disableLiveEditing';
UPDATE `Zf_configuration` SET `description`='If set to active, it is possible to upload files for pre-translation. All file formats supported by translate5 are supported.' WHERE `name`='runtimeOptions.InstantTranslate.fileTranslation';
UPDATE `Zf_configuration` SET `description`='If set to active, a pop-up is shown after a task is opened in the translate5 editor, if reference files are attached to the task.' WHERE `name`='runtimeOptions.editor.showReferenceFilesPopup';
UPDATE `Zf_configuration` SET `description`='When an assigned user leaves a task, he is asked, if he wants to finish or just leave the task. If set to active, and the user that leaves the task clicks „finish task“, he will be asked a second time, if he really wants to finish.' WHERE `name`='runtimeOptions.editor.showConfirmFinishTaskPopup';
UPDATE `Zf_configuration` SET `description`='Help window default state configuration for the project overview panel. When this is set to disabled, the window will appear automatically in the user overview. A user can then mark the checkbox „do not show again“ himself in the help window, which will be remembered for this user.' WHERE `name`='runtimeOptions.frontend.defaultState.helpWindow.project';
UPDATE `Zf_configuration` SET `description`='Help window default state configuration for the preferences section. When this is set to disabled, the window will appear automatically in the user overview. A user can then mark the checkbox „do not show again“ himself in the help window, which will be remembered for this user.' WHERE `name`='runtimeOptions.frontend.defaultState.helpWindow.preferences';
UPDATE `Zf_configuration` SET `description`='If set to active, In the source/target language dropdowns in InstantTranslate sub-languages are possible to select and use.' WHERE `name`='runtimeOptions.InstantTranslate.showSubLanguages';

UPDATE `Zf_configuration` SET `default`=`value` 
WHERE `name`='runtimeOptions.extJs.cssFile';

UPDATE `Zf_configuration` SET `default`='1',`description`='If activated, the import option that decides, if the editing of the source text in the editor is possible is by default active. Else it is disabled by default (but can be enabled in the import settings). Please note: The export of the changed source text is only possible for CSV so far. '
WHERE `name`='runtimeOptions.import.enableSourceEditing';

UPDATE `Zf_configuration` SET `default`='1' 
WHERE `name`='runtimeOptions.import.keepFilesOnError';

UPDATE `Zf_configuration` SET `default`=`value` 
WHERE `name`='runtimeOptions.termTagger.url.default';

UPDATE `Zf_configuration` SET `default`=`value` 
WHERE `name`='runtimeOptions.termTagger.url.import';

UPDATE `Zf_configuration` SET `default`=`value` 
WHERE `name`='runtimeOptions.termTagger.url.gui';

UPDATE `Zf_configuration` SET 
`description`='Attention: This is by default NOT active. To activate it, a workflow action needs to be configured. This is currently only possible on DB-Level. \nIf the task is not touched more than defined days, it will be automatically deleted. Older means, that it is not touched in the system for a longer time than this. Touching means at least opening the task or changing any kind of task assignments (users, language resources, etc.)' 
WHERE `name`='runtimeOptions.taskLifetimeDays';

