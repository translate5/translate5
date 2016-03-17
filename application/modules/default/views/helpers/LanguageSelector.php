<?php
/*
START LICENSE AND COPYRIGHT

 This file is part of translate5
 
 Copyright (c) 2013 - 2015 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

 Contact:  http://www.MittagQI.com/  /  service (ATT) MittagQI.com

 This file may be used under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE version 3
 as published by the Free Software Foundation and appearing in the file agpl3-license.txt 
 included in the packaging of this file.  Please review the following information 
 to ensure the GNU AFFERO GENERAL PUBLIC LICENSE version 3.0 requirements will be met:
 http://www.gnu.org/licenses/agpl.html

 There is a plugin exception available for use with this release of translate5 for
 open source applications that are distributed under a license other than AGPL:
 Please see Open Source License Exception for Development of Plugins for translate5
 http://www.translate5.net/plugin-exception.txt or as plugin-exception.txt in the root
 folder of translate5.
  
 @copyright  Marc Mittag, MittagQI - Quality Informatics
 @author     MittagQI - Quality Informatics
 @license    GNU AFFERO GENERAL PUBLIC LICENSE version 3 with plugin-execptions
			 http://www.gnu.org/licenses/agpl.html http://www.translate5.net/plugin-exception.txt

END LICENSE AND COPYRIGHT
*/

/** #@+
 * @author Marc Mittag
 * @package portal
 * @version 2.0
 *
 */
/**
 * View Helper zur Generierung der Sprachumschaltung
 *
 * - die runtimeOptions.translation.sourceLocale wird nicht mit als Sprache für
 *   die Sprachumschaltung angeboten (sprich ausgefiltert). Sie sollte z. B. auf
 *   setze auf Hausa als eine Sprache gesetzt sein, die wohl nicht als
 *   Portalsprache vorkommen wird. So kann auch das deutsche mittels xliff-Datei
 *   überschrieben werden und die in die Quelldateien einprogrammierten Werte
 *   müssen nicht geändert werden
 */

class View_Helper_LanguageSelector extends Zend_View_Helper_Abstract {

    public $view;

    public function setView(Zend_View_Interface $view) {
        $this->view = $view;
    }

    public function languageSelector() {
        $session = new Zend_Session_Namespace();
        $translate = ZfExtended_Zendoverwrites_Translate::getInstance();
        
        $form = new Zend_Form(array(
                'method' => 'post',
                'id' => 'languageSelector',
                'name' => 'languageSelector'
        ));
        
        $form->setAction($this->view->getUrl(array('locale')));
        
        $form->addElement('select', 'locale');
        $localeElement = $form->getElement('locale');
        $localeElement->setMultiOptions($translate->getAvailableTranslations());
        $localeElement->setValue($session->locale);
        $localeElement->setAttrib('onchange', 'document.getElementById(\'languageSelector\').submit()');
        
        $layout = Zend_Layout::getMvcInstance();
        $layout->languageSelector = $form;
    }
}