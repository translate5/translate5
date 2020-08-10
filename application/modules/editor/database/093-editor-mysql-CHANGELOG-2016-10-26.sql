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

UPDATE `LEK_change_log` SET `type` = 'bugfix' WHERE jiraNumber in ('TRANSLATE-684','TRANSLATE-745','TRANSLATE-749','TRANSLATE-753') and dateOfChange = '2016-10-17';
UPDATE `LEK_change_log` SET `type` = 'feature' WHERE jiraNumber in ('TRANSLATE-726','TRANSLATE-743') and dateOfChange = '2016-10-17';
UPDATE `LEK_change_log` SET `type` = 'change' WHERE jiraNumber in ('TRANSLATE-612', 'TRANSLATE-644', 'TRANSLATE-750') and dateOfChange = '2016-10-17';

INSERT INTO `LEK_change_log` (`dateOfChange`, `jiraNumber`, `type`, `title`, `description`, `userGroup`) VALUES ('2016-10-26', 'improved worker exception logging', 'change', 'Improve worker exception logging', 'Some types of exceptions were not logged when happened in worker context. This was fixed.', '8'),('2016-10-26', 'TRANSLATE-759', 'change', 'Introduce a config switch to set the default application GUI language', 'Until now the GUI language was defined by the language setting in the browser. With the new optional config “runtimeOptions.translation.applicationLocale” a default language can be defined, overriding the browser language.', '8'),('2016-10-26', 'TRANSLATE-751', 'change', 'Install and Update Script checks local DB configuration', 'Some Database settings are incompatible with the application. This is checked by the Install and Update Script now.', '8'),('2016-10-26', 'TRANSLATE-760', 'bugfix', 'Fix that sometimes source and target column were missing after import', 'Problem was introduced with refactoring import to Worker Architecture. Problem occurs only for users associated to the task. The visible columns in task specific settings were just initialized empty.', '14'),('2016-10-26', 'TRANSNET-10', 'bugfix', 'Inserted translate statement in the default login page', 'The default login and password reset page did not contain statements to translate the error messages. This was fixed.', '8');
