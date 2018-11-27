
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

/**
 * @class Editor.controller.admin.Customer
 * @extends Ext.app.Controller
 */
Ext.define('Editor.controller.admin.Customer', {
    extend : 'Ext.app.Controller',

    views: [
        'Editor.view.admin.customer.Panel',
        'Editor.view.admin.customer.TagField',
    ],
    models:['Editor.model.admin.Customer'],
    stores:['Editor.store.admin.Customers','Editor.store.admin.UserCustomers'],

    refs:[{
        ref: 'customerPanel',
        selector: 'customerPanel'
    },{
        ref: 'centerRegion',
        selector: 'viewport container[region="center"]'
    },{
        ref: 'headToolBar',
        selector: 'headPanel toolbar#top-menu'
    }],

    listen: {
        component: {
            'headPanel toolbar#top-menu': {
                afterrender: 'onHeadPanelAfterRender'
            },
            '#btnCustomerOverviewWindow': {
                click: 'onCustomerOverviewClick'
            },
            '#customerSwitch': {
                change: 'onCustomerSwitch'
            },
            'customerPanel':{
                show: 'onCustomerPanelShow'
            },
            'viewport container[region="center"] panel':{
                hide:'onCentarPanelComponentAfterLayout'
            },
            '#adminUserAddWindow':{
                afterrender:'onAdminUserAddWindowAfterRender'
            },
            '#adminUserGrid': {
                beforerender:'onAdminUserGridBeforeRender'
            }
        },
        controller:{
            '#Editor.$application': {
                editorViewportOpen: 'onEditorViewportOpen'
            }
        },
        store:{
            '#admin.Users':{
                load:'onUserStoreLoad'
            }
        }
    },

    strings:{
        customer:'#UT#Kunden'
    },
    
    /***
     * hide the customers button when editor is opened
     */
    onEditorViewportOpen:function(){
        this.getHeadToolBar().down('#btnCustomerOverviewWindow').setHidden(true);
    },

    /**
     * On head panel after render handler
     */
    onHeadPanelAfterRender: function(toolbar) {
        //if we are in edit task mode, do not add the customer button
        if(Ext.ComponentQuery.query('#segmentgrid')[0]){
            return;
        }
        
        if(!this.isCustomerOverviewAllowed()){
            return;
        }
        var pos = toolbar.items.length - 2;
        toolbar.insert(pos, {
            xtype: 'button',
            itemId: 'btnCustomerOverviewWindow',
            text:this.strings.customer
        });
        
        // add the drop-down "Switch client"
       var pos = toolbar.items.length - 1;
        toolbar.insert(pos, {
            xtype: 'usercustomerscombo',
            allowBlank: true,
            itemId: 'customerSwitch',
            fieldLabel: ''
        });
    },

    /***
     * Fires when the components in this container are arranged by the associated layout manager.
     */
    onCentarPanelComponentAfterLayout:function(component){
        if(!this.isCustomerOverviewAllowed()){
            return;
        }
        //set the component to visible on each centar panel element hide
        this.setCustomerOverviewButtonHidden(false);
    },

    /**
     * "Switch client" drop-down change handler
     */
    onCustomerSwitch:function(combo, customerId){
        var tasks = Ext.StoreMgr.get('admin.Tasks');
        tasks.clearFilter();
        tasks.filter([{property: 'customerId', operator:'eq', value: customerId}]);
    },

    /**
     * Customer overview button click handler
     */
    onCustomerOverviewClick: function(window) {
        var me = this,
            panel = me.getCustomerPanel();
        
        me.actualTask = window.actualTask;
        
        //hide all components in the cennter region
        me.getCenterRegion().items.each(function(item){
            item.hide();
        });

        if(!panel) {
            //add the customer panel to the center region
            panel = me.getCenterRegion().add({xtype: 'customerPanel'});
        }

        panel.show();

        me.onCustomerPanelShow(panel);
    },

    /**
     * Admin add window after render handler
     */
    onAdminUserAddWindowAfterRender:function(adminWindow){
        if(!this.isCustomerOverviewAllowed()){
            return;
        }
        var me=this,
            loginFieldset=adminWindow.down('#loginDetailsFieldset');
        
        loginFieldset.add({
            xtype:'customers'
        });
    },

    /***
     * On admin user grid before render handler.
     */
    onAdminUserGridBeforeRender:function(taskgrid){
        if(!this.isCustomerOverviewAllowed()){
            return;
        }

        //insert the customer column in the user grid
        var me = this,
            grid = taskgrid.getView().grid,
            column;

        if(grid.down('gridcolumn[dataIndex=customers]')){
            return;
        }
        
        column = Ext.create('Ext.grid.column.Column', {
            xtype: 'gridcolumn',
            width: 250,
            dataIndex:'customers',
            tdCls: 'customers',
            sortable: false,
            cls: 'customers',
            text:this.strings.customer,
            filter: {
                type: 'string'
            },
            renderer: function(v, meta, rec){
                var names = [];
                if(!v || v.length == 0){
                    return '';
                }
                var v = v.replace(/(^,)|(,$)/g, ''),
                    customersStore=Ext.StoreManager.get('customersStore');
                v=v.split(',');
                for(i=0;i<v.length;i++){
                    var tmpRec=customersStore.findRecord('id',v[i],0,false,false,true);
                    tmpRec && names.push(tmpRec.get('name'));
                }
                
                return names.join(',');
            }
        });
        //insert the column after the locale column
        grid.headerCt.insert((grid.down('gridcolumn[dataIndex=locale]').fullColumnIndex + 1), column);
        grid.getView().refresh();
    },

    /***
     * On customer pannel show handler
     */
    onCustomerPanelShow: function(panel) {
        //set the help button data
        Editor.data.helpSection = 'customeroverview';
        Editor.data.helpSectionTitle = panel.getTitle();

        //hide the customerOverview button
        this.setCustomerOverviewButtonHidden(true);
    },

    /**
     * Set the customer overview button hidden property
     */
    setCustomerOverviewButtonHidden:function(isHidden){
        if(!this.getHeadToolBar() || !this.getHeadToolBar().down('#btnCustomerOverviewWindow')){
            return;
        }
        this.getHeadToolBar().down('#btnCustomerOverviewWindow').setHidden(isHidden);
    },

    /**
     * Check if the user has a frontend right to see the customer overview 
     */
    isCustomerOverviewAllowed:function(){
        return Editor.app.authenticatedUser.isAllowed('customerAdministration');
    },

    /**
     * Users store load handler
     */
    onUserStoreLoad:function(){
        //after the users store is loaded, load the usersCustomers store
        Ext.StoreManager.get('userCustomers').loadCustom();
    }
});