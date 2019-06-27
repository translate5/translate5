
/*
START LICENSE AND COPYRIGHT

 This file is part of translate5
 
 Copyright (c) 2013 - 2017 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

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
        }
    },
    
    upload: function() {
        alert('.. @TODO: upload');
        this.saveTask();
        this.getView().close();
    },
    
    close: function() {
        this.getView().close();
    },
    
    /***
     * starts the upload / form submit
     * 
     */
    saveTask:function(){
        alert('.. @TODO: in function upload');
        return;
        
        var me = this,
            win = me.getView(),
            form = win.down('form');
        
        win.setLoading(me.strings.loadingWindowMessage);
        
        form.submit({
            //Accept Header of submitted file uploads could not be changed:
            //http://stackoverflow.com/questions/13344082/fileupload-accept-header
            //so use format parameter jsontext here, for jsontext see REST_Controller_Action_Helper_ContextSwitch
            
            params: {
                format: 'jsontext',
                autoStartImport: 0
            },
            timeout: 3600,
            url: Editor.data.restpath+'task',
            scope: this,
            success: function(form, submit) {
                var task = me.getModel('admin.Task').create(submit.result.rows);
                me.fireEvent('taskCreated', task);
                win.setLoading(false);
                me.getAdminTasksStore().load();
                
                //set the store reference to the model(it is missing), it is used later when the task is deleted
                task.store=me.getAdminTasksStore();
                
                me.setCardsTask(task);
            },
            failure: function(form, submit) {
                var card, errorHandler = Editor.app.getController('ServerException');
                win.setLoading(false);
                if(submit.failureType == 'server' && submit.result && !submit.result.success){
                    if(submit.result.httpStatus == "422") {
                        win.getLayout().setActiveItem('taskMainCard');
                        form.markInvalid(submit.result.errorsTranslated);
                    }
                    else {
                        card = win.down('#taskUploadCard');
                        if(card.isVisible()){
                            card.update(errorHandler.renderHtmlMessage(me.strings.taskError, submit.result));
                        }
                        errorHandler.handleException(submit.response);
                    }
                }
            }
        });
    },
    

});