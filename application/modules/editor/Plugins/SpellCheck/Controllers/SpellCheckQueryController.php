<?php
/*
START LICENSE AND COPYRIGHT

 This file is part of translate5
 
 Copyright (c) 2013 - 2021 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

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

use MittagQI\Translate5\Plugins\SpellCheck\LanguageTool\Service;
/**
 * Controller for the Plugin SpellCheck
 */
class editor_Plugins_SpellCheck_SpellCheckQueryController extends ZfExtended_RestController {
    
    /**
     * (non-PHPdoc)
     * @see ZfExtended_RestController::init()
     *
     * copied the init method, parent can not be used, since no real entity is used here
     */
    public function init() {
        $this->initRestControllerSpecific();
    }
    
    /**
     * (non-PHPdoc)
     * @see ZfExtended_RestController::indexAction()
     */
    public function indexAction(){
        throw new ZfExtended_BadMethodCallException(__CLASS__);
    }
    
    /**
     * Get the languages that are supported by the tool we use (currently: LanguageTool).
     */
    public function languagesAction(){
        if($this->getParam('targetLangCode') && $this->getParam('targetLangCode')!=""){
            $targetLangCode = $this->getParam('targetLangCode');
        }
        if (!$targetLangCode){
            $this->view->rows = false;
            return;
        }
        /* @var Service $service */
        $service = editor_Plugins_SpellCheck_Init::createService('languagetool');
        $this->view->rows = $service->getAdapter()->getSupportedLanguage($targetLangCode);
    }

    /**
     * The matches that our tool finds (currently: LanguageTool).
     */
    public function matchesAction(){
        $text = $this->getParam('text','');
        $language= $this->getParam('language','');
        if (empty($text) || empty($language) ) {
            error_log("NO text or language.");
            $this->view->rows = "[]";
            return;
        }
        /* @var Service $service */
        $service = editor_Plugins_SpellCheck_Init::createService('languagetool');
        $this->view->rows = $service->getAdapter()->getMatches($text, $language);
    }
    
    public function getAction() {
        throw new ZfExtended_BadMethodCallException(__CLASS__.'->get');
    }
    
    public function putAction() {
        throw new ZfExtended_BadMethodCallException(__CLASS__.'->put');
    }
    
    public function deleteAction() {
        throw new ZfExtended_BadMethodCallException(__CLASS__.'->delete');
    }
    
    public function postAction() {
        throw new ZfExtended_BadMethodCallException(__CLASS__.'->post');
    }
}
