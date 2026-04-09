-- /*
-- START LICENSE AND COPYRIGHT
--
--  This file is part of translate5
--
--  Copyright (c) 2013 - 2026 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.
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

DROP TABLE IF EXISTS LEK_segment_history_aggregation;
DROP TABLE IF EXISTS LEK_segment_history_aggregation_lev;

CREATE TABLE IF NOT EXISTS LEK_statistics_postediting_aggregation
(
    taskGuid            varchar(36)        not null,
    segmentId           int unsigned       not null,
    userGuid            varchar(36)        not null,
    workflowStepName    varchar(60)        not null,
    duration            int unsigned       not null,
    PRIMARY KEY (taskGuid,segmentId,workflowStepName,userGuid)
) ENGINE=INNODB CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS LEK_statistics_segment_aggregation
(
    taskGuid              varchar(36)        not null,
    userGuid              varchar(36)        not null,
    workflowName          varchar(100)       not null,
    workflowStepName      varchar(60)        not null,
    segmentId             int unsigned       not null,
    editable              tinyint unsigned   not null,
    latestEntry           tinyint unsigned   not null DEFAULT 0,
    levenshteinOriginal   mediumint unsigned not null,
    levenshteinPrevious   mediumint unsigned not null,
    segmentlengthPrevious mediumint unsigned not null DEFAULT 0,
    matchRate             tinyint unsigned   not null,
    qualityScore          tinyint unsigned   not null,
    langResType           varchar(60)        not null,
    langResId             mediumint unsigned not null,
    PRIMARY KEY (taskGuid,segmentId,workflowStepName)
) ENGINE=INNODB CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `LEK_segment_statistics` (
    `taskGuid` VARCHAR(38) NOT NULL,
    `segmentId` INT NOT NULL,
    `historyId` INT NOT NULL DEFAULT 0,
    `levenshteinOriginal` MEDIUMINT UNSIGNED NOT NULL DEFAULT 0,
    `levenshteinPrevious` MEDIUMINT UNSIGNED NOT NULL DEFAULT 0,
    `segmentlengthPrevious` MEDIUMINT UNSIGNED NOT NULL DEFAULT 0,
    UNIQUE KEY `uq_LEK_segment_statistics_segment_history` (`segmentId`, `historyId`),
    KEY `idx_LEK_segment_statistics_task_segment` (`taskGuid`, `segmentId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `LEK_segments`
    DROP COLUMN IF EXISTS `levenshteinOriginal`,
    DROP COLUMN IF EXISTS `levenshteinPrevious`;

ALTER TABLE `LEK_segment_history`
    DROP COLUMN IF EXISTS `levenshteinOriginal`,
    DROP COLUMN IF EXISTS `levenshteinPrevious`;

CREATE TABLE IF NOT EXISTS `LEK_task_workflow_log` (
   `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
   `taskGuid` VARCHAR(38) NOT NULL,
   `workflowName` VARCHAR(60) NOT NULL,
   `workflowStepName` VARCHAR(60) NOT NULL,
   `userGuid` VARCHAR(38) NOT NULL,
   `created` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
   PRIMARY KEY (`id`),
   INDEX `idx_t5355_workflow_log_task_id` (`taskGuid`, `id`),
   INDEX `idx_t5355_workflow_log_task_created` (`taskGuid`, `created`, `id`),
   INDEX `idx_t5355_workflow_log_dedup` (`taskGuid`, `created`, `workflowStepName`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;