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
 */
Ext.define('Editor.view.admin.config.type.JsonEditorController', {
    extend: 'Ext.app.ViewController',
    alias: 'controller.jsonEditor',

    record: null,
    preventSave: false,

    /**
     * get the record
     */
    init: function () {
        this.record = this.getView().initialConfig.record;
        this.preventSave = this.getView().initialConfig.preventSave;
    },

    /**
     * on save button click
     */
    onSave: function () {
        if (this.preventSave) {
            return;
        }
        var win = this.getView();

        try {
            // validate if the text area value is valid json. If yes set the value to the record
            var value = Ext.decode(this.getView().down('textarea').getValue());
            this.record.set('value', value);

            win.setLoading('saving...');

            this.record.save({
                success: function () {
                    win.setLoading(false);
                    win.close();
                },
                failure: function () {
                    win.setLoading(false);
                }
            });
        }catch (e) {
            Ext.Msg.show({
                title: 'Invalid JSON',
                message: e.message,
                buttons: Ext.MessageBox.OK,
                icon: Ext.Msg.ERROR
            });
        }
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

    validateJsonValue: function (){
        var view = this.getView(),
            textArea = view.down('textarea');
        try {
            Ext.decode(textArea.getValue());
            view.getViewModel().set('isJsonValid', true);
        }catch (e) {
            view.getViewModel().set('isJsonValid', false);
        }
    }

});
