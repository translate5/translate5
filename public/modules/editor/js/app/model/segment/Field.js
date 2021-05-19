
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

/**#@++
 * @author Marc Mittag
 * @package editor
 * @version 1.0
 *
 */
/**
 * @class Editor.model.segment.Field
 * @extends Ext.data.Model
 */
Ext.define('Editor.model.segment.Field', {
    TYPE_SOURCE: 'source',
    TYPE_RELAIS: 'relais',
    TYPE_TARGET: 'target',
    typeSortOrder: {
        'source': 0,
        'relais': 1,
        'target': 2
    },
  extend: 'Ext.data.Model',
  statics: {
      listSort: function(fieldList) {
          return Ext.Array.sort(fieldList, function(one, two) {
              //sort the fieldList array first by type
              var typeA = one.typeSortOrder[one.get('type')];
                  typeB = two.typeSortOrder[two.get('type')];
              //for same type sort by import order (id)
              if(typeA == typeB) {
                  return one.get('id') - two.get('id');
              }
              return typeA - typeB;
          });
      },
      isDirectionRTL: function(fieldType) {
          var lang = Editor.data.taskLanguages[fieldType];
          return lang && lang.get('rtl');
      },
      getDirectionCls: function(fieldType) {
          return 'direction-' + (Editor.model.segment.Field.isDirectionRTL(fieldType) ? 'rtl' : 'ltr'); 
      }
  },
  fields: [
    {name: 'id', type: 'int'},
    {name: 'taskGuid', type: 'string'},
    {name: 'name', type: 'string'},
    {name: 'label', type: 'string'},
    {name: 'width', type: 'integer'},
    {name: 'type', type: 'string'},
    {name: 'rankable', type: 'boolean'}
  ],
  idProperty: 'id',
  isTarget: function() {
      return this.get('type') == this.TYPE_TARGET;
  }
});