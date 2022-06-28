
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
 * @class Editor.view.LanguageResources.SearchGridViewController
 * @extends Ext.app.ViewController
 */
Ext.define('Editor.view.LanguageResources.pivot.AssocViewController', {
    extend: 'Ext.app.ViewController',
    alias: 'controller.languageResourcePivotAssoc',

    listen: {
        component: {
            '#languageResourcePivotAssoc checkcolumn[dataIndex="checked"]': {
                checkchange: 'handleAssocCheckChange'
            },
            '#startPivotPretranslation':{
                click:'onStartPivotPretranslationClick'
            }
        }
    },

    handleAssocCheckChange: function(column, rowIdx, checked,record){
        this.saveRecord(record);
    },

    /**
     * Save assoc record
     */
    saveRecord: function(record){
        var me = this,
            view = me.getView(),
            params = {},
            method = 'DELETE',
            url = Editor.data.restpath+'languageresourcetaskpivotassoc',
            checkedData = Ext.JSON.encode({
                languageResourceId: record.get('languageResourceId'),
                taskGuid: record.get('taskGuid')
            });

        if(record.get('checked')) {
            method = Ext.isNumeric(record.get('id')) ? 'PUT' : 'POST';
            params = {data: checkedData};
        }

        if(method !== 'POST') {
            url = url + '/'+record.get('associd');
        }

        Ext.Ajax.request({
            url:url,
            method: method,
            params: params,
            success: function(response){
                if(record.data.checked){
                    var resp = Ext.util.JSON.decode(response.responseText),
                        newId = resp.rows['id'];
                    record.set('associd', newId);
                    Editor.MessageBox.addSuccess(view.strings.assocSave);
                }
                else {
                    record.set('associd', 0);
                    Editor.MessageBox.addSuccess(view.strings.assocDeleted);
                }
                record.commit();
            },
            failure: function(response){
                Editor.app.getController('ServerException').handleException(response);
            }
        });
    },

    /***
     *
     */
    onStartPivotPretranslationClick: function (){
        var me = this,
            task = me.getView().task;
        if(! task){
            return;
        }
        me.queueWorker(task,function (){
            task.load({
                success: function() {
                    Editor.MessageBox.addInfo(me.getView().strings.workerQueued);
                },
            });
        });
    },

    /***
     * Queue pivot pre-translation worker. If callback provided, it will be called
     * on request success callback
     * @param task
     * @param callback
     */
    queueWorker: function (task,callback){
        if(!task){
            return;
        }

        Ext.Ajax.request({
            url: Editor.data.restpath+'languageresourcetaskpivotassoc/pretranslation/batch',
            method: 'post',
            params:{
                taskGuid: task.get('taskGuid')
            },
            scope: this,
            success: callback,
            failure: function (response) {
                Editor.app.getController('ServerException').handleException(response);
            }
        });
    }
});
