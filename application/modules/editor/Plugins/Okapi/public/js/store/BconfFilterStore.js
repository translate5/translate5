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
 * Store for the so called BconfFilters aka fprms inside a Bconf
 * @property {Map} extensionMap Holds the information of extensions-mapping.txt inside a Bconf
 * @extends Ext.data.Store
 */

Ext.define('Editor.plugins.Okapi.store.BconfFilterStore', {
    extend: 'Ext.data.Store',
    requires: [
        'Editor.plugins.Okapi.store.DefaultBconfFilterStore',
    ], // for Okapi and Translate5 filters
    storeId: 'bconffilterStore',
    alias: 'store.bconffilterStore',
    model: 'Editor.plugins.Okapi.model.BconfFilterModel',
    autoLoad: true,
    autoSync: false, // Needed to edit the name before saving!
    pageSize: 0,
    extensionMap: null,
    identifierMap: {},
    allExtensions: [],
    defaultsFilter: {
        id: 'defaultsFilter',
        filterFn: function(rec){
            return rec.data.isCustom;
        }
    },
    listeners: {
        'load': function(store, records, success, operation){
            if(success){
                // generate identifier map for loaded items
                records.forEach(record => {
                    store.identifierMap[record.get('identifier')] = record.id;
                });
                var metadata = operation.getResultSet().getMetadata(),
                    defaultRecords = Ext.getStore('defaultBconfFilters').getRange();
                // add the records from the default store, add them to the map as well
                store.add(defaultRecords);
                defaultRecords.forEach(record => {
                    store.identifierMap[record.get('identifier')] = record.id;
                });
                store.allExtensions = metadata.allExtensions;
                store.setExtensionMapping(metadata.extensionMapping);
            }
        }
    },
    /**
     * Retrieves a record by identifier via a cached map
     * @param {string} identifier
     * @returns {Editor.plugins.Okapi.model.BconfFilterModel|null}
     */
    getByIdentifier: function(identifier){
        if(this.identifierMap.hasOwnProperty(identifier)){
            this.getById(this.identifierMap[identifier]);
        }
        return null;
    },
    initConfig: function(config){
        if(!config.filters){
            config.filters = [];
        }
        config.filters.push(this.defaultsFilter); // Enable filter initially
        return this.callParent([config]);
    },
    /**
     * @param {object} identifierToExtensions: object of mapping-items: { identifier => [ extension ] }
     */
    setExtensionMapping: function(identifierToExtensions){
        var identifier, record, setSilent = { silent: true, dirty: false };
        this.extensionMap = new Map();
        // provide our items with the needed extensions
        for(identifier in this.identifierMap){
            record = this.getById(this.identifierMap[identifier]);
            if(identifierToExtensions.hasOwnProperty(identifier)){
                record.set('extensions', identifierToExtensions[identifier].sort(), setSilent);
            } else {
                record.set('extensions', [], setSilent);
            }
        }
        // generate the extension => identifier map
        for(identifier in identifierToExtensions){
            identifierToExtensions[identifier].forEach(extension => {
                this.extensionMap.set(extension, identifier);
            });
        }
    },
    /**
     * Updates all maps and removes the passed extensions from all records not having the passed identifier silently
     * It is assumed, that the record of the identifier is updated in the calling code
     * @param {string} identifier
     * @param {Array} extensions
     */
    updateExtensionsByIdentifier(identifier, extensions){
        console.log('FilterStore: updateExtensionsByIdentifier:', identifier, extensions);
        var item;
        // first, remove all existing entries of the item
        this.extensionMap.forEach((filter, extension) => {
            if(filter === identifier){
                delete this.extensionMap[extension];
            }
        });
        // then remove the extension from all items referenced in the map and create/change to the new identifier/extension
        extensions.forEach(extension => {
            if(this.extensionMap.hasOwnProperty(extension)){
                item = this.getByIdentifier(this.extensionMap[extension]);
                if(item){
                    item.removeExtension(extension, true);
                }
            }
            this.extensionMap[extension] = identifier;
        });
    },
    /**
     * Retrieves all exensions that shall be shown in the tagfield selector
     * @returns {[]}
     */
    getAllExtensions(){
        return this.allExtensions;
    },
    /**
     * Creates the extension mapping to be sent back to the store
     */
    createExtensionMappingData: function(){
        var identifierMap = {};
        this.extensionMap.forEach((identifier, extension) => {
            if(identifierMap.hasOwnProperty(identifier)){
                identifierMap[identifier].push(extension);
            } else {
                identifierMap[identifier] = [ extension ];
            }
        });
        return JSON.stringify(identifierMap);
    },
    /**
     * syncs the extension-mapping to the backend
     */
    saveExtensionMapping: function(){
        var me = this;
        Ext.Ajax.request({
            url: Editor.data.restpath + 'plugins_okapi_bconf/saveextensionsmapping',
            params: {
                id: me.getProxy().bconfId
            },
            rawData: me.createExtensionMappingData()
        });
    }
});