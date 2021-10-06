
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
 * @class Editor.view.LanguageResources.TbxExportViewController
 * @extends Ext.app.ViewController
 */
Ext.define('Editor.view.LanguageResources.TbxExportViewController', {
    extend: 'Ext.app.ViewController',
    alias: 'controller.tbxexport',
    
    /***
     * Export term and term attribute proposals for the given collection id.
     * All terms and attributes younger than the export date will be exported.
     */
    exportTbx:function(tbxBasicOnly, exportImages, record) {
    	var params = {},
	    	url = Editor.data.restpath+'languageresourceinstance/tbxexport?';
		tbxBasicOnly.up('window').destroy();
		params['collectionId'] = record.get('id');
	  	params['tbxBasicOnly'] = tbxBasicOnly.getValue() ? 1 : 0;
	  	params['exportImages'] = exportImages.getValue() ? 1 : 0;
	  	window.open(url+Ext.urlEncode(params));
    }
});