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
        type: 'int',
    }, {
        name: 'bconfId',
        type: 'int',
        reference: 'Editor.plugins.Okapi.model.BconfModel',
        critical: true,
        defaultValue: 0 /** @see self.proxy.setBconfId */
    }, {
        name: 'okapiType',
        type: 'string',
    }, {
        name: 'okapiId',
        type: 'string',
    }, {
        name: 'name',
        type: 'string',
    }, {
        name: 'description',
        type: 'string',
    }, {
        name: 'mimeType',
        type: 'string',
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
    },
    /**
     * @readonly This models a 1:n relation. To manipulate, retrieve value via .get() and use
     * @see StringSet
     * @property {bool} unchanged Flag that shows if extensions were changed during an edit
     * @link BconfFilterGridController.prepareFilterEdit,saveEdit,cancelEdit
    */
    {
        name: 'extensions',
        persist: true, // Normal saving causes many requests
        /**
         * Only allow initializing, changes will be handled by
         * Must always create new Set for change detection
         * @see Editor.plugins.Okapi.model.BconfFilterModel.addExtension
         * @see Editor.plugins.Okapi.model.BconfFilterModel.removeExtension
         * @return {StringSet}
         */
        convert: function(v, rec){ // null is passed after saving the record
            if(v && v.op){ // Special handler with op key
                var set = new StringSet(rec.data[this.name]);
                set[v.op](v.extension);
                return set;
            } else {
                var array = Ext.isString(v) ? v.split(/[,.\s]+/) : Ext.Array.from(v);
                return new StringSet(array);
            }
        },
        serialize: function(v){
            return v.toString();
        },
        isEqual: function(a, b){
            return a.toString() === b.toString();
        },
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
        return this.get('name') + '&nbsp;(' + this.id + ')';
    },

    /**
     * Add Extension to this filter. If it belonged to another filter before, remove from there.
     * @param {String} extension
     * @param {Editor.plugins.Okapi.model.BconfFilterModel} from Record where to take the extension from#
     * @param {Boolean} isRevert Inidcates if to set dirty or not
     */
    addExtension: function(extension, from, isRevert = false, showMsg = true){
        var extMap = this.store.extMap,
            filters = Editor.util.Util.getUnfiltered(this.store),
            msg = `Added extension <i>${extension}</i>`;

        from = (from !== undefined) ? from : filters.getByKey(extMap.get(extension));
        if(from){
            from.removeExtension(extension, null, isRevert, !showMsg);
            msg += ` from '${from.get('name')}'`;
        }
        this.set('extensions', {op: 'add', extension}, {dirty: !isRevert});
        extMap.set(extension, this.id);

        if(showMsg){
            Editor.MessageBox.addInfo(msg, 2);
        }
        if(from){
            return from;
        }
    },
    /**
     * Remove extension this filter. If it belonged to another filter before, remove from there.
     * @param {String} extension
     * @param {Editor.plugins.Okapi.model.BconfFilterModel} to
     */
    removeExtension: function(extension, to, isRevert, showMsg = true){
        var msg = `Removed extension <i>${extension}</i>`
        this.set('extensions', {op: 'delete', extension}, {dirty: !isRevert});

        this.extMap.delete(extension);
        // TODO: defaults 'to' receiver based on current system default (via global varaible?)
        if(to){
            to.addExtension(extension, null, isRevert, !showMsg);
            msg += ` and added to '${to.get('name')}' `;
        }
        if(showMsg){
            Editor.MessageBox.addInfo(msg, 2);
        }
        return to === null ? undefined : to;
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
                        resolve(response.responseText)
                    } else {
                        reject();
                        Editor.app.getController('ServerException').handleException(response);
                    }
                }
            })
        })
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
