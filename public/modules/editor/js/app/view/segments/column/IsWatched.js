
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
 * @author Angel Naydenov
 * @package editor
 * @version 1.0
 *
 */
/**
 * @class Editor.view.segments.column.IsWatched
 * @extends Editor.view.ui.segments.column.IsWatched
 * @initalGenerated
 */
Ext.define('Editor.view.segments.column.IsWatched', {
  extend: 'Ext.grid.column.Column',
  alias: 'widget.iswatchedColumn',
  mixins: ['Editor.view.segments.column.BaseMixin'],
  itemId: '',
  width: 90,
  dataIndex: 'isWatched',
  hidden: true,
  text: '#UT#Lesezeichen',
  text_tip: '#UT#Mit Lesezeichen versehen',
  showInMetaTooltip: true,

  initComponent: function() {
    var me = this;
    me.initBaseMixin();
    me.scope = me; //so that renderer can access this object instead the whole grid.
    me.callParent(arguments);
  },
  filter: {
      type: 'boolean'
  },
  
  renderer: function(value,t,record){
      if(value) {
          if(arguments.length > 3) { //for grid and roweditor rendering
              t.tdAttr = this.text_tip;
              return '<img src="'+Editor.data.moduleFolder+'/images/star.png" alt="bookmarked"/>'
          }
          return this.text_tip; //for tooltip rendering
      }
      return '';
  }
});