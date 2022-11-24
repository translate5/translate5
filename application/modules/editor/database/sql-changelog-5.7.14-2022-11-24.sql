
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
            
INSERT INTO `LEK_change_log` (`dateOfChange`, `jiraNumber`, `type`, `title`, `description`, `userGroup`) VALUES ('2022-11-24', 'TRANSLATE-3013', 'feature', 'TermPortal - TermPortal: Define available attributes', 'TermPortal: added ability to define which attributes are available in which TermCollections', '15'),
('2022-11-24', 'TRANSLATE-2551', 'feature', 'Import/Export - Update Task with xliff', 'Enable existing file to be replaced and with this the segments will be updated in the task.', '15'),
('2022-11-24', 'TRANSLATE-3101', 'change', 'TermTagger integration - Change TermImportController to be accessible by cron', 'editor/plugins_termimport_termimport/filesystem and editor/plugins_termimport_termimport/crossapi actions are now protected based on the calling IP address (cronIP)', '15'),
('2022-11-24', 'TRANSLATE-3100', 'change', 'Editor general - CronIp improvement', 'Configuration runtimeOptions.cronIP now supports: 
  - multiple comma-separated values
  - IP with subnet (CIDR)
  - domain names', '15'),
('2022-11-24', 'TRANSLATE-3099', 'change', 'Editor general - IP-authentication is not working in docker environment', 'Add a new configuration value to enable the usage of IP authentication behind a local proxy.', '15'),
('2022-11-24', 'TRANSLATE-3092', 'change', 'Test framework - Test API: Implement status-check loop for tbx-reimport', 'Test API: Status-check loop for tbx reimport implemented', '15'),
('2022-11-24', 'TRANSLATE-3086', 'change', 'TermPortal - Termportal: add introduction window with embedded youtube video', 'TermPortal: introduction dialog with youtube video is now shown once TermPortal is opened', '15'),
('2022-11-24', 'TRANSLATE-3107', 'bugfix', 'TermPortal, TermTagger integration - TBX import with huge image nodes fail', 'TBX files with huge images inside could crash the TBX import leading to incomplete term collections.', '15'),
('2022-11-24', 'TRANSLATE-3106', 'bugfix', 'Editor general - Prevent Google automatic site translation', 'Added Metatag to prevent automatic page translation in Chrome & Firefox', '15'),
('2022-11-24', 'TRANSLATE-3105', 'bugfix', 'Export - Export of OKAPI tasks may generate wrong warning about tag-errors', 'FIX: Exporting a task generated with OKAPI may caused falsely warnings about tag-errors', '15'),
('2022-11-24', 'TRANSLATE-3104', 'bugfix', 'Configuration - Implement a simple key value config editor for map types', 'Added editor for configurations of type json map. Therefore changed `runtimeOptions.lengthRestriction.pixelMapping` to be visible and editable in UI', '15'),
('2022-11-24', 'TRANSLATE-3098', 'bugfix', 'Editor general - Enable qm config is not respected in task meta panel', 'The config for disabling segment qm panel will be evaluated now.', '15'),
('2022-11-24', 'TRANSLATE-3091', 'bugfix', 'TermPortal - TermPortal: RootCause error shown while browsing crossReference', 'Fixed bug, happening on attempt to navigate to crossReference', '15'),
('2022-11-24', 'TRANSLATE-3090', 'bugfix', 'TermPortal - TermPortal: change DE-placeholder for noTermDefinedFor-field in filter-window', 'Some wordings improved for TermPortal GUI', '15'),
('2022-11-24', 'TRANSLATE-3089', 'bugfix', 'TermPortal - TermPortal: nothing happens on attribute save in batch editing mode', 'Fixed termportal batch-editing bug', '15'),
('2022-11-24', 'TRANSLATE-3088', 'bugfix', 'Repetition editor - Repetition editor: missing css class for context rows', 'Tags styling for context rows in repetition editor is now the same as for repetition rows', '15'),
('2022-11-24', 'TRANSLATE-3087', 'bugfix', 'Editor general - Editor: term tooltip shows wrong attribute labels', 'TermPortlet attribute labels logic improved, Image-attribute preview shown, if exists', '15'),
('2022-11-24', 'TRANSLATE-3085', 'bugfix', 'TermPortal - Termportal: solve bug happening on creating attribute in batch window', 'Investigate and solve bug catched by root cause, which appears on selecting the attrubute to be created within attributes batchediting window, so the error happens when on attempt to save draft attributes

https://app.therootcause.io/#marc-mittag/translate5/errors/871b0f3b26eb27a5c27562390fbb7dddd14f5dba', '15'),
('2022-11-24', 'TRANSLATE-3084', 'bugfix', 'TermPortal - Termportal: use TextArea for Definition-attributes', 'TermPortal: textareas are now used for attributes of datatype noteText', '15'),
('2022-11-24', 'TRANSLATE-3073', 'bugfix', 'InstantTranslate - Filetranslation must not use autoQA', 'Filetranslation-tasks do now skip AutoQA-step in the import process', '15');