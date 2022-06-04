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
Ext.define('Editor.plugins.Okapi.model.BconffilterModel', {
    extend: 'Ext.data.Model',
    requires: ['Editor.util.type.StringSet'],
    alias: 'model.bconffilterModel',
    getId: function(){
        var bconfId = this.get('bconfId'),
            okapiId = this.get('okapiId');
        return `${bconfId}-.-${okapiId}`; // Slash / as separator will lead to 404
    },
    proxy: {
        idParam: 'id',
        type: 'rest',
        url: Editor.data.restpath + 'plugins_okapi_bconffilter',
        reader: {
            rootProperty: 'rows',
        },
        writer: {
            encode: true,
            rootProperty: 'data',
        },
        api: {
            read: undefined /** @link self.proxy.setBconfId sets this for easy filtering */
        },
        /**
         * Sets the id of the currently opened bconf to
         * - the defaultValue of the bconfId field
         * - the read api param
         * @see Editor.plugins.Okapi.view.BconffilterGrid.initComponent
         */
        setBconfId: function(bconfId){
            var proxy = this;
            proxy.getModel().getField('bconfId').defaultValue = proxy.bconfId = bconfId;
            proxy.api.read = proxy.getUrl() + '?bconfId=' + bconfId
        },
    },
    idProperty: 'okapiId',
    fields: [{
        name: 'okapiId',
        type: 'string',
    }, {
        name: 'bconfId',
        type: 'int',
        //reference: 'bconfmodel', // leads to fiels being null
        critical: true,
        defaultValue: 0 /** @see self.proxy.setBconfId */
    }, {
        name: 'isCustom',
        type: 'bool',
        defaultValue: true,
        persist: false
    }, {
        name: 'description',
        type: 'string'
    },
        /**
         * @readonly This models a 1:n relation. To manipulate, retrieve value via .get() and use
         * @see StringSet
         * @property {bool} unchanged Flag that shows if extensions were changed during an edit
         * @link BconffilterGridController.prepareFilterEdit,saveEdit,cancelEdit
         */
        {
            name: 'extensions',
            persist: true, // Normal saving causes many requests
            /**
             * Only allow initializing, changes will be handled by
             * Must always create new Set for change detection
             * @see Editor.plugins.Okapi.model.BconffilterModel.addExtension
             * @see Editor.plugins.Okapi.model.BconffilterModel.removeExtension
             * @return {StringSet}
             */
            convert: function(v, rec){ // null is passed after saving the record
                if(v && v.op){ // Special handler with op key
                    var set = new StringSet(rec.data[this.name])
                    set[v.op](v.extension)
                    return set
                } else {
                    var array = Ext.isString(v) ? v.split(/[,\.\s]+/) : Ext.Array.from(v)
                    return new StringSet(array);
                }
            },
            serialize: function(v){
                return v.toString();
            },
            isEqual: function(a, b){
                return a.toString() === b.toString();
            },

        },
    ],
    isValid: function(){
        return this.get('isCustom') // don't save default filters
            && this.get('bconfId') > 0; // don't save unknow filters from extensions-mapping
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
     * @param {Editor.plugins.Okapi.model.BconffilterModel} from Record where to take the extension from#
     * @param {Boolean} isRevert Inidcates if to set dirty or not
     */
    addExtension: function(extension, from, isRevert = false){
        var extMap = this.store.extMap,
            filters = Editor.util.Util.getUnfiltered(this.store);

        from = (from !== undefined) ? from : filters.getByKey(extMap.get(extension))
        if(from){
            from.removeExtension(extension, null, isRevert);
        }
        this.set('extensions', {op: 'add', extension}, {dirty: !isRevert})
        extMap.set(extension, this.id)

        if(from){
            return from
        }
    },
    /**
     * Remove extension this filter. If it belonged to another filter before, remove from there.
     * @param {String} extension
     * @param {Editor.plugins.Okapi.model.BconffilterModel} to
     */
    removeExtension: function(extension, to, isRevert){
        var filters = Editor.util.Util.getUnfiltered(this.store);

        this.set('extensions', {op: 'delete', extension}, {dirty: !isRevert})
        this.extMap.delete(extension)

        // TODO: defaults 'to' receiver based on current system default (via global varaible?)
        if(to){
            to.addExtension(extension, null, isRevert);
            return to
        }
    },
});
