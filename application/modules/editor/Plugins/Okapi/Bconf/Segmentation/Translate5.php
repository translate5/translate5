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

/**
 * Class representing the static data for the translate5 specific SRX files
 * The repository will keep a list of all versioned SRX-files from version 1 on
 */
final class editor_Plugins_Okapi_Bconf_Segmentation_Translate5 extends editor_Plugins_Okapi_Bconf_FileInventory {

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

    /**
     * @var editor_Plugins_Okapi_Bconf_Segmentation_Translate5|null
     */
    private static ?editor_Plugins_Okapi_Bconf_Segmentation_Translate5 $_instance = NULL;

    /**
     * Classic Singleton
     * @return editor_Plugins_Okapi_Bconf_Segmentation_Translate5
     */
    public static function instance() : editor_Plugins_Okapi_Bconf_Segmentation_Translate5 {
        if(self::$_instance == NULL){
            self::$_instance = new editor_Plugins_Okapi_Bconf_Segmentation_Translate5();
        }
        return self::$_instance;
    }

    /**
     * Relative to the static data-dir
     * @var string
     */
    protected string $inventoryFile = 'srx/translate5-segmentation.json';

    /**
     * Relative to the static data-dir
     * @var string
     */
    protected string $inventoryFolder = 'srx/translate5';

    /**
     * @param string $field: must be "source"|"target"
     * @return string
     */
    public function findCurrentPath(string $field) : string {
        return $this->inventory[0]->$field;
    }

    /**
     * Retrieves the current SRX
     * @return stdClass
     */
    public function findCurrent() : stdClass {
        return $this->inventory[0];
    }

    /**
     * Retrieves an Translate5 adjusted srx by it's hash
     * @param string $hash
     * @return stdClass|null
     */
    public function findByHash(string $hash) : ?stdClass {
        $result = [];
        foreach($this->inventory as $index => $item){
            if($item->sourceHash === $hash || $item->targetHash === $hash){
                return $item;
            }
        }
        return NULL;
    }

    /**
     * The Prequesite for this AOI is, that the srx-items are ordered with version DESC
     * @param int $version
     * @return stdClass|null
     */
    public function findByVersion(int $version) : ?stdClass {
        $result = [];
        foreach($this->inventory as $index => $item){
            if($item->version <= $version){
                return $item;
            }
        }
        return NULL;
    }

    /**
     * Creates the patj for the source/target srx
     * @param stdClass $srxItem
     * @param string $field: either "source" or "target"
     * @return string
     * @throws editor_Models_ConfigException
     * @throws editor_Plugins_Okapi_Exception
     */
    public function createSrxPath(stdClass $srxItem, string $field='source') : string {
        return $this->getFolderPath().'/'.$srxItem->$field;
    }

    /**
     * Checks if all SRX files of the inventory are present
     * Used in the API Test for the Bconf filters
     * @return bool
     */
    public function validate(){
        $valid = true;
        $lastVersion = NULL;
        foreach($this->inventory as $index => $srxFile){
            $sourceSrx = $this->createSrxPath($srxFile, 'source');
            $targetSrx = $this->createSrxPath($srxFile, 'target');
            if(!file_exists($sourceSrx)){
                error_log('Okapi Segmentation Inventory '.get_class($this).': Missing SRX file '.$sourceSrx);
                $valid = false;
            }
            if($sourceSrx != $targetSrx && !file_exists($targetSrx)){
                error_log('Okapi Segmentation Inventory '.get_class($this).': Missing SRX file '.$targetSrx);
                $valid = false;
            }
            if($lastVersion !== NULL && $srxFile->version >= $lastVersion){
                error_log('Okapi Segmentation Inventory '.get_class($this).': Wrong Version in item '.($index - 1));
                $valid = false;
            }
            if(strlen($srxFile->sourceHash) !== 32 || strlen($srxFile->targetHash) !== 32){
                error_log('Okapi Segmentation Inventory '.get_class($this).': Wrong Hashes in item '.$index);
                $valid = false;
            }
        }
        return $valid;
    }
}
