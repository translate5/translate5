
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
 * @class Editor.view.LanguageResources.TmOverviewPanel
 * @extends Ext.panel.Panel
 */
Ext.define('Editor.view.LanguageResources.TmOverviewPanel', {
    extend : 'Ext.grid.Panel',
    requires: ['Editor.view.admin.customer.CustomerFilter'],
    alias: 'widget.tmOverviewPanel',
    itemId: 'tmOverviewPanel',
    title:'#UT#Sprach-Resourcen',
    strings: {
        name: '#UT#Name',
        edit: '#UT#Bearbeiten',
        erase: '#UT#Löschen',
        tasks: '#UT#Zugewiesene Aufgaben',
        download: '#UT#Dateibasiertes TM herunterladen und lokal speichern',
        resource: '#UT#Ressource',
        color: '#UT#Farbe',
        refresh: '#UT#Aktualisieren',
        add: '#UT#Hinzufügen',
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
            import: '#UT#importiert',
            notloaded: '#UT#verfügbar',
            notchecked:'#UT#Nicht geprüft',
            novalidlicense: '#UT#Keine gültige Lizenz.'
        },
        customers:'#UT#Kunden',
        useAsDefault:'#UT#Standardmässig aktiv für',
        taskassocgridcell:'#UT#Zugewiesene Aufgaben',
        groupHeader: '#UT#Ressource: {name}'
    },
    cls:'tmOverviewPanel',
    height: '100%',
    layout: {
        type: 'fit'
    },
    initConfig: function(instanceConfig) {
        var me = this,
            config = {
                title: me.title, //see EXT6UPD-9
                store :'Editor.store.LanguageResources.LanguageResource',
                plugins: ['gridfilters'],
                viewConfig: {
                    getRowClass: function(record) {
                        //adds service specific handled css to the row 
                        var service = Editor.util.LanguageResources.getService(record.get('serviceName'));
                        return service.getTmOverviewRowCls(record).join(' ');
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
                        type: 'string',
                    },
                    text: me.strings.name
                },{
                    xtype: 'actioncolumn',
                    width: 98,
                    items: [{
                        tooltip: me.strings.edit,
                        action: 'edit',
                        iconCls: 'ico-tm-edit',
                        isDisabled: function( view, rowIndex, colIndex, item, record ) {
                            return record.get('status') == 'novalidlicense' ? true : false;
                        }
                    },{
                        tooltip: me.strings.erase,
                        action: 'delete',
                        iconCls: 'ico-tm-delete',
                        isDisabled: function( view, rowIndex, colIndex, item, record ) {
                            return record.get('status') == 'novalidlicense' ? true : false;
                        }
                    },{
                        tooltip: me.strings.tasks,
                        action: 'tasks',
                        iconCls: 'ico-tm-tasks',
                        isDisabled: function( view, rowIndex, colIndex, item, record ) {
                            return record.get('status') == 'novalidlicense' ? true : false;
                        }
                    },{
                        action: 'import',
                        iconCls: 'ico-tm-import',
                        isDisabled: function( view, rowIndex, colIndex, item, record ) {
                            return record.get('status') == 'novalidlicense' ? true : false;
                        },
	                    getTip:function(view,metadata,r,rowIndex,colIndex,store){
                            return Editor.util.LanguageResources.getService(r.get('serviceName')).getAddTooltip();
	                    }
                    },{
                        tooltip: me.strings.download,
                        action: 'download',
                        iconCls: 'ico-tm-download',
                        isDisabled: function( view, rowIndex, colIndex, item, record ) {
                            return record.get('status') == 'novalidlicense' ? true : false;
                        }
                    },{
                        action: 'import',
                        getClass:function(v,meta,record) {
                        	if(record.get('serviceName') != 'TermCollection'){
                        		return '';
                        	}
                        	meta.tdAttr = 'data-qtip="'+Editor.util.LanguageResources.getService(record.get('serviceName')).getExportTooltip()+'"';
                        	return 'ico-tm-export';
                        }
                    }],
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
                    dataIndex:'resourcesCustomers',
                    filter: {
                        type: 'customer' // [Multitenancy]
                    },
                    text:me.strings.customers,
                    renderer:me.resourceCustomersRenderer
                },{
                    xtype: 'gridcolumn',
                    width: 270,
                    dataIndex:'useAsDefault',
                    filter: {
                        type: 'string'
                    },
                    text:me.strings.useAsDefault,
                    tooltip:me.strings.useAsDefault,
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
                    renderer: function(v, meta, rec){
                        var tasks = [], i;
                        
                        if(!v || v.length == 0){
                            tasks.push(this.strings.noTaskAssigned);
                        }
                        else {
                            for(i = 0;i<v.length;i++){
                                tasks.push(v[i]);
                            }
                        }
                        meta.tdAttr = 'data-qtip="'+tasks.join('<br />')+'"';
                        return v.length;
                    }
                }],
                dockedItems: [{
                    xtype: 'toolbar',
                    dock: 'top',
                    items: [{
                        xtype: 'button',
                        iconCls: 'ico-tm-add',
                        itemId: 'btnAddTm',
                        text: me.strings.add,
                        tooltip: me.strings.add
                    },{
                        xtype: 'button',
                        iconCls: 'ico-refresh',
                        itemId: 'btnRefresh',
                        text: me.strings.refresh,
                        tooltip: me.strings.refresh
                    }]
                }]
      };

      if (instanceConfig) {
          me.self.getConfigurator().merge(me, config, instanceConfig);
      }
      return me.callParent([config]);
    },
    langRenderer : function(val, md) {
        if(!val || val.length<1){
            return '';
        }
        var label=[],
            retval=[];
        for(var i=0;i<val.length;i++){
            var lang = Ext.StoreMgr.get('admin.Languages').getById(val[i]),
                label;
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
    resourceCustomersRenderer:function(value,meta,record){
        if(!value || value.length<1){
            return '';
        }
        meta.tdAttr = 'data-qtip="'+this.getCustomersNames(value,true).join('</br>')+'"';
        return value.length;
    },

    /**
     * Renders the default assigned customer by name
     */
    defaultCustomersRenderer:function(value,meta,record){
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
            }
            names.push(rec.get('name'));
        });
        return names;
    },

    /***
     * Grid row select handler
     */
    onGridRowSelect:function(grid,selected){
        if(selected.length<1){
            return;
        }
        var record = selected[0],
            status = record.get('status');
        if(status != record.STATUS_NOTCHECKED){
            return;
        }
        record.set('status',record.STATUS_LOADING);
        record.load();
    }
});