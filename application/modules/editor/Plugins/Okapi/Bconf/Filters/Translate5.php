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
 * Note that the "replaceId" prop is specific to this inventory and needs to point to a valid id in okapi-filters.json
 */
final class editor_Plugins_Okapi_Bconf_Filters_Translate5 extends editor_Plugins_Okapi_Bconf_Filters_Inventory {

    private static ?editor_Plugins_Okapi_Bconf_Filters_Translate5 $_instance = NULL;

    /**
     * Classic Singleton
     * @return editor_Plugins_Okapi_Bconf_Filters_Translate5
     */
    public static function instance() : editor_Plugins_Okapi_Bconf_Filters_Translate5 {
        if(self::$_instance == NULL){
            self::$_instance = new editor_Plugins_Okapi_Bconf_Filters_Translate5();
        }
        return self::$_instance;
    }

    /**
     * @param string $identifier
     * @return bool
     */
    public static function isTranslate5Id(string $id) : bool {
        return (strlen($id) > 9 && substr($id, 0, 10) === 'translate5');
    }

    /**
     * Relative to the static data-dir
     * @var string
     */
    protected string $inventoryFile = 'fprm/translate5-filters.json';

    /**
     * Relative to the static data-dir
     * @var string
     */
    protected string $inventoryFolder = 'fprm/translate5';

    protected function __construct(){
        parent::__construct();
        // unneccessary to encode this in the JSON ... all T5 adjusted filters must have settings
        foreach($this->inventory as $index => $item){
            $this->inventory[$index]->settings = true;
        }
    }

    /**
     * Retrieves an Translate5 adjusted filter that replaces an OKAPI default filter
     * @param string $filterId
     * @return array
     */
    public function findOkapiDefaultReplacingFilter(string $filterId){
        $result = [];
        foreach($this->inventory as $index => $item){
            if($item->replaceId === $filterId && !empty($item->replaceId)){
                $result[] = $item;
            }
        }
        return $result;
    }
}
