<?php
/*
 START LICENSE AND COPYRIGHT

 This file is part of translate5

 Copyright (c) 2013 - 2022 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

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

namespace MittagQI\Translate5\Plugins\Okapi\Bconf\Filter;

use editor_Models_ConfigException;
use MittagQI\Translate5\Plugins\Okapi\Bconf\FileInventory;
use MittagQI\Translate5\Plugins\Okapi\Bconf\Filters;
use MittagQI\Translate5\Plugins\Okapi\OkapiException;
use stdClass;

/**
 * Class representing the static data for filter/fprm inventories
 */
abstract class FilterInventory extends FileInventory
{
    public function createFprmFilename(stdClass $filterItem): string
    {
        return $filterItem->type . '@' . $filterItem->id;
    }

    /**
     * @throws editor_Models_ConfigException
     * @throws OkapiException
     */
    public function createFprmPath(stdClass $filterItem): string
    {
        return $this->getFolderPath() . '/' . $this->createFprmFilename($filterItem) . '.' . FilterEntity::EXTENSION;
    }

    /**
     * Finds filters by type and id
     * @return stdClass[]
     */
    public function findFilter(string $type = null, string $id = null): array
    {
        if ($type === null && $id === null) {
            return [];
        }
        $result = [];
        foreach ($this->inventory as $item) {
            if (($item->type === $type || $type === null) && ($item->id === $id || $id === null)) {
                $result[] = $item;
            }
        }

        return $result;
    }

    /**
     * Retrieves the rows for the frontend
     */
    public function getGridRows(int $startIndex = 0): array
    {
        $rows = [];
        foreach ($this->inventory as $item) {
            $rows[] = [
                'id' => $startIndex,
                'okapiType' => $item->type,
                'okapiId' => $item->id,
                'name' => $item->name,
                'description' => $item->description,
                'mimeType' => $item->mime,
                // the identifier can act as a unique ID in the frontend, akapiType and okapiId are not unique
                'identifier' => Filters::createIdentifier($item->type, $item->id),
                'editable' => $item->settings && Filters::hasGui($item->type),
                'isCustom' => false,
                'guiClass' => Filters::getGuiClass($item->type),
            ];
            $startIndex++;
        }

        return $rows;
    }

    /**
     * Checks if all FPRM files of the inventory are present
     * Used in the API Test for the Bconf filters
     * @throws editor_Models_ConfigException
     * @throws OkapiException
     */
    public function validate(): bool
    {
        $valid = true;
        foreach ($this->inventory as $filter) {
            if ($filter->settings !== false && ! file_exists($this->createFprmPath($filter))) {
                error_log('Okapi Filter Inventory ' . get_class($this) . ': Missing FPRM file '
                    . $this->createFprmPath($filter));
                $valid = false;
            }
        }

        return $valid;
    }
}
