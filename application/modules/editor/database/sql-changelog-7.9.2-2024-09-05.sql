
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
            
INSERT INTO `LEK_change_log` (`dateOfChange`, `jiraNumber`, `type`, `title`, `description`, `userGroup`) VALUES ('2024-09-05', 'TRANSLATE-4168', 'change', 't5memory - Enable stripping framing tags by default', 'Config value runtimeOptions.LanguageResources.t5memory.stripFramingTagsEnabled is now 1 by default', '15'),
('2024-09-05', 'TRANSLATE-4167', 'change', 't5memory, TM Maintenance - TMMaintenance search fails for big memories', 'Fix TM Maintenance search for big memories', '15'),
('2024-09-05', 'TRANSLATE-4164', 'change', 'LanguageResources - DeepL: Improve tag-repair to handle new tag-problems in DeepL', 'FIX: DeepL at times "clusters" all sent internal tags in the front of the segment. In these cases the automatic tag-repair now also kicks in', '15'),
('2024-09-05', 'TRANSLATE-4169', 'bugfix', 't5memory - Match results in Editor rendered in escaped format', 'Remove segment escaping in FE', '15');