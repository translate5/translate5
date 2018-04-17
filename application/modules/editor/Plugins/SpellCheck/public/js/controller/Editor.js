
/*
START LICENSE AND COPYRIGHT

 This file is part of translate5
 
 Copyright (c) 2013 - 2017 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

 Contact:  http://www.MittagQI.com/  /  service (ATT) MittagQI.com

 This file may be used under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE version 3
 as published by the Free Software Foundation and appearing in the file agpl3-license.txt 
 included in the packaging of this file.  Please review the following information 
 to ensure the GNU AFFERO GENERAL PUBLIC LICENSE version 3 requirements will be met:
 http://www.gnu.org/licenses/agpl.html
  
 There is a plugin exception available for use with this release of translate5 for
 translate5 plug-ins that are distributed under GNU AFFERO GENERAL PUBLIC LICENSE version 3:
 Please see http://www.translate5.net/plugin-exception.txt or plugin-exception.txt in the root
 folder of translate5.
  
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
 * @class Editor.plugins.SpellCheck.controller.Editor
 * @extends Ext.app.Controller
 */
Ext.define('Editor.plugins.SpellCheck.controller.Editor', {
    extend: 'Ext.app.Controller',
    requires: ['Editor.util.SegmentContent'],
    mixins: ['Editor.util.DevelopmentTools',
             'Editor.util.Event',
             'Editor.util.SegmentEditor',
             'Editor.util.SegmentEditorSnapshots',
             'Editor.plugins.SpellCheck.controller.UtilLanguageTool',
             'Editor.controller.SearchReplace',
             'Editor.util.SearchReplaceUtils'],
    refs:[{
        ref: 'segmentGrid',
        selector:'#segmentgrid'
    },{
        ref: 'editorPanel',
        selector:'#SpellCheckEditorPanel'
    }],
    listen: {
        controller: {
            '#Editor': {
                beforeKeyMapUsage: 'handleEditorKeyMapUsage',
                runSpellCheckOnSaving: 'handleSpellCheckOnSaving'
            },
            '#Editor.$application': {
                editorViewportOpened: 'initSpellCheckPluginForTask',
                editorViewportClosed: 'onDestroy'
            }
        },
        component: {
            'segmentsHtmleditor': {
                push: 'handleAfterContentUpdate',
                afterInsertMarkup: 'handleAfterContentUpdate'
            }
        },
    },
    messages: {
        moreInformation: '#UT#More information',
        errorsFoundOnSaving: '#UT#SpellCheck: errors found on saving Segment Nr. %segmentnr.',
        isApplyingInProgress: '#UT#The SpellCheck-Plugin was currently applying the matches, sorry for the inconvenience.',
        spellCheckOnSavingIsAlreadyRunningForAnotherSegment: '#UT#The SpellCheck on saving the segment failed because there is already another process running for Segment Nr. %segmentnr.',
    },
    statics: {
        // spellcheck-Node
        NODE_NAME_MATCH: 'span',
        // CSS-Classes for the spellcheck-Node
        CSS_CLASSNAME_MATCH: 'spellcheck',
        // CSS-Classes for error-types
        CSS_CLASSNAME_GRAMMERERROR: 'grammarError',
        CSS_CLASSNAME_SUGGESTION:   'suggestion',
        CSS_CLASSNAME_SPELLERROR:   'spellError',
        // Attributes for the spellcheck-Node
        ATTRIBUTE_ACTIVEMATCHINDEX: 'data-spellcheck-activeMatchIndex',
        // In ToolTips
        CSS_CLASSNAME_REPLACEMENTLINK:  'spellcheck-replacement',
        // Milliseconds to pass before SpellCheck is started when no editing occurs
        EDIT_IDLE_MILLISECONDS: 1000,
    },
    
    // =========================================================================
    
    targetLangCode: null,           // language to be checked
    isSupportedLanguage: null,      // if the language is supported by our tool(s)
    
    allMatchesOfTool: null,         // all matches as found by the tool
    allMatches: null,               // data of all matches found by the tool(s); here already stored independently from the tool
    allMatchesRanges: null,         // bookmarks of all ranges for the matches found by the tool(s); here already stored independently from the tool
    activeMatchNode: null,          // node of single match currently in use
    
    spellCheckResults: null,        // Store results for already checked Html-Content in the Editor: me.spellCheckResults[me.contentBeforeSpellCheckWithoutSpellCheckNodes] = content after SpellCheck (= with SpellCheck-Nodes)
    
    spellCheckTooltip: null,        // spellcheck tooltip instance
    
    editIdleTimer: null,            // time "nothing" is changed in the Editor's content; 1) user: presses no key 2) segmentsHtmleditor: no push, no afterInsertMarkup
    editIdleRestarted: null,        // has the content been changed in the Editor (= timer restarted) since the last timer has started the SpellCheck?
    
    isApplyingInProgress: false,    // flag to indicate if we are in the process of applying the matches that has been found
    
    isSpellCheckOnSaving: false,    // flag to indicate that the SpellChecker runs on saving the segment
    savedSegmentNrInTask: false,    // segmentNrInTask of the segment that started the SpellCheck on saving
    
    segmentId: null,                // ID of the currently edited Segment
    
    // before the SpellCheck-results are applied:
    // (1) we store content in the Editor (= must be the same at the moment of sending it to the SpellCheck and of applying the results!)
    editorBodyAtSpellCheckStart: null,
    editorBodyExtDomElementAtSpellCheckStart: null,
    contentBeforeSpellCheck: null,
    contentBeforeSpellCheckWithoutSpellCheckNodes: null,
    // (2) we bookmark the selection:
    selectionForCaret: null,
    bookmarkForCaret: null,
    
    // =========================================================================
    // Init
    // =========================================================================
    
    /**
     * 
     */
    init: function(){
        var me = this;
        this.callParent(arguments);
        me.consoleLog('0.1 init Editor.plugins.SpellCheck.controller.Editor');
    },
    onDestroy:function(){
        var me=this;
        if(me.spellCheckTooltip){
            me.spellCheckTooltip.destroy();
            me.spellCheckTooltip = null;
        }
        Ext.dom.GarbageCollector.collect();
        me.editor = null;
        me.consoleLog('----------------- SpellCheck: onDestroy FINISHED. ---------------');
    },
    /**
     * Init the SpellCheck-Plugin for the current task:
     * - Store task-specific properties (targetLangCode, isSupportedLanguage).
     */
    initSpellCheckPluginForTask: function() {
        var me = this;
        me.consoleLog('0.2a initSpellCheckPluginForTask');
        me.setTargetLangCode();
        me.setLanguageSupport();
        me.spellCheckResults = [];
    },
    /**
     * Init the SpellCheck-Plugin for the current Editor
     * (only if the language is supported by our own tools)
     */
    initSpellCheckPluginForEditor: function() {
        var me = this;
        if (me.isSupportedLanguage) {
            me.consoleLog('0.2b initSpellCheckPluginForEditor (' + me.targetLangCode + '/' + me.isSupportedLanguage + ')');
            me.initEditor();
            me.initSnapshotHistory();
            me.setBrowserSpellcheck();
            me.initTooltips();
            me.initKeyboardEvents();
            me.initMouseEvents();
            me.isSpellCheckOnSaving = false; // = "default" until we save the segment
        } else {
            me.consoleLog('0.2b SpellCheckPluginForEditor not initialized because language is not supported (' + me.targetLangCode + '/' + me.isSupportedLanguage + ').');
        }
    },
    /**
     * Init Editor
     */
    initEditor: function() {
        var me = this,
            plug = me.getSegmentGrid().editingPlugin,
            editor = plug.editor; // → this is the row editor component;
        me.consoleLog('initEditor');
        me.editor = editor.mainEditor; // → this is the HtmlEditor
        me.injectCSSForEditor();
    },
    /**
     * Init ToolTips
     */
    initTooltips:function(){
        var me = this;
        me.spellCheckTooltip = Ext.create('Ext.tip.ToolTip', {
            closable: true,
            renderTo: Ext.getBody(),
            target: me.getEditorBody(),
            targetIframe: me.editor.iframeEl,
            targetOffset: me.editor.iframeEl.getXY(),
            listeners: {
                beforeshow: function(tip) {
                    me.handleSpellCheckTooltip(tip);
                }
            }
        });
    },
    /**
     * Init Events
     */
    /**
     * Init the event and "Reset" everything for the new keydown-Event
     */
    initKeyDownEvent: function(event) {
        var me = this;
        me.consoleLog('initKeyDownEvent');
        // "reset" for Editor.util.Event:
        me.event = event;
        me.ignoreEvent = false;
        me.stopEvent = false;
    },
    initKeyboardEvents: function() {
        var me = this;
        Ext.get(me.editor.getDoc()).on('keydown', me.handleKeyDown, me, {priority: 9999, delegated: false});
        Ext.get(me.editor.getDoc()).on('keyup', me.handleKeyUp, me, {priority: 9999, delegated: false});
    },
    initMouseEvents: function() {
        var me = this,
            tooltipBody = Ext.getBody();
        
        me.getEditorBodyExtDomElement().on({
            contextmenu:{
                delegated: false,
                delegate: me.self.NODE_NAME_MATCH + '.' + me.self.CSS_CLASSNAME_MATCH,
                fn: me.showToolTip,
                scope: this,
                preventDefault: true
            }
        });
        
        tooltipBody.on({
            click:{
                delegated: false,
                delegate: 'a.' + me.self.CSS_CLASSNAME_REPLACEMENTLINK,
                fn: me.applyReplacement,
                scope: this,
                preventDefault: true
            }
        });
    },
    
    // =========================================================================
    // When do we run the SpellCheck?
    // =========================================================================
    
    /**
     * Everytime the user stops editing for a certain time (EDIT_IDLE_MILLISECONDS),
     * the SpellCheck ist started.
     */
    startTimerForSpellCheck: function() {
        var me = this;
        clearTimeout(me.editIdleTimer);
        me.editIdleRestarted = true;
        me.editIdleTimer = setTimeout(function(){
                me.editIdleRestarted = false;
                me.startSpellCheck();
            }, me.self.EDIT_IDLE_MILLISECONDS);
    },
    
    // =========================================================================
    // Additional handlers for events
    // =========================================================================
    
    /**
     * Handle KeyDown-Events of Editor.view.segments.HtmlEditor
     * @param {Object} event
     */
    handleKeyDown: function(event) {
        var me = this;
        me.initKeyDownEvent(event);
        if (!me.isSupportedLanguage) {
            me.consoleLog('SpellCheck: handleKeyDown failed because language is not supported or SpellCheck-Tool does not run.');
            return;
        }
        if (me.isApplyingInProgress) {
            Editor.MessageBox.addWarning(me.messages.isApplyingInProgress);
            return;
        }
        switch(true) {
            case me.eventIsCtrlZ():
                // Restore older snapshot...
                me.cleanupSnapshotHistory();
                me.rewindSnapshot();
                me.restoreSnapshotInEditor();
                // ... then stop everything
                me.ignoreEvent = true;
                me.stopEvent = true;
            break;
            case me.eventIsCtrlY():
                // Restore newer snapshot...
                me.fastforwardSnapshot();
                me.restoreSnapshotInEditor();
                // ... then stop everything
                me.ignoreEvent = true;
                me.stopEvent = true;
            break;
            default:
                me.startTimerForSpellCheck();
            break;
        }
        // Stop event?
        if(me.stopEvent) {
            event.stopEvent();
        }
    },
    /**
     * Handle KeyUp-Events of Editor.view.segments.HtmlEditor
     */
    handleKeyUp: function() {
        var me = this;
        if(me.ignoreEvent) {
            return;
        }
        // Keep a snapshot from the new content
        me.saveSnapshot();
    },
    /**
     * After each push etc.: When the content in the Editor gets updated, we save a snapshot and start the timer for running the next SpellCheck.
     */
    handleAfterContentUpdate: function() {
        var me = this;
        // Stop if we open a task (not a segment) or try to open a segment that is not editable
        if (me.getSegmentGrid().editingPlugin.context == undefined) {
            return;
        }
        // (1) New segment opened?
        me.consoleLog("segmentId: " + me.segmentId + "/" + me.getSegmentGrid().editingPlugin.context.record.get('id'));
        if (me.segmentId != me.getSegmentGrid().editingPlugin.context.record.get('id')) {
            // New segment opened? Then start a new snapshot-history.
            me.segmentId = me.getSegmentGrid().editingPlugin.context.record.get('id');
            me.initSpellCheckPluginForEditor();
            me.initSnapshotHistory();
        }
        // (1) keep a snapshot from the current content
        me.saveSnapshot();
        // (2) start Timer for SpellCheck
        me.startTimerForSpellCheck();
    },
    /**
     * When a segment is saved, the SpellChecker is activated.
     * - If there are errors (for the segment that might now be closed already), the user will get a popup-message.
     * - The SpellCheck-status of the segment in the grid is updated.
     * 
     * TODO: Should we run a check also if a segment is not saved, but closed?
     */
    handleSpellCheckOnSaving: function(segmentNrInTask) {
        var me = this,
            message;
        me.consoleLog('handleSpellCheckOnSaving...');
        if (me.isSpellCheckOnSaving && (me.savedSegmentNrInTask != null) && (me.savedSegmentNrInTask != segmentNrInTask) ){
            message = me.messages.SpellCheckOnSavingIsAlreadyRunningForAnotherSegment.replace(/%segmentnr/, me.savedSegmentNrInTask);
            Editor.MessageBox.addError(message);
            return;
        }
        me.savedSegmentNrInTask = segmentNrInTask;
        me.isSpellCheckOnSaving = true;
        me.startSpellCheck();
    },
    // =========================================================================
    // Start and finish (!) the SpellCheck.
    // =========================================================================
    
    /**
     * Prepare and run the SpellCheck (be sure to run this only for supported languages).
     */
    startSpellCheck: function() {
        var me = this,
            rangeForEditor = rangy.createRange(),
            editorBody,
            editorText;
        if (!me.isSupportedLanguage) {
            me.consoleLog('startSpellCheck failed because language is not supported or SpellCheck-Tool does not run.');
            return;
        }
        
        editorBody = me.getEditorBody();
        if(!me.editor || !editorBody) {
            me.consoleLog('startSpellCheck: initSpellCheckPluginForEditor first...');
            me.initSpellCheckPluginForEditor();
            editorBody = me.getEditorBody();
        }
        if(!editorBody) {
            me.consoleLog('startSpellCheck failed because editorBody is not found.');
            return;
        }
        
        me.consoleLog('(0.3 => startSpellCheck.)');
        
        // Here we store the content of the Editor for the SpellCheck. 
        // HTMLBodyElement and Ext.dom.Element must catch the very same status of the Editor, the content MUST NOT have changed from one to the other.
        // TODO: check if it's safe enough to get them this way:
        me.editorBodyAtSpellCheckStart = me.getEditorBody();                           // HTMLBodyElement
        me.editorBodyExtDomElementAtSpellCheckStart = me.getEditorBodyExtDomElement(); // Ext.dom.Element
        
        rangeForEditor.selectNode(me.editorBodyAtSpellCheckStart);
        me.contentBeforeSpellCheck = me.editorBodyExtDomElementAtSpellCheckStart.getHtml();

        me.cleanSpellCheckTags(); // in case a spellcheck has been run before already
        me.contentBeforeSpellCheckWithoutSpellCheckNodes = me.editorBodyExtDomElementAtSpellCheckStart.getHtml();
        
        // After this point you MUST finish the SpellCheck with finishSpellCheck() if the Editor is still opened then!!!
        
        // (1) where is the caret at the moment?
        me.selectionForCaret = rangy.getSelection(me.getEditorBody());
        me.bookmarkForCaret = me.selectionForCaret.getBookmark(me.getEditorBody());
        
        // If the text has not changed, we can use the result we have already fetched and applied.
        // (Doing "nothing" is not an option because then there won't be any SpellCheck-Nodes at all in the Editor.)
        if (!me.isSpellCheckOnSaving && (me.contentBeforeSpellCheckWithoutSpellCheckNodes in me.spellCheckResults) ) {
            me.getEditorBodyExtDomElement().setHtml(me.spellCheckResults[me.contentBeforeSpellCheckWithoutSpellCheckNodes]);
            me.consoleLog('startSpellCheck did not start a new check because we have already run a check for this content. We used the result we already have.');
            me.finishSpellCheck();
            return;
        }
        me.prepareDelNodeForSearch(true); // SearchReplaceUtils.js (add display none to all del nodes, with this they are ignored in rangeForEditor.text())
        editorText = rangeForEditor.text();
        me.runSpellCheck(editorText);
    },
    /**
     * Finish the SpellCheck.
     */
    finishSpellCheck: function() {
        var me = this;
        // (1) set the del nodes visible again
        me.prepareDelNodeForSearch(false); // SearchReplaceUtils.js
        // (2) set Editor to editable again
        me.editor.setReadOnly(false);
        me.consoleLog(' ------------------ Editor is editable again.');
        // (3) restore position of the caret (do this AFTER setReadOnly(false), otherwise there is no focus).
        if (me.bookmarkForCaret != null) {
            me.selectionForCaret.moveToBookmark(me.bookmarkForCaret);
            me.bookmarkForCaret = null;
        }
    },
    
    // =========================================================================
    // Work with the results that have been found by the tool.
    // =========================================================================
    
    /**
     * Apply the matches found by the SpellCheck:
     * - store data for all matches from tool
     * - apply results to Editor
     * @param {Array} matchesFromTool
     */
    applySpellCheck: function() {
        var me = this,
            message;
        if (me.isSpellCheckOnSaving) {
            me.consoleLog('applySpellCheck on isSpellCheckOnSaving...');
            if (me.allMatchesOfTool.length > 0) {
                message = me.messages.errorsFoundOnSaving.replace(/%segmentnr/, me.savedSegmentNrInTask);
                Editor.MessageBox.addWarning(message); // TODO: Should we show this message only if the errors are not the same as already known?
            }
            
            // TODO step2: update segment-status in grid
            
            me.isSpellCheckOnSaving = false; // = reset to "default"
            me.onDestroy();
            return;
        }
        if (me.editIdleRestarted) {
            me.consoleLog('applySpellCheck not started: results might be invalid after the content was edited in the meantime.');
            me.finishSpellCheck();
            return;
        }
        
        if (me.getEditorBodyExtDomElement() == null) {
            me.consoleLog('applySpellCheck not started: no editor-body found (maybe the editor is closed already).');
            return;
        }
        
        // (1) where is the caret at the moment?
        me.selectionForCaret = rangy.getSelection(me.getEditorBody());
        me.bookmarkForCaret = me.selectionForCaret.getBookmark(me.getEditorBody());
        
        if (me.allMatchesOfTool.length > 0) {
            me.storeAllMatchesFromTool();
            // while we apply the matches, the content must not be edited in the Editor => set the flag:
            me.isApplyingInProgress = true;
                // (2) set Editor to readonly, otherwise things get messy.
                me.consoleLog('Editor set to ReadOnly ------------------');
                me.editor.setReadOnly(true);
                // (3) apply matches
                me.applyAllMatches();
            me.isApplyingInProgress = false;
        } else {
            me.consoleLog('allMatchesOfTool: no results (not checked or nothing found).');
        }
        
        me.finishSpellCheck();
    },
    /**
     * Apply replacement as suggested in the ToolTip.
     */
    applyReplacement: function(event) {
        var me = this,
            rangeForMatch = rangy.createRange(),
            replaceText = event.currentTarget.innerText,
            range;
        
        me.selectionForCaret = rangy.getSelection(me.getEditorBody());
        me.bookmarkForCaret = me.selectionForCaret.getBookmark(me.getEditorBody());
        
        rangeForMatch.selectNodeContents(me.activeMatchNode);
        range = rangeForMatch.getBookmark();
        
        me.isActiveTrackChanges();             // SearchReplace.js
        if(!me.activeTrackChanges){            // SearchReplace.js
            me.pureReplace(range,replaceText); // SearchReplace.js
        } else {
            me.setTrackChangesInternalSpellCheckFlag(true);
            me.fireEvent('deleteAndReplace',
                 range,
                 replaceText
            );
            me.setTrackChangesInternalSpellCheckFlag(false);
        }
        
        me.spellCheckTooltip.hide();
        
        me.saveSnapshot();
        
        // new DOM after replacement => find and apply the matches again:
        me.startSpellCheck();
    },

    // =========================================================================
    // SpellCheck: generic layer for integrating specific tools
    // =========================================================================
    
    /**
     * Is the language supported by the tool(s) we use?
     * The tool's specific code shall:
     * - store the result in me.isSupportedLanguage
     */
    setLanguageSupport: function() {
        var me = this;
        me.setLanguageSupportWithTool();
    },
    /**
     * Run the SpellCheck for the given text.
     * The tool's specific code shall: 
     * - call applySpellCheck()
     * @param {String} textToCheck
     */
    runSpellCheck: function(textToCheck) {
        var me = this;
        if(textToCheck != "") {
            console.log("textToCheck: " + textToCheck);
            me.runSpellCheckWithTool(textToCheck);
        } else {
            me.finishSpellCheck();
        }
    },
    /**
     * Store data for all matches found by the tool (=> then accessable independent from tool).
     */
    storeAllMatchesFromTool: function() {
        var me = this,
            singleMatchObject;
        me.allMatches = [];
        Ext.Array.each(me.allMatchesOfTool, function(match, index) {
            singleMatchObject = {
                    matchIndex        : index,                                      // Integer
                    range             : me.getRangeForMatchFromTool(match),         // Rangy bookmark
                    message           : me.getMessageForMatchFromTool(match),       // String
                    replacements      : me.getReplacementsForMatchFromTool(match),  // Array
                    infoURLs          : me.getInfoURLsForMatchFromTool(match),      // Array
                    cssClassErrorType : me.getCSSForMatchFromTool(match)            // String
            };
            me.allMatches[index] = singleMatchObject;
        });
    },
    
    // =========================================================================
    // Helpers for the SpellChecker
    // =========================================================================

    /**
     * Apply results to captured content from the Editor.
     */
    applyAllMatches: function() {
        var me = this,
            editorBody = me.getEditorBody(),
            rangeForMatch,
            documentFragmentForMatch,
            spellCheckNode,
            contentWithSpellCheckResults;
        // before we start, the content in Editor MUST be the very same content that we have been checking
        me.getEditorBodyExtDomElement().setHtml(me.contentBeforeSpellCheck);
        // apply the matches (iterate in reverse order; otherwise the ranges get lost due to DOM-changes "in front of them")
        rangeForMatch = rangy.createRange(editorBody);
        Ext.Array.each(me.allMatches, function(match, index) {
            rangeForMatch.moveToBookmark(match.range);
            documentFragmentForMatch = rangeForMatch.extractContents();
            spellCheckNode = me.createSpellcheckNode(index);
            spellCheckNode.appendChild(documentFragmentForMatch);
            rangeForMatch.insertNode(spellCheckNode);
        }, me, true);
        // store the result of the SpellCheck for the html WITHOUT the SpellCheck-Nodes
        // for checking later if we need to run the check again
        if (me.contentBeforeSpellCheckWithoutSpellCheckNodes in me.spellCheckResults) {
            return;
        }
        contentWithSpellCheckResults = me.getEditorBodyExtDomElement().getHtml();
        me.spellCheckResults[me.contentBeforeSpellCheckWithoutSpellCheckNodes] = contentWithSpellCheckResults;
    },
    /**
     * Create and return a new node for SpellCheck-Match of the given index.
     * For match-specific data, get the data from the tool.
     * By storing this data as attributes we  
     * @param {Integer} index
     * @returns {Object}
     */
    createSpellcheckNode: function(index){
        var me = this,
            match = me.allMatches[index],
            nodeElParams = { tag: me.self.NODE_NAME_MATCH };
        // CSS-class(es)
        nodeElParams['cls'] = me.self.CSS_CLASSNAME_MATCH + ' ' + match.cssClassErrorType;
        // activeMatchIndex
        nodeElParams[me.self.ATTRIBUTE_ACTIVEMATCHINDEX] = index;
        // create and return node
        return Ext.DomHelper.createDom(nodeElParams);
    },
    /**
     * Fire event so the internal flag isSearchReplaceRangeFromSpellCheck in trackchanges class is set
     * That is used to enable/disable some of the trackchanges functionality needed for the search and replace
     * @param {Boolean}
     */
    setTrackChangesInternalSpellCheckFlag:function(isSpellCheckRange){
        this.fireEvent('isSearchReplaceRangeFromSpellCheck',isSpellCheckRange);
    },
    
    // =========================================================================
    // Helpers for the Editor and Task
    // =========================================================================
    /***
     * Set targetLangCode for the current task.
     * @returns {String}
     */
    setTargetLangCode: function(){
        var me = this,
            task = Editor.data.task,
            languages = Ext.getStore('admin.Languages'),
            targetLang = languages.getById(task.get('targetLang'));
        me.targetLangCode = targetLang.get('rfc5646');
    },
    /**
     * Disable the browser's SpellChecker?
     */
    setBrowserSpellcheck: function(){
        var me = this,
            editorBody = me.getEditorBody();
        editorBody.spellcheck = !me.isSupportedLanguage;
        me.consoleLog('(browserSpellcheck is set to:' + editorBody.spellcheck + ')');
    },
    /**
     * Inject CSS into the Editor
     */
    injectCSSForEditor: function() {
        var me = this;
        Ext.util.CSS.createStyleSheetToWindow(
                me.getEditorDoc(),
                '.'+me.self.CSS_CLASSNAME_MATCH+' {cursor: pointer;}' +
                '.'+me.self.CSS_CLASSNAME_MATCH+' {border-bottom: 3px dotted; border-color: red;}' + // TODO: use wavy line instead
                '.'+me.self.CSS_CLASSNAME_MATCH+'.'+me.self.CSS_CLASSNAME_GRAMMERERROR+' {border-color: #ab8906;}' +    // dark yellow
                '.'+me.self.CSS_CLASSNAME_MATCH+'.'+me.self.CSS_CLASSNAME_SUGGESTION+' {border-color: #458fe6;}' +      // blue
                '.'+me.self.CSS_CLASSNAME_MATCH+'.'+me.self.CSS_CLASSNAME_SPELLERROR+' {border-color: #e645a8;}'        // red-violet
            );
    },
    
    // =========================================================================
    // ToolTips
    // =========================================================================
    
    /***
     * Tooltips for the spellcheck-matches.
     */
    handleSpellCheckTooltip: function(tip) {
        var me = this,
            spellCheckData = me.getSpellCheckData(),
            tplData = {
                spellCheck: spellCheckData
            };
        if(!me.spellCheckTpl) {
            me.spellCheckTpl = new Ext.Template('{spellCheck}');
            me.spellCheckTpl.compile();
        }
        tip.update(me.spellCheckTpl.apply(tplData));
    },
    getSpellCheckData: function() {
        var me = this,
            activeMatchIndex = me.activeMatchNode.getAttribute(me.self.ATTRIBUTE_ACTIVEMATCHINDEX),
            activeMatch = me.allMatches[activeMatchIndex],
            message      = activeMatch.message,
            replacements = activeMatch.replacements,
            infoURLs     = activeMatch.infoURLs,
            nodeData = '';
        // message
        nodeData += '<b>'+ message + '</b><br />';
        // replacements
        if (replacements.length > 0) {
            nodeData += '<hr>'
            Ext.Array.each(replacements, function(replacement, index) {
                nodeData += '<a href="#" class="' + me.self.CSS_CLASSNAME_REPLACEMENTLINK + '">' + replacement + '</a><br />';
            });
        }
        // infoURLs
        if (infoURLs.length > 0) {
            nodeData += '<hr>'
            Ext.Array.each(infoURLs, function(url, index) {
                nodeData += '<a href="' + url + '" target="_blank">' + me.messages.moreInformation + '</a><br />';
            });
        }
        return nodeData;
    },
    showToolTip: function(event) {
        var me = this;
        me.activeMatchNode = event.currentTarget;
        me.spellCheckTooltip.hide();
        me.spellCheckTooltip.show();
    },
    
    // =========================================================================
    // TODO: merge this with some of SearchReplaceUtils
    // =========================================================================
    
    /***
     * Remove SpellCheck-Tags from the content we found in the editor but keep their content.
     */
    cleanSpellCheckTags:function(){
        var me = this,
            allSpellCheckElements,
            spellCheckElementParentNode;
        if(!me.editorBodyExtDomElementAtSpellCheckStart){
            me.consoleLog('cleanSpellCheckTags failed with missing me.editorBodyExtDomElementAtSpellCheckStart.');
            return false;
        }
        // find all spellcheck-elements and "remove their tags"
        allSpellCheckElements = me.editorBodyExtDomElementAtSpellCheckStart.query('.' + me.self.CSS_CLASSNAME_MATCH);
        Ext.Array.each(allSpellCheckElements, function(spellCheckEl, index) {
            spellCheckElementParentNode = spellCheckEl.parentNode;
            while(spellCheckEl.firstChild) {
                spellCheckElementParentNode.insertBefore(spellCheckEl.firstChild, spellCheckEl);
            }
            spellCheckElementParentNode.removeChild(spellCheckEl);
            spellCheckElementParentNode.normalize();
        });
    },
});
