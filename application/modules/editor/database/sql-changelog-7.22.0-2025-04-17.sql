
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
            
INSERT INTO `LEK_change_log` (`dateOfChange`, `jiraNumber`, `type`, `title`, `description`, `userGroup`) VALUES ('2025-04-17', 'TRANSLATE-4506', 'feature', 'Workflows - Workflow action to create Workflow Jobs', 'Introduction of a in workflow triggerable action to create additional jobs for tasks.', '15'),
('2025-04-17', 'TRANSLATE-4489', 'feature', 'Task Management - show task deadline in task overview', 'Added new column "Task deadline" in the task overview', '15'),
('2025-04-17', 'TRANSLATE-3785', 'feature', 'VisualReview / VisualTranslation - Exchange Visual PDF in ongoing project', 'Visual: Added Capabilities to exchange the visual or to import it, after the task was imported. The visual can be provided as individual files or with a ZIP having the exact same file-layout as used in task-import. A video-visual or a XML/XSLT based visual can not be exchanged or imported at a later point.', '15'),
('2025-04-17', 'TRANSLATE-3565', 'feature', 'InstantTranslate - Send to human revision button for instant translate', 'New functionality in InstantTranslate - Send to human revision button. By pressing the button a task is created out of file translation for revision by translators, reviewrs etc. ', '15'),
('2025-04-17', 'TRANSLATE-4601', 'change', 'VisualReview / VisualTranslation - Improve Batch PDF Exchange Frontend', 'Improve Frontend of PDF batch-exchange', '15'),
('2025-04-17', 'TRANSLATE-4538', 'change', 'AI, openai - Make fine tuning in Azure cloud work', 'OpenAI model are now tunable in Azure cloud
In case Azure cloud is used in OpenAI plugin need to add more configs. Please read the corresponding doc page for details https://confluence.translate5.net/display/BUS/Azure', '15'),
('2025-04-17', 'TRANSLATE-4518', 'change', 'VisualReview / VisualTranslation - Visual Webpage Download: Get rid of browserless', 'Visual: Get rid of browserless in the visual container to be able to catch JS-Errors', '15'),
('2025-04-17', 'TRANSLATE-4513', 'change', 'TermTagger integration - Send TBX ID as part of the URL to the termTagger', 'TBX ID is sent as part of the request/header to the termTagger', '15'),
('2025-04-17', 'TRANSLATE-4511', 'change', 'Workflows - Add custom dialogs on task finishing', 'For customized workflows the possibility to add custom actions on workflow finish was added', '15'),
('2025-04-17', 'TRANSLATE-4510', 'change', 'VisualReview / VisualTranslation - Implement a review visual view', 'For visual related workflow a new view mode review visual only is introduce, focusing the user on the visual window only.', '15'),
('2025-04-17', 'TRANSLATE-4509', 'change', 'VisualReview / VisualTranslation - Make PDF exchange batchable', 'Added Exchange Visual Review PDF interface', '15'),
('2025-04-17', 'TRANSLATE-4508', 'change', 'VisualReview / VisualTranslation, Workflows - Interact with workflow on dedicated events triggered by visual review', 'Added some new workflow interactions to modify the tasks workflow on events produced by visual review.', '15'),
('2025-04-17', 'TRANSLATE-4482', 'change', 'Import/Export - Add own Endpoint for t5-connect, add API-test for t5-connect Endpoint', 'Add API-test & own endpoints for t5connect', '15'),
('2025-04-17', 'TRANSLATE-4203', 'change', 'LanguageResources - DeepL: Switch tag-handling to be able to send tags as xliff tags', 'Switch deepl tag handler to xliff.', '15'),
('2025-04-17', 'TRANSLATE-4592', 'bugfix', 'TM Maintenance - search for (f) shows multiple segments but results in only 2', 'duplicate', '15'),
('2025-04-17', 'TRANSLATE-4543', 'bugfix', 'TermTagger integration - BUG: TermTagger produces Invalid Markup (ins/del in term-tags)', 'FIX: TermTagger may created invalid Markup in conjunction with TrackChanges', '15');