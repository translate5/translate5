/*
START LICENSE AND COPYRIGHT

 This file is part of translate5
 
 Copyright (c) 2013 - 2017 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

 Contact:  http://www.MittagQI.com/  /  service (ATT) MittagQI.com

 This file may be used under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE version 3
 as published by the Free Software Foundation and appearing in the file agpl3-license.txt 
 included in the packaging of this file.  Please review the following information 
 to ensure the GNU AFFERO GENERAL PUBLIC LICENSE version 3 requirements will be met:
 http://www.gnu.org/licenses/agpl.html
  
 There is a plugin exception available for use with this release of translate5 for
 translate5 plug-ins that are distributed under GNU AFFERO GENERAL PUBLIC LICENSE version 3:
 Please see http://www.translate5.net/plugin-exception.txt or plugin-exception.txt in the root
 folder of translate5.
  
 @copyright  Marc Mittag, MittagQI - Quality Informatics
 @author     MittagQI - Quality Informatics
 @license    GNU AFFERO GENERAL PUBLIC LICENSE version 3 with plugin-execption
			 http://www.gnu.org/licenses/agpl.html http://www.translate5.net/plugin-exception.txt

END LICENSE AND COPYRIGHT
*/

-- if there are more then one tasks assigned to a lang res, we set this lang res to autoCreatedOnImport = 0
UPDATE `LEK_languageresources` as `lr`, 
(
    SELECT count(*) `cnt`, `languageResourceId` 
    FROM `LEK_languageresources_taskassoc`
    GROUP BY `languageResourceId`
) as `assoc`
SET `lr`.`autoCreatedOnImport` = 0
WHERE 
`assoc`.`languageResourceId` = `lr`.`id`
AND `assoc`.`cnt` > 1;


ALTER TABLE `LEK_languageresources_taskassoc`
ADD COLUMN `autoCreatedOnImport` tinyint(1) DEFAULT 0;

-- since with above UPDATE only TC with one task can have autoCreatedOnImport = 1, this update is save
UPDATE `LEK_languageresources_taskassoc` as `assoc`,
`LEK_languageresources` as `lr`
SET `assoc`.`autoCreatedOnImport` = `lr`.`autoCreatedOnImport`
WHERE `assoc`.`languageResourceId` = `lr`.`id`;

ALTER TABLE `LEK_languageresources`
DROP COLUMN `autoCreatedOnImport`;
