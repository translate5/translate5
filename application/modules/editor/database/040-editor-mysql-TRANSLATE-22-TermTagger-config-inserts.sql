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

INSERT INTO Zf_configuration (`name`, `confirmed`, `module`, `category`, `value`, `default`, `defaults`, `type`, `description`) VALUES 
('runtimeOptions.termTagger.url.default', 1, 'plugin', 'termtagger', '', '', '', 'list', 'List of available TermTagger-URLs. At least one available URL must be defined. Example: ["http://localhost:9000"]'),
('runtimeOptions.termTagger.url.import', 1, 'plugin', 'termtagger', '', '', '', 'list', 'Optional list of TermTagger-URL to use for task-import processing. Fallback is list runtimeOptions.termTagger.url.default. Example: ["http://localhost:9000"]'),
('runtimeOptions.termTagger.url.gui', 1, 'plugin', 'termtagger', '', '', '', 'list', 'Optional list of TermTagger-URL to use for gui-response processing. Fallback is list runtimeOptions.termTagger.url.default. Example: ["http://localhost:9000"]'),
('runtimeOptions.termTagger.segmentsPerCall', 1, 'plugin', 'termtagger', '20', '20', '', 'integer', 'Maximal number of segments the TermTagger will process in one step'),
('runtimeOptions.termTagger.timeOut.tbxParsing', 1, 'plugin', 'termtagger', '120', '120', '', 'integer', 'connection timeout when parsing tbx'),
('runtimeOptions.termTagger.timeOut.segmentTagging', 1, 'plugin', 'termtagger', '60', '60', '', 'integer', 'connection timeout when tagging segments');
UPDATE  `Zf_configuration` SET  `default` =  '500',`value` =  '500' WHERE  `Zf_configuration`.`name` ='runtimeOptions.termTagger.timeOut.tbxParsing';
UPDATE  `Zf_configuration` SET  `default` =  '500',`value` =  '500' WHERE  `Zf_configuration`.`name` ='runtimeOptions.termTagger.timeOut.segmentTagging';