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

INSERT INTO `Zf_configuration` (`name`, `confirmed`, `module`, `category`, `value`, `default`, `defaults`, `type`, `description`) 
VALUES ('runtimeOptions.editor.notification.includeDiff', 1, 'editor', 'system', 1, 1, '', 'boolean', 'defines if the generated xml should also contain an alt trans field with a diff like content of the segment.');

-- Fix meaningless germen descriptions
UPDATE `Zf_configuration` SET `description` = 'Enables the TermTagger to be verbose' WHERE `name` = 'runtimeOptions.termTagger.debug';
UPDATE `Zf_configuration` SET `description` = 'Enables the fuzzy mode' WHERE `name` = 'runtimeOptions.termTagger.fuzzy';
UPDATE `Zf_configuration` SET `description` = 'The fuzzy percentage as integer, from 0 to 100' WHERE `name` = 'runtimeOptions.termTagger.fuzzyPercent';
UPDATE `Zf_configuration` SET `description` = 'Enables the stemmer' WHERE `name` = 'runtimeOptions.termTagger.stemmed';

-- remove removeTaggingOnExport settings
DELETE FROM `Zf_configuration` WHERE `name` = 'runtimeOptions.termTagger.removeTaggingOnExport.diffExport';
DELETE FROM `Zf_configuration` WHERE `name` = 'runtimeOptions.termTagger.removeTaggingOnExport.normalExport';

-- insert therefore negated exportTermTags settings
INSERT INTO `Zf_configuration` (`name`, `confirmed`, `module`, `category`, `value`, `default`, `defaults`, `type`, `description`) VALUES
('runtimeOptions.termTagger.exportTermTags.diffExport', 1, 'editor','termtagger',0,0,'','boolean', 'Should not be used in productive use, exporting the term information is for localisations research engineers only!'),
('runtimeOptions.termTagger.exportTermTags.normalExport', 1, 'editor','termtagger',0,0,'','boolean', 'Should not be used in productive use, exporting the term information is for localisations research engineers only!');

-- delete old unused settings
DELETE FROM `Zf_configuration` WHERE `name` = 'runtimeOptions.termTagger.dir';
DELETE FROM `Zf_configuration` WHERE `name` = 'runtimeOptions.termTagger.javaExec';