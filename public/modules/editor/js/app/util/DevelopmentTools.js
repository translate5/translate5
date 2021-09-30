
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
 * @class Editor.util.DevelopmentTools
 */
Ext.define('Editor.util.DevelopmentTools', {
    
    USE_CONSOLE: false, // (true|false): use true for developing using the browser's console, otherwise use false
    
    /**
     * Write into the browser console depending on the setting of me.USE_CONSOLE.
     * @param {(String|Object)} outputForConsole
     */
    consoleLog: function(outputForConsole) {
        var me = this;
        if (me.USE_CONSOLE) {
            if (typeof outputForConsole === 'string' || outputForConsole instanceof String) {
                Ext.log({msg: outputForConsole});
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
    },
    statics:{
    	/***
    	 * print all events in the console fired from extjs component.
    	 * The cmp is without # 
    	 * Usage example:
    	 * Editor.util.DevelopmentTools.captureComponentEvents('projectTaskGrid')
    	 */
    	captureComponentEvents:function(cmp){
    		if(Ext.isString(cmp)){
    			var cmp=Ext.ComponentQuery.query('#'+cmp)[0];
    		}
    		Ext.util.Observable.capture(cmp, function(evname) {console.log(evname, arguments);})
    	},
    	
    	/***
    	 * relese the event capture by component itemId. The itemId is without #
    	 * Usage example:  
    	 * releaseCapture('projectTaskGrid')
    	 */
    	releaseCapture:function(cmp){
    		if(Ext.isString(cmp)){
    			var cmp=Ext.ComponentQuery.query('#'+cmp)[0];
    		}
    		Ext.util.Observable.releaseCapture(cmp);
    	}
    }
    
});