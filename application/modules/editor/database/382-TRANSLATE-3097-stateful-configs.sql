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

-- Right panel and top segment toolbar buttons visibility
INSERT IGNORE INTO `Zf_configuration`
(`name`, `confirmed`, `module`, `category`, `value`, `default`, `defaults`, `type`, `typeClass`, `description`, `level`, `guiName`, `guiGroup`, `comment`)
VALUES
('runtimeOptions.frontend.defaultState.editor.eastPanelSegmentsTerminology','1','editor','system','{}','{}','','map',NULL,
 'Default state configuration for the editor east panel segments terminology. If this field value is empty ({} is not an empty value!), no state will be applied/saved for this component.','32','Editor right panel review segment terminology data default configuration','Editor: UI layout & more',''),

('runtimeOptions.frontend.defaultState.editor.eastPanelSegmentsFalsePositives','1','editor','system','{}','{}','','map',NULL,
 'Default state configuration for the editor east panel segments false positives. If this field value is empty ({} is not an empty value!), no state will be applied/saved for this component.','32','Editor right panel review segment false positives data default configuration','Editor: UI layout & more',''),

('runtimeOptions.frontend.defaultState.editor.eastPanelSegmentsMqmWhole','1','editor','system','{}','{}','','map',NULL,
 'Default state configuration for the editor east panel segments Manual QA (complete segment). If this field value is empty ({} is not an empty value!), no state will be applied/saved for this component.','32','Editor right panel review segment Manual QA (complete segment) data default configuration','Editor: UI layout & more',''),

('runtimeOptions.frontend.defaultState.editor.eastPanelSegmentsMqmInside','1','editor','system','{}','{}','','map',NULL,
 'Default state configuration for the editor east panel segments Manual QA (inside segment). If this field value is empty ({} is not an empty value!), no state will be applied/saved for this component.','32','Editor right panel review segment Manual QA (inside segment) data default configuration','Editor: UI layout & more',''),

('runtimeOptions.frontend.defaultState.editor.eastPanelSegmentsMetaStates','1','editor','system','{}','{}','','map',NULL,
 'Default state configuration for the editor east panel segments status. If this field value is empty ({} is not an empty value!), no state will be applied/saved for this component.','32','Editor right panel review segment status default configuration','Editor: UI layout & more',''),

('runtimeOptions.frontend.defaultState.editor.segmentActionMenu','1','editor','system','{checkedItems: "saveBtn,cancelBtn,saveNextBtn"}','{checkedItems: "saveBtn,cancelBtn,saveNextBtn"}','','map',NULL,
 'Default state of which segment action menu items should be additionally shown as buttons in the segment grid top toolbar. If this field value is empty ({} is not an empty value!), no state will be applied/saved for this component.','32','Editor toolbar segment action buttons visibility','Editor: UI layout & more','');
