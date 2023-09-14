<?php
/*
START LICENSE AND COPYRIGHT

 This file is part of translate5

 Copyright (c) 2013 - 2017 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

 Contact:  http://www.MittagQI.com/  /  service (ATT) MittagQI.com

 This file may be used under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE version 3
 as published by the Free Software Foundation and appearing in the file agpl3-license.txt
 included in the packaging of this file.  Please review the following information
 to ensure the GNU AFFERO GENERAL PUBLIC LICENSE version 3 requirements will be met:
 http://www.gnu.org/licenses/agpl.html

 There is a plugin exception available for use with this release of translate5 for
 translate5 plug-ins that are distributed under GNU AFFERO GENERAL PUBLIC LICENSE version 3:
 Please see http://www.translate5.net/plugin-exception.txt or plugin-exception.txt in the root
 folder of translate5.

 @copyright  Marc Mittag, MittagQI - Quality Informatics
 @author     MittagQI - Quality Informatics
 @license    GNU AFFERO GENERAL PUBLIC LICENSE version 3 with plugin-execption
			 http://www.gnu.org/licenses/agpl.html http://www.translate5.net/plugin-exception.txt

END LICENSE AND COPYRIGHT
*/

/**
 * Remove all old term collection tbx cache folders from the disc.
 * Only folders for non-existing term-collections in the translate5 will be removed.
 */
set_time_limit(0);

/* @var ZfExtended_Models_Installer_DbUpdater $this */

//uncomment the following line, so that the file is not marked as processed:
// $this->doNotSavePhpForDebugging = false;

//should be not __FILE__ in the case of wanted restarts / renamings etc
// and must not be a constant since in installation the same named constant would we defined multiple times then
$SCRIPT_IDENTIFIER = '399-TRANSLATE-3451-workflow-foreign-keys.php';

$db = Zend_Db_Table::getDefaultAdapter();
$inconsistentAssocs = $db->query('SELECT child.*
FROM LEK_user_assoc_default child
         LEFT JOIN LEK_workflow_step parent ON child.workflow = parent.workflowName AND child.workflowStepName = parent.name
WHERE parent.id IS NULL;')->fetchAll();

foreach ($inconsistentAssocs as $assoc) {
    $this->log->warn('E1553', 'Deleting inconsistent user default assoc for customer ID {customerId}', $assoc);
    $db->delete('LEK_user_assoc_default', ['id = ?' => $assoc['id']]);
}