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

class erp_Models_Validator_Order extends ZfExtended_Models_Validator_Abstract {
    
    /**
    * Validators for Order Entity
    */
    protected function defineValidators() {
        
        //`id` int(11) NOT NULL AUTO_INCREMENT,
        $this->addValidator('id', 'int', array(), true);
        
        //`entityVersion` int(11) DEFAULT '0',
        $this->addValidator('entityVersion', 'int');
        
        //`debitNumber` int(11) DEFAULT NULL,
        $this->addValidator('debitNumber', 'int', array(), true);
        
        //`name` varchar(255) DEFAULT NULL,
        $this->addValidator('name', 'stringLength', array('min' => 0, 'max' => 255));
        
        //`sourceLang` varchar(255) DEFAULT NULL,
        $this->addValidator('sourceLang', 'stringLength', array('min' => 0, 'max' => 255));
        
        //`targetLang` varchar(255) DEFAULT NULL,
        $this->addValidator('targetLang', 'stringLength', array('min' => 0, 'max' => 65535));
       
        //`offerDate` datetime DEFAULT NULL,
        $this->addValidator('offerDate', 'date', array('Y-m-d'));
        
        //`performanceDate` datetime DEFAULT NULL,
        $this->addValidator('performanceDate', 'date', array('Y-m-d'), true);
        
        //`billDate` datetime DEFAULT NULL,
        $this->addValidator('billDate', 'date', array('Y-m-d'), true);
        
        //`paidDate` datetime DEFAULT NULL,
        $this->addValidator('paidDate', 'date', array('Y-m-d'), true);
        
        //`releaseDate` datetime DEFAULT NULL,
        $this->addValidator('releaseDate', 'date', array('Y-m-d'), true);
        
        //`plannedDeliveryDate` datetime DEFAULT NULL,
        $this->addValidator('plannedDeliveryDate', 'date', array('Y-m-d'), true);
        
        //`modifiedDate` datetime DEFAULT NULL,
        $this->addValidator('modifiedDate', 'date', array('Y-m-d'));
        
        //`conversionMonth` int(11) DEFAULT NULL,
        $this->addValidator('conversionMonth', 'int', array(), true);
        
        //`conversionYear` int(11) DEFAULT NULL,
        $this->addValidator('conversionYear', 'int', array(), true);
        
        //`keyAccount` varchar(255) DEFAULT NULL,
        $this->addValidator('keyAccount', 'stringLength', array('min' => 0, 'max' => 255), true);
        
        //`customerId` int(11) DEFAULT NULL,
        $this->addValidator('customerId', 'int');
        
        //`customerName` varchar(255) NOT NULL DEFAULT '',
        $this->addValidator('customerName', 'stringLength', array('min' => 0, 'max' => 255));
        
        //`customerNumber` varchar(255) DEFAULT NULL,
        $this->addValidator('customerNumber', 'stringLength', array('min' => 0, 'max' => 255));
        
        //`customerOrder` varchar(255) DEFAULT NULL,
        $this->addValidator('customerOrder', 'stringLength', array('min' => 0, 'max' => 255));
        
        //`pmId` int(11) DEFAULT NULL,
        $this->addValidator('pmId', 'int');
        
        //`pmName` varchar(255) NOT NULL DEFAULT '',
        $this->addValidator('pmName', 'stringLength', array('min' => 0, 'max' => 255));
        
        //`offerNetValue` decimal(19,4) DEFAULT NULL,
        $this->addValidator('offerNetValue', 'float');
        
        //`offerTaxValue` decimal(19,4) DEFAULT NULL,
        $this->addValidator('offerTaxValue', 'float');
        
        //`offerGrossValue` decimal(19,4) DEFAULT NULL,
        $this->addValidator('offerGrossValue', 'float');
        
        //`offerMargin` decimal(19,4) DEFAULT NULL,
        $this->addValidator('offerMargin', 'float');
        
        //`billNetValue` decimal(19,4) DEFAULT NULL,
        $this->addValidator('billNetValue', 'float');
        
        //`billTaxValue` decimal(19,4) DEFAULT NULL,
        $this->addValidator('billTaxValue', 'float');
        
        //`billGrossValue` decimal(19,4) DEFAULT NULL,
        $this->addValidator('billGrossValue', 'float');
        
        //`billMargin` decimal(19,4) DEFAULT NULL,
        $this->addValidator('billMargin', 'float', array(), true);
        
        //`taxPercent` decimal(6,4) DEFAULT NULL,
        $this->addValidator('taxPercent', 'float');
        
        $order=ZfExtended_Factory::get('erp_Models_Order');
        /* @var $order erp_Models_Order */
        $this->addValidator('state', 'inArray', [$order->getAllStates()]);
        
        //`comments` text, // $this->addDontValidateField('comment')
        //$this->addValidator('comments', 'text');
        
        //`checked` tinyint(1) DEFAULT NULL,
        $this->addValidator('checked', 'int');
        
        //`checkerId` int(11) DEFAULT NULL,
        $this->addValidator('checkerId', 'int');
        
        //`checkerName` varchar(255) DEFAULT NULL,
        $this->addValidator('checkerName', 'stringLength', array('min' => 0, 'max' => 255));
        
        //`editorId` int(11) DEFAULT NULL,
        $this->addValidator('editorId', 'int');
        
        //`editorName` varchar(255) DEFAULT NULL,
        $this->addValidator('editorName', 'stringLength', array('min' => 0, 'max' => 255));
        
        //`isCustomerView` tinyint(1) DEFAULT NULL,
        $this->addValidator('isCustomerView', 'boolean');
    }
}
