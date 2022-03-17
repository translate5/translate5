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
 * @class Editor.view.admin.projectWizard.GridFormField
 * @extends Ext.panel.Panel
 */
Ext.define('Editor.view.admin.projectWizard.GridFormField', {
    extend:'Ext.grid.Panel',
    alias: 'widget.gridFormField',
    requires:[
        'Editor.view.admin.projectWizard.GridFormFieldViewModel'
    ],
    viewModel: {
        type: 'gridFormField'
    },
    mixins : {
        field : 'Ext.form.field.Base'
    },

    dockedItems:[{
        dock: 'top',
        xtype:'displayfield',
        padding: 10,
        fieldLabel:false,
        fieldCls:'redTextColumn',
        bind:{
            value : '{uploadErrorMsg}',
            hidden: '{!uploadErrorMsg}'
        }
    }],

    /***
     * Custom isValid implementation for the upload grid. With this, the grid is part of the importWizard form validation, and
     * if there are files with error, the form will not be submitted
     * @returns {*|boolean}
     */
    isValid: function() {
        var me = this,
            errors = me.getErrors(),
            isValid = Ext.isEmpty(errors);
        if (!me.preventMark) {
            if (isValid) {
                me.clearInvalid();
            } else {
                me.markInvalid(errors);
            }
        }
        return isValid;
    },

    /***
     * Check if the grid there are files in state error.
     * This is only used for internal validation
     * @returns {*[]}
     */
    getErrors:function (){
        var me = this,
            errors = [];
        me.getStore().each(function(record) {
            if((record.get('type') === Editor.model.admin.projectWizard.File.TYPE_ERROR) || Ext.isEmpty(record.get('error')) === false){
                errors.push(record.get('error'));
            }
        });
        return errors;
    },

    /***
     * Custom implementation for invalid. This will add invalid css class to the grid
     * @param error
     */
    markInvalid: function (error){
        if(Ext.isEmpty(error)){
            return;
        }
        var me = this,
            gridview  = me.getView(),
            tpl = Ext.isArray(error) ? error.join('<br/>') : error;

        gridview.addCls('invalidGridBorder');
        me.getViewModel().set('uploadErrorMsg',tpl);
        gridview.refresh();
    },

    /***
     * Custom implementation for clear invalid. This will remove the invalid css clss from the grid.
     */
    clearInvalid: function() {
        var me = this,
            gridview  = me.getView();
        gridview.removeCls('invalidGridBorder');
        me.getViewModel().set('uploadErrorMsg',null);
    }
});