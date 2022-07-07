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
 * Represents a bconf entry of the database or of the static default-bconf stores.
 * Note, that the default bconfs will have "virtual" ids that start 1000000 above the database based entries
 *
 * Model like
 * {
 *  "id": 1000061,
 *  "okapiType": "okf_openoffice",
 *  "okapiId": "translate5",
 *  "name": "t5 OpenOffice.org Documents",
 *  "description": "translate5 adjusted filter for OpenOffice.org documents",
 *  "mime": "application/x-openoffice",
 *  "editable": false,
 *  "clonable": true,
 *  "isCustom": false,
 *  "guiClass": ""
 * }
 * @link editor_Plugins_Okapi_Bconf_Filter_Entity::getGridRowsByBconfId}
 */

Ext.define('Editor.plugins.Okapi.model.BconfFilterModel', {
    extend: 'Ext.data.Model',
    requires: ['Editor.util.type.StringSet'],
    alias: 'model.bconffilterModel',
    idProperty: 'id',
    proxy: {
        type: 'rest',
        url: Editor.data.restpath + 'plugins_okapi_bconffilter',
        reader: {
            rootProperty: 'rows',
        },
        writer: {
            encode: true,
            rootProperty: 'data',
            writeRecordId: false
        },
        api: {
            read: undefined /** @link self.proxy.setBconfId sets this for easy filtering */
        },
        bconfId: null,
        /**
         * Sets the id of the currently opened bconf to
         * - the defaultValue of the bconfId field
         * - the read api param
         * @see Editor.plugins.Okapi.view.BconfFilterGrid.initComponent
         */
        setBconfId: function(bconfId){
            var proxy = this;
            proxy.getModel().getField('bconfId').defaultValue = proxy.bconfId = bconfId;
            proxy.api.read = proxy.getUrl() + '?bconfId=' + bconfId;
        },
    },
    fields: [{
        name: 'id',
        type: 'int'
    }, {
        name: 'bconfId',
        type: 'int',
        reference: 'Editor.plugins.Okapi.model.BconfModel',
        critical: true,
        defaultValue: 0 /** @see self.proxy.setBconfId */
    }, {
        name: 'okapiType',
        type: 'string',
        persist: false
    }, {
        name: 'okapiId',
        type: 'string',
        persist: false
    }, {
        name: 'name',
        type: 'string',
        persist: true
    }, {
        name: 'description',
        type: 'string',
        persist: true
    }, {
        name: 'mimeType',
        type: 'string',
        persist: true
    }, {
        /* the identifier is unique and is used e.g. to connect the extension-mapping with the store */
        name: 'identifier',
        type: 'string',
        critical: true
    }, {
        name: 'editable',
        type: 'bool',
        defaultValue: false,
        persist: false
    }, {
        name: 'clonable',
        type: 'bool',
        defaultValue: false,
        persist: false
    }, {
        name: 'isCustom',
        type: 'bool',
        defaultValue: true,
        persist: false
    }, {
        name: 'guiClass',
        type: 'string',
        defaultValue: '',
        persist: false
    },{
        name: 'extensions',
        type: 'auto',
        defaultValue: [],
        convert: function (value) {
            if(Array.isArray(value)){
                return value.sort();
            }
            return String(value).split(',').sort();
        },
        serialize: function(value){
            return value.join(',');
        },
        persist: true
    }],
    isValid: function(){
        return this.get('isCustom') && // don't save default filters
            (this.get('bconfId') > 0); // don't save unknow filters from extensions-mapping
    },
    /**
     * Return a displayname for the given id
     * @return {string} The filterName with the filterId in parantheses behind
     */
    getDisplayName(){
        return this.get('name') + '&nbsp;(' + this.get('identifier') + ')';
    },
    /**
     * Removes an extension, returns if it was found
     * @param {string} extension
     * @param {boolean} silent
     * @returns {boolean}
     */
    removeExtension: function(extension, silent = false){
        var extensions = this.get('extensions'),
            index = extensions.indexOf(extension);
        if(index > -1){
            extensions = extensions.splice(index, 1);
            if(silent){
                this.set('extensions', extensions, { silent: true, dirty: false });
            } else {
                this.set('extensions', extensions);
            }
            return true;
        }
        return false;
    },

    loadFprm(){
        var me = this;
        return new Promise(function(resolve, reject){
            Ext.Ajax.request({
                url: me.getProxy().getUrl() + '/getfprm',
                params: {
                    id: me.id
                },
                callback: function(options, success, response){
                    if(success){
                        resolve(response.responseText);
                    } else {
                        reject();
                        Editor.app.getController('ServerException').handleException(response);
                    }
                }
            });
        });
    },

    saveFprm(fprm){
        var id = this.id;
        return Ext.Ajax.request({
            url: this.getProxy().getUrl() + '/savefprm',
            headers: {'Content-Type': 'application/octet-stream'},
            params: {id},
            rawData: fprm,
            failure: function(options, response){
                Editor.app.getController('ServerException').handleException(response);
            }
        });
    }
});
