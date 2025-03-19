-- /*
-- START LICENSE AND COPYRIGHT
--
--  This file is part of translate5
--
--  Copyright (c) 2013 - 2024 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.
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
--              http://www.gnu.org/licenses/agpl.html http://www.translate5.net/plugin-exception.txt
--
-- END LICENSE AND COPYRIGHT
-- */

CREATE TABLE IF NOT EXISTS LEK_segment_history_aggregation
(
    taskGuid            varchar(36)        not null,
    userGuid            varchar(36)        not null,
    workflowName        varchar(100)       not null,
    workflowStepName    varchar(60)        not null,
    segmentId           int unsigned       not null,
    editable            tinyint unsigned   not null,
    duration            int unsigned       not null,
    matchRate           tinyint unsigned   not null,
    langResType         varchar(60)        not null,
    langResId           mediumint unsigned not null,
    PRIMARY KEY (taskGuid,segmentId,workflowStepName,userGuid)
) ENGINE=INNODB CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS LEK_segment_history_aggregation_lev
(
    taskGuid            varchar(36)        not null,
    userGuid            varchar(36)        not null,
    workflowName        varchar(100)       not null,
    workflowStepName    varchar(60)        not null,
    segmentId           int unsigned       not null,
    editable            tinyint unsigned   not null,
    lastEdit            tinyint unsigned   not null,
    levenshteinOriginal mediumint unsigned not null,
    levenshteinPrevious mediumint unsigned not null,
    matchRate           tinyint unsigned   not null,
    langResType         varchar(60)        not null,
    langResId           mediumint unsigned not null,
    PRIMARY KEY (taskGuid,segmentId,workflowStepName)
) ENGINE=INNODB CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS LEK_key_value_data
(
    id         varchar(100)       not null,
    value      varchar(255)       not null,
    PRIMARY KEY (id)
) ENGINE=INNODB CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
