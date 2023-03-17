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

namespace MittagQI\Translate5\Plugins\SpellCheck\LanguageTool;

use MittagQI\Translate5\Plugins\SpellCheck\Exception\DownException;
use MittagQI\Translate5\Plugins\SpellCheck\Exception\TimeOutException;
use MittagQI\Translate5\PooledService\ServiceAbstract;
use Throwable;

final class Service extends ServiceAbstract
{

    /**
     * Note, that here the service-id (used to store states in the DB) differs from the service-name!
     */
    const SERVICE_ID = 'spellcheck';

    /**
     * Caches the adapters per service
     * Instantiating an adapter is costly as it fetches the DB for languages
     * @var Adapter[]
     */
    private static array $adapters = [];

    protected array $configurationConfig = [
        'name' => 'runtimeOptions.plugins.SpellCheck.languagetool.url.default',
        'type' => 'list',
        'url' => 'http://languagetool.:8010/v2'
    ];

    protected array $guiConfigurationConfig = [
        'name' => 'runtimeOptions.plugins.SpellCheck.languagetool.url.gui',
        'type' => 'string',
        'url' => 'http://languagetool.:8010/v2'
    ];

    protected array $importConfigurationConfig = [
        'name' => 'runtimeOptions.plugins.SpellCheck.languagetool.url.import',
        'type' => 'list',
        'url' => 'http://languagetool.:8010/v2'
    ];

    /**
     * @return string
     */
    public function getServiceId(): string
    {
        return self::SERVICE_ID;
    }

    /**
     * Creates an LanguageTool Adapter, either for the passed URL or for a random URL out of the passed pool
     * The adapters will be cached throughout an request
     * @param string|null $serviceUrl
     * @param string $servicePool
     * @return Adapter
     */
    public function getAdapter(string $serviceUrl = null, string $servicePool = 'gui'): Adapter
    {
        $url = empty($serviceUrl) ? $this->getPooledServiceUrl($servicePool) : $serviceUrl;
        if(empty($url)){
            throw new DownException('E1466');
        }
        if(!array_key_exists($url, static::$adapters)){
            static::$adapters[$url] = new Adapter($url);
        }
        return static::$adapters[$url];
    }

    /**
     * @param string $url
     * @return array{
     *     success: bool,
     *     version: string|null
     * }
     */
    /**
     * @param string $url
     * @return array
     */
    protected function checkServiceUrl(string $url): array
    {
        $result = ['success' => false, 'version' => null];
        $adapter = $this->getAdapter($url);
        // Try to check a simple phrase
        try {
            $response = $adapter->getMatches('a simple test', 'en-US');
        } catch (TimeOutException $e) {
            $result['success'] = true; // can not respond due it is processing data
            return $result;
        } catch (Throwable) {
            return $result; // all other ecxceptione are regarded as service not functioning properly
        }
        if ($response) {
            $result['success'] = ($adapter->getLastStatus() === 200);
            $result['version'] = $response?->software?->version ?? null;
        }
        return $result;
    }
}
