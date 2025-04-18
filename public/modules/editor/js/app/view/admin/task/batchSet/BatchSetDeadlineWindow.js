/*
START LICENSE AND COPYRIGHT

 This file is part of translate5
 
 Copyright (c) 2013 - 2024 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

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

Ext.define('Editor.view.admin.task.batchSet.BatchSetDeadlineWindow', {
    extend: 'Editor.view.admin.task.batchSet.BatchSetWindow',
    alias: 'widget.adminTaskBatchSetDeadlineWindow',
    requires: [
        'Editor.view.admin.task.batchSet.BatchSetDeadlineViewController'
    ],
    controller: 'adminTaskBatchSetDeadlineWindow',
    itemId: 'adminTaskBatchSetDeadlineWindow',
    layout: 'hbox',

    getInnerItems: function () {
        var me = this,
            l10n = Editor.data.l10n.batchSetWindow;
        return [{
            items: [{
                xtype: 'displayfield',
                style: 'margin-top:30px',
                value: l10n.deadlineDateText
            }]
        }, {
            items: [{
                xtype: 'combobox',
                name: 'batchWorkflow',
                itemId: 'batchWorkflow',
                queryMode: 'local',
                valueField: 'id',
                displayField: 'label',
                store: 'admin.Workflow',
                forceSelection: true,
                allowBlank: false,
                fieldLabel: l10n.workflowLabel,
                labelAlign: 'top',
                labelWidth: '100%'
            }]
        }, {
            items: [{
                xtype: 'combobox',
                name: 'batchWorkflowStep',
                itemId: 'batchWorkflowStep',
                forceSelection: true,
                allowBlank: false,
                queryMode: 'local',
                valueField: 'id',
                displayField: 'text',
                fieldLabel: l10n.workflowStepLabel,
                labelAlign: 'top',
                labelWidth: ' 100%'
            }]
        }, {
            items: [{
                xtype: 'datetimefield',
                name: 'deadlineDate',
                itemId: 'deadlineDate',
                format: Editor.DATE_HOUR_MINUTE_ISO_FORMAT,
                width: 170,
                style: 'margin-top:30px',
                enableKeyEvents: true,
                listeners: {
                    keypress : function(fld,e){
                        if (e.getCharCode() === Ext.EventObject.ENTER) {
                            me.getController().onSetForSelectedClick();
                        }
                    }
                }
            }]
        }];
    }

});