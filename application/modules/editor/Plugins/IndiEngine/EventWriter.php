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

namespace MittagQI\Translate5\Plugins\IndiEngine;

use ReflectionException;
use Zend_Config;
use Zend_Db_Statement_Exception;
use Zend_Exception;
use Zend_Registry;
use ZfExtended_Factory;
use ZfExtended_Logger_Event;
use ZfExtended_Logger_Filter;
use ZfExtended_Logger_Writer_Database;
use ZfExtended_Models_Config;
use ZfExtended_Models_Log;

class EventWriter extends ZfExtended_Logger_Writer_Database
{
    /**
     * Plugin config having the following options:
     *  ->url
     *  ->lastPostedEventId
     *  ->postingMode
     *  ->verifyPeer
     *
     * @var Zend_Config
     */
    public Zend_Config $config;

    /**
     * @var bool|int
     */
    public bool|int|string $newLastId = false;

    /**
     * @var string
     */
    public string $httpHost = '';

    /**
     * editor_Plugins_IndiEngine_EventWriter constructor.
     *
     * @param array $options
     * @throws ReflectionException
     * @throws Zend_Exception
     */
    public function __construct(array $options)
    {
        // Setup config
        $this->config = Zend_Registry::get('config')->runtimeOptions->plugins->IndiEngine;

        // Call parent
        $this->filter = ZfExtended_Factory::get(ZfExtended_Logger_Filter::class, [$options['filter']]);
    }

    /**
     * Get events to be sent to Indi Engine logger instance as json => deflate => base64
     *
     * @return string
     * @throws ReflectionException
     * @throws Zend_Db_Statement_Exception
     * @throws Zend_Exception
     */
    public function getBase64() :string
    {
        // Get log model
        $log = ZfExtended_Factory::get(ZfExtended_Models_Log::class);

        // Get events
        $eventA = $log->getAllAfter($this->config->lastPostedEventId, 1000);

        // Setup httpHost
        $this->httpHost = $eventA[0]['httpHost'] ?? $_SERVER['SERVER_NAME'] ?? $_SERVER['HTTP_HOST'] ?? 'localhost';

        // Json encode events
        $json_encoded = json_encode($eventA, JSON_INVALID_UTF8_SUBSTITUTE);

        // If json encoding failed
        if ($json_encoded === false) {

            // Setup local logger instance
            $localLogger = Zend_Registry::get('logger')->cloneMe('plugin.IndiEngine');

            // Log that
            $localLogger->warn('E1594', 'JSON error with code {json_error_code} occurred on attempt to json_encode events: {json_error_msg}', [
                'json_error_code' => json_last_error(),
                'json_error_msg' => json_last_error_msg(),
                'range' => "Some of further " . count($eventA) . " events after ID {$this->config->lastPostedEventId}"
            ]);

            // Spoof events to be POSTed
            $eventA = $log->getAllAfter($this->config->lastPostedEventId, 1, 'E1594');
        }

        // Deflate
        $deflate = gzdeflate(json_encode($eventA, JSON_INVALID_UTF8_SUBSTITUTE), 9);

        // Setup newLastId if there was no json error
        if ($json_encoded !== false){
            $this->newLastId = $eventA ? array_pop($eventA)['id'] : false;
        }

        // Base64-encode
        return base64_encode($deflate);
    }

    /**
     * Try to make request to external Indi Engine logger instance.
     * If any problem occurred - log that via Translate5's built-in logger
     *
     * @param array $requestData
     * @return bool
     * @throws ReflectionException
     * @throws Zend_Exception
     */
    protected function request(array $requestData) : bool
    {
        // Setup local logger instance
        $localLogger = Zend_Registry::get('logger')->cloneMe('plugin.IndiEngine');

        // If no url defined for Indi Engine logger instance
        if (!$this->config->url) {

            // Log that
            $localLogger->warn('E1550', 'Logger URL endpoint for posting events is not configured');

            // Return false
            return false;
        }

        // Init curl
        $curl = curl_init();

        // Set opts
        curl_setopt_array($curl, [
            CURLOPT_URL => $this->config->url,
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_SSL_VERIFYPEER => $this->config->verifyPeer,
            CURLOPT_POSTFIELDS => $requestData
        ]);

        // Make request
        $response = curl_exec($curl);

        // If response is boolean false
        if ($response === false) {

            // Log that
            $localLogger->warn('E1551', 'Curl-error occurred on attempt to POST events: {curl_error}', [
                'curl_error' => curl_error($curl)
            ]);

            // Return false
            return false;
        }

        // If response's status code is not 200
        if (($code = curl_getinfo($curl, CURLINFO_HTTP_CODE)) != 200) {

            // Log that
            $localLogger->warn('E1552', 'Logger responded with failure code {code}', [
                'code' => $code,
                'text' => $response
            ]);

            // Return false
            return false;
        }

        // If lastPostedEventId should be updated - update that
        if ($this->newLastId) {
            ZfExtended_Factory
                ::get(ZfExtended_Models_Config::class)->update(
                    'runtimeOptions.plugins.IndiEngine.lastPostedEventId',
                    "$this->newLastId"
                );
        }

        // Return json-decoded response
        return true;
    }

    /**
     * Try to send all 'new' events to external Indi Engine logger instance
     *
     * @throws ReflectionException
     * @throws Zend_Db_Statement_Exception
     * @throws Zend_Exception
     */
    public function batchWrite()
    {

        // Get base64-encoded data. Also $this->httpHost is set during this call
        $base64 = $this->getBase64();

        // Make request
        $this->request([
            'host' => $this->httpHost,
            'base64' => $base64
        ]);
    }

    /**
     * Try to send one 'new' event to external Indi Engine logger instance
     *
     * @param ZfExtended_Logger_Event $event
     * @throws ReflectionException
     * @throws Zend_Exception
     */
    public function write(ZfExtended_Logger_Event $event): void
    {
        // Call parent
        parent::write($event);

        // Proceed only if postingMode is 'realtime'
        if ($this->config->postingMode !== 'realtime') {
            return;
        }

        // Prevent endless loop
        if (in_array($this->insertedData['eventCode'], ['E1550', 'E1551', 'E1552'])) {
            return;
        }

        // Setup newLastId to be used for spoofing current value of lastPostedEventId
        $this->newLastId = (int) $this->insertedId;

        // Make request
        $this->request($this->insertedData);
    }
}
