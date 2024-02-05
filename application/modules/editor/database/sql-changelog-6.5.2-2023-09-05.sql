
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
            
INSERT INTO `LEK_change_log` (`dateOfChange`, `jiraNumber`, `type`, `title`, `description`, `userGroup`) VALUES ('2023-09-05', 'TRANSLATE-3376', 'feature', 'Editor general - Select a term anywhere and hit F3 to search the Concordance', 'Concordance search now works for source/target-columns even if editor is not opened', '15'),
('2023-09-05', 'TRANSLATE-3470', 'change', 'Test framework - Add option to skip certain tests to test:runall command', 'ENHANCEMENT: add option to skip certain tests in test-commands', '15'),
('2023-09-05', 'TRANSLATE-3467', 'change', 'Installation & Update - Implement a instance specific notification facility', 'In the optional file client-specific/instance-notes.md specific notes for updating and downtime can be noted. The file is printed on each usage of the maintenance and status command so that admin is remembered to that important notes regarding update and downtime.', '15'),
('2023-09-05', 'TRANSLATE-3468', 'bugfix', 'Main back-end mechanisms (Worker, Logging, etc.) - Fix AcrossHotfolder faulty namespaces in the SQL files', 'FIX: Wrong SQL in Across Hotfolder plugin', '15'),
('2023-09-05', 'TRANSLATE-3464', 'bugfix', 'Main back-end mechanisms (Worker, Logging, etc.) - Awkward Code in base Entity-class may leads to memory buildup', 'FIX: inappropriate code in entity-base-class may leads to high memory-consumption', '15'),
('2023-09-05', 'TRANSLATE-3457', 'bugfix', 'User Management - Client-PM / User Manegment: Roles "No rights" & "Basic" appear mistakenly in the user-form', 'FIX: roles "no rights" & "basic" mistakenly appeared in the user-editor form', '15'),
('2023-09-05', 'TRANSLATE-3451', 'bugfix', 'Client management, Workflows - Fix wrong foreign key usage and introduce simple workflow management CLI commands', 'This fix cleans inconsistent entries in the user default associations - if there are any and adds proper foreign keys. The deleted entries are listed in the system log. Please check the log and readd them if necessary. The inconsistency was due invalid workflow and workflowStep combination, which can only happen due manual changes in the DB.', '15'),
('2023-09-05', 'TRANSLATE-3450', 'bugfix', 'Import/Export - XLF x tags with non unique IDs lead to duplicated tags after import', 'XLF with duplicated x tag ids in the same segment were producing wrong tag numbers on import.', '15'),
('2023-09-05', 'TRANSLATE-3449', 'bugfix', 'Editor general - Taking matches with tags ignores the tag numbers', 'Fixed calculating tag numbers when setting TM match to segment editor', '15'),
('2023-09-05', 'TRANSLATE-3448', 'bugfix', 'Main back-end mechanisms (Worker, Logging, etc.) - Missing worker dependencies', 'FIX: Task Operations may unexpectedly terminated before doing anything since the workers have been queued in state "scheduled"
FIX: Quality Operation queued workers even if completely deactivated
CARE: checked worker dependencies, added commandline-tool to visualize the dependencies', '15'),
('2023-09-05', 'TRANSLATE-3445', 'bugfix', 'LanguageResources - Google connector: wrong language compare', 'Fix for wrong language compare in google connector.', '15'),
('2023-09-05', 'TRANSLATE-3440', 'bugfix', 'TermPortal - Misunderstandable message, if no Default-PM for term-translations is defined', 'Improved message shown if no Default-PM for term-translations is defined', '15'),
('2023-09-05', 'TRANSLATE-3236', 'bugfix', 'TermPortal - Some attribute values need to change in the term translation workflow', 'Improved import logic for \'Created/Updated At/By\' and \'Gender\' tbx attributes', '15'),
('2023-09-05', 'TRANSLATE-3138', 'bugfix', 'Client management - Set filter in project and Clients grids does not reselect the row', 'Improved auto-selection logic for Projects/Clients grids when filters are used', '15'),
('2023-09-05', 'TRANSLATE-3048', 'bugfix', 'Editor general - CSRF Protection for translate5', 'translate5 - 6.0.0
- CSRF (Cross Site Request Forgery) Protection for translate5 with a CSRF-token. Important info for translate5 API users: externally the translate5 - API can only be accessed with an App-Token from now on.
translate5 - 6.0.2
- remove CSRF protection for automated cron calls
translate5 - 6.5.2
- additional action protected', '15');