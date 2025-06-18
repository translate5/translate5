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

-- due numbering mismatch of the alter SQLs, the previous file were merged in one new file (this one here) which deletes all previous stuff and reapplies
-- all the modifications so that structure and status of imported SQLs is clean again

INSERT INTO `Zf_configuration`
 (`name`, `confirmed`, `module`, `category`, `value`, `default`, `defaults`, `type`, `typeClass`, `level`, `description`, `guiName`, `guiGroup`)
VALUES
    (
        'runtimeOptions.plugins.CotiHotfolder.defaultPM',
        1,
        'editor',
        'plugins',
        (SELECT id FROM Zf_users WHERE roles LIKE '%pm%' LIMIT 1),
        '',
        '',
        'string',
        '\\MittagQI\\Translate5\\DbConfig\\Type\\CoreTypes\\DefaultPmType',
        4,
        'Set default PM user for CotiHotfolder project import',
        'Default user to be assigned as PM for CotiHotfolder-projects',
        'CotiHotfolder'
    ),
    (
        'runtimeOptions.plugins.CotiHotfolder.filesystemConfig',
        1,
        'editor',
        'plugins',
        '',
        '',
        '',
        'map',
        'ZfExtended_DbConfig_Type_SimpleMap',
        4,
        'Set Filesystem config for CotiHotfolder project import',
        'Filesystem config for CotiHotfolder project import',
        'CotiHotfolder'
    ),
    (
        'runtimeOptions.plugins.CotiHotfolder.enableAutoExport',
        1,
        'editor',
        'plugins',
        '1',
        '1',
        '',
        'boolean',
        '',
        4,
        'Choose if finished task should be automatically uploaded into client\'s filesystem',
        'Enable Auto Export',
        'CotiHotfolder'
    )
;
