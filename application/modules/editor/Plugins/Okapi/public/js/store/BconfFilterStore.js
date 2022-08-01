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
    /** @var {Map} */
    extensionMap: null,
    /** @var {Map} */
    identifierMap: null,
    /** @var {Map} */
    customIdentifierMap: null,
    /** @var {Array} */
    allExtensions: [],
    customizedFilter: {
        id: 'customizedFilter',
        filterFn: function(rec){
            return rec.data.isCustom;
        }
    },
    listeners: {
        'load': function(store, records, success, operation){
            if(success){
                store.identifierMap = new Map();
                store.customIdentifierMap = new Map();
                // generate identifier map for loaded items
                records.forEach(record => {
                    store.identifierMap.set(record.get('identifier'), record.id);
                    store.customIdentifierMap.set(record.get('identifier'), record.id);
                });
                var metadata = operation.getResultSet().getMetadata(),
                    defaultRecords = Ext.getStore('defaultBconfFilters').getRange();
                // add the records from the default store, add them to the map as well
                store.add(defaultRecords);
                defaultRecords.forEach(record => {
                    store.identifierMap.set(record.get('identifier'), record.id);
                });
                store.allExtensions = metadata.allExtensions;
                store.setExtensionMapping(metadata.extensionMapping);
            }
        }
    },
    initConfig: function(config){
        if(!config.filters){
            config.filters = [];
        }
        config.filters.push(this.customizedFilter); // Enable filter initially
        return this.callParent([config]);
    },
    /**
     * Retrieves a record by identifier via a cached map
     * @param {string} identifier
     * @returns {Editor.plugins.Okapi.model.BconfFilterModel|null}
     */
    getByIdentifier: function(identifier){
        if(this.identifierMap.has(identifier)){
            return this.getById(this.identifierMap.get(identifier));
        }
        return null;
    },
    /**
     * Retrieves all records independetly of filtering
     * @see https://forum.sencha.com/forum/showthread.php?310616
     * @returns {Ext.util.Collection }
     */
    getUnfilteredData: function(){
        return (this.isFiltered() || this.isSorted()) ? this.getData().getSource() : this.getData();
    },
    /**
     * Retrieves an item by name
     * @param {string} name
     * @returns {Editor.plugins.Okapi.model.BconfFilterModel|null}
     */
    findUnfilteredByName: function(name){
        return this.getUnfilteredData().find('name', name, 0, true, true, true);
    },
    /**
     * @param {object} identifierToExtensions: object of mapping-items: { identifier => [ extension ] }
     */
    setExtensionMapping: function(identifierToExtensions){
        var record, setSilent = { silent: true, dirty: false };
        this.extensionMap = new Map();
        // provide our items with the needed extensions
        this.identifierMap.forEach((id, identifier) => {
            record = this.getById(id);
            if(record) {
                if (identifierToExtensions.hasOwnProperty(identifier)) {
                    record.set('extensions', identifierToExtensions[identifier].sort(), setSilent);
                } else {
                    record.set('extensions', [], setSilent);
                }
            } else {
                console.log('ERROR: BconfFilterStore.setExtensionMapping: can not find record for identifier ', identifier);
            }
        });
        // generate the extension => identifier map
        for(var identifier in identifierToExtensions){
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
     * @param {boolean} isCustom
     */
    updateExtensionsByIdentifier: function(identifier, extensions, isCustom){
        var record,
            extBefore = [], // represents the extensions the changed item currently has
            customChanged = isCustom; // evaluates, if the custom extensions have been changed
        // first, remove all existing entries of the item (TODO: can we delete the extension here in the loop ? unclear ...
        this.extensionMap.forEach((filter, extension) => {
            if(filter === identifier){
                extBefore.push(extension);
            }
        });
        extBefore.forEach(extension => {
            this.extensionMap.delete(extension);
        });
        // then remove the extension from all items referenced in the map and create/change to the new identifier/extension
        extensions.forEach(extension => {
            if(this.extensionMap.has(extension)){
                record = this.getByIdentifier(this.extensionMap.get(extension));
                if(record){
                    record.removeExtension(extension, true);
                    record.commit();
                    if(!customChanged && record.get('isCustom')){
                        customChanged = true;
                    }
                } else {
                    console.log('ERROR: BconfFilterStore.updateExtensionsByIdentifier: can not find record for extension ' + extension + ' mapping to identifier ' + this.extensionMap[extension]);
                }
            }
            this.extensionMap.set(extension, identifier);
        });
        // finally, if the extensions of custom items changed we fire an according event
        if(customChanged){
            var bconfId, allCustomExts = [];
            // collect all custom extensions
            this.customIdentifierMap.forEach(id => {
                record = this.getById(id);
                if(record){
                    allCustomExts = allCustomExts.concat(record.get('extensions'));
                    bconfId = record.get('bconfId');
                }
            });
            // the bconf grid will listen and update a bconf accordingly
            this.fireEvent('customFilterExtensionsChanged', bconfId, allCustomExts);
        }
    },
    /**
     * Retrieves all exensions that shall be shown in the tagfield selector
     * @returns {[]}
     */
    getAllExtensions: function(){
        return this.allExtensions;
    }
});