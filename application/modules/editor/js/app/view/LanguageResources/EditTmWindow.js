
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
        'Editor.view.admin.customer.UserCustomersCombo'
    ],
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
        defaultCustomer:'#UT#Standardkunde'
    },
    
    height : 420,
    width : 500,
    modal : true,
    layout:'fit',
    initConfig : function(instanceConfig) {
        var me = this,
        langField = {
                xtype: 'displayfield',
                renderer: function(id) {
                    var store = Ext.getStore('admin.Languages'),
                        resource = store.getById(id);
                    return resource ? resource.get('label') : id;
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
                items: [{
                    xtype: 'displayfield',
                    name:'resourceId',
                    renderer: function(id) {
                        var store = Ext.getStore('Editor.store.LanguageResources.Resources'),
                            resource = store.getById(id);
                        return resource ? resource.get('name') : id;
                    },
                    fieldLabel: me.strings.resource
                },{
                    xtype: 'displayfield',
                    name: 'name',
                    toolTip: me.strings.name,
                    fieldLabel: me.strings.name
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
                    fieldLabel:me.strings.customers,
                    itemId:'resourcesCustomers',
                    name:'resourcesCustomers',
                    dataIndex:'resourcesCustomers',
                    store:'userCustomers'
                },{
                    xtype:'hiddenfield',
                    name:'resourcesCustomersHidden'
                },{
                    xtype:'usercustomerscombo',
                    name:'defaultCustomer',
                    fieldLabel:me.strings.defaultCustomer,
                    dataIndex:'defaultCustomer'
                },{
                    xtype: 'colorfield',
                    fieldLabel: me.strings.color,
                    toolTip: me.strings.colorTooltip, 
                    labelWidth: 160,
                    anchor: '100%',
                    name: 'color'
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
                    iconCls:'ico-save',
                    itemId: 'save-tm-btn',
                    text: me.strings.save
                }, {
                    xtype : 'button',
                    iconCls : 'ico-cancel',
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
        this.down('form').loadRecord(record);
    }
});