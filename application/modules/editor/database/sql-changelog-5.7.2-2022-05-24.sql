
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
            
INSERT INTO `LEK_change_log` (`dateOfChange`, `jiraNumber`, `type`, `title`, `description`, `userGroup`) VALUES ('2022-05-24', 'TRANSLATE-2642', 'feature', 'LanguageResources - DeepL terminology integration', 'Enable deepL language resources to use terminology as glossar.', '15'),
('2022-05-24', 'TRANSLATE-2314', 'feature', 'Editor general - Be able to lock/unlock segments in the editor by a PM', 'The project-manager is now able to lock and unlock single segments (CTRL+L). 
A jump to segment is implemented (CTRL+G).
Bookmarks can now be set also on just a selected segment, not only on an opened one (CTRL+D). Locking and bookmarking can be done in a batch way on all segments in the current filtered grid. ', '15'),
('2022-05-24', 'TRANSLATE-2976', 'change', 'Okapi integration - Make MS Office document properties translatable by default', 'The Okapi default settings are changed, so that MS Office document properties are now translateable by default.
', '15'),
('2022-05-24', 'TRANSLATE-2973', 'bugfix', 'LanguageResources - Tag Repair creates Invalid Internal tags when Markup is too complex', 'FIX: Automatic tag repair may generated invalid internal tags when complex markup was attempted to be translated', '15'),
('2022-05-24', 'TRANSLATE-2972', 'bugfix', 'Editor general - Leaving and Navigating to Deleted Tasks', 'Trying to access a deleted task via URL was not handled properly. Now the user is redirected to the task overview.', '15'),
('2022-05-24', 'TRANSLATE-2969', 'bugfix', 'Import/Export - Reintroduce BCONF import via ZIP', 'FIX: Re-enabled using a customized BCONF for OKAPI via the import zip. Please note, that this feature is nevertheless deprecated and the BCONF in the import zip will not be added to the application\'s BCONF pool.', '15'),
('2022-05-24', 'TRANSLATE-2968', 'bugfix', 'LanguageResources - Deleted space at start or end of fuzzy match not highlighted', 'Fixed visualization issues of added / deleted white-space in the fuzzy match grind of the lower language resource panel in the editor.', '15'),
('2022-05-24', 'TRANSLATE-2967', 'bugfix', 'TermPortal - TermPortal: grid-attrs height problem', 'Fixed the tiny height of attribute grids. ', '15'),
('2022-05-24', 'TRANSLATE-2965', 'bugfix', 'GroupShare integration - GroupShare sync deletes all associations between tasks and language-resources', 'The synchronization of GroupShare TMs was deleting to much task language resource associations.', '15'),
('2022-05-24', 'TRANSLATE-2964', 'bugfix', 'Workflows - PM Project Notification is triggered on each project instead only on term translation projects', 'Project creation notifications can now be sent only for certain project types.', '15'),
('2022-05-24', 'TRANSLATE-2926', 'bugfix', 'Okapi integration - Index and variables can not be extracted from Indesign', 'So far it was not possible to translate Indesign text variables and index entries, because Okapi did not extract them.

With an okapi contribution by Denis, financed by translate5, this is changed now.

Also translate5 default okapi settings are changed, so that text variables and index entries are now translated by default for idml.', '15');