
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
 * Mixin with Helpers regarding the LanguageTool: https://languagetool.org/
 * 
 * Some of this could already be done by PHP instead of handling the data here.
 * For performance however it might be even better to use the user's browser 
 * instead of the server (that must handle many requests anyway). And the data 
 * that we transfer has just a few KB and thus doesn't make a big difference anyway.
 * 
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
                me.consoleLog('- Checking supported languages (LanguageTool) done.');
                resultLT = Ext.util.JSON.decode(response.responseText);
                me.setIsSupportedLanguage(resultLT);
            },
            failure: function(response){
                me.consoleLog('- Checking supported languages (LanguageTool) failed: ' + response.status);
                me.terminateSpellCheck();
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
        if (resultLT.rows === false) {
            me.isSupportedLanguage = false;
            me.languageToCheckLongCode = null;
            me.consoleLog('=> isSupportedLanguage: ' + me.isSupportedLanguage + ' (' + me.languageToCheckLongCode + ')');
        } else {
            me.isSupportedLanguage = true;
            me.languageToCheckLongCode = resultLT.rows.longCode;
            me.consoleLog('=> isSupportedLanguage: ' + me.isSupportedLanguage + ' (' + me.languageToCheckLongCode + ')');
            me.initSpellCheckInEditor();
        }
    },
    /**
     * 
     * @param {String} text
     */
    runSpellCheckWithTool: function (textToCheck,spellCheckProcessID) {
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
                    //check if the response contains result(matches)
                    if (resultLT && resultLT.rows && resultLT.rows.matches) {
                        me.allMatchesOfTool = resultLT.rows.matches;
                        me.applySpellCheck(spellCheckProcessID);
                    } else {
                        me.finishSpellCheck(spellCheckProcessID);
                    }
                },
                failure: function(response){
                    me.consoleLog('runSpellCheckWithTool (LanguageTool) failed: ' + response.status);
                    me.terminateSpellCheck();
                } 
            });
    },
    /**
     * extract data from match: get (character-only-offset-)start of match
     * @param {Object} match
     * @returns {Integer}
     */
    getStartOfMatchFromTool: function (match) {
        return match.offset;
    },
    /**
     * extract data from match: get (character-only-offset-)end of match
     * @param {Object} match
     * @returns {Integer}
     */
    getEndOfMatchFromTool: function (match) {
        return  match.offset + match.context.length;
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
        var replacements = [];
        Ext.Array.each(match.replacements, function(replacement) {
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
        var infoURLs = [];
        Ext.Array.each(match.rule.urls, function(url) {
            Ext.Array.push(infoURLs, url.value );
        });
        return infoURLs;
    }
});