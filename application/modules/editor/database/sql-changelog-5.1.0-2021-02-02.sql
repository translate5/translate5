
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
            
INSERT INTO `LEK_change_log` (`dateOfChange`, `jiraNumber`, `type`, `title`, `description`, `userGroup`) VALUES ('2021-02-02', 'TRANSLATE-2385', 'feature', 'introduce user login statistics', 'Now the login usage of the users is tracked in the new Zf_login_log table.', '15'),
('2021-02-02', 'TRANSLATE-2374', 'feature', 'Time of deadlines also visible in grid columns and notification mails', 'The date-time is now visible in the translate5 interface for date fields(if the time is relevant for this date field), and also in the mail templates.', '15'),
('2021-02-02', 'TRANSLATE-2362', 'feature', 'HTML / XML tag protection of tags in any kind of file format', 'XLF and CSV files can now contain HTML content (CSV: plain, XLF: encoded), the  HTML tags are protected as internal tags. This must be enabled in the config for the affected tasks.', '15'),
('2021-02-02', 'TRANSLATE-471', 'feature', 'Overwrite system config by client and task', 'Adds possibility to overwrite system configuration on 4 different levels: system, client, task import and task overwrite,', '15'),
('2021-02-02', 'TRANSLATE-2368', 'change', 'Add segment matchrate to Xliff 2 export as translate5 namespaced element', 'Each segment in the xliff 2 export will have the segment matchrate as translate5 namespace attribute.', '15'),
('2021-02-02', 'TRANSLATE-2357', 'change', 'introduce DeepL config switch "formality"', 'The "formality" deepl api flag now is available as task import config.
More about the formality flag:

Sets whether the translated text should lean towards formal or informal language. This feature currently works for all target languages except "EN" (English), "EN-GB" (British English), "EN-US" (American English), "ES" (Spanish), "JA" (Japanese) and "ZH" (Chinese).
Possible options are:
"default" (default)
"more" - for a more formal language
"less" - for a more informal language', '15'),
('2021-02-02', 'TRANSLATE-2354', 'change', 'Add language code to filename of translate5 export zip', 'When exporting a task, in the exported zip file name, the task source and target language codes are included.', '15'),
('2021-02-02', 'TRANSLATE-1120', 'change', 'Change default values of several configuration parameters', 'The default value in multiple system configurations is changed.', '15'),
('2021-02-02', 'TRANSLATE-929', 'change', 'Move old task template values to new system overwrite', 'The task template parameters definition moved to system configuration.', '15'),
('2021-02-02', 'TRANSLATE-2384', 'bugfix', 'Okapi does not always fill missing targets with source content', 'In some use cases only a few segments are translated, and on export via Okapi the not translated segments are filled up by copying the source content to target automatically. This copying was failing for specific segments.', '15'),
('2021-02-02', 'TRANSLATE-2382', 'bugfix', 'ERROR in core.api.filter: E1223 - Illegal field "customerUseAsDefaultIds" requested', 'Sometimes it may happen that a filtering for customers used as default in the language resource grid leads to the above error message. This is fixed now.', '15'),
('2021-02-02', 'TRANSLATE-2373', 'bugfix', 'Prevent termtagger usage if source and target language are equal', 'FIX: Prevent termtagger hanging when source and target language of a task are identical. Now in these cases the terms are not tagged anymore', '15'),
('2021-02-02', 'TRANSLATE-2372', 'bugfix', 'Whitespace not truncated InstantTranslate text input field', 'All newlines, spaces (including non-breaking spaces), and tabs are removed from the beginning and the end of the searched string in instant translate.', '15'),
('2021-02-02', 'TRANSLATE-2367', 'bugfix', 'NoAccessException directly after login', 'Opening Translate5 with an URL containing a task to be opened for editing leads to ZfExtended_Models_Entity_NoAccessException exception if the task was already finished or still in state waiting instead of opening the task in read only mode.', '15'),
('2021-02-02', 'TRANSLATE-2365', 'bugfix', 'Help window initial size', 'On smaller screens the close button of the help window (and also the "do not show again" checkbox) were not visible.', '15'),
('2021-02-02', 'TRANSLATE-2352', 'bugfix', 'Visual: Repetitions are linked to wrong position in the layout', 'FIXED: Problem in Visual Review that segments pointing to multiple occurances in the visual review always jumped to the first occurance when clicking on the segment in the segment grid. Now the current context (position of segment before, scroll-position of review) is taken into account', '15'),
('2021-02-02', 'TRANSLATE-2351', 'bugfix', 'Preserve "private use area" of unicode characters in visual review and ensure connecting segments', 'Characters of the Private Use Areas (as used in some symbol fonts e.g.) are now preserved in the Visual Review layout', '15'),
('2021-02-02', 'TRANSLATE-2335', 'bugfix', 'Do not query MT when doing analysis in batch mode without MT pre-translation', 'When the MT pre-translation checkbox is not checked in the match analysis overview, and batch query is enabled, all associated MT resources will not be used for batch query.', '15'),
('2021-02-02', 'TRANSLATE-2311', 'bugfix', 'Cookie Security', 'Set the authentication cookie according to the latest security recommendations.', '15');