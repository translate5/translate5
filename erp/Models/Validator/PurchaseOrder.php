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

class erp_Models_Validator_PurchaseOrder extends ZfExtended_Models_Validator_Abstract {
    
    /**
    * Validators for PurchaseOrder Entity
    */
    protected function defineValidators() {
        
        //`id` int(11) NOT NULL AUTO_INCREMENT,
        $this->addValidator('id', 'int', array(), true);
        
        //`entityVersion` int(11) DEFAULT '0',
        $this->addValidator('entityVersion', 'int');
        
        //`orderId` int(11) DEFAULT NULL,
        $this->addValidator('orderId', 'int');
        
        //`number` int(11) DEFAULT NULL,
        $this->addValidator('number', 'int');
        
        //`sourceLang` varchar(255) DEFAULT NULL,
        $this->addValidator('sourceLang', 'stringLength', array('min' => 0, 'max' => 255));
        
        //`targetLang` varchar(255) DEFAULT NULL,
        $this->addValidator('targetLang', 'stringLength', array('min' => 0, 'max' => 255));
        
        //`creationDate` datetime DEFAULT NULL,
        $this->addValidator('creationDate', 'date', array('Y-m-d'));
        
        //`customerName` varchar(255) DEFAULT NULL,
        $this->addValidator('customerName', 'stringLength', array('min' => 0, 'max' => 255));
        
        //`pmId` int(11) DEFAULT NULL,
        $this->addValidator('pmId', 'int');
        
        //`pmName` varchar(255) DEFAULT NULL,
        $this->addValidator('pmName', 'stringLength', array('min' => 0, 'max' => 255));
        
        //`vendorId` int(11) DEFAULT NULL,
        $this->addValidator('vendorId', 'int');
        
        //`vendorName` varchar(255) DEFAULT NULL,
        $this->addValidator('vendorName', 'stringLength', array('min' => 0, 'max' => 255));
        
        //`vendorNumber` varchar(255) DEFAULT NULL,
        $this->addValidator('vendorNumber', 'stringLength', array('min' => 0, 'max' => 255));
        
        //`netValue` decimal(19,4) DEFAULT NULL,
        $this->addValidator('netValue', 'float');
        
        //`taxValue` decimal(19,4) DEFAULT NULL,
        $this->addValidator('taxValue', 'float');
        
        //`grossValue` decimal(19,4) DEFAULT NULL,
        $this->addValidator('grossValue', 'float');
        
        //`taxPercent` decimal(6,4) DEFAULT NULL,
        $this->addValidator('taxPercent', 'float');
        
        //`vendorCurrency` varchar(10) DEFAULT NULL,
        $this->addValidator('vendorCurrency', 'stringLength', array('min' => 0, 'max' => 10));
        
        //`originalNetValue` decimal(19,4) DEFAULT NULL,
        $this->addValidator('originalNetValue', 'float');
        
        //`originalTaxValue` decimal(19,4) DEFAULT NULL,
        $this->addValidator('originalTaxValue', 'float');
        
        //`originalGrossValue` decimal(19,4) DEFAULT NULL,
        $this->addValidator('originalGrossValue', 'float');
        
        //`state` enum('created','billed','paied','cancelled','blocked') DEFAULT 'created',
        $this->addValidator('state', 'inArray', array(ZfExtended_Utils::getConstants('erp_Models_PurchaseOrder', 'STATE_')));
        
        //`billDate` datetime DEFAULT NULL,
        $this->addValidator('billDate', 'date', array('Y-m-d'), true);
        
        //`billReceivedDate` datetime DEFAULT NULL,
        $this->addValidator('billReceivedDate', 'date', array('Y-m-d'), true);
        
        //`paymentTerm` int(11) DEFAULT NULL,
        $this->addValidator('paymentTerm', 'int', array(), true);
        
        //`checked` tinyint(1) DEFAULT NULL,
        $this->addValidator('checked', 'int');
        
        //`checkerId` int(11) DEFAULT NULL,
        $this->addValidator('checkerId', 'int');
        
        //`checkerName` varchar(255) DEFAULT NULL,
        $this->addValidator('checkerName', 'stringLength', array('min' => 0, 'max' => 255));
        
        //`paidDate` datetime DEFAULT NULL,
        $this->addValidator('paidDate', 'date', array('Y-m-d'), true);
        
        //`billNumber` varchar(255) DEFAULT NULL,
        $this->addValidator('billNumber', 'stringLength', array('min' => 0, 'max' => 255));
        
        //`editorId` int(11) DEFAULT NULL,
        $this->addValidator('editorId', 'int');
        
        //`editorName` varchar(255) DEFAULT NULL,
        $this->addValidator('editorName', 'stringLength', array('min' => 0, 'max' => 255));
        
        //`modifiedDate` datetime DEFAULT NULL,
        $this->addValidator('modifiedDate', 'date', array('Y-m-d'));
        
        //`bookingYear` int(11) DEFAULT NULL,
        $this->addValidator('bookingYear', 'int',array(),true);
        
        //`bookingMonth` int(11) DEFAULT NULL,
        $this->addValidator('bookingMonth', 'int',array(),true);
        
        //`orderStatus` varchar(45) DEFAULT NULL,
        $this->addValidator('orderStatus', 'stringLength', array('min' => 0, 'max' => 45));

        //`deliveryDate` datetime DEFAULT NULL,
        $this->addValidator('deliveryDate', 'date', array('Y-m-d'), true);

        //`wordsCount` int(11) DEFAULT NULL,
        $this->addValidator('wordsCount', 'int',array(),true);

        //`wordsDescription` varchar(100) DEFAULT NULL,
        $this->addValidator('wordsDescription', 'stringLength', array('min' => 0, 'max' => 100));

        //`hoursCount` decimal(19,4) DEFAULT NULL,
        $this->addValidator('hoursCount', 'float',array(),true);
        
        //`hoursDescription` varchar(100) DEFAULT NULL,
        $this->addValidator('hoursDescription', 'stringLength', array('min' => 0, 'max' => 100));

        //`additionalCount` decimal(19,4) DEFAULT NULL,
        $this->addValidator('additionalCount', 'float',array(),true);

        //`additionalDescription` varchar(100) DEFAULT NULL,
        $this->addValidator('additionalDescription', 'stringLength', array('min' => 0, 'max' => 100));

        //`additionalUnit` varchar(45) DEFAULT NULL,
        $this->addValidator('additionalUnit', 'stringLength', array('min' => 0, 'max' => 45));

        //`additionalPrice` decimal(19,4) DEFAULT NULL,
        $this->addValidator('additionalPrice', 'float',array(),true);

        //`transmissionPath` varchar(100) DEFAULT NULL,
        $this->addValidator('transmissionPath', 'stringLength', array('min' => 0, 'max' => 100));

        //`additionalInfo` varchar(255) DEFAULT NULL,
        $this->addValidator('additionalInfo', 'stringLength', array('min' => 0, 'max' => 255));
        
        //`perWordPrice` decimal(19,4) DEFAULT NULL,
        $this->addValidator('perWordPrice', 'float',array(),true);
        
        //`perHourPrice` decimal(19,4) DEFAULT NULL,
        $this->addValidator('perHourPrice', 'float',array(),true);
        
        //`perAdditionalUnitPrice` decimal(19,4) DEFAULT NULL,
        $this->addValidator('perAdditionalUnitPrice', 'float',array(),true);
        
        //`perLinePrice` decimal(19,4) DEFAULT NULL,
        $this->addValidator('perLinePrice', 'float',array(),true);
    }
}
