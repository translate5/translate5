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
        
        // available MachineTranslation-Engines
        $machineTranslationEngines = array(  // TODO; both content and structure of this content are DUMMY only!
                'mt1' => array('name' => 'MT Engine 1', 'source' => 'de-DE', 'target' => 'en-US'),
                'mt2' => array('name' => 'MT Engine 2', 'source' => 'de-DE', 'target' => 'en-GB'),
                'mt3' => array('name' => 'MT Engine 3', 'source' => 'fr-FR', 'target' => 'en-GB'),
                'mt4' => array('name' => 'MT Engine 4', 'source' => 'de-DE', 'target' => 'en-GB')
        );
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
        $this->view->translations = $translatedStrings;
    }
}