
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
            
INSERT INTO `LEK_change_log` (`dateOfChange`, `jiraNumber`, `type`, `title`, `description`, `userGroup`) VALUES ('2024-07-11', 'TRANSLATE-3850', 'feature', 'OpenId Connect - SSO via OpenID: Define if IDP should be able to remove rights', 'translate5 - 7.6.6: Customer will be updated or not based on this config
translate5 - 7.6.0: New client flag added for OpenId configured IDP. It can enable or disable updating of user roles, gender and locale from the IDP user claims.', '15'),
('2024-07-11', 'TRANSLATE-3494', 'feature', 'TermPortal - Check for duplicates in same language when saving new term', 'Confirmation prompt is now shown on attempt to add a target term (via text selection within opened segment editor) that already exists in the destination TermCollection', '15'),
('2024-07-11', 'TRANSLATE-4070', 'change', 'ConnectWorldserver - Plugin Connect WorldServer: disable TM-Update on re-import', 'Worldserver TM will not be updated on tasks re-import.', '15'),
('2024-07-11', 'TRANSLATE-4069', 'change', 't5memory - Add comparing sent and received data during update request to t5memory', 'When updating the segment it is now checked if the received data equals what we expect', '15'),
('2024-07-11', 'TRANSLATE-4065', 'change', 'MatchAnalysis & Pretranslation - Use empty TM for internal fuzzy', 'Use empty TM to save internal fuzzy results instead cloning the current one
', '15'),
('2024-07-11', 'TRANSLATE-3975', 'change', 't5memory - Improve concordance search tags recognition', 'Tags recognition in concordance search panel changed to reflect actual tags ordering', '15'),
('2024-07-11', 'TRANSLATE-4059', 'bugfix', 'Editor general - Remove character duplicates from special characters', 'Fixes duplicate buttons in special character list.', '15'),
('2024-07-11', 'TRANSLATE-4055', 'bugfix', 'LanguageResources - OpenAI Plugin: Exception not reported to the Frontend', 'FIX: Errors in TMs may not be reported to the Frontend when used within OpenAI training', '15'),
('2024-07-11', 'TRANSLATE-4053', 'bugfix', 'Import/Export - Switch to strict escaping for XML formats', 'Switch to strict escaping for all XML-based import formats (XLF, XLIFF, SDLXLIFF). This can be turned off by configuration if neccessary. Strict escaping means, that ">" generally is escaped in any textual content.', '15'),
('2024-07-11', 'TRANSLATE-4047', 'bugfix', 'Editor general - Stored SegmentGrid sort on sourceEdit column leads to errors in task without editable source', 'Fix problem where filtering and sorting with invalid column names leads to UI error.', '15'),
('2024-07-11', 'TRANSLATE-4044', 'bugfix', 'Import/Export - typo in error message for file upload', 'FIXED: small typo in VisualReview file upload error message', '15'),
('2024-07-11', 'TRANSLATE-4034', 'bugfix', 'VisualReview / VisualTranslation - Improve visual symlink creation for very rare cases of parallel access', 'Suppress error-msg for visual symlink creation in the very rare case of paralell access', '15'),
('2024-07-11', 'TRANSLATE-3997', 'bugfix', 'file format settings - segmentation improvements in default srx', 'FIX: File Format Settings: Rule to break after a Colon followed by a Uppercase word worked only in German in the translate5 default SRX', '15'),
('2024-07-11', 'TRANSLATE-3991', 'bugfix', 'Task Management - FIX Table Archiever, FIX worker-trigger "Process" for Tests and reduce warnings', 'FIX: Table Archiever may ran into errors when plugins not installed/active', '15'),
('2024-07-11', 'TRANSLATE-3720', 'bugfix', 'TermPortal, usability termportal - Enhance termportal attribute display usability', 'Improve term portal UI for attribute', '15'),
('2024-07-11', 'TRANSLATE-2979', 'bugfix', 'LanguageResources - Concordance search highlighting may destroy rendered tags.', 'FIX When using the concordance search the content of tags can also be searched - not leading to defect tags in the rendered output anymore.', '15');