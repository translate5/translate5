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

insert into LEK_segment_field
  (taskGuid, name, `type`, `label`, rankable, editable)
select
  taskGuid, 'source', 'source', 'Ausgangstext', 0, enableSourceEditing
from LEK_task;

insert into LEK_segment_data
  (taskGuid, name, segmentId, mid, original, originalMd5, originalToSort, edited, editedToSort)
select
  s.taskGuid, 'source', s.`id`, s.mid, s.`source`, s.sourceMd5, s.sourceToSort, IF(t.enableSourceEditing > 0, s.sourceEdited, null), IF(t.enableSourceEditing > 0, s.sourceEditedToSort, null)
from LEK_segments s, LEK_task t
where t.taskGuid = s.taskGuid;

insert into LEK_segment_field
  (taskGuid, name, `type`, `label`, rankable, editable)
select
  taskGuid, 'target', 'target', 'Zieltext', 0, 1
from LEK_task;

insert into LEK_segment_data
  (taskGuid, name, segmentId, mid, original, originalMd5, originalToSort, edited, editedToSort)
select
  taskGuid, 'target', `id`, mid, target, targetMd5, targetToSort, edited, editedToSort
from LEK_segments;

insert into LEK_segment_field
  (taskGuid, name, `type`, `label`, rankable, editable)
select
  LEK_task.taskGuid, 'relais', 'relais', 'Relaissprache', 0, 0
from LEK_task
where LEK_task.relaisLang > 0;

insert into LEK_segment_data
  (taskGuid, name, segmentId, mid, original, originalMd5, originalToSort)
select
  s.taskGuid, 'relais', s.id, s.mid, s.relais, s.relaisMd5, s.relaisToSort
from LEK_segments s, LEK_task t
where t.relaisLang > 0 and t.taskGuid = s.taskGuid;

alter table LEK_segments drop column `source`;
alter table LEK_segments drop column sourceToSort;
alter table LEK_segments drop column sourceMd5;
alter table LEK_segments drop column targetMd5;
alter table LEK_segments drop column target;
alter table LEK_segments drop column targetToSort;
alter table LEK_segments drop column edited;
alter table LEK_segments drop column editedToSort;
alter table LEK_segments drop column relais;
alter table LEK_segments drop column relaisToSort;
alter table LEK_segments drop column relaisMd5;
alter table LEK_segments drop column sourceEdited;
alter table LEK_segments drop column sourceEditedToSort;

insert into LEK_segment_history_data
  (taskGuid, segmentHistoryId, name, edited)
select
  taskGuid, `id`, 'target', edited
from LEK_segment_history;

insert into LEK_segment_history_data
  (taskGuid, segmentHistoryId, name, edited)
select
  h.taskGuid, h.`id`, 'source', h.sourceEdited
from LEK_segment_history h, LEK_task t
where t.enableSourceEditing > 0 and t.taskGuid = h.taskGuid;

alter table LEK_segment_history drop column edited;
alter table LEK_segment_history drop column sourceEdited;