/*
START LICENSE AND COPYRIGHT

 This file is part of translate5
 
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

/**
 * @class Erp.view.purchaseOrders.FormFields
 */

Ext.define('Erp.view.purchaseOrders.FormFields', {

    fieldsConfig:[{
        xtype: 'datefield',
        fieldLabel: 'Lieferdatum',
        name: 'deliveryDate'
    },{
        xtype: 'displayfield',
        fieldLabel: 'Auftragsnummer',
        name: 'orderId'
    },{
        xtype: 'displayfield',
        fieldLabel: 'PO Nr.',
        name: 'number'
    },{
        xtype: 'numberfieldcustom',
        fieldLabel: 'Gewichtete Wörter',
        name: 'wordsCount',
        allowDecimals:false,
        step: 10,
        minValue:1,
        cls:'numberfieldcustom numberfieldcustomtextalign',
        listeners:{
            change:'onWordsCountChange'
        }
    },{
        xtype: 'numberfieldcustom',
        fieldLabel: 'Wortpreis',
        name: 'perWordPrice',
        decimalPrecision:4,
        useCustomPrecision:true,
        cls:'numberfieldcustom numberfieldcustomtextalign',
        listeners:{
            change:'onPerWordPriceChange'
        }
    },{
        xtype: 'textfield',
        fieldLabel: 'Beschreibung Wörter',
        name: 'wordsDescription',
        maxLength:40,
        listeners:{
            change:'onWordsDescriptionChange'
        }
    },{
        xtype: 'numberfieldcustom',
        fieldLabel: 'Stunden',
        name: 'hoursCount',
        step: 0.5,
        minValue:0.1,
        cls:'numberfieldcustom numberfieldcustomtextalign',
        listeners:{
            change:'onHoursCountChange'
        }
    },{
        xtype: 'numberfieldcustom',
        fieldLabel: 'Stundenpreis',
        name: 'perHourPrice',
        decimalPrecision:2,
        useCustomPrecision:true,
        cls:'numberfieldcustom numberfieldcustomtextalign',
        listeners:{
            change:'onPerHourPriceChange'
        }
    },{
        xtype: 'textfield',
        fieldLabel: 'Beschreibung Stunden',
        name: 'hoursDescription',
        maxLength:40,
        listeners:{
            change:'onHoursDescriptionChange'
        }
    },{
        xtype: 'numberfieldcustom',
        fieldLabel: 'Zusatzposition Menge',
        name: 'additionalCount',
        step: 0.5,
        minValue:0.1,
        cls:'numberfieldcustom numberfieldcustomtextalign',
        listeners:{
            change:'onAdditionalCountChange'  
        }
    },{
        xtype: 'textfield',
        fieldLabel: 'Beschreibung Zusatzposition',
        name: 'additionalDescription',
        maxLength:40
    },{
        xtype: 'textfield',
        fieldLabel: 'Zusatzposition Einheit',
        name: 'additionalUnit'
    },{
        xtype: 'numberfieldcustom',
        fieldLabel: 'Zusatzposition Einzelpreis',
        name: 'perAdditionalUnitPrice',
        cls:'numberfieldcustom numberfieldcustomtextalign',
        decimalPrecision:3,
        useCustomPrecision:true,
        listeners:{
            change:'onPerAdditionalUnitPriceChange'  
        }
    },{
        xtype: 'numberfieldcustom',
        fieldLabel: 'Zusatzposition Bestellpreis',
        name: 'additionalPrice',
        step: 10,
        minValue:0.1,
        cls:'numberfieldcustom numberfieldcustomtextalign'
    },{
        xtype: 'combobox',
        fieldLabel: 'Übertragungsweg',
        name: 'transmissionPath',
        anyMatch: true,
        queryMode: 'local',
        store:Erp.data.transmissionPath
    },{
        xtype: 'textareafield',
        grow: true,
        name: 'additionalInfo',
        maxLength:254, 
        stripCharsRe: /[\r\n]/,//disable new line (because of fixed size of the field in pdf layout)
        fieldLabel: 'Weitere Infos'
    }],


    getPurchaseOrdersFieldConfig:function(fieldName,merge){
        for (var i = 0; i < this.fieldsConfig.length; i++) {
            if (this.fieldsConfig[i]['name'] === fieldName) {
                return merge ? Ext.Object.merge(this.fieldsConfig[i],merge) : this.fieldsConfig[i];
            }
        }
        return null;
    }
});