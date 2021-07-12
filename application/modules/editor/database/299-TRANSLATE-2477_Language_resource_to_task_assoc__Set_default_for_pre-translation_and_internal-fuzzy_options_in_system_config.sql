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

INSERT INTO `translate5`.`Zf_configuration` (`name`, `confirmed`, `module`, `category`, `value`, `default`, `defaults`, `type`, `description`, `level`, `guiName`, `guiGroup`) VALUES ('runtimeOptions.plugins.MatchAnalysis.internalFuzzyDefault', '1', 'editor', 'plugins', '1', '1', '', 'boolean', 'Is \"Count internal fuzzy\" checkbox in the analysis overview checked by default.', '4', 'Count internal fuzzy checked by default', 'Match analysis: defaults');

INSERT INTO `translate5`.`Zf_configuration` (`name`, `confirmed`, `module`, `category`, `value`, `default`, `defaults`, `type`, `description`, `level`, `guiName`, `guiGroup`) VALUES ('runtimeOptions.plugins.MatchAnalysis.pretranslateTmAndTermDefault', '1', 'editor', 'plugins', '1', '1', '', 'boolean', 'Is \"Pre-translate (TM & Terms)\" checkbox in the analysis overview checked by default', '4', 'Pre-translate (TM & Terms) checked by default', 'Match analysis: defaults');

INSERT INTO `translate5`.`Zf_configuration` (`name`, `confirmed`, `module`, `category`, `value`, `default`, `defaults`, `type`, `description`, `level`, `guiName`, `guiGroup`) VALUES ('runtimeOptions.plugins.MatchAnalysis.pretranslateMtDefault', '1', 'editor', 'plugins', '1', '1', '', 'boolean', 'Is \"Pre-translate (MT)\" checkbox in the analysis overview checked by default', '4', 'Pre-translate (MT) checked by default', 'Match analysis: defaults');
