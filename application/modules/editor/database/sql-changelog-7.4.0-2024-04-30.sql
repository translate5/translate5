
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
            
INSERT INTO `LEK_change_log` (`dateOfChange`, `jiraNumber`, `type`, `title`, `description`, `userGroup`) VALUES ('2024-04-30', 'TRANSLATE-3853', 'feature', 'Package Ex and Re-Import - Possibility to disallow the export of translator offline packages', 'Based on a ACL, enable or disable the package export for user roles.', '15'),
('2024-04-30', 'TRANSLATE-3851', 'feature', 'MatchAnalysis & Pretranslation - Add language combination to Excel export of analysis', 'The task language codes are added in the analysis excel export.', '15'),
('2024-04-30', 'TRANSLATE-3593', 'feature', 'Auto-QA, TermTagger integration - Split \'Terminology > Not found in target\' AutoQA-category into 4 categories', '\'Terminology > Not found in target\' quality is now split into 4 sub-categories', '15'),
('2024-04-30', 'TRANSLATE-3566', 'feature', 'ConnectWorldserver - Plugin ConnectWorldServer: Use Translate5 for Pretranslation', 'Added automatic Pretranslation to existing Plugin ConnectWorldserver', '15'),
('2024-04-30', 'TRANSLATE-3206', 'feature', 'Configuration, Import/Export - Protect and auto-convert numbers and general patterns during translation', 'Numbers are protected with tags for all translations jobs. Custom patterns for number protections can be defined in separate UI.', '15'),
('2024-04-30', 'TRANSLATE-3910', 'change', 't5memory - Add log record when t5memory memory is split into pieces', 'When memory is split into pieces due to error - log record is added', '15'),
('2024-04-30', 'TRANSLATE-3857', 'change', 'Installation & Update - docker on premise: languagetool healthcheck changed', 'docker compose pull to get the latest containers. For languagetool there is now a health check which forces the languagetool to restart when either the process crashed or it does not respond on HTTP requests', '15'),
('2024-04-30', 'TRANSLATE-3856', 'change', 't5memory - Fix t5memory export if file is deleted', 't5memory migration command error output is improved to be more descriptive', '15'),
('2024-04-30', 'TRANSLATE-3843', 'change', 'VisualReview / VisualTranslation - Detected Numbered lists may not actually be numbered lists leading to faulty/shifted layouts', 'FIX Visual Reflow: Detected Numbered lists-items may not actually be numbered lists leading to broken layouts.', '15'),
('2024-04-30', 'TRANSLATE-3822', 'change', 'InstantTranslate - Add InstantTranslate-Video to help button in translate5', 'Added ability to hide InstantTranslate help button or load contents of help window from custom URL', '15'),
('2024-04-30', 'TRANSLATE-3784', 'change', 'Installation & Update - Add SMTP OAuth 2.0 integration', 'New mail transport: ZfExtended_Zend_Mail_Transport_MSGraph.
Provides possibility to send mail using MicroSoft cloud services with OAuth2 authorisation protocol.
https://confluence.translate5.net/display/CON/Installation+specific+options', '15'),
('2024-04-30', 'TRANSLATE-3774', 'change', 'LanguageResources - Content Protection: Alter Language Resource conversion state logic', 'Alter Language Resource conversion state logic to respond on rules changes', '15'),
('2024-04-30', 'TRANSLATE-3585', 'change', 'LanguageResources - Content protection: Translation Memory Conversion', 'Content protection in translation memory conversion', '15'),
('2024-04-30', 'TRANSLATE-3909', 'bugfix', 'OpenId Connect - OpenAI: set model parameters max/min', 'Fix problem where OpenAI model parameters are not settable to 0.', '15'),
('2024-04-30', 'TRANSLATE-3901', 'bugfix', 'Editor general - Add LCIDs 2816 (zh-TW), 3082 (es-ES)', 'Added additional lcids for languages.', '15'),
('2024-04-30', 'TRANSLATE-3897', 'bugfix', 'Main back-end mechanisms (Worker, Logging, etc.) - Operation workers: missing dependencies', 'Added missing worker dependency for task operation workers.', '15'),
('2024-04-30', 'TRANSLATE-3890', 'bugfix', 'Workflows - Competing assignment for complex workflow', 'When using more complex workflows as just the default workflow with competing user assignment did delete all users with the same role (translators or reviewers or second reviewers) regardless of the workflow step. Now only the users of the same workflow step as the current user are deleted.', '15'),
('2024-04-30', 'TRANSLATE-3879', 'bugfix', 'MatchAnalysis & Pretranslation - Batch result cleanup problem', 'Fix for a problem with conflicting data when multiple batch pre-translations are running at once.', '15'),
('2024-04-30', 'TRANSLATE-3878', 'bugfix', 'LanguageResources - LanguageResource specificId column is to short', 'The specificId field for languageresources was too short, cutting data for some specific LanguageResources using long language combinations.', '15'),
('2024-04-30', 'TRANSLATE-3872', 'bugfix', 'Editor general, Import/Export - Processing single tags works wrong if they are differ in source and target', 'Fixed bug which caused inappropriate single tags parsing when id of tags are not the same in source and target ', '15'),
('2024-04-30', 'TRANSLATE-3871', 'bugfix', 'Okapi integration - Fix Okapi maintenance commands', 'Fix okapi maintenance commands.', '15'),
('2024-04-30', 'TRANSLATE-3870', 'bugfix', 'InstantTranslate - InstantTranslate linebreaks', 'Fix for a problem where line breaks are not copied to clipboard.', '15'),
('2024-04-30', 'TRANSLATE-3833', 'bugfix', 'Repetition editor - repetitions of blocked segments should not be treated as repetitions', 'Blocked segments will not be evaluated as repeated segments and also not as repetition master segment.', '15'),
('2024-04-30', 'TRANSLATE-3770', 'bugfix', 'Editor general - Fix phpstan findings', 'Fix several coding problems found by static analysis.', '15'),
('2024-04-30', 'TRANSLATE-3766', 'bugfix', 'Configuration - make runtimeOptions.frontend.importTask.edit100PercentMatch config not only for UI', 'The config which enables edition of 100% matches will affect the API to.', '15'),
('2024-04-30', 'TRANSLATE-3700', 'bugfix', 'TermPortal - Term portal: help button always visible', 'Added ability to hide TermPortal help button or load contents of help window from custom URL', '15'),
('2024-04-30', 'TRANSLATE-3600', 'bugfix', 'Auto-QA - Change Qualities using the ToSort column to evaluate their contents', 'Changed qualities to use a different data column as base of evaluation, improve number-check for number protection', '15'),
('2024-04-30', 'TRANSLATE-2753', 'bugfix', 'Editor general, usability editor - Change task progress calculation by excluding blocked segments', '(B)locked segments are now excluded from progress calculation, which is now in addition divided into task overall progress and user-specific progress when user have segments range defined', '15'),
('2024-04-30', 'TRANSLATE-514', 'bugfix', 'Main back-end mechanisms (Worker, Logging, etc.) - Improve Worker garbage clean up and implement a dead worker recognition', 'Due problems with the worker system the logging of the workers had to be changed / improved. A delay for the startup of workers which could not be started was also introduced to reduce the risk of internal endless loops.', '15');