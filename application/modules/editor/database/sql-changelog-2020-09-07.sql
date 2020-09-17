
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

INSERT INTO `LEK_change_log` (`dateOfChange`, `jiraNumber`, `type`, `title`, `description`, `userGroup`) VALUES ('2020-09-07', 'TRANSLATE-1134', 'feature', 'Jump to last edited/active segment', 'The last edited/active segment is selected again on reopening a task.', '2'),
('2020-09-07', 'TRANSLATE-2111', 'feature', 'Make pop-up about "Reference files available" and "Do you really want to finish" pop-up configurable', 'Make pop-up abaout "Reference files available" and "Do you really want to finish" pop-up configurable', '8'),
('2020-09-07', 'TRANSLATE-2125', 'feature', 'Split screen for Visual Editing (sponsored by Transline)', 'In Visual Editing the original and the modified is shown in two beneath windows.', '2'),
('2020-09-07', 'TRANSLATE-2113', 'change', 'Check if translate5 runs with latest MariaDB and MySQL versions', 'It was verified that translate5 can be installed and run with latest MariaDB and MySQL versions.', '8'),
('2020-09-07', 'TRANSLATE-2122', 'change', 'Unify naming of InstantTranslate and TermPortal everywhere', 'Unify naming of InstantTranslate and TermPortal everywhere', '8'),
('2020-09-07', 'TRANSLATE-2175', 'change', 'Implement maintenance command to delete orphaned data directories', 'With the brand new ./translate5.sh CLI command several maintenance tasks can be performed. See https://confluence.translate5.net/display/CON/CLI+Maintenance+Command', '8'),
('2020-09-07', 'TRANSLATE-2189', 'change', 'Ignore segments with tags only in SDLXLIFF import if enabled', 'SDLXLIFF Import: If a segment contains only tags it is ignored from import. This is the default behaviour in native XLF import.', '4'),
('2020-09-07', 'TRANSLATE-2025', 'change', 'Change default for runtimeOptions.segments.userCanIgnoreTagValidation to 0', 'Tag errors can now not ignored anymore on saving a segment. ', '14'),
('2020-09-07', 'TRANSLATE-2163', 'change', 'Enhance documentation of Across termExport for translate5s termImport Plug-in', 'Enhance documentation of Across termExport for translate5s termImport Plug-in', '8'),
('2020-09-07', 'TRANSLATE-2165', 'change', 'Make language resource timeout for PangeaMT configurable', 'Make language resource timeout for PangeaMT configurable', '8'),
('2020-09-07', 'TRANSLATE-2179', 'change', 'Support of PHP 7.4 for translate5', 'Support of PHP 7.4 for translate5', '8'),
('2020-09-07', 'TRANSLATE-2182', 'change', 'Change default colors for Matchrate Colorization in the VisualReview', 'Change default colors for Matchrate Colorization in the VisualReview', '14'),
('2020-09-07', 'TRANSLATE-2184', 'change', 'OpenID Authentication: User info endpoint is unreachable', 'This is fixed.', '8'),
('2020-09-07', 'TRANSLATE-2192', 'change', 'Move "leave task" button in simple mode to the upper right corner of the layout area', 'Move "leave task" button in simple mode to the upper right corner of the layout area', '2'),
('2020-09-07', 'TRANSLATE-2199', 'change', 'Support more regular expressions in segment search', 'Support all regular expressions in segment search, that are possible based on MySQL 8 or MariaDB 10.2.3', '14'),
('2020-09-07', 'TRANSLATE-2002', 'bugfix', 'Translated PDF files should be named xyz.pdf.txt in the export package', 'Okapi may return translated PDF files only as txt files, so the file should be named .txt instead .pdf.', '12'),
('2020-09-07', 'TRANSLATE-2049', 'bugfix', 'ERROR in core: E9999 - Action does not exist and was not trapped in __call()', 'Sometimes the above error occurred, this is fixed now.', '8'),
('2020-09-07', 'TRANSLATE-2062', 'bugfix', 'Support html fragments as import files without changing the structure', 'This feature was erroneously disabled by a bconf change which is revoked right now.', '8'),
('2020-09-07', 'TRANSLATE-2149', 'bugfix', 'Xliff import deletes part of segment and a tag', 'In seldom circumstances XLF content was deleted on import.', '12'),
('2020-09-07', 'TRANSLATE-2157', 'bugfix', 'Company name in deadline reminder footer', 'The company name was added in the deadline reminder footer e-mail.', '8'),
('2020-09-07', 'TRANSLATE-2162', 'bugfix', 'Task can not be accessed after open randomly', 'It happend randomly, that a user was not able to access a task after opening it. The error message was: You are not authorized to access the requested data. This is fixed.', '14'),
('2020-09-07', 'TRANSLATE-2166', 'bugfix', 'Add help page for project and preferences overview', 'Add help page for project and preferences overview', '8'),
('2020-09-07', 'TRANSLATE-2167', 'bugfix', 'Save filename with a save request to NEC-TM', 'A filenames is needed for later TMX export, so one filename is generated and saved to NEC-TM.', '12'),
('2020-09-07', 'TRANSLATE-2176', 'bugfix', 'remove not race condition aware method in term import', 'A method in the term import was not thread safe.', '8'),

('2020-09-07', 'TRANSLATE-2187', 'bugfix', 'Bad performance on loading terms in segment meta panel', 'Bad performance on loading terms in segment meta panel', '14'),
('2020-09-07', 'TRANSLATE-2188', 'bugfix', 'Text in layout of xsl-generated html gets doubled', 'Text in layout of xsl-generated html gets doubled', '8'),
('2020-09-07', 'TRANSLATE-2190', 'bugfix', 'PHP ERROR in core: E9999 - Cannot refresh row as parent is missing - fixed in DbDeadLockHandling context', 'In DbDeadLockHandling it may happen that on redoing the request a needed row is gone, this is no problem so far, so this error is ignored in that case.', '8'),
('2020-09-07', 'TRANSLATE-2191', 'bugfix', 'Session Problem: Uncaught Zend_Session_Exception: Zend_Session::start()', 'Fixed this PHP error.', '8'),
('2020-09-07', 'TRANSLATE-2194', 'bugfix', 'NEC-TM not usable in InstantTranslate', 'NEC-TM not usable in InstantTranslate', '10'),
('2020-09-07', 'TRANSLATE-2198', 'bugfix', 'Correct spelling of "Ressource(n)" in German', 'Correct spelling of "Ressource(n)" in German', '8'),
('2020-09-07', 'TRANSLATE-2210', 'bugfix', 'If a task is left, it is not focused in the project overview', 'This is fixed now', '14');