--  /*
--  START LICENSE AND COPYRIGHT
--
--  This file is part of Translate5 Editor PHP Serverside and build on Zend Framework
--
--  Copyright (c) 2013 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.
--
--  Contact:  http://www.MittagQI.com/  /  service (ÄTT) MittagQI.com
--
--  This file may be used under the terms of the GNU General Public License version 3.0
--  as published by the Free Software Foundation and appearing in the file gpl3-license.txt
--  included in the packaging of this file.  Please review the following information
--  to ensure the GNU General Public License version 3.0 requirements will be met:
--  http://www.gnu.org/copyleft/gpl.html.
--
--  For this file you are allowed to make use of the same FLOSS exceptions to the GNU
--  General Public License version 3.0 as specified by Sencha for Ext Js.
--  Please be aware, that Marc Mittag / MittagQI take no warranty  for any legal issue,
--  that may arise, if you use these FLOSS exceptions and recommend  to stick to GPL 3.
--  For further information regarding this topic please see the attached license.txt
--  of this software package.
--
--  MittagQI would be open to release translate5 under EPL or LGPL also, if this could be
--  brought in accordance with the ExtJs license scheme. You are welcome to support us
--  with legal support, if you are interested in this.
--
--
--  @copyright  Marc Mittag, MittagQI - Quality Informatics
--  @author     MittagQI - Quality Informatics
--  @license    GNU General Public License version 3.0 http://www.gnu.org/copyleft/gpl.html
--              with FLOSS exceptions (see floss-exception.txt and ux-exception.txt at the root level)
--
--  END LICENSE AND COPYRIGHT
--  */
ALTER TABLE LEK_segment_history_data ADD COLUMN `duration` INT(11) DEFAULT 0 after taskGuid;
ALTER TABLE LEK_segment_data ADD COLUMN `duration` INT(11) DEFAULT 0 after taskGuid;

-- column ist not filled in the app, value can be easily calculated, so we drop the field:
ALTER TABLE LEK_segment_history_data DROP COLUMN taskGuid;

-- with the following view an easy access to the duration values is provided
-- if fields, which are only provided in LEK_segments (since not change in history, 
-- like MID or segmentNrInTask) should also appear in the result, 
-- then we can either join LEK_segments to the whole view, 
-- or we can add a join to LEK_segments in the second select of the union statement,
-- and add the desired fields in both selects of the union

CREATE VIEW LEK_segment_durations AS 
  (select s.id segmentId, s.taskGuid, s.userGuid, s.userName, s.timestamp, s.editable, s.pretrans, s.qmId, s.stateId, s.autoStateId, s.workflowStepNr, s.workflowStep, sd.name, sd.duration
  from LEK_segments s
  join LEK_segment_data sd on s.id = sd.segmentId)
union all
  (select h.segmentId segmentId, h.taskGuid, h.userGuid, h.userName, h.timestamp, h.editable, h.pretrans, h.qmId, h.stateId, h.autoStateId, h.workflowStepNr, h.workflowStep, hd.name, hd.duration
  from LEK_segment_history h
  join LEK_segment_history_data hd on hd.segmentHistoryId = h.id); 