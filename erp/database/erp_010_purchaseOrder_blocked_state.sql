ALTER TABLE `ERP_purchaseOrder` 
CHANGE COLUMN `state` `state` ENUM('created', 'billed', 'paid', 'cancelled', 'blocked') NULL DEFAULT 'created' ;

ALTER TABLE `ERP_purchaseOrder_history` 
CHANGE COLUMN `state` `state` ENUM('created', 'billed', 'paid', 'cancelled', 'blocked') NULL DEFAULT 'created' ;
