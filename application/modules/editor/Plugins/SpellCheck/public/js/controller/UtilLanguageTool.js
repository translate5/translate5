
/*
START LICENSE AND COPYRIGHT

 Copyright (c) 2013 - 2017 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

 Contact:  http://www.MittagQI.com/  /  service (ATT) MittagQI.com

 This file is part of a plug-in for translate5. 
 translate5 can be optained via the instructions that are linked at http://www.translate5.net
 For the license of translate5 itself please see http://www.translate5.net/license.txt
 For the license of this plug-in, please see below.
 
 This file is part of a plug-in for translate5 and may be used under the terms of the
 GNU GENERAL PUBLIC LICENSE version 3 as published by the Free Software Foundation and 
 appearing in the file gpl3-license.txt included in the packaging of the translate5 plug-in
 to which this file belongs. Please review the following information to ensure the 
 GNU GENERAL PUBLIC LICENSE version 3 requirements will be met:
 http://www.gnu.org/licenses/gpl.html
   
 There is a plugin exception available for use with this release of translate5 for 
 translate5 plug-ins that are distributed under GNU GENERAL PUBLIC LICENSE version 3: 
 Please see http://www.translate5.net/plugin-exception.txt or plugin-exception.txt in the
 root folder of translate5.
  
 @copyright  Marc Mittag, MittagQI - Quality Informatics
 @author     MittagQI - Quality Informatics
 @license    GNU GENERAL PUBLIC LICENSE version 3 with plugin-execption
			 http://www.gnu.org/licenses/gpl.html
			 http://www.translate5.net/plugin-exception.txt

END LICENSE AND COPYRIGHT
*/

/**
 * Mixin with Helpers regarding the Editor
 * @class Editor.plugins.TrackChanges.controller.UtilEditor
 */
Ext.define('Editor.plugins.SpellChecker.controller.UtilLanguageTool', {
    
    workflowStepNameForTranslation: 'translation',
    workflowStepNrForFirstStep: '1',
    
    /**
     * Does the Editor use the TrackChanges at all?
     * @returns {Boolean}
     */
    ignoreTrackChanges: function() {
        var me = this;
        // Ignore TrackChanges when we are in the first step of a translation
        return (me.editorTaskWorkflowStepName  == me.workflowStepNameForTranslation
                && me.editorTaskWorkflowStepNr == me.workflowStepNrForFirstStep);
    },
    /**
     * Inject CSS into the Editor.
     * - see also: /resources/plugin.css (= CSS for grid with all the segments)
     * - but here: CSS for "div-images" not needed because they are displayed as images in the opened Editor
     */
    injectCSSForEditor: function() {
        var me = this;
        Ext.util.CSS.createStyleSheetToWindow(
                me.editor.getDoc(),
                'body.ergonomic .trackchanges img {position:relative; top: -1px; height: 13px !important;}' + 
                'body.ergonomic del.trackchanges img {filter: grayscale(100%); opacity: 0.7;}' + 
                'ins.trackchanges {text-decoration: none;}' + 
                'del.trackchanges {text-decoration: line-through; color: #999;}' + 
                '.trackchanges[data-usercssnr="usernr1"] {background-color: #f9e788;}   /* yellow pale */' + 
                '.trackchanges[data-usercssnr="usernr2"] {background-color: #f9b372;}   /* orange pale */' + 
                '.trackchanges[data-usercssnr="usernr3"] {background-color: #cbe0f9;}   /* blue pale */' + 
                '.trackchanges[data-usercssnr="usernr4"] {background-color: #f3d5ed;}   /* lilac pale */' + 
                '.trackchanges[data-usercssnr="usernr5"] {background-color: #e4fb81;}   /* yellowish green pale */' + 
                '.trackchanges[data-usercssnr="usernr6"] {background-color: #FFD8D4;}   /* pink pale */' + 
                '.trackchanges[data-usercssnr="usernr7"] {background-color: #9af2db;}   /* turquoise pale */' + 
                '.trackchanges[data-usercssnr="usernr8"] {background-color: #b5f3bf;}   /* lime green pale */' +
                'span.supersededTerm, span.deprecatedTerm {padding: 2px;}' +
                '*.searchResult {font-weight: bold;background-color: yellow;}' +
                '*.redItalic {font-style: italic;color: red;}' 
            );
    },
    /**
     * Copy something from the Editor into the browser's clipboard.
     * (https://stackoverflow.com/a/33928558)
     */
    copyToClipboard: function () {
        var me = this,
            textToCopy = me.docSelRange.toHtml(),
            docSelBookmark = me.docSel.getBookmark();
        me.consoleLog("copyToClipboard: " + textToCopy);
        if (window.clipboardData && window.clipboardData.setData) {
            // IE specific code path to prevent textarea being shown while dialog is visible.
            return clipboardData.setData("Text", textToCopy);
        } else if (me.editorDoc.queryCommandSupported && me.editorDoc.queryCommandSupported("copy")) {
            var textarea = me.editorDoc.createElement("textarea");
            textarea.textContent = textToCopy;
            textarea.style.position = "fixed";  // Prevent scrolling to bottom of page in MS Edge.
            me.editorBody.appendChild(textarea);
            textarea.select();
            try {
                me.editorDoc.execCommand("copy");  // Security exception may be thrown by some browsers.
            } catch (ex) {
                Editor.MessageBox.addError(me.messages.NoImageTagsForClipboard);
            } finally {
                me.editorBody.removeChild(textarea);
            }
        } else {
            if (me.USE_CONSOLE) {
                debugger;
            }
        }
        me.docSel.moveToBookmark(docSelBookmark);
    }
});