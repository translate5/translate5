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
 * @package editor
 * @version 1.0
 *
 */
/**
 * Moses Connector
 */
class editor_Services_Moses_Connector extends editor_Services_Connector_Abstract {
    /**
     * We assume that the best MT Match correlate this matchrate, given by config
     * @var integer
     */
    protected $MT_BASE_MATCHRATE;

    public function __construct() {
        parent::__construct();
        $config = Zend_Registry::get('config');
        /* @var $config Zend_Config */
        $this->MT_BASE_MATCHRATE = $config->runtimeOptions->LanguageResources->moses->matchrate;
    }
    
    /**
     * (non-PHPdoc)
     * @see editor_Services_Connector_Abstract::query()
     */
    public function query(editor_Models_Segment $segment) {
        $queryString = $this->getQueryString($segment);
        $this->resultList->setDefaultSource($queryString);
        
        //query moses without tags
        $queryString = $segment->stripTags($queryString);
        
        $res = $this->languageResource->getResource();
        /* @var $res editor_Services_Moses_Resource */

        $rpc = new Zend_XmlRpc_Client($res->getUrl());
        $proxy = $rpc->getProxy();
        $params = array(
            //for the "es ist ein kleines haus" Moses sample data the requests work only with lower case requests:
            //see T5DEV-86 escape [] brackets from Moses query for info on next line
            'text' => str_replace(array('[',']'), array('\[','\]'), $queryString), //"es ist ein kleines haus",
            'align' => 'false',
            'report-all-factors' => 'false',
        );
        
        $res = $this->sendToProxy($proxy, $params);
        
        if(!empty($res['text'])){
            $res['text'] = str_replace(array('\[','\]'), array('[',']'), $res['text']);
            $this->resultList->addResult($res['text'], $this->calculateMatchrate());
            return $this->resultList;
        }
        
        return $this->resultList;
    }
    
    /**
     * encapsulates the call to the proxy, does exception handling
     */
    protected function sendToProxy($proxy, $params){
        try {
            return $proxy->translate($params);
        }
        catch(Exception $e) {
            //In moses we will get mostly connection errors:
            $msg = $e->getMessage();
            if(strpos($msg, 'stream_socket_client(): unable to connect to') === 0){
                $sepPos = strpos($msg, '; File:');
                if($sepPos > 0) {
                    $msg = substr($msg, 0, $sepPos);
                }
                $msg = str_replace('^stream_socket_client()', 'Moses Server not reachable', '^'.$msg);
                throw new ZfExtended_Exception($msg, $e->getCode(), $e);
            }
            //on all errors, throw it directly
            throw $e;
        }
    }
    
    /**
     * (non-PHPdoc)
     * @see editor_Services_Connector_Abstract::search()
     */
    public function search(string $searchString, $field = 'source', $offset = null) {
        throw new BadMethodCallException("The Moses MT Connector does not support search requests");
    }

    /**
     * intended to calculate a matchrate out of the MT score
     * @param string $score
     */
    protected function calculateMatchrate($score = null) {
        return $this->MT_BASE_MATCHRATE;
    }
    
    /**
     * {@inheritDoc}
     * @see editor_Services_Connector_Abstract::getStatus()
     */
    public function getStatus(& $moreInfo){
        $res = $this->languageResource->getResource();
        /* @var $res editor_Services_Moses_Resource */
        
        $http = ZfExtended_Factory::get('Zend_Http_Client');
        $http->setConfig(['timeout' => 3]);
        /* @var $http Zend_Http_Client */
        $http->setUri($res->getUrl());
        
        try {
            $response = $http->request('GET');
        }catch (Exception $e){
            $moreInfo = $e->getMessage();
            $log = ZfExtended_Factory::get('ZfExtended_Log');
            /* @var $log ZfExtended_Log */
            $log->logException($e);
            return self::STATUS_NOCONNECTION;
        }
        
        //making a plain GET request produces a 405 state since it is not allowed.
        // This is OK, since we want just test the connectivity
        if($response->getStatus() === 405) {
            return self::STATUS_AVAILABLE;
        }
        $moreInfo = 'The answer received from Moses is not as expected!';
        return self::STATUS_NOCONNECTION;
    }
}