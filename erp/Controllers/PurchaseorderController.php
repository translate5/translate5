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

class erp_PurchaseorderController extends ZfExtended_RestController {
    use erp_Controllers_DataInjectorTrait;
    
    protected $entityClass = 'erp_Models_PurchaseOrder';
    
    /**
     * @var erp_Models_PurchaseOrder
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

    protected function decodePutData() {
        parent::decodePutData();
        $this->injectCustomerData();
        
        // inject data concerning Zf_users
        $this->injectPmData();
        $this->injectCheckerData();
        $this->injectEditorData();
        
        // inject data concerning money
        $this->injectValueData('', 'taxPercent');
        $this->injectValueData('original', 'taxPercent');
    }
    
    /**
     * Injects the customerName by the given OrderId
     */
    protected function injectCustomerData() {
        unset($this->data->customerName);
        
        if(empty($this->data->orderId)) {
            return;
        }
        $order = ZfExtended_Factory::get('erp_Models_Order');
        /* @var $order erp_Models_Order */
        
        try {
            $order->load($this->data->orderId);
            $this->data->customerName = $order->getCustomerName();
        }
        catch(ZfExtended_Models_Entity_NotFoundException $exception)
        {
            $this->log->logError(__CLASS__.'->'.__FUNCTION__.'; Order can not be loaded. '.$exception->getErrors());
            $this->additionalErrors['orderId'] = 'Der Auftrag konnte nicht gefunden werden.';
        }
        
    }
    
    /**
     * Injects the Vendor Data to the given vendorId into the PO Entity 
     */
    protected function injectVendorData() {
        unset($this->data->vendorName);
        unset($this->data->vendorNumber);
        unset($this->data->vendorCurrency);
        unset($this->data->taxPercent);
        
        if(empty($this->data->vendorId)) {
            return;
        }
        
        $vendor = ZfExtended_Factory::get('erp_Models_Vendor');
        /* @var $vendor erp_Models_Vendor */
        try {
            $data = $vendor->load($this->data->vendorId);
            $this->data->vendorName = $data['text'];
            $this->data->vendorNumber = $data['number'];
            $this->data->vendorCurrency = $data['currency'];
            $this->data->taxPercent = (float) $data['taxRate'];
        }
        catch(ZfExtended_Models_Entity_NotFoundException $exception)
        {
            $this->log->logError(__CLASS__.'->'.__FUNCTION__.'; Vendor can not be loaded. '.$exception->getErrors());
            $this->additionalErrors['vendorId'] = 'Der Vendor konnte nicht gefunden werden.';
        }
    }
    
    /**
     * (non-PHPdoc)
     * @see ZfExtended_RestController::postAction()
     */
    public function postAction()
    {
        $this->entity->init();
        $this->decodePutData();
        $this->processClientReferenceVersion();
        $this->setDataInEntity($this->postBlacklist);
        if($this->validate()){
            $this->entity->save();
            $this->entity->calculateNumber();
            $this->entity->save();
            $this->entity->updatePoCount();
            //create PDF → save it to the disl
            //send PDF to the vendor by email
            $this->sendMail();
            $this->view->rows = $this->entity->getDataObject();
        }
    }
    /**
     * two extra-lines:
     * $history = $this->entity->getNewHistoryEntity();
     * $history->save();
     * 
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
            $this->view->rows = $this->entity->getDataObject();
            $this->setPaiedDateForAll();
            $this->handleBlockedComment((int)$this->_getParam('id'));
            
            $sendMail = $this->_getParam('sendMail');
            if(isset($sendMail)){
                /***
                * Create PDF → save it to the disk
                * send PDF to the vendor by email
                * true for editMode
                */
                $this->sendMail(true);
            }
        }
    }
    
    //set the same paidDate for all po's with the same billNumber,
    //this will be executed if the user agrees to (he click yes to the provided message box)
    private function setPaiedDateForAll(){
        if(isset($this->data->sameDatePoIds)){
            $poIds=explode(",", $this->data->sameDatePoIds);
            if(empty($poIds)){
                return;
            }
            $paidDate = null;
            if(isset($this->data->paidDate)){
                $paidDate = $this->data->paidDate;
            }
            foreach ($poIds as $pid){
                if($pid == $this->_getParam('id')){
                    continue;
                }
                $this->entity->load((int)$pid);
                $history = $this->entity->getNewHistoryEntity();
                $this->entity->setPaidDate($paidDate);
                $this->entity->setState(erp_Models_PurchaseOrder::STATE_PAID);
                $history->save();
                $this->entity->save();
            }
        }
    }
    
    public function sumAction () {
        $this->view->rows = $this->entity->calcFilteredSum();
    }
    
    public function excelAction($filter='') {
        $rows = $this->entity->loadAll();
        
        $excel = ZfExtended_Factory::get('ZfExtended_Models_Entity_ExcelExport');
        /* @var $excel ZfExtended_Models_Entity_ExcelExport */
        
        // set property for export-filename
        $excel->setProperty('filename', 'ERP-Export-PurchaseOrder');
        
        // set hidden fields
        $excel->setHiddenField('entityVersion');
        $excel->setHiddenField('pmId');
        $excel->setHiddenField('vendorId');
        $excel->setHiddenField('checkerId');
        $excel->setHiddenField('editorId');
        
        $callbackComments = function($comment) {
            $regex = '/<div[\s]+class="comment">[\s]+<span[\s]+class="content">([^<]+)<\/span>[\s]+<span[\s]+class="author">([^<]+)<\/span>[\s]+<span[\s]+class="modified">([^<]+)<\/span>[\s]+<\/div>[\s]*/m';
            return preg_replace($regex, "$1\n$2 ($3) \n\n", $comment);
        };
        
        $excel->setCallback('comments', $callbackComments);
        
        $callbackBookingMonth = function($bookingMonth){
            if($bookingMonth==null || $bookingMonth<0){
                return;
            }
            return $this->monthNames[$bookingMonth];
        };
        
        $excel->setCallback('bookingMonth', $callbackBookingMonth);
        
        $callbackBookingYear = function($bookingYear){
            if($bookingYear<0){
                return;
            }
            return $bookingYear;
        };
        
        $excel->setCallback('bookingYear', $callbackBookingYear);
        
        
        /* @var $langModel erp_Models_Languages */
        $langModel = ZfExtended_Factory::get('erp_Models_Languages');
        $languages=$langModel->getAvailableLanguages();
        
        if(!empty($languages)){
            $excel->setCallback('sourceLang',function($sourceLang) use ($languages){
                foreach ($languages as $sl){
                    if($sl['value']==$sourceLang){
                        return $sl['text'];
                    }
                }
                return $sourceLang;
            });
        }
        if(!empty($languages)){
            $excel->setCallback('targetLang',function($targetLang) use ($languages){
                foreach ($languages as $sl){
                    if($sl['value']==$targetLang){
                        return $sl['text'];
                    }
                }
                return $targetLang;
            });
        }
        // field-type settings
        // date fields
        $excel->setFieldTypeDate('creationDate');
        $excel->setFieldTypeDate('billDate');
        $excel->setFieldTypeDate('billReceivedDate');
        $excel->setFieldTypeDate('paidDate');
        $excel->setFieldTypeDate('modifiedDate');
        
        // currency fields
        $excel->setFieldTypeCurrency('netValue');
        $excel->setFieldTypeCurrency('taxValue');
        $excel->setFieldTypeCurrency('grossValue');
        $excel->setFieldTypeCurrency('originalNetValue');
        $excel->setFieldTypeCurrency('originalTaxValue');
        $excel->setFieldTypeCurrency('originalGrossValue');
        
        // percent fields
        $excel->setFieldTypePercent('taxPercent');
        
        
        $excel->simpleArrayToExcel($rows);
    }
    
    public function deleteAction() {
        throw new ZfExtended_BadMethodCallException(__CLASS__.' -> '.__FUNCTION__);
    }
    
    //add comment for po,which content depends of paymentBlockValue variable
    public function handleBlockedComment($poId){
        if(!isset($this->data->paymentBlockValue)){
            return;
        }
        switch($this->data->paymentBlockValue){
            case 'block':
                $this->insertBlockedComment($poId,"PO Bezahlung blockiert");
                break;
            case 'unblock':
                $this->insertBlockedComment($poId,"PO ist nicht mehr geblockt");
                //$this->deleteBlockedComment($poId,$poCommentTmpModel);
                break;
        }
    }
    
    /***
     * This function will create the pdf file in the disc, and will send mail with the pdf attachment in it

     * @param boolean $isEdit - default false
     */
    private function sendMail($isEdit = false){
        $vendor = json_decode($this->_getParam('vendor'));
        $dirtyFields= $this->_getParam('dirtyFields');
        
        //reload the entity
        $this->entity->load($this->entity->getDataObject()->id);

        $poData = $this->entity->getDataObject();
        $isGermanTemplate=strtolower($vendor->SourceLang)=='de-de';
        
        $this->pdfExport($poData,$vendor,$dirtyFields,false);
        
        $this->sendVendorMail($vendor,$poData, $isGermanTemplate,$isEdit);
    }

    /***
     * This action will download the file, the file exist on the file system
     */    
    public function pdfdownloadAction() {
        $this->getAction();
        $vendor=json_decode($this->_getParam('vendor'));
        $fileName = $this->entity->getPdfFileName($vendor);
        $fullfilename =$this->entity->getPdfFilePath($this->entity->getOrderId()).$fileName;
        header('Content-Type: application/pdf', TRUE);
        header('Content-Disposition: attachment; filename="'.$fileName.'"');
        readfile($fullfilename);
    }

    /***
     * Display the pdf file in the pdf window preview in the frontend
     */
    public function pdfpreviewAction(){
        $requestData = $this->getRequest()->getParams('data');
        $poData = null;
        $vendor = null;
        if(isset($requestData)){
            $poData = json_decode($requestData['data']);
            $vendor = json_decode($requestData['vendor']);
            $dirtyFields = json_decode($requestData['dirtyFields']);
            $filename = $this->pdfExport($poData,$vendor,$dirtyFields,true);
            exit;
        }
    }

    private function insertBlockedComment($poId,$commentText){
        /* @var $poCommentTmpModel erp_Models_Comment_PurchaseOrderComment */
        $poCommentTmpModel=ZfExtended_Factory::get('erp_Models_Comment_PurchaseOrderComment');
        
        $poCommentTmpModel->setComment($commentText);
        $poCommentTmpModel->setUserId($this->data->editorId);
        $poCommentTmpModel->setUserName($this->data->editorName);
        $now = date('Y-m-d H:i:s');
        $poCommentTmpModel->setCreated($now);
        $poCommentTmpModel->setModified($now);
        $poCommentTmpModel->setPurchaseOrderId($poId);
        $poCommentTmpModel->setEntityVersion($this->data->entityVersion);
        $poCommentTmpModel->save();
        $poCommentTmpModel->updatePurchaseOrder($poId);
    }

    /***
     * Sent mail to the vendor and pm, with pdf as attachment in it
     * 
     * @param stdClass $vendor
     * @param stdClass $poData
     * @param boolean $isGermanTemplate
     * @param boolean $isEdit - default false
     */
    private function sendVendorMail($vendor,$poData,$isGermanTemplate,$isEdit=false){
        
        $pmUser = ZfExtended_Factory::get('ZfExtended_Models_User');
        /* @var $pmUser ZfExtended_Models_User */
        $pmUserEntity = $pmUser->load($poData->pmId);
        
        $mailSender=$pmUserEntity['email'];
        
        $attachmentName =$this->entity->getPdfFileName($vendor);
        $mailSenderName=$pmUserEntity['firstName'].' '.$pmUserEntity['surName'];
        
        if(!$vendor->IsCompany){
            $senderToName = $vendor->FirstName.' '.$vendor->LastName;
        }else{
            $senderToName=$vendor->Company;
        }
        
        $mailTitle='PO '.$poData->orderId.'-'.$poData->number.' / '.$senderToName;

        $filename=$this->entity->getPdfFilePath().$attachmentName;
        
        if($vendor->Email!=null){
            $sendMail = ZfExtended_Factory::get('erp_Models_VendorNotification');
            /* @var $sendMail erp_Models_VendorNotification */
            
            $sendMail->setFileName($filename);
            $sendMail->setAttachmentName($attachmentName);
            $sendMail->setFromMail($mailSender);
            $sendMail->setFromName($mailSenderName);
            $sendMail->setMailTitle($mailTitle);
            $sendMail->createNotification(['isEdit'=>$isEdit,'vendor'=>$vendor,'poData'=>$poData,'pmFirstName'=>$pmUserEntity['firstName'],'pmSurName'=>$pmUserEntity['surName']],$isGermanTemplate);
            $sendMail->setVendor($vendor);
            $sendMail->setToMail($vendor->Email);
            $sendMail->setToName($senderToName);
            $sendMail->notify();
        }
        
        $sendMailpm = ZfExtended_Factory::get('erp_Models_VendorNotification');
        /* @var $sendMailpm erp_Models_VendorNotification  */

        //notefy the pm
        $sendMailpm->setFileName($filename);
        $sendMailpm->setAttachmentName($attachmentName);
        $sendMailpm->setFromMail($mailSender);
        $sendMailpm->setFromName($mailSenderName);
        $sendMailpm->setMailTitle($mailTitle);
        $sendMailpm->setVendor($vendor);
        $sendMailpm->createNotification(['isEdit'=>$isEdit,'vendor'=>$vendor,'poData'=>$poData,'pmFirstName'=>$pmUserEntity['firstName'],'pmSurName'=>$pmUserEntity['surName']],$isGermanTemplate);
        $sendMailpm->setToMail($pmUserEntity['email']);
        $sendMailpm->setToName($pmUserEntity['firstName'].' '.$pmUserEntity['surName']);
        $sendMailpm->notify();
    }
    
    /***
     * Show the pdf as preview or create and send the file to download.This depends on isPreviw variable.
     * 
     * @param stdClass $poData
     * @param stdClass $vendor
     * @param string $dirtyFields
     * @param boolean $isPreview
     */
    private function pdfExport($poData,&$vendor,$dirtyFields,$isPreview){
        $projectModel = ZfExtended_Factory::get('erp_Models_Order');
        /* @var $projectModel erp_Models_Order */
        $projectModel->load($poData->orderId);
    
        $className='erp_Models_BillTemplateEnglish';
        //if the source language is german,show German template,
        //otherwise show international
        if(strtolower($vendor->SourceLang)=='de-de'){
            $className='erp_Models_BillTemplateGerman';
        }
    
        $pdfExport=ZfExtended_Factory::get($className);
        $pdfExport->setProject($projectModel);
        $pdfExport->setPoData($poData);
        $pdfExport->setVendor($vendor);
        $pdfExport->isPreview($isPreview);
    
        $pdfExport->setDirtyFields($dirtyFields);
        $pdfExport->init();
        $pdfExport->render();
    
        if($isPreview){
            $pdfExport->preview();
            return;
        }
        //in this point the po entity is loaded
        $fullFileName = $this->entity->getPdfFilePath().$this->entity->getPdfFileName($vendor);
        $pdfExport->export('F',$fullFileName);
        
        //update the vendor salutation based on the template language
        //the salutation is needed for sending mail
        $pdfExport->updateVendorSalutation($vendor);
    }
}