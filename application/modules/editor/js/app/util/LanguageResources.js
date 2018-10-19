
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
 translate5: Please see http://www.translate5.net/plugin-exception.txt or 
 plugin-exception.txt in the root folder of translate5.
  
 @copyright  Marc Mittag, MittagQI - Quality Informatics
 @author     MittagQI - Quality Informatics
 @license    GNU AFFERO GENERAL PUBLIC LICENSE version 3 with plugin-execption
			 http://www.gnu.org/licenses/agpl.html http://www.translate5.net/plugin-exception.txt

END LICENSE AND COPYRIGHT
*/

/**
 * @class Editor.util.LanguageResources
 */
Ext.define('Editor.util.LanguageResources', {
	strings: {
    	exactMatch:'#UT#100% matches mit demselben Dokumentnamen wie das aktuell übersetzte Dokument',
        repetitionMatch:'#UT#Eine Wiederholung ist ein Segment, das bereits bei derselben Aufgabe mit der gleichen Wort- und Tag-Reihenfolge weiter oben auftauchte',
        contextMatch:'#UT#103% ist eine exact-exact-match(101% match), bei der zusätzlich der gleiche Kontext in TM wie im Dokument festgelegt ist'
    },
    
    statics: {
    	/***
    	 * Static function for matchrate tooltip
    	 */
    	getMatchrateTooltip:function(matchrate){
    		return new this().getMatchrateTooltip(matchrate);
    	}
    },
    
    /***
     *  Get the match rate (only for 103,102,101 matches) tooltip depending of the match rate percent
     */
    getMatchrateTooltip:function(matchrate){
    	matchrate=parseInt(matchrate);
    	switch(matchrate) {
    		case 101:
		    	return this.strings.exactMatch;
		        break;
		    case 102:
		    	return this.strings.repetitionMatch;
		        break;
		    case 103:
		    	return this.strings.contextMatch;
		        break;
		    default:
		    	return "";
		}
    }
});
