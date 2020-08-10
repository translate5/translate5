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

insert into LEK_terms (taskGuid,term,termId,status,definition,groupId,language) 
 select taskGuid, 'Erziehungsprogramm' term, 'term_1_de-DE-1' termId, 'preferred' status, 'Test Daten' definition, 'term_1' groupId, 'de-DE' language from LEK_segments where target like '%Erziehungsprogramm%' group by taskGuid,termId;
insert into LEK_terms (taskGuid,term,termId,status,definition,groupId,language) 
  select taskGuid, 'Züchtigungsprogramm' term, 'term_1_de-DE-2' termId, 'notRecommended' status, 'Test Daten' definition, 'term_1' groupId, 'de-DE' language from LEK_segments where target like '%Erziehungsprogramm%' group by taskGuid,termId;
insert into LEK_terms (taskGuid,term,termId,status,definition,groupId,language) 
  select taskGuid, 'Bildungsprogramm' term, 'term_1_de-DE-3' termId, 'admitted' status, 'Test Daten' definition, 'term_1' groupId, 'de-DE' language from LEK_segments where target like '%Erziehungsprogramm%' group by taskGuid,termId;

insert into LEK_terms (taskGuid,term,termId,status,definition,groupId,language) 
 select taskGuid, 'Association' term, 'term_2_en-EN-1' termId, 'preferred' status, 'Test Data' definition, 'term_2' groupId, 'en-EN' language from LEK_segments where source like '%Association%' group by taskGuid,termId;
insert into LEK_terms (taskGuid,term,termId,status,definition,groupId,language) 
  select taskGuid, 'Syndicate' term, 'term_2_en-EN-2' termId, 'notRecommended' status, 'Test Data' definition, 'term_2' groupId, 'en-EN' language from LEK_segments where source like '%Association%' group by taskGuid,termId;
insert into LEK_terms (taskGuid,term,termId,status,definition,groupId,language) 
  select taskGuid, 'Society' term, 'term_2_en-EN-3' termId, 'admitted' status, 'Test Data' definition, 'term_2' groupId, 'en-EN' language from LEK_segments where source like '%Association%' group by taskGuid,termId;

insert into LEK_terms (taskGuid,term,termId,status,definition,groupId,language) 
 select taskGuid, 'Gesellschaft' term, 'term_2_de-DE-4' termId, 'preferred' status, 'Test Daten' definition, 'term_2' groupId, 'de-DE' language from LEK_segments where target like '%gesellschaft%' group by taskGuid,termId;
insert into LEK_terms (taskGuid,term,termId,status,definition,groupId,language) 
  select taskGuid, 'Verband' term, 'term_2_de-DE-5' termId, 'notRecommended' status, 'Test Daten' definition, 'term_2' groupId, 'de-DE' language from LEK_segments where target like '%gesellschaft%' group by taskGuid,termId;
insert into LEK_terms (taskGuid,term,termId,status,definition,groupId,language) 
  select taskGuid, 'Soziätät' term, 'term_2_de-DE-6' termId, 'admitted' status, 'Test Daten' definition, 'term_2' groupId, 'de-DE' language from LEK_segments where target like '%gesellschaft%' group by taskGuid,termId;

insert into `LEK_terminstances` (segmentId,term,termId)
  select seg.id, term.term, term.id from LEK_segments seg, LEK_terms term where term.term = 'Association' and seg.source like '%Association%' and seg.taskGuid = term.taskGuid;

insert into `LEK_terminstances` (segmentId,term,termId)
  select seg.id, term.term, term.id from LEK_segments seg, LEK_terms term where term.term = 'Gesellschaft' and seg.target like '%gesellschaft%' and seg.taskGuid = term.taskGuid;

insert into `LEK_terminstances` (segmentId,term,termId)
  select seg.id, term.term, term.id from LEK_segments seg, LEK_terms term where term.term = 'Erziehungsprogramm' and seg.target like '%Erziehungsprogramm%' and seg.taskGuid = term.taskGuid;

insert into `LEK_segments2terms` (`segmentId`,`lang`,`used`,`termId`,`transFound`)
  select seg.id, 'target' lang, seg2.target like '%Erziehungsprogramm%' used, term.id termId, false transFound
  from LEK_segments seg, LEK_terms term 
    LEFT JOIN LEK_segments seg2 
      ON seg2.target like concat(concat('%', term.term),'%') and seg2.taskGuid = term.taskGuid
  where term.groupId = 'term_1' and seg.target like '%Erziehungsprogramm%' and seg.taskGuid = term.taskGuid group by term.taskGuid, id, termId;

insert into `LEK_segments2terms` (`segmentId`,`lang`,`used`,`termId`,`transFound`)
  select seg.id, 'target' lang, seg2.target like '%Gesellschaft%' used, term.id termId, false transFound
  from LEK_segments seg, LEK_terms term 
    LEFT JOIN LEK_segments seg2 
      ON seg2.target like concat(concat('%', term.term),'%') and seg2.taskGuid = term.taskGuid
  where term.language = 'de-DE' and term.groupId = 'term_2' and seg.target like '%Gesellschaft%' and seg.taskGuid = term.taskGuid group by term.taskGuid, id, termId;

insert into `LEK_segments2terms` (`segmentId`,`lang`,`used`,`termId`,`transFound`)
  select seg.id, 'source' lang, seg2.source like '%Association%' used, term.id termId, seg.target like '%Gesellschaft%' transFound
  from LEK_segments seg, LEK_terms term 
    LEFT JOIN LEK_segments seg2 
      ON seg2.source like concat(concat('%', term.term),'%') and seg2.taskGuid = term.taskGuid
  where term.language = 'en-EN' and term.groupId = 'term_2' and seg.source like '%Association%' and seg.taskGuid = term.taskGuid group by term.taskGuid, id, termId;