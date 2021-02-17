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

/**
 */
class Editor_FakelangresController extends ZfExtended_Controllers_Action {
    /**
     * simple method to secure that controller is only called by the same server (wget)
     * @see ZfExtended_Controllers_Action::init()
     */
    public function init() {
        $config = Zend_Registry::get('config');
        if($config->runtimeOptions->cronIP !== $_SERVER['REMOTE_ADDR']) {
            throw new ZfExtended_Models_Entity_NoAccessException('Wrong IP to call fake language resources! Configure cronIP accordingly!');
        }
        $this->_helper->layout->disableLayout();
        $this->_helper->viewRenderer->setNoRender();
    }
    
    /**
     * Empty index, does nothing
     */
    public function indexAction() {
        //print description
    }
    
    /**
     * Faking DeepL requests, translating with rot13.
     * Supports the following calls: get languages, get usage and translate
     * @throws Exception
     */
    public function deeplAction() {
        
        //for deepl we expect always that requests:
        $this->expectHeader('accept', 'application/json; charset=utf-8');
        $this->expectHeader('accept-charset', 'UTF-8');
        $this->expectGet('auth_key', Zend_Registry::get('config')->runtimeOptions->plugins->DeepL->authkey);
        
        /*
         ** to test not authenticated requests:
         */
//         header('HTTP/1.0 403 Forbidden');
//         exit;
        
        header('Content-Type: application/json', true);
        $calledUrl = $this->getUriApiPart();
        switch ($calledUrl) {
            //Requesting which languages do exist:
            //GET https://api.deepl.com:443/v2/languages?auth_key=xxxx
            // [{"language":"DE","name":"German"},{"language":"EN","name":"English"},{"language":"ES","name":"Spanish"},{"language":"FR","name":"French"},{"language":"IT","name":"Italian"},{"language":"JA","name":"Japanese"},{"language":"NL","name":"Dutch"},{"language":"PL","name":"Polish"},{"language":"PT","name":"Portuguese"},{"language":"RU","name":"Russian"},{"language":"ZH","name":"Chinese"}]
            case 'v2/languages' :
                echo '[{"language":"DE","name":"German"},{"language":"EN","name":"English"},{"language":"ES","name":"Spanish"},{"language":"FR","name":"French"},{"language":"IT","name":"Italian"},{"language":"JA","name":"Japanese"},{"language":"NL","name":"Dutch"},{"language":"PL","name":"Polish"},{"language":"PT","name":"Portuguese"},{"language":"RU","name":"Russian"},{"language":"ZH","name":"Chinese"}]';
                return;
                
            //Requesting how the usage is:
            //GET https://api.deepl.com:443/v2/usage?auth_key=xxxx
            //{"character_count":75323,"character_limit":3000000}
            case 'v2/usage' :
                echo '{"character_count":75323,"character_limit":3000000}';
                return;
            case 'v2/translate' :
                /*
                 ** enable this two lines to test Quota Exceeded
                 */
//                 header('HTTP/1.0 456 Quota exceeded');
//                 echo '{"message":"Quota Exceeded"}';
//                 return;
                /**
                 * Enable that for formality error
                 */
//                 header('HTTP/1.0 400 Bad Request');
//                 echo '{"message":"\'formality\' is not supported for given \'target_lang\'."}';
//                 exit;
                
                break;
            default:
                throw new Exception("Unknown DeepL endpoint requested: ".$calledUrl);
        }
        
        $this->expectPost('split_sentences', '1');
        $this->expectPost('preserve_formatting', '0');

        //tag handling is not used in instant translate, therefore this check is optional
        isset($_POST['tag_handling']) && $this->expectPost('tag_handling', 'xml');
        
        //we just assume some of the source_languages to be able to throw a deepl conform error:
        /*
         * Error Example!
        [25-Nov-2020 16:55:32 Europe/Vienna] Status (505c744): 400
        [25-Nov-2020 16:55:32 Europe/Vienna] Headers (505c744):HTTP/1.1 400 Bad Request
        [25-Nov-2020 16:55:32 Europe/Vienna] Raw Body (505c744):{"message":"Value for 'target_lang' not supported."}
        */
        try {
            $this->expectPost('source_lang', ...['de', 'en', 'es']);
        }catch(Exception $e) {
            http_response_code(400);
            echo '{"message":"Value for \'source_lang\' not supported."}';
            return;
        }
        
        try {
            $this->expectPost('target_lang', ...['de', 'en', 'es']);
        }catch(Exception $e) {
            http_response_code(400);
            echo '{"message":"Value for \'target_lang\' not supported."}';
            return;
        }
        
        //expected content:
        /*
            Array
            (
                [text] => Influence and contribute!
                [source_lang] => en
                [target_lang] => de
                [split_sentences] => 0
                [preserve_formatting] => 0
                [tag_handling] => xml
            )
        */
            
        //HTTP result 200
        // POST https://api.deepl.com:443/v2/translate?auth_key=xxxx

        $RAWPOST = null;
        //deepl uses a non standard way to transport multiple texts:
        parse_str(str_replace('text=', 'text[]=', $this->_request->getRawBody()), $RAWPOST);
        if(is_array($RAWPOST) && is_array($RAWPOST['text'])) {
            $translations = $RAWPOST['text'];
        }
        else {
            $translations = [$_POST['text']];
        }
        
        $demomt = new editor_Plugins_ZDemoMT_Connector();
        $result = new stdClass();
        $result->translations = [];
        foreach($translations as $text) {
            $translated = new stdClass();
            $translated->detected_source_language = strtoupper($_POST['source_lang']);
            //additional tag:
            //$text = '<g id="1">'.$demomt->translateToRot13($_POST['text']);
            
            //remove a specific tag
            //$text = str_replace('<x id="6"/>', '', $demomt->translateToRot13($_POST['text']));
            //normal content:
            $translated->text = $demomt->translateToRot13($text);
            $result->translations[] = $translated;
        }
        
        echo json_encode($result);
    }
    
    /**
     * Faking DeepL requests, translating with rot13.
     * Supports the following calls: get languages, get usage and translate
     * @throws Exception
     */
    public function pangeamtAction() {
        //for deepl we expect always that requests:
        $this->expectHeader('accept-charset', 'UTF-8');
        $this->expectHeader('accept', 'application/json; charset=utf-8');
        $this->expectHeader('content-type', 'application/json; charset=utf-8');
        
        /* FIXME is this also the same for pangea????
         ** to test not authenticated requests:
         */
        //         header('HTTP/1.0 403 Forbidden');
        //         exit;
        
        header('Content-Type: application/json;charset=UTF-8', true);
        
        $calledUrl = $this->getUriApiPart();
        switch ($calledUrl) {
//             //Requesting which languages do exist:
//             //GET https://api.deepl.com:443/v2/languages?auth_key=xxxx
//             // [{"language":"DE","name":"German"},{"language":"EN","name":"English"},{"language":"ES","name":"Spanish"},{"language":"FR","name":"French"},{"language":"IT","name":"Italian"},{"language":"JA","name":"Japanese"},{"language":"NL","name":"Dutch"},{"language":"PL","name":"Polish"},{"language":"PT","name":"Portuguese"},{"language":"RU","name":"Russian"},{"language":"ZH","name":"Chinese"}]
//             case 'v2/languages' :
//                 echo '[{"language":"DE","name":"German"},{"language":"EN","name":"English"},{"language":"ES","name":"Spanish"},{"language":"FR","name":"French"},{"language":"IT","name":"Italian"},{"language":"JA","name":"Japanese"},{"language":"NL","name":"Dutch"},{"language":"PL","name":"Polish"},{"language":"PT","name":"Portuguese"},{"language":"RU","name":"Russian"},{"language":"ZH","name":"Chinese"}]';
//                 return;
                
//                 //Requesting how the usage is:
//                 //GET https://api.deepl.com:443/v2/usage?auth_key=xxxx
//                 //{"character_count":75323,"character_limit":3000000}
//             case 'v2/usage' :
//                     echo '{"character_count":75323,"character_limit":3000000}';
//                     return;
            case 'NexRelay/v1/corp/engines' :
                $data = $this->getInputFromRawJson();
                if(($data[0]->apikey ?? '') !== Zend_Registry::get('config')->runtimeOptions->plugins->PangeaMt->apikey) {
                    throw new Exception('Auth Key is not as expected');
                }
                echo '{"engines":[{"id":1,"processid":1,"serviceid":1,"inserviceid":0,"src":"en","tgt":"es","descr":"ENES_B_plain","domain":"","flavor":"","status":0},{"id":2,"processid":1,"serviceid":2,"inserviceid":0,"src":"auto","tgt":"en","descr":"intoEN_generic TEST","domain":"","flavor":"intoEN_generic","status":0},{"id":3,"processid":1,"serviceid":3,"inserviceid":0,"src":"de","tgt":"en","descr":"DEEN_generic TEST","domain":"","flavor":"DEEN_generic","status":0}]}';
                return;
            case 'NexRelay/v1/translate' :
                $this->expectHttpMethod('POST');
                $data = $this->getInputFromRawJson();
                if($data->src != 'de') {
                    throw new Exception('Input language expected "de".');
                }
                if($data->tgt != 'en') {
                    throw new Exception('Input language expected "de".');
                }
                if(!is_numeric($data->engine)) {
                    throw new Exception('Engine is not INT');
                }
                if(($data->{'0'}->apikey ?? '') !== Zend_Registry::get('config')->runtimeOptions->plugins->PangeaMt->apikey) {
                    throw new Exception('Auth Key is not as expected');
                }
                    
                //{"src":"de","tgt":"en","engine":"22","text":["ein"],"0":{"apikey":"cOrtesvalenc1anas"}}
                
                /* FIXME something similar for pangea???
                 ** enable this two lines to test Quota Exceeded
                 */
                //                 header('HTTP/1.0 456 Quota exceeded');
                //                 echo '{"message":"Quota Exceeded"}';
                //                 return;
                break;
            default:
                throw new Exception("Unknown PangeaMT endpoint requested: ".$calledUrl);
        }
        
        //FIXME produce an error???
        /*
         * Error Example!
         */
        $result = [];
        //HTTP result 200
        $demomt = new editor_Plugins_ZDemoMT_Connector();
            //additional tag:
            //$text = json_encode('<g id="1">'.$demomt->translateToRot13($_POST['text']));
            
            //remove a specific tag
            //$text = json_encode(str_replace('<x id="6"/>', '', $demomt->translateToRot13($_POST['text'])));
            //normal content:
        foreach($data->text as $text) {
            $oneResult = new stdClass();
            $oneResult->src = $text;
            $oneResult->tgt = $demomt->translateToRot13($text);
            $result[] = [$oneResult];
        }
            //[[{"src":"ein","tgt":"A"}],[{"src":"test","tgt":"Test"}]]
        echo json_encode($result);
    }
    
    /**
     * returns the URL without module, controller and action, just the API called part
     * @return string
     */
    protected function getUriApiPart(): string {
        //remove module, controller and action from URL:
        return join('/', array_slice(explode('/', $this->_request->getPathInfo()), 4));
    }
    
    /**
     * Verifies a header value
     * @param string $header
     * @param string $expected
     * @throws Exception
     */
    protected function expectHeader(string $header, string $expected) {
        $current = $this->_request->getHeader($header);
        if($current !== $expected) {
            throw new Exception('Header '.$header.' should be '.$expected.' but is '.$current);
        }
    }
    
    /**
     * Verifies a GET value
     * @throws Exception
     */
    protected function expectGet(string $key, string ...$expected) {
        if(!isset($_GET[$key]) || !in_array($_GET[$key], $expected, true)) {
            throw new Exception('GET '.$key.' should be one of '.join(',', $expected).' but is '.$_GET[$key]);
        }
    }
    
    /**
     * Verifies a POST value
     * @throws Exception
     */
    protected function expectPost(string $key, string ...$expected) {
        if(!isset($_POST[$key]) || !in_array($_POST[$key], $expected, true)) {
            throw new Exception('POST '.$key.' should be one of '.join(',', $expected).' but is '.$_POST[$key]);
        }
    }
    
    /**
     * Verifies the HTTP Method
     * @throws Exception
     */
    protected function expectHttpMethod(string $method) {
        $method = strtoupper($method);
        if($_SERVER['REQUEST_METHOD'] !== $method) {
            throw new Exception('HTTP Method should be '.$method.' but is '.$_SERVER['REQUEST_METHOD']);
        }
    }
    
    protected function getInputFromRawJson() {
        $rawInput = file_get_contents('php://input');
        if(empty($rawInput)) {
            throw new Exception('Raw Input was empty.');
        }
        return json_decode($rawInput);
    }
}

