
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

-- userGroup calculation: basic: 1; editor: 2; pm: 4; admin: 8
            
INSERT INTO `LEK_change_log` (`dateOfChange`, `jiraNumber`, `type`, `title`, `description`, `userGroup`) VALUES ('2020-10-06', 'TRANSLATE-2244', 'change', 'Embed translate5 guide video in help window', 'Embed the translate5 guide videos as iframe in the help window. The videos are either in german or english, they are chosen automatically depending on the GUI interface. A list of links to jump to specific parrs of the videos are provided.', '8'),
('2020-10-06', 'TRANSLATE-2214', 'change', 'Change SSO Login Button Position', 'The SSO Login Button is now placed right of the login button instead between the login input field and the submit button.', '8'),
('2020-10-06', 'TRANSLATE-1237', 'change', 'Exported xliff 2.1 is not valid', 'The XLF 2.1 output is now valid (validated against https://okapi-lynx.appspot.com/validation).', '12'),
('2020-10-06', 'TRANSLATE-2243', 'bugfix', 'Task properties panel stays enabled without selected task', 'Sometimes the task properties panel was enabled even when there is no task selected in the project tasks grid.', '12'),
('2020-10-06', 'TRANSLATE-2242', 'bugfix', 'Source text translation in matches and concordance search grid', 'Change the German translation for matches and concordance search grid source column from: Quelltext to Ausgangstext.', '6'),
('2020-10-06', 'TRANSLATE-2240', 'bugfix', 'PDF in InstantTranslate', 'Translating a PDF file with InstantTranslate document upload leads to a file with 0 bytes and file extension .pdf instead a TXT file named .pdf.txt. (like Okapi is producing it).', '12'),
('2020-10-06', 'TRANSLATE-2239', 'bugfix', 'Installer is broken due zend library invocation change', 'The installer is broken since the the zend libraries were moved and integrated with the composer auto loader. Internally a class_exist is used which now returns always true which is wrong for the installation.', '8'),
('2020-10-06', 'TRANSLATE-2237', 'bugfix', 'Auto state translations', 'Update some of the auto state translations (see image attached)', '6'),
('2020-10-06', 'TRANSLATE-2236', 'bugfix', 'Change quality and state flags default values', 'Update the default value of the runtimeOptions.segments.stateFlags and runtimeOptions.segments.qualityFlags to more usable demo values.', '15'),
('2020-10-06', 'TRANSLATE-2235', 'bugfix', 'Not all segmentation rules (SRX rules) in okapi bconf acutally are triggered', 'The reason seems to be, that all segment break="no" rules of a language need to be above all break="yes" rules, even if the break="yes" rules do not interfere with the break="no" rules.', '12'),
('2020-10-06', 'TRANSLATE-2234', 'bugfix', 'Error on global customers filter', '-', '12'),
('2020-10-06', 'TRANSLATE-2233', 'bugfix', 'Remove autoAssociateTaskPm workflow action', 'Remove the autoAssociateTaskPm workflow functionality from the workflow action configuration and from the source code too.', '12'),
('2020-10-06', 'TRANSLATE-2232', 'bugfix', 'Action button "Associated tasks" is visible for non TM resources', 'The action button for re-importing segments to tm in the language resource overview grid is visible for no tm resources (ex: the button is visible for mt resources). The button only should be visible for TM resources.', '12'),
('2020-10-06', 'TRANSLATE-2218', 'bugfix', 'Trying to edit a segment with disabled editable content columns lead to JS error', 'Trying to edit a segment when all editable columns are hidden, was leading to a JS error.', '15'),
('2020-10-06', 'TRANSLATE-2173', 'bugfix', 'Language resources without valid configuration should be shown with brackets in "Add" dialogue', 'Available but not configured LanguageResources are shown in the selection list in brackets.', '12'),
('2020-10-06', 'TRANSLATE-2075', 'bugfix', 'Fuzzy-Selection of language resources does not work as it should', 'When working with language resources the mapping between the languages of the language resource and the languages in translate5 was improved, especially in matching sub-languages. For Details see the issue.', '12'),
('2020-10-06', 'TRANSLATE-2041', 'bugfix', 'Tag IDs of created XLF 2 are invalid for importing in other CAT tools', 'The XLF 2.1 output is now valid (validated against https://okapi-lynx.appspot.com/validation).', '12'),
('2020-10-06', 'TRANSLATE-2011', 'bugfix', 'translate 2 standard term attributes for TermPortal', 'Added the missing term-attribute translations.', '12');
