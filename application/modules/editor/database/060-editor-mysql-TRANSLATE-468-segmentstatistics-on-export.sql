--  /*
--  START LICENSE AND COPYRIGHT
--
--  WILL BE REPLACED!
--
--  END LICENSE AND COPYRIGHT 
--  */
ALTER TABLE `LEK_plugin_segmentstatistics` ADD COLUMN `type` enum('import','export') DEFAULT 'import', ADD COLUMN termFound int(11) NOT NULL;
ALTER TABLE `LEK_plugin_segmentstatistics` DROP INDEX `segmentIdFieldName`, ADD UNIQUE INDEX `segmentIdFieldName` (`segmentId` ASC, `fieldName` ASC, `type` ASC);

-- mark existing projects
UPDATE LEK_plugin_segmentstatistics SET termFound = -1;