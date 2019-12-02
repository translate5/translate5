
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

INSERT INTO `LEK_change_log` (`dateOfChange`, `jiraNumber`, `type`, `title`, `description`, `userGroup`) VALUES ('2019-12-02', 'TRANSLATE-1167', 'feature', 'Edit task simultanously with multiple users', 'Multiple users can edit the same task at the same time. See Translate5 confluence how to activate that feature!', '98'),
('2019-12-02', 'TRANSLATE-1493', 'feature', 'Filter by user, workflow-step, job-status and language combination', 'Several new filters can be used in the task overview.', '98'),
('2019-12-02', 'TRANSLATE-1889', 'change', 'rfc 5646 value for estonian is wrong', 'The RFC 5646 value for estonian was wrong', '96'),
('2019-12-02', 'TRANSLATE-1886', 'change', 'Error on refreshing GroupShare TMs when a used TM should be deleted', 'The error is fixed right now.', '96'),
('2019-12-02', 'TRANSLATE-1884', 'change', 'Special Character END OF TEXT in importable content produces errors.', 'The special character END OF TEXT is masked in the import now.', '96'),
('2019-12-02', 'TRANSLATE-1840', 'change', 'Insert opening and closing tag surround text selections with one key press', 'Insert opening and closing tag surround text selections with one key press', '98');