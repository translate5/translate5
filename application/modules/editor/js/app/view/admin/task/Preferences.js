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
Ext.define('Editor.view.admin.task.Preferences', {
    extend: 'Ext.panel.Panel',
    alias: 'widget.editorAdminTaskPreferences',

    requires: [
        'Editor.view.admin.task.UserPrefsGrid',
        'Editor.view.admin.task.UserPrefsForm'
    ],

    layout: {
        type: 'border'
    },
    title: '#UT#My Tab',
    workflow_label: '#UT#Workflow',
    editInfo: '#UT#Wählen Sie einen Eintrag in der Tabelle aus um diesen zu bearbeiten!',

    initComponent: function() {
        var me = this,
            workflows = [];
            Ext.Object.each(Editor.data.app.workflows, function(key, item) {
                workflows.push([item.id, item.label]);
            });
        Ext.applyIf(me, {
            items: [{
                xtype: 'editorAdminTaskUserPrefsGrid',
                region: 'center'
            },{
                xtype: 'container',
                region: 'north',
                height: 50,
                padding: 10,
                items: [{
                    xtype: 'combobox',
                    itemId: 'taskWorkflow',
                    forceSelection: true,
                    editable: false,
                    fieldLabel: me.workflow_label,
                    value: me.actualTask.get('workflow'),
                    store: workflows
                }]
            },{
                xtype: 'container',
                region: 'east',
                autoScroll: true,
                height: 'auto',
                width: 250,
                items: [{
                    xtype: 'container',
                    itemId: 'editInfoOverlay',
                    cls: 'edit-info-overlay',
                    padding: 10,
                    html: me.editInfo
                },{
                    xtype: 'editorAdminTaskUserPrefsForm',
                    hidden: true
                }]
            }]
        });

        me.callParent(arguments);
    }
});
