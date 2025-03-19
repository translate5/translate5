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

declare(strict_types=1);

namespace MittagQI\Translate5\Statistics\Helpers;

use MittagQI\Translate5\Configuration\KeyValueStorage;
use MittagQI\Translate5\Segment\SegmentHistoryAggregation;
use Zend_EventManager_Event;

class SyncEditable
{
    public const paramLastSegmentId = 'aggregation.editable.lastSegmentId';

    public const paramLastSegmentHistoryId = 'aggregation.editable.lastSegmentHistoryId';

    public static function sync(Zend_EventManager_Event $event)
    {
        $storage = new KeyValueStorage();
        $lastSegmentId = (int) $storage->get(self::paramLastSegmentId);
        $lastSegmentHistoryId = (int) $storage->get(self::paramLastSegmentHistoryId);

        $db = \Zend_Db_Table::getDefaultAdapter();
        $newLastSegmentHistoryId = (int) $db->fetchOne('SELECT MAX(id) FROM LEK_segment_history');
        $newLastSegmentId = (int) $db->fetchOne('SELECT MAX(id) FROM LEK_segments');

        $segmentIds = $db->fetchCol('SELECT DISTINCT segmentId FROM LEK_segment_history WHERE id > ' . $lastSegmentHistoryId .
            ' AND segmentId <= ' . $lastSegmentId);

        $result = $db->fetchPairs('SELECT id,editable FROM LEK_segments WHERE id > ' . $lastSegmentId .
            ($segmentIds ? ' OR id IN (' . implode(',', $segmentIds) . ')' : ''));

        if ($result) {
            $aggregation = SegmentHistoryAggregation::create();
            foreach ($result as $segmentId => $editable) {
                $aggregation->updateEditable($segmentId, (int) $editable);
            }
        }

        // save into storage, do it in aggregation command as well
        if ($newLastSegmentHistoryId > $lastSegmentHistoryId) {
            $storage->set(self::paramLastSegmentHistoryId, $newLastSegmentHistoryId);
        }
        if ($newLastSegmentId > $lastSegmentId) {
            $storage->set(self::paramLastSegmentId, $newLastSegmentId);
        }
    }
}
