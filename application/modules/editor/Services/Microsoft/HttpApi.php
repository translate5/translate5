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

class editor_Services_Microsoft_HttpApi {
    /**
     * @var stdClass
     */
    protected $result;


    protected $error = array();

    /***
     * Api key used for authentcication
     * @var string
     */
    protected $apiKey;

    /***
     *
     * @var string
     */
    protected $apiUrl;


    /***
     * Id dictonary lookup search request
     * @var string
     */
    protected $isDictionaryLookup=false;

    public function __construct() {
        $this->initApi();
    }

    /***
     * init api authentication data
     * @throws ZfExtended_ValidateException
     */
    protected function initApi(){
        $config = Zend_Registry::get('config');
        /* @var $config Zend_Config */

        $this->apiKey = isset($config->runtimeOptions->LanguageResources->microsoft->apiKey) ? $config->runtimeOptions->LanguageResources->microsoft->apiKey:null ;
        if(empty($this->apiKey)){
            throw new ZfExtended_Exception("Microsoft translator api key is not defined");
        }

        $this->apiUrl=isset($config->runtimeOptions->LanguageResources->microsoft->apiUrl) ?$config->runtimeOptions->LanguageResources->microsoft->apiUrl:null ;
        if(empty($this->apiUrl)){
            throw new ZfExtended_Exception("Microsoft translator api url is not defined");
        }
    }

    /**
     * Search the api for given source/target language by domainCode
     *
     * @param string $text
     * @param string $sourceLang
     * @param string $targetLang
     * @return boolean
     */
    public function search($text,$sourceLang,$targetLang) {

        //set the default mode, only translation
        $path="/translate?api-version=3.0";
        //if it is dictonary lookup, change the path

        $isDirecotrLookup=$this->isValidDictionaryLookup($sourceLang, $targetLang);
        if($isDirecotrLookup){
            $path="/dictionary/lookup?api-version=3.0";
        }
        $params = "&from=".$sourceLang."&to=".$targetLang;

        if(!is_array($text)){
            $text = [$text];
        }
        $requestBody  = [];
        foreach ($text as $t) {
            $requestBody[] = ['Text' => $t];
        }

        $content = json_encode($requestBody);
        $result = $this->searchApi($path,$params, $content);
        
        $result =$this->processTranslateResponse($result);
        //if the DictionaryLookup produces an error, try with the normal translate request
        if(empty($this->result) && $this->isDictionaryLookup){
            //if in directory lookup the result is empty, trigger a normal result so translation from microsoft is received
            $path="/translate?api-version=3.0";
            $result = $this->searchApi($path,$params,$content);
            return $this->processTranslateResponse($result);
        }
        
        return $result;
    }

    /***
     * Query the microsoft api
     *
     * @param string $path
     * @param string $params
     * @param string $content
     * @return string
     */
    protected function searchApi($path,$params,$content) {
        //reset the errors array
        $this->error = [];
        $headers = "Content-type: application/json\r\n" .
            "Content-length: " . strlen($content) . "\r\n" .
            "Ocp-Apim-Subscription-Key: $this->apiKey\r\n" .
            "X-ClientTraceId: " . ZfExtended_Utils::uuid() . "\r\n";

        // NOTE: Use the key 'http' even if you are making an HTTPS request. See:
        // https://php.net/manual/en/function.stream-context-create.php
        $options = array (
            'http' => array (
                'header' => $headers,
                'method' => 'POST',
                'content' => $content
            )
        );
        $context  = stream_context_create ($options);
        $result = @file_get_contents ($this->apiUrl . $path . $params, false, $context);
        if (false === $result) {
            $this->error[] = error_get_last();
            return false;
        }
        return $result;
    }

    /***
     * Check if it is valid direcory lookup for the given language combination.
     * The microsoft translator supports only from en or to en directory lookup.
     * More info: https://docs.microsoft.com/en-us/azure/cognitive-services/Translator/language-support
     * @param string $sourceLang
     * @param string $targetLang
     * @return boolean
     */
    protected function isValidDictionaryLookup($sourceLang,$targetLang){
        return $this->isDictionaryLookup && (mb_substr(strtolower($sourceLang), 0,2)=='en' || mb_substr(strtolower($targetLang), 0,2)=='en');
    }

    /** Check the api status
     * @return boolean
     */
    public function getStatus(){
        return true;
    }


    /***
     * Gets the set of languages currently supported for translation
     * @return string|boolean
     */
    public function getLanguages(){
        $path = "/languages?api-version=3.0";
        $headers = "Content-type: application/json\r\n" .
            "Ocp-Apim-Subscription-Key: $this->apiKey\r\n" .
            "X-ClientTraceId: " . ZfExtended_Utils::uuid() . "\r\n";
        
        // NOTE: Use the key 'http' even if you are making an HTTPS request. See:
        // https://php.net/manual/en/function.stream-context-create.php
        $options = array (
            'http' => array (
                'header' => $headers,
                'method' => 'GET'
            )
        );
        //retunr only the supported languages for translation
        $params = '&scope=translation';
        $context  = stream_context_create ($options);
        $response = @file_get_contents ($this->apiUrl . $path.$params, false, $context);
        
        if (false === $response) {
            $this->error[] = error_get_last();
            $this->badGateway();
        }
        $decode = json_decode($response,true);
        //The value of the translation property is a dictionary of (key, value) pairs. 
        //Each key is a BCP 47 language tag. A key identifies a language for which text can be translated to or translated from
        $this->result = $decode['translation'] ?? [];
        return $this->getResult();
    }

    /***
     * Check if the given language code is valid for the api
     * @param string $languageCode: language code
     * @return boolean
     */
    public function isValidLanguage($languageCode){
        try {
            $this->search('Hi','en', $languageCode);
            return empty($this->error);
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * returns the found errors
     */
    public function getErrors() {
        return $this->error;
    }


    /**
     * returns the decoded JSON result
     */
    public function getResult() {
        return $this->result;
    }

    protected function badGateway() {
        $errors= $this->getErrors()[0] ?? [];
        $ex=new editor_Services_Connector_Exception('E1282',[
            'errors' => $errors
        ]);
        $ex->setMessage($errors['message'] ?? '');
        throw $ex;
    }

    /**
     * Set the response result
     * @return boolean
     */
    protected function processTranslateResponse($response) {
        if(!empty($this->error)){
            throw $this->badGateway();
        }
        
        $result=json_decode($response,true);
        if (JSON_ERROR_NONE !== json_last_error()) {
            $ex=new editor_Services_Connector_Exception('E1282');
            $ex->setMessage(json_last_error_msg());
            throw $ex;
        }
        if(empty($result)){
            return empty($this->error);
        }
        
        $collection=[];
        foreach ($result as $res) {
            //we get only one translation per search
            $single = reset($res['translations']);
            if($single === false){
                continue;
            }
            //the response layout contains only text, when no dictonary lookup is used
            if(isset($single['text'])){
                $collection[]=[
                    'text'=>$single['text']
                ];
            }else{
                //the request is triggered for dictonary lookup, collect the additinal translations
                $collection[]=[
                    'text'=>isset($single['displayTarget']) ? $single['displayTarget'] : '',
                    'metaData'=>$single
                ];
            }
        }
        $this->result=$collection;
        return empty($this->error);
    }

    public function setIsDictionaryLookup(bool $value){
        $this->isDictionaryLookup=$value;
    }
}
