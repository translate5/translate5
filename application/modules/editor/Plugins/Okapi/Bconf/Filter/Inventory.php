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
 * Class representing the static data for all translate5 specific filters
 */
abstract class editor_Plugins_Okapi_Bconf_Filter_Inventory {

    /*
     * A filter-entry has the following structure:
     {
        "id": "okf_xml-AndroidStrings",
        "type": "okf_xml",
        "name": "Android Strings",
        "description": "Android Strings XML documents",
        "mime": "text/xml",
        "extensions": ["xml"],
        "settings": true,
        "gui": false
    },
     */

    /**
     * Relative to the static data-dir
     * @var string
     */
    protected string $inventoryFile;

    /**
     * Relative to the static data-dir
     * @var string
     */
    protected string $inventoryFolder;

    /**
     * @var stdClass[]
     */
    protected array $inventory;

    protected function __construct(){
        $this->inventory = json_decode(file_get_contents($this->getFilePath()));
    }

    /**
     * Checks if all FPRM files of the inventory are present
     * Used in the API Test for the Bconf filters
     * @return bool
     */
    public function validate(){
        foreach($this->inventory as $filter){
            if($filter->settings !== false && !file_exists($this->createFprmPath($filter))){
                error_log('Okapi Filter Inventory '.get_class($this).': Missing FPRM file '.$this->createFprmPath($filter));
                // return false;
            }
        }
        return true;
    }

    /**
     * @return string
     * @throws editor_Models_ConfigException
     * @throws editor_Plugins_Okapi_Exception
     */
    public function getFolderPath() : string {
        return editor_Plugins_Okapi_Init::getDataDir() . $this->inventoryFolder;
    }

    /**
     * @return string
     * @throws editor_Models_ConfigException
     * @throws editor_Plugins_Okapi_Exception
     */
    public function getFilePath() : string {
        return editor_Plugins_Okapi_Init::getDataDir() . $this->inventoryFile;
    }

    /**
     * @param stdClass $filterItem
     * @return string
     */
    public function createFprmFilename(stdClass $filterItem) : string {
        return $filterItem->type.'@'.$filterItem->id;
    }

    /**
     * @param stdClass $filterItem
     * @return string
     * TODO OKAPI: Extension as class constant
     */
    public function createFprmPath(stdClass $filterItem) : string {
        return $this->getFolderPath().'/'.$this->createFprmFilename($filterItem).'.fprm';
    }

    /**
     * Finds filters by type and id
     * @param string $type
     * @param string|null $id
     * @return array
     */
    public function findFilter(string $type, string $id=NULL) : array {
        $result = [];
        foreach($this->inventory as $item){
            if($item->type === $type && ($id === NULL || $item->id === $id)){
                $result[] = $item;
            }
        }
        return $result;
    }
 
}
