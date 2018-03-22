
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
            },
            '#Editor.$application': {
                editorViewportOpened: 'initSpellCheckPluginForTask',
                editorViewportClosed: 'onDestroy'
            },
        },
        component: {
            'segmentsHtmleditor': {
                initialize: 'initSpellCheckPluginForEditor',
            }
        },
    },
    statics: {
        // spellcheck-Node
        NODE_NAME_MATCH: 'span',
        // CSS-Classes for the spellcheck-Node
        CSS_CLASSNAME_MATCH:        'spellcheck',
        CSS_CLASSNAME_GRAMMERERROR: 'grammarError',
        CSS_CLASSNAME_SUGGESTION:   'suggestion',
        CSS_CLASSNAME_SPELLERROR:   'spellError',
        // Attributes for the spellcheck-Node
        ATTRIBUTE_MESSAGE: 'data-spellcheck-message'
    },
    
    // =========================================================================
    
    editor: null,                   // The segment's Editor (Editor.view.segments.HtmlEditor)
    editorBody: null,               // Segment's Editor: HTMLBodyElement
    editorBodyExtDomElement: null,  // Segment's Editor: Ext.dom.Element
    
    targetLangCode: null,           // language to be checked
    isSupportedLanguage: null,      // if the language is supported by our tools
    
    spellCheckTooltip: null,        // spellcheck tooltip instance
    spellCheckMatch: null,          // current spellCheck-node for the ToolTip
    
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
        me.consoleLog('SpellCheck-Plugin: cleanup done.');
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
    },  
    /**
     * Init the SpellCheck-Plugin for the current Editor
     * (only if the language is supported by our own tools):
     * - Initialize the Editor.
     * - Disable the browser's spellcheck if the language IS supported by our own tools.
     */
    initSpellCheckPluginForEditor: function() {
        var me = this;
        if (me.isSupportedLanguage) {
            me.consoleLog('0.2b initSpellCheckPluginForEditor (' + me.targetLangCode + '/' + me.isSupportedLanguage + ')');
            me.initEditorEtc();
            me.setBrowserSpellcheck();
        } else {
            me.consoleLog('0.2b SpellCheckPluginForEditor not initialized because language is not supported (' + me.targetLangCode + '/' + me.isSupportedLanguage + ').');
        }
    },  
    /**
     * 
     */
    handleEditorKeyMapUsage: function(conf, area, mapOverwrite) {
        var me = this,
            ev = Ext.event.Event;
        if (me.isSupportedLanguage) {
            conf.keyMapConfig['space'] = [ev.SPACE,{ctrl: false, alt: false},function(key) {
                me.initSpellCheck();
            }, false];
        }
    },
    /**
     * Check if language is supported and if so, start the SpellCheck.
     */
    initSpellCheck: function() {
        var me = this;
        if (me.isSupportedLanguage== true) {
            me.consoleLog('(0.3 => startSpellCheck.)');
            me.startSpellCheck();
        } else {
            me.consoleLog('(0.3 => startSpellCheck not started because language is not supported.)');
        }
    },
    /**
     * Init Editor etc (= related stuff: CSS, ToolTips, MouseEvents).
     */
    initEditorEtc: function() {
        var me = this,
            plug = me.getSegmentGrid().editingPlugin,
            editor = plug.editor; // → this is the row editor component;
        
        me.editor = editor.mainEditor; // → this is the HtmlEditor:
        me.injectCSSForEditor();
        me.initTooltips();
        me.initMouseEvents();
    },
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
    initMouseEvents: function() {
        var me = this,
            editorBodyExtDomEl = me.getEditorBodyExtDomElement();
        
        editorBodyExtDomEl.on({
            contextmenu:{
                delegated: false,
                delegate: me.self.NODE_NAME_MATCH + '.' + me.self.CSS_CLASSNAME_MATCH,
                fn: me.showToolTip,
                scope: this,
                preventDefault: true
            }
        });
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
        if (!me.isSupportedLanguage) {
            me.consoleLog('startSpellCheck failed because language is not supported.');
            return;
        }
        me.consoleLog('startSpellCheck...');
        rangeForEditor.selectNode(me.editor.getEditorBody());
        
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
            Ext.Array.each(matches, function(match, index) {// TODO: some of this depends on what the tool returns => add generic layer and move method to Util
                rangeForMatch = rangy.createRange(editorBody);
                matchStart = match.offset;
                matchEnd = matchStart + match.context.length;
                rangeForMatch.selectCharacters(editorBody,matchStart,matchEnd);
                allRangesForMatches[index] = rangeForMatch;
            });
            Ext.Array.each(allRangesForMatches, function(rangeForMatch, index, allRangesForMatches) {
                documentFragmentForMatch = rangeForMatch.extractContents();
                spellCheckNode = me.createSpellcheckNode(matches[index]);
                spellCheckNode.appendChild(documentFragmentForMatch);
                rangeForMatch.insertNode(spellCheckNode);
            }, me, true); // iterate in reverse order! (Otherwise the ranges get lost due to DOM-changes "in front of them".) 
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
     * - store the result in me.isSupportedLanguage
     */
    setLanguageSupport: function() {
        var me = this;
        me.setLanguageSupportWithTool();
    },
    /**
     * Run the SpellCheck for the given text.
     * The tool's specific code shall: 
     * - call applySpellCheck() with the found matches
     * @param {String} textToCheck
     */
    runSpellCheck: function(textToCheck) {
        var me = this;
        me.runSpellCheckWithTool(textToCheck);
    },
    
    // =========================================================================
    // Helpers for the SpellChecker
    // =========================================================================
    
    /**
     * Create and return a new node for SpellCheck-Match.
     * For match-specific data, get the data from the tool.
     * @returns {Object}
     */
    createSpellcheckNode: function(match){
        var me = this,
            nodeElParams = { tag: me.self.NODE_NAME_MATCH };
        nodeElParams['cls'] = me.self.CSS_CLASSNAME_MATCH + ' ' + me.getCSSForMatchFromTool(match);
        nodeElParams[me.self.ATTRIBUTE_MESSAGE] = me.getMessageForMatchFromTool(match);
        return Ext.DomHelper.createDom(nodeElParams);
    },
    
    // =========================================================================
    // Helpers for the Editor and Task
    // =========================================================================
    
    /***
     * Use this function to get the editor body.
     * @returns {HTMLBodyElement}
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
     * Use this function to get the editor ext document element.
     * @returns {Ext.dom.Element}
     */
    getEditorBodyExtDomElement:function(){
        var me=this;
        me.editorBodyExtDomElement=Ext.get(me.getEditorBody());
        return me.editorBodyExtDomElement;
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
                '.'+me.self.CSS_CLASSNAME_MATCH+' {border-bottom: 2px dotted; border-color: red;}' + // TODO: use wavy line instead
                '.'+me.self.CSS_CLASSNAME_MATCH+'.'+me.self.CSS_CLASSNAME_GRAMMERERROR+' {border-color: #ab8906;}' +           // dark yellow
                '.'+me.self.CSS_CLASSNAME_MATCH+'.'+me.self.CSS_CLASSNAME_SUGGESTION+' {border-color: #458fe6;}' +             // blue
                '.'+me.self.CSS_CLASSNAME_MATCH+'.'+me.self.CSS_CLASSNAME_SPELLERROR+' {border-color: #e645a8;}'               // red-violet
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
            node = me.spellCheckMatch,
            nodeMessage;
        if (node.hasAttribute(me.self.ATTRIBUTE_MESSAGE)) {
            nodeMessage = node.getAttribute(me.self.ATTRIBUTE_MESSAGE);
        }
        return '<b>'+ nodeMessage + '</b><br /><a href="/test.html">Test</a>'+'<br />';
    },
    showToolTip: function(event) {
        var me = this;
        me.spellCheckMatch = event.currentTarget;
        me.spellCheckTooltip.show();
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
