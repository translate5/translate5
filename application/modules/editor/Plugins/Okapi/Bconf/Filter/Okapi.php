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
 * Class representing the static data for all okapi default filters a bconf can have
 */
final class editor_Plugins_Okapi_Bconf_Filter_Okapi extends editor_Plugins_Okapi_Bconf_Filter_Inventory {

    /*
     * A filter-entry has the following structure:
     {
        "id": "okf_markdown",
        "type": "okf_markdown",
        "name": "Markdown",
        "description": "Markdown files",
        "mime": "text/markdown",
        "extensions": ["md"],
        "settings": true
    },
     */

    /**
     * @var editor_Plugins_Okapi_Bconf_Filter_Okapi|null
     */
    private static ?editor_Plugins_Okapi_Bconf_Filter_Okapi $_instance = NULL;

    /**
     * Validates a default-identifier (a mapping-identifier pointing to an okapi-default and not a fprm-file)
     * @param string $identifier
     * @return bool
     */
    public static function isValidDefaultIdentifier(string $identifier) : bool {
        // avoid nonsense
        if(str_contains($identifier, editor_Plugins_Okapi_Bconf_Filters::IDENTIFIER_SEPERATOR)){
            throw new ZfExtended_BadMethodCallException('editor_Plugins_Okapi_Bconf_Filter_Okapi::isValidDefaultIdentifier can only check Okapi default filters that do not point to a fprm file');
        }
        if(count(self::instance()->findFilter(null, $identifier)) > 0){
            return true;
        }
        // as a fallback, we lookup by type. Currently, all types will have an file $type@$type as well but who knows ...
        return self::isValidType($identifier);
    }

    /**
     * validates an okapi filter type (if it generally exists)
     * @param $okapiType
     * @return bool
     */
    public static function isValidType($okapiType) : bool {
        return (count(self::instance()->findFilter($okapiType)) > 0);
    }
    /**
     * Retrieves the MimeType for a OKAPI filter type (or id)
     * @param $okapiType
     * @return string
     */
    public static function findMimeType($okapiType) : string {
        // first, try to find filter by ID (which are the un-customized types in our inventory!)
        $result = self::instance()->findFilter(null, $okapiType);
        if(count($result) > 0){
            return $result[0]->mime;
        }
        $result = self::instance()->findFilter($okapiType);
        if(count($result) > 0){
            return $result[0]->mime;
        }
        // the mime type has only informative character. Therefore we use a generic default in case of not being able to evaluate it
        return 'text/plain';
    }
    /**
     * Classic Singleton
     * @return editor_Plugins_Okapi_Bconf_Filter_Okapi
     */
    public static function instance() : editor_Plugins_Okapi_Bconf_Filter_Okapi {
        if(self::$_instance == NULL){
            self::$_instance = new editor_Plugins_Okapi_Bconf_Filter_Okapi();
        }
        return self::$_instance;
    }

    /**
     * Relative to the static data-dir
     * @var string
     */
    protected string $inventoryFile = 'fprm/okapi-filters.json';

    /**
     * Relative to the static data-dir
     * @var string
     */
    protected string $inventoryFolder = 'fprm/okapi';
}
