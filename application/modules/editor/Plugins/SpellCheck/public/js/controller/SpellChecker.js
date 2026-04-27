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

Ext.define('Editor.plugins.SpellCheck.controller.SpellChecker', {
    extend: 'Ext.app.Controller',

    requires: ['Editor.plugins.SpellCheck.view.SpellCheckWindow'],

    listen: {
        controller: {
            '#Editor': {
                beforeKeyMapUsage: 'addSpellcheckKeymapEntries',
            },
            '#Segments': {
                segmentEditSaved: 'onSegmentSaved',
            },
        },
    },

    /** @type {{ cursor: { storeNumber: number, segmentIdx: number, matchIdx: number }, segmentRecord: Editor.model.Segment } | null} */
    pendingDataForSave: null,

    /** @type Editor.plugins.SpellCheck.view.SpellCheckTooltip */
    tooltip: null,

    init: function () {},

    addSpellcheckKeymapEntries: function (editorController, area) {
        editorController.keyMapConfig['F7'] = [
            Ext.event.Event.F7,
            { ctrl: false, alt: false, shift: false },
            this.showSpellcheckWindow,
            true,
            this,
        ];
    },

    showSpellcheckWindow: function () {
        const win = this.getSpellCheckerWindow();
        win.show();
        win.setLoading(false);
        this.next(null);
    },

    showSpellCheckTooltip: function (segment, match, x, y) {
        if (!this.tooltip) {
            this.tooltip = Ext.widget('spellchecktooltip', {
                callbacks: {
                    onReplace: (quality, replacement, segment, saveAsDraft) => {
                        const cursor = this.getCursorByQualityAndSegment(quality, segment);
                        this.replaceWith(quality.id, replacement, segment, saveAsDraft, cursor);
                        this.reloadFalsePositivePanel(segment.getId());
                    },
                    onReplaceAll: (quality, replacement, saveAsDraft) => {
                        const cursor = this.getCursorByQualityAndSegment(quality, segment);
                        this.replaceAllWith(quality.id, replacement, saveAsDraft, cursor);
                    },
                    onIgnore: (quality, segment) => {
                        const cursor = this.getCursorByQualityAndSegment(quality, segment);
                        const win = this.getSpellCheckerWindow();
                        win.setLoading();
                        (quality.falsePositive ? this.unignore(quality.id) : this.ignore(quality.id))
                            .then(() => {
                                win.setLoading(false);
                                quality.falsePositive = !quality.falsePositive;
                                this.reloadFalsePositivePanel(segment.getId());
                                this.next(cursor);
                            })
                            .catch(() => {
                                win.setLoading(false);
                            });
                    },
                    onIgnoreAll: (quality, segment) => {
                        const cursor = this.getCursorByQualityAndSegment(quality, segment);
                        const win = this.getSpellCheckerWindow();
                        win.setLoading();
                        (quality.falsePositive ? this.unignoreAll(quality.id) : this.ignoreAll(quality.id))
                            .then(() => {
                                win.setLoading(false);
                                this.next(cursor);
                            })
                            .catch(() => {
                                win.setLoading(false);
                            });
                    },
                    onSaveDraftChange: (checked) => {},
                },
            });
        }

        this.tooltip.loadMatch(segment, match);
        this.tooltip.showAt(x, y);
    },

    /**
     * Find the next spell-check error after the given cursor position.
     *
     * Segments are loaded in pages of ~200 into the store's internal MixedCollection,
     * keyed by an incrementing integer starting at 1. We iterate storeNumber upward
     * until getData().get(storeNumber) returns nothing (end of all pages).
     *
     * @param  {{ storeNumber: number, segmentIdx: number, matchIdx: number, category: String }|null} cursor
     *         Pass null to start from the very first error.
     *
     * @returns {{
     *   data:   { message: string, suggestions: string[], segmentRecord: Editor.model.Segment, matchIndex: number },
     *   cursor: { storeNumber: number, segmentIdx: number, matchIdx: number }
     * }|null}
     */
    getNextSpellCheckData: function (cursor) {
        const store = this.getSegmentsStore();

        if (!store) {
            return null;
        }

        const startStoreNumber = cursor ? cursor.storeNumber : 1;
        const startSegmentId = cursor ? cursor.segmentIdx : 0;
        // Advance past the current match; on a new segment or store we start from 0
        const startMatchIdx = cursor ? cursor.matchIdx + 1 : 0;
        const selectedCategory = cursor ? cursor.category : this.getSelectedCategory();

        for (let storeNumber = startStoreNumber; ; storeNumber++) {
            let records;

            try {
                records = store.getData().get(storeNumber);
            } catch (e) {
                break;
            }

            if (!records) {
                break;
            }

            // On the starting store page begin at the cursor's segment, otherwise from 0
            const segStart = storeNumber === startStoreNumber ? startSegmentId : 0;

            for (let recordNumber = segStart; recordNumber < records.length; recordNumber++) {
                const record = records[recordNumber];
                const spellCheck = record.get('spellCheck');
                const matches = spellCheck?.target;

                if (!matches?.length) {
                    continue;
                }

                // On the very first segment of the starting store, begin after the current match
                const matchStart =
                    storeNumber === startStoreNumber && recordNumber === startSegmentId ? startMatchIdx : 0;

                for (let matchIndex = matchStart; matchIndex < matches.length; matchIndex++) {
                    const match = matches[matchIndex];

                    if (match.falsePositive) {
                        continue;
                    }

                    if (selectedCategory && match.category !== selectedCategory) {
                        continue;
                    }

                    const suggestions = (match.replacements ?? []).map((r) =>
                        r !== null && typeof r === 'object' ? (r.value ?? '') : String(r),
                    );

                    return {
                        data: {
                            message: match.content ?? match.message ?? '',
                            suggestions,
                            segmentRecord: record,
                            range: match.range ?? null,
                            matchIndex: match.matchIndex ?? matchIndex,
                            qualityId: match.id,
                        },
                        cursor: {
                            storeNumber,
                            segmentIdx: recordNumber,
                            matchIdx: matchIndex,
                            category: selectedCategory,
                        },
                    };
                }
            }
        }

        return null;
    },

    getCursorByQualityAndSegment: function (quality, segment) {
        const store = this.getSegmentsStore();

        let storeNumber, segmentIdx;

        for (let number = 1; ; number++) {
            let records;

            try {
                records = store.getData().get(number);
            } catch (e) {
                break;
            }

            if (!records) {
                continue;
            }

            const idx = records.findIndex((r) => r.getId() === segment.getId());

            if (idx !== -1) {
                storeNumber = number;
                segmentIdx = idx;

                break;
            }
        }

        if (storeNumber === undefined || segmentIdx === undefined) {
            return null;
        }

        return { storeNumber, segmentIdx, matchIdx: quality.matchIndex ?? 0, category: this.getSelectedCategory() };
    },

    next: function (cursor) {
        const win = this.getSpellCheckerWindow();
        const result = this.getNextSpellCheckData(cursor);

        win.setSpellCheckData(result ?? { message: '', suggestions: [], cursor: null });

        if (result) {
            this.getSegmentGrid().scrollTo(this.getSegmentGrid().store.indexOf(result.data.segmentRecord));
        }
    },

    ignore: function (qualityId) {
        return this._ignore(qualityId, true);
    },

    unignore: function (qualityId) {
        return this._ignore(qualityId, false);
    },

    _ignore: function (qualityId, falsePositive) {
        return new Promise((resolve, reject) => {
            Ext.Ajax.request({
                url: Editor.data.restpath + 'quality/falsepositive',
                method: 'GET',
                params: {
                    id: qualityId,
                    falsePositive: falsePositive ? 1 : 0,
                },
                success: () => {
                    // const store = this.getSegmentsStore();
                    // store.on('load', () => {
                    Editor.view.quality.FalsePositivesController.applyFalsePositiveStyle(
                        qualityId,
                        falsePositive,
                        null,
                    );
                    resolve();
                    // }, this, { single: true });
                    // store.reload();
                    this.reloadQualitiesPanel();
                },
                failure: (response) => {
                    Editor.app.getController('ServerException').handleException(response);
                    reject(response);
                },
            });
        });
    },

    ignoreAll: function (qualityId) {
        return this._ignoreAll(qualityId, true);
    },

    unignoreAll: function (qualityId) {
        return this._ignoreAll(qualityId, false);
    },

    _ignoreAll: function (qualityId, falsePositive) {
        return new Promise((resolve, reject) => {
            Ext.Ajax.request({
                url: Editor.data.restpath + 'quality/falsepositivespread',
                method: 'GET',
                params: {
                    id: qualityId,
                    falsePositive: falsePositive ? 1 : 0,
                },
                success: (xhr) => {
                    const store = this.getSegmentsStore();
                    store.on(
                        'load',
                        () => {
                            const json = Ext.JSON.decode(xhr.responseText, true);
                            if (json && json.ids) {
                                for (const id of json.ids) {
                                    Editor.view.quality.FalsePositivesController.applyFalsePositiveStyle(
                                        id,
                                        falsePositive,
                                        null,
                                    );
                                }
                            }
                            resolve();
                        },
                        this,
                        { single: true },
                    );
                    store.reload();
                    this.reloadQualitiesPanel();
                },
                failure: (response) => {
                    Editor.app.getController('ServerException').handleException(response);
                    reject(response);
                },
            });
        });
    },

    replaceWith: function (qualityId, replacement, segmentRecord, saveAsDraft, cursor) {
        if (!replacement || !segmentRecord) {
            return;
        }

        const win = this.getSpellCheckerWindow();
        win.setLoading(true);

        const grid = this.getSegmentGrid();
        grid.selectOrFocus(grid.store.indexOf(segmentRecord));

        /** @type {Editor.view.segments.RowEditing} */
        const editingPlugin = grid.editingPlugin;
        const selection = grid.getSelectionModel().getSelection()[0];

        editingPlugin.startEdit(selection, null, editingPlugin.self.STARTEDIT_MOVEEDITOR);
        editingPlugin.isDraft = saveAsDraft;
        const quality = segmentRecord.get('spellCheck').target.find((t) => t.id === qualityId);
        const range = quality.range;

        const _this = this;
        setTimeout(
            function () {
                const spellCheck = new SpellCheck();
                const content = editingPlugin.editor.mainEditor.editor.getRawData();
                // This is done to properly recalculate the replacement position
                // as range in quality doesn't care about deletions
                const transformed = spellCheck.transformMatches(
                    [
                        {
                            offset: range.start,
                            context: { length: range.end - range.start },
                            message: quality.message,
                            replacements: quality.replacements,
                            rule: {
                                urls: [],
                                issueType: '',
                            },
                        },
                    ],
                    content,
                );
                editingPlugin.editor.mainEditor.editor.replaceContentInRange(
                    transformed[0].range.start,
                    transformed[0].range.end,
                    replacement,
                );
                win.setLoading(false);
                _this.pendingDataForSave = {
                    cursor,
                    segmentRecord,
                };
                _this.getEditorController().save();
            },
            editingPlugin.editor.mainEditor.editor ? 0 : 100,
        );
    },

    onSegmentSaved: function (controller, record) {
        if (!this.pendingDataForSave) {
            return;
        }

        if (this.pendingDataForSave.segmentRecord.getId() !== record.getId()) {
            return;
        }

        this.next(this.pendingDataForSave.cursor);
        this.pendingDataForSave = null;
    },

    replaceAllWith: function (qualityId, replacement, saveAsDraft, cursor, omitCurrent) {
        const params = {
            qualityId: qualityId,
            saveCurrentDraft: saveAsDraft,
            replaceField: replacement,
            async: false,
            omitCurrent: omitCurrent,
        };

        // To enable this we need to refactor the worker
        // if (Editor.data.plugins.hasOwnProperty('FrontEndMessageBus')) {
        //     params.async = true;
        // }

        const segmentStore = this.getSegmentsStore();
        const proxy = segmentStore.getProxy();
        params[proxy.getFilterParam()] = proxy.encodeFilters(segmentStore.getFilters().items);
        params[proxy.getSortParam()] = proxy.encodeSorters(segmentStore.getSorters().items);
        params['taskGuid'] = Editor.data.task.get('taskGuid');

        if (this.isActiveTrackChanges()) {
            params['isActiveTrackChanges'] = true;
            params['attributeWorkflowstep'] =
                Editor.data.task.get('workflowStepName') + Editor.data.task.get('workflowStep');
            params['userTrackingId'] = Editor.data.task.get('userTrackingId');
            params['userColorNr'] = Editor.data.task.get('userColorNr');
        }

        const win = this.getSpellCheckerWindow();
        win.setLoading(true);

        Ext.Ajax.request({
            url: Editor.data.restpath + `plugins_spellcheck_spellcheckquery/replaceall`,
            method: 'POST',
            params: params,
            success: (xhr) => {
                win.setLoading(false);
                this.getSegmentsStore().reload({
                    scope: this,
                    callback: function () {
                        this.next(cursor);
                    },
                });

                this.reloadQualitiesPanel();
            },
            failure: (response) => {
                win.setLoading(false);
                Editor.app.getController('ServerException').handleException(response);
            },
        });
    },

    reloadQualitiesPanel: function () {
        const qfp = Ext.ComponentQuery.query('qualityFilterPanel').pop();

        if (qfp) {
            qfp.getController().reloadKeepingFilterVal();
        }
    },

    reloadFalsePositivePanel: function (segmentId) {
        Editor.app.getController('Editor.controller.MetaPanel').reloadStore(segmentId);
    },

    /**
     * @returns {Editor.view.segments.Grid}
     */
    getSegmentGrid: function () {
        return Ext.ComponentQuery.query('#segmentgrid')[0];
    },

    getSegmentsStore: function () {
        return Ext.getStore('Segments');
    },

    getSpellCheckerWindow: function () {
        return Ext.ComponentQuery.query('spellcheckwindow')[0] ?? Ext.widget('spellcheckwindow');
    },

    getSelectedCategory: function () {
        const win = this.getSpellCheckerWindow();

        return (selectedCategory = win.down('#cmbCategory')?.getValue());
    },

    /**
     * @returns {Editor.controller.Editor}
     */
    getEditorController: function () {
        return this.getController('Editor.controller.Editor');
    },

    isActiveTrackChanges: function () {
        if (!Editor.plugins.TrackChanges) {
            return false;
        }

        return !(
            Editor.data.task.get('workflowStepName') === 'translation' && Editor.data.task.get('workflowStep') === '1'
        );
    },
});
