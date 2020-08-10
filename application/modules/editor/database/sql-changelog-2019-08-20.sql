
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

INSERT INTO `LEK_change_log` (`dateOfChange`, `jiraNumber`, `type`, `title`, `description`, `userGroup`) VALUES ('2019-08-20', 'TRANSLATE-1738', 'change', 'Add "Added from MT" to note field of Term, if term stems from InstantTranslate', 'Add "Added from MT" to note field of Term, if term stems from InstantTranslate', '8'),
('2019-08-20', 'TRANSLATE-1739', 'change', 'InstantTranslate: Add button to switch languages', 'Some new buttons to switch language were added.', '8'),
('2019-08-20', 'TRANSLATE-1737', 'change', 'Only show "InstantTranslate into" drop down, if no field is open for editing', 'Only show "InstantTranslate into" drop down, if no field is open for editing', '8'),
('2019-08-20', 'TRANSLATE-1743', 'change', 'Term proposal system: Icons and Shortcuts for Editing', 'Improved icons and shortcuts for Editing.', '8'),
('2019-08-20', 'TRANSLATE-1752', 'bugfix', 'error E1149 - Export: Some segments contains tag errors is logged to much on proofreading tasks.', 'error E1149 - Export: Some segments contains tag errors is logged to much on proofreading tasks.', '8'),
('2019-08-20', 'TRANSLATE-1732', 'bugfix', 'Open Bugs term proposal system', 'Fixed several bugs.', '8'),
('2019-08-20', 'TRANSLATE-1749', 'bugfix', 'LanguageTool: Spellcheck is not working any more in Firefox', 'The spellcheck did not work in the Firefox anymore.', '14'),
('2019-08-20', 'TRANSLATE-1758', 'bugfix', 'TrackCanges: Combination of trackchanges and terminology produces sometimes corrupt segments (warning "E1132")', 'The combination of trackchanges and terminology produced sometimes corrupt segments with warning "E1132 - Conflict in merging terminology and track changes".', '12'),
('2019-08-20', 'TRANSLATE-1755', 'bugfix', 'Transit Import is not working anymore', 'Now Transit import is working again.', '12'),
('2019-08-20', 'TRANSLATE-1754', 'bugfix', 'Authentication via session auth hash does a wrong redirect if the instance is located in a sub directory', 'Only instances which are not directly in the document root were affected.', '8'),
('2019-08-20', 'TRANSLATE-1750', 'bugfix', 'Loading of tasks in the task overview had a bad performance', 'The performance was improved.', '12'),
('2019-08-20', 'TRANSLATE-1747', 'bugfix', 'E9999 - Missing Arguments $code and $message', 'A wrong usage of the logger was repaired.', '8'),
('2019-08-20', 'TRANSLATE-1757', 'bugfix', 'JS Error in LanguageResources Overview if task names contain " characters', 'Quotes in the task names were leading to a JS Error in LanguageResources overview', '12');