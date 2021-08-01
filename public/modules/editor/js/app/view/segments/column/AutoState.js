
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
 * @class Editor.view.segments.column.AutoState
 * @extends Ext.grid.column.Column
 * @initalGenerated
 */
Ext.define('Editor.view.segments.column.AutoState', {
  extend: 'Ext.grid.column.Column',
  alias: 'widget.autoStateColumn',
  mixins: ['Editor.view.segments.column.InfoToolTipMixin'],
  dataIndex: 'autoStateId',
  text: 'Bearbeitungsstatus',
  showInMetaTooltip: true,
  imgTpl: new Ext.Template('<img valign="text-bottom" class="{2}" src="'+Editor.data.moduleFolder+'images/{0}.png?v=2" alt="{1}" />'),
  stateLabels: [],
  filter: null,
  editor: {
      xtype: 'displayfield',
      cls: 'autoState'
  },
  initComponent: function() {
    var me = this,
        autoStates = Ext.Array.filter(Editor.data.segments.autoStateFlags, function(item) {
                return item.id != 999 && item.id != 998;
            });
    me.scope = me; //so that renderer can access this object instead the whole grid.
    Ext.each(Editor.data.segments.autoStateFlags, function(item){
        me.stateLabels[item.id] = item.label;
    });
    
    Ext.each(autoStates, function(item, idx, list){
        //we have to clone the values, if not we change the originals by reference
        list[idx] = {
            id: item.id,
            label: me.autoStateImg(item.id, item.label) + ' ' +  item.label
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
  autoStateImg: function(stateId, label) {
      return this.imgTpl.apply(['autoStateFlags-'+stateId, label, 'autoState-'+stateId]);
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
        var res = '';
        if(! this.stateLabels[value]) {
            value = 0;
        }
      
        if(record.get('isWatched')) {
            res += ' ' + this.imgTpl.apply(['star', 'bookmarked', '']);
        }
        if(record.get('comments') && record.get('comments').length > 0) {
            res += ' ' + this.imgTpl.apply(['comments', 'has comments', 'edit']);
        }
        
        //the following if checks if we are in renderInfoQtip or normal row rendering
        if(arguments.length > 3) { //for grid and roweditor rendering
            t.tdAttr = 'data-qtip="'+this.renderInfoQtip(record)+'"';
            return this.autoStateImg(value, this.stateLabels[value]) + res;
        }
        return this.stateLabels[value];
    }
});