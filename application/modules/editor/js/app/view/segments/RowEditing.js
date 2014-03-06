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
 * @class Editor.view.segments.RowEditing
 * @extends Ext.grid.plugin.RowEditing
 */
Ext.define('Editor.view.segments.RowEditing', {
    extend: 'Ext.grid.plugin.RowEditing',
    alias: 'plugin.segments.Rowediting',
    //internes flag: 
    editingAllowed: true,
    //aktuell geöffneter Datensatz
    openedRecord: null,
    messages: {
        previousSegmentNotSaved: 'Das Segment konnte nicht zum Bearbeiten geöffnet werden, da das vorherige Segment noch nicht korrekt seine Speicherung beendet hatte. Bitte versuchen Sie es noch einmal. Sollte es dann noch nicht funktionieren, drücken Sie bitte F5. Vielen Dank!',
        edit100pWarning: '#UT#Achtung, Sie editieren einen 100% Match!'
    },
    requires: [
        'Editor.view.segments.RowEditor'
    ],
    /**
     * überschreibt die parent Methode, unterschiede sind kommentiert
     * @returns Editor.view.segments.RowEditor
     */
    initEditor: function() {
        var me = this,
            grid = me.grid,
            view = me.view,
            headerCt = grid.headerCt;

        //override: Eigene RowEditor Klasse anstatt Ext RowEditor:
        return Ext.create('Editor.view.segments.RowEditor', {
            autoCancel: me.autoCancel,
            errorSummary: me.errorSummary,
            fields: headerCt.getGridColumns(),
            hidden: true,
            //der folgende eventhandler ist im orginal ebenfalls nicht vorhanden
            listeners: {
              show: this.focusEditor
            },
            // keep a reference..
            editingPlugin: me,
            renderTo: view.el
        });
    },
    
    focusEditor: function() {
      this.mainEditor.deferFocus();
    },
    
    /**
     * Erweitert die Orginalmethode um die "editingAllowed" Prüfung
     * @param {Editor.model.Segment} record
     * @param {Ext.grid.column.Column/Number} columnHeader The Column object defining the column to be edited, or index of the column.
     * @returns booelean|void
     */
    startEdit: function(record, columnHeader) {
        //to prevent race-conditions, check if there isalready an openedRecord and if yes show an error (see RowEditor.js function completeEdit for more information)
        if(this.openedRecord !== null){
            Editor.MessageBox.addError(this.messages.previousSegmentNotSaved,' Das Segment konnte nicht zum Bearbeiten geöffnet werden, da das vorherige Segment noch nicht korrekt gespeichert wurde. Im folgenden der Debug-Werte: this.openedRecord.internalId: ' + this.openedRecord.internalId + ' record.internalId: ' + record.internalId);
            return false;
        }
        if(this.editingAllowed && record.get('editable')){
            if(record.get('matchRate') == 100 && Editor.data.enable100pEditWarning){
                Editor.MessageBox.addInfo(this.messages.edit100pWarning, 1.4);
            }
            this.openedRecord = record;
            return this.callParent(arguments);
        }
        return false;
    },
    
    /**
     * erlaubt das Editieren
     */
    enable: function() {
        this.editingAllowed = true;
    },
    /**
     * deaktiviert das Editieren
     */
    disable: function() {
        this.editingAllowed = false;
    },
    destroy: function() {
        delete this.context;
        delete this.openedRecord;
        this.callParent(arguments);
    },
    /**
     * integriert sich in das beforeRangeChange des Scrollers
     */
    beforeRangeChange: function () {
        //@todo replace this direct save call with a event binding
        var me = this;
        if(me.editor && me.editor.mainEditor && me.openedRecord){
            Editor.app.getController('Segments').saveChainStart();
        }
    },
    /**
     * editorDomCleanUp entfernt die komplette (DOM + Komponente) Instanz des Editors. 
     * Die DOM Elemente des Editors befinden sich innerhalb des Grid View Elements. 
     * Dieses wiederrum wird bei einem Range Change neu erstellt. Die Editor Komponente verliert ihre DOM Elemente,
     * es kommt zu komischen Effekten. Mit dieser Methode wird der komplette Editor entfernt, und wird bei einer 
     * erneuten Verwendung komplett neu erstellt.
     */
    editorDomCleanUp: function() {
      var me = this,
      main,
      columns = me.grid.getView().getGridColumns();
      if(! me.editor) {
    	  return;
      }
      me.editing = false;
      me.openedRecord = null;
      main = me.editor.mainEditor;
      //enable stored editor body id to be deleted by GC:
      if(main.bodyGenId && Ext.cache[main.bodyGenId]){
    	  Ext.cache[main.bodyGenId].skipGarbageCollection = false;
    	  delete Ext.cache[me.editor.mainEditor.bodyGenId].skipGarbageCollection;
      }
      Ext.destroy(me.editor);
      Ext.each(columns, function(column) {
        // in den Columns werden die Componenten zur EditorRow abgelegt. 
        // Nach einem Range Change bestehen zar noch diese Componenten, aber die zugehörigen Dom Elemente fehlen.
        if(column.field){
          column.field.destroy();
          delete column.field;
        }
      });
      delete me.editor;
    }
    
});
