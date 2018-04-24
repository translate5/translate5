
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
 * Mixin with Helpers regarding the LanguageTool: https://languagetool.org/
 * @class Editor.plugins.TrackChanges.controller.UtilLanguageTool
 */
Ext.define('Editor.plugins.SpellCheck.controller.UtilLanguageTool', {
    
    languageToCheckLongCode: null, // longCode of LanguageTool's language, e.g "en", "en-AU", ...
    
    /**
     * Start of checking if the targetLangCode for the current taskId is supported by LanguageTool.
     */
    setLanguageSupportWithTool: function() {
        var me = this,
            resultLT,
            url = Editor.data.restpath+'plugins_spellcheck_spellcheckquery/languages',
            method = 'GET',
            params = {
                targetLangCode : me.targetLangCode
            };
        
        Ext.Ajax.request({
            url:url,
            method:method,
            params:params,
            success: function(response){
                me.consoleLog('Checking supported languages (LanguageTool) done.');
                resultLT = Ext.util.JSON.decode(response.responseText);
                me.setIsSupportedLanguage(resultLT);
            },
            failure: function(response){
                me.consoleLog('Checking supported languages (LanguageTool) failed: ' + response.status);
            }
        });
    },
    /**
     * Checks if the language of the current task is supported 
     * and stores the result in me.isSupportedLanguage.
     * @param {Boolean|Object} resultLT
     */
    setIsSupportedLanguage: function (resultLT) {
        var me = this;
        if (resultLT.row == false) {
            me.isSupportedLanguage = false;
        } else {
            me.isSupportedLanguage = true;
            me.languageToCheckLongCode = resultLT.rows.longCode;
        }
        me.consoleLog('=> isSupportedLanguage: ' + me.isSupportedLanguage);
    },
    /**
     * 
     * @param {String} text
     */
    runSpellCheckWithTool: function (textToCheck) {
        var me = this,
            url = Editor.data.restpath+'plugins_spellcheck_spellcheckquery/matches',
            method = 'POST',
            params = {
                text: textToCheck,
                language: me.languageToCheckLongCode
            },
            resultLT;
        
            Ext.Ajax.request({
                url:url,
                method:method,
                params:params,
                success: function(response){
                    me.consoleLog('runSpellCheckWithTool (LanguageTool) done.');
                    resultLT = Ext.util.JSON.decode(response.responseText);
                    if (resultLT.rows.matches) {
                        me.allMatchesOfTool = resultLT.rows.matches;
                        me.applySpellCheck();
                    } else {
                        me.finishSpellCheck();
                    }
                },
                failure: function(response){
                    me.consoleLog('runSpellCheckWithTool (LanguageTool) failed: ' + response.status);
                } 
            });
    },
    /**
     * extract data from match: (bookmark for) range
     * @param {Object} match
     * @returns {String}
     */
    getRangeForMatchFromTool: function (match) {
        var me = this,
            rangeForMatch = rangy.createRange(),
            matchStart,
            matchEnd,
            allDelNodes = [],
            rangeForDelNode = rangy.createRange(),
            bookmarkForDelNode,
            lengthOfDelNode;
        // offsets of text-only version
        matchStart = match.offset;
        matchEnd = matchStart + match.context.length;
        me.consoleLog("---\n- matchStart: " + matchStart + " / matchEnd: " + matchEnd);
        // move offsets according to hidden del-Nodes in front of the match's start and/or end
        allDelNodes = me.getEditorBodyExtDomElement().query('del');
        Ext.Array.each(allDelNodes, function(delNode, index) {
            rangeForDelNode.selectNodeContents(delNode);
            bookmarkForDelNode = rangeForDelNode.getBookmark();
            me.consoleLog("- bookmarkForDelNode: " + bookmarkForDelNode.start + " / " + bookmarkForDelNode.end);
            if (bookmarkForDelNode.start > matchStart && bookmarkForDelNode.end > matchEnd) {
                me.consoleLog("- we are already behind the match: " + bookmarkForDelNode.start + " > " + matchStart + " && " + bookmarkForDelNode.end + " > " + matchEnd);
                return false; // break here; we are already behind the match
            }
            lengthOfDelNode = rangeForDelNode.text().length;
            me.consoleLog("- length: " + lengthOfDelNode);
            if (bookmarkForDelNode.start <= matchStart) {
                matchStart = matchStart + lengthOfDelNode;
                matchEnd = matchEnd + lengthOfDelNode;
                me.consoleLog("- match NOW (start and end moved): " + matchStart + " / " + matchEnd);
            } else if (bookmarkForDelNode.end <= matchEnd) {
                matchEnd = matchEnd + lengthOfDelNode;
                me.consoleLog("- match NOW (only end moved): " + matchStart + " / " + matchEnd);
            }
        });
        rangeForMatch.selectCharacters(me.getEditorBody(),matchStart,matchEnd);
        return rangeForMatch.getBookmark();
    },
    /**
     * extract data from match: css according to issueType.
     * @param {Object} match
     * @returns {String}
     */
    getCSSForMatchFromTool: function (match) {
        var me = this,
            cssForMatch = {
              // match.rule.issueType : CSS-classname; see me.injectCSSForEditor()
                'misspelling'         : me.self.CSS_CLASSNAME_SPELLERROR,
                'register'            : me.self.CSS_CLASSNAME_SUGGESTION,
                'typographical'       : me.self.CSS_CLASSNAME_GRAMMERERROR,
                'uncategorized'       : me.self.CSS_CLASSNAME_GRAMMERERROR,
                'whitespace'          : me.self.CSS_CLASSNAME_GRAMMERERROR,
                'default'             : ''
              };
          return (cssForMatch[match.rule.issueType] || cssForMatch['default']);
    },
    /**
     * extract data from match: message
     * @param {Object} match
     * @returns {String}
     */
    getMessageForMatchFromTool: function (match) {
        return match.message;
    },
    /**
     * extract data from match: replacement(s)
     * @param {Object} match
     * @returns {Array}
     */
    getReplacementsForMatchFromTool: function (match) {
        var me = this,
            replacements = [];
        Ext.Array.each(match.replacements, function(replacement, index) {
            Ext.Array.push(replacements, replacement.value );
        });
        return replacements;
    },
    /**
     * extract data from match: URL(s) for more information
     * @param {Object} match
     * @returns {Array}
     */
    getInfoURLsForMatchFromTool: function (match) {
        var me = this,
            infoURLs = [];
        Ext.Array.each(match.rule.urls, function(url, index) {
            Ext.Array.push(infoURLs, url.value );
        });
        return infoURLs;
    }
});