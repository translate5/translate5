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
 * Lucy LT Connector
 */
class editor_Plugins_MatchResource_Services_LucyLT_Connector extends editor_Plugins_MatchResource_Services_Connector_Abstract {
    /**
     * We assume that the best MT Match correlate this matchrate, given by config
     * @var integer
     */
    protected $MT_BASE_MATCHRATE;

    public function __construct() {
        parent::__construct();
        $config = Zend_Registry::get('config');
        /* @var $config Zend_Config */
        $this->MT_BASE_MATCHRATE = $config->runtimeOptions->plugins->MatchResource->lucylt->matchrate;
    }
    
    /**
     * (non-PHPdoc)
     * @see editor_Plugins_MatchResource_Services_Connector_Abstract::query()
     */
    public function query(editor_Models_Segment $segment) {
        $queryString = $this->getQueryString($segment);
        $this->resultList->setDefaultSource($queryString);

        $internalTag = ZfExtended_Factory::get('editor_Models_Segment_InternalTag');
        /* @var $internalTag editor_Models_Segment_InternalTag */
        
        $id = 0;
        $data = [];
        
        $queryString = $internalTag->replace($queryString, function($match) use (&$id, &$data){
            $replacement = '<internalTag id="internal-'.($id++).'"/>';
            $data[$replacement] = $match[0];
            return $replacement;
        });
        
        //query lucy with internal tags as HTML only
        $queryString = strip_tags($queryString, '<internalTag><internalTag/>');
        $foundResult = $this->rawRequest('<xml>'.$queryString.'</xml>');
        
        if($foundResult === false) {
            return $this->resultList;
        }
        
        //strip xml container
        $foundResult = trim(strip_tags($foundResult, '<internalTag><internalTag/>'));
        
        //FIXME ensure that Lucy returns each internalTag only once
        //missing tags are fixed by the frontend, but errousnouly duplicated not!
//FIXME also note down concept to refactor Tag concept in translate5 
        
        //restore the internal tags
        $foundResult = str_replace(array_keys($data), array_values($data), $foundResult, $count);
        
        $this->resultList->addResult($foundResult, $this->calculateMatchrate());
        return $this->resultList;
    }
    
    /**
     * Sends the plain request to Lucy
     * @param string $queryString
     * @throws Exception
     * @return boolean|string
     */
    protected function rawRequest(string $queryString) {
        $res = $this->tmmt->getResource();
        /* @var $res editor_Plugins_MatchResource_Services_LucyLT_Resource */

        $http = ZfExtended_Factory::get('Zend_Http_Client');
        /* @var $http Zend_Http_Client */
        $auth = explode(':', $res->getCredentials());
        $http->setAuth($auth[0], $auth[1]);
        
        $url = rtrim($res->getUrl(), '/');
        $http->setUri($url.'/mtrans/exec');
        $http->setHeaders('Content-Type: application/json');
        $http->setHeaders('Accept: application/json');
        
        $params = [
            'inputParams' => [
                    "param" => [[
                            "@name"  => "TRANSLATION_DIRECTION",
                            "@value" => $this->getLanguageParameter()
                    ], [
                            "@name"  => "INPUT",
                            "@value" => $queryString
                    ], [
                            "@name"  => "MARK_UNKNOWNS",  //disable unknown translation marking <U[UNKNOWN]> 
                            "@value" => "0"
                    ], [
                            "@name"  => "MARK_ALTERNATIVES", //disable alternative marking, not seen yet
                            "@value" => "0"
                    ], [
                            "@name"  => "CHARSET",
                            "@value" => "UTF"
                    ]]
            ]
        ];
        $http->setRawData(json_encode($params), 'application/json');
        $result = $http->request('POST');
        
        if($result->getStatus() != "200") {
            throw new Exception($result->getBody(), 500);
        }
        
        $result = json_decode($result->getBody());
        if(json_last_error()) {
            throw new Exception("Error on JSON decode: ".json_last_error_msg(), 500);
        }
        
        
        $result = $result->outputParams->param;
        
        foreach($result as $item){
            if($item->{'@name'} == 'OUTPUT') {
                $foundResult = $item->{'@value'};
                return $foundResult;
            }
        }
        return false;
    }
    
    /* 
     * Lucy Result:
{
	"inputParams": {
		"param": [{
			"@name": "TRANSLATION_DIRECTION",
			"@value": "ENGLISH-GERMAN"
		}, {
			"@name": "INPUT",
			"@value": "Use the prefork MPM, which is the default MPM with Apache 2.0 and 2.2."
		}, {
			"@name": "USER",
			"@value": "translate5"
		}]
	},
	"outputParams": {
		"param": [{
			"@name": "OUTPUT",
			"@value": "Benutzen Sie das Vorgabel-<U[MPM]>, das das Vorgabe-<U[MPM]> mit Apachen 2.0 und 2.2 ist."
		}, {
			"@name": "SENTENCES",
			"@value": "1"
		}, {
			"@name": "WORDS",
			"@value": "14"
		}, {
			"@name": "CHARACTERS",
			"@value": "70"
		}, {
			"@name": "FORMAT",
			"@value": "ASCII"
		}, {
			"@name": "CHARSET",
			"@value": "WIN"
		}, {
			"@name": "SOURCE_ENCODING",
			"@value": "Windows-1252"
		}, {
			"@name": "TARGET_ENCODING",
			"@value": "Windows-1252"
		}]
	}
} 
     
     */
    
    
    /**
     * returns the TRANSLATION_DIRECTORY string as needed by Lucy
     * @return string
     */
    protected function getLanguageParameter() {
        $resource = $this->tmmt->getResource();
        /* @var $resource editor_Plugins_MatchResource_Services_LucyLT_Resource */
        
        $result = [
            'source' => $resource->getMappedLanguage($this->tmmt->getSourceLangRfc5646())
        ];
        
        $result['target'] = $resource->getMappedLanguage($this->tmmt->getTargetLangRfc5646());
        
        return join('-', $result);
    }
    
    /**
     * (non-PHPdoc)
     * @see editor_Plugins_MatchResource_Services_Connector_Abstract::search()
     */
    public function search(string $searchString, $field = 'source') {
        throw new BadMethodCallException("The Lucy LT Connector does not support search requests");
    }

    /**
     * intended to calculate a matchrate out of the MT score
     * @param string $score
     */
    protected function calculateMatchrate($score = null) {
        return $this->MT_BASE_MATCHRATE;
    }
    
}