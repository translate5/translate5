
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
            
INSERT INTO `LEK_change_log` (`dateOfChange`, `jiraNumber`, `type`, `title`, `description`, `userGroup`) VALUES ('2024-02-02', 'TRANSLATE-3654', 'change', 't5memory - Improve t5memory status response handling', 'Improve t5memory status response handling', '15'),
('2024-02-02', 'TRANSLATE-3586', 'change', 'Editor general - Always show info icon in match rate panel', 'Info icon in the first column of a match-rate panel - is now always shown', '15'),
('2024-02-02', 'TRANSLATE-3686', 'bugfix', 'Editor general - RootCause: Cannot read properties of null (reading \'forEach\')', 'Fix for UI when loading qualities.', '15'),
('2024-02-02', 'TRANSLATE-3685', 'bugfix', 'Auto-QA, Editor general - RootCause error: Cannot read properties of undefined (reading \'floating\')', 'Fix for UI error when saving false positive with slow requests.', '15'),
('2024-02-02', 'TRANSLATE-3684', 'bugfix', 'Editor general - RootCause error: resourceType is null', 'Fix for UI error when creating Language resources and selecting resource from the dropdown.', '15'),
('2024-02-02', 'TRANSLATE-3682', 'bugfix', 'Editor general - RootCause error: Cannot read properties of null (reading \'get\')', 'Fix for problem when selecting customer in task add wizard', '15'),
('2024-02-02', 'TRANSLATE-3681', 'bugfix', 'Editor general - RootCause: Cannot read properties of null (reading \'getHtml\')', 'Fix for UI error when filtering for qualities by clicking on the three. ', '15'),
('2024-02-02', 'TRANSLATE-3680', 'bugfix', 'Client management - Action column in clients grid not resizeable', 'Fix for clients action column not resizable.', '15'),
('2024-02-02', 'TRANSLATE-3678', 'bugfix', 'GroupShare integration, InstantTranslate - InstantTranslate does not use Groupshare TMs', 'Fix for a problem where group share results where not listed in instant translate.', '15'),
('2024-02-02', 'TRANSLATE-3677', 'bugfix', 'Editor general - RootCause error: this.getMarkupImage is not a function', 'Fix for UI error when changing editor view modes.', '15'),
('2024-02-02', 'TRANSLATE-3674', 'bugfix', 'Editor general - RootCause error: Cannot read properties of null (reading \'dom\')', 'Fix for UI error when displaying tooltip in editor.', '15'),
('2024-02-02', 'TRANSLATE-3673', 'bugfix', 'Editor general - FIX "Cannot read properties of undefined" from markup-decoration lib / Placeables', 'FIX potential JavaScript Error when decorating segments for SpellCheck', '15'),
('2024-02-02', 'TRANSLATE-3671', 'bugfix', 'Client management - Dropdown "Client" does not work anymore after TRANSLATE-2276', 'Fix for global customer filter not working for tasks and resources.', '15'),
('2024-02-02', 'TRANSLATE-3651', 'bugfix', 'MatchAnalysis & Pretranslation - Some segments are not pre-translated, although 100% matches exist in the TM', 'Fix pretranslation for repetitions', '15'),
('2024-02-02', 'TRANSLATE-3642', 'bugfix', 'Auto-QA, Editor general - change default for tag check reference field to source', 'Changed default value for useSourceForReference config to \'Activated\'', '15'),
('2024-02-02', 'TRANSLATE-3641', 'bugfix', 'Repetition editor - Repetitions editor: Activate/deactivate target repetitions', 'Added ability to define whether target-only repetitions should be excluded from the default pre-selection in repetition editor', '15'),
('2024-02-02', 'TRANSLATE-3623', 'bugfix', 'TermPortal - batch edit in term collection will lead to error value in termID is invalid', 'Fix for a problem when batch editing in term portal.', '15'),
('2024-02-02', 'TRANSLATE-3217', 'bugfix', 'Editor general - RootCause: Invalid JSON - answer seems not to be from translate5 - x-translate5-version header is missing', '5.9.0: added some debug code.
7.1.1: additional debugging code', '15');