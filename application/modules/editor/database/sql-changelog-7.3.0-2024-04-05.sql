
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
            
INSERT INTO `LEK_change_log` (`dateOfChange`, `jiraNumber`, `type`, `title`, `description`, `userGroup`) VALUES ('2024-04-05', 'TRANSLATE-3790', 'feature', 'LanguageResources - Overwrite DeepL API key per client', 'Allow rewrite DeepL API key on customer config level', '15'),
('2024-04-05', 'TRANSLATE-3534', 'feature', 'Import/Export, TrackChanges - TrackChanges sdlxliff round-trip', 'Accept track changes sdlxliff markup on import and transform it to translate5 syntax.
Propagate translate5 track changes to sdlxliff file on export', '15'),
('2024-04-05', 'TRANSLATE-3842', 'change', 'VisualReview / VisualTranslation - Newlines from segments (internal whitespace tags) do often "destroy" the layout, especially in the new paragraph layouts', 'ENHANCEMENT: 
* Configurable option to strip newlines from the segments when translating the WYSIWYG
* always strip newlines from segments for pragraph-fields in the WYSIWYG', '15'),
('2024-04-05', 'TRANSLATE-3841', 'change', 'Main back-end mechanisms (Worker, Logging, etc.) - TextShuttle available for clients with support contract', 'TextShuttle plugin is now available with support contract.', '15'),
('2024-04-05', 'TRANSLATE-3839', 'change', 'LanguageResources - Add possibility to UI to use timestamp of last segment save, when re-importing a task to the TM', 'Add new option to reimport task UI which purpose is to specify which time should be used for updating segment in translation memory.', '15'),
('2024-04-05', 'TRANSLATE-3733', 'change', 'LanguageResources - Introduce new language resource identifier specificId', 'Introduce a new ID field for language resources in available also via ID. 
It should contain an ID generated / coming from the originating data system - if any.', '15'),
('2024-04-05', 'TRANSLATE-3655', 'change', 'LanguageResources - implement new switch to deal with framing tags on TMX import', 'Added new option "Strip framing tags at import" for TMX import which influences the behavior of t5memory regarding segment framing tags on import.', '15'),
('2024-04-05', 'TRANSLATE-3845', 'bugfix', 'Editor general - RootCause error: null is not an object (evaluating \'d.mask\')', 'Fix problem for UI error when message bus re-sync is triggered.', '15'),
('2024-04-05', 'TRANSLATE-3840', 'bugfix', 'Hotfolder Import - Hotfolder import deadline format is too strict', 'The deadline timeformats were to strict', '15'),
('2024-04-05', 'TRANSLATE-3834', 'bugfix', 'Import/Export - runtimeOptions.project.defaultPivotLanguage not working for hotfolder projects', 'FIX: Apply runtimeOptions.project.defaultPivotLanguage setting on Project creation with Hotfolder plugin', '15'),
('2024-04-05', 'TRANSLATE-3799', 'bugfix', 't5memory - Segment check after update in t5memory doesn\'t work properly with escaped symbols', 'translate5 - 7.4.0: Remove tab replacement again
translate5 - 7.3.0: Additional code improvement
translate5 - 7.2.2: Fixed check if segment was updated properly in t5memory ', '15'),
('2024-04-05', 'TRANSLATE-3795', 'bugfix', 'Client management - clientPM should not be able to give himself term PM rights', 'FIX: Remove right for "PM selected clients" to make himself a "Term PM all clients"', '15'),
('2024-04-05', 'TRANSLATE-3731', 'bugfix', 'Task Management - Empty projects shows tasks of previous project', 'If due errors only a project is created but no tasks belonging to it, then the task list of such project behaves strange.', '15'),
('2024-04-05', 'TRANSLATE-3699', 'bugfix', 'User Management - Client PM can choose user with role Light PM as PM', 'FIX: PM for selected clients was able to select PMs not being assigned to his clients', '15'),
('2024-04-05', 'TRANSLATE-3630', 'bugfix', 'User Management - clientPM should be able to see all client configs', 'FIX: The PM selected clients now has access to "File format settings" and "pricing presets" for his selected clients', '15');