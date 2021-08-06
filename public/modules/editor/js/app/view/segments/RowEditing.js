
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
 * @class Editor.view.segments.RowEditing
 * @extends Ext.grid.plugin.RowEditing
 */
Ext.define('Editor.view.segments.RowEditing', {
    extend: 'Ext.grid.plugin.RowEditing',
    alias: 'plugin.segmentrowediting',
    editingAllowed: true,
    clicksToMoveEditor: 2,
    autoCancel: false,
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
    //default ext events + our beforestartedit, check on ext update
    relayedEvents: [
        'beforestartedit',
        'beforeedit',
        'edit',
        'validateedit',
        'canceledit'
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
    activateCell: function() {
        var me = this,
            result;

        me.editByCellActivation = true;
        result = me.callParent(arguments);
        me.editByCellActivation = false;
        return result;
    },
    /**
     * Erweitert die Orginalmethode um die "editingAllowed" Prüfung
     * @param {Editor.model.Segment} record
     * @param {Ext.grid.column.Column/Number} columnHeader The Column object defining the column to be edited, or index of the column.
     * @param {Integer} mode, the editor start mode, see the self.STARTEDIT_ constants
     * @returns boolean|void
     */
    startEdit: function(record, columnHeader, mode) {
        var me = this;

        if (me.context && me.context.record && me.context.record === record) {
            //startEdit by actionable is called twice because of different events
            // in this case jump out
            return false;
        }
        if(me.fireEvent('beforestartedit', me, [record, columnHeader, mode]) === false) {
            return false;
        }
            
        if (!me.editor) {
            me.editor = me.initEditor();
        }
        //to prevent race-conditions, check if there isalready an opened record and if yes show an error (see RowEditor.js function completeEdit for more information)
        if (me.context && me.context.record) {
            Editor.MessageBox.addError(me.messages.previousSegmentNotSaved);
            Ext.raise({
                msg: 'The segment could not be opened for editing, since the previously opened segment was not correctly saved yet.',
                contextRecordInternalId: this.context.record.internalId,
                recordInternalId: record.internalId
            });
            return false;
        }
        if (!me.editingAllowed || !record.get('editable')) {
            return false;
        }
        if (record.get('matchRate') == 100 && Editor.app.getTaskConfig('editor.enable100pEditWarning')) {
            Editor.MessageBox.addInfo(me.messages.edit100pWarning, 1.4);
        }
        me.editor.setMode(mode);
        return me.callParent(arguments);
    },
    //fixing https://www.sencha.com/forum/showthread.php?309102-ExtJS-6.0.0-RowEditor-Plugin-completeEdit-does-not-set-context-to-null&p=1128826#post1128826
    cancelEdit: function() {
        this.callParent(arguments);
        if(!this.editing) {
            this.context = null;
        }
    },
    //fixing https://www.sencha.com/forum/showthread.php?309102-ExtJS-6.0.0-RowEditor-Plugin-completeEdit-does-not-set-context-to-null&p=1128826#post1128826
    completeEdit: function() {
        this.callParent(arguments);
        //if editing was successfully finished we should also reset context
        if(!this.editing) {
            this.context = null;
        }
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
        this.callParent(arguments);
    }
});