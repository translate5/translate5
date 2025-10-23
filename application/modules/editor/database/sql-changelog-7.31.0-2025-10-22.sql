
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
            
INSERT INTO `LEK_change_log` (`dateOfChange`, `jiraNumber`, `type`, `title`, `description`, `userGroup`) VALUES ('2025-10-22', 'TRANSLATE-4374', 'feature', 'AI - RAG-based prompting for LLMs (GPT, OpenAI, Azure AI Foundry, Llama, ect.)', 'OpenAI Plugin: 
* add RAG-based prompting for LLM based language-resources
* Improve Icons & prompt layouts
* add option to use xliff-format for single text translations (InstantTranslate)', '15'),
('2025-10-22', 'TRANSLATE-3694', 'feature', 'usability editor - Finalize status of all segments in the current filtering', 'Added ability to change status of all segments in the current filtering', '15'),
('2025-10-22', 'TRANSLATE-5042', 'change', 't5memory - Redo TMX cut off functionality', 'Improve RAM usage on TMX import process', '15');