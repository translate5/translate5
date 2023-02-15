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

namespace MittagQI\Translate5\Service;

use Zend_Http_Client;
use ZfExtended_Factory;

/**
 * The t5memory languageResource Service
 */
final class T5Memory extends DockerServiceAbstract {

    protected array $configurationConfig = [
        'name' => 'runtimeOptions.LanguageResources.opentm2.server',
        'type' => 'list',
        'url' => 'http://t5memory.:4040/t5memory',
        'healthcheck' => '_service/resources', // composes to "http://t5memory.:4040/t5memory_service/resources" requesting this resources url will retrieve a 200 status and the version
        'additive' => true // TODO: is this neccessary ?
    ];

    protected function checkConfiguredHealthCheckUrl(string $url): bool
    {
        // special healthcheck url also retrieves the version
        try {
            $httpClient = ZfExtended_Factory::get(Zend_Http_Client::class);
            $httpClient->setUri($url);
            // $httpClient->setHeaders('Accept', 'application/json');
            $response = $httpClient->request('GET');
            // the status request must return 200
            if($response->getStatus() === 200) {
                // for now there is a BUG in the t5memory response for /resources and we have to parse dirtily
                $matches = [];
                if(preg_match('~"Version"\s*:\s*"([^"]+)"~', $response->getBody(), $matches) === 1){
                    $this->version = (count($matches) > 0) ? $matches[1] : null;
                }
                /* implementation when BUG is fixed
                $props = json_decode($response->getBody());
                if$props && property_exists($props, 'Version')){
                    $this->version = $props->Version;
                }
                */
                return true;
            }
            return false;

        } catch (Throwable $e){

            return false;
        }
    }
}
