/*
START LICENSE AND COPYRIGHT

 This file is part of translate5
 
 Copyright (c) 2013 - 2021 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

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

/**#@++
 * @author Marc Mittag
 * @package editor
 * @version 1.0
 *
 */
/**
 * @class Editor.controller.LanguageResourcesTaskassoc
 * @extends Ext.app.Controller
 */
Ext.define('Editor.controller.LanguageResourcesTaskassoc', {
    extend: 'Ext.app.Controller',
    views: ['Editor.view.LanguageResources.TaskAssocPanel'],
    models: ['Editor.model.LanguageResources.TaskAssoc'],
    stores: ['Editor.store.LanguageResources.TaskAssocStore'],
    strings: {
        assocSave: '#UT#Eintrag gespeichert!',
        assocDeleted: '#UT#Eintrag gelöscht!',
        assocSaveError: '#UT#Fehler beim Speichern der Änderungen!'
    },
    refs: [{
        ref: 'taskTabs',
        selector: 'adminTaskTaskManagement > tabpanel'
    }, {
        ref: 'grid',
        selector: '#languageResourcesTaskAssocGrid'
    }, {
        ref: 'taskManagement',
        selector: 'adminTaskTaskManagement'
    }, {
        ref: 'analysisRerunMsg',
        selector: '#analysisNeedRerun'
    }, {
        ref: 'adminTaskAddWindow',
        selector: '#adminTaskAddWindow'
    }],

    listen: {
        controller: {
            '#admin.TaskPreferences': {
                'loadPreferences': 'handleLoadPreferences'
            }
        },

        component: {
            '#languageResourcesTaskAssocGrid checkcolumn[dataIndex="segmentsUpdateable"]': {
                checkchange: 'handleSegmentsUpdateableChange'
            },
            '#languageResourcesTaskAssocGrid checkcolumn[dataIndex="checked"]': {
                checkchange: 'handleCheckedChange'
            },
            '#languageResourcesTaskAssocGrid': {
                edit: 'onPenaltyEdit'
            }
        },

        messagebus: {
            '#translate5 task': {
                //INFO: the listener and the event handler are also defined in the ProjectGridViewController.
                // To unify this we should use mixins, they are 2 different components and the scope is not the same.
                triggerReload: 'onTriggerTaskReload'
            }
        }
    },

    onTriggerTaskReload: function () {
        var grid = this.getGrid(),
            store = grid && grid.getStore();
        if(store){
            store.reload();
        }
    },

    handleLoadPreferences: function (controller, task) {
        var me = this,
            languageResourceparams = {
                params: {
                    filter: '[{"operator":"like","value":"' + task.get('taskGuid') + '","property":"taskGuid"}]'
                }
            };
        //set the actual task
        me.actualTask = task;
        me.getLanguageResourcesTaskAssocGrid().getStore().removeAll();
        me.getLanguageResourcesTaskAssocGrid().store.load(languageResourceparams);
    },


    /**
     * uncheck segmentsUpdateable when uncheck whole row, restore segmentsUpdateable if recheck row
     */
    handleCheckedChange: function (column, rowIdx, checked, record) {
        var me = this,
            oldValue = record.isModified('segmentsUpdateable') && record.getModified('segmentsUpdateable');

        // Prevent double click
        if (this.clickTimeout) {
            clearTimeout(this.clickTimeout);
            this.clickTimeout = null;
            return;
        }
        this.clickTimeout = setTimeout(() => {
            record.set('segmentsUpdateable', checked && oldValue);
            me.saveRecord(record);
            this.clickTimeout = null;
        }, 250);
    },
    /**
     * check row when segmentsUpdateable is checked
     */
    handleSegmentsUpdateableChange: function (column, rowIdx, checked, record) {
        var me = this;
        if (checked && !record.get('checked')) {
            record.set('checked', true);
        }
        me.saveRecord(record);
    },

    /**
     * Save assoc record
     */
    saveRecord: function (record, isSaveSequence) {
        var me = this,
            str = me.strings,
            params = {},
            method = 'DELETE',
            url = Editor.data.restpath + 'languageresourcetaskassoc',
            checkedData = Ext.JSON.encode({
                languageResourceId: record.get('languageResourceId'),
                taskGuid: record.get('taskGuid'),
                segmentsUpdateable: record.get('segmentsUpdateable'),
                penaltyGeneral: record.get('penaltyGeneral'),
                penaltySublang: record.get('penaltySublang'),
            });

        if (me.getTaskManagement()) {
            me.getTaskManagement().setLoading(true);
        }
        if (record.get('checked')) {
            method = record.get('taskassocid') ? 'PUT' : 'POST';
            params = {data: checkedData};
        }
        if (method != 'POST') {
            url = url + '/' + record.get('taskassocid');
        }

        Ext.Ajax.request({
            url: url,
            method: method,
            params: params,
            success: function (response) {
                if (record.data.checked) {
                    var resp = Ext.util.JSON.decode(response.responseText),
                        newId = resp.rows['id'];
                    record.set('taskassocid', newId);
                    Editor.MessageBox.addSuccess(str.assocSave);
                } else {
                    record.set('taskassocid', 0);
                    Editor.MessageBox.addSuccess(str.assocDeleted);
                }
                record.commit();
                if (!isSaveSequence) {
                    me.hideLoadingMask();
                }

                //fire the event when all active requests are finished
                me.fireEvent('taskAssocSavingFinished', record, me.getLanguageResourcesTaskAssocGrid().getStore());
            },
            failure: function (response) {
                Editor.app.getController('ServerException').handleException(response);
                if (!isSaveSequence) {
                    me.hideLoadingMask();
                }
            }
        });
    },

    hideLoadingMask: function () {
        var me = this;
        if (!me.getTaskManagement()) {
            return;
        }
        var task = me.getTaskManagement().getCurrentTask();
        me.getTaskManagement().setLoading(false);
        task && task.load();
    },

    /**
     * Get the right language resources task assoc gid
     */
    getLanguageResourcesTaskAssocGrid: function () {
        var me = this,
            addTaskWindow = me.getAdminTaskAddWindow();
        if (addTaskWindow) {
            return addTaskWindow.down('#languageResourcesTaskAssocGrid');
        }
        return me.getTaskManagement().down('#languageResourcesTaskAssocGrid');
    },

    /**
     * Handler for edit-event fired when penalty cell editing done
     *
     * @param plugin
     * @param context
     */
    onPenaltyEdit: function(plugin, context) {

        // Make sure assoc to be created, if not exist so far
        var changes = context.record.getChanges(),
            changedProps = Object.keys(changes);

        // If the only modified prop remaining is checked-prop - it means
        // checked-prop was auto-set checked but now it should be auto-set unchecked
        if (changedProps.length === 1 && 'checked' in changes && changes.checked === true) {
            context.record.set('checked', false);
        } else {
            context.record.set('checked', true);
        }

        // Add or update association with the penalty
        // immediately only when we're in task wizard
        if (this.getAdminTaskAddWindow()) {
            this.saveRecord(context.record);

        // Else
        } else {

            // Show/hide rerun-msg based on modified records presence
            context.store.store.getModifiedRecords().length
                ? this.getAnalysisRerunMsg().show()
                : this.getAnalysisRerunMsg().hide();

            // Update assoc in viewModel
            Editor.app.getController('Editor.plugins.MatchAnalysis.controller.MatchAnalysis').updateTaskAssoc(context.store.store);
        }
    }
});
