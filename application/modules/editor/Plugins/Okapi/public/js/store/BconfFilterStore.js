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
 * @property {Map} extMap Holds the information of extensions-mapping.txt inside a Bconf
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
    extMap: null,
    defaultsFilter: {
        id: 'defaultsFilter',
        filterFn: function(rec){return rec.data.isCustom},
    },
    initConfig: function(config){
        if(!config.filters){
            config.filters = [];
        }
        config.filters.push(this.defaultsFilter); // Enable filter initially
        return this.callParent([config]);
    },
    /**
     * @param extMapString Consist of lines like '.txt\ttokf_plaintext'
     */
    setExtMapString: function(extMapString){
        var store = this,
            matches = Array.from(extMapString.matchAll(/^\.(.*)\t(.*)$/gm), match => match.slice(1, 3)).sort(),
            bconffilters = Editor.util.Util.getUnfiltered(store),
            extMap = new Map();
        bconffilters.items.forEach(f => f.get('extensions').clear())
        matches.forEach(function([extension, okapiId]){
            var filter = bconffilters.getByKey(okapiId);
            if(filter){
                filter.get('extensions').add(extension);
            } else { // okapiId in extensions-mapping not contained in Bconf
                store.add([{
                    bconfId: 0,
                    okapiId,
                    name: '<i>#UT#Unknown filter</i>',
                    description: '#UT#Unknown filter from extensions-mapping.txt',
                }])[0].get('extensions').add(extension)
            }
            extMap.set(extension, okapiId);
        });
        // Set on model for easy retrieval, as default BconfFilters are bound to DefaultBconfFilterStore
        // @see https://docs.sencha.com/extjs/6.2.0/classic/Ext.data.Model.html#property-store
        store.getModel().prototype.extMap = extMap;
        store.extMap = extMap;
    },

    saveExtMap: function(){
        var store = this;
        return new Promise(function(resolve, reject){
            Ext.Ajax.request({
                url: Editor.data.restpath + 'plugins_okapi_bconffilter/saveextensionsmapping',
                headers: {
                    'Content-Type': 'text/tab-separated-values'
                },
                params: {
                    bconfId: store.getProxy().bconfId
                },
                rawData: Array.from(store.extMap.entries()).sort()
                    .map(ext_okapiId => '.' + ext_okapiId.join('\t'))
                    .join('\n')
            }).then(resolve, res => Editor.app.getController('ServerException').handleException(res))
        });
    }
});