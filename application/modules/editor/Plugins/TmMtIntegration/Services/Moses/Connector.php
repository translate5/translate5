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
class editor_Plugins_TmMtIntegration_Services_Moses_Connector extends editor_Plugins_TmMtIntegration_Services_ConnectorAbstract {
    /**
     * We assume that the best MT Match correlate this matchrate
     * @var integer
     */
    const MT_BASE_MATCHRATE = 70;
    
    public function addTm(string $filename, editor_Plugins_TmMtIntegration_Models_TmMt $tm){
        throw new BadMethodCallException('This Service is not filebased and cannot handle uploaded files therefore!');
    }

    public function synchronizeTmList() {
        //for Moses do currently nothing
    }

    public function open(editor_Plugins_TmMtIntegration_Models_TmMt $tmmt) {
        error_log("Opened Tmmt ".$tmmt->getName().' - '.$tmmt->getServiceName());
    }

    public function close(editor_Plugins_TmMtIntegration_Models_TmMt $tmmt) {
        error_log("Opened Tmmt ".$tmmt->getName().' - '.$tmmt->getServiceName());
    }

    public function openForQuery(editor_Plugins_TmMtIntegration_Models_TmMt $tmmt) {
        error_log("Opened Tmmt ".$tmmt->getName().' - '.$tmmt->getServiceName());
    }

    /**
     * (non-PHPdoc)
     * @see editor_Plugins_TmMtIntegration_Services_ConnectorAbstract::query()
     */
    public function query(string $queryString) {
        $rpc = new Zend_XmlRpc_Client("http://www.translate5.net:8124/RPC2");
        $proxy = $rpc->getProxy();
        $params = array(
                'text' => "es ist ein kleines haus",
                'align' => 'false',
                'report-all-factors' => 'false',
        );
        
        try {
            $res = $proxy->translate($params);
        }
        catch(Exception $e) {
            error_log($e);
        }
        
        
        $this->resultList->setDefaultSource($queryString);
        
        if(!empty($res['text'])){
            $this->resultList->addResult($res['text'], $this->calculateMatchrate());
            return $this->resultList;
        }
        
        return [];
    }
    
    /**
     */
    public function processSingleResponse($text) {
    }
    
    /**
     * (non-PHPdoc)
     * @see editor_Plugins_TmMtIntegration_Services_ConnectorAbstract::search()
     */
    public function search(string $searchString) {
        return $this->query($searchString);
    }

    /**
     * intended to calculate a matchrate out of the MT score
     * @param string $score
     */
    protected function calculateMatchrate($score = null) {
        return self::MT_BASE_MATCHRATE;
    }
    
}