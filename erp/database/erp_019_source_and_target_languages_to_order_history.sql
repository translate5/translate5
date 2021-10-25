ALTER TABLE `ERP_order_history` 
ADD COLUMN `sourceLang` VARCHAR(255) NULL AFTER `editorName`,
ADD COLUMN `targetLang` TEXT NULL AFTER `sourceLang`;