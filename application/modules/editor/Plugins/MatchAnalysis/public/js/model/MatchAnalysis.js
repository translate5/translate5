
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

/**#@++
 * @author Marc Mittag
 * @package editor
 * @version 1.0
 *
 */
/**
 * @class Editor.plugins.MatchAnalysis.model.MatchAnalysis
 * @extends Ext.data.Model
 */
Ext.define('Editor.plugins.MatchAnalysis.model.MatchAnalysis', {
  extend: 'Ext.data.Model',
  
  fields: [
    {name: 'id', type: 'int'},
    {name: 'created'},
    {name: '103'},
    {name: '102'},
    {name: '101'},
    {name: '100'},
    {name: '99'},//99-90
    {name: '89'},//89-80
    {name: '79'},//79-70
    {name: '69'},//69-60
    {name: '59'},//59-51
    {name: 'noMatch'},//50-0
    {name: 'wordCountTotal',
    	convert: function(val,row) {
    		//sum all in row columns
    		var ignoreSumColumns=[
    			  "created",
    			  "id",
    			  "resourceType",
    			  "resourceName",
    			  "resourceColor",
    			  "pretranslateMatchrate",
    			  "internalFuzzy"
    		  ],ts=0;
    		
    		for (var key in row.data) {
    		    // skip loop if the property is from prototype
    		    if (!row.data.hasOwnProperty(key)){
    		    	continue;	
    		    }

    		    //some culumns shuld not be included in the sum
    		    if(Ext.Array.contains(ignoreSumColumns,key)){
    		    	continue
		    	}
	        	ts+=row.data[key];
    		}
    		delete ignoreSumColumns;
    		return ts;
    	}    
    }
  ],
  idProperty: 'id',
  proxy : {
    type : 'rest', 
    url: Editor.data.restpath+'plugins_matchanalysis_matchanalysis',
    reader : {
      rootProperty: 'rows',
      type : 'json'
    },
    writer: {
      encode: true,
      rootProperty: 'data',
      writeAllFields: false
    }
  }
});