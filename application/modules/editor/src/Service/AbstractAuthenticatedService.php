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

use ZfExtended_Exception;

/**
 * Represents a HTTP based service that is represented by a single config-value that either is a simple string or a list
 * Concrete Implementations must have a valid $configurationConfig!
 */
abstract class AbstractAuthenticatedService extends AbstractExternalService
{
    /**
     * Creates a Connector for the service.
     * Must be implemented in all inheriting connectors
     * @param string|null $url
     * @return AbstractConnector
     */
    abstract public function getConnector(string $url = null) : AbstractConnector;

    /**
     * Our Connector must be configured as well
     * @return bool
     */
    public function isProperlySetup(): bool
    {
        return $this->isConfigured() && $this->getConnector()->isConfigured();
    }

    /**
     * Base implementation for simple external services
     * @return bool
     * @throws ZfExtended_Exception
     */
    public function check(): bool
    {
        $checked = true;
        $connectorConfigured = $this->getConnector()->isConfigured();
        $urls = $this->configHelper->getValue($this->configurationConfig['name'], $this->configurationConfig['type'], true);
        if (count($urls) === 0) {
            $this->errors[] = 'There is no URL configured.';
            $checked = false;
        }
        if(!$connectorConfigured){
            $this->errors[] = 'There is no authentication configured.';
            $checked = false;
        }
        if ($connectorConfigured && count($urls) > 0){
            foreach ($urls as $url) {
                if($this->getConnector($url)->isAvailable()){
                    $this->addCheckResult($url, $this->findVersionForUrl($url));
                } else {
                    $this->errors[] = 'The configured URL "' . $url . '" is not available or the authentication failed.';
                    $checked = false;
                }
            }
        }

        return $checked && $this->checkFoundVersions();
    }
}