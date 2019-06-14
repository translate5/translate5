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

class Editor_TermportalController extends ZfExtended_Controllers_Action {
    
    public function indexAction(){

        Zend_Layout::getMvcInstance()->setLayout('termportal');
        Zend_Layout::getMvcInstance()->setLayoutPath(APPLICATION_PATH.'/modules/editor/layouts/scripts');
        $this->view->render('termportal/layoutConfig.php');
        $this->view->appVersion = ZfExtended_Utils::getAppVersion();
        
        $this->view->Php2JsVars()->set('termportal.restPath', APPLICATION_RUNDIR.'/'.Zend_Registry::get('module').'/');
        
        //if term param exist, open this term on portal load
        if($this->getRequest()->getParam('term')!=null){
            $this->view->Php2JsVars()->set('term', $this->getRequest()->getParam('term'));
        }
        
        
        $userSession = new Zend_Session_Namespace('user');
        $this->view->Php2JsVars()->set('app.user.isInstantTranslateAllowed', in_array('instantTranslate', $userSession->data->roles)); // see FIXME for isInstantTranslateAllowed()? ("use the rights not the roles here!!!!")
        
        $userModel=ZfExtended_Factory::get('ZfExtended_Models_User');
        /* @var $userModel ZfExtended_Models_User */
        $customers=$userModel->getUserCustomersFromSession();
        $this->translate = ZfExtended_Zendoverwrites_Translate::getInstance();
        
        if(empty($customers)){
            $this->view->error=$this->translate->_("Ihnen sind derzeit keine Kundenverknüpfungen und damit auch keine TermCollections zugeordnet. Daher ist auch keine Termsuche möglich.");
            return;
        }
        
        $config = Zend_Registry::get('config');
        $defaultLangs=$config->runtimeOptions->termportal->defaultlanguages->toArray();
        
        $this->view->Php2JsVars()->set('instanttranslate.showSublanguages', $config->runtimeOptions->InstantTranslate->showSubLanguages);
        
        $langsArray = array();
        
        $model=ZfExtended_Factory::get('editor_Models_Languages');
        /* @var $model editor_Models_Languages */
        
        $this->view->Php2JsVars()->set('availableLanguages', $model->getAvailableLanguages());
        
        $collection=ZfExtended_Factory::get('editor_Models_TermCollection_TermCollection');
        /* @var $collection editor_Models_TermCollection_TermCollection */
        
        $collectionIds=$collection->getCollectionsIdsForCustomer($customers);

        if(empty($collectionIds)){
            $this->view->error=$this->translate->_("Es wurden keine verfügbaren termCollection für den Ihnen zugeordneten Kunden gefunden.");
            return;
        }
        
        //get all languages in the term collections
        $langsArray=$collection->getLanguagesInTermCollections($collectionIds);
        
        //get the user languages
        if(empty($langsArray) && !empty($defaultLangs)){
            //if no user languages are defined, get the default config languages
            $langsArray=$model->loadByRfc($defaultLangs);
        }
        
        if(empty($langsArray)){
            throw new ZfExtended_ValidateException("No user or default languages are configured.");
        }
        
        //get the translated labels
        $labelsModel=ZfExtended_Factory::get('editor_Models_TermCollection_TermAttributesLabel');
        /* @var $labelsModel editor_Models_TermCollection_TermAttributesLabel */
        $labels=$labelsModel->loadAllTranslated();
        
        $languagesModel=ZfExtended_Factory::get('editor_Models_Languages');
        /* @var $languagesModel editor_Models_Languages */
        
        $frontendLocaleNormalized=$this->normalizeLanguage($this->_session->locale);
        $preselectedLang=null;
        
        $rfcFlags=[];
        foreach ($langsArray as &$lng){

            //set preselected term-search language based on user locale language
            if(!$preselectedLang){
                $collectionLanguage=$this->normalizeLanguage($lng['rfc5646']);
                if($frontendLocaleNormalized[0] == $collectionLanguage[0]){
                    $preselectedLang=$lng['rfc5646'];
                }
            }
            
            $rfcFlags[strtolower($lng['rfc5646'])]=strtolower($lng['iso3166Part1alpha2']);
            
            $isSingleLang=strpos($lng['rfc5646'], '-')===false;
            
            //find all language sublings when the language is without "-" (de -> de-De, de-Au ..)
            if($isSingleLang){
                //load all similar languages
                $group=$languagesModel->findLanguageGroup($lng['rfc5646']);
                $lng['languageGroup']=!empty($group) ? array_column($group, 'id') : [];
                continue;
            }
            $lng['languageGroup']=[$lng['id']];
        }
        
        //all languages in the available term collections for the user
        $this->view->languages=$langsArray;
        
        // all language-combinations that are available in InstantTranslate
        $languageResourcesLanguages = ZfExtended_Factory::get('editor_Models_LanguageResources_Languages');
        /* @var $languageResourcesLanguages editor_Models_LanguageResources_Languages */
        $languageCombinations = $languageResourcesLanguages->getLanguageCombinationsForLoggedUser();
        $this->view->Php2JsVars()->set('instanttranslate.targetsForSources', $languageCombinations->targetsForSources);
        
        //rfc language code to language flag mapping
        $this->view->rfcFlags=$rfcFlags;
        $this->view->moduleFolder = APPLICATION_RUNDIR.'/modules/'.Zend_Registry::get('module').'/';
        
        $this->view->labels=$labels;
        $this->view->collectionIds=$collectionIds;
        
        $this->view->preselectedLang=$preselectedLang;
        
        $this->view->Php2JsVars()->set('termStatusMap', $config->runtimeOptions->tbx->termLabelMap->toArray());
        $this->view->Php2JsVars()->set('termStatusLabel', [
            'permitted' => $this->translate->_('erlaubte Benennung'),
            'forbidden' => $this->translate->_('verbotene Benennung'),
            'preferred' => $this->translate->_('Vorzugsbenennung'),
            'unknown' => $this->translate->_('Unbekannter Term Status'),
        ]);
        $this->view->Php2JsVars()->set('loginUrl', APPLICATION_RUNDIR.$config->runtimeOptions->loginUrl);
        $this->view->Php2JsVars()->set('restpath',APPLICATION_RUNDIR.'/'.Zend_Registry::get('module').'/');
        
        //translated strings for some of the result tables
        $translatedStrings=array(
                "termEntries"=>$this->translate->_("Term-Einträge"),
                "termEntryNameTitle"=>$this->translate->_("Eintragsbenennungen"),
                "termEntryAttributeTitle"=>$this->translate->_("Eintragseigenschaften"),
                "search" => $this->translate->_('Suche'),
                "searchFilterPlaceholderText" => $this->translate->_('Filter'),
                "noResults" => $this->translate->_('Keine Ergebnisse für die aktuelle Suche!'),
                "noExistingAttributes" => $this->translate->_('no existing attributes'),
                "collection"=>$this->translate->_("Term-Collection"),
                "client"=>$this->translate->_("Kunde"),
                "processstatus"=>$this->translate->_("Prozessstatus"),
                "instantTranslateInto"=>$this->translate->_("Sofortübersetzung nach"),
                "TermPortalLanguages"=>$this->translate->_("Terminologieportal-Sprachen"),
                "AllLanguagesAvailable"=>$this->translate->_("Alle verfügbaren Sprachen")
        );
        
        $this->view->translations=$translatedStrings;
        
        $term=ZfExtended_Factory::get('editor_Models_Term');
        /* @var $term editor_Models_Term */
        if($term->isProposableAllowed()){
            $this->view->Php2JsVars()->set('apps.termportal.proposal.translations',[
                "Ja"=>$this->translate->_("Ja"),
                "Nein"=>$this->translate->_("Nein"),
                "deleteAttributeProposalMessage"=>$this->translate->_("Möchten Sie den Attributs-Vorschlag wirklich entfernen?"),
                "deleteTermProposalMessage"=>$this->translate->_("Möchten Sie den Term-Vorschlag wirklich entfernen?"),
                "deleteAttributeProposal"=>$this->translate->_("Attributs-Vorschlag löschen"),
                "deleteProposal"=>$this->translate->_("Vorschlag löschen"),
                "addProposal"=>$this->translate->_("Änderung vorschlagen"),
                "editTermEntryProposal"=>$this->translate->_("Term-Eintrag: Änderung vorschlagen"),
                "addTermEntryProposal"=>$this->translate->_("Neuen Term-Eintrag vorschlagen"),
                "deleteTermEntryProposal"=>$this->translate->_("Term-Eintrag: Vorschlag löschen"),
                "editTermEntryAttributeProposal"=>$this->translate->_("Term-Eintrag-Attribut: Änderung vorschlagen"),
                "addTermEntryAttributeProposal"=>$this->translate->_("Neues Term-Eintrag-Attribut vorschlagen"),
                "deleteTermEntryAttributeProposal"=>$this->translate->_("Term-Eintrag-Attribut: Vorschlag löschen"),
                "editTermProposal"=>$this->translate->_("Term: Änderung vorschlagen"),
                "addTermProposal"=>$this->translate->_("Neuen Term vorschlagen"),
                "deleteTermProposal"=>$this->translate->_("Term: Vorschlag löschen"),
                "editTermAttributeProposal"=>$this->translate->_("Term-Attribut: Änderung vorschlagen"),
                "addTermAttributeProposal"=>$this->translate->_("Neues Term-Attribut vorschlagen"),
                "deleteTermAttributeProposal"=>$this->translate->_("Term-Attribut: Vorschlag löschen"),
                "chooseLanguageForTermEntry"=>$this->translate->_("Bitte Sprache für den neuen Term-Vorschlag wählen"),
                "chooseTermcollectionForTermEntry"=>$this->translate->_("Bitte Term-Collection für den neuen Term-Vorschlag wählen")
            ]);
        }
        
        // for filtering in front-end: get the names for the available collectionIds
        $customerAssoc=ZfExtended_Factory::get('editor_Models_LanguageResources_CustomerAssoc');
        /* @var $customerAssoc editor_Models_LanguageResources_CustomerAssoc */
        $collections = [];
        foreach ($collectionIds as $id) {
            $collection->load($id);
            $customerAssoc->loadByLanguageResourceId($id);
            $collections[$id] = (object) [
                                    'name' => $collection->getName(),
                                    'clients' => $customerAssoc->loadCustomerIds($id)
                                ];
        }
        $this->view->collections = $collections;
        
        // for filtering in front-end: get the names for the available clients
        $customer=ZfExtended_Factory::get('editor_Models_Customer');
        /* @var $customer editor_Models_Customer */
        $clients = [];
        foreach ($customers as $id) {
            if (count($customerAssoc->loadByCustomerIds($id))>0) {
                $customer->load($id);
                $clients[$id] = $customer->getName();
            }
        }
        $this->view->clients = $clients;
        
        // for filtering in front-end: get processtats
        $term=ZfExtended_Factory::get('editor_Models_Term');
        /* @var $term editor_Models_Term */
        $allProcessStatus = [];
        foreach ($term->getAllProcessStatus() as $processstatus) {
            $allProcessStatus[$processstatus] = $this->translate->_($processstatus);
        }
        $this->view->allProcessstatus = $allProcessStatus;
    }
    
    /**
     * normalisiert den übergebenen Sprachstring für die interne Verwendung.
     * => strtolower
     * => trennt die per - oder _ getrennten Bestandteile in ein Array auf
     * @param string $langString
     * @return array
     */
    private function normalizeLanguage($langString) {
        return explode('-',strtolower(str_replace('_','-',$langString)));
    }
}