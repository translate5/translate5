
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
            
INSERT INTO `LEK_change_log` (`dateOfChange`, `jiraNumber`, `type`, `title`, `description`, `userGroup`) VALUES ('2021-10-07', 'TRANSLATE-2640', 'feature', 'Remove InstantTranslate on/off button from InstantTranslate and move functionality to configuration', 'The auto-translate feature in instant translate can be configured if active for each client.', '15'),
('2021-10-07', 'TRANSLATE-2645', 'change', 'TermPortal: set mysql fulltext search minimum word length to 1 and disable stop words', 'Please set innodb_ft_min_token_size in your mysql installation to 1 and  	innodb_ft_enable_stopword to 0.
This is necessary for TermPortal to find words shorter than 3 characters. If you did already install translate5 5.5.0 on your server OR if you did install translate 5.5.1 BEFORE you did change that settings in your mysql installation, then you would need to update the fulltext indexes of your DB term-tables manually. 
If this is the case, please call "./translate5.sh termportal:reindex" or contact us, how to do this.
Please run "./translate5.sh system:check" to check afterwards if everything is properly configured.', '15'),
('2021-10-07', 'TRANSLATE-2641', 'change', 'AdministrativeStatus default attribute and value', 'The "Usage Status (administrativeStatus)" attribute is now the leading one regarding the term status. Its value is synchronized to all other similar attributes (normativeAuthorization and other custom ones).', '15'),
('2021-10-07', 'TRANSLATE-2634', 'change', 'Integrate PDF documentation in translate5 help window', 'Pdf documentation in the editor help window is available now.
To change PDF location or disable see config runtimeOptions.frontend.helpWindow.editor.documentationUrl', '15'),
('2021-10-07', 'TRANSLATE-2607', 'change', 'Make type timeout in InstantTranslate configurable', 'The translation delay in instant translate can be configured now.', '15'),
('2021-10-07', 'TRANSLATE-2644', 'bugfix', 'Task related notification emails should link directly to the task', 'Currently task related notification E-Mails do not point to the task but to the portal only. This is changed.', '15'),
('2021-10-07', 'TRANSLATE-2643', 'bugfix', 'Usability improvements: default user assignment', 'Usability improvements in default user association overview.', '15');