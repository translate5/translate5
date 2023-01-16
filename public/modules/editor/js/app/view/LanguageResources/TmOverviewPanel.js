
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
    title:'#UT#Sprachressourcen',
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
        noTaskAssigned:'#UT#Keine Aufgaben zugewiesen.',
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
            notchecked:'#UT#Nicht geprüft',
            novalidlicense: '#UT#Keine gültige Lizenz.'
        },
        customers:'#UT#Kunden',
        useAsDefault:'#UT#Leserechte standardmäßig',
        writeAsDefault:'#UT#Schreibrechte standardmäßig',
        taskassocgridcell:'#UT#Zugewiesene Aufgaben',
        groupHeader: '#UT#Ressource: {name}',
        specificDataText:'#UT#Zusätzliche Infos',
        pivotAsDefault:'#UT#Standardmäßig als Pivot verwenden'
    },
    cls:'tmOverviewPanel',
    height: '100%',
    layout: {
        type: 'fit'
    },

    tooltip:null,

    initConfig: function(instanceConfig) {
        var me = this,
            service = function(rec) {
                return Editor.util.LanguageResources.getService(rec.get('serviceName'));
            },
            config = {
                title: me.title, //see EXT6UPD-9
                store :'Editor.store.LanguageResources.LanguageResource',
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
                    ftype:'grouping',
                    hideGroupedHeader: true,
                    enableGroupingMenu: false
                }],
                columns: [{
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
                    width: 120,
                    items: [{
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
                        isDisabled: function( view, rowIndex, colIndex, item, record ) {
                            return record.get('status') === 'novalidlicense' ? true : false;
                        }
                    },{
                        tooltip: me.strings.tasks,
                        action: 'tasks',
                        iconCls: 'ico-tm-tasks',
                        hidden:true,
                        isDisabled: function( view, rowIndex, colIndex, item, record ) {
                            item.hidden=!record.get('writable');
                        }
                    },{
                        action: 'import',
	                    getClass:function(v,meta,r) {
                        	return service(r).getImportIconClass(r);
                        },
	                    getTip:function(view,metadata,r){
                            return service(r).getAddTooltip(r);
	                    }
                        
                    },{
                        action: 'download',
                        getClass:function(v,meta,r) {
                        	return service(r).getDownloadIconClass(r);
                        },
	                    getTip:function(view,metadata,r){
	                    	return service(r).getDownloadTooltip(r);
	                    }
                    },{
                        action: 'export',
                        getClass:function(v,meta,r) {
                        	return service(r).getExportIconClass(r);
                        },
	                    getTip:function(view,metadata,r){
	                    	return service(r).getExportTooltip(r);
	                    }
                    },{
                        tooltip: me.strings.log,
                        action: 'log',
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
                    dataIndex:'customerIds',
                    filter: {
                        type: 'customer' // [Multitenancy]
                    },
                    text:me.strings.customers,
                    renderer:me.resourceCustomersRenderer
                },{
                    xtype: 'gridcolumn',
                    width: 270,
                    dataIndex:'customerUseAsDefaultIds',
                    filter: {
                        type: 'string'
                    },
                    text:me.strings.useAsDefault,
                    tooltip:me.strings.useAsDefault,
                    renderer:me.defaultCustomersRenderer
                },
                {
                    xtype: 'gridcolumn',
                    width: 270,
                    dataIndex:'customerWriteAsDefaultIds',
                    filter: {
                        type: 'string'
                    },
                    text:me.strings.writeAsDefault,
                    tooltip:me.strings.writeAsDefault,
                    renderer:me.defaultCustomersRenderer
                },
                {
                    xtype: 'gridcolumn',
                    width: 270,
                    dataIndex:'customerPivotAsDefaultIds',
                    filter: {
                        type: 'string'
                    },
                    text:me.strings.pivotAsDefault,
                    tooltip:me.strings.pivotAsDefault,
                    renderer:me.defaultCustomersRenderer
                },
                {
                    xtype: 'gridcolumn',
                    width: 100,
                    dataIndex: 'color',
                    renderer: function(value, metaData, record) {
                        return '<div style="float: left; width: 15px; height: 15px;margin-right:5px; border: 1px solid rgba(0, 0, 0, .2);background: #'+record.data.color+';"></div>';
                    },
                    text: me.strings.color
                },{
                    xtype: 'gridcolumn',
                    text:me.strings.specificDataText,
                    width: 160,
                    tdCls:'specificData',
                    renderer:me.specificDataRenderer,
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
                            meta.tdAttr = 'data-qtip="'+str.loading+'"';
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
                    xtype:'gridcolumn',
                    width: 40,
                    dataIndex:'taskList',
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
                    items: [{
                        xtype: 'button',
                        glyph: 'f2f1@FontAwesome5FreeSolid',
                        itemId: 'btnRefresh',
                        text: me.strings.refresh,
                        tooltip: me.strings.refresh
                    },{
                        xtype: 'button',
                        glyph: 'f067@FontAwesome5FreeSolid',
                        itemId: 'btnAddTm',
                        text: me.strings.addResource,
                        tooltip: me.strings.addResource
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
        me.statisticTpl = new Ext.XTemplate(
            '<table>',
            '<tpl for=".">',
            '<tr><td>{text}: </td><td>{value}</td></tr>',
            '</tpl>',
            '</table>');
        me.callParent(arguments);
        me.view.on('afterrender', function () {
            me.tooltip = me.createToolTip();
            me.tooltip.on({
                beforeshow:{
                    scope:me,
                    fn:me.onSpecificDataTooltipBeforeShow
                }
            });
        });

    },

    onDestroy: function () {
        if (this.tooltip) {
            this.tooltip.destroy();
        }
        this.callParent(arguments);
    },


    /***
     * Tooltip for specificData column. This will fill the template with data based on the user mouse:hover column
     * @returns {Ext.tip.ToolTip}
     */
    createToolTip: function () {
        var me = this;
        return Ext.create('Ext.tip.ToolTip', {
            target: me.view.el,
            delegate: 'td.specificData',
            dismissDelay: 0,
            showDelay: 200,
            maxWidth: 1000,
            renderTo: Ext.getBody()
        });
    },

    /***
     * On specificData column before tooltip show event handler
     * @param tip
     * @returns {boolean}
     */
    onSpecificDataTooltipBeforeShow: function updateTipBody(tip) {
        var me=this,
            tr = Ext.fly(tip.triggerElement).up('tr'),
            record = me.view.getRecord(tr),
            value = record.get('specificData');

        if(Ext.isEmpty(value)){
            return false;
        }
        tip.update(me.statisticTpl.apply(Ext.JSON.decode(value)));
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
    specificDataRenderer: function(value, meta) {
        if(!Ext.isEmpty(value)){
            meta.tdCls = 'gridColumnInfoIconTooltipCenter';
        }
        return '';
    }


});
