
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

/**
 * Translations: since all the configurations are not translated, we just keep the text here also just in english
 * @class Editor.plugins.Okapi.view.UrlConfigViewController
 * @extends Ext.app.ViewController
 */
Ext.define('Editor.plugins.Okapi.view.UrlConfigViewController', {
    extend: 'Ext.app.ViewController',
    alias: 'controller.pluginOkapiUrlConfig',

    record: null,

    newRecordCounter: 0,

    /**
     * get the record
     */
    init: function() {
        this.record = this.getView().initialConfig.record;
    },

    /**
     * on save button click
     */
    onSave: function() {
        var win = this.getView(),
            grid = win.down('grid'),
            newValue = {},
            confRec = this.record;

        grid.store.each(function(rec) {
            newValue[rec.get('id')] = rec.get('url');
        });
        confRec.set('value', newValue);
        win.setLoading('saving...');
        confRec.save({
            success: function() {
                win.setLoading(false);
                win.close();
            },
            failure: function() {
                win.setLoading(false);
            }
        });
    },
    /**
     * on cancel click button
     */
    onCancel: function() {
        this.record.reject();
        this.getView().close();
    },
    /**
     * on remove click button
     */
    onRemove: function() {
        this.removeSelectedRow(true);
    },
    /**
     * on add click
     */
    onAdd: function() {
        var win = this.getView(),
            grid = win.down('grid'),
            rec;
        
        rec = grid.store.insert(0, {
            id: null,
            url: null
        })[0];
        //we set the values after creation, so that the record looks dirty
        rec.set('id', (this.newRecordCounter === 0 ? 'NEW INSTANCE' : 'NEW INSTANCE ' + (this.newRecordCounter + 1)));
        rec.set('url', 'https://');

        grid.getPlugin('urlConfigRowEditor').startEdit(rec, 1);
        this.newRecordCounter++;
    },
    /**
     * called when the user cancels an added row in the row-editor
     * @param {Ext.grid.plugin.Editing} editor
     * @param {Object} context
     */
    onAddRowCancel: function(editor, context) {
        if(context.record && context.record.get('id') && context.record.get('id').startsWith('NEW INSTANCE')){
            this.removeSelectedRow(false);
        }
    },
    /**
     * removes the selected row and optionally selects the next
     * @param {Boolean} doSelectNext
     */
    removeSelectedRow: function(doSelectNext) {
        var win = this.getView(),
            grid = win.down('grid'),
            selMod = grid.getSelectionModel();
        grid.findPlugin('rowediting').cancelEdit();
        grid.store.remove(selMod.getSelection());

        if (doSelectNext && grid.store.getCount() > 0) {
            selMod.select(0);
        }
    }
});