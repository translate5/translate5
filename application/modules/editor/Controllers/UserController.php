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
class Editor_UserController extends ZfExtended_RestController {

    protected $entityClass = 'ZfExtended_Models_User';

    /**
     * @var ZfExtended_Models_User
     */
    protected $entity;
    
    /**
     * flag to preserve twice put data encoding
     * @var boolean
     */
    protected $alreadyDecoded = false;
    
    /**
     * (non-PHPdoc)
     * @see ZfExtended_RestController::indexAction()
     * 
     * FIXME Sicherstellen, dass für nicht PMs diese Methode nur die User liefert, die gemeinsam mit dem aktuellen User an Tasks arbeiten.
     * FIXME Generell werden nur User mit der Rolle "editor" angezeigt, alle anderen haben eh keinen Zugriff auf T5
     */
    public function indexAction () {
        return parent::indexAction();
    }
    
    /**
     * (non-PHPdoc)
     * @see ZfExtended_RestController::putAction()
     */
    public function putAction() {
        try {
            parent::putAction();
            $this->handlePasswdMail();
            $this->credentialCleanup();
        }
        catch(Zend_Db_Statement_Exception $e) {
            $this->handleLoginDuplicates($e);
        }
    }
    
    /**
     * (non-PHPdoc)
     * @see ZfExtended_RestController::postAction()
     */
    public function postAction() {
        try {
            parent::postAction();
            $this->handlePasswdMail();
            $this->credentialCleanup();
        }
        catch(Zend_Db_Statement_Exception $e) {
            $this->handleLoginDuplicates($e);
        }
    }
    
    /**
     * (non-PHPdoc)
     * @see ZfExtended_RestController::getAction()
     */
    public function getAction() {
        parent::getAction();
        if($this->entity->getLogin() == 'system') {
            throw new ZfExtended_Models_Entity_NotFoundException("System Benutzer wurde versucht zu erreichen",0,null,true);
        }
        $this->credentialCleanup();
    }
    
    /**
     * (non-PHPdoc)
     * @see ZfExtended_RestController::deleteAction()
     */
    public function deleteAction() {
        $this->entity->load($this->_getParam('id'));
        $this->checkIsEditable();
        
        /**
         * @todo this should be done by an event:
         */
        $assoc = ZfExtended_Factory::get('editor_Models_TaskUserAssoc');
        /* @var $assoc editor_Models_TaskUserAssoc */
        $users = $assoc->deleteByUserguid($this->entity->getUserGuid());
        
        $this->entity->delete();
    }
    
    /**
     * encapsulate a separate REST sub request for authenticated users only.
     * A authenticated user is allowed to get and change (PUT) himself, nothing more, nothing less.
     * @throws ZfExtended_BadMethodCallException
     */
    public function authenticatedAction() {
        $userSession = new Zend_Session_Namespace('user');
        $id = $userSession->data->id;
        $this->_setParam('id', $id);
        if($this->_request->isPut()){
            $this->entity->load($id);
            $this->filterDataForAuthenticated();
            return $this->putAction();
        }
        if($this->_request->isGet()){
            return $this->getAction();
        }
        throw new ZfExtended_BadMethodCallException();
    }
    
    /**
     * decodes the put data and filters them to values the logged in user is allowed to change on himself
     */
    protected function filterDataForAuthenticated() {
        $allowed = array('passwd');
        $this->decodePutData();
        $data = get_object_vars($this->data);
        $keys = array_keys($data);
        $this->data = new stdClass();
        foreach($allowed as $allow) {
            if(in_array($allow, $keys)){
                $this->data->$allow = $data[$allow];
            }
        }
    }
    
    /**
     * remove password hashes from output
     */
    protected function credentialCleanup() {
        if(is_object($this->view->rows) && property_exists($this->view->rows, 'passwd')) {
            unset($this->view->rows->passwd);
        }
        if(is_array($this->view->rows) && isset($this->view->rows['passwd'])) {
            unset($this->view->rows['passwd']);
        }
    }
    
    /**
     * overridden to prepare data on user creation
     * (non-PHPdoc)
     * @see ZfExtended_RestController::decodePutData()
     */
    protected function decodePutData() {
        if($this->alreadyDecoded) {
            return;
        }
        $this->alreadyDecoded = true;
        $this->_request->isPost() || $this->checkIsEditable(); //checkEditable only if not POST
        parent::decodePutData();
        if($this->_request->isPost()) {
            unset($this->data->id);
            $this->data->userGuid = $this->_helper->guid->create(true);
        }
    }

    /**
     * overridden to save the user password not unencrypted and to reset passwd if requested
     * (non-PHPdoc)
     * @see ZfExtended_RestController::setDataInEntity()
     */
    protected function setDataInEntity(array $fields = null, $mode = self::SET_DATA_BLACKLIST){
        parent::setDataInEntity($fields, $mode);
        if(isset($this->data->passwd)) {
            if($this->data->passwd===''||  is_null($this->data->passwd)) {//convention for passwd being reset; 
                $this->data->passwd = null;
            }
            $this->entity->setNewPasswd($this->data->passwd,false);
        }
    }
    
    
    /**
     * handles the exception if its an duplication of the login field
     * @param Zend_Db_Statement_Exception $e
     * @throws Zend_Db_Statement_Exception
     */
    protected function handleLoginDuplicates(Zend_Db_Statement_Exception $e) {
        $msg = $e->getMessage();
        if(stripos($msg, 'duplicate entry') === false || stripos($msg, "for key 'login'") === false) {
            throw $e; //otherwise throw this again
        }
        
        $t = Zend_Registry::get('Zend_Translate');
        /* @var $t Zend_Translate */
        
        $errors = array('login' => $t->_('Dieser Anmeldename wird bereits verwendet.'));
        $e = new ZfExtended_ValidateException();
        $e->setErrors($errors);
        $this->handleValidateException($e);
    }

    /**
     * send a mail to user, if passwd has been reseted or account has been new created
     */
    protected function handlePasswdMail() {
        //convention for passwd being reset: 
        if(property_exists($this->data, 'passwd') && is_null($this->data->passwd)) {
            $general = ZfExtended_Zendoverwrites_Controller_Action_HelperBroker::getStaticHelper(
                'general'
            );
            $translate = Zend_Registry::get('Zend_Translate');
            $general->mail(
                    $this->entity->getEmail(),
                    '',
                    $translate->_('Passwort setzen'),
                    array(
                        'userEntity' =>$this->entity
                    )
            );
        }
    }
    
    /**
     * checks if the loaded entity is editable, if not throw an exception
     * we decided to use a normal exception here, not a NotAllowedExeception 
     * since editing a not editable user should not happen from frontend
     * @throws Zend_Exception
     */
    protected function checkIsEditable(){
        if(! $this->entity->getEditable()){
            throw new Zend_Exception('Tryied to manipulate a not editable user'); 
        }
    }
}