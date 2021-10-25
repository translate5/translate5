ALTER TABLE `ERP_order` 
ADD COLUMN `poCount` INT(45) NULL AFTER `editorName`;

UPDATE ERP_order as o SET poCount =(SELECT count(orderId) FROM ERP_purchaseOrder WHERE orderId =o.id GROUP BY orderId);
