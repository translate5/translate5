
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

INSERT INTO `LEK_change_log` (`dateOfChange`, `jiraNumber`, `type`, `title`, `description`, `userGroup`) VALUES ('2020-09-16', 'TRANSLATE-1050', 'feature', 'Save user customization of editor', 'The user may now change the visible columns and column positions and widths of the segment grid. This customizations are restored on next login.', '14'),
('2020-09-16', 'TRANSLATE-2071', 'feature', 'VisualReview: XML with "What you see is what you get" via XSL transformation', 'A XML with a XSLT can be imported into translate5. The XML is then converted into viewable content in VisualReview.', '4'),
('2020-09-16', 'TRANSLATE-2111', 'feature', 'Make pop-up about "Reference files available" and "Do you really want to finish" pop-up configurable', 'For both pop ups it is now configurable if they should be used and shown in the application.', '8'),
('2020-09-16', 'TRANSLATE-1793', 'feature', 'search and replace: keep last search field or preset by workflow step.', 'The last searched field and content is saved and remains in the search window when it was closed.', '2'),
('2020-09-16', 'TRANSLATE-1617', 'change', 'Renaming of buttons on leaving a task', 'The label of the leave Button was changed.', '6'),
('2020-09-16', 'TRANSLATE-2180', 'change', 'Enhance displayed text for length restrictions in the editor', 'The display text of the segment length restriction was changed.', '2'),
('2020-09-16', 'TRANSLATE-2186', 'change', 'Implement close window button for editor only usage', 'To show that Button set runtimeOptions.editor.toolbar.hideCloseButton to 0. This button can only be used if translate5 was opened via JS window.open call.', '8'),
('2020-09-16', 'TRANSLATE-2193', 'change', 'Remove "log out" button in editor', 'The user has first to leave the task before he can log out.', '14'),
('2020-09-16', 'TRANSLATE-630', 'bugfix', 'Enhance, when text filters of columns are send', 'When using a textfilter in a grid in the frontend, the user has to type very fast since the filters were sent really fast to the server. This is changed now.', '2'),
('2020-09-16', 'TRANSLATE-1877', 'bugfix', 'Missing additional content and filename of affected file in E1069 error message', 'Error E1069 shows now also the filename and the affected characters.', '8'),
('2020-09-16', 'TRANSLATE-2010', 'bugfix', 'Change tooltip of tasks locked because of excel export', 'The content of the tooltip was improved.', '4'),
('2020-09-16', 'TRANSLATE-2014', 'bugfix', 'Enhance "No results found" message in InstantTranslate', 'Enhance "No results found" message in InstantTranslate', '2'),
('2020-09-16', 'TRANSLATE-2156', 'bugfix', 'Remove "Choose automatically" option from drop-down, that chooses source or target for connecting the layout with', 'Since this was confusing users the option was removed and source is the new default', '4'),
('2020-09-16', 'TRANSLATE-2195', 'bugfix', 'InstantTranslate filepretranslation API has a wrong parameter name', 'The parameter was 0 instead as documented in confluence.', '8'),
('2020-09-16', 'TRANSLATE-2215', 'bugfix', 'VisualReview JS Error: me.down(...) is null', 'Error happend in conjunction with the usage of the action buttons in Visual Review.', '8'),
('2020-09-16', 'TRANSLATE-1031', 'bugfix', 'Currently edited column in row editor is not aligned right', 'When scrolling horizontally in the segment grid, this could lead to positioning problems of the segment editor.', '2');