# Change Log

All notable changes to translate5 will be documented here.

For a reference to the issue keys see http://jira.translate5.net

Missing Versions are merged into in the next upper versions, so no extra section is needed.

All updates are (downwards) compatible! If not this is listed in the important release notes.









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

