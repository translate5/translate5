
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
 * @class Editor.model.Remark
 * @extends Ext.data.Model
 */
 Ext.define('Editor.model.Remark', {
    extend: 'Ext.data.Model',
    idProperty: 'id',
    proxy : {
      type : 'rest',
      url: Editor.data.restpath+'commentnav',
      reader : {
        rootProperty: 'rows',
        type : 'json'
      },
      writer: {
        encode: true,
        rootProperty: 'data',
        writeAllFields: false
      }
    },
    fields: [
      {name: 'id', type: 'int'},
      {name: 'dbId', type: 'int'},
      {name: 'segmentId', type: 'int'},
      {name: 'userName', type: 'string', mapping: function(data){
          if(data.userName) return data.userName;
          var ret = '';
          if(data.firstName) ret += data.firstName;
          if(data.surName) ret += (ret && ' ') + data.surName;
          if(!ret) ret = 'Anonymous';
          return ret;
        }
      },
      {name: 'comment', type: 'string'},
      {name: 'modified', type: 'string', dateFormat: Editor.DATE_ISO_FORMAT, mapping:'updated'},
      {name: 'created', type: 'date', dateFormat: Editor.DATE_ISO_FORMAT},
      {name: 'reviewFileId', type: 'integer'},
      {name: 'page', type: 'string',  default: '0' },
      {name: 'pageNum', type: 'int' },
      {name: 'x', type: 'number', default: -1},
      {name: 'y', type: 'number', default: -1},
      {name: 'timecode', type: 'int', default: -1},
      // this is either "segmentComment" or "visualAnnotation"
      {name: 'type', type: 'string'},
    ],
    /**
     * Returns the hexadecimal no. of a virtual page in a pdfconverter output. This no are either segment-attributes "data-t5segment-page-nr" in the Markup or as "data-page-no" attributes of a page node
     * @returns {String}
     */
    getPageHexNo: function(){
        return this.get('page');
    },
    /**
     * Returns the parsed page number as used in the iframe dom controller
     * @returns {Number}
     */
    getPageNr: function(){
        return parseInt(this.get('page'), 10);
    },
    /**
     * ATTENTION: when adding visual annotations their "id" may not be the id from the database !!
     * Returns the real id of the item in the database
     * @returns {Number}
     */
    getDatabaseId: function(){
        return this.get('dbId');
    }    
  });