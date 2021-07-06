
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
            
INSERT INTO `LEK_change_log` (`dateOfChange`, `jiraNumber`, `type`, `title`, `description`, `userGroup`) VALUES ('2021-07-06', 'TRANSLATE-2081', 'feature', 'Preset of user to task assignments', 'Provides the functionality to configure auto-assignment of users to tasks on client configuration level, filtered by language, setting the to be used user and workflow step.', '15'),
('2021-07-06', 'TRANSLATE-2545', 'change', 'Flexibilize workflow by putting role and step definitions in database', 'The definition of all available workflow steps and roles is now stored in the database instead in a fixed workflow class. A new complex workflow is added for demonstration purposes and usage if wanted.', '15'),
('2021-07-06', 'TRANSLATE-2516', 'change', 'Add user column to Excel language resource usage log', 'The spreadsheet with the usage log of language resources is extended with a user column, that shows, who actually did the request.', '15'),
('2021-07-06', 'TRANSLATE-2563', 'bugfix', 'Adjust texts that connect analysis and locking of 100%-Matches', 'Adjust texts that connect analysis and locking of 100%-Matches.', '15'),
('2021-07-06', 'TRANSLATE-2560', 'bugfix', 'Combination of term-tagging and enabled source editing duplicates tags on saving a segment, AutoQA removes/merges TrackChanges from different Users', 'FIXED BUG in the TermTagger leading to duplication of internal tags when source editing was activated
FIXED BUG in the AutoQA leading to TrackChanges tags from different users being merged', '15'),
('2021-07-06', 'TRANSLATE-2557', 'bugfix', 'Select correct okapi file filter for txt-files by default', 'By default the file format conversion used for txt-files the okapi-filter "moses-text". In this filter xml-special characters like & < > where kept in encoded version when the file was reconverted back to txt after export from translate5. This was wrong. Now the default was changed to the okapi plain-text filter, what handles the xml-special chars correctly.', '15'),
('2021-07-06', 'TRANSLATE-2547', 'bugfix', 'Clean-up project tasks', 'Deleting a project deletes all files from database but not from disk. This is fixed.', '15'),
('2021-07-06', 'TRANSLATE-2536', 'bugfix', 'Task Configuration Panel does show old Values after Import', 'FIX: Task Qualities & Task Configuration panels now update their view automatically after import to avoid outdated date is being shown', '15'),
('2021-07-06', 'TRANSLATE-2533', 'bugfix', 'Line breaks in InstantTranslate are deleted', 'InstantTranslate dealing of line breaks is fixed.', '15');