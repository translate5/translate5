
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
 * @class Editor.view.segments.column.Quality
 * @extends Editor.view.ui.segments.column.Quality
 * @initalGenerated
 */
Ext.define('Editor.view.segments.column.Quality', {
  extend: 'Editor.view.ui.segments.column.Quality',
  alias: 'widget.qualityColumn',
  mixins: ['Editor.view.segments.column.BaseMixin'],
  showInMetaTooltip: true,
  filter: {
      type: 'list',
      labelField: 'label',
      phpMode: false,
      options: Editor.data.segments.qualityFlags
  },

  initComponent: function() {
    var me = this;
    me.initBaseMixin();
    me.callParent(arguments);
  },
  /**
   * rendert die Semikolon separierten QM Werte mit Zeilenumbrüchen als Trenner
   * @param {Integer} value
   * @param {Object} t
   * @param {Editor.model.Segment} record
   * @returns {String}
   */
  renderer: function(value,t,record){
    var me = this, 
    result = [];
    Ext.each(value.split(';'), function(item){
      if(item && item.length > 0 && me.qualityData[item]) {
        result.push(me.qualityData[item]);
      }
    });
    return result.join('<br />');
  }
});