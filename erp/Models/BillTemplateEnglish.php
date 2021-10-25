<?php
/*
 START LICENSE AND COPYRIGHT

This file is part of ZfExtended library

Copyright (c) 2013 - 2015 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

Contact:  http://www.MittagQI.com/  /  service (ATT) MittagQI.com

This file may be used under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE version 3
as published by the Free Software Foundation and appearing in the file agpl3-license.txt
included in the packaging of this file.  Please review the following information
to ensure the GNU AFFERO GENERAL PUBLIC LICENSE version 3.0 requirements will be met:
http://www.gnu.org/licenses/agpl.html

There is a plugin exception available for use with this release of translate5 for
open source applications that are distributed under a license other than AGPL:
Please see Open Source License Exception for Development of Plugins for translate5
http://www.translate5.net/plugin-exception.txt or as plugin-exception.txt in the root
folder of translate5.

@copyright  Marc Mittag, MittagQI - Quality Informatics
@author     MittagQI - Quality Informatics
@license    GNU AFFERO GENERAL PUBLIC LICENSE version 3 with plugin-execptions
http://www.gnu.org/licenses/agpl.html http://www.translate5.net/plugin-exception.txt

END LICENSE AND COPYRIGHT
*/


class erp_Models_BillTemplateEnglish extends erp_Models_BillTemplate{

    /*
        translations used key/values used in po pdf layout
    */
    private $translations = array(
        'title'=>'Purchase Order',
        'pmName'=>'Project Manager:',
        'vendorCurrency'=>'Currency:',
        'creationDate'=>'Date:',
        'orderId'=>'Project Number:',
        'poNumber'=>'PO Number:',
        'projectName'=>'Project Name:',
        'vendorNumber'=>'Vendor Number:',
        'tblDescription'=>'Description',
        'tblAmount'=>'Amount',
        'tblUnit'=>'Unit',
        'tblPrice'=>'Unit Price',
        'tblSubTotal'=>'Sub-Total',
        'total'=>'Total:',
        'additionalInfo'=>'Further Information:',
        'transmissionPath'=>'Method of Delivery:',
        'deliveryDate'=>'Delivery Date:',
        'termsOfPayment'=>'Payment Terms:',
        'infoText'=>'Please refer to the above project reference numbers in your invoice. Please send your invoice to invoice@tmuebersetzungen.de',
        'unit'=>'Hours'
    );

    private $salutation = array(
      '1'=>'Mr.',
      '2'=>'Ms.',
      '3'=>'Mr. and Ms.',
      '4'=>'Ladies and Gentlemen,'
    );
    
    /**
     * Shift dirty field icon by x and y.
     *
     * @var array
     */
    private $fieldsIconShift = array(
            'pmId'=>array(
                    'x'=>0,
                    'y'=>-1
            ),
            'additionalInfo'=>array(
                    'x'=>0,
                    'y'=>4
            ),
            'transmissionPath'=>array(
                    'x'=>0,
                    'y'=>-1
            ),
            'deliveryDate'=>array(
                    'x'=>0,
                    'y'=>-1
            )
    );
    

    public function __construct(){
        parent::__construct();
    }

    public function init(){
        parent::init();
        $this->translationKeys= $this->translations;
        $this->salutationKeys =$this->salutation;
        $this->dirtyFieldsIconShift = $this->fieldsIconShift;
    }
}