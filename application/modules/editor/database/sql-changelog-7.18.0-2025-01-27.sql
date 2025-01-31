
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
            
INSERT INTO `LEK_change_log` (`dateOfChange`, `jiraNumber`, `type`, `title`, `description`, `userGroup`) VALUES ('2025-01-27', 'TRANSLATE-4404', 'feature', 'Task Management - translate5 Plunet Connector', 'Integrates translate5 with Plunet for project creation, user assignments to tasks, job status, workflow and transfer of files back and forth.', '15'),
('2025-01-27', 'TRANSLATE-4321', 'feature', 'openai - Use GPT in Azure Cloud with OpenAI Plug-in', 'OpenAI plugin now supports Azure OpenAI cloud', '15'),
('2025-01-27', 'TRANSLATE-4375', 'change', 'Workflows - Remove changes.xlf creation', 'h1. problem

The feature of creating changes.xlf is outdated in code (producing errors sometime, especially the differ). Also the feature is disabled by default.
h1. solution

The feature will be removed. Removing means: 
 * removing the configs related to that
 * removing the Action / Notification code creating the changes.xlf with Xliff1.2
 * Keep the Xliff2 stuff if TrackChanges based differ is used
 * Keeping the XlfConverter and the CLI itself
 * check usage of the related differs, may be removed too
 * SUMMARY TO ANSWER missing questions above: goal is to remove the non TrackChanges based differs used for above file creation. Changes.xlf in Notifications is probably nowhere used anymore', '15'),
('2025-01-27', 'TRANSLATE-4077', 'change', 'usability language resources, usability task overview - Possibilty to save filter sets', 'Added ability to save filters sets for further reuse', '15'),
('2025-01-27', 'TRANSLATE-4409', 'bugfix', 't5memory - t5memory: On TMX import process if error happens code reacts incorrectly', 't5memory: fix import error handling', '15'),
('2025-01-27', 'TRANSLATE-4324', 'bugfix', 'Import/Export - Self closing mrk in sdlxliff gets always same tag number', 'Fix for a problem where tag check will complain about duplicate tags in segment with multiple self closing mrks.', '15');