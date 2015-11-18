
/*
START LICENSE AND COPYRIGHT

 This file is part of translate5
 
 Copyright (c) 2013 - 2015 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

 Contact:  http://www.MittagQI.com/  /  service (ATT) MittagQI.com

 This file may be used under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE version 3
 as published by the Free Software Foundation and appearing in the file agpl3-license.txt 
 included in the packaging of this file.  Please review the following information 
 to ensure the GNU AFFERO GENERAL PUBLIC LICENSE version 3.0 requirements will be met:
 http://www.gnu.org/licenses/agpl.html

 There is a plugin exception available for use with this release of translate5 for
 open source applications that are distributed under a license other than AGPL:
 Please see Open Source License Exception for Development of Plugins for translate5
 http://www.translate5.net/plugin-exception.txt or as plugin-exception.txt in the root
 folder of translate5.
  
 @copyright  Marc Mittag, MittagQI - Quality Informatics
 @author     MittagQI - Quality Informatics
 @license    GNU AFFERO GENERAL PUBLIC LICENSE version 3 with plugin-execptions
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
 * @class Editor.view.segments.column.AutoState
 * @extends Editor.view.ui.segments.column.AutoState
 * @initalGenerated
 */
Ext.define('Editor.view.segments.column.AutoState', {
  extend: 'Editor.view.ui.segments.column.AutoState',
  alias: 'widget.autoStateColumn',
  isErgonomicVisible: true,
  isErgonomicSetWidth: true,
  ergonomicWidth: 90,
  isErgonomicVisible: true,
  imgTpl: new Ext.Template('<img class="autoState-{0}" src="'+Editor.data.moduleFolder+'images/autoStateFlags-{0}.png" alt="{1}" title="{1}"/>'),
  stateLabels: [],
  filter: null,
  initComponent: function() {
    var me = this,
        autoStates = Ext.Array.filter(Editor.data.segments.autoStateFlags, function(item) {
                return item.id != 999;
            });
    me.scope = me; //so that renderer can access this object instead the whole grid.
    Ext.each(Editor.data.segments.autoStateFlags, function(item){
        me.stateLabels[item.id] = item.label;
    });
    me.filter = {
        type: 'list',
        labelField: 'label',
        phpMode: false,
        options: autoStates
    };
    me.callParent(arguments);
  },
  /**
   * rendert den integer Value des autoStateFlags zu einem img Element mit passender URL
   * @param {Integer} value
   * @param {Object} t
   * @param {Editor.model.Segment} record
   * @see {Ext.grid.column.Column}
   * @returns String
   */
  renderer: function(value,t,record){
      if(! this.stateLabels[value]) {
          value = 0;
      }
      return this.imgTpl.apply([value,this.stateLabels[value]]);
  }
});