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
 * Allowed Actions:
 *
 * - indexAction();
 * - postAction();
 * - putAction();
 *
 * - sumAction();
 * - excelAction();
 *
 */
class erp_OrderController extends ZfExtended_RestController {
    use erp_Controllers_DataInjectorTrait;
    
    protected $entityClass = 'erp_Models_Order';
    
    /**
     * @var erp_Models_Order
     */
    protected $entity;
    
    /**
     * @var array
     */
    protected $additionalErrors = array();

    /**
     * @var string
     */
    protected $filterClass = 'ZfExtended_Models_Filter_ExtJs6';

    
    public function init() {
        //add filter type for languages
        $this->_filterTypeMap = [
            'targetLang' => [
                'list' => 'listCommaSeparated'
            ],
        ];
        parent::init();
    }
    
    public function indexAction(){
        $this->handleViewType();
        $this->view->rows = $this->entity->loadAll();;
        $this->view->total = $this->entity->getTotalCount();;
    }
    
    
    public function sumAction () {
        $this->handleViewType();
        $this->view->rows = $this->entity->calcFilteredSum();
    }
    
    /**
     * (non-PHPdoc)
     * @see ZfExtended_RestController::postAction()
     */
    public function postAction() {
        parent::postAction();
        if($this->wasValid){
            $this->calculateDebitNumber();
            $this->view->rows = $this->entity->getDataObject();
        }
    }
    
    /**
     * (non-PHPdoc)
     * @see ZfExtended_RestController::putAction()
     */
    public function putAction() {
        $this->entity->load((int) $this->_getParam('id'));
        $history = $this->entity->getNewHistoryEntity();
        $this->decodePutData();
        $this->processClientReferenceVersion();
        $this->setDataInEntity();
        if($this->validate()){
            $history->save();
            $this->entity->save();
            $this->calculateDebitNumber();
            $this->view->rows = $this->entity->getDataObject();
        }
    }
    
    public function excelAction() {
        //init the correct entity for the view type
        $this->handleViewType();
        
        $rows = $this->entity->loadAll();
        
        $excel = ZfExtended_Factory::get('ZfExtended_Models_Entity_ExcelExport');
        /* @var $excel ZfExtended_Models_Entity_ExcelExport */
        
        // set property for export-filename
        $excel->setProperty('filename', 'ERP-Export-Order');
        
        // set hidden fields
        $excel->setHiddenField('entityVersion');
        $excel->setHiddenField('customerId');
        $excel->setHiddenField('pmId');
        $excel->setHiddenField('checkerId');
        $excel->setHiddenField('editorId');
        
        // sample label-translations
        $excel->setLabel('id', 'Nummer');
        $excel->setLabel('name', 'Name');
        $excel->setLabel('poCount', 'POs');
        // sample callback-function for manipulating field-values
        $callbackId = function($id) {
            return '#_'.str_pad($id, 10, '0', STR_PAD_LEFT);
        };
        $excel->setCallback('id', $callbackId);
        
        $callbackComments = function($comment) {
            $regex = '/<div[\s]+class="comment">[\s]+<span[\s]+class="content">([^<]+)<\/span>[\s]+<span[\s]+class="author">([^<]+)<\/span>[\s]+<span[\s]+class="modified">([^<]+)<\/span>[\s]+<\/div>[\s]*/m';
            return preg_replace($regex, "$1\n$2 ($3) \n\n", $comment);
        };
        
        $excel->setCallback('comments', $callbackComments);
        
        $callbackConversionMonth = function($conversionMonth){
            if($conversionMonth==null){
                return;
            }
            return $this->monthNames[$conversionMonth];
        };
        
        $excel->setCallback('conversionMonth', $callbackConversionMonth);
        
        /* @var $langModel erp_Models_Languages */
        $langModel = ZfExtended_Factory::get('erp_Models_Languages');
        $languages=$langModel->getAvailableLanguages();
        
        if(!empty($languages)){
            $excel->setCallback('sourceLang',function($sourceLang) use ($languages){
                if(empty($sourceLang)){
                    return '';
                }
                foreach ($languages as $sl){
                    if($sl['value']==$sourceLang){
                        return $sl['text'];
                    }
                }
            });
        }
        if(!empty($languages)){
            $excel->setCallback('targetLang',function($targetLang) use ($languages){
                if(empty($targetLang)){
                    return '';
                }
                $targetLang=explode(',', $targetLang);
                $retval="";
                foreach ($languages as $sl){
                    if(in_array($sl['value'],$targetLang)){
                        $retval.=','. $sl['text'];
                    }
                }
                return $retval!=''?substr($retval,1):'';
            });
        }
        // field-type settings
        // date fields
        $excel->setFieldTypeDate('offerDate');
        $excel->setFieldTypeDate('billDate');
        $excel->setFieldTypeDate('paidDate');
        $excel->setFieldTypeDate('releaseDate');
        $excel->setFieldTypeDate('modifiedDate');
        $excel->setFieldTypeDate('plannedDeliveryDate');
        $excel->setFieldTypeDate('performanceDate');
        
        // currency fields
        $excel->setFieldTypeCurrency('offerNetValue');
        $excel->setFieldTypeCurrency('offerTaxValue');
        $excel->setFieldTypeCurrency('offerGrossValue');
        $excel->setFieldTypeCurrency('billNetValue');
        $excel->setFieldTypeCurrency('billTaxValue');
        $excel->setFieldTypeCurrency('billGrossValue');
        
        // percent fields
        $excel->setFieldTypePercent('taxPercent');
        $excel->setFieldTypePercent('offerMargin');
        $excel->setFieldTypePercent('billMargin');
        
        if(!empty($rows)){
            foreach ($rows as &$row){
                if(!empty($row['poInfo'])){
                    unset($row['poInfo']);
                }
            }
        }
        $excel->simpleArrayToExcel($rows);
    }
    
    public function deleteAction() {
        throw new ZfExtended_BadMethodCallException(__CLASS__.' -> '.__FUNCTION__);
    }
    
    protected function decodePutData() {
        parent::decodePutData();
        
        // inject customer data
        // must be at first place because here the order->taxPercent is set (taxPercent depends on customer)
        // order->taxPercent is needed in later money-concerning injections
        $this->injectCustomerData();
        
        // inject data concerning Zf_users
        $this->injectPmData();
        $this->injectCheckerData();
        $this->injectEditorData();
        
        // inject data concerning money
        $this->injectValueData('bill', 'taxPercent');
        $this->injectValueData('offer', 'taxPercent');
    }
    
    /***
     * Check and init the entity type based on the customer view type.
     * This should be used only for actions where different customert type can be used
     */
    protected function handleViewType(){
        $view=$this->getRequest()->getParam('customerview');
        $manager=ZfExtended_Factory::get('erp_CustomView_Manager');
        /* @var $manager erp_CustomView_Manager */
        $view=$manager->checkUserView($view);
        
        $newdb=ZfExtended_Factory::get(get_class($this->entity->db),[
            [],
            $view->getTablename()
        ]);
        $this->entity->db=$newdb;
        $this->entity->init();
    }
    
    /**
     * Injects the customerData to the given customerId
     */
    protected function injectCustomerData() {
        unset($this->data->keyAccount);
        unset($this->data->customerNumber);
        unset($this->data->customerName);
        
        if(empty($this->data->customerId)) {
            return;
        }
        
        $customer = ZfExtended_Factory::get('erp_Models_Customer');
        /* @var $customer erp_Models_Customer */
        
        try {
            $customer->load($this->data->customerId);
            $this->data->keyAccount = $customer->getKeyaccount();
            if ($this->data->keyAccount) {
                $keyAccount = ZfExtended_Factory::get('erp_Models_Keyaccount');
                /* @var $keyAccount erp_Models_Keyaccount */
                $keyAccount->load($this->data->keyAccount);
                $this->data->keyAccount = $keyAccount->getName();
            }
            $this->data->customerName = $customer->getName();
            $this->data->customerNumber = $customer->getNumber();
            
            //if the tasx is not set, load it from the document
            if(!isset($this->data->taxPercent)){
                $order=ZfExtended_Factory::get('erp_Models_Order');
                /* @var $order erp_Models_Order */
                $order->load($this->data->id);
                $taxPercent=(float) $order->getTaxPercent();
                $this->data->taxPercent = $taxPercent;
            }
            
        }
        catch(ZfExtended_Models_Entity_NotFoundException $exception)
        {
            $this->log->logError(__CLASS__.'->'.__FUNCTION__.'; Customer can not be loaded. '.$exception->getErrors());
            $this->additionalErrors['customerId'] = 'Der Kunde konnte nicht gefunden werden.';
        }
    }
    
    protected function calculateDebitNumber() {
        if (empty($this->entity->getBillDate()) || !empty($this->entity->getDebitNumber())) {
            return;
        }
        
        try {
            $debitNumber=$this->entity->generateDebitNumber($this->entity->getBillDate());
            $this->entity->setDebitNumber($debitNumber);
            $this->entity->save();
        }
        catch(Zend_Db_Statement_Exception $e) {
            $msg = $e->getMessage();
            if(stripos($msg, 'duplicate entry') === false) {
                throw $e; //otherwise throw this again
            }
            // duplicate debitNumber => calculate debitNumber again
            $this->entity->setDebitNumber(0);
            $this->calculateDebitNumber();
        }
        
    }
}