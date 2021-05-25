
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

Ext.define('Editor.view.admin.task.UserPrefsForm', {
    extend: 'Ext.form.Panel',
    alias: 'widget.editorAdminTaskUserPrefsForm',
    bodyPadding: 10,
    autoScroll: true,
    title_edit: '#UT#Bearbeite Eintrag: "{0} - {1}"',
    title_add: '#UT#Eintrag erstellen',
    strings: {
        fieldStep: '#UT#Workflow Schritt',
        fieldUsername: '#UT#Benutzer',
        fieldTargets: '#UT#vorhandene Spalten',
        fieldNotEditContent: '#UT#Nur manuelle QS im Segment bearbeiten',
        fieldAnonymous: '#UT#anonymisierte Zieltextspalten',
        fieldVisibility: '#UT#Sichtbarkeit nicht editierbarer Zieltextspalten',
        btnSave: '#UT#Speichern',
        btnCancel: '#UT#Abbrechen',
        visShow: '#UT#Anzeigen',
        visHide: '#UT#Ausblenden',
        visDisabled: '#UT#nicht vorhanden',
        forAll: '#UT#für alle'
    },

    initConfig: function(instanceConfig) {
        var me = this,
        config;

        config = {
            items: [
                {
                    xtype: 'combobox',
                    name: 'workflowStep',
                    allowBlank: false,
                    forceSelection: true,
                    editable: false,
                    queryMode: 'local',
                    reference:'workflowstep',
                    displayField:'label',
                    valueField:'id',
                    publishes:'value',
                    store:Ext.create('Ext.data.Store', {
                    	fields:['id', 'label','role'],
                    }),
                    anchor: '100%',
                    fieldLabel: me.strings.fieldStep
                },
                {
                    xtype: 'combobox',
                    anchor: '100%',
                    name: 'taskUserAssocId',
                    reference:'taskUserAssoc',
                    queryMode: 'local',
                    valueField:'id',
                    displayTpl: '<tpl for=".">{surName}, {firstName} ({login}) ({role})</tpl>',
                    listConfig: {
                        itemTpl: [
                            '<div>{surName}, {firstName} ({login}) ({role})</div>'
                        ]
                    },
                    emptyText:me.strings.forAll,
                    bind:{
                    	store:'{UserAssocStore}'
                    },
                    fieldLabel: me.strings.fieldUsername
                },{
                    xtype: 'checkboxfield',
                    anchor: '100%',
                    name: 'notEditContent',
                    boxLabel: me.strings.fieldNotEditContent
                },{
                	xtype: 'hiddenfield',
                    dataIndex: 'userGuid',
                    name: 'userGuid',
                    bind:{
                    	value:'{taskUserAssoc.selection.userGuid}'
                    }
                },{
                    xtype: 'checkboxfield',
                    anchor: '100%',
                    name: 'anonymousCols',
                    inputValue: 1,
                    uncheckedValue: 0,
                    boxLabel: me.strings.fieldAnonymous
                },{
                    xtype: 'fieldset',
                    itemId: 'alternates',
                    title: me.strings.fieldTargets,
                    items: [{
                        xtype: 'checkboxgroup',
                        columns: 2
                    }]
                },{
                    xtype: 'fieldset',
                    title: me.strings.fieldVisibility,
                    items: [
                        {
                            xtype: 'radiofield',
                            name: 'visibility',
                            anchor: '100%',
                            inputValue: 'show',
                            boxLabel: me.strings.visShow
                        },
                        {
                            xtype: 'radiofield',
                            name: 'visibility',
                            anchor: '100%',
                            inputValue: 'hide',
                            boxLabel: me.strings.visHide
                        },
                        {
                            xtype: 'radiofield',
                            name: 'visibility',
                            anchor: '100%',
                            inputValue: 'disable',
                            boxLabel: me.strings.visDisabled
                        }
                    ]
                }
            ],
            dockedItems: [
                {
                    xtype: 'toolbar',
                    dock: 'bottom',
                    ui: 'footer',
                    items: [
                        {
                            xtype: 'tbfill'
                        },
                        {
                            xtype: 'button',
                            itemId: 'saveBtn',
                            glyph: 'f00c@FontAwesome5FreeSolid',
                            text: me.strings.btnSave
                        },
                        {
                            xtype: 'button',
                            itemId: 'cancelBtn',
                            glyph: 'f00d@FontAwesome5FreeSolid',
                            text: me.strings.btnCancel
                        }
                    ]
                }
            ]
        };

        if (instanceConfig) {
            me.self.getConfigurator().merge(me, config, instanceConfig);
        }
        return me.callParent([config]);
    },
    /**
     * sets the values from the given record into the form
     * @param {Editor.model.admin.task.UserPref} rec
     * @param {String} FOR_ALL the value to be used for null steps and users
     */
    loadRecord: function(rec, FOR_ALL) {
        var me = this,
            task = me.lookupViewModel().get('currentTask'),
            fields = task.segmentFields().collect('name'),
            wfLabel,
            userLabel;
        this.fireEvent('beforeLoadRecord', this, rec);
        me.getForm().loadRecord(rec);
        me.getForm().setValues({
        	fields: rec.get('fields').split(',')
        });
        wfLabel = me.down('combobox[name="workflowStep"]').getRawValue();
        userLabel = me.down('hiddenfield[name="userGuid"]').getRawValue();
        me.setTitle(rec.phantom ? me.title_add : Ext.String.format(me.title_edit, wfLabel, userLabel));
    }
});
