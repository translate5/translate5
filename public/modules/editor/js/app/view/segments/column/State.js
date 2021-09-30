
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
 * @class Editor.view.segments.column.State
 * @extends Editor.view.ui.segments.column.State
 * @initalGenerated
 */
Ext.define('Editor.view.segments.column.State', {
  extend: 'Editor.view.ui.segments.column.State',
  alias: 'widget.stateColumn',
  mixins: ['Editor.view.segments.column.BaseMixin'],
  stateData: {},
  showInMetaTooltip: true,
  filter: {
      type: 'list',
      labelField: 'label',
      phpMode: false,
      options: Editor.data.segments.stateFlags
  },
  
  initComponent: function() {
    var me = this;
    me.initBaseMixin();
    me.callParent(arguments);
  },
  /**
   * rendert die Semikolon separierten Status Werte mit Zeilenumbrüchen als Trenner
   * @param {Integer} value
   * @param {Object} t
   * @param {Editor.model.Segment} record
   * @returns {String}
   */
  renderer: function(value,t,record){
    if(value == 0){
      return '';
    }
    //bound to the grid!
    if(this.stateData[value]){
      return this.stateData[value];
    }
    return value;
  }
});