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