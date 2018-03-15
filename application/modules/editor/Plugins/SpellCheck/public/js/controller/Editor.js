
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
    mixins: ['Editor.plugins.SpellCheck.controller.UtilLanguageTool'],
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

    // =========================================================================

    editor: null,
    targetLangCode: null,
    USE_CONSOLE: true, // (true|false): use true for developing using the browser's console, otherwise use false
    
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
        }, true];
    },
    /**
     * 
     */
    initSpellCheck: function(event) {
        var me = this;
        me.consoleLog('*** initSpellCheck ***');
        me.initLanguageSupport();
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
    },

    // =========================================================================
    // SpellCheck: generic layer for integrating specific tools
    // =========================================================================
    
    /**
     * Is the language supported by the tool(s) we use?
     * @returns Boolean
     */
    initLanguageSupport: function() {
        var me = this,
            targetLangCode = me.getTargetLangCode();
        me.checkSupportedLanguages(targetLangCode);
    },
    
    // =========================================================================
    // Helpers
    // =========================================================================
    
    /***
     * Don't fetch targetLangCode from scratch if we already have it.
     * @returns {String}
     */
    getTargetLangCode: function(){
        var me = this,
            languages,
            task,
            targetLang,
            targetLangCode;
        if(me.targetLangCode){
            return me.targetLangCode;
        }
        languages = Ext.getStore('admin.Languages');
        task = Editor.data.task;
        targetLang = languages.getById(task.get('targetLang'));
        me.targetLangCode = targetLang.get('rfc5646');
        return me.targetLangCode;
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
