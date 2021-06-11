-- /*
-- START LICENSE AND COPYRIGHT
-- 
--  Copyright (c) 2013 - 2017 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.
-- 
--  Contact:  http://www.MittagQI.com/  /  service (ATT) MittagQI.com
-- 
--  This file is part of a plug-in for translate5. 
--  translate5 can be optained via the instructions that are linked at http://www.translate5.net
--  For the license of translate5 itself please see http://www.translate5.net/license.txt
--  For the license of this plug-in, please see below.
--  
--  This file is part of a plug-in for translate5 and may be used under the terms of the
--  GNU GENERAL PUBLIC LICENSE version 3 as published by the Free Software Foundation and 
--  appearing in the file gpl3-license.txt included in the packaging of the translate5 plug-in
--  to which this file belongs. Please review the following information to ensure the 
--  GNU GENERAL PUBLIC LICENSE version 3 requirements will be met:
--  http://www.gnu.org/licenses/gpl.html
--    
--  There is a plugin exception available for use with this release of translate5 for 
--  translate5 plug-ins that are distributed under GNU GENERAL PUBLIC LICENSE version 3: 
--  Please see http://www.translate5.net/plugin-exception.txt or plugin-exception.txt in the
--  root folder of translate5.
--   
--  @copyright  Marc Mittag, MittagQI - Quality Informatics
--  @author     MittagQI - Quality Informatics
--  @license    GNU GENERAL PUBLIC LICENSE version 3 with plugin-execption
-- 			 http://www.gnu.org/licenses/gpl.html
-- 			 http://www.translate5.net/plugin-exception.txt
-- 
-- END LICENSE AND COPYRIGHT
-- */

ALTER TABLE `terms_attributes_datatype`
DROP INDEX `idx_term_attributes_label_type` ,
ADD UNIQUE INDEX `idx_term_attributes_label_type_level` (`label` ASC, `type` ASC, `level` ASC);

# remove all data from terms_collection_attribute_datatype so the unique index is recreated bellow
DELETE FROM terms_collection_attribute_datatype;

# fix the unique index
ALTER TABLE `terms_collection_attribute_datatype`
 DROP INDEX `indexCollectionIdAndDataTypeId` ,
 ADD UNIQUE INDEX `indexCollectionIdAndDataTypeId` (`collectionId` ASC, `dataTypeId` ASC);

# fill the collection to data type assoc table
INSERT INTO `terms_collection_attribute_datatype` (collectionId,dataTypeId) (SELECT collectionId,dataTypeId FROM `terms_attributes` group by collectionId,dataTypeId);