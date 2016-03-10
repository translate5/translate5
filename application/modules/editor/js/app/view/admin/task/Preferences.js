
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

    initConfig: function(instanceConfig) {
        var me = this,
            config,
            workflows = [];
            Ext.Object.each(Editor.data.app.workflows, function(key, item) {
                workflows.push([item.id, item.label]);
            });
        config = {
                title: me.title, //see EXT6UPD-9
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
                    value: me.initialConfig.actualTask.get('workflow'),
                    store: workflows
                }]
            },{
                xtype: 'container',
                region: 'east',
                autoScroll: true,
                height: 'auto',
                width: 280,
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
        };

        if (instanceConfig) {
            me.getConfigurator().merge(me, config, instanceConfig);
        }
        return me.callParent([config]);
    }
});
