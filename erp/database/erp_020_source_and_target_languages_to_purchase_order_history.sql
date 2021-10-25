ALTER TABLE `ERP_purchaseOrder_history` 
ADD COLUMN `sourceLang` VARCHAR(255) NULL AFTER `editorName`,
ADD COLUMN `targetLang` TEXT NULL AFTER `sourceLang`;