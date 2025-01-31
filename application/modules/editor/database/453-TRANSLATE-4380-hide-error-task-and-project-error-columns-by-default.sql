-- /*
-- START LICENSE AND COPYRIGHT
--
--  This file is part of translate5
--
--  Copyright (c) 2013 - '.(date('Y')).' Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.
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

UPDATE `Zf_configuration` SET
  `value` = '{"columns":[{"id":"taskGuid"},{"id":"id"},{"id":"taskGridActionColumn"},{"id":"checked","hidden":false},{"id":"state"},{"id":"logInfo","width":100},{"id":"userState","hidden":true},{"id":"userAssocDeadline","hidden":true},{"id":"customerId","hidden":true},{"id":"workflowStepName"},{"id":"segmentFinishCount"},{"id":"qualityErrorCount"},{"id":"segmentCount","hidden":true},{"id":"wordCount"},{"id":"taskName"},{"id":"taskNr","hidden":true},{"id":"fileCount"},{"id":"sourceLang","hidden":true},{"id":"relaisLang"},{"id":"targetLang"},{"id":"referenceFiles","hidden":true},{"id":"terminologie","hidden":true},{"id":"userCount","hidden":false},{"id":"pmName","hidden":true},{"id":"orderdate","hidden":true},{"id":"enddate","hidden":true},{"id":"edit100PercentMatch","hidden":true},{"id":"emptyTargets","hidden":true},{"id":"lockLocked","hidden":true},{"id":"enableSourceEditing","hidden":true}],"weight":0}',
  `default` = '{"columns":[{"id":"taskGuid"},{"id":"id"},{"id":"taskGridActionColumn"},{"id":"checked","hidden":false},{"id":"state"},{"id":"logInfo","width":100},{"id":"userState","hidden":true},{"id":"userAssocDeadline","hidden":true},{"id":"customerId","hidden":true},{"id":"workflowStepName"},{"id":"segmentFinishCount"},{"id":"qualityErrorCount"},{"id":"segmentCount","hidden":true},{"id":"wordCount"},{"id":"taskName"},{"id":"taskNr","hidden":true},{"id":"fileCount"},{"id":"sourceLang","hidden":true},{"id":"relaisLang"},{"id":"targetLang"},{"id":"referenceFiles","hidden":true},{"id":"terminologie","hidden":true},{"id":"userCount","hidden":false},{"id":"pmName","hidden":true},{"id":"orderdate","hidden":true},{"id":"enddate","hidden":true},{"id":"edit100PercentMatch","hidden":true},{"id":"emptyTargets","hidden":true},{"id":"lockLocked","hidden":true},{"id":"enableSourceEditing","hidden":true}],"weight":0}'
WHERE `name` = 'runtimeOptions.frontend.defaultState.projectTaskGrid';