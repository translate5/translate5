
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
            
INSERT INTO `LEK_change_log` (`dateOfChange`, `jiraNumber`, `type`, `title`, `description`, `userGroup`) VALUES ('2021-12-08', 'TRANSLATE-2728', 'feature', 'Link terms in segment meta panel to the termportal', 'In the segments meta panel all terms of the currently edited segment are shown. This terms are now clickable linked to the termportal - if the termportal is available.', '15'),
('2021-12-08', 'TRANSLATE-2713', 'feature', 'Use HTML linking in Visual based on xml/xsl, if workfiles are xliff', 'Added option to add a XML/XSL combination as visual source direct in the /visual folder of the import zip: If there is an XML in the /visual folder with a linked XSL stylesheet that is present in the /visual folder as well, the visual HTML is generated from these files using the normal, text-based segmentation (and not aligning the XML against the imported bilingual workfiles)', '15'),
('2021-12-08', 'TRANSLATE-2666', 'feature', 'WYSIWYG for Images with Text', 'This new feature enables using a single Image as a source for a Visual. 
This Image is then analyzed (OCR) and the found text can be edited in the right WYSIWIG-frame. 
* The Image must be imported in the subfolder /visual/image of the import-zip
* A single WebFont-file (*.woff) can be added alongside the Image and then will be used as Font for the whole text on the Image
* If no font is provided, Arial is the general fallback
* Any text not present in the bilingual file in /workfiles will be removed from the OCR\'s output. This means, the bilingual file should contain exactly the text, that is expected to be on the image and to be translated', '15'),
('2021-12-08', 'TRANSLATE-2387', 'feature', 'Annotate visual', 'The users are able to add text annotations(markers) where ever he likes in the visual area.  Also the users are able to create segment annotations when clicking on a segment in the layout.', '15'),
('2021-12-08', 'TRANSLATE-2303', 'feature', 'Overview of comments', 'A new Comment section has been added to the left-hand side of the Segment editor.
It lists all the segment comments and visual annotations ordered by page. The type is indicated by a small symbol to the left. Its background color indicates the authoring user.
When an element of that list is clicked, translate5 jumps to the corresponding remark, either in the VisualReview or in the segment grid.
On hover the full remark is shown in a tooltip, together with the authoring user and the last change date.
New comments are added in realtime to the list.', '15'),
('2021-12-08', 'TRANSLATE-2740', 'change', 'PHP 8 is now required - support for older PHP versions is dropped', 'Translate5 and all dependencies use now PHP 8.', '15'),
('2021-12-08', 'TRANSLATE-2733', 'change', 'Embed translate5 task video in help window', 'Embed the translate5 task videos as iframe in the help window. The videos are either in german or english, they are chosen automatically depending on the GUI interface. A list of links to jump to specific parts of the videos are provided.', '15'),
('2021-12-08', 'TRANSLATE-2726', 'change', 'Invert tooltipt font color in term-column in left panel', 'Term tooltip font color set to black for proposals to be readable', '15'),
('2021-12-08', 'TRANSLATE-2693', 'change', 'Write tests for new TermPortal', 'Created tests for all termportal api endpoints', '15'),
('2021-12-08', 'TRANSLATE-2670', 'change', 'WYSIWIG for Images: Frontend - General Review-type, new (mostly dummy) ImageScroller, extensions IframeController', 'see Translate-2666', '15'),
('2021-12-08', 'TRANSLATE-2669', 'change', 'WYSIWIG for Images: Extend Font-Management', 'see TRANSLATE-2666', '15'),
('2021-12-08', 'TRANSLATE-2668', 'change', 'WYSIWIG for Images: Add new Review-type, add worker & file managment, creation of HTML file representing the review', 'see TRANSLATE-2666', '15'),
('2021-12-08', 'TRANSLATE-2667', 'change', 'WYSIWIG for Images: Implement Text Recognition', 'see TRANSLATE-2666', '15'),
('2021-12-08', 'TRANSLATE-2487', 'change', 'Edit an attribute for multiple occurrences at once', 'Added ability for attributes batch editing', '15'),
('2021-12-08', 'TRANSLATE-2741', 'bugfix', 'Segment processing status is wrong on unchanged segments with tags', 'On reviewing the processing state of a segment was set wrong if the segment contains tags and was saved unchanged.', '15'),
('2021-12-08', 'TRANSLATE-2739', 'bugfix', 'Segment length validation does also check original target on TM usage', 'On tasks using segment length restrictions some segments could not be saved if content was overtaken manually from a language resource and edited afterwards to fit in the length restriction.', '15'),
('2021-12-08', 'TRANSLATE-2737', 'bugfix', 'VisualReview height not saved in session', 'Persist VisualReview height between reloads.', '15'),
('2021-12-08', 'TRANSLATE-2736', 'bugfix', 'State of show/hide split iframe is not saved correctly', 'Fix issues with the saved state of the show/hide split frame button in the visual', '15'),
('2021-12-08', 'TRANSLATE-2732', 'bugfix', 'Advanced filter users list anonymized users query', 'Solves advanced filter error for users with no "read-anonymized" users right.', '15'),
('2021-12-08', 'TRANSLATE-2731', 'bugfix', 'No redirect to login page if maintenance is scheduled', 'The initial page of the translate5 instance does not redirect to the login page if a maintenance is scheduled.', '15'),
('2021-12-08', 'TRANSLATE-2730', 'bugfix', 'Improve maintenance handling regarding workers', 'If maintenance is scheduled the export was hanging in a endless loop, also import related workers won\'t start anymore one hour before maintenance. ', '15'),
('2021-12-08', 'TRANSLATE-2729', 'bugfix', 'PDO type casting error in bind parameters', 'The user will no longer receive an error when the customer was deleted.', '15'),
('2021-12-08', 'TRANSLATE-2724', 'bugfix', 'Translation error in the Layout', 'Workflow name is localized now.', '15'),
('2021-12-08', 'TRANSLATE-2720', 'bugfix', 'Termportal initial loading takes dozens of seconds', 'Solved termportal long initial loading problem', '15'),
('2021-12-08', 'TRANSLATE-2715', 'bugfix', 'String could not be parsed as XML - on tbx import', 'The exported TBX was no valid XML therefore was an error on re-importing that TBX.', '15'),
('2021-12-08', 'TRANSLATE-2708', 'bugfix', 'Visual review: iframe scaling problem', 'Enables zoom in in all directions in visual.', '15'),
('2021-12-08', 'TRANSLATE-2707', 'bugfix', 'correct display language-selection in Instant-Translate', 'Fixed the language listing in InstantTranslate, which was broken for a lot of languages.', '15'),
('2021-12-08', 'TRANSLATE-2706', 'bugfix', 'Not all repetitions are saved after exchanging the term-collection', 'Not all repeated segments were changed if saving repetitions with terminology and the term-collection was changed in the task.', '15'),
('2021-12-08', 'TRANSLATE-2700', 'bugfix', 'Improve termtagging performance due table locks', 'The queuing of the segments prepared for term tagging is improved, so that multiple term taggers really should work in parallel. ', '15');