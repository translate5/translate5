
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
 translate5 plug-ins that are distributed under GNU AFFERO GENERAL PUBLIC LICENSE version 3:
 Please see http://www.translate5.net/plugin-exception.txt or plugin-exception.txt in the root
 folder of translate5.
  
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
 * MetaPanel Controller
 * @class Editor.controller.MetaPanel
 * @extends Ext.app.Controller
 */
Ext.define('Editor.controller.Editor', {
  extend : 'Ext.app.Controller',
  requires: [
    'Editor.view.segments.EditorKeyMap',
    'Editor.controller.editor.PrevNextSegment'
  ],
  messages: {
      segmentReset: '#UT#Das Segment wurde auf den ursprünglichen Zustand nach dem Import zurückgesetzt.',
      segmentNotBuffered: '#UT#Das nächste / vorherige Segment wird noch geladen, bitte versuchen Sie es erneut.',
      segmentsChanged: '#UT#Die Sortierung bzw. Filterung wurde geändert, es kann kein nächstes / vorheriges Segment ausgewählt werden.',
      segmentsChangedJump: '#UT#Die Sortierung bzw. Filterung wurde geändert, es kann nicht zum aktuellen Segment zurück gesprungen werden.',
      f2FirstOpened: '#UT#Das erste bearbeitbare Segment wurde geöffnet, da kein anderes Segment ausgewählt war.',
      f2Readonly: '#UT#Das ausgewählte Segment ist nicht bearbeitbar!',
      errorTitle: '#UT# Fehler bei der Segment Validierung!',
      correctErrorsText: '#UT# Fehler beheben',
      editorMoveTitle: '#UT#Verschiebbarer Editor und hilfreiche Tastaturkürzel',
      editorMove: '#UT#Der Segmenteditor kann mit der Maus beliebig positioniert werden. <br />Dazu lediglich den Segmenteditor anklicken und dann verschieben.',
      takeTagTooltip: '#UT#STRG + EINFG (alternativ STRG + . (Punkt)) kopiert den kompletten Quelltext in den Zieltext<br />STRG + , (Komma) + &gt;Nummer&lt; kopiert den entsprechenden Tag in den Zieltext (Null entspricht Tag Nr. 10)<br />STRG + SHIFT + , (Komma) + &gt;Nummer&lt; kopiert die Tags mit den Nummern 11 bis 20 in den Zieltext.',
      saveAnyway: '#UT# Trotzdem speichern'
  },
  DEC_DIGITS: [48, 49, 50, 51, 52, 53, 54, 55, 56, 57],
  refs : [{
    ref : 'segmentGrid',
    selector : '#segmentgrid'
  },{
      ref : 'navi',
      selector : '#metapanel #naviToolbar'
  },{
      ref:'filepanel',
      selector:'#filepanel'
  }],
  registeredTooltips: [],
  isEditing: false,
  keyMapConfig: null,
  editorKeyMap: null,
  generalKeyMap: null,
  prevNextSegment: null,
  sourceTags: null,
  listen: {
      controller: {
          '#Editor.$application': {
              editorViewportClosed: 'clearKeyMaps'
          }
      },
      component: {
          '#metapanel metapanelNavi button' : {
              click : 'buttonClickDispatcher'
          },
          'segmentsHtmleditor': {
              initialize: 'initEditor',
              contentErrors: 'handleSaveWithErrors'
          },
          'roweditor': {
              destroy: 'handleDestroyRoweditor',
              repositioned: 'repositionEditor',
              dragend: 'handleEditorDragend'
          },
          'roweditor displayfield[isContentColumn!=true]': {
              afterrender: 'initMoveToolTip'
          },
          '#segmentgrid': {
              afterrender: 'initEditPluginHandler'
          },
          '#showReferenceFilesButton':{
              click:'onShowReferenceFilesButtonClick'
          }
      }
  },
  init : function() {
      var me = this;
      
      Ext.override("Ext.util.KeyMap",{
          handleTargetEvent: Editor.view.segments.EditorKeyMap.handleTargetEvent
      });
      
      //set the default config
      me.keyMapConfig = {
          'ctrl-d':         ["D",{ctrl: true, alt: false}, me.watchSegment, true],
          'ctrl-s':         ["S",{ctrl: true, alt: false}, me.save, true],
          'ctrl-g':         ["G",{ctrl: true, alt: false}, me.scrollToSegment, true],
          'ctrl-enter':     [[10,13],{ctrl: true, alt: false}, me.saveNextByWorkflow],
          'ctrl-alt-enter': [[10,13],{ctrl: true, alt: true, shift: false}, me.saveNext],
          'ctrl-alt-shift-enter': [[10,13],{ctrl: true, alt: true, shift: true}, me.savePrevious],
          'esc':            [Ext.EventObjectImpl.ESC, null, me.cancel],
          'ctrl-alt-left':  [Ext.EventObjectImpl.LEFT,{ctrl: true, alt: true}, me.goToLeft],
          'ctrl-alt-right': [Ext.EventObjectImpl.RIGHT,{ctrl: true, alt: true}, me.goToRight],
          'alt-pageup':     [Ext.EventObjectImpl.PAGE_UP,{ctrl: false, alt: true}, me.goToUpperByWorkflowNoSave],
          'alt-pagedown':   [Ext.EventObjectImpl.PAGE_DOWN,{ctrl: false, alt: true}, me.goToLowerByWorkflowNoSave],
          'alt-del':        [Ext.EventObjectImpl.DELETE,{ctrl: false, alt: true}, me.resetSegment],
          'ctrl-alt-up':    [Ext.EventObjectImpl.UP,{ctrl: true, alt: true}, me.goToUpperNoSave, true],
          'ctrl-alt-down':  [Ext.EventObjectImpl.DOWN,{ctrl: true, alt: true}, me.goToLowerNoSave, true],
          'alt-c':          ["C",{ctrl: false, alt: true}, me.handleOpenComments, true],
          'alt-s':          ["S",{ctrl: false, alt: true}, me.handleDigitPreparation(me.handleChangeState), true],
          'ctrl-comma':     [188,{ctrl: true, alt: false, shift: false}, me.handleDigitPreparation(me.handleInsertTag), true],
          'ctrl-shift-comma': [188,{ctrl: true, alt: false, shift: true}, me.handleDigitPreparation(me.handleInsertTagShift), true],
          'alt-DIGIT':      [me.DEC_DIGITS,{ctrl: false, alt: true}, me.handleAssignMQMTag, true],
          'DIGIT':          [me.DEC_DIGITS,{ctrl: false, alt: false}, me.handleDigit],
          'F2':             [Ext.EventObjectImpl.F2,{ctrl: false, alt: false}, me.handleF2KeyPress, true],
          'ctrl-insert':    [Ext.EventObjectImpl.INSERT,{ctrl: true, alt: false}, me.copySourceToTarget],
          'ctrl-dot':       [190,{ctrl: true, alt: false}, me.copySourceToTarget] //Mac Alternative key code
      };
  },
  /**
   * track isEditing state 
   */
  initEditPluginHandler: function () {
      var me = this,
          plug = me.getEditPlugin(),
          disableEditing = function(){me.isEditing = false;};
          
      plug.on('beforestartedit', me.handleBeforeStartEdit, me);
      plug.on('beforeedit', me.handleStartEdit, me);
      plug.on('canceledit', disableEditing);
      plug.on('edit', disableEditing)
      
      me.tooltip = Ext.create('Editor.view.ToolTip', {
          target: me.getSegmentGrid().getEl()
      });
      
      me.prevNextSegment = Ext.create('Editor.controller.editor.PrevNextSegment', {
        editingPlugin: plug
      });
      
      me.relayEvents(me.prevNextSegment, ['prevnextloaded']);
      
      //reset the store next/prev information if data changed
      me.getSegmentGrid().store.on('filterchange', me.handleSortOrFilter, me);
      me.getSegmentGrid().store.on('sort', me.handleSortOrFilter, me);
      
      /**
       * disable the column show / hide menu while editing a segment (EXT6UPD-85)
       */
      Ext.override(me.getSegmentGrid().getHeaderContainer(), {
          beforeMenuShow: function(menu) {
              this.callParent([menu]);
              menu.down('#columnItem').setDisabled(plug.editing);
          }
      });
      
      me.generalKeyMap = new Ext.util.KeyMap(Ext.getDoc(), me.getKeyMapConfig('application', {
          'alt-c':     ["C",{ctrl: false, alt: true}, function(key, e){
              var me = this;
              e.stopEvent();
              if(me.isEditing) {
                  me.handleOpenComments();
                  return false;
              }
              var found = Ext.select('#segment-grid-body .x-grid-item-selected td.comments-field img').first();
              if(found && (found.hasCls('add') || found.hasCls('edit'))){
                  found.dom.click();
              }
              return false;
          }]
      }));
      
      me.handleReferneceFilesMessage();
  },
  
  handleSortOrFilter: function() {
      var me = this,
          plug = me.getEditPlugin();
      
      me.prevNextSegment.handleSortOrFilter();
      if(plug.editor && plug.editor.context) {
          plug.editor.context.reordered = true;
      }
  },
  
  /**
   * initializes the roweditor moveable tooltip
   */
  initMoveToolTip: function(displayfield){
      var me = this,
          id = displayfield.getId()+'-bodyEl';
      if(displayfield.ownQuicktip){
          return;
      }
      me.registeredTooltips.push(id);
      Ext.tip.QuickTipManager.register({
          target: id, 
          title: me.messages.editorMoveTitle,
          text: me.messages.editorMove + '<br /><br />' + me.messages.takeTagTooltip
      });
  },
  handleDestroyRoweditor: function() {
      //FIXME needed for Ext 6.2, possibly removable for further ExtJS updates, see T5DEV-172
      var me = this;
      if(me.registeredTooltips && me.registeredTooltips.length > 0) {
          Ext.Array.each(me.registeredTooltips, function(item) {
              if(Ext.tip.QuickTipManager.tip) {
                  delete Ext.tip.QuickTipManager.tip.targets[item];
              }
          });
      }
  },
  /**
   * saves the segment of the already opened editor and restarts startEditing call 
   */
  handleBeforeStartEdit: function(plugin, args){
      if(!plugin.editing) {
          //if editing is started by enter or F2 on a selected row:
          //FIXME the check for editByCellActivation is commented because is not needed. With this
          //the message will be reused in visualReview plugin
          //if(plugin.editByCellActivation && !args[0].get('editable')){
          if(!args[0].get('editable')){
              Editor.MessageBox.addInfo(this.messages.f2Readonly);
          }
          return true;
      }
      this.fireEvent('saveSegment', {
          scope: this,
          segmentUsageFinished: function(){
              plugin.startEdit.apply(plugin, args);
          }
      });
      return false;
  },
  handleStartEdit: function(plugin, context) {
    var me = this;
    me.isEditing = true;
    me.prevNextSegment.calculateRows(context);//context.record, context.rowIdx
    me.getSourceTags(context);
  },
    getSourceTags: function(context) {
        var me = this,
            plug = me.getEditPlugin(),
            source = context.record.get('source'),
            tempNode, parse, walkNodes;

        me.sourceTags = [];
        //do nothing when editing the source field
        if(/^source/.test(context.column.dataIndex)){
            return;
        }

        tempNode = document.createElement('DIV');
        Ext.fly(tempNode).update(source);

        walkNodes = function(rootNode) {
            Ext.each(rootNode.childNodes, function(item){
                if(Ext.isTextNode(item) || item.tagName != 'DIV'){
                    return;
                }
                if(item.tagName == 'DIV' && /(^|[\s])term([\s]|$)/.test(item.className)){
                    walkNodes(item);
                    return;
                }
                var divItem = Ext.fly(item),
                    tagNr = divItem.down('span.short').dom.innerHTML.replace(/[^0-9]/g, ''),
                    tagType = item.className.match(/^(open|single|close)\s/),
                    //we use a real array starting at 0 for tag 1
                    idx = tagNr-1;
                if(!tagType) {
                    return;
                }
                tagType = tagType[1];
                if(!me.sourceTags[idx]) {
                    me.sourceTags[idx] = {};
                }
                me.sourceTags[idx][plug.editor.mainEditor.idPrefix+tagType+tagNr] = '<div class="'+item.className+'">'+item.innerHTML+'</div>';
          });
      };
      walkNodes(tempNode);
    },
  /**
   * Gibt die RowEditing Instanz des Grids zurück
   * @returns Editor.view.segments.RowEditing
   */
  getEditPlugin: function() {
    return this.getSegmentGrid().editingPlugin;
  },
  /**
   * converts the here used simple keymap config to the fullblown KeyMap config
   * the simple config contains arrays with the following indizes:
   * 0: key
   * 1: special key config
   * 2: function to be called
   * 3: boolean, if true prepend event propagation stopper
   *
   * @param {String} area, a speakable name where the config is used. 
   *    Just passed to the keyMapConfig event to determine there if the event should be processed or not
   * @param {Object} overwrite a config object for dedicated overwriting of key bindings
   */
  getKeyMapConfig: function(area, overwrite) {
      var me = this,
          conf = [],
          overwrite = overwrite || {};
      
      /*
       * event beforeKeyMapUsage parameters:
       * @param {Editor.controller.Editor} 
       * @param {String} area, the area describes where the keymap shall be used.  
       * @param {Object} overwrite, the object with overwrite definitions 
       */
      me.fireEvent('beforeKeyMapUsage', this, area, overwrite);
      
      Ext.Object.each(me.keyMapConfig, function(key, item){
          //applies if available the overwritten config instead the default one
          if(overwrite && overwrite[key]) {
              item = overwrite[key];
          }
          if(!item) {
              return;
          }
          //applies the keys config and scope to a fresh conf object
          var confObj = Ext.applyIf({
              key: item[0],
              scope: me
          }, item[1]);
          if(item[3]) {
              confObj.defaultEventAction = 'stopEvent';
              //prepends the event propagation stopper
          }
          confObj.fn = function(key, e) {
              item[2].apply(confObj.scope, arguments);
              //FIXME Ausnahme für digitHandler definieren, wenn nicht im isDigitPreparation Modus!
              return false; //stop further key binding processing
          }
          conf.push(confObj);
      });
      return conf;
  },
  /**
   * binds strg + enter as save segment combination
   * @param {Editor.view.segments.HtmlEditor} editor
   */
  initEditor: function(editor){
      var me = this,
          docEl = Ext.get(editor.getDoc()),
          offset = editor.iframeEl.getXY();

      if(me.editorKeyMap) {
          me.editorKeyMap.destroy();
      }
      docEl.on('paste', function(e){
          e.stopPropagation();
          e.preventDefault();
          editor.insertAtCursor((e.browserEvent.clipboardData || window.clipboardData).getData('Text'));
      }, me, {delegated: false});
      if(me.editorTooltip){
          me.editorTooltip.setTarget(editor.getEditorBody());
          me.editorTooltip.targetOffset = offset;
      }
      else {
          me.editorTooltip = Ext.create('Editor.view.ToolTip', {
              target: editor.getDoc(),
              targetOffset: offset
          });
      }
  },
  repositionEditor: function(editor) {
      if(this.editorTooltip){
          var offset = editor.mainEditor.iframeEl.getXY();
          this.editorTooltip.targetOffset = offset;
      }
  },
  handleEditorDragend: function(moved, event){
      var comp = (moved.proxy && !moved.comp.liveDrag) ? moved.proxy : moved.comp;
      this.repositionEditor(comp);
  },
  clearKeyMaps: function() {
      var me = this;
      if(me.editorKeyMap) {
          me.editorKeyMap.destroy();
          me.editorKeyMap = null;
      }
      
      if(me.generalKeyMap) {
          me.generalKeyMap.destroy();
          me.generalKeyMap = null;
      }
  },
  buttonClickDispatcher: function(btn, e) {
      var me = this,
          action = btn.itemId && btn.itemId.replace(/Btn$/, '');
      if(action && Ext.isFunction(me[action])) {
          me[action](btn, e);
      }
  },
  /**
   * Handler for save Button
   */
  save: function() {
      var me = this,
          ed = me.getEditPlugin(),
          rec = ed.editing && ed.context.record;

      //since save without moving was triggered, we have to reset the calculated data
      me.prevNextSegment.reset();

      if(me.isEditing &&rec && rec.get('editable')) {
          me.fireEvent('saveUnsavedComments');
          me.fireEvent('saveSegment');
      }
  },
  /**
   * Special Universal preparation Handler for pressing DIGIT keys
   * A preparation keyboard shortcut can be defined, for example ALT-S. 
   * If ALT-S is pressed, then if the next key is a DIGIT the given 
   * digithandler will be called with the preseed DIGIT.
   * @param {Function} must be function in the controller scope, since scope parameter is not supported
   */
  handleDigitPreparation: function(digithandler) {
      var me = this;
      return function(key, event) {
          me.digitHandler = digithandler;
          event.isDigitPreparation = true;
          event.stopEvent();
          return false;
      };
  },
  /**
   * Digit handler, does only something if a DIGIT preparation shortcut was pressed directly before.
   */
  handleDigit: function(k, e) {
      if(e.lastWasDigitPreparation){
          e.stopEvent();
          this.digitHandler(k, e);
          return false;
      } 
  },
  /**
   * Moves to the next row without saving current record
   * @return {Boolean} true if there is a next segment, false otherwise
   */
  goToLowerNoSave: function() {
      var me = this;
      me.prevNextSegment.calcNext()
      me.moveToAdjacentRow();
  },
  /**
   * Moves to the next row without saving current record
   * @return {Boolean} true if there is a next segment, false otherwise
   */
  goToUpperNoSave: function() {
      var me = this;
      me.prevNextSegment.calcPrev()
      me.moveToAdjacentRow();
  },
  /**
   * Moves to the next row with the same workflow value without saving current record
   * @return {Boolean} true if there is a next segment, false otherwise
   */
  goToLowerByWorkflowNoSave: function(key, e) {
      var me = this;
      e.preventDefault();
      e.stopEvent();
      me.prevNextSegment.calcNext(true)
      me.moveToAdjacentRow();
  },
  /**
   * Moves to the previous row with the same workflow value without saving current record
   * @return {Boolean} true if there is a next segment, false otherwise
   */
  goToUpperByWorkflowNoSave: function(key, e) {
      var me = this;
      e.preventDefault();
      e.stopEvent();
      me.prevNextSegment.calcPrev(true)
      me.moveToAdjacentRow();
  },
  /**
   * Moves to the next or previous row without saving current record
   * @param {Object} rowMeta meta information of the next/prev segment to be opened
   * @return {Boolean} true if there is a next segment, false otherwise
   */
  moveToAdjacentRow: function() {
      var me = this;
      
      if(!me.isEditing) {
          return;
      }

      me.cancel();
      me.openNextRow();
  },
  /**
   * Handler for saveNext Button
   * @return {Boolean} true if there is a next segment, false otherwise
   */
  saveNext: function() {
      this.prevNextSegment.calcNext()
      this.saveOtherRow();
  },
  /**
   * Handler for savePrevious Button
   * @return {Boolean} true if there is a next segment, false otherwise
   */
  savePrevious: function() {
      this.prevNextSegment.calcPrev()
      this.saveOtherRow();
  },
  /**
   * Handler for saveNext Button
   * @return {Boolean} true if there is a next segment, false otherwise
   */
  saveNextByWorkflow: function() {
      this.prevNextSegment.calcNext(true)
      this.saveOtherRow();
  },
  /**
   * Handler for savePrevious Button
   * @return {Boolean} true if there is a next segment, false otherwise
   */
  savePreviousByWorkflow: function() {
      this.prevNextSegment.calcPrev(true)
      this.saveOtherRow();
  },
  /**
   * save and go to other row
   */
  saveOtherRow: function() {
      var me = this,
          grid = me.getSegmentGrid(),
          selModel = grid.getSelectionModel(),
          ed = me.getEditPlugin();
      
      if(!me.isEditing) {
          return;
      }
      me.fireEvent('saveUnsavedComments');
      
      me.fireEvent('saveSegment', {
          scope: me,
          segmentUsageFinished: function(){
              me.openNextRow();
          }
      });
  },
  /**
   * Opens a next row, if any
   * @param {Object} data from getPrevNextRow
   */
  openNextRow: function() {
      var me = this,
          grid = me.getSegmentGrid(),
          selModel = grid.getSelectionModel(),
          ed = me.getEditPlugin()
          rowMeta = me.prevNextSegment.getCalculated();
      
      //if we have a nextSegment and it is rendered, bring into the view and open it
      if (rowMeta.rec && grid.getView().getNode(rowMeta.rec)) {
          selModel.select(rowMeta.rec);
          //REMIND here was startEdit defered with 300 millis, is this still needed?
          ed.startEdit(rowMeta.rec, rowMeta.lastColumn, ed.self.STARTEDIT_SCROLLUNDER);
          return;
      }

      if(Ext.isDefined(rowMeta.idx)) {
          //if we only have a rowIndex or it is not rendered, we have to scroll first
          callback = function() {
              grid.selectOrFocus(rowMeta.idx);
              sel = selModel.getSelection();
              ed.startEdit(sel[0], rowMeta.lastColumn, ed.self.STARTEDIT_SCROLLUNDER);
          };
          grid.scrollTo(rowMeta.idx, {
              callback: callback,
              notScrollCallback: callback
          });
          return;
      }
      
      if(rowMeta.isBorderReached) {
          Editor.MessageBox.addInfo(rowMeta.errorText);
          return;
      }
      
      if(rowMeta.isLoading) {
          Editor.MessageBox.addInfo(me.messages.segmentNotBuffered);
      }
      else {
          Editor.MessageBox.addInfo(me.messages.segmentsChanged);
      }
  },
  /**
   * @param {Editor.view.segments.HtmlEditor} editor
   * @param {String} msg
   */
  handleSaveWithErrors: function(editor, msg){
      var me = this,
          msgBox;
      
      //if there was an empty message we assume that there was no error,
      if(!msg) {
          return;
      }
      
      msgBox = Ext.create('Ext.window.MessageBox', {
          buttonText:{
              yes: me.messages.correctErrorsText,
              no: me.messages.saveAnyway
          }
      });
      msgBox.confirm(me.messages.errorTitle, msg, function(btn) {
          if(btn == 'yes') {
              return;
          }
          me.saveAndIgnoreContentErrors();
      },me);
  },
  /**
   * triggers the save chain but ignoring htmleditor content errors then
   */
  saveAndIgnoreContentErrors: function() {
      var me = this,
          plug = me.getEditPlugin();
      plug.editor.mainEditor.disableContentErrorCheckOnce();
      if(me.prevNextSegment.getCalculated()){
          me.saveOtherRow.apply(me);
          return;
      }
      me.save();
  },
  /**
   * Handler für cancel Button
   */
  cancel: function() {
    this.getEditPlugin().cancelEdit();
  },
  /**
   * Handles pressing the keyboard shortcuts for changing the segment state
   */
  handleChangeState: function(key, e) {
      var param = Number(key) - 48;
      //we ignore 0, since this is no valid state
      if(param === 0){
          return false;
      }
      this.fireEvent('changeState', param);
      e.stopEvent();
      return false;
  },
  /**
   * Handles pressing the comment keyboard shortcut
   */
  handleOpenComments: function(key) {
      this.fireEvent('openComments');
  },
  /**
   * Handles pressing the MQM tag shortcuts, without shift 1-10, with shift 11-20
   */
  handleAssignMQMTag: function(key, e) {
      var me = this;
      if(!me.isEditing) {
          return;
      }
      e.preventDefault();
      e.stopEvent();
      var param = Number(key) - 48;
      if (param == 0) {
          param = 10;
      }
      if(e.shiftKey) {
          param = param + 10;
      }
      me.fireEvent('assignMQMTag', param);
  },
  /**
   * Move the editor about one editable field
   */
  goToCustom: function(direction, saveRecord) {
    var me = this,
        info = me.getColInfo(),
        idx = info && info.foundIdx,
        cols = info && info.columns,
        store = me.getSegmentGrid().store,
        plug = me.getEditPlugin(),
        newRec;
    
    if(info === false) {
      return;
    }
    newRec = store.getAt(store.indexOf(plug.context.record) + direction);
    
    //check if there exists a next/prev row, if not we dont need to move the editor.
    while(newRec && !newRec.get('editable')) {
        newRec = store.getAt(store.indexOf(newRec) + direction);
    }
    if(cols[idx + direction]) {
      plug.editor.changeColumnToEdit(cols[idx + direction]);
      return;
    }
    if(direction > 0) {
        //goto next segment and first col
        if(newRec) {
            plug.editor.changeColumnToEdit(cols[0]);
        }
        if (saveRecord) {
          me.saveNext();
        }
        else {
          me.goToLowerNoSave();
        }
        return;
    }
    //goto prev segment and last col
    if(newRec) {
        plug.editor.changeColumnToEdit(cols[cols.length - 1]);
    }
    if (saveRecord) {
      me.savePrevious();
    }
    else {
      me.goToUpperNoSave();
    }
  },
  /**
   * Move the editor about one editable field
   */
  goAlternateRight: function(btn, ev) {
    this.goToCustom(1, true);
  },
  /**
   * Move the editor about one editable field
   */
  goAlternateLeft: function(btn, ev) {
    this.goToCustom(-1, true);
  },
  /**
   * Move the editor about one editable field left
   */
  goToLeft: function(key, e) {
    var me = this,
        direction = -1;
    if(!me.isEditing) {
        return;
    }
    e.preventDefault();
    e.stopEvent();
    me.goToCustom(direction, true);
  },
  /**
   * Move the editor about one editable field right
   */
  goToRight: function(key, e) {
    var me = this,
        direction = 1;
    if(!me.isEditing) {
        return;
    }
    e.preventDefault();
    e.stopEvent();
    me.goToCustom(direction, true);
  },
  /**
   * returns the visible columns and which column has actually the editor
   * @return {Object}
   */
  getColInfo: function() {
    var me = this,
        plug = me.getEditPlugin(),
        columns = me.getSegmentGrid().query('contentEditableColumn:not([hidden])'),
        foundIdx = false,
        current = plug.editor.getEditedField();
    
    if(!plug || !plug.editor || !plug.editing) {
        return false;
    }
    
    Ext.Array.each(columns, function(col, idx) {
      if(col.dataIndex == current) {
        foundIdx = idx;
      }
    });
    if(foundIdx === false) {
      return false;
    }

    return {
      plug: plug,
      columns: columns,
      foundIdx: foundIdx
    };
  },
  
  /**
   * brings the currently opened segment back into the view.
   */
  scrollToSegment: function(key, e) {
      var me = this,
          plug = me.getEditPlugin();
      e.preventDefault();
      e.stopEvent();
      if(!this.isEditing) {
          return;
      }
      if(plug.editor.context.reordered) {
          Editor.MessageBox.addInfo(me.messages.segmentsChangedJump);
          return;
      }
      plug.editor.setMode(plug.self.STARTEDIT_SCROLLUNDER);
      plug.editor.initialPositioning();
  },
  
  /**
   * resets the htmleditor content to the original content
   */
  resetSegment: function() {
      if(!this.isEditing) {
          return;
      }
      var me = this,
          plug = me.getEditPlugin(),
          editor = plug.editor,
          rec = plug.context.record,
          columnToRead = editor.columnToEdit.replace(/Edit$/, '');
      Editor.MessageBox.addInfo(me.messages.segmentReset);
      editor.mainEditor.setValueAndMarkup(rec.get(columnToRead), rec.get('id'), editor.columnToEdit);
  },
  /**
   * handler for the F2 key
   */
  handleF2KeyPress: function() {
    var me = this,
        grid = me.getSegmentGrid(),
        selModel = grid.getSelectionModel(),
        ed = me.getEditPlugin(),
        cols = grid.query('contentEditableColumn:not([hidden])'),
        view = grid.getView(),
        sel = [],
        firstEditableRow = grid.store.getFirsteditableRow(),
        callback;
    
    if(Ext.isEmpty(firstEditableRow)) {
        return;
    }

    if (ed.editing) {
        ed.editor.mainEditor.deferFocus();
        return;
    }
    
    if (selModel.hasSelection()){
        //with selection scroll the selection into the viewport and open it afterwards
        sel = selModel.getSelection();
        grid.scrollTo(grid.store.indexOf(sel[0]),{
            callback: function() {
                ed.startEdit(sel[0], cols[0]);
            }
        });
    } else {
        //with no selection, scroll to the first editable select, select it, then open it
        callback = function() {
            grid.selectOrFocus(firstEditableRow);
            sel = selModel.getSelection();
            var editStarted = ed.startEdit(sel[0], cols[0]);
            if(editStarted) {
              Editor.MessageBox.addInfo(me.messages.f2FirstOpened);
            }
        };
        grid.scrollTo(firstEditableRow, {
            callback: callback,
            notScrollCallback: callback
        });
    }
  },
  copySourceToTarget: function() {
      var plug = this.getEditPlugin();
      //do only something when editing targets:
      if(!this.isEditing || !/^target/.test(plug.editor.columnToEdit)){
          return;
      }
      plug.editor.mainEditor.insertMarkup(plug.context.record.get('source'));
  },

    handleInsertTagShift: function(key, e) {
        e.shiftKey = true; //somehow a hack, but is doing what it should do
        this.handleInsertTag(key, e);
    },
    handleInsertTag: function(key, e) {
        var me = this,
            plug = this.getEditPlugin(),
            editor = plug.editor.mainEditor,
            source = plug.context.record.get('source'),
            tagIdx = Number(key) - 49, //49 shifts tag nr down to 0 for tag 1
            tempNode, parse;

        //key 0 equals to tadIdx -1 and equals to tag nr 10 (which equals to tagIdx 9)
        if(tagIdx < 0) {
            tagIdx = 9;
        }

        if(e.shiftKey) {
            tagIdx = tagIdx + 10;
        }
            
        //do only something when editing targets with tags and tag nrs > 1:
        if(!me.sourceTags || !me.sourceTags[tagIdx]){
            return;
        }

        Ext.Object.each(me.sourceTags[tagIdx], function(id, tag){
            if(editor.getDoc().getElementById(id)){
                return;
            }
            editor.insertMarkup(tag);
            return false;
        });

        e.stopEvent();
        return false;
    },
  /**
   * scrolls to the first segment.
   */
  handleHomeKeyPress: function() {
      this.getSegmentGrid().scrollTo(0);
  },
  /**
   * Handler for watchSegmentBtn
   * @param {Ext.button.Button} button
   */
  watchSegment: function() {
      if(!this.isEditing){
          return;
      }
      var me = this,
          model, config,
          ed = me.getEditPlugin(),
          record = ed.context.record,
          segmentId = record.get('id'),
          isWatched = Boolean(record.get('isWatched')),
          segmentUserAssocId = record.get('segmentUserAssocId'),
          navi = me.getNavi(),
          startText = navi.item_startWatchingSegment,
          stopText = navi.item_stopWatchingSegment,
          but = navi.down('#watchSegmentBtn'),
          success = function(rec, op) {
              //isWatched
              record.set('isWatched', !isWatched);
              record.set('segmentUserAssocId', isWatched ? null : rec.data['id']);
              but.setTooltip(isWatched ? startText : stopText);
              but.toggle(!isWatched, true);
              if(op.action == 'create') {
                  me.fireEvent('watchlistAdded', record, me, rec);
              }
              else {
                  me.fireEvent('watchlistRemoved', record, me, rec);
              }
          },
          failure = function(rec, op) {
              but.setTooltip(isWatched ? stopText : startText);
              but.toggle(isWatched, true);
          };
    
    if (isWatched)
    {
        config = {
            id: segmentUserAssocId
        }
        model = Ext.create('Editor.model.SegmentUserAssoc', config);
        model.getProxy().setAppendId(true);
        model.erase({
            success: success,
            failure: failure
        });
    }
    else
    {
        model = Ext.create('Editor.model.SegmentUserAssoc', {'segmentId': segmentId});
        model.save({
            success: success,
            failure: failure
        });
    }
  },
  
  handleReferneceFilesMessage:function(){
      if(Editor.data.task.get('referenceFiles')){
          var referenceInfoMessage = Ext.create('Editor.view.ReferenceFilesInfoMessage',{}),
          task = new Ext.util.DelayedTask(function(){
              referenceInfoMessage.destroy();
          });
          task.delay(20000);
          referenceInfoMessage.show();
      }
  },

  /***
   * "Reference files info message" window button handler
   */
  onShowReferenceFilesButtonClick:function(){
      var filePanel =this.getFilepanel(); 
      filePanel.expand();
      filePanel.down('referenceFileTree').expand();
  }
  
});
