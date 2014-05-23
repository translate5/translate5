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

    width: 250,
    bodyPadding: 10,
    title: '#UT#User Pref Details',

    initComponent: function() {
        var me = this;

        Ext.applyIf(me, {
            items: [
                {
                    xtype: 'combobox',
                    name: 'workflowStep',
                    forceSelection: true,
                    queryMode: 'local',
                    store: [['','']],//dummy entry to get correct fields
                    anchor: '100%',
                    fieldLabel: 'Workflow Step'
                },
                {
                    xtype: 'combobox',
                    anchor: '100%',
                    name: 'userGuid',
                    forceSelection: true,
                    queryMode: 'local',
                    store: [['','']],//dummy entry to get correct fields
                    fieldLabel: 'User'
                },{
                    xtype: 'checkboxfield',
                    anchor: '100%',
                    boxLabel: 'Anonymous Column Label'
                },{
                    xtype: 'fieldset',
                    itemId: 'alternates',
                    title: 'Alternative Access',
                    items: [{
                        xtype: 'checkboxgroup',
                        columns: 2
                    }]
                },{
                    xtype: 'fieldset',
                    title: 'Visibility of non-editable target columns',
                    items: [
                        {
                            xtype: 'radiofield',
                            anchor: '100%',
                            boxLabel: 'Box Label'
                        },
                        {
                            xtype: 'radiofield',
                            anchor: '100%',
                            boxLabel: 'Box Label'
                        },
                        {
                            xtype: 'radiofield',
                            anchor: '100%',
                            boxLabel: 'Box Label'
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
                            itemId: 'cancelBtn',
                            text: 'Cancel'
                        },
                        {
                            xtype: 'button',
                            itemId: 'saveBtn',
                            text: 'Save'
                        }
                    ]
                }
            ]
        });

        me.callParent(arguments);
    },
    loadRecord: function(rec) {
        var me = this,
            fields = me.actualTask.segmentFields().collect('name'),
            res = me.callParent(arguments),
            checked = rec.get('fields').split(','),
            toSet = {};
        Ext.Array.each(fields, function(val) {
            toSet[val] = (Ext.Array.indexOf(checked, val) >= 0);
        });
        me.getForm().setValues({fields: rec.get('fields').split(',')});
        return res;
    }
});