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

INSERT INTO `Zf_acl_rules` (`module`, `role`, `resource`, `right`) VALUES
('editor', 'pm', 'editor_instanttranslate', 'all'),
('editor', 'editor', 'editor_instanttranslate', 'all'),
('editor', 'api', 'editor_instanttranslate', 'all');

INSERT INTO `Zf_configuration` (`name`, `confirmed`, `module`, `category`, `value`, `default`, `defaults`, `type`, `description`) 
VALUES ('runtimeOptions.LanguageResources.fileExtension', '1', 'editor', 'system', '', '', '', 'map', 'Available file types by extension per engine type. The engine type is defined by source rcf5646,target rcf5646. ex: \"en-ge,en-us\"');

INSERT INTO `Zf_configuration` (`name`, `confirmed`, `module`, `category`, `value`, `default`, `defaults`, `type`, `description`) 
VALUES ('runtimeOptions.InstantTranslate.customersMaxSearchCharacters', '1', 'editor', 'instanttranslate', '[]', '[]', '', 'map', 'Maximum character available in source search field configured by customer');

INSERT INTO `Zf_configuration` (`name`, `confirmed`, `module`, `category`, `value`, `default`, `defaults`, `type`, `description`) 
VALUES ('runtimeOptions.InstantTranslate.minMatchRateBorder', '1', 'editor', 'instanttranslate', '', '', '', 'integer', 'Minimum matchrate allowed to be displayed in instantranslate result list');