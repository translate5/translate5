
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
        colNotEditContent: '#UT#Cannot Edit Content',
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
    //***********************************************************************************
    //Begin Events
    //***********************************************************************************
    /**
     * @event confirmDelete
     * @param {Ext.form.Panel} grid
     * @param {Editor.model.admin.task.UserPref[]} toDelete
     * @param {Ext.button.Button} btn
     */
    //***********************************************************************************
    //End Events
    //***********************************************************************************
    initConfig: function(instanceConfig) {
        var me = this,
            config,
            userStore = Ext.StoreMgr.get('admin.TaskUserAssocs');
            
        config = {
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
                        meta = me.initialConfig.actualTask.getWorkflowMetaData();
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
                    dataIndex: 'notEditContent',
                    text: me.strings.colNotEditContent
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
        };

        if (instanceConfig) {
            me.getConfigurator().merge(me, config, instanceConfig);
        }
        return me.callParent([config]);
    }
});
