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
--  to ensure the GNU AFFERO GENERAL PUBLIC LICENSE version 3.0 requirements will be met:
--  http://www.gnu.org/licenses/agpl.html
--   
--  @copyright  Marc Mittag, MittagQI - Quality Informatics
--  @author     MittagQI - Quality Informatics
--  @license    GNU AFFERO GENERAL PUBLIC LICENSE version 3
-- 			 http://www.gnu.org/licenses/agpl.html
-- 
-- END LICENSE AND COPYRIGHT
-- */

update Zf_configuration set `value` = '["css/editorAdditions.css?v=2"]' 
where name = 'runtimeOptions.publicAdditions.css' and value = `default`;

update Zf_configuration set `default` = '["css/editorAdditions.css?v=2"]'
where name = 'runtimeOptions.publicAdditions.css';

update Zf_configuration set `value` = '/css/translate5.css?v=2' 
where name = 'runtimeOptions.server.pathToCSS' and value = `default`;

update Zf_configuration set `default` = '/css/translate5.css?v=2'
where name = 'runtimeOptions.server.pathToCSS';

update Zf_configuration set `value` = '["index", "usage", "testdata", "source", "newsletter", "testimonials"]' 
where name = 'runtimeOptions.content.viewTemplatesAllowed' and value = `default`;

update Zf_configuration set `default` = '["index", "usage", "testdata", "source", "newsletter", "testimonials"]'
where name = 'runtimeOptions.content.viewTemplatesAllowed';