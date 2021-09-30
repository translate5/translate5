
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

Ext.define('Editor.view.searchandreplace.SearchTab', {
    extend:'Ext.form.Panel',
    xtype:'searchTab',
    alias:'widget.searchTab',
    itemId:'searchTab',
    
    
    requires:[
        'Editor.view.searchandreplace.SearchReplaceViewModel',
        'Editor.view.searchandreplace.SearchTabViewController',
    ],
    viewModel: {
        type: 'searchreplaceviewmodel'
    },
    controller:'searchtabviewcontroller',
    
    closable:false,
    
    strings:{
      comboFieldLabel:'#UT#Suchen nach',//Search for
      searchInField:'#UT#Suchen in',
      matchCaseLabel:'#UT#Groß/Kleinschreibung beachten',//Match case
      towardsTop:'#UT#Nach oben suchen',//Search towards the top
      useForSearch:'#UT#Bei der Suche verwenden',//Use for search
      normalSearch:'#UT#Normal (Standard)',//Normal (default)
      wildcardsSearch:'#UT#Wildcards (* und ?)',
      regularExpressionSearch:'#UT#Regulärer Ausdruck',
      saveCurrentOpen:'#UT#Segment beim Schließen speichern',//Save segment on close
      invalidRegularExpression:'#UT#Ungültiger Regulärer Ausdruck',
      unsupportedRegularExpression:'#UT#Dieser reguläre Ausdruck wird nicht unterstützt',
      segmentMatchInfoMessage:'#UT#Segmente mit Suchtreffer:',
      searchInLockedSegments:'#UT#Suche in gesperrten Segmenten'
    },
    
    padding:'10 10 10 10',
    
    initConfig : function(instanceConfig) {
        var me = this,
        config = {
                items:[{
                    xtype:'textfield',
                    itemId:'searchField',
                    allowBlank:false,
                    maxLength:1024,
                    name:'searchField',
                    focusable:true,
                    enableKeyEvents:true,
                    fieldLabel:me.strings.comboFieldLabel,
                    validator:me.validateSearchField,
                    listeners:{
                        change:'onSearchFieldTextChange'
                    }
                },{
                    xtype:'combo',
                    itemId:'searchInField',
                    name:'searchInField',
                    allowBlank:false,
                    fieldLabel:me.strings.searchInField,
                    displayField:'value',
                    valueField:'id',
                    forceSelection:true,
                    allowBlank:false,
                    editable: false,
                    enableKeyEvents:true,
                    listeners:{
                        select:'resetSearchParameters'
                    }
                },{
                    xtype:'checkbox',
                    itemId:'matchCase',
                    name:'matchCase',
                    boxLabel:me.strings.matchCaseLabel,
                    listeners:{
                        change:'resetSearchParameters'
                    }
                },{
                    xtype:'checkbox',
                    itemId:'searchTopChekbox',
                    name:'searchTopChekbox',
                    boxLabel:me.strings.towardsTop
                },{
                    xtype      : 'fieldcontainer',
                    fieldLabel : me.strings.useForSearch,
                    defaultType: 'radiofield',
                    defaults: {
                        flex: 1,
                        listeners:{
                            change:'onSearchTypeChange'
                        }
                    },
                    layout: 'vbox',
                    items: [
                        {
                            boxLabel  : me.strings.normalSearch,
                            name      : 'searchType',
                            inputValue: 'normalSearch'
                        }, {
                            boxLabel  : me.strings.wildcardsSearch,
                            name      : 'searchType',
                            inputValue: 'wildcardsSearch',
                        }, {
                            boxLabel  : me.strings.regularExpressionSearch,
                            name      : 'searchType',
                            inputValue: 'regularExpressionSearch',
                        }
                    ]
                },{
                    xtype: 'label',
                    bind:{
                        text:me.strings.segmentMatchInfoMessage+' {getResultsCount}',
                        visible:'{showResultsLabel}'
                    }
                },{
                    xtype:'checkbox',
                    itemId:'saveCurrentOpen',
                    name:'saveCurrentOpen',
                    bind:{
                        value:'{!isSearchView}'
                    },
                    boxLabel:me.strings.saveCurrentOpen
                },{
                    xtype:'checkbox',
                    itemId:'searchInLockedSegments',
                    name:'searchInLockedSegments',
                    boxLabel:me.strings.searchInLockedSegments,
                    bind:{
                        visible:'{isSearchView}'
                    },
                    listeners:{
                        change:'resetSearchParameters'
                    }
                }]
        };
        if (instanceConfig) {
            me.self.getConfigurator().merge(me, config, instanceConfig);
        }
        return me.callParent([config]);
    },
    
    /***
     * Regex not supported by mysql
     */
    blackListRegex:[
        //hexadecimal and unicodes should be tested on real cases
        ///\\x[a-fA-F0-9]{2}/g,//Hexadecimal escape | \xFF where FF are 2 hexadecimal digits | Matches the character at the specified position in the code page  |  \xA9 matches © when using the Latin-1 code page
        ///\\u[a-fA-F0-9]{4}/g,//Matches a specific Unicode code point.
        
        //this is a tag specific regex. Here is needed different approche (tabs,linebrakes etc.. are saved as tags in the db)
        /\\n/g,//Character escape
        /\\r/g,//Character escape
        /\\t/g,//Character escape
        /\\v/g,//Character escape
        /\\b/g,//javascript: [\b\t] matches a backspace or a tab character.
    ],
    
    /***
     * Regex not supported by older database versions
     */
    blackListRegexOldDbVersion:[
        /\\x[a-fA-F0-9]{2}/g,//Hexadecimal escape | \xFF where FF are 2 hexadecimal digits | Matches the character at the specified position in the code page  |  \xA9 matches © when using the Latin-1 code page
        /\\n/g,//Character escape
        /\\r/g,//Character escape
        /\\t/g,//Character escape
        /\\f/g,//Character escape
        /\\v/g,//Character escape
        /\\c[a-zA-Z]/g,//Control character escape \cA through \cZ Match an ASCII character Control+A through Control+Z, equivalent to \x01 through \x1A   \cM\cJ matches a Windows CRLF line break
        //Control character escape \ca through \cz Match an ASCII character Control+A through Control+Z, equivalent to \x01 through \x1A   \cm\cj matches a Windows CRLF line break
        /\\0/g,//NULL escape
        /\\(?:[1-7][0-7]{0,2}|[0-7]{2,3})/g,//Octal escape
        /\\[\^\]\-]/g,//\ (backslash) followed by any of ^-]\
        /\\b/g,//javascript: [\b\t] matches a backspace or a tab character.
        /\\B/g,//javascript: \B. matches b, c, e, and f in abc def
        /\\d/g,//Shorthand Character Classes
        /\\D/g,//Shorthand Character Classes
        /\\s/g,//Shorthand Character Classes
        /\\S/g,//Shorthand Character Classes
        /\\w/g,//Shorthand Character Classes
        /\\W/g,//Shorthand Character Classes
        /\\h/g,//Shorthand Character Classes
        /\?\?/g,//abc?? matches ab or abc
        /\*\?/g,//".*?" matches "def" and "ghi" in abc "def" "ghi" jkl
        /\+\?/g,//".+?" matches "def" and "ghi" in abc "def" "ghi" jkl
        /{[0-9],[0-9]}\?/g,
        /{[0-9],}\?/g,
        /\\u[a-fA-F0-9]{4}/g,//Matches a specific Unicode code point.
        /\(\?\:.*?\)/g,//Non-capturing parentheses group the regex so you can apply regex operators, but do not capture anything.
        /\(.*?\)=\\[0-9]/g,//(abc|def)=\1 matches abc=abc or def=def, but not abc=def or def=abc.
        /\(\?\=.*?\)/g,//Matches at a position where the pattern inside the lookahead can be matched. Matches only the position. It does not consume any characters or expand the match. In a pattern like one(?=two)three, both two and three have to match at the position where the match of one ends.
        /\(\?\!.*?\)/g,//Similar to positive lookahead, except that negative lookahead only succeeds if the regex inside the lookahead fails to match.
        /\[\:(.*)\:\]/g,
    ],
    /***
     * Search field validator
     */
    validateSearchField:function(val){
        var tabPanel=this.up('#searchreplacetabpanel'),
            searchTab=tabPanel.down('#searchTab'),
            tabPanelviewModel=tabPanel.getViewModel(),
            activeTab=tabPanel.getActiveTab(),
            searchType=activeTab.down('radiofield').getGroupValue();
        
        if(searchType==="regularExpressionSearch"){
            try {
                new RegExp(val);
            } catch (e) {
                tabPanelviewModel.set('disableSearchButton',true);
                return activeTab.strings.invalidRegularExpression;
            }
            
            if(val!==""){
                var isMysql =Editor.data.dbVersion && ( Editor.data.dbVersion.startsWith("5") || Editor.data.dbVersion.startsWith("8")),
                    version =Editor.data.dbVersion &&  parseFloat(Editor.data.dbVersion),
                    blArray=searchTab.blackListRegexOldDbVersion;
                
                //TODO: (mysql update) remove this check when we update to the mysql 8 or mariadb
                //is mysql and version is > 7 
                if(isMysql && version>7){
                    blArray=searchTab.blackListRegex;
                }
                
                //is maria db and version > 10.1
                if(!isMysql && version>10.1){
                    blArray=searchTab.blackListRegex;
                }
                
                var arrLength=blArray.length;
                for (var i = 0; i < arrLength; i++){
                    var arrayRegex=blArray[i];
                    if(searchTab.isRegexMatch(arrayRegex,val)){
                        tabPanelviewModel.set('disableSearchButton',true);
                        return activeTab.strings.unsupportedRegularExpression;
                    }
                }
            }
        }
        tabPanelviewModel.set('disableSearchButton',val===null || val==="");
        return val!=null || val!=="";
    },
    
    /***
     * Check if given regex will match in the input string
     */
    isRegexMatch:function(arrayRegex,inputString){
        var regex=arrayRegex,
            m,
            isMatch=false;

        while ((m = regex.exec(inputString)) !== null) {
            // The result can be accessed through the `m`-variable.

            // This is necessary to avoid infinite loops with zero-width matches
            if (m.index === regex.lastIndex) {
                regex.lastIndex++;
            }
            isMatch=true;
        }
        
        return isMatch;
    }
    
});