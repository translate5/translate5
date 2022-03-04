
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
 * @class Editor.plugins.SpellCheck.controller.Editor
 * @extends Ext.app.Controller
 */
Ext.define('Editor.plugins.SpellCheck.controller.Editor', {
    extend: 'Ext.app.Controller',
    requires: ['Editor.util.SegmentContent'],
    mixins: ['Editor.util.DevelopmentTools',
             'Editor.util.Event',
             'Editor.util.Range',
             'Editor.util.SegmentEditor',
             'Editor.plugins.SpellCheck.controller.UtilLanguageTool',
             'Editor.controller.SearchReplace',
             'Editor.util.SearchReplaceUtils'],
    refs:[{
        ref: 'segmentGrid',
        selector:'#segmentgrid'
    },{
        ref: 'editorPanel',
        selector:'#SpellCheckEditorPanel'
    },{
        ref: 'concordenceSourceSearch',
        selector:'languageResourceSearchGrid #sourceSearch'
    }],
    listen: {
        // When opening a task (all at once, in this order):
        // - (1) #Editor.$application     editorViewportOpened
        // - (2) segmentsHtmleditor       push
        // - (3) segmentsHtmleditor       initialize
        // When opening a segment:
        // - (1) segmentsHtmleditor       push
        // When closing a task:
        // - (1) #Editor.$application     editorViewportClosed
        controller: {
            '#Editor': {
                beforeKeyMapUsage: 'handleEditorKeyMapUsage'
            },
            '#Editor.$application': {
                // editorViewportOpened: 'initSpellCheckPlugin' // NOW VIA #segmentStatusStrip afterRender (= comes first and needs this infos already)
                editorViewportClosed: 'onDestroy',
                editorConfigLoaded:'onEditorConfigLoaded'
            }
        },
        component: {
            'segmentsHtmleditor': {
                initialize: 'initEditor',
                push: 'handleAfterContentUpdate'
            },
            '#segmentStatusStrip': {
                afterRender: 'initTargetLang'
            },
            '#segmentStatusStrip #btnRunSpellCheck': {
                click: 'startSpellCheckViaButton'
            }
        },
    },
    spellCheckMessages: {
        moreInformation: 'Mehr Informationen'
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
    isSupportedLanguage: undefined, // if the language is supported by our tool(s):
                                    // - initially: undefined
                                    // - on task is opened => start setLanguageSupport() => result: true or false
                                    // - on push: when isSupportedLanguage is still undefined => start setLanguageSupport() => result: true or false
    
    allMatchesOfTool: null,         // all matches as found by the tool
    allMatches: null,               // data of all matches found by the tool(s); here already stored independently from the tool
    allMatchesRanges: null,         // bookmarks of all ranges for the matches found by the tool(s); here already stored independently from the tool
    activeMatchNode: null,          // node of single match currently in use
    
    spellCheckTooltip: null,        // Ext.menu.Menu ("ToolTip"-instance)
    
    editIdleTimer: null,            // time since "nothing" is changed in the Editor's content; 1) user: presses no key 2) segmentsHtmleditor: no push, no afterInsertMarkup
    
    spellCheckInProgressID: false,  // id of the currently valid SpellCheck-Process (false if none is running)
    // TODO: Instead of using IDs for the processes it would be better to use an object for each process 
    // (= handle the SpellCheck-Processes via a class with each process as an instance from it!).
    
    segmentId: null,                // ID of the currently edited Segment
    
    bookmarkForCaret: null,         // position of the caret in the Editor
    
    // =========================================================================
    
    // TRANSLATE-1630 "Workaround for east asian problems with spellchecker" 
    languagesToStopIdle: ['ja','ko','zh'],  // target-languages that cause problems when using SpellCheck via keyboard-idle
    disableSpellCheckByIdle: null,          // = use button instead of idle (will be set according to the target-language)
    
    // =========================================================================
    // Init
    // =========================================================================
    
    init: function(){
        var me = this;
        this.callParent(arguments);
        me.consoleLog('0.1 init Editor.plugins.SpellCheck.controller.Editor');
    },
    
    /***
     * After task config load event handler.
     */
    onEditorConfigLoaded:function(app, task){
        var me=this,
            isPluginActive = app.getTaskConfig('plugins.SpellCheck.active');
        me.setActive(isPluginActive);
    },
    
    onDestroy:function(){
        var me=this;
        if(me.spellCheckTooltip){
            me.spellCheckTooltip.destroy();
            me.spellCheckTooltip = null;
        }
        Ext.dom.GarbageCollector.collect();
        me.editor = null;
        me.terminateSpellCheck();
        me.consoleLog('---------------- SpellCheck: onDestroy FINISHED. -------------');
    },
    /**
     * Init basics according to the task's target-language: 
     * - what's the target-language?
     * - do we use keyboard-idle or a statusStrip-button to start a SpellCheck?
     */
    initTargetLang: function(statusStrip) {
        var me = this;
        me.consoleLog('0.2 SpellCheck: initTargetLang.');
        me.setTargetLangCode();
        me.setDisableSpellCheckByIdle(statusStrip);
    },
    /**
     * Initialize Editor in general and language-support.
     */
    initEditor: function(editor) {
        var me = this;
        me.consoleLog('0.3 SpellCheck: initEditor.');
        me.editor = editor;
        me.setLanguageSupport();
    },
    /**
     * SpellCheck-specific for Editor
     */
    initSpellCheckInEditor: function() {
        var me = this;
        me.consoleLog('0.4 SpellCheck: initSpellCheckInEditor.');
        me.initTooltips();
        me.initEvents();
        me.setBrowserSpellcheck();
        me.setSnapshotHistory();
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
     * Init (+ "Reset") everything for the new keydown-Event
     */
    initKeyDownEvent: function(event) {
        var me = this;
        // "reset" for Editor.util.Event:
        me.event = event;
        me.ignoreEvent = false;
        me.stopEvent = false;
    },
    /**
     * Init Events
     */
    initEvents: function() {
        var me = this,
            docEl = Ext.get(me.editor.getDoc()),
            tooltipBody = Ext.getBody();
        me.consoleLog('SpellCheck: initEvents...');
        
        if (!me.disableSpellCheckByIdle) {
            docEl.on({
                keyup:{
                    delegated: false,
                    priority: 9980,
                    fn: me.handleKeyUp,
                    scope: this,
                    preventDefault: false
                }
            });
        }
        
        me.editor.on({
            afterInsertMarkup:{
                delegated: false,
                fn: me.handleAfterContentUpdate,
                scope: this,
                preventDefault: true,
                options: {priority: -800}   // for fireEvent('afterInsertMarkup'): SpellCheck must run after(!!!) TrackChanges: 
                                            // 1. TrackChanges needs the given range before SpellCheck changes it
                                            // 2. SpellCheck will start an Ajax-call
            }
        });
        
        docEl.on({
            click:{
                delegated: false,
                fn: me.handleClickInEditor,
                scope: this,
                preventDefault: false
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
    /**
     * Adds a keyboard shortcut for starting the SpellCheck
     */
    handleEditorKeyMapUsage: function(cont, area) {
        var me = this;
        if (area  === 'editor') {
            //same shortcut in micros%ft Word
            cont.keyMapConfig['F7'] = [Ext.event.Event.F7, {ctrl: false, alt: false}, me.startSpellCheckViaShortcut, true, me];
        }
    },
    /**
     * Adds a "Run SpellCheck"-Button to the StatusStrip.
     * @param {Object} statusStrip
     */
    addSpellCheckButton: function (statusStrip) {
        statusStrip.add({
            xtype: 'button',
            cls: 'spellcheck-icon',
            text: 'SpellCheck',
            itemId: 'btnRunSpellCheck',
            tooltip: 'F7'
        });
    },
    
    // =========================================================================
    // When do we run the SpellCheck?
    // =========================================================================
    
    /**
     * Start SpellCheck after idle (= when the user stopped editing for a certain time, see EDIT_IDLE_MILLISECONDS).
     */
    startSpellCheckViaTimer: function() {
        var me = this;
        if (!me.isSupportedLanguage) {
            me.consoleLog('startSpellCheckViaTimer not started because language is not supported or SpellCheck-Tool does not run.');
            return;
        }
        if (me.disableSpellCheckByIdle) {
            me.consoleLog('startSpellCheckViaTimer not started because disableSpellCheckByIdle is true.');
            return;
        }
        // "reset" if a timer is already running
        clearTimeout(me.editIdleTimer);
        me.editIdleTimer = null;
        // if a spellcheck is already running
        me.spellCheckInProgressID = false;
        // start new timer
        me.consoleLog('(' + me.editIdleTimer + ') startSpellCheckViaTimer (' + Ext.Date.format(new Date(), 'c') + ')');
        me.editIdleTimer = setTimeout(function(){
                me.consoleLog('(' + me.editIdleTimer + ') setTimeout => will startSpellCheck now (' + Ext.Date.format(new Date(), 'c') + ')');
                me.startSpellCheck();
                clearTimeout(me.editIdleTimer);
            }, me.self.EDIT_IDLE_MILLISECONDS);
    },
    /**
     * Start SpellCheck via StatusStrip-button.
     */
    startSpellCheckViaButton: function() {
        var me = this;
        me.consoleLog('startSpellCheckViaButton');
        me.terminateSpellCheck();
        me.startSpellCheck();
    },
    /**
     * Start SpellCheck via F7.
     */
    startSpellCheckViaShortcut: function() {
        var me = this;
        me.consoleLog('startSpellCheckViaShortcut');
        me.terminateSpellCheck();
        me.startSpellCheck();
    },
    
    // =========================================================================
    // Additional handlers for events
    // =========================================================================
    
    handleClickInEditor: function() {
        var me = this;
        me.consoleLog('SpellCheck: handleClickInEditor...');
        me.terminateSpellCheck();
    },
    /**
     * Handle KeyDown-Events of Editor.view.segments.HtmlEditor
     * @param {Object} event
     */
    handleKeyUp: function(event) {
        var me = this;
        me.consoleLog('SpellCheck: handleKeyUp...');
        if (!me.isSupportedLanguage) {
            me.consoleLog('handleKeyUp stopped because language is not supported or SpellCheck-Tool does not run.');
            return;
        }
        me.initKeyDownEvent(event);
        if(me.eventHasToBeIgnored() || me.eventIsCtrlV()){ // CTRL+V: SpellCheck will run anyway after insert markup (afterInsertMarkup); don't start it twice
            me.consoleLog(' => Ignored for SpellCheck.');
            me.ignoreEvent = true;
            me.stopEvent = false;
        }
        if(me.eventIsCtrlZ() || me.eventIsCtrlY() || me.eventHasToBeIgnoredAndStopped()){
            me.consoleLog(' => Ignored for SpellCheck and stopped.');
            me.ignoreEvent = true;
            me.stopEvent = true;
        }
        
        // Terminate running SpellChecks?
        switch(true) {
            case me.eventIsTranslate5():
                me.consoleLog('translate5-Keyboard-Shortcut!');
                me.terminateSpellCheck();
            break;
            case (!me.ignoreEvent || me.eventIsArrowKey()):
                me.consoleLog('The user is still editing!');
                me.startSpellCheckViaTimer(); // TODO: HÄ?????
            break;
        }
        
        me.startSpellCheckViaTimer(); // TODO: HÄ?????
        
        // Stop event?
        if(me.stopEvent) {
            event.stopEvent();
        }
    },
    /**
     * After each push etc.: When the content in the Editor gets updated, we start the timer for running the next SpellCheck.
     */
    handleAfterContentUpdate: function() {
        var me = this;
        // Stop if we open a task (not a segment) or try to open a segment that is not editable
        if (me.getSegmentGrid().editingPlugin.context === undefined) {
            me.consoleLog('(SpellCheck:) handleAfterContentUpdate => NO SpellCheck; no editable segment referred (eg. task opened).');
            return;
        }
        
        // Stop if the language is not supported (or we cannot tell yet) or the tool does not run.
        if (!me.isSupportedLanguage) {
            me.consoleLog('(SpellCheck:) handleAfterContentUpdate => NO SpellCheck; the language is not supported or the SpellCheck-Tool does not run.');
            return;
        }
        
        me.consoleLog('(SpellCheck:) handleAfterContentUpdate (' + me.targetLangCode + '/' + me.isSupportedLanguage + ')');
        
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
            spellCheckProcessID,
            editorContentAsText;
        
        if (!me.isSupportedLanguage) { // Should not be the case when we got here already, but that is not enough: it MUST NOT happen.
            me.consoleLog('startSpellCheck failed eg because language is not supported or SpellCheck-Tool does not run.');
            return;
        }
        
        if (me.disableSpellCheckByIdle) {
            me.setEditorDisabled(true);
        }
        // TrackChanges must remove it's placeholder.
        me.fireEvent('removePlaceholdersInEditor');
        
        editorContentAsText = me.getEditorContentAsText(false);
        
        if (editorContentAsText.trim() === '') {
            me.consoleLog('startSpellCheck stopped because editorContentAsText = ""');
            me.terminateSpellCheck();
            return true;
        }
        me.consoleLog('startSpellCheck for editorContentAsText: ' + editorContentAsText);
        
        me.consoleLog('(0.3 => startSpellCheck (' + Ext.Date.format(new Date(), 'c') + ').)');
        spellCheckProcessID = Ext.Date.format(new Date(), 'time');
        me.spellCheckInProgressID = spellCheckProcessID;
        me.consoleLog('me.spellCheckInProgressID: ' + spellCheckProcessID);
        
        // where is the caret at the moment?
        me.bookmarkForCaret = me.getPositionOfCaret();
        
        // "ignore" multiple whitespaces, because we delete them anyway on save.
        me.collapseMultipleWhitespaceInEditor();
        
        me.allMatches = null;
        me.allMatchesRanges = null;
        me.runSpellCheck(editorContentAsText, spellCheckProcessID);
        // => runSpellCheck with the tool calls applySpellCheck() when the results arrive.
    },
    /**
     * What to do after the SpellCheck has been run.
     */
    finishSpellCheck: function(spellCheckProcessID) {
        var me = this,
            sourceSearch = me.getConcordenceSourceSearch();
        
        if (spellCheckProcessID !== me.spellCheckInProgressID) {
            me.consoleLog('do NOT finishSpellCheck...');
            return;
        }
        me.consoleLog('finishSpellCheck...');
        
        if (me.bookmarkForCaret != null) {
            me.setPositionOfCaret(me.bookmarkForCaret);
            me.bookmarkForCaret = null;
        }
        // if the user opens segment for editing and immediately after this
        // uses f3 to focus on concordence search then ignore the editor focus
        if(!sourceSearch || !sourceSearch.hasFocus){
            me.getEditorBody().focus();
        }
        
        me.spellCheckInProgressID = false;
        
        if (me.disableSpellCheckByIdle) {
            me.setEditorDisabled(false);
        }
    },
    /**
     * "Terminate" the SpellCheck.
     */
    terminateSpellCheck: function() {
        var me = this;
        me.consoleLog('terminateSpellCheck.');
        if (!me.isSupportedLanguage) {
            me.consoleLog('terminateSpellCheck stopped eg because language is not supported or SpellCheck-Tool does not run.');
            return;
        }
        clearTimeout(me.editIdleTimer);
        me.editIdleTimer = null;
        me.spellCheckInProgressID = false;
        
        if (me.editor === null) {
            return;
        }
        
        me.getEditorBody().focus();
        
        if (me.disableSpellCheckByIdle) {
            me.setEditorDisabled(false);
        }
    },
    
    // =========================================================================
    // Work with the results that have been found by the tool.
    // =========================================================================
    
    /**
     * Apply the matches found by the SpellCheck (store them and apply the result to the Editor).
     */
    applySpellCheck: function(spellCheckProcessID) {
        var me = this;
        
        if (spellCheckProcessID !== me.spellCheckInProgressID) {
            me.consoleLog('NO applySpellCheck, spellCheckProcess is no longer valid (' + spellCheckProcessID + '/' + me.spellCheckInProgressID + ').');
            me.finishSpellCheck(spellCheckProcessID);
            return;
        }
        
        if (me.allMatchesOfTool === null || me.allMatchesOfTool.length === 0) {
            me.consoleLog('allMatchesOfTool: no errors.');
            me.cleanSpellCheckMarkupInEditor(); // in case there have been errors marked before
            me.bookmarkForCaret = null;
            me.finishSpellCheck(spellCheckProcessID);
            return;
        }
        
        me.storeAllMatchesFromTool();
        me.applySpellCheckResult(spellCheckProcessID);
    },
    /**
     * Apply the results.
     */
    applySpellCheckResult: function(spellCheckProcessID) {
        var me = this;
        
        if (spellCheckProcessID !== me.spellCheckInProgressID) {
            me.consoleLog('NO applySpellCheckResult, spellCheckProcess is no longer valid (' + spellCheckProcessID + '/' + me.spellCheckInProgressID + ').');
            me.finishSpellCheck(spellCheckProcessID);
            return;
        }
        if (me.getEditorBodyExtDomElement() == null) {
            me.consoleLog('applySpellCheck not started: no editor-body found (maybe the editor is closed already).');
            me.finishSpellCheck(spellCheckProcessID);
            return;
        }
        
        me.cleanSpellCheckMarkupInEditor(); // in case a spellcheck has been run before already
        
        if (me.allMatchesOfTool.length > 0) {
            me.applyAllMatches(spellCheckProcessID);
            me.consoleLog('allMatches applied (' + spellCheckProcessID + ').');
        }
        
        me.finishSpellCheck(spellCheckProcessID);
    },
    /**
     * Apply replacement as suggested in the ToolTip.
     */
    applyReplacement: function(event) {
        var me = this,
            rangeForMatch = rangy.createRange(),
            rangeForMatchBookmark,
            replaceText,
            bookmarkForCaret;
        
        bookmarkForCaret = me.getPositionOfCaret();
        
        // Find and bookmark the range that belongs to the SpellCheck-Node for the current ToolTip.
        rangeForMatch.selectNodeContents(me.activeMatchNode);
        rangeForMatchBookmark = me.getBookmarkForRangeInTranslate5(rangeForMatch,true);
        
        // Remove SpellCheck- and TermTag-Markup.
        me.cleanSpellCheckMarkupInEditor();
        
        // Update the range (the SpellCheck-Node is no longer in the DOM!...).
        rangeForMatch = me.moveRangeToBookmarkInTranslate5(rangeForMatch,rangeForMatchBookmark,true);
        rangeForMatchBookmark = me.getBookmarkForRangeInTranslate5(rangeForMatch,true);
        
        // Replacement
        replaceText = Ext.get(event.currentTarget).query('a:first-child span:first-child')[0].innerHTML;
        
        me.isActiveTrackChanges();                                  // SearchReplace.js
        if(!me.activeTrackChanges){                                 // SearchReplace.js
            me.pureReplace(rangeForMatchBookmark,replaceText,true); // SearchReplace.js
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
        
        me.collapseMultipleWhitespaceInEditor();
        
        // new DOM after replacement => find and apply the matches again:
        me.startSpellCheck();
        
        me.setPositionOfCaret(bookmarkForCaret); // TODO: does not land right if the replacement has not the same length as what was replaced
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
    runSpellCheck: function(textToCheck, spellCheckProcessID) {
        var me = this;
        if(textToCheck !== '') {
            me.consoleLog('textToCheck: ' + textToCheck);
            me.runSpellCheckWithTool(textToCheck, spellCheckProcessID);
        } else {
            me.finishSpellCheck(spellCheckProcessID);
        }
    },
    /**
     * Store data for all matches found by the tool (=> then accessable independent from tool).
     */
    storeAllMatchesFromTool: function() {
        var me = this,
            matchStart,
            matchEnd,
            singleMatchObject;
        me.allMatches = [];
        Ext.Array.each(me.allMatchesOfTool, function(match, index) {
            // offsets of text-only version
            matchStart = me.getStartOfMatchFromTool(match);
            matchEnd = me.getEndOfMatchFromTool(match);
            singleMatchObject = {
                    matchIndex        : index,                                      // Integer
                    range             : me.getRangeForMatch(matchStart,matchEnd),   // Rangy bookmark
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
     * Get the range of a match in the Editor using the offsets of the text-only version.
     * @param {Integer} matchStart
     * @param {Integer} matchEnd
     * @returns {Object}
     */
    getRangeForMatch: function(matchStart,matchEnd) {
        var me = this,
            rangeForMatch = rangy.createRange(),
            allDelNodes = [],
            rangeForDelNode = rangy.createRange(),
            bookmarkForDelNode,
            lengthOfDelNode;
        // me.consoleLog('---\n- matchStart: ' + matchStart + ' / matchEnd: ' + matchEnd);
        
        // move offsets according to hidden del-Nodes in front of the match's start and/or end
        allDelNodes = me.getEditorBodyExtDomElement().query('del');
        Ext.Array.each(allDelNodes, function(delNode) {
            rangeForDelNode.selectNodeContents(delNode);
            bookmarkForDelNode = rangeForDelNode.getBookmark();
            //me.consoleLog('- bookmarkForDelNode: ' + bookmarkForDelNode.start + ' / ' + bookmarkForDelNode.end);
            if (bookmarkForDelNode.start > matchStart && bookmarkForDelNode.end > matchEnd) {
                //me.consoleLog('- we are already behind the match: ' + bookmarkForDelNode.start + ' > ' + matchStart + ' && ' + bookmarkForDelNode.end + ' > ' + matchEnd);
                return false; // break here; we are already behind the match
            }
            lengthOfDelNode = rangeForDelNode.text().length;
            me.consoleLog('- length: ' + lengthOfDelNode);
            if (bookmarkForDelNode.start <= matchStart) {
                matchStart = matchStart + lengthOfDelNode;
                matchEnd = matchEnd + lengthOfDelNode;
                //me.consoleLog('- match NOW (start and end moved): ' + matchStart + ' / ' + matchEnd);
            } else if (bookmarkForDelNode.end <= matchEnd) {
                matchEnd = matchEnd + lengthOfDelNode;
                //me.consoleLog('- match NOW (only end moved): ' + matchStart + ' / ' + matchEnd);
            }
        });
        
        // set range for Match by selecting characters
        rangeForMatch.selectCharacters(me.getEditorBody(),matchStart,matchEnd);
        
        // return the bookmark
        return rangeForMatch.getBookmark();
    },
    /**
     * Apply results to captured content from the Editor.
     */
    applyAllMatches: function(spellCheckProcessID) {
        var me = this,
            editorBody = me.getEditorBody(),
            rangeForMatch,
            documentFragmentForMatch,
            spellCheckNode;
        // apply the matches (iterate in reverse order; otherwise the ranges get lost due to DOM-changes "in front of them")
        rangeForMatch = rangy.createRange(editorBody);
        Ext.Array.each(me.allMatches, function(match, index) {
            if (spellCheckProcessID !== me.spellCheckInProgressID) {
                return false; // break (eg after a new keyDown-event)
            }
            rangeForMatch.moveToBookmark(match.range);
            rangeForMatch = me.cleanBordersOfCharacterbasedRange(rangeForMatch);
            documentFragmentForMatch = rangeForMatch.extractContents();
            spellCheckNode = me.createSpellcheckNode(index);
            spellCheckNode.appendChild(documentFragmentForMatch);
            rangeForMatch.insertNode(spellCheckNode);
        }, me, true);

        if (!me.spellCheckInProgressID) {
            return;
        }
        
        me.cleanUpNode(editorBody);
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

    
    /**
     * TRANSLATE-1630 "Workaround for east asian problems with spellchecker"
     * For example, typing Japanese with European keys requires time for the user
     * to use Space and Enter. Running a SpellCheck disrupts the typing. Hence,
     * the SpellCheck must only start when intentionally invoked by the user 
     * (= using a Button instead of keyboard-Idle).
     * @param {Ext.container.Container} statusStrip
     */
    setDisableSpellCheckByIdle: function (statusStrip) {
        var me = this,
            targetLangSplit = me.targetLangCode.split('-'),
            mainLang = targetLangSplit[0];
        // is the target-language one of those that cause problems?
        me.disableSpellCheckByIdle = (me.languagesToStopIdle.indexOf(mainLang) !== -1);
        // if yes, add button in statusStrip:
        if (me.disableSpellCheckByIdle) {
            me.addSpellCheckButton(statusStrip);
        }
    },
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
     * (When me.isSupportedLanguage is false or undefined, we still need the browser's SpellCheck!)
     */
    setBrowserSpellcheck: function(){
        var me = this,
            editorBody = me.getEditorBody();
        editorBody.spellcheck = (me.isSupportedLanguage) ? false : true;
        me.consoleLog('Browser-Spellcheck is set to: ' + editorBody.spellcheck + '.');
    },
    /**
     * Enable SnapshotHistory if needed.
     */
    setSnapshotHistory: function(){
        var me = this;
    	if (me.isSupportedLanguage) {
            me.fireEvent('activateSnapshotHistory');
            me.consoleLog('Spellcheck activateSnapshotHistory: yes');
    	} else {
            me.consoleLog('Spellcheck activateSnapshotHistory: no');
    	}
    },
    
    // =========================================================================
    // ToolTips
    // =========================================================================
    
    /***
     * Tooltips for the spellcheck-matches.
     */
    handleSpellCheckTooltip: function() {
        var me = this,
            oldMenuItems,
            spellCheckData;
        // remove formerly added menu-items
        oldMenuItems = me.spellCheckTooltip.items.items;
        Ext.Array.each(oldMenuItems, function(itemToRemove) {
            me.spellCheckTooltip.remove(itemToRemove);
        },me, true);
        // update Tooltip
        spellCheckData = me.getSpellCheckData();
        if (spellCheckData !== false) {
            me.spellCheckTooltip.add(spellCheckData);
        }
    },
    getSpellCheckData: function() {
        var me = this,
            activeMatchIndex,
            activeMatch,
            message,
            replacements,
            infoURLs,
            items;
        if (me.allMatches === null || me.allMatches.length < 1) {
            return false;
        }
        activeMatchIndex = me.activeMatchNode.getAttribute(me.self.ATTRIBUTE_ACTIVEMATCHINDEX);
        activeMatch = me.allMatches[activeMatchIndex];
        message      = activeMatch.message;
        replacements = activeMatch.replacements;
        infoURLs     = activeMatch.infoURLs;
        items = [];
        // message
        items.push({text: '<b>'+message+'</b>',
                    cls: me.self.CSS_CLASSNAME_TOOLTIP_HEADER });
        // replacement(s)
        if (replacements.length > 0) {
            Ext.Array.each(replacements, function(replacement) {
                items.push({text: replacement.replace(' ', '&nbsp;'), // quick and dirty workaround for empty spaces (e.g. when "  " should be replaced with " " or when " - " should be replaced with " – ")
                            cls: me.self.CSS_CLASSNAME_REPLACEMENTLINK });
            });
        }
        // infoURL(s)
        if (infoURLs.length > 0) {
            Ext.Array.each(infoURLs, function(url) {
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
            posX = event.getX() + me.editor.iframeEl.getX(),
            posY = event.getY() + me.editor.iframeEl.getY();
        me.activeMatchNode = event.currentTarget;
        me.initTooltips(); // me.spellCheckTooltip.hide() is not enough (e.g. after a contextmenu had been shown with a long list of replacements, the next contextmenu was placed as if it still has that height)
        me.spellCheckTooltip.showAt(posX,posY);
    }
});
