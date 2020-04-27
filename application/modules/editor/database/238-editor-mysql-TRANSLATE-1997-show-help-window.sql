
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

INSERT INTO `Zf_configuration` (`name`, `confirmed`, `module`, `category`, `value`, `default`, `defaults`, `type`, `description`, `level`) 
VALUES 
('runtimeOptions.frontend.defaultState.helpWindow.segmentsGrid', '1', 'editor', 'system', '{"doNotShowAgain":false,"loaderUrl":"\/help\/{0}"}', '', '', 'map', 'Help window default state configuration for the Segment Grid. When doNotShowAgain is set to false, the window will appear automaticly for the segment grid. When setting this config to true or leave the field value as empty, the window will not apear automaticly.', '16');

INSERT INTO `Zf_configuration` (`name`, `confirmed`, `module`, `category`, `value`, `default`, `defaults`, `type`, `description`, `level`) 
VALUES 
('runtimeOptions.frontend.defaultState.segmentsGrid', '1', 'editor', 'system', '', '', '', 'map', 'Segment grid default state configuration. When this config is empty, the task grid state will not be saved or applied. For how to config this value please visit this page: https://confluence.translate5.net/display/CON/Configure+grids+and+window+state', '16');
