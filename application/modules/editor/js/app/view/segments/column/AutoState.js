
/*
START LICENSE AND COPYRIGHT

 This file is part of translate5
 
 Copyright (c) 2013 - 2017 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

 Contact:  http://www.MittagQI.com/  /  service (ATT) MittagQI.com

 This file may be used under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE version 3
 as published by the Free Software Foundation and appearing in the file agpl3-license.txt 
 included in the packaging of this file.  Please review the following information 
 to ensure the GNU AFFERO GENERAL PUBLIC LICENSE version 3.0 requirements will be met:
 http://www.gnu.org/licenses/agpl.html
  
 @copyright  Marc Mittag, MittagQI - Quality Informatics
 @author     MittagQI - Quality Informatics
 @license    GNU AFFERO GENERAL PUBLIC LICENSE version 3
			 http://www.gnu.org/licenses/agpl.html

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
 * @extends Ext.grid.column.Column
 * @initalGenerated
 */
Ext.define('Editor.view.segments.column.AutoState', {
  extend: 'Ext.grid.column.Column',
  alias: 'widget.autoStateColumn',
  dataIndex: 'autoStateId',
  text: 'Autostatus',
  isErgonomicVisible: true,
  isErgonomicSetWidth: true,
  ergonomicWidth: 90,
  isErgonomicVisible: true,
  imgTpl: new Ext.Template('<img valign="text-bottom" class="autoState-{0}" src="'+Editor.data.moduleFolder+'images/autoStateFlags-{0}.png?v=1" alt="{1}" title="{1}"/>'),
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
    
    Ext.each(autoStates, function(item, idx, list){
        //we have to clone the values, if not we change the originals by reference
        list[idx] = {
            id: item.id,
            label: me.imgTpl.apply([item.id, item.label]) + ' ' +  item.label
        } 
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