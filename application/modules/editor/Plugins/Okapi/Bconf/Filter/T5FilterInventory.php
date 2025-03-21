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

/**
 * Class representing the static data for all translate5 specific filters
 * Note that the "replaceId" prop is specific to this inventory and needs to point to a valid id in okapi-filters.json
 */
final class T5FilterInventory extends FilterInventory
{
    /*
     * A filter-entry has the following structure:
     {
        "id": "translate5",
        "type": "okf_openoffice",
        "replaceId": "okf_openoffice",
        "name": "t5 OpenOffice.org Documents",
        "description": "translate5 adjusted filter for OpenOffice.org documents",
        "mime": "application/x-openoffice",
        "extensions": ["odp","ods","odt"]
    },
     */

    private static ?T5FilterInventory $_instance = null;

    /**
     * Classic Singleton
     */
    public static function instance(): T5FilterInventory
    {
        if (self::$_instance == null) {
            self::$_instance = new T5FilterInventory();
        }

        return self::$_instance;
    }

    public static function isTranslate5Id(string $id): bool
    {
        return (strlen($id) > 9 && str_starts_with($id, 'translate5'));
    }

    /**
     * Relative to the static data-dir
     */
    protected string $inventoryFile = 'fprm/translate5-filters.json';

    /**
     * Relative to the static data-dir
     */
    protected string $inventoryFolder = 'fprm/translate5';

    protected function __construct()
    {
        parent::__construct();
        // unneccessary to encode this in the JSON ... all T5 adjusted filters must have settings
        foreach ($this->inventory as $index => $item) {
            $this->inventory[$index]->settings = true;
        }
    }

    /**
     * Retrieves a Translate5 adjusted filter that replaces an OKAPI default filter
     */
    public function findOkapiDefaultReplacingFilter(string $filterId): array
    {
        $result = [];
        foreach ($this->inventory as $index => $item) {
            if ($item->replaceId === $filterId && ! empty($item->replaceId)) {
                $result[] = $item;
            }
        }

        return $result;
    }

    /**
     * This generates an extension-mapping for all translate-5 extensions defined in our store
     * This is used to re-pack outdated system-bconf's
     */
    public function getExtensionMappingEntries(): array
    {
        $extensionMapping = [];
        foreach ($this->inventory as $item) {
            if (! empty($item->extensions)) {
                $identifier = $this->createFprmFilename($item);
                foreach ($item->extensions as $extension) {
                    $extensionMapping[$extension] = $identifier;
                }
            }
        }

        return $extensionMapping;
    }
}
