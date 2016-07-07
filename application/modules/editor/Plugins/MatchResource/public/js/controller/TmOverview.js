
/*
START LICENSE AND COPYRIGHT

 This file is part of translate5
 
 Copyright (c) 2013 - 2015 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

 Contact:  http://www.MittagQI.com/  /  service (ATT) MittagQI.com

 This file may be used under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE version 3
 as published by the Free Software Foundation and appearing in the file agpl3-license.txt 
 included in the packaging of this file.  Please review the following information 
 to ensure the GNU AFFERO GENERAL PUBLIC LICENSE version 3.0 requirements will be met:
 http://www.gnu.org/licenses/agpl.html

 There is a plugin exception available for use with this release of translate5 for
 open source applications that are distributed under a license other than AGPL:
 Please see Open Source License Exception for Development of Plugins for translate5
 http://www.translate5.net/plugin-exception.txt or as plugin-exception.txt in the root
 folder of translate5.
  
 @copyright  Marc Mittag, MittagQI - Quality Informatics
 @author     MittagQI - Quality Informatics
 @license    GNU AFFERO GENERAL PUBLIC LICENSE version 3 with plugin-execptions
			 http://www.gnu.org/licenses/agpl.html http://www.translate5.net/plugin-exception.txt

END LICENSE AND COPYRIGHT
*/

/**#@++
 * @author Marc Mittag
 * @package editor
 * @version 1.0
 *
 */
/**
 * Die Einstellungen werden in einem Cookie gespeichert
 * @class Editor.controller.TmOverview
 * @extends Ext.app.Controller
 */
Ext.define('Editor.plugins.MatchResource.controller.TmOverview', {
    extend : 'Ext.app.Controller',
    views: ['Editor.plugins.MatchResource.view.TmOverviewPanel','Editor.plugins.MatchResource.view.AddTmWindow'],
    models: ['Editor.plugins.MatchResource.model.Resource','Editor.plugins.MatchResource.model.TmMt'],
    stores:['Editor.plugins.MatchResource.store.Resources','Editor.plugins.MatchResource.store.TmMts'],
    strings: {
        matchresource: '#UT#Matchressourcen',
        deleteConfirm: '#UT#Matchressource endgültig löschen?',
        deleteConfirmText: '#UT#Soll die gewählte Matchressource "{0}" wirklich endgültig gelöscht werden?',
        deleted: '#UT#Matchressource gelöscht.',
        edited: '#UT#Die Matchressource "{0}" wurde erfolgreich geändert.',
        created: '#UT#Die Matchressource "{0}" wurde erfolgreich erstellt.',
    },
    refs:[{
        ref: 'tmOverviewPanel',
        selector: '#tmOverviewPanel'
    },{
        ref: 'centerRegion',
        selector: 'viewport container[region="center"]'
    },{
        ref: 'headToolBar',
        selector: 'headPanel toolbar#top-menu'
    },{
        ref: 'TmForm',
        selector: '#addTmWindow form'
    },{
        ref: 'TmWindow',
        selector: '#addTmWindow'
    },{
        ref : 'topMenu',
        selector : 'headPanel #top-menu'
    }],
    listen: {
        controller: {
            '#Editor.$application': {
                editorViewportClosed: 'showButtonTmOverview'
            },
            '#Editor.$application':{
                editorViewportOpened:'hideButtonTmOverview'
            }
        },
        component: {
            '#btnTmOverviewWindow': {
                click: 'handleOnButtonClick'
            },
            '#tmOverviewPanel':{
                hide: 'handleAfterHide',
                show: 'handleAfterShow',
                celldblclick: 'handleEditTm' 
            },
            '#btnAddTm':{
                click:'handleOnAddTmClick'
            },
            '#save-tm-btn':{
                click:'handleSaveWindowClick'
            },
            '#cancel-tm-btn':{
                click:'handleCancelWindowClick'
            },
            '#gridTmOverview actioncolumn':{
                click:'handleTmGridActionColumnClick'
            },
            '#tmOverviewPanel #btnRefresh':{
                click:'handleButtonRefreshClick'
            },
            'headPanel': {
                afterrender: 'handleRenderHeadPanel'
            }
        }
    },
    handleAfterShow: function() {
        this.getHeadToolBar().down('#btnTmOverviewWindow').hide();
    },
    handleAfterHide: function() {
        this.getHeadToolBar().down('#btnTmOverviewWindow').show();
    },
    hideButtonTmOverview : function(){
        this.getHeadToolBar().down('#btnTmOverviewWindow').hide();
    },
    showButtonTmOverview : function(){
        this.getHeadToolBar().down('#btnTmOverviewWindow').show();
    },
    /**
     * inject the plugin tab and load the task meta data set
     *  xtype: 'button',
        itemId: 'btnTmOverviewWindow',
        text: 'TM Overview',
     */
    onParentRender: function(window) {
        var me = this;
        me.actualTask = window.actualTask;
        me.getTaskTabs().add({xtype: 'tmMtIntegrationTaskAssocPanel', actualTask: me.actualTask});
    },
    handleRenderHeadPanel: function() {
        var pos = this.getTopMenu().items.length - 1;
        this.getTopMenu().insert(pos, {
            xtype: 'button',
            itemId: 'btnTmOverviewWindow',
            text: this.strings.matchresource
        });
    },
    handleOnButtonClick: function(window) {
        var me = this,
            panel = me.getTmOverviewPanel();
        me.actualTask = window.actualTask;
      
        me.getCenterRegion().items.each(function(item){
            item.hide();
        });
      
        if(panel) {
            panel.show();
        } else {
            me.getCenterRegion().add({xtype: 'TmOverviewPanel'}).show();
            me.handleAfterShow();
        }
    },
    handleOnAddTmClick : function(){
        var win = Ext.widget('addTmWindow',{editMode: false});
        win.show();
    },
    handleButtonRefreshClick : function(){
        Ext.getCmp('gridTmOverview').getStore().load();
        this.getEditorPluginsMatchResourceStoreResourcesStore().load();
    },
    handleSaveWindowClick:function(){
        var me = this,
            form = this.getTmForm(),
            win = me.getTmWindow();

        if(!form.isValid()) {
            return;
        }

        if(win.editMode){
            var f = form.getForm(),
                record = form.getRecord();
            record.reject();
            f.updateRecord(record);

            record.save({
                failure: function(records, op) {
                    Editor.app.getController('ServerException').handleException(op.error.response);
                },
                success: function() {
                    var msg = Ext.String.format(me.strings.edited, record.get('name'));
                    Ext.getCmp('gridTmOverview').getStore().load();
                    win.setLoading(false);
                    win.close();
                    Editor.MessageBox.addSuccess(msg);
                }
            });
            return;
        }
        form.submit({
            //if editMode:
            // method = PUT instead of POST // is working the file upload PUR request?
            // provide the ID of the record in the URL with /ID
            params: {
                format: 'jsontext'
            },
            url: Editor.data.restpath+'plugins_matchresource_tmmt',
            scope: me,
            success: function(form, submit) {
                var msg = Ext.String.format(me.strings.created, submit.result.rows.name);
                Ext.getCmp('gridTmOverview').getStore().load();
                win.setLoading(false);
                me.getTmWindow().close();
                Editor.MessageBox.addSuccess(msg);
            },
            failure: function(form, submit) {
                win.setLoading(false);
                Editor.app.getController('ServerException').handleException(submit.response);
            }
        });
    },
    handleCancelWindowClick:function(){
        this.getTmForm().getForm().reset();
        this.getTmWindow().close();
    },
    handleEditTm : function(view, cell, cellIdx, rec){
        var win = Ext.widget('addTmWindow',{editMode: true});
        win.loadRecord(rec);
        win.show();
    },
    handleTmGridActionColumnClick:function(view, cell, row, col, ev, evObj) {
        var me = this,
            store = view.getStore(),
            selectedRow = store.getAt(row),
            t = ev.getTarget(),
            msg = me.strings,
            info,
            f = t.className.match(/ico-tm-([^ ]+)/);

        switch(f && f[1] || '') {
            case 'edit':
                me.handleEditTm(view,cell,col,selectedRow);
                break;
            case 'delete':
                info = Ext.String.format(me.strings.deleteConfirmText, selectedRow.get('name'));
                Ext.Msg.confirm(me.strings.deleteConfirm, info, function(btn){
                    if(btn !== 'yes') {
                        return;
                    }
                    selectedRow.dropped = true;
                    selectedRow.save({
                        failure: function() {
                            selectedRow.reject();
                        },
                        success: function() {
                            store && store.load();
                            store.remove(selectedRow);
                            Editor.MessageBox.addSuccess(me.strings.deleted);
                        }
                    });
                });
                break;
        }
    }
});