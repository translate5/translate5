<?php
/*
 START LICENSE AND COPYRIGHT

 This file is part of translate5

 Copyright (c) 2013 - 2022 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

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

use MittagQI\Translate5\Plugins\Okapi\Service;

/**
 * Upload/download file to okapi server, and converting it to xlf
 * One Connector Instance can contain one Okapi Project
 */
class editor_Plugins_Okapi_Connector {
    /**
     * Request timeout for the api
     * 
     * @var integer
     */
    const REQUEST_TIMEOUT_SECONDS = 360;
    
    /**
     * The file extenssion of the converted file
     *  
     * @var string
     */
    const OUTPUT_FILE_EXTENSION='.xlf';

    /**
     * The input types used as path part in the Okapi upload URL
     * @var string
     */
    const INPUT_TYPE_DEFAULT = ''; //needed for importing all files, export: the manifest.rkm
    const INPUT_TYPE_ORIGINAL = 'original'; //needed for export, place for the original (html) files
    const INPUT_TYPE_WORK = 'work';  //needed for export, place for the work (xlf) files

    /**
     * The url for the current  active project
     * @var string
     */
    private $projectUrl;

    /**
     * Server to use (what can be configured on task & customer level)
     * @var string|null
     */
    private ?string $serverToUse;

    /**
     * The OKAPI service
     * @var Service
     */
    private Service $service;

    /**
     * @param Zend_Config|null $config
     * @throws Zend_Exception
     */
    public function __construct(Zend_Config $config = null) {
        $this->serverToUse = is_null($config) ? Zend_Registry::get('config')->runtimeOptions->plugins->Okapi->serverUsed : $config->runtimeOptions->plugins->Okapi->serverUsed;
        $this->service = editor_Plugins_Okapi_Init::createService('okapi');
    }

    /**
     * Get the okapi api url from the configured servers and server used
     * @return string
     * @throws editor_Plugins_Okapi_Exception
     */
    public function getApiUrl(): string
    {
        return $this->service->getConfiguredServiceUrl($this->serverToUse) . '/';
    }

    /**
     * Create the http object, set the authentication and set the url
     * 
     * @param string $url
     * @return Zend_Http_Client
     */
    private function getHttpClient($url){
        $http = ZfExtended_Factory::get('Zend_Http_Client');
        /* @var $http Zend_Http_Client */
        $http->setUri($url);
        $http->setConfig(array('timeout'=>self::REQUEST_TIMEOUT_SECONDS));
        return $http;
    }
    
    /**
     * Check for the status of the response. If the status is different than 200 or 201,
     * ZfExtended_BadGateway exception is thrown.
     * Also the function checks for the invalid decoded json.
     * 
     * @param Zend_Http_Response $response
     * @throws ZfExtended_BadGateway
     * @throws ZfExtended_Exception
     * @return stdClass|string
     */
    private function processResponse(Zend_Http_Response $response){
        $validStates = [200,201,401];
        
        //check for HTTP State (REST errors)
        if(!in_array($response->getStatus(), $validStates)) {
            throw new ZfExtended_BadGateway("HTTP Status was not 200/201/401 body: ".$response->getBody(), 500);
        }
        
        return $response->getBody();
    }
    
    /**
     * Create the project on Okapi server.
     */
    public function createProject() {
        $http = $this->getHttpClient($this->getApiUrl().'projects/new');
        $response = $http->request('POST');
        $this->processResponse($response);
        $url=$response->getHeader('Location');
        $this->projectUrl= $url;
    }
    
    /**
     * Remove the project from Okapi server.
     */
    public function removeProject() {
        if(empty($this->projectUrl)) {
            return;
        };
        $http = $this->getHttpClient($this->projectUrl);
        $response= $http->request('DELETE');
        $this->processResponse($response);
    }

    /**
     * @param string $bconfPath
     * @param bool $forImport
     * @return void
     * @throws Zend_Http_Client_Exception
     * @throws ZfExtended_BadGateway
     * @throws ZfExtended_Exception
     * @throws editor_Plugins_Okapi_Exception
     */
    public function uploadOkapiConfig(string $bconfPath, bool $forImport){
        if(empty($bconfPath) || !file_exists($bconfPath)) {
             // 'Okapi Plug-In: Bconf not given or not found: {bconfFile}',
             throw new editor_Plugins_Okapi_Exception('E1055', ['bconfFile' => $bconfPath]);
        }
        // $bconfPath = '/var/www/translate5/application/modules/editor/Plugins/Okapi/data/okapi_default_import.bconf';
        $url = $this->projectUrl.'/batchConfiguration';
        $http = $this->getHttpClient($url);
        $http->setFileUpload($bconfPath , 'batchConfiguration');
        $response = $http->request('POST');
        try {
            $this->processResponse($response);
        } catch (ZfExtended_BadGateway $e) {
            // to improve support we add the name of the bconf as used in the Frontend (if possible) for import bconf's
            $msg = $e->getMessage();
            if($forImport){
                try {
                    $bconfId = (int) basename(dirname($bconfPath));
                    $bconf = new editor_Plugins_Okapi_Bconf_Entity();
                    $bconf->load($bconfId);
                    $bconfName = $bconf->getName();
                    $e->setMessage($msg . " \n" . 'Import-bconf used was \'' . $bconfName . '\' (id ' . $bconfId . ')');
                } catch(Throwable){
                    $e->setMessage($msg . " \n".'Import-bconf used was \''.basename($bconfPath).'\'');
                }
            } else {
                $e->setMessage($msg . " \n".'Export-bconf used was \''.basename($bconfPath).'\'');
            }
            throw $e;
        }
    }
    
    /**
     * Upload the source file(the file which will be converted)
     * @param string $fileName file name to be used in okapi
     * @param SplFileInfo $realFilePath path to the file to be uploaded
     */
    public function uploadInputFile($fileName, SplFileInfo $realFilePath){
        $this->uploadFile($fileName, $realFilePath, self::INPUT_TYPE_DEFAULT);
    }
    
    /**
     * Upload the original file for merging the XLF data into
     * @param string $fileName file name to be used in okapi
     * @param SplFileInfo $realFilePath path to the file to be uploaded
     */
    public function uploadOriginalFile($fileName, SplFileInfo $realFilePath){
        $this->uploadFile($fileName, $realFilePath, self::INPUT_TYPE_ORIGINAL);
    }
    
    /**
     * Upload the work file (XLF) to be merged into the original file
     * @param string $fileName file name to be used in okapi
     * @param SplFileInfo $realFilePath path to the file to be uploaded
     */
    public function uploadWorkFile($fileName, SplFileInfo $realFilePath){
        $this->uploadFile($fileName, $realFilePath, self::INPUT_TYPE_WORK);
    }
    
    /**
     * Upload the source file(the file which will be converted)
     * @param string $fileName
     * @param SplFileInfo $realFilePath
     * @param string $type
     */
    protected function uploadFile($fileName, SplFileInfo $realFilePath, $type){
        //PUT http://{host}/okapi-longhorn/projects/1/inputFiles/help.html
        //Ex.: Uploads a file that will have the name 'help.html'
        
        if(!empty($type)) {
            //add the upload type to the URL
            $fileName = $type.'/'.$fileName;
        }
        $url = $this->projectUrl.'/inputFiles/'.$fileName;
        $http = $this->getHttpClient($url);
        $http->setFileUpload($realFilePath,'inputFile');
        $response = $http->request('PUT');
        $this->processResponse($response);
    }
    
    /**
     * Run the file conversion. For each uploaded files converted file will be created
     */
    public function executeTask($source, $target){
        $url = $this->projectUrl.'/tasks/execute/'.$source.'/'.$target;
        $http = $this->getHttpClient($url);
        $response = $http->request('POST');
        $this->processResponse($response);
    }
    
    /**
     * Checks the default configured /system level) Okapi Service
     * TODO FIXME: this should be implemented in MittagQI\Translate5\Plugins\Okapi\Service ...
     */
    public function ping(){
        $url = $this->service->getServiceUrl();
        if(empty($url)) {
            return 'Okapi NOT configured!';
        }
        try {
            $http = $this->getHttpClient($url);
            $http->setConfig(['timeout' => 15]); //for ping just 15 seconds
            $response = $http->request('GET');
            $this->processResponse($response);
        }
        catch (Exception $e) {
            return 'Okapi '.$url.' DOWN!';
        }
        return 'Okapi '.$url.' UP!';
    }
    
    /**
     * Download the converted file from okapi, and save the file on the disk.
     * @param string $fileName
     * @param string $manifestFile
     * @param SplFileInfo $originalFile
     * @return string the path to the downloaded data file
     */
    public function downloadFile($fileName, $manifestFile, SplFileInfo $dataDir){
        $downloadedFile = $dataDir.'/'.$fileName.self::OUTPUT_FILE_EXTENSION;
        $url = $this->projectUrl.'/outputFiles/pack1/work/'.$fileName.self::OUTPUT_FILE_EXTENSION;
        $http = $this->getHttpClient($url);
        $response = $http->request('GET');
        $responseFile = $this->processResponse($response);
        file_put_contents($downloadedFile, $responseFile);
        
        //additionaly we save the manifest.rkm file to the disk, needed for export
        $url = $this->projectUrl.'/outputFiles/pack1/manifest.rkm';
        $http = $this->getHttpClient($url);
        $response = $http->request('GET');
        file_put_contents($dataDir.'/'.$manifestFile, $this->processResponse($response));
        
        return $downloadedFile;
    }
    
    /**
     * Download the converted file from okapi, and save the file on the disk.
     * @param string $fileName filename in okapi to get the file
     * @param string $manifestFile
     * @param SplFileInfo $originalFile
     */
    public function downloadMergedFile($fileName, SplFileInfo $targetFile){
        $http = $this->getHttpClient($this->projectUrl.'/outputFiles/'.$fileName);
        $response = $http->request('GET');
        file_put_contents($targetFile, $this->processResponse($response));
    }
}