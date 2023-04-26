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
 * Store for the pricing presets of the translate5 installation
 *
 * @extends Ext.data.Store
 */
Ext.define('Editor.plugins.MatchAnalysis.store.admin.pricing.PresetStore', {
    extend: 'Ext.data.Store',
    requires: ['Editor.plugins.MatchAnalysis.model.admin.pricing.PresetModel'],
    storeId: 'pricingPresetStore',
    model: 'Editor.plugins.MatchAnalysis.model.admin.pricing.PresetModel',
    autoLoad: true,
    autoSync: false,
    pageSize: 0,

    /**
     * Retrieves all records independently of filtering
     *
     * @see https://forum.sencha.com/forum/showthread.php?310616
     * @returns {Ext.util.Collection}
     */
    getUnfilteredData: function(){
        return (this.isFiltered() || this.isSorted()) ? this.getData().getSource() : this.getData();
    },

    /**
     * Retrieves an item by name
     *
     * @param {string} name
     * @returns {Editor.plugins.Okapi.model.BconfModel|null}
     */
    findUnfilteredByName: function(name){
        return this.getUnfilteredData().find('name', name, 0, true, true, true);
    },

    /**
     * Creates the data-source for the import wizard
     * This store will only contain the customers pricing presets or the presets bound to no customer
     * The store also has a property "selectedId" that will define the initial selected item
     * 
     * @param {int} customerId
     * @param {int} customerDefaultPricingPresetId
     * @returns {Ext.data.Store}
     */
    createImportWizardSelectionData(customerId, customerDefaultPricingPresetId){
        var cid, item, items = [], defaultName = 'UNDEFINED';
        this.getUnfilteredData().each(record => {
            cid = record.get('customerId');
            if (cid === null || cid === customerId) {
                item = {
                    'id': record.id,
                    'name': record.get('name'),
                    'description': record.get('description'),
                    'cid': (cid === null ? 0 : cid),
                    'priceAdjustment': record.get('priceAdjustment'),
                    'unitType': record.get('unitType')
                };
                // if no customer specific default given we look for the system default (which can only be attached to a record with id 'null'
                if (!customerDefaultPricingPresetId && record.get('isDefault')) {
                    customerDefaultPricingPresetId = item.id;
                    defaultName = item.name;
                } else if (customerDefaultPricingPresetId && record.id === customerDefaultPricingPresetId) {
                    defaultName = item.name;
                }
                items.push(item);
            }
            return true;
        });
        return Ext.create('Ext.data.Store', {
            fields: ['id', 'name', 'cid', 'priceAdjustment', 'unitType'],
            sorters: [
                { 'property': 'cid', 'direction': 'DESC' },
                { 'property': 'name', 'direction': 'ASC' }
            ],
            data : items,
            selectedId: customerDefaultPricingPresetId
        });
    }
});