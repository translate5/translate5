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

namespace MittagQI\Translate5\Task\CustomFields;

use MittagQI\Translate5\Acl\TaskCustomField;
use ZfExtended_Acl;

/**
 * Class responsible for filtering custom fields based on various criteria
 */
class FieldFilter
{
    public function __construct(
        private ZfExtended_Acl $acl,
    ) {
    }

    public static function create(): self
    {
        return new self(
            ZfExtended_Acl::getInstance(),
        );
    }

    /**
     * @throws \Zend_Acl_Exception
     */
    public function getAllowedCustomFieldsByUserRoles(Field $field, array $userRoles): array
    {
        $all = $field->loadAllSorted();
        $allowed = [];
        foreach ($all as $field) {
            if ($this->acl->isInAllowedRoles($userRoles, TaskCustomField::ID, "customField{$field['id']}")) {
                $allowed[] = $field;
            }
        }

        return $allowed;
    }

    /**
     * Get allowed custom fields filtered for instant translate
     * @throws \Zend_Acl_Exception
     */
    public function getAllowedFieldsForInstantTranslate(Field $field, array $userRoles): array
    {
        $allowedFields = $this->getAllowedCustomFieldsByUserRoles($field, $userRoles);

        return $this->filterByPlace($allowedFields, 'instantTranslate');
    }

    /**
     * Filter fields by a specific place to show
     */
    private function filterByPlace(array $fields, string $place): array
    {
        $out = [];

        foreach ($fields as $field) {
            $places = $this->parsePlacesToShow($field);
            if (in_array($place, $places, true)) {
                $out[] = $field;
            }
        }

        return $out;
    }

    /**
     * Parse the placesToShow field into an array
     */
    private function parsePlacesToShow(array $field): array
    {
        $placesToShow = (string) ($field['placesToShow'] ?? '');

        return array_map('trim', explode(',', $placesToShow));
    }
}
