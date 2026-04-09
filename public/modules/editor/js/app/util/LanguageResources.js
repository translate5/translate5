
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
 * @class Editor.util.LanguageResources
 */
Ext.define('Editor.util.LanguageResources', {
	strings: {
		exactMatch:'#UT#A 101% match is a 100% match that originates from a file with the same name.',
        repetitionMatch:'#UT#A 102% match is a repetition. A repetition is an identical match that reoccurs within the current task.',
		contextMatch:'#UT#The displayed match rate can result from TM penalties as follows:<br/>First value in brackets = original match rate<br/>Second value = set penalty deduction<br/>Third value = set sublanguage penalty',
		termcollectionMatch:'#UT#A 104% match is a 100% match coming from a TermCollection.'
    },
    
    statics: {
    	/***
    	 * Static function for matchrate tooltip
    	 */
    	getMatchrateTooltip:function(matchrate){
    		return new this().getMatchrateTooltip(matchrate);
		},
		
		resourceType:{
			TM:'tm',
			MT:'mt',
			TERM_COLLECTION:'termcollection'
		},
		
		/**
		 * Adds a service instance to the internal list. Usable for Plugins which deliver LanguageResource services. 
		 * The core languageResources are added by the LanguageResource controller
		 * addService and getService are used similar to the ServiceManager in the backend
		 * @param {Editor.view.LanguageResources.services.Default} serviceTypeInstance
		 */
		addService: function (serviceTypeInstance) {
		    if(!this.serviceInstances) {
		        this.serviceInstances = new Ext.util.Collection();
		    }
		    this.serviceInstances.add(serviceTypeInstance);
		},
		/**
		 * returns the service instance to the given service type, if no specific found the Default instance is returned
		 * @return {Editor.view.LanguageResources.services.Default}
		 */
		getService: function (serviceType) {
            var service = this.serviceInstances,
                result = service.get(serviceType);
            if(!result) {
                return service.get('Default');
            }
            return result;
		}
    },
    
    /***
     *  Get the match rate (only for 104,103,102,101 matches) tooltip depending of the match rate percent
     */
    getMatchrateTooltip:function(matchrate){
    	matchrate=parseInt(matchrate);
    	switch(matchrate) {
    		case 101:
		    	return this.strings.exactMatch;
		    case 102:
		    	return this.strings.repetitionMatch;
		    case 103:
		    	return this.strings.contextMatch;
			case 104:
		    	return this.strings.termcollectionMatch;
		    default:
		    	return "";
		}
    }
});
