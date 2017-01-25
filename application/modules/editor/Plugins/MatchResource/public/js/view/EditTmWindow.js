
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

Ext.define('Editor.plugins.MatchResource.view.EditTmWindow', {
    extend: 'Ext.window.Window',
    requires: [
        'Ext.ux.colorpick.Button',
        'Ext.ux.colorpick.Field'
    ],
    alias: 'widget.editTmWindow',
    itemId: 'editTmWindow',
    strings: {
        edit: '#UT#Matchressource hinzufügen',
        resource: '#UT#Ressource',
        name: '#UT#Name',
        source: '#UT#Quellsprache',
        target: '#UT#Zielsprache',
        file: '#UT#TM-Datei',
        color: '#UT#Farbe',
        colorTooltip: '#UT#Farbe dieser Matchressource',
        save: '#UT#Speichern',
        cancel: '#UT#Abbrechen'
    },
    height : 300,
    width : 500,
    modal : true,
    layout:'fit',
    editMode:false,
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
            roles = [],
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
                        var store = Ext.getStore('Editor.plugins.MatchResource.store.Resources'),
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
                    iconCls:'ico-user-save',
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
     * loads the record into the form, does set the role checkboxes according to the roles value
     * @param record
     */
    loadRecord: function(record) {
        this.down('form').loadRecord(record);
    }
});