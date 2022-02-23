
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
            
INSERT INTO `LEK_change_log` (`dateOfChange`, `jiraNumber`, `type`, `title`, `description`, `userGroup`) VALUES ('2022-02-17', 'TRANSLATE-2789', 'feature', 'Import/Export - Import: Support specially tagged bilingual pdfs from a certain client', 'FEATURE: Support special bilingual PDFs as source for the Visual', '15'),
('2022-02-17', 'TRANSLATE-2717', 'feature', 'Client management, Configuration - Take over client configuration from another client', 'New feature where customer configuration and default user assignments can be copied from one customer to another.', '15'),
('2022-02-17', 'TRANSLATE-2819', 'change', 'SpellCheck (LanguageTool integration) - SpellChecker: Add toggle button to activate/deactivate the SpellCheck', '', '15'),
('2022-02-17', 'TRANSLATE-2722', 'change', 'InstantTranslate, TermPortal - Customizable header for InstantTranslate including custom HTML', 'Enables custom header content configuration in instant-translate and term-portal. For more info see the instant-translate and term-portal header section in this link https://confluence.translate5.net/pages/viewpage.action?pageId=3866712', '15'),
('2022-02-17', 'TRANSLATE-2841', 'bugfix', 'Client management - Contents of clients tabs are not updated, when a new client is selected', 'Editing a customer in the customer panel is now possible with just selecting a row.', '15'),
('2022-02-17', 'TRANSLATE-2840', 'bugfix', 'Import/Export - Delete user association if the task import fails', 'Remove all user associations from a task, if the task import fails. So no e-mail will be sent to the users.', '15'),
('2022-02-17', 'TRANSLATE-2837', 'bugfix', 'Okapi integration - Change default segmentation rules to match Trados and MemoQ instead of Okapi and Across', 'So far translate5 (based on Okapi) did not segment after a colon.
Since Trados and MemoQ do that by default, this is changed now to make translate5 better compatible with the vast majority of TMs out there.', '15'),
('2022-02-17', 'TRANSLATE-2834', 'bugfix', 'MatchAnalysis & Pretranslation - Change repetition behaviour in pre-translation', 'On pre-translations with using fuzzy matches, repeated segments may be filled with different tags / amount of tags as there are tags in the source content. Then the repetition algorithm could not process such segments as repetitions and finally the analysis was not counting them as repetitions.
Now such segments always count as repetition in the analysis, but it does not get the 102% matchrate (since this may lead the translator to jump over the segment and ignore its content). Therefore such a repeated segment is filled with the fuzzy match content and the fuzzy match-rate. If the translator then edits and fix the fuzzy to be the correct translation , and then uses the repetition editor to fill the repetitions, then it is set to 102% matchrate.', '15'),
('2022-02-17', 'TRANSLATE-2832', 'bugfix', 'LanguageResources - Language filter in language resources overview is wrong', 'Language filter in language resources overview will filter for rfc values instead of language name.', '15'),
('2022-02-17', 'TRANSLATE-2828', 'bugfix', 'Editor general - Pivot language selector for zip uploads', 'Pivot language can now be set when uploading zip in the import wizard.', '15'),
('2022-02-17', 'TRANSLATE-2827', 'bugfix', 'Import/Export, Task Management - Improve workfile and pivot file matching', 'The matching between the workfile and pivot filenames is more easier right now, since the filename is compared now only to the first dot. So file.en-de.xlf matches now file.en-it.xlf and there is no need to rename such pivot files.', '15'),
('2022-02-17', 'TRANSLATE-2826', 'bugfix', 'TermPortal, TermTagger integration - processStatus is not correctly mapped by tbx import', 'processStatus is now set up correctly on processStatus-col in terms_term-table', '15'),
('2022-02-17', 'TRANSLATE-2818', 'bugfix', 'Auto-QA - AutoQA: Length-Check must Re-Evaluate also when processing Repititions', 'FIX: AutoQA now re-evaluates the length check for each segment individually when saving repititions', '15');