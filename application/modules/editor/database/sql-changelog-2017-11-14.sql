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

INSERT INTO `LEK_change_log` (`dateOfChange`, `jiraNumber`, `type`, `title`, `description`, `userGroup`) VALUES ('2017-11-14', 'TRANSLATE-931', 'feature', 'Tag check can NOT be skipped in case of tag error (configurable)', 'The application checks on saving a segment if all tags of the source exist in the target and also if tag order is correct. The user has had the choice to ignore the tag errors and save anyway. Now it is configurable if the user can save an erroneous segment anyway or not.', '14'),
('2017-11-14', 'TRANSLATE-822', 'feature', 'import XLF segment min and max length for future usage', 'XLF provides attributes to define the minimal and maximal length of segments. This constraints are imported and saved right now for usage in the future.', '12'),
('2017-11-14', 'TRANSLATE-1027', 'feature', 'Add translation step in default workflow', 'The default workflow covers now also translations tasks by providing a new first step "translation". The second step will then be "proofReading" and the third one "translatorCheck". All steps are optional and can be chosen just by setting the user to task association properly.', '12'),
('2017-11-14', 'TRANSLATE-1001', 'bugfix', 'Tag check did not work for translation tasks', 'Due the history reasons the tag validation on saving a task could not deal with translation tasks. This is fixed right now.', '14'),
('2017-11-14', 'TRANSLATE-1037', 'bugfix', 'VisualReview and feedback button are overlaying each other', 'The Feedback Button to send feedback about the application (if enabled so far) overlaps with the minimal navigation in visual review simple mode. This is fixed now by just hiding the feedback button in simple mode.', '14'),
('2017-11-14', 'TRANSLATE-763', 'bugfix', 'SDLXLIFF imports no segments with empty target tags', 'Some versions of SDL Studio export empty targets as empty single target tag. The import could not deal with such segments, this is fixed right now.', '12'),
('2017-11-14', 'TRANSLATE-1051', 'bugfix', 'Internal XLIFF reader for internal application translation can not deal with single tags', 'The internal XLIFF reader for internal application localization can not deal with single tags, this is fixed now for br tags.', '8');
