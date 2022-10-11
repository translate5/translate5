
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
            
INSERT INTO `LEK_change_log` (`dateOfChange`, `jiraNumber`, `type`, `title`, `description`, `userGroup`) VALUES ('2022-10-11', 'TRANSLATE-3067', 'feature', 'LanguageResources - Export glossary', 'Export of a DeepL glossary in language resources overview.', '15'),
('2022-10-11', 'TRANSLATE-3016', 'feature', 'Configuration, Editor general, TermTagger integration - Show and use only terms of a certain process level in the editor', 'UPDATE: the defined term process status list will also be applied when creating new deepl glossary
Only the terms with a defined process status are used for term tagging and listed in the editor term-portlet. The configuration is runtimeOptions.termTagger.usedTermProcessStatus. ', '15'),
('2022-10-11', 'TRANSLATE-2561', 'feature', 'Repetition editor - Enhancement of repetition editor', 'Added two new configs for automatic repetitions processing: "Repetition type" -radio buttons (source, target, source and target, source or target) and "Same content only"-checkbox', '15'),
('2022-10-11', 'TRANSLATE-3071', 'change', 'Import/Export - Enable XLF namespace registration from plug-ins', 'Plug-ins can now register custom XLIFF namespace handlers to enable import of proprietary XLIFF dialects.', '15'),
('2022-10-11', 'TRANSLATE-3066', 'change', 'TBX-Import - Trim leading/trailing whitespaces from terms on import', 'leading/trailing whitespaces are now trimmed from terms on import', '15'),
('2022-10-11', 'TRANSLATE-3068', 'bugfix', 'MatchAnalysis & Pretranslation - Fix repetition behaviour in pre-translation with MT only', 'On pre-translations with MTs only, repeated segments may get the wrong tags and produce therefore tag errors, especially a problem for instant translating files.', '15'),
('2022-10-11', 'TRANSLATE-3049', 'bugfix', 'SpellCheck (LanguageTool integration) - Empty segments are send to SpellCheck on import', 'Empty segments are not sent for spellchecking to LanguageTool anymore', '15'),
('2022-10-11', 'TRANSLATE-3044', 'bugfix', 'TermTagger integration - Cache terminology data on RecalcTransFound', 'Translation status assignment rewritten for terms tagged by TermTagger', '15'),
('2022-10-11', 'TRANSLATE-283', 'bugfix', 'Editor general - XSS Protection in translate5', 'ENHANCEMENT: Added general protection against CrossSiteScripting/XSS attacks', '15');