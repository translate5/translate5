-- /*
-- START LICENSE AND COPYRIGHT
--
--  This file is part of translate5
--
--  Copyright (c) 2013 - 2025 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.
--
--  Contact:  http://www.MittagQI.com/  /  service (ATT) MittagQI.com
--
--  This file may be used under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE version 3
--  as published by the Free Software Foundation and appearing in the file agpl3-license.txt
--  included in the packaging of this file.  Please review the following information
--  to ensure the GNU AFFERO GENERAL PUBLIC LICENSE version 3 requirements will be met:
--  http://www.gnu.org/licenses/agpl.html
--
--  There is a plugin exception available for use with this release of translate5 for
--  translate5: Please see http://www.translate5.net/plugin-exception.txt or
--  plugin-exception.txt in the root folder of translate5.
--
--  @copyright  Marc Mittag, MittagQI - Quality Informatics
--  @author     MittagQI - Quality Informatics
--  @license    GNU AFFERO GENERAL PUBLIC LICENSE version 3 with plugin-execption
-- 			 http://www.gnu.org/licenses/agpl.html http://www.translate5.net/plugin-exception.txt
--
-- END LICENSE AND COPYRIGHT
-- */

CREATE TABLE `LEK_coti_upload` (
   `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
   `pkg_name` varchar(255) NOT NULL,
   `uploaded` datetime NOT NULL,
   PRIMARY KEY (`id`)
);

CREATE TABLE `LEK_coti_project_upload_assoc` (
   `project_id` int NOT NULL,
   `upload_id` int UNSIGNED NOT NULL,
   PRIMARY KEY (`project_id`),
   KEY(`upload_id`)
);

ALTER TABLE `LEK_coti_project_upload_assoc`
    add constraint `LEK_coti_project_upload_assoc_upload_id` FOREIGN KEY (upload_id) references LEK_coti_upload(id) ON DELETE CASCADE;

ALTER TABLE `LEK_coti_project_upload_assoc`
    add constraint `LEK_coti_project_upload_assoc_project_id` FOREIGN KEY (project_id) references LEK_task(id) ON DELETE CASCADE;
