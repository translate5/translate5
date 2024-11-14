
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
            
INSERT INTO `LEK_change_log` (`dateOfChange`, `jiraNumber`, `type`, `title`, `description`, `userGroup`) VALUES ('2024-10-27', 'TRANSLATE-4067', 'feature', 'LanguageResources, Task Management - Introduce Task TM in translate5', 'Introduced Task TM concept for t5memory', '15'),
('2024-10-27', 'TRANSLATE-4181', 'bugfix', 'Task Management, Workflows - Add missing workflow select field  and workflow steps from all existing workflows to task overview "advanced filters"', 'Added filter by Workflow in task overview', '15'),
('2024-10-27', 'TRANSLATE-3987', 'bugfix', 'file format settings - wrong segmentation', 'So far in certain cases it was segmented after a full stop (.), even if no whitespace followed. This is removed (the rule 
<rule break="yes">
<beforebreak>[\.!?…][\'"\u00BB\u2019\u201D\u203A\p{Pe}\u0002]*</beforebreak>
<afterbreak>\p{Lu}[^\p{Lu}]</afterbreak>
</rule>
got removed from srx)', '15');