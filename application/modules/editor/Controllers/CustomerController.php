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

/**
 *
 */
class Editor_CustomerController extends ZfExtended_RestController {
    
    protected $entityClass = 'editor_Models_Customer';
    
    /**
     * @var editor_Models_Customer
     */
    protected $entity;
    
    public function init() {
        parent::init();
        //add context of valid export formats:
        //resourceLogExport
        $this->_helper->getHelper('contextSwitch')->addContext('resourceLogExport', [
            'headers' => [
                'Content-Type'=> 'application/zip',
            ]
        ])->addActionContext('exportresource', 'resourceLogExport')->initContext();
    }
    
    public function indexAction(){
        parent::indexAction();
        $this->cleanUpOpenIdForDefault();
    }
    
    public function postAction() {
        try {
            return parent::postAction();
        }
        catch(ZfExtended_Models_Entity_Exceptions_IntegrityDuplicateKey $e) {
            $this->handleDuplicate($e);
        }
    }
    
    public function putAction() {
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

        $context = $this->_helper->getHelper('contextSwitch')->getCurrentContext();
        //if json is requested, return only the data
        if($context == 'json'){
            //INFO: this is currently only available for api testing
            $this->setupTextExportResourcesLogData($customerId);
            return;
        }
        $export = ZfExtended_Factory::get('editor_Models_LanguageResources_UsageExporter');
        /* @var $export editor_Models_LanguageResources_UsageExporter */
        $taskType = $this->getRequest()->getParam('taskType',null);
        if(!empty($taskType)){
            $taskType = explode(',', $taskType);
            $export->setDocumentTaskType($taskType);
        }
        if($export->excel($customerId)){
            $t = ZfExtended_Zendoverwrites_Translate::getInstance();
            $this->view->result = $t->_("Es wurden keine Ergebnisse gefunden");
        }
    }

    /***
     * @param bool|null $associative When TRUE, returned objects will be converted into associative arrays.
     * @return void
     */
    protected function decodePutData(?bool $associative = false)
    {
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
    
    /***
     * Set the resources log data for the current export request. If the request is from non test user, this will throw an exception.
     * @param int $customerId
     */
    protected function setupTextExportResourcesLogData(int $customerId = null) {
        $user = new Zend_Session_Namespace('user');
        $allowed = ['testmanager','testapiuser'];
        if(!in_array($user->data->login, $allowed)){
            throw new ZfExtended_Models_Entity_NoAccessException('The current user is not alowed to use the resources log export data.');
        }
        $export = ZfExtended_Factory::get('editor_Models_LanguageResources_UsageExporter');
        /* @var $export editor_Models_LanguageResources_UsageExporter */
        $taskType = $this->getRequest()->getParam('taskType',null);
        if(!empty($taskType)){
            $taskType = explode(',', $taskType);
            $export->setDocumentTaskType($taskType);
        }
        $this->view->rows = $export->getExportRawDataTests($customerId);
    }
}