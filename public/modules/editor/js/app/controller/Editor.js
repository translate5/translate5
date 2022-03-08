
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
    extend : 'Ext.app.Controller',
    requires: [
        'Editor.view.segments.EditorKeyMap',
        'Editor.controller.editor.PrevNextSegment',
        'Editor.view.task.ConfirmationWindow',
        'Editor.view.ReferenceFilesInfoMessage'
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
        noVisibleContentColumn:'#UT#Ausgeblendete bearbeitbare Spalten wurden sichtbar geschaltet, da die Aufgabe zum Editieren geöffnet wurde, aber keine editierbare Spalte sichtbar war.',
        gridEndReached: '#UT#Kein weiteres Segment bearbeitbar!',
        gridStartReached: '#UT#Kein vorheriges Segment bearbeitbar!',
        gridEndReachedFiltered: '#UT#Kein weiteres Segment im Workflow bearbeitbar!',
        gridStartReachedFiltered: '#UT#Kein vorheriges Segment im Workflow bearbeitbar!'
    },
    DEC_DIGITS: [48, 49, 50, 51, 52, 53, 54, 55, 56, 57],
    refs : [{
        ref : 'segmentGrid',
        selector : '#segmentgrid'
    },{
        ref : 'navi',
        selector : '#metapanel #naviToolbar'
    },{
        ref:'filepanel',
        selector:'#filepanel'
    },{
        ref:'segmentsHtmleditor',
        selector:'#segmentsHtmleditor'
    },{
        ref: 'languageResourceSearchGrid',
        selector: 'languageResourceSearchGrid'
    },{
        ref: 'languageResourceEditorPanel',
        selector: 'languageResourceEditorPanel'
    }],
    registeredTooltips: [],
    isEditing: false,
    isCapturingChange: false,
    keyMapConfig: null,
    editorKeyMap: null,
    generalKeyMap: null,
    prevNextSegment: null,
    sourceTags: null,
    copiedSelectionWithTagHandling: null,
    resetSegmentValueForEditor: null,
    /**
     * TODO FIXME: this references the HTML editor and therefore better should be called htmlEditor. The Range mixin though expects "editor"
     * {Editor.view.segments.HtmlEditor}
     */
    editor: null,
    listen: {
        controller: {
            '#Editor.$application': {
                editorViewportClosed: 'onCloseEditorViewport',
                editorViewportOpened: 'onOpenEditorViewport'
            },
            '#QualityMqm': {
            	afterInsertMqmTag: 'handleAfterContentChange'
            },
            '#Editor.plugins.TrackChanges.controller.Editor':{
                setValueForEditor: 'setValueForEditor'
            }
        },
        component: {
            '#metapanel metapanelNavi button' : {
                click : 'buttonClickDispatcher'
            },
            'segmentsHtmleditor': {
                initialize: 'initEditor',
                contentErrors: 'handleSaveWithErrors',
                afterSetValueAndMarkup: 'handleAfterContentChange',
                afterInsertMarkup: 'handleDelayedChange'
            },
            'roweditor': {
                destroy: 'handleDestroyRoweditor'
            },
            'roweditor displayfield[isContentColumn!=true]': {
                afterrender: 'initMoveToolTip'
            },
            '#segmentgrid': {
                afterrender: 'initEditPluginHandler',
                select:'onSegmentGridSelect',
                segmentSizeChanged:'onSegmentGridSegmentsSizeChanged'
            },
            '#showReferenceFilesButton': {
                click:'onShowReferenceFilesButtonClick'
            },
            
            '#commentContainer textarea': {
                specialkey: 'handleCommentEnter'
            },
            
            'taskConfirmationWindow button': {
                click:'taskConfirm'
            },
            '#naviToolbar #btnInsertWhitespaceNbsp': {
                click: 'insertWhitespaceNbsp'
            },
            '#naviToolbar #btnInsertWhitespaceNewline': {
                click: 'insertWhitespaceNewline'
            },
            '#naviToolbar #btnInsertWhitespaceTab': {
                click: 'insertWhitespaceTab'
            },
            '#segmentMinMaxLength': {
                insertNewline: 'insertWhitespaceNewline'
            }
        },
        store:{
            '#Segments':{
                load:'onSegmentsStoreLoad'
            }
        }
    },

    routes: {
        'task/:id/:segmentNrInTask/edit': 'onTaskSegmentEditRoute',
        'task/:id/edit': 'onTaskSegmentEditRoute'
    },

    init : function() {
        var me = this;
        
        Ext.override('Ext.util.KeyMap',{
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
            'ctrl-d':         ['D',{ctrl: true, alt: false}, me.watchSegment, true],
            'ctrl-s':         ['S',{ctrl: true, alt: false}, me.save, true],
            'ctrl-g':         ['G',{ctrl: true, alt: false}, me.scrollToSegment, true],
            'ctrl-z':         ['Z',{ctrl: true, alt: false}, me.undo],
            'ctrl-y':         ['Y',{ctrl: true, alt: false}, me.redo],
            'ctrl-l':         ['L',{ctrl: true, alt: false}, me.focusSegmentShortcut, true],
            'ctrl-enter':     [[10,13],{ctrl: true, alt: false}, me.saveNextByWorkflow],
            'ctrl-alt-enter': [[10,13],{ctrl: true, alt: true, shift: false}, me.saveNext],
            'ctrl-alt-shift-enter': [[10,13],{ctrl: true, alt: true, shift: true}, me.savePrevious],
            'esc':            [Ext.EventObjectImpl.ESC, null, me.cancel],
            'ctrl-alt-left':  [Ext.EventObjectImpl.LEFT,{ctrl: true, alt: true}, me.goToLeft],
            'ctrl-alt-right': [Ext.EventObjectImpl.RIGHT,{ctrl: true, alt: true}, me.goToRight],
            'alt-pageup':     [Ext.EventObjectImpl.PAGE_UP,{ctrl: false, alt: true}, me.goToUpperByWorkflowNoSave],
            'alt-pagedown':   [Ext.EventObjectImpl.PAGE_DOWN,{ctrl: false, alt: true}, me.goToLowerByWorkflowNoSave],
            'alt-del':        [Ext.EventObjectImpl.DELETE,{ctrl: false, alt: true}, me.resetSegment],
            'ctrl-alt-up':    [Ext.EventObjectImpl.UP,{ctrl: true, alt: true}, me.goToUpperNoSave, true],
            'ctrl-alt-down':  [Ext.EventObjectImpl.DOWN,{ctrl: true, alt: true}, me.goToLowerNoSave, true],
            'alt-c':          ['C',{ctrl: false, alt: true}, me.handleOpenComments, true],
            'alt-s':          ['S',{ctrl: false, alt: true}, me.handleDigitPreparation(me.handleChangeState), true],
            'ctrl-comma':     [188,{ctrl: true, alt: false, shift: false}, me.handleDigitPreparation(me.handleInsertTag), true],
            'ctrl-shift-comma': [188,{ctrl: true, alt: false, shift: true}, me.handleDigitPreparation(me.handleInsertTagShift), true],
            'F2':             [Ext.EventObjectImpl.F2,{ctrl: false, alt: false}, me.handleF2KeyPress, true],
            'F3':             [Ext.EventObjectImpl.F3,{ctrl: false, alt: false}, me.handleF3KeyPress, true],
            'ctrl-insert':    [Ext.EventObjectImpl.INSERT,{ctrl: true, alt: false}, me.copySourceToTarget],
            'ctrl-dot':       [190,{ctrl: true, alt: false}, me.copySourceToTarget], //Mac Alternative key code,
            // DEC_DIGITS:
            // (If you change the setting for a defaultEventAction for DEC_DIGITS,
            // please check if eventIsTranslate5() still works as expected 
            // in Editor.util.Event).
            'alt-DIGIT':      [me.DEC_DIGITS,{ctrl: false, alt: true}, me.handleAssignMQMTag, true],
            'DIGIT':          [me.DEC_DIGITS,{ctrl: false, alt: false}, me.handleDigit],
            'ctrl-zoomIn':    [[187, Ext.EventObjectImpl.NUM_PLUS],{ctrl: true, alt: false, shift: false}, me.handleZoomIn, true],
            'ctrl-zoomOut':   [[189, Ext.EventObjectImpl.NUM_MINUS],{ctrl: true, alt: false, shift: false}, me.handleZoomOut, true]
        };
        
        //FIXME let me come from the server out of AutoStates.php
        Editor.data.segments.autoStates = {
            'TRANSLATED': 0,
            'REVIEWED': 1,
            'REVIEWED_AUTO': 2,
            'BLOCKED': 3,
            'NOT_TRANSLATED': 4,
            'REVIEWED_UNTOUCHED': 5,
            'REVIEWED_UNCHANGED': 6,
            'REVIEWED_UNCHANGED_AUTO': 7,
            'REVIEWED_TRANSLATOR': 8,
            'REVIEWED_TRANSLATOR_AUTO': 9,
            'REVIEWED_PM': 10,
            'REVIEWED_PM_AUTO': 11,
            'REVIEWED_PM_UNCHANGED': 12,
            'REVIEWED_PM_UNCHANGED_AUTO': 13,
            'TRANSLATED_AUTO': 14,
            'EDITING_BY_USER': 998,
            'PENDING': 999
        };
    },
    /**
     * track isEditing state 
     */
    initEditPluginHandler: function (segmentsGrid) {
        var me = this,
            plug = me.getEditPlugin(),
            disableEditing = function(){me.isEditing = false;};
            
        plug.on('beforestartedit', me.handleBeforeStartEdit, me);
        plug.on('beforeedit', me.handleStartEdit, me);
        plug.on('canceledit', disableEditing);
        plug.on('edit', disableEditing);
        
        Ext.getDoc().on('copy', me.copySelectionWithInternalTags, me, {priority: 9999, delegated: false});
        Ext.getDoc().on('cut', me.copySelectionWithInternalTags, me, {priority: 9999, delegated: false});
        //previous cut handlers may stop default event processing, so we have to remove and cut the selected content manually in later handler:
        Ext.getDoc().on('cut', me.removeSelectionAfterCut, me, {priority: 1001, delegated: false});        
        
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
        
        /**
         * disable the column show / hide menu while editing a segment (EXT6UPD-85)
         */
        Ext.override(segmentsGrid.getHeaderContainer(), {
            beforeMenuShow: function(menu) {
                this.callParent([menu]);
                menu.down('#columnItem').setDisabled(plug.editing);
            }
        });
        
        me.generalKeyMap = new Ext.util.KeyMap(Ext.getDoc(), me.getKeyMapConfig('application', {
            'alt-c':[
                'C',{ctrl: false, alt: true}, 
                function(key, e){
                    e.stopEvent();
                    Ext.fireEvent('editorOpenComments');
                    return false;
                }
            ]
        }));
        //inits the editor iframe directly after loading the application
        plug.editor = plug.initEditor();
        
        me.handleReferenceFilesMessage();

        //after segment grid is rendered, get the segment grid segment size values and update the html editor text size with those values
        me.onSegmentGridSegmentsSizeChanged(segmentsGrid,segmentsGrid.newSegmentSizeCls,segmentsGrid.oldSegmentSizeCls);
    },
    
    handleSortOrFilter: function() {
        var me = this,
            plug = me.getEditPlugin();
        
        me.prevNextSegment.handleSortOrFilter();
        if(plug && plug.editor && plug.editor.context) {
            plug.editor.context.reordered = true;
        }
    },
    
    /**
     * initializes the roweditor moveable tooltip
     */
    initMoveToolTip: function(displayfield){
        var me = this,
            id = displayfield.getId()+'-bodyEl';
        if(displayfield.ownQuicktip){
            return;
        }
        me.registeredTooltips.push(id);
        Ext.tip.QuickTipManager.register({
            target: id,
            title: me.messages.editorMoveTitle,
            text: me.messages.editorMove + '<br /><br />' + me.messages.takeTagTooltip
        });
    },
    handleDestroyRoweditor: function() {
        //FIXME needed for Ext 6.2, possibly removable for further ExtJS updates, see T5DEV-172
        var me = this;
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
            this.fireEvent('prepareTrackChangesForSaving');
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
                var skip = i++ > 12, 
                    pending = segment.get('autoStateId') == Editor.data.segments.autoStates.PENDING;
                //skip after 6 seconds, with can not edit message
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
    handleStartEdit: function(plugin, context) {
        var me = this;
        me.isEditing = true;
        me.prevNextSegment.calculateRows(context); //context.record, context.rowIdx TODO
        me.getSourceTags(context);
    },
    getSourceTags: function(context) {
        var me = this,
            plug = me.getEditPlugin(),
            source = context.record.get('source'),
            tempNode, walkNodes;

        me.sourceTags = [];
        //do nothing when editing the source field
        if(/^source/.test(context.column.dataIndex)){
            return;
        }

        tempNode = document.createElement('DIV');
        Ext.fly(tempNode).update(source);

        walkNodes = function(rootNode) {
            Ext.each(rootNode.childNodes, function(item){
                if(Ext.isTextNode(item) || item.tagName !== 'DIV'){
                    return;
                }
                if(item.tagName === 'DIV' && /(^|[\s])term([\s]|$)/.test(item.className)){
                    walkNodes(item);
                    return;
                }
                var divItem = Ext.fly(item),
                    tagNr = divItem.down('span.short').dom.innerHTML.replace(/[^0-9]/g, ''),
                    tagType = item.className.match(/^(open|single|close)\s/),
                    //we use a real array starting at 0 for tag 1
                    idx = tagNr-1;
                if(!tagType) {
                    return;
                }
                tagType = tagType[1];
                if(!me.sourceTags[idx]) {
                    me.sourceTags[idx] = {};
                }
                me.sourceTags[idx][plug.editor.mainEditor.idPrefix+tagType+tagNr] = '<div class="'+item.className+'">'+item.innerHTML+'</div>';
            });
        };
        walkNodes(tempNode);
    },
    /**
     * Gibt die RowEditing Instanz des Grids zurück
     * @returns Editor.view.segments.RowEditing
     */
    getEditPlugin: function() {
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
    getKeyMapConfig: function(area, overwrite) {
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
        Ext.Object.each(me.keyMapConfig, function(key, item){
            //copy the config to the overwrite object, only if it does not exist already!
            if(overwrite[key]) {
                return;
            }
            overwrite[key] = item;
        });
        
        //no we process the merged configs:
        Ext.Object.each(overwrite, function(key, item){
            if(!item) {
                return;
            }
            
            //applies the keys config and scope to a fresh conf object
            var confObj = Ext.applyIf({
                key: item[0],
                scope: item[4] || me
            }, item[1]);
            if(item[3]) {
                confObj.defaultEventAction = 'stopEvent';
                //prepends the event propagation stopper
            }
            confObj.fn = function(key, e) {
                item[2].apply(confObj.scope, arguments);
                //FIXME Ausnahme für digitHandler definieren, wenn nicht im isDigitPreparation Modus!
                return false; //stop further key binding processing
            };
            conf.push(confObj);
        });
        return conf;
    },
    /**
     * binds strg + enter as save segment combination
     * @param {Editor.view.segments.HtmlEditor} editor
     */
    initEditor: function(editor){
        var me = this,
            docEl = Ext.get(editor.getDoc());
        this.editor = editor;
        
        if(me.editorKeyMap) {
            me.editorKeyMap.destroy();
        }
        
        editor.editorKeyMap = me.editorKeyMap = new Editor.view.segments.EditorKeyMap({
            target: docEl,
            binding: me.getKeyMapConfig('editor', {
                // insert whitespace key events
                'ctrl-shift-space': [Ext.EventObjectImpl.SPACE,{ctrl: true, alt: false, shift: true}, me.insertWhitespaceNbsp, true],
                'shift-enter': [Ext.EventObjectImpl.ENTER,{ctrl: false, alt: false, shift: true}, me.insertWhitespaceNewline, true],
                'enter': [Ext.EventObjectImpl.ENTER,{ctrl: false, alt: false, shift: false}, me.insertWhitespaceNewline, true],
                'tab': [Ext.EventObjectImpl.TAB,{ctrl: false, alt: false}, me.insertWhitespaceTab, true]
            })
        });
        editor.DEC_DIGITS = me.DEC_DIGITS;
        
        docEl.on({
            dragend:{
                delegated: false,
                priority: 9999,
                fn: me.handleDragEnd,
                scope: this,
                preventDefault: false
            },
            keyup:{
                delegated: false,
                priority: 9999,
                fn: me.handleKeyUp,
                scope: this,
                preventDefault: false
            },
            mouseup:{
                delegated: false,
                priority: 9999,
                fn: me.handleMouseUp,
                scope: this,
                preventDefault: false
            },
            singletap:{
                delegated: false,
                priority: 9999,
                fn: me.handleMouseUp,
                scope: this,
                preventDefault: false
            },
            copy: {
                delegated: false,
                priority: 9999,
                fn: me.copySelectionWithInternalTags,
                scope: me
            },
            cut: {
                delegated: false,
                priority: 9999,
                fn: me.copySelectionWithInternalTags,
                scope: me
            },
            paste: {
                delegated: false,
                priority: 5000,
                fn: me.pasteContent,
                scope: me
            }
        });

        //add second cut handler to remove the content if default handler prevented
        docEl.on('cut', me.removeSelectionAfterCut, me, {priority: 1001, delegated: false});        

        
        // Paste does not reach the browser's clipboard-functionality,
        // so we need our own SnapshotHistory for handling CTRL+Z and CTRL+Y.
        me.fireEvent('activateSnapshotHistory');
        
        if(me.editorTooltip){
            me.editorTooltip.setTarget(editor.getEditorBody());
            me.editorTooltip.targetIframe = editor.iframeEl;
        }
        else {
            me.editorTooltip = Ext.create('Editor.view.ToolTip', {
                target: editor.getDoc(),
                targetIframe: editor.iframeEl
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
        me.clearKeyMaps();
        // removing the following handler has no effect, but it should be removed here!
        //FIXME should be unbound since rebind on each task open!? or not? 
        //Ext.getDoc().un('copy', me.copySelectionWithInternalTags);
        me.tooltip && me.tooltip.destroy();
        me.taskConfirmation && me.taskConfirmation.destroy();
    },
    clearKeyMaps: function() {
        var me = this;
        if(me.editorKeyMap) {
            //FIXME: Fix for the bug in internet explorer
            //http://jira.translate5.net/browse/TRANSLATE-1086
        	//same problem with different error log under edge
        	//https://jira.translate5.net/browse/TRANSLATE-2037
            if(!Ext.isIE && !Ext.isEdge){
                me.editorKeyMap.destroy();
            }
            me.editorKeyMap = null;
        }
        
        if(me.generalKeyMap) {
            //FIXME: Fix for the bug in internet explorer
            //http://jira.translate5.net/browse/TRANSLATE-1086
        	//same problem with different error log under edge
        	//https://jira.translate5.net/browse/TRANSLATE-2037
            if(!Ext.isIE && !Ext.isEdge){
                me.generalKeyMap.destroy();
            }
            me.generalKeyMap = null;
        }
    },
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
    save: function() {
        var me = this,
            ed = me.getEditPlugin(),
            rec = ed.editing && ed.context.record;

        //since save without moving was triggered, we have to reset the calculated data
        me.prevNextSegment.reset();

        me.fireEvent('saveUnsavedComments');
        if(me.isEditing && rec && rec.get('editable')) {
            me.fireEvent('prepareTrackChangesForSaving');
            me.fireEvent('saveSegment');
        }
    },
    /**
     * Handler for CTRL+X
     */
    undo: function() {
        this.fireEvent('undo'); // see SnapshotHistory
    },
    /**
     * Handler for CTRL+Y
     */
    redo: function() {
        this.fireEvent('redo'); // see SnapshotHistory
    },

    /***
     * Focus the segment given in the prompt window input
     */
    focusSegmentShortcut:function (){
        var me = this,
            prompt = Ext.Msg.prompt('Go to segment', 'No.:', function(btn, text){
            if (btn === 'ok'){
                me.getSegmentGrid().focusSegment(text);
            }
        });
        prompt.down('textfield').focus(200);
    },

    /**
     * handleAfterContentChange: save snapshot.
     */
    handleAfterContentChange: function(preventSaveSnapshot) {
    	if(!preventSaveSnapshot) {
    	    this.fireEvent('saveSnapshot'); // see SnapshotHistory
    	}
    	// trigger deferred change handler
    	if(!this.isCapturingChange){
    		var me = this;
    		this.isCapturingChange = true;
    		setTimeout(function(){ me.handleDelayedChange(); }, Editor.data.editor.deferredChangeTimeout);
    	}
    },
    /**
     * handleAfterCursorMove: save new position of cursor if necessary.
     */
    handleAfterCursorMove: function() {
    	this.fireEvent('updateSnapshotBookmark'); // see SnapshotHistory
    },
    
    /**
     * handleDelayedChange: fire deferred change event if still changing
     */    
    handleDelayedChange: function(){
    	if(this.isEditing) {
    		this.fireEvent('clockedchange',  this.editor, this.getEditPlugin().context);
        }
    	this.isCapturingChange = false;
    },

    /**
     * After keyboard-event: handle changes if event is not to be ignored.
     * ('change'-event from segmentsHtmleditor does not work; is not really envoked when we need it!)
     * @param event
     */
    handleKeyUp: function(event) {
        var me = this;
        me.consoleLog('Editor: handleKeyUp');
        me.event = event; // Editor.util.Event
	    // New content? 
        // Ignore 
        // - keys that don't produce content (strg,alt,shift itself, arrows etc)
        // - keys that must not change the content in the Editor (e.g. strg-z will not always do what the user expects)
	    if (!me.eventIsCtrlZ() && !me.eventIsCtrlY() && !me.eventHasToBeIgnored() && !me.eventHasToBeIgnoredAndStopped()) {
	    	me.handleAfterContentChange();
	    	return;
	    }
	    // New position of cursor?
	    if (me.eventIsArrowKey()) {
	    	me.handleAfterCursorMove();
	    }
    },
    /**
     * After mouse-click.
     */
    handleMouseUp: function() {
        var me = this;
        me.consoleLog('Editor: handleMouseUp');
        me.handleAfterCursorMove();
    },
    /**
     * 
     */
    handleDragEnd: function() {
        var me = this;
        me.consoleLog('Editor: handleDragEnd');
        me.fireEvent('afterDragEnd');
    },
    
    /**
     * Special Universal preparation Handler for pressing DIGIT keys
     * A preparation keyboard shortcut can be defined, for example ALT-S. 
     * If ALT-S is pressed, then if the next key is a DIGIT the given 
     * digithandler will be called with the preseed DIGIT.
     * @param {Function} must be function in the controller scope, since scope parameter is not supported
     */
    handleDigitPreparation: function(digithandler) {
        var me = this;
        return function(key, event) {
            me.digitHandler = digithandler;
            event.isDigitPreparation = true;
            event.stopEvent();
            return false;
        };
    },
    /**
     * Digit handler, does only something if a DIGIT preparation shortcut was pressed directly before.
     */
    handleDigit: function(k, e) {
        if(e.lastWasDigitPreparation){
            e.stopEvent();
            this.digitHandler(k, e);
            return false;
        } 
    },
    
    /**
     * Keyboard handler for zoom in, calls just the viewmodes function directly
     */
    handleZoomIn: function() {
        this.getSegmentGrid().setSegmentSize(1, true);
    },
    /**
     * Keyboard handler for zoom out, calls just the viewmodes function directly
     */
    handleZoomOut: function() {
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
        var type = (inWorkflow) ? 'workflow' : 'editable',
            msg = (inWorkflow) ? this.messages.gridStartReachedFiltered : this.messages.gridStartReached;
        this.prevNextSegment.calcPrev(type, msg);
    },
    /**
     * triggers the calculation of the next segment with the appropriate configs/msg
     */
    calcNext: function(inWorkflow){
        var type = (inWorkflow) ? 'workflow' : 'editable',
            msg = (inWorkflow) ? this.messages.gridEndReachedFiltered : this.messages.gridEndReached;
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
    /**
     * Handler for savePrevious Button
     * @return {Boolean} true if there is a next segment, false otherwise
     */
    savePreviousByWorkflow: function() {
        this.calcPrev(true);
        this.saveOtherRow();
    },
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
    saveOtherRow: function() {
        var me = this;
        
        me.fireEvent('saveUnsavedComments');
        if(!me.isEditing) {
            return;
        }
        me.fireEvent('prepareTrackChangesForSaving');
        
        me.fireEvent('saveSegment', {
            scope: me,
            segmentUsageFinished: function(){
                me.openNextRow();
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
     * @param {Bool} isTagError (optional; default: true)
     */
    handleSaveWithErrors: function(editor, msg, isTagError = true){
        var me = this,
            msgBox;
        
        //if there was an empty message we assume that there was no error,
        if(!msg) {
            return;
        }
        
        msgBox = Ext.create('Ext.window.MessageBox', {
            buttonText:{
                ok: 'OK',
                yes: 'OK',
                no: me.messages.saveAnyway
            }
        });
        // tag-errors: check if user is allowed to save anyway
        if(isTagError && Editor.app.getTaskConfig('segments.userCanIgnoreTagValidation')) {
            msgBox.confirm(me.messages.errorTitle, msg, function(btn) {
                if(btn === 'no') {
                    me.saveAndIgnoreContentErrors();
                }
            },me);
        }
        else {
            msgBox.alert(me.messages.errorTitle, msg);
        }
    },
    /**
     * triggers the save chain but ignoring htmleditor content errors then
     */
    saveAndIgnoreContentErrors: function() {
        var me = this,
            plug = me.getEditPlugin();
        plug.editor.mainEditor.disableContentErrorCheckOnce();
        if(me.prevNextSegment.getCalculated()){
            me.saveOtherRow.apply(me);
            return;
        }
        me.save();
    },
    /**
     * Handler für cancel Button
     */
    cancel: function() {
        this.getEditPlugin().cancelEdit();
    },
    /**
     * Handles pressing the keyboard shortcuts for changing the segment state
     */
    handleChangeState: function(key, e) {
        var param = Number(key) - 48;
        //we ignore 0, since this is no valid state
        if(param !== 0){
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
        var me = this;
        if(!me.isEditing) {
            return;
        }
        e.preventDefault();
        e.stopEvent();
        var param = Number(key) - 48;
        if (param === 0) {
            param = 10;
        }
        if(e.shiftKey) {
            param = param + 10;
        }
        me.fireEvent('assignMQMTag', param);
    },
    /**
     * Move the editor about one editable field
     */
    goToCustom: function(direction, saveRecord) {
        var me = this,
            info = me.getColInfo(),
            idx = info && info.foundIdx,
            cols = info && info.columns,
            store = me.getSegmentGrid().store,
            plug = me.getEditPlugin(),
            newRec;
        
        if(info === false) {
        return;
        }
        newRec = store.getAt(store.indexOf(plug.context.record) + direction);
        
        //check if there exists a next/prev row, if not we dont need to move the editor.
        while(newRec && !newRec.get('editable')) {
            newRec = store.getAt(store.indexOf(newRec) + direction);
        }
        if(cols[idx + direction]) {
        plug.editor.changeColumnToEdit(cols[idx + direction]);
        return;
        }
        if(direction > 0) {
            //goto next segment and first col
            if(newRec) {
                plug.editor.changeColumnToEdit(cols[0]);
            }
            if (saveRecord) {
            me.saveNext();
            }
            else {
            me.goToLowerNoSave();
            }
            return;
        }
        //goto prev segment and last col
        if(newRec) {
            plug.editor.changeColumnToEdit(cols[cols.length - 1]);
        }
        if (saveRecord) {
        me.savePrevious();
        }
        else {
        me.goToUpperNoSave();
        }
    },
    /**
     * Move the editor about one editable field
     */
    goAlternateRight: function() {
        this.goToCustom(1, true);
    },
    /**
     * Move the editor about one editable field
     */
    goAlternateLeft: function() {
        this.goToCustom(-1, true);
    },
    /**
     * Move the editor about one editable field left
     */
    goToLeft: function(key, e) {
        var me = this,
            direction = -1;
        if(!me.isEditing) {
            return;
        }
        e.preventDefault();
        e.stopEvent();
        me.goToCustom(direction, true);
    },
    /**
     * Move the editor about one editable field right
     */
    goToRight: function(key, e) {
        var me = this,
            direction = 1;
        if(!me.isEditing) {
            return;
        }
        e.preventDefault();
        e.stopEvent();
        me.goToCustom(direction, true);
    },
    /**
     * returns the visible columns and which column has actually the editor
     * @return {Object}
     */
    getColInfo: function() {
        var me = this,
            plug = me.getEditPlugin(),
            columns = me.getSegmentGrid().query('contentEditableColumn:not([hidden])'),
            foundIdx = false,
            current = plug.editor.getEditedField();
        
        if(!plug || !plug.editor || !plug.editing) {
            return false;
        }
        
        Ext.Array.each(columns, function(col, idx) {
        if(col.dataIndex === current) {
            foundIdx = idx;
        }
        });
        if(foundIdx === false) {
        return false;
        }

        return {
        plug: plug,
        columns: columns,
        foundIdx: foundIdx
        };
    },
    
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
        me.fireEvent('prepareCompleteReplace',rec.get(columnToRead),true); // if TrackChanges are activated, DEL- and INS-markups are added first and then setValueForEditor is applied from there (= again, but so what)
        editor.mainEditor.setValueAndMarkup(me.resetSegmentValueForEditor, rec, editor.columnToEdit);
    },
    setValueForEditor: function(value) {
        var me = this;
        me.resetSegmentValueForEditor = value;
    },
    /**
     * handler for the F2 key
     */
    handleF2KeyPress: function() {
        var me = this,
            grid = me.getSegmentGrid(),
            selModel = grid.getSelectionModel(),
            ed = me.getEditPlugin(),
            cols = grid.query('contentEditableColumn:not([hidden])'),
            sel = [],
            firstEditableRow = grid.store.getFirsteditableRow(),
            callback;
        
        if(Ext.isEmpty(firstEditableRow)) {
            return;
        }

        if (ed.editing) {
            ed.editor.mainEditor.deferFocus();
            return;
        }
        
        if (selModel.hasSelection()){
            //with selection scroll the selection into the viewport and open it afterwards
            sel = selModel.getSelection();
            grid.scrollTo(grid.store.indexOf(sel[0]),{
                callback: function() {
                    ed.startEdit(sel[0], cols[0]);
                }
            });
        } else {
            //with no selection, scroll to the first editable select, select it, then open it
            callback = function() {
                grid.selectOrFocus(firstEditableRow);
                sel = selModel.getSelection();
                var editStarted = ed.startEdit(sel[0], cols[0]);
                if(editStarted) {
                Editor.MessageBox.addInfo(me.messages.f2FirstOpened);
                }
            };
            grid.scrollTo(firstEditableRow, {
                callback: callback,
                notScrollCallback: callback
            });
        }
    },
    /***
     * F3 editor event handler.
     * This will set the focus in the sourceSearch field of concordence search panel
     */
    handleF3KeyPress: function() {
        var me = this,
            searchGrid = me.getLanguageResourceSearchGrid(),
            editorPanel = me.getLanguageResourceEditorPanel(),
            delay;
        if(!editorPanel || !searchGrid){
            return;
        }
        // expand if collapsed and set the delay to 0.5 sec (delay because of expand animation)
        if(editorPanel.getCollapsed()){
            editorPanel.expand();
            delay = 500;
        }
        editorPanel.setActiveTab(searchGrid);
        searchGrid.down('#sourceSearch').focus(false,delay);
    },
    removeSelectionAfterCut: function(e) {
        if(!e.defaultPrevented || !e.stopped) {
            return;
        }
        var activeElement = Ext.fly(Ext.Element.getActiveElement()),
            sel;

        //currently removing the content after cut is only useful in the htmleditor, nowhere else
        if(activeElement.is('iframe.x-htmleditor-iframe')) {            
            sel = rangy.getSelection(this.getEditPlugin().editor.mainEditor.getEditorBody());       
            if(sel.rangeCount) {
                sel.getRangeAt(0).deleteContents();
                sel.getRangeAt(0).collapse();
                sel.getRangeAt(0).select();
            }
        }
    },
    copySelectionWithInternalTags: function(e) {
        // The user will expect the copied text to be available in the clipboard,
        // so we do NOT stop the propagation of the event.
        if(!this.editorKeyMap) {
            //if we are not in a task, we may not invoke. Easiest way: check for editorKeyMap 
            return;
        }
        // CTRL+C gets the selected text (including internal tags)
        var me = this,
            plug = me.getEditPlugin(),
            copy = {},
            isTagColumn = false,
            sel, selRange, i,
            selInternalTags,
            activeElement,
            position;

        //reset previous copies
        me.copiedSelectionWithTagHandling = null;
            
        //do only something when editing targets:
        if(!me.isEditing || !/^target/.test(plug.editor.columnToEdit)){
            return;
        }

        activeElement = Ext.fly(Ext.Element.getActiveElement());
        isTagColumn = activeElement.hasCls('segment-tag-column');
        // if the focus is not in an element that can use internal tags, we have nothing to do.
        if (!activeElement.hasCls('segment-tag-container') && !isTagColumn && !activeElement.is('iframe.x-htmleditor-iframe')) {
            return;
        }

        // Where does the copied text come from? If it's from a segment's source or the Editor itself, 
        // we store the id from the segment, because in this case it will be allowed to paste the internal tags 
        // into the target (but only if the target belongs to the same segment as the source).
        copy = {
            selDataHtml: '', // = selected content WITH internal tags
            selDataText: '', // = selected content WITHOUT internal tags
            selSegmentId: '',
            format: ''
        };
        
        sel = rangy.getSelection();
        
        // selections that need extra handling:
        if(isTagColumn || activeElement.hasCls('segment-tag-container') || activeElement.hasCls('type-source')) {
            if(isTagColumn && activeElement.down('div.x-grid-cell-inner')) {
                activeElement = activeElement.down('div.x-grid-cell-inner');
            }
            // whole source segment selected? Then select the content within only.
            // (= without the surrounding "<div (...) class="segment-tag-container (...) type-source">(...)</div>"
            selRange = sel.rangeCount ? sel.getRangeAt(0) : null;
            position = me.getPositionInfoForRange(selRange, activeElement.dom);
            if(position.atStart && position.atEnd){
                sel.selectAllChildren(activeElement.dom);
            }
            copy.selSegmentId = plug.context.record.get('id');
            copy.format = 'div';
        }
        else if (activeElement.is('iframe.x-htmleditor-iframe')) {
            sel = rangy.getSelection(plug.editor.mainEditor.getEditorBody());
            copy.selSegmentId = plug.context.record.get('id');
            copy.format = 'img';
        }
        
        selRange = sel.rangeCount ? sel.getRangeAt(0) : null;
        if (selRange === null || selRange.collapsed) {
            return;
        }
        
        // Firefox uses multiple selections. 
        // For example: 'abc<del>def</del>ghi'
        // - Firefox: first range: 'abc', second range: 'ghi'
        // - Chrome: one single range with 'abc<del>def</del>ghi'
        for (i = 0; i < sel.rangeCount; i++) {
            selRange = sel.getRangeAt(i);
            selRange = me.getRangeWithFullInternalTags(selRange);
            copy.selDataHtml += selRange.toHtml();
        }
        
        // preset text and html with the found ranges
        // for insert as html (must not include element-ids that already exist in Ext.cache!)
        copy.selDataText = copy.selDataHtml = copy.selDataHtml.replace(/id="ext-element-[0-9]+"/, '');
        
        // for insert as text only
        //the toString is working if copying img tags
        if(copy.format == 'div') {
            // for copying internal tags as divs we have to do the following:
            selInternalTags = selRange.getNodes([1], function(node) {
                return node.classList.contains('internal-tag');
            });
            Ext.Array.each(selInternalTags, function(internalTag) {
                copy.selDataText = copy.selDataText.replace(internalTag.outerHTML, '');
            });
        }
        else {
            copy.selDataText = selRange.toString();
        }
        
        me.copiedSelectionWithTagHandling = copy;

        // console.log(me.copiedSelectionWithTagHandling);
        // if we are in a regular copy / cut event we set the clipboard content to our needs
        if(e && e.browserEvent) {
            e.browserEvent.clipboardData.setData('text/plain', copy.selDataText);
            e.browserEvent.clipboardData.setData('text/html', copy.selDataHtml);
            e.preventDefault();
            e.stopEvent();
        }
    },
    /**
     * Pasting our own content must be handled special to insert correct tags
     */
    pasteContent: function(e){
        e.stopPropagation();
        e.preventDefault();
        var me = this,
            plug = me.getEditPlugin(),
            segmentId = plug.context.record.get('id'),
            internalClip = me.copiedSelectionWithTagHandling || {},
            clipboard = (e.browserEvent.clipboardData || window.clipboardData),
            clipboardText = clipboard.getData('Text'),
            clipboardHtml = clipboard.getData('text/html'),
            toInsert, sel,
            textMatch = clipboardText == internalClip.selDataText,
            //the clipboardHtml adds meta information like charset and so on, so we just check if 
            // the stored one is a substring of the one in the clipboard
            htmlMatch = clipboardHtml.includes(internalClip.selDataHtml);

        //remove selected content before pasting the new content
        sel = rangy.getSelection(this.getEditPlugin().editor.mainEditor.getEditorBody());       
        if(sel.rangeCount) {
            sel.getRangeAt(0).deleteContents();
            sel.getRangeAt(0).collapse();
            sel.getRangeAt(0).select();
        }

        //when making a copy in translate5, we store the content in an internal variable and in the clipboard
        //if neither the text or html clipboard content matches the internally stored content, 
        // that means that the pasted content comes from outside and we insert just text:
        if(me.copiedSelectionWithTagHandling === null || !textMatch || !htmlMatch) {
            me.editor.insertMarkup(Ext.String.htmlEncode(clipboardText));
            me.handleAfterContentChange(true); //prevent saving snapshot, since this is done in insertMarkup
            me.copiedSelectionWithTagHandling = null;
            return;
        }
        /*
        console.log("text", clipboardText);
        console.log("html", clipboardHtml);
        console.log("data", internalClip);
        */
        //to insert tags, the copy/cut from segment must be the same as the paste to segment, so that tags are not moved between segments
        if(segmentId === internalClip.selSegmentId) {
            toInsert = internalClip.selDataHtml;
        }
        else {
            toInsert = internalClip.selDataText;
        }

        // we always use insertMarkup, regardless if it is img or div content
        me.editor.insertMarkup(toInsert);
        me.handleAfterContentChange(true); //prevent saving snapshot, since this is done in insertMarkup
    },
    copySourceToTarget: function() {
        var plug = this.getEditPlugin();
        //do only something when editing targets:
        if(!this.isEditing || !/^target/.test(plug.editor.columnToEdit)){
            return;
        }
        plug.editor.mainEditor.insertMarkup(plug.context.record.get('source'));
    },
    insertWhitespaceNbsp: function(key, e) {
        this.insertWhitespace(key, e, 'nbsp');
    },
    insertWhitespaceTab: function(key, e) {
        this.insertWhitespace(key, e, 'tab');
    },
    insertWhitespaceNewline: function(key, e) {
        this.insertWhitespace(key, e, 'newline');
    },
    insertWhitespace: function(key, e, whitespaceType) {
        var me = this,
            userCanModifyWhitespaceTags = Editor.app.getTaskConfig('segments.userCanModifyWhitespaceTags'),
            userCanInsertWhitespaceTags = Editor.app.getTaskConfig('segments.userCanInsertWhitespaceTags'),
            tagNr,
            caret;
        if (!userCanModifyWhitespaceTags || !userCanInsertWhitespaceTags) {
            return;
        }
        caret = (e === undefined) ? null : me.getPositionOfCaret(); // caret bookmark not neccessary when triggered by event
        tagNr = me.getNextWhitespaceTagNumber();
        me.editor.insertWhitespaceInEditor(whitespaceType, tagNr);

        if (e === undefined) { // MinMaxLength inserts via an event that will not incorporate key data but needs a callback-event to restore the caret on it's own
            me.fireEvent('afterInsertWhitespace');
            return;
        }
        me.setPositionOfCaret(caret);
        if (e.delegatedTarget.nodeName.toLowerCase() === 'a') {
            me.editor.focus();
        }
        e.stopEvent();
    },
    /**
     * What's the number for the next Whitespace-Tag?
     * @return number nextTagNr
     */
    getNextWhitespaceTagNumber: function () {
        var me = this,
            plug = this.getEditPlugin(),
            editor = plug.editor.mainEditor,
            imgInTarget = editor.getDoc().getElementsByTagName('img'),
            collectedIds = ['0'];
        // source
        if(me.sourceTags){
            me.sourceTags.map(function(item){
                collectedIds = collectedIds.concat(Ext.Object.getKeys(item));
            });
        }
        // target
        Ext.Object.each(imgInTarget, function(key, imgNode){
            var imgClassList = imgNode.classList;
            if (imgClassList.contains('single') || imgClassList.contains('open')) {
                collectedIds.push(imgNode.id);
            }
        });
        // use the highest
        return Math.max.apply(null, collectedIds.map(function(val){
            return parseInt(val.replace(/[^0-9]*/,''));
        })) + 1;
    },

        handleInsertTagShift: function(key, e) {
            e.shiftKey = true; //somehow a hack, but is doing what it should do
            this.handleInsertTag(key, e);
        },
        handleInsertTag: function(key, e) {
            var me = this,
                plug = this.getEditPlugin(),
                editor = plug.editor.mainEditor,
                tagIdx = Number(key) - 49, //49 shifts tag nr down to 0 for tag 1
                sourceTagsForTagIdx,
                sel,
                selRange,
                rangeOpen,
                bookmarkOpen,
                rangeClose,
                insertBothTags;

            //key 0 equals to tadIdx -1 and equals to tag nr 10 (which equals to tagIdx 9)
            if(tagIdx < 0) {
                tagIdx = 9;
            }

            if(e.shiftKey) {
                tagIdx = tagIdx + 10;
            }
                
            //do only something when editing targets with tags and tag nrs > 1:
            if(!me.sourceTags || !me.sourceTags[tagIdx]){
                return;
            }
            
            sourceTagsForTagIdx = [];
            Ext.Object.each(me.sourceTags[tagIdx], function(id, tag){
                var tagObject = {'id': id, 'tag': tag}; 
                sourceTagsForTagIdx.push(tagObject);
            });
            
            // If a text range is marked, this short-cut inserts immediately the opening tag
            // at the start of the range and the closing tag at the end of the range.
            insertBothTags = false;
            sel = rangy.getSelection(editor.getEditorBody());
            selRange = sel.rangeCount ? sel.getRangeAt(0) : null;
            if (selRange !== null && !selRange.collapsed) {
                insertBothTags = true;
                rangeOpen = selRange.cloneRange();
                rangeOpen.collapse(true);
                bookmarkOpen = rangeOpen.getBookmark();
                rangeClose = selRange.cloneRange();
                rangeClose.collapse(false);
                // Make sure to insert closing tag first, otherwise the ranges gets messy.
                sourceTagsForTagIdx.sort(function(a, b){
                  var x = a.id.toLowerCase();
                  var y = b.id.toLowerCase();
                  if (x < y) {return -1;}
                  if (x > y) {return 1;}
                  return 0;
                });
            }
            
            Ext.Array.each(sourceTagsForTagIdx, function(tagObject){
                var id = tagObject.id,
		    tag = tagObject.tag,
                    tagInTarget = editor.getDoc().getElementById(id);
                if(tagInTarget && tagInTarget.parentNode.nodeName.toLowerCase() !== 'del'){
                    return;
                }
                if (insertBothTags) {
                    switch (true) {
                        case (id.indexOf('-open') !== -1):
                            // In Firefox, sel.setSingleRange(rangeOpen) does NOT work. No idea why.
                            // Workaround: use bookmark - THAT works somehow.
                            selRange.moveToBookmark(bookmarkOpen);
                            sel.setSingleRange(selRange);
                        break;
                        case (id.indexOf('-close') !== -1):
                            sel.setSingleRange(rangeClose);
                        break;
                    }
                }
                editor.insertMarkup(tag);
                if (!insertBothTags) {
                    return false;
                }
            });
            
            if (insertBothTags) {
                // place cursor at the end of the formerly selected content
                sel.removeAllRanges();
                sel.addRange(rangeClose);
            }
            
            e.stopEvent();
            return false;
        },
    /**
     * scrolls to the first segment.
     */
    handleHomeKeyPress: function() {
        this.getSegmentGrid().scrollTo(0);
    },
    /**
     * Handler for watchSegmentBtn
     * @param {Ext.button.Button} button
     */
    watchSegment: function() {
        if(!this.isEditing){
            return;
        }
        var me = this,
            model, config,
            ed = me.getEditPlugin(),
            record = ed.context.record,
            segmentId = record.get('id'),
            isWatched = Boolean(record.get('isWatched')),
            segmentUserAssocId = record.get('segmentUserAssocId'),
            navi = me.getNavi(),
            startText = navi.item_startWatchingSegment,
            stopText = navi.item_stopWatchingSegment,
            but = navi.down('#watchSegmentBtn'),
            success = function(rec, op) {
                var displayfield = ed.editor.down('displayfield[name="autoStateId"]'),
                    autoStateCell = ed.context && Ext.fly(ed.context.row).down('td.x-grid-cell-autoStateColumn div.x-grid-cell-inner');
                //isWatched
                record.set('isWatched', !isWatched);
                record.set('segmentUserAssocId', isWatched ? null : rec.data['id']);
                but.setTooltip(isWatched ? startText : stopText);
                but.toggle(!isWatched, true);
                if(op.action === 'create') {
                    me.fireEvent('watchlistAdded', record, me, rec);
                }
                else {
                    me.fireEvent('watchlistRemoved', record, me, rec);
                }
                //update autostate displayfield, since the displayfields are getting the rendered content, we have to fetch it here from rendered HTML too
                autoStateCell && displayfield.setValue(autoStateCell.getHtml());
            },
            failure = function() {
                but.setTooltip(isWatched ? stopText : startText);
                but.toggle(isWatched, true);
            };
        
        if (isWatched) {
            config = {
                id: segmentUserAssocId
            };
            model = Ext.create('Editor.model.SegmentUserAssoc', config);
            model.getProxy().setAppendId(true);
            model.erase({
                success: success,
                failure: failure
            });
        } else {
            model = Ext.create('Editor.model.SegmentUserAssoc', {'segmentId': segmentId});
            model.save({
                success: success,
                failure: failure
            });
        }
    },
    
    /**
     * In textareas ExtJS 6.2 enter keys are not bubbling up, but they are triggering a specialkey event
     *  we listen to that event and process our own keys then. 
     */
    handleCommentEnter: function(field, e) {
        var key = e.getKey();
        
        if (key === e.ENTER && e.hasModifier() && this.generalKeyMap) {
            this.generalKeyMap.handleTargetEvent(e);
        }
    },
    
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

    /***
     * "Reference files info message" window button handler
     */
    onShowReferenceFilesButtonClick:function(){
        var filePanel =this.getFilepanel().expand();
        var taskFiles = filePanel.down('taskfiles').expand();
        taskFiles.scrollable.scrollIntoView(taskFiles.down('referenceFileTree').view.el);
    },

    /**
     * Confirm the current task
     */
    taskConfirm: function () {
        Editor.util.TaskActions.confirm(function(task, app, strings){
            Editor.MessageBox.addSuccess(strings.taskConfirmed);
        });
    },

    /***
     * Edit task and focus segment route
     */
    onTaskSegmentEditRoute: function(taskId, segmentNrInTask) {
        var me = this;
        if(Editor.data.task?.id == taskId) {
            me.getSegmentGrid()?.focusSegment(segmentNrInTask);
            return;
        }
        if(Editor.data.task?.isModel){ // task is active, switch task
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
        if(Editor.data.task?.id == taskId){
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

    /***
     * When segment grid selection changes, update the hash
     */
    onSegmentGridSelect: function(grid, record, index, event){
        this.redirectTo('task/'+Editor.data.task.get('id')+'/'+record.get('segmentNrInTask')+'/edit');
    },

    /***
     * Segments store load event handler
     */
    onSegmentsStoreLoad: function(store){
        //check the content editable column visibility
        this.handleNotEditableContentColumn();
        
        // if already selected from other load listener or nothing selectable, return
        if(!store.getCount() || this.getSegmentGrid().selection) {
            return;
        }
        var jumpToSegmentIndex = 
            Editor.app.parseSegmentIdFromTaskEditHash(true)
            || store.proxy.reader.metaData?.jumpToSegmentIndex
            || 1;
        this.getSegmentGrid().focusSegment(jumpToSegmentIndex);
    },

    /**
     * Segments grid segment size event handler
     * 
     * @param {Ext.Component} grid 
     * @param {String} newSize 
     * @param {String} oldSize 
     */
    onSegmentGridSegmentsSizeChanged: function(grid, newSize, oldSize){
        var me=this,
            htmlEditor = me.getSegmentsHtmleditor();
        if(!htmlEditor){
            return;
        }
        htmlEditor.setSegmentSize(grid,newSize,oldSize);
    },
    
    /***
     * Make sure that there is an editable content column when user try to edit segment when the task is in edit mode
     */
    handleNotEditableContentColumn: function(){
        var me=this,
            isReadOnly = me.getSegmentGrid().lookupViewModel().get('taskIsReadonly');
        
        if(isReadOnly){
            return;
        }
        
        var hiddenEditable=Ext.ComponentQuery.query('contentEditableColumn[hidden="true"]'),
            allEditable = Ext.ComponentQuery.query('contentEditableColumn');

        if(hiddenEditable.length == allEditable.length){
            //no visible content editable column found. Show info message and display all hidden content editable columns
            Editor.MessageBox.addInfo(this.messages.noVisibleContentColumn);
            for(var i=0;i<hiddenEditable.length;i++){
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
    }
});
