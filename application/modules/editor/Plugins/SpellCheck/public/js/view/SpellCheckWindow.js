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

Ext.define('Editor.plugins.SpellCheck.view.SpellCheckWindow', {
    extend: 'Ext.window.Window',
    alias: 'widget.spellcheckwindow',
    itemId: 'spellcheckwindow',
    stateful: true,
    stateId: 'editor.spellcheckwindow',
    viewModel: {
        data: {
            cursor: null,
            qualityId: null,
            /** @type {Ext.data.Model|null} */
            segmentRecord: null,
        },
    },
    minHeight: 350,
    width: 670,
    constrainHeader: true,
    autoHeight: true,
    layout: 'fit',
    x: 100,
    y: 100,

    initConfig: function (instanceConfig) {
        const config = {
            bind: { title: '{l10n.SpellCheck.windowTitle}' },
            items: [
                {
                    xtype: 'panel',
                    layout: { type: 'vbox', align: 'stretch' },
                    border: false,
                    bodyPadding: 8,
                    items: [
                        // ── Current error field ────────────────────────────
                        {
                            xtype: 'displayfield',
                            itemId: 'spellcheckErrorMessage',
                            margin: '0 0 8 0',
                            fieldLabel: 'Error',
                            labelWidth: 100,
                            value: '',
                            fieldCls: 'spellcheck-error-field',
                        },
                        // ── Two-column row ─────────────────────────────────
                        {
                            xtype: 'panel',
                            layout: { type: 'hbox', align: 'stretch' },
                            border: false,
                            height: 350,
                            items: [
                                // ── Left column: suggestions list ──────────
                                {
                                    xtype: 'container',
                                    flex: 1,
                                    layout: { type: 'hbox', align: 'stretch' },
                                    cls: 'spellcheck-suggestions-col',
                                    items: [
                                        {
                                            xtype: 'label',
                                            bind: { text: '{l10n.SpellCheck.suggestions}' },
                                            width: 100,
                                            margin: '4 4 2 0',
                                            cls: 'spellcheck-field-label',
                                        },
                                        {
                                            xtype: 'dataview',
                                            itemId: 'spellcheckSuggestionsList',
                                            flex: 1,
                                            scrollable: 'y',
                                            trackOver: true,
                                            overItemCls: 'spellcheck-suggestion-hovered',
                                            selectedItemCls: 'spellcheck-suggestion-selected',
                                            itemSelector: '.spellcheck-suggestion-item',
                                            bind: {
                                                emptyText:
                                                    '<div style="padding:4px;color:#999;">{l10n.SpellCheck.noSuggestions}</div>',
                                            },
                                            store: Ext.create('Ext.data.Store', {
                                                fields: ['value'],
                                                data: [],
                                            }),
                                            tpl: new Ext.XTemplate(
                                                '<tpl for=".">',
                                                '<div class="spellcheck-suggestion-item">{value}</div>',
                                                '</tpl>',
                                            ),
                                            cls: 'spellcheck-suggestions-list',
                                            listeners: {
                                                selectionchange: function (selModel, selected) {
                                                    const win = this.up('spellcheckwindow');
                                                    const hasSelection = selected.length > 0;
                                                    win.down('#btnReplaceWithProposal').setDisabled(!hasSelection);
                                                    win.down('#btnReplaceAllWithProposal').setDisabled(!hasSelection);
                                                },
                                            },
                                        },
                                    ],
                                },
                                // ── Right column: action buttons ────────────
                                {
                                    xtype: 'container',
                                    itemId: 'spellcheckActions',
                                    width: 160,
                                    margin: '10 0 0 8',
                                    layout: { type: 'vbox', align: 'stretch' },
                                    defaults: {
                                        xtype: 'button',
                                        textAlign: 'left',
                                        margin: '0 0 6 0',
                                    },
                                    items: [
                                        {
                                            xtype: 'combobox',
                                            itemId: 'cmbCategory',
                                            bind: { fieldLabel: '{l10n.SpellCheck.category}' },
                                            labelAlign: 'top',
                                            margin: '0 0 10 0',
                                            editable: false,
                                            value: 'misspelling',
                                            store: Ext.create('Ext.data.Store', {
                                                fields: ['value', 'display'],
                                                data: [
                                                    {
                                                        value: 'misspelling',
                                                        display: Editor.data.l10n.SpellCheck.spelling,
                                                    },
                                                    { value: 'grammar', display: Editor.data.l10n.SpellCheck.grammar },
                                                    {
                                                        value: 'typographical',
                                                        display: Editor.data.l10n.SpellCheck.typographical,
                                                    },
                                                ],
                                            }),
                                            displayField: 'display',
                                            valueField: 'value',
                                            listeners: {
                                                change: function (combo, newValue) {
                                                    const win = this.up('spellcheckwindow');

                                                    win.getSpellCheckController().next(null);
                                                },
                                            },
                                        },
                                        {
                                            itemId: 'btnNextError',
                                            bind: {
                                                text: '{l10n.SpellCheck.nextError}',
                                                tooltip: '{l10n.SpellCheck.nextErrorTooltip}',
                                            },
                                            handler: function () {
                                                const win = this.up('spellcheckwindow');
                                                const vm = win.getViewModel();
                                                const cursor = vm.get('cursor');

                                                win.getSpellCheckController().next(cursor);
                                            },
                                        },
                                        {
                                            itemId: 'btnIgnoreError',
                                            bind: {
                                                disabled: '{qualityId === null}',
                                                text: '{l10n.SpellCheck.ignoreError}',
                                                tooltip: '{l10n.SpellCheck.ignoreErrorTooltip}',
                                            },
                                            handler: function () {
                                                const win = this.up('spellcheckwindow');
                                                const vm = win.getViewModel();
                                                const qualityId = vm.get('qualityId');
                                                const cursor = vm.get('cursor');
                                                const controller = win.getSpellCheckController();
                                                controller
                                                    .ignore(qualityId)
                                                    .then(() => {
                                                        controller.next(cursor);
                                                    })
                                                    .catch(() => {});
                                            },
                                        },
                                        {
                                            itemId: 'btnIgnoreAllErrors',
                                            bind: {
                                                disabled: '{qualityId === null}',
                                                text: '{l10n.SpellCheck.ignoreAllSameErrors}',
                                                tooltip: '{l10n.SpellCheck.ignoreAllSameErrorsTooltip}',
                                            },
                                            handler: function () {
                                                const win = this.up('spellcheckwindow');
                                                const vm = win.getViewModel();
                                                const qualityId = vm.get('qualityId');
                                                const cursor = vm.get('cursor');
                                                const controller = win.getSpellCheckController();

                                                controller
                                                    .ignoreAll(qualityId)
                                                    .then(() => {
                                                        controller.next(cursor);
                                                    })
                                                    .catch(() => {});
                                            },
                                        },
                                        {
                                            itemId: 'btnReplaceWithProposal',
                                            bind: {
                                                text: '{l10n.SpellCheck.replaceWithProposal}',
                                                tooltip: '{l10n.SpellCheck.replaceWithProposalTooltip}',
                                            },
                                            disabled: true,
                                            handler: function () {
                                                const win = this.up('spellcheckwindow');
                                                const saveAsDraft = win.down('#chkSaveAsDraft').getValue();
                                                const vm = win.getViewModel();
                                                const qualityId = vm.get('qualityId');
                                                const segmentRecord = vm.get('segmentRecord');
                                                const cursor = vm.get('cursor');

                                                const listView = win.down('#spellcheckSuggestionsList');
                                                const selected = listView.getSelectionModel().getSelection();

                                                if (!selected) {
                                                    return;
                                                }

                                                const replacement = selected[0].get('value');

                                                win.getSpellCheckController().replaceWith(
                                                    qualityId,
                                                    replacement,
                                                    segmentRecord,
                                                    saveAsDraft,
                                                    cursor,
                                                );
                                            },
                                        },
                                        {
                                            itemId: 'btnReplaceAllWithProposal',
                                            bind: {
                                                text: '{l10n.SpellCheck.replaceAllWithProposal}',
                                                tooltip: '{l10n.SpellCheck.replaceAllWithProposalTooltip}',
                                            },
                                            disabled: true,
                                            handler: function () {
                                                const win = this.up('spellcheckwindow');
                                                const saveAsDraft = win.down('#chkSaveAsDraft').getValue();
                                                const vm = win.getViewModel();
                                                const qualityId = vm.get('qualityId');
                                                const cursor = vm.get('cursor');

                                                const listView = win.down('#spellcheckSuggestionsList');
                                                const selected = listView.getSelectionModel().getSelection();

                                                if (!selected) {
                                                    return;
                                                }

                                                const replacement = selected[0].get('value');

                                                win.getSpellCheckController().replaceAllWith(
                                                    qualityId,
                                                    replacement,
                                                    saveAsDraft,
                                                    cursor,
                                                );
                                            },
                                        },
                                    ],
                                },
                            ],
                        },
                        // ── Save as draft checkbox ─────────────────────────
                        {
                            xtype: 'checkbox',
                            itemId: 'chkSaveAsDraft',
                            bind: { boxLabel: '{l10n.SpellCheck.saveAsDraft}' },
                            margin: '8 0 0 0',
                            // Default true; honour whatever the user last chose
                            checked: localStorage.getItem('spellcheck.saveAsDraft') !== '0',
                            listeners: {
                                change: function (chk, newValue) {
                                    localStorage.setItem('spellcheck.saveAsDraft', newValue ? '1' : '0');
                                },
                            },
                        },
                    ],
                },
            ],
        };

        if (instanceConfig) {
            this.self.getConfigurator().merge(this, config, instanceConfig);
        }

        return this.callParent([config]);
    },

    /**
     * Populate the window with spell-check data from the controller.
     *
     * @param {Object}   data
     * @param {String}   data.data.message     - The error message / misspelled word
     * @param {String[]} data.data.suggestions - List of replacement proposals
     * @param {String[]} data.data.qualityId   - Id of the quality for further use
     */
    setSpellCheckData: function (data) {
        const spellCheckData = data.data || { message: '', suggestions: [], qualityId: null };
        const errorField = this.down('#spellcheckErrorMessage');
        const listView = this.down('#spellcheckSuggestionsList');

        // ── Error message ────────────────────────────────────────────────────
        errorField.setValue(spellCheckData.message ?? '');

        // ── Suggestions list ─────────────────────────────────────────────────
        const records = (spellCheckData.suggestions ?? []).map((s) => ({ value: s }));
        listView.getStore().loadData(records);

        // Clear any previous selection and disable replace buttons
        listView.getSelectionModel().deselectAll();
        this.down('#btnReplaceWithProposal').setDisabled(true);
        this.down('#btnReplaceAllWithProposal').setDisabled(true);

        const vm = this.getViewModel();
        vm.set('cursor', data.cursor ?? null);
        vm.set('qualityId', spellCheckData.qualityId ?? null);
        vm.set('segmentRecord', spellCheckData.segmentRecord ?? null);
    },

    /**
     * @returns {Editor.plugins.SpellCheck.controller.SpellChecker}
     */
    getSpellCheckController: function () {
        return Editor.app.getController('Editor.plugins.SpellCheck.controller.SpellChecker');
    },
});
