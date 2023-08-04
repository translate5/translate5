
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
            
INSERT INTO `LEK_change_log` (`dateOfChange`, `jiraNumber`, `type`, `title`, `description`, `userGroup`) VALUES ('2023-07-28', 'TRANSLATE-3207', 'feature', 'LanguageResources, TermPortal - Extend TBX import with images from zip folder', 'Added support for terminology images to be imported/exported in zip-archive', '15'),
('2023-07-28', 'TRANSLATE-3164', 'feature', 'VisualReview / VisualTranslation - Pivot language preview in the visual layout', 'Plugin Visual: In the (left) source layout for tasks with available pivot now a toggle button exists, that will switch between source and pivot language. Please note, that the pivot-view will use the reflown Wysiwyg-layout with all known limitations.', '15'),
('2023-07-28', 'TRANSLATE-3031', 'feature', 'Client management, LanguageResources, Task Management, User Management - Multitenancy of management interfaces', 'Add role "PM selected clients" which enables to create PMs which are restricted to certain clients (multitenancy)', '15'),
('2023-07-28', 'TRANSLATE-3432', 'change', 'Main back-end mechanisms (Worker, Logging, etc.) - Logger to catch more info about no access exception', 'Added special logging improvement for certain backend error.', '15'),
('2023-07-28', 'TRANSLATE-3398', 'change', 'Workflows - Extend translate5 mail on task status change', 'ENHANCEMENT: It can be configured if the changed segments email contains the commented segments and if the "Target text(at time of import)" is shown.', '15'),
('2023-07-28', 'TRANSLATE-3396', 'change', 'VisualReview / VisualTranslation - Visual WYSIWYG: Pages of the visual remain untranslated when database is very slow', 'FIX: When the database is very slow, the visual Wysiwyg may remain untranslated in sections', '15'),
('2023-07-28', 'TRANSLATE-3378', 'change', 'Test framework - Add tests for TildeMT plugin', 'Added API test for TildeMT plugin using a fake-API', '15'),
('2023-07-28', 'TRANSLATE-3109', 'change', 'User Management - UI for appTokens', 'For API users: Implemented a administration for application auth tokens in the UI. Improved according CLI commands to list, delete app tokens and set expires date with CLI.
', '15'),
('2023-07-28', 'TRANSLATE-3439', 'bugfix', 'VisualReview / VisualTranslation - Visual: FIX Image-Import for monochrome or transparent images', 'FIX: When images as review-source in the Visual are completely transparent or  monochrome the color-processing fails with an unhandled exception', '15'),
('2023-07-28', 'TRANSLATE-3438', 'bugfix', 'TermPortal, TermTagger integration - TermPortal: Custom attribute names are not reflected in translate5s editor', 'FIXED: Term-portlet attributes labels problem ', '15'),
('2023-07-28', 'TRANSLATE-3437', 'bugfix', 'Editor general - No way to save segment with MQM tags', 'Fixed bug which caused an error on saving segment with MQM tag', '15'),
('2023-07-28', 'TRANSLATE-3435', 'bugfix', 'Authentication - Sessions are not cleaned up in DB, Logins frequently fail (mayby due to faulty db-session-data)', 'FIX: expired sessions were not cleaned anymore leading to potential problems with the login. Other quirks also could lead to multiple entries for the unique-id', '15'),
('2023-07-28', 'TRANSLATE-3433', 'bugfix', 'VisualReview / VisualTranslation - Segment selection/scrolling may leads to wrong "segment not found" toasts', 'BUG: segment selection/scrolling may leads to wrong "segment not found" toasts', '15'),
('2023-07-28', 'TRANSLATE-3431', 'bugfix', 'Editor general - Pasting content when segments editor is closed', 'FIX: solve potential problem when pasting content in the segment-editor very fast', '15'),
('2023-07-28', 'TRANSLATE-3430', 'bugfix', 'MatchAnalysis & Pretranslation - Match Ranges & Pricing: changing preset should be possible for PM only', 'Pricing preset now can be changed by PM only', '15'),
('2023-07-28', 'TRANSLATE-3402', 'bugfix', 'Okapi integration - Hotfix: delete deepl glossary on deleting termcollection', 'translate - 6.4.3: When deleting a termcollection the corresponding DeepL glossary was not deleted. This is fixed now.
translate - 6.5.0: Change wrong title in change-log', '15'),
('2023-07-28', 'TRANSLATE-3304', 'bugfix', 'Package Ex and Re-Import - Improve re-import segment alignment', 'Different segment alignment will be used base on the task version. All tasks older then translate5 - 6.5.0 will use different segment alignment.', '15'),
('2023-07-28', 'TRANSLATE-3303', 'bugfix', 'Import/Export - Generated mid is not unique enough', 'The XLF re-import had problems if in a package segments had the same segment ID. Now the generation of the ID is changed to be really unique.', '15'),
('2023-07-28', 'TRANSLATE-2831', 'bugfix', 'Configuration - Repetition editor options do not appear in client overwrites', 'Fix config level of Repetition editor options', '15');