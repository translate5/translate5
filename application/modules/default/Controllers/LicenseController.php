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

/**#@+
 * @author Marc Mittag
 * @package translate5
 * @version 1.0
 *
 */
/**
 * Klasse der Nutzermethoden
 *
 *
 */
class LicenseController extends ZfExtended_Controllers_Action {
    public function init(){
        parent::init();
    }
    
    protected function getForm() {
        $form = new Zend_Form;

        $form->setAction('/license/accept')->setMethod('post');
        $form->setAttrib('id', 'acceptLicense');
        $accept = new Zend_Form_Element_Checkbox('accept');
        $accept->setRequired(true);
        $accept->setDescription('Yes, I accept the above AGPLv3 license for translate5.');
        $validator = new Zend_Validate_Between(array('min'=>1,'max'=>1,'inclusive'=>true));
        $validator->setMessage('Please accept the license');
        $accept->addValidator($validator);
        $form->addElement($accept);
        $form->addElement('submit', 'download', array('label' => 'Download'));
        return $form;
    }
    
    public function acceptAction() {
        $form = $this->getForm();
        $this->view->form = $form;
        if (!$this->getRequest()->isPost()) {
            return;
        }
        ob_start();
        var_dump($form->isValid($_POST));
        error_log(ob_get_clean());
        if (!$form->isValid($_POST)) {
            return;
        }
        ob_clean();
        $file_url = 'http://www.translate5.net/downloads/translate5.zip';
        header('Content-Type: application/octet-stream');
        header("Content-Transfer-Encoding: Binary");
        header("Content-disposition: attachment; filename=\"" . basename($file_url) . "\"");
        readfile($file_url);
        exit;
    }
}