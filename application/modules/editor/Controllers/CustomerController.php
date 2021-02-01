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
        parent::indexAction();
        $this->cleanUpOpenIdForDefault();
    }
    
    public function postAction() {
        //check if the user is allowed to do customer administration
        if(!$this->isAllowed("backend","customerAdministration")){
            throw new ZfExtended_NoAccessException();
        }
        try {
            return parent::postAction();
        }
        catch(ZfExtended_Models_Entity_Exceptions_IntegrityDuplicateKey $e) {
            $this->handleDuplicate($e);
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
        catch(ZfExtended_Models_Entity_Exceptions_IntegrityDuplicateKey $e) {
            $this->handleDuplicate($e);
        }
    }
    
    public function deleteAction() {
        try {
            parent::deleteAction();
        }
        catch(ZfExtended_Models_Entity_Exceptions_IntegrityConstraint $e) {
            ZfExtended_Models_Entity_Conflict::addCodes([
                'E1047' => 'A client cannot be deleted as long as tasks are assigned to this client.'
            ], 'editor.customer');
            throw new ZfExtended_Models_Entity_Conflict('E1047');
        }
    }
    
    /***
     * Export language resources usage as excel document
     */
    public function exportresourceAction(){
        $customerId = $this->getRequest()->getParam('customerId',null);
        $export = ZfExtended_Factory::get('editor_Models_LanguageResources_UsageExporter');
        /* @var $export editor_Models_LanguageResources_UsageExporter */
        $export->excel($customerId);
    }
    
    protected function decodePutData(){
        parent::decodePutData();
        $this->handleDomainField();
    }
    
    /***
     * Handle the domain field from the post/put request data.
     */
    protected function handleDomainField(){
        if(!isset($this->data->domain)){
            return;
        }
        //because it is uniqe key, do not allow empty value
        if(empty($this->data->domain)){
            $this->data->domain=null;
            return;
        }
        //add always / at the end of the url
        if(substr($this->data->domain,-1)!=='/'){
            $this->data->domain.='/';
        }
        
        //remove always the protocol if it is provided by the api or frontend
        $disallowed = array('http://', 'https://');
        foreach($disallowed as $d) {
            if(strpos($this->data->domain, $d) === 0) {
                $this->data->domain=str_replace($d, '', $this->data->domain);
            }
        }
    }
    
    /***
     * Remove the openid data for the default customer if it is configured so
     */
    protected function cleanUpOpenIdForDefault(){
        $config = Zend_Registry::get('config');
        $showOpenIdForDefault=(boolean)$config->runtimeOptions->customers->openid->showOpenIdDefaultCustomerData;
        if($showOpenIdForDefault){
            return;
        }
        
        foreach ($this->view->rows as &$row){
            if($row['number']!=editor_Models_Customer::DEFAULTCUSTOMER_NUMBER){
                continue;
            }
            $row['domain']=null;
            $row['openIdServer']=null;
            $row['openIdIssuer']=null;
            $row['openIdAuth2Url']=null;
            $row['openIdServerRoles']=null;
            $row['openIdDefaultServerRoles']=null;
            $row['openIdClientId']=null;
            $row['openIdClientSecret']=null;
            $row['openIdRedirectLabel']=null;
            $row['openIdRedirectCheckbox']=null;
        }
    }
    
    /**
     * Protect the default customer from being edited or deleted.
     */
    protected function entityLoad() {
        $this->entity->load($this->_getParam('id'));
        $isModification = $this->_request->isPut() || $this->_request->isDelete();
        if($isModification && $this->entity->isDefaultCustomer()) {
            throw new ZfExtended_Models_Entity_NoAccessException('The default client must not be edited or deleted.');
        }
    }
    
    /**
     * Internal handler for duplicated entity message
     * @param Zend_Db_Statement_Exception $e
     * @throws Zend_Db_Statement_Exception
     */
    protected function handleDuplicate(ZfExtended_Models_Entity_Exceptions_IntegrityDuplicateKey $e) {
        if($e->isInMessage('domain_UNIQUE')){
            ZfExtended_UnprocessableEntity::addCodes([
                'E1104' => 'This domain is already in use.'
            ], 'editor.customer');
            throw ZfExtended_UnprocessableEntity::createResponse('E1104', [
                'domain' => ['duplicateDomain' => 'Diese Domain wird bereits verwendet.']
            ]);
        }
        
        ZfExtended_UnprocessableEntity::addCodes([
            'E1063' => 'The given client-number is already in use.'
        ], 'editor.customer');
        throw ZfExtended_UnprocessableEntity::createResponse('E1063', [
            'number' => ['duplicateClientNumber' => 'Diese Kundennummer wird bereits verwendet.']
        ]);
    }
}