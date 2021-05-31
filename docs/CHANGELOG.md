# Change Log

All notable changes to translate5 will be documented here.

For a reference to the issue keys see http://jira.translate5.net

Missing Versions are merged into in the next upper versions, so no extra section is needed.

All updates are (downwards) compatible! If not this is listed in the important release notes.








## [5.2.0] - 2021-05-31

### Important Notes:
#### [TRANSLATE-2417](https://jira.translate5.net/browse/TRANSLATE-2417)
The default enabled for configuration of language resources is now split up into read default rights and write default rights, so that reading and writing is configurable separately. The write default right is not automatically set for existing language resources.
The old API field "resourcesCustomersHidden" in language-resources to customers association will no longer be supported. It was marked as deprecated since April 2020. Please use only customerUseAsDefaultIds from now on.

#### [TRANSLATE-2410](https://jira.translate5.net/browse/TRANSLATE-2410)
This was no bug in translate5 - everything correct here - but a problem with Firefox on Windows for Korean and Vietnamese, preventing the users to enter Asiatic characters. Translate5 users with Korean or Vietnamese target language will get a warning message now, that they should switch to Chrome or Edge.

#### [TRANSLATE-2315](https://jira.translate5.net/browse/TRANSLATE-2315)
Added a filter in the segments grid to filter for repeated segments. For the already imported tasks some data must be recalculated, so the database migration could need some time. The corresponding database migration script can be restarted if it should stop unexpected.

#### [TRANSLATE-2196](https://jira.translate5.net/browse/TRANSLATE-2196)
Introducing a new Quality Assurance, for details see below change log entry.
The REST API Endpoint for the download of quality statistics (MQM subsegment tags) was renamed from "editor/qmstatistics" to "editor/quality/downloadstatistics"

#### [TRANSLATE-1643](https://jira.translate5.net/browse/TRANSLATE-1643)
Introduced new processing state (AutoStatus) "pretranslated".
This state is used for segments pre-translated in translate5, but also for imported segments which provide such information. For example SDLXLIFF: 
if edit100%percentMatch is disabled, used full TM matches not edited in Trados manually are not editable. So edited 100% matches are editable in translate5 by the reviewer now. Not changed has the behaviour for auto-propagated segments and segments with a match-rate < 100%: they are still editable as usual.

#### [TRANSLATE-1481](https://jira.translate5.net/browse/TRANSLATE-1481)
The TMX files imported into OpenTM2 are modified. The internal tags are modified (removing type attribute and convert tags with content to single placeholder tags) to improve matching when finding segments.
 


### Added
**[TRANSLATE-2417](https://jira.translate5.net/browse/TRANSLATE-2417): OpenTM: writeable by default** <br>
The default enabled for configuration of language resources is now split up into read default rights and write default rights, so that reading and writing is configurable separately. The write default right is not automatically set for existing language resources.
The old API field "resourcesCustomersHidden" in language-resources to customers association will no longer be supported. It was marked as deprecated since April 2020. Please use only customerUseAsDefaultIds from now on.

**[TRANSLATE-2315](https://jira.translate5.net/browse/TRANSLATE-2315): Filtering for Repeated segments in translate5s editor** <br>
Added a filter in the segments grid to filter for repeated segments.

**[TRANSLATE-2196](https://jira.translate5.net/browse/TRANSLATE-2196): Complete Auto QA for translate5** <br>
Introduces a new Quality Assurance:

* Panel to filter the Segment Grid by quality
* GUI to set evaluated quality errors as "false positives"
* Improved panels to set the manual QA for the whole segment and within the segment (now independant from saving edited segment content)
* Automatic evaluation of several quality problems (AutoQA)

For an overview how to use the new feature, please see https://confluence.translate5.net/pages/viewpage.action?pageId=557218

For an overview of the new REST API, please see https://confluence.translate5.net/pages/viewpage.action?pageId=256737288

**[TRANSLATE-2077](https://jira.translate5.net/browse/TRANSLATE-2077): Offer export of Trados-Style analysis xml** <br>
The match analysis report can be exported now in a widely usable XML format.


### Changed
**[TRANSLATE-2494](https://jira.translate5.net/browse/TRANSLATE-2494): Plugins enabled by default** <br>
Enables ModelFront, IpAuthentication and PangeaMt plugins to be active by default.

**[TRANSLATE-2481](https://jira.translate5.net/browse/TRANSLATE-2481): Enable default deadline in configuration to be also decimal values (number of days in the future)** <br>
Default deadline date configuration accepts decimal number as configuration. You will be able to define 1 and a half day for the deadline when setting the config to 1.5

**[TRANSLATE-2473](https://jira.translate5.net/browse/TRANSLATE-2473): Show language names in language drop downs in InstantTranslate** <br>
The languages drop-down in instant translate will now show the full language name + language code


### Bugfixes
**[TRANSLATE-2527](https://jira.translate5.net/browse/TRANSLATE-2527): Remove instant-Translate default rest api routes** <br>
The default rest-routes in instant translate are removed.

**[TRANSLATE-2517](https://jira.translate5.net/browse/TRANSLATE-2517): NULL as string in Zf_configuration defaults instead real NULL values** <br>
Some default values in the configuration are not as expected.

**[TRANSLATE-2515](https://jira.translate5.net/browse/TRANSLATE-2515): Remove the limit from customers drop-down** <br>
Fixes the customer limit in language resources customers combobox.

**[TRANSLATE-2511](https://jira.translate5.net/browse/TRANSLATE-2511): PHP error on deleting tasks** <br>
Fixed seldom problem on deleting tasks:
ERROR in core: E9999 - Argument 1 passed to editor_Models_Task_Remover::cleanupProject() must be of the type int, null given

**[TRANSLATE-2509](https://jira.translate5.net/browse/TRANSLATE-2509): Bugfix: target "_blank" in Links in the visual review causes unwanted popups with deactivated links** <br>
External Links opening a new window still cause unwanted popups in the Visual Review

**[TRANSLATE-2499](https://jira.translate5.net/browse/TRANSLATE-2499): Search window saved position can be moved outside of the viewport** <br>
Search window saved position can be moved outside of the viewport and the user is then not able to move it back. This is fixed now for the search window, for other windows the bad position is not saved, so after reopening the window it is accessible again.
Also fixed logged configuration changes, always showing old value the system value instead the overwritten level value.

**[TRANSLATE-2496](https://jira.translate5.net/browse/TRANSLATE-2496): Enable target segmentation in Okapi** <br>
So far target segmentation had not been activated in okapi segmentation settings. For PO files with partly existing target this let to <mrk>-segment tags in the source, but not in the target and thus to an import error in translate5. This is changed now.


**[TRANSLATE-2484](https://jira.translate5.net/browse/TRANSLATE-2484): Buffered grid "ensure visible" override** <br>
Fixes problems with the segment grid.

**[TRANSLATE-2482](https://jira.translate5.net/browse/TRANSLATE-2482): Serialization failure: 1213 Deadlock found when trying to get lock** <br>
Fixes update worker progress mysql deadlock.

**[TRANSLATE-2480](https://jira.translate5.net/browse/TRANSLATE-2480): Instant-translate expired user session** <br>
On expired session, the user will be redirected to the login page in instant translate or term portal.

**[TRANSLATE-2478](https://jira.translate5.net/browse/TRANSLATE-2478): Add missing languages** <br>
Adds additional languages: 
sr-latn-rs, so-so, am-et, es-419, rm-ch, es-us, az-latn-az, uz-latn-uz, bs-latn-ba

**[TRANSLATE-2455](https://jira.translate5.net/browse/TRANSLATE-2455): Empty Segment Grid after opening a task** <br>
Fixing a seldom issue where the segment grid remains empty after opening a task.

**[TRANSLATE-2439](https://jira.translate5.net/browse/TRANSLATE-2439): prevent configuration mismatch on level task-import** <br>
Task import specific configurations are now fixed after the task import and can neither be changed for the rest of the task's lifetime nor can they be overwritten otherwise

**[TRANSLATE-2410](https://jira.translate5.net/browse/TRANSLATE-2410): Add Warning for users editing Korean, Vietnamese or Japanese tasks when working with Firefox** <br>
This was no bug in translate5 - everything correct here - but a problem with Firefox on Windows for Korean and Vietnamese, preventing the users to enter Asiatic characters. Translate5 users with Korean or Vietnamese target language will get a warning message now, that they should switch to Chrome or Edge.

**[TRANSLATE-1643](https://jira.translate5.net/browse/TRANSLATE-1643): A separate autostatus pretranslated is missing for pretranslation** <br>
Introduced new processing state (AutoStatus) "pretranslated".
This state is used for segments pre-translated in translate5, but also for imported segments which provide such information. For example SDLXLIFF: 
if edit100%percentMatch is disabled, used full TM matches not edited in Trados manually are not editable. So edited 100% matches are editable in translate5 by the reviewer now. Not changed has the behaviour for auto-propagated segments and segments with a match-rate < 100%: they are still editable as usual.

**[TRANSLATE-1481](https://jira.translate5.net/browse/TRANSLATE-1481): Improve tag handling with matches coming from OpenTM2** <br>
The TMX files imported into OpenTM2 are modified. The internal tags are modified (removing type attribute and convert tags with content to single placeholder tags) to improve matching when finding segments.


## [5.1.3] - 2021-04-15

### Important Notes:
 


### Added
**[TRANSLATE-2363](https://jira.translate5.net/browse/TRANSLATE-2363): Development tool session:impersonate accessible via api** <br>
Enables an API user to authenticate in a name of different user. This feature is only available via translate5 API and for users with api role. More info you can find here : https://confluence.translate5.net/display/TAD/Session


### Bugfixes
**[TRANSLATE-2471](https://jira.translate5.net/browse/TRANSLATE-2471): Auto-assigned users and deadline-date** <br>
Fixes missing deadline date for auto assigned users.

**[TRANSLATE-2470](https://jira.translate5.net/browse/TRANSLATE-2470): Errors on log mail delivery stops whole PHP process** <br>
Errors on log e-mail delivery stops whole application process and leads to additional errors. The originating error is not logged in the translate5 log, only in the PHP log.

**[TRANSLATE-2468](https://jira.translate5.net/browse/TRANSLATE-2468): Instant-translate custom title** <br>
Enables instant-translate custom title definition in client-specific locales.

**[TRANSLATE-2467](https://jira.translate5.net/browse/TRANSLATE-2467): RootCause Error "Cannot read property 'nodeName' of null"** <br>
Fixed Bug in TrackChanges when editing already edited segments

**[TRANSLATE-2465](https://jira.translate5.net/browse/TRANSLATE-2465): Add version parameter to instanttranslate and termportal assets** <br>
The web assets (CSS and JS files) were not probably updated in termportal and instanttranslate after an update.

**[TRANSLATE-2464](https://jira.translate5.net/browse/TRANSLATE-2464): Tag protection feature does not work if content contains XML comments or CDATA blocks** <br>
The tag protection feature was not working properly if the content contains XML comments or CDATA blocks.

**[TRANSLATE-2463](https://jira.translate5.net/browse/TRANSLATE-2463): Match analysis and batch worker fix** <br>
Fixes that machine translation engines were queried to often with enabled batch quries and projects with multiple target languages and some other minor problems with match analysis and batch query workers.

**[TRANSLATE-2461](https://jira.translate5.net/browse/TRANSLATE-2461): Non Public Plugin Classes referenced in public code** <br>
Pure public translate5 installations were not usable due a code reference to non public code.

**[TRANSLATE-2459](https://jira.translate5.net/browse/TRANSLATE-2459): Segments grid scroll-to uses private function** <br>
Segments grid scroll to segment function improvement.

**[TRANSLATE-2458](https://jira.translate5.net/browse/TRANSLATE-2458): Reenable logout on window close also for open id users** <br>
Currently the logout on window close feature is not working for users logging in via OpenID connect.

**[TRANSLATE-2457](https://jira.translate5.net/browse/TRANSLATE-2457): Globalese engines string IDs crash translate5 task import wizard** <br>
Globalese may return also string based engine IDs, translate5 was only supporting integer ids so far.

**[TRANSLATE-2431](https://jira.translate5.net/browse/TRANSLATE-2431): Errors on update with not configured mail server** <br>
If there is no e-mail server configured, the update shows an error due missing SMTP config.


## [5.1.2] - 2021-03-31

### Important Notes:
#### [TRANSLATE-2442](https://jira.translate5.net/browse/TRANSLATE-2442)
Adding a repetition column in language resource usage log excel.

#### [TRANSLATE-2435](https://jira.translate5.net/browse/TRANSLATE-2435)
Attention: Backwards-incompatible change: The mail address of the project manager of a task is now added as reply-to mail address to all mails, that are automatically send in the workflow (assignment notifications, mails on workflow step finish, deadline reminders). The main system mail address stays as sender mail address.
If you  do not want this, do not upgrade, but get in touch with translate5s development team.

#### [TRANSLATE-2432](https://jira.translate5.net/browse/TRANSLATE-2432)
The default okapi bconf files now can be defined as config:
 - runtimeOptions.plugins.Okapi.import.okapiBconfDefaultName
 - runtimeOptions.plugins.Okapi.export.okapiBconfDefaultName
All bconf files defined in the Okapi/data folder, will be listed as selectable config value.

#### [TRANSLATE-2375](https://jira.translate5.net/browse/TRANSLATE-2375)
For each workflow step, you can define in the configuration(system, customer and task-import defaults) how many days(weekends will be skipped in the calculation) after the order date, the deadline date will be.

#### [TRANSLATE-2248](https://jira.translate5.net/browse/TRANSLATE-2248)
IMPORTANT: The "visualReview" folder in the zip import package is deprecated from now on. In the future please always use the new folder "visual" instead. All files that need to be reviewed or translated will have to be placed in the new folder "visual" from now on. In some future version of translate5 the support for "visualReview" folder will be completely removed. Currently it still is supported, but will write a "deprecated" message to the php error-log.

#### [TRANSLATE-1596](https://jira.translate5.net/browse/TRANSLATE-1596)
IMPORTANT: The "proofRead" folder in the zip import package is deprecated from now on. In the future please always use the new folder "workfiles" instead. All files that need to be reviewed or translated will have to be placed in the new folder "workfiles" from now on. In some future version of translate5 the support for "proofRead" folder will be completely removed. Currently it still is supported, but will write a "deprecated" message to the php error-log.
 


### Added
**[TRANSLATE-2412](https://jira.translate5.net/browse/TRANSLATE-2412): Create a shortcut to directly get into the concordance search bar** <br>
New editor shortcut (F3) to get the cursor in "concordance search" source field.

**[TRANSLATE-2375](https://jira.translate5.net/browse/TRANSLATE-2375): Set default deadline per workflow step in configuration** <br>
Define default deadline date for task-user association

**[TRANSLATE-2342](https://jira.translate5.net/browse/TRANSLATE-2342): Show progress of document translation** <br>
Import progress bar in instant translate file translation and in the task overview.


### Changed



FIX: Newlines may have been rendered twice in case of internal tags representing newlines

**[TRANSLATE-2446](https://jira.translate5.net/browse/TRANSLATE-2446): Fonts Management for Visual: Add search capabilities by name / taskname** <br>
ENHANCEMENT: Added search-field to search for fonts by task name in the font management

**[TRANSLATE-2440](https://jira.translate5.net/browse/TRANSLATE-2440): Project task backend tests** <br>
Implement API tests testing the import of multiple tasks bundled in a project (one source language, multiple target languages).

**[TRANSLATE-2424](https://jira.translate5.net/browse/TRANSLATE-2424): Add Language as label under Language Flag image** <br>
TermPortal - added language label to language flag to display RFC language.

**[TRANSLATE-2350](https://jira.translate5.net/browse/TRANSLATE-2350): Make configurable if pivot language should be available in add task wizard** <br>
The availability / visibility of the pivot language in the add task wizard can be configured in the configuration for each customer now.

**[TRANSLATE-2248](https://jira.translate5.net/browse/TRANSLATE-2248): Change name of "visualReview" folder to "visual"** <br>
The "visualReview" folder in the zip import package is deprecated from now on. In the future please always use the new folder "visual" instead. All files that need to be reviewed or translated will have to be placed in the new folder "visual" from now on. In some future version of translate5 the support for "visualReview" folder will be completely removed. Currently it still is supported, but will write a "deprecated" message to the php error-log.

**[TRANSLATE-1925](https://jira.translate5.net/browse/TRANSLATE-1925): BUG: Workers running parallelism is not implemented correctly** <br>
Enhancement: Setting more workers to "waiting" in the "wakeupScheduled" call independently of the calling worker to improve the parallelism of running workers

**[TRANSLATE-1596](https://jira.translate5.net/browse/TRANSLATE-1596): Change name of "proofRead" folder to "workfiles"** <br>
The "proofRead" folder in the zip import package is deprecated from now on. In the future please always use the new folder "workfiles" instead. All files that need to be reviewed or translated will have to be placed in the new folder "workfiles" from now on. In some future version of translate5 the support for "proofRead" folder will be completely removed. Currently it still is supported, but will write a "deprecated" message to the php error-log.


### Bugfixes
**[TRANSLATE-2456](https://jira.translate5.net/browse/TRANSLATE-2456): Quote in task name produces error** <br>
Fixed problem with language resources to task association when the task name contains single or double quotes.

**[TRANSLATE-2454](https://jira.translate5.net/browse/TRANSLATE-2454): Configuration userCanModifyWhitespaceTags is not loaded properly** <br>
Users were not able to save segments with changed whitespace tags, since the corresponding configuration which allows this was not loaded properly.

**[TRANSLATE-2453](https://jira.translate5.net/browse/TRANSLATE-2453): Fix unescaped control characters in language resource answers** <br>
Solving the the following error coming from OpenTM2: ERROR in editor.languageresource.service.connector: E1315 - JSON decode error: Control character error, possibly incorrectly encoded

**[TRANSLATE-2451](https://jira.translate5.net/browse/TRANSLATE-2451): Fix description text of lock segment checkbox and task column** <br>
Clarify that feature "Locked segments in the imported file are also locked in translate5" is for SDLXLIFF files only.

**[TRANSLATE-2449](https://jira.translate5.net/browse/TRANSLATE-2449): Grid grouping feature collapse/expand error** <br>
Fixes error with collapse/expand in locally filtered config grid.

**[TRANSLATE-2448](https://jira.translate5.net/browse/TRANSLATE-2448): Unable to refresh entity after save** <br>
Fixing an error which may occur when using pre-translation with enabled batch mode of language resources.

**[TRANSLATE-2445](https://jira.translate5.net/browse/TRANSLATE-2445): Unknown bullet prevents proper segmentation** <br>
FIX: Added some more bullet characters to better filter out list markup during segmentation
FIX: Priorize longer segments during segmentation to prevent segments containing each other (e.g. "Product XYZ", "Product XYZ is good") can not be found properly.

**[TRANSLATE-2442](https://jira.translate5.net/browse/TRANSLATE-2442): Disabled connectors and repetitions** <br>
Fixing a problem with repetitions in match analysis and pre-translation context, also a repetition column is added in resource usage log excel export.

**[TRANSLATE-2441](https://jira.translate5.net/browse/TRANSLATE-2441): HTML Cleanup in Visual Review way structurally changed internal tags** <br>
FIXED: Segments with interleaving term-tags and internal-tags may were not shown properly in the visual review (parts of the text missing).

**[TRANSLATE-2438](https://jira.translate5.net/browse/TRANSLATE-2438): Fix plug-in XlfExportTranslateByAutostate for hybrid usage of translate5** <br>
The XlfExportTranslateByAutostate plug-in was designed for t5connect only, a hybrid usage of tasks directly uploaded and exported to and from translate5 was not possible. This is fixed now.

**[TRANSLATE-2435](https://jira.translate5.net/browse/TRANSLATE-2435): Add reply-to with project-manager mail to all automated workflow-mails** <br>
In all workflow mails, the project manager e-mail address is added as reply-to mail address.

**[TRANSLATE-2433](https://jira.translate5.net/browse/TRANSLATE-2433): file extension XLF can not be handled - xlf can** <br>
Uppercase file extensions (XLF instead xlf) were not imported. This is fixed now.

**[TRANSLATE-2432](https://jira.translate5.net/browse/TRANSLATE-2432): Make default bconf path configurable** <br>
More flexible configuration for Okapi import/export .bconf files changeable per task import.

**[TRANSLATE-2428](https://jira.translate5.net/browse/TRANSLATE-2428): Blocked segments and task word count** <br>
Include or exclude the blocked segments from task total word count and match-analysis when enabling or disabling  "100% matches can be edited" task flag.

**[TRANSLATE-2427](https://jira.translate5.net/browse/TRANSLATE-2427): Multiple problems with worker related to match analsis and pretranslation** <br>
A combination of multiple problems led to hanging workers when importing a project with multiple targets and activated pre-translation.

**[TRANSLATE-2426](https://jira.translate5.net/browse/TRANSLATE-2426): Term-tagging with default term-collection** <br>
term-tagging was not done with term collection assigned as default for the project-task customer

**[TRANSLATE-2425](https://jira.translate5.net/browse/TRANSLATE-2425): HTML Import does not work properly when directPublicAccess not set** <br>
FIX: Visual Review does not show files from subfolders of the review-directory when directPublicAccess is not active (Proxy-access)

**[TRANSLATE-2423](https://jira.translate5.net/browse/TRANSLATE-2423): Multicolumn CSV import was not working anymore in some special cases** <br>
Multicolumn CSV import with multiple files and different target columns was not working anymore, this is fixed now.

**[TRANSLATE-2421](https://jira.translate5.net/browse/TRANSLATE-2421): Worker not started due maintenance should log to the affected task** <br>
If a worker is not started due maintenance, this should be logged to the affected task if possible.

**[TRANSLATE-2420](https://jira.translate5.net/browse/TRANSLATE-2420): Spelling mistake: Task finished, E-mail template** <br>
Spelling correction.

**[TRANSLATE-2413](https://jira.translate5.net/browse/TRANSLATE-2413): Wrong E-Mail encoding leads to SMTP error with long segments on some mail servers** <br>
When finishing a task an email is sent to the PM containing all edited segments. If there are contained long segments, or segments with a lot of tags with long content, this may result on some mail servers in an error. 

**[TRANSLATE-2411](https://jira.translate5.net/browse/TRANSLATE-2411): Self closing g tags coming from Globalese pretranslation can not be resolved** <br>
Globalese receives a segment with a <g>tag</g> pair, but returns it as self closing <g/> tag, which is so far valid XML but could not be resolved by the reimport of the data.

**[TRANSLATE-2325](https://jira.translate5.net/browse/TRANSLATE-2325): TermPortal: Do not show unknown tag name in the attribute header.** <br>
Do not show tag name any more in TermPortal for unkown type-attribute values and other attribute values

**[TRANSLATE-2256](https://jira.translate5.net/browse/TRANSLATE-2256): Always activate button "Show/Hide TrackChanges"** <br>
Show/hide track changes checkbox will always be available (no matter on the workflow step)

**[TRANSLATE-198](https://jira.translate5.net/browse/TRANSLATE-198): Open different tasks if editor is opened in multiple tabs** <br>
The user will no longer be allowed to edit 2 different tasks using 2 browser tabs. 


## [5.1.1] - 2021-02-17

### Important Notes:
#### [TRANSLATE-2391](https://jira.translate5.net/browse/TRANSLATE-2391)
The "Complete task?" text in the pop-up dialog was changed since it was confusing.

#### [TRANSLATE-1484](https://jira.translate5.net/browse/TRANSLATE-1484)
Attention: The numbers exported by this new feature are only collected from the point in time onwards, when translate5 is updated to the release with this feature.
To configure log auto clean, define how many days the log should stay in the database in runtimeOptions.LanguageResources.usageLogger.logLifetime.
 


### Added
**[TRANSLATE-1484](https://jira.translate5.net/browse/TRANSLATE-1484): Count translated characters by MT engine and customer** <br>
Enables language resources usage log and statistic export.


### Changed
**[TRANSLATE-2407](https://jira.translate5.net/browse/TRANSLATE-2407): Embed new configuration help window** <br>
The brand-new help videos about the configuration possibilities are available now and embedded in the application as help pop-up.

**[TRANSLATE-2402](https://jira.translate5.net/browse/TRANSLATE-2402): Remove rights for PMs to change instance defaults for configuration** <br>
The PM will not longer be able to modify instance level configurations, only admin users may do that.

**[TRANSLATE-2379](https://jira.translate5.net/browse/TRANSLATE-2379): Workflow mails: Show only changed segments** <br>
Duplicating TRANSLATE-1979


### Bugfixes
**[TRANSLATE-2406](https://jira.translate5.net/browse/TRANSLATE-2406): Translated text is not replaced with translation but concatenated** <br>
FIX: Solved problem where the Live editing did not remove the original text completely when replacing it with new contents

**[TRANSLATE-2403](https://jira.translate5.net/browse/TRANSLATE-2403): Visual Review: Images are missing, the first Image is not shown in one Iframe** <br>
FIX: A downloaded Website for the Visual Review may not show responsive images when they had a source set defined
FIX: Elements with a background-image set by inline style in a downloaded website for the Visual Review may not show the background image
FIX: Some images were not shown either in the original iframe or the WYSIWIG iframe in a Visual Review
ENHANCEMENT: Focus-styles made the current page hard to see in the Visual Review pager 

**[TRANSLATE-2401](https://jira.translate5.net/browse/TRANSLATE-2401): DeepL formality fallback** <br>
Formality will be set to "default" for resources with unsupported target languages.

**[TRANSLATE-2396](https://jira.translate5.net/browse/TRANSLATE-2396): Diverged GUI and Backend version after update** <br>
The user gets an error message if the version of the GUI is older as the backend - which may happen after an update in certain circumstances. Normally this is handled due the usage of the maintenance mode.

**[TRANSLATE-2391](https://jira.translate5.net/browse/TRANSLATE-2391): "Complete task?". Text in dialog box is confusing.** <br>
The "Complete task?" text in the pop-up dialog was changed since it was confusing.

**[TRANSLATE-2390](https://jira.translate5.net/browse/TRANSLATE-2390): TermImport plug-in matches TermCollection name to non-Termcollection-type languageresources** <br>
The termImport plug-in imports a TBX into an existing termCollection, if the name is the same as the one specified in the plug-in config file. Although the language resource type was not checked, so this led to errors if the found language resource was not of type term collection.

**[TRANSLATE-1979](https://jira.translate5.net/browse/TRANSLATE-1979): Do not list "changes" of translator in mail send after finish of translation step** <br>
The changed segments will not longer be listed in the notification mails after translation step is finished - since all segments were changed here.


## [5.1.0] - 2021-02-02

### Important Notes:

#### [TRANSLATE-471](https://jira.translate5.net/browse/TRANSLATE-471)
VERY IMPORTANT: The configuration of translate5 is changed fundamentally. 
The installation.ini is cleaned up to the values which are intended to be there: Basic DB, Mailing and Logging configuration.
All other configurations made in the installation.ini are adopted automatically into the database based configuration, now maintainable via the GUI or the translate5 CLI command. A backup of the installation.ini will be made as installation.ini.bak for reference, since comments and out-commented configurations are not overtaken.
For CSV Users: 
For config "runtimeOptions.import.csv.fields.mid" the "value" and the "default" are changed to "id" (default/value was mid). 
For the config "runtimeOptions.import.csv.fields.source" the "value" and "default" are changed to source (default/value was quelle).

#### [TRANSLATE-2362](https://jira.translate5.net/browse/TRANSLATE-2362)
For CSV file importers: the protection of HTML tags in the CSV content is now configurable (tag protection config) and is disabled by default.

#### [TRANSLATE-2354](https://jira.translate5.net/browse/TRANSLATE-2354)
For API Users: ensure that the changed filename has no effect on your api usage. The URL for downloading remains the same.

#### [TRANSLATE-2311](https://jira.translate5.net/browse/TRANSLATE-2311)
Important for users which are using an own task administration: test if the integration works, if not the samesite cookie config in application.ini must be removed and this issue reopened!

#### [TRANSLATE-929](https://jira.translate5.net/browse/TRANSLATE-929)
The usage of the task template is not supported any more after the release of this issue. Instead the configurations that so far had been supported by the task template now can be made in the system configuration in the GUI and can be customized/overwritten on client level and task-import level.
As fallback a task-config.ini file can be placed in the import package, containing task-configuration in an INI style: config.name = value.


### Added
**[TRANSLATE-2385](https://jira.translate5.net/browse/TRANSLATE-2385): introduce user login statistics** <br>
Now the login usage of the users is tracked in the new Zf_login_log table.

**[TRANSLATE-2374](https://jira.translate5.net/browse/TRANSLATE-2374): Time of deadlines also visible in grid columns and notification mails** <br>
The date-time is now visible in the translate5 interface for date fields(if the time is relevant for this date field), and also in the mail templates.

**[TRANSLATE-2362](https://jira.translate5.net/browse/TRANSLATE-2362): HTML / XML tag protection of tags in any kind of file format** <br>
XLF and CSV files can now contain HTML content (CSV: plain, XLF: encoded), the  HTML tags are protected as internal tags. This must be enabled in the config for the affected tasks.

**[TRANSLATE-471](https://jira.translate5.net/browse/TRANSLATE-471): Overwrite system config by client and task** <br>
Adds possibility to overwrite system configuration on 4 different levels: system, client, task import and task overwrite,


### Changed
**[TRANSLATE-2368](https://jira.translate5.net/browse/TRANSLATE-2368): Add segment matchrate to Xliff 2 export as translate5 namespaced element** <br>
Each segment in the xliff 2 export will have the segment matchrate as translate5 namespace attribute.

**[TRANSLATE-2357](https://jira.translate5.net/browse/TRANSLATE-2357): introduce DeepL config switch "formality"** <br>
The "formality" deepl api flag now is available as task import config.
More about the formality flag:

Sets whether the translated text should lean towards formal or informal language. This feature currently works for all target languages except "EN" (English), "EN-GB" (British English), "EN-US" (American English), "ES" (Spanish), "JA" (Japanese) and "ZH" (Chinese).
Possible options are:
"default" (default)
"more" - for a more formal language
"less" - for a more informal language

**[TRANSLATE-2354](https://jira.translate5.net/browse/TRANSLATE-2354): Add language code to filename of translate5 export zip** <br>
When exporting a task, in the exported zip file name, the task source and target language codes are included.

**[TRANSLATE-1120](https://jira.translate5.net/browse/TRANSLATE-1120): Change default values of several configuration parameters** <br>
The default value in multiple system configurations is changed.

**[TRANSLATE-929](https://jira.translate5.net/browse/TRANSLATE-929): Move old task template values to new system overwrite** <br>
The task template parameters definition moved to system configuration.


### Bugfixes
**[TRANSLATE-2384](https://jira.translate5.net/browse/TRANSLATE-2384): Okapi does not always fill missing targets with source content** <br>
In some use cases only a few segments are translated, and on export via Okapi the not translated segments are filled up by copying the source content to target automatically. This copying was failing for specific segments.

**[TRANSLATE-2382](https://jira.translate5.net/browse/TRANSLATE-2382): ERROR in core.api.filter: E1223 - Illegal field "customerUseAsDefaultIds" requested** <br>
Sometimes it may happen that a filtering for customers used as default in the language resource grid leads to the above error message. This is fixed now.

**[TRANSLATE-2373](https://jira.translate5.net/browse/TRANSLATE-2373): Prevent termtagger usage if source and target language are equal** <br>
FIX: Prevent termtagger hanging when source and target language of a task are identical. Now in these cases the terms are not tagged anymore

**[TRANSLATE-2372](https://jira.translate5.net/browse/TRANSLATE-2372): Whitespace not truncated InstantTranslate text input field** <br>
All newlines, spaces (including non-breaking spaces), and tabs are removed from the beginning and the end of the searched string in instant translate.

**[TRANSLATE-2367](https://jira.translate5.net/browse/TRANSLATE-2367): NoAccessException directly after login** <br>
Opening Translate5 with an URL containing a task to be opened for editing leads to ZfExtended_Models_Entity_NoAccessException exception if the task was already finished or still in state waiting instead of opening the task in read only mode.

**[TRANSLATE-2365](https://jira.translate5.net/browse/TRANSLATE-2365): Help window initial size** <br>
On smaller screens the close button of the help window (and also the "do not show again" checkbox) were not visible.

**[TRANSLATE-2352](https://jira.translate5.net/browse/TRANSLATE-2352): Visual: Repetitions are linked to wrong position in the layout** <br>
FIXED: Problem in Visual Review that segments pointing to multiple occurances in the visual review always jumped to the first occurance when clicking on the segment in the segment grid. Now the current context (position of segment before, scroll-position of review) is taken into account

**[TRANSLATE-2351](https://jira.translate5.net/browse/TRANSLATE-2351): Preserve "private use area" of unicode characters in visual review and ensure connecting segments** <br>
Characters of the Private Use Areas (as used in some symbol fonts e.g.) are now preserved in the Visual Review layout

**[TRANSLATE-2335](https://jira.translate5.net/browse/TRANSLATE-2335): Do not query MT when doing analysis in batch mode without MT pre-translation** <br>
When the MT pre-translation checkbox is not checked in the match analysis overview, and batch query is enabled, all associated MT resources will not be used for batch query.

**[TRANSLATE-2311](https://jira.translate5.net/browse/TRANSLATE-2311): Cookie Security** <br>
Set the authentication cookie according to the latest security recommendations.

**[TRANSLATE-2383](https://jira.translate5.net/browse/TRANSLATE-2383): OpenTM2 workaround to import swiss languages** <br>
Since OpenTM2 is not capable of importing sub languages we have to provide fixes on demand. Here de-CH, it-CH and fr-CH are fixed. 


## [5.0.15] - 2020-12-21

### Important Notes:
#### [TRANSLATE-2336](https://jira.translate5.net/browse/TRANSLATE-2336)
Now language resources of the same customer and with a sub-language (de-de, de-at) are also added automatically to tasks using only the base language (de).
 


### Added
**[TRANSLATE-2249](https://jira.translate5.net/browse/TRANSLATE-2249): Length restriction for sdlxliff files** <br>
SDLXLIFF specific length restrictions are now read out and used for internal processing.


### Changed
**[TRANSLATE-2343](https://jira.translate5.net/browse/TRANSLATE-2343): Enhance links from default skin to www.translate5.net** <br>
Change links from default skin to www.translate5.net

**[TRANSLATE-390](https://jira.translate5.net/browse/TRANSLATE-390): Prevent that the same error creates a email on each request to prevent log spam** <br>
Implemented the code base to recognize duplicated errors and prevent sending error mails.


### Bugfixes
**[TRANSLATE-2353](https://jira.translate5.net/browse/TRANSLATE-2353): OpenTM2 strange matching of single tags** <br>
In the communication with OpenTM2 the used tags are modified to improve found matches.

**[TRANSLATE-2346](https://jira.translate5.net/browse/TRANSLATE-2346): Wrong Tag numbering on using language resources** <br>
If a segment containing special characters and is taken over from a language resource, the tag numbering could be messed up. This results then in false positive tag errors.

**[TRANSLATE-2339](https://jira.translate5.net/browse/TRANSLATE-2339): OpenTM2 can not handle  datatype="unknown" in TMX import** <br>
OpenTM2 does not import any segments from a TMX, that has  datatype="unknown" in its header tag, this is fixed by modifying the TMX on upload.

**[TRANSLATE-2338](https://jira.translate5.net/browse/TRANSLATE-2338): Use ph tag in OpenTM2 to represent line-breaks** <br>
In the communication with OpenTM2 line-breaks are converted to ph type="lb" tags, this improves the matchrates for affected segments.

**[TRANSLATE-2336](https://jira.translate5.net/browse/TRANSLATE-2336): Auto association of language resources does not use language fuzzy match** <br>
Now language resources with a sub-language (de-de, de-at) are also added to tasks using only the base language (de). 

**[TRANSLATE-2334](https://jira.translate5.net/browse/TRANSLATE-2334): Pressing ESC while task is uploading results in task stuck in status import** <br>
Escaping from task upload window while uploading is now prevented.

**[TRANSLATE-2332](https://jira.translate5.net/browse/TRANSLATE-2332): Auto user association on task import does not work anymore** <br>
Auto associated users are added now again, either as translators or as revieweres depending on the nature of the task.

**[TRANSLATE-2328](https://jira.translate5.net/browse/TRANSLATE-2328): InstantTranslate: File upload will not work behind a proxy** <br>
InstantTranslate file upload may not work behind a proxy, depending on the network configuration. See config worker.server.

**[TRANSLATE-2294](https://jira.translate5.net/browse/TRANSLATE-2294): Additional tags from language resources are not handled properly** <br>
The tag and whitespace handling of all language resources are unified and fixed, regarding to missing or additional tags.


## [5.0.13] - 2020-11-17

### Important Notes:
#### [TRANSLATE-2312](https://jira.translate5.net/browse/TRANSLATE-2312)
Re-enabled full text search in the target language field of the task creation wizard.

#### [TRANSLATE-2306](https://jira.translate5.net/browse/TRANSLATE-2306)
The button in the editor to leave a task (formerly "Leave task"), which is currently labeled "Continue task later" is renamed to "Back to task list" as agreed in monthly meeting.
 


### Added
**[TRANSLATE-2225](https://jira.translate5.net/browse/TRANSLATE-2225): Import filter for special Excel file format containing texts with special length restriction needs** <br>
A client specific import filter for a data in a client specific excel file format.


### Changed
**[TRANSLATE-2296](https://jira.translate5.net/browse/TRANSLATE-2296): Improve Globalese integration to work with project feature** <br>
Fix Globalese integration with latest translate5.


### Bugfixes
**[TRANSLATE-2313](https://jira.translate5.net/browse/TRANSLATE-2313): InstantTranslate: new users sometimes can not use InstantTranslate** <br>
New users are sometimes not able to use instanttranslate. That depends on the showSubLanguages config and the available languages.

**[TRANSLATE-2312](https://jira.translate5.net/browse/TRANSLATE-2312): Can't use "de" anymore to select a target language** <br>
In project creation target language field type "(de)" and you get no results. Instead typing "Ger" works. The first one is working now again.

**[TRANSLATE-2311](https://jira.translate5.net/browse/TRANSLATE-2311): Cookie Security** <br>
Set the authentication cookie according to the latest security recommendations.

**[TRANSLATE-2308](https://jira.translate5.net/browse/TRANSLATE-2308): Disable webserver directory listing** <br>
The apache directory listing is disabled for security reasons in the .htaccess file.

**[TRANSLATE-2307](https://jira.translate5.net/browse/TRANSLATE-2307): Instanttranslate documents were accessable for other users** <br>
Instanttranslate documents could be accessed from other users by guessing the task id in the URL.

**[TRANSLATE-2306](https://jira.translate5.net/browse/TRANSLATE-2306): Rename "Continue task later" button** <br>
The button in the editor to leave a task (formerly "Leave task"), which is currently labeled "Continue task later" is renamed to "Back to task list" as agreed in monthly meeting.

**[TRANSLATE-2293](https://jira.translate5.net/browse/TRANSLATE-2293): Custom panel is not state full** <br>
The by default disabled custom panel is now also stateful.

**[TRANSLATE-2288](https://jira.translate5.net/browse/TRANSLATE-2288): Reduce translate5.zip size to decrease installation time** <br>
The time needed for an update of translate5 depends also on the package size. The package was blown up in the last time, now the size is reduced again.

**[TRANSLATE-2287](https://jira.translate5.net/browse/TRANSLATE-2287): Styles coming from plugins are added multiple times to the HtmlEditor** <br>
Sometimes the content styles of the HTML Editor are added multiple times, this is fixed.

**[TRANSLATE-2265](https://jira.translate5.net/browse/TRANSLATE-2265): Microsoft translator directory lookup change** <br>
Solves the problem that microsoft translator does not provide results when searching text in instant translate with more then 5 characters.

**[TRANSLATE-2224](https://jira.translate5.net/browse/TRANSLATE-2224): Deleted tags in TrackChanges do not really look deleted** <br>
FIX: Deleted tags in TrackChanges in the HTML-Editor now look deleted as well (decorated with a strike-through)

**[TRANSLATE-2172](https://jira.translate5.net/browse/TRANSLATE-2172): maxNumberOfLines currently only works for pixel-length and not char-length checks** <br>
Enabling line based length check also for length unit character.

**[TRANSLATE-2151](https://jira.translate5.net/browse/TRANSLATE-2151): Visual Editing: If page grows to large (gets blue footer) and had  been zoomed, some visual effects do not work, as they should** <br>
Fixed inconsistencies with the Text-Reflow and especially the page-growth colorization when zooming the visual review. Pages now keep their grown size  when scrolling them out of view & back.

**[TRANSLATE-1034](https://jira.translate5.net/browse/TRANSLATE-1034): uploading file bigger as post_max_size or upload_max_filesize gives no error message, just a empty window** <br>
If uploading a file bigger as post_max_size or upload_max_filesize gives an error message is given now.


## [5.0.12] - 2020-10-21

### Important Notes:
-  


### Changed
**[TRANSLATE-2279](https://jira.translate5.net/browse/TRANSLATE-2279): Integrate git hook checks** <br>
Development: Integrate git hooks to validate source code.


### Bugfixes
**[TRANSLATE-2282](https://jira.translate5.net/browse/TRANSLATE-2282): Mixing XLF id and rid values led to wrong tag numbering** <br>
When in some paired XLF tags the rid was used, and in others the id to pair the tags, this could lead to duplicated tag numbers.

**[TRANSLATE-2280](https://jira.translate5.net/browse/TRANSLATE-2280): OpenTM2 is not reachable anymore if TMPrefix configuration is empty** <br>
OpenTM2 installations were not reachable anymore from the application if the tmprefix was not configured. Empty tmprefixes are valid again.

**[TRANSLATE-2278](https://jira.translate5.net/browse/TRANSLATE-2278): Check if the searched text is valid for segmentation** <br>
Text segmentation and text segmentation search in instant-translate only will be done only when for the current search TM is available or risk-predictor (ModelFront) is enabled.

**[TRANSLATE-2277](https://jira.translate5.net/browse/TRANSLATE-2277): UserConfig value does not respect config data type** <br>
The UserConfig values did not respect the underlying configs data type, therefore the preferences of the repetition editor were not loaded correctly and the repetition editor did not come up.

**[TRANSLATE-2265](https://jira.translate5.net/browse/TRANSLATE-2265): Microsoft translator directory lookup change** <br>
Solves the problem that microsoft translator does not provide results when searching text in instant translate with more then 5 characters.

**[TRANSLATE-2264](https://jira.translate5.net/browse/TRANSLATE-2264): Relative links for instant-translate file download** <br>
Fixed file file download link in instant translate when the user is accessing translate5 from different domain.

**[TRANSLATE-2263](https://jira.translate5.net/browse/TRANSLATE-2263): Do not use ExtJS debug anymore** <br>
Instead of using the debug version of ExtJS now the normal one is used. This reduces the initial load from 10 to 2MB.

**[TRANSLATE-2262](https://jira.translate5.net/browse/TRANSLATE-2262): Remove sensitive data of API endpoint task/userlist** <br>
The userlst needed for filtering in the task management exposes the encrypted password.

**[TRANSLATE-2261](https://jira.translate5.net/browse/TRANSLATE-2261): Improve terminology import performance** <br>
The import performance of large terminology was really slow, by adding some databases indexes the imported was boosted. 

**[TRANSLATE-2260](https://jira.translate5.net/browse/TRANSLATE-2260): Visual Review: Normalizing whitespace when comparing segments for content-align / pivot-language** <br>
Whitespace will now be normalized when aligned visuals in the visual review or pivot languages are validated against the segments 

**[TRANSLATE-2252](https://jira.translate5.net/browse/TRANSLATE-2252): Reapply tooltip over processing status column** <br>
The tool-tips were changed accidentally and are restored now.

**[TRANSLATE-2251](https://jira.translate5.net/browse/TRANSLATE-2251): Reapply "Red bubble" to changed segments in left side layout of split screen** <br>
The red bubble representing edited segments will now also show in the left (unedited) frame of the split-view of the visual review

**[TRANSLATE-2250](https://jira.translate5.net/browse/TRANSLATE-2250): Also allow uploading HTML for VisualReview** <br>
Since it is possible to put HTML files as layout source in the visual folder of the zip import package, selecting an HTML file in the GUI should be allowed, too.

**[TRANSLATE-2245](https://jira.translate5.net/browse/TRANSLATE-2245): Switch analysis to batch mode, where language resources support it** <br>
Sending multiple segment per request when match analysis and pre-translation is running now can be configured in (default enabled): runtimeOptions.plugins.MatchAnalysis.enableBatchQuery; Currently this is supported by the following language resources: Nectm, PangeaMt, Microsoft, Google, DeepL

**[TRANSLATE-2220](https://jira.translate5.net/browse/TRANSLATE-2220): XML/XSLT import for visual review: Filenames may not be suitable for OKAPI processing** <br>
FIX: Any filenames e.g. like "File (Kopie)" now can be processed, either as aligned XML/XSLT file or with a direct XML/XSL import 


## [5.0.11] - 2020-10-14

### Important Notes:
#### [TRANSLATE-2045](https://jira.translate5.net/browse/TRANSLATE-2045)
- This update modifies the whole data of the database! 
Therefore a back up of the database directly before the update is mandatory!
- On larger installations this update may need some time (for example for 2.5 million segments in 700 tasks the script needed about 15 minutes)
- The database script requires user with ALTER privilege on the database - which should be the case if installed translate5 as described in the installation guide.
- In the case of errors collect them and send them to us and restore your backup. 
- In the issue description is a manual guide for the migration
 


### Changed
[TRANSLATE-2246](https://jira.translate5.net/browse/TRANSLATE-2246): Move the Ip based exception and the extended user model into the same named Plugin<br>
Some code refactoring.


### Bugfixes
[TRANSLATE-2259](https://jira.translate5.net/browse/TRANSLATE-2259): Inconsistent workflow may lead in TaskUserAssoc Entity Not Found error when saving a segment.<br>
The PM is allowed to set the Job associations as they want it. This may lead to an inconsistent workflow. One error when editing segments in an inconsistent workflow is fixed now.

[TRANSLATE-2258](https://jira.translate5.net/browse/TRANSLATE-2258): Fix error E1161 "The job can not be modified due editing by a user" so that it is not triggered by viewing only users.<br>
The above mentioned error is now only triggered if the user has opened the task for editing, before also a readonly opened task was triggering that error.

[TRANSLATE-2247](https://jira.translate5.net/browse/TRANSLATE-2247): New installations save wrong mysql executable path (for installer and updater)<br>
Fix a bug preventing new installations to be usable.

[TRANSLATE-2045](https://jira.translate5.net/browse/TRANSLATE-2045): Use utf8mb4 charset for DB<br>
Change all utf8 fields to the mysql datatype utf8mb4. 


## [5.0.10] - 2020-10-06

### Important Notes:

### Added
[TRANSLATE-2160](https://jira.translate5.net/browse/TRANSLATE-2160): IP-based Authentication, that creates temporary users
For the roles "InstantTranslate" and "Term search" it is now possible to configure IP addresses in translate5s configuration. Users coming from these IPs will then be logged in automatically with a temporary user. All uploaded data and the temporary user will automatically be deleted, when the session expires.

### Changed
[TRANSLATE-2244](https://jira.translate5.net/browse/TRANSLATE-2244): Embed translate5 guide video in help window<br>
Embed the translate5 guide videos as iframe in the help window. The videos are either in german or english, they are chosen automatically depending on the GUI interface. A list of links to jump to specific parrs of the videos are provided.

[TRANSLATE-2214](https://jira.translate5.net/browse/TRANSLATE-2214): Change SSO Login Button Position<br>
The SSO Login Button is now placed right of the login button instead between the login input field and the submit button.

[TRANSLATE-1237](https://jira.translate5.net/browse/TRANSLATE-1237): Exported xliff 2.1 is not valid<br>
The XLF 2.1 output is now valid (validated against https://okapi-lynx.appspot.com/validation).


### Bugfixes
[TRANSLATE-2243](https://jira.translate5.net/browse/TRANSLATE-2243): Task properties panel stays enabled without selected task<br>
Sometimes the task properties panel was enabled even when there is no task selected in the project tasks grid.

[TRANSLATE-2242](https://jira.translate5.net/browse/TRANSLATE-2242): Source text translation in matches and concordance search grid<br>
Change the German translation for matches and concordance search grid source column from: Quelltext to Ausgangstext.

[TRANSLATE-2240](https://jira.translate5.net/browse/TRANSLATE-2240): PDF in InstantTranslate<br>
Translating a PDF file with InstantTranslate document upload leads to a file with 0 bytes and file extension .pdf instead a TXT file named .pdf.txt. (like Okapi is producing it).

[TRANSLATE-2239](https://jira.translate5.net/browse/TRANSLATE-2239): Installer is broken due zend library invocation change<br>
The installer is broken since the the zend libraries were moved and integrated with the composer auto loader. Internally a class_exist is used which now returns always true which is wrong for the installation.

[TRANSLATE-2237](https://jira.translate5.net/browse/TRANSLATE-2237): Auto state translations<br>
Update some of the auto state translations (see image attached)

[TRANSLATE-2236](https://jira.translate5.net/browse/TRANSLATE-2236): Change quality and state flags default values<br>
Update the default value of the runtimeOptions.segments.stateFlags and runtimeOptions.segments.qualityFlags to more usable demo values.

[TRANSLATE-2235](https://jira.translate5.net/browse/TRANSLATE-2235): Not all segmentation rules (SRX rules) in okapi bconf acutally are triggered<br>
The reason seems to be, that all segment break="no" rules of a language need to be above all break="yes" rules, even if the break="yes" rules do not interfere with the break="no" rules.

[TRANSLATE-2234](https://jira.translate5.net/browse/TRANSLATE-2234): Error on global customers filter<br>
-

[TRANSLATE-2233](https://jira.translate5.net/browse/TRANSLATE-2233): Remove autoAssociateTaskPm workflow action<br>
Remove the autoAssociateTaskPm workflow functionality from the workflow action configuration and from the source code too.

[TRANSLATE-2232](https://jira.translate5.net/browse/TRANSLATE-2232): Action button "Associated tasks" is visible for non TM resources<br>
The action button for re-importing segments to tm in the language resource overview grid is visible for no tm resources (ex: the button is visible for mt resources). The button only should be visible for TM resources.

[TRANSLATE-2218](https://jira.translate5.net/browse/TRANSLATE-2218): Trying to edit a segment with disabled editable content columns lead to JS error<br>
Trying to edit a segment when all editable columns are hidden, was leading to a JS error.

[TRANSLATE-2173](https://jira.translate5.net/browse/TRANSLATE-2173): Language resources without valid configuration should be shown with brackets in "Add" dialogue<br>
Available but not configured LanguageResources are shown in the selection list in brackets.

[TRANSLATE-2075](https://jira.translate5.net/browse/TRANSLATE-2075): Fuzzy-Selection of language resources does not work as it should<br>
When working with language resources the mapping between the languages of the language resource and the languages in translate5 was improved, especially in matching sub-languages. For Details see the issue.

[TRANSLATE-2041](https://jira.translate5.net/browse/TRANSLATE-2041): Tag IDs of created XLF 2 are invalid for importing in other CAT tools<br>
The XLF 2.1 output is now valid (validated against https://okapi-lynx.appspot.com/validation).

[TRANSLATE-2011](https://jira.translate5.net/browse/TRANSLATE-2011): translate 2 standard term attributes for TermPortal<br>
Added the missing term-attribute translations.


## [5.0.9] - 2020-09-16

### Important Notes:
 ADD MANUALLY Infos from Jiras 'Important release notes:' here


### Added
[TRANSLATE-1050](https://jira.translate5.net/browse/TRANSLATE-1050): Save user customization of editor<br>
The user may now change the visible columns and column positions and widths of the segment grid. This customizations are restored on next login.

[TRANSLATE-2071](https://jira.translate5.net/browse/TRANSLATE-2071): VisualReview: XML with "What you see is what you get" via XSL transformation<br>
A XML with a XSLT can be imported into translate5. The XML is then converted into viewable content in VisualReview.

[TRANSLATE-2111](https://jira.translate5.net/browse/TRANSLATE-2111): Make pop-up about "Reference files available" and "Do you really want to finish" pop-up configurable<br>
For both pop ups it is now configurable if they should be used and shown in the application.

[TRANSLATE-1793](https://jira.translate5.net/browse/TRANSLATE-1793): search and replace: keep last search field or preset by workflow step.<br>
The last searched field and content is saved and remains in the search window when it was closed.


### Changed
[TRANSLATE-1617](https://jira.translate5.net/browse/TRANSLATE-1617): Renaming of buttons on leaving a task<br>
The label of the leave Button was changed.

[TRANSLATE-2180](https://jira.translate5.net/browse/TRANSLATE-2180): Enhance displayed text for length restrictions in the editor<br>
The display text of the segment length restriction was changed.

[TRANSLATE-2186](https://jira.translate5.net/browse/TRANSLATE-2186): Implement close window button for editor only usage<br>
To show that Button set runtimeOptions.editor.toolbar.hideCloseButton to 0. This button can only be used if translate5 was opened via JS window.open call.

[TRANSLATE-2193](https://jira.translate5.net/browse/TRANSLATE-2193): Remove "log out" button in editor<br>
The user has first to leave the task before he can log out.


### Bugfixes
[TRANSLATE-630](https://jira.translate5.net/browse/TRANSLATE-630): Enhance, when text filters of columns are send<br>
When using a textfilter in a grid in the frontend, the user has to type very fast since the filters were sent really fast to the server. This is changed now.

[TRANSLATE-1877](https://jira.translate5.net/browse/TRANSLATE-1877): Missing additional content and filename of affected file in E1069 error message<br>
Error E1069 shows now also the filename and the affected characters.

[TRANSLATE-2010](https://jira.translate5.net/browse/TRANSLATE-2010): Change tooltip of tasks locked because of excel export<br>
The content of the tooltip was improved.

[TRANSLATE-2014](https://jira.translate5.net/browse/TRANSLATE-2014): Enhance "No results found" message in InstantTranslate<br>
Enhance "No results found" message in InstantTranslate

[TRANSLATE-2156](https://jira.translate5.net/browse/TRANSLATE-2156): Remove "Choose automatically" option from drop-down, that chooses source or target for connecting the layout with<br>
Since this was confusing users the option was removed and source is the new default

[TRANSLATE-2195](https://jira.translate5.net/browse/TRANSLATE-2195): InstantTranslate filepretranslation API has a wrong parameter name<br>
The parameter was 0 instead as documented in confluence.

[TRANSLATE-2215](https://jira.translate5.net/browse/TRANSLATE-2215): VisualReview JS Error: me.down(...) is null<br>
Error happend in conjunction with the usage of the action buttons in Visual Review.

[TRANSLATE-1031](https://jira.translate5.net/browse/TRANSLATE-1031): Currently edited column in row editor is not aligned right<br>
When scrolling horizontally in the segment grid, this could lead to positioning problems of the segment editor.



## [5.0.8] - 2020-09-07

### Important Notes:
#### TRANSLATE-2177: Commandline Interface to maintain translate5
For the usage of the pre-released CLI tool for maintaining translate5 see: 
https://confluence.translate5.net/display/CON/CLI+Maintenance+Command
#### TRANSLATE-2184: User info endpoint is unreachable
With this improvement new openid config is introduced : runtimeOptions.openid.requestUserInfo
If this config is active (by default active), the user info endpoint will be requested for additional user information. In some authentication providers, this request is not required at all, since all needed user information can be fetched from the claims. 




#### TRANSLATE-2053: Deadline by hour and not only by day
This feature requires an activated periodical cron. For activation see: https://confluence.translate5.net/display/CON/Install+translate5+and+direct+dependencies#Installtranslate5anddirectdependencies-cronjobsConfigureCronjobs/taskscheduler
#### TRANSLATE-2025: Change default for runtimeOptions.segments.userCanIgnoreTagValidation to 0
The system setting that allows users to ignore tag validation has been set to "do not allow".
If you need users to be allowed to ignore the tag validation, you need to switch this on again.
To switch it on again, call in the translate5 installation folder:
./translate5.sh config runtimeOptions.segments.userCanIgnoreTagValidation 1

### Added
[TRANSLATE-1134](https://jira.translate5.net/browse/TRANSLATE-1134): Jump to last edited/active segment<br>
The last edited/active segment is selected again on reopening a task.

[TRANSLATE-2111](https://jira.translate5.net/browse/TRANSLATE-2111): Make pop-up about "Reference files available" and "Do you really want to finish" pop-up configurable<br>
Make pop-up abaout "Reference files available" and "Do you really want to finish" pop-up configurable

[TRANSLATE-2125](https://jira.translate5.net/browse/TRANSLATE-2125): Split screen for Visual Editing (sponsored by Transline)<br>
In Visual Editing the original and the modified is shown in two beneath windows.


### Changed
[TRANSLATE-2113](https://jira.translate5.net/browse/TRANSLATE-2113): Check if translate5 runs with latest MariaDB and MySQL versions<br>
It was verified that translate5 can be installed and run with latest MariaDB and MySQL versions.

[TRANSLATE-2122](https://jira.translate5.net/browse/TRANSLATE-2122): Unify naming of InstantTranslate and TermPortal everywhere<br>
Unify naming of InstantTranslate and TermPortal everywhere

[TRANSLATE-2175](https://jira.translate5.net/browse/TRANSLATE-2175): Implement maintenance command to delete orphaned data directories<br>
With the brand new ./translate5.sh CLI command several maintenance tasks can be performed. See https://confluence.translate5.net/display/CON/CLI+Maintenance+Command

[TRANSLATE-2189](https://jira.translate5.net/browse/TRANSLATE-2189): Ignore segments with tags only in SDLXLIFF import if enabled<br>
SDLXLIFF Import: If a segment contains only tags it is ignored from import. This is the default behaviour in native XLF import.

[TRANSLATE-2025](https://jira.translate5.net/browse/TRANSLATE-2025): Change default for runtimeOptions.segments.userCanIgnoreTagValidation to 0<br>
Tag errors can now not ignored anymore on saving a segment. 

[TRANSLATE-2163](https://jira.translate5.net/browse/TRANSLATE-2163): Enhance documentation of Across termExport for translate5s termImport Plug-in<br>
Enhance documentation of Across termExport for translate5s termImport Plug-in

[TRANSLATE-2165](https://jira.translate5.net/browse/TRANSLATE-2165): Make language resource timeout for PangeaMT configurable<br>
Make language resource timeout for PangeaMT configurable

[TRANSLATE-2179](https://jira.translate5.net/browse/TRANSLATE-2179): Support of PHP 7.4 for translate5<br>
Support of PHP 7.4 for translate5

[TRANSLATE-2182](https://jira.translate5.net/browse/TRANSLATE-2182): Change default colors for Matchrate Colorization in the VisualReview<br>
Change default colors for Matchrate Colorization in the VisualReview

[TRANSLATE-2184](https://jira.translate5.net/browse/TRANSLATE-2184): OpenID Authentication: User info endpoint is unreachable<br>
This is fixed.

[TRANSLATE-2192](https://jira.translate5.net/browse/TRANSLATE-2192): Move "leave task" button in simple mode to the upper right corner of the layout area<br>
Move "leave task" button in simple mode to the upper right corner of the layout area

[TRANSLATE-2199](https://jira.translate5.net/browse/TRANSLATE-2199): Support more regular expressions in segment search<br>
Support all regular expressions in segment search, that are possible based on MySQL 8 or MariaDB 10.2.3


### Bugfixes
[TRANSLATE-2002](https://jira.translate5.net/browse/TRANSLATE-2002): Translated PDF files should be named xyz.pdf.txt in the export package<br>
Okapi may return translated PDF files only as txt files, so the file should be named .txt instead .pdf.

[TRANSLATE-2049](https://jira.translate5.net/browse/TRANSLATE-2049): ERROR in core: E9999 - Action does not exist and was not trapped in __call()<br>
Sometimes the above error occurred, this is fixed now.

[TRANSLATE-2062](https://jira.translate5.net/browse/TRANSLATE-2062): Support html fragments as import files without changing the structure<br>
This feature was erroneously disabled by a bconf change which is revoked right now.

[TRANSLATE-2149](https://jira.translate5.net/browse/TRANSLATE-2149): Xliff import deletes part of segment and a tag<br>
In seldom circumstances XLF content was deleted on import.

[TRANSLATE-2157](https://jira.translate5.net/browse/TRANSLATE-2157): Company name in deadline reminder footer<br>
The company name was added in the deadline reminder footer e-mail.

[TRANSLATE-2162](https://jira.translate5.net/browse/TRANSLATE-2162): Task can not be accessed after open randomly<br>
It happend randomly, that a user was not able to access a task after opening it. The error message was: You are not authorized to access the requested data. This is fixed.

[TRANSLATE-2166](https://jira.translate5.net/browse/TRANSLATE-2166): Add help page for project and preferences overview<br>
Add help page for project and preferences overview

[TRANSLATE-2167](https://jira.translate5.net/browse/TRANSLATE-2167): Save filename with a save request to NEC-TM<br>
A filenames is needed for later TMX export, so one filename is generated and saved to NEC-TM.

[TRANSLATE-2176](https://jira.translate5.net/browse/TRANSLATE-2176): remove not race condition aware method in term import<br>
A method in the term import was not thread safe.




[TRANSLATE-2187](https://jira.translate5.net/browse/TRANSLATE-2187): Bad performance on loading terms in segment meta panel<br>
Bad performance on loading terms in segment meta panel

[TRANSLATE-2188](https://jira.translate5.net/browse/TRANSLATE-2188): Text in layout of xsl-generated html gets doubled<br>
Text in layout of xsl-generated html gets doubled

[TRANSLATE-2190](https://jira.translate5.net/browse/TRANSLATE-2190): PHP ERROR in core: E9999 - Cannot refresh row as parent is missing - fixed in DbDeadLockHandling context<br>
In DbDeadLockHandling it may happen that on redoing the request a needed row is gone, this is no problem so far, so this error is ignored in that case.

[TRANSLATE-2191](https://jira.translate5.net/browse/TRANSLATE-2191): Session Problem: Uncaught Zend_Session_Exception: Zend_Session::start()<br>
Fixed this PHP error.

[TRANSLATE-2194](https://jira.translate5.net/browse/TRANSLATE-2194): NEC-TM not usable in InstantTranslate<br>
NEC-TM not usable in InstantTranslate

[TRANSLATE-2198](https://jira.translate5.net/browse/TRANSLATE-2198): Correct spelling of "Ressource(n)" in German<br>
Correct spelling of "Ressource(n)" in German

[TRANSLATE-2210](https://jira.translate5.net/browse/TRANSLATE-2210): If a task is left, it is not focused in the project overview<br>
This is fixed now




## [5.0.7] - 2020-08-05

### Added

TRANSLATE-2069: Show task-id and segment-id in URL and enable to access a task via URL (sponsored by Supertext)
A user is now able to send an URL that points to a certain segment of an opened task to another user and he will be able to automatically open the segment and scroll to the task alone via entering the URL (provided he has access rights to the task). This works also, if the user still has to log in and also if login works via OpenID Connect.

### Changed

TRANSLATE-2150: Disable default enabled workflow action finishOverduedTaskUserAssoc
Disable default enabled workflow action finishOverduedTaskUserAssoc

TRANSLATE-2159: Update Third-Party-Library Horde Text Diff
Include the up2date version of the used diff library

### Bugfixes


Again further major improvments of the layout for the „What you see is what you get“ feature compared to version 5.0.6

TRANSLATE-2148: Load module plugins only
A fix in the architecture of translate5

TRANSLATE-2153: In some cases translate5 deletes spaces between segments
This refers to the visual layout representation of segments (not the actual translation)

TRANSLATE-2155: Visual HTML fails on import for multi-target-lang project
Creating a mulit-lang project failed, when fetching the layout via URL

TRANSLATE-2158: Reflect special whitespace characters in the layout
Entering linebreak, non-breaking-space and tabs in the segment effects now „What you see is what you get“ the layout

## [5.0.6] - 2020-07-23

### Changed

TRANSLATE-2139: Pre-translation exceptions
The error handling for integrated language resources has been improved

### Bugfixes

TRANSLATE-2117: LanguageResources: update & query segments with tags
For PangeaMT and NEC-TM the usage of internal tags was provided / fixed and a general mechanism for language resources for this issue introduced

TRANSLATE-2127: Xliff files with file extension xml are passed to okapi instead of translate5s xliff parser
XML files that acutally contain XLIFF had been passed to Okapi instead of the translate5 xliff parser, if they startet with a BOM (Byte order mark)

TRANSLATE-2138: Visual via URL does not work in certain cases
In some cases passing the layout via URL did not work

TRANSLATE-2142: Missing property definition
A small fix

TRANSLATE-2143: Problems Live-Editing: Shortened segments, insufficient whitespace
Major enhancements in the „What you see is what you get“ feature regarding whitespace handling and layout issues

TRANSLATE-2144: Several problems with copy and paste content into an edited segment

TRANSLATE-2146: Exclude materialized view check in segments total count
A small fix

## [5.0.5] - 2020-07-13

### Added

[TRANSLATE-2137: Translate files with InstantTranslate: Enable it to turn the feature of via configuration

### Bugfixes

TRANSLATE-2035: Add extra column to languageresource log table

TRANSLATE-2047: Errormessages on DB Update V 3.4.1

TRANSLATE-2120: Add missing DB constraint to Zf_configuration table

TRANSLATE-2129: Look for and solve open Javascript bugs (theRootCause)

TRANSLATE-2131: APPLICATON_PATH under Windows contains slash

TRANSLATE-2132: Kpi buttons are visible for editor only users

TRANSLATE-2134: Remove document properties for MS Office and LibreOffice formats of default okapi bconf

## [5.0.4] - 2020-07-06

### Added

TRANSLATE-2016: Align visual jobs with sdlxliff for review purposes

### Changed

TRANSLATE-2128: Add capabilities to disable the "What you see is what you get" via config-option

### Bugfixes

TRANSLATE-2074: Login: Syntax error or access violation (depending on role)

TRANSLATE-2114: Missing session leads to Javascript-Error

TRANSLATE-2123: Don't use zend cache for materialized view table check

## [5.0.3] - 2020-06-30

### Added

TRANSLATE-1774: Integrate NEC-TM with translate5 as LanguageResource
Integrated NEC-TM with translate5 as Language-Resource

TRANSLATE-2052: Assign different segments of same task to different users
Added capabilities to assign different segments of the same task to different users

### Bugfixes

TRANSLATE-2094: Set finish date for all reviewers
Removed workflow action „setReviewersFinishDate“

TRANSLATE-2096: Use FontAwesome5 icons in translate5
Use FontAwesome5 for all icons in translate5

TRANSLATE-2097: Minimum characters requirement for client name in clients form
Minimum characters requirement for client name in clients form is now 1

TRANSLATE-2101: Disable automated translation xliff creation from notFoundTranslation xliff in production instances
Disable automated creation of a xliff-file from notFoundTranslation xliff in production instances

TRANSLATE-2102: Comma in PDF filename leads to failing visual import
VisualTranslation: Commas in PDF filenames (formerly leading to failing imports) are now automatically corrected

TRANSLATE-2104: KPI button does not work
The KPI Button works as expected now

TRANSLATE-2105: Serverside check for pixel-based length check fails on multiple lines
The serverside check for the pixel-based length check works as expected with multiple lines now

TRANSLATE-2106: Remove white spaces from user login and password
Whitespace and blanks from user login and password in the login form are automatically removed

TRANSLATE-2109: Remove string length restriction flag
Remove string length restriction configuration option

TRANSLATE-2121: filename issues with tmx import and export from and to NEC-TM
Fixed issues with filenames on NEC-TM tmx export and import

## [5.0.2] - 2020-06-19

### Added

TRANSLATE-1900: Pixel length check: Handle characters with unkown pixel length
Pixel length check: Handle characters with unkown pixel length

TRANSLATE-2054: Integrate PangeaMT with translate5
Integrate PangeaMT as new machine translation language resource.

TRANSLATE-2092: Import specific DisplayText XML
Import specific DisplayText XML

TRANSLATE-2071: XML mit "What you see is what you get" via XSL transformation
An imported XML may contains a link to an XSL stylesheet. If this link exists (as a file or valid URL) the Source for the VisualTranslation is generated from the XSL processing of the XML

### Changed

TRANSLATE-2070: In XLF Import: Move also bx,ex and it tags out of the segment (sponsored by Supertext)
Move paired tags out of the segment, where the corresponding tag belongs to another segment

### Bugfixes

TRANSLATE-2091: Prevent hanging imports when starting maintenance mode
Starting an improt while a maintenance is scheduled could lead to hanging import workers. Now workers don't start when a maintenance is scheduled.

## [5.0.1] - 2020-06-17

### Added

TRANSLATE-1900: Pixel length check: Handle characters with unkown pixel length
Pixel length check: Handle characters with unkown pixel length

TRANSLATE-2054: Integrate PangeaMT with translate5
Integrate PangeaMT as new machine translation language resource.

TRANSLATE-2092: Import specific DisplayText XML
Import specific DisplayText XML

### Changed

TRANSLATE-2070: In XLF Import: Move also bx,ex and it tags out of the segment (sponsored by Supertext)
Move paired tags out of the segment, where the corresponding tag belongs to another segment

### Bugfixes

TRANSLATE-2091: Prevent hanging imports when starting maintenance mode
Starting an improt while a maintenance is scheduled could lead to hanging import workers. Now workers don't start when a maintenance is scheduled.

## [5.0.0] - 2020-06-04

### Added

TRANSLATE-1610: Bundle tasks to projects
Several tasks with same content and same source language can now be bundled to projects. A completely new project overview was created therefore.

TRANSLATE-1901: Support lines in pixel-based length check
If configured the width of each new-line in target content is calculated and checked separately.




TRANSLATE-2086: Integrate ModelFront (MT risk prediction)
ModelFront risk prediction for MT matches is integrated.

TRANSLATE-2087: VisualTranslation: Highlight pre-translated segments of bad quality / missing translations
Highlight pre-translated segments of bad quality / missing translations in visual translation 

TRANSLATE-1929: VisualTranslation: HTML files can import directly
HTML files can be used directly as import file in VisualTranslation

### Changed

TRANSLATE-2072: move character pixel definition from customer to file level
The definition of character pixel widths is move from customer to file level

TRANSLATE-2084: Disable possiblity to delete tags by default
The possibility to save a segment with tag errors and ignore the warn message is disabled now. This can be re-enabled as described in https://jira.translate5.net/browse/TRANSLATE-2084. Whitespace tags can still be deleted. 

TRANSLATE-2085: InstantTranslate: handling of single segments with dot
Translating one sentence with a trailing dot was recognized as multiple sentences instead only one.

## [3.4.4] - 2020-05-27

### Changed

TRANSLATE-2043: Use Composer to manage all the PHP dependencies in development
All PHP third party code libraries are now delivered as one third-party package. In development composer is used now to manage all the PHP dependencies.

### Bugfixes

TRANSLATE-2082: Missing surrounding tags on export of translation tags
For better usability surrounding tags of a segment are not imported. In translation task this tags are not added anymore on export. For review tasks everything was working.

## [3.4.3] - 2020-05-11

### Added

TRANSLATE-1661: MatchAnalysis: GroupShare TMs support now also count of internal fuzzies
The GroupShare connector is now able to support the count of internal fuzzies

### Bugfixes

TRANSLATE-2062: Support html fragments as import files without changing the structure
The Okapi import filter was changed, so that also HTML fragments (instead only valid HTML documents) can be imported

## [3.4.2] - 2020-05-07

### Added

TRANSLATE-1999: Optional custom content can be displayed in the file area of the editor
See configuration runtimeOptions.editor.customPanel.url and runtimeOptions.editor.customPanel.title

TRANSLATE-2028: Change how help window urls are defined in Zf_configuration
See https://confluence.translate5.net/display/CON/Database+based+configuration

TRANSLATE-2039: InstantTranslate: Translate text area segmented against TM and MT and Terminology
InstantTranslate can deal now with multiple sentences

TRANSLATE-2048: Provide segment auto-state summary via API
A segment auto-state summary is now provided via API

### Changed

TRANSLATE-2044: Change Edge browser support version
Minimum Edge Version is now: Version 80.0.361.50: 11. Februar or higher

TRANSLATE-2042: Introduce a tab panel used for the administrative main components
The administration main menu was improved

TRANSLATE-1926: Add LanguageResources: show all services that translate5 can handle
On adding LanguageResources also the not configured resources are shown (disabled, but the user knows now that it does exist)

TRANSLATE-2031: NEC-TM: Categeries are mandatory
On the creation and usage of NEC-TM categeries are now mandatory

### Bugfixes

TRANSLATE-1769: Fuzzy-Matching of languages in TermTagging does not work, when a TermCollection is added after task import
If choosing a language with out a sublanguage in translate5 (just "de" for example) the termtagger should also tag terms in the language de_DE. This was not working anymore.

TRANSLATE-2024: InstantTranslate file translation: Segments stay empty, if no translation is provided
If for a segment no translation could be find, the source text remains.

TRANSLATE-2029: NEC-TM Error in GUI: Save category assocs
A JS error occured on saving NEC-TMs

TRANSLATE-2030: Garbage Collector produces DB DeadLocks due wrong timezone configuration
The problem was fixed internally, although it should be ensured, that the DB and PHP run in the same timezone.

TRANSLATE-2033: JS error when leaving the application
The JS error "Sync XHR not allowed in page dismissal" was solved

TRANSLATE-2034: In Chinese languages some ^h characters are added which prevents export then due invalid XML 
The characters are masked now as special character, which prevents the XML getting scrambled.

TRANSLATE-2036: Handle empty response from the spell check
The Editor may handle empty spell check results now

TRANSLATE-2037: VisualReview: Leaving a task leads to an error in Microsoft Edge
Is fixed now, was reproduced on Microsoft Edge: 44.18362.449.0

TRANSLATE-2050: Change Language Resource API so that it is understandable
Especially the handling of the associated clients and the default clients was improved

TRANSLATE-2051: TaskGrid advanced datefilter is not working
Especially the date at was not working

TRANSLATE-2055: Switch okapi import to tags, that show tag markup to translators
Instead of g and x tags Okapi produces know ph, it, bpt and ept tags, which in the end shows the real tag content to the user in the Editor.

TRANSLATE-2056: Finished task can not be opened readonly
Tasks finished in the workflow could not be opened anymore read-only by the finishing user

TRANSLATE-2057: Disable term tagging in read only segments
This can be changed in the configuration, so that terms of non editable segments can be tagged if needed

TRANSLATE-2059: Relais import fails with DB error message
This is fixed now.

TRANSLATE-2023: InstantTranslate - Filetranslation: Remove associations to LanguageResources after translation
On using the file translation in InstantTranslate some automatically used language resources are now removed again

## [3.4.1] - 2020-04-08

### Added

TRANSLATE-1997: Show help window automatically and remember "seen" click
If configured the window pops up automatically and saves the "have seen" info

TRANSLATE-2001: Support MemoQ comments for im- and export
Added comment support to the MemoQ im- and export

### Changed

TRANSLATE-2007: LanguageResources that cannot be used: Improve error handling
Improved the error handling if a chosen language-resource is not available.

### Bugfixes

TRANSLATE-2022: Prevent huge segments to be send to the termTagger
Huge Segments (configurable, default more then 150 words) are not send to the TermTagger anymore due performance reasons.

TRANSLATE-1753: Import Archive for single uploads misses files and can not be reimported
In the import archive for single uploads some files were missing, so that the task could not be reimported with the clone button.

TRANSLATE-2018: mysql error when date field as default value has CURRENT_TIMESTAMP
The problem is solved in translate5 by adding the current timestamp there

TRANSLATE-2008: Improve TermTagger usage when TermTagger is not reachable
The TermTagger is not reachable in the time when it is tagging terms. So if the segments are bigger this leads to timeout messages when trying to connect to the termtagger.

TRANSLATE-2004: send import summary mail to pm on import errors
Sends a summary of import errors and warnings to the PM, by default only if the PM did not start the import but via API. Can be overriden by setting always to true in the workflow notification configuration.

TRANSLATE-1977: User can not be assigned to 2 different workflow roles of the same task
A user can not added multiple times in different roles to a task. For example: first as translator and additionaly as second reviewer.

TRANSLATE-1998: Not able to edit segment in editor, segment locked
This was an error in the multi user backend

TRANSLATE-2013: Not replaced relaisLanguageTranslated in task association e-mail
A text fragment was missing in the task association e-mail

TRANSLATE-2012: MessageBus is not reacting to requests
The MessageBus-server was hanging in an endless loop in some circumstances.

TRANSLATE-2003: Remove criticical data from error mails
Some critical data is removed automatically from log e-mails.

TRANSLATE-2005: "Display tracked changes" only when TrackChanges are active for a task
The button to toggle TrackChanges is disabled if TrackChanges are not available due workflow reasons

## [3.4.0] - 2020-03-04

### VERY IMPORTANT NOTES for API users (due TRANSLATE-1455):

*   On assigning users to task the role "lector" was replaced with "reviewer". When using the old value a deprecated message is created and the value is transformed. This should be changed in your application talking to translate5 very soon!

*	In a task the fields taskDeliveryDate and realDeliveryDate are removed! The values are moved on job level (task user association). There are added the new fields deadlineDate and finishedDate. 

### Special Notes

*	TRANSLATE-1969: for adding general hunspell directories to the spellchecker see: https://confluence.translate5.net/display/TIU/Activate+additional+languages+for+spell+checking

*	TRANSLATE-1831: DeepL is now integrated in translate5 for users with support- and development contract: See https://confluence.translate5.net/display/TPLO/DeepL

### Added

TRANSLATE-1960: Define if source or target is connected with visualReview on import
The user can choose now if the uploaded PDF corresponds to the source or target content.

TRANSLATE-1831: Integrate DeepL in translate5 (Only for users with support- and development contract)
The DeepL Integration is only available for users with a support- and development contract. The Plug-In must be activated and the DeepL key configured in the config for usage. See https://confluence.translate5.net/display/TPLO/DeepL

TRANSLATE-1455: Deadlines and assignment dates for every role of a task
This was only possible for the whole task, now per each associated user a dedicated deadline can be defined.

TRANSLATE-1987: Load custom page in the editors branding area
Custom content in the branding area can now be included via URL

TRANSLATE-1927: Pre-translate documents in InstantTranslate
InstantTranslate is now able to translate documents

### Changed

TRANSLATE-1959: InstantTranslate: handle tags in the source as part of the source-text
InstantTranslate is now supposed to handle tags in the source as part of the source-text.

TRANSLATE-1918: VisualReview: log segmentation results
The results of the segmentation is logged into the task log and is sent via email.

TRANSLATE-1916: Change supported browser message
The message about the supported browsers was changed, also IE11 is no not supported anymore.

TRANSLATE-905: Improve maintenance mode
The maintenance mode has now a free-text field to display data to the users, also the maintenance can be announced to all admin users. See https://confluence.translate5.net/display/TIU/install-and-update.sh+functionality

### Bugfixes

TRANSLATE-1989: Erroneously locked segment on tasks with only one user and no simultaneous usage mode
Some segments were locked in the frontend although only one user was working on the task.

TRANSLATE-1988: Enhanced filters button provides drop-down with to much user names
Only the users associated to the tasks visible to the current user should be visible.

TRANSLATE-1986: Unable to import empty term with attributes
An error occurs when importing term with empty term value, valid term attributes and valid term id.

TRANSLATE-1980: Button "open task" is missing for unaccepted jobs
For jobs that are not accepted so far, the "open task" action icon is missing. It should be shown again.

TRANSLATE-1978: In InstantTranslate the Fuzzy-Match is not highlighted correctly
The source difference of fuzzy matches was not shown correctly.

TRANSLATE-1911: Error if spellcheck answer returns from server after task was left already
When the task was left before the spellcheck answer was returned from the server an error occured.

TRANSLATE-1841: pc elements in xliff 2.1 exports are not correctly nested in conjunction with TrackChanges Markup
The xliff 2.1 export produced invalid XML in some circumstances.

TRANSLATE-1981: Sorting the bookmark column produces errors
Sorting the by default hidden bookmark column in the segment table produced an error.

TRANSLATE-1975: Reenable Copy & Paste from term window
Copy and paste was not working any more for the terms listed in the segment meta panel on the right.

TRANSLATE-1973: TrackChanges should not added by default on translation tasks without a workflow with CTRL+INS
When using CTRL+INS to copy the source to the target content, TrackChanges should be only added for review tasks in any case.

TRANSLATE-1972: Default role in translation tasks should be translator not reviewer
This affects the front-end default role in the task user association window.

TRANSLATE-1971: segments excluded with excluded framing ept and bpt tags could not be exported
Very seldom error in combination with segments containing ept and bpt tags.

TRANSLATE-1970: Unable to open Instant-translate/Term-portal from translate5 buttons
This bug was applicable only if the config runtimeOptions.logoutOnWindowClose is enabled.

TRANSLATE-1968: Correct spelling mistake
Fixed a german typo in the user notification on association pop-up.

TRANSLATE-1969: Adding hunspell directories for spell checking does not work for majority of languages
Using external hunspell directories via LanguageTool is working now. Usage is described in https://confluence.translate5.net/display/TIU/Activate+additional+languages+for+spell+checking

TRANSLATE-1966: File-system TBX import error on term-collection create
The file-system based TBX import is now working again.

TRANSLATE-1964: OpenID: Check for provider roles before the default roles check
OpenID was throwing an exception if the default roles are not set for the client domain even if the openid provider provide the roles in the claims response.

TRANSLATE-1963: Tbx import fails when importing a file
On TBX import the TBX parser throws an exception and the import process is stopped only when the file is uploaded from the users itself.

TRANSLATE-1962: SDLLanguageCloud: status always returns unavailable
Checking the status was always returning unavailable, although the LanguageResource is available and working.

TRANSLATE-1919: taskGuid column is missing in LEK_comment_meta
A database column was missing.

TRANSLATE-1913: Missing translation if no language resource is available for the language combination
Just added the missing English translation.

## [3.3.2] - 2019-12-18

### Added

TRANSLATE-1531: Provide progress data about a task
Editors and PMs see the progress of the tasks.

### Changed

TRANSLATE-1896: Delete MemoQ QA-tags on import of memoq xliff
Otherwise the MemoQ file could not be imported

TRANSLATE-1910: When talking to an OpenId server missing ssl certificates can be configured
If the SSO server uses a self signed certificate or is not configured properly a missing certificate chain can be configured in the SSO client used by translate5.

### Bugfixes

TRANSLATE-1824: xlf import does not handle some unicode entities correctly
The special characters are masked as tags now.

TRANSLATE-1909: Reset the task tbx hash when assigned termcollection to task is updated
For the termtagger a cached TBX is created out of all term-collections assigned to a task. On term-collection update this cached file is updated too.

TRANSLATE-1885: Several BugFixes in the GUI
Several BugFixes in the GUI

TRANSLATE-1760: TrackChanges: Bugs with editing content
Some errors according to TrackChanges were fixed.

TRANSLATE-1864: Usage of changealike editor may duplicate internal tags
This happened only under special circumstances.

TRANSLATE-1804: Segments containing only the number 0 are not imported
There were also problems on the export of such segments.

TRANSLATE-1879: Handle removals of corresponding opening and closing tags for tasks with and without trackChanges
If a removed tag was part of a tag pair, the second tag is deleted automatically.

## [3.3.1] - 2019-12-02

### Added

TRANSLATE-1167: Edit task simultanously with multiple users
Multiple users can edit the same task at the same time. See Translate5 confluence how to activate that feature!

TRANSLATE-1493: Filter by user, workflow-step, job-status and language combination
Several new filters can be used in the task overview.

### Changed

TRANSLATE-1871: Enhance theRootCause integration: users can activate video-recording after login
Users can activate optionally video-recording after login to improve error reporting.

TRANSLATE-1889: rfc 5646 value for estonian is wrong
The RFC 5646 value for estonian was wrong

TRANSLATE-1886: Error on refreshing GroupShare TMs when a used TM should be deleted
The error is fixed right now.

TRANSLATE-1884: Special Character END OF TEXT in importable content produces errors.
The special character END OF TEXT is masked in the import now.

TRANSLATE-1840: Insert opening and closing tag surround text selections with one key press
Insert opening and closing tag surround text selections with one key press

## [3.2.13] - 2019-11-12

### Added

TRANSLATE-1839: Show some KPIs in translate5 task overview
One KPI is for example the average time until delivery of a task.

### Changed

TRANSLATE-1858: GroupShare TMs with several languages
Such TMs are listed now correctly.

TRANSLATE-1849: OpenID Connect integration should be able to handle and merge roles from different groups for one user
OpenID Connect integration should be able to handle and merge roles from different groups for one user

TRANSLATE-1848: define collection per user in automated term proposal export
the collections to be exported can be defined now

### Bugfixes

TRANSLATE-1869: Calling TermPortal without Session does not redirect to login
Now the user is redirected to the login page.

TRANSLATE-1866: TermPortal does not filter along termCollection, when showing terms of a termEntry
This is fixed now.

TRANSLATE-1819: TermPortal: comment is NOT mandatory when editing is canceled
TermPortal: comment is NOT mandatory when editing is canceled

TRANSLATE-1865: JS error when resetting segment to initial value
JS error when resetting segment to initial value

TRANSLATE-1480: JS Error on using search and replace
This is fixed now.

TRANSLATE-1863: InvalidXMLException: E1024 - Invalid XML does not bubble correctly into frontend on import
Now the error is logged and shown correctly

TRANSLATE-1861: Not all known default matchrate Icons are shown
All icons are shown again.

TRANSLATE-1850: Non userfriendly error on saving workflow user prefs with an already deleted user
A human readable error message is shown now.

TRANSLATE-1860: Error code error logging in visual review
Errors are now logged into the task log.

TRANSLATE-1862: Copy & Paste from outside the segment
Copy & Paste from outside the segment is now possible

TRANSLATE-1857: Some strings in the interface are shown in German
Translated the missing strings

TRANSLATE-1363: Ensure that search and replace can not be sent without field to be searched
The problem is fixed now

TRANSLATE-1867: TBX-Import: In TBX without termEntry-IDs terms get merged along autogenerated termEntry-ID
The generation of termEntry-ID was changed in the case the TBX does not provide such an ID.

TRANSLATE-1552: Auto set needed ACL roles
If a user gets the role pm, the editor role is needed too. Such missing roles are added now automatically.

VISUAL-52: Red bubble on changed alias segments in layout
Changed alias segments are now also shown as edited

VISUAL-62: Integrate PDF optimization via ghostscript to reduce font errors on HTML conversion
This must be activated in the config. If activated import errors of the PDF files should be reduced.

## [3.2.12] - 2019-10-16

### Added

TRANSLATE-1838: OpenID integration: Support different roles for default roles than for maximal allowed roles

### Bugfixes

TRANSLATE-1719: The supplied node is incorrect or has an incorrect ancestor for this operation

TRANSLATE-1820: TermPortal (engl.): "comment", not "note" or "Anmerkung"

## [3.2.11] - 2019-10-14

### Changed

TRANSLATE-1378 Search & Replace: Activate "Replace"-key right away

TRANSLATE-1615 Move whitespace buttons to segment meta-panel

TRANSLATE-1815 Segment editor should automatically move down a bit

TRANSLATE-1836: Get rid of message "Segment updated in TM!"

### Bugfixes

TRANSLATE-1826: Include east asian sub-languages and thai in string-based termTagging

## [3.2.10] - 2019-10-08

### Added

TRANSLATE-1774: Integrate NEC-TM with translate5 as LanguageResource

## [3.2.9] - 2019-10-07

### Added

TRANSLATE-1671: (Un)lock 100%-Matches in task properties

TRANSLATE-1803: New-options-for-automatic-term-proposal-deletion

TRANSLATE-1816: Create a search & replace button

TRANSLATE-1817: Get rid of head panel in editor

### Bugfixes

TRANSLATE-1551: Readonly task is editable when using VisualReview

TRANSLATE-1761: Clean-up-tbx-for-filesystem-import-directory

TRANSLATE-1790: In-the-general-mail-template-the-portal-link-points-to-wrong-url

## [3.2.8] - 2019-09-24

### Bugfixes

TRANSLATE-1045: javascript error: rendered block refreshed at (this is the fix for the doRefreshView override function in the BufferedRenderer)

TRANSLATE-1219: Editor iframe body is reset and therefore not usable due missing content

TRANSLATE-1756: Excel export error with segments containing an equal sign at the beginning

TRANSLATE-1796: Error on match analysis tab panel open

TRANSLATE-1797: Deleting of terms on import does not work

TRANSLATE-1798: showSubLanguages in TermPortal does not work as it should

TRANSLATE-1799: TermEntry Proposals get deleted, when they should not

TRANSLATE-1800: Uncaught Error: rendered block refreshed at 0 rows

## [3.2.7] - 2019-09-12

### Added

TRANSLATE-1736: Config switch to disable sub-languages for TermPortal search field

TRANSLATE-1741: Usage of user crowds in translate5

TRANSLATE-1734: InstantTranslate: Preset of languages used for translation

TRANSLATE-1735: Optionally make note field in TermPortal mandatory

TRANSLATE-1733: System config in TermPortal: All languages available for adding a new term?

### Changed

TRANSLATE-1792: Make columns in user table of workflow e-mails configurable

TRANSLATE-1791: Enable neutral salutation

### Bugfixes

TRANSLATE-1742: Not configured mail server may crash application

TRANSLATE-1771: "InstantTranslate Into" available in to many languages

TRANSLATE-1788: Javascript error getEditorBody.textContent() is undefined

TRANSLATE-1782: Minor TermPortal bugs fixed

## [3.2.6] - 2019-08-29

### Added

TRANSLATE-1763: Import comments from SDLXLIFF to translate5

TRANSLATE-1776: Terminology in meta panel is also shown on just clicking on a segment

### Bugfixes

TRANSLATE-1730: Delete change markers from SDLXLIFF

TRANSLATE-1778: TrackChanges fail cursor-position in Firefox

TRANSLATE-1781: TrackChanges: reset in combination with matches is buggy

TRANSLATE-1770: TrackChanges: reset to initial content must not mark own changes as as change

TRANSLATE-1765: TrackChanges: Content marked as insert produces problems with SpellChecker

TRANSLATE-1767: Cloning of task where assigned TBX language resource has been deleted leads to failed import

## [3.2.5] - 2019-08-20

### Changed

TRANSLATE-1738: Add "Added from MT" to note field of Term, if term stems from InstantTranslate

TRANSLATE-1739: InstantTranslate: Add button to switch languages

TRANSLATE-1737: Only show "InstantTranslate into" drop down, if no field is open for editing

TRANSLATE-1743: Term proposal system: Icons and Shortcuts for Editing

### Bugfixes

TRANSLATE-1752: error E1149 - Export: Some segments contains tag errors is logged to much on proofreading tasks.

TRANSLATE-1732: Open Bugs term proposal system

TRANSLATE-1749: spellcheck is not working any more in Firefox

TRANSLATE-1758: Combination of trackchanges and terminology produces sometimes corrupt segments (warning "E1132")

TRANSLATE-1755: Transit Import is not working anymore

TRANSLATE-1754: Authentication via session auth hash does a wrong redirect if the instance is located in a sub directory

TRANSLATE-1750: Loading of tasks in the task overview had a bad performance

TRANSLATE-1747: E9999 - Missing Arguments $code and $message

TRANSLATE-1757: JS Error in LanguageResources Overview if task names contain " characters

## [3.2.4] - 2019-07-30

### Added

TRANSLATE-1720: Add segment editing history (snapshots) to JS debugging (rootcause)

TRANSLATE-1273: Propose new terminology and terminology changes

### Bugfixes

TRANSLATE-717: Blocked column in segment grid shows no values and filter is inverted

TRANSLATE-1305: Exclude framing internal tags from xliff import also for translation projects

TRANSLATE-1724: TrackChanges: JavaSript error: WrongDocumentError (IE11 only)

TRANSLATE-1721: JavaScript error: me.allMatches is null

TRANSLATE-1045: JavaScript error: rendered block refreshed at 16 rows while BufferedRenderer view size is 48

TRANSLATE-1717: Segments containing one whitespace character can crash Okapi on export

TRANSLATE-1718: Flexibilize LanguageResource creation via API by allow also language lcid

TRANSLATE-1716: Pretranslation does not replace tags in repetitions correctly

TRANSLATE-1634: TrackChanges: CTRL+Z: undo works, but looses the TrackChange-INS

TRANSLATE-1711: TrackChanges are not added on segment reset to import state

TRANSLATE-1710: TrackChanges are not correct on taking over TM match

TRANSLATE-1627: SpellCheck impedes TrackChanges for CTRL+V and CTRL+. into empty segments

## [3.2.3] - 2019-07-17

### Added

TRANSLATE-1489: Export task as excel and be able to reimport it

### Bugfixes

TRANSLATE-1464: SpellCheck for Japanese and other languages using the Microsoft Input Method Editor

TRANSLATE-1715: XLF Import: Segments with tags only should be ignored and pretranslated automatically on translation tasks

TRANSLATE-1705: Pre-translation does not remove "AdditionalTag"-Tag from OpenTM2

TRANSLATE-1637: MatchAnalysis: Errors in Frontend when analysing multiple tasks

TRANSLATE-1658: Notify assoc users with state open in notifyOverdueTasks

TRANSLATE-1709: Missing translator checkers in email when all proofreaders are finished

TRANSLATE-1708: Possible server error on segment search

TRANSLATE-1707: XLIFF 2.1 Export creates invalid XML

TRANSLATE-1702: Multiple parallel export of the same task from the same session leads to errors

TRANSLATE-1706: Improve TrackChanges markup for internal tags in Editor

## [3.2.2] - 2019-06-27

### Added

TRANSLATE-1676: Disable file extension check if a custom bconf is provided

### Changed

TRANSLATE-1665: Change font colour to black

### Bugfixes

TRANSLATE-1701: Searching in bookmarked segments leads to SQL error (missing column)

TRANSLATE-1660: Remove message for unsupported browser for MS Edge

TRANSLATE-1620: Relais (pivot) import does not work, if Trados alters mid

TRANSLATE-1181: Workflow Cron Daily actions are called multiple times

TRANSLATE-1695: VisualReview: segmentmap generation has a bad performance on task load

				** one of the two improvements is not applied automatically to the task **

				** if you still experience loading timeouts in the GUI on opening a task **

				** go to task administration, end the task, and reopen it again, this is decribed in: **

				** https://jira.translate5.net/browse/TRANSLATE-1695?focusedCommentId=20594&page=com.atlassian.jira.plugin.system.issuetabpanels:comment-tabpanel#comment-20594 **

TRANSLATE-1694: Allow SDLXLIFF tags with dashes in the ID

TRANSLATE-1691: Search and Replace does not escape entities in the replaced text properly

TRANSLATE-1684: Uneditable segments with tags only can lose content on export

TRANSLATE-1669: repetition editor deletes wrong tags

TRANSLATE-1693: Search and Replace does not open segment on small tasks

TRANSLATE-1666: Improve error communication when uploading a import package without proofRead folder

TRANSLATE-1689: Pressing "tab" in search and replace produces a JS error

TRANSLATE-1683: Inserting white-space tags in the editor can overwrite other tags in the target

TRANSLATE-1659: Change of description for auto-assignment area in user management

TRANSLATE-1654: TermTagger stops working on import of certain task - improved error management and logging

				** if a termtagger is not reachable anymore (due crash or not started) it will be deactivated in translate5 **

				** for reactivation of such deactivated termtaggers see the instructions to E1129 **

				** in https://confluence.translate5.net/display/TAD/EventCodes#EventCodes-E1129 **

## [3.2.1] - 2019-05-10

### Added

TRANSLATE-1403: Anonymize users in the workflow

### Changed

TRANSLATE-1648: Disable the drop down menu in the column head of the task grid via ACL

TRANSLATE-1636: OpenID Connect: Automatically remove protocol from translate5 domain

VISUAL-64: VisualReview: Improve texts on leaving visualReview task

### Bugfixes

TRANSLATE-1646: The frontend inserts invisible BOM (EFBBBF) characters into the saved segment

TRANSLATE-1642: Saving client with duplicate "translate5 domain" shows wrong error message

T5DEV-267: GroupShare Integration pre-translation and analysis does not work

TRANSLATE-1635: OpenID Connect: Logout URL of TermPortal leads to error, when directly login again with OpenID via MS ActiveDirectory

TRANSLATE-1633: Across XLF comment import does provide wrong comment date

TRANSLATE-1641: Adjust the translate5 help window width and height

TRANSLATE-1640: OpenID Connect: Customer domain is mandatory for OpenId group

TRANSLATE-1632: JS: Cannot read property 'length' of undefined

TRANSLATE-1631: JS: me.store.reload is not a function

TRANSLATE-337: uniqid should not be used for security relevant issues

TRANSLATE-1639: OpenID Connect: OpenId authorization redirect after wrong translate5 password

TRANSLATE-1638: OpenID Connect: OpenId created user is not editable

## [3.2.0] - 2019-04-17

### Added

VISUAL-63: VisualReview for translation tasks

TRANSLATE-355: Better error handling and user communication on import and export errors

### Changed

TRANSLATE-702: Migrate translate5 to be using PHP 7.3

TRANSLATE-613: Refactor error messages and error handling

TRANSLATE-293: create separate config for error mails receiver

### Bugfixes

TRANSLATE-1605: TrackChanges splits up the words send to the languagetool

TRANSLATE-1624: TrackChanges: type after CTRL+A after choosing a match

TRANSLATE-1256: In the editor CTRL-Z (undo) does not work after pasting content

TRANSLATE-1356: In the editor the caret is placed wrong after CTRL+Z

TRANSLATE-1520: Last CTRL+Z "loses" the caret in the Edtior

## [3.1.3] - 2019-04-08

### Added

TRANSLATE-1600: TrackChanges: Make tracked change marks hideable via a button and keyboard short-cut

TRANSLATE-1390: Microsoft translator can be used as language resource

### Bugfixes

TRANSLATE-1613: The segment timestamp is not set properly with MySQL 8

TRANSLATE-1612: Task clone does not clone language resources

TRANSLATE-1604: Jobs may not be created with status finished

TRANSLATE-1609: API Usage: On task creation no PM can be explicitly defined

TRANSLATE-1603: Show the link to TermPortal in InstantTranslate only, if user has TermPortal access rights

TRANSLATE-1595: Match analysis export button is disabled erroneously

TRANSLATE-1597: Concordance search uses only the source language

TRANSLATE-1607: Feature logout on page change disables language switch

TRANSLATE-1599: Error in Search and Replace repaired

T5DEV-266: Sessions can be hijacked

## [3.1.1] - 2019-02-28

### Added

TRANSLATE-1589: Separate button to sync the GroupShare TMs in LanguageResources panel

TRANSLATE-1586: Close session on browser window close

TRANSLATE-1581: Click on PM Name in task overview opens e-mail program to send an e-mail to the PM

TRANSLATE-1457: Use OpenID Connect optionally for authentication and is now able to run under different domains

### Changed

TRANSLATE-1583: VisualReview: Change the button layout in "leave visual review" messagebox

TRANSLATE-1584: Rename "Autostatus" to "Bearbeitungsstatus" in translate5 editor (german GUI)

TRANSLATE-1542: InstantTranslate: Improve language selection in InstantTranslate

TRANSLATE-1587: Enable session delete to delete via internalSessionUniqId

### Bugfixes

TRANSLATE-1579: TermTagger is not tagging terminology automatically on task import wizard

TRANSLATE-1588: Pre-translation is running although it was disabled

TRANSLATE-1572: Import language resources in background

TRANSLATE-1575: Unable to take over match from language resources match grid in editor

TRANSLATE-1567: Globalese integration: Error occurred during file upload or translation

TRANSLATE-1560: Introduce a config switch to disable match resource panel

TRANSLATE-1580: Remove word count field from the task import wizard

TRANSLATE-1571: Copy and paste segment content does not work when selecting whole source segment

## [3.0.5] - 2019-02-07

### Bugfixes

TRANSLATE-1570: Editor-only usage (embedded translate5) was not working properly due JS errors

TRANSLATE-1548: TrackChanges: nested DEL tags in the frontend

TRANSLATE-1526: TrackChanges: pasting content into the editor could lead to an JS error

TRANSLATE-1566: Segment pixel length restriction does not work with globalese pretranslation

TRANSLATE-1556: pressing ctrl-c in language resource panel produced an JS error

TRANSLATE-910: Fast clicking on segment bookmark button produces an error on server side

TRANSLATE-1545: Term details are not displayed in term portal

TRANSLATE-1525: TrackChanges: seldom error in the GUI fixed

TRANSLATE-1230: Translate5 was not usable on touch devices

## [3.0.4] - 2019-01-31

### Changed

TRANSLATE-1555: Okapi Import: Add SRX segmentation rules for most common languages

### Bugfixes

TRANSLATE-1557: Implement the missing workflow step workflowEnded

TRANSLATE-1299: metaCache generation is cut off by mysql setting

TRANSLATE-1554: List only terms in task languages combination in editor terminology list

TRANSLATE-1550: unnecessary okapiarchive.zip wastes harddisk space

## [3.0.3] - 2019-01-24

### Added

TRANSLATE-1547: Workflow: send mail to PM if one user finishes a task

TRANSLATE-1386: Pixel-based length restrictions for display text translation

## [3.0.2] - 2019-01-21

### Added

TRANSLATE-1523: Configurable: Should source files be auto-attached as reference files?

### Changed

TRANSLATE-1543: InstantTranslate: show main languages in InstantTranslate language selection

TRANSLATE-1533: Switch API value, that is checked to know, if Globalese engine is available

### Bugfixes

TRANSLATE-1540: Filtering language resources by customer replaces resource name with customer name

TRANSLATE-1541: For title tag of TermPortal and InstantTranslate translation mechanism is not used

TRANSLATE-1537: GroupShare sync throws an exception if a language can no be found locally

TRANSLATE-1535: GroupShare license cache ID may not contain special characters

TRANSLATE-1534: internal target marker persists as translation on pretranslation with fuzzy match analysis

TRANSLATE-1532: Globalese integration: error 500 thrown, if no engines are available

TRANSLATE-1518: Multitenancy language resources to customer association fix (customer assoc migration fix)

TRANSLATE-1522: Autostaus "Autoübersetzt" is untranslated in EN

VISUAL-57: VisualReview: Prevent translate5 to scroll layout, if segment has been opened by click in the layout

TRANSLATE-1519: Termcollection is not assigned with default customer with zip import

TRANSLATE-1521: OpenTM2 Matches with <it> or <ph> tags are not shown

TRANSLATE-1501: TrackChanges: Select a word with double click then type new text produces JS error and wrong track changes

TRANSLATE-1544: JS error on using grid filters

TRANSLATE-1527: JS error on copy text content in task overview area

TRANSLATE-1524: JS Error when leaving task faster as server responds terms of segment

TRANSLATE-1503: CTRL+Z does not undo CTRL+.

TRANSLATE-1412: TermPortal logout URL is wrong - same for InstantTranslate

TRANSLATE-1517: Add user: no defaultcustomer if no customer is selected

TRANSLATE-1538: click in white head area of TermPortal or InstantTranslate leads to action

TRANSLATE-1539: click on info icon of term does not transfer sublanguage, when opening term in TermPortal

## [3.0.1] - 2018-12-21

### Bugfixes

TRANSLATE-1412: TermPortal logout URL is wrong

TRANSLATE-1504: TermTagging does not work with certain terms

Fixing several smaller problems

## [3.0.0] - 2018-12-20

### PHP Installation

For PHP installation the package intl must be activated!

 

### Added

TRANSLATE-1490: Highlight fuzzy range in source of match in translate5 editor

TRANSLATE-1430: Enable copy and paste of internal tags from source to target

TRANSLATE-1397: Multitenancy phase 1

TRANSLATE-1206: Add Whitespace chars to segment

### Changed

TRANSLATE-1483: PHP Backend: Implement an easy way to join tables for filtering via API

TRANSLATE-1460: Deactivate export menu in taskoverview for editor users

### Bugfixes

TRANSLATE-1500: PM dropdown field in task properties shows max 25 users

TRANSLATE-1497: Convert JSON.parse calls to Ext.JSON.decode calls for better debugging

TRANSLATE-1491: Combine multiple OpenTM2 100% matches to one match

TRANSLATE-1488: JS Error "Cannot read property 'row' of null" on using bookmark functionality

TRANSLATE-1487: User can not change his own password

TRANSLATE-1477: Error on removing a user from a task which finished then the task

TRANSLATE-1476: TrackChanges: JS Error when replacing a character in certain cases

TRANSLATE-1475: Merging of term tagger result and track changes content leads to several errors

TRANSLATE-1474: Clicking in Treepanel while segments are loading is creating an error

TRANSLATE-1472: Task delete throws DB foreign key constraint error

TRANSLATE-1470: Do not automatically add anymore missing tags on overtaking results from language resources

TRANSLATE-146: Internal translation mechanism creates corrupt XLIFF

TRANSLATE-1465: InstantTranslate: increased input-field must not be covered by other elements

TRANSLATE-1463: Trigger workflow action not in all remove user cases

TRANSLATE-1449: Spellcheck needs to handle whitespace tags as space / word boundary

TRANSLATE-1440: Short tag view does not accurately reflect tag order and relationship between tags

TRANSLATE-1505: Several smaller issues

TRANSLATE-1429: TrackChanges: Unable to get property 'className' of undefined or null reference

TRANSLATE-1398: TrackChanges: Backspace and DEL are removing whole content instead only single characters

TRANSLATE-1333: Search and Replace: JS Error: Die Eigenschaft "getActiveTab" eines undefinierten oder Nullverweises kann nicht abgerufen werden

TRANSLATE-1332: Search and Replace - JS error: record is undefined

TRANSLATE-1300: TrackChanges: Position of the caret after deleting from CTRL+A

TRANSLATE-1020: Tasknames with HTML entities are producing errors in segmentstatistics plugin

T5DEV-251: Several issues in InstantTranslate

T5DEV-253: Several issues in match analysis and pre-translation

TRANSLATE-1499: Task Name filtering does not work anymore after leaving a task

## [2.9.2] - 2018-10-30

### Bugfixes

Fixing Problems in IE11

TRANSLATE-1451: Missing customer frontend right blocks whole language resources

TRANSLATE-1453: Updating from an older version of translate5 led to errors in updating

## [2.9.1] - 2018-10-25

### Added

TRANSLATE-1339: InstantTranslate-Portal: integration of SDL Language Cloud, Terminology, TM and MT resources and similar

TRANSLATE-1362: Integrate Google Translate as language resource

TRANSLATE-1162: GroupShare Plugin: Use SDL Trados GroupShare as Language-Resource

### Changed

VISUAL-56: VisualReview: Change text that is shown, when segment is not connected

### Bugfixes

TRANSLATE-1447: Escaping XML Entities in XLIFF 2.1 export (like attribute its:person)

TRANSLATE-1448: translate5 stops loading with Internet Explorer 11

## [2.8.7] - 2018-10-16

### Added

TRANSLATE-1433: Trigger workflow actions also on removing a user from a task

VISUAL-26: VisualReview: Add buttons to resize layout

TRANSLATE-1207: Add buttons to resize/zoom segment table

TRANSLATE-1135: Highlight and Copy text in source and target columns

### Changed

TRANSLATE-1380: Change skeleton-files location from DB to filesystem

TRANSLATE-1381: Print proper error message if SDLXLIFF with comments is imported

TRANSLATE-1437: Collect relais file alignment errors instead mail and log each error separately

TRANSLATE-1396: Remove the misleading "C:\fakepath\" from task name

### Bugfixes

TRANSLATE-1442: Repetition editor uses sometimes wrong tag 

TRANSLATE-1441: Exception about missing segment materialized view on XLIFF2 export

TRANSLATE-1382: Deleting PM users associated to tasks can lead to workflow errors

TRANSLATE-1335: Wrong segment sorting and filtering because of internal tags

TRANSLATE-1129: Missing segments on scrolling with page-down / page-up

TRANSLATE-1431: Deleting a comment can lead to a JS exception

VISUAL-55: VisualReview: Replace special Whitespace-Chars

TRANSLATE-1438: Okapi conversion did not work anymore due to Okapi Longhorn bug

## [2.8.6] - 2018-09-13

### Added

TRANSLATE-1425: Provide ImportArchiv.zip as download from the export menu for admin users

### Bugfixes

TRANSLATE-1426: Segment length calculation was not working due not updated metaCache

TRANSLATE-1370: Xliff Import can not deal with empty source targets as single tags

TRANSLATE-1427: Date calculation in Notification Mails is wrong

TRANSLATE-1177: Clicking into empty area of file tree produces sometimes an JS error

TRANSLATE-1422: Uncaught TypeError: Cannot read property 'record' of undefined

## [2.8.5] - 2018-08-27

### Added

VISUAL-50: VisualReview: Improve initial loading performance by accessing images directly and not via PHP proxy

### Changed

VISUAL-49: VisualReview: Extend default editor mode to support visualReview

TRANSLATE-1415: Rename startViewMode values in config

### Bugfixes

TRANSLATE-1416: exception 'PDOException' with message 'SQLSTATE[42S01]: Base table or view already exists: 1050 Table 'siblings' already exists'

VISUAL-48: VisualReview: Improve visualReview scroll performance on very large VisualReview Projects

TRANSLATE-1413: TermPortal: Import deletes all old Terms, regardless of the originating TermCollection

TRANSLATE-1392: Unlock task on logout

TRANSLATE-1417: Task Import ignored termEntry IDs and produced there fore task mismatch

## [2.8.4] - 2018-08-17

### Added

TRANSLATE-1375: Map arbitrary term attributes to administrativeStatus

### Bugfixes

VISUAL-46: Loading screen until everything loaded

## [2.8.3] - 2018-08-14

### Bugfixes

TRANSLATE-1404: TrackChanges: Tracked changes disappear when using backspace/del

## [2.8.2] - 2018-08-14

### Bugfixes

TRANSLATE-1376: Segment length calculation does not include length of content outside of mrk tags

TRANSLATE-1399: Using backspace on empty segment increases segment length

TRANSLATE-1395: Enhance error message on missing relais folder

TRANSLATE-1379: TrackChanges: disrupt conversion into japanese characters

TRANSLATE-1373: TermPortal: TermCollection import stops because of unsaved term

TRANSLATE-1372: TrackChanges: Multiple empty spaces after export

## [2.8.1] - 2018-08-08

### Added

TRANSLATE-1352: Include PM changes in changes-mail and changes.xliff (xliff 2.1)

TRANSLATE-884: Implement generic match analysis and pre-translation (on the example of OpenTM2)

TRANSLATE-392: Systemwide (non-persistent) memory

### Changed

VISUAL-31: Visual Review: improve segmentation

TRANSLATE-1360: Make PM dropdown in task properties searchable

### Bugfixes

TRANSLATE-1383: Additional workflow roles associated to a task prohibit a correct workflow switching

TRANSLATE-1161: Task locking clean up is only done on listing the task overview

TRANSLATE-1067: API Usage: 'Zend_Exception' with message 'Indirect modification of overloaded property Zend_View::$rows has no effect

TRANSLATE-1385: PreFillSession Resource Plugin must be removed

TRANSLATE-1340: IP based SessionRestriction is to restrictive for users having multiple IPs

TRANSLATE-1359: PM to task association dropdown in task properties list does not contain all PMs

## [2.7.9] - 2018-07-17

### Changed

TRANSLATE-1349: Remove the message of saving a segment successfully

### Bugfixes

TRANSLATE-1337: removing orphaned tags is not working with tag check save anyway

TRANSLATE-1245: Add missing keyboard shortcuts related to segment commenting

TRANSLATE-1326: Comments for non-editable segment in visualReview mode and normal mode

TRANSLATE-1345: Unable to import task with Relais language and terminology

TRANSLATE-1347: Unknown Term status are not set to the default as configured

TRANSLATE-1351: Remove jquery from official release and bundle it as dependency

TRANSLATE-1353: Huge TBX files can not be imported

## [2.7.8] - 2018-07-04

### Changed

VISUAL-43: VisualReview: Split segments search into long, middle, short

TRANSLATE-1323: SpellCheck must not remove the TermTag-Markup

TRANSLATE-1331: add application version to task table

### Bugfixes

TRANSLATE-1234: changes.xliff diff algorithm fails under some circumstances

TRANSLATE-1306: SpellCheck: blocked after typing with MatchResources

TRANSLATE-1336: TermPortal: functions by IE11 in termportal

## [2.7.7] - 2018-06-27

### Bugfixes

TRANSLATE-1324: RepetitionEditor: repetitions could not be saved due JS error

## [2.7.6] - 2018-06-27

### Added

TRANSLATE-1269: TermPortal: Enable deletion of older terms

TRANSLATE-858: SpellCheck: Integrate languagetool grammer, style and spell checker as micro service

VISUAL-44: VisualReview: Make "switch editor mode"-button configureable in visualReview

### Changed

TRANSLATE-1310: Improve import performance by SQL optimizing in metacache update

TRANSLATE-1317: Check for /data/import folder

TRANSLATE-1304: remove own js log call for one specific segment editing error in favour of rootcause

TRANSLATE-1287: TermPortal: Introduce scrollbar in left result column of termPortal

TRANSLATE-1296: Simplify error message on missing tags

TRANSLATE-1295: Remove sorting by click on column header in editor

### Bugfixes

TRANSLATE-1311: segmentMeta transunitId was set to null or was calculated wrong for string ids

TRANSLATE-1313: No error handling if tasks languages are not present in TBX

TRANSLATE-1315: SpellCheck & TrackChanges: corrected errors still marked

T5DEV-245: Error on opening a segment

TRANSLATE-1283: TermPortal: Term collection attributes translation

TRANSLATE-1318: TermPortal: Pre-select search language with matching GUI language group

TRANSLATE-1294: TermPortal: Undefined variable: translate in termportal

TRANSLATE-1292: TermPortal: Undefined variable: file in okapi worker

TRANSLATE-1286: TermPortal: Number shows up, when selecting term from the live search

## [2.7.5] - 2018-05-30

### Changed

TRANSLATE-1269: Enable deletion of older terms

TINTERNAL-28: Change TBX Collection directory naming scheme

TRANSLATE-1268: Pre-select language of term search with GUI-language

TRANSLATE-1266: Show "-" as value instead of provisionallyProcessed

### Bugfixes

TRANSLATE-1231: xliff 1.2 import can not handle different number of mrk-tags in source and target

TRANSLATE-1265: Deletion of task does not delete dependent termCollection

TRANSLATE-1283: TermPortal: Add GUI translations for Term collection attributes

TRANSLATE-1284: TermPortal: term searches are not restricted to a specific term collection

## [2.7.4] - 2018-05-24

### Added

TRANSLATE-1135: Highlight and Copy text in source and target columns

### Bugfixes

TRANSLATE-1267: content between two track changes DEL tags is getting deleted on some circumstances

VISUAL-33: Huge VisualReview projects lead to preg errors in PHP postprocessing of generated HTML

TRANSLATE-1102: Calling default modules pages by ajax can lead to logout by loosing the session

TRANSLATE-1226: Zend_Exception with message Array to string conversion

## [2.7.3] - 2018-05-09

### Bugfixes

TRANSLATE-1243: IE11 could not load Segment.js

TRANSLATE-1239: JS: Uncaught TypeError: Cannot read property 'length' of undefined

## [2.7.2] - 2018-05-08

### Changed

TRANSLATE-1240: Integrate external libs correctly in installer

### Bugfixes

requests producing a 404 were causing a logout instead of showing 404

## [2.7.1] - 2018-05-07

### Added

TRANSLATE-1136: Check for content outside of mrk-tags (xliff)

TRANSLATE-1192: Length restriction: Add length of several segments

TRANSLATE-1130: Show specific whitespace-Tag

TRANSLATE-1190: Automatic import of TBX files

TRANSLATE-1189: Flexible Term and TermEntry Attributes

TRANSLATE-1187: Introduce TermCollections

TRANSLATE-1188: Extending the TBX-import

TRANSLATE-1186: new system role "termCustomerSearch"

TRANSLATE-1184: Client management

TRANSLATE-1185: Add field "end client" to user management

### Changed

VISUAL-30: The connection algorithm connects segments only partially

### Bugfixes

TRANSLATE-1229: xliff 1.2 export deletes tags

TRANSLATE-1236: User creation via API should accept a given userGuid

TRANSLATE-1235: User creation via API produces errors on POST/PUT with invalid content

TRANSLATE-1128: Selecting segment and scrolling leads to jumping of grid

TRANSLATE-1233: Keyboard Navigation through grid looses focus

## [2.6.30] - 2018-04-16

### Changed

TRANSLATE-1218: XLIFF Import: preserveWhitespace per default to true

Renamed all editor modes

### Bugfixes

TRANSLATE-1154: Across xliff import does not set match rate

TRANSLATE-1215: TrackChanges: JS Exception on CTRL+. usage

TRANSLATE-1140: Row editor is not displayed after the first match in certain situations.

TRANSLATE-1219: Editor iframe body is reset and therefore not usable due missing content

VISUAL-28: Opening of visual task in IE 11 throws JS error

## [2.6.29] - 2018-04-11

### Added

TRANSLATE-1130: Show specific whitespace-Tag

TRANSLATE-1132: Whitespace tags: Always deleteable

TRANSLATE-1127: xliff: Preserve whitespace between mrk-Tags

TRANSLATE-1137: Show bookmark and comment icons in autostatus column

TRANSLATE-1058: Send changelog via email to admin users when updating with install-and-update script

### Changed

T5DEV-217: remaining search and replace todos

TRANSLATE-1200: Refactor images of internal tags to SVG content instead PNG

### Bugfixes

TRANSLATE-1209: TrackChanges: content tags in DEL INS tags are not displayed correctly in full tag mode

TRANSLATE-1212: TrackChanges: deleted content tags in a DEL tag can not readded via CTRL+, + Number

TRANSLATE-1210: TrackChanges: Using repetition editor on segments where a content tag is in a DEL and INS tag throws an exception

TRANSLATE-1194: TrackChanges: When the export removes deleted words, no double spaces must be left.

TRANSLATE-1124: store whitespace tag metrics into internal tag

VISUAL-24: visualReview: After adding a comment, a strange white window appears

## [2.6.27] - 2018-03-15

### Changed

T5DEV-236; visualReview: Matching-Algorithm: added some more "special spaces"

### Bugfixes

TRANSLATE-1183: Error in TaskActions.js using IE11

TRANSLATE-1182: VisualReview - JS Error: Cannot read property 'getIframeBody' of undefined

## [2.6.26] - 2018-03-15

### Added

T5DEV-213: XlfExportTranslateByAutostate Plug-In

### Changed

TRANSLATE-1180: improve logging and enduser communication in case of ZfExtended_NoAccessException exceptions

TRANSLATE-1179: HEAD and OPTIONS request should not create a log entry

## [2.6.25] - 2018-03-12

### Added

TRANSLATE-1166: task-status-unconfirmed

TRANSLATE-1070: Make initial values of checkboxes in task add window configurable

TRANSLATE-949: delete old tasks by cron job (config sql file)

### Changed

TRANSLATE-1144: Disable translate5 update popup for non admin users

PMs without loadAllTasks should be able to see their tasks, even without a task assoc.

TRANSLATE-1114: TrackChanges: fast replacing selected content triggers debugger statement

TRANSLATE-1145: Using TrackChanges and MatchResources

TRANSLATE-1143: The text in the tooltips with ins-del tags is not readable in visualReview layout

T5DEV-234 TrackChanges: reproduce handleDigitPreparation for Keyboard-Events

### Bugfixes

TRANSLATE-1178: if there are only directories and not files in proofRead, this results in "no importable files in the task"

TRANSLATE-1078: visualReview: Upload of PDF in wizard does not work

TRANSLATE-1164: VisualReview throws an exception with disabled headpanel

TRANSLATE-1155: Adding a translation check user to a proofreading task changes workflow step to translation

TRANSLATE-1153: Find Editor after opening another Task

TRANSLATE-1148: Maximum characters allowed in toSort column is over the limit

TRANSLATE-969: Calculation of next editable segment fails when sorting and filtering for a content column

TRANSLATE-1147: #UT messages still inside of trackChanges tooltip

TRANSLATE-1042: copy source to target is not working in firefox

## [2.6.23] - 2018-02-15

### Changed

TRANSLATE-1142: Task DB migration tracker

T5DEV-228: VisualReview: aliased segments get a tooltip now

### Bugfixes

TRANSLATE-1096: Changelog model produce unneeded error log

## [2.6.22] - 2018-02-13

### Added

TRANSLATE-32: Search and Replace in translate5 editor

TRANSLATE-1116: Clone a already imported task

TRANSLATE-1109: Enable import of invalid XLIFF used for internal translations

TRANSLATE-1107: VisualReview converter server wrapper

### Changed

TRANSLATE-1019: Improve File Handling Architecture in the import process

T5DEV-218: Enhance visualReview matching algorithm

TRANSLATE-1017: Use Okapi longhorn for merging files back instead tikal

TRANSLATE-1121: Several minor improvement in the installer

TRANSLATE-667: GUI cancels task POST requests longer than 60 seconds

### Bugfixes

TRANSLATE-1131: Internet Explorer compatibility mode results in non starting application

TRANSLATE-1122: TrackChanges: saving content to an attached matchresource (openTM2) saves also the <del> content

TRANSLATE-1108: VisualReview: absolute paths for CSS and embedded fonts are not working on installations with a modified APPLICATION_RUNDIR

TRANSLATE-1138: Okapi Export does not work with files moved internally in translate5

TRANSLATE-1112: Across XML parser has problems with single tags in the comment XML

TRANSLATE-1110: Missing and wrong translated user roles in the notifyAllAssociatedUsers e-mail

TRANSLATE-1117: In IE Edge in the HtmlEditor the cursor cannot be moved by mouse only by keyboard

TRANSLATE-1141: TrackChanges: Del-tags are not ignored when the characters are counted in min/max length

## [2.6.21] - 2018-01-22

### Bugfixes

TRANSLATE-1076: Windows Only: install-and-update-batch overwrites path to mysql executable

TRANSLATE-1103: TrackChanges Plug-In: Open segment for editing leads to an error in IE11

## [2.6.20] - 2018-01-18

### Bugfixes

TRANSLATE-1097: Current release produces SQL error on installation

## [2.6.18] - 2018-01-17

### Added

TRANSLATE-950: Implement a user hierarchy for user listing and editing

TRANSLATE-1089: Create segment history entry when set autostatus untouched, auto-set and reset username on unfinish

TRANSLATE-1099: Exclude framing internal tags from xliff import

TRANSLATE-941: New front-end rights

TRANSLATE-942: New task attributes tab in task properties window

TRANSLATE-1090: A user without setaclrole for a specific role can revoke such already granted roles

### Changed

Integrate segmentation rules for EN in Okapi default bconf-file

TRANSLATE-1091: Rename "language" field/column in user grid / user add window

### Bugfixes

TRANSLATE-1101: Using Translate5 in internet explorer leads sometimes to logouts while application load

TRANSLATE-1086: Leave visualReview task leads to error in IE 11

T5DEV-219: Subsegment img found on saving some segments with tags and enabled track changes

## [2.6.16] - 2017-12-14

### Changed

TRANSLATE-1084: refactor internal translation mechanism

### Bugfixes

several smaller issues

## [2.6.14] - 2017-12-11

### Added

TRANSLATE-1061: Add user locale dropdown to user add and edit window

### Bugfixes

TRANSLATE-1081: Using a taskGuid filter on /editor/task does not work for non PM users

TRANSLATE-1077: Segment editing in IE 11 does not work

## [2.6.13] - 2017-12-07

### Added

TRANSLATE-822: segment min and max length - activated in Frontend

TRANSLATE-869: Okapi integration - improved tikal logging

## [2.6.12] - 2017-12-06

### Added

TRANSLATE-1074: Editor-only mode: On opening finished task: Open in read-only mode

### Changed

TRANSLATE-1055: Disable therootcause feedback button

TRANSLATE-1073: Update configured languages.

TRANSLATE-1072: Set default GUI language for users to EN

### Bugfixes

visualReview: fixes for translate5 embedded editor usage and RTL fixes

## [2.6.11] - 2017-11-30

### Added

TRANSLATE-935: Configure columns of task overview on system level

### Changed

TRANSLATE-905: Improve formatting of the maintenance mode message and add timezone to the timestamp.

### Bugfixes

T5DEV-198: Fixes for the non public VisualReview Plug-In

TRANSLATE-1063: VisualReview Plug-In: missing CSS for internal tags and to much line breaks

TRANSLATE-1053: Repetition editor starts over tag check dialog on overtaking segments from MatchResource

## [2.6.10] - 2017-11-14

### Added

TRANSLATE-931: Tag check can NOT be skipped in case of error

TRANSLATE-822: segment min and max length

TRANSLATE-1027: Add translation step in workflow

### Changed

Bundled OpenTM2 Installer 1.4.1.2 

### Bugfixes

TRANSLATE-1001: Tag check does not work for translation tasks

TRANSLATE-1037: VisualReview and feedback button are overlaying each other

TRANSLATE-763: SDLXLIFF imports no segments with empty target tags

TRANSLATE-1051: Internal XLIFF reader for internal application translation can not deal with single tags

## [2.6.4] - 2017-10-19

### Added

TRANSLATE-944: Import and Export comments from Across Xliff

TRANSLATE-1013: Improve embedded translate5 usage by a static link

T5DEV-161: Non public VisualReview Plug-In

### Changed

TRANSLATE-1028: Correct wrong or misleading language shortcuts

## [2.6.2] - 2017-10-16

### Added

TRANSLATE-869: Okapi integration for source file format conversion

TRANSLATE-995: Import files with generic XML suffix with auto type detection

TRANSLATE-994: Support RTL languages in the editor

### Changed

TRANSLATE-1012: Improve REST API on task creation

TRANSLATE-1004: Enhance text description for task grid column to show task type

### Bugfixes

TRANSLATE-1011: XLIFF Import can not deal with internal unicodePrivateUseArea tags

TRANSLATE-1015: Reference Files are not attached to tasks

TRANSLATE-983: More tags in OpenTM2 answer than in translate5 segment lead to error

TRANSLATE-972: translate5 does not check, if there are relevant files in the import zip

## [2.6.1] - 2017-09-14

### Added

TRANSLATE-994: Support RTL languages in the editor (must be set in LEK_languages)

TRANSLATE-974: Save all segments of a task to a TM

### Changed

TRANSLATE-925: support xliff 1.2 as import format - improve fileparser to file extension mapping

TRANSLATE-926: ExtJS 6.2 update

TRANSLATE-972: translate5 does not check, if there are relevant files in the import zip

TRANSLATE-981: User inserts content copied from rich text wordprocessing tool

### Bugfixes

TRANSLATE-984: The editor converts single quotes to the corresponding HTML entity

TRANSLATE-997: Reset password works only once without reloading the user data

TRANSLATE-915: JS Error: response is undefined

## [2.5.35] - 2017-08-17

### Changed

TRANSLATE-957: XLF Import: Different tag numbering on tags swapped position from source to target

TRANSLATE-955: XLF Import: Whitespace import in XLF documents

### Bugfixes

TRANSLATE-937: translate untranslated GUI elements

TRANSLATE-925: XLF Import: support xliff 1.2 as import format - several smaller fixes

TRANSLATE-971: XLF Import: Importing an XLF with comments produces an error

TRANSLATE-968: XLF Import: Ignore CDATA blocks in the Import XMLParser

TRANSLATE-967: SDLXLIFF segment attributes could not be parsed

MITTAGQI-42: Changes.xliff filename was invalid under windows and minor issue in error logging

TRANSLATE-960: Trying to delete a task user assoc entry produces an exception

## [2.5.34] - 2017-08-07

### Added

TRANSLATE-925: support xliff 1.2 as import format

### Changed

T5DEV-172: (Ext 6.2 update prework) Quicktip manager instances have problems if configured targets does not exist anymore

T5DEV-171: (Ext 6.2 update prework) Get Controller instance getController works only with full classname

### Bugfixes

TRANSLATE-953: Direct Workers (like GUI TermTagging) are using the wrong worker state

## [2.5.33] - 2017-07-11

### Changed

TRANSLATE-628: Log changed terminology in changes xliff

### Bugfixes

TRANSLATE-921: Saving ChangeAlikes reaches PHP max_input_vars limit with a very high repetition count

TRANSLATE-922: Segment timestamp updates only on the first save of a segment

## [2.5.32] - 2017-07-04

### Changed

TRANSLATE-911: Workflow Notification mails could be too large for underlying mail system

TRANSLATE-906: translation bug: "Mehr Info" in EN

TRANSLATE-909: Editor window - change column title "Target text(zur Importzeit)"

TRANSLATE-894: Copy source to target – FIX

TRANSLATE-907: Rename QM-Subsegments to MQM in the GUI

TRANSLATE-818: internal tag replace id usage with data-origid and data-filename - additional migration script

TRANSLATE-895: Copy individual tags from source to target - ToolTip

TRANSLATE-885: fill non-editable target for translation tasks - compare targetHash to history

small fix for empty match rate tooltips showing "null"

## [2.5.31] - 2017-06-23

### Changed

TRANSLATE-882: Switch default match resource color from red to a nice green

### Bugfixes

TRANSLATE-845: Calling task export on task without segment view produces an error (with enabled SegmentStatistics Plugin)

TRANSLATE-904: json syntax error in match resource plugin

Multiple minor changes/fixes (code comment changes, missing tooltip) 

## [2.5.30] - 2017-06-13

### Added

TRANSLATE-885: fill non-editable target for translation tasks

TRANSLATE-894: Copy source to target

TRANSLATE-895: Copy individual tags from source to target

TRANSLATE-901: GUI task creation wizard

TRANSLATE-902: Pretranslation with Globalese Machine Translation

### Changed

TRANSLATE-296: Harmonize whitespace and unicode special chars protection throughout the import file formats

TRANSLATE-896: Restructure editor menu

## [2.5.27] - 2017-05-29

### Added

TRANSLATE-871: New Tooltip shows segment meta data over segmentNrInTask column

TRANSLATE-878: Enable GUI JS logger TheRootCause

TRANSLATE-877: Make Worker URL separately configurable

### Changed

TRANSLATE-823: ignore sdlxliff bookmarks for relais import check

TRANSLATE-870: Enable MatchRate and Relays column per default in ergonomic mode

TRANSLATE-857: change target column names in the segment grid

TRANSLATE-880: XLF import: Copy source to target, if target is empty or does not exist

TRANSLATE-897: changes.xliff generation: alt-trans shorttext for target columns must be changed

### Bugfixes

TRANSLATE-875: Width of relays column is too small

TRANSLATE-891: OpenTM2 answer with Unicode characters and internal tags produces invalid HTML in answer

TRANSLATE-888: Mask tab character in source files with internal tag

TRANSLATE-879: SDLXliff and XLF import does not work with missing target tags

## [2.5.26] - 2017-04-24

### Added

TRANSLATE-871: New Tooltip should show segment meta data over segmentNrInTask column

### Changed

TRANSLATE-823: ignore sdlxliff bookmarks for relais import check

TRANSLATE-870: Enable MatchRate and Relais column per default in ergonomic mode

### Bugfixes

TRANSLATE-875: Width of relais column is too small

## [2.5.25] - 2017-04-06

### Changed

MITTAGQI-36: Add new license plug-in exception

## [2.5.24] - 2017-04-05

### Bugfixes

TRANSLATE-850: Task can not be closed when user was logged out in the meantime

## [2.5.23] - 2017-04-05

### Changed

Included OpenTM2 Community Edition updated to Version 1.3.4.2

## [2.5.22] - 2017-04-05

### Changed

TRANSLATE-854: Change font-size in ergo-mode to 13pt

### Bugfixes

TRANSLATE-849: wrong usage of findRecord in frontend leads to wired errors

TRANSLATE-853: installer fails with "-" in database name

## [2.5.14] - 2017-03-30

### Added

TRANSLATE-807: Change default editor mode to ergonomic mode

TRANSLATE-796: Enhance concordance search

TRANSLATE-826: Show only a maximum of MessageBox messages

TRANSLATE-821: Switch translate5 to Triton theme

TRANSLATE-502: OpenTM2-Integration into MatchResource Plug-In

### Changed

TRANSLATE-820: Generalization of Languages model

TRANSLATE-818: internal tag replace id usage with data-origid and data-filename

MITTAGQI-30: Update license informations

### Bugfixes

TRANSLATE-833: Add application locale to the configurable Help URL

TRANSLATE-839: Ensure right character set of DB import with importer

TRANSLATE-844: roweditor minimizes its height

TRANSLATE-758: DbUpdater under Windows can not deal with DB Passwords with special characters

TRANSLATE-805: show match type tooltip also in row editor

## [2.5.9] - 2017-01-23

### Bugfixes

fixing an installer issue with already existing tables while installation

TRANSLATE-783: Indentation of fields

## [2.5.7] - 2017-01-19

### Bugfixes

TRANSLATE-767: Changealike Window title was always in german

TRANSLATE-787: Translate5 editor does not start anymore - on all installed instances

TRANSLATE-782: Change text in task creation pop-up

TRANSLATE-781: different white space inside of internal tags leads to failures in relais import

TRANSLATE-780: id column of LEK_browser_log must not be NULL

TRANSLATE-768: Db Updater complains about Zf_worker_dependencies is missing

## [2.5.6] - 2016-11-04

### Changed

Content changes in the pages surround the editor

### Bugfixes

TRANSLATE-758: DbUpdater under Windows can not deal with DB Passwords with special characters

TRANSLATE-761: Task must be reloaded when switching from state import to open

## [2.5.2] - 2016-10-26

### Added

TRANSLATE-726: New Column "type" in ChangeLog Plugin

TRANSLATE-743: Implement filters in change-log grid

### Changed

improved worker exception logging

TRANSLATE-759: Introduce config switch to set application language instead of browser recognition

TRANSLATE-751: Updater must check for invalid DB settings

TRANSLATE-612: User-Authentication via API - enable session deletion, login counter

TRANSLATE-644: enable editor-only usage in translate5 - enable direct task association

TRANSLATE-750: Make API auth default locale configurable

### Bugfixes

TRANSLATE-760: The source and target columns are missing sometimes after import for non PM users

TRANSNET-10: Login and passwd reset page must be also in english

TRANSLATE-684: Introduce match-type column - fixing tests

TRANSLATE-745: double tooltip on columns with icon in taskoverview

TRANSLATE-749: session->locale sollte an dieser Stelle bereits durch LoginController gesetzt sein

TRANSLATE-753: change-log-window is not translated on initial show

## [2.5.1] - 2016-09-27

### Added

TRANSLATE-637: Inform users about new features

TRANSLATE-137: Maintenance Mode

TRANSLATE-680: Automatic substituations of tags for repetitions

TRANSLATE-612: User-Authentication via API

TRANSLATE-664: Integrate separate help area in translate5 editor

TRANSLATE-684: Introduce match-type column

TRANSLATE-644: enable editor-only usage in translate5

TRANSLATE-718: Introduce a config switch to disable comment export (default is to enable export)

TRANSLATE-625: Switch Task-Import and -export to worker-architecture

TRANSLATE-621: Implement task status "error"

### Changed

TRANSLATE-646: search for "füll" is finding the attribute-value "full", that is contained in every internal tag

TRANSLATE-750: Make API auth default locale configurable

### Bugfixes

TRANSLATE-725: Filtering status column in task overview throws error

TRANSLATE-727: Filtering source language column in task overview throws an error

TRANSLATE-728: Missing column title for match resource column

several

TRANSLATE-715: Fix MQM short cut labels

TRANSLATE-749: session locale fix

## [2.4.16] - 2016-08-04

### Added

TRANSLATE-711: Check-Script for translate-683

enable application zip override on commandline

### Changed

TRANSLATE-710: change generated dates in changes.xliff to DateTime::ATOM format

TRANSLATE-705: Single click leads to opening of segment

TRANSLATE-712: remote sorting not working in task and user grid

TRANSLATE-713: JS Error when opening segments with terminology with unknown term status

### Changed - for supporters only

TRANSLATE-421: Display TM-Assoc in task-Overview panel & Task-Assoc in TM-Overview panel
             Only available for supporters of the crowdfunding until the crowdfunding is fully financed,
             see https://www.startnext.com/joined-os-translation-system

## [2.4.14] - 2016-07-27

### Added

TRANSLATE-707: Export comments to sdlxliff

TRANSLATE-684: adding a matchRateType column

translate5 Plugins: added support for translations, public files and php controllers

### Added - for supporters only

TRANSLATE-421: translate5 connects and uses results from third party TM (openTM2) and MT (Moses MT) resources
             Only available for supporters of the crowdfunding until the crowdfunding is fully financed,
             see https://www.startnext.com/joined-os-translation-system

### Changed

TRANSLATE-706: Check during relais import, if source of relais file is identical to source of to be translated file

TRANSLATE-689: Files containing empty segments cannot be imported in Transit

TRANSLATE-701: remove legacy content tag export code

TRANSLATE-700: Move regexInternalTags from config to class constant 

## [2.4.9] - 2016-06-02

### Changed

TRANSLATE-678: Diff Export is destroying entities in certain rare cases

TRANSLATE-670: Keyboard short cut collision under windows: 
              CTRL + ALT + DIGIT changed to ALT + S and then DIGIT
              CTRL + ALT + C changed ALT + C

TRANSLATE-631: ExtJS6 Update, fixes (related to keyboard shortcuts)

TRANSLATE-682: translate5 export contains closing div tags from termtagger: PORTAL-88

TRANSLATE-683: repetition editor changes the source, even if it is non-editable

TRANSLATE-686: Autostate calculation in ChangeAlike handling is wrong in some circumstances

## [2.4.8] - 2016-05-06

### Added

integrate crowdfunding success in frontpage

### Changed

TRANSLATE-631: ExtJS6 Update, fixes (IE-warnmessage, errors related to shortcut CTRL-G)

## [2.4.7] - 2016-04-25

### Added

TRANSLATE-679: Notify User about outdated browser

### Changed

TRANSLATE-631: ExtJS6 Update, fixes

TRANSLATE-668: Termtagger config GUI repaired

TRANSLATE-671: Improve filemap performance (memory peak on huge tasks)

## [2.4.6] - 2016-04-06

### Changed

TRANSLATE-631: ExtJs 6 Update, several fixes

## [2.4.5] - 2016-03-17

### Added

TRANSLATE-586: Allow user only to add MQM-tags, but not to edit the content

TRANSLATE-631: ExtJs 6 Update, including new features:
- decoupling segment editor from the grid
- segment editor can be moved vertically
- the opened segment stays open, regardless where the other segments are scrolled
- Navi Button to scroll back to the opened segment

TRANSLATE-598: Show count of filtered segments in GUI

### Changed

TRANSLATE-659: Multiple tags of the same type are producing DomQuery warnings

TRANSLATE-218: enable MQM for empty strings / missing content

TRANSLATE-578: Change MQM-syntax in exported CSV

TRANSLATE-622: Change order of the save and cancel button in the meta panel

TRANSLATE-654: Improve tag protection and regex-based protection in CSV files

TRANSLATE-653: Stop import, if TBX-file is given but does not contain entries for one of the selected languages

## [2.3.103] - 2016-02-04

### Added

TRANSLATE-576: Added Keyboard shortcuts for most common actions

TRANSLATE-216: Introduced a user specific segment watch-list

TRANSLATE-641: Revert segment to initial version

TRANSLATE-653: Stopping import, if given TBX-file does not contain entries for one of the selected languages

TRANSLATE-635: lock segments in translate5 that are locked in original bilingual system

TRANSLATE-640: make maxParallelProcesses for all other worker types configurable

TRANSLATE-627: Make configurable, if unfiltered statistic file is generated or not

TRANSLATE-620: add columns for number of chars and lines per file to statistics

### Changed

TRANSLATE-652: transNotDefined in XliffTermTagger-Responses leads to duplicate CSS-class definitions

TRANSLATE-655: Fixed sql-error in Installer on sql-import of new installation from the scratch

TRANSLATE-650: switch XliffTermTagger version checking to new version output

TRANSLATE-648: MQM-Shortcut-Hint does not show correct shortcuts

TRANSLATE-594: Fixed entity encode on import and decode on export of CSV files

TRANSLATE-624: don't copy icons in terminology portlet of editor

## [2.3.102] - 2015-12-09

### Added

TRANSLATE-614: JS-based serverside Log of Browser-Version of the user

TRANSLATE-619: Import statistics: configurable value for generating statistic tables for single language pairs

### Changed

TRANSLATE-611: Fixed Error-Message "Terme"

TRANSLATE-610: Enhance Error-Message on tag error in editor

TRANSLATE-615: Repetition editor sets wrong autostate for unchanged source match with different target content

TRANSLATE-609: Improve error message on receiving a termtagger error while loading TBX

TRANSLATE-608: Internal space tag is not reconverted in changes.xml

TRANSLATE-607: DB Deadlock on taskUserAssoc clean up

TRANSLATE-604: Termtagger errors when importing already imported taskGuid
improve striptermtags error output

TRANSLATE-623: Change segment grid column order

TRANSLATE-622: Change order of the save and cancel button in the meta panel

TRANSLATE-598: Show count of filtered segments in GUI

