/*
 START LICENSE AND COPYRIGHT

 This file is part of translate5

 Copyright (c) 2013 - 2022 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

 Contact:  http://www.MittagQI.com/  /  service (ATT) MittagQI.com

 This file may be used under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE version 3
 as published by the Free Software Foundation and appearing in the file agpl3-license.txt
 included in the packaging of this file.  Please review the following information
 to ensure the GNU AFFERO GENERAL PUBLIC LICENSE version 3 requirements will be met:
 http://www.gnu.org/licenses/agpl.html

 There is a plugin exception available for use with this release of translate5 for
 translate5: Please see http://www.translate5.net/plugin-exception.txt or
 plugin-exception.txt in the root folder of translate5.

 @copyright  Marc Mittag, MittagQI - Quality Informatics
 @author     MittagQI - Quality Informatics
 @license    GNU AFFERO GENERAL PUBLIC LICENSE version 3 with plugin-execption
 		     http://www.gnu.org/licenses/agpl.html http://www.translate5.net/plugin-exception.txt

 END LICENSE AND COPYRIGHT
 */

/**
 * Represents a pricing preset entry of the database. Model like
 * {
 *  "id": "20",
 *  "customerId": null,
 *  "name": "Translate5-Standard",
 *  "description": "'The default pricing preset. Copy to customize ranges and prices. Or go to "Clients" and customize ranges and prices there.'",
 *  "isDefault": "1"
 *  }
 */
Ext.define('Editor.plugins.MatchAnalysis.model.admin.pricing.PresetModel', {
    extend: 'Ext.data.Model',
    alias: 'model.pricingPresetModel',
    idProperty: 'id',
    proxy: {
        type: 'rest',
        url: Editor.data.restpath + 'plugins_matchanalysis_pricingpreset',
        reader: {
            rootProperty: 'rows',
            type : 'json'
        },
        writer: {
            encode: true,
            rootProperty: 'data',
        },
    },
    fields: [{
        name: 'id',
        type: 'int',
    }, {
        name: 'customerId',
        type: 'int',
        allowNull: true,
        reference: 'Editor.model.admin.Customer'
    }, {
        name: 'name',
        type: 'string',
        convert: v => Ext.String.htmlEncode(v)
    }, {
        name: 'description',
        type: 'string',
        convert: v => Ext.String.htmlEncode(v)
    }, {
        name: 'priceAdjustment',
        type: 'number'
    }, {
        name: 'isDefault', // global setting
        type: 'boolean'
    }, {
        name: 'isTqeDefault', // global TQE setting
        type: 'boolean',
        defaultValue: false
    }],
    toUrl: function(){
        return Ext.util.History.getToken().replace(/pricingPresets\/?\d*.*$/, 'pricingPresets/' + this.id)
    }
});
