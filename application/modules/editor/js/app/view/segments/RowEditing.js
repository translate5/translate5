
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
 * @class Editor.view.segments.RowEditing
 * @extends Ext.grid.plugin.RowEditing
 */
Ext.define('Editor.view.segments.RowEditing', {
    extend: 'Ext.grid.plugin.RowEditing',
    alias: 'plugin.segmentrowediting',
    editingAllowed: true,
    openedRecord: null,
    statics: {
        STARTEDIT_MOVEEDITOR: 0,
        STARTEDIT_SCROLLUNDER: 1
    },
    messages: {
        previousSegmentNotSaved: 'Das Segment konnte nicht zum Bearbeiten geöffnet werden, da das vorherige Segment noch nicht korrekt seine Speicherung beendet hatte. Bitte versuchen Sie es noch einmal. Sollte es dann noch nicht funktionieren, drücken Sie bitte F5. Vielen Dank!',
        edit100pWarning: '#UT#Achtung, Sie editieren einen 100% Match!'
    },
    requires: [
        'Editor.view.segments.RowEditor'
    ],
    initEditorConfig: function() {
        var me = this,
            grid = me.grid,
            view = me.view,
            headerCt = grid.headerCt,
            cfg = {
                autoCancel: me.autoCancel,
                errorSummary: me.errorSummary,
                fields: headerCt.getGridColumns(),
                hidden: true,
                // keep a reference..
                editingPlugin: me,
                style: {
                    zIndex: 2
                },
                view: view,
                renderTo: grid.body.el
            };
        return cfg;
    },
    initEditor: function() {
        return new Editor.view.segments.RowEditor(this.initEditorConfig());
    },
    getEditor: function() {
        return this.editor;
    },
    /**
     * Erweitert die Orginalmethode um die "editingAllowed" Prüfung
     * @param {Editor.model.Segment} record
     * @param {Ext.grid.column.Column/Number} columnHeader The Column object defining the column to be edited, or index of the column.
     * @param {Integer} mode, the editor start mode, see the self.STARTEDIT_ constants
     * @returns booelean|void
     */
    startEdit: function(record, columnHeader, mode) {
        var me = this,
            started = false;
            
        if (!me.editor) {
            me.editor = me.initEditor();
        }
        me.editor.setMode(mode);
        
        //to prevent race-conditions, check if there isalready an openedRecord and if yes show an error (see RowEditor.js function completeEdit for more information)
        if (me.openedRecord !== null) {
            Editor.MessageBox.addError(me.messages.previousSegmentNotSaved,' Das Segment konnte nicht zum Bearbeiten geöffnet werden, da das vorherige Segment noch nicht korrekt gespeichert wurde. Im folgenden der Debug-Werte: this.openedRecord.internalId: ' + this.openedRecord.internalId + ' record.internalId: ' + record.internalId);
            return false;
        }
        if (me.editingAllowed && record.get('editable')) {
            if (record.get('matchRate') == 100 && Editor.data.enable100pEditWarning) {
                Editor.MessageBox.addInfo(me.messages.edit100pWarning, 1.4);
            }
            me.openedRecord = record;
            started = me.callParent(arguments);
            return started;
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
    }
});