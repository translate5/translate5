
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

Ext.define('Editor.view.project.ProjectGrid', {
	extend: 'Ext.grid.Panel',
	alias: 'widget.projectGrid',
	cls:'projectGrid',
    requires:[
    	'Editor.view.project.ProjectGridViewController'
	],
	controller:'projectGrid',
	itemId: 'projectGrid',
	stateful: true,
    stateId: 'editor.projectGrid',
    stateEvents: ['resize'], //currently we save sizes only!
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
            customColumns = Editor.controller.admin.TaskCustomField.getGridColumnsFor('projectGrid'),
        	config={
        		languageStore: Ext.StoreMgr.get('admin.Languages'),
        		columns:[{
                    xtype: 'gridcolumn',
                    width: 140,
                    dataIndex: 'taskGuid',
                    stateId: 'taskGuid',
                    hidden: true,
                    filter: {
                        type: 'string'
                    },
                    text: 'TaskGuid'
                },{
        			xtype: 'gridcolumn',
        			width: 60,
        			dataIndex: 'id',
	                filter: {
	                    type: 'numeric',
                        updateBuffer: 2000
                        //createMenu: function () {
                        //    this.menu = Ext.widget(this.getMenuConfig());
	                    //    console.log(this.menu, this, this.superclass, this.superclass.superclass);
	                        //console.log(this.superclass, arguments);
	                        //this.callOverridden();
	                        //this.superclass.createMenu.call(this);
                            //Ext.grid.filters.filter.prototype.createMenu.call(this);
                        //}
                    },
                    text: Editor.data.l10n.projectGrid.text_cols.id,
                    bind: {
                        text: '{l10n.projectGrid.text_cols.id}'
                    }
        		},{
                    menuDisabled: true,//must be disabled, because of disappearing filter menu entry on missing filter
                    xtype: 'taskActionColumn',
                    stateId:'taskGridActionColumn',
                    sortable: false,
                    text: Editor.data.l10n.projectGrid.text_cols.taskActions,
                    bind: {
                        text: '{l10n.projectGrid.text_cols.taskActions}'
                    }
        		},{
                    xtype: 'checkcolumn',
                    dataIndex: 'checked',
                    sortable: false,
                    bind: {
                        tooltip: '{l10n.projectGrid.strings.batchSetTooltip}'
                    },
                    width: 20
                },{
        			xtype: 'gridcolumn',
                    width: 220,
                    dataIndex: 'taskName',
                    stateId:'taskName',
                    filter: {
                        type: 'string'
                    },
                    text: Editor.data.l10n.projectGrid.text_cols.taskName,
                    bind: {
                        text: '{l10n.projectGrid.text_cols.taskName}'
                    },
                    renderer: v => Ext.String.htmlEncode(v)
        		},{
        			xtype: 'gridcolumn',
                    width: 135,
                    renderer: me.customerRenderer,
                    dataIndex: 'customerId',
                    stateId: 'customerId',
                    filter: {
                        type: 'customer' // [Multitenancy]
                    },
                    text: Editor.data.l10n.projectGrid.text_cols.customerId,
                    bind: {
                        text: '{l10n.projectGrid.text_cols.customerId}'
                    }
        		},{
        			xtype: 'gridcolumn',
                    width: 135,
                    dataIndex: 'pmName',
                    stateId: 'pmName',
                    filter: {
                        type: 'string'
                    },
                    renderer: function(v, meta,rec) {
                        var tooltip = Ext.String.htmlEncode(v),
                            ret = Ext.String.htmlEncode(v);

                        if (Editor.data.frontend.tasklist.pmMailTo) {
                            tooltip = Ext.String.htmlEncode(rec.get('pmMail'));
                            ret = '<a alt="'+tooltip+'" href="mailto:'+tooltip+'" target="_blank">' + ret + '</a>';
                            meta.tdAttr = 'data-qtip="'+Ext.String.htmlEncode(tooltip)+'"';
                        }

                        return ret;
                    },
                    text: Editor.data.l10n.projectGrid.text_cols.pmGuid,
                    bind: {
                        text: '{l10n.projectGrid.text_cols.pmGuid}'
                    }
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
                    text: Editor.data.l10n.projectGrid.text_cols.sourceLang,
                    bind: {
                        tooltip: '{l10n.projectGrid.text_cols.sourceLang}',
                        text: '{l10n.projectGrid.text_cols.sourceLang}',
                    },
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
                    text: Editor.data.l10n.projectGrid.text_cols.taskNr,
                    bind: {
                        text: '{l10n.projectGrid.text_cols.taskNr}',
                    }
        		},{
                    xtype: 'gridcolumn',
                    width: 137,
                    dataIndex: 'description',
                    stateId: 'description',
                    filter: {
                        type: 'string'
                    },
                    tdCls: 'description',
                    text: Editor.data.l10n.projectGrid.text_cols.description,
                    bind: {
                        text: '{l10n.projectGrid.text_cols.description}'
                    },
                    renderer: v => Ext.String.htmlEncode(v)
        		},{
                    xtype: 'datecolumn',
                    width: 100,
                    dataIndex: 'orderdate',
                    stateId: 'orderdate',
                    filter: {
                        type: 'date',
                        dateFormat: Editor.DATE_ISO_FORMAT
                    },
                    text: Editor.data.l10n.projectGrid.text_cols.orderdate,
                    bind: {
                        text: '{l10n.projectGrid.text_cols.orderdate}'
                    }
        		}].concat(customColumns),
        		dockedItems: [{
        	        xtype: 'toolbar',
        	        dock: 'top',
                    border: 0,
                    itemId: 'projectToolbar',
                    enableOverflow: true,
        	        items: [{
        	            xtype: 'button',
        	            glyph: 'f2f1@FontAwesome5FreeSolid',
        	            itemId: 'reloadProjectbtn',
                        bind: {
                            text: '{l10n.projectGrid.strings.reloadBtn}',
                            tooltip: '{l10n.projectGrid.strings.reloadBtnTip}'
                        }
        	        },{
        	            xtype: 'button',
        	            glyph: 'f067@FontAwesome5FreeSolid',
        	            itemId: 'add-project-btn',
                        listeners: {
                            // INFO: the drop event only works when is defined here.
                            drop: {
                                element: 'el',
                                fn: 'onAddProjectBtnDrop'
                            },
                            scope: 'controller'
                        },
                        bind: {
                            text: '{l10n.projectGrid.strings.addProject}',
                            tooltip: '{l10n.projectGrid.strings.addProjectTip}'
                        },
        	            hidden: ! Editor.app.authenticatedUser.isAllowed('editorAddTask'),
        	        },{
                        xtype: 'button',
                        glyph: 'f068@FontAwesome5FreeSolid',
                        itemId: 'resetFilterBtn',
                        bind: {
                            text: '{l10n.projectGrid.strings.resetFilterText}',
                            tooltip: '{l10n.projectGrid.strings.resetFilterText}'
                        }
                    }, {
        	            xtype: 'button',
                        itemId: 'onlyMyProjects',
                        enableToggle: true,
                        bind: {
        	                text: '{l10n.projectGrid.strings.onlyMyProjects}'
                        }
                    }, {
                        xtype: 'button',
                        itemId: 'batch-set-btn',
                        bind: {
                            text: '{l10n.projectGrid.strings.batchSetProperties}'
                        }
                    }]
        		}]
        };
        if (instanceConfig) {
            me.self.getConfigurator().merge(me, config, instanceConfig);
        }
        return me.callParent([config]);
    },

    /**
     * Scrolle the given task row into view
     *
     * @param {Integer} rowindex
     * @param {Object} config
     */
    scrollTo: function(rowindex, config) {
        if(rowindex < 0 || this.getStore().isLoading()) {
            return;
        }
        if(!config){
            config = {};
        }
        var me = this;
        me.ensureVisible(rowindex, config);
    },

    initComponent:function(){
    	var me=this;
    	me.callParent();
    	me.configureActionColumn();
    },
    
    /**
     * renders the value (= names) of the customer column
     * @param {String} val
     * @returns {String}
     */
    customerRenderer: function(val, md, record) {
        var customer = Ext.String.htmlEncode(record.get('customerName'));

        if (customer) {
            md.tdAttr = 'data-qtip="' + Ext.String.htmlEncode(customer) + ' (id: ' + val + ')"';

            return customer;
        }

        return Editor.data.l10n.projectGrid.strings.notFound;
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
        return Editor.data.l10n.projectGrid.strings.notFound;
    },
    
    /***
     * Configure the project action columns 
     */
    configureActionColumn:function(){
    	var me=this;
	    me.availableActions = ['editorMenuProject'];
    }
});
