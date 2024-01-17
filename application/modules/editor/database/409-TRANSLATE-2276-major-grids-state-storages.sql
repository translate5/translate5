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
--              http://www.gnu.org/licenses/agpl.html http://www.translate5.net/plugin-exception.txt
--
-- END LICENSE AND COPYRIGHT
-- */

REPLACE INTO `Zf_configuration` (`name`, `confirmed`, `module`, `category`, `value`, `default`, `defaults`, `type`,
`typeClass`, `description`, `level`, `guiName`, `guiGroup`, `comment`) VALUES
('runtimeOptions.frontend.defaultState.editor.tmOverviewPanel'    ,'1','editor','system','{columns: []}','{columns: []}','','map',NULL,
 'Language resources table default state configuration'           ,'32','Language resources table default configuration','Editor: UI layout & more',''),

('runtimeOptions.frontend.defaultState.editor.customerPanelGrid'  ,'1','editor','system','{columns: []}','{columns: []}','','map',NULL,
 'Clients table default state configuration'                      ,'32','Clients table default configuration','Editor: UI layout & more',''),

('runtimeOptions.frontend.defaultState.editor.adminUserGrid'      ,'1','editor','system','{columns: []}','{columns: []}','','map',NULL,
 'Users table default state configuration'                        ,'32','Users table default configuration','Editor: UI layout & more',''),

('runtimeOptions.frontend.defaultState.editor.adminTaskGrid'      ,'1','editor','system','{columns: []}','{columns: []}','','map',NULL,
 'Tasks table default state configuration'                        ,'32','Tasks table default configuration','Editor: UI layout & more',''),

('runtimeOptions.frontend.defaultState.editor.projectGrid'        ,'1','editor','system','{columns: []}','{columns: []}','','map',NULL,
 'Projects table default state configuration'                     ,'32','Projects table default configuration','Editor: UI layout & more','');

DELETE FROM `Zf_configuration` WHERE `name` IN (
    'runtimeOptions.frontend.defaultState.adminTaskGrid',
    'runtimeOptions.frontend.defaultState.adminUserGrid',
    'runtimeOptions.frontend.defaultState.projectGrid'
);