ALTER TABLE `ERP_purchaseOrder` 
ADD COLUMN `deliveryDate` DATETIME NULL AFTER `orderStatus`,
ADD COLUMN `wordsCount` INT(11) NULL AFTER `deliveryDate`,
ADD COLUMN `wordsDescription` VARCHAR(100) NULL AFTER `wordsCount`,
ADD COLUMN `hoursCount` INT(11) NULL AFTER `wordsDescription`,
ADD COLUMN `hoursDescription` VARCHAR(100) NULL AFTER `hoursCount`,
ADD COLUMN `additionalCount` INT(11) NULL AFTER `hoursDescription`,
ADD COLUMN `additionalDescription` VARCHAR(100) NULL AFTER `additionalCount`,
ADD COLUMN `additionalUnit` VARCHAR(45) NULL AFTER `additionalDescription`,
ADD COLUMN `additionalPrice` INT(11) NULL AFTER `additionalUnit`,
ADD COLUMN `transmissionPath` INT(11) NULL AFTER `additionalPrice`,
ADD COLUMN `additionalInfo` TINYTEXT NULL AFTER `transmissionPath`;
