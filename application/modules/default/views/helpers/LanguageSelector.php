<?php
 /*
 START LICENSE AND COPYRIGHT
 
 This file is part of Translate5 Editor PHP Serverside and build on Zend Framework
 
 Copyright (c) 2013 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

 Contact:  http://www.MittagQI.com/  /  service (ÄTT) MittagQI.com

 This file may be used under the terms of the GNU General Public License version 3.0
 as published by the Free Software Foundation and appearing in the file gpl3-license.txt 
 included in the packaging of this file.  Please review the following information 
 to ensure the GNU General Public License version 3.0 requirements will be met:
 http://www.gnu.org/copyleft/gpl.html.

 For this file you are allowed to make use of the same FLOSS exceptions to the GNU 
 General Public License version 3.0 as specified by Sencha for Ext Js. 
 Please be aware, that Marc Mittag / MittagQI take no warranty  for any legal issue, 
 that may arise, if you use these FLOSS exceptions and recommend  to stick to GPL 3. 
 For further information regarding this topic please see the attached license.txt
 of this software package.
 
 MittagQI would be open to release translate5 under EPL or LGPL also, if this could be
 brought in accordance with the ExtJs license scheme. You are welcome to support us
 with legal support, if you are interested in this.
 
 
 @copyright  Marc Mittag, MittagQI - Quality Informatics
 @author     MittagQI - Quality Informatics
 @license    GNU General Public License version 3.0 http://www.gnu.org/copyleft/gpl.html
             with FLOSS exceptions (see floss-exception.txt and ux-exception.txt at the root level)
 
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
        
        $form = new Zend_Form([
                'method' => 'post',
                'id' => 'languageSelector',
                'name' => 'languageSelector'
        ]);
        
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