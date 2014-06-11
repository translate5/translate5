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
Ext.define('Editor.view.admin.task.UserPrefsGrid', {
    extend: 'Ext.grid.Panel',
    alias: 'widget.editorAdminTaskUserPrefsGrid',
    store: 'admin.task.UserPrefs',

    strings: {
        defaultEntry: '#UT# Standard Eintrag',
        confirmDeleteTitle: '#UT#Eintrag löschen?',
        confirmDelete: '#UT#Soll dieser Eintrag wirklich gelöscht werden?',
        colStep: '#UT#Workflow Schritt',
        colUsername: '#UT#Benutzer',
        colTargets: '#UT#Spalten (vorhanden)',
        colAnonymous: '#UT#Anonymisiert',
        colVisibility: '#UT#Sichtbarkeit',
        add: '#UT#Eintrag hinzufügen',
        reload: '#UT#Aktualisieren',
        remove: '#UT#Eintrag löschen',
        vis_show: '#UT#anzeigen',
        vis_hide: '#UT#ausblenden',
        vis_disable: '#UT#nicht vorhanden'
    },
    viewConfig: {
        loadMask: false
    },

    initComponent: function() {
        var me = this,
            userStore = Ext.StoreMgr.get('admin.TaskUserAssocs');
            
        me.addEvents(
              /**
               * @event taskCreated
               * @param {Ext.form.Panel} grid
               * @param {Editor.model.admin.task.UserPref[]} toDelete
               * @param {Ext.button.Button} btn
               */
              'confirmDelete'
        );

        Ext.applyIf(me, {
            columns: [
                {
                    xtype: 'gridcolumn',
                    dataIndex: 'workflowStep',
                    text: me.strings.colStep,
                    renderer: function(v, meta, rec) {
                        var meta;
                        if(v.length == 0) {
                            return me.strings.defaultEntry;
                        }
                        meta = me.actualTask.getWorkflowMetaData();
                        return meta.steps[v] || v;
                    }
                },
                {
                    xtype: 'gridcolumn',
                    dataIndex: 'userGuid',
                    renderer: function(v, meta, rec) {
                        if(v.length == 0) {
                            return me.strings.defaultEntry;
                        }
                        var idx = userStore.find('userGuid', v),
                            user = userStore.getAt(idx);
                        if(user) {
                            return Editor.model.admin.User.getLongUserName(user);
                        }
                        return v;
                    },
                    text: me.strings.colUsername
                },
                {
                    xtype: 'gridcolumn',
                    renderer: function(value, metaData, record, rowIndex, colIndex, store, view) {
                        var fields = value.split(','),
                            cnt = 0,
                            result = [],
                            visible = 0;
                        Ext.Object.each(this.fieldLabels, function(k, v){
                            cnt++;
                            if(Ext.Array.indexOf(fields, k) >= 0) {
                                result.push(v);
                                visible++;
                            } else {
                                result.push('<strike>'+v+'</strike>');
                            }
                        });
                        metaData.tdAttr = 'data-qtip="'+result.join('<br />')+'"';

                        return cnt+' ('+visible+')';
                    },
                    dataIndex: 'fields',
                    text: me.strings.colTargets
                },
                {
                    xtype: 'booleancolumn',
                    dataIndex: 'anonymousCols',
                    text: me.strings.colAnonymous
                },
                {
                    xtype: 'gridcolumn',
                    dataIndex: 'visibility',
                    renderer: function(v) {
                        return me.strings['vis_'+v];
                    },
                    text: me.strings.colVisibility
                }
            ],
            dockedItems: [
                {
                    xtype: 'toolbar',
                    dock: 'top',
                    items: [
                        {
                            xtype: 'button',
                            itemId: 'userPrefAdd',
                            iconCls: 'ico-add',
                            text: me.strings.add
                        },
                        {
                            xtype: 'button',
                            itemId: 'userPrefDelete',
                            iconCls: 'ico-del',
                            disabled: true,
                            handler: function() {
                                Ext.Msg.confirm(me.strings.confirmDeleteTitle, me.strings.confirmDelete, function(btn){
                                    var toDelete = me.getSelectionModel().getSelection();
                                    if(btn == 'yes') {
                                        me.fireEvent('confirmDelete', me, toDelete, this);
                                    }
                                });
                            },
                            text: me.strings.remove
                        },
                        {
                            xtype: 'button',
                            itemId: 'userPrefReload',
                            iconCls: 'ico-refresh',
                            text: me.strings.reload
                        }
                    ]
                }
            ]
        });

        me.callParent(arguments);
    }
});
