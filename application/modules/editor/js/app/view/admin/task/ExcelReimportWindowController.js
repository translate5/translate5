
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
    strings: {
        loadingWindowMessage: '#UT# Datei wird hochgeladen',
    },

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
        this.saveTask();
    },
    
    close: function() {
        this.getView().close();
    },
    
    /***
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
            url: Editor.data.restpath+'task/'+task.get('id')+'/excelreimport',
            scope: this,
            success: function(form, submit) {
                alert('.. upload success :-)');
                win.close();
            },
            failure: function(form, submit) {
                win.setLoading(false);
                alert('.. upload failed :-(');
            }
        });
    },
    

});