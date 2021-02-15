<?php
/*
 START LICENSE AND COPYRIGHT
 
 This file is part of translate5
 
 Copyright (c) 2013 - 2017 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.
 
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

/**#@+
 * @author Marc Mittag
 * @package portal
 * @version 2.0
 *
 */

/**
 * Contains checks for fundamental configuration values
 */
class Models_SystemRequirement_Modules_Configuration extends ZfExtended_Models_SystemRequirement_Modules_Abstract {
    
    const MEMCACHE_ID = 'Models_SystemRequirement_Modules_Configuration::checkServerName';
    
    /**
     * {@inheritDoc}
     * @see ZfExtended_Models_SystemRequirement_Modules_Abstract::validate()
     */
    function validate(): ZfExtended_Models_SystemRequirement_Result {
        $this->result->id = 'configuration';
        $this->result->name = 'Configuration';
        
        $this->checkServerName();
        // TODO how to test APPLICATION_RUNDIR??? (not easy since configured in apache)
        return $this->result;
    }
    
    /**
     * The php and the mysql timezone must be set to the same value, otherwise we will get problems, see TRANSLATE-2030
     * @param Zend_Db_Adapter_Abstract $db
     */
    protected function checkServerName() {
        $config = Zend_Registry::get('config');
        $path = '/index/testserver';
        $url = $config->runtimeOptions->server->protocol . $config->runtimeOptions->server->name . $path;
        
        $serverId = ZfExtended_Utils::uuid();
        $memcache = new ZfExtended_Cache_MySQLMemoryBackend();
        $memcache->save($serverId, self::MEMCACHE_ID);
        
        $curlError = '';
        $curlErrorWorker = '';
        $output = $this->callUrl($url, $curlError);
        $hasWorkerUrl = !empty($config->runtimeOptions->worker->server);
        if($hasWorkerUrl) {
            $outputWorker = $this->callUrl($config->runtimeOptions->worker->server.$path, $curlErrorWorker);
        }
        
        $memcache->remove(self::MEMCACHE_ID);
        
        if ($output === false) {
            $error = 'An internal connection to Translate5 can not be established: URL '.$url.PHP_EOL.PHP_EOL;
            $error .= 'Connection error message: '.$curlError.PHP_EOL.PHP_EOL;
            $error .= 'Depending on the above error message: '.PHP_EOL;
            $error .= '  this could be a networking error,'.PHP_EOL;
            $error .= '  or a misconfiguration of server.protocol and server.name,'.PHP_EOL;
            $error .= '  or a misconfiguration of the webserver itself. '.PHP_EOL;
            $error .= 'For SSL errors mostly an intermediate certificate is missing or use worker.server config if behind a SSL Proxy.'.PHP_EOL;
            $error .= 'If your configured public server.name is not available from a internal connection'.PHP_EOL;
            $error .= 'due network configuration reasons, consider to set the internal used worker.server configuration.'.PHP_EOL;
            if($hasWorkerUrl) {
                $this->result->warning[] = $error;
                $output = $outputWorker;
            }
            else {
                $this->result->error[] = $error;
                return;
            }
        }
        
        if($hasWorkerUrl && $outputWorker === false) {
            $error = 'The internal connection to Translate5 via the configured worker.server '.PHP_EOL;
            $error .= 'can not be established: URL '.$url.PHP_EOL.PHP_EOL;
            $error .= 'Connection error message: '.$curlErrorWorker.PHP_EOL.PHP_EOL;
            $error .= 'If this is the only error regarding server.name and worker.server configuration, '.PHP_EOL;
            $error .= ' consider to remove the worker.server configuration.'.PHP_EOL;
            $error .= 'Otherwise try to solve the warnings / errors about the server.name first.'.PHP_EOL;
            $this->result->error[] = $error;
            return;
        }
        
        $output = explode(' ', $output);
        
        $noMemCacheId = empty($output[0]) || $output[0] != Models_SystemRequirement_Modules_Configuration::MEMCACHE_ID;
        $noServerIdMatch = empty($output[1]) || $output[1] != $serverId;
        if($noMemCacheId || $noServerIdMatch) {
            $error = 'An internal connection to a Translate5 instance could be established: URL '.$url.PHP_EOL;
            $error .= 'but it seems that this is not the translate5 instance from where the check-command was started!'.PHP_EOL;
            $error .= ' this could be a misconfiguration of server.protocol and server.name (or worker.server if set)'.PHP_EOL;
            $this->result->error[] = $error;
        }
    }
    
    protected function callUrl(string $url, string &$curlError) {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_FAILONERROR, true);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_FRESH_CONNECT, true);
        
        //// Timeout in seconds
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 1);
        curl_setopt($curl, CURLOPT_TIMEOUT, 2);
        
        $result = curl_exec($curl);
        if($result === false) {
            $curlError = curl_error($curl);
            return false;
        }
        return $result;
    }
}