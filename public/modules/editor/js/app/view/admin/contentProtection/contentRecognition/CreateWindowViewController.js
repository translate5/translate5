
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

Ext.define('Editor.view.admin.contentProtection.contentRecognition.CreateWindowViewController', {
    extend: 'Ext.app.ViewController',
    alias: 'controller.adminCreateContentRecognitionWindowViewController',
    listen: {
        component: {
            '#adminCreateContentRecognitionWindow #create-btn': {
                click: 'saveNewContentRecognition'
            },
            '#adminCreateContentRecognitionWindow #cancel-btn': {
                click: 'handleUserCancel'
            }
        }
    },
    handleUserCancel: function() {
        var win = this.getView();
        win.down('form').getForm().reset();
        win.close();
    },
    saveNewContentRecognition: function() {
        var me = this,
            win = me.getView(),
            form = win.down('form').getForm(),
            record = form.getRecord(),
            store = Ext.StoreManager.get('admin.contentProtection.ContentRecognitionStore');

        if (!form.isValid()) {
            return;
        }

        // Update associated record with values
        form.updateRecord();

        win.setLoading(true);

        if (0 === record.get('languageId')) {
            record.set('languageId', null);
        }

        record.save({
            preventDefaultHandler: true,
            success: function() {
                Editor.MessageBox.addSuccess('Success');
                store.load();
                win.setLoading(false);
                win.close();
            },
            failure: function(rec, op) {
                win.setLoading(false);
                Editor.app.getController('ServerException').handleFormFailure(form, rec, op);
            }
        });
    },
    onFormatFieldKeyUp: function (event, el) {
        var value = el.value,
            form = this.getView().down('form'),
            resultContainer = form.down('#formatRenderExample');

        if (this.abortController) {
            this.abortController.abort();
        }

        if ('' === value.trim() || !form.down('#type').value) {
            resultContainer.update('');

            return;
        }

        // Create a new AbortController for the current request
        this.abortController = new AbortController();

        Ext.Ajax.request({
            method: 'get',
            url: Editor.data.restpath + 'contentprotection/contentrecognition/testformat',
            params: {
                type: form.down('#type').value,
                ruleFormat: value
            },
            success: function (response) {
                var responseData = Ext.decode(response.responseText);

                resultContainer.update(responseData.rows.example);
            },
            failure: function (response) {
                if (response.statusText !== 'AbortError') {
                    console.error('Request failed: ', response.statusText);
                }
            },
            // Attach the AbortSignal to the request
            signal: this.abortController.signal
        });
    }
});
