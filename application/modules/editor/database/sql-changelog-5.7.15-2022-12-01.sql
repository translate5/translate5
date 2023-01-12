
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
            
INSERT INTO `LEK_change_log` (`dateOfChange`, `jiraNumber`, `type`, `title`, `description`, `userGroup`) VALUES ('2022-12-01', 'TRANSLATE-3122', 'bugfix', 'TermTagger integration - Termportlet in segment-meta-panel mixes all language level attributes up', 'The Termportlet in the segment-meta-panel was loading to much data and was mixing up attributes on language level.', '15'),
('2022-12-01', 'TRANSLATE-3120', 'bugfix', 'Editor general - Workfiles not listed in editor', 'Fixes problem where the work-files where not listed in editor', '15'),
('2022-12-01', 'TRANSLATE-3119', 'bugfix', 'TermPortal - TermPortal: error popping once attribute disabled', 'Fixed error popping on attempt to remove usages of disabled attributes from filter window in case if no filter window exists as no search yet done', '15'),
('2022-12-01', 'TRANSLATE-3116', 'bugfix', 'SpellCheck (LanguageTool integration) - Editor: spellcheck styling breaks custom tags markup', 'Fixed spellcheck styles breaking custom tags markup', '15'),
('2022-12-01', 'TRANSLATE-3115', 'bugfix', 'Import/Export - proofread deprecation message was not shown on a task', 'The warning that the foldername proofRead is deprecated and should not be used anymore was not logged to a task but only into the system log therefore the PMs did not notice that message.', '15'),
('2022-12-01', 'TRANSLATE-3113', 'bugfix', 'Editor general - Adding MQM tags is not always working', 'Fixed adding MQM tags to the latest selected word in the segment editor', '15'),
('2022-12-01', 'TRANSLATE-3112', 'bugfix', 'Editor general - MQM severity is not working properly', 'Fix MQM tag severity in tooltip in segments grid', '15'),
('2022-12-01', 'TRANSLATE-3111', 'bugfix', 'Editor general - Editor: matchrate filter search problem', 'Fixed problem that segment filter was not applied if a range was set too quickly on a MatchRate-column\'s filter.', '15'),
('2022-12-01', 'TRANSLATE-3110', 'bugfix', 'TermPortal - TermPortal: batch-editing should be available for termPM* roles only', 'BatchEdit-button is now shown for \'TermPM\' and \'TermPM (all clients)\' user roles only', '15');