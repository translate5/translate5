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

class erp_Models_Validator_Comment extends ZfExtended_Models_Validator_Abstract {
    
    /**
    * Validators for Order- and PurchaseOrder-Comment Entity
    */
    protected function defineValidators() {
        
        //`id` int(11) NOT NULL AUTO_INCREMENT,
        $this->addValidator('id', 'int');
        
        // special for OrderComments
        //`orderId` int(11) NOT NULL,
        $this->addValidator('orderId', 'int');
        
        // special for PurchaseOrderComments
        //`purchaseOrderId` int(11) NOT NULL,
        $this->addValidator('purchaseOrderId', 'int');
        
        //`userId` int(11) NOT NULL,
        $this->addValidator('userId', 'int');
        
        //`userName` varchar(255) NOT NULL DEFAULT '',
        $this->addValidator('userName', 'stringLength', array('min' => 3, 'max' => 255));
        
        //`comment` text NOT NULL,
        $this->addValidator('comment', 'stringLength', array('min' => 1));
        
        //`created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
        $this->addValidator('created', 'date', array('Y-m-d H:i:s'));
        
        //`modified` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
        $this->addValidator('modified', 'date', array('Y-m-d H:i:s'));
    }
}
