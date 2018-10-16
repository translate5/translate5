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
 * This controller is used for rendering the instant translate preview
 */
class Editor_InstanttranslateController extends ZfExtended_Controllers_Action {
    
    public function indexAction(){
        
        $this->translate = ZfExtended_Zendoverwrites_Translate::getInstance();
        
        $config = Zend_Registry::get('config');
        Zend_Layout::getMvcInstance()->setLayout('instanttranslate');
        Zend_Layout::getMvcInstance()->setLayoutPath(APPLICATION_PATH.'/modules/editor/layouts/scripts');
        $this->view->render('instanttranslate/layoutConfig.php');
        
        //set the user preselected languages from the user meta table
        $this->setDefaultLanguages();

        //supported file uploads and extensions per language combination
        $this->view->Php2JsVars()->set('languageresource.fileExtension',$config->runtimeOptions->LanguageResources->fileExtension->toArray());
        
        $cs=ZfExtended_Factory::get('editor_Models_LanguageResources_CustomerAssoc');
        /* @var $cs editor_Models_LanguageResources_CustomerAssoc */
        $this->view->Php2JsVars()->set('languageresource.useAsDefault',$cs->findAsDefaultForUser());

        $engineModel=ZfExtended_Factory::get('editor_Models_LanguageResources_LanguageResource');
        /* @var $engineModel editor_Models_LanguageResources_LanguageResource */

        //load all available mt resources(engines)
        $this->view->machineTranslationEngines= $engineModel->getEnginesByAssoc();
        
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
                "orTranslateFile"           => $this->translate->_("oder lassen Sie ein Dokument übersetzen"),
                "orTranslateText"           => $this->translate->_("oder lassen Sie Text übersetzen, den Sie eingeben."),
                "turnOffInstantTranslation" => $this->translate->_("Sofortübersetzung deaktivieren"),
                "turnOnInstantTranslation"  => $this->translate->_("Sofortübersetzung aktivieren"),
                "uploadFile"                => $this->translate->_("Laden Sie eine Datei hoch"),
                "pleaseChoose"              => $this->translate->_("Bitte auswählen"),
                "clearBothLists"            => $this->translate->_("Beide Listen zurücksetzen"),
                "showAllAvailableFor"       => $this->translate->_("Alle anzeigen für"),
                "notAllowed"                => $this->translate->_("nicht erlaubt"),
                "machineTranslation"        => $this->translate->_("Maschinenübersetzung"),
                "selectedMtEngine"          => $this->translate->_("MT-Engine")
                
        );
        $this->view->Php2JsVars()->set('languageresource.translatedStrings',$translatedStrings);
        $this->view->translations= $translatedStrings; // we also need some of them outside of javascript
        
        $this->view->Php2JsVars()->set('restpath',APPLICATION_RUNDIR.'/'.Zend_Registry::get('module').'/');
        
        $this->view->Php2JsVars()->set('termStatusMap', $config->runtimeOptions->tbx->termLabelMap->toArray());
        $this->view->Php2JsVars()->set('termStatusLabel', [
            'permitted' => $this->translate->_('erlaubte Benennung'),
            'forbidden' => $this->translate->_('verbotene Benennung'),
            'preferred' => $this->translate->_('Vorzugsbenennung'),
            'unknown' => $this->translate->_('Unbekannter Term Status'),
        ]);
        
        $this->view->publicModulePath = APPLICATION_RUNDIR.'/modules/'.Zend_Registry::get('module');
        $this->view->Php2JsVars()->set('moduleFolder', $this->view->publicModulePath.'/');
        
    }
    
    /***
     * Set the default languages for preselection for the loged user
     */
    private function setDefaultLanguages(){
        $sessionUser = new Zend_Session_Namespace('user');
        $sessionUser=$sessionUser->data;
        $userModel=ZfExtended_Factory::get('editor_Models_UserMeta');
        /* @var $userModel editor_Models_UserMeta */
        try{
            //get the meta for the curent user
            $userModel->loadByUser($sessionUser->id);
        }catch(ZfExtended_Models_Entity_NotFoundException $e){
            $this->view->sourceSearchLanguagePreselectionLocale=null;
            $this->view->targetSearchLanguagePreselectionLocale=null;
            return;
        }
        
        //load and covert the id values to rfc
        $lang=ZfExtended_Factory::get('editor_Models_Languages');
        /* @var $lang editor_Models_Languages */
        $ret=$lang->loadAllKeyValueCustom('id', 'rfc5646');
        $this->view->sourceSearchLanguagePreselectionLocale=$ret[$userModel->getSourceLangDefault()];
        $this->view->targetSearchLanguagePreselectionLocale=$ret[$userModel->getTargetLangDefault()];
    }
}