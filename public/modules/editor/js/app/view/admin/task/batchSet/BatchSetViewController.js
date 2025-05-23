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

Ext.define('Editor.view.admin.task.batchSet.BatchSetViewController', {
    extend: 'Ext.app.ViewController',

    childParamField: '',
    batchFields: [],
    withFileUpload: false,

    listen: {
        component: {
            '#setForFiltered': {
                click: 'onSetForFilteredClick'
            },
            '#setForSelected': {
                click: 'onSetForSelectedClick'
            }
        }
    },

    onSetForSelectedClick: function () {
        this.handleBatchSet(true);
    },

    /**
     * Handler for "Set for all" buttons
     * @param {Boolean} selectedTasksOnly
     */
    handleBatchSet: function (selectedTasksOnly) {
        const batchParams = {},
            childParams = this.initBatchParams();
        if (childParams) {
            batchParams[this.childParamField] = childParams;
        }
        if(Object.keys(batchParams).length < 1) {
            return this.showWarningNoProperty();
        }

        const store = Ext.StoreManager.get('project.Project'),
            proxy = store.getProxy(),
            tasksData = {};

        if (selectedTasksOnly) {

            const dataController = Editor.app.getController('Editor.controller.BatchEditing'),
                projectsAndTasks = dataController.getProjectsAndTasks();

            if (projectsAndTasks.length<1) {
                return this.showWarning(Editor.data.l10n.batchSetWindow.noTasksSelected);
            }
            // unselect checkboxes
            dataController.clearData();
            tasksData.projectsAndTasks = projectsAndTasks.join(',');
        } else {
            tasksData[proxy.getFilterParam()] = proxy.encodeFilters(store.getFilters().items);
        }

        this.submitBatchParams(tasksData, batchParams);
    },

    submitBatchParams: function (tasksData, batchParams) {
        for (const [updateType, params] of Object.entries(batchParams)) {

            params.updateType = updateType;

            if(this.withFileUpload) {
                const view = this.getView(), form = view.down('form');

                params.format = 'jsontext';

                view.setLoading(true);
                form.submit({
                    params: {...params, ...tasksData},
                    url: Editor.data.restpath + 'taskuserassoc/batchset',
                    success: function(frm, submit) {
                        Editor.MessageBox.addSuccess('Success');
                        view.setLoading(false);
                        view.close();
                    },
                    failure: function(frm, submit) {
                        Editor.app.getController('ServerException').handleException(submit.response);
                        view.setLoading(false);
                    }
                });
                return;
            }

            Ext.Ajax.request({
                url: Editor.data.restpath + 'taskuserassoc/batchset',
                method: 'POST',
                params: {...params, ...tasksData},
                success: function (response) {
                    Editor.MessageBox.addSuccess('Success');
                },
                failure: function (response) {
                    Editor.app.getController('ServerException').handleException(response);
                }
            });
        }
    },

    /**
     * Init parameters for batch set; returns null unless all required fields are set
     * @return {Object|null}
     */
    initBatchParams: function () {
        const me = this.getView(), params = {};
        this.batchFields.every(function (fieldId) {
            const field = me.down('#' + fieldId);
            if (!field.value) {
                return false;
            }
            params[fieldId] = field.value;
            return true;
        });
        return Object.keys(params).length === this.batchFields.length ? params : null;
    },

    showWarning: function (msg) {
        Ext.Msg.alert(Editor.data.l10n.batchSetWindow.warning, msg);
    },

    showWarningNoProperty: function (msg) {
        this.showWarning(Editor.data.l10n.batchSetWindow.noPropertySet);
    },

    onSetForFilteredClick: function () {
        const me = this,
            store = Ext.StoreManager.get('project.Project'),
            proxy = store.getProxy(),
            params = this.initBatchParams();

        if(params === null) {
            return this.showWarningNoProperty();
        }

        params.countTasks = 1;
        params[proxy.getFilterParam()] = proxy.encodeFilters(store.getFilters().items);

        Ext.Ajax.request({
            url: Editor.data.restpath + 'taskuserassoc/batchset',
            method: 'POST',
            params: params,
            success: function (response) {
                let jobsCount = response.responseJson.total,
                    l10n = Editor.data.l10n.batchSetWindow,
                    question = l10n.allFilteredWarning.replace('. ', ' ('+jobsCount+' '+l10n.jobsLabel+'). ');
                if (jobsCount > 50) {
                    question = '<b style="color:red">'+ question + '</b>';
                }
                Ext.MessageBox.confirm(
                    l10n.setForFiltered, question, function (btn) {
                        if (btn === 'yes') {
                            me.handleBatchSet(false);
                        }
                    });
            },
            failure: function (response) {
                Editor.app.getController('ServerException').handleException(response);
            }
        });
    }

});