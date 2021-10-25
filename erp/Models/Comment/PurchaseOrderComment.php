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
 * ERP-PurchaseOrderComment Entity Objekt
 * 
 * @method integer getId() getId()
 * @method void setId() setId(integer $id)
 * 
 * @method integer getPurchaseOrderId() getPurchaseOrderId()
 * @method void setPurchaseOrderId() setPurchaseOrderId(integer $id)
 * 
 * @method integer getUserId() getUserId()
 * @method void setUserId() setUserId(integer $id)
 * 
 * @method integer getUserName() getUserName()
 * @method void setUserName() setUserName(string $name)
 * 
 * @method integer getComment() getComment()
 * @method void setComment() setComment(string $comment)
 * 
 * @method integer getCreated() getCreated()
 * @method void setCreated() setCreated(string $date)
 * 
 * @method integer getModified() getModified()
 * @method void setModified() setModified(string $date)
 * 
*/
class erp_Models_Comment_PurchaseOrderComment extends erp_Models_Comment_Abstract {
    protected $dbInstanceClass = 'erp_Models_Db_PurchaseOrderComment';
    protected $validatorInstanceClass   = 'erp_Models_Validator_Comment';
    
    /**
    * updates the order comments field by merging all comments to the order, and apply HTML markup to each comment
    * @param integer $orderId
    */
    public function updatePurchaseOrder($purchaseOrderId) {
        $purchaseOrder = ZfExtended_Factory::get('erp_Models_PurchaseOrder');
        /* @var $purchaseOrder erp_Models_PurchaseOrder */
        $purchaseOrder->load($purchaseOrderId);
        
        $comments = $this->loadByForeignId($purchaseOrderId);
        
        $purchaseOrder->setComments($this->getMarkedUp($comments));
        $purchaseOrder->save();
    }
    
    /**
     * adds the purchaseOrder id filter to the comment is editable check
     * (non-PHPdoc)
     * @see erp_Models_Comment_Abstract::whereForeignId()
     */
    protected function whereForeignId(Zend_Db_Select $select) {
        return $select->where('purchaseOrderId = ?', $this->getPurchaseOrderId());
    }
    
    /**
     * (non-PHPdoc)
     * @see erp_Models_Comment_Abstract::getForeignId()
     */
    public function getForeignId() {
        return $this->getPurchaseOrderId();
    }
}
