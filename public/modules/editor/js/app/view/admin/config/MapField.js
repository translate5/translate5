
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

Ext.define('Editor.view.admin.config.MapField', {
    extend: 'Ext.form.Panel',
    requires:[
        'Editor.view.admin.config.MapFieldViewController',
        'Editor.view.admin.config.MapFieldViewModel',
    ],
    alias: 'widget.adminTaskMapField',
    itemId: 'adminTaskMapField',
    controller: 'adminTaskMapField',
    viewModel:{
        type:'adminTaskMapField'
    },
    title: '#UT#Konfiguration bearbeiten',
    strings: {
        
    },
    configValue : [],
    publishes: {
        //publish this field so it is bindable
        configValue: true
    },
    
    setConfigValue:function(record){
        var me=this,
            vm = me.getViewModel(),
            data = [];
        Ext.Object.each(record, function(k, v, myself) {
            data.push({
                id:k,
                value:v,
            })
        });
        vm.set('configValue',data);
        me.configValue = data;
    },
    
    getConfigValue:function(){
        debugger;
        return this.configValue;
    },
    
    initConfig: function(instanceConfig) {
        var me = this,
            config = {
                title: me.title,//see EXT6UPD-9
                items:[{
                    xtype: 'textfield',
                    bind:'{result.selection.id}',
                    name: 'id',
                    itemId: 'id',
                    fieldLabel: 'Id'
                },{
                    xtype: 'textfield',
                    bind:'{result.selection.value}',
                    name: 'value',
                    itemId: 'value',
                    fieldLabel: 'Value'
                },{
                    xtype: 'button',
                    name: 'save',
                    itemId:'save',
                    text: 'Save'
                },{
                    xtype:'grid',
                    name:'result',
                    itemId:'result',
                    reference:'result',
                    bind:{
                        store:'{configValue}'
                    },
                    columns: [{ 
                            text: 'Id',
                            dataIndex: 'id',
                            renderer:function(){
                                console.log(arguments);
                            }
                        },{ 
                            text: "Value", 
                            dataIndex: 'value'
                        },{
                            xtype: 'actioncolumn',
                            sortable: false,
                            menuDisabled: true,
                            items: [{
                                glyph: 'f2ed@FontAwesome5FreeSolid',
                                tooltip: me.strings.editRecordTooltip,
                                handler: 'onActionColumnDeleteRecordClick'
                            }]
                        }
                    ]
                }]
            };
        if (instanceConfig) {
            me.self.getConfigurator().merge(me, config, instanceConfig);
        }
        return me.callParent([config]);
    }
});