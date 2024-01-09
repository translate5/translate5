
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
            
INSERT INTO `LEK_change_log` (`dateOfChange`, `jiraNumber`, `type`, `title`, `description`, `userGroup`) VALUES ('2023-12-19', 'TRANSLATE-3436', 'feature', 'LanguageResources - Integrate GPT-4 with translate5 as translation engine', 'New Private Plugin "OpenAI" to use OpenAI-Models as language-resource and base functionality to fine-tune these models', '15'),
('2023-12-19', 'TRANSLATE-3627', 'bugfix', 'Main back-end mechanisms (Worker, Logging, etc.) - HOTFIX: Progress reporting of Looped Segment Processing Workers does not work', 'FIX: progress of termtagger and spellcheck workers was not properly reported to GUI', '15'),
('2023-12-19', 'TRANSLATE-3624', 'bugfix', 'InstantTranslate - Instant Translate will find no en-us terms', 'Fix: list all regional language results from term collections when searching with the main language code ', '15'),
('2023-12-19', 'TRANSLATE-3590', 'bugfix', 'Main back-end mechanisms (Worker, Logging, etc.) - Create Globally usable API-request to replace usage of InstantTranslate in various places', 'Code cleanup: Centralize API-request from InsrtantTranslate as base-code', '15');