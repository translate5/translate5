
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
--              http://www.gnu.org/licenses/agpl.html http://www.translate5.net/plugin-exception.txt
--
-- END LICENSE AND COPYRIGHT
-- */

-- userGroup calculation: basic: 1; editor: 2; pm: 4; admin: 8
            
INSERT INTO `LEK_change_log` (`dateOfChange`, `jiraNumber`, `type`, `title`, `description`, `userGroup`) VALUES ('2025-09-03', 'TRANSLATE-4829', 'feature', 'AI - Max number of segments in request to LLM', '[🆕 Feature] AI-Plugin: 
* updated model-list & model capabilities to match OpenAI latest changes
* improved token-limit calculations
* added model configuration to customize batch-size for pretranslation and disable batching all together', '15'),
('2025-09-03', 'TRANSLATE-4815', 'feature', 'AI - MS Azure AI Foundry integeration: Allow passing of model deployment name on language resource creation', '[🆕 Feature]  Added capabilities to use Azure cloud as LLM without the necessary credentials to manage/deploy models. In this case, the models need to be deployed by the user', '15'),
('2025-09-03', 'TRANSLATE-4903', 'change', 'Configuration - Update phpstan to newer version', '[🛠️ Improvement] Updated software dependency', '15'),
('2025-09-03', 'TRANSLATE-4889', 'change', 't5memory - Document http://{{socket}}/t5memory/{{mem_name}}/addtotable end point', '[🛠️ Improvement] Update t5memory documentation.', '15'),
('2025-09-03', 'TRANSLATE-4438', 'change', 'AI - Integrate Llama 3 as language resource analogous to GPT', '[🆕 Feature]  Added Meta Llama as available integration into Translate5 AI plugin', '15'),
('2025-09-03', 'TRANSLATE-4924', 'bugfix', 'TermTagger integration - TermTagging: Editing Segments should not run into an Exception when all TermCollections have been removed', '[🐞 Fix] Do not throw Exception when all TermCollections were Removed and thus the task-TBX is not valid anymore when editing a segment.', '15'),
('2025-09-03', 'TRANSLATE-4899', 'bugfix', 'Import/Export - Repetition hash calculated incorrectly', '[🐞 Fix] Changed logic to calculate segment hashed. Will affect newly imported tasks only.', '15'),
('2025-09-03', 'TRANSLATE-4879', 'bugfix', 'LanguageResources, t5memory - Reimport fails in case after task finish language resource is unassigned', '[🐞 Fix] Changed reimport to work even in case when language resource is unassigned', '15'),
('2025-09-03', 'TRANSLATE-4856', 'bugfix', 'Auto-QA - AutoQA: Unchanged Fuzzy Match check also lists not-changed MT pre-translations', 'translate5 - 7.26.4: [🐞 Fix] MT-translations are not counted as Unedited fuzzy matches anymore
translate5 - 7.29.0: [🐞 Fix] Additional improvements and fixes.', '15');