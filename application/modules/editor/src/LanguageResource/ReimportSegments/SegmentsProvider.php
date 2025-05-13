<?php
/*
START LICENSE AND COPYRIGHT

 This file is part of translate5

 Copyright (c) 2013 - 2021 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

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

namespace MittagQI\Translate5\LanguageResource\ReimportSegments;

use editor_Models_Segment as Segment;
use editor_Models_Segment_AutoStates as AutoStates;
use editor_Models_Segment_Iterator;
use MittagQI\Translate5\Segment\FilteredIterator;
use ZfExtended_Models_Filter_ExtJs6;

class SegmentsProvider
{
    public function __construct(
        private readonly Segment $segment
    ) {
    }

    public function getSegments(string $taskGuid, array $filters): iterable
    {
        if (empty($filters)) {
            return new editor_Models_Segment_Iterator($taskGuid);
        }

        $filter = new ZfExtended_Models_Filter_ExtJs6($this->segment);
        $this->segment->filterAndSort($filter);

        if (isset($filters[ReimportSegmentsOptions::FILTER_TIMESTAMP])) {
            // all loaded segments are filtered by the given timestamp
            $filterObject = new \stdClass();
            $filterObject->field = 'timestamp';
            $filterObject->type = 'string';
            $filterObject->comparison = 'eq';
            $filterObject->value = $filters[ReimportSegmentsOptions::FILTER_TIMESTAMP];

            $this->segment->getFilter()->addFilter($filterObject);
        }

        if ($filters[ReimportSegmentsOptions::FILTER_ONLY_EDITED] ?? false) {
            // all loaded segments are filtered by the states
            $filterObject = new \stdClass();
            $filterObject->field = 'autoStateId';
            $filterObject->type = 'notInList';
            $filterObject->comparison = 'in';
            $filterObject->value = [
                AutoStates::NOT_TRANSLATED,
                AutoStates::PRETRANSLATED,
                AutoStates::LOCKED,
                AutoStates::BLOCKED,
            ];

            $this->segment->getFilter()->addFilter($filterObject);
        }

        return new FilteredIterator($taskGuid, $this->segment);
    }
}
