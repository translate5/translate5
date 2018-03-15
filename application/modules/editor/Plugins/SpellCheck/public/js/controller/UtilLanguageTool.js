
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
    
    /**
     * Checks if the given targetLangCode language is supported by LanguageTool.
     * If yes, we will fire an event and thus start the SpellChecker.
     * Returns an Array with the rfc5646-Codes.
     * @returns {Array}
     */
    checkSupportedLanguages: function(targetLangCode) {
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
                me.consoleLog('HURRA! Next step: Sprache prüfen und wenn ok fireEvent für SpellCheck.');
                //var obj = Ext.decode(response.responseText);
                //console.dir(obj);
                //me.renderArrayWithSupportedLanguageCodes(response);
                // if Ext.Array.contains(supportedLanguages,targetLangCode);
                // => fire event
            },
            failure: function(response){
                me.consoleLog('getSupportedLanguages from LanguageTool failed: ' + response.status);
            }
        });
    }

});