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
--  translate5 plug-ins that are distributed under GNU AFFERO GENERAL PUBLIC LICENSE version 3:
--  Please see http://www.translate5.net/plugin-exception.txt or plugin-exception.txt in the root
--  folder of translate5.
--   
--  @copyright  Marc Mittag, MittagQI - Quality Informatics
--  @author     MittagQI - Quality Informatics
--  @license    GNU AFFERO GENERAL PUBLIC LICENSE version 3 with plugin-execption
-- 			 http://www.gnu.org/licenses/agpl.html http://www.translate5.net/plugin-exception.txt
-- 
-- END LICENSE AND COPYRIGHT
-- */


INSERT INTO `Zf_acl_rules` (`module`, `role`, `resource`, `right`) 
VALUES ('editor', 'editor', 'frontend', 'pluginPangeaMt'),
('editor', 'admin', 'frontend', 'pluginPangeaMt'),
('editor', 'pm', 'frontend', 'pluginPangeaMt');

INSERT INTO `Zf_configuration` (`name`, `confirmed`, `module`, `category`, `value`, `default`, `defaults`, `type`, `description`) VALUES
('runtimeOptions.plugins.PangeaMt.server', 1, 'editor', 'plugins', '["http://prod.pangeamt.com:8080"]', '[]', '', 'list', 'PangeaMT Api Server; format: ["SCHEME://HOST:PORT"]'),
('runtimeOptions.plugins.PangeaMt.apikey', 1, 'editor', 'plugins', '', '(put your api key here)', '', 'string', 'The apikey as given from PangeaMT'),
('runtimeOptions.plugins.PangeaMt.matchrate', '1', 'editor', 'editor', '70', '70', '', 'integer', 'PangeaMT penalty value, used as default matchrate since in MT no matchrate is available');

-- add the plugin into an existing plugin config
UPDATE  `Zf_configuration`
SET  `value` = REPLACE(`value`, '"]', '","editor_Plugins_PangeaMt_Init"]')
WHERE  `Zf_configuration`.`name` ="runtimeOptions.plugins.active" and not `Zf_configuration`.`value` like '%editor_Plugins_PangeaMt_Init%';


