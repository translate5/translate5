Ext.define('Editor.view.segments.new.RowEditorNew', {
    extend: 'Ext.grid.RowEditor',
    itemId: 't5RowEditor', //'segmentsHtmleditor', TODO don't forget to rename all listeners

    requires: [
        'Editor.view.segments.new.EditorNew',
    ],

    userCls: 'segment-font-sizable',
    liveDrag: true,
    rowToEditOrigHeight: 0,
    editorExtraHeight: 0, // 20,
    editorLocalTop: 0,
    firstEdit: true,

    /**
     * If set to true, rowEditor remains on its position on startEdit and grid scrolls instead
     */
    isScrollUnderMoveMode: false,

    columns: null,
    statusStrip: null,

    strings: {
        tagOrderErrorText: '#UT# Einige der im Segment verwendeten Tags sind in der falschen Reihenfolgen (schließender vor öffnendem Tag).',
        tagMissingText: '#UT#Folgende Tags fehlen:<br/><ul><li>{0}</li></ul>So entfernen Sie den Fehler:<br/><ul><li>Klicken Sie auf OK, um diese Nachricht zu entfernen</li><li>Drücken Sie ESC, um das Segment ohne Speichern zu verlassen</li><li>Öffnen Sie das Segment erneut</li></ul>Wiederholen Sie jetzt Ihre Änderungen.<br/>Verwenden Sie alternativ die Hilfeschaltfläche, und suchen Sie nach Tastenkombinationen, um die fehlenden Tags aus der Quelle einzugeben.',
        tagDuplicatedText: '#UT#Die nachfolgenden Tags wurden beim Editieren dupliziert, das Segment kann nicht gespeichert werden. Löschen Sie die duplizierten Tags. <br />Duplizierte Tags:{0}',
        tagExcessText: '#UT#Die folgenden Tags existieren nicht in der Quellsprache:<br/><ul><li>{0}</li></ul>So entfernen Sie den Fehler:<br/><ul><li>Klicken Sie auf OK, um diese Nachricht zu entfernen</li><li>Drücken Sie ESC, um das Segment ohne Speichern zu verlassen</li><li>Öffnen Sie das Segment erneut</li></ul>Wiederholen Sie jetzt Ihre Änderungen.',
        tagRemovedText: '#UT# Es wurden Tags mit fehlendem Partner entfernt!',
        cantEditContents: '#UT#Es ist Ihnen nicht erlaubt, den Segmentinhalt zu bearbeiten. Bitte verwenden Sie STRG+Z um Ihre Änderungen zurückzusetzen oder brechen Sie das Bearbeiten des Segments ab.',
    },

    initComponent: function () {
        console.log('t5RowEditor initComponent');

        this.columns = new Ext.util.HashMap();

        // initialize a scroller instance for maintaining horizontal scroll position
        this.setScrollable({
            x: false,
            y: false
        });

        this.callParent();

        this.layout = {
            type: 'hbox',
            align: 'begin'
        };

        this.on('afterlayout', this.onAfterLayout, this);

        this.mainEditor = this.add(new Editor.view.segments.new.EditorNew({editingPlugin: this.editingPlugin}));

        this.mon(Ext.GlobalEvents, {
            scope: this,
            show: this.repositionIfVisible
        });

        this.fireEvent('initialize', this);
    },

    isSourceEditing: function () {
        return this.columnToEdit === "sourceEdit";
    },

    /**
     * cancels the editing process
     */
    cancelEdit: function () {
        //console.log("RowEditorNew.cancelEdit");
        let me = this;

        me.context.record.reject();
        me.getTimeTrackingData();

        me.restoreEditingRowHeight();
        me.callParent(arguments);

        me.mainEditor.cancelEdit();
    },

    completeEdit: function () {
        //console.log("RowEditorNew.completeEdit");
        let me = this,
            rec = me.context.record;

        if (!me.saveMainEditorContent(rec)) {
            return false;
        }

        me.stopTimeTrack(me.columnToEdit);
        //we have to provide the durations to the change alike handler,
        //since the record is available there, we put them into a tmp field,
        //the set durations field is overwritten / cleared by successful PUT
        rec.set('durations', me.getTimeTrackingData());

        me.restoreEditingRowHeight();
        me.hide();

        me.previousRecord = rec;
        return true;
    },

    //
    // Grid listener added when this is rendered.
    // Keep our containing element sized correctly
    // @override
    //
    onGridResize: function () {
        this.setEditorWidth();
    },

    // /**
    //  * implements mouse dragging of the row editor
    //  */
    // onBoxReady: function () {
    //     let me = this,
    //         grid = me.editingPlugin.grid,
    //         ddConfig = {
    //             el: me.el,
    //             constrain: true,
    //             constrainDelegate: true,
    //             listeners: {
    //                 dragend: function () {
    //                     me.editorLocalTop = me.getOffsetsTo(grid.body)[1];
    //                 }
    //             },
    //             constrainTo: grid.body,
    //             //constrainTo: me.view.body//,
    //             delegate: '.x-field:not(.segment-content) .x-form-display-field-body'
    //         };
    //
    //     me.dd = new Ext.util.ComponentDragger(me, ddConfig);
    //     //override onDrag so that component can only be moved vertically.
    //     //This would not be needed if we use view body as constrainTo
    //     //but we must use grid.body, since view.body will have the height of the available segments,
    //     //which is to less if only 2 segments are available by filter for example
    //     me.dd.onDrag = function (e) {
    //         let me = this,
    //             comp = (me.proxy && !me.comp.liveDrag) ? me.proxy : me.comp,
    //             offset = me.getOffset(me.constrain || me.constrainDelegate ? 'dragTarget' : null);
    //
    //         comp.setPagePosition(me.startPosition[0], me.startPosition[1] + offset[1]);
    //     }.bind(me.dd);
    //
    //     me.relayEvents(me.dd, ['dragstart', 'drag', 'dragend']);
    // },

    /**
     * @override
     */
    onViewScroll: function () {
        //console.log("RowEditorNew.onViewScroll");

        let me = this,
            scrollLeft = me.scrollingView.getScrollX(),
            scrollLeftChanged = scrollLeft !== me.lastScrollLeft;

        me.callParent();

        // Fix for TRANSLATE-1031:
        // If you scroll right in the editor before you have opened the editor the first time,
        // and then you open the editor, the content of the roweditor is shifted right.
        // This seems to be a bug in chrome:
        // therefore we use lastScrollLeft to shift the editor to the correct position
        // if the grid was scrolled after the editor has opened once - we need no shift anymore
        // and force lastScrollLeft to be zero
        if (me.firstEdit) {
            me.lastScrollLeft = scrollLeft;
        } else {
            me.lastScrollLeft = 0;
        }

        if (scrollLeftChanged) {
            me.reposition();
        }
    },

    insertColumnEditor: function (column) {
        //console.log("RowEditorNew.insertColumnEditor");
        let me = this;

        me.callParent(arguments);

        if (column.getEditor) {
            me.columns.add(column.getEditor().id, column);
        }
    },

    /**
     * Doing nothing with the tab key, since navigation is done by our own keys
     * @param {} e
     */
    onFieldTab: function (e) {
    },

    getRefItems: function (deep) {
        let me = this;
        //using the panels getRefItems method instead the roweditor one
        return me.superclass.superclass.getRefItems.apply(me, arguments);
    },

    /**
     * ensures that the roweditor stays at the initial opened position
     * @param {} animateConfig
     * @param {} fromScrollHandler
     */
    reposition: function (animateConfig, fromScrollHandler) {
        let me = this;
        me.callParent();
        me.el.setLocalX(-me.lastScrollLeft);
        me.setEditorWidth();
        me.fireEvent('repositioned', this);
    },

    /**
     * WARNING: why ever this method is not called anymore. Because of the buffered renderer?
     * overriding to remain editor open on view refresh
     * @param {} view
     */
    onViewRefresh: function (view) {
        //console.log("RowEditorNew.onViewRefresh");

        let me = this,
            context = me.context,
            row;
        // Recover our row node after a view refresh
        if (context && (row = view.getRow(context.record))) {
            context.row = row;
            me.reposition();

            if (me.tooltip && me.tooltip.isVisible()) {
                me.tooltip.setTarget(context.row);
            }
        }
    },

    /**
     * sets the initial position of the roweditor after opening a segment
     */
    initialPositioning: function () {
        //console.log("RowEditorNew.initialPositioning");

        let me = this,
            context = me.context,
            grid = me.editingPlugin.grid,
            row = context && context.row,
            rowIdx = context && context.rowIdx,
            rowTop,
            moveEditor;

        // Position this editor if the context row is rendered (buffered rendering may mean that it's not in the DOM at all)
        if (!row || !Ext.isElement(row)) {
            return;
        }
        moveEditor = function () {
            // Get the y position of the row relative to its top-most static parent.
            // offsetTop will be relative to the table, and is incorrect
            // when mixed with certain grid features (e.g., grouping).
            rowTop = Ext.fly(row).getOffsetsTo(grid.body)[1] - grid.el.getBorderWidth('t') + me.lastScrollTop;
            me.editorLocalTop = me.calculateEditorTop(rowTop);
            me.reposition();
        };
        if (me.isScrollUnderMoveMode) {
            //giving the finalScroller as fallback handler to the scroll command
            grid.scrollTo(rowIdx, {
                target: 'editor',
                notScrollCallback: moveEditor
            });
        } else {
            moveEditor();
        }
    },

    /**
     * same as original, expect the button height.
     * @return {Number} the scroll delta. Zero if scrolling is not required.
     */
    getScrollDelta: function () {
        let me = this,
            scrollingViewDom = me.scrollingViewEl.dom,
            context = me.context,
            body = me.body,
            deltaY = 0;

        if (context) {
            deltaY = Ext.fly(context.row).getOffsetsTo(scrollingViewDom)[1];
            if (deltaY < 0) {
                deltaY -= body.getBorderPadding().beforeY;
            } else if (deltaY > 0) {
                deltaY = Math.max(deltaY + me.getHeight() -
                    scrollingViewDom.clientHeight - body.getBorderWidth('b'), 0);
                if (deltaY > 0) {
                    deltaY -= body.getBorderPadding().afterY;
                }
            }
        }
        return deltaY;
    },

    /***
     * Get editor visible scroll height
     */
    getScrollDeltaCustom: function () {
        let me = this,
            scrollingViewDom = me.scrollingViewEl.dom,
            context = me.context,
            body = me.body,
            deltaY = 0;

        if (!context) {
            return deltaY;
        }
        deltaY = Ext.fly(context.row).getOffsetsTo(scrollingViewDom)[1];
        if (deltaY < 0) {
            deltaY -= body.getBorderPadding().beforeY;
        } else if (deltaY > 0) {
            deltaY = Math.max(deltaY + me.getHeight(), 0);
            if (deltaY > 0) {
                deltaY -= body.getBorderPadding().afterY;
            }
        }
        return deltaY;
    },

    /**
     * Start editing the specified grid at the specified position.
     * @param {Ext.data.Model} record The Store data record which backs the row to be edited.
     * @param {Ext.data.Model} columnHeader The Column object defining the column to be focused
     */
    startEdit: function (record, columnHeader) {
        //console.log("RowEditorNew.startEdit");

        this.callParent(arguments);

        //me.mainEditor.startEdit(context.cell, me.mainEditor.getValue());
        this.editing = true;

        this.focusContextCell();

        this.fireEvent('afterStartEdit', this);
    },

    /**
     * Start editing the specified grid at the specified position.
     * The valid modes:
     * 0: for default positioning
     * 1: for scroll grid instead move editor
     * => The values are defined as constants in the RowEditor Plugin
     * @param {int} mode
     */
    setMode: function (mode) {
        this.isScrollUnderMoveMode = (mode === 1);
    },

    /**
     * just returns the given delta, since buttons are disabled
     * @param {} delta
     * @return {}
     */
    syncButtonPosition: function (delta) {
        return delta;
    },

    setEditorHeight: function () {
        //console.log("RowEditorNew.setEditorHeight");
        // return;

        let me = this,
            context = me.context,
            row = Ext.get(context.row),
            rowHeight = row.setHeight(null) && row.getHeight(), //force recalculation on each call
            //height of the whole roweditor, contains doubled extraHeight
            editorHeight,
            moveEditor;

        // when switching tag mode recalculation is triggered,
        //  but when the row is not available anymore due grid reload we don't have any height information
        //  in this case its better to leave the old height instead of use a rowHeight of 0
        if (rowHeight === 0 && me.rowToEditOrigHeight > 0) {
            return;
        }

        me.rowToEditOrigHeight = rowHeight;
        me.mainEditor.setHeight(rowHeight + me.editorExtraHeight); //+ 10
        //get height again, since htmlEditor could modify / increase the height itself
        // editorHeight = me.mainEditor.getHeight() + me.editorExtraHeight; //add the extra height again
        editorHeight = me.mainEditor.height + me.editorExtraHeight; //add the extra height again
        moveEditor = (me.editorLocalTop + editorHeight) - me.scrollingView.getHeight()
        row.setHeight(editorHeight);

        //low border of editor is outside of the visible area, then we have to move the editor additionaly
        if (moveEditor > 0) {
            me.editorLocalTop -= moveEditor;
            me.editorLocalTop = Math.max(me.editorLocalTop, 0);
            me.reposition();
        }

        me.setHeight(editorHeight);
    },

    restoreEditingRowHeight: function () {
        //console.log("RowEditorNew.restoreEditingRowHeight");
        // return;

        let me = this,
            context = me.context,
            row = Ext.get(context.row);

        //setting to null triggers auto calculation again (which was the default before editing)
        row && row.setHeight(null);
        me.rowToEditOrigHeight = 0;
    },

    /**
     * set the editor width to the maximal visible width,
     * provides a horizontal clipping of the roweditor.
     */
    setEditorWidth: function () {
        //console.log("RowEditorNew.setEditorWidth");
        // return;

        let me = this,
            editingPlugin = me.editingPlugin,
            grid = editingPlugin.grid,
            i, columnsWidth = 0,
            viewEl = me.editingPlugin.view.el,
            dom = viewEl.dom,
            scrollbarWidth = (dom.scrollHeight > dom.clientHeight ? Ext.getScrollbarSize().width : 0);

        //the internal syncEditorClip makes a top / bottom clipping.
        //We need instead a clipping at the right.
        me.setWidth(viewEl.getWidth() + me.lastScrollLeft - scrollbarWidth);
    },

    /**
     * overriden for wrapEl disabling and initial positioning
     */
    onShow: function () {
        //console.log("RowEditorNew.onShow");
        let me = this;

        me.callParent(arguments);

        me.setEditorHeight();
        me.setEditorWidth();
        me.initialPositioning();
    },

    /**
     * overriden for wrapEl disabling
     * @return {}
     */
    onHide: function () {
        //console.log("RowEditorNew.onHide");
        let me = this,
            column,
            focusContext;

        // Try to push focus into the cell below the active field
        if (me.activeField) {
            column = me.activeField.column;
            focusContext = new Ext.grid.CellContext(column.getView()).setPosition(me.context.record, column);
            focusContext.view.getNavigationModel().setPosition(focusContext);
            me.activeField = null;
        }
        me.superclass.superclass.onHide.apply(me, arguments);
        if (me.tooltip) {
            me.hideToolTip();
        }
    },

    onResize: function () {
        //console.log("RowEditorNew.onResize");
        this.setEditorWidth();
    },

    beforeDestroy: function () {
        Ext.destroy(this.tooltip);
        this.callParent();
    },


    /**
     * handles clicking on the displayfields of the roweditor to change the editor position
     * @param {Ext.Event} ev
     * @param {DOMNode} target
     */
    changeColumnByClick: function (ev, target) {
        let me = this,
            cmp = null;

        //bubble up to the dom element which is the el of the Component
        while (target && target.nodeType === 1) {
            if (/displayfield-[0-9]+/.test(target.id)) {
                cmp = me.columns.get(target.id);
                if (cmp) {
                    break;
                }
            }
            target = target.parentNode;
        }

        if (cmp && cmp.dataIndex != "source") { // when the dblclick comes from a source of an opened segment, the user might want to select a word for copy & paste
            me.changeColumnToEdit(cmp);
        }
    },

    /**
     * changes the maineditor to the given column
     * @param {Editor.view.segments.column.ContentEditable} column
     */
    changeColumnToEdit: function (column) {
        let me = this,
            oldIdx = me.columnToEdit,
            rec = me.context.record,
            oldField = me.query('displayfield[name="' + oldIdx + '"]');

        if (oldIdx == column.dataIndex) {
            //column did not change
            return;
        }

        if (!me.saveMainEditorContent(rec)) {
            return; //errors on saving, also do not change
        }

        //sync content back to the displayfield
        if (oldField && oldField.length > 0) {
            oldField[0].setValue(rec.get(oldIdx));
        }

        if (me.setColumnToEdit(column)) {
            // me.mainEditor.setValueAndMarkup(rec.get(me.columnToEdit), rec, me.columnToEdit);
            me.mainEditor.setValue(rec.get(me.columnToEdit), rec, me.columnToEdit);
        }

        me.focusContextCell();
    },

    /**
     * Method Implements that we can have multiple editable columns, but only one HtmlEditor Instance
     * This is done by swaping the position of the different field editors
     *
     * @param {Ext.grid.column.Column} col
     * @return {Boolean} returns true if column has changed, false otherwise
     */
    setColumnToEdit: function (col) {
        let me = this,
            firstTarget = Editor.view.segments.column.ContentEditable.firstTarget, //is the dataIndex
            toEdit = col.dataIndex,
            hasToSwap = false,
            linkedDisplayField = null;

        if (col.segmentField) {
            me.mainEditor.fieldTypeToEdit = col.segmentField.get('type');
        }

        //if user clicked on a not content column open default dataindex (also if it is a content column but not editable)
        if (!col.segmentField || !col.segmentField.get('editable')) {
            toEdit = firstTarget;
            me.mainEditor.fieldTypeToEdit = Editor.model.segment.Field.prototype.TYPE_TARGET;
        } else if (col.isContentColumn && !col.isEditableContentColumn) {
            //if it's the readonly column take the edit one
            toEdit = col.dataIndex + 'Edit';
        }

        //no swap if last edited column was the same
        hasToSwap = me.columnToEdit !== toEdit;

        if (hasToSwap && me.columnToEdit) {
            me.stopTimeTrack(me.columnToEdit);
        }

        me.startTimeTrack();

        me.items.each(function (field) {
            if (!me.columns.containsKey(field.id)) {
                return; //ignore the editor itself, which has no col mapping
            }
            let vis = me.columns.get(field.id).isVisible();
            if (field.name == toEdit && vis) {
                linkedDisplayField = field;
                return;
            }
        });

        //all editor fields disabled
        if (!linkedDisplayField || !hasToSwap) {
            if (!linkedDisplayField) {
                me.linkedDisplayField = false;
            }
            return false;
        }

        me.columnToEdit = toEdit;
        me.columnClicked = col.dataIndex;
        me.mainEditor.dataIndex = col.dataIndex;

        // me.mainEditor.setDirectionRtl(Editor.model.segment.Field.isDirectionRTL(me.mainEditor.fieldTypeToEdit));

        //if isset linkedDisplayField the cols get changed in focusContextCell
        me.linkedDisplayField = linkedDisplayField;

        return true;
    },

    onAfterLayout: function () {
        let me = this,
            toDis = me.linkedDisplayField,
            pos;

        if (!me.mainEditor || !toDis) {
            return;
        }

        pos = toDis.getPosition(true);
        //swap position
        me.repositionMainEditor(pos[0]);
    },

    /**
     * overrides original focusing with our repositioning of the editor
     */
    focusContextCell: function () {
        let me = this,
            toDis = me.linkedDisplayField,
            pos;

        if (!me.mainEditor) {
            return;
        }

        if (!toDis) {
            // me.mainEditor.deferFocus();
            return;
        }

        pos = toDis.getPosition(true);

        //disable editor if column was also disabled
        me.mainEditor.setWidth(toDis.width);
        //swap position
        me.repositionMainEditor(pos[0]);
        me.scrollMainEditorHorizontallyInView();
        // me.mainEditor.deferFocus();
        me.mainEditor.setVisible(toDis.isVisible());
        me.fireEvent('afterEditorMoved', me.columnToEdit, me);

        return true;
    },

    /**
     * repositions the grid view so that, the mainEditor is visible after change the editing column
     */
    scrollMainEditorHorizontallyInView: function () {
        let me = this,
            view = me.editingPlugin.grid.getView(),
            gridReg = view.getEl().getRegion(),
            offset,
            edReg = me.mainEditor.getEl().getRegion();

        if (gridReg.contains(edReg)) {
            return;
        }

        me.isRunningHorizontalScrolling = true;

        if (edReg.right > gridReg.right) {
            offset = -1 * gridReg.getOutOfBoundOffsetX(edReg.right) + 30;
            view.scrollBy(offset, 0, false);
        } else {
            offset = -1 * gridReg.getOutOfBoundOffsetX(edReg.x) - 20;
            view.scrollBy(offset, 0, false);
        }
    },

    /**
     * place the HtmlEditor/MainEditor in the rowEditor over the desired displayfield
     */
    repositionMainEditor: function (newX) {
        let me = this;

        if (newX || newX === 0) {
            me.editorNewXPosition = newX;
        } else {
            newX = me.editorNewXPosition;
        }

        me.mainEditor.setPosition(newX, 0);
    },

    /**
     * reusable method to get info which field is edited by opened editor
     * returns the dataIndex of the field
     * @return {String}
     */
    getEditedField: function () {
        return this.columnToEdit;
    },

    /**
     * shows / hides the main editor, used as show hide column handler
     */
    toggleMainEditor: function (show) {
        this.mainEditor.setVisible(show);
    },

    loadRecord: function (record) {
        let me = this;
        me.callParent(arguments);

        me.setColumnToEdit(me.context.column);
        // me.mainEditor.setValueAndMarkup(record.get(me.columnToEdit), record, me.columnToEdit);
        me.mainEditor.setValue(record.get(me.columnToEdit), record, me.columnToEdit);

        //init internal markup table for tag check, but only if a translation task
        if (Editor.data.task.get('emptyTargets')) {
            // me.mainEditor.insertMarkup(record.get('source'), true);
        }
    },

    /**
     * saves the Editor Content into the loaded record
     * @param {Editor.model.Segment} record
     * @returns {Boolean}
     */
    saveMainEditorContent: function (record) {
        let me = this,
            plug = me.editingPlugin,
            // the cleanInvisibleCharacters call removes invisible characters automatically added by the editor,
            // thus preventing the record from being marked as modified when the content has not actually changed
            data = me.mainEditor.editor.getDataT5Format();
        // newValue = me.cleanInvisibleCharacters(me.mainEditor.getValueAndUnMarkup()),
        let newValue = data.data;

        //check, if the context delivers really the correct record, because through some issues in real life data
        //rose the idea, that there might exist special race conditions, where
        //the context.record is not the record of the newValue
        if (!plug.context || !plug.context.record || plug.context.record.internalId !== record.internalId) {
            let title = me.messages.segmentNotSavedUserMessage + newValue;
            let msg = ' Das Segment konnte nicht gespeichert werden. Im folgenden der Debug-Werte: ' + newValue;

            if (plug.context && plug.context.record) {
                msg += 'me.editingPlugin.context.record.internalId: ' + me.editingPlugin.context.record.internalId;
            }

            msg += ' record.internalId: ' + record.internalId;
            Editor.MessageBox.addError(title, msg);

            return false;
        }

        // Remove all TrackChanges Markup
        // var cleanValue = me.cleanForSaveEditorContent(newValue);
        let cleanValue = newValue;

        if (cleanValue.length === 0 && record.get(me.columnToEdit).length > 0) {
            Editor.MessageBox.addError(me.messages.cantSaveEmptySegment);

            return false;
        }

        if (me.hasAndDisplayErrors(me.context.record, data.checkResult)) {
            return false;
        }

        record.beginEdit();
        record.set(me.columnToEdit, newValue);
        record.set('autoStateId', 999);
        //update the segment length in the metaCache of the edited segment:
        // FIXME
        // record.updateMetaCacheLength(me.columnToEdit, me.mainEditor.getLastSegmentLength());

        record.endEdit();

        return true;
    },

    // TODO refactor this method
    hasAndDisplayErrors: function (currentSegment, tagsCheckResult) {
        let me = this,
            msg = '';
        const editorWrapper = this.editingPlugin.editor.mainEditor.editor;

        const minMaxLength = Ext.ComponentQuery.query('#segmentMinMaxLength')[0];
        const segmentLengthStatus = minMaxLength.getMinMaxLengthStatus(editorWrapper.getRawData());

        // if the segment length is not in the defined range, add an error message - not disableable, so before disableErrorCheck
        if (!segmentLengthStatus.includes('segmentLengthValid')) {
            msg = minMaxLength.renderErrorMessage(segmentLengthStatus);
            me.fireEvent('contentErrors', me, msg, false);

            return true;
        }

        // if we are running a second time into this method triggered by callback,
        // the callback can disable a second error check
        if (me.disableErrorCheck) {
            me.fireEvent('contentErrors', me, null, true);
            me.disableErrorCheck = false;

            return false;
        }

        // since this error can't be handled somehow, we don't fire an event but show the message and stop immediately
        if (Editor.data.task.get('notEditContent') && me.contentEdited) {
            Editor.MessageBox.addError(me.strings.cantEditContents);

            return true;
        }

        if (tagsCheckResult.isSuccessful()) {
            me.fireEvent('contentErrors', me, null, true);

            return false;
        }

        const referenceField = this.mainEditor.getReferenceField(
            currentSegment.get('target'),
            currentSegment.get('pretrans'),
            currentSegment.get('matchRateType')
        );

        // first item the field to check, second item: the error text:
        let todo = [
            ['missingTags', referenceField === 'source' ? 'tagMissingTextSource' : 'tagMissingTextTarget'],
            ['duplicatedTags', 'tagDuplicatedText'],
            ['excessTags', referenceField === 'source' ? 'tagExcessTextSource' : 'tagExcessTextTarget'],
        ];

        for (let i = 0; i < todo.length; i++) {
            let missingSvg = '';

            if (tagsCheckResult[todo[i][0]].length > 0) {
                msg += me.strings[todo[i][1]];

                tagsCheckResult[todo[i][0]].forEach((tag) => {
                    missingSvg += tag.outerHTML;
                });

                msg = Ext.String.format(msg, missingSvg);
                msg += '<br /><br />';
            }
        }

        if (!tagsCheckResult.tagsOrderCorrect) {
            msg += me.strings.tagOrderErrorText;
        }

        me.fireEvent('contentErrors', me, msg, true);

        return true;
    },

    /**
     * starts tracking the editing time for the given field
     */
    startTimeTrack: function () {
        if (!this.timeTrackingData) {
            this.timeTrackingData = {};
        }

        this.timeTrackingData._start = new Date();
    },
    /**
     * stops and saves the elapsed milliseconds since last startTimeTrack call for the given field
     * @param {String} field
     */
    stopTimeTrack: function (field) {
        let end = new Date(),
            data = this.timeTrackingData,
            value = data[field],
            duration;

        if (!data._start) {
            return;
        }

        duration = end - data._start;
        delete (data._start);

        if (value) {
            duration += value;
        }

        data[field] = duration;
    },
    /**
     * resets the tracking information and returns them as a object
     * @return {Object}
     */
    getTimeTrackingData: function () {
        let result = this.timeTrackingData;
        delete (result._start);
        this.timeTrackingData = {};

        return result;
    },

    /**
     * disables the hasAndDisplayErrors method on its next call, used for save and ignore the tag checks
     */
    disableContentErrorCheckOnce: function () {
        this.disableErrorCheck = true;
    },
});
