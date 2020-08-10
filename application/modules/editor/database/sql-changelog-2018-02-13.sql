
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

INSERT INTO `LEK_change_log` (`dateOfChange`, `jiraNumber`, `type`, `title`, `description`, `userGroup`) VALUES ('2018-02-13', 'TRANSLATE-32', 'feature', 'Search and Replace in translate5 editor', 'The search and replace functionality is currently only available with enabled TrackChanges Plugin. ', '14'),
('2018-02-13', 'TRANSLATE-1116', 'feature', 'Clone a already imported task', 'Clone a already imported task - must be activated via an additional ACL right editorCloneTask (resource frontend). ', '12'),
('2018-02-13', 'TRANSLATE-1109', 'feature', 'Enable import of invalid XLIFF used for internal translations', 'Enable import of invalid XLIFF used for internal translations', '8'),
('2018-02-13', 'TRANSLATE-1107', 'feature', 'VisualReview Converter Server Wrapper', 'With the VisualReview Converter Server Wrapper the converter can be called on a different server as the webserver.', '8'),
('2018-02-13', 'TRANSLATE-1019', 'change', 'Improve File Handling Architecture in the import and export process', 'Improve File Handling Architecture in the import and export process', '8'),
('2018-02-13', 'T5DEV-218', 'change', 'Enhance visualReview matching algorithm', 'Enhance visualReview matching algorithm', '12'),
('2018-02-13', 'TRANSLATE-1017', 'change', 'Use Okapi longhorn for merging files back instead tikal', 'Use Okapi longhorn for merging files back instead tikal', '12'),
('2018-02-13', 'TRANSLATE-1121', 'change', 'Several minor improvement in the installer', 'Several minor improvement in the installer', '8'),
('2018-02-13', 'TRANSLATE-667', 'change', 'GUI cancels task POST requests longer than 60 seconds', 'Now bigger uploads taking longer as 60 seconds are uploaded successfully.', '12'),
('2018-02-13', 'TRANSLATE-1131', 'bugfix', 'Internet Explorer compatibility mode results in non starting application', 'Internet Explorer triggers the compatibility mode for translate5 used in intranets.', '14'),
('2018-02-13', 'TRANSLATE-1122', 'bugfix', 'TrackChanges: saving content to an attached matchresource (openTM2) saves also the <del> content', 'TrackChanges: saving content to an attached matchresource (openTM2) saves also the <del> content', '14'),
('2018-02-13', 'TRANSLATE-1108', 'bugfix', 'VisualReview: absolute paths for CSS and embedded fonts are not working on installations with a modifed APPLICATION_RUNDIR', 'VisualReview: absolute paths for CSS and embedded fonts are not working on installations with a modifed APPLICATION_RUNDIR', '8'),
('2018-02-13', 'TRANSLATE-1138', 'bugfix', 'Okapi Export does not work with files moved internally in translate5', 'Files moved inside the file tree on the left side of the editor window, could not merged back with Okapi on export.', '12'),
('2018-02-13', 'TRANSLATE-1112', 'bugfix', 'Across XML parser has problems with single tags in the comment XML', 'Across XML parser has problems with single tags in the comment XML', '12'),
('2018-02-13', 'TRANSLATE-1110', 'bugfix', 'Missing and wrong translated user roles in the notifyAllAssociatedUsers e-mail', 'Missing and wrong translated user roles in the notifyAllAssociatedUsers e-mail', '12'),
('2018-02-13', 'TRANSLATE-1117', 'bugfix', 'In IE Edge in the HtmlEditor the cursor cannot be moved by mouse only by keyboard', 'In IE Edge in the HtmlEditor the cursor cannot be moved by mouse only by keyboard', '14'),
('2018-02-13', 'TRANSLATE-1141', 'bugfix', 'TrackChanges: Del-tags are not ignored when the characters are counted in min/max length', 'With activated TrackChanges Plug-In del-tags are not ignored when the characters are counted in segment min/max length.', '14');