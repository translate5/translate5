ALTER TABLE `ERP_purchaseOrder` 
ADD COLUMN `bookingYear` INT(11) NULL DEFAULT NULL AFTER `modifiedDate`,
ADD COLUMN `bookingMonth` INT(11) NULL DEFAULT NULL AFTER `bookingYear`;