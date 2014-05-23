/*
 START LICENSE AND COPYRIGHT
 
 This file is part of Translate5 Editor Javascript GUI and build on ExtJs 4 lib
 
 Copyright (c) 2013 Marc Mittag; MittagQI - Quality Informatics; All rights reserved.
 
 Contact:  http://www.MittagQI.com/  /  service (ÄTT) MittagQI.com
 
 This file may be used under the terms of the GNU General Public License version 3.0
 as published by the Free Software Foundation and appearing in the file gpl3-license.txt 
 included in the packaging of this file.  Please review the following information 
 to ensure the GNU General Public License version 3.0 requirements will be met:
 http://www.gnu.org/copyleft/gpl.html.

 For this file you are allowed to make use of the same FLOSS exceptions to the GNU 
 General Public License version 3.0 as specified by Sencha for Ext Js. 
 Please be aware, that Marc Mittag / MittagQI take no warranty
 for any legal issue, that may arise, if you use these FLOSS exceptions and recommend
 to stick to GPL 3. For further information regarding this topic please see the attached 
 license.txt of this software package.
 
 MittagQI would be open to release translate5 under EPL or LGPL also, if this could be
 brought in accordance with the ExtJs license scheme. You are welcome to support us
 with legal support, if you are interested in this.
 
 
 @copyright  Marc Mittag, MittagQI - Quality Informatics
 @author     MittagQI - Quality Informatics
 @license    GNU General Public License version 3.0 http://www.gnu.org/copyleft/gpl.html
             with FLOSS exceptions (see floss-exception.txt and ux-exception.txt at the root level)
 
 END LICENSE AND COPYRIGHT 
 */
Ext.define('Editor.view.admin.task.UserPrefsGrid', {
    extend: 'Ext.grid.Panel',
    alias: 'widget.editorAdminTaskUserPrefsGrid',
    store: 'admin.task.UserPrefs',

    initComponent: function() {
        var me = this;

        Ext.applyIf(me, {
            columns: [
                {
                    xtype: 'gridcolumn',
                    dataIndex: 'workflowStep',
                    text: 'Workflow Step'
                },
                {
                    xtype: 'gridcolumn',
                    dataIndex: 'username',
                    text: 'Username'
                },
                {
                    xtype: 'gridcolumn',
                    renderer: function(value, metaData, record, rowIndex, colIndex, store, view) {
                        var fields = value.split(','),
                            result = [];
                        Ext.Object.each(this.fieldLabels, function(k, v){
                            if(Ext.Array.indexOf(fields, k) >= 0) {
                                result.push(v);
                            }
                            else {
                                result.push('<strike>'+v+'</strike>');
                            }
                        });
                        return result.join('<br />');
                    },
                    dataIndex: 'fields',
                    text: 'Target Columns'
                },
                {
                    xtype: 'booleancolumn',
                    dataIndex: 'bool',
                    text: 'Anonymous Columns'
                },
                {
                    xtype: 'gridcolumn',
                    dataIndex: 'userGuid',
                    renderer: function(v, meta, rec) {
                        if(v.length == 0) {
                            return "#UT#Default Entry";
                        }
                        Ext.StoreMgr.get();
                    },
                    text: 'Non-editable Targets'
                }
            ],
            dockedItems: [
                {
                    xtype: 'toolbar',
                    dock: 'top',
                    items: [
                        {
                            xtype: 'button',
                            itemId: 'userPrefAdd',
                            text: 'Add'
                        },
                        {
                            xtype: 'button',
                            itemId: 'userPrefDelete',
                            text: 'Delete'
                        }
                    ]
                }
            ]
        });

        me.callParent(arguments);
    }
});