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
 translate5: Please see http://www.translate5.net/plugin-exception.txt or
 plugin-exception.txt in the root folder of translate5.

 @copyright  Marc Mittag, MittagQI - Quality Informatics
 @author     MittagQI - Quality Informatics
 @license    GNU AFFERO GENERAL PUBLIC LICENSE version 3 with plugin-execption
             http://www.gnu.org/licenses/agpl.html http://www.translate5.net/plugin-exception.txt

END LICENSE AND COPYRIGHT
*/

/**#@+
 * @author Marc Mittag
 * @package portal
 * @version 2.0
 *
 */

declare(strict_types=1);

use MittagQI\Translate5\Segment\SegmentHistoryAggregation;
use MittagQI\Translate5\Statistics\AbstractStatisticsDB;
use MittagQI\Translate5\Statistics\Factory;

class Models_SystemRequirement_Modules_DbStatistics extends ZfExtended_Models_SystemRequirement_Modules_Abstract
{
    /**
     * @throws Zend_Exception
     * @see ZfExtended_Models_SystemRequirement_Modules_Abstract::validate()
     */
    public function validate(): ZfExtended_Models_SystemRequirement_Result
    {
        if (Zend_Registry::isRegistered('statistics')) {
            $db = Zend_Registry::get('statistics');
        } else {
            $db = Factory::createDb();
        }

        $this->result->id = 'statisticsdb';
        $this->result->name = 'Statistics ' . (str_ends_with(get_class($db), 'MariaDB') ? 'Tables' : 'Database');

        $this->validateDb($db);

        return $this->result;
    }

    private function validateDb(AbstractStatisticsDB $db)
    {
        if (! $db->isAlive()) {
            $this->result->error[] = "Connection to Statistics DB failed";
        } else {
            if (str_ends_with(get_class($db), 'SQLite')) {
                $config = \Zend_Registry::get('config');
                $dbFile = $config->resources->db->statistics?->sqliteDbname;
                if (! empty($dbFile) && ! is_writeable($dbFile)) {
                    $this->result->error[] = "File is not writeable: $dbFile";
                }
            }
            foreach ([SegmentHistoryAggregation::TABLE_NAME, SegmentHistoryAggregation::TABLE_NAME_LEV] as $tableName) {
                if (! $db->tableExists($tableName)) {
                    $this->result->error[] = "Error accessing table '$tableName'";
                }
            }
        }
    }
}
