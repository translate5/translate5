/*
 START LICENSE AND COPYRIGHT

 This file is part of translate5

 Copyright (c) 2013 - 2022 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

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

/**
 * Main Controller of the task custom fields feature
 *
 * @extends Ext.app.Controller
 */
Ext.define('Editor.controller.admin.TaskCustomField', {
    extend: 'Ext.app.Controller',

    statics: {
        getGridColumnsFor: function(gridType) {
            var locale = Editor.data.locale, column, columns = [];

            // Foreach custom field
            Editor.data.editor.task.customFields.forEach(field => {

                // If field should not be shown in given gridType - skip
                if (!field.placesToShow.match(gridType)){
                    return;
                }

                // Get labels and tooltips
                var labelL10n = Ext.JSON.decode(field.label, true) || {};

                // Primary config
                column = {
                    text      : locale in labelL10n ? labelL10n[locale] : field.label,
                    xtype     : 'gridcolumn',
                    dataIndex : 'customField' + field.id,
                    stateId   : 'customField' + field.id,
                    filter    : {
                        type: 'string'
                    }
                };

                // If it's a checkbox or combobox
                if (!field.type.match('text')) {

                    // Setup list-filter
                    column.filter = {
                        type: 'list',
                        options: [],
                        phpMode: false,
                        labelField: 'text'
                    };

                    // If it's a checkbox
                    if (field.type === 'checkbox') {

                        // Use '1' instead of 'on'
                        column.renderer = value => value === '1' ? 'Yes' : 'No';

                        // Setup filter enumerated options
                        column.filter.options.push(['1', 'Yes'], ['0', 'No']);

                    // Else if it's a combobox
                    } else if (field.type === 'combobox') {
                        // Decode field's comboboxData, if possible
                        column.comboboxData = Ext.JSON.decode(field.comboboxData, true) || {};

                        // Convert combobox data into filter options
                        Ext.Object.each(column.comboboxData, function (value, title) {
                            column.filter.options.push([value, title[Editor.data.locale]]);
                        });

                        // Make sure the right title is shown for a value
                        column.renderer = function (value, _1, _2, _3, colIndex, _4, view) {
                            var selectedOption = view.getHeaderCt().getHeaderAtIndex(colIndex).comboboxData[value];
                            if(!selectedOption){
                                return value;
                            }
                            return selectedOption[Editor.data.locale];
                        };

                    }
                }

                // Add to configs array
                columns.push(column);
            });

            //
            return columns;
        },

        /**
         * Get item configs for the custom fields and render them in the defined place. If formType is 'projectWizard'
         *  and editMode is true, the fields are rendered in task properties panel.
         * @param formType
         * @param editMode
         * @returns {*[]}
         */
        getFormFieldsFor: function(formType, editMode) {
            var locale = Editor.data.locale,
                config,
                fields = [];

            // Foreach custom field
            Editor.data.editor.task.customFields.forEach(field => {

                // If field should not be shown in current formType - skip
                if (!field.placesToShow.match(formType))
                {
                    return;
                }

                // If field should be shown in project wizard, but it's
                // a readonly-field - skip, as it does not have any value at this step
                if (formType === 'projectWizard' && field.mode === 'readonly')
                {
                    return;
                }

                // Get labels and tooltips
                var labelL10n = Ext.JSON.decode(field.label, true) || {};
                var tooltipL10n = Ext.JSON.decode(field.tooltip, true) || {};

                // Start config
                config = {
                    fieldLabel: locale in labelL10n ? labelL10n[locale] : field.label,
                    tooltip   : locale in tooltipL10n ? tooltipL10n[locale] : field.tooltip,
                    xtype     : field.type,
                    name      : 'customField' + field.id,
                    itemId    : 'customField' + field.id,
                    readOnly  : field.mode === 'readonly',
                    regex     : field.regex ? new RegExp(field.regex) : null,
                    allowBlank: field.type === 'checkbox' || field.mode !== 'required',
                    isCustom  : true
                };

                if(editMode){
                    config.bind = '{currentTask.customField' + field.id+'}';
                }

                // If it's a checkbox
                if (field.type === 'checkbox') {

                    // Use '1' instead of 'on', '0' instead of '' and submit anyway
                    config.getSubmitValue = function() {
                        return this.checked ? 1 : 0;
                    };

                // Else if it's a combobox
                } else if (field.type === 'combobox') {

                    // Decode field's comboboxData, if possible
                    var decoded = Ext.JSON.decode(field.comboboxData, true) || {}, store = [];

                    // Re-structure to format supported by extjs
                    Ext.Object.each(decoded, (value, title) => store.push({
                        value: value,
                        title: title[Editor.data.locale]
                    }));

                    // Apply combobox-specific configs
                    Ext.merge(config, {
                        valueField: 'value',
                        displayField: 'title',
                        store: {
                            fields: ['value', 'title'],
                            data: store
                        }
                    });
                }

                // Add to configs array
                fields.push(config);
            });

            //
            return fields;
        }
    },

    requires: [
        'Editor.view.admin.task.CustomField.Grid'
    ],

    listen: {
        component: {
            '#preferencesOverviewPanel': {
                render: 'addToSettingsPanel'
            },
            '#taskMainCard': {
                render: {
                    fn: 'addCustomFieldsToTaskMainCard',
                    priority: 900 // we want after customersCombo has been added
                }
            }
        }
    },

    /** @property {Editor.view.admin.taskCustomField.Grid} panel reference to our main view */
    panel: null,

    addToSettingsPanel: function(panel){
        if(Editor.app.authenticatedUser.isAllowed('taskCustomField')){
            this.panel = panel.insert(2, {
                xtype: 'taskCustomFieldGrid',
                bind: {
                    title: '{l10n.taskCustomField.title}'
                },
                itemId: 'taskCustomFields',
                glyph: 'f1de@FontAwesome5FreeSolid',
                routePrefix: 'preferences/'
            });
        }
    },

    addCustomFieldsToTaskMainCard: function(taskMainCard) {
        taskMainCard.down('#taskMainCardContainer').add(
            Editor.controller.admin.TaskCustomField.getFormFieldsFor('projectWizard')
        );
    }
});
