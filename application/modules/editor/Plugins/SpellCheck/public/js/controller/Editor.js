
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
    spellCheckMessages: {
        moreInformation: '#UT#More information',
        errorsFoundOnSaving: '#UT#SpellCheck: errors found on saving Segment Nr. %segmentnr.',
        tagsBetweenWhitespaces: '#UT#There are tags between multiple whitespaces.',
        isApplyingInProgress: '#UT#The SpellCheck-Plugin was currently applying the matches, sorry for the inconvenience.',
        spellCheckOnSavingIsAlreadyRunningForAnotherSegment: '#UT#The SpellCheck on saving the segment failed because there is already another process running for Segment Nr. %segmentnr.',
        spellCheckStopped: '#UT#The SpellChecks has been stopped.'
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
        CSS_CLASSNAME_TOOLTIP_HEADER:  'spellcheck-tooltip-header',
        CSS_CLASSNAME_REPLACEMENTLINK:  'spellcheck-replacement',
        CSS_CLASSNAME_TOOLTIP_MOREINFO:  'spellcheck-tooltip-moreinformation',
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
    
    spellCheckTooltip: null,        // Ext.menu.Menu ("ToolTip"-instance)
    
    editIdleTimer: null,            // time "nothing" is changed in the Editor's content; 1) user: presses no key 2) segmentsHtmleditor: no push, no afterInsertMarkup
    editIdleRestarted: null,        // has the content been changed in the Editor (= timer restarted) since the last timer has started the SpellCheck?
    
    isApplyingInProgress: false,    // flag to indicate if we are in the process of applying the matches that has been found
    
    isSpellCheckOnSaving: false,    // flag to indicate that the SpellChecker runs on saving the segment
    savedSegmentNrInTask: false,    // segmentNrInTask of the segment that started the SpellCheck on saving
    
    segmentId: null,                // ID of the currently edited Segment
    
    // before the SpellCheck-results are applied, we store caret and content from the Editor:
    selectionForCaret: null,
    bookmarkForCaret: null,
    contentBeforeSpellCheck: null,
    contentBeforeSpellCheckWithoutSpellCheckNodes: null,
    
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
            me.consoleLog('0.2b SpellCheckPluginForEditor not initialized because language is not supported (' + me.targetLangCode + '/' + me.isSupportedLanguage + ') or SpellCheck-Tool does not run.');
        }
    },
    /**
     * Init Editor
     */
    initEditor: function() {
        var me = this,
            plug = me.getSegmentGrid().editingPlugin,
            editor = plug.editor; // → this is the row editor component;
        me.consoleLog('initEditor (SpellCheck)');
        me.editor = editor.mainEditor; // → this is the HtmlEditor
        me.injectCSSForEditor();
    },
    /**
     * Init ToolTips
     */
    initTooltips:function(){
        var me = this;
        me.spellCheckTooltip = Ext.create('Ext.menu.Menu', {
            minWidth: 200,
            plain: true,
            renderTo: Ext.getBody(),
            items: [],
            listeners: {
                beforeshow: function() {
                    me.handleSpellCheckTooltip();
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
        // "reset" for Editor.util.Event:
        me.event = event;
        me.ignoreEvent = false;
        me.stopEvent = false;
    },
    initKeyboardEvents: function() {
        var me = this;
        Ext.get(me.editor.getDoc()).on('keydown', me.handleKeyDown, me, {priority: 9980, delegated: false});
        Ext.get(me.editor.getDoc()).on('keyup', me.handleKeyUp, me, {priority: 9980, delegated: false});
    },
    initMouseEvents: function() {
        var me = this,
            editorDoc = Ext.get(me.editor.getDoc()),
            tooltipBody = Ext.getBody();
        
        editorDoc.on({
            click:{
                delegated: false,
                fn: me.handleClickInEditor,
                scope: this,
                preventDefault: true
            }
        });
        
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
                delegate: 'div.' + me.self.CSS_CLASSNAME_REPLACEMENTLINK,
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
     * the SpellCheck is started.
     */
    startTimerForSpellCheck: function() {
        var me = this;
        clearTimeout(me.editIdleTimer);
        me.editIdleRestarted = true;
        me.editIdleTimer = setTimeout(function(){
                me.consoleLog('startTimerForSpellCheck (' + Ext.Date.format(new Date(), 'c') + ')');
                me.editIdleRestarted = false;
                me.startSpellCheck();
            }, me.self.EDIT_IDLE_MILLISECONDS);
    },
    
    // =========================================================================
    // Additional handlers for events
    // =========================================================================
    
    handleClickInEditor: function(event) {
        var me = this;
        me.editIdleTimer = null;
    },
    /**
     * Handle KeyDown-Events of Editor.view.segments.HtmlEditor
     * @param {Object} event
     */
    handleKeyDown: function(event) {
        var me = this;
        me.initKeyDownEvent(event);
        if(me.eventHasToBeIgnored()){
            me.consoleLog(" => Ignored for SpellCheck.");
            me.ignoreEvent = true;
            me.stopEvent = false;
        }
        if(me.eventHasToBeIgnoredAndStopped()){
            me.consoleLog(" => Ignored for SpellCheck and stopped.");
            me.ignoreEvent = true;
            me.stopEvent = true;
        }
        
        if(!me.ignoreEvent) {
            if (!me.isSupportedLanguage) {
                me.consoleLog('SpellCheck: handleKeyDown failed because language is not supported or SpellCheck-Tool does not run.');
                return;
            }
            if (me.isApplyingInProgress) {
                Editor.MessageBox.addWarning(me.spellCheckMessages.isApplyingInProgress);
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
            }
            me.startTimerForSpellCheck();
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
            message = me.spellCheckMessages.SpellCheckOnSavingIsAlreadyRunningForAnotherSegment.replace(/%segmentnr/, me.savedSegmentNrInTask);
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
            editorText;
        
        if (!me.isSupportedLanguage) {
            me.consoleLog('startSpellCheck failed because language is not supported or SpellCheck-Tool does not run.');
            return;
        }
        
        if(!me.getEditorBody()) {
            me.consoleLog('startSpellCheck: initSpellCheckPluginForEditor first...');
            me.initSpellCheckPluginForEditor();
        }
        
        // "ignore" multiple whitespaces, because we delete them anyway on save.
        // Exception: Show the error message if there are tags between the multiple whitespaces.
        if(me.removeMultipleWhitespaceInEditor()) {
            Editor.MessageBox.addError(me.spellCheckMessages.tagsBetweenWhitespaces + ' ' + me.spellCheckMessages.spellCheckStopped);
            return;
        }
        
        me.consoleLog('(0.3 => startSpellCheck.)');
        me.allMatches = null;
        me.allMatchesRanges = null;
        
        // where is the caret at the moment?
        me.selectionForCaret = rangy.getSelection(me.getEditorBody());
        me.bookmarkForCaret = me.selectionForCaret.getBookmark();
        
        // store content WITH SpellCheck-Markup
        me.contentBeforeSpellCheck = me.getEditorBodyExtDomElement().getHtml();
        // store content WITHOUT SpellCheck-Markup
        me.cleanSpanMarkupInEditor(); // in case a spellcheck has been run before already
        me.contentBeforeSpellCheckWithoutSpellCheckNodes = me.getEditorBodyExtDomElement().getHtml();
        
        // If the text has not changed, we can use the result we have already fetched and applied.
        // (Doing "nothing" is not an option because then there won't be any SpellCheck-Nodes at all in the Editor.)
        if (!me.isSpellCheckOnSaving && (me.contentBeforeSpellCheckWithoutSpellCheckNodes in me.spellCheckResults) ) {
            me.consoleLog('startSpellCheck did not start a new check because we have already run a check for this content.');
            me.applySpellCheckResult(me.spellCheckResults[me.contentBeforeSpellCheckWithoutSpellCheckNodes]);
            return;
        }
        
        editorText = me.getEditorContentAsText(false);
        console.log("editorText: " + editorText);
        me.runSpellCheck(editorText);
        // => runSpellCheck with the tool calls applySpellCheck() when the results arrive.
    },
    /**
     * What to do after the SpellCheck has been run.
     */
    finishSpellCheck: function() {
        var me = this;
        
        // restore position of the caret ( do this AFTER setReadOnly(false), otherwise there is no focus).
        if (me.bookmarkForCaret != null) {
            me.selectionForCaret.moveToBookmark(me.bookmarkForCaret);
        }
        
        // "reset"
        me.bookmarkForCaret = null;
        me.contentBeforeSpellCheck = null;
        me.contentBeforeSpellCheckWithoutSpellCheckNodes = null;
    },
    
    // =========================================================================
    // Work with the results that have been found by the tool.
    // =========================================================================
    
    /**
     * Apply the matches found by the SpellCheck (store them and apply the result to the Editor).
     */
    applySpellCheck: function() {
        var me = this,
            message;
        
        if (me.isSpellCheckOnSaving) {
            me.consoleLog('applySpellCheck on isSpellCheckOnSaving...');
            if (me.allMatchesOfTool.length > 0) {
                message = me.spellCheckMessages.errorsFoundOnSaving.replace(/%segmentnr/, me.savedSegmentNrInTask);
                Editor.MessageBox.addWarning(message); // TODO: Should we show this message only if the errors are not the same as already known?
            }
            
            // TODO step2: update segment-status in grid
            
            me.isSpellCheckOnSaving = false; // = reset to "default"
            me.onDestroy();
            return;
        }
        
        if (!me.allMatchesOfTool.length > 0) {
            me.consoleLog('allMatchesOfTool: no results.');
            me.bookmarkForCaret = null;
            me.finishSpellCheck();
            return;
        }
        
        me.storeAllMatchesFromTool();
        me.applySpellCheckResult();
    },
    /**
     * Apply the results.
     * @param {String} resultFromFormerCheck (optional)
     */
    applySpellCheckResult: function(resultFromFormerCheck) {
        var me = this;
        if (me.editIdleRestarted) {
            me.consoleLog('applySpellCheck not started: results might be invalid after the content was edited in the meantime.');
            me.finishSpellCheck();
            return;
        }
        if (me.getEditorBodyExtDomElement() == null) {
            me.consoleLog('applySpellCheck not started: no editor-body found (maybe the editor is closed already).');
            me.finishSpellCheck();
            return;
        }
        
        // ------------- while we apply the matches, the content must not be edited in the Editor ------------------
        me.isApplyingInProgress = true;
        me.consoleLog('Set Editor to ReadOnly ------------------');
        me.editor.setReadOnly(true);
                
            if (resultFromFormerCheck != undefined) {
                me.bookmarkForCaret = me.selectionForCaret.getBookmark();
                me.getEditorBodyExtDomElement().setHtml(resultFromFormerCheck);
                me.consoleLog('resultFromFormerCheck applied.');
            } else if (me.allMatchesOfTool.length > 0) {
                me.applyAllMatches();
                me.consoleLog('allMatches applied.');
            }
            
        me.editor.setReadOnly(false);
        me.consoleLog(' ------------------ Editor is editable again.');
        me.isApplyingInProgress = false;
        // ---------------------------------------------------------------------------------------------------------
        
        me.finishSpellCheck();
    },
    /**
     * Apply replacement as suggested in the ToolTip.
     */
    applyReplacement: function(event) {
        var me = this,
            rangeForMatch = rangy.createRange(),
            rangeForMatchBookmark,
            replaceText,
            bookmarkForCaretOnReplacement;
        
        bookmarkForCaretOnReplacement = me.selectionForCaret.getBookmark();
        
        // Find and bookmark the range that belongs to the SpellCheck-Node for the current ToolTip.
        rangeForMatch.selectNodeContents(me.activeMatchNode);
        rangeForMatchBookmark = rangeForMatch.getBookmark();
        
        // Remove SpellCheck- and TermTag-Markup.
        me.cleanSpanMarkupInEditor();
        
        // Update the range (the SpellCheck-Node is no longer in the DOM!...).
        rangeForMatch.moveToBookmark(rangeForMatchBookmark);
        rangeForMatchBookmark = rangeForMatch.getBookmark();
        
        // Replacement
        replaceText = Ext.get(event.currentTarget).query('a:first-child')[0].innerText;
        
        me.isActiveTrackChanges();                             // SearchReplace.js
        if(!me.activeTrackChanges){                            // SearchReplace.js
            me.pureReplace(rangeForMatchBookmark,replaceText); // SearchReplace.js
        } else {
            me.setTrackChangesInternalSpellCheckFlag(true);
            me.fireEvent('deleteAndReplace',
                 rangeForMatchBookmark,
                 replaceText
            );
            me.setTrackChangesInternalSpellCheckFlag(false);
        }
        
        me.consoleLog('replaceText (' + replaceText + ') applied.');
        
        me.spellCheckTooltip.hide();
        
        me.removeMultipleWhitespaceInEditor();
        
        me.saveSnapshot();
        
        // new DOM after replacement => find and apply the matches again:
        me.startSpellCheck();
        
        me.selectionForCaret.moveToBookmark(bookmarkForCaretOnReplacement); // TODO: does not land right if the replacement has not the same length as what was replaced
        me.getEditorBody().focus();
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
            spellCheckNode;
        // apply the matches (iterate in reverse order; otherwise the ranges get lost due to DOM-changes "in front of them")
        rangeForMatch = rangy.createRange(editorBody);
        Ext.Array.each(me.allMatches, function(match, index) {
            rangeForMatch.moveToBookmark(match.range);
            documentFragmentForMatch = rangeForMatch.extractContents();
            spellCheckNode = me.createSpellcheckNode(index);
            spellCheckNode.appendChild(documentFragmentForMatch);
            rangeForMatch.insertNode(spellCheckNode);
        }, me, true);
        me.cleanUpNode(editorBody);
        // store the result of the SpellCheck for the html WITHOUT the SpellCheck-Nodes
        // for checking later if we need to run the check again at all
        if (me.contentBeforeSpellCheckWithoutSpellCheckNodes in me.spellCheckResults) {
            return;
        }
        me.spellCheckResults[me.contentBeforeSpellCheckWithoutSpellCheckNodes] = me.getEditorBodyExtDomElement().getHtml();
    },
    /**
     * Create and return a new node for SpellCheck-Match of the given index.
     * For match-specific data, get the data from the tool.
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
    handleSpellCheckTooltip: function() {
        var me = this,
            oldMenuItems;
        // remove formerly added menu-items
        oldMenuItems = me.spellCheckTooltip.items.items;
        Ext.Array.each(oldMenuItems, function(itemToRemove, index) {
            me.spellCheckTooltip.remove(itemToRemove);
        },me, true);
        // update Tooltip
        me.spellCheckTooltip.add(me.getSpellCheckData());
    },
    getSpellCheckData: function() {
        var me = this,
            activeMatchIndex = me.activeMatchNode.getAttribute(me.self.ATTRIBUTE_ACTIVEMATCHINDEX),
            activeMatch = me.allMatches[activeMatchIndex],
            message      = activeMatch.message,
            replacements = activeMatch.replacements,
            infoURLs     = activeMatch.infoURLs,
            items = [];
        // message
        items.push({text: '<b>'+message+'</b>',
                    cls: me.self.CSS_CLASSNAME_TOOLTIP_HEADER });
        // replacement(s)
        if (replacements.length > 0) {
            Ext.Array.each(replacements, function(replacement, index) {
                items.push({text: replacement.replace(' ', '&nbsp;'), // quick and dirty workaround for empty spaces (e.g. when "  " should be replaced with " " or when " - " should be replaced with " – ")
                            cls: me.self.CSS_CLASSNAME_REPLACEMENTLINK });
            });
        }
        // infoURL(s)
        if (infoURLs.length > 0) {
            Ext.Array.each(infoURLs, function(url, index) {
                items.push({text: me.spellCheckMessages.moreInformation,
                            cls: me.self.CSS_CLASSNAME_TOOLTIP_MOREINFO,
                            href: url,
                            hrefTarget: '_blank'});
            });
        }
        return items;
    },
    showToolTip: function(event) {
        var me = this,
            posX = event.getX() + me.editor.iframeEl.getX()
            posY = event.getY() + me.editor.iframeEl.getY();
        me.activeMatchNode = event.currentTarget;
        me.spellCheckTooltip.hide();
        me.spellCheckTooltip.showAt(posX,posY);
    }
});
