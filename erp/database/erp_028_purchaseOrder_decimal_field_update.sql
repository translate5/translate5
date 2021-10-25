ALTER TABLE `ERP_purchaseOrder` 
CHANGE COLUMN `hoursCount` `hoursCount` DECIMAL(19,4) NULL DEFAULT NULL ,
CHANGE COLUMN `additionalPrice` `additionalPrice` DECIMAL(19,4) NULL DEFAULT NULL ,
CHANGE COLUMN `additionalCount` `additionalCount` DECIMAL(19,4) NULL DEFAULT NULL ;
