<?php
/*
START LICENSE AND COPYRIGHT

 This file is part of translate5

 Copyright (c) 2013 - 2025 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

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

use MittagQI\Translate5\Plugins\Okapi\Bconf\BconfEntity;

/* @var $this ZfExtended_Models_Installer_DbUpdater */

$argc = count($argv);
if (empty($this) || empty($argv) || $argc < 5 || $argc > 7 || ! isset($config)) {
    die("please dont call the script direct! Call it by using DBUpdater!\n\n");
}

$bconf = new BconfEntity();
$bconfAll = $bconf->loadAll();

foreach ($bconfAll as $bconfData) {
    try {
        $bconf = new BconfEntity();
        $bconf->load($bconfData['id']);

        if ($bconf->getPipeline()->hasToBeRepaired()) {
            $bconf->getPipeline()->repair();
            $bconf->getPipeline()->flush();
            $bconf->pack();
        }
    } catch (Exception $e) {
        $msg = 'ERROR loading bconf ' . $bconf->getId() . ', "' .
            $bconf->getName() . '": ' . $e->getMessage();
        error_log($msg);
    }
}
