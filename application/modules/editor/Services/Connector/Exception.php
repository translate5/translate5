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

/**
 * Evaluates to a ZfExtended_BadGateway exception!
 */
class editor_Services_Connector_Exception extends ZfExtended_BadGatewayErrorCode {
    /**
     * @var string
     */
    protected $domain = 'editor.languageresource.service.connector';
    
    static protected $localErrorCodes = [
        'E1282' => 'Language resource communication error.',
        'E1288' => 'The language code [{languageCode}] from resource [{resourceName}] is not valid or does not exist in the translate5 language code collection.',
        'E1311' => 'Could not connect to language resource {service}: server not reachable',
        'E1312' => 'Could not connect to language resource {service}: timeout on connection to server',
        'E1370' => 'Empty response from language resource {service}',
        'E1313' => 'The queried language resource {service} returns an error.',
    ];
    
    protected function setDuplication() {
        parent::setDuplication();
        ZfExtended_Logger::addDuplicatesByMessage('E1311', 'E1312', 'E1370');
    }
}