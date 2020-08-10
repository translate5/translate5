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

INSERT INTO `LEK_change_log` (`dateOfChange`, `jiraNumber`, `type`, `title`, `description`, `userGroup`) VALUES ('2017-07-04', 'TRANSLATE-911', 'change', 'Workflow Notification mails could be too large for underlying mail system', 'The attachment of the changes.xliff is now configurable and disabled by default, so that the generated e-mails are much smaller.', '12'),
('2017-07-04', 'TRANSLATE-907', 'change', 'Several smaller issues (wording, code changes etc)', 'TRANSLATE-906: translation bug: "Mehr Info" in EN<br>TRANSLATE-909: Editor window - change column title "Target text(zur Importzeit)"<br>TRANSLATE-894: Copy source to target – FIX<br>TRANSLATE-907: Rename QM-Subsegments to MQM in the GUI<br>TRANSLATE-818: internal tag replace id usage with data-origid and data-filename - additional migration script<br>TRANSLATE-895: Copy individual tags from source to target - ToolTip<br>TRANSLATE-885: fill non-editable target for translation tasks - compare targetHash to history<br>small fix for empty match rate tooltips showing "null"', '14');
