
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
            
INSERT INTO `LEK_change_log` (`dateOfChange`, `jiraNumber`, `type`, `title`, `description`, `userGroup`) VALUES ('2023-04-03', 'TRANSLATE-3249', 'change', 'LanguageResources - Add documentation about t5memory status request processing in t5', 'Response from t5memory for the `status` API call was changed so t5memory connector has been modified to parse the status of the translation memory accordingly.
The documentation about the t5memory status processing is also added and can be found here:
https://confluence.translate5.net/display/TAD/Status+response+parsing
', '15'),
('2023-04-03', 'TRANSLATE-3241', 'change', 'OpenTM2 integration - T5memory automatic reorganize and via CLI', 'Added two new commands: 
  - t5memory:reorganize for manually triggering translation memory reorganizing
  - t5memory:list - for listing all translation memories with their statuses
Add new config for setting up error codes from t5memory that should trigger automatic reorganizing
Added automatic translation memory reorganizing if appropriate error appears in response from t5memory engine
', '15'),
('2023-04-03', 'TRANSLATE-3260', 'bugfix', 'TrackChanges - Disable TrackChanges for ja, ko, zh, vi completely to fix char input problems', 'Added option to completely disable TrackChanges per language (\'ko\', \'ja\', ...) to solve problems with character input in these languages', '15'),
('2023-04-03', 'TRANSLATE-3257', 'bugfix', 'GroupShare integration - Segments are not saved back to GS', 'Segments could not be not saved back to GroupShare. Passing the optional configuration confirmationLevels: [\'Unspecified\'] did solve the problem.', '15'),
('2023-04-03', 'TRANSLATE-3255', 'bugfix', 'GroupShare integration - Fix segment updating also if segment has not tags in source but in target', 'If the target text has tags but source not, the segment could not be saved to groupshare TM.', '15'),
('2023-04-03', 'TRANSLATE-3231', 'bugfix', 'Export - No download progress is shown for translator packages', 'Waiting screen will be shown on package export.
Fix for package export and re-import API responses.', '15'),
('2023-04-03', 'TRANSLATE-3111', 'bugfix', 'Editor general - Editor: matchrate filter search problem', 'Fixed problem that segment filter was not applied if a range was set too quickly on a MatchRate-column\'s filter.
Fix error produced by the filters when leaving the task.', '15');