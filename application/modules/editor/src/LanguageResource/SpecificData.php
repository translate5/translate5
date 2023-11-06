<?php
/*
START LICENSE AND COPYRIGHT

 This file is part of translate5

 Copyright (c) 2013 - 2021 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

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

declare(strict_types=1);

namespace MittagQI\Translate5\LanguageResource;

use Zend_Exception;
use Zend_Json;
use Zend_Json_Exception;
use ZfExtended_Exception;
use ZfExtended_Zendoverwrites_Translate;

class SpecificData
{
    /**
     * Transforms the specificData for the frontend, adds translations for the array-keys,
     *
     * TODO FIXME: the whole specific-data handling should be further improved, frontend & backend
     * Generally, the getSpecificData method should return an instance of this class, that then can encapsulate
     * the needed processings
     *
     * @param array $specificData
     * @param string $serviceName
     * @return array
     * @throws Zend_Exception
     * @throws ZfExtended_Exception
     */
    public static function localize(array $specificData, string $serviceName): array
    {
        if (empty($specificData)){
            return [];
        }

        // UGLY: For historic reasons, the translations of specificData fields
        // are in the xliffs as <fieldName>_<serviceName> e.g. "fileName_OpenTM2"
        // therefore we add a seperate "localizedKeys"-node that will contain the localized key-names
        $translate = ZfExtended_Zendoverwrites_Translate::getInstance();
        $keys = array_keys($specificData);
        $localizations = [];

        // A specific-data value never must be called "localizedKeys"
        if (in_array('localizedKeys', $keys)) {
            throw new ZfExtended_Exception('A LEK_languageresource.specificData JSON property must not be called "localizedKeys"!');
        }

        // fileName shall be the first key - if present
        if (in_array('fileName', $keys)) {
            $localizations['fileName'] = $translate->_if('fileName_' . $serviceName, 'File name');
        }
        // then the others
        foreach ($keys as $key) {
            if ($key !== 'status' && $key !== 'fileName') {
                $localizations[$key] = $translate->_if($key . '_' . $serviceName, ucfirst($key));
            }
        }
        // status as the last. Note, that "status" initially was not shown, but it is much cleaner to show it and it does not hurt to do so
        if (in_array('status', $keys)) {
            $localizations['status'] = 'Status';
        }
        // add key-localizations to the specific data
        $specificData['localizedKeys'] = $localizations;

        return $specificData;
    }
}
