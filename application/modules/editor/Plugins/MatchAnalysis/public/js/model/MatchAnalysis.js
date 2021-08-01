
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