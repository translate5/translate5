
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
            
INSERT INTO `LEK_change_log` (`dateOfChange`, `jiraNumber`, `type`, `title`, `description`, `userGroup`) VALUES ('2020-10-21', 'TRANSLATE-2279', 'change', 'Integrate git hook checks', 'Development: Integrate git hooks to validate source code.', '8'),
('2020-10-21', 'TRANSLATE-2282', 'bugfix', 'Mixing XLF id and rid values led to wrong tag numbering', 'When in some paired XLF tags the rid was used, and in others the id to pair the tags, this could lead to duplicated tag numbers.', '6'),
('2020-10-21', 'TRANSLATE-2280', 'bugfix', 'OpenTM2 is not reachable anymore if TMPrefix configuration is empty', 'OpenTM2 installations were not reachable anymore from the application if the tmprefix was not configured. Empty tmprefixes are valid again.', '8'),
('2020-10-21', 'TRANSLATE-2278', 'bugfix', 'Check if the searched text is valid for segmentation', 'Text segmentation and text segmentation search in instant-translate only will be done only when for the current search TM is available or risk-predictor (ModelFront) is enabled.', '2'),
('2020-10-21', 'TRANSLATE-2277', 'bugfix', 'UserConfig value does not respect config data type', 'The UserConfig values did not respect the underlying configs data type, therefore the preferences of the repetition editor were not loaded correctly and the repetition editor did not come up.', '6'),
('2020-10-21', 'TRANSLATE-2265', 'bugfix', 'Microsoft translator directory lookup change', 'Solves the problem that microsoft translator does not provide results when searching text in instant translate with more then 5 characters.', '15'),
('2020-10-21', 'TRANSLATE-2264', 'bugfix', 'Relative links for instant-translate file download', 'Fixed file file download link in instant translate when the user is accessing translate5 from different domain.', '12'),
('2020-10-21', 'TRANSLATE-2263', 'bugfix', 'Do not use ExtJS debug anymore', 'Instead of using the debug version of ExtJS now the normal one is used. This reduces the initial load from 10 to 2MB.', '8'),
('2020-10-21', 'TRANSLATE-2262', 'bugfix', 'Remove sensitive data of API endpoint task/userlist', 'The userlst needed for filtering in the task management exposes the encrypted password.', '8'),
('2020-10-21', 'TRANSLATE-2261', 'bugfix', 'Improve terminology import performance', 'The import performance of large terminology was really slow, by adding some databases indexes the imported was boosted. ', '12'),
('2020-10-21', 'TRANSLATE-2260', 'bugfix', 'Visual Review: Normalizing whitespace when comparing segments for content-align / pivot-language', 'Whitespace will now be normalized when aligned visuals in the visual review or pivot languages are validated against the segments ', '12'),
('2020-10-21', 'TRANSLATE-2252', 'bugfix', 'Reapply tooltip over processing status column', 'The tool-tips were changed accidentally and are restored now.', '6'),
('2020-10-21', 'TRANSLATE-2251', 'bugfix', 'Reapply "Red bubble" to changed segments in left side layout of split screen', 'The red bubble representing edited segments will now also show in the left (unedited) frame of the split-view of the visual review', '6'),
('2020-10-21', 'TRANSLATE-2250', 'bugfix', 'Also allow uploading HTML for VisualReview', 'Since it is possible to put HTML files as layout source in the visual folder of the zip import package, selecting an HTML file in the GUI should be allowed, too.', '12'),
('2020-10-21', 'TRANSLATE-2245', 'bugfix', 'Switch analysis to batch mode, where language resources support it', 'Sending multiple segment per request when match analysis and pre-translation is running now can be configured in (default enabled): runtimeOptions.plugins.MatchAnalysis.enableBatchQuery; Currently this is supported by the following language resources: Nectm, PangeaMt, Microsoft, Google, DeepL', '12'),
('2020-10-21', 'TRANSLATE-2220', 'bugfix', 'XML/XSLT import for visual review: Filenames may not be suitable for OKAPI processing', 'FIX: Any filenames e.g. like "File (Kopie)" now can be processed, either as aligned XML/XSLT file or with a direct XML/XSL import ', '12');
