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

/* * #@+
 * @author Marc Mittag
 * @package editor
 * @version 1.0
 *
 */

/**
 * ERP-PurchaseOrder Entity Objekt
 * 
 * @method integer getId() getId()
 * @method void setId() setId(integer $id)
 * 
 * @method integer getOrderId() getOrderId()
 * @method void setOrderId() setOrderId(integer $id)
 * 
 * @method integer getNumber() getNumber()
 * @method void setNumber() setNumber(integer $number)
 * 
 * @method string getCreationDate() getCreationDate()
 * @method void setCreationDate() setCreationDate(string $date)
 * 
 * @method string getCustomerName() getCustomerName()
 * @method void setCustomerName() setCustomerName(string $name)
 * 
 * @method integer getPmId() getPmId()
 * @method void setPmId() setPmId(integer $id)
 * 
 * @method string getPmName() getPmName()
 * @method void setPmName() setPmName(string $name)
 * 
 * @method integer getVendorId() getVendorId()
 * @method void setVendorId() setVendorId(integer $id)
 * 
 * @method string getVendorName() getVendorName()
 * @method void setVendorName() setVendorName(string $name)
 * 
 * @method string getVendorNumber() getVendorNumber()
 * @method void setVendorNumber() setVendorNumber(string $number)
 * 
 * @method float getNetValue() getNetValue()
 * @method void setNetValue() setNetValue(float $value)
 * 
 * @method float getTaxValue() getTaxValue()
 * @method void setTaxValue() setTaxValue(float $tax)
 * 
 * @method float getGrossValue() getGrossValue()
 * @method void setGrossValue() setGrossValue(float $value)
 * 
 * @method float getTaxPercent() getTaxPercent()
 * @method void setTaxPercent() setTaxPercent(float $tax)
 * 
 * @method string getVendorCurrency() getVendorCurrency()
 * @method void setVendorCurrency() setVendorCurrency(string $currency)
 * 
 * @method float getOriginalNetValue() getOriginalNetValue()
 * @method void setOriginalNetValue() setOriginalNetValue(float $value)
 * 
 * @method float getOriginalTaxValue() getOriginalTaxValue()
 * @method void setOriginalTaxValue() setOriginalTaxValue(float $value)
 * 
 * @method float getOriginalGrossValue() getOriginalGrossValue()
 * @method void setOriginalGrossValue() setOriginalGrossValue(float $value)
 * 
 * @method string getState() getState()
 * @method void setState() setState(string $state)
 * 
 * @method string getBillDate() getBillDate()
 * @method void setBillDate() setBillDate(string $date)
 * 
 * @method string getBillReceivedDate() getBillReceivedDate()
 * @method void setBillReceivedDate() setBillReceivedDate(string $date)
 * 
 * @method integer getPaymentTerm() getPaymentTerm()
 * @method void setPaymentTerm() setPaymentTerm(integer $term)
 * 
 * @method boolean getChecked() getChecked()
 * @method void setChecked() setChecked(boolean $checked)
 * 
 * @method integer getCheckerId() getCheckerId()
 * @method void setCheckerId() setCheckerId(integer $id)
 * 
 * @method string getCheckerName() getCheckerName()
 * @method void setCheckerName() setCheckerName(string $name)
 * 
 * @method string getPaidDate() getPaidDate()
 * @method void setPaidDate() setPaidDate(string $date)
 * 
 * @method string getModifiedDate() getModifiedDate()
 * @method void setModifiedDate() setModifiedDate(string $date)
 * 
 * @method string getBillNumber() getBillNumber()
 * @method void setBillNumber() setBillNumber(string $number)
 * 
 * @method string getComments() getComments()
 * @method void setComments() setComments(string $comment)
 * 
 * @method integer getEditorId() getEditorId()
 * @method void setEditorId() setEditorId(integer $id)
 * 
 * @method string getEditorName() getEditorName()
 * @method void setEditorName() setEditorName(string $name)
 * 
 * 
*/
class erp_Models_PurchaseOrder extends ZfExtended_Models_Entity_Abstract {
    // list of all possible purchaseOrder-states:
    // 'created','billed','paied','cancelled'
    const STATE_CREATED   = 'created';
    const STATE_BILLED    = 'billed';
    const STATE_PAID     = 'paid';
    const STATE_CANCELLED = 'cancelled';
    const STATE_BLOCKED = 'blocked';
    
    protected $dbInstanceClass          = 'erp_Models_Db_PurchaseOrder';
    protected $validatorInstanceClass   = 'erp_Models_Validator_PurchaseOrder';
    
    private static $statesTranslation = array(  'created' => 'PO erstellt',
                                                'billed' => 'V-Rechnung eingegangen',
                                                'cancelled' => 'storniert',
                                                'paid' => 'V-Rechnung bezahlt',
                                                'blocked' => 'Bezahlung blockiert');
    

    public function calculateNumber() {
        if ($this->getNumber() > 0) {
            return;
        }
        
        try {
            $orderId = $this->getOrderId();
            $db = $this->db;
            $s = $db->select()->from($db->info($db::NAME), 'number');
            $s->where('orderId = ?', $orderId)->order('number DESC')->limit(1);
            $result = $db->fetchAll($s)->toArray();
            
            if (!empty($result)) {
                $number = $result[0]['number'] + 1;
            }
            else
            {
                $number = 1;
            }
            $this->setNumber($number);
        }
        catch(Zend_Db_Statement_Exception $e) {
            $msg = $e->getMessage();
            if(stripos($msg, 'duplicate entry') === false) {
                throw $e; //otherwise throw this again
            }
            // duplicate number => calculate number again
            $this->setNumber(0);
            $this->calculateNumber();
        }
    }
    
    /**
     * creates a new, empty, unsaved PurchaseOrderHistory entity
     * 
     * @return erp_Models_PurchaseOrderHistory
     */
    public function getNewHistoryEntity() {
        $history = ZfExtended_Factory::get('erp_Models_PurchaseOrderHistory');
        /* @var $history erp_Models_PurchaseOrderHistory */
        
        $fields = array('orderId', 'number', 'creationDate', 'customerName', 'pmId', 'pmName','sourceLang','targetLang',
                        'vendorId', 'vendorName', 'vendorNumber', 'netValue', 'taxValue', 'grossValue', 'taxPercent',
                        'vendorCurrency', 'originalNetValue', 'originalTaxValue', 'originalGrossValue', 'state', 'billDate', 'billReceivedDate', 'paymentTerm',
                        'checked', 'checkerId', 'checkerName', 'paidDate', 'billNumber', 'comments', 'editorId', 'editorName', 'modifiedDate',
                        'deliveryDate','wordsCount','wordsDescription','hoursCount','hoursDescription',
                        'additionalCount','additionalDescription','additionalUnit','additionalPrice',
                        'transmissionPath','additionalInfo','perWordPrice','perHourPrice','perAdditionalUnitPrice'
                        );
        $history->setPurchaseOrderId($this->getId());
        
        foreach ($fields as $field) {
            $history->__call('set' . ucfirst($field), array($this->get($field)));
        }
        
        return $history;
    }
    
    public function getStatesList() {
        return array(
                'states' => ZfExtended_Utils::getConstants('erp_Models_PurchaseOrder', 'STATE_'), 
                'stateLabels' => $this::$statesTranslation,
        );
    }
    
    public function calcFilteredSum() {
        $order = ZfExtended_Factory::get('erp_Models_Order');
        /* @var $order erp_Models_Order */
        $db = $this->db;
        
        $s = $this->db->select();
        $s->setIntegrityCheck(false);
        
        $cols = array(
            'netValue' => 'SUM(po.netValue)',
            'taxValue' => 'SUM(po.taxValue)',
            'grossValue' => 'SUM(po.grossValue)',
        );
        
        //fetch the po sums and the order billNetvalue for billed, paid and ordered states only
        //since the result is grouped by order, we have to generate the overall sum in PHP then
        $s->from(array('po' => $db->info($db::NAME)), $cols)
          ->from(array('o' => $order->db->info($db::NAME)), array('o.id', 'o.billNetValue'))
          ->where('o.id = po.orderId')
          ->group('o.id');
        
        $this->filter->setDefaultTable('po');
        $sumsPerOrder = $this->loadFilterdCustom($s);
        $this->filter->setDefaultTable('');
        
        // init summary structur
        $sum = new stdClass();
        $sum->netValue = 0;
        $sum->taxValue = 0;
        $sum->grossValue = 0;
        $sum->orderBillNetValue = 0;
        
        // calculate summaries
        foreach ($sumsPerOrder as $orderPoSum) {
            $sum->netValue += $orderPoSum['netValue'];
            $sum->taxValue += $orderPoSum['taxValue'];
            $sum->orderBillNetValue += $orderPoSum['billNetValue'];
        }
        
        // calculate grossValue
        $sum->grossValue = $sum->netValue + $sum->taxValue;
        
        // calculate raw-margin
        if($sum->orderBillNetValue > 0){
            $sum->rawMargin = round(($sum->orderBillNetValue - $sum->netValue) * 100 / $sum->orderBillNetValue, 2);
        } else {
            $sum->rawMargin = 0;
        }
        return $sum;
    }
    /***
     * Increment the poCount(ERP_order table) field by one when new po is created
     */
    public function updatePoCount(){
        /* @var $order erp_Models_Order */
        $order = ZfExtended_Factory::get('erp_Models_Order');
        $order->updatePoCount($this->getOrderId());
    }
    
    public function getCustomerIdForPo($orderId){
        /* @var $order erp_Models_Order */
        $order = ZfExtended_Factory::get('erp_Models_Order');
        $order->load($orderId);
        return $order->getCustomerId();
    }

    /***
     * Return the name of the pdf file.
     * 
     * @param $vendor: vendor data neede for generating the pdf filename
     * @return string
     */
    public function getPdfFileName($vendor){
        //convert to array if it is a object
        if(is_object($vendor)){
            $vendor=json_decode(json_encode($vendor), true);
        }
        
        //if the vendor is company, use the company name as filename
        $senderToName = $vendor['FirstName'].' '.$vendor['LastName'];
        if($vendor['IsCompany']){
            $senderToName=$vendor['Company'];
        }
        $ret= preg_replace('/\,+/', ' ', $senderToName);//replace whitespace with _
        $ret= preg_replace('/\.+/', '', $ret);//replace whitespace with _
        $ret= preg_replace('/\s+/', '_', $ret);//replace whitespace with _
        $ret=ltrim($ret, '_');//remove leading _ if exist
        $ret=rtrim($ret, '_');//remove _ at the nd if exist
        return 'PO-'.$this->getOrderId().'-'.$this->getNumber().'-v'.$this->getEntityVersion().'_'.$ret.'.pdf';
    }
    
    /***
     * Get the folder path where the pdf file is located.
     * @return string
     */
    public function getPdfFilePath(){
        return APPLICATION_PATH.'/../data/purchaseOrderPdfs/'.$this->getOrderId().'/';
    }
}