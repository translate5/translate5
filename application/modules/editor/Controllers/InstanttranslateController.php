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

class Editor_InstanttranslateController extends ZfExtended_Controllers_Action {
    
    public function indexAction(){
        
        $this->translate = ZfExtended_Zendoverwrites_Translate::getInstance();
        
        //$config = Zend_Registry::get('config');
        
        Zend_Layout::getMvcInstance()->setLayout('instanttranslate');
        Zend_Layout::getMvcInstance()->setLayoutPath(APPLICATION_PATH.'/modules/editor/layouts/scripts');
        $this->view->render('instanttranslate/layoutConfig.php');
        
        // last selected source and target languages for the user (=> new table Zf_users_meta)
        $sourceSearchLanguagePreselectionLocale= 'en-US'; // TODO; both content and structure of this content are DUMMY only!
        $targetSearchLanguagePreselectionLocale= 'de-DE'; // TODO; both content and structure of this content are DUMMY only!
        
        $this->view->sourceSearchLanguagePreselectionLocale = $sourceSearchLanguagePreselectionLocale;
        $this->view->targetSearchLanguagePreselectionLocale = $targetSearchLanguagePreselectionLocale;

        //TODO: from config, but this returns always empti obj in frontend
        //$this->view->Php2JsVars()->set('languageresource.fileExtension',$config->runtimeOptions->LanguageResources->fileExtension);
        $this->view->Php2JsVars()->set('languageresource.fileExtension',
            [
                "de-DE,en-GB"=>["txt","csv"],
                "en-US,ru-RU"=>["txt","csv"],
                "en-US,de-DE"=>["txt","csv"]
                
            ]);
        
        $dummyTmmt=ZfExtended_Factory::get('editor_Models_TmMt');
        /* @var $dummyTmmt editor_Models_TmMt */
        
        $api=ZfExtended_Factory::get('editor_Services_SDLLanguageCloud_HttpApi',[$dummyTmmt]);
        /* @var $api editor_Services_SDLLanguageCloud_HttpApi */
        
        
        $result=null;
        //load all available engines
        if($api->getEngines()){
            $result=$api->getResult();
        }
        
        $machineTranslationEngines=array();

        //if the available engines exist, merge them to frontend var, so they can be used for trans
        if($result->totalCount>0){
            $engineCounter=1;
            foreach($result->translationEngines as $engine){
                $machineTranslationEngines['mt'.$engineCounter]=array(
                    'name' =>$engine->type.', ['.$engine->fromCulture.','.$engine->toCulture.']', 
                    'source' => $engine->fromCulture,
                    'sourceIso' => $engine->from->code,
                    'target' => $engine->toCulture,
                    'targetIso' => $engine->to->code,
                    'domainCode' => $engine->domainCode
                );
                $engineCounter++;
            }
        }
        
        // available MachineTranslation-Engines
        /*$machineTranslationEngines = array(  // TODO; both content and structure of this content are DUMMY only!
                'mt1' => array('name' => 'MT Engine 1', 'source' => 'de-DE', 'target' => 'en-US'),
                'mt2' => array('name' => 'MT Engine 2', 'source' => 'de-DE', 'target' => 'en-GB'),
                'mt3' => array('name' => 'MT Engine 3', 'source' => 'fr-FR', 'target' => 'en-GB'),
                'mt4' => array('name' => 'MT Engine 4', 'source' => 'de-DE', 'target' => 'en-GB')
        );
        */
        $this->view->machineTranslationEngines= $machineTranslationEngines;
        
        $rfcToIsoLanguage=array();
        $lngModel=ZfExtended_Factory::get('editor_Models_Languages');
        /* @var $lngModel editor_Models_Languages */
        $lngs=$lngModel->loadAll();
        foreach($lngs as $l){
            $rfcToIsoLanguage[$l['rfc5646']]=$l['iso6393'];
        }
        
        $this->view->Php2JsVars()->set('languageresource.rfcToIsoLanguage',$rfcToIsoLanguage);
        
        //translated strings
        $translatedStrings=array(
                "availableMTEngines"        => $this->translate->_("Verfügbare MT-Engines"),
                "clearText"                 => $this->translate->_("Text zurücksetzen"),
                "copy"                      => $this->translate->_("Kopieren"),
                "enterText"                 => $this->translate->_("Geben Sie Text ein"),
                "foundMt"                   => $this->translate->_("MT-Engines gefunden"),
                "noMatchingMt"              => $this->translate->_("Keine passende MT-Engine verfügbar. Bitte eine andere Sprachkombination wählen."),
                "selectMt"                  => $this->translate->_("Bitte eine der verfügbaren MT-Engines auswählen."),
                "serverErrorMsg500"         => $this->translate->_("Die Anfrage führte zu einem Fehler im angefragten Dienst."),
                "translate"                 => $this->translate->_("Übersetzen"),
                "orTranslateFile"           => $this->translate->_("oder lassen Sie ein Dokument übersetzen."),
                "translateText"             => $this->translate->_("lassen Sie Text übersetzen, den Sie eingeben."),
                "turnOffInstantTranslation" => $this->translate->_("Sofortübersetzung deaktivieren"),
                "turnOnInstantTranslation"  => $this->translate->_("Sofortübersetzung aktivieren"),
                "uploadFileOr"              => $this->translate->_("Laden Sie eine Datei hoch oder"),
                "pleaseChoose"              => $this->translate->_("Bitte auswählen"),
                "clearBothLists"            => $this->translate->_("Beide Listen zurücksetzen"),
                "showAllAvailableFor"       => $this->translate->_("Alle anzeigen für")
                
        );
        $this->view->restPath=APPLICATION_RUNDIR.'/'.Zend_Registry::get('module').'/';
        $this->view->translations = $translatedStrings;
    }
    
    public function translateAction(){

        //get all requested params
        $params=$this->getRequest()->getParams();
        
        $apiParams=array();
        
        if(!$this->isValidParam($params,'text')){
            //TODO: translate me
            $this->_helper->json(array("errors"=>"No string for translation is provided"));
            return;
        }

        $apiParams['text']=$params['text'];
        if($this->isValidParam($params,'domainCode')){
            $apiParams['domainCode']=$params['domainCode'];
            $this->_helper->json(array("rows"=>$this->searchString($apiParams)));
        }
        
        if(!$this->isValidParam($params,'source')){
            //TODO: translate me
            $this->_helper->json(array("errors"=>"Source language definition is missing."));
            return;
        }
        
        $apiParams['from']=$params['source'];
        
        if(!$this->isValidParam($params,'target')){
            //TODO: translate me
            $this->_helper->json(array("errors"=>"Target language definition is missing."));
            return;
        }
        
        $apiParams['to']=$params['target'];
        $trans=$this->searchString($apiParams);
        $this->_helper->json(array("rows"=>$trans));
    }
    
    public function fileAction(){
        
        $upload = new Zend_File_Transfer();
        //$upload->addValidator('Extension', false, 'tbx');
        // Returns all known internal file information
        $files = $upload->getFileInfo();

        if(empty($files)){
            $this->_helper->json(array("error"=>"No upload files were found"));
        }
        
        $reqParams=$this->getRequest()->getParams();
        
        //if there is no engine domain defined or source and target language, return an error message
        if(!$this->isValidParam($reqParams,'domainCode') && (!$this->isValidParam($reqParams,'from') || !$this->isValidParam($reqParams,'to'))){
            $this->_helper->json(array("error"=>"Engine domainCode or source and target language parametars are missing"));
        }
        
        
        $dummyTmmt=ZfExtended_Factory::get('editor_Models_TmMt');
        /* @var $dummyTmmt editor_Models_TmMt */
        $api=ZfExtended_Factory::get('editor_Services_SDLLanguageCloud_HttpApi',[$dummyTmmt]);
        /* @var $api editor_Services_SDLLanguageCloud_HttpApi */
        
        $reqParams['file']=$files[0];
        
        $response=$api->uploadFile($reqParams);
        if(!$response){
            //TODO handle errors to the frontend
            error_log(print_r($api->getErrors(),1));
            $messages='';
            foreach($api->getErrors() as $error){
                $messages=$error->body.'\n';
            }
            //$ex=new ZfExtended_ValidateException();
           // $ex->setErrors(["Unable to process the request.".$messages]);
            //            throw $ex;
            //$this->_helper->json(array("error"=>"Unable to process the request.".$messages));
            
            $this->_helper->json(array("error"=>"Unable to process the request."));
        }
        $this->_helper->json(array("fileId"=>$api->getResult()->id));
    }
    
    public function urlAction(){
        $requestParams=$this->getRequest()->getParams();
        
        if(!$this->isValidParam($requestParams, 'fileId')){
            $this->_helper->json(array("error"=>"File id parametar is not valid"));
        }
        
        $url=$this->getDownloadUrl($requestParams['fileId']);
        $this->_helper->json(array("downloadUrl"=>$url));
    }
    
    public function downloadAction(){
        $requestParams=$this->getRequest()->getParams();
        
        if(!$this->isValidParam($requestParams, 'url')){
            $this->_helper->json(array("error"=>"Url parametar is not valid"));
        }
        
        if(!$this->isValidParam($requestParams, 'fileName')){
            $this->_helper->json(array("error"=>"FileName parametar is not valid"));
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
     * Run translation for given params
     * @param array $params
     */
    private function searchString($params){
        $dummyTmmt=ZfExtended_Factory::get('editor_Models_TmMt');
        /* @var $dummyTmmt editor_Models_TmMt */
        $api=ZfExtended_Factory::get('editor_Services_SDLLanguageCloud_HttpApi',[$dummyTmmt]);
        /* @var $api editor_Services_SDLLanguageCloud_HttpApi */
        
        $result=null;
        if($api->search($params)){
            $result=$api->getResult();
        }
        return isset($result->translation) ? $result->translation : "";
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
}