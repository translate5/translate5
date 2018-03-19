
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
    mixins: ['Editor.plugins.SpellCheck.controller.UtilLanguageTool',
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
                beforeKeyMapUsage: 'handleEditorKeyMapUsage'
            }
        }
    },
    statics: {
        NODE_NAME_MATCH: 'div',
        CSS_CLASSNAME_MATCH: 'spellcheck'
    },
    
    // =========================================================================
    
    editor: null,                   // The segment's Editor (Editor.view.segments.HtmlEditor)
    editorBody: null,               // Segment's Editor: HTMLBodyElement
    editorBodyExtDomElement: null,  // Segment's Editor: Ext.dom.Element
    
    taskId: null,                   // current taskId (updated with every initSpellCheck)
    
    targetLangCode: [],             // store targetLang by tasks
    isSupportedLanguage: [],        // store isSupportedLanguage by tasks
    
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
    /**
     * 
     */
    handleEditorKeyMapUsage: function(conf, area, mapOverwrite) {
        var me = this,
            ev = Ext.event.Event;
        conf.keyMapConfig['space'] = [ev.SPACE,{ctrl: false, alt: false},function(key) {
            me.initSpellCheck();
        }, false];
    },
    /**
     * 
     */
    initSpellCheck: function() {
        var me = this;
        // We store task-specific properties (targetLangCode, isSupportedLanguage)
        // => always check everything related to the current task!
        me.taskId = Editor.data.task.get('id');
        // Start SpellCheck if language is supported.
        me.consoleLog('*** 0.2 initSpellCheck for task ' + me.taskId + ' (' + me.targetLangCode[me.taskId] + '/' + me.isSupportedLanguage[me.taskId] + ') ***');
        if(me.isSupportedLanguage[me.taskId] == null) {
            me.consoleLog('(0.2 => checkSupportedLanguages first.)');
            me.startSpellCheckAfterCheckingSupportedLanguages();
        } else if (me.isSupportedLanguage[me.taskId] == true) {
            me.consoleLog('(0.2 => startSpellCheck directly.)');
            me.startSpellCheck();
        } else {
            me.consoleLog('(0.2 => startSpellCheck not started because language is not supported.)');
        }
    },
    /**
     * 
     */
    initEditor: function() {
        var me = this,
            plug = me.getSegmentGrid().editingPlugin,
            editor = plug.editor; // → this is the row editor component
        me.consoleLog('initEditor');
        me.editor = editor.mainEditor; // → this is the HtmlEditor
        
        // inject CSS
        Ext.util.CSS.createStyleSheetToWindow(
                me.editor.getDoc(),
                '.spellcheck {border-bottom: 1px solid red; display: inline-block;}' 
            );
    },

    // =========================================================================
    // SpellCheck
    // =========================================================================
    
    /**
     * Start the SpellCheck (be sure to run this only for supported languages).
     */
    startSpellCheck: function() {
        var me = this,
            editorText,
            rangeForEditor = rangy.createRange();
        me.consoleLog('startSpellCheck...');
        me.initEditor();
        rangeForEditor.selectNode(me.editor.getEditorBody());
        
        // TODO: run SpellCheck only if the content in the Editor has changed.
        
        //add display none to all del nodes, with this they are ignored as searchable
        me.prepareDelNodeForSearch(true);
        
        editorText = rangeForEditor.text();
        me.runSpellCheck(editorText);
    },
    /**
     * Apply the matches found by the SpellCheck.
     * @param {Array} matches
     */
    applySpellCheck: function(matches) {
        var me = this,
            editorBody,
            rangeForMatch,
            matchStart,
            matchEnd,
            allRangesForMatches = [],
            documentFragmentForMatch,
            spellCheckNode;
        me.consoleLog('applySpellCheck...');
        
        if (matches.length > 0) {
            editorBody = me.editor.getEditorBody();
            Ext.Array.each(matches, function(match, index) {
                rangeForMatch = rangy.createRange();
                matchStart = match.context.offset;
                matchEnd = matchStart + match.context.length;
                rangeForMatch.selectCharacters(editorBody,matchStart,matchEnd);
                allRangesForMatches[index] = rangeForMatch;
            });
            Ext.Array.each(allRangesForMatches, function(rangeForMatch, index) {
                documentFragmentForMatch = rangeForMatch.extractContents();
                spellCheckNode = me.createSpellcheckNode();
                spellCheckNode.appendChild(documentFragmentForMatch);
                rangeForMatch.insertNode(spellCheckNode);
            });
        }
        
        me.finishSpellCheck();
    },
    /**
     * Finish the SpellCheck.
     */
    finishSpellCheck: function() {
        var me = this;
        me.consoleLog('finishSpellCheck...');
        //set the dell nodes visible again
        me.prepareDelNodeForSearch(false);
    },

    // =========================================================================
    // SpellCheck: generic layer for integrating specific tools
    // =========================================================================
    
    /**
     * Is the language supported by the tool(s) we use?
     * The tool's specific code shall:
     * (1) store the result in me.isSupportedLanguage[me.taskId]
     * (2) call 'startSpellCheck' if the language is supported.
     */
    startSpellCheckAfterCheckingSupportedLanguages: function() {
        var me = this;
        me.startSpellCheckAfterCheckingSupportedLanguagesWithTool();
    },
    /**
     * Run the SpellCheck for the given text.
     * The tool's specific code shall call 'applySpellCheck' with the found matches.
     * @param {String} textToCheck
     */
    runSpellCheck: function(textToCheck) {
        var me = this;
        me.runSpellCheckWithTool(textToCheck);
    },
    
    // =========================================================================
    // Helpers for the the SpellChecker
    // =========================================================================
    
    /**
     * Create and return a new node for SpellCheck-Match.
     * @returns {Object}
     */
    createSpellcheckNode: function(){
        var me = this,
            nodeElParams = { tag: me.self.NODE_NAME_MATCH,
                             cls: me.self.CSS_CLASSNAME_MATCH };
        return Ext.DomHelper.createDom(nodeElParams);
    },
    
    // =========================================================================
    // Helpers for the Editor and Task
    // =========================================================================
    
    /***
     * Use this function to get the editor
     */
    getEditorBody:function(){
        var me=this;
        if(!me.editor){
            return false;
        }
        if(me.editor.editorBody){
            return me.editor.editorBody;
        }
        //reinit the editor body
        me.editorBody=me.editor.getEditorBody();
        return me.editorBody;
    },
    /***
     * Use this function to get the editor ext document element
     */
    getEditorBodyExtDomElement:function(){
        var me=this;
        me.editorBodyExtDomElement=Ext.get(me.getEditorBody());
        return me.editorBodyExtDomElement;
    },
    /***
     * Don't fetch targetLangCode for the given taskId from scratch if we already have it.
     * @returns {String}
     */
    getTargetLangCodeByTaskId: function(taskId){
        var me = this,
            languages,
            task,
            targetLang;
        if(me.targetLangCode[taskId]){
            return me.targetLangCode[taskId];
        }
        task = Editor.data.task;
        if (Editor.data.task.get('id') != taskId) {
            me.consoleLog('getTargetLangCodeByTaskId failed: Cannot fetch data for the given taskId.');
            return null;
        }
        languages = Ext.getStore('admin.Languages');
        targetLang = languages.getById(task.get('targetLang'));
        me.targetLangCode[taskId] = targetLang.get('rfc5646');
        return me.targetLangCode[taskId];
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
