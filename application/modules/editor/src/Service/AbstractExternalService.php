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

use JsonException;
use Symfony\Component\Console\Style\SymfonyStyle;
use Zend_Db_Statement_Exception;
use Zend_Exception;
use ZfExtended_Exception;
use ZfExtended_Models_Entity_Exceptions_IntegrityConstraint;
use ZfExtended_Models_Entity_Exceptions_IntegrityDuplicateKey;

/**
 * This is a service that is represented by a single config-value and represents a external/non dockerized service
 * Concrete Implementations must have a valid $configurationConfig
 */
abstract class AbstractExternalService extends AbstractHttpService
{
    /**
     * An assoc array that has to have at least 3 props:
     * "name": of the config
     * "type": of the config (either string, integer, boolean or list)
     * "url": the default-url, may be used when locating
     * "optional": optional, if set to true, this will result in only a warning if the service is not configured
     */
    protected array $configurationConfig;

    /**
     * Base implementation for simple external services
     * @param array $config : optional to inject further dependencies
     * @throws JsonException
     * @throws Zend_Db_Statement_Exception
     * @throws Zend_Exception
     * @throws ZfExtended_Exception
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityConstraint
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityDuplicateKey
     */
    public function locate(SymfonyStyle $io, mixed $url, bool $doSave = false, array $config = []): bool
    {
        $configName = $this->configurationConfig['name'];
        $configType = $this->configurationConfig['type'];

        if (array_key_exists('remove', $config) && $config['remove'] === true) {
            $this->updateConfigurationConfig($configName, $configType, [], $doSave, $io);

            return false;
        }

        if (empty($url) && array_key_exists('url', $this->configurationConfig)) {
            $url = $this->configurationConfig['url'];
        }
        if ($this->checkPotentialServiceUrl($this->getName(), $url, $io)) {
            $this->updateConfigurationConfig($configName, $configType, $url, $doSave, $io);

            return true;
        }

        return false;
    }

    /**
     * Base implementation for simple external services
     * @throws ZfExtended_Exception
     */
    public function check(): bool
    {
        $checked = true;
        $urls = $this->configHelper->getValue($this->configurationConfig['name'], $this->configurationConfig['type'], true);
        if (count($urls) === 0) {
            $this->errors[] = 'There is no URL configured.';
            $checked = false;
        } else {
            foreach ($urls as $url) {
                if (! $this->checkUrl($url)) {
                    $checked = false;
                }
            }
        }

        return $checked && $this->checkFoundVersions();
    }

    public function checkUrl(string $url): bool
    {
        if (empty($url)) {
            $this->errors[] = 'There is an empty URL set.';

            return false;
        } elseif (! $this->isUrlReachable($url)) {
            $this->errors[] = 'The configured URL "' . $url . '" is not reachable.';

            return false;
        }
        $this->addCheckResult($url, $this->findVersionForUrl($url));

        return true;
    }

    /**
     * Checks if the service URL is reachable
     */
    protected function checkPotentialServiceUrl(string $label, string $url, SymfonyStyle $io = null): bool
    {
        $result = true;
        if (! $this->isUrlReachable($url)) {
            $url = 'NONE (expected: ' . $url . ')';
            $result = false;
        }
        $this->output('Found "' . $label . '": ' . $url, $io, 'info');

        return $result;
    }

    /**
     * Simple URL check
     */
    protected function isUrlReachable(string $url): bool
    {
        $urlParsed = $this->parseUrl($url);
        if (empty($urlParsed['host'])) {
            return false;
        }

        return $this->isDnsSet(...$urlParsed);
    }

    /**
     * May be extended to evaluate a version
     */
    protected function findVersionForUrl(string $url): ?string
    {
        return null;
    }
}
