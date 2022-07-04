
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
            
INSERT INTO `LEK_change_log` (`dateOfChange`, `jiraNumber`, `type`, `title`, `description`, `userGroup`) VALUES ('2022-03-30', 'TRANSLATE-2697', 'feature', 'VisualReview / VisualTranslation - General plugin which parses a visual HTML source from a reference file', 'Added capabilities to download the visual source from an URL embedded in a reference XML file', '15'),
('2022-03-30', 'TRANSLATE-2923', 'change', 'MatchAnalysis & Pretranslation - Enable 101% Matches to be shown as <inContextExact in Trados analysis XML export', 'A matchrate of 101% may be mapped to InContextExact matches in the analysis XML export for Trados (if configured: runtimeOptions.plugins.MatchAnalysis.xmlInContextUsage)', '15'),
('2022-03-30', 'TRANSLATE-2938', 'bugfix', 'Editor general - Remove the limit from the global customer switch', 'The global customer dropdown has shown only 20 customers, now all are show.', '15'),
('2022-03-30', 'TRANSLATE-2937', 'bugfix', 'Main back-end mechanisms (Worker, Logging, etc.) - Workflow user prefs loading fails on importing task', 'Solves a problem with user preferences in importing tasks.', '15'),
('2022-03-30', 'TRANSLATE-2934', 'bugfix', 'VisualReview / VisualTranslation - Bookmark segment in visual does not work', 'The segment bookmark filter button in the simple view mode of visual review was not working, this is fixed.', '15'),
('2022-03-30', 'TRANSLATE-2930', 'bugfix', 'InstantTranslate - Instant-translate task types listed in task overview', 'Pre-translated files with instant-translate will not be listed anymore as tasks in task overview.', '15'),
('2022-03-30', 'TRANSLATE-2922', 'bugfix', 'MatchAnalysis & Pretranslation - 103%-Matches are shown in wrong category in Trados XML Export', 'A matchrate of 103% must be mapped to perfect matches in the analysis XML export for Trados (was previously mapped to InContextExact).', '15'),
('2022-03-30', 'TRANSLATE-2921', 'bugfix', 'TermPortal - Batch edit should only change all terms on affected level', 'Batch editing was internally changed, so the only selected terms and language- and termEntry- levels of selected terms are affected.', '15'),
('2022-03-30', 'TRANSLATE-2844', 'bugfix', 'Import/Export - upload wizard is blocked by zip-file as reference file', 'Disallow zip files to be uploaded as a reference file via the UI, since they can not be processed and were causing errors.', '15'),
('2022-03-30', 'TRANSLATE-2835', 'bugfix', 'OpenTM2 integration - Repair invalid OpenTM2 TMX export', 'Depending on the content in the TM the exported TMX may result in invalid XML. This is tried to be fixed as best as possible to provide valid XML.', '15'),
('2022-03-30', 'TRANSLATE-2766', 'bugfix', 'Client management - Change client sorting in drop-downs to alphabethically', 'All over the application clients in the drop-downs were sorted by the order, they have been added to the application. Now they are sorted alphabetically.', '15');