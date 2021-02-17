
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
            
INSERT INTO `LEK_change_log` (`dateOfChange`, `jiraNumber`, `type`, `title`, `description`, `userGroup`) VALUES ('2021-02-17', 'TRANSLATE-1484', 'feature', 'Count translated characters by MT engine and customer', 'Enables language resources usage log and statistic export.', '15'),
('2021-02-17', 'TRANSLATE-2407', 'change', 'Embed new configuration help window', 'The brand-new help videos about the configuration possibilities are available now and embedded in the application as help pop-up.', '15'),
('2021-02-17', 'TRANSLATE-2402', 'change', 'Remove rights for PMs to change instance defaults for configuration', 'The PM will not longer be able to modify instance level configurations, only admin users may do that.', '15'),
('2021-02-17', 'TRANSLATE-2379', 'change', 'Workflow mails: Show only changed segments', 'Duplicating TRANSLATE-1979', '15'),
('2021-02-17', 'TRANSLATE-2406', 'bugfix', 'Translated text is not replaced with translation but concatenated', 'FIX: Solved problem where the Live editing did not remove the original text completely when replacing it with new contents', '15'),
('2021-02-17', 'TRANSLATE-2403', 'bugfix', 'Visual Review: Images are missing, the first Image is not shown in one Iframe', 'FIX: A downloaded Website for the Visual Review may not show responsive images when they had a source set defined
FIX: Elements with a background-image set by inline style in a downloaded website for the Visual Review may not show the background image
FIX: Some images were not shown either in the original iframe or the WYSIWIG iframe in a Visual Review
ENHANCEMENT: Focus-styles made the current page hard to see in the Visual Review pager ', '15'),
('2021-02-17', 'TRANSLATE-2401', 'bugfix', 'DeepL formality fallback', 'Formality will be set to "default" for resources with unsupported target languages.', '15'),
('2021-02-17', 'TRANSLATE-2396', 'bugfix', 'Diverged GUI and Backend version after update', 'The user gets an error message if the version of the GUI is older as the backend - which may happen after an update in certain circumstances. Normally this is handled due the usage of the maintenance mode.', '15'),
('2021-02-17', 'TRANSLATE-2391', 'bugfix', '"Complete task?". Text in dialog box is confusing.', 'The "Complete task?" text in the pop-up dialog was changed since it was confusing.', '15'),
('2021-02-17', 'TRANSLATE-2390', 'bugfix', 'TermImport plug-in matches TermCollection name to non-Termcollection-type languageresources', 'The termImport plug-in imports a TBX into an existing termCollection, if the name is the same as the one specified in the plug-in config file. Although the language resource type was not checked, so this led to errors if the found language resource was not of type term collection.', '15'),
('2021-02-17', 'TRANSLATE-1979', 'bugfix', 'Do not list "changes" of translator in mail send after finish of translation step', 'The changed segments will not longer be listed in the notification mails after translation step is finished - since all segments were changed here.', '15');