
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

INSERT INTO `LEK_change_log` (`dateOfChange`, `jiraNumber`, `type`, `title`, `description`, `userGroup`) VALUES ('2020-05-07', 'TRANSLATE-1999', 'feature', 'Optional custom content can be displayed in the file area of the editor', 'See configuration runtimeOptions.editor.customPanel.url and runtimeOptions.editor.customPanel.title', '8'),
('2020-05-07', 'TRANSLATE-2028', 'feature', 'Change how help window urls are defined in Zf_configuration', 'See https://confluence.translate5.net/display/CON/Database+based+configuration', '8'),
('2020-05-07', 'TRANSLATE-2039', 'feature', 'InstantTranslate: Translate text area segmented against TM and MT and Terminology', 'InstantTranslate can deal now with multiple sentences', '2'),
('2020-05-07', 'TRANSLATE-2048', 'feature', 'Provide segment auto-state summary via API', 'A segment auto-state summary is now provided via API', '8'),
('2020-05-07', 'TRANSLATE-2044', 'change', 'Change Edge browser support version', 'Minimum Edge Version is now: Version 80.0.361.50: 11. Februar or higher', '14'),
('2020-05-07', 'TRANSLATE-2042', 'change', 'Introduce a tab panel used for the administrative main components', 'The administration main menu was improved', '12'),
('2020-05-07', 'TRANSLATE-1926', 'change', 'Add LanguageResources: show all services that translate5 can handle', 'On adding LanguageResources also the not configured resources are shown (disabled, but the user knows now that it does exist)', '12'),
('2020-05-07', 'TRANSLATE-2031', 'change', 'NEC-TM: Categeries are mandatory', 'On the creation and usage of NEC-TM categeries are now mandatory', '12'),
('2020-05-07', 'TRANSLATE-1769', 'bugfix', 'Fuzzy-Matching of languages in TermTagging does not work, when a TermCollection is added after task import', 'If choosing a language with out a sublanguage in translate5 (just "de" for example) the termtagger should also tag terms in the language de_DE. This was not working anymore.', '12'),
('2020-05-07', 'TRANSLATE-2024', 'bugfix', 'InstantTranslate file translation: Segments stay empty, if no translation is provided', 'If for a segment no translation could be find, the source text remains.', '2'),
('2020-05-07', 'TRANSLATE-2029', 'bugfix', 'NEC-TM Error in GUI: Save category assocs', 'A JS error occured on saving NEC-TMs', '12'),
('2020-05-07', 'TRANSLATE-2030', 'bugfix', 'Garbage Collector produces DB DeadLocks due wrong timezone configuration', 'The problem was fixed internally, although it should be ensured, that the DB and PHP run in the same timezone.', '8'),
('2020-05-07', 'TRANSLATE-2033', 'bugfix', 'JS error when leaving the application', 'The JS error "Sync XHR not allowed in page dismissal" was solved', '8'),
('2020-05-07', 'TRANSLATE-2034', 'bugfix', 'In Chinese languages some ^h characters are added which prevents export then due invalid XML ', 'The characters are masked now as special character, which prevents the XML getting scrambled.', '6'),
('2020-05-07', 'TRANSLATE-2036', 'bugfix', 'Handle empty response from the spell check', 'The Editor may handle empty spell check results now', '2'),
('2020-05-07', 'TRANSLATE-2037', 'bugfix', 'VisualReview: Leaving a task leads to an error in Microsoft Edge', 'Is fixed now, was reproduced on Microsoft Edge: 44.18362.449.0', '2'),
('2020-05-07', 'TRANSLATE-2050', 'bugfix', 'Change Language Resource API so that it is understandable', 'Especially the handling of the associated clients and the default clients was improved', '8'),
('2020-05-07', 'TRANSLATE-2051', 'bugfix', 'TaskGrid advanced datefilter is not working', 'Especially the date at was not working', '4'),
('2020-05-07', 'TRANSLATE-2055', 'bugfix', 'Switch okapi import to tags, that show tag markup to translators', 'Instead of g and x tags Okapi produces know ph, it, bpt and ept tags, which in the end shows the real tag content to the user in the Editor.', '6'),
('2020-05-07', 'TRANSLATE-2056', 'bugfix', 'Finished task can not be opened readonly', 'Tasks finished in the workflow could not be opened anymore read-only by the finishing user', '6'),
('2020-05-07', 'TRANSLATE-2057', 'bugfix', 'Disable term tagging in read only segments', 'This can be changed in the configuration, so that terms of non editable segments can be tagged if needed', '6'),
('2020-05-07', 'TRANSLATE-2059', 'bugfix', 'Relais import fails with DB error message', 'This is fixed now.', '4'),
('2020-05-07', 'TRANSLATE-2023', 'bugfix', 'InstantTranslate - Filetranslation: Remove associations to LanguageResources after translation', 'On using the file translation in InstantTranslate some automatically used language resources are now removed again', '8');
