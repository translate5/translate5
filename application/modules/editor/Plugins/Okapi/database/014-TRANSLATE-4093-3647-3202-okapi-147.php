<?php
/*
START LICENSE AND COPYRIGHT

 This file is part of translate5

 Copyright (c) 2013 - 2024 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

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
 * This script imports all fprms extracted to the bconf-data-folders additionally to the DB
 */

use MittagQI\Translate5\Plugins\Okapi\Bconf\BconfEntity;
use MittagQI\Translate5\Plugins\Okapi\Bconf\Filter\FprmUpdaterTo147;

set_time_limit(0);

//uncomment the following line, so that the file is not marked as processed:
//$this->doNotSavePhpForDebugging = false;

//should be not __FILE__ in the case of wanted restarts / renamings etc
// and must not be a constant since in installation the same named constant would we defined multiple times then
$SCRIPT_IDENTIFIER = '014-TRANSLATE-4093-3647-3202-okapi-147.php';

//uncomment the following line, so that the file is not marked as processed:
// $this->doNotSavePhpForDebugging = false;

/* @var $this ZfExtended_Models_Installer_DbUpdater */

$updater = new FprmUpdaterTo147();
$bconf = new BconfEntity();

foreach ($bconf->loadAll() as $bconfData) {
    try {
        $bconf->load($bconfData['id']);
        $updater->updateInDir($bconf->getDataDirectory(), $bconf->getId(), $bconf->getName());
        $extensionMapping = $bconf->getExtensionMapping();
        $extensionMapping->rescanFilters();
        $bconf->repackIfOutdated(true);
    } catch (Exception $e) {
        $msg = 'ERROR rescanning filters for bconf ' . $bconf->getId() . ', "' . $bconf->getName() . '": ' . $e->getMessage();
        error_log($msg);
    }
}

$db = Zend_Db_Table::getDefaultAdapter();
$db->query("DELETE FROM `Zf_configuration` WHERE `name`='runtimeOptions.plugins.Okapi.import.okapiBconfDefaultName'");
