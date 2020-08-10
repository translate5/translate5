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

delete from Zf_configuration where name = 'runtimeOptions.termTagger.javaExec';
delete from Zf_configuration where name = 'runtimeOptions.termTagger.dir';

update Zf_configuration set description = 'Ruft den TermTagger im Debugmodus auf.' where name = 'runtimeOptions.termTagger.debug';
update Zf_configuration set description = 'Aktiviert den Fuzzy Match Modus beim taggen von Termen.' where name = 'runtimeOptions.termTagger.fuzzy';
update Zf_configuration set description = 'Fuzzy Match Wert, eine Zahl zwischen 0 und 100' where name = 'runtimeOptions.termTagger.fuzzyPercent';
update Zf_configuration set description = 'removes termTagging on diff export, because Studio sometimes seems to destroy change-history otherwhise' where name = 'runtimeOptions.termTagger.removeTaggingOnExport.diffExport';
update Zf_configuration set description = 'removes termTagging on normal export, because Studio sometimes seems to destroy change-history otherwhise' where name = 'runtimeOptions.termTagger.removeTaggingOnExport.normalExport';
update Zf_configuration set description = 'Aktiviert den Stemmer beim taggen von Termen' where name = 'runtimeOptions.termTagger.stemmed';
update Zf_configuration set description = 'Comma separated list of available TermTagger-URLs. At least one available URL must be defined. Example: ["http://localhost:9000"]' where name = 'runtimeOptions.termTagger.url.default';
update Zf_configuration set description = 'connection timeout in seconds when parsing tbx' where name = 'runtimeOptions.termTagger.timeOut.tbxParsing';
update Zf_configuration set description = 'connection timeout in seconds when tagging segments' where name = 'runtimeOptions.termTagger.timeOut.segmentTagging';

update Zf_configuration set description = 'define mid column-header for csv-file-import, all other columns are used as (alternate) translation(s)' where name = 'runtimeOptions.import.csv.fields.mid';
update Zf_configuration set description = 'define source column-header for csv-file-import, all other columns are used as (alternate) translation(s)' where name = 'runtimeOptions.import.csv.fields.source';