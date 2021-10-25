ALTER TABLE `ERP_order` 
ADD COLUMN `performanceDate` DATETIME NULL DEFAULT NULL AFTER `poCount`,
ADD COLUMN `plannedDeliveryDate` DATETIME NULL DEFAULT NULL AFTER `performanceDate`;

