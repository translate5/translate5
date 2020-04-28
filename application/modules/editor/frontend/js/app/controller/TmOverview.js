
/*
START LICENSE AND COPYRIGHT

 This file is part of translate5
 
 Copyright (c) 2013 - 2017 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

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
Ext.define('Editor.controller.TmOverview', {
    extend : 'Ext.app.Controller',
    views: [
        'Editor.view.LanguageResources.TmOverviewPanel',
        'Editor.view.LanguageResources.AddTmWindow',
        'Editor.view.LanguageResources.ImportTmWindow',
        'Editor.view.LanguageResources.EditTmWindow',
        'Editor.view.LanguageResources.TaskGridWindow',
        'Editor.view.LanguageResources.ImportCollectionWindow',
        'Editor.view.LanguageResources.log.LogWindow',
        'Editor.view.LanguageResources.ProposalExport',
        'Editor.view.LanguageResources.services.Default'
    ],
    models: ['Editor.model.admin.Task', 'Editor.model.LanguageResources.Resource','Editor.model.LanguageResources.LanguageResource'],
    stores:[
        'Editor.store.LanguageResources.Resources',
        'Editor.store.LanguageResources.LanguageResource',
        'Editor.store.LanguageResources.SdlEngine',
        'Editor.store.LanguageResources.Logs'
    ],
    strings: {
        deleteConfirm: '#UT#Sprachressource endgültig löschen?',
        deleteConfirmText: '#UT#Soll die gewählte Sprachressource "{0}" wirklich endgültig gelöscht werden?',
        deleteConfirmLocal: '#UT#Sprachressource löschen?',
        deleteConfirmLocalText: '#UT#Soll die gewählte Sprachressource "{0}" aus der Liste der hier angezeigten Sprach-Resourcen gelöscht werden? <br /> Es werden keine Daten im verknüpften TM System gelöscht, da keine Verbindung besteht.',
        deleted: '#UT#Sprachressource gelöscht.',
        edited: '#UT#Die Sprachressource "{0}" wurde erfolgreich geändert.',
        created: '#UT#Die Sprachressource "{0}" wurde erfolgreich erstellt.',
        noResourcesAssigned: '#UT#Keine Sprach-Resourcen zugewiesen.',
        taskassocgridcell:'#UT#Zugewiesene Sprach-Resourcen',
        exportTm: '#UT#als TM Datei exportieren',
        exportTmx: '#UT#als TMX Datei exportieren'
    },
    refs:[{
        ref: 'tmOverviewPanel',
        selector: '#tmOverviewPanel'
    },{
        ref: 'AddTmForm',
        selector: '#addTmWindow form'
    },{
        ref: 'TmWindow',
        selector: '#addTmWindow'
    }],
    listen: {
        component: {
            '#tmOverviewPanel':{
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
            '#tmOverviewPanel pagingtoolbar':{
                afterrender: function(pagebar){
                    //fix render issue where the pagecount in the bar is not shown correctly, 
                    // even though all given pageing data in pagebar is correct. Possible ExtJS Bug
                    pagebar.onLoad(); 
                }
            },
            '#tmOverviewPanel #btnRefresh':{
                click:'handleButtonRefreshClick'
            },
            '#adminTaskGrid': {
                beforerender:'injectTaskassocColumn'
            },
            'addTmWindow combo[name="resourceId"]': {
                select: 'handleResourceChanged'
            },
            'addTmWindow filefield[name="tmUpload"]': {
                change: 'handleChangeImportFile'
            }
        },
        store: {
            '#Editor.store.LanguageResources.LanguageResource': {
                update: 'addRecordToImportCheck'
            }
        }
    },
    /**
     * Internal stack for records to be reloaded because of status import
     */
    importingRecords: [],
    /**
     * Task to check the records to be imported
     */
    checkImportingRecordsTask: null,
    init: function() {
        var me = this;
        //add the taskassocs field to the task model
        Editor.model.admin.Task.replaceFields({
            name: 'taskassocs', type: 'auto', persist: false
        });

        //add the default service interceptor instance
        //this needs to be initialized here, since the service classes are used in the tmoverview panel
        Editor.util.LanguageResources.addService(Ext.create('Editor.view.LanguageResources.services.Default'));
        
        //define task to reload importing tasks
        me.checkImportingRecordsTask = Ext.TaskManager.newTask({
            run: function(){
                var rec;
                while(me.importingRecords.length > 0) {
                    rec = me.importingRecords.shift();
                    rec.set('status', rec.STATUS_LOADING);
                    rec.load();
                }
                // stop the task when all records are reloaded
                me.checkImportingRecordsTask && me.checkImportingRecordsTask.stop();
            },
            interval: 5000
        });
    },
    handleOnAddTmClick : function(){
        var win = Ext.widget('addTmWindow');
        win.show();
    },
    handleButtonRefreshClick : function(){
        this.getTmOverviewPanel().getStore().load();
        Ext.StoreManager.get('Editor.store.LanguageResources.Resources').load();
    },
    handleSaveAddClick:function(button){
        var me = this,
            window = me.getTmWindow(),
            windowViewController = window.getController(),
            form = window.down('form'),
            resourceField = form.down('combo[name="resourceId"]'),
            serviceName = resourceField.getSelection() && resourceField.getSelection().get('serviceName'),
            helppage = resourceField.getSelection() && resourceField.getSelection().get('helppage');
        
        if(!form.isValid()) {
            return;
        }
        
        if (!windowViewController.isValidService(serviceName, helppage)) {
            return;
        }

        me.mergeCustomerFieldIds(form);

        //check and update the form fields from the engine
        me.handleEngineSelect(form);

        window.setLoading(true);
        form.submit({
            timeout: 240,
            params: {
                format: 'jsontext'
            },
            url: Editor.data.restpath+'languageresourceinstance',
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
                if(!res || res.success || !Ext.isArray(res.errors)) {
                    Editor.app.getController('ServerException').handleException(submit.response);
                    return;
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

        me.mergeCustomerFieldIds(form);
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
            record = window.languageResourceRecord;

        if(!form.isValid()) {
            return;
        }

        window.setLoading(true);
        form.submit({
            params: {
                format: 'jsontext'
            },
            url: Editor.data.restpath+'languageresourceinstance/'+record.get('id')+'/import/',
            scope: me,
            success: function(form, submit) {
                record.load();
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
     * Checks loaded LanguageResources and reloads LanguageResources with status import periodically
     * @param {Ext.data.Store} store
     */
    addRecordToImportCheck: function(store, record) {
        var me = this,
            checkImporting;
        if(record.get('status') === record.STATUS_IMPORT) {
            me.importingRecords.push(record);
            me.checkImportingRecordsTask.start();
        }
    },
    /**
     * Loops over the given error array and shows additional non formfield specfific errors
     */
    showGeneralErrors: function (errors){
        Ext.Array.each(errors, function(item){
            if(!item.id || item.id === -1) {
                Editor.MessageBox.getInstance().showDirectError(item.msg || item._errorMessage, item.data);
            }
        })
    },
    handleCancelClick: function(button){
        var window = button.up('window'),
            form=window.down('form').getForm();

        form.reset();
        window.close();
    },
    handleLogTm:function(view, cell, cellIdx, rec){
        var win = Ext.widget('languageResourcesLogLogWindow',{
        	languageResource:rec
        });
        win.show();
        win.load();
    },
    handleEditTm : function(view, cell, cellIdx, rec){
        var win = Ext.widget('editTmWindow');
        win.loadRecord(rec);
        win.show();
    },
    handleShowTasks: function(view, cell, cellIdx, rec){
        var win = Ext.widget('languageResourceTaskGridWindow');
        win.loadRecord(rec);
        win.show();
    },
    handleImportTm : function(view, cell, cellIdx, rec){
        //find the import window from the service name
        var importWindow = Editor.util.LanguageResources.getService(rec.get('serviceName')).getImportWindow(),
            win = Ext.widget(importWindow);
        win.loadRecord(rec);
        win.show();
    },
    handleTmGridActionColumnClick:function(view, cell, row, col, ev, record) {
        var me = this,
            grid=view.up('tmOverviewPanel'),
            f = ev.getTarget().className.match(/ico-tm-([^ ]+)/);
        
        //call the selection row handler
        grid.onGridRowSelect(grid,[record]);

        switch(f && f[1] || '') {
            case 'edit':
                me.handleEditTm(view,cell,col,record);
                break;
            case 'tasks':
                me.handleShowTasks(view,cell,col,record);
                break;
            case 'import':
                me.handleImportTm(view,cell,col,record);
                break;
            case 'download':
                me.handleDownloadTm(view,cell,col,record, ev);
                break;
            case 'delete':
                me.handleDeleteTm(view,cell,col,record);
                break;
            case 'export':
                me.handleExportProposalClick(view,cell,col,record);
                break;
            case 'log':
                me.handleLogTm(view,cell,col,record);
                break;
        }
    },
    handleDownloadTm : function(view, cell, cellIdx, rec, ev){
        var me = this,
            proxy = rec.proxy,
            id = rec.getId(),
            url = proxy.getUrl(),
            menu,
            filetypes = Editor.util.LanguageResources.getService(rec.get('serviceName')).getValidFiletypes(),
            createMenuItems = function() {
                var items = [];
                if (filetypes.indexOf('tm') !== -1) {
                    items.push({
                        itemId: 'exportTm',
                        hrefTarget: '_blank',
                        href: url+'/download.tm',
                        text: me.strings.exportTm
                    });
                }
                if (filetypes.indexOf('tmx') !== -1) {
                    items.push({
                        itemId: 'exportTmx',
                        hrefTarget: '_blank',
                        href: url+'/download.tmx',
                        text : me.strings.exportTmx
                    });
                }
                return items;
            };

        if (!url.match(proxy.slashRe)) {
            url += '/';
        }
        url += encodeURIComponent(id);

        menu = Ext.widget('menu', { 
            items: createMenuItems()
        });
        menu.showAt(ev.getXY());
    },
    handleDeleteTm : function(view, cell, cellIdx, rec){
        var msg = this.strings,
            store = view.getStore(),
            noConn = rec.get('status') == rec.STATUS_NOCONNECTION,
            info = Ext.String.format(noConn ? msg.deleteConfirmLocalText : msg.deleteConfirmText, rec.get('name')),
            //force local deletion when no connection to resource
            params = noConn ? {deleteLocally: true} : {};

        
        Ext.Msg.confirm(noConn ? msg.deleteConfirmLocal : msg.deleteConfirm, info, function(btn){
            if(btn !== 'yes') {
                return;
            }
            rec.drop();
            rec.save({
                params: params,
                failure: function() {
                    rec.reject();
                    store && store.load();
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
            stateId: 'taskassocColumn',
            tdCls: 'taskassocs',
            sortable: false,
            cls: 'taskassocs',
            text:me.strings.taskassocgridcell,
            renderer: function(v, meta, rec){
                var strservices = [],
                    i, languageResource;
                if(!v || v.length == 0){
                    meta.tdAttr = 'data-qtip="'+me.strings.noResourcesAssigned+'"';
                    //meta.tdCls  = meta.tdCls  + ' info-icon';
                    return '';
                }
                for(i=0;i<v.length;i++){
                    languageResource = v[i];
                    strservices.push(languageResource.name+' ('+languageResource.serviceName+')');
                    //meta.tdAttr = 'data-qtip="'+languageResource.name+' ('+languageResource.serviceName+')<br/>"';
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
    },
    handleChangeImportFile: function(field, val){
        var name = this.getAddTmForm().down('textfield[name=name]'),
            srcLang = this.getAddTmForm().down('combo[name=sourceLang]'),
            targetLang = this.getAddTmForm().down('combo[name=targetLang]'),
            langs = val.match(/-([a-zA-Z]{2,3})-([a-zA-Z]{2,3})\.[^.]+$/);

        if(name.getValue() == '') {
            name.setValue(val.replace(/\.[^.]+$/, ''));
        }
        //simple algorithmus to get the language from the filenam
        if(langs && langs.length == 3) {
            var srcStore = srcLang.store,
                targetStore = targetLang.store,
                srcIdx = srcStore.find('label', '('+langs[1]+')', 0, true, true),
                targetIdx = targetStore.find('label', '('+langs[2]+')', 0, true, true);

            if(srcIdx >= 0) {
                srcLang.setValue(srcStore.getAt(srcIdx).get('id'));
            }
            if(targetIdx >= 0) {
                targetLang.setValue(targetStore.getAt(targetIdx).get('id'));
            }
        }
    },

    /***
     * Merge customer field values as comma separated values
     */
    mergeCustomerFieldIds:function(form){
        var form=form.getForm(),
            record = form.getRecord(),
            resourcesCustomers=form.findField('resourcesCustomers'),
            resourcesCustomersHidden=form.findField('resourcesCustomersHidden'),
            useAsDefault=form.findField('useAsDefault'),
            defaultCustomers=useAsDefault.getValue(),
            customers=resourcesCustomers.getValue(),
            customersData=[];

        Ext.each(customers, function (item) {
            customersData.push({
                customerId:item,
                useAsDefault:Ext.Array.contains(defaultCustomers, item) ? 1 :0
            });
        });

        resourcesCustomersHidden.setValue(Ext.encode(customersData));
        //if record exist(editTm) -> set it
        if(record){
            record.set('resourcesCustomersHidden',Ext.encode(customersData));
        }
    },

    /**
     * Set the labelText(domainCode) when engine with domain code is selected
     */
    handleEngineSelect:function(form){
        var sdlEngine=form.down('#sdlEngine').getSelection();
        
        //set the labelText field with the domain code if exist
        if(sdlEngine){
            form.getForm().findField('specificData').setValue(JSON.stringify({
            	domainCode:sdlEngine.get('domainCode'),
        		engineName:sdlEngine.get('name')
            }));
        }
    },

    /**
     * Get language labels joined with "," for given language ids
     */
    getLanguageLable:function(languageIds){
        var labels=[],
            lngStore=Ext.StoreManager.get('admin.Languages');
        languageIds.forEach(function(id){
            labels.push(lngStore.getById(id).get('label'));
        });
        return labels.join(',');
    },
    
    /***
     * Export proposals action button click handler
     */
    handleExportProposalClick:function(view, cell, cellIdx, rec){
    	var proposalWindow=Ext.create('Editor.view.LanguageResources.ProposalExport',{
    		record:rec
    	});
    	proposalWindow.show();
    }
});