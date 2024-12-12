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

use MittagQI\Translate5\Plugins\Okapi\Bconf\FileInventory;

/**
 * Class representing the special volatile properties for X-Properties based FPRMs
 * Volatile Properties are those property-names, that can dynamically be added when editing a FPRM of the given okapi-type.
 *
 * Volatile variables always define the first name-part of the prop without a potential type
 *  cfd0=HYPERLINK                          => cfd
 *  codeFinderRules0                        => codeFinderRules
 *  rule0.codeFinderRules.rule1             => rule
 *  fontMappings.1.sourceLocalePattern      => fontMappings
 *
 * An entry has the following structure:
 *  {
 *      "type": "okf_openxml",
 *      "properties": [
 *          "cfd",
 *          "sss",
 *          "hlt",
 *          ...
 *      ]
 *  }
 */
class VolatileProperties extends FileInventory
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Relative to the static data-dir
     */
    protected string $inventoryFile = 'fprm/volatile-properties.json';

    /**
     * We do not have a corresponding folder
     */
    protected string $inventoryFolder = 'does/not/exist';

    /**
     * Finds the volatile prop-names for the given OKAPI-type
     * Will return NULL if the volatile props for the type is not known
     * (there may be types that can not have volatile props)
     * @param string $okapiType : Like "okf_xml"
     * @return string[]|null
     */
    public function getPropertyNames(string $okapiType): ?array
    {
        foreach ($this->inventory as $item) {
            if ($item->type === $okapiType) {
                return is_object($item->properties) ? (array) $item->properties : $item->properties;
            }
        }

        return null;
    }

    /**
     * Nothing to validate here ...
     */
    public function validate(): bool
    {
        return true;
    }
}
