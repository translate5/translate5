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

        $userModel=ZfExtended_Factory::get('ZfExtended_Models_User');
        /* @var $userModel ZfExtended_Models_User */
        $customers=$userModel->getUserCustomersFromSession();
        
        if(empty($customers)){
            $this->view->error=$this->translate->_("Ihnen sind derzeit keine Kundenverknüpfungen und damit auch keine TermCollections zugeordnet. Daher ist auch keine Termsuche möglich.");
            return;
        }
        
        $config = Zend_Registry::get('config');
        $defaultLangs=$config->runtimeOptions->termportal->defaultlanguages->toArray();
        
        $langsArray = array();
        
        $model=ZfExtended_Factory::get('editor_Models_Languages');
        /* @var $model editor_Models_Languages */
        
        Zend_Layout::getMvcInstance()->setLayoutPath(APPLICATION_PATH.'/modules/editor/layouts/scripts/termportal');
        
        $this->translate = ZfExtended_Zendoverwrites_Translate::getInstance();
        
        
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
        
        $frontendLocale= $this->_session->locale;
        
        $preselectedLang=null;
        
        $rfcFlags=[];
        foreach ($langsArray as &$lng){
            $rfcFlags[strtolower($lng['rfc5646'])]=strtolower($lng['iso3166Part1alpha2']);
            
            $isSingleLang=strpos($lng['rfc5646'], '-')===false;
            
            //if the frontend locale and the current language are the same, use it as preselected
            if(!$preselectedLang && $frontendLocale==$lng['rfc5646']){
                $preselectedLang=$frontendLocale;
            }
            
            //find all language sublings when the language is without "-" (de -> de-De, de-Au ..)
            if($isSingleLang){
                //load all similar languages
                $group=$languagesModel->findLanguageGroup($lng['rfc5646']);
                
                //check if the current locale lang exist in the group
                if(!$preselectedLang && !empty($group)){
                    
                    foreach ($group as $groupSingle){
                        if(!$preselectedLang && $frontendLocale==$groupSingle['rfc5646']){
                            $preselectedLang=$lng['rfc5646'];
                            break;
                        }
                    }
                }
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
        
        
        $this->view->restPath=APPLICATION_RUNDIR.'/'.Zend_Registry::get('module').'/';
        
        
        //translated strings for some of the result tables
        $translatedStrings=array(
                "termTableTitle"=>$this->translate->_("Terme"),
                "termEntryAttributeTableTitle"=>$this->translate->_("Eigenschaften des Eintrags"),
                "search" => $this->translate->_('Suche'),
                "noResults" => $this->translate->_('Keine Ergebnisse für die aktuelle Suche!'),
                "noExistingAttributes" => $this->translate->_('no existing attributes')
        );
        
        $this->view->translations=$translatedStrings;
    }
}