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

UPDATE `Zf_configuration` SET `default` =  'modules/editor/ThirdParty/Open_Sans/OpenSans-Regular.ttf' WHERE  `Zf_configuration`.`name` ='runtimeOptions.imageTag.fontFilePath';
UPDATE `Zf_configuration` SET `description` =  'must be true type font - relative path to application folder' WHERE  `Zf_configuration`.`name` ='runtimeOptions.imageTag.fontFilePath';
UPDATE `Zf_configuration` SET `default` =  '0' WHERE  `Zf_configuration`.`name` ='runtimeOptions.termTagger.fuzzy';
UPDATE `Zf_configuration` SET `default` = "9" where name = "runtimeOptions.editor.columns.widthFactorHeader";

UPDATE  `Zf_configuration` SET  `default` = '["editor_Plugins_NoMissingTargetTerminology_Bootstrap","editor_Plugins_Transit_Bootstrap","editor_Plugins_SegmentStatistics_Bootstrap","editor_Plugins_TermTagger_Bootstrap"]' WHERE  `Zf_configuration`.`name` ="runtimeOptions.plugins.active";

UPDATE  `Zf_configuration` SET  `default` =  '["http://localhost:9001","http://localhost:9002"]' WHERE  `Zf_configuration`.`name` ="runtimeOptions.termTagger.url.default";
-- UPDATE  `Zf_configuration` SET  `value` =  '["http://localhost:9001","http://localhost:9002"]' WHERE  `Zf_configuration`.`name` ="runtimeOptions.termTagger.url.default";
UPDATE  `Zf_configuration` SET  `default` =  '["http://localhost:9001","http://localhost:9002"]' WHERE  `Zf_configuration`.`name` ="runtimeOptions.termTagger.url.import";
-- UPDATE  `Zf_configuration` SET  `value` =  '["http://localhost:9001","http://localhost:9002"]' WHERE  `Zf_configuration`.`name` ="runtimeOptions.termTagger.url.import";
UPDATE  `Zf_configuration` SET  `default` =  '["http://localhost:9003"]' WHERE  `Zf_configuration`.`name` ="runtimeOptions.termTagger.url.gui";
-- UPDATE  `Zf_configuration` SET  `value` =  '["http://localhost:9003"]' WHERE  `Zf_configuration`.`name` ="runtimeOptions.termTagger.url.gui";

UPDATE  `Zf_configuration` SET  `default` =  '50' WHERE  `Zf_configuration`.`name` ="runtimeOptions.termTagger.segmentsPerCall";

-- UPDATE  `Zf_configuration` SET  `value` =  '50' WHERE  `Zf_configuration`.`name` ="runtimeOptions.termTagger.segmentsPerCall";

UPDATE  `Zf_configuration` SET  `default` =  '1000' WHERE  `Zf_configuration`.`name` ="runtimeOptions.termTagger.tbxParsing";

-- UPDATE  `Zf_configuration` SET  `value` =  '1000' WHERE  `Zf_configuration`.`name` ="runtimeOptions.termTagger.tbxParsing";

UPDATE  `Zf_configuration` SET  `default` =  '1000' WHERE  `Zf_configuration`.`name` ="runtimeOptions.termTagger.segmentTagging";

-- UPDATE  `Zf_configuration` SET  `value` =  '1000' WHERE  `Zf_configuration`.`name` ="runtimeOptions.termTagger.segmentTagging";
