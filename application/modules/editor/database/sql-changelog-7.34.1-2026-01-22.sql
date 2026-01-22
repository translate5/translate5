
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
            
INSERT INTO `LEK_change_log` (`dateOfChange`, `jiraNumber`, `type`, `title`, `description`, `userGroup`) VALUES ('2026-01-22', 'TRANSLATE-4834', 'feature', 'translate5 AI - Translation Quality Estimation (TQE): base implementation', '7.34.1: Fix for installations without AI plug-in
7.34.0: Translation Quality Estimation (TQE) is now possible with translate5 AI.', '15'),
('2026-01-22', 'TRANSLATE-5182', 'bugfix', 'LanguageResources - Track changes stripped when sendWhitespaceAsTag is evaluated', 'Fix problem where track changes where stripped when runtimeOptions.LanguageResources.{resource}.sendWhitespaceAsTag is enabled', '15'),
('2026-01-22', 'TRANSLATE-4554', 'bugfix', 'LanguageResources - Foreign task TMs shown for first language only in project wizard', 'Show foreign Task TMs in wizard for all languages of project', '15');