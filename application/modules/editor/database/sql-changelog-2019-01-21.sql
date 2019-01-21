
-- /*
-- START LICENSE AND COPYRIGHT
-- 
--  This file is part of translate5
--  
--  Copyright (c) 2013 - 2017 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.
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

INSERT INTO `LEK_change_log` (`dateOfChange`, `jiraNumber`, `type`, `title`, `description`, `userGroup`) VALUES ('2019-01-21', 'TRANSLATE-1523', 'feature', 'Configurable: Should source files be auto-attached as reference files?', 'Now it is configurable if non bilingual source files are auto-attached as reference files.', '12'),
('2019-01-21', 'TRANSLATE-1543', 'change', 'InstantTranslate: Only show main languages in InstantTranslate language selection', 'In InstantTranslate only the main languages are shown in the language drop-downs. The sub-languages can be enabled via config.', '14'),
('2019-01-21', 'TRANSLATE-1533', 'change', 'Switch API value, that is checked to know, if Globalese engine is available', 'Just implemented a Globalese API change.', '8'),
('2019-01-21', 'TRANSLATE-1540', 'bugfix', 'Filtering language resources by customer replaces resource name with customer name', 'When filtering the language resources by customer, the resource name was replaced with the customer name.', '12'),
('2019-01-21', 'TRANSLATE-1541', 'bugfix', 'For title tag of TermPortal and InstantTranslate translation mechanism is not used', 'The application title of TermPortal and InstantTranslate was not properly translated', '12'),
('2019-01-21', 'TRANSLATE-1537', 'bugfix', 'GroupShare sync throws an exception if a language can no be found locally', 'Now the synchronisation proceeds and the missing languages are logged.', '12'),
('2019-01-21', 'TRANSLATE-1535', 'bugfix', 'GroupShare license cache ID may not contain special characters', 'Each user using GroupShare locks a GroupShare license. The locking process could not deal with E-Mails as usernames.', '12'),
('2019-01-21', 'TRANSLATE-1534', 'bugfix', 'internal target marker persists as translation on pretranslation with fuzzy match analysis', 'On using fuzzy analysis with pre-translation some segments were pre-translated with an internal marker.', '14'),
('2019-01-21', 'TRANSLATE-1532', 'bugfix', 'Globalese integration: error 500 thrown, if no engines are available', 'No it is handled in a user friendly way if no Globalese engines are available for the selected language combination.', '12'),
('2019-01-21', 'TRANSLATE-1518', 'bugfix', 'Multitenancy language resources to customer association fix (customer assoc migration fix)', 'The association between language resources and defaultcustomer was fixed.', '12'),
('2019-01-21', 'TRANSLATE-1522', 'bugfix', 'Autostaus "Autoübersetzt" is untranslated in EN', 'Autostaus "Autoübersetzt" is untranslated in EN', '12'),
('2019-01-21', 'VISUAL-57', 'bugfix', 'VisualReview: Prevent translate5 to scroll layout, if segment has been opened by click in the layout', 'The layout was scrolling to another segment when opening a segment via click on an alias segment.', '14'),
('2019-01-21', 'TRANSLATE-1519', 'bugfix', 'Termcollection is not assigned with default customer with zip import', 'Termcollection is not assigned with default customer with zip import', '12'),
('2019-01-21', 'TRANSLATE-1521', 'bugfix', 'OpenTM2 Matches with <it> or <ph> tags are not shown', 'Now the content of the tags is removed, so that the results can be shown instead of discarding it.', '14'),
('2019-01-21', 'TRANSLATE-1501', 'bugfix', 'TrackChanges: Select a word with double click then type new text produces JS error and wrong track changes', 'TrackChanges: Select a word with double click then type new text produces JS error and wrong track changes', '14'),
('2019-01-21', 'TRANSLATE-1544', 'bugfix', 'JS error on using grid filters', 'JS error on using grid filters solved: Cannot read property \'isCollapsedPlaceholder\' of undefined', '12'),
('2019-01-21', 'TRANSLATE-1527', 'bugfix', 'JS error on copy text content in task overview area', 'JS error on copy text content in task overview area solved: JS Error: this.getSegmentGrid(...) is undefined', '12'),
('2019-01-21', 'TRANSLATE-1524', 'bugfix', 'JS Error when leaving task faster as server responds terms of segment', 'JS Error when leaving task faster as server responds terms of segment solved: Cannot read property \'updateLayout\' of undefined', '12'),
('2019-01-21', 'TRANSLATE-1503', 'bugfix', 'CTRL+Z does not undo CTRL+.', 'CTRL+Z does not undo CTRL+.', '14'),
('2019-01-21', 'TRANSLATE-1412', 'bugfix', 'TermPortal logout URL is wrong - same for InstantTranslate', 'TermPortal logout URL is wrong - same for InstantTranslate', '12'),
('2019-01-21', 'TRANSLATE-1517', 'bugfix', 'Add user: no defaultcustomer if no customer is selected', 'Add user: no defaultcustomer if no customer is selected', '12'),
('2019-01-21', 'TRANSLATE-1538', 'bugfix', 'click in white head area of TermPortal or InstantTranslate leads to action', 'click in white head area of TermPortal or InstantTranslate leads to action', '14'),
('2019-01-21', 'TRANSLATE-1539', 'bugfix', 'click on info icon of term does not transfer sublanguage, when opening term in TermPortal', 'click on info icon of term does not transfer sublanguage, when opening term in TermPortal', '14');