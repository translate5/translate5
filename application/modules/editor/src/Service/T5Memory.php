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
 * The t5memory languageResource Service
 *
 * IMPORTANT
 *
 * For now (09/2023), T5memory is used primarily, but still OpenTM2 is in use in production (T5memory is the linux ported mod of the windows OpenTM2)
 * Therefore in the Code there are still code-sections distinguishing between the two.
 * Whenever OpenTM2 is completely out of production, we should refeactor those. They are marked with "TODO T5MEMORY"
 *
 * T5MEMORY QUIRKS
 *
 * For now, some commands in t5memory are buggy as they lead to t5memory not answering requests anymore. Mainly reorganizing memories
 * Therefore a high Timeout is neccessary for t5memory requests to make sure, those requests can run through and we do not falsely detect t5memory as being "down"
 * This behaviour change in future versions and we should then refactor this code
 * Also it seems, one can queue reorganizations by calling reorganize while one is running but get's no response
 */
final class T5Memory extends DockerServiceAbstract
{
    /**
     * The general timeoout for t5memory that is neccessary, because t5memory does not answer requests during reorganization
     */
    public const REQUEST_TIMEOUT = 3600;

    /**
     * It is possible to have an installation without having t5memory set up
     */
    protected bool $mandatory = false;

    protected array $configurationConfig = [
        'name' => 'runtimeOptions.LanguageResources.opentm2.server', // TODO: once OpenTM2 is not used anymore, this should be renamed
        'type' => 'list',
        'url' => 'http://t5memory.:4040/t5memory',
        'healthcheck' => '/', // requesting the base url will retrieve a 200 status and the version
        'healthcheckIsJson' => true,
        'additive' => true, // leads to new endpoints being added when using autodiscovery. TODO: remove once OpenTM2 is not used anymore
    ];

    protected array $testConfigs = [
        // this leads to the application-db configs being copied to the test-DB
        'runtimeOptions.LanguageResources.opentm2.server' => null,
        'runtimeOptions.LanguageResources.opentm2.tmprefix' => null,
    ];

    /**
     * We must distinguish between t5memory and the old OpenTM2 to provide different service URLs
     * (non-PHPdoc)
     * @see DockerServiceAbstract::checkConfiguredHealthCheckUrl()
     */
    protected function checkConfiguredHealthCheckUrl(string $healthcheckUrl, string $serviceUrl, bool $addResult = true): bool
    {
        if ($this->isT5MemoryService($serviceUrl)) {
            $healthcheckUrl = rtrim($serviceUrl, '/') . '_service/resources'; // composes to "http://t5memory.:4040/t5memory_service/resources" requesting this resources url will retrieve a 200 status and the version
        }

        return parent::checkConfiguredHealthCheckUrl($healthcheckUrl, $serviceUrl, $addResult);
    }

    /**
     * We must distinguish between t5memory and the old OpenTM2, OpenTM2 will get a fixed version
     * (non-PHPdoc)
     * @see DockerServiceAbstract::findVersionInResponseBody()
     */
    protected function findVersionInResponseBody(string $responseBody, string $serviceUrl): ?string
    {
        if ($this->isT5MemoryService($serviceUrl)) {
            // older revisions returned broken JSON so we have to try JSON and then a more hacky regex approach
            $resources = json_decode($responseBody);
            $matches = [];
            if ($resources) {
                return (property_exists($resources, 'Version')) ? $resources->Version : null;
            } elseif (preg_match('~"Version"\s*:\s*"([^"]+)"~', $responseBody, $matches) === 1) {
                return (count($matches) > 0) ? $matches[1] : null;
            }

            return null;
        } else {
            // there is no other version in existance anymore
            return 'OpenTM2-1.3.0';
        }
    }

    /**
     * Distinguishes between t5memory and OpenTM2
     */
    private function isT5MemoryService(string $serviceUrl): bool
    {
        return (str_ends_with(rtrim($serviceUrl, '/'), 't5memory'));
    }
}
