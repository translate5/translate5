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

Ext.define('Editor.view.admin.lsp.EditWindow', {
    extend: 'Ext.window.Window',
    alias: 'widget.lspEditWindow',
    requires: [
        'Editor.view.admin.customer.TagField',
    ],
    itemId: 'lspEditWindow',
    modal: true,
    layout: 'fit',
    width: 400,
    flex: 1,

    bind: {
        title: '{l10n.lsp.edit}'
    },

    items: [
        {
            xtype: 'form',
            items: [
                {
                    layout: {
                        type: 'hbox',
                        pack: 'start',
                        align: 'stretch'
                    },
                    xtype: 'container',
                    items: [
                        {
                            xtype: 'textfield',
                            name: 'name',
                            bind: {
                                fieldLabel: 'l10n.lsp.form.name',
                            },
                            allowBlank: false,
                            // maxLength: 255,
                            // bind:{
                            //     readOnly: '{record.isDefaultCustomer}'
                            // },
                            // minLength: 1
                        },
                        {
                            xtype: 'textfield',
                            name: 'description',
                            bind: {
                                fieldLabel: 'l10n.lsp.form.description',
                            },
                            allowBlank: false,
                            // bind:{
                            //     readOnly: '{record.isDefaultCustomer}'
                            // },
                            // maxLength: 255
                        },
                    ]
                },
                {
                    layout: {
                        type: 'hbox',
                        pack: 'start',
                        align: 'stretch'
                    },
                    xtype: 'container',
                    items: [
                        {
                            xtype: 'customers',
                            name: 'customers',
                            bind:{
                                fieldLabel: '{l10n.lsp.form.clients}',
                            },
                        },
                    ]
                }
            ]
        }
    ],
    dockedItems: [
        {
            xtype: 'toolbar',
            dock: 'bottom',
            ui: 'footer',
            layout: {
                type: 'hbox',
                pack: 'start'
            },
            items: [
                {
                    xtype: 'tbfill'
                },
                {
                    xtype: 'button',
                    glyph: 'f0c7@FontAwesome5FreeSolid',
                    bind: {
                        text: '{l10n.lsp.form.saveBtn}',
                    },
                    handler: () => {debugger; Ext.ComponentQuery.query('lspPanel')[0].controller.onSaveClick()},
                },
                {
                    xtype: 'button',
                    glyph: 'f00d@FontAwesome5FreeSolid',
                    bind: {
                        text: '{l10n.lsp.form.cancelBtn}',
                    },
                    handler: () => Ext.ComponentQuery.query('lspPanel')[0].controller.onCancelEditClick(),
                }
            ]
        }
    ],

    loadRecord: function (record) {
        const form = this.down('form');

        form.loadRecord(record);
    },
});
