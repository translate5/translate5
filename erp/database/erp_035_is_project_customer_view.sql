ALTER TABLE `ERP_order` 
ADD COLUMN `isCustomerView` TINYINT(1) NULL COMMENT 'Is the current row customer specific row (row created from customer specific view)' AFTER `plannedDeliveryDate`;
