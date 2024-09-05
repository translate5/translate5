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

use MittagQI\Translate5\CrossSynchronization\CrossSynchronizationConnection;
use MittagQI\Translate5\CrossSynchronization\CrossSynchronizationConnectionCustomer;

$SCRIPT_IDENTIFIER = '441-TRANSLATE-4152-extract-customers-from-cross-sync-table.php';

$db = Zend_Db_Table::getDefaultAdapter();

$connection = ZfExtended_Factory::get(CrossSynchronizationConnection::class);

$connections = $connection->loadAll();

$uniqueConnections = [];

foreach ($connections as $row) {
    $key = $row['sourceLanguageResourceId'] . '-' . $row['targetLanguageResourceId'];

    $connectionCustomer = ZfExtended_Factory::get(CrossSynchronizationConnectionCustomer::class);
    $connectionCustomer->setConnectionId($uniqueConnections[$key] ?? $row['id']);
    $connectionCustomer->setCustomerId($row['customerId']);
    $connectionCustomer->save();

    if (! isset($uniqueConnections[$key])) {
        $uniqueConnections[$key] = $row['id'];
    } else {
        $connection = ZfExtended_Factory::get(CrossSynchronizationConnection::class);
        $connection->load($row['id']);
        $connection->delete();
    }
}
