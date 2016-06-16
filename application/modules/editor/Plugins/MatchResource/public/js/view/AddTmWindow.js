
/*
START LICENSE AND COPYRIGHT

 This file is part of translate5
 
 Copyright (c) 2013 - 2015 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

 Contact:  http://www.MittagQI.com/  /  service (ATT) MittagQI.com

 This file may be used under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE version 3
 as published by the Free Software Foundation and appearing in the file agpl3-license.txt 
 included in the packaging of this file.  Please review the following information 
 to ensure the GNU AFFERO GENERAL PUBLIC LICENSE version 3.0 requirements will be met:
 http://www.gnu.org/licenses/agpl.html

 There is a plugin exception available for use with this release of translate5 for
 open source applications that are distributed under a license other than AGPL:
 Please see Open Source License Exception for Development of Plugins for translate5
 http://www.translate5.net/plugin-exception.txt or as plugin-exception.txt in the root
 folder of translate5.
  
 @copyright  Marc Mittag, MittagQI - Quality Informatics
 @author     MittagQI - Quality Informatics
 @license    GNU AFFERO GENERAL PUBLIC LICENSE version 3 with plugin-execptions
			 http://www.gnu.org/licenses/agpl.html http://www.translate5.net/plugin-exception.txt

END LICENSE AND COPYRIGHT
*/

Ext.define('Editor.plugins.MatchResource.view.AddTmWindow', {
    extend : 'Ext.window.Window',
    requires: ['Ext.ux.colorpick.Button'],
    alias : 'widget.addTmWindow',
    itemId : 'addTmWindow',
    cls : 'addTmWindow',
    height : 300,
    width : 500,
    modal : true,
    layout:'fit',
    editMode:false,
    initComponent: function() {
        var me = this;
        me.callParent(arguments);
    },
    initConfig : function(instanceConfig) {
        var me = this,
        langCombo = {
                xtype: 'combo',
                typeAhead: true,
                displayField: 'label',
                forceSelection: true,
                queryMode: 'local',
                valueField: 'id'
            },
            roles = [],
            config = {},
            defaults = {
                labelWidth: 160,
                anchor: '100%'
            },
        config = {
            title: 'Add TM',
            items : [{
                xtype: 'form',
                id:'addTmForm',
                padding: 5,
                ui: 'default-frame',
                defaults: defaults,
                items: [{
	                	xtype: 'combo',
	                    name:'resourceId',
	                    dataIndex:'resourceId',
	                    disabled : instanceConfig.editMode,
	                    typeAhead: true,
	                    forceSelection: true,
	                    queryMode: 'local',
	                    listeners: {
	                        select: me.serviceSelect
	                    },
	                    valueField: 'id',
	                    displayField: 'name',
	                    store:'Editor.plugins.MatchResource.store.Resources',
	                    fieldLabel: 'Resource'
                	},{
                    	xtype: 'textfield',
                        name: 'name',
                        maxLength: 255,
                        allowBlank: false,
                        toolTip:'Name',
                        fieldLabel: 'Name'
                    },Ext.applyIf({
                        name: 'sourceLang',
                        allowBlank: false,
                        toolTip: 'Source Language',
                        //each combo needs its own store instance, see EXT6UPD-8
                        store: Ext.create(Editor.store.admin.Languages),
                        fieldLabel:'Source Language'
                    }, langCombo),Ext.applyIf({
                        name: 'targetLang',
                        allowBlank: false,
                        toolTip: 'Target Language',
                        //each combo needs its own store instance, see EXT6UPD-8
                        store:Ext.create(Editor.store.admin.Languages),
                        fieldLabel: 'Target Language'
                    }, langCombo),
                    {
                    	xtype: 'hiddenfield',
                        name: 'serviceType',
                        dataIndex: 'serviceType',
                        maxLength: 255,
                        allowBlank: false
                    },{
                    	xtype: 'hiddenfield',
                        name: 'serviceName',
                        dataIndex: 'serviceName',
                        maxLength: 255,
                        allowBlank: false
	                },{
	                    xtype: 'colorfield',
	                    fieldLabel: 'Color Field',
	                    labelWidth: 160,
	                    anchor: '100%',
	                    name: 'color'
	                },{
	                    xtype: 'filefield',
	                    name: 'tmUpload',
	                    //regex: /\.(zip|sdlxliff|xlf|csv|testcase)$/i,
	                    //regexText: 'Wählen Sie die zu importierenden Daten (ZIP, CSV, SDLXLIFF; Angabe notwendig)',
	                    allowBlank: false,
	                    toolTip: 'Add File',
	                    disabled:true,
	                    fieldLabel: 'Add File'
	                  }]
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
                    iconCls :'ico-user-save',
                    itemId : 'save-tm-btn',
                    text : 'Save TM'
                }, {
                    xtype : 'button',
                    iconCls : 'ico-cancel',
                    itemId : 'cancel-tm-btn',
                    text : 'Cancel'
                }]
            }]
        };
        if (instanceConfig) {
            me.getConfigurator().merge(me, config, instanceConfig);
        }
        return me.callParent([config]);
    },
    /**
     * loads the record into the form, does set the role checkboxes according to the roles value
     * @param record
     */
    loadRecord: function(record) {
        this.down('form').loadRecord(record);
    },
    serviceSelect:function(combo, record, index){
    	var me = Ext.getCmp('addTmForm');
    	me.down('filefield').setDisabled(!record.get('filebased'));
    	me.down('hiddenfield[name="serviceType"]').setValue(record.get('serviceType'));
    	me.down('hiddenfield[name="serviceName"]').setValue(record.get('serviceName'));
    	me.down('colorfield[name="color"]').setValue(record.get('defaultColor'));
    }
});