
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

Ext.define('Editor.view.admin.user.AssocViewController', {
    extend: 'Ext.app.ViewController',
    alias: 'controller.adminUserAssoc',

    listen:{
        component:{
            '#saveAssocBtn':{
                click:'onSaveAssocBtnClick'
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
            '#workflowCombo':{
                change:'onWorkflowComboChange'
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
        },
        store:{
            '#admin.UserAssocDefault':{
                load:'onAdminUserAssocDefaultStoreLoad'
            }
        }
    },

    strings:{
        deleteUserMessage: '#UT#Soll dieser Eintrag wirklich gelöscht werden?',
        deleteUserTitle: '#UT#Eintrag löschen?',
        assocSaved: '#UT#Benutzerzuweisung gespeichert'
    },

    onSaveAssocBtnClick : function(){
        var me = this,
            formPanel = me.lookup('assocForm'),
            form = formPanel.getForm(),
            rec = formPanel.getRecord();

        form.updateRecord(rec);

        if(! form.isValid()) {
            return;
        }
        rec.save({
            failure: function(rec, op) {
                var errorHandler = Editor.app.getController('ServerException');
                errorHandler.handleFormFailure(form, rec, op);
            },
            success: function() {
                me.getView().down('grid').getStore().load();
                Editor.MessageBox.addSuccess(me.strings.assocSaved);
                me.resetRecord();
            }
        });
    },

    onCancelAssocBtnClick : function(){
        var me=this;
        me.resetRecord();
    },

    onAddAssocBtnClick : function(){
        var me=this,
            formPanel = me.lookup('assocForm');
        me.resetRecord();
        formPanel.setDisabled(false);
    },

    onDeleteAssocBtnClick : function (){
        var me = this;
        // Ask user to confirm this action
        Ext.Msg.confirm(me.strings.deleteUserTitle, me.strings.deleteUserMessage, function (result) {
            // User confirmed yes
            if (result === 'yes') {
                var record = me.getViewModel().get('selectedAssocRecord'),
                    store = me.getView().down('grid').getStore();
                record.dropped = true;
                record.save();
                store.load();
                me.onCancelAssocBtnClick();
            }
        });
    },

    onReloadAssocBtnClick : function (){
        var me=this;
        me.getView().down('grid').getStore().load();
        me.resetRecord();
    },

    onAssocGridSelect: function (grid,record) {
        var me=this,
            form = me.lookup('assocForm');
        form.getForm().loadRecord(record.clone());
        form.setDisabled(false);
    },

    /***
     * On default user assoc store load event handler
     */
    onAdminUserAssocDefaultStoreLoad:function (){
        this.resetRecord();
    },


    /***
     * On workflow step name change event handler
     * @param combo
     * @param record
     */
    onWorkflowStepNameSelect: function (combo,record) {
        var me = this,
            form = me.lookup('assocForm').getForm(),
            deadlineDate = form.findField('deadlineDate');
        deadlineDate.setValue(me.getConfigDeadlineDate(record.get('id')));
    },

    onWorkflowComboChange : function (combo, newValue){
        var me = this;
        me.getView().loadAssocData();
        me.getViewModel().getStore('workflowSteps').loadForWorkflow(newValue);
        me.resetRecord();
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
     *
     * For each form field check and validate if there is a duplicate record in the store
     */
    checkDuplicates:function (){
        var me = this,
            grid = me.getView().down('grid'),
            form = me.lookup('assocForm').getForm(),
            record = form.getRecord(),
            store = grid.getStore();

        form.updateRecord();

        // if the record exist on the server, ignore the check ( the check is being triggered via selecting record in the grid)
        if(!record || !record.phantom){
            return;
        }

        form.getFields().each(function (field){
            if(field.isVisible() && !field.allowBlank){
                
                field.duplicateRecord = false;
                field.clearInvalid();
                
                store.each(function (r){
                    field.duplicateRecord = r.getUnique() === record.getUnique();
                    if(field.duplicateRecord){
                        return false;
                    }
                });
                
            }
        });
        form.isValid();
    },

    /***
     * Resets the current form record and load new record into the form
     */
    resetRecord:function (record){
        var me=this,
            formPanel = me.lookup('assocForm'),
            form = formPanel.getForm();
        if(!record){
            record = me.getView().getDefaultFormRecord();
        }
        me.getView().fireEvent('addnewassoc', record, formPanel);
        form.loadRecord(record);
    }
});
