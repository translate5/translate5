
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
            
INSERT INTO `LEK_change_log` (`dateOfChange`, `jiraNumber`, `type`, `title`, `description`, `userGroup`) VALUES ('2026-03-05', 'TRANSLATE-4898', 'feature', 't5memory - Add retry if t5memory rejects request due to restart', 'Added proper handling of the error if t5memory rejects request due to scheduled restart', '15'),
('2026-03-05', 'TRANSLATE-5267', 'change', 'Editor general - Add highlighting a comment by click on segment in the grid', '[:gear:  Improvement] Comment in the left "Comments" panel is now highlighted when clicked on the segment ', '15'),
('2026-03-05', 'TRANSLATE-5090', 'change', 't5memory - Introduce settings for TMX filter and tmx-utils', 'Introduce settings for TMX filter and tmx-utils', '15'),
('2026-03-05', 'TRANSLATE-5088', 'change', 't5memory - Improve segment filtering on TMX import', 'Improve segment filtering on TMX import', '15'),
('2026-03-05', 'TRANSLATE-5087', 'change', 't5memory - Check for duplicates on segment updates', 'Check for duplicates on segment updates', '15'),
('2026-03-05', 'TRANSLATE-5073', 'change', 't5memory - Improve fuzzy and concordance search results providing', 'Improve fuzzy and concordance search results providing', '15'),
('2026-03-05', 'TRANSLATE-5050', 'change', 't5memory - Redo TMX export in parallel', 'TMX will exported in parallel parts form t5memory', '15'),
('2026-03-05', 'TRANSLATE-4845', 'change', 'Content Protection - Make t5n tags smaller to save segment space on t5memory side', 't5n tags on t5memory side will become smaller so that longer segments may be saved into memory', '15'),
('2026-03-05', 'TRANSLATE-3764', 'change', 'InstantTranslate - make runtimeOptions.InstantTranslate.user.defaultLanguages possible in UI', 'translate5 - 7.2.0: Default selected languages for instant translate are configurable.
translate5 - 7.35.3: Fix availability of new config for new installations too (was working only for udpated installations)', '15'),
('2026-03-05', 'TRANSLATE-5340', 'bugfix', 'Content Protection - Key is not generated for new Content protection rules', 'Fix key generation of Content protection rules', '15'),
('2026-03-05', 'TRANSLATE-5339', 'bugfix', 'Translate5 CLI - Warn on CLI execution as root to prevent permission problems', 'In order to prevent permission problems in the file system the CLI tools are printing a confirm warning when running as root.', '15'),
('2026-03-05', 'TRANSLATE-5335', 'bugfix', 'Repetition editor - Master segment saved twice on repetition editor confirm', 'Fix events on repetition editor actions', '15'),
('2026-03-05', 'TRANSLATE-5310', 'bugfix', 'Content Protection, MatchAnalysis & Pretranslation - blocked segments are processed with Content Protection', 'Do not process blocked segments are processed with Content Protection', '15'),
('2026-03-05', 'TRANSLATE-5293', 'bugfix', 'Main back-end mechanisms (Worker, Logging, etc.) - Task clone leads to an error', 'Fix for error when clone task produces errors in rare cases.', '15'),
('2026-03-05', 'TRANSLATE-5244', 'bugfix', 'Package Ex and Re-Import - Reimport of Translator Package leads to invalid Markup', 'FIX: translator package reimport may lead to exceptions due to tag-errors in diffing to evaluate track-changes', '15'),
('2026-03-05', 'TRANSLATE-5216', 'bugfix', 'translate5 AI - Check why gpt 5.1 and 5.2 work only very inconsistently in MS Azure AI foundry and not at all at openai', '[🐞 Fix]  Added support of GPT-5 models', '15');