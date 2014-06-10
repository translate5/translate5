/*
 START LICENSE AND COPYRIGHT
 
 This file is part of Translate5 Editor Javascript GUI and build on ExtJs 4 lib
 
 Copyright (c) 2013 Marc Mittag; MittagQI - Quality Informatics; All rights reserved.
 
 Contact:  http://www.MittagQI.com/  /  service (ÄTT) MittagQI.com
 
 This file may be used under the terms of the GNU General Public License version 3.0
 as published by the Free Software Foundation and appearing in the file gpl3-license.txt 
 included in the packaging of this file.  Please review the following information 
 to ensure the GNU General Public License version 3.0 requirements will be met:
 http://www.gnu.org/copyleft/gpl.html.

 For this file you are allowed to make use of the same FLOSS exceptions to the GNU 
 General Public License version 3.0 as specified by Sencha for Ext Js. 
 Please be aware, that Marc Mittag / MittagQI take no warranty
 for any legal issue, that may arise, if you use these FLOSS exceptions and recommend
 to stick to GPL 3. For further information regarding this topic please see the attached 
 license.txt of this software package.
 
 MittagQI would be open to release translate5 under EPL or LGPL also, if this could be
 brought in accordance with the ExtJs license scheme. You are welcome to support us
 with legal support, if you are interested in this.
 
 
 @copyright  Marc Mittag, MittagQI - Quality Informatics
 @author     MittagQI - Quality Informatics
 @license    GNU General Public License version 3.0 http://www.gnu.org/copyleft/gpl.html
             with FLOSS exceptions (see floss-exception.txt and ux-exception.txt at the root level)
 
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
        fieldAnonymous: '#UT#anonymisierte Zieltextspalten',
        fieldVisibility: '#UT#Sichtbarkeit der nicht editierbaren Zielsprachen',
        btnSave: '#UT#Speichern',
        btnCancel: '#UT#Abbrechen',
        visShow: '#UT#Anzeigen',
        visHide: '#UT#Ausblenden',
        visDisabled: '#UT#nicht vorhanden'
    },

    initComponent: function() {
        var me = this;

        Ext.applyIf(me, {
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
                            text: me.strings.save
                        },
                        {
                            xtype: 'button',
                            itemId: 'cancelBtn',
                            text: me.strings.cancel
                        }
                    ]
                }
            ]
        });

        me.callParent(arguments);
    },
    /**
     * sets the values from the given record into the form
     * @param {Editor.model.admin.task.UserPref} rec
     * @param {String} FOR_ALL the value to be used for null steps and users
     */
    loadRecord: function(rec, FOR_ALL) {
        var me = this,
            fields = me.actualTask.segmentFields().collect('name'),
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
        wfLabel = me.down('.combobox[name="workflowStep"]').getRawValue();
        userLabel = me.down('.combobox[name="userGuid"]').getRawValue();
        me.setTitle(rec.phantom ? me.title_add : Ext.String.format(me.title_edit, wfLabel, userLabel));
    }
});