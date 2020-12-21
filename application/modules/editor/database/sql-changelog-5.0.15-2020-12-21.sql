
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
            
INSERT INTO `LEK_change_log` (`dateOfChange`, `jiraNumber`, `type`, `title`, `description`, `userGroup`) VALUES ('2020-12-21', 'TRANSLATE-2249', 'feature', 'Length restriction for sdlxliff files', 'SDLXLIFF specific length restrictions are now read out and used for internal processing.', '12'),
('2020-12-21', 'TRANSLATE-2343', 'change', 'Enhance links from default skin to www.translate5.net', 'Change links from default skin to www.translate5.net', '8'),
('2020-12-21', 'TRANSLATE-390', 'change', 'Prevent that the same error creates a email on each request to prevent log spam', 'Implemented the code base to recognize duplicated errors and prevent sending error mails.', '8'),
('2020-12-21', 'TRANSLATE-2353', 'bugfix', 'OpenTM2 strange matching of single tags', 'In the communication with OpenTM2 the used tags are modified to improve found matches.', '15'),
('2020-12-21', 'TRANSLATE-2346', 'bugfix', 'Wrong Tag numbering on using language resources', 'If a segment containing special characters and is taken over from a language resource, the tag numbering could be messed up. This results then in false positive tag errors.', '15'),
('2020-12-21', 'TRANSLATE-2339', 'bugfix', 'OpenTM2 can not handle  datatype="unknown" in TMX import', 'OpenTM2 does not import any segments from a TMX, that has  datatype="unknown" in its header tag, this is fixed by modifying the TMX on upload.', '12'),
('2020-12-21', 'TRANSLATE-2338', 'bugfix', 'Use ph tag in OpenTM2 to represent line-breaks', 'In the communication with OpenTM2 line-breaks are converted to ph type="lb" tags, this improves the matchrates for affected segments.', '15'),
('2020-12-21', 'TRANSLATE-2336', 'bugfix', 'Auto association of language resources does not use language fuzzy match', 'Now language resources with a sub-language (de-de, de-at) are also added to tasks using only the base language (de). ', '12'),
('2020-12-21', 'TRANSLATE-2334', 'bugfix', 'Pressing ESC while task is uploading results in task stuck in status import', 'Escaping from task upload window while uploading is now prevented.', '12'),
('2020-12-21', 'TRANSLATE-2332', 'bugfix', 'Auto user association on task import does not work anymore', 'Auto associated users are added now again, either as translators or as revieweres depending on the nature of the task.', '12'),
('2020-12-21', 'TRANSLATE-2328', 'bugfix', 'InstantTranslate: File upload will not work behind a proxy', 'InstantTranslate file upload may not work behind a proxy, depending on the network configuration. See config worker.server.', '8'),
('2020-12-21', 'TRANSLATE-2294', 'bugfix', 'Additional tags from language resources are not handled properly', 'The tag and whitespace handling of all language resources are unified and fixed, regarding to missing or additional tags.', '12');
