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
 * Lucy LT Connector
 */
class editor_Services_LucyLT_Connector extends editor_Services_Connector_Abstract {

    public function __construct() {
        parent::__construct();
        $this->defaultMatchRate = $this->config->runtimeOptions->LanguageResources->lucylt->matchrate;
    }
    
    /**
     * (non-PHPdoc)
     * @see editor_Services_Connector_Abstract::query()
     *
     * FIXME currently no unified whitespace handling is used (see other connectors), should be refactored on demand
     */
    public function query(editor_Models_Segment $segment) {
        $queryString = $this->getQueryStringAndSetAsDefault($segment);

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
        $res = $this->languageResource->getResource();
        /* @var $res editor_Services_LucyLT_Resource */

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
        $resource = $this->languageResource->getResource();
        /* @var $resource editor_Services_LucyLT_Resource */
        
        $result = [
            'source' => $resource->getMappedLanguage($this->languageResource->getSourceLangCode())
        ];
        
        $result['target'] = $resource->getMappedLanguage($this->languageResource->getTargetLangCode());
        
        return join('-', $result);
    }
    
    public function translate(string $searchString){
        throw new BadMethodCallException("The Lucy LT Connector does not support translate requests");
    }

    /**
     * intended to calculate a matchrate out of the MT score
     * @param string $score
     */
    protected function calculateMatchrate($score = null) {
        return $this->defaultMatchRate;
    }
    
    public function getStatus(editor_Models_LanguageResources_Resource $resource){
        $this->lastStatusInfo = '';
        $http = ZfExtended_Factory::get('Zend_Http_Client');
        /* @var $http Zend_Http_Client */
        
        $auth = explode(':', $this->resource->getCredentials());
        $http->setAuth($auth[0], $auth[1]);
        $http->setConfig(['timeout' => 3]);
        
        $url = rtrim($this->resource->getUrl(), '/');
        $http->setUri($url.'/mtrans/exec');
        $http->setHeaders('Content-Type: application/json');
        $http->setHeaders('Accept: application/json');
        
        $response = $http->request('OPTIONS');
        
        $status = $response->getStatus();
        
        switch ($status) {
            case 200:
                if(strpos($response->getBody(), 'resource path="mtrans/exec"') !== false) {
                    return self::STATUS_AVAILABLE;
                }
                break;
            case 401:
                $this->lastStatusInfo = 'Translate5 can not authenticate itself at the Lucy LT server.';
                return self::STATUS_NOCONNECTION;
        }
        $this->lastStatusInfo = 'The answer received from the Lucy LT Server is not as expected!';
        return self::STATUS_NOCONNECTION;
    }
}