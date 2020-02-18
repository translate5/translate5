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

-- TODO: "The installer should query the admin, if he wants to
-- - enable theRootCause in his translate5 instance (use existing Zf_configuration flag)
-- - enable video recording of bugs in his translate5 instance (create new Zf_configuration flag)
-- The question should appear one time and if answered not again. But also for updated installations 
-- it should appear one time, after new improvement is added to the installer. (...)

INSERT INTO `Zf_configuration` (`name`, `confirmed`, `module`, `category`, `value`, `default`, `defaults`, `type`, `description`) 
VALUES ('runtimeOptions.debug.enableJsLoggerVideo', '1', 'editor', 'logging', '0', '0', '', 'boolean', 'If enabled, the user can allow the JS-frontend-logger to record a video of the segment-editing during the user session. The video is recorded by a third party web application and only stored if an error occurs while editing. Only MittagQI has access to the videos so far. If you want also access to these videos or want to further customize the logger, please contact support@translate5.net');
