
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

Ext.define('Editor.view.admin.contentProtection.inputMapping.CreateWindowViewController', {
    extend: 'Ext.app.ViewController',
    alias: 'controller.adminCreateInputMappingWindowViewController',
    listen: {
        component: {
            '#adminCreateInputMappingWindow #create-btn': {
                click: 'saveNewInputMapping'
            },
            '#adminCreateInputMappingWindow #cancel-btn': {
                click: 'handleUserCancel'
            }
        }
    },
    handleUserCancel: function() {
        var win = this.getView();
        win.down('form').getForm().reset();
        win.close();
    },
    saveNewInputMapping: function() {
        var me = this,
            win = me.getView(),
            form = win.down('form').getForm(),
            record = form.getRecord(),
            store = Ext.StoreManager.get('admin.contentProtection.InputMappingStore');

        if (!form.isValid()) {
            return;
        }

        // Update associated record with values
        form.updateRecord();

        win.setLoading(true);

        record.set('contentRecognitionId', form.getValues().contentRecognitionId);

        const callback = (btn) => {
            if ('yes' !== btn) {
                win.setLoading(false);
            }

            record.save({
                preventDefaultHandler: true,
                success: function () {
                    Editor.MessageBox.addSuccess('Success');
                    store.load();
                    win.setLoading(false);
                    win.close();
                },
                failure: function (rec, op) {
                    win.setLoading(false);
                    Editor.app.getController('ServerException').handleFormFailure(form, rec, op);
                }
            });
        };

        Ext.MessageBox.confirm(
            Editor.data.l10n.contentProtection.mapping.input.confirm_add_title,
            Editor.data.l10n.contentProtection.mapping.input.confirm_add_message,
            callback
        );
    }
});
