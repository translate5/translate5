
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
        fieldNotEditContent: '#UT#Nur MQM-tags bearbeiten',
        fieldAnonymous: '#UT#anonymisierte Zieltextspalten',
        fieldVisibility: '#UT#Sichtbarkeit nicht editierbarer Zieltextspalten',
        btnSave: '#UT#Speichern',
        btnCancel: '#UT#Abbrechen',
        visShow: '#UT#Anzeigen',
        visHide: '#UT#Ausblenden',
        visDisabled: '#UT#nicht vorhanden'
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
                    store: [['','']],//dummy entry to get correct fields
                    anchor: '100%',
                    fieldLabel: me.strings.fieldStep
                },
                {
                    xtype: 'combobox',
                    anchor: '100%',
                    name: 'userGuid',
                    allowBlank: false,
                    forceSelection: true,
                    queryMode: 'local',
                    store: [['','']],//dummy entry to get correct fields
                    fieldLabel: me.strings.fieldUsername
                },{
                    xtype: 'checkboxfield',
                    anchor: '100%',
                    name: 'notEditContent',
                    boxLabel: me.strings.fieldNotEditContent
                },{
                    xtype: 'checkboxfield',
                    anchor: '100%',
                    name: 'anonymousCols',
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
                            iconCls : 'ico-save',
                            text: me.strings.btnSave
                        },
                        {
                            xtype: 'button',
                            itemId: 'cancelBtn',
                            iconCls : 'ico-cancel',
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
            checked = rec.get('fields').split(','),
            toSet = {},
            wfLabel,
            userLabel;
        this.fireEvent('beforeLoadRecord', this, rec);
        //set the field checkboxes by the stored string
        Ext.Array.each(fields, function(val) {
            toSet[val] = (Ext.Array.indexOf(checked, val) >= 0);
        });
        me.getForm()._record = rec;
        //manipulate the record data as needed
        me.getForm().setValues(Ext.applyIf({
            fields: rec.get('fields').split(','),
            workflowStep: ''
        }, rec.data));
        //set the userGuid separatly since we have first to calculate the entries by setting the workflowStep again
        me.getForm().setValues({
            workflowStep: rec.get('workflowStep') || FOR_ALL,
            userGuid: rec.get('userGuid') || FOR_ALL
        });
        wfLabel = me.down('combobox[name="workflowStep"]').getRawValue();
        userLabel = me.down('combobox[name="userGuid"]').getRawValue();
        me.setTitle(rec.phantom ? me.title_add : Ext.String.format(me.title_edit, wfLabel, userLabel));
    }
});
