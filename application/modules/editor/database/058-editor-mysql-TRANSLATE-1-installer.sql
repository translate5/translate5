--  /*
--  START LICENSE AND COPYRIGHT
--  
--  This file is part of Translate5 Editor PHP Serverside and build on Zend Framework
--  
--  Copyright (c) 2014 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.
-- 
--  Contact:  http://www.MittagQI.com/  /  service (ÄTT) MittagQI.com
-- 
--  This file may be used under the terms of the GNU General Public License version 3.0
--  as published by the Free Software Foundation and appearing in the file gpl3-license.txt 
--  included in the packaging of this file.  Please review the following information 
--  to ensure the GNU General Public License version 3.0 requirements will be met:
--  http://www.gnu.org/copyleft/gpl.html.
-- 
--  For this file you are allowed to make use of the same FLOSS exceptions to the GNU 
--  General Public License version 3.0 as specified by Sencha for Ext Js. 
--  Please be aware, that Marc Mittag / MittagQI take no warranty  for any legal issue, 
--  that may arise, if you use these FLOSS exceptions and recommend  to stick to GPL 3. 
--  For further information regarding this topic please see the attached license.txt
--  of this software package.
--  
--  MittagQI would be open to release translate5 under EPL or LGPL also, if this could be
--  brought in accordance with the ExtJs license scheme. You are welcome to support us
--  with legal support, if you are interested in this.
--  
--  
--  @copyright  Marc Mittag, MittagQI - Quality Informatics
--  @author     MittagQI - Quality Informatics
--  @license    GNU General Public License version 3.0 http://www.gnu.org/copyleft/gpl.html
--              with FLOSS exceptions (see floss-exception.txt and ux-exception.txt at the root level)
--  
--  END LICENSE AND COPYRIGHT 
--  */
-- 
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
