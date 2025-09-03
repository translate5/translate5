<?php
/*
START LICENSE AND COPYRIGHT
 Copyright (c) 2013 - 2022 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.
 Contact:  http://www.MittagQI.com/  /  service (ATT) MittagQI.com

 This file is part of a paid plug-in for translate5.

 The translate5 core software and its freely downloadable plug-ins are licensed under an AGPLv3 open-source license
 (https://www.gnu.org/licenses/agpl-3.0.en.html).
 translate5 can be optained via the instructions that are linked at http://www.translate5.net
 For the license of translate5 itself please see http://www.translate5.net/license.txt
 Paid translate5 plugins can deviate from standard AGPLv3 licensing and therefore constitute an
 exception. As such, translate5 plug-ins can be licensed under either AGPLv3 or GPLv3 (see below for details).

 Briefly summarized, a GPLv3 license dictates the same conditions as its AGPLv3 variant, except that it
 does not require the program (plug-in, in this case) to direct users toward its download location if it is
 only being used via the web in a browser.
 This enables developers to write custom plug-ins for translate5 and keep them private, granted they
 meet the GPLv3 licensing conditions stated above.
 As the source code of this paid plug-in is under open source GPLv3 license, everyone who did obtain
 the source code could pass it on for free or paid to other companies or even put it on the web for
 free download for everyone.

 As this would undermine completely the financial base of translate5s development and the translate5
 community, we at MittagQI would not longer support a company or supply it with updates for translate5,
 that would pass on the source code to third parties.

 Of course as long as the code stays within the company who obtained it, you are free to do
 everything you want with the source code (within the GPLv3 boundaries), like extending it or installing
 it multiple times.

 This file is part of a plug-in for translate5 and may be used under the terms of the
 GNU GENERAL PUBLIC LICENSE version 3 as published by the Free Software Foundation and
 appearing in the file gpl3-license.txt included in the packaging of the translate5 plug-in
 to which this file belongs. Please review the following information to ensure the
 GNU GENERAL PUBLIC LICENSE version 3 requirements will be met:
 http://www.gnu.org/licenses/gpl.html

 Please see http://www.translate5.net/plugin-exception.txt or plugin-exception.txt in the
 root folder of translate5. This plug-in exception allows using GPLv3 for translate5 plug-ins,
 although translate5 core is licensed under AGPLv3.

 @copyright  Marc Mittag, MittagQI - Quality Informatics
 @author     MittagQI - Quality Informatics
 @license    GNU GENERAL PUBLIC LICENSE version 3 with plugin-execption
             http://www.gnu.org/licenses/gpl.html
             http://www.translate5.net/plugin-exception.txt
END LICENSE AND COPYRIGHT
*/

declare(strict_types=1);

namespace MittagQI\Translate5\Repository;

use editor_Models_SegmentField;
use editor_Models_SegmentHistoryData;

class SegmentHistoryDataRepository
{
    private \Zend_Db_Table_Abstract $db;

    public function __construct()
    {
        $this->db = (new editor_Models_SegmentHistoryData())->db;
    }

    /**
     * loads the history data entries to one segment, DESC sorted by id (creation)
     *  can be limited with $limit parameter
     *  can be filtered to one datafield with optional parameter $field
     * @param int $id
     * @param string $field optional, defaults to null which means all fields
     * @param number $limit optional, defaults to 0 which means no limit
     */
    public function loadBySegmentId($id, $field = null, $limit = 0): array
    {
        $s = $this->db->select();
        $s->where('segmentId = ?', $id);
        if (! empty($field)) {
            $s->where('name = ?', $field);
        }
        $s->order('id DESC');
        if ($limit > 0) {
            $s->limit($limit);
        }

        return $this->db->fetchAll($s)->toArray();
    }

    /**
     * Get original md5 hash for target-field, which is (for some reason)
     * available in the history only as a 2nd history-record
     */
    public function getOriginalMd5(int|string $segmentId, string $field = 'target'): ?string
    {
        $s = $this->db->select();
        $s->where('segmentId = ?', $segmentId);
        $s->where('name = ?', $field);
        $s->order('id ASC');
        $s->limit(1, 1);

        return $this->db->fetchAll($s)->toArray()[0]['originalMd5'] ?? null;
    }

    public function loadByHistoryId(int $id, $columns = []): array
    {
        $s = $this->db->select()->where('segmentHistoryId = ?', $id);
        if (! empty($columns)) {
            $s = $s->from($s->getTable(), $columns);
        }
        $row = $this->db->fetchRow($s);

        return $row !== null ? $row->toArray() : [];
    }

    public function getDurationSumByHistoryIds(array $ids, string $name = editor_Models_SegmentField::TYPE_TARGET): int
    {
        if (empty($ids)) {
            return 0;
        }
        $s = $this->db->select()->where('segmentHistoryId IN (' . implode(',', array_map('intval', $ids)) . ') AND name = ?', $name);
        $s = $s->from($s->getTable(), ['SUM(duration) AS total']);

        return (int) $this->db->fetchRow($s)->toArray()['total'];
    }
}
