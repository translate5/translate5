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
 * @class Editor.view.admin.config.type.SimpleMapController
 * @extends Ext.app.ViewController
 */
Ext.define('Editor.view.admin.config.type.SimpleMapController', {
    extend: 'Ext.app.ViewController',
    alias: 'controller.configTypeSimpleMap',

    record: null,
    jsonField: null,
    preventSave: false,

    /**
     * get the record
     */
    init: function () {
        this.record = this.getView().initialConfig.record;
        this.jsonField = this.getView().initialConfig.jsonField;
        this.preventSave = this.getView().initialConfig.preventSave;
    },

    /**
     * on save button click
     */
    onSave: function () {
        var win = this.getView(),
            grid = win.down('grid'),
            newValue = {},
            confRec = this.record;

        grid.store.each(function (rec) {
            newValue[rec.get('index')] = rec.get('value');
        });

        if(!confRec){
            this.jsonField.setValue(Ext.JSON.encode(newValue));
            win.close();
            return;
        }

        confRec.set('value', newValue);

        if (this.preventSave) {
            win.close();
            return;
        }
        win.setLoading('saving...');
        confRec.save({
            success: function () {
                win.setLoading(false);
                win.close();
            },
            failure: function () {
                win.setLoading(false);
            }
        });
    },

    /**
     * on cancel click button
     */
    onCancel: function () {
        if (this.record) {
            this.record.reject();
        }
        this.getView().close();
    },

    /**
     * on remove click button
     */
    onRemove: function () {
        var win = this.getView(),
            grid = win.down('grid'),
            selMod = grid.getSelectionModel();

        grid.findPlugin('rowediting').cancelEdit();

        grid.store.remove(selMod.getSelection());

        if (grid.store.getCount() > 0) {
            selMod.select(0);
        }
    },

    /**
     * on add click
     */
    onAdd: function () {
        var win = this.getView(),
            grid = win.down('grid'),
            rec;

        rec = grid.store.insert(0, {
            index: '',
            value: ''
        })[0];
        //we set the values after creation, so that the record looks dirty
        rec.set('index', '');
        rec.set('value', '');
    }
});
