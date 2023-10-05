
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
            
INSERT INTO `LEK_change_log` (`dateOfChange`, `jiraNumber`, `type`, `title`, `description`, `userGroup`) VALUES ('2023-09-22', 'TRANSLATE-3489', 'feature', 'Okapi integration - Enhance figma default xml conversion settings', 'ENHANCEMENT: Fix FIGMA file-format settings regarding whitespace', '15'),
('2023-09-22', 'TRANSLATE-3474', 'change', 'TermPortal - Show explaining text, when no filters are set.', 'Added explaining text for active filters field when none are in use', '15'),
('2023-09-22', 'TRANSLATE-3473', 'change', 'TermPortal - Make info about TermCollection and Client bold in Termportal middle column', 'Font for Client and TermCollection names in Siblings-panel is now bolder and bigger', '15'),
('2023-09-22', 'TRANSLATE-3472', 'change', 'Editor general - Show match resource name in match panel of translate5 editor', 'LanguageResource name is now shown in addition to match rate value in \'Match Rate\' column within Match panel', '15'),
('2023-09-22', 'TRANSLATE-3241', 'change', 'OpenTM2 integration - T5memory automatic reorganize and via CLI', 'translate - 5.9.4
Added two new commands: 
  - t5memory:reorganize for manually triggering translation memory reorganizing
  - t5memory:list - for listing all translation memories with their statuses
Add new config for setting up error codes from t5memory that should trigger automatic reorganizing
Added automatic translation memory reorganizing if appropriate error appears in response from t5memory engine

translate - 6.2.0
 -  Fix the status check for GroupShare language resources

translate - 6.5.3
-   CLI improvement', '15'),
('2023-09-22', 'TRANSLATE-3495', 'bugfix', 'Editor general - FIX whitespace tag-check to cope with frontend does not correctly number whitespace-tags', 'FIX: Numbering of whitespace-tags may be faulty due to frontend-errors leading to incorrect  tag-errors', '15'),
('2023-09-22', 'TRANSLATE-3490', 'bugfix', 'Auto-QA - AutoQA: Internal Tag-Check does not detect tags with incorrect order on the same index', 'FIX: AutoQA did not detect overlapping/interleaving tags when they are on the same index', '15'),
('2023-09-22', 'TRANSLATE-3487', 'bugfix', 'Editor general - Taking over fuzzy matches in the UI may lead to corrupted internal tags', 'Taking over fuzzy matches in the UI was producing corrupted internal tags. In the Editor the tags were looking correctly, but on database level they did contain the wrong content. ', '15'),
('2023-09-22', 'TRANSLATE-3484', 'bugfix', 'API - Low full task listing on large instances', 'The full listing of tasks via API is reduced to the task data, no additional sub data like quality stats etc per task is added any more to improve loading speed of such a request. ', '15'),
('2023-09-22', 'TRANSLATE-3478', 'bugfix', 'OpenId Connect - Open task for editing with SSO enabled', 'Fix: start task editing from a link with SSO authentication does not work', '15'),
('2023-09-22', 'TRANSLATE-3461', 'bugfix', 'Authentication, Editor general - Use http header fields only lowercase', 'FIX: evaluation of sent request headers is case-insensitive now', '15'),
('2023-09-22', 'TRANSLATE-3454', 'bugfix', 't5memory - Analysis runs through although t5memories are in state of reorganisation', 'All connection errors are logged now while match analysis, if match analysis is incomplete due such problems an error message is shown on the analysis page.', '15'),
('2023-09-22', 'TRANSLATE-3444', 'bugfix', 'Main back-end mechanisms (Worker, Logging, etc.) - ERROR in core: E9999 - Cannot refresh row as parent is missing', 'Fix for back-end error when authentication an user.', '15'),
('2023-09-22', 'TRANSLATE-3292', 'bugfix', 'Editor general - List all homonyms in right-side TermPortlet of the editor', 'Now the other homonyms are shown in the right-side Terminology-panel as well', '15');