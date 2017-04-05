# Change Log
All notable changes to translate5 will be documented here.
For a reference to the issue keys see http://jira.translate5.net
Missing Versions are merged into in the next upper versions, so no extra section is needed.

## [2.5.23] - 2017-04-05
###Changed
Included OpenTM2 Community Edition updated to Version 1.3.4.2

## [2.5.22] - 2017-04-05
###Changed
TRANSLATE-854: Change font-size in ergo-mode to 13pt

###Bugfixes
TRANSLATE-849: wrong usage of findRecord in frontend leads to wired errors
TRANSLATE-853: installer fails with "-" in database name

## [2.5.14] - 2017-03-30
###Added
TRANSLATE-807: Change default editor mode to ergonomic mode
TRANSLATE-796: Enhance concordance search
TRANSLATE-826: Show only a maximum of MessageBox messages
TRANSLATE-821: Switch translate5 to Triton theme
TRANSLATE-502: OpenTM2-Integration into MatchResource Plug-In

###Changed
TRANSLATE-820: Generalization of Languages model
TRANSLATE-818: internal tag replace id usage with data-origid and data-filename
MITTAGQI-30: Update license informations

###Bugfixes
TRANSLATE-833: Add application locale to the configurable Help URL
TRANSLATE-839: Ensure right character set of DB import with importer
TRANSLATE-844: roweditor minimizes its height
TRANSLATE-758: DbUpdater under Windows can not deal with DB Passwords with special characters
TRANSLATE-805: show match type tooltip also in row editor

## [2.5.9] - 2017-01-23
###Bugfixes
fixing an installer issue with already existing tables while installation
TRANSLATE-783: Indentation of fields

## [2.5.7] - 2017-01-19
###Bugfixes
TRANSLATE-767: Changealike Window title was always in german
TRANSLATE-787: Translate5 editor does not start anymore - on all installed instances
TRANSLATE-782: Change text in task creation pop-up
TRANSLATE-781: different white space inside of internal tags leads to failures in relais import
TRANSLATE-780: id column of LEK_browser_log must not be NULL
TRANSLATE-768: Db Updater complains about Zf_worker_dependencies is missing

## [2.5.6] - 2016-11-04
###Changed
Content changes in the pages surround the editor

###Bugfixes
TRANSLATE-758: DbUpdater under Windows can not deal with DB Passwords with special characters
TRANSLATE-761: Task must be reloaded when switching from state import to open

## [2.5.2] - 2016-10-26
###Added
TRANSLATE-726: New Column "type" in ChangeLog Plugin
TRANSLATE-743: Implement filters in change-log grid

###Changed
improved worker exception logging
TRANSLATE-759: Introduce config switch to set application language instead of browser recognition
TRANSLATE-751: Updater must check for invalid DB settings
TRANSLATE-612: User-Authentication via API - enable session deletion, login counter
TRANSLATE-644: enable editor-only usage in translate5 - enable direct task association
TRANSLATE-750: Make API auth default locale configurable

###Bugfixes
TRANSLATE-760: The source and target columns are missing sometimes after import for non PM users
TRANSNET-10: Login and passwd reset page must be also in english
TRANSLATE-684: Introduce match-type column - fixing tests
TRANSLATE-745: double tooltip on columns with icon in taskoverview
TRANSLATE-749: session->locale sollte an dieser Stelle bereits durch LoginController gesetzt sein
TRANSLATE-753: change-log-window is not translated on initial show

## [2.5.1] - 2016-09-27
###Added
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

###Changed
TRANSLATE-646: search for "füll" is finding the attribute-value "full", that is contained in every internal tag
TRANSLATE-750: Make API auth default locale configurable

###Bugfixes
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



For formatting of this file see http://keepachangelog.com/
