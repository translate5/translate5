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
        
        $langsArray = array();
        
        $model=ZfExtended_Factory::get('editor_Models_Languages');
        /* @var $model editor_Models_Languages */
        
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
                "editTermEntry"=>$this->translate->_("Term-Eintrag bearbeiten"),
                "addTermEntry"=>$this->translate->_("Term-Eintrag hinzufügen"),
                "deleteTermEntry"=>$this->translate->_("Term-Eintrag löschen"),
                "editTermEntryAttribute"=>$this->translate->_("Term-Eintrag-Attribut bearbeiten"),
                "addTermEntryAttribute"=>$this->translate->_("Term-Eintrag-Attribut hinzufügen"),
                "deleteTermEntryAttribute"=>$this->translate->_("Term-Eintrag-Attribut löschen"),
                "editTerm"=>$this->translate->_("Term bearbeiten"),
                "addTerm"=>$this->translate->_("Term hinzufügen"),
                "deleteTerm"=>$this->translate->_("Term löschen"),
                "editTermAttribute"=>$this->translate->_("Term-Attribut bearbeiten"),
                "addTermAttribute"=>$this->translate->_("Term-Attribut hinzufügen"),
                "deleteTermAttribute"=>$this->translate->_("Term-Attribut löschen")
        );
        
        $this->view->translations=$translatedStrings;
        
        $term=ZfExtended_Factory::get('editor_Models_Term');
        /* @var $term editor_Models_Term */
        if($term->isProposableAllowed()){
            $this->view->Php2JsVars()->set('apps.termportal.proposal.translations',[
                "Ja"=>$this->translate->_("Ja"),
                "Nein"=>$this->translate->_("Nein"),
                "deleteAttributeProposalMessage"=>$this->translate->_("Möchten Sie das Attribut wirklich entfernen?"),
                "deleteTermProposalMessage"=>$this->translate->_("Möchten Sie der Term wirklich entfernen?"),
                "deleteAttributeProposalTitle"=>$this->translate->_("Attribut entfernen"),
                "deleteTermProposalTitle"=>$this->translate->_("Term entfernen")
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