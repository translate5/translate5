
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
            
INSERT INTO `LEK_change_log` (`dateOfChange`, `jiraNumber`, `type`, `title`, `description`, `userGroup`) VALUES ('2023-07-13', 'TRANSLATE-3426', 'bugfix', 'Editor general - Error while trying to set content to editor from matches', 'Fix for problem when taking over language resources suggested translations can lead to UI error', '15'),
('2023-07-13', 'TRANSLATE-3425', 'bugfix', 'Import/Export - Tags imported from across get wrong id', 'In across xliff the tags may use a custom unique id instead the default id attribute which leads to problems with duplicated tags which had to be repaired manually in the past. Now the across ID is used instead.', '15'),
('2023-07-13', 'TRANSLATE-3424', 'bugfix', 'OpenTM2 integration - Tag mismatch in t5memory results due nonnumeric rids', 'Tags from segments may get removed when taking over from t5memory due mismatching tag ids.', '15'),
('2023-07-13', 'TRANSLATE-3402', 'bugfix', 'Okapi integration - Hotfix: delete deepl glossary on deleting termcollection', 'When deleting a termcollection the corresponding DeepL glossary was not deleted. This is fixed now.', '15');