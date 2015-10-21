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
 * MetaPanel Controller
 * @class Editor.controller.MetaPanel
 * @extends Ext.app.Controller
 */
Ext.define('Editor.controller.MetaPanel', {
  extend : 'Ext.app.Controller',
  requires: ['Editor.view.qmsubsegments.AddFlagFieldset'],
  messages: {
  },
  refs : [{
    ref : 'metaPanel',
    selector : '#metapanel'
  },{
    ref : 'metaTermPanel',
    selector : '#metapanel #metaTermPanel'
  },{
    ref : 'leftBtn',
    selector : '#metapanel #goAlternateLeftBtn'
  },{
    ref : 'rightBtn',
    selector : '#metapanel #goAlternateRightBtn'
  },{
      ref : 'navi',
      selector : '#metapanel #naviToolbar'
  },{
      ref : 'segmentMeta',
      selector : '#metapanel .segmentsMetapanel'
  },{
    ref : 'segmentGrid',
    selector : '#segmentgrid'
  }],
  
  init : function() {
      var me = this;
      me.control({
      '#metapanel' : {
          show : me.layout
      },
      '#segmentgrid': {
          afterrender: me.initEditPluginHandler
      }
    });
  },
  /**
   * Gibt die RowEditing Instanz des Grids zurück
   * @returns Editor.view.segments.RowEditing
   */
  getEditPlugin: function() {
    return this.getSegmentGrid().editingPlugin;
  },
  initEditPluginHandler: function() {
      var me = this, 
          multiEdit = me.getSegmentGrid().query('.contentEditableColumn').length > 1,
          useChangeAlikes = Editor.app.authenticatedUser.isAllowed('useChangeAlikes', Editor.data.task),
          edCtrl = me.application.getController('Editor');
          
      edCtrl.on('changeState', me.changeState, me);

    //Diese Events können erst in onlauch gebunden werden, in init existiert das Plugin noch nicht
      me.getEditPlugin().on('beforeedit', me.startEdit, me);
      me.getEditPlugin().on('canceledit', me.cancelEdit, me);
      me.getEditPlugin().on('edit', me.saveEdit, me);
    
      me.getLeftBtn().setVisible(multiEdit && ! useChangeAlikes);
      me.getRightBtn().setVisible(multiEdit && ! useChangeAlikes);
  },
  /**
   * Handler für save Button
   */
  layout: function() {
    this.getNavi().doLayout(); //FIXME noch was anderes layouten?
  },
  /**
   * Editor.view.segments.RowEditing beforeedit handler, initiert das MetaPanel mit den Daten
   * @param {Object} context
   */
  startEdit: function(context) {
    var me = this,
        mp = me.getMetaPanel(),
        segmentId = context.record.get('id');
    
    me.record = context.record;
    me.getMetaTermPanel().getLoader().load({params: {id: segmentId}});
    //bindStore(me.record.terms());
    me.loadRecord(me.record);
    //FIXME here doLayout???
    me.getNavi().enable();
    me.getSegmentMeta().show();
    mp.show();
  },
  /**
   * opens metapanel for readonly segments
   * @param {Editor.model.Segment} record
   */
  openReadonly: function(record) {
      var me = this,
      mp = me.getMetaPanel();
      me.record = record;
      me.getNavi().disable();
      me.getSegmentMeta().hide();
      mp.show();
  },
  /**
   * lädt die konkreten record ins Meta Panel 
   * @param {Ext.data.Model} record
   */
  loadRecord: function(record) {
    var me = this,
        mp = me.getMetaPanel(),
        form = mp.down('#metaInfoForm'),
        values = record.getQmAsArray(),
        qmBoxes = mp.query('#metaQm .checkbox');
    statBoxes = mp.query('#metaStates .radio');
    Ext.each(statBoxes, function(box){
      box.setValue(false);
    });
    form.loadRecord(record);
    Ext.each(qmBoxes, function(box){
      box.setValue(Ext.Array.contains(values, box.inputValue));
    });
  },
  /**
   * Editor.view.segments.RowEditing edit handler, Speichert die Daten aus dem MetaPanel im record
   */
  saveEdit: function() {
    var me = this,
        mp = me.getMetaPanel(),
        form = mp.down('#metaInfoForm'),
        qmBoxes = mp.query('#metaQm .checkbox'),
        quality = [];
    Ext.each(qmBoxes, function(box){box.getValue() && quality.push(box.inputValue);});
    me.record.set('stateId', form.getValues().stateId);
    me.record.setQmFromArray(quality);
    //close the metapanel
    me.cancelEdit();
  },
  changeState: function(param) {
    var me = this,
        mp = me.getMetaPanel(),
        statBoxes = mp.query('#metaStates .radio');
    Ext.each(statBoxes, function(box){
      if (box.inputValue == param)
      {
        box.setValue(true);
      }
    });

  },  
  /**
   * Editor.view.segments.RowEditing canceledit handler
   * @hint metapanel
   */
  cancelEdit: function() {
    this.getMetaPanel().hide();
  }
});
