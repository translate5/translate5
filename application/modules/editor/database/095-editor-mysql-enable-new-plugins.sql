-- /*
-- START LICENSE AND COPYRIGHT
-- 
--  This file is part of translate5
--  
--  Copyright (c) 2013 - 2015 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.
-- 
--  Contact:  http://www.MittagQI.com/  /  service (ATT) MittagQI.com
-- 
--  This file may be used under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE version 3
--  as published by the Free Software Foundation and appearing in the file agpl3-license.txt 
--  included in the packaging of this file.  Please review the following information 
--  to ensure the GNU AFFERO GENERAL PUBLIC LICENSE version 3.0 requirements will be met:
--  http://www.gnu.org/licenses/agpl.html
-- 
--  There is a plugin exception available for use with this release of translate5 for
--  open source applications that are distributed under a license other than AGPL:
--  Please see Open Source License Exception for Development of Plugins for translate5
--  http://www.translate5.net/plugin-exception.txt or as plugin-exception.txt in the root
--  folder of translate5.
--   
--  @copyright  Marc Mittag, MittagQI - Quality Informatics
--  @author     MittagQI - Quality Informatics
--  @license    GNU AFFERO GENERAL PUBLIC LICENSE version 3 with plugin-execptions
-- 			 http://www.gnu.org/licenses/agpl.html http://www.translate5.net/plugin-exception.txt
-- 
-- END LICENSE AND COPYRIGHT
-- */

-- add the new plugins to the default entry
UPDATE  `Zf_configuration` 
SET  `default` = '["editor_Plugins_NoMissingTargetTerminology_Bootstrap","editor_Plugins_Transit_Bootstrap","editor_Plugins_SegmentStatistics_Bootstrap","editor_Plugins_TermTagger_Bootstrap","editor_Plugins_MatchResource_Init","editor_Plugins_ChangeLog_Init"]' 
WHERE  `Zf_configuration`.`name` ="runtimeOptions.plugins.active";

-- add the new plugins into an existing plugin config
UPDATE  `Zf_configuration` 
SET  `value` = REPLACE(`value`, '"]', '","editor_Plugins_MatchResource_Init","editor_Plugins_ChangeLog_Init"]') 
WHERE  `Zf_configuration`.`name` ="runtimeOptions.plugins.active";

-- add the new plugins into an empty plugin config
UPDATE  `Zf_configuration` 
SET  `value` = '["editor_Plugins_MatchResource_Init","editor_Plugins_ChangeLog_Init"]' 
WHERE  `Zf_configuration`.`name` ="runtimeOptions.plugins.active" AND (`value` = '' OR `value` = '[]');

 
