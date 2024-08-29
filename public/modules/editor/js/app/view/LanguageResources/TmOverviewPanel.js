
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

/**#@++
 * @author Marc Mittag
 * @package editor
 * @version 1.0
 *
 */
/**
 * @class Editor.view.LanguageResources.TmOverviewPanel
 * @extends Ext.panel.Panel
 */
Ext.define('Editor.view.LanguageResources.TmOverviewPanel', {
    extend : 'Ext.grid.Panel',
    requires: [
        'Editor.view.admin.customer.CustomerFilter',
        'Editor.view.LanguageResources.TmOverviewViewController',
        'Editor.view.LanguageResources.TermCollectionExportActionMenu'
    ],
    alias: 'widget.tmOverviewPanel',
    controller: 'tmOverviewPanel',
    itemId: 'tmOverviewPanel',
    stateful: true,
    stateId: 'editor.tmOverviewPanel',
    title: '#UT#Sprachressourcen',
    helpSection: 'languageresource',
    glyph: 'xf1c0@FontAwesome5FreeSolid',
    strings: {
        name: '#UT#Name',
        edit: '#UT#Bearbeiten',
        erase: '#UT#Löschen',
        tasks: '#UT#Zugewiesene Aufgaben',
        resource: '#UT#Ressource',
        color: '#UT#Farbe',
        refresh: '#UT#Aktualisieren',
        addResource: '#UT#Sprachressource hinzufügen',
        noTaskAssigned: '#UT#Keine Aufgaben zugewiesen.',
        sourceLang: '#UT#Quellsprache',
        targetLang: '#UT#Zielsprache',
        languageResourceStatusColumn: '#UT#Status',
        languageResourceStatus: {
            loading: '#UT#Statusinformationen werden geladen',
            error: '#UT#Fehler',
            available: '#UT#verfügbar',
            unknown: '#UT#unbekannt',
            noconnection: '#UT#Keine Verbindung!',
            import: '#UT#Import läuft',
            notloaded: '#UT#verfügbar',
            notchecked: '#UT#Nicht geprüft',
            novalidlicense: '#UT#Keine gültige Lizenz.',
            tuninginprogress: '#UT#Wird trainiert',
            reorganize_in_progress: '#UT#Wird reorganisiert',
            reorganize_failed: '#UT#Reorganisation gescheitert'
        },
        customers: '#UT#Kunden',
        useAsDefault: '#UT#Leserechte standardmäßig',
        writeAsDefault: '#UT#Schreibrechte standardmäßig',
        taskassocgridcell: '#UT#Zugewiesene Aufgaben',
        groupHeader: '#UT#Ressource: {name}',
        specificDataText:'#UT#Zusätzliche Infos',
        pivotAsDefault:'#UT#Standardmäßig als Pivot verwenden',
        tmNotConverted: '#UT#TM Not Converted',
    },
    cls: 'tmOverviewPanel',
    height: '100%',
    layout: {
        type: 'fit'
    },

    tooltip:null,

    initConfig: function(instanceConfig) {
        var me = this,
            canNotAddLangresource =  ! Editor.app.authenticatedUser.isAllowed('editorAddLangresource'),
            canNotDeleteLangresource =  ! Editor.app.authenticatedUser.isAllowed('editorDeleteLangresource'),
            service = function(rec) {
                return Editor.util.LanguageResources.getService(rec.get('serviceName'));
            },
            config = {
                title: me.title, //see EXT6UPD-9
                tooltip: Editor.data.l10n.languageResources.tooltip,
                store : 'Editor.store.LanguageResources.LanguageResource',
                plugins: ['gridfilters'],
                viewConfig: {
                    getRowClass: function(record) {
                        //adds service specific handled css to the row 
                        return service(record).getTmOverviewRowCls(record).join(' ');
                    }
                },
                selModel: {
                    pruneRemoved: false,
                    listeners: {
                        selectionchange: {
                            fn: 'onGridRowSelect',
                            scope: me
                        }
                    }
                },
                features: [{
                    ftype: 'grouping',
                    hideGroupedHeader: true,
                    enableGroupingMenu: false
                }],
                columns: [{
                    xtype: 'gridcolumn',
                    width: 50,
                    text: 'ID',
                    dataIndex: 'id',
                    hidden: true,
                    filter: {
                        type: 'numeric'
                    }
                },{
                    xtype: 'gridcolumn',
                    width: 170,
                    text: me.strings.resource,
                    dataIndex: 'serviceName',
                    tdCls: 'serviceName',
                    renderer: function(v, meta, rec){
                        var store = Ext.getStore('Editor.store.LanguageResources.Resources'),
                            resource = store.getById(rec.get('resourceId'));
                        if(resource) {
                            meta.tdAttr = 'data-qtip="'+resource.get('name')+'"';
                        }
                        return v;
                    },
                    filter: {
                        type: 'list',
                        options: Editor.data.LanguageResources.serviceNames
                    }
                },{
                    xtype: 'gridcolumn',
                    width: 390,
                    dataIndex: 'name',
                    filter: {
                        type: 'string'
                    },
                    renderer: function(v, meta, rec){
                        return service(rec).getNameRenderer().call(this, v, meta, rec);
                    },
                    text: me.strings.name
                },{
                    xtype: 'actioncolumn',
                    width: 140,
                    items: [{
                        getTip: (v, meta, rec) => service(rec).getConversionIconTip(rec),
                        getClass: (v, meta, rec) => service(rec).getConversionIconClass(rec),
                        hidden: true,
                        isDisabled: (view, rowIndex, colIndex, item, record) =>
                            item.hidden = !record.get('tmNeedsConversion')
                    },{
                        tooltip: Editor.data.l10n.crossLanguageResourceSynchronization.tooltip,
                        iconCls: 'ico-tm-sync',
                        hidden: true,
                        isDisabled: (view, rowIndex, colIndex, item, record) =>
                            item.hidden = ! record.get('synchronizableService')
                    },{
                        tooltip: me.strings.edit,
                        action: 'edit',
                        iconCls: 'ico-tm-edit',
                        isDisabled: function( view, rowIndex, colIndex, item, record ) {
                            return record.get('status') === 'novalidlicense' ? true : false;
                        }
                    },{
                        tooltip: me.strings.erase,
                        action: 'delete',
                        iconCls: 'ico-tm-delete',
                        hidden: canNotDeleteLangresource,
                        isDisabled: function( view, rowIndex, colIndex, item, record ) {
                            return record.get('status') === 'novalidlicense' ? true : false;
                        }
                    },{
                        tooltip: me.strings.tasks,
                        action: 'tasks',
                        iconCls: 'ico-tm-tasks',
                        hidden: true,
                        isDisabled: function( view, rowIndex, colIndex, item, record ) {
                            // this icon is hidden for resources which are NOT writable
                            // and visible for all writable resources like (TM - t5memory for example)
                            item.hidden = !record.get('writable');
                        }
                    },{
                        action: 'specific',
                        getClass: function(v, meta, r) {
                            return service(r).getEditSpecificIconClass(r);
                        },
                        getTip: function(view, metadata, r){
                            return service(r).getEditSpecificTooltip(r);
                        },
                        isDisabled: function(view, rowIndex, colIndex, item, record) {
                            return service(record).isEditSpecificDisabled(record);
                        }
                    },{
                        action: 'import',
	                    getClass: function(v,meta,r) {
                        	return service(r).getImportIconClass(r);
                        },
	                    getTip: function(view,metadata,r){
                            return service(r).getAddTooltip(r);
	                    },
                        isDisabled: function( view, rowIndex, colIndex, item, record ) {
                            return [record.STATUS_IMPORT, record.STATUS_LOADING].includes(record.get('status'));
                        }
                    },{
                        action: 'download',
                        getClass: function(v,meta,r) {
                        	return service(r).getDownloadIconClass(r);
                        },
	                    getTip: function(view,metadata,r){
	                    	return service(r).getDownloadTooltip(r);
	                    },
                        isDisabled: function( view, rowIndex, colIndex, item, record ) {
                            return [record.STATUS_IMPORT, record.STATUS_LOADING].includes(record.get('status'));
                        }
                    },{
                        action: 'export',
                        getClass: function(v,meta,r) {
                        	return service(r).getExportIconClass(r);
                        },
	                    getTip: function(view,metadata,r){
	                    	return service(r).getExportTooltip(r);
	                    },
                        isDisabled: function( view, rowIndex, colIndex, item, record ) {
                            return [record.STATUS_IMPORT, record.STATUS_LOADING].includes(record.get('status'));
                        }
                    },{
                        action: 'log',
                        tooltip: me.strings.log,
                        getTip:function(view,metadata,r){
                        	return service(r).getLogTooltip(r);
	                    },
	                    getClass:function(view,metadata,r){
	                    	return service(r).getLogIconClass(r);
	                    }
                    }]
                },{
                    xtype: 'gridcolumn',
                    width: 100,
                    dataIndex: 'sourceLang',
                    renderer : me.langRenderer,
                    cls: 'source-lang',
                    filter: {
                        type: 'string'
                    },
                    text: me.strings.sourceLang
                },{
                    xtype: 'gridcolumn',
                    width: 100,
                    dataIndex: 'targetLang',
                    renderer : me.langRenderer,
                    cls: 'target-lang',
                    filter: {
                        type: 'string'
                    },
                    text: me.strings.targetLang
                },{
                    xtype: 'gridcolumn',
                    width: 100,
                    dataIndex: 'customerIds',
                    filter: {
                        type: 'customer' // [Multitenancy]
                    },
                    text:me.strings.customers,
                    tooltip: Editor.data.l10n.languageResources.customers.tooltip,
                    renderer:me.resourceCustomersRenderer
                },{
                    xtype: 'gridcolumn',
                    width: 270,
                    dataIndex: 'customerUseAsDefaultIds',
                    filter: {
                        type: 'string'
                    },
                    text:me.strings.useAsDefault,
                    tooltip: Editor.data.l10n.languageResources.useAsDefault.tooltip,
                    renderer:me.defaultCustomersRenderer
                },
                {
                    xtype: 'gridcolumn',
                    width: 270,
                    dataIndex: 'customerWriteAsDefaultIds',
                    filter: {
                        type: 'string'
                    },
                    text:me.strings.writeAsDefault,
                    tooltip: Editor.data.l10n.languageResources.writeAsDefault.tooltip,
                    renderer:me.defaultCustomersRenderer
                },
                {
                    xtype: 'gridcolumn',
                    width: 270,
                    dataIndex: 'customerPivotAsDefaultIds',
                    filter: {
                        type: 'string'
                    },
                    text:me.strings.pivotAsDefault,
                    tooltip: Editor.data.l10n.languageResources.pivotAsDefault.tooltip,
                    renderer:me.defaultCustomersRenderer
                },
                {
                    xtype: 'gridcolumn',
                    width: 100,
                    dataIndex: 'color',
                    renderer: function(value, metaData, record) {
                        return '<div style="float:left; width:15px; height:15px; margin-right:5px;' +
                            ' border:1px solid rgba(0,0,0,.2); background:#' +
                            record.data.color +
                            ';"></div>';
                    },
                    text: me.strings.color
                },{
                    xtype: 'gridcolumn',
                    text: me.strings.specificDataText,
                    width: 160,
                    tdCls: 'specificData',
                    renderer: me.specificDataRenderer,
                    dataIndex: 'specificData'
                },{
                    xtype: 'gridcolumn',
                    width: 160,
                    text: me.strings.languageResourceStatusColumn,
                    dataIndex: 'status',
                    tdCls: 'status',
                    renderer: function(value, meta, record) {
                        var str = me.strings.languageResourceStatus,
                            info = record.get('statusInfo');
                        if(value === "loading") { 
                            // show list as soon as possible, so show status on click only due to different latency of the requested TMs
                            meta.tdCls = 'loading';
                            meta.tdAttr = 'data-qtip="' + str.loading + '"';
                            return ''; //no string since icon set
                        }
                        if(str[value]){
                            value = str[value];
                        }
                        else {
                            value = str.unknown;
                        }
                        if(info) {
                            meta.tdAttr = 'data-qtip="'+info+'"';
                            meta.tdCls = 'infoIcon';
                        }
                        else {
                            meta.tdAttr = 'data-qtip=""';
                        }
                        return value;

                    }
                },{
                    xtype: 'gridcolumn',
                    width: 40,
                    dataIndex: 'taskList',
                    filter: {
                        type: 'string'
                    },
                    tdCls: 'taskList',
                    cls: 'taskList',
                    text: me.strings.taskassocgridcell,
                    renderer: function(v, meta){
                        var tasks = [], i;
                        
                        if(!v || v.length === 0){
                            tasks.push(this.strings.noTaskAssigned);
                        }
                        else {
                            for(i = 0;i<v.length;i++){
                                tasks.push(v[i]);
                            }
                        }
                        meta.tdAttr = 'data-qtip="'+Ext.String.htmlEncode(tasks.join('<br />•••••••••••<br />'))+'"';
                        return v.length;
                    }
                }],
                dockedItems: [{
                    xtype: 'toolbar',
                    dock: 'top',
                    enableOverflow: true,
                    items: [{
                        xtype: 'button',
                        glyph: 'f2f1@FontAwesome5FreeSolid',
                        itemId: 'btnRefresh',
                        text: me.strings.refresh,
                        tooltip: Editor.data.l10n.languageResources.refresh
                    },{
                        xtype: 'button',
                        glyph: 'f067@FontAwesome5FreeSolid',
                        itemId: 'btnAddTm',
                        text: me.strings.addResource,
                        tooltip: Editor.data.l10n.languageResources.create,
                        hidden: canNotAddLangresource,
                    },
                    {
                        xtype: 'button',
                        iconCls: 'x-fa fa-filter',
                        id: 'showConvertedFilter',
                        bind: {
                            text: '{l10n.contentProtection.show_only_not_converted}'
                        },
                        enableToggle: true,
                        toggleHandler: 'onShowOnlyNotConverted'
                    },
                    {
                        xtype: 'button',
                        iconCls: 'x-fa fa-exchange',
                        itemId: 'btnConvertTms',
                        bind: {
                            text: '{l10n.contentProtection.convert_tms}'
                        },
                        handler: 'onConvertTms',
                        hidden: true
                    }]
                }]
      };

      if (instanceConfig) {
          me.self.getConfigurator().merge(me, config, instanceConfig);
      }
      return me.callParent([config]);
    },

    initComponent: function () {
        var me = this;
        me.callParent(arguments);
        me.view.on('afterrender', function () {
            me.tooltip = me.createToolTip();
            me.tooltip.on({
                beforeshow:{
                    scope: me,
                    fn: me.createSpecificDataTooltip
                }
            });
        });

    },

    onDestroy: function () {
        if (this.tooltip && this.tooltip.destroy) {
            this.tooltip.destroy();
        }
        this.callParent(arguments);
    },


    /***
     * Tooltip for specificData column. This will fill the template with data based on the user mouse:hover column
     * @returns {Ext.tip.ToolTip}
     */
    createToolTip: function () {
        return Ext.create('Ext.tip.ToolTip', {
            target: this.view.el,
            delegate: 'td.specificData',
            dismissDelay: 0,
            showDelay: 200,
            maxWidth: 1000,
            renderTo: Ext.getBody()
        });
    },

    /**
     * Creates the specific-data tooltip contents out of the specific-data
     * @param {Ext.tip.ToolTip} tip
     * @returns {boolean}
     */
    createSpecificDataTooltip: function updateTipBody(tip) {
        var tr = Ext.fly(tip.triggerElement).up('tr'),
            record = this.view.getRecord(tr),
            specificData = record.getSpecificData(),
            serviceName = record.get('serviceName');

        if(Ext.Object.isEmpty(specificData)){
            return false;
        }

        var key, rows = '';

        // fileName shall always come first
        if(specificData.hasOwnProperty('fileName')){
            rows += this.createSpecificDataRow('fileName', serviceName, specificData.fileName);
        }

        if (specificData.hasOwnProperty('version') && typeof specificData.version === "object" && specificData.version.hasOwnProperty('version')) {
            rows += this.createSpecificDataRow('version', serviceName, specificData.version.version);
        }

        // then the others
        for (key in specificData) {
            if (key !== 'fileName' && key !== 'status' && key !== 'memories' && key !== 'version') {
                rows += this.createSpecificDataRow(key, serviceName, specificData[key]);
            }
        }

        // status shall always come last
        if(specificData.hasOwnProperty('status')){
            rows += this.createSpecificDataRow('status', serviceName, specificData.status);
        }

        tip.update('<table>' + rows + '</table>');
    },

    /**
     *
     * @param {string} key
     * @param {string} serviceName
     * @param {*} value
     * @returns {string}
     */
    createSpecificDataRow: function(key, serviceName, value){
        return '<tr><td>' +
            this.localizeSpecificDataKey(key, serviceName) +
            ': </td><td>' + Ext.String.htmlEncode(value) + '</td></tr>';
    },

    /**
     * Translates a key of the specific-data using the modern localization via Editor.data.l10n
     * @param {string} key
     * @param {string} serviceName
     * @returns {string}
     */
    localizeSpecificDataKey: function(key, serviceName){
        if(Editor.data.l10n.languageResources.specificData.hasOwnProperty(serviceName) &&
            Editor.data.l10n.languageResources.specificData[serviceName].hasOwnProperty(key)){
            return (Editor.data.l10n.languageResources.specificData[serviceName])[key];
        }
        if(Editor.data.l10n.languageResources.specificData.all.hasOwnProperty(key)){
            return Editor.data.l10n.languageResources.specificData.all[key];
        }
        return key.charAt(0).toUpperCase() + key.slice(1);
    },

    langRenderer : function(val, md) {
        if(!val || val.length<1){
            return '';
        }
        var label=[],
            retval=[];
        for(var i=0;i<val.length;i++){
            var lang = Ext.StoreMgr.get('admin.Languages').getById(val[i]);
            if (lang) {
                label.push(lang.get('label'));
                retval.push(lang.get('rfc5646'));
            }
        }
        md.tdAttr = 'data-qtip="' + label.join(',') + '"';
        return retval.join(',');
    },

    /**
     * Renders assigned customers to the resource by name
     */
    resourceCustomersRenderer:function(value,meta){
        if(!value || value.length<1){
            return '';
        }
        meta.tdAttr = 'data-qtip="'+this.getCustomersNames(value,true).join('</br>')+'"';
        return value.length;
    },

    /**
     * Renders the default assigned customer by name
     */
    defaultCustomersRenderer:function(value){
        if(!value || value.length<1){
            return '';
        }
        return this.getCustomersNames(value).join(',');
    },

    /**
     * Get customer names by customer id.
     * When addCustomerNumber is true, the customer number will be concatenate to the result in format [customerNumber] customerName
     */
    getCustomersNames:function(customerIds, addCustomerNumber){
        if(!customerIds || customerIds.length<1){
            return '';
        }
        
        var names = [], 
            customerStore = Ext.StoreManager.get('customersStore');
        
        Ext.Array.each(customerIds, function(id) {
            var rec = customerStore.getById(id);
            if(!rec) {
                return;
            }
            if(addCustomerNumber){
                names.push('['+rec.get('number')+'] '+rec.get('name'));
            }else{
                names.push(rec.get('name'));
            }
        });
        return names;
    },

    /***
     * TmOverview grid row select handler
     * @param grid TmOverview grid
     * @param selected Selected grid record. This record will also be reloaded.
     * @param callback Callback function called after record reload. On load failure, the record will be with status error.
     */
    onGridRowSelect:function(grid,selected,callback){
        if(selected.length<1){
            return;
        }
        var record = selected[0],
            status = record.get('status'),
            callbackCheck = function(r){
                if (typeof callback !== 'undefined' && typeof callback === 'function') {
                    callback(r);
                }
            };
        if(status !== record.STATUS_NOTCHECKED){
            callbackCheck(record);
            return;
        }
        record.set('status',record.STATUS_LOADING);
        record.load({
            failure: function(newRecord) {
                record.set('status',newRecord.STATUS_ERROR);
                callbackCheck(record);
            },
            success:function(loadedRecord){
                callbackCheck(loadedRecord);
            }
        });
    },

    /***
     * Custom specific data renderer
     * @param value
     * @param meta
     * @param record
     * @returns {string}
     */
    specificDataRenderer: function(value, meta, record) {
        if(!Ext.isEmpty(value)){
            meta.tdCls = 'gridColumnInfoIconTooltipCenter';
        }
        return '';
    }


});
