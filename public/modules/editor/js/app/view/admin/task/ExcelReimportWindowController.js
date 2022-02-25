
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
 * @class Editor.view.admin.log.GridViewController
 * @extends Ext.app.ViewController
 */
Ext.define('Editor.view.admin.task.ExcelReimportWindowController', {
    extend: 'Ext.app.ViewController',
    alias: 'controller.editortaskExcelReimportWindowController',
    listen: {
        component: {
            '#uploadBtn': {
                click: 'upload'
            },
            '#cancelBtn': {
                click: 'close'
            },
            'filefield': {
                change: 'filechange'
            }
        }
    },
    
    upload: function() {
        this.saveTask();
    },
    
    close: function() {
        this.getView().close();
    },
    
    filechange: function() {
        this.getView().down('#feedback').update('');
        this.getView().down('#cancelBtn').setText(this.getView().strings.cancelBtn);
    },
    
    /**
     * starts the upload / form submit
     * 
     */
    saveTask:function(){
        var me = this,
            win = me.getView(),
            form = win.down('form'),
            task = win.task;
        
        //alert('URL: '+Editor.data.restpath+'task/'+task.get('id')+'/excelreimport');
        if (!form.isValid()) {
            return;
        }
        
        win.setLoading(win.strings.loadingWindowMessage);
        win.down('#cancelBtn').setText(win.strings.closeBtn);
        form.submit({
            //Accept Header of submitted file uploads could not be changed:
            //http://stackoverflow.com/questions/13344082/fileupload-accept-header
            //so use format parameter jsontext here, for jsontext see REST_Controller_Action_Helper_ContextSwitch
            params: {
                format: 'jsontext',
                autoStartImport: 0
            },
            timeout: 3600,
            url: Editor.data.restpath+'task/'+task.get('id')+'/excelreimport',
            scope: this,
            success: function(form, submit) {
                if(submit.result.errors) {
                    win.down('#feedback').update(submit.result.errors);
                    win.down('#uploadBtn').hide();
                    win.setLoading(false);
                }
                else {
                    win.close();
                }
                task.store && task.load();
            },
            failure: function(form, submit) {
                var errors;
                win.setLoading(false);
                if(submit.result?.httpStatus == "422") {
                    errors = submit.result.errorsTranslated;
                    form.markInvalid(errors);
                    if(errors && errors.excelreimportUpload) {
                        win.down('#feedback').update({msg: errors.excelreimportUpload.join('<br>'), type: 'error'});
                    }
                }
                else {
                    Editor.app.getController('ServerException').handleException(submit.response, true);
                }
            }
        });
    }
});