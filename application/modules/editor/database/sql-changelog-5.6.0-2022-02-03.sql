
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
            
INSERT INTO `LEK_change_log` (`dateOfChange`, `jiraNumber`, `type`, `title`, `description`, `userGroup`) VALUES ('2022-02-03', 'TRANSLATE-2727', 'feature', 'Task Management - Column for "ended" date of a task in the task grid and the exported meta data file for a task', 'A new column "ended date" is added to the task overview. It is filled automatically with the timestamp when the task is ended by the pm (not to be confused with finishing a workflow step).', '15'),
('2022-02-03', 'TRANSLATE-2671', 'feature', 'Import/Export, VisualReview / VisualTranslation - WYSIWIG for Videos', 'The visual now has capabilities to load a video as source together with segments and their timecodes (either as XSLX or SRT file). This gives the following new Features:

* Video highlights the timecoded segments when the player reaches the play position
* Annotations can be added to the video that appear as tooltip with an arrow pointing to the position on the selected timecodes frame
* Import of subtitle (.srt) files as workfiles
* Player can be navigated by clicking on the segments in the grid to play the segment
* Clicking on the timerail of the video highlights the associated segment
* Jumping from segment to segment, forth and back with player buttons and shortcuts
* In the Comment/Annotation Overview, clicking Comments/Annotations will navigate the video
* The Items in the Comment/Annotation Overview show their timecodes and are ordered by timecode

The Following prequesites must be fullfilled by a video to be used as visual source:
* mp4 file-format,
* h264 Codec
* max FullHD (1920x1080) resolution
', '15'),
('2022-02-03', 'TRANSLATE-2540', 'feature', 'Auto-QA - Check "target is empty or contains only spaces, punctuation, or alike"', 'Empty segments check added', '15'),
('2022-02-03', 'TRANSLATE-2537', 'feature', 'Auto-QA - Check inconsistent translations', 'Added consistency checks: segments with same target, but different source and segments with same source, but different target. In both cases tags ignored.', '15'),
('2022-02-03', 'TRANSLATE-2491', 'feature', 'TermPortal - Term-translation-Workflow', 'Added ability to transfer terms from TermPortal to Translate5, and import back to those terms TermCollection(s) once translated', '15'),
('2022-02-03', 'TRANSLATE-2080', 'feature', 'Task Management - Round up project creation wizard by refactoring and enhancing first screen', 'The first page of the project / task creation wizard was completely reworked with regard to the file upload. Now files can be added by drag and drop. The source and target language of bilingual files is automatically read out from the file and set then in wizard. This allows project creation directly out of a bunch of files without putting them in a ZIP file before. The well known ZIP import will still work.', '15'),
('2022-02-03', 'TRANSLATE-2792', 'change', 'TermPortal - Sort attributes filter drop down alphabetically', 'options in TermPortal filter-window attributes-combobox are now sorted alphabetically', '15'),
('2022-02-03', 'TRANSLATE-2777', 'change', 'TermPortal - Usability enhancements for TermPortal', 'Added a number of usability enhancements for TermPortal', '15'),

('2022-02-03', 'TRANSLATE-2678', 'change', 'VisualReview / VisualTranslation - WYSIWIG for Videos: Export Video Annotations', 'See TRANSLATE-2671', '15'),
('2022-02-03', 'TRANSLATE-2676', 'change', 'VisualReview / VisualTranslation - WYSIWIG for Videos: Frontend: Extending Annotations for Videos', 'See TRANSLATE-2671', '15'),
('2022-02-03', 'TRANSLATE-2675', 'change', 'VisualReview / VisualTranslation - WYSIWIG for Videos: Frontend: New IframeController "Video", new Visual iframe for Videos', 'See TRANSLATE-2671', '15'),
('2022-02-03', 'TRANSLATE-2674', 'change', 'VisualReview / VisualTranslation - WYSIWIG for Videos: Add new Review-type, Video-HTML-Template', 'See TRANSLATE-2671', '15'),
('2022-02-03', 'TRANSLATE-2673', 'change', 'Import/Export - WYSIWIG for Videos: Import Videos with Excel Timeline', 'See TRANSLATE-2671', '15'),
('2022-02-03', 'TRANSLATE-2801', 'bugfix', 'Repetition editor - Do not update matchrate on repetitions for review tasks', 'In the last release it was introduced that segments edited with the repetition editor was getting always the 102% match-rate for repetitions. Since is now changed so that this affects only translations and in review tasks the match rate is not touched in using repetitions.', '15'),
('2022-02-03', 'TRANSLATE-2800', 'bugfix', 'Editor general - User association wizard error when removing users', 'Solves problem when removing associated users from the task and quickly selecting another user from the grid afterwards.', '15'),
('2022-02-03', 'TRANSLATE-2797', 'bugfix', 'TBX-Import - Definition is not addable on language level due wrong default datatype', 'In some special cases the collected term attribute types and labels were overwriting some default labels. This so overwritten labels could then not be edited any more in the GUI.', '15'),
('2022-02-03', 'TRANSLATE-2796', 'bugfix', 'TermPortal - Change tooltip / Definition on language level cant be set / Double attribute of "Definition" on entry level', 'tooltips changed to \'Forbidden\' / \'Verboten\' for deprecatedTerm and supersededTerm statuses', '15'),
('2022-02-03', 'TRANSLATE-2795', 'bugfix', 'Import/Export, TermPortal - Term TBX-ID and term tbx-entry-id should be exported in excel-export', 'TermCollection Excel-export feature is now exporting Term/Entry tbx-ids instead of db-ids', '15'),
('2022-02-03', 'TRANSLATE-2794', 'bugfix', 'TermPortal - TermEntries are not deleted on TermCollection deletion', 'TermEntries are not deleted automatically on TermCollection deletion due a missing foreign key connection in database.', '15'),
('2022-02-03', 'TRANSLATE-2791', 'bugfix', 'TermTagger integration - Extend term attribute mapping to <descrip> elements', 'In the TermPortal proprietary TBX attributes could be mapped to the Usage Status. This was restricted to termNotes, now all types of attributes can be mapped (for example xBool_Forbidden in descrip elements).', '15'),
('2022-02-03', 'TRANSLATE-2790', 'bugfix', 'OpenTM2 integration - Disable OpenTM2 fixes if requesting t5memory', 'The OpenTM2 TMX import fixes are not needed anymore for the new t5memory, they should be disabled if the language resource is pointing to t5memory instead OpenTM2.', '15'),
('2022-02-03', 'TRANSLATE-2788', 'bugfix', 'Configuration - No default values in config editor for list type configs with defaults provided', 'For some configuration values the config editor in the settings was not working properly. This is fixed now.', '15'),
('2022-02-03', 'TRANSLATE-2785', 'bugfix', 'LanguageResources - Improve DeepL error handling and other fixes', 'DeepL was shortly not reachable, the produced errors were not handled properly in translate5, this is fixed. ', '15'),
('2022-02-03', 'TRANSLATE-2781', 'bugfix', 'Editor general - Access to job is still locked after user has closed his window', 'If a user just closes the browser it may happen that the there triggered automaticall logout does not work. Then the edited task of the user remains locked. The garbage cleaning and the API access to so locked jobs are improved, so that the task is getting unlocked then.', '15'),
('2022-02-03', 'TRANSLATE-2780', 'bugfix', 'VisualReview / VisualTranslation - Add missing close button to visual review simple mode', 'For embedded usage of the translate5 editor only: In visual review simple mode there is now also a close application button - in the normal mode it was existing already.', '15'),
('2022-02-03', 'TRANSLATE-2776', 'bugfix', 'Import/Export - XLF translate no with different mrk counts lead to unknown mrk tag error', 'The combination of XLF translate = no and a different amount of mrk segments in source and target was triggering erroneously this error.', '15'),
('2022-02-03', 'TRANSLATE-2775', 'bugfix', 'InstantTranslate - Issue with changing the language in InstantTranslate', 'fixed issue with changing the language in InstantTranslate', '15'),
('2022-02-03', 'TRANSLATE-2774', 'bugfix', 'Workflows - The calculation of a tasks workflow step is not working properly', 'The workflow step calculation of a task was calculating a wrong result if a workflow step (mostly visiting of a visitor) was added as first user.', '15'),
('2022-02-03', 'TRANSLATE-2773', 'bugfix', 'Auto-QA - Wrong job loading method in quality context used', 'There were errors on loading a tasks qualities on a no workflow task.', '15'),
('2022-02-03', 'TRANSLATE-2771', 'bugfix', 'OpenTM2 integration - translate5 sends bx / ex tags to opentm2 instead of paired g-tag', 'The XLF tag pairer does not work if the string contains a single tag in addition to the paired tag.', '15'),
('2022-02-03', 'TRANSLATE-2770', 'bugfix', 'TermPortal - Creating terms in TermPortal are creating null definitions instead empty strings', 'Fixed a bug on importing TBX files with empty definitions.', '15'),
('2022-02-03', 'TRANSLATE-2769', 'bugfix', 'VisualReview / VisualTranslation - Hide and collapse annotations is not working', 'Fixes the problem with hide and collapse annotations in visual.', '15'),
('2022-02-03', 'TRANSLATE-2767', 'bugfix', 'TermPortal - Issues popped up in Transline presentation', 'fixed js error, added tooltips for BatchWindow buttons', '15'),
('2022-02-03', 'TRANSLATE-2723', 'bugfix', 'Task Management, User Management - Reminder E-Mail sent multiple times', 'Fixed an annoying bug responsible for sending the deadline reminder e-mails multiple times. ', '15'),
('2022-02-03', 'TRANSLATE-2712', 'bugfix', 'VisualReview / VisualTranslation - Visual review: cancel segment editing removes the content from layout', 'FIXED: Bug where Text in the Visual disappeared, when the segment-editing was canceled', '15');