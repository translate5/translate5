# Change Log

All notable changes to translate5 will be documented here.

For a reference to the issue keys see http://jira.translate5.net

Missing Versions are merged into in the next upper versions, so no extra section is needed.

All updates are (downwards) compatible! If not this is listed in the important release notes.










## [7.11.0] - 2024-10-04

### Important Notes:
#### [TRANSLATE-4198](https://jira.translate5.net/browse/TRANSLATE-4198)
We need to make TM Maintenance available for all deployments
See https://jira.translate5.net/browse/TRANSLATE-4198 for formatted instructions.

Needed steps:
- update PHP and t5memory (t5memory tag should be :latest) container, recreate
- docker compose pull php t5memory
- docker compose up --force-recreate -d php t5memory 
- activate plugin - go to the document-root of your translate5 installation dir in your php-docker-container and call there
t5 plugin:enable TMMaintenance
- the needed access roles are added automatically to all sysadmin and admin users
- admins and PM users can set the needed roles to each user in the UIs user administration panel
- log in with such user and you are able to use TM Maintenance with that user.

Please note, that before being able to use TM maintenance for a TM, the TM needs to be reorganized because of changes in its data structures. translate5 will do that automatically for a TM as soon as you start to try to use it in TM maintenance, yet with big TMs that will take some time and the UI might run into timeouts then.
You can also migrate all TMs first on the CMD to the new format by using the command line interface. Go to the document-root of your translate5 installation dir in your php-docker-container and call there:
t5 t5memory:reorganize

The command has the following parameters

--tmName=TMNAME If no UUID was given this will filter the list of all TMs if provided
--batchSize=BATCHSIZE Number of memories to reorganize at once. Works only if no UUID and tmName was given
--startFromId=STARTFROMID

The IDs of the TMs you see when showing the “id” column in the language resource management in the UI.
 


### Changed
**[TRANSLATE-4206](https://jira.translate5.net/browse/TRANSLATE-4206): TM Maintenance - Add user name and timestamp to error modal in TMMaintenance** <br>
Added debug info to the error modal in TM Maintenance

**[TRANSLATE-4201](https://jira.translate5.net/browse/TRANSLATE-4201): InstantTranslate - InstantTranslate: If multi-segment: Highlight different resources in result** <br>
Multi-segment mode: best results from different language resources are highlighted and merged into single result block

**[TRANSLATE-4198](https://jira.translate5.net/browse/TRANSLATE-4198): t5memory - Instruction how to enable TM Maintenance** <br>
TM Maintenance is now enabled by default


### Bugfixes
**[TRANSLATE-4219](https://jira.translate5.net/browse/TRANSLATE-4219): LanguageResources - Unable to add or remove client from default read/write in language resources** <br>
Fix customer assignment meta data update

**[TRANSLATE-4216](https://jira.translate5.net/browse/TRANSLATE-4216): Import/Export - Across hotfolder: bconf causes import error** <br>
Fix bconf passing between plugins

**[TRANSLATE-4205](https://jira.translate5.net/browse/TRANSLATE-4205): Main back-end mechanisms (Worker, Logging, etc.) - Delayed worker leads to slow import for small tasks** <br>
Smaller tasks were running to long due delayed termtagger workers, this is fixed.

**[TRANSLATE-4199](https://jira.translate5.net/browse/TRANSLATE-4199): file format settings - Import of BCONFs with corrupt Extension-mapping is possible (and maybe editing also)** <br>
FIX: It was possible to import a BCONF with faulty extension-mapping (only "." as extension)

**[TRANSLATE-4194](https://jira.translate5.net/browse/TRANSLATE-4194): Configuration - make page number in system log readable** <br>
FIXED: page number was clipped if due to insufficient input field width within paging toolbar

**[TRANSLATE-4193](https://jira.translate5.net/browse/TRANSLATE-4193): OpenTM2 integration - T5Memory import memory split does not work** <br>
Fix large TMX files import into t5memory

**[TRANSLATE-4189](https://jira.translate5.net/browse/TRANSLATE-4189): Content Protection - html escaped in UI** <br>
Updated addQTip in TaskGrid.js and contentRecognition's GridController to retain linebreak tags

**[TRANSLATE-4163](https://jira.translate5.net/browse/TRANSLATE-4163): Auto-QA - Terminology panel does not show the correct terminology when using "CTRL + ENTER" to save** <br>
FIX: wrong terminology shown when segment saved with "CTRL + ENTER"

**[TRANSLATE-4056](https://jira.translate5.net/browse/TRANSLATE-4056): TermTagger integration - Delayed Workers: Improve Termtagging & Spellchecking to not stop when Containers are busy** <br>
7.10.0: Enhancement: When a single Segment have a TermTagger error in the Import, a warning is reported to the task-events instead of an exception rendering the task erroneous
7.11.0: Fix performance problem with smaller task


## [7.10.0] - 2024-09-19

### Important Notes:
 


### Changed
**[TRANSLATE-4173](https://jira.translate5.net/browse/TRANSLATE-4173): InstantTranslate - InstantTranslate: Move automatically triggered "manual translate" button on resource level** <br>
manual translation button is now shown for each resource, if it's slow

**[TRANSLATE-4149](https://jira.translate5.net/browse/TRANSLATE-4149): SpellCheck (LanguageTool integration), TermTagger integration - Segment processing may processes segments simultaneously** <br>
FIX: Auto-QA processing may had bugs processing segments simultaneously and overwriting results

**[TRANSLATE-4039](https://jira.translate5.net/browse/TRANSLATE-4039): InstantTranslate - Request assigned languageResources in InstantTranslate in the Frontend** <br>
IMPROVEMENT: InstantTranslate requests the attached Resources individually from the Frontend to bring request-times down


### Bugfixes
**[TRANSLATE-4192](https://jira.translate5.net/browse/TRANSLATE-4192): Workflows - job status "autoclose" preselected when assigning users** <br>
Fix default pre-selected job state.

**[TRANSLATE-4180](https://jira.translate5.net/browse/TRANSLATE-4180): Installation & Update - Prevent default plugin activation for updates** <br>
Since 7.8.0 default plugins were activated by default. This was also done on updates, so by purposes deactivated default plugins were reactivated automatically. This is fixed, so that default plug-ins are only activated on installations.

**[TRANSLATE-4179](https://jira.translate5.net/browse/TRANSLATE-4179): Editor general - Fix html escaping in concordance search** <br>
Remove unneeded html escaping in concordance search grid result

**[TRANSLATE-4178](https://jira.translate5.net/browse/TRANSLATE-4178): Editor general - comments to a segment starting with < will be empty** <br>
Fix saving segment comments containing special characters

**[TRANSLATE-4174](https://jira.translate5.net/browse/TRANSLATE-4174): Editor general - Language resource name wrongly escaped in Match rate grid** <br>
Fix Language resource name escaping in Match rate grid

**[TRANSLATE-4056](https://jira.translate5.net/browse/TRANSLATE-4056): TermTagger integration - Delayed Workers: Improve Termtagging & Spellchecking to not stop when Containers are busy** <br>
Enhancement: When a single Segment have a TermTagger error in the Import, a warning is reported to the task-events instead of an exception rendering the task erroneous


## [7.9.2] - 2024-09-05

### Important Notes:
 


### Changed
**[TRANSLATE-4168](https://jira.translate5.net/browse/TRANSLATE-4168): t5memory - Enable stripping framing tags by default** <br>
Config value runtimeOptions.LanguageResources.t5memory.stripFramingTagsEnabled is now 1 by default

**[TRANSLATE-4167](https://jira.translate5.net/browse/TRANSLATE-4167): t5memory, TM Maintenance - TMMaintenance search fails for big memories** <br>
Fix TM Maintenance search for big memories

**[TRANSLATE-4164](https://jira.translate5.net/browse/TRANSLATE-4164): LanguageResources - DeepL: Improve tag-repair to handle new tag-problems in DeepL** <br>
FIX: DeepL at times "clusters" all sent internal tags in the front of the segment. In these cases the automatic tag-repair now also kicks in


### Bugfixes
**[TRANSLATE-4169](https://jira.translate5.net/browse/TRANSLATE-4169): t5memory - Match results in Editor rendered in escaped format** <br>
Remove segment escaping in FE


## [7.9.1] - 2024-09-04

### Important Notes:
 


### Bugfixes
**[TRANSLATE-4162](https://jira.translate5.net/browse/TRANSLATE-4162): Editor general - Error in html escape in match grid template** <br>
Fix for UI error with wrong html escape.

**[TRANSLATE-4160](https://jira.translate5.net/browse/TRANSLATE-4160): Client management, OpenId Connect - Client pm sub roles not available in openid roles configuration** <br>
FIX: Make clientPM subroles accessible in the OpenID client configuration


## [7.9.0] - 2024-08-30

### Important Notes:
#### [TRANSLATE-4109](https://jira.translate5.net/browse/TRANSLATE-4109)
Update Visual docker containers to get that change
 


### Added
**[TRANSLATE-3938](https://jira.translate5.net/browse/TRANSLATE-3938): Import/Export - Export segments as html** <br>
Introduce HTML Task export feature


### Changed
**[TRANSLATE-4109](https://jira.translate5.net/browse/TRANSLATE-4109): VisualReview / VisualTranslation - Add PDF Version check to pdfconverter container** <br>
IMPROVEMENT Visual: warn, when imported PDF is of X-4 subtype (which frequently create problems when converting)

**[TRANSLATE-3960](https://jira.translate5.net/browse/TRANSLATE-3960): Editor general - Test PXSS in all input fields of the application** <br>
Security: fixed remaining PXSS issues by adding frontend-sanitization

**[TRANSLATE-3518](https://jira.translate5.net/browse/TRANSLATE-3518): LanguageResources - Infrastructure for using "translate5 language resources" as training resources for MT** <br>
Cross Language Resource synchronisation mechanism and abstraction layer introduced into application.
From now on we have mechanic to connect different Language Resource types (like t5memory, Term Collection, etc) for data synchronisation if it is possible


### Bugfixes
**[TRANSLATE-4161](https://jira.translate5.net/browse/TRANSLATE-4161): TM Maintenance - Segments batch deletion no longer works** <br>
Fix segments batch deletion in TM Maintenance

**[TRANSLATE-4159](https://jira.translate5.net/browse/TRANSLATE-4159): t5memory - Html entities are not escaped in TMX export** <br>
Escape tag like enties for t5memory

**[TRANSLATE-4157](https://jira.translate5.net/browse/TRANSLATE-4157): TermPortal, TM Maintenance - Uncaught TypeError: Ext.scrollbar._size is null** <br>
FIXED: problem with production builds of TermPortal and TMMaintenance

**[TRANSLATE-4152](https://jira.translate5.net/browse/TRANSLATE-4152): LanguageResources - reenable usage of sub-languages in as source of lang synch with major languages as target of lang synch** <br>
Changed language resource synchronisation makes it possible to connect source language resource with a sub-language to a target language resource with a major language

**[TRANSLATE-3452](https://jira.translate5.net/browse/TRANSLATE-3452): Auto-QA - Automatic tag correction completes to many tags on Excel re-import** <br>
Excel Re-import: taglike placeholders are now escaped to prevent errors in the UI

**[TRANSLATE-3079](https://jira.translate5.net/browse/TRANSLATE-3079): Editor general, Security Related - Self-XSS is still possible** <br>
Security: fixed PXSS issuesin grids in the frontend


## [7.8.2] - 2024-08-23

### Important Notes:
 


### Added
**[TRANSLATE-4132](https://jira.translate5.net/browse/TRANSLATE-4132): Main back-end mechanisms (Worker, Logging, etc.) - Auto-close jobs by task deadline** <br>
 translate5 - 7.8.0: New date field project deadline date available for task.
 translate5 - 7.8.2: Auto-close jobs can be turned on and off by config. By default it is off.


## [7.8.1] - 2024-08-22

### Important Notes:
#### [TRANSLATE-4151](https://jira.translate5.net/browse/TRANSLATE-4151)
IMPORTANT: Deprecated target languages "en" and "pt" in DeepL language-resources were converted to "en-GB" and "pt-PT". In case other variants are wanted, please create a new resource or contact our support to change it in the database.

#### [TRANSLATE-4109](https://jira.translate5.net/browse/TRANSLATE-4109)
Update Visual docker containers to get that change
 


### Changed
**[TRANSLATE-4109](https://jira.translate5.net/browse/TRANSLATE-4109): VisualReview / VisualTranslation - Add PDF Version check to pdfconverter container** <br>
IMPROVEMENT Visual: warn, when imported PDF is of X-4 subtype (which frequently create problems when converting)


### Bugfixes
**[TRANSLATE-4158](https://jira.translate5.net/browse/TRANSLATE-4158): LanguageResources - Add explanation text in empty language resource sync window** <br>
Add explanation text for empty language resource sync window

**[TRANSLATE-4154](https://jira.translate5.net/browse/TRANSLATE-4154): InstantTranslate - Instant translate produces error for user with no assigned customers** <br>
FIX: instant translate crashes when accessed by user with no assigned customers

**[TRANSLATE-4153](https://jira.translate5.net/browse/TRANSLATE-4153): Editor general - Bookmark filter in editor does not work** <br>
FIXED: bookmark filter is now working again

**[TRANSLATE-4151](https://jira.translate5.net/browse/TRANSLATE-4151): LanguageResources - Use EN-GB instead of simple EN and PT as target for DeepL resources** <br>
For DeepL language resources target languages "en" and "pt" changed to "en-GB" and "pt-PT" respectively.

**[TRANSLATE-4118](https://jira.translate5.net/browse/TRANSLATE-4118): TermPortal - Search results scrolling problem in Firefox** <br>
translate5 - 7.8.0: FIXED: scrollbar not available for search results grid in Firefox
translate5 - 7.8.1: Fix for UI error in instant-translate


## [7.8.0] - 2024-08-20

### Important Notes:
#### [TRANSLATE-2270](https://jira.translate5.net/browse/TRANSLATE-2270)
To make plugin work properly it is needed to add an alias in apache config inside the PHP container:
edit /etc/apache2/sites-enabled/translate5.conf and add the following line
Alias /editor/plugins/resources/TMMaintenance/ext /var/www/translate5/application/modules/editor/Plugins/TMMaintenance/public/resources/ext
 


### Added
**[TRANSLATE-4132](https://jira.translate5.net/browse/TRANSLATE-4132): Main back-end mechanisms (Worker, Logging, etc.) - Auto-close jobs by task deadline** <br>
New date field project deadline date available for task.

**[TRANSLATE-3898](https://jira.translate5.net/browse/TRANSLATE-3898): LanguageResources - Change tmx import to be able to use html multipart fileupload** <br>
Change TMX import to be able to use multipart file-upload

**[TRANSLATE-2270](https://jira.translate5.net/browse/TRANSLATE-2270): LanguageResources - Translation Memory Maintenance** <br>
translate5 - 7.7.0 : New plugin TMMaintenance for managing segments in t5memory
translate5 - 7.8.0 : Improved UI error handling and display


### Changed
**[TRANSLATE-3518](https://jira.translate5.net/browse/TRANSLATE-3518): LanguageResources - Infrastructure for using "translate5 language resources" as training resources for MT** <br>
Cross Language Resource synchronisation mechanism and abstraction layer introduced into application.
From now on we have mechanic to connect different Language Resource types (like t5memory, Term Collection, etc) for data synchronisation if it is possible

**[TRANSLATE-4137](https://jira.translate5.net/browse/TRANSLATE-4137): t5memory, Translate5 CLI - Improve t5memory reorganize command** <br>
Added capability to process language resources in batches to t5memory:reorganize command

**[TRANSLATE-4135](https://jira.translate5.net/browse/TRANSLATE-4135): TM Maintenance - TMMaintenance: segments loading usability** <br>
Added 'Loading...'-row to the bottom of the results grid and amended grid title so loading progress is shown

**[TRANSLATE-4123](https://jira.translate5.net/browse/TRANSLATE-4123): ConnectWorldserver - Plugin ConnectWorldserver: add reviewer to entry in task-history** <br>
Sample:
assigned person(s):
- User1 MittagQI [User1@translate5.net] (reviewing: finished)
- User2 MittagQI [User2@translate5.net] (reviewing: finished)

**[TRANSLATE-4094](https://jira.translate5.net/browse/TRANSLATE-4094): VisualReview / VisualTranslation - Use VTT files for Video Imports** <br>
Visual: Enable the import of .vtt-files as workfile for a video-based visual

**[TRANSLATE-4062](https://jira.translate5.net/browse/TRANSLATE-4062): Workflows - Add archive config to use import date instead task modified date** <br>
translate5 - 7.7.0: Extend task archiving functionality to filter for created timestamp also, instead only modified timestamp. Configurable in Workflow configuration.
translate5 - 7.8.0: add ftps support

**[TRANSLATE-4057](https://jira.translate5.net/browse/TRANSLATE-4057): Auto-QA - Wrong error count in autoQA after collapsing and re-expanding autoQA panel** <br>
Qualities filter type is now preserved on collapse/expand of filter panel

**[TRANSLATE-4022](https://jira.translate5.net/browse/TRANSLATE-4022): Auto-QA, SNC - SNC: add new error previously unknown to translate5** <br>
"(Possibly erroneous) separator from SRC found unchanged in TRG" reported by SNC-lib is now added to the list of known by Translate5 and is now counted as AutoQA-quality under Numbers category group

**[TRANSLATE-3936](https://jira.translate5.net/browse/TRANSLATE-3936): Editor general - Ensure that default plug-ins without config produce no errors** <br>
Ensure that plug-ins enabled by default are not producing errors when no configuration is given

**[TRANSLATE-3883](https://jira.translate5.net/browse/TRANSLATE-3883): Import/Export - Make TMX export run as stream** <br>
Fix issues with export of large TMs


### Bugfixes
**[TRANSLATE-4150](https://jira.translate5.net/browse/TRANSLATE-4150): Installation & Update - General error: 1270 Illegal mix of collations** <br>
Fixing an older DB change file not compatible to latest DB system

**[TRANSLATE-4147](https://jira.translate5.net/browse/TRANSLATE-4147): Content Protection - Content protection: Tag alike render, meta-info in tag** <br>
Fix render of tag like protected entities
Store meta info into tag itself for state independency.

**[TRANSLATE-4146](https://jira.translate5.net/browse/TRANSLATE-4146): t5memory, TM Maintenance - Special characters are not treated properly in TM Maintenance** <br>
Fixed special characters processing in search fields and in editor

**[TRANSLATE-4145](https://jira.translate5.net/browse/TRANSLATE-4145): InstantTranslate - Enable aborting InstantTranslate Requests independently from maxRequestDuration** <br>
Min time a ranslation-request can be aboted to trigger the next one is now independant from maxRequestDuration

**[TRANSLATE-4143](https://jira.translate5.net/browse/TRANSLATE-4143): Auto-QA - AutoQA Filter is not correctly updated when segment-interdependent qualities change** <br>
Fix AutoQA filter-panel is not updated when certain segment qualities change

**[TRANSLATE-4140](https://jira.translate5.net/browse/TRANSLATE-4140): t5memory, TM Maintenance - Reorganize is triggered when memory not loaded to RAM** <br>
Fixed triggering reorganization on the memory which is not loaded into RAM yet

**[TRANSLATE-4139](https://jira.translate5.net/browse/TRANSLATE-4139): t5memory - wrong timestamp in matches saved with option "time of segment saving"** <br>
Fix segment timestamp when reimporting task to TM and "Time of segment saving" is chosen as an option

**[TRANSLATE-4131](https://jira.translate5.net/browse/TRANSLATE-4131): LanguageResources - Language resource data is not updated when edit form is opened** <br>
Fix bug when language resource becomes not editable

**[TRANSLATE-4127](https://jira.translate5.net/browse/TRANSLATE-4127): Auto-QA - RootCause: this.getView() is null** <br>
translate - 7.8.0 : added logging for further investigation of a problem with AutoQA filters

**[TRANSLATE-4122](https://jira.translate5.net/browse/TRANSLATE-4122): Import/Export - Import wizard default assignment: source language not selectable** <br>
Fix for a problem where target language is not selectable in the user assignment panel in the import wizard.

**[TRANSLATE-4120](https://jira.translate5.net/browse/TRANSLATE-4120): Content Protection - improve content protection rules float generic comma and float generic dot** <br>
Content protection for floats and integers will try to protect + and - sign before number as default behaviour.

**[TRANSLATE-4118](https://jira.translate5.net/browse/TRANSLATE-4118): TermPortal - Search results scrolling problem in Firefox** <br>
FIXED: scrollbar not available for search results grid in Firefox

**[TRANSLATE-4117](https://jira.translate5.net/browse/TRANSLATE-4117): MatchAnalysis & Pretranslation - Race condition in Segment Processing functionality** <br>
Fix race condition in segment processing

**[TRANSLATE-4083](https://jira.translate5.net/browse/TRANSLATE-4083): Content Protection - Content Protection: Rule not working** <br>
Fixed tag attribute parsing in XmlParser.

**[TRANSLATE-4082](https://jira.translate5.net/browse/TRANSLATE-4082): Content Protection - Content Protection: duplicate key error and priority value not taken over** <br>
Fix issue with Content protection rule creation.
Change the way validation error delivered to end user.

**[TRANSLATE-4063](https://jira.translate5.net/browse/TRANSLATE-4063): Editor general - UI error in pricing pre-set grid** <br>
FIXED: error popping due to incorrect tooltip render for checkbox-column in pricing preset grid

**[TRANSLATE-4051](https://jira.translate5.net/browse/TRANSLATE-4051): InstantTranslate - Instant translate help window does not remember close state** <br>
Fix for a problem where the visibility state of help button in Instant-Translate  is not remembered.

**[TRANSLATE-4050](https://jira.translate5.net/browse/TRANSLATE-4050): TermTagger integration - correct target term not recognized** <br>
FIXED: several problems and logic gaps with terminology recognition

**[TRANSLATE-4019](https://jira.translate5.net/browse/TRANSLATE-4019): Editor general - Error in UI filters** <br>
Fix error when using string filters with special characters in grids.

**[TRANSLATE-3946](https://jira.translate5.net/browse/TRANSLATE-3946): Repetition editor - Repetition's first occurrence should keep its initial match rate** <br>
First occurrence of repetition is now kept untouched, while others are set 102 or higher

**[TRANSLATE-3899](https://jira.translate5.net/browse/TRANSLATE-3899): LanguageResources - Overwritten DeepL API key produces error with related Term Collection** <br>
 DeepL API key provided on customer lvl now applied as for DeepL Language Resource as to Term Collection related to it

**[TRANSLATE-3602](https://jira.translate5.net/browse/TRANSLATE-3602): Globalese integration - CSRF-protection causes Globalese plug-in to fail** <br>
Fixed Plugin auth handling


## [7.7.0] - 2024-07-31

### Important Notes:
#### [TRANSLATE-4029](https://jira.translate5.net/browse/TRANSLATE-4029)
added theme-name as CSS-class inside editor body to be more flexible in client-specific skins.

#### [TRANSLATE-2270](https://jira.translate5.net/browse/TRANSLATE-2270)
To make plugin work properly it is needed to add an alias in apache config inside the PHP container:
edit /etc/apache2/sites-enabled/translate5.conf and add the following line
Alias /editor/plugins/resources/TMMaintenance/ext /var/www/translate5/application/modules/editor/Plugins/TMMaintenance/public/resources/ext
 


### Added
**[TRANSLATE-2270](https://jira.translate5.net/browse/TRANSLATE-2270): LanguageResources - Translation Memory Maintenance** <br>
New plugin TMMaintenance for managing segments in t5memory


### Changed
**[TRANSLATE-4092](https://jira.translate5.net/browse/TRANSLATE-4092): Translate5 CLI - CLI tool for testing SFTP and task archiving config** <br>
Implement filesystem:external:check and task:archive commands for testing and manual usage of external file systems and the task archiving stuff.

**[TRANSLATE-4079](https://jira.translate5.net/browse/TRANSLATE-4079): LanguageResources - Only one request per concordance search** <br>
TM-records are now loaded one-by-one until 20 loaded or nothing left

**[TRANSLATE-4069](https://jira.translate5.net/browse/TRANSLATE-4069): t5memory - Add comparing sent and received data during update request to t5memory** <br>
translate5 - 7.6.6: When updating the segment it is now checked if the received data equals what we expect
translate5 - 7.7.0: Disable t5memory data check because of to many logs

**[TRANSLATE-4065](https://jira.translate5.net/browse/TRANSLATE-4065): MatchAnalysis & Pretranslation - Use empty TM for internal fuzzy** <br>
translate5 - 7.6.6: Use empty TM to save internal fuzzy results instead cloning the current one
translate5 - 7.7.0: Improve logging for removed memory.

**[TRANSLATE-4062](https://jira.translate5.net/browse/TRANSLATE-4062): Workflows - Add archive config to use import date instead task modified date** <br>
Extend task archiving functionality to filter for created timestamp also, instead only modified timestamp. Configurable in Workflow configuration.

**[TRANSLATE-4011](https://jira.translate5.net/browse/TRANSLATE-4011): Export - Plugin ConnectWorldserver: no state error on task export for re-transfer to Worldserver** <br>
task is not set to state error on export


### Bugfixes
**[TRANSLATE-4115](https://jira.translate5.net/browse/TRANSLATE-4115): InstantTranslate - InstantTranslate: switch to manual-mode only if several requests took too long** <br>
FIX: InstantTranslate now switches to manual-mode (not "instant" anymore) only when several requests in a row took longer than the configured threshold




**[TRANSLATE-4113](https://jira.translate5.net/browse/TRANSLATE-4113): Editor general - Improve Logging of Invalid Markup for Sanitization** <br>
FIX: Improved logging of invalid markup sent from segment editing - not leading to security error anymore

**[TRANSLATE-4097](https://jira.translate5.net/browse/TRANSLATE-4097): Editor general - RootCause: response.getAllResponseHeaders is not a function** <br>
FIXED: fixed problem popping when maintenance mode is going to be enabled

**[TRANSLATE-4095](https://jira.translate5.net/browse/TRANSLATE-4095): Main back-end mechanisms (Worker, Logging, etc.) - Log cron job calls and add system check** <br>
Add log entries for each cron job call and a check to the system check if crons are triggered or not.

**[TRANSLATE-4088](https://jira.translate5.net/browse/TRANSLATE-4088): Configuration - In-Context fonts - search by task name throws error** <br>
FIX: searching in In-Context fonts may lead to exception

**[TRANSLATE-4086](https://jira.translate5.net/browse/TRANSLATE-4086): OpenId Connect - OpenID connect: wrong error handling with empty user info** <br>
Error handling fix in OpenID connect

**[TRANSLATE-4084](https://jira.translate5.net/browse/TRANSLATE-4084): Editor general - Change level of branding config** <br>
Branding config can be adjusted via the UI.

**[TRANSLATE-4080](https://jira.translate5.net/browse/TRANSLATE-4080): Okapi integration - FIX: Okapi fails exporting custom subfilters** <br>
FIX: OKAPI failed to export Files processed with a Filter using a customized Subfilter. This is only a temporary fix until the issue is solved within OKAPI

**[TRANSLATE-4074](https://jira.translate5.net/browse/TRANSLATE-4074): VisualReview / VisualTranslation - Visual does not reflect Pivot language in case target segments are empty** <br>
FIX: Visual did not show pivot language in case the target segments were empty

**[TRANSLATE-4066](https://jira.translate5.net/browse/TRANSLATE-4066): t5memory - Change save2disk behavior when reimporting task to t5memory** <br>
TM is now flushed to disk only when reimport is finished

**[TRANSLATE-4048](https://jira.translate5.net/browse/TRANSLATE-4048): LanguageResources - It is possible to create language resource for down server** <br>
It is now not possible to create a Language resource when the corresponding server is unreachable

**[TRANSLATE-4029](https://jira.translate5.net/browse/TRANSLATE-4029): Editor general - Customer specific theme overwrite is not working** <br>
Theme name as CSS class in body tag.

**[TRANSLATE-4024](https://jira.translate5.net/browse/TRANSLATE-4024): Configuration - Missing config-type** <br>
If the type of a certain config can not be detected, "string" will be set as default.

**[TRANSLATE-4023](https://jira.translate5.net/browse/TRANSLATE-4023): Auto-QA - AutoQA portlet should dissappear with no active checks** <br>
Editor's AutoQA leftside portlet is now hidden if no autoQA enabled for the task

**[TRANSLATE-4020](https://jira.translate5.net/browse/TRANSLATE-4020): VisualReview / VisualTranslation - PDF converter fails to cleanup JOB and thus does not respond with a proper log** <br>
FIX visual: pdf converter did not write a proper log & failed to clean up the workfiles in the converter container


## [7.6.6] - 2024-07-11

### Important Notes:
#### [TRANSLATE-4053](https://jira.translate5.net/browse/TRANSLATE-4053)
Strict escaping of all XML-based Imports is now the default. This also means, that exports are strictly escaped!
 


### Added
**[TRANSLATE-3850](https://jira.translate5.net/browse/TRANSLATE-3850): OpenId Connect - SSO via OpenID: Define if IDP should be able to remove rights** <br>
translate5 - 7.6.6: Customer will be updated or not based on this config
translate5 - 7.6.0: New client flag added for OpenId configured IDP. It can enable or disable updating of user roles, gender and locale from the IDP user claims.

**[TRANSLATE-3494](https://jira.translate5.net/browse/TRANSLATE-3494): TermPortal - Check for duplicates in same language when saving new term** <br>
Confirmation prompt is now shown on attempt to add a target term (via text selection within opened segment editor) that already exists in the destination TermCollection


### Changed
**[TRANSLATE-4070](https://jira.translate5.net/browse/TRANSLATE-4070): ConnectWorldserver - Plugin Connect WorldServer: disable TM-Update on re-import** <br>
Worldserver TM will not be updated on tasks re-import.

**[TRANSLATE-4069](https://jira.translate5.net/browse/TRANSLATE-4069): t5memory - Add comparing sent and received data during update request to t5memory** <br>
When updating the segment it is now checked if the received data equals what we expect

**[TRANSLATE-4065](https://jira.translate5.net/browse/TRANSLATE-4065): MatchAnalysis & Pretranslation - Use empty TM for internal fuzzy** <br>
Use empty TM to save internal fuzzy results instead cloning the current one


**[TRANSLATE-3975](https://jira.translate5.net/browse/TRANSLATE-3975): t5memory - Improve concordance search tags recognition** <br>
Tags recognition in concordance search panel changed to reflect actual tags ordering


### Bugfixes
**[TRANSLATE-4059](https://jira.translate5.net/browse/TRANSLATE-4059): Editor general - Remove character duplicates from special characters** <br>
Fixes duplicate buttons in special character list.

**[TRANSLATE-4055](https://jira.translate5.net/browse/TRANSLATE-4055): LanguageResources - OpenAI Plugin: Exception not reported to the Frontend** <br>
FIX: Errors in TMs may not be reported to the Frontend when used within OpenAI training

**[TRANSLATE-4053](https://jira.translate5.net/browse/TRANSLATE-4053): Import/Export - Switch to strict escaping for XML formats** <br>
Switch to strict escaping for all XML-based import formats (XLF, XLIFF, SDLXLIFF). This can be turned off by configuration if neccessary. Strict escaping means, that ">" generally is escaped in any textual content.

**[TRANSLATE-4047](https://jira.translate5.net/browse/TRANSLATE-4047): Editor general - Stored SegmentGrid sort on sourceEdit column leads to errors in task without editable source** <br>
Fix problem where filtering and sorting with invalid column names leads to UI error.

**[TRANSLATE-4044](https://jira.translate5.net/browse/TRANSLATE-4044): Import/Export - typo in error message for file upload** <br>
FIXED: small typo in VisualReview file upload error message

**[TRANSLATE-4034](https://jira.translate5.net/browse/TRANSLATE-4034): VisualReview / VisualTranslation - Improve visual symlink creation for very rare cases of parallel access** <br>
Suppress error-msg for visual symlink creation in the very rare case of paralell access

**[TRANSLATE-3997](https://jira.translate5.net/browse/TRANSLATE-3997): file format settings - segmentation improvements in default srx** <br>
FIX: File Format Settings: Rule to break after a Colon followed by a Uppercase word worked only in German in the translate5 default SRX

**[TRANSLATE-3991](https://jira.translate5.net/browse/TRANSLATE-3991): Task Management - FIX Table Archiever, FIX worker-trigger "Process" for Tests and reduce warnings** <br>
FIX: Table Archiever may ran into errors when plugins not installed/active

**[TRANSLATE-3720](https://jira.translate5.net/browse/TRANSLATE-3720): TermPortal, usability termportal - Enhance termportal attribute display usability** <br>
Improve term portal UI for attribute

**[TRANSLATE-2979](https://jira.translate5.net/browse/TRANSLATE-2979): LanguageResources - Concordance search highlighting may destroy rendered tags.** <br>
FIX When using the concordance search the content of tags can also be searched - not leading to defect tags in the rendered output anymore.


## [7.6.5] - 2024-07-02

### Important Notes:
 


### Added
**[TRANSLATE-3593](https://jira.translate5.net/browse/TRANSLATE-3593): Auto-QA, TermTagger integration - Split 'Terminology > Not found in target' AutoQA-category into 4 categories** <br>
translate5 - 7.4.0: 'Terminology > Not found in target' quality is now split into 4 sub-categories
translate5 - 7.6.5:  Additional improvements 


### Changed
**[TRANSLATE-4037](https://jira.translate5.net/browse/TRANSLATE-4037): LanguageResources, t5memory - On resaving segments to TM: Log segmentnumber in task** <br>
On reimporting segments to the t5memory failed segment ids are now added to the log record.

**[TRANSLATE-4035](https://jira.translate5.net/browse/TRANSLATE-4035): ConnectWorldserver - Plugin ConnectWorldserver: Cache for expensive function getAllUsers()** <br>
To speed things up, the result of a once loaded list is cached inside Translate5. Default timeout for this cache is 1 hour (60 minutes) and is be configurable by a Translate5 config.

!!! a once given timeout can not be shortend. This means: if cache is stored with timeout 60 it will be used for 60 minutes. !!!

**[TRANSLATE-4033](https://jira.translate5.net/browse/TRANSLATE-4033): InstantTranslate - InstantTranslate:TM minimum match rate overwritable on client level** <br>
Enables clients overwrite for match.rate border config in instant translate.

**[TRANSLATE-4027](https://jira.translate5.net/browse/TRANSLATE-4027): Auto-QA, TermTagger integration - Restore client-side termtagging for old tasks** <br>
TermTagging ability on client-side is now restored in order to be able to work with old tasks and to provide some transition period of time needed for the end users

**[TRANSLATE-3995](https://jira.translate5.net/browse/TRANSLATE-3995): file format settings - Remove not so useful rule from t5 default SRX** <br>
Enhancement: Remove Rule from t5 default file-format settings that tried to segment malformed sentences "A sentence.The next sentence." but did more harm than good


### Bugfixes
**[TRANSLATE-4049](https://jira.translate5.net/browse/TRANSLATE-4049): Export - Html entities not escaped on sdlxliff export** <br>
Fix: Escape html entities on sdlxliff export

**[TRANSLATE-4038](https://jira.translate5.net/browse/TRANSLATE-4038): InstantTranslate - HOTFIX: InstantTranslate GUI may leads to request-buildup on the backend** <br>
FIX: InstantTranslate may cause request-buildups in the backend degrading performance significantly. The fix changes the way InstantTranslate works:
* An instant translation request is only sent after the request before returned.
* If the system is too slow for "instant" translation (or too many Languageresources are assigned to the current customer) Instant translate will switch back to manual mode with a "translate" button

**[TRANSLATE-4021](https://jira.translate5.net/browse/TRANSLATE-4021): VisualReview / VisualTranslation - Visual does not reflect changes in the WYSIWYG with freshly imported translation tasks** <br>
FIX: Visual does not reflect changes in the WYSIWYG with freshly imported translation tasks

**[TRANSLATE-3971](https://jira.translate5.net/browse/TRANSLATE-3971): Import/Export - SDLXLIFF internal tags with "textual" IDs** <br>
SDLXLIFF: Fixed processing of format tags from QuickInsertsList

**[TRANSLATE-3714](https://jira.translate5.net/browse/TRANSLATE-3714): Editor general, usability editor - Summarize diffs in fuzzy match results** <br>
FIXED: problem with diff appearance in fuzzy match panel


## [7.6.4] - 2024-06-19

### Important Notes:
 


### Changed
**[TRANSLATE-4015](https://jira.translate5.net/browse/TRANSLATE-4015): Export - Error on export of SDLXLIFF with inserted tag** <br>
Fix: SDLXLIFF track-changes export of inserted tag

**[TRANSLATE-4003](https://jira.translate5.net/browse/TRANSLATE-4003): Okapi integration - Okapi server config only on system level** <br>
The Okapi server config is changed to system level, so that it can only be changed via CLI interface and not anymore over the UI config.


### Bugfixes
**[TRANSLATE-4012](https://jira.translate5.net/browse/TRANSLATE-4012): Editor general - RootCause: Cannot read properties of undefined (reading 'replace')** <br>
translate-7.6.4: Implement logging to better trace this problem.

**[TRANSLATE-4004](https://jira.translate5.net/browse/TRANSLATE-4004): Installation & Update - switching http https context on using sessionTokens** <br>
When accessing /editor instead /editor/ a redirect to http was made also in https context which might break translate5 integration scenarios 

**[TRANSLATE-4001](https://jira.translate5.net/browse/TRANSLATE-4001): Workflows - ArchiveTaskActions may be stuck on old tasks and loose data** <br>
ArchiveTaskActions does not archive tasks if there are old tasks in state error and workflowstep filter is used.

**[TRANSLATE-3999](https://jira.translate5.net/browse/TRANSLATE-3999): Auto-QA - Line length evaluation in Length check fires warnings** <br>
Fix: Line length evaluation in Length check fires warnings

**[TRANSLATE-3968](https://jira.translate5.net/browse/TRANSLATE-3968): API, Authentication - Internal API not usable with application tokens** <br>
7.6.4: Fix for sessions created via API token
7.6.2: Functionality which is using the Internal API is not usable with application tokens.

**[TRANSLATE-3924](https://jira.translate5.net/browse/TRANSLATE-3924): TermPortal - TermTranslation terms do not appear in TermCollection** <br>
FIXED: problem with reimport translated term back to termcollection


## [7.6.3] - 2024-06-10

### Important Notes:
 


### Added
**[TRANSLATE-4000](https://jira.translate5.net/browse/TRANSLATE-4000): Editor general - Simple json editor for UI configs** <br>
New simple json editor in the UI for map configs.


### Changed
**[TRANSLATE-3923](https://jira.translate5.net/browse/TRANSLATE-3923): Auto-QA - "Not found in target" category according to target term** <br>
translate5 - 7.5.0: Quality errors in 'Not found in target' category group now count cases when best possible translations of source terms are not found in segment target
translate5 - 7.6.3: Improve tests


### Bugfixes
**[TRANSLATE-3998](https://jira.translate5.net/browse/TRANSLATE-3998): Export - Wrong date values in excel export** <br>
Fixed wrong dates in excel export when the date time is 00:00:00

**[TRANSLATE-2500](https://jira.translate5.net/browse/TRANSLATE-2500): Main back-end mechanisms (Worker, Logging, etc.) - Worker Architecture: Solving Problems with Deadlocks and related Locking/Mutex Quirks** <br>
5.2.2 Improved the internal worker handling regarding DB dead locks and a small opportunity that workers run twice.
7.5.0 Improved the setRunning condition to reduce duplicated worker runs
7.6.3 Improved worker queue for large project imports



## [7.6.2] - 2024-06-07

### Important Notes:
#### [TRANSLATE-3983](https://jira.translate5.net/browse/TRANSLATE-3983)
The workers are now called as individual process instead http requests by default.
 


### Changed
**[TRANSLATE-3985](https://jira.translate5.net/browse/TRANSLATE-3985): t5memory - Add check for language resource status before migration** <br>
Added checking language resource status before starting migration.

**[TRANSLATE-3983](https://jira.translate5.net/browse/TRANSLATE-3983): Main back-end mechanisms (Worker, Logging, etc.) - Use process based workers by default** <br>
The config runtimeOptions.worker.triggerType is changed now from http to process by default.

**[TRANSLATE-3981](https://jira.translate5.net/browse/TRANSLATE-3981): Installation & Update - Installation is not working anymore** <br>
FIXED: problem with installation

**[TRANSLATE-3978](https://jira.translate5.net/browse/TRANSLATE-3978): InstantTranslate - Change font in InstantTranslate help window** <br>
Set up font name, size and color in InstanstTranslate and TermPortal help windows


### Bugfixes
**[TRANSLATE-3993](https://jira.translate5.net/browse/TRANSLATE-3993): Main back-end mechanisms (Worker, Logging, etc.) - Final task import workers are initialized with the wrong status** <br>
In seldom circumstances the import produces tasks with no segments imported due wrong order called workers.

**[TRANSLATE-3992](https://jira.translate5.net/browse/TRANSLATE-3992): Test framework - wait for worker instead task status** <br>
Improvements Test-API: functionality to wait for workers to be finished

**[TRANSLATE-3989](https://jira.translate5.net/browse/TRANSLATE-3989): MatchAnalysis & Pretranslation - OpenAI: Non-trained Models cannot be used for batch-translation** <br>
FIX: Non-trained OpenAI Models failed when used for batch-translation (task-import)

**[TRANSLATE-3988](https://jira.translate5.net/browse/TRANSLATE-3988): TrackChanges - UI Crash on opening or saving a segment with track changes** <br>
Some weird cascading track-changes tags lead to a crash of the segment editor on startup / segment save.

**[TRANSLATE-3986](https://jira.translate5.net/browse/TRANSLATE-3986): Import/Export - Export not possible when okapi import had errors** <br>
Fix an issue which prevents task export when some files of the task could not be imported with Okapi.

**[TRANSLATE-3982](https://jira.translate5.net/browse/TRANSLATE-3982): Editor general - Transtilde may seep into internal tags** <br>
FIX: Special string may ends up in the segments content in the Richtext-Editor with Placeables

**[TRANSLATE-3979](https://jira.translate5.net/browse/TRANSLATE-3979): Editor general - Users with no roles can not be deleted via UI** <br>
Fixes problem where users with no roles cannot be removed via UI.

**[TRANSLATE-3977](https://jira.translate5.net/browse/TRANSLATE-3977): Main back-end mechanisms (Worker, Logging, etc.) - Error: Typed property ZfExtended_Logger_Writer_Database::$insertedData must not be accessed before initialization** <br>
FIXED: flooding the log due to unhandled duplicates

**[TRANSLATE-3976](https://jira.translate5.net/browse/TRANSLATE-3976): Export - Multi segment target in SDLXLIFF handled incorrectly on export** <br>
Fix export of sdlxliff: provide draft config field, matchrate and related fields in multi-segment target

**[TRANSLATE-3972](https://jira.translate5.net/browse/TRANSLATE-3972): Import/Export - XLIFF import: tags paired by RID are not paired anymore: TESTS** <br>
Added tests to check pairing of ept/bpt by RID on import of xliff

**[TRANSLATE-3968](https://jira.translate5.net/browse/TRANSLATE-3968): API, Authentication - Internal API not usable with application tokens** <br>
Functionality which is using the Internal API is not usable with application tokens.

**[TRANSLATE-3966](https://jira.translate5.net/browse/TRANSLATE-3966): Import/Export - Ensure that SDLXLIFF changemarks and locked segments are working well together** <br>
The SDLXLIFF export of changemarks applied to locked tags may lead to invalid SDLXLIFF.


## [7.6.1] - 2024-05-29

### Important Notes:
 


### Bugfixes
**[TRANSLATE-3970](https://jira.translate5.net/browse/TRANSLATE-3970): Import/Export - XLIFF import: tags paired by RID are not paired anymore FIX-script** <br>
Added CLI command to fix TRANSLATE-3967

**[TRANSLATE-3969](https://jira.translate5.net/browse/TRANSLATE-3969): Editor general - comments in task are shown as {content:nl2br:htmlEncode}** <br>
Fix comment escaping in task view

**[TRANSLATE-3967](https://jira.translate5.net/browse/TRANSLATE-3967): Import/Export - XLIFF import: tags paired by RID are not paired anymore** <br>
FIX: pbt/ept tag pairs connected by RID may could not be paired anymore in T5 when singular-tags with conflicting namespaced id's are present

**[TRANSLATE-3957](https://jira.translate5.net/browse/TRANSLATE-3957): Import/Export - SDLXLIFF diff export struggles when there are entities in the raw content** <br>
SDLXLIFF diff export does not export XML entities correctly


## [7.6.0] - 2024-05-24

### Important Notes:
#### [TRANSLATE-3852](https://jira.translate5.net/browse/TRANSLATE-3852)
Complex workflow step 2nd revision changed: when entering this step all locked 100% matches are opened for editing and auto QA is re-triggered.
 


### Added
**[TRANSLATE-3852](https://jira.translate5.net/browse/TRANSLATE-3852): Workflows - Workflow step "Revision language with editable 100% matches"** <br>
Complex workflow step 2nd revision renamed to "2nd revision language with editable 100% matches" and action added to unlock 100% matches for editing and trigger auto QA in this step.

**[TRANSLATE-3850](https://jira.translate5.net/browse/TRANSLATE-3850): OpenId Connect - SSO via OpenID: Define if IDP should be able to remove rights** <br>
New client flag added for OpenId configured IDP. It can enable or disable updating of user roles, gender and locale from the IDP user claims.


### Changed
**[TRANSLATE-3964](https://jira.translate5.net/browse/TRANSLATE-3964): Import/Export - Prevent PXSS in filenames** <br>
Fixed XSS issues in filenames

**[TRANSLATE-3960](https://jira.translate5.net/browse/TRANSLATE-3960): Editor general - Test PXSS in all input fields of the application** <br>
Security: fixed some remaining PXSS issues.


### Bugfixes
**[TRANSLATE-3965](https://jira.translate5.net/browse/TRANSLATE-3965): Package Ex and Re-Import - Missing dot in TMX file names in translator package** <br>
In translator packages the TMX files are generated with the dot before the TMX file extension. This is fixed now.

**[TRANSLATE-3959](https://jira.translate5.net/browse/TRANSLATE-3959): Editor general - Languages filters: search with no value leads to an error** <br>
Fixes problem when filtering languages with no value in language resources overview 

**[TRANSLATE-3956](https://jira.translate5.net/browse/TRANSLATE-3956): TermTagger integration - Backend error on recalculation of the transFound transNotFound and transNotDefined** <br>
FIXED: problem popping when only translations are found but that's homonym ones

**[TRANSLATE-3955](https://jira.translate5.net/browse/TRANSLATE-3955): Content Protection - Task creation fails on sublanguage if main languages was deleted** <br>
Fix: Task creation fails on sublanguage if main languages was deleted

**[TRANSLATE-3954](https://jira.translate5.net/browse/TRANSLATE-3954): Editor general - Missing instant translate auto set role for admins** <br>
Added missing auto-set roles for admin users.

**[TRANSLATE-3951](https://jira.translate5.net/browse/TRANSLATE-3951): Editor general - Missing userGuid in user tracking table** <br>
Fix for error when empty user entire exist in user tracking table.

**[TRANSLATE-3950](https://jira.translate5.net/browse/TRANSLATE-3950): ConnectWorldserver, Import/Export - Add missing tests to TRANSLATE-3931** <br>
Add missing tests to TRANSLATE-3931

**[TRANSLATE-3683](https://jira.translate5.net/browse/TRANSLATE-3683): VisualReview / VisualTranslation - when source segment is edited it will be shown in target visual** <br>
FIX: The WYSYWIG Visual now only reflects changes on the (first) target and not e.g. an editable source

**[TRANSLATE-3621](https://jira.translate5.net/browse/TRANSLATE-3621): VisualReview / VisualTranslation - If first repetition is selected, all repetitions are highlightes green** <br>
Improvement: When the Visual has more repetitions of a certain segment then the grid, these additional repetitions (on the end of the visual) are nowassociated with the last repetition in the grid instead of the first


## [7.5.0] - 2024-05-17

### Important Notes:
#### [TRANSLATE-3896](https://jira.translate5.net/browse/TRANSLATE-3896)
OKAPI now is the default CSV-parser
 


### Added
**[TRANSLATE-3534](https://jira.translate5.net/browse/TRANSLATE-3534): Import/Export, TrackChanges - TrackChanges sdlxliff round-trip** <br>
Accept track changes sdlxliff markup on import and transform it to translate5 syntax.
Propagate translate5 track changes to sdlxliff file on export


### Changed
**[TRANSLATE-3931](https://jira.translate5.net/browse/TRANSLATE-3931): ConnectWorldserver, Import/Export - Optionally remove content from sdlxliff target segments, that contain only tags and/or whitespace** <br>
7.5.0: Add possibility to optionally remove content from sdlxliff target segments, that contain only tags and/or whitespace 

**[TRANSLATE-3923](https://jira.translate5.net/browse/TRANSLATE-3923): Auto-QA - "Not found in target" category according to target term** <br>
Quality errors in 'Not found in target' category group now count cases when best possible translations of source terms are not found in segment target

**[TRANSLATE-3914](https://jira.translate5.net/browse/TRANSLATE-3914): VisualReview / VisualTranslation - Change visual wget test data location** <br>
Change internas of the wget test.

**[TRANSLATE-3905](https://jira.translate5.net/browse/TRANSLATE-3905): InstantTranslate - Improve API usage to provide file content as normal POST parameter** <br>
Improve the instanttranslate API to enable filepretranslations also via plain POST requests.

**[TRANSLATE-3896](https://jira.translate5.net/browse/TRANSLATE-3896): Import/Export - Use Okapi for CSV files by default** <br>
OKAPI now is the default Parser for CSV files and the translate5 internal parser has to be enabled in the config if it shall be used instead

**[TRANSLATE-3537](https://jira.translate5.net/browse/TRANSLATE-3537): Import/Export - Process comments from xliff 1.2 files** <br>
7.5.0: Change export config label and description
6.8.0: XLF comments placed in note tags are now also imported and exported as task comments. The behavior is configurable.


### Bugfixes
**[TRANSLATE-3949](https://jira.translate5.net/browse/TRANSLATE-3949): t5memory - Reimport segments does not work as expected** <br>
Fix reimport task into t5memory

**[TRANSLATE-3948](https://jira.translate5.net/browse/TRANSLATE-3948): Import/Export - FIX: pmlight cannot import tasks** <br>
FIX: pm-light role could not import tasks due to insufficient rights

**[TRANSLATE-3937](https://jira.translate5.net/browse/TRANSLATE-3937): Import/Export - Matchrate calculated wrong on import** <br>
Fixed match rate calculation on importing xlf files containing alt-trans nodes

**[TRANSLATE-3935](https://jira.translate5.net/browse/TRANSLATE-3935): Import/Export - SQL query runs into timeout with large file with many repetitions** <br>
Fix for deadlock problem when syncing repetitions.

**[TRANSLATE-3934](https://jira.translate5.net/browse/TRANSLATE-3934): Import/Export - hotfolder project export: warning for empty segments** <br>
The warning E1150 if okapi export had empty targets is now logged only if there was an error on exporting via Okapi.

**[TRANSLATE-3930](https://jira.translate5.net/browse/TRANSLATE-3930): t5memory - Fix stripFramingTags parameter in request to t5memory** <br>
Fixed passing "strip framing tags" value to t5memory

**[TRANSLATE-3926](https://jira.translate5.net/browse/TRANSLATE-3926): GroupShare integration - Fix GroupShare connector in order to work with translate5 7.4.0 and 7.4.1** <br>
GroupShare plug-in was not compatible to latest version 7.4.0 and 7.4.1

**[TRANSLATE-3921](https://jira.translate5.net/browse/TRANSLATE-3921): t5memory - Disable direct t5memory TM download due data disclosure** <br>
Disabled t5memory download TM functionality due a data disclosure - the TM file did contain the filenames of other opened TM files at the same time.

**[TRANSLATE-3920](https://jira.translate5.net/browse/TRANSLATE-3920): User Management - Hotfolder projects make Client PM selectable as default** <br>
Hotfolder plugin: Add clientPm role to PM list in settings

**[TRANSLATE-3911](https://jira.translate5.net/browse/TRANSLATE-3911): Configuration - Hotfolder settings passwort and DeepL API key readable when write protected** <br>
Configs visibility can be restricted based on a user roles.

**[TRANSLATE-3907](https://jira.translate5.net/browse/TRANSLATE-3907): Hotfolder Import - Hotfolder Bug fixes** <br>
Hotfolder plugin: use PM over-written on client level

**[TRANSLATE-3882](https://jira.translate5.net/browse/TRANSLATE-3882): Export - Export of Project Fails due to XML Parser Problems** <br>
FIX: BUG with XML-Parser during Export

**[TRANSLATE-3869](https://jira.translate5.net/browse/TRANSLATE-3869): Import/Export - trackChanges for sdlxliff should only be contained in Changes-Export** <br>
Fix: Export sdlxliff without track changes no longer produce revision tags

**[TRANSLATE-3749](https://jira.translate5.net/browse/TRANSLATE-3749): Auto-QA - QA consistency wrong results** <br>
FIX: Evaluation of QA errors/problems did not respect locked segments. Now locked segments will not count for QA problems

**[TRANSLATE-3713](https://jira.translate5.net/browse/TRANSLATE-3713): TermTagger integration, usability editor - Wrong target term high-lighted in right column of the editor** <br>
Improved target terms usage highlighting in right-side Termportlet

**[TRANSLATE-2500](https://jira.translate5.net/browse/TRANSLATE-2500): Main back-end mechanisms (Worker, Logging, etc.) - Worker Architecture: Solving Problems with Deadlocks and related Locking/Mutex Quirks** <br>
7.5.0 Improved the setRunning condition to reduce duplicated worker runs
5.2.2 Improved the internal worker handling regarding DB dead locks and a small opportunity that workers run twice.


## [7.4.1] - 2024-05-03

### Important Notes:
 


### Bugfixes
**[TRANSLATE-3917](https://jira.translate5.net/browse/TRANSLATE-3917): Editor general - UI error when confirming task** <br>
Fix for UI error when user confirms job.


## [7.4.0] - 2024-04-30

### Important Notes:
#### [TRANSLATE-3857](https://jira.translate5.net/browse/TRANSLATE-3857)
for on premise docker users: healthcheck for languagetool changed, including automatic restart on failure!

#### [TRANSLATE-3784](https://jira.translate5.net/browse/TRANSLATE-3784)
New settings possible described with this change
https://confluence.translate5.net/x/TgET#:~:text=Use%20MicroSoft%20OAuth2%20routing%20to%20send%20mail%3A
 


### Added
**[TRANSLATE-3853](https://jira.translate5.net/browse/TRANSLATE-3853): Package Ex and Re-Import - Possibility to disallow the export of translator offline packages** <br>
Based on a ACL, enable or disable the package export for user roles.

**[TRANSLATE-3851](https://jira.translate5.net/browse/TRANSLATE-3851): MatchAnalysis & Pretranslation - Add language combination to Excel export of analysis** <br>
The task language codes are added in the analysis excel export.

**[TRANSLATE-3593](https://jira.translate5.net/browse/TRANSLATE-3593): Auto-QA, TermTagger integration - Split 'Terminology > Not found in target' AutoQA-category into 4 categories** <br>
'Terminology > Not found in target' quality is now split into 4 sub-categories

**[TRANSLATE-3566](https://jira.translate5.net/browse/TRANSLATE-3566): ConnectWorldserver - Plugin ConnectWorldServer: Use Translate5 for Pretranslation** <br>
Added automatic Pretranslation to existing Plugin ConnectWorldserver

**[TRANSLATE-3206](https://jira.translate5.net/browse/TRANSLATE-3206): Configuration, Import/Export - Protect and auto-convert numbers and general patterns during translation** <br>
Numbers are protected with tags for all translations jobs. Custom patterns for number protections can be defined in separate UI.


### Changed
**[TRANSLATE-3910](https://jira.translate5.net/browse/TRANSLATE-3910): t5memory - Add log record when t5memory memory is split into pieces** <br>
When memory is split into pieces due to error - log record is added

**[TRANSLATE-3857](https://jira.translate5.net/browse/TRANSLATE-3857): Installation & Update - docker on premise: languagetool healthcheck changed** <br>
docker compose pull to get the latest containers. For languagetool there is now a health check which forces the languagetool to restart when either the process crashed or it does not respond on HTTP requests

**[TRANSLATE-3856](https://jira.translate5.net/browse/TRANSLATE-3856): t5memory - Fix t5memory export if file is deleted** <br>
t5memory migration command error output is improved to be more descriptive

**[TRANSLATE-3843](https://jira.translate5.net/browse/TRANSLATE-3843): VisualReview / VisualTranslation - Detected Numbered lists may not actually be numbered lists leading to faulty/shifted layouts** <br>
FIX Visual Reflow: Detected Numbered lists-items may not actually be numbered lists leading to broken layouts.

**[TRANSLATE-3822](https://jira.translate5.net/browse/TRANSLATE-3822): InstantTranslate - Add InstantTranslate-Video to help button in translate5** <br>
Added ability to hide InstantTranslate help button or load contents of help window from custom URL

**[TRANSLATE-3784](https://jira.translate5.net/browse/TRANSLATE-3784): Installation & Update - Add SMTP OAuth 2.0 integration** <br>
New mail transport: ZfExtended_Zend_Mail_Transport_MSGraph.
Provides possibility to send mail using MicroSoft cloud services with OAuth2 authorisation protocol.
https://confluence.translate5.net/display/CON/Installation+specific+options

**[TRANSLATE-3774](https://jira.translate5.net/browse/TRANSLATE-3774): LanguageResources - Content Protection: Alter Language Resource conversion state logic** <br>
Alter Language Resource conversion state logic to respond on rules changes

**[TRANSLATE-3585](https://jira.translate5.net/browse/TRANSLATE-3585): LanguageResources - Content protection: Translation Memory Conversion** <br>
Content protection in translation memory conversion


### Bugfixes
**[TRANSLATE-3909](https://jira.translate5.net/browse/TRANSLATE-3909): OpenId Connect - OpenAI: set model parameters max/min** <br>
Fix problem where OpenAI model parameters are not settable to 0.

**[TRANSLATE-3901](https://jira.translate5.net/browse/TRANSLATE-3901): Editor general - Add LCIDs 2816 (zh-TW), 3082 (es-ES)** <br>
Added additional lcids for languages.

**[TRANSLATE-3897](https://jira.translate5.net/browse/TRANSLATE-3897): Main back-end mechanisms (Worker, Logging, etc.) - Operation workers: missing dependencies** <br>
Added missing worker dependency for task operation workers.

**[TRANSLATE-3890](https://jira.translate5.net/browse/TRANSLATE-3890): Workflows - Competing assignment for complex workflow** <br>
When using more complex workflows as just the default workflow with competing user assignment did delete all users with the same role (translators or reviewers or second reviewers) regardless of the workflow step. Now only the users of the same workflow step as the current user are deleted.

**[TRANSLATE-3879](https://jira.translate5.net/browse/TRANSLATE-3879): MatchAnalysis & Pretranslation - Batch result cleanup problem** <br>
Fix for a problem with conflicting data when multiple batch pre-translations are running at once.

**[TRANSLATE-3878](https://jira.translate5.net/browse/TRANSLATE-3878): LanguageResources - LanguageResource specificId column is to short** <br>
The specificId field for languageresources was too short, cutting data for some specific LanguageResources using long language combinations.

**[TRANSLATE-3872](https://jira.translate5.net/browse/TRANSLATE-3872): Editor general, Import/Export - Processing single tags works wrong if they are differ in source and target** <br>
Fixed bug which caused inappropriate single tags parsing when id of tags are not the same in source and target 

**[TRANSLATE-3871](https://jira.translate5.net/browse/TRANSLATE-3871): Okapi integration - Fix Okapi maintenance commands** <br>
Fix okapi maintenance commands.

**[TRANSLATE-3870](https://jira.translate5.net/browse/TRANSLATE-3870): InstantTranslate - InstantTranslate linebreaks** <br>
Fix for a problem where line breaks are not copied to clipboard.

**[TRANSLATE-3833](https://jira.translate5.net/browse/TRANSLATE-3833): Repetition editor - repetitions of blocked segments should not be treated as repetitions** <br>
Blocked segments will not be evaluated as repeated segments and also not as repetition master segment.

**[TRANSLATE-3770](https://jira.translate5.net/browse/TRANSLATE-3770): Editor general - Fix phpstan findings** <br>
Fix several coding problems found by static analysis.

**[TRANSLATE-3766](https://jira.translate5.net/browse/TRANSLATE-3766): Configuration - make runtimeOptions.frontend.importTask.edit100PercentMatch config not only for UI** <br>
The config which enables edition of 100% matches will affect the API to.

**[TRANSLATE-3700](https://jira.translate5.net/browse/TRANSLATE-3700): TermPortal - Term portal: help button always visible** <br>
Added ability to hide TermPortal help button or load contents of help window from custom URL

**[TRANSLATE-3600](https://jira.translate5.net/browse/TRANSLATE-3600): Auto-QA - Change Qualities using the ToSort column to evaluate their contents** <br>
Changed qualities to use a different data column as base of evaluation, improve number-check for number protection

**[TRANSLATE-2753](https://jira.translate5.net/browse/TRANSLATE-2753): Editor general, usability editor - Change task progress calculation by excluding blocked segments** <br>
(B)locked segments are now excluded from progress calculation, which is now in addition divided into task overall progress and user-specific progress when user have segments range defined

**[TRANSLATE-514](https://jira.translate5.net/browse/TRANSLATE-514): Main back-end mechanisms (Worker, Logging, etc.) - Improve Worker garbage clean up and implement a dead worker recognition** <br>
Due problems with the worker system the logging of the workers had to be changed / improved. A delay for the startup of workers which could not be started was also introduced to reduce the risk of internal endless loops.


## [7.4.0] - 2024-04-30

### Important Notes:
#### [TRANSLATE-3857](https://jira.translate5.net/browse/TRANSLATE-3857)
for on premise docker users: healthcheck for languagetool changed, including automatic restart on failure!

#### [TRANSLATE-3784](https://jira.translate5.net/browse/TRANSLATE-3784)
New settings possible described with this change
https://confluence.translate5.net/x/TgET#:~:text=Use%20MicroSoft%20OAuth2%20routing%20to%20send%20mail%3A
 


### Added
**[TRANSLATE-3858](https://jira.translate5.net/browse/TRANSLATE-3858): t5connect - test** <br>


**[TRANSLATE-3853](https://jira.translate5.net/browse/TRANSLATE-3853): Package Ex and Re-Import - Possibility to disallow the export of translator offline packages** <br>
Based on a ACL, enable or disable the package export for user roles.

**[TRANSLATE-3851](https://jira.translate5.net/browse/TRANSLATE-3851): MatchAnalysis & Pretranslation - Add language combination to Excel export of analysis** <br>
The task language codes are added in the analysis excel export.

**[TRANSLATE-3593](https://jira.translate5.net/browse/TRANSLATE-3593): Auto-QA, TermTagger integration - Split 'Terminology > Not found in target' AutoQA-category into 4 categories** <br>
'Terminology > Not found in target' quality is now split into 4 sub-categories

**[TRANSLATE-3566](https://jira.translate5.net/browse/TRANSLATE-3566): ConnectWorldserver - Plugin ConnectWorldServer: Use Translate5 for Pretranslation** <br>
Added automatic Pretranslation to existing Plugin ConnectWorldserver

**[TRANSLATE-3206](https://jira.translate5.net/browse/TRANSLATE-3206): Configuration, Import/Export - Protect and auto-convert numbers and general patterns during translation** <br>
Numbers are protected with tags for all translations jobs. Custom patterns for number protections can be defined in separate UI.


### Changed
**[TRANSLATE-3910](https://jira.translate5.net/browse/TRANSLATE-3910): t5memory - Add log record when t5memory memory is split into pieces** <br>
When memory is split into pieces due to error - log record is added

**[TRANSLATE-3875](https://jira.translate5.net/browse/TRANSLATE-3875): Workflows - Sasha test improvement 1** <br>
d;

**[TRANSLATE-3874](https://jira.translate5.net/browse/TRANSLATE-3874): Auto-QA, Workflows - Sasha test 2** <br>
fj

**[TRANSLATE-3867](https://jira.translate5.net/browse/TRANSLATE-3867): API - Sasha test improvement** <br>
r

**[TRANSLATE-3857](https://jira.translate5.net/browse/TRANSLATE-3857): Installation & Update - docker on premise: languagetool healthcheck changed** <br>
docker compose pull to get the latest containers. For languagetool there is now a health check which forces the languagetool to restart when either the process crashed or it does not respond on HTTP requests

**[TRANSLATE-3856](https://jira.translate5.net/browse/TRANSLATE-3856): t5memory - Fix t5memory export if file is deleted** <br>
t5memory migration command error output is improved to be more descriptive

**[TRANSLATE-3843](https://jira.translate5.net/browse/TRANSLATE-3843): VisualReview / VisualTranslation - Detected Numbered lists may not actually be numbered lists leading to faulty/shifted layouts** <br>
FIX Visual Reflow: Detected Numbered lists-items may not actually be numbered lists leading to broken layouts.

**[TRANSLATE-3822](https://jira.translate5.net/browse/TRANSLATE-3822): InstantTranslate - Add InstantTranslate-Video to help button in translate5** <br>
Added ability to hide InstantTranslate help button or load contents of help window from custom URL

**[TRANSLATE-3784](https://jira.translate5.net/browse/TRANSLATE-3784): Installation & Update - Add SMTP OAuth 2.0 integration** <br>
New mail transport: ZfExtended_Zend_Mail_Transport_MSGraph.
Provides possibility to send mail using MicroSoft cloud services with OAuth2 authorisation protocol.
https://confluence.translate5.net/display/CON/Installation+specific+options

**[TRANSLATE-3774](https://jira.translate5.net/browse/TRANSLATE-3774): LanguageResources - Content Protection: Alter Language Resource conversion state logic** <br>
Alter Language Resource conversion state logic to respond on rules changes

**[TRANSLATE-3585](https://jira.translate5.net/browse/TRANSLATE-3585): LanguageResources - Content protection: Translation Memory Conversion** <br>
Content protection in translation memory conversion


### Bugfixes
**[TRANSLATE-3909](https://jira.translate5.net/browse/TRANSLATE-3909): OpenId Connect - OpenAI: set model parameters max/min** <br>
Fix problem where OpenAI model parameters are not settable to 0.

**[TRANSLATE-3901](https://jira.translate5.net/browse/TRANSLATE-3901): Editor general - Add LCIDs 2816 (zh-TW), 3082 (es-ES)** <br>
Added additional lcids for languages.

**[TRANSLATE-3897](https://jira.translate5.net/browse/TRANSLATE-3897): Main back-end mechanisms (Worker, Logging, etc.) - Operation workers: missing dependencies** <br>
Added missing worker dependency for task operation workers.

**[TRANSLATE-3890](https://jira.translate5.net/browse/TRANSLATE-3890): Workflows - Competing assignment for complex workflow** <br>
When using more complex workflows as just the default workflow with competing user assignment did delete all users with the same role (translators or reviewers or second reviewers) regardless of the workflow step. Now only the users of the same workflow step as the current user are deleted.

**[TRANSLATE-3879](https://jira.translate5.net/browse/TRANSLATE-3879): MatchAnalysis & Pretranslation - Batch result cleanup problem** <br>
Fix for a problem with conflicting data when multiple batch pre-translations are running at once.

**[TRANSLATE-3878](https://jira.translate5.net/browse/TRANSLATE-3878): LanguageResources - LanguageResource specificId column is to short** <br>
The specificId field for languageresources was too short, cutting data for some specific LanguageResources using long language combinations.

**[TRANSLATE-3872](https://jira.translate5.net/browse/TRANSLATE-3872): Editor general, Import/Export - Processing single tags works wrong if they are differ in source and target** <br>
Fixed bug which caused inappropriate single tags parsing when id of tags are not the same in source and target 

**[TRANSLATE-3871](https://jira.translate5.net/browse/TRANSLATE-3871): Okapi integration - Fix Okapi maintenance commands** <br>
Fix okapi maintenance commands.

**[TRANSLATE-3870](https://jira.translate5.net/browse/TRANSLATE-3870): InstantTranslate - InstantTranslate linebreaks** <br>
Fix for a problem where line breaks are not copied to clipboard.

**[TRANSLATE-3833](https://jira.translate5.net/browse/TRANSLATE-3833): Repetition editor - repetitions of blocked segments should not be treated as repetitions** <br>
Blocked segments will not be evaluated as repeated segments and also not as repetition master segment.

**[TRANSLATE-3770](https://jira.translate5.net/browse/TRANSLATE-3770): Editor general - Fix phpstan findings** <br>
Fix several coding problems found by static analysis.

**[TRANSLATE-3766](https://jira.translate5.net/browse/TRANSLATE-3766): Configuration - make runtimeOptions.frontend.importTask.edit100PercentMatch config not only for UI** <br>
The config which enables edition of 100% matches will affect the API to.

**[TRANSLATE-3700](https://jira.translate5.net/browse/TRANSLATE-3700): TermPortal - Term portal: help button always visible** <br>
Added ability to hide TermPortal help button or load contents of help window from custom URL

**[TRANSLATE-3600](https://jira.translate5.net/browse/TRANSLATE-3600): Auto-QA - Change Qualities using the ToSort column to evaluate their contents** <br>
Changed qualities to use a different data column as base of evaluation, improve number-check for number protection

**[TRANSLATE-2753](https://jira.translate5.net/browse/TRANSLATE-2753): Editor general, usability editor - Change task progress calculation by excluding blocked segments** <br>
(B)locked segments are now excluded from progress calculation, which is now in addition divided into task overall progress and user-specific progress when user have segments range defined

**[TRANSLATE-514](https://jira.translate5.net/browse/TRANSLATE-514): Main back-end mechanisms (Worker, Logging, etc.) - Improve Worker garbage clean up and implement a dead worker recognition** <br>
Due problems with the worker system the logging of the workers had to be changed / improved. A delay for the startup of workers which could not be started was also introduced to reduce the risk of internal endless loops.


## [7.3.2] - 2024-04-16

### Important Notes:
 


### Changed
**[TRANSLATE-3877](https://jira.translate5.net/browse/TRANSLATE-3877): Hotfolder Import - Change importing user to system user** <br>
Changed the importing user to system user instead the associated PM. This ensures that the PM gets an E-Mail when the task was created.

**[TRANSLATE-3866](https://jira.translate5.net/browse/TRANSLATE-3866): Editor general - Improve logging of invalid markup on segment PUT** <br>
FIX: Improve logging of invalid markup sent on segment PUT


### Bugfixes
**[TRANSLATE-3891](https://jira.translate5.net/browse/TRANSLATE-3891): Import/Export - Disable sdlxliff track changes  export** <br>
Disable of sdlxliff track changes on export because of a problem in the segment data structure.

**[TRANSLATE-3885](https://jira.translate5.net/browse/TRANSLATE-3885): Auto-QA, usability editor - Change wording for "false positives"** <br>
Improved wordings for false positives in the auto-QA


## [7.3.1] - 2024-04-09

### Important Notes:
 


### Changed
**[TRANSLATE-3860](https://jira.translate5.net/browse/TRANSLATE-3860): VisualReview / VisualTranslation - FIX: Bugs of Visual Enhancements Milestone I** <br>
FIX Visual: Segments may be duplicated in the reflow / WYSIWYG and appear multiple times overlapping other segments


### Bugfixes
**[TRANSLATE-3865](https://jira.translate5.net/browse/TRANSLATE-3865): LanguageResources, User Management - Error on language resources for admins with no assigned users** <br>
Fix for a problem in language resources overview for users with 0 assigned customers.

**[TRANSLATE-3864](https://jira.translate5.net/browse/TRANSLATE-3864): Import/Export - Problem with sdlxliff export and track changes** <br>
Fix for sdlxliff export and track changes.


## [7.3.0] - 2024-04-05

### Important Notes:
#### [TRANSLATE-3534](https://jira.translate5.net/browse/TRANSLATE-3534)
From this version on MariaDB 10.11 or higher is needed, MySQL support is dropped. If you are using the dockerized setup nothing must be done.

#### [TRANSLATE-3655](https://jira.translate5.net/browse/TRANSLATE-3655)
Needs at least t5memory >= 0.5.58, versions prior 0.5 must be migrated with the CLI t5memory:migration command!
This feature is disabled by default and can be enabled by the translate5 team on hosted instances. On self-hosted instances, it can be enabled via the translate5 command-line tool by setting runtimeOptions.LanguageResources.t5memory.stripFramingTagsEnabled to 1.
 


### Added
**[TRANSLATE-3790](https://jira.translate5.net/browse/TRANSLATE-3790): LanguageResources - Overwrite DeepL API key per client** <br>
Allow rewrite DeepL API key on customer config level

**[TRANSLATE-3534](https://jira.translate5.net/browse/TRANSLATE-3534): Import/Export, TrackChanges - TrackChanges sdlxliff round-trip** <br>
Accept track changes sdlxliff markup on import and transform it to translate5 syntax.
Propagate translate5 track changes to sdlxliff file on export


### Changed
**[TRANSLATE-3842](https://jira.translate5.net/browse/TRANSLATE-3842): VisualReview / VisualTranslation - Newlines from segments (internal whitespace tags) do often "destroy" the layout, especially in the new paragraph layouts** <br>
ENHANCEMENT: 
* Configurable option to strip newlines from the segments when translating the WYSIWYG
* always strip newlines from segments for pragraph-fields in the WYSIWYG

**[TRANSLATE-3841](https://jira.translate5.net/browse/TRANSLATE-3841): Main back-end mechanisms (Worker, Logging, etc.) - TextShuttle available for clients with support contract** <br>
TextShuttle plugin is now available with support contract.

**[TRANSLATE-3839](https://jira.translate5.net/browse/TRANSLATE-3839): LanguageResources - Add possibility to UI to use timestamp of last segment save, when re-importing a task to the TM** <br>
Add new option to reimport task UI which purpose is to specify which time should be used for updating segment in translation memory.

**[TRANSLATE-3733](https://jira.translate5.net/browse/TRANSLATE-3733): LanguageResources - Introduce new language resource identifier specificId** <br>
Introduce a new ID field for language resources in available also via ID. 
It should contain an ID generated / coming from the originating data system - if any.

**[TRANSLATE-3655](https://jira.translate5.net/browse/TRANSLATE-3655): LanguageResources - implement new switch to deal with framing tags on TMX import** <br>
Added new option "Strip framing tags at import" for TMX import which influences the behavior of t5memory regarding segment framing tags on import.


### Bugfixes
**[TRANSLATE-3845](https://jira.translate5.net/browse/TRANSLATE-3845): Editor general - RootCause error: null is not an object (evaluating 'd.mask')** <br>
Fix problem for UI error when message bus re-sync is triggered.

**[TRANSLATE-3840](https://jira.translate5.net/browse/TRANSLATE-3840): Hotfolder Import - Hotfolder import deadline format is too strict** <br>
The deadline timeformats were to strict

**[TRANSLATE-3834](https://jira.translate5.net/browse/TRANSLATE-3834): Import/Export - runtimeOptions.project.defaultPivotLanguage not working for hotfolder projects** <br>
FIX: Apply runtimeOptions.project.defaultPivotLanguage setting on Project creation with Hotfolder plugin

**[TRANSLATE-3799](https://jira.translate5.net/browse/TRANSLATE-3799): t5memory - Segment check after update in t5memory doesn't work properly with escaped symbols** <br>
translate5 - 7.4.0: Remove tab replacement again
translate5 - 7.3.0: Additional code improvement
translate5 - 7.2.2: Fixed check if segment was updated properly in t5memory 

**[TRANSLATE-3795](https://jira.translate5.net/browse/TRANSLATE-3795): Client management - clientPM should not be able to give himself term PM rights** <br>
FIX: Remove right for "PM selected clients" to make himself a "Term PM all clients"

**[TRANSLATE-3731](https://jira.translate5.net/browse/TRANSLATE-3731): Task Management - Empty projects shows tasks of previous project** <br>
If due errors only a project is created but no tasks belonging to it, then the task list of such project behaves strange.

**[TRANSLATE-3699](https://jira.translate5.net/browse/TRANSLATE-3699): User Management - Client PM can choose user with role Light PM as PM** <br>
FIX: PM for selected clients was able to select PMs not being assigned to his clients

**[TRANSLATE-3630](https://jira.translate5.net/browse/TRANSLATE-3630): User Management - clientPM should be able to see all client configs** <br>
FIX: The PM selected clients now has access to "File format settings" and "pricing presets" for his selected clients


## [7.2.4] - 2024-03-28

### Important Notes:
#### [TRANSLATE-3837](https://jira.translate5.net/browse/TRANSLATE-3837)
for on premise docker users: healthcheck for termtagger changed
 


### Changed
**[TRANSLATE-3837](https://jira.translate5.net/browse/TRANSLATE-3837): Installation & Update - docker on premise: termtagger and languagetool healthcheck changed** <br>
docker compose pull to get the latest containers. For termtagger there is now a health check which forces the termtagger to restart when it consumes to much memory.

**[TRANSLATE-3824](https://jira.translate5.net/browse/TRANSLATE-3824): Installation & Update - Show hosting status in UI and create separate monitoring endpoint** <br>
Add a separate monitoring endpoint, add in hosting some information about the hosting status.

**[TRANSLATE-3820](https://jira.translate5.net/browse/TRANSLATE-3820): Task Management - Add tk-TM (Turkmen (Turkmenistan)) to translate5 languages** <br>
Add tk-TM (Turkmen (Turkmenistan)) to language list

**[TRANSLATE-3815](https://jira.translate5.net/browse/TRANSLATE-3815): MatchAnalysis & Pretranslation - Fix MatchAnalysisTest** <br>
Fixed test

**[TRANSLATE-3814](https://jira.translate5.net/browse/TRANSLATE-3814): Import/Export - FIX: Enable use of TMX zip archive in TM creation process** <br>
Fix translations and zip usage on TM creation process


### Bugfixes
**[TRANSLATE-3832](https://jira.translate5.net/browse/TRANSLATE-3832): Editor general - RootCause error: Cannot read properties of null (reading 'expand')** <br>
UI fixing a problem expanding the quality tree.

**[TRANSLATE-3826](https://jira.translate5.net/browse/TRANSLATE-3826): Editor general - RootCause error: me.selectedCustomersConfigStore is null** <br>
Fix for a problem when opening the task creation window and closing it immediately.

**[TRANSLATE-3825](https://jira.translate5.net/browse/TRANSLATE-3825): Editor general - No access exception: reopen locked task** <br>
Fix for a problem where task was unlocked by the inactive-cleanup component, but the user has still the translate5 task-editing UI open.

**[TRANSLATE-3823](https://jira.translate5.net/browse/TRANSLATE-3823): TermPortal - Remove non breaking spaces from terms** <br>
Remove non breaking spaces and non regular white-spaces on term import and from all existing terms in the database.

**[TRANSLATE-3821](https://jira.translate5.net/browse/TRANSLATE-3821): Export - Across Hotfoler: Export worker does not wait for Okapi worker** <br>
Fix Across Hotfolder tasks export

**[TRANSLATE-3817](https://jira.translate5.net/browse/TRANSLATE-3817): InstantTranslate - translate5 sends unescaped xml special char via InstantTranslate to t5memory** <br>
Escape potentially unescaped content sent to t5memory since this may crashes t5memory

**[TRANSLATE-3811](https://jira.translate5.net/browse/TRANSLATE-3811): VisualReview / VisualTranslation - Visual: Font may be mis-selected when one font's name is containing the other** <br>
FIX: Some visual fonts have been mis-identified as being identical

**[TRANSLATE-3769](https://jira.translate5.net/browse/TRANSLATE-3769): Editor general - Cancel import unlocks exporting task** <br>
Fix for a problem with task cancel import logic.

**[TRANSLATE-3643](https://jira.translate5.net/browse/TRANSLATE-3643): User Management - enable PM role to create MT resources** <br>
PM's are allowed to create MT resources.


## [7.2.2] - 2024-03-15

### Important Notes:
#### [TRANSLATE-3812](https://jira.translate5.net/browse/TRANSLATE-3812)
The config runtimeOptions.frontend.importTask.edit100PercentMatch is renamed to runtimeOptions.import.edit100PercentMatch and affects now API imports too. Previously this was always false for API imports.

#### [TRANSLATE-3796](https://jira.translate5.net/browse/TRANSLATE-3796)
t5memory / OpenTM2 URL configuration is now only possible from CLI, not any more from UI. See issue for reason.
 


### Added
**[TRANSLATE-3794](https://jira.translate5.net/browse/TRANSLATE-3794): t5memory - Improve reimport tasks mechanism** <br>
New command is added for reimport task segments to TM
Added new button to the language resources UI for reimporting only updated segments

**[TRANSLATE-3748](https://jira.translate5.net/browse/TRANSLATE-3748): LanguageResources - TMX zip-import** <br>
Added support for zip uploads in t5memory resources.


### Changed
**[TRANSLATE-3812](https://jira.translate5.net/browse/TRANSLATE-3812): Import/Export - Make runtimeOptions.frontend.importTask.edit100PercentMatch affect the server side** <br>
The config runtimeOptions.frontend.importTask.edit100PercentMatch is renamed to runtimeOptions.import.edit100PercentMatch and affects now API imports too. Previously this was always false for API imports.


### Bugfixes
**[TRANSLATE-3810](https://jira.translate5.net/browse/TRANSLATE-3810): Editor general - Reorganize tm can save status of internal fuzzy memory** <br>
FIx for a problem with the memory name for t5memory language resources.

**[TRANSLATE-3805](https://jira.translate5.net/browse/TRANSLATE-3805): Editor general - RootCause error: Cannot read properties of null (reading 'NEXTeditable')** <br>
Fix for UI error when saving segment and there are no available next segments in the workflow.

**[TRANSLATE-3804](https://jira.translate5.net/browse/TRANSLATE-3804): Editor general - RootCause: Cannot read properties of null (reading 'items')** <br>
DEBUG: more info about the problem will be captured for further investigation once it happen next time

**[TRANSLATE-3799](https://jira.translate5.net/browse/TRANSLATE-3799): t5memory - Segment check after update in t5memory doesn't work properly with escaped symbols** <br>
Fixed check if segment was updated properly in t5memory

**[TRANSLATE-3798](https://jira.translate5.net/browse/TRANSLATE-3798): Editor general - RootCause: Failed to execute 'setAttribute' on 'Element': 'vorlage,' is not a valid attribute name.** <br>
Added more detailed logging of such cases for further investigation

**[TRANSLATE-3797](https://jira.translate5.net/browse/TRANSLATE-3797): Editor general - Do not run CLI cron jobs with active maintenance** <br>
Scheduled cron jobs via CLI may not run when maintenance is enabled.

**[TRANSLATE-3796](https://jira.translate5.net/browse/TRANSLATE-3796): t5memory - Fix t5memory migration command** <br>
Fix cleaning config value in t5memory:migrate command

**[TRANSLATE-3793](https://jira.translate5.net/browse/TRANSLATE-3793): LanguageResources - change date format in file name of resource usage export** <br>
Fix date-format in excel-export zip-file-names to become the standard "Y-m-d"

**[TRANSLATE-3789](https://jira.translate5.net/browse/TRANSLATE-3789): VisualReview / VisualTranslation - Remove "Max number of layout errors" from visual, just "warn" from a certain thresh on, that errors happened** <br>
Visual: Tasks are imported, even if the thresh of layout-errors is exceeded; Only a warning will be added in such cases

**[TRANSLATE-3782](https://jira.translate5.net/browse/TRANSLATE-3782): Repetition editor - repetions editor target text not shown** <br>
FIXED: target tests were not visible in repetitions editor

**[TRANSLATE-3758](https://jira.translate5.net/browse/TRANSLATE-3758): Configuration - move config edit100PercentMatch to client level** <br>
Attention: See als TRANSLATE-3812! The config runtimeOptions.frontend.importTask.edit100PercentMatch is renamed to runtimeOptions.import.edit100PercentMatch and affects now API imports too, and can be set on client level.

**[TRANSLATE-3755](https://jira.translate5.net/browse/TRANSLATE-3755): Main back-end mechanisms (Worker, Logging, etc.) - PHP E_ERROR: Uncaught TypeError: gzdeflate(): Argument #1 ($data) must be of type string, bool given** <br>
FIXED: if error happens on json-encoding events to be logged - is now POSTed to logger instead

**[TRANSLATE-3741](https://jira.translate5.net/browse/TRANSLATE-3741): Import/Export - Pricing scheme selected on client level is not respected for projects coming over the hotfolder** <br>
Fix Task creation process. Provide pricing preset from Client config

**[TRANSLATE-3670](https://jira.translate5.net/browse/TRANSLATE-3670): Editor general - Task custom fields label should be required** <br>
Label field is not required when creating new custom field.


## [7.2.1] - 2024-03-07

### Important Notes:
 


### Added
**[TRANSLATE-3752](https://jira.translate5.net/browse/TRANSLATE-3752): Editor general - Only display TM matches above a minimum match rate** <br>
Added new config for translation memory matches below the configured match rate will not be shown in the fuzzy match panel.


### Changed
**[TRANSLATE-3771](https://jira.translate5.net/browse/TRANSLATE-3771): Editor general, usability editor - Highlight better the actual error in the right panel** <br>
UI improvements in the QA overview of an opened segment in the editor.


### Bugfixes
**[TRANSLATE-3788](https://jira.translate5.net/browse/TRANSLATE-3788): User Management - change Mrs. to Ms. in user salutation** <br>
fix wrong English translation in the UI

**[TRANSLATE-3783](https://jira.translate5.net/browse/TRANSLATE-3783): t5memory - Fix sending save2disk parameter to t5memory** <br>
t5memory did not properly store saved segments on disk due a wrong flag send by translate5.

**[TRANSLATE-3779](https://jira.translate5.net/browse/TRANSLATE-3779): TermPortal - RootCause: [PromiseRejectionEvent] Ext.route.Router.onRouteRejection()** <br>
FIXED: javascript error popping when no default languages are configured for TermPortal

**[TRANSLATE-3778](https://jira.translate5.net/browse/TRANSLATE-3778): TermPortal - RootCause: Cannot read properties of null (reading 'setAttribute')** <br>
FIXED: UI problem with tooltips

**[TRANSLATE-3777](https://jira.translate5.net/browse/TRANSLATE-3777): Task Management - RootCause: Cannot read properties of undefined (reading 'taskCustomField')** <br>
FIXED: tooltip problem for custom field roles checkboxes group

**[TRANSLATE-3763](https://jira.translate5.net/browse/TRANSLATE-3763): Editor general - RootCause: Cannot read properties of null (reading 'style')** <br>
Fix a UI problem in the task/project add window.

**[TRANSLATE-3744](https://jira.translate5.net/browse/TRANSLATE-3744): Editor general - Task events entity load** <br>
Fix for entity loading in the task events API endpoint.

**[TRANSLATE-3663](https://jira.translate5.net/browse/TRANSLATE-3663): Editor general - Make custom fields editable** <br>
Defined custom field values for a task, can be edited.

**[TRANSLATE-3420](https://jira.translate5.net/browse/TRANSLATE-3420): Import/Export - SDLxliff corrupt after export, if imported untranslated into translate5 and containing internal tags of type locked** <br>
Exported files with locked tags producing errors on re-import.


## [7.2.0] - 2024-03-04

### Important Notes:
#### [TRANSLATE-3780](https://jira.translate5.net/browse/TRANSLATE-3780)
In addition to changing the default instance level value to disabled for the "runtimeOptions.editor.frontend.reviewTask.useSourceForReference" config, also the customer specific overwrites for this config will be set to disabled.

#### [TRANSLATE-3554](https://jira.translate5.net/browse/TRANSLATE-3554)
The visual enhancements works only with the latest visualconverter image (translate5/visualconverter:0.6). Using an older converter will lead to constant failure!
 


### Changed
**[TRANSLATE-3764](https://jira.translate5.net/browse/TRANSLATE-3764): InstantTranslate - make runtimeOptions.InstantTranslate.user.defaultLanguages possible in UI** <br>
Default selected languages for instant translate are configurable.

**[TRANSLATE-3759](https://jira.translate5.net/browse/TRANSLATE-3759): ConnectWorldserver - Failing Test MittagQI\Translate5\Plugins\ConnectWorldserver\tests\ExternalOnlineReviewTest::testCreateTaskFromWorldserverTestdata** <br>
Bugfix failing test

**[TRANSLATE-3757](https://jira.translate5.net/browse/TRANSLATE-3757): Editor general - New documentation links** <br>
Add new documentation links.

**[TRANSLATE-3554](https://jira.translate5.net/browse/TRANSLATE-3554): VisualReview / VisualTranslation - Enhancements for visual as ordered by translate5 Consortium** <br>
Visual: Improved the Text-Reflow of the WYSIWYG Visual (right frame) to:
* detect sequences of text as justified, right/left aligned and centered paragraphs
* avoid lost segments due to changed text-order
* improve detection & rendering of lists
* avoid overlapping elements in the frontend
* improve handling of superflous whitespace from the segments


### Bugfixes
**[TRANSLATE-3780](https://jira.translate5.net/browse/TRANSLATE-3780): Trados integration - Change default of runtimeOptions.editor.frontend.reviewTask.useSourceForReference back to "Disabled"** <br>
Revoke the default value for the "runtimeOptions.editor.frontend.reviewTask.useSourceForReference" config back to disabled.

**[TRANSLATE-3773](https://jira.translate5.net/browse/TRANSLATE-3773): Editor general - Task action menu error** <br>
Fix for UI error where the task action menu was not up to date with the task.

**[TRANSLATE-3767](https://jira.translate5.net/browse/TRANSLATE-3767): Editor general - UI error when Tag-Checking** <br>
Fix improper use of ExtJS-API

**[TRANSLATE-3762](https://jira.translate5.net/browse/TRANSLATE-3762): Task Management - RootCause: Cannot read properties of null (reading 'items')** <br>
FIXED: error popping on frequent subsequent clicks on task menu icon

**[TRANSLATE-3755](https://jira.translate5.net/browse/TRANSLATE-3755): Main back-end mechanisms (Worker, Logging, etc.) - PHP E_ERROR: Uncaught TypeError: gzdeflate(): Argument #1 ($data) must be of type string, bool given** <br>
FIXED: if error happens on json-encoding events to be logged - is now POSTed to logger instead

**[TRANSLATE-3754](https://jira.translate5.net/browse/TRANSLATE-3754): LanguageResources - Fix tag handling in taking over matches from matchresource panel** <br>
When taking over matches from the matchpanel tag order of the source is applied to the target.

**[TRANSLATE-3751](https://jira.translate5.net/browse/TRANSLATE-3751): Editor general - Reduce log level for not found errors** <br>
Reduce log level of multiple errors.

**[TRANSLATE-3745](https://jira.translate5.net/browse/TRANSLATE-3745): t5memory - Querying segments with flipped tags between source and target does not work** <br>
When dealing with segments where the tag order has changed between source and target, the order of tags was saved wrong and restored wrong from t5memory when re-using such a segment. 

**[TRANSLATE-3735](https://jira.translate5.net/browse/TRANSLATE-3735): Editor general - Manual QA complete segment not editable, if segment is opened for editing** <br>
FIXED: Manual QA was disabled when segment opened

**[TRANSLATE-3697](https://jira.translate5.net/browse/TRANSLATE-3697): InstantTranslate - Missing whitespaces in InstantTranslate** <br>
Fix wrong newline conversion


## [7.1.4] - 2024-02-23

### Important Notes:
#### [TRANSLATE-3736](https://jira.translate5.net/browse/TRANSLATE-3736)
Fix or UI error when displaying tag errors.

#### [TRANSLATE-3716](https://jira.translate5.net/browse/TRANSLATE-3716)
By default single tags on the start and end of a segment will now be imported. See runtimeOptions.import.xlf.ignoreFramingTags

#### [TRANSLATE-3591](https://jira.translate5.net/browse/TRANSLATE-3591)
This is a backwards incompatible feature. If you need the old behavior, change the config value of the option runtimeOptions.LanguageResources.enableMtForNonUntranslatedSegments.
Please note: If you turn this option on again in the config, again for each segment opening costs at the MT will be generated.
 


### Bugfixes
**[TRANSLATE-3750](https://jira.translate5.net/browse/TRANSLATE-3750): t5memory - Fix deletion of TMs on fuzzy TM errors** <br>
In very rare cases TMs in t5memory get deleted.

**[TRANSLATE-3747](https://jira.translate5.net/browse/TRANSLATE-3747): Import/Export - Extend Placeables to also inspect contents of <ph> & <it> tags** <br>
Improve Placeables: scan the contents of <ph> and <it> tags instead of the tags

**[TRANSLATE-3743](https://jira.translate5.net/browse/TRANSLATE-3743): LanguageResources - Changes in the OpenAI API lead to errors when training a model** <br>
Updating OpenAI lib

**[TRANSLATE-3742](https://jira.translate5.net/browse/TRANSLATE-3742): t5memory - Fix resetting reorganize attempts** <br>
Fix error while saving segment to t5memory

**[TRANSLATE-3737](https://jira.translate5.net/browse/TRANSLATE-3737): SpellCheck (LanguageTool integration) - Warning instead of error when the target language is not supported by the spell checker** <br>
Warning instead of error when the target language is not supported by the spell checker.

**[TRANSLATE-3736](https://jira.translate5.net/browse/TRANSLATE-3736): Editor general - RootCause error: tagData is undefined** <br>
Fix for a problem when displaying tag errors popup.

**[TRANSLATE-3734](https://jira.translate5.net/browse/TRANSLATE-3734): Editor general - Reconnect and closed websocket connections** <br>
Fix for message bus reconnecting when connection is lost.

**[TRANSLATE-3732](https://jira.translate5.net/browse/TRANSLATE-3732): MatchAnalysis & Pretranslation - RootCause: Cannot read properties of null (reading 'getMetadata')** <br>
Fix for UI error when analysis load returns not results

**[TRANSLATE-3730](https://jira.translate5.net/browse/TRANSLATE-3730): Import/Export - across hotfolder bug fixing** <br>
Several smaller fixes in instruction.xml evaluation regarding the PM to be used.

**[TRANSLATE-3716](https://jira.translate5.net/browse/TRANSLATE-3716): Import/Export - Change default for runtimeOptions.import.xlf.ignoreFramingTags to "paired"** <br>
It often leads to problems for users, who do not know translate5 well enough, that the default setting for runtimeOptions.import.xlf.ignoreFramingTags is "all".
Because in some import formats there are stand-alone tags, that stand for words, and with "all" they would be excluded from the segment and miss as info for the translator and can not be moved with the text inside the segment.
Therefore the default is changed to runtimeOptions.import.xlf.ignoreFramingTags = "paired"

**[TRANSLATE-3690](https://jira.translate5.net/browse/TRANSLATE-3690): Workflows - workflow starts with "view only"** <br>
Fix for a problem where the initial task workflow step is set to a wrong value when we have default assigned user with workflow role "view only".

**[TRANSLATE-3679](https://jira.translate5.net/browse/TRANSLATE-3679): LanguageResources, Task Management - deselecting language resources in task creation wizard not saved** <br>
Fix for a problem where the resources association grid was not updated after task creating in the project overview.

**[TRANSLATE-3591](https://jira.translate5.net/browse/TRANSLATE-3591): Editor general - Only query MT in fuzzy panel of editor, if segment untranslated** <br>
So far with each opening of a segment, all match resources are queried.

In the future this should only happen for MT resources, if the segment is in the segment status "untranslated".

The old behavior can be turned on again by a new config options, overwritable on client, import and task level. It's name needs to be specified in the important release notes of this issue.


## [7.1.3] - 2024-02-14

### Important Notes:
#### [TRANSLATE-3404](https://jira.translate5.net/browse/TRANSLATE-3404)
t5memory v0.5 requires version >0.5.59
 


### Changed
**[TRANSLATE-3692](https://jira.translate5.net/browse/TRANSLATE-3692): TermPortal - Log deleted terms** <br>
Implement logging when term is deleted from a term collection.

**[TRANSLATE-3404](https://jira.translate5.net/browse/TRANSLATE-3404): t5memory - Change t5memory reorganize call to async** <br>
translate5 - 7.1.2: Added support of t5memory v0.5.x 
translate5 - 7.1.3: Provide fixes for migration command


### Bugfixes
**[TRANSLATE-3726](https://jira.translate5.net/browse/TRANSLATE-3726): Editor general - Commenting on segment does not update the progress** <br>
Fix for a problem when no workflow progress was registered when commenting on a segment.

**[TRANSLATE-3704](https://jira.translate5.net/browse/TRANSLATE-3704): LanguageResources - Increase timeout for requests to TildeMT to 1 min** <br>
Request timeout for TildeMT increased to 60 seconds


## [7.1.2] - 2024-02-09

### Important Notes:
#### [TRANSLATE-3404](https://jira.translate5.net/browse/TRANSLATE-3404)
t5memory v0.5 requires version >0.5.59
 


### Changed
**[TRANSLATE-3692](https://jira.translate5.net/browse/TRANSLATE-3692): TermPortal - Log deleted terms** <br>
Implement logging when term is deleted from a term collection.

**[TRANSLATE-3404](https://jira.translate5.net/browse/TRANSLATE-3404): t5memory - Change t5memory reorganize call to async** <br>
Added support of t5memory v0.5.x 

**[TRANSLATE-3332](https://jira.translate5.net/browse/TRANSLATE-3332): SpellCheck (LanguageTool integration) - New error type for languageTool** <br>
'Numbers'-category errors detected by SpellCheck's LanguageTool are now counted and shown in the left AutoQA panel


### Bugfixes
**[TRANSLATE-3706](https://jira.translate5.net/browse/TRANSLATE-3706): MatchAnalysis & Pretranslation - Pretranslation choses match with same matchrate independent of age** <br>
Use the newer TM match-rate in case there are more than 100% or greater match-rates.

**[TRANSLATE-3703](https://jira.translate5.net/browse/TRANSLATE-3703): file format settings - custom file extension for file filter not recognized and no UI error message** <br>
FIXED: Backend rejected file-type although matching file-format was set in the frontend

**[TRANSLATE-3698](https://jira.translate5.net/browse/TRANSLATE-3698): Editor general - User info command line tool error** <br>
Fix for user info command line tool.

**[TRANSLATE-3696](https://jira.translate5.net/browse/TRANSLATE-3696): Task Management - RootCause: Cannot read properties of null (reading 'get')** <br>
FIXED: task user special properties were not addable

**[TRANSLATE-3693](https://jira.translate5.net/browse/TRANSLATE-3693): Configuration - Custom field with regex UI validation stops project creation wizard** <br>
Fix for a custom field validations in project creation wizard

**[TRANSLATE-3689](https://jira.translate5.net/browse/TRANSLATE-3689): Editor general - No record in task action menu** <br>
Improve the task detection when action menu is created.

**[TRANSLATE-3688](https://jira.translate5.net/browse/TRANSLATE-3688): Client management - Advanced filters in task overview are not saved** <br>
FIXED: advanced filters are now saved as well

**[TRANSLATE-3687](https://jira.translate5.net/browse/TRANSLATE-3687): Editor general - RootCause error: record is undefined** <br>
Fix for UI error when trying to update edited segments in visual review layout.

**[TRANSLATE-3656](https://jira.translate5.net/browse/TRANSLATE-3656): Import/Export - Buttons do not work in project wizard, if moved to "burger" menu** <br>
FIXED: overflow-menu buttons not working in the 'User assignment defaults' step of project wizard

**[TRANSLATE-3613](https://jira.translate5.net/browse/TRANSLATE-3613): Editor general - Message on "no more segments in workflow" misleading** <br>
FIXED: misleading messages when editing inside filtered segments grid is reached top or bottom

**[TRANSLATE-3604](https://jira.translate5.net/browse/TRANSLATE-3604): Auto-QA - Consistency quality** <br>
FIXED: wrong translation for 'Inconsistent target' AutoQA label

**[TRANSLATE-3587](https://jira.translate5.net/browse/TRANSLATE-3587): Import/Export - navigation through fields in task creation wizard** <br>
FIXED: tabbable fields problem while mask is shown in project wizard

**[TRANSLATE-3568](https://jira.translate5.net/browse/TRANSLATE-3568): InstantTranslate - DeepL swallos full stop between sentences** <br>
Text was re-segmented if source language had to be auto-detected

**[TRANSLATE-3466](https://jira.translate5.net/browse/TRANSLATE-3466): Import/Export - TBX-import: reduce log data during import** <br>
Reduced logs for E1472 and E1446 so that total quantity of occurrences happened during import is logged once per event type, instead of logging each occurrence individually


## [7.1.1] - 2024-02-02

### Important Notes:
 


### Changed
**[TRANSLATE-3654](https://jira.translate5.net/browse/TRANSLATE-3654): t5memory - Improve t5memory status response handling** <br>
Improve t5memory status response handling

**[TRANSLATE-3586](https://jira.translate5.net/browse/TRANSLATE-3586): Editor general - Always show info icon in match rate panel** <br>
Info icon in the first column of a match-rate panel - is now always shown


### Bugfixes
**[TRANSLATE-3686](https://jira.translate5.net/browse/TRANSLATE-3686): Editor general - RootCause: Cannot read properties of null (reading 'forEach')** <br>
Fix for UI when loading qualities.

**[TRANSLATE-3685](https://jira.translate5.net/browse/TRANSLATE-3685): Auto-QA, Editor general - RootCause error: Cannot read properties of undefined (reading 'floating')** <br>
Fix for UI error when saving false positive with slow requests.

**[TRANSLATE-3684](https://jira.translate5.net/browse/TRANSLATE-3684): Editor general - RootCause error: resourceType is null** <br>
Fix for UI error when creating Language resources and selecting resource from the dropdown.

**[TRANSLATE-3682](https://jira.translate5.net/browse/TRANSLATE-3682): Editor general - RootCause error: Cannot read properties of null (reading 'get')** <br>
Fix for problem when selecting customer in task add wizard

**[TRANSLATE-3681](https://jira.translate5.net/browse/TRANSLATE-3681): Editor general - RootCause: Cannot read properties of null (reading 'getHtml')** <br>
Fix for UI error when filtering for qualities by clicking on the three. 

**[TRANSLATE-3680](https://jira.translate5.net/browse/TRANSLATE-3680): Client management - Action column in clients grid not resizeable** <br>
Fix for clients action column not resizable.

**[TRANSLATE-3678](https://jira.translate5.net/browse/TRANSLATE-3678): GroupShare integration, InstantTranslate - InstantTranslate does not use Groupshare TMs** <br>
Fix for a problem where group share results where not listed in instant translate.

**[TRANSLATE-3677](https://jira.translate5.net/browse/TRANSLATE-3677): Editor general - RootCause error: this.getMarkupImage is not a function** <br>
Fix for UI error when changing editor view modes.

**[TRANSLATE-3674](https://jira.translate5.net/browse/TRANSLATE-3674): Editor general - RootCause error: Cannot read properties of null (reading 'dom')** <br>
Fix for UI error when displaying tooltip in editor.

**[TRANSLATE-3673](https://jira.translate5.net/browse/TRANSLATE-3673): Editor general - FIX "Cannot read properties of undefined" from markup-decoration lib / Placeables** <br>
FIX potential JavaScript Error when decorating segments for SpellCheck

**[TRANSLATE-3671](https://jira.translate5.net/browse/TRANSLATE-3671): Client management - Dropdown "Client" does not work anymore after TRANSLATE-2276** <br>
Fix for global customer filter not working for tasks and resources.

**[TRANSLATE-3651](https://jira.translate5.net/browse/TRANSLATE-3651): MatchAnalysis & Pretranslation - Some segments are not pre-translated, although 100% matches exist in the TM** <br>
Fix pretranslation for repetitions

**[TRANSLATE-3642](https://jira.translate5.net/browse/TRANSLATE-3642): Auto-QA, Editor general - change default for tag check reference field to source** <br>
Changed default value for useSourceForReference config to 'Activated'

**[TRANSLATE-3641](https://jira.translate5.net/browse/TRANSLATE-3641): Repetition editor - Repetitions editor: Activate/deactivate target repetitions** <br>
Added ability to define whether target-only repetitions should be excluded from the default pre-selection in repetition editor

**[TRANSLATE-3623](https://jira.translate5.net/browse/TRANSLATE-3623): TermPortal - batch edit in term collection will lead to error value in termID is invalid** <br>
Fix for a problem when batch editing in term portal.

**[TRANSLATE-3217](https://jira.translate5.net/browse/TRANSLATE-3217): Editor general - RootCause: Invalid JSON - answer seems not to be from translate5 - x-translate5-version header is missing** <br>
5.9.0: added some debug code.
7.1.1: additional debugging code


## [7.1.0] - 2024-01-19

### Important Notes:
#### [TRANSLATE-3483](https://jira.translate5.net/browse/TRANSLATE-3483)
Defining and changing field can be high resource usage. Please do it with coordination with the translate5 support team.
 


### Added
**[TRANSLATE-3650](https://jira.translate5.net/browse/TRANSLATE-3650): Editor general - Special characters listed for all languages** <br>
Can be defined special characters in the editor to be available for all languages.

**[TRANSLATE-3533](https://jira.translate5.net/browse/TRANSLATE-3533): Import/Export, VisualReview / VisualTranslation - Placeables in translate5** <br>
Added capabilities to identify Placeables in xliff-tags. Placeables are single internal tags that will be shown with their text-content instead as tag. For identification XPaths have to be defined in the configuration.

**[TRANSLATE-3483](https://jira.translate5.net/browse/TRANSLATE-3483): Task Management - Custom project/task meta data fields** <br>
New feature where custom fields can be defined for a task.

**[TRANSLATE-2276](https://jira.translate5.net/browse/TRANSLATE-2276): Client management, LanguageResources, Task Management, User Management - Save customization of project, task, language resource, user and client management** <br>
Columns in main grids do now remember their order, visibility, sorting and filtering


### Changed
**[TRANSLATE-3636](https://jira.translate5.net/browse/TRANSLATE-3636): Auto-QA - FIX Quality Decorations in Segment Grid** <br>
FIX: Spellcheck decorations may have wrong positions and/or wrong Segment-Text in right-click layer in the segment-grid

**[TRANSLATE-3622](https://jira.translate5.net/browse/TRANSLATE-3622): Main back-end mechanisms (Worker, Logging, etc.) - Zip and upload data-directory to Indi Engine logger after pipeline completion** <br>
Translate5 instance logger improvements.


### Bugfixes
**[TRANSLATE-3669](https://jira.translate5.net/browse/TRANSLATE-3669): TBX-Import - Cross API connector was not working on php 8.1 due class loading problems** <br>
The Across TBX Import was not working anymore with php 8.1

**[TRANSLATE-3664](https://jira.translate5.net/browse/TRANSLATE-3664): sso - Missing header in proxy config** <br>
For https request the http host was set with wrong value leading SSO customers to be not detected based on the domain.

**[TRANSLATE-3662](https://jira.translate5.net/browse/TRANSLATE-3662): LanguageResources - Dictionary search language support** <br>
Check for dictionary supported languages before searching for result.

**[TRANSLATE-3661](https://jira.translate5.net/browse/TRANSLATE-3661): Okapi integration - Okapi config allows deletion of okapi instances even if in use** <br>
Okapi servers being in use by several tasks could be deleted over the UI, this is prevented now.

**[TRANSLATE-3658](https://jira.translate5.net/browse/TRANSLATE-3658): file format settings - File formats: Make format-check in the import-wizard dynamic** <br>
FIX: Check of added workfiles did not respect the extension-mapping of the selected bconf

**[TRANSLATE-3653](https://jira.translate5.net/browse/TRANSLATE-3653): t5memory - t5memory TMX Upload does not work anymore** <br>
The TMX upload was not working anymore in hosted environments

**[TRANSLATE-3652](https://jira.translate5.net/browse/TRANSLATE-3652): Import/Export - Remove wrong SRX rule from all languages** <br>
Remove erroneus SRX-rule from translate5 default File-format settings (BCONF)

**[TRANSLATE-3640](https://jira.translate5.net/browse/TRANSLATE-3640): Client management - email link to task not working for clients with own domain** <br>
Fix for translate5 url in email templates.

**[TRANSLATE-3635](https://jira.translate5.net/browse/TRANSLATE-3635): Auto-QA, Editor general - Usage of "target at import time" as source for tags: Only for bilingual tasks** <br>
Target at import time is considered to be a reference field for checking tags only for files where we did directly get the bilingual files in the import

**[TRANSLATE-3633](https://jira.translate5.net/browse/TRANSLATE-3633): VisualReview / VisualTranslation - Visual: Order of merged PDFs random** <br>
FIX: When merging PDFs for a Visual, the order of Files is now sorted by name

**[TRANSLATE-3617](https://jira.translate5.net/browse/TRANSLATE-3617): Editor general - Help button is not visible in editor** <br>
Fix for help button not visible in editor overview.



## [7.0.1] - 2024-01-08

### Important Notes:


### Changed
**[TRANSLATE-3632](https://jira.translate5.net/browse/TRANSLATE-3632): t5memory - Log if segment is not saved  to TM** <br>
Add check if a segment was updated properly in t5memory and if not - log that for debug purposes

**[TRANSLATE-3629](https://jira.translate5.net/browse/TRANSLATE-3629): Package Ex and Re-Import - Translator package import: Move checkbox for "save to TM" from upload window to sysconfig** <br>
Write segments to TM on package re-import is now configurable on customer and task level and is not available any more as separate checkbox on re-import dialogue.


### Bugfixes
**[TRANSLATE-3639](https://jira.translate5.net/browse/TRANSLATE-3639): Auto-QA, MatchAnalysis & Pretranslation - Inserted fuzzy should not write into "target at import time" field** <br>
Target text (at time of import / pretranslation) is now not updated anymore when applying match from translation memory match (was erroneously introduced in 7.0.0)

**[TRANSLATE-3638](https://jira.translate5.net/browse/TRANSLATE-3638): Auto-QA, TrackChanges - Tags checker doesn't ignore deleted tags** <br>
Fix bug when deleted tags weren't ignored during tags validation

**[TRANSLATE-3614](https://jira.translate5.net/browse/TRANSLATE-3614): InstantTranslate - TM match in instant translate ignored** <br>
Fix for translating segmented text in instant-translate so that more results come from TMs if assigned.


## [7.0.0] - 2023-12-19

### Important Notes:
#### [TRANSLATE-3436](https://jira.translate5.net/browse/TRANSLATE-3436)
To update to this version PHP 8.1.23 is required.
 


### Added
**[TRANSLATE-3436](https://jira.translate5.net/browse/TRANSLATE-3436): LanguageResources - Integrate GPT-4 with translate5 as translation engine** <br>
New Private Plugin "OpenAI" to use OpenAI-Models as language-resource and base functionality to fine-tune these models


### Bugfixes
**[TRANSLATE-3627](https://jira.translate5.net/browse/TRANSLATE-3627): Main back-end mechanisms (Worker, Logging, etc.) - HOTFIX: Progress reporting of Looped Segment Processing Workers does not work** <br>
FIX: progress of termtagger and spellcheck workers was not properly reported to GUI

**[TRANSLATE-3624](https://jira.translate5.net/browse/TRANSLATE-3624): InstantTranslate - Instant Translate will find no en-us terms** <br>
Fix: list all regional language results from term collections when searching with the main language code 

**[TRANSLATE-3590](https://jira.translate5.net/browse/TRANSLATE-3590): Main back-end mechanisms (Worker, Logging, etc.) - Create Globally usable API-request to replace usage of InstantTranslate in various places** <br>
Code cleanup: Centralize API-request from InsrtantTranslate as base-code


## [6.9.1] - 2023-12-18

### Important Notes:
 


### Added
**[TRANSLATE-3553](https://jira.translate5.net/browse/TRANSLATE-3553): TermPortal - Extend folder-based term import to work via sftp** <br>
translate5 - 6.9.0: Added support for terminology import from remote SFTP directory
translate5 - 6.9.1: Added additional config value check


### Bugfixes
**[TRANSLATE-3626](https://jira.translate5.net/browse/TRANSLATE-3626): t5memory - Write to instant translate t5memory memory** <br>
Fix for writing to instant-translate memory.

**[TRANSLATE-3619](https://jira.translate5.net/browse/TRANSLATE-3619): Editor general - SQL error when filtering repetitions with bookmarks** <br>
FIXED: sql-error when both bookbarks and repetiions filters are used

**[TRANSLATE-3419](https://jira.translate5.net/browse/TRANSLATE-3419): Task Management - Click on PM name in project overview opens mail with undefined address - and logs out user in certain cases** <br>
translate5 - 6.7.0: FIXED: 'mailto:undefined' links in PM names in Project overview
translate5 - 6.9.1: project task grid fix


## [6.9.0] - 2023-12-14

### Important Notes:
#### [TRANSLATE-3561](https://jira.translate5.net/browse/TRANSLATE-3561)
These changes will work properly only with t5memory after version 0.4.1056
 


### Added
**[TRANSLATE-3553](https://jira.translate5.net/browse/TRANSLATE-3553): TermPortal - Extend folder-based term import to work via sftp** <br>
Added support for terminology import from remote SFTP directory

**[TRANSLATE-3550](https://jira.translate5.net/browse/TRANSLATE-3550): sso - Add client field for IdP and SSO** <br>
Added new config to define customer number field in SSO claims.


### Changed
**[TRANSLATE-3582](https://jira.translate5.net/browse/TRANSLATE-3582): Editor general - Change behavior of reference field in editor** <br>
Reference field for tags validation is now considered to be "target at import time" not only if task is a review task, but also if "target at import time" contains some data.

**[TRANSLATE-3580](https://jira.translate5.net/browse/TRANSLATE-3580): LanguageResources - Remove NecTm plugin** <br>
NecTm plugin removed as deprecated

**[TRANSLATE-3561](https://jira.translate5.net/browse/TRANSLATE-3561): t5memory - Enable t5memory connector to load balance big TMs** <br>
translate5 - 6.8.0: Fix overflow error when importing very big files into t5memory by splitting the TM internally.
translate5 - 6.8.2: Fix for data tooltip


### Bugfixes
**[TRANSLATE-3612](https://jira.translate5.net/browse/TRANSLATE-3612): Main back-end mechanisms (Worker, Logging, etc.) - Password reset does not work** <br>
Fix for password reset

**[TRANSLATE-3609](https://jira.translate5.net/browse/TRANSLATE-3609): SpellCheck (LanguageTool integration), TermTagger integration - Detect horizonal scaling also behind single pool URL for a pooled service** <br>
Ebnable better horizonlal scaling for singular pool URLs for TermTagger & LanguageTool

**[TRANSLATE-3607](https://jira.translate5.net/browse/TRANSLATE-3607): t5memory - Non ASCII characters in document name leads to an error in t5memory** <br>
Fix for problem when segments are updated in t5memory and the response did contains non ASCII characters.

**[TRANSLATE-3598](https://jira.translate5.net/browse/TRANSLATE-3598): Editor general - Fix PHP 8.1 warnings** <br>
Fix several PHP 8.1 warnings

**[TRANSLATE-3597](https://jira.translate5.net/browse/TRANSLATE-3597): Editor general - Fix PHP 8.1 warnings** <br>
Fix several PHP 8.1 warnings

**[TRANSLATE-3596](https://jira.translate5.net/browse/TRANSLATE-3596): Editor general - Fix PHP 8.1 warnings** <br>
Fix several PHP 8.1 warnings

**[TRANSLATE-3595](https://jira.translate5.net/browse/TRANSLATE-3595): User Management - client PM can change password for admin users** <br>
FIX: client restricted PMs could edit user's with elevated roles

**[TRANSLATE-3584](https://jira.translate5.net/browse/TRANSLATE-3584): Configuration - Implement a outbound proxy config** <br>
In hosted environments it might be necessary to route the outgoing traffic (visual downloads or similar) over a configurable proxy.

**[TRANSLATE-3576](https://jira.translate5.net/browse/TRANSLATE-3576): LanguageResources - Microsoft and google language mapper problem** <br>
Fix for a problem with wrong language codes in google and microsoft resource.

**[TRANSLATE-3414](https://jira.translate5.net/browse/TRANSLATE-3414): Import/Export - sdlxliff comments produce several problems** <br>
Fix problem where sdlxliff comment are not correctly processed on import and export.

**[TRANSLATE-3284](https://jira.translate5.net/browse/TRANSLATE-3284): Task Management - Tasks in "competetive mode" get accepted automatically** <br>
FIXED: tasks are now not being auto-accepted when auto-opened after login


## [6.8.1] - 2023-12-07

### Important Notes:
 


### Changed
**[TRANSLATE-3608](https://jira.translate5.net/browse/TRANSLATE-3608): Configuration - Improve edit 100% matches config desciption** <br>
Improvement in 100% matches config (runtimeOptions.frontend.importTask.edit100PercentMatch) description.


### Bugfixes
**[TRANSLATE-3610](https://jira.translate5.net/browse/TRANSLATE-3610): Main back-end mechanisms (Worker, Logging, etc.) - FIX bug in Sanitization with empty params** <br>
FIX: Possible unneccessary exception when sanitizing params

**[TRANSLATE-3606](https://jira.translate5.net/browse/TRANSLATE-3606): Main back-end mechanisms (Worker, Logging, etc.), User Management - Session API authentication combined with apptokens leads to beeing the wrong user** <br>
FIX authentication via POST on the session-controller, where elevated credentials were delivered when called with an App-Token

**[TRANSLATE-3605](https://jira.translate5.net/browse/TRANSLATE-3605): LanguageResources - TM button for associated tasks missing** <br>
Fix problem where the TM button for associated tasks was not visible in resources overview.


## [6.8.0] - 2023-12-05

### Important Notes:
#### [TRANSLATE-3561](https://jira.translate5.net/browse/TRANSLATE-3561)
These changes will work properly only with t5memory after version 0.4.1056
 


### Changed
**[TRANSLATE-3561](https://jira.translate5.net/browse/TRANSLATE-3561): t5memory - Enable t5memory connector to load balance big TMs** <br>
Fix overflow error when importing very big files into t5memory by splitting the TM internally.

**[TRANSLATE-3537](https://jira.translate5.net/browse/TRANSLATE-3537): Import/Export - Process comments from xliff 1.2 files** <br>
XLF comments placed in note tags are now also imported and exported as task comments. The behavior is configurable.


### Bugfixes
**[TRANSLATE-3601](https://jira.translate5.net/browse/TRANSLATE-3601): VisualReview / VisualTranslation - Change default for processing of invisible texts in PDF converter in Visual** <br>
Changed default for processing of invisible text in the visual (Text visibility correction) to fix only fully occluded text


## [6.7.3] - 2023-12-01

### Important Notes:
 


### Added
**[TRANSLATE-3548](https://jira.translate5.net/browse/TRANSLATE-3548): Okapi integration - Show xml-tags configured as 'translate="no"' in format conversion as protected segments in translate5** <br>
Main bulk of this feature is Okapi development and to use it Okapi 1.46 is needed. It allows to configure xml tags in Okapi as 'translate="no"', but still show them as locked segments in translate5.


### Bugfixes
**[TRANSLATE-3588](https://jira.translate5.net/browse/TRANSLATE-3588): Editor general, VisualReview / VisualTranslation - Cleaning up the visual public symlinks does not work** <br>
FIX: cleanup for visual public URL symbolic links


## [6.7.2] - 2023-11-28

### Important Notes:
#### [TRANSLATE-3571](https://jira.translate5.net/browse/TRANSLATE-3571)
The import and task finish callbacks are now sending the proper content-type application/json; charset=utf-8 - what must be supported by your endpoint if using that feature.
 


### Changed
**[TRANSLATE-3562](https://jira.translate5.net/browse/TRANSLATE-3562): LanguageResources - Make name of TildeMT configurable in system configuration** <br>
TildeMT service name now can be configured

**[TRANSLATE-3547](https://jira.translate5.net/browse/TRANSLATE-3547): LanguageResources, t5memory - Change direct saving to tm to queue** <br>
If enabled segments in TMs will be updated asynchronously via queued worker (runtimeOptions.LanguageResources.tmQueuedUpdate)

**[TRANSLATE-3542](https://jira.translate5.net/browse/TRANSLATE-3542): Editor general - Enhance translate5 with more tooltips for better usability** <br>
Enhanced Translate5 and TermPortal tooltips

**[TRANSLATE-3421](https://jira.translate5.net/browse/TRANSLATE-3421): Main back-end mechanisms (Worker, Logging, etc.) - Organize test output and php errors from live instances based on Indi Engine** <br>
Internal improvements for automatic testing in development cycle


### Bugfixes
**[TRANSLATE-3583](https://jira.translate5.net/browse/TRANSLATE-3583): VisualReview / VisualTranslation - FIX Visual Image Test** <br>
Update Google libraries to solve API-test problems

**[TRANSLATE-3577](https://jira.translate5.net/browse/TRANSLATE-3577): Auto-QA - Missing DB indizes are leading to long running analysis** <br>
Due a missing DB index the analysis and pre-translation was taking to much time.

**[TRANSLATE-3573](https://jira.translate5.net/browse/TRANSLATE-3573): Main back-end mechanisms (Worker, Logging, etc.) - Fix start of task operations in case of exceptions** <br>
FIX: QA operation workers stay in database if start of task operation failed

**[TRANSLATE-3571](https://jira.translate5.net/browse/TRANSLATE-3571): API - Add missing content-type header in task import callback** <br>
The import callback was not sending a content-type, some callback implementations were not able to handle that.

**[TRANSLATE-3559](https://jira.translate5.net/browse/TRANSLATE-3559): Configuration, Test framework - Remove method \ZfExtended_Models_Config::loadListByNamePart** <br>
Remove a internal function using the system configuration in an incomplete way.

**[TRANSLATE-3496](https://jira.translate5.net/browse/TRANSLATE-3496): Main back-end mechanisms (Worker, Logging, etc.) - session code cleanup and performance improvement** <br>
translate5 - 6.7.0 
 * Loading performance of session data improved. (Step 1)
translate5 - 6.7.2
 * Storing session improved. (Step 2)



## [6.7.1] - 2023-11-08

### Important Notes:
 


### Bugfixes
**[TRANSLATE-3563](https://jira.translate5.net/browse/TRANSLATE-3563): Editor general - Fix plugin localized strings** <br>
Fix a problem in the plugin localizations preventing translate5 to be loaded after login.

**[TRANSLATE-3558](https://jira.translate5.net/browse/TRANSLATE-3558): InstantTranslate - InstantTranslate missing white space between 2 sentences in target** <br>
FIXED: Multiple sentences are now concatenated with whitespaces in-between.

**[TRANSLATE-3546](https://jira.translate5.net/browse/TRANSLATE-3546): Editor general, VisualReview / VisualTranslation - Editor user preferences not persistent, when task left in simple mode** <br>
FIXED: user preferences persistence on view mode change

**[TRANSLATE-1068](https://jira.translate5.net/browse/TRANSLATE-1068): API - Improve REST API on wrong usage** <br>
6.7.1: fix for special use case when authenticating against API session endpoint
6.7.0: API requests (expect file uploading requests) can now understand JSON in raw body, additionally to the encapsulated JSON in a data form field. Also a proper HTTP error code is sent when providing invalid JSON.


## [6.7.0] - 2023-11-03

### Important Notes:
#### [TRANSLATE-3526](https://jira.translate5.net/browse/TRANSLATE-3526)
Reminder Thomas: remove workaround in elasticcloud after deployment!

#### [TRANSLATE-3521](https://jira.translate5.net/browse/TRANSLATE-3521)
All config levels are resetted to there desired original value. So customizations as needed for example on demo installations are reset.

#### [TRANSLATE-1068](https://jira.translate5.net/browse/TRANSLATE-1068)
Update API documentation on release.
 


### Added
**[TRANSLATE-3549](https://jira.translate5.net/browse/TRANSLATE-3549): User Management - Delete users from list after set time** <br>
New feature that allows to automatically delete SSO users that have not logged in for a set period of time

**[TRANSLATE-3544](https://jira.translate5.net/browse/TRANSLATE-3544): InstantTranslate - add keyboard shortcut for "translate" in instant translate** <br>
If auto-translate is disabled in instant translate, users now can run translations with a keyboard shortcut (alt + enter).

**[TRANSLATE-3407](https://jira.translate5.net/browse/TRANSLATE-3407): Editor general, Repetition editor - Filter only repetitions except first** <br>
It's now possible to show repetitions excluding first occurrences of each repetition group


### Changed
**[TRANSLATE-3557](https://jira.translate5.net/browse/TRANSLATE-3557): t5memory - Improve fuzzy analysis speed** <br>
Internal fuzzy speeded up by omitting flushing memory on each segment update in t5memory

**[TRANSLATE-3555](https://jira.translate5.net/browse/TRANSLATE-3555): Task Management - Flexibilize task deletion and archiving trigger** <br>
Add flexibility to task auto-deletion. Now user can provide workflow statuses at which task will be archived and deleted from task list 

**[TRANSLATE-3531](https://jira.translate5.net/browse/TRANSLATE-3531): t5memory - Improve memory:migrate CLI command** <br>
For not exportable memories a create-empty option is added to the memory:migrate CLI command

**[TRANSLATE-3530](https://jira.translate5.net/browse/TRANSLATE-3530): ConnectWorldserver - Plugin ConnectWorldserver: add DueDate** <br>
Added DueDate for tasks created by plugin ConnectWorldserver

**[TRANSLATE-3520](https://jira.translate5.net/browse/TRANSLATE-3520): Translate5 CLI - Improve internal translation package creation** <br>
Implemented a CLI command to import the internal translations as translate5 task.

**[TRANSLATE-3418](https://jira.translate5.net/browse/TRANSLATE-3418): Main back-end mechanisms (Worker, Logging, etc.) - Make toast messages closeable, when clicking somewhere** <br>
Toast messages are now closeable

**[TRANSLATE-3364](https://jira.translate5.net/browse/TRANSLATE-3364): Editor general, SpellCheck (LanguageTool integration) - Show and take over correction proposals of spellcheck also by keyboard** <br>
Added CTRL+R shortcut for showing replacement suggestions when the cursor is inside an spellcheck-highlighted word in an open segment editor

**[TRANSLATE-3363](https://jira.translate5.net/browse/TRANSLATE-3363): Auto-QA, Editor general, SpellCheck (LanguageTool integration) - Keyboard short-cuts to set false positives** <br>
Added ability to use CTRL + ALT + DIGIT keyboard shortcut to toggle false positive flag on selected segment's qualities

**[TRANSLATE-3315](https://jira.translate5.net/browse/TRANSLATE-3315): Auto-QA - Enhance false positive pop-up, that appears on right-click on error** <br>
Enhanced the way of how false-positives  flag can be spreaded across similar AutoQA errors

**[TRANSLATE-3314](https://jira.translate5.net/browse/TRANSLATE-3314): Auto-QA - "Only errors" in AutoQA in the editor should be default setting** <br>
Default option in AutoQA dropdown at the top left is changed from 'Show all' to 'Only errors'


### Bugfixes
**[TRANSLATE-3556](https://jira.translate5.net/browse/TRANSLATE-3556): MatchAnalysis & Pretranslation - Timeout on segment view creation on large imports** <br>
Fixing a problem with the initializing of matchanalysis workers producing strange materialized view timeouts on larger imports.

**[TRANSLATE-3540](https://jira.translate5.net/browse/TRANSLATE-3540): Export - problems with excel ex and re import** <br>
Fixed that the task menu is updated directly after exporting a task as excel so that re-import button is shown without reloading the task overview.

**[TRANSLATE-3529](https://jira.translate5.net/browse/TRANSLATE-3529): InstantTranslate - writetm - Call to a member function getId() on null** <br>
Made the /instanttranslateapi/writetm endpoint more robust against missing or wrong data in request.

**[TRANSLATE-3527](https://jira.translate5.net/browse/TRANSLATE-3527): TermPortal - TermPortal batch edit "select all" leads to error: param "termid" is not given** <br>
Fixed batch-editing problem popping when all-terms-except-certain selection have no except-certain terms

**[TRANSLATE-3526](https://jira.translate5.net/browse/TRANSLATE-3526): Import/Export, VisualReview / VisualTranslation - Export not possible after server movement** <br>
Some specific file formats did store an absolute file path which prevents server movements.

**[TRANSLATE-3525](https://jira.translate5.net/browse/TRANSLATE-3525): VisualReview / VisualTranslation - Video-Pathes in visual not ready for use in docker cloud** <br>
FIX: Pathes of linked videos in Visual contained absolute paths on server, making problems on changing server location

**[TRANSLATE-3523](https://jira.translate5.net/browse/TRANSLATE-3523): Auto-QA - Allow tag error if error already exists in reference** <br>
Change the way how duplicated tags are parsed during import if they are already duplicated in the imported content.

**[TRANSLATE-3522](https://jira.translate5.net/browse/TRANSLATE-3522): Editor general - Project focus route in URL does not work** <br>
Fix problem where project or task was not being focused when clicking on a link containing a direct task route.

**[TRANSLATE-3521](https://jira.translate5.net/browse/TRANSLATE-3521): Configuration, Translate5 CLI - Config CLI command was resetting the config level to 1** <br>
Due a bug on the CLI config command the config level of changed values was reset to system level, so the config was not changeable in the UI anymore.

**[TRANSLATE-3514](https://jira.translate5.net/browse/TRANSLATE-3514): Editor general - Fix several invalid class references** <br>
Several invalid class references and other coding problems are fixed.

**[TRANSLATE-3513](https://jira.translate5.net/browse/TRANSLATE-3513): Comments - Blocked segments can be commented and then are unblocked** <br>
Blocked and locked segments no longer can be commented in translate5 if not explicitly allowed by ACL.

**[TRANSLATE-3512](https://jira.translate5.net/browse/TRANSLATE-3512): Auto-QA - Missing tags cannot be inserted in the editor** <br>
New config useSourceForReference whose purpose is to choose the reference field for review tasks.
Fixed some error message to make it more obvious which field tags are compared to
Shortcuts like ctrl+insert, ctrl+comma+digit are now working based on reference field tags, but not source

**[TRANSLATE-3509](https://jira.translate5.net/browse/TRANSLATE-3509): Task Management - project ID filter closes after inserting first character** <br>
Fixed filter for ID-column in projects view where the filter dialogue was closing to fast.

**[TRANSLATE-3508](https://jira.translate5.net/browse/TRANSLATE-3508): Editor general - changed error message when long segments cannot be saved to TM** <br>
Improved error message on saving large segments to t5memory TM.

**[TRANSLATE-3496](https://jira.translate5.net/browse/TRANSLATE-3496): Main back-end mechanisms (Worker, Logging, etc.) - session code cleanup and performance improvement** <br>
Loading performance of session data improved. (Step 1)

**[TRANSLATE-3485](https://jira.translate5.net/browse/TRANSLATE-3485): LanguageResources, OpenTM2 integration - T5memory: add Export-call to clone & reorganize, log invalid segments** <br>
FIXES t5memory: 
* add import-call before clone & reorganize calls to fix updated segments missing
* add logging on reorganize for invalid segments when cloning

**[TRANSLATE-3463](https://jira.translate5.net/browse/TRANSLATE-3463): Auto-QA - Deactivation AutoQA for Import queues the AutoQA-Workers nevertheless** <br>
FIX: AutoQA-Workers have been queued even when the AutoQA was completely disabled for the Import

**[TRANSLATE-3456](https://jira.translate5.net/browse/TRANSLATE-3456): t5memory - Error-Message that a segment could not be saved back to TM not shown** <br>
FIX: Error-Message that a segment could not be saved back to TM was not shown in the frontend

**[TRANSLATE-3427](https://jira.translate5.net/browse/TRANSLATE-3427): Import/Export - Reference files from import zip package are not imported** <br>
Fix problem where reference files where not imported from zip packages.

**[TRANSLATE-3419](https://jira.translate5.net/browse/TRANSLATE-3419): Task Management - Click on PM name in project overview opens mail with undefined address - and logs out user in certain cases** <br>
FIXED: 'mailto:undefined' links in PM names in Project overview

**[TRANSLATE-3390](https://jira.translate5.net/browse/TRANSLATE-3390): Auto-QA - Filter is reset, if autoqa error is marked as false positive** <br>
AutoQA filter does now keep selection on False Positive change for qualities

**[TRANSLATE-3316](https://jira.translate5.net/browse/TRANSLATE-3316): Editor general, Search & Replace (editor) - Mysql wildcards not escaped when using search and replace and grid filters** <br>
Mysql wildcards (% and _  ) are now escaped when searching with search and replace and with the grid filters.

**[TRANSLATE-3300](https://jira.translate5.net/browse/TRANSLATE-3300): TermTagger integration - Terms that contain xml special chars are not tagged** <br>
Replaced non-breaking spaces with ordinary spaces before feeding tbx-data to TermTagger

**[TRANSLATE-3291](https://jira.translate5.net/browse/TRANSLATE-3291): Editor general - Sort terms in TermPortlet of the editor according to their order in the segment** <br>
Terms in the right-side Terminology-panel are now sorted in the order they appear in segment source

**[TRANSLATE-1068](https://jira.translate5.net/browse/TRANSLATE-1068): API - Improve REST API on wrong usage** <br>
API requests (expect file uploading requests) can now understand JSON in raw body, additionally to the encapsulated JSON in a data form field. Also a proper HTTP error code is sent when providing invalid JSON.


## [6.6.1] - 2023-10-04

### Important Notes:
 


### Added
**[TRANSLATE-3504](https://jira.translate5.net/browse/TRANSLATE-3504): VisualReview / VisualTranslation - Improve t5memory cli management** <br>
Added a new command for deleting t5memory language resource

**[TRANSLATE-1436](https://jira.translate5.net/browse/TRANSLATE-1436): TermPortal - Add or propose terminology directly from translate5 task** <br>
translate5 - 6.6.0: Added ability to propose terminology right from the opened segment in the editor
translate5 - 6.6.1: Additional UI improvements


### Bugfixes
**[TRANSLATE-3510](https://jira.translate5.net/browse/TRANSLATE-3510): Main back-end mechanisms (Worker, Logging, etc.) - Remove error log summary mail** <br>
The error log summary e-mail is removed in favour of the error log available in the UI.

**[TRANSLATE-3497](https://jira.translate5.net/browse/TRANSLATE-3497): Editor general, Search & Replace (editor) - "replace all" disabled** <br>
'Replace all' button is now disabled if task is really opened by more than one user

**[TRANSLATE-3487](https://jira.translate5.net/browse/TRANSLATE-3487): Editor general - Taking over fuzzy matches in the UI may lead to corrupted internal tags** <br>
6.5.4: FIX: Segments with more then 9 tags were producing errors in the UI
6.5.3: Taking over fuzzy matches in the UI was producing corrupted internal tags. In the Editor the tags were looking correctly, but on database level they did contain the wrong content. 
6.5.5: Fix problem with locked segment tag
6.6.1: Fix problem in old safari browsers


## [6.6.0] - 2023-09-29

### Important Notes:
 


### Added
**[TRANSLATE-3403](https://jira.translate5.net/browse/TRANSLATE-3403): TermPortal - Show history of term or attribute in TermPortal** <br>
Added ability to show editing history for terms and attributes

**[TRANSLATE-1436](https://jira.translate5.net/browse/TRANSLATE-1436): TermPortal - Add or propose terminology directly from translate5 task** <br>
Added ability to propose terminology right from the opened segment in the editor


### Changed
**[TRANSLATE-3500](https://jira.translate5.net/browse/TRANSLATE-3500): ConnectWorldserver - Plugin ConnectWorldserver: finished task will (sometimes) not be transfered back to Worldserver** <br>
If connection to Worldserver does not exist, transfer back to Worldserver does not happen, but the task in Translate5 was finished nevertheless.
Now there is a check so its not possible to finish the task any more and a "connection-error" is shown to the user.

**[TRANSLATE-3408](https://jira.translate5.net/browse/TRANSLATE-3408): InstantTranslate - Implement proper segmentation for InstantTranslate** <br>
Improved segmentation for InstantTranslate to work like the  target segmentation of Okapi


### Bugfixes
**[TRANSLATE-3503](https://jira.translate5.net/browse/TRANSLATE-3503): VisualReview / VisualTranslation - If pdf file contains brackets import fails** <br>
Fixed bug which caused task containing PDF files with square brackets in name fail to import

**[TRANSLATE-3477](https://jira.translate5.net/browse/TRANSLATE-3477): User Management - Add missing ACL right and role documentation** <br>
Added ACL rights and role documentation.


## [6.5.5] - 2023-09-27

### Important Notes:
 


### Bugfixes
**[TRANSLATE-3499](https://jira.translate5.net/browse/TRANSLATE-3499): VisualReview / VisualTranslation - Set --correct-text-visibility for pdfconverter via GUI** <br>
Added new config option runtimeOptions.plugins.VisualReview.pdfcorrectTextVisibility which changes text visibility in PDF which contains for example image overlays / watersigns like "draft" or similar things hiding the real text.

**[TRANSLATE-3487](https://jira.translate5.net/browse/TRANSLATE-3487): Editor general - Taking over fuzzy matches in the UI may lead to corrupted internal tags** <br>
6.5.4: FIX: Segments with more then 9 tags were producing errors in the UI
6.5.3: Taking over fuzzy matches in the UI was producing corrupted internal tags. In the Editor the tags were looking correctly, but on database level they did contain the wrong content. 
6.5.5: Fix problem with locked segment tag

**[TRANSLATE-3289](https://jira.translate5.net/browse/TRANSLATE-3289): TermPortal - Login deletes hash of TermPortal URL** <br>
Addressbar location hash is now preserved on login, if applicable


## [6.5.4] - 2023-09-26
### Bugfixes
**[TRANSLATE-3487](https://jira.translate5.net/browse/TRANSLATE-3487): Editor general - Taking over fuzzy matches in the UI may lead to corrupted internal tags** <br>
FIX: Segments with more then 9 tags were producing errors in the UI

## [6.5.3] - 2023-09-22
### Important Notes:

### Added
**[TRANSLATE-3489](https://jira.translate5.net/browse/TRANSLATE-3489): Okapi integration - Enhance figma default xml conversion settings** <br>
ENHANCEMENT: Fix FIGMA file-format settings regarding whitespace


### Changed
**[TRANSLATE-3474](https://jira.translate5.net/browse/TRANSLATE-3474): TermPortal - Show explaining text, when no filters are set.** <br>
Added explaining text for active filters field when none are in use

**[TRANSLATE-3473](https://jira.translate5.net/browse/TRANSLATE-3473): TermPortal - Make info about TermCollection and Client bold in Termportal middle column** <br>
Font for Client and TermCollection names in Siblings-panel is now bolder and bigger

**[TRANSLATE-3472](https://jira.translate5.net/browse/TRANSLATE-3472): Editor general - Show match resource name in match panel of translate5 editor** <br>
LanguageResource name is now shown in addition to match rate value in 'Match Rate' column within Match panel

**[TRANSLATE-3241](https://jira.translate5.net/browse/TRANSLATE-3241): OpenTM2 integration - T5memory automatic reorganize and via CLI** <br>
translate - 5.9.4
Added two new commands: 
  - t5memory:reorganize for manually triggering translation memory reorganizing
  - t5memory:list - for listing all translation memories with their statuses
Add new config for setting up error codes from t5memory that should trigger automatic reorganizing
Added automatic translation memory reorganizing if appropriate error appears in response from t5memory engine

translate - 6.2.0
 -  Fix the status check for GroupShare language resources

translate - 6.5.3
-   CLI improvement


### Bugfixes
**[TRANSLATE-3495](https://jira.translate5.net/browse/TRANSLATE-3495): Editor general - FIX whitespace tag-check to cope with frontend does not correctly number whitespace-tags** <br>
FIX: Numbering of whitespace-tags may be faulty due to frontend-errors leading to incorrect  tag-errors

**[TRANSLATE-3490](https://jira.translate5.net/browse/TRANSLATE-3490): Auto-QA - AutoQA: Internal Tag-Check does not detect tags with incorrect order on the same index** <br>
FIX: AutoQA did not detect overlapping/interleaving tags when they are on the same index

**[TRANSLATE-3487](https://jira.translate5.net/browse/TRANSLATE-3487): Editor general - Taking over fuzzy matches in the UI may lead to corrupted internal tags** <br>
Taking over fuzzy matches in the UI was producing corrupted internal tags. In the Editor the tags were looking correctly, but on database level they did contain the wrong content. 

**[TRANSLATE-3484](https://jira.translate5.net/browse/TRANSLATE-3484): API - Low full task listing on large instances** <br>
The full listing of tasks via API is reduced to the task data, no additional sub data like quality stats etc per task is added any more to improve loading speed of such a request. 

**[TRANSLATE-3478](https://jira.translate5.net/browse/TRANSLATE-3478): OpenId Connect - Open task for editing with SSO enabled** <br>
Fix: start task editing from a link with SSO authentication does not work

**[TRANSLATE-3461](https://jira.translate5.net/browse/TRANSLATE-3461): Authentication, Editor general - Use http header fields only lowercase** <br>
FIX: evaluation of sent request headers is case-insensitive now

**[TRANSLATE-3454](https://jira.translate5.net/browse/TRANSLATE-3454): t5memory - Analysis runs through although t5memories are in state of reorganisation** <br>
All connection errors are logged now while match analysis, if match analysis is incomplete due such problems an error message is shown on the analysis page.

**[TRANSLATE-3444](https://jira.translate5.net/browse/TRANSLATE-3444): Main back-end mechanisms (Worker, Logging, etc.) - ERROR in core: E9999 - Cannot refresh row as parent is missing** <br>
Fix for back-end error when authentication an user.

**[TRANSLATE-3292](https://jira.translate5.net/browse/TRANSLATE-3292): Editor general - List all homonyms in right-side TermPortlet of the editor** <br>
Now the other homonyms are shown in the right-side Terminology-panel as well


## [6.5.2] - 2023-09-05

### Important Notes:
#### [TRANSLATE-3451](https://jira.translate5.net/browse/TRANSLATE-3451)
This fix cleans inconsistent entries in the user default associations - if there are any. The deleted entries are listed in the system log. Please check the log and readd them if necessary. The inconsistency was due invalid workflow and workflowStep combination, which can only happen due manual changes in the DB.

#### [TRANSLATE-3048](https://jira.translate5.net/browse/TRANSLATE-3048)
This Feature changes the way the T5-API can be accessed: An App-Token MUST be used from now on to request the API
externally. t5connect must be setup to use App-Tokens !!
 


### Added
**[TRANSLATE-3376](https://jira.translate5.net/browse/TRANSLATE-3376): Editor general - Select a term anywhere and hit F3 to search the Concordance** <br>
Concordance search now works for source/target-columns even if editor is not opened


### Changed
**[TRANSLATE-3470](https://jira.translate5.net/browse/TRANSLATE-3470): Test framework - Add option to skip certain tests to test:runall command** <br>
ENHANCEMENT: add option to skip certain tests in test-commands

**[TRANSLATE-3467](https://jira.translate5.net/browse/TRANSLATE-3467): Installation & Update - Implement a instance specific notification facility** <br>
In the optional file client-specific/instance-notes.md specific notes for updating and downtime can be noted. The file is printed on each usage of the maintenance and status command so that admin is remembered to that important notes regarding update and downtime.


### Bugfixes
**[TRANSLATE-3468](https://jira.translate5.net/browse/TRANSLATE-3468): Main back-end mechanisms (Worker, Logging, etc.) - Fix AcrossHotfolder faulty namespaces in the SQL files** <br>
FIX: Wrong SQL in Across Hotfolder plugin

**[TRANSLATE-3464](https://jira.translate5.net/browse/TRANSLATE-3464): Main back-end mechanisms (Worker, Logging, etc.) - Awkward Code in base Entity-class may leads to memory buildup** <br>
FIX: inappropriate code in entity-base-class may leads to high memory-consumption

**[TRANSLATE-3457](https://jira.translate5.net/browse/TRANSLATE-3457): User Management - Client-PM / User Manegment: Roles "No rights" & "Basic" appear mistakenly in the user-form** <br>
FIX: roles "no rights" & "basic" mistakenly appeared in the user-editor form

**[TRANSLATE-3451](https://jira.translate5.net/browse/TRANSLATE-3451): Client management, Workflows - Fix wrong foreign key usage and introduce simple workflow management CLI commands** <br>
This fix cleans inconsistent entries in the user default associations - if there are any and adds proper foreign keys. The deleted entries are listed in the system log. Please check the log and readd them if necessary. The inconsistency was due invalid workflow and workflowStep combination, which can only happen due manual changes in the DB.

**[TRANSLATE-3450](https://jira.translate5.net/browse/TRANSLATE-3450): Import/Export - XLF x tags with non unique IDs lead to duplicated tags after import** <br>
XLF with duplicated x tag ids in the same segment were producing wrong tag numbers on import.

**[TRANSLATE-3449](https://jira.translate5.net/browse/TRANSLATE-3449): Editor general - Taking matches with tags ignores the tag numbers** <br>
Fixed calculating tag numbers when setting TM match to segment editor

**[TRANSLATE-3448](https://jira.translate5.net/browse/TRANSLATE-3448): Main back-end mechanisms (Worker, Logging, etc.) - Missing worker dependencies** <br>
FIX: Task Operations may unexpectedly terminated before doing anything since the workers have been queued in state "scheduled"
FIX: Quality Operation queued workers even if completely deactivated
CARE: checked worker dependencies, added commandline-tool to visualize the dependencies

**[TRANSLATE-3445](https://jira.translate5.net/browse/TRANSLATE-3445): LanguageResources - Google connector: wrong language compare** <br>
Fix for wrong language compare in google connector.

**[TRANSLATE-3440](https://jira.translate5.net/browse/TRANSLATE-3440): TermPortal - Misunderstandable message, if no Default-PM for term-translations is defined** <br>
Improved message shown if no Default-PM for term-translations is defined

**[TRANSLATE-3236](https://jira.translate5.net/browse/TRANSLATE-3236): TermPortal - Some attribute values need to change in the term translation workflow** <br>
Improved import logic for 'Created/Updated At/By' and 'Gender' tbx attributes

**[TRANSLATE-3138](https://jira.translate5.net/browse/TRANSLATE-3138): Client management - Set filter in project and Clients grids does not reselect the row** <br>
Improved auto-selection logic for Projects/Clients grids when filters are used

**[TRANSLATE-3048](https://jira.translate5.net/browse/TRANSLATE-3048): Editor general - CSRF Protection for translate5** <br>
translate5 - 6.0.0
- CSRF (Cross Site Request Forgery) Protection for translate5 with a CSRF-token. Important info for translate5 API users: externally the translate5 - API can only be accessed with an App-Token from now on.
translate5 - 6.0.2
- remove CSRF protection for automated cron calls
translate5 - 6.5.2
- additional action protected


## [6.5.1] - 2023-08-04

### Important Notes:
 


### Bugfixes
**[TRANSLATE-3442](https://jira.translate5.net/browse/TRANSLATE-3442): Client management - Role Client-PM must have Customer-management enabled to have customers available in other views** <br>
FIX: Role "PM selected Clients" must have Customer management enabled to have accessible Customers in other management-views

**[TRANSLATE-3441](https://jira.translate5.net/browse/TRANSLATE-3441): Editor general - Translate5 UI errors** <br>
Multiple fixes for UI errors.

**[TRANSLATE-3433](https://jira.translate5.net/browse/TRANSLATE-3433): VisualReview / VisualTranslation - Segment selection/scrolling may leads to wrong "segment not found" toasts** <br>
translate5 - 6.5.0: BUG: segment selection/scrolling may leads to wrong "segment not found" toasts
translate5 - 6.5.1: Additional improvement

**[TRANSLATE-3422](https://jira.translate5.net/browse/TRANSLATE-3422): TBX-Import - Language mapping does not work correctly for TBX, that are imported in a zip** <br>
Language matching is improved when importing TBX file in import zip package.


## [6.5.0] - 2023-07-28

### Important Notes:
 


### Added
**[TRANSLATE-3207](https://jira.translate5.net/browse/TRANSLATE-3207): LanguageResources, TermPortal - Extend TBX import with images from zip folder** <br>
Added support for terminology images to be imported/exported in zip-archive

**[TRANSLATE-3164](https://jira.translate5.net/browse/TRANSLATE-3164): VisualReview / VisualTranslation - Pivot language preview in the visual layout** <br>
Plugin Visual: In the (left) source layout for tasks with available pivot now a toggle button exists, that will switch between source and pivot language. Please note, that the pivot-view will use the reflown Wysiwyg-layout with all known limitations.

**[TRANSLATE-3031](https://jira.translate5.net/browse/TRANSLATE-3031): Client management, LanguageResources, Task Management, User Management - Multitenancy of management interfaces** <br>
Add role "PM selected clients" which enables to create PMs which are restricted to certain clients (multitenancy)


### Changed
**[TRANSLATE-3432](https://jira.translate5.net/browse/TRANSLATE-3432): Main back-end mechanisms (Worker, Logging, etc.) - Logger to catch more info about no access exception** <br>
Added special logging improvement for certain backend error.

**[TRANSLATE-3398](https://jira.translate5.net/browse/TRANSLATE-3398): Workflows - Extend translate5 mail on task status change** <br>
ENHANCEMENT: It can be configured if the changed segments email contains the commented segments and if the "Target text(at time of import)" is shown.

**[TRANSLATE-3396](https://jira.translate5.net/browse/TRANSLATE-3396): VisualReview / VisualTranslation - Visual WYSIWYG: Pages of the visual remain untranslated when database is very slow** <br>
FIX: When the database is very slow, the visual Wysiwyg may remain untranslated in sections

**[TRANSLATE-3378](https://jira.translate5.net/browse/TRANSLATE-3378): Test framework - Add tests for TildeMT plugin** <br>
Added API test for TildeMT plugin using a fake-API

**[TRANSLATE-3109](https://jira.translate5.net/browse/TRANSLATE-3109): User Management - UI for appTokens** <br>
For API users: Implemented a administration for application auth tokens in the UI. Improved according CLI commands to list, delete app tokens and set expires date with CLI.



### Bugfixes
**[TRANSLATE-3439](https://jira.translate5.net/browse/TRANSLATE-3439): VisualReview / VisualTranslation - Visual: FIX Image-Import for monochrome or transparent images** <br>
FIX: When images as review-source in the Visual are completely transparent or  monochrome the color-processing fails with an unhandled exception

**[TRANSLATE-3438](https://jira.translate5.net/browse/TRANSLATE-3438): TermPortal, TermTagger integration - TermPortal: Custom attribute names are not reflected in translate5s editor** <br>
FIXED: Term-portlet attributes labels problem 

**[TRANSLATE-3437](https://jira.translate5.net/browse/TRANSLATE-3437): Editor general - No way to save segment with MQM tags** <br>
Fixed bug which caused an error on saving segment with MQM tag

**[TRANSLATE-3435](https://jira.translate5.net/browse/TRANSLATE-3435): Authentication - Sessions are not cleaned up in DB, Logins frequently fail (mayby due to faulty db-session-data)** <br>
FIX: expired sessions were not cleaned anymore leading to potential problems with the login. Other quirks also could lead to multiple entries for the unique-id

**[TRANSLATE-3433](https://jira.translate5.net/browse/TRANSLATE-3433): VisualReview / VisualTranslation - Segment selection/scrolling may leads to wrong "segment not found" toasts** <br>
BUG: segment selection/scrolling may leads to wrong "segment not found" toasts

**[TRANSLATE-3431](https://jira.translate5.net/browse/TRANSLATE-3431): Editor general - Pasting content when segments editor is closed** <br>
FIX: solve potential problem when pasting content in the segment-editor very fast

**[TRANSLATE-3430](https://jira.translate5.net/browse/TRANSLATE-3430): MatchAnalysis & Pretranslation - Match Ranges & Pricing: changing preset should be possible for PM only** <br>
Pricing preset now can be changed by PM only

**[TRANSLATE-3402](https://jira.translate5.net/browse/TRANSLATE-3402): Okapi integration - Hotfix: delete deepl glossary on deleting termcollection** <br>
translate - 6.4.3: When deleting a termcollection the corresponding DeepL glossary was not deleted. This is fixed now.
translate - 6.5.0: Change wrong title in change-log

**[TRANSLATE-3304](https://jira.translate5.net/browse/TRANSLATE-3304): Package Ex and Re-Import - Improve re-import segment alignment** <br>
Different segment alignment will be used base on the task version. All tasks older then translate5 - 6.5.0 will use different segment alignment.

**[TRANSLATE-3303](https://jira.translate5.net/browse/TRANSLATE-3303): Import/Export - Generated mid is not unique enough** <br>
The XLF re-import had problems if in a package segments had the same segment ID. Now the generation of the ID is changed to be really unique.

**[TRANSLATE-2831](https://jira.translate5.net/browse/TRANSLATE-2831): Configuration - Repetition editor options do not appear in client overwrites** <br>
Fix config level of Repetition editor options


## [6.4.3] - 2023-07-13

### Important Notes:
 


### Bugfixes
**[TRANSLATE-3426](https://jira.translate5.net/browse/TRANSLATE-3426): Editor general - Error while trying to set content to editor from matches** <br>
Fix for problem when taking over language resources suggested translations can lead to UI error

**[TRANSLATE-3425](https://jira.translate5.net/browse/TRANSLATE-3425): Import/Export - Tags imported from across get wrong id** <br>
In across xliff the tags may use a custom unique id instead the default id attribute which leads to problems with duplicated tags which had to be repaired manually in the past. Now the across ID is used instead.

**[TRANSLATE-3424](https://jira.translate5.net/browse/TRANSLATE-3424): OpenTM2 integration - Tag mismatch in t5memory results due nonnumeric rids** <br>
Tags from segments may get removed when taking over from t5memory due mismatching tag ids.

**[TRANSLATE-3402](https://jira.translate5.net/browse/TRANSLATE-3402): DeepL integration - Hotfix: delete deepl glossary on deleting termcollection** <br>
When deleting a termcollection the corresponding DeepL glossary was not deleted. This is fixed now.


## [6.4.2] - 2023-07-11

### Important Notes:
#### [TRANSLATE-3417](https://jira.translate5.net/browse/TRANSLATE-3417)
pdfconverter docker image update is required
 


### Changed
**[TRANSLATE-3417](https://jira.translate5.net/browse/TRANSLATE-3417): VisualReview / VisualTranslation - Skip optimization step in pdfconverter** <br>
PDF converter now doesn't optimize pdf files before conversion by default, but does that as a fallback if conversion failed. Behavior can be changed by enabling runtimeOptions.plugins.VisualReview.optimizeBeforeConversion config option.


### Bugfixes
**[TRANSLATE-3423](https://jira.translate5.net/browse/TRANSLATE-3423): VisualReview / VisualTranslation - Error focusing segment alias in split-frame sidbar** <br>
Fix for a front-end problem when trying to focus segment in visual split-frame.

**[TRANSLATE-3416](https://jira.translate5.net/browse/TRANSLATE-3416): LanguageResources - DeepL languages request missing languages** <br>
Fix for a problem where regional languages where not listed as available target option for DeepL language resource

**[TRANSLATE-3412](https://jira.translate5.net/browse/TRANSLATE-3412): LanguageResources - Missing class include in Glossary events** <br>
Fix for a problem where term collection was not able to be assigned as glossary source

**[TRANSLATE-3399](https://jira.translate5.net/browse/TRANSLATE-3399): InstantTranslate, LanguageResources - Unescaped special chars returned by DeepL** <br>
It seems like the DeepL API has changed its default behaviour regarding the tag handling. Now we force HTML usage if not explicitly XML is given to fix some encoding problems


## [6.4.1] - 2023-07-03

### Important Notes:
 


### Bugfixes
**[TRANSLATE-3411](https://jira.translate5.net/browse/TRANSLATE-3411): Auto-QA, Main back-end mechanisms (Worker, Logging, etc.) - UI JS error and to much php log data** <br>
Fixing some UI JS errors coming from TRANSLATE-3360 and remove some unnecessary log entries flooding the php.log file (resulting from TRANSLATE-2101)

**[TRANSLATE-3410](https://jira.translate5.net/browse/TRANSLATE-3410): I10N - Missing locale logic for login page** <br>
FIXED: wrong/missing locale logic for login page

**[TRANSLATE-3405](https://jira.translate5.net/browse/TRANSLATE-3405): Auto-QA - Fix Quality Tests** <br>
Adjust Quality-tests for current features, fix small file-handling quirk


## [6.4.0] - 2023-06-28

### Important Notes:
#### [TRANSLATE-3397](https://jira.translate5.net/browse/TRANSLATE-3397)
The languatetool live check on editing is enabled now by default - also on instances where it was disabled with purpose. If you don't want the live check please disable it again: configuration SpellCheck.liveCheckOnEditing

#### [TRANSLATE-3375](https://jira.translate5.net/browse/TRANSLATE-3375)
The warning for the translator about "editing an 100% matches" in the editor GUI is disabled by default with this release on system level. Client level settings will stay. If you want to have this enabled on system level in the future, you will need to reactivate it.
 


### Added
**[TRANSLATE-3360](https://jira.translate5.net/browse/TRANSLATE-3360): Auto-QA, Editor general - AutoQA must be 0 errors to finish task** <br>
It is now configurable for the PM to create a list of qualities on system, client, import and task level, for which AutoQA check 0 errors is required to finish the task. Errors that are set to false positive are allowed and do not count.

**[TRANSLATE-3321](https://jira.translate5.net/browse/TRANSLATE-3321): InstantTranslate - InstantTranslate with DeepL: Detect source language automatically** <br>
ENHANCEMENT: InstantTranslate now supports auto-detection of the source language

**[TRANSLATE-3218](https://jira.translate5.net/browse/TRANSLATE-3218): API - Hotfolder-based connector solution, that mimics Across hotfolder** <br>
6.4.0: Several fixes, introducing an API endpoint to trigger the hotfolder check manually
6.3.1: New AcrossHotfolder plugin that watches hotfolders for tasks, that should be created in translate5 - and re-exported to the hotfolder, once they are ready


### Changed
**[TRANSLATE-3393](https://jira.translate5.net/browse/TRANSLATE-3393): Editor general - Include new German editor documentation in translate5** <br>
The new German documentation about the translate5 editor has been linked in the help section of the editor

**[TRANSLATE-3391](https://jira.translate5.net/browse/TRANSLATE-3391): t5memory - Add 500 status code to automatically trigger reorganize TM** <br>
Add t5memory 500 error to trigger TM reorganization automatically.

**[TRANSLATE-3381](https://jira.translate5.net/browse/TRANSLATE-3381): Main back-end mechanisms (Worker, Logging, etc.) - Start workers as plain processes instead using HTTP requests** <br>
6.4.0: The current approach of triggering workers via HTTP is hard to debug and has a big overhead due the HTTP connections. Now the worker invocation can be switched to use raw processes - which is still under development and disabled by default but can be enabled for testing purposes in production.

**[TRANSLATE-3377](https://jira.translate5.net/browse/TRANSLATE-3377): Editor general, Repetition editor - Repetition editor window is annoying** <br>
New info message how to disable the repetition editor is added to the repetition editor pop-up.

**[TRANSLATE-3375](https://jira.translate5.net/browse/TRANSLATE-3375): Configuration, Editor general - Warning about editing a 100%-Match: Disable it by default** <br>
The warning about editing 100% matches will be disabled by default on system level.


### Bugfixes
**[TRANSLATE-3397](https://jira.translate5.net/browse/TRANSLATE-3397): Configuration - Correct configuration default values** <br>
The default value of some configurations was changed in the past, but the comparator (for the is changed check) in the DB was not updated. This is fixed now.

**[TRANSLATE-3394](https://jira.translate5.net/browse/TRANSLATE-3394): Main back-end mechanisms (Worker, Logging, etc.) - Bug in exception handling in looped workers leads to exceptions that should have been retried** <br>
FIX: Looped processing workers may threw exceptions when the request should have been retried 

**[TRANSLATE-2101](https://jira.translate5.net/browse/TRANSLATE-2101): Main back-end mechanisms (Worker, Logging, etc.) - Disable automated translation xliff creation from notFountTranslation xliff in production instances** <br>
translate5 - 6.4.0: Disabling the not found translation log writer for production instances.

translate5 - 5.0.3: Deactivating a logging facility for missing internal UI translations in production and clean the huge log files. Also enable caching for UI translations in production instances only.


## [6.3.1] - 2023-06-20

### Important Notes:
 


### Changed
**[TRANSLATE-3384](https://jira.translate5.net/browse/TRANSLATE-3384): ConnectWorldserver - Plugin ConnectWorldserver unusable due merge-conflicts** <br>
Repair ConnectWorldserver plug-in which was unusable since the code base was on the development state and not on a releasable state.

**[TRANSLATE-3382](https://jira.translate5.net/browse/TRANSLATE-3382): Editor general - Fix TextShuttle plugin** <br>
TextShuttle plugin structure fixed


**[TRANSLATE-3357](https://jira.translate5.net/browse/TRANSLATE-3357): LanguageResources - Make Tilde config data overwriteable on client level** <br>
TildeMT API configuration parameters can now be overwritten on client level

**[TRANSLATE-3354](https://jira.translate5.net/browse/TRANSLATE-3354): LanguageResources - API Keys for Textshuttle via GUI, overwritable on client level** <br>
Some API configurations for TextShuttle plugin can now be overwritten on client level


### Bugfixes
**[TRANSLATE-3388](https://jira.translate5.net/browse/TRANSLATE-3388): Editor general - Fix and improve architecture to evaluate the supported file formats** <br>
Improve evaluation of supported file types/formats, fix wrong filetype-evalution in frontend when task specific file filters were set.

**[TRANSLATE-3387](https://jira.translate5.net/browse/TRANSLATE-3387): Editor general - Unable to change UI langauge** <br>
Fix problem where the UI language was unable to be changed

**[TRANSLATE-3383](https://jira.translate5.net/browse/TRANSLATE-3383): Editor general - Newline visualization in internal-tags / segments** <br>
FIX: Newlines in tags may appear as newlines in translate5 internal tags leading to defect tags in the frontend. Now they are converted to visual newlines instead.

**[TRANSLATE-3379](https://jira.translate5.net/browse/TRANSLATE-3379): Import/Export - Missing workflow user preferences leads to errors in the UI** <br>
Error in the UI when the task has no workflow preferences entries which can happen if the task can not be imported.

**[TRANSLATE-3343](https://jira.translate5.net/browse/TRANSLATE-3343): Main back-end mechanisms (Worker, Logging, etc.) - Stop PdfToHtmlWorker if pdfconverter failed to create a job** <br>
PdfToHtmlWorker now finishes immediately if the conversion job failed to create or there is an error occurred while retrieving the conversion job result. So now the error on task import appears faster without waiting for the maximum pdfconverter timeout to exceed.

**[TRANSLATE-3186](https://jira.translate5.net/browse/TRANSLATE-3186): Import/Export - Import is interrupted because of files with no segments** <br>
If an import contains some files containing no translatable content will no longer set the whole task to status error.


## [6.3.0] - 2023-06-15

### Important Notes:
#### [TRANSLATE-2551](https://jira.translate5.net/browse/TRANSLATE-2551)
Changements in "/editor/file" API endpoint - normally there was no practical use case to access it via external API - though we want to mention here that the API endpoint file was renamed to /editor/filetree.
 


### Added
**[TRANSLATE-3218](https://jira.translate5.net/browse/TRANSLATE-3218): API - Hotfolder-based connector solution, that mimics Across hotfolder** <br>
New AcrossHotfolder plugin that watches hotfolders for tasks, that should be created in translate5 - and re-exported to the hotfolder, once they are ready

**[TRANSLATE-2551](https://jira.translate5.net/browse/TRANSLATE-2551): Import/Export - Update Task with xliff** <br>
5.7.14
Enable existing file to be replaced and with this the segments will be updated in the task.
6.3.0
Fix for getting the correct export class when changed segments are collected for e-mail.


### Changed
**[TRANSLATE-3372](https://jira.translate5.net/browse/TRANSLATE-3372): Client management - Prevent TEST calls with root-rights** <br>
ENHANCEMENT: prevent calling the API-test CLI-command to be used with root-rights

**[TRANSLATE-3346](https://jira.translate5.net/browse/TRANSLATE-3346): file format settings - Add cleanup command for invalid BCONF entries** <br>
ENHANCEMENT: Add CLI command to clean and fix invalid BCONF entries

**[TRANSLATE-3331](https://jira.translate5.net/browse/TRANSLATE-3331): Test framework - Base-architecture to provide test-configs from plugins (especially private plugins), Improved Service Architecture** <br>
ENHANCEMENT: add test-config provider from plugins and plugin-services


### Bugfixes
**[TRANSLATE-3368](https://jira.translate5.net/browse/TRANSLATE-3368): VisualReview / VisualTranslation - Pdfconverter fail to process pdf** <br>
  - PDFconverter command was changed to be run by watchman for immediate conversion and by cron for periodical
  - PDFconverter command now has capability to be run miltiple times, max amount of parallel runs is configurable via MAX_PARALLEL_PROCESSES environment variable of the pdfconverter container

**[TRANSLATE-3366](https://jira.translate5.net/browse/TRANSLATE-3366): file format settings - Add the internal tag "hyper" to the figma file format settings** <br>
Improved FIGMA file-format settings to support "hyper" attributes in figma-files

**[TRANSLATE-3355](https://jira.translate5.net/browse/TRANSLATE-3355): InstantTranslate - Auto-deletion of instant-translate pre-translated tasks is not correct** <br>
Replace order date with created date when fetching InstantTranslate pre-translated tasks to remove

**[TRANSLATE-3348](https://jira.translate5.net/browse/TRANSLATE-3348): Import/Export - Plugin-ConnectWorldserver error on import** <br>
6.2.2
improved download files from Worldserver and error-, notification-handling
6.3.0
Fix error on task import.

**[TRANSLATE-3345](https://jira.translate5.net/browse/TRANSLATE-3345): Configuration - Wrong or non existing config type class error** <br>
Not found config class for configuration will be logged as warnings

**[TRANSLATE-3336](https://jira.translate5.net/browse/TRANSLATE-3336): Auto-QA - False positive pop-up and tooltip need readjustment for grey theme** <br>
FIXED: false positives style problem in Gray and Neptune themes

**[TRANSLATE-3312](https://jira.translate5.net/browse/TRANSLATE-3312): Editor general - Active project grid filter leads to an error in task add window** <br>
Filtered tasks grid lead to an error when creating new project.

**[TRANSLATE-3311](https://jira.translate5.net/browse/TRANSLATE-3311): I10N - Add Bengali for Bangladesh and India to LEK_languages** <br>
- Bengali `bn` set as main language
- Added two sublanguages for Bengali: India and Bangladesh
- Added locale translations for sublanguage names

**[TRANSLATE-3309](https://jira.translate5.net/browse/TRANSLATE-3309): Main back-end mechanisms (Worker, Logging, etc.) - Consolidate session lifetime configuration** <br>
Consolidate session configuration and make it accessible over the UI.

**[TRANSLATE-3308](https://jira.translate5.net/browse/TRANSLATE-3308): TermTagger integration - Missing locale causes sql error** <br>
FIXED: error on missing user locale

**[TRANSLATE-2992](https://jira.translate5.net/browse/TRANSLATE-2992): Main back-end mechanisms (Worker, Logging, etc.) - PHP's setlocale has different default values** <br>
5.7.4
The PHP's system locale was not correctly set. This is due a strange behaviour setting the default locale randomly.
6.3.0
Some small code improvements

**[TRANSLATE-2190](https://jira.translate5.net/browse/TRANSLATE-2190): Main back-end mechanisms (Worker, Logging, etc.) - PHP ERROR in core: E9999 - Cannot refresh row as parent is missing - fixed in DbDeadLockHandling context** <br>
6.3.0
Fix for back-end workers error.


## [6.2.3] - 2023-06-09

### Important Notes:
 


### Changed
**[TRANSLATE-3365](https://jira.translate5.net/browse/TRANSLATE-3365): Test framework - Improvement on testing framework** <br>
Improvement in translate5 testing framework.

**[TRANSLATE-3349](https://jira.translate5.net/browse/TRANSLATE-3349): LanguageResources - HOTFIX: DeepL API changes regarding formality** <br>
HOTFIX: API-changes with DeepL (formality) lead to pretranslation/analysis fails for certain target languages

**[TRANSLATE-3329](https://jira.translate5.net/browse/TRANSLATE-3329): Test framework - Testing certain branch in the cloud accessible for developers** <br>
Cloud based testing Implementation.


### Bugfixes
**[TRANSLATE-3367](https://jira.translate5.net/browse/TRANSLATE-3367): VisualReview / VisualTranslation - Visual can not be created due to failing CSS processing** <br>
FIX: In very rare cases the CSS processing of the Visual Markup failed preventing the Visual to be created

**[TRANSLATE-3358](https://jira.translate5.net/browse/TRANSLATE-3358): LanguageResources - TildeMT update translation does not work** <br>
Updating translations was not possible because of wrong API parameter name.

**[TRANSLATE-3356](https://jira.translate5.net/browse/TRANSLATE-3356): file format settings - OKAPI import: Available extensions of used bconf not used for processing files** <br>
FIX: Added extensions in custom file-format-settings may have been rejected nevertheless when trying to import files with this extension
FIX: In the Client Panels freshly added file-filter-settings created an error when deleted immediately after creation

**[TRANSLATE-3353](https://jira.translate5.net/browse/TRANSLATE-3353): Translate5 CLI - HOTFIX: qautodiscovery-command does not work properly in self-hosted dockerized instances** <br>
FIX: improved service:autodiscovery command when used in self-hosted instances

**[TRANSLATE-3352](https://jira.translate5.net/browse/TRANSLATE-3352): VisualReview / VisualTranslation - Increase timeout for communication with visualconverter and pdfconverter** <br>
Communication timeouts between T5 and visualconverter/pdfconverter were increased to 30 seconds

**[TRANSLATE-3350](https://jira.translate5.net/browse/TRANSLATE-3350): Export - Error when task is exported multiple times** <br>
Exporting task multiple times lead to an error. Now the users will no longer be able to export a task if there is already running export for the same task.

**[TRANSLATE-3347](https://jira.translate5.net/browse/TRANSLATE-3347): Import/Export - Race condition in creating task meta data on import** <br>
When there is a longer time gap between steps in the import it may happen that the import crashes due race-conditions in saving the task meta table.

**[TRANSLATE-3341](https://jira.translate5.net/browse/TRANSLATE-3341): TBX-Import - TBX files are kept on disk on updating term-collections** <br>
On updating TermCollections all TBX files are kept on disk: this is reduced to 3 months in the past for debugging purposes.

**[TRANSLATE-3335](https://jira.translate5.net/browse/TRANSLATE-3335): t5memory - Reimport stops, if one segment can not be saved because of segment length** <br>
Fixed an error that might cause t5memory reorganizing when it was not actually needed

**[TRANSLATE-3320](https://jira.translate5.net/browse/TRANSLATE-3320): LanguageResources - FIX Tag check and tag handling for LanguageResource matches** <br>
FIX: Solve Problems with additional whitespace tags from accepted TM matches not being saved / stripped on saving

**[TRANSLATE-3319](https://jira.translate5.net/browse/TRANSLATE-3319): Import/Export - FIX tag-handling in Transit Plugin** <br>
Fixed bug with tag parsing in Transit plugin


## [6.2.2] - 2023-05-25

### Important Notes:
 


### Added
**[TRANSLATE-3172](https://jira.translate5.net/browse/TRANSLATE-3172): file format settings - XML File Filter Settings for Figma** <br>
Assigned "*.figma" file -extension for the figma file-filter setting


### Bugfixes
**[TRANSLATE-3344](https://jira.translate5.net/browse/TRANSLATE-3344): VisualReview / VisualTranslation - FIX symlink creation in visual for invalid symlinks** <br>
Symlink creation in visual might not refresh outdated symlinks

**[TRANSLATE-3339](https://jira.translate5.net/browse/TRANSLATE-3339): Editor general - HOTFIX: several smaller fixes** <br>
ENHANCEMENT: improved event-msg of the "too many segments per trans-unit" exception
FIX: increased max segments per transunit to 250
ENHANCEMENT: Add all OKAPI versions when using the autodiscovery for development

**[TRANSLATE-3338](https://jira.translate5.net/browse/TRANSLATE-3338): SNC - Clean SNC numbers check debug output** <br>
Clean the debug output in SNC numbers check library.

**[TRANSLATE-3337](https://jira.translate5.net/browse/TRANSLATE-3337): API - Plugin ConnectWorldserver: wrong attribut for Visual** <br>
Plugin ConnectWordserver: changed attribute for visual from "layout_source_translate5" to new name "translate5_layout_source"


## [6.2.1] - 2023-05-11

### Important Notes:
 


### Bugfixes
**[TRANSLATE-3327](https://jira.translate5.net/browse/TRANSLATE-3327): LanguageResources - Problem with unconfigured language resources** <br>
Removed configuration from language resource services leads to an error


## [6.2.0] - 2023-05-11

### Important Notes:
#### [TRANSLATE-3313](https://jira.translate5.net/browse/TRANSLATE-3313)
PHP error_log location moved to translate5 installation root folder /data/logs/ directory, with logs rotation enabled, if needed elsewhere overwrite the location in installation.ini
 


### Added
**[TRANSLATE-3322](https://jira.translate5.net/browse/TRANSLATE-3322): Editor general - Integrate Tilde MT in translate5** <br>
Added new plugin which integrates Tilde Machine Translation into Translate5.


### Changed
**[TRANSLATE-3317](https://jira.translate5.net/browse/TRANSLATE-3317): Editor general - Add port to be configurable** <br>
Custom database port can be set when installing new translate5 instance using the translate5 installer. This can be done with setting the new environment variable T5_INSTALL_DB_PORT while installing Translate5.

**[TRANSLATE-3313](https://jira.translate5.net/browse/TRANSLATE-3313): Main back-end mechanisms (Worker, Logging, etc.) - place php.log under data log and use log rotation** <br>
PHP error_log moved to translate5 installation root folder /data/logs/ directory, with logs rotation enabled, if needed elsewhere overwrite the location in installation.ini

**[TRANSLATE-3299](https://jira.translate5.net/browse/TRANSLATE-3299): Auto-QA - Enable Segment Batches in Spellchecker Request** <br>
Enabled batch-processing of segments in the Spellcheck during import.

**[TRANSLATE-3267](https://jira.translate5.net/browse/TRANSLATE-3267): LanguageResources - Improve automatic memory reorganization** <br>
translate5 - 6.0.0
    - Language resource while reorganizing TM is happening is now treated as importing to restrict any other operation on it.
    - Update now is also disabled while reorganizing TM is in progress.

translate5 - 6.2.0
    - Reformating of the error codes list

**[TRANSLATE-3241](https://jira.translate5.net/browse/TRANSLATE-3241): OpenTM2 integration - T5memory automatic reorganize and via CLI** <br>
translate - 5.9.4
Added two new commands: 
  - t5memory:reorganize for manually triggering translation memory reorganizing
  - t5memory:list - for listing all translation memories with their statuses
Add new config for setting up error codes from t5memory that should trigger automatic reorganizing
Added automatic translation memory reorganizing if appropriate error appears in response from t5memory engine

translate - 6.2.0
 -  Fix the status check for GroupShare language resources


### Bugfixes
**[TRANSLATE-3323](https://jira.translate5.net/browse/TRANSLATE-3323): InstantTranslate, t5memory - t5memory translations not available in instant translate** <br>
Fix for a problem where t5memory results where not listed in instant-translate.

**[TRANSLATE-3318](https://jira.translate5.net/browse/TRANSLATE-3318): MatchAnalysis & Pretranslation - Cloning of pricing template does not clone prices in no-matches column** <br>
FIXED: Price for 'No match' column not cloned during pricing preset cloning

**[TRANSLATE-3310](https://jira.translate5.net/browse/TRANSLATE-3310): Main back-end mechanisms (Worker, Logging, etc.) - Maintenance display text localization** <br>
Enable custom maintenance text and text localization.

**[TRANSLATE-3307](https://jira.translate5.net/browse/TRANSLATE-3307): Translate5 CLI - Cron events are not triggered on CLI usage** <br>
translate5 command line tool fix for cron commands. In details: cron did not trigger the cron related events.

**[TRANSLATE-3306](https://jira.translate5.net/browse/TRANSLATE-3306): Editor general - Error occurred when trying to assign language resource to task** <br>
The translate5 will no longer raise error in case for duplicate user assignment.

**[TRANSLATE-3280](https://jira.translate5.net/browse/TRANSLATE-3280): Editor general - Fixing UI errors** <br>
translate - 6.0.2
- Fix for error when switching customer in add task window and quickly closing the window with esc key. (me.selectedCustomersConfigStore is null)
- Fix for error when "segment qualities" are still loading but the user already left/close the task. (this.getMetaFalPosPanel() is undefined)
- Right clicking on disabled segment with spelling error leads to an error. (c is null_in_quality_context.json)
- Applying delayed quality styles to segment can lead to an error in case the user left the task before the callback/response is evaluated.

translate - 6.2.0
 - Fix for UI error : setRootNode is undefined

**[TRANSLATE-3262](https://jira.translate5.net/browse/TRANSLATE-3262): Import/Export - Protected non breaking spaces are not respected on reimport** <br>
On re-import, the protected tags (white spaces, line breaks etc)  from the incoming content will no longer be ignored.

**[TRANSLATE-3061](https://jira.translate5.net/browse/TRANSLATE-3061): Test framework - FIX API Tests** <br>
translate5 - 5.7.13
 - Code refactoring for the testing environment. Improvements and fixes for API test cases.
translate5 - 6.0.2
 - Fixed config loading level in testing environment 
translate5 - 6.2.0
 - general improvement in API test cases


## [6.1.0] - 2023-04-26

### Important Notes:
#### [TRANSLATE-2991](https://jira.translate5.net/browse/TRANSLATE-2991)
The "match rate boundaries" configuration in the system configuration and the clients overwrites is removed.
If you changed the default configuration on system level or for certain clients, you need to manually redo the configuration in the new "Pricing match rates" configuration on system and/or client level.
 


### Added
**[TRANSLATE-3182](https://jira.translate5.net/browse/TRANSLATE-3182): Editor general - Show optionally character count for current open segment** <br>
Added optional character counter for the segment-editor. By default it is invisible - unless the runtimeOptions.editor.toolbar.showHideCharCounter config is set to active. The counter can be activated by the user  in the segment grid settings menu

**[TRANSLATE-2991](https://jira.translate5.net/browse/TRANSLATE-2991): Configuration, MatchAnalysis & Pretranslation - Pricing & match rate presets** <br>
Sophisticated config options for calculating prices and defining custom match ranges are introduced.


### Changed
**[TRANSLATE-3246](https://jira.translate5.net/browse/TRANSLATE-3246): file format settings - Fixed naming scheme for OKAPI config entries** <br>
Fixed naming scheme for Okapi Service Configuration Entries. In the frontend, no name must be defined anymore

**[TRANSLATE-3225](https://jira.translate5.net/browse/TRANSLATE-3225): InstantTranslate - DeepL at times simply not answers requests leading to errors in T5 that suggest the app is malfunctioning** <br>
Instant translate UI now shows errors happening with language resources during translation


### Bugfixes
**[TRANSLATE-3302](https://jira.translate5.net/browse/TRANSLATE-3302): Translate5 CLI - Notification mails are not translated when starting cron via CLI** <br>
Internal translations were missing when calling cron via CLI.

**[TRANSLATE-3298](https://jira.translate5.net/browse/TRANSLATE-3298): Main back-end mechanisms (Worker, Logging, etc.) - Version conflict when using multiple tabs** <br>
Improve logging when translate5 is opened in multiple tabs and reduce log entries when a version conflict pops up.

**[TRANSLATE-3297](https://jira.translate5.net/browse/TRANSLATE-3297): Import/Export - Corrupt skeleton file on reimport** <br>
Fix for corrupted skeleton files after translator package was re-imported into a task.

**[TRANSLATE-3296](https://jira.translate5.net/browse/TRANSLATE-3296): SpellCheck (LanguageTool integration) - Add config to prevent spellchecking non-editable / locked segments on import** <br>
Enhancement: Add configuration to skip spellchecking for read-only segments on import

**[TRANSLATE-3295](https://jira.translate5.net/browse/TRANSLATE-3295): TermTagger integration - Terms in Source are not identified in target and therefore falsly are flagged "not found"** <br>
FIX: Terms in the segment source may have been falsely flagged as "not found in target"

**[TRANSLATE-3294](https://jira.translate5.net/browse/TRANSLATE-3294): Editor general - Increase systemstatus timeout** <br>
The system-check under preferences may run into a timeout if some services do not respond in a reasonable amount of time, therefore the timeout in the UI is increased and a proper error message is shown.


**[TRANSLATE-3293](https://jira.translate5.net/browse/TRANSLATE-3293): Editor general - Stop trimming leading/trailing whitespaces on segment save** <br>
Leading/trailing whitespaces will no longer be trimmed from the segment on save.

**[TRANSLATE-3287](https://jira.translate5.net/browse/TRANSLATE-3287): Editor general - Protected spaces are being removed automatically, when saving** <br>
FIX: protected spaces may be removed when saving a translated segment in the review

**[TRANSLATE-3286](https://jira.translate5.net/browse/TRANSLATE-3286): Editor general - Error on trying to insert duplicate entry to DB** <br>
Fixed throwing duplicate entry exception in ZfExtended/Models/Entity/Abstract

**[TRANSLATE-3283](https://jira.translate5.net/browse/TRANSLATE-3283): TermPortal - Set for rejected term automatically the term attribute normativeAuthorization "deprecatedTerm"** <br>
Fix for the problem where for a rejected term automatically the term attribute "normativeAuthorization" was not set to "deprecatedTerm".

**[TRANSLATE-3278](https://jira.translate5.net/browse/TRANSLATE-3278): VisualReview / VisualTranslation - VisualReview sym link clean up** <br>
Visual (code quality improvement): 
- symbolic links are created as relative paths to simplify moving the data or application directory
- improve cleanup of symbolic link

**[TRANSLATE-3250](https://jira.translate5.net/browse/TRANSLATE-3250): TermPortal - Term translation project creation fails silently** <br>
TermTranslation-project creation was just failing silently, if the PM user which was set as default PM for TermTranslation-projects was deleted before. 

**[TRANSLATE-3058](https://jira.translate5.net/browse/TRANSLATE-3058): Main back-end mechanisms (Worker, Logging, etc.), SpellCheck (LanguageTool integration), TermTagger integration - Simplify termtagger and spellcheck workers** <br>
translate5 - 6.0.0: 
Improvement: TermTagger Worker & SpellCheck Worker are not queued dynamically anymore but according to the configured slots & looping through segments. This reduces deadlocks & limits processes 
translate5 - 6.1.0:
Improve behavior of Processing-State queries regarding deadlocks

**[TRANSLATE-2063](https://jira.translate5.net/browse/TRANSLATE-2063): Import/Export - Enable parallele use of multiple okapi versions to fix Okapi bugs** <br>
NEXT: Fixed docker autodiscovery not to overwrite existing config.
5.9.0: Added dedicated CLI commands to maintain Okapi config.
5.7.6: Multiple okapi instances can be configured and used for task imports.
6.1.0: Enhancement: Fixed naming scheme for the keys of the Okapi Server Configuration entries


## [6.0.2] - 2023-04-20

### Important Notes:
#### [TRANSLATE-3048](https://jira.translate5.net/browse/TRANSLATE-3048)
This Feature changes the way the T5-API can be accessed: An App-Token MUST be used from now on to request the API
externally. t5connect must be setup to use App-Tokens !!
 


### Bugfixes
**[TRANSLATE-3285](https://jira.translate5.net/browse/TRANSLATE-3285): Export - Lock task on export translator package** <br>
Improve task locking when exporting translator package.

**[TRANSLATE-3282](https://jira.translate5.net/browse/TRANSLATE-3282): TermPortal - Terms are not marked on imports with termcollection auto association** <br>
Instances with default term collections associated it might happen that terms were not checked automatically after import.

**[TRANSLATE-3281](https://jira.translate5.net/browse/TRANSLATE-3281): LanguageResources - Wildcard escape in collection search** <br>
Mysql wildcards will be escaped when searching terms in term collection.

**[TRANSLATE-3280](https://jira.translate5.net/browse/TRANSLATE-3280): Editor general - Fixing UI errors** <br>
- Fix for error when switching customer in add task window and quickly closing the window with esc key. (me.selectedCustomersConfigStore is null)
- Fix for error when "segment qualities" are still loading but the user already left/close the task. (this.getMetaFalPosPanel() is undefined)
- Right clicking on disabled segment with spelling error leads to an error. (c is null_in_quality_context.json)
- Applying delayed quality styles to segment can lead to an error in case the user left the task before the callback/response is evaluated.

**[TRANSLATE-3279](https://jira.translate5.net/browse/TRANSLATE-3279): TermPortal - Logic of camelCase detection needs to be fixed** <br>
Fixed the way of how picklist values are shown in GUI

**[TRANSLATE-3277](https://jira.translate5.net/browse/TRANSLATE-3277): Main back-end mechanisms (Worker, Logging, etc.) - Division by zero error when calculating progress** <br>
Solves problem with workers crash when calculating progress.

**[TRANSLATE-3275](https://jira.translate5.net/browse/TRANSLATE-3275): Editor general - Improve logging for no access errors on opened tasks** <br>
The user will get sometimes no access error when task is being opened for editing.  For that reason, the front-end error logging is improved.

**[TRANSLATE-3256](https://jira.translate5.net/browse/TRANSLATE-3256): Editor general - False positive menu option stays visible on leaving the task** <br>
Fixed floating FalsePositives-panel problem

**[TRANSLATE-3227](https://jira.translate5.net/browse/TRANSLATE-3227): Task Management - Horizontal scrollbar in project wizard pop-up is missing** <br>
Overflow menu is now turned On for most toolbars and tab-bars, including project wizard

**[TRANSLATE-3061](https://jira.translate5.net/browse/TRANSLATE-3061): Test framework - FIX API Tests** <br>
translate5 - 5.7.13
 - Code refactoring for the testing environment. Improvements and fixes for API test cases.
translate5 - 6.0.2
 - Fixed config loading level in testing environment 


**[TRANSLATE-3048](https://jira.translate5.net/browse/TRANSLATE-3048): Editor general - CSRF Protection for translate5** <br>
translate5 - 6.0.0
- CSRF (Cross Site Request Forgery) Protection for translate5 with a CSRF-token. Important info for translate5 API users: externally the translate5 - API can only be accessed with an App-Token from now on.

translate5 - 6.0.2
- remove CSRF protection for automated cron calls

**[TRANSLATE-2993](https://jira.translate5.net/browse/TRANSLATE-2993): LanguageResources, TermPortal - Invalid TBX causes TermPortal to crash** <br>
Empty termEntry/language/term attribute-nodes are now skipped if found in TBX-files

**[TRANSLATE-2396](https://jira.translate5.net/browse/TRANSLATE-2396): Installation & Update - Diverged GUI and Backend version after update** <br>
translate5 - 5.1.1 - The user gets an error message if the version of the GUI is older as the backend - which may happen after an update in certain circumstances. Normally this is handled due the usage of the maintenance mode.

translate5 - 6.0.2 - Fixed missing version header on error handling. Additional fix: Return JSON on rest based exceptions instead just a string


## [6.0.1] - 2023-04-11

### Important Notes:
 


### Bugfixes
**[TRANSLATE-3274](https://jira.translate5.net/browse/TRANSLATE-3274): Authentication - Info that app token authentication was used is lost** <br>
In some circumstances the info that a request was authenticated via app token is lost and therefore CRSF protection is blocking requests.

**[TRANSLATE-3273](https://jira.translate5.net/browse/TRANSLATE-3273): Authentication - Security fixes against hacking translate5 for CSRF (Cross-site request forgery)** <br>
CSRF protection was unintentionally blocking some live communication between browser and translate5 server. In detail: session re-sync endpoint needed for re-sync to MessageBus socket server after network reconnect. 
An exception for that endpoint was added.


## [6.0.0] - 2023-04-10

### Important Notes:
#### [TRANSLATE-3268](https://jira.translate5.net/browse/TRANSLATE-3268)
The translate5 system log is now purged to 6 weeks in the past each night.

#### [TRANSLATE-3259](https://jira.translate5.net/browse/TRANSLATE-3259)
Please note that it will work properly only with the t5memory version >=0.4.36

#### [TRANSLATE-3233](https://jira.translate5.net/browse/TRANSLATE-3233)
IMPORTANT: update docker compose files to the new pdfconverter.

#### [TRANSLATE-3048](https://jira.translate5.net/browse/TRANSLATE-3048)
This Feature changes the way the T5-API can be accessed: An App-Token MUST be used from now on to request the API
externally. t5connect must be setup to use App-Tokens !!
 


### Added
**[TRANSLATE-3234](https://jira.translate5.net/browse/TRANSLATE-3234): API - API Improvements for Figma** <br>
The API endpoint for langauges (/language) now respects locale, targetLang can be sent as comma-separated array

**[TRANSLATE-3233](https://jira.translate5.net/browse/TRANSLATE-3233): VisualReview / VisualTranslation - Replace visualbrowser container with our own Dockerized Headless Browser** <br>
VisualReview plugin text reflow and text resize code moved to a separate repository. 
Visualbrowser is replaced by translate5/visualconverter image.
Config runtimeOptions.plugins.VisualReview.dockerizedHeadlessChromeUrl is now replaced by runtimeOptions.plugins.VisualReview.visualConverterUrl


### Changed
**[TRANSLATE-3252](https://jira.translate5.net/browse/TRANSLATE-3252): VisualReview / VisualTranslation - Add Info/Warning if Font's could not be parsed in a PDF based visual** <br>
Add info/warning for fonts that could not be properly evaluated in the conversion of a PDF as source of the visual


### Bugfixes
**[TRANSLATE-3270](https://jira.translate5.net/browse/TRANSLATE-3270): Editor general - Several rootcause fixes** <br>
Fixed: Frontend error "me.editor is null" in Qualities Filter-Panel
Fixed: Frontend error "Cannot read properties of null (reading 'filter')" in Qualities Filter-Panel
Fixed: Frontend error "Cannot read properties of undefined (reading 'down')" when right-clicking segments

**[TRANSLATE-3268](https://jira.translate5.net/browse/TRANSLATE-3268): Main back-end mechanisms (Worker, Logging, etc.) - Automatic system log purge to a configurable amount of weeks in the past** <br>
To reduce DB load the translate5 system log is now purged to 6 weeks in the past each night.

**[TRANSLATE-3265](https://jira.translate5.net/browse/TRANSLATE-3265): Import/Export - Folder evaluated as file in zip data provider** <br>
Fix a problem with Zip archive content validator.

**[TRANSLATE-3260](https://jira.translate5.net/browse/TRANSLATE-3260): TrackChanges - Disable TrackChanges for ja, ko, zh, vi completely to fix char input problems** <br>
Added option to completely disable TrackChanges per language ('ko', 'ja', ...) to solve problems with character input in these languages
- FIX config-level for deactivating target languages

**[TRANSLATE-3259](https://jira.translate5.net/browse/TRANSLATE-3259): MatchAnalysis & Pretranslation - Pivot pre-translation is not paused while tm is importing** <br>
Pivot worker now has the pause mechanism which waits until all related t5memory language resources are available.
This will work properly only with t5memory version greater then 0.4.36

**[TRANSLATE-3258](https://jira.translate5.net/browse/TRANSLATE-3258): file format settings - T5 Segmentation Rules: Add rules for  "z. B." in parallel with "z.B."** <br>
Added Segmentation rules to not break after "z. B." just like with "z.B."

**[TRANSLATE-3058](https://jira.translate5.net/browse/TRANSLATE-3058): Main back-end mechanisms (Worker, Logging, etc.), SpellCheck (LanguageTool integration), TermTagger integration - Simplify termtagger and spellcheck workers** <br>
Improvement: TermTagger Worker & SpellCheck Worker are not queued dynamically anymore but according to the configured slots & looping through segments. This reduces deadlocks & limits processes 

**[TRANSLATE-3048](https://jira.translate5.net/browse/TRANSLATE-3048): Editor general - CSRF Protection for translate5** <br>
CSRF (Cross Site Request Forgery) Protection for translate5 with a CSRF-token. Important info for translate5 API users: externally the translate5 - API can only be accessed with an App-Token from now on.

**[TRANSLATE-2592](https://jira.translate5.net/browse/TRANSLATE-2592): TrackChanges - Reduce and by default hide use of TrackChanges in the translation step** <br>
Regarding translation and track changes: changes are only recorded for pre-translated segments and changes are hidden by default for translators (and can be activated by the user in the view modes drop-down of the editor)




## [5.9.4] - 2023-04-03

### Important Notes:
 


### Changed
**[TRANSLATE-3249](https://jira.translate5.net/browse/TRANSLATE-3249): LanguageResources - Add documentation about t5memory status request processing in t5** <br>
Response from t5memory for the `status` API call was changed so t5memory connector has been modified to parse the status of the translation memory accordingly.
The documentation about the t5memory status processing is also added and can be found here:
https://confluence.translate5.net/display/TAD/Status+response+parsing


**[TRANSLATE-3241](https://jira.translate5.net/browse/TRANSLATE-3241): OpenTM2 integration - T5memory automatic reorganize and via CLI** <br>
Added two new commands: 
  - t5memory:reorganize for manually triggering translation memory reorganizing
  - t5memory:list - for listing all translation memories with their statuses
Add new config for setting up error codes from t5memory that should trigger automatic reorganizing
Added automatic translation memory reorganizing if appropriate error appears in response from t5memory engine



### Bugfixes
**[TRANSLATE-3260](https://jira.translate5.net/browse/TRANSLATE-3260): TrackChanges - Disable TrackChanges for ja, ko, zh, vi completely to fix char input problems** <br>
Added option to completely disable TrackChanges per language ('ko', 'ja', ...) to solve problems with character input in these languages

**[TRANSLATE-3257](https://jira.translate5.net/browse/TRANSLATE-3257): GroupShare integration - Segments are not saved back to GS** <br>
Segments could not be not saved back to GroupShare. Passing the optional configuration confirmationLevels: ['Unspecified'] did solve the problem.

**[TRANSLATE-3255](https://jira.translate5.net/browse/TRANSLATE-3255): GroupShare integration - Fix segment updating also if segment has not tags in source but in target** <br>
If the target text has tags but source not, the segment could not be saved to groupshare TM.

**[TRANSLATE-3231](https://jira.translate5.net/browse/TRANSLATE-3231): Export - No download progress is shown for translator packages** <br>
Waiting screen will be shown on package export.
Fix for package export and re-import API responses.

**[TRANSLATE-3111](https://jira.translate5.net/browse/TRANSLATE-3111): Editor general - Editor: matchrate filter search problem** <br>
Fixed problem that segment filter was not applied if a range was set too quickly on a MatchRate-column's filter.
Fix error produced by the filters when leaving the task.


## [5.9.3] - 2023-03-27

### Important Notes:
 


### Changed
**[TRANSLATE-3249](https://jira.translate5.net/browse/TRANSLATE-3249): LanguageResources - Add documentation about t5memory status request processing in t5** <br>
Response from t5memory for the `status` API call was changed so t5memory connector has been modified to parse the status of the translation memory accordingly


### Bugfixes
**[TRANSLATE-3254](https://jira.translate5.net/browse/TRANSLATE-3254): Editor general - Sort the target languages alphabetically on task creation** <br>
Sorting of the target languages will be only done when task is created via translate5 UI.

**[TRANSLATE-3231](https://jira.translate5.net/browse/TRANSLATE-3231): Export - No download progress is shown for translator packages** <br>
Waiting screen will be shown on package export.


## [5.9.2] - 2023-03-16

### Important Notes:
 


### Bugfixes
**[TRANSLATE-3248](https://jira.translate5.net/browse/TRANSLATE-3248): Authentication - Authentication for users with empty db password** <br>
Fix a problem when error is produced on authentication for users with empty database password.

**[TRANSLATE-3247](https://jira.translate5.net/browse/TRANSLATE-3247): TermPortal - Duplicated attributes cleanup problem** <br>
A script to cleanup duplicated term attributes did not run successfully in some circumstances and was blocking the whole DB update procedure.

**[TRANSLATE-3238](https://jira.translate5.net/browse/TRANSLATE-3238): Task Management - Not supported file format for translator package: Disable download button** <br>
Translator package export/import buttons will be disabled in case the task does not allow or support export this.


## [5.9.1] - 2023-03-14

### Changed
**[TRANSLATE-3245](https://jira.translate5.net/browse/TRANSLATE-3245): VisualReview / VisualTranslation - Replace webserver in pdfconverter to nginx** <br>
Fixed problem which caused pdfconverter container fail to start


 



### Bugfixes
**[TRANSLATE-3117](https://jira.translate5.net/browse/TRANSLATE-3117): Import/Export - translator package** <br>
5.9.0: Editor users are now able to download a zip package including everything needed to translate a job outside of translate5 and afterwards update the task with it.
5.9.1: Fix - enable reimport package for non pm users

**[TRANSLATE-3242](https://jira.translate5.net/browse/TRANSLATE-3242): MatchAnalysis & Pretranslation - Fix match analysis on API usage** <br>
- Task is now locked immediately after match analysis is scheduled.
- PauseMatchAnalysis worker now returns an error in case after maximum wait time language resource is still not available.
- Documentation updated

**[TRANSLATE-3240](https://jira.translate5.net/browse/TRANSLATE-3240): TBX-Import, TermPortal - Re-create term portal disk images on re-import** <br>
Images missing on disk are now recreated during tbx import

**[TRANSLATE-3239](https://jira.translate5.net/browse/TRANSLATE-3239): Authentication - Unify HTTPS checks for usage behind proxy with ssl offloaded** <br>
Fix that SSO and CLI auth:impersonate is working behind a proxy with SSL offloading.

**[TRANSLATE-3237](https://jira.translate5.net/browse/TRANSLATE-3237): Configuration, Editor general - UI: User config requested before loaded** <br>
Fixed bug popping sometimes if config store is not yet loaded

**[TRANSLATE-3235](https://jira.translate5.net/browse/TRANSLATE-3235): Okapi integration, TermPortal - Internal term translations should always use the system default bconf** <br>
System default bconf is now used for termtranslation-tasks


## [5.9.0] - 2023-03-07

### Important Notes:

#### [TRANSLATE-2185](https://jira.translate5.net/browse/TRANSLATE-2185) - SETUP
Before updating from a version < 5.8.0 see https://confluence.translate5.net/x/BYAIG

#### [TRANSLATE-3205](https://jira.translate5.net/browse/TRANSLATE-3205) - SETUP
On-premise docker Installations: Update the php container with docker compose pull php too.
On-premise legacy Installations: ensure that apache module mod_headers is enabled (s2enmod headers).

#### [TRANSLATE-3204](https://jira.translate5.net/browse/TRANSLATE-3204) - changed behaviour
PMs are now also allowed to download the import archive of the imported task.

#### [TRANSLATE-3192](https://jira.translate5.net/browse/TRANSLATE-3192) - changed behaviour
The system default of multi user mode for tasks changed to "Simultaneous" from "Cooperative".  If you want to keep it as it is, you need to set it back to its old value by hand. If it was set to "competitive" by you, the value will stay as it was before.

#### [TRANSLATE-3117](https://jira.translate5.net/browse/TRANSLATE-3117) - changed behaviour
IMPORTANT: This enables the translator and the reviewer, to download a translator package or the translated (or un-translated) bilingual file. This was not possible previously. It does not change something regarding confidentiality, because also previously (of course) all segments and TM entries were available to the translator. As they have to be (otherwise he can not translate). Yet it makes it more easy now, to get them on your desktop.

#### [TRANSLATE-3097](https://jira.translate5.net/browse/TRANSLATE-3097) - changed user interface
The usability of the editor was enhanced very much for translators and reviewers in the normal and details view mode of the editor.
The action icons to handle the currently open segment were moved to the top. Same for the special characters. Users can now select, what icons they want to have in the top toolbar and what in a drop-down list. The repetition editor settings were moved to the left into the "view modes" drop-down, which was renamed to "settings". 
In the right panel the terminology was moved to its own tab and the segment meta data tab was renamed to "Quality assurance".
As a result there is much more space for terminology and QA in the right panel.


### Added
**[TRANSLATE-3223](https://jira.translate5.net/browse/TRANSLATE-3223): Editor general - Create a new user role to force the editor only mode** <br>
In editor-only-mode (leave application button instead back to tasklist) admins are now allowed to switch back to task list. 
For other users an optional role (editor-only-override) is added. This enables a hybrid setup of editor only mode and default mode with task overview.

**[TRANSLATE-3205](https://jira.translate5.net/browse/TRANSLATE-3205): API - Make T5 API ready for use via Browser (full CORS support)** <br>
IMPROVEMENT: Full CORS support to enable API-usage via JS when authenticating with an App-Token 

**[TRANSLATE-3188](https://jira.translate5.net/browse/TRANSLATE-3188): LanguageResources, MatchAnalysis & Pretranslation - Speed up internal fuzzy analysis by copying binary files** <br>
Now during match analyzing translation memory is cloned using the new t5memory API endpoint instead of export/import, which significantly increases the speed of cloning.

**[TRANSLATE-3117](https://jira.translate5.net/browse/TRANSLATE-3117): Import/Export - translator package** <br>
Editor users are now able to download a zip package including everything needed to translate a job outside of translate5 and afterwards update the task with it.

**[TRANSLATE-3097](https://jira.translate5.net/browse/TRANSLATE-3097): Editor general - Enhance editor menu usability** <br>
Enhanced editor menu usability. For details please see "important release notes".

**[TRANSLATE-2994](https://jira.translate5.net/browse/TRANSLATE-2994): LanguageResources, OpenTM2 integration - t5memory roll-out** <br>
5.9.0: FIX: increase timeout
5.7.13: Added new cli command for migrating OpenTM2 to t5memory.
Check the usage of 
./translate5.sh help otm2:migrate

**[TRANSLATE-2185](https://jira.translate5.net/browse/TRANSLATE-2185): Installation & Update - Prepare translate5 for usage with docker** <br>
5.9.0: Introduce service checks if the configured services are working
5.8.1: Introducing the setup of translate5 and the used services as docker containers.


### Changed
**[TRANSLATE-3216](https://jira.translate5.net/browse/TRANSLATE-3216): VisualReview / VisualTranslation - Add Version Endpoint to PDF-Converter** <br>
Added new endpoint to pdfconverter API which returns list of libraries versions.

**[TRANSLATE-3204](https://jira.translate5.net/browse/TRANSLATE-3204): Export - PMs and PMlights should also be able to download the import archive** <br>
PMs should also be allowed to download the import archive of the imported task. Previously only admins were allowed to do that.

**[TRANSLATE-3192](https://jira.translate5.net/browse/TRANSLATE-3192): Task Management - Set default for multi usage mode to "Simultaneous"** <br>
Change default value for task initial usage mode from "Cooperative" to "Simultaneous".

**[TRANSLATE-3183](https://jira.translate5.net/browse/TRANSLATE-3183): API - Enable API-Usage via JS when using an App-Token** <br>
IMPROVEMENT: Sending Access-Control header to allow API-usage via JS when authenticating with an App-Token

**[TRANSLATE-3072](https://jira.translate5.net/browse/TRANSLATE-3072): file format settings - Usability enhancements for file format settings** <br>
ENHANCEMENT: Improved usability of File format and segmentation settings UI: better localization, more tooltips, some bugfixes


### Bugfixes
**[TRANSLATE-3228](https://jira.translate5.net/browse/TRANSLATE-3228): Export - Doubled language code in filename of translate5 export zip** <br>
Removed doubled language code in filename of translate5 export zip

**[TRANSLATE-3224](https://jira.translate5.net/browse/TRANSLATE-3224): Editor general, InstantTranslate - Column not found error when creating a project on fresh Docker install** <br>
Add missing column to LEK_languageresources table if installing without InstantTranslate.

**[TRANSLATE-3219](https://jira.translate5.net/browse/TRANSLATE-3219): Workflows - Workflow notification json decode problems** <br>
When using JSON based workflow notification parameters it might come to strange JSON syntax errors.

**[TRANSLATE-3217](https://jira.translate5.net/browse/TRANSLATE-3217): Editor general - RootCause: Invalid JSON - answer seems not to be from translate5 - x-translate5-version header is missing** <br>
In 5.9.0: added some debug code.

**[TRANSLATE-3215](https://jira.translate5.net/browse/TRANSLATE-3215): TermPortal - RootCause: filter window error** <br>
In 5.9.0: added some debug code.

**[TRANSLATE-3214](https://jira.translate5.net/browse/TRANSLATE-3214): TermPortal - RootCause: locale change in attributes management** <br>
FIXED: bug popping after GUI locale change in attributes management

**[TRANSLATE-3209](https://jira.translate5.net/browse/TRANSLATE-3209): Editor general - RootCause error: vm is null** <br>
Fix for UI error when task progress is refreshed but the user opens task for editing.

**[TRANSLATE-3208](https://jira.translate5.net/browse/TRANSLATE-3208): Editor general - RootCause error: Cannot read properties of undefined (reading 'removeAll')** <br>
Fix for UI error when removing project.

**[TRANSLATE-3203](https://jira.translate5.net/browse/TRANSLATE-3203): SpellCheck (LanguageTool integration) - RootCause error: Cannot read properties of undefined (reading 'message')** <br>
Fix for UI error when accepting or changing spell check recommendations

**[TRANSLATE-3199](https://jira.translate5.net/browse/TRANSLATE-3199): Task Management - RootCause-error: rendered block refreshed at 0 rows** <br>
FIXED: error unregularly/randomly popping on task import and/or initial projects grid load

**[TRANSLATE-3195](https://jira.translate5.net/browse/TRANSLATE-3195): Editor general - RootCause-error: PageMap asked for range which it does not have** <br>
Fixed segments grid error popping on attempt to scroll to some position while (re)loading is in process

**[TRANSLATE-3194](https://jira.translate5.net/browse/TRANSLATE-3194): Editor general, OpenTM2 integration - Front-end error on empty translate5 memory status response** <br>
Fix for front-end error on translate5 memory status check.

**[TRANSLATE-3189](https://jira.translate5.net/browse/TRANSLATE-3189): MatchAnalysis & Pretranslation, Test framework - Reduce Match analysis test complexity** <br>
Improve the Analysis and pre-translation tests.

**[TRANSLATE-3185](https://jira.translate5.net/browse/TRANSLATE-3185): User Management - Error message for duplicate user login** <br>
Improve failure error messages when creation or editing a user.

**[TRANSLATE-3181](https://jira.translate5.net/browse/TRANSLATE-3181): Editor general - Pasted content inside concordance search is not used for searching** <br>
Fix for a problem where concordance search was not triggered when pasting content in one of the search fields and then clicking on the search button.

**[TRANSLATE-3062](https://jira.translate5.net/browse/TRANSLATE-3062): Installation & Update, Test framework - Test DB reset and removement of mysql CLI dependency** <br>
5.9.0: database dump and cron invocation via CLI possible
5.7.13: Removed the mysql CLI tool as dependency from translate5 PHP code.

**[TRANSLATE-3052](https://jira.translate5.net/browse/TRANSLATE-3052): LanguageResources - Clean resource assignments after customer is removed** <br>
5.9.0: Bugfix
5.8.5: Removing customer from resource will be prevented in case this resource is used/assigned to a task.

**[TRANSLATE-2063](https://jira.translate5.net/browse/TRANSLATE-2063): Import/Export - Enable parallele use of multiple okapi versions to fix Okapi bugs** <br>
5.9.0: Added dedicated CLI commands to maintain Okapi config.
5.7.6: Multiple okapi instances can be configured and used for task imports.


## [5.8.6] - 2023-02-01

### Important Notes:
#### [TRANSLATE-3123](https://jira.translate5.net/browse/TRANSLATE-3123)
Users who already adjusted the checkboxes, indicating whether some attribute datatype is enabled for some TermCollection - have to redo this

#### [TRANSLATE-3039](https://jira.translate5.net/browse/TRANSLATE-3039)
The password rules were changed: The password length must be at least 12 characters and must have mixed content (lower / uppercase / numbers). 
This affects new users and when the password is changed!
 


### Changed
**[TRANSLATE-3039](https://jira.translate5.net/browse/TRANSLATE-3039): Editor general - Improve password rules (4.7)** <br>
The current password rule (just 8 characters) was to lax. The new user password roles requirements can be found in this link: https://confluence.translate5.net/x/AYBVG (released in 5.8.5, fixes in 5.8.6)



### Bugfixes
**[TRANSLATE-3180](https://jira.translate5.net/browse/TRANSLATE-3180): Main back-end mechanisms (Worker, Logging, etc.) - Changing user association in import wizard does not take effect** <br>
Fix for a problem when user association is modified in the import wizard.

**[TRANSLATE-3178](https://jira.translate5.net/browse/TRANSLATE-3178): VisualReview / VisualTranslation - Visual Video import: Blank Segments cause subtitle-number to sip into next segment and empty segment to be skipped** <br>
FIX: Video SRT Import: Subtitles with Timestamp but without Content caused Quirks in the Segmentation

**[TRANSLATE-3176](https://jira.translate5.net/browse/TRANSLATE-3176): Export, Main back-end mechanisms (Worker, Logging, etc.) - Filenames with quotes are truncated upon download** <br>
Quotes in the task name led to cut of filenames on export. Fixed in 5.8.6.

**[TRANSLATE-3175](https://jira.translate5.net/browse/TRANSLATE-3175): LanguageResources - Need to allow importing new file only after importing is finished** <br>
Language resource import and export buttons are disabled while importing is in progress

**[TRANSLATE-3123](https://jira.translate5.net/browse/TRANSLATE-3123): Import/Export - Tbx import: handling duplicated attributes** <br>
TBX import: removed term-level attributes duplicates


## [5.8.5] - 2023-01-24

### Important Notes:
#### [TRANSLATE-3039](https://jira.translate5.net/browse/TRANSLATE-3039)
The password rules were changed: The password length must be at least 12 characters and must have mixed content (lower / uppercase / numbers). 
This affects new users and when the password is changed!
 


### Added
**[TRANSLATE-3172](https://jira.translate5.net/browse/TRANSLATE-3172): file format settings - XML File Filter Settings for Figma** <br>
Added XML filter for Figma (collaborative software)

**[TRANSLATE-3136](https://jira.translate5.net/browse/TRANSLATE-3136): MatchAnalysis & Pretranslation - Show analysis results for editor users** <br>
Analysis results are available for all users with editor role.

**[TRANSLATE-3054](https://jira.translate5.net/browse/TRANSLATE-3054): Auto-QA - Batch-set multiple AutoQA errors of type LanguageTool or Terminology to false positive** <br>
It is now possible to batch-set false-positive for similar autoQA-qualities


### Changed
**[TRANSLATE-3170](https://jira.translate5.net/browse/TRANSLATE-3170): VisualReview / VisualTranslation - Improve error-logging for pdfconverter** <br>
Improve logging and data clean up in external service PDFconverter

**[TRANSLATE-3169](https://jira.translate5.net/browse/TRANSLATE-3169): SpellCheck (LanguageTool integration) - Make 'non-conformance' error to be counted** <br>
'non-conformance' errors detected by LanguageTool are now counted by AutoQA

**[TRANSLATE-3039](https://jira.translate5.net/browse/TRANSLATE-3039): Editor general - Improve password rules (4.7)** <br>
The current password rule (just 8 characters) was to lax. The new user password roles requirements can be found in this link: https://confluence.translate5.net/x/AYBVG

**[TRANSLATE-294](https://jira.translate5.net/browse/TRANSLATE-294): Editor general - Add the task guid in the task overview as hidden column for better debugging** <br>
Add the TaskGuid as by default hidden column to the task grid.


### Bugfixes
**[TRANSLATE-3174](https://jira.translate5.net/browse/TRANSLATE-3174): Auto-QA, Import/Export - Ignore protected character tags (mostly whitespace) from tagcheck** <br>
Several fixes in context of tag check of data coming from a language resource containing several tags and whitespaces converted to translate5 space tags.

**[TRANSLATE-3173](https://jira.translate5.net/browse/TRANSLATE-3173): file format settings - Change of file extension association does not refresh grid** <br>
FIX: Bconf-Grid "Extensions" column was not updated after custom filters have been added or removed

**[TRANSLATE-3171](https://jira.translate5.net/browse/TRANSLATE-3171): LanguageResources - Additional tags from manually taken over TM match is triggering tag check** <br>
Tagcheck was producing a false positive on saving a manually taken over segment from LanguageResource.

**[TRANSLATE-3168](https://jira.translate5.net/browse/TRANSLATE-3168): TermPortal - Terms transfer source language problem** <br>
Terms transfer sublanguage problem fixed

**[TRANSLATE-3167](https://jira.translate5.net/browse/TRANSLATE-3167): TBX-Import - Logger is missing in TbxBinaryDataImport class** <br>
Fix problem with tbx import logging in binary data.

**[TRANSLATE-3166](https://jira.translate5.net/browse/TRANSLATE-3166): TBX-Import - Missing support for TBX-standard tags on tbx-import** <br>
Not all descripGrp tags where imported by the TBX import.

**[TRANSLATE-3165](https://jira.translate5.net/browse/TRANSLATE-3165): TBX-Import - TBX import ignores custom attributes within descrip tags** <br>
term-level attributes using <descrip>-tags are now not ignored on tbx-import

**[TRANSLATE-3163](https://jira.translate5.net/browse/TRANSLATE-3163): Configuration - Typos system configuration texts** <br>
Fix typos and textual inconsistencies in configuration labels and descriptions.

**[TRANSLATE-3161](https://jira.translate5.net/browse/TRANSLATE-3161): InstantTranslate - languageId-problem on opening term in TermPortal** <br>
Fix 'Open term in TermPortal' when using sublanguages.

**[TRANSLATE-3160](https://jira.translate5.net/browse/TRANSLATE-3160): Editor general - Keyboard shortcut for concordance search not working as described** <br>
Fix the field to focus on F3 shortcut usage.

**[TRANSLATE-3159](https://jira.translate5.net/browse/TRANSLATE-3159): LanguageResources - Server Error 500 when filtering language resources** <br>
Fixed server error popping on filtering languageresources by name and customer

**[TRANSLATE-3158](https://jira.translate5.net/browse/TRANSLATE-3158): SpellCheck (LanguageTool integration), TermTagger integration - Task is unusable due status error caused by a recoverable error** <br>
Task is not unusable anymore in case of termtagger-worker exception

**[TRANSLATE-3155](https://jira.translate5.net/browse/TRANSLATE-3155): Task Management - Ending a task, that is currently in task state edit is possible** <br>
If task is opened for editing it's not possible to change its state to ended

**[TRANSLATE-3154](https://jira.translate5.net/browse/TRANSLATE-3154): Auto-QA - Consistency check should be case sensitive** <br>
Consistency check is now case-sensitive

**[TRANSLATE-3149](https://jira.translate5.net/browse/TRANSLATE-3149): Task Management, WebSocket Server - 403 Forbidden messages in opened task** <br>
Users are getting multiple 403 Forbidden error messages.
On instances with a lot of users not logging out, this might also happen often due a bug in removing such stalled sessions. This is fixed in 5.8.5.
For users with unstable internet connections this was fixed in 5.8.2.

**[TRANSLATE-3147](https://jira.translate5.net/browse/TRANSLATE-3147): InstantTranslate - Availability time in InstantTranslate makes no sense for IP-based Auth** <br>
Translated file download's 'available until' line is not shown for IP-based users

**[TRANSLATE-3142](https://jira.translate5.net/browse/TRANSLATE-3142): SpellCheck (LanguageTool integration) - Improve user feedback when spellchecker is overloaded** <br>
Improved error message if segment save runs into a timeout.

**[TRANSLATE-3140](https://jira.translate5.net/browse/TRANSLATE-3140): OpenTM2 integration - Evaluate t5memory status on usage** <br>
A new worker was introduced for pausing match analysis if t5memory is importing a file

**[TRANSLATE-3126](https://jira.translate5.net/browse/TRANSLATE-3126): InstantTranslate, TermPortal - Logout on window close also in instanttranslate and termportal** <br>
logoutOnWindowClose-config triggers now logout when last tab is closed not anymore already on first tab.

**[TRANSLATE-3052](https://jira.translate5.net/browse/TRANSLATE-3052): LanguageResources - Clean resource assignments after customer is removed** <br>
Removing customer from resource will be prevented in case this resource is used/assigned to a task.


## [5.8.4] - 2023-01-09

### Important Notes:
 


### Changed
**[TRANSLATE-3157](https://jira.translate5.net/browse/TRANSLATE-3157): SpellCheck (LanguageTool integration) - Summon new SNC-error with the one SpellCheck is already counting** <br>
SNC-error beginnig with  "Dubiose 'Zahl' ..." renamed to "Dubiose Zahl" for being counted as already known to Translate5


### Bugfixes
**[TRANSLATE-3146](https://jira.translate5.net/browse/TRANSLATE-3146): TermPortal - Attribute tooltip has annoying latency** <br>
Tooltips do now have no before-show delay

**[TRANSLATE-3095](https://jira.translate5.net/browse/TRANSLATE-3095): TermPortal - Not all available TermCollections visible in drop-down menu** <br>
Filter window's TermCollection dropdown problem not appearing anymore


## [5.8.3] - 2022-12-26

### Important Notes:
#### [TRANSLATE-3156](https://jira.translate5.net/browse/TRANSLATE-3156)
For API users creating languages via LCID only: 
We updated a bunch of LCIDs. Backup languages table and compare the content with the table after update.
 


### Changed
**[TRANSLATE-3156](https://jira.translate5.net/browse/TRANSLATE-3156): Import/Export - Add missing LCID values** <br>
Update and add missing LCIDs.


### Bugfixes
**[TRANSLATE-3152](https://jira.translate5.net/browse/TRANSLATE-3152): Editor general - Term portlet fix** <br>
5.8.3: Hotfixed the wrong encoding of image tags in the term portlet.

**[TRANSLATE-3151](https://jira.translate5.net/browse/TRANSLATE-3151): Import/Export - Import of xliff file fails because of > inside an attribute value of a ph tag** <br>
Introduce a xmlpreparse config which cleans and normalizes the XML structure of the imported XLF files.


## [5.8.2] - 2022-12-20

### Important Notes:
#### [TRANSLATE-3123](https://jira.translate5.net/browse/TRANSLATE-3123)
Users who already adjusted the checkboxes, indicating whether some attribute datatype is enabled for some TermCollection - have to redo this

#### [TRANSLATE-764](https://jira.translate5.net/browse/TRANSLATE-764)
UPDATE: The format of the export.zip is changing! 
Previously the export.zip was containing a folder named like the taskGuid.
This is removed and the content directories are now directly in export.zip root.
The legacy behaviour can be kept by setting in config runtimeOptions.editor.export.taskguiddirectory to 1
or completely controlled by using a GET parameter taskguiddirectory with 0 or 1 on export URL.
 


### Changed
**[TRANSLATE-764](https://jira.translate5.net/browse/TRANSLATE-764): Import/Export - Restructuring of export.zip** <br>
UPDATE: The content structure of the export zip changed. In the future it does NOT contain any more a folder with the task guid, but directly on the highest level of the zip all files of the task that were translated/reviewed.


### Bugfixes
**[TRANSLATE-3150](https://jira.translate5.net/browse/TRANSLATE-3150): TermPortal - TermPortal: term status tooltips old locale after locale changed** <br>
Search results icons tooltips language is now changed on GUI language change

**[TRANSLATE-3149](https://jira.translate5.net/browse/TRANSLATE-3149): Task Management, WebSocket Server - 403 Forbidden messages in opened task** <br>
Users with an unstable internet connection got multiple 403 Forbidden error messages.

**[TRANSLATE-3145](https://jira.translate5.net/browse/TRANSLATE-3145): TermPortal - TermPortal: problem on creating Chinese term** <br>
fixed problem popping on creating term in Chinese language

**[TRANSLATE-3144](https://jira.translate5.net/browse/TRANSLATE-3144): Export - Task export crashes with apache internal server error - no PHP error** <br>
If the tasks name contains non printable invalid UTF8 characters, the task was not exportable.

**[TRANSLATE-3130](https://jira.translate5.net/browse/TRANSLATE-3130): User Management - Login name with space or maybe other unusual characters causes problems** <br>
User validator was changed to prevent creating users with login name containing a space character

**[TRANSLATE-3123](https://jira.translate5.net/browse/TRANSLATE-3123): Import/Export - Tbx import: handling duplicated attributes** <br>
TBX import: removed term-level attributes duplicates


## [5.8.1] - 2022-12-16 and [5.8.0] - 2022-12-06

### Important Notes:
####  [TRANSLATE-3055](https://jira.translate5.net/browse/TRANSLATE-3055) [TRANSLATE-2185](https://jira.translate5.net/browse/TRANSLATE-2185)
<mark>Before updating see 
[translate5 5.8.0 - needed visualreview to docker migration](https://confluence.translate5.net/x/BYAIG)!</mark>


#### [TRANSLATE-3108](https://jira.translate5.net/browse/TRANSLATE-3108)
API Users should switch to appTokens instead plain password usage! 
This is mandatory with one of the next releases.
For usage see: https://confluence.translate5.net/x/AQAoG


#### [TRANSLATE-764](https://jira.translate5.net/browse/TRANSLATE-764)
The format of the export.zip is changing! 
Previously the export.zip was containing a folder named like the taskGuid.
This is removed and the content directories are now directly in export.zip root.
 


### Added
**[TRANSLATE-3108](https://jira.translate5.net/browse/TRANSLATE-3108): Main back-end mechanisms (Worker, Logging, etc.) - App tokens for API authentication** <br>
Via CLI tool appTokens can now be added to dedicated users. Such app tokens should be used then in the future for authentication via API.

**[TRANSLATE-3069](https://jira.translate5.net/browse/TRANSLATE-3069): LanguageResources - TM pre-translation match rate set to 80 as default** <br>
Enables the minimum value form pre-translate TM match-rate to be configurable for client.

**[TRANSLATE-2185](https://jira.translate5.net/browse/TRANSLATE-2185): Installation & Update - Prepare translate5 for usage with docker** <br>
Introducing the setup of translate5 and the used services as docker containers.

**[TRANSLATE-3055](https://jira.translate5.net/browse/TRANSLATE-3055): VisualReview / VisualTranslation - Connect visual reflow via HTTP to headless browser instance** <br>
* Changed usage of headless Browser for the visual to use a docker-image
* Added own Worker for the Text-Reflow Conversion
* Legacy Cleanup: Changed fallback-implementation for visuals, where the text-reflow fails to use the translate5 standard scroller & icon library. This fixes issues with missing annotation-icons in those cases

**[TRANSLATE-2185](https://jira.translate5.net/browse/TRANSLATE-2185): Installation & Update - Prepare translate5 for usage with docker** <br>
Introducing the setup of translate5 and the used services as docker containers.


### Changed
**[TRANSLATE-3143](https://jira.translate5.net/browse/TRANSLATE-3143): Editor Length Check - Change some default config values for pixel length check** <br>
The settings runtimeOptions.lengthRestriction.automaticNewLineAdding and 
runtimeOptions.lengthRestriction.newLineReplaceWhitespace are set now to off by default. 

**[TRANSLATE-3134](https://jira.translate5.net/browse/TRANSLATE-3134): OpenTM2 integration - Amend translate5 to send appropriate json terminator to t5memory** <br>
Request json sent to t5memory is now pretty printed

**[TRANSLATE-2925](https://jira.translate5.net/browse/TRANSLATE-2925): VisualReview / VisualTranslation - API tests for all types of visuals** <br>
Added API tests for all types of visuals

**[TRANSLATE-764](https://jira.translate5.net/browse/TRANSLATE-764): Import/Export - Restructuring of export.zip** <br>
The content structure of the export zip changed. In the future it does NOT contain any more a folder with the task guid, but directly on the highest level of the zip all files of the task that were translated/reviewed.

**[TRANSLATE-3127](https://jira.translate5.net/browse/TRANSLATE-3127): Editor general - Change the order of form components in "Manual QA inside segment" fieldset** <br>
MQM widget form fields ordering changed

**: VisualReview / VisualTranslation - Make pdf converter reachable via network** <br>
The previous local pdf converter is now reachable as a service via network.


### Bugfixes
**[TRANSLATE-3137](https://jira.translate5.net/browse/TRANSLATE-3137): TermPortal - TermPortal: missing ACL for pure termportal-users** <br>
added missing ACL rules for pure TermPortal users

**[TRANSLATE-3132](https://jira.translate5.net/browse/TRANSLATE-3132): TermPortal - TermPortal: duplicated users in 'Created by' filter** <br>
only distinct user names are now shown in 'Created by' and 'Updated by' filters

**[TRANSLATE-3131](https://jira.translate5.net/browse/TRANSLATE-3131): TermTagger integration - Termtagger not synchronized with Terminology** <br>
Task terminology is now refreshed prior Analyse/Re-check operations

**[TRANSLATE-3129](https://jira.translate5.net/browse/TRANSLATE-3129): Task Management - PM light can not choose different PM for a project** <br>
PmLight user is now allowed to change PM of a project

**[TRANSLATE-3128](https://jira.translate5.net/browse/TRANSLATE-3128): Task Management - PM of task can not be changed to PM light user** <br>
Task can be assigned to pmLight user now

**[TRANSLATE-3133](https://jira.translate5.net/browse/TRANSLATE-3133): TermPortal - TermPortal: sort filterwindow dropdowns alphabetically** <br>
Filter window Clients and TermCollection dropdowns are now sorted alphabetically

**[TRANSLATE-3123](https://jira.translate5.net/browse/TRANSLATE-3123): Import/Export - Tbx import: duplicated attributes should be deleted** <br>
TBX import: removed term-level attributes duplicates



## [5.7.15] - 2022-12-01

### Important Notes:
 


### Bugfixes
**[TRANSLATE-3122](https://jira.translate5.net/browse/TRANSLATE-3122): TermTagger integration - Termportlet in segment-meta-panel mixes all language level attributes up** <br>
The Termportlet in the segment-meta-panel was loading to much data and was mixing up attributes on language level.

**[TRANSLATE-3120](https://jira.translate5.net/browse/TRANSLATE-3120): Editor general - Workfiles not listed in editor** <br>
Fixes problem where the work-files where not listed in editor

**[TRANSLATE-3119](https://jira.translate5.net/browse/TRANSLATE-3119): TermPortal - TermPortal: error popping once attribute disabled** <br>
Fixed error popping on attempt to remove usages of disabled attributes from filter window in case if no filter window exists as no search yet done

**[TRANSLATE-3116](https://jira.translate5.net/browse/TRANSLATE-3116): SpellCheck (LanguageTool integration) - Editor: spellcheck styling breaks custom tags markup** <br>
Fixed spellcheck styles breaking custom tags markup

**[TRANSLATE-3115](https://jira.translate5.net/browse/TRANSLATE-3115): Import/Export - proofread deprecation message was not shown on a task** <br>
The warning that the foldername proofRead is deprecated and should not be used anymore was not logged to a task but only into the system log therefore the PMs did not notice that message.

**[TRANSLATE-3113](https://jira.translate5.net/browse/TRANSLATE-3113): Editor general - Adding MQM tags is not always working** <br>
Fixed adding MQM tags to the latest selected word in the segment editor

**[TRANSLATE-3112](https://jira.translate5.net/browse/TRANSLATE-3112): Editor general - MQM severity is not working properly** <br>
Fix MQM tag severity in tooltip in segments grid

**[TRANSLATE-3111](https://jira.translate5.net/browse/TRANSLATE-3111): Editor general - Editor: matchrate filter search problem** <br>
Fixed problem that segment filter was not applied if a range was set too quickly on a MatchRate-column's filter.

**[TRANSLATE-3110](https://jira.translate5.net/browse/TRANSLATE-3110): TermPortal - TermPortal: batch-editing should be available for termPM* roles only** <br>
BatchEdit-button is now shown for 'TermPM' and 'TermPM (all clients)' user roles only


## [5.7.14] - 2022-11-24

### Important Notes:
#### [TRANSLATE-2551](https://jira.translate5.net/browse/TRANSLATE-2551)
Changements in "/editor/file" API endpoint - normally there was no practical use case to access it via external API - though we want to mention here that the API endpoint file was renamed to /editor/filetree.
 


### Added
**[TRANSLATE-3013](https://jira.translate5.net/browse/TRANSLATE-3013): TermPortal - TermPortal: Define available attributes** <br>
TermPortal: added ability to define which attributes are available in which TermCollections

**[TRANSLATE-2551](https://jira.translate5.net/browse/TRANSLATE-2551): Import/Export - Update Task with xliff** <br>
Enable existing file to be replaced and with this the segments will be updated in the task.


### Changed
**[TRANSLATE-3101](https://jira.translate5.net/browse/TRANSLATE-3101): TermTagger integration - Change TermImportController to be accessible by cron** <br>
editor/plugins_termimport_termimport/filesystem and editor/plugins_termimport_termimport/crossapi actions are now protected based on the calling IP address (cronIP)

**[TRANSLATE-3100](https://jira.translate5.net/browse/TRANSLATE-3100): Editor general - CronIp improvement** <br>
Configuration runtimeOptions.cronIP now supports: 
  - multiple comma-separated values
  - IP with subnet (CIDR)
  - domain names

**[TRANSLATE-3099](https://jira.translate5.net/browse/TRANSLATE-3099): Editor general - IP-authentication is not working in docker environment** <br>
Add a new configuration value to enable the usage of IP authentication behind a local proxy.

**[TRANSLATE-3092](https://jira.translate5.net/browse/TRANSLATE-3092): Test framework - Test API: Implement status-check loop for tbx-reimport** <br>
Test API: Status-check loop for tbx reimport implemented

**[TRANSLATE-3086](https://jira.translate5.net/browse/TRANSLATE-3086): TermPortal - Termportal: add introduction window with embedded youtube video** <br>
TermPortal: introduction dialog with youtube video is now shown once TermPortal is opened


### Bugfixes
**[TRANSLATE-3107](https://jira.translate5.net/browse/TRANSLATE-3107): TermPortal, TermTagger integration - TBX import with huge image nodes fail** <br>
TBX files with huge images inside could crash the TBX import leading to incomplete term collections.

**[TRANSLATE-3106](https://jira.translate5.net/browse/TRANSLATE-3106): Editor general - Prevent Google automatic site translation** <br>
Added Metatag to prevent automatic page translation in Chrome & Firefox

**[TRANSLATE-3105](https://jira.translate5.net/browse/TRANSLATE-3105): Export - Export of OKAPI tasks may generate wrong warning about tag-errors** <br>
FIX: Exporting a task generated with OKAPI may caused falsely warnings about tag-errors

**[TRANSLATE-3104](https://jira.translate5.net/browse/TRANSLATE-3104): Configuration - Implement a simple key value config editor for map types** <br>
Added editor for configurations of type json map. Therefore changed `runtimeOptions.lengthRestriction.pixelMapping` to be visible and editable in UI

**[TRANSLATE-3098](https://jira.translate5.net/browse/TRANSLATE-3098): Editor general - Enable qm config is not respected in task meta panel** <br>
The config for disabling segment qm panel will be evaluated now.

**[TRANSLATE-3091](https://jira.translate5.net/browse/TRANSLATE-3091): TermPortal - TermPortal: RootCause error shown while browsing crossReference** <br>
Fixed bug, happening on attempt to navigate to crossReference

**[TRANSLATE-3090](https://jira.translate5.net/browse/TRANSLATE-3090): TermPortal - TermPortal: change DE-placeholder for noTermDefinedFor-field in filter-window** <br>
Some wordings improved for TermPortal GUI

**[TRANSLATE-3089](https://jira.translate5.net/browse/TRANSLATE-3089): TermPortal - TermPortal: nothing happens on attribute save in batch editing mode** <br>
Fixed termportal batch-editing bug

**[TRANSLATE-3088](https://jira.translate5.net/browse/TRANSLATE-3088): Repetition editor - Repetition editor: missing css class for context rows** <br>
Tags styling for context rows in repetition editor is now the same as for repetition rows

**[TRANSLATE-3087](https://jira.translate5.net/browse/TRANSLATE-3087): Editor general - Editor: term tooltip shows wrong attribute labels** <br>
TermPortlet attribute labels logic improved, Image-attribute preview shown, if exists

**[TRANSLATE-3085](https://jira.translate5.net/browse/TRANSLATE-3085): TermPortal - Termportal: solve bug happening on creating attribute in batch window** <br>
Made sure more debug info to be logged for case of next time occurence of a non-reproducable bug popping on attempt to save new attribute in batch editing dialog.

**[TRANSLATE-3084](https://jira.translate5.net/browse/TRANSLATE-3084): TermPortal - Termportal: use TextArea for Definition-attributes** <br>
TermPortal: textareas are now used for attributes of datatype noteText

**[TRANSLATE-3073](https://jira.translate5.net/browse/TRANSLATE-3073): InstantTranslate - Filetranslation must not use autoQA** <br>
Filetranslation-tasks do now skip AutoQA-step in the import process


## [5.7.13] - 2022-10-24

### Important Notes:
 


### Added
**[TRANSLATE-2994](https://jira.translate5.net/browse/TRANSLATE-2994): LanguageResources, OpenTM2 integration - t5memory roll-out** <br>
Added new cli command for migrating OpenTM2 to t5memory.
Check the usage of 
./translate5.sh help otm2:migrate


### Changed
**[TRANSLATE-3080](https://jira.translate5.net/browse/TRANSLATE-3080): LanguageResources - Language resources Help video** <br>
Integrate language resources video in Language-resources help page.


### Bugfixes
**[TRANSLATE-3082](https://jira.translate5.net/browse/TRANSLATE-3082): VisualReview / VisualTranslation - FIX alias-segments may not be translated in the live-editing when they are "far away" from each other** <br>
FIX: Repeated segments in the right visual layout, that were several pages "away" from each other may remained untranslated when scrolling to the rear occurances
FIX: Right WYSIWYG frame is partly untranslated in PDF-based visuals with lots of pages or when scrolling fast

**[TRANSLATE-3081](https://jira.translate5.net/browse/TRANSLATE-3081): TermPortal - Fix show sub languages config level in TermPortal** <br>
Correct the accessibility level for show sub-languages config in term portal.

**[TRANSLATE-3077](https://jira.translate5.net/browse/TRANSLATE-3077): LanguageResources - Auto-start pivot translation** <br>
Introduce a configuration to auto-start pivot pre-translation on API based task imports.

**[TRANSLATE-3062](https://jira.translate5.net/browse/TRANSLATE-3062): Installation & Update, Test framework - Test DB reset and removement of mysql CLI dependency** <br>
Removed the mysql CLI tool as dependency from translate5 PHP code.

**[TRANSLATE-3061](https://jira.translate5.net/browse/TRANSLATE-3061): Test framework - FIX API Tests** <br>
Fixed API tests, generalized test API

**[TRANSLATE-3053](https://jira.translate5.net/browse/TRANSLATE-3053): Editor general - Refactor direct role usages into usages via ACL rights** <br>
Direct role usages refactored with rights usages instead


## [5.7.12] - 2022-10-11

### Important Notes:
 


### Added
**[TRANSLATE-3067](https://jira.translate5.net/browse/TRANSLATE-3067): LanguageResources - Export glossary** <br>
Export of a DeepL glossary in language resources overview.

**[TRANSLATE-3016](https://jira.translate5.net/browse/TRANSLATE-3016): Configuration, Editor general, TermTagger integration - Show and use only terms of a certain process level in the editor** <br>
UPDATE: the defined term process status list will also be applied when creating new deepl glossary
Only the terms with a defined process status are used for term tagging and listed in the editor term-portlet. The configuration is runtimeOptions.termTagger.usedTermProcessStatus. 

**[TRANSLATE-2561](https://jira.translate5.net/browse/TRANSLATE-2561): Repetition editor - Enhancement of repetition editor** <br>
Added two new configs for automatic repetitions processing: "Repetition type" -radio buttons (source, target, source and target, source or target) and "Same content only"-checkbox


### Changed
**[TRANSLATE-3071](https://jira.translate5.net/browse/TRANSLATE-3071): Import/Export - Enable XLF namespace registration from plug-ins** <br>
Plug-ins can now register custom XLIFF namespace handlers to enable import of proprietary XLIFF dialects.

**[TRANSLATE-3066](https://jira.translate5.net/browse/TRANSLATE-3066): TBX-Import - Trim leading/trailing whitespaces from terms on import** <br>
leading/trailing whitespaces are now trimmed from terms on import


### Bugfixes
**[TRANSLATE-3068](https://jira.translate5.net/browse/TRANSLATE-3068): MatchAnalysis & Pretranslation - Fix repetition behaviour in pre-translation with MT only** <br>
On pre-translations with MTs only, repeated segments may get the wrong tags and produce therefore tag errors, especially a problem for instant translating files.

**[TRANSLATE-3049](https://jira.translate5.net/browse/TRANSLATE-3049): SpellCheck (LanguageTool integration) - Empty segments are send to SpellCheck on import** <br>
Empty segments are not sent for spellchecking to LanguageTool anymore

**[TRANSLATE-3044](https://jira.translate5.net/browse/TRANSLATE-3044): TermTagger integration - Cache terminology data on RecalcTransFound** <br>
Translation status assignment rewritten for terms tagged by TermTagger

**[TRANSLATE-283](https://jira.translate5.net/browse/TRANSLATE-283): Editor general - XSS Protection in translate5** <br>
ENHANCEMENT: Added general protection against CrossSiteScripting/XSS attacks


## [5.7.11] - 2022-09-22

### Important Notes:
 


### Changed
**[TRANSLATE-2988](https://jira.translate5.net/browse/TRANSLATE-2988): LanguageResources - Make translate5 fit for switch to t5memory** <br>
FIXED in 5.7.11: the language mapping to en-UK was used till 5.7.10 erroneously for saving and querying segments. To fix the affected languages are queried both in OpenTM2.
Add some fixes and data conversions when exporting a TMX from OpenTM2 so that it can be imported into t5memory.


## [5.7.10] - 2022-09-20

### Important Notes:
#### [TRANSLATE-3051](https://jira.translate5.net/browse/TRANSLATE-3051)
Ensure that a DB backup is done. All user passwords will get additionally encrypted with a random secret (pepper) created and stored in the installation.ini

#### [TRANSLATE-3016](https://jira.translate5.net/browse/TRANSLATE-3016)
Until our last release, terms with all kind of statuses were used for term tagging. From now on, only terms which are with status finalized (finalized is default value and additional statuses can be configured) are used for term tagging.
 


### Added
**[TRANSLATE-3038](https://jira.translate5.net/browse/TRANSLATE-3038): Editor general - Integrate anti virus software (4.6)** <br>
SECURITY ENHANCEMENT: Added blacklist to limit uploadable reference file types

**[TRANSLATE-3016](https://jira.translate5.net/browse/TRANSLATE-3016): Configuration, Editor general, TermTagger integration - Show and use only terms of a certain process level in the editor** <br>
Only the terms with a defined process status are used for term tagging and listed in the editor term-portlet. The configuration is runtimeOptions.termTagger.usedTermProcessStatus. 


### Changed
**[TRANSLATE-3057](https://jira.translate5.net/browse/TRANSLATE-3057): TermPortal - Extend term status map** <br>
Extend the term status mapping with additional types.

**[TRANSLATE-3040](https://jira.translate5.net/browse/TRANSLATE-3040): User Management - On password change the old one must be given (4.8)** <br>
If a user is changing his password, the old password must be given and validated too, to prevent taking over stolen user accounts.


### Bugfixes
**[TRANSLATE-3056](https://jira.translate5.net/browse/TRANSLATE-3056): Auto-QA - MQM Controller does not activate when changing task after deactivation** <br>
FIX: After deactivating MQM, it was not activated anymore when opening the next task

**[TRANSLATE-3051](https://jira.translate5.net/browse/TRANSLATE-3051): User Management - Add SALT to MD5 user password (4.4)** <br>
The user passwords are now stored in a more secure way.

**[TRANSLATE-3050](https://jira.translate5.net/browse/TRANSLATE-3050): Import/Export - Whitespace tag handling did encode internal tag placeholders on display text import filter** <br>
Fix for a proprietary import filter.

**[TRANSLATE-3041](https://jira.translate5.net/browse/TRANSLATE-3041): Auto-QA, Editor general - Wrong whitespace tag numbering leads to non working whitespace added QA check** <br>
The internal numbering of whitespace tags (newline, tab etc) was not consistent anymore between source and target, therefore the whitespace added auto QA is producing a lot of false positives.

**[TRANSLATE-3036](https://jira.translate5.net/browse/TRANSLATE-3036): VisualReview / VisualTranslation - Visual: Do not update blocked empty segments, fix multiple-variables segments in variable segmentation** <br>
FIX: Visual: Segments with several singular internal tags seen as variables were not detected
FIX: Visual: A hidden left iframe may prevented a proper update with the current segments in the right iframes layout
ENHANCEMENT: Visual: Empty blocked segments are not updated (=deleted) in the layout anymore

**[TRANSLATE-3035](https://jira.translate5.net/browse/TRANSLATE-3035): SpellCheck (LanguageTool integration) - UI spellcheck is not working after a task with disabled spellcheck was opened** <br>
Spellcheck remained disabled for other tasks after opening one task where spellcheck was explicitly disabled with liveCheckOnEditing config.

**[TRANSLATE-3026](https://jira.translate5.net/browse/TRANSLATE-3026): Editor general - Jump to task from task overview to project overview** <br>
Fix for the problem when clicking on jump to task action button in task overview, the project grid is stuck in endless reload loop.


## [5.7.9] - 2022-09-01

### Important Notes:
 


### Added
**[TRANSLATE-3019](https://jira.translate5.net/browse/TRANSLATE-3019): Configuration - Support Subnets in IP-based authentication** <br>
Change IpAuthentication plugin to support subnet masks, e.g. 192.168.0.1/24

**[TRANSLATE-3016](https://jira.translate5.net/browse/TRANSLATE-3016): Configuration, Editor general, TermTagger integration - Show and use only terms of a certain process level in the editor** <br>
What kind of process status the terms has, used for term tagging and listed in the editor term-portlet  can be configured as system, client and task level.

**[TRANSLATE-3015](https://jira.translate5.net/browse/TRANSLATE-3015): TBX-Import - Merge multiple attributes of the same type in TBX import** <br>
Two attributes will be merged into one if they are from the same type and appear on same level.

**[TRANSLATE-3014](https://jira.translate5.net/browse/TRANSLATE-3014): Editor general - Show color of TermCollection behind term in editors termportlet** <br>
Term collection color will be listed in the term portlet for each term.

**[TRANSLATE-3003](https://jira.translate5.net/browse/TRANSLATE-3003): Editor general - Show term attributes in term-portlet of translate5s editor** <br>
Tooltip with the term entry, language and term attributes will be show with mouse over the terms in the term portlet in editor.


### Bugfixes
**[TRANSLATE-3045](https://jira.translate5.net/browse/TRANSLATE-3045): TermTagger integration - Optimize terms_term indexes** <br>
Improve the DB indizes for the terms_term table.

**[TRANSLATE-3043](https://jira.translate5.net/browse/TRANSLATE-3043): SpellCheck (LanguageTool integration) - spellcheck markup is destroying internal tags** <br>
SpellCheck: Multi-whitespaces are now respected while applying spellcheck styles

**[TRANSLATE-3041](https://jira.translate5.net/browse/TRANSLATE-3041): Auto-QA, Editor general - Wrong whitespace tag numbering leads to non working whitespace added QA check** <br>
The internal numbering of whitespace tags (newline, tab etc) was not consistent anymore between source and target, therefore the whitespace added auto QA is producing a lot of false positives.

**[TRANSLATE-3030](https://jira.translate5.net/browse/TRANSLATE-3030): Auto-QA - Fixes Spellcheck-QA-Worker: Index for state-field, proper solution for logging / "last worker"** <br>
FIX: Spellcheck AutoQA-worker was lacking an database-Index, with the index spellchecking should be faster on import

**[TRANSLATE-3029](https://jira.translate5.net/browse/TRANSLATE-3029): file format settings - IDML FPRM Editor too heigh** <br>
FIX: Height of IDML FPRM Editor too big on smaller screens so that buttons are not visible

**[TRANSLATE-3028](https://jira.translate5.net/browse/TRANSLATE-3028): Main back-end mechanisms (Worker, Logging, etc.) - Reset password error** <br>
Fix for a problem where the user was not able to reset the password.


## [5.7.8] - 2022-08-18

### Important Notes:
 


### Changed
**[TRANSLATE-2380](https://jira.translate5.net/browse/TRANSLATE-2380): VisualReview / VisualTranslation - Visual: Also connect segments, that contain variables with the layout** <br>
Visual: Segmentation of PDF/HTML based reviews now finds segments containing variables in the layout
FIX: The Segmentation result is now calculated for all visual files together
FIX: Alike Segments may have been not updated in the layout when changing the master


### Bugfixes
**[TRANSLATE-3025](https://jira.translate5.net/browse/TRANSLATE-3025): OpenTM2 integration - OpenTM2 returns sometimes empty source language** <br>
On TMX export from OpenTM2 the source xml:lang attribute of a segment was sometimes empty. This is fixed now for a proper migration to t5memory.

**[TRANSLATE-3024](https://jira.translate5.net/browse/TRANSLATE-3024): LanguageResources - Solve Problems with Empty Sources and TMs** <br>
FIX: Empty sources in segments lead to errors when saving them to Translation Memories

**[TRANSLATE-2916](https://jira.translate5.net/browse/TRANSLATE-2916): VisualReview / VisualTranslation - Repetitions in the segment grid are not linked to the visual** <br>
NOTHING TO MENTION, issue resolved with TRANSLATE-2380


## [5.7.7] - 2022-08-09

### Important Notes:
 


## [5.7.6] - 2022-08-05

### Important Notes:
#### [TRANSLATE-3022](https://jira.translate5.net/browse/TRANSLATE-3022)
Stay in field: This issue is security related!

#### [TRANSLATE-3020](https://jira.translate5.net/browse/TRANSLATE-3020)
This issue is security related!
 


### Added
**[TRANSLATE-3010](https://jira.translate5.net/browse/TRANSLATE-3010): LanguageResources - Set default pivot language in systemconfiguration** <br>
Default task pivot languages can be configured for each customer.

**[TRANSLATE-2812](https://jira.translate5.net/browse/TRANSLATE-2812): Editor general, LanguageResources - Send highlighted word in segment to concordance search or synonym search** <br>
Enables selected text in editor to be send as synonym or concordance search.

**[TRANSLATE-2538](https://jira.translate5.net/browse/TRANSLATE-2538): Auto-QA - AutoQA: Include Spell-, Grammar- and Style-Check** <br>
All spelling, grammar and style errors found by languagetool for all segments of a task are now listed in AutoQA and it is possible to filter the segments by error type.
In addition errors are now not only marked in the segment open for editing, but also in all other segments.
In addition there are now many more subtypes for errors (before we had only spelling, grammar and style).


### Changed
**[TRANSLATE-3008](https://jira.translate5.net/browse/TRANSLATE-3008): LanguageResources - Change tooltip for checkbox "Pre-translate (MT)"** <br>
Improves tooltip texts in match analysis.

**[TRANSLATE-2932](https://jira.translate5.net/browse/TRANSLATE-2932): Okapi integration, Task Management - BCONF Management Milestone 2** <br>
BCONF Management Milestone 2
* adds capabilities to upload/update the SRX files embedded in a BCONF
* adds the frontend to manage the embedded filters/FPRM's of a bconf together with the related extension-mapping
* New filters can be created by cloning existing (customized or default) ones
* adds capabilities to generally edit and validate filters/FPRM's
* adds frontend editors for the following filters: okf_html, okf_icml, okf_idml, okf_itshtml5, okf_openxml, okf_xml, okf_xmlstream


### Bugfixes
**[TRANSLATE-3022](https://jira.translate5.net/browse/TRANSLATE-3022): Editor general - RXSS with help page editordocumentation possible** <br>
Security related fix.

**[TRANSLATE-3020](https://jira.translate5.net/browse/TRANSLATE-3020): Editor general - PXSS on showing reference files** <br>
Security related fix.

**[TRANSLATE-3011](https://jira.translate5.net/browse/TRANSLATE-3011): Import/Export - Extend error handling in xlf parser** <br>
Error handling code improvement for xlf parser.

**[TRANSLATE-3009](https://jira.translate5.net/browse/TRANSLATE-3009): Editor general - Base tooltip class problem** <br>
Fix for a general problem when tooltips are shown in some places in the application.

**[TRANSLATE-2935](https://jira.translate5.net/browse/TRANSLATE-2935): Auto-QA, TermTagger integration - Avoid term-check false positive in case of homonyms and display homonyms in source and target** <br>
TermTagger: Fixed term-check false positives in case of homonyms

**[TRANSLATE-2063](https://jira.translate5.net/browse/TRANSLATE-2063): Import/Export - Enable parallele use of multiple okapi versions to fix Okapi bugs** <br>
Multiple okapi instances can be configured and used for task imports.


## [5.7.5] - 2022-07-22

### Important Notes:
#### [TRANSLATE-3002](https://jira.translate5.net/browse/TRANSLATE-3002)
Activate in client instance, see TS-1787!
 


### Added
**[TRANSLATE-3002](https://jira.translate5.net/browse/TRANSLATE-3002): Workflows - Ask for task finish on task close too** <br>
Added dialog shown on leaving the application in embedded mode, with finish task and just leave as possible choices. Added config option to control whether such dialog should be shown.


### Changed
**[TRANSLATE-2999](https://jira.translate5.net/browse/TRANSLATE-2999): TermPortal - Create missing term attributes datatype foreign key** <br>
Fixed problem with missing data types for term attributes in term portal.


### Bugfixes
**[TRANSLATE-3007](https://jira.translate5.net/browse/TRANSLATE-3007): InstantTranslate - Instant translate search content with tags** <br>
FIXED Bug in Instanttranslate when segmented results are processed due to a missing API

**[TRANSLATE-3006](https://jira.translate5.net/browse/TRANSLATE-3006): LanguageResources - Problem with DeepL target language** <br>
Fixes problem where the DeepL language resource target language was saved as lowercase value.

**[TRANSLATE-3004](https://jira.translate5.net/browse/TRANSLATE-3004): Editor general - Error on deleting project** <br>
Solves problem where error pop-up was shown when deleting project.

**[TRANSLATE-3000](https://jira.translate5.net/browse/TRANSLATE-3000): Editor general - Use project task store for task reference in import wizard** <br>
Solves problem in import wizard when assigning task users.

**[TRANSLATE-2996](https://jira.translate5.net/browse/TRANSLATE-2996): MatchAnalysis & Pretranslation - Analysis grid reconfigure leads to an error** <br>
Solves problem with front-end error in match analysis overview.

**[TRANSLATE-2995](https://jira.translate5.net/browse/TRANSLATE-2995): Main back-end mechanisms (Worker, Logging, etc.) - Event logger error** <br>
Fixed back-end error with workflow actions info logging.

**[TRANSLATE-2987](https://jira.translate5.net/browse/TRANSLATE-2987): Task Management - Routing problems when jumping from and to project overview** <br>
Fixed a problem where the selected task was not focused after switching between the overviews.

**[TRANSLATE-2963](https://jira.translate5.net/browse/TRANSLATE-2963): Main back-end mechanisms (Worker, Logging, etc.), MatchAnalysis & Pretranslation - Queuing matchanalysis multiple times leads to locked tasks** <br>
FIX: Prevent running multiple operations for the same task

**[TRANSLATE-2813](https://jira.translate5.net/browse/TRANSLATE-2813): Client management, LanguageResources, Task Management, User Management - Copy&paste content of PM grids** <br>
Now you can copy text from all grids cells in translate5.

**[TRANSLATE-2786](https://jira.translate5.net/browse/TRANSLATE-2786): Import/Export - xliff 1.2 import fails if a g tag contains a mrk segment tag** <br>
The XLF import fails if there are g tags surrounding the mrk segmentation tags.


## [5.7.4] - 2022-06-30

### Important Notes:
#### [TRANSLATE-2984](https://jira.translate5.net/browse/TRANSLATE-2984)
Configure in client instance! See internal notes.
 


### Added
**[TRANSLATE-2984](https://jira.translate5.net/browse/TRANSLATE-2984): Task Management - Archive and delete old tasks** <br>
Implement a workflow action to export ended tasks, save the export (xliff2 and normal export) to a configurable destination and delete the task afterwards.
This action is disabled by default.

**[TRANSLATE-2855](https://jira.translate5.net/browse/TRANSLATE-2855): MatchAnalysis & Pretranslation - Pre-translate pivot language with language resource** <br>
Pivot segments in task now can be be filled/translated using language resources. For api usage check this link: https://confluence.translate5.net/display/TAD/LanguageResources%3A+pivot+pre-translation

**[TRANSLATE-2839](https://jira.translate5.net/browse/TRANSLATE-2839): OpenTM2 integration - Attach to t5memory service** <br>
Structural adjustments for t5memory service.


### Changed
**[TRANSLATE-2988](https://jira.translate5.net/browse/TRANSLATE-2988): LanguageResources - Make translate5 fit for switch to t5memory** <br>
Add some fixes and data conversions when exporting a TMX from OpenTM2 so that it can be imported into t5memory.


### Bugfixes
**[TRANSLATE-2992](https://jira.translate5.net/browse/TRANSLATE-2992): Main back-end mechanisms (Worker, Logging, etc.) - PHP's setlocale has different default values** <br>
The PHP's system locale was not correctly set. This is due a strange behaviour setting the default locale randomly.

**[TRANSLATE-2990](https://jira.translate5.net/browse/TRANSLATE-2990): OpenTM2 integration - Improve error handling on task re-import into TM** <br>
Sometimes the re-import a task into a TM feature was hanging and blocking the task. This is solved, the task is reopened in the case of an error and the logging was improved.

**[TRANSLATE-2989](https://jira.translate5.net/browse/TRANSLATE-2989): Import/Export - XLIFF2 export is failing** <br>
The XLIFF 2 export was failing if the imported tasks was containing one file which was ignored on import (for example if all segments were tagged with translate no)


## [5.7.3] - 2022-06-14

### Important Notes:
 


### Added
**[TRANSLATE-2811](https://jira.translate5.net/browse/TRANSLATE-2811): Editor general, LanguageResources - Integrate MS Translator synonym search in editor** <br>
Microsoft's translator synonym search is now part of translate5 editor.

**[TRANSLATE-2539](https://jira.translate5.net/browse/TRANSLATE-2539): Auto-QA - AutoQA: Numbers check** <br>
AutoQA: added 12 number-checks from SNC library


### Changed
**[TRANSLATE-2986](https://jira.translate5.net/browse/TRANSLATE-2986): Main back-end mechanisms (Worker, Logging, etc.) - Trigger callback when all users did finish the assigned role** <br>
After all jobs are finished, callback workflow action can be configured. How this can be configured it is explained in this link:  https://confluence.translate5.net/display/BUS/Workflow+Action+and+Notification+Customization#:~:text=Remote%20callback%20when%20all%20users%20finish%20there%20jobs

**[TRANSLATE-2978](https://jira.translate5.net/browse/TRANSLATE-2978): Editor Length Check - Disable automatic adding of newlines on segments by configuration** <br>
The automatic adding of newlines could now disabled by configuration.


### Bugfixes
**[TRANSLATE-2985](https://jira.translate5.net/browse/TRANSLATE-2985): Editor general - Error on configuration overview filtering** <br>
The error which pops-up when quick-typing in configuration filter is solved.

**[TRANSLATE-2983](https://jira.translate5.net/browse/TRANSLATE-2983): Editor general - Task action menu error after leaving a task** <br>
Opening the task action menu after leaving the task will no longer produce error.

**[TRANSLATE-2982](https://jira.translate5.net/browse/TRANSLATE-2982): TermPortal, TermTagger integration - Empty term in TBX leads to crashing termtagger** <br>
If an imported TBX was containing empty terms (which is basically non sense) and that term collection was then used for termtagging in asian languages, the termtagger was hanging in an endless loop and was not usable anymore.

**[TRANSLATE-2981](https://jira.translate5.net/browse/TRANSLATE-2981): TBX-Import - Importing TBX with invalid XML leads to high CPU usage** <br>
On importing a TBX file with invalid XML the import process was caught in an endless loop. This is fixed and the import stops now with an error message.

**[TRANSLATE-2980](https://jira.translate5.net/browse/TRANSLATE-2980): Editor general - On task delete translate5 keeps the old route** <br>
Missing task message when the task is removed will no longer be shown.


## [5.7.2] - 2022-05-24

### Important Notes:
#### [TRANSLATE-2314](https://jira.translate5.net/browse/TRANSLATE-2314)
Wording and Icon changed: Introduced a new processing state "locked" with a lock icon. This segments can be locked / unlocked by PM users. The processing state blocked remains for segments which can not be unlocked by PMs.
 


### Added
**[TRANSLATE-2642](https://jira.translate5.net/browse/TRANSLATE-2642): LanguageResources - DeepL terminology integration** <br>
Enable deepL language resources to use terminology as glossar.

**[TRANSLATE-2314](https://jira.translate5.net/browse/TRANSLATE-2314): Editor general - Be able to lock/unlock segments in the editor by a PM** <br>
The project-manager is now able to lock and unlock single segments (CTRL+L). 
A jump to segment is implemented (CTRL+G).
Bookmarks can now be set also on just a selected segment, not only on an opened one (CTRL+D). Locking and bookmarking can be done in a batch way on all segments in the current filtered grid. 


### Changed
**[TRANSLATE-2976](https://jira.translate5.net/browse/TRANSLATE-2976): Okapi integration - Make MS Office document properties translatable by default** <br>
The Okapi default settings are changed, so that MS Office document properties are now translateable by default.



### Bugfixes
**[TRANSLATE-2973](https://jira.translate5.net/browse/TRANSLATE-2973): LanguageResources - Tag Repair creates Invalid Internal tags when Markup is too complex** <br>
FIX: Automatic tag repair may generated invalid internal tags when complex markup was attempted to be translated

**[TRANSLATE-2972](https://jira.translate5.net/browse/TRANSLATE-2972): Editor general - Leaving and Navigating to Deleted Tasks** <br>
Trying to access a deleted task via URL was not handled properly. Now the user is redirected to the task overview.

**[TRANSLATE-2969](https://jira.translate5.net/browse/TRANSLATE-2969): Import/Export - Reintroduce BCONF import via ZIP** <br>
FIX: Re-enabled using a customized BCONF for OKAPI via the import zip. Please note, that this feature is nevertheless deprecated and the BCONF in the import zip will not be added to the application's BCONF pool.

**[TRANSLATE-2968](https://jira.translate5.net/browse/TRANSLATE-2968): LanguageResources - Deleted space at start or end of fuzzy match not highlighted** <br>
Fixed visualization issues of added / deleted white-space in the fuzzy match grind of the lower language resource panel in the editor.

**[TRANSLATE-2967](https://jira.translate5.net/browse/TRANSLATE-2967): TermPortal - TermPortal: grid-attrs height problem** <br>
Fixed the tiny height of attribute grids. 

**[TRANSLATE-2965](https://jira.translate5.net/browse/TRANSLATE-2965): GroupShare integration - GroupShare sync deletes all associations between tasks and language-resources** <br>
The synchronization of GroupShare TMs was deleting to much task language resource associations.

**[TRANSLATE-2964](https://jira.translate5.net/browse/TRANSLATE-2964): Workflows - PM Project Notification is triggered on each project instead only on term translation projects** <br>
Project creation notifications can now be sent only for certain project types.

**[TRANSLATE-2926](https://jira.translate5.net/browse/TRANSLATE-2926): Okapi integration - Index and variables can not be extracted from Indesign** <br>
So far it was not possible to translate Indesign text variables and index entries, because Okapi did not extract them.

With an okapi contribution by Denis, financed by translate5, this is changed now.

Also translate5 default okapi settings are changed, so that text variables and index entries are now translated by default for idml.


## [5.7.1] - 2022-05-10

### Important Notes:
#### [TRANSLATE-2931](https://jira.translate5.net/browse/TRANSLATE-2931)
Remove Support for bconf files included in Import Archives

#### [TRANSLATE-2884](https://jira.translate5.net/browse/TRANSLATE-2884)
A new role systemadmin is added, to be used only for technical people and translate5 system administrators. Read below the details for usage and what it enables.
 


### Changed
**[TRANSLATE-2960](https://jira.translate5.net/browse/TRANSLATE-2960): VisualReview / VisualTranslation - Enable Markup processing in Subtitle Import parsers** <br>
Visual Video: Enable markup protection in internal tags as well as whitespace  for the import

**[TRANSLATE-2931](https://jira.translate5.net/browse/TRANSLATE-2931): Okapi integration, Task Management - Import file format and segmentation settings - Bconf Management (Milestone 1)** <br>
Translate5 can now manage Okapi BatchConfiguration files - needed for configuring the import file filters. Admins and PMs can upload, download, rename Bconfs and upload and download contained SRX files in the new 'File format and segmentation settings' grid under 'Preferences'. It is also available under 'Clients' to easily handle specific requirements of different customers. You can also set a default there, which overrides the one from the global perspective. During Project Creation a dropdown menu presents the available Bconf files for the chosen client, preset with the configured default. The selected one is then passed to Okapi on import.

**[TRANSLATE-2901](https://jira.translate5.net/browse/TRANSLATE-2901): InstantTranslate - Languageresource type filter in instanttranslate API** <br>
ENHANCEMENT: Added filters to filter InstantTranslate API for language resource types and id's. See https://confluence.translate5.net/display/TAD/InstantTranslate for details

FIX: fixed whitespace-rendering in translations when Translation Memories were requested and text to translate was segmented therefore

**[TRANSLATE-2884](https://jira.translate5.net/browse/TRANSLATE-2884): Main back-end mechanisms (Worker, Logging, etc.) - Further restrict nightly error mail summaries** <br>
A new role systemadmin is added, to be used only for technical people and translate5 system administrators. 
Only users with that role will receive the nightly error summary e-mail in the future (currently all admins). Only systemadmins can set the role systemadmin and api.
For hosted clients: contact us so that we can enable the right for desired users.
For on premise clients: the role must be added manually in the DB to one user. With that user the role can then be set on other users.


### Bugfixes
**[TRANSLATE-2962](https://jira.translate5.net/browse/TRANSLATE-2962): LanguageResources - DeepL error when when sending large content** <br>
Fixes problem with failing request to DeepL because of exhausted request size.

**[TRANSLATE-2961](https://jira.translate5.net/browse/TRANSLATE-2961): Editor general - Error on repetition save** <br>
Solves a problem where an error happens in the UI after saving repetitions with repetition editor.

**[TRANSLATE-2959](https://jira.translate5.net/browse/TRANSLATE-2959): OpenId Connect - Overlay for SSO login auto-redirect** <br>
Adds overlay when auto-redirecting with SSO authentication.

**[TRANSLATE-2957](https://jira.translate5.net/browse/TRANSLATE-2957): OpenId Connect - Missing default text on SSO button** <br>
When configuring SSO via OpenID no default button text is provided, therefore the SSO Login button may occur as button without text - not recognizable as button then.

**[TRANSLATE-2910](https://jira.translate5.net/browse/TRANSLATE-2910): TermPortal, User Management - Role rights for approval workflow of terms in the TermPortal** <br>
Terms/attributes editing/deletion access logic reworked for Term Proposer, Term Reviewer and Term Finalizer roles

**[TRANSLATE-2558](https://jira.translate5.net/browse/TRANSLATE-2558): Editor general - Task focus after login** <br>
On application load always the first project was selected, instead the one given in the URL. This is fixed now. Other application parts (like preferences or clients) can now also opened directly after application start by passing its section in the URL.


## [5.7.0] - 2022-04-26

### Important Notes:
#### [TRANSLATE-2949](https://jira.translate5.net/browse/TRANSLATE-2949)
Update the config in the corresponding client instance according to TS-1664.

#### [TRANSLATE-2799](https://jira.translate5.net/browse/TRANSLATE-2799)
All EN and PT DeepL language resources must be changed to EN-GB and PT-PT

#### [TRANSLATE-2762](https://jira.translate5.net/browse/TRANSLATE-2762)
Enable HTML Markup in InstantTranslate

#### [TRANSLATE-2534](https://jira.translate5.net/browse/TRANSLATE-2534)
Update / restart of message bus required after Update!
API users using /editor/session endpoint: providing a taskGuid on session API login is obsolete. Just login and open the task by putting the task ID in the URL: /editor/taskid/123/ - the auth call returns the URL for convenience.
 


### Added
**[TRANSLATE-2949](https://jira.translate5.net/browse/TRANSLATE-2949): Configuration, User Management - Make settings for new users pre-configurable** <br>
Enable setting default pre-selected source and target languages in instant translate. For more info how this can be configured, please check the config option runtimeOptions.InstantTranslate.user.defaultLanguages in this link
https://confluence.translate5.net/display/TAD/InstantTranslate

**[TRANSLATE-2869](https://jira.translate5.net/browse/TRANSLATE-2869): Import/Export, Task Management - Export of editing history of a task** <br>
Provide for PMs the possibility to download the tasks content as spreadsheet containing all segments, with the pre-translated target and the target content after each workflow step.

**[TRANSLATE-2822](https://jira.translate5.net/browse/TRANSLATE-2822): MatchAnalysis & Pretranslation - Match Analysis on a character basis** <br>
Match analysis now can be displayed on character or word base.

**[TRANSLATE-2779](https://jira.translate5.net/browse/TRANSLATE-2779): Auto-QA - QA check for leading/trailing white space in segments** <br>
Added check for 3 different kinds of leading/trailing whitespaces within a segment

**[TRANSLATE-2762](https://jira.translate5.net/browse/TRANSLATE-2762): InstantTranslate - Enable tags in InstantTranslate text field** <br>
Instant Translate now supports using HTML markup in the text to translate. Tag-errors maybe caused by the used services (e.g. DeepL) are automatically repaired when markup is submitted. Please note, that for the time, the typed markup is incomplete or the markup is syntactically incorrect, an error hinting at the invalidity of the markup is shown.


### Changed
**[TRANSLATE-2952](https://jira.translate5.net/browse/TRANSLATE-2952): Editor general - Automated workflow and user roles video** <br>
Integrates the automated workflow and user roles in translate5 help page.

**[TRANSLATE-2902](https://jira.translate5.net/browse/TRANSLATE-2902): Configuration, Task Management, TermPortal - Send e-mail to specific PM on creation of project through TermTranslation Workflow** <br>
Added system config to specify user to be assigned as PM for termtranslation-projects by default, and to send an email notification to that user on termtranslation-project creation


### Bugfixes
**[TRANSLATE-2958](https://jira.translate5.net/browse/TRANSLATE-2958): TermPortal - TermCollection not updateable after deleting the initial import user** <br>
If a user was deleted, and this user has imported a TBX, the resulting term collection could no be updated by re-importing a TBX anymore. This is fixed.

**[TRANSLATE-2955](https://jira.translate5.net/browse/TRANSLATE-2955): LanguageResources, OpenTM2 integration - Segment can not be saved if language resource is writable and not available** <br>
If a language resource is assigned writable to a task and the same language resource is not available, the segment can not be saved.

**[TRANSLATE-2954](https://jira.translate5.net/browse/TRANSLATE-2954): Import/Export - If Import reaches PHP max_file_uploads limit there is no understandable error message** <br>
If the amount of files reaches the configured max_file_uploads in PHP there is no understandable error message for the user what is the underlying reason why the upload is failing. 

**[TRANSLATE-2953](https://jira.translate5.net/browse/TRANSLATE-2953): Import/Export - Create task without selecting file** <br>
Fixes a problem where the import wizard form could be submitted without selecting a valid workfile.

**[TRANSLATE-2951](https://jira.translate5.net/browse/TRANSLATE-2951): API, InstantTranslate - Instant-translate filelist does not return the taskId** <br>
Fixes a problem where the task-id was not returned as parameter in the instant-translate filelist api call.

**[TRANSLATE-2947](https://jira.translate5.net/browse/TRANSLATE-2947): Import/Export - Can not import SDLXLIFF where sdl-def tags are missing** <br>
For historical reasons sdl-def tags were mandatory in SDLXLIFF trans-units, which is not necessary anymore.

**[TRANSLATE-2924](https://jira.translate5.net/browse/TRANSLATE-2924): InstantTranslate - translate file not usable in InstantTranslate** <br>
Improved GUI behaviour, file translation is always selectable and shows an Error-message if no translation service is available for the selected languages. Also, when changing languages the mode is not automatically reset to "text translation" anymore

**[TRANSLATE-2862](https://jira.translate5.net/browse/TRANSLATE-2862): InstantTranslate - Issue with the usage of "<" in InstantTranslate** <br>
BUGFIX InstantTranslate Plugin: Translated text is not terminated anymore after a single "<" in the original text

**[TRANSLATE-2850](https://jira.translate5.net/browse/TRANSLATE-2850): Import/Export - File review.html created in import-zip, even if not necessary** <br>
reviewHtml.txt will be no longer created when there are no visual-urls defined on import.

**[TRANSLATE-2843](https://jira.translate5.net/browse/TRANSLATE-2843): Import/Export - translate5 requires target language in xliff-file** <br>
Xml based files where no target language is detected on import(import wizard), will be imported as non-bilingual files.

**[TRANSLATE-2799](https://jira.translate5.net/browse/TRANSLATE-2799): LanguageResources - DeepL API - some languages missing compared to https://www.deepl.com/translator** <br>
All DeepL resources where the target language is EN or PT, will be changed from EN -> EN-GB and PT to PT-PT. The reason for this is a recent DeepL api change.

**[TRANSLATE-2534](https://jira.translate5.net/browse/TRANSLATE-2534): Editor general - Enable opening multiple tasks in multiple tabs** <br>
Multiple tasks can now be opened in different browser tabs within the same user session at the same time. This is especially interesting for embedded usage of translate5 where tasks are opened via custom links instead of the translate5 internal task overview.


## [5.6.10] - 2022-04-07

### Important Notes:
 


### Added
**[TRANSLATE-2942](https://jira.translate5.net/browse/TRANSLATE-2942): Repetition editor - Make repetitions more restrict by including segment meta fields into repetition calculation** <br>
Make repetition calculation more restrict by including segment meta fields (like maxLength) into repetition calculation. Can be defined by new configuration runtimeOptions.alike.segmentMetaFields.

**[TRANSLATE-2842](https://jira.translate5.net/browse/TRANSLATE-2842): Workflows - new configuration to disable workflow mails** <br>
Workflow mails can be disabled via configuration.

**[TRANSLATE-2386](https://jira.translate5.net/browse/TRANSLATE-2386): Configuration, Editor general - Add language specific special characters in database configuration for usage in editor** <br>
The current bar in the editor that enables adding special characters (currently non-breaking space, carriage return and tab) can be extended by characters, that can be defined in the configuration.
Example of the config layout can be found here:
https://confluence.translate5.net/display/BUS/Special+characters


### Bugfixes
**[TRANSLATE-2946](https://jira.translate5.net/browse/TRANSLATE-2946): Editor general, Editor Length Check - Multiple problems on automatic adding of newlines in to long segments** <br>
Multiple Problems fixed: add newline or tab when with selected text in editor lead to an error. Multiple newlines were added in some circumstances in multiline segments with to long content. Optionally overwrite the trailing whitespace when newlines are added automatically.

**[TRANSLATE-2943](https://jira.translate5.net/browse/TRANSLATE-2943): MatchAnalysis & Pretranslation - No analysis is shown if all segments were pre-translated and locked for editing** <br>
No analysis was shown if all segments were locked for editing due successful pre-translation although the analysis was run. Now an empty result is shown.

**[TRANSLATE-2941](https://jira.translate5.net/browse/TRANSLATE-2941): Editor general - Ignore case for imported files extensions** <br>
The extension validator in the import wizard will no longer be case sensitive.

**[TRANSLATE-2940](https://jira.translate5.net/browse/TRANSLATE-2940): Main back-end mechanisms (Worker, Logging, etc.) - Login redirect routes** <br>
Instant-translate / Term-portal routes will be evaluated correctly on login.

**[TRANSLATE-2939](https://jira.translate5.net/browse/TRANSLATE-2939): TermTagger integration - Fix language matching on term tagging** <br>
The language matching between a task and terminology was not correct. Now terms in a major language (de) are also used in tasks with a sub language (de-DE)

**[TRANSLATE-2914](https://jira.translate5.net/browse/TRANSLATE-2914): I10N - Missing localization for Chinese** <br>
Added missing translations for Chinese languages in the language drop downs.


## [5.6.9] - 2022-03-30

### Important Notes:
 


### Added
**[TRANSLATE-2697](https://jira.translate5.net/browse/TRANSLATE-2697): VisualReview / VisualTranslation - General plugin which parses a visual HTML source from a reference file** <br>
Added capabilities to download the visual source from an URL embedded in a reference XML file


### Changed
**[TRANSLATE-2923](https://jira.translate5.net/browse/TRANSLATE-2923): MatchAnalysis & Pretranslation - Enable 101% Matches to be shown as <inContextExact in Trados analysis XML export** <br>
A matchrate of 101% may be mapped to InContextExact matches in the analysis XML export for Trados (if configured: runtimeOptions.plugins.MatchAnalysis.xmlInContextUsage)


### Bugfixes
**[TRANSLATE-2938](https://jira.translate5.net/browse/TRANSLATE-2938): Editor general - Remove the limit from the global customer switch** <br>
The global customer dropdown has shown only 20 customers, now all are show.

**[TRANSLATE-2937](https://jira.translate5.net/browse/TRANSLATE-2937): Main back-end mechanisms (Worker, Logging, etc.) - Workflow user prefs loading fails on importing task** <br>
Solves a problem with user preferences in importing tasks.

**[TRANSLATE-2934](https://jira.translate5.net/browse/TRANSLATE-2934): VisualReview / VisualTranslation - Bookmark segment in visual does not work** <br>
The segment bookmark filter button in the simple view mode of visual review was not working, this is fixed.

**[TRANSLATE-2930](https://jira.translate5.net/browse/TRANSLATE-2930): InstantTranslate - Instant-translate task types listed in task overview** <br>
Pre-translated files with instant-translate will not be listed anymore as tasks in task overview.

**[TRANSLATE-2922](https://jira.translate5.net/browse/TRANSLATE-2922): MatchAnalysis & Pretranslation - 103%-Matches are shown in wrong category in Trados XML Export** <br>
A matchrate of 103% must be mapped to perfect matches in the analysis XML export for Trados (was previously mapped to InContextExact).

**[TRANSLATE-2921](https://jira.translate5.net/browse/TRANSLATE-2921): TermPortal - Batch edit should only change all terms on affected level** <br>
Batch editing was internally changed, so the only selected terms and language- and termEntry- levels of selected terms are affected.

**[TRANSLATE-2844](https://jira.translate5.net/browse/TRANSLATE-2844): Import/Export - upload wizard is blocked by zip-file as reference file** <br>
Disallow zip files to be uploaded as a reference file via the UI, since they can not be processed and were causing errors.

**[TRANSLATE-2835](https://jira.translate5.net/browse/TRANSLATE-2835): OpenTM2 integration - Repair invalid OpenTM2 TMX export** <br>
Depending on the content in the TM the exported TMX may result in invalid XML. This is tried to be fixed as best as possible to provide valid XML.

**[TRANSLATE-2766](https://jira.translate5.net/browse/TRANSLATE-2766): Client management - Change client sorting in drop-downs to alphabethically** <br>
All over the application clients in the drop-downs were sorted by the order, they have been added to the application. Now they are sorted alphabetically.


## [5.6.8] - 2022-03-22

### Important Notes:
 


### Changed
**[TRANSLATE-2915](https://jira.translate5.net/browse/TRANSLATE-2915): Okapi integration - Optimize okapi android xml and ios string settings** <br>
Settings for android xml and IOs string files were optimized to protect certain tag structures, cdata and special characters

**[TRANSLATE-2907](https://jira.translate5.net/browse/TRANSLATE-2907): InstantTranslate - Improve FileTranslation in InstantTranslate** <br>
InstantTranslate FileTranslation always starts direct after selecting (or Drag'nDrop) the file no matter what is configed for runtimeOptions.InstantTranslate.instantTranslationIsActive

**[TRANSLATE-2903](https://jira.translate5.net/browse/TRANSLATE-2903): TermPortal - Batch edit for Process Status and Usage Status attrs** <br>
TermPortal: batch editing is now possible for Process Status and Usage Status attributes


### Bugfixes
**[TRANSLATE-2920](https://jira.translate5.net/browse/TRANSLATE-2920): Editor general - REVERT:  TRANSLATE-2345-fix-jumping-cursor** <br>
ROLLBACK: Fix for jumping cursor reverted

**[TRANSLATE-2912](https://jira.translate5.net/browse/TRANSLATE-2912): Import/Export - reviewHTML.txt import in zip file does not work anymore** <br>
Fixes a problem where reviewHTML.txt file in the zip import package is ignored.

**[TRANSLATE-2911](https://jira.translate5.net/browse/TRANSLATE-2911): Editor general - Cursor jumps to start of segment** <br>
FIX: Cursor Jumps when SpellChecker runs and after navigating with arrow-keys 

**[TRANSLATE-2905](https://jira.translate5.net/browse/TRANSLATE-2905): InstantTranslate - No usable error message on file upload error due php max file size reached** <br>
Custom error message when uploading larger files as allowed in instant-translate.

**[TRANSLATE-2890](https://jira.translate5.net/browse/TRANSLATE-2890): Main back-end mechanisms (Worker, Logging, etc.) - Module redirect based on initial_page acl** <br>
Authentication acl improvements

**[TRANSLATE-2848](https://jira.translate5.net/browse/TRANSLATE-2848): Import/Export - TermCollection not listed in import wizard** <br>
Language resources will be grouped by task in language-resources to task association panel in the import wizard.


## [5.6.7] - 2022-03-17

### Important Notes:
#### [TRANSLATE-2906](https://jira.translate5.net/browse/TRANSLATE-2906)
For users with large amount of terminology: due a DB collation change the SQL file of this fix may run several minutes.

#### [TRANSLATE-2895](https://jira.translate5.net/browse/TRANSLATE-2895)
For XLF imports the behaviour how and which surrounding tags are ignored is changed! See the details below.
 


### Added
**[TRANSLATE-2895](https://jira.translate5.net/browse/TRANSLATE-2895): Import/Export - Optionally remove single tags and bordering tag pairs at segment borders** <br>
The behaviour how tags are ignored from XLF (not SDLXIFF!) imports has been improved so that all surrounding tags can be ignored right now. The config runtimeOptions.import.xlf.ignoreFramingTags has therefore been changed and has now 3 config values: disabled, paired, all. Where paired ignores only tag pairs at the start and end of a segment, and all ignores all tags before and after plain text. Tags inside of text (and their paired partners) remain always in the segment. The new default is to ignore all tags, not only the paired ones.

**[TRANSLATE-2891](https://jira.translate5.net/browse/TRANSLATE-2891): TermPortal - Choose in TermTranslation Workflow, if definitions are translated** <br>
It's now possible to choose whether definition-attributes should be exported while exporting terms from TermPortal to main Translate5 app


### Changed
**[TRANSLATE-2899](https://jira.translate5.net/browse/TRANSLATE-2899): VisualReview / VisualTranslation - Base Work for Visual API tests** <br>
Added capabilities for generating API -tests for the Visual

**[TRANSLATE-2897](https://jira.translate5.net/browse/TRANSLATE-2897): Import/Export - Make XML Parser more standard conform** <br>
The internal used XML parser was not completly standard conform regarding the naming of tags.


### Bugfixes
**[TRANSLATE-2906](https://jira.translate5.net/browse/TRANSLATE-2906): TBX-Import - Improve slow TBX import of huge TBX files** <br>
Due a improvement in TBX term ID handling, the import performance for bigger TBX files was reduced. This is repaired now.

**[TRANSLATE-2900](https://jira.translate5.net/browse/TRANSLATE-2900): OpenId Connect - Auto-set roles for sso authentications** <br>
Auto set roles is respected in SSO created users.

**[TRANSLATE-2898](https://jira.translate5.net/browse/TRANSLATE-2898): Editor general - Disable project deletion while task is importing** <br>
Now project can not be deleted while there is a running project-task import.

**[TRANSLATE-2896](https://jira.translate5.net/browse/TRANSLATE-2896): Editor general - Remove null safe operator from js code** <br>
Javascript code improvement.

**[TRANSLATE-2883](https://jira.translate5.net/browse/TRANSLATE-2883): VisualReview / VisualTranslation - Enable visual with source website, html, xml/xslt and images to provide more than 19 pages** <br>
FIX: The Pager for the visual now shows reviews with more than 9 pages properly.

**[TRANSLATE-2868](https://jira.translate5.net/browse/TRANSLATE-2868): Editor general - Jump to segment on task open: priority change** <br>
URL links to segments work now. The segment id from the URL hash gets prioritized over the last edited segment id.

**[TRANSLATE-2859](https://jira.translate5.net/browse/TRANSLATE-2859): TermPortal - Change logic, who can edit and delete attributes** <br>
The rights who can delete terms are finer granulated right now.

**[TRANSLATE-2849](https://jira.translate5.net/browse/TRANSLATE-2849): Import/Export - Disable Filename-Matching for 1:1 Files, it is possible to upload matching-faults** <br>
File-name matching in visual for single project tasks is disabled and additional import project wizard improvements.

**[TRANSLATE-2345](https://jira.translate5.net/browse/TRANSLATE-2345): Editor general, TrackChanges - Cursor jumps to start of segment, when user enters space and stops typing for a while** <br>
FIX: Cursor Jumps when inserting Whitespace, SpellChecking and in various other situations


## [5.6.6] - 2022-03-08

### Important Notes:
 


### Bugfixes
**[TRANSLATE-2892](https://jira.translate5.net/browse/TRANSLATE-2892): Import/Export - Visual Mapping source for project uploads** <br>
Solves problem of visual mapping not set to the correct value for project imports.


## [5.6.5] - 2022-03-07

### Important Notes:
 


### Bugfixes
**[TRANSLATE-2889](https://jira.translate5.net/browse/TRANSLATE-2889): API - logoutOnWindowClose does not work** <br>
If just closing the application window the user is now logged out correctly (if configured).

**[TRANSLATE-2888](https://jira.translate5.net/browse/TRANSLATE-2888): Comments, VisualReview / VisualTranslation - Commenting a segment via Visual does not work** <br>
The creation of comments in Visual by clicking in the Visual window was not working any more.


**[TRANSLATE-2887](https://jira.translate5.net/browse/TRANSLATE-2887): Editor general - Search/Replace is not working sometimes** <br>
Make Search/Replace work again on tasks with many segments.


## [5.6.4] - 2022-03-03

### Important Notes:
#### [TRANSLATE-2872](https://jira.translate5.net/browse/TRANSLATE-2872)
For all API users: The task import can now call an URL after finishing a task import and hanging imports are cancelled after (configurable) 48h.
 


### Added
**[TRANSLATE-2872](https://jira.translate5.net/browse/TRANSLATE-2872): Import/Export - Implement a URL callback triggered after task import is finished** <br>
Now a URL can be configured (runtimeOptions.import.callbackUrl) to be called after a task was imported. 
The URL is called via POST and receives the task object as JSON. So systems creating tasks via API are getting now immediate answer if the task is imported. The status of the task (error on error, or open on success) contains info about the import success. If the task import is running longer as 48 hours, the task is set to error and the callback is called too.

**[TRANSLATE-2860](https://jira.translate5.net/browse/TRANSLATE-2860): TermPortal - Attribute levels should be collapsed by default** <br>
Entry-level images added to language-level ones in Images-column of Siblings-panel

**[TRANSLATE-2483](https://jira.translate5.net/browse/TRANSLATE-2483): InstantTranslate - Save InstantTranslate translation to TM** <br>
Enables translation to be saved to "Instant-Translate" TM memory. For more info how this should be used, check this link: https://confluence.translate5.net/display/TAD/InstantTranslate


### Bugfixes
**[TRANSLATE-2882](https://jira.translate5.net/browse/TRANSLATE-2882): Main back-end mechanisms (Worker, Logging, etc.) - Calling updateProgress on export triggers error in the GUI** <br>
The progress update was also triggered on exports, causing some strange task undefined errors in the GUI.

**[TRANSLATE-2879](https://jira.translate5.net/browse/TRANSLATE-2879): TermPortal - termPM-role have no sufficient rights to transfer terms from TermPortal** <br>
Fixed: terms transfer was unavailable for termPM-users

**[TRANSLATE-2878](https://jira.translate5.net/browse/TRANSLATE-2878): Editor general - Metadata export error with array type filter** <br>
Filtered tasks with multiple option filter will no longer produce an error when Export meta data is clicked.

**[TRANSLATE-2876](https://jira.translate5.net/browse/TRANSLATE-2876): Search & Replace (editor) - Search and replace match case search** <br>
Error will no longer happen when searching with regular expression with match-case on.

**[TRANSLATE-2875](https://jira.translate5.net/browse/TRANSLATE-2875): Import/Export - Task Entity not found message on sending a invalid task setup in upload wizard** <br>
The message "Task Entity not found" was sometimes poping up when creating a new task with invalid configuration.

**[TRANSLATE-2874](https://jira.translate5.net/browse/TRANSLATE-2874): InstantTranslate, MatchAnalysis & Pretranslation - MT stops pre-translation at first repeated segment** <br>
On pre-translating against MT only, repetitions are producing an error, preventing the pre-translation to be finshed. 

**[TRANSLATE-2871](https://jira.translate5.net/browse/TRANSLATE-2871): InstantTranslate - Instant-translate result list name problem** <br>
Problem with listed results in instant translate with multiple resources with same name.

**[TRANSLATE-2870](https://jira.translate5.net/browse/TRANSLATE-2870): Task Management - Deleting a cloned task deletes the complete project** <br>
This bug affects only projects containing one target task. If this single task is cloned, and the original task was deleted, the whole project was deleted erroneously. This is changed now by implicitly creating a new project for such tasks. 

**[TRANSLATE-2858](https://jira.translate5.net/browse/TRANSLATE-2858): TermPortal - Proposal for Term entries cant be completed** <br>
Fixed proposal creation when newTermAllLanguagesAvailable config option is Off

**[TRANSLATE-2854](https://jira.translate5.net/browse/TRANSLATE-2854): TermPortal - Term-portal error: join(): Argument #1 ($pieces) must be of type array, string given** <br>
Fixed bug in loading terms.


## [5.6.3] - 2022-02-24

### Important Notes:
 


### Changed
**[TRANSLATE-2852](https://jira.translate5.net/browse/TRANSLATE-2852): TermPortal - Allow role TermPM to start Term-Translation-Workflow** <br>
termPM-role is now sifficient for Transfer-button to be shown.
TermPortal filter window will assume *-query if yet empty.

**[TRANSLATE-2851](https://jira.translate5.net/browse/TRANSLATE-2851): TermPortal - Security dialogue, when deleting something in TermPortal** <br>
Added confirmation dialogs on term/attribute deletion attempt


### Bugfixes
**[TRANSLATE-2856](https://jira.translate5.net/browse/TRANSLATE-2856): API, Editor general - Login/Logout issues** <br>
Fixed a race condition on logout that sometimes resulted in HTML being parsed as javascript.

**[TRANSLATE-2853](https://jira.translate5.net/browse/TRANSLATE-2853): Editor general - User association error** <br>
Solves problem when assigning users in import wizard after a workflow is changed and the current import produces only one task.

**[TRANSLATE-2846](https://jira.translate5.net/browse/TRANSLATE-2846): Task Management - Filter on QA errors column is not working** <br>
FIX: Sorting/Filtering of column "QS Errors" in task grid now functional

**[TRANSLATE-2818](https://jira.translate5.net/browse/TRANSLATE-2818): Auto-QA - Length-Check must Re-Evaluate also when processing Repititions** <br>
FIX: AutoQA now re-evaluates the length check for each segment individually when saving repititions


## [5.6.2] - 2022-02-17

### Important Notes:
#### [TRANSLATE-2834](https://jira.translate5.net/browse/TRANSLATE-2834)
The calculation of the match-rate of repeated and pre-translated fuzzy segments has been changed. More details in the concrete change log entry of that issue.

#### [TRANSLATE-2827](https://jira.translate5.net/browse/TRANSLATE-2827)
The matching between the workfile and pivot filenames is more easier right now, since the filename is compared now only to the first dot. So file.en-de.xlf matches now file.en-it.xlf and there is no need to rename such pivot files.
 


### Added
**[TRANSLATE-2789](https://jira.translate5.net/browse/TRANSLATE-2789): Import/Export - Import: Support specially tagged bilingual pdfs from a certain client** <br>
FEATURE: Support special bilingual PDFs as source for the Visual

**[TRANSLATE-2717](https://jira.translate5.net/browse/TRANSLATE-2717): Client management, Configuration - Take over client configuration from another client** <br>
New feature where customer configuration and default user assignments can be copied from one customer to another.


### Changed
**[TRANSLATE-2819](https://jira.translate5.net/browse/TRANSLATE-2819): SpellCheck (LanguageTool integration) - SpellChecker: Add toggle button to activate/deactivate the SpellCheck** <br>


**[TRANSLATE-2722](https://jira.translate5.net/browse/TRANSLATE-2722): InstantTranslate, TermPortal - Customizable header for InstantTranslate including custom HTML** <br>
Enables custom header content configuration in instant-translate and term-portal. For more info see the instant-translate and term-portal header section in this link https://confluence.translate5.net/pages/viewpage.action?pageId=3866712


### Bugfixes
**[TRANSLATE-2841](https://jira.translate5.net/browse/TRANSLATE-2841): Client management - Contents of clients tabs are not updated, when a new client is selected** <br>
Editing a customer in the customer panel is now possible with just selecting a row.

**[TRANSLATE-2840](https://jira.translate5.net/browse/TRANSLATE-2840): Import/Export - Delete user association if the task import fails** <br>
Remove all user associations from a task, if the task import fails. So no e-mail will be sent to the users.

**[TRANSLATE-2837](https://jira.translate5.net/browse/TRANSLATE-2837): Okapi integration - Change default segmentation rules to match Trados and MemoQ instead of Okapi and Across** <br>
So far translate5 (based on Okapi) did not segment after a colon.
Since Trados and MemoQ do that by default, this is changed now to make translate5 better compatible with the vast majority of TMs out there.

**[TRANSLATE-2834](https://jira.translate5.net/browse/TRANSLATE-2834): MatchAnalysis & Pretranslation - Change repetition behaviour in pre-translation** <br>
On pre-translations with using fuzzy matches, repeated segments may be filled with different tags / amount of tags as there are tags in the source content. Then the repetition algorithm could not process such segments as repetitions and finally the analysis was not counting them as repetitions.
Now such segments always count as repetition in the analysis, but it does not get the 102% matchrate (since this may lead the translator to jump over the segment and ignore its content). Therefore such a repeated segment is filled with the fuzzy match content and the fuzzy match-rate. If the translator then edits and fix the fuzzy to be the correct translation , and then uses the repetition editor to fill the repetitions, then it is set to 102% matchrate.

**[TRANSLATE-2832](https://jira.translate5.net/browse/TRANSLATE-2832): LanguageResources - Language filter in language resources overview is wrong** <br>
Language filter in language resources overview will filter for rfc values instead of language name.

**[TRANSLATE-2828](https://jira.translate5.net/browse/TRANSLATE-2828): Editor general - Pivot language selector for zip uploads** <br>
Pivot language can now be set when uploading zip in the import wizard.

**[TRANSLATE-2827](https://jira.translate5.net/browse/TRANSLATE-2827): Import/Export, Task Management - Improve workfile and pivot file matching** <br>
The matching between the workfile and pivot filenames is more easier right now, since the filename is compared now only to the first dot. So file.en-de.xlf matches now file.en-it.xlf and there is no need to rename such pivot files.

**[TRANSLATE-2826](https://jira.translate5.net/browse/TRANSLATE-2826): TermPortal, TermTagger integration - processStatus is not correctly mapped by tbx import** <br>
processStatus is now set up correctly on processStatus-col in terms_term-table

**[TRANSLATE-2818](https://jira.translate5.net/browse/TRANSLATE-2818): Auto-QA - AutoQA: Length-Check must Re-Evaluate also when processing Repititions** <br>
FIX: AutoQA now re-evaluates the length check for each segment individually when saving repititions


## [5.6.1] - 2022-02-09

### Important Notes:
 


### Changed
**[TRANSLATE-2810](https://jira.translate5.net/browse/TRANSLATE-2810): TermPortal - All roles should be able to see all terms with all process status.** <br>
unprocessed-terms are now searchable even if user has no termProposer-role

**[TRANSLATE-2809](https://jira.translate5.net/browse/TRANSLATE-2809): TermPortal - Reimport term should be only possible for tasks created by the Term-Translation workflow** <br>
Reimport of terms back to their TermCollections is possible only for task, created via TermPortal terms transfer function


### Bugfixes
**[TRANSLATE-2825](https://jira.translate5.net/browse/TRANSLATE-2825): Task Management - Multiple files with multiple pivot files can not be uploaded** <br>
Multiple files with multiple pivot files can not be added in the task creation wizard. The pivot files are marked as invalid.

**[TRANSLATE-2824](https://jira.translate5.net/browse/TRANSLATE-2824): Okapi integration - Enable aggressive tag clean-up in Okapi for MS Office files by default** <br>
Office often creates an incredible mess with inline tags, if users edit with character based markup.
Okapi has an option to partly clean this up when converting an office file.
This option is now switched on by default.

**[TRANSLATE-2821](https://jira.translate5.net/browse/TRANSLATE-2821): Auto-QA - Empty segment check does not report completely empty segments** <br>
Segments with completely empty targets are now counted in AutoQA: Empty-check

**[TRANSLATE-2817](https://jira.translate5.net/browse/TRANSLATE-2817): VisualReview / VisualTranslation - Solve Problems with CommentNavigation causing too much DB strain** <br>
FIX: Loading of Comment Navigation may was slow

**[TRANSLATE-2816](https://jira.translate5.net/browse/TRANSLATE-2816): Comments - Comment Overview performance problem and multiple loading calls** <br>
"AllComments" store: Prevent multiple requests by only making new ones when none are pending.

**[TRANSLATE-2815](https://jira.translate5.net/browse/TRANSLATE-2815): Import/Export, Task Management - Upload time out for bigger files** <br>
The upload timeout in the import wizard is increased to prevent timeouts for slower connections.

**[TRANSLATE-2814](https://jira.translate5.net/browse/TRANSLATE-2814): VisualReview / VisualTranslation - Solve Problems with Caching of plugin resources** <br>
FIX: Resources needed for the visual may become cached too long generating JS errors

**[TRANSLATE-2808](https://jira.translate5.net/browse/TRANSLATE-2808): TermPortal - Mind sublanguages while terms transfer validation** <br>
Sublanguages are now respected while terms transfer validation

**[TRANSLATE-2803](https://jira.translate5.net/browse/TRANSLATE-2803): Editor general - Displaying the logos causes issues** <br>
If the consortium logos were shown with a configured delay on the application startup, this may lead to problems when loading the application via an URL containing a task ID to open that task directly.

**[TRANSLATE-2802](https://jira.translate5.net/browse/TRANSLATE-2802): Task Management - Add isReview and isTranslation methods to task entity** <br>
Internal functions renamed.

**[TRANSLATE-2685](https://jira.translate5.net/browse/TRANSLATE-2685): Editor general - Error on pasting tags inside segment-editor** <br>
There was JS problem when editing a segment and pasting external content containing XML fragments.


## [5.6.0] - 2022-02-03

### Important Notes:
#### [TRANSLATE-2801](https://jira.translate5.net/browse/TRANSLATE-2801)
In the last release it was introduced that segments edited with the repetition editor was getting always the 102% match-rate for repetitions. Since is now changed so that this affects only translations and in review tasks the match rate is not touched in using repetitions.

#### [TRANSLATE-2796](https://jira.translate5.net/browse/TRANSLATE-2796)


#### [TRANSLATE-2780](https://jira.translate5.net/browse/TRANSLATE-2780)
For embedded usage of the translate5 editor only: In visual review simple mode there is now also a close application button.




#### [TRANSLATE-2671](https://jira.translate5.net/browse/TRANSLATE-2671)
There is a JavaScript Mediaplayer used which is under MIT License:
https://github.com/mediaelement/mediaelement
https://www.mediaelementjs.com/
This player is added as a resource to the public resources of the visual, not via composer

#### [TRANSLATE-2080](https://jira.translate5.net/browse/TRANSLATE-2080)
The first page of the project / task creation wizard was completely reworked with regard to the file upload. Now files can be added by drag and drop. The source and target language of bilingual files is automatically read out from the file and set then in wizard. This allows project creation directly out of a bunch of files without putting them in a ZIP file before. The well known ZIP import will still work.
 


### Added
**[TRANSLATE-2727](https://jira.translate5.net/browse/TRANSLATE-2727): Task Management - Column for "ended" date of a task in the task grid and the exported meta data file for a task** <br>
A new column "ended date" is added to the task overview. It is filled automatically with the timestamp when the task is ended by the pm (not to be confused with finishing a workflow step).

**[TRANSLATE-2671](https://jira.translate5.net/browse/TRANSLATE-2671): Import/Export, VisualReview / VisualTranslation - WYSIWIG for Videos** <br>
The visual now has capabilities to load a video as source together with segments and their timecodes (either as XSLX or SRT file). This gives the following new Features:

* Video highlights the timecoded segments when the player reaches the play position
* Annotations can be added to the video that appear as tooltip with an arrow pointing to the position on the selected timecodes frame
* Import of subtitle (.srt) files as workfiles
* Player can be navigated by clicking on the segments in the grid to play the segment
* Clicking on the timerail of the video highlights the associated segment
* Jumping from segment to segment, forth and back with player buttons and shortcuts
* In the Comment/Annotation Overview, clicking Comments/Annotations will navigate the video
* The Items in the Comment/Annotation Overview show their timecodes and are ordered by timecode

The Following prequesites must be fullfilled by a video to be used as visual source:
* mp4 file-format,
* h264 Codec
* max FullHD (1920x1080) resolution


**[TRANSLATE-2540](https://jira.translate5.net/browse/TRANSLATE-2540): Auto-QA - Check "target is empty or contains only spaces, punctuation, or alike"** <br>
Empty segments check added

**[TRANSLATE-2537](https://jira.translate5.net/browse/TRANSLATE-2537): Auto-QA - Check inconsistent translations** <br>
Added consistency checks: segments with same target, but different source and segments with same source, but different target. In both cases tags ignored.

**[TRANSLATE-2491](https://jira.translate5.net/browse/TRANSLATE-2491): TermPortal - Term-translation-Workflow** <br>
Added ability to transfer terms from TermPortal to Translate5, and import back to those terms TermCollection(s) once translated

**[TRANSLATE-2080](https://jira.translate5.net/browse/TRANSLATE-2080): Task Management - Round up project creation wizard by refactoring and enhancing first screen** <br>
The first page of the project / task creation wizard was completely reworked with regard to the file upload. Now files can be added by drag and drop. The source and target language of bilingual files is automatically read out from the file and set then in wizard. This allows project creation directly out of a bunch of files without putting them in a ZIP file before. The well known ZIP import will still work.


### Changed
**[TRANSLATE-2792](https://jira.translate5.net/browse/TRANSLATE-2792): TermPortal - Sort attributes filter drop down alphabetically** <br>
options in TermPortal filter-window attributes-combobox are now sorted alphabetically

**[TRANSLATE-2777](https://jira.translate5.net/browse/TRANSLATE-2777): TermPortal - Usability enhancements for TermPortal** <br>
Added a number of usability enhancements for TermPortal




**[TRANSLATE-2678](https://jira.translate5.net/browse/TRANSLATE-2678): VisualReview / VisualTranslation - WYSIWIG for Videos: Export Video Annotations** <br>
See TRANSLATE-2671

**[TRANSLATE-2676](https://jira.translate5.net/browse/TRANSLATE-2676): VisualReview / VisualTranslation - WYSIWIG for Videos: Frontend: Extending Annotations for Videos** <br>
See TRANSLATE-2671

**[TRANSLATE-2675](https://jira.translate5.net/browse/TRANSLATE-2675): VisualReview / VisualTranslation - WYSIWIG for Videos: Frontend: New IframeController "Video", new Visual iframe for Videos** <br>
See TRANSLATE-2671

**[TRANSLATE-2674](https://jira.translate5.net/browse/TRANSLATE-2674): VisualReview / VisualTranslation - WYSIWIG for Videos: Add new Review-type, Video-HTML-Template** <br>
See TRANSLATE-2671

**[TRANSLATE-2673](https://jira.translate5.net/browse/TRANSLATE-2673): Import/Export - WYSIWIG for Videos: Import Videos with Excel Timeline** <br>
See TRANSLATE-2671


### Bugfixes
**[TRANSLATE-2801](https://jira.translate5.net/browse/TRANSLATE-2801): Repetition editor - Do not update matchrate on repetitions for review tasks** <br>
In the last release it was introduced that segments edited with the repetition editor was getting always the 102% match-rate for repetitions. Since is now changed so that this affects only translations and in review tasks the match rate is not touched in using repetitions.

**[TRANSLATE-2800](https://jira.translate5.net/browse/TRANSLATE-2800): Editor general - User association wizard error when removing users** <br>
Solves problem when removing associated users from the task and quickly selecting another user from the grid afterwards.

**[TRANSLATE-2797](https://jira.translate5.net/browse/TRANSLATE-2797): TBX-Import - Definition is not addable on language level due wrong default datatype** <br>
In some special cases the collected term attribute types and labels were overwriting some default labels. This so overwritten labels could then not be edited any more in the GUI.

**[TRANSLATE-2796](https://jira.translate5.net/browse/TRANSLATE-2796): TermPortal - Change tooltip / Definition on language level cant be set / Double attribute of "Definition" on entry level** <br>
tooltips changed to 'Forbidden' / 'Verboten' for deprecatedTerm and supersededTerm statuses

**[TRANSLATE-2795](https://jira.translate5.net/browse/TRANSLATE-2795): Import/Export, TermPortal - Term TBX-ID and term tbx-entry-id should be exported in excel-export** <br>
TermCollection Excel-export feature is now exporting Term/Entry tbx-ids instead of db-ids

**[TRANSLATE-2794](https://jira.translate5.net/browse/TRANSLATE-2794): TermPortal - TermEntries are not deleted on TermCollection deletion** <br>
TermEntries are not deleted automatically on TermCollection deletion due a missing foreign key connection in database.

**[TRANSLATE-2791](https://jira.translate5.net/browse/TRANSLATE-2791): TermTagger integration - Extend term attribute mapping to <descrip> elements** <br>
In the TermPortal proprietary TBX attributes could be mapped to the Usage Status. This was restricted to termNotes, now all types of attributes can be mapped (for example xBool_Forbidden in descrip elements).

**[TRANSLATE-2790](https://jira.translate5.net/browse/TRANSLATE-2790): OpenTM2 integration - Disable OpenTM2 fixes if requesting t5memory** <br>
The OpenTM2 TMX import fixes are not needed anymore for the new t5memory, they should be disabled if the language resource is pointing to t5memory instead OpenTM2.

**[TRANSLATE-2788](https://jira.translate5.net/browse/TRANSLATE-2788): Configuration - No default values in config editor for list type configs with defaults provided** <br>
For some configuration values the config editor in the settings was not working properly. This is fixed now.

**[TRANSLATE-2785](https://jira.translate5.net/browse/TRANSLATE-2785): LanguageResources - Improve DeepL error handling and other fixes** <br>
DeepL was shortly not reachable, the produced errors were not handled properly in translate5, this is fixed. 

**[TRANSLATE-2781](https://jira.translate5.net/browse/TRANSLATE-2781): Editor general - Access to job is still locked after user has closed his window** <br>
If a user just closes the browser it may happen that the there triggered automaticall logout does not work. Then the edited task of the user remains locked. The garbage cleaning and the API access to so locked jobs are improved, so that the task is getting unlocked then.

**[TRANSLATE-2780](https://jira.translate5.net/browse/TRANSLATE-2780): VisualReview / VisualTranslation - Add missing close button to visual review simple mode** <br>
For embedded usage of the translate5 editor only: In visual review simple mode there is now also a close application button - in the normal mode it was existing already.

**[TRANSLATE-2776](https://jira.translate5.net/browse/TRANSLATE-2776): Import/Export - XLF translate no with different mrk counts lead to unknown mrk tag error** <br>
The combination of XLF translate = no and a different amount of mrk segments in source and target was triggering erroneously this error.

**[TRANSLATE-2775](https://jira.translate5.net/browse/TRANSLATE-2775): InstantTranslate - Issue with changing the language in InstantTranslate** <br>
fixed issue with changing the language in InstantTranslate

**[TRANSLATE-2774](https://jira.translate5.net/browse/TRANSLATE-2774): Workflows - The calculation of a tasks workflow step is not working properly** <br>
The workflow step calculation of a task was calculating a wrong result if a workflow step (mostly visiting of a visitor) was added as first user.

**[TRANSLATE-2773](https://jira.translate5.net/browse/TRANSLATE-2773): Auto-QA - Wrong job loading method in quality context used** <br>
There were errors on loading a tasks qualities on a no workflow task.

**[TRANSLATE-2771](https://jira.translate5.net/browse/TRANSLATE-2771): OpenTM2 integration - translate5 sends bx / ex tags to opentm2 instead of paired g-tag** <br>
The XLF tag pairer does not work if the string contains a single tag in addition to the paired tag.

**[TRANSLATE-2770](https://jira.translate5.net/browse/TRANSLATE-2770): TermPortal - Creating terms in TermPortal are creating null definitions instead empty strings** <br>
Fixed a bug on importing TBX files with empty definitions.

**[TRANSLATE-2769](https://jira.translate5.net/browse/TRANSLATE-2769): VisualReview / VisualTranslation - Hide and collapse annotations is not working** <br>
Fixes the problem with hide and collapse annotations in visual.

**[TRANSLATE-2767](https://jira.translate5.net/browse/TRANSLATE-2767): TermPortal - Issues popped up in Transline presentation** <br>
fixed js error, added tooltips for BatchWindow buttons

**[TRANSLATE-2723](https://jira.translate5.net/browse/TRANSLATE-2723): Task Management, User Management - Reminder E-Mail sent multiple times** <br>
Fixed an annoying bug responsible for sending the deadline reminder e-mails multiple times. 

**[TRANSLATE-2712](https://jira.translate5.net/browse/TRANSLATE-2712): VisualReview / VisualTranslation - Visual review: cancel segment editing removes the content from layout** <br>
FIXED: Bug where Text in the Visual disappeared, when the segment-editing was canceled


## [5.5.6] - 2021-12-17

### Important Notes:
#### [TRANSLATE-2756](https://jira.translate5.net/browse/TRANSLATE-2756)
The match-rate in the editor for repetitions is now 102% as defined and not any more the original percentage from the repeated segment. This is now the same behaviour as it is already in the analysis.
 


### Changed
**[TRANSLATE-2761](https://jira.translate5.net/browse/TRANSLATE-2761): Test for tbx specialchars import** <br>
Added test for import tbx containing specialchars

**[TRANSLATE-2760](https://jira.translate5.net/browse/TRANSLATE-2760): AutoQA also processed when performing an Analysis  & add AutoQA Reanalysis** <br>
* The AnalysisOperation in a task's MatchAnalysis panel now covers a re-evaluation of the QA
* This makes the seperate Button to tag the Terms obsolete, so it is removed
* added Button to Re-check the QA in the task's QA panel

**[TRANSLATE-2488](https://jira.translate5.net/browse/TRANSLATE-2488): Excel export of TermCollection** <br>
Added ability to export TermCollections into xlsx-format


### Bugfixes
**[TRANSLATE-2763](https://jira.translate5.net/browse/TRANSLATE-2763): Term term entries older than current import deletes also unchanged terms** <br>
TBX Import: The setting "Term term entries older than current import" did also delete the terms which are contained unchanged in the TBX.

**[TRANSLATE-2759](https://jira.translate5.net/browse/TRANSLATE-2759): Deleted newlines were still counting as newline in length calculation** <br>
When using the line counting feature in segment content deleted newlines were still counted since they still exist as trackchanges.

**[TRANSLATE-2758](https://jira.translate5.net/browse/TRANSLATE-2758): scrollToAnnotation: Annotation references, sorting and size** <br>
Scrolling, size and sorting of annotations has been fixed

**[TRANSLATE-2756](https://jira.translate5.net/browse/TRANSLATE-2756): Segments were locked after pre-translation but no translation content was set** <br>
It could happen that repeated segments were blocked with a matchrate >= 100% but no content was pre-translated in the segment. Also the target original field was filled wrong on using repetitions. And the match-rate for repetitions is now 102% as defined and not original the percentage from the repeated segment. This is now the same behaviour as in the analysis.

**[TRANSLATE-2755](https://jira.translate5.net/browse/TRANSLATE-2755): Workers getting PHP fatal errors remain running** <br>
Import workers getting PHP fatal errors were remain running, instead of being properly marked crashed. 

**[TRANSLATE-2751](https://jira.translate5.net/browse/TRANSLATE-2751): Mouse over segment with add-annotation active** <br>
The cursor will be of type cross when the user is in annotation creation mode and the mouse is over the segment.

**[TRANSLATE-2750](https://jira.translate5.net/browse/TRANSLATE-2750): Make project tasks overview and task properties resizable and stateful** <br>
The height of the project tasks overview and the property panel of a single task are now resizeable.

**[TRANSLATE-2749](https://jira.translate5.net/browse/TRANSLATE-2749): Blocked segments in workflow progress** <br>
The blocked segments now will be included in the workflow step progress calculation.

**[TRANSLATE-2747](https://jira.translate5.net/browse/TRANSLATE-2747): Proposals are not listed in search results in some cases** <br>
TermPortal: it's now possible to find proposals for existing terms using 'Unprocessed' as a value of 'Process status' filter

**[TRANSLATE-2746](https://jira.translate5.net/browse/TRANSLATE-2746): Add a Value for "InstantTranslate: TM minimum match rate"** <br>
Set the default value to 70 for minimum matchrate allowed to be displayed in InstantTranslate result list for TM language resources.

**[TRANSLATE-2745](https://jira.translate5.net/browse/TRANSLATE-2745): 500 Internal Server Error on creating comments** <br>
Creating a segment comment was leading to an error due the new comment overview feature.

**[TRANSLATE-2744](https://jira.translate5.net/browse/TRANSLATE-2744): XLIFF2 Export with more than one translator does not work** <br>
The XLIFF2 export was not working with more than one translator associated to the task.

**[TRANSLATE-2719](https://jira.translate5.net/browse/TRANSLATE-2719): TermPortal result column is empty, despite matches are shown** <br>
TermPortal: fixed 'left column is empty, despite matches are shown' bug


## [5.5.5] - 2021-12-08

### Important Notes:
#### [TRANSLATE-2740](https://jira.translate5.net/browse/TRANSLATE-2740)
WARNING: PHP 8 is now required on server side!

#### [TRANSLATE-2666](https://jira.translate5.net/browse/TRANSLATE-2666)
To use this Feature the following prequesites must be fullfilled:
* Google Vision API Key stored in  configuration
* PHP Imagick Extension installed & working

#### [TRANSLATE-2387](https://jira.translate5.net/browse/TRANSLATE-2387)
Google material icons are integrated per composer.

#### [TRANSLATE-2303](https://jira.translate5.net/browse/TRANSLATE-2303)
A new Comment section has been added to the left-hand side of the Segment editor. It lists all the segment comments and visual annotations ordered by page.
 


### Added
**[TRANSLATE-2728](https://jira.translate5.net/browse/TRANSLATE-2728): Link terms in segment meta panel to the termportal** <br>
In the segments meta panel all terms of the currently edited segment are shown. This terms are now clickable linked to the termportal - if the termportal is available.

**[TRANSLATE-2713](https://jira.translate5.net/browse/TRANSLATE-2713): Use HTML linking in Visual based on xml/xsl, if workfiles are xliff** <br>
Added option to add a XML/XSL combination as visual source direct in the /visual folder of the import zip: If there is an XML in the /visual folder with a linked XSL stylesheet that is present in the /visual folder as well, the visual HTML is generated from these files using the normal, text-based segmentation (and not aligning the XML against the imported bilingual workfiles)

**[TRANSLATE-2666](https://jira.translate5.net/browse/TRANSLATE-2666): WYSIWYG for Images with Text** <br>
This new feature enables using a single Image as a source for a Visual. 
This Image is then analyzed (OCR) and the found text can be edited in the right WYSIWIG-frame. 
* The Image must be imported in the subfolder /visual/image of the import-zip
* A single WebFont-file (*.woff) can be added alongside the Image and then will be used as Font for the whole text on the Image
* If no font is provided, Arial is the general fallback
* Any text not present in the bilingual file in /workfiles will be removed from the OCR's output. This means, the bilingual file should contain exactly the text, that is expected to be on the image and to be translated

**[TRANSLATE-2387](https://jira.translate5.net/browse/TRANSLATE-2387): Annotate visual** <br>
The users are able to add text annotations(markers) where ever he likes in the visual area.  Also the users are able to create segment annotations when clicking on a segment in the layout.

**[TRANSLATE-2303](https://jira.translate5.net/browse/TRANSLATE-2303): Overview of comments** <br>
A new Comment section has been added to the left-hand side of the Segment editor.
It lists all the segment comments and visual annotations ordered by page. The type is indicated by a small symbol to the left. Its background color indicates the authoring user.
When an element of that list is clicked, translate5 jumps to the corresponding remark, either in the VisualReview or in the segment grid.
On hover the full remark is shown in a tooltip, together with the authoring user and the last change date.
New comments are added in realtime to the list.


### Changed
**[TRANSLATE-2740](https://jira.translate5.net/browse/TRANSLATE-2740): PHP 8 is now required - support for older PHP versions is dropped** <br>
Translate5 and all dependencies use now PHP 8.

**[TRANSLATE-2733](https://jira.translate5.net/browse/TRANSLATE-2733): Embed translate5 task video in help window** <br>
Embed the translate5 task videos as iframe in the help window. The videos are either in german or english, they are chosen automatically depending on the GUI interface. A list of links to jump to specific parts of the videos are provided.

**[TRANSLATE-2726](https://jira.translate5.net/browse/TRANSLATE-2726): Invert tooltipt font color in term-column in left panel** <br>
Term tooltip font color set to black for proposals to be readable

**[TRANSLATE-2693](https://jira.translate5.net/browse/TRANSLATE-2693): Write tests for new TermPortal** <br>
Created tests for all termportal api endpoints

**[TRANSLATE-2670](https://jira.translate5.net/browse/TRANSLATE-2670): WYSIWIG for Images: Frontend - General Review-type, new (mostly dummy) ImageScroller, extensions IframeController** <br>
see Translate-2666

**[TRANSLATE-2669](https://jira.translate5.net/browse/TRANSLATE-2669): WYSIWIG for Images: Extend Font-Management** <br>
see TRANSLATE-2666

**[TRANSLATE-2668](https://jira.translate5.net/browse/TRANSLATE-2668): WYSIWIG for Images: Add new Review-type, add worker & file managment, creation of HTML file representing the review** <br>
see TRANSLATE-2666

**[TRANSLATE-2667](https://jira.translate5.net/browse/TRANSLATE-2667): WYSIWIG for Images: Implement Text Recognition** <br>
see TRANSLATE-2666

**[TRANSLATE-2487](https://jira.translate5.net/browse/TRANSLATE-2487): Edit an attribute for multiple occurrences at once** <br>
Added ability for attributes batch editing


### Bugfixes
**[TRANSLATE-2741](https://jira.translate5.net/browse/TRANSLATE-2741): Segment processing status is wrong on unchanged segments with tags** <br>
On reviewing the processing state of a segment was set wrong if the segment contains tags and was saved unchanged.

**[TRANSLATE-2739](https://jira.translate5.net/browse/TRANSLATE-2739): Segment length validation does also check original target on TM usage** <br>
On tasks using segment length restrictions some segments could not be saved if content was overtaken manually from a language resource and edited afterwards to fit in the length restriction.

**[TRANSLATE-2737](https://jira.translate5.net/browse/TRANSLATE-2737): VisualReview height not saved in session** <br>
Persist VisualReview height between reloads.

**[TRANSLATE-2736](https://jira.translate5.net/browse/TRANSLATE-2736): State of show/hide split iframe is not saved correctly** <br>
Fix issues with the saved state of the show/hide split frame button in the visual

**[TRANSLATE-2732](https://jira.translate5.net/browse/TRANSLATE-2732): Advanced filter users list anonymized users query** <br>
Solves advanced filter error for users with no "read-anonymized" users right.

**[TRANSLATE-2731](https://jira.translate5.net/browse/TRANSLATE-2731): No redirect to login page if maintenance is scheduled** <br>
The initial page of the translate5 instance does not redirect to the login page if a maintenance is scheduled.

**[TRANSLATE-2730](https://jira.translate5.net/browse/TRANSLATE-2730): Improve maintenance handling regarding workers** <br>
If maintenance is scheduled the export was hanging in a endless loop, also import related workers won't start anymore one hour before maintenance. 

**[TRANSLATE-2729](https://jira.translate5.net/browse/TRANSLATE-2729): PDO type casting error in bind parameters** <br>
The user will no longer receive an error when the customer was deleted.

**[TRANSLATE-2724](https://jira.translate5.net/browse/TRANSLATE-2724): Translation error in the Layout** <br>
Workflow name is localized now.

**[TRANSLATE-2720](https://jira.translate5.net/browse/TRANSLATE-2720): Termportal initial loading takes dozens of seconds** <br>
Solved termportal long initial loading problem

**[TRANSLATE-2715](https://jira.translate5.net/browse/TRANSLATE-2715): String could not be parsed as XML - on tbx import** <br>
The exported TBX was no valid XML therefore was an error on re-importing that TBX.

**[TRANSLATE-2708](https://jira.translate5.net/browse/TRANSLATE-2708): Visual review: iframe scaling problem** <br>
Enables zoom in in all directions in visual.

**[TRANSLATE-2707](https://jira.translate5.net/browse/TRANSLATE-2707): correct display language-selection in Instant-Translate** <br>
Fixed the language listing in InstantTranslate, which was broken for a lot of languages.

**[TRANSLATE-2706](https://jira.translate5.net/browse/TRANSLATE-2706): Not all repetitions are saved after exchanging the term-collection** <br>
Not all repeated segments were changed if saving repetitions with terminology and the term-collection was changed in the task.

**[TRANSLATE-2700](https://jira.translate5.net/browse/TRANSLATE-2700): Improve termtagging performance due table locks** <br>
The queuing of the segments prepared for term tagging is improved, so that multiple term taggers really should work in parallel. 


## [5.5.4] - 2021-11-15

### Important Notes:
#### [TRANSLATE-2638](https://jira.translate5.net/browse/TRANSLATE-2638)
Implement new layout for InstantTranslate as discussed with the consortium members.

#### [TRANSLATE-2404](https://jira.translate5.net/browse/TRANSLATE-2404)
The layout of the start analysis panel has been changed. The checkboxes were reordered, so that they need less space. And a separate button was introduced to run the terminology check. That means running a terminology check after the import is now completely independent of running analyses.
 


### Added
**[TRANSLATE-2638](https://jira.translate5.net/browse/TRANSLATE-2638): Implement new layout for InstantTranslate** <br>
Implement new layout for InstantTranslate as discussed with the consortium members.


### Changed
**[TRANSLATE-2683](https://jira.translate5.net/browse/TRANSLATE-2683): Editor Embedded: export may be started while last edited segment still is saving** <br>
For translate5 embedded usage: the JS API function Editor.util.TaskActions.isTaskExportable() returns true or false if the currently opened task can be exported regarding the last segment save call.

**[TRANSLATE-2649](https://jira.translate5.net/browse/TRANSLATE-2649): Small fixes for TermPortal** <br>
A number of fixes/improvements implemented

**[TRANSLATE-2632](https://jira.translate5.net/browse/TRANSLATE-2632): TermPortal code refactoring** <br>
Termportal code and related tests are now refactored for better maintainability.

**[TRANSLATE-2489](https://jira.translate5.net/browse/TRANSLATE-2489): Change of attribute label in GUI** <br>
Added ability to edit attribute labels


### Bugfixes
**[TRANSLATE-2701](https://jira.translate5.net/browse/TRANSLATE-2701): Source term from InstantTranslate not saved along with target term** <br>
TermPortal: In case the source term, that had been translated in InstantTranslate was not contained in the TermCollection, only the target term was added, the new source term not. This is fixed.

**[TRANSLATE-2699](https://jira.translate5.net/browse/TRANSLATE-2699): Add missing ID column to task overview and fix date type in meta data excel** <br>
Add missing ID column to task overview and fix date type in meta data excel export.

**[TRANSLATE-2696](https://jira.translate5.net/browse/TRANSLATE-2696): Malicious segments may lead to endless loop while term tagging** <br>
Segments with specific / malicious content may lead to endless loops while term tagging so that the task import is running forever.

**[TRANSLATE-2695](https://jira.translate5.net/browse/TRANSLATE-2695): JS error task is null** <br>
Due unknown conditions there might be an error task is null in the GUI. Since the reason could not be determined, we just fixed the symptoms. As a result a user might click twice on the menu action item to get all items.

**[TRANSLATE-2694](https://jira.translate5.net/browse/TRANSLATE-2694): Improve GUI logging for false positive "Not all repeated segments could be saved" messages** <br>
Improve GUI logging for message like: Not all repeated segments could be saved. With the advanced logging should it be possible to detect the reason behind.

**[TRANSLATE-2691](https://jira.translate5.net/browse/TRANSLATE-2691): SDLXLIFF diff export is failing with an endless loop** <br>
The SDLXLIFF export with diff fails by hanging in an endless loop if the segment content has a specific form. This is fixed by updating the underlying diff library.

**[TRANSLATE-2690](https://jira.translate5.net/browse/TRANSLATE-2690): task is null: User association in import wizard** <br>
Fix for "task is null" error in import user-assoc wizard

**[TRANSLATE-2689](https://jira.translate5.net/browse/TRANSLATE-2689): TBX import fails because of some ID error** <br>
Terminology containing string based IDs could not be imported if the same ID was used one time lower case and one time uppercase.

**[TRANSLATE-2688](https://jira.translate5.net/browse/TRANSLATE-2688): For many languages the lcid is missing in LEK_languages** <br>
Added some missing LCID values in the language table.

**[TRANSLATE-2687](https://jira.translate5.net/browse/TRANSLATE-2687): Wrong texts in system config options** <br>
Improve description and GUI-text for system configurations.

**[TRANSLATE-2686](https://jira.translate5.net/browse/TRANSLATE-2686): TermTagging does not work after import** <br>
If term tagging is started along with analysis on an already imported task, nothing gets tagged.

**[TRANSLATE-2404](https://jira.translate5.net/browse/TRANSLATE-2404): There is no way to run only the terminology check only after import** <br>
There is no way to start the terminology check only from the language resource association panel, a analysis is always started as well. This is changed now.


## [5.5.3] - 2021-10-28

### Important Notes:
 


### Added
**[TRANSLATE-2613](https://jira.translate5.net/browse/TRANSLATE-2613): Add Locaria Logo to Website and App** <br>
Added Locaria logo to the app

**[TRANSLATE-2076](https://jira.translate5.net/browse/TRANSLATE-2076): Define analysis fuzzy match ranges** <br>
The ranges of the match rates for the analysis can now be defined in the configuration: runtimeOptions.plugins.MatchAnalysis.fuzzyBoundaries


### Changed
**[TRANSLATE-2652](https://jira.translate5.net/browse/TRANSLATE-2652): Add keyboard short-cuts for Accept/Reject TrackChanges** <br>
ENHANCEMENT: Keyboard Shortcuts for TrackChanges accept/reject feature

**[TRANSLATE-2625](https://jira.translate5.net/browse/TRANSLATE-2625): Solve tag errors automatically on export** <br>
Internal Tag Errors (faulty structure) will be fixed automatically when exporting a task: Orphan opening/closing tags will be removed, structurally broken tag pairs will be corrected. The errors in the task itself will remain.


### Bugfixes
**[TRANSLATE-2681](https://jira.translate5.net/browse/TRANSLATE-2681): Language naming mismatch regarding the chinese languages** <br>
The languages zh-Hans and zh-Hant were missing. Currently zh-CN was named "Chinese simplified", this is changed now to Chinese (China).

**[TRANSLATE-2680](https://jira.translate5.net/browse/TRANSLATE-2680): Okapi empty target fix was working only for tasks with editable source** <br>
The Okapi export fix TRANSLATE-2384 was working only for tasks with editable source. Now it works in general. Also in case of an export error, the XLF in the export zip was named as original file (so file.docx was containing XLF). This is changed, so that the XLF is named now file.docx.xlf). Additionally a export-error.txt is created which explains the problem.


**[TRANSLATE-2679](https://jira.translate5.net/browse/TRANSLATE-2679): Microsoft translator connection language code mapping is not case insensitive** <br>
Microsoft translator returns zh-Hans for simplified Chinese, we have configured zh-hans in our language table. Therefore the language can not be used. This is fixed now.

**[TRANSLATE-2672](https://jira.translate5.net/browse/TRANSLATE-2672): UI theme selection may be wrong if system default is not triton theme** <br>
The users selected theme may be resetted to triton theme instead to the system default theme.

**[TRANSLATE-2664](https://jira.translate5.net/browse/TRANSLATE-2664): Fix TermPortal client-specific favicon and CSS usage** <br>
The technical possibilities to customize the TermPortal layout were not fully migrated from the old termportal.

**[TRANSLATE-2658](https://jira.translate5.net/browse/TRANSLATE-2658): Wrong tag numbering between source and target in imported MemoQ XLF files** <br>
For MemoQ XLF files it may happen that tag numbering between source and target was wrong. This is corrected now.

**[TRANSLATE-2657](https://jira.translate5.net/browse/TRANSLATE-2657): Missing term roles for legacy admin users** <br>
Activate the term portal roles for admin users not having them.

**[TRANSLATE-2656](https://jira.translate5.net/browse/TRANSLATE-2656): Notify associated users checkbox is not effective** <br>
The bug is fixed where the "notify associated users checkbox" in the import wizard does not take effect when disabled.

**[TRANSLATE-2592](https://jira.translate5.net/browse/TRANSLATE-2592): Reduce and by default hide use of TrackChanges in the translation step** <br>
Regarding translation and track changes: changes are only recorded for pre-translated segments and changes are hidden by default for translators (and can be activated by the user in the view modes drop-down of the editor)




## [5.5.2] - 2021-10-11

### Important Notes:
 


### Changed
**[TRANSLATE-2637](https://jira.translate5.net/browse/TRANSLATE-2637): Warn regarding merging terms** <br>
Warning message will be shown when using merge terms functionality in term collection import/re-import

**[TRANSLATE-2630](https://jira.translate5.net/browse/TRANSLATE-2630): Add language resource name to language resource pop-up - same for projects** <br>
Improves info messages and windows titles in language resources, project and task overview.


### Bugfixes
**[TRANSLATE-2597](https://jira.translate5.net/browse/TRANSLATE-2597): Set resource usage log lifetime by default to 30 days** <br>
This will set the default lifetime days for resources usage log configuration to 30 days when there is no value set.

**[TRANSLATE-2528](https://jira.translate5.net/browse/TRANSLATE-2528): Instant-translate and Term-portal route after login** <br>
Fixed problems accessing TermPortal / InstantTranslate with external URLs.


## [5.5.1] - 2021-10-07

### Important Notes:
#### [TRANSLATE-2645](https://jira.translate5.net/browse/TRANSLATE-2645)
Please set innodb_ft_min_token_size in your mysql installation to 1 and  	innodb_ft_enable_stopword to 0.
This is necessary for TermPortal to find words shorter than 3 characters. If you did already install translate5 5.5.0 on your server OR if you did install translate 5.5.1 BEFORE you did change that settings in your mysql installation, then you would need to update the fulltext indexes of your DB term-tables manually. 
If this is the case, please call "./translate5.sh termportal:reindex" or contact us, how to do this.
Please run "./translate5.sh system:check" to check afterwards if everything is properly configured.

#### [TRANSLATE-2634](https://jira.translate5.net/browse/TRANSLATE-2634)
By default the new PDF documentation for translate5s editor is integrated in the help window of the editor. If you do not want to show this to your users, please deactivate it after the upgrade in the system configuration (GUI). For detailed info how this can be configured please check this link: https://confluence.translate5.net/display/CON/Database+based+configuration
If you happen to have defined custom help content in the editor, and the help content is in written text/pdf/html , it make sense this content to be loaded with editordocumentation.phtml
 


### Added
**[TRANSLATE-2640](https://jira.translate5.net/browse/TRANSLATE-2640): Remove InstantTranslate on/off button from InstantTranslate and move functionality to configuration** <br>
The auto-translate feature in instant translate can be configured if active for each client.


### Changed
**[TRANSLATE-2645](https://jira.translate5.net/browse/TRANSLATE-2645): TermPortal: set mysql fulltext search minimum word length to 1 and disable stop words** <br>
Please set innodb_ft_min_token_size in your mysql installation to 1 and  	innodb_ft_enable_stopword to 0.
This is necessary for TermPortal to find words shorter than 3 characters. If you did already install translate5 5.5.0 on your server OR if you did install translate 5.5.1 BEFORE you did change that settings in your mysql installation, then you would need to update the fulltext indexes of your DB term-tables manually. 
If this is the case, please call "./translate5.sh termportal:reindex" or contact us, how to do this.
Please run "./translate5.sh system:check" to check afterwards if everything is properly configured.

**[TRANSLATE-2641](https://jira.translate5.net/browse/TRANSLATE-2641): AdministrativeStatus default attribute and value** <br>
The "Usage Status (administrativeStatus)" attribute is now the leading one regarding the term status. Its value is synchronized to all other similar attributes (normativeAuthorization and other custom ones).

**[TRANSLATE-2634](https://jira.translate5.net/browse/TRANSLATE-2634): Integrate PDF documentation in translate5 help window** <br>
Pdf documentation in the editor help window is available now.
To change PDF location or disable see config runtimeOptions.frontend.helpWindow.editor.documentationUrl

**[TRANSLATE-2607](https://jira.translate5.net/browse/TRANSLATE-2607): Make type timeout in InstantTranslate configurable** <br>
The translation delay in instant translate can be configured now.


### Bugfixes
**[TRANSLATE-2644](https://jira.translate5.net/browse/TRANSLATE-2644): Task related notification emails should link directly to the task** <br>
Currently task related notification E-Mails do not point to the task but to the portal only. This is changed.

**[TRANSLATE-2643](https://jira.translate5.net/browse/TRANSLATE-2643): Usability improvements: default user assignment** <br>
Usability improvements in default user association overview.


## [5.5.0] - 2021-09-30

### Important Notes:
#### [TRANSLATE-2623](https://jira.translate5.net/browse/TRANSLATE-2623)
All user-set themes different from "thriton" or "arria" are set to the translate5 default theme: thriton. From now on, there are only 2 available themes in the dropdown: Dark theme (Aria) and Default theme (Triton)

#### [TRANSLATE-1405](https://jira.translate5.net/browse/TRANSLATE-1405)
Due the new TermPortal all terminology data is migrated to a new database structure, this may run a long time depending on the size of the term databases. The progress can be watched in the transalte5 system log.

The config values runtimeOptions.tbx.termLabelMap.* are merged into one config runtimeOptions.tbx.termLabelMap

The config values runtimeOptions.tbx.termImportMap.* (which maps custom to valid TBX term status value) are moved into a own configuration table. 
If the previoulsy confiuration was completly in DB all is fine, conversion is done automatically. If such config was still/again in installation.ini, then migratation must be done by hand. Unknown status values are logged on TBX import.
 


### Added
**[TRANSLATE-2302](https://jira.translate5.net/browse/TRANSLATE-2302): Accept and reject TrackChanges** <br>
Plugin TrackChanges
* added capabilities for the editor, to accept/reject changes from preceiding workflow-steps
* reduced tracking of changes in the translation step, only pretranslated segments are tracked
* by default, TrackChanges is invisible in the translation step
* the visibility of changes is normally reduced to the changes of the preceiding workflow steps
* the visibility and capability to accept/reject for the editor can be set via the user assocciations on the task and customer level

**[TRANSLATE-1405](https://jira.translate5.net/browse/TRANSLATE-1405): TermPortal as terminology management solution** <br>
Introduced the brand new TermPortal, now completely usable as terminology management solution.


### Changed
**[TRANSLATE-2629](https://jira.translate5.net/browse/TRANSLATE-2629): Integrate beo-proposals for German names of standard tbx attributes** <br>
Term-portal improvement UI names of standard TBX attributes

**[TRANSLATE-2625](https://jira.translate5.net/browse/TRANSLATE-2625): Solve tag errors automatically on export** <br>
Internal Tag Errors (faulty structure) will be fixed automatically when exporting a task: Orphan opening/closing tags will be removed, structurally broken tag pairs will be corrected. The errors in the task itself will remain.

**[TRANSLATE-2623](https://jira.translate5.net/browse/TRANSLATE-2623): Move theme switch button and language switch button in settings panel** <br>
The drop-down for switching the translate5 language and translate5 theme is moved under "Preferences" ->"My settings" tab.

**[TRANSLATE-2622](https://jira.translate5.net/browse/TRANSLATE-2622): CLI video in settings help window** <br>
Integrate CLI video in preferences help page.

**[TRANSLATE-2611](https://jira.translate5.net/browse/TRANSLATE-2611): Check Visual Review URLs before downloading them if they are accessible** <br>
Added additional check for Visual Review URLs if the URL is accessible before downloading it to improve the logged error


### Bugfixes
**[TRANSLATE-2621](https://jira.translate5.net/browse/TRANSLATE-2621): Logging task specific stuff before task is saved leads to errors** <br>
In seldom cases it may happen that task specific errors should be logged in the time before the task was first saved to DB, this was producing a system error on processing the initial error and the information about the initial error was lost.

**[TRANSLATE-2618](https://jira.translate5.net/browse/TRANSLATE-2618): Rename tooltips for next segment in translate5** <br>
Improves tooltip text in editor meta panel segment navigation.

**[TRANSLATE-2614](https://jira.translate5.net/browse/TRANSLATE-2614): Correct translate5 workflow names of complex workflow** <br>
Improve the step names and translations of the complex workflow

**[TRANSLATE-2612](https://jira.translate5.net/browse/TRANSLATE-2612): Job status changes from open to waiting on deadline change** <br>
If the deadline of a job in a task is changed, the status of the job changes from "open" to "waiting". This is fixed.

**[TRANSLATE-2609](https://jira.translate5.net/browse/TRANSLATE-2609): Import of MemoQ comments fails** <br>
HOTFIX: MemoQ comment parsing produces corrupt comments with single comment nodes. Add Exception to the base parsing API to prevent usage of negative length's

**[TRANSLATE-2603](https://jira.translate5.net/browse/TRANSLATE-2603): Browser does not refresh cache for maintenance page** <br>
It could happen that users were hanging in the maintenance page - depending on their proxy / cache settings. This is solved now.

**[TRANSLATE-2602](https://jira.translate5.net/browse/TRANSLATE-2602): msg is not defined** <br>
Fixed a ordinary programming error in the frontend message bus.

**[TRANSLATE-2601](https://jira.translate5.net/browse/TRANSLATE-2601): role column is not listed in workflow mail** <br>
The role was not shown any more in the notification e-mails if a task was assigned to users.

**[TRANSLATE-2599](https://jira.translate5.net/browse/TRANSLATE-2599): reviewer can not open associated task in read-only mode** <br>
If a user with segment ranges tries to open a task read-only due workflow state waiting or finished this was resulting in an error.

**[TRANSLATE-2598](https://jira.translate5.net/browse/TRANSLATE-2598): Layout Change Logout** <br>
Changing translate5 theme will no longer logout the user.

**[TRANSLATE-2591](https://jira.translate5.net/browse/TRANSLATE-2591): comments of translate no segments are not exported anymore** <br>
comments of segments with translate = no were not exported any more, this is fixed now.


## [5.2.7] - 2021-08-06

### Important Notes:


### Bugfixes
**[TRANSLATE-2596](https://jira.translate5.net/browse/TRANSLATE-2596): Message bus session synchronization rights** <br>
Solves the problem where the message bus did not have the rights to synchronize the session.

**[TRANSLATE-2595](https://jira.translate5.net/browse/TRANSLATE-2595): Customers store autoload for not authorized users** <br>
Solves the problem with loading of the customers for not-authorized users.


## [5.2.6] - 2021-08-04

### Important Notes:
#### [TRANSLATE-2570](https://jira.translate5.net/browse/TRANSLATE-2570)
FIX must not be applied when imports are running

#### [TRANSLATE-2564](https://jira.translate5.net/browse/TRANSLATE-2564)
FIX must not be applied when imports are running

#### [TRANSLATE-2416](https://jira.translate5.net/browse/TRANSLATE-2416)
In order to use (set to a user) the new role PM-light, the admin or PM has to re-login and reload the application.
 


### Added
**[TRANSLATE-2580](https://jira.translate5.net/browse/TRANSLATE-2580): Add segment length check to AutoQA** <br>
AutoQA now incorporates a check of the(pixel based)  segment-length

**[TRANSLATE-2416](https://jira.translate5.net/browse/TRANSLATE-2416): Create PM-light system role** <br>
A new role PM-light is created, which may only administrate its own projects and tasks and has no access to user management or language resources management.


### Changed
**[TRANSLATE-2586](https://jira.translate5.net/browse/TRANSLATE-2586): Check the URLs in the reviewHtml.txt file for the visual** <br>
ENHANCEMENT: Warn and clean visual source URLs that can not be imported because they have a fragment "#"
ENHANCEMENT: Skip duplicates and clean URLs in the reviewHtml.txt file

**[TRANSLATE-2583](https://jira.translate5.net/browse/TRANSLATE-2583): Save config record instead of model sync** <br>
Code improvements in the configuration overview grid.


### Bugfixes
**[TRANSLATE-2589](https://jira.translate5.net/browse/TRANSLATE-2589): Exclude meta data of images for word files by default** <br>
By default translate5 will now not extract any more meta data of images, that are embedded in MS Word files.

**[TRANSLATE-2587](https://jira.translate5.net/browse/TRANSLATE-2587): Improve error logging** <br>
Improves error messages in instant-translate.

**[TRANSLATE-2585](https://jira.translate5.net/browse/TRANSLATE-2585): Evaluate auto_set_role acl for OpenID authentications** <br>
All missing mandatory translate roles for users authentication via SSO will be automatically added.

**[TRANSLATE-2584](https://jira.translate5.net/browse/TRANSLATE-2584): Across XLF with translate no may contain invalid segmented content** <br>
Across XLF may contain invalid segmented content for not translatable (not editable) segments. This is fixed by using the not segment content in that case.

**[TRANSLATE-2570](https://jira.translate5.net/browse/TRANSLATE-2570): AutoQA checks blocked segments / finds unedited fuzzy errors in unedited bilingual segments** <br>
ENHANCEMENT: blocked segments will no longer be evaluated in the quality-management, only if they have structural internal tag-errors they will appear in a new category for this
FIX: Missing internal tags may have been detected in untranslated empty segments
FIX: Added task-name & guid to error-logs regarding structural internal tag errors
FIX: Quality-Management is now bound to a proper ACL
FIX: Re-establish proper layout of action icons in Task-Grid



**[TRANSLATE-2564](https://jira.translate5.net/browse/TRANSLATE-2564): Do not render MQM-Tags parted by overlappings** <br>
FIX: MQM-Tags now are visualized with overlappings unresolved (not cut into pieves)



## [5.2.5] - 2021-07-20

### Important Notes:
#### [TRANSLATE-2388](https://jira.translate5.net/browse/TRANSLATE-2388)
The task usageMode can now be set via API on task creation (not as mentioned in the issue before via config).

#### [TRANSLATE-1808](https://jira.translate5.net/browse/TRANSLATE-1808)
Test this feature after making a release! 
- on our server with create instance 
- sole installation
 


### Added
**[TRANSLATE-2518](https://jira.translate5.net/browse/TRANSLATE-2518): Add project description to project and tasks** <br>
A project description can be added on project creation.

**[TRANSLATE-2477](https://jira.translate5.net/browse/TRANSLATE-2477): Language resource to task assoc: Set default for pre-translation and internal-fuzzy options in system config** <br>
Default values for "internal fuzzy", "translate MT" and "translate TM and Term" checkboxes  can be defined as system configuration configuration (overwritable on client level).

**[TRANSLATE-992](https://jira.translate5.net/browse/TRANSLATE-992): New Keyboard shortcuts for process / cancel repetition editor** <br>
Adding keyboard shortcuts to save (ctrl+s) or cancel (esc) the processing of repetitions in the repetition editor.


### Changed
**[TRANSLATE-2566](https://jira.translate5.net/browse/TRANSLATE-2566): Integrate Theme-Switch in translate5** <br>
Users are able to change the translate5 theme.

**[TRANSLATE-2381](https://jira.translate5.net/browse/TRANSLATE-2381): Visual: Enhance the reflow mechanism for overlapping elements** <br>
Visual: Improved Text-Reflow. This signifantly reduces the rate of PDFs that cannot be imported with a functional WYSIWIG preview. There now is a threshhold for detected reflow-rendering errors that can be raised for individual tasks that had to many errors on Import as a last ressort. Although that will rarely be neccessary.

**[TRANSLATE-1808](https://jira.translate5.net/browse/TRANSLATE-1808): Installer should set the timezone** <br>
The installer always set timezone europe/berlin, know the  user is asked on installation which timezone should be used.


### Bugfixes
**[TRANSLATE-2581](https://jira.translate5.net/browse/TRANSLATE-2581): Task user assoc workflow step drop-down filtering** <br>
If a user was added twice to a task, and the workflow step of the second user was changed to the same step of the first user, this led to a duplicated key error message.

**[TRANSLATE-2578](https://jira.translate5.net/browse/TRANSLATE-2578): Reload users to task association grid after task import finishes** <br>
Refresh users to task association grid after the task import is done.

**[TRANSLATE-2576](https://jira.translate5.net/browse/TRANSLATE-2576): Notify associated user button does not work** <br>
Fixes problem with "Notify users" button not sending emails.

**[TRANSLATE-2575](https://jira.translate5.net/browse/TRANSLATE-2575): System default configuration on instance or client level has no influence on Multiple user setting in import wizard** <br>
The default value for the "multiple user" setting drop-down was not correctly preset from config.

**[TRANSLATE-2573](https://jira.translate5.net/browse/TRANSLATE-2573): User assignment entry disappears in import wizard, when pre-assigned deadline is changed** <br>
Edited user association in import wizard was disappearing after switching the workflow.

**[TRANSLATE-2571](https://jira.translate5.net/browse/TRANSLATE-2571): ERROR in core: E9999 - TimeOut on waiting for the following materialized view to be filled** <br>
There was a problem when editing a default associated user of a task in the task add wizard. This is fixed now.

**[TRANSLATE-2568](https://jira.translate5.net/browse/TRANSLATE-2568): ModelFront plug-in is defect and prevents language resource usage** <br>
The ModelFront plug-in was defect and stopped match analysis and pre-translation from working.

**[TRANSLATE-2567](https://jira.translate5.net/browse/TRANSLATE-2567): TagProtection can not deal with line breaks in HTML attributes** <br>
When using TagProtection (protect plain HTML code in XLF as tags) line breaks in HTML attributes were not probably resolved.

**[TRANSLATE-2565](https://jira.translate5.net/browse/TRANSLATE-2565): GroupShare: Wrong tag order using the groupshare language resource** <br>
Nested internal tags were restored in wrong order if using a segment containing such tags from the groupshare language resource. 

**[TRANSLATE-2546](https://jira.translate5.net/browse/TRANSLATE-2546): New uuid column of match analysis is not filled up for existing analysis** <br>
The new uuid database column of the match analysis table is not filled up for existing analysis.

**[TRANSLATE-2544](https://jira.translate5.net/browse/TRANSLATE-2544): Focus new project after creating it** <br>
After task/project creation the created project will be focused in the project overview

**[TRANSLATE-2525](https://jira.translate5.net/browse/TRANSLATE-2525): npsp spaces outside of mrk-tags of mtype "seg" should be allowed** <br>
Due to invalid XLIFF from Across there is a check in import, that checks, if there is text outside of mrk-tags of mtype "seg" inside of seg-source or target tags. Spaces and tags are allowed, but nbsp characters were not so far. This is changed and all other masked whitespace tags are allowed to be outside of mrk tags too.

**[TRANSLATE-2388](https://jira.translate5.net/browse/TRANSLATE-2388): Ensure config overwrite works for "task usage mode"** <br>
The task usageMode can now be set via API on task creation.


## [5.2.4] - 2021-07-06

### Important Notes:
#### [TRANSLATE-2560](https://jira.translate5.net/browse/TRANSLATE-2560)
These changes must not be applied with running imports!

#### [TRANSLATE-2545](https://jira.translate5.net/browse/TRANSLATE-2545)
The usage of at least PHP >= 7.4 is mandatory now! (PHP 8 is not yet supported).
The field role on associating a user to a task is deprecated. Use the field workflowStepName instead. The change is downwards compatible and creates a warning on further usage of the role field. See https://confluence.translate5.net/display/TAD/Task+User+Associations

#### [TRANSLATE-2081](https://jira.translate5.net/browse/TRANSLATE-2081)
The form field group "Auto-Assignment" based on languages in the user management is removed completely.
If you now want to pre-define, if a user should be assigned to a task automatically, please use the new very extended mechanisms in the client management panel.
Existing pre-definitions are NOT migrated.
The OpenID-Connect configuration is moved into a separate tab.
 


### Added
**[TRANSLATE-2081](https://jira.translate5.net/browse/TRANSLATE-2081): Preset of user to task assignments** <br>
Provides the functionality to configure auto-assignment of users to tasks on client configuration level, filtered by language, setting the to be used user and workflow step.


### Changed
**[TRANSLATE-2545](https://jira.translate5.net/browse/TRANSLATE-2545): Flexibilize workflow by putting role and step definitions in database** <br>
The definition of all available workflow steps and roles is now stored in the database instead in a fixed workflow class. A new complex workflow is added for demonstration purposes and usage if wanted.

**[TRANSLATE-2516](https://jira.translate5.net/browse/TRANSLATE-2516): Add user column to Excel language resource usage log** <br>
The spreadsheet with the usage log of language resources is extended with a user column, that shows, who actually did the request.


### Bugfixes
**[TRANSLATE-2563](https://jira.translate5.net/browse/TRANSLATE-2563): Adjust texts that connect analysis and locking of 100%-Matches** <br>
Adjust texts that connect analysis and locking of 100%-Matches.

**[TRANSLATE-2560](https://jira.translate5.net/browse/TRANSLATE-2560): Combination of term-tagging and enabled source editing duplicates tags on saving a segment, AutoQA removes/merges TrackChanges from different Users** <br>
FIXED BUG in the TermTagger leading to duplication of internal tags when source editing was activated
FIXED BUG in the AutoQA leading to TrackChanges tags from different users being merged

**[TRANSLATE-2557](https://jira.translate5.net/browse/TRANSLATE-2557): Select correct okapi file filter for txt-files by default** <br>
By default the file format conversion used for txt-files the okapi-filter "moses-text". In this filter xml-special characters like & < > where kept in encoded version when the file was reconverted back to txt after export from translate5. This was wrong. Now the default was changed to the okapi plain-text filter, what handles the xml-special chars correctly.

**[TRANSLATE-2547](https://jira.translate5.net/browse/TRANSLATE-2547): Clean-up project tasks** <br>
Deleting a project deletes all files from database but not from disk. This is fixed.

**[TRANSLATE-2536](https://jira.translate5.net/browse/TRANSLATE-2536): Task Configuration Panel does show old Values after Import** <br>
FIX: Task Qualities & Task Configuration panels now update their view automatically after import to avoid outdated date is being shown

**[TRANSLATE-2533](https://jira.translate5.net/browse/TRANSLATE-2533): Line breaks in InstantTranslate are deleted** <br>
InstantTranslate dealing of line breaks is fixed.


## [5.2.3] - 2021-06-24

### Important Notes:
This release contains important Hotfixes for the releases 5.2.0 / 5.2.1 / 5.2.2!

### Bugfixes
**[TRANSLATE-2556](https://jira.translate5.net/browse/TRANSLATE-2556): PHP Error Specified column previousOrigin is not in the row** <br>
This error was triggered in certain circumstances by the import of SDLXLIFF files containing empty origin information.

**[TRANSLATE-2555](https://jira.translate5.net/browse/TRANSLATE-2555): XML errors in uploaded TMX files are not shown properly in the TM event log** <br>
The XML error was logged in the system log, but was not added to the specific log of the TM. This is changed now so that the PM can see what is wrong.

**[TRANSLATE-2554](https://jira.translate5.net/browse/TRANSLATE-2554): BUG TermTagger Worker: Workers are scheduled exponentially** <br>
FIXED: Bug in TermTagger Worker leads to scheduling workers exponentially what causes database deadlocks

**[TRANSLATE-2552](https://jira.translate5.net/browse/TRANSLATE-2552): Typos in translate5** <br>
Fixes couple of typos in translate5 locales


## [5.2.2] - 2021-06-09

### Important Notes:
 
**This release contains important Hotfixes for [TRANSLATE-2196](https://jira.translate5.net/browse/TRANSLATE-2196): Complete Auto QA for translate5 released in version 5.2.0.**


### Bugfixes
**[TRANSLATE-2500](https://jira.translate5.net/browse/TRANSLATE-2500): Worker Architecture: Solving Problems with Deadlocks and related Locking/Mutex Quirks** <br>
Improved the internal worker handling regarding DB dead locks and a small opportunity that workers run twice.


## [5.2.1] - 2021-06-08

### Important Notes:
In addition to the below listed fixes, several minor fixes for the new features of major release 5.2.0 were implemented.


### Changed
**[TRANSLATE-2501](https://jira.translate5.net/browse/TRANSLATE-2501): Create table that contains all attribute types of a termCollection** <br>
All available data type attributes for term collection are saved in database.


### Bugfixes
**[TRANSLATE-2532](https://jira.translate5.net/browse/TRANSLATE-2532): ERROR in core: E9999 - Call to a member function getMessage() on null** <br>
Fix a seldom PHP error, only happening when translate5 instance is tried to be crawled.

**[TRANSLATE-2531](https://jira.translate5.net/browse/TRANSLATE-2531): Microsoft Translator language resource connector is not properly implemented** <br>
The Microsoft Translator language resource connector is not properly implemented regarding error handling and if a location restriction is used in the azure API configuration.

**[TRANSLATE-2529](https://jira.translate5.net/browse/TRANSLATE-2529): Brute-Force attacks may produce: ERROR in core: E9999 - $request->getParam('locale') war keine gültige locale** <br>
Providing invalid locales as parameter on application loading has produced an error. Now the invalid locale is ignored and the default one is loaded.

**[TRANSLATE-2526](https://jira.translate5.net/browse/TRANSLATE-2526): Run analysis on task import wizard** <br>
Fixes problem with analysis and pre-translation not triggered for default associated resources on task import (without opening the language resources wizard)


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

