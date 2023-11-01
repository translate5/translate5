-- /*
-- START LICENSE AND COPYRIGHT
--
--  This file is part of translate5
--
--  Copyright (c) 2013 - '.(date('Y')).' Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.
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

-- Get id of most recent Zf_errorlog-record
SET @lastPostedEventId = (
    SELECT IFNULL(`AUTO_INCREMENT`-1, 0)
    FROM `information_schema`.`TABLES`
    WHERE `TABLE_SCHEMA` = DATABASE() AND `TABLE_NAME` = 'Zf_errorlog'
);

INSERT IGNORE INTO `Zf_configuration`
(`name`, `confirmed`, `module`, `category`, `value`, `default`, `defaults`, `type`, `description`, `level`, `guiName`, `guiGroup`)
VALUES
('runtimeOptions.plugins.IndiEngine.url', '1', 'editor', 'plugins', 'https://logger.translate5.net/api/event', 'https://logger.translate5.net/api/event',
 '', 'string', 'URL endpoint of an external Indi Engine logger instance, where system log events will be posted to', '8', 'Indi Engine logger HTTP-endpoint', 'IndiEngine'),
('runtimeOptions.plugins.IndiEngine.lastPostedEventId', '1', 'editor', 'plugins', @lastPostedEventId, @lastPostedEventId, 0, 'integer',
 'This is used to distinguish between old and new events, so that only new ones are posted', '8', 'ID of last event posted to logger', 'IndiEngine'),
('runtimeOptions.plugins.IndiEngine.postingMode',1,'editor','plugins','periodical','periodical','periodical,realtime','string',
 'Periodical means events are posted in batch manner by cron job, otherwise posted once happened - each via separate POST-request'
 ,32,'How events should be posted to logger','IndiEngine'),
('runtimeOptions.plugins.IndiEngine.verifyPeer',1,'editor','plugins',0,0,'','boolean',
 'Value for CURLOPT_SSL_VERIFYPEER used while posting events, which should be 1 for production instances' ,32,'Whether peer should be verified when events to logger','IndiEngine');

