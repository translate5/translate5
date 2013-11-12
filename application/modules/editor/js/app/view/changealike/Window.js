/*
 START LICENSE AND COPYRIGHT
 
 This file is part of Translate5 Editor Javascript GUI and build on ExtJs 4 lib
 
 Copyright (c) 2013 Marc Mittag; MittagQI - Quality Informatics; All rights reserved.
 
 Contact:  http://www.MittagQI.com/  /  service (ÄTT) MittagQI.com
 
 This file may be used under the terms of the GNU General Public License version 3.0
 as published by the Free Software Foundation and appearing in the file gpl3-license.txt 
 included in the packaging of this file.  Please review the following information 
 to ensure the GNU General Public License version 3.0 requirements will be met:
 http://www.gnu.org/copyleft/gpl.html.

 For this file you are allowed to make use of the same FLOSS exceptions to the GNU 
 General Public License version 3.0 as specified by Sencha for Ext Js. 
 Please be aware, that Marc Mittag / MittagQI take no warranty
 for any legal issue, that may arise, if you use these FLOSS exceptions and recommend
 to stick to GPL 3. For further information regarding this topic please see the attached 
 license.txt of this software package.
 
 MittagQI would be open to release translate5 under EPL or LGPL also, if this could be
 brought in accordance with the ExtJs license scheme. You are welcome to support us
 with legal support, if you are interested in this.
 
 
 @copyright  Marc Mittag, MittagQI - Quality Informatics
 @author     MittagQI - Quality Informatics
 @license    GNU General Public License version 3.0 http://www.gnu.org/copyleft/gpl.html
             with FLOSS exceptions (see floss-exception.txt and ux-exception.txt at the root level)
 
 END LICENSE AND COPYRIGHT 
 */

/**#@++
 * @author Marc Mittag
 * @package editor
 * @version 1.0
 *
 */
/**
 * @class Editor.view.changealike.Window
 * @extends Editor.view.ui.changealike.Window
 */
Ext.define('Editor.view.changealike.Window', {
  extend: 'Editor.view.ui.changealike.Window',
  alias: 'widget.changealikeWindow',

  //Item Strings:
  items_segmentData: ['<h1>aktuell bearbeitetes Segment</h1>',
               '{edited}'],
  additionSourceEdition: 'Achtung: Der Ausgangstext wird überschrieben!',
  items_help: 'Hilfe:',
  loadingMask: null,
  loadedAlikes: null,
  tools: [{
    type:'help',
    renderTpl: ['{label} '+Ext.panel.Tool.prototype.renderTpl]
  }],
  id: 'change-alike-window',
  openedFor: null,
  sourceEdited: null,
  initComponent: function() {
    var me = this;
    me.items_segmentData = Ext.create('Ext.XTemplate', me.items_segmentData);
    me.items_segmentData.compile();
    me.callParent(arguments);
    me.loadingMask = new Ext.LoadMask(me, {modal: false, store: false});
    Ext.apply(me.tools[0], {
      tooltip: me.items_help,
      renderData: {
        label:  me.items_help
      },
      handler: me.showHelp,
      scope: me
    });
  },
  /**
   * @param {Editor.model.Segment} rec
   * @param {Boolean} sourceEdited
   */
  show: function(rec, sourceEdited) {
      //@todo SourceMatch Filterung im WDHE
      //und entsprechende Spalten im WDHE Grid ausblenden.
      //Editor.data.task.get('enableSourceEditing');
      var me = this,
          grid = me.down('.gridpanel'),
          id = rec.get('id');
      me.openedFor = id;
      me.sourceEdited = sourceEdited;
      me.callParent();
      me.updateInfoText(rec, sourceEdited);
      if(me.loadedAlikes) {
          grid.setAlikes(me.filterAlikes(me.loadedAlikes));
      }
      else {
          // setting a loading mask for the window / grid is not possible. 
          // perhaps because of bug for ext-4.0.7 (see http://www.sencha.com/forum/showthread.php?157954)
          // after trying different, not working things, we decided to disable the buttons and show a simple text message, without a grey box
          me.loadingMask.show();
          me.down('toolbar').disable();
      }
      me.loadedAlikes = false; //reset flag after usage
      Ext.Array.each(grid.columns, function(col){
          if(col.dataIndex == 'targetMatch' || col.dataIndex == 'sourceMatch') {
              col.setVisible(!sourceEdited);
          }
      });
  },
  /**
   * @param {Integer} id
   * @param {Array} alikes
   */
  setAlikes: function(id, alikes) {
      var me = this, 
          grid = me.down('.gridpanel');
      me.loadedAlikes = alikes;
      if(grid.rendered){
          grid.setAlikes(me.filterAlikes(alikes));
      }
      me.setLoading(false);
      me.loadingMask.hide();
      me.down('toolbar').enable();
  },
  /**
   * Filters the alikes as needed by sourceEditing (if enabled)
   * @param {Array} alikes
   * @returns {Array}
   */
  filterAlikes: function(alikes) {
      var me = this, 
          result = [];
      Ext.Array.each(alikes, function(rec){
          //if source editing return only alikes with source matched = true 
          if(me.sourceEdited && !rec.get('sourceMatch')) {
              return;
          }
          result.push(rec);
      });
      return result;
  },
  /**
   * updates the text shown about the loaded segment in the Change Alike Editor
   * @param {Editor.model.Segment} segmentRecord
   * @param {Boolean} sourceEdited
   */
  updateInfoText: function(segmentRecord, sourceEdited) {
      var edited = sourceEdited ? 'sourceEdited' : 'edited';
          addition = sourceEdited ? '<p class="changealike-info-text">'+this.additionSourceEdition+'</p>' : '';

      this.down('#infoText').update({
          edited: segmentRecord.get(edited),
          addition: addition,
          id: segmentRecord.get('id'),
          autoStateId: segmentRecord.get('autoStateId')
      });
  },
  showHelp: function() {
    var help = Ext.ComponentMgr.create({
      xtype: 'window',
      closable:true,
      modal: true,
      height:510,
      bodyPadding: 5,
      width:400,
      loader: {
        url: Editor.data.pathToRunDir+'/editor/index/wdhehelp',
        autoLoad: true
      }
    });
    help.show();
  }
});
