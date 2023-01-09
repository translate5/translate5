
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
            
INSERT INTO `LEK_change_log` (`dateOfChange`, `jiraNumber`, `type`, `title`, `description`, `userGroup`) VALUES ('2023-01-09', 'TRANSLATE-3157', 'change', 'SpellCheck (LanguageTool integration) - Summon new SNC-error with the one SpellCheck is already counting', 'SNC-error beginnig with  "Dubiose \'Zahl\' ..." renamed to "Dubiose Zahl" for being counted as already known to Translate5', '15'),
('2023-01-09', 'TRANSLATE-3146', 'bugfix', 'TermPortal - Attribute tooltip has annoying latency', 'Tooltips do now have no before-show delay', '15'),
('2023-01-09', 'TRANSLATE-3095', 'bugfix', 'TermPortal - Not all available TermCollections visible in drop-down menu', 'Filter window\'s TermCollection dropdown problem not appearing anymore', '15');