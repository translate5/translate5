
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
 * @extends Ext.grid.column.Column
 * @initalGenerated
 */
Ext.define('Editor.plugins.ChangeLog.view.TypeColumn', {
  extend: 'Ext.grid.column.Column',
  alias: 'widget.typecolumn',
  dataIndex: 'type',
  filter: null,
  strings: {
      type:'#UT#Typ'
  },
  types: {
      bugfix:'#UT#Bugfix',
      feature:'#UT#Feature',
      change:'#UT#Change'
  },
  initComponent: function() {
    var me=this;
    
    me.cellWrap=true;
    me.width=55;
    me.text=me.strings.type;
    var filterIcon = [{
        id:'feature',
        label:'<img valign="text-bottom" src="editor/plugins/resources/changeLog/images/add.png" alt="" title=""/>'+' '+me.types.feature
    },{
        id:'change',
        label:'<img valign="text-bottom" src="editor/plugins/resources/changeLog/images/arrow_refresh.png" alt="" title=""/>'+' '+me.types.change
    },{
        id:'bugfix',
        label:'<img valign="text-bottom" src="editor/plugins/resources/changeLog/images/lightning.png" alt="" title=""/>'+' '+me.types.bugfix
    }];
    me.filter = {
        type: 'list',
        labelField: 'label',
        phpMode: false,
        options: filterIcon
    };
    me.callParent(arguments);
  },
  renderer:function(v,meta,rec){
      var me=this,
          types = me.down('typecolumn').types,
          type=rec.get('type');
      
      if(!type || type==""){
          type="change";
      }
      meta.tdAttr= 'data-qtip="'+types[type]+'"';
      meta.tdCls = meta.tdCls  + 'type '+type;
      return "";
  }
});
