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

/**
 *
 */
class Editor_CustomerController extends ZfExtended_RestController {
    
    protected $entityClass = 'editor_Models_Customer';
    
    /**
     * @var editor_Models_Customer
     */
    protected $entity;
    
    public function indexAction(){
        //check if the user is allowed to do customer administration
        if(!$this->isAllowed("backend","customerAdministration")){
            throw new ZfExtended_NoAccessException();
        }
        
        return parent::indexAction();
    }
    
    public function postAction() {
        //check if the user is allowed to do customer administration
        if(!$this->isAllowed("backend","customerAdministration")){
            throw new ZfExtended_NoAccessException();
        }
        try {
            return parent::postAction();
        }
        catch(Zend_Db_Statement_Exception $e) {
            $this->handleDuplicateNumber($e);
        }
    }
    
    public function putAction() {
        //check if the user is allowed to do customer administration
        if(!$this->isAllowed("backend","customerAdministration")){
            throw new ZfExtended_NoAccessException();
        }
        try {
            return parent::putAction();
        }
        catch(Zend_Db_Statement_Exception $e) {
            $this->handleDuplicateNumber($e);
        }
    }
    
    public function deleteAction() {
        try {
            parent::deleteAction();
        }
        catch(Zend_Db_Statement_Exception $e) {
            $m = $e->getMessage();
            if(stripos($m, 'Integrity constraint violation') !== false) {
                throw new ZfExtended_Models_Entity_NotAcceptableException('A client cannot be deleted as long as tasks are assigned to this client.', 0, $e);
            }
            throw $e;
        }
    }
    
    /**
     * Protect the default customer from being edited or deleted.
     */
    protected function entityLoad() {
        $this->entity->load($this->_getParam('id'));
        if(($this->_request->isPut() || $this->_request->isDelete())
                && $this->entity->isDefaultCustomer()) {
                    throw new ZfExtended_Models_Entity_NotAcceptableException('The default client must not be edited or deleted.', 0);
                }
    }
    
    /**
     * Internal handler for duplicated entity message
     * @param Zend_Db_Statement_Exception $e
     * @throws Zend_Db_Statement_Exception
     */
    protected function handleDuplicateNumber(Zend_Db_Statement_Exception $e) {
        $msg = $e->getMessage();
        if(stripos($msg, 'Duplicate entry') === false || stripos($msg, "for key ") === false) {
            throw $e; //otherwise throw this again
        }
    
        $t = ZfExtended_Zendoverwrites_Translate::getInstance();
        /* @var $t ZfExtended_Zendoverwrites_Translate */;;
    
        $errors = array('number' => $t->_('Diese Kundennummer wird bereits verwendet.'));
        $e = new ZfExtended_ValidateException();
        $e->setErrors($errors);
        $this->handleValidateException($e);
    }
}