
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
        colNotEditContent: '#UT#Nur manuelle QS im Segment bearbeiten',
        colAnonymous: '#UT#Anonymisiert',
        colVisibility: '#UT#Sichtbarkeit',
        add: '#UT#Eintrag hinzufügen',
        reload: '#UT#Aktualisieren',
        remove: '#UT#Eintrag löschen',
        vis_show: '#UT#anzeigen',
        vis_hide: '#UT#ausblenden',
        vis_disable: '#UT#nicht vorhanden'
    },
    bind:{
    	store:{
    		model:'Editor.model.admin.task.UserPref',
			remoteSort: true,
			remoteFilter: true,
			pageSize: false,
			autoLoad:true,
			getDefaultFor: function(workflow) {
		      var idx = this.findBy(function(rec){
		          return (rec.get('workflow') == workflow && rec.isDefault());
		      });
		      if(idx >= 0) {
		          return this.getAt(idx);
		      }
		      return null;
			},
			filters:[{
    			property: 'taskGuid',
        		operator:"eq",
        		value:'{currentTask.taskGuid}'
			},{
				property: 'workflow',
        		operator:"eq",
        		value:'{currentTask.workflow}'
			}]
    	}
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
            userStore = Ext.StoreMgr.get('admin.Users');
            
        config = {
            columns: [
                {
                    xtype: 'gridcolumn',
                    dataIndex: 'workflowStep',
                    text: me.strings.colStep,
                    renderer: function(v, meta, rec) {
                        var meta = this.lookupViewModel(true).get('currentTask').getWorkflowMetaData();
                        if(v.length == 0) {
                            return me.strings.defaultEntry;
                        }
                        return meta.steps[v] || v;
                    }
                },
                {
                    xtype: 'gridcolumn',
                    dataIndex: 'userGuid',
                    renderer: function(v, meta, rec) {
                        if(!v) {
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
                            task = this.lookupViewModel(true).get('currentTask'),
                            visible = 0;
                        
                        task.segmentFields().each(function(field){
                            cnt++;
                            if(Ext.Array.indexOf(fields, field.get('name')) >= 0) {
                                result.push(field.get('label'));
                                visible++;
                            } else {
                                result.push('<strike>'+field.get('label')+'</strike>');
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
                            itemId: 'userPrefReload',
                            glyph: 'f2f1@FontAwesome5FreeSolid',
                            text: me.strings.reload
                        },
                        {
                            xtype: 'button',
                            itemId: 'userPrefAdd',
                            glyph: 'f00c@FontAwesome5FreeSolid',
                            text: me.strings.add
                        },
                        {
                            xtype: 'button',
                            itemId: 'userPrefDelete',
                            glyph: 'f00d@FontAwesome5FreeSolid',
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
                        }
                    ]
                }
            ]
        };

        if (instanceConfig) {
            me.self.getConfigurator().merge(me, config, instanceConfig);
        }
        return me.callParent([config]);
    }
});
