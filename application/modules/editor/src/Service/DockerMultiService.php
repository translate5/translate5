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

/**
 * This represents a multi-url service where the URLs are seperated to "gui" (for editing), "import" and "default"
 * The default-service is represented by $configurationConfig while gui and import have their own setup-data
 */
abstract class DockerMultiService extends DockerService {

    /**
     * Structure see DockerService::configurationConfig
     * @var array
     */
    protected array $guiConfigurationConfig;

    /**
     * Structure see DockerService::configurationConfig
     * @var array
     */
    protected array $importConfigurationConfig;

    /**
     * Retrieves the default service url's for an multi-service
     * @return array
     * @throws ZfExtended_Exception
     */
    public function getDefaultServiceUrls(): array
    {
        return $this->getConfigValueFromName($this->configurationConfig['name'], $this->configurationConfig['type'], true);
    }

    /**
     * Retrieves the default service url's for an multi-service
     * @return array
     * @throws ZfExtended_Exception
     */
    public function getGuiServiceUrls(): array
    {
        return $this->getConfigValueFromName($this->guiConfigurationConfig['name'], $this->guiConfigurationConfig['type'], true);
    }

    /**
     * Retrieves the default service url's for an multi-service
     * @return array
     * @throws ZfExtended_Exception
     */
    public function getImportServiceUrls(): array
    {
        return $this->getConfigValueFromName($this->importConfigurationConfig['name'], $this->importConfigurationConfig['type'], true);
    }

    public function check(): bool
    {
        $defaultUrls = $this->getDefaultServiceUrls();
        $guiUrls = $this->getGuiServiceUrls();
        $importUrls = $this->getImportServiceUrls();
        if(empty($defaultUrls)){
            $this->errors[] = 'There are no default-URLs configured.';
        }
        if(empty($guiUrls)){
            $this->warnings[] = 'There are no gui-URLs configured.';
        }
        if(empty($importUrls)){
            $this->warnings[] = 'There are no import-URLs configured.';
        }
        $checked = true;
        $urls = array_unique(array_merge($defaultUrls, $guiUrls, $importUrls));
        foreach ($urls as $url) {
            if (empty($url)) {
                $this->errors[] = 'There is an empty service-URL set.';
                $checked = false;
            } else if (!$this->checkConfiguredServiceUrl($url)) {
                $this->errors[] = 'The configured service-URL "' . $url . '" is not reachable.';
                $checked = false;
            } else if(!$this->customServiceCheck($url)){
                $this->errors[] = 'The configured service-URL "' . $url . '" is not working properly.';
                $checked = false;
            }
        }
        return $checked;
    }

    /**
     * Can be used to add further special checks in inheriting classes
     * @return bool
     */
    protected function customServiceCheck(string $url): bool
    {
        return true;
    }
}
