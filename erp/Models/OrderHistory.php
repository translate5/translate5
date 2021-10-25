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
 * ERP-OrderHistory Entity Objekt
 * 
 * @method integer getId() getId()
 * @method void setId() setId(integer $id)
 * 
 * @method integer getOrderId() getOrderId()
 * @method void setOrderId() setOrderId(integer $id)
 * 
 * @method string getHistoryCreated() getHistoryCreated()
 * @method void setHistoryCreated() setHistoryCreated(string $date)
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
 * 
 * @method float getTaxPercent() getTaxPercent()
 * @method void setTaxPercent() setTaxPercent(float $margin)
 *
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
*/


class erp_Models_OrderHistory extends ZfExtended_Models_Entity_Abstract {
    protected $dbInstanceClass          = 'erp_Models_Db_OrderHistory';
    //protected $validatorInstanceClass   = 'erp_Models_Validator_OrderHistory';
}
