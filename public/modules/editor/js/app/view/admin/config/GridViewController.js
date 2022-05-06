
/*
START LICENSE AND COPYRIGHT

 This file is part of translate5
 
 Copyright (c) 2013 - 2021 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

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

Ext.define('Editor.view.admin.config.GridViewController', {
    extend: 'Ext.app.ViewController',
    alias: 'controller.adminConfigGrid',

    routes: {
        'config/:name' :'filterConfigByRoute',
    },

    listen:{
        component:{
            '#searchField':{
                change:'onSearchFieldChange'
            },
            '#showReadOnly':{
                change:'onShowReadOnlyChange'
            }
        },
        controller: {
            'taskGrid': {
                taskImportFinished: 'onTaskImportFinished'
            }
        },
        store: {
            '#admin.Config':{
                load:'onStoreLoad'
            }
        }
    },
    
    /**
     * @cfg {String} matchCls
     * The matched string css classe.
     */
    matchCls: 'x-livesearch-match',
    
    // detects html tag
    tagsRe: /<[^>]*>/gm,
    
    // DEL ASCII code
    tagsProtect: '\x0f',
    
    init: function(view) {
        var me=this;
        me.groupingFeature = view.view.findFeature('grouping');
        me.searchField = me.getView().down('#searchField');
    },

    onCollapseAll: function() {
        if(this.groupingFeature){
            this.groupingFeature.collapseAll();
        }
    },

    onExpandAll: function() {
        if(this.groupingFeature){
            this.groupingFeature.expandAll();
        }
    },
    
    onSaveClick: function(view, recIndex, cellIndex, item, e, record){
        this.saveRecord(record);
    },
    
    onGridActivate: function(){
        var me = this,
            store = me.getView().getStore();
        if(!store.isLoaded()){
            store.load();
        }
    },

    /***
     * If there is one readonly config in the curretn config store, set view model propertie
     */
    handleHasReadOnly:function(){
        var me=this,
            view = me.getView(),
            store = view && view.getStore(),
            hasReadOnly = false;

        if(!view || !store){
            return;
        }

        store.each(function(rec){
            if(!hasReadOnly && rec.get('isReadOnly')){
                hasReadOnly = true;
            }
        },me,{filtered: true});
        me.getView().getViewModel().set('hasReadOnly',hasReadOnly);
    },
    
    onGroupExpand:function(){
        this.markMatches();
    },
    
    saveRecord:function(record){
        var me=this,
            view = me.getView();
        
        if(!record.dirty){
            return;
        }
        //if the current change is for instance level
        if(Ext.Array.contains([record.CONFIG_LEVEL_INSTANCE,record.CONFIG_LEVEL_CLIENT],parseInt(record.get('level')))){
            Ext.Msg.alert('',view.strings.configChangeReloadMessageBoxText);
        }
        record.save({
            success: function(rec, operation){
                Editor.MessageBox.addSuccess(view.strings.updateConfigSuccessMessage);
            },
            failure: function(rec, operation) {
                Editor.app.getController('ServerException').handleCallback(rec, operation, false);
            }
        });
    },
    /***
     * Handler when the cell editing is finished
     */
    onConfigEdit : function(editor,context){
        this.saveRecord(context.record);
    },

    filterConfigByRoute: function(filterValue) {
        this.searchField.setValue(filterValue);
        this.groupingFeature.expandAll();
    },

    /**
     * @return {String} The value to process or null if the searchField value is blank or invalid.
     * @private
     */
    getSearchValue: function() {
        var me = this,
            value = me.searchField.getValue();
            
        if (value === '') {
            return null;
        }
        return Ext.String.escapeRegex(value);
    },
    
    onSearchFieldChange:function(field){
        var me = this,
            view = me.getView().view,
            store = me.getView().store;

        view.refresh();

        me.searchValue = me.getSearchValue();
        //TODO UGLY: is there another generic way to do such a thing? Otherwise we would have to implement a parser which gets and changes only the desired part of the hash instead of setting the whole one(here the config value)
        me.redirectTo('preferences/adminConfigGrid|config/'+me.searchValue);
        
        if(me.searchValue == null){
            store.clearFilter();
            return;
        }
        
        me.searchRegExp = new RegExp(me.getSearchValue(), 'gi');

        //filter the store for searchText value
        me.localConfigFilter();

        //mark the matched searchValue
        me.markMatches();
    },

    onStoreLoad: function() {
        //if we have a searchValue already on initial store load we have to expand all so that the results are visible
        if(this.searchValue) {
            this.groupingFeature.expandAll();
        }
    },
    
    onShowReadOnlyChange:function(field, newValue, oldValue, eOpts ){
        this.handleReadonlyConfig(newValue);
    },

    /**
     * Handles the task import finish (triggered implicitly by the messagabus ...)
     */
    onTaskImportFinished: function(task){
        this.getView().refreshForTask(task.get('taskGuid'));
    },
    /**
     * Show or hide readonly configs in the grid, based on the showReadonlyConfig flag
     */
    handleReadonlyConfig:function(showReadonlyConfig){
        var me = this,
            view = me.getView(),
            store = view && view.getStore();

        if(!store){
            return;
        }

        if(showReadonlyConfig){
            store.removeFilter('isReadOnly');
        }else{
            store.addFilter({ 
                property: 'isReadOnly',
                value   : false
            });
        }
        
        me.markMatches();
    },
    
    /***
     * Filter the store for searchText value
     */
    localConfigFilter:function(){
        var me=this,
            store = me.getView().getStore();
        if(me.searchValue == null){
            return;
        }
        //local store filter
        store.filter(new Ext.util.Filter({
            filterFn: function (object) {
                var match = false;
                if(!me.searchRegExp){
                    store.clearFilter();
                    return match;
                }
                Ext.Object.each(object.data, function (property, value) {
                    match = match || me.searchRegExp.test(String(value));
                });
                return match;
              }
        }));
    },
    
    /***
     * Mark matches in grid for searchText value
     */
    markMatches:function(){
        var me=this,
            view = me.getView().view,
            store = me.getView().getStore(),
            columns = me.getView().getVisibleColumns();
        
        if(me.searchValue == null){
            return;
        }
        
        me.matches = [];
        store.each(function(record, idx) {
            var node = view.getNode(record);

            if (node) {
                Ext.Array.forEach(columns, function(column) {
                    var cell = Ext.fly(node).down(column.getCellInnerSelector(), true),
                        matches, cellHTML,
                        seen;

                    if (cell) {
                        matches = cell.innerHTML.match(me.tagsRe);
                        cellHTML = cell.innerHTML.replace(me.tagsRe, me.tagsProtect);

                        // populate indexes array, and replace wrap matched string in a span
                        cellHTML = cellHTML.replace(me.searchRegExp, function(m) {
                            if (!seen) {
                                me.matches.push({
                                    record: record,
                                    column: column
                                });
                                seen = true;
                            }
                            return '<span class="' + me.matchCls + '">' + m + '</span>';
                        }, me);
                        // restore protected tags
                        Ext.each(matches, function(match) {
                            cellHTML = cellHTML.replace(me.tagsProtect, match);
                        });
                        // update cell html
                        cell.innerHTML = cellHTML;
                    }
                });
            }
         }, me);
    }
});