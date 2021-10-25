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
 * ERP-PurchaseOrderHistory Entity Objekt
 * 
 * @method integer getId() getId()
 * @method void setId() setId(integer $id)
 * 
 * @method integer getPurchaseOrderId() getPurchaseOrderId()
 * @method void setPurchaseOrderId() setPurchaseOrderId(integer $id)
 * 
 * @method string getHistoryCreated() getHistoryCreated()
 * @method void setHistoryCreated() setHistoryCreated(string $date)
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
 * @method void setPmId() setId(integer $id)
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
*/
class erp_Models_PurchaseOrderHistory extends ZfExtended_Models_Entity_Abstract {
    protected $dbInstanceClass          = 'erp_Models_Db_PurchaseOrderHistory';
    //protected $validatorInstanceClass   = 'erp_Models_Validator_PurchaseOrderHistory';
}
