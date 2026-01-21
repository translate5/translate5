-- /*
-- START LICENSE AND COPYRIGHT
--
--  This file is part of translate5
--
--  Copyright (c) 2013 - 2024 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.
--
--  Contact:  http://www.MittagQI.com/  /  service (ATT) MittagQI.com
--
--  This file may be used under the terms of the GNU GENERAL PUBLIC LICENSE version 3
--  as published by the Free Software Foundation and appearing in the file gpl3-license.txt
--  included in the packaging of this file.  Please review the following information
--  to ensure the GNU GENERAL PUBLIC LICENSE version 3 requirements will be met:
--  http://www.gnu.org/licenses/gpl.html
--
--  @copyright  Marc Mittag, MittagQI - Quality Informatics
--  @author     MittagQI - Quality Informatics
--  @license    GNU GENERAL PUBLIC LICENSE version 3
--              http://www.gnu.org/licenses/gpl.html
--
-- END LICENSE AND COPYRIGHT
-- */

ALTER TABLE `LEK_task_meta` DROP FOREIGN KEY `fk-task_meta-tqe_pricing_preset`;
ALTER TABLE `LEK_task_meta` DROP COLUMN `tqePricingPresetId`;

ALTER TABLE `LEK_customer_meta` DROP FOREIGN KEY `fk-customer_meta-tqe_pricing_preset`;
ALTER TABLE `LEK_customer_meta` DROP COLUMN `defaultTqePricingPresetId`;

ALTER TABLE `match_analysis_pricing_preset` DROP COLUMN `isTqeDefault`;
