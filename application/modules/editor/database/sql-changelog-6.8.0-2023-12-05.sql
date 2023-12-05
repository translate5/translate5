
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
            
INSERT INTO `LEK_change_log` (`dateOfChange`, `jiraNumber`, `type`, `title`, `description`, `userGroup`) VALUES ('2023-12-05', 'TRANSLATE-3561', 'change', 't5memory - Enable t5memory connector to load balance big TMs', 'Fix overflow error when importing very big files into t5memory by splitting the TM internally.', '15'),
('2023-12-05', 'TRANSLATE-3537', 'change', 'Import/Export - Process comments from xliff 1.2 files', 'XLF comments placed in note tags are now also imported and exported as task comments. The behavior is configurable.', '15'),
('2023-12-05', 'TRANSLATE-3601', 'bugfix', 'VisualReview / VisualTranslation - Change default for processing of invisible texts in PDF converter in Visual', 'Changed default for processing of invisible text in the visual (Text visibility correction) to fix only fully occluded text', '15');