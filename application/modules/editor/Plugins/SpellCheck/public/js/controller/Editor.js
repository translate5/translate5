
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
    requires: ['Editor.util.SegmentContent',
               'Ext.tip.ToolTip'],
    mixins: ['Editor.plugins.SpellCheck.controller.UtilLanguageTool',
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
            },
        },
        component: {
            'segmentsHtmleditor': {
                push: 'startTimerForSpellCheck',
                afterInsertMarkup: 'startTimerForSpellCheck'
            }
        },
    },
    messages: {
        moreInformation: '#UT#More information',
        errorsFoundOnSaving: '#UT#SpellCheck: errors found on saving Segment Nr. %segmentnr.',
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
    
    editor: null,                   // The segment's Editor (Editor.view.segments.HtmlEditor)
    editorBodyExtDomElement: null,  // Segment's Editor: Ext.dom.Element (also needed for me.prepareDelNodeForSearch() in SearchReplaceUtils.js)
    
    targetLangCode: null,           // language to be checked
    isSupportedLanguage: null,      // if the language is supported by our tool(s)
    
    allMatchesOfTool: null,         // all matches as found by the tool
    allMatches: null,               // data of all matches found by the tool(s); here already stored independently from the tool
    allMatchesRanges: null,         // bookmarks of all ranges for the matches found by the tool(s); here already stored independently from the tool
    activeMatchNode: null,          // node of single match currently in use
    
    spellCheckResults: null,        // Store results for already checked Html-Content in the Editor: me.spellCheckResults[htmlWithoutSpellCheckNodes] = htmlWithSpellCheckNodes
    
    spellCheckTooltip: null,        // spellcheck tooltip instance
    
    editIdleTimer: null,            // time "nothing" is changed in the Editor's content; 1) user: presses no key 2) segmentsHtmleditor: no push, no afterInsertMarkup
    editIdleRestarted: null,        // has the content been changed in the Editor (= timer restarted) since the last timer has started the SpellCheck?
    
    isSpellCheckOnSaving: false,    // flag to indicate that the SpellChecker runs on saving the segment
    savedSegmentNrInTask: false,    // segmentNrInTask of the segment that started the SpellCheck on saving
    
    USE_CONSOLE: true,              // (true|false): use true for developing using the browser's console, otherwise use false
    
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
        me.editorBodyExtDomElement = me.getEditorBodyExtDomElement();
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
    initKeyboardEvents: function() {
        var me = this;
        Ext.get(me.editor.getDoc()).on('keydown', me.startTimerForSpellCheck, me, {priority: 9999, delegated: false});
    },
    initMouseEvents: function() {
        var me = this,
            tooltipBody = Ext.getBody();
        
        me.editorBodyExtDomElement.on({
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
    // Run, apply and finish the SpellCheck.
    // =========================================================================
    
    /**
     * Prepare and run the SpellCheck (be sure to run this only for supported languages).
     */
    startSpellCheck: function() {
        var me = this,
            rangeForEditor = rangy.createRange(),
            editorBody,
            editorText,
            htmlWithoutSpellCheckNodes,
            htmlWithSpellCheckNodes;
        if (!me.isSupportedLanguage) {
            me.consoleLog('startSpellCheck failed because language is not supported.');
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
        
        me.cleanSpellCheckTags(); // in case a spellcheck has been run before already
        
        me.prepareDelNodeForSearch(true);   // SearchReplaceUtils.js (add display none to all del nodes, with this they are ignored as searchable)
        
        // If the text has not changed, we can use the result we have already fetched and applied.
        // (Doing "nothing" is not an option because then there won't be any SpellCheck-Nodes at all in the Editor.)
        // TODO: keep the position of the caret!
        htmlWithoutSpellCheckNodes = me.editorBodyExtDomElement.getHtml();
        if (!me.isSpellCheckOnSaving && (htmlWithoutSpellCheckNodes in me.spellCheckResults) ) {
            htmlWithSpellCheckNodes = me.spellCheckResults[htmlWithoutSpellCheckNodes];
            me.editorBodyExtDomElement.setHtml(htmlWithSpellCheckNodes);
            me.consoleLog('startSpellCheck did not start a new check because we have already run a check for this content. We used the result we already have.');
            return;
        }
        
        rangeForEditor.selectNode(editorBody);
        editorText = rangeForEditor.text();
        me.runSpellCheck(editorText);
    },
    /**
     * Apply the matches found by the SpellCheck:
     * - store data for all matches from tool
     * - apply results to Editor
     * @param {Array} matchesFromTool
     */
    applySpellCheck: function() {
        var me = this,
            message,
            htmlWithoutSpellCheckNodes,
            htmlWithSpellCheckNodes;
        if (me.isSpellCheckOnSaving) {
            me.consoleLog('applySpellCheck on isSpellCheckOnSaving...');
            if (me.allMatchesOfTool.length > 0) {
                message = me.messages.errorsFoundOnSaving.replace(/%segmentnr/, me.savedSegmentNrInTask);
                Editor.MessageBox.addInfo(message); // TODO: Should we show this message only if the errors are not the same as already known?
            }
            // TODO: update segment-status in grid
            me.isSpellCheckOnSaving = false; // = reset to "default"
            return;
        }
        if (me.editIdleRestarted) {
            me.consoleLog('applySpellCheck not started: results might be invalid after the content was edited in the meantime.');
            return;
        }
        
        htmlWithoutSpellCheckNodes = me.editorBodyExtDomElement.getHtml();
        if (me.allMatchesOfTool.length > 0) {
            me.storeAllMatchesFromTool();
            me.showAllMatchesInEditor();
        } else {
            me.consoleLog('allMatchesOfTool: no results (not checked or nothing found).');
        }
        htmlWithSpellCheckNodes = me.editorBodyExtDomElement.getHtml();
        
        // and store the result of the SpellCheck for the html WITHOUT the SpellCheck-Nodes
        me.spellCheckResults[htmlWithoutSpellCheckNodes] = htmlWithSpellCheckNodes;
        
        me.finishSpellCheck();
    },
    /**
     * Finish the SpellCheck.
     */
    finishSpellCheck: function() {
        var me = this;
        me.prepareDelNodeForSearch(false);  // SearchReplaceUtils.js (set the del nodes visible again)
        // TODO: if the user was editing content behind the first match, the caret jumps in front of the first match.
    },
    /**
     * Apply replacement as suggested in the ToolTip.
     */
    applyReplacement: function(event) {
        var me = this,
            rangeForMatch = rangy.createRange(),
            replaceText = event.currentTarget.innerText,
            range;
        
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
     * Apply results to Editor.
     */
    showAllMatchesInEditor: function() {
        var me = this,
            editorBody = me.getEditorBody(),
            rangeForMatch = rangy.createRange(editorBody),
            documentFragmentForMatch,
            spellCheckNode;
        Ext.Array.each(me.allMatches, function(match, index) {
            rangeForMatch.moveToBookmark(match.range);
            documentFragmentForMatch = rangeForMatch.extractContents();
            spellCheckNode = me.createSpellcheckNode(index);
            spellCheckNode.appendChild(documentFragmentForMatch);
            rangeForMatch.insertNode(spellCheckNode);
        }, me, true); // iterate in reverse order! (Otherwise the ranges get lost due to DOM-changes "in front of them".)
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
     * Use this function to get the editor body.
     * @returns {HTMLBodyElement}
     */
    getEditorBody:function(){
        var me = this;
        if(!me.editor){
            return false;
        }
        if(me.editor.editorBody){
            return me.editor.editorBody;
        }
        return me.editor.getEditorBody();
    },
    /***
     * Use this function to get the editor ext document element.
     * @returns {Ext.dom.Element}
     */
    getEditorBodyExtDomElement:function(){
        var me = this;
        return Ext.get(me.getEditorBody());
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
                me.editor.getDoc(),
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
     * Remove SpellCheck-Tags from the editor but keep their content.
     */
    cleanSpellCheckTags:function(){
        var me = this,
            allSpellCheckElements,
            spellCheckElementParentNode;
        if(!me.editorBodyExtDomElement){
            me.consoleLog('cleanSpellCheckTags failed with missing editorBodyExtDomElement.');
            return false;
        }
        // find all spellcheck-elements and "remove their tags"
        allSpellCheckElements = me.editorBodyExtDomElement.query('.' + me.self.CSS_CLASSNAME_MATCH);
        Ext.Array.each(allSpellCheckElements, function(spellCheckEl, index) {
            spellCheckElementParentNode = spellCheckEl.parentNode;
            while(spellCheckEl.firstChild) {
                spellCheckElementParentNode.insertBefore(spellCheckEl.firstChild, spellCheckEl);
            }
            spellCheckElementParentNode.removeChild(spellCheckEl);
            spellCheckElementParentNode.normalize();
        });
    },
    
    // =========================================================================
    // Development
    // =========================================================================
    
    /**
     * Write into the browser console depending on the setting of me.USE_CONSOLE.
     * @param {(String|Object)} outputForConsole
     */
    consoleLog: function(outputForConsole) {
        var me = this;
        if (me.USE_CONSOLE) {
            if (typeof outputForConsole === 'string' || outputForConsole instanceof String) {
                console.log(outputForConsole);
            } else {
                console.dir(outputForConsole);
            }
        }
    },
    consoleClear: function() {
        var me = this;
        if (me.USE_CONSOLE) {
            console.clear();
        }
    }
});
