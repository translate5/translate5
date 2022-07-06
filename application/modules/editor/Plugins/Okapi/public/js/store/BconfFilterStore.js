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
    defaultsFilter: {
        id: 'defaultsFilter',
        filterFn: function(rec){
            return rec.data.isCustom;
        }
    },
    initConfig: function(config){
        if(!config.filters){
            config.filters = [];
        }
        config.filters.push(this.defaultsFilter); // Enable filter initially
        return this.callParent([config]);
    },
    /**
     * @param extensionMapping: array of mapping-items: { identifier => [ extension ] }
     */
    setExtensionMapping: function(extensionMappingData){
        var me = this,
            storeItems = Editor.util.Util.getUnfiltered(this),
            identifierMap = JSON.parse(extensionMappingData),
            silent = { silent: true, dirty: false },
            identifier;
        me.extensionMap = new Map();

        // provide our items with the needed extensions
        storeItems.each(function(item){
            identifier = item.get('identifier');
            if(identifierMap.hasOwnProperty(identifier)){
                item.set('extensions', identifierMap[identifier], silent);
            } else {
                item.set('extensions', [], silent);
            }
        });
        // generate the extension => identifier map
        for(identifier in identifierMap){
            identifierMap[identifier].forEach(extension => {
                me.extensionMap.set(extension, identifier);
            });
        }
        // Set on model for easy retrieval, as default BconfFilters are bound to DefaultBconfFilterStore
        // @see https://docs.sencha.com/extjs/6.2.0/classic/Ext.data.Model.html#property-store
        this.getModel().prototype.extensionMap = me.extensionMap;
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
        // TODO REMOVE
        console.log('createExtensionMappingData: ', JSON.stringify(identifierMap));

        return JSON.stringify(identifierMap);
    },

    saveExtensionMapping: function(){
        var me = this;
        return new Promise(function(resolve, reject){
            Ext.Ajax.request({
                url: Editor.data.restpath + 'plugins_okapi_bconf/saveextensionsmapping',
                headers: {
                    'Content-Type': 'text/tab-separated-values'
                },
                params: {
                    id: me.getProxy().bconfId
                },
                rawData: me.createExtensionMappingData()
            }).then(resolve, res => Editor.app.getController('ServerException').handleException(res));
        });
    }
});