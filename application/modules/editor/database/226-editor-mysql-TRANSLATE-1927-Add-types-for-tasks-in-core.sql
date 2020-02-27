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

-- TRANSLATE-1927: Hidden tasks needed => introduce types for tasks in core
ALTER TABLE `LEK_task` 
ADD COLUMN `taskType` VARCHAR(255) NULL DEFAULT 'default';

-- set initialtasktypes depending on the role
INSERT INTO Zf_acl_rules (`module`, `role`, `resource`, `right`) VALUES 
('editor', 'api', 'initial_tasktype', 'instanttranslate-pre-translate'),
('editor', 'api', 'initial_tasktype', 'default'),
('editor', 'pm', 'initial_tasktype', 'default'),
('editor', 'instantTranslate', 'initial_tasktype', 'instanttranslate-pre-translate');
