
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
            
INSERT INTO `LEK_change_log` (`dateOfChange`, `jiraNumber`, `type`, `title`, `description`, `userGroup`) VALUES ('2021-06-08', 'TRANSLATE-2501', 'change', 'Create table that contains all attribute types of a termCollection', 'All available data type attributes for term collection are saved in database.', '15'),
('2021-06-08', 'TRANSLATE-2532', 'bugfix', 'ERROR in core: E9999 - Call to a member function getMessage() on null', 'Fix a seldom PHP error, only happening when translate5 instance is tried to be crawled.', '15'),
('2021-06-08', 'TRANSLATE-2531', 'bugfix', 'Microsoft Translator language resource connector is not properly implemented', 'The Microsoft Translator language resource connector is not properly implemented regarding error handling and if a location restriction is used in the azure API configuration.', '15'),
('2021-06-08', 'TRANSLATE-2529', 'bugfix', 'Brute-Force attacks may produce: ERROR in core: E9999 - $request->getParam(\'locale\') war keine gültige locale', 'Providing invalid locales as parameter on application loading has produced an error. Now the invalid locale is ignored and the default one is loaded.', '15'),
('2021-06-08', 'TRANSLATE-2526', 'bugfix', 'Run analysis on task import wizard', 'Fixes problem with analysis and pre-translation not triggered for default associated resources on task import (without opening the language resources wizard)', '15');