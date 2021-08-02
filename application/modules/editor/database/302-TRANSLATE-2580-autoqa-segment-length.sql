-- /*
-- START LICENSE AND COPYRIGHT
--
--  This file is part of translate5
--
--  Copyright (c) 2013 - 2020 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.
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

INSERT INTO `Zf_configuration` (`name`, `confirmed`, `module`, `category`, `value`, `default`, `defaults`, `type`, `description`, `level`, `guiName`, `guiGroup`, `comment`) VALUES
('runtimeOptions.autoQA.segmentPixelLengthTooShortPercent', 1, 'editor', 'system', 20, 20, '', 'integer', 'If given, defines how long of the max defined length a segment has to be in percent', 8, 'Defines the length check for segments being too short in percent', 'Editor: QA', '');

INSERT INTO `Zf_configuration` (`name`, `confirmed`, `module`, `category`, `value`, `default`, `defaults`, `type`, `description`, `level`, `guiName`, `guiGroup`, `comment`) VALUES
('runtimeOptions.autoQA.segmentPixelLengthTooShortPixel', 1, 'editor', 'system', 100, 100, '', 'integer', 'If given, defines how much shorter a segment can be than the defined length in pixels', 8, 'Defines the length check for segments being too short in pixels', 'Editor: QA', '');

INSERT INTO `Zf_configuration` (`name`, `confirmed`, `module`, `category`, `value`, `default`, `defaults`, `type`, `description`, `level`, `guiName`, `guiGroup`, `comment`) VALUES
('runtimeOptions.autoQA.segmentPixelLengthTooShortChars', 1, 'editor', 'system', 20, 20, '', 'integer', 'If given, defines how much shorter a segment can be than the defined length in characters', 8, 'Defines the length check for segments being too short in characters', 'Editor: QA', '');

INSERT INTO `Zf_configuration` (`name`, `confirmed`, `module`, `category`, `value`, `default`, `defaults`, `type`, `description`, `level`, `guiName`, `guiGroup`, `comment`) VALUES
('runtimeOptions.autoQA.enableSegmentLengthCheck', 1, 'editor', 'system', 1, 1, '', 'boolean', 'If activated (default), AutoQA covers checking the segment length', 8, 'Enables segment length check', 'Editor: QA', '');
