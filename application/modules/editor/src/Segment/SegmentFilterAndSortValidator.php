<?php

namespace MittagQI\Translate5\Segment;

use editor_Models_Segment;
use MittagQI\ZfExtended\Models\Filter\ValidatorInterface;
use stdClass;

/**
 * Validate and remove the invalide filters and the sorts for the provided segment entity.
 */
class SegmentFilterAndSortValidator
{
    public function __construct(
        private ValidatorInterface $validator,
        private editor_Models_Segment $entity
    ) {
    }

    public function validateAndRemoveInvalid(array $allowedTableFields, array $mappedFields): void
    {
        $filters = $this->entity->getFilter()->getFilters();
        $sorters = $this->entity->getFilter()->getSort();

        /* @var $filter stdClass */
        foreach ($filters as $filter) {
            if (! $this->validator->validate($filter->field, $allowedTableFields, $mappedFields)) {
                $this->entity->getFilter()->deleteFilter($filter->field);
            }
        }

        /* @var $sorter stdClass */
        foreach ($sorters as $sorter) {
            if (! $this->validator->validate($sorter->property, $allowedTableFields, $mappedFields)) {
                $this->entity->getFilter()->deleteSort($sorter->property);
            }
        }
    }
}
