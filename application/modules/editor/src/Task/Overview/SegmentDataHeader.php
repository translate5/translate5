<?php
/*
START LICENSE AND COPYRIGHT

 This file is part of ZfExtended library

 Copyright (c) 2013 - 2021 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

 Contact:  http://www.MittagQI.com/  /  service (ATT) MittagQI.com

 This file may be used under the terms of the GNU LESSER GENERAL PUBLIC LICENSE version 3
 as published by the Free Software Foundation and appearing in the file lgpl3-license.txt
 included in the packaging of this file.  Please review the following information
 to ensure the GNU LESSER GENERAL PUBLIC LICENSE version 3.0 requirements will be met:
https://www.gnu.org/licenses/lgpl-3.0.txt

 @copyright  Marc Mittag, MittagQI - Quality Informatics
 @author     MittagQI - Quality Informatics
 @license    GNU LESSER GENERAL PUBLIC LICENSE version 3
             https://www.gnu.org/licenses/lgpl-3.0.txt

END LICENSE AND COPYRIGHT
*/

namespace MittagQI\Translate5\Task\Overview;

class SegmentDataHeader
{
    public const FIELD_NR = 'nr';

    public const FIELD_STATUS = 'status';

    public const FIELD_MANUAL_QS = 'manualQS';

    public const FIELD_EDIT_STATUS = 'editStatus';

    public const FIELD_MATCH_RATE = 'matchRate';

    public const FIELD_COMMENTS = 'comments';

    private array $fields = [];

    public function add(string $id, string $label): void
    {
        if (isset($this->fields[$id])) {
            throw new \InvalidArgumentException('Field with id ' . $id . ' already exists');
        }

        $this->fields[$id] = new SegmentField($id, $label);
    }

    /**
     * @return iterable<SegmentField>
     */
    public function getFields(): iterable
    {
        return $this->fields;
    }

    public function getField(string $id): ?SegmentField
    {
        return $this->fields[$id] ?? null;
    }
}
