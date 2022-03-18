
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
            
INSERT INTO `LEK_change_log` (`dateOfChange`, `jiraNumber`, `type`, `title`, `description`, `userGroup`) VALUES ('2022-03-17', 'TRANSLATE-2895', 'feature', 'Import/Export - Optionally remove single tags and bordering tag pairs at segment borders', 'The behaviour how tags are ignored from XLF (not SDLXIFF!) imports has been improved so that all surrounding tags can be ignored right now. The config runtimeOptions.import.xlf.ignoreFramingTags has therefore been changed and has now 3 config values: disabled, paired, all. Where paired ignores only tag pairs at the start and end of a segment, and all ignores all tags before and after plain text. Tags inside of text (and their paired partners) remain always in the segment. The new default is to ignore all tags, not only the paired ones.', '15'),
('2022-03-17', 'TRANSLATE-2891', 'feature', 'TermPortal - Choose in TermTranslation Workflow, if definitions are translated', 'It\'s now possible to choose whether definition-attributes should be exported while exporting terms from TermPortal to main Translate5 app', '15'),
('2022-03-17', 'TRANSLATE-2899', 'change', 'VisualReview / VisualTranslation - Base Work for Visual API tests', 'Added capabilities for generating API -tests for the Visual', '15'),
('2022-03-17', 'TRANSLATE-2897', 'change', 'Import/Export - Make XML Parser more standard conform', 'The internal used XML parser was not completly standard conform regarding the naming of tags.', '15'),
('2022-03-17', 'TRANSLATE-2906', 'bugfix', 'TBX-Import - Improve slow TBX import of huge TBX files', 'Due a improvement in TBX term ID handling, the import performance for bigger TBX files was reduced. This is repaired now.', '15'),
('2022-03-17', 'TRANSLATE-2900', 'bugfix', 'OpenId Connect - Auto-set roles for sso authentications', 'Auto set roles is respected in SSO created users.', '15'),
('2022-03-17', 'TRANSLATE-2898', 'bugfix', 'Editor general - Disable project deletion while task is importing', 'Now project can not be deleted while there is a running project-task import.', '15'),
('2022-03-17', 'TRANSLATE-2896', 'bugfix', 'Editor general - Remove null safe operator from js code', 'Javascript code improvement.', '15'),
('2022-03-17', 'TRANSLATE-2883', 'bugfix', 'VisualReview / VisualTranslation - Enable visual with source website, html, xml/xslt and images to provide more than 19 pages', 'FIX: The Pager for the visual now shows reviews with more than 9 pages properly.', '15'),
('2022-03-17', 'TRANSLATE-2868', 'bugfix', 'Editor general - Jump to segment on task open: priority change', 'URL links to segments work now. The segment id from the URL hash gets prioritized over the last edited segment id.', '15'),
('2022-03-17', 'TRANSLATE-2859', 'bugfix', 'TermPortal - Change logic, who can edit and delete attributes', 'The rights who can delete terms are finer granulated right now.', '15'),
('2022-03-17', 'TRANSLATE-2849', 'bugfix', 'Import/Export - Disable Filename-Matching for 1:1 Files, it is possible to upload matching-faults', 'File-name matching in visual for single project tasks is disabled and additional import project wizard improvements.', '15'),
('2022-03-17', 'TRANSLATE-2345', 'bugfix', 'Editor general, TrackChanges - Cursor jumps to start of segment, when user enters space and stops typing for a while', 'FIX: Cursor Jumps when inserting Whitespace, SpellChecking and in various other situations', '15');