
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
    views: [
        'Editor.plugins.MatchResource.view.TmOverviewPanel',
        'Editor.plugins.MatchResource.view.AddTmWindow',
        'Editor.plugins.MatchResource.view.ImportTmWindow',
        'Editor.plugins.MatchResource.view.EditTmWindow'
    ],
    models: ['Editor.model.admin.Task', 'Editor.plugins.MatchResource.model.Resource','Editor.plugins.MatchResource.model.TmMt'],
    stores:['Editor.plugins.MatchResource.store.Resources','Editor.plugins.MatchResource.store.TmMts'],
    strings: {
        matchresource: '#UT#Matchressourcen',
        deleteConfirm: '#UT#Matchressource endgültig löschen?',
        deleteConfirmText: '#UT#Soll die gewählte Matchressource "{0}" wirklich endgültig gelöscht werden?',
        deleted: '#UT#Matchressource gelöscht.',
        edited: '#UT#Die Matchressource "{0}" wurde erfolgreich geändert.',
        created: '#UT#Die Matchressource "{0}" wurde erfolgreich erstellt.',
        noResourcesAssigned: '#UT#Keine Matchressourcen zugewiesen.',
        taskassocgridcell:'#UT#Zugewiesene Matchressourcen'
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
        ref: 'AddTmForm',
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
            'addTmWindow #save-tm-btn':{
                click:'handleSaveAddClick'
            },
            'editTmWindow #save-tm-btn':{
                click:'handleSaveEditClick'
            },
            'importTmWindow #save-tm-btn':{
                click:'handleSaveImportClick'
            },
            '#cancel-tm-btn':{
                click:'handleCancelClick'
            },
            '#tmOverviewPanel actioncolumn':{
                click:'handleTmGridActionColumnClick'
            },
            '#tmOverviewPanel #btnRefresh':{
                click:'handleButtonRefreshClick'
            },
            'headPanel': {
                afterrender: 'handleRenderHeadPanel'
            },
            '#adminTaskGrid': {
                beforerender:'injectTaskassocColumn'
            },
            'addTmWindow combo[name="resourceId"]': {
                select: 'handleResourceChanged'
            }
        }
    },
    init: function() {
        //add the taskassocs field to the task model
        Editor.model.admin.Task.replaceFields({
            name: 'taskassocs', type: 'auto', persist: false
        });
    },
    handleAfterShow: function(panel) {
        this.getHeadToolBar().down('#btnTmOverviewWindow').hide();
        Editor.data.helpSection = 'matchresource';
        Editor.data.helpSectionTitle = panel.getTitle();
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
    handleRenderHeadPanel: function() {
        var pos = this.getTopMenu().items.length - 2;
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
            panel = me.getCenterRegion().add({xtype: 'tmOverviewPanel'}).show();
            me.handleAfterShow(panel);
        }
    },
    handleOnAddTmClick : function(){
        var win = Ext.widget('addTmWindow');
        win.show();
    },
    handleButtonRefreshClick : function(){
        this.getTmOverviewPanel().getStore().load();
        this.getEditorPluginsMatchResourceStoreResourcesStore().load();
    },
    handleSaveAddClick:function(button){
        var me = this,
            window = button.up('window');
            form = window.down('form');

        if(!form.isValid()) {
            return;
        }

        window.setLoading(true);
        form.submit({
            params: {
                format: 'jsontext'
            },
            url: Editor.data.restpath+'plugins_matchresource_tmmt',
            scope: me,
            success: function(form, submit) {
                var msg = Ext.String.format(me.strings.created, submit.result.rows.name);
                this.getTmOverviewPanel().getStore().load();
                window.setLoading(false);
                window.close();
                Editor.MessageBox.addSuccess(msg);
            },
            failure: function(form, submit) {
                var res = submit.result;
                window.setLoading(false);
                //submit results are always state 200.
                //If success false and errors is an array, this errors are shown in the form directly,
                // so we dont need the handleException
                if(res.success || !Ext.isArray(res.errors)) {
                    Editor.app.getController('ServerException').handleException(submit.response);
                }
                if(Ext.isArray(res.errors)) {
                    form.markInvalid(res.errors);
                    me.showGeneralErrors(res.errors);
                    return;
                }
            }
        });
    },
    handleSaveEditClick: function(button){
        var me = this,
            window = button.up('window');
            form = window.down('form'),
            record = form.getRecord();

        record.reject();
        form.updateRecord(record);

        window.setLoading(true);
        record.save({
            failure: function(records, op) {
                window.setLoading(false);
                Editor.app.getController('ServerException').handleException(op.error.response);
            },
            success: function() {
                var msg = Ext.String.format(me.strings.edited, record.get('name'));
                me.getTmOverviewPanel().getStore().load();
                window.setLoading(false);
                window.close();
                Editor.MessageBox.addSuccess(msg);
            }
        });
    },
    handleSaveImportClick: function(button){
        var me = this,
            window = button.up('window'),
            form = window.down('form'),
            record = window.tmmtRecord;

        if(!form.isValid()) {
            return;
        }

        window.setLoading(true);
        form.submit({
            params: {
                format: 'jsontext'
            },
            url: Editor.data.restpath+'plugins_matchresource_tmmt/'+record.get('id')+'/import/',
            scope: me,
            success: function(form, submit) {
                window.setLoading(false);
                window.close();
                Editor.MessageBox.addSuccess(window.strings.importSuccess);
            },
            failure: function(form, submit) {
                var res = submit.result;
                window.setLoading(false);
                //submit results are always state 200.
                if(res && res.httpStatus) {
                    submit.response.status = res.httpStatus;
                }
                //If success false and errors is an array, this errors are shown in the form directly,
                // so we dont need the handleException
                if(res.success || !Ext.isArray(res.errors) || !res.message || res.message != 'NOT OK') {
                    Editor.app.getController('ServerException').handleException(submit.response);
                }
                if(Ext.isArray(res.errors)) {
                    form.markInvalid(res.errors);
                    me.showGeneralErrors(res.errors);
                    return;
                }
            }
        });
    },
    /**
     * Loops over the given error array and shows additional non formfield specfific errors
     */
    showGeneralErrors: function (errors){
        Ext.Array.each(errors, function(item){
            if(!item.id || item.id == -1) {
                Editor.MessageBox.getInstance().showDirectError(item.msg || item._errorMessage, item.data);
            }
        })
    },
    handleCancelClick: function(button){
        var window = button.up('window');
        window.down('form').getForm().reset();
        window.close();
    },
    handleEditTm : function(view, cell, cellIdx, rec){
        var win = Ext.widget('editTmWindow');
        win.loadRecord(rec);
        win.show();
    },
    handleImportTm : function(view, cell, cellIdx, rec){
        var win = Ext.widget('importTmWindow');
        win.loadRecord(rec);
        win.show();
    },
    handleTmGridActionColumnClick:function(view, cell, row, col, ev, evObj) {
        var me = this,
            store = view.getStore(),
            record = store.getAt(row),
            f = ev.getTarget().className.match(/ico-tm-([^ ]+)/);

        switch(f && f[1] || '') {
            case 'edit':
                me.handleEditTm(view,cell,col,record);
                break;
            case 'import':
                me.handleImportTm(view,cell,col,record);
                break;
            case 'download':
                me.handleDownloadTm(view,cell,col,record);
                break;
            case 'delete':
                me.handleDeleteTm(view,cell,col,record);
                break;
        }
    },
    handleDownloadTm : function(view, cell, cellIdx, rec){
        var proxy = rec.proxy,
            id = rec.getId(),
            url = proxy.getUrl();

        if (proxy.getAppendId() && proxy.isValidId(id)) {
            if (!url.match(proxy.slashRe)) {
                url += '/';
            }
        }
        url += encodeURIComponent(id);
        url += '/download';
        window.open(url);
    },
    handleDeleteTm : function(view, cell, cellIdx, rec){
        var msg = this.strings,
            store = view.getStore(),
            info = Ext.String.format(msg.deleteConfirmText, rec.get('name'));
        Ext.Msg.confirm(msg.deleteConfirm, info, function(btn){
            if(btn !== 'yes') {
                return;
            }
            rec.dropped = true;
            rec.save({
                failure: function() {
                    rec.reject();
                },
                success: function(record, operation) {
                    store && store.load();
                    store.remove(rec);
                    Editor.MessageBox.addSuccess(msg.deleted);
                    Editor.MessageBox.addByOperation(operation);
                }
            });
        });
    },
    /***
     * this function will insert the taskassoc column in to the adminTaskGrid
     */
    injectTaskassocColumn:function(taskgrid){
        var me = this,
            grid = taskgrid.getView().grid,
            column;
        
        if(grid.down('gridcolumn[dataIndex=taskassocs]')){
            return;
        }
        
        column = Ext.create('Ext.grid.column.Column', {
            xtype: 'gridcolumn',
            width: 45,
            dataIndex:'taskassocs',
            tdCls: 'taskassocs',
            sortable: false,
            cls: 'taskassocs',
            text:me.strings.taskassocgridcell,
            renderer: function(v, meta, rec){
                var strservices = [],
                    i, tmmt;
                if(!v || v.length == 0){
                    meta.tdAttr = 'data-qtip="'+me.strings.noResourcesAssigned+'"';
                    //meta.tdCls  = meta.tdCls  + ' info-icon';
                    return '';
                }
                for(i=0;i<v.length;i++){
                    tmmt = v[i];
                    strservices.push(tmmt.name+' ('+tmmt.serviceName+')');
                    //meta.tdAttr = 'data-qtip="'+tmmt.name+' ('+tmmt.serviceName+')<br/>"';
                }
                meta.tdAttr = 'data-qtip="'+strservices.join('<br />')+'"';
                return v.length;
            }
        });
        grid.headerCt.insert((grid.down('gridcolumn[dataIndex=userCount]').fullColumnIndex + 1), column);//inserting the dynamic column into grid
        grid.getView().refresh();
    },
    handleResourceChanged: function(combo, record, index) {
        var form = this.getAddTmForm().getForm(),
            disableUpload = !record.get('filebased'),
            filefield = form.findField('tmUpload');
        form.findField('serviceType').setValue(record.get('serviceType'));
        form.findField('serviceName').setValue(record.get('serviceName'));
        form.findField('color').setValue(record.get('defaultColor'));
        filefield.setDisabled(disableUpload);
        filefield.setReadOnly(disableUpload);
    }
});