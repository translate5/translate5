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

namespace MittagQI\Translate5\Plugins\Okapi\Bconf\Segmentation;

use editor_Models_ConfigException;
use editor_Plugins_Okapi_Init;
use MittagQI\Translate5\Plugins\Okapi\Bconf\FileInventory;
use MittagQI\Translate5\Plugins\Okapi\OkapiException;
use stdClass;

/**
 * Class representing the static data for the translate5 specific SRX files
 * The repository will keep a list of all versioned SRX-files from version 1 on
 */
final class T5SrxInventory extends FileInventory
{
    /*
     * A srx-entry has the following structure:
    {
        "version": 1,
        "source": "languages-1.srx",
        "target": "languages-1.srx",
        "sourceHash": "3f3c76d610eb4a7848bedeeb6734c4de",
        "targetHash": "3f3c76d610eb4a7848bedeeb6734c4de"
    }
     */

    private static ?T5SrxInventory $_instance = null;

    /**
     * Classic Singleton
     */
    public static function instance(): T5SrxInventory
    {
        if (self::$_instance == null) {
            self::$_instance = new T5SrxInventory();
        }

        return self::$_instance;
    }

    /**
     * Relative to the static data-dir
     */
    protected string $inventoryFile = 'srx/translate5-segmentation.json';

    /**
     * Relative to the static data-dir
     */
    protected string $inventoryFolder = 'srx/translate5';

    public function findCurrentPath(string $field): string
    {
        return $this->inventory[0]->$field;
    }

    /**
     * Retrieves the current SRX
     */
    public function findCurrent(): stdClass
    {
        return $this->inventory[0];
    }

    /**
     * Retrieves an Translate5 adjusted srx by it's hash
     */
    public function findByHash(string $hash): ?stdClass
    {
        foreach ($this->inventory as $index => $item) {
            if ($item->sourceHash === $hash || $item->targetHash === $hash) {
                return $item;
            }
        }

        return null;
    }

    /**
     * The Prequesite for this AOI is, that the srx-items are ordered with version DESC
     */
    public function findByVersion(int $version): ?stdClass
    {
        foreach ($this->inventory as $index => $item) {
            if ($item->version <= $version) {
                return $item;
            }
        }

        return null;
    }

    /**
     * Creates the patj for the source/target srx
     * @throws editor_Models_ConfigException
     * @throws OkapiException
     */
    public function createSrxPath(stdClass $srxItem, string $field = 'source'): string
    {
        return $this->getFolderPath() . '/' . $srxItem->$field;
    }

    /**
     * Checks if all SRX files of the inventory are present
     * Used in the API Test for the Bconf filters
     * @throws editor_Models_ConfigException
     * @throws OkapiException
     */
    public function validate(): bool
    {
        $valid = true;
        foreach ($this->inventory as $index => $srxFile) {
            $sourceSrx = $this->createSrxPath($srxFile, 'source');
            $targetSrx = $this->createSrxPath($srxFile, 'target');
            if (! file_exists($sourceSrx)) {
                error_log('Okapi Segmentation Inventory ' . get_class($this) . ':'
                    . ' Missing SRX file ' . $sourceSrx);
                $valid = false;
            }
            if ($sourceSrx != $targetSrx && ! file_exists($targetSrx)) {
                error_log('Okapi Segmentation Inventory ' . get_class($this) . ':'
                    . ' Missing SRX file ' . $targetSrx);
                $valid = false;
            }
            if ((int) $srxFile->version > editor_Plugins_Okapi_Init::BCONF_VERSION_INDEX) {
                error_log('Okapi Segmentation Inventory ' . get_class($this) . ':'
                    . ' Wrong Version in item ' . ($index - 1));
                $valid = false;
            }
            if (strlen($srxFile->sourceHash) !== 32 || strlen($srxFile->targetHash) !== 32) {
                error_log('Okapi Segmentation Inventory ' . get_class($this) . ':'
                    . ' Wrong Hashes in item ' . $index);
                $valid = false;
            }
        }

        return $valid;
    }
}
