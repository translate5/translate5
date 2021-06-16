
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
 translate5 plug-ins that are distributed under GNU AFFERO GENERAL PUBLIC LICENSE version 3:
 Please see http://www.translate5.net/plugin-exception.txt or plugin-exception.txt in the root
 folder of translate5.
  
 @copyright  Marc Mittag, MittagQI - Quality Informatics
 @author     MittagQI - Quality Informatics
 @license    GNU AFFERO GENERAL PUBLIC LICENSE version 3 with plugin-execption
             http://www.gnu.org/licenses/agpl.html http://www.translate5.net/plugin-exception.txt

END LICENSE AND COPYRIGHT
*/

Ext.define('Editor.view.admin.user.AssocViewController', {
    extend: 'Ext.app.ViewController',
    alias: 'controller.adminUserAssoc',

    listen:{
        component:{
            '#saveAssocBtn':{
                click:'onSaveAssocBtnClick',
            },
            '#cancelAssocBtn': {
                click:'onCancelAssocBtnClick'
            },
            '#addAssocBtn': {
                click:'onAddAssocBtnClick'
            },
            '#deleteAssocBtn': {
                click:'onDeleteAssocBtnClick'
            },
            '#reloadAssocBtn': {
                click:'onReloadAssocBtnClick'
            },
            '#adminUserAssocGrid':{
                select: 'onAssocGridSelect'
            },
            '#workflowStepName': {
                select: 'onWorkflowStepNameSelect',
                change:'checkDuplicates'
            },
            '#workflow':{
                change:'checkDuplicates'
            },
            '#sourceLang':{
                change:'checkDuplicates'
            },
            '#targetLang':{
                change:'checkDuplicates'
            },
            '#userGuid':{
                change:'checkDuplicates'
            }
        }
    },

    onSaveAssocBtnClick : function(){

        var me = this,
            view = me.getView(),
            form = view.down('form').getForm(),
            store = view.down('grid').getStore();

        if (form.isValid()) {
            store.sync();
        }
    },

    onCancelAssocBtnClick : function(){
        this.cancelEditRecord();
    },

    onAddAssocBtnClick : function(){
        var me = this,
            grid = me.getView().down('grid'),
            newRecord = Ext.create('Editor.model.admin.UserAssocDefault',{
                customerId : me.getViewModel().get('selectedCustomer').get('id'),
                workflow: Editor.data.frontend.hasOwnProperty('import.defaultTaskWorkflow') ? Editor.data.frontend.import.defaultTaskWorkflow : 'default'
            });
        grid.getStore().rejectChanges();
        grid.getStore().add(newRecord);
        grid.setSelection(newRecord);
    },

    onDeleteAssocBtnClick : function (){
        var me = this;
        // Ask user to confirm this action
        Ext.Msg.confirm('Confirm Delete', 'Are you sure you want to delete this user?', function (result) {
            // User confirmed yes
            if (result === 'yes') {
                var record = me.getViewModel().get('selectedAssocRecord'),
                    store = me.getView().down('grid').getStore();
                // Delete record from store
                store.remove(record);
                store.sync();
            }

        });
    },

    onReloadAssocBtnClick : function (){
        var me=this;
        me.getView().down('grid').getStore().load();
    },

    onAssocGridSelect: function () {

    },

    /***
     * On workflow step name change event handler
     * @param combo
     * @param record
     */
    onWorkflowStepNameSelect: function (combo,record) {
        var me = this,
            form = me.getView().down('form').getForm(),
            deadlineDate = form.findField('deadlineDate');
        deadlineDate.setValue(me.getConfigDeadlineDate(record.get('id')));
    },

    /***
     * Get default deadline date for the current customer for given workflow step name
     * @param step
     * @returns {*}
     */
    getConfigDeadlineDate:function (step){
        var me = this,
            configStore = me.getView().up('#displayTabPanel').down('adminConfigGrid').getStore();
        return configStore.getConfig('workflow.default.'+step+'.defaultDeadlineDate');
    },

    /***
     * Cancels form editing and resets the current form values
     */
    cancelEditRecord:function (){
        var me=this,
            grid = me.getView().down('grid');

        grid.getStore().rejectChanges();
        grid.getSelectionModel().deselectAll();
    },

    /***
     * Check if the current new form record exist already.
     * @param field
     */
    checkDuplicates:function (field){
        var me = this,
            grid = me.getView().down('grid'),
            selection = grid.getSelection(),
            record = selection.length === 1 ? selection[0] : false,
            store = grid.getStore();

        // if the record exist on the server, ignore the check ( the check is being triggered via selecting record in the grid)
        if(!record || !record.phantom){
            return;
        }

        store.each(function (r){
            if(r.phantom){
                return true;
            }
            field.duplicateRecord = r.toString() === record.toString();
            field.clearInvalid();
            if(field.duplicateRecord){
                field.markInvalid('Duplicate field');
                record.set(field.getName(),null);
                return false;
            }
        });
    }
});
