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
 translate5 plug-ins that are distributed under GNU AFFERO GENERAL PUBLIC LICENSE version 3:
 Please see http://www.translate5.net/plugin-exception.txt or plugin-exception.txt in the root
 folder of translate5.
  
 @copyright  Marc Mittag, MittagQI - Quality Informatics
 @author     MittagQI - Quality Informatics
 @license    GNU AFFERO GENERAL PUBLIC LICENSE version 3 with plugin-execption
			 http://www.gnu.org/licenses/agpl.html http://www.translate5.net/plugin-exception.txt

END LICENSE AND COPYRIGHT
*/

/**
 * Connector to Globalese
 * One Connector Instance can contain one Globalese Project
 * 
 * FIXME errorhandling: throwing meaningful exceptions here on connection problems should be enough. Test it!
 *       for error handling: either you distinguish here between critical (stops processing in the Worker) or non critical (Worker can proceed) errors
 *       or you always throw here exceptions and you decide in the worker if the exceptions is critical or not
 */
class editor_Plugins_GlobalesePreTranslation_Connector {
    
    /***
     * 
     * @var $config Zend_Config
     */
    private $globaleseConfig;
    
    /***
     * 
     * @var string
     */
    private $apiUrl;
    
    /***
     *
     * @var string
     */
    private $username;
    
    /***
     *
     * @var string
     */
    private $apiKey;
    
    /***
     * 
     * @var int
     */
    private $globaleseProjectId;
    
    /***
     * 
     * @var array
     */
    private $globaleseFileIds=[];
    
    public function __construct() {
        $this->globaleseConfig= Zend_Registry::get('config')->runtimeOptions->plugins->GlobalesePreTranslation;
        /* @var $config Zend_Config */
        
        $this->apiUrl=$this->globaleseConfig->api->url;
    }
    
    private function authenticate(){
        
    }
    
    private function getHttpClient(){
        $http = ZfExtended_Factory::get('Zend_Http_Client');
        /* @var $http Zend_Http_Client */
        
        $http->setAuth($this->username,$this->apiKey);
        
        //error_log($http);
        return $http;
    }
    
    /**
     * FIXME implement me
     * @param editor_Models_Task $task
     * @return integer
     */
    public function createProject(editor_Models_Task $task) {
        $projectname = "Translate5-".$task->getTaskGuid();
        $sourceLang="en-gb";
        $targetLang="de-de";
        $group=1;
        $engine=1;
        
        $http = $this->getHttpClient();
        $http->setUri($this->apiUrl.'projects');
        $http->setHeaders('Content-Type: application/json');
        $http->setHeaders('Accept: application/json');
        
        $params = [
                'name'=>$projectname,
                'source-language'=>$sourceLang,
                'target-language'=>$targetLang,
                'group'=>$group,
                'engine'=>$engine
        ];
        
        $http->setRawData(json_encode($params), 'application/json');
        $result = $http->request('POST');
        
        error_log(print_r("-----------------------CREATE PROJECT START",1));
        error_log(print_r($result,1));
        error_log(print_r("-----------------------CREATE PROJECT END",1));
        
        $decode = json_decode($result->getBody());
        
        if(isset($decode->id)){
            $this->globaleseProjectId = $decode->id;
            return $decode->id;
        }
        //FIXME this here throws an erro, idk why !!
        //$langModel = ZfExtended_Factory::get('ZfExtended_Languages');
        /* @var $langModel ZfExtended_Languages */
        //$langarray = $langModel->loaderByIds('id',$ids);
        
        
        //the project name can be: "Translate5 ".$taskGuid I dont see any need to transfer our real taskname
        //save task internally for getting the languages from
        //save the projectId internally for further processing
        return 123; //returns the new created project id
    }
    
    /**
     * FIXME implement me
     * @param integer $projectId
     */
    public function removeProject() {
        $http = $this->getHttpClient();
        $http->setUri($this->apiUrl.'projects/'.$this->globaleseProjectId);
        
        $result = $http->request('DELETE');
    }
    
    /**
     * FIXME implement me
     * 
     * @param string $filename
     * @param string $xliff the xliff content as plain string
     * @return integer the fileid of the generated file
     */
    public function upload($filename, $xliff) {
        //throw an error if internal projectId is empty
        if(!$this->globaleseProjectId){
            throw new Exception();
        }
        
        $fileId = $this->createFile($filename);
        $this->uplodFile($fileId, $xliff);
        $this->translateFile($fileId);
        
        array_push($this->globaleseFileIds, $fileId);
        
        return $fileId;
        
        //creates file in Globalese
        //uploads file to Globalese
        //starts translation in Globalese
        $this->dummyXliff = str_replace('<target state="needs-translation"></target>', '<target state="needs-review-translation" state-qualifier="leveraged-mt" translate5:origin="Globalese">Dummy translated Text</target>', $xliff); 
        $this->dummyFileId = rand();
        return $this->dummyFileId; //fileid
    }
    
    /***
     * 
     */
    private function createFile($filename){
        $sourceLang="en-gb";
        $targetLang="de-de";
        
        $http = $this->getHttpClient();
        $http->setUri($this->apiUrl.'translation-files');
        $http->setHeaders('Content-Type: application/json');
        $http->setHeaders('Accept: application/json');
        
        $params = [
                'project'=>$this->globaleseProjectId,
                'name'=>$filename,
                'source-language'=>$sourceLang,
                'target-language'=>$targetLang
        ];
        
        $http->setRawData(json_encode($params), 'application/json');
        $result = $http->request('POST');
        
        $result=json_decode($result->getBody());
        
        if(!$result->id){
            return null;
        }
        return $result->id;
    }
    
    private function uplodFile($fileId,$xliff){
        $http = $this->getHttpClient();
        $http->setUri($this->apiUrl.'translation-files/'.$fileId);
        
        $http->setRawData($xliff);
        $result = $http->request('POST');
        
        $result=json_decode($result->getBody());
        
        error_log(print_r("fieldId->(".$fileId.")",1));
        error_log(print_r($result,1));
        
        //if(!$result->id){
        //    return null;
       // }
        //return $result->id;
    }
    
    private function translateFile($fileId){
        $http = $this->getHttpClient();
        $http->setUri($this->apiUrl.'translation-files/'.$fileId.'/translate');
        $result = $http->request('POST');
    }
    
    private function deleteFile($fieldId){
        $http = $this->getHttpClient();
        $http->setUri($this->apiUrl.'translation-files/'.$fileId);
        $result = $http->request('DELETE');
    }
    
    /**
     * returns the first found translated fileid, 
     * @return mixed fileId of found file, null when there are pending files but non finished, false if there are no more files
     */
    public function getFirstTranslated() {
        foreach ($this->globaleseFileIds as $fileId){
            $http = $this->getHttpClient();
            $http->setUri($this->apiUrl.'translation-files/'.$fileId);
            $result = $http->request('POST');
            $decode = json_decode($result->getBody());
        }
        //FIXME implement me and test me with all possible results
        //loops over all results and logs and deletes files with "failed" status 
        //returns the first found translated fileid, null if none found
        return $this->dummyFileId;
    }
    
    /**
     * gets the file content to the given fileid 
     * @param integer $fileId
     * @param boolean $remove default true, if true removes the fetched file immediatelly from Globalese project
     * @return string
     */
    public function getFileContent($fileId, $remove = true) {
        //FIXME implement me
        $this->dummyFileId = false;
        return $this->dummyXliff;
    }
    
    public function getEngines(){
        $http = $this->getHttpClient();
        //$http->setUri($this->apiUrl.'engines/?source=en&groups=1');
        $http->setUri($this->apiUrl.'engines/3');
        $http->setHeaders('Content-Type: application/json');
        $http->setHeaders('Accept: application/json');
        $result = $http->request();
        
        if($result->getStatus() != "200") {
            throw new Exception($result->getBody(), 500);
        }
        
        error_log(print_r("aaaaaaaaaaaaaaaaa",1));
        error_log(print_r($result->getBody(),1));
        $result = json_decode($result->getBody());
        error_log(print_r("bbbbbbbbbbbbbbbbb",1));
        
        //$result = json_decode($result->getBody());
        if(json_last_error()) {
            throw new Exception("Error on JSON decode: ".json_last_error_msg(), 500);
        }
        
        return $result;
    }
    
    public function getGroups(){
        $http = $this->getHttpClient();
        $http->setUri($this->apiUrl.'groups');
        $http->setHeaders('Content-Type: application/json');
        $http->setHeaders('Accept: application/json');
        $result = $http->request();
        
        if($result->getStatus() != "200") {
            throw new Exception($result->getBody(), 500);
        }
        
        $result = json_decode($result->getBody());
        if(json_last_error()) {
            throw new Exception("Error on JSON decode: ".json_last_error_msg(), 500);
        }
        
        return $result;
    }
    
    public function setAuth($username,$apiKey){
        $this->username = $username;
        $this->apiKey = $apiKey;
    }
}