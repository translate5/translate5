
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

Ext.define('Editor.view.admin.config.Editor', {
    extend: 'Ext.window.Window',
    requires:[
        'Editor.view.admin.config.EditorViewController',
        'Editor.view.admin.config.EditorViewModel',
        'Editor.view.admin.config.MapField'
    ],
    alias: 'widget.adminConfigEditor',
    itemId: 'adminConfigEditor',
    controller: 'adminConfigEditor',
    viewModel:{
        type:'adminConfigEditor'
    },
    title: '#UT#Konfiguration bearbeiten',
    strings: {
        saveConfigBtn : '#UT#Speichern',
        cancelConfigBtn : '#UT#Abbrechen',
        name : '#UT#Name',
        value : '#UT#Value'
    },
    height : 400,
    width : 400,
    modal : true,
    layout: 'anchor',
    autoScroll: true,
    
    initConfig: function(instanceConfig) {
        var me = this,
            config = {
                title: me.title, //see EXT6UPD-9
                items:[{
                    xtype: 'displayfield',
                    maxLength: 120,
                    name: 'name',
                    bind:'{record.name}',
                    fieldLabel: me.strings.name
                }],
                dockedItems : [{
                    xtype : 'toolbar',
                    dock : 'bottom',
                    ui: 'footer',
                    layout: {
                        type: 'hbox',
                        pack: 'start'
                    },
                    items : [{
                        xtype: 'tbfill'
                    },{
                        xtype : 'button',
                        glyph: 'f00c@FontAwesome5FreeSolid',
                        itemId : 'saveConfigBtn',
                        text : me.strings.saveConfigBtn
                    }, {
                        xtype : 'button',
                        glyph: 'f00d@FontAwesome5FreeSolid',
                        itemId : 'cancelConfigBtn',
                        text : me.strings.cancelConfigBtn
                    }]
                }]
            };
        if (instanceConfig) {
            me.self.getConfigurator().merge(me, config, instanceConfig);
        }
        return me.callParent([config]);
    },
    
    /***
     */
    loadRecord: function(record) {
        var me=this;
        me.renderValueField(record);
        me.getViewModel().set('record',record);
    },
    
    /***
     * 
     */
    renderValueField:function(record){
        var me=this,
            config={
                xtype: 'textfield',
                maxLength: 120,
                name: 'value',
                fieldLabel: me.strings.value
            };
        switch(record.get('type')){
            case 'boolean':
            case 'bool':
                config={
                    xtype: 'checkboxfield',
                    uncheckedValue:0,
                    maxLength: 120,
                    name: 'value',
                    bind:'{record.value}',
                    fieldLabel: me.strings.value
                };
                break;
            case 'map':
                config={
                    xtype:'adminTaskMapField',
                    bind:{
                        configValue:'{record.value}'
                    }
                };
                break;
            case 'list':
                config={
                    xtype: 'tagfield',
                    fieldLabel: me.strings.value,
                    name: 'value',
                    store:[],//this will init the tagfield withe empty store on creation.
                    bind:{
                        value:'{record.value}',
                        store:'{record.defaults}',//as selectable options if defined
                        createNewOnEnter:'{!configHasDefaults}'
                    },
                    queryMode: 'local',
                    createNewOnEnter:true,
                    filterPickList: true,
                    setCreateNewOnEnter:function(newValue){
                        this.createNewOnEnter = newValue;
                    },
                    getCreateNewOnEnter:function(){
                        return this.createNewOnEnter;
                    },
                };
                break;
        }
        me.add(config);
    }
});