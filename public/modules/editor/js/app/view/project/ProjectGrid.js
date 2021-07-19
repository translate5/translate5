
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

Ext.define('Editor.view.project.ProjectGrid', {
	extend: 'Ext.grid.Panel',
	alias: 'widget.projectGrid',
	cls:'projectGrid',
    requires:[
    	'Editor.view.project.ProjectGridViewController'
	],
	controller:'projectGrid',
	itemId: 'projectGrid',
	strings: {
		addProject:'#UT#Projekt hinzufügen',
		addProjectTip:'#UT#Neues Projekt hinzufügen',
		reloadBtn: '#UT#Aktualisieren',
		reloadBtnTip: '#UT#Projektliste vom Server aktualisieren.'
		
	},
	text_cols: {
	      taskActions: '#UT#Aktionen',
	      customerId: '#UT#Endkunde',
	      taskName: '#UT#Name',
	      taskNr: '#UT#Auftragsnr.',
	      sourceLang: '#UT#Quellsprache',
	      pmGuid: '#UT#Projektmanager',
	      orderdate: '#UT#Bestelldatum',
	      description: '#UT#Projektbeschreibung',
		  id:'#UT#Id',
		  notFound: '#UT#nicht gefunden',
		  resetFilterText:'#UT#Filter zurücksetzen'
	},
	stateful:false,
	plugins: ['gridfilters'],
	store: 'project.Project',
	viewConfig: {
	      getRowClass: function(task) {
	          var res = [],
	              user = Editor.app.authenticatedUser,
	              actions = this.panel.availableActions;
	          Ext.Array.each(actions, function(action) {
	              if(user.isAllowed(action, task)) {
	                  res.push(action);
	              }
	          });
	          return res.join(' ');
	      }
	},
	//INFO: because the filters are not wirking when the projectGrid extends the taskGrid component,
    //the required columns,translations and renderer functions are duplicated here. With this the projectGrid does not depend on the taskGrid component.
    initConfig: function(instanceConfig) {
        var me = this,
        	config={
        		languageStore: Ext.StoreMgr.get('admin.Languages'),
        		columns:[{
        			xtype: 'gridcolumn',
        			width: 60,
        			dataIndex: 'id',
	                filter: {
	                    type: 'numeric'
	                },
	                text: me.text_cols.id
        		},{
                    text: me.text_cols.taskActions,
                    menuDisabled: true,//must be disabled, because of disappearing filter menu entry on missing filter
                    xtype: 'taskActionColumn',
                    stateId:'taskGridActionColumn',
                    sortable: false
        		},{
        			xtype: 'gridcolumn',
                    width: 220,
                    dataIndex: 'taskName',
                    stateId:'taskName',
                    filter: {
                        type: 'string'
                    },
                    text: me.text_cols.taskName
        		},{
        			xtype: 'gridcolumn',
                    width: 135,
                    renderer: me.customerRenderer,
                    dataIndex: 'customerId',
                    stateId: 'customerId',
                    filter: {
                        type: 'customer' // [Multitenancy]
                    },
                    text: me.text_cols.customerId
        		},{
        			xtype: 'gridcolumn',
                    width: 135,
                    dataIndex: 'pmName',
                    stateId: 'pmName',
                    filter: {
                        type: 'string'
                    },
                    renderer: function(v, meta,rec) {
                  	  var tooltip=v,
                  	  	  ret=v;
                  	  if(Editor.data.frontend.tasklist.pmMailTo){
                  		  tooltip=rec.get('pmMail');
                  		  ret='<a alt="'+tooltip+'" href="mailto:'+tooltip+'">'+v+'</a>';
                  		  meta.tdAttr = 'data-qtip="'+tooltip+'"';
                  	  }
                        return ret;
                    },
                    text: me.text_cols.pmGuid
        		},{
                    xtype: 'gridcolumn',
                    width: 110,
                    cls: 'source-lang',
                    renderer: me.langRenderer,
                    dataIndex: 'sourceLang',
                    stateId: 'sourceLang',
                    filter: {
                        type: 'list',
                        options: Editor.data.languages,
                        phpMode: false
                    },
                    tooltip: me.text_cols.sourceLang,
                    text: me.text_cols.sourceLang,
                    sortable: false
        		},{
                    xtype: 'gridcolumn',
                    width: 110,
                    dataIndex: 'taskNr',
                    stateId: 'taskNr',
                    filter: {
                        type: 'string'
                    },
                    tdCls: 'taskNr',
                    text: me.text_cols.taskNr
        		},{
                    xtype: 'gridcolumn',
                    width: 137,
                    dataIndex: 'description',
                    stateId: 'description',
                    filter: {
                        type: 'string'
                    },
                    tdCls: 'description',
                    text: me.text_cols.description
        		},{
                    xtype: 'datecolumn',
                    width: 100,
                    dataIndex: 'orderdate',
                    stateId: 'orderdate',
                    filter: {
                        type: 'date',
                        dateFormat: Editor.DATE_ISO_FORMAT
                    },
                    text: me.text_cols.orderdate
        		}],
        		dockedItems: [{
        	        xtype: 'toolbar',
        	        dock: 'top',
        	        items: [{
        	            xtype: 'button',
        	            glyph: 'f2f1@FontAwesome5FreeSolid',
        	            itemId: 'reloadProjectbtn',
        	            text: me.strings.reloadBtn,
        	            tooltip: me.strings.reloadBtnTip
        	        },{
        	            xtype: 'button',
        	            glyph: 'f067@FontAwesome5FreeSolid',
        	            itemId: 'add-project-btn',
        	            text: me.strings.addProject,
        	            hidden: ! Editor.app.authenticatedUser.isAllowed('editorAddTask'),
        	            tooltip: me.strings.addProjectTip
        	        },{
                        xtype: 'button',
                        glyph: 'f068@FontAwesome5FreeSolid',
                        itemId: 'resetFilterBtn',
                        text: me.strings.resetFilterText,
                        tooltip: me.strings.resetFilterText
                    }]
        		}]
        };
        if (instanceConfig) {
            me.self.getConfigurator().merge(me, config, instanceConfig);
        }
        return me.callParent([config]);
    },
    
    initComponent:function(){
    	var me=this;
    	me.callParent();
    	me.store.load();
    	me.configureActionColumn();
    },
    
    /**
     * renders the value (= names) of the customer column
     * @param {String} val
     * @returns {String}
     */
    customerRenderer: function(val, md, record) {
        var customer = record.get('customerName');
        if(customer){
            md.tdAttr = 'data-qtip="' + customer + ' (id: ' + val + ')"';
            return customer;
        }
        return this.strings.notFound;
    },
    
    /**
     * renders the value of the language columns
     * @param {String} val
     * @returns {String}
     */
    langRenderer: function(val, md) {
        var me = this,
            lang = me.languageStore.getById(val), 
            label;
        if(lang){
            label = lang.get('label');
            md.tdAttr = 'data-qtip="' + label + '"';
            return label;
        }
        if (!val || val == "0") {
            return '';
        }
        return me.strings.notFound;
    },
    
    /***
     * Configure the project action columns 
     */
    configureActionColumn:function(){
    	var me=this;
	    me.availableActions = ['editorMenuProject'];
    }
});
