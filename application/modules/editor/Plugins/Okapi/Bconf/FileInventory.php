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

namespace MittagQI\Translate5\Plugins\Okapi\Bconf;

use editor_Plugins_Okapi_Init;

/**
 * Class representing the static data of a filebase inventory with a JSON inventory-file
 */
abstract class FileInventory
{
    /**
     * Relative to the static data-dir
     */
    protected string $inventoryFile;

    /**
     * Relative to the static data-dir
     */
    protected string $inventoryFolder;

    protected array $inventory;

    protected function __construct()
    {
        $this->inventory = json_decode(file_get_contents($this->getFilePath()));
    }

    public function getFolderPath(): string
    {
        return editor_Plugins_Okapi_Init::getDataDir() . $this->inventoryFolder;
    }

    public function getFilePath(): string
    {
        return editor_Plugins_Okapi_Init::getDataDir() . $this->inventoryFile;
    }

    /**
     * Retrieves all items in the inventory
     */
    public function getInventory(): array
    {
        return $this->inventory;
    }

    /**
     * Checks if all linked files of the inventory are present
     */
    abstract public function validate(): bool;
}
