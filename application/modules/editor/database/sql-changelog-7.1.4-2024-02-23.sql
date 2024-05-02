
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
            
INSERT INTO `LEK_change_log` (`dateOfChange`, `jiraNumber`, `type`, `title`, `description`, `userGroup`) VALUES ('2024-02-23', 'TRANSLATE-3750', 'bugfix', 't5memory - Fix deletion of TMs on fuzzy TM errors', 'In very rare cases TMs in t5memory get deleted.', '15'),
('2024-02-23', 'TRANSLATE-3747', 'bugfix', 'Import/Export - Extend Placeables to also inspect contents of <ph> & <it> tags', 'Improve Placeables: scan the contents of <ph> and <it> tags instead of the tags', '15'),
('2024-02-23', 'TRANSLATE-3743', 'bugfix', 'LanguageResources - Changes in the OpenAI API lead to errors when training a model', 'Updating OpenAI lib', '15'),
('2024-02-23', 'TRANSLATE-3742', 'bugfix', 't5memory - Fix resetting reorganize attempts', 'Fix error while saving segment to t5memory', '15'),
('2024-02-23', 'TRANSLATE-3737', 'bugfix', 'SpellCheck (LanguageTool integration) - Warning instead of error when the target language is not supported by the spell checker', 'Warning instead of error when the target language is not supported by the spell checker.', '15'),
('2024-02-23', 'TRANSLATE-3736', 'bugfix', 'Editor general - RootCause error: tagData is undefined', 'Fix for a problem when displaying tag errors popup.', '15'),
('2024-02-23', 'TRANSLATE-3734', 'bugfix', 'Editor general - Reconnect and closed websocket connections', 'Fix for message bus reconnecting when connection is lost.', '15'),
('2024-02-23', 'TRANSLATE-3732', 'bugfix', 'MatchAnalysis & Pretranslation - RootCause: Cannot read properties of null (reading \'getMetadata\')', 'Fix for UI error when analysis load returns not results', '15'),
('2024-02-23', 'TRANSLATE-3730', 'bugfix', 'Import/Export - across hotfolder bug fixing', 'Several smaller fixes in instruction.xml evaluation regarding the PM to be used.', '15'),
('2024-02-23', 'TRANSLATE-3716', 'bugfix', 'Import/Export - Change default for runtimeOptions.import.xlf.ignoreFramingTags to "paired"', 'It often leads to problems for users, who do not know translate5 well enough, that the default setting for runtimeOptions.import.xlf.ignoreFramingTags is "all".
Because in some import formats there are stand-alone tags, that stand for words, and with "all" they would be excluded from the segment and miss as info for the translator and can not be moved with the text inside the segment.
Therefore the default is changed to runtimeOptions.import.xlf.ignoreFramingTags = "paired"', '15'),
('2024-02-23', 'TRANSLATE-3690', 'bugfix', 'Workflows - workflow starts with "view only"', 'Fix for a problem where the initial task workflow step is set to a wrong value when we have default assigned user with workflow role "view only".', '15'),
('2024-02-23', 'TRANSLATE-3679', 'bugfix', 'LanguageResources, Task Management - deselecting language resources in task creation wizard not saved', 'Fix for a problem where the resources association grid was not updated after task creating in the project overview.', '15'),
('2024-02-23', 'TRANSLATE-3591', 'bugfix', 'Editor general - Only query MT in fuzzy panel of editor, if segment untranslated', 'So far with each opening of a segment, all match resources are queried.

In the future this should only happen for MT resources, if the segment is in the segment status "untranslated".

The old behavior can be turned on again by a new config options, overwritable on client, import and task level. It\'s name needs to be specified in the important release notes of this issue.', '15');