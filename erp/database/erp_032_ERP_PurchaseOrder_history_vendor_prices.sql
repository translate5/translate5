ALTER TABLE `ERP_purchaseOrder_history` 
ADD COLUMN `perWordPrice` DECIMAL(19,4) NULL DEFAULT NULL AFTER `additionalInfo`,
ADD COLUMN `perHourPrice` DECIMAL(19,4) NULL DEFAULT NULL AFTER `perWordPrice`,
ADD COLUMN `perAdditionalUnitPrice` DECIMAL(19,4) NULL DEFAULT NULL AFTER `perHourPrice`;

