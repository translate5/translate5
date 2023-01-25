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

-- Typos --
UPDATE `Zf_configuration` SET `description` = REPLACE(`description`, "auto-propgate", "auto-propagate");
UPDATE `Zf_configuration` SET `description` = REPLACE(`description`, "invokation", "invocation");
UPDATE `Zf_configuration` SET `description` = REPLACE(`description`, "punctiation", "punctuation");
UPDATE `Zf_configuration` SET `description` = REPLACE(`description`, "e.g ", "e.g. ");
UPDATE `Zf_configuration` SET `description` = REPLACE(`description`, "If one line of a segment is to long", "If one line of a segment is too long");
UPDATE `Zf_configuration` SET `description` = REPLACE(`description`, "superseeded", "superseded");
UPDATE `Zf_configuration` SET `description` = REPLACE(`description`, "For moreinfo see the branding", "For more info see the branding");
UPDATE `Zf_configuration` SET `description` = REPLACE(`description`, "Pattern for a XML reference file", "Pattern for an XML reference file");
UPDATE `Zf_configuration` SET `description` = REPLACE(`description`, "Okapi bconf is not used for CSV iimport", "Okapi bconf is not used for CSV import");
UPDATE `Zf_configuration` SET `description` = REPLACE(`description`, "CSV import: ecnclosure", "CSV import: enclosure");
UPDATE `Zf_configuration` SET `description` = REPLACE(`description`, "this is determined by another configuraiton parameter", "this is determined by another configuration parameter");
UPDATE `Zf_configuration` SET `description` = REPLACE(`description`, "If set to active, informations are added", "If set to active, information is added");
UPDATE `Zf_configuration` SET `description` = REPLACE(`description`, "and if this checkbox is checcked", "and if this checkbox is checked");
UPDATE `Zf_configuration` SET `description` = REPLACE(`description`, "If checcked, whitespace is preserved,", "If checked, whitespace is preserved,");
UPDATE `Zf_configuration` SET `description` = REPLACE(`description`, "ressources", "resources");
UPDATE `Zf_configuration` SET `description` = REPLACE(`description`, "azureaccount", "azure account");
UPDATE `Zf_configuration` SET `description` = REPLACE(`description`, "accessable", "accessible");
UPDATE `Zf_configuration` SET `description` = REPLACE(`description`, "date is shown.If set to", "date is shown. If set to");
UPDATE `Zf_configuration` SET `description` = REPLACE(`description`, "immidiately", "immediately");
UPDATE `Zf_configuration` SET `description` = REPLACE(`description`, "keeped", "kept");
UPDATE `Zf_configuration` SET `description` = REPLACE(`description`, "prefered", "preferred");
UPDATE `Zf_configuration` SET `description` = REPLACE(`description`, "from the users browser", "from the user's browser");
UPDATE `Zf_configuration` SET `description` = REPLACE(`description`, "Errors will be send to translate5s", "Errors will be sent to translate5s");
UPDATE `Zf_configuration` SET `description` = REPLACE(`description`, "occurence", "occurrence");
UPDATE `Zf_configuration` SET `description` = REPLACE(`description`, "Okapi server used for the a task", "Okapi server used for a task");
UPDATE `Zf_configuration` SET `description` = REPLACE(`description`, "If emtpy, nothing is loaded", "If empty, nothing is loaded");
UPDATE `Zf_configuration` SET `description` = REPLACE(`description`, "The imported task is send as raw JSON in that request", "The imported task is sent as raw JSON in that request");
UPDATE `Zf_configuration` SET `description` = REPLACE(`description`, "successfull", "successful");

-- Inconsistencies --
UPDATE `Zf_configuration` SET `description` = REPLACE(`description`, 'api', 'API') WHERE `description` REGEXP '(^| )api';
UPDATE `Zf_configuration` SET `description` = REPLACE(`description`, 'Api', 'API') WHERE `description` REGEXP '(^| )api';
UPDATE `Zf_configuration` SET `description` = REPLACE(`description`, 'url', 'URL') WHERE `description` REGEXP '(^| )url';
UPDATE `Zf_configuration` SET `description` = REPLACE(`description`, 'MicroSoft', 'Microsoft');
UPDATE `Zf_configuration` SET `description` = REPLACE(`description`, 'microsoft', 'Microsoft');
UPDATE `Zf_configuration` SET `description` = REPLACE(`description`, 'pretranslated', 'pre-translation');
UPDATE `Zf_configuration` SET `description` = REPLACE(`description`, 'pretranslation', 'pre-translation');
UPDATE `Zf_configuration` SET `description` = REPLACE(`description`, 'matchrate', 'match-rate');
UPDATE `Zf_configuration` SET `description` = REPLACE(`description`, 'match rate', 'match-rate');
UPDATE `Zf_configuration` SET `description` = REPLACE(`description`, 'languagetool', 'LanguageTool');
UPDATE `Zf_configuration` SET `description` = REPLACE(`description`, 'LanguagaTool', 'LanguageTool');
UPDATE `Zf_configuration` SET `description` = REPLACE(`description`, 'languageTool', 'LanguageTool');
UPDATE `Zf_configuration` SET `description` = REPLACE(`description`, 'languagecloud', 'LanguageCloud');
UPDATE `Zf_configuration` SET `description` = REPLACE(`description`, 'termtagger', 'TermTagger');
UPDATE `Zf_configuration` SET `description` = REPLACE(`description`, 'Websocket', 'WebSocket');
UPDATE `Zf_configuration` SET `description` = REPLACE(`description`, 'Extjs', 'ExtJS');
UPDATE `Zf_configuration` SET `description` = REPLACE(`description`, 'ExtJs', 'ExtJS');
UPDATE `Zf_configuration` SET `description` = REPLACE(`description`, 'termportal', 'TermPortal');
UPDATE `Zf_configuration` SET `description` = REPLACE(`description`, ' he ', ' the user ');

-- Spelling for English (United States) --
UPDATE `Zf_configuration` SET `description` = "Default behavior, for the repetition editor (auto-propagate). Possible values: 'never', 'always', 'individual' – they refer to when automatic replacements are made. Individual means, for every segment with repetitions a window will pop-up and ask the user what to do." WHERE `name` = "runtimeOptions.alike.defaultBehaviour";
UPDATE `Zf_configuration` SET `description` = "Name of the company that uses translate5. Is shown in emails and other places" WHERE `name` = "runtimeOptions.companyName";
UPDATE `Zf_configuration` SET `description` = "Department that is responsible for translate5 in the company that uses translate5." WHERE `name` = "runtimeOptions.contactData.emergencyContactDepartment";
UPDATE `Zf_configuration` SET `description` = "It is recommended to call Translate5 cron job mechanism every 15 min. These calls are only allowed to originate from the IP address configured here." WHERE `name` = "runtimeOptions.cronIP";
UPDATE `Zf_configuration` SET `description` = "regex which defines non-word-characters; must include brackets () for the return of the delimiters of preg_split by PREG_SPLIT_DELIM_CAPTURE; define including delimiters and modifiers" WHERE `name` = "runtimeOptions.editor.export.wordBreakUpRegex";
UPDATE `Zf_configuration` SET `description` = "defines if the generated xml should be additionally stored in the task directory" WHERE `name` = "runtimeOptions.editor.notification.saveXmlToFile";
UPDATE `Zf_configuration` SET `description` = "Severity levels for the MQM quality assurance. The MQM issue types can be overwritten in the import zip file (please see https://confluence.translate5.net/x/-wET ). Please contact Translate5 developers, if this should be available as a GUI configuration option." WHERE `name` = "runtimeOptions.editor.qmSeverity";
UPDATE `Zf_configuration` SET `description` = "The \"default\" equals the theme configured in runtimeOptions.extJs.defaultTheme config. If this config is empty ExtJS \"Triton\" theme will be used. The dark theme equals the ExtJS \"Aria\" theme. More themes can be used if configured on db level. Please contact translate5 support, if you need this." WHERE `name` = "runtimeOptions.extJs.theme";
UPDATE `Zf_configuration` SET `description` = "If activated, the import option that decides if the editing of the source text in the editor is possible is by default active. Else it is disabled by default (but can be enabled in the import settings). Please note: The export of the changed source text is only possible for CSV so far. " WHERE `name` = "runtimeOptions.import.enableSourceEditing";
UPDATE `Zf_configuration` SET `description` = "List of email addresses, that will be set to BCC for ALL emails translate5 sends." WHERE `name` = "runtimeOptions.mail.generalBcc";
UPDATE `Zf_configuration` SET `description` = "Available options for the quality assurance panel on the right side of the editor" WHERE `name` = "runtimeOptions.segments.qualityFlags";
UPDATE `Zf_configuration` SET `description` = "visibility of non-editable target column(s): For \"show\" or \"hide\" the user can change the visibility of the columns in the usual way in the editor. If \"disable\" is selected, the user has no access at all to the non-editable columns." WHERE `name` = "runtimeOptions.workflow.default.visibility";
UPDATE `Zf_configuration` SET `description` = "visibility of non-editable target column(s): For \"show\" or \"hide\" the user can change the visibility of the columns in the usual way in the editor. If \"disable\" is selected, the user has no access at all to the non-editable columns." WHERE `name` = "runtimeOptions.workflow.dummy.visibility";
UPDATE `Zf_configuration` SET `description` = "visibility of non-editable target column(s): For \"show\" or \"hide\" the user can change the visibility of the columns in the usual way in the editor. If \"disable\" is selected, the user has no access at all to the non-editable columns." WHERE `name` = "runtimeOptions.workflow.ranking.visibility";
UPDATE `Zf_configuration` SET `description` = "This list contains the plugins which should be loaded for the application! Please see https://confluence.translate5.net/x/cwET for more information. If you activate a plug-in, every user should log out and log in again. Also some plug-ins like TrackChanges should not be deactivated, once they have been used." WHERE `name` = "runtimeOptions.plugins.active";
UPDATE `Zf_configuration` SET `description` = "If set to active, information is added to the target-infofield of a segment- further configuration values decide which information." WHERE `name` = "runtimeOptions.plugins.transit.writeInfoField.enabled";
UPDATE `Zf_configuration` SET `description` = "decides if segments with metadata \"notTranslated\" will be locked from editing by this plugin." WHERE `name` = "runtimeOptions.plugins.LockSegmentsBasedOnConfig.metaToLock.notTranslated";
UPDATE `Zf_configuration` SET `description` = "decides if segments with metadata \"transitLockedForRefMat\" will be locked from editing by this plugin." WHERE `name` = "runtimeOptions.plugins.LockSegmentsBasedOnConfig.metaToLock.transitLockedForRefMat";
UPDATE `Zf_configuration` SET `description` = "decides if segments with metadata \"noMissingTargetTermOnImport\" will be locked from editing by this plugin." WHERE `name` = "runtimeOptions.plugins.LockSegmentsBasedOnConfig.metaToLock.noMissingTargetTermOnImport";
UPDATE `Zf_configuration` SET `description` = "Refers to import processes. List one or multiple URLs, where TermTagger-instances can be reached for checking and marked in the segments (to check, if the correct terminology is used). Translate5 does load balancing, if more than one is configured." WHERE `name` = "runtimeOptions.termTagger.url.import";
UPDATE `Zf_configuration` SET `description` = "Refers to segments saved in the GUI. List one or multiple URLs, where TermTagger-instances can be reached for checking and marked in the segments (to check, if the correct terminology is used). Translate5 does load balancing, if more than one is configured." WHERE `name` = "runtimeOptions.termTagger.url.gui";
UPDATE `Zf_configuration` SET `description` = "This flag disables the application to send emails." WHERE `name` = "runtimeOptions.sendMailDisabled";
UPDATE `Zf_configuration` SET `description` = "This is set to a number of minutes. This defines, how many minutes before the runtimeOptions.maintenance.startDate the no new users are login anymore." WHERE `name` = "runtimeOptions.maintenance.timeToLoginLock";
UPDATE `Zf_configuration` SET `description` = "Set here a default locale for the application GUI. If empty, the default locale is derived from the user's browser (which is the default)." WHERE `name` = "runtimeOptions.translation.applicationLocale";
UPDATE `Zf_configuration` SET `description` = "If empty defaults to \"runtimeOptions.server.protocol\" and \"runtimeOptions.server.name\". This config allows access to the local worker API through a different URL as the public one. Format of this configuration value: SCHEME://HOST:PORT" WHERE `name` = "runtimeOptions.worker.server";
UPDATE `Zf_configuration` SET `description` = "If the writing of information to the target-infofield is activated (this is determined by another configuration parameter), and if the export date is added to the target-infofield (also determined by another parameter) this text field becomes relevant. If it is empty the current date is used as the export date. If it contains a valid date in the form YYYY-MM-DD this date is used." WHERE `name` = "runtimeOptions.plugins.transit.writeInfoField.exportDateValue";
UPDATE `Zf_configuration` SET `description` = "If checked, only the content of editable segments is written back to the transit file on export. This does not influence the Info Field!" WHERE `name` = "runtimeOptions.plugins.transit.exportOnlyEditable";
UPDATE `Zf_configuration` SET `description` = "If enabled, an advice is shown for IE users to use a more performant browser." WHERE `name` = "runtimeOptions.browserAdvice";
UPDATE `Zf_configuration` SET `description` = "How many parallel processes are allowed for file and segment parsing in the import. This value depends on what your hardware can serve. Please consult Translate5 team, if you change this." WHERE `name` = "runtimeOptions.worker.editor_Models_Import_Worker.maxParallelWorkers";
UPDATE `Zf_configuration` SET `description` = "How many parallel processes are allowed for the export. This value depends on what your hardware can serve. Please consult Translate5 team, if you change this." WHERE `name` = "runtimeOptions.worker.editor_Models_Export_Worker.maxParallelWorkers";
UPDATE `Zf_configuration` SET `description` = "If set to active, error-logging in the graphical user interface is activated. Errors will be sent to Translate5 developers via theRootCause.io. Users can decide on every single occurrence of an error, if they want to report it." WHERE `name` = "runtimeOptions.debug.enableJsLogger";
UPDATE `Zf_configuration` SET `description` = "If enabled, notification emails with segment data get also the changed segments as XLIFF-attachment." WHERE `name` = "runtimeOptions.editor.notification.enableSegmentXlfAttachment";
UPDATE `Zf_configuration` SET `description` = "Attention: This is by default NOT active. To activate it, a workflow action needs to be configured. This is currently only possible on DB-Level. If the task is not touched more than defined days, it will be automatically deleted. Older means that it is not touched in the system for a longer time than this. Touching means at least opening the task or changing any kind of task assignments (users, language resources, etc.)" WHERE `name` = "runtimeOptions.taskLifetimeDays";
UPDATE `Zf_configuration` SET `description` = "If set to active, the generated XLIFF will be in XLIFF 2 format. Else XLIFF 1.2" WHERE `name` = "runtimeOptions.editor.notification.xliff2Active";
UPDATE `Zf_configuration` SET `description` = "decides if segments with metadata \"transitLockedForRefMat\" will be ignored by this plugin." WHERE `name` = "runtimeOptions.plugins.SegmentStatistics.metaToIgnore.transitLockedForRefMat";
UPDATE `Zf_configuration` SET `description` = "This refers to the XLIFF file based pre-translation with Globalese – not the language resource-based one. How many parallel processes are allowed depends on Globalese capabilities." WHERE `name` = "runtimeOptions.worker.editor_Plugins_GlobalesePreTranslation_Worker.maxParallelWorkers";
UPDATE `Zf_configuration` SET `description` = "How many parallel processes are allowed for okapi file conversion within the translate5 instance. Please consult Translate5 team, if you change this." WHERE `name` = "runtimeOptions.worker.editor_Plugins_Okapi_Worker.maxParallelWorkers";
UPDATE `Zf_configuration` SET `description` = "The absolute path to the tikal executable, no usable default can be given so it is empty and must be configured by the user!" WHERE `name` = "runtimeOptions.plugins.Okapi.tikal.executable";
UPDATE `Zf_configuration` SET `description` = "Max parallel running import FileTree workers" WHERE `name` = "runtimeOptions.worker.editor_Models_Import_Worker_FileTree.maxParallelWorkers";
UPDATE `Zf_configuration` SET `description` = "Max parallel running import reference FileTree workers" WHERE `name` = "runtimeOptions.worker.editor_Models_Import_Worker_ReferenceFileTree.maxParallelWorkers";
UPDATE `Zf_configuration` SET `description` = "If set to active, the import option that decides if 100% matches can be edited in the task is activated by default. Else it is disabled by default (but can be enabled in the import settings)." WHERE `name` = "runtimeOptions.frontend.importTask.edit100PercentMatch";
UPDATE `Zf_configuration` SET `description` = "If enabled, deleted / added whitespace tags are ignored in the tag validation. If disabled the user must have the same whitespace tags in source and target." WHERE `name` = "runtimeOptions.segments.userCanModifyWhitespaceTags";
UPDATE `Zf_configuration` SET `description` = "Max parallel running processes of the XLIFF 2 export worker are allowed. Please consult Translate5 team, if you change this." WHERE `name` = "runtimeOptions.worker.editor_Models_Export_Xliff2Worker.maxParallelWorkers";
UPDATE `Zf_configuration` SET `description` = "Which buttons are hidden in the visual review action button panel. Provide the button itemId, and it will not be shown in the action button panel. The button Ids can be found in the ActionButton.js " WHERE `name` = "runtimeOptions.plugins.VisualReview.hideButton";
UPDATE `Zf_configuration` SET `description` = "Defines how garbage collection should be triggered: on each request in a specific time frame, cron via cronjob URL /editor/cron/periodical. Calling the cron URL once reconfigures the application to use cron based garbage collection." WHERE `name` = "runtimeOptions.garbageCollector.invocation";
UPDATE `Zf_configuration` SET `description` = "Define the default pixel-widths for font-sizes, independent from the used font or character. Key is the font size and value is the pixel width assumed in the GUI check." WHERE `name` = "runtimeOptions.lengthRestriction.pixelMapping";
UPDATE `Zf_configuration` SET `description` = "Attach original files as reference files for all files, that are converted by Okapi (all except bilingual file formats and CSV)" WHERE `name` = "runtimeOptions.plugins.Okapi.import.fileconverters.attachOriginalFileAsReference";
UPDATE `Zf_configuration` SET `description` = "Max parallel running processes of the import of language resource data (TMX or TBX, etc) are allowed. Please consult Translate5 team, if you change this." WHERE `name` = "runtimeOptions.worker.editor_Services_ImportWorker.maxParallelWorkers";
UPDATE `Zf_configuration` SET `description` = "If set to active and only a TermCollection and no MT or TM language resource is assigned to the task, the fuzzy match panel will not be shown in Translate5 editor." WHERE `name` = "runtimeOptions.editor.LanguageResources.disableIfOnlyTermCollection";
UPDATE `Zf_configuration` SET `description` = "Microsoft translator language resource API URL. To be able to use Microsoft translator, you should create a Microsoft Azure account. Create and setup and Microsoft azure account in the following link: https://azure.Microsoft.com/en-us/services/cognitive-services/translator-text-API/" WHERE `name` = "runtimeOptions.LanguageResources.microsoft.apiUrl";
UPDATE `Zf_configuration` SET `description` = "Enable this to use symlinks instead of the PHP proxy to access the review.html files. Improves performance for large imported files, but needs a symlink capable OS. Please contact translate5 support, before activating this." WHERE `name` = "runtimeOptions.plugins.VisualReview.directPublicAccess";
UPDATE `Zf_configuration` SET `description` = "Max parallel running processes of the match analysis worker are allowed. Please consult Translate5 team, if you change this." WHERE `name` = "runtimeOptions.worker.editor_Plugins_MatchAnalysis_Worker.maxParallelWorkers";
UPDATE `Zf_configuration` SET `description` = "Max parallel running processes of the Excel task export and reimport are allowed. Please consult Translate5 team, if you change this." WHERE `name` = "runtimeOptions.worker.editor_Models_Excel_Worker.maxParallelWorkers";
UPDATE `Zf_configuration` SET `description` = "Max parallel running processes of the NEC-TM categories (aka tags) sync are allowed. Please consult Translate5 team, if you change this." WHERE `name` = "runtimeOptions.worker.editor_Plugins_NecTm_Worker.maxParallelWorkers";
UPDATE `Zf_configuration` SET `description` = "If activated, when the user creates a new term in the TermPortal, the user is able to select the language of the term from all languages available in translate5. If deactivated, the user can only choose from those languages that exist in the language resources that are available for him at the moment." WHERE `name` = "runtimeOptions.termportal.newTermAllLanguagesAvailable";
UPDATE `Zf_configuration` SET `description` = "Are sub-languages shown and usable as selectable options in the drop-down to select the language in which is searched?" WHERE `name` = "runtimeOptions.TermPortal.showSubLanguages";
UPDATE `Zf_configuration` SET `description` = "An additional text message about the maintenance, shown in the GUI and in the maintenance announcement email." WHERE `name` = "runtimeOptions.maintenance.message";
UPDATE `Zf_configuration` SET `description` = "A comma separated list of system roles, which should receive the maintenance announcement email. Single users can be added by adding user: LOGINNAME instead of a group." WHERE `name` = "runtimeOptions.maintenance.announcementMail";
UPDATE `Zf_configuration` SET `description` = "If set to active, the error-logging in the GUI (see previous option) is extended by video recording. Videos are only kept in case of an error that is sent by the user to theRootCause.io. The user still has the option to decide, if the user only wants to submit the error or if the user also wants to submit the video. If a video is provided, it will be deleted, when Translate5 developers did look after the error." WHERE `name` = "runtimeOptions.debug.enableJsLoggerVideo";
UPDATE `Zf_configuration` SET `description` = "Url for the branding source in the editor branding area. When the config is configured with this value : /client-specific/branding.phtml , then the branding.phtml file will be loaded from the client-specific/public directory ." WHERE `name` = "runtimeOptions.editor.editorBrandingSource";
UPDATE `Zf_configuration` SET `description` = "Defines what columns in the task overview are shown in  what order and if they are hidden or visible. For more information please see https://confluence.translate5.net/x/AQBdCQ" WHERE `name` = "runtimeOptions.frontend.defaultState.adminTaskGrid";
UPDATE `Zf_configuration` SET `description` = "Defines what columns in the user overview are shown in  what order and if they are hidden or visible. For more information please see https://confluence.translate5.net/x/AQBdCQ" WHERE `name` = "runtimeOptions.frontend.defaultState.adminUserGrid";
UPDATE `Zf_configuration` SET `description` = "Optional title for the additional custom panel on the left. This text is used for all GUI languages. If it should be translated, overwrite it in a XLF file in client-specific/locales" WHERE `name` = "runtimeOptions.editor.customPanel.title";
UPDATE `Zf_configuration` SET `description` = "The content from the defined URL will be loaded in this help page section. If it is empty, nothing is loaded and the help button will not be available." WHERE `name` = "runtimeOptions.frontend.helpWindow.customeroverview.loaderUrl";
UPDATE `Zf_configuration` SET `description` = "The content from the defined URL will be loaded in this help page section. If it is empty, nothing is loaded and the help button will not be available." WHERE `name` = "runtimeOptions.frontend.helpWindow.taskoverview.loaderUrl";
UPDATE `Zf_configuration` SET `description` = "The content from the defined URL will be loaded in this help page section. If it is empty, nothing is loaded and the help button will not be available." WHERE `name` = "runtimeOptions.frontend.helpWindow.useroverview.loaderUrl";
UPDATE `Zf_configuration` SET `description` = "The content from the defined URL will be loaded in this help page section. If it is empty, nothing is loaded and the help button will not be available." WHERE `name` = "runtimeOptions.frontend.helpWindow.languageresource.loaderUrl";
UPDATE `Zf_configuration` SET `description` = "This is security relevant. Only activate this, if you are sure that the Javascript in the HTML will be safe and not hack your system." WHERE `name` = "runtimeOptions.plugins.VisualReview.allowHtmlImportJavascript";
UPDATE `Zf_configuration` SET `description` = "Defines the background colors that are applied to segments in the layout according to their match-rate. Key is the match-rate, value is the HTML hex code of the color. This color will be applied for all segments lower or equal to the defined match-rate." WHERE `name` = "runtimeOptions.plugins.VisualReview.matchRateColorization";
UPDATE `Zf_configuration` SET `description` = "Default state configuration for the editor fuzzy match and concordance search panel. If this field value is empty ({} is not an empty value!), no state will be applied/saved for this component." WHERE `name` = "runtimeOptions.frontend.defaultState.editor.languageResourceEditorPanel";
UPDATE `Zf_configuration` SET `description` = "When an assigned user leaves a task, the user is asked if the user wants to finish or just leave the task. If set to active, and the user that leaves the task clicks „finish task“, the user will be asked a second time, if the user really wants to finish." WHERE `name` = "runtimeOptions.editor.showConfirmFinishTaskPopup";
UPDATE `Zf_configuration` SET `description` = "The content from the defined URL will be loaded in this help page section. If it is empty, nothing is loaded and the help button will not be available." WHERE `name` = "runtimeOptions.frontend.helpWindow.project.loaderUrl";
UPDATE `Zf_configuration` SET `description` = "The content from the defined URL will be loaded in this help page section. If it is empty, nothing is loaded and the help button will not be available." WHERE `name` = "runtimeOptions.frontend.helpWindow.preferences.loaderUrl";
UPDATE `Zf_configuration` SET `description` = "Default behavior, for „empty target“ checkbox in the repetition editor (auto-propagate): Only replace repetition automatically / propose replacement of repetition, if target is empty. This is the default behavior that can be changed by the user." WHERE `name` = "runtimeOptions.alike.showOnEmptyTarget";
UPDATE `Zf_configuration` SET `description` = "List of ip addresses with map to customer for ip based authentication. Example where the users coming from 192.168.2.143 are assigned to a customer with number 1000 :{\"192.168.2.143\" : \"1000\"}." WHERE `name` = "runtimeOptions.authentication.ipbased.IpCustomerMap";
UPDATE `Zf_configuration` SET `description` = "User roles that should be assigned to users that authenticate via IP." WHERE `name` = "runtimeOptions.authentication.ipbased.userRoles";
UPDATE `Zf_configuration` SET `description` = "Enables batch query requests for pre-translations only for the associated language resource that supports batch query. Batch query is much faster for many language resources for imports and InstantTranslate" WHERE `name` = "runtimeOptions.LanguageResources.Pretranslation.enableBatchQuery";
UPDATE `Zf_configuration` SET `description` = "Defines how the unit of measurement size is used for length calculation." WHERE `name` = "runtimeOptions.lengthRestriction.sizeUnit";
UPDATE `Zf_configuration` SET `description` = "Contains the name of a font-family, e.g. \"Arial\" or \"Times New Roman\", that refers to the pixel-mapping.xlsx file (see documentation in Translate5 confluence)" WHERE `name` = "runtimeOptions.lengthRestriction.pixelmapping.font";
UPDATE `Zf_configuration` SET `description` = "show Consortium Logos on application load for xyz seconds [default 3]. time counts after the application is loaded completely. if set to 0, the consortium logos are not shown at all." WHERE `name` = "runtimeOptions.startup.showConsortiumLogos";
UPDATE `Zf_configuration` SET `description` = "JSON content of the API-Keyfile to authenticate with the google cloud vision API." WHERE `name` = "runtimeOptions.plugins.VisualReview.googleCloudApiKey";
UPDATE `Zf_configuration` SET `description` = "Max parallel running workers of the TermTagger removal worker." WHERE `name` = "runtimeOptions.worker.editor_Plugins_TermTagger_Worker_Remove.maxParallelWorkers";
UPDATE `Zf_configuration` SET `description` = "If value is selected, InstantTranslate translations will be saved in a separate TM of the selected type(s)." WHERE `name` = "runtimeOptions.InstantTranslate.saveToServices";
UPDATE `Zf_configuration` SET `description` = "When set to active, no workflow emails will be sent." WHERE `name` = "runtimeOptions.workflow.disableNotifications";
UPDATE `Zf_configuration` SET `description` = "Refers to import processes. List one or multiple URLs, where LanguageTool-instances can be reached for segment target text spell checking. Translate5 does load balancing, if more than one is configured." WHERE `name` = "runtimeOptions.plugins.SpellCheck.languagetool.url.import";
UPDATE `Zf_configuration` SET `description` = "Type of repetitions that should be propagated in case of propagation behavior is 'always'. Possible values: 'source', 'target', 'bothAnd', 'bothOr' - they refer to when automatic replacements are made with 'always'-behavior. " WHERE `name` = "runtimeOptions.alike.repetitionType";
UPDATE `Zf_configuration` SET `description` = "Default behavior, for \"Same context only\" checkbox in the repetition editor (auto-propagate): Only replace repetitions of the same context, e.g. having same content for their previous and next segments. This is the default behavior that can be changed by the user." WHERE `name` = "runtimeOptions.alike.sameContextOnly";
