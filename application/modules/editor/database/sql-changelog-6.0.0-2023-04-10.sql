
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
            
INSERT INTO `LEK_change_log` (`dateOfChange`, `jiraNumber`, `type`, `title`, `description`, `userGroup`) VALUES ('2023-04-10', 'TRANSLATE-3234', 'feature', 'API - API Improvements for Figma', 'The API endpoint for langauges (/language) now respects locale, targetLang can be sent as comma-separated array', '15'),
('2023-04-10', 'TRANSLATE-3233', 'feature', 'VisualReview / VisualTranslation - Replace visualbrowser container with our own Dockerized Headless Browser', 'VisualReview plugin text reflow and text resize code moved to a separate repository. 
Visualbrowser is replaced by translate5/visualconverter image.
Config runtimeOptions.plugins.VisualReview.dockerizedHeadlessChromeUrl is now replaced by runtimeOptions.plugins.VisualReview.visualConverterUrl', '15'),
('2023-04-10', 'TRANSLATE-3252', 'change', 'VisualReview / VisualTranslation - Add Info/Warning if Font\'s could not be parsed in a PDF based visual', 'Add info/warning for fonts that could not be properly evaluated in the conversion of a PDF as source of the visual', '15'),
('2023-04-10', 'TRANSLATE-3270', 'bugfix', 'Editor general - Several rootcause fixes', 'Fixed: Frontend error "me.editor is null" in Qualities Filter-Panel
Fixed: Frontend error "Cannot read properties of null (reading \'filter\')" in Qualities Filter-Panel
Fixed: Frontend error "Cannot read properties of undefined (reading \'down\')" when right-clicking segments', '15'),
('2023-04-10', 'TRANSLATE-3268', 'bugfix', 'Main back-end mechanisms (Worker, Logging, etc.) - Automatic system log purge to a configurable amount of weeks in the past', 'To reduce DB load the translate5 system log is now purged to 6 weeks in the past each night.', '15'),
('2023-04-10', 'TRANSLATE-3265', 'bugfix', 'Import/Export - Folder evaluated as file in zip data provider', 'Fix a problem with Zip archive content validator.', '15'),
('2023-04-10', 'TRANSLATE-3260', 'bugfix', 'TrackChanges - Disable TrackChanges for ja, ko, zh, vi completely to fix char input problems', 'Added option to completely disable TrackChanges per language (\'ko\', \'ja\', ...) to solve problems with character input in these languages
- FIX config-level for deactivating target languages', '15'),
('2023-04-10', 'TRANSLATE-3259', 'bugfix', 'MatchAnalysis & Pretranslation - Pivot pre-translation is not paused while tm is importing', 'Pivot worker now has the pause mechanism which waits until all related t5memory language resources are available.
This will work properly only with t5memory version greater then 0.4.36', '15'),
('2023-04-10', 'TRANSLATE-3258', 'bugfix', 'file format settings - T5 Segmentation Rules: Add rules for  "z. B." in parallel with "z.B."', 'Added Segmentation rules to not break after "z. B." just like with "z.B."', '15'),
('2023-04-10', 'TRANSLATE-3058', 'bugfix', 'Main back-end mechanisms (Worker, Logging, etc.), SpellCheck (LanguageTool integration), TermTagger integration - Simplify termtagger and spellcheck workers', 'Improvement: TermTagger Worker & SpellCheck Worker are not queued dynamically anymore but according to the configured slots & looping through segments. This reduces deadlocks & limits processes ', '15'),
('2023-04-10', 'TRANSLATE-3048', 'bugfix', 'Editor general - CSRF Protection for translate5', 'CSRF (Cross Site Request Forgery) Protection for translate5 with a CSRF-token. Important info for translate5 API users: externally the translate5 - API can only be accessed with an App-Token from now on.', '15'),
('2023-04-10', 'TRANSLATE-2592', 'bugfix', 'TrackChanges - Reduce and by default hide use of TrackChanges in the translation step', 'Regarding translation and track changes: changes are only recorded for pre-translated segments and changes are hidden by default for translators (and can be activated by the user in the view modes drop-down of the editor)

', '15');