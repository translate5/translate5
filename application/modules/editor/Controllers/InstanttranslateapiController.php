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

/***
 * Provide a interface to the sdl language cloud api
 */
class Editor_InstanttranslateapiController extends ZfExtended_RestController{
    
    public function init() {
        $this->_helper->viewRenderer->setNoRender(true);
        $this->_helper->layout->disableLayout();
        $this->events = ZfExtended_Factory::get('ZfExtended_EventManager', array(get_class($this)));
        $this->eventManager = Zend_EventManager_StaticEventManager::getInstance();
        $this->restMessages = ZfExtended_Factory::get('ZfExtended_Models_Messages');
        Zend_Registry::set('rest_messages', $this->restMessages);
        $this->log = ZfExtended_Factory::get('ZfExtended_Log');
    }
    
    public function translateAction(){

        //get all requested params
        $params=$this->getRequest()->getParams();
        
        if(!$this->isValidParam($params,'text')){
            
            $e=new ZfExtended_ValidateException();
            $e->setErrors(["No string for translation is provided"]);
            $this->handleValidateException($e);
            return;
        }

        if(!$this->isValidParam($params,'id')){
            $e=new ZfExtended_ValidateException();
            $e->setErrors(["Engine id is missing."]);
            $this->handleValidateException($e);
            return;
        }
        
        $trans=$this->searchResources($params['text'],$params['id']);
        $this->view->rows=$trans;
    }
    
    /***
     * upload file to sdl language cloud for translation
     */
    public function fileAction(){
        
        $upload = new Zend_File_Transfer();
        //$upload->addValidator('Extension', false, 'tbx');
        // Returns all known internal file information
        $files = $upload->getFileInfo();

        if(empty($files)){
            $e=new ZfExtended_ValidateException();
            $e->setErrors(["No upload files were found"]);
            $this->handleValidateException($e);
            return;
        }
        
        $reqParams=$this->getRequest()->getParams();
        
        //if there is no engine domain defined or source and target language, return an error message
        if(!$this->isValidParam($reqParams,'domainCode') && (!$this->isValidParam($reqParams,'from') || !$this->isValidParam($reqParams,'to'))){
            $e=new ZfExtended_ValidateException();
            $e->setErrors("Engine domainCode or source and target language parametars are missing");
            $this->handleValidateException($e);
            return;
        }
        
        //load all languages (sdl api use iso6393 langage shortcuts)
        $langModel=ZfExtended_Factory::get('editor_Models_Languages');
        /* @var $langModel editor_Models_Languages */
        $lngs=$langModel->loadAllKeyValueCustom('rfc5646','iso6393');

        //get the iso language value for the given rfc
        $reqParams['from']=$lngs[$reqParams['from']];
        $reqParams['to']=$lngs[$reqParams['to']];
        
        $dummyTmmt=ZfExtended_Factory::get('editor_Models_TmMt');
        /* @var $dummyTmmt editor_Models_TmMt */
        $api=ZfExtended_Factory::get('editor_Services_SDLLanguageCloud_HttpApi',[$dummyTmmt]);
        /* @var $api editor_Services_SDLLanguageCloud_HttpApi */
        
        $reqParams['file']=$files[0];
        
        $response=$api->uploadFile($reqParams);
        if(!$response){
            $e=new ZfExtended_ValidateException();
            $e->setErrors($api->getErrors());
            $this->handleValidateException($e);
            return;
        }
        $this->view->fileId=$api->getResult()->id;
    }
    
    /***
     * check for url for the uploaded file. If the file translation is finished, downloadUrl will be provided by sdl api
     */
    public function urlAction(){
        $requestParams=$this->getRequest()->getParams();
        
        if(!$this->isValidParam($requestParams, 'fileId')){
            $e=new ZfExtended_ValidateException();
            $e->setErrors("File id parametar is not valid");
            $this->handleValidateException($e);
            return;
        }
        
        $url=$this->getDownloadUrl($requestParams['fileId']);
        $this->view->downloadUrl=$url;
    }
    
    /***
     * Download the file. Download url should be provided as request parametar.
     */
    public function downloadAction(){
        $requestParams=$this->getRequest()->getParams();
        
        if(!$this->isValidParam($requestParams, 'url')){
            $e=new ZfExtended_ValidateException();
            $e->setErrors("Url parametar is not valid");
            $this->handleValidateException($e);
            return;
        }
        
        if(!$this->isValidParam($requestParams, 'fileName')){
            $e=new ZfExtended_ValidateException();
            $e->setErrors("FileName parametar is not valid");
            $this->handleValidateException($e);
            return;
        }
        
        $dummyTmmt=ZfExtended_Factory::get('editor_Models_TmMt');
        /* @var $dummyTmmt editor_Models_TmMt */
        $api=ZfExtended_Factory::get('editor_Services_SDLLanguageCloud_HttpApi',[$dummyTmmt]);
        /* @var $api editor_Services_SDLLanguageCloud_HttpApi */
        
        $localFile=$api->downloadFile($requestParams['url'],$requestParams['fileName']);

        $this->view->layout()->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);
        
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="'.$requestParams['fileName'].'"');
        header('Content-Transfer-Encoding: binary');
        header('Expires: 0');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Pragma: public');
        ob_clean();
        flush();
        readfile($localFile);
        unlink($localFile);
        exit();
    }
    
    public function engineAction() {
        $engineModel=ZfExtended_Factory::get('editor_Models_LanguageResources_SdlResources');
        /* @var $engineModel editor_Models_LanguageResources_SdlResources */
        $this->view->rows=$engineModel->getAllEngines();
    }
    
    /***
     * Get the download file status from sdl language cloud
     * @param string $fileId
     * @return string
     */
    private function getDownloadUrl($fileId){
        $dummyTmmt=ZfExtended_Factory::get('editor_Models_TmMt');
        /* @var $dummyTmmt editor_Models_TmMt */
        $api=ZfExtended_Factory::get('editor_Services_SDLLanguageCloud_HttpApi',[$dummyTmmt]);
        /* @var $api editor_Services_SDLLanguageCloud_HttpApi */

        $api->getFileStatus($fileId);
        $result=$api->getResult();
        
        if(isset($result->result) && isset($result->result->downloadURL)){
            return $result->result->downloadURL;
        }
        
        return "";
    }
    
    /***
     * Search all available language resources assigned to customer of a user for a given language combo.
     * 
     * @param string $text : query string
     * @param integer $id : language resource id (engine id)
     */
    private function searchResources($text,$id){
        $model=ZfExtended_Factory::get('editor_Models_TmMt');
        /* @var $model editor_Models_TmMt */
        $model->load($id);
        $sourceLang=$model->getSourceLang();
        $targetLang=$model->getTargetLang();
        
        //update the default selected languages for the curent user
        $this->updateDefaultLanguages($sourceLang,$targetLang);
        
        //get all resources for the customers of the user by language combination
        $resources=$model->loadByUserCustomerAssocs(null,$sourceLang,$targetLang);
        
        if(empty($resources)){
            return '';
        }
        
        $searchResults=[];
        //for each assoc resource search the resource for the result
        foreach ($resources as $res) {
            
            $model=ZfExtended_Factory::get('editor_Models_TmMt');
            /* @var $model editor_Models_TmMt */
            $model->init($res);
            
            $manager = ZfExtended_Factory::get('editor_Services_Manager');
            /* @var $manager editor_Services_Manager */
            
            $connector=$manager->getConnector($model,$sourceLang,$targetLang);
            /* @var $connector editor_Services_Connector_Abstract */
            
            //init dummy segment so the query action can be used
            $segment=ZfExtended_Factory::get('editor_Models_Segment');
            /* @var $segment editor_Models_Segment */
            $segment->setMid(-1);
            $segment->setFileId(-1);
            $connector->fileNameCache['-1']="InstantTranslate";
            
            $sfm=ZfExtended_Factory::get('editor_Models_SegmentFieldManager');
            /* @var $sfm editor_Models_SegmentFieldManager */
            
            $sfm->setByName('source', $text);
            $segment->setFieldContents($sfm, [
                'source'=>[
                    'original'=>$text,
                    'editable'=>true
                ]
            ]);
            
            //query the resource
            $result = $connector->query($segment);
            
            //check and filter the results
            $results = $this->filterResults($text,$result->getResult());
            
            if(!isset($searchResults[$model->getServiceName()])){
                $searchResults[$model->getServiceName()]=[];
            }
            
            $searchResults[$model->getServiceName()][$model->getName()]=$results;
        }
        
        return $searchResults;
    }
    
    /***
     * Filter results by minimum configured match rate.
     * The differences between the search string and the result source will be marked with tags.
     * 
     * @param string $searchText
     * @param stdClass $results
     * @return array
     */
    private function filterResults($searchText,$results){
        
        $config = Zend_Registry::get('config');
        $matchRate=$config->runtimeOptions->InstantTranslate->minMatchRateBorder;
        
        $collectedResults=[];
        foreach ($results as $result) {
            //if 100 matchrate, collect the result
            if($result->matchrate>=100 || $result->matchrate==0){
                $collectedResults[]=$result;
                continue;
            }
            
            //if the matchrate is over or equal to the border, highlight the diff and collect the result
            if($result->matchrate>=$matchRate){
                $result->sourceDiff=$this->highlightDif($result->source,$searchText);
                $collectedResults[]=$result;
                continue;
            }
        }
        return $collectedResults;
    }
    
    /***
     * highlight the diff between the search string and the source result
     * 
     * @param string $source
     * @param string $target
     * @return string
     */
    private function highlightDif($source,$target){
        $difTagger=ZfExtended_Factory::get('editor_Models_Export_DiffTagger_Csv');
        /* @var $difTagger editor_Models_Export_DiffTagger_Csv */
        
        $sourceArr = $difTagger->tagBreakUp($source);
        $targetArr = $difTagger->tagBreakUp($target);
        
        $sourceArr = $difTagger->wordBreakUp($sourceArr);
        $targetArr = $difTagger->wordBreakUp($targetArr);
        
        $diff = ZfExtended_Factory::get('ZfExtended_Diff');
        /* @var $diff ZfExtended_Diff */
        $diffRes = $diff->process($sourceArr, $targetArr);

        //add highlight tag
        $addTags=function($value){
            if (count($value) > 0) {
                
                $addition = implode('', $value);
                if($addition === '')return;
                return '<span class="highlight">' . $addition . '</span>';
            }
            return '';
        };
        
        foreach ($diffRes as $key => &$val) {
            if (is_array($val)) {
                $val['i'] = $addTags($val['i']);
                $val['d'] ='';
                $val = implode('', $val);
            }
        }
        return implode('', $diffRes);
        
    }
    
    /***
     * Check if the request param is valid in array
     * @param array $prm
     * @param string $key
     * @return boolean
     */
    private function isValidParam($prm,$key){
        return isset($prm[$key]) && !empty($prm[$key]);
    }
    
    /***
     * Update the source and target default languages for the current user
     * @param integer $source
     * @param integer $target
     */
    private function updateDefaultLanguages($source,$target){
        $sessionUser = new Zend_Session_Namespace('user');
        $sessionUser=$sessionUser->data;
        $userModel=ZfExtended_Factory::get('editor_Models_UserMeta');
        /* @var $userModel editor_Models_UserMeta */
        
        //save the default preselected languages for the curent user
        $userModel->saveDefaultLanguages($sessionUser->id,$source,$target);
    }
}