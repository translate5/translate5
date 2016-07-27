# Change Log
All notable changes to translate5 will be documented here.
For a reference to the issue keys see http://jira.translate5.net
Missing Versions are merged into in the next upper versions, so no extra section is needed.


## [2.4.14] - 2016-07-27
### Added
TRANSLATE-707: Export comments to sdlxliff
TRANSLATE-684: adding a matchRateType column
translate5 Plugins: added support for translations, public files and php controllers

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
