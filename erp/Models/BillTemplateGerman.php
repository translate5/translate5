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


class erp_Models_BillTemplateGerman extends erp_Models_BillTemplate{

    /*
        translations used key/values used in po pdf layout
     */
    private $translations = array(
        'title'=>'Auftragsbestätigung / PO',
        'pmName'=>'Projektmanager:',
        'vendorCurrency'=>'Währungsschlüssel:',
        'creationDate'=>'Datum:',
        'orderId'=>'Projektnummer:',
        'poNumber'=>'Bestellnummer:',
        'projectName'=>'Projektname:',
        'vendorNumber'=>'Lieferantennr.:',
        'tblDescription'=>'Leistung',
        'tblAmount'=>'Menge',
        'tblUnit'=>'Einheit',
        'tblPrice'=>'Bestellpreis',
        'tblSubTotal'=>'Summe',
        'total'=>'Gesamtbetrag/Bestellung:',
        'additionalInfo'=>'Weitere Infos:',
        'transmissionPath'=>'Übertragungsweg:',
        'deliveryDate'=>'Liefertermin:',
        'termsOfPayment'=>'Zahlungsbedingungen:',
        'infoText'=>'Bitte geben Sie auf Ihrer Rechnung die obigen Projektdaten an. Senden Sie Ihre Rechnungen bitte an invoice@tmuebersetzungen.de',
        'unit'=>'Stunden'
    );

    private $salutation = array(
        1=>'Herr',
        2=>'Frau',
        3=>'Herr und Frau',
        4=>'Sehr geehrte Damen und Herren,'
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
        $this->isGermanTemplate=true;
    }
}