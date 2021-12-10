
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
 * @class Editor.view.admin.TaskUpload
 * @extends Ext.panel.Panel
 */
Ext.define('Editor.view.admin.projectWizard.UploadGrid', {
    extend:'Ext.grid.Panel',
    alias: 'widget.wizardUploadGrid',
    requires:[
        'Editor.view.admin.projectWizard.UploadGridViewController',
        'Editor.view.admin.projectWizard.UploadGridViewModel',
        'Editor.view.admin.projectWizard.FileButton'
    ],
    controller:'wizardUploadGrid',
    viewModel: {
        type: 'wizardUploadGrid'
    },
    reference: 'uploadgrid',
    itemId:'importUpload',
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
    // CLICK / PROOF OF CONCEPT DUMMY FOR CONCEPTION, CLEAN UP, FIX TRANSLATIONS!
    initConfig: function(instanceConfig) {
        var me = this,
            config = {
                height: 300,
                allowDeselect : true,
                sortableColumns:false,
                tbar: [{
                    xtype: 'wizardFileButton',
                    text: 'Add work file(s)',
                    listeners: {
                        change: 'onManualAdd'
                    }
                },{
                    xtype: 'wizardFileButton',
                    text: 'Add pivot file(s)',
                    listeners: {
                        change: 'onManualAddPivot'
                    }
                },{
                    xtype: 'button', text: 'Upload Test', handler: 'testNewUpload'
                },{
                    xtype: 'tbseparator'
                },{
                    xtype: 'button',
                    text: 'remove file',
                    handler: 'removeFiles',
                    //disabled: true,
                    bind: {
                        disabled: '{!uploadgrid.selection}'
                    }
                }],
                viewConfig: {
                    emptyText: 'Drag and drop files here to add them as work files, or to one of the above buttons to add them with a different type. (BUTTON DD TO BE DONE!)'
                },
                //FIXME disable manual sort in general!

                selModel: {
                    selType: 'checkboxmodel'
                },
                columns: [{
                    xtype: 'gridcolumn',
                    width: 140,
                    dataIndex: 'targetLang',
                    //renderer:me.langRenderer,
                    text: 'Target Language'
                },{
                    xtype: 'gridcolumn',
                    dataIndex: 'name',
                    flex: 1,
                    text: 'File'
                },{
                    xtype: 'gridcolumn',
                    width: 90,
                    renderer:function (val,meta,record) {
                        if(val === 'error'){
                            meta.tdCls = 'redTextColumn';
                            return record.get('error');
                        }
                        return val;
                    },
                    dataIndex: 'type',
                    text: 'Type'
                },{
                    xtype: 'gridcolumn',
                    width: 90,
                    formatter: 'fileSize',
                    dataIndex: 'size',
                    text: 'Size'
                }]
            };
        
        if (instanceConfig) {
            me.self.getConfigurator().merge(me, config, instanceConfig);
        }
        return me.callParent([ config ]);
    },

    markInvalid: function (error){
        if(Ext.isEmpty(error)){
            return;
        }
        var me = this,
            gridview  = me.getView();

        me.getStore().removeAll();

        gridview.emptyText = '<div class="x-grid-empty redTextColumn">'+error+'</div>';
        gridview.refresh();
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
            label = lang.get('label');
            md.tdAttr = 'data-qtip="' + label + '"';
            return label;
        }
        if (!val || val === "0") {
            return '';
        }
        return '-';
    }
});