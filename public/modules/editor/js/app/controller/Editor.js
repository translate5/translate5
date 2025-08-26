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

/**
 * Editor Controller
 * @class Editor.controller.Editor
 * @extends Ext.app.Controller
 */
Ext.define('Editor.controller.Editor', {
    extend: 'Ext.app.Controller',
    requires: [
        'Editor.view.segments.EditorKeyMap',
        'Editor.controller.editor.PrevNextSegment',
        'Editor.view.task.ConfirmationWindow',
        // 'Editor.view.ReferenceFilesInfoMessage',
        'Editor.view.task.QuickSearchInfoMessage'
    ],
    mixins: [
        'Editor.util.Event',
        'Editor.util.Range'
    ],
    messages: {
        segmentReset: '#UT#Das Segment wurde auf den ursprünglichen Zustand nach dem Import zurückgesetzt.',
        segmentNotBuffered: '#UT#Das nächste / vorherige Segment wird noch geladen, bitte versuchen Sie es erneut.',
        segmentStillSaving: '#UT#Das nächste / vorherige Segment wird noch gespeichert, bitte warten...',
        segmentsChanged: '#UT#Die Sortierung bzw. Filterung wurde geändert, es kann kein nächstes / vorheriges Segment ausgewählt werden.',
        segmentsChangedJump: '#UT#Die Sortierung bzw. Filterung wurde geändert, es kann nicht zum aktuellen Segment zurück gesprungen werden.',
        f2FirstOpened: '#UT#Das erste bearbeitbare Segment wurde geöffnet, da kein anderes Segment ausgewählt war.',
        f2Readonly: '#UT#Das ausgewählte Segment ist nicht bearbeitbar!',
        errorTitle: '#UT# Fehler bei der Segment Validierung!',
        correctErrorsText: '#UT# Fehler beheben',
        editorMoveTitle: '#UT#Verschiebbarer Editor und hilfreiche Tastaturkürzel',
        editorMove: '#UT#Der Segmenteditor kann mit der Maus beliebig positioniert werden. <br />Dazu lediglich den Segmenteditor anklicken und dann verschieben.',
        takeTagTooltip: '#UT#STRG + EINFG (alternativ STRG + . (Punkt)) kopiert den kompletten Quelltext in den Zieltext<br />STRG + , (Komma) + &gt;Nummer&lt; kopiert den entsprechenden Tag in den Zieltext (Null entspricht Tag Nr. 10)<br />STRG + SHIFT + , (Komma) + &gt;Nummer&lt; kopiert die Tags mit den Nummern 11 bis 20 in den Zieltext.',
        saveAnyway: '#UT# Trotzdem speichern',
        doubleclickToTakeMatch: '#UT# Doppelklick auf die Zeile übernimmt diesen Match in das geöffnete Segment.',
        noVisibleContentColumn: '#UT#Ausgeblendete bearbeitbare Spalten wurden sichtbar geschaltet, da die Aufgabe zum Editieren geöffnet wurde, aber keine editierbare Spalte sichtbar war.',
        gridEndReached: '#UT#Kein weiteres Segment bearbeitbar!',
        gridStartReached: '#UT#Kein vorheriges Segment bearbeitbar!',
        gridEndReachedWorkflow: '#UT#Kein weiteres Segment im Workflow bearbeitbar!',
        gridEndReachedFiltered: '#UT#Keine weiteren Segmente in der aktuellen Filterung',
        gridStartReachedWorkflow: '#UT#Kein vorheriges Segment im Workflow bearbeitbar!',
        gridStartReachedFiltered: '#UT#Keine vorherigen Segmente in der aktuellen Filterung'
    },
    DEC_DIGITS: [48, 49, 50, 51, 52, 53, 54, 55, 56, 57],
    refs: [
        {
            ref: 'segmentGrid',
            selector: '#segmentgrid'
        },
        // {
        //     ref:'filepanel',
        //     selector:'#filepanel'
        // },
        // {
        //     ref: 'falsePositiveCheckColumn',
        //     selector: '#metapanel #falsePositives grid checkcolumn'
        // }
        {
            ref: 'languageResourceSearchGrid',
            selector: 'languageResourceSearchGrid'
        },
        {
            ref: 'languageResourceEditorPanel',
            selector: 'languageResourceEditorPanel'
        },
        {
            ref: 'synonymSearch',
            selector: '#synonymSearch'
        }
    ],
    registeredTooltips: [],
    isEditing: false,
    isCapturingChange: false,
    keyMapConfig: null,
    editorKeyMap: null,
    generalKeyMap: null,
    prevNextSegment: null,
    sourceTags: null,
    resetSegmentValueForEditor: null,
    htmlEditor: null,

    quickSearchInfoMessage: null,

    taskOpenRequest: false,

    listen: {
        controller: {
            '#Editor.$application': {
                editorViewportClosed: 'onCloseEditorViewport',
                editorViewportOpened: 'onOpenEditorViewport'
            },
            /*'#QualityMqm': {
            	afterInsertMqmTag: 'handleAfterContentChange'
            },
            '#ServerException':{
                serverExceptionE1600: 'onServerExceptionE1600'
            }*/
        },
        component: {
             'segmentsToolbar [dispatcher]' : {
                 click : 'buttonClickDispatcher'
             },
             '#segmentActionMenu menucheckitem': {
                 beforecheckchange: item => item.allowCheckChange,
                 click: 'onSegmentActionMenuItemClick',
                 checkchange: 'onSegmentActionMenuItemToggle'
             },
            '#t5RowEditor': {
                initialize: 'onRowEditorInitialize',
                contentErrors: 'onSaveWithErrors',
                // afterStartEdit: 'onAfterStartEdit',
                // afterSetValueAndMarkup: 'handleAfterContentChange',
                // afterInsertMarkup: 'handleDelayedChange',
            },
            '#t5Editor': {
                afterInstantiateEditor: 'onEditorInstantiate',
                editorDataChanged: 'handleAfterContentChange',
                afterStartEdit: 'onAfterStartEdit',
            },
            'roweditor': {
                destroy: 'handleDestroyRoweditor'
            },
            'roweditor displayfield[isContentColumn!=true]': {
                afterrender: 'initMoveToolTip'
            },
            '#segmentgrid': {
                afterrender: 'initEditPluginHandler',
                select: 'onSegmentGridSelect',
                // segmentSizeChanged:'onSegmentGridSegmentsSizeChanged'
            },
            // '#showReferenceFilesButton': {
            //     click:'onShowReferenceFilesButtonClick'
            // },
            // '#commentContainer textarea': {
            //     specialkey: 'handleCommentEnter'
            // },
            'taskConfirmationWindow button': {
                click:'taskConfirm'
            },
            'segmentsToolbar #btnInsertWhitespaceNbsp': {
                click: 'insertWhitespaceNbsp'
            },
            'segmentsToolbar #btnInsertWhitespaceNewline': {
                click: 'insertWhitespaceNewline'
            },
            'segmentsToolbar #btnInsertWhitespaceTab': {
                click: 'insertWhitespaceTab'
            },
            'segmentsToolbar specialCharactersButton': {
                click: 'insertSpecialCharacter'
            },
            'segmentsToolbar #specialCharactersCombo': {
                change: 'insertWhitespaceCombo'
            },
            // '#segmentMinMaxLength': {
            //     insertNewline: 'insertWhitespaceNewline'
            // },
            // 'segmentsToolbar #specialChars': {
            //     disable: btn => btn.hideMenu()
            // }
        },

        store: {
            '#Segments': {
                load: 'onSegmentsStoreLoad'
            }
        }
    },

    routes: {
        'task/:id/:segmentNrInTask/edit': 'onTaskSegmentEditRoute',
        'task/:id/edit': 'onTaskSegmentEditRoute',
    },

    init: function () {
        var me = this;

        Ext.override('Ext.util.KeyMap', {
            handleTargetEvent: Editor.view.segments.EditorKeyMap.handleTargetEvent
        });

        // -------------------------------------------------------------------------------------
        // set the default config
        // -------------------------------------------------------------------------------------
        // 'xyz': [key(s), {ctrl, alt, shift}, fn, defaultEventAction==stopEvent, (optional:) scope]
        // -------------------------------------------------------------------------------------
        // **** CAUTION: with any changes, please check if they affect Editor.util.Event ****
        // -------------------------------------------------------------------------------------
        // For a complete overview of all keyboard-shortcuts (including those from plugins) see:
        // https://confluence.translate5.net/display/BUS/Editor+keyboard+shortcuts
        // -------------------------------------------------------------------------------------
        me.keyMapConfig = {
            'ctrl-d': ['D', {ctrl: true, alt: false}, me.watchSegment, true],
            'ctrl-s': ['S', {ctrl: true, alt: false}, me.save, true],
            'ctrl-g':         ['G',{ctrl: true, alt: false}, me.focusSegmentShortcut, true],
            // 'ctrl-z':         ['Z',{ctrl: true, alt: false}, me.undo],
            // 'ctrl-y':         ['Y',{ctrl: true, alt: false}, me.redo],
            'ctrl-l':         ['L',{ctrl: true, alt: false}, me.toggleSegmentLock, true],
            'ctrl-enter':     [[10,13],{ctrl: true, alt: false}, me.saveNextByWorkflow],
            'ctrl-alt-enter': [[10,13],{ctrl: true, alt: true, shift: false}, me.saveNext],
            'ctrl-alt-shift-enter': [[10,13],{ctrl: true, alt: true, shift: true}, me.savePrevious],
            'esc': [Ext.EventObjectImpl.ESC, null, me.cancel],
            // 'ctrl-alt-left':  [Ext.EventObjectImpl.LEFT,{ctrl: true, alt: true}, me.goToLeft],
            // 'ctrl-alt-right': [Ext.EventObjectImpl.RIGHT,{ctrl: true, alt: true}, me.goToRight],
            'alt-pageup':     [Ext.EventObjectImpl.PAGE_UP,{ctrl: false, alt: true}, me.goToUpperByWorkflowNoSave],
            'alt-pagedown':   [Ext.EventObjectImpl.PAGE_DOWN,{ctrl: false, alt: true}, me.goToLowerByWorkflowNoSave],
            'alt-del':        [Ext.EventObjectImpl.DELETE,{ctrl: false, alt: true}, me.resetSegment],
            'ctrl-alt-up':    [Ext.EventObjectImpl.UP,{ctrl: true, alt: true}, me.goToUpperNoSave, true],
            'ctrl-alt-down':  [Ext.EventObjectImpl.DOWN,{ctrl: true, alt: true}, me.goToLowerNoSave, true],
            'alt-c':          ['C', {ctrl: false, alt: true}, me.handleOpenComments, true],
            'alt-s':          ['S', {ctrl: false, alt: true}, me.handleDigitPreparation(me.handleChangeState), true],
            // 'F2':             [Ext.EventObjectImpl.F2,{ctrl: false, alt: false}, me.handleF2KeyPress, true],
            'F3':             [Ext.EventObjectImpl.F3,{ctrl: false, alt: false}, me.handleF3KeyPress, true],
            'alt-F3':         [Ext.EventObjectImpl.F3,{ctrl: false, alt: true}, me.handleAltF3KeyPress, true],
            'ctrl-insert':       [Ext.EventObjectImpl.INSERT,{ctrl: true, alt: false}, me.copySourceToTarget],
            'ctrl-dot':          [190, {ctrl: true, shift: false, alt: false}, me.copySourceToTarget], //Mac Alternative key code,
            'ctrl-shift-insert': [Ext.EventObjectImpl.INSERT, {ctrl: true, shift: true, alt: false}, me.copyReferenceToTarget],
            'ctrl-shift-dot':    [190, {ctrl: true, shift: true, alt: false}, me.copyReferenceToTarget], //Mac Alternative key code,
            // // DEC_DIGITS:
            // // (If you change the setting for a defaultEventAction for DEC_DIGITS,
            // // please check if eventIsTranslate5() still works as expected
            // // in Editor.util.Event).
            // 'ctrl-alt-DIGIT': [me.DEC_DIGITS,{ctrl: true, alt: true}, me.toggleFalsePositive, true],
            'alt-DIGIT': [me.DEC_DIGITS, {ctrl: false, alt: true}, me.handleAssignMQMTag, true],
            'DIGIT': [me.DEC_DIGITS, {ctrl: false, alt: false}, me.handleDigit],
            'ctrl-zoomIn': [[187, Ext.EventObjectImpl.NUM_PLUS], {
                ctrl: true,
                alt: false,
                shift: false
            }, me.handleZoomIn, true],
            'ctrl-zoomOut': [[189, Ext.EventObjectImpl.NUM_MINUS], {
                ctrl: true,
                alt: false,
                shift: false
            }, me.handleZoomOut, true],
        };

        // Workaround for the not working zoom in/out handlers in firefox
        if (navigator.userAgent.toLowerCase().includes('firefox')) {
            document.addEventListener('keydown', event => {
                if (event.ctrlKey && ~['+', '=', '-'].indexOf(event.key) && this.getSegmentGrid()) {
                    event.preventDefault();
                    event.key === '-' ? me.handleZoomOut() : me.handleZoomIn();
                }
            });
        }
    },

    /**
     *
     * @returns {boolean}
     */
    onServerExceptionE1600: function (){
        var me = this;

        if(!Editor.data.task)
        {
            // let the error be handled by the default error handler
            return true;
        }
        if(me.taskOpenRequest)
        {
            return false;
        }

        me.taskOpenRequest = true;

        me.openTaskRequest(Editor.data.task.get('id'),function (success,task){
            me.taskOpenRequest = !success;
        });

        return false;
    },

    /**
     * track isEditing state
     */
    initEditPluginHandler: function (segmentsGrid) {
        let me = this,
            plug = me.getEditPlugin(),
            disableEditing = function () {
                let vm = me.getSegmentGrid().lookupViewModel();
                //if needed add current edited segment here too
                vm.set('isEditingSegment', false);
                me.isEditing = false;
            };

        plug.on('beforestartedit', me.handleBeforeStartEdit, me);
        plug.on('beforeedit', me.handleStartEdit, me);
        plug.on('canceledit', disableEditing);
        plug.on('edit', disableEditing);

        me.tooltip = Ext.create('Editor.view.ToolTip', {
            target: segmentsGrid.getEl()
        });

        me.prevNextSegment = Ext.create('Editor.controller.editor.PrevNextSegment', {
            editingPlugin: plug
        });

        me.relayEvents(me.prevNextSegment, ['prevnextloaded']);

        //reset the store next/prev information if data changed
        segmentsGrid.store.on('filterchange', me.handleSortOrFilter, me);
        segmentsGrid.store.on('sort', me.handleSortOrFilter, me);
        segmentsGrid.store.on('prefetch', me.prevNextSegment.clearCalculated, me.prevNextSegment);

        /**
         * disable the column show / hide menu while editing a segment (EXT6UPD-85)
         */
        Ext.override(segmentsGrid.getHeaderContainer(), {
            beforeMenuShow: function(menu) {
                this.callParent([menu]);
                menu.down('#columnItem').setDisabled(plug.editing);
            }
        });

        me.generalKeyMap = new Ext.util.KeyMap(
            Ext.getDoc(),
            me.getKeyMapConfig(
                'application',
                {
                    'alt-c': [
                        'C', {ctrl: false, alt: true},
                        function (key, e) {
                            e.stopEvent();
                            Ext.fireEvent('editorOpenComments');

                            return false;
                        }
                    ]
                }
            )
        );

        //inits the editor directly after loading the application
        plug.editor = plug.initEditor();

        me.handleReferenceFilesMessage();

        //after segment grid is rendered, get the segment grid segment size values and update the html editor text size with those values
        // me.onSegmentGridSegmentsSizeChanged(segmentsGrid,segmentsGrid.newSegmentSizeCls,segmentsGrid.oldSegmentSizeCls);
    },
    
    handleSortOrFilter: function() {
        var me = this,
            plug = me.getEditPlugin();
        
        me.prevNextSegment.clearCalculated();
        if(plug && plug.editor && plug.editor.context) {
            plug.editor.context.reordered = true;
        }
    },
    
    /**
     * initializes the roweditor moveable tooltip
     */
    initMoveToolTip: function(displayfield){
        let me = this,
            id = displayfield.getId()+'-bodyEl';

        if(displayfield.ownQuicktip){
            return;
        }

        me.registeredTooltips.push(id);
        Ext.tip.QuickTipManager.register({
            target: id,
            //title: me.messages.editorMoveTitle,
            text: me.messages.takeTagTooltip
        });
    },

    handleDestroyRoweditor: function() {
        //FIXME needed for Ext 6.2, possibly removable for further ExtJS updates, see T5DEV-172
        let me = this;

        if(me.registeredTooltips && me.registeredTooltips.length > 0) {
            Ext.Array.each(me.registeredTooltips, function(item) {
                if(Ext.tip.QuickTipManager.tip) {
                    delete Ext.tip.QuickTipManager.tip.targets[item];
                }
            });
        }
    },

    /**
     * saves the segment of the already opened editor and restarts startEditing call
     */
    handleBeforeStartEdit: function(plugin, args){
        var me = this,
            segment = args[0],
            i = 0,
            deferInterval;
        //check the content editable column visibility
        me.handleNotEditableContentColumn();

        //if there is already an edited segment, we have to save that first
        if(plugin.editing) {
            // this.fireEvent('prepareTrackChangesForSaving');
            this.fireEvent('saveSegment', {
                scope: this,
                //when the save was successful we open the previously requested segment again
                segmentUsageFinished: function(){
                    plugin.startEdit.apply(plugin, args);
                }
            });
            return false;
        }

        //if segment is editable we proceed with the edit request
        if(segment.get('editable')){
            return true;
        }

        //if segment is not editable due a pending save, we stop the startEdit request and defer it
        if(segment.get('autoStateId') == Editor.data.segments.autoStates.PENDING) {
            //since there is no easy way to attach to the segment save (also it is unsure that a save is called at all)
            // we just make a loop to check if the segment state is not pending anymore
            me.getSegmentGrid().setLoading(me.messages.segmentStillSaving);
            deferInterval = Ext.interval(function(){
                //skip after 6 seconds, with can not edit message
                var skip = i++ > 12,
                    pending = segment.get('autoStateId') == Editor.data.segments.autoStates.PENDING;
                if(skip) {
                    Editor.MessageBox.addInfo(me.messages.f2Readonly);
                }
                //if the segment is not saving anymore, we try to open it
                if(!pending && !plugin.editing) {
                    //if editor is already editing we do not start another one
                     plugin.startEdit.apply(plugin, args);
                }
                if(skip || !pending) {
                    clearInterval(deferInterval);
                    me.getSegmentGrid().setLoading(false);
                }
            },500);
            return false;
        }

        //if we reach here, we just print the segment readonly message
        Editor.MessageBox.addInfo(this.messages.f2Readonly);

        return true;
    },

    handleStartEdit: function (plugin, context) {
        let me = this,
            vm = me.getSegmentGrid().lookupViewModel();
        me.isEditing = true;
        //if needed add current edited segment here too
        vm.set('isEditingSegment', true);
        me.prevNextSegment.calculateRows(context); //context.record, context.rowIdx TODO
    },

    onAfterStartEdit: function (editor) {
        if (this.editorKeyMap) {
            this.editorKeyMap.destroy();
        }

        const el = editor.getEditorBody();

        this.editorKeyMap = new Editor.view.segments.EditorKeyMap({
            target: el,
            binding: this.getKeyMapConfig('editor', {
                // insert editor-specific key events
                'ctrl-comma': [188, {
                    ctrl: true,
                    alt: false,
                    shift: false
                }, this.handleDigitPreparation(this.handleInsertTag), true],
                'ctrl-shift-comma': [188, {
                    ctrl: true,
                    alt: false,
                    shift: true
                }, this.handleDigitPreparation(this.handleInsertTagShift), true],
                'ctrl-shift-space': [Ext.EventObjectImpl.SPACE, {
                    ctrl: true,
                    alt: false,
                    shift: true
                }, () => this.insertWhitespaceNbsp(), true],
                'shift-enter': [Ext.EventObjectImpl.ENTER, {
                    ctrl: false,
                    alt: false,
                    shift: true
                }, () => this.insertWhitespaceNewline(), true],
                'enter': [Ext.EventObjectImpl.ENTER, {
                    ctrl: false,
                    alt: false,
                    shift: false
                }, () => this.insertWhitespaceNewline(), true],
                'tab': [Ext.EventObjectImpl.TAB, {
                    ctrl: false,
                    alt: false
                }, () => this.insertWhitespaceTab(), true]
            })
        });
    },

    /**
     * Gibt die RowEditing Instanz des Grids zurück
     * @returns Editor.view.segments.RowEditing
     */
    getEditPlugin: function () {
        return this.getSegmentGrid().editingPlugin;
    },

    /**
     * converts the here used simple keymap config to the fullblown KeyMap config
     * the simple config contains arrays with the following indizes:
     * 0: key
     * 1: special key config
     * 2: function to be called
     * 3: boolean, if true prepend event propagation stopper
     * 4: scope (optional)
     *
     * @param {String} area, a speakable name where the config is used.
     *    Just passed to the keyMapConfig event to determine there if the event should be processed or not
     * @param {Object} overwrite a config object for dedicated overwriting of key bindings
     */
    getKeyMapConfig: function (area, overwrite) {
        var me = this,
            conf = [],
            overwrite = overwrite || {};

        /*
        * event beforeKeyMapUsage parameters:
        * @param {Editor.controller.Editor}
        * @param {String} area, the area describes where the keymap shall be used.
        * @param {Object} overwrite, the object with overwrite definitions
        */
        me.fireEvent('beforeKeyMapUsage', this, area, overwrite);

        //we may not use me.keyMapConfig to add the overwriten values, since it must remain unchanged,
        // so we do it the other way round and copy all values to the overwrite object:
        Ext.Object.each(me.keyMapConfig, function (key, item) {
            //copy the config to the overwrite object, only if it does not exist already!
            if (overwrite[key]) {
                return;
            }

            overwrite[key] = item;
        });

        //no we process the merged configs:
        Ext.Object.each(overwrite, function (key, item) {
            if (!item) {
                return;
            }

            //applies the keys config and scope to a fresh conf object
            var confObj = Ext.applyIf(
                {
                    key: item[0],
                    scope: item[4] || me
                },
                item[1]
            );

            if (item[3]) {
                confObj.defaultEventAction = 'stopEvent';
                //prepends the event propagation stopper
            }

            confObj.fn = function (key, e) {
                item[2].apply(confObj.scope, arguments);
                //FIXME Ausnahme für digitHandler definieren, wenn nicht im isDigitPreparation Modus!
                return false; //stop further key binding processing
            };

            conf.push(confObj);
        });

        return conf;
    },

    onEditorInstantiate: function (editor) {
        const viewNode = editor.editor.getEditorViewNode();

        viewNode.addEventListener(RichTextEditor.EditorWrapper.EDITOR_EVENTS.ON_SELECTION_CHANGE_COMPLETED, (event) => {
            this.onEditorSelectionChange(event);
        });
    },

    /**
     * binds strg + enter as save segment combination
     * @param {Editor.view.segments.HtmlEditor} editor
     */
    onRowEditorInitialize: function(editor){
        console.log('initEditor');

        let me = this,
            docEl = Ext.get(document);
        this.htmlEditor = editor;

        if (me.editorKeyMap) {
            me.editorKeyMap.destroy();
        }

        docEl.on({
            dragstart:{
                delegated: false,
                priority: 9999,
                fn: this.onDragStart,
                scope: this,
                preventDefault: false
            },
    //     dragend:{
    //         delegated: false,
    //         priority: 9999,
    //         fn: me.handleDragEnd,
    //         scope: this,
    //         preventDefault: false
    //     },
    //     keyup:{
    //         delegated: false,
    //         priority: 9999,
    //         fn: me.handleKeyUp,
    //         scope: this,
    //         preventDefault: false
    //     },
    //     mouseup:{
    //         delegated: false,
    //         priority: 9999,
    //         fn: me.handleMouseUp,
    //         scope: this,
    //         preventDefault: false
    //     },
    //     singletap:{
    //         delegated: false,
    //         priority: 9999,
    //         fn: me.handleMouseUp,
    //         scope: this,
    //         preventDefault: false
    //     },
            copy: {
                delegated: false,
                priority: 9999,
                fn: me.copySelectionWithInternalTags,
                scope: me
            },
    //     cut: {
    //         delegated: false,
    //         priority: 9999,
    //         fn: me.copySelectionWithInternalTags,
    //         scope: me
    //     },
    //     paste: {
    //         delegated: false,
    //         priority: 5000,
    //         fn: me.pasteContent,
    //         scope: me
    //     },
        });


        // Prevent ² and ³ from being inserted into opened segment editor
        // when ctrl+alt+2 and ctrl+alt+3 combination pressed on German keyboard
        // layout and toggle false positives as originally planned
        docEl.dom.addEventListener('beforeinput', function(e) {
            if (e.data === '²' || e.data === '³') {
                e.preventDefault();
                me.toggleFalsePositive(e.data === '²' ? 50 : 51);
            }
        })

    //add second cut handler to remove the content if default handler prevented
    // docEl.on('cut', me.removeSelectionAfterCut, me, {priority: 1001, delegated: false});


    // Paste does not reach the browser's clipboard-functionality,
    // so we need our own SnapshotHistory for handling CTRL+Z and CTRL+Y.
    // me.fireEvent('activateSnapshotHistory');

        if (me.editorTooltip) {
            me.editorTooltip.setTarget(document.body);
        } else {
            me.editorTooltip = Ext.create('Editor.view.ToolTip', {
                target: document,
            });
        }
    },

    onOpenEditorViewport: function(app, task) {
        if(! task.isUnconfirmed()) {
            return;
        }
        this.taskConfirmation = Ext.widget('taskConfirmationWindow').show();
    },
    /**
     * Cleanup stuff in the editor view port
     */
    onCloseEditorViewport: function() {
        var me = this;
        // TODO: disabled by Leon with the new editor
        //me.clearKeyMaps();
        // removing the following handler has no effect, but it should be removed here!
        //FIXME should be unbound since rebind on each task open!? or not?
        //Ext.getDoc().un('copy', me.copySelectionWithInternalTags);
        // TODO: disabled by Leon with the new editor
        // me.tooltip && me.tooltip.destroy();
        me.taskConfirmation && me.taskConfirmation.destroy();
    },
    // clearKeyMaps: function() {
    //     var me = this;
    //     if(me.editorKeyMap) {
    //         //FIXME: Fix for the bug in internet explorer
    //         //http://jira.translate5.net/browse/TRANSLATE-1086
    //     	//same problem with different error log under edge
    //     	//https://jira.translate5.net/browse/TRANSLATE-2037
    //         if(!Ext.isIE && !Ext.isEdge){
    //             me.editorKeyMap.destroy();
    //         }
    //         me.editorKeyMap = null;
    //     }
    //
    //     if(me.generalKeyMap) {
    //         //FIXME: Fix for the bug in internet explorer
    //         //http://jira.translate5.net/browse/TRANSLATE-1086
    //     	//same problem with different error log under edge
    //     	//https://jira.translate5.net/browse/TRANSLATE-2037
    //         if(!Ext.isIE && !Ext.isEdge){
    //             me.generalKeyMap.destroy();
    //         }
    //         me.generalKeyMap = null;
    //     }
    // },
    buttonClickDispatcher: function(btn, e) {
         var me = this,
             action = btn.itemId && btn.itemId.replace(/Btn$/, '');
         if(action && Ext.isFunction(me[action])) {
             me[action](btn, e);
         }
    },

    /**
     * Handler for save Button
     */
    save: function () {
        var me = this,
            ed = me.getEditPlugin(),
            rec = ed.editing && ed.context.record;

        //since save without moving was triggered, we have to reset the calculated data
        me.prevNextSegment.reset();

        // me.fireEvent('saveUnsavedComments');
        if (me.isEditing && rec && rec.get('editable')) {
            // me.fireEvent('prepareTrackChangesForSaving');
            me.fireEvent('saveSegment');
        }
    },

    // /**
    //  * Handler for CTRL+X
    //  */
    // undo: function() {
    //     this.fireEvent('undo'); // see SnapshotHistory
    // },
    // /**
    //  * Handler for CTRL+Y
    //  */
    // redo: function() {
    //     this.fireEvent('redo'); // see SnapshotHistory
    // },
    //
    /**
     * Handling CTRL-L
     */
    toggleSegmentLock: function() {
        var segments = Editor.app.getController('Segments');
        segments && segments.onToggleLockBtn();
    },

    /**
     * Focus the segment given in the prompt window input
     * Handling CTRL-G
     */
    focusSegmentShortcut:function (key, ev){
        if(this.isEditing) {
            this.scrollToSegment(key, ev);
            return;
        }

        let me = this,
            grid = me.getSegmentGrid(),
            nr = grid.selection && grid.selection.get('segmentNrInTask'),
            prompt = Ext.Msg.prompt('Go to segment', 'No.:', function(btn, text){
            if (btn === 'ok'){
                me.getSegmentGrid().focusSegment(text);
            }
        }, me, false, nr);
        prompt.down('textfield').selectOnFocus = true;
        prompt.down('textfield').focus(200);
    },

    handleAfterContentChange: function(preventSaveSnapshot) {
        // if(!preventSaveSnapshot) {
        //     this.fireEvent('saveSnapshot'); // see SnapshotHistory
        // }
        // trigger deferred change handler

        if (!this.isCapturingChange) {
            const me = this;
            this.isCapturingChange = true;

            setTimeout(function () {
                me.handleDelayedChange();
            }, Editor.data.editor.deferredChangeTimeout);
        }
    },

    // /**
    //  * handleAfterCursorMove: save new position of cursor if necessary.
    //  */
    // handleAfterCursorMove: function() {
    // 	this.fireEvent('updateSnapshotBookmark'); // see SnapshotHistory
    // },

    /**
     * handleAfterCursorMove: fire deferred change event if still changing
     */
    handleDelayedChange: function () {
        if (this.isEditing) {
            this.fireEvent('clockedchange', this.htmlEditor, this.getEditPlugin().context);
        }

        this.isCapturingChange = false;
    },

    // /**
    //  * After keyboard-event: handle changes if event is not to be ignored.
    //  * ('change'-event from segmentsHtmleditor does not work; is not really envoked when we need it!)
    //  * @param event
    //  */
    // handleKeyUp: function(event) {
    //     var me = this;
    //     me.consoleLog('Editor: handleKeyUp');
    //     me.event = event; // Editor.util.Event
    //     // New content?
    //     // Ignore
    //     // - keys that don't produce content (strg,alt,shift itself, arrows etc)
    //     // - keys that must not change the content in the Editor (e.g. strg-z will not always do what the user expects)
    //     if (!me.eventIsCtrlZ() && !me.eventIsCtrlY() && !me.eventHasToBeIgnored() && !me.eventHasToBeIgnoredAndStopped()) {
    //     	me.handleAfterContentChange();
    //     	return;
    //     }
    //     // New position of cursor?
    //     if (me.eventIsArrowKey()) {
    //     	me.handleAfterCursorMove();
    //     }
    // },
    // /**
    //  * After mouse-click.
    //  */
    // handleMouseUp: function() {
    //     var me = this;
    //     me.consoleLog('Editor: handleMouseUp');
    //     me.handleAfterCursorMove();
    // },
    // /**
    //  *
    //  */
    // handleDragEnd: function() {
    //     var me = this;
    //     me.consoleLog('Editor: handleDragEnd');
    //     me.fireEvent('afterDragEnd');
    // },

    /**
     * Special Universal preparation Handler for pressing DIGIT keys
     * A preparation keyboard shortcut can be defined, for example ALT-S.
     * If ALT-S is pressed, then if the next key is a DIGIT the given
     * digithandler will be called with the preseed DIGIT.
     * @param {Function} must be function in the controller scope, since scope parameter is not supported
     */
    handleDigitPreparation: function (digithandler) {
        return (key, event) => {
            this.digitHandler = digithandler;
            event.isDigitPreparation = true;
            event.stopEvent();

            return false;
        };
    },

    /**
     * Digit handler, does only something if a DIGIT preparation shortcut was pressed directly before.
     */
    handleDigit: function (k, e) {
        if (e.lastWasDigitPreparation) {
            e.stopEvent();
            this.digitHandler(k, e);

            return false;
        }
    },

    /**
     * Keyboard handler for zoom in, calls just the viewmodes function directly
     */
    handleZoomIn: function (k, e) {
        this.getSegmentGrid().setSegmentSize(1, true);
    },

    /**
     * Keyboard handler for zoom out, calls just the viewmodes function directly
     */
    handleZoomOut: function () {
        this.getSegmentGrid().setSegmentSize(-1, true);
    },

    /**
     * Moves to the next row without saving current record
     * @return {Boolean} true if there is a next segment, false otherwise
     */
    goToLowerNoSave: function() {
        var me = this;
        me.calcNext();
        me.moveToAdjacentRow();
    },
    /**
     * Moves to the next row without saving current record
     * @return {Boolean} true if there is a next segment, false otherwise
     */
    goToUpperNoSave: function() {
        var me = this;
        me.calcPrev();
        me.moveToAdjacentRow();
    },
    /**
     * Moves to the next row with the same workflow value without saving current record
     * @return {Boolean} true if there is a next segment, false otherwise
     */
    goToLowerByWorkflowNoSave: function(key, e) {
        var me = this;
        e.preventDefault();
        e.stopEvent();
        me.calcNext(true);
        me.moveToAdjacentRow();
    },
    /**
     * Moves to the previous row with the same workflow value without saving current record
     * @return {Boolean} true if there is a next segment, false otherwise
     */
    goToUpperByWorkflowNoSave: function(key, e) {
        var me = this;
        e.preventDefault();
        e.stopEvent();
        me.calcPrev(true);
        me.moveToAdjacentRow();
    },
    /**
     * Moves to the next or previous row without saving current record
     * @param {Object} rowMeta meta information of the next/prev segment to be opened
     * @return {Boolean} true if there is a next segment, false otherwise
     */
    moveToAdjacentRow: function() {
        var me = this;

        if(!me.isEditing) {
            return;
        }

        me.cancel();
        me.openNextRow();
    },
    /**
     * triggers the calculation of the prev segment with the appropriate configs/msg
     */
    calcPrev: function(inWorkflow){
        var type = inWorkflow ? 'workflow' : 'editable',
            store = this.getSegmentGrid().getStore(),
            filtersAreUsed = store.getFilters().getCount() || store.getProxy().getExtraParams().qualities,
            msg = filtersAreUsed
                ? this.messages.gridStartReachedFiltered
                : (inWorkflow
                    ? this.messages.gridStartReachedWorkflow
                    : this.messages.gridStartReached);
        this.prevNextSegment.calcPrev(type, msg);
    },
    /**
     * triggers the calculation of the next segment with the appropriate configs/msg
     */
    calcNext: function(inWorkflow){
        var type = inWorkflow ? 'workflow' : 'editable',
            store = this.getSegmentGrid().getStore(),
            filtersAreUsed = store.getFilters().getCount() || store.getProxy().getExtraParams().qualities,
            msg = filtersAreUsed
                ? this.messages.gridEndReachedFiltered
                : (inWorkflow
                    ? this.messages.gridEndReachedWorkflow
                    : this.messages.gridEndReached);

        this.prevNextSegment.calcNext(type, msg);
    },
    /**
     * Handler for saveNext Button
     * @return {Boolean} true if there is a next segment, false otherwise
     */
    saveNext: function() {
        this.calcNext();
        this.saveOtherRow();
    },
    /**
     * Handler for savePrevious Button
     * @return {Boolean} true if there is a next segment, false otherwise
     */
    savePrevious: function() {
        this.calcPrev();
        this.saveOtherRow();
    },
    /**
     * Handler for saveNext Button
     * @return {Boolean} true if there is a next segment, false otherwise
     */
    saveNextByWorkflow: function() {
        this.calcNext(true);
        this.saveOtherRow();
    },
    // /**
    //  * Handler for savePrevious Button
    //  * @return {Boolean} true if there is a next segment, false otherwise
    //  */
    // savePreviousByWorkflow: function() {
    //     this.calcPrev(true);
    //     this.saveOtherRow();
    // },

    /**
     * API for plugins to save the current and open the next segment followed by the passed callback
     * @param {String} type
     * @param {String} msg
     * @param {function} callback
     */
    saveCurrentAndOpenNext: function(type, msg, callback){
        this.nextOpenedCallback = callback;
        this.prevNextSegment.calcNext(type, msg);
        this.saveOtherRow();
    },
    /**
     * save and go to other row
     */
    saveOtherRow: function () {
        this.fireEvent('saveUnsavedComments');

        if (!this.isEditing) {
            return;
        }

        this.fireEvent('saveSegment', {
            scope: this,
            segmentUsageFinished: () => {
                this.openNextRow();
            }
        });
    },
    /**
     * Opens a next row, if any
     */
    openNextRow: function() {
        var me = this,
            grid = me.getSegmentGrid(),
            selModel = grid.getSelectionModel(),
            ed = me.getEditPlugin(),
            rowMeta = me.prevNextSegment.getCalculated(),
            callback,
            sel,
            scrollMode = ed.self.STARTEDIT_MOVEEDITOR;

        // do not try to open the editor if there is nothing to be opened.
        // this can happen in some rare cases when repetitions are saved via repetition editor
        if(!rowMeta){
            return;
        }

        //if the editor should be scrolled or moved
        if(!rowMeta.isMoveEditor){
        	scrollMode = ed.self.STARTEDIT_SCROLLUNDER;
        }

        //if we have a nextSegment and it is rendered, bring into the view and open it
        if (rowMeta.rec && grid.getView().getNode(rowMeta.rec)) {
            selModel.select(rowMeta.rec);
            //REMIND here was startEdit defered with 300 millis, is this still needed?
            ed.startEdit(rowMeta.rec, rowMeta.lastColumn,scrollMode);
            me.nextRowOpened();
            return;
        }

        if(Ext.isDefined(rowMeta.idx)) {
            //if we only have a rowIndex or it is not rendered, we have to scroll first
            callback = function() {
                grid.selectOrFocus(rowMeta.idx);
                sel = selModel.getSelection();
                ed.startEdit(sel[0], rowMeta.lastColumn,scrollMode);
                me.nextRowOpened();
            };
            grid.scrollTo(rowMeta.idx, {
                callback: callback,
                notScrollCallback: callback
            });
            return;
        }

        if(rowMeta.isBorderReached) {
            Editor.MessageBox.addInfo(rowMeta.errorText);
            return;
        }

        if(rowMeta.isLoading) {
            Editor.MessageBox.addInfo(me.messages.segmentNotBuffered);
        }
        else {
            Editor.MessageBox.addInfo(me.messages.segmentsChanged);
        }
    },

    /**
     * Applies any callbacks after opening a row (can currently only be one)
     */
    nextRowOpened: function(){
        if(this.nextOpenedCallback){
            this.nextOpenedCallback();
        }
        this.nextOpenedCallback = null;
    },

    /**
     * @param {Editor.view.segments.HtmlEditor} editor
     * @param {String} msg
     * @param {Boolean} isTagError (optional; default: true)
     */
    onSaveWithErrors: function (editor, msg, isTagError = true) {
        let me = this,
            msgBox;

        //if there was an empty message we assume that there was no error,
        if (!msg) {
            return;
        }

        msgBox = Ext.create('Ext.window.MessageBox', {
            buttonText: {
                ok: 'OK',
                yes: 'OK',
                no: me.messages.saveAnyway
            }
        });

        // tag-errors: check if user is allowed to save anyway
        if (isTagError && Editor.app.getTaskConfig('segments.userCanIgnoreTagValidation')) {
            msgBox.confirm(
                me.messages.errorTitle, msg, function (btn) {
                    if (btn === 'no') {
                        me.saveAndIgnoreContentErrors();
                    }
                },
                me
            );
        } else {
            msgBox.alert(me.messages.errorTitle, msg);
        }
    },

    /**
     * Triggers the save chain but ignoring htmleditor content errors then
     */
    saveAndIgnoreContentErrors: function () {
        this.getEditPlugin().editor.disableContentErrorCheckOnce();

        if (this.prevNextSegment.getCalculated()) {
            this.saveOtherRow.apply(this);

            return;
        }

        this.save();
    },

    /**
     * Handler für cancel Button
     */
    cancel: function () {
        this.getEditPlugin().cancelEdit();
    },

    /**
     * Handles pressing the keyboard shortcuts for changing the segment state
     */
    handleChangeState: function (key, e) {
        var param = Number(key) - 48;

        // we ignore 0, since this is no valid state
        if (param !== 0) {
            this.fireEvent('changeSegmentState', param);
            e.stopEvent();
        }

        return false;
    },

    /**
     * Handles pressing the comment keyboard shortcut
     */
    handleOpenComments: function() {
        Ext.fireEvent('editorOpenComments');
    },

    /**
     * Handles pressing the MQM tag shortcuts, without shift 1-10, with shift 11-20
     */
    handleAssignMQMTag: function(key, e) {
        if(!this.isEditing) {
            return;
        }

        e.preventDefault();
        e.stopEvent();

        let param = Number(key) - 48;

        if (param === 0) {
            param = 10;
        }

        if(e.shiftKey) {
            param = param + 10;
        }

        this.fireEvent('assignMQMTag', param);
    },

    // /**
    //  * Move the editor about one editable field
    //  */
    // goToCustom: function(direction, saveRecord) {
    //     var me = this,
    //         info = me.getColInfo(),
    //         idx = info && info.foundIdx,
    //         cols = info && info.columns,
    //         store = me.getSegmentGrid().store,
    //         plug = me.getEditPlugin(),
    //         newRec;
    //
    //     if(info === false) {
    //     return;
    //     }
    //     newRec = store.getAt(store.indexOf(plug.context.record) + direction);
    //
    //     //check if there exists a next/prev row, if not we dont need to move the editor.
    //     while(newRec && !newRec.get('editable')) {
    //         newRec = store.getAt(store.indexOf(newRec) + direction);
    //     }
    //     if(cols[idx + direction]) {
    //     plug.editor.changeColumnToEdit(cols[idx + direction]);
    //     return;
    //     }
    //     if(direction > 0) {
    //         //goto next segment and first col
    //         if(newRec) {
    //             plug.editor.changeColumnToEdit(cols[0]);
    //         }
    //         if (saveRecord) {
    //         me.saveNext();
    //         }
    //         else {
    //         me.goToLowerNoSave();
    //         }
    //         return;
    //     }
    //     //goto prev segment and last col
    //     if(newRec) {
    //         plug.editor.changeColumnToEdit(cols[cols.length - 1]);
    //     }
    //     if (saveRecord) {
    //     me.savePrevious();
    //     }
    //     else {
    //     me.goToUpperNoSave();
    //     }
    // },
    // /**
    //  * Move the editor about one editable field
    //  */
    // goAlternateRight: function() {
    //     this.goToCustom(1, true);
    // },
    // /**
    //  * Move the editor about one editable field
    //  */
    // goAlternateLeft: function() {
    //     this.goToCustom(-1, true);
    // },
    // /**
    //  * Move the editor about one editable field left
    //  */
    // goToLeft: function(key, e) {
    //     var me = this,
    //         direction = -1;
    //     if(!me.isEditing) {
    //         return;
    //     }
    //     e.preventDefault();
    //     e.stopEvent();
    //     me.goToCustom(direction, true);
    // },
    // /**
    //  * Move the editor about one editable field right
    //  */
    // goToRight: function(key, e) {
    //     var me = this,
    //         direction = 1;
    //     if(!me.isEditing) {
    //         return;
    //     }
    //     e.preventDefault();
    //     e.stopEvent();
    //     me.goToCustom(direction, true);
    // },
    // /**
    //  * returns the visible columns and which column has actually the editor
    //  * @return {Object}
    //  */
    // getColInfo: function() {
    //     var me = this,
    //         plug = me.getEditPlugin(),
    //         columns = me.getSegmentGrid().query('contentEditableColumn:not([hidden])'),
    //         foundIdx = false,
    //         current = plug.editor.getEditedField();
    //
    //     if(!plug || !plug.editor || !plug.editing) {
    //         return false;
    //     }
    //
    //     Ext.Array.each(columns, function(col, idx) {
    //     if(col.dataIndex === current) {
    //         foundIdx = idx;
    //     }
    //     });
    //     if(foundIdx === false) {
    //     return false;
    //     }
    //
    //     return {
    //     plug: plug,
    //     columns: columns,
    //     foundIdx: foundIdx
    //     };
    // },
    //
    /**
     * brings the currently opened segment back into the view.
     */
    scrollToSegment: function(key, e) {
        var me = this,
            plug = me.getEditPlugin();
        e.preventDefault();
        e.stopEvent();
        if(!this.isEditing) {
            return;
        }
        if(plug.editor.context.reordered) {
            Editor.MessageBox.addInfo(me.messages.segmentsChangedJump);
            return;
        }
        plug.editor.setMode(plug.self.STARTEDIT_SCROLLUNDER);
        plug.editor.initialPositioning();
    },

    /**
     * resets the htmleditor content to the original content
     */
    resetSegment: function() {
        if(!this.isEditing) {
            return;
        }
        var me = this,
            plug = me.getEditPlugin(),
            editor = plug.editor,
            rec = plug.context.record,
            columnToRead = editor.columnToEdit.replace(/Edit$/, '');
        Editor.MessageBox.addInfo(me.messages.segmentReset);
        me.setValueForEditor(rec.get(columnToRead));
        // me.fireEvent('prepareCompleteReplace',rec.get(columnToRead),true); // if TrackChanges are activated, DEL- and INS-markups are added first and then setValueForEditor is applied from there (= again, but so what)
        editor.mainEditor.setValue(me.resetSegmentValueForEditor, rec, editor.columnToEdit);
    },
    setValueForEditor: function(value) {
        this.resetSegmentValueForEditor = value;
    },
    // /**
    //  * handler for the F2 key
    //  */
    // handleF2KeyPress: function() {
    //     var me = this,
    //         grid = me.getSegmentGrid(),
    //         selModel = grid.getSelectionModel(),
    //         ed = me.getEditPlugin(),
    //         cols = grid.query('contentEditableColumn:not([hidden])'),
    //         sel = [],
    //         firstEditableRow = grid.store.getFirsteditableRow(),
    //         callback;
    //
    //     if(Ext.isEmpty(firstEditableRow)) {
    //         return;
    //     }
    //
    //     if (ed.editing) {
    //         ed.editor.mainEditor.deferFocus();
    //         return;
    //     }
    //
    //     if (selModel.hasSelection()){
    //         //with selection scroll the selection into the viewport and open it afterwards
    //         sel = selModel.getSelection();
    //         grid.scrollTo(grid.store.indexOf(sel[0]),{
    //             callback: function() {
    //                 ed.startEdit(sel[0], cols[0]);
    //             }
    //         });
    //     } else {
    //         //with no selection, scroll to the first editable select, select it, then open it
    //         callback = function() {
    //             grid.selectOrFocus(firstEditableRow);
    //             sel = selModel.getSelection();
    //             var editStarted = ed.startEdit(sel[0], cols[0]);
    //             if(editStarted) {
    //             Editor.MessageBox.addInfo(me.messages.f2FirstOpened);
    //             }
    //         };
    //         grid.scrollTo(firstEditableRow, {
    //             callback: callback,
    //             notScrollCallback: callback
    //         });
    //     }
    // },

    /***
     * F3 editor event handler.
     * This will set the focus in the targetSearch field of concordance search panel
     */
    handleF3KeyPress: function (keyCode, event) {
        const searchGrid = this.getLanguageResourceSearchGrid();
        this.openEditorPanel(searchGrid);

        let fieldType;

        if (event.getTarget('.type-source') || (this.htmlEditor && this.htmlEditor.getEditedField() === 'sourceEdit')) {
            fieldType = 'source';
        } else if (event.getTarget('.type-target') || (this.htmlEditor && this.htmlEditor.getEditedField() === 'targetEdit')) {
            fieldType = 'target';
        } else {
            return;
        }

        const selectedText = this.getSelectedText();
        const searchField = searchGrid.down('#' + fieldType + 'Search');

        if (selectedText === '') {
            searchField.focus(false, 500);

            return;
        }

        searchField.setValue(selectedText);
        searchGrid.getController().setLastActiveField(searchField);
        searchGrid.getController().handleSearchAll();
    },

    /**
     * Event handler for alt+f3 shortcut.
     * This will trigger synonym search with the selected text editor
     */
    handleAltF3KeyPress: function () {
        const searchGrid = this.getSynonymSearch();
        this.openEditorPanel(searchGrid);

        const selectedText = this.getSelectedText();
        const searchField = searchGrid.down('#textSearch');

        if (selectedText === '') {
            searchField.focus(false, 500);

            return;
        }

        searchField.setValue(selectedText);
        searchGrid.getController().search();
    },

    openEditorPanel: function (activeTab) {
        const editorPanel = this.getLanguageResourceEditorPanel();

        if (!editorPanel) {
            return;
        }

        // expand if collapsed
        if (editorPanel.getCollapsed()) {
            editorPanel.expand();
        }

        editorPanel.setActiveTab(activeTab);
    },

    //
    // removeSelectionAfterCut: function(e) {
    //     if(!e.defaultPrevented || !e.stopped) {
    //         return;
    //     }
    //     var activeElement = Ext.fly(Ext.Element.getActiveElement()),
    //         sel;
    //
    //     //currently removing the content after cut is only useful in the htmleditor, nowhere else
    //     if(activeElement.is('iframe.x-htmleditor-iframe')) {
    //         sel = rangy.getSelection(this.getEditPlugin().editor.mainEditor.getEditorBody());
    //         if(sel.rangeCount) {
    //             sel.getRangeAt(0).deleteContents();
    //             sel.getRangeAt(0).collapse();
    //             sel.getRangeAt(0).select();
    //         }
    //     }
    // },

    onDragStart: function (event) {
        const plugin = this.getEditPlugin();

        //do only something when editing targets:
        if (!this.isEditing || !/^target/.test(plugin.editor.columnToEdit)) {
            return;
        }

        let activeElement = Ext.fly(Ext.Element.getActiveElement());
        const isTagColumn = activeElement.hasCls('segment-tag-column');
        const isTagContainer = activeElement.hasCls('segment-tag-container');

        // if the focus is not in an element that can use internal tags, we have nothing to do.
        if (!isTagContainer && !isTagColumn) {
            return;
        }

        const selection = rangy.getSelection();
        let selectedRange = selection.rangeCount ? selection.getRangeAt(0) : null;

        if (selectedRange === null || selectedRange.collapsed) {
            return;
        }

        // selections that need extra handling:
        if (isTagColumn || isTagContainer || activeElement.hasCls('type-source')) {
            if (isTagColumn && activeElement.down('div.x-grid-cell-inner')) {
                activeElement = activeElement.down('div.x-grid-cell-inner');
            }

            // whole source segment selected? Then select the content within only.
            // (= without the surrounding "<div (...) class="segment-tag-container (...) type-source">(...)</div>"
            const position = this.getPositionInfoForRange(selectedRange, activeElement.dom);

            if (position.atStart && position.atEnd) {
                selection.selectAllChildren(activeElement.dom);
            }
        }

        let content = '';

        // Firefox uses multiple selections.
        // For example: 'abc<del>def</del>ghi'
        // - Firefox: first range: 'abc', second range: 'ghi'
        // - Chrome: one single range with 'abc<del>def</del>ghi'
        for (let i = 0; i < selection.rangeCount; i++) {
            selectedRange = selection.getRangeAt(i);
            selectedRange = this.getRangeWithFullInternalTags(selectedRange);
            content += selectedRange.toHtml();
        }

        content = content.replace(/id="ext-element-[0-9]+"/, '');
        let textContent = content;

        const selectedInternalTags = selectedRange.getNodes([1], (node) => node.classList.contains('internal-tag'));
        for (const internalTag of selectedInternalTags) {
            textContent = textContent.replace(internalTag.outerHTML, '');
        }

        event.event.dataTransfer.clearData();
        event.event.dataTransfer.setData('text/plain', textContent);
        event.event.dataTransfer.setData('text/html', content);
    },

    copySelectionWithInternalTags: function (event) {
        // The user will expect the copied text to be available in the clipboard,
        // so we do NOT stop the propagation of the event.
        if (!this.editorKeyMap) {
            //if we are not in a task, we may not invoke. Easiest way: check for editorKeyMap
            return;
        }

        // CTRL+C gets the selected text (including internal tags)
        const plugin = this.getEditPlugin();

        //do only something when editing targets:
        if (!this.isEditing || !/^target/.test(plugin.editor.columnToEdit)) {
            return;
        }

        let activeElement = Ext.fly(Ext.Element.getActiveElement());
        const isTagColumn = activeElement.hasCls('segment-tag-column');
        const isTagContainer = activeElement.hasCls('segment-tag-container');

        // if the focus is not in an element that can use internal tags, we have nothing to do.
        if (!isTagContainer && !isTagColumn) {
            return;
        }

        // Where does the copied text come from? If it's from a segment's source or the Editor itself,
        // we store the id from the segment, because in this case it will be allowed to paste the internal tags
        // into the target (but only if the target belongs to the same segment as the source).
        const copy = {
            selDataHtml: '', // = selected content WITH internal tags
            selDataText: '', // = selected content WITHOUT internal tags
            selSegmentId: '',
            format: ''
        };

        const selection = rangy.getSelection();
        let selectedRange = selection.rangeCount ? selection.getRangeAt(0) : null;

        if (selectedRange === null || selectedRange.collapsed) {
            return;
        }

        // selections that need extra handling:
        if (isTagColumn || isTagContainer || activeElement.hasCls('type-source')) {
            if (isTagColumn && activeElement.down('div.x-grid-cell-inner')) {
                activeElement = activeElement.down('div.x-grid-cell-inner');
            }

            // whole source segment selected? Then select the content within only.
            // (= without the surrounding "<div (...) class="segment-tag-container (...) type-source">(...)</div>"
            const position = this.getPositionInfoForRange(selectedRange, activeElement.dom);

            if (position.atStart && position.atEnd) {
                selection.selectAllChildren(activeElement.dom);
            }

            copy.selSegmentId = plugin.context.record.get('id');
            copy.format = 'div';
        }

        // Firefox uses multiple selections.
        // For example: 'abc<del>def</del>ghi'
        // - Firefox: first range: 'abc', second range: 'ghi'
        // - Chrome: one single range with 'abc<del>def</del>ghi'
        for (let i = 0; i < selection.rangeCount; i++) {
            selectedRange = selection.getRangeAt(i);
            selectedRange = this.getRangeWithFullInternalTags(selectedRange);
            copy.selDataHtml += selectedRange.toHtml();
        }

        // preset text and html with the found ranges
        // for insert as html (must not include element-ids that already exist in Ext.cache!)
        copy.selDataText = copy.selDataHtml = copy.selDataHtml.replace(/id="ext-element-[0-9]+"/, '');

        // for insert as text only
        //the toString is working if copying img tags
        if (copy.format === 'div') {
            // for copying internal tags as divs we have to do the following:
            const selectedInternalTags = selectedRange.getNodes([1], (node) => node.classList.contains('internal-tag'));
            for (const internalTag of selectedInternalTags) {
                copy.selDataText = copy.selDataText.replace(internalTag.outerHTML, '');
            }
        } else {
            copy.selDataText = selectedRange.toString();
        }

        // if we are in a regular copy / cut event we set the clipboard content to our needs
        if (event && event.browserEvent) {
            event.browserEvent.clipboardData.setData('text/plain', copy.selDataText);
            event.browserEvent.clipboardData.setData('text/html', copy.selDataHtml);
            event.preventDefault();
            event.stopEvent();
        }
    },


    // /**
    //  * Pasting our own content must be handled special to insert correct tags
    //  */
    // pasteContent: function(e){
    //     e.stopPropagation();
    //     e.preventDefault();
    //     var me = this,
    //         plug = me.getEditPlugin(),
    //         record = plug.context ? plug.context.record : null;
    //
    //     // if the user is fast enough to close the window and after this use ctr + v to paste the content, the event
    //     // will be fired but the row editor will not exist anymore
    //     if(!record){
    //         return;
    //     }
    //
    //     var segmentId = record.get('id'),
    //         internalClip = me.copiedSelectionWithTagHandling || {},
    //         clipboard = (e.browserEvent.clipboardData || window.clipboardData),
    //         clipboardText = clipboard.getData('Text'),
    //         clipboardHtml = clipboard.getData('text/html'),
    //         toInsert, sel,
    //         textMatch = clipboardText == internalClip.selDataText,
    //         //the clipboardHtml adds meta information like charset and so on, so we just check if
    //         // the stored one is a substring of the one in the clipboard
    //         htmlMatch = clipboardHtml.includes(internalClip.selDataHtml);
    //
    //     //remove selected content before pasting the new content
    //     sel = rangy.getSelection(this.getEditPlugin().editor.mainEditor.getEditorBody());
    //     if(sel.rangeCount) {
    //         sel.getRangeAt(0).deleteContents();
    //         sel.getRangeAt(0).collapse();
    //         sel.getRangeAt(0).select();
    //     }
    //
    //     //when making a copy in translate5, we store the content in an internal variable and in the clipboard
    //     //if neither the text or html clipboard content matches the internally stored content,
    //     // that means that the pasted content comes from outside and we insert just text:
    //     if(me.copiedSelectionWithTagHandling === null || !textMatch || !htmlMatch) {
    //         me.htmlEditor.insertMarkup(Ext.String.htmlEncode(clipboardText));
    //         me.handleAfterContentChange(true); //prevent saving snapshot, since this is done in insertMarkup
    //         me.copiedSelectionWithTagHandling = null;
    //         return;
    //     }
    //     /*
    //     console.log("text", clipboardText);
    //     console.log("html", clipboardHtml);
    //     console.log("data", internalClip);
    //     */
    //     //to insert tags, the copy/cut from segment must be the same as the paste to segment, so that tags are not moved between segments
    //     if(segmentId === internalClip.selSegmentId) {
    //         toInsert = internalClip.selDataHtml;
    //     }
    //     else {
    //         toInsert = internalClip.selDataText;
    //     }
    //
    //     // we always use insertMarkup, regardless if it is img or div content
    //     me.htmlEditor.insertMarkup(toInsert);
    //     me.handleAfterContentChange(true); //prevent saving snapshot, since this is done in insertMarkup
    // },

    /**
     * Event handler for text selection change in editor
     */
    onEditorSelectionChange: function (event) {
        const selectedText = event.detail.selection.toString(),
            synonymGridExist = this.getSynonymSearch() !== undefined,
            editorPanelExist = this.getLanguageResourceEditorPanel() !== undefined;

        if (!synonymGridExist && !editorPanelExist) {
            return;
        }

        // for less than 4 characters do not show the message
        if (selectedText.length < 4) {
            return;
        }

        if (!this.quickSearchInfoMessage) {
            this.quickSearchInfoMessage = Ext.create('Editor.view.task.QuickSearchInfoMessage');
        }

        this.quickSearchInfoMessage.synonymGridExist = synonymGridExist;
        this.quickSearchInfoMessage.showMessage();
    },


    copyReferenceToTarget: function() {
        const plug = this.getEditPlugin();

        //do only something when editing targets:
        if(!this.isEditing || !/^target/.test(plug.editor.columnToEdit)){
            return;
        }

        const referenceField = plug.editor.mainEditor.getReferenceField(
            plug.context.record.get('target'),
            plug.context.record.get('pretrans'),
            plug.context.record.get('matchRateType'),
        );
        plug.editor.mainEditor.setValue(
            plug.context.record.get(referenceField),
            plug.context.record,
            plug.editor.columnToEdit,
            false,
            true
        );
    },

    copySourceToTarget: function() {
        const plug = this.getEditPlugin();

        //do only something when editing targets:
        if(!this.isEditing || !/^target/.test(plug.editor.columnToEdit)){
            return;
        }

        plug.editor.mainEditor.setValue(
            plug.context.record.get('source'),
            plug.context.record,
            plug.editor.columnToEdit,
            false,
            true
        );
    },

    insertWhitespaceNbsp: function(key,e) {
        this.insertWhitespace('nbsp');
    },
    insertWhitespaceNewline: function(button, event, position = null, replaceWhitespaceBeforePosition = false) {
        this.insertWhitespace('newline', position, replaceWhitespaceBeforePosition);
    },
    insertWhitespaceTab: function(button, event, position = null) {
        this.insertWhitespace('tab', position);
    },
    insertWhitespaceCombo: function(field, newVal, oldVal, eOpts) {
        this.insertWhitespace(newVal);
        field.up('#specialChars').hideMenu();
    },

    /**
     * Button handler for special characters buttons.
     * @param button
     * @param e
     */
    insertSpecialCharacter: function (button, e) {
        let plug = this.getEditPlugin(),
            editor = plug && plug.editor.mainEditor;

        if (!editor) {
            return;
        }

        editor.insertSymbol(button.value);

        e.stopEvent();
    },

    insertWhitespace: function(whitespaceType, position = null, replaceWhitespaceBeforePosition = false) {
        const userCanModifyWhitespaceTags = Editor.app.getTaskConfig('segments.userCanModifyWhitespaceTags'),
            userCanInsertWhitespaceTags = Editor.app.getTaskConfig('segments.userCanInsertWhitespaceTags');

        if (!userCanModifyWhitespaceTags || !userCanInsertWhitespaceTags) {
            return;
        }

        this.getEditPlugin().editor.mainEditor.insertWhitespace(whitespaceType, position, replaceWhitespaceBeforePosition);
    },

    handleInsertTagShift: function(key, e) {
        e.shiftKey = true;
        this.handleInsertTag(key, e);
    },

    handleInsertTag: function (key, e) {
            const editorWrapper = this.getEditPlugin().editor.mainEditor.editor,
            tagIdx = Number(e.browserEvent.key);

        if (e.shiftKey) {
            tagIdx = tagIdx + 10;
        }

        editorWrapper.insertTagFromReference(tagIdx);

        e.stopEvent();

        return false;
    },

    // /**
    //  * scrolls to the first segment.
    //  */
    // handleHomeKeyPress: function() {
    //     this.getSegmentGrid().scrollTo(0);
    // },

    /**
     * Handler for watchSegmentBtn
     */
    watchSegment: function() {
        let me = this,
            ed = me.getEditPlugin(),
            edited = ed.context?.record,
            selected = this.getSegmentGrid()?.getViewModel()?.get('selectedSegment');

        // If we're not editing segment, or we are, but grid selection moved to another segment
        if (!me.isEditing || (selected && selected.get('id') !== edited.get('id'))) {
            // Toggle bookmark for the segment which is currently selected and is not the one that is being edited
            return selected?.toggleBookmark();
        }

        // Toggle bookmark for the segment that is currently being edited
        edited.toggleBookmark(() => {
            let displayField = ed.editor.down('displayfield[name="autoStateId"]'),
                autoStateCell = ed.context && Ext.fly(ed.context.row).down('td.x-grid-cell-autoStateColumn div.x-grid-cell-inner');

            // Update autoState displayField, since the displayFields are getting the rendered content,
            // we have to fetch it here from rendered HTML too
            autoStateCell && displayField.setValue(autoStateCell.getHtml());
        });
    },

    //
    // /**
    //  * In textareas ExtJS 6.2 enter keys are not bubbling up, but they are triggering a specialkey event
    //  *  we listen to that event and process our own keys then.
    //  */
    // handleCommentEnter: function(field, e) {
    //     var key = e.getKey();
    //
    //     if (key === e.ENTER && e.hasModifier() && this.generalKeyMap) {
    //         this.generalKeyMap.handleTargetEvent(e);
    //     }
    // },

    handleReferenceFilesMessage:function(){
        //if there are reference files for the task and if it is show reference files is alowed from config
        if(Editor.data.task.get('referenceFiles') && Editor.app.getTaskConfig('editor.showReferenceFilesPopup')===true){
            var referenceInfoMessage = Ext.create('Editor.view.ReferenceFilesInfoMessage',{}),
            task = new Ext.util.DelayedTask(function(){
                referenceInfoMessage.destroy();
            });
            task.delay(20000);
            referenceInfoMessage.show();
        }
    },

    // /***
    //  * "Reference files info message" window button handler
    //  */
    // onShowReferenceFilesButtonClick:function(){
    //     var filePanel =this.getFilepanel().expand();
    //     var taskFiles = filePanel.down('taskfiles').expand();
    //     taskFiles.scrollable.scrollIntoView(taskFiles.down('referenceFileTree').view.el);
    // },

    /**
     * Confirm the current task
     */
    taskConfirm: function () {
        Editor.util.TaskActions.confirm(function(task, app, strings){
            Editor.MessageBox.addSuccess(strings.taskConfirmed);
        });
    },

    /**
     * Edit task and focus segment route
     * @param {String} taskId
     * @param {String} segmentNrInTask
     */
    onTaskSegmentEditRoute: function(taskId, segmentNrInTask) {
        let me = this,
            grid = me.getSegmentGrid(),
            dataTask = Editor.data.task;

        //prevent re-run of focusing if we have the segment already selected
        if(grid && grid.selection && grid.selection.get('segmentNrInTask') === parseInt(segmentNrInTask)) {
            return;
        }

        if(dataTask && dataTask.id === parseInt(taskId)) {
            grid && grid.focusSegment(segmentNrInTask);
            return;
        }
        if(dataTask && dataTask.isModel){ // task is active, switch task
            // QUIRK: Do not prevent task closing when task changes per route
            Editor.util.TaskActions.close(function(task, app, strings){
                me.openTask(taskId);
            });
        } else {
            me.openTask(taskId);
        }
    },

    /***
     * Open taks for editing for given taskid
     */
    openTask: function(taskId){
        //if the task is loaded, do nothing
        if(Editor.data.task && Editor.data.task.id == taskId){
            return;
        }
        Editor.model.admin.Task.load(taskId, {
            success: function(task) {
                Editor.util.TaskActions.openTask(task);
            },
            failure: function(record, op, success) {
                Editor.app.getController('ServerException').handleException(op.error.response);
            }
        });
    },

    openTaskRequest: function(taskId,callback){
        //if the task is loaded, do nothing
        Editor.model.admin.Task.load(taskId, {
            success: function(task) {
                Editor.util.TaskActions.openTaskRequest(task);
                callback(true,task);
            },
            failure: function(record, op, success) {
                Editor.app.getController('ServerException').handleException(op.error.response);
                callback(false,null);
            }
        });
    },

    /***
     * When segment grid selection changes, update the hash
     */
    onSegmentGridSelect: function(grid, record, index, event){
        this.redirectTo('task/'+Editor.data.task.get('id')+'/'+record.get('segmentNrInTask')+'/edit');
    },

    /***
     * Segments store load event handler
     */
    onSegmentsStoreLoad: function (store) {
        let segmentsGrid = this.getSegmentGrid();

        if(!segmentsGrid){
            return;
        }

        //check the content editable column visibility
        this.handleNotEditableContentColumn();

        // if already selected from other load listener or nothing selectable, return
        if (!store.getCount() || segmentsGrid.selection) {
            return;
        }

        let jumpToSegmentIndex =
            Editor.app.parseSegmentIdFromTaskEditHash(true)
            || (store.proxy.reader.metaData && store.proxy.reader.metaData.jumpToSegmentIndex)
            || 1;

        segmentsGrid.focusSegment(jumpToSegmentIndex);
    },

    // /**
    //  * Segments grid segment size event handler
    //  *
    //  * @param {Ext.Component} grid
    //  * @param {String} newSize
    //  * @param {String} oldSize
    //  */
    // onSegmentGridSegmentsSizeChanged: function(grid, newSize, oldSize){
    //     var me=this,
    //         htmlEditor = me.getSegmentsHtmleditor();
    //     if(!htmlEditor){
    //         return;
    //     }
    //     htmlEditor.setSegmentSize(grid,newSize,oldSize);
    // },

    /***
     * Make sure that there is an editable content column when user try to edit segment when the task is in edit mode
     */
    handleNotEditableContentColumn: function () {
        var me = this,
            isReadOnly = me.getSegmentGrid().lookupViewModel().get('taskIsReadonly');

        if (isReadOnly) {
            return;
        }

        let hiddenEditable = Ext.ComponentQuery.query('contentEditableColumn[hidden="true"]'),
            allEditable = Ext.ComponentQuery.query('contentEditableColumn');

        if (hiddenEditable.length === allEditable.length) {
            //no visible content editable column found. Show info message and display all hidden content editable columns
            Editor.MessageBox.addInfo(this.messages.noVisibleContentColumn);
            for (var i = 0; i < hiddenEditable.length; i++) {
                hiddenEditable[i].setVisible(true);
            }
        }
    },

    /**
     * Adds types to the prev-next controller
     */
    addPrevNextSegmentType: function(type, parser, additionalParams){
        if(this.prevNextSegment){
            this.prevNextSegment.addType(type, parser, additionalParams);
        }
    },

    /***
     * Return the current selected text in editor without tags.
     *
     * @returns {string|*}
     */
    getSelectedTextInEditor: function (){
        var grid = Ext.getCmp('segment-grid'),
            isEditingSegment = grid.getViewModel().get('isEditingSegment'),
            selection = window.getSelection(),
            selectedText = selection.toString(),
            selectedElem = selection.focusNode ? Ext.get(selection.focusNode.parentNode) : null;

        // If segment is opened for editing and selection changed within roweditor
        if (isEditingSegment
            && selectedElem
            && selectedElem.up('.x-grid-row-editor')) {

            // If selection changed within roweditor's source-column or within target-column
            if (selectedElem.hasCls('type-source') || selectedElem.up('.ck-content')) {
                return selectedText;
            }
        }

        return '';
    },

    /**
     * Return the current selected text in editor without tags.
     *
     * @returns {string|*}
     */
    getSelectedText: function (){
        const grid = Ext.getCmp('segment-grid'),
            selection = window.getSelection();

        if (! selection.isCollapsed && selection.rangeCount > 0) {
            return selection.toString();
        }

        return '';
    },

    //
    // /**
    //  * Distinguish between menu item itself click and menu item checkbox click
    //  *
    //  * @param item
    //  * @param event
    //  */
    onSegmentActionMenuItemClick: function(item, event) {
         if (event.getTarget('.x-menu-item-checkbox')) {
             item.allowCheckChange = true;
             item.setChecked(!item.checked);
             item.allowCheckChange = false;
         } else {
             var button = this.getSegmentGrid().down('segmentsToolbar #' + item.itemId);
             if (button.dispatcher) {
                 this.buttonClickDispatcher(item);
             } else {
                 button.click();
             }
         }
    },

    /**
     * Toggle corresponding segment action button in the toolbar
     *
     * @param item
     */
    onSegmentActionMenuItemToggle: function(item) {

        // Update comma-separated itemIds of checked items within menu's stateful checkedItems-prop
        item.up().checkedItems = Ext.Array.pluck(item.up().query('[checked]'), 'itemId').join(',');

        // Save state
        item.up().saveState();

        // Toggle button visibility
        item.up('toolbar').down('#' + item.itemId).setVisible(item.checked);
    }
});
