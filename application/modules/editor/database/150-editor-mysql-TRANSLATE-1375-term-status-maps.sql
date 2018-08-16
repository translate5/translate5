-- /*
-- START LICENSE AND COPYRIGHT
-- 
--  This file is part of translate5
--  
--  Copyright (c) 2013 - 2017 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.
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

INSERT INTO `Zf_configuration` (`name`, `confirmed`, `module`, `category`, `value`, `default`, `defaults`, `type`, `description`) 
VALUES ('runtimeOptions.tbx.termLabelMap.preferredTerm', '1', 'editor', 'termtagger', 'preferred', 'preferred', 'preferred,permitted,forbidden', 'string', 'Defines how the Term Status should be visualized in the Frontend'),
('runtimeOptions.tbx.termLabelMap.admittedTerm', '1', 'editor', 'termtagger', 'permitted', 'permitted', 'preferred,permitted,forbidden', 'string', 'Defines how the Term Status should be visualized in the Frontend'),
('runtimeOptions.tbx.termLabelMap.legalTerm', '1', 'editor', 'termtagger', 'permitted', 'permitted', 'preferred,permitted,forbidden', 'string', 'Defines how the Term Status should be visualized in the Frontend'),
('runtimeOptions.tbx.termLabelMap.regulatedTerm', '1', 'editor', 'termtagger', 'permitted', 'permitted', 'preferred,permitted,forbidden', 'string', 'Defines how the Term Status should be visualized in the Frontend'),
('runtimeOptions.tbx.termLabelMap.standardizedTerm', '1', 'editor', 'termtagger', 'permitted', 'permitted', 'preferred,permitted,forbidden', 'string', 'Defines how the Term Status should be visualized in the Frontend'),
('runtimeOptions.tbx.termLabelMap.deprecatedTerm', '1', 'editor', 'termtagger', 'forbidden', 'forbidden', 'preferred,permitted,forbidden', 'string', 'Defines how the Term Status should be visualized in the Frontend'),
('runtimeOptions.tbx.termLabelMap.supersededTerm', '1', 'editor', 'termtagger', 'forbidden', 'forbidden', 'preferred,permitted,forbidden', 'string', 'Defines how the Term Status should be visualized in the Frontend');

INSERT INTO `Zf_configuration` (`name`, `confirmed`, `module`, `category`, `value`, `default`, `defaults`, `type`, `description`) 
VALUES ('runtimeOptions.tbx.termImportMap.across_ISO_picklist_Usage.do not use', '1', 'editor', 'termtagger', 'supersededTerm', 'supersededTerm', '', 'string', 'Defines a mapping for foreign term status to an internal known status.');
