
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
--              http://www.gnu.org/licenses/agpl.html http://www.translate5.net/plugin-exception.txt
--
-- END LICENSE AND COPYRIGHT
-- */

-- userGroup calculation: basic: 1; editor: 2; pm: 4; admin: 8
            
INSERT INTO `LEK_change_log` (`dateOfChange`, `jiraNumber`, `type`, `title`, `description`, `userGroup`) VALUES ('2025-08-08', 'TRANSLATE-4822', 'feature', 't5memory - Save multiple target for the same source to t5memory', 'Added config which purpose is to force t5memory to store segments with the same source as duplicates', '15'),
('2025-08-08', 'TRANSLATE-4231', 'feature', 'VisualReview / VisualTranslation - Offer WYSIWYG side of the visual as PDF download', 'Visual: Add capabilities to export the visual as PDF for most visual types (Not Video obviously)', '15'),
('2025-08-08', 'TRANSLATE-4861', 'change', 't5memory - Add t5memory error code 5017 to list of codes, that triggers reorganize', 'Added error 5017 to the errors list that are supposed to trigger t5memory memory reorganize process', '15'),
('2025-08-08', 'TRANSLATE-4841', 'change', 'Workflows - Print approval: add size limit check for PDF attachment', 'Print approval: added size limit check for PDF attachment', '15'),
('2025-08-08', 'TRANSLATE-4743', 'bugfix', 'MatchAnalysis & Pretranslation - Omit versioning strategy for t5memory API', 'ONLY FOR ON PREMISE USERS:
!!! For on-premise useres: BACK COMPATIBILITY BREAK !!!
End of support of t5memory older then version 0.6.x. Please make sure, you pulled t5memory "latest" in your docker setup (what you should do anyway with each update). If you still should be running TMs with 0.4 version, you need to migrate them with the "t5 t5memory:migrate" command. Get in touch with translate5 support in this case.', '15');