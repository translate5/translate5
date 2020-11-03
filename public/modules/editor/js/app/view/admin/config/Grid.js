
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

Ext.define('Editor.view.admin.config.Grid', {
    extend: 'Ext.grid.Panel',
    requires: [
        'Editor.view.admin.config.GridViewModel',
        'Editor.view.admin.config.GridViewController'
    ],
    controller: 'adminConfigGrid',
    viewModel:{
        type:'adminConfigGrid',
    },
    alias: 'widget.adminConfigGrid',
    itemId: 'adminConfigGrid',
    glyph: 'xf085@FontAwesome5FreeSolid',
    title:'#UT#Konfig überschreiben',
    store:{
        model:'Editor.model.Config',
        autoLoad:false
    },
    layout: {
        type: 'fit'
    },
    strings: {
        id:'#UT#Value',
        name:'#UT#Name',
        value:'#UT#Value',
        editRecordTooltip:'#UT#Konfiguration bearbeiten',
    },
    listeners:{
        rowdblclick:'onRowDblClick'
    },
    /***
     * Extra params property used for store proxy binding 
     * How to use:
     * {
            xtype: 'adminConfigGrid',
            store:{
                model:'Editor.model.TaskConfig',
                autoLoad:false,//it will be loaded when extraParam is set
            },
            bind:{
                extraParams:{
                    taskGuid : '{projectTaskSelection.taskGuid}'
                }
            }
        }
     */
    extraParams : [],
    publishes: {
        //publish this field so it is bindable
        extraParams: true
    },
    
    /***
     * allow the store extra params to be configurable on grid level. This will enable flexible loads via binding
     */
    setExtraParams:function(newExtra){
        if(!newExtra){
            return;
        }
        
        //check for empty values, and remove them. Loads for empty values is not required
        Ext.Object.each(newExtra, function(key, value, myself) {
            if(!value || value === "" || value === undefined){
                delete newExtra[key]
            }
        });
        
        if(Ext.Object.getSize(newExtra) < 1){
            this.getStore().removeAll();
            return;
        }
        
        var me=this,
            store = me.getStore(),
            existing = store.getProxy().getExtraParams(),
            merged = Ext.Object.merge(existing, newExtra);
        store.getProxy().setExtraParams(merged);
        store.setPageSize(0);//set the page size to 0, TODO: if pageing required remove me
        store.load();
    },
    
    initConfig: function(instanceConfig) {
        var me = this,
            config = {
                columns: [{
                    xtype: 'gridcolumn',
                    width: 230,
                    dataIndex: 'id',
                    filter: {
                        type: 'number'
                    },
                    text: me.strings.id
                },{
                    xtype: 'gridcolumn',
                    width: 230,
                    dataIndex: 'name',
                    filter: {
                        type: 'string'
                    },
                    text: me.strings.name
                },{
                    xtype: 'gridcolumn',
                    width: 230,
                    dataIndex: 'value',
                    text: me.strings.value
                },{
                    xtype: 'actioncolumn',
                    width: 30,
                    sortable: false,
                    menuDisabled: true,
                    items: [{
                        glyph: 'xf044@FontAwesome5FreeSolid',
                        tooltip: me.strings.editRecordTooltip,
                        handler: 'onEditClick'
                    }]
                }] 
            };
        if (instanceConfig) {
        	config=me.self.getConfigurator().merge(me, config, instanceConfig);
        }
        return me.callParent([config]);
    }
});