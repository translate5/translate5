
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
                var responseText = '{"software":{"name":"LanguageTool","version":"4.1-SNAPSHOT","buildDate":"2018-03-21 21:01","apiVersion":1,"status":""},"warnings":{"incompleteResults":false},"language":{"name":"German","code":"de"},"matches":[{"message":"Dieser Satz fängt nicht mit einem großgeschriebenen Wort an","shortMessage":"","replacements":[{"value":"Oder"}],"offset":100,"length":4,"context":{"text":"...auf die farbig unterlegten Textstellen. oder nutzen Sie diesen Text als Beispiel für...","offset":43,"length":4},"sentence":"oder nutzen Sie diesen Text als Beispiel für ein Paar Fehler , die LanguageTool erkennen kann: Ihm wurde Angst und bange.","rule":{"id":"UPPERCASE_SENTENCE_START","description":"Großschreibung am Satzanfang","issueType":"typographical","category":{"id":"CASING","name":"Groß-/Kleinschreibung"}}},{"message":"Meinten Sie ein paar (=einige), im Unterschied zu ein Paar (=genau zwei)?","shortMessage":"Bitte überprüfen Sie die Groß-/Kleinschreibung.","replacements":[{"value":"ein paar"}],"offset":145,"length":8,"context":{"text":"...nutzen Sie diesen Text als Beispiel für ein Paar Fehler , die LanguageTool erkennen kann...","offset":43,"length":8},"sentence":"oder nutzen Sie diesen Text als Beispiel für ein Paar Fehler , die LanguageTool erkennen kann: Ihm wurde Angst und bange.","rule":{"id":"EIN_PAAR","subId":"2","description":"Paar vs. paar","issueType":"uncategorized","urls":[{"value":"http://www.canoo.net/services/GermanSpelling/Regeln/Gross-klein/Denominalisierung.html#Anchor-Die-14210"}],"category":{"id":"CASING","name":"Groß-/Kleinschreibung"}}},{"message":"Nur hinter einem Komma steht ein Leerzeichen, aber nicht davor.","shortMessage":"","replacements":[{"value":","}],"offset":160,"length":2,"context":{"text":"...en Text als Beispiel für ein Paar Fehler , die LanguageTool erkennen kann: Ihm wur...","offset":43,"length":2},"sentence":"oder nutzen Sie diesen Text als Beispiel für ein Paar Fehler , die LanguageTool erkennen kann: Ihm wurde Angst und bange.","rule":{"id":"COMMA_PARENTHESIS_WHITESPACE","description":"Leerzeichen vor/hinter Kommas und Klammern","issueType":"whitespace","category":{"id":"TYPOGRAPHY","name":"Typographie"}}},{"message":"In der Wendung angst und bange werden/sein werden angst und bange kleingeschrieben.","shortMessage":"Bitte überprüfen Sie die Groß-/Kleinschreibung.","replacements":[{"value":"angst und bange"}],"offset":205,"length":15,"context":{"text":"...e LanguageTool erkennen kann: Ihm wurde Angst und bange. Mögliche stilistische Probleme werden ...","offset":43,"length":15},"sentence":"oder nutzen Sie diesen Text als Beispiel für ein Paar Fehler , die LanguageTool erkennen kann: Ihm wurde Angst und bange.","rule":{"id":"ANGST_UND_BANGE","subId":"2","description":"Angst/angst und Bange/bange","issueType":"uncategorized","urls":[{"value":"http://www.canoo.net/services/GermanSpelling/Regeln/Gross-klein/Denominalisierung.html#Anchor-Die-49575"}],"category":{"id":"CASING","name":"Groß-/Kleinschreibung"}}},{"message":"besser wie ist umgangssprachlich. Verwenden Sie zum Ausdrücken von Ungleichheit als.","shortMessage":"","replacements":[{"value":"als"}],"offset":295,"length":3,"context":{"text":"...rden blau hervorgehoben: Das ist besser wie vor drei Jahren. Eine Rechtschreibprüfu...","offset":43,"length":3},"sentence":"Mögliche stilistische Probleme werden blau hervorgehoben: Das ist besser wie vor drei Jahren.","rule":{"id":"KOMP_WIE","subId":"3","description":"besser wie (als)","issueType":"register","category":{"id":"COLLOQUIALISMS","name":"Umgangssprache"}}},{"message":"Möglicherweise ist hier ein Wort zu viel oder es fehlt ein Komma.","shortMessage":"Möglicherweise ist hier ein Wort zu viel oder es fehlt ein Komma","replacements":[{"value":"findet"},{"value":"findet, findet"}],"offset":340,"length":13,"context":{"text":"...or drei Jahren. Eine Rechtschreibprüfun findet findet übrigens auch statt. Donnerstag, den 27...","offset":43,"length":13},"sentence":"Eine Rechtschreibprüfun findet findet übrigens auch statt.","rule":{"id":"SAGT_RUFT","subId":"1","description":"Zwei aufeinanderfolgende Verben","issueType":"uncategorized","category":{"id":"TYPOS","name":"Mögliche Tippfehler"}}},{"message":"Das Datum 27.06.2017 fällt nicht auf einen Donnerstag, sondern auf einen Dienstag.","shortMessage":"","replacements":[],"offset":375,"length":26,"context":{"text":"...üfun findet findet übrigens auch statt. Donnerstag, den 27.06.2017 wurde LanguageTool 3.8 veröffentlicht.","offset":43,"length":26},"sentence":"Donnerstag, den 27.06.2017 wurde LanguageTool 3.8 veröffentlicht.","rule":{"id":"DATUM_WOCHENTAG","subId":"1","description":"Wochentag passt nicht zum Datum","issueType":"uncategorized","category":{"id":"SEMANTICS","name":"Semantische Unstimmigkeiten"}}}]}';
                //resultLT = Ext.util.JSON.decode(responseText);
                resultLT = JSON.parse(responseText); // Ext.util.JSON.decode sometimes throws an exception that the responseText is not valid JSON
                matches = resultLT.matches;
                me.applySpellCheck(matches);
                // -------------------------
            }
        });
    },
    /**
     * Turn matches into ranges.
     * @param {Object} matches
     * @returns {Array} allRangesForMatches
     */
    getRangesForMatchesFromTool: function (matches) {
        var me = this,
            editorBody,
            rangeForMatch,
            matchStart,
            matchEnd,
            allRangesForMatches = [];
        if (matches.length > 0) {
            editorBody = me.editor.getEditorBody();
            Ext.Array.each(matches, function(match, index) {
                rangeForMatch = rangy.createRange(editorBody);
                matchStart = match.offset;
                matchEnd = matchStart + match.context.length;
                rangeForMatch.selectCharacters(editorBody,matchStart,matchEnd);
                Ext.Array.push(allRangesForMatches, rangeForMatch);
            });
        }
        return allRangesForMatches;
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