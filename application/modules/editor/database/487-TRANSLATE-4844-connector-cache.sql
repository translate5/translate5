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



INSERT INTO `Zf_configuration` (`name`, `confirmed`, `module`, `category`, `value`, `default`, `defaults`, `type`,
                                `description`, `level`, `guiName`, `guiGroup`, `comment`)
VALUES ('runtimeOptions.LanguageResources.cache.redisUrl', '1', 'editor', 'system',
        '', '', '', 'url',
        'URL to the optional redis server for query caching. Caching is disabled if empty, which is the default at the moment. Url should look like tcp://redis.:6379 where port is optional if default is used.',
        1,'Query Cache URL', 'Language resources', ''),
       ('runtimeOptions.LanguageResources.cache.timeToLive', '1', 'editor', 'system',
        '60', '60', '', 'integer',
        'Time to life of the cache entries in minutes',
        1,'Query Cache Time to life', 'Language resources', '');