
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
            
INSERT INTO `LEK_change_log` (`dateOfChange`, `jiraNumber`, `type`, `title`, `description`, `userGroup`) VALUES ('2022-04-07', 'TRANSLATE-2942', 'feature', 'Repetition editor - Make repetitions more restrict by including segment meta fields into repetition calculation', 'Make repetition calculation more restrict by including segment meta fields (like maxLength) into repetition calculation. Can be defined by new configuration runtimeOptions.alike.segmentMetaFields.', '15'),
('2022-04-07', 'TRANSLATE-2842', 'feature', 'Workflows - new configuration to disable workflow mails', 'Workflow mails can be disabled via configuration.', '15'),
('2022-04-07', 'TRANSLATE-2386', 'feature', 'Configuration, Editor general - Add language specific special characters in database configuration for usage in editor', 'The current bar in the editor that enables adding special characters (currently non-breaking space, carriage return and tab) can be extended by characters, that can be defined in the configuration.
Example of the config layout can be found here:
https://confluence.translate5.net/display/BUS/Special+characters', '15'),
('2022-04-07', 'TRANSLATE-2946', 'bugfix', 'Editor general, Editor Length Check - Multiple problems on automatic adding of newlines in to long segments', 'Multiple Problems fixed: add newline or tab when with selected text in editor lead to an error. Multiple newlines were added in some circumstances in multiline segments with to long content. Optionally overwrite the trailing whitespace when newlines are added automatically.', '15'),
('2022-04-07', 'TRANSLATE-2943', 'bugfix', 'MatchAnalysis & Pretranslation - No analysis is shown if all segments were pre-translated and locked for editing', 'No analysis was shown if all segments were locked for editing due successful pre-translation although the analysis was run. Now an empty result is shown.', '15'),
('2022-04-07', 'TRANSLATE-2941', 'bugfix', 'Editor general - Ignore case for imported files extensions', 'The extension validator in the import wizard will no longer be case sensitive.', '15'),
('2022-04-07', 'TRANSLATE-2940', 'bugfix', 'Main back-end mechanisms (Worker, Logging, etc.) - Login redirect routes', 'Instant-translate / Term-portal routes will be evaluated correctly on login.', '15'),
('2022-04-07', 'TRANSLATE-2939', 'bugfix', 'TermTagger integration - Fix language matching on term tagging', 'The language matching between a task and terminology was not correct. Now terms in a major language (de) are also used in tasks with a sub language (de-DE)', '15'),
('2022-04-07', 'TRANSLATE-2914', 'bugfix', 'I10N - Missing localization for Chinese', 'Added missing translations for Chinese languages in the language drop downs.', '15');