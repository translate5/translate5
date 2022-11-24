
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

Ext.define('Editor.view.admin.task.reimport.ReimportWindowViewController', {
    extend: 'Ext.app.ViewController',
    alias: 'controller.adminTaskReimportReimportWindow',

    listen: {
        component: {
            '#saveBtn':{
                click:'onImportButtonClick'
            }
        }
    },

    onImportButtonClick: function(button){
        var me = this,
            view = me.getView(),
            form = view.down('form'),
            record = view.record,
            task = view.task,
            locales = Editor.data.l10n.projectOverview.taskManagement.taskReimportWindow;

        if(!form.isValid()) {
            return;
        }

        task.set('state','reimport');

        view.setLoading(true);

        form.submit({
            params: {
                format: 'jsontext',
                fileId: record.get('id'),
            },
            url: Editor.data.restpath+'taskid/'+task.get('id')+'/file/',
            scope: me,
            success: function(form, submit) {
                Editor.MessageBox.addSuccess(locales.fileReimportRunning);
                record.load();
                view.setLoading(false);
                view.close();
            },
            failure: function(form, submit) {
                Editor.MessageBox.addWarning(locales.fileReimportFinishedWithErrors);
                Editor.app.getController('ServerException').handleException(submit.response);
                view.setLoading(false);
                task.load();
            }
        });
    },

    /***
     * File change event handler
     * @param field
     * @param fileName
     */
    onFileFieldChange: function (field, fileName){
        var me = this,
            view = me.getView(),
            form = view.down('form'),
            record = view.record,
            infoLabel = view.down('#nameDontMatchInfoLabel');


        // show info lable that the filename of the uploaded file and the one to be replaced are not matching
        if( !record || Ext.isEmpty(fileName)){
            infoLabel.setVisible(false);
            return;
        }

        fileName = fileName.replace(/C:\\fakepath\\/g, '');

        infoLabel.setVisible(record.get('filename') !== fileName);
    }
});