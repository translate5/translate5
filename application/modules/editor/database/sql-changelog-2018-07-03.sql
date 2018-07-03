
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

INSERT INTO `LEK_change_log` (`dateOfChange`, `jiraNumber`, `type`, `title`, `description`, `userGroup`) VALUES ('2018-07-03', 'VISUAL-43', 'change', 'VisualReview: Improve performance by splitting segments search into long, middle, short', 'VisualReview: Improve performance by splitting segments search into long, middle, short', '12'),
('2018-07-03', 'TRANSLATE-1323', 'change', 'SpellCheck must not remove the TermTag-Markup', 'TermTag-Markup was removed by using the SpellChecker, this should not be.', '14'),
('2018-07-03', 'TRANSLATE-1234', 'bugfix', 'changes.xliff diff algorithm fails under some circumstances', 'changes.xliff diff algorithm fails under some circumstances', '12'),
('2018-07-03', 'TRANSLATE-1306', 'bugfix', 'SpellCheck: blocked after typing with MatchResources', 'SpellCheck: blocked after typing with MatchResources', '14');