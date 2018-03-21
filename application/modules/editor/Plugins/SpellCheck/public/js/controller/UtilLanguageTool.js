
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
 * Mixin with Helpers regarding the LanguageTool
 * @class Editor.plugins.TrackChanges.controller.UtilLanguageTool
 */
Ext.define('Editor.plugins.SpellCheck.controller.UtilLanguageTool', {
    
    urlCheck: 'http://translate5.local:8081/v2/check',         // TODO: set url according to user's configuration
    urlLanguages: 'http://translate5.local:8081/v2/languages', // TODO: set url according to user's configuration
    
    languageToCheck: null,
    
    /**
     * Start of checking if the targetLangCode for the current taskId is supported by LanguageTool.
     */
    setLanguageSupportWithTool: function() {
        var me = this,
            url = me.urlLanguages;
        Ext.Ajax.request({
            url:url,
            method:"GET",
            headers: {
                //CORS
                //'Access-Control-Allow-Origin': '*',
                //'Access-Control-Allow-Headers': 'X-Requested-With',
                //JSON
                'Content-Type': 'application/json'
            },
            success: function(response){
                me.consoleLog('checkSupportedLanguagesWithTool (LanguageTool) done.');
                // TODO: CORS-problem
                // supportedLanguages = Ext.util.JSON.decode(response.responseText);
                // me.setIsSupportedLanguage(supportedLanguages);
            },
            failure: function(response){
                me.consoleLog('checkSupportedLanguagesWithTool (LanguageTool) failed: ' + response.status);
                // TODO: DEV only! ----------
                var responseText = '[{"name":"Asturian","code":"ast","longCode":"ast-ES"},{"name":"Belarusian","code":"be","longCode":"be-BY"},{"name":"Breton","code":"br","longCode":"br-FR"},{"name":"Catalan","code":"ca","longCode":"ca-ES"},{"name":"Catalan (Valencian)","code":"ca","longCode":"ca-ES-valencia"},{"name":"Chinese","code":"zh","longCode":"zh-CN"},{"name":"Danish","code":"da","longCode":"da-DK"},{"name":"Dutch","code":"nl","longCode":"nl"},{"name":"English","code":"en","longCode":"en"},{"name":"English (Australian)","code":"en","longCode":"en-AU"},{"name":"English (Canadian)","code":"en","longCode":"en-CA"},{"name":"English (GB)","code":"en","longCode":"en-GB"},{"name":"English (New Zealand)","code":"en","longCode":"en-NZ"},{"name":"English (South African)","code":"en","longCode":"en-ZA"},{"name":"English (US)","code":"en","longCode":"en-US"},{"name":"Esperanto","code":"eo","longCode":"eo"},{"name":"French","code":"fr","longCode":"fr"},{"name":"Galician","code":"gl","longCode":"gl-ES"},{"name":"German","code":"de","longCode":"de"},{"name":"German (Austria)","code":"de","longCode":"de-AT"},{"name":"German (Germany)","code":"de","longCode":"de-DE"},{"name":"German (Swiss)","code":"de","longCode":"de-CH"},{"name":"Greek","code":"el","longCode":"el-GR"},{"name":"Italian","code":"it","longCode":"it"},{"name":"Japanese","code":"ja","longCode":"ja-JP"},{"name":"Khmer","code":"km","longCode":"km-KH"},{"name":"Persian","code":"fa","longCode":"fa"},{"name":"Polish","code":"pl","longCode":"pl-PL"},{"name":"Portuguese","code":"pt","longCode":"pt"},{"name":"Portuguese (Angola preAO)","code":"pt","longCode":"pt-AO"},{"name":"Portuguese (Brazil)","code":"pt","longCode":"pt-BR"},{"name":"Portuguese (Moçambique preAO)","code":"pt","longCode":"pt-MZ"},{"name":"Portuguese (Portugal)","code":"pt","longCode":"pt-PT"},{"name":"Romanian","code":"ro","longCode":"ro-RO"},{"name":"Russian","code":"ru","longCode":"ru-RU"},{"name":"Serbian","code":"sr","longCode":"sr"},{"name":"Serbian (Bosnia and Herzegovina)","code":"sr","longCode":"sr-BA"},{"name":"Serbian (Croatia)","code":"sr","longCode":"sr-HR"},{"name":"Serbian (Montenegro)","code":"sr","longCode":"sr-ME"},{"name":"Serbian (Serbia)","code":"sr","longCode":"sr-RS"},{"name":"Simple German","code":"de-DE-x-simple-language","longCode":"de-DE-x-simple-language"},{"name":"Slovak","code":"sk","longCode":"sk-SK"},{"name":"Slovenian","code":"sl","longCode":"sl-SI"},{"name":"Spanish","code":"es","longCode":"es"},{"name":"Swedish","code":"sv","longCode":"sv"},{"name":"Tagalog","code":"tl","longCode":"tl-PH"},{"name":"Tamil","code":"ta","longCode":"ta-IN"},{"name":"Ukrainian","code":"uk","longCode":"uk-UA"}]';
                supportedLanguages = Ext.util.JSON.decode(responseText);
                me.setIsSupportedLanguage(supportedLanguages);
                // -------------------------
            }
        });
    },
    /**
     * Checks if the language of the current task is supported 
     * and stores the result in me.isSupportedLanguage.
     * @param {Array} supportedLanguages
     */
    setIsSupportedLanguage: function (supportedLanguages) {
        var me = this;
        me.isSupportedLanguage = false;
        Ext.Array.each(supportedLanguages, function(lang, index) {
            if (lang.code == me.targetLangCode) {
                me.languageToCheck = lang; // for further inner purposes of LT
                me.isSupportedLanguage = true;
                return false; // break the iteration
            }
        });
    },
    /**
     * 
     * @param {String} text
     */
    runSpellCheckWithTool: function (textToCheck) {
        var me = this,
            url = me.urlCheck,
            params = {
                text: textToCheck,
                language: me.languageToCheck.longCode
            },
            resultLT,
            matches = [];
        
        Ext.Ajax.request({
            url:url,
            method:"POST",
            headers: {
                'Content-Type': 'application/json'
            },
            params:params,
            jsonData: true, // otherwise: "No parameters for this request"
            success: function(response){
                me.consoleLog('runSpellCheckWithTool (LanguageTool) done.');
                // TODO: SyntaxError: JSON.parse: unexpected end of data at line 1 column 1 of the JSON data
                // resultLT = Ext.util.JSON.decode(response.responseText);
                // matches = resultLT.matches;
                // me.applySpellCheck(matches);
            },
            failure: function(response){
                me.consoleLog('runSpellCheckWithTool (LanguageTool) failed: ' + response.status);
                // TODO: DEV only! ----------
                var responseText = '{"software":{"name":"LanguageTool","version":"4.1-SNAPSHOT","buildDate":"2018-03-15 21:01","apiVersion":1,"status":""},"warnings":{"incompleteResults":false},"language":{"name":"German (Germany)","code":"de-DE"},"matches":[{"message":"Möglicher Rechtschreibfehler gefunden","shortMessage":"Rechtschreibfehler","replacements":[{"value":"aßt"},{"value":"äst"},{"value":"ist"},{"value":"alt"},{"value":"Ost"},{"value":"ad"},{"value":"aß"},{"value":"Ast"},{"value":"Gst"},{"value":"ast-"},{"value":"äse"},{"value":"äste"},{"value":"äße"},{"value":"und"},{"value":"mit"},{"value":"als"},{"value":"an"},{"value":"auf"},{"value":"aus"},{"value":"das"}],"offset":2,"length":3,"context":{"text":"  asd","offset":2,"length":3},"sentence":"asd","rule":{"id":"GERMAN_SPELLER_RULE","description":"Möglicher Rechtschreibfehler","issueType":"misspelling","category":{"id":"TYPOS","name":"Mögliche Tippfehler"}}}]}';
                resultLT = Ext.util.JSON.decode(responseText);
                matches = resultLT.matches;
                me.applySpellCheck(matches);
                // -------------------------
            }
        });
    }

});