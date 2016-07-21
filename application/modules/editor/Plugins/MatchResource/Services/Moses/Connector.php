<?php
/*
START LICENSE AND COPYRIGHT

 This file is part of translate5

 Copyright (c) 2013 - 2015 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

 Contact:  http://www.MittagQI.com/  /  service (ATT) MittagQI.com

 This file may be used under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE version 3
 as published by the Free Software Foundation and appearing in the file agpl3-license.txt
 included in the packaging of this file.  Please review the following information
 to ensure the GNU AFFERO GENERAL PUBLIC LICENSE version 3.0 requirements will be met:
 http://www.gnu.org/licenses/agpl.html

 There is a plugin exception available for use with this release of translate5 for
 open source applications that are distributed under a license other than AGPL:
 Please see Open Source License Exception for Development of Plugins for translate5
 http://www.translate5.net/plugin-exception.txt or as plugin-exception.txt in the root
 folder of translate5.

 @copyright  Marc Mittag, MittagQI - Quality Informatics
 @author     MittagQI - Quality Informatics
 @license    GNU AFFERO GENERAL PUBLIC LICENSE version 3 with plugin-execptions
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
class editor_Plugins_MatchResource_Services_Moses_Connector extends editor_Plugins_MatchResource_Services_ConnectorAbstract {
    /**
     * We assume that the best MT Match correlate this matchrate, given by config
     * @var integer
     */
    protected $MT_BASE_MATCHRATE;

    public function __construct() {
        parent::__construct();
        $config = Zend_Registry::get('config');
        /* @var $config Zend_Config */
        $this->MT_BASE_MATCHRATE = $config->runtimeOptions->plugins->MatchResource->moses->matchrate;
    }
    
    public function addTm(string $filename){
        throw new BadMethodCallException('This Service is not filebased and cannot handle uploaded files therefore!');
    }

    /**
     * (non-PHPdoc)
     * @see editor_Plugins_MatchResource_Services_ConnectorAbstract::query()
     */
    public function query(editor_Models_Segment $segment) {
        $queryString = $this->getQueryString($segment);
        //query moses without tags
        $queryString = $segment->stripTags($queryString);
        
        $res = $this->tmmt->getResource();
        /* @var $res editor_Plugins_MatchResource_Services_Moses_Resource */

        $rpc = new Zend_XmlRpc_Client($res->getUrl());
        $proxy = $rpc->getProxy();
        $params = array(
            //for the "es ist ein kleines haus" sample data the requests work only with lower case requests:
            'text' => strtolower($queryString), //"es ist ein kleines haus",
            'align' => 'false',
            'report-all-factors' => 'false',
        );
        
        $res = $this->sendToProxy($proxy, $params);
        
        $this->resultList->setDefaultSource($queryString);
        
        if(!empty($res['text'])){
            $this->resultList->addResult($res['text'], $this->calculateMatchrate());
            return $this->resultList;
        }
        
        return [];
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
     * @see editor_Plugins_MatchResource_Services_ConnectorAbstract::search()
     */
    public function search(string $searchString, $field = 'source') {
        //since a MT can not be searched in the target language, we just pass the $searchString to the query call
        return $this->query($searchString);
    }

    /**
     * intended to calculate a matchrate out of the MT score
     * @param string $score
     */
    protected function calculateMatchrate($score = null) {
        return $this->MT_BASE_MATCHRATE;
    }
    
}