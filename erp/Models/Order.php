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
 * ERP-Order Entity Objekt
 * 
 * @method integer getId() getId()
 * @method void setId() setId(integer $id)
 * 
 * @method integer getEntityVersion() getEntityVersion()
 * @method void setEntityVersion() setEntityVersion(integer $version)
 * 
 * @method integer getDebitNumber() getDebitNumber()
 * @method void setDebitNumber() setDebitNumber(integer $number)
 * 
 * @method string getName() getName()
 * @method void setName() setName(string $name)
 * 
 * @method string getOfferDate() getOfferDate()
 * @method void setOfferDate() setOfferDate(string $date)
 * 
 * @method string getBillDate() getBillDate()
 * @method void setBillDate() setBillDate(string $date)
 * 
 * @method string getPaidDate() getPaidDate()
 * @method void setPaidDate() setPaidDate(string $date)
 * 
 * @method string getReleaseDate() getReleaseDate()
 * @method void setReleaseDate() setReleaseDate(string $date)
 * 
 * @method string getModifiedDate() getModifiedDate()
 * @method void setModifiedDate() setModifiedDate(string $date)
 * 
 * @method integer getConversionMonth() getConversionMonth()
 * @method void setConversionMonth() setConversionMonth(integer $month)
 * 
 * @method integer getConversionYear() getConversionYear()
 * @method void setConversionYear() setConversionYear(integer $year)
 * 
 * @method string getKeyAccount() getKeyAccount()
 * @method void setKeyAccount() setKeyAccount(string $name)
 * 
 * @method integer getCustomerId() getCustomerId()
 * @method void setCustomerId() setCustomerId(integer $customerId)
 * 
 * @method string getCustomerName() getCustomerName()
 * @method void setCustomerName() setCustomerName(string $name)
 * 
 * @method string getCustomerNumber() getCustomerNumber()
 * @method void setCustomerNumber() setCustomerNumber(string $number)
 * 
 * @method string getCustomerOrder() getCustomerOrder()
 * @method void setCustomerOrder() setCustomerOrder(string $orderNumber)
 * 
 * @method integer getPmId() getPmId()
 * @method void setPmId() setId(integer $id)
 * 
 * @method string getPmName() getPmName()
 * @method void setPmName() setPmName(string $name)
 * 
 * 
 * @method float getOfferNetValue() getOfferNetValue()
 * @method void setOfferNetValue() setOfferNetValue(float $value)
 * 
 * @method float getOfferTaxValue() getOfferTaxValue()
 * @method void setOfferTaxValue() setOfferTaxValue(float $value)
 * 
 * @method float getOfferGrossValue() getOfferGrossValue()
 * @method void setOfferGrossValue() setOfferGrossValue(float $value)
 * 
 * @method float getOfferMargin() getOfferMargin()
 * @method void setOfferMargin() setOfferMargin(float $margin)
 * 
 * 
 * @method float getBillNetValue() getBillNetValue()
 * @method void setBillNetValue() setBillNetValue(float $value)
 * 
 * @method float getBillTaxValue() getBillTaxValue()
 * @method void setBillTaxValue() setBillTaxValue(float $tax)
 * 
 * @method float getBillGrossValue() getBillGrossValue()
 * @method void setBillGrossValue() setBillGrossValue(float $value)
 * 
 * @method float getBillMargin() getBillMargin()
 * @method void setBillMargin() setBillMargin(float $margin)
 * 
 * @method float getTaxPercent() getTaxPercent()
 * @method void setTaxPercent() setTaxPercent(float $margin)
 * 
 * @method string getState() getState()
 * @method void setState() setState(string $state)
 * 
 * @method string getComments() getComments()
 * @method void setComments() setComments(string $comment)
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
 * @method integer getEditorId() getEditorId()
 * @method void setEditorId() setEditorId(integer $id)
 * 
 * @method string getEditorName() getEditorName()
 * @method void setEditorName() setEditorName(string $name)
 * 
 * @method boolean getIsCustomerView() getIsCustomerView()
 * @method void setIsCustomerView() setIsCustomerView(boolean $isCustomerView)
*/

class erp_Models_Order extends ZfExtended_Models_Entity_Abstract {
    
    protected $dbInstanceClass          = 'erp_Models_Db_Order';
    protected $validatorInstanceClass   = 'erp_Models_Validator_Order';
    
    // list of all possible order-states:
    // 'proforma','offered','declined','ordered','cancelled','billed','paid'
    const STATE_PROFORMA = 'proforma';
    const STATE_OFFERED  = 'offered';
    const STATE_DECLINED = 'declined';
    const STATE_ORDERED  = 'ordered';
    const STATE_CANCELLED = 'cancelled';
    const STATE_BILLED = 'billed';
    const STATE_PAID = 'paid';
    
    // Array with states 'offer'
    public  static $statesOffer = array(SELF::STATE_OFFERED, SELF::STATE_DECLINED);
    // Array with states 'bill'
    public static $statesBill = array(SELF::STATE_ORDERED, SELF::STATE_CANCELLED, SELF::STATE_BILLED, SELF::STATE_PAID);
    
    // Array with translation of the different states (used as label in input-filed of the GUI)
    private static $statesTranslation = array(  'proforma' => 'pro forma',
                                                'offered' => 'angeboten',
                                                'declined' => 'abgelehnt',
                                                'ordered' => 'beauftragt',
                                                'cancelled' => 'storniert',
                                                'billed' => 'berechnet',
                                                'paid' => 'bezahlt');
    
    public $m_filters;
    
    /***
     * Additional order states
     * @var array
     */
    protected static $additionalStates=[];
    
    /***
     * Additional order states translations
     * @var array
     */
    protected static $additionalStatesTranslation=[];
    
    /***
     * Get all states. The return array will merge the additional states and the all defined states as constants in the class
     * @return array
     */
    public function getAllStates(){
        return array_merge(ZfExtended_Utils::getConstants('erp_Models_Order', 'STATE_'),$this::$additionalStates);
    }
    
    /***
     * Get all state translations. The return array is merge from all aditinal state translations and the one defined in the class
     * @return array
     */
    public function getAllStatesTranslations(){
        return array_merge($this::$statesTranslation,$this::$additionalStatesTranslation);
    }
    
    /***
     * Add additional state to the additional state array
     * @param string $newState
     */
    public function addAdditionalState(string $newState){
        if(in_array($newState, $this::$additionalStates)){
            return;
        }
        $this::$additionalStates['STATE_'.mb_strtoupper($newState)]=$newState;
    }
    
    /***
     * Add additional state translation to the additional state tanslation array
     * @param string $newState
     */
    public function addAdditionalStateTranslation(string $state,string $stateTranslation){
        if(isset($this::$additionalStatesTranslation[$state])){
            return;
        }
        $this::$additionalStatesTranslation[$state]=$stateTranslation;
    }
    
    
    /**
     * Checks if given $state belongs to offer-states
     * 
     * @param string $state
     * @return boolean
     */
    static public function isOfferState ($state = '') {
        return in_array($state, self::$statesOffer);
    }
    
    /**
     * Checks if given $state belongs to bill-states
     * 
     * @param string $state
     * @return boolean
     */
    static public function isBillState ($state = '') {
        return in_array($state, self::$statesBill);
    }
    
    public function getStatesList() {
        return array(
                'states' => $this->getAllStates(), 
                'stateLabels' => $this->getAllStatesTranslations(),
        );
    }
    
    /**
     * creates a new, empty, unsaved OrderHistory entity
     * 
     * @return erp_Models_OrderHistory
     */
    public function getNewHistoryEntity() {
        $history = ZfExtended_Factory::get('erp_Models_OrderHistory');
        /* @var $history erp_Models_OrderHistory */
        
        $fields = array('debitNumber', 'name', 'offerDate', 'billDate', 'paidDate', 'releaseDate', 'modifiedDate',
                        'conversionMonth', 'conversionYear', 'keyAccount', 'customerId', 'customerName', 'customerNumber', 'customerOrder',
                        'pmId', 'pmName','sourceLang','targetLang',
                        'offerNetValue', 'offerTaxValue', 'offerGrossValue', 'offerMargin',
                        'billNetValue', 'billTaxValue', 'billGrossValue', 'billMargin', 
                        'state', 'comments', 'checked', 'checkerId', 'checkerName', 'editorId', 'editorName'
                        );
        $history->setOrderId($this->getId());
        
        foreach ($fields as $field) {
            $history->__call('set' . ucfirst($field), array($this->get($field)));
        }
        
        return $history;
    }
    
    public function calcFilteredSum() {
        $s = $this->db->select();
        
        $cols = array(
            'offerNetValue' => 'SUM(offerNetValue)',
            'offerTaxValue' => 'SUM(offerTaxValue)',
            'billNetValue' => 'SUM(billNetValue)',
            'billTaxValue' => 'SUM(billTaxValue)',
            'tmpOfferMargin' => 'SUM(offerMargin * offerNetValue)',
            'tmpBillMargin' => 'SUM(billMargin * billNetValue)',
        );
        
        $s->from($this->db, $cols);
        
        $res = $this->loadFilterdCustom($s);
        if(empty($res) || empty($res[0])) {
            return;
        }
        
        $sum = (object) $res[0];

        //0.000 from DB evaluates sometimes (float logic) to empty = false 
        //instead expected true for div by zero prevention below
        $floatEmpty = function($val) {
            if(empty($val)) {
                return true;
            }
            return abs(0-$val) < 0.00001;
        };
        
        if ($floatEmpty($sum->tmpOfferMargin) || $floatEmpty($sum->offerNetValue)) {
            $sum->offerMargin = 0;
        }
        else {
            $sum->offerMargin = round($sum->tmpOfferMargin / $sum->offerNetValue, 2);
        }
        unset($sum->tmpOfferMargin);
        
        if ($floatEmpty($sum->tmpBillMargin) || $floatEmpty($sum->billNetValue)) {
            $sum->billMargin = 0;
        }
        else {
            $sum->billMargin = round($sum->tmpBillMargin / $sum->billNetValue, 2);
        }
        unset($sum->tmpBillMargin);
        
        $sum->allNetValue = $sum->offerNetValue + $sum->billNetValue;
        $sum->offerGrossValue = $sum->offerNetValue + $sum->offerTaxValue;
        $sum->billGrossValue = $sum->billNetValue + $sum->billTaxValue;
        
        return $sum;
    }
    
    /***
     * Load all orders
     * @param boolean $mergePoInfo: Merge the order po info in the return array
     * {@inheritDoc}
     * @see ZfExtended_Models_Entity_Abstract::loadAll()
     */
    public function loadAll($mergePoInfo=true){
        $s = $this->db->select();
        $rows=$this->loadFilterdCustom($s);
        if(!$mergePoInfo){
            return $rows;
        }
        return $this->mergePoInfo($rows);
    }

    /***
     * Merge the poInfo array into the result row array
     * @param array $rows
     * @return array
     */
    protected function mergePoInfo($rows){
        if(empty($rows)){
            return $rows;
        }
        foreach ($rows as &$row){
            $result=$this->findPosForOrder($row['id']);
            if(empty($result)){
                continue;
            }
            $row['poInfo']=[];
            foreach ($result as $res){
                array_push($row['poInfo'], $res);
            }
        }
        return $rows;
    }
    //update po count for given orderId
    public function updatePoCount($orderId){
        $this->load($orderId);
        $poCount = $this->getPoCount();
        if($poCount == null){
            $poCount = 0;
        }
        $poCount++;
        $this->setPoCount($poCount);
        $this->save();
    }
    //find all pos for given orderId
    public function findPosForOrder($orderId){
        $db = $this->db;
        $s = $this->db->select();
        $s->setIntegrityCheck(false);
        $s->from('ERP_purchaseOrder')
        ->where('orderId = '.$orderId);
        $result = $db->fetchAll($s)->toArray();
        return $result;
    }

    public function setTargetLang($language) {
        parent::__call(__FUNCTION__, [','.trim($language, ',').',']);
    }
    
    /***
     * Generate unique debid number by given date
     * @param string $billDate
     * @return string|number
     */
    public function generateDebitNumber(string $date){
        $year = date('Y', strtotime($date));
        $db = $this->db;
        $s = $db->select()->from($db->info($db::NAME), 'debitNumber');
        $s->where('debitNumber LIKE ?', $year.'%')->order('debitNumber DESC')->limit(1);
        $result = $db->fetchAll($s)->toArray();
        $debitNumber = $year.'00001';
        if (!empty($result)) {
            $debitNumber = $result[0]['debitNumber'] + 1;
        }
        return  $debitNumber;
    }
}
