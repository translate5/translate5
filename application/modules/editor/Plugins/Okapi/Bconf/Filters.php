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
 * Class representing the Filters a bconf can have
 * These consist of okapi default-filters, translate5-adjusted filters and the user customized filters from the database
 */
class editor_Plugins_Okapi_Bconf_Filters {

    /**
     * @var string
     */
    const EXTENSION = 'fprm';

    /**
     * @var string
     */
    const IDENTIFIER_SEPERATOR = '@';

    /**
     * All Filters that have a GUI must be defined here
     * @var string[]
     */
    const GUIS = [];

    /**
     * Evaluates if a filter has aExtJS GUI to be edited with
     * @param string $filterType
     * @return bool
     */
    public static function hasGui(string $filterType) : bool {
        return in_array($filterType, self::GUIS);
    }

    /**
     * Small helper to create the filter-gui classname out of the filter type
     * Optionalle defines the full ExtJS path
     * @param string $filterType
     * @return string|NULL
     */
    public static function getGuiName(string $filterType, bool $fullPath=false) : ?string {
        if(!self::hasGui($filterType)){
            return NULL;
        }
        $name = ucfirst(substr($filterType, 4));
        if($fullPath){
            return 'Editor.plugins.Okapi.view.fprm.'.$name;
        }
        return $name;
    }

    /**
     * Evaluates, if the identifier represents an okapi default identifier
     * @param string $identifier
     * @return bool
     */
    public static function isOkapiDefaultIdentifier(string $identifier) : bool {
        return !str_contains($identifier, self::IDENTIFIER_SEPERATOR);
    }
    /**
     * Parses an identifier that is part of the bconf file
     * @param string $identifier
     * @return stdClass
     * @throws ZfExtended_Exception
     */
    public static function parseIdentifier(string $identifier) : stdClass {
        $parts = explode('@', $identifier);
        if(count($parts) !== 2 || substr($parts[0], 0, 4) !== 'okf_') {
            throw new ZfExtended_Exception('OKAPI FPRM identifier '.$identifier.' is not valid');
        }
        $result = new stdClass();
        $result->type = $parts[0];
        $result->id = $parts[1];
        return $result;
    }

    /**
     * @return editor_Plugins_Okapi_Bconf_Filters
     */
    public static function instance() : editor_Plugins_Okapi_Bconf_Filters {
        if(self::$_instance == NULL){
            self::$_instance = new editor_Plugins_Okapi_Bconf_Filters();
        }
        return self::$_instance;
    }


    /**
     * @var editor_Plugins_Okapi_Bconf_Filters_Okapi
     */
    private editor_Plugins_Okapi_Bconf_Filters_Okapi $okapiFilters;

    /**
     * @var editor_Plugins_Okapi_Bconf_Filters_Translate5
     */
    private editor_Plugins_Okapi_Bconf_Filters_Translate5 $translate5Filters;

    protected function __construct(){
        $this->okapiFilters = editor_Plugins_Okapi_Bconf_Filters_Okapi::instance();
        $this->translate5Filters = editor_Plugins_Okapi_Bconf_Filters_Translate5::instance();
    }

    /**
     * @param string $identifier
     * @return bool
     * @throws ZfExtended_Exception
     */
    public function isDefaultFilter(string $identifier) : bool {
        $idata = self::parseIdentifier($identifier);
        // translate5 adjusted
        if(strlen($idata->id) > 9 && substr($idata->id, 0, 10) === 'translate5'){
            return $this->isTranslate5Filter($idata->type, $idata->id);
        } else {
            return $this->isOkapiDefaultFilter($idata->type, $idata->id);
        }
    }

    /**
     * @param string $type
     * @param string $id
     * @return bool
     */
    public function isOkapiDefaultFilter(string $type, string $id) : bool {
        $filters = $this->okapiFilters->findFilter($type, $id);
        return(count($filters) > 0);
    }

    /**
     * @param string $type
     * @param string $id
     * @return bool
     */
    public function isTranslate5Filter(string $type, string $id) : bool {
        $filters = $this->translate5Filters->findFilter($type, $id);
        return(count($filters) > 0);
    }

    /**
     * Finds the fprm path for an OKAPI default filter
     * Note that this might actually return a tranlate5 adjusted filter in case it is a replacing filter
     * @param $filterId
     * @return string|null
     * @throws ZfExtended_Exception
     */
    public function getOkapiDefaultFilterPathById($filterId) : ?string {
        // fisrt, search if there is a replacing filter
        $filters = $this->translate5Filters->findOkapiDefaultReplacingFilter($filterId);
        if(count($filters) > 1){
            throw new ZfExtended_Exception('translate5 replacing filter id '.$filterId.' is ambigous!');
        } else if(count($filters) === 1){
            return $this->translate5Filters->createFprmPath($filters[0]);
        }
        // search the OKAPI filters
        $filters = $this->okapiFilters->findFilter(NULL, $filterId);
        if(count($filters) > 1){
            throw new ZfExtended_Exception('OKAPI filter id '.$filterId.' is ambigous!');
        } else if(count($filters) === 1){
            return $this->okapiFilters->createFprmPath($filters[0]);
        }
        return NULL;
    }

    /**
     * @param string $type
     * @param string $id
     * @return string|null
     * @throws ZfExtended_Exception
     */
    public function getTranslate5FilterPath(string $type, string $id) : ?string {
        $filters = $this->translate5Filters->findFilter($type, $id);
        if(count($filters) > 1){
            throw new ZfExtended_Exception('Translate5 filter identifier '.$type.self::IDENTIFIER_SEPERATOR.$id.' is ambigous!');
        } else if(count($filters) === 1){
            return $this->translate5Filters->createFprmPath($filters[0]);
        }
        return NULL;
    }
}
