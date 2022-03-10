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
 * @class Editor.view.admin.projectWizard.UploadGrid
 * @extends Ext.panel.Panel
 */
Ext.define('Editor.view.admin.projectWizard.UploadGrid', {
    extend:'Ext.grid.Panel',
    alias: 'widget.wizardUploadGrid',
    requires:[
        'Editor.view.admin.projectWizard.UploadGridViewController',
        'Editor.view.admin.projectWizard.UploadGridViewModel',
        'Editor.view.admin.projectWizard.FileButton',
        'Editor.model.admin.projectWizard.File'
    ],

    mixins : {
        field : 'Ext.form.field.Base'
    },

    controller:'wizardUploadGrid',
    viewModel: {
        type: 'wizardUploadGrid'
    },
    reference: 'uploadgrid',
    itemId:'importUpload',
    cls:'importUpload',
    bind: {
        store: '{files}'
    },
    listeners: {
        //for adding mor functionality like drop zones, deleting etc, see: https://stackoverflow.com/questions/33562928/drag-and-drop-file-using-extjs-6
        drop: {
            element: 'el',
            fn: 'onDrop'
        },
        scope: 'controller'
    },

    strings:{
        gridEmptyText:'#UT#Ziehen Sie die Dateien entweder hierher, um sie als Arbeitsdateien hinzuzufügen, oder klicken Sie auf eine der obigen Schaltflächen, um andere Dateitypen zu wählen.',
        workFilesFilesButton:'#UT#Arbeitsdatei(en)',
        pivotFilesFilesButton:'#UT#Pivot-Datei(en)',
        removeFilesFilesButton:'#UT#Datei löschen',
        targetLang:'#UT#Zielsprache',
        file:'#UT#Datei',
        type:'#UT#Typ',
        size:'#UT#Größe',
        errorColumnText:'#UT#Fehler',
        workFilesTypeText:'#UT#Arbeitsdatei',
        pivotFilesTypeText:'#UT#Pivot-Datei',
        fileMix:'#UT#Wählen Sie entweder eine ZIP-Datei oder mehrere andere Dateien. Ein Mix aus ZIP-Dateien und anderen Dateien ist nicht möglich!',
        referenceFilesFilesButton:'#UT#Referenz-Dateien(en)',
        referenceFilesTypeText:'#UT#Referenz-Datei'
    },

    initConfig: function(instanceConfig) {
        var me = this,
            config = {
                height: 300,
                allowDeselect : true,
                sortableColumns:false,
                tbar: [{
                    xtype: 'wizardFileButton',
                    glyph: 'f067@FontAwesome5FreeSolid',
                    text: me.strings.workFilesFilesButton,
                    name:'workFilesFilesButton',
                    tooltip:me.strings.fileMix,
                    componentCls: 'disabledButtonTooltip',
                    bind: {
                        disabled: '{isZipUpload}'
                    },
                    listeners: {
                        change: 'onManualAdd'
                    }
                },{
                    xtype: 'wizardFileButton',
                    glyph: 'f067@FontAwesome5FreeSolid',
                    text: me.strings.pivotFilesFilesButton,
                    name:'pivotFilesFilesButton',
                    tooltip:me.strings.fileMix,
                    componentCls: 'disabledButtonTooltip',
                    bind: {
                        disabled: '{isZipUpload}'
                    },
                    listeners: {
                        change: 'onManualAddPivot'
                    }
                },{
                    xtype: 'wizardFileButton',
                    glyph: 'f067@FontAwesome5FreeSolid',
                    text: me.strings.referenceFilesFilesButton,
                    name:'referenceFilesFilesButton',
                    tooltip:me.strings.fileMix,
                    componentCls: 'disabledButtonTooltip',
                    bind: {
                        disabled: '{isZipUpload}'
                    },
                    listeners: {
                        change: 'onManualAddReference'
                    }
                },{
                    xtype: 'tbseparator'
                },{
                    xtype: 'button',
                    text: me.strings.removeFilesFilesButton,
                    handler: 'removeFiles',
                    bind: {
                        disabled: '{!uploadgrid.selection}'
                    }
                }],
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
                viewConfig: {
                    emptyText: me.strings.gridEmptyText,
                    markDirty: false,
                    getRowClass: function (record) {
                        var res = [];
                        if(record.get('type') === Editor.model.admin.projectWizard.File.TYPE_ERROR){
                            res.push('error');
                        }
                        return res.join(' ');
                    }
                },
                selModel: {
                    selType: 'checkboxmodel'
                },
                columns: [{
                    xtype: 'gridcolumn',
                    width: 140,
                    dataIndex: 'targetLang',
                    renderer:me.langRenderer,
                    text: me.strings.targetLang,
                },{
                    xtype: 'gridcolumn',
                    dataIndex: 'name',
                    flex: 1,
                    text: me.strings.file
                },{
                    xtype: 'gridcolumn',
                    width: 90,
                    renderer:me.typeRenderer,
                    dataIndex: 'type',
                    tdCls: 'type',
                    text: me.strings.type
                },{
                    xtype: 'gridcolumn',
                    width: 90,
                    formatter: 'fileSize',
                    dataIndex: 'size',
                    text: me.strings.size
                }]
            };
        
        if (instanceConfig) {
            me.self.getConfigurator().merge(me, config, instanceConfig);
        }
        return me.callParent([ config ]);
    },


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
            if(record.get('type') === Editor.model.admin.projectWizard.File.TYPE_ERROR){
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
    },

    /***
     * Custom renderer for the type column
     * @param val
     * @param meta
     * @param record
     * @returns {string|*}
     */
    typeRenderer: function (val,meta,record) {
        var me = this;
        switch (val){
            case Editor.model.admin.projectWizard.File.TYPE_ERROR:
                meta.tdAttr= 'data-qtip="'+record.get('error')+'"';
                return me.strings.errorColumnText;
            case Editor.model.admin.projectWizard.File.TYPE_WORKFILES:
                return me.strings.workFilesTypeText;
            case Editor.model.admin.projectWizard.File.TYPE_PIVOT:
                return me.strings.pivotFilesTypeText;
            case Editor.model.admin.projectWizard.File.TYPE_REFERENCE:
                return me.strings.referenceFilesTypeText;
        }
        return val;
    },

    /**
     * renders the value of the language columns
     * @param {String} val
     * @returns {String}
     */
    langRenderer: function (val, md) {
        var lang = Ext.StoreMgr.get('admin.Languages').getById(val),
            label;
        if (lang) {
            label = lang.get('rfc5646');
            md.tdAttr = 'data-qtip="' + label + '"';
            return label;
        }
        if (!val || val === "0") {
            return '';
        }
        return '-';
    }
});