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
        
        Zend_Layout::getMvcInstance()->setLayoutPath(APPLICATION_PATH.'/modules/editor/layouts/scripts/instanttranslate');
        
        // last selected source and target languages for the user (=> new table Zf_users_meta)
        $sourceSearchLanguagePreselectionLocale= 'de-DE'; // TODO; both content and structure of this content are DUMMY only!
        $targetSearchLanguagePreselectionLocale= 'en-GB'; // TODO; both content and structure of this content are DUMMY only!
        
        $this->view->sourceSearchLanguagePreselectionLocale = $sourceSearchLanguagePreselectionLocale;
        $this->view->targetSearchLanguagePreselectionLocale = $targetSearchLanguagePreselectionLocale;
        
        
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
                    'target' => $engine->toCulture,
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
        
        //translated strings
        $translatedStrings=array(
                "availableMTEngines"        => $this->translate->_("Verfügbare MT-Engines"),
                "noMatchingMt"              => $this->translate->_("Keine passende MT-Engine verfügbar. Bitte eine andere Sprachkombination wählen."),
                "selectMt"                  => $this->translate->_("Bitte eine der verfügbaren MT-Engines auswählen."),
                "translate"                 => $this->translate->_("Übersetzen"),
                "turnOffInstantTranslation" => $this->translate->_("Sofortübersetzung deaktivieren"),
                "turnOnInstantTranslation"  => $this->translate->_("Sofortübersetzung aktivieren")
        );
        $this->view->restPath=APPLICATION_RUNDIR.'/'.Zend_Registry::get('module').'/';
        $this->view->translations = $translatedStrings;
    }
    
    public function translateAction(){

        //get all requested params
        $params=$this->getRequest()->getParams();
        
        //validate single params
        $isValidParam=function($prm,$key){
          return isset($prm[$key]) && !empty($prm[$key]);
        };
        
        $apiParams=array();
        
        if(!$isValidParam($params,'text')){
            //TODO: translate me
            $this->_helper->json(array("errors"=>"No string for translation is provided"));
            return;
        }

        $apiParams['text']=$params['text'];
        if($isValidParam($params,'domainCode')){
            $apiParams['domainCode']=$params['domainCode'];
            $this->view->rows=$this->searchString($apiParams);
            return;
        }
        
        if(!$isValidParam($params,'source')){
            //TODO: translate me
            $this->_helper->json(array("errors"=>"Source language definition is missing."));
            return;
        }
        
        $apiParams['from']=$params['source'];
        
        if(!$isValidParam($params,'target')){
            //TODO: translate me
            $this->_helper->json(array("errors"=>"Target language definition is missing."));
            return;
        }
        
        $apiParams['to']=$params['target'];
        
        $this->_helper->json(array("rows"=>$this->searchString($apiParams)));
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
}