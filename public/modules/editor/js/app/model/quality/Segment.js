
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
 * The model for a segment's quality entry
 */
Ext.define('Editor.model.quality.Segment', {
    extend: 'Ext.data.Model',
    fields: [
        { name:'id', type:'int' },
        { name:'segmentId', type:'int' },
        { name:'field', type:'string' },
        { name:'type', type:'string' },
        { name:'typeTitle', type:'string' },
        { name:'category', type:'string' },
        { name:'categoryIndex', type:'int' },
        { name:'title', type:'string' },
        { name:'falsePositive', type:'int' },
        { name:'filterable', type:'boolean' },
        { name:'falsifiable', type:'boolean' },
        { name:'hasTag', type:'boolean' },
        { name:'tagName', type:'string' },
        { name:'cssClass', type:'string' }
    ],
    idProperty: 'id',
    proxy : {
      type : 'rest',
      url: Editor.data.restpath+'quality/segment',
      reader : {
        rootProperty: 'rows',
        type : 'json'
      }
    }
});
