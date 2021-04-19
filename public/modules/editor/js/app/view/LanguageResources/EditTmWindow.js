
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

Ext.define('Editor.view.LanguageResources.EditTmWindow', {
    extend: 'Ext.window.Window',
    requires: [
        'Ext.ux.colorpick.Button',
        'Ext.ux.colorpick.Field',
        'Editor.view.admin.customer.TagField',
        'Editor.view.admin.customer.UserCustomersCombo',
        'Editor.view.LanguageResources.TmWindowViewModel',
        'Editor.view.LanguageResources.TmWindowViewController'
    ],
    controller: 'tmwindowviewcontroller',
    viewModel: {
        type: 'tmwindow'
    },
    alias: 'widget.editTmWindow',
    itemId: 'editTmWindow',
    strings: {
        edit: '#UT#Sprachressource hinzufügen',
        resource: '#UT#Ressource',
        name: '#UT#Name',
        source: '#UT#Quellsprache',
        target: '#UT#Zielsprache',
        file: '#UT#TM-Datei',
        color: '#UT#Farbe',
        colorTooltip: '#UT#Farbe dieser Sprachressource',
        save: '#UT#Speichern',
        cancel: '#UT#Abbrechen',
        customers:'#UT#Kunden',
        useAsDefault:'#UT#Leserechte standardmäßig',
        writeAsDefault:'#UT#Schreibrechte standardmäßig',
        collection:'#UT#TBX-Datei',
        importTbxType: '#UT#Bitte verwenden Sie eine TBX Datei!',
        categories: '#UT#Kategorien',
    },
    
    listeners:{
        render:'onTmWindowRender'
    },

    height : 500,
    width : 500,
    modal : true,
    layout:'fit',
    initConfig : function(instanceConfig) {
        var me = this,
        langField = {
                xtype: 'displayfield',
                renderer: function(value,field) {
                    if(!value){
                        return '';
                    }
                    var retval=[];
                    for(var i=0;i<value.length;i++){
                        var lang = Ext.StoreMgr.get('admin.Languages').getById(value[i]);
                        if (lang) {
                            retval.push(lang.get('rfc5646'));
                        }
                    }
                    return retval.join(',');
                }
            },
            config = {},
            defaults = {
                labelWidth: 160,
                anchor: '100%'
            },
        config = {
            title: me.strings.edit,
            items : [{
                xtype: 'form',
                padding: 5,
                ui: 'default-frame',
                defaults: defaults,
                autoScroll: true,
                items: [{
                    xtype: 'displayfield',
                    name:'resourceId',
                    id:'resourceId',
                    renderer: function(id) {
                        var store = Ext.getStore('Editor.store.LanguageResources.Resources'),
                            resource = store.getById(id);
                        return resource ? resource.get('name') : id;
                    },
                    fieldLabel: me.strings.resource
                },{
                    xtype: 'textfield',
                    name: 'name',
                    toolTip: me.strings.name,
                    fieldLabel: me.strings.name,
                    maxLength: 255,
                    allowBlank: false,
                    toolTip:'Name'
                },Ext.applyIf({
                    name: 'sourceLang',
                    toolTip: me.strings.source,
                    fieldLabel: me.strings.source
                }, langField),Ext.applyIf({
                    name: 'targetLang',
                    toolTip: me.strings.target,
                    fieldLabel: me.strings.target
                }, langField),{
                    xtype:'customers',
                    name:'customerIds[]',
                    listeners:{
                        change:'onCustomersTagFieldChange'
                    },
                    bind:{
                        store:'{customers}'
                    },
                    fieldLabel:me.strings.customers,
                    itemId:'resourcesCustomers',
                    dataIndex:'customerIds',
                    allowBlank: false,
                    reference:'resourcesCustomers',
                },{
                    xtype:'tagfield',
                    name:'customerUseAsDefaultIds[]',
                    listeners:{
                        change:'onCustomersReadTagFieldChange'
                    },
                    itemId:'useAsDefault',
                    bind:{
                        store:'{customersDefaultRead}'
                    },
                    fieldLabel:me.strings.useAsDefault,
                    reference:'useAsDefault',
                    displayField: 'name', 
                    valueField: 'id', 
                    queryMode: 'local', 
                    dataIndex:'customerUseAsDefaultIds'
                },{
                    xtype:'tagfield',
                    name:'customerWriteAsDefaultIds[]',
                    listeners:{
                        change:'onCustomersWriteTagFieldChange'
                    },
                    itemId:'writeAsDefault',
                    bind:{
                        store:'{customersDefaultWrite}'
                    },
                    fieldLabel:me.strings.writeAsDefault,
                    displayField: 'name', 
                    valueField: 'id', 
                    queryMode: 'local', 
                    dataIndex:'customerWriteAsDefaultIds'
                },{
                    xtype: 'colorfield',
                    fieldLabel: me.strings.color,
                    toolTip: me.strings.colorTooltip, 
                    labelWidth: 160,
                    anchor: '100%',
                    name: 'color'
                },{
                    // Categories: currently only active for Plugin
                    // (here: display the categories only, don't edit them
                    // after the LanguageResource has been created)
                    xtype: 'displayfield',
                    name:'categories',
                    id:'categories',
                    renderer: function(value) {
                        if(!value){
                            return '';
                        }
                        var retval=[];
                        for(var i=0;i<value.length;i++){
                            retval.push(value[i]);
                        }
                        return retval.join('<br>');
                    },
                    toolTip: me.strings.categories,
                    fieldLabel: me.strings.categories,
                    disabled: true
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
                    xtype: 'button',
                    glyph: 'f00c@FontAwesome5FreeSolid',
                    itemId: 'save-tm-btn',
                    text: me.strings.save
                }, {
                    xtype : 'button',
                    glyph: 'f00d@FontAwesome5FreeSolid',
                    itemId : 'cancel-tm-btn',
                    text : me.strings.cancel
                }]
            }]
        };
        if (instanceConfig) {
            me.self.getConfigurator().merge(me, config, instanceConfig);
        }
        return me.callParent([config]);
    },
    /**
     * loads the record into the form
     * @param record
     */
    loadRecord: function(record) {
        var me=this,
            vm=me.getViewModel();

        me.down('form').loadRecord(record);
        vm.set('serviceName',record.get('serviceName'));
    }
});